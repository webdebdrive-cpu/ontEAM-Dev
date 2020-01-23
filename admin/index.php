<?php
// Nyitólap

// Minősítésre váró dokumentumok utólagos karbantartása
//if( isset($_GET['ez_kell_meg_elesre2']) ) {
//    require_once('../weblap.php');
//    $projektek = $adatbazis_kapcsolat->s('recid', 'projektkezeles');
//    foreach( $projektek as $projekt ) {
//        echo $projekt['recid'] . '<br>';
//        projekt_minositesre_varo_dok_szamol($projekt['recid']);
//    }
//    exit;
//}

require_once('header.php');

require_once('dashboard.php');

require_once('footer.php');