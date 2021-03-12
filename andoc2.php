<?php
// Ez a fájl az andoc.php-ban megírt webservice osztályt futtatkja
require_once 'weblap.php';

header('Content-Type: text/html; charset='.get_web_charset());

ini_set('soap.wsdl_cache_enabled', '0');

$url_elotag = get_url_elotag();

switch ( $_SERVER['HTTP_HOST'] ) {
    case 'demo.webdeb.hu'       : $wsdl_fajl = 'onteam_wd_demo.wsdl';   break;
    case 'www.onteam.hu'        : $wsdl_fajl = 'onteam.wsdl';           break;
    case 'onteam.hu'            : $wsdl_fajl = 'onteam.wsdl';           break;
    case 'teszt.onteam.hu'      : $wsdl_fajl = 'teszt.onteam.wsdl';     break;
    case 'dev.onteam.hu'        : $wsdl_fajl = 'dev.onteam.wsdl';       break;
}

// Új SOAP kliens inicializálása
$soap = new SoapClient($url_elotag.$wsdl_fajl, 
                        array(
                            'uri'       => $url_elotag.'onteam.php',
                            // 'location'  => $url_elotag.'onteam.php',
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
    
    // Ez ahhoz kell, hogy manuálisan teszteljető legyen,
    // de a gyakorlatban majd az ANdoc POST hívással kommunikál
    if( !$_POST['xml'] && !isset($_GET['teszt']) ) {
        
        echo '
            <form method="post" action="'.self().'?'.$get.'">
                <h1>'.$nev.' XML</h1>
                <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                <br><input type="submit" value="Mehet">
            </form>
            ';
        
    } else {
        // Számla import
        if( isset($_GET['teszt']) ) {
            $xml = fajlbol_olvas($get.'_minta_uj.xml');
        } else {
            $xml = $_POST['xml'];
        }

        $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml) );

//        $szamla = simplexml_load_string($xml, 'SimpleXMLElement');
//        var_dump($szamla);
//        $szamla = $szamla[0];
//        echo 'f';
//        exit;
        
        echo '<pre style="font-size: 1.4rem">';
        print_r($soap -> SzamlaAdatokImport( $xml ));
        echo '</pre>';
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
        var_dump( $soap -> SzerzodesAdatokImport( $xml ) );    
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
        var_dump( $soap -> KoltsegnemAdatokImport( $xml ) );    
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
        var_dump( $soap -> KoltseghelyAdatokImport( $xml ) );    
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
        var_dump( $soap -> SzallitoAdatokImport( $xml ) );    
    }
    
}