<?php
require_once 'weblap.php';

header('Content-Type: text/html; charset='. get_web_charset());

ini_set('soap.wsdl_cache_enabled', '0');

$url_elotag = get_url_elotag();

$soap = new SoapClient($url_elotag.'onteam.wsdl', 
                        array(
                            'uri'       => $url_elotag.'andoc.php',
                            'location'  => $url_elotag.'andoc.php',
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

$header_params  = new SoapVar($auth, SOAP_ENC_OBJECT);
$header         = new SoapHeader('ontEAM', 'belepes', $header_params, false);
$soap -> __setSoapHeaders( array($header) );

if( isset($_GET['szamla']) ) {
    
    if( !$_POST['xml'] ) {
        
        echo '
            <form method="post" action="'.self().'?szamla">
                <h1>Számla XML</h1>
                <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                <br><input type="submit" value="Mehet">
            </form>
            ';
        
    } else {
        // Számla import
        // $xml = fajlbol_olvas('szamla_minta.xml');
        $xml = $_POST['xml'];
        $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
        var_dump( $soap -> SzamlaAdatokImport( $xml ) );
    }
    
}


if( isset($_GET['szerzodes']) ) {
    
    if( !$_POST['xml'] ) {
        
        echo '
            <form method="post" action="'.self().'?szerzodes">
                <h1>Szerződés XML</h1>
                <textarea name="xml" style="width: 50rem; height: 20rem"></textarea>
                <br><input type="submit" value="Mehet">
            </form>
            ';
        
    } else {
        // Szerződés import
        //$xml = fajlbol_olvas('szerzodes_minta.xml');
        $xml = $_POST['xml'];
        $xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
        var_dump( $soap -> SzerzodesAdatokImport( $xml ) );    
    }
    
}