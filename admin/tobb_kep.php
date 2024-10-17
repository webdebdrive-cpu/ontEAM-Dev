<?php
/**
    FONTOS OLVASD EL!

    1. ITT NEM SZERPELHET A: require_once('../weblap.php'); A session_start() miatt, ami a képfeltöltéshez kell
    2. A $tobbkep_tablanev = 'kepek'; változót muszáj itt megadni. Ennek értéke az alap PHP fájlban, az if($tobb_kep){} feltételnél megadott $tobbkep_tablanev-vel azonos. Lényegében azt az adatbázis táblát kell megadni, amiben az adatok letárolásra kerülnek.

    HA TÖBB ILYEN FELTÖLTÉSI PANEL IS KELL (azaz nem csak egy "Több kép" lenne hanem több is), akkor a következőket lépésekre lesz szükség:
        - tobb_kep.php-ból csinálj klónokat, pl. tobb_kep2.php (ugyanis ez lesz meghívva ajax-al, fájl feltöltésekor)

 */

require_once('../class/html/class.Img.php');

$php_tablanev     = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
$tobbkep_tablanev = $php_tablanev.'_dok';
$archivum = 0;
if( substr($php_tablanev, strlen($php_tablanev) - 5) == '_arch' ) {
    $tobbkep_tablanev = substr($megnevezes, 0, strlen($megnevezes)-5).'_dok_arch';
    $archivum = 1;
}
if( $_POST['tabla'] ) {
    $tobbkep_tablanev = $_POST['tabla'].'_dok';
}

// Eszközkezelésnél az engedélyeknek van egy külön tálbája
if( basename($_SERVER['PHP_SELF']) == 'eszkozkezeles.php' && isset($_GET['engedelyek']) ) {
    $tobbkep_tablanev = 'eszkozkezeles_eng_dok';
}

