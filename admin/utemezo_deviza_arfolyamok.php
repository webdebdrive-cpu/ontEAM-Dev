<?php
require_once('../weblap.php');

$log_utvonal = str_replace('.php', '', basename(self())).'.err';
$log = $log_tmp = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
// Adott méret felett felezzük a log fájlt
if( filesize($log_utvonal) > get_logfile_max_meret() ) {
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}

// Tranzakció indul
$db_error = 0;
mysqli_autocommit($db_kapcs, false);

$client = new SoapClient('https://www.mnb.hu/arfolyamok.asmx?wsdl');
$arfolyamok = simplexml_load_string($client -> GetCurrentExchangeRates() -> GetCurrentExchangeRatesResult);
foreach ($arfolyamok -> Day -> Rate as $xml) {
    echo $xml['curr'].' - '.str_replace(',', '.', $xml) .'<br>';
    $letezik = $adatbazis_kapcsolat -> s2('recid', 'mnb', "`deviza`='{$xml['curr']}'");
    
    if( $letezik ) {
        $sql = $adatbazis_kapcsolat -> u('mnb', array('arfolyam',                   'update_dt' ),
                                                array(str_replace(',', '.', $xml),  'NOW()'     ),
                                            "`deviza`='{$xml['curr']}'");
        if( $sql === false ) { $db_error = 1; }
    } else {
        $sql = $adatbazis_kapcsolat -> i('mnb', array('deviza',     'arfolyam',                  'insert_dt' ),
                                                array($xml['curr'], str_replace(',', '.', $xml), 'NOW()'     )
                                );
        if( $sql === false ) { $db_error = 1; }
    }
}
mysqli_commit($db_kapcs);

//$munkalapok = $adatbazis_kapcsolat->s('recid', 'munkalap', "`stat_id` IN ('".MUNKALAP_STAT_IGAZOLASRA_VAR."', '".MUNKALAP_STAT_LEZARASRA_VAR."')");
//foreach ( $munkalapok as $munkalap ) {
//    $statusz = $adatbazis_kapcsolat->s2('insert_dt', 'munkalap_statusz', "`munkalap_recid`='{$munkalap['recid']}'", 'recid desc', '1');
//    if( $statusz['insert_dt'] < date('Y-m-d H:i:s', time() - 60 * 60 * 48) ) {
//        addStatusz( 'munkalap_statusz', $munkalap['recid'], MUNKALAP_STAT_LEZART, '', 'Lezárta: '. get_nev(), 'a rendszer a user' );
//$log_tmp = 'Munkalap lezárt státuszra léptetve (recid): '.$munkalap['recid'].'
//';
//$log .= $log_tmp;
//file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
//    }
//}

// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.


';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
