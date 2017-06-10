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

if( $_POST['tabla'] ) {
    switch($_POST['tabla']) {
        case 'bejelentes':
            $tobbkep_tablanev = 'bejelentes_dok';
        break;
    }
}

switch(basename($_SERVER['PHP_SELF'])) {
    case 'bejelentes.php':
        $tobbkep_tablanev = 'bejelentes_dok';
    break;
}

//Képfeltöltés
if(isset($_POST['session_name'])){

    session_id($_POST['session_name']);
    session_start();
    require_once('../weblap.php');
    $utvonal = '../'.get_utvonal().'doc';

    if(!is_dir($utvonal)){
        mkdir($utvonal, 0777);
    }

    $utvonal = $utvonal.'/'.date('ym');

    if(!is_dir($utvonal)){
        mkdir($utvonal, 0777);
    }

    // JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
    if($_FILES['file']){
        $_FILES[$_POST['megnevezes']] = $_FILES['file'];
    }

	//Tiltott fájlformátumok kiszűrése, szitnén nincs feltöltés
	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
    if( in_array( $_FILES[ $_SESSION['fajl']['megnevezes'] ]['type'], $tiltott_fajlok ) ){
		exit;
	}

    switch($_FILES[ $_SESSION['fajl']['megnevezes'] ]['error']){

        case 1:
            echo 'Hiba! Túl nagy méretű fájl! Maximum '.ini_get('upload_max_filesize').' engedélyezett!';
        break;

        case 2:
            echo 'Hiba! Túl nagy méretű fájl!'; /* A HTML form-ban is megadható volna egy méretkorlát? */
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
            //Végleges képnév a teljes elérési útvonallal, kiterjesztéssel (hozzáadjuk az eredeti fájl kiterjesztését)
            $kepnev = $utvonal.'/'.time().'.'.kit($_FILES[ $_SESSION['fajl']['megnevezes'] ]['name']);

            //Fájlfeltöltés
            if(move_uploaded_file($_FILES[ $_SESSION['fajl']['megnevezes'] ]['tmp_name'], $kepnev)){

                /* Vízjelezés */
                //imagejpeg(logot_pecsetel($kepnev, '../elemek/vizjel.png', 50, 50, 100), $kepnev);

                //Letárolás adatbázisba is A kép neve nem tartalmazza a get_utvonal részt! Ha sikeres akkor adunk egy aoutputot a flashfile-nak
                $id = $adatbazis_kapcsolat -> i( $_SESSION['fajl']['tabla'], array($_SESSION['fajl']['oszlop'], 'kepnev', 'megj'), array($_SESSION['fajl']['termek_id'], str_replace('../'.get_utvonal(), '', $kepnev), basename($kepnev) ) );
                $sor_kepek = $adatbazis_kapcsolat->s('*', $_SESSION['fajl']['tabla'], 'recid='.$id);
                $sor_kepek = $sor_kepek[0];
                echo get_tobbkep_thumb($sor_kepek);
            }
        break;
    }

    $weblap -> leallit();
    exit;
}

require_once('../weblap.php');

//Sorrendezés
if(isset($_POST['kepek_sorrend'])){

    //Mindig egy undefined-el kezdődik ezt le kell venni
    $_POST['kepek_sorrend'] = substr($_POST['kepek_sorrend'], 9, strlen($_POST['kepek_sorrend']));

    $sorrend_tomb = explode(';', $_POST['kepek_sorrend']);
    $i = 0;
    foreach($sorrend_tomb as $kepnev){
        $parancs = 'UPDATE `'.get_db_tabla().$tobbkep_tablanev.'` SET `sor`=\''.$i.'\' WHERE `kepnev`=\''.$kepnev.'\' AND `'.$_POST['azonosit_oszlop'].'`=\''.$_SESSION['fajl']['termek_id'].'\'';
        mysqli_query($db_kapcs, $parancs);
        $i++;
    }

    $weblap -> leallit();
    exit;
}

