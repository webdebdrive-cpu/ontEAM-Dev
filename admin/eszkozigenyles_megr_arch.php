<?php
require_once('../weblap.php');

if( !_jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) ) {
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
$akt_tomb               = $eszkozigenyles_megr_arch_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'megrendelést';                   // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = 1;
if( $tobb_kep ) {

    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        return 'eszkozigenyles_megr_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// Megjegyzések kiíratása
if (isset($_POST['megj_kiir'])) {

    echo json_encode( array('html' => megj_kiir('eszkozigenyles_megr', $_POST['id'], $_POST['rendszer'])) );

    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        // Rendezés kiszedése $_SESSION['akt_sql']-ből
        $order = '';
        preg_match('/ORDER BY .*?(?=LIMIT|$)/is', $_SESSION['akt_sql'], $matches);
        if (!empty($matches)) {
            $order = trim($matches[0]);
        }

        // Ha van LIMIT a $_SESSION['akt_sql']-ben, akkor azt itt is figyelembe kell venni
        if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
            $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
            if( strpos($limit, ' ') ) {
                $limit = substr($limit, 0, strpos($limit, ' '));
            }
            $limit = " LIMIT {$limit}";
        }
        
        $sql = 'SELECT
                `rend_szam` as \'Rendelés szám\',
                `telephely_txt` as \'Szálloda\',
                `szallito_txt` as \'Szállító\',
                `ossz_dev` as \'Deviza összesen\',
                `ossz_netto` as \'Nettó összesen\',
                `szall_hat` as \'Szállítási határidő\',
                `'.get_db_tabla().$megnevezes.'`.`megr_dt` as \'Megrendelés dátuma\',
                `'.get_db_tabla().$megnevezes.'`.`igazolva_dt` as \'Emailt szállító megkapta\',
                `szall_mail` as \'Szállító email címe\'
                '. csv_export_get_from_tol($megnevezes).$order.$limit;
        $query = mysqli_query($db_kapcs, $sql);

        // Nagyobb CSV-kre kérdezzünk rá előzetesen
        $osszes_sor = mysqli_num_rows($query);
        if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
            $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
            echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
            $weblap -> leallit();
            exit;
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

    csv_export_lezar($fh, $filename, 'Archív Beszerzés megrendelés');
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

if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
    // Számla fülek
    $ful_tomb = array(
        'modosit'       => 'Adatlap',
        'tortenet'      => 'Történet',
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
$gombok = '';
if( isset($_GET['uj']) && _jog(JOG_BESZERZ_IGENYLESEK_MEGR_OLVAS) ) {
    
    // Új felvitel itt nincs is, automatikusan jön létre

// Módosítás gombok
} else {

    $gombok = '';

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
    
    // Új felvitel nincs, automatikusan jön létre

// Szállító módosítása
} elseif (isset($_GET['modosit'])) {
    
    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if( isset($_GET['id_hidden']) ) {
        $_GET['id'] = $_GET['id_hidden'];
    }

    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

    // Mivel archiv felületen vagyunk, ezért a mezők típusát és nevét átírjuk ha kell
    // Erre azért van szükség, hogy pl. egy atocompletes mezőből txt legyen, hogy az értékét ne más táblákból olvassa vissza
    // hanem az adatbázisban letároltat
    $i = 0;
    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type_arch'] ) {
            $akt_tomb[$i]['type'] = $tomb['type_arch'];
            $akt_tomb[$i]['name'] = $tomb['name_arch'];
        }
        $i++;
    }

    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type'] == 'autocomplete' ) {

            if( $tomb['csoport'] ) {
                $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                $kulso_ertek['nev'] = $kulso_ertek['info'];
            } else {
                $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
            }

            $sor[$tomb['name'].'_autocomp'] = '';
            if( $kulso_ertek ) {
                $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
            }
        }
    }

    $json_sor = array();
    foreach ( $sor as $kulcs => $elem ) {
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

    // Form
    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    // Ez elkészíti a $mezok változót
    require('switch.php');

    // Aláírók kiíratása
    $szallodat_lat = " AND `aktiv`=1 AND `virtual`=0 AND `nev` IN (SELECT `nev` FROM `".get_db_tabla()."telephely_kapcs` WHERE `telephely_id`='{$sor['telephely_id']}')";
    $elsodleges = $adatbazis_kapcsolat -> s2('teljes_nev', 'login', "substr(`flags`, ".(JOG_BESZERZ_IGENYLESEK_ELSODLEGES+1).", 1)=1{$szallodat_lat}");
    $masodlagos = $adatbazis_kapcsolat -> s2('teljes_nev', 'login', "substr(`flags`, ".(JOG_BESZERZ_IGENYLESEK_MASODLAGOS+1).", 1)=1{$szallodat_lat}");
    $mezok .= nl2('
        <div class="dtr">
            <div class="dtc form_bal">
                <span>Elsődleges aláíró:</span>
            </div>
            <div class="dtc form_jobb">
                <input type="text" maxlength="50" autocomplete="off" readonly="readonly" style="width: 35rem;" value="'.$elsodleges['teljes_nev'].'" tabindex="-1">
            </div>
        </div>
        <div class="dtr">
            <div class="dtc form_bal">
                <span>Másodlagos aláíró:</span>
            </div>
            <div class="dtc form_jobb">
                <input type="text" maxlength="50" autocomplete="off" readonly="readonly" style="width: 35rem;" value="'.$masodlagos['teljes_nev'].'" tabindex="-1">
            </div>
        </div>
    ');

    $jobb_oldal = '';

    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
        fajlbol_olvas('../sablon/admin/form_modosit.html', true)
    );

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html,
            'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html;
    }

    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

// Történet
}elseif( isset($_GET['tortenet']) ) {

    $szamla_html .= get_tortenet($megnevezes);

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok
        ));
    } else {
        echo $szamla_html.$gombok;
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
} elseif (isset($_GET['megjegyzesek'])) {

    $szamla_html .= megj_kiir('eszkozigenyles_megr', $_GET['id']);

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

        $sorok = $adatbazis_kapcsolat->s('recid megj user insert_usr insert_dt', 'megjegyzes_arch', "`id`='{$id}' AND `tabla`='{$tabla}'{$sql_kieg}", 'recid desc');
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