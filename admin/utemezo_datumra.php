<?php
require_once('../weblap.php');

$log = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
$log_utvonal = str_replace('.php', '', basename(self())).'.err';
// Adott méret felett felezzük a log fájlt
if( filesize($log_utvonal)>get_logfile_max_meret() ){
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}
// Munkalapok ütemezett dátuma
$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`esnap_d`<='".date('Y-m-d')."' AND `stat_id`='".MUNKALAP_STAT_HATIDO_UTEMEZETT."'");
if( $munkalapok === false ) { $db_error = true; }
$log .= $adatbazis_kapcsolat->gp() . '
';
foreach($munkalapok as $munkalap){
    
    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Nyitott státuszt kapnak
    if( addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_NYITOTT, '', '', 'a rendszer a user' ) === false ) {
        $db_error = true;
        $log .= 'NEM SIKERÜLT Munkalapok nyitott státuszra léptetni (recid): '.$munkalap['recid'].'
';
    }else{
        $log .= 'Munkalap nyitott státuszra léptetve (recid): '.$munkalap['recid'].'
';
    }
    
    // Munkalaphoz tartozó bejelentések (igaz, csak egy van, de megírtuk általánosan) folyamatbanra váltanak
    $bejelentesek = $adatbazis_kapcsolat->s('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap['recid']}'");
    foreach($bejelentesek as $bejelentes){
        if( addStatusz( 'bejelentes_statusz', $bejelentes['bejelentes_id'], BEJELENTES_STAT_FOLYAMATBAN, '', '', 'a rendszer a user' ) === false ) {
            $db_error = true;
            $log .= 'NEM SIKERÜLT Munkalaphoz tartozó bejelentést folyamatban státuszra léptetni (recid): '.$munkalap['recid'].'
';
        }else{
            $log .= 'Munkalaphoz tartozó bejelentés folyamatban státuszra léptetve (recid): '.$bejelentes['bejelentes_id'].'
';
        }
    }
    
    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }
    
}

// A lefutást is eltároljuk
$log .= date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
fajlba_mentes($log_utvonal, $log);
$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));