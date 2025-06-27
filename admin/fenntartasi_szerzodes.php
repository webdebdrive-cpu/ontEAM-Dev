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
    function get_tobbkep_darab()    { return 10;    } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';    } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;    } // MB-ban
    function get_tobbkep_oszlopnev(){                 // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}


// Köztes tábla szerződéseiek kilistázása
if( isset($_POST['fenntartasi_szerzodes_get_koztes']) ) {
    
    if( $_COOKIE['uj_felvitel_'.$megnevezes] ) {
        
        echo json_encode( array('hiba' => 'Egy másik tabon már megkezdődött egy Fenntartási szerződés feltöltése,<br>kérem előbb azt fejezze be, vagy lépjen ki belőle!', 'title' => 'Hiba') );

        $weblap -> leallit();
        exit;
    }
    
    // Köztes szerődésekből készítsünk táblázatot
    $html = '';
    $szerzodesek = $adatbazis_kapcsolat->s('recid andoc_id szszam szallito_id targy kcs_21 netto dev_net dok_link', 'fenntartasi_szerzodes_atmeneti', "");
    if( $szerzodesek ) {
        $html .= nl2('
            <table class="csikos szamla_cont_tabla" id="pop_tetelek">
                <thead>
                <tr>
                    <th class="l">Szerződésszám</th>
                    <th class="l">Partner</th>
                    <th class="l">Tárgy</th>
                    <th class="r">Nettó érték</th>
                    <th class="c">Szerződés kép</th>
                </tr>
                </thead>
                <tbody>
            ');
        foreach( $szerzodesek as $szerzodes ) {
            $link = '';
            if( $szerzodes['dok_link'] ) {
                $link .= nl2('
                            <a href="'.$szerzodes['dok_link'].'" target="_blank">
                                <img src="../elemek/admin/file.svg" title="Fájl megnyitása" alt="Fájl megnyitása" width="24">
                            </a>
                            ');
            }
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
                        <td class="r nowrap">'.number_format($szerzodes[$netto_oszlop], $tizedes, ',', ' ').' '.erteklista(ERTEKLISTA_PENZNEM, $szerzodes['kcs_21']).'</td>
                        <td class="c">'.$link.'</td>
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

// Köztes tábla áthelyezése projektek közé
if( isset($_POST['projekt_szerzodes']) ) {
    
    $szerzodes = $adatbazis_kapcsolat->s2('erk_xml', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    $adatbazis_kapcsolat->d('fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
    
    // Postolás
    // Milyen adatokkal szeretnénk postolni
    $mit   = array('<property name="Szerződés iránya"></property>',          '<property name="Szerződés iránya">fenntartás</property>', '<property name="Szerződés iránya">Fenntartás</property>');
    $mivel = array('<property name="Szerződés iránya">projekt</property>',   '<property name="Szerződés iránya">projekt</property>',    '<property name="Szerződés iránya">projekt</property>'   );
    $szerzodes['erk_xml'] = str_replace($mit, $mivel, htmlspecialchars_decode( substr($szerzodes['erk_xml'], 19) ) );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$_SERVER['HTTP_HOST'].'/andoc2/index.php?szerzodes');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . $szerzodes['erk_xml']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 500);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $result = curl_exec($ch);
    curl_close($ch);
    var_dump($result);
    
    $weblap -> leallit();
    exit;
}

// Szerződés szám foglat-e vagy sem?
if( isset($_POST['szszam_letezik']) ) {
    
    if( $adatbazis_kapcsolat->s('recid', 'fenntartasi_szerzodes', "`szszam`='{$_POST['szszam']}'") ) {
        echo json_encode( array(
                            'hiba'  => 'Ez a szerződés szám már foglalt, így nem menthető!',
                            'title' => 'Hiba',
                            ) );
    } else {
        echo json_encode( array(
                            'ok'  => 1,
                            ) );
    }
    
    $weblap -> leallit();
    exit;
}


// Tétel sorok kiíratása
if( isset($_POST['pop_tetelek_feltolt']) ) {
    
    // Ha van a GET-ben andoc_id, akkor azt itt $_POST-ként kapjuk meg, ami annyit jelent, 
    // hogy egy új számla felvitelénél vagyunk, új feltöltési módban, és az átmeneti táblából kell dolgozni
    if( $_POST['id'] == 'false' ) {
        $_POST['id'] = '';
    }
    
    if( $_POST['andoc_id'] ) {
        $szerzodes = $adatbazis_kapcsolat->s2('recid kcs_21', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_POST['andoc_id']}'");
        $fej_id    = 'uj';
    } else if( $_POST['id'] != 'false' ) {
        $szerzodes = $adatbazis_kapcsolat->s2('kcs_21', 'fenntartasi_szerzodes', "`recid`='{$_POST['id']}'");
        $fej_id    = $_POST['id'];
    } else {
        $fej_id    = 'uj';
    }
    
    // Illetve ezt az alábbi változókkal két ágra bontuk: Ft-os vagy devizás-e a számla.
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $szerzodes_netto_mezo   = 'dev_net';
        $szamla_netto_mezo      = 'dev_net';
        $penznem                = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $tizedesek              = 2;
        $session_mezo           = 'fennt_szerz_tetel_dev_net';
        $session_varhato_ert    = 'fennt_szerz_tetel_dev_varh_ert';
    } else {
        $szerzodes_netto_mezo   = 'netto_ert';
        $szamla_netto_mezo      = 'netto_ert';
        $penznem                = get_penznem();
        $tizedesek              = 0;
        $session_mezo           = 'fennt_szerz_tetel_netto_ert';
        $session_varhato_ert    = 'fennt_szerz_tetel_varh_ert';
    }
    
    // Tételek táblázat sorait kiszámoljuk:
    $tetelek_html = '';
    $i            = 0;
    foreach( $_SESSION['fennt_szerz_tetel_telephely_id'][$fej_id] as $telephely ) {
        
        $szla_netto_ert = $_SESSION[$session_mezo][$fej_id][$i];
        
        // Minden sornak adunk egy azonosítót. Mivel lehet csak egy ideiglenes sorról van szó, nem mindig van recid-nk, ezért időbélyeges azonosítót hozunk létre, megtoldva egy $i-vel.
        if( !$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i] ) {
            $_SESSION['fennt_szerz_tetel_recid' ][$fej_id][$i] = time().rand(0,1000);
        }

        $jogcim = $adatbazis_kapcsolat->s2('jogcim ktghely megn szla_szam', 'jogcim', "`recid`='{$_SESSION['fennt_szerz_tetel_jogcim'][$fej_id][$i]}'");
        
        // Van-e már számlával összeköttetés
        $class = $teljesites = $netto_ertek = '';
        $torol = '
                <a onclick="popup_tetel_sor_torol(\'pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i].'\');">
                    <span class="icon">
                        <i class="material-icons">
                            delete
                        </i>
                    </span>
                </a>
                ';
        if( $_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i] ) {
            $szamla_tetel = $adatbazis_kapcsolat->s2('recid fenntartasi_szamla_recid '.$szamla_netto_mezo, 'fenntartasi_szamla_tetel', "`fenntartasi_szerzodes_tetel_recid`='{$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i]}'");
            if( $szamla_tetel ) {
                $szamla         = $adatbazis_kapcsolat->s2('telj_dt', 'fenntartasi_szamla', "`recid`='{$szamla_tetel['fenntartasi_szamla_recid']}'");
                $class          = ' szerzodes_szamla_tetel_szurke';
                $netto_ertek    = number_format($szamla_tetel[$szamla_netto_mezo], $tizedesek, ',', ' ');
                $teljesites     = datum_kiir($szamla['telj_dt']);
                $torol          = '';
            }
        }
        
        $tetelek_html .= nl2('
                        <tr class="pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i].$class.'">
                            <td class="pop_telephely l">'.getSzallodaNev($_SESSION['fennt_szerz_tetel_telephely_id'][$fej_id][$i]).'</td>
                            <td class="pop_targy l">'.$_SESSION['fennt_szerz_tetel_targy'][$fej_id][$i].'</td>
                            <td class="pop_varh_erk l">'.datum_kiir($_SESSION['fennt_szerz_tetel_varh_erk'][$fej_id][$i]).'</td>
                            <td class="pop_varh_ert r">'.number_format(str_replace(array(',', ' '), array('.', ''), $_SESSION[$session_varhato_ert][$fej_id][$i]), $tizedesek, ',', ' ').'</td>
                            <td class="pop_jogcim_autocomp l">' .$jogcim['jogcim'   ].'</td>
                            <td class="pop_ktgnem_autocomp l">' .$jogcim['megn'     ].'</td>
                            <td class="pop_ktghely_autocomp l">'.$jogcim['ktghely'  ].'</td>
                            <td class="pop_telj_dt l">'.$teljesites.'</td>
                            <td class="pop_netto_ert r">'.$netto_ertek.'</td>
                            ').
                            /*<td>
                                <a class="szerk" onclick="popup_tetel_sor_szerk(\'pop_tetel_'.$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i].'\');">
                                    🖉
                                </a>
                            </td>*/
                            nl2('<td>
                                '.$torol.'
                                <input type="hidden" class="pop_jogcim" name="jogcim" value="'.$_SESSION['fennt_szerz_tetel_jogcim'][$fej_id][$i].'">
                                <input type="hidden" class="pop_telephely_id" value="'.$_SESSION['fennt_szerz_tetel_telephely_id'][$fej_id][$i].'">
                                <input type="hidden" class="pop_valtozott" value="'.$_SESSION['fennt_szerz_tetel_valtozott'][$fej_id][$i].'">
                                <input type="hidden" class="pop_recid" name="recid" value="'.$_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i].'">
                            </td>
                        </tr>');
        $i++;
    }

    // Szálloda select előállítása, visszavezetve a selectek.php + switch.php működésére
    require_once 'selectek.php';
    $akt_tomb = array(
                        array('type' => 'select_felh_telephely',   'megn' => 'Szálloda:',   'name' => 'pop_telephely_id',  'class' => 'disableNav csillag', 'style' => 'width: 100%;'),
                );
    // Számít, hogy ki a költségviselő (befolyásolja, hogy milyen szállodák lesznek a lenyílóban), ezért kell ez az alábbi sor
    $koltsegviselo_szalloda_lenyilohoz = $_POST['kolt_vis'];
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
    $targy_adat     = 2;
    $varh_erk_adat  = 3;
    $varh_ert_adat  = 4;
    $jogcim_adat    = 5;
    $telj_dt_adat   = 6;
    $netto_ert_adat = 7;
    $valtozott_adat = 8;
    
    $tetelek_osszege = '';
    $i = 0;
    foreach ( $_POST['adatok'][$varh_ert_adat][1] as $ertek ) {
        $_POST['adatok'][$varh_ert_adat][1][$i] = str_replace(array(' ', ','), array('', '.'), $ertek);
        $tetelek_osszege += $_POST['adatok'][$varh_ert_adat][1][$i];
        $i++;
    }
    
    // Új feltöltésnél még nincs recid-n
    if( !$recid ) {
        $szerzodes = array();
        $szerzodes['nullas'] = 0;
        if( $_POST['nullas'] == 'true' ) {
            $szerzodes['nullas'] = 1;
        }
    } else {
        $szerzodes = $adatbazis_kapcsolat->s2('nullas', 'fenntartasi_szerzodes', "`recid`='{$recid}'");
        if( $szerzodes === false ) { $db_error = true; }
    }
    
    // Deviza esetén tört számokkal is számolunk
    $penznem   = get_penznem();
    $devizas   = false;
    $tizedesek = 0;
    $db_mezo   = 'netto_ert';
    $varh_mezo = 'varh_ert';
    $kerekit   = KEREKITESI_HATAR_HUF;
    if( $_POST['kcs_21'] && $_POST['kcs_21']!=KCS_21_HUF ) {
        $devizas   = true;
        $tizedesek = 2;
        $penznem   = erteklista(ERTEKLISTA_PENZNEM, $_POST['kcs_21']);
        $db_mezo   = 'dev_net';
        $varh_mezo = 'dev_varh_ert';
        $kerekit   = KEREKITESI_HATAR_DEV;
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
    } elseif( !$szerzodes['nullas'] && abs($tetelek_osszege - $szerzodes_netto_erteke) > $kerekit ) {
        echo json_encode( array( 
                        'hiba'  => 'A szerződés tételeinek összege ('. number_format($tetelek_osszege, $tizedesek, ',', ' ').' '.$penznem.') nem egyezik meg a szerződés nettó értékével ('.number_format($szerzodes_netto_erteke, $tizedesek, ',', ' ').' '.$penznem.')!<br>Így nem menthető!',
                        'title' => 'Hiba'
                        ) );
                $weblap->leallit();
                exit();
                
    // Tételek kimentése Session-be
    } else {
        
        // Ha nem ismert a recid, a $fej_id legyen "uj"
        $fej_id = $recid;
        if( !$recid ) {
            $fej_id = 'uj';
        }
        
        $_SESSION['fennt_szerz_tetel_recid'         ][$fej_id] = $_POST['adatok'][$recid_adat][1];
        $_SESSION['fennt_szerz_tetel_telephely_id'  ][$fej_id] = $_POST['adatok'][$telephely_adat][1];
        $_SESSION['fennt_szerz_tetel_targy'         ][$fej_id] = $_POST['adatok'][$targy_adat][1];
        $_SESSION['fennt_szerz_tetel_varh_erk'      ][$fej_id] = $_POST['adatok'][$varh_erk_adat][1];
        if( $devizas ) {
            $_SESSION['fennt_szerz_tetel_dev_varh_ert'][$fej_id] = $_POST['adatok'][$varh_ert_adat][1];
        } else {
            $_SESSION['fennt_szerz_tetel_varh_ert'][$fej_id] = $_POST['adatok'][$varh_ert_adat][1];
        }
        $_SESSION['fennt_szerz_tetel_jogcim'    ][$fej_id] = $_POST['adatok'][$jogcim_adat][1];
        $_SESSION['fennt_szerz_tetel_telj_dt'   ][$fej_id] = $_POST['adatok'][$telj_dt_adat][1];
        if( $devizas ) {
            $_SESSION['fennt_szerz_tetel_dev_net'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        } else {
            $_SESSION['fennt_szerz_tetel_netto_ert'][$fej_id] = str_replace(',', '.', $_POST['adatok'][$netto_ert_adat][1]);
        }
        $_SESSION['fennt_szerz_tetel_valtozott'][$fej_id] = $_POST['adatok'][$valtozott_adat][1];
    }

    // Adatbázisba mentést csak akkor csinálnuk, ha már meglevő szerződést szerkesztünk
    if( $recid ) {
        
        $fennt_szerz = $adatbazis_kapcsolat->s2('arfolyam', 'fenntartasi_szerzodes', "`recid`='{$recid}'");
        if( $fennt_szerz === false ) { $db_error = true; }
        
        $i = 0;
        foreach ($_POST['adatok'][$telephely_adat][1] as $telephely) {

            $telephely  = $_POST['adatok'][$telephely_adat  ][1][$i];
            $targy      = $_POST['adatok'][$targy_adat      ][1][$i];
            $varh_erk   = $_POST['adatok'][$varh_erk_adat   ][1][$i];
            $varh_ert   = $_POST['adatok'][$varh_ert_adat   ][1][$i];
            $jogcim     = $_POST['adatok'][$jogcim_adat     ][1][$i];
            $telj_dt    = $_POST['adatok'][$telj_dt_adat    ][1][$i];
            $netto      = $_POST['adatok'][$netto_ert_adat  ][1][$i];
            $valtozott = 0;
            if( $_POST['adatok'][$valtozott_adat][1][$i] ) {
                $valtozott = 1;
            }

            $jogcim = $adatbazis_kapcsolat->s2('recid jogcim uzemresz megn szla_szam ktghely ktghely_id', 'jogcim', "`recid`='{$jogcim}'");

            // Ha ismerjük a tétel recid-ját, akkor update
            if( $_POST['adatok'][$recid_adat][1][$i] ) {
                
                $mezok      = array('telephely_id', 'targy',  'varh_erk', $varh_mezo, 'jogcim_recid',   'jogcim',            'ktgnem_id',           'ktgnem',        'ktghely_id',           'ktghely',           'telj_dt', $db_mezo,    'valtozott' );
                $ertekek    = array($telephely,     $targy,   $varh_erk,  $varh_ert,  $jogcim['recid'],  $jogcim['jogcim'],  $jogcim['szla_szam'],  $jogcim['megn'], $jogcim['ktghely_id'],  $jogcim['ktghely'],  $telj_dt,  $netto,      $valtozott  );
                if( $devizas ) {
                    array_push($mezok,   'netto_ert',                       'varh_ert'                          );
                    array_push($ertekek, $netto*$fennt_szerz['arfolyam'],   $varh_ert*$fennt_szerz['arfolyam']  );
                }
                
                $sql = $adatbazis_kapcsolat->u('fenntartasi_szerzodes_tetel', $mezok, $ertekek, "`recid`='{$_POST['adatok'][$recid_adat][1][$i]}'");
                if( $sql === false ) { $db_error = true; }
                
            // Egyébként insert
            } else {
                $mezok      = array('fenntartasi_szerzodes_recid', 'rownum', 'telephely_id', 'targy',  'varh_erk', $varh_mezo, 'jogcim_recid',    'jogcim',           'ktgnem_id',           'ktgnem',        'ktghely_id',           'ktghely',            'telj_dt', $db_mezo, 'valtozott', 'loginnev',                         'insert_dt',    'insert_usr',                             'update_usr'                          );
                $ertekek    = array($recid,                        $rownum,  $telephely,     $targy,   $varh_erk,  $varh_ert,  $jogcim['recid'],  $jogcim['jogcim'],  $jogcim['szla_szam'],  $jogcim['megn'], $jogcim['ktghely_id'],  $jogcim['ktghely'],   $telj_dt,  $netto,   '1',         $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev']);
                if( $devizas ) {
                    array_push($mezok,   'netto_ert',                       'varh_ert'                          );
                    array_push($ertekek, $netto*$fennt_szerz['arfolyam'],   $varh_ert*$fennt_szerz['arfolyam']  );
                }
                $fej_id = $recid;
                $rownum = $adatbazis_kapcsolat->s2('MAX(`rownum`)', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$recid}'");
                $rownum = $rownum['MAX(`rownum`)']+1;
                $uj_id = $adatbazis_kapcsolat->i('fenntartasi_szerzodes_tetel', $mezok, $ertekek);
                if( $sql === false ) { $db_error = true; }
                $_SESSION['fennt_szerz_tetel_recid'][$fej_id][$i] = $uj_id;
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

// CSV generálása
if( isset($_GET['csv']) ) {
    
    require_once 'selectek.php';

    $csv_neve = 'Fenntartási-szerződés';
    if( isset($_GET['tetelek']) ) {
        $csv_neve .= '-tételek';
    }
    
    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        if( $_SESSION['nyomtatas_ids'] ) {

            // Tételek exportja
            if( isset($_GET['tetelek']) ) {
                
                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Ez olyan modulok alatt szükséges, ahol van egy alapértelmezett limit-es szűrés)
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == ARCHIV_NAVPANEL_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
                        $mindet_listazd = true;
                    }
                }
                if( $mindet_listazd ) {
                    
                    // A LIMIT-et levágjuk, illetve a balnavpanel szerinti rendezést is nullázzuk
                    $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, strpos($lekerdezes_fromtol, 'LIMIT') );
                    $_SESSION['akt_sql_order'] = '';
                    
                } else {
                    
                    if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                        $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                    } else {
                        $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                    }

                    // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
                    if( $_SESSION['akt_sql_order'] ){
                        $hossz = strpos($lekerdezes_fromtol, 'ORDER');
                        if( $hossz ) {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
                        } else {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
                        }
                    }
                    
                }
                
                // Ha nincs FROM rész, akkor azt még beletesszük
                if( !$lekerdezes_fromtol ) {
                    $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
                }
                
                // Van benne egy GROUP BY feltétel is, ezt is vegyük ki, hogy ahány tétel, annyiszor benne legyen a fej is
                $lekerdezes_fromtol = str_replace('GROUP BY  `'.get_db_tabla().$megnevezes.'`.`recid`', '', $lekerdezes_fromtol);

                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`hatalyos` as \'Hatályos\',
                            `'.get_db_tabla().$megnevezes.'`.`szszam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`verzio` as \'Szerződés verzió\',
                            `'.get_db_tabla().$megnevezes.'`.`sztipus` as \'Szerződés típus\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte_dt` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Szerződő partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`erv_dt` as \'Érvényesség kezdete\',
                            `'.get_db_tabla().$megnevezes.'`.`bef_dt` as \'Érvényesség vége\',
                            `'.get_db_tabla().$megnevezes.'`.`kcs_21` as \'Pénznem\',
                            `'.get_db_tabla().$megnevezes.'`.`szlafizhi` as \'Számla fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_net` as \'Deviza nettó\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam` as \'Árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`alairva_dt` as \'Aláírva\',
                            `'.get_db_tabla().$megnevezes.'`.`felmondas` as \'Felmondás\',
                            `'.get_db_tabla().$megnevezes.'`.`nullas` as \'Érték nélküli szerződés\',
                            `'.get_db_tabla().$megnevezes.'`.`fiz_felt` as \'Fizetési feltétel\',
                            `'.get_db_tabla().$megnevezes.'`.`p_nev` as \' Szerződő fél kapcsolattartó neve\',
                            `'.get_db_tabla().$megnevezes.'`.`p_cim` as \' Szerződő fél kapcsolattartó címe\',
                            `'.get_db_tabla().$megnevezes.'`.`p_tel` as \' Szerződő fél kapcsolattartó telefonszáma\',
                            `'.get_db_tabla().$megnevezes.'`.`p_beosztas` as \' Szerződő fél kapcsolattartó beosztása\',
                            `'.get_db_tabla().$megnevezes.'`.`p_email` as \' Szerződő fél kapcsolattartó e-mail címe\',
                            `'.get_db_tabla().$megnevezes.'`.`p_fax` as \' Szerződő fél kapcsolattartó fax száma\',
                            `'.get_db_tabla().$megnevezes.'`.`megjegyz` as \'Megjegyzés\',
                            '.
                            
                            // Innentől a tételek
                            '
                            `'.get_db_tabla().$megnevezes.'_tetel`.`telephely_id` as `Szálloda`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_erk` as `Számla várh. érk.`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`varh_ert` as `Számla várh. értéke (Ft)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_varh_ert` as `Számla várh. értéke (devizában)`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`telj_dt` as `Teljesítés`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`netto_ert` as `Érték`,
                            `'.get_db_tabla().$megnevezes.'_tetel`.`dev_net` as `Érték (devizában)`
                            '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];
                
                $query = mysqli_query($db_kapcs, $sql);

                // Nagyobb CSV-kre kérdezzünk rá előzetesen
                $osszes_sor = mysqli_num_rows($query);
//  Itt ilyet ne csináljunk, mert a csv_link_cserel_kattint 3 paraméterét nem tudom kimenteni
//                if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
//                    $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="csv_link_cserel_kattint();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
//                    echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
//                    $weblap -> leallit();
//                    exit;
//                } else {
//                    $sql .= $_SESSION['akt_sql_order'];
//                }

                $query      = mysqli_query($db_kapcs, $sql);

                $csvhead    = array();
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                }
                fputcsv($open, $csvhead, ';');
                
                $datum_mezok = array('Kelte', 'Érvényesség kezdete', 'Érvényesség vége', 'Számla fizetési határidő', 'Aláírva', 'Számla várh. érk.', 'Teljesítés');
                $szam_mezok  = array('Deviza nettó', 'Árfolyam', 'Nettó érték', 'Számla várh. értéke (Ft)', 'Számla várh. értéke (devizában)', 'Érték', 'Érték (devizában)');

                $i = 0;
                while ( $sorok = mysqli_fetch_assoc($query) ) {

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {
                        if( $kulcs == 'Szálloda' ) {
                            $sor = getSzallodaNev($sor);
                        }
                        if( $kulcs == 'Szerződő partner' ) {
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if( $kulcs == 'Költségviselő' ) {
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if( $kulcs == 'Pénznem' ) {
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if( $kulcs == 'Szerződés típus' ) {
                            foreach ( $select_szerzodes_tipus as $tipus ) {
                                if ( $sor == $tipus['value'] ) {
                                    $sor = $tipus['name'];
                                }
                            }
                        }

                        if( $kulcs == 'Hatályos' || $kulcs == 'Érték nélküli szerződés' ) {
                            if( $sor ) {
                                $sor = 'igen';
                            } else {
                                $sor = 'nem';
                            }
                        }
                        
                        // Dátumok és számok formázása
                        if( in_array($kulcs, $datum_mezok) ) {
                            $sor = datum_kiir($sor);
                        }
                        if( in_array($kulcs, $szam_mezok) ) {
                            $sor = str_replace('.', ',', $sor);
                        }
                        
                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
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

            // Projektszerződések exportja
            } else {

                // Ha van benne limit, akkor a teljes adatbázist kilistázzuk, de csak akkor, ha a limit az alapértelmezett (Ez olyan modulok alatt szükséges, ahol van egy alapértelmezett limit-es szűrés)
                $mindet_listazd = false;
                if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
                    $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
                    if( strpos($limit, ' ') ) {
                        $limit = substr($limit, 0, strpos($limit, ' '));
                    }
                    if( $limit == ARCHIV_NAVPANEL_LIMIT && !$_SESSION['nyomtatas_ids_ksz'] ) {
                        $mindet_listazd = true;
                    }
                }
                if( $mindet_listazd ) {
                    
                    // A LIMIT-et levágjuk, illetve a balnavpanel szerinti rendezést is nullázzuk
                    $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, strpos($lekerdezes_fromtol, 'LIMIT') );
                    $_SESSION['akt_sql_order'] = '';
                    
                } else {
                    
                    if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                        $lekerdezes_fromtol = str_replace('WHERE', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
                    } else {
                        $lekerdezes_fromtol = str_replace('ORDER', "WHERE `".get_db_tabla().$megnevezes."`.`recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
                    }
                    
                    // Ha van $_SESSION['akt_sql_order'], akkor eszerint rendezünk, tehát a $lekerdezes_fromtol-t elvágjuk az Order-nél
                    if( $_SESSION['akt_sql_order'] ){
                        $hossz = strpos($lekerdezes_fromtol, 'ORDER');
                        if( $hossz ) {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0, $hossz);
                        } else {
                            $lekerdezes_fromtol = substr($lekerdezes_fromtol, 0);
                        }
                    }
                    // Ha nincs FROM rész, akkor azt még beletesszük
                    if( !$lekerdezes_fromtol ) {
                        $lekerdezes_fromtol = ' FROM `'.get_db_tabla().$megnevezes.'` ';
                    }
                    
                }
                
                $sql = 'SELECT
                            `'.get_db_tabla().$megnevezes.'`.`hatalyos` as \'Hatályos\',
                            `'.get_db_tabla().$megnevezes.'`.`szszam` as \'Szerződés szám\',
                            `'.get_db_tabla().$megnevezes.'`.`verzio` as \'Szerződés verzió\',
                            `'.get_db_tabla().$megnevezes.'`.`sztipus` as \'Szerződés típus\',
                            `'.get_db_tabla().$megnevezes.'`.`kelte_dt` as \'Kelte\',
                            `'.get_db_tabla().$megnevezes.'`.`szallito_id` as \'Szerződő partner\',
                            `'.get_db_tabla().$megnevezes.'`.`targy` as \'Tárgy\',
                            `'.get_db_tabla().$megnevezes.'`.`kolt_vis` as \'Költségviselő\',
                            `'.get_db_tabla().$megnevezes.'`.`erv_dt` as \'Érvényesség kezdete\',
                            `'.get_db_tabla().$megnevezes.'`.`bef_dt` as \'Érvényesség vége\',
                            `'.get_db_tabla().$megnevezes.'`.`kcs_21` as \'Pénznem\',
                            `'.get_db_tabla().$megnevezes.'`.`szlafizhi` as \'Számla fizetési határidő\',
                            `'.get_db_tabla().$megnevezes.'`.`dev_net` as \'Deviza nettó\',
                            `'.get_db_tabla().$megnevezes.'`.`arfolyam` as \'Árfolyam\',
                            `'.get_db_tabla().$megnevezes.'`.`netto` as \'Nettó érték\',
                            `'.get_db_tabla().$megnevezes.'`.`alairva_dt` as \'Aláírva\',
                            `'.get_db_tabla().$megnevezes.'`.`felmondas` as \'Felmondás\',
                            `'.get_db_tabla().$megnevezes.'`.`nullas` as \'Érték nélküli szerződés\',
                            `'.get_db_tabla().$megnevezes.'`.`fiz_felt` as \'Fizetési feltétel\',
                            `'.get_db_tabla().$megnevezes.'`.`p_nev` as \' Szerződő fél kapcsolattartó neve\',
                            `'.get_db_tabla().$megnevezes.'`.`p_cim` as \' Szerződő fél kapcsolattartó címe\',
                            `'.get_db_tabla().$megnevezes.'`.`p_tel` as \' Szerződő fél kapcsolattartó telefonszáma\',
                            `'.get_db_tabla().$megnevezes.'`.`p_beosztas` as \' Szerződő fél kapcsolattartó beosztása\',
                            `'.get_db_tabla().$megnevezes.'`.`p_email` as \' Szerződő fél kapcsolattartó e-mail címe\',
                            `'.get_db_tabla().$megnevezes.'`.`p_fax` as \' Szerződő fél kapcsolattartó fax száma\',
                            `'.get_db_tabla().$megnevezes.'`.`megjegyz` as \'Megjegyzés\'
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
                while($fejlec_nevek = mysqli_fetch_field($query)){
                    $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                }
                fputcsv($open, $csvhead, ';');

                
                $datum_mezok = array('Kelte', 'Érvényesség kezdete', 'Érvényesség vége', 'Számla fizetési határidő', 'Aláírva', 'Számla várh. érk.', 'Teljesítés');
                $szam_mezok  = array('Deviza nettó', 'Árfolyam', 'Nettó érték');

                $i = 0;
                while ( $sorok = mysqli_fetch_assoc($query) ) {

                    $csvcont = array();
                    foreach( $sorok as $kulcs => $sor ) {

                        if( $kulcs == 'Szerződő partner' ) {
                            $sor = getErtek('nev', 'szallito', 'id', $sor);
                        }
                        if( $kulcs == 'Költségviselő' ) {
                            $sor = erteklista(ERTEKLISTA_KOLTSEGVISELO, $sor);
                        }
                        if( $kulcs == 'Pénznem' ) {
                            $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                        }
                        if( $kulcs == 'Szerződés típus' ) {
                            foreach ( $select_szerzodes_tipus as $tipus ) {
                                if ( $sor == $tipus['value'] ) {
                                    $sor = $tipus['name'];
                                }
                            }
                        }

                        if( $kulcs == 'Hatályos' || $kulcs == 'Érték nélküli szerződés' ) {
                            if( $sor ) {
                                $sor = 'igen';
                            } else {
                                $sor = 'nem';
                            }
                        }
                        
                        // Dátumok és számok formázása
                        if( in_array($kulcs, $datum_mezok) ) {
                            $sor = datum_kiir($sor);
                        }
                        if( in_array($kulcs, $szam_mezok) ) {
                            $sor = str_replace('.', ',', $sor);
                        }
                        
                        $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( htmlspecialchars_decode( $sor ) ) );
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

            }
        }
        
        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();

    }

    csv_export_lezar($fh, $filename, $csv_neve);
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
    
    // Ha épp egy szűrésben vagyunk, akkor a szűrés értékeit mentsük ki SESSION-be, hogy a tételek fül alatt, ha ajaxosan töltenénk be tartalmat
    // akkor is meglegyenek ezek az info-k (ajax-ban nincsenek benne ezek a szűrés GET-ek)
    if( $_GET['mezo'] && $_GET['felt'] ) {
        $_SESSION['akt_szuro_mezo'] = $_GET['mezo'];
        $_SESSION['akt_szuro_felt'] = $_GET['felt'];
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
        $szerzodes = $adatbazis_kapcsolat->s2('recid kcs_21 szszam', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");
    } else if( _jog(JOG_FENNTARTASI_SZERZ_OLVAS) ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$_GET['id']}'", 'insert_dt');
    }
    
    $i = 0;
    $ossz_netto   = 0;
    $ossz_dev_net = 0;
    $fej_id       = $_GET['id'];
    $_SESSION['fennt_szerz_tetel_recid'         ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_telephely_id'  ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_targy'         ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_varh_erk'      ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_varh_ert'      ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_dev_varh_ert'  ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_jogcim'        ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_kgtnem'        ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_kgthely'       ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_telj_dt'       ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_netto_ert'     ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_dev_net'       ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_valtozott'     ][$fej_id] =
    $_SESSION['fennt_szerz_tetel_andocos'       ][$fej_id] =
                                                            array();
    foreach ( $tetelek as $tetel ) {
        $ossz_netto   += $tetel['netto_ert'];
        $ossz_dev_net += $tetel['dev_net'];
        $_SESSION['fennt_szerz_tetel_recid'         ][$fej_id][$i] = $tetel['recid'];
        $_SESSION['fennt_szerz_tetel_telephely_id'  ][$fej_id][$i] = $tetel['telephely_id'];
        $_SESSION['fennt_szerz_tetel_targy'         ][$fej_id][$i] = $tetel['targy'];
        $_SESSION['fennt_szerz_tetel_varh_erk'      ][$fej_id][$i] = str_replace('-', '.', $tetel['varh_erk']);
        $_SESSION['fennt_szerz_tetel_varh_ert'      ][$fej_id][$i] = number_format($tetel['varh_ert'], 0, '', ' ');
        $_SESSION['fennt_szerz_tetel_dev_varh_ert'  ][$fej_id][$i] = number_format($tetel['dev_varh_ert'], 2, ',', ' ');
        $_SESSION['fennt_szerz_tetel_jogcim'        ][$fej_id][$i] = $tetel['jogcim_recid'];
        $_SESSION['fennt_szerz_tetel_kgtnem'        ][$fej_id][$i] = $tetel['kgtnem_id'];
        $_SESSION['fennt_szerz_tetel_kgthely'       ][$fej_id][$i] = $tetel['kgthely_id'];
        $_SESSION['fennt_szerz_tetel_telj_dt'       ][$fej_id][$i] = str_replace('-', '.', $tetel['telj_dt']);
        $_SESSION['fennt_szerz_tetel_netto_ert'     ][$fej_id][$i] = number_format($tetel['netto_ert'], 0, '', ' ');
        $_SESSION['fennt_szerz_tetel_dev_net'       ][$fej_id][$i] = number_format($tetel['dev_net'],   2, ',', ' ');
        $_SESSION['fennt_szerz_tetel_valtozott'     ][$fej_id][$i] = $tetel['valtozott'];
        $_SESSION['fennt_szerz_tetel_andocos'       ][$fej_id][$i] = $tetel['andocos'];

        $i++;
    }
    
    $js_submit_elott = 'return form_ell_admin();';
    
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
        
        // Ha már volt regisztrálva új felvitel, akkor be se töltsük az új felvitel felületet
        if( $_COOKIE['uj_felvitel_'.$megnevezes] ) {
            $szamla_html .= nl2('
                                <h1 class="piros">A Fenntartási szerződés feltöltése már megkezdődött,<br>kérem előbb azt fejezze be, vagy lépjen ki belőle!</h1>
                                <script>
                                    setTimeout(function(){
                                        $(\'#szerk_form\').hide();
                                    }, 200);
                                </script>
                            ');

        // Regisztráljuk, hogy elkezdődött egy új felvitel, hogy ne lehessen két új felvitelt elindítani
        } else {
        
            // Regisztráljuk, hogy elkezdődött egy új felvitel, hogy ne lehessen két új felvitelt elindítani
            $szamla_html .= nl2('
                            <script>
                                createCookie(\'uj_felvitel_\'+baseName(location), \''.$_GET['andoc_id'].'\', 1);
                            </script>
                        ');

            // Új feltöltéskor a tétel sorokat töröljük, ne az legyen, hogy szerkesztésből maradt adatokkal számoljunk
            $fej_id = 'uj';
            $_SESSION['fennt_szerz_tetel_recid'         ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_telephely_id'  ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_targy'         ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_varh_erk'      ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_telj_dt'       ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_varh_ert'      ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_dev_varh_ert'  ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_netto_ert'     ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_dev_net'       ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_valtozott'     ][$fej_id] = '';
            $_SESSION['fennt_szerz_tetel_andocos'       ][$fej_id] = '';

            // Új szerződés rögzítésekor ellenőrizni kell, hogy van-e már legalább egy tétel
            // Illetve a tételek összege azonos-e a szerződés nettójával
            $js_submit_elott = nl2('

                if( !$(\'#van_tetel\').val() ) {
                    alert2(\'Szerződés rögzítéséhez legalább egy tételt töltsön fel!\', \'\', \'Hiba\');
                    return false;
                }

                var tizedesek   = 2;
                var mezo1       = \'ossz_dev_net\';
                var mezo2       = \'dev_net\';
                var kerekit     = \''.KEREKITESI_HATAR_DEV.'\';
                if(  $(\'#kcs_21\').val()==\''.KCS_21_HUF.'\' ) {
                    tizedesek   = 0;
                    mezo1       = \'ossz_netto\';
                    mezo2       = \'netto\';
                    kerekit     = \''.KEREKITESI_HATAR_HUF.'\';
                }

                var netto_ossz   = Number($(\'#\'+mezo2).val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                var tetelek_ossz = Number($(\'#\'+mezo1).val().replace(/ /g, \'\').replace(/,/g, \'.\'));
                if( $(\'#nullas\').is(\':checked\') == false ) {
                    if( Math.abs( Number(tetelek_ossz)-Number(netto_ossz) ) > kerekit ) {
                        alert2(\'A szerződés tételeinek összege (\'+ezresek(tetelek_ossz, tizedesek, \',\', \' \', \'-nullát is írja ki-\')+\' \'+$(\'#kcs_21_autocomp\').val()+\') nem egyezik meg a szerződés nettó értékével(\'+ezresek(netto_ossz, tizedesek, \',\', \' \', \'-nullát is írja ki-\')+\' \'+$(\'#kcs_21_autocomp\').val()+\')!<br>Így nem menthető!\', \'\', \'Hiba\');
                        return false;
                    }
                }
                return form_ell_admin();
                ');

            // Ha foglalt volna az Andoc azonosító vagy az iktatószám, akkor mentéskor küldjünk hibaüzenetet
            // Éles rendszerben ilyen nem fordulhat elő, de teszelésekkor szükséges, hogy ha gond volna, tudjunk róla
            $onclick = 'if( form_ell_admin_elott() ){ uj_szerzodes_ellenorzes(); }';
// 2024.10.06 Volt róla szó, hogy létre kell tudni hozni új üres szerződést is, nem csak Andocból. Sőt, ez lényegében meg is lett csinálva, volt is róla levelezve.
// Viszont mégsem kérték, ezér itt is megállítjuk ezt a folyamatot, a bizti kedvéért.
// (Erre a "2024.10.06 Volt róla szó, hogy"-ra ha rákeresel találsz még ide vonatkozó kódokat.)
if( !$_GET['andoc_id'] ) {
    echo '<script>location.href=\'fenntartasi_szerzodes.php\'</script>';
}
            if( $adatbazis_kapcsolat -> s2('recid', 'fenntartasi_szerzodes', "`andoc_id`='{$_GET['andoc_id']}'") ) {
                $onclick = 'alert2(\''.a_az($_GET['andoc_id'], '-nagybetű-').' „'.$_GET['andoc_id'].'” Andoc azonosító már foglalt, így nem menthető a szerződés!\', \'\', \'Hiba\');';
            }
            if( $adatbazis_kapcsolat -> s2('recid', 'fenntartasi_szerzodes', "`szszam`='{$szerzodes['szszam']}'") ) {
                $onclick = 'alert2(\''.a_az($szerzodes['ikt_szam'], '-nagybetű-').' „'.$szerzodes['szszam'].'” szerződés szám már foglalt, így nem menthető a szerződés!\', \'\', \'Hiba\');';
            }

            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="hidden" name="atmeneti_recid" value="'.$szerzodes['recid'].'">
                    <input type="hidden" id="van_tetel" value="">
                    <input type="hidden" id="ossz_netto" value="'.$ossz_netto.'">
                    <input type="hidden" id="ossz_dev_net" value="'.$ossz_dev_net.'">
                    <input type="button" class="gombb mentes_gomb gombb_alert" name="mehet_gomb" id="mehet_gomb" value="Mentés" onclick="'.$onclick.'">
                    <input type="button" class="gomb piros" id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
                </div>
            ');
            $gombok .= '<input type="button" class="gomb" value="Tételek kezelése" onclick="csoportMuvelet(\'fenntartasiszerzodes_tetelek_kezelese\');"> ';
            $gombok .= '<input type="button" class="gomb" value="Projekt szerződés" onclick="projekt_szerzodes();"> ';

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

            $gombok .= nl2('
                        <script>
                        function uj_szerzodes_ellenorzes() {

                            szurkit(\'mehet_megsem_rejt\');

                            formModositva = false;

                            /* Szerződés szám foglalt-e már */
                            $.ajax({
                                url : \'fenntartasi_szerzodes.php\',
                                type: \'POST\',
                                data: esamp2es(\'szszam=\' + $(\'#szszam\').val() + \'&amp;szszam_letezik=\' + new Date().getTime()),
                                success: function(response) {
                                    if(response.substring(0, 6)==\'SESKIL\'){ location.href = \'index.php\'; }
                                    if(response) {
                                        response = JSON.parse(response);

                                        if( response.hiba ) {
                                            alert2(response.hiba, response.gomb, response.title);
                                            alert_utan_mehet_megsem_mutat_beallit();
                                            if(response.focus){
                                                $(\'#\'+response.focus).addClass(\'hibas_ide_focus\');
                                            }
                                        } else {
                                            $(\'#szerk_form\').submit();
                                        }

                                    }
                                    return false;
                                },
                                complete: function(xhr) {
                                    if( xhr.status != 200 ) {
                                        alert2(xhr.responseText, \'\', \'Hiba\');
                                    }
                                }
                            });

                        }
                        </script>
                ');
        }

    // Módosítás gombok
    } else {

        // Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';

        // Jogosutságkezelés
        if( !_jog(JOG_FENNTARTASI_SZERZ_KEZEL) ){
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága törölni ezt a '.$szoveg.'!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
        } elseif( $sor['andoc_id'] ) {
            $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Ez a szerződés Andocból származik, ezért itt nem törölhető!\', \'\', \'Hiba\')" value="Töröl" title="Töröl">';
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
            $gombok .= '<input type="hidden" id="kcs_21_autocomp_db" value="'.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).'">';
            $gombok .= '<input type="hidden" id="kcs_21_db" value="'.$sor['kcs_21'].'">';
            $gombok .= '<input type="hidden" id="netto_db" value="'.number_format($sor['netto'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="dev_net_db" value="'.number_format($sor['dev_net'], $tizedesek, ',', ' ').'">';
            $gombok .= '<input type="hidden" id="kolt_vis_db" value="'.$sor['kolt_vis'].'">';
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
        $sor = $adatbazis_kapcsolat->s2('*', 'fenntartasi_szerzodes_atmeneti', "`andoc_id`='{$_GET['andoc_id']}'");

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

        // Javascript-es ellenőrzéshez
        $tabla = str_replace('.php', '', basename(self()));

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
        
        // Ha épp egy sikeres feltöltés után vagyunk, akkor töröljük a Cookie-t
        if( isset($_GET['siker']) ) {
            $szamla_html .= nl2('
                            <script>
                                eraseCookie(\'uj_felvitel_\'+baseName(location));
                            </script>
                        ');
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
                    <div class="dtc">Tárgy</div>
                    <div class="dtc">Számla várh. érk.</div>
                    <div class="dtc r">Számla várh.<br>értéke ('.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')</div>
                    <div class="dtc">Teljesítés</div>
                    <div class="dtc r">Érték ('.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')</div>
                </div>
        ');
        
        $osszeg_oszlop = 'varh_ert';
        $ertek_oszlop  = 'netto_ert';
        $tizedesek     = 0;
        if( $sor['kcs_21'] && $sor['kcs_21']!=KCS_21_HUF ) {
            $osszeg_oszlop = 'dev_varh_ert';
            $ertek_oszlop  = 'dev_net';
            $tizedesek     = 2;
        }
        
        // Tételek felsorolása
        // Szűrünk felhasználó szállodáira, de ha szállodára szűrés, akkor azzal is számolunk
        $sql_kieg = '';

        // Sima szűrés (kimentve SESSION-be, hogy ajaxos betöltéskor (pl. nav panelben vagy füleken lépkedve) is megmaradjon ez az info
        if( $_SESSION['akt_szuro_mezo'] == 'telephely_id' ) {
            $sql_kieg .= " AND `telephely_id`='{$_SESSION['akt_szuro_felt']}'";
        }
        // Összetett szűrés esetén ki kell számolni
        $keresek = explode('me|', $_SESSION['o_szuro_feltetel']);
        foreach ( $keresek as $keres ) {
            if( substr($keres, 0, 12) == 'telephely_id' ) {
                $ertek = explode('#fe|', $keres);
                $ertek = str_replace(array('#', 'es|'), array('', ''), $ertek[1]);
                $sql_kieg .= " AND `telephely_id`='{$ertek}'";
            }
        }
        $szallodak = get_akt_login_szallodai();
        if( $szallodak ) {
            $tetelek = $adatbazis_kapcsolat->s('telephely_id, targy, telj_dt, netto_ert, dev_net, varh_erk, varh_ert, dev_varh_ert', 'fenntartasi_szerzodes_tetel', "`fenntartasi_szerzodes_recid`='{$_GET['id']}' AND `telephely_id` IN (".implode(',', $szallodak)."){$sql_kieg}", 'insert_dt');
            foreach ( $tetelek as $tetel ) {

                $szamla_html .= nl2('
                    <div class="dtr nSor">
                        <div class="dtc">'.getSzallodaNev($tetel['telephely_id']).'</div>
                        <div class="dtc">'.$tetel['targy'].'</div>
                        <div class="dtc">'.datum_kiir($tetel['varh_erk']).'</div>
                        <div class="dtc r nowrap">'.number_format($tetel[$osszeg_oszlop],  $tizedesek, ',', ' ').'</div>
                        <div class="dtc">'.datum_kiir($tetel['telj_dt']).'</div>
                        <div class="dtc r nowrap">'.number_format($tetel[$ertek_oszlop], $tizedesek, ',', ' ').'</div>
                    </div>
                ');
            }
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

        // Ha nincs andoc_id, akkor ez egy manuálisan létrehozott szerződés, ekkor a dokumentumok fül a hagyományos működésű legyen.
        if( !$sor['andoc_id'] ) {
            
            require_once('tobb_kep.php');

        // Egyébként a speciálisabb dokumentumok fül tartalmát töltsük be
        } else {

            // Itt az XML-ből kapott dokumentum linkjét jelenítjük meg
            if( $sor['dok_link'] ) {
                $szamla_html .= nl2('
                    Fődokumentum link:
                    <br><input type="text" value="'.$sor['dok_link'].'" readonly style="width: 100%; margin: 1rem 0">
                    <br><input type="button" class="gomb" value="Dokumentum megtekintése" onclick="window.open(\''.$sor['dok_link'].'\')">');
            }
            $szamla_html .= nl2('
                    <br>Bejövő xml:
                    <br>').'<textarea class="oriasiTargy input_kijelolve" style="height: 60rem;" disabled>'.$sor['erk_xml'].'</textarea>';

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

        if( $_GET['id'] ) {
            $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id`='{$_GET['id']}' AND `tabla`='".str_replace('.php', '', basename(self()))."'", 'recid desc');
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