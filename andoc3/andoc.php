<?php
require_once '../weblap.php';

$url_elotag = get_url_elotag();

// Szerver felkonfigurálása
$soap = new SoapServer(
    $url_elotag.'onteam.wsdl',
    array('uri' => $url_elotag.'andoc.php')
);

$soap -> setClass('ServerClass');
$soap -> handle();

// A szerverre írunk egy osztályt, ez lesz a motor
class ServerClass {

    // Konstansok
    const SZAMLA_TIPUS_FENNTARTASI  = 'fenntartási számla';
    const SZAMLA_TIPUS_PROJEKT      = 'beruházási számla';
    const SZAMLA_TIPUS_ENERGIA      = 'energia számla';
    const SZAMLA_TIPUS_ANYAG        = 'műszaki anyag';
    const SZAMLA_TIPUS_MEGALL       = 'megállapodás számla';
    
    const SZERZ_TIPUS_FENNTARTASI   = 'fenntartás';
    const SZERZ_TIPUS_PROJEKT       = 'projekt';
    
    const KCS_21_HUF                = 5;
    const KCS_21_TXT                = 'HUF';
    const IU_USER                   = 'Andoc';
    const CHAR_CODING               = 'UTF-8';
    
    // Változók
    private $user_is_valid = false;
    private $login_hibauzi = 'Webservice használatához kérjük jelentkezzen be!';

    public function belepes($header) {
        if( $header->username == 'Andoc' & $header->password == 'AontEAM3' ) {
            $this -> user_is_valid = true;
        } else {
            Throw new SOAPFault ('Helytelen jelszó!', 401);
        }
    }
    
