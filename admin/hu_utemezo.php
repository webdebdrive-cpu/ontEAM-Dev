<?php
require_once('../weblap.php');
require_once('datumra_utemezes.php');


// Ütemezett feladatok lekérdezése, bejárása
$log = '';
$log_utvonal = get_ut_logfajl();
unlink($log_utvonal);
$ut_feladatok = $adatbazis_kapcsolat->s('*', 'utemezett_feladatok', "DATE_ADD(`esnap_d`, INTERVAL - `riaszt` DAY)<=CURDATE() AND `utnap_d`<CURDATE() AND `aktiv`=1");
$log .= '*****************************
'.date('Y.m.d H:i:s').' ütemezés indul
'.$adatbazis_kapcsolat->gp();

var_dump( getnextvaliddate(false, '0000.00.00', 'd', 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, '0000.00.00', '0000.00.00')  );
foreach($ut_feladatok as $ut_feladat){
    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // logolás
    $log .= 'Recid: '.$ut_feladat['recid'].' Feladatszám: '.$ut_feladat['id'].'
        ';
    $text = $ut_feladat['id'].' feladatot ütemezte '.$ut_feladat['insert_usr']."\r\n".$ut_feladat['nev']."\r\n".$ut_feladat['leiras'];

    // Feladatszám kiszámítása
    // Megnézzük, hogy az adott évszámmal létezik e bejegyzés
    $ikt_seq = $adatbazis_kapcsolat->s2('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
    if( $ikt_seq === false ) { $db_error = true; }
    if( $ikt_seq ) {
 
        $sorszam = $ikt_seq['sorszam'];
        $sorszam++;
        $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");
        if( $sql === false ) { $db_error = true; }
 
    } else {
 
        $sorszam = 1;
        $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam',    'ev',       'tabla'     ),
                                                        array($sorszam,     date('y'),  'munkalap'  )
                                        );
        if( $sql === false ) { $db_error = true; }
    }
 
    // Iktatószám formázása
    $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);
    $sql = $adatbazis_kapcsolat->i('munkalap', array('ikt_szam', 'uf_szam',         'stat_id',              'text', 'szemelyzet_id',                'obj_id',               'gep_id',               'tipus_id', 'esnap_d',              'insert_usr',               'insert_dt'    ),
                                               array($ikt_szam,  $ut_feladat['id'], MUNKALAP_STAT_NYITOTT,  $text,  $ut_feladat['szemelyzet_id'],   $ut_feladat['obj_id'],  $ut_feladat['gep_id'],  3/*Ütemezett feladat a tipusa*/,    $ut_feladat['esnap_d'], $ut_feladat['insert_usr'],  'NOW()',       )
                                   );
    if( $sql === false ) { $db_error = true; }

    $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                                        array('munkalap_recid', 'stat_id',                 'insert_dt',    'insert_usr',                'update_usr'                ),
                                        array($id,              MUNKALAP_STAT_NYITOTT,     'NOW()',        $ut_feladat['insert_usr'],   $ut_feladat['update_usr']   )
                                    );
    if( $sql === false ) { $db_error = true; }
    
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
//echo $esnap_d . "recid:$ut_feladat[recid] getnextvaliddate(false, $esem_d $ut_feladat[cikltip], $ut_feladat[ciklaltip], $ut_feladat[ciklszorz], $ut_feladat[ciklho], $ut_feladat[ciklhet], $ut_feladat[ciklnap], $ut_feladat[idoszakos], $ut_feladat[kezdho], $ut_feladat[kezdnap], $ut_feladat[vegeho], $ut_feladat[vegenap], $aktkezd_d, $aktvege_d) \r\n";
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
    if( $sql === false ) { $db_error = true; }
echo $adatbazis_kapcsolat->gp() . ';<br>';

    // Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }
 
}
 
// Végső log (ha ez nincs a fájban, akkor  kifagyott az ütemező
fajlba_mentes($log_utvonal, $log."\n\r".date('Y.m.d H:i:s').' ütemezés vége !utemezo sikeresen lefutott!'. "\r\n");
 
$weblap -> leallit();