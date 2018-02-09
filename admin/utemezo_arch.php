<?php
require_once('../weblap.php');

// Bejelentések archiválása
// Ha bejelentés Elutasítva státuszban van, archiválunk.
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_ELUTASITVA."'");
bejelentesek_archival( adatbazis2tomb($bejelentesek, 'recid') );

// Ha Lezárt státuszban van, megnézzük munkalapjait, ha minden munkalapja Lezárt státuszban van, akkor archiváljuk bejelentést, munkalapokat, mindkettő gyerektábláit bejelentésenként egy tranzakcióban.
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_LEZART."'");
$bejelentesek = adatbazis2tomb($bejelentesek, 'recid');
if($bejelentesek){
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id` IN (".implode(', ', $bejelentesek).")");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
    if($munkalapok){
        $adatbazis_kapcsolat->s('recid', 'munkalap', "`recid` IN (".implode(', ', $munkalapok).") AND `stat_id`!='".MUNKALAP_STAT_LEZART."'");
        if( count($adatbazis_kapcsolat->s('recid', 'munkalap', "`recid` IN (".implode(', ', $munkalapok).") AND `stat_id`!='".MUNKALAP_STAT_LEZART."'"))==0 ){
            bejelentesek_archival( $bejelentesek, 'munkalapokat is' );
        }
    }
}

// Bejelentések archiválása
function bejelentesek_archival($idk, $munkalap_is){
    
    global $adatbazis_kapcsolat;
    global $db_kapcs;
    
    // Ha csak egy ID-t kaptunk volna
    if(is_numeric($idk) ){
        $idk = array($idk);
    }
    
    // Bejárás
    foreach($idk as $recid) {
        
        // Tranzakciót indítunk
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);
        
        // Bejelentés adatainak átmásolása
        $bejelentes = $adatbazis_kapcsolat->s2('*', 'bejelentes', "`recid`='{$recid}'");
        if( $bejelentes === false ) { $db_error = true; }
        
        $mezok   = array();
        $ertekek = array();
        foreach($bejelentes as $kulcs => $ertek){
            array_push($mezok, $kulcs);
            array_push($ertekek, $bejelentes[$kulcs]);
        }

        // Címkék kiszámítása és ezek felvétele a mezőkbe
        $telephely_txt          = getErtek('nev', 'telephely', 'id', $bejelentes['telephely_id']);
        $csoport_txt            = getErtek('nev', 'bejelentes_csoport', 'recid', $bejelentes['csoport_id']  );
        $hiba_txt               = getErtek('info', 'erteklista', 'recid', $bejelentes['hiba_id']  );
        $obj_txt                = getErtek('nev', 'obj', 'recid', $bejelentes['obj_id']  );
        $gep_txt                = getErtek('nev', 'eszkozkezeles', 'recid', $bejelentes['gep_id']  );
        $stat_txt               = getStatText('bejelentes', $bejelentes['stat_id']);
        $usr_beosztas_txt       = erteklista(ERTEKLISTA_BEOSZTAS, $bejelentes['usr_beosztas_id']);
        $usr_reszleg_txt        = erteklista(ERTEKLISTA_RESZLEG, $bejelentes['usr_reszleg_id']);
        $usr_man_beosztas_txt   = erteklista(ERTEKLISTA_BEOSZTAS, $bejelentes['usr_man_beosztas_id']);
        $usr_man_reszleg_txt    = erteklista(ERTEKLISTA_RESZLEG, $bejelentes['usr_man_reszleg_id']);
        
        array_push($mezok,   'telephely_txt', 'csoport_txt', 'hiba_txt', 'obj_txt', 'gep_txt', 'stat_txt', 'usr_beosztas_txt', 'usr_reszleg_txt', 'usr_man_beosztas_txt', 'usr_man_reszleg_txt');
        array_push($ertekek, $telephely_txt,  $csoport_txt,  $hiba_txt,  $obj_txt,  $gep_txt,  $stat_txt,  $usr_beosztas_txt,  $usr_reszleg_txt,  $usr_man_beosztas_txt,  $usr_man_reszleg_txt);
        
        $sql = $adatbazis_kapcsolat->i('bejelentes_arch', $mezok, $ertekek);
        if( $sql === false ) { $db_error = true; }
        
        // Dokumentumok
        $doksik = $adatbazis_kapcsolat->s('*', 'bejelentes_dok', "`bejelentes_id`='{$recid}'");
        if( $doksik === false ) { $db_error = true; }
        foreach($doksik as $doksi){
            $mezok   = array();
            $ertekek = array();
            foreach($doksi as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $doksi[$kulcs]);
            }
            $sql = $adatbazis_kapcsolat->i('bejelentes_dok_arch', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        // Státuszok
        $statuszok = $adatbazis_kapcsolat->s('*', 'bejelentes_statusz', "`bejelentes_recid`='{$recid}'");
        if( $statuszok === false ) { $db_error = true; }
        foreach($statuszok as $statusz){
            $mezok   = array();
            $ertekek = array();
            foreach($statusz as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $statusz[$kulcs]);
            }
            
            $stat_txt       = getStatText('bejelentes', $statusz['stat_id']);
            $erteklista_txt = getErtek('info', 'erteklista', 'recid', $statusz['erteklista_recid']);
            array_push($mezok,   'stat_txt',   'erteklista_txt');
            array_push($ertekek, $stat_txt,    $erteklista_txt);
            
            $sql = $adatbazis_kapcsolat->i('bejelentes_statusz_arch', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        // Eredeti táblák törlése
        $sql = $adatbazis_kapcsolat->d('bejelentes', "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->d('bejelentes_dok', "`bejelentes_id`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->d('bejelentes_statusz', "`bejelentes_recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        
        // Ha kell akkor egy tranzakcióban archiváljuk a munkalapokat is
        if( $munkalap_is ){
            $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
            $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
            if( munkalapok_archival($munkalapok, 'tranzakció nélkül')===false ){
                $db_error = true;
            }
        }
        
        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
        } else {
            mysqli_rollback($db_kapcs);
        }
        
    }
    
}


