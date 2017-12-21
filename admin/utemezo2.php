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
$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`esnap_d`='".date('Y-m-d')."'");
if( $munkalapok === false ) { $db_error = true; }
echo $adatbazis_kapcsolat->gp() . '<br>';
foreach($munkalapok as $munkalap){
    
    $megj = 'Ütemezve erre a napra: '.date('Y-m-d');
    
    // Nyitott státuszt kapnak
    if( addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_VARAKOZO, '', $megj ) === false ) {
        $db_error = true;
    }
    
    // És egy megjegyzést is
    addMegj('munkalap', $munkalap['recid'], $megj);
    
}


// Tranzakció kommitolása, vagy rollback-ezése
if( !$db_error ) {
    mysqli_commit($db_kapcs);
} else {
    mysqli_rollback($db_kapcs);
}