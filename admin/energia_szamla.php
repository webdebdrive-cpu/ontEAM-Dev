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

// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['andoc_id'] ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'energia_szamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szamla = $adatbazis_kapcsolat->s2('kcs_21', 'energia_szamla', "`recid`='{$_POST['id']}'");
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szamla['kcs_21'] && $szamla['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = getErtekLista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'energia_szla_tetel_dev_net';
        $session_mezo_br        = 'energia_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'energia_szla_tetel_netto_ert';
        $session_mezo_br        = 'energia_szla_tetel_brutto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i = 0;
    foreach( $_SESSION['energia_szla_tetel_jogcim'] as $jogcim ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$i];
        $torles = '';
        if( !$_SESSION['energia_szla_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['energia_szla_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['energia_szla_tetel_recid'][$i] ) {
            $_SESSION['energia_szla_tetel_recid'][$i] = time().rand(0,1000);
        }

        $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim', "`recid`='{$_SESSION['energia_szla_tetel_jogcim'][$i]}'");
        $dijelem = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$_SESSION['energia_szla_tetel_dijelem'][$i]}'");
        $aranyos = '';
        if( $_SESSION['energia_szla_tetel_aranyos'][$i] ) {
            $aranyos .= '✓';
        }
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['energia_szla_tetel_recid'][$i].'">
                            <td class="pop_jogcim_txt">'.$jogcim['jogcim'].'</td>
                            <td class="pop_dijelem_txt">'.$dijelem['info'].'</td>
                            <td class="pop_aranyos">'.$aranyos.'</td>
                            <td class="l">
                                <span class="pop_tol">'.$_SESSION['energia_szla_tetel_tol'][$i].'</span> -
                                <span class="pop_ig">'.$_SESSION['energia_szla_tetel_ig'][$i].'</span>
                            </td>
                            <td class="l">
                                <span class="pop_indul">'.$_SESSION['energia_szla_tetel_indul'][$i].'</span> -
                                <span class="pop_zar">'.$_SESSION['energia_szla_tetel_zar'][$i].'</span>
                            </td>
                            <td class="pop_szorzo">'.$_SESSION['energia_szla_tetel_szorzo'][$i].'</td>
                            <td class="pop_mert">'.$_SESSION['energia_szla_tetel_mert'][$i].'</td>
                            <td class="pop_kwh_szorzo">'.$_SESSION['energia_szla_tetel_kwh_szorzo'][$i].'</td>
                            <td class="pop_menny">'.$_SESSION['energia_szla_tetel_menny'][$i].'</td>
                            <td class="pop_egyseg">'.$_SESSION['energia_szla_tetel_egyseg'][$i].'</td>
                            <td class="pop_menny_kwh">'.$_SESSION['energia_szla_tetel_menny_kwh'][$i].'</td>
                            <td class="pop_egyseg_ar">'.$_SESSION['energia_szla_tetel_egyseg_ar'][$i].'</td>
                            <td class="pop_dij">'.$_SESSION['energia_szla_tetel_dij'][$i].'</td>
                            <td class="pop_afa">'.$_SESSION['energia_szla_tetel_afa'][$i].'</td>
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_jogcim" name="pop_jogcim_id" value="'.$_SESSION['energia_szla_tetel_jogcim'][$i].'">
                                <input type="hidden" class="pop_dijelem" name="pop_dijelem_id" value="'.$_SESSION['energia_szla_tetel_dijelem'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['energia_szla_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['energia_szla_tetel_recid'][$i].'">
                            </td>
                        </tr>');
        $i++;
    }
    
    require_once 'selectek.php';
    
    // Jogcím select előállítása
    $jogcim_select = '<select id="pop_jogcim" onchange="dijelem_select()" class="csillag">';
    foreach ( $select_jogcim as $jogcim ) {
        $jogcim_select .= '<option value="'.$jogcim['value'].'">'.$jogcim['name'].'</option>';
    }
    $jogcim_select .= '</select>';
    
    echo json_encode( array(
                        'html'              => $html,
                        'jogcim_select'     => $jogcim_select,
                    ) );
    
    $weblap -> leallit();
    exit();
}

