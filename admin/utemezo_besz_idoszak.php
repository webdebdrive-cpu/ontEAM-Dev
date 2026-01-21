<?php
require_once('../weblap.php');

// Két értesítő is küld ez az ütemező:
// 1. Ha egy beszerzési időszak aktív és az Igénylés határideje még nyitva van és 4, 3, 2, 1 nap múlva lejár
// 2. Ha egy beszerzési időszak aktív és az Igénylés határideje lejárt egy nappal és még nyitva van, akkor küldünk róla értesítést

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

$idobelyeg  = date('Y-m-d H:i:s');

// 1. Ha egy beszerzési időszak aktív és az Igénylés határideje még nyitva van és 4, 3, 2, 1 nap múlva lejár
// Címzettek lekérdezése

// Négyszer járjuk be, egyszer a holnap majd a holnap utániakról, majd 3 nap múlva és 4 nap múlvaiakról küldünk mailt
for( $i=1; $i<5; $i++ ) {

    for( $j=0; $j<2; $j++ ) {

        if( !$j ) {
            $fured_text  = '';
            $oszlop_kieg = '';
            $sql_ertesites_kieg = " AND substr(`flags_ert`, ".(ERT_BESZERZ_IGENY_IDOSZ_ZAR+1).", 1)=1";
        } else {
            $fured_text  = ' füredi';
            $oszlop_kieg = '_fured';
            $sql_ertesites_kieg = " AND substr(`flags_ert`, ".(ERT_BESZERZ_IGENY_IDOSZ_ZAR_F+1).", 1)=1";
        }
        $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 {$sql_ertesites_kieg}");
        $log_tmp = $adatbazis_kapcsolat->gp() . '
        ';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

        $datum_figyel = date('Y-m-d', strtotime('+'.$i.' Days'));
        $besz_idoszakok = $adatbazis_kapcsolat->s('recid azon nyitva hatar nyitva_fured hatar_fured', 'besz_idoszak', "`stat_id`='".BESZERZES_IDOSZAK_AKTIV."' AND `nyitva{$oszlop_kieg}`=1 AND `hatar{$oszlop_kieg}`='".$datum_figyel."'");
        $log_tmp = $adatbazis_kapcsolat->gp() . '
        ';
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        
        $alairas = str_replace('{GET_NEV}', get_nev(), fajlbol_olvas('../sablon/admin/mail/alairas.html') );
        
        foreach ( $besz_idoszakok as $besz_idoszak ) {
            $log_tmp = $megj = '';
        
            // Ha a normál határidő fog lejárni
            $mail_text_kieg = '';
            if( $besz_idoszak['nyitva'.$oszlop_kieg] && $besz_idoszak['hatar'.$oszlop_kieg] == $datum_figyel ) {
                $mail_text_kieg .= '<br>A '.$besz_idoszak['azon'].'. sz. tervezett'.$fured_text.' beszerzési időszak igénylési határideje '.datum_kiir($besz_idoszak['hatar'.$oszlop_kieg]).' napon lejár.';
            }
        
            foreach ($cimzettek as $cimzett) {
                $cimzettek = explode(',', $cimzett['usermail']);
        
                $uzi = 
                    'Tisztelt '.$cimzett['teljes_nev'].'!
                    <br>
                    '.$mail_text_kieg.'
                    <br>Kérjük, hogy a határidőig adja fel tervezett igényeit!
                    '.$alairas;
        
                $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), array($cimzett['teljes_nev']), $cimzettek, $targy_kieg.'Emlékeztető az igénylési időszak közelgő lezárásáról', $uzi_kieg.$uzi);
                $log_tmp    .= 'Emlékeztető igénylési időszak lezárásáról értesítő email elküldve: '.$cimzett['teljes_nev'].' - '.$cimzett['usermail'].'
        ';
                $megj       .= 'Emlékeztető igénylési időszak lezárásáról értesítő email elküldve: '.$cimzett['teljes_nev'].' ('.$cimzett['usermail'].') '.datum_kiir($idobelyeg).' időpontban.';
            }
        
            if( !$sikeres_kuldes ) {
                // Megjegyzésbe is beleírjuk
                if( addMegj('besz_idoszak', $besz_idoszak['recid'], $megj) === false ) {
                    $db_error = true;
                }
        
                $log .= $log_tmp;
                file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
            }
        }
    }
}



