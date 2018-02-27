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
// Bejelentéseknél és munkalapoknál is, ha több mint 48 órája igazolásra vár státuszban van, Lezárt státuszba tesszük
$bejelentesek = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_KESZ_IG."'");
$log .= $adatbazis_kapcsolat->gp() . '
';
foreach($bejelentesek as $bejelentes){
    $statusz = $adatbazis_kapcsolat->s2('insert_dt', 'bejelentes_statusz', "`bejelentes_recid`='{$bejelentes['recid']}'", 'recid desc', '1');
    if($statusz['insert_dt']<date('Y-m-d H:i:s', time()-60*60*48)){
        addStatusz( 'bejelentes_statusz', $bejelentes['recid'], BEJELENTES_STAT_LEZART, '', 'Lezárta: '. get_nev(), 'a rendszer a user' );
$log .= 'Bejelentés lezárt státuszra léptetve (recid): '.$bejelentes['recid'].'
';
    }
}

$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id` IN ('".MUNKALAP_STAT_IGAZOLASRA_VAR."', '".MUNKALAP_STAT_LEZARASRA_VAR."')");
foreach($munkalapok as $munkalap){
    $statusz = $adatbazis_kapcsolat->s2('insert_dt', 'munkalap_statusz', "`munkalap_recid`='{$munkalap['recid']}'", 'recid desc', '1');
    if($statusz['insert_dt']<date('Y-m-d H:i:s', time()-60*60*48)){
        addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_LEZART, '', 'Lezárta: '. get_nev(), 'a rendszer a user' );
$log .= 'Munkalap lezárt státuszra léptetve (recid): '.$munkalap['recid'].'
';
    }
}

// A lefutást is eltároljuk
$log .= date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
fajlba_mentes($log_utvonal, $log);
$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));