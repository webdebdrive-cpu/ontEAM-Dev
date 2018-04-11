<?php
/*        ADMINISZTRÁCIÓS FELÜLET                                                                                                          *\

  Admin mappa elérési útvonala a szerveren (jelsszóhoz): $_SERVER['DOCUMENT_ROOT']

  Telepítés: A teljes admin mappát át kell másolni (class, css, js mappákat is használja, de ezek már elvileg jelen vannak).
	 admin/index.php-t megnyitva telepítsd az admint.

  Súgó és dokumentáció: index.php?sugo

\*                                                                                                                                         */

if(isset($_GET['info'])){
    phpinfo();
    exit;
}


require_once('../weblap.php');

/*
// Státusz idők rendbetétele
if (isset($_GET['stat_ido'])) {
    $tablak = array('anyagigenyles_statusz', 'bejelentes_statusz', 'bejelentes_statusz_arch', 'cikkrendfej_statusz', 'eloterj_bod_statusz', 'eloterj_fb_statusz', 'munkalap_statusz', 'munkalap_statusz_arch', 'projektigenyles_statusz', 'projektigenyles_statusz_arch', 'projektkezeles_statusz');
    foreach($tablak as $tabla){
        $statuszok = $adatbazis_kapcsolat->s('recid statido, gongyolt_statido, ossz_statido', $tabla, "`statido`!=''");
        foreach($statuszok as $stat){
            $adatbazis_kapcsolat->u($tabla,
                                    array('statido_s',                      'gongyolt_statido_s',                       'ossz_statido_s'                     ),
                                    array(statido_to_sec($stat['statido']), statido_to_sec($stat['gongyolt_statido']),  statido_to_sec($stat['ossz_statido'])),
                            "`recid`='{$stat['recid']}'");
        }
    }
    echo 'f';
}*/


/*// Loginról értesítő levelek küldése
if (isset($_GET['login_ertesit'])) {

    $heliasok = $adatbazis_kapcsolat->s('nev', 'telephely_kapcs', "`telephely_id`='2032'");
    $heliasok = adatbazis2tomb($heliasok, 'nev');
    if($heliasok){
        $loginok = $adatbazis_kapcsolat->s('teljes_nev nev usermail', 'login', "`aktiv`>0 AND `virtual`<1 AND `usermail`!='' AND (`szemelyzet_id`='".HIBABEJELENTO."' OR `szemelyzet_id`='".SZALLODA_IGAZGATO."') AND `nev` IN ('".implode('\', \'', $heliasok)."')");
        foreach( $loginok as $login ){
            echo $login['nev'] . '<br>';
$uzi = '
Tisztelt '.$login['teljes_nev'].'!
<br>
<br>Az '.get_nev().' rendszer 2018.04.05 csütörtök reggel 7:00 időpontban indul a Béke szállodában.
<br>
<br>Kérjük, hogy ettől az időponttól hibabejelentéseiket az '.get_nev().' rendszerben tegyék meg.
<br>
<br>Loginneve az új rendszerben: '.$login['nev'].'
<br>
<br>Telefonos segítség: Handari Tamás 70-210-1312
'. fajlbol_olvas('../sablon/admin/mail/alairas.html');

            // var_dump( level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], 'ontEAM rendszer indítása Béke szállodában', $uzi) );
        }
    }
    exit;
}*/


/*
if (isset($_GET['trans'])) {


        $eredmen = mysqli_query($db_kapcs, 'SET AUTOCOMMIT=0');
        $eredmen = mysqli_query($db_kapcs, 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        $eredmen = mysqli_query($db_kapcs, 'START TRANSACTION');
        $eredmen = mysqli_query($db_kapcs, 'SELECT `sorszam`+1 FROM `onteam_iktszam_seq` WHERE `tabla`=\'anyagigenyles\' AND `ev`=\'17\' FOR UPDATE;');
        $eredmen = mysqli_fetch_assoc($eredmen);
        var_dump($eredmen);

    if(isset($_GET['irok'])){
        mysqli_query($db_kapcs, 'UPDATE `onteam_iktszam_seq` SET `sorszam`=`sorszam`+1 WHERE `tabla`=\'anyagigenyles\' AND `ev`=\'17\';');
        $eredmen = mysqli_query($db_kapcs, 'SELECT `sorszam` FROM `onteam_iktszam_seq` WHERE `tabla`=\'anyagigenyles\' AND `ev`=\'17\' FOR UPDATE;');
        $eredmen = mysqli_fetch_assoc($eredmen);
        var_dump($eredmen);
    }
    if (isset($_GET['comm'])) {
        $eredmen = mysqli_query($db_kapcs, 'COMMIT');
    }
}
*/

