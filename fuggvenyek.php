<?php
/*                                                                                                                          *\
    Ide írd meg a saját, globális függvényeidet. Mivel ez a header() előtt meghívódik, kimenetele a fájlnak ne legyen.
\*                                                                                                                          */







/**
*	Insert & Update
*
*	@param string $type: 'insert' vagy 'update'
*	@param string $tabla: a beszúrandó tábla neve 'elotag_' nélkül
*	@param array $tomb: beszúrandó étékek. Vigyázat, egyeznie kell a megfelelő szerkezettel:
*	name: $POST[name] (tehát érték) és az adatbázis tábla neve
*	alert:  ha nem üres akkor visszaadja a sorsámot, hogy hol akadt el
*
*	@return array: első része a $errnum második pedig a beszúrt vagy módosított sor id-je
*/
function i_u($type, $tabla, $tomb){
	global $adatbazis_kapcsolat;

    $nevid   = '';

    $oszlopok = array();
    $ertekek  = array();

	$i=0;
	foreach($tomb as $ertek){//értékek párosítása

		//Ha alert-es egy mező, és a post-ja üres, akkor hiba! (nem adott meg egy kötelező mezőt)
        if($ertek['alert'] != '' && $_POST[$ertek['name']] == '' && !$ertek['disabled']) {
			return array($i, '');
			break;
		}

        //Ha az adott esemenyek.php tomb sora tartalmaz nevid jelzést, akkor ezt a sort eltároljuk, mert ennek a nevéből gyártuk névid-t
        if($ertek['nevid'] == true){
			$nevid = $ertek;
		}

		//Wysiwyg útvonalszépítés a feltöltött képekre, HA van egyáltalán fájlfeltöltés
		if($ertek['wysiwyg'] && $ertek['feltoltes']){
            $_POST[$ertek['name']] = str_replace('wysiwyg/ckfinder/core/connector/php/../../../../../', '', $_POST[$ertek['name']]);
        }

        //Ha felhasználó adatlapját módosítjuk, és van megadva jelszó, akkor lecseréljük a mostanit
        if($tabla=='felh' && $ertek['name']=='reg_jelsz' && $_POST[$ertek['name']]){
            $_POST[$ertek['name']] = sha1($_POST[$ertek['name']]); //sha1 helyett más elkódolást is használhatnánk, de ez javasolt. A lényeg, hogy legyen összhangban a class.Felhasznalo konfigjával
        }

		//Ha nem gomb, nem fájl és nem disabled és az oszlopnév sem üres és maga a POST egyáltalán létezik is (ne nullázzunk felül el nem küldött POST miatt)
		if($ertek['type'] != 'submit' && $ertek['type'] != 'button' && $ertek['type'] != 'file' && $ertek['type'] != 'flashfile' && $ertek['type'] != 'jsfile' && !($ertek['disabled'] && $type == 'update'/*Tiltott mezőket beszúrni lehet csak moddolni nem*/) && $ertek['name'] && isset($_POST[$ertek['name']]) ){
			array_push($oszlopok, $ertek['name']);
			array_push($ertekek, trim($_POST[$ertek['name']]));
		}

		$i++;
	}

	if($type == 'insert') {
		$id = $adatbazis_kapcsolat -> insert($tabla, $oszlopok, $ertekek, 'recid='.$_GET['id']);

        /* Kezdeményezett művelet */
        $muvelet = 'A(z) '.$tabla.'-ba új recordot szúrt be a következő azonosítóval: '.$id.'.';

        /* Végrehajtott változtatás */
        $valtozas = '1.: A(z) <strong>recid</strong> mező a következő értéket kapta: <strong>'.$id.'</strong><br>';

        $i = 2;
        foreach($_POST as $key => $value) {
            if( $key != 'mehet_gomb' && $key != 'uj'.$tabla ) {
                $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező a következő értéket kapta: <strong>'.$value.'</strong><br>';

                $i++;
            }
        }

        $adatbazis_kapcsolat -> i(
            'log',
            array( 'felh_id',                          'nev',       'valtozas'),
            array( $_SESSION[get_db_tabla().'user_id'], $muvelet,   $valtozas )
        );

	} else {
        /* Ha volt változás, akkor logoljuk */
        $record = $adatbazis_kapcsolat->s('*', $tabla, 'recid='.$_GET['id'], '', '1');

        if( $record ) {
            /* Kezdeményezett művelet */
            $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordját módosította.';

            /* Végrehajtott változtatás */
            $valtozas = '';

            $i = 1;
            foreach($record[0] as $key => $value) {
                if( $key != 'recid' && $_POST[$key] ) {
                    if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ) {
                        $value = date('Y.m.d', strtotime($value));
                    }

                    if( $_POST[$key] != $value ) {
                        $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező értékét <strong>"'.$value.'"</strong>-ról <strong>"'.$_POST[$key].'"</strong>-ra változtatta.<br>';

                        $i++;
                    }
                }
            }

            $adatbazis_kapcsolat -> i(
                'log',
                array( 'felh_id',                          'nev',       'valtozas'),
                array( $_SESSION[get_db_tabla().'user_id'], $muvelet,   $valtozas )
            );
        }
        echo $valtozas;
        exit();

		$id = $adatbazis_kapcsolat -> update($tabla, $oszlopok, $ertekek, 'recid='.$_GET['id']);
	}
