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


// Loginról értesítő levelek küldése
if (false && isset($_GET['login_ertesit'])) {
    
    $szalloda_nev   = 'SÁRVÁR TH'; // SÁRVÁR TH
    $galyasz_bence  = false;
    $datum          = date('Y.m.d', strtotime(' +1 day'));
    $napok          = array('vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat');
    $nap_neve       = date('w', strtotime( str_replace('.', '-', $datum) ));
    $nap_neve       = $napok[$nap_neve];
    
    $szalloda_id = $adatbazis_kapcsolat->s2('id', 'telephely', "`nev`='{$szalloda_nev}'");
    $szalloda_id = $szalloda_id['id'];
    
    $heliasok = $adatbazis_kapcsolat->s('nev', 'telephely_kapcs', "`telephely_id`='{$szalloda_id}'");
    $heliasok = adatbazis2tomb($heliasok, 'nev');
    if($heliasok){
        $loginok = $adatbazis_kapcsolat->s('teljes_nev nev usermail', 'login', "`aktiv`>0 AND `virtual`<1 AND `usermail`!='' AND (`szemelyzet_id`='".HIBABEJELENTO."' OR `szemelyzet_id`='".TELEPITETT_SZEMELYZET."' OR `szemelyzet_id`='".MUSZAKI_VEZETO."' OR `szemelyzet_id`='".SZALLODA_IGAZGATO."') AND `nev` IN ('".implode('\', \'', $heliasok)."')");
        foreach( $loginok as $login ){
            echo $login['nev'] . '<br>';
$uzi = '
Tisztelt '.$login['teljes_nev'].'!
<br>
<br>Az '.get_nev().' rendszer '.$datum.' '.$nap_neve.' reggel 7:00 időpontban indul a '.$szalloda_nev.' szállodában.
<br>
<br>Kérjük, hogy ettől az időponttól hibabejelentéseiket az '.get_nev().' rendszerben tegyék meg.
<br>
<br>Loginneve az új rendszerben: '.$login['nev'].'
<br>

';

// A levél tartalma eltérő, ha $galyasz_bence igaz vagy hamis
if($galyasz_bence){
    $uzi .= '
<br>Az éles indítás napján Gályász Bence (tel: 30-960-9677) '.get_nev().' oktató a szállodában tartózkodik.
<br>
<br>Kérdés vagy probléma esetén forduljanak hozzá bizalommal.
<br>
<br>További telefonos segítség: Handari Tamás 70-210-1312
        ';
}else{
    $uzi .= '
<br>Telefonos segítség: Handari Tamás 70-210-1312
';
}

$uzi .= fajlbol_olvas('../sablon/admin/mail/alairas.html');



            var_dump( level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) );
            //var_dump( level_kuldes(get_nev(), get_mail(), 'Handrai Tamás', 'handari@szinvatrend.hu', 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) ); exit;
            //var_dump( level_kuldes(get_nev(), get_mail(), 'BK', 'bk@webdeb.hu', 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) ); exit;
        }
    }
    exit;
}

if (false && isset($_GET['anyagigenyles_tabla_oszlop_bovit'])) {
    
    $loginok = $adatbazis_kapcsolat->s('recid tabla_sorrend', 'login');
    foreach( $loginok as $login ){
        $login['tabla_sorrend'] = str_replace('{&quot;id&quot;:&quot;anyag_tabla_jovah_menny', '{&quot;id&quot;:&quot;anyag_tabla_jovah_ertek&quot;,&quot;width&quot;:118},{&quot;id&quot;:&quot;anyag_tabla_jovah_menny', $login['tabla_sorrend']);
        $adatbazis_kapcsolat->u('login', array('tabla_sorrend'), array($login['tabla_sorrend']), "`recid`='{$login['recid']}'");
    }
    
    exit;
}

