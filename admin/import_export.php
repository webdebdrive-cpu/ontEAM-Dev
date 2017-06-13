<?php
/*                                                                                                                                      *\
    Termékek letöltése csv-be árakkal akcsiós árakkal. Ezt vissza is lehet frissíteni.
    Ez esetleges hibákat loggolja, figyelmeztet.

    Ezt a fájlt meghívva a így: iport_export.php?xml_gen akkor egy XML-t állít elő az árgép számára.

    SÚGÓ: CSV-ben a számokat dátummá alakítja az Excel? Megoldás: "valami";="12.5" Tehát az idézőjel elé tegyél egy egyenlőség jelet.

    Ha azonnal le akarot tölteni a CSV-t, azt ilyen fejlécekkel éred el:
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={FAJLNEV}.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
\*                                                                                                                                      */

require_once('../weblap.php');
//XML előállítás pl. árgéphez
if(isset($_GET['xml_gen'])){

    //Karaterkódolás
    $mibol = get_web_charset();
    $mibe  = 'windows-1250';

    $xml_keret = '<?xml version="1.0" encoding="utf-8"?>
    <Catalog date="'.date('d-m-Y H:i:s').'">
    {TARTALOM}
    </Catalog>
    ';

    $xml_sor = '
    <product>
        <id>{KODSZAM}</id>
        <name><![CDATA[{NEV}]]></name>
        <desc><![CDATA[{LEIRAS}]]></desc>
        <price>{AR}</price>
        <category><![CDATA[{KAT}]]></category>
        <urlsite><![CDATA[{LINK}]]></urlsite>
        <urlpicture><![CDATA[{KEP}]]></urlpicture>
        <instock>{RAKTARON}</instock>
    </product>
    ';
    $xml_mit = array('{KODSZAM}', '{NEV}', '{LEIRAS}', '{AR}', '{KAT}', '{LINK}', '{KEP}', '{RAKTARON}');

    //Fájlnevek
    $aru_szinkron       = 'aru_szinkron.xml';
    $aru_szinkron_fnev  = 'xml/aru_szinkron.xml';
    $aru_szinkron_xml   = '';
    $gyokerbe_ment      = '../aru_szinkron.xml';

    //Termékek bejárása cikkszám szerint
    $termekek = $adatbazis_kapcsolat -> s('id nevid text ar akcar kep', 'termek', 'aktiv>0');
    foreach($termekek as $termek){

        $leiras = $termek['text'];
        $leiras = htmlspecialchars_decode2($leiras);
        $leiras = strip_tags($leiras);
        $leiras = str_replace('&nbsp;', '', $leiras);

        if($termek['akc_ar']){
            $ar    = $termek['akc_ar'];
            $akcio = 'Akciós termék!';
        }else{
            $ar    = $termek['ar'];
            $akcio = '';
        }

        $katok  = $adatbazis_kapcsolat -> s('nev szulo', 'kat', 'id='.$termek['kat']);
        $kat    = $katok[0];
        $link   = 'http://'.get_domain().'/index.php?id='.$termek['nevid'];

        $kep = '';
        if(is_file('../'.get_utvonal().$termek['kep'])){
            $kep = 'http://'.get_domain().'/'.get_utvonal().$termek['kep'];
        }

        /*
        //Többképes verzió
		$kep_lek = mysqli_query($db_kapcs, 'SELECT `kepnev` FROM `'.get_db_tabla().'kepek` WHERE `termek_id`=\''.$termek['id'].'\' ORDER BY `sor`');
		$sor_kep=mysqli_fetch_assoc($kep_lek);
		if(is_file('../'.get_utvonal().$sor_kep['kepnev'])){
            $kep = 'http://'.get_domain().'/'.get_utvonal().$sor_kep['kepnev'];
		}
         */

        //Raktáron
        $raktaron = 'Nincs raktáron';
        if($termek['raktaron']){
            $raktaron = 'Raktáron';
        }

        $xml_mivel  = array($termek['nevid'], $termek['nev'], $leiras,  $ar,  $fokat['nev'].' '.$kat['nev'], $link, $kep, $raktaron);
        $xml       .= str_replace($xml_mit, $xml_mivel, $xml_sor);


    }

    $xml = str_replace('{TARTALOM}', $xml, $xml_keret);
    fajlba_mentes($aru_szinkron_fnev, $xml );
    fajlba_mentes($gyokerbe_ment, $xml );

    $weblap -> leallit();
    exit;
}





