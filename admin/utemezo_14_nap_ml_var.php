<?php
require_once('../weblap.php');

header('Content-Type: text/html; charset='.get_web_charset());

$log_utvonal = str_replace('.php', '', basename(self())).'.err';
$log_tmp = $log = '
*****************************
'.date('Y.m.d H:i:s').' '.basename(self()).' elindult
';
file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

// Adott méret felett felezzük a log fájlt
if( filesize($log_utvonal)>get_logfile_max_meret() ){
    $log_tmp = fajlbol_olvas($log_utvonal);
    $log_tmp = mb_substr($log_tmp, round(mb_strlen($log_tmp, 'UTF-8')/2), NULL, 'UTF-8');
    fajlba_mentes($log_utvonal, $log_tmp);
}

// Tábla ürítése
mysqli_query($db_kapcs, 'TRUNCATE '. get_db_tabla().'munkalap_14var;');
$log_tmp = 'TRUNCATE '. get_db_tabla().'munkalap_14var;'."\r\n";
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

// 15 napja várakozó munkalapok kiszámítása és letárolása egy ideiglenes táblába
$varakozok = $adatbazis_kapcsolat->s('munkalap_recid, text, login, insert_dt, insert_usr', 'munkalap_statusz', "`stat_id`='".MUNKALAP_STAT_VARAKOZO."' AND `insert_dt`<'".date('Y-m-d H:i:s', time()-60*60*24*15)."'");
$log_tmp = $adatbazis_kapcsolat->gp()."\r\n";
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

foreach($varakozok as $varakozo){
    $munkalap = $adatbazis_kapcsolat->s2('recid telephely_id', 'munkalap', "`recid`='{$varakozo['munkalap_recid']}'");
    $log_tmp = $adatbazis_kapcsolat->gp()."\r\n";
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

        
    $szalloda_nev = getErtek('nev', 'telephely', 'id', $munkalap['telephely_id']);

    $adatbazis_kapcsolat->i('munkalap_14var',   array('munkalap_id',        'telephely_id',             'stat_insert_login', 'telephely_nev', 'stat_insert_dt',       'stat_insert_usr',         'indok',           'insert_dt',   'update_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($munkalap['recid'],   $munkalap['telephely_id'],  $varakozo['login'],  $szalloda_nev,   $varakozo['insert_dt'], $varakozo['insert_usr'],   $varakozo['text'], 'NOW()',       'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                            );

    $log_tmp = $adatbazis_kapcsolat->gp()."\r\n";
    $log .= $log_tmp;
    file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);
    
}


