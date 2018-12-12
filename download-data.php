<?php
require('consts.php');
require('functions.php');

header('Content-Type: text/plain');

function getCookies($page) {
    $ch = createHosysCURL($page);

    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
    curl_setopt($ch, CURLOPT_NOBODY, true);

    @$result = curl_exec($ch);

    $error = curl_error($ch);

    if ($error) {
        echo 'Error:' . $error;
    }

    curl_close($ch);

    preg_match_all('/^Set\-Cookie:\s*([^;]+)/mi', $result, $matches);

    foreach($matches[1] as $item) {
        if (strpos($item, HOSYS_COOKIE) === 0) {
            return $item;
        }
    }

    throw new Exception('Nenalezeno COOKIE');
}

function getSouteze($page) {
    $ch = createHosysCURL($page);

    @$htmlText = curl_exec($ch);

    $error = curl_error($ch);

    if ($error) {
        echo 'Error:' . $error;
    }

    curl_close($ch);

    $dom = new DOMDocument();
    @$dom->loadHTML($htmlText);

    $xpath = new DOMXPath($dom);

    $result = $xpath->query("//select[@id='FiltrSoutez']/*");

    $souteze = array();
    $poradi = 0;

    foreach($result as $node) {
        $optClass = $node->getAttribute('class');

        if ($optClass == 'cOptionVse') {
            continue;
        }

        $poradi++;
        $uroven = 0;

        if ($optClass == "cOptionUroven") {
            $uroven = 1;
        } else if ($optClass == "cOptionSoutez") {
            $uroven = 2;
        } else if ($optClass == "cOptionCast") {
            $uroven = 3;
        } else {
            echo "Nepodporovaná hodnoty úrovně: ${optClass}.";
            continue;
        }

        $id = trim($node->getAttribute('value'));

        $name = str_replace("\xC2\xA0", " ", $node->nodeValue);
        $name = trim($name);
        $name = str_replace("  ", " ", $name);

        array_push($souteze, array(
            'hosys_soutez_id' => $id,
            'uroven' => $uroven,
            'nazev' => $name,
            'poradi' => $poradi,
        ));
    }

    return $souteze;
}

