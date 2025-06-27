<?php
require_once('../weblap.php');

if( !_jog(JOG_VENDEGEJSZAKAK_OLVAS) ) {
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
$akt_tomb               = $vendegejszakak_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'vendégéjszakát';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){                           // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
        global $megnevezes;
        return $megnevezes.'_dok';

    }
}

// Anyagigénylés táblázat rendezésének letárolása SESSION-be
if (isset($_POST['anyagigenyles_tablazat_rendez'])) {

    $mentett_adat = $adatbazis_kapcsolat->s2('recid tabla_rendezes', 'login', "`recid` = '".$_SESSION[get_db_tabla().'login_recid']."'", '', '1');

    if( $mentett_adat ) {
        $rendezes = array(
            'vendegej_tablazat_rendez' => $_POST['oszlop'],
            'vendegej_tablazat_irany'  => $_POST['irany']
        );

        if( !$mentett_adat['tabla_rendezes_vendegej'] ) {
            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($rendezes)), "`recid`='".$_SESSION[get_db_tabla().'login_recid']."'");
        } else {
            $mentett_rendezes = json_decode(htmlspecialchars_decode($mentett_adat['tabla_rendezes_vendegej']), true);

            $mentett_rendezes['vendegej_tablazat_rendez'] = $_POST['oszlop'];
            $mentett_rendezes['vendegej_tablazat_irany']  = $_POST['irany'];

            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($mentett_rendezes)), "`recid`='".$_SESSION[get_db_tabla().'login_recid']."'");
        }
    }

    // Nem biztos, hogy küldtünk oszlop adatot, lehet csak az irány változik
    if( $_POST['oszlop'] ) {
        $_SESSION['vendegej_tablazat_rendez'] = $_POST['oszlop'];
    }
    if( $_POST['irany'] ) {
        $_SESSION['vendegej_tablazat_irany']  = $_POST['irany'];
    }
    
    // CSV exportban is jó legyen a sorrend ($_SESSION['akt_sql']), ezért hívjuk meg:
    szamla_lista('', 'vendegejszakak', '', '', $_GET);

    $weblap->leallit();
    exit();
}