// Kötött fejléc nevű CSV feldolgozása (login_import.csv legyen a fájl neve)
if (false && isset($_GET['loginnev_import']) ) {
    
    header('Content-Type: text/html; charset='. get_web_charset());
    
    $fajlnev = 'csv/login_import.csv';
    setlocale(LC_ALL,'hu_HU');

    $k = 0;
    $loginnevek = array();
    if (!isset($_GET['mehet'])) {
        $log_csv .= "Beolvasás eredménye, még nincs beimportálva!\r\n";
    }
    $log_csv .= 'Esemény;Státusz;PP2 loginnév;Teljes név;ontEAM név;Részleg;Beosztás;E-mail;Telefon;'."\r\n";
    if (($csv_fajl = fopen($fajlnev, 'r')) !== FALSE) {
        while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {

            $akt_tomb = array();
            // Első oszlop a fejlécek
            if($k == 0) {

                foreach($sor as $key => $value) {
                    $_SESSION['sor_fejlec'][$key] = $value;
                }

            // Adatok feldolgozása
            } else {
                
                foreach($sor as $key => $value) {

                    $akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
                }

                foreach($akt_tomb as $key => $value) {
                    $akt_tomb[$key] = utf8($akt_tomb[$key]);
                }

                $szemelyzet_id = '';
                foreach($akt_tomb as $key => $value) {

                    switch( utf8($key) ) {

                        case 'Státusz':
                            $statusz = mb_strtolower($akt_tomb[$key], get_web_charset());
                            if( $statusz == 'szállodai bejelentő' ){
                                $szemelyzet_id = HIBABEJELENTO;
                            }
                            if( $statusz == 'telepített karbantartó' || $statusz == 'telepített karbentartó' ){
                                $szemelyzet_id = TELEPITETT_SZEMELYZET;
                            }
                            if( $statusz == 'gazdasági vezető' ){
                                $szemelyzet_id = HIBABEJELENTO;
                            }
                            if( $statusz == 'szálloda igazgató' || $statusz == 'szálloda igazgató 1' || $statusz == 'szálloda igazgató 2' ){
                                $szemelyzet_id = SZALLODA_IGAZGATO;
                            }
                            if( $statusz == 'létesítmény főmérnök' || $statusz == 'létesítmény felelős' || $statusz == 'műszaki vezető' ){
                                $szemelyzet_id = MUSZAKI_VEZETO;
                            }
                        break;


                        case 'PPM 2 Login név':
                            $regi_nev = trim($akt_tomb[$key]);
                        break;


                        case 'Név':
                            $teljes_nev = trim($akt_tomb[$key]);
                        break;


                        case 'ontEAM login név':

                            $login_nev = trim($akt_tomb[$key]);
                        break;


                        case 'Részleg':
                            $reszleg = trim($akt_tomb[$key]);
                        break;


                        case 'Beosztás':
                            $beosztas = trim($akt_tomb[$key]);
                            if( $beosztas == 'Karbantartó' ){
                                $beosztas = 'Telepített karbantartó';
                            }
                            $beosztas = trim($akt_tomb[$key]);
                            if( $beosztas == 'Létesítmény főmérnök' || $beosztas == 'Létesítmény felelős' ){
                                $beosztas = 'Műszaki vezető';
                            }
                        break;


                        case 'Email':
                            $email = trim($akt_tomb[$key]);
                        break;


                        case 'Telefon':
                            $telefon = trim($akt_tomb[$key]);
                        break;


                        case 'Értesítés':
                            $ertesites = trim($akt_tomb[$key]);
                        break;


                        case 'Szálloda':
                            $szalloda = trim($akt_tomb[$key]);
                        break;
                    }
                }

                if($szemelyzet_id){
                    //if($login_nev){
                        
                        // Mégis megengedett a hiányzó loginnév, ekkor a teljesn név első 8 karkateréből gyártunk
                        if( !$login_nev ){
                            $hossz = 8;
                            $login_nev = str_replace('-', '', fajlnev_keszit($teljes_nev, 'ékezet marad'));
                            $login_nev = mb_substr($login_nev, 0, $hossz);
                            $sorszam   = '';
                            while(mb_strlen($login_nev.$sorszam)<$hossz){
                                $sorszam   .= '1';
                            }
                            
                            $elozo_login_nev = $login_nev.$sorszam;
                            while($adatbazis_kapcsolat->s('recid', 'login', "`nev`='".($login_nev.$sorszam)."'") || in_array($login_nev.$sorszam, $loginnevek)){
                                $hossz++;
                                $login_nev = str_replace('-', '', fajlnev_keszit($teljes_nev, 'ékezet marad'));
                                $login_nev = mb_substr($login_nev.$sorszam, 0, $hossz);
                                if( $elozo_login_nev == $login_nev ){
                                    $sorszam++;
                                }
                                $elozo_login_nev = $login_nev.$sorszam;
                            }
                            $login_nev .= $sorszam;
                            $log_csv .= 'Nem volt megadva loginnév, így a nevéből generáva: '.$login_nev.' ';
                        }else{

                            if (!isset($_GET['mehet'])) {
                                $log_csv .= 'ELŐRE MEGADOTT LOGINNÉV: '.$login_nev.' ';
                            }
                        }

                        // Jegyezzük meg, hogy milyen loginnevek voltak
                        array_push($loginnevek, $login_nev);
                    
                        // Ha foglalt a loginnév, de inaktív, akkor töröljük, egyébként hibaüzi
                        $mehet = false;
                        if(!$adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$login_nev}'")){
                            $mehet = true;
                        }elseif( $adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$login_nev}' AND `aktiv`=0") ){
                            $adatbazis_kapcsolat->d('login', "`nev`='{$login_nev}'");
                            $log_csv .= $login_nev.' TÖRÖLVE'."\r\n";
                            $mehet = true;
                        }
                        if($mehet){
                            $reszleg_sql = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_RESZLEG."' AND `info`='".htmlspecialchars($reszleg)."'");
                            if(!$reszleg_sql){
                                $reszleg = 'NEM ISMERT RÉSZLEG: '.$reszleg;
                            }
                            // if(!$reszleg || $reszleg_sql){
                                
                                $beosztas_sql = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_BEOSZTAS."' AND `info`='".htmlspecialchars($beosztas)."'");
                                if(!$beosztas_sql){
                                    $beosztas = 'NEM ISMERT BEOSZTÁS: '.$beosztas;
                                }
                                // if(!$beosztas || $beosztas_sql){
                                
                                    $mezok   = array('nev',      'szemelyzet_id', 'teljes_nev', 'usertel', 'usermail', 'aktiv', 'virtual', 'van_jelszo'   );
                                    $ertekek = array($login_nev, $szemelyzet_id,  $teljes_nev,  $telefon,  $email,     '1',     '0',       '0'            );
                                    
                                    if($reszleg_sql['id']){
                                        array_push($mezok,   'reszleg_id');
                                        array_push($ertekek, $reszleg_sql['id']);
                                    }
                                    if($reszleg_sql['id']){
                                        array_push($mezok,   'beosztas_id');
                                        array_push($ertekek, $beosztas_sql['id']);
                                    }
                                    
                                    if($ertesites){
                                        array_push($mezok,   'flags_ert');
                                        array_push($ertekek, '11100000000001');
                                    }
                                    
                                    $szerepkor = $adatbazis_kapcsolat->s2('flags', 'szerepkor', "`recid`='{$szemelyzet_id}'", '', '1');
                                    if( $szerepkor['flags'] ) {
                                        array_push($mezok,     'flags'            );
                                        array_push($ertekek,   $szerepkor['flags']);
                                    }
                                    
                                    array_push($mezok,     'insert_dt', 'insert_usr',         'update_usr'        );
                                    array_push($ertekek,   'NOW()',     get_nev().' import',  get_nev().' import' );
                                    
                                    $adatok  = csv_ellenorzes_letolt($statusz).';'.csv_ellenorzes_letolt($regi_nev).';'.csv_ellenorzes_letolt($teljes_nev).';'.csv_ellenorzes_letolt($login_nev). ';'.csv_ellenorzes_letolt($reszleg).';'.csv_ellenorzes_letolt($beosztas).';'.csv_ellenorzes_letolt($email).';'.csv_ellenorzes_letolt($telefon)."\r\n";
                                    // Már létező logint átnevezünk
                                    if($regi_nev){
                                        if($adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$regi_nev}'")){
                                            $adatbazis_kapcsolat->u('login', $mezok, $ertekek, "`nev`='{$regi_nev}'");
if (isset($_GET['teszt'])) {
    echo $adatbazis_kapcsolat->gp() . '<br>';
}
                                            $log_csv .= 'Felhasználó FRISSÍTVE;'.$adatok;
                                        }else{
                                            $adatbazis_kapcsolat->i('login', $mezok, $ertekek);
if (isset($_GET['teszt'])) {
echo $adatbazis_kapcsolat->gp() . '<br>';
}
                                            $log_csv .= 'Nem található a régi PP2-es loginnév, ezért új felhasználó LÉTREHOZVA;'.$adatok;
                                        }
                                        
                                    // Ha nincs régi loginnév, csak új
                                    }else{
                                        
                                        if( isset($_GET['mehet']) ){
                                            $adatbazis_kapcsolat->i('login', $mezok, $ertekek);
if (isset($_GET['teszt'])) {
echo $adatbazis_kapcsolat->gp() . '<br>';
}
                                            $log_csv .= 'Felhasználó LÉTREHOZVA;'.$adatok;
                                        }else{
                                            $log_csv .= ';'.$adatok;
                                        }
                                    }
                                    
                                    // Szállodához rendelés
                                    $szallodak = explode(',', $szalloda);
                                    foreach( $szallodak as $szall ){
                                        $szall = mb_strtoupper( trim($szall) ).' TH';
                                        $szall_id = $adatbazis_kapcsolat->s2('id', 'telephely', "`nev`='{$szall}'");
                                        if(!$szall_id){
                                            $log_csv .= 'Nem ismert szálloda (ez még az előző sorhoz tartozik): '.$szall;
                                        }
                                        
                                        if( isset($_GET['mehet']) ){
                                            if(!$adatbazis_kapcsolat->s('recid', 'telephely_kapcs', "`nev`='{$login_nev}' AND `telephely_id`='{$szall_id['id']}'")){
                                                $adatbazis_kapcsolat->i('telephely_kapcs',  array('nev',        'telephely_id',  'insert_dt', 'insert_usr', 'update_usr'),
                                                                                            array($login_nev,   $szall_id['id'], 'NOW()',     get_nev(),    get_nev()   )
                                                                        );
if (isset($_GET['teszt'])) {
echo $adatbazis_kapcsolat->gp() . '<br>';
}

                                            }
                                        }
                                    }

                                    
//                                }else{
//                                    echo '<b style="color: #f00">Nem ismert beosztás: '. $beosztas.' ('.$regi_nev.' - '.$teljes_nev.' - '.$login_nev. ')</b><br>';
//                                }
                                
//                            }else{
//                                echo '<b style="color: #f00">Nem ismert részleg: '. $reszleg.' ('.$regi_nev.' - '.$teljes_nev.' - '.$login_nev. ')</b><br>';
//                            }
                            
                        }else{
                            $log_csv .= 'FOGLALT LOGINNÉV!;'.csv_ellenorzes_letolt($statusz).';'.csv_ellenorzes_letolt($regi_nev).';'.csv_ellenorzes_letolt($teljes_nev).';'.csv_ellenorzes_letolt($login_nev)."\r\n";
                        }
