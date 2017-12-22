<?php
require_once('../weblap.php');

// Ha a lgofájl nem tartalmazza az !utemezo sikeresen lefutott! szöveget, akkor az első ütemező kifagyott
$log_utvonal = get_ut_logfajl();
$log = fajlbol_olvas($log_utvonal);
if(strpos($log, '!utemezo sikeresen lefutott!')===false){
    if($_SERVER['HTTP_HOST']=='onteam.hu' || $_SERVER['HTTP_HOST']=='demo.szinvatrend.hu'){
        level_kuldes( get_nev(), get_mail(), 'Handari Tamás', 'bk@webdeb.hu', 'Ütemező nem futott le', nl2br($log) );
    }
}

// Tranzakciót indítunk
$db_error = false;
mysqli_autocommit($db_kapcs, false);

// Munkalapok ütemezett dátuma
$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`esnap_d`<='".date('Y-m-d')."' AND `stat_id`='".MUNKALAP_STAT_VARAKOZO."'");
if( $munkalapok === false ) { $db_error = true; }
echo $adatbazis_kapcsolat->gp() . '<br>';
foreach($munkalapok as $munkalap){
    
    // Nyitott státuszt kapnak
    if( addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_VARAKOZO ) === false ) {
        $db_error = true;
    }
    
}


// Tranzakció kommitolása, vagy rollback-ezése
if( !$db_error ) {
    mysqli_commit($db_kapcs);
} else {
    mysqli_rollback($db_kapcs);
}