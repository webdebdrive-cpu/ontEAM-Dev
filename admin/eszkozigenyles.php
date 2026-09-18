<?php

require_once('../weblap.php');

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
$szazalek_fajl          = 'eszkozigenyles_szazalek.txt';

$igenyles_mezok_pdfhez = 'recid tipus stat_id nev igmen jhmen arfolyam netto jhar jhossz telephely_id szallito_id szall_hat fiz_mod hat_ido ossz_netto ossz_dev kcs_21 kiszall beuzemel munkadij egyeb_koltseg eloleg megj insert_dt'; // Ezeket az oszlopokat kérjük le PDF készítéséhez

// Cikkszám ha nincs megadva, egyszer kérdezzünk rá.
$szamla_html = nl2('
                    <script>
                        var TIPUS_KERDES_VOLT   = 0;
                        var CSZ_KERDES_VOLT     = 0;
                    </script>
                ');

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

// Megrendelők elklészítése folyamata hol áll
if (isset($_GET['folyamat_leker'])) {

    $szazalek = fajlbol_olvas($szazalek_fajl);
    if( $szazalek == '100' ) {
        unlink($szazalek_fajl);
        echo json_encode(array(
                                'kesz' => 1,
                        ));
    } else {
        echo json_encode(array(
                                'szazalek' => $szazalek,
                        ));
    }
    
    $weblap -> leallit();
    exit;
}

// Árfolyam lekérése, ha nem HUF-os az igénylés
if (isset($_POST['get_arfolyam'])) {

    if( $_POST['tervezett'] ) {
        // Ha ismert az igénylés ID, akkor onnan kérjük le az időszak azonosítót
        if( $_POST['id'] && $_POST['id'] != 'false' ) {
            $igenyles = $adatbazis_kapcsolat -> s2('idoszak_azon', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
            $arfolyam = $adatbazis_kapcsolat -> s2('arfolyam', 'besz_idoszak', "`azon`='{$igenyles['idoszak_azon']}'");

        // Új felvitelben pedig az éppen aktív időszak
        } else {
            $arfolyam = $adatbazis_kapcsolat -> s2('arfolyam', 'besz_idoszak', "`stat_id`='".BESZERZES_IDOSZAK_AKTIV."'");
        }

    } else {
        $arfolyam = $adatbazis_kapcsolat -> s2('arfolyam', 'beallitas', "`recid`='1'");
    }

    echo json_encode(array(
                             'arfolyam' => number_format($arfolyam['arfolyam'], 5, ',', ''),
                     ));

    $weblap -> leallit();
    exit;
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

// Igénylés adatainak lekérése
if (isset($_POST['get_igeny'])) {

    $igenyles = $adatbazis_kapcsolat -> s2('tipus', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    
    echo json_encode(array(
                             'tipus'  => $igenyles['tipus'],
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
        ['nev' => 'Részleg',                     'id' => 'eszkoz_tabla_reszleg'         ],
        ['nev' => 'Termék név',                  'id' => 'eszkoz_tabla_nev'             ],
        ['nev' => 'Cikkszám',                    'id' => 'eszkoz_tabla_csz'             ],
        ['nev' => 'Specifikáció',                'id' => 'eszkoz_tabla_spec'            ],
        ['nev' => 'Igénylés indoka/koncepció',   'id' => 'eszkoz_tabla_igeny_indok'     ],
        ['nev' => 'Kiegészítés megrendelőre',    'id' => 'eszkoz_tabla_megj'            ],
        ['nev' => 'Szállító',                    'id' => 'eszkoz_tabla_szallito'        ],
        ['nev' => 'Szállítási határidő',         'id' => 'eszkoz_tabla_szall_hat'       ],
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
        ['nev' => 'Szálloda igazgató',           'id' => 'eszkoz_tabla_szall_ig_vel'    ],
        ['nev' => 'Validáló',                    'id' => 'eszkoz_tabla_bi_vel'          ],
        ['nev' => 'BI vezető',                   'id' => 'eszkoz_tabla_bi_jh'           ],
        ['nev' => 'Szakmai vezetés vélemény',    'id' => 'eszkoz_tabla_szak_vel'        ],
        ['nev' => 'Felsővezető',                 'id' => 'eszkoz_tabla_felso_vel'       ],
        ['nev' => 'Véglegesítő',                 'id' => 'eszkoz_tabla_bi_vegl'         ],
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
                            <input type="checkbox" class="check check_megjelenik"'.$checked_megjelenik.' name="megjelenik_'.$oszlop['id'].'" id="megjelenik_'.$oszlop['id'].'" value="1" onclick="if( $(this).prop(\'checked\') == false ){$(\'#megjelenik_'.$oszlop['id'].'\').prop(\'checked\', false);}">
                            <label for="megjelenik_'.$oszlop['id'].'"></label>
                        </div>
                    </div>
                    <div class="dtc form_jobb">
                        <div class="cb">
                            <input type="checkbox" class="check check_egysoros"'.$checked_egysoros.' name="egysoros_'.$oszlop['id'].'" id="egysoros_'.$oszlop['id'].'" value="1" onclick="if( $(this).prop(\'checked\') == false ){$(\'#egysoros_'.$oszlop['id'].'\').prop(\'checked\', false);}">
                            <label for="egysoros_'.$oszlop['id'].'"></label>
                        </div>
                    </div>
                </div>
            ');
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

// Tervezett igénylések megrendelők elkészítése
if (isset($_POST['megrendelok_elkeszit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus idoszak_azon', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Csak tervezett igénylés engedélyezett
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        echo json_encode(array(
                                'hiba'  => 'Csak tervezett igénylésnél lehet megrendelőket elkészíteni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Csak felsővezető jóváhagyásra vár és BI véglegesítésre státusz alatt
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR && $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VEGL_VAR ) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet megrendelőket elkészíteni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nincs joga hozzá, akkor hibaüzenet. A jog a státusz párral figyelendő
    if(
        $sor['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR && !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH   ) ||
        $sor['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR  && !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT )
    ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga megrendelőket elkészíteni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Készüljünk fel az SQL lekérdezésre. Amelyik státuszban van az igénylés, az annak megfelelő igénylés sorokat kérjük majd le, és a státusznak megfelelő jóváhagyás oszlopot kell figyelni.
    $jovahagy_oszlop = 'felso_vel';
    if( $sor['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR ) {
        $jovahagy_oszlop = 'bi_vegl';
    }

    // Azokból az igénylésekből készül PDF, akik tervezettek, adott státuszúak, a jóváhagyás oszlopa 1 (tehát zöld). Tehát pl. BI véglgesítésre vár státuszú, és ekkor bi_vegl legyen 1-es, azaz zöld pipa.
    $igenylesek = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`tipus`='".BESZ_TIP_TERVEZETT."' AND `idoszak_azon`='{$sor['idoszak_azon']}' AND `stat_id`='{$sor['stat_id']}' AND `{$jovahagy_oszlop}`='1'");
    if( $igenylesek === false ) { $db_error = true; }

    // Ha még csak kérdezünk, akkor elég egy darabszámot visszaadni
    if( $_POST['csak_kerdez'] ) {
        echo json_encode( array(
                                'db' => count($igenylesek),
                                ) );
        $weblap -> leallit();
        exit;
    }
    $igenylesek = adatbazis2tomb($igenylesek, 'recid');

    // Ezzel még várjunk!
    // 2DO: IDE KÉT MEGRENDELES_KESZIT KELL. ELŐBB CSAK AZOKAT, AHOL MÉG ÜRES A MEGRENELÉS SZÁM. HA EZEN FELÜL VOLNÁNAK OLYANOK, AHOL MÁR VAN MEGRENDELÉS SZÁM (JAVÍTOTT IGÉNYLÉSEK ESETETE) AKKOR AZOKBÓL IS KELL CSINÁLNI EGY ÚJ MEGRENDELÉST.
    // SŐT, LEHET TÖBB ELTÉRŐ MEGRENDELÉS SZÁMOK VANNAK, TEHÁT AHÁNY MEGRENDELÉS SZÁM VAN ANNYI PDF KÉSZÜL.

    // PDF készítése
    if( megrendeles_keszit($igenylesek, '-tervezett-') == false ) {
        $db_error = true;
    }

    // Levélküldés, de szállodánként csak egyet
    $igenylesek_mailhez = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`tipus`='".BESZ_TIP_TERVEZETT."' AND `idoszak_azon`='{$sor['idoszak_azon']}' AND `stat_id`='{$sor['stat_id']}' AND `{$jovahagy_oszlop}`='1'", '', '', 'telephely_id');
    if( $igenylesek_mailhez === false ) { $db_error = true; }
    foreach ($igenylesek_mailhez as $igenyles_mailhez) {
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $igenyles_mailhez['recid'], ERT_BESZERZ_MEGR_KIKULD_ELO, 'eszkozigenyles', 'eszkozigenyles_megr_kikuld_elo_tervezett');
    }

    // Státusz léptetés minden egyes igénylésnél
    foreach ($igenylesek as $igenyles) {
        if( addStatusz('eszkozigenyles_statusz', $igenyles, ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
            $db_error = true;
        }
    }


    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker' => 1,
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

// Jóváhagyott mennyiség módosítása
if (isset($_POST['jhmen_mod'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $eszkozig = $adatbazis_kapcsolat -> s2('stat_id netto ossz_netto', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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

    // Jóváhagyott mennyiség eltárolása + a Jóváhagyott nettó egységár és Jóváhagyott nettó összesen mezőt is frissíteni kell.
    $jhar       = $eszkozig['netto'];
    $jhossz     = $_POST['jhmen']*$jhar;

    $mezok      = array('jhmen',         'jhar', 'jhossz');
    $ertekek    = array($_POST['jhmen'], $jhar,  $jhossz );
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    if( addMegj('eszkozigenyles', $_POST['id'], 'Jóváhagyott mennyiség módosítva: '.number_format($_POST['jhmen'], 0, ',', ' ').' db') === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'jhmen'     => number_format($_POST['jhmen'],   0, ',', ' '),
                                'jhar'      => number_format($jhar,             0, ',', ' '),
                                'jhossz'    => number_format($jhossz,           0, ',', ' '),
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

// Igénylés tételei szerkeszthetők-e (Tételek kezelése popup): a teljes feldolgozási láncon végig szerkeszthető (bármely beszerzés-igénylés jogú felhasználónak),
// egészen addig, amíg az igénylés le nem zárult (megrendelve / elutasítva)
function eszkozigenyles_tetel_szerkesztheto($eszkozig) {
    if( $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_MEGRENDELVE || $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_ELUTASITOTT ) {
        return false;
    }
    return (
        _jog(JOG_BESZERZ_IGENYLESEK_LETREH        ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_KEZEL         ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA   ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS  ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_BI_JOVA       ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL   ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH   ) ||
        _jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT )
    );
}

// Tétel sorok HTML-jét építi fel a "Tételek kezelése" popuphoz (a popup csak a Mentés gombra kattintva ír DB-be, addig a sorokkal a böngészőben dolgozunk)
function eszkozigenyles_tetel_sorok_html($eszkozigenyles_id, $szerkesztheto = true) {
    global $adatbazis_kapcsolat;

    $muvelet_cellak = '';
    if( $szerkesztheto ) {
        $muvelet_cellak = '
                <td><a class="szerk" onclick="tetel_sor_szerk(\'pop_tetel_MUVELET_ID\');">🖉</a></td>
                <td><a onclick="tetel_sor_torol(\'pop_tetel_MUVELET_ID\');"><span class="icon"><i class="material-icons">delete</i></span></a><input type="hidden" class="pop_recid" value="MUVELET_ID"></td>';
    }

    $html    = '';
    $tetelek = $adatbazis_kapcsolat -> s('*', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$eszkozigenyles_id}'", 'recid');
    foreach ( $tetelek as $tetel ) {
        $muvelet_kiir = str_replace('MUVELET_ID', $tetel['recid'], $muvelet_cellak);
        $html .= nl2('
            <tr class="pop_tetel_'.$tetel['recid'].'">
                <td class="pop_nev l">'.htmlspecialchars_decode($tetel['nev']).'</td>
                <td class="pop_spec l">'.htmlspecialchars_decode($tetel['spec']).'</td>
                <td class="pop_csz l">'.htmlspecialchars_decode($tetel['csz']).'</td>
                <td class="pop_igmen r nowrap">'.number_format($tetel['igmen'], 0, ',', ' ').'</td>
                <td class="pop_igar r nowrap">'.number_format($tetel['igar'], 0, ',', ' ').'</td>
                <td class="pop_ignetto r nowrap">'.number_format($tetel['ignetto'], 0, ',', ' ').'</td>'.
                $muvelet_kiir.'
            </tr>
        ');
    }
    return $html;
}

// Fejbe visszaírja a tételek igényelt összesítését (dok_db/megj_db mintájára a tetel_db-t is)
function eszkozigenyles_tetel_fej_ujraszamol($eszkozigenyles_id) {
    global $adatbazis_kapcsolat;

    $osszesito = $adatbazis_kapcsolat -> s2('COUNT(`recid`) AS db, SUM(`ignetto`) AS osszeg', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$eszkozigenyles_id}'");
    if( $osszesito === false ) { return false; }

    if( $osszesito['db'] == 1 ) {
        $tetel   = $adatbazis_kapcsolat -> s2('igmen igar', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$eszkozigenyles_id}'");
        if( $tetel === false ) { return false; }
        $mezok   = array('igmen',         'netto',        'ossz_netto',       'tetel_db'       );
        $ertekek = array($tetel['igmen'], $tetel['igar'], $osszesito['osszeg'], $osszesito['db'] );
    } else {
        $mezok   = array('igmen', 'netto', 'ossz_netto',         'tetel_db'       );
        $ertekek = array(0,       0,       $osszesito['osszeg'], $osszesito['db'] );
    }
    return $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$eszkozigenyles_id}'");
}

// Tételek kezelése popup feltöltése
if( isset($_POST['tetel_lista_feltolt']) ) {

    $eszkozig = $adatbazis_kapcsolat -> s2('stat_id kcs_21', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $eszkozig === false ) {
        echo json_encode(array('hiba' => tranzakcioHiba(), 'title' => 'Hiba'));
        $weblap -> leallit();
        exit;
    }

    $szerkesztheto = eszkozigenyles_tetel_szerkesztheto($eszkozig);
    echo json_encode(array(
        'html'          => eszkozigenyles_tetel_sorok_html($_POST['id'], $szerkesztheto),
        'szerkesztheto' => $szerkesztheto ? 1 : 0,
    ));

    $weblap -> leallit();
    exit;
}

// Tétel sorok mentése (Tételek kezelése popup): a popup-on összeállított teljes sorlistát kapjuk meg egyben, a törölt sorok recid-jeivel együtt
if( isset($_POST['tetel_sorok_ment']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $eszkozig = $adatbazis_kapcsolat -> s2('stat_id kcs_21', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $eszkozig === false ) { $db_error = true; }

    if( !$db_error && !eszkozigenyles_tetel_szerkesztheto($eszkozig) ) {
        echo json_encode(array('hiba' => 'Önnek nincs joga a tételeket szerkeszteni!', 'title' => 'Hiba'));
        $weblap -> leallit();
        exit;
    }

    $recidk  = json_decode($_POST['recidk'],  true);
    $nevek   = json_decode($_POST['nevek'],   true);
    $specek  = json_decode($_POST['specek'],  true);
    $cszk    = json_decode($_POST['cszk'],    true);
    $igmenek = json_decode($_POST['igmenek'], true);
    $igarok  = json_decode($_POST['igarok'],  true);
    $torolt_sorok = array_filter( json_decode($_POST['torolt_sorok'], true) );

    if( !$db_error && count($nevek) < 1 ) {
        echo json_encode(array('hiba' => 'Legalább egy tételt fel kell venni!', 'title' => 'Hiba'));
        $weblap -> leallit();
        exit;
    }

    foreach ( $nevek as $i => $nev ) {

        if( !$db_error && !trim($nev) ) {
            echo json_encode(array('hiba' => 'Kérem töltse ki a tétel nevét!', 'title' => 'Hiba'));
            $weblap -> leallit();
            exit;
        }
        if( !$db_error && ( !$igmenek[$i] || $igmenek[$i]<=0 ) ) {
            echo json_encode(array('hiba' => 'Kérem töltse ki az igényelt mennyiséget!', 'title' => 'Hiba'));
            $weblap -> leallit();
            exit;
        }
        if( !$db_error && $igarok[$i]<0 ) {
            echo json_encode(array('hiba' => 'Kérem töltse ki az igényelt egységárat!', 'title' => 'Hiba'));
            $weblap -> leallit();
            exit;
        }

        $ignetto = $igmenek[$i]*$igarok[$i];

        if( $recidk[$i] ) {
            $mezok      = array('nev',    'spec',      'csz',   'igmen',      'igar',      'ignetto', 'update_usr'                           );
            $ertekek    = array($nev,     $specek[$i], $cszk[$i], $igmenek[$i], $igarok[$i], $ignetto,  $_SESSION[get_db_tabla().'teljes_nev'] );
            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_tetel', $mezok, $ertekek, "`recid`='{$recidk[$i]}' AND `eszkozigenyles_id`='{$_POST['id']}'");
            if( $sql === false ) { $db_error = true; }
        } else {
            $mezok      = array('eszkozigenyles_id', 'nev', 'spec',      'csz',     'igmen',      'igar',      'ignetto', 'insert_dt',         'insert_usr',                            'update_usr'                            );
            $ertekek    = array($_POST['id'],        $nev,  $specek[$i], $cszk[$i], $igmenek[$i], $igarok[$i], $ignetto,  date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], $_SESSION[get_db_tabla().'teljes_nev'] );
            $sql = $adatbazis_kapcsolat -> i('eszkozigenyles_tetel', $mezok, $ertekek);
            if( $sql === false ) { $db_error = true; }
        }
    }

    foreach ( $torolt_sorok as $torolt_recid ) {
        $sql = $adatbazis_kapcsolat -> d('eszkozigenyles_tetel', "`recid`='{$torolt_recid}' AND `eszkozigenyles_id`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
    }

    if( !$db_error && eszkozigenyles_tetel_fej_ujraszamol($_POST['id']) === false ) {
        $db_error = true;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        $fej = $adatbazis_kapcsolat -> s2('ossz_netto tetel_db', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
        echo json_encode(array(
            'siker'     => 1,
            'ossz_netto'=> number_format($fej['ossz_netto'], 0, ',', ' '),
            'tetel_db'  => $fej['tetel_db'],
            'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array('hiba' => tranzakcioHiba(), 'title' => 'Hiba'));
    }

    $weblap -> leallit();
    exit;
}

// Tételek jóváhagyása popup feltöltése
if( isset($_POST['tetel_jh_feltolt']) ) {

    $tetelek = $adatbazis_kapcsolat -> s('*', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$_POST['id']}'", 'recid');
    if( $tetelek === false ) {
        echo json_encode(array('hiba' => tranzakcioHiba(), 'title' => 'Hiba'));
        $weblap -> leallit();
        exit;
    }

    $html = '';
    foreach ( $tetelek as $tetel ) {
        $jhmen = $tetel['jhmen'];
        if( !$jhmen ) {
            $jhmen = $tetel['igmen'];
        }
        $html .= nl2('
            <tr class="pop_jh_tetel_'.$tetel['recid'].'">
                <td class="l">'.htmlspecialchars_decode($tetel['nev']).'</td>
                <td class="l">'.htmlspecialchars_decode($tetel['csz']).'</td>
                <td class="r nowrap">'.number_format($tetel['igmen'], 0, ',', ' ').'</td>
                <td class="r nowrap">'.number_format($tetel['igar'], 0, ',', ' ').'</td>
                <td class="r nowrap"><input type="text" class="f_num f_csak_pozitiv oriasi pop_jh_jhmen" data-tort="0" data-recid="'.$tetel['recid'].'" data-jhar="'.$tetel['igar'].'" value="'.number_format($jhmen, 0, ',', ' ').'"></td>
                <td class="r nowrap pop_jh_jhnetto">'.number_format($jhmen*$tetel['igar'], 0, ',', ' ').'</td>
            </tr>
        ');
    }

    echo json_encode(array('html' => $html));

    $weblap -> leallit();
    exit;
}

// Tételek jóváhagyása popup mentése
if( isset($_POST['tetel_jh_ment']) ) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $eszkozig = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $eszkozig === false ) { $db_error = true; }

    if(
        !$db_error && !(
            ( _jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) && $eszkozig['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR )
        )
    ) {
        echo json_encode(array('hiba' => 'Önnek nincs joga a tételeket jóváhagyni!', 'title' => 'Hiba'));
        $weblap -> leallit();
        exit;
    }

    $tetel_idk = json_decode($_POST['tetel_idk'], true);
    $jhmenek   = json_decode($_POST['jhmenek'],   true);

    foreach ( $tetel_idk as $i => $tetel_id ) {
        $jhmen = str_replace(array(' ', ','), array('', '.'), $jhmenek[$i]);
        $tetel = $adatbazis_kapcsolat -> s2('igar', 'eszkozigenyles_tetel', "`recid`='{$tetel_id}' AND `eszkozigenyles_id`='{$_POST['id']}'");
        if( $tetel === false ) { $db_error = true; continue; }
        $jhnetto = $jhmen*$tetel['igar'];
        $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_tetel', array('jhmen', 'jhar',        'jhnetto', 'update_usr'                            ),
                                                                    array($jhmen,  $tetel['igar'], $jhnetto,  $_SESSION[get_db_tabla().'teljes_nev'] ),
                                                        "`recid`='{$tetel_id}' AND `eszkozigenyles_id`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
    }

    if( !$db_error ) {
        $osszesito = $adatbazis_kapcsolat -> s2('COUNT(`recid`) AS db, SUM(`jhnetto`) AS osszeg', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$_POST['id']}'");
        if( $osszesito === false ) { $db_error = true; }
    }

    if( !$db_error ) {
        if( $osszesito['db'] == 1 ) {
            $tetel      = $adatbazis_kapcsolat -> s2('jhmen jhar', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$_POST['id']}'");
            $mezok      = array('jhmen',         'jhar',         'jhossz'           );
            $ertekek    = array($tetel['jhmen'], $tetel['jhar'], $osszesito['osszeg']);
        } else {
            $mezok      = array('jhmen', 'jhar', 'jhossz'           );
            $ertekek    = array(0,       0,      $osszesito['osszeg']);
        }
        $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
    }

    if( !$db_error ) {
        addMegj('eszkozigenyles', $_POST['id'], 'Tételek jóváhagyott mennyisége módosítva.');
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode(array(
            'siker'  => 1,
            'jhossz' => number_format($osszesito['osszeg'], 0, ',', ' '),
            'gombok' => get_eszkozigenyles_gombok($_POST['id']),
        ));
    } else {
        mysqli_rollback($db_kapcs);
        echo json_encode(array('hiba' => tranzakcioHiba(), 'title' => 'Hiba'));
    }

    $weblap -> leallit();
    exit;
}

// Mehet BI validálásra
if (isset($_POST['mehet_validalasra'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('idoszak_azon stat_id tipus szallito_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // PHP alatt is elleőrizzük, hogy van-e szállító
    if( !$sor['szallito_id'] ) {
        echo json_encode(array(
                                'hiba'  => 'BI validálásra küldés előtt kérem adja meg a Szállítót!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Csak tervezett igényléstnél értelmezett ez a funkció
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        echo json_encode(array(
                                'hiba'  => 'Csak tervezett igénylést lehet validálásra küldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_LETREH) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga validálásra küldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_UJ_FELVITEL ) {
        echo json_encode(array(
                                'hiba'  => 'Ebben a státuszban nem lehet validálásra küldeni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Státusz léptetés
    if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VALID_VAR) === false ) {
        $db_error = true;
    }
    $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VALID_VAR);

    // Ha adott időszakon belül már nincs több kisebb státuszú igénylés, akkor email értesítést küldünk
    // idoszak_azon tevezett esetében mindig van, de azért nem árthat ez a feltétel
    // UPDATE! 2025.11.11 Ekkor mégse küldjünk még emailt, mert lehet egyessével viszik fel, tehát emiatt lesz adott igénylés az utolsó.
    // if( $sor['idoszak_azon'] ) {
    //     $van_meg_ilyen_igenyles = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`idoszak_azon`='{$sor['idoszak_azon']}' AND `stat_id`<='".ESZKOZIGENYL_STAT_UJ_FELVITEL."'");
    //     if( $van_meg_ilyen_igenyles === false ) { $db_error = true; }
    //     if( !$van_meg_ilyen_igenyles ) {
    //         $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], '-1', 'eszkozigenyles_utolso_igeny_valid', 'eszkozigenyles_kezeles_jogunak');
    //     }
    // }

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

// Műszakilag nem érintett lekezelése
if (isset($_POST['muszakilag_nem_erintett'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus musz_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['musz_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts', 'musz_indok'),
                                                        array($velemeny,    0,           $indok      ),
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
    // Ilyen ág nincs is már, mert az új igénylés egyből BI validálásra vár státuszba lép tervezett igénylénél
    }/* else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_MUSZAK_JH_VAR);
    }*/

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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus musz_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // VIGYÁZZ! IDE KÉT FÉLEKÉPPEN IS EL TUDSZ JUNTI, RÁADÁSUL A NYOMÓGOMB FELIRAT IS UGYANAZ (Műszaki előkészítést igényel), 
    // (Tervezett igénylés + Új felvitel státusz ILLETVE Soron kívüli igénylés + Műszaki jóváhagyásra vár státusz.)
    // CSAK MÁS-MÁS SZITUÁCIÓBAN JÖN KI, EMIATT MÁSKÉPP KELL JOGOT ELLENŐRIZNI:
    
    // Ha nincs joga hozzá, hibaüzenet
    if( 
        // Tagadjuk az alábbi két felállás. Igénylés típusától függ, hogy melyik jogra van szükség
        !(
            ( $sor['tipus'] == BESZ_TIP_TERVEZETT                                           && _jog(JOG_BESZERZ_IGENYLESEK_LETREH     ) ) ||
            ( ($sor['tipus'] == BESZ_TIP_SORONKIVULI || $sor['tipus'] == BESZ_TIP_GM_KERET) && _jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA) )
        )
    ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga véleményezni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( 
        // Tagadjuk az alábbi két felállás, amikor is a megfelelő státusban vagyunk. Igénylés típusától függ, hogy melyiket fogadjuk el
        !(
            ( $sor['tipus'] == BESZ_TIP_TERVEZETT                                          && $sor['stat_id'] = ESZKOZIGENYL_STAT_UJ_FELVITEL   ) ||
            (($sor['tipus'] == BESZ_TIP_SORONKIVULI || $sor['tipus'] == BESZ_TIP_GM_KERET) && $sor['stat_id'] = ESZKOZIGENYL_STAT_MUSZAK_JH_VAR )
        )
    ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet műszakilag véleményezni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Kivitelezői költség + indoklás csak soron kívülinél kötelező
    if( $sor['tipus'] == BESZ_TIP_SORONKIVULI ) {
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
    }

    // Nyomógomb eltárolása
    $velemeny = 2;
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['musz_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts',         'becsult',         'musz_indok'),
                                                        array($velemeny,    $_POST['kiv_kolts'], $_POST['becsult'], $indok      ),
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

    // Tervezett esetén marad a státusz
    } else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_UJ_FELVITEL);
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

// // Műszaki visszavon
// if (isset($_POST['muszaki_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_MUSZAK_JOVA) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga műszaki igénylést visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_MUSZAK_JH_VAR ) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet műszakilag véleményezni, sem visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('musz_el_ig', 'kiv_kolts', 'musz_indok'),
//                                                         array($velemeny,    0,           ''          ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiszámítása
//     $pipa = '';
//     $szall_ig_pipak = musz_el_ig_ertekek();
//     foreach ($szall_ig_pipak as $szall_ig_pipa) {
//         if( $szall_ig_pipa['value'] == $velemeny ) {
//             $pipa = $szall_ig_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// Szálloda igazgató jóváhagy
if (isset($_POST['szall_igazgato_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus szall_ig_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['szall_ig_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel',   'szall_ig_indok'),
                                                        array($velemeny,        $indok          ),
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
    // Ilyen ág nincs is már, mert az új igénylés egyből BI validálásra vár státuszba lép tervezett igénylénél
    }/* else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);
    }*/

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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus szall_ig_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['szall_ig_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel',   'szall_ig_indok'),
                                                        array($velemeny,        $indok          ),
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
    // Ilyen ág nincs is már, mert az új igénylés egyből BI validálásra vár státuszba lép tervezett igénylénél
    }/* else {
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZALL_ENG_VAR);
    }*/

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

// // Szálloda igazgató visszavon
// if (isset($_POST['szall_igazgato_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_SZALLODAI_ENG) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga igénylést igazgatóként visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZALL_ENG_VAR ) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést igazgatóként visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szall_ig_vel',   'szall_ig_indok'),
//                                                         array($velemeny,        ''              ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiíratása
//     $pipa = '';
//     $szall_ig_pipak = szall_ig_vel_ertekek();
//     foreach ($szall_ig_pipak as $szall_ig_pipa) {
//         if( $szall_ig_pipa['value'] == $velemeny ) {
//             $pipa = $szall_ig_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// BI validál
if (isset($_POST['bi_validal'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('idoszak_azon stat_id tipus dok_db bi_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést BI-ként validálni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VALID_VAR ) {
        echo json_encode(array(
                                'hiba'  => $statusz_hiba, // 'Ebben a státuszban nem lehet igénylést BI-ként validálni!',
                                'gomb'  => $statusz_hiba_gomb,
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nyomógomb eltárolása
    $velemeny = 1;
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel',     'bi_indok'),
                                                        array($velemeny,    $indok      ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_JH_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_jh_elo');

    // 2025.09.25 Már nem, mindkét esetben BI jóváhagyásra vár státuszba lépünk
    // // GM keret esetén Kiküldésre vár státuszba lépünk, azaz kimaradt egy adag státuszozás
    // } else if( $sor['tipus'] == BESZ_TIP_GM_KERET) {

    //     // Megrendelés készítése (fontos, hogy még státuszléptetés előtt, mert a megrendelésnél van státusz ellenőrzés)
    //     if( megrendeles_keszit($_POST['id']) === false ) {
    //         $db_error = true;
    //     }

    //     // Státusz léptetés
    //     if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
    //         $db_error = true;
    //     }
        
    //     $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_KIKULD_VAR);

    //     // E-mail értesítés küldése
    //     $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_MEGR_KIKULD_ELO, 'eszkozigenyles', 'eszkozigenyles_megr_kikuld_elo');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_SZAKM_VEL_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);

        // Ha adott időszakon belül már nincs több kisebb státuszú igénylés, akkor email értesítést küldünk
        // idoszak_azon tevezett esetében mindig van, de azért nem árthat ez a feltétel
        if( $sor['idoszak_azon'] ) {
            $van_meg_ilyen_igenyles = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`idoszak_azon`='{$sor['idoszak_azon']}' AND `stat_id`<='".ESZKOZIGENYL_STAT_BI_VALID_VAR."'");
            if( $van_meg_ilyen_igenyles === false ) { $db_error = true; }
            if( !$van_meg_ilyen_igenyles ) {
                $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_SZAKMAI_VEL_ELO, 'eszkozigenyles_utolso_igeny_szakmai', 'eszkozigenyles_szak_vel_var_jogunak');
            }
        }

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('idoszak_azon stat_id tipus bi_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel',  'bi_indok' ),
                                                        array($velemeny, $indok     ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT ) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_bi_elutasit');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // Ha adott időszakon belül már nincs több kisebb státuszú igénylés, akkor email értesítést küldünk
        // idoszak_azon tevezett esetében mindig van, de azért nem árthat ez a feltétel
        if( $sor['idoszak_azon'] ) {
            $van_meg_ilyen_igenyles = $adatbazis_kapcsolat -> s('recid', 'eszkozigenyles', "`idoszak_azon`='{$sor['idoszak_azon']}' AND `stat_id`<='".ESZKOZIGENYL_STAT_BI_VALID_VAR."' AND `recid`!='{$_POST['id']}'");
            if( $van_meg_ilyen_igenyles === false ) { $db_error = true; }
            if( !$van_meg_ilyen_igenyles ) {
                $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_SZAKMAI_VEL_ELO, 'eszkozigenyles_utolso_igeny_szakmai', 'eszkozigenyles_vel_var_jogunak');
            }
        }
        $naviKov = 1;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                'naviKov'   => $naviKov,
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

// // BI visszavon
// if (isset($_POST['bi_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga BI-ként visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VALID_VAR ) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést BI-ként visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vel',   'bi_indok'),
//                                                         array(0,          ''        ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiíratása
//     $pipa = '';
//     $bi_pipak = bi_vel_ertekek();
//     foreach ($bi_pipak as $bi_pipa) {
//         if( $bi_pipa['value'] == $velemeny ) {
//             $pipa = $bi_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// BI jóváhagy
if (isset($_POST['bi_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus dok_db bi_jh_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést BI-ként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_JH_VAR) {
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_jh_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_jh',   'bi_jh_indok'),
                                                        array($velemeny, $indok       ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_vel_pipak = bi_jh_ertekek();
    foreach ($bi_vel_pipak as $bi_vel_pipa) {
        if( $bi_vel_pipa['value'] == $velemeny ) {
            $pipa = $bi_vel_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    $naviKov = '';
    if( $sor['tipus'] == BESZ_TIP_SORONKIVULI ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }

        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

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

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_KIKULD_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

// BI részben beleegyezik
if (isset($_POST['bi_jh_felt_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus bi_jh_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga igénylést BI-ként jóváhagyni!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_JH_VAR ) {
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_jh_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_jh',      'bi_jh_indok'),
                                                        array($velemeny,    $indok       ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_jh_pipak = bi_jh_ertekek();
    foreach ($bi_jh_pipak as $bi_jh_pipa) {
        if( $bi_jh_pipa['value'] == $velemeny ) {
            $pipa = $bi_jh_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha soron kívüli
    $naviKov = '';
    if( $sor['tipus'] == BESZ_TIP_SORONKIVULI ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

    // GM keret esetén Kiküldésre vár státuszba lépünk, azaz kimaradt egy adag státuszozás
    } else if( $sor['tipus'] == BESZ_TIP_GM_KERET) {

        // Státusz léptetés
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VEGL_VAR) === false ) {
            $db_error = true;
        }
        
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_VEGLEGESIT_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_veglegesit_elo');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VEGL_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

// BI elutasít (BI jóváhagyásra vár)
if (isset($_POST['bi_jh_elutasit'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $_POST['indok'] = trim($_POST['indok']);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus bi_jh_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_JOVA) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga BI-ként elutasítani!',
                                'title' => 'Hiba',
                        ));
        $weblap -> leallit();
        exit;
    }

    // Nem megfelelő státuszkor hibaüzenet
    if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_JH_VAR) {
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_jh_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_jh',      'bi_jh_indok'),
                                                        array($velemeny,    $indok       ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Pipa kiíratása
    $pipa = '';
    $bi_pipak = bi_jh_ertekek();
    foreach ($bi_pipak as $bi_pipa) {
        if( $bi_pipa['value'] == $velemeny ) {
            $pipa = $bi_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT ) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_bi_jh_elutasit');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT ) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        $naviKov = 1;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus szak_indok netto igmen jhmen', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['szak_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'),
                                                        array($velemeny,    $indok      ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Jóváhagyott mennyiség eltárolása. Itt most a jóváhagyott mennyiség az igényelt mennyiség.
    // 2026.03.16 DE CSAK AKKOR, ha még nincs értéke a jhmen-nek. Ha van, akkor már ne írjuk azt felül!
    if( !$sor['jhmen'] ) {
        $jhar       = $sor['netto'];
        $jhossz     = $sor['igmen']*$jhar;
    
        $mezok      = array('jhmen',        'jhar', 'jhossz');
        $ertekek    = array($sor['igmen'],  $jhar,  $jhossz );
        $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }
    }

    // Pipa kiíratása
    $pipa = '';
    $szak_pipak = szak_vel_ertekek();
    foreach ($szak_pipak as $szak_pipa) {
        if( $szak_pipa['value'] == $velemeny ) {
            $pipa = $szak_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

    // Tervezett esetén is státusz léptetés
    } else {

        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }

        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus szak_indok netto igmen', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['szak_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'),
                                                        array($velemeny,    $indok      ),
                                                    "`recid`='{$_POST['id']}'");
    if( $sql === false ) { $db_error = true; }

    // Jóváhagyott mennyiség eltárolása. Itt most a jóváhagyott mennyiség az igényelt mennyiség.
    // A deviza összesen mezőt (ha devizás az igény) és a jóváhagyott nettó összesen mezőt frissíteni kell
    $jhar       = $sor['netto'];
    $jhossz     = $sor['igmen']*$jhar;

    $mezok      = array('jhmen',        'jhar', 'jhossz');
    $ertekek    = array($sor['igmen'],  $jhar,  $jhossz );
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$_POST['id']}'");
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_FELSOVEZ_JH_ELO, 'eszkozigenyles', 'eszkozigenyles_felsovez_jh_elo');

    // Tervezett esetén is státusz léptetés
    } else {
        // $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_SZAKM_VEL_VAR);
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus szak_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['szak_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'),
                                                        array($velemeny,    $indok      ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR ) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        // E-mail értesítés küldése
        // Itt mégsem kell, mert innen tovább megy felsővezetés elé
        // $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_szak_elutasit');

    // Tervezett esetén is státusz léptetés
    } else {

        // Illetve ekkor ürítünk pár mezőt:
        $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('jhmen', 'jhar', 'jhossz'),
                                                            array(0,       0,      0      ),
                                                            "`recid`='{$_POST['id']}'");
        if( $sql === false ) { $db_error = true; }

        // Majd státuszléptetés
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_FELSO_JH_VAR ) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

// // Szakmai vezetés visszavon
// if (isset($_POST['szak_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_SZAKMAI_VEL) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga szakmai vezetésként visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_SZAKM_VEL_VAR ) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet igénylést szakmai vezetésként visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('szak_vel',   'szak_indok'),
//                                                         array($velemeny,    ''          ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiíratása
//     $pipa = '';
//     $szak_pipak = szak_vel_ertekek();
//     foreach ($szak_pipak as $szak_pipa) {
//         if( $szak_pipa['value'] == $velemeny ) {
//             $pipa = $szak_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// Felsővezetés beleegyezik
if (isset($_POST['felso_beleegyezes'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus felso_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['felso_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel', 'felso_indok'),
                                                        array($velemeny,    $indok      ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
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

    // Tervezett esetén is státusz léptetés
    } else {
        // NEM! Tervezett esetben nincs még státuszéptetés jóváhagyáskor, majd csak a Megrendelők elkészítések gombra kattintva
        // if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
        //     $db_error = true;
        // }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_FELSO_JH_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus felso_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['felso_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'   ),
                                                        array($velemeny,    $indok          ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VEGL_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_VEGLEGESIT_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_veglegesit_elo');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VEGL_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        $naviKov = 1;
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
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus felso_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['felso_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'   ),
                                                        array($velemeny,    $indok          ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_felso_elutasit');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        $naviKov = 1;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                'naviKov'   => $naviKov,
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

// // Felsővezetés visszavon
// if (isset($_POST['felso_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga felsővezetőként visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_FELSO_JH_VAR) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet felsővezetőként visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('felso_vel',  'felso_indok'),
//                                                         array($velemeny,    ''           ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiíratása
//     $pipa = '';
//     $felso_pipak = felso_vel_ertekek();
//     foreach ($felso_pipak as $felso_pipa) {
//         if( $felso_pipa['value'] == $velemeny ) {
//             $pipa = $felso_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// BI véglegesít beleegyezik
if (isset($_POST['bi_vegl_jovahagy'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus dok_db bi_vegl_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_vegl_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl', 'bi_vegl_indok'),
                                                        array($velemeny, $indok         ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
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

    // Tervezett esetén is státusz léptetés
    } else {
        // NEM! Tervezett esetben nincs még státuszéptetés jóváhagyáskor, majd csak a Megrendelők elkészítések gombra kattintva
        // if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_KIKULD_VAR) === false ) {
        //     $db_error = true;
        // }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VEGL_VAR);

        $naviKov = 1;
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
                                'dok_db'    => $sor['dok_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'naviKov'   => $naviKov,
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

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus bi_vegl_indok', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
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
    $indok = '';
    if( $_POST['indok'] ) {
        $indok .= datum_kiir(date('Y-m-d H:i:s')).' '.$_SESSION[get_db_tabla().'teljes_nev']."\n".$_POST['indok']."\n\n";
    }
    $indok .= $sor['bi_vegl_indok'];
    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl',  'bi_vegl_indok'   ),
                                                        array($velemeny,  $indok            ),
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

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_ELUTASIT, 'eszkozigenyles', 'eszkozigenyles_bi_vegl_elutasit');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_ELUTASITOTT) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_ELUTASITOTT);

        $naviKov = 1;
    }

    if( !$db_error ) {
        mysqli_commit($db_kapcs);
        echo json_encode( array(
                                'siker'     => 1,
                                'statusz'   => $statusz,
                                'pipa'      => $pipa,
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'indok'     => $_POST['indok'],
                                'naviKov'   => $naviKov,
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

// // BI véglegesítés visszavon
// if (isset($_POST['bi_vegl_visszavon'])) {

//     $db_error = false;
//     mysqli_autocommit($db_kapcs, false);

//     $sor = $adatbazis_kapcsolat -> s2('stat_id', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
//     if( $sor === false ) { $db_error = true; }

//     // Ha nincs joga hozzá, akkor hibaüzenet
//     if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) ) {
//         echo json_encode(array(
//                                 'hiba'  => 'Önnek nincs joga BI véglegesítőként visszavonni!',
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nem megfelelő státuszkor hibaüzenet
//     if( $sor['stat_id'] != ESZKOZIGENYL_STAT_BI_VEGL_VAR) {
//         echo json_encode(array(
//                                 'hiba'  => $statusz_hiba, //'Ebben a státuszban nem lehet BI véglegesítőként visszavonni!',
//                                 'gomb'  => $statusz_hiba_gomb,
//                                 'title' => 'Hiba',
//                         ));
//         $weblap -> leallit();
//         exit;
//     }

//     // Nyomógomb eltárolása
//     $velemeny = 0;
//     $sql = $adatbazis_kapcsolat -> u('eszkozigenyles',  array('bi_vegl',   'bi_vegl_indok'),
//                                                         array($velemeny,   ''             ),
//                                                     "`recid`='{$_POST['id']}'");
//     if( $sql === false ) { $db_error = true; }

//     // Pipa kiíratása
//     $pipa = '';
//     $bi_vegl_pipak = bi_vegl_ertekek();
//     foreach ($bi_vegl_pipak as $bi_vegl_pipa) {
//         if( $bi_vegl_pipa['value'] == $velemeny ) {
//             $pipa = $bi_vegl_pipa['ikon'];
//             break;
//         }
//     }

//     if( !$db_error ) {
//         mysqli_commit($db_kapcs);
//         echo json_encode( array(
//                                 'siker'     => 1,
//                                 'pipa'      => $pipa,
//                                 'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
//                                 ) );
//     } else {
//         mysqli_rollback($db_kapcs);
//         echo json_encode(array(
//             'hiba'  => tranzakcioHiba(),
//             'title' => 'Hiba'
//         ));
//     }

//     $weblap -> leallit();
//     exit;
// }

// Vissza BI validálásra
if (isset($_POST['vissza_validalasra'])) {

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $sor = $adatbazis_kapcsolat -> s2('stat_id tipus dok_db bi_vegl', 'eszkozigenyles', "`recid`='{$_POST['id']}'");
    if( $sor === false ) { $db_error = true; }

    // Ha nincs joga hozzá, akkor hibaüzenet
    if( !_jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) ) {
        echo json_encode(array(
                                'hiba'  => 'Önnek nincs joga az igénylést visszaküldeni BI validálásra!',
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

    // Pipa kiíratása
    $pipa = '';
    $bi_vegl_pipak = bi_vegl_ertekek();
    foreach ($bi_vegl_pipak as $bi_vegl_pipa) {
        if( $bi_vegl_pipa['value'] == $sor['bi_vegl'] ) {
            $pipa = $bi_vegl_pipa['ikon'];
            break;
        }
    }

    // Státusz léptetés, ha nem tervezett
    $naviKov = '';
    if( $sor['tipus'] != BESZ_TIP_TERVEZETT ) {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VALID_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VALID_VAR);

        // E-mail értesítés küldése
        $mail_hibauzenet = level_felkeszit_es_kuld('eszkozigenyles', $_POST['id'], ERT_BESZERZ_BI_VALIDALAS_ELO, 'eszkozigenyles', 'eszkozigenyles_bi_validalas_elo');

    // Tervezett esetén is státusz léptetés
    } else {
        if( addStatusz('eszkozigenyles_statusz', $_POST['id'], ESZKOZIGENYL_STAT_BI_VALID_VAR) === false ) {
            $db_error = true;
        }
        $statusz = getStatText('eszkozigenyles', ESZKOZIGENYL_STAT_BI_VALID_VAR);

        $naviKov = 1;
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
                                'dok_db'    => $sor['dok_db'],
                                'gombok'    => get_eszkozigenyles_gombok($_POST['id']),
                                'naviKov'   => $naviKov,
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

    // Ha van tol és ig, akkor az egy felsővezetői prezentáció
    if( $_GET['tol'] && $_GET['ig'] ) {

        if( !valos_datum_e($_GET['tol']) ) {
            ajax_hiba('Kérem egy valós kezdő dátumot adjon meg!');
        }

        if( !valos_datum_e($_GET['ig']) ) {
            ajax_hiba('Kérem egy valós végdátumot adjon meg!');
        }
        
        if( $_GET['ig']<$_GET['tol'] ) {
            ajax_hiba('A kezdő dátum nem lehet kisebb a végdátumtól!');
        }

        require_once('selectek.php');
        $musz_el_ig_ertekek     = musz_el_ig_ertekek();
        $szall_ig_vel_ertekek   = szall_ig_vel_ertekek();
        $bi_vel_ertekek         = bi_vel_ertekek();
        $bi_jh_ertekek          = bi_jh_ertekek();
        $szak_vel_ertekek       = szak_vel_ertekek();        
        $felso_vel_ertekek      = felso_vel_ertekek();        
    
        // CSV export előkészítése
        list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
        if( $open ) {

            // Két lekérdezést csinálunk: 
            // 1. Az egyik az aktív státuszú beszerzési időszakhoz tartozó, (tehát tervezett) igénylések kerül lekérdezésre, szálloda, azon belül igénylő részleg, azon belül nettó érték szerint lekérve, ez utóbbi úgy, hogy legfelül a legdrágább.
            $sql = 'SELECT
                    `'.get_db_tabla().'eszkozigenyles`.`azon` as \'Igény ID\',
                    `telephely_id` as \'Szálloda\',
                    `reszleg` as \'Részleg\',
                    `tipus` as \'Típus\',
                    `'.get_db_tabla().'eszkozigenyles`.`nev` as \'Termék név\',
                    `szallito_id` as \'Szállító\',
                    `ossz_netto` as \'Nettó összesen\',
                    `jhmen` as \'Jóváhagyott mennyiség\',
                    `jhar` as \'Jóváhagyott nettó egységár\',
                    `jhossz` as \'Jóváhagyott nettó összesen\',
                    `kcs_21` as \'Pénznem\',
                    `igmen` as \'Igényelt mennyiség\',
                    `kiv_kolts` as \'Műszaki előkészítés költség\',
                    `musz_el_ig` as \'Műszaki előkészítést igényel\',
                    `musz_indok` as \'Indoklás\',
                    `szall_ig_vel` as \'Szálloda igazgató vélemény\',
                    `szall_ig_indok` as \'Szálloda igazgató indoklás\',
                    `bi_vel` as \'Validáló\',
                    `bi_indok` as \'Validáló indoklás\',
                    `bi_jh` as \'BI vezető\',
                    `bi_jh_indok` as \'BI vezető indoklás\',
                    `szak_vel` as \'Szakmai vélemény\',
                    `szak_indok` as \'Szakmai indoklás\',
                    `felso_vel` as \'Felsővezető\',
                    `felso_indok` as \'Felsővezető indoklás\'

                    '.csv_export_get_from_tol($megnevezes).
                    // És akkor itt folytatjuk a feltételt: aktív státuszú beszerzési időszakhoz tartozó igénylések, amik nincsenek elutasítva
                    " AND `idoszak_azon`=(SELECT `azon` FROM `".get_db_tabla()."besz_idoszak` WHERE `stat_id`='".BESZERZES_IDOSZAK_AKTIV."') AND `stat_id`!='".ESZKOZIGENYL_STAT_ELUTASITOTT."'".
                    // szálloda, azon belül igénylő részleg, azon belül nettó érték szerint lekérve, ez utóbbi úgy, hogy legfelül a legdrágább.
                    " ORDER BY `".get_db_tabla()."telephely`.`nev`, `".get_db_tabla()."reszleg_nev`.`info`, `ossz_netto` DESC
                    ";

            $query = mysqli_query($db_kapcs, $sql);
    
            // Nagyobb CSV-kre kérdezzünk rá előzetesen
            $osszes_sor = mysqli_num_rows($query);
    
            if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                $weblap -> leallit();
                exit;
            }
    
            $query = mysqli_query($db_kapcs, $sql);
     
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
                        $sor = getStatText('eszkozigenyles', $sor);
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
    
                    if( $kulcs == 'Pénznem' ) {
                        $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                    }

                    if( $kulcs == 'Részleg' ) {
                        $sor = erteklista(ERTEKLISTA_RESZLEG, $sor);
                    }

                    if( $kulcs == 'Műszaki előkészítést igényel' ) {
                        $sor = getSpecialisPipaloErtek($szak_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Szálloda igazgató vélemény' ) {
                        $sor = getSpecialisPipaloErtek($szall_ig_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Validáló' ) {
                        $sor = getSpecialisPipaloErtek($bi_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'BI vezető' ) {
                        $sor = getSpecialisPipaloErtek($bi_jh_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Szakmai vélemény' ) {
                        $sor = getSpecialisPipaloErtek($szak_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Felsővezető' ) {
                        $sor = getSpecialisPipaloErtek($felso_vel_ertekek, $sor, '-text-');
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

            // Pár üres sor a következő táblázat előtt
            $csvcont = [];
            fputcsv($open, $csvcont, ';');
            fputcsv($open, $csvcont, ';');
            fputcsv($open, $csvcont, ';');


            // 2. lekérdezés: Megadott dátumok alapján a soronkívüli/GM terhes eredmény, amelyek a dátum tól - ig időszakban lettek feladva.
            $sql = 'SELECT
                    `'.get_db_tabla().'eszkozigenyles`.`azon` as \'Igény ID\',
                    `telephely_id` as \'Szálloda\',
                    `reszleg` as \'Részleg\',
                    `tipus` as \'Típus\',
                    `'.get_db_tabla().'eszkozigenyles`.`nev` as \'Termék név\',
                    `szallito_id` as \'Szállító\',
                    `ossz_netto` as \'Nettó összesen\',
                    `jhmen` as \'Jóváhagyott mennyiség\',
                    `jhar` as \'Jóváhagyott nettó egységár\',
                    `jhossz` as \'Jóváhagyott nettó összesen\',
                    `kcs_21` as \'Pénznem\',
                    `igmen` as \'Igényelt mennyiség\',
                    `kiv_kolts` as \'Műszaki előkészítés költség\',
                    `musz_el_ig` as \'Műszaki előkészítést igényel\',
                    `musz_indok` as \'Indoklás\',
                    `szall_ig_vel` as \'Szálloda igazgató vélemény\',
                    `szall_ig_indok` as \'Szálloda igazgató indoklás\',
                    `bi_vel` as \'Validáló\',
                    `bi_indok` as \'Validáló indoklás\',
                    `bi_jh` as \'BI vezető\',
                    `bi_jh_indok` as \'BI vezető indoklás\',
                    `szak_vel` as \'Szakmai vélemény\',
                    `szak_indok` as \'Szakmai indoklás\',
                    `felso_vel` as \'Felsővezető\',
                    `felso_indok` as \'Felsővezető indoklás\'
                    '.csv_export_get_from_tol($megnevezes).
                    // És akkor itt folytatjuk a feltételt: aktív státuszú beszerzési időszakhoz tartozó igénylések, amik nincsenek elutasítva
                    " AND `tipus`!='".BESZ_TIP_TERVEZETT."' AND `stat_id`!='".ESZKOZIGENYL_STAT_ELUTASITOTT."' AND `".get_db_tabla()."eszkozigenyles`.`insert_dt`>='".$_GET['tol']." 00:00:00' AND `".get_db_tabla()."eszkozigenyles`.`insert_dt`<='".$_GET['ig']." 23:59:59'";

            $query = mysqli_query($db_kapcs, $sql);
    
            // Nagyobb CSV-kre kérdezzünk rá előzetesen
            $osszes_sor = mysqli_num_rows($query);
            if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
                $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
                echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
                $weblap -> leallit();
                exit;
            } else {
                // szálloda, azon belül igénylő részleg, azon belül nettó érték szerint lekérve, ez utóbbi úgy, hogy legfelül a legdrágább.
                $sql .= " ORDER BY `".get_db_tabla()."telephely`.`nev`, `".get_db_tabla()."reszleg_nev`.`info`, `ossz_netto` DESC";
            }
    
            $query = mysqli_query($db_kapcs, $sql);
     
            while( $sorok = mysqli_fetch_assoc($query) ) {
    
                $csvcont = array();
                foreach( $sorok as $kulcs => $sor ) {  
                    
                    if( in_array($kulcs, $tort_szamok) ) {
                        $sor = str_replace('.', ',', $sor);
                    }
                    
                    if( $kulcs == 'Státusz' ) {
                        $sor = getStatText('eszkozigenyles', $sor);
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
    
                    if( $kulcs == 'Pénznem' ) {
                        $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                    }

                    if( $kulcs == 'Részleg' ) {
                        $sor = erteklista(ERTEKLISTA_RESZLEG, $sor);
                    }

                    if( $kulcs == 'Műszaki előkészítést igényel' ) {
                        $sor = getSpecialisPipaloErtek($szak_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Szálloda igazgató vélemény' ) {
                        $sor = getSpecialisPipaloErtek($szall_ig_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Validáló' ) {
                        $sor = getSpecialisPipaloErtek($bi_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'BI vezető' ) {
                        $sor = getSpecialisPipaloErtek($bi_jh_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Szakmai vélemény' ) {
                        $sor = getSpecialisPipaloErtek($szak_vel_ertekek, $sor, '-text-');
                    }

                    if( $kulcs == 'Felsővezető' ) {
                        $sor = getSpecialisPipaloErtek($felso_vel_ertekek, $sor, '-text-');
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
    
        csv_export_lezar($fh, $filename, 'Felsővezetői prezentáció');

    // Általános eset
    } else {
    
        // CSV export előkészítése
        list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
        if( $open ) {
            
            $sql = 'SELECT
                    `'.get_db_tabla().'eszkozigenyles`.`recid` as \'__recid__\',
                    `stat_id` as \'Státusz\',
                    `'.get_db_tabla().'eszkozigenyles`.`azon` as \'Azonosító\',
                    `idoszak_azon` as \'Időszak ID\',
                    `tipus` as \'Típus\',
                    `prior` as \'Prioritás\',
                    `telephely_id` as \'Szálloda\',
                    `reszleg` as \'Részleg\',
                    `'.get_db_tabla().'eszkozigenyles`.`nev` as \'Termék név\',
                    `csz` as \'Cikkszám\',
                    `spec` as \'Specifikáció\',
                    `igeny_indok` as \'Igénylés indoka/koncepció\',
                    `megj` as \'Megjegyzés\',
                    `szallito_id` as \'Szállító\',
                    `szall_hat` as \'Szállítási határidő:\',
                    `fiz_mod` as \'Fizetési mód\',
                    `hat_ido` as \'Fizetési határidő\',
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
                    `szall_ig_vel` as \'Szálloda igazgató\',
                    `bi_vel` as \'Validáló\',
                    `bi_jh` as \'BI vezető\',
                    `felso_vel` as \'Felsővezető\''.
                    csv_export_get_from_tol($megnevezes);
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
                if( $fejlec_nevek -> name == '__recid__' ) { continue; }
                $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
            }
            fputcsv($open, $csvhead, ';');

            $tort_szamok = ['Árfolyam', 'Deviza egységár', 'Kiszállítási költség', 'Beüzemelési költség', 'Munkadíj', 'Egyéb költség', 'Kivitelezési költség'];
            $exportalt_recidek = [];
            $i = 0;
            while( $sorok = mysqli_fetch_assoc($query) ) {

                $exportalt_recidek[] = $sorok['__recid__'];

                $csvcont = array();
                foreach( $sorok as $kulcs => $sor ) {

                    if( $kulcs == '__recid__' ) { continue; }

                    if( in_array($kulcs, $tort_szamok) ) {
                        $sor = str_replace('.', ',', $sor);
                    }

                    if( $kulcs == 'Időszak ID' ) {
                        $sor = '{EGYENLOSEG_JELET_ELE}'.$sor.'{EGYENLOSEG_JELET_ELE_VEGE}';
                    }
                    
                    if( $kulcs == 'Státusz' ) {
                        $sor = getStatText('eszkozigenyles', $sor);
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
    
                    if( $kulcs == 'Pénznem' ) {
                        $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
                    }
    
                    if( $kulcs == 'Részleg' ) {
                        $sor = erteklista(ERTEKLISTA_RESZLEG, $sor);
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

            // A fenti exportált igénylésekhez tartozó tételek soronkénti bontása, az igénylés-sorok után
            if( $exportalt_recidek ) {

                // Pár üres sor a tétel-bontás előtt
                $csvcont = [];
                fputcsv($open, $csvcont, ';');
                fputcsv($open, $csvcont, ';');
                fputcsv($open, $csvcont, ';');

                $tetel_sql = 'SELECT
                        `'.get_db_tabla().'eszkozigenyles`.`azon` as \'Azonosító\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`nev` as \'Termék név\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`csz` as \'Cikkszám\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`spec` as \'Specifikáció\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`igmen` as \'Igényelt mennyiség\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`igar` as \'Igényelt egységár\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`ignetto` as \'Igényelt nettó\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`jhmen` as \'Jóváhagyott mennyiség\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`jhar` as \'Jóváhagyott egységár\',
                        `'.get_db_tabla().'eszkozigenyles_tetel`.`jhnetto` as \'Jóváhagyott nettó\'
                        FROM `'.get_db_tabla().'eszkozigenyles_tetel`
                        JOIN `'.get_db_tabla().'eszkozigenyles` ON `'.get_db_tabla().'eszkozigenyles`.`recid`=`'.get_db_tabla().'eszkozigenyles_tetel`.`eszkozigenyles_id`
                        WHERE `'.get_db_tabla().'eszkozigenyles_tetel`.`eszkozigenyles_id` IN ('.implode(', ', $exportalt_recidek).')
                        ORDER BY `'.get_db_tabla().'eszkozigenyles_tetel`.`eszkozigenyles_id`, `'.get_db_tabla().'eszkozigenyles_tetel`.`recid`';

                $tetel_query = mysqli_query($db_kapcs, $tetel_sql);

                $tetel_csvhead = array();
                while ( $fejlec_nevek = mysqli_fetch_field($tetel_query) ) {
                    $tetel_csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
                }
                fputcsv($open, $tetel_csvhead, ';');

                while( $sorok = mysqli_fetch_assoc($tetel_query) ) {

                    $csvcont = array();
                    foreach( $sorok as $sor ) {
                        $sor = htmlspecialchars_decode( $sor );
                        while ( strpos($sor, '&amp;') !== false || strpos($sor, '&quot;') !== false ) {
                            $sor = htmlspecialchars_decode( $sor );
                        }
                        $sor = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
                        $csvcont[] = $sor;
                    }
                    fputcsv($open, $csvcont, ';');
                }
            }
        }

        csv_export_lezar($fh, $filename, 'Eszközigénylések');
    }

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
if( $sor['tetel_db'] ) {
    $tetel_db = ' ('.$sor['tetel_db'].')';
}

if( !isset($_GET['uj']) && ( isset($_GET['tablazat']) || isset($_GET['modosit']) || isset($_GET['tetel']) || isset($_GET['jovahagyasok']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) ) {
    // Számla fülek
    $ful_tomb = array(
        'tablazat'      => 'Eszközigénylés',
        'modosit'       => 'Adatlap',
        'tetel'         => 'Tételek'.$tetel_db,
        'jovahagyasok'  => 'Jóváhagyások',
        'tortenet'      => 'Történet',
        'dokumentumok'  => 'Dokumentumok'.$dok_db,
        'megjegyzesek'  => 'Megjegyzések'.$megj_db,
    );

    $kivetelek = array('ajax', 'uj', 'no_lista');
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
// Mivel a Mentés gombra kattintva fut le, ezért támaszkodhatunk arra, hogy az adatlap fülön vagyunk, tehát JS-el elérhetőek a mezők.

// GM terhes esetén nem lehet magasabb a Nettó egységár mint 50k és az össz nettó sem lehet több 500k
// Ha a típus tervezett, akkor nézzük meg, hogy mik az aktív időszak határidejei. 
$idoszak = $adatbazis_kapcsolat -> s2('hatar nyitva hatar_fured nyitva_fured', 'besz_idoszak', "`stat_id`='".BESZERZES_IDOSZAK_AKTIV."'");
$js_submit_elott_kieg1 = $js_submit_elott_kieg2 = '';
// Az alábbit csak új felvitelnél ellenőrizzük
if( isset($_GET['uj']) ) {
    $js_submit_elott_kieg1 = '
                        if( !$(\'#csz\').val() && !CSZ_KERDES_VOLT ) {
                            CSZ_KERDES_VOLT = 1;
                            alert2(\'A cikkszám nincs megadava.<br>Biztos, hogy menti az igénylést cikkszám nélkül?\', \'<input type="button" class="gomb zold" value="Igen" onclick="alert2(); $(\\\'#mehet_gomb\\\').click();"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); CSZ_KERDES_VOLT = 0;">\', \'Kérdés\');
                            return false;
                        }
                        ';
    $js_submit_elott_kieg2 = '
                            /* Tervezett igénylésél határidők/nyitottság ellenőrzése */
                            if( $(\'#tipus\').val() == '.BESZ_TIP_TERVEZETT.' ) {
                                if( ![\''.implode('\', \'', furedi_szallodak()).'\'].includes( $(\'#telephely_id\').val() ) && \''.$idoszak['nyitva'].'\' ==\'0\' ) {
                                    alert2(\'Az igénylés időszak zárva van erre a szállodára!\', \'\', \'Hiba\');
                                    return false;
                                }
                                if( [\''.implode('\', \'', furedi_szallodak()).'\'].includes( $(\'#telephely_id\').val() ) && \''.$idoszak['nyitva_fured'].'\' ==\'0\' ) {
                                    alert2(\'Az igénylés időszak zárva van a füredi szállodákra!\', \'\', \'Hiba\');
                                    return false;
                                }
                            }
                        ';
}
// Az alábbi feltétel pedig csak módosításkor kell bele
if( $_GET['id'] ) {
    // Ha tervezett volt a típusa, de most már más van kiválasztva, akkor státusz is fogunk léptetni, erről tájékoztassuk
    if( $sor['tipus'] == BESZ_TIP_TERVEZETT ) {
        $js_submit_elott_kieg3 = '
                                if( $(\'#tipus\').val() != \''.BESZ_TIP_TERVEZETT.'\' && !TIPUS_KERDES_VOLT ) {
                                    TIPUS_KERDES_VOLT = 1;
                                    alert2(\'Figyelem!<br>Ha az igénylés típusát tervzettről átállítja, akkor az igény műszaki jóváhagyásra vár státuszba kerül.<br>Folytatja?\', \'<input type="button" class="gomb zold" value="Igen" onclick="alert2(); $(\\\'#mehet_gomb\\\').click();"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); TIPUS_KERDES_VOLT = 0;">\', \'Kérdés\');
                                    return false;
                                }
                            ';
    }
}
$js_submit_elott = nl2('

                        '.$js_submit_elott_kieg1.'

                        if( $(\'#tipus\').val() == '.BESZ_TIP_GM_KERET.' ) {
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
                        
                        '.$js_submit_elott_kieg2.'
                        '.$js_submit_elott_kieg3.'

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

                        if( _get(\'uj\') && !$(\'#szallito_id\').val() && !$(\'#szallito_igenyelve\').val() ) {
                            /* 2026.02.17 Ez mégsem kell, mert eleve már csak új felvitelben ellenőrizzük, szerkesztésnél nem kell újból
                            if( _get(\'uj\') ) {
                                if( !form_ell_admin() ) {
                                    return false;
                                }
                            } else {
                                if( !form_ell_admin(\'-mentes_nelkul-\') ) {
                                    return false;
                                }
                            }*/

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
    $szamla_html .= nl2('
       <script>
            $(document).ready(function () {
                $(\'#kcs_21_autocomp\').attr(\'disabled\', true);

                $(document).on(\'change\', \'#tipus\', function(){
                    if( $(this).val() != \'\' ) {
                        $(\'#kcs_21_autocomp\').attr(\'disabled\', false);
                    } else {
                        $(\'#kcs_21_autocomp\').attr(\'disabled\', true);
                    }
                });
            });
       </script>
    ');

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
                
                /*
                Ilyet mégse tegyünk... elég az eszkozigenyles.js -jsben levő, $(document).on(\'input\'... rész. Itt fontos, hogy betöltéskor azt mutassuk, amit az adatbázis.
                nettokat_atszamol();
                */

                /* Pár textarea readonly attribútumát cseréljük le disabled-re. A különbség az, hogy disabled esetében a benne levő szöveg részben is kijelölhető lesz, míg readonly-nál csak egyben jelölhető ki belekattintva. */
                [\'spec\', \'igeny_indok\', \'megj\'].forEach(id => {
                    const $el = $(\'#\' + id);
                    if ($el.prop(\'readonly\')) {
                        $el.prop({ readonly: false, disabled: true });
                    }
                });
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

// Tételek
} elseif( isset($_GET['tetel']) ) {

    $deviza = '';
    if( $sor['kcs_21'] != KCS_21_HUF ) {
        $deviza = ' ('.erteklista(ERTEKLISTA_PENZNEM, $sor['kcs_21']).')';
    }
    $tizedesek = 0;
    if( $sor['kcs_21'] != KCS_21_HUF ) {
        $tizedesek = 2;
    }

    $szamla_html .= nl2('
        <div class="dt naplo">
            <div class="dtr nFejlec">
                <div class="dtc">Név</div>
                <div class="dtc">Specifikáció</div>
                <div class="dtc">Cikkszám</div>
                <div class="dtc r">Igényelt menny.</div>
                <div class="dtc r">Igényelt egységár'.$deviza.'</div>
                <div class="dtc r">Igényelt nettó'.$deviza.'</div>
                <div class="dtc r">Jóváhagyott menny.</div>
                <div class="dtc r">Jóváhagyott egységár'.$deviza.'</div>
                <div class="dtc r">Jóváhagyott nettó'.$deviza.'</div>
            </div>
    ');

    // Tételek felsorolása
    if( $_GET['id'] ) {
        $tetelek = $adatbazis_kapcsolat->s('*', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$_GET['id']}'", 'recid');
        foreach ( $tetelek as $tetel ) {

            $igar    = $tetel['igar'];
            $ignetto = $tetel['ignetto'];
            $jhar    = $tetel['jhar'];
            $jhnetto = $tetel['jhnetto'];
            if( $sor['kcs_21'] != KCS_21_HUF && $sor['arfolyam'] > 0 ) {
                $igar    = $igar/$sor['arfolyam'];
                $ignetto = $ignetto/$sor['arfolyam'];
                $jhar    = $jhar/$sor['arfolyam'];
                $jhnetto = $jhnetto/$sor['arfolyam'];
            }

            $szamla_html .= nl2('
                <div class="dtr nSor">
                    <div class="dtc">'.htmlspecialchars_decode($tetel['nev']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['spec']).'</div>
                    <div class="dtc">'.htmlspecialchars_decode($tetel['csz']).'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['igmen'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($igar,    $tizedesek, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($ignetto, $tizedesek, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($tetel['jhmen'], 0, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($jhar,    $tizedesek, ',', ' ').'</div>
                    <div class="dtc r nowrap">'.number_format($jhnetto, $tizedesek, ',', ' ').'</div>
                </div>
            ');
        }
    }
    $szamla_html .= '</div>';

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
function megrendeles_keszit($eszkozigenyles_id, $tevezett = 0) {

    session_write_close();

    global $adatbazis_kapcsolat;
    global $szazalek_fajl;

    $db_error = false;

    // Így tudod kikapcsolni a warningokat és hibaüzeneteket:
    // error_reporting(0); 
    include('../mpdf/mpdf.php');

    require('selectek.php');

    $eszkozig_oszlopok = 'recid tipus stat_id rend_szam csz nev igmen jhmen arfolyam netto jhar jhossz telephely_id szallito_id szall_hat fiz_mod hat_ido ossz_netto ossz_dev kcs_21 kiszall beuzemel munkadij egyeb_koltseg eloleg megj insert_dt';

    // Group by-ozzunk szálloda és szállító alapján, mert tervezett igényléseknél ezek alapján kell létrehozni egy-egy PDF-et.
    // Soron kívüli + GM esetén sem gond a group by, egy sor marad benne
    if( $tevezett ) {
        $where = "`recid` IN (".implode(', ', $eszkozigenyles_id).")";
    } else {
        $where = "`recid`='{$eszkozigenyles_id}'";
    }
    $telephely_szallitok = $adatbazis_kapcsolat -> s('telephely_id szallito_id', 'eszkozigenyles', $where, '', '', "`telephely_id`, `szallito_id`");
    $osszes_sor = count($telephely_szallitok);
    $dok_szama = 0;
    $sor_i = 0;
    foreach ($telephely_szallitok as $telephely_szallito) {
        
        // Tervezett esetben nem csak egy igénylést, hanem egy egész tömböt kell feldolgozni
        if( $tevezett ) {
            $eszkozigenylesek = $adatbazis_kapcsolat -> s($eszkozig_oszlopok, 'eszkozigenyles', "`telephely_id`='{$telephely_szallito['telephely_id']}' AND `szallito_id`='{$telephely_szallito['szallito_id']}' AND `recid` IN (".implode(', ', $eszkozigenyles_id).")");
            if( $eszkozigenylesek === false ) { $db_error = true; }

        // Soron kívüli / GM-es eset. Itt nem is kell a $telephely_szallito feltétel bele, úgy is csak egy sor
        } else {
            $eszkozigenylesek = $adatbazis_kapcsolat -> s($eszkozig_oszlopok, 'eszkozigenyles', "`recid`='{$eszkozigenyles_id}'");
            if( $eszkozigenylesek === false ) { $db_error = true; }
        }

        // Státusz és jogok ellenőrzése (nehogy sima link beírással hozzanak létre megrendelést)
        if(

            // Soron kívüli + GM-es igénylések:
            // Ekkor az $eszkozigenyles-ben egy konkrét sor van

            // BI validálás + validálásra vár státusz + GM terhes típus esetén készül megrendelés
            ( !$tevezett && _jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS ) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_BI_VALID_VAR && $eszkozigenylesek[0]['tipus'] == BESZ_TIP_GM_KERET   ) ||
            // BI validálás + validálásra vár státusz + GM terhes típus esetén készül megrendelés
            ( !$tevezett && _jog(JOG_BESZERZ_IGENYLESEK_BI_VALIDALAS ) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_BI_JH_VAR && $eszkozigenylesek[0]['tipus'] == BESZ_TIP_GM_KERET      ) ||
            // Felsővezetői jóváhagyás + felsővezetői jh vár státuszkor készül megrendelés, ha nem tervezett típus
            ( !$tevezett && _jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR && $eszkozigenylesek[0]['tipus'] != BESZ_TIP_TERVEZETT    ) ||
            // BI véglegesít + véglegesítésre vár státuszkor készül megrendelés, ha nem tervezett típus
            ( !$tevezett && _jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR && $eszkozigenylesek[0]['tipus'] != BESZ_TIP_TERVEZETT   ) ||

            // Tervezett igénylések:
            // Ekkor az $eszkozigenyles-ben egy tömb van

            // Felsővezetői jóváhagyás + Felsővezetői jh vár agy BI véglegesítés státuszkor készül megrendelés, ha tervezett típus
            ( $tevezett &&  (
                                ( _jog(JOG_BESZERZ_IGENYLESEK_FELSOVEZ_JH  ) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_FELSO_JH_VAR) ||
                                ( _jog(JOG_BESZERZ_IGENYLESEK_BI_VEGLEGESIT) && $eszkozigenylesek[0]['stat_id'] == ESZKOZIGENYL_STAT_BI_VEGL_VAR )
                            )
            )                                                                                                                                      ||

            // Tesztelés:
            isset($_GET['pdf_mutat']) || isset($_GET['html_mutat'])
        ) {

            // Ha csak a HTML szerkesztése miatt tesztelünk, akkor ne hozzunk létre megrendelést
            if( !isset($_GET['html_mutat']) && !isset($_GET['pdf_mutat']) ) {

                $tetelek_html    = '';
                $mindossz        = 0;
                $ossz_netto_megr = 0;
                $ossz_dev_megr   = 0;
                foreach ($eszkozigenylesek as $eszkozigenyles) {

                    // Az igényléshez tartozó tétel-sorok lekérése. A megrendelő PDF-en soronként ezek jelennek meg, nem az összevont igénylés-fej.
                    $eszkozig_tetelek = $adatbazis_kapcsolat -> s('*', 'eszkozigenyles_tetel', "`eszkozigenyles_id`='{$eszkozigenyles['recid']}'", 'recid');
                    if( $eszkozig_tetelek === false ) { $db_error = true; $eszkozig_tetelek = []; }

                    // Tételenként: ha nem lett volna megadva a jóváhagyott mennyiség, akkor az igényelt adatokat másoljuk be
                    $jhossz = 0;
                    foreach ( $eszkozig_tetelek as &$eszkozig_tetel ) {
                        if( !$eszkozig_tetel['jhmen'] ) {
                            $eszkozig_tetel['jhmen']   = $eszkozig_tetel['igmen'];
                            $eszkozig_tetel['jhar']    = $eszkozig_tetel['igar'];
                            $eszkozig_tetel['jhnetto'] = $eszkozig_tetel['igmen']*$eszkozig_tetel['igar'];
                            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_tetel', array('jhmen',                 'jhar',                 'jhnetto'                ),
                                                                                        array($eszkozig_tetel['jhmen'], $eszkozig_tetel['jhar'], $eszkozig_tetel['jhnetto']),
                                                                            "`recid`='{$eszkozig_tetel['recid']}'");
                            if( $sql === false ) { $db_error = true; }
                        }
                        $jhossz += $eszkozig_tetel['jhnetto'];
                    }
                    unset($eszkozig_tetel);

                    $mezok      = array('jhossz');
                    $ertekek    = array($jhossz);
                    // A fej Jóváhagyott mennyiség/egységár mezője csak akkor tükröz tétel-értéket, ha pontosan 1 tétel van (több tételnél összesítő, üres)
                    if( count($eszkozig_tetelek) == 1 ) {
                        array_push($mezok,   'jhmen',                          'jhar'                          );
                        array_push($ertekek, $eszkozig_tetelek[0]['jhmen'],    $eszkozig_tetelek[0]['jhar']    );
                        $eszkozigenyles['jhmen'] = $eszkozig_tetelek[0]['jhmen'];
                        $eszkozigenyles['jhar']  = $eszkozig_tetelek[0]['jhar'];
                    }
                    $eszkozigenyles['jhossz'] = $jhossz;

                    // HUF esetén $ossz_netto és $ossz_dev számítás
                    if( $eszkozigenyles['kcs_21']==KCS_21_HUF ) {
                        $ossz_netto = $eszkozigenyles['jhossz'];
                        $ossz_dev   = 0;
                        if( $eszkozigenyles['kiszall'] > 0 ) {
                            $ossz_netto += $eszkozigenyles['kiszall'];
                        }
                        if( $eszkozigenyles['beuzemel'] > 0 ) {
                            $ossz_netto += $eszkozigenyles['beuzemel'];
                        }
                        if( $eszkozigenyles['munkadij'] > 0 ) {
                            $ossz_netto += $eszkozigenyles['munkadij'];
                        }
                        if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                            $ossz_netto += $eszkozigenyles['egyeb_koltseg'];
                        }

                    // Ha nem HUF $ossz_netto és $ossz_dev számítás
                    } else {
                        $ossz_dev = $eszkozigenyles['jhossz']/$eszkozigenyles['arfolyam'];
                        if( $eszkozigenyles['kiszall'] > 0 ) {
                            $ossz_dev += $eszkozigenyles['kiszall'];
                        }
                        if( $eszkozigenyles['beuzemel'] > 0 ) {
                            $ossz_dev += $eszkozigenyles['beuzemel'];
                        }
                        if( $eszkozigenyles['munkadij'] > 0 ) {
                            $ossz_dev += $eszkozigenyles['munkadij'];
                        }
                        if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                            $ossz_dev += $eszkozigenyles['egyeb_koltseg'];
                        }
                        $ossz_netto = $ossz_dev*$eszkozigenyles['arfolyam'];
                    }
                    $ossz_netto_megr += $ossz_netto;
                    $ossz_dev_megr   += $ossz_dev;

                    // Tételek összeszedése: soronként a tényleges tétel-sorok kerülnek a megrendelő PDF-re, nem az összevont igénylés-fej
                    $penznem    = getErtekLista(ERTEKLISTA_PENZNEM, $eszkozigenyles['kcs_21']);
                    $tizedesek  = 0;
                    if( $eszkozigenyles['kcs_21']!=KCS_21_HUF ) {
                        $tizedesek  = 2;
                    }

                    foreach ( $eszkozig_tetelek as $eszkozig_tetel ) {

                        $tetel_jhar    = $eszkozig_tetel['jhar'];
                        $tetel_jhnetto = $eszkozig_tetel['jhnetto'];
                        if( $eszkozigenyles['kcs_21']!=KCS_21_HUF ) {
                            $tetel_jhar    = $tetel_jhar/$eszkozigenyles['arfolyam'];
                            $tetel_jhnetto = $tetel_jhnetto/$eszkozigenyles['arfolyam'];
                        }
                        $mindossz += $tetel_jhnetto;

                        $tetel_neve = $eszkozig_tetel['nev'];
                        if( $eszkozig_tetel['csz'] ) {
                            $tetel_neve .= ', cikkszám: '.$eszkozig_tetel['csz'];
                        }

                        $tetelek_html .= '
                                        <tr style="border-top: 1px solid #ddd;">
                                            <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: left; vertical-align: top;">'.$tetel_neve.'</td>
                                            <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($eszkozig_tetel['jhmen'], 0, '', ' ').'</td>
                                            <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($tetel_jhar,    $tizedesek, ',', ' ').'</td>
                                            <td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right; vertical-align: top;">'.number_format($tetel_jhnetto, $tizedesek, ',', ' ').'</td>
                                        </tr>
                                    ';
                    }

                    // Fej-szintű extra költségek (kiszállítás / beüzemelés / munkadíj / egyéb) és a kiegészítő megjegyzés a tétel-sorok után, önálló sorokban
                    if( $eszkozigenyles['kiszall'] > 0 ) {
                        $tetelek_html .= '<tr style="border-top: 1px solid #ddd;"><td colspan="3" style="border: 1px solid #ddd; padding: 2px;">Kiszállítási költség</td><td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right;">'.number_format($eszkozigenyles['kiszall'], $tizedesek, ',', ' ').'</td></tr>';
                        $mindossz += $eszkozigenyles['kiszall'];
                    }
                    if( $eszkozigenyles['beuzemel'] > 0 ) {
                        $tetelek_html .= '<tr style="border-top: 1px solid #ddd;"><td colspan="3" style="border: 1px solid #ddd; padding: 2px;">Beüzemelési költség</td><td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right;">'.number_format($eszkozigenyles['beuzemel'], $tizedesek, ',', ' ').'</td></tr>';
                        $mindossz += $eszkozigenyles['beuzemel'];
                    }
                    if( $eszkozigenyles['munkadij'] > 0 ) {
                        $tetelek_html .= '<tr style="border-top: 1px solid #ddd;"><td colspan="3" style="border: 1px solid #ddd; padding: 2px;">Munkadíj</td><td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right;">'.number_format($eszkozigenyles['munkadij'], $tizedesek, ',', ' ').'</td></tr>';
                        $mindossz += $eszkozigenyles['munkadij'];
                    }
                    if( $eszkozigenyles['egyeb_koltseg'] > 0 ) {
                        $tetelek_html .= '<tr style="border-top: 1px solid #ddd;"><td colspan="3" style="border: 1px solid #ddd; padding: 2px;">Egyéb költség</td><td class="c" style="border: 1px solid #ddd; padding: 2px; text-align: right;">'.number_format($eszkozigenyles['egyeb_koltseg'], $tizedesek, ',', ' ').'</td></tr>';
                        $mindossz += $eszkozigenyles['egyeb_koltseg'];
                    }
                    if( $eszkozigenyles['megj'] ) {
                        $tetelek_html .= '<tr style="border-top: 1px solid #ddd;"><td colspan="4" style="border: 1px solid #ddd; padding: 2px;">'.nl2br2($eszkozigenyles['megj']).'</td></tr>';
                    }

                    $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', $mezok, $ertekek, "`recid`='{$eszkozigenyles['recid']}'");
                    if( $sql === false ) { $db_error = true; }

                }

                // Szállító email címének lekérése (ha tervezetten egy sor volt, ha egy egész tömb, az utolsó igénylésben biztosan jó szallito_id van)
                $szall_mail = getErtek('email', 'szallito_beszerzes', 'recid', $eszkozigenyles['szallito_id']);

                // Ha létezik a rend_szam, akkor ilyen számon jöjjön létre a megrendelő (lehet egy létező megrendelést javítottak - azaz törlésre kerül - majd ugyanazon a számon kell létrehozni )
                // 2025.12.10 Ez a funkció kikapcsolásra került. Tehát megrendelő javításakor a rend_szam minden esetben törlésre kerül, így nem lesz olyan eset, hogy itt ezen a ponton ez egy ismert adat legyen.
                if( $eszkozigenyles['rend_szam'] ) {
                    $rend_szam = $eszkozigenyles['rend_szam'];
                } else {
                    $rend_szam = get_iktatoszam_sorszam('eszkozigenyles_megr');
                    if($rend_szam===false){ $db_error = true; }
                    $rend_szam = 'TE/'.date('Y').'/'.$rend_szam;
                }
                $rend_szam_fajlba = str_replace('/', '', $rend_szam);

                // Hash készítése a megrendeléshez
                $hash = jelszot_keszit();
                $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
                if( $foglalt === false ) { $db_error = true; }
                while($foglalt) {
                    $hash = jelszot_keszit();
                    $foglalt = $adatbazis_kapcsolat -> s2('recid', 'eszkozigenyles_megr', "`hash`='{$hash}'");
                    if( $foglalt === false ) { $db_error = true; }
                }

                // Megrendeléshez az utolsó igénylés adatai íródnak. Szállod/szállító rendben is lesz, határidő lehet eltérő
                $megr_recid = $adatbazis_kapcsolat -> i('eszkozigenyles_megr',  array('hash', 'rend_szam', 'telephely_id',                  'szallito_id',                  'szall_mail', 'szall_hat',                  'ossz_netto',       'ossz_dev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                                array($hash,  $rend_szam,  $eszkozigenyles['telephely_id'], $eszkozigenyles['szallito_id'], $szall_mail,  $eszkozigenyles['szall_hat'], $ossz_netto_megr,   $ossz_dev_megr, $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
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

                // Rendszám beírása az igénylésekhez
                if( $tevezett ) {
                    $where = "`telephely_id`='{$telephely_szallito['telephely_id']}' AND `szallito_id`='{$telephely_szallito['szallito_id']}' AND `recid` IN (".implode(', ', $eszkozigenyles_id).")";
                } else {
                    $where = "`recid`='{$eszkozigenyles_id}'";
                }
                $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('rend_szam'), array($rend_szam), $where);
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
            if( $kontakt['usermail'] ) {
                $usertel = ', mail.: '.$kontakt['usermail'];
            }

            $szallito = $adatbazis_kapcsolat->s2('nev knev tel email', 'szallito_beszerzes', "`recid`='{$eszkozigenyles['szallito_id']}'");
            if( $szallito === false ) { $db_error = true; }

            // Ha nincs kapcsolattartó név, akkor "Partnerünk" a megszólítás
            if( !$szallito['knev'] ) {
                $szallito['knev'] = 'Partnerünk';
            }
            // 2025.10.04: kifejezetten fixen ezt szeretnék látni és nem a kapcsolattartó nevét
            // 2025.11.04: Tibi kérte, hogy mégis a kapcsolattartó neve kerülön oda, ha ki van töltve, sőt ott is csak az első vesszőig.
            // $szallito['knev'] = 'Partnerünk';
            $szallito['knev'] = explode(',', $szallito['knev'])[0];

            $telephely = $adatbazis_kapcsolat->s2('nev props', 'telephely', "`id`='{$eszkozigenyles['telephely_id']}'");
            if( $telephely === false ) { $db_error = true; }
            $props_szn      = get_props($telephely['props'], 'szn'      );
            $props_cim      = get_props($telephely['props'], 'cim'      );
            $props_szallcim = nl2br2(get_props($telephely['props'], 'szallcim' ));

            $fizmod_kiir = '';
            foreach ($select_fiz_mod as $fizmod) {
                if( $eszkozigenyles['fiz_mod'] && $fizmod['value'] == $eszkozigenyles['fiz_mod'] ) {
                    $fizmod_kiir = $fizmod['name'];
                }
            }

            $hat_ido_kiir = '';
            foreach ($select_hat_ido as $hat_ido) {
                if( $eszkozigenyles['hat_ido'] && $hat_ido['value'] == $eszkozigenyles['hat_ido'] ) {
                    $hat_ido_kiir = $hat_ido['name'];
                }
            }
        
            // A Fizetési mód és határidő sor csak akkor jelenik meg, ha van legalább egyik adat
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
                            '.$hat_ido_kiir.'
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
                        <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 88px;">Mennyiség<br>(db)</th>
                        <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 118px;">Nettó egységár<br>('.$penznem.')</th>
                        <th style="border: 1px solid #ddd; padding: 2px; text-align: right; width: 118px;">Nettó összesen<br>('.$penznem.')</th>
                    </tr>
                    '.$tetelek_html.'
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
            if( !is_dir('../'.get_utvonal().'eszkozigenyles_megr/'.$ev.'/'.$megr_recid) ) {
                mkdir('../'.get_utvonal().'eszkozigenyles_megr/'.$ev.'/'.$megr_recid);
            }

            $mpdf -> Output(iconv('UTF-8', 'Windows-1250', '../'.get_utvonal().'eszkozigenyles_megr/'.$ev.'/'.$megr_recid.'/'.$fajlnev), 'F');
            $utvonal_db_be = $ev.'/'.$megr_recid.'/'.$fajlnev;
            $dok_szama++;

            // Megrendelések dokumentumához is adjuk hozzá
            $doc_recid = $adatbazis_kapcsolat -> i('eszkozigenyles_megr_dok',   array('eszkozigenyles_megr_id', 'utvonal',      'fajlnev', 'megr', 'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                                array($megr_recid,              $utvonal_db_be, $fajlnev,  1,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                    );
            if( $doc_recid === false ) { $db_error = true; }

            // Biztosan csak egy dokumentuma lesz, hisz most jött létre
            $sql = $adatbazis_kapcsolat -> u('eszkozigenyles_megr', array('dok_db'), array('1'), "`recid`='{$megr_recid}'");
            if( $sql === false ) { $db_error = true; }

            // Eszközigénylés dokumentumához is adjuk hozzá
            $eszkozigenylesek_akt = $adatbazis_kapcsolat -> s('recid dok_db', 'eszkozigenyles', "`rend_szam`='{$rend_szam}'");
            if( $eszkozigenylesek_akt === false ) { $db_error = true; }
            foreach ($eszkozigenylesek_akt as $eszkozigenyles_akt) {
                $sql = $adatbazis_kapcsolat -> i('eszkozigenyles_dok',  array('eszkozigenyles_id',          'utvonal',                                'fajlnev',     'update_usr',                           'insert_dt',    'insert_usr'                            ),
                                                                        array($eszkozigenyles_akt['recid'], '../eszkozigenyles_megr/'.$utvonal_db_be, $fajlnev,      $_SESSION[get_db_tabla().'teljes_nev'], 'NOW()',        $_SESSION[get_db_tabla().'teljes_nev']  )
                                        );
                if( $sql === false ) { $db_error = true; }
                $sql = $adatbazis_kapcsolat -> u('eszkozigenyles', array('dok_db'), array($eszkozigenyles_akt['dok_db']+1), "`recid`='{$eszkozigenyles_akt['recid']}'");
                if( $sql === false ) { $db_error = true; }
            }

            // Nem működött, adatbázissal sem
            // Folyamat %-os értéke hol jár
            fajlba_mentes($szazalek_fajl, number_format(($sor_i / $osszes_sor) * 100, 0, '', ''));

            $sor_i++;

        } else {
            $db_error = true;
        }
    }

    // 100% jelzése
    fajlba_mentes($szazalek_fajl, '100');

    if( $db_error ) {
        return false;
    } else {
        return true;
    }
}