// CSV generálása
if( isset($_GET['csv']) ) {

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        $lekerdezes_fromtol = csv_export_get_from_tol($megnevezes);
        if( $_GET['ev'] && $_GET['ho'] ) {
            if( $_GET['ho'] < 10 ) {
                $_GET['ho'] = sprintf('%02u', $_GET['ho']);
            }
            $lekerdezes_fromtol .= " AND `v_d` LIKE '{$_GET['ev']}-{$_GET['ho']}%'";
        }

        $sql = 'SELECT
                `'.get_db_tabla().'vendegejszakak`.`telephely_id` as \'Szálloda\',
                `'.get_db_tabla().'vendegejszakak`.`v_d` as \'Dátum\',
                `'.get_db_tabla().'vendegejszakak`.`v_ert` as \'Vendégéjszakák száma\',
                `'.get_db_tabla().'vendegejszakak`.`ksz_ert` as \'Kiadott szobák száma\',
                `'.get_db_tabla().'vendegejszakak`.`khsz_ert` as \'Kiadható szobák száma\',
                `'.get_db_tabla().'vendegejszakak`.`rr_ert` as \'Rendezvényen résztvettek száma\',
                `'.get_db_tabla().'vendegejszakak`.`ugyl_ert` as \'Uszodai-gyógyászati látogatók száma\',
                `'.get_db_tabla().'vendegejszakak`.`ter_ert` as \'Terítékek száma\'
                '.$lekerdezes_fromtol;
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
        while ( $sorok = mysqli_fetch_assoc($query) ) {
            $csvcont = array();

            // Dátum formázása
            $sorok['insert_dt'] = str_replace('-', '.', $sorok['insert_dt']);
            // Számok formázása
            $szamok = array('Vendégéjszakák száma', 'Kiadott szobák száma', 'Kiadható szobák száma', 'Rendezvényen résztvettek száma', 'Uszodai-gyógyászati látogatók száma', 'Terítékek száma');
            foreach ( $szamok as $szam ) {
                $sorok[$szam] = str_replace('.', ',', $sorok[$szam]);
            }

            foreach( $sorok as $kulcs => $sor ) {

                if( $kulcs == 'Szálloda' ) {
                    $sor = getSzallodaNev($sor);
                }
                if( $kulcs == 'Dátuma' ) {
                    $sor = datum_kiir($sor);
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

        csv_export_lezar($fh, $filename, 'Vendégéjszakák');
    }
}



// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}

require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        $class = '';
        if( isset($_GET['tablazat']) ) {
            $class = ' class="nopadd"';
        }

        echo '<div id="szamlaBetolt"'.$class.' data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }
    if( $sor['megj_db'] ) {
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['tablazat']) || isset($_GET['modosit']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'tablazat'      => 'Vendégéjszakák',
            'modosit'       => 'Adatlap',
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );

        $kivetelek = array('ajax', 'uj');
        foreach ( $ful_tomb as $key => $value ) {
            array_push($kivetelek, $key);
        }

        $fulek = '';
        foreach ( $ful_tomb as $key => $value ) {

            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }

            $id_betolteshez = $_GET['id'];
            if( !$_GET['id'] ) {
                $id_betolteshez = $_SESSION['legutobbi_id'];
            }

            if( $key == 'tablazat' ) {
                $fulek .= '<a href="'.self().get_get($kivetelek).'&amp;tablazat"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            } else {
                $fulek .= '<a href="javascript: szamlaBetolt(\''.$id_betolteshez.'\', \''.basename(self()).'\', \''.$key.'\', \'\', \'\', \'-vendeg-\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
            }

        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

    // Gombok itt nem kellenek egyáltalán
    // Ha mégis kell, puskázz az anyagigenylesek.php-ból
    $gombok = '';

    // Műveletek box
    $gombok = nl2('
        <div class="muveletek"'.$muv_style.'>
            '.$gombok.'
        </div>
    ');

    $js_submit_elott = 'return form_ell_admin();';

	// Táblázat
    if( isset($_GET['tablazat']) ) {

        // A fülek CSS-ét itt felülírjuk, illetve a táblázat rendezőt és egyés ide vonatkozós JS-t is meghívunk
        // Anyagigénylések JS-ét haszáljuk itt is, a speciális táblázatos működés miatt.
        $szamla_html .= nl2('
                <style type="text/css">
                    div.szamlaFulek{
                        margin-left: 0;
                    }
                </style>
                <script src="../js/sorttable.js"></script>
                <script src="../js/anyagigenyles_tablazat.js"></script>
                <script>$(document).ready(function () { fejlecPozicional(); });</script>
        ');

        // Ha nincs egy sornyi találat sem
        if( !$balNavPanelRECIDk ) {
            $szamla_html .= vendeg_ejszaka_datum_szuro('-poz-relative-');
        }
        $szamla_html .= $balNavPanelHTMLje;

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'fulek'    => $fulek,  // A fülek is változnak
                'tartalom' => $gombok, // Ajax esetén csak a gombokat írjuk ki
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Vendégéjszaka módosítása
    } elseif( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
        foreach ( $akt_tomb as $tomb ) {

            // Autocomplete lekezelése
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
                } else {
                    $sor[$tomb['name'].'_autocomp'] = $sor[$tomb['name']];
                }

            }

        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
        $szamla_html .= '<div class="vh" style="height: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
            require_once('tobb_kep.php');
        }

	// Megjegyzések
    } elseif( isset($_GET['megjegyzesek']) ) {

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        if( $_GET['id'] ) {
            $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id`='{$_GET['id']}' AND `tabla`='".str_replace('.php', '', basename(self()))."'", 'recid desc');
            if( $sorok ) {
                $i = 1;
                foreach($sorok as $sor) {
                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                            <div class="dtc">'.$sor['insert_usr'].'</div>
                            <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                        </div>
                    ');

                    $i++;
                }
            }
        }

        // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
        $szamla_html .= '<div class="dn">'.$balNavPanelHTMLje.'</div>';

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
