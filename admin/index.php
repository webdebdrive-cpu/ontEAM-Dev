<?php
// Nyitólap

require_once('header.php');

require_once('dashboard.php');

// 
if( isset($_GET['teszt']) ) {
    $projektek = $adatbazis_kapcsolat->s('ikt_szam', 'projektkezeles', "");
    foreach( $projektek as $p ) {
        projektkezeles_szamlazott_osszeg($p['ikt_szam']);
    }
}

require_once('footer.php');