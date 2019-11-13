<?php
// Nyitólap

// 2014-nél régebbi szerződésekhez tartozó dokumentumok automatikusan szerződés tipusuak.
if( isset($_GET['ez_kell_meg_elesre']) ) {
    require_once('../weblap.php');
    $szerzodesek = $adatbazis_kapcsolat->s('recid sz_szam', 'projektszerzodes', "`sz_szam`<='PSZ-14'");
    foreach( $szerzodesek as $szerzodes ) {
        $adatbazis_kapcsolat->u('projektszerzodes_dok', array('tipus'), array(EL_DOK_TIPUS_SZERZODES), "`projektszerzodes_id`='{$szerzodes['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    exit;
}
// Minősítésre váró dokumentumok utólagos karbantartása
if( isset($_GET['ez_kell_meg_elesre2']) ) {
    require_once('../weblap.php');
    $projektek = $adatbazis_kapcsolat->s('recid', 'projektkezeles');
    foreach( $projektek as $projekt ) {
        echo $projekt['recid'] . '<br>';
        projekt_minositesre_varo_dok_szamol($projekt['recid']);
    }
    exit;
}

require_once('header.php');

require_once('dashboard.php');


require_once('footer.php');