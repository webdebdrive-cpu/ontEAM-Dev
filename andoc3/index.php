<?php
// Ez a fájl az andoc.php-ban megírt webservice osztályt futtatja
require_once '../weblap.php';

header('Content-Type: text/html; charset='.get_web_charset());

ini_set('soap.wsdl_cache_enabled', '0');

$url_elotag     = get_url_elotag();
$konyvtar       = 'log/';
$log_utvonal    = $konyvtar.'andoc.log';
$log_torolve    = $konyvtar.'log_torles_lefutott';

switch ( $_SERVER['HTTP_HOST'] ) {
    case 'demo.webdeb.hu'       : $wsdl_fajl = 'onteam_wd_demo.wsdl';   break;
    case 'www.onteam.hu'        : $wsdl_fajl = 'onteam.wsdl';           break;
    case 'onteam.hu'            : $wsdl_fajl = 'onteam.wsdl';           break;
    case 'teszt.onteam.hu'      : $wsdl_fajl = 'teszt.onteam.wsdl';     break;
    case 'dev.onteam.hu'        : $wsdl_fajl = 'dev.onteam.wsdl';       break;
}

// Új SOAP kliens inicializálása
try {
    
    if( !$_POST['xml'] ) {
        
        $adatok = '';
        foreach ($_POST as $kulcs => $ertek) {
            $adatok .= 'Post neve: '.$kulcs."\nÉrtéke:\n".$ertek."\n\n";
        }
        $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'

'.$adatok/*Sortörésekkel mentsük a logba*/;
        andoc_logolas($log);
    }
    
    $soap = new SoapClient($url_elotag.$wsdl_fajl,
                            array(
                                'uri'       => $url_elotag.'andoc.php',
                                //'location'  => $url_elotag.'andoc.php',
                                'trace'     => true,
                                'encoding'  => 'utf-8',
                                'cache_wsdl'=> WSDL_CACHE_NONE,
                                )
                            );

    // Bejelentkezéshez a felhasználói név jelszó így adható át header-en keresztül
    $auth = array(
                'username' => 'Andoc',
                'password' => 'AontEAM3',
            );

    // Fejléc előállítása
    $header_params  = new SoapVar($auth, SOAP_ENC_OBJECT);
    $header         = new SoapHeader('ontEAM', 'belepes', $header_params, false);
    // Header hívás
    $soap -> __setSoapHeaders( array($header) );

    // Számla import
    $get = 'szamla';
    $nev = 'Számla';
    if( isset($_GET[$get]) ) {

        // Ez ahhoz kell, hogy manuálisan tesztelhető legyen,
        // de a gyakorlatban majd az Andoc POST hívással kommunikál
        if( !$_POST['xml'] ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        // Ha már van POST['xml'] adatunk, akkor logolás és feldolgozás
        } else {

            $xml = $_POST['xml'];
            if( !$xml ) {
                $xml = '***Üres XML-t kaptunk!***';
            }
            
            $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
POST:
'.str_replace("><", ">\n<", $xml)/*Sortörésekkel mentsük a logba*/;
            // Logolás
            andoc_logolas($log);

            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml) );
            $soap -> SzamlaAdatokImport( $xml );

            // Ez egy XML-t ír ki arról, hogy minden OK
            andoc_valasz_siker();
           
        }

    }

    // Szállító import
    $get = 'szerzodes';
    $nev = 'Szerződés';
    if( isset($_GET[$get]) ) {
        if( !$_POST['xml'] ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {
            
            // Szerződés import
            $xml = $_POST['xml'];
            if( !$xml ) {
                $xml = '***Üres XML-t kaptunk!***';
            }
            $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
POST:
'.str_replace("><", ">\n<", $xml)/*Sortörésekkel mentsük a logba*/;
            // Logolás
            andoc_logolas($log);
            
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );

            $soap -> SzerzodesAdatokImport( $xml );    
            
            // Ez egy XML-t ír ki arról, hogy minden OK
            andoc_valasz_siker();
            
        }

    }

    // Szállító import
    $get = 'koltsegnem';
    $nev = 'Költségnem';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            $xml = $_POST['xml'];
            if( !$xml ) {
                $xml = '***Üres XML-t kaptunk!***';
            }
            $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
POST:
'.str_replace("><", ">\n<", $xml)/*Sortörésekkel mentsük a logba*/;
            // Logolás
            andoc_logolas($log);
            
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> KoltsegnemAdatokImport( $xml );    
        }

    }

    // Szállító import
    $get = 'koltseghely';
    $nev = 'Költséghely';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            $xml = $_POST['xml'];
            if( !$xml ) {
                $xml = '***Üres XML-t kaptunk!***';
            }
            $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
POST:
'.str_replace("><", ">\n<", $xml)/*Sortörésekkel mentsük a logba*/;
            // Logolás
            andoc_logolas($log);
            
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> KoltseghelyAdatokImport( $xml );    
        }

    }

    // Szállító import
    $get = 'szallito';
    $nev = 'Szállító';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            $xml = $_POST['xml'];
            if( !$xml ) {
                $xml = '***Üres XML-t kaptunk!***';
            }
            $log = '


*****************************
FOGADOTT XML
'.date('Y.m.d H:i:s').'
Ip: '.$_SERVER['REMOTE_ADDR'].'
URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
POST:
'.str_replace("><", ">\n<", $xml)/*Sortörésekkel mentsük a logba*/;
            // Logolás
            andoc_logolas($log);
            
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> SzallitoAdatokImport( $xml );    
        }

    }
} catch (SoapFault $e) {
    $log = '<xml><result>ERROR</result><error>'.$e->getMessage().'</error></xml>';
    echo $log;
    
    // Hibaüzenetet is logoljuk el
    andoc_logolas('
-----------------------------
Válaszunk Andoc felé:
'.$log.'
');
}

// Sikeres feldolgozás esetén ez a választ küldjük el + ezt is logoljuk
function andoc_valasz_siker() {
    $log = '<xml><result>OK</result><error /></xml>';
    echo $log;
    andoc_logolas('
-----------------------------
Válaszunk Andoc felé:
'.$log.'
');
}

// Andoc logololás, illetve annak archiválása
function andoc_logolas($log) {
    
    global $konyvtar;
    global $log_utvonal;
    global $log_torolve;
    
    file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);
    if( filesize($log_utvonal) > (get_logfile_max_meret_andoc()) ) {
        $uj_fajl = $konyvtar.'andoc'.date('Ymd').'.log';
        while ( is_file($uj_fajl) ) {
            $uj_fajl = fajlnev_sorszamoz($uj_fajl);
        }
        copy($log_utvonal, $uj_fajl);
        unlink($log_utvonal);
    }
    
    // Egy évnél korábbi fájlok törlése, de csak naponta egyszer
    $lefutott = fajlbol_olvas($log_torolve);
    if( $lefutott < date('Y-m-d H:i:s', time()-60*60*24) ) {
        unlink($log_torolve); // Azért kell előbb törölni, mert csak úgy beleírni újra valami miatt nem lehetett
        fajlba_mentes($log_torolve, date('Y-m-d H:i:s'));
        $logok = konyvtar_bejar($konyvtar);
        foreach ( $logok as $log ) {
            if( filemtime($log) < time()-get_logfile_max_elet_andoc() ) {
                unlink($log);
            }
        }
    }
}