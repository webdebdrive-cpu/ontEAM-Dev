<?php
/**
    FONTOS OLVASD EL!

    1. ITT NEM SZERPELHET A: require_once('../weblap.php'); A session_start() miatt, ami a képfeltöltéshez kell
    2. A $tobbkep_tablanev = 'kepek'; változót muszáj itt megadni. Ennek értéke az alap PHP fájlban, az if($tobb_kep){} feltételnél megadott $tobbkep_tablanev-vel azonos. Lényegében azt az adatbázis táblát kell megadni, amiben az adatok letárolásra kerülnek.

    HA TÖBB ILYEN FELTÖLTÉSI PANEL IS KELL (azaz nem csak egy "Több kép" lenne hanem több is), akkor a következőket lépésekre lesz szükség:
        - tobb_kep.php-ból csinálj klónokat, pl. tobb_kep2.php (ugyanis ez lesz meghívva ajax-al, fájl feltöltésekor)

 */

require_once('../class/html/class.Img.php');

$php_tablanev     = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
$tobbkep_tablanev = $php_tablanev.'_dok';
$archivum = false;
if( substr($php_tablanev, strlen($php_tablanev)-5) == '_arch' ){
    $tobbkep_tablanev = substr($megnevezes, 0, strlen($megnevezes)-5).'_dok_arch';
    $archivum = true;
}
if( $_POST['tabla'] ) {
    $tobbkep_tablanev = $_POST['tabla'].'_dok';
}

// Eszközkezelésnél az engedélyeknek van egy külön tálbája
if(basename($_SERVER['PHP_SELF']) == 'eszkozkezeles.php' && isset($_GET['engedelyek'])){
    $tobbkep_tablanev = 'eszkozkezeles_eng_dok';
}