// Díjelem select előállítása
if( isset($_POST['dijelem_select']) ) {

    $dijelem_select  = '<select id="pop_dijelem" class="csillag" onchange="dijelem_ertek_betolt()">';
    $dijelem_select .= '<option value="">-Válasszon-</option>';
    $jogcim     = $adatbazis_kapcsolat->s2('szla_szam', 'jogcim', "`recid`='{$_POST['jogcim_recid']}'");
    $erteklista = $adatbazis_kapcsolat->s('recid info', 'erteklista', "`csoport`='".ERTEKLISTA_DIJELEM."' AND `ktgnem`='{$jogcim['szla_szam']}'");
    foreach ( $erteklista as $ertek ) {
        $dijelem_select .= '<option value="'.$ertek['recid'].'">'.$ertek['info'].'</option>';
    }
    $dijelem_select .= '</select>';
    
    echo json_encode( array(
                        'dijelem_select' => $dijelem_select,
                    ) );
    
    $weblap -> leallit();
    exit();
}

// Díjelemet kiválasztva az értéklista mezőit töltsük be 
if( isset($_POST['dijelem_ertek_betolt']) ) {

    $erteklista = $adatbazis_kapcsolat->s2('mee afa kwh_szorzo', 'erteklista', "`recid`='{$_POST['dijelem_recid']}'");
    
    // Ha csak egy érétk volna benne, akkor nem szerkeszthető
    // (vesszővel felsorolva több érték is lehet benne)
    $meek  = explode(',', $erteklista['mee']);
    $disabled = '';
    $elso_option = '<option value="">-Válasszon-</option>';
    
    if( count($meek)==1 ) {
        $disabled    = ' disabled';
        if( $mee[0] ) {
            $elso_option = '';
        }
    }
    
    $mee_select  = '<select id="pop_egyseg" class="csillag"'.$disabled.'>';
    $mee_select .= $elso_option;
    foreach ( $meek as $mee ) {
        $mee = trim($mee);
        $mee_select .= '<option value="'.$mee.'">'.$mee.'</option>';
    }
    $kwh_szorzo .= '</select>';
    echo json_encode( array(
                        'mee'       => $mee_select,
                        'afa'       => str_replace('.', ',', $erteklista['afa']),
                        'kwh_szorzo'=> str_replace('.', ',', $erteklista['kwh_szorzo']),
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
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    $arfolyam               = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $jogcim_adat    = 1;
    $dijelem_adat   = 2;
    $aranyos_adat   = 3;
    $tol_adat       = 4;
    $ig_adat        = 5;
    $indul_adat     = 6;
    $zar_adat       = 7;
    $szorzo_adat    = 8;
    $mert_adat      = 9;
    $kwh_szorzo_adat= 10;
    $menny_adat     = 11;
    $egyseg_adat    = 12;
    $menny_kwh_adat = 13;
    $egyseg_ar_adat = 14;
    $dij_adat       = 15;
    $afa_adat       = 16;
    
// Tétel összeg ellenőrzése - Egyelőre ilyen működés nem kell
// Ennek van egy 2. fele később az ellenőrzéseknél
//    $tetelek_osszege = 0;
//    $i = 0;
//    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
//        $_POST['adatok'][$netto_ert_adat] [1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
//        $tetelek_osszege += $_POST['adatok'][$netto_ert_adat] [1][$i];
//        $i++;
//    }
//    $tetelek_br_osszege = 0;
//    $i = 0;
//    foreach ( $_POST['adatok'][$brutto_ert_adat][1] as $ertek ) {
//        $_POST['adatok'][$brutto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
//        $tetelek_br_osszege += $_POST['adatok'][$brutto_ert_adat][1][$i];
//        $i++;
//    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
    }

    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$jogcim_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
//    // Ha a számla nettó értéke nem egyenlő a tételek nettó összegével, akkor hibaüzenet
//    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
//        echo json_encode( array( 
//                        'hiba'  => 'A számla tételeinek nettó összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
//                        'title' => 'Hiba'
//                        ) );
//                $weblap->leallit();
//                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['energia_szla_tetel_recid']     = $_POST['adatok'][$recid_adat][1];
        $_SESSION['energia_szla_tetel_jogcim']    = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['energia_szla_tetel_dijelem']   = $_POST['adatok'][$dijelem_adat][1];
        $_SESSION['energia_szla_tetel_aranyos']   = $_POST['adatok'][$aranyos_adat][1];
        $_SESSION['energia_szla_tetel_tol']       = $_POST['adatok'][$tol_adat][1];
        $_SESSION['energia_szla_tetel_ig']        = $_POST['adatok'][$ig_adat][1];
        $_SESSION['energia_szla_tetel_indul']     = $_POST['adatok'][$indul_adat][1];
        $_SESSION['energia_szla_tetel_zar']       = $_POST['adatok'][$zar_adat][1];
        $_SESSION['energia_szla_tetel_szorzo']    = $_POST['adatok'][$szorzo_adat][1];
        $_SESSION['energia_szla_tetel_mert']      = $_POST['adatok'][$mert_adat][1];
        $_SESSION['energia_szla_tetel_kwh_szorzo']= $_POST['adatok'][$kwh_szorzo_adat][1];
        $_SESSION['energia_szla_tetel_menny']     = $_POST['adatok'][$menny_adat][1];
        $_SESSION['energia_szla_tetel_egyseg']    = $_POST['adatok'][$egyseg_adat][1];
        $_SESSION['energia_szla_tetel_menny_kwh'] = $_POST['adatok'][$menny_kwh_adat][1];
        $_SESSION['energia_szla_tetel_egyseg_ar'] = $_POST['adatok'][$egyseg_ar_adat][1];
        $_SESSION['energia_szla_tetel_dij']       = $_POST['adatok'][$dij_adat][1];
        $_SESSION['energia_szla_tetel_afa']       = $_POST['adatok'][$afa_adat][1];
        $_SESSION['energia_szla_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }
    
    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    if( $recid ) {
        $i = 0;
        foreach ( $_POST['adatok'][$jogcim_adat][1] as $jogcim ) {
            
            
            
            $jogcim    = $_POST['adatok'][$jogcim_adat][1][$i];
            $dijelem   = $_POST['adatok'][$dijelem_adat][1][$i];
            $aranyos   = $_POST['adatok'][$aranyos_adat][1][$i];
            $tol       = $_POST['adatok'][$tol_adat][1][$i];
            $ig        = $_POST['adatok'][$ig_adat][1][$i];
            $indul     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$indul_adat][1][$i]);
            $zar       = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$zar_adat][1][$i]);
            $szorzo    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$szorzo_adat][1][$i]);
            $mert      = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$mert_adat][1][$i]);
            $kwh_szorzo= str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$kwh_szorzo_adat][1][$i]);
            $menny     = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_adat][1][$i]);
            $egyseg    = $_POST['adatok'][$egyseg_adat][1][$i];
            $menny_kwh = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$menny_kwh_adat][1][$i]);
            $egyseg_ar = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$egyseg_ar_adat][1][$i]);
            $dij       = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$dij_adat][1][$i]);
            $afa       = $_POST['adatok'][$afa_adat][1][$i];
            $valtozott = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }

            // Jogcím esetén még a ktgnem-et is letároljuk
            $jogcim = $adatbazis_kapcsolat->s2('recid szla_szam', 'jogcim', "`recid`='{$jogcim}'");

            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                
                $mezok   = array('jogcim_recid',    'ktgnem_id',           'dijelem_recid', 'aranyos', 'tol', 'ig',   'indul', 'zar', 'szorzo',   'mert', 'kwh_szorzo', 'menny', 'egyseg', 'menny_kwh', 'egyseg_ar', 'dij', 'afa', 'valtozott', 'update_usr'                             );
                $ertekek = array($jogcim['recid'],  $jogcim['szla_szam'],  $dijelem,        $aranyos,  $tol,  $ig,    $indul,  $zar,  $szorzo,    $mert,  $kwh_szorzo,  $menny,  $egyseg,  $menny_kwh,  $egyseg_ar,  $dij,  $afa,  $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );

                $sql = $adatbazis_kapcsolat->u('energia_szamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {

                $rownum = $adatbazis_kapcsolat->s2('MAX(`rownum`)', 'energia_szamla_tetel', "`energia_szamla_recid`='{$recid}'");
                $rownum = $rownum['MAX(`rownum`)']+1;
                $mezok   = array('energia_szamla_recid', 'rownum', 'jogcim_recid',    'ktgnem_id',           'dijelem_recid', 'aranyos', 'tol', 'ig',   'indul', 'zar', 'szorzo',   'mert', 'kwh_szorzo', 'menny', 'egyseg', 'menny_kwh', 'egyseg_ar', 'dij', 'afa', 'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                $ertekek = array($recid,                 $rownum,  $jogcim['recid'],  $jogcim['szla_szam'],  $dijelem,        $aranyos,  $tol,  $ig,    $indul,  $zar,  $szorzo,    $mert,  $kwh_szorzo,  $menny,  $egyseg,  $menny_kwh,  $egyseg_ar,  $dij,  $afa,  '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );

                $uj_id = $adatbazis_kapcsolat->i('energia_szamla_tetel', $mezok, $ertekek);
                if( $sql === false ) { $db_error = true; }
                $_SESSION['energia_szla_tetel_recid'][$i] = $uj_id;

            }
            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        if( count($torlendo_idk) ) {
            $sql = $adatbazis_kapcsolat->d('energia_szamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk)."')");
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

    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {
        $szamla  = $adatbazis_kapcsolat->s2('recid kcs_21', 'energia_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel_atmeneti', "`energia_szamla_recid`='{$szamla['recid']}'");
    } else if( _jog(JOG_ENERGIA_SZAMLAK_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_GET['id']}'");
    }
    $i = 0;
    $_SESSION['energia_szla_tetel_recid']       =
    $_SESSION['energia_szla_tetel_jogcim']      =
    $_SESSION['energia_szla_tetel_dijelem']     =
    $_SESSION['energia_szla_tetel_aranyos']     =
    $_SESSION['energia_szla_tetel_tol']         =
    $_SESSION['energia_szla_tetel_ig']          =
    $_SESSION['energia_szla_tetel_indul']       =
    $_SESSION['energia_szla_tetel_zar']         =
    $_SESSION['energia_szla_tetel_szorzo']      =
    $_SESSION['energia_szla_tetel_mert']        =
    $_SESSION['energia_szla_tetel_kwh_szorzo']  =
    $_SESSION['energia_szla_tetel_menny']       =
    $_SESSION['energia_szla_tetel_egyseg']      =
    $_SESSION['energia_szla_tetel_menny_kwh']   =
    $_SESSION['energia_szla_tetel_egyseg_ar']   =
    $_SESSION['energia_szla_tetel_dij']         =
    $_SESSION['energia_szla_tetel_afa']         =
    $_SESSION['energia_szla_tetel_valtozott']   =
    $_SESSION['energia_szla_tetel_andocos']     =
                                                    array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['energia_szla_tetel_recid'][$i]       = $tetel['recid'];
        $_SESSION['energia_szla_tetel_dijelem'][$i]     = $tetel['dijelem_recid'];
        $_SESSION['energia_szla_tetel_jogcim'][$i]      = $tetel['jogcim_recid'];
        $_SESSION['energia_szla_tetel_aranyos'][$i]     = $tetel['aranyos'];
        $_SESSION['energia_szla_tetel_tol'][$i]         = $tetel['tol'];
        $_SESSION['energia_szla_tetel_ig'][$i]          = $tetel['ig'];
        $_SESSION['energia_szla_tetel_indul'][$i]       = number_format($tetel['indul'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_zar'][$i]         = number_format($tetel['zar'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_szorzo'][$i]      = number_format($tetel['szorzo'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_mert'][$i]        = number_format($tetel['mert'], 5, ',', ' ');
        $_SESSION['energia_szla_tetel_kwh_szorzo'][$i]  = number_format($tetel['kwh_szorzo'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_menny'][$i]       = number_format($tetel['menny'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_egyseg'][$i]      = $tetel['egyseg'];
        $_SESSION['energia_szla_tetel_menny_kwh'][$i]   = number_format($tetel['menny_kwh'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_egyseg_ar'][$i]   = number_format($tetel['egyseg_ar'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_dij'][$i]         = number_format($tetel['dij'], 4, ',', ' ');
        $_SESSION['energia_szla_tetel_afa'][$i]         = $tetel['afa'];
        $_SESSION['energia_szla_tetel_valtozott'][$i]   = $tetel['valtozott'];
        $_SESSION['energia_szla_tetel_andocos'][$i]     = $tetel['andocos'];

        $i++;
    }
    
    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');
        
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'energiaszamla_tetelek_kezelese\');"> ';

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
        if( !_jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'energiaszamla_tetelek_kezelese\');"> ';
        }

        if( _jog(JOG_ENERGIA_SZAMLAK_KEZEL) ) {
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
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
                    <div class="dtc">Jogcím</div>
                    <div class="dtc">Díjelem</div>
                    <div class="dtc">Fogyasztással arányos</div>
                    <div class="dtc">Vonatkozó időszak</div>
                    <div class="dtc">Mérőállás</div>
                    <div class="dtc">Szorzó / Fűtőérték</div>
                    <div class="dtc">Mért fogyasztás</div>
                    <div class="dtc">kWh-szorzó</div>
                    <div class="dtc">Mennyiség (számlázási egységben)</div>
                    <div class="dtc">Mennyiség-egység</div>
                    <div class="dtc">Mennyiség (kWh-ban)</div>
                    <div class="dtc">Egységár</div>
                    <div class="dtc">Díj</div>
                    <div class="dtc">Áfa</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'energia_szamla_tetel', "`energia_szamla_recid`='{$_GET['id']}'", 'rownum');
        foreach ( $tetelek as $tetel ) {
            
            $jogcim  = $adatbazis_kapcsolat->s2('jogcim', 'jogcim', "`recid`='{$tetel['jogcim_recid']}'");
            $dijelem = $adatbazis_kapcsolat->s2('info', 'erteklista', "`recid`='{$tetel['dijelem_recid']}'");
            $aranyos = '';
            if( $tetel['aranyos'] ) {
                $aranyos .= '✓';
            }
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$jogcim['jogcim'].'</div>
                    <div class="dtc">'.$dijelem['info'].'</div>
                    <div class="dtc c">'.$aranyos.'</div>
                    <div class="dtc nowrap">'.datum_kiir($tetel['tol']).'-<br>'.datum_kiir($tetel['ig']).'</div>
                    <div class="dtc nowrap">'.number_format($tetel['indul'], 4, ',', ' ').'-<br>'.number_format($tetel['zar'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['szorzo'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['mert'], 5, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['kwh_szorzo'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['menny'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['egyseg'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['menny_kwh'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['egyseg_ar'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dij'], 4, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['afa'], 0, ',', ' ').'</div>
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