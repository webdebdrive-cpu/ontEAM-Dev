<?php
require_once('../weblap.php');

if( !_jog(JOG_BESZERZ_IGENYLESEK_OLVAS) && !_jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) && !_jog(JOG_BESZ_SZALL_OLVAS) ) {
    if( !$_SESSION[get_db_tabla() . 'user'] ) {
        $weblap -> leallit();
        header('Location: index.php');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $weblap -> leallit();
    exit('Nincs megfelelő jogosultság az oldal eléréséhez!');
}

// Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $teszteles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'teszt eredményt';               // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = 0;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

if( !$_GET['ajax'] ) {
    echo '<div id="szamlaBetolt">';
}

// Az adatlap adatait már a legelején lekérjük
if( $_GET['id'] ){
    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
}
if( $sor['dok_db'] ) {
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if( $sor['megj_db'] ) {
    $megj_db = ' ('.$sor['megj_db'].')';
}

if( isset($_GET['modosit']) ) {
    // Számla fülek
    $ful_tomb = array(
        'modosit'       => 'Adatlap',
    );
    $fulek = '';
    foreach($ful_tomb as $key => $value) {
        
        $active = 'active';
        if( isset($_GET[$key]) ) {
            $active = ' class="active"';
        }

        $fulek .= '<a href="javascript: szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
    }

    $szamla_html .= str_replace(
        array('{FULEK}'),
        array( $fulek  ),
        fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
    );
}

$js_submit_elott = 'return form_ell_admin();';

// Gombok kirajzolása minden oldalra
$gombok = '';
// Js, a törlés gombhoz
$gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

if( _jog(JOG_BESZERZ_IGENYLESEK_OLVAS) || _jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) || _jog(JOG_BESZ_SZALL_OLVAS) ) {
    $gombok = nl2('
        <div class="form_kuldes">
            <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
            <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
        </div>
    ').$gombok;
}

$muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
    if( $_COOKIE['csuszkaSzelesseg'] ) {
        $muv_style = ' style="width: calc(100% - '.(($_COOKIE['csuszkaSzelesseg'] + 16)/10).'rem);"';
    }
}

// Műveletek box
$gombok = nl2('
    <div class="muveletek"'.$muv_style.'>
        '.$gombok.'
    </div>
');

// Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
if( isset($_GET['id_hidden']) ) {
    $_GET['id'] = $_GET['id_hidden'];
}

$szamla_html .= nl2('
    <table class="csikos">
    <tr>
        <th>Modul</th>
        <th>Rekord</th>
        <th>Rekord státusz</th>
        <th>Szálloda</th>
        <th>Tesztelés eredménye</th>
        <th>Fájlok</th>
        <th>Felhasználó</th>
        <th>Dátum</th>
    </tr>
');

$tesztelesek = $adatbazis_kapcsolat -> s('*', 'teszteles', "", 'insert_dt desc');
foreach ($tesztelesek as $teszteles) {

    $fajlok_html = '';
    $kepek = $adatbazis_kapcsolat -> s('utvonal fajlnev', 'teszteles_dok', "`teszteles_recid`='{$teszteles['recid']}'");
    foreach ($kepek as $kep) {
        $fajlok_html .= nl2('
            <a href="../'.get_utvonal().$kep['utvonal'].'" class="light" target="_blank" rel="album_'.$teszteles['recid'].'">'.$kep['fajlnev'].'</a>
            <br>
        ');
    }

    $szamla_html .= nl2('
        <tr>
            <td>'.$teszteles['modul_nev'].'</td>
            <td>'.$teszteles['modul_recid'].'</td>
            <td>'.getStatText($teszteles['modul'], $teszteles['stat_id']).'</td>
            <td>'.getSzallodaNev($teszteles['telephely_id']).'</td>
            <td>'.nl2br2($teszteles['text']).'</td>
            <td>'.$fajlok_html.'</td>
            <td>'.$teszteles['insert_usr'].'</td>
            <td>'.datum_kiir($teszteles['insert_dt']).'</td>
        </tr>
');
}
$szamla_html .= nl2('
    </table>
');

if( $_GET['ajax'] ) {
    echo json_encode(array(
        'tartalom'  => $szamla_html,
        'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
    ));
} else {
    echo $szamla_html;
}

if( $tobb_kep ) {
    require_once('tobb_kep.php');
}


if( !$_GET['ajax'] ) {
    echo '</div>';
}

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}