<?php
require_once('../weblap.php');
require_once('datumra_utemezes.php');


// Ütemezett feladatok lekérdezése, bejárása
$log = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
$log_utvonal = str_replace('.php', '', basename(self())).'.err';
// Adott méret felett felezzük a log fájlt
if( filesize($log_utvonal)>get_logfile_max_meret() ){
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}
$ut_feladatok = $adatbazis_kapcsolat->s('*', 'utemezett_feladatok', "DATE_ADD(`esnap_d`, INTERVAL - `riaszt` DAY)<=CURDATE() AND `utnap_d`<CURDATE() AND `aktiv`=1");
$log .= $adatbazis_kapcsolat->gp().'
';

foreach($ut_feladatok as $ut_feladat){
    
    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // logolás
    $log .= 'Recid: '.$ut_feladat['recid'].' Feladatszám: '.$ut_feladat['id'].' elindult
';

    $text = /*$ut_feladat['id'].' feladatot ütemezte '.$ut_feladat['insert_usr']."\r\n".*/$ut_feladat['nev']."\r\n".$ut_feladat['leiras'];

    // Feladatszám kiszámítása
    // Megnézzük, hogy az adott évszámmal létezik e bejegyzés
    $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
    if( $ikt_seq === false ) { $db_error = true; }
    if( $ikt_seq ) {
 
        $sorszam = $ikt_seq['sorszam'];
        $sorszam++;
        $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
        if( $sql === false ) {
            $db_error = true;
            $log .= 'NEM SIKERÜLT iktatószám szekveciát frissíteni: '.$adatbazis_kapcsolat->gp().'
';
        }else{
            $log .= 'Iktatószám szekvecia frissítve: '.$adatbazis_kapcsolat->gp().'
';
            
        }
 
    } else {
 
        $sorszam = 1;
        $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam',    'ev',       'tabla'     ),
                                                        array($sorszam,     date('y'),  'munkalap'  )
                                        );
        if( $sql === false ) {
            $db_error = true;
            $log .= 'NEM SIKERÜLT új iktatószámot létrehozni: '.$adatbazis_kapcsolat->gp().'
';
        }else{
            $log .= 'Új iktatószám szekvencia bejegyzés létrehozva: '.$adatbazis_kapcsolat->gp().'
';
        }
    }
 
    // Iktatószám formázása
    $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);
    $log .= 'Iktatószám létrehozva: '.$ikt_szam.'
