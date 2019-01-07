<?php

function createPDO() {
    return new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME.';charset=UTF8', DB_USER, DB_PASS);
}

function nowFromDb() {
    try {
        $pdo = createPDO();

        $stmt = $pdo->prepare("select now() as now"); 
        $stmt->execute(); 

        $row = $stmt->fetch();

        return $row['now'];
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage() . "<br/>";
        // print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function createHosysCURL($page, $extraHttpHeader = null) {
    $httpHeader = array(
        'Connection: keep-alive',
        'Pragma: no-cache',
        'Cache-Control: no-cache',
        // 'Upgrade-Insecure-Requests: 1'
        'Origin: https://www.hosys.cz',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: cs,en-US;q=0.9,en;q=0.8',
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    );

    // var_dump($httpHeader);

    if (is_array($extraHttpHeader)) {
        $httpHeader = array_merge ( $httpHeader, $extraHttpHeader );
        // var_dump($extraHttpHeader);
        // var_dump($httpHeader);
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $page);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    return $ch;
}

function createHosysParams($extraParams) {
    $baseParams = array(
        'My_Formular' => 'Tygrik-Ajax',
        'My_Face' => 'Nooks',
        'My_IDs' => 'NookHosysRozpis',
        'My_Cislo' => '',
        'My_Soutez' => '',
        'My_PIX' => '',
        'My_PortHeight' => '190',
        'My_FiltrDatumOd' => '',
        'My_FiltrRidi' => '~',
        'My_FiltrLed' => '~',
        'My_FiltrMinihokej' => 'A',
        'My_Razeni' => 'DUC',
        'My_FiltrDatumDo' => '',
        'My_FiltrUzemi' => '~',
        'My_FiltrFce' => '~',
        'My_FiltrPratelske' => 'A',
        'My_Varianta' => 'STR',
        'My_FiltrDatum' => 'VSE',
        'My_FiltrSoutez' => '~',
        'My_FiltrSouperi' => '~',
        'My_FiltrOba' => 'OBA',
        'My_FiltrStadion' => '~',
        'My_FiltrCislo' => '~',
        'My_FiltrStav' => 'VSE',
        'My_FiltrRequest' => 'Filtrovat'
    );

    if (is_array($extraParams)) {
        return array_merge ( $baseParams, $extraParams );
    } else {
        return $baseParams;
    }
}
?>