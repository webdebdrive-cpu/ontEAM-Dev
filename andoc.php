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
        
        $elfogadott_tipusok = array('anyag', 'energia', 'fenntartasi');

        $szamlak = simplexml_load_string($szamla_xml, 'SimpleXMLElement');

        foreach ( $szamlak as $szamla ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);
            
            // Kötelező mezők ellenőrzése
            if(  !trim($szamla['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id = $szamla['andoc_id'];
            
            if( !in_array($szamla -> tipus, $elfogadott_tipusok) ) {
                Throw new SOAPFault ('Server', 'Helytelen tipus!');
            }
            if( !trim($szamla -> ikt_szam) ) {
                Throw new SOAPFault ('Server', 'Hiányzó iktatószám!');
            }
            if( !valos_datum_e($szamla -> erk_d) ) {
                Throw new SOAPFault ('Server', 'Helytelen érkezés dátum!');
            }
            if( !trim($szamla -> szlaszam) ) {
                Throw new SOAPFault ('Server', 'Hiányzó számlaszám!');
            }
            if( trim($szamla -> szalloda) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó szálloda!');
            }
            if( !trim($szamla -> szallito) ) {
                Throw new SOAPFault ('Server', 'Hiányzó szállító!');
            }
            if( !valos_datum_e($szamla -> telj_d) ) {
                Throw new SOAPFault ('Server', 'Helytelen teljesítés dátum!');
            }
            if( !valos_datum_e($szamla -> fizhat_d) ) {
                Throw new SOAPFault ('Server', 'Helytelen fizetési határidő!');
            }
            if( trim($szamla -> fizmod) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó fizetési mód!');
            }
            if( trim($szamla -> netert) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó nettó érték!');
            }
            if( trim($szamla -> brutert) == '' ) {
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
            if( trim($szamla -> statusz) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó státusz!');
            }

            // Tételek
            $tetelek = $szamla -> tetel;
            if( !count($tetelek) ) {
                Throw new SOAPFault ('Server', 'Hiányzó tételek!');
            }
            
            // Melyik adatbázis táblával kell dolgozni?
            switch( $szamla -> tipus ) {
                case 'anyag'        : $tabla = 'anyag_szamla';          break;
                case 'energia'      : $tabla = 'energia_szamla';        break;
                case 'fenntartasi'  : $tabla = 'fenntartasi_szamla';    break;
            }

            // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól föggően, hogy az Andok azonsoító ismert-e.
            // Ehhez az első lépés, a mező/érték párokat beállítani.
            $mezok = array(
                            'erk_d',
                            // 'kelte',
                            // 'muszak_szam',
                            // 'musz_dt',
                            'szla_szam',
                            'ikt_szam',
                            'telj_d',
                            'fizhat_d',
                            'telephely_id',
                            'szallito_id',
                            'targy',
                            'kcs_21',
                            'atvaltva_d',
                            'vnetert',
                            'vbrutert',
                            'brutert',
                            'netert',
                            // 'nullas',
                            // 'visszaadva',
                            // 'visszaadva_dt',
                            'fizmod',
                            'elutleir',
                            'stat_id',
                            // 'megjegyz',
                            // 'szev',
                            // 'szhonap',
                            // 'vontol_d',
                            // 'vonig_d',
                            // 'ktgnemid',
                            // 'ktghelyid',
                            // 'kolt_vis',
                        );
            $ertekek = array(
                            $szamla -> erk_d,
                            // $szamla -> kelte,
                            // $szamla -> muszak_szam,
                            // $szamla -> musz_d,
                            $szamla -> szla_szam,
                            $szamla -> ikt_szam,
                            $szamla -> telj_d,
                            $szamla -> fizhat_d,
                            $szamla -> telephely_id,
                            $szamla -> szallito_id,
                            $szamla -> targy,
                            $szamla -> kcs_21,
                            $szamla -> atvaltva_d,
                            $szamla -> vbrutert,
                            $szamla -> vnetert,
                            $szamla -> netert,
                            $szamla -> brutert,
                            // $szamla -> nullas,
                            // $szamla -> visszaadva,
                            // $szamla -> visszaadva_dt,
                            $szamla -> fizmod,
                            $szamla -> elutleir, 
                            $szamla -> stat_id,
                            // $szamla -> megjegyz,
                            // $szamla -> szev,
                            // $szamla -> szhonap,
                            // $szamla -> vontol_d,
                            // $szamla -> vonig_d,
                            // $szamla -> ktgnemid,
                            // $szamla -> ktghelyid,
                            // $szamla -> kolt_vis,
                        );
            
            $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_id`='{$andoc_id}'");
            $aparecid = $aparecid['recid'];
            if( $aparecid === false ) { $db_error = true; }

            // Insert
            if( !$aparecid ) {

                // Insertnél kell az andoc_id is
                array_push($mezok,      'andoc_id'    );
                array_push($ertekek,    $andoc_id     );
                $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                if( $aparecid === false ) { $db_error = true; }
                $return_uzi    = 'beszúrva';
                $insert_update = 'insert';
                
            // Update
            } else {
                
                $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
                $return_uzi     = 'frissítve';
                $insert_update  = 'update';
            }

            // Tételek bejárása
            foreach ( $tetelek as $tetel ) {
                
                $andoc_id = $tetel['andoc_id'];
                
                // Kötelező mezők ellenőrzése
                if( trim($tetel -> szalloda) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szálloda a tételeknél!');
                }
                if( trim($tetel -> ktgnem) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó költségnem a tételeknél!');
                }
                if( trim($tetel -> netert) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó nettó érték a tételeknél!');
                }
                if( trim($tetel -> statusz) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó státusz a tételeknél!');
                }
                if( trim($tetel -> ktghely) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó költséghely a tételeknél!');
                }
                if( trim($tetel -> szszam) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó szerződés szám a tételeknél!');
                }
                if( trim($tetel -> verzio) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó verzió a tételeknél!');
                }
                if( trim($tetel -> sorid) == '' ) {
                    Throw new SOAPFault ('Server', 'Hiányzó sorszám a tételeknél!');
                }
                // Ha van igazolás dátum, de nem helyes a formátuma
                if( trim($tetel -> igazol_dt) != '' && !valos_datum_e($tetel -> igazol_dt) ) {
                    Throw new SOAPFault ('Server', 'Helytelen igazolás dátum a tételeknél!');
                }
                
                // Tételek beszúrása
                $mezok   = array(
                                'aparecid',
                                'telephely_id',
                                'targy',
                                'ktgnemid',
                                'ktghelyid',
                                'netert',
                                'vnetert',
                                'stat_id',
                                'igazol_usr',
                                'igazol_dt',
                                'szszam',
                                'verzio',
                                // 'bank',
                                // 'kifizetes',
                                // 'brertek',
                                // 'vbrutert',
                                // 'kcs_21',
                                // 'rogzitve',
                                // 'rogzitette',
                                // 'cikkid',
                                // 'oracikkid',
                                // 'rndert',
                                // 'modert',
                                // 'vnrdert',
                                // 'vmodert',
                                // 'kcs_35',
                            );
                $ertekek = array(
                                $aparecid,
                                $tetel -> telephely_id,
                                $tetel -> targy,
                                $tetel -> ktgnem,
                                $tetel -> ktghely,
                                $tetel -> netert,
                                $tetel -> vnetert,
                                $tetel -> stat_id,
                                $tetel -> igazol_usr,
                                $tetel -> igazol_dt,
                                $tetel -> szszam,
                                $tetel -> verzio,
                                // $tetel -> bank,
                                // $tetel -> kifizetes,
                                // $tetel -> brertek,
                                // $tetel -> vbrutert,
                                // $tetel -> kcs_21,
                                // $tetel -> rogzitve,
                                // $tetel -> rogzitette,
                                // $tetel -> cikkid,
                                // $tetel -> oracikkid,
                                // $tetel -> rndert,
                                // $tetel -> modert,
                                // $tetel -> vnrdert,
                                // $tetel -> vmodert,
                                // $tetel -> kcs_35,
                            );
                
                $tetel_letezik = $adatbazis_kapcsolat->s2('recid', $tabla.'_tetel', "`andoc_id`='{$andoc_id}'");
                $tetel_letezik = $tetel_letezik['recid'];
                if( $tetel_letezik === false ) { $db_error = true; }

                // Insert
                if( !$tetel_letezik ) {

                    // Insertnél kell az andoc_id is
                    array_push($mezok,      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
                    array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
                    $sql = $adatbazis_kapcsolat->i($tabla.'_tetel', $mezok, $ertekek);
                    if( $sql === false ) { $db_error = true; }

                // Update
                } else {

                    array_push($mezok,      'update_usr'    );
                    array_push($ertekek,    self::IU_USER   );
                    $sql = $adatbazis_kapcsolat->u($tabla.'_tetel', $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                    if( $sql === false ) { $db_error = true; }

                }
                
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A számla és tételei sikeresen '.$return_uzi.'!';
            } else {
                mysqli_rollback($db_kapcs);
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

            // Általános, kötelező mezők ellenőrzése
            if( !$szerzodes -> szerztipus ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés típus!');
            }
            if(  !trim($szerzodes['andoc_id']) ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_id = $szerzodes['andoc_id'];
            
            if( !trim($szerzodes -> sz_szam) ) {
                Throw new SOAPFault ('Server', 'Hiányzó szerződés szám!');
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
            // Ha nem HUF-os a számla, akkor pár mező még kötelezővé válik
            if( $szerzodes -> kcs_21 != self::KCS_21_HUF ) {
                
                if( !trim($szerzodes -> dev_net) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó deviza nettó érték!');
                }
                if( !trim($szerzodes -> arfolyam) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó árfolyam!');
                }
                
            }

            // Ha az alapvető adatokon nem akadtunk fenn, akkor szúrjuk be vagy updateljük a számlát, attól föggően, hogy az Andok azonsoító ismert-e.
            // Ehhez az első lépés, a mező/érték párokat beállítani.
            // Fenntartási szerződéssel kezdjük
            if( $szerzodes -> szerztipus == self::SZERZ_TIPUS_FENNTARTASI ) {

                // Csak a fenntartási szerződésre vonatkozó ellenőrzések
                if( !trim($szerzodes -> erv_d) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó érvényesség kezdete!');
                }
                if( !trim($szerzodes -> alairva_d) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó aláírva!');
                }
                if( !trim($szerzodes -> hatalyos) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó hatályos!');
                }
                
                $tabla = 'fenntartasi_szerzodes';
                
                $mezok = array(
                                'szszam',
                                'verzio',
                                'sztipus',
                                'szallito_id',
                                'targy',
                                'erv_d',
                                'bef_d',
                                'felmondas',
                                'netert',
                                'p_nev',
                                'p_beosztas',
                                'p_cim',
                                'p_email',
                                'p_tel',
                                'p_fax',
                                'alairva_d',
                                'vnetert',
                                'szlafizhi',
                                'hatalyos',
                                'kcs_21',
                                'megjegyz',
                            );
                $ertekek = array(
                                $szerzodes -> sz_szam,
                                $szerzodes -> verzio,
                                $szerzodes -> sztipus,
                                $szerzodes -> szallito_id,
                                $szerzodes -> targy,
                                $szerzodes -> erv_d,
                                $szerzodes -> bef_d,
                                $szerzodes -> felmondas,
                                $szerzodes -> netto,
                                $szerzodes -> p_nev,
                                $szerzodes -> p_beosztas,
                                $szerzodes -> p_cim,
                                $szerzodes -> p_email,
                                $szerzodes -> p_tel,
                                $szerzodes -> p_fax,
                                $szerzodes -> alairva_d,
                                $szerzodes -> dev_net,
                                $szerzodes -> szlafizhi,
                                $szerzodes -> hatalyos,
                                $szerzodes -> kcs_21,
                                $szerzodes -> megjegyz,
                            );
                
            // Ha projektszerződés
            } else if( $szerzodes -> szerztipus == self::SZERZ_TIPUS_PROJEKT ) {
                
                // Csak a projektszerződésre vonatkozó ellenőrzések
                if( !trim($szerzodes -> telephely_id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó telephely!');
                }
                if( !trim($szerzodes -> kelte) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó kelte!');
                }
                if( !trim($szerzodes -> szallito_id) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó partner!');
                }
                if( !trim($szerzodes -> epitesi_eng) && !trim($szerzodes -> epitesi) ) {
                    Throw new SOAPFault ('Server', 'Hiányzó építési összeg!');
                }
                
                $tabla = 'projektszerzodes';
                
                $mezok = array(
                                'telephely_id',
                                'sz_szam',
                                'kelte',
                                'szallito_id',
                                'targy',
                                'kolt_vis',
                                'sz_koto',
                                'kcs_21',
                                'dev_net',
                                'arfolyam',
                                'valtva',
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
                                $szerzodes -> kelte,
                                $szerzodes -> szallito_id,
                                $szerzodes -> targy,
                                $szerzodes -> kolt_vis,
                                $szerzodes -> sz_koto,
                                $szerzodes -> kcs_21,
                                $szerzodes -> dev_net,
                                $szerzodes -> arfolyam,
                                $szerzodes -> valtva,
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

                // Insertnél kell az andoc_id is
                array_push($mezok,      'andoc_id', 'update_usr',   'insert_usr',   'insert_dt' );
                array_push($ertekek,    $andoc_id,  self::IU_USER,  self::IU_USER,  'NOW()'     );
                $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                if( $aparecid === false ) { $db_error = true; }
                $return_uzi    = 'beszúrva';
                $insert_update = 'insert';
                
            // Update
            } else {
                
                array_push($mezok,      'update_usr'    );
                array_push($ertekek,    self::IU_USER   );
                
                $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_id`='{$andoc_id}'");
                if( $sql === false ) { $db_error = true; }
                $return_uzi     = 'frissítve';
                $insert_update  = 'update';
                
            }
            
            // Tranzakció kommitolása, vagy rollback-ezése
            if( !$db_error ) {
                mysqli_commit($db_kapcs);
                return 'A szerződés sikeresen '.$return_uzi.'!' . $adatbazis_kapcsolat->gp();
            } else {
                mysqli_rollback($db_kapcs);
            }
        }
        
    }
}