// Vegyük azokat a felhasználókat, akinél a "Több, mint 14 napja várakozó munkalapok" értesítés be van pipálva
$loginok = $adatbazis_kapcsolat->s('nev teljes_nev usermail szemelyzet_id', 'login', "SUBSTR( `flags_ert`, ".(ERT_14_NAPJA_VARO_ML+1).", 1 )=1");
foreach ($loginok as $login){
    
    // Login szerepköre
    $login_szerepkor = getSzerepkorNev($login['szemelyzet_id']);
    
    // Lekérdezzük azokat a sorokat az ideiglenes táblából, ami adott felhasználó láthatóságába tartozik
    $szallodak = get_akt_login_szallodai(false, false, $login['nev']);
    
    if(!$login['usermail']){
        $log_tmp = $login['teljes_nev'].' '.$login_szerepkor.' felhasználónak nincs mailcíme, így nem küldtünk neki levelet.'."\r\n";
        echo $login['teljes_nev'].' '.$login_szerepkor.' felhasználónak nincs mailcíme, így nem küldtünk neki levelet.<hr>';
    
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);
    }elseif(!$szallodak){
        $log_tmp = $login['teljes_nev'].' '.$login_szerepkor.' felhasználónak egyetlen olyan szállodája sincs, amiben 14 napnál régebbi várakozó munkalapok volnának, ezért nem küldtünk neki levelet.'."\r\n";
        echo $login['teljes_nev'].' '.$login_szerepkor.' felhasználónak  egyetlen olyan szállodája sincs, amiben 14 napnál régebbi várakozó munkalapok volnának, ezért nem küldtünk neki levelet.<hr>';
    
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);
    }else{
        
        $uzi = 'Tisztelt '.$login['teljes_nev'].'!
                <br>
                <br>Az ontEAM rendszerben az alábbi, több mint két hete várakozó munkalapok vannak:
                <br>';
        
        // Szálloda és bejelentő neve alapján csoportosítunk/rendezünk
        $szalloda_bejelento = $adatbazis_kapcsolat->s('DISTINCT `telephely_nev`, `stat_insert_login`, `stat_insert_usr`', 'munkalap_14var', "`telephely_id` IN ('".implode('\', \'', $szallodak)."')", 'telephely_nev, stat_insert_usr');
        foreach($szalloda_bejelento as $szb){
            
            $bejelento = $adatbazis_kapcsolat->s2('szemelyzet_id', 'login', "`nev`='{$szb['stat_insert_login']}'");
            $szerepkor = getSzerepkorNev($bejelento['szemelyzet_id']);
            
            $uzi .= '<br>Telephely: '.$szb['telephely_nev'].'
                     <br>Várakoztatja: '.$szb['stat_insert_usr'].' '.$szerepkor.'
                     <br>
                     <br>';
            
            // Lássuk a konkrét sorokat
            $munkalapok_var = $adatbazis_kapcsolat->s('*', 'munkalap_14var', "`telephely_id` IN ('".implode('\', \'', $szallodak)."') AND `telephely_nev`='{$szb['telephely_nev']}' AND `stat_insert_usr`='{$szb['stat_insert_usr']}'", 'stat_insert_dt');
            foreach($munkalapok_var as $munkalap_var){
                
                $munkalap = $adatbazis_kapcsolat->s2('ikt_szam text', 'munkalap', "`recid`='{$munkalap_var['munkalap_id']}'");
                // echo $login['nev'].' '.$munkalap['telephely_nev'].' '.$munkalap['stat_insert_usr'].' '.$munkalap['stat_insert_dt'] . '<br>';
            
                // ML leírás mezője (ha nincs bejelentése) vagy bejelentésének hiba mezője
                $bejelentes = $adatbazis_kapcsolat->s2('bejelentes_id', 'munkalap_kapcs', "`munkalap_id`='{$munkalap_var['munkalap_id']}'");
                if($bejelentes){
                    $bejelentes = $adatbazis_kapcsolat->s2('hiba_text', 'bejelentes', "`recid`='{$bejelentes['bejelentes_id']}'");
                    $leiras = $bejelentes['hiba_text'];
                }else{
                    $leiras = $munkalap['text'];
                }
                $leiras = nl2br2($leiras);
                
                // Eltelt napok száma
                $napok = time()-strtotime($munkalap_var['stat_insert_dt']);
                $napok = round($napok / (60 * 60 * 24));
                
                $uzi .= $napok.' napja - '.$munkalap['ikt_szam'].' - '.$leiras.'
                        <br>Indoklás: '.nl2br2($munkalap_var['indok']).'
                        <br>
                        <br>';
            }
        }
        $uzi .= fajlbol_olvas('../sablon/admin/mail/alairas.html').'<br>';
        
        $sikeres_kuldes = level_kuldes(get_nev(), get_mail(), $login['teljes_nev'], $login['usermail'], get_nev().' több mint két hete várakozó munkalapok', $uzi);
        if($sikeres_kuldes){
            $log_tmp = 'E-mail elküldve '.$login['teljes_nev'].' ('.$login['usermail'].') '.$login_szerepkor.' felhasználónak: '.$uzi."\r\n";
        }else{
            $log_tmp = 'E-mail küldése nem sikerült '.$login['teljes_nev'].' ('.$login['usermail'].') '.$login_szerepkor.' felhasználónak: '.$uzi."\r\n";
        }
        $log .= $log_tmp;
        file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

        echo $login['usermail'].' címre küldve:<br>'.$uzi.'<hr>';
    }
    
}

// A lefutást is eltároljuk
$log_tmp = date('Y.m.d H:i:s').' ütemező sikeresen lefutott.




';
$log .= $log_tmp;
file_put_contents($log_utvonal, $log_tmp.PHP_EOL, FILE_APPEND | LOCK_EX);

//$adatbazis_kapcsolat->i('utemezo_log', array('utemezo', 'log'), array(basename(self()), $log));
mysqli_autocommit($db_kapcs, false);
mysqli_query($db_kapcs, 'INSERT INTO `'.get_db_tabla().'utemezo_log` SET `utemezo`=\''.basename(self()).'\', `log`=\''.addslashes($log).'\'') or die(mysqli_error($db_kapcs));
mysqli_commit($db_kapcs);
