<?php
require_once('../weblap.php');

if( !_jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {
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
$akt_tomb               = $energia_szamla_tomb;
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['kifizetesek']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'kifizetesek'   => 'Kifizetések',
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
    if( isset($_GET['uj']) && _jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
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
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( true /* !_jog(... Energia számla törlés jog ...) */ ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

//        if( _jog(... Energia számla írás jog ...) ){
//            $gombok = nl2('
//                <div class="form_kuldes">
//                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
//                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
//                </div>
//            ').$gombok;
//        }

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

        // FORM
		if( isset($_GET['ujkent']) ) {
            foreach ( $akt_tomb as $ertek ) {
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
        foreach ( $rend_mezok as $rend_mezo ) {
            $sor[$rend_mezo] = '';
        }

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
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
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Rekord módosítása
    } elseif( isset($_GET['modosit']) ) {

        $honapok = array('Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December');
        
        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

        // A hónapot szövegesen kell megjeleníteni. Ezért itt felülírjuk. Olyna mintha eleve így lett volna eltárolva.
        $sor['szhonap'] = $honapok[$sor['szhonap']-1];
        
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
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

	    // FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

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

	// Számla tételek
    } elseif( isset($_GET['tetel']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc r">Nettó érték</div>
                    <div class="dtc r">Deviza nettó</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_GET['id']}'", 'igazol_dt');
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                </div>
            ');
        }
        $szamla_html .= '</div>';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Kifizetések fül
    } elseif( isset($_GET['kifizetesek']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Bank</div>
                    <div class="dtc">Kifizetve</div>
                    <div class="dtc r">Bruttó</div>
                    <div class="dtc r">Deviza bruttó</div>
                    <div class="dtc">Pénznem</div>
                    <div class="dtc">Rögzítve</div>
                    <div class="dtc">Rögzítette</div>
                </div>
        ');

        // Tételek felsorolása
        $kifizetesek = $adatbazis_kapcsolat->s('*', 'energia_szamla_kifizetesek', "`energia_szamla_recid`='{$_GET['id']}'", 'insert_dt');
        foreach( $kifizetesek as $kifizetes ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.erteklista(ERTEKLISTA_BANK, $kifizetes['bank']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['kifizetes_dt']).'</div>
                    <div class="dtc r">'.number_format($kifizetes['br_ertek'], 0, '', ' ').'</div>
                    <div class="dtc r">'.number_format($kifizetes['dev_br'], 2, ',', ' ').'</div>
                    <div class="dtc">'.erteklista(ERTEKLISTA_PENZNEM, $kifizetes['kcs_21']).'</div>
                    <div class="dtc">'.datum_kiir($kifizetes['rogzitve_dt'], 'csak dátum').'</div>
                    <div class="dtc">'.$kifizetes['rogzitette'].'</div>
                </div>
                ');
        }
        $szamla_html .= '</div>';
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }
        
	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
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

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'recid desc');

        if( $sorok ) {
            $i = 1;
            foreach ( $sorok as $sor ) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" data-id="'.$sor['recid'].'" data-i="'.$i.'" class="dtr nSor kijSor">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br2($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                    </div>
                ');

                $i++;
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html.$gombok,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	}

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}