    // Számla importálása
    public function SzamlaAdatokImport( $szamla_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;
        
        $szamla = simplexml_load_string($szamla_xml, 'SimpleXMLElement');
        if( $szamla === false ) {
            Throw new SOAPFault ('Server', 'XML beolvasása nem sikerült. Lehet, hogy hibás karaktert tartalmaz?
                '.$szamla_xml);
        }
        $szamla_xml = date('Y-m-d H:i:s').$szamla_xml."\n\n\n";
        $szamla_xml = str_replace("><", ">\n<", $szamla_xml);

        // Tranzakciót indítunk
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // A számla adatai a "data -> register -> properties -> container"-ben lesz
        // mégpedig abban, amelyiknek "NIK1" vagy "NIK3" a neve
        $szamla_adatok = array();

        foreach( $szamla -> data -> register -> properties -> container as $cont ) {
            if( $cont['name'] == 'NIK1' ) {

                // Ezen belül van egy csomó "row -> property" értéke is
                // Ezekből csinálunk egy tömböt, hogy ne kelljen állandóan foreach-el bejárni
                // hanem tudunk adott jellemzőre hivatkozni
                foreach ($cont -> row -> property as $prop) {
                    $kulcs = (string)$prop['name'][0];
                    $szamla_adatok[$kulcs] = (string)$prop[0];
                }
            }
            if( $cont['name'] == 'NIK3' ) {

                // Ezen belül van egy csomó "row -> property" értéke is
                // Ezekből csinálunk egy tömböt, hogy ne kelljen állandóan foreach-el bejárni
                // hanem tudunk adott jellemzőre hivatkozni
                foreach ($cont -> row -> property as $prop) {
                    $kulcs = (string)$prop['name'][0];
                    $szamla_adatok[$kulcs] = (string)$prop[0];
                }

            }
        }

        // Kötelező mezők ellenőrzése ami Insert, update és delete utasításhoz is kellenek
        // Ha van 'Rögzítendő űrlap típusa' tag-ünk, akkor az kötelezően 'műszaki számla' értékű legyen
        if( $szamla_adatok['Rögzítendő űrlap típusa'] && mb_strtolower($szamla_adatok['Rögzítendő űrlap típusa'], self::CHAR_CODING) != 'műszaki számla' ) {
            Throw new SOAPFault ('Server', 'Nem műszaki számla!');
        }

        // registernumber megléte mindig kötelező
        if( !trim($szamla -> data -> register['registernumber']) ) {
            Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
        }
        $szamla_andoc_id = $andoc_id = $szamla -> data -> register['registernumber'];

        // Ha a Technikailag sztornózott tag értéke Igen, akkor a számlát sztornózni kell. 
        $tech_sztorno = 0;
        if( $szamla_adatok['Technikailag sztornózott'] == 'Igen' ) {
            $tech_sztorno = 1;
        }

        $kifizetes_import = 0;
        // Kifizetséről van szó, ha a Fizetési státusz tag-ben az alábbiak vannak
        if( $szamla_adatok['Fizetési státusz'] == 'kifizetetlen' || $szamla_adatok['Fizetési státusz'] == 'részben kifizetve' || $szamla_adatok['Fizetési státusz'] == 'kifizetett' ) {
            $kifizetes_import = 1;
        }

        // Ha kifizetésről van szó, akkor kötelező megadni a Számla főtípust is (sima insertnél nem feltétlen kötelező)
        if( $kifizetes_import && !$szamla_adatok['Számla főtípus'] ) {
            Throw new SOAPFault ('Server', 'Hiányzó számlafőtípus!');
        }

        if( trim($szamla -> data -> register -> file['name']) ) {
            Throw new SOAPFault ('Server', 'A számla nem befogadható, mert tartalmazza a kapcsolt file-t!');
        }

        // Típus alapján az adatbázis táblák azonosítása
        if( $szamla_adatok['Számla főtípus'] ) {
            $szamla_tipus = mb_strtolower($szamla_adatok['Számla főtípus'], self::CHAR_CODING);
            switch( $szamla_tipus ) {
                case self::SZAMLA_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szamla';    break;
                case self::SZAMLA_TIPUS_PROJEKT         : $tabla = 'projektszamla';         break;
                case self::SZAMLA_TIPUS_ENERGIA         : $tabla = 'energia_szamla';        break;
                case self::SZAMLA_TIPUS_ANYAG           : $tabla = 'anyag_szamla';          break;
                case self::SZAMLA_TIPUS_MEGALL          : $tabla = 'megallapodas_szamla';   break;
                default                                 : $tabla = '!hiba!';                break;
            }
        // Ha nem ismert a számla főtípusa, akkor az andoc számla táblával dolgozunk
        } else {
            $tabla = 'szamla';
        }
        if( $tabla == '!hiba!' ) {
            Throw new SOAPFault ('Server', 'Nem azonosítható be a számla főtípusa!');
        }

        $apa_recid_oszlop = $tabla.'_recid';

        // Azt, hogy ez insert vagy update lesz, azt már itt el kell dönteni, hisz update esetén nem kötelezőek a mezők!
        $extra_mezok = '';
        // projektszámla esetén további mezőket kell lekérni
        if( $tabla == 'projektszamla' ) {
            $extra_mezok .= ' elo_igazolva elo_igazolva_dt';
        }
        $szamla_db = $adatbazis_kapcsolat->s2('recid szla_szam erk_xml tetel_db sztornozva_dt utalvanyozva_dt igazolva_dt'.$extra_mezok, $tabla, "`andoc_id`='{$andoc_id}'");
        if( $szamla_db === false ) { $db_error = mysqli_error($db_kapcs); }

        // Ha létezik a számla az éles táblában, akkor sima ügy, azt kell updatelni
        if( $szamla_db['recid'] ) {
            $insert_update = 'update';

        // Ha a számla nincs benne az éles táblában...
        } else {
            
            // Ha nem létezik az éles táblában, akkor megnézzük az összes modul átmeneti tábláját is, lehet hogy ott van valamelyikben
            $atmeneti_tablak = array('fenntartasi_szamla_atmeneti', 'projektszamla_atmeneti', 'energia_szamla_atmeneti', 'anyag_szamla_atmeneti', 'megallapodas_szamla_atmeneti');
            foreach ($atmeneti_tablak as $atmeneti_tabla) {
                $atmenetiben_van = $adatbazis_kapcsolat -> s2('recid', $atmeneti_tabla, "`andoc_id`='{$andoc_id}'");
                if( $atmenetiben_van === false ) { $db_error = mysqli_error($db_kapcs); }

                // Ha valamelyik átmeneti táblában volna, akkor őt kell updatelni. Azaz a $tabla értékét írjuk át
                // és úgy megyünk tovább, mintha eleve ez lett volna a táblánk
                if( $atmenetiben_van['recid'] ) {
                    $tabla = str_replace('_atmeneti', '', $atmeneti_tabla);
                    break;
                }
            }
            // Ha az átmeneti táblában volt, akkor maradunk update üzemmódban, de most már az átmeneti táblába fogunk dolgozni
            if( $atmenetiben_van['recid'] ) {
                
                $insert_update = 'update';
            
            // Ha adott modul átmeneti táblájában sincs, akkor Insert.
            // Insert esetén mindig az Andoc számlák közé kerül be a számla ($tabla = 'szamla'),
            // innen osztják majd szét ontEAM felületen a megfelelő modulba
            } else {
                // Ha nem ismert az Andoc azonosító, és kifizetésről van szó,
                // akkor ez egy update lett volna, de nem sikerült beazonosítani
                if( $kifizetes_import ) {
                    Throw new SOAPFault ('Server', 'Nem azonosítható be az Andoc azonosító, így a kifizetés nem lehetséges!');
                }
                $insert_update = 'insert';
                $tabla         = 'szamla';
            }

        }

 // Throw new SOAPFault ('Server', $insert_update.' - '.$tabla);

        // Újabban nincs törlés funkció
//        // Törléshez ismernünk kell az andoc_id-t és a számla típusát
//        // Delete
//        if( $szamla -> torles == 1 ) {
//
//            $apa_tabla = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
//            if( !$apa_tabla ) {
//                Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
//            }
//
//            $return_uzi = 'törölve';
//
//            $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
//            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//            $sql = $adatbazis_kapcsolat->d($tabla.'_tetel', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
//            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//            $sql = $adatbazis_kapcsolat->d($tabla.'_kifizetesek', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
//            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//
//        // Insert/update
//        } else {

            // Az adatbázisban fontos, hogy a kezdő 0-ák meglegyenek, ezzel fogunk a későbbiekben dolgozni
            // A $szamla_adatok['Partner száma']-t viszont ne bántsuk, hogy el tudjuk később dönteni, hogy volt-e benne érték vagy sem.
            if( $szamla_adatok['Partner száma'] ) {
                $szallito_id = sprintf('%05d', $szamla_adatok['Partner száma']);
            }
            $szalloda_klaszter_original = $szamla_adatok['Szálloda - Klaszter'];

            // Az alábbiakat csak insert esetén kell vizsgálni
            if( $insert_update == 'insert' ) {
            
                if( !trim($szamla_adatok['Danubius azonosító']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó Danubius azonosító!');
                }
                if( !valos_datum_e($szamla_adatok['Számla kelte']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen keltezés!');
                }
                if( !valos_datum_e($szamla_adatok['Számla érkezés dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen érkezés dátum!');
                }
                if( !valos_datum_e($szamla_adatok['Iktatás dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen iktatás dátum!');
                }
                if( !trim($szamla_adatok['Rögzítő neve']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó rögzítő név!');
                }
                if( !trim($szamla_adatok['Számla sorszáma']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó számlaszám!');
                }
                // 2020.09.10 Kérésre ez mégsem kötelező adat
//                if( !trim($szamla_adatok['Szálloda - Klaszter']) ) {
//                    Throw new SOAPFault ('Server', 'Hiányzó szálloda!');
//                }
                if( !trim($szamla_adatok['Fizetés módja']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó fizetési mód!');
                }
                if( !trim($szamla_adatok['Partner száma']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szállító!');
                }

                if( !valos_datum_e($szamla_adatok['Teljesítés dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen teljesítés dátum!');
                }
                if( !valos_datum_e($szamla_adatok['Fizetési határidő']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen fizetési határidő!');
                }
                if( !trim($szamla_adatok['Számla bruttó összege']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó bruttó érték!');
                }
                if( !trim($szamla_adatok['Számla fizetője']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó számla fizetője adat!');
                }

                // Itt most "HUF", "EUR" stb-t kapunk, tehát az értéket és nem a kódot.
                if( !trim($szamla_adatok['Pénznem']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
                
                // Foglalt számlaszám ellenőrzése
                if( $szamla_adatok['Számla sorszáma'] ) {
                    // Átmeneti táblában
                    $letezo_ikt_szam = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`szla_szam`='{$szamla_adatok['Számla sorszáma']}' AND `szallito_id`='{$szallito_id}' AND `andoc_id`!='{$andoc_id}'");
                    if( $letezo_ikt_szam ) {
                        Throw new SOAPFault('Server', '"'.$szamla_adatok['Számla sorszáma'].'" számlaszám már létezik ezzel a partnerrel a FELDOLGOZÁSRA VÁRÓ számlák között!');
                    }

                    // Éles táblában
                    $letezo_ikt_szam = $adatbazis_kapcsolat->s2('recid', $tabla, "`szla_szam`='{$szamla_adatok['Számla sorszáma']}' AND `szallito_id`='{$szallito_id}' AND `andoc_id`!='{$andoc_id}'");
                    if( $letezo_ikt_szam ) {
                        Throw new SOAPFault ('Server', '"'.$szamla_adatok['Számla sorszáma'].'" számlaszám már létezik ezzel a partnerrel a FELDOLGOZOTT számlák között!');
                    }
                }
                
            // Módosításkor a dátum mezőket kell figyelni, hogy ha van, akkor helyesek-e
            } else {
                
                // Update esetén a szállodát akkor sem módosítjuk ha az XML tartalmazná, ez fixen legyen kinullázva.
                // Oka: a szálloda ontEAM oldalon módosulhat, ami Andoc oldalra nem kerül visszaadásra, és a legközelebbi update módosítaná a friss beállításainkat
                // Viszont ha az Andoc számlák között van, akkor később ki lesz törölve és újból létrehozva, erre az esetre mentjük ki a $szalloda_klaszter_original -ba
                $szamla_adatok['Szálloda - Klaszter'] = '';
                
                if( $szamla_adatok['Számla kelte'] && !valos_datum_e($szamla_adatok['Számla kelte']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen keltezés!');
                }
                if( $szamla_adatok['Számla érkezés dátuma'] && !valos_datum_e($szamla_adatok['Számla érkezés dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen érkezés dátum!');
                }
                if( $szamla_adatok['Iktatás dátuma'] && !valos_datum_e($szamla_adatok['Iktatás dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen iktatás dátum!');
                }
                if( $szamla_adatok['Teljesítés dátuma'] && !valos_datum_e($szamla_adatok['Teljesítés dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen teljesítés dátum!');
                }
                if( $szamla_adatok['Fizetési határidő'] && !valos_datum_e($szamla_adatok['Fizetési határidő']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen fizetési határidő!');
                }
                
                // Ha a számla sztornózott állapotban van, akkor már nem lehet kifizetést csinálni rá
                if( $kifizetes_import && $szamla_db['sztornozva_dt'] && $szamla_db['sztornozva_dt'] != '0000-00-00 00:00:00' ) {
                    Throw new SOAPFault ('Server', 'A számla már sztornózva van, így nem lehet kifizetni!');
                }
                
                // Ha NEM kifizetésről van szó sem Technikai sztornózásról, akkor sima számla update, és ekkor pár mező kötelező
                if( !$kifizetes_import && !$tech_sztorno ) {
                    
                    if( !trim($szamla_adatok['Fizetés módja']) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó fizetési mód!');
                    }
                    if( !trim($szamla_adatok['Partner száma']) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó szállító!');
                    }

                    // Itt most "HUF", "EUR" stb-t kapunk, tehát az értéket és nem a kódot.
                    if( !trim($szamla_adatok['Pénznem']) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                    }
                    
                }
            }

            
            // Foglalt iktatószám ellenőrzése
            if( $szamla_adatok['Danubius azonosító'] ) {
                // Átmeneti táblában
                $letezo_ikt_szam = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`ikt_szam`='{$szamla_adatok['Danubius azonosító']}' AND `andoc_id`!='{$andoc_id}'");
                if( $letezo_ikt_szam ) {
                    Throw new SOAPFault ('Server', '"'.$szamla_adatok['Danubius azonosító'].'" Danubius azonosító már létezik a FELDOLGOZÁSRA VÁRÓ számlák között!');
                }
                
                // Éles táblában
                $letezo_ikt_szam = $adatbazis_kapcsolat->s2('recid', $tabla, "`ikt_szam`='{$szamla_adatok['Danubius azonosító']}' AND `andoc_id`!='{$andoc_id}'");
                if( $letezo_ikt_szam ) {
                    Throw new SOAPFault ('Server', '"'.$szamla_adatok['Danubius azonosító'].'" Danubius azonosító már létezik a FELDOLGOZOTT számlák között!');
                }
            }
            
            // Ha a számla pénzneme HUF, akkor a Deviza bruttót ne importáljuk
            if( $szamla_adatok['Pénznem'] == self::KCS_21_TXT ) {
                $szamla_adatok['Deviza bruttó összege'] = '';
            }
            
            // Ha van pénznem adatunk (bár elvileg mindig van, mert insert + update esetén is kötelező)
            $arfolyam = 0;
            if( $szamla_adatok['Pénznem'] ) {
                $szamla_adatok['Pénznem'] = mb_strtoupper($szamla_adatok['Pénznem'], self::CHAR_CODING);

                // Ha devizás és nincs megadva árfolyam, akkor hiba
                if( $szamla_adatok['Pénznem'] != self::KCS_21_TXT && !trim($szamla_adatok['Belső árfolyam']) ) {
                    Throw new SOAPFault ('Server', 'Devizás számla esetén az Árfolyam mező kitöltése kötelező!');
                }
                
                // Ha nem HUF a pénznem, akkor az összegek a deviza mezőbe kerüljenek fejben és tételeknél is
                if( $szamla_adatok['Pénznem'] != self::KCS_21_TXT ) {
                    $arfolyam = $szamla_adatok['Belső árfolyam'];
                    $szamla_adatok['Deviza bruttó összege'] = $szamla_adatok['Számla bruttó összege'];
                    $szamla_adatok['Számla bruttó összege'] = $szamla_adatok['Számla bruttó összege']*$szamla_adatok['Belső árfolyam'];
                }

                // Ha olyan értéket kapnánk, mint amilyen értékű pénznem nem is létezik, akkor létrehozzuk
                $penznem = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `info`='{$szamla_adatok['Pénznem']}'");
                if( !$penznem ) {
                    $ertek = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."'", 'id desc');
                    if( $ertek ) {
                        $ertek_id = $ertek['id'] + 1;
                    } else {
                        $ertek_id = 1;
                    }
                    $penznem['id'] = $ertek_id;
                    $sql = $adatbazis_kapcsolat->i('erteklista',    array('csoport',          'id',       'info',                     'dhszla_id'              ),
                                                                    array(ERTEKLISTA_PENZNEM, $ertek_id,  $szamla_adatok['Pénznem'],  $szamla_adatok['Pénznem'])
                                            );
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                }
            }
            
            // Nullás számla vagy sem
            $nullas = 0;
            if( $szamla_adatok['Számla bruttó összege'] == '0' ) {
                $nullas = 1;
            }

            $i = 0;
            $szamla_tetelek = array();
            foreach( $szamla -> data -> register -> properties -> container as $cont ) {
                if( $cont['name'] == 'IK1' ) {

                    foreach ($cont -> row as $row) {
                        $szamla_tetelek[$i]['rownumberonpage'] = $row['rownumberonpage'];
                        foreach ($row -> property as $prop) {
                            $kulcs = (string)$prop['name'][0];
                            $szamla_tetelek[$i][$kulcs] = (string)$prop[0];
                        }
                        $i++;
                    }
                    break;

                }
            }
            
            // Kifizetéskor ellenőrizzük a tételek számát. Ha eltérés van, akkor auto mail
            // 2022.12.07: ha nincs benne tétel egyáltalán, akkor nem kell figyelni az eltérést
            $tetelek_szama = count($szamla_tetelek);
            if( $tetelek_szama && $kifizetes_import && $szamla_db['tetel_db'] != $tetelek_szama ) {
                if( $_SERVER['REMOTE_ADDR'] != get_debug_ip() ) {
                    level_felkeszit_es_kuld($tabla, $szamla_db['recid'], '', 'szamla_kifizetes', 'szamla_kifizetes', '');
                }
                $sql = $adatbazis_kapcsolat->u($tabla, array('tetel_elter'), array(1), "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
            }

            // 2021.09.09 Kérésre a tételek már nem kötelezőek
//            if( !count($szamla_tetelek) && $insert_update == 'insert' ) {
//                Throw new SOAPFault ('Server', 'Hiányzó tételek!');
//            }
            
            // Kifizetésekkel sem foglalkozunk már, nem használunk ilyen gyermek táblát
            // $kifizetesek = $szamla -> kifizetes;

            // Csak ez a két fizetési mód létezik:
            $fizmod = '';
            if( mb_strtolower($szamla_adatok['Fizetés módja'], self::CHAR_CODING) == 'készpénz' ) {
                $fizmod = 'k';
            }
            if( mb_strtolower($szamla_adatok['Fizetés módja'], self::CHAR_CODING) == 'utalás' ) {
                $fizmod = 'u';
            }
            $eszamla = '0';
            if( mb_strtolower($szamla_adatok['E-Számla'], self::CHAR_CODING) == 'igen' ) {
                $eszamla = '1';
            }
            
            $igazolva = '0';
            if( mb_strtolower($szamla_adatok['Igazoló döntés'], self::CHAR_CODING) == 'igazolva' ) {
                $igazolva = '1';
            }
            
            $utalvanyozva = '0';
            if( mb_strtolower($szamla_adatok['Utalványozó döntés'], self::CHAR_CODING) == 'utalványozva' ) {
                $utalvanyozva = '1';
            }
            // Ha a számla sztornózott állapotban van, akkor ne lehessen utalványozni
            if( $szamla_adatok['Utalványozó döntés'] && $szamla_db['sztornozva_dt'] && $szamla_db['sztornozva_dt'] != '0000-00-00 00:00:00' ) {
                Throw new SOAPFault ('Server', 'A számla már sztornózva van, így nem lehet utalványozni!');
            }

            // Elutasítás leírása + Utalványozás leírása egyben
            $elutleir = trim($szamla_adatok['Teljesítés igazolás elutasítás oka'].' '.$szamla_adatok['Utalványozás elutasítás oka']);

            // Költségviselő felismerése, beállítása
            if( $szamla_adatok['Számla fizetője'] ) {
                $erteklista = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_KOLTSEGVISELO."' AND `cegkod`='{$szamla_adatok['Számla fizetője']}'");
                if( $erteklista === false ) { $db_error = mysqli_error($db_kapcs); }
                $kolt_vis = $erteklista['id'];
            }
            $szamla_adatok['Kifizetett összeg'] = str_replace(',', '.', $szamla_adatok['Kifizetett összeg']);

            // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól függően, hogy az Andok azonosító ismert-e.
            // Ehhez az első lépés, a mező/érték párokat beállítani.
            $mezok = $ertekek = array();
            if( $szamla_adatok['Számla érkezés dátuma']             ) { array_push($mezok, 'erk_dt'         ); array_push($ertekek, $szamla_adatok['Számla érkezés dátuma']);       }
            if( $szamla_adatok['Számla kelte']                      ) { array_push($mezok, 'kelte_dt'       ); array_push($ertekek, $szamla_adatok['Számla kelte']);                }
            if( $szamla_adatok['Számla sorszáma']                   ) { array_push($mezok, 'szla_szam'      ); array_push($ertekek, $szamla_adatok['Számla sorszáma']);             }
            if( $szamla_adatok['Danubius azonosító']                ) { array_push($mezok, 'ikt_szam'       ); array_push($ertekek, $szamla_adatok['Danubius azonosító']);          }
            if( $szamla_adatok['Rögzítő neve']                      ) { array_push($mezok, 'ikt_usr'        ); array_push($ertekek, $szamla_adatok['Rögzítő neve']);                }
            if( $szamla_adatok['Számla típusa']                     ) { array_push($mezok, 'tipus'          ); array_push($ertekek, $szamla_adatok['Számla típusa']);               }
            if( $szamla_adatok['Vonalkód']                          ) { array_push($mezok, 'vonalkod'       ); array_push($ertekek, $szamla_adatok['Vonalkód']);                    }
            if( $szamla_adatok['Teljesítés dátuma']                 ) { array_push($mezok, 'telj_dt'        ); array_push($ertekek, $szamla_adatok['Teljesítés dátuma']);           }
            if( $szamla_adatok['Fizetési határidő']                 ) { array_push($mezok, 'fizhat_dt'      ); array_push($ertekek, $szamla_adatok['Fizetési határidő']);           }
            if( $szamla_adatok['Szálloda - Klaszter']               ) { array_push($mezok, 'telephely_id'   ); array_push($ertekek, $szamla_adatok['Szálloda - Klaszter']);         }
            if( $szamla_adatok['Partner száma']                     ) { array_push($mezok, 'szallito_id'    ); array_push($ertekek, $szallito_id);                                  }
            if( $szamla_adatok['Számla tárgya']                     ) { array_push($mezok, 'targy'          ); array_push($ertekek, $szamla_adatok['Számla tárgya']);               }
            if( $szamla_adatok['Pénznem']                           ) { array_push($mezok, 'kcs_21'         ); array_push($ertekek, $penznem['id']);                                }
            if( $szamla_adatok['Átváltva - NINCS ILYEN MEZŐ']       ) { array_push($mezok, 'atvaltva_dt'    ); array_push($ertekek, $szamla_adatok['Átváltva - NINCS ILYEN..']);    }
            if( $szamla_adatok['Belső árfolyam']                    ) { array_push($mezok, 'arfolyam'       ); array_push($ertekek, $szamla_adatok['Belső árfolyam']);              }
            if( $szamla_adatok['Szállító árfolyama']                ) { array_push($mezok, 'arfolyam_szall' ); array_push($ertekek, $szamla_adatok['Szállító árfolyama']);          }
            if( $szamla_adatok['Dev net - NINCS ILYEN MEZŐ']        ) { array_push($mezok, 'dev_net'        ); array_push($ertekek, $szamla_adatok['Dev net - NINCS ILYEN ']);      }
            if( $szamla_adatok['Deviza bruttó összege']             ) { array_push($mezok, 'dev_br'         ); array_push($ertekek, $szamla_adatok['Deviza bruttó összege']);       }
            if( $szamla_adatok['Nettó - NINCS ILYEN MEZŐ']          ) { array_push($mezok, 'netto'          ); array_push($ertekek, $szamla_adatok['Nettó - NINCS ILYEN MEZŐ']);    }
            if( $szamla_adatok['Számla bruttó összege']             ) { array_push($mezok, 'brutto'         ); array_push($ertekek, $szamla_adatok['Számla bruttó összege']);       }
            if( isset($szamla_adatok['Kifizetett összeg'])          ) { array_push($mezok, 'kif_ossz'       ); array_push($ertekek, $szamla_adatok['Kifizetett összeg']);           }
            if( $szamla_adatok['Utolsó kifizetés dátuma']           ) { array_push($mezok, 'ut_kif_dt'      ); array_push($ertekek, $szamla_adatok['Utolsó kifizetés dátuma']);     }
            if( $szamla_adatok['Kifizetve - NINCS ILYEN MEZŐ']      ) { array_push($mezok, 'kifizetve'      ); array_push($ertekek, $szamla_adatok['Kifizetve - NINCS ILYE..']);    }
            if( $szamla_adatok['E-Számla']                          ) { array_push($mezok, 'eszamla'        ); array_push($ertekek, $eszamla);                                      }
            if( $szamla_adatok['Visszaadva - NINCS ILYEN MEZŐ']     ) { array_push($mezok, 'visszaadva'     ); array_push($ertekek, $szamla_adatok['Visszaadva - NINCS ILYEN']);    }
            if( $szamla_adatok['Visszaadva dátum - NINCS I...']     ) { array_push($mezok, 'visszaadva_dt'  ); array_push($ertekek, $szamla_adatok['Visszaadva dátum - NINCS']);    }
            if( $szamla_adatok['Igazoló döntés']                    ) { array_push($mezok, 'igazolva'       ); array_push($ertekek, $igazolva);                                     }
            if( $szamla_adatok['Utalványozó döntés']                ) { array_push($mezok, 'utalvanyozva'   ); array_push($ertekek, $utalvanyozva);                                 }
            if( $szamla_adatok['Megjegyzés']                        ) { array_push($mezok, 'megjegyz'       ); array_push($ertekek, $szamla_adatok['Megjegyzés']);                  }
            if( $szamla_adatok['Számla bruttó összege']             ) { array_push($mezok, 'nullas'         ); array_push($ertekek, $nullas);                                       }
            if( $szamla_adatok['Fődokumentum link']                 ) { array_push($mezok, 'dok_link'       ); array_push($ertekek, $szamla_adatok['Fődokumentum link']);           }
            if( $szamla_adatok['Beruházási projektkód']             ) { array_push($mezok, 'pr_szam'        ); array_push($ertekek, $szamla_adatok['Beruházási projektkód']);       }
            if( $szamla_adatok['Teljesítés igazolás elutasítás oka']) { array_push($mezok, 'elutleir'       ); array_push($ertekek, $szamla_adatok['Teljesítés igazolás elutasítás oka']); }
            if( $szamla_adatok['Kapcsolódó szerződés/megrendelés']  ) { array_push($mezok, 'sz_szam'        ); array_push($ertekek, $szamla_adatok['Kapcsolódó szerződés/megrendelés']);   }
            if( $kolt_vis                                           ) { array_push($mezok, 'kolt_vis'       ); array_push($ertekek, $kolt_vis);                                     }
            if( $fizmod                                             ) { array_push($mezok, 'fizmod'         ); array_push($ertekek, $fizmod);                                       }

            // Ha az Andoc számlák táblában volna a számla, akkor előbb töröljük
            if( $tabla == 'szamla' ) {

                $atmenetiben_van = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
                if( $atmenetiben_van === false ) { $db_error = mysqli_error($db_kapcs); }

                if( $atmenetiben_van ) {

                    $sql = $adatbazis_kapcsolat->d($tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    // Viszont ekkor át kell kapcsolni insert üzemmódra
                    $insert_update = 'insert';

                    // Plusz ekkor mégis be kell írni a szálloda adatot (vagy átírni, ha benne volna)
                    $i = 0;
                    $volt_telephely = 0;
                    foreach ($mezok as $mezo) {
                        if( $mezo == 'telephely_id' ) {
                            $ertekek[$i] = $szalloda_klaszter_original;
                            $volt_telephely = 1;
                        }
                        $i++;
                    }
                    if( !$volt_telephely ) {
                        array_push($mezok,      'telephely_id'              );
                        array_push($ertekek,    $szalloda_klaszter_original );
                    }
                }

            }


// Throw new SOAPFault ('Server', $aparecid);
            $aparecid = $szamla_db['recid'];
// Throw new SOAPFault ('Server', $aparecid);
            // Ha nincs $aparecid, akkor ez azt jelenti, hogy a számla nincs benne az éles táblában.
            // Ekkor két eset lehet: benne van az adott modul átmeneti táblájában (ekkor update állapotban vagyunk),
            // vagy még az átmenetiben nincs, ekkor Insert módban vagyunk
            if( !$aparecid ) {
// Throw new SOAPFault ('Server', $insert_update);
                // Adott modul átmeneti táblájában van a számla, ezt kell updatelni
                if( $insert_update == 'update' ) {
                    
                    $return_uzi = 'beszúrva';
                    $szamla_xml = "*** SZÁMLA UPDATE (átmeneti) ***\n".$szamla_xml;
                    // Update esetén erk_xml-t meg kell toldani
                    $szamla_db['erk_xml'] = $szamla_xml.htmlspecialchars_decode($szamla_db['erk_xml']);

                    array_push($mezok,   'erk_xml',             'update_usr'    );
                    array_push($ertekek, $szamla_db['erk_xml'], self::IU_USER   );
                    
                    $sql = $adatbazis_kapcsolat->u($tabla.'_atmeneti', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    
                // Andoc nagy átmeneti táblájába szúrjuk be
                } else {
                    
                    $return_uzi = 'módosítva';
                    $szamla_xml = "*** ÚJ SZÁMLA ***\n".$szamla_xml;

                    // Insertnél kell az andoc_id is
                    if( $andoc_id ) {
                        array_push($mezok,     'erk_xml',   'andoc_id', 'insert_usr',  'update_usr',   'insert_dt'  );
                        array_push($ertekek,   $szamla_xml, $andoc_id,  self::IU_USER,  self::IU_USER, 'NOW()'      );
                        $aparecid = $adatbazis_kapcsolat->i($tabla.'_atmeneti', $mezok, $ertekek);
                        if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }
                    }
// Throw new SOAPFault ('Server', $adatbazis_kapcsolat -> gp() );
                }
                
            // Update, éles táblát updatelünk
            } else {

                // Ha a számla még nincs igazolva, akkor addig nem updatelhető
                // Projektszámláknál ezt összetettebb ellenőrizni:
                if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {

                    // Ha a számla előigazoláson átesett már (van előigazolás dátuma) és a döntés elutasítás, akkor rendben vagyunk, befogadjuk a számlát, hadd tudjon ránézni az előigazoló
                    $mehet = 0;
                    if( $szamla_db['elo_igazolva_dt']!='0000-00-00 00:00:00' && !$szamla_db['elo_igazolva'] ) {
                        $mehet = 1;
                    }
                    // Ha a számla előigazoláson átesett már (van előigazolás dátuma) de a döntés igazolva
                    if( $szamla_db['elo_igazolva_dt']!='0000-00-00 00:00:00' && $szamla_db['elo_igazolva'] ) {
                        // akkor viszont azt is vizsgálni kell, hogy a számla utógazolva dátuma ki van-e töltve. Ha igen, akkor mehet.
                        if( $szamla_db['igazolva_dt']!='0000-00-00 00:00:00' ) {
                            $mehet = 1;
                        }
                    }
                    if( !$mehet ) {
                        Throw new SOAPFault ('Server', 'A '.$szamla_tipus.' nincs igazolva, ezért nem lehet küldeni ontEAM rendszernek!');
                    }

                // Nem projektszámláknál elég az igazolva_dt dátumot figyelni
                } else {

                    if( $szamla_db['igazolva_dt']=='0000-00-00 00:00:00' ) {
                        $mondat_eleje = 'A '.$szamla_tipus;
                        if( self::SZAMLA_TIPUS_PROJEKT == self::SZAMLA_TIPUS_ENERGIA ) {
                            $mondat_eleje = 'Az '.$szamla_tipus;
                        }
                        if( self::SZAMLA_TIPUS_PROJEKT == self::SZAMLA_TIPUS_ANYAG ) {
                            $mondat_eleje = 'A '.$szamla_tipus.' számla';
                        }
                        Throw new SOAPFault ('Server', $mondat_eleje.' nincs igazolva, ezért nem lehet küldeni ontEAM rendszernek!');
                    }
                }


                $return_uzi = 'módosítva';
                if( $kifizetes_import ) {
                    $szamla_xml = "*** KIFIZETÉS ***\n".$szamla_xml;
                } else if( $szamla_db['sztornozva_dt'] != '0000-00-00 00:00:00' ) {
                    $szamla_xml = "*** ÚJRA FELDOLGOZOTT SZÁMLA ***\n".$szamla_xml;
                } else if( $szamla_adatok['Utalványozó döntés'] ) {
                    $szamla_xml = "*** UTALVÁNYOZOTT SZÁMLA ***\n".$szamla_xml;
                } else {
                    $szamla_xml = "*** SZÁMLA UPDATE (éles) ***\n".$szamla_xml;
                }

                // Update esetén erk_xml-t meg kell toldani
                $szamla_db['erk_xml'] = $szamla_xml.htmlspecialchars_decode($szamla_db['erk_xml']);
    
                array_push($mezok,   'elutleir',  'erk_xml',             'update_usr'    );
                array_push($ertekek, $elutleir,   $szamla_db['erk_xml'], self::IU_USER   );
                if( $utalvanyozva == '1' && $szamla_db['utalvanyozva_dt'] == '0000-00-00 00:00:00' ) {
                    array_push($mezok,     'utalvanyozva_dt');
                    array_push($ertekek,   'NOW()'          );
                }

                // Ha egy utalványozást épp elutasítanának, vagy technikai sztornózásról van szó, akkor az utalvanyozva_dt-t és sztornozva_dt-t állítsuk be, mert onnan fogjuk tudni, hogy el van utasítva, hogy 0 az értéke, de a dátuma ki van töltve
                if( 
                    (mb_strtolower($szamla_adatok['Utalványozó döntés'], self::CHAR_CODING) == 'elutasítva' && $szamla_db['utalvanyozva_dt'] == '0000-00-00 00:00:00') ||
                    $tech_sztorno
                 ) {

                    array_push($mezok,     'utalvanyozva_dt',   'sztornozva_dt', 'sztornozva_usr');
                    array_push($ertekek,   'NOW()',             'NOW()',         'Andoc'         );

                    // Elutasításkor minden számla fejben töröljük a kapcsolt szerződés számot
                    $sql = $adatbazis_kapcsolat->u($tabla, array('sz_szam'), array(''), "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    // a tömbben is
                    $mezo_i = 0;
                    foreach ($mezok as $mezo) {
                        if( $mezo == 'sz_szam' ) {
                            $ertekek[$mezo_i] = '';
                        }
                        $mezo_i++;
                    }

                    // Projektszámláknál a pr_szam és let_fel mezőket is üríteni kell
                    if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                        $szamla = $adatbazis_kapcsolat->s2('pr_szam', $tabla, "`andoc_id`='{$andoc_id}'");
                        if( $szamla === false ) { $db_error = mysqli_error($db_kapcs); }
                        $sql = $adatbazis_kapcsolat->u($tabla, array('pr_szam', 'let_fel'), array('', ''), "`andoc_id`='{$andoc_id}'");
                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                        // a tömbben is
                        $mezo_i = 0;
                        foreach ($mezok as $mezo) {
                            if( $mezo == 'pr_szam' ) {
                                $ertekek[$mezo_i] = '';
                            }
                            $mezo_i++;
                        }
                    }

                    // Fenntartás és projekt számlák esetén a szerződés tételeknél nullázni kell a teljesítést és nettó értékeket
                    if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI || $szamla_tipus == self::SZAMLA_TIPUS_MEGALL || $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                        
                        // Projekt és fenntartási szerződés tételeknél más a mezők neve
                        $netto_mezo     = 'ertek';
                        $dev_netto_mezo = 'dev_ertek';
                        $telj_mezo      = 'teljesites';
                        if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI || $szamla_tipus == self::SZAMLA_TIPUS_MEGALL ) {
                            $netto_mezo     = 'netto_ert';
                            $dev_netto_mezo = 'dev_net';
                            $telj_mezo      = 'telj_dt';
                        }
                        
                        $szerzodes_tabla = str_replace('szamla', 'szerzodes', $tabla);
                        // Megállapodás szerződés DB tábla neve nem `megallapodas_szerzodes`, hanem csak simán `megallapodas`, ezért kiszűrjük a "_szamla" stringet
                        if( $szamla_tipus == self::SZAMLA_TIPUS_MEGALL ) {
                            $szerzodes_tabla = str_replace('_szamla', '', $tabla);
                        }
                        $tetelek = $adatbazis_kapcsolat->s($szerzodes_tabla.'_tetel_recid', $tabla.'_tetel', "`{$tabla}_recid`='{$szamla_db['recid']}'");
                        if( $tetelek === false ) { $db_error = mysqli_error($db_kapcs); }
                        
                        foreach ( $tetelek as $tetel ) {
                            $sql = $adatbazis_kapcsolat->u($szerzodes_tabla.'_tetel',   array($netto_mezo, $dev_netto_mezo, $telj_mezo  ),
                                                                                        array('0',         '0.00',          '0000-00-00'),
                                                                                        "`recid`='{$tetel[$szerzodes_tabla.'_tetel_recid']}'");
                            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                        }

                        //... illetve a kapcsolást szintén töröljük a számla tételeknél
                        $sql = $adatbazis_kapcsolat->u($tabla.'_tetel', array($szerzodes_tabla.'_tetel_recid'),
                                                                        array('0'                            ),
                                                        "`{$tabla}_recid`='{$szamla_db['recid']}'");
                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                        
                        // Projektek átszámítása
                        if( $szamla['pr_szam'] ) {
                            if( projektkezeles_szamlazott_osszeg ( $szamla['pr_szam'] ) === false ) {
                                $db_error = 'Projektek átszámítása nem sikerült';
                            }
                        }
                    }

                    // Anyagszámla esetén a tételeket be kell járni, és a mennyiséget kivonni a
                    // hozzájuk tartozó anyagrendelések számlázott mennyiségéből
                    if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG ) {
                        $szamla = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                        if( $szamla === false ) { $db_error = mysqli_error($db_kapcs); }
                        $tetelek = $adatbazis_kapcsolat->s('rnd_recid rndmen', 'anyag_szamla_tetel', "`anyag_szamla_recid`='{$szamla['recid']}'");
                        if( $tetelek === false ) { $db_error = mysqli_error($db_kapcs); }
                        foreach ($tetelek as $tetel ) {
                            if( $tetel['rnd_recid'] ) {
                                $rnd = $adatbazis_kapcsolat->s2('szlamen', 'anyagigenyles', "`recid`='{$tetel['rnd_recid']}'");
                                if( $rnd === false ) { $db_error = mysqli_error($db_kapcs); }
                                $sql = $adatbazis_kapcsolat -> u('anyagigenyles', array('szlamen'), array($rnd['szlamen']-$tetel['rndmen']), "`recid`='{$tetel['rnd_recid']}'");
                                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                            }
                        }

                        // Fenntartási számlák esetén a munkalap kapcsolatot is bontani kell
                        if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                            $sql = $adatbazis_kapcsolat -> u('munkalap', array('szla_szam'), array(''), "`szla_szam`='{$szamla_db['szla_szam']}'");
                            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                        }
                    }
                    
                    // Megjegyzésbe is kerüljön be az indoklás
                    if( $tech_sztorno ) {
                        if( addMegj($tabla, $szamla_db['recid'], 'A számla technikailag sztornózásra került.', 'Andoc') === false ) {
                            $db_error = 'Megjegyzésbe írás nem sikerült';
                        }
                    } else {
                        if( addMegj($tabla, $szamla_db['recid'], 'Utalványozás elutasítva.<br>Indoklás: '.nl2br2($szamla_adatok['Utalványozás elutasítás oka']), 'Andoc') === false ) {
                            $db_error = 'Megjegyzésbe írás nem sikerült';
                        }
                    }

                }

                $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                // *** ÚJRA FELDOLGOZOTT SZÁMLA *** esete
                // Ha NEM kifizetésről van szó és nem is Technikailag sztornózott számláról, és a számla az ontEAM oldalon sztornó állapotban van, akkor töröljük a történetből a feldolgozás, igazolás (+előigazolás), utalványozás, sztornó sorok adatait
                if( !$kifizetes_import && !$tech_sztorno && $szamla_db['sztornozva_dt'] != '0000-00-00 00:00:00' ) {

                    $mezok   = array('feldolgozva_dt',      'feldolgozva_usr', 'igazolva', 'igazolva_dt',         'igazolva_usr', 'utalvanyozva', 'utalvanyozva_dt',     'utalvanyozva_usr', 'sztornozva_dt',       'sztornozva_usr', 'sztornozva_leir', 'elutleir');
                    $ertekek = array('0000-00-00 00:00:00', '',                '0',        '0000-00-00 00:00:00', '',             '0',            '0000-00-00 00:00:00', '',                 '0000-00-00 00:00:00', '',               '',                ''        );

                    // Projektszámláknál az előigazolást is üríteni kell, hisz ha előigazolása pl. elutasításban maradna, akkor ne, tudnádnak vele mit kezdeni (nem jellennének meg gombok lent)
                    // A lényeg, hogy itt még ezt is üríteni kell a fentieken felül
                    if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                        array_push($mezok,   'elo_igazolva',    'elo_igazolva_dt',      'elo_igazolva_usr'  );
                        array_push($ertekek, '0',               '0000-00-00 00:00:00',  ''                  );
                    }

                    $sql = $adatbazis_kapcsolat->u($tabla,  $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    
                    // Ürítsük a könyvelés tételeket is, ezek majd újra kiszámolódnak igazoláskor
                    $sql = $adatbazis_kapcsolat->d('energia_szamla_konyveles', "`energia_szamla_recid`='{$szamla_db['recid']}'");
                    if( $sql === false ) { $db_error = true; }
                    
                    // Illetve ilyenkor minden tételénél a változott értéket 1-re állítjuk, hogy igazolásra küldéskor a popupban benne legyenek a tételek
                    $sql = $adatbazis_kapcsolat->u($tabla.'_tetel',
                                                                    array('valtozott'),
                                                                    array('1'        ),
                                                                "`{$tabla}_recid`='{$aparecid}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                    // Fenntartási számlák esetén Email értesítés küldése
                    $fenntartas_szamlak = array(self::SZAMLA_TIPUS_ANYAG, self::SZAMLA_TIPUS_ENERGIA, self::SZAMLA_TIPUS_FENNTARTASI);
                    if( in_array($szamla_tipus, $fenntartas_szamlak) ) {
                        $szamla = $adatbazis_kapcsolat->s2('szla_szam szallito_id', $tabla, "`andoc_id`='{$andoc_id}'");
                        $szamla_tipus = '';
                        switch( $tabla ) {
                            case 'energia_szamla'       : $szamla_tipus = 'Energia számla';     break;
                            case 'anyag_szamla'         : $szamla_tipus = 'Anyag számla';       break;
                            case 'fenntartasi_szamla'   : $szamla_tipus = 'Fenntartási számla'; break;
                        }
            
                        $szallito = getErtek('nev', 'szallito', 'id', $szamla['szallito_id']);
            
                        $mit    = array('{SZAMLA_TIPUS}', '{SZLA_SZAM}',        '{SZALLITO}');
                        $mivel  = array($szamla_tipus,    $szamla['szla_szam'], $szallito   );
                        $uzi    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/fenntartasszamla_asszisztens.html') );

                        $kieg = get_mail_teszt_felirat();
                        $targy_kieg = $kieg[0];
                        $uzi_kieg   = $kieg[1];

                        // A címzettnél vizsgáljuk az értesítés pipa meglétét is
                        $cimzettek = $adatbazis_kapcsolat->s('nev teljes_nev usermail aktiv virtual szemelyzet_id', 'login', "substr(`flags_ert`, ".(ERT_MUSZUGY_VISSZAADAS+1).", 1)=1 AND `aktiv`=1 AND `virtual`=0");
                        foreach ($cimzettek as $akt_cimzett) {
                            
                            // Ha több címzett is van (tehát első kapja, többi másolatot), akkor az első legyen csak megszólítva
                            $megszolitas = $akt_cimzett['teljes_nev'];
                            if( strpos($akt_cimzett['teljes_nev'], '|') !== false ) {
                                $megszolitas = explode('|', $akt_cimzett['teljes_nev']);
                                $megszolitas = $megszolitas[0];
                            }

                            // Ha több mailcím is szerepel vesszővel elválasztva, akkor azt is le kell kezelni:
                            $mailcimek = explode(',', $akt_cimzett['usermail']);
                            foreach( $mailcimek as $mailcim ) {
                                
                                $mit            = array('{NEV}'     );
                                $mivel          = array($megszolitas);
                                $megszolitas    = str_replace( $mit, $mivel, fajlbol_olvas('../sablon/admin/mail/megszolitas.html'));

                                $targy   =  get_nev().' fenntartási számla visszaküldve ügyintézőnek';
                                $uzi     =  $uzi.'
                                            <br>'.fajlbol_olvas('../sablon/admin/mail/alairas.html');

                                // Tényleges küldés
                                $level = level_kuldes(get_nev(), get_mail(), $akt_cimzett['teljes_nev'], $mailcim, $targy_kieg.$targy, $uzi_kieg.$megszolitas.$uzi);

                            }
                        }
                    }
                }

                // Ha kifizetésről van szó, akkor még kezeljük le a Kifizetve pipát az updatelt adatok ismeretében
                if( $kifizetes_import ) {

                    $szamla = $adatbazis_kapcsolat->s2('kcs_21, brutto, dev_br, kif_ossz, arfolyam', $tabla, "`andoc_id`='{$andoc_id}'");
                    if( $szamla === false ) { $db_error = mysqli_error($db_kapcs); }
                    
                    if( $szamla['kcs_21'] == KCS_21_HUF ) {
                        $br_mezo  = 'brutto';
                    } else {
                        $br_mezo  = 'dev_br';
                    }

                    if( number_format($szamla['kif_ossz'], 2, '.', '') >= number_format($szamla[$br_mezo], 2, '.', '') ) {
                        $sql = $adatbazis_kapcsolat->u($tabla, array('kifizetve'), array(1), "`andoc_id`='{$andoc_id}'");
                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    } else {
                        $sql = $adatbazis_kapcsolat->u($tabla, array('kifizetve'), array(0), "`andoc_id`='{$andoc_id}'");
                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
                    }

                    // Illetve ebben az esetben letároljuk a tételeket is, amik az XML-ben voltak, hogy fel lehessen őket venni a rendes tételek közé
                    // De csak akkor, ha tételszámban van eltérés... egyébként feleslegesen ne gyűjtsük őket
                    if( $szamla_db['tetel_db'] != count($szamla_tetelek) ) {
                        
                        $sql = $adatbazis_kapcsolat->d($tabla.'_tetel_kifizetes', "`{$tabla}_recid`='{$aparecid}'");
                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                        $kotelezo_tetel_mezok = array(
                                                    'Nettó sorösszeg',
                                                    'Pénznem (sor)',
                                                    'ÁFA kód',
                                                    'Sor megnevezése',
                                                    'Főkönyvi számlaszám',
                                                    'Költséghely',
                                                    'ÁFA %',
                                                    );
                        foreach ( $szamla_tetelek as $tetel ) {

                            // Tételeknél minden adat kötelező
                            foreach ( $kotelezo_tetel_mezok as $kot_mezo ) {
                                if( !$tetel[$kot_mezo] && $tetel[$kot_mezo] != '0' ) {
                                    Throw new SOAPFault ('Server', a_az($kot_mezo, '-nagybetűs-').' '.$kot_mezo.' mező értéke nem lehet üres!');
                                }
                            }

                            // ÁFA kód beazonosítása. Ha nincs, akkor hibát dobunk.
                            $afakod = $adatbazis_kapcsolat->s2('recid', 'afakod', "`kod`='{$tetel['ÁFA kód']}'");
                            if( $afakod === false ) { $db_error = mysqli_error($db_kapcs); }
                            if( !$afakod ) {
                                Throw new SOAPFault ('Server', 'Nem ismert Áfakód ('.$tetel['ÁFA kód'].'), így nem sikerült feldolgozni a kifizetést.');
                            } else {
                                $tetel['afakod_recid'] = $afakod['recid'];
                            }

                            // Jogcím kiszámítása a főkönyvi számlaszám és költség hely alapján
                            $jogcim = $adatbazis_kapcsolat->s2('recid jogcim', 'jogcim', "`szla_szam`='{$tetel['Főkönyvi számlaszám']}' AND `ktghely_id`='{$tetel['Költséghely']}'");
                            if( $jogcim === false ) { $db_error = mysqli_error($db_kapcs); }
                            if( !$jogcim ) {
                                Throw new SOAPFault ('Server', 'Nem ismert Főkönyvi számlaszám ('.$tetel['Főkönyvi számlaszám'].') és Költséghely ('.$tetel['Költséghely'].') páros, így nem sikerült feldolgozni a kifizetést.');
                            } else {
                                $tetel['jogcim_recid']  = $jogcim['recid'];
                                $tetel['jogcim']        = $jogcim['jogcim'];
                            }

                            // HUF/Deviza esetén más mezőket használunk DB-ben, illetve Deviza esetén nincs bruttó eltárolva
                            $netto_ert  = $tetel['Nettó sorösszeg'];
                            $brutto_ert = $tetel['Bruttó sorösszeg'];
                            $dev_net    = '0';
                            $dev_br     = '0';

                            if( $szamla_adatok['Pénznem (sor)'] != self::KCS_21_TXT ) {
                                $dev_net    = $tetel['Nettó sorösszeg'];
                                $dev_br     = $tetel['Bruttó sorösszeg'];
                                $netto_ert  = $tetel['Nettó sorösszeg'] *$szamla['arfolyam'];
                                $brutto_ert = $tetel['Bruttó sorösszeg']*$szamla['arfolyam'];
                            }

                            // Tételek beszúrása
                            $mezok   = array(
                                            $apa_recid_oszlop,
                                            'rownum',
                                            'update_usr',
                                            'netto_ert',
                                            'brutto_ert',
                                            'dev_net',
                                            'dev_br',
                                            'jogcim',
                                            'jogcim_recid',
                                            'uzemresz',
                                            'megn',
                                            'ktgnem_id',
                                            'ktghely_id',
                                            'afa',
                                            'afa_kod',
                                            'afakod_recid',
                                            'afa_nev',
                                            'flex',
                                            'insert_usr',
                                            'insert_dt',
                                        );
                            $ertekek = array(
                                            $aparecid,
                                            $tetel['rownumberonpage'],
                                            self::IU_USER,
                                            $netto_ert,
                                            $brutto_ert,
                                            $dev_net,
                                            $dev_br,
                                            $tetel['jogcim'],
                                            $tetel['jogcim_recid'],
                                            $tetel['Üzemrész'],
                                            $tetel['Főkönyvi számlaszám megnevezése'],
                                            $tetel['Főkönyvi számlaszám'],
                                            $tetel['Költséghely'],
                                            $tetel['ÁFA %'],
                                            $tetel['ÁFA kód'],
                                            $tetel['afakod_recid'],
                                            $tetel['ÁFA neve'],
                                            $tetel['Flexmező'],
                                            self::IU_USER,
                                            'NOW()',
                                        );

                            // További mezők felvétele, számla típusától függően
                            if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG ) {
                            //
                            }
                            if( $szamla_tipus == self::SZAMLA_TIPUS_ENERGIA ) {
                            //
                            }
                            if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                                array_push($mezok,      'afa_ossz'              );
                                array_push($ertekek,    $tetel['ÁFA Összege']   );
                            }
                            if( $szamla_tipus == self::SZAMLA_TIPUS_MEGALL ) {
                                array_push($mezok,      'afa_ossz'              );
                                array_push($ertekek,    $tetel['ÁFA Összege']   );
                            }
                            if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT) {
                                if( !$tetel['Sor megnevezése'] ) {
                                    Throw new SOAPFault ('Server', 'A Sor megnevezése mező értéke nem lehet üres!');
                                }
                                array_push($mezok,      /*'afa_ossz',             */'targy'                     );
                                array_push($ertekek,    /*$tetel['ÁFA Összege'],  */$tetel['Sor megnevezése']   );
                            }

                            $sql = $adatbazis_kapcsolat->i($tabla.'_tetel_kifizetes', $mezok, $ertekek);
                            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                        }
                    }
                }
                
            }
            
            

            // Tételek bejárása
            // 2021.06.22. 8:30-i levél alapján: 
            // Update fogadásakor, ha a fogadott rekord már benne van éles táblába (azaz nem az átmenetibe van), tételeket nem fogadjuk be, csak fej adatokat írjuk felül
            // Magyarul csak akkor dolgozzuk fel, ha insert, vagy ha az átmeneti táblában van 
            
            // 2021.09.09 kérésre a számla tételeket egyáltalán nem dolgozzuk fel
//            if( $insert_update == 'insert' || $atmenetiben_van ) {
//                
//                foreach ( $szamla_tetelek as $tetel ) {
//
//                    // Tételek kötelező mezők ellenőrzése
//                    if( !trim($tetel['rownumberonpage']) ) {
//                        Throw new SOAPFault ('Server', 'Hiányzó rownumberonpage a tételeknél!');
//                    }
//                    if( $insert_update == 'insert' && !trim($tetel['Nettó sorösszeg']) ) {
//                        Throw new SOAPFault ('Server', 'Hiányzó nettó sorösszeg a tételeknél!');
//                    }
//
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI) {
//                        if( $insert_update == 'insert' && !trim($tetel['Főkönyvi számlaszám']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
//                        }
//                        if( $insert_update == 'insert' && !trim($tetel['Költséghely']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
//                        }
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_MEGALL) {
//                        if( $insert_update == 'insert' && !trim($tetel['Főkönyvi számlaszám']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
//                        }
//                        if( $insert_update == 'insert' && !trim($tetel['Költséghely']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
//                        }
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT) {
//                        if( $insert_update == 'insert' && !trim($tetel['Főkönyvi számlaszám']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
//                        }
//                        if( $insert_update == 'insert' && !trim($tetel['Költséghely']) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
//                        }
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG) {
//                        if( !trim($tetel -> ktgnem_id) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
//                        }
//                        if( !trim($tetel -> ktghely_id) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
//                        }
//                        if( !trim($tetel -> szlamen) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó számlázott mennyiség a tételeknél!');
//                        }
//                        if( !trim($tetel -> szlamee) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó mennyiségi egység a tételeknél!');
//                        }
//                        if( !trim($tetel -> szlaear) ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó egységár a tételeknél!');
//                        }
//                        if( !trim($tetel -> vszlaear) && $szamla -> kcs_21 != self::KCS_21_HUF ) {
//                            Throw new SOAPFault ('Server', 'Hiányzó deviza egységár a tételeknél!');
//                        }
//                    }
//
//                    $afa_tetel = $tetel['ÁFA %'].' %';
//                    $afa_kod = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_AFA_KOD."' AND `info`='{$afa_tetel}'");
//                    if( !$afa_kod ) {
//                        $ertek = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_AFA_KOD."'", 'id desc');
//                        if( $ertek ) {
//                            $ertek_id = $ertek['id'] + 1;
//                        } else {
//                            $ertek_id = 1;
//                        }
//                        $afa_kod['id'] = $ertek_id;
//                        $sql = $adatbazis_kapcsolat->i('erteklista',    array('csoport',          'id',       'info'    ),
//                                                                        array(ERTEKLISTA_AFA_KOD, $ertek_id,  $afa_tetel)
//                                                        );
//                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//                    }
//
//                    // HUF/Deviza esetén más mezőket használunk DB-ben, illetve Deviza esetén nincs bruttó eltárolva
//                    
//                    $netto_ert  = $tetel['Nettó sorösszeg'];
//                    $brutto_ert = $tetel['Bruttó sorösszeg'];
//                    $dev_net    = '0';
//                    $dev_br     = '0';
//                    if( $szamla_adatok['Pénznem'] != self::KCS_21_TXT ) {
//                        $dev_net    = $tetel['Nettó sorösszeg'];
//                        $dev_br     = $tetel['Bruttó sorösszeg'];
//                        $netto_ert  = $tetel['Nettó sorösszeg'] *$arfolyam;
//                        $brutto_ert = $tetel['Bruttó sorösszeg']*$arfolyam;
//                    }
//
//                    // Tételek beszúrása
//                    $mezok   = array(
//                                    $apa_recid_oszlop,
//                                    'rownum',
//                                    'update_usr',
//                                    'netto_ert',
//                                    'brutto_ert',
//                                    'dev_net',
//                                    'dev_br',
//                                );
//                    $ertekek = array(
//                                    $aparecid,
//                                    $tetel['rownumberonpage'],
//                                    self::IU_USER,
//                                    $netto_ert,
//                                    $brutto_ert,
//                                    $dev_net,
//                                    $dev_br,
//                                );
//                    if( $tetel['Sor megnevezése']       ) { array_push($mezok, 'targy'      ); array_push($ertekek, $tetel['Sor megnevezése']   ); }
//                    if( $tetel['ÁFA Összege']           ) { array_push($mezok, 'afa_ossz'   ); array_push($ertekek, $tetel['ÁFA Összege']       ); }
//                    if( $tetel['Flexmező']              ) { array_push($mezok, 'flex'       ); array_push($ertekek, $tetel['Flexmező']          ); }
//
//                    // További mezők felvétele, számla típusától függően
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
//                        array_push($mezok,      'jogcim',           'uzemresz',            'megn',                                      'ktgnem_id',                    'ktghely_id',           'afa',              'afa_kod',          'afa_nev'         );
//                        array_push($ertekek,    $tetel['Jogcím'],   $tetel['Üzemrész'],    $tetel['Főkönyvi számlaszám megnevezése'],   $tetel['Főkönyvi számlaszám'],  $tetel['Költséghely'],  $tetel['ÁFA %'],    $tetel['ÁFA kód'],  $tetel['ÁFA neve']);
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_MEGALL ) {
//                        array_push($mezok,      'jogcim',           'uzemresz',            'megn',                                      'ktgnem_id',                    'ktghely_id',           'afa',              'afa_kod',          'afa_nev'         );
//                        array_push($ertekek,    $tetel['Jogcím'],   $tetel['Üzemrész'],    $tetel['Főkönyvi számlaszám megnevezése'],   $tetel['Főkönyvi számlaszám'],  $tetel['Költséghely'],  $tetel['ÁFA %'],    $tetel['ÁFA kód'],  $tetel['ÁFA neve']);
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT) {
//                        // Nem kell további mezőket felvenni
//                    }
//                    if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG) {
//                        //
//                    }
//
//                    // Tétel létezik-e már?
//                    $tetel_letezik = $adatbazis_kapcsolat->s2('recid', $tabla.'_tetel', "`{$tabla}_recid`='{$aparecid}' AND `rownum`='{$tetel['rownumberonpage']}'");
//                    $tetel_letezik = $tetel_letezik['recid'];
//                    if( $tetel_letezik === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                    // Insert
//                    if( !$tetel_letezik ) {
//
//                        $tabla_kieg = '';
//                        if( $insert_update == 'insert' ) {
//                            $tabla_kieg = '_atmeneti';
//                        }
//                        array_push($mezok,   'andocos', 'insert_usr',   'insert_dt' );
//                        array_push($ertekek, '1',       self::IU_USER,  'NOW()'     );
//                        $sql = $adatbazis_kapcsolat->i($tabla.'_tetel'.$tabla_kieg, $mezok, $ertekek);
//                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                    // Update
//                    } else {
//
//                        $sql = $adatbazis_kapcsolat->u($tabla.'_tetel', $mezok, $ertekek, "`rownum`='{$tetel['rownumberonpage']}'");
//                        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                    }
//                }
//            }

            // Partner insert/update ha az eredeti 'Partner száma' mezőnek volt értéke
            if( $szamla_adatok['Partner száma'] ) {
                $mezok   = array();
                $ertekek = array();
                
                if( $szamla_adatok['Partner státusza'] ) {
                    $aktiv = 0;
                    if( mb_strtolower($szamla_adatok['Partner státusza'], self::CHAR_CODING) == 'aktív' ) {
                        $aktiv = 1;
                    }
                    array_push($mezok,   'aktiv');
                    array_push($ertekek, $aktiv );
                }
                if( $szamla_adatok['Partner név'] ) {
                    array_push($mezok,   'nev');
                    array_push($ertekek, $szamla_adatok['Partner név'] );
                }
                if( $szamla_adatok['Partner cím'] ) {
                    array_push($mezok,   'utca');
                    array_push($ertekek, $szamla_adatok['Partner cím'] );
                }
                if( $szamla_adatok['Partner bankszámlaszám'] ) {
                    array_push($mezok,   'bank');
                    array_push($ertekek, $szamla_adatok['Partner bankszámlaszám'] );
                }
                if( $szamla_adatok['Partner adószám'] ) {
                    array_push($mezok,   'adoszam');
                    array_push($ertekek, $szamla_adatok['Partner adószám'] );
                }
                
                // Ha sikerült beazonosítani a parntert a kapott számmal, akkor update
                $partner = $adatbazis_kapcsolat->s2('id', 'szallito', "`id`='{$szallito_id}'");
                if( $partner ) {
                    $adatbazis_kapcsolat->u('szallito', $mezok, $ertekek, "`id`='{$szallito_id}'");
                // Egyébként...
                } else {
                    array_push($mezok,   'id',          'insert_dt' );
                    array_push($ertekek, $szallito_id,  'NOW()'     );
                    $adatbazis_kapcsolat->i('szallito', $mezok, $ertekek);
                }
            }

            // Kifizetésekkel újabban nem foglalkozunk
//            // Kifizetések bejárása
//            foreach ( $kifizetesek as $kifizetes ) {
//
//                $andoc_id = $kifizetes['andoc_id'];
//
//                // Kifizetések beszúrása
//                $mezok   = array(
//                                $apa_recid_oszlop,
//                                'szlakifid',
//                                'szla_szam',
//                                'szallito_id',
//                                'bank',
//                                'kifizetes_dt',
//                                'br_ertek',
//                                'rogzitve_dt',
//                                'rogzitette',
//                                'dev_br',
//                                'kcs_21',
//                                'loginnev',
//                                'update_usr',
//                            );
//                $ertekek = array(
//                                $aparecid,
//                                $kifizetes -> szlakifid,
//                                $kifizetes -> szla_szam,
//                                $szallito_id,
//                                $kifizetes -> bank,
//                                $kifizetes -> kifizetes_dt,
//                                $kifizetes -> br_ertek,
//                                $kifizetes -> rogzitve_dt,
//                                $kifizetes -> rogzitette,
//                                $kifizetes -> dev_br,
//                                $kifizetes -> kcs_21,
//                                self::IU_USER,
//                                self::IU_USER,
//                            );
//
//                $kifizetes_letezik = $adatbazis_kapcsolat->s2('recid', $tabla.'_kifizetesek', "`andoc_id`='{$andoc_id}'");
//                $kifizetes_letezik = $kifizetes_letezik['recid'];
//                if( $kifizetes_letezik === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                // Insert
//                if( !$kifizetes_letezik ) {
//
//                    // Insertnél kell az andoc_id is
//                    array_push($mezok,      'andoc_id', 'insert_usr',   'insert_dt' );
//                    array_push($ertekek,    $andoc_id,  self::IU_USER,  'NOW()'     );
//                    $sql = $adatbazis_kapcsolat->i($tabla.'_kifizetesek', $mezok, $ertekek);
//                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                // Update
//                } else {
//
//                    $sql = $adatbazis_kapcsolat->u($tabla.'_kifizetesek', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
//                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
//
//                }
//
//            }
//        }

        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            return 'A számla, és a tételei sikeresen '.$return_uzi.'! ID: '.$szamla_andoc_id;
        } else {
            mysqli_rollback($db_kapcs);
            Throw new SOAPFault ('Server', 'A számla feldolgozása közben ismeretlen hiba történt: ID: '.$szamla_andoc_id.' Hibaüzenet:'.$db_error);
        }
        
    }
    
    // Szerződés importálása
    public function SzerzodesAdatokImport( $szerzodes_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $szerzodes = simplexml_load_string($szerzodes_xml, 'SimpleXMLElement');
        $szerzodes_xml = date('Y-m-d H:i:s').$szerzodes_xml."\n\n\n";
        $szerzodes_xml = str_replace("><", ">\n<", $szerzodes_xml);

        // Tranzakciót indítunk
        $db_error = false;
        mysqli_autocommit($db_kapcs, false);

        // A szerződés adatai a "data -> register -> properties -> container"-ben lesz
        // mégpedig abban, amelyiknek "NIK1" vagy "NIK2" a neve
        $szerzodes_adatok = array();
        foreach( $szerzodes -> data -> register -> properties -> container as $cont ) {
            if( $cont['name'] == 'NIK1' ) {

                // Ezen belül van egy csomó "row -> property" értéke is
                // Ezekből csinálunk egy tömböt, hogy ne kelljen állandóan foreach-el bejárni
                // hanem tudunk adott jellemzőre hivatkozni
                foreach ($cont -> row -> property as $prop) {
                    $kulcs = (string)$prop['name'][0];
                    $szerzodes_adatok[$kulcs] = (string)$prop[0];
                }
            }
            if( $cont['name'] == 'NIK2' ) {

                // Ezen belül van egy csomó "row -> property" értéke is
                // Ezekből csinálunk egy tömböt, hogy ne kelljen állandóan foreach-el bejárni
                // hanem tudunk adott jellemzőre hivatkozni
                foreach ($cont -> row -> property as $prop) {
                    $kulcs = (string)$prop['name'][0];
                    $szerzodes_adatok[$kulcs] = (string)$prop[0];
                }

            }
            if( $cont['name'] == 'NIK5' ) {

                // Ezen belül van egy csomó "row -> property" értéke is
                // Ezekből csinálunk egy tömböt, hogy ne kelljen állandóan foreach-el bejárni
                // hanem tudunk adott jellemzőre hivatkozni
                foreach ($cont -> row -> property as $prop) {
                    $kulcs = (string)$prop['name'][0];
                    $szerzodes_adatok[$kulcs] = (string)$prop[0];
                }

            }
        }
        
        // Kötelező mezők ellenőrzése ami Insert, update és delete utasításhoz is kellenek
        if( !trim($szerzodes -> data -> register['registernumber']) ) {
            Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
        }

        if( trim($szerzodes -> data -> register -> file['name']) ) {
            Throw new SOAPFault ('Server', 'A szerződés nem befogadható, mert tartalmazza a kapcsolt file-t!');
        }
        
        $andoc_id = $szerzodes -> data -> register['registernumber'];
        
        if( !$szerzodes_adatok['Szerződés iránya'] ) {
            // Ha nincs megadva a szerződés iránya, akkor fenntartási szerződésnek vesszük
            // Throw new SOAPFault ('Server', 'Kérem adja meg a szerződés irányát!');
            $szerzodes_adatok['Szerződés iránya'] = SZERZ_TIPUS_FENNTARTASI;
        }
        switch( strtolower( $szerzodes_adatok['Szerződés iránya'] ) ) {
            case self::SZERZ_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szerzodes';  break;
            case self::SZERZ_TIPUS_PROJEKT         : $tabla = 'projektszerzodes';       break;
            default                                : $tabla = '!hiba!';                 break;
        }

        if( $tabla == '!hiba!' ) {
            Throw new SOAPFault ('Server', 'Nem azonosítható be a szerződés iránya!');
        }
        
        $apa_recid_oszlop = $tabla.'_recid';
        
        // Azt, hogy ez insert vagy update lesz, azt már itt el kell dönteni, hisz update esetén nem kötelezőek a mezők!
        $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
        $aparecid = $aparecid['recid'];
        if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }
        if( $aparecid ) {
            $insert_update = 'update';
        } else {
            $insert_update = 'insert';
        }

        // Az alábbiakat csak insert esetén kell vizsgálni
        if( $insert_update == 'insert' ) {
            
            if( !valos_datum_e($szerzodes_adatok['Iktatás dátuma']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó iktatás dátum!');
            }
            if( !trim($szerzodes_adatok['Rögzítő neve']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó rögzítő neve!');
            }
            if( !trim($szerzodes_adatok['Szerződés iktatószáma']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés iktatószám!');
            }
            if( !trim($szerzodes_adatok['Partner száma']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó partnerszám!');
            }
            // Ha a szerződés iránya projekt, akkor a szerződés típus ne legyen kötelező
            if( !trim($szerzodes_adatok['Szerződés Típus']) && $tabla != 'projektszerzodes' ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés típus!');
            }
            if( !valos_datum_e($szerzodes_adatok['Hatálybalépés dátuma']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó hatálybalépés dátum!');
            }
            
            $ertek_nelkuli = strtolower(trim($szerzodes_adatok['Érték nélküli szerződés']));
            if( $ertek_nelkuli == 'true' ) {
                // Ekkor nullázzuk az összegeket
                $szerzodes_adatok['Szerződés Ft összege'] = '';
                $szerzodes_adatok['Szerződés összege']    = '';
                // minden más esetben az
            } else {
                if( !trim($szerzodes_adatok['Pénznem']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
                if( !trim($szerzodes_adatok['Szerződés Ft összege'])  ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szerződés összeg!');
                }
            }
            
            
        // Módosításkor a dátum mezőket kell figyelni, hogy ha van, akkor helyesek-e
        } else {
            if( $szerzodes_adatok['Iktatás dátuma'] && !valos_datum_e($szerzodes_adatok['Iktatás dátuma']) ) {
                Throw new SOAPFault ('Server', 'Helytelen iktatás dátuma!');
            }
            if( $szerzodes_adatok['Hatálybalépés dátuma'] && !valos_datum_e($szerzodes_adatok['Hatálybalépés dátuma']) ) {
                Throw new SOAPFault ('Server', 'Helytelen hatálybalépés dátuma!');
            }
        }
        
        // Nem kötelező, de mivel dátum mező, ezért ha van értéke, akkor ellenőrizni kell
        if( $szerzodes_adatok['Lejárat dátuma'] && !valos_datum_e($szerzodes_adatok['Lejárat dátuma']) ) {
            Throw new SOAPFault ('Server', 'Helytelen lejárat dátuma!');
        }
        
        // Ha a pénznem HUF, akkor a 'Szerződés összege' legyen nullázva
        // (mert ebben a deviza nettója van, és ezt ne írjuk be magunkhoz, ha HUF-os a szerződés)
        if( $szerzodes_adatok['Pénznem'] == self::KCS_21_TXT ) {
            $szerzodes_adatok['Szerződés összege'] = '';
        }
        
        // Ha van pénznem adatunk (update esetén nem feltétlen van)
        if( $szerzodes_adatok['Pénznem'] ) {
            $szerzodes_adatok['Pénznem'] = mb_strtoupper($szerzodes_adatok['Pénznem'], self::CHAR_CODING);

            // Ha devizás és nincs megadva deviza összeg, akkor hiba
            if( $szerzodes_adatok['Pénznem'] != self::KCS_21_TXT && !trim($szerzodes_adatok['Szerződés összege']) ) {
                Throw new SOAPFault ('Server', 'Devizás szerződés esetén a Szerződés összege mező kitöltése kötelező!');
            }

            // Ha devizás és nincs megadva árfolyam, akkor hiba
            if( $szerzodes_adatok['Pénznem'] != self::KCS_21_TXT && !trim($szerzodes_adatok['Árfolyam']) ) {
                Throw new SOAPFault ('Server', 'Devizás szerződés esetén az Árfolyam mező kitöltése kötelező!');
            }
            
            // Ha olyan értéket kapnánk, mint amilyen értékű pénznem nem is létezik, akkor létrehozzuk
            $penznem = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `info`='{$szerzodes_adatok['Pénznem']}'");
            if( !$penznem ) {
                $ertek = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."'", 'id desc');
                if( $ertek ) {
                    $ertek_id = $ertek['id'] + 1;
                } else {
                    $ertek_id = 1;
                }
                $penznem['id'] = $ertek_id;
                $adatbazis_kapcsolat->i('erteklista', array('csoport',          'id',       'info',                        'dhszla_id'                 ),
                                                      array(ERTEKLISTA_PENZNEM, $ertek_id,  $szerzodes_adatok['Pénznem'],  $szerzodes_adatok['Pénznem'])
                                        );
            }
        }
        
        // Foglalt szerződésszám ellenőrzése
        if( $szerzodes_adatok['Szerződés iktatószáma'] ) {
            // Átmeneti táblában
            $letezo_szszam = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`{$szszam_mezo}`='{$szerzodes_adatok['Szerződés iktatószáma']}' AND `andoc_id`!='{$andoc_id}'");
            if( $letezo_szszam ) {
                Throw new SOAPFault ('Server', '"'.$szerzodes_adatok['Szerződés iktatószáma'].'" Szerződés iktatószám már létezik a FELDOLGOZANDÓ szerződések között!');
            }
            
            // Éles táblában
            $letezo_szszam = $adatbazis_kapcsolat->s2('recid', $tabla, "`{$szszam_mezo}`='{$szerzodes_adatok['Szerződés iktatószáma']}' AND `andoc_id`!='{$andoc_id}'");
            if( $letezo_szszam ) {
                Throw new SOAPFault ('Server', '"'.$szerzodes_adatok['Szerződés iktatószáma'].'" Szerződés iktatószám szám már létezik a FELDOLGOZOTT szerződések között!');
            }
        }
        
        // Szerződés típusok
        if( $szerzodes_adatok['Szerződés Típus'] ) {
            switch ( mb_strtolower( $szerzodes_adatok['Szerződés Típus'], self::CHAR_CODING ) ) {
                case 'keretszerződés'           : $sztipus = 'ks'; break;
                case 'karbantartási szerződés'  : $sztipus = 'ka'; break;
                case 'egyedi megrendelés'       : $sztipus = 'em'; break;
                case 'egyéb szerződés'          : $sztipus = 'sz'; break;
                case 'energia szerződés'        : $sztipus = 'es'; break;
                default: 
                    if( $tabla == 'fenntartasi_szerzodes' ) {
                        Throw new SOAPFault ('Server', 'Nem ismert szerződés típus!');
                    } else {
                        // Csak fenntartási szerződés esetén kötelező a "Szerződés Típus"-t megadni
                    }
            }
        }
        
        // Hatályos
        $hatalyos = '0';
        if( mb_strtolower( $szerzodes_adatok['Hatályos'], self::CHAR_CODING) == 'igen' ) {
            $hatalyos = '1';
        }
        
        // Az adatbázisban fontos, hogy a kezdő 0-ák meglegyenek, ezzel fogunk a későbbiekben dolgozni
        // A $szerzodes_adatok['Partner száma']-t viszont ne bátsuk, hogy el tudjuk később dönteni, hogy volt-e benne érték vagy sem.
        if( $szerzodes_adatok['Partner száma'] ) {
            $szallito_id = sprintf('%05d', $szerzodes_adatok['Partner száma']);
        }

        // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a szerződést, attól függően, hogy az Andok azonsoító ismert-e.
        // Ehhez az első lépés, a mező/érték párokat beállítani.
        $szszam_mezo = 'szszam';
        $netto_mezo  = 'netto';
        if( $tabla == 'projektszerzodes' ) {
            $szszam_mezo = 'sz_szam';
            $netto_mezo  = 'szerz_ossz';
        }
        $mezok = $ertekek = array();
        if( $szerzodes_adatok['Iktatás dátuma']                         ) { array_push($mezok, 'ikt_dt'         ); array_push($ertekek, $szerzodes_adatok['Iktatás dátuma']                         ); }
        // Szálloda adatot akkor sem rögzítünk, ha kapnánk! if( $szerzodes_adatok['Szálloda - Klaszter']                    ) { array_push($mezok, 'telephely_id'   ); array_push($ertekek, $szerzodes_adatok['Szálloda - Klaszter']                    ); }
        if( $szerzodes_adatok['Rögzítő neve']                           ) { array_push($mezok, 'ikt_usr'        ); array_push($ertekek, $szerzodes_adatok['Rögzítő neve']                           ); }
        if( $szerzodes_adatok['Szerződés iktatószáma']                  ) { array_push($mezok, $szszam_mezo     ); array_push($ertekek, $szerzodes_adatok['Szerződés iktatószáma']                  ); }
        if( $szerzodes_adatok['Partner száma']                          ) { array_push($mezok, 'szallito_id'    ); array_push($ertekek, $szallito_id                                                ); }
        if( $szerzodes_adatok['Fizetési feltétel']                      ) { array_push($mezok, 'fiz_felt'       ); array_push($ertekek, $szerzodes_adatok['Fizetési feltétel']                      ); }
        if( $szerzodes_adatok['Szerződés Típus']                        ) { array_push($mezok, 'sztipus'        ); array_push($ertekek, $sztipus                                                    ); }
        if( $szerzodes_adatok['Hatályos']                               ) { array_push($mezok, 'hatalyos'       ); array_push($ertekek, $hatalyos                                                   ); }
        if( $szerzodes_adatok['Hatálybalépés dátuma']                   ) { array_push($mezok, 'erv_dt'         ); array_push($ertekek, $szerzodes_adatok['Hatálybalépés dátuma']                   ); }
        if( $szerzodes_adatok['Lejárat dátuma']                         ) { array_push($mezok, 'bef_dt'         ); array_push($ertekek, $szerzodes_adatok['Lejárat dátuma']                         ); }
        if( $szerzodes_adatok['Szerződés összege']                      ) { array_push($mezok, 'dev_net'        ); array_push($ertekek, $szerzodes_adatok['Szerződés összege']                      ); }
        if( $szerzodes_adatok['Pénznem']                                ) { array_push($mezok, 'kcs_21'         ); array_push($ertekek, $penznem['id']                                              ); }
        if( $szerzodes_adatok['Szerződés Ft összege']                   ) { array_push($mezok, $netto_mezo      ); array_push($ertekek, $szerzodes_adatok['Szerződés Ft összege']                   ); }
        if( $szerzodes_adatok['Árfolyam']                               ) { array_push($mezok, 'arfolyam'       ); array_push($ertekek, $szerzodes_adatok['Árfolyam']                               ); }
        if( $szerzodes_adatok['Szerződés szerinti fizetési határidő']   ) { array_push($mezok, 'szlafizhi'      ); array_push($ertekek, $szerzodes_adatok['Szerződés szerinti fizetési határidő']   ); }
        if( $szerzodes_adatok['Szerződés tárgya']                       ) { array_push($mezok, 'targy'          ); array_push($ertekek, $szerzodes_adatok['Szerződés tárgya']                       ); }
        if( $szerzodes_adatok['Megjegyzés']                             ) { array_push($mezok, 'megjegyz'       ); array_push($ertekek, $szerzodes_adatok['Megjegyzés']                             ); }
        if( $szerzodes_adatok['Fődokumentum link']                      ) { array_push($mezok, 'dok_link'       ); array_push($ertekek, $szerzodes_adatok['Fődokumentum link']                      ); }
        if( $ertek_nelkuli == 'true' ) {
            array_push($mezok,   'nullas');
            array_push($ertekek, 1       );
        }
        if( $ertek_nelkuli == 'false' ) {
            array_push($mezok,   'nullas');
            array_push($ertekek, 0       );
        }
        // Ha az átmeneti táblában volna a szerződés, akkor előbb töröljük
        $atmenetiben_van = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
        if( $atmenetiben_van === false ) { $db_error = mysqli_error($db_kapcs); }
        $sql = $adatbazis_kapcsolat->d($tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
        if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
// Throw new SOAPFault ('Server', $adatbazis_kapcsolat->gp());
        $elesben_van = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
        $aparecid    = $elesben_van['recid'];
        if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

        // Insert
        if( !$aparecid ) {

            // Insert esetén a foglalt szerződés számot ellenőrizni kell
            if( $adatbazis_kapcsolat->s2('id', $tabla, "`{$szszam_mezo}`='{$szerzodes_adatok['Szerződés iktatószáma']}'") ) {
                Throw new SOAPFault ('Server', 'Ilyen számú szerződés már létezik, így nem hozható létre újra!');
            }                    

            $return_uzi = 'beszúrva';

            // Insertnél kell az andoc_id is
            array_push($mezok,      'erk_xml',      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
            array_push($ertekek,    $szerzodes_xml, $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
            $aparecid = $adatbazis_kapcsolat->i($tabla.'_atmeneti', $mezok, $ertekek);
            if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

        // Update
        } else {

            $return_uzi = 'módosítva';
            
            // Update esetén erk_xml-t meg kell toldani
            $sor = $adatbazis_kapcsolat->s2('erk_xml', $tabla, "`andoc_id`='{$andoc_id}'");
            if( $sor === false ) { $db_error = mysqli_error($db_kapcs); }
            $sor['erk_xml'] = $szerzodes_xml.htmlspecialchars_decode($sor['erk_xml']);

            array_push($mezok,     'erk_xml',       'update_usr'    );
            array_push($ertekek,   $sor['erk_xml'], self::IU_USER   );

            $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
            if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

        }
// Throw new SOAPFault ('Server', $adatbazis_kapcsolat->gp());
        // Itt jönnek a tételek importja, de Andoc szerződéseknél nincsenek tételek
        // Partner insert/update, ha az eredeti 'Partner száma' adatban volt értéke
        if( $szerzodes_adatok['Partner száma'] ) {
            $mezok   = array();
            $ertekek = array();

            // Számláknál van ilyen jellemző is, de itt nincs, ezért a partner mindig aktív lesz
//            if( $szerzodes_adatok['Partner státusza'] ) {
//                $aktiv = 0;
//                if( mb_strtolower($szamla_adatok['Partner státusza'], self::CHAR_CODING) == 'aktív' ) {
                    $aktiv = 1;
//                }
                array_push($mezok,   'aktiv');
                array_push($ertekek, $aktiv );
//            }
            if( $szerzodes_adatok['Partner név'] ) {
                array_push($mezok,   'nev');
                array_push($ertekek, $szerzodes_adatok['Partner név'] );
            }
            if( $szerzodes_adatok['Partner cím'] ) {
                array_push($mezok,   'utca');
                array_push($ertekek, $szerzodes_adatok['Partner cím'] );
            }
            if( $szerzodes_adatok['Partner bankszámlaszám'] ) {
                array_push($mezok,   'bank');
                array_push($ertekek, $szerzodes_adatok['Partner bankszámlaszám'] );
            }
            if( $szerzodes_adatok['Partner adószám'] ) {
                array_push($mezok,   'adoszam');
                array_push($ertekek, $szerzodes_adatok['Partner adószám'] );
            }

            $partner = $adatbazis_kapcsolat->s2('id', 'szallito', "`id`='{$szallito_id}'");
            if( $partner ) {
                $adatbazis_kapcsolat->u('szallito', $mezok, $ertekek, "`id`='{$szallito_id}'");
            } else {
                array_push($mezok,   'id'           );
                array_push($ertekek, $szallito_id   );
                $adatbazis_kapcsolat->i('szallito', $mezok, $ertekek);
            }
        }

        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            return 'A szerződés sikeresen '.$return_uzi.'! ID: '.$andoc_id;
        } else {
            mysqli_rollback($db_kapcs);
            Throw new SOAPFault ('Server', 'A szerződés feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id.' Hibaüzenet:'.$db_error);
        }
        
    }
    
    // Költségnem importálása
    public function KoltsegnemAdatokImport( $koltsegnem_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $koltsegnemek = simplexml_load_string($koltsegnem_xml, 'SimpleXMLElement');

        foreach ( $koltsegnemek as $koltsegnem ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);

            // Kötelező mezők ellenőrzése
            if(  !trim($koltsegnem['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id = $koltsegnem['andoc_id'];
            $tabla = 'ktgnem';
            
            // Törléshez ismernünk kell az andoc_id-t
            // Delete
            if( $koltsegnem -> torles == 1 ) {
                
                $sql = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                if( !$sql ) {
                    Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
                }
                
                $return_uzi = 'törölve';
                    
                // Kötelező mezők ellenőrzése
                $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
            
            // Insert/update
            } else {
                
                if( !trim($koltsegnem -> id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó ID!');
                }
                if( !trim($koltsegnem -> nev) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó név!');
                }

                $mezok = array(
                                'id',
                                'nev',
                            );
                $ertekek = array(
                                sprintf('%07d', $koltsegnem -> id),
                                $koltsegnem -> nev,
                            );


                $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                $aparecid = $aparecid['recid'];
                if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Insert
                if( !$aparecid ) {
                    
                    // Insert esetén a foglalt ID-t ellenőrizni kell
                    if( $adatbazis_kapcsolat->s2('id', $tabla, "`id`='{$koltsegnem -> id}'") ) {
                        Throw new SOAPFault ('Server', 'Ilyen ID-jú költségnem már létezik, így nem hozható létre újra!');
                    }

                    $return_uzi = 'beszúrva';
                    
                    // Insertnél kell az andoc_id is
                    array_push($mezok,      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
                    array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
                    $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                    if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Update
                } else {

                    $return_uzi     = 'frissítve';
                    
                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                }
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A költségnem sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                Throw new SOAPFault ('Server', 'A költségnem feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id.' Hibaüzenet:'.$db_error);
            }
        }
        
    }
    
    // Költséghely importálása
    public function KoltseghelyAdatokImport( $koltseghely_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $koltseghelyek = simplexml_load_string($koltseghely_xml, 'SimpleXMLElement');
        foreach ( $koltseghelyek as $koltseghely ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);

            // Kötelező mezők ellenőrzése
            if(  !trim($koltseghely['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id = $koltseghely['andoc_id'];
            $tabla = 'ktghely';
            
            // Törléshez ismernünk kell az andoc_id-t
            // Delete
            if( $koltseghely -> torles == 1 ) {
                
                $sql = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                if( !$sql ) {
                    Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
                }
                
                $return_uzi = 'törölve';
                
                $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
            
            // Insert/update
            } else {
                
                // Kötelező mezők ellenőrzése
                if( !trim($koltseghely -> id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó ID!');
                }
                if( !trim($koltseghely -> nev) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó név!');
                }
                
                $mezok = array(
                                'id',
                                'nev',
                            );
                $ertekek = array(
                                sprintf('%04d', $koltseghely -> id),
                                $koltseghely -> nev,
                            );


                $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                $aparecid = $aparecid['recid'];
                if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Insert
                if( !$aparecid ) {
                    
                    // Insert esetén a foglalt ID-t ellenőrizni kell
                    if( $adatbazis_kapcsolat->s2('id', $tabla, "`id`='{$koltseghely -> id}'") ) {
                        Throw new SOAPFault ('Server', 'Ilyen ID-jú költséghely már létezik, így nem hozható létre újra!');
                    }
                    
                    $return_uzi = 'beszúrva';

                    // Insertnél kell az andoc_id is
                    array_push($mezok,      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
                    array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
                    $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                    if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Update
                } else {
                    
                    $return_uzi = 'módosítva';

                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                }
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A költséghely sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                Throw new SOAPFault ('Server', 'A költséghely feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id.' Hibaüzenet:'.$db_error);
            }
        }
        
    }
    
    // Partner importálása
    public function SzallitoAdatokImport( $szallito_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $szallitok = simplexml_load_string($szallito_xml, 'SimpleXMLElement');

        foreach ( $szallitok as $szallito ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);

            // Kötelező mezők ellenőrzése
            if(  !trim($szallito['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id   = $szallito['andoc_id'];
            $tabla      = 'szallito';

            // Törléshez ismernünk kell az andoc_id-t
            // Delete
            if( $szallito -> torles == 1 ) {
                
                $sql = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                if( !$sql ) {
                    Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
                }
                
                $return_uzi = 'törölve';
                $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }
            
            // Insert/update
            } else {
                
                // Kötelező mezők ellenőrzése
                if( !trim($szallito -> id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó ID!');
                }
                if( !trim($szallito -> nev) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó név!');
                }
                
                // szallito_id esetén fontos, hogy meglegyenek a keződ 0-k
                $szallito_id = sprintf('%05d', $szallito -> id);
                
                $mezok = array(
                                'id',
                                'nev',
                                'aktiv',
                                'isz',
                                'varos',
                                'utca',
                                'lcim',
                                'bank',
                                'knev',
                                'tel',
                                'tel2',
                                'email',
                                'adoszam',
                                'cg',
                                'text',
                            );
                $ertekek = array(
                                $szallito_id,
                                $szallito -> nev,
                                $szallito -> aktiv,
                                $szallito -> isz,
                                $szallito -> varos,
                                $szallito -> utca,
                                $szallito -> lcim,
                                $szallito -> bank,
                                $szallito -> knev,
                                $szallito -> tel,
                                $szallito -> tel2,
                                $szallito -> email,
                                $szallito -> adoszam,
                                $szallito -> cg,
                                $szallito -> text,
                            );


                $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                $aparecid = $aparecid['recid'];
                if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Insert
                if( !$aparecid ) {
                    $return_uzi = 'beszúrva';
                    
                    // Insert esetén a foglalt ID-t ellenőrizni kell
                    if( $adatbazis_kapcsolat->s2('id', $tabla, "`id`='{$szallito -> id}'") ) {
                        Throw new SOAPFault ('Server', 'Ilyen ID-jú parter már létezik, így nem hozható létre újra!');
                    }

                    // Insertnél kell az andoc_id is
                    array_push($mezok,      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
                    array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
                    $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                    if( $aparecid === false ) { $db_error = mysqli_error($db_kapcs); }

                // Update
                } else {
                    
                    $return_uzi = 'módosítva';

                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = mysqli_error($db_kapcs); }

                }
            }

            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A partner sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                Throw new SOAPFault ('Server', 'A partner feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id.' Hibaüzenet:'.$db_error);
            }
        }
        
    }
}
