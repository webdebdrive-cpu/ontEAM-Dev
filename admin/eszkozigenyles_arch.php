<?php
require_once('../weblap.php');

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

$igenyles_mezok_pdfhez = 'recid tipus stat_id nev igmen jhmen arfolyam netto jhar jhossz telephely_id szallito_id szall_hat fiz_mod hat_ido ossz_netto ossz_dev kcs_21 kiszall beuzemel munkadij egyeb_koltseg eloleg megj insert_dt'; // Ezeket az oszlopokat kérjük le PDF készítéséhez

// Cikkszám ha nincs megadva, egyszer kérdezzünk rá.
$szamla_html = '<script>var CSZ_KERDES_VOLT = 0;</script>';

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
        return 'eszkozigenyles_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Megjegyzések kiíratása
if (isset($_POST['megj_kiir'])) {

    echo json_encode( array('html' => megj_kiir('eszkozigenyles', $_POST['id'], $_POST['rendszer'])) );

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

            $_SESSION['eszkozigenyles_arch_tablazat_rendez'] = $mentett_rendezes['eszkozigenyles_arch_tablazat_rendez'] = $_POST['oszlop'];
            $_SESSION['eszkozigenyles_arch_tablazat_irany' ] = $mentett_rendezes['eszkozigenyles_arch_tablazat_irany' ] = $_POST['irany' ];

            $adatbazis_kapcsolat -> u('login', array('tabla_rendezes'), array(json_encode($mentett_rendezes)), "`recid`='".$_SESSION[get_db_tabla().'login_recid']."'");

        }
    }

    // Nem biztos, hogy küldünk oszlop adatot, lehet csak az irány változik
    if( $_POST['oszlop'] ) {
        $_SESSION['eszkozigenyles_arch_tablazat_rendez'] = $_POST['oszlop'];
    }
    if( $_POST['irany'] ) {
        $_SESSION['eszkozigenyles_arch_tablazat_irany' ] = $_POST['irany'];
    }
    
    // CSV exportban is jó legyen a sorrend ($_SESSION['akt_sql']), ezért hívjuk meg:
    szamla_lista('', 'eszkozigenyles_arch', '', '', $_GET);

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

// CSV generálása
if( isset($_GET['csv']) ) {

    require_once('selectek.php');
    
    // CSV export előkészítése
    list($mibol, $mibe, $filename, $open) = csv_export_elokeszit($megnevezes);
    if( $open ) {

        // Rendezés kiszedése $_SESSION['akt_sql']-ből
        $order = '';
        preg_match('/ORDER BY .*?(?=LIMIT|$)/is', $_SESSION['akt_sql'], $matches);
        if (!empty($matches)) {
            $order = trim($matches[0]);
        }

        // Ha van LIMIT a $_SESSION['akt_sql']-ben, akkor azt itt is figyelembe kell venni
        if( strpos( $_SESSION['akt_sql'], 'LIMIT' )!==false ) {
            $limit = trim( substr( $_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'LIMIT') ), 'LIMIT ' );
            if( strpos($limit, ' ') ) {
                $limit = substr($limit, 0, strpos($limit, ' '));
            }
            $limit = " LIMIT {$limit}";
        }
        
        $sql = 'SELECT
                `stat_id` as \'Státusz\',
                `'.get_db_tabla().'eszkozigenyles_arch`.`azon` as \'Azonosító\',
                `idoszak_azon` as \'Időszak ID\',
                `tipus_txt` as \'Típus\',
                `prior_txt` as \'Prioritás\',
                `telephely_txt` as \'Szálloda\',
                `reszleg_txt` as \'Részleg\',
                `'.get_db_tabla().'eszkozigenyles_arch`.`nev` as \'Termék név\',
                `csz` as \'Cikkszám\',
                `spec` as \'Specifikáció\',
                `igeny_indok` as \'Igénylés indoka/koncepció\',
                `megj` as \'Megjegyzés\',
                `szallito_txt` as \'Szállító\',
                `szall_hat` as \'Szállítási határidő:\',
                `fiz_mod_txt` as \'Fizetési mód\',
                `hat_ido_txt` as \'Fizetési határidő\',
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
                csv_export_get_from_tol($megnevezes).$order.$limit;
        $query = mysqli_query($db_kapcs, $sql);

        // Nagyobb CSV-kre kérdezzünk rá előzetesen
        $osszes_sor = mysqli_num_rows($query);
        if( !$_GET['mehet'] && $osszes_sor>CSV_EXPORT_LIMIT ) {
            $gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#navGombok .excel\').attr(\'onclick\', \'csv_general( this, \\\'\\\', \\\'&mehet=1\\\');\'); $(\'.navGombok a.excel\').click();"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">';
            echo json_encode(array('hiba' => 'A CSV export több mint '.number_format(CSV_EXPORT_LIMIT, 0, '', ' ').' sort tartalmaz. Ennek előállítása hosszabb időbe telik majd.<br>Biztosan folytatja?', 'gomb' => $gomb));
            $weblap -> leallit();
            exit;
        }

        $query      = mysqli_query($db_kapcs, $sql);
        $csvhead    = array();
        while ( $fejlec_nevek = mysqli_fetch_field($query) ) {
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        $tort_szamok = ['Árfolyam', 'Deviza egységár', 'Kiszállítási költség', 'Beüzemelési költség', 'Munkadíj', 'Egyéb költség', 'Kivitelezési költség'];
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

                if( $kulcs == 'Pénznem' ) {
                    $sor = erteklista(ERTEKLISTA_PENZNEM, $sor);
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

    csv_export_lezar($fh, $filename, 'Archív Eszközigénylések');

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

// Gombok kirajzolása minden oldalra
$gombok = '';
if( isset($_GET['uj']) ) {

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

    // Műveletek box
    $gombok = nl2('
        <div class="muveletek megis_mindig_latszik"'.$muv_style.'>
            '.$gombok.'
        </div>
    ');
}

// Új eszközigénylés felvitele
if( isset($_GET['uj']) ) {


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

    // Mivel artchiv felületen vagyunk, ezért a mezők típusát és nevét átírjuk ha kell
    // Erre azért van szükség, hogy pl. egy atocompletes mezőből txt legyen, hogy az értékét ne más táblákból olvassa vissza
    // hanem az adatbázisban letároltat
    $i = 0;
    foreach ( $akt_tomb as $tomb ) {
        if( $tomb['type_arch'] ) {
            $akt_tomb[$i]['type'] = $tomb['type_arch'];
            $akt_tomb[$i]['name'] = $tomb['name_arch'];
        }
        $i++;
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
                Ilyet már ne csináljunk, szigorúan azt mutatjuk, ami az adatbázisban van.
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
        $tetelek = $adatbazis_kapcsolat->s('*', 'eszkozigenyles_tetel_arch', "`eszkozigenyles_id`='{$_GET['id']}'", 'recid');
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
    $szamla_html .= megj_kiir('eszkozigenyles', $_GET['id']);

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

        $sorok = $adatbazis_kapcsolat->s('recid megj user insert_usr insert_dt', 'megjegyzes_arch', "`id`='{$id}' AND `tabla`='{$tabla}'{$sql_kieg}", 'recid desc');
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