function processHosysRozpisHtml($pageNum, $hosysSoutezId, $cookie) {
    $extraHttpHeader = array(
        'Content-type: application/x-www-form-urlencoded',
        'Referer: ' . HOSYS_PAGE_ROZPIS,
        'Cookie: ' . $cookie,
    );

    $extraParams = array(
        'My_PIX' => $pageNum,
        //'My_FiltrSoutez' => rawurlencode($hosysSoutezId),
        'My_FiltrSoutez' => $hosysSoutezId,
    );

    // var_dump($extraParams);
    // var_dump(createHosysParams($extraParams));
    // var_dump(http_build_query(createHosysParams($extraParams)));
    // var_dump(urldecode(http_build_query(createHosysParams($extraParams))));

    $params = http_build_query(createHosysParams($extraParams), null, '&', PHP_QUERY_RFC3986);
    // echo "params:" . $params2 . "\n\n";

    $ch = createHosysCURL(HOSYS_PAGE_DEFAULT, $extraHttpHeader);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    @$html = curl_exec($ch);

    // $info = curl_getinfo($ch);
    // var_dump($info);
    // echo "\n";

    $error = curl_error($ch);

    if ($error) {
        echo 'Error:' . $error . "\n";
    }

    curl_close($ch);

    // echo $html . "\n";
    $htmlText = '<html xmlns="http://www.w3.org/1999/xhtml" lang="cs" xml:lang="cs"><head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head><body>' . $html . '</body></html>';
    // echo "\n\n\n" . $htmlText . "\n\n\n";

    $dom = new DOMDocument();
    @$dom->loadHTML($htmlText);

    $hasNextPage = false;
    $rows = array();

    foreach($dom->getElementsByTagName('div') as $div) {
        if ($div->getAttribute('id') == 'Roll') {
            echo "\"${hosysSoutezId}\": #${pageNum}\n";

            foreach($div->getElementsByTagName('tr') as $tr) {
                $trClassName = trim($tr->getAttribute('class'));

                if ($trClassName == 'cTrPrazdneTop') {
                    break;
                }

                if ($trClassName == 'cTrCara') {
                    continue;
                }

                $trOnClick = trim($tr->getAttribute('onclick')); // <tr class="cTrZrusene" onclick="Utkani.Show('C0951', 'JCK MOP')">
                preg_match_all('/Utkani\.Show\(\'([^\']+)\', \'([^\']+)\'\)/', $trOnClick, $matchesOnClick);

                $row = array();

                $row['hosys_rozpis_id'] = $matchesOnClick[2][0] . ' ' . $matchesOnClick[1][0];
                $row['hosys_soutez_id'] = $hosysSoutezId;
                $row['status_row'] = '';
                $row['den_title'] = '';
                $row['den'] = '';
                $row['datum_title'] = '';
                $row['datum'] = '';
                $row['cas_title'] = '';
                $row['cas'] = '';
                $row['stadion_title'] = '';
                $row['stadion'] = '';
                $row['soutez_title'] = '';
                $row['soutez'] = '';
                $row['cislo_title'] = '';
                $row['cislo'] = '';
                $row['domaci_title'] = '';
                $row['domaci'] = '';
                $row['hoste_title'] = '';
                $row['hoste'] = '';
                $row['domaci_zkr_title'] = '';
                $row['domaci_zkr'] = '';
                $row['hoste_zkr_title'] = '';
                $row['hoste_zkr'] = '';
                $row['status'] = '';
                $row['zmena'] = false;

                $row['status_row'] = ltrim($trClassName, 'cTr');
                // echo "\tROW: " . $row['status_row'] . "\n";

                $cTdTeSouperiROfirst = true;

                foreach($tr->getElementsByTagName('td') as $td) {
                    // echo "\t\tCELL: " . $td->getAttribute('class') . "\n";

                    $tdClasses = $td->getAttribute('class');

                    if (strpos($tdClasses, 'cTdTeDen') !== false) {
                        // <span title="Úterý 31.7.2018">Út</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['den_title'] = trim($span->getAttribute('title'));
                        $row['den'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeDatum') !== false) {
                        // <span title="Úterý 31.7.2018">31.7.</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['datum_title'] = trim($span->getAttribute('title'));
                        $row['datum'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeCas') !== false) {
                        // <span title="Úterý 31.7.2018"> 13:00</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['cas_title'] = trim($span->getAttribute('title'));
                        $row['cas'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeStadion') !== false) {
                        // <span title="Chomutov, Zimní stadion, Mostecká 5773, 430 01 Chomutov">CV</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['stadion_title'] = trim($span->getAttribute('title'));
                        $row['stadion'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeSoutez') !== false) {
                        // <span title="Přátelská utkání a turnaje">PRA </span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['soutez_title'] = trim($span->getAttribute('title'));
                        $row['soutez'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeCislo') !== false) {
                        // <span title="Extraliga (AAA ELH)">X004</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['cislo_title'] = trim($span->getAttribute('title'));
                        $row['cislo'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeSouperiRO') !== false && $cTdTeSouperiROfirst) {
                        // <span title="Jekatěrinburg Piráti Chomutov - CV (50302)">Jekatěrinburg</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['domaci_title'] = $span ? trim($span->getAttribute('title')) : '';
                        $row['domaci'] = $span ? trim($span->nodeValue) : '';
                        $row['zmena'] = $span ? $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false) : false;
                    } else if (strpos($tdClasses, 'cTdTeSouperiRO') !== false && !$cTdTeSouperiROfirst) {
                        // <span title="HC DYNAMO PARDUBICE Piráti Chomutov - CV (50302)">HC DYNAMO PARDUBICE</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['hoste_title'] = trim($span->getAttribute('title'));
                        $row['hoste'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);
                    } else if (strpos($tdClasses, 'cTdTeOba') !== false) {
                        // <span title="Jekatěrinburg Piráti Chomutov - CV (50302)">JEK</span>-        <span title="HC DYNAMO PARDUBICE Piráti Chomutov - CV (50302)">PA</span>
                        $span = $td->getElementsByTagName('span')[0];
                        $row['domaci_zkr_title'] = trim($span->getAttribute('title'));
                        $row['domaci_zkr'] = trim($span->nodeValue);
                        $row['zmena'] = $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false);

                        $span = $td->getElementsByTagName('span')[1];
                        $row['hoste_zkr_title'] = $span ? trim($span->getAttribute('title')) : '';
                        $row['hoste_zkr'] = $span ? trim($span->nodeValue) : '';
                        $row['zmena'] = $span ? $row['zmena'] || (strpos($span->getAttribute('class'), 'kZmena') !== false) : false;
                    } else if (strpos($tdClasses, 'cTdTeStav') !== false) {
                        // <td class="cTdTe cTdTeStav">Odehráno</td>
                        $row['status'] = trim($td->nodeValue);
                    } else {
                        // $row['na'] = trim($td->nodeValue);
                        echo 'Nepodporovaná hodnota: ' . $tdClasses . '; ' . trim($td->nodeValue) . "\n";
                    }
                }

                array_push($rows, $row);
            }
        } else {
            foreach($div->getElementsByTagName('a') as $a) {
                if ($a->getAttribute('title') == '[Last]' && !empty($a->getAttribute('onclick'))) {
                    $hasNextPage = true;
                }
            }
        }
    }

    // var_dump($rows);
    echo "\thasNextPage: " . ($hasNextPage ? 'true' : 'false') . "\n";

    saveHosysRozpis($rows);

    return $hasNextPage;
}

function deleteHosysRozpisTemp() {
    try {
        $pdo = createPDO();

        $pdo->exec("TRUNCATE TABLE `hosys_rozpis_temp`");

        print("TRUNCATE TABLE `hosys_rozpis_temp`.\n");

        $pdo = null;
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage() . "<br/>";
        // print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function saveHosysRozpis($rows) {
    if (empty($rows)) {
        return;
    }

    try {
        $pdo = createPDO();

        // echo array_keys($rows[0]) . "\n";
        // echo implode(', ', array_keys($rows[0])) . "\n";

        $placeholders = implode(', ', array_fill(0, count($rows[0]), '?'));
        $sqlValueNames = implode(', ', array_keys($rows[0]));

        $sql = 'INSERT INTO `hosys_rozpis_temp` (' . $sqlValueNames . ') VALUES (' . $placeholders . ')';
        // echo $sql . "\n";
        // echo $sqlValueNames . "\n";
        // echo $placeholders . "\n";

        $stmt = $pdo->prepare($sql);

        // bind param
        foreach ($rows as $row){
            // var_dump($row);
            // var_dump(array_values($row));
            $values = array_values($row);
            // echo join("', '", $values) . "\n";

            $stmt->execute($values);
        }

        $pdo = null;
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage() . "<br/>";
        // print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function updateSouteze($souteze) {
    try {
        $pdo = createPDO();

        $sql = 'INSERT INTO hosys_soutez (hosys_soutez_id, uroven, nazev, poradi) ' .
               'VALUES (?, ?, ?, ?) ' .
               'ON DUPLICATE KEY UPDATE uroven = VALUES(uroven), nazev = VALUES(nazev), poradi = VALUES(poradi)';
        // echo $sql . "\n";
        // echo $sqlValueNames . "\n";
        // echo $placeholders . "\n";

        $stmt = $pdo->prepare($sql);

        // bind param
        foreach($souteze as $soutez) {
            // var_dump($soutez);
            // var_dump(array_values($soutez));
            $values = array(
                $soutez['hosys_soutez_id'],
                $soutez['uroven'],
                $soutez['nazev'],
                $soutez['poradi'],
            );
            // echo join("', '", $values) . "\n";

            $stmt->execute($values);
        }

        $pdo = null;
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage() . "<br/>";
        // print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function updateHosysRozpis() {
    try {
        $pdo = createPDO();

        // Vložení nových záznamů
        $pdo->exec("INSERT INTO hosys_rozpis SELECT * FROM hosys_rozpis_temp tmp " .
                   "WHERE not exists (select 1 from hosys_rozpis r where r.hosys_rozpis_id = tmp.hosys_rozpis_id)");

        // Aktualizace
        $pdo->exec("UPDATE hosys_rozpis AS hr INNER JOIN hosys_rozpis_temp AS tmp ON " .
                   " hr.hosys_rozpis_id = tmp.hosys_rozpis_id and (" .
                   "          hr.hosys_soutez_id <> tmp.hosys_soutez_id" .
                   "       or hr.status_row <> tmp.status_row" .
                   "       or hr.den_title <> tmp.den_title" .
                   "       or hr.den <> tmp.den" .
                   "       or hr.datum_title <> tmp.datum_title" .
                   "       or hr.datum <> tmp.datum" .
                   "       or hr.cas_title <> tmp.cas_title" .
                   "       or hr.cas <> tmp.cas" .
                   "       or hr.stadion_title <> tmp.stadion_title" .
                   "       or hr.stadion <> tmp.stadion" .
                   "       or hr.soutez_title <> tmp.soutez_title" .
                   "       or hr.soutez <> tmp.soutez" .
                   "       or hr.cislo_title <> tmp.cislo_title" .
                   "       or hr.cislo <> tmp.cislo" .
                   "       or hr.domaci_title <> tmp.domaci_title" .
                   "       or hr.domaci <> tmp.domaci" .
                   "       or hr.domaci_zkr_title <> tmp.domaci_zkr_title" .
                   "       or hr.domaci_zkr <> tmp.domaci_zkr" .
                   "       or hr.hoste <> tmp.hoste" .
                   "       or hr.hoste_title <> tmp.hoste_title" .
                   "       or hr.hoste_zkr_title <> tmp.hoste_zkr_title" .
                   "       or hr.hoste_zkr <> tmp.hoste_zkr" .
                   "       or hr.status <> tmp.status" .
                   "       or hr.zmena <> tmp.zmena)" .
                   " SET" .
                   "    hr.hosys_soutez_id = tmp.hosys_soutez_id," .
                   "    hr.status_row = tmp.status_row," .
                   "    hr.den_title = tmp.den_title," .
                   "    hr.den = tmp.den," .
                   "    hr.datum_title = tmp.datum_title," .
                   "    hr.datum = tmp.datum," .
                   "    hr.cas_title = tmp.cas_title," .
                   "    hr.cas = tmp.cas," .
                   "    hr.stadion_title = tmp.stadion_title," .
                   "    hr.stadion = tmp.stadion," .
                   "    hr.soutez_title = tmp.soutez_title," .
                   "    hr.soutez = tmp.soutez," .
                   "    hr.cislo_title = tmp.cislo_title," .
                   "    hr.cislo = tmp.cislo," .
                   "    hr.domaci_title = tmp.domaci_title," .
                   "    hr.domaci = tmp.domaci," .
                   "    hr.domaci_zkr_title = tmp.domaci_zkr_title," .
                   "    hr.domaci_zkr = tmp.domaci_zkr," .
                   "    hr.hoste = tmp.hoste," .
                   "    hr.hoste_title = tmp.hoste_title," .
                   "    hr.hoste_zkr_title = tmp.hoste_zkr_title," .
                   "    hr.hoste_zkr = tmp.hoste_zkr," .
                   "    hr.status = tmp.status," .
                   "    hr.zmena = tmp.zmena," .
                   "    hr.vlozeno = tmp.vlozeno");

        $pdo = null;
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage() . "<br/>";
        // print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function downloadHosysRozpis($souteze) {
    $cookie = getCookies(HOSYS_PAGE_ROZPIS);
    // echo "cookie: " . $cookie . "\n";

    foreach($souteze as $soutez) {
        if ($soutez['uroven'] < 3) {
            continue;
        }

    $pageNum = 0;
    // $pageNum = 451;
        $hosysSoutezId = $soutez['hosys_soutez_id'];

        while (processHosysRozpisHtml($pageNum, $hosysSoutezId, $cookie)) {
        $pageNum++;
    }
}
}

$souteze = getSouteze(HOSYS_PAGE_ROZPIS);
// var_dump($souteze);
updateSouteze($souteze);
// echo "souteze: " . $souteze . "\n";

deleteHosysRozpisTemp();
downloadHosysRozpis($souteze);
updateHosysRozpis();
?>
