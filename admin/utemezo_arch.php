<?php
require_once('../weblap.php');

$log_utvonal = str_replace('.php', '', basename(self())).'.err';
$log = $log_tmp = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
if( filesize($log_utvonal)>(get_logfile_max_meret()*4) ){
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}

// Bejelentések archiválása
// Ha bejelentés Elutasítva státuszban van, archiválunk.
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_ELUTASITVA."'");
$bejelentesek = adatbazis2tomb($bejelentesek, 'recid');
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
bejelentesek_archival( $bejelentesek );

// Ha Lezárt státuszban van, megnézzük munkalapjait, ha minden munkalapja Lezárt vagy Sztornó státuszban van, akkor archiváljuk bejelentést, munkalapokat, mindkettő gyerektábláit bejelentésenként egy tranzakcióban.
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_LEZART."'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
foreach($bejelentesek as $bejelentes){
    $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$bejelentes['recid']}'");
    $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
    if($munkalapok){
        if( count($adatbazis_kapcsolat->s('recid', 'munkalap', "`recid` IN (".implode(', ', $munkalapok).") AND `stat_id`!='".MUNKALAP_STAT_LEZART."' AND `stat_id`!='".MUNKALAP_STAT_SZTORNO."'"))==0 ){
            bejelentesek_archival( $bejelentes['recid'] );
        }
    }
}

