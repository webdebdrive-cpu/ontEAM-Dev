<?php
require_once('../weblap.php');

// JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
if($_FILES['file']){
    $_FILES[$_POST['megnevezes']] = $_FILES['file'];
}

// Fájlfeltöltés
if( isset($_POST['megnevezes']) ){

    // Tiltott fájlformátumok kiszűrése, szintén nincs feltöltés
    $tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
    if( in_array( $_FILES[$_POST['megnevezes']]['type'], $tiltott_fajlok ) ){
        $weblap -> leallit();
        exit;
    }

    $mit   = array('"', '\'', '&', '#');
    $mivel = array();
    $_FILES[$_POST['megnevezes']]['name'] = nl2(str_replace($mit, $mivel, $_FILES[$_POST['megnevezes']]['name']));
    if(kit($_FILES[$_POST['megnevezes']]['name'])=='php'){
        echo 'nice try :)';
        $weblap -> leallit();
        exit;
    }
    
    // Elérési útvonal kiszámítása, könyvtárak létrehozása
    $konyvtar_db_nev = $_POST['konyvtar'];
    $utvonal = '../'.get_utvonal().$konyvtar_db_nev;
    $ikt_szam = $adatbazis_kapcsolat->s2('recid ikt_szam', 'munkalap', "`recid` = '".$_POST['sorid']."'", '', '1');
    if( $ikt_szam ) {
        $utvonal = $utvonal.'/'.str_replace('/', '_', $ikt_szam['ikt_szam']).'/email';
        $konyvtarak = explode('/', $utvonal);
        $konyvtarak_ut = '';
        foreach($konyvtarak as $konyvtar){
            $konyvtarak_ut .= $konyvtar.'/';
            if( !is_dir($konyvtarak_ut) ) {
                mkdir($konyvtarak_ut, 0777);
            }
        }
    }

    setlocale(LC_CTYPE, 'hu_HU.utf8');
    $fajlnev = pathinfo($_FILES[ $_POST['megnevezes'] ]['name']);
    
    // -- kiszűrése a fájl nevéből
    $fajlnev = str_replace('--', '-', $fajlnev['filename']);
    while(strpos($fajlnev, '--')!==false){
        $fajlnev = str_replace('--', '-', $fajlnev);
    }

    switch($_FILES[ $_POST['megnevezes'] ]['error']){

        case 1:
            echo 'Hiba! Túl nagy méretű fájl! Maximum '.ini_get('upload_max_filesize').' engedélyezett!';
        break;

        case 2:
            echo 'Hiba! Túl nagy méretű fájl!'; // A HTML form-ban is megadható volna egy méretkorlát?
        break;

        case 3:
            echo 'Hiba! A fájl feltöltése idő közben megszakadt!';
        break;

        case 4:
            echo 'Hiba! A fájl feltöltése ismeretlen okokból sikertelen volt!';
        break;

        case 6:
            echo 'Hiba! A fájl feltöltése sikertelen volt! (Hiányzik a szerveren a TMP mappa.)';
        break;

        case 7:
            echo 'Hiba! A fájl feltöltése sikertelen volt! (Nem támogatott kiterjesztés.)';
        break;

        case 0:

            // Végleges képnév a teljes elérési útvonallal, kiterjesztéssel (hozzáadjuk az eredeti fájl kiterjesztését)
            $kit   = kit($_FILES[ $_POST['megnevezes'] ]['name']);
            $kepnev = $utvonal.'/'.$fajlnev.'.'.$kit;
            if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'false' ) {
                echo 'Hiba!A fájl létezik!';
            } else {
                if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'true' ) {
                    kep_torol($kepnev);
                }

                // Fájlfeltöltés
                if( move_uploaded_file($_FILES[ $_POST['megnevezes'] ]['tmp_name'], $kepnev) ){
                    echo basename($kepnev);
                }
            }
        break;
    }

    $weblap -> leallit();
    exit;
}

?>
