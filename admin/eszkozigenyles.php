<?php
require_once('../weblap.php');

// if( FALSE && isset($_GET['megj_karbantart']) ) {
//     // Ha a szöveg
//     // email elküldve vagy
//     // email küldése vagy
//     // mező előző értéke: vagy
//     // módosítva:
//     // akkor az rendszerüzenet volt

//     $megjegyzesek = $adatbazis_kapcsolat -> s('recid megj', 'megjegyzes', "`tabla` IN ('eszkozigenyles', 'eszkozigenyles_megr', 'szallito_beszerzes')");
//     foreach ($megjegyzesek as $megjegyzes) {
        
//         $user = 0;
//         if(
//             strpos($megjegyzes['megj'], 'E-mail elküldve'            ) === false &&
//             strpos($megjegyzes['megj'], 'E-mail küldése'             ) === false &&
//             strpos($megjegyzes['megj'], 'esemény NEM következett be' ) === false &&
//             strpos($megjegyzes['megj'], 'mező előző értéke'          ) === false &&
//             strpos($megjegyzes['megj'], 'módosítva'                  ) === false
//         ) {
//             $adatbazis_kapcsolat -> u('megjegyzes', array('user'), array(1), "`recid`='{$megjegyzes['recid']}'");
//             echo $megjegyzes['megj'] .'<br>';
//             echo $adatbazis_kapcsolat -> gp().'<br>';
//         }

//     } 

//     echo 'f';
//     exit;
// }

if( isset($_GET['azon_karbantart']) ) {
    $eszkozigenylesek = $adatbazis_kapcsolat -> s('recid tipus', 'eszkozigenyles');
    foreach ($eszkozigenylesek as $eszkozigenyles) {
        $tipus = $adatbazis_kapcsolat -> s2('nev', 'eszkozigenyles_tipus', "`recid`='{$eszkozigenyles['tipus']}'");
        $tipus = mb_substr($tipus['nev'], 0, 1);

        $azon = $tipus.$eszkozigenyles['recid'];
        $sql = $adatbazis_kapcsolat -> u($tabla, array('azon'), array($azon), "`recid`='{$eszkozigenyles['recid']}'");
        if( $sql === false ) { $db_error = true; }
    } 
    echo 'f';
    exit;
}

if( isset($_GET['pdf_mutat']) || isset($_GET['html_mutat']) ) {
    $igeny_id = $_GET['pdf_mutat'].$_GET['html_mutat'];
    if( !$igeny_id ) {
        if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' ) {
            $igeny_id = 240;
        } else {
            $igeny_id = 10;
        }
    }
    megrendeles_keszit($igeny_id);
    exit;
}

if( !_jog(JOG_BESZERZ_IGENYLESEK_OLVAS) ) {
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
$akt_tomb               = $eszkozigenyles_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'eszközigénylést';               // Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Ha státuszléptetéskor nem stimmelnek a státuszok, akkor legnagyobb valószínűséggel arról van szó, hogy ugyanazt az igénylést párhuzamosan más már tovább léptette.
// Ezért inkább ezt a hibaüzenetet írjuk ki:
$statusz_hiba       = 'Az igénylés adatai időközben megváltoztak.<br>A frissítéshez kattintson az Ok gombra.';
$statusz_hiba_gomb  = '<input type="button" class="link gomb escapes_megsem" value="Ok" onclick="location.reload()">';

// Dokumentumok fülhöz kell majd
$tobb_kep = 1;
if( $tobb_kep ) {
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';         } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                      // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Szállító igénylése
if (isset($_POST['szallito_kerese'])) {

    require_once('selectek.php');

    $tipus_kiir = '';
    foreach ($select_besz_tipus as $tipus) {
        if( $tipus['value'] == $_POST['tipus'] ) {
            $tipus_kiir = $tipus['name'];
            break;
        }
    }

    // Attól függően, hogy melyik rendszerben vagyunk, kap a tárgy/üzenet egy [Teszt] előtagot.
    $kieg = get_mail_teszt_felirat();
    $targy_kieg = $kieg[0];
    $uzi_kieg   = $kieg[1];

    // Igénylő adatai (tehát a belépett felhasználó)
    $login = $adatbazis_kapcsolat -> s2('teljes_nev usertel', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");

    $cimzettek = $adatbazis_kapcsolat->s('teljes_nev usermail', 'login', "substr(`flags`, ".(JOG_BESZ_SZALL_KEZEL+1).", 1)=1 AND `usermail`!='' AND `aktiv`=1 AND `virtual`=0");

    $targy = $targy_kieg.'Új szállító kérése';
    $uzi   = $uzi_kieg.'Tisztelt Beszerzési Igazgatóság!
<br>
<br>Önnek egy új szállító regisztráció kérése érkezett:
<br>
<br><b>Az új szállító adatai:</b>
<br>'.nl2br2($_POST['szall_adatok']).'
<br><b>Termék név:</b> '.$_POST['nev'].'
<br><b>Szálloda:</b> '.getSzallodaNev($_POST['szalloda']).'
<br><b>Beszerzés típus:</b> '.$tipus_kiir.'
<br><b>Részleg:</b> '.getErtekLista(ERTEKLISTA_RESZLEG, $_POST['reszleg']).'
<br><b>Igénylő:</b> '.$login['teljes_nev'].'
<br><b>Telefonszáma:</b> '.$login['usertel'].'
<br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');

    
    // Dev rendszerben annak megy ki a levél, aki a gombot megnyomta
    if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' ) {
        $cimzettek = $adatbazis_kapcsolat -> s('teljes_nev usermail', 'login', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
    }
    // Teszt felületen azt kérték, hogy Tamás és Tibi kapjanak másolatot
    if( $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
        array_push($cimzettek, array('teljes_nev' => 'Korcsmáros Tibor', 'usermail' => SZINVATREND_TESZT_MAIL ));
        array_push($cimzettek, array('teljes_nev' => 'Handari Tamás',    'usermail' => SZINVATREND_TESZT_MAIL2));
    }
    $nevek          = [];
    $mailcimek_ossz = [];
    foreach ($cimzettek as $cimzett) {
        $mailcimek = explode(',', $cimzett['usermail']);
        foreach( $mailcimek as $mailcim ) {
            if( $mailcim ) {
                array_push($nevek,          trim($cimzett['teljes_nev']));
                array_push($mailcimek_ossz, trim($mailcim));
            }
        }
    }


if( $_SERVER['REMOTE_ADDR'] == get_debug_ip() ) {
    $mailcimek_ossz = ['bk@webdeb.hu'];
}
    level_kuldes(get_nev(), get_mail(), $nevek, $mailcimek_ossz, $targy, $uzi);
    
    echo json_encode(array(
                            'ok'  => '1',
                     ));

    $weblap -> leallit();
    exit;
}

// Eszközigénylés táblázat rendezésének letárolása SESSION-be
if (isset($_POST['tablazat_rendez'])) {

    $mentett_adat = $adatbazis_kapcsolat->s2('recid tabla_rendezes', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'", '', '1');
    if( $mentett_adat ) {
        $rendezes = array(
                            'tablazat_rendez' => $_POST['oszlop'],
                            'tablazat_irany'  => $_POST['irany' ]
                        );

        if( !$mentett_adat['tabla_rendezes'] ) {
            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($rendezes)), "`recid`='".$_SESSION[get_db_tabla().'login_recid']."'");
        } else {
            $mentett_rendezes = json_decode(htmlspecialchars_decode($mentett_adat['tabla_rendezes']), true);

            $_SESSION['eszkozigenyles_tablazat_rendez'] = $mentett_rendezes['eszkozigenyles_tablazat_rendez'] = $_POST['oszlop'];
            $_SESSION['eszkozigenyles_tablazat_irany' ] = $mentett_rendezes['eszkozigenyles_tablazat_irany' ] = $_POST['irany' ];

            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($mentett_rendezes)), "`recid`='".$_SESSION[get_db_tabla().'login_recid']."'");

        }
    }

    // Nem biztos, hogy küldünk oszlop adatot, lehet csak az irány változik
    if( $_POST['oszlop'] ) {
        $_SESSION['eszkozigenyles_tablazat_rendez'] = $_POST['oszlop'];
    }
    if( $_POST['irany'] ) {
        $_SESSION['eszkozigenyles_tablazat_irany' ] = $_POST['irany'];
    }
    
    // CSV exportban is jó legyen a sorrend ($_SESSION['akt_sql']), ezért hívjuk meg:
    szamla_lista('', 'eszkozigenyles', '', '', $_GET);

    echo json_encode( array('ok' => 1) );

    $weblap->leallit();
    exit();
}

