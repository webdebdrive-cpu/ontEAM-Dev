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

								case 'id'               : $id       = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev      = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid    = trim($akt_tomb[$key]);  break;
								case 'aktiv'            : $aktiv    = trim($akt_tomb[$key]);  break;

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
                            array('beosztas_id', 'reszleg_id', 'szerepkor_id', 'van_jelszo', 'fer', 'virtual', 'statusz', 'usertel', 'usermail', 'nev',     'teljes_nev', 'flags', 'aktiv',  'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($kcs_18,       $kcs_17,       $statid,        $chk3,        $fer,  $virtual,  $statid,   $usertel,  $usermail,  $loginnev, $nev,         $flgs,   $aktiv,  $update_usr,  $insert_dt,  $insert_usr   )
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
                            array('id', 'nev', 'recid', 'props', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($id,  $nev,  $recid,  $props,  $update_usr,  $insert_dt,  $insert_usr   )
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
                            array('recid', 'telephely_id', 'obj_id', 'nev', 'azon', 'megj', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($recid,   $telepid,      $objid,   $nev,   $azon, $megj,  $update_usr,  $insert_dt,  $insert_usr   )
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
                                                    array('recid', 'telephely_id', 'szulo',   'nev', 'update_usr', 'insert_dt', 'insert_usr'  ),
                                                    array($recid,   $telepid,      $parentid, $nev,  $update_usr,  $insert_dt,  $insert_usr   )
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

								case 'csoport'          : (int)$csoport = trim($akt_tomb[$key]);  break;
								case 'data'             : (int)$id      = trim($akt_tomb[$key]);  break;
								case 'info'             : $info         = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;

							}
						}

                        $adatbazis_kapcsolat->i('erteklista',
                            array('csoport', 'id', 'info', 'recid', 'update_usr', 'insert_dt', 'insert_usr'  ),
                            array($csoport,  $id,  $info,  $recid,  $update_usr,  $insert_dt,  $insert_usr   )
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
								case 'iktszam'          : $iktszam      = trim($akt_tomb[$key]);  break;
								case 'vissza_dat'       :
                                    $vissza_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szallkod'         : $szallkod     = trim($akt_tomb[$key]);  break;
								case 'szlaszam'         : $szlaszam     = trim($akt_tomb[$key]);  break;
								case 'telj_dat'         :
                                    $telj_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'fizhat_dat'       :
                                    $fizhat_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szallitoid'       : $szallitoid   = trim($akt_tomb[$key]);  break;
								case 'targy'            : $targy        = trim($akt_tomb[$key]);  break;
								case 'netert'           : $netert       = trim($akt_tomb[$key]);  break;
								case 'brutert'          : $brutert      = trim($akt_tomb[$key]);  break;
								case 'kifmod'           : $kifmod       = trim($akt_tomb[$key]);  break;
								case 'kcs_03'           : $kcs_03       = trim($akt_tomb[$key]);  break;
								case 'kcs_56'           : $kcs_56       = trim($akt_tomb[$key]);  break;
								case 'kcs_06'           : $kcs_06       = trim($akt_tomb[$key]);  break;
								case 'valnetert'        : $valnetert    = trim($akt_tomb[$key]);  break;
								case 'valbrutert'       : $valbrutert   = trim($akt_tomb[$key]);  break;
								case 'valarf'           : $valarf       = trim($akt_tomb[$key]);  break;
								case 'valarf_dat'       :
                                    $valarf_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'kcs_51'           : $kcs_51       = trim($akt_tomb[$key]);  break;
								case 'kiseroszam'       : $kiseroszam   = trim($akt_tomb[$key]);  break;
								case 'rendszam'         : $rendszam     = trim($akt_tomb[$key]);  break;
								case 'bevszam'          : $bevszam      = trim($akt_tomb[$key]);  break;
								case 'ikt_dat'          :
                                    $ikt_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'iktatta'          : $iktatta      = trim($akt_tomb[$key]);  break;
								case 'kontir_dat'       :
                                    $kontir_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'kontirozta'       : $kontirozta   = trim($akt_tomb[$key]);  break;
								case 'igazol_dat'       :
                                    $igazol_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'igazolta'         : $igazolta     = trim($akt_tomb[$key]);  break;
								case 'elkuld_dat'       :
                                    $elkuld_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'elkuldte'         : $elkuldte     = trim($akt_tomb[$key]);  break;
								case 'kif_dat'          : $kif_dat      = trim($akt_tomb[$key]);  break;
								case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
								case 'celszkod'         : $celszkod     = trim($akt_tomb[$key]);  break;
								case 'megjegyzes'       : $megjegyzes   = trim($akt_tomb[$key]);  break;
								case 'kcs_17'           : $kcs_17       = trim($akt_tomb[$key]);  break;
								case 'sablon'           :
                                    if( $akt_tomb[$key] == 'IGAZ' ) {
                                        $sablon = 1;
                                    } else if( $akt_tomb[$key] == 'HAMIS' ) {
                                        $sablon = 0;
                                    }
                                break;
								case 'szabadszal'       : $szabadszal   = trim($akt_tomb[$key]);  break;
								case 'szkp_dat'         :
                                    $szkp_dat = str_replace('.', '-', $akt_tomb[$key]);
                                break;
								case 'szkpfeldol'       : $szkpfeldol   = trim($akt_tomb[$key]);  break;
								case 'afatipus'         : $afatipus     = trim($akt_tomb[$key]);  break;
								case 'torolt'           :
                                    if( $akt_tomb[$key] == 'IGAZ' ) {
                                        $torolt = 1;
                                    } else if( $akt_tomb[$key] == 'HAMIS' ) {
                                        $torolt = 0;
                                    }
                                break;
								case 'kcs2_17'          : $kcs2_17      = trim($akt_tomb[$key]);  break;
								case 'eszkozcsop'       : $eszkozcsop   = trim($akt_tomb[$key]);  break;
								case 'eszkozert'        : $eszkozert    = trim($akt_tomb[$key]);  break;
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
                                                    array('ikt_szam', 'erkezett', 'visszaadva', 'szaml_szam', 'teljesites', 'fiz_hat',   'targy', 'netto', 'brutto', 'fiz_mod', 'valuta_netto', 'valuta_brutto', 'arfolyam', 'atvaltva',  'rend_szam', 'bev_szam', 'megj',      'szallito_szab', 'afa_tipus', 'torolve', 'eszkoz_csop', 'eszkoz_ert', 'e-szamla', 'iktatva', 'iktatta', 'igazolva',  'igazolta', 'elkuldve',  'elkuldte', 'szkp_atvette', 'szkp_feldolg', 'sablon', 'datetime',            'statusz', 'szallito_id'  ),
                                                    array($iktszam,   $erk_dat,   $vissza_dat,  $szlaszam,    $telj_dat,    $fizhat_dat, $targy,  $netert, $brutert, $kifmod,   $valnetert,     $valbrutert,     $valarf,    $valarf_dat, $rendszam,   $bevszam,   $megjegyzes, $szabadszal,     $afatipus,   $torolt,   $eszkozcsop,   $eszkozert,   $eszla,     $ikt_dat,  $iktatta,  $igazol_dat, $igazolta,  $elkuld_dat, $elkuldte,  $szkp_dat,      $szkpfeldol,    $sablon,  $erk_dat.' 00-00-00',  $statusz,  $szallkod      )
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
                                case 'id'               : $id           = trim($akt_tomb[$key]);  break;
								case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
								case 'oracikkid'        : $oracikkid    = trim($akt_tomb[$key]);  break;
                                case 'cikkmee'          : $cikkmee      = trim($akt_tomb[$key]);  break;
                                case 'kcs_19'           : $szakter_id   = trim($akt_tomb[$key]);  break;
                                case 'segedanyag'       : $segedanyag   = trim($akt_tomb[$key]);  break;
                                case 'helyi'            : $helyi        = trim($akt_tomb[$key]);  break;
                                case 'leiras'           : $leiras       = trim($akt_tomb[$key]);  break;
                                case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;
							}
						}

                        $adatbazis_kapcsolat->i('cikktorzs',
                            array('id', 'nev', 'oracikkid', 'cikkmee', 'szakter_id', 'segedanyag',  'helyi', 'leiras', 'update_usr', 'insert_dt', 'insert_usr' ),
                            array($id,  $nev,  $oracikkid,  $cikkmee,  $szakter_id,  $segedanyag,   $helyi,   $leiras, $update_usr,  $insert_dt,  $insert_usr  )
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
                                case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
								case 'insert_usr'       : $insert_usr   = trim($akt_tomb[$key]);  break;
                                case 'id'               : $id           = trim($akt_tomb[$key]);  break;
                                case 'objid'            : $obj_id       = trim($akt_tomb[$key]);  break;
                                case 'gepid'            : $gep_id       = trim($akt_tomb[$key]);  break;
                                case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
                                case 'cikltip'          : $cikltip      = trim($akt_tomb[$key]);  break;
                                case 'ciklszorz'        : $ciklszorz    = trim($akt_tomb[$key]);  break;
                                case 'ciklnap'          : $ciklnap      = trim($akt_tomb[$key]);  break;
                                case 'hatosagi'         : $hatosagi     = trim($akt_tomb[$key]);  break;
                                case 'utnap_d'          : $utnap_d      = trim($akt_tomb[$key]);  break;
                                case 'leiras'           : $leiras       = trim($akt_tomb[$key]);  break;
								case 'telepid'          : $telephely_id = trim($akt_tomb[$key]);  break;
                                case 'riaszt'           : $riaszt       = trim($akt_tomb[$key]);  break;
                                case 'hozrend'          : $szemelyzet_id= trim($akt_tomb[$key]);  break;
                                case 'ciklho'           : $ciklho       = trim($akt_tomb[$key]);  break;
                                case 'ciklaltip'        : $ciklaltip    = trim($akt_tomb[$key]);  break;
                                case 'esnap_d'          : $esnap_d      = trim($akt_tomb[$key]);  break;
                                case 'kezdho'           : $kezdho       = trim($akt_tomb[$key]);  break;
                                case 'kezdnap'          : $kezdnap      = trim($akt_tomb[$key]);  break;
                                case 'vegeho'           : $vegeho       = trim($akt_tomb[$key]);  break;
                                case 'vegenap'          : $vegenap      = trim($akt_tomb[$key]);  break;
                                case 'aktkezd_d'        : $aktkezd_d    = trim($akt_tomb[$key]);  break;
                                case 'aktvege_d'        : $aktvege_d    = trim($akt_tomb[$key]);  break;
                                case 'ciklhet'          : $ciklhet      = trim($akt_tomb[$key]);  break;
                                case 'idoszakos'        : $idoszakos    = trim($akt_tomb[$key]);  break;
                                case 'utesnap_d'        : $utesnap_d    = trim($akt_tomb[$key]);  break;
                                case 'esem_d'           : $esem_d       = trim($akt_tomb[$key]);  break;
                                case 'loginnev'         : $loginnev     = trim($akt_tomb[$key]);  break;
                                case 'hataly_d'         : $hataly_d     = trim($akt_tomb[$key]);  break;
                                case 'aktiv'            : $aktiv        = trim($akt_tomb[$key]);  break;
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
                                array('id', 'kezdho', 'kezdnap', 'vegeho', 'vegenap', 'idoszakos', 'aktiv', 'telephely_id', 'obj_id', 'gep_id', 'nev', 'leiras', 'riaszt', 'szemelyzet_id', 'hatosagi', 'cikltip',     'ciklaltip', 'ciklho', 'ciklho2', 'ciklhet', 'ho_rendsz_het', 'ciklnap', 'ciklszorz', 'honapjan', 'hataly_d', 'utnap_d', 'aktkezd_d', 'aktvege_d', 'esnap_d', 'utesnap_d', 'esem_d', 'loginnev', 'update_usr', 'insert_dt', 'insert_usr' ),
                                array($id,  $kezdho,  $kezdnap,  $vegeho,  $vegenap,  $idoszakos,  $aktiv,  $telephely_id,  $obj_id,  $gep_id,  $nev,  $leiras,  $riaszt,  $szemelyzet_id,  $hatosagi,  $cikltip,      $ciklaltip,  $ciklho,  $ciklho2,  $ciklhet,  $ho_rendsz_het,  $ciklnap,  $ciklszorz,  $honapjan,  $hataly_d,  $utnap_d,  $aktkezd_d,  $aktvege_d,  $esnap_d,  $utesnap_d,  $esem_d,  $login_nev, $update_usr,  $insert_dt,  $insert_usr  )
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
        
	}elseif(isset($_GET['anyagigenyles'])){

		if(!isset($_FILES['termekek'])){

			$html .= nl2('
							<form method="post" action="'.self().'?anyagigenyles" enctype="multipart/form-data">
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
                                case 'update_dt'        : $update_dt    = trim($akt_tomb[$key]);  break;
								case 'update_usr'       : $update_usr   = trim($akt_tomb[$key]);  break;
								case 'insert_dt'        : $insert_dt    = trim($akt_tomb[$key]);  break;
                                case 'insert_usr'       : $loginnev     = trim($akt_tomb[$key]);  break;
                                case 'recid'            : $recid        = trim($akt_tomb[$key]);  break;
                                case 'iktszam'          : $ikt_szam     = trim($akt_tomb[$key]);  break;
								case 'telepid'          : $telephely_id = trim($akt_tomb[$key]);  break;
                                case 'cikkid'           : $cikk_id      = trim($akt_tomb[$key]);  break;
                                case 'nev'              : $nev          = trim($akt_tomb[$key]);  break;
                                case 'cikkmee'          : $cikkmee      = trim($akt_tomb[$key]);  break;
                                case 'igmen'            : $igmen        = trim($akt_tomb[$key]);  break;
                                case 'igleir'           : $igleir       = trim($akt_tomb[$key]);  break;
                                case 'jhmen'            : $jhmen        = trim($akt_tomb[$key]);  break;
                                case 'rndmen'           : $rndmen       = trim($akt_tomb[$key]);  break;
                                case 'rndear'           : $rndear       = trim($akt_tomb[$key]);  break;
                                case 'rndid'            : $rndid        = trim($akt_tomb[$key]);  break;
                                case 'erkmen'           : $erkmen       = trim($akt_tomb[$key]);  break;
                                case 'erkear'           : $erkear       = trim($akt_tomb[$key]);  break;
                                case 'erkcimid'         : $erkcimid     = trim($akt_tomb[$key]);  break;
                                case 'szlacimid'        : $szlacimid    = trim($akt_tomb[$key]);  break;
                                case 'elutleir'         : $elutleir     = trim($akt_tomb[$key]);  break;
                                case 'statid'           : $stat_id      = trim($akt_tomb[$key]);  break;
                                case 'keszpenz'         : $keszpenz     = trim($akt_tomb[$key]);  break;
                                case 'uileir'           : $uileir       = nl2(trim($akt_tomb[$key]));  break;
                                case 'szallitoid'       : $szallito     = trim($akt_tomb[$key]);  break;
                                case 'oracikkid'        : $oracikkid    = trim($akt_tomb[$key]);  break;
                                case 'aparecid'         : $aparecid     = trim($akt_tomb[$key]);  break;
                                case 'kepfajl'          : $kepfajl      = trim($akt_tomb[$key]);  break;
                                case 'rnd_dt'           : $rnd_dt       = trim($akt_tomb[$key]);  break;
                                case 'kulsosnek'        : $kulsosnek    = trim($akt_tomb[$key]);  break;
                                case 'szlaid'           : $szlaid       = trim($akt_tomb[$key]);  break;
                                case 'rndmenleir'       : $rndmenleir   = trim($akt_tomb[$key]);  break;
                                case 'vbrutert'         : $vbrutert     = trim($akt_tomb[$key]);  break;
                                case 'vnetert'          : $vnetert      = trim($akt_tomb[$key]);  break;
                                case 'kcs_21'           : $kcs_21       = trim($akt_tomb[$key]);  break;
                                case 'arfolyam'         : $arfolyam     = trim($akt_tomb[$key]);  break;
                                case 'atvaltva_d'       : $atvaltva_d   = trim($akt_tomb[$key]);  break;
                                case 'vrndear'          : $vrndear      = trim($akt_tomb[$key]);  break;
                                case 'verkear'          : $verkear      = trim($akt_tomb[$key]);  break;
							}
                            
                            if($statid=='10'){
                                $statid = '05';
                            }
                            
                            $cikk_id = sprintf('%07d', $cikk_id);

                            $atvaltva_d = str_replace('.', '-', $atvaltva_d);
                            $rnd_dt     = str_replace('.', '-', $rnd_dt);
                            $insert_dt  = str_replace('.', '-', $insert_dt);
                            $update_dt  = str_replace('.', '-', $update_dt);
                            
                            $arfolyam   = str_replace(',', '.', $arfolyam);
                            $erkear     = str_replace(',', '.', $erkear);
                            $erkmen     = str_replace(',', '.', $erkmen);
                            $igmen      = str_replace(',', '.', $igmen);
                            $jhmen      = str_replace(',', '.', $jhmen);
                            $rndear     = str_replace(',', '.', $rndear);
                            $rndmen     = str_replace(',', '.', $rndmen);
                            $vbrutert   = str_replace(',', '.', $vbrutert);
                            $verkear    = str_replace(',', '.', $verkear);
                            $vnetert    = str_replace(',', '.', $vnetert);
                            $vrndear    = str_replace(',', '.', $vrndear);
						}
                        
                        if($recid){
                            
                            $adatbazis_kapcsolat->i('anyagigenyles',
                                array('recid', 'ikt_szam', 'telephely_id', 'cikk_id', 'nev', 'cikkmee', 'igmen', 'igleir', 'jhmen', 'rndmen', 'rndear', 'rndid', 'erkmen', 'erkear', 'erkcimid', 'szlacimid', 'elutleir', 'stat_id', 'keszpenz', 'uileir', 'szallito', 'oracikkid', 'aparecid', 'kepfajl', 'rnd_dt', 'kulsosnek', 'szlaid', 'rndmenleir', 'vbrutert', 'vnetert', 'kcs_21', 'arfolyam', 'atvaltva_d', 'vrndear', 'verkear',  'loginnev', 'update_usr', 'insert_dt'  ),
                                array($recid,  $ikt_szam,  $telephely_id,  $cikk_id,  $nev,  $cikkmee,  $igmen,  $igleir,  $jhmen,  $rndmen,  $rndear,  $rndid,  $erkmen,  $erkear,  $erkcimid,  $szlacimid,  $elutleir,  $stat_id,  $keszpenz,  $uileir,  $szallito,  $oracikkid,  $aparecid,  $kepfajl,  $rnd_dt,  $kulsosnek,  $szlaid,  $rndmenleir,  $vbrutert,  $vnetert,  $kcs_21,  $arfolyam,  $atvaltva_d,  $vrndear,  $verkear,   $loginnev,  $update_usr,  $insert_dt   )
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