// Legutóbb feltötött fájl adatainak lekérése, tipus kiválasztásához
if( isset($_POST['get_feltoltott_fajl']) ) {

    require_once('../weblap.php');
    $dokumentum = $adatbazis_kapcsolat->s2('recid fajlnev', $_POST['tabla'].'_dok', "`{$_POST['tabla']}_id`='{$_POST['id']}'", 'recid desc', '1');

    echo nl2('
        Legutóbb feltöltöt fájl: '.$dokumentum['fajlnev'].'
        <br>Válassza ki a dokumentum tipusát:
        <br><input type="hidden" name="pop_recid" id="pop_recid" value="'.$dokumentum['recid'].'">
        ');
    
    $weblap -> leallit();
    exit;
}

// Dokumentum típus letárolása
if( isset($_POST['dokumentum_tipus_valaszt']) ) {

    require_once('../weblap.php');
    $sql = $adatbazis_kapcsolat->u($_POST['tabla'].'_dok', array('tipus'), array($_POST['tipus']), "`recid`='{$_POST['recid']}'");
    
    // Ilyen elvileg csak a projekt kezelés felületen fordulhat elő
    if( $_POST['tabla'] == 'projektkezeles' ) {
        $pr_id = $adatbazis_kapcsolat->s2('projektkezeles_id', 'projektkezeles_dok', "`recid`='{$_POST['recid']}'");
        projekt_minositesre_varo_dok_szamol($pr_id['projektkezeles_id']);
    }
    
    // Logolás
    $dok = $adatbazis_kapcsolat->s2($_POST['tabla'].'_id fajlnev', $_POST['tabla'].'_dok', "`recid`='{$_POST['recid']}'");
    addMegj($_POST['tabla'], $dok[$_POST['tabla'].'_id'], $dok['fajlnev'].' dokumentum '. erteklista(ERTEKLISTA_DOK_TIPUS, $_POST['tipus']).' típusnak minősítve');
    
    $weblap -> leallit();
    exit;
}

// Fájlfeltöltés
if( isset($_POST['megnevezes']) && !$archivum ) {

    // Ha a megnevezés csak annyi, hogy javaslattetel_popup, akkor munkalap javaslattétel popuján töltünk fel képet, ekkor másképp kell eljárni
    if( $_POST['megnevezes'] == 'javaslattetel_popup' ) {

        require_once('../weblap.php');
        $konyvtar_db_nev = csonkit($_POST['tabla'], 4);
        $utvonal = '../'.get_utvonal().'munkalap';
        $ikt_szam = $adatbazis_kapcsolat->s2('recid ikt_szam', 'munkalap', "`recid` = '".$_POST['sorid']."'", '', '1');
        if( $ikt_szam ) {
            $utvonal = $utvonal.'/'.str_replace('/', '_', $ikt_szam['ikt_szam']);
            if( !is_dir($utvonal) ) {
                mkdir($utvonal, 0777);
            }
        }

        // JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
        if( $_FILES['file'] ) {
            $_FILES[ $_POST['megnevezes'] ] = $_FILES['file'];
        }

        $sor_kepek          = 'munkalap';
        $tobbkep_tablanev   = 'munkalap_dok';

    // Általános eset
    } else {

        require_once('../weblap.php');

        // utolsó 4 karakter a "_dok", ezt levesszük
        $konyvtar_db_nev = csonkit($_POST['tabla'], 4);
        $utvonal = '../'.get_utvonal().$konyvtar_db_nev;

        // Mappa esetleges létrehozása
        if( !is_dir($utvonal) ) {
            mkdir($utvonal, 0777);
        }
        
        // Az útvonal, projektek esetén évszámmal is meg van még toldva, amit a rekord insert_dt-jéből veszünk, kivéve számláknál, ott a teljesítés éve számít
        $projekt_feluletek = array('projektkezeles', 'projektszamla', 'projektszerzodes', 'fenntartasi_szerzodes');
        if( in_array($konyvtar_db_nev, $projekt_feluletek) ) {

            // Projekt számla esetén a teljesítés évét kell mappának beállítani
            if( $konyvtar_db_nev == 'projektszamla' ) {
                $pr_szamla = $adatbazis_kapcsolat->s2('telj_dt szallito_id', 'projektszamla', "`recid`='".$_POST['sorid']."'");
                $ev_mappa   = substr($pr_szamla['telj_dt'], 0, 4);
            } else {
                $sor        = $adatbazis_kapcsolat->s2('insert_dt', $konyvtar_db_nev, "`recid`='".$_POST['sorid']."'");
                $ev_mappa   = substr($sor['insert_dt'], 0, 4);
            }
            
            $utvonal .= '/'.$ev_mappa;

            // Mappa esetleges létrehozása
            if( !is_dir($utvonal) ) {
                mkdir($utvonal, 0777);
            }
        }

        // Projekt számla esetén nincs ikt. szám oszlop, ezért ott a szallito_id a könyvtár neve
        if( $konyvtar_db_nev == 'projektszamla' ) {

            $utvonal .= '/'.$pr_szamla['szallito_id'];
            
        // Általános eset
        } else {

            // Következő mappa szint az iktatószámból jön
            // Projektszerződésnél az iktatószámnak más az oszlopneve
            $ikt_szam_oszlop = 'ikt_szam';
            if( $konyvtar_db_nev == 'projektszerzodes' ) {
                $ikt_szam_oszlop = 'sz_szam';
            }
            if( $konyvtar_db_nev == 'fenntartasi_szerzodes' ) {
                $ikt_szam_oszlop = 'szszam';
            }
            // Partnertörzsnél (szállítóknál) az id más az oszlopneve
            if( $konyvtar_db_nev == 'szallito' ) {
                $ikt_szam_oszlop = 'id';
            }
            // Iktatószám alapján könyvtár felépítése
            $ikt_szam = $adatbazis_kapcsolat->s2('recid '.$ikt_szam_oszlop, $_POST['konyvtar'], "`recid` = '".$_POST['sorid']."'", '', '1');
            if( $ikt_szam ) {
                $utvonal = $utvonal.'/'.str_replace('/', '_', $ikt_szam[$ikt_szam_oszlop]);
            }
        }

        // Mappa esetleges létrehozása
        if( !is_dir($utvonal) ) {
            mkdir($utvonal, 0777);
        }
        
        // JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
        if( $_FILES['file'] ) {
            $_FILES[$_POST['megnevezes']] = $_FILES['file'];
        }
    }

    // Ha nincs dokumentum kezelés jogunk, akkor feltöltést blokkoljuk
    if( !dokumentum_kezeles_jog($_POST['tabla'], $_POST['sorid']) ) {
        exit;
    }

	// Tiltott fájlformátumok kiszűrése, szintén nincs feltöltés
	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
    if( in_array($_FILES[$_POST['megnevezes']]['type'], $tiltott_fajlok) ) {
        echo 'Hiba!Ilyen formátumú fájl feltöltése tiltott, a fájlfeltöltés nem jött létre!';
        $weblap -> leallit();
		exit;
	}

    setlocale(LC_CTYPE, 'hu_HU.utf8');
    $fajlnev = pathinfo($_FILES[ $_POST['megnevezes'] ]['name']);
    
    // -- kiszűrése a fájl nevéből
    $fajlnev = str_replace('--', '-', $fajlnev['filename']);
    while ( strpos($fajlnev, '--') !== false ) {
        $fajlnev = str_replace('--', '-', $fajlnev);
    }

    switch ( $_FILES[$_POST['megnevezes']]['error'] ) {

        case 1:
            echo 'Hiba! Túl nagy méretű fájl! Maximum '.ini_get('upload_max_filesize').' engedélyezett!';
        break;

        case 2:
            echo 'Hiba! Túl nagy méretű fájl!'; // A HTML form-ban is megadható volna egy méretkorlát?
        break;

        case 3:
            echo 'Hiba! A fájl feltöltése idő közben megszakadt!';
        break;

        case 4:
            echo 'Hiba! A fájl feltöltése ismeretlen okokból sikertelen volt!';
        break;

        case 6:
            echo 'Hiba! A fájl feltöltése sikertelen volt! (Hiányzik a szerveren a TMP mappa.)';
        break;

        case 7:
            echo 'Hiba! A fájl feltöltése sikertelen volt! (Nem támogatott kiterjesztés.)';
        break;

        case 0:
            // Végleges képnév a teljes elérési útvonallal, kiterjesztéssel (hozzáadjuk az eredeti fájl kiterjesztését)
            $kit   = kit($_FILES[ $_POST['megnevezes'] ]['name']);
            $kepnev = $utvonal.'/'.$fajlnev.'.'.$kit;

            if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'false' ) {
                echo 'Hiba!A fájl létezik!';
            } else {
                if( is_file($kepnev) && $_POST['ellenorzes_kihagy'] == 'true' ) {
                    kep_torol($kepnev);
                }

                // Fájlfeltöltés
                if( move_uploaded_file($_FILES[$_POST['megnevezes']]['tmp_name'], $kepnev) ) {

                    $apa_tabla = csonkit($_POST['tabla'], 4);
                    
                    // Tipus választó popup-ot így hívjuk meg, hogy egy tipus_beallit sztringgel kezdjük a visszaküldött adatokat
                    if( tipusos_dokumentumok( $apa_tabla ) ){
                        echo 'tipus_beallit';
                        
                        // Illetve ekkor megjegyzéshez is bekerül a fájl neve
                        addMegj( $apa_tabla, $_POST['sorid'], 'Dokumentum hozzáadva: '.$fajlnev.'.'.$kit);
                    }
                    // Projektszerződés menüpont alatt a dokumentum feltöltéséről szintén megjegyzést írunk
                    // Azért van külön ágban, mert itt kifejezetten nem kell ez: echo 'tipus_beallit'; Plusz itt van egy levélküldés is
                    if( $_POST['tabla']=='projektszerzodes_dok' ){
                        addMegj( $apa_tabla, $_POST['sorid'], 'Dokumentum hozzáadva: '.$fajlnev.'.'.$kit);
                        level_felkeszit_es_kuld('projektszerzodes', $_POST['sorid'], '', 'projektszerzodesUjDok', 'projektszerzodes_ujdok');
                    }

                    // Projektkezelés menüpont alatt kiírjuk, hogy sikeres feltöltés, és hogy léptessük-e tovább? Ezt a popup hívást jelezzük itt.
                    if( $_POST['tabla']=='projektkezeles_dok' ){
                        // Lezárt státuszhoz már nem engedünk doksit felölteni
                        $prk = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$_POST['sorid']}'");
                        if( $prk['stat_id']==PRKEZEL_STAT_LEZART ){
                            exit;
                        }
                        echo 'leptessuk_e_tovabb';
                        
                    }
                    
                    $kep_tort = str_replace('../'.get_utvonal().$konyvtar_db_nev.'/', '', $kepnev);
                    if( $_POST['ellenorzes_kihagy'] == 'false' ) {
                        // Letárolás adatbázisba is. A kép neve nem tartalmazza a get_utvonal részt! Ha sikeres akkor adunk egy outputot a flashfile-nak
                        $id = $adatbazis_kapcsolat -> i( $_POST['tabla'],
                                                        array($_POST['oszlop'], 'utvonal',  'fajlnev',         'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                           ),
                                                        array($_POST['sorid'],  $kep_tort,  $fajlnev.'.'.$kit, $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev'] )
                                                    );

                        $sor_kepek = $adatbazis_kapcsolat->s2('*', $_POST['tabla'], "`recid`='{$id}'");

                        $osszes_sor = $adatbazis_kapcsolat->s('recid', $_POST['tabla'], '');

                        echo get_tobbkep_thumb($sor_kepek, count($osszes_sor));
                        
                    } else {
                        
                        $sor_kep = $adatbazis_kapcsolat->s($_POST['oszlop'], $_POST['tabla'], "`".$_POST['oszlop']."` = '".$_POST['sorid']."' AND `utvonal` = '{$kep_tort}'", '', '0,1');

                        if( !$sor_kep ) {
                            $id = $adatbazis_kapcsolat -> i( $_POST['tabla'],
                                                                array($_POST['oszlop'], 'utvonal',  'fajlnev',            'loginnev',                        'insert_dt',   'insert_usr',                            'update_usr'                            ),
                                                                array($_POST['sorid'],  $kep_tort,  $fajlnev.'.'.$kit,    $_SESSION[get_db_tabla().'user'],  'NOW()',       $_SESSION[get_db_tabla().'teljes_nev'],  $_SESSION[get_db_tabla().'teljes_nev']  )
                                                            );
                        }

                        echo 'frissit';
                    }

                    // Végül a szülő táblában a dok_db karbantartása
                    $dokumentumok_szama = count( $adatbazis_kapcsolat->s('recid', $_POST['tabla'], "`{$_POST['oszlop']}`='{$_POST['sorid']}'") );
                    $adatbazis_kapcsolat->u($konyvtar_db_nev, array('dok_db'), array($dokumentumok_szama), "`recid`='{$_POST['sorid']}'");

                    
                    // Illetve projektkezelésnél/projektszerződésnél adott projekt minősítésre váró dokumentumait újra összeszámoljuk
                    if( $_POST['tabla']=='projektkezeles_dok' ) {
                        projekt_minositesre_varo_dok_szamol($_POST['sorid']);
                    }
                    if( $_POST['tabla']=='projektszerzodes_dok' ) {
                        $szerzodes = $adatbazis_kapcsolat->s2('pr_szam', 'projektszerzodes', "`recid`='{$_POST['sorid']}'");
                        $projekt   = $adatbazis_kapcsolat->s2('recid', 'projektkezeles', "`ikt_szam`='{$szerzodes['pr_szam']}'");
                        projekt_minositesre_varo_dok_szamol($projekt['recid']);
                    }

                }
            }
        break;
    }

    $weblap -> leallit();
    exit;
}
require_once('../weblap.php');

// Tartalom
require_once '../class/html/class.Img.php';

// Sessionok beállítása a feltöltéshez:
$megn = 'tobbkep'.$_GET['id'];

// Korlátozás csak képre
$eng_kit = '';
if( get_tobbkep_csakkep() ) {
    $eng_kit = ' accept="'.get_tobbkep_csakkep().'"';
}

// Fájlfeltöltéskor milyen paraméterek menjenek át
$parameterek = nl2("
                    'megnevezes'    : '".$megn."',
                    'konyvtar'      : '".$megnevezes."',
                    'tabla'         : '".$tobbkep_tablanev."',
                    'oszlop'        : '".get_tobbkep_oszlopnev()."',
                    'sorid'         : '".$_GET['id']."'
                    ");

// Ha minden fájl feltöltése kész, mi történjen (ide kéne egy jó ajax hívás)
$gomb_neve   = 'Tallózás';
$minden_kesz = '';
if( $tobbkep_tablanev == 'eszkozkezeles_eng_dok' ) {
    $minden_kesz = 'kapcsolodoSzerkeszt(\'eszkozkezel_engedely\');';
    $gomb_neve   = 'Új engedély feltöltése';
} else {
    $minden_kesz = 'szamlaBetolt(_get(\'id\'), baseName(location.href) + \'.php\', \'dokumentumok\');';
}

// Típus oszlop
$multiple       = ' multiple=""';
$tipus_fejlec   = '';
$apa_tabla      = csonkit($tobbkep_tablanev, 4);
if( tipusos_dokumentumok($apa_tabla) ) {
    $tipus_fejlec   = '<div class="felirat">Típus</div>';
    $multiple       = '';
}

// Van-e dokumentum kezelés jogunk?
$nincs_torles = '';
if( !dokumentum_kezeles_jog($tobbkep_tablanev, $_GET['id']) ) {
    $nincs_torles = 'nincs törlés';
}

if( !$nincs_torles ) {
    $sorrend_ujkep       = fajlbol_olvas('../sablon/admin/tobbkep_ujkep.html', true);
    $sorrend_ujkep_mit   = array('{GOMB_NEVE}', '{MEGN}',  '{PHP_FAJL}',                     '{ENG_KIT}', '{PARAMETEREK}', '{MINDEN_KESZ}', '{MULTIPLE}', '{MAX_MERET}'                         );
    $sorrend_ujkep_mivel = array($gomb_neve,    $megn,     'tobb_kep.php'/*self() nem jó!*/, $eng_kit,     $parameterek,    $minden_kesz,   $multiple,    ini_get('upload_max_filesize')*1024   );
}

$tobbkep_html  = '';
if( !$archivum ) {
    $tobbkep_html .= str_replace($sorrend_ujkep_mit, $sorrend_ujkep_mivel, $sorrend_ujkep);
}

$i     = 0;
$kepek = $adatbazis_kapcsolat -> s('*', $tobbkep_tablanev, "`".get_tobbkep_oszlopnev()."`='{$_GET['id']}'", 'insert_dt');

// Eszközkezelés engedélyeknél dátumot is kiírunk
$mellek = 'dokumentumok';
if( basename(self()) == 'eszkozkezeles.php' && isset($_GET['engedelyek']) ) {
    $mellek = 'engedelyek';
}

$tobbkep_html .= nl2('
    <div>
        <ul id="sorrendezes_tobbkep" data-tabla="'.$tobbkep_tablanev.'" data-mellek="'.$mellek.'">
    ');

// Projektkezelésnél a direktbe feltölött dokumentumokat másképp listázzuk, hogy lehessen ezejet is minősíteni, illetve levélben elküldeni
if( $kepek && $apa_tabla != 'projektkezeles' ) {
    
    $tobbkep_html .= nl2('
                                <li class="fejlec">
                                    <div class="kep">Fájlnév</div>
                                    <div class="felirat c" style="width: 28.3rem"></div>
                                    <div class="felirat">Feltöltötte</div>
                                    '.$tipus_fejlec.'
                                    <div class="felirat">Feltöltve</div>
                                    <div class="maradek torles"></div>
                                </li>
                                ');

    $i = 1;
    foreach ( $kepek as $sor_kepek ) {
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, $tobbkep_tablanev, $nincs_torles);

        $i++;
    }
    
}

// Projektek adatlapján felsoroljuk a kapcsolódó szerződések és számák dokumetumait is
if( $apa_tabla == 'projektkezeles' ) {
    
    // Projekt lekérdezése
    $projekt = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$_GET['id']}'");
    
    // Direktbe ide feltöltött dokumentumok, aminknek a tipusa üres => minősítésre váróak
    $kepek_direkt = $adatbazis_kapcsolat->s('*', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='0'");
    
    // A szerződéseket kétszer járjuk be: a minősítésre váróakat (tehát ahol a tipus oszlop üres), majd a szerződéseket.
    $kepek          = array();
    $szerzodesek    = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$projekt['ikt_szam']}'");
    $szerzodesek    = adatbazis2tomb($szerzodesek, 'recid');

    // Ha van szerződés, akkor a szerződés dokumentumokat is kirajzoljuk
    if( $szerzodesek ) {
        $kepek = $adatbazis_kapcsolat -> s('`'.get_db_tabla().'projektszerzodes_dok`.*, `'.get_db_tabla().'szallito`.`nev`', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(',', $szerzodesek).") AND `tipus`='0'", 
                                            // Order
                                            '`'.get_db_tabla().'szallito`.`nev`, `'.get_db_tabla().'projektszerzodes`.`sz_szam`', '', '', '', 
                                            // Csatlakozzunk a szerződés sorához a szallito_id miatt, majd tovább csatlakozunk a szállítókhoz, a nev oszlop miatt
                                            'JOIN `'.get_db_tabla().'projektszerzodes` ON `'.get_db_tabla().'projektszerzodes_dok`.`projektszerzodes_id`=`'.get_db_tabla().'projektszerzodes`.`recid`
                                             JOIN `'.get_db_tabla().'szallito` ON `'.get_db_tabla().'projektszerzodes`.`szallito_id`=`'.get_db_tabla().'szallito`.`id` ');
    }

    // Direkt és szerződés dokumentumokat kezeljük egybe
    $kepek = array_merge($kepek_direkt, $kepek);
    if( $kepek ) {
        
        // Nyitva-zárva állapot $_SESSION-ben tárolva
        $azon = 'dok_minositesre_var_'.$_GET['id'];
        $dn = ' dn';
        if( $_SESSION['projekt_dok'][$azon] ) {
            $dn = '';
        }
        
        $tobbkep_html .= nl2('
                            <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                <h3>Minősítésre váró dokumentumok</h3>
                            </li>
                            <li class="fejlec nohover '.$azon.$dn.'">
                                <div class="felirat">Partner</div>
                                <div class="felirat">Szerződés</div>
                                <div class="kep">Fájlnév</div>
                                <div class="felirat c"></div>
                                <div class="felirat feltoltotte_fejlec">Feltöltötte</div>
                                <div class="felirat">Feltöltve</div>
                                <div class="felirat">Minősítés</div>
                                <div class="felirat">Törlés</div>
                                <div class="maradek torles">Jelölés</div>
                            </li>
            ');
    }
    foreach ( $kepek as $sor_kepek ) {
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, 'projektszerzodes_dok', '', $sor_kepek['projektszerzodes_id'], $azon.$dn);
        $i++;
    }
    
    // Szerződések bejárása másodjára: minősített dokumentumok kiíratása, tehát ahol a tipus=szerződés
    $kepek = array();
    if( $szerzodesek ) {
        $kepek = $adatbazis_kapcsolat -> s('`'.get_db_tabla().'projektszerzodes_dok`.*, `'.get_db_tabla().'szallito`.`nev`', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(',', $szerzodesek).") AND `tipus`='".EL_DOK_TIPUS_SZERZODES."'",
                                            // Order
                                            '`'.get_db_tabla().'szallito`.`nev`, `'.get_db_tabla().'projektszerzodes`.`sz_szam`', '', '', '', 
                                            // Csatlakozzunk a szerződés sorához a szallito_id miatt, majd tovább csatlakozunk a szállítókhoz, a nev oszlop miatt
                                            'JOIN `'.get_db_tabla().'projektszerzodes` ON `'.get_db_tabla().'projektszerzodes_dok`.`projektszerzodes_id`=`'.get_db_tabla().'projektszerzodes`.`recid`
                                             JOIN `'.get_db_tabla().'szallito` ON `'.get_db_tabla().'projektszerzodes`.`szallito_id`=`'.get_db_tabla().'szallito`.`id` ');
    }

    // Itt az a feltétel, hogy képek vagy fődokumentum linkek vannak-e.
    // Lehet, hogy még nincs minősített dokumentum, de a szerződés fődokumentum linkjeit 
    // már ekkor ki kell írni, tehát ha van olyan szerződés, aminek van fődokumentum linkje, akkor a Szerződés fájlok blokkot már mutatjuk
    $szerzodesek_fodokok = $adatbazis_kapcsolat -> s('recid', 'projektszerzodes', "`recid` IN (".implode(',', $szerzodesek).") AND `dok_link`!=''", '', '1');
    if( $kepek || $szerzodesek_fodokok ) {
        
        // Nyitva-zárva megjegyzése
        $azon = 'dok_szerzodesek_'.$_GET['id'];
        $dn = ' dn';
        if( $_SESSION['projekt_dok'][$azon] ) {
            $dn = '';
        }
        $tobbkep_html .= nl2('
                            <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                <h3>Szerződések</h3>
                            </li>
                            <li class="fejlec nohover '.$azon.$dn.'">
                                <div class="felirat">Partner</div>
                                <div class="felirat">Szerződés</div>
                                <div class="kep">Fájlnév</div>
                                <div class="felirat c"></div>
                                <div class="felirat">Feltöltötte</div>
                                <div class="felirat">Feltöltve</div>
                                <div class="felirat">Minősítés törlése</div>
                                <div class="felirat"></div>
                                <div class="maradek torles">Jelölés</div>
                            </li>
            ');
    }
    $szerzodes_fodok_link_kiirva = array();
    foreach ( $kepek as $sor_kepek ) {
        
        // Andoc fődokumentum link kilistázása, ha adott szerződésnél még nem jártunk volna
        if( !in_array($sor_kepek['projektszerzodes_id'], $szerzodes_fodok_link_kiirva) ) {
            
            array_push($szerzodes_fodok_link_kiirva, $sor_kepek['projektszerzodes_id']);
            
            $partner    = '';
            $szerzodes  = $adatbazis_kapcsolat->s2('sz_szam szallito_id dok_link insert_usr insert_dt', 'projektszerzodes', "`recid`='{$sor_kepek['projektszerzodes_id']}'");
            $partner    = getErtek('nev', 'szallito', 'id', $szerzodes['szallito_id']);

            // Class
            $class_kiir = '';
            if( $dn ) {
                $class_kiir = ' class="'.$dn.'"';
            }
            
            if( $szerzodes['dok_link'] ) {
                $tobbkep_html .= nl2('
                        <li'.$class_kiir.'>
                            <div class="felirat">'.$partner.'</div>
                            <div class="felirat">
                                <a href="projektszerzodes.php?id=x&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$szerzodes['sz_szam'].'&amp;dokumentumok" target="_blank">
                                    '.$szerzodes['sz_szam'].'
                                </a>
                            </div>
                            <div class="kep">
                                <div>
                                    <a href="'.$szerzodes['dok_link'].'" target="_blank">
                                        <img src="../elemek/ikonok/link.png" alt="Link">
                                    </a>
                                </div>
                            </div>
                            <div id="megj_'.$azon.'_felirat" class="felirat c">
                                <a href="'.$szerzodes['dok_link'].'" target="_blank">
                                    '.wordwrap($szerzodes['dok_link'], 35, '<br>', 1).'
                                </a>
                            </div>
                            <div class="felirat">'.$szerzodes['insert_usr'].'</div>
                            <div class="felirat">'.datum_kiir($szerzodes['insert_dt']).'</div>
                            <div class="felirat"></div>
                            <div class="felirat"></div>
                            <div class="maradek" style="width: 6rem"></div>
                        </li>
                    ');
            }
        }
        
        // Majd a konkrét csatolt doksik kiíratása
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, 'projektszerzodes_dok', 'nincs törlés', $sor_kepek['projektszerzodes_id'], $azon.$dn);
        $i++;
    }
    
    // A szerződéseket járjuk be mégegyszer, arra az esetre, ha nem csatolt dokumentuma.
    // Akkor is meg kell jeleníteni a fődokumentumok linkjeit (fentebb csak akkor lett kiírva, ha adott szerződésnek volt csatolt doksija is)
    foreach ( $szerzodesek as $szerzodes ) {
        
        if( !in_array($szerzodes, $szerzodes_fodok_link_kiirva) ) {
            
            array_push($szerzodes_fodok_link_kiirva, $szerzodes);
            
            $partner    = '';
            $szerzodes  = $adatbazis_kapcsolat->s2('sz_szam szallito_id dok_link insert_usr insert_dt', 'projektszerzodes', "`recid`='{$szerzodes}'");
            $partner    = getErtek('nev', 'szallito', 'id', $szerzodes['szallito_id']);

            $class_kiir = '';
            if( $dn ) {
                $class_kiir = ' class="'.$dn.'"';
            }
            
            if( $szerzodes['dok_link'] ) {
                $tobbkep_html .= nl2('
                        <li'.$class_kiir.'>
                            <div class="felirat">'.$partner.'</div>
                            <div class="felirat">
                                <a href="projektszerzodes.php?id=x&amp;mezo=sz_szam&amp;op=equal&amp;felt='.$szerzodes['sz_szam'].'&amp;dokumentumok" target="_blank">
                                    '.$szerzodes['sz_szam'].'
                                </a>
                            </div>
                            <div class="kep">
                                <div>
                                    <a href="'.$szerzodes['dok_link'].'" target="_blank">
                                        <img src="../elemek/ikonok/link.png" alt="Link">
                                    </a>
                                </div>
                            </div>
                            <div class="felirat c">
                                <a href="'.$szerzodes['dok_link'].'" target="_blank">
                                    '.wordwrap($szerzodes['dok_link'], 35, '<br>', 1).'
                                </a>
                            </div>
                            <div class="felirat">'.$szerzodes['insert_usr'].'</div>
                            <div class="felirat">'.datum_kiir($szerzodes['insert_dt']).'</div>
                            <div class="felirat"></div>
                            <div class="felirat"></div>
                            <div class="maradek" style="width: 6rem"></div>
                        </li>
                    ');
            }
        }
    }
    
    // Számlák
    $kepek      = array();
    $szamlak    = $adatbazis_kapcsolat->s('recid', 'projektszamla', "`pr_szam`='{$projekt['ikt_szam']}'");
    $szamlak    = adatbazis2tomb($szamlak, 'recid');

    // Ha van szerződés, akkor a szerződés dokumentumokat is kirajzoljuk
    if( $szamlak ) {
        $kepek = $adatbazis_kapcsolat -> s('`'.get_db_tabla().'projektszamla_dok`.*, `'.get_db_tabla().'szallito`.`nev`', 'projektszamla_dok', "`projektszamla_id` IN (".implode(',', $szamlak).")",
                                            // Order
                                            '`'.get_db_tabla().'szallito`.`nev`, `'.get_db_tabla().'projektszamla`.`szla_szam`', '', '', '', 
                                            // Csatlakozzunk a számla sorához a szallito_id miatt, majd tovább csatlakozunk a szállítókhoz, a nev oszlop miatt
                                            'JOIN `'.get_db_tabla().'projektszamla` ON `'.get_db_tabla().'projektszamla_dok`.`projektszamla_id`=`'.get_db_tabla().'projektszamla`.`recid`
                                             JOIN `'.get_db_tabla().'szallito` ON `'.get_db_tabla().'projektszamla`.`szallito_id`=`'.get_db_tabla().'szallito`.`id` ');
    }
    // Itt az a feltétel, hogy képek vagy fődokumentum linkek vannak-e.
    // Lehet, hogy még nincs minősített dokumentum, de a számla fődokumentum linkjeit 
    // már ekkor ki kell írni, tehát ha van olyan számla, aminek van fődokumentum linkje, akkor a Számla fájlok blokkot már mutatjuk
    $szamla_fodokok = $adatbazis_kapcsolat -> s('recid', 'projektszamla', "`recid` IN (".implode(',', $szamlak).") AND `dok_link`!=''", '', '1');
    if( $kepek || $szamla_fodokok ) {
        
        // Nyitva-zárva megjegyzése
        $azon = 'dok_szamlak_'.$_GET['id'];
        $dn = ' dn';
        if( $_SESSION['projekt_dok'][$azon] ) {
            $dn = '';
        }
        $tobbkep_html .= nl2('
                            <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                <h3>Számlák</h3>
                            </li>
                            <li class="fejlec nohover '.$azon.$dn.'">
                                <div class="felirat">Partner</div>
                                <div class="felirat">Számla</div>
                                <div class="kep">Fájlnév</div>
                                <div class="felirat c"></div>
                                <div class="felirat">Feltöltötte</div>
                                <div class="felirat">Feltöltve</div>
                                <div class="felirat">Minősítés</div>
                                <div class="felirat"></div>
                                <div class="maradek torles">Jelölés</div>
                            </li>
            ');
    }
    $szamla_fodok_link_kiirva = array();
    foreach ( $kepek as $sor_kepek ) {
        
        // Andoc fődokumentum link kilistázása, ha adott szerződésnél még nem jártunk volna
        if( !in_array($sor_kepek['projektszamla_id'], $szamla_fodok_link_kiirva) ) {
            
            array_push($szamla_fodok_link_kiirva, $sor_kepek['projektszamla_id']);
            
            $partner    = '';
            $szamla     = $adatbazis_kapcsolat->s2('szla_szam szallito_id dok_link insert_usr insert_dt', 'projektszamla', "`recid`='{$sor_kepek['projektszamla_id']}'");
            $partner    = getErtek('nev', 'szallito', 'id', $szamla['szallito_id']);

            // Class
            $class_kiir = '';
            if( $dn ) {
                $class_kiir = ' class="'.$dn.'"';
            }
            
            if( $szamla['dok_link'] ) {
                $tobbkep_html .= nl2('
                        <li'.$class_kiir.'>
                            <div class="felirat">'.$partner.'</div>
                            <div class="felirat">
                                <a href="projektszamla.php?id=x&amp;mezo=szla_szam&amp;op=equal&amp;felt='.$szamla['szla_szam'].'&amp;dokumentumok" target="_blank">
                                    '.$szamla['szla_szam'].'
                                </a>
                            </div>
                            <div class="kep">
                                <div>
                                    <a href="'.$szamla['dok_link'].'" target="_blank">
                                        <img src="../elemek/ikonok/link.png" alt="Link">
                                    </a>
                                </div>
                            </div>
                            <div class="felirat c">
                                <a href="'.$szamla['dok_link'].'" target="_blank">
                                    '.wordwrap($szamla['dok_link'], 35, '<br>', 1).'
                                </a>
                            </div>
                            <div class="felirat">'.$szamla['insert_usr'].'</div>
                            <div class="felirat">'.datum_kiir($szamla['insert_dt']).'</div>
                            <div class="felirat"></div>
                            <div class="felirat"></div>
                            <div class="maradek" style="width: 6rem"></div>
                        </li>
                    ');
            }
        }
        
        // Majd a konkrét csatolt doksik kiíratása
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, 'projektszamla_dok', 'nincs törlés', $sor_kepek['projektszamla_id'], $azon.$dn);
        $i++;
    }
    
    // A számlákat járjuk be mégegyszer, arra az esetre, ha a számlának nincs doksija,
    // akkor is meg kell jeleníteni a fődokumentumok linkjeit (fentebb csak akkor lett kiírva, ha adott számlának volt csatolt doksija is)
    foreach ( $szamlak as $szamla ) {
        
        if( !in_array($szamla, $szamla_fodok_link_kiirva) ) {
            
            array_push($szamla_fodok_link_kiirva, $szamla);
            
            $partner    = '';
            $szamla     = $adatbazis_kapcsolat->s2('szla_szam szallito_id dok_link insert_usr insert_dt', 'projektszamla', "`recid`='{$szamla}'");
            $partner    = getErtek('nev', 'szallito', 'id', $szamla['szallito_id']);

            $class_kiir = '';
            if( $dn ) {
                $class_kiir = ' class="'.$dn.'"';
            }
            
            if( $szamla['dok_link'] ) {
                $tobbkep_html .= nl2('
                        <li'.$class_kiir.'>
                            <div class="felirat">'.$partner.'</div>
                            <div class="felirat">
                                <a href="projektszamla.php?id=x&amp;mezo=szla_szam&amp;op=equal&amp;felt='.$szamla['szla_szam'].'&amp;dokumentumok" target="_blank">
                                    '.$szamla['szla_szam'].'
                                </a>
                            </div>
                            <div class="kep">
                                <div>
                                    <a href="'.$szamla['dok_link'].'" target="_blank">
                                        <img src="../elemek/ikonok/link.png" alt="Link">
                                    </a>
                                </div>
                            </div>
                            <div class="felirat c">
                                <a href="'.$szamla['dok_link'].'" target="_blank">
                                    '.wordwrap($szamla['dok_link'], 35, '<br>', 1).'
                                </a>
                            </div>
                            <div class="felirat">'.$szamla['insert_usr'].'</div>
                            <div class="felirat">'.datum_kiir($szamla['insert_dt']).'</div>
                            <div class="felirat"></div>
                            <div class="felirat"></div>
                            <div class="maradek" style="width: 6rem"></div>
                        </li>
                    ');
            }
        }
    }
    
    // FONTOS: Ez a dokumentum felsorolás még folytatódni fog a Projektek modul alatt, 
    // de itt most kicsit megszakítjuk a működést, hogy a soron következő Jótállások
    // kilistázását a Projektszerződések felülete alatt is megjelenjen
    
}

// Projketszerződésnél pedig felsoroljuk a jótállás fül alatti dokumentumokat is
if( /*$apa_tabla == 'projektszerzodes' ||*/ $apa_tabla == 'projektkezeles' ) {

    $kepek = array();
    /*if( $apa_tabla == 'projektszerzodes' ) {
        
        $kepek = $adatbazis_kapcsolat->s('*', 'projektszerzodes_jotallas', "`projektszerzodes_recid`='{$_GET['id']}'");
    
        if( $kepek ) {
            
            // Nyitva-zárva megjegyzése
            $azon = 'dok_jotallas_'.$_GET['id'];
            $dn = ' dn';
            if( $_SESSION['projekt_dok'][$azon] ) {
                $dn = '';
            }

            $tobbkep_html .= nl2('
                                <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                    <h3>Jótállások</h3>
                                </li>
                                <li class="fejlec nohover '.$azon.$dn.'">
                                    <div class="kep">Fájlnév</div>
                                    <div class="felirat c" style="width: 28.2rem"></div>
                                    <div class="felirat" style="width: 15rem;">Feltöltötte</div>
                                    <div class="felirat">Feltöltve</div>
                                    <div class="maradek torles"></div>
                                </li>
                ');
        }
        
    } elseif( $apa_tabla == 'projektkezeles' ) {
        */
        $projekt             = $adatbazis_kapcsolat->s2('ikt_szam', 'projektkezeles', "`recid`='{$_GET['id']}'");
        $pr_szerzodes_recidk = $adatbazis_kapcsolat->s('recid', 'projektszerzodes', "`pr_szam`='{$projekt['ikt_szam']}'");
        $pr_szerzodes_recidk = adatbazis2tomb($pr_szerzodes_recidk, 'recid');
        // Csak azokat a jótállásokat listázzuk ki, amihez már van fájl csatolva
        $kepek = $adatbazis_kapcsolat -> s('`'.get_db_tabla().'projektszerzodes_jotallas`.*, `'.get_db_tabla().'szallito`.`nev`', 'projektszerzodes_jotallas', "`projektszerzodes_recid` IN (".implode(',', $pr_szerzodes_recidk).") AND `utvonal`!=''",
                                            // Order
                                            '`'.get_db_tabla().'szallito`.`nev`, `'.get_db_tabla().'projektszerzodes`.`sz_szam`', '', '', '', 
                                            // Csatlakozzunk a szerződés sorához a szallito_id miatt, majd tovább csatlakozunk a szállítókhoz, a nev oszlop miatt
                                            'JOIN `'.get_db_tabla().'projektszerzodes` ON `'.get_db_tabla().'projektszerzodes_jotallas`.`projektszerzodes_recid`=`'.get_db_tabla().'projektszerzodes`.`recid`
                                             JOIN `'.get_db_tabla().'szallito` ON `'.get_db_tabla().'projektszerzodes`.`szallito_id`=`'.get_db_tabla().'szallito`.`id` ');
        
        if( $kepek ) {

            // Nyitva-zárva megjegyzése
            $azon = 'dok_jotallas_'.$_GET['id'];
            $dn = ' dn';
            if( $_SESSION['projekt_dok'][$azon] ) {
                $dn = '';
            }
            $tobbkep_html .= nl2('
                                <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                    <h3>Jótállások</h3>
                                </li>
                                <li class="fejlec nohover '.$azon.$dn.'">
                                    <div class="felirat">Partner</div>
                                    <div class="felirat">Szerződés</div>
                                    <div class="kep">Fájlnév</div>
                                    <div class="felirat c"></div>
                                    <div class="felirat">Feltöltötte</div>
                                    <div class="felirat">Feltöltve</div>
                                    <div class="felirat">Minősítés törlése</div>
                                    <div class="felirat"></div>
                                    <div class="maradek torles">Jelölés</div>
                                </li>
                ');
        }
    /*}*/
    
    foreach ( $kepek as $sor_kepek ) {
        $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, 'projektszerzodes_jotallas', 'nincs törlés', $sor_kepek['projektszerzodes_recid'], $azon.$dn);
        $i++;
    }

}

// Végül egy általános ciklus a Projektek modul alá:
// felsoroljuk az értéklistában levő minősítés fajtákat, és felsoroljuk azokhoz tartozó dokumentumokat is. 
// (Kivéve a szerződéseket, mert azokat fentebb már kiírtuk.)
if( $apa_tabla == 'projektkezeles' ) {
    $dok_tipusok = $adatbazis_kapcsolat->s('id info', 'erteklista', "`csoport`='".ERTEKLISTA_DOK_TIPUS."' AND `id`!='".EL_DOK_TIPUS_SZERZODES."'", 'info');
    foreach( $dok_tipusok as $tipus ) {

        // Tehát itt egy ciklusban ugyanazt csináljuk mint fentebb, csak adott típusra szűrve
        $kepek = array();
        
        // Direkt dokumentumok
        $kepek_direkt = $adatbazis_kapcsolat->s('*', 'projektkezeles_dok', "`projektkezeles_id`='{$_GET['id']}' AND `tipus`='{$tipus['id']}'");
        
        // Ha van szerződés, akkor a szerződés dokumentumokat is kirajzoljuk
        if( $szerzodesek ) {
            $kepek = $adatbazis_kapcsolat -> s('`'.get_db_tabla().'projektszerzodes_dok`.*, `'.get_db_tabla().'szallito`.`nev`', 'projektszerzodes_dok', "`projektszerzodes_id` IN (".implode(',', $szerzodesek).") AND `tipus`='{$tipus['id']}'",
                                                // Order
                                                '`'.get_db_tabla().'szallito`.`nev`, `'.get_db_tabla().'projektszerzodes`.`sz_szam`', '', '', '',
                                                // Csatlakozzunk a szerződés sorához a szallito_id miatt, majd tovább csatlakozunk a szállítókhoz, a nev oszlop miatt
                                                'JOIN `'.get_db_tabla().'projektszerzodes` ON `'.get_db_tabla().'projektszerzodes_dok`.`projektszerzodes_id`=`'.get_db_tabla().'projektszerzodes`.`recid`
                                                 JOIN `'.get_db_tabla().'szallito` ON `'.get_db_tabla().'projektszerzodes`.`szallito_id`=`'.get_db_tabla().'szallito`.`id` ');
        }
        
        // Direkt és szerződés dokumentumokat kezeljük egybe
        $kepek = array_merge($kepek_direkt, $kepek);
        if( $kepek ) {
            
            // Nyitva-zárva megjegyzése
            $azon = 'dok_tipus_'.$tipus['id'].'_'.$_GET['id'];
            $dn = ' dn';
            if( $_SESSION['projekt_dok'][$azon] ) {
                $dn = '';
            }
            $tobbkep_html .= nl2('
                                <li class="nohover link" style="margin: 2rem 0rem 0.3rem 0" onclick="dokumentumok_kibont(\''.$azon.'\', \''.$apa_tabla.'\');">
                                    <h3>'.$tipus['info'].'</h3>
                                </li>
                                <li class="fejlec nohover '.$azon.$dn.'">
                                    <div class="felirat">Partner</div>
                                    <div class="felirat">Szerződés</div>
                                    <div class="kep">Fájlnév</div>
                                    <div class="felirat c"></div>
                                    <div class="felirat">Feltöltötte</div>
                                    <div class="felirat">Feltöltve</div>
                                    <div class="felirat">Minősítés törlése</div>
                                    <div class="felirat"></div>
                                    <div class="maradek torles">Jelölés</div>
                                </li>
                ');
        }
        foreach ( $kepek as $sor_kepek ) {
            $tobbkep_html .= get_tobbkep_thumb($sor_kepek, $i, 'projektszerzodes_dok', 'nincs törlés', $sor_kepek['projektszerzodes_id'], $azon.$dn );
            $i++;
        }
        
    }
}
    
$tobbkep_html .= '</ul></div>';

// Projektkezelés dokuemtnum felsorolásánál fix oszlopszélességekkel dolgozunk 
// (Az egészet becsomagoljuk egy DIV-be, amit megCSS-ezünk)
if( $apa_tabla == 'projektkezeles' ) {
    $tobbkep_html = nl2('
                        <link href="../css/projektkezeles_dok.css" rel="stylesheet" type="text/css">
                        <div class="projektkezeles_fixszel">'.$tobbkep_html.'</div>
                    ');
}

$szamla_html .= $tobbkep_html;





// A több képe thumbnailját hozza létre, egy adatbázis sorból
function get_tobbkep_thumb($sor_kepek = '', $i = '', $tobbkep_tablanev = '', $nincs_torles = '', $kulso_recid = '', $class = '') {

    global $archivum;
    global $adatbazis_kapcsolat;
    
    if( !$tobbkep_tablanev ) {
        $tobbkep_tablanev = $_POST['tabla'];
    }

    $javaslattetel = '';
    if( $_POST['megnevezes'] == 'javaslattetel_popup' ) {
        $tobbkep_tablanev   = 'munkalap_dok';
        $javaslattetel      = 'javaslattetel';
    }

    require_once('../weblap.php');
    $tobbkep_html = '';
    
    $apa_tabla = csonkit($tobbkep_tablanev, 4);

    $sor_kepek['utvonal'] = stripslashes(htmlspecialchars_decode($sor_kepek['utvonal']));
    $csonkit = 4;
    if( $archivum ) {
        $csonkit = 9;
    }
    // Jótállás esetén nem kell csonkítani a tábla nevén
    if( $apa_tabla == 'projektszerzodes_jota' ) {
        $csonkit = 0;
    }
    
    $direkt_dokumentum = 0;
    $kep_utvonal = '../'.get_utvonal().csonkit($tobbkep_tablanev, $csonkit).'/'.$sor_kepek['utvonal'];
    // Itt van egy kis csavar: ha projektkezelés alatt vagyunk, és a fájl nem található, akkor lehet az egy direkt csatolt dokumentum, ezért ez alapján is megpróbáljuk megjeleníteni a fájlt
    if( basename( self() ) == 'projektkezeles.php' && !is_file($kep_utvonal) ) {
        // Ekkor a $tobbkep_tablanev változót is korrigáljuk, mert a később pl. a Minősítés gomb kirajzolásánál jelentősége van.
        $tobbkep_tablanev  = 'projektkezeles_dok';
        // Illetve jelezzük, hogy ez egy direkt dokumentum, szintén a minősítéshez kell
        $direkt_dokumentum = 1;
        
        $kep_utvonal = '../'.get_utvonal().csonkit($tobbkep_tablanev, $csonkit).'/'.$sor_kepek['utvonal'];
    }

// A fájl sora akkor is jelenjen meg, ha már nem létezik, hogy a dev-be átmásolt adatok esetén ha nincs ott a fájl maga, attól még jelenjen meg.
//    if(is_file($kep_utvonal)){
        $felirat = utf8(stripslashes($sor_kepek['fajlnev']));
        $kep     = new Img($kep_utvonal, $felirat, 200, 200);

        // A fájl linkjében a szóközöket %20-ra cseréljük, hogy dupla szóközös fájllal is működjön.
        $kep_utvonal = str_replace(' ', '%20', htmlspecialchars($kep_utvonal));

        if( !$felirat ) {
            $felirat = '<span class="i link">- Nincs leírás -</span>';
        }

        // Eszközkezelés engedélyeknél dátumot is kiírunk
        $datum = '';
        if( basename(self()) == 'eszkozkezeles.php' && isset($_GET['engedelyek']) ) {
            $datum = '';
            if( $sor_kepek['kezdet'] != '0000-00-00' ) {
                $datum .= str_replace('-', '.', $sor_kepek['kezdet']);
            }
            $datum .= ' - ';
            if( $sor_kepek['veg'] != '0000-00-00' ) {
                $datum .= str_replace('-', '.', $sor_kepek['veg']);
            }
        // Egyébként az insertDt
        } else {
            $datum .= datum_kiir($sor_kepek['insert_dt']);
        }

        $sor_kepek['fajlnev'] = htmlspecialchars($sor_kepek['fajlnev']);

        $id_kieg = '';
        if( $_POST['megnevezes'] == 'javaslattetel_popup' ) {
            $id_kieg = '_jt_popup';
        }
        $azon = 'kep'.$id_kieg.$sor_kepek['recid'];

        // Csak az törölheti, aki feltöltötte (ha archivum, akkor meg már senki)
        $torles = '';
        $betolt = 'dokumentumok';
        if( isset($_GET['engedelyek']) ) {
            $betolt = 'engedelyek';
        }
        if( $sor_kepek['loginnev'] == $_SESSION[get_db_tabla().'user'] && !$archivum ) {

            // Nyelvtani szépség
            $a_az = a_az($sor_kepek['fajlnev']);
            
            // Projekteknél előfordulhat, hogy van típus oszlop. Ha van ilyen oszlopnak értéke, akkor már nem törölhető
            if( $sor_kepek['tipus'] ) {
                $torles = '<span class="icon link" onclick="$(\'#'.$azon.'\').addClass(\'torles_elott\'); alert2(\''.ucfirst($a_az).' '.addslashes($sor_kepek['fajlnev']).' dokumentum már minősítve van,<br>így nem törölhető!\', \'\', \'Hiba\')"><i class="material-icons" style="font-size: 2rem">delete</i></span>';
            // Általános eset, törlés ikon
            } else {
                $torles = '
                            <script>
                                dok_torol_gomb_'.$sor_kepek['recid'].' = \'<input type="button" class="gomb zold" value="Igen" onclick="mellekTorol(\\\''.$tobbkep_tablanev.'\\\', \\\''.$betolt.'\\\', \\\''.$javaslattetel.'\\\', \\\''.$sor_kepek['recid'].'\\\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\\\'.torles_elott\\\').removeClass(\\\'torles_elott\\\').removeClass(\\\'kijelolve\\\');">\';
                            </script>
                            <span class="icon link" onclick="$(\'#'.$azon.'\').addClass(\'torles_elott\'); alert2(\'Biztos, hogy törli '.$a_az.' '.addslashes($sor_kepek['fajlnev']).' dokumentumot?\', dok_torol_gomb_'.$sor_kepek['recid'].');"><i class="material-icons" style="font-size: 2rem">delete</i></span>
                    ';
            }
            
        }
        
        // Törlés lehetőségét mégis tiltjuk, ha nincs dokumentum kezelés jogunk
        if( $nincs_torles ) {
            $torles = '';
        }
        // Ha kaptunk egy külső recid-t, akkor annak az adatlapjára mutató linket állítjuk elő
        $ful = 'dokumentumok';
        $kulso_link = '';
        if( $kulso_recid ) {
            switch ( $apa_tabla ) {
                case 'projektszerzodes'         : $oszlop = 'sz_szam';          break;
                case 'projektszerzodes_jota'    : 
                                                    $oszlop     = 'sz_szam';
                                                    $apa_tabla  = 'projektszerzodes';
                                                    $ful        = 'jotallas';
                break;
                case 'projektszamla'            : $oszlop = 'szla_szam';        break;
            }
            
            $sor        = $adatbazis_kapcsolat->s2($oszlop, $apa_tabla, "`recid`='{$kulso_recid}'");
            $kulso_link = '<a href="'.$apa_tabla.'.php?id=x&amp;mezo='.$oszlop.'&amp;op=equal&amp;felt='.$sor[$oszlop].'&amp;'.$ful.'" target="_blank">'.$sor[$oszlop].'</a>';
        }

        // Típus oszlop
        $tipus_oszlop = '';
        if( tipusos_dokumentumok($apa_tabla) ) {
            $tipus_oszlop = '<div class="felirat">'.erteklista(ERTEKLISTA_DOK_TIPUS, $sor_kepek['tipus']).'</div>';
        }
        
        // Class
        $class_kiir = '';
        if( $class ) {
            $class_kiir = ' class="'.$class.'"';
        }
        
        // Projektszerződéses dokumentumok végén van minősítésre lehetőség (projektek felület alatt), ha még nem volna beállítva a típusa
        // Ilyenkor más class nevet kell adni a törlés oszlopnak, hogy ne essen szét a táblázat
        $minosites    = '';
        $torles_class = 'maradek';
        $tobbkep_tabla_kivetelek = array('projektkezeles_dok', 'projektszerzodes_dok', 'projektszerzodes_jotallas', 'projektszamla_dok');
        if( basename(self())=='projektkezeles.php' && in_array($tobbkep_tablanev, $tobbkep_tabla_kivetelek) ) {
            
            // Jótállások alatt nincs minősítés gomb
            $minosites = '<div class="felirat"></div>';
            $nem_minositeses = array('projektszerzodes_jotallas', 'projektszamla_dok');
            if( !in_array($tobbkep_tablanev, $nem_minositeses) ) {
                // Ha szerződésről van szó, és nincs még minősítve, csak akkor jelenítjük meg a minősítést gombot
                $torles_class = 'felirat';
                // Direktbe feltöltött dokumentumok minősítés gombja más (számla típus ki van szűrve)
                if( $direkt_dokumentum && !$sor_kepek['tipus'] ) {

                    $minosites = nl2('
                        <div class="felirat l">
                            <input type="button" class="gomb alacsony" value="Minősítés" onclick="dok_minosites(\''.$sor_kepek['recid'].'\', \'szűrt\')">
                        </div>
                        ');
                // Nem direkt dokumentum minősítés (itt megengedett a számla típus)
                } else if( !$direkt_dokumentum && !$sor_kepek['tipus'] ) {
                    $minosites = nl2('
                        <div class="felirat l">
                            <input type="button" class="gomb alacsony" value="Minősítés" onclick="dok_minosites(\''.$sor_kepek['recid'].'\')">
                        </div>
                        ');
                // Ha nem minősíthető egy dokumentum (mert már minősítve van), akkor meg a minősítés törölhető róla
                } else {
                    $minosites = nl2('
                                    <div class="felirat l">
                                        <input type="button" class="gomb alacsony" value="Törlés" onclick="minosites_torol(\''.$sor_kepek['recid'].'\', \''.$tobbkep_tablanev.'\')">
                                    </div>
                                    ');
                }
            }
            
            // Partner
            $partner        = '';
            $szallito_id    = getErtek('szallito_id', $apa_tabla, 'recid', $kulso_recid);
            $partner        = getErtek('nev', 'szallito', 'id', $szallito_id);

            // Checked
            $checked = '';
            if( !$_SESSION['projekt_dok_kuld_check'] ) {
                $_SESSION['projekt_dok_kuld_check'] = array();
            }
            if( in_array($sor_kepek['recid'], $_SESSION['projekt_dok_kuld_check']) ) {
                $checked = ' checked';
            }
            
            // Ha nincs projekt kezelés jog, akkor dokumentumot nem lehet küldeni, emiatt a kijelölést is elrejtjük
            $checkbox = '';
            if( _jog(JOG_PRKEZEL_KEZEL) ) {
                $checkbox = nl2('
                        <div class="cb jobbra">
                            <input type="checkbox" class="check dok_jeloles" onclick="projekt_dok_check(\''.$sor_kepek['recid'].'\', $(this).is(\':checked\'))" id="jeloles_'.$azon.'"'.$checked.'>
                            <label for="jeloles_'.$azon.'"></label>
                        </div>
                    ');
            }
            
            // Projektek alatt a származtatott dokumentumok oszlopsorrendje eltér az általános esettől
            $tobbkep_html .= nl2('
                <li id="'.$azon.'" title="'.$sor_kepek['megj'].'" data-id="'.$sor_kepek['recid'].'" data-i="'.$i.'"'.$class_kiir.'>
                    <script>gomb'.$azon.' = get_tobbkep_megj_szerk_gomb(\''.basename(__FILE__).'\', \''.$azon.'\', \''.$sor_kepek['recid'].'\');</script>
                    <div class="felirat">'.$partner.'</div>
                    <div class="felirat">'.$kulso_link.'</div>
                    <div class="kep">
                        <div>
                            <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés">
                                '.$kep -> getHtml().'
                            </a>
                        </div>
                    </div>
                    <div id="megj_'.$azon.'_felirat" class="felirat c">
                        <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés" class="fajlnev">
                            '.mb_wordwrap($felirat, 20, '<br>', true, 'UTF-8').'
                        </a>
                    </div>
                    <div class="felirat">'.$sor_kepek['insert_usr'].'</div>
                    '.$tipus_oszlop.'
                    <div class="felirat">'.$datum.'</div>
                    '.$minosites.'
                    <div class="felirat">'.$torles.'</div>
                    <div class="maradek" style="width: 6rem">
                        '.$checkbox.'
                    </div>
                </li>
            ');
            
        // Általános dokumentum felsorolás
        } else {
            
            $tobbkep_html .= nl2('
                <li id="'.$azon.'" title="'.$sor_kepek['megj'].'" data-id="'.$sor_kepek['recid'].'" data-i="'.$i.'"'.$class_kiir.'>
                    <script>gomb'.$azon.' = get_tobbkep_megj_szerk_gomb(\''.basename(__FILE__).'\', \''.$azon.'\', \''.$sor_kepek['recid'].'\');</script>
                    <div class="kep">
                        <div>
                            <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés">
                                '.$kep -> getHtml().'
                            </a>
                        </div>
                    </div>
                    <div id="megj_'.$azon.'_felirat" class="felirat c" style="width: 30rem">
                        <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés" class="fajlnev">
                            '.mb_wordwrap($felirat, 20, '<br>', true, 'UTF-8').'
                        </a>
                    </div>
                    <div class="felirat">'.$sor_kepek['insert_usr'].'</div>
                    '.$tipus_oszlop.'
                    <div class="felirat">'.$datum.'</div>
                    <div class="'.$torles_class.' torles">'.$torles.'</div>
                    '.$minosites.'
                </li>
            ');
            
        }

//    }
    return $tobbkep_html;

}

// Általános függévény, ami eldönti adott tábla alapján,hogy van-e oda kezelés jogunk-e vagy sem.
// Ha nincs, akkor nincs törlés ikon a már feltöltött dokumentum sorok végén, illetve nincs feltöltés lehetőség sem,
// sőt feltöltés résznél is blokkolunk.
function dokumentum_kezeles_jog($tabla, $id) {
    
    global $adatbazis_kapcsolat;
    
    // Bizonyos felületeken fixen nincs szerkesztés jog
    $fixen_nincs = array('anyag_szamla_dok', 'energia_szamla_dok', 'fenntartasi_szamla_dok');
    if( in_array($tabla, $fixen_nincs) ) {
        return false;
    }
    
    // Fenntartási szerződéseknél csak akkor nincs, ha van andoc azonosítónk (hisz akkor csak az XML-ből kapott adatokat jelenítjük meg)
    // Viszont a manuálisan felvitt szerződések miatt néha meg kell a kezelés jog
    if( $tabla == 'fenntartasi_szerzodes_dok' ) {
        $szerzodes = $adatbazis_kapcsolat -> s2('andoc_id', 'fenntartasi_szerzodes', "`recid`='{$id}'");
        if( $szerzodes['andoc_id'] ) {
            return false;
        }
    }
    
    // Projektigénylések elutasítva és jóváhagyva státuszainál
    if( $tabla == 'projektigenyles_dok' ) {
        $pri = $adatbazis_kapcsolat->s2('stat_id', 'projektigenyles', "`recid`='{$id}'");
        if( $pri['stat_id'] == PRIGENY_STAT_ELUTASITVA || $pri['stat_id'] == PRIGENY_STAT_JOVAHAGYVA ) {
            return false;
        }
    }
    // Projektkezeléskor ha nincs hozzá jogunk
    if( $tabla == 'projektkezeles_dok' ) {
        if( !_jog(JOG_PRKEZEL_KEZEL) ) {
            return false;
        }
        // Ha a projekt zárt, akkor már nem lehet dokumentumokat hozzáadni, törölni
        $prj = $adatbazis_kapcsolat->s2('stat_id', 'projektkezeles', "`recid`='{$id}'");
        if( $prj['stat_id'] == PRKEZEL_STAT_LEZART ) {
            return false;
        }
    }
    
    return true;
}

// WordWrap kiegészítő függvény, ékezetes tartalommal is működjön
function mb_wordwrap($str, $width = 75, $break = "\n", $cut = false, $charset = null) {
    if ($charset === null) $charset = mb_internal_encoding();

    $pieces = explode($break, $str);
    $result = array();
    foreach ($pieces as $piece) {
      $current = $piece;
      while ($cut && mb_strlen($current) > $width) {
        $result[] = mb_substr($current, 0, $width, $charset);
        $current  = mb_substr($current, $width, 2048, $charset);
      }
      $result[] = $current;
    }
    return implode($break, $result);
}