//Képfelírat lekérdezése
if($_POST['get_kepfelirat']){
    $felirat = mysqli_query($db_kapcs, 'SELECT `megj` FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['get_kepfelirat'].'\'');
    $felirat = mysqli_fetch_assoc($felirat);
    echo $felirat['megj'];

    $weblap -> leallit();
    exit;
}

//Megjegyzés átírása
if(isset($_POST['megj_atir'])){

    $parancs = 'UPDATE `'.get_db_tabla().$tobbkep_tablanev.'` SET `megj`=\''.$_POST['szoveg'].'\' WHERE `recid`=\''.$_POST['id'].'\'';
    mysqli_query($db_kapcs, $parancs);
//echo $adatbazis_kapcsolat->gp() . '<br>';
    $felirat = mysqli_query($db_kapcs, 'SELECT `megj` FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['id'].'\'');
    $felirat = mysqli_fetch_assoc($felirat);
    echo $felirat['megj'];

    if(!$felirat['megj']){
        echo '<span class="i link">- Nincs leírás -</spam>';
    }

    $weblap -> leallit();
    exit;
}

//Kép törlése
if(isset($_POST['tobbkep_torol'])){

    $kepnev = mysqli_query($db_kapcs, 'SELECT `kepnev` FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['tobbkep_torol'].'\'');
    $kepnev = mysqli_fetch_assoc($kepnev);

    $parancs = 'DELETE FROM `'.get_db_tabla().$tobbkep_tablanev.'` WHERE `recid`=\''.$_POST['tobbkep_torol'].'\'';
    mysqli_query($db_kapcs, $parancs);

    kep_torol('../'.get_utvonal().$kepnev['kepnev']);

    $weblap -> leallit();
    exit;
}



/** Tartalom */

require_once '../class/html/class.Img.php';

//Sessionok beállítása a feltöltéshez:
$megn = 'tobbkep'.$_GET['id'];
$_SESSION['fajl']['megnevezes'] = $megn;                    //A $FILE változó neve, egyedi legyen
$_SESSION['fajl']['nev']        = $sor['nev'];              //Fájlnév, a terméknévből származtatva (kiterjesztés nem kell, fajlnev_keszit is lesz később így ez sem kell)
$_SESSION['fajl']['konyvtar']   = $megnevezes;              //Könyvtár, ahol a képeket gyűjtjük (eredeti termék könyvtára)
$_SESSION['fajl']['tabla']      = get_tobbkep_tabla();      //Képek tábla, amellyel összekapcsoljuk a termékkel
$_SESSION['fajl']['oszlop']     = get_tobbkep_oszlopnev();  //Képek tábla oszopa, amellyel összekapcsoljuk a termékkel
$_SESSION['fajl']['termek_id']  = $_GET['id'];              //A termék azonosítója, amivel összekapcsoljuk

/* Korlátozás csak képre */
if(get_tobbkep_csakkep() == 'true'){
    $csakkep  = ' accept="image/*"';
    $csakkep2 = ' && fajlok[i].type.match(/^image\//)';
}

//Fájlfeltöltéskor milyen paraméterek menjenek át
$parameterek = "'session_name':'".session_id()."', 'megnevezes':'".$megn."', 'sorid':'".$_GET['id']."'";

//Ha minden fájl feltöltése kész, mi történjen (ide kéne egy jó ajax hívás)
$minden_kesz = '';
if(isset($_GET['modosit'])){
    //$minden_kesz = 'document.location.reload(true);';
}

$sorrend_ujkep       = fajlbol_olvas('../sablon/admin/tobbkep_ujkep.html', true);
$sorrend_ujkep_mit   = array('{MEGN}',  '{PHP_FAJL}',                     '{CSAK_KEP}', '{CSAK_KEP2}',  '{PARAMETEREK}', '{MINDEN_KESZ}'    );
$sorrend_ujkep_mivel = array($megn,     'tobb_kep.php'/*self() nem jó!*/, $csakkep,     $csakkep2,      $parameterek,    $minden_kesz       );