// Ha munkalapnak nincs bejelentése és Lezárt vagy Sztornó státuszban van, archiválunk
$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id`='".MUNKALAP_STAT_LEZART."' OR `stat_id`='".MUNKALAP_STAT_SZTORNO."'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
foreach($munkalapok as $munkalap){
    if( count($adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'"))==0 ) {
        munkalapok_archival($munkalap['recid']);
    }
}


// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(
   file_put_contents($log_utvonal, "\r\n\r\n".'LOGÍRÁS HIBA: '.mysqli_error($db_kapcs), FILE_APPEND | LOCK_EX)
);
mysqli_commit($db_kapcs);
//$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));


// Bejelentések archiválása
function bejelentesek_archival($idk) {
    
    global $adatbazis_kapcsolat;
    global $db_kapcs;
    global $log;
    global $log_utvonal;
    
    // Ha csak egy ID-t kaptunk volna
    if(is_numeric($idk) ){
        $idk = array($idk);
    }
    
    $log_tmp = 'Bejelentések archiválása (recidk): '.implode(', ', $idk).'
';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
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
        $gep_txt                = getErtek('nev', 'eszkozkezeles', 'recid', $bejelentes['gep_id']  );
        $stat_txt               = getStatText('bejelentes', $bejelentes['stat_id']);
        $usr_beosztas_txt       = erteklista(ERTEKLISTA_BEOSZTAS, $bejelentes['usr_beosztas_id']);
        $usr_reszleg_txt        = erteklista(ERTEKLISTA_RESZLEG, $bejelentes['usr_reszleg_id']);
        $usr_man_beosztas_txt   = erteklista(ERTEKLISTA_BEOSZTAS, $bejelentes['usr_man_beosztas_id']);
        $usr_man_reszleg_txt    = erteklista(ERTEKLISTA_RESZLEG, $bejelentes['usr_man_reszleg_id']);
        
        array_push($mezok,   'telephely_txt', 'csoport_txt', 'hiba_txt', 'gep_txt', 'stat_txt', 'usr_beosztas_txt', 'usr_reszleg_txt', 'usr_man_beosztas_txt', 'usr_man_reszleg_txt');
        array_push($ertekek, $telephely_txt,  $csoport_txt,  $hiba_txt,  $gep_txt,  $stat_txt,  $usr_beosztas_txt,  $usr_reszleg_txt,  $usr_man_beosztas_txt,  $usr_man_reszleg_txt);
        
        $sql = $adatbazis_kapcsolat->i('bejelentes_arch', $mezok, $ertekek);
        if( $sql === false ) { 
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Dokumentumok
        $doksik = $adatbazis_kapcsolat->s('*', 'bejelentes_dok', "`bejelentes_id`='{$recid}'");
        if( $doksik === false ) { $db_error = true; }
        foreach($doksik as $doksi){
            $mezok   = array();
            $ertekek = array();
            foreach($doksi as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $doksi[$kulcs]);
                }
            }
            $sql = $adatbazis_kapcsolat->i('bejelentes_dok_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Státuszok
        $statuszok = $adatbazis_kapcsolat->s('*', 'bejelentes_statusz', "`bejelentes_recid`='{$recid}'");
        if( $statuszok === false ) { $db_error = true; }
        foreach($statuszok as $statusz){
            $mezok   = array();
            $ertekek = array();
            foreach($statusz as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $statusz[$kulcs]);
                }
            }
            
            $stat_txt       = getStatText('bejelentes', $statusz['stat_id']);
            $erteklista_txt = getErtek('info', 'erteklista', 'recid', $statusz['erteklista_recid']);
            if( $kulcs!='recid' ){
                array_push($mezok,   'stat_txt',   'erteklista_txt');
                array_push($ertekek, $stat_txt,    $erteklista_txt);
            }
            
            $sql = $adatbazis_kapcsolat->i('bejelentes_statusz_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Megjegyzések
        $megjegyzesek = $adatbazis_kapcsolat->s('*', 'megjegyzes', "`tabla`='bejelentes' AND `id`='{$recid}'");
        if( $megjegyzesek === false ) { $db_error = true; }
        foreach($megjegyzesek as $megj){

            $mezok   = array();
            $ertekek = array();
            foreach($megj as $kulcs => $ertek){
                array_push($mezok,   $kulcs);
                array_push($ertekek, htmlspecialchars_decode( $megj[$kulcs]) );
            }
            
            $sql = $adatbazis_kapcsolat->i('megjegyzes_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Bejelentés rekordok törlése
        $sql = $adatbazis_kapcsolat->d('bejelentes', "`recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Dokumentum rekordok törlése
        $sql = $adatbazis_kapcsolat->d('bejelentes_dok', "`bejelentes_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Státusz rekordok törlése
        $sql = $adatbazis_kapcsolat->d('bejelentes_statusz', "`bejelentes_recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Megjegyzés rekodok törlése
        $sql = $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='bejelentes' AND `id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

        // Update: a bejelentést mindig a munkalap kapcsolatával együtt archiváljuk, nincs olyan eset, hogy csak a bejelentést.
        // Ha kell akkor egy tranzakcióban archiváljuk a munkalapokat is
//        if( $munkalap_is ){
            $munkalapok = $adatbazis_kapcsolat->s('munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
            $munkalapok = adatbazis2tomb($munkalapok, 'munkalap_id');
            if($munkalapok){
                if( munkalapok_archival($munkalapok, 'tranzakció nélkül')===false ){
                    $db_error = true;
                }
            }
        
//        // Ha a munkalapján nem archiváljuk, akkor viszont a ML kapcsolatot igen.
//        }else{
//            
//            $kapcsolatok = $adatbazis_kapcsolat->s('*', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
//            if( $kapcsolatok === false ) { $db_error = true; }
//            foreach($kapcsolatok as $kapcs){
//                $mezok   = array();
//                $ertekek = array();
//                foreach($kapcs as $kulcs => $ertek){
//                    if( $kulcs!='recid' ){
//                        array_push($mezok, $kulcs);
//                        array_push($ertekek, $kapcs[$kulcs]);
//                    }
//                }
//
//                $sql = $adatbazis_kapcsolat->i('munkalap_kapcs_arch', $mezok, $ertekek);
//                if( $sql === false ) {
//                    $db_error = true;
//                    $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
//    ';
//                }else{
//                    $log_tmp = $adatbazis_kapcsolat->gp() . '
//    ';
//                }
//                $log .= $log_tmp;
//                file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
//            }
//
//            // Bejelentés törlésekor a munkalap kapcsolatát is töröljük
//            $sql = $adatbazis_kapcsolat->d('munkalap_kapcs', "`bejelentes_id`='{$recid}'");
//            if( $sql === false ) {
//                $db_error = true;
//                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
//    ';
//            }else{
//                $log_tmp = $adatbazis_kapcsolat->gp() . '
//    ';
//            }
//            $log .= $log_tmp;
//            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
//        }
        
        

        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
        } else {
            mysqli_rollback($db_kapcs);
        }
        
    }
    
}


// Munkalapok archiválása
function munkalapok_archival($idk, $tranzakcio_nelkul) {
    
    global $adatbazis_kapcsolat;
    global $db_kapcs;
    global $log;
    global $log_utvonal;
    
    // Ha csak egy ID-t kaptunk volna
    if(is_numeric($idk) ){
        $idk = array($idk);
    }
    
    $log_tmp = 'Munkalapok archiválása (recidk): '.implode(', ', $idk).'
';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

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
        $szemelyzet_txt         = getSzerepkorNev($munkalap['szemelyzet_id']);
        $szallito_txt           = getErtek('nev', 'szallito', 'id', $munkalap['szallito_id']);
        $telephely_txt          = getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']);
        $stat_txt               = getStatText('munkalap', $munkalap['stat_id']);
        $tipus_txt              = getErtek('info', 'erteklista', 'recid', $munkalap['tipus_id']  );
        $jh1_szerep_txt         = getSzerepkorNev($munkalap['jh1_szerep']);
        $jh2_szerep_txt         = getSzerepkorNev($munkalap['jh2_szerep']);
        
        array_push($mezok,   'csoport_txt', 'szemelyzet_txt', 'szallito_txt', 'telephely_txt', 'stat_txt', 'tipus_txt', 'jh1_szerep_txt', 'jh2_szerep_txt');
        array_push($ertekek, $csoport_txt,  $szemelyzet_txt,  $szallito_txt,  $telephely_txt,  $stat_txt,  $tipus_txt,  $jh1_szerep_txt,  $jh2_szerep_txt );
        
        $sql = $adatbazis_kapcsolat->i('munkalap_arch', $mezok, $ertekek);
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Anyagok
        $anyagok = $adatbazis_kapcsolat->s('*', 'munkalap_anyag', "`munkalap_id`='{$recid}'");
        if( $anyagok === false ) { $db_error = true; }
        foreach($anyagok as $anyag){
            $mezok   = array();
            $ertekek = array();
            foreach($anyag as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $anyag[$kulcs]);
                }
            }
            $telephely_txt = getErtek('nev', 'telephely', 'id', $anyag['telephely_id']);
            array_push($mezok,   'telephely_txt');
            array_push($ertekek, $telephely_txt );
            
            $sql = $adatbazis_kapcsolat->i('munkalap_anyag_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Dokumentumok
        $doksik = $adatbazis_kapcsolat->s('*', 'munkalap_dok', "`munkalap_id`='{$recid}'");
        if( $doksik === false ) { $db_error = true; }
        foreach($doksik as $doksi){
            $mezok   = array();
            $ertekek = array();
            foreach($doksi as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $doksi[$kulcs]);
                }
            }
            $sql = $adatbazis_kapcsolat->i('munkalap_dok_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Feladat
        $feladatok = $adatbazis_kapcsolat->s('*', 'munkalap_feladat', "`munkalap_id`='{$recid}'");
        if( $feladatok === false ) { $db_error = true; }
        foreach($feladatok as $feladat){
            $mezok   = array();
            $ertekek = array();
            foreach($feladat as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $feladat[$kulcs]);
                }
            }
            $erteklista_txt = erteklista(ERTEKLISTA_ELV_FELAD, $feladat['erteklista_id']);
            array_push($mezok,   'erteklista_txt');
            array_push($ertekek, $erteklista_txt );

            $sql = $adatbazis_kapcsolat->i('munkalap_feladat_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Kapcsolatok bejelentésekkel
        $kapcsok = $adatbazis_kapcsolat->s('*', 'munkalap_kapcs', "`munkalap_id`='{$recid}'");
        if( $kapcsok === false ) { $db_error = true; }
        foreach($kapcsok as $kapcs){
            $mezok   = array();
            $ertekek = array();
            foreach($kapcs as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $kapcs[$kulcs]);
                }
            }
            
            $sql = $adatbazis_kapcsolat->i('munkalap_kapcs_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Munkaidő
        $munkaidok = $adatbazis_kapcsolat->s('*', 'munkalap_munkaido', "`munkalap_id`='{$recid}'");
        if( $munkaidok === false ) { $db_error = true; }
        foreach($munkaidok as $ido){
            $mezok   = array();
            $ertekek = array();
            foreach($ido as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $ido[$kulcs]);
                }
            }
            $login_txt = getErtek('nev', 'login', 'recid', $ido['login_id']);
            array_push($mezok,   'login_txt');
            array_push($ertekek, $login_txt );
            
            $sql = $adatbazis_kapcsolat->i('munkalap_munkaido_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Státuszok
        $statuszok = $adatbazis_kapcsolat->s('*', 'munkalap_statusz', "`munkalap_recid`='{$recid}'");
        if( $statuszok === false ) { $db_error = true; }
        foreach($statuszok as $statusz){
            $mezok   = array();
            $ertekek = array();
            foreach($statusz as $kulcs => $ertek){
                if( $kulcs!='recid' ){
                    array_push($mezok, $kulcs);
                    array_push($ertekek, $statusz[$kulcs]);
                }
            }
            
            $stat_txt       = getStatText('munkalap', $statusz['stat_id']);
            $erteklista_txt = getErtek('info', 'erteklista', 'recid', $statusz['erteklista_recid']);
            if( $kulcs!='recid' ){
                array_push($mezok,   'stat_txt',   'erteklista_txt');
                array_push($ertekek, $stat_txt,    $erteklista_txt);
            }
            
            $sql = $adatbazis_kapcsolat->i('munkalap_statusz_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Tétel
        $tetelek = $adatbazis_kapcsolat->s('*', 'munkalap_tetel', "`munkalap_id`='{$recid}'");
        if( $tetelek === false ) { $db_error = true; }
        foreach($tetelek as $tetel){
            $mezok   = array();
            $ertekek = array();
            foreach($tetel as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $tetel[$kulcs]);
            }
            
            $erteklista_txt = erteklista(ERTEKLISTA_ATADAS_OKA, $tetel['erteklista_id']);
            array_push($mezok,   'erteklista_txt' );
            array_push($ertekek, $erteklista_txt  );
            
            $sql = $adatbazis_kapcsolat->i('munkalap_tetel_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Email-ek
        $emailek = $adatbazis_kapcsolat->s('*', 'munkalap_email', "`munkalap_recid`='{$recid}'");
        if( $emailek === false ) { $db_error = true; }
        foreach($emailek as $email){
            $mezok   = array();
            $ertekek = array();
            foreach($email as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $email[$kulcs]);
            }
            
            $szallito_txt = getErtek('nev', 'szallito', 'id', $email['szallito_id']);
            array_push($mezok,   'szallito_txt' );
            array_push($ertekek, $szallito_txt  );
            
            $sql = $adatbazis_kapcsolat->i('munkalap_email_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        // Email-ek csatolmányai
        $emailek = $adatbazis_kapcsolat->s('*', 'munkalap_email_dok', "`munkalap_recid`='{$recid}'");
        if( $emailek === false ) { $db_error = true; }
        foreach($emailek as $email){
            $mezok   = array();
            $ertekek = array();
            foreach($email as $kulcs => $ertek){
                array_push($mezok, $kulcs);
                array_push($ertekek, $email[$kulcs]);
            }
            
            $sql = $adatbazis_kapcsolat->i('munkalap_email_dok_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
        
        // Megjegyzések
        $megjegyzesek = $adatbazis_kapcsolat->s('*', 'megjegyzes', "`tabla`='munkalap' AND `id`='{$recid}'");
        if( $megjegyzesek === false ) { $db_error = true; }
        foreach($megjegyzesek as $megj){

            $mezok   = array();
            $ertekek = array();
            foreach($megj as $kulcs => $ertek){
                array_push($mezok,   $kulcs);
                array_push($ertekek, htmlspecialchars_decode( $megj[$kulcs] ) );
            }
            
            $sql = $adatbazis_kapcsolat->i('megjegyzes_arch', $mezok, $ertekek);
            if( $sql === false ) {
                $db_error = true;
                $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
            }else{
                $log_tmp = $adatbazis_kapcsolat->gp() . '
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }

        
        // REKORDOK TÖRLÉSE
        
        // Munkalap rekordok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap', "`recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Anyag rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_anyag', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Dokumentum rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_dok', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Feladat rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_feladat', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Munkalap kapcsolat rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_kapcs', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Munkaidő rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_munkaido', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Státusz rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_statusz', "`munkalap_recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Tétel rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_tetel', "`munkalap_id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Email rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_email', "`munkalap_recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Email csatolmány rekodok törlése
        $sql = $adatbazis_kapcsolat->d('munkalap_email_dok', "`munkalap_recid`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        // Megjegyzés rekodok törlése
        $sql = $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='munkalap' AND `id`='{$recid}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM FUTOTT LE: '.$adatbazis_kapcsolat->gp() . '
';
        }else{
            $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
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