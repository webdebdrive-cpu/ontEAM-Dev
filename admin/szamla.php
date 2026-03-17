<?php
require_once('../weblap.php');

if( !_jog(JOG_FENNTARTASI_SZAMLAK_OLVAS) && !_jog(JOG_ENERGIA_SZAMLAK_OLVAS) && !_jog(JOG_ANYAG_SZAMLAK_OLVAS) && !_jog(JOG_PRSZAMLAK_OLVAS) && !_jog(JOG_PR_SAJAT_SZAMLA_MEGTEKINT) ) {
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
$akt_tomb               = $szamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'számlát';         // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
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

// Számla áthelyezése valamelyik modul alá
if( isset($_POST['szamla_athelyez']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    if( !_jog(ANDOC_SZAMLAK_SZETOSZTASA) ) {
        echo json_encode(array(
            'hiba'  => 'Önnek nincs joga számlákat szétosztani!',
            'title' => 'Hiba'
        ));
        $weblap->leallit();
        exit();
    }
    
    // Melyik táblába kell küldeni
    switch ($_POST['tipus']) {
        case 'anyag'        : $tabla = 'anyag_szamla_atmeneti';        break;
        case 'energia'      : $tabla = 'energia_szamla_atmeneti';      break;
        case 'fenntartas'   : $tabla = 'fenntartasi_szamla_atmeneti';  break;
        case 'megallapodas' : $tabla = 'megallapodas_szamla_atmeneti'; break;
        case 'projekt'      : $tabla = 'projektszamla_atmeneti';       break;
    }
    
    $recidk = json_decode($_POST['recidk'], '-tömb-');
    if( $recidk ) {
        $szamlak = $adatbazis_kapcsolat->s('*', 'szamla_atmeneti', "`recid` IN (".implode(', ', $recidk).")");
        if( $szamlak === false ) { $db_error = true; }
        $dekodolandok = szamla_oszlopok_decode();
        foreach ( $szamlak as $szamla ) {
            // Oszlopok és értékek beállítása
            $ertekek  = array();
            $oszlopok = szamla_oszlopok(); // FONTOS, hogy ez itt a ciklusban legyen
            foreach ( $oszlopok as $oszlop ) {
                if( in_array($oszlop, $dekodolandok) ) {
                    $szamla[$oszlop] = htmlspecialchars_decode($szamla[$oszlop]);
                }
                $ertekek[$oszlop] = $szamla[$oszlop];
            }

            // Energia számláknál még egy fajta oszlopot is ki kell töltenünk
            if( $tabla == 'energia_szamla_atmeneti' ) {
                array_push($oszlopok, 'fajta'        );
                array_push($ertekek,  $_POST['fajta']);
            }

            // Tényleges mozgatás
            $sql = $adatbazis_kapcsolat->i($tabla, $oszlopok, $ertekek);
            if( $sql === false ) { $db_error = true; }

            // Sor törlése
            $sql = $adatbazis_kapcsolat->d('szamla_atmeneti', "`recid`='{$szamla['recid']}'");
            if( $sql === false ) { $db_error = true; }
        }
    // Ha nem kaptunk recid-t
    } else {
        echo json_encode(array(
            'hiba'  => 'Egyetlen számlasor sem került kijelölésre, a művelet így nem hajtható végre!',
            'title' => 'Hiba'
        ));
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array('siker' => '1'));
    } else {
        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Számla visszahelyezése ide
if( isset($_POST['vissza_andoc_szamlakhoz']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Melyik táblából kell kiolvasni
    $tabla = str_replace('.php', '_atmeneti', $_POST['tipus']);

    $szamla = $adatbazis_kapcsolat->s2('*', $tabla, "`andoc_id`='{$_POST['andoc_id']}'");
    if( $szamla === false ) { $db_error = true; }
    
    // Oszlopok és értékek beállítása
    $dekodolandok = szamla_oszlopok_decode();
    $ertekek  = array();
    $oszlopok = szamla_oszlopok();
    foreach ( $oszlopok as $oszlop ) {
        if( in_array($oszlop, $dekodolandok) ) {
            $szamla[$oszlop] = htmlspecialchars_decode($szamla[$oszlop]);
        }
        $ertekek[$oszlop] = $szamla[$oszlop];
    }
    
    // Tényleges mozgatás
    $sql = $adatbazis_kapcsolat->i('szamla_atmeneti', $oszlopok, $ertekek);
    if( $sql === false ) { $db_error = true; }
    
    // Sor törlése
    $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$_POST['andoc_id']}'");
    if( $sql === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array('siker' => '1'));
    } else {
        mysqli_rollback($db_kapcs);

        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap->leallit();
    exit();
}

// Nem műszaki számla
if( isset($_POST['nem_muszaki_szamla']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // A számla törlése és az XML előállítása, elküldése.
    // Itt előfordulhat, hogy szöveges hibaüzenetet kapunk vissza (pl. az XML üres volt.)
    $hibauzenet = xml_vissza($_POST['recid']);
    if( $hibauzenet !== false ) {
        $db_error = $hibauzenet;
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array('siker' => '1'));
    } else {
        mysqli_rollback($db_kapcs);

        // Alapértelmezetten a tranzakcioHiba()-t szoktuk kiírni, de ha kaptunk $hibauzenet-et az xml_vissza-ból, akkor azt írjuk ki
        $hiba = tranzakcioHiba();
        if( $hibauzenet ) {
            $hiba = $hibauzenet;
        }

        echo json_encode(array(
            'hiba'  => $hiba,
            'title' => 'Hiba',
        ));
    }
    
    $weblap->leallit();
    exit();
}



// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

if( !$_GET['ajax'] ) {
    echo '<div id="szamlaBetolt">';
}

if( isset($_GET['modosit']) ) {
    // Számla fülek
    $ful_tomb = array(
        // 'modosit'       => 'Adatlap',
        'tablazat'      => 'Andoc számlák',
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

$gombok = '';
if( isset($_GET['uj']) && (_jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) || _jog(JOG_ANYAG_SZAMLAK_KEZEL) || _jog(JOG_ENERGIA_SZAMLAK_KEZEL) || _jog(JOG_PRSZAMLAK_KEZEL) || _jog(JOG_PR_SAJAT_SZAMLA_KEZEL) ) ) {

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

// Táblázat gombjai
} else if( isset($_GET['tablazat']) ) {
    
    if( _jog(ANDOC_SZAMLAK_SZETOSZTASA) ) {
        $gombok .= '<input type="button" class="gomb" value="Fenntartás" onclick="szamla_athelyez(\'fenntartas\')"> ';
        $gombok .= nl2('
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb modositaskor_rejt" value="Energia">
                        <div class="muv">
                            <ul>
                                <li><a onclick="szamla_athelyez(\'energia\', \''.ENERGIA_SZAMLA_VILLANY .'\')">Villany</a></li>
                                <li><a onclick="szamla_athelyez(\'energia\', \''.ENERGIA_SZAMLA_GAZ     .'\')">Gáz</a></li>
                                <li><a onclick="szamla_athelyez(\'energia\', \''.ENERGIA_SZAMLA_VIZ     .'\')">Víz és csatorna</a></li>
                                <li><a onclick="szamla_athelyez(\'energia\', \''.ENERGIA_SZAMLA_TERMAL  .'\')">Termál- és gyógyvíz</a></li>
                            </ul>
                        </div>
                    </div>
                    ');
        $gombok .= '<input type="button" class="gomb" value="Anyag" onclick="szamla_athelyez(\'anyag\')"> ';
        $gombok .= '<input type="button" class="gomb" value="Projekt" onclick="szamla_athelyez(\'projekt\')"> ';
        $gombok .= '<input type="button" class="gomb" value="Megállapodás" onclick="szamla_athelyez(\'megallapodas\')"> ';
        $gombok .= '<input type="button" class="gomb piros" value="Nem műszaki számla" onclick="nem_muszaki_szamla()"> ';
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
    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

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

// Új rekord felvitele
if( isset($_GET['uj']) ) {

//        // Itt úgy fogunk tenni, mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
//        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
//        $sor = $adatbazis_kapcsolat->s2('*', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
//        $sor['szallito_id_autocomp'] = getErtek('nev', 'szallito', 'id', $sor['szallito_id']);
//	    $js = js_kiir($akt_tomb, 'form_ell_admin()');
//
//	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);
//
//        // Javascript-es ellenőrzéshez
//        $tabla = str_replace('.php', '', basename(self()));
//
//        $json_sor = array();
//        foreach ( $sor as $kulcs => $elem ) {
//            $json_sor[$kulcs] = formAdatok_replace($elem);
//        }
//        
//        $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
//
//		// Ez elkészíti a $mezok változót
//		require('switch.php');
//
//	    $form_mit     = array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}', '{MUV_STYLE}'   );
//	    $form_mivel   = array($js,    $js_submit_elott,    self(),     $mezok,    $megnevezes,    $ckeditor,     $gombok,    $muv_style      );
//	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);
//
//        if( $_GET['ajax'] ) {
//            echo json_encode(array(
//                'tartalom'  => $szamla_html,
//                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
//            ));
//        } else {
//            echo $szamla_html;
//        }

// Rekord módosítása
} elseif( isset($_GET['modosit']) ) {

//        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
//        if( isset($_GET['id_hidden']) ) {
//            $_GET['id'] = $_GET['id_hidden'];
//        }
//
//	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
//        
//        foreach ( $akt_tomb as $tomb ) {
//            if( $tomb['type'] == 'autocomplete' ) {
//                
//                if( $tomb['csoport'] ) {
//                    $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
//                    $kulso_ertek['nev'] = $kulso_ertek['info'];
//                } else {
//                    $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
//                }
//
//                $sor[$tomb['name'].'_autocomp'] = '';
//                if( $kulso_ertek ) {
//                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
//                }
//            }
//        }
//
//        $json_sor = array();
//        foreach ( $sor as $kulcs => $elem ) {
//            $json_sor[$kulcs] = formAdatok_replace($elem);
//        }
//        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
//
//	    // FORM
//	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');
//
//		// Form actionja
//		$action = self().get_get(array('errnum','kephiba','siker'));
//
//		// Ez elkészíti a $mezok változót
//		require('switch.php');
//
//	    $szamla_html .= str_replace(
//            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
//            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
//            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
//        );
//
//        if( $_GET['ajax'] ) {
//            echo json_encode(array(
//                'tartalom'  => $szamla_html,
//                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
//            ));
//        } else {
//            echo $szamla_html;
//        }
//
//        if( $tobb_kep ) {
//            require_once('tobb_kep.php');
//		}

// Táblázat
} elseif( isset($_GET['tablazat']) ) {
    
    $tetelek_html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szla_szam szallito_id targy kcs_21 brutto dev_br telj_dt dok_link', 'szamla_atmeneti', "", 'insert_dt desc');
    $i = 0;
    $van_ures_sor = 0;
    foreach ( $szamlak as $szamla ) {

        if( !$szamla['andoc_id'] ) {
            $van_ures_sor = 1;
        }

        $brutto    = 'brutto';
        $tizedesek = 0;
        if( $szamla['kcs_21'] != KCS_21_HUF ) {
            $brutto    = 'dev_br';
            $tizedesek = 2;
        }
        
        $link = '';
        if( $szamla['dok_link'] ) {
            $link .= nl2('
                        <a href="'.$szamla['dok_link'].'" target="_blank">
                            <img src="../elemek/admin/file.svg" title="Fájl megnyitása" alt="Fájl megnyitása" width="24">
                        </a>
                        ');
        }
        
        $kieg = '';
        $tetelek_html .= nl2('
                <tr class="kijSor" data-recid="'.$szamla['recid'].'" data-i="'.($i+1).'">
                    <td>'.getSzallodaNev($szamla['telephely_id']).'</td>
                    <td>'.$szamla['szla_szam'].'</td>
                    <td>'.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']).'</td>
                    <td>'.htmlspecialchars_decode($szamla['targy']).'</td>
                    <td sorttable_customkey="'.number_format($szamla[$brutto], $tizedesek, '.', '').'" class="r">'.number_format($szamla[$brutto], $tizedesek, ',', ' ').'</td>
                    <td class="c">'.erteklista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']).'</td>
                    <td class="c">'.datum_kiir($szamla['telj_dt']).'</td>
                    <td class="c">'.$link.'</td>
                </tr>
            ');
        $i++;
    }
if( $van_ures_sor || isset($_GET['teszt']) ) {
    $last_mail = fajlbol_olvas('!andoc_mail_volt.txt');
    if( $last_mail < date('Y-m-d H:i:s')-60*60*24 ) {
        fajlba_mentes('!andoc_mail_volt.txt', date('Y-m-d H:i:s'));
        level_kuldes(get_nev(), get_mail(), 'BK', 'bk@webdeb.hu', 'Üres Andoc számla van', 'Üres Andoc számla van');
    }
}
    
    if( $szamlak ) {
        // <table id="dragTable" class="sar-table szamla_cont_tabla"> <= Ettől lesz sorrendezhető az oszlopa
        $szamla_html .= nl2('
                <script src="../js/sorttable.js"></script>
                <div class="kijCont">
                    <table class="szamla_cont_tabla sortable">
                    <thead>
                        <tr>
                            <th style="width: 10rem">Szálloda</th>
                            <th style="width: 15rem" class="nowrap">Számlaszám</th>
                            <th>Szállító</th>
                            <th>Tárgy</th>
                            <th style="width: 10rem" class="nowrap r">Bruttó</th>
                            <th style="width: 5rem" class="c">Pénznem</th>
                            <th style="width: 10rem" class="c">Teljesítés dátuma</th>
                            <th style="width: 10rem" class="c">Számla kép</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.$tetelek_html.'
                    </tbody>
                    </table>
                </div>
                        ');
    // Ha nincs Andoc számla
    } else {
        $szamla_html .= '<h1 style="margin-top: 4rem">Jelenleg nem található egyetlen Andoc-ból küldött számla sem!</h1>';
    }

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


// Andoc számla elutasításához gyártunk egy XML vissza fájlt
function xml_vissza( $szamla_id ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    $szamla = $adatbazis_kapcsolat->s2('targy igazolva andoc_id telephely_id kcs_21 erk_xml kuld_tetel_xml kuld_xml megjegyz kolt_vis igazolva_usr', 'szamla_atmeneti', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
 
    // Cégkód konverzió
    $erteklista = $adatbazis_kapcsolat->s2('cegkod', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `id`='{$szamla['kolt_vis']}'");
    if( $erteklista === false ) { $db_error = true; }
    
    // XML dekódolása
    $szamla['targy']    = htmlspecialchars_decode($szamla['targy']);
    $szamla['megjegyz'] = htmlspecialchars_decode($szamla['megjegyz']);
    
    // Központi cukrásztermelő esetén a $szalloda_klaszter számát átírjuk
    $szalloda_klaszter = $szamla['telephely_id'];
    if( $szalloda_klaszter == TELEPHELY_KOZP_CUK ) {
        $szalloda_klaszter = TELEPHELY_KOZP_CUK_MOD;
    }

    // A NIK3 rész csak akkor kerül bele az XML-be, ha a <form_template_name> TAG-ben NEM 'Műszaki számla' szerepel
    $nik3 = '';
    $szamla['erk_xml'] = htmlspecialchars_decode($szamla['erk_xml']);
    if( get_string_between($szamla['erk_xml'],'<form_template_name>', '</form_template_name>') != 'Műszaki számla' ) {
        $nik3 = nl2('
            <container name="NIK3">
                <row rownumberonpage="1">
                    <property name="Rögzítendő űrlap típusa">Elutasított</property>
                </row>
            </container>
        ');
    }

    $valasz_xml = 'psInputXml=<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>[AUTO] Számla szétosztás</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Igazoló döntés">Elutasítva</property>
            <property name="Teljesítés igazolás elutasítás oka">Nem műszaki számla.</property>
            <property name="Szálloda - Klaszter">'.$szalloda_klaszter.'</property>
            <property name="Számla tárgya">'.$szamla['targy'].'</property>
            <property name="Megjegyzés">'.$szamla['megjegyz'].'</property>
            <property name="Számla fizetője">'.$erteklista['cegkod'].'</property>
            <property name="Teljesítés igazoló személye">'.$_SESSION[get_db_tabla().'teljes_nev'].'</property>
          </row>
        </container>
        '.$nik3.'
        <container name="IK1">
        </container>
      </properties>
    </register>
  </data>
</xml>
';
   
    $fejlec = array(
        "Content-type: application/x-www-form-urlencoded",
        "Content-length: ".strlen($valasz_xml),
        "Connection: close",
    );
    
    // Válasz XML postolása CSAK éles és Teszt környezetben van
    if( eles_es_teszt_rendszer_e() ) {
        if( eles_rendszer_e() ) {
            $valasz = URL_post(ANDOC_POST_KULD_URL_ELES, $valasz_xml, $fejlec);
        } else {
            $valasz = URL_post(ANDOC_POST_KULD_URL_TESZT, $valasz_xml, $fejlec);
        }
    } else {
        $valasz_xml = '
***Csak Éles és Teszt rendszerben küldjük el az XML-t valójában!***

        '.$valasz_xml;
    }

    // Logolás
    $log = '*****************************
    KÜLDÖTT XML (Nem műszaki számla elutasításakor)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz_xml.'



    ';
    file_put_contents(ANDOC_LOG_UTVONAL, $log, FILE_APPEND | LOCK_EX);
    
    // Logolás
    $log = '*****************************
    KAPOTT XML (Nem műszaki számla elutasításakor válaszként)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz.'
        
        
        
    ';
    file_put_contents(ANDOC_LOG_UTVONAL, $log, FILE_APPEND | LOCK_EX);
            
        // A kapott választ feldolgozató állapotra kell hozni, hogy értelmezni lehessen, ehhez ki kell törölni belőle a sallangot
        if( eles_es_teszt_rendszer_e() ) {
            $mit   = array('<?xml version="1.0" encoding="utf-8"?>', '<string xmlns="AndocWebServices.WebService_AndocSimple"><?xml version="1.0" encoding="utf-8" standalone="yes"?>', '</xml></string>');
            $mivel = array('',                                       '',                                                                                                                '</xml>'         );

            $xml_string = trim(str_replace($mit, $mivel, htmlspecialchars_decode($valasz)));
            if (empty($xml_string)) {
                $db_error = 'Andoctól kapott XML válasz feldolgozása során hiba történt (üres választ kaptunk)!';
            } else {
                
                libxml_use_internal_errors(true);
                try {
                    $kapott_xml = new SimpleXMLElement($xml_string);
                } catch (Exception $e) {
                    $db_error = 'Andoctól kapott XML válasz feldolgozása során hiba történt!';
                    $kapott_xml = null;
                }

                if ($kapott_xml === null) {
                    $errors = libxml_get_errors();
                    libxml_clear_errors();
                }
            }

        }

        // Ha Andoc-ék nem tudják fogadni a választ, akkor hibaüzenet
        if( eles_es_teszt_rendszer_e() && $kapott_xml -> result != 'I' ) {

            // Logolás
            $log = '*****************************
            Az alábbi válasz XML-t nem sikerült elküldeni:
            '.date('Y.m.d H:i:s').'
            Ip: '.$_SERVER['REMOTE_ADDR'].'
            URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
            POST:
            '.$valasz_xml.'



        ';
        file_put_contents(ANDOC_LOG_UTVONAL, $log, FILE_APPEND | LOCK_EX);
        
        $db_error = true;
        
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        // Számla törlése
        $sql = $adatbazis_kapcsolat->d('szamla_atmeneti', "`recid`='{$szamla_id}'");
        if( $sql === false ) { $db_error = true; }

    }
    
    return $db_error;
}