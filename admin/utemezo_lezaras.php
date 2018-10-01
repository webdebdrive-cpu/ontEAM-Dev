<?php
require_once('../weblap.php');

$log_utvonal = str_replace('.php', '', basename(self())).'.err';
$log = $log_tmp = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
// Adott méret felett felezzük a log fájlt
if( filesize($log_utvonal)>get_logfile_max_meret() ){
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}

// Bejelentéseknél és munkalapoknál is, ha több mint 48 órája igazolásra vár státuszban van, Lezárt státuszba tesszük
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_KESZ_IG."'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

foreach($bejelentesek as $bejelentes){
    $statusz = $adatbazis_kapcsolat->s2('insert_dt', 'bejelentes_statusz', "`bejelentes_recid`='{$bejelentes['recid']}'", 'recid desc', '1');
    if($statusz['insert_dt']<date('Y-m-d H:i:s', time()-60*60*48)){
        addStatusz( 'bejelentes_statusz', $bejelentes['recid'], BEJELENTES_STAT_LEZART, '', 'Lezárta: '. get_nev(), 'a rendszer a user' );
$log_tmp = 'Bejelentés lezárt státuszra léptetve (recid): '.$bejelentes['recid'].'
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    }
}

$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id` IN ('".MUNKALAP_STAT_IGAZOLASRA_VAR."', '".MUNKALAP_STAT_LEZARASRA_VAR."')");
foreach($munkalapok as $munkalap){
    $statusz = $adatbazis_kapcsolat->s2('insert_dt', 'munkalap_statusz', "`munkalap_recid`='{$munkalap['recid']}'", 'recid desc', '1');
    if($statusz['insert_dt']<date('Y-m-d H:i:s', time()-60*60*48)){
        addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_LEZART, '', 'Lezárta: '. get_nev(), 'a rendszer a user' );
$log_tmp = 'Munkalap lezárt státuszra léptetve (recid): '.$munkalap['recid'].'
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    }
}

// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

//$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));
mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);