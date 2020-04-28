<?php
// Adatok helyerállítása, karbantartása, étesítések kiküldése stb..
require_once('../weblap.php');

if( false && isset($_GET['info']) && $_SESSION[get_db_tabla().'user'] ){
    phpinfo();
    exit;
}

// Loginból törzsadat feltöltése
if( isset($_GET['torzsadat_feltolt']) ) {
    $loginok = $adatbazis_kapcsolat->s('*', 'login', "`szemelyzet_id` IN (".TELEPITETT_SZEMELYZET.", ".MOBILCSOPORT.", ".DISZPECSER.")");
    foreach( $loginok as $login ) {
        $adatbazis_kapcsolat->i('torzsadat', array('nev',                'felh_loginnev',   'beosztas_id',          'aktiv',         'torolve'         ),
                                             array($login['teljes_nev'], $login['nev'],     $login['beosztas_id'],   $login['aktiv'], $login['torolve'] )
                                );
echo $adatbazis_kapcsolat->gp() . '<br>';
    }
}

// Levélküldés tesztelése
if( false && isset($_GET['mail_teszt']) ) {
    require_once('../class/class.Phpmailer.php');
    
    try {
        
        $mail = new PHPMailer;

        $felado_mail = get_mail();
        $felado_nev  = get_nev();
        $cimzett_mail = 'falu@fits.hu';
        $cimzett_nev  = 'Falu';
//        $cimzett_mail = 'bk@webdeb.hu';
//        $cimzett_nev  = 'BK';

        $targy = $uzi = 'teszt';

        $fajlok = array(
//                          '../'.get_utvonal().'nincskep.jpg',
//                          '../'.get_utvonal().'teszt.mp3',
//                          '../'.get_utvonal().'3mb.jpg',
//                          '../'.get_utvonal().'3mb2.jpg',
//                          '../'.get_utvonal().'3mb3.jpg',
                        );

        $mail -> CharSet  = get_web_charset();
        $mail -> setFrom($felado_mail, $felado_nev);
        $mail -> addReplyTo($felado_mail, $felado_nev);
        $mail -> Subject  = $targy;
        $mail -> AltBody  = strip_tags($uzi);
        $mail -> MsgHTML($uzi);

        if( is_array($fajlok) ) {
            foreach ( $fajlok as $fajl ) {
                $mail -> AddAttachment($fajl);
            }

        } elseif( $fajlok ) {
            $mail -> AddAttachment($fajlok);
        }

        // Címzett
        if( is_array($cimzett_mail) ) {
            $i = 0;
            foreach ( $cimzett_mail as $c_mail ) {
                $mail -> addAddress(trim($c_mail), trim($cimzett_nev[$i]));
                $i++;
            }
        } elseif( $cimzett_mail ) {
            $mail -> AddAddress(trim($cimzett_mail), trim($cimzett_nev));
        }

        // Másolatot kap
        if( is_array($masolat_mail) ) {
            $i = 0;
            foreach ( $masolat_mail as $c_mail ) {
                $mail -> addCC(trim($c_mail), trim($masolat_nev[$i]));
                $i++;
            }
        } elseif( $masolat_mail ) {
            $mail -> addCC(trim($masolat_mail), trim($masolat_nev));
        }

        // Másolatot kap
        if( is_array($r_masolat_mail) ) {
            $i = 0;
            foreach ( $r_masolat_mail as $c_mail ) {
                $mail -> AddBcc(trim($c_mail), trim($r_masolat_nev[$i]));
                $i++;
            }
        } elseif( $r_masolat_mail ) {
            $mail -> AddBcc(trim($r_masolat_mail), trim($r_masolat_nev));
        }

        if( !$mail->Send() ) {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            echo 'Hiba: ' . $mail->ErrorInfo;
        } else {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            echo 'Sikeres levelkuldes';
        }
    
    } catch (phpmailerException $e) {
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
      echo $e->getMessage(); //Boring error messages from anything else!
    }
    
    exit;
}


