<?php

// TXT fájl soronkénti feldolgozása és POST-olása

$url = 'https://'.$_SERVER['HTTP_HOST'].'/andoc2/index.php?szamla';

$lines = file('XML.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {

    $postData = [
        'xml' => $line
    ];

    echo '<pre style="font-size: 1.4rem">';
    print_r($postData);
    echo '</pre>';

    // $ch = curl_init($url);

    // curl_setopt_array($ch, [
    //     CURLOPT_POST => true,
    //     CURLOPT_POSTFIELDS => http_build_query($postData),
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_TIMEOUT => 30,
    // ]);

    // $response = curl_exec($ch);

    // if (curl_errno($ch)) {
    //     echo "Hiba: " . curl_error($ch) . PHP_EOL;
    // } else {
    //     echo "Sikeresen elküldve." . PHP_EOL;
    //     echo "Válasz: " . $response . PHP_EOL;
    // }

    // curl_close($ch);

    // opcionális várakozás két kérés között
    sleep(1);
}