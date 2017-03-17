<?php
/*  tartalom.php => Többnylevű oldalak szövegeit tároljuk le itt                                                                                        *\

    Ha egy weboldal többnyelvű vagy tegeződni/magázódni is tud, akkor az egyes feliratokat, mondatokat vagy tartalmakat többszörösen le kell tárolni egy
    nagy tömbben. Pl: 'bemutatkozas' =>  array( $oszlop1 => 'Bemutatkozás', $oszlop2 => 'Introduction'), ahol az $oszlop1 a magyar az $oszlop2 pedig az
    angol szöveget tartalmazza. Végeredményben tehát egy nagy, kétdimenziós (vagy több) $tartalom_tomb tömb jön létre, ahol az első dimenzió egy általunk
    megválasztott azonosító (kulcs) második pedig a kívánt nyelv. Pl.: $tartalom_tomb['bemutatkozas']['hu']-ben a Bemutatkozás szerepel majd míg
    $tartalom_tomb['bemutatkozas']['en']-ben az Introduction szerepel. Ezt élesben a $tartalom_tomb['bemutatkozas'][$_SESSION['nyelv']]-al íratjuk ki,
    mivel a $_SESSION['nyelv']-ben szerepel az aktuálisan kiválasztott nyelv.
     
    Itt egy mintatömb amiből ki lehet indulni. Egy-egy szót vagy mondatot érdemes tehát ezekbe felvinni, viszont komolyabb és hosszabb szövegeket célszerű
    sablonfájlokba kimenteni (pl. magunkrol_hu.html és magunkrol_en.html). Ha túl sok sablofájl létezne, akkor akár külön könyvtár is készíthető számukra
    (pl. sablon/hu/magunkrol.html és sablon/en/magunkrol.html), majd ezekre hivatkozni a $_SESSION['nyelv']-el.
    
    Képelemek (amin szöveg is szerepel vagy függ valami miatt a nyelvtől), azt a sablonhoz hasonlóan érdemes kategoriak_hu.png és kategoriak_en.png
    képelemként letárolni majd a $_SESSION['nyelv']-el hivatkozni rá (kategoriak_'.$_SESSION['nyelv'].'.png).
    
    Gondolom már mondanom sem kell, ha a működés vagy bármi más függne még a nyelvtől, akkor így lehet ezt vizsgálni:
    if($_SESSION['nyelv']==hu){...} 
    
\*                                                                                                                                                      */






/*
// A tömböt újabban egy szotar.csv-ből olassuk be, amely CSV-t az adminról is könnyen szerkeszteni lehet az admin/forditas.php mintakóddal.
setlocale(LC_ALL, 'hu_HU');
$i = 0;
$tartalom_tomb    = array();
$dupla_kulcs_szur = array();
$mibol = 'Windows-1250';//Windows-1250
if( ($csv_fajl = fopen(get_utvonal().'szotar.csv', 'r') ) !== FALSE){
    
    while(($sor = fgetcsv($csv_fajl, 0, ';')) !== FALSE){
        
        //Első sor magyarázat
        if($i>0){
            
            //Dupla kulcsok szűrése
            if(trim($sor[0]) && in_array($sor[0], $dupla_kulcs_szur)){
                header('Content-Type: text/html; charset=UTF-8'.get_web_charset());
                echo 'Hiba! A szótárfájlban duplán szerepel a következő kulcs: '.$sor[0] . '<br>';
            }else{
                array_push( $dupla_kulcs_szur, $sor[0] );
            }

            //Iconv (inkább az üreset iconvozzuk, mintsem többször ugyanazt, így ez legyen az első lépés)
            $sor[1] = str_replace('&', '&amp;', iconv($mibol, get_web_charset(), $sor[1]));
            $sor[2] = str_replace('&', '&amp;', iconv($mibol, get_web_charset(), $sor[2]));

            //Hiányos adat esetén a szomszédos nyelv jelenjen meg
            if(!$sor[2]){ $sor[2]=$sor[1]; }
            if(!$sor[1]){ $sor[1]=$sor[2]; }

            $tartalom_tomb[$sor[0]] = array('hu' => $sor[1], 'en' => $sor[2]);

        }
        
        $i++;
    }
    
    fclose($csv_fajl);
}


//Régi megoldás: tömb létrehozása manuálisan. Ettől sokkal inkább ajánlott a szótárfájl használata (szavak.csv)
/*
//Az egyes oszlopnevek. Ez alapján tudunk majd így rájuk hivatkozni: $tartalom_tomb['valami']['hu'];  pontosabban $tartalom_tomb['valami'][$_SESSION['nyelv']];
$oszlop1 = 'hu';
$oszlop2 = 'en';
$oszlop3 = 'de';

$tartalom_tomb=array(
   'menukep' => array('hu' => '',	'en' => '_en'),

   'zaszlo_txt'    => array( $oszlop1 => 'válasszon nyelvet:', $oszlop2 => 'choose language:'),
   'zaszlo_hu_src' => array( $oszlop1 => 'elemek/hu.gif'),
   'zaszlo_hu_txt' => array( $oszlop1 => 'Magyar',             $oszlop2 => 'Hungarian'),
   'zaszlo_en_src' => array( $oszlop1 => 'elemek/en.gif'),
   'zaszlo_en_txt' => array( $oszlop1 => 'Angol',              $oszlop2 => 'English'),
   
   'minden_jog'    => array( $oszlop1 => 'Minden jog fenntartva', $oszlop2 => 'All rights reserved',  $oszlop3 => 'Alle rechte vorbehalten'),
   'keszitette'    => array( $oszlop1 => 'Készítette: WebDeb - ahol a weblapok születnek',          $oszlop2 => 'Created by: www.WebDeb.hu',  $oszlop3 => 'Verfertigt: www.WebDeb.hu'),
	'404'    =>  array( $oszlop1 => '
		<p><strong>A keresett tartalom nem található vagy már megszűnt.</strong></p>
		<p>Az oldal 5 másodperc múlva a főlapra ugrik!</p>
	',             $oszlop2 => '
		<p><strong>The page you requested can not be found!</strong></p>
		<p>You will be redirected to the homepage ion 5 seconds!</p>
	',             $oszlop3 => '
		<p><strong>Inhalt ist nicht verfügbar.</strong></p>
		<p>Die Seite springt innerhalb 5 Minuten zu der Hauptseite.</p>
	'),
	
   'bemutatkozas'     =>  array( $oszlop1 => 'Bemutatkozás',            $oszlop2 => 'Introduction'),
   'bemutatkozas_src' =>  array( $oszlop1 => 'elemek/bemutatkozas.gif', $oszlop2 => 'elemek/bemutatkozas_en.gif'),
   
);

$i    = 0;
$keyz = array_keys($tartalom_tomb);
foreach($tartalom_tomb as $ertek){
	if(!$ertek[$oszlop2]){
		$tartalom_tomb[$keyz[$i]][$oszlop2]=$ertek[$oszlop1];
	}
	
	//if(!$ertek[$oszlop3]){
	//	$tartalom_tomb[$keyz[$i]][$oszlop3]=$ertek[$oszlop1];
	//}
	$i++;
}





?>*/