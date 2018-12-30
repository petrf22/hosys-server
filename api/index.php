<?php
require('../consts.php');


// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}

/**
 * Get hearder Authorization
 * */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
/**
* get access token from header
* */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }

    return null;
}

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

require 'remove_accents.function.php';

require 'classes/LoggedUser.php';
require 'classes/Mapper.php';
require 'classes/AbstractEntity.php';
require 'classes/HosysSoutezEntity.php';
require 'classes/HosysSoutezMapper.php';
require 'classes/HosysRozpisEntity.php';
require 'classes/HosysRozpisMapper.php';

define("ERR_CODE_BASE", 40000);
define("ERR_CODE_ACTIVITY_NOT_FOUND", ERR_CODE_BASE + 1);
define("ERR_CODE_DEFAULT_GROUP_NOT_FOUND", ERR_CODE_BASE + 2);
define("ERR_CODE_DUPLICATE_ACTIVITI_NAME", ERR_CODE_BASE + 3);
define("ERR_CODE_DUPLICATE_EMAIL", ERR_CODE_BASE + 4);
define("ERR_CODE_USER_NOT_FOUND", ERR_CODE_BASE + 5);
define("ERR_CODE_USER_NOT_APPROVED", ERR_CODE_BASE + 6);
define("ERR_CODE_NOT_RIGHT", ERR_CODE_BASE + 7);
define("ERR_CODE_DUPLICATE_GROUP_NAME", ERR_CODE_BASE + 8);
define("ERR_CODE_GROUP_NOT_FOUND", ERR_CODE_BASE + 9);

$config = [
    'settings' => [
        'debug' => true,
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/../logs/app.log',
        ],
        'db' => [
            'host'   => DB_SERVER,
            'user'   => DB_USER,
            'pass'   => DB_PASS,
            'dbname' => DB_NAME,
        ],
    ],
];

$app = new Slim\App($config);

$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:hostprivate actSrv: ActivityService,=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=utf8", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('slim-app');
    $file_handler = new \Monolog\Handler\StreamHandler(sys_get_temp_dir() . "/slim-app.log");
    // $file_handler = new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/app.log');

    $logger->pushHandler($file_handler);

    return $logger;
};

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if (strval($code) == "23000") {
            if (strpos($message, "Duplicate entry") !== false) {
                if (strpos($message, "for key 'act_user_email'") !== false) {
                    $code = ERR_CODE_DUPLICATE_EMAIL;
                    $message = "Zadaný email již existuje.";
                } else if (strpos($message, "for key 'name'") !== false) {
                    $code = ERR_CODE_DUPLICATE_ACTIVITI_NAME;
                    $message = "Zadaný název aktivity již existuje.";
                } else if (strpos($message, "for key 'act_grp_name'") !== false) {
                    $code = ERR_CODE_DUPLICATE_GROUP_NAME;
                    $message = "Zadaný název skupiny již existuje.";
                }
            } else if (strpos($message, "Integrity constraint violation") !== false) {
                if (strpos($message, "CONSTRAINT `user_group`") !== false) {
                    $code = ERR_CODE_DUPLICATE_EMAIL;
                    $message = "Skupinu nelze smazat, protože jsou do ní zařazení členové.";
                }
            }
        }

        return $c['response']
            ->withStatus(500)
            ->withJson(["code" => $code, "message" => $message], null, JSON_UNESCAPED_UNICODE| JSON_NUMERIC_CHECK);
    };
};

