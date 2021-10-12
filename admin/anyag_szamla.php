<?php
require_once('../weblap.php');

if( !_jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
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
$akt_tomb               = $anyag_szamla_tomb;
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

// Mennyiségi egység feltöltése a cikktörzs alapján
if( isset($_POST['onteam_id_mee_kitolt']) ) {
    
    $cikk = $adatbazis_kapcsolat->s2('id cikkmee', 'cikktorzs', "`recid`={$_POST['recid']}");
    echo json_encode(array(
                        'cikkmee'   => $cikk['cikkmee'],
                        'id'        => $cikk['id'],
                        ));
    
    $weblap -> leallit();
    exit;
}

// Jogcím autocomplete alapján a költségnem és hely feltöltése
if( isset($_POST['ktgnem_ktghely_kitolt']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    $jogcim  = $adatbazis_kapcsolat->s2('szla_szam ktghely ktghely_id megn', 'jogcim', "`recid`='{$_POST['jogcim_recid']}'");
    if( $jogcim === false ) { $db_error = true; }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'ktgnem_id' => $jogcim['szla_szam'],
            'ktghely_id'=> $jogcim['ktghely_id'],
            'ktgnem'    => $jogcim['megn'],
            'ktghely'   => $jogcim['ktghely'],
        ));
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

// Számla küldése igazolásra
if( isset($_POST['kuldes_igazolasra']) ) {
    
    $sor = $adatbazis_kapcsolat->s2('feldolgozva_dt', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
        
        echo json_encode(array(
                            'hiba'  => 'A számla már feldolgozott, így nem küldhető újra igazolásra!',
                            'title' => 'Hiba',
                            ));
        
        $weblap -> leallit();
        exit;
    }
    
    $adatbazis_kapcsolat->u('anyag_szamla',  array('feldolgozva_dt', 'feldolgozva_usr'                       ),
                                             array('NOW()',          $_SESSION[get_db_tabla().'teljes_nev']  ),
                                             "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
                        'siker' => 1,
                        ));
    
    $weblap -> leallit();
    exit;
}