$tobbkep_html  = '';
$tobbkep_html .= str_replace($sorrend_ujkep_mit, $sorrend_ujkep_mivel, $sorrend_ujkep);

$i     = 0;
$kepek = $adatbazis_kapcsolat -> select('*', $tobbkep_tablanev, get_tobbkep_oszlopnev().'='.$_GET['id'], 'sor');
$tobbkep_html .= '<div><ul id="sorrendezes_tobbkep" title="'.$_SESSION['fajl']['oszlop'].'">';
foreach($kepek as $sor_kepek){
    $tobbkep_html .= get_tobbkep_thumb($sor_kepek);
}
$tobbkep_html .= '</ul></div>';

$szamla_html .= $tobbkep_html;






/* A több képe thumbnailját hozza létre, egy adatbázis sorból */
function get_tobbkep_thumb($sor_kepek){

    require_once('../weblap.php');

    $tobbkep_html = '';
    $kep_utvonal = '../'.get_utvonal().$sor_kepek['kepnev'];
    if(is_file($kep_utvonal)){

        $felirat = utf8($sor_kepek['megj']);
        $kep     = new Img($kep_utvonal, $felirat, 200, 200);

        if( !$felirat ) {
            $felirat = '<span class="i link">- Nincs leírás -</span>';
        }

        $azon    = 'kep'.$sor_kepek['recid'];
        $megj_szerkesztes_onclick = ' onclick="get_tobbkep_megj(\''.basename(__FILE__).'\', \''.$sor_kepek['recid'].'\', \'megj_atir'.$azon.'\'); alert2(\'Megjegyzés szerkesztése:\', gomb'.$azon.');"';
        /* title="'.$sor_kepek['kepnev'].'" */
        $tobbkep_html .= nl2('
            <li id="'.$azon.'" title="">
            <script>gomb'.$azon.' = get_tobbkep_megj_szerk_gomb(\''.basename(__FILE__).'\', \''.$azon.'\', \''.$sor_kepek['recid'].'\');</script>
            <!--<div class="torol" onclick="tobbkep_torol(\''.basename(__FILE__).'\', \''.$sor_kepek['recid'].'\', \''.$azon.'\');">
                <img alt="" class="dn" src="../elemek/admin/torol_hover.png" />
            </div>-->
            <div class="kep">
                <div>'.$kep -> getHtml().'</div>
            </div>
            <div id="megj_'.$azon.'_felirat" class="felirat f12 link c"'.$megj_szerkesztes_onclick.'>'.$felirat.'</div>
            <div class="nagyit">
                <a href="'.$kep_utvonal.'" class="light" rel="album[]" target="_blank">
                    <i class="material-icons">file_download</i>
                </a>
            </div>
        ');
        $tobbkep_html .= '</li>';

    }
    return $tobbkep_html;

}


//Beragadt képek törlése (mivel egy bejegyzés törlésekor még magát a további fotókat nem töröljük, így ezt utólag számítjuk ki, lefuttatva egy ilyen karbantartást)
$tobbkep = $adatbazis_kapcsolat -> select(get_tobbkep_oszlopnev().' kepnev', get_tobbkep_tabla());
foreach($tobbkep as $sor_tobbkep){

    $van_bejegyzses = $adatbazis_kapcsolat->s('recid', $megnevezes, 'recid='.$sor_tobbkep[get_tobbkep_oszlopnev()]);
    if(!$van_bejegyzses){

        //eredeti fájl törlése
        if(is_file('../'.get_utvonal().$sor_tobbkep['kepnev'])){
            unlink('../'.get_utvonal().$sor_tobbkep['kepnev']);
        }

        //ha van kiterjesztése
        if(strrpos($sor_tobbkep['kepnev'], '.')>0){
            remover('../'.get_utvonal().substr($sor_tobbkep['kepnev'],0,strrpos($sor_tobbkep['kepnev'],'.')).'_*');
        }else{
            remover('../'.get_utvonal().$sor_tobbkep['kepnev'].'_*');
        }
    }
}
?>