$app->get('/rozpis', function ($request, $response, $args) {
  $this->logger->addInfo("GET '/rozpis");

  $mapper = new HosysRozpisMapper($this->db);

  $params = $request->getQueryParams();
  $soutez = isset($params['soutez']) ? $params['soutez'] : "";
  $dayMin = isset($params['dayMin']) ? intval($params['dayMin']) : -10;
  $dayMax = isset($params['dayMax']) ? intval($params['dayMax']) : 10;
  $offset = isset($params['offset']) ? intval($params['offset']) : 0;
  $max = isset($params['max']) ? intval($params['max']) : 10;

  if (!empty($soutez)) {
    $results = $mapper->findBySoutez($soutez, $dayMin, $dayMax);
  } else {
    $results = $mapper->findAll($offset, $max);
  }

  return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

$app->get('/rozpis/{id}', function ($request, $response, $args) {
    $this->logger->addInfo("GET '/rozpis/{id}");

    $mapper = new HosysRozpisMapper($this->db);

    $results = $mapper->findById($args['id']);

    return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

$app->get('/soutez', function ($request, $response, $args) {
    $this->logger->addInfo("GET '/soutez");

    $params = $request->getQueryParams();
    $offset = isset($params['offset']) ? intval($params['offset']) : 0;
    $max = isset($params['max']) ? intval($params['max']) : 10;

    $mapper = new HosysSoutezMapper($this->db);

    $results = $mapper->findAll($offset, $max);

    return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

$app->get('/soutez/{id}', function ($request, $response, $args) {
    $this->logger->addInfo("GET '/soutez/{id}");

    $mapper = new HosysSoutezMapper($this->db);

    $results = $mapper->findById($args['id']);

    return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

// /** Registrace nového uživatele. Pokud není vytvořena aktivita, tak se vytvoří. */
// $app->put('/register', function ($request, $response, $args) {
//     // var_dump($request->getParsedBody());

//     $newUserData = $request->getParsedBody();
//     $actId = filter_var($newUserData["activity"]["actId"], FILTER_SANITIZE_NUMBER_INT);
//     $actName = trim($newUserData["activity"]["name"]);
//     $grpDefaultName = "Výchozí skupina";

//     try {
//         $this->db->beginTransaction();

//         $actMapper = new ActivityMapper($this->db);
//         $grpMapper = new GroupMapper($this->db);
//         $userMapper = new UserMapper($this->db);
//         $admin = false;
//         $approved = null;

//         $activity = null;
//         $group = null;
//         $user = null;
//         $actGroupId = null;

//         if ($actId == 0) {
//             $admin = true;
//             $approved = date('Y-m-d H:i:s');
//             $actId = $actMapper->create($actName);

//             $groupEntity = new GroupEntity();
//             $groupEntity->actGroupId = 0;
//             $groupEntity->actId = $actId;
//             $groupEntity->userDefault = true;
//             $groupEntity->name = $grpDefaultName;
//             $groupEntity->notifyBefore = 24;
//             $groupEntity->actTypTime = "H";

//             $actGroupId = $grpMapper->create($groupEntity);
//         } else {
//             if (!$actMapper->existsById($actId)) {
//                 throw new Exception("Aktivita #$actId nenalezena", ERR_CODE_ACTIVITY_NOT_FOUND);
//             }

//             $group = $grpMapper->findUserDefaultByActId($actMapper->existsById($actId));

//             if ($group == null) {
//                 throw new Exception("Nenalezena výchozí skupina pro aktivitu #$actId", ERR_CODE_DEFAULT_GROUP_NOT_FOUND);
//             }

//             $actGroupId = $group->actGroupId;
//         }


//         $userEntity = new UserEntity();

//         $userEntity->actUserId = 0;
//         $userEntity->actId = $actId;
//         $userEntity->actGroupId = $actGroupId;
//         $userEntity->firstName = trim($newUserData["firstName"]);
//         $userEntity->lastName = trim($newUserData["lastName"]);
//         $userEntity->alias = trim($newUserData["alias"]);
//         $userEntity->email = trim($newUserData["email"]);
//         $userEntity->telephone = null;
//         $userEntity->mandatory = false;
//         $userEntity->admin = $admin;
//         $userEntity->approved = $approved;
//         $userEntity->sendNotify = true;
//         $userEntity->password = password_hash(trim($newUserData["password"]), PASSWORD_BCRYPT);

//         $userIdIns = $userMapper->create($userEntity);

//         $this->db->commit();
//     } catch(PDOException $e) {
//         $this->db->rollback();

//         throw $e;
//     }

//     //return $this->response->withJson($user, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// /** Vytvoření uživatele v rámci aktivity */
// $app->put('/user', function ($request, $response, $args) {
//     $this->logger->addInfo("PUT '/user");
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));
//     $actUserId = intval($token->getClaim('actUserId'));

//     if (!$admin) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $jsonUser = $request->getParsedBody();

//     $userEntity = new UserEntity();

//     $userEntity->actUserId = 0;
//     $userEntity->actId = $actId;
//     $userEntity->actGroupId = intval($jsonUser["group"]["actGroupId"]);
//     $userEntity->firstName = trim($jsonUser["firstName"]);
//     $userEntity->lastName = trim($jsonUser["lastName"]);
//     $userEntity->alias = trim($jsonUser["alias"]);
//     $userEntity->email = trim($jsonUser["email"]);
//     $userEntity->telephone = trim($jsonUser["telephone"]);
//     $userEntity->mandatory = $jsonUser["mandatory"];
//     $userEntity->admin = false;
//     $userEntity->approved = $jsonUser["approved"] != null ? date('Y-m-d H:i:s') : null;
//     $userEntity->sendNotify = boolval($jsonUser["sendNotify"]);

//     $userMapper = new UserMapper($this->db);

//     $userIdIns = $userMapper->create($userEntity);

//     $user = $userMapper->findById($actId, $userIdIns);

//     return $this->response->withJson($user, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->post('/user', function ($request, $response, $args) {
//     $this->logger->addInfo("POST '/user");
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));
//     $actUserId = intval($token->getClaim('actUserId'));

//     $jsonUser = $request->getParsedBody();

//     if (!$admin && $actUserId != intval($jsonUser["actUserId"])) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $userMapper = new UserMapper($this->db);

//     $user = $userMapper->findById($actId, $jsonUser["actUserId"]);

//     if ($user == null) {
//         throw new Exception("Uživatel nenalezen.", ERR_CODE_USER_NOT_FOUND);
//     }

//     $user->actGroupId = intval($jsonUser["group"]["actGroupId"]);
//     $user->firstName = trim($jsonUser["firstName"]);
//     $user->lastName = trim($jsonUser["lastName"]);
//     $user->alias = trim($jsonUser["alias"]);
//     $user->email = trim($jsonUser["email"]);
//     $user->telephone = trim($jsonUser["telephone"]);
//     $user->mandatory = $jsonUser["mandatory"];
//     $user->approved = $user->approved == null && $jsonUser["approved"] != null ? date('Y-m-d H:i:s') : $user->approved;
//     $user->sendNotify = boolval($jsonUser["sendNotify"]);

//     $userMapper->update($user);

//     $user = $userMapper->findById($actId, $user->actUserId);

//     return $this->response->withJson($user, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->get('/user/{id}', function ($request, $response, $args) {
//     $this->logger->addInfo("GET '/user" . $args['id']);
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));

//     $mapper = new UserMapper($this->db);

//     $user = $mapper->findById($actId, $args['id']);

//     return $this->response->withJson($user, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->delete('/user/{id}', function ($request, $response, $args) {
//     $this->logger->addInfo("DELETE '/user" . $args['id']);
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));

