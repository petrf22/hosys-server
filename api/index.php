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

$app->get('/soutez/{id}/tab', function ($request, $response, $args) {
    $this->logger->addInfo("GET '/soutez/{id}/tab");

    $mapper = new HosysSoutezTabMapper($this->db);

    $data = $mapper->findByHosysSoutezId($args['id']);
    $results = array(
        "hosysSoutezId" => $args['id'],
        "html" => isset($data->htmlTabulka) ? $data->htmlTabulka : "",
    );

    return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

$app->get('/soutez/{id}/info', function ($request, $response, $args) {
    $this->logger->addInfo("GET '/soutez/{id}/info");

    $mapper = new HosysSoutezTabMapper($this->db);

    $data = $mapper->findByHosysSoutezId($args['id']);
    $results = array(
        "hosysSoutezId" => $args['id'],
        "html" => isset($data->htmlSoutez) ? $data->htmlSoutez : "",
    );

    return $this->response->withJson($results, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
});

$app->run();
