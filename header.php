<?php
require_once('weblap.php');

if($_GET['id'])   { $_GET['id']    = sql_ertek($_GET['id']);    }
if($_GET['kat'])  { $_GET['kat']   = sql_ertek($_GET['kat']);   }
if($_GET['fokat']){ $_GET['fokat'] = sql_ertek($_GET['fokat']); }
if($_GET['o'])    { $_GET['o']     = sql_ertek($_GET['o']);     }
if($_GET['lh'])   { $_GET['lh']    = sql_ertek($_GET['lh']);    }
if($_GET['ksz'])  { $_GET['ksz']   = sql_ertek($_GET['ksz']);   }

// A HTML kód előállítása (<head>)
$head = $weblap -> head_keszit();

$head -> title_hossz        = get_title_hossz();
$head -> desc_hossz         = get_desc_hossz();
$head -> keywords_hossz     = get_key_hossz();

$head -> setDocType ( get_doctype() );
$head -> setTitle   ( get_title() );
$head -> setDesc    ( get_description() );
$head -> setKeywords( get_keywords() );
$head -> setOg      ( get_og() );

$head -> charset            = get_web_charset();
$head -> favicon            = get_favicon();
$head -> country            = get_country();
$head -> language           = get_language();

// Alapértelmezetten '' (azaz üresstring). Ide tehető be további JS vagy CSS script, vagy bármi, ami a header-be kell
//FONTOS Google font beágyazásakor a a HTML kódban az aposztrófokat cseréld idézőjelre! (Alapértelmezetten src='URL' alakban kapod a beágyazó kódot, ezt erre módosítsd src="URL").
$head -> extrak             = '';

echo $head -> getHtml();

echo '<body>';

// Ezt a JS-t lehető legkésőbbi időpontban írasd ki, amikor még nem kapsz JS hibát mondjuk a Jquery hiánya miatt.
if( get_nyelvek() ) {
    $utvonal = 'js/wdb_min_'.$_SESSION['nyelv'].'.js';
} else {
    $utvonal = 'js/wdb_min.js';
}
if( filesize($utvonal) ) {
    echo '<script src="'.$utvonal.'"></script>';
}