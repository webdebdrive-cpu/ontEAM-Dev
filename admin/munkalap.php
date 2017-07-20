<?php
require_once('../weblap.php');
//Config

$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $munkalap_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'munkalapot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$gyorskereso_text       = '';       //Gyorskereső felirat. Ha üres akkor nincs gyorskeresés
$oszlop_keres           = 'nev';                //Oszlopnevek, melyben keresünk (több oszlop is felsorolható vesszőkkel)
$oszlop_listaz          = 'nev';                //Oszlopnevek, melyeket kilizátunk (több oszlop is felsorolható vesszőkkel)
$gyorskereso_id         = 'ksz';                //Gyorskereső input neve és ID-ja

//Változók beállítása
$csak_szerkesztheto     = false;                //Ha tiltani szeretnéd, hogy újat tudjon feltölteni, vagy törölni adott bejegyzést
$elonezet               = '';                   //Ha üres nincs előnézet. Egyébként meg az előnézet URL-jét add meg ide, pl.: '../index.php?id='.$_GET['id']
$fajloszlop             = 'kep';                //Kép (vagy kivételes esetekben más fájl) oszlopneve.
$id_latszik             = false;                //A termék ID-ja látsszon-e a felsorolásnál, kibontásnál (van, hogy ügyfél is használja)
$kep_thumb_listaba      = false;                //Lista nézetben legyen-e a képoszlop alapján képthumbnail
$magas                  = get_indexkep_m();     //Kiskép magassága
$sorrendezes            = false;                //Ha sorrendet is be szeretnénk állítani. Adatbázisban sor oszlopot létre kell hozni, jquery-ui.js-t és admin_sorrendez.js-t aktiválni az adminhoz
$szel                   = get_indexkep_sz();    //Kiskép szélessége
$ujkent_szerkesztheto   = true;                 //Szerkesztésnél elérhető egy gomb, melyre kattintva a termékadatai átkerülnek az új feltöltésbe

$ckeditor_js            = '../'.get_utvonal().'wysiwyg/ckeditor.js';    //Ckeditor JS útvonala
$formazatlan_szam       = array('nev', 'csz', 'tel', 'fax', 'mobil');          //Azok az oszlopneveket sorold fel, melyek numerikus értéket is tartalmazhatnak, de nem szeretnéd, ha lista nézetben ezres csoportra bontaná

$szamla_html = '';

//Több kép menedzseléséhez
$tobb_kep = true; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;         } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'false';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;         } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return 'munkalap_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

    //Kép törlésének lekezelése
    if (isset($_GET[$torles.'tobbkep_torol'])){

        $tobbkep = $adatbazis_kapcsolat -> s('kepnev', get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);
        foreach($tobbkep as $sor_tobbkep){

            //eredeti fájl törlése
            if(is_file('../'.get_utvonal().$sor_tobbkep['kepnev'])){
                unlink('../'.get_utvonal().$sor_tobbkep['kepnev']);
            }

            //ha van kiterjesztése
            if(strrpos($sor_tobbkep['kepnev'], '.')>0){
                remover('../'.get_utvonal().substr($sor_tobbkep['kepnev'],0,strrpos($sor_tobbkep['kepnev'],'.')).'_*');
            }else{
                remover('../'.get_utvonal().$sor_tobbkep['kepnev'].'_*');
            }
        }

        $adatbazis_kapcsolat -> delete(get_tobbkep_tabla(), get_tobbkep_oszlopnev().'='.$_GET[$torles.'tobbkep_torol']);

        if(isset($_GET['noload'])){
            $weblap -> leallit();
            exit();
        }
    }

}

/* Átadás műszaki vezetőnek */
if( isset($_POST['atadasMuszVeznek']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            /* Frissítjük a munkalap tételt */
            $sql = $adatbazis_kapcsolat -> u('munkalap_tetel',
                array('atadta_dt',           'visszaadva', 'update_usr'                           ),
                array( date('Y-m-d H:i:s'),   1,           $_SESSION[get_db_tabla().'teljes_nev'] ),
                "`recid` = '".$akt_tetel['recid']."'"
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Frissítjuk a munkalap visszaadva sorát */
            $sql = $adatbazis_kapcsolat -> u('munkalap',
                array('visszaadva', 'szemelyzet_id', 'mobil_loginnev', 'update_dt',          'update_usr'                           ),
                array( 1,            5,              '',                date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] ),
                "`recid` = '{$recid}'"
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Folyamatban státuszra állítjuk a munkalapot */
            if( !addStatusz( 'munkalap_statusz', $recid, 2 ) ) {
                $db_error = true;
            }

            if( !$db_error ) {
                mysqli_commit($db_kapcs);

                echo json_encode(array(
                    'statusz' => 'siker'
                ));
            } else {
                mysqli_rollback($db_kapcs);

                echo json_encode(array(
                    'hiba' => tranzakcioHiba()
                ));
            }
        }
    } else {
        echo json_encode(array(
            'hiba' => 'Adja meg az átadás okát!'
        ));
    }

    $weblap->leallit();
    exit();
}