// Munkalapok archiválása
function munkalapok_archival($idk, $tranzakcio_nelkul){
    
    global $adatbazis_kapcsolat;
    global $db_kapcs;
    
    // Ha csak egy ID-t kaptunk volna
    if(is_numeric($idk) ){
        $idk = array($idk);
    }
    
    // Bejárás
    foreach($idk as $recid) {
        
        // Tranzakciót indítunk, ha kell
        $db_error = false;
        if(!$tranzakcio_nelkul){
            mysqli_autocommit($db_kapcs, false);
        }
        
        // Munkalap adatainak átmásolása
        $munkalap = $adatbazis_kapcsolat->s2('*', 'munkalap', "`recid`='{$recid}'");
        if( $munkalap === false ) { $db_error = true; }
        
        $mezok   = array();
        $ertekek = array();
        foreach($munkalap as $kulcs => $ertek){
            array_push($mezok, $kulcs);
            array_push($ertekek, $munkalap[$kulcs]);
        }
        
        // Címkék kiszámítása és ezek felvétele a mezőkbe
        $csoport_txt            = getErtek('nev', 'munkalap_csoport', 'recid', $munkalap['csoport_id']  );
        $szemelyzet_txt         = getErtek('nev', 'szerepkor', 'recid', $munkalap['szemelyzet_id']);
        $szallito_txt           = getErtek('nev', 'szallito', 'recid', $munkalap['szallito_id']);
        $telephely_txt          = getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']);
        $stat_txt               = getStatText('munkalap', $munkalap['stat_id']);
        $obj_txt                = getErtek('nev', 'obj', 'recid', $munkalap['obj_id']  );
        $gep_txt                = getErtek('nev', 'eszkozkezeles', 'recid', $munkalap['gep_id']  );
        $tipus_txt              = getErtek('info', 'erteklista', 'recid', $munkalap['tipus_id']  );
        $jh1_szerep_txt         = getErtek('nev', 'szerepkor', 'recid', $munkalap['jh1_szerep']  );
        $jh2_szerep_txt         = getErtek('nev', 'szerepkor', 'recid', $munkalap['jh2_szerep']  );
        
        array_push($mezok,   'csoport_txt', 'szemelyzet_txt', 'szallito_txt', 'telephely_txt', 'stat_txt', 'obj_txt', 'gep_txt', 'tipus_txt', 'jh1_szerep_txt', 'jh2_szerep_txt');
        array_push($ertekek, $csoport_txt,  $szemelyzet_txt,  $szallito_txt,  $telephely_txt,  $stat_txt,  $obj_txt,  $gep_txt,  $tipus_txt,  $jh1_szerep_txt,  $jh2_szerep_txt );
        
        $sql = $adatbazis_kapcsolat->i('munkalap_arch', $mezok, $ertekek);
        if( $sql === false ) { $db_error = true; }
        
        // Dokumentumok
        $doksik = $adatbazis_kapcsolat->s('*', 'munkalap_dok', "`munkalap_id`='{$recid}'");
        if( $doksik === false ) { $db_error = true; }
        foreach($doksik as $doksi){
            $mezok   = array();
            $ertekek = array();
            foreach($doksi as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $doksi[$kulcs]);
            }
            $sql = $adatbazis_kapcsolat->i('munkalap_dok_arch', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        // Státuszok
        $statuszok = $adatbazis_kapcsolat->s('*', 'munkalap_statusz', "`munkalap_recid`='{$recid}'");
        if( $statuszok === false ) { $db_error = true; }
        foreach($statuszok as $statusz){
            $mezok   = array();
            $ertekek = array();
            foreach($statusz as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $statusz[$kulcs]);
            }
            
            $stat_txt       = getStatText('munkalap', $statusz['stat_id']);
            $erteklista_txt = getErtek('info', 'erteklista', 'recid', $statusz['erteklista_recid']);
            array_push($mezok,   'stat_txt',   'erteklista_txt');
            array_push($ertekek, $stat_txt,    $erteklista_txt);
            
            $sql = $adatbazis_kapcsolat->i('munkalap_statusz_arch', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
        
        
        // Eredeti táblák törlése
        $sql = $adatbazis_kapcsolat->d('munkalap', "`recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->d('munkalap_dok', "`munkalap_id`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->d('munkalap_statusz', "`munkalap_recid`='{$recid}'");
        if( $sql === false ) { $db_error = true; }
    }
    
    // Tranzakció kommitolása, vagy rollback-ezése
    // Vagy ha nem tranzakciózunk, akkor retun false- legyen, hogy a bejelentésekből meghívva ott tudjunk rollback-ezni
    if( !$db_error ) {
        if(!$tranzakcio_nelkul){
            mysqli_commit($db_kapcs);
        }
        return true;
    } else {
        if(!$tranzakcio_nelkul){
            mysqli_rollback($db_kapcs);
        }
        return false;
    }
    
}