// Legutóbb feltötött fájl adatainak lekérése, tipus kiválasztásához
if (isset($_POST['get_feltoltott_fajl'])) {
    
    require_once('../weblap.php');
    $dokumentum = $adatbazis_kapcsolat->s2('recid fajlnev', $_POST['tabla'].'_dok', "`{$_POST['tabla']}_id`='{$_POST['id']}'", 'recid desc', '1');

    echo nl2('
        Legutóbb feltöltöt fájl: '.$dokumentum['fajlnev'].'
        <br>Válassza ki a dokumentum tipusát:
        <br><input type="hidden" name="pop_recid" id="pop_recid" value="'.$dokumentum['recid'].'">
        ');
    
    $weblap -> leallit();
    exit;
}

// Dokumentum tipus letárolása
if (isset($_POST['dokumentum_tipus_valaszt'])) {
    
    require_once('../weblap.php');
    $sql = $adatbazis_kapcsolat->u($_POST['tabla'].'_dok', array('tipus'), array($_POST['tipus']), "`recid`='{$_POST['recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    $weblap -> leallit();
    exit;
}

// Képfeltöltés
if( isset($_POST['megnevezes']) && !$archivum ){
    
    // Ha a megnevezés csak annyi, hogy javaslattetel_popup, akkor munkalap javaslattétel popuján töltünk fel képet, ekkor másképp kell eljárni
    if($_POST['megnevezes']=='javaslattetel_popup'){

        require_once('../weblap.php');
        $konyvtar_db_nev = csonkit($_POST['tabla'], 4);
        $utvonal = '../'.get_utvonal().'munkalap';
        $ikt_szam = $adatbazis_kapcsolat->s2('recid ikt_szam', 'munkalap', "`recid` = '".$_POST['sorid']."'", '', '1');
        if( $ikt_szam ) {
            $utvonal = $utvonal.'/'.str_replace('/', '_', $ikt_szam['ikt_szam']);
            if( !is_dir($utvonal) ) {
                mkdir($utvonal, 0777);
            }
        }

        // JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
        if($_FILES['file']){
            $_FILES[ $_POST['megnevezes'] ] = $_FILES['file'];
        }

        $sor_kepek          = 'munkalap';
        $tobbkep_tablanev   = 'munkalap_dok';

    // Általános eset
    }else{

        require_once('../weblap.php');

        // utolsó 4 karakter a "_dok", ezt levesszük
        $konyvtar_db_nev = csonkit($_POST['tabla'], 4);
        $utvonal = '../'.get_utvonal().$konyvtar_db_nev;

        if(!is_dir($utvonal)){
            mkdir($utvonal, 0777);
        }

        $ikt_szam = $adatbazis_kapcsolat->s2('recid ikt_szam', $_POST['konyvtar'], "`recid` = '".$_POST['sorid']."'", '', '1');
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
    }

    // Ha nincs dokumentum kezelés jogunk, akkor feltöltést blokkoljuk
    if( !dokumentum_kezeles_jog($_POST['tabla'], $_POST['sorid']) ){
        exit;
    }

	// Tiltott fájlformátumok kiszűrése, szintén nincs feltöltés
	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
    if( in_array( $_FILES[ $_POST['megnevezes'] ]['type'], $tiltott_fajlok ) ){
        echo 'Hiba!Ilyen formátumú fájl feltöltése tiltott, a fájlfeltöltés nem jött létre!';
        $weblap -> leallit();
		exit;
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
                if(move_uploaded_file($_FILES[ $_POST['megnevezes'] ]['tmp_name'], $kepnev)){

                    $apa_tabla = csonkit($_POST['tabla'], 4);
                    
                    // Tipus választó popup-ot így hívjuk meg, hogy egy tipus_beallit szringgel kezdjük a visszaküldött adatokat
                    if( tipusos_dokumentumok( $apa_tabla ) ){
                        echo 'tipus_beallit';
                        
                        // Illetve ekkor megjegyzéshez is bekerül a fájl neve
                        addMegj( $apa_tabla, $_POST['sorid'], 'Dokumentum hozzáadva: '.$fajlnev.'.'.$kit);
                    }
                    
                    // Projektkezelés menüpont alatt kiírjuk, hogy sikeres feltöltés, és hogy léptessük-e tovább? Ezt a popup hívást jelezzük itt.
                    if( $_POST['tabla']=='projektkezeles_dok' ){
                        echo 'leptessuk_e_tovabb';
                    }
                    
                    $kep_tort = str_replace('../'.get_utvonal().$konyvtar_db_nev.'/', '', $kepnev);
                    if( $_POST['ellenorzes_kihagy'] == 'false' ) {
                        // Letárolás adatbázisba is. A kép neve nem tartalmazza a get_utvonal részt! Ha sikeres akkor adunk egy outputot a flashfile-nak
                        $id = $adatbazis_kapcsolat -> i( $_POST['tabla'],
                                                        array($_POST['oszlop'], 'utvonal',  'fajlnev',         'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                           ),
                                                        array($_POST['sorid'],  $kep_tort,  $fajlnev.'.'.$kit, $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev'] )
                                                    );

                        $sor_kepek = $adatbazis_kapcsolat->s2('*', $_POST['tabla'], "`recid`='{$id}'");

                        $osszes_sor = $adatbazis_kapcsolat->s('recid', $_POST['tabla'], '');

                        echo get_tobbkep_thumb($sor_kepek, count($osszes_sor));
                    } else {
                        $sor_kep = $adatbazis_kapcsolat->s($_POST['oszlop'], $_POST['tabla'], "`".$_POST['oszlop']."` = '".$_POST['sorid']."' AND `utvonal` = '{$kep_tort}'", '', '0,1');

                        if( !$sor_kep ) {
                            $id = $adatbazis_kapcsolat -> i( $_POST['tabla'],
                                                                array($_POST['oszlop'], 'utvonal',  'fajlnev',            'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                            ),
                                                                array($_POST['sorid'],  $kep_tort,  $fajlnev.'.'.$kit,    $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev']  )
                                                            );
                        }

                        echo 'frissit';
                    }

                    // Végül a szülő táblában a dok_db karbantartása
                    $dokumentumok_szama = count( $adatbazis_kapcsolat->s('recid', $_POST['tabla'], "`{$_POST['oszlop']}`='{$_POST['sorid']}'") );
                    $adatbazis_kapcsolat->u($konyvtar_db_nev, array('dok_db'), array($dokumentumok_szama), "`recid`='{$_POST['sorid']}'");

                }
            }
        break;
    }

    $weblap -> leallit();
    exit;
}

require_once('../weblap.php');



// Tartalom
require_once '../class/html/class.Img.php';

// Sessionok beállítása a feltöltéshez:
$megn = 'tobbkep'.$_GET['id'];
//$_SESSION['fajl']['megnevezes'] = $megn;                    //A $FILE változó neve, egyedi legyen
//$_SESSION['fajl']['nev']        = $sor['nev'];              //Fájlnév, a terméknévből származtatva (kiterjesztés nem kell, fajlnev_keszit is lesz később így ez sem kell)
//$_SESSION['fajl']['konyvtar']   = $megnevezes;              //Könyvtár, ahol a képeket gyűjtjük (eredeti termék könyvtára)
//$_SESSION['fajl']['tabla']      = $tobbkep_tablanev;        //Képek tábla, amellyel összekapcsoljuk a termékkel
//$_SESSION['fajl']['oszlop']     = get_tobbkep_oszlopnev();  //Képek tábla oszopa, amellyel összekapcsoljuk a termékkel
//$_SESSION['fajl']['termek_id']  = $_GET['id'];              //A termék azonosítója, amivel összekapcsoljuk

// Korlátozás csak képre
$eng_kit = '';
if(get_tobbkep_csakkep()){
    $eng_kit = ' accept="'.get_tobbkep_csakkep().'"';
}

// Fájlfeltöltéskor milyen paraméterek menjenek át
$parameterek = nl2("
                    'megnevezes'    : '".$megn."',
                    'konyvtar'      : '".$megnevezes."',
                    'tabla'         : '".$tobbkep_tablanev."',
                    'oszlop'        : '".get_tobbkep_oszlopnev()."',
                    'sorid'         : '".$_GET['id']."'
                    ");

// Ha minden fájl feltöltése kész, mi történjen (ide kéne egy jó ajax hívás)
$gomb_neve   = 'Tallózás';
$minden_kesz = '';
if($tobbkep_tablanev=='eszkozkezeles_eng_dok'){
    $minden_kesz = 'kapcsolodoSzerkeszt(\'eszkozkezel_engedely\');';
    $gomb_neve   = 'Új engedély feltöltése';
} else {
    $minden_kesz = 'szamlaBetolt(_get(\'id\'), baseName(location.href) + \'.php\', \'dokumentumok\');';
}

// Típus oszlop
$multiple       = ' multiple=""';
$tipus_fejlec   = '';
$apa_tabla      = csonkit($tobbkep_tablanev, 4);
if( tipusos_dokumentumok($apa_tabla) ){
    $tipus_fejlec   = '<div class="felirat">Típus</div>';
    $multiple       = '';
}

// Van-e dokumentum kezelés jogunk?
$nincs_torles = '';
if(!dokumentum_kezeles_jog($tobbkep_tablanev, $_GET['id'])){
    $nincs_torles = 'nincs törlés';
}
if( !$nincs_torles ){
    $sorrend_ujkep       = fajlbol_olvas('../sablon/admin/tobbkep_ujkep.html', true);
    $sorrend_ujkep_mit   = array('{GOMB_NEVE}', '{MEGN}',  '{PHP_FAJL}',                     '{ENG_KIT}', '{PARAMETEREK}', '{MINDEN_KESZ}', '{MULTIPLE}');
    $sorrend_ujkep_mivel = array($gomb_neve,    $megn,     'tobb_kep.php'/*self() nem jó!*/, $eng_kit,     $parameterek,    $minden_kesz,   $multiple   );
}

$tobbkep_html  = '';
if( !$archivum ){
    $tobbkep_html .= str_replace($sorrend_ujkep_mit, $sorrend_ujkep_mivel, $sorrend_ujkep);
}

$i     = 0;
$kepek = $adatbazis_kapcsolat -> s('*', $tobbkep_tablanev, "`".get_tobbkep_oszlopnev()."`='{$_GET['id']}'", 'sor');

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
                                    '.$tipus_fejlec.'
                                    <div class="felirat">Dátum</div>
                                    <div class="maradek torles"></div>
                                </li>
                                ');

    $i = 1;
    foreach($kepek as $sor_kepek) {
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, $tobbkep_tablanev, $nincs_torles);

        $i++;
    }
    $tobbkep_html .= '</ul></div>';
}
$szamla_html .= $tobbkep_html;





// A több képe thumbnailját hozza létre, egy adatbázis sorból
function get_tobbkep_thumb($sor_kepek, $i, $tabla, $nincs_torles) {

    global $archivum;
    global $tobbkep_tablanev;
    global $adatbazis_kapcsolat;
    
    if(!$tobbkep_tablanev){
        $tobbkep_tablanev = $_POST['tabla'];
    }

    $javaslattetel = '';
    if($_POST['megnevezes']=='javaslattetel_popup'){
        $tabla = 'munkalap_dok';
        $javaslattetel = 'javaslattetel';
    }

    require_once('../weblap.php');
    $tobbkep_html = '';

    $sor_kepek['utvonal'] = stripslashes(htmlspecialchars_decode($sor_kepek['utvonal']));
    $csonkit = 4;
    if($archivum) {
        $csonkit = 9;
    }
    $kep_utvonal = '../'.get_utvonal().csonkit($tobbkep_tablanev, $csonkit).'/'.$sor_kepek['utvonal'];
// A fájl sora akkor is jelenjen meg, ha már nem létezik, hogy a dev-be átmásolt adatok esetén ha nincs ott a fájl maga, attól még jelenjen meg.
//    if(is_file($kep_utvonal)){
    
        $kep_reszek = pathinfo($kep_utvonal);
        $ikon_utvonal = '../elemek/ikonok/'.strtolower($kep_reszek['extension']).'.png';
        $felirat = utf8(stripslashes($sor_kepek['fajlnev']));
        $kep     = new Img($ikon_utvonal, $felirat, 200, 200);

        // A fájl linkjében a szóközöket %20-ra cseréljük, hogy dupla szóközös fájllal is működjön.
        $kep_utvonal = str_replace(' ', '%20', htmlspecialchars($kep_utvonal));

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
            $datum .= datum_kiir($sor_kepek['insert_dt']);
        }

        $sor_kepek['fajlnev'] = htmlspecialchars($sor_kepek['fajlnev']);

        $id_kieg = '';
        if($_POST['megnevezes']=='javaslattetel_popup'){
            $id_kieg = '_jt_popup';
        }
        $azon = 'kep'.$id_kieg.$sor_kepek['recid'];

        // Csak az törölheti, aki feltöltötte (ha archivum, akkor meg már senki)
        $torles = '';
        $betolt = 'dokumentumok';
        if( isset($_GET['engedelyek']) ){
            $betolt = 'engedelyek';
        }
        if( $sor_kepek['loginnev'] == $_SESSION[get_db_tabla().'user'] && !$archivum ){
            
            // Projektkezelésenél még a státusz/dokumentum faját is figyeljük
            $statusz_tipus_figyelmeztetes = '';
            if( $tobbkep_tablanev == 'projektkezeles_dok' ){
                
                $prk = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$_GET['id']}'");
                if( $prk === false ) { $db_error = true; }

                switch( $prk['stat_id'] ){
                    case PRKEZEL_STAT_ELOKESZITES_ALATT:
                        $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}'");
                        if( $van_doksi === false ) { $db_error = true; }
                        if( count($van_doksi)==1 ){
                            $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes dokumentum!<br>Előbb csatoljon fel egy másikat!';
                        }
                    break;
                    case PRKEZEL_STAT_FOLYAMATBAN:
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_SZERZODES){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'");
                            if( $van_doksi === false ) { $db_error = true; }
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Szerződéses dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                    break;
                    case PRKEZEL_STAT_KESZ:
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_SZERZODES){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'");
                            if( $van_doksi === false ) { $db_error = true; }
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Szerződéses dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_ATADASI){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_ATADASI."'");
                            if( $van_doksi === false ) { $db_error = true; }
 
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Átadási. jk. dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                    break;
                    case PRKEZEL_STAT_LEZART:
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_SZERZODES){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'");
                            if( $van_doksi === false ) { $db_error = true; }
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Szerződéses dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_ATADASI){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_ATADASI."'");
                            if( $van_doksi === false ) { $db_error = true; }
 
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Átadási. jk. dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                        if($sor_kepek['tipus']==EL_DOK_TIPUS_GAR_BEJAR){
                            $van_doksi = $adatbazis_kapcsolat->s('recid', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='".EL_DOK_TIPUS_GAR_BEJAR."'");
                            if( $van_doksi === false ) { $db_error = true; }
                            if( count($van_doksi)==1 ){
                                $statusz_tipus_figyelmeztetes = 'Ebben a státuszban nem törölhető az összes Gar. bejárási jk. dokumentum!<br>Előbb csatoljon fel egy másikat!';
                            }
                        }
                    break;
                }
            }
            
            if($statusz_tipus_figyelmeztetes){
                $torles = '<span class="icon link" onclick="alert2(\''.$statusz_tipus_figyelmeztetes.'\', \'\', \'Hiba\');"><i class="material-icons" style="font-size: 2rem">delete</i></span>';
            
            // Tényleges törlés gomb működése
            }else{
                $torles = '
                            <script>
                                dok_torol_gomb_'.$sor_kepek['recid'].' = \'<input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\\\''.$tabla.'\\\', \\\''.$betolt.'\\\', \\\''.$javaslattetel.'\\\', \\\''.$sor_kepek['recid'].'\\\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\\\'.torles_elott\\\').removeClass(\\\'torles_elott\\\').removeClass(\\\'kijelolve\\\');">\';
                            </script>
                            <span class="icon link" onclick="$(\'#'.$azon.'\').addClass(\'torles_elott\'); alert2(\'Biztos, hogy törli a(z) '.$sor_kepek['fajlnev'].' dokumentumot?\', dok_torol_gomb_'.$sor_kepek['recid'].');"><i class="material-icons" style="font-size: 2rem">delete</i></span>
                    ';
            }
            
        }
        
        // Törlés lehetőségét mégis tiltjuk, ha nincs dokumentum kezelés jogunk
        if( $nincs_torles ){
            $torles = '';
        }

        // Típus oszlop
        $tipus_oszlop = '';
        $apa_tabla    = csonkit($tabla, 4);
        if( tipusos_dokumentumok( $apa_tabla ) ){
            $tipus_oszlop = '<div class="felirat">'.erteklista(ERTEKLISTA_DOK_TIPUS, $sor_kepek['tipus']).'</div>';
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
                        '.mb_wordwrap($felirat, 20, '<br>', 1, 'UTF-8').'
                    </a>
                </div>
                <div class="felirat">'.$sor_kepek['insert_usr'].'</div>
                '.$tipus_oszlop.'
                <div class="felirat">'.$datum.'</div>
                <div class="maradek torles">'.$torles.'</div>
            </li>
        ');

//    }
    return $tobbkep_html;

}

// Általános függévény, ami eldönti adott tábla alapján,hogy van-e oda kezelés jogunk-e vagy sem.
// Ha nincs, akkor nincs törlés ikon a már feltöltött dokumentum sorok végén, illetve nincs feltöltés lehetőség sem,
// sőt feltöltés résznél is blokkolunk.
function dokumentum_kezeles_jog($tabla, $id) {
    
    global $adatbazis_kapcsolat;
    
    // Projektigénylések elutasítva és jóváhagyva státuszainál
    if( $tabla == 'projektigenyles_dok' ){
        $pri = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$id}'");
        if( $pri['stat_id']==PRIGENY_STAT_ELUTASITVA || $pri['stat_id']==PRIGENY_STAT_JOVAHAGYVA ){
            return false;
        }
    }
    // Projektkezeléskor ha nincs hozzá jogunk
    if( $tabla == 'projektkezeles_dok' ) {
        if(!_jog(JOG_PRKEZEL_KEZEL)) {
            return false;
        }
    }
    
    return true;
}

// WordWrap kiegészítő függvény, ékezetes tartalommal is működjön
function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false, $charset = null) {
    if ($charset === null) $charset = mb_internal_encoding();

    $pieces = explode($break, $str);
    $result = array();
    foreach ($pieces as $piece) {
      $current = $piece;
      while ($cut && mb_strlen($current) > $width) {
        $result[] = mb_substr($current, 0, $width, $charset);
        $current  = mb_substr($current, $width, 2048, $charset);
      }
      $result[] = $current;
    }
    return implode($break, $result);
}
?>
