<?php
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

// Adott nap, illetve másnap milyen beosztások vannak?
$beosztasok = $adatbazis_kapcsolat->s('beosztas_nevek_recid beosztas_recid datum', 'beosztas_naptar', "`datum`='".date('Y-m-d')."' OR `datum`='".date('Y-m-d', time()+(60*60*24))."'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

// Összeszedjük a beosztás neveket, és a hozzá tartozó szállodákat,
// illetve ellenőrizzük, hogy a törzsadathoz a szálloda létezik-e?
$level_uzi = '';
foreach( $beosztasok as $beosztas ) {
    
    // Beosztás alapján a szálloda visszakeresése
    $telephely = $adatbazis_kapcsolat->s2('telephely_id', 'beosztas', "`recid`='{$beosztas['beosztas_recid']}'");
    $log_tmp = $adatbazis_kapcsolat->gp() . '
    ';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
    // Login beazonosítása
    $torzsadat = $adatbazis_kapcsolat->s2('torzsadat_recid', 'beosztas_nevek', "`recid`='{$beosztas['beosztas_nevek_recid']}'");
    $log_tmp = $adatbazis_kapcsolat->gp() . '
    ';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
    $torzsadat = $adatbazis_kapcsolat->s2('felh_loginnev nev', 'torzsadat', "`recid`='{$torzsadat['torzsadat_recid']}'");
    $log_tmp = $adatbazis_kapcsolat->gp() . '
    ';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
    // Van-e az illetőnek megfelelő szállodája?
    $van_szallodaja = $adatbazis_kapcsolat->s2('recid', 'telephely_kapcs', "`nev`='{$torzsadat['felh_loginnev']}' AND `telephely_id`='{$telephely['telephely_id']}'");
    $log_tmp = $adatbazis_kapcsolat->gp() . '
    ';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
    if( !$van_szallodaja ) {
        $level_uzi .= datum_kiir($beosztas['datum']).' Név: '.$torzsadat['nev'].' (Loginnév: '.$torzsadat['felh_loginnev'].') Szálloda: '. getSzallodaNev($telephely['telephely_id']).'<br>
';
    }
}

if( $level_uzi ) {
    $uzi = 'Tisztelt {NEV}!
<br>
<br>A munkaidőbeosztás szerinti szálloda nincs hozzárendelve az alábbi felhasználókhoz:
<br>'.$level_uzi.'
<br>
<br>Rendelje hozzá a szállodát a felhasználókhoz, különben nem fogja látni ontEAM rendszerben a feladatait!
        '.fajlbol_olvas('../sablon/admin/mail/alairas.html');
    
    $log_tmp = '

Levél tartalma:
    '.$uzi.'

Értesítést kapott róla:
';
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    
    $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail', 'login', "substr(`flags_ert`, ".(ERT_BEOSZTAS_ELL+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
    foreach( $cimzettek as $cimzett ) {
        
        $akt_uzi = str_replace('{NEV}', $cimzett['teljes_nev'], $uzi);
        $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $cimzett['teljes_nev'], $cimzett['usermail'], get_nev().' munkaidőbeosztás szerinti szálloda nincs hozzárendelve felhasználóhoz', $akt_uzi);
        
        $log_tmp = $cimzett['teljes_nev'].' ('.$cimzett['usermail'].')
';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
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