//                    }else{
//                        $log_csv .= 'HIÁNYZÓ LOGINNÉV!;'.csv_ellenorzes_letolt($statusz).';'.csv_ellenorzes_letolt($regi_nev).';'.csv_ellenorzes_letolt($teljes_nev).';'.csv_ellenorzes_letolt($login_nev)."\r\n";
//                    }
                }else{
                    // Ha az egész sor üres, ne írjunk bele a logba, hogy nem sikerült felismerni
                    if($teljes_nev || $login_nev){
                        $log_csv .= 'NEM ISMERT SZEMÉLYZETTÍPUS!;'.csv_ellenorzes_letolt($statusz).';'.csv_ellenorzes_letolt($regi_nev).';'.csv_ellenorzes_letolt($teljes_nev).';'.csv_ellenorzes_letolt($login_nev)."\r\n";
                    }
                }
                // Adott sor feldolgozása, SQL előállítása
            }
            $k++;
        }
        fclose($csv_fajl);
    }else{
        echo 'Fájl nem található';
    }

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=felhasznalo_import_log.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo iconv2(get_web_charset(), 'Windows-1250', $log_csv);
    
    exit;
}

// Logginnév átnevezése
if (false && isset($_GET['loginnev_csere'])) {
    
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

// Objektumok importja
if (false && isset($_GET['objektum_import'])) {
    
    $telephely_id = 1060;
    
    $adatbazis_kapcsolat->d('obj', "`telephely_id`='$telephely_id'");
    
    header('Content-Type: text/html; charset='. get_web_charset());
    
    $fajlnev = 'csv/obj_import.csv';
    setlocale(LC_ALL,'hu_HU');

    $k = 0;
    if (($csv_fajl = fopen($fajlnev, 'r')) !== FALSE) {
        while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {

            $akt_tomb = array();
            // Első oszlop a fejlécek
            if($k == 0) {

                foreach($sor as $key => $value) {
                    $_SESSION['sor_fejlec'][] = $value;
                }

            // Adatok feldolgozása
            } else {
                
                foreach($sor as $key => $value) {
                    $akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
                }

                foreach($akt_tomb as $key => $value) {
                    $akt_tomb[$key] = utf8($akt_tomb[$key]);
                }

                $szemelyzet_id = '';
                foreach($akt_tomb as $key => $value) {
                    
                    switch( utf8($key) ) {

                        case 'Név':
                            $nev = $akt_tomb[$key];
                        break;


                        case 'Szint':
                            $szint = trim($akt_tomb[$key]);
                        break;

                    }
                }
                
                // Ha szint, akkor a szülője 0, és megjegyezzük az insert id-ját
                if($szint){
                    $szint_id = $adatbazis_kapcsolat->i('obj',  array('telephely_id', 'szulo', 'nev', 'insert_dt', 'insert_usr',        'update_usr'           ),
                                                                array($telephely_id,  0,       $nev,  'NOW()',     get_nev().' import',  get_nev().' import'   )
                                                        );
                }else{
                    $adatbazis_kapcsolat->i('obj',  array('telephely_id', 'szulo',      'nev', 'insert_dt', 'insert_usr',         'update_usr'          ),
                                                    array($telephely_id,  $szint_id,    $nev,  'NOW()',     get_nev().' import',  get_nev().' import'   )
                                            );
                }
echo $adatbazis_kapcsolat->gp() . '<br>';

            }
            $k++;
        }
        fclose($csv_fajl);
    }else{
        echo 'Fájl nem található';
    }
    
    exit;
}

// Objektumok importja PPM-ből
if (false && isset($_GET['objektum_import_ppm'])) {
    
    $telephely_id = 1033;
    
    $adatbazis_kapcsolat->d('obj', "`telephely_id`='$telephely_id'");
    
    header('Content-Type: text/html; charset='. get_web_charset());
    
    $fajlnev = 'csv/obj_import_ppm.csv';
    setlocale(LC_ALL,'hu_HU');

    $k = 0;
    if (($csv_fajl = fopen($fajlnev, 'r')) !== FALSE) {
        while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {

            $akt_tomb = array();
            // Első oszlop a fejlécek
            if($k == 0) {

                foreach($sor as $key => $value) {
                    $_SESSION['sor_fejlec'][] = $value;
                }

            // Adatok feldolgozása
            } else {
                

                foreach($sor as $key => $value) {
                    $akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
                }

                foreach($akt_tomb as $key => $value) {
                    $akt_tomb[$key] = utf8($akt_tomb[$key]);
                }

                $szemelyzet_id = '';
                foreach($akt_tomb as $key => $value) {

                    switch( utf8($key) ) {

                        case 'Szint':
                            $szint = trim($akt_tomb[$key]);
                        break;

                        case 'Név':
                            $nev = $akt_tomb[$key];
                        break;

                    }

                }

                switch( strlen($szint) ){

                    // Emelet
                    case 2:
                        $szint_id = $adatbazis_kapcsolat->i('obj',  array('telephely_id', 'szulo', 'nev', 'sor', 'insert_dt', 'insert_usr',        'update_usr'           ),
                                                                    array($telephely_id,  0,       $nev,  $k,    'NOW()',     get_nev().' import',  get_nev().' import'   )
                                                            );
                    break;
                
                    // Helyiség
                    case 6:
                        $helyiseg = $adatbazis_kapcsolat->i('obj',  array('telephely_id', 'szulo',      'nev', 'sor', 'insert_dt', 'insert_usr',         'update_usr'          ),
                                                                    array($telephely_id,  $szint_id,    $nev,  $k,    'NOW()',     get_nev().' import',  get_nev().' import'   )
                                                            );
                    break;
                
                    // Gép
                    case 10:
                        $adatbazis_kapcsolat->i('eszkozkezeles',    array('telephely_id', 'obj_id',     'nev', 'insert_dt', 'insert_usr',         'update_usr'          ),
                                                                    array($telephely_id,  $helyiseg,    $nev,  'NOW()',     get_nev().' import',  get_nev().' import'   )
                                                );
                    break;
                }
                
echo $adatbazis_kapcsolat->gp() . '<br>';

            }
            $k++;
        }
        fclose($csv_fajl);
    }else{
        echo 'Fájl nem található';
    }
    
    exit;
}

// Beszállítók importja
if (false && isset($_GET['szallito_import'])) {
    
    header('Content-Type: text/html; charset='. get_web_charset());
    
    $fajlnev = 'csv/szallito_import.csv';
    setlocale(LC_ALL,'hu_HU');

    $k = 0;
    if (($csv_fajl = fopen($fajlnev, 'r')) !== FALSE) {
        while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {

            $akt_tomb = array();
            // Első oszlop a fejlécek
            if($k == 0) {

                foreach($sor as $key => $value) {
                    $_SESSION['sor_fejlec'][] = $value;
                }

            // Adatok feldolgozása
            } else {
                

                foreach($sor as $key => $value) {
                    $akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
                }

                foreach($akt_tomb as $key => $value) {
                    $akt_tomb[$key] = utf8($akt_tomb[$key]);
                }

                $szemelyzet_id = '';
                foreach($akt_tomb as $key => $value) {

                    switch( utf8($key) ) {

                        case 'Oracle szállító kód':
                            $oracle_kod = trim($akt_tomb[$key]);
                        break;

                        case 'Szállító':
                            $nev = $akt_tomb[$key];
                        break;

                        case 'Cím':
                            $cim = $akt_tomb[$key];
                        break;

                        case 'Adószám':
                            $adoszam = $akt_tomb[$key];
                        break;

                        case 'Bankszámlaszám':
                            $bank = $akt_tomb[$key];
                        break;

                        case 'Tevékenység':
                            $tev = $akt_tomb[$key];
                        break;

                        case 'Kontakt':
                            $kontakt = $akt_tomb[$key];
                        break;

                        case 'E-mail':
                            $email = $akt_tomb[$key];
                        break;

                        case 'Telefon':
                            $telefon = $akt_tomb[$key];
                        break;

                    }

                }

                $adatbazis_kapcsolat->i('szallito', array('nev', 'insert_dt', 'insert_usr',         'update_usr'       ),
                                                    array($nev,  'NOW()',     get_nev().' import',  get_nev().' import')
                                        );
echo $adatbazis_kapcsolat->gp() . '<br>';

            }
            $k++;
        }
        fclose($csv_fajl);
    }else{
        echo 'Fájl nem található';
    }
    
    exit;
}


// 
/*if (false && isset($_GET['login_szall_kapcs'])) {
    
    $log = 'Login név;Teljes név;Login létrejötte;Telephely ID;Telephely név;Kapcsolat létrejötte;'."\r\n";
    $loginok = $adatbazis_kapcsolat->s('nev teljes_nev insert_dt', 'login');
    foreach( $loginok as $login ){
        
        $kapcsok = $adatbazis_kapcsolat->s('recid insert_dt telephely_id', 'telephely_kapcs', "`nev`='{$login['nev']}'");
        if( count($kapcsok)>1 ){
            
            foreach ( $kapcsok as $kapcs ){
                if($kapcs['insert_dt']<'2018-02-15 00:00:00'){
                    $megj = '';
                    $akt_login = $adatbazis_kapcsolat->s2('virtual aktiv', 'login', "`nev`='{$login['nev']}'");
                    if(!$akt_login){
                        $megj .= 'Ilyen loginnév nem is létezik!';
                    }else{
                        if($akt_login['virtual']){
                            $megj .= 'Virtuális felhasználó!';
                        }
                        if(!$akt_login['aktiv']){
                            $megj .= 'Nem aktív felhasználó!';
                        }
                    }
                    $telephely = $adatbazis_kapcsolat->s2('nev', 'telephely', "`id`='{$kapcs['telephely_id']}'");
                    $log .= $login['nev'].';'.$login['teljes_nev'].';'.$login['insert_dt'].';'.$kapcs['telephely_id'].';'.$telephely['nev'].';'.$kapcs['insert_dt'].';'.$megj."\r\n";
                }
            }
        }
        
    }
    
    echo $log . '<br>';
    
    exit;
}*/


// Munkalap kapcsolat javítás
//if (isset($_GET['munkalap_kapcs_javit'])) {
//    $kapcsolatok = $adatbazis_kapcsolat->s('*', 'munkalap_kapcs');
//    foreach ($kapcsolatok as $kapcs){
//        $bejelentes = $adatbazis_kapcsolat->s2('surgos', 'bejelentes', "`recid`='{$kapcs['bejelentes_id']}'");
//        $munkalap   = $adatbazis_kapcsolat->s2('surgos', 'munkalap',   "`recid`='{$kapcs['munkalap_id']}'");
//        if($bejelentes['surgos'] != $munkalap['surgos']){
//            $adatbazis_kapcsolat->u('munkalap', array('surgos'), array($bejelentes['surgos']), "`recid`='{$kapcs['munkalap_id']}'");
//            echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//    }
//    exit;
//}

/*
// Bejelentések törlése, hogy a gyakorló felület ne legyen tele adatokkal
if (isset($_GET['bejelentes_del'])) {
    
    // Lzártak törlése
    $lezartak = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id`='".BEJELENTES_STAT_LEZART."'");
    foreach($lezartak as $lezart){
        bejelentes_torol($lezart['recid']);
    }
    
    // Nyitott / Folyamatban státuszúakat is töröljük
    $nyitott_folyamatban = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`stat_id` IN ('01', '02')", 'recid desc', '5, 99999');
    foreach($nyitott_folyamatban as $nyf){
        bejelentes_torol($nyf['recid']);
    }
    
}
function bejelentes_torol($recid, $tabla = 'bejelentes'){
    
    global $adatbazis_kapcsolat;
    
    $adatbazis_kapcsolat->d($tabla, "`recid`='{$recid}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    // Kapcsolódó táblák törlése
    $adatbazis_kapcsolat->d('megjegyzes', "`tabla`='{$tabla}' AND `id`='{$recid}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    $adatbazis_kapcsolat->d($tabla.'_statusz', "`{$tabla}_recid`='{$recid}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    
    $fajlok = $adatbazis_kapcsolat->s('*', $tabla.'_dok ', "`{$tabla}_id`='{$recid}'");
    foreach($fajlok as $fajl){
        kep_torol('../'.get_utvonal().$tabla.'/'.$fajl['utvonal']);
        $adatbazis_kapcsolat->d($tabla.'_dok', "`{$tabla}_id`='{$recid}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    if($tabla!='munkalap'){
        $munkalapok = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs', "`bejelentes_id`='{$recid}'");
        foreach($munkalapok as $ml){
            $adatbazis_kapcsolat->d('munkalap_kapcs', "`recid`='{$ml['recid']}'");
echo $adatbazis_kapcsolat->gp() . '<br>';
            bejelentes_torol($ml['recid'], 'munkalap');
        }
    }
}*/

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




// Státusz hozzáadás manuálisan
if (false && isset($_GET['add_statusz'])) {
    addStatusz('bejelentes_statusz', 0000, BEJELENTES_STAT_FOLYAMATBAN, '', '', '', ' 2018.06.22 19:20', ' Korsós Gyula');
    exit;
}


// Dokumentumok és megjegyzések karbantartása
if (isset($_GET['dok_megj'])) {
    
//    $tabla = 'bejelentes';
//    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`dok_db`=0");
//    foreach($bejelentesek as $bejelentes){
//        
//        $megj_ek = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla`='{$tabla}' AND `id`='{$bejelentes['recid']}'");
//        $dok_ok  = $adatbazis_kapcsolat->s('recid', $tabla.'_dok', "`{$tabla}_id`='{$bejelentes['recid']}'");
//        if($megj_ek || $dok_ok){
//            $adatbazis_kapcsolat->u($tabla, array('megj_db', 'dok_db'), array(count($megj_ek), count($dok_ok)), "`recid`='{$bejelentes['recid']}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//    }
//    
//    $tabla = 'bejelentes_arch';
//    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla);
//    foreach($bejelentesek as $bejelentes){
//        $megj_ek = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla`='bejelentes' AND `id`='{$bejelentes['recid']}'");
//        $dok_ok  = $adatbazis_kapcsolat->s('recid', $tabla.'_dok', "`bejelentes_id`='{$bejelentes['recid']}'");
//        $adatbazis_kapcsolat->u($tabla, array('megj_db', 'dok_db'), array(count($megj_ek), count($dok_ok)), "`recid`='{$bejelentes['recid']}'");
//    }
//    echo '<hr>KÉSZ';
//    exit;
//    $tabla = 'munkalap';
//    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla, "`dok_db`=0");
//    foreach($bejelentesek as $bejelentes){
//        $megj_ek = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla`='{$tabla}' AND `id`='{$bejelentes['recid']}'");
//        $dok_ok  = $adatbazis_kapcsolat->s('recid', $tabla.'_dok', "`{$tabla}_id`='{$bejelentes['recid']}'");
//        if($megj_ek || $dok_ok){
//            $adatbazis_kapcsolat->u($tabla, array('megj_db', 'dok_db'), array(count($megj_ek), count($dok_ok)), "`recid`='{$bejelentes['recid']}'");
//echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//    }
//    echo '<hr>KÉSZ';
//    exit;
//    $tabla = 'munkalap_arch';
//    $bejelentesek = $adatbazis_kapcsolat->s('recid', $tabla);
//    foreach($bejelentesek as $bejelentes){
//        $megj_ek = $adatbazis_kapcsolat->s('recid', 'megjegyzes', "`tabla`='munkalap' AND `id`='{$bejelentes['recid']}'");
//        $dok_ok = $adatbazis_kapcsolat->s('recid', $tabla.'_dok', "`munkalap_id`='{$bejelentes['recid']}'");
//        $adatbazis_kapcsolat->u($tabla, array('megj_db', 'dok_db'), array(count($megj_ek), count($dok_ok)), "`recid`='{$bejelentes['recid']}'");
//    }
//    
//    
//    echo '<hr>KÉSZ';
//    exit;
    
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
}
?>