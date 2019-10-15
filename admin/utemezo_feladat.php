<?php
require_once('../weblap.php');
require_once('datumra_utemezes.php');


// Ütemezett feladatok lekérdezése, bejárása
$log_utvonal = str_replace('.php', '', basename(self())).'.err';
$log_tmp = $log = '
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

// A ciklust kétszer járjuk be: először az ütmezett feladatokat, majd a rendszeres karbantartást
for ( $i_ciklus = 0; $i_ciklus < 2; $i_ciklus++ ) {

    $megnevezes = 'Ütemezett feladat';
    $targyas    = 'ütemezett feladatot';
    $tabla      = 'utemezett_feladatok';
    $tipus      = MLTIPUS_UTEMEZETT_F;
    if( $i_ciklus ) {
        $megnevezes = 'Rendszeres karbantartás';
        $targyas    = 'rendszeres karbantartást';
        $tabla      = 'rendszeres_karbantartas';
        $tipus      = MLTIPUS_RENDSZERES_K;
    }

    $ut_feladatok = $adatbazis_kapcsolat->s('*', $tabla, "DATE_ADD(`esnap_d`, INTERVAL - `riaszt` DAY)<=CURDATE() AND `utnap_d`<CURDATE() AND `aktiv`=1");
    $log_tmp = $adatbazis_kapcsolat->gp().'
';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

    foreach ( $ut_feladatok as $ut_feladat ) {

        // Tranzakciót indítunk
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // Logolás
        $log_tmp = 'Recid: '.$ut_feladat['recid'].' ('.$megnevezes.') Feladatszám: '.$ut_feladat['id'].' elindult
';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        $text = $ut_feladat['nev']."\r\n".$ut_feladat['leiras'];

        // Szállodának mi a modellje
        $szalloda = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$ut_feladat['telephely_id']}'");
        
        // Ha az ütemezett feladatnak/rendszeres karbantartásnak a hozzárendelt személyzete külső kivitelező, akkor átírjuk
        $szemelyzet_id = $ut_feladat['szemelyzet_id'];
        if( $szemelyzet_id == KULSO_KIVITELEZO ) {
            $szemelyzet_id = MUSZAKI_VEZETO;
        }

        // Új munkalap státusza mi legyen. Alapból nyitott
        $ujrecid = munkalap_letrehoz(MUNKALAP_STAT_NYITOTT, $ut_feladat['telephely_id'], $tipus, $ut_feladat['obj_id'], $ut_feladat['gep_id'], 0, $text, $szemelyzet_id, $ut_feladat['szallito_id'], $ut_feladat['id'], $ut_feladat['esnap_d'], '', $ut_feladat['loginnev']);
        if( $ujrecid === false ) {
            $db_error = true;
            $log_tmp = 'NEM SIKERÜLT új munkalapot létrehozni: '.$adatbazis_kapcsolat->gp().'
';
        } else {
            $log_tmp = 'Munkalap létrehozva: '.$adatbazis_kapcsolat->gp().'
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

        $log_tmp = ''; // Azért ürítem, mert már ki van írva a fájba, és ha a következő feltételekből egyik sem teljesül, akkor a LOG-ba bekerül mégegyszer ugyanaz a sor
        // Egyszállodás modell esetén a munkalap automatikusan hozzárendelt státuszban jön létre, ha telepített a hozzárendelt személyzet
        if( !$szalloda['modell'] && $ut_feladat['szemelyzet_id'] == TELEPITETT_SZEMELYZET ) {
            if( addStatusz('munkalap_statusz', $ujrecid, MUNKALAP_STAT_HOZZARENDELT, '', '', '', '', $ut_feladat['loginnev']) === false ) {
                $db_error = true;
                $log_tmp = 'NEM SIKERÜLT munkalap státuszt léptetni hozzárendeltre: '.$adatbazis_kapcsolat->gp().'
';
            }
        }
        // Előkészítés alatt státuszban jön létre, ha külső kivitelező a hozzárendelt személyzet
        if( !$szalloda['modell'] && $ut_feladat['szemelyzet_id'] == KULSO_KIVITELEZO ) {
            if( addStatusz('munkalap_statusz', $ujrecid, MUNKALAP_STAT_ELOKESZITES_ALATT, '', '', '', '', $ut_feladat['loginnev']) === false ) {
                $db_error = true;
                $log_tmp = 'NEM SIKERÜLT munkalap státuszt léptetni előkészítés alatt-ra: '.$adatbazis_kapcsolat->gp().'
';
            }
            
            // Ha előkészítés státuszban jött létre az új munkalap, akkor a hozzárendelt személyzetet állítsuk be Műszaki vezetőnek
            $sql = $adatbazis_kapcsolat->u('munkalap', array('szemelyzet_id'), array(MUSZAKI_VEZETO), "`recid`='{$ujrecid}'");
            if( $sql === false ) { 
                $db_error = true;
                $log_tmp = 'NEM SIKERÜLT az új munkalapot módosítani: '.$adatbazis_kapcsolat->gp().'
';
            } else {
                $log_tmp = 'Az új munkalap módosítva: '.$adatbazis_kapcsolat->gp().'
';
            }
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

        }

        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

        // Feladat adatainak frissítése
        $oszlopok = array('utnap_d',        'utesnap_d',            'update_usr');
        $ertekek  = array(date('Y-m-d'),    $ut_feladat['esnap_d'], get_nev()   );

        // Ha nem határidős (azaz ciklikus), akkor futtatjuk ütemező programot, ami visszaadja következő esedékességet
        if( $ut_feladat['cikltip'] != 'l' ) {

            $esem_d     = substr(str_replace('-', '.', $ut_feladat['esem_d']),  0, 10);
            $esnap_d    = substr(str_replace('-', '.', $ut_feladat['esnap_d']), 0, 10);
            $aktkezd_d  = str_replace('-', '.', $ut_feladat['aktkezd_d']);
            $aktvege_d  = str_replace('-', '.', $ut_feladat['aktvege_d']);

            // Logolás
            $log_tmp = "recid:$ut_feladat[recid] getnextvaliddate(false, $esnap_d, $ut_feladat[cikltip], $ut_feladat[ciklaltip], $ut_feladat[ciklszorz], $ut_feladat[ciklho], $ut_feladat[ciklhet], $ut_feladat[ciklnap], $ut_feladat[idoszakos], $ut_feladat[kezdho], $ut_feladat[kezdnap], $ut_feladat[vegeho], $ut_feladat[vegenap], $aktkezd_d, $aktvege_d) \r\n";
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
     //echo $esnap_d . "recid:$ut_feladat[recid] getnextvaliddate(false, $esem_d, $ut_feladat[cikltip], $ut_feladat[ciklaltip], $ut_feladat[ciklszorz], $ut_feladat[ciklho], $ut_feladat[ciklhet], $ut_feladat[ciklnap], $ut_feladat[idoszakos], $ut_feladat[kezdho], $ut_feladat[kezdnap], $ut_feladat[vegeho], $ut_feladat[vegenap], $aktkezd_d, $aktvege_d) <br><br>";

            $esnap_d = getnextvaliddate(false, $esnap_d, $ut_feladat['cikltip'], $ut_feladat['ciklaltip'], $ut_feladat['ciklszorz'], $ut_feladat['ciklho'], $ut_feladat['ciklhet'], $ut_feladat['ciklnap'], $ut_feladat['idoszakos'], $ut_feladat['kezdho'], $ut_feladat['kezdnap'], $ut_feladat['vegeho'], $ut_feladat['vegenap'], $aktkezd_d, $aktvege_d);

            $esnap_d = str_replace('.', '-', $esnap_d);
            // Ha ez egy múltbeli dátum (ez azt jelenti, hogy az ütmező rosszul számolt, tehát programhiba volt), akkor az esnap_d mezőbe 3000-01-01-et írunk
            if($esnap_d<date('Y-m-d')){
                $esnap_d = '3000-01-01';
            }
            array_push($oszlopok, 'esnap_d');
            array_push($ertekek,  $esnap_d );

        // Ha határidős volt, akkor inaktiváljuk
        } else {

            array_push($oszlopok, 'aktiv');
            array_push($ertekek,  0      );

        }

        // A tényleges update
        $sql = $adatbazis_kapcsolat->u($tabla,  $oszlopok, $ertekek, "`recid`='{$ut_feladat['recid']}'");
        if( $sql === false ) {
            $db_error = true;
            $log_tmp = 'NEM SIKERÜLT '.a_az($targyas).' '.$targyas.' módosítani: '.$adatbazis_kapcsolat->gp().'
';
        } else {
            $log_tmp = $megnevezes.' módosítva: '.$adatbazis_kapcsolat->gp().'
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);


        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
        } else {
            mysqli_rollback($db_kapcs);
        }

    }
}

