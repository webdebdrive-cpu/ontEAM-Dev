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
    public function SzamlaAdatokImport( $xml ) {
        
        global $adatbazis_kapcsolat;
        global $db_kapcs;
        
        $db_error           = false;
        $elfogadott_tipusok = array('anyag', 'energia', 'fenntartasi');
        
        $szamlak = simplexml_load_string($xml, 'SimpleXMLElement');

        foreach ( $szamlak as $szamla ) {

            // Tranzakciót indítunk
            $db_error = false;
            mysqli_autocommit($db_kapcs, false);
            
            // Kötelező mezők ellenőrzése
            if(  trim($szamla['registernumber']) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó Andoc azonosító!');
            }
            $andoc_azon = $szamla['registernumber'];
            
            if( !in_array($szamla -> tipus, $elfogadott_tipusok) ) {
                Throw new SOAPFault ('Server', 'Helytelen tipus!');
            }
            if( trim($szamla -> ikt_szam) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó iktatószám!');
            }
            if( !valos_datum_e($szamla -> erk_d) ) {
                Throw new SOAPFault ('Server', 'Helytelen érkezés dátum!');
            }
            if( trim($szamla -> szlaszam) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó számlaszám!');
            }
            if( trim($szamla -> szalloda) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó szálloda!');
            }
            if( trim($szamla -> szallito) == '' ) {
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
            if( trim($szamla -> penznem) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó pénznem!');
            }
            if( trim($szamla -> statusz) == '' ) {
                Throw new SOAPFault ('Server', 'Hiányzó státusz!');
            }
            
            // Visszafejtendő adatok
            $telephely = $adatbazis_kapcsolat->s2('id', 'telephely', "`nev`='{$szamla -> szalloda}'");
            if( !$telephely ) {
                Throw new SOAPFault ('Server', 'Nem ismert szálloda név!');
            }
            $szallito = $adatbazis_kapcsolat->s2('id', 'szallito', "`nev`='{$szamla -> szallito}'");
            if( !$szallito ) {
                Throw new SOAPFault ('Server', 'Nem ismert szállító név!');
            }
            $penznem = $adatbazis_kapcsolat->s2('id', 'erteklista', "`csoport`='".ERTEKLISTA_PENZNEM."' AND `info`='{$szamla -> penznem}'");
            if( !$penznem ) {
                Throw new SOAPFault ('Server', 'Nem ismert pénznem!');
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
                            'ikt_szam',
                            'erk_d',
                            'szlaszam',
                            'telephely_id',
                            'szallito_id',
                            'telj_d',
                            'fizhat_d',
                            'fizmod',
                            'targy',
                            'brutert',
                            'netert',
                            'vbrutert',
                            'vnetert',
                            'kcs_21',
                            'stat_id',
                            'elutleir',
                            'atvaltva_d',
                        );
            $ertekek = array(
                            $szamla -> ikt_szam,
                            $szamla -> erk_d,
                            $szamla -> szlaszam,
                            $telephely['id'],
                            $szallito['id'],
                            $szamla -> telj_d,
                            $szamla -> fizhat_d,
                            $szamla -> fizmod,
                            $szamla -> targy,
                            $szamla -> brutert,
                            $szamla -> netert,
                            $szamla -> vbrutert,
                            $szamla -> vnetert,
                            $penznem['id'],
                            $szamla -> stat_id,
                            $szamla -> elutleir, 
                            $szamla -> atvaltva_d,
                        );
            
            $aparecid = $adatbazis_kapcsolat->s2('recid', $tabla, "`andoc_azon`='{$andoc_azon}'");
            $aparecid = $aparecid['recid'];

            if( $aparecid === false ) { $db_error = true; }
            
            // Insert
            if( !$aparecid ) {

                // Insertnél kell az andoc_azon is
                array_push($mezok,      'andoc_azon'    );
                array_push($ertekek,    $andoc_azon     );
                $aparecid = $adatbazis_kapcsolat->i($tabla, $mezok, $ertekek);
                if( $aparecid === false ) { $db_error = true; }
                $return_uzi    = 'beszúrva';
                $insert_update = 'insert';
                
            // Update
            } else {
                
                $sql = $adatbazis_kapcsolat->u($tabla, $mezok, $ertekek, "`andoc_azon`='{$andoc_azon}'");
                if( $aparecid === false ) { $db_error = true; }
                $return_uzi     = 'frissítve';
                $insert_update  = 'update';
                
            }
            
            // Ha update-ről van szó, akkor minden tételt törlünk a tételek bejárása előtt, hogy újra létre lehessen hozni
            if( $insert_update == 'update' ) {
                $sql = $adatbazis_kapcsolat->d($tabla.'_tetel', "`aparecid` = '{$aparecid}'");
                if( $sql === false ) { $db_error = true; }
            }
            // Tételek bejárása
            foreach ( $tetelek as $tetel ) {
                
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

                // Visszafejtendő adatok
                $telephely = $adatbazis_kapcsolat->s2('id', 'telephely', "`nev`='{$tetel -> szalloda}'");
                if( !$telephely ) {
                    Throw new SOAPFault ('Server', 'Nem ismert szálloda név a tételeknél!');
                }
                $ktgnem = $adatbazis_kapcsolat->s2('recid', 'ktgnem', "`nev`='{$tetel -> ktgnem}'");
                if( $ktgnem === false ) { $db_error = true; }
                if( !$ktgnem['recid'] ) {
                    Throw new SOAPFault ('Server', 'Nem ismert költségnem a tételeknél!');
                }
                $ktghely = $adatbazis_kapcsolat->s2('recid', 'ktghely', "`nev`='{$tetel -> ktghely}'");
                if( $ktghely === false ) { $db_error = true; }
                if( !$ktghely['recid'] ) {
                    Throw new SOAPFault ('Server', 'Nem ismert költséghely a tételeknél!');
                }
                
                // Tételek beszúrása
                $mezok   = array(
                                'aparecid',
                                'telephely_id',
                                'targy',
                                'ktgnemid',
                                'netert',
                                'vnetert',
                                'stat_id',
                                'ktghelyid',
                                'igazol_usr',
                                'igazol_dt',
                                'szszam',
                                'verzio',
                                'sorid',
                            );
                $ertekek = array(
                                $aparecid,
                                $telephely['id'],
                                $tetel -> targy,
                                $ktgnem['recid'],
                                $tetel -> netert,
                                $tetel -> vnetert,
                                $tetel -> stat_id,
                                $ktghely['recid'],
                                $tetel -> igazol_usr,
                                $tetel -> igazol_dt,
                                $tetel -> szszam,
                                $tetel -> verzio,
                                $tetel -> sorid,
                            );
                $sql = $adatbazis_kapcsolat->i($tabla.'_tetel', $mezok, $ertekek);
                if( $sql === false ) { $db_error = true; }
                
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
}
