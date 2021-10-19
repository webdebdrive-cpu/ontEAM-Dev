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
        if( !trim($szamla -> data -> register['registernumber']) ) {
            Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
        }
        $szamla_andoc_id = $andoc_id = $szamla -> data -> register['registernumber'];
        if( !$szamla_adatok['Számla főtípus'] ) {
            Throw new SOAPFault ('Server', 'Hiányzó számlatípus!');
        }

        if( trim($szamla -> data -> register -> file['name']) ) {
            Throw new SOAPFault ('Server', 'A számla nem befogadható, mert tartalmazza a kapcsolt file-t!');
        }

        // Típus alapján az adatbázis táblák azonosítása
        $szamla_tipus = mb_strtolower($szamla_adatok['Számla főtípus'], self::CHAR_CODING);
        switch( $szamla_tipus ) {
            case self::SZAMLA_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szamla';    break;
            case self::SZAMLA_TIPUS_PROJEKT         : $tabla = 'projektszamla';         break;
            case self::SZAMLA_TIPUS_ENERGIA         : $tabla = 'energia_szamla';        break;
            case self::SZAMLA_TIPUS_ANYAG           : $tabla = 'anyag_szamla';          break;
            default                                 : $tabla = '!hiba!';                break;
        }
        if( $tabla == '!hiba!' ) {
            Throw new SOAPFault ('Server', 'Nem azonosítható be a számla főtípusa!');
        }
        $apa_recid_oszlop = $tabla.'_recid';
        
        // Azt, hogy ez insert vagy update lesz, azt már itt el kell dönteni, hisz update esetén nem kötelezőek a mezők!
        $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
        $aparecid = $aparecid['recid'];
        if( $aparecid === false ) { $db_error = true; }
        if( $aparecid ) {
            $insert_update = 'update';
        } else {
            $insert_update = 'insert';
        }

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
//            if( $sql === false ) { $db_error = true; }
//            $sql = $adatbazis_kapcsolat->d($tabla.'_tetel', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
//            if( $sql === false ) { $db_error = true; }
//            $sql = $adatbazis_kapcsolat->d($tabla.'_kifizetesek', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
//            if( $sql === false ) { $db_error = true; }
//
//        // Insert/update
//        } else {

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
                // szallito_id esetén fontos, hogy meglegyenek a kezdő 0-k
                $szallito_id = sprintf('%05d', $szamla_adatok['Partner száma']);
            
                if( !valos_datum_e($szamla_adatok['Teljesítés dátuma']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen teljesítés dátum!');
                }
                if( !valos_datum_e($szamla_adatok['Fizetési határidő']) ) {
                    Throw new SOAPFault ('Server', 'Helytelen fizetési határidő!');
                }
                if( !trim($szamla_adatok['Számla bruttó összege']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó bruttó érték!');
                }

                // Itt most "HUF", "EUR" stb-t kapunk, tehát az értéket és nem a kódot.
                if( !trim($szamla_adatok['Pénznem']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
                
            // Módosításkor a dátum mezőket kell figyelni, hogy ha van, akkor helyesek-e
            } else {
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
                if( !trim($szamla_adatok['Fizetés módja']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó fizetési mód!');
                }
                if( !trim($szamla_adatok['Partner száma']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szállító!');
                }
                // szallito_id esetén fontos, hogy meglegyenek a kezdő 0-k
                $szallito_id = sprintf('%05d', $szamla_adatok['Partner száma']);
                
                // Itt most "HUF", "EUR" stb-t kapunk, tehát az értéket és nem a kódot.
                if( !trim($szamla_adatok['Pénznem']) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
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
            
            // Ha van pénznem adatunk (update esetén nem feltétlen van)
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
                    if( $sql === false ) { $db_error = true; }
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

            // 2021.09.09 Kérésre a tételek már nem kötelezőek
//            if( !count($szamla_tetelek) && $insert_update == 'insert' ) {
//                Throw new SOAPFault ('Server', 'Hiányzó tételek!');
//            }
            
            // Kifizetésekkel sem foglalkozunk már, nem használunk ilyen gyermek táblát
            // $kifizetesek = $szamla -> kifizetes;

            // Ha volna pár kötelező mező, ami már számlatípus specifikus
            if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {

            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {

            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_ENERGIA ) {

            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG ) {

            }

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

            // Elutasítás leírása + Utalványozás leírása egyben
            $eutleir = trim($szamla['Teljesítés igazolás elutasítás oka'].' '.$szamla['Utalványozás elutasítás oka']);
            
            // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól föggően, hogy az Andok azonosító ismert-e.
            // Ehhez az első lépés, a mező/érték párokat beállítani.
            $mezok = $ertekek = array();
            if( $szamla_adatok['Számla érkezés dátuma']         ) { array_push($mezok, 'erk_dt'         ); array_push($ertekek, $szamla_adatok['Számla érkezés dátuma']);   }
            if( $szamla_adatok['Számla kelte']                  ) { array_push($mezok, 'kelte_dt'       ); array_push($ertekek, $szamla_adatok['Számla kelte']);            }
            if( $szamla_adatok['Számla sorszáma']               ) { array_push($mezok, 'szla_szam'      ); array_push($ertekek, $szamla_adatok['Számla sorszáma']);         }
            if( $szamla_adatok['Danubius azonosító']            ) { array_push($mezok, 'ikt_szam'       ); array_push($ertekek, $szamla_adatok['Danubius azonosító']);      }
            if( $szamla_adatok['Rögzítő neve']                  ) { array_push($mezok, 'ikt_usr'        ); array_push($ertekek, $szamla_adatok['Rögzítő neve']);            }
            if( $szamla_adatok['Számla típusa']                 ) { array_push($mezok, 'tipus'          ); array_push($ertekek, $szamla_adatok['Számla típusa']);           }
            if( $szamla_adatok['Vonalkód']                      ) { array_push($mezok, 'vonalkod'       ); array_push($ertekek, $szamla_adatok['Vonalkód']);                }
            if( $szamla_adatok['Teljesítés dátuma']             ) { array_push($mezok, 'telj_dt'        ); array_push($ertekek, $szamla_adatok['Teljesítés dátuma']);       }
            if( $szamla_adatok['Fizetési határidő']             ) { array_push($mezok, 'fizhat_dt'      ); array_push($ertekek, $szamla_adatok['Fizetési határidő']);       }
            if( $szamla_adatok['Szálloda - Klaszter']           ) { array_push($mezok, 'telephely_id'   ); array_push($ertekek, $szamla_adatok['Szálloda - Klaszter']);     }
            if( $szamla_adatok['Partner száma']                 ) { array_push($mezok, 'szallito_id'    ); array_push($ertekek, $szallito_id);                              }
            if( $szamla_adatok['Számla tárgya']                 ) { array_push($mezok, 'targy'          ); array_push($ertekek, $szamla_adatok['Számla tárgya']);           }
            if( $szamla_adatok['Pénznem']                       ) { array_push($mezok, 'kcs_21'         ); array_push($ertekek, $penznem['id']);                            }
            if( $szamla_adatok['Átváltva - NINCS ILYEN MEZŐ']   ) { array_push($mezok, 'atvaltva_dt'    ); array_push($ertekek, $szamla_adatok['Átváltva - NINCS ILYEN..']);}
            if( $szamla_adatok['Belső árfolyam']                ) { array_push($mezok, 'arfolyam'       ); array_push($ertekek, $szamla_adatok['Belső árfolyam']);          }
            if( $szamla_adatok['Szállító árfolyama']            ) { array_push($mezok, 'arfolyam_szall' ); array_push($ertekek, $szamla_adatok['Szállító árfolyama']);      }
            if( $szamla_adatok['Dev net - NINCS ILYEN MEZŐ']    ) { array_push($mezok, 'dev_net'        ); array_push($ertekek, $szamla_adatok['Dev net - NINCS ILYEN ']);  }
            if( $szamla_adatok['Deviza bruttó összege']         ) { array_push($mezok, 'dev_br'         ); array_push($ertekek, $szamla_adatok['Deviza bruttó összege']);   }
            if( $szamla_adatok['Nettó - NINCS ILYEN MEZŐ']      ) { array_push($mezok, 'netto'          ); array_push($ertekek, $szamla_adatok['Nettó - NINCS ILYEN MEZŐ']);}
            if( $szamla_adatok['Számla bruttó összege']         ) { array_push($mezok, 'brutto'         ); array_push($ertekek, $szamla_adatok['Számla bruttó összege']);   }
            if( $szamla_adatok['Kifizetett összeg']             ) { array_push($mezok, 'kif_ossz'       ); array_push($ertekek, $szamla_adatok['Kifizetett összeg']);       }
            if( $szamla_adatok['Utolsó kifizetés dátuma']       ) { array_push($mezok, 'ut_kif_dt'      ); array_push($ertekek, $szamla_adatok['Utolsó kifizetés dátuma']); }
            if( $szamla_adatok['Kifizetve - NINCS ILYEN MEZŐ']  ) { array_push($mezok, 'kifizetve'      ); array_push($ertekek, $szamla_adatok['Kifizetve - NINCS ILYE..']);}
            if( $szamla_adatok['E-Számla']                      ) { array_push($mezok, 'eszamla'        ); array_push($ertekek, $eszamla);                                  }
            if( $szamla_adatok['Visszaadva - NINCS ILYEN MEZŐ'] ) { array_push($mezok, 'visszaadva'     ); array_push($ertekek, $szamla_adatok['Visszaadva - NINCS ILYEN']);}
            if( $szamla_adatok['Visszaadva dátum - NINCS I...'] ) { array_push($mezok, 'visszaadva_dt'  ); array_push($ertekek, $szamla_adatok['Visszaadva dátum - NINCS']);}
            if( $szamla_adatok['Igazoló döntés']                ) { array_push($mezok, 'igazolva'       ); array_push($ertekek, $igazolva);                                 }
            if( $szamla_adatok['Utalványozó döntés']            ) { array_push($mezok, 'utalvanyozva'   ); array_push($ertekek, $utalvanyozva);                             }
            if( $szamla_adatok['Megjegyzés']                    ) { array_push($mezok, 'megjegyz'       ); array_push($ertekek, $szamla_adatok['Megjegyzés']);              }
            if( $szamla_adatok['Számla bruttó összege']         ) { array_push($mezok, 'nullas'         ); array_push($ertekek, $nullas);                                   }
            if( $szamla_adatok['Fődokumentum link']             ) { array_push($mezok, 'dok_link'       ); array_push($ertekek, $szamla_adatok['Fődokumentum link']);       }
            
            // További mezők felvétele, számla típusától függően
            if( $szamla_tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                if( $szamla_adatok['Beruházási projektkód']         ) { array_push($mezok, 'pr_szam'    ); array_push($ertekek, $szamla_adatok['Beruházási projektkód']);       }
                if( $fizmod                                         ) { array_push($mezok, 'fizmod'     ); array_push($ertekek, $fizmod);                                       }
                if( $szamla['Teljesítés igazolás elutasítás oka']   ) { array_push($mezok, 'elutleir'   ); array_push($ertekek, $szamla['Teljesítés igazolás elutasítás oka']); }
                $feldolgozasra_var_stat = F_SZAMLA_STAT_FELDOLGOZASRA_VAR;
            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                if( $szamla_adatok['Beruházási projektkód']         ) { array_push($mezok, 'pr_szam'    ); array_push($ertekek, $szamla_adatok['Beruházási projektkód']);       }
                if( $fizmod                                         ) { array_push($mezok, 'fizmod'     ); array_push($ertekek, $fizmod);                                       }
                if( $szamla['Teljesítés igazolás elutasítás oka']   ) { array_push($mezok, 'elutleir'   ); array_push($ertekek, $szamla['Teljesítés igazolás elutasítás oka']); }
                if( $szamla['Kapcsolódó szerződés/megrendelés']     ) { array_push($mezok, 'sz_szam'    ); array_push($ertekek, $szamla['Kapcsolódó szerződés/megrendelés']);   }
                $feldolgozasra_var_stat = P_SZAMLA_STAT_FELDOLGOZASRA_VAR;
            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_ENERGIA ) {
                if( $szamla_adatok['Beruházási projektkód']         ) { array_push($mezok, 'pr_szam'    ); array_push($ertekek, $szamla_adatok['Beruházási projektkód']);       }
                if( $fizmod                                         ) { array_push($mezok, 'fizmod'     ); array_push($ertekek, $fizmod);                                       }
                if( $szamla['Teljesítés igazolás elutasítás oka']   ) { array_push($mezok, 'elutleir'   ); array_push($ertekek, $szamla['Teljesítés igazolás elutasítás oka']); }
                $feldolgozasra_var_stat = E_SZAMLA_STAT_FELDOLGOZASRA_VAR;
            }
            if( $szamla_tipus == self::SZAMLA_TIPUS_ANYAG ) {
                if( $szamla_adatok['Beruházási projektkód']         ) { array_push($mezok, 'pr_szam'    ); array_push($ertekek, $szamla_adatok['Beruházási projektkód']);       }
                if( $fizmod                                         ) { array_push($mezok, 'fizmod'     ); array_push($ertekek, $fizmod);                                       }
                if( $szamla['Teljesítés igazolás elutasítás oka']   ) { array_push($mezok, 'elutleir'   ); array_push($ertekek, $szamla['Teljesítés igazolás elutasítás oka']); }
                $feldolgozasra_var_stat = A_SZAMLA_STAT_FELDOLGOZASRA_VAR;
            }

            // Ha az átmeneti táblában volna a számla, akkor előbb töröljük
            $atmenetiben_van = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
            if( $atmenetiben_van === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->d($tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
            if( $sql === false ) { $db_error = true; }
            $sql = $adatbazis_kapcsolat->d($tabla.'_tetel_atmeneti', "`{$tabla}_recid`='{$atmenetiben_van['recid']}'");
            if( $sql === false ) { $db_error = true; }
            
            $elesben_van = $adatbazis_kapcsolat->s2('recid igazolva_dt', $tabla, "`andoc_id`='{$andoc_id}'");
            $aparecid    = $elesben_van['recid'];
            if( $aparecid === false ) { $db_error = true; }

            // Insert
            if( !$aparecid ) {

                $return_uzi = 'beszúrva';

                // Insertnél kell az andoc_id is
                array_push($mezok,     'erk_xml',   'andoc_id', 'insert_usr',  'update_usr',   'insert_dt'  );
                array_push($ertekek,   $szamla_xml, $andoc_id,  self::IU_USER,  self::IU_USER, 'NOW()'      );
                $aparecid = $adatbazis_kapcsolat->i($tabla.'_atmeneti', $mezok, $ertekek);
                if( $aparecid === false ) { $db_error = true; }
 
                // UPDATE: számláknál nem használunk státusz egyáltalán
                // Új beszúráskor a kezdőstátusz fixen a feldolgozásra vár lesz
//                $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array($feldolgozasra_var_stat), "`recid`='{$aparecid}'");
//                if( $sql === false ) { $db_error = true; }
//                $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
//                                                array($tabla.'_recid',   'stat_id',               'login',          'insert_dt', 'insert_usr',  'update_usr'    ),
//                                                array($aparecid,         $feldolgozasra_var_stat, self::IU_USER,    'NOW()',     self::IU_USER, self::IU_USER   )
//                                            );
//                if( $sql === false ) { $db_error = true; }

            // Update
            } else {

                // Ha a számla még nincs igazolva, akkor addig nem updatelhető
                if( $elesben_van['igazolva_dt']=='0000-00-00 00:00:00' ) {
                    Throw new SOAPFault ('Server', 'A számla nincs igazolva, így az nem módosítható még!');
                }

                $return_uzi = 'módosítva';
                
                // Update esetén erk_xml-t meg kell toldani
                $sor = $adatbazis_kapcsolat->s2('erk_xml utalvanyozva_dt', $tabla, "`andoc_id`='{$andoc_id}'");
                if( $sor === false ) { $db_error = true; }
                $sor['erk_xml'] = htmlspecialchars_decode($sor['erk_xml']).$szamla_xml;
    
                array_push($mezok,     'erk_xml',       'update_usr'    );
                array_push($ertekek,   $sor['erk_xml'], self::IU_USER   );
                if( $utalvanyozva == '1' && $sor['utalvanyozva_dt'] == '0000-00-00 00:00:00' ) {
                    array_push($mezok,     'utalvanyozva_dt');
                    array_push($ertekek,   'NOW()'          );
                }

                $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
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
//                        if( $sql === false ) { $db_error = true; }
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
//                    if( $tetel_letezik === false ) { $db_error = true; }
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
//                        if( $sql === false ) { $db_error = true; }
//
//                    // Update
//                    } else {
//
//                        $sql = $adatbazis_kapcsolat->u($tabla.'_tetel', $mezok, $ertekek, "`rownum`='{$tetel['rownumberonpage']}'");
//                        if( $sql === false ) { $db_error = true; }
//
//                    }
//                }
//            }

            // Partner insert/update
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
                
                $partner = $adatbazis_kapcsolat->s2('id', 'szallito', "`id`='{$szamla_adatok['Partner száma']}'");
                if( $partner ) {
                    $adatbazis_kapcsolat->u('szallito', $mezok, $ertekek, "`id`='{$szamla_adatok['Partner száma']}'");
                } else {
                    array_push($mezok,   'id'                           );
                    array_push($ertekek, $szamla_adatok['Partner száma']);
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
//                if( $kifizetes_letezik === false ) { $db_error = true; }
//
//                // Insert
//                if( !$kifizetes_letezik ) {
//
//                    // Insertnél kell az andoc_id is
//                    array_push($mezok,      'andoc_id', 'insert_usr',   'insert_dt' );
//                    array_push($ertekek,    $andoc_id,  self::IU_USER,  'NOW()'     );
//                    $sql = $adatbazis_kapcsolat->i($tabla.'_kifizetesek', $mezok, $ertekek);
//                    if( $sql === false ) { $db_error = true; }
//
//                // Update
//                } else {
//
//                    $sql = $adatbazis_kapcsolat->u($tabla.'_kifizetesek', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
//                    if( $sql === false ) { $db_error = true; }
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
            return 'A számla feldolgozása közben ismeretlen hiba történt: ID: '.$szamla_andoc_id;
        }
        
    }
    
    // Szerződés importálása
    public function SzerzodesAdatokImport( $szerzodes_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $szerzodes = simplexml_load_string($szerzodes_xml, 'SimpleXMLElement');
        $szerzodes_xml = date('Y-m-d H:i:s').$szerzodes_xml."\n\n\n";

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
        }
        
        // Kötelező mezők ellenőrzése ami Insert, update és delete utasításhoz is kellenek
        if( !trim($szerzodes -> data -> register['registernumber']) ) {
            Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
        }

        if( trim($szerzodes -> data -> register -> file['name']) ) {
            Throw new SOAPFault ('Server', 'A szerződés nem befogadható, mert tartalmazza a kapcsolt file-t!');
        }
        
        $szerzodes_andoc_id = $andoc_id = $szerzodes -> data -> register['registernumber'];
        
        if( !$szerzodes_adatok['Szerződés iránya'] ) {
            // Ha nincs megadva a szerződés iránya, akkor fenntartási szerződésnek vesszük
            // Throw new SOAPFault ('Server', 'Kérem adja meg a szerződés irányát!');
            $szerzodes_adatok['Szerződés iránya'] = SZERZ_TIPUS_FENNTARTASI;
        }
        switch( strtolower( $szerzodes_adatok['Szerződés iránya'] ) ) {
            case self::SZERZ_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szerzodes';    break;
            case self::SZERZ_TIPUS_PROJEKT         : $tabla = 'projektszerzodes';         break;
        }
        
        $apa_recid_oszlop = $tabla.'_recid';
        
        // Azt, hogy ez insert vagy update lesz, azt már itt el kell dönteni, hisz update esetén nem kötelezőek a mezők!
        $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
        $aparecid = $aparecid['recid'];
        if( $aparecid === false ) { $db_error = true; }
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
            if( !trim($szerzodes_adatok['Pénznem']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
            }
            if( !trim($szerzodes_adatok['Szerződés Ft összege'])  ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés összeg!');
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
        
        // szallito_id esetén fontos, hogy meglegyenek a keződ 0-k
        $szallito_id = sprintf('%05d', $szerzodes_adatok['Partner száma']);

        // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a szerződést, attól föggően, hogy az Andok azonsoító ismert-e.
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
        
        // Ha az átmeneti táblában volna a szerződés, akkor előbb töröljük
        $atmenetiben_van = $adatbazis_kapcsolat->s2('recid', $tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
        if( $atmenetiben_van === false ) { $db_error = true; }
        $sql = $adatbazis_kapcsolat->d($tabla.'_atmeneti', "`andoc_id`='{$andoc_id}'");
        if( $sql === false ) { $db_error = true; }

        $elesben_van = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
        $aparecid    = $elesben_van['recid'];
        if( $aparecid === false ) { $db_error = true; }

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
            if( $aparecid === false ) { $db_error = true; }
// Throw new SOAPFault ('Server', $adatbazis_kapcsolat->gp());

        // Update
        } else {

            $return_uzi = 'módosítva';
            
            // Update esetén erk_xml-t meg kell toldani
            $sor = $adatbazis_kapcsolat->s2('erk_xml', $tabla, "`andoc_id`='{$andoc_id}'");
            if( $sor === false ) { $db_error = true; }
            $sor['erk_xml'] = htmlspecialchars_decode($sor['erk_xml']).$szerzodes_xml;

            array_push($mezok,     'erk_xml',       'update_usr'    );
            array_push($ertekek,   $sor['erk_xml'], self::IU_USER   );

            $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
            if( $sql === false ) { $db_error = true; }

        }
        
        // Itt jönnek a tételek importja, de Andoc szerződéseknél nincsenek tételek
        // Partner insert/update
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

            $partner = $adatbazis_kapcsolat->s2('id', 'szallito', "`id`='{$szerzodes_adatok['Partner száma']}'");
            if( $partner ) {
                $adatbazis_kapcsolat->u('szallito', $mezok, $ertekek, "`id`='{$szerzodes_adatok['Partner száma']}'");
            } else {
                array_push($mezok,   'id'                              );
                array_push($ertekek, $szerzodes_adatok['Partner száma']   );
                $adatbazis_kapcsolat->i('szallito', $mezok, $ertekek);
            }
        }

        // Tranzakció kommitolása, vagy rollback-ezése
        if( !$db_error ) {
            mysqli_commit($db_kapcs);
            return 'A szerződés sikeresen '.$return_uzi.'! ID: '.$andoc_id;
        } else {
            mysqli_rollback($db_kapcs);
            return 'A szerződés feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id;
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
                if( $sql === false ) { $db_error = true; }
            
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
                if( $aparecid === false ) { $db_error = true; }

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
                    if( $aparecid === false ) { $db_error = true; }

                // Update
                } else {

                    $return_uzi     = 'frissítve';
                    
                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = true; }

                }
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A költségnem sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                return 'A költségnem feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id;
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
                if( $sql === false ) { $db_error = true; }
            
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
                if( $aparecid === false ) { $db_error = true; }

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
                    if( $aparecid === false ) { $db_error = true; }

                // Update
                } else {
                    
                    $return_uzi = 'módosítva';

                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = true; }

                }
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A költséghely sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                return 'A költséghely feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id;
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
                if( $sql === false ) { $db_error = true; }
            
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
                if( $aparecid === false ) { $db_error = true; }

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
                    if( $aparecid === false ) { $db_error = true; }

                // Update
                } else {
                    
                    $return_uzi = 'módosítva';

                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );

                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = true; }

                }
            }

            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A partner sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                return 'A partner feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id;
            }
        }
        
    }
}
