<?php

if( isset($_GET['pdf_felurir']) ) {
    $uj_fajl = '../tartalom/uj_pdf.pdf';
    $regi_fajl = '../tartalom/eszkozigenyles_megr/26/670/DH_te_megrendeles_TE2026552.pdf';
    copy($uj_fajl, $regi_fajl);
    unlink($uj_fajl);
    exit;
}

// Munkalap végigléptetése
//if( FALSE && isset($_GET['munkalap_leptet']) ) {
//    require_once '../weblap.php';
//    $ikt_szamok = array('M20/0018370', 'M20/0016709', 'M20/0016708', 'M20/0016706', 'M20/0016705');
//    $munkalapok = $adatbazis_kapcsolat->s('recid szemelyzet_id', 'munkalap', "`ikt_szam` IN ('".implode('\', \'', $ikt_szamok)."')");
//    foreach( $munkalapok as $ml ) {
//        
//        // Ha egy ML-nak nincs feladata, akkor kreálunk egyet
//        $tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id`='{$ml['recid']}'");
//        if( !$tetel ) {
//            $tetel = $adatbazis_kapcsolat->i('munkalap_tetel',  array('munkalap_id', 'loginnev', 'insert_dt', 'update_dt',  'insert_usr',  'update_usr'),
//                                                                array($ml['recid'],  get_nev(),  'NOW()',     'NOW()',      get_nev(),      get_nev()  )
//                                            );
//echo $adatbazis_kapcsolat->gp() . '<br>';
//            $adatbazis_kapcsolat->i('munkalap_feladat', array('munkalap_id', 'tetel_id', 'erteklista_id', 'insert_dt', 'update_dt',  'insert_usr',  'update_usr'),
//                                                        array($ml['recid'],  $tetel,     25,              'NOW()',     'NOW()',      get_nev(),     get_nev()   )
//                                    );
//echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//        // Ha a hozzárendelt személyzet külsős, akkor nincs felvéve státusz, hanem ekkor kell elvégzett munkát hozzáadni.
//        // Ezután igazolásra vár státuszú lesz, majd lezárásra vár, végül lezárt.
//        if( $ml['szemelyzet_id'] == KULSO_KIVITELEZO ) {
//            
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_IGAZOLASRA_VAR, 0, 'Automatikusan lezárva', '-Onteam a user-' );
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_LEZARASRA_VAR,  0, 'Automatikusan lezárva', '-Onteam a user-' );
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_LEZART,         0, 'Automatikusan lezárva', '-Onteam a user-' );
//        } else {
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_FELVEVE,        0, 'Automatikusan lezárva', '-Onteam a user-' );
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_LEZARASRA_VAR,  0, 'Automatikusan lezárva', '-Onteam a user-' );
//            addStatusz( 'munkalap_statusz', $ml['recid'], MUNKALAP_STAT_LEZART,         0, 'Automatikusan lezárva', '-Onteam a user-' );
//        }
//    }
//    
//    exit;
//}

