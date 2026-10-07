<?php
require_once('../weblap.php');

if( !_jog(JOG_BESZ_SZALL_OLVAS) ) {
    if(!$_SESSION[get_db_tabla().'user']){
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
$akt_tomb               = $szallito_beszerzes_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'beszerzés szállítót';            // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = 1;
if( $tobb_kep ) {

    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Szállítókód ellenőrzése (nem foglat-e, helyes-e?) illetve a mailcím mező ellenőrzése
if( isset($_POST['szallitokod_ell']) ) {

    $_POST['id'] = trim($_POST['id']);

    // Ha van ID-nk, de nincs get_id-nk (ha nincs get_id == false, akkor új felvitelben vagyunk)
    if( $_POST['id'] && $_POST['get_id'] != 'false' ) {

        // Hossz ellenőrzése
        if( strlen($_POST['id']) != SZALLITOKOD_HOSSZ ) {
            echo json_encode(array(
                'hiba'  => 'Az oracle kód hossza '.SZALLITOKOD_HOSSZ.' karakter legyen!',
                'title' => 'Hiba',
            ));
    
            $weblap->leallit();
            exit();
        }
        
        // Numerikus-e?
        if( !is_numeric($_POST['id']) ) {
            echo json_encode(array(
                'hiba'  => 'A szállítókód csak számokat tartalmazhat!',
                'gomb'  => '<input type="button" value="Ok" class="gomb" onclick="szurkit_zar(\'mégsem mehet mutat\'); alert2();">',
                'title' => 'Hiba',
            ));
    
            $weblap->leallit();
            exit();
        }

    }
    // Innentől olyan ellenőrzések jönnek, amit új felvitelkor és módosításnál is ellenőrizni kell

    // Oracle kód szabad-e még?
    if( !$_POST['id_ell_nelkul'] && $_POST['id'] ) {
        $foglalt_id = $adatbazis_kapcsolat->s('id', 'szallito_beszerzes', "`id`='{$_POST['id']}' AND `recid`!='{$_POST['get_id']}'");
        if( $foglalt_id ) {
            echo json_encode(array(
                'hiba'  => 'Ez az oracle kód már létezik a beszerzés szállítók között ezen a néven: '.$_POST['id'].'<br>Folytatja?',
                'gomb'  => '<input type="button" class="gomb zold" value="Igen" onclick="szallitokod_ell(\'-id ellenőrzés nélkül-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">',
                'title' => 'Kérdés',
            ));
    
            $weblap->leallit();
            exit();
        }
    }

    // Szállító neve nem lehet foglalt
    $foglalt = $adatbazis_kapcsolat -> s2('recid', 'szallito_beszerzes', "`nev`='".addslashes(htmlspecialchars($_POST['nev']))."' AND `recid`!='{$_POST['get_id']}'");
    if( $foglalt ) {
            echo json_encode(array(
                'hiba'  => 'Ilyen nevű beszállító már létezik a beszerzés szállítók között!',
                'title' => 'Hiba',
            ));
    
            $weblap->leallit();
            exit();
    }

    // E-mail címek validálása
    $hibauzenet = is_valid_mail_tobb($_POST['email'], 'hibaüzenettel');
    if( $hibauzenet ) {
        echo json_encode(array(
            'hiba'  => $hibauzenet,
            'title' => 'Hiba',
        ));

        $weblap->leallit();
        exit();
    }

    echo json_encode(array(
        'siker' => '1'
    ));

    $weblap->leallit();
    exit();

}

// Megjegyzések kiíratása
if (isset($_POST['megj_kiir'])) {

    echo json_encode( array('html' => megj_kiir($megnevezes, $_POST['id'], $_POST['rendszer'])) );

    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        // A fizetési határidő kódjának szöveges megfelelőjéhez
        require_once('selectek.php');
        $hat_ido_nevek = array_column($select_hat_ido, 'name', 'value');

        $sql = 'SELECT
                `recid` as \'Szállító Id\',
                `aktiv` as \'Aktív\',
                `id` as \'Oracle kód\',
                `nev` as \'Név\',
                `isz` as \'Irsz\',
                `varos` as \'Város\',
                `utca` as \'Utca\',
                `lcim` as \'Lev.cím\',
                `szakter` as \'Szakterület\',
                `bank` as \'Bankszámlaszám\',
                (SELECT `nev` FROM `'.get_db_tabla().'eszkozigenyles_fiz_mod` WHERE `recid`=`'.get_db_tabla().'szallito_beszerzes`.`fiz_mod`) as \'Fizetési mód\',
                `hat_ido` as \'Fizetési határidő\',
                IF(`nyelv`=\'\', \'HUN\', `nyelv`) as \'Nyelv\',
                `knev` as \'Kapcs.név\',
                `tel` as \'Telefon\',
                `tel2` as \'Fax\',
                `email` as \'E-mail\',
                `adoszam` as \'Adószám\',
                `cg` as \'Cg\',
                `text` as \'Leírás\'
                '. csv_export_get_from_tol($megnevezes);
        $query = mysqli_query($db_kapcs, $sql);

        // Nagyobb CSV-kre kérdezzünk rá előzetesen
        $osszes_sor = mysqli_num_rows($query);
        if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
            $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
            echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
            $weblap -> leallit();
            exit;
        } else {
            $sql .= $_SESSION['akt_sql_order'];
        }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if( $kulcs == 'Aktív' ) {
                    if( $sor ) {
                        $sor = 'igen';
                    } else {
                        $sor = 'nem';
                    }
                }
                
                if( $kulcs == 'Fizetési határidő' ) {
                    $sor = $sor !== '' ? ($hat_ido_nevek[$sor] ?? $sor) : '';
                }

                if( $kulcs == 'Szállító kód' ) {
                    $sor = '{EGYENLOSEG_JELET_ELE}'.$sor.'{EGYENLOSEG_JELET_ELE_VEGE}';
                }

                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                    $sor = htmlspecialchars_decode( $sor );
                }
                $sor = str_replace(
                                array('"{EGYENLOSEG_JELET_ELE}',    '{EGYENLOSEG_JELET_ELE_VEGE}"',     '{EGYENLOSEG_JELET_ELE}',   '{EGYENLOSEG_JELET_ELE_VEGE}', ';="";'  ),
                                array('="',                         '"',                                '="',                       '"',                            ';;'    ),
                                $sor
                        );
                $sor = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
                $csvcont[] = $sor;
            }

            fputcsv($open, $csvcont, ';');

            // 1000 soronként kiíratunk
            if( $i % 1000 == 0 && $i > 0 ) {
                // Progress
                echo json_encode(array('pr' => number_format(($i / $osszes_sor) * 100, 0, '', ''))).PHP_EOL;
                
                // Felszabadítás
                ob_flush();
                flush();
            }

            $i++;
        }

        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();
    }

    csv_export_lezar($fh, $filename, 'Beszerzés szállító');
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
if($sor['dok_db']){
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if($sor['megj_db']){
    $megj_db = ' ('.$sor['megj_db'].')';
}

if( isset($_GET['modosit']) || /*isset($_GET['szakterulet']) || */isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
    // Számla fülek
    $ful_tomb = array(
        'modosit'       => 'Adatlap',
        // 'szakterulet'   => 'Szakterület',
        'dokumentumok'  => 'Dokumentumok'.$dok_db,
        'megjegyzesek'  => 'Megjegyzések'.$megj_db,
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
$gombok = '
        <script>
        function szallitokod_ell(id_ell_nelkul = \'\'){
            $.ajax({
                url : \'szallito_beszerzes.php\',
                type: \'POST\',
                data: {
                    get_id          : _get(\'id\'),
                    id              : $(\'#id\').val(),
                    id_ell_nelkul   : id_ell_nelkul,
                    nev             : $(\'#nev\').val(),
                    email           : $(\'#email\').val(),
                    szallitokod_ell : Date.now()
                },
                success: function (response) {
                    if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                    if (response) {
                        response = JSON.parse(response);
                        if (response.hiba) {
                            alert2(response.hiba, response.gomb, response.title);
                            alert_utan_mehet_megsem_mutat_beallit();
                            return false;
                        }

                        alert2();
                        
                        if (response.siker) {
                            $(\'#szerk_form\').submit();
                        }

                    }
                },
                complete: function(xhr) {
                    if( xhr.status != 200 ) {
                        alert2(xhr.responseText, \'\', \'Hiba\');
                    }
                }
            });
        }
        </script>
';
if( isset($_GET['uj']) && _jog(JOG_BESZ_SZALL_OLVAS) ) {
    
    // Gombok
    $gombok .= nl2('
        <div class="form_kuldes">
            <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); szallitokod_ell();">
            <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
        </div>
    ');

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

// Módosítás gombok
} else {

    // Js, a törlés gombhoz
    $gombok .= '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

    // Jogosutságkezelés
    if(!_jog(JOG_BESZ_SZALL_TOROL)){
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
    }else{
        $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
    }

    if( _jog(JOG_BESZ_SZALL_KEZEL) ){
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="szurkit(\'mehet_megsem_rejt\'); szallitokod_ell();">
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
}

// Új termék felvitele
if(isset($_GET['uj']) && !$csak_szerkesztheto){
    
    // Újként szerkesztéskor feltöltjük a mezők tartalmát
    if($_GET['ujkent']){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, 'recid='.$_GET['ujkent']);
        if( $sor ) {
            foreach($akt_tomb as $ertek) {
                if( $ertek['name'] != 'recid' ) {
                    $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
                } else {
                    $_SESSION[$ertek['name']] = '';
                }
            }
        }

        foreach($akt_tomb as $ertek){
            $sor[$ertek['name']] = $_SESSION[$ertek['name']];
        }

    }
    $js = js_kiir($akt_tomb, 'form_ell_admin()');

    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

    // Javascript-es ellenőrzéshez
    $tabla = str_replace('.php', '', basename(self()));

    $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
    $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

    $sor = array();
    foreach($rend_mezok as $rend_mezo) {
        $sor[$rend_mezo] = '';
    }

    foreach($akt_tomb as $tomb) {
        if( $tomb['type'] == 'autocomplete' ) {
            $sor[$tomb['name'].'_autocomp'] = '';
        }
    }

    $json_sor = array();
    foreach($sor as $kulcs => $elem){
        $json_sor[$kulcs] = formAdatok_replace($elem);
    }
    $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

    // Ez elkészíti a $mezok változót
    require('switch.php');

    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html,
            'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

// Szállító módosítása
} elseif (isset($_GET['modosit'])) {
    
    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if(isset($_GET['id_hidden'])){
        $_GET['id'] = $_GET['id_hidden'];
    }

    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

    foreach($akt_tomb as $tomb) {
        if( $tomb['type'] == 'autocomplete' ) {

            if($tomb['csoport']){
                $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                $kulso_ertek['nev'] = $kulso_ertek['info'];
            }else{
                $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
            }

            $sor[$tomb['name'].'_autocomp'] = '';
            if( $kulso_ertek ) {
                $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
            }
        }
    }

    $json_sor = array();
    foreach($sor as $kulcs => $elem){
        $json_sor[$kulcs] = formAdatok_replace($elem);
    }
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\');</script>';
    
    // Duplákáláskor feltett kérdés
    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', "`id`='{$sor['telephely_id']}'", '', '1');
    $telephely = $telephely[0]['nev'];
    $szamla_html .= nl2('
        <script>
            duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
            duplikaKerdes = \'Név: '.addslashes($sor['nev']).'<br><br>Biztos, hogy duplikálja?\';
        </script>
    ');

    // FORM
    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    // Ez elkészíti a $mezok változót
    require('switch.php');

    $jobb_oldal = '';

    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
        fajlbol_olvas('../sablon/admin/form_modosit.html', true)
    );

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html,
            'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

// Dokumentumok fül
} elseif( isset($_GET['dokumentumok']) ) {

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom'  => $szamla_html.$gombok,
            'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Megjegyzések
}elseif(isset($_GET['megjegyzesek'])){

    $szamla_html .= megj_kiir($megnevezes, $_GET['id']);

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok,
            'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html.$gombok;
    }

}

if( !$_GET['ajax'] ) {
    echo '</div>';
}

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}

// Megjegyzések kiíratása
function megj_kiir($tabla, $id, $rendszer_is = 0) {
    global $adatbazis_kapcsolat;

        $sql_kieg = " AND `user`='1'";
        $rendszer_checked = '';
        $rendszer_param   = 'rendszer';
        if( $rendszer_is ) {
            $rendszer_checked = ' checked';
            $rendszer_param   = '';
            $sql_kieg = '';
        }

    // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
    $html = nl2('
        <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek" id="megj_tartalma">
            <div class="dtr nFejlec">
                <div class="dtc">
                    Megjegyzés
                    <label style="margin-left: 2rem">
                        <input type="checkbox"'.$rendszer_checked.' onclick="megj_kiir(\''.$rendszer_param.'\')">
                        Rendszerüzenetek megjelenítése
                    <label>
                </div>
                <div class="dtc">Hozzáadta</div>
                <div class="dtc">Hozzáadva</div>
            </div>
    ');

    if( $id ) {

        $sorok = $adatbazis_kapcsolat->s('recid megj user insert_usr insert_dt', 'megjegyzes', "`id`='{$id}' AND `tabla`='{$tabla}'{$sql_kieg}", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $user_kieg = '';
                if( !$sor['user'] ) {
                    $user_kieg .= 'ontEAM<br>';
                }
                $html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$user_kieg.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

    }
    
    return $html;
}