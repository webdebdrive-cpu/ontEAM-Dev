<?php
// Ez a fájl az andoc.php-ban megírt webservice osztályt futtatja
require_once '../weblap.php';

header('Content-Type: text/html; charset='.get_web_charset());

ini_set('soap.wsdl_cache_enabled', '0');

$url_elotag = get_url_elotag();

switch ( $_SERVER['HTTP_HOST'] ) {
    case 'demo.webdeb.hu'       : $wsdl_fajl = 'andoc2/onteam_wd_demo.wsdl';    break;
    case 'www.onteam.hu'        : $wsdl_fajl = 'andoc2/onteam.wsdl';            break;
    case 'onteam.hu'            : $wsdl_fajl = 'andoc2/onteam.wsdl';            break;
    case 'teszt.onteam.hu'      : $wsdl_fajl = 'andoc2/teszt.onteam.wsdl';      break;
    case 'dev.onteam.hu'        : $wsdl_fajl = 'andoc2/dev.onteam.wsdl';        break;
}

// Új SOAP kliens inicializálása
try {
    $soap = new SoapClient($url_elotag.$wsdl_fajl, 
                            array(
                                'uri'       => $url_elotag.'andoc2/andoc.php',
                                //'location'  => $url_elotag.'andoc2/andoc.php',
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
        // de a gyakorlatban majd az ANdoc POST hívással kommunikál
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
            $log_utvonal = '../andoc.log';
            $log = '*****************************
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$xml.'
    ';
            file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);
            if( filesize($log_utvonal)>(get_logfile_max_meret()) ){
                $log = fajlbol_olvas($log_utvonal);
                $log = mb_substr($log, round(mb_strlen($log, 'UTF-8')/2), NULL, 'UTF-8');
                fajlba_mentes($log_utvonal, $log);
            }

            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml) );

            $soap -> SzamlaAdatokImport( $xml );
            
            // Webservicenek így adjunk át hibát vagy sikeres üzenetet
            echo '<xml><result>OK</result><error /></xml>';
           
        }

    }

    // Szállító import
    $get = 'szerzodes';
    $nev = 'Szerződés';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] && !isset($_GET['teszt']) ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {
            // Szerződés import
            if( isset($_GET['teszt']) ) {
                $xml = fajlbol_olvas($get.'_minta.xml');
            } else {
                $xml = $_POST['xml'];
            }
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> SzerzodesAdatokImport( $xml );    
        }

    }

    // Szállító import
    $get = 'koltsegnem';
    $nev = 'Költségnem';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] && !isset($_GET['teszt']) ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            if( isset($_GET['teszt']) ) {
                $xml = fajlbol_olvas($get.'_minta.xml');
            } else {
                $xml = $_POST['xml'];
            }
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> KoltsegnemAdatokImport( $xml );    
        }

    }

    // Szállító import
    $get = 'koltseghely';
    $nev = 'Költséghely';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] && !isset($_GET['teszt']) ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            if( isset($_GET['teszt']) ) {
                $xml = fajlbol_olvas($get.'_minta.xml');
            } else {
                $xml = $_POST['xml'];
            }
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> KoltseghelyAdatokImport( $xml );    
        }

    }

    // Szállító import
    $get = 'szallito';
    $nev = 'Szállító';
    if( isset($_GET[$get]) ) {

        if( !$_POST['xml'] && !isset($_GET['teszt']) ) {

            echo '
                <form method="post" action="'.self().'?'.$get.'">
                    <h1>'.$nev.' XML</h1>
                    <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                    <br><input type="submit" value="Mehet">
                </form>
                ';

        } else {

            if( isset($_GET['teszt']) ) {
                $xml = fajlbol_olvas($get.'_minta.xml');
            } else {
                $xml = $_POST['xml'];
            }
            $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
            $soap -> SzallitoAdatokImport( $xml );    
        }

    }
} catch (SoapFault $e) {
    echo '<xml><result>ERROR</result><error>'.$e->getMessage().'</error></xml>';
}