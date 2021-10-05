<?php/*
ÚJ MENÜPONT KIALAKTÍÁSÁNÁL EZEKRE ÜGYELJ
- esemenyek.php $_SESSION['ketHasabos'] listát bővítsd
- ugyanitt alatta a switch($tabla) résznél is létre kell hozni egy switch-et
- készíts egy tabla.php fájlt is (a !minta.php-ból)
- header.php-ban a menüpontot alakítsd ki
- admin.js-ben a ketHasabos = new Array rész felsorolását bővítsd ki
- fuggvenyek.php-ben a szamla_lista() függvényt bővítsd ki a if( $bejegyzesek ) résznél, és annak megfelelően egy új függvényt is hozz létre.
- meta.php-ban is legyen lekezelve az oldal címe
- Ha a menüpont tartalmaz dokumentumokat is, akkor a fuggvenyek.php-ban a dokumentumtarLista() függvényben a $menupontok tömböt is tartsd karban
- Majd a jogosultásgoat ne felejtsd el beállítani (törölni, updatelni, látni ki tud)
 
ÚJ BEJEGYZÉS FELTÖLTÉS IKONRA KATTINTVA MI TRÖTÉNJEN
- header.php-ben erre keress rá: "Új ikonra kattintva mi történik"
- admin.js-ben erre keress rá: "Új ikonra kattintva mi történik"
 
ÚJ FÜL KIALAKÍTÁSAKOR
- adott php fájl elején a $ful_tomb -öt egészítsd ki, illetve közvetelnül előtte a $_GET-ekve is vedd fel az új paramétereket
- adott php fájlban az if(isset($_GET['modosit']))-hoz hasonlóan vedd fel a fül paraméterénke megfelelő feltételt
- Előbbinél lényeges, hogy legyen kimenetel, ajax számára. Legalább egy mondatot írj ki.
- admin.js-ben a "param = get_get([" -ra keress rá (többször), és itt is vedd fel az új fülek paraméterét
 
LE-FEL LÉPTETETÉS LEKEZELÉSE
- admin.js "Léptetés lefele nyíllal, lépkedve le fel"-re keress rá
 
LOGINNEV nevű oszlop feltöltése utólag az insert_usr alapján
Ha kell egy loginnev nevű oszlop, de csak insert_usr adataid vannak, akkor az index.php elején találsz egy $_GET['loginnev_gyart'] karbantartó függvényt, amivel fel tudod tölteni az adatokat.
FONTOS: ilyenkor  a fuggvenyek.php-ban az i_u()-nél fel kell venned a 'loginnev' oszlopot a tömbbe (lásd gépek esete):
       } else if( $tabla == 'cikktorzs' ) {
           array_push($oszlopok, 'loginnev'                        );
           array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );
       }

JOGOSOLTSÁGKEZELÉSKOR, HA EGY ŰRLAP NEM SZERKESZTHETŐ:
- menüből vedd ki
- php fájl legelejére, és a $_GET['uj'] részhez is tedd be feltételnek, ne lehessen megkerülni a rendszer link ismeretében
- php fájlnál a módosítás Mentés gomb megjelenését is ehhez a joghoz kötni
- header.php-ben az új felvitel és törlés ikonokat zárd ki (nem elég disabled, meg dispaly none, mert kijátszhatják a rendszert). Erre keress rá: "Törlés és új gombok ne is látsszon, ha nincs hozzá joga".
- esemenyek.php-ban az if( isset( $_GET[$ertek['torid']] ) ) résznél szintén blokkold a tölést
- törlés ikonra kattintva hibaüzenet (pl. státusz miatt nem szerkeszthető): addott php fájl: <input type="button" id="recordTorol">-t keresd
 
TÖRLÉSKOR A KAPCSOLÓDÓ TÁBLÁK LEKEZELÉSE:
- esemenyek.php-ben a if( isset( $_GET[$ertek['torid']] ) ) részt keres, és ennek a végén találod.
 
BAL OLDALI NAVIGÁCIÓS PANEL TÉTELEINEK KISZÁMÍTÁSA (WHERE LEKÉRDEZÉS) + NAV PANEL TARTALMÁNAK KIÍRATÁSA ÉS RENDEZÉSE + RENDEZÉSI IRÁNY NYILAI
- Ha nincs bal nav panelre szükség, akkor azt a header.php-ben tudod beállítani a $bal_nav_mentes tömbnél
- fuggvenyek szamla_lista()-ban kerül kiszámításra a where feltétel. Ez a header.php és esemenyek.php-ban hívódik meg (+ weblap.php-ban is, hogy az ID-kat ellenőrizzük)
- A lista tartalmát viszont a függvényekben tudod beállítani: szamla_lista().
- Bal oldali nav panel rendezése (sok JOIN-al) szintén a fuggvenyek.php szamla_lista() részben tudod beállítani
- szamla_lista()-ban a "Bal oldali nav panel select-je" szövegre keress rá
- Adott menüpont bal oldali navpanel szerkezete a menüpont DB táblaneve+Lista függvénnyel állítható be. Pl. anyagigenylesLista() (fuggvenyek.php)
- A rendezés select rendes feliratait a getOszlopNev() függvénynél állítható be (oszlolpnévhez kötött felirat)
 
SZŰRÉS LEKEZELÉSE:
- szures.php, amit header.php hív meg. Ebben vannak beállítva a kapcsolódó táblák szűrő mezői (pl. Megjegyzés)
- esemenyek.php-ban a tömböt egészítsd ki a 'szuro' => 1 paraméterrel.
- fuggvenyek.php adott php fájlt visszadja-e a get_akt_tomb() függvénnyel?
- Ha kapcsolódó tábla egy adatára szeretnél szűrni, akkor keresd a "// Kapcsolódó táblák szűrő mezői" kommentet a szures.php-ban
- SQL-t a get_szures_where_kieg() függvény számolja ki. Itt szintén be kell állítani a kapcsolódó táblákat
- A kapcsolódó táblákat még az esemenyek.php isset($_POST['f_o_szures_submit']) részénél kell lekezelni
- fuggvenyek.php-ben a kapcsolódó táblák miatt még itt állítsd be a működést: // Szűréskor kell pár JOIN-olás a külső táblákhoz
- Lehet szűrni arra is, hogy van-e csatolt dokumentum vagy sem. Ezt a szures.php $dokumentumos_oldal változónál állítjuk be.
- Ha a szűrőben megjelenő listában egyedi nevet szeretnél adni, akkor az esemeny tömbben a 'megn_szurobe' paraméterben tudod megadni.
 
SORREDEZÉS
- Az A-Z és Z-A-t az admin.js bejegyzesekLista() függvény vége állítja. Illetve a header.php, maikor betölt az oldal.
- A kilsitázott tételek valódi sorrendjét a fuggvenyek.php -ban szamla_lista() függvény elején kerül beállításra
- Az esemenyek.php -ban a rendezhető mezőknél az alapértelmezett irányt is meg tudod adni egy tömbben, de nem kötelező (ha nincs, akkor marad ami volt). 
 
NYOMTATÁSI NÉZETEK KIGENEÁRLÁSA:
- header.php hívja meg a nyomtatas.php
 
STÁTUSZOK KARBANTARTÁSA
- addStatusz() függvénnyel írj be értéket és akkor rendben lesz. Ennek előfeltétele, hogy már egy státusza legyen (pl. egy alap NYITOTT).
- statusz_text.php-ben taláhatóak a megnevezések
 
KIJELÖLÉSEK MŰKÖDÉSE (SOROK MEGJELÖLÉSE BAL NAVPANELEN ÉS FÜLEK ALATTI SOROKNÁL)
- admin.js-ben erre keress rá: Aloldalon/fülek alatti felsorolás kijelölések lekezelése. Pl. felhasználók szállodái.
- admin.js-ben erre keress rá: Csoportra kattintás
- admin.js-ben erre keress rá: Bal oldali nav panelen kattintunk egy bejegyzésre. Ekkor lekezeljük mi történik a kijelöésekkel
 
CIKKTÖRZSBŐL LEKÉRT AUTOCOMPLETE ESETÉN A MENNYISÉGI EGYSÉG AUTOMATIKUSAN BEÍRÓDIK
Ha van egy autocompletes input, ami cikktörzsből dolgozik, akkor ebben az űrlapban elhelyezett <span class="insertMee"></span> span-ba be fog íródni a mennyiségi egység is
 
GOMBOK KISZERVEZÉSE ÁLTALÁNOSAN
- Új feltöltés űrlap feltétele elé összegyűjteni a jogosultásgokat és kialakítani a nyomógombokat (pl. erteklista.php mintájára) Erre keress rá: "Gombok kirajzolása minden oldalra"
- Új és modosít részen belül törölni minden $gomb változót, de csak miután beállítottad a gombokat, kialakítottad a jogokat.
- Ha van extra fül, akkor ott a $szamla_html-t meg kell toldani a $gombok-al is
- $muv_style változó se maradjon feleslegesen a uj/modosit ágban
 
CSOPORTOSÍTÁS ENGEDLYEZÉSE
- A bal oldali navpanel UL-jének ha van "kijelolheto" class-ja, akkor lehet több sort kijelölni
- Csoport nevére kattintva mi történjen: "csoport_nevre_kattintas"-ra keress rá. Ezt a class nevet kell ráadni.
 
SAJÁT JS FÁJLOK
- Ha egy menüpont alatt nagyon sok JS függvényt használunk, akkor azt nem az admin.js-ben gyűjtjük vég nékül, hanem egy saját JS fájlt kap. Pl. munkalap.js. Ezt a header.php-ban hívjuk meg.
 
FÓKUSZÁLÁSOK
- Ha azt szeretnéd, hogy Esc után is a hibát kiváltott mezőre fókuszáljunk, akkor adj neki egy "hibas_ide_focus" class-t.
 
JOBB OLDALI NAVPANELRE KATTINTVA BETÖLT EGY SOR, LEFUT A HÁTTÉRBEN EGY AJAX
- Ezzel kapcsolatos JS-t itt találod: admin.js szamlaBetolt();
  
SZÁMLA IMPORT WEBSERVICE ANDOCBÓL
- andoc.php-kezeli
 