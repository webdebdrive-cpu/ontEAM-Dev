<?php
require_once('../weblap.php');
require_once('datumra_utemezes.php');

// Tranzakciót indítunk
$db_error = false;
mysqli_autocommit($db_kapcs, false);

// Ütemezett feladatok lekérdezése, bejárása
$ut_feladatok = $adatbazis_kapcsolat->s('*', 'utemezett_feladatok', "DATE_ADD(`esnap_d`, INTERVAL - `riaszt` DAY)<=CURDATE() AND `utnap_d`<CURDATE() AND `aktiv`=1");
echo htmlspecialchars($adatbazis_kapcsolat->gp()) . ';<br>';
foreach($ut_feladatok as $ut_feladat){
    
    $text           = $ut_feladat['id'].' feladatot ütemezte '.$ut_feladat['insert_usr']."\r\n".$ut_feladat['nev']."\r\n".$ut_feladat['leiras'];
    $feladat_szam   = 'M'.date('y').'/0'.$ut_feladat['id'];
    
    $sql = $adatbazis_kapcsolat->i('munkalap', array('ikt_szam',    'stat_id',              'text', 'szemelyzet_id',                'obj_id',               'gep_id',                'insert_usr',               'insert_dt',    'update_usr'                ),
                                               array($feladat_szam, MUNKALAP_STAT_NYITOTT,  $text,  $ut_feladat['szemelyzet_id'],   $ut_feladat['obj_id'],  $ut_feladat['gep_id'],   $ut_feladat['insert_usr'],  'NOW()',        $ut_feladat['update_usr']   )
                                   );
    if( $sql === false ) { $db_error = true; }
 echo $adatbazis_kapcsolat->gp() . ';<br>';
    
    $sql = $adatbazis_kapcsolat -> i('munkalap_statusz',
                                        array('munkalap_recid', 'stat_id',                 'insert_dt',    'insert_usr',                'update_usr'                ),
                                        array($id,              MUNKALAP_STAT_NYITOTT,     'NOW()',        $ut_feladat['insert_usr'],   $ut_feladat['update_usr']   )
                                    );
    if( $sql === false ) { $db_error = true; }
    
    // Feladat adatainak frissítése
    $oszlopok = array('utnap_d',        'utesnap_d'             );
    $ertekek  = array(date('Y-m-d'),    $ut_feladat['esnap_d']  );
    
    // Ha nem határidős (azaz ciklikus), akor futtatjuk ütemező programot, ami visszaadja következő esedékességet
    if($ut_feladat['cikltip']!='l'){
        
        $esem_d     = substr(str_replace('-', '.', $ut_feladat['esem_d']), 0, 10);
        $aktkezd_d  = str_replace('-', '.', $ut_feladat['aktkezd_d']);
        $aktvege_d  = str_replace('-', '.', $ut_feladat['aktvege_d']);
        
        //$esnap_d = getnextvaliddate(false, $esem_d, $ut_feladat['cikltip'], $ut_feladat['ciklaltip'], $ut_feladat['ciklszorz'], $ut_feladat['ciklho'], $ut_feladat['ciklhet'], $ut_feladat['ciklnap'], $ut_feladat['idoszakos'], $ut_feladat['kezdho'], $ut_feladat['kezdnap'], $ut_feladat['vegeho'], $ut_feladat['vegenap'], $aktkezd_d, $aktvege_d);
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
}


// Tranzakció kommitolása, vagy rollback-ezése
if( !$db_error ) {
    mysqli_commit($db_kapcs);
} else {
    mysqli_rollback($db_kapcs);
}