// Még nem minősített dokumentumok mind szerződés legyen
if( false && isset($_GET['dok_minosites']) ) {
    
    $szerzodesek = $adatbazis_kapcsolat->s('recid', 'projektszerzodes');
    foreach( $szerzodesek as $szerzodes ) {
        $szez_docok = $adatbazis_kapcsolat->s('recid tipus', 'projektszerzodes_dok', "`projektszerzodes_id`='{$szerzodes['recid']}'");
        foreach ( $szez_docok as $doc ) {
            if( !$doc['tipus'] ) {
                $adatbazis_kapcsolat->u('projektszerzodes_dok', array('tipus'), array(EL_DOK_TIPUS_SZERZODES), "`recid`='{$doc['recid']}'");
                echo $adatbazis_kapcsolat->gp() . '<br>';
            }
        }
    }
    
    exit;
}

// Projektek alatti dokumetumok újraszámolása
if( false && isset($_GET['projekt_dok_ujraszamol']) ) {
    
    $projektek = $adatbazis_kapcsolat->s('recid', 'projektkezeles');
    foreach( $projektek as $projekt ) {
        
    }
    
    exit;
}

// Anyagigénylés beerkezett dátum karbantartása
//if (isset($_GET['anyagigenyles_beerkezett'])) {
//    $anyagigenylesek = $adatbazis_kapcsolat->s('recid', 'anyagigenyles');
//    foreach ($anyagigenylesek as $ai){
//        $insert_dt = $adatbazis_kapcsolat->s2('insert_dt', 'anyagigenyles_statusz', "`anyagigenyles_recid`='{$ai['recid']}' AND `stat_id`='".CIKKIGN_STAT_BEERKEZETT."'", 'recid desc');
//        if($insert_dt){
//            $adatbazis_kapcsolat->u('anyagigenyles', array('beerkezett'), array($insert_dt['insert_dt']), "`recid`='{$ai['recid']}'");
//            echo $adatbazis_kapcsolat->gp() . '<br>';
//        }
//    }
//    exit;
//}