// Felhasználó - törzsadat szinkron
//if( FALSE && isset($_GET['felh_torzsadat']) ) {
//    require_once '../weblap.php';
//    $loginok = $adatbazis_kapcsolat->s('*', 'login', "");
//    foreach ( $loginok as $login ) {
//        if( $login['nev'] ) {
//            if( in_array($login['szemelyzet_id'], loginbol_torzsadatot_szemelyzetek() ) ) {
//
//                $_POST['aktiv']     = $login['aktiv'];
//                $_POST['virtual']   = $login['virtual'];
//                $_POST['teljes_nev']= $login['teljes_nev'];
//                $_POST['nev']       = $login['nev'];
//                // Aktiv/Virtuál pipa törzsadatoknál is frissül
//                $torzsadat = $adatbazis_kapcsolat->s2('*', 'torzsadat', "`felh_loginnev`='{$login['nev']}'");
//                // Törzsadat tábla updatelése és annak logjába írás
//
//                // Létrehozzuk, ha még nem létezne
//                if( !$torzsadat ) {
//                    $uj_torzs = $adatbazis_kapcsolat->i('torzsadat',
//                                                                array('nev',                'felh_loginnev', 'aktiv',         'virtual'         ),
//                                                                array($_POST['teljes_nev'], $_POST['nev'],   $_POST['aktiv'], $_POST['virtual'] )
//                                                        );
//                    // Napi munkaidőt is bellítjuk
//                    $adatbazis_kapcsolat->i('torzsadat_napi_munkaido',  array('torzsadat_recid', 'datum',        'ora', 'insert_dt'),
//                                                                        array($uj_torzs,         date('Y-m-01'), '8',   'NOW()'    )
//                                            );
//                    addMegj('torzsadat', $uj_torzs, 'Új személyzet hozzáadva');
//                    $torzsadat = $adatbazis_kapcsolat->s2('*', 'torzsadat', "`felh_loginnev`='{$login['nev']}'");
//                }
//                
//                torzsadat_update_es_log($torzsadat);
//                
//            }
//        }
//    }
//    
//    // Törzsadatokból töröljük azokat a rekordokat, aminek nincs felhasználó párja
//    // Illetve ha van párja, akkor ellenőrizzük, hogy van-e Heti munkaidő beállítva, vagy sem. Ha kell pótoljuk.
//    $torzsadatok = $adatbazis_kapcsolat->s('recid felh_loginnev', 'torzsadat', "");
//    foreach( $torzsadatok as $torzs ) {
//        if( !$adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$torzs['felh_loginnev']}'") ) {
//            $adatbazis_kapcsolat->d('torzsadat', "`recid`='{$torzs['recid']}'");
//        } else {
//            if( !$adatbazis_kapcsolat->s('recid', 'torzsadat_napi_munkaido', "`torzsadat_recid`='{$torzs['recid']}'") ) {
//                $adatbazis_kapcsolat->i('torzsadat_napi_munkaido',  array('torzsadat_recid', 'datum',       'ora', 'insert_dt'  ),
//                                                                    array($torzs['recid'],   date('Y-m-d'), '8',   'NOW()'      )
//                                        );
//echo $adatbazis_kapcsolat->gp() . '<br>';
//            }
//        }
//    }
//    exit;
//}

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
// if( isset($_GET['ez_kell_meg_elesre2']) ) {
//    require_once('../weblap.php');
//    $projektek = $adatbazis_kapcsolat->s('recid', 'projektkezeles');
//    foreach( $projektek as $projekt ) {
//        echo $projekt['recid'] . '<br>';
//        projekt_minositesre_varo_dok_szamol($projekt['recid']);
//    }
//    exit;
// }
//if( isset($_GET['teszt']) ) {
//    require_once '../weblap.php';
//    
//    $szamla_tetelek = $adatbazis_kapcsolat->s('*', 'projektszamla_tetel', "`projektszerzodes_tetel_recid`>0");
//    foreach($szamla_tetelek as $szamla_tetel) {
//        $szamla = $adatbazis_kapcsolat->s2('*', 'projektszamla', "`recid`='{$szamla_tetel['projektszamla_recid']}'");
//        $szerzodes_tetel = $adatbazis_kapcsolat->s2('*', 'projektszerzodes_tetel', "`recid`='{$szamla_tetel['projektszerzodes_tetel_recid']}'");
//        if( $szerzodes_tetel['dev_varh_ert'] ) {
//            $szamla_ertek = $szerzodes_tetel['dev_varh_ert']*$szamla['arfolyam'];
//            $adatbazis_kapcsolat->u('projektszerzodes_tetel', array('ertek'), array($szamla_ertek), "`recid`='{$szamla_tetel['projektszerzodes_tetel_recid']}'");
//            $adatbazis_kapcsolat->u('projektszamla_tetel', array('netto_ert'), array($szamla_ertek), "`recid`='{$szamla_tetel['recid']}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//    }
//    exit;
//    
//}
// if( isset($_GET['teszt']) ) {
//    require_once '../weblap.php';
   
//     $szamlak = $adatbazis_kapcsolat -> s('distinct szallito_id', 'energia_szamla', "
//             recid IN ( SELECT DISTINCT energia_szamla_recid FROM `onteam_energia_szamla_tetel` WHERE `jogcim` in ( 'Szolgáltatásra adott előleg', 'Bírságok, költségek', 'Egyéb költségek - Műszak, karbantartó részleg', 'Késedelmi kamat', 'Egyéb energia - Energia' ) )
//     ");

//     foreach ($szamlak as $szamla) {
//         $szallito = $adatbazis_kapcsolat -> s2('nev', 'szallito', "`id`='{$szamla['szallito_id']}'");
//         echo $szamla['szallito_id'].';'.$szallito['nev'].'<br>';
//     }
//    exit;
   
