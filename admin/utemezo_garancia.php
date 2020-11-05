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

// Projektszerződések jótállásaiból azokat vesszük, ahol a bejárás 30 napon belül van
$jotallasok = $adatbazis_kapcsolat->s('recid projektszerzodes_recid bejaras', 'projektszerzodes_jotallas', "`bejaras`<='".date('Y-m-d', time()+60*60*24*30)."' AND `mail_ment`='0000-00-00 00:00:00'");
$log_tmp = $adatbazis_kapcsolat->gp() . '
';

$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);

foreach ( $jotallasok as $jotallas ) {
    
    // Tranzakciót indítunk
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $pr_szerzodes = $adatbazis_kapcsolat->s2('pr_szam sz_szam targy telephely_id', 'projektszerzodes', "`recid`='{$jotallas['projektszerzodes_recid']}'");
    $projekt      = $adatbazis_kapcsolat->s2('pfelelos', 'projektkezeles', "`ikt_szam`='{$pr_szerzodes['pr_szam']}'");
    $login        = $adatbazis_kapcsolat->s2('teljes_nev usermail', 'login', "`recid`='{$projekt['pfelelos']}'");

    $uzi = 
'Tisztelt {NEV}!
<br>
<br>Az alábbi projekt szerződés garanciális bejárása lesz esedékes:
<br>
<br>Szerződés szám: '.$pr_szerzodes['sz_szam'].'
<br>Szálloda: '.getSzallodaNev($pr_szerzodes['telephely_id']).'
<br>Tárgy: '.nl2br2($pr_szerzodes['targy']).'
<br>Garanciális bejárás dátuma: '.datum_kiir($jotallas['bejaras']).'
        '.str_replace('{GET_NEV}', get_nev(), fajlbol_olvas('../sablon/admin/mail/alairas.html') );

    $kieg = get_mail_teszt_felirat();
    $targy_kieg = $kieg[0];
    $uzi_kieg   = $kieg[1];

    $idobelyeg  = date('Y-m-d H:i:s');
    
    // Egy levél megy a projektfelelősnek.
    $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], $targy_kieg.'Értesítés garanciális bejárásáról', $uzi_kieg.str_replace('{NEV}', $login['teljes_nev'], $uzi));
    $megj = 'Garanciális bejárásáról értesítő email elküldve: '.$login['teljes_nev'].' ('.$login['usermail'].') '.datum_kiir($idobelyeg).' időpontban.';
    $log_tmp = 'Értesítő levél elküldve garanciális bejárásáról: '.$login['teljes_nev'].' - '.$login['usermail'].'
';
    // Illetve még azoknak, akiknél ez az értesítés be van kacsolva
    $loginok = $adatbazis_kapcsolat->s('teljes_nev usermail', 'login', "SUBSTR( `flags_ert`, ".(ERT_GAR_BEJARAS+1).", 1 )=1");
    foreach( $loginok as $login ) {
        $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], $targy_kieg.'Értesítés garanciális bejárásáról', $uzi_kieg.str_replace('{NEV}', $login['teljes_nev'], $uzi));
        $megj    .= 'Garanciális bejárásáról értesítő email elküldve: '.$login['teljes_nev'].' ('.$login['usermail'].') '.datum_kiir($idobelyeg).' időpontban.<br>';
        $log_tmp .= 'Értesítő levél elküldve garanciális bejárásáról: '.$login['teljes_nev'].' - '.$login['usermail'].'
';
    }
    
    // Ha van értéke, az egy hibaüzenet, akkor sikerült küldeni, ha nincs visszatérési érték
    if( !$sikeres_kuldes ) {
        
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp, FILE_APPEND | LOCK_EX);
        $sql = $adatbazis_kapcsolat->u('projektszerzodes_jotallas', array('mail_ment'), array($idobelyeg), "`recid`='{$jotallas['recid']}'");
        if( $sql === false ) {
            $log_tmp = 'NEM SIKERÜLT a jótállást módosítani: '.$adatbazis_kapcsolat->gp().'
';
            $log .= $log_tmp;
            $db_error = true;
        }
        
        // Megjegyzésbe is beleírjuk
        if( addMegj('projektszerzodes', $jotallas['projektszerzodes_recid'], $megj) === false ) {
            $db_error = true;
        }
    
    // Sikertelen levélküldés 
    } else {

        $log_tmp = 'NEM SIKERÜLT az e-mailt elküldeni: '.$adatbazis_kapcsolat->gp().'
';
        $log .= $log_tmp;
        
        // Megjegyzésbe is beleírjuk
        $megj = 'Garanciális bejárásáról értesítő email elküldése SIKERTELEN: '.$login['teljes_nev'].' ('.$login['usermail'].') '.datum_kiir($idobelyeg).' időpontban.';
        if( addMegj('projektszerzodes', $jotallas['projektszerzodes_recid'], $megj) === false ) {
            $db_error = true;
        }
        
    }
    
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

mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);