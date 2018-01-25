<?php
/**
    FONTOS OLVASD EL!

    1. ITT NEM SZERPELHET A: require_once('../weblap.php'); A session_start() miatt, ami a képfeltöltéshez kell
    2. A $tobbkep_tablanev = 'kepek'; változót muszáj itt megadni. Ennek értéke az alap PHP fájlban, az if($tobb_kep){} feltételnél megadott $tobbkep_tablanev-vel azonos. Lényegében azt az adatbázis táblát kell megadni, amiben az adatok letárolásra kerülnek.

    HA TÖBB ILYEN FELTÖLTÉSI PANEL IS KELL (azaz nem csak egy "Több kép" lenne hanem több is), akkor a következőket lépésekre lesz szükség:
        - tobb_kep.php-ból csinálj klónokat, pl. tobb_kep2.php (ugyanis ez lesz meghívva ajax-al, fájl feltöltésekor)
        - minden egyes $_SESSION['fajl'] kezdetű SESSION változókat is klónozd. Pl. $_SESSION['fajl']['megnevezes'] => $_SESSION['fajl']['megnevezes2']. Ezt minden egyes előfordulásnál meg kell tenni.

 */

require_once('../class/html/class.Img.php');

$tobbkep_tablanev = str_replace('.php', '', basename($_SERVER['PHP_SELF'])).'_dok';
if( $_POST['tabla'] ) {
    $tobbkep_tablanev = $_POST['tabla'].'_dok';
}

// Eszközkezelésnél az engedélyeknek van egy külön tálbája
if(basename($_SERVER['PHP_SELF']) == 'eszkozkezeles.php' && isset($_GET['engedelyek'])){
    $tobbkep_tablanev = 'eszkozkezeles_eng_dok';
}
// echo $tobbkep_tablanev; exit;

