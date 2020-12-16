<?php
require_once('../weblap.php');

if( !_jog(JOG_FENNTARTASI_SZAMLAK_OLVAS) ) {
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
$akt_tomb               = $fenntartasi_szamla_tomb;
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

// Köztes tábla számláinak kilistázása
if( isset($_POST['fenntartasi_szamla_get_koztes']) ) {
    
    // Köztes számlákból készítsünk táblázatot
    $html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szallito_id szla_szam telj_dt', 'fenntartasi_szamla_atmeneti', "");
    if( $szamlak ) {
        $html .= nl2('
            <table class="csikos szamla_cont_tabla" id="pop_tetelek">
                <thead>
                <tr>
                    <th class="l">Szálloda</th>
                    <th class="l">Szállító</th>
                    <th class="l">Számlaszám</th>
                    <th class="l">Teljesítés dáma</th>
                </tr>
                </thead>
                <tbody>
            ');
        foreach( $szamlak as $szamla ) {
            $html .= nl2('
                    <tr data-andoc_id="'.$szamla['andoc_id'].'">
                        <td>'.getSzallodaNev($szamla['telephely_id']).'</td>
                        <td>'.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']).'</td>
                        <td>'.htmlspecialchars($szamla['szla_szam']).'</td>
                        <td>'.datum_kiir($szamla['telj_dt']).'</td>
                    </tr>
                ');
        }
        $html .= nl2('
                <tbody>
                </table>
            ');
    } else {
        $html .= '<script>popupZar(); alert2(\'Jelenleg nem található feldolgozandó számla.\', \'\', \'Hiba\')</script>';
    }
    
    echo json_encode( array('html' => $html) );
    
    $weblap -> leallit();
    exit;
}

// Számla igazolása
if( isset($_POST['szamla_igazol']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha már igazolva volna, akkor hibaüzenet
    $sor = $adatbazis_kapcsolat->s2('igazolva_dt', 'fenntartasi_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
        echo json_encode(array(
                                'hiba'  => 'A számla már igazolva van, így nem igazolható újból!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(SZAMLAK_IGAZOLASA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát igazolni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Adatbázisba mentés
    $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla', array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
                                                         array(1,          'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   ),
                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Illetve az XML visszát is intézzük
    if( xml_vissza($_POST['id']) !== false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap -> leallit();
    exit;
}

// Számla elutasít
if( isset($_POST['fenntartasiSzamlaElutasit']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(SZAMLAK_IGAZOLASA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga számlát elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }
    
    // Adatbázisba mentés
    $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla', array('igazolva', 'elutleir',     'igazolva_dt', 'igazolva_usr'                           ),
                                                         array(0,          $_POST['memo'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   ),
                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Illetve az XML visszát is intézzük
    if( xml_vissza($_POST['id']) !== false ) { $db_error = true; }
                                                
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array('siker' => 1 ) );
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba'
        ));
    }
    
    $weblap -> leallit();
    exit;
}

// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['andoc_id'] ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'fenntartasi_szamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szamla = $adatbazis_kapcsolat->s2('kcs_21', 'fenntartasi_szamla', "`recid`='{$_POST['id']}'");
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szamla['kcs_21'] && $szamla['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = getErtekLista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'fennt_szla_tetel_dev_net';
    } else {
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'fennt_szla_tetel_netto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i = 0;
    foreach( $_SESSION['fennt_szla_tetel_targy'] as $targy ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$i];
        $torles = '';
        if( !$_SESSION['fennt_szla_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['fennt_szla_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['fennt_szla_tetel_recid'][$i] ) {
            $_SESSION['fennt_szla_tetel_recid'][$i] = time().rand(0,1000);
        }
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['fennt_szla_tetel_recid'][$i].'">
                            <td class="pop_targy l">'.htmlspecialchars($_SESSION['fennt_szla_tetel_targy'][$i]).'</td>
                            <td class="pop_netto_ert r">'.$_SESSION[$session_mezo][$i].'</td>
                            <td class="pop_ktgnem_autocomp l">'.getErtek('nev', 'ktgnem',  'id', $_SESSION['fennt_szla_tetel_ktgnem'][$i]).'</td>
                            <td class="pop_ktghely_autocomp l">
                                '.getErtek('nev', 'ktghely', 'id', $_SESSION['fennt_szla_tetel_ktghely'][$i]).'
                            </td>
                            <td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['fennt_szla_tetel_recid'][$i].'\');">
                                    🖉
                                </a>
                            </td>
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_ktgnem" name="pop_ktgnem_id" value="'.$_SESSION['fennt_szla_tetel_ktgnem'][$i].'">
                                <input type="hidden" class="pop_ktghely" name="pop_ktghely_id" value="'.$_SESSION['fennt_szla_tetel_ktghely'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['fennt_szla_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['fennt_szla_tetel_recid'][$i].'">
                            </td>
                        </tr>');
        $i++;
    }
   
    echo json_encode( array(
        'html' => $html,
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
    $szamla_netto_erteke    = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_netto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );

    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $targy_adat     = 1;
    $netto_ert_adat = 2;
    $ktgnem_adat    = 3;
    $ktghely_adat   = 4;
    $valtozott_adat = 5;
    
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
    if( !$_POST['adatok'][$targy_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['fennt_szla_tetel_recid']     = $_POST['adatok'][$recid_adat][1];
        $_SESSION['fennt_szla_tetel_targy']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$targy_adat][1]);
        if( $devizas ) {
            $_SESSION['fennt_szla_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        } else {
            $_SESSION['fennt_szla_tetel_netto_ert'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        }
        $_SESSION['fennt_szla_tetel_ktgnem']    = $_POST['adatok'][$ktgnem_adat][1];
        $_SESSION['fennt_szla_tetel_ktghely']   = $_POST['adatok'][$ktghely_adat][1];
        $_SESSION['fennt_szla_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }
    
    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    if( $recid ) {
        $i = 0;
        foreach ($_POST['adatok'][$targy_adat][1] as $targy) {

            $netto   = $_POST['adatok'][$netto_ert_adat][1][$i];
            $targy   = $_POST['adatok'][$targy_adat][1][$i];
            $ktgnem  = $_POST['adatok'][$ktgnem_adat][1][$i];
            $ktghely = $_POST['adatok'][$ktghely_adat][1][$i];
            $valtozott = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }
            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla_tetel',
                                                array('targy', $db_mezo, 'ktgnem_id',    'ktghely_id',  'valtozott' ),
                                                array($targy,  $netto,   $ktgnem,        $ktghely,      $valtozott  ), 
                                                "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
            // Egyébként insert
            } else {
                
                $rownum = $adatbazis_kapcsolat->s2('MAX(`rownum`)', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$recid}'");
                $rownum = $rownum['MAX(`rownum`)']+1;
                $uj_id = $adatbazis_kapcsolat->i('fenntartasi_szamla_tetel', 
                                                array('fenntartasi_szamla_recid', 'rownum', 'targy', $db_mezo, 'ktgnem_id',    'ktghely_id', 'valtozott'),
                                                array($recid,                     $rownum,  $targy,  $netto,   $ktgnem,        $ktghely,     '1'        )
                                            );
                if( $sql === false ) { $db_error = true; }
                $_SESSION['fennt_szla_tetel_recid'][$i] = $uj_id;
            }
            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        if( count($torlendo_idk) ) {
            $sql = $adatbazis_kapcsolat->d('fenntartasi_szamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk)."')");
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['tortenet']) /*|| isset($_GET['kifizetesek'])*/ || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'tortenet'      => 'Történet',
            // 'kifizetesek'   => 'Kifizetések',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db,
        );
        $fulek = '';
        foreach ( $ful_tomb as $key => $value ) {

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
    if( isset($_GET['uj']) && _jog(JOG_FENNTARTASI_SZAMLAK_OLVAS) ) {
        $szamla  = $adatbazis_kapcsolat->s2('recid kcs_21', 'fenntartasi_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel_atmeneti', "`fenntartasi_szamla_recid`='{$szamla['recid']}'");
    } else if( _jog(JOG_FENNTARTASI_SZAMLAK_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_GET['id']}'");
    }
    $i = 0;
    $ossz_netto   = 0;
    $ossz_dev_net = 0;
    $_SESSION['fennt_szla_tetel_recid'] = $_SESSION['fennt_szla_tetel_targy'] = $_SESSION['fennt_szla_tetel_netto_ert'] = $_SESSION['fennt_szla_tetel_dev_net'] = $_SESSION['fennt_szla_tetel_ktgnem'] = $_SESSION['fennt_szla_tetel_ktghely'] = array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['fennt_szla_tetel_recid'][$i]     = $tetel['recid'];
        $_SESSION['fennt_szla_tetel_targy'][$i]     = $tetel['targy'];
        $_SESSION['fennt_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
        $_SESSION['fennt_szla_tetel_dev_net'][$i]   = number_format($tetel['dev_net'],   2, ',', ' ');
        $_SESSION['fennt_szla_tetel_ktgnem'][$i]    = $tetel['ktgnem_id'];
        $_SESSION['fennt_szla_tetel_ktghely'][$i]   = $tetel['ktghely_id'];
        $_SESSION['fennt_szla_tetel_valtozott'][$i] = $tetel['valtozott'];
        $_SESSION['fennt_szla_tetel_andocos'][$i]   = $tetel['andocos'];

        $i++;
    }
    
    // Gombok kirajzolása minden oldalra
    // Fenntartási számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
    // Deviza esetén más session-t járunk be
    $tizedesek  = 0;
    $mezo1      = 'ossz_netto';
    $mezo2      = 'netto';
    if( $szamla['kcs_21']!=KCS_21_HUF ) {
        $tizedesek  = 2;
        $mezo1      = 'ossz_dev_net';
        $mezo2      = 'dev_net';
    }
    
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) ) {
        
        // Új számla rögzítésekor így ellenőrizzük a nettó és tételek összegének azonosságát
        $js_submit_elott = nl2('

            var tetelek_ossz = ezresek($(\'#'.$mezo1.'\').val().replace(/ /g, \'\').replace(/,/g, \'.\'), '.$tizedesek.', \',\', \' \');
            if( $(\'#nullas\').is(\':checked\') == false ) {
                if( $(\'#'.$mezo2.'\').val() != tetelek_ossz ) {
                    alert2(\'A számla tételeinek összege (\'+tetelek_ossz+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a számla nettó értékével(\'+$(\'#'.$mezo2.'\').val()+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
                    return false;
                }
            } else if( $(\'#nullas\').is(\':checked\') == true ) {
                if( tetelek_ossz != \'0\' && tetelek_ossz != \'0,00\' && tetelek_ossz != \'\' ) {
                    alert2(\'Nullás számla esetén a tételek értéke is 0 legyen!<br>Így nem menthető!\', \'\', \'Hiba\');
                    return false;
                }
            }
            return form_ell_admin();
            
            ');
        
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="hidden" name="atmeneti_recid" value="'.$szamla['recid'].'">
                <input type="hidden" id="ossz_netto" value="'.$ossz_netto.'">
                <input type="hidden" id="ossz_dev_net" value="'.$ossz_dev_net.'">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="if( form_ell_admin_elott() ){ $(\'#szerk_form\').submit(); }">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszamla_tetelek_kezelese\');"> ';

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
        if( _jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        if( $sor['igazolva_dt']=='0000-00-00 00:00:00' && _jog(SZAMLAK_IGAZOLASA) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="szamla_igazol()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Elutasítás" onclick="csoportMuvelet(\'fenntartasiSzamlaElutasit\');"> ';
        }
        
        // Szerkesztéshez az kell, hogy legyen jogunk, illetve a számla ne legyen még igazolva/elutasítva
        if( _jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) && $sor['igazolva_dt'] == '0000-00-00 00:00:00' ){
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszamla_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.number_format($sor['netto'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.number_format($sor['dev_net'], $tizedesek, ',', ' ').'">';
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

        // Itt úgy fogunk tenni,mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
        $sor = $adatbazis_kapcsolat->s2('*', 'fenntartasi_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");

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
        
        // Fizetési módot oda vissza konvertálni kell, mert XML-ből a teljes címkét kapjuk, de mi csak egy karaktert tárolunk
        switch ($sor['fizmod']) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
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
        
        // Fizetési módokat szövegesen írjuk ki (amúgy szem szerkeszthető az adatlap)
        switch ($sor['fizmod']) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
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
                    <!--<div class="dtc">Szálloda</div>-->
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Költségnem</div>
                    <div class="dtc r">Nettó érték</div>
                    <div class="dtc r">Bruttó érték</div>
                    <div class="dtc r">Deviza nettó</div>
                    <!--<div class="dtc">Státusz</div>-->
                    <div class="dtc">Költséghely</div>
                    <!--<div class="dtc">Igazoló</div>-->
                    <!--<div class="dtc">Igazolás ideje</div>-->
                    <div class="dtc">Szerz.szám</div>
                    <div class="dtc">Szerz.verzió</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_GET['id']}'", 'igazol_dt');
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <!--<div class="dtc">'.getSzallodaNev($tetel['telephely_id']).'</div>-->
                    <div class="dtc">'.$tetel['targy'].'</div>
                    <div class="dtc">'.getErtek('nev', 'ktgnem', 'id', $tetel['ktgnem_id']).'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                    <!--<div class="dtc">'.$tetel['stat_id'].'</div>-->
                    <div class="dtc">'.getErtek('nev', 'ktghely', 'id', $tetel['ktghely_id']).'</div>
                    <!--<div class="dtc">'.$tetel['igazol_usr'].'</div>-->
                    <!--<div class="dtc">'.datum_kiir($tetel['igazol_dt']).'</div>-->
                    <div class="dtc">'.$tetel['szszam'].'</div>
                    <div class="dtc">'.$tetel['verzio'].'</div>
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

	// Történet
    } elseif( isset($_GET['tortenet']) ) {
        
        $igazolva = $igazolva_dt = $utalvanyozva = $igazol_elut = $utalvanyozva_elut = '';
        if( $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
            $igazolva_dt = datum_kiir($sor['igazolva_dt']);
        }
        if( $sor['utalvanyozva_dt']!='0000-00-00 00:00:00' ) {
            $utalvanyozva_dt = datum_kiir($sor['utalvanyozva_dt']);
        }
        if( $sor['igazolva'] ) {
            $igazolva = '<b class="pipa">✓</b>';
        }
        if( $sor['utalvanyozva'] ) {
            $utalvanyozva = '<b class="pipa">✓</b>';
        }
        // Ha nincs igazolva, de az igazolás időpontja már ismert, akkor tujduk, ez el lett utasítva
        if( !$sor['igazolva'] && $sor['igazolva_dt']!='0000-00-00 00:00:00' ) {
            $igazolva    = '<b class="x">✖</b>';
            $igazol_elut = nl2('
                </div>
                <div class="indoklas">
                    Elutasítás indoklása:
                    <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                </div>
                <div class="dt naplo">
                ');
        }
        if( !$sor['utalvanyozva'] && $sor['utalvanyozva_dt']!='0000-00-00 00:00:00' ) {
            $utalvanyozva       = '<b class="x">✖</b>';
            $utalvanyozva_elut  = nl2('
                </div>
                <div class="indoklas">
                    Elutasítás indoklása:
                    <br>'.htmlspecialchars_decode( nl2br2($sor['elutleir']) ).'
                </div>
                <div class="dt naplo">
                ');
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Esemény</div>
                    <div class="dtc"><b class="pipa">✓</b></div>
                    <div class="dtc">Felhasználó</div>
                    <div class="dtc">Dátum</div>
                </div>

                <div class="dtr nSor">
                    <div class="dtc">ontEAM-be érkezett</div>
                    <div class="dtc"><b class="pipa">✓</b></div>
                    <div class="dtc">'.$sor['ikt_usr'].'</div>
                    <div class="dtc">'.datum_kiir($sor['ont_erk_dt']).'</div>
                </div>
                <div class="dtr nSor">
                    <div class="dtc">Feldolgozva</div>
                    <div class="dtc"><b class="pipa">✓</b></div>
                    <div class="dtc">'.$sor['insert_usr'].'</div>
                    <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                </div>
                <div class="dtr nSor">
                    <div class="dtc">Igazolva</div>
                    <div class="dtc">'.$igazolva.'</div>
                    <div class="dtc">'.$sor['igazolva_usr'].'</div>
                    <div class="dtc">'.$igazolva_dt.'</div>
                </div>
                '.$igazol_elut.'
                <div class="dtr nSor">
                    <div class="dtc">Utalványozva</div>
                    <div class="dtc">'.$utalvanyozva.'</div>
                    <div class="dtc">'.$sor['utalvanyozva_usr'].'</div>
                    <div class="dtc">'.$utalvanyozva_dt.'</div>
                </div>
                '.$utalvanyozva_elut.'
            </div>
        ');
        
        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }
        
	// Kifizetések fül
//    } elseif( isset($_GET['kifizetesek']) ) {
//
//        $szamla_html .= nl2('
//            <div class="dt naplo">
//                <div class="dtr nFejlec">
//                    <div class="dtc">Bank</div>
//                    <div class="dtc">Kifizetve</div>
//                    <div class="dtc r">Bruttó</div>
//                    <div class="dtc r">Deviza bruttó</div>
//                    <div class="dtc">Pénznem</div>
//                    <div class="dtc">Rögzítve</div>
//                    <div class="dtc">Rögzítette</div>
//                </div>
//        ');
//
//        // Tételek felsorolása
//        $kifizetesek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_kifizetesek', "`fenntartasi_szamla_recid`='{$_GET['id']}'", 'insert_dt');
//        foreach( $kifizetesek as $kifizetes ) {
//            
//            $szamla_html .= nl2('
//                <div class="dtr nSor">
//                    <div class="dtc">'.erteklista(ERTEKLISTA_BANK, $kifizetes['bank']).'</div>
//                    <div class="dtc">'.datum_kiir($kifizetes['kifizetes_dt']).'</div>
//                    <div class="dtc r">'.number_format($kifizetes['br_ertek'], 0, '', ' ').'</div>
//                    <div class="dtc r">'.number_format($kifizetes['dev_br'], 2, ',', ' ').'</div>
//                    <div class="dtc">'.erteklista(ERTEKLISTA_PENZNEM, $kifizetes['kcs_21']).'</div>
//                    <div class="dtc">'.datum_kiir($kifizetes['rogzitve_dt'], 'csak dátum').'</div>
//                    <div class="dtc">'.$kifizetes['rogzitette'].'</div>
//                </div>
//                ');
//        }
//        $szamla_html .= '</div>';
//        
//        if( $_GET['ajax'] ) {
//            echo json_encode(array(
//                'tartalom' => $szamla_html.$gombok,
//                'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
//            ));
//        } else {
//            echo $szamla_html.$gombok;
//        }

	// Dokumentumok
    } elseif( isset($_GET['dokumentumok']) ) {

//        if( $tobb_kep ) {
//            require_once('tobb_kep.php');
//        }
        // Itt az XML-ből kapott dokumentum linkjét jelenítjük meg
        if( $sor['dok_link'] ) {
            $szamla_html .= nl2('
                Fődokumentum link:
                <br><input type="text" value="'.$sor['dok_link'].'" readonly style="width: 100%; margin: 1rem 0">
                <br><input type="button" class="gomb" value="Dokumentum megtekintése" onclick="window.open(\''.$sor['dok_link'].'\')">
                <br>Bejövő xml:
                <br>').'<textarea class="oriasiTargy" readonly>'.$sor['erk_xml'].'</textarea>'.nl2('
                <br>            
                <br>Kimenő xml:
                <br>').'<textarea class="oriasiTargy" readonly>'.$sor['kuld_xml'].'</textarea>';
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

// Igazoláskor, elutasításkor, utalványozáskor gyártunk egy XML vissza fájlt, és azt beírjuk az adatbázisba
function xml_vissza( $szamla_id ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    $szamla = $adatbazis_kapcsolat->s2('igazolva andoc_id telephely_id kcs_21 kuld_xml', 'fenntartasi_szamla', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
    
    // Igazolva érték XML-be
    if( $szamla['igazolva'] ) {
        $igazolva = 'Igazolva';
    } else {
        $igazolva = 'Elutasítva';
    }
    
    // XML dekódolása
    $szamla['kuld_xml'] = htmlspecialchars_decode($szamla['kuld_xml']);
    
    // A számla devizája dönti el, hogy a tételbe melyik nettó mező kerüljön
    if( $szamla['kcs_21'] == KCS_21_HUF ) {
        $netto_mezo = 'netto_ert';
    } else {
        $netto_mezo = 'dev_net';
    }

    $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
    if( $tetelek === false ) { $db_error = true; }
    
    $tetel_xml = '';
    $i = 0;
    foreach( $tetelek as $tetel ) {
        
        $afa        = getErtekLista(ERTEKLISTA_AFA_KOD, $tetel['afa_kod']);
        $szalloda   = getSzallodaNev($szamla['telephely_id']);
        $tetel_xml .= '
            <row rownumberonpage="'.$tetel['rownum'].'">
              <property name="Sor megnevezése">'.$tetel['targy'].'</property>
              <property name="Nettó sorösszeg">'.$tetel[$netto_mezo].'</property>
              <property name="Főkönyvi számlaszám">'.$tetel['ktgnem_id'].'</property>
              <property name="Költséghely">'.$tetel['ktghely_id'].'</property>
            </row>
            ';
        $i++;
    }
   
    
    $xml = $szamla['kuld_xml'].
           date('Y-m-d H:i:s').'
<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>[AUTO] Utalványozásra küldés</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Igazoló döntés">'.$igazolva.'</property>
          </row>
        </container>
        <container name="IK1">
          '.$tetel_xml.'
        </container>
      </properties>
    </register>
  </data>
</xml>



';
    
    $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla',    array('kuld_xml'),
                                                            array($xml),
                                    "`recid`='{$szamla_id}'");
    if( $tetelek === false ) { $db_error = true; }
    
    // Tételeknél nullázzuk a változott pipát
    $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla_tetel', array('valtozott'), array(0), "`fenntartasi_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
    if( $sql === false ) { $db_error = true; }
    
    return $db_error;
    
}