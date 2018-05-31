<?php
// Ütemezés típushoz - A 'kulsos' paraméterrel az szabályozható, hogy Külsős átadáskor, melyik személyzettípus jelenjen meg
$szerepkorok = $adatbazis_kapcsolat->s('recid nev', 'szerepkor', "", 'recid');

$kiveteles_oldalak = array('utemezett_feladatok.php', 'rendszeres_karbantartas.php');

$select_szemelyzet = array();
array_push($select_szemelyzet, array('name' => '- Válasszon -',                             'value' => '0' ));
if( !in_array(basename(self()), $kiveteles_oldalak) ){
    array_push($select_szemelyzet, array('name' => $szerepkorok[HIBABEJELENTO-1]['nev'],             'value' => HIBABEJELENTO,           'szin' => '#E0CBFF', 'kulsos' => 0 ));
}

array_push($select_szemelyzet, array('name' => $szerepkorok[TELEPITETT_SZEMELYZET-1]['nev'],  'value' => TELEPITETT_SZEMELYZET,   'szin' => '#CBE3FF', 'kulsos' => 0 ));
array_push($select_szemelyzet, array('name' => $szerepkorok[KULSO_KIVITELEZO-1]['nev'],       'value' => KULSO_KIVITELEZO,        'szin' => '#FCFFCB', 'kulsos' => 0 ));

// Ha a kivételes oldalakon vagyunk, és a szálloda egyszállodás, akkor ne legyen benne a mobilcsoport, de többszállodás esetén igen
if( in_array(basename(self()), $kiveteles_oldalak) ){
    array_push($select_szemelyzet, array('name' => $szerepkorok[MOBILCSOPORT-1]['nev'],           'value' => MOBILCSOPORT,            'szin' => '#CCFFCB', 'kulsos' => 0, 'id' => 'mobilcsoport_opt', 'class' => 'dn' ));
}

if( !in_array(basename(self()), $kiveteles_oldalak) ) {
    array_push($select_szemelyzet, array('name' => $szerepkorok[MOBILCSOPORT-1]['nev'],              'value' => MOBILCSOPORT,            'szin' => '#CCFFCB', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[MUSZAKI_VEZETO_HELYETTES-1]['nev'],  'value' => MUSZAKI_VEZETO_HELYETTES,'szin' => '#FFCBF1', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[MUSZAKI_VEZETO-1]['nev'],            'value' => MUSZAKI_VEZETO,          'szin' => '#FFCBCB', 'kulsos' => 1, 'hozzarendel_sor' => 4 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[TERULETI_FELELOS-1]['nev'],          'value' => TERULETI_FELELOS,        'szin' => '#E0CBFF', 'kulsos' => 1, 'hozzarendel_sor' => 2 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[MUSZAKI_ADMINISZTRATOR-1]['nev'],    'value' => MUSZAKI_ADMINISZTRATOR,  'szin' => '#E0CBFF', 'kulsos' => 1/*, 'hozzarendel_sor' => 3*/ ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[DISZPECSER-1]['nev'],                'value' => DISZPECSER,              'szin' => '#FFEBCB', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[MSZK_VEZETO-1]['nev'],               'value' => MSZK_VEZETO,             'szin' => '#FFEBCB', 'kulsos' => 1, 'hozzarendel_sor' => 1 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[MUSZAKI_IGAZGATO-1]['nev'],          'value' => MUSZAKI_IGAZGATO,        'szin' => '#E0CBFF', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => $szerepkorok[SZALLODA_IGAZGATO-1]['nev'],         'value' => SZALLODA_IGAZGATO,       'szin' => '#E0CBFF', 'kulsos' => 0 ));
}