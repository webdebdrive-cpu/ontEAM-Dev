<?php
require('weblap.php');

// JS alapú fájlfeltöltés esetén a $_FILES['file'] tömbben vannak az adatok a $_FILES[megnevezés] helyett
if($_FILES['file']){
    $_FILES[$_POST['megnevezes']] = $_FILES['file'];
}

//Tiltott fájlformátumok kiszűrése, szitnén nincs feltöltés
$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
if( in_array( $_FILES[$_POST['megnevezes']]['type'], $tiltott_fajlok ) ){
    exit;
}

$tomb = array(
                'algoritmus' => $_POST['algoritmus'],
                'oszlop'     => $_POST['oszlop'],
                'tabla'      => $_POST['tabla'],
                'meret'      => $_POST['meret'],
                'csakkep'    => $_POST['csakkep'],
                'reload'     => $_POST['reload']
             );

fajl_menedzser($_POST['megnevezes'], $tomb, $_POST['sorid'], $_POST['uj_mod']);


/**
*	Kép menedzser kizárólag az eseményekhez való
*	FONTOS: ha képet töltünk fel, magától odateszi a kiterjesztést (.jpg .gif .png). 
*	Átlagos fájlok esetén adjuk meg a kiterjesztést	
*
*	@param string $megnevezes tömben a képváltozó neve pl.: hir_kep
*	@param array $tomb Tömb ami alapján dolgozunk. Jelen esetben mindig az $ertek[tomb] lesz az aktuális tömb, ezzel kell meghívni
*	@param string $tabla Adatbázis tábla neve (ilyen nevű mappába másol)
*	@param string $tabla_id Utolsó beszúrt sor, vagy módosított sor (amennyiben a fájlneve a sorid-ből származna)
*	@param string $uj_mod Új beszúrás vagy mod (uj|mod) (Ha uj akkor a régi kép felülíródhat, mod esetén új képnevet gyárt pl. teszt_1.jpg)
*	@param integer $meret méret limit, ami alapból 400 Kb
*	@param boolean $csakkep ez alapból igaz, tehát csak jpg, png és gifet enged feltölteni
*
*	@return array 0. argumentuma a kép neve, 1. pedig a hibaüzenet (ha van) <== ezt a web_ftp-ből örökli
*	k1: túllépte a limitet
*	k2: nem képtipusú fájl
*	k3: ilyen nevű fájl már létezett a szerveren és törölni nem lehetett
*	k4: nem sikerül felmásolni
*	k5: nincs `kep` oszlop az adatbázisban
*/
function fajl_menedzser($megnevezes, $tomb, $sor_id, $uj_mod){
    
    global $adatbazis_kapcsolat;

    //Hogy ne csak admin alól tudjuk használni
	if(basename( dirname( self() ) ).'/' == get_admin() ){
        $utvonal = '../'.get_utvonal();
    }else{
        $utvonal = get_utvonal();
    }

    $algoritmus  = $tomb['algoritmus'];
    $fajl_oszlop = $tomb['oszlop'];
    $tabla       = $tomb['tabla'];
    $meret       = $tomb['meret'];
    $csakkep     = $tomb['csakkep'];
    $reload      = $tomb['reload'];
    
    if($csakkep=='false'){
        $csakkep = 0;
    }
    
    //Ha új feltöltés, akkor a képet ideiglenesen lementjük, míg a bejegyzés el nem készül hozzá
    if($_POST['uj_feltolt']){
        
        if($algoritmus=='fajlnev'){
            $fajlnev = str_replace('.'.kit($_FILES[$megnevezes]['name']), '', $_FILES[$megnevezes]['name']);
        }elseif($algoritmus=='id'){
            $fajlnev = 'get_id';
        }
        //A fájl sorszáma pedig a SESSION id-ja, ezzel azonosítva az aktuális munkafolymatot
        //majd #két sharp közt# az oszlop neve, hogy tudjuk, az adatbázisban melyik oszlopba kell beírni
        //majd a következő #két sharp közt# a fájl végleges neve (ezt persze csak akkor tudjuk ha fájlnév vagy ID alapján mentjük el)
        //végül a timestamp, hogy biztosan egyedi legyen
        $sor_id     = session_id().'|||'.$fajl_oszlop.'|||'.$fajlnev.'|||'.$_FILES[$megnevezes]['name'].'|||'.time();
        $algoritmus = 'id';             //Az algoritmus tehát ID szerinti, hogy ne legyen adatbázis művelet
        $tabla      = 'cache/jsfajlok'; //A tartalom mappán belül a 'cache' könyvtárba mentjük majd el ideiglenesen
    }

	switch($algoritmus){
		//case 'sorid': $kepnev=$sor_id; break;
		
		case 'fajlnev':
		
			$kepnev = $_FILES[$megnevezes]['name'];

            //Fájl
            $kit = '.'.kit($_FILES[$megnevezes]['name']);
            $kepnev  = fajlnev_keszit( str_replace( $kit, '', $_FILES[$megnevezes]['name'] ) ).$kit;
            $_FILES[$megnevezes]['name'] = utf8($_FILES[$megnevezes]['name']);

            //Ékezetek visszakonvertálása
            $mit   = array('&#337;',    '&#336;',   '&amp;#369;',   '&amp;#368;');
            $mivel = array('ő',             'Ő',            'ű',            'Ű'         );
            $_FILES[$megnevezes]['name'] = str_replace($mit, $mivel, $_FILES[$megnevezes]['name']);
            
            $sor = $adatbazis_kapcsolat -> select($fajl_oszlop, $tabla, 'id='.$sor_id);
            $sor = $sor[0];
			
            //ha a fájlt felülírjuk (tehát már el volt tárolva a neve a táblában), akkor levesszük a tábla nevét
            if( $sor[$fajl_oszlop] ){
				
				$mit      = array($tabla.'/');
				$mivel    = array('');
				$pathinfo = pathinfo($sor[$fajl_oszlop]);
				
				$kepnev    = str_replace($mit, $mivel, $pathinfo['filename']).'.'.kit($kepnev);
				$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után
				
			//Ha nincs a táblában a neve (új felöltés vagy módosítás, és sose volt kép, vagy törölve lett)
            }else{
					
				$pathinfo = pathinfo($kepnev);
				$kit      = $pathinfo['extension'];
				$kepnev   = $pathinfo['filename'];
				
				$i     = 1;
				$ujnev = $kepnev.'_1';
				//DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az, keresünk szabad számot
                while( glob($utvonal.$tabla.'/'.$ujnev.'*') ){
					$i++;
					$ujnev = $kepnev.'_'.$i;
				}
				
				$kepnev = fajlnev_keszit($ujnev).'.'.$kit;
				
                //Ez most azért false, mert a JSFF-nél ha egy forban több fjál input is szerepelt, akkor felülírták egymást
                $overwrite = false;
					
			} 
		break;
		
		//termék nevéből nyert képnév
		case 'nev':
			
			$kepnev  = stripslashes( strip_tags( htmlspecialchars_decode2( $_POST['nev'] ) ) );

            $sor = $adatbazis_kapcsolat -> select($fajl_oszlop, $tabla, 'id='.$sor_id);
            $sor = $sor[0];
            
            //ha a fájlt felülírjuk (tehát már el volt tárolva a neve a táblában), akkor levesszük a a tábla nevét
			if($sor[$fajl_oszlop]){
				
				$mit      = array($tabla.'/');
				$mivel    = array('');
				$pathinfo = pathinfo($sor[$fajl_oszlop]);
				
				$kepnev    = str_replace($mit, $mivel, $pathinfo['filename']).'.'.kit($_FILES[$megnevezes]['name']);
				$overwrite = true;	//Megmondjuk a webftp-nek, hogy az adott fáljt írja felül, ne szemeteljünk, takarítsunk magunk után
			
			//Ha nincs a táblában a neve (új felöltés vagy módosítás, és sose volt kép, vagy törölve lett)
			}else{
				
				//Itt a kiterjesztés az eredetileg felvitt fájl kiterjesztése lesz
				$kit   = kit($_FILES[$megnevezes]['name']);
				
				$i     = 1;
				$ujnev = $kepnev.'_1';
				//DE! megnézzük, hogy ez a '_1' nem foglalt-e. Ha az keresünk szabad számot
                while( glob($utvonal.$tabla.'/'.$ujnev.'*') ){
					$i++;
					$ujnev = $kepnev.'_'.$i;
				}
				
				$kepnev = fajlnev_keszit($ujnev).'.'.$kit;

                //Ez most azért false, mert a JSFF-nél ha egy forban több fjál input is szerepelt, akkor felülírták egymást
                $overwrite = false;
					
			}
		break;
		
		default:
			//elmentjük a kiterjesztést, kisbetűsre alakítva
            $kit = strtolower(substr($_FILES[$megnevezes]['name'],strrpos($_FILES[$megnevezes]['name'],'.'),strlen($_FILES[$megnevezes]['name'])));
			$kepnev = $sor_id.$kit;

			$algoritmus = 'sorid';//bizti kedvéért
			$sor_id     = null;//Kinullázzuk a táblát, hogy a web_ftp ne próbálkozzon SQL-el 
			$overwrite  = true;	//Sorid esetén mindig töröljük az előzőt (pl. 'sorid'.jpg-t)
		break;
	}
    
    return web_ftp($megnevezes, $tabla.'/'.$kepnev, $meret, $overwrite, $csakkep, $tabla, $fajl_oszlop, $sor_id, true);
	
}
?>