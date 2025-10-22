<?php

if( isset($_GET['teszt']) ) {
    require_once '../weblap.php';
    
    $tablak = ['onteam_afakod',
'onteam_anyagigenyles',
'onteam_anyagigenyles_dok',
'onteam_anyagigenyles_statusz',
'onteam_anyag_szamla',
'onteam_anyag_szamla_atmeneti',
'onteam_anyag_szamla_dok',
'onteam_anyag_szamla_kifizetesek',
'onteam_anyag_szamla_konyveles',
'onteam_anyag_szamla_tetel',
'onteam_anyag_szamla_tetel_atmeneti',
'onteam_anyag_szamla_tetel_kifizetes',
'onteam_bejelentes',
'onteam_bejelentes_arch',
'onteam_bejelentes_csoport',
'onteam_bejelentes_dok',
'onteam_bejelentes_dok_arch',
'onteam_bejelentes_statusz',
'onteam_bejelentes_statusz_arch',
'onteam_bejovo_szamlak',
'onteam_beosztas',
'onteam_beosztas_muszak',
'onteam_beosztas_naptar',
'onteam_beosztas_nevek',
'onteam_beosztas_unnepnapok',
'onteam_bsz_statusz',
'onteam_cash_flow',
'onteam_cikkrendfej',
'onteam_cikkrendfej_statusz',
'onteam_cikkrnd',
'onteam_cikkrnstat',
'onteam_cikktorzs',
'onteam_eloterj_bod',
'onteam_eloterj_bod_dok',
'onteam_eloterj_bod_statusz',
'onteam_eloterj_fb',
'onteam_eloterj_fb_dok',
'onteam_eloterj_fb_statusz',
'onteam_energia',
'onteam_energia_szamla',
'onteam_energia_szamla_atmeneti',
'onteam_energia_szamla_dok',
'onteam_energia_szamla_kifizetesek',
'onteam_energia_szamla_konyveles',
'onteam_energia_szamla_tetel',
'onteam_energia_szamla_tetel_atmeneti',
'onteam_energia_szamla_tetel_kifizetes',
'onteam_energia_terv',
'onteam_energia_terv_dok',
'onteam_energia_terv_ear',
'onteam_energia_terv_statusz',
'onteam_erteklista',
'onteam_erteklista_csoport',
'onteam_eseti_fenntartas',
'onteam_eseti_fenntartas_dok',
'onteam_eszkozigenyles',
'onteam_eszkozigenyles_dok',
'onteam_eszkozigenyles_fiz_mod',
'onteam_eszkozigenyles_megr',
'onteam_eszkozigenyles_megr_dok',
'onteam_eszkozigenyles_megr_statusz',
'onteam_eszkozigenyles_statusz',
'onteam_eszkozigenyles_tipus',
'onteam_eszkozkezeles',
'onteam_eszkozkezeles_dok',
'onteam_eszkozkezeles_eng_dok',
'onteam_felmeresi_terv',
'onteam_fenntartasi_szamla',
'onteam_fenntartasi_szamla_atmeneti',
'onteam_fenntartasi_szamla_dok',
'onteam_fenntartasi_szamla_kifizetesek',
'onteam_fenntartasi_szamla_konyveles',
'onteam_fenntartasi_szamla_tetel',
'onteam_fenntartasi_szamla_tetel_atmeneti',
'onteam_fenntartasi_szamla_tetel_kifizetes',
'onteam_fenntartasi_szerzodes',
'onteam_fenntartasi_szerzodes_atmeneti',
'onteam_fenntartasi_szerzodes_dok',
'onteam_fenntartasi_szerzodes_tetel',
'onteam_fenntartasi_terv',
'onteam_fenntartasi_terv_bwh',
'onteam_fenntartasi_terv_dok',
'onteam_fenntartasi_terv_energia',
'onteam_fenntartasi_terv_eseti_karban',
'onteam_fenntartasi_terv_hibaelharitas',
'onteam_fenntartasi_terv_miniprojekt',
'onteam_fenntartasi_terv_muszaki_anyag',
'onteam_fenntartasi_terv_rendsz_karban',
'onteam_fenntartasi_terv_statusz',
'onteam_iktszam_seq',
'onteam_jog',
'onteam_jogcim',
'onteam_ktghely',
'onteam_ktgnem',
'onteam_log',
'onteam_login',
'onteam_login_debug',
'onteam_login_tarsreszleg',
'onteam_megallapodas',
'onteam_megallapodas_atmeneti',
'onteam_megallapodas_dok',
'onteam_megallapodas_szamla',
'onteam_megallapodas_szamla_atmeneti',
'onteam_megallapodas_szamla_dok',
'onteam_megallapodas_szamla_konyveles',
'onteam_megallapodas_szamla_tetel',
'onteam_megallapodas_szamla_tetel_atmeneti',
'onteam_megallapodas_szamla_tetel_kifizetes',
'onteam_megallapodas_tetel',
'onteam_megjegyzes',
'onteam_megjegyzes_arch',
'onteam_mnb',
'onteam_mozgas',
'onteam_munkalap',
'onteam_munkalap_14var',
'onteam_munkalap_anyag',
'onteam_munkalap_anyag_arch',
'onteam_munkalap_arch',
'onteam_munkalap_csoport',
'onteam_munkalap_dok',
'onteam_munkalap_dok_arch',
'onteam_munkalap_email',
'onteam_munkalap_email_arch',
'onteam_munkalap_email_dok',
'onteam_munkalap_email_dok_arch',
'onteam_munkalap_feladat',
'onteam_munkalap_feladat_arch',
'onteam_munkalap_kapcs',
'onteam_munkalap_kapcs_arch',
'onteam_munkalap_megrendeles',
'onteam_munkalap_megr_tetel',
'onteam_munkalap_munkaido',
'onteam_munkalap_munkaido_arch',
'onteam_munkalap_statusz',
'onteam_munkalap_statusz_arch',
'onteam_munkalap_tetel',
'onteam_munkalap_tetel_arch',
'onteam_obj',
'onteam_projektigenyles',
'onteam_projektigenyles_arch',
'onteam_projektigenyles_csoport',
'onteam_projektigenyles_dok',
'onteam_projektigenyles_dok_arch',
'onteam_projektigenyles_eloterj',
'onteam_projektigenyles_eloterj_arch',
'onteam_projektigenyles_kapcs',
'onteam_projektigenyles_kapcs_arch',
'onteam_projektigenyles_megter',
'onteam_projektigenyles_megter_arch',
'onteam_projektigenyles_statusz',
'onteam_projektigenyles_statusz_arch',
'onteam_projektigenyles_velemeny',
'onteam_projektigenyles_velemeny_arch',
'onteam_projektkezeles',
'onteam_projektkezeles_cashflow',
'onteam_projektkezeles_cashflow_nev',
'onteam_projektkezeles_dok',
'onteam_projektkezeles_email',
'onteam_projektkezeles_email_dok',
'onteam_projektkezeles_statusz',
'onteam_projektkezeles_team',
'onteam_projektszamla',
'onteam_projektszamla_atmeneti',
'onteam_projektszamla_dok',
'onteam_projektszamla_kifizetesek',
'onteam_projektszamla_konyveles',
'onteam_projektszamla_tetel',
'onteam_projektszamla_tetel_atmeneti',
'onteam_projektszamla_tetel_kifizetes',
'onteam_projektszerzodes',
'onteam_projektszerzodes_atmeneti',
'onteam_projektszerzodes_dok',
'onteam_projektszerzodes_jotallas',
'onteam_projektszerzodes_m3',
'onteam_projektszerzodes_masolat',
'onteam_projektszerzodes_masolat2',
'onteam_projektszerzodes_regi_masolat',
'onteam_projektszerzodes_tetel',
'onteam_projektszerzodes_tetel_masolat',
'onteam_projektszerzodes_tetel_masolat2',
'onteam_projektszerzodes_tetel_regi_masolat',
'onteam_raktar',
'onteam_rendszer',
'onteam_rendszeres_karbantartas',
'onteam_rendszeres_karbantartas_dok',
'onteam_szabadsagigeny',
'onteam_szakterulet',
'onteam_szakter_kapcs',
'onteam_szallito',
'onteam_szallito_beszerzes',
'onteam_szallito_beszerzes_dok',
'onteam_szallito_dok',
'onteam_szamla_atmeneti',
'onteam_szerepkor',
'onteam_telephely',
'onteam_telephely_kapcs',
'onteam_tervezes',
'onteam_teszteles',
'onteam_teszteles_dok',
'onteam_te_reszleg',
'onteam_torzsadat',
'onteam_torzsadat_napi_munkaido',
'onteam_utemezett_feladatok',
'onteam_utemezett_feladatok_dok',
'onteam_utemezo_log',
'onteam_uzemeltetesi_naplo',
'onteam_vendegejszakak'];

    foreach ($tablak as $tabla) {
        echo "ALTER TABLE `{$tabla}` CHANGE `insert_dt` `insert_dt` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';<br> 
              ALTER TABLE `{$tabla}` CHANGE `update_dt` `update_dt` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP; <br>
              ALTER TABLE `{$tabla}` CHANGE `insert_usr` `insert_usr` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; <br>
              ALTER TABLE `{$tabla}` CHANGE `update_usr` `update_usr` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; <br>
              
              ".'<br>';
    }
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