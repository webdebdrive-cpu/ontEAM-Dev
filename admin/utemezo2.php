<?php
require_once('../weblap.php');
require_once('datumra_utemezes.php');

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