// }

require_once('header.php');

// if( isset($_GET['teszt']) ) {

//     echo date('Y-m-d H:i:s') .'<br>';

//     $dateObj = DateTime::createFromFormat('U.u', microtime(TRUE));
//     $dateObj->setTimeZone(new DateTimeZone('Europe/Budapest'));
//     $teszt = $dateObj->format('Y-m-d H:i:s');
//     echo $teszt;
// }

// PDF csere (pl. mert angol nyelvűre kell cserélni)
if( FALSE && isset($_GET['pdf_csere']) ) {
    $uj_fajl       = 'DH_te_megrendeles_TE2026159.pdf';
    $uj_fajl_back  = 'DH_te_megrendeles_TE2026159_back.pdf';
    $konyvtar = 'eszkozigenyles_megr/26/276/';
    rename('../'.get_utvonal().$konyvtar.$uj_fajl, '../'.get_utvonal().$konyvtar.$uj_fajl_back);
    copy('../'.get_utvonal().$uj_fajl, '../'.get_utvonal().$konyvtar.$uj_fajl);
    unlink('../'.get_utvonal().$uj_fajl);
}

// Státusz léptetés
if( isset($_GET['statusz_leptet']) ) {
    // addStatusz('munkalap_statusz', 632477, MUNKALAP_STAT_MEGR_ELKULD_VAR);

    exit;
}

// if( isset($_GET['teszt']) ) {

//     $szerepkor = MUSZAKI_ADMINISZTRATOR;
//     $andoc_id  = '_5SR0HUEYV';
//     $szamla_db = $adatbazis_kapcsolat->s2('recid szla_szam szallito_id netto erk_xml tetel_db sztornozva_dt utalvanyozva_dt igazolva_dt', 'fenntartasi_szamla', "`andoc_id`='{$andoc_id}'");

//     $loginok = $adatbazis_kapcsolat -> s('teljes_nev usermail', 'login', "`szemelyzet_id`='{$szerepkor}' AND `aktiv`='1' AND `virtual`='0'");
//     echo $adatbazis_kapcsolat -> gp().'<br>';
//     $targy = 'Számla technikai sztornózás';
//     $uzi = 'Tisztelt {NEV}!
// <br>
// <br>Értesítjük, hogy '.a_az($szamla_neve).' '.$szamla_neve.' számla technikailag sztornózásra került.
// <br>
// <br>A számla adatai:
// <br>Számlaszám: '.$szamla_db['szla_szam'].'
// <br>Szállító: '.getErtek('nev', 'szallito', 'id', $szamla_db['szallito_id']).'
// <br>Nettó érték: '.number_format($szamla_db['netto'], 0, '', ' ').' Ft
// <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');

//     foreach ($loginok as $login) {
//         $mailcimek = explode(',', $login['usermail']);
//         foreach ($mailcimek as $mailcim) {
//             $mailcim = trim($mailcim);
//             if( $mailcim ) {

//                 $akt_uzi = str_replace('{NEV}', $login['teljes_nev'], $uzi);

//                 level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], 'bk@webdeb.hu', $targy, $akt_uzi);

//                 echo $mailcim . ' - ' . $login['teljes_nev'] . '<br>';
//                 echo $akt_uzi . '<br><hr>';

//             }
//         }
//     }
//     exit;
// }

// if( isset($_GET['varh_ertek_karbantart']) ) {
//     $szerz_tetelek = $adatbazis_kapcsolat -> s('recid projektszerzodes_recid dev_ertek dev_varh_ert', 'projektszerzodes_tetel', "`dev_ertek`!='0.00'");
//     foreach ($szerz_tetelek as $tetel) {
//         $pr_szerzodes = $adatbazis_kapcsolat -> s2('arfolyam', 'projektszerzodes', "`recid`='{$tetel['projektszerzodes_recid']}'");
//         $ertek      = round($pr_szerzodes['arfolyam']*$tetel['dev_ertek']   );
//         $varh_ert   = round($pr_szerzodes['arfolyam']*$tetel['dev_varh_ert']);
//         $adatbazis_kapcsolat -> u('projektszerzodes_tetel', array('ertek', 'varh_ert'), array($ertek, $varh_ert), "`recid`='{$tetel['recid']}'");
//         echo $adatbazis_kapcsolat -> gp().'<br>';
//     }
// }

require_once('dashboard.php');

require_once('footer.php');