<?php
require_once('../weblap.php');

if( !_jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
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
$akt_tomb               = $fenntartasi_szerzodes_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'szerződést';         // Hibaüzenetkor: Biztosan törlöd a $szoveg?

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


// Köztes tábla szerződéseiek kilistázása
if( isset($_POST['fenntartasi_szerzodes_get_koztes']) ) {
    
    // Köztes szerődésekből készítsünk táblázatot
    $html = '';
    $szerzodesek = $adatbazis_kapcsolat->s('recid andoc_id szszam szallito_id targy kcs_21 netto dev_net', 'fenntartasi_szerzodes_atmeneti', "");
    if( $szerzodesek ) {
        $html .= nl2('
            <table class="csikos szamla_cont_tabla" id="pop_tetelek">
                <thead>
                <tr>
                    <th class="l">Szerződésszám</th>
                    <th class="l">Partner</th>
                    <th class="l">Tárgy</th>
                    <th class="r">Nettó érték</th>
                </tr>
                </thead>
                <tbody>
            ');
        foreach( $szerzodesek as $szerzodes ) {
            
            $netto_oszlop = 'netto';
            $tizedes      = 0;
            if( $szerzodes['kcs_21'] != KCS_21_HUF ) {
                $netto_oszlop = 'dev_net';
                $tizedes      = 2;
            }
            $html .= nl2('
                    <tr data-andoc_id="'.$szerzodes['andoc_id'].'">
                        <td class="l">'.$szerzodes['szszam'].'</td>
                        <td class="l">'.$szerzodes['szallito_id'].' '.getErtek('nev', 'szallito', 'id', $szerzodes['szallito_id']).'</td>
                        <td class="l">'.resz_string($szerzodes['targy'], 40).'</td>
                        <td class="r nowrap">'.number_format($szerzodes[$netto_oszlop], $tizedes, ',', ' ').'</td>
                    </tr>
                ');
        }
        $html .= nl2('
                <tbody>
                </table>
            ');
    } else {
        $html .= '<script>popupZar(); alert2(\'Jelenleg nem található feldolgozandó szerződés.\', \'\', \'Hiba\')</script>';
    }
    
    echo json_encode( array('html' => $html) );
    
    $weblap -> leallit();
    exit;
}


// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['andoc_id'] ) {
        $szerzodes = $adatbazis_kapcsolat->s2('recid kcs_21', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szerzodes = $adatbazis_kapcsolat->s2('kcs_21', 'fenntartasi_szerzodes', "`recid`='{$_POST['id']}'");
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szerzodes['kcs_21'] && $szerzodes['kcs_21']!=KCS_21_HUF ) {
        $szerzodes_netto_mezo   = 'dev_net';
        $penznem                = getErtekLista(ERTEKLISTA_PENZNEM, $szerzodes['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'fennt_szerz_tetel_dev_net';
    } else {
        $szerzodes_netto_mezo   = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'fennt_szerz_tetel_netto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $tetelek_html = '';
    $i = 0;
    foreach( $_SESSION['fennt_szerz_tetel_telephely_id'] as $telephely ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$i];
        $torles = '';
        // Itt nincsenek Andoc-os tételek
        // if( !$_SESSION['fennt_szerz_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        // }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['fennt_szerz_tetel_recid'][$i] ) {
            $_SESSION['fennt_szerz_tetel_recid'][$i] = time().rand(0,1000);
        }
        $tetelek_html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$i].'">
                            <td class="pop_telephely l">'.getSzallodaNev($_SESSION['fennt_szerz_tetel_telephely_id'][$i]).'</td>
                            <td class="pop_telj_dt l">'. datum_kiir($_SESSION['fennt_szerz_tetel_telj_dt'][$i]).'</td>
                            <td class="pop_netto_ert r">'.number_format(str_replace(array(',', ' '), array('.', ''), $_SESSION[$session_mezo][$i]), $tizedesek, ',', ' ').'</td>
                            <td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$i].'\');">
                                    🖉
                                </a>
                            </td>
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_telephely_id" value="'.$_SESSION['fennt_szerz_tetel_telephely_id'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['fennt_szerz_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['fennt_szerz_tetel_recid'][$i].'">
                            </td>
                        </tr>');
        $i++;
    }

    // Szálloda select előállítása, visszavezetve a selectek.php + switch.php működésére
    require_once 'selectek.php';
    $akt_tomb = array(
                        array('type' => 'select_felh_telephely',   'megn' => 'Szálloda:',   'name' => 'pop_telephely_id',  'class' => 'disableNav csillag', 'style' => 'width: 100%;'),
                );
    require_once 'switch.php';
    $szalloda_select = $input -> getHtml();
   
    echo json_encode( array(
        'html'            => $tetelek_html,
        'szalloda_select' => $szalloda_select,
        ) );
    
    $weblap -> leallit();
    exit();
}

// Tételek mentése
if( isset($_POST['tetel_sorok_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    if( $_POST['recid'] == 'false' ) {
        $_POST['recid'] = '';
    }
    $recid                  = $_POST['recid'];
    $szerzodes_netto_erteke = str_replace(array(' ', ','), array('', '.'), $_POST['szerzodes_netto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );

    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $telephely_adat = 1;
    $telj_dt_adat   = 2;
    $netto_ert_adat = 3;
    $valtozott_adat = 4;
    
    $tetelek_osszege = '';
    $i = 0;
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$netto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $_POST['adatok'][$netto_ert_adat][1][$i];
        $i++;
    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$telephely_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy szerződés tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a szerződés nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szerzodes_netto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A szerződés tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a szerződés nettó értékével ('.number_format($szerzodes_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['fennt_szerz_tetel_recid']        = $_POST['adatok'][$recid_adat][1];
        $_SESSION['fennt_szerz_tetel_telephely_id'] = $_POST['adatok'][$telephely_adat][1];
        $_SESSION['fennt_szerz_tetel_telj_dt']      = $_POST['adatok'][$telj_dt_adat][1];
        if( $devizas ) {
            $_SESSION['fennt_szerz_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        } else {
            $_SESSION['fennt_szerz_tetel_netto_ert'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        }
        $_SESSION['fennt_szerz_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }

    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő szerződést szerkesztünk
    if( $recid ) {
        $i = 0;
        foreach ($_POST['adatok'][$telephely_adat][1] as $telephely) {

            $telephely  = $_POST['adatok'][$telephely_adat][1][$i];
            $telj_dt    = $_POST['adatok'][$telj_dt_adat][1][$i];
            $netto      = $_POST['adatok'][$netto_ert_adat][1][$i];
            $valtozott = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }
            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szerzodes_tetel',
                                                array('telephely_id', 'telj_dt', $db_mezo,    'valtozott' ),
                                                array($telephely,     $telj_dt,  $netto,      $valtozott  ), 
                                                "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {
                
                $rownum = $adatbazis_kapcsolat->s2('MAX(`rownum`)', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$recid}'");
                $rownum = $rownum['MAX(`rownum`)']+1;
                $uj_id = $adatbazis_kapcsolat->i('fenntartasi_szerzodes_tetel', 
                                                array('fenntartasi_szerzodes_recid', 'rownum', 'telephely_id', 'telj_dt', $db_mezo, 'valtozott'),
                                                array($recid,                        $rownum,  $telephely,     $telj_dt,  $netto,   '1'        )
                                            );
                if( $sql === false ) { $db_error = true; }
                $_SESSION['fennt_szerz_tetel_recid'][$i] = $uj_id;
            }

            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        if( count($torlendo_idk) ) {
            $sql = $adatbazis_kapcsolat->d('fenntartasi_szerzodes_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk)."')");
            if( $sql === false ) { $db_error = true; }
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1, 'netto' => number_format($tetelek_osszege, $tizedesek, ',', ' ')) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap -> leallit();
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
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
    
    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
        $szerzodes  = $adatbazis_kapcsolat->s2('recid kcs_21', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
    } else if( _jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$_GET['id']}'");
    }
    
    $i = 0;
    $ossz_netto   = 0;
    $ossz_dev_net = 0;
    $_SESSION['fennt_szerz_tetel_recid'] = $_SESSION['fennt_szerz_tetel_telephely_id'] = $_SESSION['fennt_szerz_tetel_telj_dt'] = $_SESSION['fennt_szerz_tetel_netto_ert'] = $_SESSION['fennt_szerz_tetel_dev_net'] = array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['fennt_szerz_tetel_recid'][$i]        = $tetel['recid'];
        $_SESSION['fennt_szerz_tetel_telephely_id'][$i] = $tetel['telephely_id'];
        $_SESSION['fennt_szerz_tetel_telj_dt'][$i]      = $tetel['telj_dt'];
        $_SESSION['fennt_szerz_tetel_netto_ert'][$i]    = number_format($tetel['netto_ert'], 0, '', ' ');
        $_SESSION['fennt_szerz_tetel_dev_net'][$i]      = number_format($tetel['dev_net'],   2, ',', ' ');
        $_SESSION['fennt_szerz_tetel_valtozott'][$i]    = $tetel['valtozott'];
        $_SESSION['fennt_szerz_tetel_andocos'][$i]      = $tetel['andocos'];

        $i++;
    }
    
    $js_submit_elott = 'return form_ell_admin();';
    
    // Gombok kirajzolása minden oldalra
    // Fenntartási szerződés mentésekor ellenőrizzük, hogy a szerződés nettó értéke azonos-e a tételek összegével
    // Deviza esetén más session-t járunk be
    $tizedesek  = 0;
    $mezo1      = 'ossz_netto';
    $mezo2      = 'netto';
    if( $szerzodes['kcs_21']!=KCS_21_HUF ) {
        $tizedesek  = 2;
        $mezo1      = 'ossz_dev_net';
        $mezo2      = 'dev_net';
    }

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
        
        // Új szerződés rögzítésekor ellenőrizni kell, hogy van-e már legalább egy tétel
        // Illetve a tételek összege azonos-e a szerződés nettójával
        $js_submit_elott = nl2('
            
            if( !$(\'#van_tetel\').val() ) {
                alert2(\'Szerződés rögzítéséhez legalább egy tételt töltsön fel!\', \'\', \'Hiba\');
                return false;
            }
            
            var tetelek_ossz = ezresek($(\'#'.$mezo1.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'), '.$tizedesek.', \',\', \' \');
            if( $(\'#'.$mezo2.'\').val() != tetelek_ossz ) {
                alert2(\'A szerződés tételeinek összege (\'+tetelek_ossz+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a szerződés nettó értékével(\'+$(\'#'.$mezo2.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
                return false;
            }
            return form_ell_admin();
            
            ');

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="hidden" name="atmeneti_recid" value="'.$szerzodes['recid'].'">
                <input type="hidden" id="van_tetel" value="">
                <input type="hidden" id="ossz_netto" value="'.$ossz_netto.'">
                <input type="hidden" id="ossz_dev_net" value="'.$ossz_dev_net.'">
                <input type="button" class="gombb mentes_gomb gombb_alert" name="mehet_gomb" id="mehet_gomb" value="Mentés" onclick="if( form_ell_admin_elott() ){ $(\'#szerk_form\').submit(); }">
                <input type="button" class="gomb piros" id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszerzodes_tetelek_kezelese\');"> ';

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
        if( true /* !_jog(... fenntartási szerződés törlés jog ...) */ ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }

        if( _jog(JOG_FENNTARTASI_SZERZ_KEZEL) ){
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                    <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ').$gombok;
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszerzodes_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.number_format($sor['netto'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.number_format($sor['dev_net'], $tizedesek, ',', ' ').'">';
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
    
	// Új rekord felvitele
	if( isset($_GET['uj']) ) {

        // Itt úgy fogunk tenni,mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
        $sor = $adatbazis_kapcsolat->s2('*', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

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

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if( isset($_GET['id_hidden']) ) {
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

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

	// Szerződés tételek
    } elseif( isset($_GET['tetel']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Teljesítés</div>
                    <div class="dtc r">Nettó érték</div>
                    <div class="dtc r">Valuta nettó érték</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('telephely_id, telj_dt, netto_ert, dev_net', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$_GET['id']}'", 'telj_dt');
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.getSzallodaNev($tetel['telephely_id']).'</div>
                    <div class="dtc">'.datum_kiir($tetel['telj_dt']).'</div>
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