// Eseti fenntartások
$eseti_fenntartasok = $adatbazis_kapcsolat->s('*', 'eseti_fenntartas', "DATE_ADD(`esnap_d`, INTERVAL - `riaszt` DAY)<=CURDATE() AND `aktiv`=1 AND `munkalap_recid`=0");
$log_tmp = $adatbazis_kapcsolat->gp().'
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

foreach($eseti_fenntartasok as $eseti){
    
    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    // Logolás
    $log_tmp = 'Recid: '.$eseti['recid'].' (Eseti fenntartás) Feladatszám: '.$eseti['id'].' elindult
';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

    $text = $eseti['nev']."\r\n".$eseti['leiras'];

    // Szállodának mi a modellje
    $szalloda = $adatbazis_kapcsolat->s2('modell', 'telephely', "`id`='{$eseti['telephely_id']}'");

    // Új munkalap státusza mi legyen. Alapból nyitott
    $ujrecid = munkalap_letrehoz(MUNKALAP_STAT_NYITOTT, $eseti['telephely_id'], MLTIPUS_ESETI_FENNT, $eseti['obj_id'], $eseti['gep_id'], 0, $text, $eseti['szemelyzet_id'], $eseti['szallito_id'], $eseti['id'], $eseti['esnap_d'], '', $eseti['loginnev']);
    if( $ujrecid === false ) {
        $db_error = true;
        $log_tmp = 'NEM SIKERÜLT új munkalapot létrehozni: '.$adatbazis_kapcsolat->gp().'
';
    } else {
        $log_tmp = 'Munkalap létrehozva: '.$adatbazis_kapcsolat->gp().'
';
    }
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

    // Egyszállodás modell esetén a munkalap automatikusan hozzárendelt státuszban jön létre, ha telepített a hozzárendelt személyzet
    if( !$szalloda['modell'] && $eseti['szemelyzet_id']==TELEPITETT_SZEMELYZET ){
        if( addStatusz('munkalap_statusz', $ujrecid, MUNKALAP_STAT_HOZZARENDELT, '', '', '', '', $eseti['loginnev'])===false ){
            $db_error = true;
            $log_tmp = 'NEM SIKERÜLT munkalap státuszt léptetni hozzárendeltre: '.$adatbazis_kapcsolat->gp().'
';
        }
    }
    // Előkészítés alatt státuszban jön létre, ha külső kivitelező a hozzárendelt személyzet
    if( !$szalloda['modell'] && $eseti['szemelyzet_id']==KULSO_KIVITELEZO ){
        if( addStatusz('munkalap_statusz', $ujrecid, MUNKALAP_STAT_ELOKESZITES_ALATT, '', '', '', '', $eseti['loginnev'])===false ){
            $db_error = true;
            $log_tmp = 'NEM SIKERÜLT munkalap státuszt léptetni előkészítés alatt-ra: '.$adatbazis_kapcsolat->gp().'
';
        }
        
        // Ha előkészítés státuszban jött létre az új munkalap, akkor a hozzárendelt személyzetet állítsuk be Műszaki vezetőnek
        $sql = $adatbazis_kapcsolat->u('munkalap', array('szemelyzet_id'), array(MUSZAKI_VEZETO), "`recid`='{$ujrecid}'");
        if( $sql === false ) { 
            $db_error = true;
            $log_tmp = 'NEM SIKERÜLT az új munkalapot módosítani: '.$adatbazis_kapcsolat->gp().'
';
        } else {
            $log_tmp = 'Az új munkalap módosítva: '.$adatbazis_kapcsolat->gp().'
';
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    }
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

    // Feladat adatainak frissítése. Ütemezés napja a mai nap; Utolsó esedékesség napja az esnap-d mező értéke. Inaktiváljuk, de a létrehozott ML recid-ját is eltároljuk, hogy visszaaktiválással ne ütemeződjön újra.
    // Tehát csak azokból az eseti karbantartásokból fogunk ML-ot készíteni, aminél még nincs letárolva a munkalap_recid oszlop.
    $sql = $adatbazis_kapcsolat->u('eseti_fenntartas',  array('utnap_d',        'utesnap_d',       'munkalap_recid', 'aktiv', 'update_usr'  ),
                                                        array(date('Y-m-d'),    $eseti['esnap_d'], $ujrecid,         0,       get_nev()     ),
                                    "`recid`='{$eseti['recid']}'");
    if( $sql === false ) { 
        $db_error = true;
        $log_tmp = 'NEM SIKERÜLT '.a_az($targyas).' '.$targyas.' módosítani: '.$adatbazis_kapcsolat->gp().'
';
    } else {
        $log_tmp = $megnevezes.' módosítva: '.$adatbazis_kapcsolat->gp().'
';
    }
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);


    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }
}



// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

// A lefutást is eltároljuk
// $adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));
mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);

$weblap -> leallit();