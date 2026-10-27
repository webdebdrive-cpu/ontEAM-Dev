<?php
require_once('../engine.php');

// Adott XML kiírtaása
if( !empty($_GET['f']) ) {

    // Ne lehessen a könyvtárból kilépni (pl. f=../../config), csak a fájlnév számít
    $_GET['f'] = basename($_GET['f']);

    if( isset($_GET['del']) ) {
        unlink(get_pszk_utvonal().$_GET['f'].'.xml');
    } else {
        header('Content-Type: application/xml; charset=utf-8');
        echo fajlbol_olvas(get_pszk_utvonal().$_GET['f'].'.xml');
    }
    
    
// Létező XML fálkok felsorolása
} else {
    
    $xml_ek_kiir = '';
    $xml_ek = konyvtar_bejar(get_pszk_utvonal());
    if( $xml_ek ) {
        
        foreach( $xml_ek as $xml ) {
            $xml_ek_kiir .= /*get_http().$_SERVER['HTTP_HOST'].self().'?f='.*/trim(basename($xml), '.xml').',';
        }
        echo trim($xml_ek_kiir, ',');
        
    } else {
        echo '-1';
    }
    
}


$weblap -> leallit();