$fajlnev        = 'csv/termekek.csv';
$csv_letolt_nev = 'Termékek.csv';
$admin_mappanev = get_admin();
$ar_lehet_nulla = false;

require_once('header.php');

    $html = '<b>'.$navi.'</b>';

    if(isset($_GET['letolt'])){

        $fajlnev = 'csv/felhasznalok.csv';

        $mibol   = get_web_charset();
        $mibe    = 'windows-1250';

        if(is_file($fajlnev)){
            unlink($fajlnev);
        }

		$open = fopen($fajlnev,'a');
		if($open){

			$query = mysqli_query($db_kapcs, 'SELECT * FROM `tblu`');
			$cols  = mysqli_num_fields($query);

			$csvhead    = array();
			for( $i = 0; $i < $cols; $i++ ){
				$csvhead[] = iconv2($mibol, $mibe, mysqli_fetch_field($query, $i) -> name );
			}
			fputcsv($open, $csvhead, ';');

			while( $jelentkezesek = mysqli_fetch_assoc($query) ){
				$csvcont = array();
				foreach( $jelentkezesek as $kulcs => $sor ) {
					$csvcont[] = iconv2($mibol, $mibe, strip_tags( htmlspecialchars_decode( $sor ) ) );
				}
				fputcsv($open, $csvcont, ';');
			}
		}
		fclose($open);

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=felhasznalok.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        //echo fajlbol_olvas($fajlnev);

        //Vagy ki is irathatotd:
        $html .= nl2('
            <h1>
                A csv fájl sikeresen elkészült, töltsd le
                <a class="u" href="../letolt.php?fajl='.$admin_mappanev.$fajlnev.'&amp;nev='.$csv_letolt_nev.'">ide kattintva</a>
            </h1>
        ');


    }elseif(isset($_GET['szallitok'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?szallitok" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt az <b>ügyfelek</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/szallitok.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'id'               : $id       = $akt_tomb[$key];  break;
								case 'nev'              : $nev      = $akt_tomb[$key];  break;
								case 'recid'            : $recid    = $akt_tomb[$key];  break;
								case 'aktiv'            : $aktiv    = $akt_tomb[$key];  break;

							}
						}

                        if( $aktiv == 'IGAZ' ) {
                            $aktiv = 1;
                        } else if( $aktiv == 'HAMIS' ) {
                            $aktiv = 0;
                        }

                        $adatbazis_kapcsolat->i('szallito',
                            array('id', 'nev', 'aktiv'),
                            array($id,  $nev,  $aktiv )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
	}elseif(isset($_GET['felhasznalok'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?felhasznalok" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>felhasználók</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/felhasznalok.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'loginnev'         : $loginnev     = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'chk1'             : $chk1         = trim($akt_tomb[$key]);  break;
								case 'chk2'             : $chk2         = trim($akt_tomb[$key]);  break;
								case 'flgs'             : $flgs         = trim($akt_tomb[$key]);  break;
								case 'kcs_17'           : $kcs_17       = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;
								case 'aktiv'            : $aktiv        = trim($akt_tomb[$key]);  break;
                                case 'kcs_18'           : $kcs_18       = trim($akt_tomb[$key]);  break;
								case 'usertel'          : $usertel      = trim($akt_tomb[$key]);  break;
								case 'usermail'         : $usermail     = trim($akt_tomb[$key]);  break;
								case 'statid'           : $statid       = trim($akt_tomb[$key]);  break;
								case 'mobil'            : $mobil        = trim($akt_tomb[$key]);  break;
                                case 'chk3'             : $chk3         = trim($akt_tomb[$key]);  break;
								case 'fer'              : $fer          = trim($akt_tomb[$key]);  break;
								case 'hasmsg'           : $hasmsg       = trim($akt_tomb[$key]);  break;
								case 'virtual'          : $virtual      = trim($akt_tomb[$key]);  break;

							}
						}

                        $usertel = '';

                        $adatbazis_kapcsolat->i('login',
                            array('beosztas_id', 'reszleg_id', 'szerepkor_id', 'van_jelszo', 'fer', 'virtual', 'statusz', 'usertel', 'usermail', 'nev',     'teljes_nev', 'flags', 'aktiv', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($kcs_18,       $kcs_17,       $statid,        $chk3,        $fer,  $virtual,  $statid,   $usertel,  $usermail,  $loginnev, $nev,         $flgs,   $aktiv,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}

    } elseif(isset($_GET['telephely'])) {

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?telephely" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>telephely</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/telephely.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}
                        
						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'id'               : $id           = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;
								case 'props'            : $props        = trim($akt_tomb[$key]);  break;

							}
						}


                        $adatbazis_kapcsolat->i('telephely',
                            array('id', 'nev', 'recid', 'props', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($id,  $nev,  $recid,  $props,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}

    } elseif(isset($_GET['gep'])) {

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?gep" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>gép</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/gep.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'telepid'          : $telepid      = trim($akt_tomb[$key]);  break;
								case 'objid'            : $objid        = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'azon'             : $azon         = trim($akt_tomb[$key]);  break;
								case 'megjegyzes'       : $megj         = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;

							}
						}


                        $adatbazis_kapcsolat->i('gep',
                            array('recid', 'telephely_id', 'obj_id', 'nev', 'azon', 'megj', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($recid,   $telepid,      $objid,   $nev,   $azon, $megj,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}

    } elseif(isset($_GET['obj'])) {

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?obj" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>obj</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/obj.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'telepid'          : $telepid      = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'parentid'         : $parentid     = trim($akt_tomb[$key]);  break;
								case 'hierid'           : $hierid       = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;

							}
						}


                        $adatbazis_kapcsolat->i('obj',
                            array('recid', 'telephely_id', 'szulo',   'nev', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($recid,   $telepid,      $parentid, $nev,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}

    } elseif(isset($_GET['felhasznalok_korr'])) {

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?felhasznalok_korr" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>felhasználók</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/felhasznalok_korr.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

                                case 'aktiv'         : $aktiv       = trim($akt_tomb[$key]);  break;
								case 'nev'           : $nev         = trim($akt_tomb[$key]);  break;
								case 'login_nev'     : $login_nev   = trim($akt_tomb[$key]);  break;
								case 'email'         : $email       = trim($akt_tomb[$key]);  break;
								case 'telefon'       : $telefon     = trim($akt_tomb[$key]);  break;

								/*case 'loginnev'         : $loginnev     = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'chk1'             : $chk1         = trim($akt_tomb[$key]);  break;
								case 'chk2'             : $chk2         = trim($akt_tomb[$key]);  break;
								case 'flgs'             : $flgs         = trim($akt_tomb[$key]);  break;
								case 'kcs_17'           : $kcs_17       = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;
								case 'aktiv'            : $aktiv        = trim($akt_tomb[$key]);  break;
                                case 'kcs_18'           : $kcs_18       = trim($akt_tomb[$key]);  break;
								case 'usertel'          : $usertel      = trim($akt_tomb[$key]);  break;
								case 'usermail'         : $usermail     = trim($akt_tomb[$key]);  break;
								case 'statid'           : $statid       = trim($akt_tomb[$key]);  break;
								case 'mobil'            : $mobil        = trim($akt_tomb[$key]);  break;
                                case 'chk3'             : $chk3         = trim($akt_tomb[$key]);  break;
								case 'fer'              : $fer          = trim($akt_tomb[$key]);  break;
								case 'hasmsg'           : $hasmsg       = trim($akt_tomb[$key]);  break;
								case 'virtual'          : $virtual      = trim($akt_tomb[$key]);  break;*/

							}
						}

                        $felh = $adatbazis_kapcsolat->s('recid', 'login', 'nev='.$login_nev, '', '');

                        if( $felh ) {
                            $felh = $felh[0];

                            $adatbazis_kapcsolat -> u('login', array('teljes_nev', 'usertel', 'usermail'), array($nev, $telefon, $email), 'recid='.$felh['recid']);
                        }
                        echo $login_nev .'<br />';

                        /*$usertel = '';

                        $adatbazis_kapcsolat->i('login',
                            array('beosztas_id', 'reszleg_id', 'szerepkor_id', 'van_jelszo', 'fer', 'virtual', 'statusz', 'usertel', 'usermail', 'nev',     'teljes_nev', 'flags', 'aktiv', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($kcs_18,       $kcs_17,       $statid,        $chk3,        $fer,  $virtual,  $statid,   $usertel,  $usermail,  $loginnev, $nev,         $flgs,   $aktiv,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';*/

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
	}elseif(isset($_GET['erteklista'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?erteklista" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>erteklista</b> frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/erteklista.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {

								case 'csoport'          : (int)$csoport = $akt_tomb[$key];  break;
								case 'data'             : (int)$id      = $akt_tomb[$key];  break;
								case 'info'             : $info         = $akt_tomb[$key];  break;
								case 'recid'            : $recid        = $akt_tomb[$key];  break;
								case 'update_dt'        : $update_dt    = $akt_tomb[$key];  break;
								case 'update_usr'       : $update_usr   = $akt_tomb[$key];  break;
								case 'insert_dt'        : $insert_dt    = $akt_tomb[$key];  break;
								case 'insert_usr'       : $insert_usr   = $akt_tomb[$key];  break;

							}
						}

                        $adatbazis_kapcsolat->i('erteklista',
                            array('csoport', 'id', 'info', 'recid', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($csoport,  $id,  $info,  $recid,  $update_dt,  $update_usr,  $insert_dt,  $insert_usr   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
        
	}elseif(isset($_GET['szamlak'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?szamlak" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt az <b>ügyfelek</b>frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/beszallitok.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {
								case 'erk_dat'          :
                                    $erk_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'iktszam'          : $iktszam      = $akt_tomb[$key];  break;
								case 'vissza_dat'       :
                                    $vissza_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szallkod'         : $szallkod     = $akt_tomb[$key];  break;
								case 'szlaszam'         : $szlaszam     = $akt_tomb[$key];  break;
								case 'telj_dat'         :
                                    $telj_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'fizhat_dat'       :
                                    $fizhat_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szallitoid'       : $szallitoid   = $akt_tomb[$key];  break;
								case 'targy'            : $targy        = $akt_tomb[$key];  break;
								case 'netert'           : $netert       = $akt_tomb[$key];  break;
								case 'brutert'          : $brutert      = $akt_tomb[$key];  break;
								case 'kifmod'           : $kifmod       = $akt_tomb[$key];  break;
								case 'kcs_03'           : $kcs_03       = $akt_tomb[$key];  break;
								case 'kcs_56'           : $kcs_56       = $akt_tomb[$key];  break;
								case 'kcs_06'           : $kcs_06       = $akt_tomb[$key];  break;
								case 'valnetert'        : $valnetert    = $akt_tomb[$key];  break;
								case 'valbrutert'       : $valbrutert   = $akt_tomb[$key];  break;
								case 'valarf'           : $valarf       = $akt_tomb[$key];  break;
								case 'valarf_dat'       :
                                    $valarf_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'kcs_51'           : $kcs_51       = $akt_tomb[$key];  break;
								case 'kiseroszam'       : $kiseroszam   = $akt_tomb[$key];  break;
								case 'rendszam'         : $rendszam     = $akt_tomb[$key];  break;
								case 'bevszam'          : $bevszam      = $akt_tomb[$key];  break;
								case 'ikt_dat'          :
                                    $ikt_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'iktatta'          : $iktatta      = $akt_tomb[$key];  break;
								case 'kontir_dat'       :
                                    $kontir_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'kontirozta'       : $kontirozta   = $akt_tomb[$key];  break;
								case 'igazol_dat'       :
                                    $igazol_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'igazolta'         : $igazolta     = $akt_tomb[$key];  break;
								case 'elkuld_dat'       :
                                    $elkuld_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'elkuldte'         : $elkuldte     = $akt_tomb[$key];  break;
								case 'kif_dat'          : $kif_dat      = $akt_tomb[$key];  break;
								case 'recid'            : $recid        = $akt_tomb[$key];  break;
								case 'celszkod'         : $celszkod     = $akt_tomb[$key];  break;
								case 'megjegyzes'       : $megjegyzes   = $akt_tomb[$key];  break;
								case 'kcs_17'           : $kcs_17       = $akt_tomb[$key];  break;
								case 'sablon'           :
                                    if( $akt_tomb[$key] == 'IGAZ' ) {
                                        $sablon = 1;
                                    } else if( $akt_tomb[$key] == 'HAMIS' ) {
                                        $sablon = 0;
                                    }
                                break;
								case 'szabadszal'       : $szabadszal   = $akt_tomb[$key];  break;
								case 'szkp_dat'         :
                                    $szkp_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szkpfeldol'       : $szkpfeldol   = $akt_tomb[$key];  break;
								case 'afatipus'         : $afatipus     = $akt_tomb[$key];  break;
								case 'torolt'           :
                                    if( $akt_tomb[$key] == 'IGAZ' ) {
                                        $torolt = 1;
                                    } else if( $akt_tomb[$key] == 'HAMIS' ) {
                                        $torolt = 0;
                                    }
                                break;
								case 'kcs2_17'          : $kcs2_17      = $akt_tomb[$key];  break;
								case 'eszkozcsop'       : $eszkozcsop   = $akt_tomb[$key];  break;
								case 'eszkozert'        : $eszkozert    = $akt_tomb[$key];  break;
								case 'eszla'            :
                                    if( $akt_tomb[$key] == 'IGAZ' ) {
                                        $eszla = 1;
                                    } else if( $akt_tomb[$key] == 'HAMIS' ) {
                                        $eszla = 0;
                                    }
                                break;
							}
						}

                        if( !$kif_dat ) {
                            $statusz = 11;
                        }

                        if( !$igazol_dat ) {
                            $statusz = 12;
                        }

                        if( !$kontir_dat ) {
                            $statusz = 5;
                        }

                        if( !$ikt_dat ) {
                            $statusz = 4;
                        }

                        $adatbazis_kapcsolat->i('bejovo_szamlak',
                            array('ikt_szam', 'erkezett', 'visszaadva', 'szaml_szam', 'teljesites', 'fiz_hat',   'targy', 'netto', 'brutto', 'fiz_mod', 'valuta_netto', 'valuta_brutto', 'arfolyam', 'atvaltva',  'rend_szam', 'bev_szam', 'megj',      'szallito_szab', 'afa_tipus', 'torolve', 'eszkoz_csop', 'eszkoz_ert', 'e-szamla', 'iktatva', 'iktatta', 'igazolva',  'igazolta', 'elkuldve',  'elkuldte', 'szkp_atvette', 'szkp_feldolg', 'sablon', 'datetime',            'statusz', 'szallito'  ),
                            array($iktszam,   $erk_dat,   $vissza_dat,  $szlaszam,    $telj_dat,    $fizhat_dat, $targy,  $netert, $brutert, $kifmod,   $valnetert,     $valbrutert,     $valarf,    $valarf_dat, $rendszam,   $bevszam,   $megjegyzes, $szabadszal,     $afatipus,   $torolt,   $eszkozcsop,   $eszkozert,   $eszla,     $ikt_dat,  $iktatta,  $igazol_dat, $igazolta,  $elkuld_dat, $elkuldte,  $szkp_dat,      $szkpfeldol,    $sablon,  $erk_dat.' 00-00-00',  $statusz,  $szallkod   )
                        );

						echo $adatbazis_kapcsolat->gp() . '<br>';
//if($k==100) {exit();}
					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
        
	}elseif(isset($_GET['cikktorzs'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?cikktorzs" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>cikktörzs</b> frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/beszallitok.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));

							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {
                                case 'id'               : $id           = $akt_tomb[$key];  break;
								case 'nev'              : $nev          = $akt_tomb[$key];  break;
								case 'oracikkid'        : $oracikkid    = $akt_tomb[$key];  break;
                                case 'cikkmee'          : $cikkmee      = $akt_tomb[$key];  break;
                                case 'kcs_19'           : $szakter_id   = $akt_tomb[$key];  break;
                                case 'segedanyag'       : $segedanyag   = $akt_tomb[$key];  break;
                                case 'helyi'            : $helyi        = $akt_tomb[$key];  break;
                                case 'leiras'           : $leiras       = $akt_tomb[$key];  break;
                                case 'update_dt'        : $update_dt    = $akt_tomb[$key];  break;
								case 'update_usr'       : $update_usr   = $akt_tomb[$key];  break;
								case 'insert_dt'        : $insert_dt    = $akt_tomb[$key];  break;
								case 'insert_usr'       : $insert_usr   = $akt_tomb[$key];  break;
							}
						}

                        $adatbazis_kapcsolat->i('cikktorzs',
                            array('id', 'nev', 'oracikkid', 'cikkmee', 'szakter_id', 'segedanyag',  'helyi', 'leiras', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr' ),
                            array($id,  $nev,  $oracikkid,  $cikkmee,  $szakter_id,  $segedanyag,   $helyi,   $leiras, $update_dt,  $update_usr,  $insert_dt,  $insert_usr  )
                        );
echo $adatbazis_kapcsolat->gp() . '<br>';

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
        
	}elseif(isset($_GET['feladatok'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?feladatok" enctype="multipart/form-data">
								<p class="c" style="margin: 2rem 0 0 0">
									Tallózd be a CSV fájlt a <b>feladatok</b> frissítéséhez:
									<input type="file" name="termekek">
									<br><input type="submit" class="gombb" value="Frissít">
								</p>
							</form>
						');

		}else{

			$fajlnev = 'csv/beszallitok.csv';

			$mibol = 'windows-1250';
			$mibe  = get_web_charset();

			$mit   = array(' ','.',',',';','-','Ft','FT','ft','fT','"',"'");
			$mivel = array('','','','','','','','','','','');

			//Alapból mehet majd a feltöltés, de ha hiba van az adatok feldolgozásával, akkor ezzel megszakítható
			//FIGYELEM! Ha ilyet használsz, akkor viszont ne soronként dolgozd fel az SQL a ciklusban, hanem készíts egy nagy stringet tele az SQL parancsokkal és egy $mehet-től függően futtasd le.
			$mehet      = true;
			$csv_sql    = '';
			$log        = '';

			if($_FILES['termekek']['tmp_name']){
				fajlba_mentes($fajlnev.'_up', fajlbol_olvas($_FILES['termekek']['tmp_name']));
			}

			setlocale(LC_ALL,'hu_HU');

			$_SESSION['sor_fejlec'] = array();

			$k = 0;
			if (($csv_fajl = fopen($fajlnev.'_up', 'r')) !== FALSE) {
				$osszes_sor = count(file($fajlnev.'_up')) . '<br>';

				while (($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE) {
					$akt_tomb = array();

					//Első oszlop a fejlécek
					if($k == 0) {

						foreach($sor as $key => $value) {
                            $value = trim(mb_strtolower($value, 'UTF-8'));
							$_SESSION['sor_fejlec'][] = $value;
						}

					} else {

						foreach($sor as $key => $value) {
							$akt_tomb[$_SESSION['sor_fejlec'][$key]] = $value;
						}

						foreach($akt_tomb as $key => $value) {
							$akt_tomb[$key] = utf8($akt_tomb[$key]);
						}

						foreach($akt_tomb as $key => $value) {

							$key_kicsi = trim(mb_strtolower($key, 'UTF-8'));
 
							//Azonosítás fejléc nevekkel
							switch($key_kicsi) {
                                case 'id'               : $id           = $akt_tomb[$key];  break;
                                case 'aktiv'            : $aktiv        = $akt_tomb[$key];  break;
								case 'telepid'          : $telephely_id = $akt_tomb[$key];  break;
                                case 'objid'            : $obj_id       = $akt_tomb[$key];  break;
                                case 'gepid'            : $gep_id       = $akt_tomb[$key];  break;
                                case 'nev'              : $nev          = $akt_tomb[$key];  break;
                                case 'leiras'           : $leiras       = $akt_tomb[$key];  break;
                                case 'riaszt'           : $riaszt       = $akt_tomb[$key];  break;
                                case 'hozrend'          : $szemelyzet_id= $akt_tomb[$key];  break;
                                case 'hatosagi'         : $hatosagi     = $akt_tomb[$key];  break;
                                case 'cikltip'          : $cikltip      = $akt_tomb[$key];  break;
                                case 'ciklaltip'        : $ciklaltip    = $akt_tomb[$key];  break;
                                case 'ciklho'           : $ciklho       = $akt_tomb[$key];  break;
                                case 'ciklhet'          : $ciklhet      = $akt_tomb[$key];  break;
                                case 'ciklnap'          : $ciklnap      = $akt_tomb[$key];  break;
                                case 'ciklszorz'        : $ciklszorz    = $akt_tomb[$key];  break;
                                case 'hataly_d'         : $hataly_d     = $akt_tomb[$key];  break;
                                case 'utnap_d'          : $utnap_d      = $akt_tomb[$key];  break;
                                case 'aktkezd_d'        : $aktkezd_d    = $akt_tomb[$key];  break;
                                case 'aktvege_d'        : $aktvege_d    = $akt_tomb[$key];  break;
                                case 'esnap_d'          : $esnap_d      = $akt_tomb[$key];  break;
                                case 'utesnap_d'        : $utesnap_d    = $akt_tomb[$key];  break;
                                case 'esem_d'           : $esem_d       = $akt_tomb[$key];  break;
                                case 'loginnev'         : $loginnev     = $akt_tomb[$key];  break;
                                case 'update_dt'        : $update_dt    = $akt_tomb[$key];  break;
								case 'update_usr'       : $update_usr   = $akt_tomb[$key];  break;
								case 'insert_dt'        : $insert_dt    = $akt_tomb[$key];  break;
								case 'insert_usr'       : $insert_usr   = $akt_tomb[$key];  break;
							}
                            
                            $utnap_d   = str_replace('.', '-', $utnap_d);
                            $aktkezd_d = str_replace('.', '-', $aktkezd_d);
                            $aktvege_d = str_replace('.', '-', $aktvege_d);
                            $hataly_d  = str_replace('.', '-', $hataly_d);
                            $utesnap_d = str_replace('.', '-', $utesnap_d);
                            $esnap_d   = str_replace('.', '-', $esnap_d);
                            $esem_d    = str_replace('.', '-', $esem_d);
                            $insert_dt = str_replace('.', '-', $insert_dt);
                            $update_dt = str_replace('.', '-', $update_dt);
						}
                        
                        if($id){
                            
                            $ciklho2        = '';
                            $ho_rendsz_het  = '';
                            $honapjan       = '';
                            if($cikltip=='m' && $ciklaltip==2){
                                $ciklho2        = $ciklszorz;
                                $ho_rendsz_het  = $ciklnap;
                            }
                            if($cikltip=='m' && $ciklaltip==1){
                                $honapjan = $ciklnap;
                            }
                            
                            $adatbazis_kapcsolat->i('utemezett_feladatok',
                                array('id', 'aktiv', 'telephely_id', 'obj_id', 'gep_id', 'nev', 'leiras', 'riaszt', 'szemelyzet_id', 'hatosagi', 'cikltip',     'ciklaltip', 'ciklho', 'ciklho2', 'ciklhet', 'ho_rendsz_het', 'ciklnap', 'ciklszorz', 'honapjan', 'hataly_d', 'utnap_d', 'aktkezd_d', 'aktvege_d', 'esnap_d', 'utesnap_d', 'esem_d', 'loginnev', 'update_dt', 'update_usr', 'insert_dt', 'insert_usr' ),
                                array($id,  $aktiv,  $telephely_id,  $obj_id,  $gep_id,  $nev,  $leiras,  $riaszt,  $szemelyzet_id,  $hatosagi,  $cikltip,      $ciklaltip,  $ciklho,  $ciklho2,  $ciklhet,  $ho_rendsz_het,  $ciklnap,  $ciklszorz,  $honapjan,  $hataly_d,  $utnap_d,  $aktkezd_d,  $aktvege_d,  $esnap_d,  $utesnap_d,  $esem_d,  $login_nev, $update_dt,  $update_usr,  $insert_dt,  $insert_usr  )
                            );
echo $adatbazis_kapcsolat->gp() . '<br>';
                        }

					}

					$k++;
				}
				fclose($csv_fajl);
			}

			//Ha volt log, akkor azt kiíratjuk
			$filename = 'csv/log.txt';
			if(is_file($filename) && filesize($filename)){
				$handle = fopen($filename, 'r');
				$log = fread($handle, filesize($filename));
				fclose($handle);
			}

			if($log){

				$html .= nl2('
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
								<p class="b" style="margin-top: 2rem">
									A feltöltés során az alábbi hibák fordultak elő:
								</p>
								<p>'.$log.'</p>
								<p class="r">
									<a href="javascript:history.back()">« Vissza</a>
								</p>
							');

			}else{
				$html .= nl2('<h1>A frissítés megtörtént!</h1>');
			}

		}
	}

    echo $html;

require_once('footer.php');
?>