';
    
    $ujrecid = $adatbazis_kapcsolat->i('munkalap',  array('ikt_szam', 'uf_szam',         'stat_id',              'text', 'szemelyzet_id',                'telephely_id',               'obj_id',               'gep_id',               'tipus_id',                      'esnap_d',              'loginnev',                         'update_usr',                           'insert_dt',    'insert_usr'                             ),
                                                    array($ikt_szam,  $ut_feladat['id'], MUNKALAP_STAT_NYITOTT,  $text,  $ut_feladat['szemelyzet_id'],   $ut_feladat['telephely_id'],  $ut_feladat['obj_id'],  $ut_feladat['gep_id'],  3/*Ütemezett feladat a tipusa*/, $ut_feladat['esnap_d'], $_SESSION[get_db_tabla().'user'],   $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']   )
                                   );
    if( $ujrecid === false ) {
        $db_error = true;
        $log .= 'NEM SIKERÜLT új munkalapot létrehozni: '.$adatbazis_kapcsolat->gp().'
';
    }else{
        $log .= 'Munkalap létrehozva: '.$adatbazis_kapcsolat->gp().'
';
    }

    $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                                        array('munkalap_recid', 'stat_id',             'login',                             'insert_dt',    'insert_usr',                'update_usr'                ),
                                        array($ujrecid,         MUNKALAP_STAT_NYITOTT, $_SESSION[get_db_tabla().'user'],    'NOW()',        $ut_feladat['insert_usr'],   $ut_feladat['update_usr']   )
                                    );
    if( $sql === false ) {
        $db_error = true;
        $log .= 'NEM SIKERÜLT munkalap státuszt beszúrni: '.$adatbazis_kapcsolat->gp().'
';
    }else{
        $log .= 'Munkalap státusz beszúrva: '.$adatbazis_kapcsolat->gp().'
';
    }
    
    // Feladat adatainak frissítése
    $oszlopok = array('utnap_d',        'utesnap_d'             );
    $ertekek  = array(date('Y-m-d'),    $ut_feladat['esnap_d']  );
    
    // Ha nem határidős (azaz ciklikus), akkor futtatjuk ütemező programot, ami visszaadja következő esedékességet
    if($ut_feladat['cikltip']!='l'){

        $esem_d     = substr(str_replace('-', '.', $ut_feladat['esem_d']), 0, 10);
        $aktkezd_d  = str_replace('-', '.', $ut_feladat['aktkezd_d']);
        $aktvege_d  = str_replace('-', '.', $ut_feladat['aktvege_d']);

        // logolás
        $log .= "recid:$ut_feladat[recid] getnextvaliddate(false, $esem_d $ut_feladat[cikltip], $ut_feladat[ciklaltip], $ut_feladat[ciklszorz], $ut_feladat[ciklho], $ut_feladat[ciklhet], $ut_feladat[ciklnap], $ut_feladat[idoszakos], $ut_feladat[kezdho], $ut_feladat[kezdnap], $ut_feladat[vegeho], $ut_feladat[vegenap], $aktkezd_d, $aktvege_d) \r\n";
// echo $esnap_d . "recid:$ut_feladat[recid] getnextvaliddate(false, $esem_d $ut_feladat[cikltip], $ut_feladat[ciklaltip], $ut_feladat[ciklszorz], $ut_feladat[ciklho], $ut_feladat[ciklhet], $ut_feladat[ciklnap], $ut_feladat[idoszakos], $ut_feladat[kezdho], $ut_feladat[kezdnap], $ut_feladat[vegeho], $ut_feladat[vegenap], $aktkezd_d, $aktvege_d) <br><br>";
        $esnap_d = getnextvaliddate(false, $esem_d, $ut_feladat['cikltip'], $ut_feladat['ciklaltip'], $ut_feladat['ciklszorz'], $ut_feladat['ciklho'], $ut_feladat['ciklhet'], $ut_feladat['ciklnap'], $ut_feladat['idoszakos'], $ut_feladat['kezdho'], $ut_feladat['kezdnap'], $ut_feladat['vegeho'], $ut_feladat['vegenap'], $aktkezd_d, $aktvege_d);

        $esnap_d = str_replace('.', '-', $esnap_d);
        // Ha ez egy múltbeli dátum, akkor az esnap_d mezőbe 3000-01-01-et írunk
        if($esnap_d<date('Y-m-d')){
            $esnap_d = '3000-01-01';
        }
        array_push($oszlopok, 'esnap_d');
        array_push($ertekek,  $esnap_d );
        
    // Ha határidős volt, akkor inaktiváljuk
    }else{
        
        array_push($oszlopok, 'aktiv');
        array_push($ertekek,  0      );
        
    }
    
    // A tényleges update
    $sql = $adatbazis_kapcsolat->u('utemezett_feladatok',  $oszlopok, $ertekek, "`recid`='{$ut_feladat['recid']}'");
    if( $sql === false ) { 
        $db_error = true;
        $log .= 'NEM SIKERÜLT ütemezett feladatot módosítani: '.$adatbazis_kapcsolat->gp().'
';
    }else{
        $log .= 'Ütemezett feladat módosítva: '.$adatbazis_kapcsolat->gp().'
';
    }


    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }
 
}

// A lefutást is eltároljuk
$log .= date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
fajlba_mentes($log_utvonal, $log);
echo htmlspecialchars($log) . '<br>';

// A lefutást is eltároljuk
$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));
 
$weblap -> leallit();