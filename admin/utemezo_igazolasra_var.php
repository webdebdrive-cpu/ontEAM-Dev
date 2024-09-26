<?php
require_once('../weblap.php');

exit;
// Még ne használjuk, mert át kell írni a működését, hogy egy összesítő levelet küldjön

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

// Igazolásra váró számlákról menjen egy értesítő levél
$szamla_tablak = array('fenntartasi_szamla', 'energia_szamla', 'anyag_szamla');
foreach ($szamla_tablak as $szamla_tabla) {
    
    $szamla = $adatbazis_kapcsolat->s('recid telephely_id', $szamla_tabla, "`telephely_id`!=0 AND `feldolgozva_dt`!='0000-00-00 00:00:00' AND `igazolva_dt`='0000-00-00 00:00:00'");
    $log_tmp = $adatbazis_kapcsolat->gp() . '
    ';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

    $telephelyek = adatbazis2tomb($szamla, 'telephely_id');

    $mail_mehet_loginok = array();
    
    // Vegyük azokat a telephelyeket, amik érintve voltak olyan számlával, amik még nincsenek igazolva.
    if( $telephelyek ) {
        // Majd vegyük a műszaki vezetőket, názzük meg, kik kapcsolódnak ezekhez a telephelyekhez. Nekik kell küldeni a levelet.
        $muszaki_vezetok = $adatbazis_kapcsolat -> s('nev', 'login', "`aktiv`=1 AND `virtual`=0 AND `usermail`!='' AND `szemelyzet_id`='".MUSZAKI_VEZETO."'");
        foreach ($muszaki_vezetok as $muszaki_vezeto) {
            $szallodak = $adatbazis_kapcsolat->s2('telephely_id', 'telephely_kapcs', "`nev`='{$muszaki_vezeto['nev']}' AND `telephely_id` IN (".implode(', ', $telephelyek).")");
            // És adott szálloda a műszaki vezetőhöz tartozik
            if( $szallodak ) {
                array_push($mail_mehet_loginok, $muszaki_vezeto['nev']);

                $log_tmp = $adatbazis_kapcsolat->gp() . '
                ';
                $log .= $log_tmp;
                file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);    
            }
        }
    }
    $mail_mehet_loginok = array_unique($mail_mehet_loginok);

    $kieg = get_mail_teszt_felirat();
    $targy_kieg = $kieg[0];
    $uzi_kieg   = $kieg[1];
    foreach ($mail_mehet_loginok as $loginok) {

        $login = $adatbazis_kapcsolat -> s2('teljes_nev usermail', 'login', "`nev`='{$loginok}'");
        $log_tmp = $adatbazis_kapcsolat->gp() . '
        ';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);    

        $megszolitas = $login['teljes_nev'];
        if( strpos($login['teljes_nev'], '|') !== false ) {
            $megszolitas = explode('|', $login['teljes_nev']);
            $megszolitas = $megszolitas[0];
        }

        $mit            = array('{NEV}'     );
        $mivel          = array($megszolitas);
        $megszolitas    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/megszolitas.html'));

        $targy   =  'Önnek igazolásra váró számlái vannak '.get_nev().' rendszerben';
        $uzi     =  'Önnek igazolásra váró számlái vannak '.get_nev().' rendszerben.
                    <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');


        // Ha több mailcím is szerepel vesszővel elválasztva, akkor azt is le kell kezelni:
        $mailcimek = explode(',', $login['usermail']);
        foreach( $mailcimek as $mailcim ) {
            $level = level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $mailcim, $targy_kieg.$targy, $uzi_kieg.$megszolitas.$uzi);
            $log_tmp = 'Levél kiküldve a '.$mailcim.' címre
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