// Köztes tábla számláinak kilistázása
if( isset($_POST['anyag_szamla_get_koztes']) ) {
    
    // Köztes számlákból készítsünk táblázatot
    $html = '';
    $szamlak = $adatbazis_kapcsolat->s('recid andoc_id telephely_id szallito_id szla_szam telj_dt', 'anyag_szamla_atmeneti', "");
    if( $szamlak ) {
        $html .= nl2('
            <table class="csikos szamla_cont_tabla" id="pop_tetelek">
                <thead>
                <tr>
                    <th class="l">Szálloda</th>
                    <th class="l">Szállító</th>
                    <th class="l">Számlaszám</th>
                    <th class="c">Teljesítés dátuma</th>
                </tr>
                </thead>
                <tbody>
            ');
        foreach( $szamlak as $szamla ) {
            $html .= nl2('
                    <tr data-andoc_id="'.$szamla['andoc_id'].'">
                        <td class="l">'.getSzallodaNev($szamla['telephely_id']).'</td>
                        <td class="l">'.getErtek('nev', 'szallito', 'id', $szamla['szallito_id']).'</td>
                        <td class="l">'.htmlspecialchars($szamla['szla_szam']).'</td>
                        <td class="c">'.datum_kiir($szamla['telj_dt']).'</td>
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
    $sor = $adatbazis_kapcsolat->s2('igazolva_dt', 'anyag_szamla', "`recid`='{$_POST['id']}'");
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
    
    
    // Illetve az XML visszát is intézzük
    if( xml_vissza($_POST['id'], 'Igazolva', '') !== false ) {
        echo json_encode(array(
                                'hiba'  => 'Az Andoc felé küldött visszaigazoló XML elküldése során hiba történt!<br>A számlát nem sikerült igazolni.',
                                'title' => 'Hiba',
                        ));
        mysqli_commit($db_kapcs);
        $weblap -> leallit();
        exit;
        
    // Adatbázisba mentés csak akkor, ha nem volt gond az XML küldéssel
    } else {
        $sql = $adatbazis_kapcsolat->u('anyag_szamla', array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
                                                       array(1,          'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }        
    }
    
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
if( isset($_POST['anyagSzamlaElutasit']) ) {
    
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
    
    // Illetve az XML visszát is intézzük
    if( xml_vissza($_POST['id'], 'Elutasítva', $_POST['memo']) !== false ) {
        echo json_encode(array(
                                'hiba'  => 'Az Andoc felé küldött visszaigazoló XML elküldése során hiba történt!<br>A számlát nem sikerült elutasítani.',
                                'title' => 'Hiba',
                        ));
        mysqli_commit($db_kapcs);
        $weblap -> leallit();
        exit;
        
    // Adatbázisba mentés csak akkor, ha nem volt gond az XML küldéssel
    } else {
        $sql = $adatbazis_kapcsolat->u('anyag_szamla',    array('igazolva', 'elutleir',     'igazolva_dt', 'igazolva_usr'                           ),
                                                          array(0,          $_POST['memo'], 'NOW()',       $_SESSION[get_db_tabla().'teljes_nev']   ),
                                        "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
    }
                                                
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
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    } else {
        $szamla = $adatbazis_kapcsolat->s2('kcs_21', 'anyag_szamla', "`recid`='{$_POST['id']}'");
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $szamla['kcs_21'] && $szamla['kcs_21']!=KCS_21_HUF ) {
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = getErtekLista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'anyag_szla_tetel_dev_net';
        $session_mezo_br        = 'anyag_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'anyag_szla_tetel_netto_ert';
        $session_mezo_br        = 'anyag_szla_tetel_brutto_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $html = '';
    $i = 0;
    foreach( $_SESSION['anyag_szla_tetel_cikktorzs_id'] as $cikktorzs_id ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$i];
        $torles = '';
        if( !$_SESSION['anyag_szla_tetel_andocos'][$i] ) {
            $torles = '
                        <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'\');">
                            <span class="icon">
                                <i class="material-icons">
                                    delete
                                </i>
                            </span>
                        </a>
                ';
        }
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['anyag_szla_tetel_recid'][$i] ) {
            $_SESSION['anyag_szla_tetel_recid'][$i] = time().rand(0,1000);
        }
        $class = '';
        
        $jogcim = $adatbazis_kapcsolat->s2('jogcim ktghely megn', 'jogcim', "`recid`='{$_SESSION['anyag_szla_tetel_jogcim'][$i]}'");
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'">
                            <td class="pop_cikktorzs l">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikktorzs'][$i]).'</td>
                            <td class="pop_szall_csz l">'.htmlspecialchars($_SESSION['anyag_szla_tetel_szall_csz'][$i]).'</td>
                            <td class="pop_cikktorzs_id c">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikktorzs_id'][$i]).'</td>
                            <td class="pop_cikkmee c">'.htmlspecialchars($_SESSION['anyag_szla_tetel_cikkmee'][$i]).'</td>
                            <td class="pop_netto_ert r nowrap">'.$_SESSION[$session_mezo][$i].'</td>
                            <td class="pop_brutto_ert r nowrap">'.$_SESSION[$session_mezo_br][$i].'</td>
                            <td class="pop_jogcim l">'.htmlspecialchars($jogcim['jogcim']).'</td>
                            <td class="pop_ktgnem_autocomp l">
                                '.$jogcim['megn'].'
                            </td>
                            <td class="pop_ktghely_autocomp l">
                                '.$jogcim['ktghely'].'
                            </td>
                            <td>
                                '.$anyagrendeles/* Még nincs kész */.'
                            </td>
                            <!--<td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['anyag_szla_tetel_recid'][$i].'\');">
                                    🖉
                                </a>
                            </td>-->
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_afakod" name="pop_afakod_id" value="'.$_SESSION['anyag_szla_tetel_afakod'][$i].'">
                                <input type="hidden" class="pop_jogcim" name="pop_jogcim_id" value="'.$_SESSION['anyag_szla_tetel_jogcim'][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['anyag_szla_tetel_valtozott'][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['anyag_szla_tetel_recid'][$i].'">
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
    $szamla_brutto_erteke   = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_brutto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    $arfolyam               = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat         = 0;
    $cikktorzs_adat     = 1;
    $szall_csz_adat     = 2;
    $cikktorzs_id_adat  = 3;
    $cikkmee_adat       = 4;
    $netto_ert_adat     = 5;
    $brutto_ert_adat    = 6;
    $afakod_adat        = 7;
    $jogcim_adat        = 8;
    $valtozott_adat     = 9;
    
    $tetelek_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$netto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $_POST['adatok'][$netto_ert_adat] [1][$i];
        $i++;
    }
    $tetelek_br_osszege = 0;
    $i = 0;
    foreach ( $_POST['adatok'][$brutto_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$brutto_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_br_osszege += $_POST['adatok'][$brutto_ert_adat][1][$i];
        $i++;
    }

    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    $db_mezo_br= 'brutto_ert';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $db_mezo_br= 'dev_br';
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$cikktorzs_id_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy számla tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla nettó értéke nem egyenlő a tételek nettó összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_osszege, $tizedesek, ',', ' ') != number_format($szamla_netto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek nettó összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla nettó értékével ('.number_format($szamla_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Ha a számla bruttó értéke nem egyenlő a tételek bruttó összegével, akkor hibaüzenet
    } elseif( number_format($tetelek_br_osszege, $tizedesek, ',', ' ') != number_format($szamla_brutto_erteke, $tizedesek, ',', ' ') ) {
        echo json_encode( array( 
                        'hiba'  => 'A számla tételeinek bruttó összege ('. number_format($tetelek_br_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a számla bruttó értékével ('.number_format($szamla_brutto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        $_SESSION['anyag_szla_tetel_recid']         = $_POST['adatok'][$recid_adat][1];
        $_SESSION['anyag_szla_tetel_cikktorzs']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$cikktorzs_adat][1]);
        $_SESSION['anyag_szla_tetel_szall_csz']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$szall_csz_adat][1]);
        $_SESSION['anyag_szla_tetel_cikktorzs_id']  = $_POST['adatok'][$cikktorzs_id_adat][1];
        $_SESSION['anyag_szla_tetel_cikkmee']       = $_POST['adatok'][$cikkmee_adat][1];
        if( $devizas ) {
            $_SESSION['anyag_szla_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['anyag_szla_tetel_dev_br']  = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        } else {
            $_SESSION['anyag_szla_tetel_netto_ert']  = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['anyag_szla_tetel_brutto_ert'] = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        }
        $_SESSION['anyag_szla_tetel_afakod']    = $_POST['adatok'][$afakod_adat][1];
        $_SESSION['anyag_szla_tetel_jogcim']    = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['anyag_szla_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }

    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    if( $recid ) {
        $i = 0;
        foreach ($_POST['adatok'][$cikktorzs_id_adat][1] as $cikktorzs_id) {

            $cikktorzs      = $_POST['adatok'][$cikktorzs_adat][1][$i];
            $szall_csz      = $_POST['adatok'][$szall_csz_adat][1][$i];
            $cikktorzs_id   = $_POST['adatok'][$cikktorzs_id_adat][1][$i];
            $cikkmee        = $_POST['adatok'][$cikkmee_adat][1][$i];
            $netto          = $_POST['adatok'][$netto_ert_adat][1][$i];
            $brutto         = $_POST['adatok'][$brutto_ert_adat][1][$i];
            $afakod         = $_POST['adatok'][$afakod_adat][1][$i];
            $targy          = $_POST['adatok'][$targy_adat][1][$i];
            $jogcim         = $_POST['adatok'][$jogcim_adat][1][$i];
            $ktgnem         = $_POST['adatok'][$ktgnem_adat][1][$i];
            $ktghely        = $_POST['adatok'][$ktghely_adat][1][$i];
            $valtozott      = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }
            
            // Jogcím/Áfakód esetén nem csak egy recid-t tárolunk le, hanem a tábla oszlopait, ezért lekérjük a megfelelő sort
            $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");
            $afakod = $adatbazis_kapcsolat->s2('recid nev kod afa', 'afakod', "`recid`='{$afakod}'");
            
            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                
                $mezok   = array('cikktorzs', 'szall_csz', 'cikktorzs_id', 'cikkmee', $db_mezo, $db_mezo_br, 'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'valtozott', 'update_usr'                             );
                $ertekek = array($cikk,       $szall_csz,  $cikktorzs_id,  $cikkmee,  $netto,   $brutto,     $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {

                $mezok   = array('anyag_szamla_recid', 'cikktorzs', 'szall_csz', 'cikktorzs_id', 'cikkmee',  $db_mezo, $db_mezo_br, 'ktgnem_id',          'ktghely_id',           'ktghely',            'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',        'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                $ertekek = array($recid,               $cikktorzs,  $szall_csz,  $cikktorzs_id,  $cikkmee,   $netto,   $brutto,     $jogcim['szla_szam'], $jogcim['ktghely_id'],  $jogcim['ktghely'],   $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'],   '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $uj_id = $adatbazis_kapcsolat->i('anyag_szamla_tetel', $mezok, $ertekek);
                if( $sql === false ) { $db_error = true; }
                $_SESSION['anyag_szla_tetel_recid'][$i] = $uj_id;
            }
            $i++;
        }
        
        // Ha kell a törölt sorok törlése adatbázisból is
        $_POST['torolt_sorok'] = trim($_POST['torolt_sorok'], 'pop_tetel_');
        $torlendo_idk = explode('pop_tetel_', $_POST['torolt_sorok']);
        if( count($torlendo_idk) ) {
            $sql = $adatbazis_kapcsolat->d('anyag_szamla_tetel', "`recid` IN ('". implode('\', \'', $torlendo_idk)."')");
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

// Kiválasztott számla tételeinek felsorolása
if( isset($_POST['get_szamla_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');

        // Tételek bejárása
        $tetelek = $adatbazis_kapcsolat->s('recid ktgnem_id netto_ert cikk_id mee szlamen szlaear', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_POST['szamla_id']}'");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="feld_var_szamlak" class="csikos v2 fix_fejlec hoveres">
                <tr>
                    <th>Név</th>
                    <th>Mee</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                    <th>Számlázott egységár</th>
                    <th>Nettó érték</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Rendelés kiválasztása</th>
                    <th>Módosító tétel</th>
                </tr>
            ');
        foreach ( $tetelek as $tetel ) {

            // Ha már van kacsolat a számla tétel és az anyagrendelés tétel között, akkor a rendelés adatait is kitöltjük
            $anyagrend = '';
            if( $kapcsolasok[$tetel['recid']] ) {
                $anyagrend = $adatbazis_kapcsolat->s2('nev rndmen erkmen szlamen rndear', 'anyagigenyles', "`recid`='{$kapcsolasok[$tetel['recid']]}'");
            }
            
            $hibas_sor = '';
            if( $anyagrend['erkmen'] && $anyagrend['erkmen'] < $tetel['szlamen'] ) {
                $hibas_sor = ' class="piros"';
            }
            if( $anyagrend['rndear'] && $anyagrend['rndear'] < $tetel['szlaear'] ) {
                $hibas_sor = ' class="piros"';
            }

            $tablazat .= nl2('
                <tr'.$hibas_sor.' data-recid="'.$tetel['recid'].'">
                    <td>?</td>
                    <td>'.$tetel['mee'].'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndmen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['erkmen'], 2, ',', ' ').'</td>
                    <td>'.number_format($anyagrend['szlamen'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlamen'], 2, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($anyagrend['rndear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['szlaear'], 4, ',', ' ').'</td>
                    <td class="nowrap">'.number_format($tetel['netto_ert'], 0, ',', ' ').'</td>
                    <td>'.$tetel['cikk_id'].'</td>
                    <td>'.$anyagrend['nev'].'</td>
                    <td><input type="button" class="gomb alacsony" value="Kiválaszt" onclick="anyag_szamla_tetel_rendeles_kivalaszt(\''.$tetel['recid'].'\')"></td>
                    <td>
                        <div class="cb">
                            <input type="checkbox" class="check" id="modosit_'.$tetel['recid'].'">
                            <label for="modosit_'.$tetel['recid'].'"></label>
                        </div>
                    </td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések kilistázása egy számla tétel alapján
if( isset($_POST['get_anyagrendeles_tetelek']) ) {
    
    if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) ) {
        
        $foglalt_anyagrend_sorok = array();
        $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
        foreach ( $kapcsolasok as $kapcs ) {
            array_push($foglalt_anyagrend_sorok, $kapcs);
        }
    
        $szamla = $adatbazis_kapcsolat->s2('rnd_id', 'anyag_szamla', "`recid`='{$_POST['szamla_id']}'");

        // A rendelés kiválasztás popupon, azokat a rendelés tételeket kell megjeleníteni, melyeknél a rendelés szám megegyezik a kiválasztott számla rendelés számával 
        // és a számlázott mennyiség < beérkezett mennyiség, mert ezeknél a tételeknél várunk még számlát.
        // Továbbá melyeknek részben beérkezett vagy beérkezett a státusza és szállító az, ami a kiválasztott számla szállítója, telephely pedig szintén az előbb kiválasztott számla telephelye.
        $anyagrendelesek = $adatbazis_kapcsolat->s('recid rndid cikk_id cikkmee ikt_szam rndmen erkmen szlamen rndear', 'anyagigenyles', "`rndid`='{$szamla['rnd_id']}' AND `szlamen`<`erkmen`");

        $tablazat = nl2('
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                <table id="anyagrend_tetelek" class="csikos v2 kijCont csak_egy_kijelolheto fix_fejlec hoveres">
                <tr>
                    <th>Rendelés szám</th>
                    <th>Cikkszám</th>
                    <th>Cikknév</th>
                    <th>Mee</th>
                    <th>Anyagig. Szám</th>
                    <th>Rendelt mennyiség</th>
                    <th>Beérkezett mennyiség</th>
                    <th>Már számlázott mennyiség</th>
                    <th>Rendelési egységár</th>
                </tr>
            ');
        foreach ( $anyagrendelesek as $rendeles ) {

            $szurke = '';
            if( in_array($rendeles['recid'], $foglalt_anyagrend_sorok) ) {
                $szurke = ' szurke';
            }
            
            $tablazat .= nl2('
                <tr class="kijSor'.$szurke.'" data-recid="'.$rendeles['recid'].'">
                    <td>'.$rendeles['rndid'].'</td>
                    <td>'.$rendeles['cikk_id'].'</td>
                    <td>'.getErtek('nev', 'cikktorzs', 'id', $rendeles['cikk_id']).'</td>
                    <td>'.$rendeles['cikkmee'].'</td>
                    <td>'.$rendeles['ikt_szam'].'</td>
                    <td class="r">'.number_format($rendeles['rndmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['erkmen'],  2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['szlamen'], 2, ',', ' ').'</td>
                    <td class="r">'.number_format($rendeles['rndear'],  4, ',', ' ').'</td>
                </tr>
                ');
        }

        $tablazat .= nl2('
                </table>
            </div>
            ');

        echo json_encode( array('tablazat' => $tablazat) );
    
    } else {
        echo json_encode( array(
                                'hiba'  => 'Önnek nincs joga számlát kiválasztani!',
                                'title' => 'Hiba'
                                )
                        );
    }
    
    $weblap -> leallit();
    exit;
}

// Anyagrendelések és számla tételek összekapcsolása
if( isset($_POST['anyag_szamla_tetelek_ment']) ) {
    
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Javasriptből kaptunk egy tömbböt, ilyen formában: tomb[szamla_tetel_recid] = anyagrendeles_recid
    $kapcsolasok = json_decode($_POST['kapcsolasok'], 'tömbbe');
    
    // Státusz léptetés
    if( !addStatusz('anyag_szamla_statusz', $_POST['id'], A_SZAMLA_STAT_IGAZOLASRA_VAR) ) {
        $db_error = true;
    }
    
    // Az anyagrendelés sorába hozzáadjuk a már számlázott összeget a meglevőhöz
    // Illetve a számla tétel sorába is letároljuk a rendelés azonosítóját és rnd_id-ját.
    foreach( $kapcsolasok as $kulcs => $kapcs ) {
        
        $szamla_tetel  = $adatbazis_kapcsolat->s2('netto_ert szlamen cikk_id szlaear', 'anyag_szamla_tetel', "`recid`='{$kulcs}'");
        $anyagrendeles = $adatbazis_kapcsolat->s2('ikt_szam rndid erkmen rndear', 'anyagigenyles', "`recid`='{$kapcs}'");
        
        // A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!
        if( $anyagrendeles['erkmen'] < $szamla_tetel['szlamen'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>A számlázott mennyiség nem lehet nagyobb, mint a beérkezett mennyiség!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázási egységár nem lehet nagyobb a rendelési egységárnál
        if( $anyagrendeles['rndear'] < $szamla_tetel['szlaear'] ) {
            echo json_encode(array(
                'hiba'  => 'Cikkszám: '.$szamla_tetel['cikk_id'].'<br>Számlázási egységár nem lehet nagyobb a rendelési egységárnál!<br>A számla nem menthető!',
                'title' => 'Hiba',
            ));
            $weblap -> leallit();
            exit;
        }
        
        // Számlázott mennyiség növelése
        if( !$szamla_tetel ) { $db_error = true; }
        if( !mysqli_query($db_kapcs, 'UPDATE `'.get_db_tabla().'anyagigenyles` SET `szlamen`=`szlamen`+'.$szamla_tetel['netto_ert'].' WHERE `recid`=\''.$kapcs.'\'') ) {
            $db_error = true;
        }
        
        // Anyagrendelés ID-k eltárolása tételekhez
        $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel',
                                                            array('anyagigenyles_szam',         'rnd_id'                 ),
                                                            array($anyagrendeles['ikt_szam'],   $anyagrendeles['rndid']  ),
                                        "`recid`='{$kulcs}'");
        if( !$sql ) { $db_error = true; }
    }
    
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'statusz' => 'siker'
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array(
            'hiba'  => tranzakcioHiba(),
            'title' => 'Hiba',
        ));
    }
    
    
    $weblap -> leallit();
    exit;
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

    if( isset($_GET['modosit']) || isset($_GET['tetel']) || /*isset($_GET['kifizetesek']) ||*/ isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            // 'kifizetesek'   => 'Kifizetések',
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
    
    
    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $szamla  = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel_atmeneti', "`anyag_szamla_recid`='{$szamla['recid']}'");
    } else if( _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_GET['id']}'");
    }
    $i = 0;
    $ossz_netto   = 0;
    $ossz_dev_net = 0;
    $_SESSION['anyag_szla_tetel_recid']             =
        $_SESSION['anyag_szla_tetel_cikktorzs']     =
        $_SESSION['anyag_szla_tetel_szall_csz']     =
        $_SESSION['anyag_szla_tetel_cikktorzs_id']  =
        $_SESSION['anyag_szla_tetel_cikkmee']       =
        $_SESSION['anyag_szla_tetel_netto_ert']     =
        $_SESSION['anyag_szla_tetel_brutto_ert']    =
        $_SESSION['anyag_szla_tetel_dev_net']       =
        $_SESSION['anyag_szla_tetel_dev_br']        =
        $_SESSION['anyag_szla_tetel_afakod']        =
        $_SESSION['anyag_szla_tetel_jogcim']        =
                                                array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['anyag_szla_tetel_recid'][$i]         = $tetel['recid'];
        $_SESSION['anyag_szla_tetel_cikktorzs'][$i]     = $tetel['cikktorzs'];
        $_SESSION['anyag_szla_tetel_szall_csz'][$i]     = $tetel['szall_csz'];
        $_SESSION['anyag_szla_tetel_cikktorzs_id'][$i]  = $tetel['szall_csz'];
        $_SESSION['anyag_szla_tetel_cikkmee'][$i]       = $tetel['cikkmee'];
        $_SESSION['anyag_szla_tetel_netto_ert'][$i]     = number_format($tetel['netto_ert'],  0, '', ' ');
        $_SESSION['anyag_szla_tetel_brutto_ert'][$i]    = number_format($tetel['brutto_ert'], 0, '', ' ');
        $_SESSION['anyag_szla_tetel_dev_net'][$i]       = number_format($tetel['dev_net'],    2, ',', ' ');
        $_SESSION['anyag_szla_tetel_dev_br'][$i]        = number_format($tetel['dev_br'],     2, ',', ' ');
        $_SESSION['anyag_szla_tetel_afakod'][$i]        = $tetel['afakod_recid'];
        $_SESSION['anyag_szla_tetel_jogcim'][$i]        = $tetel['jogcim_recid'];
        $_SESSION['anyag_szla_tetel_valtozott'][$i]     = $tetel['valtozott'];
        $_SESSION['anyag_szla_tetel_andocos'][$i]       = $tetel['andocos'];

        $i++;
    }
    
    // Anyag számla mentésekor HA nem nullás a számla, akkor ellenőrizzük, hogy a számla nettó értéke azonos-e a tételek összegével
    // Deviza esetén más session-t járunk be
    $tizedesek  = 0;
    $mezo1      = 'ossz_netto';
    $mezo2      = 'netto';
    if( $szamla['kcs_21']!=KCS_21_HUF ) {
        $tizedesek  = 2;
        $mezo1      = 'ossz_dev_net';
        $mezo2      = 'dev_net';
    }
    
    // Tételek SESSION-jének feltöltése (új felvitelben kicsit más a lekérdezés
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {
        $szamla = $adatbazis_kapcsolat->s2('recid kcs_21', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
    }

    // Gombok kirajzolása minden oldalra
    $gombok = '';
    if( isset($_GET['uj']) && _jog(JOG_ANYAG_SZAMLAK_OLVAS) ) {

        $gombok = nl2('
            <div class="form_kuldes">
                <input type="hidden" name="atmeneti_recid" value="'.$szamla['recid'].'">
                <input type="hidden" id="ossz_netto" value="">
                <input type="hidden" id="ossz_dev_net" value="">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');
        $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'anyagszamla_tetelek_kezelese\');"> ';

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


        // Ha már fel van dolgozva, nincs még igazolva és van hozzá jogunk
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' && $sor['igazolva_dt']=='0000-00-00 00:00:00' && _jog(SZAMLAK_IGAZOLASA) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="szamla_igazol()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Elutasítás" onclick="csoportMuvelet(\'anyagSzamlaElutasit\');"> ';
        }
        
        // Szerkesztéshez az kell, hogy legyen jogunk, illetve a számla ne legyen még igazolva/elutasítva
        if( _jog(JOG_ANYAG_SZAMLAK_KEZEL) && $sor['igazolva_dt'] == '0000-00-00 00:00:00' ) {
            // Ha még nincs feldolgozva
            if( $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
                $gombok .= '<input type="button" class="gomb" value="Küldés igazolásra" onclick="kuldes_igazolasra()"> ';
            }
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'anyagszamla_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.$sor['netto'].'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.$sor['dev_net'].'">';
            $gombok .= '<input type="hidden" id="arfolyam_db" value="'.$sor['arfolyam'].'">';
            $gombok .= '<input type="hidden" id="nullas_db" value="'.$sor['nullas'].'">';
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

        // Itt úgy fogunk tenni, mintha újként szerkesztenénk, viszont nem a session-ból keresünk ide értékeket
        // hanem a kapott andocid alapján az átmeneti táblából. Magyarul egy sima lekérdezéssel meg is van a $sor tömb.
        $sor = $adatbazis_kapcsolat->s2('*', 'anyag_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $sor['szallito_id_autocomp'] = getErtek('nev', 'szallito', 'id', $sor['szallito_id']);
	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

// Itt most kifejezetten kell, hogy az autocomplete-s értékek is meglegyenek
//        foreach ( $akt_tomb as $tomb ) {
//            if( $tomb['type'] == 'autocomplete' ) {
//                $sor[$tomb['name'].'_autocomp'] = '';
//            }
//        }
//        
        // Fizetési módot oda vissza konvertálni kell, mert XML-ből a teljes címkét kapjuk, de mi csak egy karaktert tárolunk
        switch ( strtolower($sor['fizmod']) ) {
            case 'u': $sor['fizmod'] = 'Utalás';    break;
            case 'k': $sor['fizmod'] = 'Készpénz';  break;
        }

        $json_sor = array();
        foreach ( $sor as $kulcs => $elem ) {
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        
        $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

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

        $deviza = '';
        if( $sor['kcs_21'] != KCS_21_HUF ) {
            $deviza = ' ('.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')';
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Cikktörzs</div>
                    <div class="dtc">Szállító cikkszáma</div>
                    <div class="dtc">Danubius cikkszám</div>
                    <div class="dtc">Mennyiségi egység</div>
                    <div class="dtc">Költségnem</div>
                    <div class="dtc">Költséghely</div>
                    <div class="dtc r">Nettó érték (HUF)</div>
                    <div class="dtc r">Bruttó érték (HUF)</div>
                    <div class="dtc r">Deviza nettó'.$deviza.'</div>
                    <div class="dtc r">Deviza bruttó'.$deviza.'</div>
                    <div class="dtc r">Áfa %</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$_GET['id']}'");
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.$tetel['cikktorzs'].'</div>
                    <div class="dtc">'.$tetel['szall_csz'].'</div>
                    <div class="dtc">'.$tetel['cikktorzs_id'].'</div>
                    <div class="dtc">'.$tetel['cikkmee'].'</div>
                    <div class="dtc">'.$tetel['megn'].'</div>
                    <div class="dtc">'.$tetel['ktghely'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'],    2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'],     2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['afa'].'</div>
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
        if( $sor['igazolva'] && $sor['igazolva_dt'] != '0000-00-00 00:00:00' ) {
            $igazolva = '<b class="pipa">✓</b>';
        }
        if( $sor['utalvanyozva'] && $sor['utalvanyozva_dt'] != '0000-00-00 00:00:00' ) {
            $utalvanyozva = '<b class="pipa">✓</b>';
        }
        // 2021.09.14 Tamás kérte, hogy ne legyen piros X ha még nincs feldolgozva, csak pipa, ha már megvan
        // $feldolgozva = '<b class="x">✖</b>';
        $feldolgozva = '';
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
            $feldolgozva     = '<b class="pipa">✓</b>';
            $feldolgozva_usr = $sor['feldolgozva_usr'];
            $feldolgozva_dt  = datum_kiir($sor['feldolgozva_dt']);
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
                    <div class="dtc"><b class="pipa">'.$feldolgozva.'</b></div>
                    <div class="dtc">'.$feldolgozva_usr.'</div>
                    <div class="dtc">'.$feldolgozva_dt.'</div>
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
//        $kifizetesek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_kifizetesek', "`anyag_szamla_recid`='{$_GET['id']}'", 'insert_dt');
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

        // Itt az XML-ből kapott dokumentum linkjét jelenítjük meg
        if( $sor['dok_link'] ) {
            $szamla_html .= nl2('
                Fődokumentum link:
                <br><input type="text" value="'.$sor['dok_link'].'" readonly style="width: 100%; margin: 1rem 0">
                <br><input type="button" class="gomb" value="Dokumentum megtekintése" onclick="window.open(\''.$sor['dok_link'].'\')">');
        }
        $szamla_html .= nl2('
                <br>Bejövő xml:
                <br>').'<textarea class="oriasiTargy" style="height: 30rem;" readonly>'.$sor['erk_xml'].'</textarea>'.nl2('
                <br>            
                <br>Kimenő xml:
                <br>').'<textarea class="oriasiTargy" style="height: 30rem;" readonly>'.$sor['kuld_xml'].'</textarea>';

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
function xml_vissza( $szamla_id, $igazolva = '', $oka = '' ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    $szamla = $adatbazis_kapcsolat->s2('igazolva andoc_id telephely_id kcs_21 kuld_xml', 'anyag_szamla', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
    
    // XML dekódolása
    $szamla['kuld_xml'] = htmlspecialchars_decode($szamla['kuld_xml']);
    
    // A számla devizája dönti el, hogy a tételbe melyik nettó mező kerüljön
    if( $szamla['kcs_21'] == KCS_21_HUF ) {
        $netto_mezo  = 'netto_ert';
        $brutto_mezo = 'brutto_ert';
    } else {
        $netto_mezo  = 'dev_net';
        $brutto_mezo = 'dev_br';
    }

    // A tételeket költségnem + költséghely + ÁFAkulcs szerincs csoportosíva küldjük vissza.
    $tetelek = $adatbazis_kapcsolat->s('*', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$szamla_id}' AND `valtozott`='1'", '', '', "`ktgnem_id`, `ktghely_id`, `afa_kod`");
    if( $tetelek === false ) { $db_error = true; }

    $tetel_xml = '';
    $i = 0;
    foreach( $tetelek as $tetel ) {
        
        $netto_ertek  = $adatbazis_kapcsolat->s2("SUM(`{$netto_mezo}`)", 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$szamla_id}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $netto_ertek  = $netto_ertek["SUM(`{$netto_mezo}`)"];
        $brutto_ertek = $adatbazis_kapcsolat->s2("SUM(`{$brutto_mezo}`)", 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$szamla_id}' AND `valtozott`='1' AND `ktgnem_id`='{$tetel['ktgnem_id']}' AND `ktghely_id`='{$tetel['ktghely_id']}' AND `afa_kod`='{$tetel['afa_kod']}'");
        $brutto_ertek = $brutto_ertek["SUM(`{$brutto_mezo}`)"];
        $afa_osszege  = $brutto_ertek-$netto_ertek;
        
        $szalloda   = getSzallodaNev($szamla['telephely_id']);
        // Nem tudjuk még mi legyen az értéke <property name="Sor megnevezése">'.$tetel['targy'].'</property>
        // <property name="Nettó sorösszeg">'.$netto_ertek.'</property>
        // <property name="Pénznem (sor)">'.getErtekLista(ERTEKLISTA_PENZNEM, $szamla['kcs_21']).'</property>
        // <property name="ÁFA Összege">'.$afa_osszege.'</property>
        // <property name="Szálloda">'.$szamla['telephely_id'].' - '.getSzallodaNev($szamla['telephely_id']).'</property>
        $tetel_xml .= '
            <row rownumberonpage="'.($i+1).'">
              <property name="Bruttó sorösszeg">'.$brutto_ertek.'</property>
              <property name="Főkönyvi számlaszám">'.$tetel['ktgnem_id'].'</property>
              <property name="Jogcím">'.$tetel['jogcim'].'</property>
              <property name="Üzemrész">'.$tetel['uzemresz'].'</property>
              <property name="Főkönyvi számlaszám megnevezése">'.$tetel['megn'].'</property>
              <property name="Főkönyvi számlaszám">'.$tetel['ktgnem_id'].'</property>
              <property name="Költséghely">'.$tetel['ktghely_id'].'</property>
              <property name="ÁFA kód">'.$tetel['afa_kod'].'</property>
              <property name="ÁFA neve">'.$tetel['afa_nev'].'</property>
              <property name="ÁFA %">'.$tetel['afa'].'</property>
            </row>
            ';
        $i++;
    }
    
    $valasz_xml = 'psInputXml=<?xml version="1.0" encoding="utf-8"?>
<xml>
  <data>
    <register registernumber="'.$szamla['andoc_id'].'" operation="update">
      <workflow_node_name>[AUTO] Utalványozásra küldés</workflow_node_name>
      <properties>
        <container name="NIK1">
          <row rownumberonpage="1">
            <property name="Igazoló döntés">'.$igazolva.'</property>
            <property name="Teljesítés igazolás elutasítás oka">'.$oka.'</property>
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
//if( isset($_GET['teszt']) ) {
//    echo $valasz_xml;
//    echo 'f';
//    exit;
//}
   
    $fejlec = array(
        "Content-type: application/x-www-form-urlencoded",
        "Content-length: ".strlen($valasz_xml),
        "Connection: close",
    );
    
    // Válasz XML postolása
    $valasz = URL_post(ANDOC_POST_KULD_URL, $valasz_xml, $fejlec);
    
    // Logolás
    $log_utvonal = '../andoc.log';
    $log = '*****************************
    KÜLDÖTT XML (Igazolás/elutasításkor)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz_xml.'



';
    file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);
    
    // Logolás
    $log_utvonal = '../andoc.log';
    $log = '*****************************
    KAPOTT XML (Igazolás/elutasításra válaszként)
    '.date('Y.m.d H:i:s').'
    Ip: '.$_SERVER['REMOTE_ADDR'].'
    URL: '.get_http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'
    POST:
    '.$valasz.'
        
        
        
';
    file_put_contents($log_utvonal, $log, FILE_APPEND | LOCK_EX);
            
    // A kapott választ feldolgozató állapotra kell hozni, hogy értelmezni lehessen, ehhez ki kell törölni belőle a sallangot
    $mit   = array('<?xml version="1.0" encoding="utf-8"?>', '<string xmlns="AndocWebServices.WebService_AndocSimple"><?xml version="1.0" encoding="utf-8" standalone="yes"?>', '</xml></string>');
    $mivel = array('',                                       '',                                                                                                                '</xml>'         );
    $kapott_xml = new SimpleXMLElement(trim(str_replace($mit, $mivel, htmlspecialchars_decode($valasz))));

    // Ha Andoc-ék nem tudják fogadni a választ, akkor hibaüzenet
    if( $kapott_xml -> result != 'I' ) {
        addMegj('anyag_szamla', $szamla_id, 'Az alábbi válasz XML-t nem sikerült elküldeni:<br>'.htmlspecialchars($valasz_xml));
        $db_error = true;
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        $xml = $szamla['kuld_xml'].
               date('Y-m-d H:i:s').'
               '.$valasz_xml.'



    ';

        $sql = $adatbazis_kapcsolat->u('anyag_szamla',  array('kuld_xml'),
                                                        array($xml),
                                        "`recid`='{$szamla_id}'");
        if( $tetelek === false ) { $db_error = true; }

        // Tételeknél nullázzuk a változott pipát
        $sql = $adatbazis_kapcsolat->u('anyag_szamla_tetel', array('valtozott'), array(0), "`anyag_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
        if( $sql === false ) { $db_error = true; }
    }
   
    
    return $db_error;
    
}