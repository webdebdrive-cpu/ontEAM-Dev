<?php
// Nyitólap

// Ha egy tételnek csak deviza nettója van, számoljuk ki a HUF nettót
//if( isset($_GET['tetelek_karbantart_szamla']) ) {
//    require_once '../weblap.php';
//    $tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`dev_net`>0 AND `netto_ert`=0");
//    foreach( $tetelek as $tetel ) {
//        $szulo = $adatbazis_kapcsolat->s2('arfolyam', 'projektszamla', "`recid`='{$tetel['projektszamla_recid']}'");
//        $adatbazis_kapcsolat->u('projektszamla_tetel', array('netto_ert'), array(($tetel['dev_net']*$szulo['arfolyam'])), "`recid`='{$tetel['recid']}'");
//        echo $tetel['recid'].' '.$tetel['dev_net'].' x '.$szulo['arfolyam'].' = '.($tetel['dev_net']*$szulo['arfolyam']) . '<br>';
//
//    }
//    exit;
//}
//if( isset($_GET['tetelek_karbantart_szerzodes']) ) {
//    require_once '../weblap.php';
//    $tetelek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_tetel', "`dev_varh_ert`>0 AND `varh_ert`=0");
//    foreach( $tetelek as $tetel ) {
//        $szulo = $adatbazis_kapcsolat->s2('arfolyam', 'projektszerzodes', "`recid`='{$tetel['projektszerzodes_recid']}'");
//        $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('varh_ert'), array(($tetel['dev_varh_ert']*$szulo['arfolyam'])), "`recid`='{$tetel['recid']}'");
//        echo $tetel['recid'].' '.$tetel['dev_varh_ert'].' x '.$szulo['arfolyam'].' = '.($tetel['dev_varh_ert']*$szulo['arfolyam']) . '<br>';
//
//    }
//    // És ide kellene még egy SQL: ha van már teljesítés dátuma, akkor nem a varh_ert, hanem az ertek oszlopot is ki kéne tölteni
//    exit;
//}


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
if( isset($_GET['teszt']) ) {
    require_once '../weblap.php';
    
    $szamla_tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`>0");
    foreach($szamla_tetelek as $szamla_tetel) {
        $szamla = $adatbazis_kapcsolat->s2('*', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
        $szerzodes_tetel = $adatbazis_kapcsolat->s2('*', 'projektszerzodes_tetel', "`recid`='{$szamla_tetel['projektszerzodes_tetel_recid']}'");
        if( $szerzodes_tetel['dev_varh_ert'] ) {
            $szamla_ertek = $szerzodes_tetel['dev_varh_ert']*$szamla['arfolyam'];
            $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('ertek'), array($szamla_ertek), "`recid`='{$szamla_tetel['projektszerzodes_tetel_recid']}'");
            $adatbazis_kapcsolat->u('projektszamla_tetel', array('netto_ert'), array($szamla_ertek), "`recid`='{$szamla_tetel['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
        }
    }
    exit;
    
}


require_once('header.php');

require_once('dashboard.php');

require_once('footer.php');