//echo $adatbazis_kapcsolat -> getParancs();

	if(isset($_GET['id']) && $type == 'update'){
		$id=$_GET['id'];
	}

	if($nevid != '' && $type == 'insert'){//Ha kell, akkor beállítjuk még a nevid-s oszlopot

		$nevid = fajlnev_keszit($_POST[$nevid[name]]);
		if(!$nevid){
            $nevid = 'noname';
        }
        $nevid_tomb = array();
        $nevidk = $adatbazis_kapcsolat -> s('nevid', $tabla, '`nevid` LIKE \''.$nevid.'%\'');
        foreach($nevidk as $nev_id){
            array_push($nevid_tomb, $nev_id['nevid']);
        }

        while(in_array($nevid, $nevid_tomb)){
            $nevid = fajlnev_sorszamoz($nevid);
        }

		$adatbazis_kapcsolat -> u($tabla, 'nevid', $nevid, 'id='.$id);
	}

	return array('', $id);
}


/**
*	JS alapú hibaüzenetket generál egy tömb alapján (alert2)
*
*	@param array:  mely tomb alapján generáljon (az alert miatt lényeges)
*	@param string: mi legyen a form ellenőrző függvény neve?
*	@param bool:   lehet-e ajaxos vagy hagyományos?
*/
function js_kiir($tomb, $fg = 'form_ell_regi()', $ajax = false){

    $ret = '<'.str_replace('#', '', 's#cript').'>function '.$fg.'{';

    $param = '';
    foreach($tomb as $ertek){

        if($ertek['name']){

            //Csak akkor figyelünk lenti paramétereket, ha egyáltalán az elem létezik. Ezzel egy csomó JS hibát elekerólhetünk és debuggolós szívásokat
            $ret .= 'if($(\'#'.$ertek['name'].'\').length){ ';

            if($ertek['alert']!=''){
                $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; alert2(\''.$ertek['alert'].'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false; }';
            }

            if($ertek['mail']){
                $ret .= "str=document.getElementById('$ertek[name]').value.trim(); if(str){ filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i; if (!filter.test(str)){ document.getElementById('$ertek[name]').focus(); document.getElementById('$ertek[name]').className='alert'; alert2('Helytelen e-mail cím!'); scrollto('#$ertek[name]', 500); return false; } }";
            }

            if($ertek['name'] == 'jelsz2'){
                $ret .= 'if(document.getElementById(\'reg_jelsz\').value.length < 4 ){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszó hossza minimum 4 karakter legyen!\');return false;}';
                $ret .= 'if(document.getElementById(\'jelsz2\').value != document.getElementById(\'reg_jelsz\').value){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszavak nem egyeznek meg!\');return false;}';
            }

            if($ertek['name'] == 'isz' && $ertek['alert']){
                $ret .= 'if(document.getElementById(\'isz\').value.length != 4){document.getElementById(\'isz\').className += \' alert\';alert2(\'Helytelen irányítószám!\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
            }

            if($ertek['name'] == 'sz_isz' && $ertek['alert']){
                $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
            }

            if($ertek['name'] == 'szall_isz' && $ertek['alert']){
                $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
            }

            //Addószám formátumának ellenőrzése (xxxxxxxx-x-xx alakú-e). Az inputra egyébként a main.js-ben található adoszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
            if($ertek['name'] == 'ado'){
                $ret .= nl2('
                    reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                    if( reszek[0].length!=8 || reszek[1].length!=1 || reszek[2].length!=2){
                        document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                        alert2(\'Az adószám formátuma helytelen!\');
                        scrollto(\'#'.$ertek['name'].'\', 500);
                        return false;
                    }
                    ');
            }

            //Addószám formátumának ellenőrzése (xx-xx-xxxxxx alakú-e). Az inputra egyébként a main.js-ben található cegjegyzekszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
            if($ertek['name'] == 'cegjegyzek'){
                $ret .= nl2('
                    reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                    if( reszek[0].length!=2 || reszek[1].length!=2 || reszek[2].length!=2){
                        document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                        alert2(\'Az adószám formátuma helytelen!\');
                        scrollto(\'#'.$ertek['name'].'\', 500);
                        return false;
                    }
                    ');
            }

            if($ertek['name'] == 'elf'){
                $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').checked==false){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A feltételek elfogadása kötelező!\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
            }

            if($ertek['name'] == 'akcar'){	//Ha van akciós ár, akkor figyelük azt is, hogy kissebb legyen
                                        //Először ki kell szűrni a pontokat belőle
                $ret .= "var ar=document.getElementById('ar').value; var akcar=document.getElementById('akcar').value; mit=new RegExp('[.]','ig'); ar=ar.replace(mit,''); akcar=akcar.replace(mit,''); if(Number(ar)<=Number(akcar)){ document.getElementById('".$ertek['name']."').className += ' alert'; alert2('Az akciós ár nem kedvezőbb a listaártól!'); scrollto('#$ertek[name]', 500); return false; }";
            }

            $ret .= '}';
        }

        if($ajax && !strstr($ertek['type'], 'file')/* tehát nem jsfile vagy sima file... */ ){
            if($ertek['type']=='checkbox'){
                $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').is(\':checked\')+\'&amp;';
            }  else {
                $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
            }
        }
    }

    //Ajaxos form módosítás
    $ajax_kod = nl2('
        alert2(\'Mentés...\');
        $.ajax({
            url : \''.self().'?id='.$_GET['id'].'\',
            type: \'POST\',
            data: esamp2es(\''.$param.str_replace('.php', '', basename(self())).'lista=1&amp;szerkeszt=1&amp;ajax_form_modosit=\' + new Date().getTime()),
            success: function(html){
                alert2(html);
                $(\'#mehet_gomb\').removeClass(\'gombb_alert\');
            }
        });
    ');

    if($ajax){
        $ret .= nl2('; '.$ajax_kod.'
            ; return false; }
            ');
    }else{
        $ret .= '; return true;	}';
    }
    $ret .= nl2('
            $(document).ready(function() {
                $(\'form input[type="text"], #szerk_form textarea\').keyup(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });

                $(\'form input[type="checkbox"]\').click(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });

                $(\'form select\').change(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });
            });</script>
        ');

	return $ret;
}


function get_termek_thumb($id, $sablon, $hivatkozott_oldal, $id_oszlop, $id_get_neve, $tabla, $netto_aras){

    global $adatbazis_kapcsolat;

    $termek = $adatbazis_kapcsolat->s('id nev kep text ar akcar', $tabla, "`$id_oszlop`='$id'");
    $termek = $termek[0];

    $kat = $adatbazis_kapcsolat->s('nev', 'kat', "`id`='{$termek['kat']}'");
    $kat = $kat[0];

    //Link
    $link = $hivatkozott_oldal.'?'.fajlnev_keszit($kat['nev'].' '.$termek['nev']).'&amp;'.$id_get_neve.'='.$termek[$id_oszlop];

    /*//Többképes verzió
    $kepek = $adatbazis_kapcsolat -> select('id, kepnev', 'kepek', 'termek_id='.$termek['id'], 'sor');
    $kep   = $kepek[0];
    $termek['kep'] = $kep['kepnev'];*/

    //Kép
    if($termek['kep'] && is_file(get_utvonal().$termek['kep'])){
        $kep_obj =  new Img(get_utvonal().$termek['kep'], $termek['nev'], get_indexkep_sz(), get_indexkep_m());
    }else{
        $kep_obj = new Img(get_utvonal().'nincskep.jpg', $termek['nev']);
    }
    $fokep     = $kep_obj -> getHtml();

    $intro = nl2br2( resz_string($termek['text'], 200) );
    //$intro = nl2(resz_string( strip_tags( htmlspecialchars_decode2( $termek['text'] ) ), 200) ); <= Webes szövegszerkesztő esetére
    //$intro = nl2(resz_string( strip_tags( string2html( $termek['text'] ) ), 200) );              <= Wikipedia szerű formázáshoz

    //Ár, akciós ár
    if($netto_aras){
        $netto_ar  = $termek['ar'];
        $brutto_ar = $termek['ar']*((100+get_afa())/100);
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar'];
            $brutto_akcar = $termek['akcar']*((100+get_afa())/100);
        }
    }else{
        $netto_ar  = $termek['ar']/((100+get_afa())/100);
        $brutto_ar = $termek['ar'];
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar']/((100+get_afa())/100);
            $brutto_akcar = $termek['akcar'];
        }
    }

    if($termek['akcar']>0){
        $ar       = '<p class="listaar">Listaár: '.number_format($brutto_ar, 0, '', '.').' '.get_penznem().' <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
        $akcar    = '<p class="akcar">Akciós ár: <span class="ar">'.number_format($brutto_akcar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_akcar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    }else{
        $ar    = '';
        $akcar = '<p class="akcar">Listaár: <span class="ar">'.number_format($brutto_ar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    }

    $bovebben = '<a href="'.$link.'">Bővebben</a>';

    $kosarba  = '';

    //Kiíratás
    $mit    = array('{ID}', /* A sablonváltozók és adatok legyenek egymás alatt */  '{LINK}', '{KEP}', '{NEV}',        '{INTRO}',  '{AR}', '{AKCAR}', '{KOSARBA}');
    $mivel = array($termek['id']/*itt a rendes id kell pl. a kosár miatt*/,         $link,    $fokep,  $termek['nev'], $intro,     $ar,    $akcar,    $kosarba);
    echo str_replace($mit, $mivel, $sablon);

}

/**
*	Web FTP
*	FONTOS: ha képet töltünk fel, magától odateszi a kiterjesztést (.jpg .gif .png).
*	Átlagos fájlok esetén adjuk meg a kiterjesztést is a $kepnev-ben
*
*	@param string $valtozo kötelező megadni, ez lesz a változó neve (űrlap neve)
*	@param string $fajlneve kötelező megadni, ez lesz a feltöltött fájl neve
*	@param integer $size méret limit, ami alapból 400 Kb
*	@param boolean $overwrite az eredeti fájlt felülírjuk-e ha már létezett ilyen nevű, vagy készítünk egy újat (pl: kep2.jpg)
*	@param boolean $csakkep ez alapból igaz, tehát csak jpg, png és gifet enged feltölteni
*	@param string $tabla adatbázis tábla neve. Kötelező megadni ha a $sor_id nem null
*	@param string $oszlop adatbázis oszlop neve. Kötelező megadni ha a $sor_id nem null
*	@param string $sor_id adatbázis tábla egy soridje. Ha ez adott, akkor SQL update-ja a tábla adott oszlopát, mivel ilyen esetben a fájl útvonala letárolt
*
*	@return array 0. argumentuma a kép neve, 1. pedig a hibaüzenet (-1 ha sikeres)
*	k1: túllépte a limitet
*	k2: nem képtipusú fájl
*	k3: ilyen nevű fájl már létezett a szerveren és törölni nem lehetett
*	k4: nem sikerül felmásolni
*/
function web_ftp($valtozo, $kepnev, $size=409600, $overwrite=false, $csakkep=true, $tabla=null, $oszlop=null, $sor_id=null, $flashfeltoltes){

    global $adatbazis_kapcsolat;
    global $db_kapcs;

    //Hogy ne csak admin alól tudjuk használni
	if(basename( dirname( self() ) ).'/' == get_admin() ){
        $utvonal = '../'.get_utvonal();
    }else{
        $utvonal = get_utvonal();
    }

    //Ha túllépi a méretlimitet: hiba1
	if($_FILES[$valtozo]['size'] > $size){
		return array($kepnev, 'k1');
	}

	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
	//Ha csakképet engedünk, és nem képformátumú: hiba2
	if($csakkep && !$flashfeltoltes && !($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg' || $_FILES[$valtozo]['type']=='image/gif' || $_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')) {
		return array($kepnev,'k2');
	}elseif(in_array($_FILES[$valtozo]['type'],$tiltott_fajlok)){
		return array($kepnev,'k2b');
	}

    //Ha kell, levesszük a kiterjesztést
	$kit = '';
    if(strrpos($kepnev,'.')){
        $kepnev_tmp = substr($kepnev,0,strrpos($kepnev,'.'));
        $kit        = strtolower(substr($kepnev,strrpos($kepnev,'.'),strlen($kepnev)));//elmentjük a kiterjesztést, kisbetűsre alakítva
        $kepnev     = $kepnev_tmp;
    }
	if($overwrite){//Ha van, felülírjuk a már létező fájlt
		if($sor_id != null){//ha meg van adva a $sor_id akkor valószínűleg az elérési útvonal le van tárolva adatbázisba. Megkíséreljük ezt az előző fájlt törölni

			$lekerdez = $adatbazis_kapcsolat -> select($oszlop, $tabla, 'id='.$sor_id);
			$sor      = $lekerdez[0];

			//Ebben az esetben valóban az adatbázisban van elmentve a kép elérési útvonala
			if($sor[$oszlop]){

                //Elfőrodukhat, hogy több bejegyzésnél is a kép útvonala azonos
                //Emiatt ha az egyiknél cserélnénk a képet, akkor azt még ne törölje le, ha máshol még arra a képre hivatkozunk
                $van_mashol_kep = false;
                $van_mashol_kep = $adatbazis_kapcsolat->s('id', $tabla, $oszlop.'='.$sor[$oszlop].' AND id!='.$sor_id);

                if(!$van_mashol_kep){
                    //ha van kiterjesztése (általában van)
                    if( strrpos($sor[$oszlop], '.') > 0 ){
                        remover($utvonal.substr($sor[$oszlop], 0, strrpos($sor[$oszlop], '.')).'_*');
                    }else{
                        remover($utvonal.$sor[$oszlop].'_*');
                    }
                    //Végül magát a fájlt is töröljük
                    if(is_file($utvonal.$sor[$oszlop])){
                        unlink($utvonal.$sor[$oszlop]);
                    }
                }
			}

			$lekerdez = mysqli_query($db_kapcs, 'SELECT `id` FROM `'.get_db_tabla().$tabla.'` WHERE `id`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
			$foglalt_nev = mysqli_num_rows($lekerdez);
			while( $foglalt_nev ){
				$kepnev = fajlnev_sorszamoz($kepnev);

				$lekerdez=mysqli_query($db_kapcs, 'SELECT `id` FROM `'.get_db_tabla().$tabla.'` WHERE `id`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
				$foglalt_nev = mysqli_num_rows($lekerdez);
			}

		}

		//Egyébként ha nincs letárolva (sorid-s technika) és minden más esetben is törlünk minden hasonló fájlt
        $kepnev_ = $utvonal.$kepnev;
		if(is_file($kepnev_))       { if(!unlink($kepnev_)){        return 'k3'; } }
		if(is_file($kepnev_.'.jpg')){ if(!unlink($kepnev_.'.jpg')){ return 'k3'; } }
		if(is_file($kepnev_.'.gif')){ if(!unlink($kepnev_.'.gif')){ return 'k3'; } }
		if(is_file($kepnev_.'.png')){ if(!unlink($kepnev_.'.png')){ return 'k3'; } }

		remover($kepnev_.'_*');//Resized képek esetére

	}else{//Egyébként másolat (Arra az esetre, ha pl. két termék neve megeggyezik és a nevéből nyerjük ki a képnevet => termeknev_2.jpg)

		$i = 1;
		$ujnev = $kepnev;
		while(glob($utvonal.$ujnev.'*')){
			$ujnev = $kepnev.'_'.$i;
			$i++;
		}
		if( $i==1 && $sor_id ){
            $ujnev = $kepnev.'_1';
        }
		$kepnev = $ujnev;

	}

	//Ha szükséges, akkor adunk neki kiterjesztést is (képek esetén), vagy visszaadjuk az eredetit ha nem kép volt
	if($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg') { $kit='.jpg'; }
	if($_FILES[$valtozo]['type']=='image/gif')                                              { $kit='.gif'; }
	if($_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')  { $kit='.png'; }
	$kepnev .= $kit;


    $konyvtar = substr($kepnev,0,strrpos($kepnev,'/'));
    if(!is_dir($utvonal.$konyvtar)){
        mkdir($utvonal.$konyvtar, 0777);
    }

	if(move_uploaded_file($_FILES[$valtozo]['tmp_name'], $utvonal.$kepnev)){
		if($sor_id){
			if(!$adatbazis_kapcsolat -> u($tabla, array($oszlop), array($kepnev), 'id='.$sor_id)){
				return(array($kepnev,'k5'));//Sikertelen táblamod => hiba5
			}
		}
		if($flashfeltoltes){
            //Flash alapú fájlfeltöltéskor kell egy kimenet, ha minden rendben volt.
            //Akkor ez már legyen az útvonal, mert JS-el feldolgozva ezt ki is tudjuk íratni.
            echo $utvonal.$kepnev;
        }

        //A képeket progresszívvá alakítjuk
        if( in_array($kit, array('.jpg'/*, '.png' PNG tömörítéssel gondok voltak, erre más megoldás kell */))  ){
            $data       = fread(fopen($utvonal.$kepnev, 'rb'), filesize($utvonal.$kepnev));
            $src_img    = @imagecreatefromstring($data);
            imageinterlace($src_img, 1);
            if($kit=='.jpg'){
                imagejpeg($src_img, $utvonal.$kepnev, 100);
            }else{
                imagepng($src_img, $utvonal.$kepnev);
            }
        }

        /* Vízjelezés
        if($tabla=='termek'){
            //Debug: fajlba_mentes('!debug.txt', $utvonal.$kepnev);
            imagejpeg(logot_pecsetel($utvonal.$kepnev, 'elemek/vizjel.png', 50, 50, 100), $utvonal.$kepnev);
        }
         */

		return array($kepnev, '-1');
	}else{
		return array($kepnev, 'k4');
	}
}

function szamla_lista( $tabla, $val = NULL ) {
    global $adatbazis_kapcsolat;

    $where = '';
    if( $val ) {
        $where = '`nev` LIKE \'%'.$val.'%\'';
    }

    if( $tabla ) {
        $bejovo_szamlak = $adatbazis_kapcsolat->s('recid nev datetime', $tabla, $where, 'datetime desc', '');

        if( $bejovo_szamlak ) {
            $aktDatum = date_formaz($bejovo_szamlak[0]['datetime']);

            $tartBal .= '<ul id="szamlaList" class="szamla_cont">';

            $i = 1;
            foreach($bejovo_szamlak as $bejovo_szamla) {
                $szamlaDatum = date_formaz($bejovo_szamla['datetime']);

                if( $i == 1 || $aktDatum != $szamlaDatum ) {
                    $tartBal .= '<li class="szamla_datum"><strong>'.$szamlaDatum.'</strong></li>';

                    $aktDatum = $szamlaDatum;
                }

                $active = '';
                if( $bejovo_szamla['recid'] == $_GET['id'] ) {
                    $active = ' active';
                }

                $tartBal .= '<li id="szamla-'.$bejovo_szamla['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bejovo_szamla['recid'].'"><a href="'.self().'?unset=&epo=50&oldal=1&order=nev&id='.$bejovo_szamla['recid'].'&modosit">'.$bejovo_szamla['nev'].'</a></li>';

                $i++;
            }

            $tartBal .= '</ul>';
        }

        return $tartBal;
    }

    return false;
}