// Táblázat oszlopai, rejtéshez
if (isset($_POST['get_tabla_oszlopok'])) {

    $oszlopok = [
        ['nev' => 'Igény ID',                    'id' => 'eszkoz_tabla_azon'            ],
        ['nev' => 'Időszak ID',                  'id' => 'eszkoz_tabla_idoszak_azon'    ],
        ['nev' => 'Státusz',                     'id' => 'eszkoz_tabla_statusz'         ],
        ['nev' => 'Típus',                       'id' => 'eszkoz_tabla_tipus'           ],
        ['nev' => 'Prioritás',                   'id' => 'eszkoz_tabla_prior'           ],
        ['nev' => 'Szálloda',                    'id' => 'eszkoz_tabla_szalloda'        ],
        ['nev' => 'Termék név',                  'id' => 'eszkoz_tabla_nev'             ],
        ['nev' => 'Cikkszám',                    'id' => 'eszkoz_tabla_csz'             ],
        ['nev' => 'Specifikáció',                'id' => 'eszkoz_tabla_spec'            ],
        ['nev' => 'Igénylés indoka/koncepció',   'id' => 'eszkoz_tabla_igeny_indok'     ],
        ['nev' => 'Kiegészítés megrendelőre',    'id' => 'eszkoz_tabla_megj'            ],
        ['nev' => 'Szállító',                    'id' => 'eszkoz_tabla_szallito'        ],
        ['nev' => 'Szállítási határidő',         'id' => 'eszkoz_tabla_'                ],
        ['nev' => 'Fizetési mód',                'id' => 'eszkoz_tabla_fiz_mod'         ],
        ['nev' => 'Fizetési határidő',           'id' => 'eszkoz_tabla_hat_ido'         ],
        ['nev' => 'Igényelt mennyiség',          'id' => 'eszkoz_tabla_igmen'           ],
        ['nev' => 'Nettó egységár',              'id' => 'eszkoz_tabla_netto'           ],
        ['nev' => 'Pénznem',                     'id' => 'eszkoz_tabla_kcs_21'          ],
        ['nev' => 'Árfolyam',                    'id' => 'eszkoz_tabla_arfolyam'        ],
        ['nev' => 'Deviza egységár',             'id' => 'eszkoz_tabla_dev_net'         ],
        ['nev' => 'Nettó összesen',              'id' => 'eszkoz_tabla_ossz_netto'      ],
        ['nev' => 'Jóváhagyott mennyiség',       'id' => 'eszkoz_tabla_jhmen'           ],
        ['nev' => 'Jóváhagyott nettó egységár',  'id' => 'eszkoz_tabla_jhar'            ],
        ['nev' => 'Jóváhagyott nettó összesen',  'id' => 'eszkoz_tabla_jhossz'          ],
        ['nev' => 'Kiszállítási költség',        'id' => 'eszkoz_tabla_kiszall'         ],
        ['nev' => 'Beüzemelési költség',         'id' => 'eszkoz_tabla_beuzemel'        ],
        ['nev' => 'Munkadíj',                    'id' => 'eszkoz_tabla_munkadij'        ],
        ['nev' => 'Egyéb költség',               'id' => 'eszkoz_tabla_egyeb_koltseg'   ],
        ['nev' => 'Rendelés szám',               'id' => 'eszkoz_tabla_rend_szam'       ],
        ['nev' => 'Műszaki előkészítést igényel','id' => 'eszkoz_tabla_musz_el_ig'      ],
        ['nev' => 'Kivitelezési költség',        'id' => 'eszkoz_tabla_kiv_kolts'       ],
        ['nev' => 'Szálloda igazgató véleménye', 'id' => 'eszkoz_tabla_szall_ig_vel'    ],
        ['nev' => 'BI véleménye',                'id' => 'eszkoz_tabla_bi_vel'          ],
        ['nev' => 'Szakmai vezetés vélemény',    'id' => 'eszkoz_tabla_szak_vel'        ],
        ['nev' => 'Felsővezetés vélemény',       'id' => 'eszkoz_tabla_felso_vel'       ],
    ];

    $sorrend = getErtek('tabla_sorrend', 'login', 'recid', $_SESSION[get_db_tabla().'login_recid']);
    $oszlopok_sorrend = array();
    if( $sorrend ) {
        $sorrend = json_decode(htmlspecialchars_decode($sorrend), true);
        if( $sorrend['eszkozigenyles'] ) {
            foreach ( $sorrend['eszkozigenyles'] as $key => $value ) {
                foreach ( $oszlopok as $key => $val ) {
                    if( $val['id'] === $value['id']) {

                        if( $value['megjelenik'] ) {
                            $val['megjelenik'] = 1;
                        }
                        if( $value['egysoros'] ) {
                            $val['egysoros'] = 1;
                        }

                        // Még véletlen se szerepelhessen egy oszlop benne duplán
                        $mar_benne_van = 0;
                        foreach ( $oszlopok_sorrend as $oszl_s ) {
                            if( $oszl_s['id'] == $val['id'] ) {
                                $mar_benne_van = 1;
                            }
                        }
                        if( !$mar_benne_van ) {
                            $oszlopok_sorrend[] = $val;
                        }
                    }
                }
            }
        }
    }
    
    // Ha volna olyan oszlop az $oszlopok tömbben, ami nincs benne az $oszlopok_sorrend-ben (mert mondjuk egy új oszlop hozzá lett adva), akkor vegyük fel
    foreach ( $oszlopok as $key => $val ) {
        $benne_van = 0;
        foreach ($oszlopok_sorrend as $key2 => $val2) {
            if( $val2['id'] == $val['id'] ) {
                $benne_van = 1;
                break;
            }
        }
        if( !$benne_van ) {
            $oszlopok_sorrend[] = $val;
        }
    }

    $html = nl2('
        
            <div class="dt" style="margin-left: 8.5rem">
                <div class="dtr">
                    <div class="dtc form_bal"></div>
                    <div class="dtc form_jobb r" style="padding-right: 1.3rem">
                        <b>Megjelenik</b>
                    </div>
                    <div class="dtc form_jobb">
                        <b>Egysoros</b>
                    </div>
                </div>
            </div>
            <div class="oszlopok_max_h">
                <div class="dt">
        ');
        $tabindex = 1;
        foreach ($oszlopok_sorrend as $oszlop) {

            $checked_megjelenik = '';
            if ($oszlop['megjelenik']) {
                $checked_megjelenik = ' checked';
            }
            $checked_egysoros = '';
            if ($oszlop['egysoros']) {
                $checked_egysoros = ' checked';
            }

            $html .= nl2('
                <div class="dtr">
                    <div class="dtc form_bal">
                        <span>'.$oszlop['nev'].':</span>
                    </div>
                    <div class="dtc form_jobb">
                        <div class="cb">
                            <input type="checkbox" class="check check_megjelenik"'.$checked_megjelenik.' name="megjelenik_'.$oszlop['id'].'" id="megjelenik_'.$oszlop['id'].'" value="1" onclick="if( $(this).prop(\'checked\') == false ){$(\'#megjelenik_'.$oszlop['id'].'\').prop(\'checked\', false);}" tabindex="'.$tabindex.'">
                            <label for="megjelenik_'.$oszlop['id'].'"></label>
                        </div>
                    </div>
                    <div class="dtc form_jobb">
                        <div class="cb">
                            <input type="checkbox" class="check check_egysoros"'.$checked_egysoros.' name="egysoros_'.$oszlop['id'].'" id="egysoros_'.$oszlop['id'].'" value="1" onclick="if( $(this).prop(\'checked\') == false ){$(\'#egysoros_'.$oszlop['id'].'\').prop(\'checked\', false);}" tabindex="'.$tabindex.'">
                            <label for="egysoros_'.$oszlop['id'].'"></label>
                        </div>
                    </div>
                </div>
            ');
            $tabindex++;
        }
        $html .= nl2('
            </div>
        </div>
    ');

    echo json_encode( array(
                        'html' => $html
                        ) );

    $weblap -> leallit();
    exit;
}


// Jóváahgyott mennyiség módosítása
if (isset($_POST['jhmen_mod'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $eszkozig = $adatbazis_kapcsolat -> s2('stat_id jhar', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $eszkozig === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( 
        // Tagadjuk azt, hogy adott jog + státusz stimmel-e
        !(
            ( _jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG ) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_SZALL_ENG_VAR )  ||
            ( _jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS  ) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_BI_VALID_VAR  )  ||
            ( _jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL   ) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_SZAKM_VEL_VAR )  ||
            ( _jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH   ) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR  )  ||
            ( _jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT ) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR   )
        )
    ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga jóváhagyott mennyiséget módosítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Jóváhagyott mennyiség eltárolása
    // A deviza összesen mezőt (ha devizás az igény) és a jóváhagyott nettó összesen mezőt frissíteni kell
    $jhossz     = $_POST['jhmen']*$eszkozig['jhar'];
    $mezok      = array('jhmen',            'jhossz');
    $ertekek    = array($_POST['jhmen'],    $jhossz );
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    if( addMegj('eszkozigenyles', $_POST['id'], 'Jóváhagyott mennyiség módosítva: '.number_format($_POST['jhmen'], 0, ',', ' ').' db') === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'jhmen'     => number_format($_POST['jhmen'], 0, ',', ' ').' db',
                                'jhmen_db'  => $_POST['jhmen'],
                                'jhossz'    => number_format($jhossz,   0, ',', ' '),
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Műszakilag nem érintett lekezelése
if (isset($_POST['muszakilag_nem_erintett'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga véleményezni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_MUSZAK_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet műszakilag véleményezni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts', 'musz_indok'),
                                                        array($velemeny,    0,           ''          ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_SZALLODAI_ENG_ELO, 'eszkozigenyles', 'eszkozigenyles_szallodai_eng_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_MUSZAK_JH_VAR);
    }

    // Pipa kiszámítása
    $pipa = '';
    $szall_ig_pipak = musz_el_ig_ertekek();
    foreach ($szall_ig_pipak as $szall_ig_pipa) {
        if( $szall_ig_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_pipa['ikon'];
            break;
        }
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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


// Műszaki előkészítést igényel
if (isset($_POST['musz_elokeszitest_igenyel'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok']     = trim($_POST['indok']);
    $_POST['kiv_kolts'] = str_replace(' ', '', $_POST['kiv_kolts']);

    if( $_POST['becsult'] == 'true' ) {
        $_POST['becsult'] = 1;
    } else {
        $_POST['becsult'] = 0;
    }

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga véleményezni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_MUSZAK_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet műszakilag véleményezni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva a kivitelezési költség, akkor hibaüzenet
    if( !$_POST['kiv_kolts'] || $_POST['kiv_kolts'] == '0' ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg a kivitelezési költséget!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts',         'becsult',         'musz_indok'    ),
                                                        array($velemeny,    $_POST['kiv_kolts'], $_POST['becsult'], $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_SZALLODAI_ENG_ELO, 'eszkozigenyles', 'eszkozigenyles_szallodai_eng_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_MUSZAK_JH_VAR);
    }

    // Pipa kiszámítása
    $pipa = '';
    $szall_ig_pipak = musz_el_ig_ertekek();
    foreach ($szall_ig_pipak as $szall_ig_pipa) {
        if( $szall_ig_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_pipa['ikon'];
            break;
        }
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'kiv_kolts' => number_format($_POST['kiv_kolts'], 0, '', ' '),
                                'becsult'   => $_POST['becsult'],
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// Műszaki visszavon
if (isset($_POST['muszaki_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga műszaki igénylést visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_MUSZAK_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet műszakilag véleményezni, sem visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts', 'musz_indok'),
                                                        array($velemeny,    0,           ''          ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiszámítása
    $pipa = '';
    $szall_ig_pipak = musz_el_ig_ertekek();
    foreach ($szall_ig_pipak as $szall_ig_pipa) {
        if( $szall_ig_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Szálloda igazgató jóváhagy
if (isset($_POST['szall_igazgato_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést igazgatóként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést igazgatóként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel'),
                                                        array($velemeny     ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szall_ig_vel_pipak = szall_ig_vel_ertekek();
    foreach ($szall_ig_vel_pipak as $szall_ig_vel_pipa) {
        if( $szall_ig_vel_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_vel_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VALID_VAR ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VALID_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_VALIDALAS_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_validalas_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Szálloda igazgató elutasít
if (isset($_POST['szall_ig_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igazgatóként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést elutasítani!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel',   'szall_ig_indok'    ),
                                                        array($velemeny,        $_POST['indok']     ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szall_ig_pipak = szall_ig_vel_ertekek();
    foreach ($szall_ig_pipak as $szall_ig_pipa) {
        if( $szall_ig_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_szall_ig_elutasit');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// Szálloda igazgató visszavon
if (isset($_POST['szall_igazgato_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést igazgatóként visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést igazgatóként visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel',   'szall_ig_indok'),
                                                        array($velemeny,        ''              ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szall_ig_pipak = szall_ig_vel_ertekek();
    foreach ($szall_ig_pipak as $szall_ig_pipa) {
        if( $szall_ig_pipa['value'] == $velemeny ) {
            $pipa = $szall_ig_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// BI jóváhagy
if (isset($_POST['bi_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus dok_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést BI-ként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VALID_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést BI-ként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel'  ),
                                                        array($velemeny ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_vel_pipak = bi_vel_ertekek();
    foreach ($bi_vel_pipak as $bi_vel_pipa) {
        if( $bi_vel_pipa['value'] == $velemeny ) {
            $pipa = $bi_vel_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] == BESZ_TIP_SORONKIVULI ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_SZAKM_VEL_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_SZAKMAI_VEL_ELO, 'eszkozigenyles', 'eszkozigenyles_szakmai_vel_elo');

    // GM keret esetén Kiküldésre vár státuszba lépünk, azaz kimaradt egy adag státuszozás
    } else if( $sor['tipus'] == BESZ_TIP_GM_KERET) {

        // Megrendelés készítése (fontos, hogy még státuszléptetés előtt, mert a megrendelésnél van státusz ellenőrzés)
        if( megrendeles_keszit($_POST['id']) === false ) {
            $db_error = true;
        }

        // Státusz léptetés
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_KIKULD_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_MEGR_KIKULD_ELO, 'eszkozigenyles', 'eszkozigenyles_megr_kikuld_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'dok_db'    => $sor['dok_db'],
                                ) );
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

// BI elutasít (BI validálásra vár)
if (isset($_POST['bi_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga BI-ként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VALID_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet BI-ként elutasítani!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel',  'bi_indok'      ),
                                                        array($velemeny, $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_pipak = bi_vel_ertekek();
    foreach ($bi_pipak as $bi_pipa) {
        if( $bi_pipa['value'] == $velemeny ) {
            $pipa = $bi_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_bi_elutasit');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// BI visszavon
if (isset($_POST['bi_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga BI-ként visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VALID_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést BI-ként visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel',   'bi_indok'),
                                                        array(0,          ''        ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_pipak = bi_vel_ertekek();
    foreach ($bi_pipak as $bi_pipa) {
        if( $bi_pipa['value'] == $velemeny ) {
            $pipa = $bi_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Szakmai vezetés beleegyezik
if (isset($_POST['szak_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést szakmai vezetésként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZAKM_VEL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést szakmai vezetésként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel'),
                                                        array($velemeny ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szak_pipak = szak_vel_ertekek();
    foreach ($szak_pipak as $szak_pipa) {
        if( $szak_pipa['value'] == $velemeny ) {
            $pipa = $szak_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Szakmai vezetés részben beleegyezik
if (isset($_POST['szak_felt_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést szakmai vezetésként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZAKM_VEL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést szakmai vezetésként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 3;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'    ),
                                                        array($velemeny,    $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szak_pipak = szak_vel_ertekek();
    foreach ($szak_pipak as $szak_pipa) {
        if( $szak_pipa['value'] == $velemeny ) {
            $pipa = $szak_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Szakmai vezetés elutasít
if (isset($_POST['szak_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga szakmai vezetésként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZAKM_VEL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet szakmai vezetésként elutasítani!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'    ),
                                                        array($velemeny,    $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szak_pipak = szak_vel_ertekek();
    foreach ($szak_pipak as $szak_pipa) {
        if( $szak_pipa['value'] == $velemeny ) {
            $pipa = $szak_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        // Itt mégsem kell, mert innen tovább megy felsővezetés elé
        // $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_szak_elutasit');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// Szakmai vezetés visszavon
if (isset($_POST['szak_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga szakmai vezetésként visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZAKM_VEL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést szakmai vezetésként visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'),
                                                        array($velemeny,    ''          ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $szak_pipak = szak_vel_ertekek();
    foreach ($szak_pipak as $szak_pipa) {
        if( $szak_pipa['value'] == $velemeny ) {
            $pipa = $szak_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Felsővezetés beleegyezik
if (isset($_POST['felso_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést felsővezetőként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet felsővezetőként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel'),
                                                        array($velemeny  ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $felso_pipak = felso_vel_ertekek();
    foreach ($felso_pipak as $felso_pipa) {
        if( $felso_pipa['value'] == $velemeny ) {
            $pipa = $felso_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli vagy GM terhes
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {

        // Megrendelés készítése (fontos, hogy még státuszléptetés előtt, mert a megrendelésnél van státusz ellenőrzés)
        if( megrendeles_keszit($_POST['id']) === false ) {
            $db_error = true;
        }

        // Státuszléptetés
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
            $db_error = true;
        }

        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_KIKULD_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_MEGR_KIKULD_ELO, 'eszkozigenyles', 'eszkozigenyles_megr_kikuld_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db dok_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'dok_db'    => $megj['dok_db'],
                                ) );
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

// Felsővezetés részben beleegyezik
if (isset($_POST['felso_felt_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést felsővezetőként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést felsővezetőként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 3;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'   ),
                                                        array($velemeny,    $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $felso_pipak = felso_vel_ertekek();
    foreach ($felso_pipak as $felso_pipa) {
        if( $felso_pipa['value'] == $velemeny ) {
            $pipa = $felso_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VEGL_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_VEGLEGESIT_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_veglegesit_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Felsővezetés elutasít
if (isset($_POST['felso_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga felsővezetőként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet felsővezetőként elutasítani!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'   ),
                                                        array($velemeny,    $_POST['indok'] ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $felso_pipak = felso_vel_ertekek();
    foreach ($felso_pipak as $felso_pipa) {
        if( $felso_pipa['value'] == $velemeny ) {
            $pipa = $felso_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_felso_elutasit');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// Felsővezetés visszavon
if (isset($_POST['felso_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga felsővezetőként visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet felsővezetőként visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'),
                                                        array($velemeny,    ''           ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $felso_pipak = felso_vel_ertekek();
    foreach ($felso_pipak as $felso_pipa) {
        if( $felso_pipa['value'] == $velemeny ) {
            $pipa = $felso_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// BI véglegesít beleegyezik
if (isset($_POST['bi_vegl_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus dok_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést BI véglegesítőként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VEGL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet BI véglegesítőként jóváhagyni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl'  ),
                                                        array($velemeny  ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_vegl_pipak = bi_vegl_ertekek();
    foreach ($bi_vegl_pipak as $bi_vegl_pipa) {
        if( $bi_vegl_pipa['value'] == $velemeny ) {
            $pipa = $bi_vegl_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha nem tervezett (azaz soron kívüli vagy GM terhes)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {

        // Megrendelés készítése (fontos, hogy még státuszléptetés előtt, mert a megrendelésnél van státusz ellenőrzés)
        if( megrendeles_keszit($_POST['id']) === false ) {
            $db_error = true;
        }

        // Státusz léptetés
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_KIKULD_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_MEGR_KIKULD_ELO, 'eszkozigenyles', 'eszkozigenyles_megr_kikuld_elo');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);
    }

    // Megjegyzések számát frissen kérjük le, ha volt értesítés, akkor nőhetett a száma
    $megj = $adatbazis_kapcsolat -> s2('megj_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $megj === false ) { $db_error = true; }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'megj_db'   => $megj['megj_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'dok_db'    => $sor['dok_db'],
                                ) );
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

// BI véglegesítés elutasít
if (isset($_POST['bi_vegl_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga BI véglegesítőként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VEGL_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet BI véglegesítőként elutasítani!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nem volna megadva az indoklás, akkor hibaüzenet
    if( !$_POST['indok'] ) {
        echo json_encode(array(
                                'hiba'  => 'Kérem adja meg az indoklást!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl',  'bi_vegl_indok'   ),
                                                        array($velemeny,  $_POST['indok']   ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_vegl_pipak = bi_vegl_ertekek();
    foreach ($bi_vegl_pipak as $bi_vegl_pipa) {
        if( $bi_vegl_pipa['value'] == $velemeny ) {
            $pipa = $bi_vegl_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli (azaz nem tervezett)
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_bi_vegl_elutasit');

    // Tervezett esetén marad a státusz, csak a pipa változik
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                ) );
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

// BI véglegesítés visszavon
if (isset($_POST['bi_vegl_visszavon'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga BI véglegesítőként visszavonni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VEGL_VAR) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet BI véglegesítőként visszavonni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 0;
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl',   'bi_vegl_indok'),
                                                        array($velemeny,   ''             ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_vegl_pipak = bi_vegl_ertekek();
    foreach ($bi_vegl_pipak as $bi_vegl_pipa) {
        if( $bi_vegl_pipa['value'] == $velemeny ) {
            $pipa = $bi_vegl_pipa['ikon'];
            break;
        }
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                ) );
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

// Megjegyzések kiíratása
if (isset($_POST['megj_kiir'])) {

    echo json_encode( array('html' => megj_kiir($megnevezes, $_POST['id'], $_POST['rendszer'])) );

    $weblap -> leallit();
    exit;
}

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once('selectek.php');

    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {
        
        $sql = 'SELECT
				`stat_id` as \'Státusz\',
				`azon` as \'Azonosító\',
				`idoszak_azon` as \'Időszak ID\',
				`tipus` as \'Típus\',
				`prior` as \'Prioritás\',
				`telephely_id` as \'Szálloda\',
				`'.get_db_tabla().'eszkozigenyles`.`nev` as \'Termék név\',
				`csz` as \'Cikkszám\',
				`spec` as \'Specifikáció\',
				`igeny_indok` as \'Igénylés indoka/koncepció\',
				`megj` as \'Megjegyzés\',
				`szallito_id` as \'Szállító\',
				`szall_hat` as \'Szállítási határidő:\',
				`fiz_mod` as \'Fizetési mód\',
				`hat_ido` as \'Határidő\',
				`igmen` as \'Igényelt mennyiség\',
				`netto` as \'Nettó egységár\',
				`kcs_21` as \'Pénznem\',
				`arfolyam` as \'Árfolyam\',
				`dev_net` as \'Deviza egységár\',
				`ossz_netto` as \'Nettó összesen\',
				`jhmen` as \'Jóváhagyott mennyiség\',
				`jhar` as \'Jóváhagyott nettó egységár\',
				`jhossz` as \'Jóváhagyott nettó összesen\',
				`kiszall` as \'Kiszállítási költség\',
				`beuzemel` as \'Beüzemelési költség\',
				`munkadij` as \'Munkadíj\',
				`egyeb_koltseg` as \'Egyéb költség\',
				`rend_szam` as \'Rendelés szám\',
				`musz_el_ig` as \'Műszaki előkészítést igényel\',
				`kiv_kolts` as \'Kivitelezési költség\',
				`szall_ig_vel` as \'Szálloda igazgató véleménye\',
				`bi_vel` as \'BI véleménye\',
				`szak_vel` as \'Szakmai vezetés vélemény\',
				`felso_vel` as \'Felsővezetés vélemény\'
                '. csv_export_get_from_tol($megnevezes);
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

        $tort_szamok = ['Árfolyam', 'Deviza egységár', ''];
        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {  
                
                if( in_array($kulcs, $tort_szamok) ) {
                    $sor = str_replace('.', ',', $sor);
                }
                
                if( $kulcs == 'Státusz' ) {
                    $sor = getStatText('munkalap', $sor);
                }

                if( $kulcs == 'Típus' ) {
                    foreach ($select_besz_tipus as $tipus) {
                        if( $tipus['value'] == $sor ) {
                            $sor = $tipus['name'];
                            break;
                        }
                    }
                    if( !$sor ) {
                        $sor = '';
                    }
                }

                if( $kulcs == 'Prioritás' ) {
                    foreach ($select_eszkoz_prior as $prior) {
                        if( $prior['value'] == $sor ) {
                            $sor = $prior['name'];
                            break;
                        }
                    }
                    if( !$sor ) {
                        $sor = '';
                    }
                }

                if( $kulcs == 'Fizetési mód' ) {
                    foreach ($select_fiz_mod as $fiz_mod) {
                        if( $fiz_mod['value'] == $sor ) {
                            $sor = $fiz_mod['name'];
                            break;
                        }
                    }
                    if( !$sor ) {
                        $sor = '';
                    }
                }
                
                if( $kulcs == 'Szálloda' ) {
                    $sor = getSzallodaNev($sor);
                }
                
                if( $kulcs == 'Szállító' ) {
                    $sor = getErtek('nev', 'szallito_beszerzes', 'recid', $sor);
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

        // Progress
        echo json_encode(array('pr' => '100')).PHP_EOL;

        // Felszabadítás
        ob_flush();
        flush();
    }

    csv_export_lezar($fh, $filename, 'Eszközigénylések');
}



// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
// require_once-al nem működött...
require('selectek.php');

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
if( $sor['dok_db'] ) {
    $dok_db  = ' ('.$sor['dok_db'].')';
}
if( $sor['megj_db'] ) {
    $megj_db = ' ('.$sor['megj_db'].')';
}

if( !isset($_GET['uj']) && ( isset($_GET['tablazat']) || isset($_GET['modosit']) || isset($_GET['jovahagyasok']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) ) {
    // Számla fülek
    $ful_tomb = array(
        'tablazat'      => 'Eszközigénylés',
        'modosit'       => 'Adatlap',
        'jovahagyasok'  => 'Jóváhagyások',
        'tortenet'      => 'Történet',
        'dokumentumok'  => 'Dokumentumok'.$dok_db,
        'megjegyzesek'  => 'Megjegyzések'.$megj_db,
    );

    $kivetelek = array('ajax', 'uj');
    foreach ( $ful_tomb as $key => $value ) {
        array_push($kivetelek, $key);
    }

    $fulek = '';
    foreach ( $ful_tomb as $key => $value ) {

        if( $key=='tablazat'  ) {
            $value = 'Eszközigénylés';
        }

        $active = 'active';
        if( isset($_GET[$key]) ) {
            $active = ' class="active"';
        }

        $id_betolteshez = $_GET['id'];
        if (!$_GET['id']) {
            $id_betolteshez = $_SESSION['legutobbi_id'];
        }

        if( $key == 'tablazat' ) {
            $fulek .= '<a href="'.self().get_get($kivetelek).'&amp;tablazat"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        } else {
            $fulek .= '<a href="javascript: szamlaBetolt(_get(\'id\'), \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

    }

    $szamla_html .= str_replace(
        array('{FULEK}'),
        array( $fulek  ),
        fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
    );
}

// Űrlap mentésekor ellenőrzünk pár dolgot (nem csak új felvitelben, módosításkor is)
// Mivel a Mentés gombra kattintva fut le, ezért támaszkodhatunk arra, hogy az adatlap fülön vagyunk, tehát JS-e elérhetőek a mezők.
// GM terhes esetén nem lehet magasabb a Nettó egységár mint 50k és az össz nettó sem lehet több 500k
$js_submit_elott = nl2('

                        if( $(\'#tipus\').val() == 3 ) {
                            if( $(\'#netto\').val().replace(/ /g, \'\') > '.GM_TERHES_EGYSEGAR.' ) {
                                alert2(\'A Nettó egységár mező értéke nem lehet több mint '.number_format(GM_TERHES_EGYSEGAR, 0, '', ' ').' '.get_penznem().'!\', \'\', \'Hiba\');
                                if( $(\'#kcs_21_autocomp\').val() == \'HUF\' ) {
                                    $(\'#netto\').addClass(\'hibas_ide_focus\');
                                } else {
                                    $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                                }
                                return false;
                            }
                            if( $(\'#ossz_netto\').val().replace(/ /g, \'\') > '.GM_TERHES_OSSZNETTO.' ) {
                                alert2(\'A Nettó összesen mező értéke nem lehet több mint '.number_format(GM_TERHES_OSSZNETTO, 0, '', ' ').' '.get_penznem().'!\', \'\', \'Hiba\');
                                if( $(\'#kcs_21_autocomp\').val() == \'HUF\' ) {
                                    $(\'#netto\').addClass(\'hibas_ide_focus\');
                                } else {
                                    $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                                }
                                return false;
                            }
                        }

                        if( $(\'#szallito_id\').val() ) {
                            if( !$(\'#kcs_21\').val().trim() ) {
                                alert2(\'Kérem adja meg a pénznemet!\');
                                $(\'#kcs_21\').addClass(\'hibas_ide_focus\');
                                return false;
                            }
                            if( !$(\'#igmen\').val().trim() || $(\'#igmen\').val()==\'0\' ) {
                                alert2(\'Kérem adja meg az igényelt mennyiséget!\');
                                $(\'#igmen\').addClass(\'hibas_ide_focus\');
                                return false;
                            }
                        }

                        if( $(\'#kcs_21\').val() == '.KCS_21_HUF.' ) {
                            if( !$(\'#netto\').val().trim() || $(\'#netto\').val()==\'0\' ) {
                                alert2(\'Kérem adja meg a nettó egységárat!\');
                                $(\'#netto\').addClass(\'hibas_ide_focus\');
                                return false;
                            }
                        
                        } else if( $(\'#kcs_21\').val() ) {
                            if( !$(\'#arfolyam\').val().trim() || $(\'#arfolyam\').val()==\'0,00000\' ) {
                                alert2(\'Kérem adja meg az árfolyamot!\');
                                $(\'#arfolyam\').addClass(\'hibas_ide_focus\');
                                return false;
                            }
                            if( !$(\'#dev_net\').val().trim() || $(\'#dev_net\').val()==\'0,00\' ) {
                                alert2(\'Kérem adja meg a deviza egységárat!\');
                                $(\'#dev_net\').addClass(\'hibas_ide_focus\');
                                return false;
                            }
                        }

                        if( !$(\'#szallito_id\').val() && !$(\'#szallito_igenyelve\').val() ) {
                            if( _get(\'uj\') ) {
                                if( !form_ell_admin() ) {
                                    return false;
                                }
                            } else {
                                if( !form_ell_admin(\'-mentes_nelkul-\') ) {
                                    return false;
                                }
                            }

                            csoportMuvelet(\'eszkozigenyles_szallito_igenyel\');
                            return false;
                        } else {
                            return form_ell_admin();
                        }

                        ');

// Gombok kirajzolása minden oldalra
$gombok = '';
if( isset($_GET['uj']) ) {

    // Kinek lesz ehhez joga?
    // Ez most egyelőre szándékosan rejtve van
    if( JOG_BESZERZ_IGENYLESEK_OLVAS ) {
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="mentesGomb();">
                <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
            </div>
        ');

        // Műveletek box
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

// Táblázat nézetben levő gombok
} else if(isset($_GET['tablazat'])) {

    $gombok .= get_eszkozigenyles_gombok($_GET['id']);

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
    $gombok .= get_eszkozigenyles_gombok($_GET['id']);
    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].')" value="Töröl" title="Töröl">';

    $gombok = nl2('
        <div class="form_kuldes">
            <input type="button" class="gombb mentes_gomb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés" onclick="if( form_ell_admin_elott() ) { mentesGomb(); }">
            <input type="button" class="gomb piros" disabled id="megsem_gomb" value="Mégsem" onclick="megsemGomb();">
        </div>
    ').$gombok;

    // Műveletek box
    $gombok = nl2('
        <div class="muveletek megis_mindig_latszik"'.$muv_style.'>
            '.$gombok.'
        </div>
    ');
}

// Új eszközigénylés felvitele
if( isset($_GET['uj']) ) {

    // Újként szerkesztéskor feltöltjük a mezők tartalmát
    if( $_GET['ujkent'] ) {
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['ujkent']}'");
        if( $sor ) {
            foreach ( $akt_tomb as $ertek ) {
                $_SESSION[$ertek['name']] = stripslashes( $sor[$ertek['name']] );
            }
        }

        foreach ( $akt_tomb as $ertek ) {
            $sor[$ertek['name']] = $_SESSION[$ertek['name']];
        }
    }
    $js = js_kiir($akt_tomb, 'form_ell_admin()');

    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);

    // Javascript-es ellenőrzéshez
    $tabla = str_replace('.php', '', basename(self()));
    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type'] == 'autocomplete' ) {
            $sor[$tomb['name'].'_autocomp'] = '';
        }
    }

    $json_sor = array();
    foreach ( $sor as $kulcs => $elem ) {
        $json_sor[$kulcs] = formAdatok_replace($elem);
    }
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
    $szamla_html .= '<input type="hidden" id="szallito_igenyelve">';

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

// Táblázat
} elseif( isset($_GET['tablazat']) ) {

    // A fülek CSS-ét itt felülírjuk, illetve a táblázat rendezőt és egy ide vonatkozós JS-t is meghívunk
    $szamla_html .= nl2('
            <style type="text/css">
                div.szamlaFulek{
                    margin-left: 0;
                }
            </style>
            <script src="../js/sorttable.js"></script>
            <script src="../js/eszkozigenyles_tablazat.js"></script>
            <script>$(document).ready(function () { fejlecPozicional(); });</script>
    ');

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

// Rekord módosítása
} elseif( isset($_GET['modosit']) ) {

    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if( isset($_GET['id_hidden']) ) {
        $_GET['id'] = $_GET['id_hidden'];
    }

    $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");

    // Az időszak ID formátumát majd itt állítjuk elő, ilyesmi alaka lesz 26/1
    $sor['beszerzes_idoszak_recid'] = '';

    if( $sor['kcs_21'] == KCS_21_HUF ) {
        $sor['kiszall']         = (int)$sor['kiszall' ];
        $sor['beuzemel']        = (int)$sor['beuzemel'];
        $sor['munkadij']        = (int)$sor['munkadij'];
        $sor['egyeb_koltseg']   = (int)$sor['egyeb_koltseg'];
    }

    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type'] == 'autocomplete' ) {
            
            if( $tomb['csoport'] ) {
                $kulso_ertek = $adatbazis_kapcsolat->s2('info', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}' AND `csoport`='{$tomb['csoport']}'", '', '1');
                $kulso_ertek['nev'] = $kulso_ertek['info'];
            } else {
                $kulso_ertek = $adatbazis_kapcsolat->s2('recid nev', $tomb['tabla'], "`{$tomb['oszlop_keres']}`='{$sor[$tomb['name']]}'", '', '1');
            }

            // Részlegnél kell a teljes útvonal
            if( $tomb['name'] == 'te_alcsop' ) {
                $reszleg = $adatbazis_kapcsolat -> s2('szulo nev', 'te_reszleg', "`recid`='{$sor['te_alcsop']}'");
                $sor['te_alcsop_autocomp'] = $reszleg['nev'];

                $szulo = $adatbazis_kapcsolat -> s2('szulo nev', 'te_reszleg', "`recid`='{$reszleg['szulo']}'");
                $sor['te_alcsop_autocomp'] .= ' / '.$szulo['nev'];

                $szulo = $adatbazis_kapcsolat -> s2('szulo nev', 'te_reszleg', "`recid`='{$szulo['szulo']}'");
                $sor['te_alcsop_autocomp'] .= ' / '.$szulo['nev'];

            // Ha nem te_alcsop, akkor jöhet az általános autocomplete
            } else {
                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek['nev'];
                }
            }

        }
    }


    $json_sor = array();
    foreach ( $sor as $kulcs => $elem ) {
        $json_sor[$kulcs] = formAdatok_replace($elem);
    }
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
    $szamla_html .= '<input type="hidden" id="szallito_igenyelve">';
    $szamla_html .= nl2('
        <script>
            $(\'#tartalomJobb\').scrollTop(0);
            duplikalGomb  = "<p class=\"c\"><input class=\"gomb zold\" type=\"button\" value=\"Igen\" onclick=\"szurkit(); location.href = \''.basename(self()).'?uj&amp;ujkent='.$_GET['id'].'\'; \"><input class=\"gomb piros\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
            duplikaKerdes = \'Szálloda: '.str_replace('\'', '', getSzallodaNev($sor['telephely_id'])).'<br>Szállító: '.str_replace('\'', '', getErtek('nev', 'szallito_beszerzes', 'recid', $sor['szallito_id'])).'<br><br>Biztos, hogy duplikálja?\';

            /* Ha ismert a szállító, pár mező kötelezővé válik */
            $(document).ready(function(){
                szallito_kotelezo_mezok();
                nettokat_atszamol();
            });
        </script>
    ');
    
    // Form
    $js = js_kiir($akt_tomb, 'form_ell_admin(mentes_nelkul = false)', 'ajax');

    // Form actionja
    $action = self().get_get(array('errnum','kephiba','siker'));

    // Ez elkészíti a $mezok változót
    require('switch.php');

    $szamla_html .= str_replace(
        array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}', '{UPDATE_DT}'     ),
        array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok,    $sor['update_dt'] ),
        fajlbol_olvas('../sablon/admin/form_modosit.html', true)
    );

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; width: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

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

// Jóváhagyások - ugyanaz mint a modosit
} elseif( isset($_GET['jovahagyasok']) ) {

    // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
    if( isset($_GET['id_hidden']) ) {
        $_GET['id'] = $_GET['id_hidden'];
    }

    $akt_tomb = $eszkozigenyles_jovahagyasok_tomb;
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
    $szamla_html .= '<script>formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';
    
    // Form
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

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; width: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

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

// Történet
}elseif( isset($_GET['tortenet']) ) {

    $szamla_html .= get_tortenet($megnevezes);

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; width: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Dokumentumok
} elseif( isset($_GET['dokumentumok']) ) {
    
    if( $tobb_kep ) {
        require_once('tobb_kep.php');
    }

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létetéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; width: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom' => $szamla_html.$gombok,
            'link'     => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html.$gombok;
    }

// Megjegyzések
} elseif( isset($_GET['megjegyzesek']) ) {

    // Megjegyzések kiíratása
    $szamla_html .= megj_kiir($megnevezes, $_GET['id']);

    // A léptetés csak akkor fog működni, ha a bal navpanel kódja benne van a kódban
    // FONTOS, hogy nem lehet "dn", mert látszania kell, hogy a JavaScript tudjon vele dolgozni. A létezéshez kell.
    $szamla_html .= '<div class="vh" style="height: 0; width: 0; overflow: hidden;">'.$balNavPanelHTMLje.'</div>';

    if( $_GET['ajax'] ) {
        echo json_encode(array(
            'tartalom'  => $szamla_html.$gombok,
            'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
        ));
    } else {
        echo $szamla_html.$gombok;
    }

}

// Ajaxnál de megjegyzéseknél se írjuk ki a </div>-et. Itt a $balNavPanelHTMLje dolog kavar be, amit egy korábbi </div> után kellene kiírni
if( !$_GET['ajax'] && !isset($_GET['megjegyzesek']) ) {
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

        $sorok = $adatbazis_kapcsolat->s('recid megj user insert_usr insert_dt', 'megjegyzes', "`id`='{$id}' AND `tabla`='{$tabla}'{$sql_kieg}", 'recid desc');
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

    $html .= '</div>';
    
    return $html;
}

// Megrendelés készítése
function megrendeles_keszit($eszkozigenyles_id = '') {

    global $adatbazis_kapcsolat;

    $db_error = false;

    require('selectek.php');

    // Megrendelés létrehozása
    $eszkozigenyles = $adatbazis_kapcsolat -> s2('recid tipus stat_id nev igmen jhmen arfolyam netto jhar jhossz telephely_id szallito_id szall_hat fiz_mod hat_ido ossz_netto ossz_dev kcs_21 kiszall beuzemel munkadij egyeb_koltseg eloleg megj insert_dt', 'eszkozigenyles', "`recid`='{$eszkozigenyles_id}'");
    if( $eszkozigenyles === false ) { $db_error = true; }

    // Státusz és jogok ellenőrzése (nehogy sima link beírással hozzanak létre megrendelést)
    if(
        // BI validálás + validálásra vár státusz + GM terhes típus esetén készül megrendelés
        (_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS ) && $eszkozigenyles['stat_id'] == ESZKOZIGENYL_STAT_BI_VALID_VAR && $eszkozigenyles['tipus'] == BESZ_TIP_GM_KERET ) ||
        // Felsővezetői jóváhagyás + felsővezetői jh vár státuszkor készül megrendelés, ha nem tervezett típus
        (_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) && $eszkozigenyles['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR && $eszkozigenyles['tipus'] != BESZ_TIP_TERVEZETT ) ||
        // BI véglegesít + véglegesítésre vár státuszkor készül megrendelés, ha nem tervezett típus
        (_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) && $eszkozigenyles['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR && $eszkozigenyles['tipus'] != BESZ_TIP_TERVEZETT ) ||
        isset($_GET['pdf_mutat']) || isset($_GET['html_mutat'])
    ) {

        // Ha csak a HTML szerkesztése miatt tesztelünk, akkor ne hozzunk létre megrendelést
        if( !isset($_GET['html_mutat']) && !isset($_GET['pdf_mutat']) ) {

            $mezok   = array();
            $ertekek = array();
            // Ha nem lett volna megadva a jóváhagyott mennyiség, akkor az igényelt mennyiséget másoljuk be oda
            if( !$eszkozigenyles['jhmen'] ) {
                array_push($mezok,   'jhmen'                    );
                array_push($ertekek, $eszkozigenyles['igmen']   );
                $eszkozigenyles['jhmen'] = $eszkozigenyles['igmen'];
            }
            // Jóváhagyott nettó egységár tuti nincs ekkor még kitöltve, hisz ez egy fixen disabled mező. Ebbe a nettó egységár értéke kerül be ebben a pillanatban.
            array_push($mezok,   'jhar'                     );
            array_push($ertekek, $eszkozigenyles['netto']   );
            $eszkozigenyles['jhar'] = $eszkozigenyles['netto'];
            
            // A Jóváhagyott nettó összesen is garantáltan üres ekkor még. Most kell beleírni a jóváhagyott mennyiséget és egységár szorzatát.
            $jhossz = $eszkozigenyles['jhmen']*$eszkozigenyles['jhar'];
            array_push($mezok,   'jhossz'   );
            array_push($ertekek, $jhossz    );
            $eszkozigenyles['jhossz'] = $jhossz;

            $rend_szam = get_iktatoszam_sorszam('eszkozigenyles_megr');
            if($rend_szam===false){ $db_error = true; }
            $rend_szam = 'M/'.date('Y').'/'.$rend_szam;
            $rend_szam_fajlba = str_replace('/', '', $rend_szam);

            $hash = jelszot_keszit();
            $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
            if( $foglalt === false ) { $db_error = true; }
            while($foglalt) {
                $hash = jelszot_keszit();
                $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
                if( $foglalt === false ) { $db_error = true; }
            }

            $szall_mail = getErtek('email', 'szallito_beszerzes', 'recid', $eszkozigenyles['szallito_id']);
            $megr_recid = $adatbazis_kapcsolat -> i('eszkozigenyles_megr', array('hash', 'rend_szam', 'telephely_id',                 'szallito_id',                   'szall_mail', 'szall_hat',                  'ossz_netto',                   'ossz_dev',                     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                           array($hash,  $rend_szam,  $eszkozigenyles['telephely_id'], $eszkozigenyles['szallito_id'], $szall_mail,  $eszkozigenyles['szall_hat'], $eszkozigenyles['ossz_netto'],  $eszkozigenyles['ossz_dev'],    $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                    );
            if( $megr_recid === false ) { $db_error = true; }

            // Induló státusz beállítása
            $sql = $adatbazis_kapcsolat -> i('eszkozigenyles_megr_statusz',
                                                array('eszkozigenyles_megr_recid',  'stat_id',                          'login',                            'insert_dt',    'insert_usr',                             'update_usr'                           ),
                                                array($megr_recid,                  ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR,  $_SESSION[get_db_tabla().'user'],   'NOW()',        $_SESSION[get_db_tabla().'teljes_nev'],   $_SESSION[get_db_tabla().'teljes_nev'] )
                                            );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->u('eszkozigenyles_megr', array('stat_id'), array(ESZKOZIGENYL_MEGR_STAT_KIKULD_VAR), "`recid`='{$megr_recid}'");
            if( $sql === false ) { $db_error = true; }

            // Végül a rendszámot is írjuk be
            array_push($mezok,   'rend_szam');
            array_push($ertekek, $rend_szam );

            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$eszkozigenyles_id}'");
            if( $sql === false ) { $db_error = true; }

            // Később hivatkozunk erre a mezőre, ezért kell itt megadni az értékét
            $eszkozigenyles['rend_szam'] = $rend_szam;
        }

        // Kontakt személy lekérdezése: akinek van "Szállodai beszerző" joga és adott igénylés szállodája a látókörében van
        $kontakt = $adatbazis_kapcsolat -> s2('teljes_nev usertel usermail', 'login', "substr(`flags`, ".(JOG_BESZERZ_IGENYLESEK_BESZERZO+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0 AND `nev` IN (SELECT `nev` FROM `".get_db_tabla()."telephely_kapcs` WHERE `telephely_id`='{$eszkozigenyles['telephely_id']}')");
        if( $kontakt === false ) { $db_error = true; }
        $usertel = '';
        if( $kontakt['usertel'] ) {
            $usertel = ', tel.: '.$kontakt['usertel'];
        }

        $penznem = getErtekLista(ERTEKLISTA_PENZNEM, $eszkozigenyles['kcs_21']);
        $tizedesek  = 0;
        $jhar       = $eszkozigenyles['jhar'];
        $jhossz     = $eszkozigenyles['jhossz'];
        if( $eszkozigenyles['kcs_21']!=KCS_21_HUF ) {
            $tizedesek  = 2;
            $jhar       = $eszkozigenyles['jhar'  ]/$eszkozigenyles['arfolyam'];
            $jhossz     = $eszkozigenyles['jhossz']/$eszkozigenyles['arfolyam'];
        }

        $szallito = $adatbazis_kapcsolat->s2('nev knev tel email', 'szallito_beszerzes', "`recid`='{$eszkozigenyles['szallito_id']}'");
        if( $szallito === false ) { $db_error = true; }

        // Ha nincs kapcsolattartó név, akkor "Partnerünk" a megszólítás
        if( !$szallito['knev'] ) {
            $szallito['knev'] = 'Partnerünk';
        }

        $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='{$eszkozigenyles['telephely_id']}'");
        if( $telephely === false ) { $db_error = true; }
        $props_szn      = get_props($telephely['props'], 'szn'      );
        $props_cim      = get_props($telephely['props'], 'cim'      );
        $props_szallcim = nl2br2(get_props($telephely['props'], 'szallcim' ));

        $fizmod_kiir = '';
        foreach ($select_fiz_mod as $fizmod) {
            if( $fizmod['value'] == $eszkozigenyles['fiz_mod'] ) {
                $fizmod_kiir = $fizmod['name'];
            }
        }

        if( $eszkozigenyles['hat_ido'] ) {
            $eszkozigenyles['hat_ido'] .= ' nap';
        } else {
            $eszkozigenyles['hat_ido'] = '';
        }
    
        // A Fizetési mód és határidő sor csak akkor jelenik meg, ha van legalábbi egyik adat
        $fiz_mod_hatarido = '';
        $eloleg = '';
        if( $eszkozigenyles['eloleg'] ) {
            $eloleg .= '&nbsp;&nbsp;&nbsp;Előleg: '.$eszkozigenyles['eloleg'].'%';
        }
        if( $fizmod_kiir || $eszkozigenyles['hat_ido'] ) {
            $fiz_mod_hatarido .= nl2('
                <tr>
                    <td class="b" style="padding: 20px 20px 0 0">
                        Fizetési mód és határidő:
                    </td>
                    <td style="vertical-align: top; padding: 20px 0 0 0">
                        '.$fizmod_kiir.'
                        '.$eszkozigenyles['hat_ido'].'
                        '.$eloleg.'
                    </td>
                </tr>
            ');
        }

        // Lényegi tartalom
        $fejlec = get_nyomtatas_fejlec_megr($eszkozigenyles, $szallito, $kontakt);
        if( $fejlec === false ) {
            $db_error = true;
        }

        $mindossz = $eszkozigenyles['ossz_netto'];
        $tetel_neve  = $eszkozigenyles['nev'];
        $jhossz_kiir = number_format($jhossz, $tizedesek, ',', ' ');
        if( $eszkozigenyles['kiszall'] > 0 ) {
            $tetel_neve  .= '<br>Kiszállítási költség';
            $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['kiszall'], $tizedesek, ',', ' ');
            $mindossz += $eszkozigenyles['kiszall'];
        }
        if( $eszkozigenyles['beuzemel'] > 0 ) {
            $tetel_neve  .= '<br>Beüzemelési költség';
            $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['beuzemel'], $tizedesek, ',', ' ');
            $mindossz += $eszkozigenyles['beuzemel'];
        }
        if( $eszkozigenyles['munkadij'] > 0 ) {
            $tetel_neve  .= '<br>Munkadíj';
            $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['munkadij'], $tizedesek, ',', ' ');
            $mindossz += $eszkozigenyles['munkadij'];
        }
        if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
            $tetel_neve  .= '<br>Egyéb költség';
            $jhossz_kiir .= '<br>'.number_format($eszkozigenyles['egyeb_koltseg'], $tizedesek, ',', ' ');
            $mindossz += $eszkozigenyles['egyeb_koltseg'];
        }
        if( $eszkozigenyles['megj'] ) {
            $tetel_neve .= '<br>'.nl2br2($eszkozigenyles['megj']);
        }


        $tartalom = '
        <div class="padd" style="padding-bottom: 0; margin-bottom: 0">
                '.$fejlec.'
                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    Tisztelt '.$szallito['knev'].'!
                    <br>
                    <br>Megrendeljük Önöktől a <b>Danubius Hotel '.getSzallodaNev($eszkozigenyles['telephely_id']).'</b> részére az alábbi tételeket:
                    <br>
                </div>

                <table class="border full" style="font-size: 12px;border-collapse: collapse; border: 1px solid #000;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: left;">Megnevezés</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right;">Mennyiség<br>(db)</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right;">Nettó egységár<br>('.$penznem.')</th>
                    <th style="border: 1px solid #ddd; padding: 2px; text-align: right;">Nettó összesen<br>('.$penznem.')</th>
                </tr>
                <tr style="border-top: 1px solid #ddd;">
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: left; vertical-align: top;">'.$tetel_neve.'</td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($eszkozigenyles['jhmen' ], 0, '', ' ').'</td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($jhar,   $tizedesek, ',', ' ').'</td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.$jhossz_kiir.'</td>
                </tr>
                <tr style="border-top: 1px solid #ddd;">
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: left; vertical-align: top;"><b>Mindösszesen:</b></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"></td>
                    <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;"><b>'.number_format($mindossz, $tizedesek, ',', ' ').'</b></td>
                </tr>
                </table>

                <table style="font-size: 12px; border-collapse: collapse; margin-bottom: 20px">
                '.$fiz_mod_hatarido.'
                <tr>
                    <td class="b" style="padding: 10px 20px 0 0">
                        Kontaktszemély:
                    </td>
                    <td style="vertical-align: top; padding: 10px 0 0 0">
                        '.$kontakt['teljes_nev'].$usertel.'
                    </td>
                </tr>
                </table>

                <table style="font-size: 12px; border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="padding: 2px; width: 50%;">
                        Számlázási cím
                    </td>
                    <td style="padding: 2px; width: 50%;">
                        Szállítási cím
                    </td>
                </tr>
                </table>
                <table style="font-size: 12px; border-collapse: collapse; margin-bottom: 20px; width: 100%; border: 1px solid #000">
                <tr>
                    <td style="padding: 2px; vertical-align: top; width: 50%; border-right: 1px solid #000">
                        <b>Danubius Hotels Zrt.</b>
                        <br><b>'.$props_szn.'
                        <br>'.$props_cim.'</b>
                        <br><b>Adószáma:</b> 10594702-2-44
                        <br><b>Cégjegyzékszám:</b> 01-10-041669
                    </td>
                    <td style="padding: 2px; width: 50%; vertical-align: top; border: 1px solid #000">
                        <b>'.$props_szallcim.'</b>
                    </td>
                </tr>
                </table>

                <div style="font-size: 12px; margin: 0 0 2px 0; padding-top: 10px">
                    Kérjük megrendelésünket a szállítási határidő feltüntetésével szíveskedjen visszaigazolni.
                    <br>A számlán a megrendelés számát legyenek kedvesek szerepeltetni!
                </div>

                <div style="page-break-inside: avoid;">
                    <div style="font-size: 12px; margin: 30px 0 3px 0;">
                        Üdvözlettel,
                        <br>Budapest, '.date_formaz(date('Y-m-d')).'
                    </div>
                    '.get_nyomtatas_alairasok(0, 0, '', $eszkozigenyles['telephely_id']).'
                </div>
            </div>
        </div>
        ';

        $keret = get_nyomtatas_keret('Megrendelés száma: '.$rend_szam);
        $html  = str_replace('{TARTALOM}', $tartalom, $keret);

$html = nl2($html);
if( isset($_GET['html_mutat']) ) {
    echo $html;
    exit;
}

        // Így tudod kikapcsolni a warningokat és hibaüzeneteket:
        // error_reporting(0); 
        include('../mpdf/mpdf.php');

        $mit = array('<script src="../js/jquery.js"></script>', '<link href="../css/nyomtatas.css" rel="stylesheet" type="text/css">');
        $html = str_replace($mit, '', $html);

        $mpdf = new mPDF('utf-8');

        $mpdf -> AddPage('P',     '',   '', '', '', '10', '10', '20', '10', '5', '5');
        $mpdf -> nbpgPrefix = '/ ';
        $header = get_nyomtatas_fejlec_top($rend_szam, '-lapszámmal-', '-Műsz.szolg. szöveg nélkül-');
        $footer = '<div class="dtc r" style="font-size: 7px">
                        '.$_SESSION[get_db_tabla().'user'].'
                        '.date('Y.m.d H:i:s').'
                    </div>';
        $mpdf ->SetHTMLHeader( $header, '', true);
        $mpdf ->SetHTMLFooter( $footer, '', true);

        $mpdf -> WriteHTML( $html );

if( isset($_GET['pdf_mutat']) ) {
    $pdf_neve = str_replace('/', '_', $munkalap['ikt_szam']).'.pdf';
    $mpdf->Output($pdf_neve, 'I'); 
    exit;
}
        
        // Fájlba mentés
        $ev         = date('y', strtotime($eszkozigenyles['insert_dt']));
        $fajlnev    = 'DH_te_megrendeles_'.$rend_szam_fajlba.'.pdf';
        if( !is_dir('../'.get_utvonal().'eszkozigenyles_megr') ) {
            mkdir('../'.get_utvonal().'eszkozigenyles_megr');
        }
        if( !is_dir('../'.get_utvonal().'eszkozigenyles_megr/'.$ev) ) {
            mkdir('../'.get_utvonal().'eszkozigenyles_megr/'.$ev);
        }

        $mpdf -> Output(iconv('UTF-8', 'Windows-1250', '../'.get_utvonal().'eszkozigenyles_megr/'.$ev.'/'.$fajlnev), 'F');
        $utvonal_db_be = $ev.'/'.$fajlnev;

        // Megrendelések dokumentumához is adjuk hozzá
        $doc_recid = $adatbazis_kapcsolat -> i('eszkozigenyles_megr_dok',   array('eszkozigenyles_megr_id', 'utvonal',      'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                            array($megr_recid,              $utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                );
        if( $doc_recid === false ) { $db_error = true; }

        // Biztosan csak egy dokumentuma lesz, hisz most jött létre
        $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_megr', array('dok_db'), array('1'), "`recid`='{$megr_recid}'");
        if( $sql === false ) { $db_error = true; }

        // Eszközigénylés dokumentumához is adjuk hozzá
        $eszkozigenylesek = $adatbazis_kapcsolat -> s('recid dok_db', 'eszkozigenyles', "`rend_szam`='{$rend_szam}'");
        if( $eszkozigenylesek === false ) { $db_error = true; }
        foreach ($eszkozigenylesek as $eszkozigenyles) {
            $sql = $adatbazis_kapcsolat -> i('eszkozigenyles_dok',  array('eszkozigenyles_id',      'utvonal',                                'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                    array($eszkozigenyles['recid'], '../eszkozigenyles_megr/'.$utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                    );
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('dok_db'), array($eszkozigenyles['dok_db']+1), "`recid`='{$eszkozigenyles['recid']}'");
            if( $sql === false ) { $db_error = true; }
        }

    } else {
        $db_error = true;
    }

    if( $db_error ) {
        return false;
    } else {
        return true;
    }
}