// Tibi importjához kell
if(false &&  isset($_GET['projekt_szerzodott_felhasznalt_kitolt']) ) {

    $projektek = $adatbazis_kapcsolat->s('*', 'projektkezeles', "`ikt_szam`!=''");
    foreach ($projektek as $projekt){
        $szerzodesek = $adatbazis_kapcsolat->s2('SUM(`szerz_ossz`)', 'projektszerzodes', "`pr_szam`='{$projekt['ikt_szam']}'");
        $sz_ossz = $szerzodesek['SUM(`szerz_ossz`)'];
        $fel_hato = $projekt['jkolt']-$sz_ossz;
        $adatbazis_kapcsolat->u('projektkezeles', array('sz_ossz', 'fel_hato'), array($sz_ossz, $fel_hato), "`recid`='{$projekt['recid']}'");
        echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    exit;
}

// Loginról értesítő levelek küldése
if (false && isset($_GET['login_ertesit'])) {
    
    $szalloda_nev   = 'RÁBA'; // SÁRVÁR TH
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
<br>Kérem, hogy ettől az időponttól hibabejelentéseiket az '.get_nev().' rendszerben tegyék meg.
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



            //var_dump( level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) );
            //var_dump( level_kuldes(get_nev(), get_mail(), 'Handrai Tamás', 'handari@szinvatrend.hu', 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) ); exit;
            //var_dump( level_kuldes(get_nev(), get_mail(), 'BK', 'bk@webdeb.hu', 'ontEAM rendszer indítása '.$szalloda_nev.' szállodában', $uzi) ); exit;
        }
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


                        case 'E-mail':
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
                                        $szall = mb_strtoupper( trim($szall) );
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
    header("Content-Disposition: attachment; filename=felhasznalo_import_log_".date('Y-m-d').".csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo iconv2(get_web_charset(), 'Windows-1250', $log_csv);
    
    exit;
}

// Logginnév átnevezése
if( false && isset($_GET['loginnev_csere']) ) {

    $regi_nev = trim($_GET['regi']);
    $uj_nev   = trim($_GET['uj']);
    
    if( !$regi_nev || !$uj_nev ) {
        echo 'Nem adatad meg a régi vagy új loginnevet!';
        exit;
    }
    if( !$adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$regi_nev}'") ){
        echo ucfirst(a_az($regi_nev)).' '.$regi_nev.' loginnév nem található a rendszerben!';
        exit;
    }
    if( $adatbazis_kapcsolat->s('recid', 'login', "`nev`='{$uj_nev}'") ) {
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
                            array('tabla' => 'energia_terv',            'oszlop' => 'loginnev'      ),
                            array('tabla' => 'energia_terv_dok',        'oszlop' => 'loginnev'      ),
                            array('tabla' => 'energia_terv_statusz',    'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eseti_fenntartas',        'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eseti_fenntartas_doc',    'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eszkozkezeles_dok',       'oszlop' => 'loginnev'      ),
                            array('tabla' => 'eszkozkezeles_eng_dok',   'oszlop' => 'loginnev'      ),
                            array('tabla' => 'fenntartasi_terv',        'oszlop' => 'loginnev'      ),
                            array('tabla' => 'fenntartasi_terv_dok',    'oszlop' => 'loginnev'      ),
                            array('tabla' => 'fenntartasi_terv_statusz','oszlop' => 'login'         ),
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
                            array('tabla' => 'munkalap_email_dok',      'oszlop' => 'loginnev'      ),
                            array('tabla' => 'munkalap_email_dok_arch', 'oszlop' => 'loginnev'      ),
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
                            array('tabla' => 'projektigenyles_kapcs',        'oszlop' => 'loginnev' ),
                            array('tabla' => 'projektigenyles_kapcs_arch',   'oszlop' => 'login'    ),
                            array('tabla' => 'projektigenyles_statusz',      'oszlop' => 'login'    ),
                            array('tabla' => 'projektigenyles_statusz_arch', 'oszlop' => 'login'    ),
                            array('tabla' => 'projektkezeles',              'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektkezeles_cashflow',     'oszlop' => 'login'     ),
                            array('tabla' => 'projektkezeles_cashflow_nev', 'oszlop' => 'login'     ),
                            array('tabla' => 'projektkezeles_dok',          'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektkezeles_email_dok',    'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektkezeles_statusz',      'oszlop' => 'login'     ),
                            array('tabla' => 'projektszamla',               'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszamla_dok',           'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszamla_kifizetesek',   'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszamla_tetel',         'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszerzodes',            'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszerzodes_dok',        'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszerzodes_jotallas',   'oszlop' => 'loginnev'  ),
                            array('tabla' => 'projektszerzodes_tetel',      'oszlop' => 'loginnev'  ),
                            array('tabla' => 'rendszeres_karbantartas',     'oszlop' => 'loginnev'  ),
                            array('tabla' => 'rendszeres_karbantartas_doc', 'oszlop' => 'loginnev'  ),
                            array('tabla' => 'szallito_dok',                'oszlop' => 'loginnev'  ),
                            array('tabla' => 'telephely_kapcs',             'oszlop' => 'nev'       ),
                            array('tabla' => 'torzsadat',                   'oszlop' => 'felh_loginnev' ),
                            array('tabla' => 'utemezett_feladatok',         'oszlop' => 'loginnev'  ),
                            array('tabla' => 'utemezett_feladatok_doc',     'oszlop' => 'loginnev'  ),
        
        
                        );
    foreach ( $tablak_oszlopok as $to ) {
        $adatbazis_kapcsolat->u($to['tabla'], array($to['oszlop']), array($uj_nev), "`{$to['oszlop']}`='{$regi_nev}'");
        echo $adatbazis_kapcsolat->gp() . '<br>';
    }
    
    $adatbazis_kapcsolat->u('login', array('van_jelszo'), array(0), "`nev`='{$uj_nev}'");
    echo $adatbazis_kapcsolat->gp() . '<br>';
    
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
    
    $telephely_id = 2130;
    
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


if (false && isset($_GET['anyagigenyles_tabla_oszlop_bovit'])) {
    
    $loginok = $adatbazis_kapcsolat->s('recid tabla_sorrend', 'login');
    foreach( $loginok as $login ){
        $login['tabla_sorrend'] = str_replace('{&quot;id&quot;:&quot;anyag_tabla_jovah_menny', '{&quot;id&quot;:&quot;anyag_tabla_jovah_ertek&quot;,&quot;width&quot;:118},{&quot;id&quot;:&quot;anyag_tabla_jovah_menny', $login['tabla_sorrend']);
        $adatbazis_kapcsolat->u('login', array('tabla_sorrend'), array($login['tabla_sorrend']), "`recid`='{$login['recid']}'");
    }
    
    exit;
}

// Beszállítók importja
if (isset($_GET['szallito_import'])) {
    
    header('Content-Type: text/html; charset='. get_web_charset());
    
    $_SESSION['sor_fejlec'] = array();
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

                // Ha van egyáltalán oracle kód
                if($oracle_kod && $oracle_kod!='?'){
                    $kod_hossz = strlen($oracle_kod);
                    if($kod_hossz<5){
                        for($i=0; $i<5-$kod_hossz;$i++){
                            $oracle_kod = '0'.$oracle_kod;
                        }
                    }
                    
                    // Cím feldolgozása
                    if($cim){
                        $isz = substr($cim, 0, 4);
                        if(is_numeric($isz)){
                            $cim         = trim(substr($cim, 4));
                            $elso_szokoz = strpos($cim, ' ');
                            $varos       = trim(substr($cim, 0, $elso_szokoz));
                            if(substr($varos, strlen($varos)-1)==','){
                                $varos = csonkit($varos, 1);
                            }
                            $cim         = trim(substr($cim, $elso_szokoz));
                        }else{
                            $isz    = '';
                            $varos  = '';
                        }
                    }
                    
                    // Szakterület létezik-e?
                    if($tev){
                        $szakterulet_id = $adatbazis_kapcsolat->s2('recid', 'erteklista', "`info`='{$tev}' AND `csoport`='".ERTEKLISTA_SZAKTER."'");
                        $szakterulet_id = $szakterulet_id['recid'];
                        if(!$szakterulet_id){

                            $max_id = mysqli_query($db_kapcs, 'SELECT MAX(`id`) FROM `'.get_db_tabla().'erteklista`');
                            $max_id = mysqli_fetch_array($max_id);
                            $max_id = $max_id[0]+1;

                            $szakterulet_id = $adatbazis_kapcsolat->i('erteklista', array('id',     'info', 'csoport',            'insert_dt', 'insert_usr',         'update_usr'         ),
                                                                                    array($max_id,  $tev,   ERTEKLISTA_SZAKTER,   'NOW()',     get_nev().' import',  get_nev().' import'  )
                                                                    );
                            echo 'Nem létező szakterület létrehozva: ';
                            echo $adatbazis_kapcsolat->gp() . '<br>';
                        }
                    }
                    
                    // Ellenőrizzük, hogy nem foglalt-e
                    $szallito_id = $adatbazis_kapcsolat->s2('recid', 'szallito', "`id`='{$oracle_kod}'");
                    $szallito_id = $szallito_id['recid'];
                    if($szallito_id){
                        
                        if( !isset($_GET['mehet']) ){
                            echo $oracle_kod.' Orcale kód már foglalt. Felülírható? ('.$nev.' '.$isz.' '.$varos.' '.$cim.' '.$adoszam.')' . '<br>';
                        }else{
                            $adatbazis_kapcsolat->u('szallito', array('id',         'nev', 'isz', 'varos', 'utca', 'adoszam', 'bank', 'knev',   'email', 'tel',    'aktiv',  'insert_dt', 'insert_usr',         'update_usr'       ),
                                                                array($oracle_kod,  $nev,  $isz,  $varos,  $cim,   $adoszam,  $bank,  $kontakt, $email,  $telefon, 1,        'NOW()',     get_nev().' import',  get_nev().' import'),
                                                                "`id`='$oracle_kod'"
                                                    );
echo $adatbazis_kapcsolat->gp() . '<br>';
                        }
                        
                    }else{

                        $szallito_id = $adatbazis_kapcsolat->i('szallito',  array('id',         'nev', 'isz', 'varos', 'utca', 'adoszam', 'bank', 'knev',   'email', 'tel',    'aktiv',  'insert_dt', 'insert_usr',         'update_usr'       ),
                                                                            array($oracle_kod,  $nev,  $isz,  $varos,  $cim,   $adoszam,  $bank,  $kontakt, $email,  $telefon, 1,        'NOW()',     get_nev().' import',  get_nev().' import')
                                                            );
echo $adatbazis_kapcsolat->gp() . '<br>';

                        
                    }
                    
                    if($tev){
                        // Szállító és szakterület összekapcsolása, ha még nem volna
                        if(!$adatbazis_kapcsolat->s('recid', 'szakter_kapcs', "`szallito_id`='{$szallito_id}' AND `szakter_id`='{$szakterulet_id}'")){
                            $adatbazis_kapcsolat->i('szakter_kapcs', array('szallito_id', 'szakter_id'), array($szallito_id, $szakterulet_id));
echo $adatbazis_kapcsolat->gp() . '<br>';
                        }
                    }
                    
                }


            }
            $k++;
        }
        fclose($csv_fajl);
    }else{
        echo 'Fájl nem található';
    }
    
    exit;
}


