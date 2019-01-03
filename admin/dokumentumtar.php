<?php
require_once('../weblap.php');

if( !_jog(JOG_DOKUMENTUMTAR_OLVAS) ) {
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
$akt_tomb               = $szamla_tomb;
$torles                 = $megnevezes.'tid';
$szoveg                 = 'dokumentumot';        //Hibaüzenetkor: Biztosan törlöd a $szoveg?

$szamla_html = '';

// Dokumentumok fülhöz kell majd
$tobb_kep = false;
if($tobb_kep){
    function get_tobbkep_darab()    { return 10;        } // Maximum hány kép tallózható be
    function get_tobbkep_csakkep()  { return '.jpg,.png';} // Milyen kiterjesztéseket engedünk meg (ha mindenet, legyen üres)
    function get_tobbkep_kepmeret() { return 10;        } // MB-ban
    function get_tobbkep_oszlopnev(){                     // Melyik oszloppal kötjük össze a két táblát
        global $megnevezes;
        return $megnevezes.'_id';
    }
    function get_tobbkep_tabla(){ return str_replace('.php', '', basename(self())).'_dok'; } // A képeket tartalmazó adatbázis tábla neve FONTOS: ha itt átnevezed akkor a tobbkep.php-ban is át kell írni!

}

// Tartalom
if( !$_GET['ajax'] ) {
    require_once('header.php');
}
require_once('selectek.php');

    if( !$_GET['ajax'] ) {
        echo '<div id="szamlaBetolt">';
    }


    if( isset($_GET['modosit']) || isset($_GET['szamlak']) || isset($_GET['dokumentumok']) ) {
        // Számla fülek
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

            $fulek .= '<a href="javascript: szamlaBetolt(\''.$_GET['id'].'\', \''.basename(self()).'\', \''.$key.'\'); void(0);"'.$active.' data-oldal="'.$key.'">'.$value.'</a>';
        }

        $szamla_html .= str_replace(
            array('{FULEK}'),
            array( $fulek  ),
            fajlbol_olvas('../sablon/admin/szamla_fulek.html', true)
        );
    }

	// Új termék felvitele
	if(isset($_GET['konyvtar'])){

        $fajlok_html = '';
        list($tabla, $konyvtar) = explode('/', $_GET['konyvtar']);
        $post   = '_dok';
        $apa_id = $tabla.'_id';
        
        // Projektigénylés véleményél nincs _dok postfix
        if($tabla=='projektigenyles_velemeny'){
            $post   = '';
            $apa_id = 'projektigenyles_recid';
        }
        $fajlok = $adatbazis_kapcsolat->s('recid '.$apa_id.' utvonal fajlnev', $tabla.$post, "`utvonal` LIKE '$konyvtar%'", 'fajlnev');
        foreach($fajlok as $fajl){

            $kep_utvonal = '../'.get_utvonal().$tabla.'/'.$fajl['utvonal'];
            if(is_file($kep_utvonal)){

                $felirat = utf8($fajl['fajlnev']);
                $kep     = new Img($kep_utvonal, $felirat, 200, 200);

                if( !$felirat ) {
                    $felirat = '<span class="i link">- Nincs leírás -</span>';
                }

                $azon    = 'kep'.$fajl['recid'];
                // $megj_szerkesztes_onclick = ' onclick="window.open(\''.$kep_utvonal.'\')"';
                $link = $tabla.'.php?id='.$fajl[$apa_id].'&amp;dokumentumok';
                if($tabla=='projektigenyles_velemeny'){
                    $link = 'projektigenyles.php?id='.$fajl[$apa_id].'&amp;velemenyezok';
                }
                $fajlok_html .= nl2('
                    <li id="'.$azon.'" class="nohover" title="'.$fajl['megj'].'" data-id="'.$fajl['recid'].'" data-i="'.$i.'">
                        <div class="kep">
                            <div>
                                <a href="'.$kep_utvonal.'" target="_blank">
                                    '.$kep -> getHtml().'
                                </a>
                           </div>
                        </div>
                        <div id="megj_'.$azon.'_felirat" class="felirat f12 c"'.$megj_szerkesztes_onclick.'>
                            <a href="'.$kep_utvonal.'" target="_blank">
                                '.$felirat.'
                            </a>
                        </div>
                        <div class="nagyit">
                            <a href="'.$link.'" target="_blank" title="Ugrás menüpontra">
                                <i class="material-icons">list</i>
                            </a>
                        </div>
                    </li>
                ');

            }

        }
        if($fajlok_html){
            $fajlok_html = '<ul id="sorrendezes_tobbkep">'.$fajlok_html.'</ul>';
        } else {
            $fajlok_html = 'üres mappa';
        }

        echo $fajlok_html;
    }

    if( !$_GET['ajax'] ) {
        echo '</div>';
    }

if( !$_GET['ajax'] ) {
    require_once('footer.php');
}