/* Munkalap anyagok */
if( isset($_POST['getMunkalapFeladatok']) ) {
    $recid = $_POST['recid'];

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( $recid ) {
        $sql = $akt_tetel = $adatbazis_kapcsolat->s('recid', 'munkalap_tetel', "`munkalap_id` = '{$recid}'", 'recid desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_tetel ) {
            $akt_tetel = $akt_tetel[0];

            $sql = $munkalapok = $adatbazis_kapcsolat->s('recid erteklista_id memo', 'munkalap_feladat', "`munkalap_id` = '{$recid}' AND `tetel_id` = '".$akt_tetel['recid']."'", '', '');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $munkalapok ) {
                $sorok = '';
                foreach($munkalapok as $munkalap) {
                    $sorok .= nl2('
                        <tr>
                            <td>'.erteklista(5, $munkalap['erteklista_id']).'<input type="hidden" name="addCikkID[]" value="'.$munkalap['cikk_id'].'"><input type="hidden" name="addCikkUj[]" value="0"></td>
                            <td>'.nl2br($munkalap['memo']).'</td>
                            <td><a onclick="munkFeladatTorol( \''.$munkalap['recid'].'\', this );"><span class="icon"><i class="material-icons">delete</i></span></a></td>
                        </tr>
                    ');
                }

                $html = nl2('
                    <table class="csikos">
                        <tr>
                            <th>Elvégzett feladat</th>
                            <th>Leírás</th>
                            <th class="torles"></th>
                        </tr>
                        '.$sorok.'
                    </table>
                ');

                if( !$db_error ) {
                    mysqli_commit($db_kapcs);

                    echo json_encode(array(
                        'statusz' => 'siker',
                        'html' => $html
                    ));
                } else {
                    mysqli_rollback($db_kapcs);

                    echo json_encode(array(
                        'hiba' => tranzakcioHiba()
                    ));
                }
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap átadása mobil csoportnak */
if( isset($_POST['munkalapMobil']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    /* Hozzárendelt csoport törlése */
    parse_str($_POST['munkMobilKivetel']);

    /* Hozzárendelt személyzet törlése */
    parse_str($_POST['munkHozzMegszuntet']);

    /* Törzsadat csatolása a munkalaphoz */
    parse_str($_POST['addTorzsID']);
    parse_str($_POST['addTorzsMunkID']);

    /*foreach($idk as $id) {
        $sql = $munkalap = $adatbazis_kapcsolat->s('recid ikt_szam statusz_id', 'munkalap', "`recid` = '{$id}'", '', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $munkalap  ) {
            $munkalap = $munkalap[0];

            if( !in_array($munkalap['statusz_id'], array(1)) ) {
                echo json_encode(array(
                    'hiba' => 'A(z) "'.$munkalap['ikt_szam'].'" iktatószámú munkalap státusz idő közben megváltozott!'
                ));

                $weblap->leallit();
                exit();
            }
        } else {
            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));

            $weblap->leallit();
            exit();
        }
    }*/

    if( is_array($idk) ) {
        $maradStatRecid = array();

        /* Hozzárendelt csoport törlése */
        if( count($munkMobilKivetel) ) {
            foreach($munkMobilKivetel as $value) {
                $maradStatRecid[] = $value;

                $sql = $munkalap = $adatbazis_kapcsolat->s('recid statusz_id', 'munkalap', "`recid` = '{$value}'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                        array('szemelyzet_id', 'mobil_loginnev', 'update_usr'                          ),
                        array( 0,              '',               $_SESSION[get_db_tabla().'teljes_nev']),
                        "`recid` = '{$value}'"
                    );

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    if( $munkalap['statusz_id'] != 1 ) {
                        if( !addStatusz( 'munkalap_statusz', $value, 1 ) ) {
                            $db_error = true;
                        }
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba()
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Kivesszük a hozzárendelt mobil_loginnev-et */
        if( count($munkHozzMegszuntet) ) {
            foreach($munkHozzMegszuntet as $value) {
                $sql = $munkalap = $adatbazis_kapcsolat->s('recid statusz_id', 'munkalap', "`recid` = '{$value}'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $adatbazis_kapcsolat -> u('munkalap',
                        array('mobil_loginnev', 'update_dt',          'update_usr'                          ),
                        array('',                date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev']),
                        "`recid` = '{$value}'"
                    );

                    if( $sql === false ) {
                        $db_error = true;
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba()
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        /* Törzsadat hozzárendelése munkalapokhoz */
        if( count($addTorzsMunkID) ) {
            foreach($addTorzsMunkID as $key => $value) {
                $sql = $munkalap = $adatbazis_kapcsolat->s('recid statusz_id', 'munkalap', "`recid` = '{$value}'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    $sql = $torzslap = $adatbazis_kapcsolat->s('recid felh_loginnev', 'torzsadat', "`recid` = '".$addTorzsID[$key]."'", '', '1');

                    if( $sql === false ) {
                        $db_error = true;
                    }

                    if( $torzslap ) {
                        $torzslap = $torzslap[0];

                        $sql = $adatbazis_kapcsolat -> u('munkalap',
                            array('szemelyzet_id', 'mobil_loginnev',           'update_usr'                          ),
                            array(2,               $torzslap['felh_loginnev'], $_SESSION[get_db_tabla().'teljes_nev']),
                            "`recid` = '{$value}'"
                        );

                        if( $sql === false ) {
                            $db_error = true;
                        }
                    } else {
                        echo json_encode(array(
                            'hiba' => tranzakcioHiba()
                        ));

                        $weblap->leallit();
                        exit();
                    }
                } else {
                    echo json_encode(array(
                        'hiba' => tranzakcioHiba()
                    ));

                    $weblap->leallit();
                    exit();
                }
            }
        }

        foreach($idk as $id) {
            if( !in_array($id, $maradStatRecid) ) {
                $sql = $munkalap = $adatbazis_kapcsolat->s('recid statusz_id', 'munkalap', "`recid` = '{$id}'", '', '1');

                if( $sql === false ) {
                    $db_error = true;
                }

                if( $munkalap ) {
                    $munkalap = $munkalap[0];

                    if( $munkalap['statusz_id'] != 2 ) {
                        if( !addStatusz( 'munkalap_statusz', $id, 2 ) ) {
                            $db_error = true;
                        }
                    }
                }
            }
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'beosztasNelkul' => $beosztasNelkul,
                'szemRendelt' => $szemRendelt
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

/* Munkalap sorok lekérdezése */
if( isset($_POST['hozzBeosztasNelkul']) ) {
    $idk = json_decode($_POST['idk'], true);

    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    if( is_array($idk) ) {
        foreach($idk as $id) {
            $sql = $statusz = $adatbazis_kapcsolat->s('statusz_id', 'munkalap', "`recid` = '{$id}'", '', '');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $statusz ) {
                $statusz = $statusz[0];

                if( !in_array($statusz['statusz_id'], array(1)) ) {
                    echo json_encode(array(
                        'hiba' => 'Kérem csak nyitott státuszú munkalapokat jelöljön meg!'
                    ));

                    $weblap->leallit();
                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => tranzakcioHiba()
                ));

                $weblap->leallit();
                exit();
            }
        }

        $osszes_sor = 0;

        /* Hozzárendelt munkalapok beosztás nélkül */
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id statusz_id surgos', 'munkalap', "(`szemelyzet_id` = '2' AND `statusz_id` = '2' AND `mobil_loginnev` = '') OR (`recid` IN (".implode(',', $idk).") AND `szemelyzet_id` = '0' AND `statusz_id` IN (1) AND `mobil_loginnev` = '')", '', '');

        $osszes_sor += count($munkalapok);

        if( $sql === false ) {
            $db_error = true;
        }

        $beosztasNelkul = '';
        $sorok = '';
        if( $munkalapok ) {
            $i = 1;
            foreach($munkalapok as $munkalap) {
                /* Telephely */
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                /* Sürgős e */
                $surgos = 'nem';
                if( $munkalap['surgos'] ) {
                    $surgos = 'igen';
                }

                /* Státusz */
                $statusz = '';
                if( $munkalap['statusz_id'] ) {
                    $statusz = $adatbazis_kapcsolat->s('recid nev', 'munkalap_statusz_text', "`recid` = '".$munkalap['statusz_id']."'", '', '1');

                    if( $statusz ) {
                        $statusz = $statusz[0];
                    }
                }

                $sorok .= nl2('
                    <tr class="kijSor" data-recid="'.$munkalap['recid'].'" data-i="'.$i.'">
                        <td class="nev"></td>
                        <td>'.$statusz['nev'].'</td>
                        <td>'.$munkalap['ikt_szam'].'</td>
                        <td>'.$telephely.'</td>
                        <td>'.$surgos.'</td>
                    </tr>
                ');

                $i++;
            }
        }

        $beosztasNelkul = nl2('
            <table class="csikos kicsi kijCont">
                <tr>
                    <th>Név</th>
                    <th>Mlap státusz</th>
                    <th>Ikt. szám</th>
                    <th>Szálloda</th>
                    <th>Sürgős</th>
                </tr>
                '.$sorok.'
            </table>
        ');

        /* Személyzethez rendelt munkalapok */
        $munkalapok = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id statusz_id mobil_loginnev surgos', 'munkalap', "`mobil_loginnev` != '' AND `szemelyzet_id` = '2' AND `statusz_id` IN (2, 8)", '', '');

        $osszes_sor += count($munkalapok);

        if( $sql === false ) {
            $db_error = true;
        }

        $szemRendelt = '';
        $sorok = '';
        if( $munkalapok ) {
            $i = 1;
            foreach($munkalapok as $munkalap) {
                /* Telephely */
                $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$munkalap['telephely_id'], '', '1');
                $telephely = $telephely[0]['nev'];

                /* Sürgős e */
                $surgos = 'nem';
                if( $munkalap['surgos'] ) {
                    $surgos = 'igen';
                }

                /* Státusz */
                $statusz = '';
                if( $munkalap['statusz_id'] ) {
                    $statusz = $adatbazis_kapcsolat->s('recid nev', 'munkalap_statusz_text', "`recid` = '".$munkalap['statusz_id']."'", '', '1');

                    if( $statusz ) {
                        $statusz = $statusz[0];
                    }
                }

                $sorok .= nl2('
                    <tr class="kijSor" data-recid="'.$munkalap['recid'].'" data-statid="'.$munkalap['statusz_id'].'" data-i="'.$i.'">
                        <td class="nev">'.$munkalap['mobil_loginnev'].'</td>
                        <td>'.$statusz['nev'].'</td>
                        <td>'.$munkalap['ikt_szam'].'</td>
                        <td>'.$telephely.'</td>
                        <td>'.$surgos.'</td>
                    </tr>
                ');

                $i++;
            }
        }

        $szemRendelt = nl2('
            <table class="csikos kicsi kijCont">
                <tr>
                    <th>Név</th>
                    <th>Mlap státusz</th>
                    <th>Ikt. szám</th>
                    <th>Szálloda</th>
                    <th>Sürgős</th>
                </tr>
                '.$sorok.'
            </table>
        ');

        if( !$osszes_sor ) {
            echo json_encode(array(
                'hiba' => 'Nincsenek megjeleníthető sorok!'
            ));

            $weblap->leallit();
            exit();
        }

        if( !$db_error ) {
            mysqli_commit($db_kapcs);

            echo json_encode(array(
                'statusz' => 'siker',
                'beosztasNelkul' => $beosztasNelkul,
                'szemRendelt' => $szemRendelt
            ));
        } else {
            mysqli_rollback($db_kapcs);

            echo json_encode(array(
                'hiba' => tranzakcioHiba()
            ));
        }
    }

    $weblap->leallit();
    exit();
}

if( isset($_POST['jogokhozzaad']) ) {
    $id = $_POST['id'];

    /* Kitöröljük a nem kívánatos POST elemeket */
    unset($_POST['id']);
    unset($_POST['jogokhozzaad']);

    /* Rendezzük a tömböt */
    ksort($_POST);

    $adatbazis_kapcsolat -> u('login', array('flags'), array(implode('', $_POST)), 'recid='.$id);

    $weblap->leallit();
    hd($_SERVER['REQUEST_URI']);

    exit();
}
/*
Ha true a config.php-ban:
js/+jquery-ui.js aktiválni a config.php-ban az ADMIN JS és CSS RÉSZBEN!!!
js/+admin_sorrendez.js aktiválni

Hozzá kell adni ezt a fájlt:
admin/+tobb_kep.php aktiválni

Minta SQL
CREATE TABLE IF NOT EXISTS `TABLA_kepek`(
  `id` int(10) unsigned NOT NULL auto_increment,
  `"TERMEK"ID` int(10) unsigned default NULL,
  `kepnev` varchar(100) NOT NULL default '',
  `megj` varchar(200) NOT NULL default '',
  `sor` int(11) NOT NULL default '0',
  `datetime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
)

Vízjelezéshez vissza kell kommentezni a +tobb_kep.php-ban a Képfeltöltésnél egy részt!
*/

/** Események */
if(isset($_GET['siker']) && isset($_GET['modosit'])){

}

/** Tartalom */
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

if($gyorskereso_text){
    $dl = nl2('
                <input type="text" value="'.htmlspecialchars($_GET['ksz']).'" name="'.$gyorskereso_id.'" id="'.$gyorskereso_id.'" placeholder="'.$gyorskereso_text.'" autocomplete="off">
                <script>
                    $(document).ready(function() {
                        $(\'#'.$gyorskereso_id.'\').autocomplete({
                            \'php_fajl\'        : \'../autocomplete.php\',
                            \'tabla\'           : \''.$megnevezes.'\',
                            \'oszlop_keres\'    : \''.$oszlop_keres.'\',
                            \'oszlop_listaz\'   : \''.$oszlop_listaz.'\'
                        });
                    });
                </script>
            ');
    $gyk_sablon  = fajlbol_olvas('../sablon/admin/gyorskereso.html',  true);
    $gyk_sablon2 = fajlbol_olvas('../sablon/admin/gyorskereso2.html', true);
    $gyk_mit     = array('{ACTION}', '{FELIRAT}',       '{DL}', '{EGYEB_DE_FORM_NEM_LEHET}');
    $gyk_mivel   = array(self(),     $gyorskereso_text, $dl);
    $gyk  = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon);
    $gyk2 = str_replace($gyk_mit, $gyk_mivel, $gyk_sablon2);
}

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['naptar']) || isset($_GET['bejelentesek']) || isset($_GET['tortenet']) || isset($_GET['anyagigenyles']) || isset($_GET['elszamolas']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['tetel']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tetel'         => 'Tételek',
            'tortenet'      => 'Történet',
            'bejelentesek'  => 'Bejelentések',
            'elszamolas'    => 'Elszámolás',
            'anyagigenyles' => 'Anyagigénylések',
            'dokumentumok'  => 'Dokumentumok',
            'megjegyzesek'  => 'Megjegyzések',
        );

        $fulek = '';
        foreach($ful_tomb as $key => $value) {
            $active = 'active';
            if( isset($_GET[$key]) ) {
                $active = ' class="active"';
            }

            $fulek .= '<a href="javascript: szamlaBetolt('.$_GET['id'].', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

    /* Gombok kirajzolása minden oldalra */
    $gombok = '';
    if( isset($_GET['uj']) ) {
        $disabled = '';
        $class = '';
        if( !$_GET['ujkent'] ) {
            $disabled = ' disabled';
        } else {
            $class = ' gombb_alert';
        }

        /* Gombok */
        $gombok = nl2('
            <div class="form_kuldes">
                <input type="submit" class="gombb'.$class.'" name="mehet_gomb" id="mehet_gomb"'.$disabled.' value="Mentés">
            </div>
        ');

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    } else {
        $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
        $sor = $sor[0];

        //Js, a törlés gombhoz
	    $gombok = '<script>var gomb'.$_GET['id'].'= get_kibont_torol(\''.self().'\', \''.$torles.'\', \''.$_GET['id'].'\');</script>';
	    if($elonezet){
           $gombok .= ' <input type="button" class="gombb" onclick="window.open(\''.$elonezet.'\')" value="Előnézet" title="Előnézet">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb" onclick="location.href=\''.self().'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        if( !$csak_szerkesztheto ) {
            //Csak diszpécser vagy diszpkp vezető törölhet
            if(!_jog(2) && !_jog(23)){
                $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Nincs jogosultsága munkalap törléséhez!\')" value="Töröl" title="Töröl">';
            }else{

                //Egyébként nézzük meg, hogy van-e tétel vagy bejelentés rajta. Ha van, akkor sem törölhető
                if($adatbazis_kapcsolat->s('id', 'munkalap_tetel', "`munkalap_id`='{$_GET['id']}'")){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'A munkalap nem törölhető, mert munkavégzéseket tartalmaz!\')" value="Töröl" title="Töröl">';
                }elseif($adatbazis_kapcsolat->s('recid ', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'")){
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Munkalap törlése előtt vegye le róla a bejelentéseket!\')" value="Töröl" title="Töröl">';
                }else{
                    $gombok .= ' <input type="button" id="recordTorol" class="gombb_alert dn" onclick="alert2(\'Biztosan törli ezt a '.$szoveg.'?\', gomb'.$_GET['id'].', \'Kérdés\')" value="Töröl" title="Töröl">';
                }
            }
        }

        if( isset($_GET['modosit']) ) {
            $gombok = nl2('
                <div class="form_kuldes">
                    <input type="submit" class="gombb" name="mehet_gomb" id="mehet_gomb" disabled value="Mentés">
                </div>
            ').$gombok;
        }

        /* Kijelöltekkel való művelet gombok */
        if( _jog(2) ) {
            $gombok .= nl2('
                <div id="kmCont">
                    <script>
                        csoportKiveszGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"csoportbolTorol(\'csoport_id\');\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                    </script>
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Csoportosítás..." title="Csoportosítás...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="csoportMuvelet(\'csoporthoz_adas\');">Csoporthoz adás</a></li>
                                <li><a onclick="alert2(\'Biztos kiveszi a kijelölt sorokat a megadott csoportokból?\', csoportKiveszGomb);">Csoportból kivétel</a></li>
                                <li><a onclick="csa_alert();">Csoport átnevezése</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Várólista..." title="Várólista...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="varolistahozAdas(\'csak kérdezünk még\');">Várólistához adás</a></li>
                                <li><a onclick="varolistabolKivetel(\'csak kérdezünk még\');">Várólistból kivétel</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="kijeloltek_muv">
                        <input type="button" class="gomb foGomb" value="Munka átadása..." title="Munka átadása...">
                        <div class="muv">
                            <ul>
                                <li><a onclick="munkalapTelepitett(\'csak kérdezünk még\');">Telepített</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_mobil\');">Mobil</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_kulso\');">Külső</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_adminisztrator\');">Adminisztrátor</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_muszaki_vezeto\');">Műszaki vezető</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_teruleti_vezeto\');">Területi vezető</a></li>
                                <li><a onclick="csoportMuvelet(\'munkalap_diszp_kozp\');">Diszp. közp. vezető</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            ');
        } else if( _jog(3) && _jog(5) && in_array($sor['statusz_id'], array(8, 10)) ) {

            $telephely = $adatbazis_kapcsolat->s('modell', 'telephely', 'id='.$sor['telephely_id'], '', '1');

            $atadas_gomb = '';
            if( $telephely ) {
                $telephely = $telephely[0]['modell'];

                /* Több szállodás modell */
                if( $telephely ) {
                    $atadas_gomb = nl2('
                        <script>
                            muszVezAtadGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"atadasMuszVeznek();\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                        </script>
                        <input type="button" class="gomb foGomb" value="Átadás Műsz.Vez.-ne" title="Átadás Műsz.Vez.-nek" onclick="alert2(\'Biztos, hogy átadja a munkalapot Műszaki Vezetőnek?\', muszVezAtadGomb);">
                    ');
                /* Egy szállodás modell */
                } else {
                    $atadas_gomb = '<input type="button" class="gomb foGomb" value="Átadás diszpécsernek" title="Átadás diszpécsernek" onclick="csoportMuvelet(\'atadasDiszpecsernek\')">';
                }
            }

            $gombok .= nl2('
                <input type="button" class="gomb foGomb" value="Elvégzett feladat hozzáadása" title="Elvégzett feladat hozzáadása" onclick="csoportMuvelet(\'feladatHozzaad\')">
                <input type="button" class="gomb foGomb" value="Készre jelentés" title="Készre jelentés" onclick="munkalapKeszre(\'csak kérdezünk még\');">
                '.$atadas_gomb.'
                <input type="button" class="gomb foGomb" value="Anyagigénylés szükséges" title="Anyagigénylés szükséges" onclick="csoportMuvelet(\'munkalapAnyagigenyles\');">
                <input type="button" class="gomb foGomb" value="Anyag vételezés" title="Anyag vételezés" onclick="csoportMuvelet(\'munkalapAnyagvetelezes\');">
            ');

        } else if( _jog(3) && _jog(5) ) {
            $telephely = $adatbazis_kapcsolat -> select('nev', 'telephely', "`id`='{$sor['telephely_id']}'");
            $telephely = $telephely[0];

            $gombok .= nl2('
                <script>
                    var munkalapFelveszKerdes = \'
                        <div class="l">
                            Iktatószám: '.$sor['ikt_szam'].'
                            <br>Szálloda: '.addslashes($telephely['nev']).'
                        </div>
                        <br>Felveszi ezt a munkalapot?\';

                    munkalapFelvetelGomb = "<p class=\"c\"><input class=\"gomb piros\" type=\"button\" value=\"Igen\" onclick=\"munkalapFelvesz(\''.$_GET['id'].'\');\"><input class=\"gomb zold\" type=\"button\" value=\"Nem\" onclick=\"alert2()\"></p>";
                </script>
                <input type="button" class="gomb foGomb" value="Munka felvétele" title="Munka felvétele" onclick="alert2(munkalapFelveszKerdes, munkalapFelvetelGomb);">
            ');
        }

        $muv_style = ' style="width: calc(100% - '.($_SESSION['defaultCsuszka'] + 16).'px);"';
        if( in_array(basename(self()), $_SESSION['ketHasabos']) && $_SESSION[get_db_tabla().'user'] ) {
            if( $_COOKIE['csuszkaSzelesseg'] ) {
                $muv_style = ' style="width: calc(100% - '.($_COOKIE['csuszkaSzelesseg'] + 16).'px);"';
            }
        }

        /* Műveletek box */
        $gombok = nl2('
            <div class="muveletek"'.$muv_style.'>
                '.$gombok.'
            </div>
        ');
    }

	//Új termék felvitele
	if(isset($_GET['uj']) && !$csak_szerkesztheto) {

        //NAVIGÁCIÓ
        $navi_sablon  = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit     = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel   = array( $navi,             '',         $gyk2 );
        $szamla_html .= str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
	    require_once('info.php');

	    //FORM
		if(isset($_GET['ujkent'])) {
            foreach($akt_tomb as $ertek) {
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

	    $js = js_kiir($akt_tomb, 'form_ell_admin()');

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

	    $form_sablon = fajlbol_olvas('../sablon/admin/form_uj.html', true);
	    $form_mit    = array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{CK_EDITOR}', '{GOMBOK}');

        /* Javascipt-es ellenőrzéshez */
        $tabla = str_replace('.php', '', basename(self()));

        $rend_mezok = $adatbazis_kapcsolat->s('`column_name`', '`information_schema`.columns', '`table_schema` LIKE  \''.get_db().'\' AND  `table_name` LIKE  \''.get_db_tabla().$tabla.'\'', '', '');
        $rend_mezok = adatbazis2tomb($rend_mezok, 'column_name');

        $sor = array();
        foreach($rend_mezok as $rend_mezo) {
            $sor[$rend_mezo] = '';
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $sor[$tomb['name'].'_autocomp'] = '';
            }
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $mezok .= '<input type="hidden" id="ujkentSzerkID" name="ujkentSzerkID" value="'.$_GET['ujkent'].'">';

	    $form_mivel   = array($js, self(), $mezok, $megnevezes, $ckeditor, $gombok);
	    $szamla_html .= str_replace($form_mit, $form_mivel, $form_sablon);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	//Termék módosítása
	}elseif(isset($_GET['modosit'])) {

        //Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

	    $sor = $adatbazis_kapcsolat -> select('*', $megnevezes, 'recid='.$_GET['id']);
		$sor = $sor[0];
//echo $adatbazis_kapcsolat->gp() . '<br>';
        if( $sor['datetime'] ) {
            $_SESSION['formMod'] = array(
                'datetime' => $sor['update_dt'],
                'oldal'    => basename(self())
            );
        }

        foreach($akt_tomb as $tomb) {
            if( $tomb['type'] == 'autocomplete' ) {
                $kulso_ertek = $adatbazis_kapcsolat->s('recid nev', $tomb['tabla'], 'recid='.$sor[$tomb['name']], '', '1');

                $sor[$tomb['name'].'_autocomp'] = '';
                if( $kulso_ertek ) {
                    $sor[$tomb['name'].'_autocomp'] = $kulso_ertek[0]['nev'];
                }
            }
        }

        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($sor).'\'); </script>';

        $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
        $telephely = $telephely[0]['nev'];

        $munkalap_kapcs = $adatbazis_kapcsolat->s('recid', 'munkalap_kapcs', "`munkalap_id` = '".$sor['recid']."'", '', '');

        $szamla_html .= nl2('
            <script>
                duplikaKerdes = \'Iktatószám: '.$sor['ikt_szam'].'<br>Szálloda: '.$telephely.'<br><br>Ez a munkalap '.count($munkalap_kapcs).' db bejelentésből készült. <br>Ezek a bejelentések az új munkalaphoz is hozzá lesznek rendelve. <br>Biztos, hogy duplikálja?\';
            </script>
        ');

        if($id_latszik){
            $navi = '<b>#'.$_GET['id'].'</b> '.$navi;
        }

        //NAVIGÁCIÓ
        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array( $navi,             '',         $gyk2 );
        //echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

	    //HIBAüZENETEK
        require_once('info.php');

	    //FORM
	    $js = js_kiir($akt_tomb, 'form_ell_admin()', 'ajax');

		//Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

        if(isset($_GET['ujkent'])){
			foreach($akt_tomb as $ertek){
				$sor[$ertek['name']] = $_SESSION[$ertek['name']];
			}
		}

        //Ckeditort csak akkor húzzuk be, ha kell
        if(is_file($ckeditor_js)){
            $ckeditor = '<script src="'.$ckeditor_js.'"></script>';
        }

		// Ez elkészíti a $mezok változót
		require('switch.php');

	    $szamla_html .= str_replace(
            array('{JS}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'),
            array( $js,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok  ),
            fajlbol_olvas('../sablon/admin/form_modosit.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

        if($tobb_kep){
			require_once('tobb_kep.php');
		}

    } elseif(isset($_GET['tetel'])) {

        /* Tétel sorok */
        $sorok = $adatbazis_kapcsolat->s('recid atadta_dt visszaadva erteklista_id loginnev insert_dt insert_usr', 'munkalap_tetel', "`munkalap_id` = '".$_GET['id']."'", 'insert_dt desc', '');

        if( $sorok ) {
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <table class="csikos full">
                        <tr>
                            <th class="nev">Személyzet</th>
                            <th>Felvette</th>
                            <th>Átadta</th>
                            <th>Visszaadva</th>
                        </tr>
                ');

                $visszaadva = '';
                if( $sor['visszaadva'] ) {
                    $visszaadva = 'igen';
                }

                $atadta = '';
                if( $sor['atadta_dt'] != '0000-00-00 00:00:00' ) {
                    $atadta = date('Y.m.d H:i', strtotime($sor['atadta_dt']));
                }

                $atadas_oka = erteklista(7, $sor['erteklista_id']);
                if( $atadas_oka ) {
                    $atadas_oka = '<br>'.$atadas_oka;
                }

                /* Szerepkör */
                $tetel_felh = $adatbazis_kapcsolat->s('szerepkor_id', 'login', "`nev` = '".$sor['loginnev']."'", '', '');

                $szerepkor = '';
                if( $tetel_felh ) {
                    $tetel_felh = $tetel_felh[0];

                    $szerepkor_sor = $adatbazis_kapcsolat->s('nev', 'szerepkor', "`recid` = '".$tetel_felh['szerepkor_id']."'", '', '1');

                    if( $szerepkor_sor ) {
                        $szerepkor_sor = $szerepkor_sor[0];

                        $szerepkor = '<br>('.$szerepkor_sor['nev'].')';
                    }
                }

                $szamla_html .= nl2('
                    <tr >
                        <td>'.$sor['insert_usr'].$szerepkor.'</td>
                        <td>'.date('Y.m.d H:i', strtotime($sor['insert_dt'])).'</td>
                        <td>'.$atadta.$atadas_oka.'</td>
                        <td>'.$visszaadva.'</td>
                    </tr>
                ');

                $szamla_html .= '</table>';

                /* Elvégzett feladatok lekérdezése */
                $feladatok = $adatbazis_kapcsolat->s('erteklista_id tetel_id memo', 'munkalap_feladat', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');

                if( $feladatok ) {
                    $szamla_html .= nl2('
                        <table class="csikos full al behuz">
                            <tr>
                                <th>Elvégzett feladat</th>
                                <th>Leírás</th>
                            </tr>
                    ');

                    foreach($feladatok as $feladat) {
                        $szamla_html .= nl2('
                            <tr>
                                <td>'.erteklista(5, $feladat['erteklista_id']).'</td>
                                <td>'.nl2br($feladat['memo']).'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
                }

                /* Elvégzett feladatok lekérdezése */
                $anyagok = $adatbazis_kapcsolat->s('cikk_nev cikk_men szuk_men cikk_mee stat_id', 'munkalap_anyag', "`tetel_id` = '{$sor['recid']}'", 'insert_dt desc', '');

                if( $anyagok ) {
                    $szamla_html .= nl2('
                        <table class="csikos full al behuz">
                            <tr>
                                <th>Anyag felhasználás</th>
                                <th>Felhasznált mennyiség</th>
                                <th>Szükséges mennyiség</th>
                                <th>Anyag státusz</th>
                            </tr>
                    ');

                    foreach($anyagok as $anyag) {
                        $cikk_men = '';
                        if( $anyag['cikk_men'] > 0 ) {
                            $cikk_men = $anyag['cikk_men'].' '.$anyag['cikk_mee'];
                        }

                        $szuk_men = '';
                        if( $anyag['szuk_men'] > 0 ) {
                            $szuk_men = $anyag['szuk_men'].' '.$anyag['cikk_mee'];
                        }

                        $szamla_html .= nl2('
                            <tr>
                                <td>'.$anyag['cikk_nev'].'</td>
                                <td>'.$cikk_men.'</td>
                                <td>'.$szuk_men.'</td>
                                <td>'.$munkAnyag_statusz[$anyag['stat_id']].'</td>
                            </tr>
                        ');
                    }

                    $szamla_html .= '</table>';
                }
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Bejelentések
	}elseif(isset($_GET['bejelentesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="bejelentesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Iktató szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Hiba</div>
                    <div class="dtc">Bejelentve</div>
                </div>
        ');

        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid bejelentes_id', 'munkalap_kapcs', "`munkalap_id` = '{$_GET['id']}'", '', '');

        if( $munkalap_kapcsok ) {
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {
                $sor = $adatbazis_kapcsolat->s('recid ikt_szam telephely_id obj_id hiba_id insert_dt', 'bejelentes', "`recid` = '".$munkalap_kapcs['bejelentes_id']."'", '', '1');

                if( $sor ) {
                    $sor = $sor[0];

                    /* Telephely */
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    /* Helység */
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    /* Hiba */
                    $hiba = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=4 AND `recid`='{$sor['hiba_id']}'");
                    $hiba = $hiba[0];

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc"><a href="bejelentes.php?id='.$sor['recid'].'&modosit" target="_blank">'.$sor['ikt_szam'].'</a></div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.$hiba['info'].'</div>
                            <div class="dtc">'.$sor['insert_dt'].'</div>
                        </div>
                    ');
                }

                $i++;
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Történet
	}elseif(isset($_GET['tortenet'])){

        $szamla_html .= nl2('
            <div class="dt naplo">
                <div class="dtr nFejlec">
                    <div class="dtc">Státusz</div>
                    <div class="dtc">Kelte</div>
                    <div class="dtc">Felhasználó</div>
                    <div class="dtc">Státuszidő</div>
                    <div class="dtc">Göngyölt státuszidő</div>
                    <div class="dtc">Megjegyzés</div>
                    <!--<div class="dtc">Összesített státuszidő</div>-->
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('stat_id insert_dt insert_usr statido gongyolt_statido ossz_statido text', $megnevezes.'_statusz', "`id` = '".$_GET['id']."'", 'insert_dt', '');
        if( $sorok ) {
            foreach($sorok as $sor) {
                $statusz = $adatbazis_kapcsolat->s('nev szinkod', $megnevezes.'_statusz_text', 'recid='.$sor['stat_id'], '', '1');

                $szinkod = '';
                if( $statusz ) {
                    $statusz = $statusz[0];

                    $sor['statusz'] = $statusz['nev'];

                    if( $statusz['szinkod'] ) {
                        $szinkod = ' style="background: '.$statusz['szinkod'].';"';
                    }
                }

                //Adatbázisba eleinte szóköz nélkül került be a nap, ezért biztonság kedvéért egy szóközt még beszúrunk, illetve a 0nap-ot is kiszűrjük (bár ez egy adatbázis frissítés után már felesleges)
                $sor['statido']          = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['statido']);
                $sor['gongyolt_statido'] = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['gongyolt_statido']);
                $sor['ossz_statido']     = str_replace(array('0nap', 'nap'), array('', ' nap'), $sor['ossz_statido']);
                $szamla_html .= nl2('
                    <div class="dtr nSor"'.$szinkod.'>
                        <div class="dtc">'.$sor['statusz'].'</div>
                        <div class="dtc">'.date('Y.m.d H:i:s', strtotime($sor['insert_dt'])).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['statido'].'</div>
                        <div class="dtc">'.$sor['gongyolt_statido'].'</div>
                        <div class="dtc">'.nl2br2($sor['text']).'</div>
                        <!--<div class="dtc">'.$sor['ossz_statido'].'</div>-->
                    </div>
                ');
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Anyagigénylés
	}elseif(isset($_GET['anyagigenyles'])){

        $szamla_html .= 'Anyagigénylés felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Elszámolás
	}elseif(isset($_GET['elszamolas'])){

        $szamla_html .= 'Elszámolás felülete';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }


	//Dokumentumok
	}elseif(isset($_GET['dokumentumok'])){

        if( $tobb_kep ) {
            require_once('tobb_kep.php');
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Megjegyzések
	}elseif(isset($_GET['megjegyzesek'])){

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj insert_usr insert_dt', 'megjegyzes', "`id` = '".$_GET['id']."' AND `tabla` = '".str_replace('.php', '', basename(self()))."'", 'insert_dt desc', '');

        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.nl2br($sor['megj']).'</div>
                        <div class="dtc">'.$sor['insert_usr'].'</div>
                        <div class="dtc">'.$sor['insert_dt'].'</div>
                    </div>
                ');

                $i++;
            }
        }

        $szamla_html .= '</div>';

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok,
                'link' => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	//Lista
	}elseif(isset($_GET['lista'])){

        //LEKÉRDEZÉS
        $where      = '';
        $oszlopok   = '';
        foreach($akt_tomb as $ertek){
            if($_GET[$ertek['name']]){
                $where.=' `'.$ertek['name'].'`=\''.$_GET[$ertek['name']].'\' AND';
            }
            if($ertek['show']){
                $oszlopok .= $ertek['name'].', ';
            }
        }
        //A használni kívánt oszlopokat itt tudod bővíteni
        $oszlopok .= 'recid, ';
        if($kep_thumb_listaba){
            $oszlopok .= $fajloszlop.', ';
        }

        //Ebben az esetben a gyorskeresőt általános keresőként használtuk
        if(isset($_GET[$gyorskereso_id])){
            $where_keres = '';
            foreach($akt_tomb as $ertek){
                if($ertek['type']=='text' || $ertek['type']=='textarea'){
                    $where_keres .= ' `'.$ertek['name'].'` LIKE \'%'.$_GET[$gyorskereso_id].'%\' OR';
                }
            }
        }
        if($where){
            $where = csonkit($where,4);
        }
        //Ha keresünk, akkor még kiegészítjük a WHERE feltételt
        if($where_keres){
            $where .= ' ('.csonkit($where_keres, 3).')';
        }

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, $where, '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        if(!$csak_szerkesztheto){
            $gombok = '<input type="button" class="gombb jf jf3" onclick="location.href=\''.self().'?uj\'" value="Új" title="Új feltöltés">';
        }
        if($sorrendezes){
            $gombok .= ' <input type="button" class="gombb jf jf4" onclick="location.href=\''.$_SERVER['PHP_SELF'].'?sorrend\'" value="Sorrend" title="Sorrendezés">';
        }

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);

        //HIBAüZENETEK
        require_once('info.php');

        //Lapozás előkészítése
        require_once('../class/class.Lapozo.php');

        if(!$_GET['oldal']){
            $_GET['oldal'] = 1;
        }

        $lapozo = new Lapozo;
        $lapozo -> elem_osszes = $bejegyzesek;
        $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
        //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
        //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
        //$lapozo -> kozep_meret     = 9;        //Nagy oldalszám esetén a középső intervallum mérete
        //$lapozo -> max_fix_oldal   = 10;        //Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat
        //$lapozo -> epo_array       = array(50, 100, 150);  //Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat
        //$lapozo -> mind_text       = 'Összes oldal';  //pldalszámok mellett a "minden elem kilistázásának" felirata (ha $mind_var üres string akkor nem jelenik ez meg)
        //$lapozo -> elozo_title     = '« Előző oldal';        //'Előző' felirat, link title-jéhez
        //$lapozo -> kovet_title     = 'Következő oldal »';    //'Következő' felirat, link title-jéhez
        //$lapozo -> kozep_meret     = 'Aktuális oldal';       //'Aktuális oldal' felirat, link title-jéhez

        $utvonal = '../sablon/admin/';
        $lapozo -> sablon_elozo       = $utvonal.'lapozas_elozo.html';
        $lapozo -> sablon_kovetkezo   = $utvonal.'lapozas_kovet.html';
        $lapozo -> sablon_oldalak     = $utvonal.'lapozas_oldalak.html';
        $lapozo -> sablon_option      = $utvonal.'lapozas_option.html';
        $lapozo -> sablon_pontok      = $utvonal.'lapozas_pontok.html';
        $lapozo -> sablon_select      = $utvonal.'lapozas_select.html';

        //Számolás
        $lapozo -> lapozas();

        $lekerdezes = $adatbazis_kapcsolat -> select($oszlopok, $megnevezes, $where, $_GET['rend'].' '.$_GET['desc'], $lapozo -> getLimit());

        //GYORSKERESŐ
        echo $gyk;

        //SZŰKÍTÉS HA KELL
        echo szukites($akt_tomb, $megnevezes);

        //lapozas($sql,$_GET['lh']);

        //SQL
        if($bejegyzesek){

            //Form sablon
            $form_sablon = fajlbol_olvas('../sablon/admin/form_lista_torles_nelkul.html', true);
            $form_mit    = array('{ACTION}', '{FEJLECEK}', '{SOROK}', '{MEGNEVEZES}', '{TOBBKEP}', '{TOBBKEP_OSZLOP}');

            $fejlecek = '';
            if($kep_thumb_listaba){
                $fejlecek .= '<td class="fejlec">Kép</td>';
            }
            if($id_latszik){
                $fejlecek .= '<td class="fejlec">#</td>';
            }
            foreach($akt_tomb as $ertek){
                if($ertek['show']){

                    $fejlecek .= '<td class="fejlec">';

                    if($_GET['order'] == $ertek['name']){
                        if(isset($_GET['desc'])){
                            $fejlecek .= '<div class="nyil_le"></div>';
                        }else{
                            $fejlecek .= '<div class="nyil_fel"></div>';
                        }
                    }

                    $fejlecek .= '<a href="'.self().get_get_order($ertek['name']).'">'.$ertek['megn'].'</a></td>';

                }
            }

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

            //törléshez a JS script
            ?><script><?php
                ?>uzi_e = 'Biztosan törli a ';<?php
                ?>uzi_v = ' <?php echo $szoveg?>?';<?php
                ?>gomb_e = get_torles_gomb_e('<?php echo self().get_get()?>','<?php echo $torles?>');<?php
                ?>gomb_v = get_torles_gomb_v();<?php

                foreach($lekerdezes as $sor){
                    ?>uzi<?php echo $sor['recid']?>='<?php echo $sor['cikksz']?>'; gomb<?php echo $sor['recid']?>='<?php echo $sor['recid']?>&amp;noload\',\'sor<?php echo $sor['recid']?>\'';<?php
                }
            ?></script><?php

            //Sorok sablon
            $dn = '';
            if($csak_szerkesztheto){
                $dn = ' dn';
            }
            $sorok = '';
            $sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_torles_nelkul.html', true);
            //$sorok_sablon = fajlbol_olvas('../sablon/admin/form_sorok_szinezett.html', true);

            $i = 0;
            foreach($lekerdezes as $sor) {
                $oszlopok = '';
                $link = self().get_get(array('id', 'id_hidden', 'lista', 'siker')).'&amp;id='.$sor['recid'].'&amp;modosit';

                if($kep_thumb_listaba){
                    $kep = kep_kiir('../'.get_utvonal().$sor[$fajloszlop], $sor['nev'], get_indexkep_sz(), get_indexkep_m(), 'b');
                    $oszlopok .= '<td'.$style.'>'.$kep.'</td>';
                }
                if($id_latszik){
                    $oszlopok .= '<td>#'.$sor['recid'].'</td>';
                }

                foreach($akt_tomb as $ertek){
                    if($ertek['show']){

                        $style = '';
                        if($ertek['lista_style']){
                            $style = ' style="'.$ertek['lista_style'].'"';
                        }

                        //Kategóriánál hasznos: az adott érték egy külső tábla azonosítája... így tudjuk összekapcsolni
                        if($ertek['kulso_tabla']){
                            list($tabla, $oszlop) = explode('|', $ertek['kulso_tabla']);
                            $kulso_ertek = $adatbazis_kapcsolat->s($oszlop, $tabla, "id={$sor[$ertek['name']]}");
                            $sor[$ertek['name']] = $kulso_ertek[0][$oszlop];
                        }

                        if($ertek['type'] == 'checkbox'){
                            $oszlopok .= '<td'.$style.'>';
                        }else{
                            $oszlopok .= '<td'.$style.' onclick="if(!onclick_disabled){location.href=\''.$link.'\';}">';
                        }

                        if(is_numeric($sor[$ertek['name']]) && !in_array($ertek['name'], $formazatlan_szam) && $ertek['type'] != 'checkbox'){
                            $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text">'.number_format($sor[$ertek['name']],0,'','.').'</span>'.$ertek['post'].'</span>';
                            $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                        } else {
                            if($ertek['type'] == 'checkbox'){
                                $class = '';
                                if($sor[$ertek['name']]){
                                    $class = ' aktiv';
                                }
                                $oszlopok .= '<span class="pipa'.$class.'" id="pipa_'.$ertek['name'].'_'.$i.'" onclick="pipa_kapcsolo(\'pipa_'.$ertek['name'].'_'.$i.'\', \'pipa_'.$ertek['name'].'\', \''.$megnevezes.'lista\', \''.$sor['recid'].'\')">✔</span>';
                            }else{
                                $oszlopok .= '<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_span" onclick="inline_szerk(\''.$ertek['name'].'\', \''.$sor['recid'].'\')">'.$ertek['pre'].'<span id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'_text" onclick="inline_szerk(\''.$ertek['name'].'\')">'.$sor[$ertek['name']].'</span>'.$ertek['post'].'</span>';
                                $oszlopok .= '<br><input class="dn" type="text" name="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" id="inline_szerk_'.$ertek['name'].'_'.$sor['recid'].'" value="'.$sor[$ertek['name']].'" onblur="inline_szerk_ment(\''.$megnevezes.'\', \''.$ertek['name'].'\', \''.$sor['recid'].'\')">';
                            }
                        }

                        $oszlopok .= '</td>';
                    }
                }

                $szin = '';
                /*Pl:
                switch( $sor['statusz'] ){
                    case 'jelezni'     : $szin = 'sarga';  break;
                    case 'jelezve'     : $szin = 'lila';   break;
                    case 'elfogadva'   : $szin = 'zold';   break;
                    case 'kiszamlazva' : $szin = 'piros';  break;
                    case 'kifizetve'   : $szin = 'szurke'; break;
                }
                */

                $sorok_mit   = array('{I}', '{ID}',     '{DN}', '{SZIN}', '{LINK}', '{OSZLOPOK}');
                $sorok_mivel = array($i%2,  $sor['recid'], $dn,    $szin,    $link,    $oszlopok   );
                $sorok .= str_replace($sorok_mit, $sorok_mivel, $sorok_sablon);
                $i++;
            }

            //Ha többképes, akkor azt a törlésnél jelezni kell, úgy hogy postoljuk az oszlopot (több termék törlése egyszerre)
            $tobbkep = '';
            if($tobb_kep){
                $tobbkep = '<input type="hidden" name="oszlopnev" value="'.$tobbkep_oszlopnev.'">';
            }

            $form_mivel  = array(self().get_get(), $fejlecek, $sorok, $megnevezes, $tobbkep, $tobbkep_oszlopnev);

            echo str_replace($form_mit, $form_mivel, $form_sablon);

            if($lapozo -> getOldalakSzama() > 1){
                ?><fieldset class="c"><legend>Lapozás</legend><table class="auto"><tr><td><?php
                //Kiíratás
                echo $lapozo -> kiir();
                echo $lapozo -> epoSelectKiir();
                //echo $lapozo -> oldalakSelectKiir();
                ?></td></tr></table></fieldset><?php
            }

        }else{
            ?><h1>Jelenleg nem taláható felvitt adat!</h1><?php
        }

    //Sorrendezése
    }elseif(isset($_GET['sorrend'])){

        //NAVIGÁCIÓ

        //Darabszám kiszámítása
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid', $megnevezes, '', '', '', '', true);
        $navi .= ' <span class="navi_db">(<span id="bejegyzesek_szama">'.$bejegyzesek.'</span> db. bejegyzés)</span>';

        $gombok = '';
        if(!$csak_szerkesztheto){
            $gombok .= '<input type="button" class="gombb jf jf4" onclick="location.href=\''.self().'?uj\'" value="Új" feltöltés" title="Új feltöltés">';
        }
        $gombok .= ' <input type="button" class="gombb jf jf5" onclick="location.href=\''.self().get_get(array('sorrend')).'&amp;lista\'" value="Lista" title="Lista megtekintése">';

        $navi_sablon = fajlbol_olvas('../sablon/admin/navi.html', true);
        $navi_mit    = array('{NAVI_FELIRAT}',  '{GOMBOK}', '{GYK}');
        $navi_mivel  = array($navi,             $gombok,    $gyk2);
        echo str_replace($navi_mit, $navi_mivel, $navi_sablon);


        $sorrend_html  = '';
        $sorrend_html .= '<ul id="sorrendezes_bejegyzes" title="'.$megnevezes.'">';

        $i = 0;
        $bejegyzesek = $adatbazis_kapcsolat -> select('recid nev kep', $megnevezes, '', 'sor');
        foreach($bejegyzesek as $bejegyzes){

            /*Ha egy termékhez több kép is tartozik

            $kiskep = $adatbazis_kapcsolat -> select('kepnev', 'kepek', 'termekid='.$sor_kepek['id'], 'sor');
            $sor_kiskep = $kiskep[0];*/

            /*Egy kép esetén

            if( !$bejegyzes['kep'] || !is_file('../'.get_utvonal().$bejegyzes['kep'])){
                $kep = '../elemek/admin/sorrend.gif';
            }else{
                $kep = '../'.get_utvonal().$bejegyzes['kep'];
            }
            require_once('../class/html/class.Img.php');
            $kep = new Img($kep, $bejegyzes['nev'], get_kep_sz(), get_kep_m());
            $kep = $kep -> getHtml();
            */

            $sorrend_html .= '<li title="'.$bejegyzes['nev'].'" value="'.$bejegyzes['recid'].'"><div class="kep">'.$kep.'<br><b>'.$bejegyzes['nev'].'</b></div></li>';

            $i++;
        }

        $sorrend_html .= '</ul>';
        echo $sorrend_html;

    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
