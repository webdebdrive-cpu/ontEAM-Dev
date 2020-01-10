<?php
/*
Játékszabályok:

- Két eset van: Egy bejelentés egy munkalap illetve egy bejelentés több munkalap
- Ha egy munkalapot csoportosítunk, akkor a benne levő munkalapok automatikusan várakozóra is kerülnek
  (és áttételesen a hozzá tartozó bejelentés is).
- Ha kiveszünk belőle egy elemet, akkor annak státusza nyitottra vált (és áttételesen a hozzá tartozó bejelentés is).
- Csoportot lehessen egyben visszatenni nyitottnak, de ekkor nem bomlik fel a csoport.

*/

require_once('../weblap.php');


if( !_jog(JOG_BEJELENTES_MEGTEKINT) && !_jog(JOG_BEJELENTES_SAJAT_RESZL) ) {
    if(!$_SESSION[get_db_tabla().'user']){
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
$megnevezes_origi       = substr($megnevezes, 0, strlen($megnevezes)-5);
$akt_tomb               = $bejelentes_arch_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'bejelentést';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = true;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '';        } // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes_origi;
        return $megnevezes_origi.'_id';
    }
    function get_tobbkep_tabla(){ return 'bejelentes_arch_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!
}

// CSV generálása
if (isset($_GET['csv'])) {

    // Progresshez szükséges fejlécek
    header('X-Accel-Buffering: no');
    header('Content-Type: text/octet-stream');
    header('Cache-Control: no-cache');

    $mibol   = get_web_charset();
    $mibe    = 'windows-1250';

    $target  = 'export';

    if( !is_dir($target) ) {
        mkdir($target);
    }

    $target .= '/csv';

    if( !is_dir($target) ) {
        mkdir($target);
    }

    $filename = $target.'/'.$_POST['filename'];

    // Open the output stream
    $open = fopen($filename, 'w');
    fwrite($open, "sep=;\n");

    if( $open ) {

        $lekerdezes_fromtol = substr($_SESSION['akt_sql'], strpos($_SESSION['akt_sql'], 'FROM'));

        // Eredetileg csak a bal navpanelben is megjelenő sorokat listázzuk ki (cél, hogy illetéktelen sorokhoz ne lehessen hozzáférni).
        // Viszont ha érvényben volna egy LIMIT is az aktuális SQL-ben, akkor ezt az ID ellenőrzést mégsem tesszük meg (mert van egy alapértelmezett 1000-es lista limit,
        // de ha több sor is van adott feltételre, akkor az CSV-ben minden sort látni szeretnénk.
        if( $_SESSION['nyomtatas_ids'] && strpos( $_SESSION['akt_sql'], 'LIMIT' )===false ) {
            if( strstr($_SESSION['akt_sql'], 'WHERE') ) {
                $lekerdezes_fromtol = str_replace('WHERE', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") AND ", $lekerdezes_fromtol);
            } else {
                $lekerdezes_fromtol = str_replace('ORDER', "WHERE `recid` IN (".$_SESSION['nyomtatas_ids'].") ORDER", $lekerdezes_fromtol);
            }
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
        
        $sql = 'SELECT
                `ikt_szam` as \'Bejelentés szám\',
                `'.get_db_tabla().$megnevezes.'`.`telephely_id` as \'Szálloda\',
                `hiba_id` as \'Bejelentett hiba\',
                `hiba_text` as \'Hiba leírása\',
                `obj_txt` as \'Helyiség\',
                `helyiseg_text` as \'Hely leírása\',
                `gep_id` as \'Gépnév\',
                `stat_id` as \'Státusz\',
                `'.get_db_tabla().$megnevezes.'`.`insert_dt` as \'Bejelentés dátuma\'
                '.$lekerdezes_fromtol.$_SESSION['akt_sql_order'];
        $query      = mysqli_query($db_kapcs, $sql);

        $csvhead    = array();
        while($fejlec_nevek = mysqli_fetch_field($query)){
            $csvhead[] = iconv2($mibol, $mibe, $fejlec_nevek -> name );
        }
        fputcsv($open, $csvhead, ';');

        // Összes sor
        $osszes_sor = mysqli_num_rows($query);

        $i = 0;
        while( $sorok = mysqli_fetch_assoc($query) ) {

            $csvcont = array();
            foreach( $sorok as $kulcs => $sor ) {
                if($kulcs=='Szálloda'){
                    $sor = getSzallodaNev($sor);
                }
                if($kulcs=='Bejelentett hiba'){
                    $sor = getErtekLista('', '', $sor);
                }
                if($kulcs=='Gépnév'){
                    $sor = $adatbazis_kapcsolat->s2('nev', 'eszkozkezeles', "`recid`='{$sor}'");
                    $sor = $sor['nev'];
                }
                if($kulcs=='Státusz'){
                    $sor = getStatText('bejelentes', $sor);
                }
                if($kulcs=='Bejelentés dátuma'){
                    $sor = datum_kiir($sor);
                }
                
                // Lehet nem volna elég 1-szer sőt kétszer sem ráereszteni a htmlspecialchars_decode-ot
                $sor = htmlspecialchars_decode( $sor );
                while(strpos($sor, '&amp;')!==false || strpos($sor, '&quot;')!==false ){
                    $sor = htmlspecialchars_decode( $sor );
                }
                $csvcont[] = iconv($mibol, $mibe.'//TRANSLIT', strip_tags( $sor ) );
            }

            fputcsv($open, $csvcont, ';');

            // 100 soronként kiírtatunk
            if($i % 1000 == 0 && $i > 0) {
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

    // Get the contents of the output buffer
    fclose($fh);

    echo json_encode(array(
        'filename' => $filename
    ));

    // Felszabadítás
    ob_flush();
    flush();
    
    $weblap->leallit();
    exit();
}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt" data-recid="'.$_GET['id'].'" data-page="'.str_replace('.php', '', basename(self())).'">';
    }

    // Az adatlap adatait már a legelején lekérjük
    if( $_GET['id'] ){
        $sor = $adatbazis_kapcsolat -> s2('*', $megnevezes, "`recid`='{$_GET['id']}'");
    }

    if($sor['dok_db']){
        $dok_db  = ' ('.$sor['dok_db'].')';
    }
    if($sor['megj_db']){
        $megj_db = ' ('.$sor['megj_db'].')';
    }

    if( isset($_GET['modosit']) || isset($_GET['tortenet']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) || isset($_GET['munkalapok']) ) {
        // Számla fülek
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'tortenet'      => 'Történet',
            'munkalapok'    => 'Munkalapok',
            'dokumentumok'  => 'Dokumentumok'.$dok_db,
            'megjegyzesek'  => 'Megjegyzések'.$megj_db
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

    // Gombok nincsenek
    $gombok = '<div class="muveletek"></div>';

    $js_submit_elott = 'return form_ell_admin();';

    if( isset($_GET['modosit']) ) {

        // Ha Gyorskeresőből érkeznénk ide, akkor az ID-t valójában az id_hidden tartalmazza majd
        if(isset($_GET['id_hidden'])){
            $_GET['id'] = $_GET['id_hidden'];
        }

        foreach ( $akt_tomb as $tomb ) {
            if( $tomb['type'] == 'autocomplete' ) {

                if($tomb['csoport']){
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
        foreach($sor as $kulcs => $elem){
            $json_sor[$kulcs] = formAdatok_replace($elem);
        }
        $szamla_html .= '<script> formAdatok = JSON.parse(\''.json_encode($json_sor).'\'); </script>';

		// Form actionja
		$action = self().get_get(array('errnum','kephiba','siker'));

		// Ez elkészíti a $mezok változót
		require('switch.php');

        $jobb_oldal = '';

	    $szamla_html .= str_replace(
            array('{JS}', '{JS_SUBMIT_ELOTT}', '{ACTION}', '{MEZOK}', '{MEGNEVEZES}', '{KEP}',     '{CK_EDITOR}', '{GOMBOK}'    ),
            array( $js,   $js_submit_elott,    $action,    $mezok,    $megnevezes,    $jobb_oldal, $ckeditor,     $gombok       ),
            fajlbol_olvas('../sablon/admin/form_modosit_arch.html', true)
        );

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom'  => $szamla_html,
                'link'      => get_get(array('ajax', 'kat', 'epo', 'oldal', 'order'), true)
            ));
        } else {
            echo $szamla_html;
        }

	// Történet fül
    }elseif( isset($_GET['tortenet']) ) {

        $szamla_html .= get_tortenet($megnevezes);

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }

	// Munkalapok fül
    } elseif( isset($_GET['munkalapok']) ) {

        $szamla_html .= nl2('
            <div class="dt naplo kijCont" data-tabla="munkalap_kapcs" data-mellek="munkalapok">
                <div class="dtr nFejlec">
                    <div class="dtc">Munkalap szám</div>
                    <div class="dtc">Szálloda</div>
                    <div class="dtc">Létrehozva</div>
                    <div class="dtc">Státusz</div>
                </div>
        ');

        $munkalap_kapcsok = $adatbazis_kapcsolat->s('recid munkalap_id', 'munkalap_kapcs_arch', "`bejelentes_id` = '{$_GET['id']}'");
        if( $munkalap_kapcsok ) {
            $i = 1;
            foreach($munkalap_kapcsok as $munkalap_kapcs) {

                // Csak akkor látszik a munkalap, ha az nyitott
                $sor = $adatbazis_kapcsolat->s2('recid ikt_szam telephely_id obj_id insert_dt stat_id', 'munkalap_arch', "`recid` = '".$munkalap_kapcs['munkalap_id']."'", '', '1');
                if( $sor ) {

                    // Telephely
                    $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$sor['telephely_id'], '', '1');
                    $telephely = $telephely[0]['nev'];

                    // Helység
                    $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$sor['obj_id'], '', '1');
                    $helyseg = $helyseg[0]['nev'];

                    $ikt_szam = '<a href="munkalap_arch.php?mezo=ikt_szam&amp;op=equal&amp;felt='.$sor['ikt_szam'].'&amp;modosit" target="_blank">'.$sor['ikt_szam'].'</a>';
                    if( !_jog(JOG_MUNKALAP_MEGTEKINT) && !_jog(JOG_MUNKALAP_MEGTEKINT_SZALL) && !_jog(JOG_MUNKALAP_MEGTEKINT_FELADAT) ){
                        $ikt_szam = $sor['ikt_szam'];
                    }

                    $szamla_html .= nl2('
                        <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                            <div class="dtc">'.$ikt_szam.'</div>
                            <div class="dtc">'.$telephely.' - '.$helyseg.'</div>
                            <div class="dtc">'.datum_kiir($sor['insert_dt']).'</div>
                            <div class="dtc">'.getStatText('munkalap', $sor['stat_id']).'</div>
                        </div>
                    ');
                }

                $i++;
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


    // Dokumentumok fül
    }elseif( isset($_GET['dokumentumok']) ) {

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

    // Megjegyzés
    }elseif(isset($_GET['megjegyzesek'])) {

        // <div class="dt naplo" class nevekből a kijCont kivéve, hogy a megjegyzéseket ne lehessen törölni
        $szamla_html .= nl2('
            <div class="dt naplo" data-tabla="megjegyzes" data-mellek="megjegyzesek">
                <div class="dtr nFejlec">
                    <div class="dtc">Megjegyzés</div>
                    <div class="dtc">Hozzáadta</div>
                    <div class="dtc">Hozzáadva</div>
                </div>
        ');

        $sorok = $adatbazis_kapcsolat->s('recid megj szerepkor insert_usr insert_dt', 'megjegyzes_arch', "`id` = '".$_GET['id']."' AND `tabla` = '".$megnevezes_origi."'", 'recid desc');
        if( $sorok ) {
            $i = 1;
            foreach($sorok as $sor) {
                $szamla_html .= nl2('
                    <div id="naploSor-'.$sor['recid'].'" class="dtr nSor kijSor" data-id="'.$sor['recid'].'" data-i="'.$i.'">
                        <div class="dtc">'.htmlspecialchars_decode( nl2br($sor['megj']) ).'</div>
                        <div class="dtc">'.$sor['insert_usr'].' '.getSzerepkorNev($sor['szerepkor']).'</div>
                        <div class="dtc">'.str_replace('-', '.', substr($sor['insert_dt'], 0, 16)).'</div>
                    </div>
                ');

                $i++;
            }
        }

        if( $_GET['ajax'] ) {
            echo json_encode(array(
                'tartalom' => $szamla_html.$gombok
            ));
        } else {
            echo $szamla_html.$gombok;
        }
    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