//     if (!$admin) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $mapper = new UserMapper($this->db);

//     $user = $mapper->delete($actId, $args['id']);

//     return $this->response;
// });

// $app->get('/users', function ($request, $response, $args) {
//     $this->logger->addInfo("GET '/users");

//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));

//     $mapper = new UserMapper($this->db);

//     $users = $mapper->findAllByActId($actId);

//     return $this->response->withJson($users, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->get('/groups', function ($request, $response, $args) {
//     $this->logger->addInfo("GET '/groups");

//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));

//     $mapper = new GroupMapper($this->db);

//     $groups = $mapper->findAllByActId($actId);

//     return $this->response->withJson($groups, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->get('/group/{id}', function ($request, $response, $args) {
//     $this->logger->addInfo("GET '/group" . $args['id']);
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));

//     $mapper = new GroupMapper($this->db);

//     $group = $mapper->findById($actId, $args['id']);

//     return $this->response->withJson($group, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->put('/group', function ($request, $response, $args) {
//     $this->logger->addInfo("PUT '/group");
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));

//     if (!$admin) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $jsonGroup = $request->getParsedBody();

//     $groupEntity = new GroupEntity();

//     $groupEntity->actGroupId = 0;
//     $groupEntity->actId = $actId;
//     $groupEntity->name = trim($jsonGroup["name"]);
//     $groupEntity->notifyBefore = intval(trim($jsonGroup["notifyBefore"]));
//     $groupEntity->actTypTime = trim($jsonGroup["actTypTime"]) != "M" ? "H" : "M";
//     $groupEntity->userDefault = boolVal($jsonGroup["userDefault"]);

//     $groupMapper = new GroupMapper($this->db);

//     try {
//         $this->db->beginTransaction();

//         if ($groupEntity->userDefault) {
//             $groupMapper->resetDefault($actId);
//         }

