<?php
// Ütemezés típushoz - A 'kulsos' paraméterrel az szabályozható, hogy Külsős átadáskor, melyik személyzettípus jelenjen meg

$kiveteles_oldalak = array('utemezett_feladatok.php', 'rendszeres_karbantartas.php', 'eseti_fenntartas.php');

$select_szemelyzet = array();
array_push($select_szemelyzet, array('name' => '- Válasszon -',                                 'value' => '0' ));
if( !in_array(basename(self()), $kiveteles_oldalak) ){
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(HIBABEJELENTO),              'value' => HIBABEJELENTO,           'szin' => '#E0CBFF', 'kulsos' => 0 ));
}

array_push($select_szemelyzet, array('name' => getSzerepkorNev(TELEPITETT_SZEMELYZET),          'value' => TELEPITETT_SZEMELYZET,   'szin' => '#CBE3FF', 'kulsos' => 0 ));
array_push($select_szemelyzet, array('name' => getSzerepkorNev(KULSO_KIVITELEZO),               'value' => KULSO_KIVITELEZO,        'szin' => '#FCFFCB', 'kulsos' => 0 ));

// Ha a kivételes oldalakon vagyunk, és a szálloda egyszállodás, akkor ne legyen benne a mobilcsoport, de többszállodás esetén igen
if( in_array(basename(self()), $kiveteles_oldalak) ){
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MOBILCSOPORT),               'value' => MOBILCSOPORT,            'szin' => '#CCFFCB', 'kulsos' => 0, 'id' => 'mobilcsoport_opt', 'class' => 'dn' ));
}

if( !in_array(basename(self()), $kiveteles_oldalak) ) {
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MOBILCSOPORT),              'value' => MOBILCSOPORT,            'szin' => '#CCFFCB', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MUSZAKI_VEZETO_HELYETTES),  'value' => MUSZAKI_VEZETO_HELYETTES,'szin' => '#FFCBF1', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MUSZAKI_VEZETO),            'value' => MUSZAKI_VEZETO,          'szin' => '#FFCBCB', 'kulsos' => 1, 'hozzarendel_sor' => 4 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(TERULETI_FELELOS),          'value' => TERULETI_FELELOS,        'szin' => '#E0CBFF', 'kulsos' => 1, 'hozzarendel_sor' => 2 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MUSZAKI_ADMINISZTRATOR),    'value' => MUSZAKI_ADMINISZTRATOR,  'szin' => '#E0CBFF', 'kulsos' => 1/*, 'hozzarendel_sor' => 3*/ ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(DISZPECSER),                'value' => DISZPECSER,              'szin' => '#FFEBCB', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MSZK_VEZETO),               'value' => MSZK_VEZETO,             'szin' => '#FFEBCB', 'kulsos' => 1, 'hozzarendel_sor' => 1 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(MUSZAKI_IGAZGATO),          'value' => MUSZAKI_IGAZGATO,        'szin' => '#E0CBFF', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(SZALLODA_IGAZGATO),         'value' => SZALLODA_IGAZGATO,       'szin' => '#E0CBFF', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(FEJLESZTESI_VIG_H),         'value' => FEJLESZTESI_VIG_H,       'szin' => '#E0CBFF', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(BERUHAZASI_IGAZGATO),       'value' => BERUHAZASI_IGAZGATO,     'szin' => '#E0CBFF', 'kulsos' => 0 ));
    array_push($select_szemelyzet, array('name' => getSzerepkorNev(LETESITMENY_FELELOS),       'value' => LETESITMENY_FELELOS,     'szin' => '#E0CBFF', 'kulsos' => 0 ));
}


// Csak a felhasználók felületén mutatjuk meg még ezeken felül az általuk létrehozott szerepköröket
if(basename(self())=='login.php'){
    $tovabbi_szerepkorok = $adatbazis_kapcsolat->s('recid nev', 'szerepkor', "`recid`>'12'", 'nev');
    foreach($tovabbi_szerepkorok as $szerepkor){
        array_push($select_szemelyzet, array('name' => $szerepkor['nev'],         'value' => $szerepkor['recid'],       'szin' => '', 'kulsos' => 0 ));
    }
}