/*// Ha egy tálbának utólag adsz egy loginnev nevű oszlopnevet
if (isset($_GET['loginnev_gyart'])) {

    // $tabla = 'munkalap';
    // Több táblát is bejárunk
    $tablak = array('anyagigenyles_statusz', 'bejelentes_statusz', 'bejelentes_statusz_arch', 'cikkrendfej_statusz', 'eloterj_bod_statusz', 'eloterj_fb_statusz', 'munkalap_statusz', 'munkalap_statusz_arch', 'projektigenyles_statusz', 'projektigenyles_statusz_arch', 'projektkezeles_statusz');
    foreach($tablak as $tabla){
        
        $sorok = $adatbazis_kapcsolat->s('recid insert_usr', $tabla, "`login`=''");
        foreach($sorok as $sor){

            $loginnev = $adatbazis_kapcsolat->s2('nev', 'login', "`teljes_nev`='{$sor['insert_usr']}'");
            $loginnev = $loginnev['nev'];

            $adatbazis_kapcsolat->u($tabla, array('login'),
                                            array($loginnev),
                                        "`recid`='{$sor['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
        }
        
    }

    exit;
}*/

// Kötött fejléc nevű CSV feldolgozása
if (isset($_GET['loginnev_import'])) {
    
    
    
    exit;
}

// Logginnév átnevezése
if (isset($_GET['loginnev_csere'])) {
    
    $regi_nev = trim($_GET['regi']);
    $uj_nev   = trim($_GET['uj']);
    
    if(!$regi_nev || !$uj_nev){
        echo 'Nem adatad meg a régi vagy új loginnevet!';
        exit;
    }
    if( !$adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$regi_nev}'") ){
        echo ucfirst(a_az($regi_nev)).' '.$regi_nev.' loginnév nem található a rendszerben!';
        exit;
    }
    if($adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$uj_nev}'")){
        echo ucfirst(a_az($uj_nev)).' '.$uj_nev.' loginnév már foglalt!';
        exit;
    }
    
    $tablak_oszlopok = array(
                            array('tabla' => 'anyagigenyles',           'oszlop' => 'loginnev'      ),
                            array('tabla' => 'anyagigenyles_dok',       'oszlop' => 'loginnev'      ),
                            array('tabla' => 'anyagigenyles_statusz',   'oszlop' => 'login'         ),
                            array('tabla' => 'bejelentes',              'oszlop' => 'loginnev'      ),
                            array('tabla' => 'bejelentes_arch',         'oszlop' => 'loginnev'      ),
                            array('tabla' => 'bejelentes_doc',          'oszlop' => 'loginnev'      ),
                            array('tabla' => 'bejelentes_doc_arch',     'oszlop' => 'loginnev'      ),
                            array('tabla' => 'bejelentes_statusz',      'oszlop' => 'login'         ),
                            array('tabla' => 'bejelentes_statusz_arch', 'oszlop' => 'login'         ),
                            array('tabla' => 'beosztas',                'oszlop' => 'loginnev'      ),
                            array('tabla' => 'beosztas_naptar',         'oszlop' => 'loginnev'      ),
                            array('tabla' => 'cash_flow',               'oszlop' => 'loginnev'      ),
                            array('tabla' => 'cikkrendfej_statusz',     'oszlop' => 'login'         ),
                            array('tabla' => 'eloterj_bod_dok',         'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eloterj_bod_statusz',     'oszlop' => 'login'         ),
                            array('tabla' => 'eloterj_fb_dok',          'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eloterj_fb_statusz',      'oszlop' => 'login'         ),
                            array('tabla' => 'eseti_fenntartas',        'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eseti_fenntartas_doc',    'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eszkozkezeles_dok',       'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eszkozkezeles_eng_dok',   'oszlop' => 'loginnev'      ),
                            array('tabla' => 'ktghely',                 'oszlop' => 'loginnev'      ),
                            array('tabla' => 'ktgnem',                  'oszlop' => 'loginnev'      ),
                            array('tabla' => 'login',                   'oszlop' => 'nev'           ),
                            array('tabla' => 'megjegyzes',              'oszlop' => 'loginnev'      ),
                            array('tabla' => 'megjegyzes_arch',         'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap',                'oszlop' => 'mobil_loginnev'),
                            array('tabla' => 'munkalap',                'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap',                'oszlop' => 'jh1_login'     ),
                            array('tabla' => 'munkalap',                'oszlop' => 'jh2_login'     ),
                            array('tabla' => 'munkalap_14var',          'oszlop' => 'stat_insert_login'),
                            array('tabla' => 'munkalap_arch',           'oszlop' => 'mobil_loginnev'),
                            array('tabla' => 'munkalap_arch',           'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap_arch',           'oszlop' => 'jh1_login'     ),
                            array('tabla' => 'munkalap_arch',           'oszlop' => 'jh2_login'     ),
                            array('tabla' => 'munkalap_dok',            'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap_dok_arch',       'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap_statusz',        'oszlop' => 'login'         ),
                            array('tabla' => 'munkalap_statusz_arch',   'oszlop' => 'login'         ),
                            array('tabla' => 'munkalap_tetel',          'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap_tetel_arch',     'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles',         'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_arch',    'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_doc',     'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_doc_arch','oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_eloterj', 'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_eloterj_arch', 'oszlop' => 'loginnev' ),
                            array('tabla' => 'projektigenyles_kapcs',   'oszlop' => 'loginnev'      ),
                            array('tabla' => 'projektigenyles_statusz', 'oszlop' => 'login'         ),
                            array('tabla' => 'rendszeres_karbantartas', 'oszlop' => 'loginnev'      ),
                            array('tabla' => 'rendszeres_karbantartas_doc', 'oszlop' => 'loginnev'  ),
                            array('tabla' => 'telephely_kapcs',         'oszlop' => 'nev'           ),
                            array('tabla' => 'torzsadat',               'oszlop' => 'felh_loginnev' ),
                            array('tabla' => 'utemezett_feladatok',     'oszlop' => 'loginnev'      ),
                            array('tabla' => 'utemezett_feladatok_doc', 'oszlop' => 'loginnev'      ),
        
                        );
    foreach ( $tablak_oszlopok as $to ){
        $adatbazis_kapcsolat->u($to['tabla'], array($to['oszlop']), array($uj_nev), "`{$to['oszlop']}`='{$regi_nev}'");
        echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    $adatbazis_kapcsolat->u('login', array('van_jelszo'), array(0), "`nev`='{$uj_nev}'");
    
    exit;
}

