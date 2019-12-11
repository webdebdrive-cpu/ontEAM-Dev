<?php
require_once 'weblap.php';

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
    const SZAMLA_TIPUS_FENNTARTASI  = 1;
    const SZAMLA_TIPUS_PROJEKT      = 2;
    const SZAMLA_TIPUS_ENERGIA      = 3;
    const SZAMLA_TIPUS_ANYAG        = 4;
    
    const SZERZ_TIPUS_FENNTARTASI   = 1;
    const SZERZ_TIPUS_PROJEKT       = 2;
    
    const KCS_21_HUF                = 5;
    const IU_USER                   = 'Andoc';
    
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

        $szamlak = simplexml_load_string($szamla_xml, 'SimpleXMLElement');

        foreach ( $szamlak as $szamla ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);
            
            // Kötelező mezők ellenőrzése ami Insert, update és delete utasításhoz is kellenek
            if(  !trim($szamla['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $szamla_andoc_id = $andoc_id = $szamla['andoc_id'];
            if( !$szamla -> tipus ) {
                Throw new SOAPFault ('Server', 'Hiányzó számlatípus!');
            }
            
            // Típus alapján az adatbázis táblák azonosítása
            switch( $szamla -> tipus ) {
                case self::SZAMLA_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szamla';    break;
                case self::SZAMLA_TIPUS_PROJEKT         : $tabla = 'projektszamla';         break;
                case self::SZAMLA_TIPUS_ENERGIA         : $tabla = 'energia_szamla';        break;
                case self::SZAMLA_TIPUS_ANYAG           : $tabla = 'anyag_szamla';          break;
            }
            $apa_recid_oszlop = $tabla.'_recid';

            // Törléshez ismernünk kell az andoc_id-t és a számla típusát
            // Delete
            if( $szamla -> torles == 1 ) {
                
                $apa_tabla = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                if( !$apa_tabla ) {
                    Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
                }
                
                $return_uzi = 'törölve';
                
                $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
                $sql = $adatbazis_kapcsolat->d($tabla.'_tetel', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
                if( $sql === false ) { $db_error = true; }
                $sql = $adatbazis_kapcsolat->d($tabla.'_kifizetesek', "`{$apa_recid_oszlop}`='{$apa_tabla['recid']}'");
                if( $sql === false ) { $db_error = true; }
                
            // Insert/update
            } else {
            
                if( !trim($szamla -> ikt_szam) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó iktatószám!');
                }
                if( !valos_datum_e($szamla -> kelte_dt) ) {
                    Throw new SOAPFault ('Server', 'Helytelen keltezés!');
                }
                if( !valos_datum_e($szamla -> erk_dt) ) {
                    Throw new SOAPFault ('Server', 'Helytelen érkezés dátum!');
                }
                if( !trim($szamla -> szla_szam) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó számlaszám!');
                }
                if( !trim($szamla -> szallito_id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szállító!');
                }
                if( !valos_datum_e($szamla -> telj_dt) ) {
                    Throw new SOAPFault ('Server', 'Helytelen teljesítés dátum!');
                }
                if( !valos_datum_e($szamla -> fizhat_dt) ) {
                    Throw new SOAPFault ('Server', 'Helytelen fizetési határidő!');
                }
                if( !trim($szamla -> netto) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó nettó érték!');
                }
                if( !trim($szamla -> brutto) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó bruttó érték!');
                }
                if( !trim($szamla -> kcs_21) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
                // Ha olyan értéket kapnánk, mint amilyen értékű pénznem nem is létezik, akkor hibát dobunk
                $penznem = $adatbazis_kapcsolat->s2('recid', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `id`='{$szamla -> kcs_21}'");
                if( !$penznem ) {
                    Throw new SOAPFault ('Server', 'Nem ismert pénznem!');
                }
                $tetelek = $szamla -> tetel;
                if( !count($tetelek) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó tételek!');
                }
                $kifizetesek = $szamla -> kifizetes;

                // Van pár kötelező mező, ami már számlatípus specifikus
                if( $szamla -> tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                    if( !trim($szamla -> fizmod) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó fizetési mód');
                    }
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                    if( !trim($szamla -> telephely_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó szálloda');
                    }
                    if( !trim($szamla -> targy) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó tárgy');
                    }
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_ENERGIA ) {
                    if( !trim($szamla -> telephely_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó szálloda');
                    }
                    if( !trim($szamla -> szev) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó számlázott év!');
                    }
                    if( !trim($szamla -> szhonap) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó számlázott hónap!');
                    }
                    if( !trim($szamla -> vontol_dt) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó von.időszak (tól-)!');
                    }
                    if( !trim($szamla -> vonig_dt) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó von.időszak (ig-)!');
                    }
                    if( !trim($szamla -> ktgnem_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó költségnem');
                    }
                    if( !trim($szamla -> ktghely_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó költséghely');
                    }
                    if( trim($szamla -> ktghely_id) != 7020 ) {
                        Throw new SOAPFault ('Server', 'Helytelen költséghely. Csak energia lehet (ID=7020)');
                    }
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_ANYAG ) {
                    if( !trim($szamla -> telephely_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó szálloda');
                    }
                    if( !trim($szamla -> rndid) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó rendelés id');
                    }
                    if( !trim($szamla -> targy) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó tárgy');
                    }
                    if( !trim($szamla -> fizmod) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó fizetési mód');
                    }
                }
                // Ha nem HUF-os a számla, akkor pár mező még kötelezővé válik, az összes számlánál
                if( $szamla -> kcs_21 != self::KCS_21_HUF ) {

                    if( !trim($szamla -> dev_net) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó deviza nettó érték!');
                    }
                    if( !trim($szamla -> dev_br) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó deviza bruttó érték!');
                    }

                }
                
                // Iktatószám csak egyedi lehet
                $sql = $adatbazis_kapcsolat->s('recid', $tabla, "`ikt_szam`='{$szamla -> ikt_szam}' AND `andoc_id`!='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
                if( $sql ) {
                    Throw new SOAPFault ('Server', 'Hiba! Ez az iktatószám már foglalt: '.$szamla -> ikt_szam);
                }
                
                // szallito_id esetén fontos, hogy meglegyenek a keződ 0-k
                $szallito_id = sprintf('%05d', $szamla -> szallito_id);

                // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól föggően, hogy az Andok azonsoító ismert-e.
                // Ehhez az első lépés, a mező/érték párokat beállítani.
                $mezok = array(
                                'erk_dt',
                                'kelte_dt',
                                'szla_szam',
                                'ikt_szam',
                                'telj_dt',
                                'fizhat_dt',
                                'telephely_id',
                                'szallito_id',
                                'targy',
                                'kcs_21',
                                'atvaltva_dt',
                                'arfolyam',
                                'dev_net',
                                'dev_br',
                                'netto',
                                'brutto',
                                'kifizetve',
                                'nullas',
                                'visszaadva',
                                'visszaadva_dt',
                            );
                $ertekek = array(
                                $szamla -> erk_dt,
                                $szamla -> kelte_dt,
                                $szamla -> szla_szam,
                                $szamla -> ikt_szam,
                                $szamla -> telj_dt,
                                $szamla -> fizhat_dt,
                                $szamla -> telephely_id,
                                $szallito_id,
                                $szamla -> targy,
                                $szamla -> kcs_21,
                                $szamla -> atvaltva_dt,
                                $szamla -> arfolyam,
                                $szamla -> dev_net,
                                $szamla -> dev_br,
                                $szamla -> netto,
                                $szamla -> brutto,
                                $szamla -> kifizetve,
                                $szamla -> nullas,
                                $szamla -> visszaadva,
                                $szamla -> visszaadva_dt,
                            );

                // További mezők felvétele, számla típusától függően
                if( $szamla -> tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                    array_push($mezok,     'kcs_25',            'fizmod',           'elutleir'          );
                    array_push($ertekek,   $szamla -> kcs_25,   $szamla -> fizmod,  $szamla -> elutleir );
                    $feldolgozasra_var_stat = F_SZAMLA_STAT_FELDOLGOZASRA_VAR;
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_PROJEKT ) {
                    array_push($mezok,      'muszak_szam',          'musz_dt',          'kolt_vis'          );
                    array_push($ertekek,    $szamla -> muszak_szam, $szamla -> musz_dt, $szamla -> kolt_vis );
                    $feldolgozasra_var_stat = P_SZAMLA_STAT_FELDOLGOZASRA_VAR;
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_ENERGIA ) {
                    array_push($mezok,      'megjegyz',             'szev',          'szhonap',             'vontol_dt',            'vonig_dt',          'ktgnem_id',            'ktghely_id',           'elutleir'           );
                    array_push($ertekek,    $szamla -> megjegyz,    $szamla -> szev, $szamla -> szhonap,    $szamla -> vontol_dt,   $szamla -> vonig_dt,  $szamla -> ktgnem_id,  $szamla -> ktghely_id,  $szamla -> elutleir  );
                    $feldolgozasra_var_stat = E_SZAMLA_STAT_FELDOLGOZASRA_VAR;
                }
                if( $szamla -> tipus == self::SZAMLA_TIPUS_ANYAG ) {
                    array_push($mezok,     'kcs_25',            'megjegyz',          'fizmod',          'elutleir',          'rndid'            );
                    array_push($ertekek,   $szamla -> kcs_25,   $szamla -> megjegyz, $szamla -> fizmod, $szamla -> elutleir, $szamla -> rndid   );
                    $feldolgozasra_var_stat = A_SZAMLA_STAT_FELDOLGOZASRA_VAR;
                }

                $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                $aparecid = $aparecid['recid'];
                if( $aparecid === false ) { $db_error = true; }

                // Insert
                if( !$aparecid ) {

                    $return_uzi = 'beszúrva';

                    // Insertnél kell az andoc_id is
                    array_push($mezok,      'andoc_id', 'insert_usr',  'update_usr',   'insert_dt'  );
                    array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER, 'NOW()'      );
                    $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                    if( $aparecid === false ) { $db_error = true; }

                    // A státuszt átírjuk feldolgozásra vár státuszra.
                    // Hiába adtak meg az XML-ben státusz kódot, új beszúráskor fixen ezt írjuk be
                    $sql = $adatbazis_kapcsolat->u($tabla, array('stat_id'), array($feldolgozasra_var_stat), "`recid`='{$aparecid}'");
                    if( $sql === false ) { $db_error = true; }
                    // Illetve beállítjuk kezdőstátusznak is a feldolgzva vár státuszt
                    $sql = $adatbazis_kapcsolat -> i($tabla.'_statusz',
                                                    array($tabla.'_recid',   'stat_id',               'login',          'insert_dt', 'insert_usr',  'update_usr'    ),
                                                    array($aparecid,         $feldolgozasra_var_stat, self::IU_USER,    'NOW()',     self::IU_USER, self::IU_USER   )
                                                );
                    if( $sql === false ) { $db_error = true; }

                // Update
                } else {

                    $return_uzi = 'módosítva';
                    
                    $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = true; }
                    
                    // Státusz léptetéssel dolgozzuk fel a kapott státuszt
                    if( $szamla -> stat_id ) {
                        if( addStatusz($tabla.'_statusz', $aparecid, $szamla -> stat_id ) === false ) {
                            $db_error = true;
                        }
                    }
                    
                }

                // Tételek bejárása
                foreach ( $tetelek as $tetel ) {

                    $andoc_id = $tetel['andoc_id'];

                    // Tételek kötelező mezők ellenőrzése
                    if( !trim($tetel -> netto) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó nettó érték a tételeknél!');
                    }

                    if( $szamla -> tipus == self::SZAMLA_TIPUS_FENNTARTASI) {
                        if( !trim($tetel -> telephely_id) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó szálloda a tételeknél!');
                        }
                        if( !trim($tetel -> targy) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó tárgy a tételeknél!');
                        }
                        if( !trim($tetel -> ktgnem_id) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
                        }
                        if( !trim($tetel -> ktghely_id) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
                        }
                    }
                    if( $szamla -> tipus == self::SZAMLA_TIPUS_PROJEKT) {
                        if( !trim($tetel -> targy) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó tárgy a tételeknél!');
                        }
                    }
                    if( $szamla -> tipus == self::SZAMLA_TIPUS_ANYAG) {
                        if( !trim($tetel -> ktgnem_id) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
                        }
                        if( !trim($tetel -> ktghely_id) ) {
                            Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
                        }
                    }

                    // Tételek beszúrása
                    $mezok   = array(
                                    $apa_recid_oszlop,
                                    'netto_ert',
                                    'dev_net',
                                    'update_usr',
                                );
                    $ertekek = array(
                                    $aparecid,
                                    $tetel -> netto,
                                    $tetel -> dev_net,
                                    self::IU_USER,
                                );

                    // További mezők felvétele, számla típusától függően
                    if( $szamla -> tipus == self::SZAMLA_TIPUS_FENNTARTASI ) {
                        array_push($mezok,      'verzio', 'szszam',     'igazol_usr',         'igazol_dt',         'telephely_id',         'targy',            'ktgnem_id',           'ktghely_id',          'stat_id'         );
                        array_push($ertekek,    $tetel -> verzio,       $tetel -> szszam,   $tetel -> igazol_usr, $tetel -> igazol_dt, $tetel -> telephely_id, $tetel -> targy,    $tetel -> ktgnem_id,   $tetel -> ktghely_id,  $tetel -> stat_id );
                    }
                    if( $szamla -> tipus == self::SZAMLA_TIPUS_PROJEKT) {
                        array_push($mezok,      'targy',            'dev_br'            );
                        array_push($ertekek,    $tetel -> targy,    $tetel -> dev_br    );
                    }
                    if( $szamla -> tipus == self::SZAMLA_TIPUS_ANYAG) {
                        array_push($mezok,      'ktgnem_id',           'ktghely_id',            'cikk_id',           'rndert',           'modert',           'vnrdert',          'vmodert',          'oracikkid',            'kcs_35'            );
                        array_push($ertekek,    $tetel -> ktgnem_id,   $tetel -> ktghely_id,    $tetel -> cikk_id,   $tetel -> rndert,   $tetel -> modert,   $tetel -> vnrdert,   $tetel -> vmodert,   $tetel -> oracikkid,  $tetel -> kcs_35    );
                    }

                    $tetel_letezik = $adatbazis_kapcsolat->s2('recid', $tabla.'_tetel', "`andoc_id`='{$andoc_id}'");
                    $tetel_letezik = $tetel_letezik['recid'];
                    if( $tetel_letezik === false ) { $db_error = true; }

                    // Insert
                    if( !$tetel_letezik ) {

                        // Insertnél kell az andoc_id is
                        array_push($mezok,      'andoc_id', 'insert_usr',   'insert_dt' );
                        array_push($ertekek,    $andoc_id,  self::IU_USER,  'NOW()'     );
                        $sql = $adatbazis_kapcsolat->i($tabla.'_tetel', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }

                    // Update
                    } else {

                        $sql = $adatbazis_kapcsolat->u($tabla.'_tetel', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                        if( $sql === false ) { $db_error = true; }

                    }
    
                }

                // Kifizetések bejárása
                foreach ( $kifizetesek as $kifizetes ) {

                    $andoc_id = $kifizetes['andoc_id'];

                    // Kifizetések beszúrása
                    $mezok   = array(
                                    $apa_recid_oszlop,
                                    'szlakifid',
                                    'szla_szam',
                                    'szallito_id',
                                    'bank',
                                    'kifizetes_dt',
                                    'br_ertek',
                                    'rogzitve_dt',
                                    'rogzitette',
                                    'dev_br',
                                    'kcs_21',
                                    'loginnev',
                                    'update_usr',
                                );
                    $ertekek = array(
                                    $aparecid,
                                    $kifizetes -> szlakifid,
                                    $kifizetes -> szla_szam,
                                    $szallito_id,
                                    $kifizetes -> bank,
                                    $kifizetes -> kifizetes_dt,
                                    $kifizetes -> br_ertek,
                                    $kifizetes -> rogzitve_dt,
                                    $kifizetes -> rogzitette,
                                    $kifizetes -> dev_br,
                                    $kifizetes -> kcs_21,
                                    self::IU_USER,
                                    self::IU_USER,
                                );

                    $kifizetes_letezik = $adatbazis_kapcsolat->s2('recid', $tabla.'_kifizetesek', "`andoc_id`='{$andoc_id}'");
                    $kifizetes_letezik = $kifizetes_letezik['recid'];
                    if( $kifizetes_letezik === false ) { $db_error = true; }

                    // Insert
                    if( !$kifizetes_letezik ) {

                        // Insertnél kell az andoc_id is
                        array_push($mezok,      'andoc_id', 'insert_usr',   'insert_dt' );
                        array_push($ertekek,    $andoc_id,  self::IU_USER,  'NOW()'     );
                        $sql = $adatbazis_kapcsolat->i($tabla.'_kifizetesek', $mezok, $ertekek);
                        if( $sql === false ) { $db_error = true; }

                    // Update
                    } else {

                        $sql = $adatbazis_kapcsolat->u($tabla.'_kifizetesek', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                        if( $sql === false ) { $db_error = true; }

                    }
    
                }
            }

            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A számla, és a tételei, kifizetései sikeresen '.$return_uzi.'! ID: '.$szamla_andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                return 'A számla feldolgozása közben ismeretlen hiba történt: ID: '.$szamla_andoc_id;
            }
        }
        
    }
    
    // Szerződés importálása
    public function SzerzodesAdatokImport( $szerzodes_xml ) {

        global $adatbazis_kapcsolat;
        global $db_kapcs;

        $szerzodesek = simplexml_load_string($szerzodes_xml, 'SimpleXMLElement');
        foreach ( $szerzodesek as $szerzodes ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);

            // Kötelező mezők, Insert, Update és Delete művelet esetén is
            if( !$szerzodes -> tipus ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés típus!');
            }
            if(  !trim($szerzodes['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id = $szerzodes['andoc_id'];
            
            switch( $szerzodes -> tipus ) {
                case self::SZERZ_TIPUS_FENNTARTASI     : $tabla = 'fenntartasi_szerzodes';    break;
                case self::SZERZ_TIPUS_PROJEKT         : $tabla = 'projektszerzodes';         break;
            }
            $apa_recid_oszlop = $tabla.'_recid';

            // Törléshez ismernünk kell az andoc_id-t és a számla típusát
            // Delete
            if( $szerzodes -> torles == 1 ) {
                
                $sql = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                if( !$sql ) {
                    Throw new SOAPFault ('Server', 'Nem ismert Andoc azonosító. A törlés nem sikerült!');
                }
                
                $return_uzi = 'törölve';
                
                $sql = $adatbazis_kapcsolat->d($tabla, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
                $sql = $adatbazis_kapcsolat->d($tabla.'_tetel', "`{$apa_recid_oszlop}`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
            
            // Insert/update
            } else {

                // Kötelező mezők ellenőrzése, szerződés típustól függetlenül
                if( !trim($szerzodes -> sz_szam) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szerződés szám!');
                }
                if( !trim($szerzodes -> kelte_dt) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó keltezés!');
                }
                if( !trim($szerzodes -> verzio) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó verzió!');
                }
                if( !trim($szerzodes -> targy) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó tárgy!');
                }
                if( !trim($szerzodes -> kcs_21) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
                }
                // Ha olyan értéket kapnánk, mint amilyen értékű pénznem nem is létezik, akkor hibát dobunk
                $penznem = $adatbazis_kapcsolat->s2('recid', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `id`='{$szerzodes -> kcs_21}'");
                if( !$penznem ) {
                    Throw new SOAPFault ('Server', 'Nem ismert pénznem!');
                }
                if( !trim($szerzodes -> netto) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó nettó érték!');
                }
                // Ha nem HUF-os a számla, akkor pár mező még kötelezővé válik, mindkét szerződésnél
                if( $szerzodes -> kcs_21 != self::KCS_21_HUF ) {

                    if( !trim($szerzodes -> dev_net) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó deviza nettó érték!');
                    }
                    if( !trim($szerzodes -> arfolyam) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó árfolyam!');
                    }

                }

                // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól föggően, hogy az Andok azonsoító ismert-e.
                // Ehhez a követekző lépések: az aktuális szerződéstípusokra vonatkozó kötelező mezők ellenőrzése, majd a mező/érték párokat beállítása.
                // Fenntartási szerződéssel kezdjük:
                if( $szerzodes -> tipus == self::SZERZ_TIPUS_FENNTARTASI ) {
                    // Csak a fenntartási szerződésre vonatkozó ellenőrzések
                    if( !trim($szerzodes -> erv_dt) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó érvényesség kezdete!');
                    }
                    if( !trim($szerzodes -> alairva_dt) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó aláírva!');
                    }
                    if( !trim($szerzodes -> hatalyos) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó hatályos!');
                    }
                    
                    // szallito_id esetén fontos, hogy meglegyenek a keződ 0-k
                    $szallito_id = sprintf('%05d', $szerzodes -> szallito_id);

                    $mezok = array(
                                    'szszam',
                                    'kelte_dt',
                                    'verzio',
                                    'sztipus',
                                    'szallito_id',
                                    'targy',
                                    'erv_dt',
                                    'bef_dt',
                                    'felmondas',
                                    'netto',
                                    'vnetert',
                                    'p_nev',
                                    'p_beosztas',
                                    'p_cim',
                                    'p_email',
                                    'p_tel',
                                    'p_fax',
                                    'alairva_dt',
                                    'szlafizhi',
                                    'hatalyos',
                                    'kcs_21',
                                    'megjegyz',
                                );
                    $ertekek = array(
                                    $szerzodes -> sz_szam,
                                    $szerzodes -> kelte_dt,
                                    $szerzodes -> verzio,
                                    $szerzodes -> sztipus,
                                    $szallito_id,
                                    $szerzodes -> targy,
                                    $szerzodes -> erv_dt,
                                    $szerzodes -> bef_dt,
                                    $szerzodes -> felmondas,
                                    $szerzodes -> netto,
                                    $szerzodes -> dev_net,
                                    $szerzodes -> p_nev,
                                    $szerzodes -> p_beosztas,
                                    $szerzodes -> p_cim,
                                    $szerzodes -> p_email,
                                    $szerzodes -> p_tel,
                                    $szerzodes -> p_fax,
                                    $szerzodes -> alairva_dt,
                                    $szerzodes -> szlafizhi,
                                    $szerzodes -> hatalyos,
                                    $szerzodes -> kcs_21,
                                    $szerzodes -> megjegyz,
                                );

                // Projektszerződés feldolgozása
                } else if( $szerzodes -> tipus == self::SZERZ_TIPUS_PROJEKT ) {

                    // Csak a projektszerződésre vonatkozó kötelező mezők ellenőrzése
                    if( !trim($szerzodes -> kelte_dt) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó kelte!');
                    }
                    if( !trim($szerzodes -> pr_szam) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó projektszám!');
                    }
                    if( !trim($szerzodes -> telephely_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó telephely!');
                    }
                    if( !trim($szerzodes -> szallito_id) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó partner!');
                    }
                    if( !trim($szerzodes -> epitesi_eng) && !trim($szerzodes -> epitesi) ) {
                        Throw new SOAPFault ('Server', 'Hiányzó építési összeg!');
                    }
                    
                    // szallito_id esetén fontos, hogy meglegyenek a keződ 0-k
                    $szallito_id = sprintf('%05d', $szerzodes -> szallito_id);

                    $mezok = array(
                                    'telephely_id',
                                    'sz_szam',
                                    'pr_szam',
                                    'kelte_dt',
                                    'szallito_id',
                                    'targy',
                                    'kolt_vis',
                                    'sz_koto',
                                    'kcs_21',
                                    'dev_net',
                                    'arfolyam',
                                    'atvaltva_dt',
                                    'kiv_kezd',
                                    'kiv_vege',
                                    'szerz_ossz',
                                    'epitesi_eng',
                                    'epitesi',
                                    'muszak_atv',
                                    'muszaki_gar_kezd',
                                    'muszaki_gar_vege',
                                    'bank_gar',
                                    'bank_gar_vege',
                                );
                    $ertekek = array(
                                    $szerzodes -> telephely_id,
                                    $szerzodes -> sz_szam,
                                    $szerzodes -> pr_szam,
                                    $szerzodes -> kelte_dt,
                                    $szallito_id,
                                    $szerzodes -> targy,
                                    $szerzodes -> kolt_vis,
                                    $szerzodes -> sz_koto,
                                    $szerzodes -> kcs_21,
                                    $szerzodes -> dev_net,
                                    $szerzodes -> arfolyam,
                                    $szerzodes -> atvaltva_dt,
                                    $szerzodes -> kiv_kezd,
                                    $szerzodes -> kiv_vege,
                                    $szerzodes -> netto,
                                    $szerzodes -> epitesi_eng,
                                    $szerzodes -> epitesi,
                                    $szerzodes -> muszak_atv,
                                    $szerzodes -> muszaki_gar_kezd,
                                    $szerzodes -> muszaki_gar_vege,
                                    $szerzodes -> bank_gar,
                                    $szerzodes -> bank_gar_vege,
                                );

                }

                $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
                $aparecid = $aparecid['recid'];
                if( $aparecid === false ) { $db_error = true; }

                // Insert
                if( !$aparecid ) {
                    
                    // Insert esetén a foglalt szerződés számot ellenőrizni kell
                    if( $adatbazis_kapcsolat->s2('id', $tabla, "`sz_szam`='{$szerzodes -> sz_szam}'") ) {
                        Throw new SOAPFault ('Server', 'Ilyen számú szerződés már létezik, így nem hozható létre újra!');
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
// return $adatbazis_kapcsolat->gp() . '<br>';
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A szerződés sikeresen '.$return_uzi.'! ID: '.$andoc_id;
            } else {
                mysqli_rollback($db_kapcs);
                return 'A szerződés feldolgozása közben ismeretlen hiba történt: ID: '.$andoc_id;
            }
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