// 2. Ha egy beszerzési időszak aktív és az Igénylés határideje lejárt egy nappal és még nyitva van, akkor küldünk róla értesítést
// Címzettek lekérdezése
$sql_ertesites_kieg = " AND substr(`flags_ert`, ".(ERT_BESZERZ_IGENY_LEZAR_VAR+1).", 1)=1";
$cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "`aktiv`=1 AND `virtual`=0 {$sql_ertesites_kieg}");
if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    echo $adatbazis_kapcsolat -> gp().'<br>';
}
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

$datum_figyel = date('Y-m-d', strtotime('-1 Day'));
$besz_idoszakok = $adatbazis_kapcsolat->s('recid azon nyitva hatar nyitva_fured hatar_fured', 'besz_idoszak', "`stat_id`='".BESZERZES_IDOSZAK_AKTIV."' AND (
                                                                                                                                                                (`nyitva`=1       AND `hatar`      <'".$datum_figyel."') OR
                                                                                                                                                                (`nyitva_fured`=1 AND `hatar_fured`<'".$datum_figyel."')
                                                                                                                                                            )");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

$alairas = str_replace('{GET_NEV}', get_nev(), fajlbol_olvas('../sablon/admin/mail/alairas.html') );

foreach ( $besz_idoszakok as $besz_idoszak ) {
    $log_tmp = $megj = '';

    // Ha a normál határidő járt le
    $mail_text_kieg = '';
    if( $besz_idoszak['nyitva'] && $besz_idoszak['hatar'] < $datum_figyel ) {
        $mail_text_kieg .= '<br>Az igénylési határidő '.datum_kiir($besz_idoszak['hatar']).' napon lejárt.';
    }
    if( $besz_idoszak['nyitva_fured'] && $besz_idoszak['hatar_fured'] < $datum_figyel ) {
        $mail_text_kieg .= '<br>A füredi igénylési határidő '.datum_kiir($besz_idoszak['hatar_fured']).' napon lejárt.';
    }

    foreach ($cimzettek as $cimzett) {
        $cimzettek = explode(',', $cimzett['usermail']);

        $uzi = 
            'Tisztelt '.$cimzett['teljes_nev'].'!
            <br>
            <br> A '.$besz_idoszak['azon'].'. sz. tárgyi eszköz igényfeladási időszak lezárásra vár.
            '.$mail_text_kieg.'
            <br>Kérjük zárja le az igénylési időszakot!
            '.$alairas;
if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    echo '<pre style="font-size: 1.4rem">';
    print_r($cimzettek);
    echo $cimzett['teljes_nev'];
    echo '</pre>';
} else {
        $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), array($cimzett['teljes_nev']), $cimzettek, $targy_kieg.'Értesítés: igénylési időszak lejárt', $uzi_kieg.$uzi);
}

        $log_tmp    .= 'Értesítés igénylési időszak lezárásáról értesítő email elküldve: '.$cimzett['teljes_nev'].' - '.$cimzett['usermail'].'
';
        $megj       .= 'Értesítés igénylési időszak lezárásáról értesítő email elküldve: '.$cimzett['teljes_nev'].' ('.$cimzett['usermail'].') '.datum_kiir($idobelyeg).' időpontban.';
    }

    if( !$sikeres_kuldes ) {
        // Megjegyzésbe is beleírjuk
        if( addMegj('besz_idoszak', $besz_idoszak['recid'], $megj) === false ) {
            $db_error = true;
        }

        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
    }
}

// A lefutást is eltároljuk
$log_tmp = '
'.date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);