/*
// Anyagigénylés speciálisabb volt
if (isset($_GET['loginnev_gyart_anyagigenyles'])) {

    $tabla = 'anyagigenyles';

    $sorok = $adatbazis_kapcsolat->s('recid loginnev', $tabla);
    foreach($sorok as $sor){
        $teljes_nev = $adatbazis_kapcsolat->s('teljes_nev', 'login', "`nev`='{$sor['loginnev']}'");
        $teljes_nev = $teljes_nev[0]['teljes_nev'];

        $adatbazis_kapcsolat->u($tabla, array('insert_usr'), array($teljes_nev), "`recid`='{$sor['recid']}'");
    }

    exit;
}
*/

/*
// Státusz feltöltés, ha csak az adott bejegyzés státusza volna kitöltve és tröténet tábla még nem
if (isset($_GET['statusz_fetlolt'])) {
    $tabla = 'anyagigenyles';
    $anyagok = $adatbazis_kapcsolat->s('recid stat_id', $tabla);
    foreach($anyagok as $anyag){
        $adatbazis_kapcsolat->i($tabla.'_statusz',  array('stat_id',        $tabla.'_recid'     ),
                                                    array($anyag['stat_id'], $anyag['recid']     )
                                );
    }

    exit;
}
*/

/*
// Felhasználókból karbantartjuk a törzsadatokat
if (isset($_GET['felh2torzs'])) {
    //Felhasználóból személyzet gyártása
    $felhok = $adatbazis_kapcsolat->s('*', 'login');
echo $adatbazis_kapcsolat->gp() . '<br>';
    foreach($felhok as $felh){

        $beosztas = '';
        if($felh['beosztas_id']=='94'){
            $beosztas = '1';
        }
        if($felh['beosztas_id']=='93'){
            $beosztas = '2';
        }
        if($felh['beosztas_id']=='16'){
            $beosztas = '4';
        }
        if($felh['beosztas_id']=='111'){
            $beosztas = '7';
        }

        if($beosztas){
            $mezok   = array('nev',               'szemelyzet_id', 'szul_datum', 'szakma', 'munka_kezd', 'komp', 'aktiv'        );
            $ertekek = array($felh['teljes_nev'], $beosztas,       '',           '',       '',           '',     $felh['aktiv'] );
            if(!$adatbazis_kapcsolat->s('id', 'torzsadat', "`felh_loginnev`='{$felh['nev']}'")){
                array_push($mezok, 'felh_loginnev');
                array_push($ertekek, $felh['nev']);
                $adatbazis_kapcsolat->i('torzsadat', $mezok, $ertekek);
            }else{
                $adatbazis_kapcsolat->u('torzsadat', $mezok, $ertekek, "`felh_loginnev`='{$felh['nev']}'");
            }
echo $adatbazis_kapcsolat->gp() . '<br>';
        }
    }
}
*/

require_once('header.php');
require_once('footer.php');
?>