// Képfeltöltés
if(isset($_POST['session_name'])){

    session_id($_POST['session_name']);
    session_start();
    require_once('../weblap.php');

    $konyvtar_db_nev = csonkit($_SESSION['fajl']['tabla'], 4);
    $utvonal = '../'.get_utvonal().$konyvtar_db_nev; //utolsó 4 karakter a "_dok", ezt levesszük

    if(!is_dir($utvonal)){
        mkdir($utvonal, 0777);
    }

    $ikt_szam = $adatbazis_kapcsolat->s2('recid ikt_szam', $_SESSION['fajl']['konyvtar'], "`recid` = '".$_POST['sorid']."'", '', '1');

    if( $ikt_szam ) {

        $utvonal = $utvonal.'/'.str_replace('/', '_', $ikt_szam['ikt_szam']);

        if( !is_dir($utvonal) ) {
            mkdir($utvonal, 0777);
        }
    }

    // JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
    if($_FILES['file']){
        $_FILES[$_POST['megnevezes']] = $_FILES['file'];
    }

	// Tiltott fájlformátumok kiszűrése, szitnén nincs feltöltés
	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
    if( in_array( $_FILES[ $_SESSION['fajl']['megnevezes'] ]['type'], $tiltott_fajlok ) ){
        echo 'Hiba!Ilyen formátumú fájl feltöltése tiltott, a fájlfeltöltés nem jött létre!';
        $weblap -> leallit();
		exit;
	}

    setlocale(LC_CTYPE, 'hu_HU.utf8');
    $fajlnev = pathinfo($_FILES[ $_SESSION['fajl']['megnevezes'] ]['name']);
    $fajlnev = $fajlnev['filename'];

    switch($_FILES[ $_SESSION['fajl']['megnevezes'] ]['error']){

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
            $kit   = kit($_FILES[ $_SESSION['fajl']['megnevezes'] ]['name']);
            $kepnev = $utvonal.'/'.$fajlnev.'.'.$kit;

            if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'false' ) {
                echo 'Hiba!A fájl létezik!';
            } else {
                if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'true' ) {
                    kep_torol($kepnev);
                }

                // Fájlfeltöltés
                if(move_uploaded_file($_FILES[ $_SESSION['fajl']['megnevezes'] ]['tmp_name'], $kepnev)){

                    $kep_tort = str_replace('../'.get_utvonal().$konyvtar_db_nev.'/', '', $kepnev);
                    if( $_POST['ellenorzes_kihagy'] == 'false' ) {
                        // Letárolás adatbázisba is A kép neve nem tartalmazza a get_utvonal részt! Ha sikeres akkor adunk egy outputot a flashfile-nak
                        $id = $adatbazis_kapcsolat -> i( $_SESSION['fajl']['tabla'],
                            array($_SESSION['fajl']['oszlop'],    'utvonal',  'fajlnev',         'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                           ),
                            array($_SESSION['fajl']['termek_id'], $kep_tort,  $fajlnev.'.'.$kit, $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev'] )
                        );


                        $sor_kepek = $adatbazis_kapcsolat->s2('*', $_SESSION['fajl']['tabla'], 'recid='.$id);

                        $osszes_sor = $adatbazis_kapcsolat->s('recid', $_SESSION['fajl']['tabla'], '');

                        echo get_tobbkep_thumb($sor_kepek, count($osszes_sor));
                    } else {
                        $sor_kep = $adatbazis_kapcsolat->s($_SESSION['fajl']['oszlop'], $_SESSION['fajl']['tabla'], "`".$_SESSION['fajl']['oszlop']."` = '".$_POST['sorid']."' AND `utvonal` = '{$kep_tort}'", '', '0,1');

                        if( !$sor_kep ) {
                            $id = $adatbazis_kapcsolat -> i( $_SESSION['fajl']['tabla'],
                                array($_SESSION['fajl']['oszlop'],    'utvonal',  'fajlnev',            'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                            ),
                                array($_SESSION['fajl']['termek_id'], $kep_tort,  $fajlnev.'.'.$kit,    $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev']  )
                            );
                        }

                        echo 'frissit';
                    }
                }
            }
        break;
    }

    $weblap -> leallit();
    exit;
}

require_once('../weblap.php');

// Kép törlése
if(isset($_POST['tobbkep_torol'])){

    $kepnev = mysqli_query($db_kapcs, 'SELECT `utvonal` FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['tobbkep_torol'].'\'');
    $kepnev = mysqli_fetch_assoc($kepnev);

    $parancs = 'DELETE FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['tobbkep_torol'].'\'';
    mysqli_query($db_kapcs, $parancs);

    kep_torol('../'.get_utvonal().$kepnev['utvonal']);

    $weblap -> leallit();
    exit;
}



// Tartalom
require_once '../class/html/class.Img.php';

// Sessionok beállítása a feltöltéshez:
$megn = 'tobbkep'.$_GET['id'];
$_SESSION['fajl']['megnevezes'] = $megn;                    //A $FILE változó neve, egyedi legyen
$_SESSION['fajl']['nev']        = $sor['nev'];              //Fájlnév, a terméknévből származtatva (kiterjesztés nem kell, fajlnev_keszit is lesz később így ez sem kell)
$_SESSION['fajl']['konyvtar']   = $megnevezes;              //Könyvtár, ahol a képeket gyűjtjük (eredeti termék könyvtára)
$_SESSION['fajl']['tabla']      = $tobbkep_tablanev;        //Képek tábla, amellyel összekapcsoljuk a termékkel
$_SESSION['fajl']['oszlop']     = get_tobbkep_oszlopnev();  //Képek tábla oszopa, amellyel összekapcsoljuk a termékkel
$_SESSION['fajl']['termek_id']  = $_GET['id'];              //A termék azonosítója, amivel összekapcsoljuk

// Korlátozás csak képre
$eng_kit = '';
if(get_tobbkep_csakkep()){
    $eng_kit = ' accept="'.get_tobbkep_csakkep().'"';
}

// Fájlfeltöltéskor milyen paraméterek menjenek át
$parameterek = "'session_name':'".session_id()."', 'megnevezes':'".$megn."', 'sorid':'".$_GET['id']."'";

// Ha minden fájl feltöltése kész, mi történjen (ide kéne egy jó ajax hívás)
$gomb_neve   = 'Tallózás';
$minden_kesz = '';
if($tobbkep_tablanev=='eszkozkezeles_eng_dok'){
    $minden_kesz = 'kapcsolodoSzerkeszt(\'eszkozkezel_engedely\');';
    $gomb_neve   = 'Új engedély feltöltése';
} else {
    $minden_kesz = 'szamlaBetolt(_get(\'id\'), baseName(location.href) + \'.php\', \'dokumentumok\');';
}

$sorrend_ujkep       = fajlbol_olvas('../sablon/admin/tobbkep_ujkep.html', true);
$sorrend_ujkep_mit   = array('{GOMB_NEVE}', '{MEGN}',  '{PHP_FAJL}',                     '{ENG_KIT}', '{PARAMETEREK}', '{MINDEN_KESZ}'    );
$sorrend_ujkep_mivel = array($gomb_neve,    $megn,     'tobb_kep.php'/*self() nem jó!*/, $eng_kit,     $parameterek,    $minden_kesz       );

$tobbkep_html  = '';
$tobbkep_html .= str_replace($sorrend_ujkep_mit, $sorrend_ujkep_mivel, $sorrend_ujkep);

$i     = 0;
$kepek = $adatbazis_kapcsolat -> s('*', $tobbkep_tablanev, get_tobbkep_oszlopnev().'='.$_GET['id'], 'sor');

// Eszközkezelés engedélyeknél dátumot is kiírunk
$mellek = 'dokumentumok';
if(basename(self()) == 'eszkozkezeles.php' && isset($_GET['engedelyek'])){
    $mellek = 'engedelyek';
}

if($kepek){
    $tobbkep_html .= nl2('
                        <div>
                            <ul id="sorrendezes_tobbkep" data-tabla="'.$tobbkep_tablanev.'" data-mellek="'.$mellek.'">
                                <li class="fejlec">
                                    <div class="kep">Kép</div>
                                    <div class="felirat c" style="width: 15rem">Fájlnév</div>
                                    <div class="felirat">Feltölötte</div>
                                    <div class="felirat">Dátum</div>
                                    <div class="maradek torles"></div>
                                </li>
                                ');
    $i = 1;
    foreach($kepek as $sor_kepek) {
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, $tobbkep_tablanev);

        $i++;
    }
    $tobbkep_html .= '</ul></div>';
}
$szamla_html .= $tobbkep_html;





// A több képe thumbnailját hozza létre, egy adatbázis sorból
function get_tobbkep_thumb($sor_kepek, $i, $tabla) {

    require_once('../weblap.php');
    $tobbkep_html = '';
    $kep_utvonal = '../'.get_utvonal().csonkit($_SESSION['fajl']['tabla'], 4).'/'.$sor_kepek['utvonal'];
    if(is_file($kep_utvonal)){

        $felirat = utf8($sor_kepek['fajlnev']);
        $kep     = new Img($kep_utvonal, $felirat, 200, 200);

        if( !$felirat ) {
            $felirat = '<span class="i link">- Nincs leírás -</span>';
        }

        // Eszközkezelés engedélyeknél dátumot is kiírunk
        $datum = '';
        if(basename(self()) == 'eszkozkezeles.php' && isset($_GET['engedelyek'])){
            $datum = '';
            if($sor_kepek['kezdet']!='0000-00-00'){
                $datum .= str_replace('-', '.', $sor_kepek['kezdet']);
            }
            $datum .= ' - ';
            if($sor_kepek['veg']!='0000-00-00'){
                $datum .= str_replace('-', '.', $sor_kepek['veg']);
            }
        // Egyébként az insertDt
        }else{
            $datum .= substr( str_replace('-', '.', $sor_kepek['insert_dt']), 0, 10 );
        }
        
        $sor_kepek['fajlnev'] = htmlspecialchars($sor_kepek['fajlnev']);
        
        $azon    = 'kep'.$sor_kepek['recid'];

        // Csak az törölheti, aki feltöltötte
        $torles = '';
        $betolt = 'dokumentumok';
        if( isset($_GET['engedelyek']) ){
            $betolt = 'engedelyek';
        }
        if( $sor_kepek['loginnev'] == $_SESSION[get_db_tabla().'user']  ){
            $torles = '
                        <script>
                            dok_torol_gomb_'.$sor_kepek['recid'].' = \'<input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\\\''.$tabla.'\\\', \\\''.$betolt.'\\\', \\\'\\\', \\\''.$sor_kepek['recid'].'\\\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\\\'.torles_elott\\\').removeClass(\\\'torles_elott\\\').removeClass(\\\'kijelolve\\\');">\';
                        </script>
                        <span class="icon link" onclick="$(\'#'.$azon.'\').addClass(\'torles_elott\'); alert2(\'Biztos, hogy törli a(z) '.$sor_kepek['fajlnev'].' dokumentumot?\', dok_torol_gomb_'.$sor_kepek['recid'].');"><i class="material-icons" style="font-size: 2rem">delete</i></span>
                ';
        }
        

        $tobbkep_html .= nl2('
            <li id="'.$azon.'" title="'.$sor_kepek['megj'].'" data-id="'.$sor_kepek['recid'].'" data-i="'.$i.'">
                <script>gomb'.$azon.' = get_tobbkep_megj_szerk_gomb(\''.basename(__FILE__).'\', \''.$azon.'\', \''.$sor_kepek['recid'].'\');</script>
                <div class="kep">
                    <div>
                        <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés">
                            '.$kep -> getHtml().'
                        </a>
                    </div>
                </div>
                <div id="megj_'.$azon.'_felirat" class="felirat c" style="width: 15rem">
                    <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés">
                        '.wordwrap($felirat, 20, '<br>', 1).'
                    </a>
                </div>
                <div class="felirat">'.$sor_kepek['insert_usr'].'</div>
                <div class="felirat">'.$datum.'</div>
                <div class="maradek torles">'.$torles.'</div>
            </li>
        ');

    }
    return $tobbkep_html;

}
?>