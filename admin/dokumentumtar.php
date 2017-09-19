<?php
require_once('../weblap.php');
//Config
$megnevezes             = pathinfo(self());
$megnevezes             = $megnevezes['filename'];
$akt_tomb               = $szamlak_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'dokumentumot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

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

if( $_POST ) {
    echo '<pre style="font-size: 1.4rem">', print_r($_POST), '</pre>';
    exit();
}

//Több kép menedzseléséhez
$tobb_kep = false; // Ha true, akkor js/jquery-ui.js, js/admin_sorrendez.js aktivál és minify!, admin/tobb_kep.php aktivál
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return 'true';    } // Lényeges, hogy egy string legyen
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

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
        echo '<div id="szamlaBetolt">';
    }


    if( isset($_GET['modosit']) || isset($_GET['szamlak']) || isset($_GET['dokumentumok']) || isset($_GET['megjegyzesek']) ) {
        /* Számla fülek */
        $ful_tomb = array(
            'modosit'       => 'Adatlap',
            'szamlak'       => 'Számlatételek',
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

	//Új termék felvitele
	if(isset($_GET['konyvtar'])){
        
        $fajlok_html = '';
        list($tabla, $konyvtar) = explode('/', $_GET['konyvtar']);
        $fajlok = $adatbazis_kapcsolat->s('recid kepnev megj', $tabla.'_dok', "`kepnev` LIKE '$konyvtar%'", 'kepnev');
        foreach($fajlok as $fajl){
            
            $kep_utvonal = '../'.get_utvonal().$tabla.'/'.$fajl['kepnev'];
            if(is_file($kep_utvonal)){

                $felirat = utf8($fajl['megj']);
                $kep     = new Img($kep_utvonal, $felirat, 200, 200);

                if( !$felirat ) {
                    $felirat = '<span class="i link">- Nincs leírás -</span>';
                }

                $azon    = 'kep'.$fajl['recid'];
                //$megj_szerkesztes_onclick = ' onclick="window.open(\''.$kep_utvonal.'\')"';
                $fajlok_html .= nl2('
                    <li id="'.$azon.'" class="kijSor" title="'.$fajl['megj'].'" data-id="'.$fajl['recid'].'" data-i="'.$i.'">
                        <script>gomb'.$azon.' = get_tobbkep_megj_szerk_gomb(\''.basename(__FILE__).'\', \''.$azon.'\', \''.$fajl['recid'].'\');</script>
                        <div class="kep">
                            <div>'.$kep -> getHtml().'</div>
                        </div>
                        <div id="megj_'.$azon.'_felirat" class="felirat f12 link c"'.$megj_szerkesztes_onclick.'>'.$felirat.'</div>
                        <div class="nagyit">
                            <a href="'.$tabla.'.php?dokumentumok" target="_blank" title="Ugrás menüpontra">
                                <i class="material-icons">list</i>
                            </a>
                            <a href="'.$kep_utvonal.'" target="_blank" title="Letöltés">
                                <i class="material-icons">file_download</i>
                            </a>
                        </div>
                    </li>
                ');

            }            

        }
        if($fajlok_html){
            $fajlok_html = '<ul id="sorrendezes_tobbkep">'.$fajlok_html.'</ul>';
        }
        
        echo $fajlok_html;
    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
