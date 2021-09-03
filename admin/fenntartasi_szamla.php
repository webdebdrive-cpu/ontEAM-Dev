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

// Számla küldése igazolásra
if( isset($_POST['kuldes_igazolasra']) ) {
    
    $sor = $adatbazis_kapcsolat->s2('feldolgozva_dt', 'fenntartasi_szamla', "`recid`='{$_POST['id']}'");
    if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' ) {
        
        echo json_encode(array(
                            'hiba'  => 'A számla már feldolgozott, így nem küldhető újra igazolásra!',
                            'title' => 'Hiba',
                            ));
        
        $weblap -> leallit();
        exit;
    }
    
    $adatbazis_kapcsolat->u('fenntartasi_szamla',   array('feldolgozva_dt', 'feldolgozva_usr'                       ),
                                                    array('NOW()',          $_SESSION[get_db_tabla().'teljes_nev']  ),
                                                "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
                        'siker' => 1,
                        ));
    
    $weblap -> leallit();
    exit;
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
        $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla', array('igazolva', 'igazolva_dt', 'igazolva_usr'                           ),
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
        $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla', array('igazolva', 'elutleir',     'igazolva_dt', 'igazolva_usr'                           ),
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
        $session_mezo_br        = 'fennt_szla_tetel_dev_br';
    } else {
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'fennt_szla_tetel_netto_ert';
        $session_mezo_br        = 'fennt_szla_tetel_brutto_ert';
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
        $class = $targy = $pr_tetel = '';
        if( $_SESSION['fennt_szla_szerzodes_recid'][$i] ) {
            $pr_tetel = $adatbazis_kapcsolat->s2('recid targy', 'fenntartasi_szerzodes_tetel', "`recid`='{$_SESSION['fennt_szla_szerzodes_recid'][$i]}'");
        }

        if( $pr_tetel ) {
            $targy = htmlspecialchars_decode( $pr_tetel['targy'] );
            $class = ''; // ' szerzodes_szamla_tetel_szurke'
            $torol = '';
        } else {
            if( $_SESSION['fennt_szla_tetel_recid'][$i] ) {
                $targy = '<input type="button" class="gomb" value="kapcsolás" onclick="kapcsolas_popup_nyit(\''.$_SESSION['fennt_szla_tetel_recid'][$i].'\');">';
            }
        }
        
        $jogcim = $adatbazis_kapcsolat->s2('ktghely megn', 'jogcim', "`recid`='{$_SESSION['fennt_szla_tetel_jogcim'][$i]}'");
        $html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['fennt_szla_tetel_recid'][$i].'">
                            <td class="pop_targy l">'.htmlspecialchars($_SESSION['fennt_szla_tetel_targy'][$i]).'</td>
                            <td class="pop_netto_ert r">'.$_SESSION[$session_mezo][$i].'</td>
                            <td class="pop_brutto_ert r">'.$_SESSION[$session_mezo_br][$i].'</td>
                            <td class="pop_ktgnem_autocomp l">
                                '.$jogcim['megn'].'
                            </td>
                            <td class="pop_ktghely_autocomp l">
                                '.$jogcim['ktghely'].'
                            </td>
                            <td>
                                '.$targy.'
                            </td>
                            <!--<td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['fennt_szla_tetel_recid'][$i].'\');">
                                    🖉
                                </a>
                            </td>-->
                            <td>
                                '.$torles.'
                                <input type="hidden" class="pop_afakod" name="pop_afakod_id" value="'.$_SESSION['fennt_szla_tetel_afakod'][$i].'">
                                <input type="hidden" class="pop_jogcim" name="pop_jogcim_id" value="'.$_SESSION['fennt_szla_tetel_jogcim'][$i].'">
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
    $szamla_brutto_erteke   = str_replace(array(' ', ','), array('', '.'), $_POST['szamla_brutto_erteke']);
    $_POST['adatok']        = json_decode( $_POST['adatok'] );
    $arfolyam               = str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat     = 0;
    $targy_adat     = 1;
    $netto_ert_adat = 2;
    $brutto_ert_adat= 3;
    $afakod_adat    = 4;
    $jogcim_adat    = 5;
    $valtozott_adat = 6;
    
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
    if( !$_POST['adatok'][$targy_adat][1] ) {
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
        $_SESSION['fennt_szla_tetel_recid']     = $_POST['adatok'][$recid_adat][1];
        $_SESSION['fennt_szla_tetel_targy']     = str_replace('{IDEZOJEL}', '"', $_POST['adatok'][$targy_adat][1]);
        $_SESSION['fennt_szerz_tetel_recid']    = $_POST['adatok'][$pr_tetel_adat][1];
        if( $devizas ) {
            $_SESSION['fennt_szla_tetel_dev_net'] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['fennt_szla_tetel_dev_br']  = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        } else {
            $_SESSION['fennt_szla_tetel_netto_ert']  = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
            $_SESSION['fennt_szla_tetel_brutto_ert'] = str_replace(',', '.', $_POST['adatok'][$brutto_ert_adat][1]);
        }
        $_SESSION['fennt_szla_tetel_afakod']    = $_POST['adatok'][$afakod_adat][1];
        $_SESSION['fennt_szla_tetel_jogcim']    = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['fennt_szla_tetel_valtozott'] = $_POST['adatok'][$valtozott_adat][1];
    }

    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő számlát szerkesztünk
    if( $recid ) {
        $i = 0;
        foreach ($_POST['adatok'][$targy_adat][1] as $targy) {

            $netto   = $_POST['adatok'][$netto_ert_adat][1][$i];
            $brutto  = $_POST['adatok'][$brutto_ert_adat][1][$i];
            // Bruttót nem árfolyamból számolunk, ez a sor törölhető $brutto  = $netto*str_replace(array(' ', ','), array('', '.'), $_POST['arfolyam']);
            $afakod  = $_POST['adatok'][$afakod_adat][1][$i];
            $targy   = $_POST['adatok'][$targy_adat][1][$i];
            $jogcim  = $_POST['adatok'][$jogcim_adat][1][$i];
            $ktgnem  = $_POST['adatok'][$ktgnem_adat][1][$i];
            $ktghely = $_POST['adatok'][$ktghely_adat][1][$i];
            $valtozott = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }
            
            // Jogcím/Áfakód esetén nem csak egy recid-t tárolunk le, hanem a tábla oszlopait, ezért lekérjük a megfelelő sort
            $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");
            $afakod = $adatbazis_kapcsolat->s2('recid nev kod afa', 'afakod', "`recid`='{$afakod}'");
            
            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                
                $mezok   = array('targy', $db_mezo, $db_mezo_br, 'ktgnem_id',           'ktghely_id',           'ktghely',          'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',      'valtozott', 'update_usr'                             );
                $ertekek = array($targy,  $netto,   $brutto,     $jogcim['szla_szam'],  $jogcim['ktghely_id'],  $jogcim['ktghely'], $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'], $valtozott,  $_SESSION[get_db_tabla().'teljes_nev']   );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {

                $rownum = $adatbazis_kapcsolat->s2('MAX(`rownum`)', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$recid}'");
                $rownum = $rownum['MAX(`rownum`)']+1;
                $mezok   = array('fenntartasi_szamla_recid', 'rownum', 'targy', $db_mezo, $db_mezo_br, 'ktgnem_id',          'ktghely_id',           'ktghely',            'jogcim_recid',   'jogcim',          'uzemresz',          'megn',          'afakod_recid',   'afa',          'afa_kod',      'afa_nev',        'valtozott', 'loginnev',                          'insert_dt',    'insert_usr',                             'update_usr'                              );
                $ertekek = array($recid,                     $rownum,  $targy,  $netto,   $brutto,     $jogcim['szla_szam'], $jogcim['ktghely_id'],  $jogcim['ktghely'],   $jogcim['recid'], $jogcim['jogcim'], $jogcim['uzemresz'], $jogcim['megn'], $afakod['recid'], $afakod['afa'], $afakod['kod'], $afakod['nev'],   '1',          $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']    );
                // Devizás számla esetén a Ft-os nettó/bruttó értékeket is számoljuk ki és írjuk be
                if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
                    array_push($mezok,   'netto_ert',       'brutto_ert'        );
                    array_push($ertekek, $netto*$arfolyam,  $brutto*$arfolyam   );
                }
                $uj_id = $adatbazis_kapcsolat->i('fenntartasi_szamla_tetel', $mezok, $ertekek);
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

// Lekérdezzük, hogy van-e a fenntartási számla tételei között olyan, amihez van kapcsolva szerződés tétel
if( isset($_POST['van_kapcsolt_szerzodes']) ) {

    // Új feltöltéskor ezt nem kell ellenőrizni
    if( !$_POST['uj'] || $_POST['uj'] == 'false' ) {

        $szerzodes_tetelek = $adatbazis_kapcsolat->s('recid', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_POST['recid']}' AND `fenntartasi_szerzodes_tetel_recid`!=0");
        if ( $szerzodes_tetelek ){

            echo json_encode( array(
                                'hiba'  => 'A számlának van olyan tétele, amihez van szerződés tétel kapcsolva, így a szerződés szám nem törölhető!',
                                'title' => 'Hiba',
                            ) );
            $weblap -> leallit();
            exit;
        }
        
    }
    
    echo json_encode( array(
                        'nincs'  => 'nincs csatolt szerződés',
                    ) );
    $weblap -> leallit();
    exit;
}

// Fenntartási táblázat betöltése
if( isset($_POST['get_fenntartasi_szerzodes_tabla']) ) {

    // Jogosultság ellenőrzése
    if( !_jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) ) {
        echo json_encode( array(
                            'hiba'  => 'Önnek nincs joga fenntartási számlákat kezelni!',
                            'title' => 'Hiba',
                        ) );
        $weblap -> leallit();
        exit;
    }
    
    $html = '';

    $fennt_sz_ek = $adatbazis_kapcsolat->s('
                                        `'.get_db_tabla().'fenntartasi_szerzodes`.`recid`,
                                        `'.get_db_tabla().'fenntartasi_szerzodes`.`szszam`,
                                        `'.get_db_tabla().'fenntartasi_szerzodes`.`netto`,
                                        `'.get_db_tabla().'fenntartasi_szerzodes`.`targy`
                                            ',
                                        'fenntartasi_szerzodes',
                                        "`szallito_id`='{$_POST['szallito_id']}'", 'szszam');

    $i = 1;
    foreach ( $fennt_sz_ek as $fennt ) {
        $html .= '
            <tr class="kijSor" data-i="'.$i.'" data-recid="'.$fennt['recid'].'" data-szszam="'.$fennt['szszam'].'">
                <td style="width: 11rem">
                    '.$fennt['szszam'].'
                    <div class="dn targy_txt"><a href="fenntartasi_szerzodes.php?id=x&amp;modosit&amp;mezo=szszam&amp;op=equal&amp;felt='.$fennt['szszam'].'" target="_blank">'.resz_string($fennt['targy'], 60).'</a></div>
                </td>
                <td class="l" style="width: 40rem">'.nl2br2($fennt['targy']).'</td>
                <td class="r nowrap">'.number_format($fennt['netto'], 0, '', ' ').'</td>
            </tr>
            ';
        $i++;
    }

    if( $html ) {

        // Táblázat rendezhető: szálloda + becsült költség, becsült költség + szálloda, típus + becsült költség. Ezek legyenek egy lenyílóból kiválaszthatóak.
        // Nincs rendezés..., de amúgy így kellene a HTML-t bővíteni:
//            Rendezés:
//            <select onchange="get_projekt_tabla(this.value)">
//                <option>szálloda + becsült költség</option>
//                <option value="bkolt"'.$sz_osszeg_selected.'>becsült költség + szálloda</option>
//            </select>
        $html = '
            <div style="max-height: 40rem; overflow-y: auto; overflow-x: none;">
                '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).' szerződései:
                <table class="csikos v2 kijCont csak_egy_kijelolheto" id="fennt_szerzodesek" style="cursor: default;">
                <thead>
                <tr>
                    <th>
                        Szerződés szám
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#fennt_szerzodesek tbody\'), this.value, 1)">
                    </th>
                    <th>
                        Tárgy
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#fennt_szerzodesek tbody\'), this.value, 2)">
                    </th>
                    <th>
                        Nettó
                        <br><input type="text" class="flat" placeholder="Szűrés" onkeyup="tabla_szur($(\'#fennt_szerzodesek tbody\'), this.value, 3)">
                    </th>
                </tr>
                </thead>
                <tbody>
                    '.$html.'
                </tbody>
                </table>
            </div>
            <script>
            ';
    } else {
        $html = '<script>popupRejt(); setTimeout(function(){ popupZar(); alert2(\'Szállító: '.getErtek('nev', 'szallito', 'id', $_POST['szallito_id']).'<br><br>Nincs ilyen szerződés!\', \'\', \'Hiba\'); }, 10);</script>';
    }

    echo json_encode(
            array( 'html' => nl2($html) )
            );

    $weblap->leallit();
    exit();
}

// Kapcsoló popup feltöltése
if( isset($_POST['pop_kapcsolas_feltolt']) ) {
    
    // Mindenek előtt ellenőrizzük, hogy a szerződés partnerkódja azonos-e a számla partner kódjával
    $szamla_tetel   = $adatbazis_kapcsolat->s2('fenntartasi_szamla_recid netto_ert dev_net', 'fenntartasi_szamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    $szamla         = $adatbazis_kapcsolat->s2('sz_szam szallito_id', 'fenntartasi_szamla', "`recid`='{$szamla_tetel['fenntartasi_szamla_recid']}'");
    $szerzodes      = $adatbazis_kapcsolat->s2('szallito_id', 'fenntartasi_szerzodes', "`szszam`='{$szamla['sz_szam']}'");

    if( $szamla['szallito_id'] != $szerzodes['szallito_id'] ) {
        echo json_encode( array(
            'hiba'  => 'A számla partnerkódja nem egyezik a szerződés partnerkódjával, kérem ellenőrizze!',
            'title' => 'Hiba'
            ) );

        $weblap -> leallit();
        exit();
    }

    $penznem    = getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
    $tizedesek  = 0;
    $szl_netto  = 'netto';
    $mezo       = 'varh_ert';
    $netto_mezo = 'netto_ert';
    $szerz_mezo = 'netto';
    $ertek_mezo = 'ertek';
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $szl_netto  = 'dev_net';
        $netto_mezo = 'dev_net';
        $mezo       = 'dev_varh_ert';
        $szerz_mezo = 'dev_net';
        $ertek_mezo = 'dev_net';
        $tizedesek  = 2;
    }
    
    // Formázott számból kivesszük a szóközöket
    $_POST[$szl_netto] = str_replace(array(' ', ','), array('', '.'), $_POST[$szl_netto]);
    
    // Szerződés összegének kiszámítása
    if( $szamla_tetel['fenntartasi_szamla_recid'] ) {
        $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_dt', 'fenntartasi_szamla', "`recid`='{$szamla_tetel['fenntartasi_szamla_recid']}'");
    }

    // Szerződés számot post-ként is megkapjuk. Ha mégsem akkor hibát dobunk.
    if( $_POST['sz_szam'] ) {

        // Hiába kaptuk meg post-ként, minket az érdekel hogy DB szintén meg van-e. Ha nem akkor figyelmeztetés, hogy előbb mentsen adatlapot
        if( !$szamla['sz_szam'] ) {

            echo json_encode( array(
                'hiba'  => 'Kapcsolás előtt mentse szerződésszámot a számla adatlapján!',
                'title' => 'Hiba'
                ) );

            $weblap -> leallit();
            exit();
            
        }
        
        // Szerződés lekérdezése
        $szerzodes = $adatbazis_kapcsolat->s2('recid kcs_21 '.$szerz_mezo, 'fenntartasi_szerzodes', "`szszam`='{$szamla['sz_szam']}'");
        $szerzodes_ossz = number_format($szerzodes[$szerz_mezo], $tizedesek, ',', ' ').' '.$penznem;

        // Ha a szerződés és a számla pénzneme nem azonos, akkor hibaüzenet
        if( $szerzodes['kcs_21'] != $_POST['kcs_21'] ) {
            echo json_encode( array(
                'hiba'  => nl2('
                                A számla tétele nem kapcsolható a szerződés tételhez,
                                <br>mert a számla pénzneme nem egyezik a szerződés pénznemével!
                                '),
                'title' => 'Hiba'
                ) );

            $weblap -> leallit();
            exit();
        }
        
    // Ilyen elvileg elő sem fordulhat, de jobb leellenőrizni
    } else {

        echo json_encode( array(
            'hiba'  => 'Előbb adja meg szerződés számot az adatlapon!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
        
    }

    // A nettó mezőben levő összeg azonos-e a tételek összegével?
    $tetelek = $adatbazis_kapcsolat->s2('SUM(`'.$netto_mezo.'`)', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$szamla_tetel['fenntartasi_szamla_recid']}'");
    if( $tetelek['SUM(`'.$netto_mezo.'`)']!= $_POST[$szl_netto] ) {
        echo json_encode( array(
            'hiba'  => 'A számla aktuális nettó értéke ('.number_format($_POST[$szl_netto], $tizedesek, ',', ' ').' '.$penznem.') nem egyenlő a tételek értékeivel ('.number_format($tetelek['SUM(`'.$netto_mezo.'`)'], $tizedesek, ',', ' ').' '.$penznem.'), így nem kapcsolható!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
    }
   
    // Tételek táblázat sorait kiszámoljuk:
    $tabla  = '';
    $i      = 0;
    $van_szalloda_egyezes = false;
    $szerzodes_tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$szerzodes['recid']}'");
    foreach( $szerzodes_tetelek as $tetel ) {
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        $id_tmp = time();
        $id_tmp .= $i;
        
        $class      = '';
        $disabled   = '';
        $torol      = '<a onclick="popup_tetel_sor_torol(\'pop_kapcsol_'.$id_tmp.'\');"><span class="icon"><i class="material-icons">delete</i></span></a>';
        $van_szamlaja = $adatbazis_kapcsolat->s2('recid, fenntartasi_szamla_recid, '.$ertek_mezo, 'fenntartasi_szamla_tetel', "`fenntartasi_szerzodes_tetel_recid`='{$tetel['recid']}'");
        $szamla_tmp   = '';
        if( $van_szamlaja ) {
            $szamla_tmp = $adatbazis_kapcsolat->s2('telj_dt', 'fenntartasi_szamla', "`recid`='{$van_szamlaja['fenntartasi_szamla_recid']}'");
            $disabled   = ' disabled';
            $class      = ' szerzodes_szamla_tetel_szurke';
            $torol      = '';
        }
        
        // Ha adott tétel szállodája nem egyezik meg a számla fej szállodájával, akkor is disabled
        if( $tetel['telephely_id'] != $_POST['telephely_id'] ) {
            $disabled   = ' disabled';
            $class      = ' szerzodes_szamla_tetel_szurke';
            $torol      = '';
        } else {
            $van_szalloda_egyezes = true;
        }
        
        $tabla .= nl2('
                        <tr class="pop_kapcsol_'.$id_tmp.$class.'">
                            <td class="pop_targy l">'.$tetel['targy'].'</td>
                            <td class="pop_varh_erk">'.datum_kiir($tetel['varh_erk']).'</td>
                            <td><input type="text" id="varh_ert_'.$id_tmp.'" value="'.number_format($tetel[$mezo], $tizedesek, ',', ' ').'" class="r f_num pop_varhato_ert" data-tort="'.$tizedesek.'" maxlength="12"'.$disabled.'></td>
                            <td><input type="text" value="'.datum_kiir($szamla_tmp['telj_dt']).'" class="datepicker_matol pop_teljesites" maxlength="10"'.$disabled.'></td>
                            <td><input type="text" id="ertek_'.$id_tmp.'" value="'.number_format($van_szamlaja[$ertek_mezo], $tizedesek, ',', ' ').'" class="r f_num pop_netto_ert" data-tort="'.$tizedesek.'" maxlength="12"'.$disabled.'></td>
                            <td>
                                '.$torol.'
                                <span class="dn pop_recid">'.$tetel['recid'].'</span>
                            </td>
                        </tr>');
        $i++;
    }
    
    // Ha a szerődés tételei között egy olyan sincs, aminek a szállodája azonos volna a számla szállodájával, akkor hibaüzenet
    if( !$van_szalloda_egyezes ) {
        echo json_encode( array(
            'hiba'  => 'A választott szerződés tételei között nem szerepel '.getSzallodaNev($_POST['telephely_id']).' szálloda!',
            'title' => 'Hiba'
            ) );
        
        $weblap -> leallit();
        exit();
    }
   
    echo json_encode( array(
        'tabla'                 => $tabla,
        'szerzodes_ossz'        => $szerzodes_ossz,
        'szamla_tetel_netto'    => number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' '),
        'szamla_telj_dt'        => datum_kiir($szamla['telj_dt']),
        ) );
    
    $weblap -> leallit();
    exit();
}

// Fenntartási számla kapcsoló popup mentése
if( isset($_POST['fenntartasi_szamla_kapcsolas']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);
    
    // Kapott tömb indexeit cseréljük beszédesebb változókra
    $recid_adat         = 0;
    $targy_adat         = 1;
    $varh_erk_adat      = 2;
    $varhato_ert_adat   = 3;
    $teljesites_adat    = 4;
    $netto_ert_adat     = 5;
    
    $recid           = $_POST['recid'];
    $_POST['adatok'] = json_decode( $_POST['adatok'] );
    
    $varh_ert_mezo  = 'varh_ert';
    $ertek_mezo     = 'netto_ert';
    $netto_mezo     = 'netto_ert';
    $szerz_mezo     = 'netto';
    $tizedesek      = 0;
    $penznem        = get_penznem();
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $varh_ert_mezo  = 'dev_varh_ert';
        $ertek_mezo     = 'dev_net';
        $netto_mezo     = 'dev_net';
        $szerz_mezo     = 'dev_net';
        $tizedesek      = 2;
        $penznem        = getErtekLista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
    }
    
    // Tárgy-ban levő idézőjelek visszakonvertálása
    $i = 0;
    foreach ( $_POST['adatok'][$targy_adat][1] as $ertek ) {
        $_POST['adatok'][$targy_adat][1][$i] = str_replace('|IDEZOJEL|', '"', $ertek);
        $i++;
    }
    
    // Ha nincs egy sornyi tételünk sem, akkor hibaüzenet
    if( !$_POST['adatok'][$targy_adat][1] ) {
        echo json_encode( array( 
                        'hiba'  => 'Legalább egy szerződés tételnek szerepelnie kell a táblázatban, üresen nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    }
    
    $szamla = $adatbazis_kapcsolat->s2('sz_szam telj_dt arfolyam', 'fenntartasi_szamla', "`recid`='{$recid}'");
    if( $szamla === false ) { $db_error = true; }
    $szerzodes = $adatbazis_kapcsolat->s2('recid '.$szerz_mezo, 'fenntartasi_szerzodes', "`szszam`='{$szamla['sz_szam']}'");
    if( $szerzodes === false ) { $db_error = true; }
    // Az a számlatétel, amit épp kapcsolunk
    $szamla_tetel = $adatbazis_kapcsolat->s2($netto_mezo, 'fenntartasi_szamla_tetel', "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $szamla_tetel === false ) { $db_error = true; }

    $kifizet_var_ossz = '';
    $i = 0;
    foreach ( $_POST['adatok'][$varhato_ert_adat][1] as $ertek ) {
        // A kifizetésre vár értéket csak akkor adjuk hozzá, ha a nettó érték mező üres, azaz nincs benne tényadat
        if( !$_POST['adatok'][$netto_ert_adat][1][$i] ) {
            $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
            $kifizet_var_ossz += $ertek;
        }
        $i++;
    }

    $kifizetett_ossz = '';
    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $kifizetett_ossz += $ertek;
    }
    $kif_ossz = $kifizet_var_ossz + $kifizetett_ossz;
    
    // Egyetlen új nettó értékünk van?
    $i = 0;
    $ertek_i            = 0;
    $teljesites_i       = 0;
    $volt_ertek         = 0;
    $volt_teljesites    = 0;
    $beirt_netto_ertek  = 0;
    $beirt_teljesites   = 0;

    foreach ( $_POST['adatok'][$netto_ert_adat][1] as $ertek ) {
        
        // Számokból a szóközt vegyük ki, vesszőt pontra cseréljük
        $ertek = str_replace(array(' ', ','), array('', '.'), $ertek);
        $_POST['adatok'][$varhato_ert_adat][1][$i]  = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$varhato_ert_adat][1][$i]);
        $_POST['adatok'][$netto_ert_adat][1][$i]    = str_replace(array(' ', ','), array('', '.'), $_POST['adatok'][$netto_ert_adat][1][$i]);
        
        // Recid alapján nézzük meg, hogy van-e már kapcsolt számlája. 
        $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'fenntartasi_szamla_tetel', "`fenntartasi_szerzodes_tetel_recid`='{$_POST['adatok'][0][1][$i]}'");
        if( $van_szamlaja === false ) { $db_error = true; }

        // Ha nincs számlája (ennek lehet az is az oka, hogy egy fiktív sor, azaz nem ismert a recid),
        // akkor ellenőrizzük, hogy van-e beírva várható érkezés. Ha van, számoljunk, mert csak egy ilyen mező lehet
        // Ha van számlája, akkor azt nem ellenőrizzük, mert akkor az egy szürke sor, azzal nem kell foglalkozni
        if( ( !$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i] ) && ($_POST['adatok'][$teljesites_adat][1][$i]!='' && $_POST['adatok'][$teljesites_adat][1][$i] != '0000.00.00' && $_POST['adatok'][$teljesites_adat][1][$i] < '4000.00.00') ) {
            $volt_ertek++;
            // Csak egy értéket fogadunk el, ezt jegyezzük is meg
            $beirt_netto_ertek = $ertek;
            $ertek_i           = $i;
        }

        // A dátumot is ellenőrizni kell, hogy csak egy helyen lett-e beírva
        if( (!$van_szamlaja || !$_POST['adatok'][$recid_adat][1][$i]) && $_POST['adatok'][$teljesites_adat][1][$i] && $_POST['adatok'][$teljesites_adat][1][$i] != '0000.00.00' && $_POST['adatok'][$teljesites_adat][1][$i] < '4000.00.00' ) {
            $volt_teljesites++;
            // Csak egy teljesítés dátumot fogadunk el, ezt jegyezzük is meg
            $beirt_teljesites = str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]);
            $teljesites_i     = $i;
        }
        
        $i++;
    }

    // Ha több értéket/teljesítést is kitöltöttek, akkor hiba
    if( $volt_ertek > 1 ) {
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat nettó értéket!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    if( $volt_teljesites > 1 ) {
        echo json_encode( array( 
                        'hiba'  => 'Csak egy szerződés tételhez írhat teljesítést!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }

    // Ha itt vagyunk, akkor csak egy nettó értéket/teljesítést írtak be. Kérdés, hogy megfelelő értéket írtak be?
    if( number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' ') != number_format($beirt_netto_ertek, $tizedesek, ',', ' ') ) {
        echo json_encode( array(
                        'hiba'  => 'A nettó érték oszlopba kérem '.number_format($szamla_tetel[$netto_mezo], $tizedesek, ',', ' ').' '. $penznem.'-t írjon, hisz ez az összeg szerepel a csatolni kívánt számla tételen!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    // Teljesítés dátuma a számla adatlapján található teljesítés dátumával egyezen meg
    if( $szamla['telj_dt'] != $beirt_teljesites ) {
        echo json_encode( array(
                        'hiba'  => 'A teljesítés oszlopba kérem '. datum_kiir($szamla['telj_dt']).' dátumot írjon, hisz ez szerepel a csatolni kívánt számlán!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha eltérő sorokban vannak új adatok, az is hiba
    // De a feltételt úgy fogalmazzuk meg, hogy ha van és van $teljesites_i és $ertek_i is. 
    // Mert ha nullás értéket kapcsolunk, akkor a 0-át nem fogja értéknek tekinteni, míg a dátumot igen, és a két sor nem lesz egyenlő. Na az ilyen esetet meg kell engedni.
    if( $teljesites_i && $ertek_i && $teljesites_i != $ertek_i ) {
        echo json_encode( array(
                        'hiba'  => 'Kérem azonos tételhez írja be a teljesítést és nettó értéket!'.$teljesites_i.' '.$ertek_i,
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
    }
    
    // Ha a kifizetett és a még kifizetésre váró összegek meghaladják a szerződés összegével, akkor hibaüzenet
    if( number_format($kifizetett_ossz, $tizedesek, '.', '') > number_format($szerzodes[$szerz_mezo], $tizedesek, '.', '') ) {
        echo json_encode( array( 
                        'hiba'  => 'A már kifizetett ('.number_format($kifizetett_ossz, $tizedesek, ',', ' ').' '.$penznem.') számlák összege több,<br>mint a szerződés összege ('.number_format($szerzodes[$szerz_mezo], $tizedesek, ',', ' ').' '.$penznem.')! Így nem menthető!',
                        'title' => 'Hiba',
                        ) );
                $weblap->leallit();
                exit();
                
    }

    // Ellenőrzések után tároljuk el a változtatásokat. 
    // Szerződés tétetel rekordok frissítése
    if( $_POST['adatok'][$recid_adat][1][$ertek_i] ) {
        $szerzodes_tetel_recid = $_POST['adatok'][$recid_adat][1][$ertek_i];
    } else {
        $szerzodes_tetel_recid = 'most hozza majd létre';
    }
    $muvelet  = 'Fenntartási számla tételhez szerződés tételt csatolt. Számla recid: '.$recid.', Számla száma: '.$szamla['sz_szam'].', Számla tétel recid: '.$_POST['szamla_tetel_recid'].', Szerződés tétel recid: '.$szerzodes_tetel_recid;
    $valtozas = '';
    $i = 0;
    $kapott_recidk = array();
    foreach ( $_POST['adatok'][$recid_adat][1] as $szerzodes_tetel_recid ) {

        // Ha valós rekord, nem újonnan létrehozott fiktiv tétel
        if( $szerzodes_tetel_recid ) {
            // Ha már van számlája, akkor az egy szürke sor volt, annak nem mentjük el az adatait
            $van_szamlaja = $adatbazis_kapcsolat->s2('recid', 'fenntartasi_szamla_tetel', "`fenntartasi_szerzodes_tetel_recid`='{$szerzodes_tetel_recid}'");
            if( $van_szamlaja === false ) { $db_error = true; }
            if( !$van_szamlaja ) {

                $ertek_regi = $adatbazis_kapcsolat->s2('*', 'fenntartasi_szerzodes_tetel', "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $ertek_regi === false ) { $db_error = true; }
                
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szerzodes_tetel',
                                                                        array($varh_ert_mezo,                              'telj_dt',                                   $ertek_mezo                               ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i]  ),
                                            "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
                // Ha devizás a számla, akkor nem csak a dev_ertek hanem a sima ertek mezőt is ki kell tölteni kapcsoláskor a szerőzdés tételnél.
                // Az `netto_ert` mező = `dev_net` * szerződés árfolyama
                if( $ertek_mezo == 'dev_net' ) {
                    $sql = $adatbazis_kapcsolat->u('fenntartasi_szerzodes_tetel',
                                                                            array('varh_ert',                                                     'netto_ert'                                                 ),
                                                                            array($_POST['adatok'][$varhato_ert_adat][1][$i]*$szamla['arfolyam'], $_POST['adatok'][$netto_ert_adat][1][$i]*$szamla['arfolyam']),
                                                "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                    if( $sql === false ) { $db_error = true; }
                }
                
                // Logolás
                if( $ertek_i == $i ) {
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
                } else {
                    $valtozas .= $ertek_regi['recid'].' recid-jú szerződés tételt módosította:' . "\n";
                }
                $valtozas .= '[mezo]'.$varh_ert_mezo.'[/mezo] [ertek_regi]' .$ertek_regi[$varh_ert_mezo]    .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$varhato_ert_adat][1][$i]                      .'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]teljesites[/mezo] [ertek_regi]'         .$ertek_regi['telj_dt']         .'[/ertek_regi] [ertek_uj]'.str_replace('.', '-', $_POST['adatok'][$teljesites_adat][1][$i]).'[/ertek_uj]'."\n";
                $valtozas .= '[mezo]'.$ertek_mezo.'[/mezo] [ertek_regi]'    .$ertek_regi[$ertek_mezo]       .'[/ertek_regi] [ertek_uj]'.$_POST['adatok'][$netto_ert_adat][1][$i]                        .'[/ertek_uj]'."\n";
            }
            
        // Az újonnan létrehozott fiktív tételt be kell szúrni
        } else {

            $uj_szerzodes_tetel_recid = $adatbazis_kapcsolat->i('fenntartasi_szerzodes_tetel',
                                                                    array('fenntartasi_szerzodes_recid', 'telephely_id',          'targy',                                'varh_erk',                               $varh_ert_mezo,                              'telj_dt',                                   $ertek_mezo,                                'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                            ),
                                                                    array($szerzodes['recid'],           $_POST['telephely_id'],  $_POST['adatok'][$targy_adat][1][$i],   $_POST['adatok'][$varh_erk_adat][1][$i],  $_POST['adatok'][$varhato_ert_adat][1][$i],  $_POST['adatok'][$teljesites_adat][1][$i],   $_POST['adatok'][$netto_ert_adat][1][$i],   $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
            if( $uj_szerzodes_tetel_recid === false ) { $db_error = true; }
            
            // Ha devizás a számla, akkor nem csak a dev_ertek hanem a sima ertek mezőt is ki kell tölteni kapcsoláskor a szerőzdés tételnél.
            // Az `netto_ert` mező = `dev_net` * szerződés árfolyama
            if( $ertek_mezo == 'dev_net' ) {
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szerzodes_tetel',
                                                                        array('varh_ert',                                                     'netto_ert'                                                 ),
                                                                        array($_POST['adatok'][$varhato_ert_adat][1][$i]*$szamla['arfolyam'], $_POST['adatok'][$netto_ert_adat][1][$i]*$szamla['arfolyam']),
                                            "`recid`='{$uj_szerzodes_tetel_recid}'");
                if( $sql === false ) { $db_error = true; }
            }
                
            // Az újonnan létrehozott sorokat vegyük fel a tömbbe, mert ami nem a tömb része, azt törölni fogjuk (hisz onnan fogjuk tudni, mely sorokat törölték a popup táblázatából)
            array_push( $_POST['adatok'][$recid_adat][1], $uj_szerzodes_tetel_recid);

            // Ha épp az $ertek_i-edik ciklusnál járnánk, akkor a frissen létrehozott tétel recid-ját felvesszük a tömbbe, hisz a következő lépésnél még össze is kell kapcsolni
            if( $ertek_i == $i ) {
                $_POST['adatok'][$recid_adat][1][$ertek_i] = $uj_szerzodes_tetel_recid;
                $valtozas .= 'Szerződés tételt hozott létre, és EHHEZ KAPCSOLTA a számla tételt:' . "\n";
            } else {
                $valtozas .= 'Szerződés tételt hozott létre:' . "\n";
            }
            
            // Logolás
            $valtozas .= '[mezo]recid[/mezo] [ertek_uj]'                    .$uj_szerzodes_tetel_recid                  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]projektszerzodes_recid[/mezo] [ertek_uj]'   .$szerzodes['recid']                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]targy[/mezo] [ertek_uj]'                    .$_POST['adatok'][$targy_adat][1][$i]       .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]varh_erk[/mezo] [ertek_uj]'                 .$_POST['adatok'][$varh_erk_adat][1][$i]    .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]'.$varh_ert_mezo.'[/mezo] [ertek_uj]'       .$_POST['adatok'][$varhato_ert_adat][1][$i] .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]teljesites[/mezo] [ertek_uj]'               .$_POST['adatok'][$teljesites_adat][1][$i]  .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]'.$ertek_mezo.'[/mezo] [ertek_uj]'          .$_POST['adatok'][$netto_ert_adat][1][$i]   .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]loginnev[/mezo] [ertek_uj]'                 .$_SESSION[get_db_tabla().'user']           .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_dt[/mezo] [ertek_uj]'                .date('Y-m-d H:i:s')                        .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]insert_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            $valtozas .= '[mezo]update_usr[/mezo] [ertek_uj]'               .$_SESSION[get_db_tabla().'teljes_nev']     .'[/ertek_uj]'."\n";
            
        }
        $i++;
    }

    // Számla tétel összekapcsolása a szerződés tétellel. Az $ertek_i-edik adatot kell menteni
    $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla_tetel', array('fenntartasi_szerzodes_tetel_recid'), array($_POST['adatok'][$recid_adat][1][$ertek_i]), "`recid`='{$_POST['szamla_tetel_recid']}'");
    if( $sql === false ) { $db_error = true; }
    
    // Ezt a SESSION-ben is updatelni kell
    $i = 0;
    foreach( $_SESSION['fennt_szla_tetel_recid'] as $szla_tetel_recid ){
        if( $szla_tetel_recid==$_POST['szamla_tetel_recid'] ){
            $_SESSION['fennt_szla_szerzodes_recid'][$i] = $_POST['adatok'][$recid_adat][1][$ertek_i];
        }
        $i++;
    }
    
    // Végül töröljük azokat a szerződés tételeket, amik nem jöttek át adatként (ki lettek törölve kliens oldalon)
    $_POST['adatok'][$recid_adat][1] = array_filter($_POST['adatok'][$recid_adat][1]);
    $torolt_tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$szerzodes['recid']}' AND `recid` NOT IN (".implode(', ', $_POST['adatok'][$recid_adat][1]).")");
    if( $torolt_tetelek ){
        $valtozas .= 'A következő szerződés tételeket törölte:'."\n".logolas_kapcsolodo_tablak_sorai($torolt_tetelek);
    }
    // Tényleges törlés
    $sql = $adatbazis_kapcsolat->d('fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$szerzodes['recid']}' AND `recid` NOT IN (".implode(', ', $_POST['adatok'][$recid_adat][1]).")");
    if( $sql === false ) { $db_error = true; }
    
    // Legvégül logolunk
    $sql = $adatbazis_kapcsolat -> i(
        'log',
        array( 'felh_id',                               'nev',       'valtozas',    'ip'                   ),
        array( $_SESSION[get_db_tabla().'login_recid'], $muvelet,    $valtozas,     $_SERVER['REMOTE_ADDR'])
    );
    if( $sql === false ) { $db_error = true; }
 
    if( !$db_error ) {
        mysqli_commit($db_kapcs);

        echo json_encode(array(
            'statusz' => 'siker'
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
    $_SESSION['fennt_szla_tetel_recid'] = $_SESSION['fennt_szla_tetel_targy'] = $_SESSION['fennt_szla_tetel_netto_ert'] = $_SESSION['fennt_szla_tetel_brutto_ert'] = $_SESSION['fennt_szla_tetel_dev_net'] = $_SESSION['fennt_szla_tetel_dev_br'] = $_SESSION['fennt_szla_tetel_afakod'] = $_SESSION['fennt_szla_tetel_jogcim'] = array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['fennt_szla_tetel_recid'][$i]     = $tetel['recid'];
        $_SESSION['fennt_szla_tetel_targy'][$i]     = $tetel['targy'];
        $_SESSION['fennt_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'],  0, '', ' ');
        $_SESSION['fennt_szla_tetel_brutto_ert'][$i]= number_format($tetel['brutto_ert'], 0, '', ' ');
        $_SESSION['fennt_szla_tetel_dev_net'][$i]   = number_format($tetel['dev_net'],    2, ',', ' ');
        $_SESSION['fennt_szla_tetel_dev_br'][$i]    = number_format($tetel['dev_br'],     2, ',', ' ');
        $_SESSION['fennt_szla_tetel_afakod'][$i]    = $tetel['afakod_recid'];
        $_SESSION['fennt_szla_tetel_jogcim'][$i]    = $tetel['jogcim_recid'];
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

        // Új felvitelkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['fennt_szla_tetel_recid'] = $_SESSION['fennt_szla_tetel_targy'] = $_SESSION['fennt_szla_tetel_netto_ert'] = $_SESSION['fennt_szla_szerzodes_recid'] = $_SESSION['fennt_szla_tetel_dev_net'] = $_SESSION['fennt_szla_tetel_jogcim'] = array();
        $fenntartasi_szamla_atmeneti = $adatbazis_kapcsolat->s2('recid', 'fenntartasi_szamla_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel_atmeneti', "`fenntartasi_szamla_recid`='{$fenntartasi_szamla_atmeneti['recid']}'", 'recid');
        $van_kapcsolt_tetel = false;
        foreach ( $tetelek as $tetel ) {
            $_SESSION['fennt_szla_tetel_recid'][$i]     = $tetel['recid'];
            $_SESSION['fennt_szla_tetel_targy'][$i]     = $tetel['targy'];
            $_SESSION['fennt_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
            $_SESSION['fennt_szla_tetel_dev_net'][$i]   = number_format($tetel['dev_net'],   2, ',', ' ');
            $_SESSION['fennt_szla_tetel_jogcim'][$i]    = $tetel['jogcim_recid'];
            $_SESSION['fennt_szla_szerzodes_recid'][$i] = $tetel['fenntartasi_szerzodes_tetel_recid'];
            if( $tetel['fenntartasi_szerzodes_tetel_recid'] ) {
                $van_kapcsolt_tetel = true;
            }
            $i++;
        }
        
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
        
        // Módosításkor updateljük a Session-ben a tételek adatait, így lesz jó a popupban
        $i = 0;
        $_SESSION['fennt_szla_tetel_recid'] = $_SESSION['fennt_szla_tetel_targy'] = $_SESSION['fennt_szla_tetel_netto_ert'] = $_SESSION['fennt_szla_szerzodes_recid'] = $_SESSION['fennt_szla_tetel_dev_net'] = array();
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_GET['id']}'", 'recid');
        $van_kapcsolt_tetel = false;
        foreach ( $tetelek as $tetel ) {
            $_SESSION['fennt_szla_tetel_recid'][$i]     = $tetel['recid'];
            $_SESSION['fennt_szla_tetel_targy'][$i]     = $tetel['targy'];
            $_SESSION['fennt_szla_tetel_netto_ert'][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
            $_SESSION['fennt_szla_tetel_dev_net'][$i]   = number_format($tetel['dev_net'],   2, ',', ' ');
            $_SESSION['fennt_szla_tetel_jogcim'][$i]    = $tetel['jogcim_recid'];
            $_SESSION['fennt_szla_szerzodes_recid'][$i] = $tetel['fenntartasi_szerzodes_tetel_recid'];
            if( $tetel['fenntartasi_szerzodes_tetel_recid'] ) {
                $van_kapcsolt_tetel = true;
            }
            $i++;
        }

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( _jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } else {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';
        }
        
        // Ha már fel van dolgozva, nincs még igazolva és van hozzá jogunk
        if( $sor['feldolgozva_dt'] != '0000-00-00 00:00:00' && $sor['igazolva_dt']=='0000-00-00 00:00:00' && _jog(SZAMLAK_IGAZOLASA) ) {
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Igazolás" onclick="szamla_igazol()"> ';
            $gombok .= '<input type="button" class="gomb modositaskor_rejt" value="Elutasítás" onclick="csoportMuvelet(\'fenntartasiSzamlaElutasit\');"> ';
        }
        
        // Szerkesztéshez az kell, hogy legyen jogunk, illetve a számla ne legyen még igazolva/elutasítva
        if( _jog(JOG_FENNTARTASI_SZAMLAK_KEZEL) && $sor['igazolva_dt'] == '0000-00-00 00:00:00' ) {
            // Ha még nincs feldolgozva
            if( $sor['feldolgozva_dt'] == '0000-00-00 00:00:00' ) {
                $gombok .= '<input type="button" class="gomb" value="Küldés igazolásra" onclick="kuldes_igazolasra()"> ';
            }
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszamla_tetelek_kezelese\');"> ';
            // Ez a popuphoz kell
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.number_format($sor['netto'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="brutto_db" value="'.number_format($sor['brutto'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.number_format($sor['dev_net'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="dev_br_db" value="'.number_format($sor['dev_br'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="arfolyam_db" value="'.number_format($sor['arfolyam'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="sz_szam_db" value="'.$sor['sz_szam'].'">';
            $gombok .= '<input type="hidden" id="telephely_id_db" value="'.$sor['telephely_id'].'">';
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

        $deviza = '';
        if( $sor['kcs_21'] != KCS_21_HUF ) {
            $deviza = ' ('.getErtekLista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')';
        }
        
        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <!--<div class="dtc">Szálloda</div>-->
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Költségnem</div>
                    <div class="dtc">Költséghely</div>
                    <div class="dtc r">Nettó érték (HUF)</div>
                    <div class="dtc r">Bruttó érték (HUF)</div>
                    <div class="dtc r">Áfa %</div>
                    <div class="dtc r">Deviza nettó'.$deviza.'</div>
                    <div class="dtc r">Deviza bruttó'.$deviza.'</div>
                </div>
        ');
        
        // Tételek felsorolása
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szamla_tetel', "`fenntartasi_szamla_recid`='{$_GET['id']}'", 'igazol_dt');
        foreach ( $tetelek as $tetel ) {
            
            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <!--<div class="dtc">'.getSzallodaNev($tetel['telephely_id']).'</div>-->
                    <div class="dtc">'.$tetel['targy'].'</div>
                    <div class="dtc">'.$tetel['megn']/*getErtek('nev', 'ktgnem', 'id', $tetel['ktgnem_id'])*/.'</div>
                    <div class="dtc">'.$tetel['ktghely_id']/*getErtek('nev', 'ktghely', 'id', $tetel['ktghely_id'])*/.'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['netto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['brutto_ert'],  0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.$tetel['afa'].'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_net'], 2, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['dev_br'], 2, ',', ' ').'</div>
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
        $feldolgozva = '<b class="x">✖</b>';
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
                <br>').'<textarea class="oriasiTargy" style="height: 30rem;" readonly>'.$sor['erk_xml'].'</textarea>'.nl2('
                <br>            
                <br>Kimenő xml:
                <br>').'<textarea class="oriasiTargy" style="height: 30rem;" readonly>'.$sor['kuld_xml'].'</textarea>';
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
function xml_vissza( $szamla_id, $igazolva = '', $oka = '' ) {
    
    global $adatbazis_kapcsolat;
    
    $db_error = false;
    
    $szamla = $adatbazis_kapcsolat->s2('igazolva andoc_id telephely_id kcs_21 kuld_xml', 'fenntartasi_szamla', "`recid`='{$szamla_id}'");
    if( $szamla === false ) { $db_error = true; }
    
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
        
        $szalloda   = getSzallodaNev($szamla['telephely_id']);
        $tetel_xml .= '
            <row rownumberonpage="'.$tetel['rownum'].'">
              <property name="Sor megnevezése">'.$tetel['targy'].'</property>
              <property name="Nettó sorösszeg">'.$tetel[$netto_mezo].'</property>
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
    KÜLDÖTT XML
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
    KAPOTT XML
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
        addMegj('fenntartasi_szamla', $szamla_id, 'Az alábbi válasz XML-t nem sikerült elküldeni:<br>'.htmlspecialchars($valasz_xml));
        $db_error = true;
    // Ezt csak akkor módosítjuk, ha az XML küldése is rendben volt
    } else {
        $xml = $szamla['kuld_xml'].
               date('Y-m-d H:i:s').'
               '.$valasz_xml.'



    ';

        $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla',    array('kuld_xml'),
                                                                array($xml),
                                        "`recid`='{$szamla_id}'");
        if( $tetelek === false ) { $db_error = true; }

        // Tételeknél nullázzuk a változott pipát
        $sql = $adatbazis_kapcsolat->u('fenntartasi_szamla_tetel', array('valtozott'), array(0), "`fenntartasi_szamla_recid`='{$szamla_id}' AND `valtozott`='1'");
        if( $sql === false ) { $db_error = true; }
    }
   
    
    return $db_error;
    
}