//         $grpIdIns = $groupMapper->create($groupEntity);

//         $this->db->commit();

//         // $group = $groupMapper->findByName($actId, $groupEntity->name);
//         $group = $groupMapper->findById($actId, $grpIdIns);

//     } catch(PDOException $e) {
//         $this->db->rollback();

//         throw $e;
//     }


//     return $this->response->withJson($group, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });

// $app->post('/group', function ($request, $response, $args) {
//     $this->logger->addInfo("POST '/group");
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));

//     $jsonGroup = $request->getParsedBody();

//     if (!$admin) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $groupMapper = new GroupMapper($this->db);

//     $groupEntity = $groupMapper->findById($actId, $jsonGroup["actGroupId"]);

//     if ($groupEntity == null) {
//         throw new Exception("Skupina nenalezen.", ERR_CODE_GROUP_NOT_FOUND);
//     }

//     $jsonUserDefaultValue = boolVal($jsonGroup["userDefault"]);
//     $changeUserDefault = !$groupEntity->userDefault && $jsonUserDefaultValue;

//     $groupEntity->name = trim($jsonGroup["name"]);
//     $groupEntity->notifyBefore = intval(trim($jsonGroup["notifyBefore"]));
//     $groupEntitytypTime = trim($jsonGroup["actTypTime"]) != "M" ? "H" : "M";
//     $groupEntity->userDefault = $jsonUserDefaultValue;

//     try {
//         $this->db->beginTransaction();

//         if ($changeUserDefault) {
//             $groupMapper->resetDefault($actId);
//         }

//         $groupMapper->update($groupEntity);

//         $groupEntity = $groupMapper->findById($actId, $groupEntity->actGroupId);

//         $this->db->commit();

//         return $this->response->withJson($groupEntity, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
//     } catch(PDOException $e) {
//         $this->db->rollback();

//         throw $e;
//     }
// });

// $app->delete('/group/{id}', function ($request, $response, $args) {
//     $this->logger->addInfo("DELETE '/group" . $args['id']);
//     $token = (new Lcobucci\JWT\Parser())->parse((string) getBearerToken()); // Parses from a string
//     $actId = intval($token->getClaim('actId'));
//     $admin = boolval($token->getClaim('admin'));

//     if (!$admin) {
//         throw new Exception("Nemáte oprávnění pro tuto operaci.", ERR_CODE_NOT_RIGHT);
//     }

//     $mapper = new GroupMapper($this->db);

//     $group = $mapper->delete($actId, $args['id']);

//     return $this->response;
// });

// $app->post('/authenticate', function ($request, $response, $args) {
//     $this->logger->addInfo("POST '/authenticate");

//     //var_dump($request->getParsedBody());
//     $mapper = new UserMapper($this->db);

//     $userEntity = $mapper->findByLoginData($request->getParsedBody());

//     if ($userEntity == null) {
//         throw new Exception("Uživatel nenalezen. Zkontrolujte správně zadaný název aktivity, e-mail a heslo", ERR_CODE_USER_NOT_FOUND);
//     }

//     if ($userEntity->approved == null) {
//         throw new Exception("Uživatel nebyl zatím schválen. Po schválení obdržíte e-mail.", ERR_CODE_USER_NOT_APPROVED);
//     }

//     $token = (new Lcobucci\JWT\Builder())
//         ->setIssuer('http://slezeme.se') // Configures the issuer (iss claim)
//         ->setAudience('http://slezeme.se') // Configures the audience (aud claim)
//         ->setId('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
//         ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
//         ->setNotBefore(time() + 60) // Configures the time that the token can be used (nbf claim)
//         ->setExpiration(time() + 3600) // Configures the expiration time of the token (exp claim)
//         ->set('actUserId', $userEntity->actUserId) // Configures a new claim, called "actUserId"
//         ->set('actId', $userEntity->actId) // Configures a new claim, called "actId"
//         ->set('admin', $userEntity->admin) // Configures a new claim, called "admin"
//         ->getToken(); // Retrieves the generated toke

//     $loggedUser = new LoggedUser($userEntity, (string)$token);

//     return $this->response->withJson($loggedUser, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
// });


// /*
// $app->post('/ticket/new', function (Request $request, Response $response) {
//     $data = $request->getParsedBody();
//     $ticket_data = [];
//     $ticket_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
//     $ticket_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
// });
// */
$app->run();
