<?php
require_once 'weblap.php';

header('Content-Type: text/html; charset='. get_web_charset());

$url_elotag = get_url_elotag();

$soap = new SoapClient($url_elotag.'onteam.wsdl', 
                        array(
                            'uri'       => $url_elotag.'andoc.php',
                            'location'  => $url_elotag.'andoc.php',
                            'trace'     => true,
                            'encoding'  => 'utf-8')
                        );

// Bejelentkezéshez a felhasználói név jelszó így adható át header-en keresztül
$auth = array(
            'username' => 'Andoc',
            'password' => 'AontEAM3',
        );

$header_params  = new SoapVar($auth, SOAP_ENC_OBJECT);
$header         = new SoapHeader('ontEAM', 'belepes', $header_params, false);
$soap -> __setSoapHeaders( array($header) );

$xml = fajlbol_olvas('szamla_minta.xml');
$xml = trim( str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $xml) );
var_dump( $soap -> SzamlaAdatokImport( $xml ) );