// Szerepkör és beállított jögök közti különbség kiszűrése felhasználónként
if (isset($_GET['szerepkor_jogosultsag_ell'])) {
    
    require_once 'jogosultsag.php';
    
    $csv = 'Telejes név;Loginnév;Jogosultság; Eltérések a szerepkör jogosultságaihoz képest'."\r\n";
    
    $loginok = $adatbazis_kapcsolat->s('*', 'login', "`aktiv`=1 AND `virtual`=0");
    foreach($loginok as $login){
        
        $szerepkor = $adatbazis_kapcsolat->s2('flags', 'szerepkor', "`recid`='{$login['szemelyzet_id']}'");
        if($szerepkor['flags']!=$login['flags']){
            
            $sz_flags = str_split($szerepkor['flags']);
            $lo_flags = str_split($login['flags']);
            $i = 0;
            $elteres = '';
            foreach($sz_flags as $f){
                if($sz_flags[$i]!=$lo_flags[$i]){
                    $elteres .= '"'.$jogosultsagok[$i]['nev'].'"';
                    if($sz_flags[$i]=='1'){
                        $elteres .= ' KI van pipálva;';
                    }else{
                        $elteres .= ' BE van pipálva;';
                    }
                }
                $i++;
            }
            if($elteres){
                $csv .= $login['teljes_nev'].';'.$login['nev'].';'. getSzerepkorNev($login['szemelyzet_id']) . ';'.$elteres;
                //$csv .= $szerepkor['flags'].';'.$login['flags'];
                $csv .= "\r\n";
            }
        }
    }
    
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=szerepkor_jog_elteresek ".date('Y-m-d').".csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo iconv2(get_web_charset(), 'Windows-1250', $csv);
    //echo str_replace("\r\n", '<br>', $csv);
    exit;
}
?>