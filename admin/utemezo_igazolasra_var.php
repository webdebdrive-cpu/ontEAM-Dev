<?php
// Reggelente egy e-mailt kapnak azok a műszaki vezetők, akiknek igazolandó számlájuk van az ontEAM-ben
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

$kieg = get_mail_teszt_felirat();
$targy_kieg = $kieg[0];
$uzi_kieg   = $kieg[1];
$muszaki_vezetok = $adatbazis_kapcsolat -> s('nev teljes_nev usermail', 'login', "`aktiv`=1 AND `virtual`=0 AND `usermail`!='' AND `szemelyzet_id`='".MUSZAKI_VEZETO."'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
foreach ($muszaki_vezetok as $muszaki_vezeto) {

    $megszolitas = $muszaki_vezeto['teljes_nev'];
    if( strpos($muszaki_vezeto['teljes_nev'], '|') !== false ) {
        $megszolitas = explode('|', $muszaki_vezeto['teljes_nev']);
        $megszolitas = $megszolitas[0];
    }

    $szamlak = array(
                        array('nev' => 'Fenntartási számlák',   'tabla' => 'fenntartasi_szamla' ),
                        array('nev' => 'Energia számlák',       'tabla' => 'energia_szamla'     ),
                        array('nev' => 'Anyag számlák',         'tabla' => 'anyag_szamla'       ),
                    );
    $uzi_mondatok = '';
    foreach ($szamlak as $szamla) {
        // Nézzük meg, hogy van-e olyan még nem igazolt számlája, ami olyan szállodához tartozik, ami adott műszaki vezetőnek a látóköre
        $van_szamlaja = $adatbazis_kapcsolat -> s2('recid', $szamla['tabla'], "
                                                    `telephely_id` IN (
                                                                        SELECT `telephely_id` FROM `".get_db_tabla()."telephely_kapcs` WHERE `nev`='{$muszaki_vezeto['nev']}'
                                                                    )
                                                    AND `feldolgozva_dt`!='0000-00-00 00:00:00' AND `igazolva_dt`='0000-00-00 00:00:00'");
        $log_tmp = $adatbazis_kapcsolat->gp() . '
';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        if( $van_szamlaja ) {
            $log_tmp = 'A fenti lekérdezésre volt eredmény, ezért e számlatípusról KAP értesítést "'.$muszaki_vezeto['nev'].'" login nevű műszaki vezető.

';
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
            $uzi_mondatok .= '<br>'.$szamla['nev'].' igazolása.
';
        } else {
            $log_tmp = 'A fenti lekérdezésre NEM volt eredmény, ezért e számlatípusról NEM KAP értesítést "'.$muszaki_vezeto['nev'].'" login nevű műszaki vezető.

';
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        }
    }

    if( $uzi_mondatok ) {
        $mit            = array('{NEV}'     );
        $mivel          = array($megszolitas);
        $megszolitas    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/megszolitas.html'));
    
        $targy   =  'Önnek igazolásra váró számlái vannak '.get_nev().' rendszerben';
        $uzi     =  'Önnek az alábbi feladatai vannak '.get_nev().' rendszerben:
                    '.$uzi_mondatok.'
                    <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');
    
        // Ha több mailcím is szerepel vesszővel elválasztva, akkor azt is le kell kezelni:
        $mailcimek = explode(',', $muszaki_vezeto['usermail']);
if( isset($_GET['tibi_teszt']) ) {
    $mailcimek = array('korcsmaros@szinvatrend.hu');
}
        foreach( $mailcimek as $mailcim ) {
            $level = level_kuldes(get_nev(), get_mail(), $muszaki_vezeto['teljes_nev'], $mailcim, $targy_kieg.$targy, $uzi_kieg.$megszolitas.$uzi);
            $log_tmp = 'Levél kiküldve a '.$mailcim.' címre:
Önnek az alábbi feladatai vannak '.get_nev().' rendszerben:
'.$uzi_mondatok.'
______

';
            $log .= $log_tmp;
            file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);    
        }
    }

}

// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);