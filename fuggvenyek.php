<?php
/*                                                                                                                          *\
    Ide írd meg a saját, globális függvényeidet. Mivel ez a header() előtt meghívódik, kimenetele a fájlnak ne legyen.
\*                                                                                                                          */







/**
*	Insert & Update
*
*	@param string $type: 'insert' vagy 'update'
*	@param string $tabla: a beszúrandó tábla neve 'elotag_' nélkül
*	@param array $tomb: beszúrandó étékek. Vigyázat, egyeznie kell a megfelelő szerkezettel:
*	name: $POST[name] (tehát érték) és az adatbázis tábla neve
*	alert:  ha nem üres akkor visszaadja a sorsámot, hogy hol akadt el
*
*	@return array: első része a $errnum második pedig a beszúrt vagy módosított sor id-je
*/
function i_u($type, $tabla, $tomb){
	global $adatbazis_kapcsolat;
	global $db_kapcs;

    //Tranzakció indul
    $db_error = false;
    mysqli_autocommit($db_kapcs, false);

    $nevid   = '';

    $oszlopok = array();
    $ertekek  = array();

	$i = 0;
	foreach($tomb as $ertek) { //értékek párosítása

		//Ha alert-es egy mező, és a post-ja üres, akkor hiba! (nem adott meg egy kötelező mezőt)
        if($ertek['alert'] != '' && $_POST[$ertek['name']] == '' && !$ertek['disabled']) {
			return array($i, '');
			break;
		}

        //Ha az adott esemenyek.php tomb sora tartalmaz nevid jelzést, akkor ezt a sort eltároljuk, mert ennek a nevéből gyártuk névid-t
        if($ertek['nevid'] == true) {
			$nevid = $ertek;
		}

		//Wysiwyg útvonalszépítés a feltöltött képekre, HA van egyáltalán fájlfeltöltés
		if($ertek['wysiwyg'] && $ertek['feltoltes']) {
            $_POST[$ertek['name']] = str_replace('wysiwyg/ckfinder/core/connector/php/../../../../../', '', $_POST[$ertek['name']]);
        }

        //Ha felhasználó adatlapját módosítjuk, és van megadva jelszó, akkor lecseréljük a mostanit
        if($tabla=='felh' && $ertek['name']=='reg_jelsz' && $_POST[$ertek['name']]) {
            $_POST[$ertek['name']] = sha1($_POST[$ertek['name']]); //sha1 helyett más elkódolást is használhatnánk, de ez javasolt. A lényeg, hogy legyen összhangban a class.Felhasznalo konfigjával
        }

        //Ha van datepicker class-ja, akkor dátum, és a pontokat kötőjelre cseréljük
        if(strpos($ertek['class'], 'datepicker')!==false){
            $_POST[$ertek['name']] = str_replace('.', '-', $_POST[$ertek['name']]);
        }

		//Ha nem gomb, nem fájl és nem disabled és az oszlopnév sem üres és maga a POST egyáltalán létezik is (ne nullázzunk felül el nem küldött POST miatt)
		if($ertek['type'] != 'submit' && $ertek['type'] != 'button' && $ertek['type'] != 'file' && $ertek['type'] != 'flashfile' && $ertek['type'] != 'jsfile' && !($ertek['disabled'] && $type == 'update'/*Tiltott mezőket beszúrni lehet csak moddolni nem*/) && $ertek['name'] && isset($_POST[$ertek['name']]) ) {
			array_push($oszlopok, $ertek['name']);
			array_push($ertekek, trim($_POST[$ertek['name']]));
		}

		$i++;
	}

    //Dátumra ütmezés, ha az ütemezett feldaatokat módosítjuk/töltjük fel
    if( $tabla == 'utemezett_feladatok' ) {

        if($_POST['cikltip']=='l'){
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  ''                );
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  ''                );
        }
        //Napi cilus, X naponként ismétlődik
        if($_POST['ciklnap']){
            array_push($oszlopok, 'ciklaltip'       );
            array_push($ertekek,  '1'               );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklnap'] );

        }
        //Napi ciklus, hétköznaponként isméltődik
        if($_POST['hetkoz_kent']){
            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  '2'           );

            array_push($oszlopok, 'ciklszorz'   );
            array_push($ertekek,  '0'           );
        }

        //Havi ciklus x havonta ismétlődik
        if($_POST['ciklho']){
            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  '1'           );

            array_push($oszlopok, 'ciklszorz'      );
            array_push($ertekek,  $_POST['ciklho'] );

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $_POST['honapjan'];

        }

        if($_POST['ciklho2']){
            array_push($oszlopok, 'ciklaltip'               );
            array_push($ertekek,  '2'                       );

            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ciklho2'] );
        }

        if($_POST['ism_het']){
            array_push($oszlopok, 'ciklszorz'       );
            array_push($ertekek,  $_POST['ism_het'] );
            $cikl_nap = '';
            if($type == 'insert') {
                if($_POST['h']) { $cikl_nap .= '1'; }
                if($_POST['k']) { $cikl_nap .= '2'; }
                if($_POST['s']) { $cikl_nap .= '3'; }
                if($_POST['cs']){ $cikl_nap .= '4'; }
                if($_POST['p']) { $cikl_nap .= '5'; }
                if($_POST['sz']){ $cikl_nap .= '6'; }
                if($_POST['v']) { $cikl_nap .= '7'; }
            }else{
                if($_POST['h']=='true') { $cikl_nap .= '1'; }
                if($_POST['k']=='true') { $cikl_nap .= '2'; }
                if($_POST['s']=='true') { $cikl_nap .= '3'; }
                if($_POST['cs']=='true'){ $cikl_nap .= '4'; }
                if($_POST['p']=='true') { $cikl_nap .= '5'; }
                if($_POST['sz']=='true'){ $cikl_nap .= '6'; }
                if($_POST['v']=='true') { $cikl_nap .= '7'; }
            }

            $index = array_search('ciklnap', $oszlopok);
            $ertekek[$index] = $cikl_nap;

            array_push($oszlopok, 'ciklaltip'   );
            array_push($ertekek,  ''            );
        }


        //Úgy általánosságban még hozzáadjuk a kezdho, kezdnap, vegeho, vegenap értékeket
        array_push($oszlopok, 'kezdho',        'kezdnap',           'vegeho',           'vegenap',          'aktkezd_d',            'aktvege_d'           );
        array_push($ertekek, $_POST['kezdho'], $_POST['kezdnap'],   $_POST['vegeho'],   $_POST['vegenap'],  $_POST['aktkezd_d'],    $_POST['aktvege_d']   );
    }

	if($type == 'insert') {

        /* Datetime-ot is frissítjük */
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'insert_dt',          'insert_usr'                          );
            array_push($ertekek,   date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev']);
        }

        //Anyagigénylés
        if( $tabla == 'anyagigenyles' ) {

            /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
            $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', 'tabla='.$tabla.' and ev='.date('y'), '', '');
            if( $ikt_seq === false ) {
                $db_error = true;
            }
            if( $ikt_seq ) {
                $ikt_seq = $ikt_seq[0];

                /* Sorszám */
                $sorszam = $ikt_seq['sorszam'];

                /* Sorszám növelés */
                $sorszam++;

                /* Letároljuk az új sorszámot */
                $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), 'tabla='.$tabla.' and ev='.date('y'));
                if( $sql === false ) {
                    $db_error = true;
                }
            } else {
                /* Sorszám */
                $sorszam = 1;

                $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), $tabla));
                if( $sql === false ) {
                    $db_error = true;
                }
            }

            /* Iktatószám formázása */
            $ikt_szam = date('y').'/'.sprintf('%04d', $sorszam);


            /* Új anyagigényléskor a munkalaphoz tartozó anagok státuszán állítani kell */
            $sql = $adatbazis_kapcsolat->u('munkalap_anyag', array('stat_id'), array(2), "`telephely_id`='{$_POST['telephely_id']}' AND `cikk_id`='{$_POST['nev']}'");
            if( $cikk_nev === false ) {
                $db_error = true;
            }

            //Miután a $_POST['nev']-et felvettük cikkid-nak, csak utána írjuk felül
            $cikk_nev = $adatbazis_kapcsolat->s('nev', 'cikktorzs', "`id`='{$_POST['nev']}'");
            if( $cikk_nev === false ) {
                $db_error = true;
            }
            $cikk_nev = $cikk_nev[0];

            $i = 0;
            foreach($oszlopok as $oszl){
                if($oszl=='nev'){
                    $ertekek[$i] = $cikk_nev['nev'];
                }
                $i++;
            }

            //Cikk nevet letároljuk a cikknévbe
            array_push($oszlopok,  'cikktorzs_id'   );
            array_push($ertekek,   $_POST['nev']    );

            array_push($oszlopok,  'ikt_szam');
            array_push($ertekek,   $ikt_szam );

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        //Bejelentések iktatószám képzése
        } else if( $tabla == 'bejelentes' ) {
            /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
            $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', 'tabla='.$tabla.' and ev='.date('y'), '', '');
            if( $ikt_seq === false ) {
                $db_error = true;
            }
            if( $ikt_seq ) {
                $ikt_seq = $ikt_seq[0];

                /* Sorszám */
                $sorszam = $ikt_seq['sorszam'];

                /* Sorszám növelés */
                $sorszam++;

                /* Letároljuk az új sorszámot */
                $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), 'tabla='.$tabla.' and ev='.date('y'));
                if( $sql === false ) {
                    $db_error = true;
                }
            } else {
                /* Sorszám */
                $sorszam = 1;

                $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), $tabla));
                if( $sql === false ) {
                    $db_error = true;
                }
            }

            /* Iktatószám formázása */
            $ikt_szam = date('y').'/'.sprintf('%07d', $sorszam);

            array_push($oszlopok,  'ikt_szam');
            array_push($ertekek,   $ikt_szam );

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        //Gégpek
        } else if( $tabla == 'gep' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        //Költséghely
        } else if( $tabla == 'ktghely' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        //Költségnem
        } else if( $tabla == 'ktgnem' ) {
            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );

        //Login
        }elseif( $tabla == 'login' ) {
            if( $_POST['szerepkor_id'] ) {
                $szerepkor = $adatbazis_kapcsolat->s('flags', 'szerepkor', 'recid='.$_POST['szerepkor_id'], '', '1');
                if( $szerepkor === false ) {
                    $db_error = true;
                }
                if( $szerepkor ) {
                    $szerepkor = $szerepkor[0];

                    if( $szerepkor['flags'] ) {
                        array_push($oszlopok, 'flags'             );
                        array_push($ertekek,   $szerepkor['flags']);
                    }
                }
            }

        //Ütemezett feladatok ID képzése
        } else if( $tabla == 'utemezett_feladatok' ) {
            /* ID formázása */

            //Szálloda azonosító
            $szalloda   = $adatbazis_kapcsolat->s('azon', 'telephely', "`id`='{$_POST['telephely_id']}'");
            $szalloda   = $szalloda[0]['azon'];

            /* Megnézzük, hogy az adott hotell nevével létezik e bejegyzés */
            $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', 'tabla='.$tabla.' and ev='.$szalloda, '', '');
            if( $ikt_seq === false ) {
                $db_error = true;
            }
            if( $ikt_seq ) {
                $ikt_seq = $ikt_seq[0];

                /* Sorszám */
                $sorszam = $ikt_seq['sorszam'];

                /* Sorszám növelés */
                $sorszam++;

                /* Letároljuk az új sorszámot */
                $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), 'tabla='.$tabla.' and ev='.$szalloda);
                if( $sql === false ) {
                    $db_error = true;
                }
            } else {
                /* Sorszám */
                $sorszam = 1;

                $id = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, $szalloda, $tabla));
                if( $id === false ) {
                    $db_error = true;
                }
            }
            $id         = $szalloda.sprintf('%04d', $sorszam);

            array_push($oszlopok,  'id' );
            array_push($ertekek,   $id  );

            array_push($oszlopok, 'loginnev'                        );
            array_push($ertekek,   $_SESSION[get_db_tabla().'user'] );
        } else if( $tabla == 'erteklista' ) {
            $ertek = $adatbazis_kapcsolat->s('id', 'erteklista', "`csoport` = '".$_POST['csoport']."'", '', '');

            if( $ertek ) {
                $ertek_id = count($ertek) + 1;
            } else {
                $ertek_id = 1;
            }

            array_push($oszlopok,  'id'       );
            array_push($ertekek,   $ertek_id  );
        }

		$id = $adatbazis_kapcsolat -> insert($tabla, $oszlopok, $ertekek);
        if( $id === false ) {
            $db_error = true;
        }

//echo $adatbazis_kapcsolat->gp() . '<br>';
//echo '<pre style="font-size: 1.4rem">';
//echo $adatbazis_kapcsolat->gp() . '<br>';
//echo '</pre>';
//exit;
        //Bejelentések iktatószám képzése
        if( $tabla == 'bejelentes' ) {
            $sql = $adatbazis_kapcsolat -> i('bejelentes_statusz',
                array('id', 'stat_id', 'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
                array($id,   1,         date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Megvizsgáljuk, hogy egy/több szállodás modell */
            $telephely = $adatbazis_kapcsolat->s('modell', 'telephely', "`id` = '".$_POST['telephely_id']."'", '', '1');

            if( $telephely ) {
                $telephely = $telephely[0];

                /* Egyszállodás modell, így rögtön munkalapot generálunk belőle */
                if( !$telephely['modell'] ) {
                    /* Megnézzük, hogy az adott évszámmal létezik e bejegyzés */
                    $ikt_seq = $adatbazis_kapcsolat->s('ev sorszam', 'iktszam_seq', "`tabla` = 'munkalap' AND `ev` = '".date('y')."'", '', '');

                    if( $ikt_seq === false ) {
                        $db_error = true;
                    }

                    if( $ikt_seq ) {
                        $ikt_seq = $ikt_seq[0];

                        /* Sorszám */
                        $sorszam = $ikt_seq['sorszam'];

                        /* Sorszám növelés */
                        $sorszam++;

                        /* Letároljuk az új sorszámot */
                        $sql = $adatbazis_kapcsolat -> u('iktszam_seq', array('sorszam'), array($sorszam), "`tabla` = 'munkalap' AND `ev` = '".date('y')."'");

                        if( $sql === false ) {
                            $db_error = true;
                        }
                    } else {
                        /* Sorszám */
                        $sorszam = 1;

                        $sql = $adatbazis_kapcsolat -> i('iktszam_seq', array('sorszam', 'ev', 'tabla'), array($sorszam, date('y'), 'munkalap'));
                        if( $sql === false ) {
                            $db_error = true;
                        }
                    }

                    /* Iktatószám formázása */
                    $ikt_szam = 'M'.date('y').'/'.sprintf('%07d', $sorszam);

                    /* Munkalap beszúrása */
                    $recid = $adatbazis_kapcsolat -> i('munkalap',
                        array('ikt_szam',   'statusz_id',  'tipus_id',  'telephely_id',        'update_dt',          'update_usr',                          'insert_dt',          'insert_usr'                              ),
                        array($ikt_szam,     1,             1,          $_POST['telephely_id'], date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'], date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev']   )
                    );

                    if( $recid === false ) {
                        $db_error = true;
                    }

                    $statusz  = addStatusz( 'munkalap_statusz', $id, 1 );
                    $statusz2 = addStatusz( 'munkalap_statusz', $id, 2 );

                    if( $statusz === false ) {
                        $db_error = true;
                    }

                    if( $statusz2 === false ) {
                        $db_error = true;
                    }
                }
            }
        }

        /* Kezdeményezett művelet */
        $muvelet = 'A(z) '.$tabla.'-ba új recordot szúrt be a következő azonosítóval: '.$id.'.';

        /* Végrehajtott változtatás */
        $valtozas = '1.: A(z) <strong>recid</strong> mező a következő értéket kapta: <strong>'.$id.'</strong><br>';

        $i = 2;
        foreach($_POST as $key => $value) {
            if( $key != 'mehet_gomb' && $key != 'uj'.$tabla ) {
                $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező a következő értéket kapta: <strong>'.$value.'</strong><br>';

                $i++;
            }
        }

        $sql = $adatbazis_kapcsolat -> i(
            'log',
            array( 'felh_id',                          'nev',       'valtozas'),
            array( $_SESSION[get_db_tabla().'user_id'], $muvelet,   $valtozas )
        );

        if( $sql === false ) {
            $db_error = true;
        }

	} else {
        /* Csak nyitott státuszú bejelentést lehet szerkeszteni */
        if( $tabla == 'bejelentes' ) {
            $akt_statusz = $adatbazis_kapcsolat->s('stat_id', $tabla.'_statusz', 'id='.$_GET['id'], 'insert_dt desc', '1');
            if( $akt_statusz === false ) {
                $db_error = true;
            }
            if( $akt_statusz ) {
                $akt_statusz = $akt_statusz[0];

                if( $akt_statusz['stat_id'] != 1 ) {
                    echo json_encode(array(
                        'hiba' => 'Csak nyitott státuszú bejegyzést lehet módosítani!'
                    ));

                    exit();
                }
            } else {
                echo json_encode(array(
                    'hiba' => 'Státusz nem található!'
                ));

                exit();
            }
        }

        /* Ha volt változás, akkor logoljuk */
        $record = $adatbazis_kapcsolat->s('*', $tabla, 'recid='.$_GET['id'], '', '1');
        if( $record === false ) {
            $db_error = true;
        }
        if( $record ) {
            if( $record[0]['datetime'] ) {
                if( $_SESSION['formMod']['oldal'] == basename(self()) ) {
                    if( $_SESSION['formMod']['datetime'] != $record[0]['update_dt'] ) {
                        echo json_encode(array(
                            'hiba' => 'Ezt a bejegyzést valaki más már módosította, <br>kérlek kezd előről a szerkesztést!'
                        ));

                        exit();
                    }
                }
            }

            /* Kezdeményezett művelet */
            $muvelet = 'A(z) '.$tabla.' '.$_GET['id'].'. recordját módosította.';

            /* Végrehajtott változtatás */
            $valtozas = '';

            $i = 1;
            foreach($record[0] as $key => $value) {
                if( $key != 'recid' && $_POST[$key] ) {
                    if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ) {
                        $value = date('Y.m.d', strtotime($value));
                    }

                    if( $_POST[$key] != $value ) {
                        $valtozas .= $i.'.: A(z) <strong>'.$key.'</strong> mező értékét <strong>"'.$value.'"</strong>-ról <strong>"'.$_POST[$key].'"</strong>-ra változtatta.<br>';

                        $i++;
                    }
                }
            }

            $sql = $adatbazis_kapcsolat -> i(
                'log',
                array( 'felh_id',                          'nev',       'valtozas'),
                array( $_SESSION[get_db_tabla().'user_id'], $muvelet,   $valtozas )
            );
            if( $sql === false ) {
                $db_error = true;
            }
        }

        /* Datetime-ot is frissítjük */
        if( in_array($tabla.'.php', $_SESSION['ketHasabos']) ) {
            array_push($oszlopok, 'update_dt',          'update_usr'                          );
            array_push($ertekek,   date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev']);
        }

        if( $tabla == 'login' ) {
            if( $_POST['szerepkor_id'] && !$record[0]['flags'] ) {
                $szerepkor = $adatbazis_kapcsolat->s('flags', 'szerepkor', 'recid='.$_POST['szerepkor_id'], '', '1');
                if( $szerepkor === false ) {
                    $db_error = true;
                }
                if( $szerepkor ) {
                    $szerepkor = $szerepkor[0];

                    if( $szerepkor['flags'] ) {
                        array_push($oszlopok, 'flags'             );
                        array_push($ertekek,   $szerepkor['flags']);
                    }
                }
            }
        }


		$id = $adatbazis_kapcsolat -> update($tabla, $oszlopok, $ertekek, 'recid='.$_GET['id']);
//echo $adatbazis_kapcsolat->gp() . '<br>'; exit;
        if( $id === false ) {
            $db_error = true;
        }
        if( $id ) {
            $sor = $adatbazis_kapcsolat->s('*', $tabla, 'recid='.$_GET['id'], '', '1');
            if( $sor === false ) {
                $db_error = true;
            }
            if( $sor ) {
                $_SESSION['formMod'] = array(
                    'datetime' => $sor[0]['update_dt'],
                    'oldal' => basename(self())
                );

                foreach($tomb as $value) {
                    if( $value['type'] == 'autocomplete' ) {
                        $kulso_ertek = $adatbazis_kapcsolat->s('recid nev', $value['tabla'], 'recid='.$sor[0][$value['name']], '', '1');
                        if( $kulso_ertek === false ) {
                            $db_error = true;
                        }
                        $sor[0][$value['name'].'_autocomp'] = '';
                        if( $kulso_ertek ) {
                            $sor[0][$value['name'].'_autocomp'] = $kulso_ertek[0]['nev'];
                        }
                    }
                }

                echo json_encode(array(
                    'statusz' => 'siker',
                    'adatok' => $sor[0]
                ));
            }
        }
	}

	if(isset($_GET['id']) && $type == 'update'){
		$id=$_GET['id'];
	}

	if($nevid != '' && $type == 'insert'){//Ha kell, akkor beállítjuk még a nevid-s oszlopot

		$nevid = fajlnev_keszit($_POST[$nevid[name]]);
		if(!$nevid){
            $nevid = 'noname';
        }
        $nevid_tomb = array();
        $nevidk = $adatbazis_kapcsolat -> s('nevid', $tabla, '`nevid` LIKE \''.$nevid.'%\'');
        if( $nevidk === false ) {
            $db_error = true;
        }
        foreach($nevidk as $nev_id){
            array_push($nevid_tomb, $nev_id['nevid']);
        }

        while(in_array($nevid, $nevid_tomb)){
            $nevid = fajlnev_sorszamoz($nevid);
        }

		$sql = $adatbazis_kapcsolat -> u($tabla, 'nevid', $nevid, 'id='.$id);
        if( $sql === false ) {
            $db_error = true;
        }
	}

    //Tranzakció kommitolása, vagy rollback-ezése
    if( !$db_error ) {
        mysqli_commit($db_kapcs);
    } else {
        mysqli_rollback($db_kapcs);
    }

	return array('', $id);
}


/**
*	JS alapú hibaüzenetket generál egy tömb alapján (alert2)
*
*	@param array:  mely tomb alapján generáljon (az alert miatt lényeges)
*	@param string: mi legyen a form ellenőrző függvény neve?
*	@param bool:   lehet-e ajaxos vagy hagyományos?
*/
function js_kiir($tomb, $fg = 'form_ell_regi()', $ajax = false) {

    $hiba_txt = 'Hiba'; //Csak hogy egy helyen kelljen átírni.

    $ret = nl2('
                <'.str_replace('#', '', 's#cript').'>
                    var helyiseg_gep_mehet = false;
                    var riasztas_mehet     = false;
                    var utemezes_mehet     = false;
                    var bejelentes_mehet   = false;
                    function '.$fg.'{
            ');

    $param = '';
    foreach($tomb as $ertek){
        if( $ertek['type'] != 'statusz' && $ertek['type'] != 'info' ) {
            if($ertek['name']) {

                //Csak akkor figyelünk lenti paramétereket, ha egyáltalán az elem létezik. Ezzel egy csomó JS hibát elekerólhetünk és debuggolós szívásokat
                $ret .= 'if($(\'#'.$ertek['name'].'\').length){ ';

                if($ertek['alert']){
                    $focus_autocomp = $ertek['name'];
                    if( $ertek['type'] == 'autocomplete' ) {
                        $focus_autocomp = $ertek['name'].'_autocomp';
                    }

                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2(); '.$focus_autocomp.'.focus();"></p>\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false; }';
                }
                //Ha numerikus a mező, akkor a 0,00 is üres értéknek számít
                if(strpos($ertek['class'], 'f_num')!==false){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value==\'0,00\'){ document.getElementById(\''.$ertek['name'].'\').className += \' alert\'; alert2(\''.$ertek['alert'].'\', \'<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2();"></p>\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false; }';
                }

                if($ertek['mail']){
                    $ret .= "str=document.getElementById('$ertek[name]').value.trim(); if(str){ filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i; if (!filter.test(str)){ document.getElementById('$ertek[name]').focus(); document.getElementById('$ertek[name]').className='alert'; alert2('Helytelen e-mail cím!', \'\', \''.$hiba_txt.'\'); scrollto('#$ertek[name]', 500); return false; } }";
                }

                if($ertek['name'] == 'jelsz2'){
                    $ret .= 'if(document.getElementById(\'reg_jelsz\').value.length < 4 ){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszó hossza minimum 4 karakter legyen!\');return false;}';
                    $ret .= 'if(document.getElementById(\'jelsz2\').value != document.getElementById(\'reg_jelsz\').value){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A jelszavak nem egyeznek meg!\', \'\', \''.$hiba_txt.'\');return false;}';
                }

                if($ertek['name'] == 'isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\'isz\').value.length != 4){document.getElementById(\'isz\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
                }

                if($ertek['name'] == 'sz_isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
                }

                if($ertek['name'] == 'szall_isz' && $ertek['alert']){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').value.length != 4){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'Helytelen irányítószám!\', \'\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
                }

                //Addószám formátumának ellenőrzése (xxxxxxxx-x-xx alakú-e). Az inputra egyébként a main.js-ben található adoszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
                if($ertek['name'] == 'ado'){
                    $ret .= nl2('
                        reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                        if( reszek[0].length!=8 || reszek[1].length!=1 || reszek[2].length!=2){
                            document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                            alert2(\'Az adószám formátuma helytelen!\', \'\', \''.$hiba_txt.'\');
                            scrollto(\'#'.$ertek['name'].'\', 500);
                            return false;
                        }
                        ');
                }

                //Addószám formátumának ellenőrzése (xx-xx-xxxxxx alakú-e). Az inputra egyébként a main.js-ben található cegjegyzekszam_formaz() függvényt hívd meg, de az alábbi ellenőrzéssel együtt lesz tökéletes
                if($ertek['name'] == 'cegjegyzek'){
                    $ret .= nl2('
                        reszek = $(\'#'.$ertek['name'].'\').val().split(\'-\');
                        if( reszek[0].length!=2 || reszek[1].length!=2 || reszek[2].length!=2){
                            document.getElementById(\''.$ertek['name'].'\').className += \' alert\';
                            alert2(\'Az adószám formátuma helytelen!\', \'\', \''.$hiba_txt.'\');
                            scrollto(\'#'.$ertek['name'].'\', 500);
                            return false;
                        }
                        ');
                }

                if($ertek['name'] == 'elf'){
                    $ret .= 'if(document.getElementById(\''.$ertek['name'].'\').checked==false){document.getElementById(\''.$ertek['name'].'\').className += \' alert\';alert2(\'A feltételek elfogadása kötelező!\',\'\', \''.$hiba_txt.'\'); scrollto(\'#'.$ertek['name'].'\', 500); return false;}';
                }

                if($ertek['name'] == 'akcar'){	//Ha van akciós ár, akkor figyelük azt is, hogy kissebb legyen
                                            //Először ki kell szűrni a pontokat belőle
                    $ret .= "var ar=document.getElementById('ar').value; var akcar=document.getElementById('akcar').value; mit=new RegExp('[.]','ig'); ar=ar.replace(mit,''); akcar=akcar.replace(mit,''); if(Number(ar)<=Number(akcar)){ document.getElementById('".$ertek['name']."').className += ' alert'; alert2('Az akciós ár nem kedvezőbb a listaártól!', '', ".$hiba_txt."'); scrollto('#$ertek[name]', 500); return false; }";
                }

                $ret .= '}';
            }

            if($ajax && !strstr($ertek['type'], 'file')/* tehát nem jsfile vagy sima file... */ ){
                if($ertek['name']){//Ha van neve, nem HTML pl.
                    if($ertek['type']=='checkbox'){
                        $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').is(\':checked\')+\'&amp;';
                    }  else {
                        $param .= $ertek['name'].'=\'+$(\'#'.$ertek['name'].'\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
                    }
                }
            }
        }
    }

    //Egyedi hibaüzenetek
    if( basename(self())=='utemezett_feladatok.php' ){

        $param .= 'aktkezd_d=\'+$(\'#aktkezd_d\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'aktvege_d=\'+$(\'#aktvege_d\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'ism_het=\'+$(\'#ism_het\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'kezdho=\'+$(\'#kezdho\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'kezdnap=\'+$(\'#kezdnap\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'vegeho=\'+$(\'#vegeho\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'vegenap=\'+$(\'#vegenap\').val().replace(/&/gi, \'|ES|\')+\'&amp;';
        $param .= 'h=\'+$(\'#h\') .is(\':checked\')+\'&amp;';
        $param .= 'k=\'+$(\'#k\') .is(\':checked\')+\'&amp;';
        $param .= 's=\'+$(\'#s\') .is(\':checked\')+\'&amp;';
        $param .= 'cs=\'+$(\'#cs\').is(\':checked\')+\'&amp;';
        $param .= 'p=\'+$(\'#p\') .is(\':checked\')+\'&amp;';
        $param .= 'sz=\'+$(\'#sz\').is(\':checked\')+\'&amp;';
        $param .= 'v=\'+$(\'#v\') .is(\':checked\')+\'&amp;';
        $ret .= nl2('

                /* gép és helyiség is üresen maradt figylemeztetés */
                if(!$(\'#gep_id\').val() && !$(\'#obj_id\').val() && !helyiseg_gep_mehet){
                    uf_gomb = \'<div class="c"><input type="button" class="gomb piros" value="Igen" onclick="helyiseg_gep_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb zold" value="Nem" onclick="alert2()"></div>\';
                    alert2(\'Nem adott meg sem helyiséget sem gépet a feladathoz.<br>Folytatja?\', uf_gomb, \'Kérdés\');
                    return false;
                }

                /* Riasztás 0 értékkel van beállítva, így mehet? */
                if( (!$(\'#riaszt\').val() || $(\'#riaszt\').val()==0) && !riasztas_mehet ){
                    uf_gomb2 = \'<div class="c"><input type="button" class="gomb piros" value="Igen" onclick="riasztas_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb zold" value="Nem" onclick="alert2()"></div>\';
                    alert2(\'
                        Ön a következőt választotta:
                        <br>
                        <br><b class="piros">Riasztás 0 nappal az esedékesség előtt.</b>
                        <br>
                        <br>Ha folytatja, a feladat az esedékesség napján készül munkalap!
                        <br>
                        <br>Folytatja?
                    \', uf_gomb2, \'Kérdés\');
                    return false;
                }


                /* Dátumra ütemezés közlése mentés előtt. */
                if( !utemezes_mehet ){

                    $.ajax({
                        url : \'utemezett_feladatok.php\',
                        type: \'GET\',
                        data: esamp2es(\'id='.$_GET['id'].'&amp;datumra_utemezes=\' + new Date().getTime()),
                        success: function(response) {

                            response = JSON.parse(response);
                            uf_gomb3 = \'<div class="c"><input type="button" class="gomb piros" value="Igen" onclick="utemezes_mehet = true; $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb zold" value="Nem" onclick="alert2()"></div>\';
                            alert2(response[\'uzenet\'], uf_gomb3, \'Kérdés\');
                            $(\'#esnap_d\').val(response[\'esedekes\']);
                            return false;

                        }
                    });
                }else{
                    alert2();
                }

        ');
    }

    if( basename(self())=='anyagigenyles.php' ) {
        $ret .= nl2('

            if( !$(\'#nev\').val() ){
                alert2(\'Kérjük válasszon ki egy cikknevet az értéklistából!\', \'\', \'Hiba\');
                return false;
            }

            anyagig_id = \'#th\'+$(\'#telephely_id\').val()+\'a\'+$(\'#nev\').val();
            min_mennyiseg = $(anyagig_id).html();
            mee           = $(anyagig_id+\'_mee\').html();
            gomb          = \'<input type="button" class="gomb piros" value="Igen" onclick="$(\\\'#igmen\\\').val(min_mennyiseg); $(\\\'#szerk_form\\\').submit();"> <input type="button" class="gomb zold" value="Nem" onclick="alert2()">\';
            if(min_mennyiseg && min_mennyiseg>$(\'#igmen\').val()){
                alert2(\'Igényelt mennyiség: \' + $(\'#igmen\').val() + \' \'+mee+\'<br>Minimális igényelhető mennyiség: \' +min_mennyiseg+\' \'+mee+\'<br>Ha folytatja, \' +min_mennyiseg+\' \'+mee+\' lesz igényelve.<br>Folytatja?\', gomb, \'Hiba\');
                return false;
            }

        ');
    }

    if( basename(self())=='bejelentes.php' ) {
        $ret .= nl2('
            if( !bejelentes_mehet ) {
                $.ajax({
                    url : \'esemenyek.php\',
                    type: \'GET\',
                    data: esamp2es(\'telephely_id=\' + $(\'#telephely_id\').val() + \'&amp;folyamatban_munkak=\' + new Date().getTime()),
                    success: function(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba);
                            }
                        }

                    }
                });

                return false;
            } else {
                alert2();
            }
        ');
    }

    if( basename(self())=='ktghely.php' || basename(self())=='ktgnem.php' ){

        $ret .= nl2('
            if($(\'#id\').hasClass(\'foglalt_id\')){
                alert2($(\'#id\').val()+\' - \'+$(\'#nev\').val()+\'<br>Ez a kód már használatban van!\', \'\', \'Hiba\');
                return false;
            }
        ');
    }

    //Ajaxos form módosítás
    $ajax_kod = nl2('
        $.ajax({
            url : \''.self().'?id='.$_GET['id'].'\',
            type: \'POST\',
            data: esamp2es(\''.$param.str_replace('.php', '', basename(self())).'lista=1&amp;szerkeszt=1&amp;ajax_form_modosit=\' + new Date().getTime()),
            success: function(response) {
                if( response ) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba);
                        }

                        return false;
                    }

                    szamlaSorFrissit(\''.str_replace('.php', '', basename(self())).'\', \''.$_GET['id'].'\');

                    formAdatok = response.adatok;

                    gombokNullaz();

                    $(\'#mehet_gomb\').removeClass(\'gombb_alert\').attr(\'disabled\', true);
                }
            }
        });
    ');

    if($ajax){
        $ret .= nl2('; '.$ajax_kod.'
            ; return false; }
            ');
    }else{
        $ret .= '; return true;	}';
    }
    $ret .= nl2('
            $(document).ready(function() {
                $(\'form input[type="text"], #szerk_form textarea\').keyup(function() {
                    /*$(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');*/
                });

                $(\'form input[type="checkbox"]\').click(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });

                $(\'form select\').change(function() {
                    $(\'#szerk_form input[type="submit"]\').addClass(\'gombb_alert\');
                });
            });</script>
        ');

	return $ret;
}


function get_termek_thumb($id, $sablon, $hivatkozott_oldal, $id_oszlop, $id_get_neve, $tabla, $netto_aras){

    global $adatbazis_kapcsolat;

    $termek = $adatbazis_kapcsolat->s('id nev kep text ar akcar', $tabla, "`$id_oszlop`='$id'");
    $termek = $termek[0];

    $kat = $adatbazis_kapcsolat->s('nev', 'kat', "`id`='{$termek['kat']}'");
    $kat = $kat[0];

    //Link
    $link = $hivatkozott_oldal.'?'.fajlnev_keszit($kat['nev'].' '.$termek['nev']).'&amp;'.$id_get_neve.'='.$termek[$id_oszlop];

    /*//Többképes verzió
    $kepek = $adatbazis_kapcsolat -> select('id, kepnev', 'kepek', 'termek_id='.$termek['id'], 'sor');
    $kep   = $kepek[0];
    $termek['kep'] = $kep['kepnev'];*/

    //Kép
    if($termek['kep'] && is_file(get_utvonal().$termek['kep'])){
        $kep_obj =  new Img(get_utvonal().$termek['kep'], $termek['nev'], get_indexkep_sz(), get_indexkep_m());
    }else{
        $kep_obj = new Img(get_utvonal().'nincskep.jpg', $termek['nev']);
    }
    $fokep     = $kep_obj -> getHtml();

    $intro = nl2br2( resz_string($termek['text'], 200) );
    //$intro = nl2(resz_string( strip_tags( htmlspecialchars_decode2( $termek['text'] ) ), 200) ); <= Webes szövegszerkesztő esetére
    //$intro = nl2(resz_string( strip_tags( string2html( $termek['text'] ) ), 200) );              <= Wikipedia szerű formázáshoz

    //Ár, akciós ár
    if($netto_aras){
        $netto_ar  = $termek['ar'];
        $brutto_ar = $termek['ar']*((100+get_afa())/100);
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar'];
            $brutto_akcar = $termek['akcar']*((100+get_afa())/100);
        }
    }else{
        $netto_ar  = $termek['ar']/((100+get_afa())/100);
        $brutto_ar = $termek['ar'];
        if($termek['akcar']){
            $netto_akcar  = $termek['akcar']/((100+get_afa())/100);
            $brutto_akcar = $termek['akcar'];
        }
    }

    if($termek['akcar']>0){
        $ar       = '<p class="listaar">Listaár: '.number_format($brutto_ar, 0, '', '.').' '.get_penznem().' <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
        $akcar    = '<p class="akcar">Akciós ár: <span class="ar">'.number_format($brutto_akcar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_akcar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    }else{
        $ar    = '';
        $akcar = '<p class="akcar">Listaár: <span class="ar">'.number_format($brutto_ar, 0, '', '.').' '.get_penznem().'</span> <span class="afa">('.number_format($netto_ar, 0, '', '.').' '.get_penznem().' + '.get_afa().'% ÁFA)</span></p>';
    }

    $bovebben = '<a href="'.$link.'">Bővebben</a>';

    $kosarba  = '';

    //Kiíratás
    $mit    = array('{ID}', /* A sablonváltozók és adatok legyenek egymás alatt */  '{LINK}', '{KEP}', '{NEV}',        '{INTRO}',  '{AR}', '{AKCAR}', '{KOSARBA}');
    $mivel = array($termek['id']/*itt a rendes id kell pl. a kosár miatt*/,         $link,    $fokep,  $termek['nev'], $intro,     $ar,    $akcar,    $kosarba);
    echo str_replace($mit, $mivel, $sablon);

}

/**
*	Web FTP
*	FONTOS: ha képet töltünk fel, magától odateszi a kiterjesztést (.jpg .gif .png).
*	Átlagos fájlok esetén adjuk meg a kiterjesztést is a $kepnev-ben
*
*	@param string $valtozo kötelező megadni, ez lesz a változó neve (űrlap neve)
*	@param string $fajlneve kötelező megadni, ez lesz a feltöltött fájl neve
*	@param integer $size méret limit, ami alapból 400 Kb
*	@param boolean $overwrite az eredeti fájlt felülírjuk-e ha már létezett ilyen nevű, vagy készítünk egy újat (pl: kep2.jpg)
*	@param boolean $csakkep ez alapból igaz, tehát csak jpg, png és gifet enged feltölteni
*	@param string $tabla adatbázis tábla neve. Kötelező megadni ha a $sor_id nem null
*	@param string $oszlop adatbázis oszlop neve. Kötelező megadni ha a $sor_id nem null
*	@param string $sor_id adatbázis tábla egy soridje. Ha ez adott, akkor SQL update-ja a tábla adott oszlopát, mivel ilyen esetben a fájl útvonala letárolt
*
*	@return array 0. argumentuma a kép neve, 1. pedig a hibaüzenet (-1 ha sikeres)
*	k1: túllépte a limitet
*	k2: nem képtipusú fájl
*	k3: ilyen nevű fájl már létezett a szerveren és törölni nem lehetett
*	k4: nem sikerül felmásolni
*/
function web_ftp($valtozo, $kepnev, $size=409600, $overwrite=false, $csakkep=true, $tabla=null, $oszlop=null, $sor_id=null, $flashfeltoltes){

    global $adatbazis_kapcsolat;
    global $db_kapcs;

    //Hogy ne csak admin alól tudjuk használni
	if(basename( dirname( self() ) ).'/' == get_admin() ){
        $utvonal = '../'.get_utvonal();
    }else{
        $utvonal = get_utvonal();
    }

    //Ha túllépi a méretlimitet: hiba1
	if($_FILES[$valtozo]['size'] > $size){
		return array($kepnev, 'k1');
	}

	$tiltott_fajlok = array('text/x-js', 'text/css', 'text/html', 'application/xhtml+xml', 'text/xml');
	//Ha csakképet engedünk, és nem képformátumú: hiba2
	if($csakkep && !$flashfeltoltes && !($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg' || $_FILES[$valtozo]['type']=='image/gif' || $_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')) {
		return array($kepnev,'k2');
	}elseif(in_array($_FILES[$valtozo]['type'],$tiltott_fajlok)){
		return array($kepnev,'k2b');
	}

    //Ha kell, levesszük a kiterjesztést
	$kit = '';
    if(strrpos($kepnev,'.')){
        $kepnev_tmp = substr($kepnev,0,strrpos($kepnev,'.'));
        $kit        = strtolower(substr($kepnev,strrpos($kepnev,'.'),strlen($kepnev)));//elmentjük a kiterjesztést, kisbetűsre alakítva
        $kepnev     = $kepnev_tmp;
    }
	if($overwrite){//Ha van, felülírjuk a már létező fájlt
		if($sor_id != null){//ha meg van adva a $sor_id akkor valószínűleg az elérési útvonal le van tárolva adatbázisba. Megkíséreljük ezt az előző fájlt törölni

			$lekerdez = $adatbazis_kapcsolat -> select($oszlop, $tabla, 'recid='.$sor_id);
			$sor      = $lekerdez[0];

			//Ebben az esetben valóban az adatbázisban van elmentve a kép elérési útvonala
			if($sor[$oszlop]){

                //Elfőrodukhat, hogy több bejegyzésnél is a kép útvonala azonos
                //Emiatt ha az egyiknél cserélnénk a képet, akkor azt még ne törölje le, ha máshol még arra a képre hivatkozunk
                $van_mashol_kep = false;
                $van_mashol_kep = $adatbazis_kapcsolat->s('recid', $tabla, $oszlop.'='.$sor[$oszlop].' AND recid!='.$sor_id);

                if(!$van_mashol_kep){
                    //ha van kiterjesztése (általában van)
                    if( strrpos($sor[$oszlop], '.') > 0 ){
                        remover($utvonal.substr($sor[$oszlop], 0, strrpos($sor[$oszlop], '.')).'_*');
                    }else{
                        remover($utvonal.$sor[$oszlop].'_*');
                    }
                    //Végül magát a fájlt is töröljük
                    if(is_file($utvonal.$sor[$oszlop])){
                        unlink($utvonal.$sor[$oszlop]);
                    }
                }
			}

			$lekerdez = mysqli_query($db_kapcs, 'SELECT `recid` FROM `'.get_db_tabla().$tabla.'` WHERE `recid`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
			$foglalt_nev = mysqli_num_rows($lekerdez);
			while( $foglalt_nev ){
				$kepnev = fajlnev_sorszamoz($kepnev);

				$lekerdez=mysqli_query($db_kapcs, 'SELECT `recid` FROM `'.get_db_tabla().$tabla.'` WHERE `recid`!=\''.$sor_id.'\' AND `'.$oszlop.'` LIKE \''.$kepnev.'%\'');
				$foglalt_nev = mysqli_num_rows($lekerdez);
			}

		}

		//Egyébként ha nincs letárolva (sorid-s technika) és minden más esetben is törlünk minden hasonló fájlt
        $kepnev_ = $utvonal.$kepnev;
		if(is_file($kepnev_))       { if(!unlink($kepnev_)){        return 'k3'; } }
		if(is_file($kepnev_.'.jpg')){ if(!unlink($kepnev_.'.jpg')){ return 'k3'; } }
		if(is_file($kepnev_.'.gif')){ if(!unlink($kepnev_.'.gif')){ return 'k3'; } }
		if(is_file($kepnev_.'.png')){ if(!unlink($kepnev_.'.png')){ return 'k3'; } }

		remover($kepnev_.'_*');//Resized képek esetére

	}else{//Egyébként másolat (Arra az esetre, ha pl. két termék neve megeggyezik és a nevéből nyerjük ki a képnevet => termeknev_2.jpg)

		$i = 1;
		$ujnev = $kepnev;
		while(glob($utvonal.$ujnev.'*')){
			$ujnev = $kepnev.'_'.$i;
			$i++;
		}
		if( $i==1 && $sor_id ){
            $ujnev = $kepnev.'_1';
        }
		$kepnev = $ujnev;

	}

	//Ha szükséges, akkor adunk neki kiterjesztést is (képek esetén), vagy visszaadjuk az eredetit ha nem kép volt
	if($_FILES[$valtozo]['type']=='image/pjpeg' || $_FILES[$valtozo]['type']=='image/jpeg') { $kit='.jpg'; }
	if($_FILES[$valtozo]['type']=='image/gif')                                              { $kit='.gif'; }
	if($_FILES[$valtozo]['type']=='image/png' || $_FILES[$valtozo]['type']=='image/x-png')  { $kit='.png'; }
	$kepnev .= $kit;


    $konyvtar = substr($kepnev,0,strrpos($kepnev,'/'));
    if(!is_dir($utvonal.$konyvtar)){
        mkdir($utvonal.$konyvtar, 0777);
    }

	if(move_uploaded_file($_FILES[$valtozo]['tmp_name'], $utvonal.$kepnev)){
		if($sor_id) {
			if(!$adatbazis_kapcsolat -> u($tabla, array($oszlop), array($kepnev), 'recid='.$sor_id)){
				return(array($kepnev,'k5'));//Sikertelen táblamod => hiba5
			}
		}
		if($flashfeltoltes){
            //Flash alapú fájlfeltöltéskor kell egy kimenet, ha minden rendben volt.
            //Akkor ez már legyen az útvonal, mert JS-el feldolgozva ezt ki is tudjuk íratni.
            echo $utvonal.$kepnev;
        }

        //A képeket progresszívvá alakítjuk
        if( in_array($kit, array('.jpg'/*, '.png' PNG tömörítéssel gondok voltak, erre más megoldás kell */))  ){
            $data       = fread(fopen($utvonal.$kepnev, 'rb'), filesize($utvonal.$kepnev));
            $src_img    = @imagecreatefromstring($data);
            imageinterlace($src_img, 1);
            if($kit=='.jpg'){
                imagejpeg($src_img, $utvonal.$kepnev, 100);
            }else{
                imagepng($src_img, $utvonal.$kepnev);
            }
        }

        /* Vízjelezés
        if($tabla=='termek'){
            //Debug: fajlba_mentes('!debug.txt', $utvonal.$kepnev);
            imagejpeg(logot_pecsetel($utvonal.$kepnev, 'elemek/vizjel.png', 50, 50, 100), $utvonal.$kepnev);
        }
         */

		return array($kepnev, '-1');
	}else{
		return array($kepnev, 'k4');
	}
}

/* Központi SQL előállítása a bal oldali navpanel elemeinek kiszámításához (where feltétel, rendezés) */
function szamla_lista( $where, $tabla ) {

    global $adatbazis_kapcsolat;
    global $db_kapcs;
    global $_OPTS;

    //Config
    if(!$tabla){
        $tabla  = str_replace('.php', '', basename(self()));
    }
    $select = $_OPTS['select'];
    if($_GET['rend']){
        $order = $_GET['rend'];
    }else{
        $order = $_OPTS['rend'];
    }

    $irany = $_GET['irany'];
    //Aktív esetén a rendezés irányát meg kell fordítani (tehát az aktívak, egyesek legyenek elől)
    if($order=='aktiv'){
        if($irany=='desc'){
            $irany = 'asc';
        }else{
            $irany = 'desc';
        }
    }
    $order .= ' '.$irany;

    //Ha nem egy konrét recid-t keresünk, akkor állítsuk elő a where feltételt
    if(!$where){

        if( $tabla == 'rendszer' ) {
            if( $_GET['rkat'] ) {
                if( strstr($_GET['rkat'], ',') ) {
                    $where = "`rkat` IN (".$_GET['rkat'].") AND ";
                } else {
                    $where = "`rkat` = '".$_GET['rkat']."' AND ";
                }
            }
        }

        //Bejelentéseknél szűrünk a törölve oszlop értékére is (logikailag törölt sorok nem látszanak)
        //Illetve csak a beloginolt user szállodáihoz tartozó bejegyzések látszanak
        $szallodak = get_akt_login_szallodai();
        if($tabla == 'bejelentes'){
            $where = "`".get_db_tabla()."$tabla`.`torolve` = '0' AND `".get_db_tabla()."bejelentes`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }
        if($tabla == 'anyagigenyles'){
            $where = "`".get_db_tabla()."anyagigenyles`.`telephely_id` IN (".implode(',', $szallodak).") AND ";
        }

        //Keresés
        if( $_GET['ksz'] ) {

            $selectek = explode(' ', $_OPTS['select']);

            $ksz_where = '';
            foreach($selectek as $select_tmp) {
                if( $select_tmp && $select_tmp != 'recid' ) {
                    if(strpos($select_tmp, '`')!==false){
                        $ksz_where .= trim($select_tmp, ',')." LIKE '%{$_GET['ksz']}%' OR ";
                    }else{
                        $ksz_where .= "`{$select_tmp}` LIKE '%{$_GET['ksz']}%' OR ";
                    }
                }
            }

            if($ksz_where){
                $where .= '('.trim($ksz_where, ' OR ').') AND ';
            }

        }

        //ha van $_GET['idk'] változónk, akkor azokra az ID-kra még pluszban szűrünk
        if ($_GET['recidk']) {
            $where .= '`recid` IN ('.$_GET['recidk'].') AND ';
        }

        //Where feltétel lezárása
        $where = trim($where, ' AND ');

    //Ha van megadva where feltétel, akkor az a recid=x, tehát frissítünk
    }else{
        $frissit = true;
    }

    //Lapozás előkészítése
    require_once('../class/class.Lapozo.php');

    if(!$_GET['oldal']){
        $_GET['oldal'] = 1;
    }

    $_GET['epo'] = 9999999999999;

    $bejegyzesek = $adatbazis_kapcsolat->s('recid', $tabla, $where);
    $lapozo = new Lapozo;
    $lapozo -> elem_osszes      = count($bejegyzesek);
    $lapozo -> fajl             = $_SERVER['PHP_SELF'];
    $lapozo -> mind_var         = ''; //'Összes elem kilistázása' változó neve. Ha üres akkor nincs össsez oldal megjelenítés. Alapértelmezetten 'osszes'
    //$lapozo -> epo_var         = 'epo';    //Elem per oldal változó neve
    //$lapozo -> oldal_var       = 'oldal';  //Oldal változó neve
    $lapozo -> kozep_meret      = 5; //Nagy oldalszám esetén a középső intervallum mérete
    //$lapozo -> max_fix_oldal   = 10;        //Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat
    //$lapozo -> epo_array       = array(50, 100, 150);  //Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat
    //$lapozo -> mind_text       = 'Összes oldal';  //pldalszámok mellett a "minden elem kilistázásának" felirata (ha $mind_var üres string akkor nem jelenik ez meg)
    //$lapozo -> elozo_title     = '« Előző oldal';        //'Előző' felirat, link title-jéhez
    //$lapozo -> kovet_title     = 'Következő oldal »';    //'Következő' felirat, link title-jéhez
    //$lapozo -> kozep_meret     = 'Aktuális oldal';       //'Aktuális oldal' felirat, link title-jéhez

    $utvonal = '../sablon/admin/';
    $lapozo -> sablon_elozo       = $utvonal.'lapozas_elozo.html';
    $lapozo -> sablon_kovetkezo   = $utvonal.'lapozas_kovet.html';
    $lapozo -> sablon_oldalak     = $utvonal.'lapozas_oldalak.html';
    $lapozo -> sablon_option      = $utvonal.'lapozas_option.html';
    $lapozo -> sablon_pontok      = $utvonal.'lapozas_pontok.html';
    $lapozo -> sablon_select      = $utvonal.'lapozas_select.html';


    /* Számolás */
    $lapozo -> lapozas();

    $lapozas = $lapozo -> kiir();

    $limit = $lapozo ->getLimit();
//echo 'sel:'.$select . ' ir:'.$irany.' or:'.$order.' lim:'.$limit.' where:'.$where.'<br>';

    //Rendezés telephely táblával JOIN-olva
    $bejegyzesek = array();
    if(strpos($order, 'telephely_id')!==false || strpos($select, 'telephely_id')!==false){

        //Először is az ordert felülírjuk
        $order = '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'];

        //Bejelentéseknél a rendezés külön problémakör:
        if($tabla=='bejelentes'){

            $order = '';
            //Elsődleges rendezés a csport, ettől lesznek a csoportok is sorban (elől az önálló bejelentések). De ezt csak Diszpécsereknél renezzük így, mert csak náluk jelenik meg a csoportosítás
            if(_jog(2)){
                $order .= '`'.get_db_tabla().'bejelentes_csoport`.`nev`, ';
            }
            //A sürgős mindig elől lesz, bármi is a rendezettség
            $order .= '`'.get_db_tabla().'bejelentes`.`surgos` DESC, ';

            $irany_ikt_szam  = '';
            $irany_telephely = '';
            //Ha iktatószám szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if($_GET['rend']=='ikt_szam'){
                $order .= '`'.get_db_tabla().'bejelentes`.`ikt_szam` '.$_GET['irany'].', `'.get_db_tabla().'telephely`.`nev`';
            }
            //Ha telephely szerint rendezünk, akkor pedig ezt a szempontot kell előre venni, és az irány is erre vonatkozik
            if($_GET['rend']=='telephely_id'){
                $order .= '`'.get_db_tabla().'telephely`.`nev` '.$_GET['irany'].', `'.get_db_tabla().'bejelentes`.`ikt_szam`';
            }
        }

        //Rendezés anyagigényléskor
        if($tabla=='anyagigenyles'){
            //Ha iktatószám szerint rendezünk, akkor ez lesz a következő rendezési szempont, és az irány is erre vonatkozik
            if($_GET['rend']=='ikt_szam'){
                $order = '`'.get_db_tabla().'anyagigenyles`.`ikt_szam` '.$_GET['irany'];
            }
        }

        //Rendezés munkalap esetén
        if($tabla=='munkalap'){
            $order = 'csoport_id asc, '.$order;
        }

        //Select-et is kiegészítjük a rendezni kívánt oszloppal
        $select .= ', `'.get_db_tabla().'telephely`.`nev`';

        $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit);
        $mit   = 'WHERE';

        //Bejelentésnél még az értéklistát is felvesszük
        $sql_kieg = '';
        if($tabla=='bejelentes'){
            $sql_kieg = nl2('
                                JOIN `'.get_db_tabla().'erteklista` ON (`'.get_db_tabla().'erteklista`.`csoport`=4 AND `'.get_db_tabla().$tabla.'`.`hiba_id`=`'.get_db_tabla().'erteklista`.`recid`)
                                LEFT OUTER JOIN `'.get_db_tabla().'bejelentes_csoport` ON `'.get_db_tabla().$tabla.'`.`csoport_id`=`'.get_db_tabla().'bejelentes_csoport`.`recid`
                            ');
        }
        if(strpos($select, 'obj_id')!==false){
            $sql_kieg .= ' JOIN `'.get_db_tabla().'obj` ON `'.get_db_tabla().$tabla.'`.`obj_id`=`'.get_db_tabla().'obj`.`recid` ';
        }
        $mivel = 'JOIN `'.get_db_tabla().'telephely` ON `'.get_db_tabla().$tabla.'`.`telephely_id`=`'.get_db_tabla().'telephely`.`id`'.$sql_kieg.' WHERE';
        $sql = $adatbazis_kapcsolat->gp();

        //Ha az SQL-ben nem volna WHERE feltétel, akkor az ORDER BY elá kell beszúrni a JOIN-t
        if(strpos($sql, 'WHERE')===false){
            $mit = 'ORDER BY';
            $mivel = str_replace('WHERE', 'ORDER BY', $mivel);
        }
        $sql = str_replace($mit, $mivel, $sql);

        $sorok = mysqli_query($db_kapcs, $sql);
        while($sor = mysqli_fetch_assoc($sorok)){
            array_push($bejegyzesek, $sor);
        }

    //Értéklistát csoport szerinti sorrenben, azon belül pedig infó szerinti sorrendben kell kilitázni
    }elseif($tabla=='erteklista'){

        $sql = 'SELECT
                        `'.get_db_tabla().'erteklista`.`recid`,
                        `'.get_db_tabla().'erteklista`.`csoport`,
                        `'.get_db_tabla().'erteklista`.`info`,
                        `'.get_db_tabla().'erteklista_csoport`.`nev`
                FROM    `'.get_db_tabla().'erteklista`
                JOIN    `'.get_db_tabla().'erteklista_csoport` ON `'.get_db_tabla().'erteklista`.`csoport`=`'.get_db_tabla().'erteklista_csoport`.`csoport`
                WHERE   `'.get_db_tabla().'erteklista`.`torolve`=0
                ORDER BY `'.get_db_tabla().'erteklista_csoport`.`nev`, `'.get_db_tabla().'erteklista`.`info`
                ';

        $sorok = mysqli_query($db_kapcs, $sql);
        while($sor = mysqli_fetch_assoc($sorok)){
            array_push($bejegyzesek, $sor);
        }

    }else{
        $bejegyzesek = $adatbazis_kapcsolat->s($select, $tabla, $where, $order, $limit);
        $sql = $adatbazis_kapcsolat->gp();
    }
    //Excel exporthoz (lehet Nyomtatáshoz, PDF-hez is kell majd?)
    $_SESSION['akt_sql'] = $sql;
if (isset($_GET['debug'])) {
    echo $_SESSION['akt_sql'] . '<br>';
}

    if( $bejegyzesek ) {
        switch($tabla) {
            case 'anyagigenyles':
                $html = anyagigenylesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'anyagrendeles':
                $html = anyagrendelesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'bejelentes':
                $html = bejelentesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'bejovo_szamlak':
                $html = bejovoSzamlaLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'beosztas':
                $html = beosztasLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'cash_flow':
                $html = cash_flowLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'cikktorzs':
                $html = cikktorzsLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'energia':
                $html = energiaLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'erteklista':
                $html = erteklistaLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'eseti_fenntartas':
                $html = eseti_fenntartasLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'eszkozkezeles':
                $html = eszkozkezelesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'felmeresi_terv':
                $html = felmeresi_tervLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'fenntartasi_szerzodes':
                $html = fenntartasi_szerzodesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'ktghely':
                $html = ktghelyLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'ktgnem':
                $html = ktghelyLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'login':
                $html = loginLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'munkalap':
                $html = munkalapLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'rendszeres_karbantartas':
                $html = rendszeres_karbantartasLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'projektkezeles':
                $html = projektkezelesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'projektigenyles':
                $html = projektigenylesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'raktar':
                $html = raktarLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'rendszer':
                $html = rendszerLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'szamla':
                $html = szamlaLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'szallito':
                $html = szallitoLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'szabadsagigeny':
                $html = szabadsagigenyLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'szerepkor':
                $html = szerepkorLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'tervezes':
                $html = tervezesLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'torzsadat':
                $html = torzsadatLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'utemezett_feladatok':
                $html = utemezett_feladatokLista( $bejegyzesek, $tabla, $frissit );
            break;
            case 'uzemeltetesi_naplo':
                $html = uzemeltetesi_naploLista( $bejegyzesek, $tabla, $frissit );
            break;
        }
    }

    return $html;

}

function bejelentesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport_id'];

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            if( !$frissit && _jog(2)/* Csoportra bontás csak a diszpécsernek jelenik meg */ ) {
                $aktCsoport = $bsz['csoport_id'];

                if( $i == 1 || $csoport != $aktCsoport ) {
                    $csoportSor = $adatbazis_kapcsolat->s('nev', 'bejelentes_csoport', 'recid='.$aktCsoport, '', '1');

                    if( $csoportSor ) {
                        $csoportSor = $csoportSor[0];

                        /* Van e sürgős */
                        $surgos_e = $adatbazis_kapcsolat->s('recid', 'bejelentes', "`csoport_id` = '{$aktCsoport}' AND `surgos` > 0", '', '1');

                        $surgos_icon = '';
                        if( $surgos_e ) {
                            $surgos_icon = nl2('
                                <span class="icon bej piros">
                                    <i class="material-icons">error</i>
                                </span>
                            ');
                        }

                        $html .= '<li class="szamla_datum csoport" data-id="'.$aktCsoport.'"><span class="icon"></span><span class="csoportNev" data-id="'.$aktCsoport.'"><strong>'.$surgos_icon.$csoportSor['nev'].'</strong></li>';
                    }

                    $csoport = $aktCsoport;
                }
            }

            $surgos = '';
            if( $bsz['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            /* Telephely */
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            /* Helység */
            $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$bsz['obj_id'], '', '1');
            $helyseg = $helyseg[0]['nev'];

            /* Hiba */
            $hiba = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport`=4 AND `recid`='{$bsz['hiba_id']}'");
            $hiba = $hiba[0];

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$surgos.' '.$bsz['ikt_szam'].'</span>
                            <span>'.$telephely.' - '.$helyseg.'</span>
                            <span>'.$hiba['info'].'</span>
                         </a>';

            $csoport_class = '';
            $csoport_data = '';
            if( $bsz['csoport_id'] && _jog(2) ) {
                $csoport_class = ' behuz dn';
                $csoport_data = ' data-csoportID="'.$bsz['csoport_id'].'"';
            }

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.$csoport_class.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'"'.$csoport_data.'>'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}
function munkalapLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport_id'];

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            if( !$frissit ) {
                $aktCsoport = $bsz['csoport_id'];

                if( $i == 1 || $csoport != $aktCsoport ) {
                    $csoportSor = $adatbazis_kapcsolat->s('nev', 'munkalap_csoport', 'recid='.$aktCsoport, '', '1');

                    if( $csoportSor ) {
                        $csoportSor = $csoportSor[0];

                        /* Van e sürgős */
                        $surgos_e = $adatbazis_kapcsolat->s('recid', 'munkalap', "`csoport_id` = '{$aktCsoport}' AND `surgos` > 0", '', '1');

                        $surgos_icon = '';
                        if( $surgos_e ) {
                            $surgos_icon = nl2('
                                <span class="icon bej piros">
                                    <i class="material-icons">error</i>
                                </span>
                            ');
                        }

                        $html .= '<li class="szamla_datum csoport" data-id="'.$aktCsoport.'"><span class="icon"></span><span class="csoportNev" data-id="'.$aktCsoport.'"><strong>'.$surgos_icon.$csoportSor['nev'].'</strong></li>';
                    }

                    $csoport = $aktCsoport;
                }
            }

            $surgos = '';
            if( $bsz['surgos'] ) {
                $surgos = nl2('
                    <span class="icon piros">
                        <i class="material-icons">error</i>
                    </span>
                ');
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }


            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['ikt_szam'].'</span>
                         </a>';

            $csoport_class = '';
            $csoport_data = '';
            if( $bsz['csoport_id'] ) {
                $csoport_class = ' behuz dn';
                $csoport_data = ' data-csoportID="'.$bsz['csoport_id'].'"';
            }

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.$csoport_class.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'"'.$csoport_data.'>'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function utemezett_feladatokLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            /* Telephely */
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');
            $telephely = $telephely[0]['nev'];

            /* Helység */
            $helyseg = $adatbazis_kapcsolat->s('nev', 'obj', 'recid='.$bsz['obj_id'], '', '1');
            $helyseg = $helyseg[0]['nev'];

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$telephely.' - '.$helyseg.'</span>
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return $html;
    }

    return false;
}

function rendszeres_karbantartasLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return $html;
    }

    return false;
}

function eseti_fenntartasLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return $html;
    }

    return false;
}

function projektkezelesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return $html;
    }

    return false;
}

function fenntartasi_szerzodesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function anyagigenylesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }


            /* Telephely */
            $telephely = $adatbazis_kapcsolat->s('nev', 'telephely', 'id='.$bsz['telephely_id'], '', '1');
//echo $adatbazis_kapcsolat->gp() . '<br>';
            $telephely = $telephely[0];

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['ikt_szam'].'</span>
                            <span>'.$telephely['nev'].'</span>
                            <span>'.$bsz['anyagigenyles_nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function felmeresi_tervLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function torzsadatLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function szabadsagigenyLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function beosztasLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function projektigenylesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function anyagrendelesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function uzemeltetesi_naploLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function energiaLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function tervezesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function cash_flowLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function raktarLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function szamlaLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['recid'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
                <div id="naptar" class="'.$naptar_dn.'">
                    <div class="naptar"></div>
                </div>
            ');
        }

        return $html;
    }

    return false;
}

function cikktorzsLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function eszkozkezelesLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {

        $naptar_dn = 'dn';
        $lsita_dn  = '';
        if (isset($_GET['naptar'])) {
            $naptar_dn = '';
            $lsita_dn  = ' dn';
        }

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$lsita_dn.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= nl2('
                </ul>
            ');
        }

        return $html;
    }

    return false;
}

function szerepkorLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">
                            <a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                                <span>'.$bsz['nev'].'</span>
                            </a>
                        </li>';

            if( !$frissit ) {
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function loginLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['teljes_nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function erteklistaLista( $bejegyzesek, $tabla, $frissit ) {

    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $csoport = $bejegyzesek[0]['csoport'];
        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            if( !$frissit ) {
                $aktCsoport = $bsz['csoport'];

                if( $i == 1 || $csoport != $aktCsoport ) {
                    $csoportSor = $adatbazis_kapcsolat->s('nev', 'erteklista_csoport', 'csoport='.$aktCsoport, '', '1');

                    if( $csoportSor ) {
                        $csoportSor = $csoportSor[0];

                        $html .= '<li class="szamla_datum"><strong>'.$csoportSor['nev'].'</strong></li>';
                    }

                    $csoport = $aktCsoport;
                }
            }

            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['info'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function szallitoLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $aktiv = nl2('
                <span class="icon piros">
                    <i class="material-icons">clear</i>
                </span>
            ');
            if( $bsz['aktiv'] ) {
                $aktiv = nl2('
                    <span class="icon zold">
                        <i class="material-icons">done</i>
                    </span>
                ');
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$aktiv.' '.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function ktghelyLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['id'].' - '.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function ktgnemLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont rendszer'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span>'.$bsz['id'].' - '.$bsz['nev'].'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function bejovoSzamlaLista( $bejegyzesek, $tabla, $frissit ) {
    global $adatbazis_kapcsolat;

    if( $bejegyzesek ) {
        //$aktDatum = date_formaz($bejegyzesek[0]['erkezett']);

        $jClass = '';
        if( _jog(2) ) {
            $jClass = ' kijelolheto';
        }

        if( !$frissit ) {
            $html = '<ul id="szamlaList" class="szamla_cont'.$jClass.'">';
        }

        $i = 1;
        foreach($bejegyzesek as $bsz) {
            /*$szamlaDatum = date_formaz($bsz['erkezett']);

            if( $i == 1 || $aktDatum != $szamlaDatum ) {
                $html .= '<li class="szamla_datum"><strong>'.$szamlaDatum.'</strong></li>';

                $aktDatum = $szamlaDatum;
            }*/

            $active = '';
            if( $bsz['recid'] == $_GET['id'] ) {
                $active = ' active';
            }

            $statusz = '';
            $szinkod = '';
            if( $bsz['statusz'] ) {
                $statusz_sor = $adatbazis_kapcsolat->s('recid nev szinkod', 'bsz_statusz', 'recid='.$bsz['statusz'], '', '1');

                if( $statusz_sor ) {
                    $statusz_sor = $statusz_sor[0];

                    $statusz = $statusz_sor['nev'];

                    if( $statusz_sor['szinkod'] ) {
                        $szinkod = ' style="background: '.$statusz_sor['szinkod'].';"';
                    }
                }
            }

            $szallito_sor = $adatbazis_kapcsolat->s('recid nev', 'szallito', 'recid='.$bsz['szallito'], '', '1');

            $szallito = '';
            if( $szallito_sor ) {
                $szallito_sor = $szallito_sor[0];

                $szallito = $szallito_sor['nev'];
            }

            $li_tart = '<a class="szamla" data-recid="'.$bsz['recid'].'" data-fajl="'.$tabla.'.php" data-muvelet="modosit">
                            <span class="ikt_szam"><span class="szso"><span class="statusz"'.$szinkod.'></span>Ikt. szám:</span> '.$bsz['ikt_szam'].'</span>
                            <span class="targy"><span class="szso">Tárgy:</span> '.resz_string($bsz['targy'], 40).'</span>
                            <span class="szallito"><span class="szso">Szállító:</span> '.$szallito.'</span>
                            <span class="netto"><span class="szso">Nettó:</span> '.number_format($bsz['netto'], 0, '', '.').' '.get_penznem().'</span>
                            <span class="statusz"><span class="szso">Státusz:</span> '.$statusz.'</span>
                        </a>';

            if( !$frissit ) {
                $html .= '<li id="szamla-'.$bsz['recid'].'" class="szamla_sor'.$active.'" data-recid="'.$bsz['recid'].'" data-i="'.$i.'">'.$li_tart.'</li>';
            } else {
                $html .= $li_tart;
            }

            $i++;
        }

        if( !$frissit ) {
            $html .= '</ul>';
        }

        return $html;
    }

    return false;
}

function getOszlopNev( $oszlop ) {
    $oszlopNevek = array(
        'aktiv'         => 'Aktív',
        'csoport'       => 'Név',
        'e-szamla'      => 'E-számla',
        'esnap_d'       => 'Esedékesség',
        'erkezett'      => 'Érkezett',
        'id'            => 'ID',
        'ikt_szam'      => 'Iktató szám',
        'nev'           => 'Név',
        'rkat'          => 'Érték',
        'statusz'       => 'Státusz',
        'szerepkor_id'  => 'Szerepkör',
        'telephely_id'  => 'Szálloda',
        'teljes_nev'    => 'Teljes név',
        'torolve'       => 'Törölve',
    );

    if( basename(self()) == 'login.php' ) {
        $oszlopNevek['nev'] = 'Login név';
    }

    if( $oszlopNevek[$oszlop] ) {
        return $oszlopNevek[$oszlop];
    } else {
        return $oszlop;
    }
}

function erteklista($csoport, $id) {
    global $adatbazis_kapcsolat;

    $sor = $adatbazis_kapcsolat->s('info', 'erteklista', "`csoport` = '{$csoport}' AND `id` = '{$id}'", '', '1');

    if( $sor ) {
        $sor = $sor[0];

        return $sor['info'];
    } else {
        return false;
    }
}

function jogosultsagKatok( $tomb ) {
    require('jogosultsag.php');

    if( $tomb ) {
        $katok = array();

        foreach($tomb as $value) {
            if( !in_array($value['kat'], adatbazis2tomb($katok, 'id')) ) {
                array_push(
                    $katok,
                    array(
                        'id'  => $value['kat'],
                        'nev' => $jogosultsagKatok[$value['kat']]['nev'],
                        'sor' => $jogosultsagKatok[$value['kat']]['sor'],
                    )
                );
            }
        }

        return $katok;
    }

    return false;
}

function getJogSorRendez( $katID, $tomb ) {
    $jogKatok = array();

    if( $tomb ) {
        foreach($tomb as $value) {
            if( $value['kat'] == $katID ) {
                array_push($jogKatok, $value);
            }
        }

        return $jogKatok;
    }

    return false;
}

function obj_gen( $telephelyID = false ) {
    global $adatbazis_kapcsolat;

    $where = '';
    if( $telephelyID ) {
        $where = "`id` = '{$telephelyID}'";
    }

    $telephelyek = $adatbazis_kapcsolat->s('id nev', 'telephely', $where, 'nev', '');

    $html = '';
    if( $telephelyek ) {
        $html .= '<h2>Objektumok hierarchiája</h2><ul class="telephelyek">';

        foreach($telephelyek as $telephely) {
            $fokatok = $adatbazis_kapcsolat->s('recid telephely_id nev szulo', 'obj', 'szulo=0 and telephely_id='.$telephely['id'], '', '');

            $fokat_html = '';
            if( $fokatok ) {
                $active = '';
                if( $telephelyID == $telephely['id'] ) {
                    $active = ' active';
                }

                $fokat_html .= '<ul class="alkatok'.$active.'">';

                foreach($fokatok as $fokat) {
                    $katok = $adatbazis_kapcsolat->s('recid telephely_id nev szulo', 'obj', 'szulo='.$fokat['recid'], '', '');

                    $kat_html = '';
                    if( $katok ) {
                        $kat_html .= '<ul class="alkatok">';

                        foreach($katok as $kat) {
                            $kat_html .= '<li class="alkat"><a onclick="addObj( this );" class="link" data-recid="'.$kat['recid'].'">'.$kat['nev'].'</a></li>';
                        }

                        $kat_html .= '</ul>';
                    }

                    $alkatok = '';
                    $icon    = '';
                    $nyit    = ' class="link"';
                    $onclick = 'addObj( this );';
                    if( $kat_html ) {
                        $alkatok = ' alkatos';
                        $icon    = '<span class="icon"></span>';
                        $nyit    = ' class="nyit link"';
                        $onclick = '';
                    }

                    $fokat_html .= '<li class="kat'.$alkatok.'"><a onclick="'.$onclick.'"'.$nyit.' data-recid="'.$fokat['recid'].'">'.$icon.$fokat['nev'].'</a>'.$kat_html.'</li>';
                }

                $fokat_html .= '</ul>';
            }

            $alkatok = '';
            $icon = '';
            $nyit = '';
            if( $fokat_html ) {
                $alkatok = ' alkatos';
                $icon = '<span class="icon"></span>';
                $nyit = ' class="nyit"';
            }

            $html .= '<li class="fokat'.$alkatok.'"><a href=""'.$nyit.'>'.$icon.$telephely['nev'].'</a>'.$fokat_html.'</li>';
        }

        $html .= '</ul>';
    }

    return $html;
}

function het_napja($nap){
    $napok = array('vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat');
    return $napok[$nap];
}

function _jog( $value ) {
    if( $_SESSION[get_db_tabla().'rights'][$value] ) {
        return $_SESSION[get_db_tabla().'rights'][$value];
    }

    return false;
}

function getStatuszIdo( $time ) {
    /* Eltelt napok */
    $napok = floor($time / 86400);

    /* Óra */
    $ora = floor(($time % 86400) / 3600);

    /* Perc */
    $perc = floor(($time % 86400 % 3600) / 60);

    /* Másodperc */
    $mp =  floor(($time % 86400 % 3600 % 60));

    return $napok.'nap '.sprintf('%02d', $ora).':'.sprintf('%02d', $perc).':'.sprintf('%02d', $mp);
}

/* Státusz hozzáadása */
function addStatusz( $tabla = false, $id = false, $stat_id = false, $erteklista_id = 0 ) {
    global $adatbazis_kapcsolat;

    $db_error = false;

    if( $tabla && $id && $stat_id ) {
        /* Lekérjük az az éppen aktuális státusz */
        $sql = $akt_statusz = $adatbazis_kapcsolat->s('recid insert_dt', $tabla, 'id='.$id, 'insert_dt desc', '1');

        if( $sql === false ) {
            $db_error = true;
        }

        if( $akt_statusz ) {
            $akt_statusz = $akt_statusz[0];

            /* Megállapítjuk a státusz idejét */
            $statusz_ido = round( time() - strtotime($akt_statusz['insert_dt']) );
            $statusz_ido = getStatuszIdo( $statusz_ido );

            /* Göngyölt státuszidő */
            $sql = $elso_statusz = $adatbazis_kapcsolat->s('insert_dt', $tabla, 'id='.$id, 'insert_dt asc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            if( $elso_statusz ) {
                $elso_statusz = $elso_statusz[0];

                $gongyolt_statido = round( time() - strtotime($elso_statusz['insert_dt']) );
                $gongyolt_statido = getStatuszIdo( $gongyolt_statido );
            }

            /* Összesített státuszidő */
            $sql = $elso_akt_stat = $adatbazis_kapcsolat->s('recid insert_dt', $tabla, 'id='.$id.' and stat_id='.$stat_id, 'insert_dt asc', '1');

            if( $sql === false ) {
                $db_error = true;
            }

            $ossz_statido = '';
            if( $elso_akt_stat ) {
                $elso_akt_stat = $elso_akt_stat[0];

                $ossz_statido = round( time() - strtotime($elso_akt_stat['insert_dt']) );
                $ossz_statido = getStatuszIdo( $ossz_statido );

                /* Frissítjük a sort */
                $sql = $adatbazis_kapcsolat -> u($tabla,
                    array('ossz_statido'),
                    array($ossz_statido ),
                    'id='.$id.' and stat_id='.$stat_id
                );

                if( $sql === false ) {
                    $db_error = true;
                }
            }

            /* Frissítjük a sort */
            $sql = $adatbazis_kapcsolat -> u($tabla,
                array('end_dt',             'statido',    'gongyolt_statido'),
                array( date('Y-m-d H:i:s'), $statusz_ido, $gongyolt_statido ),
                'recid='.$akt_statusz['recid']
            );

            if( $sql === false ) {
                $db_error = true;
            }

            /* Beszúrjuk az újat */
            $sql = $adatbazis_kapcsolat -> i($tabla,
                array('id',  'stat_id', 'erteklista_id',  'insert_dt',          'insert_usr',                              'update_dt',          'update_usr'                           ),
                array($id,   $stat_id,  $erteklista_id,    date('Y-m-d H:i:s'),  $_SESSION[get_db_tabla().'teljes_nev'],    date('Y-m-d H:i:s'), $_SESSION[get_db_tabla().'teljes_nev'] )
            );
            if( $sql === false ) {
                $db_error = true;
            }

            /* Az alap tábla státusz oszlopát is karban tartjuk */
            $alap_tabla = str_replace('_statusz', '', $tabla);
            $sql = $adatbazis_kapcsolat->u($alap_tabla, array('statusz_id'), array($stat_id), "`recid`='$id'");
            if( $sql === false ) {
                $db_error = true;
            }


            if( !$db_error ) {
                return true;
            } else {
                return false;
            }
        }
    }

    return false;
}

//megnevezésből a megfelelő tömb betöltése központosítva
function get_akt_tomb($megn){

    global $anyagigenyles_tomb;
    global $bejelentes_tomb;
    global $login_tomb;

    switch($megn){
        case 'anyagigenyles': $akt_tomb = $anyagigenyles_tomb;  break;
        case 'bejelentes'   : $akt_tomb = $bejelentes_tomb;     break;
        case 'login'        : $akt_tomb = $login_tomb;          break;
    }

    return $akt_tomb;
}

/* Az aktuálisan bejelentkezett felhasználóhoz rendelt szállodák ID_ját adja vissza. Ez többször előfroduló igén, azért van kiszervezve függvénybe. */
function get_akt_login_szallodai(){
    global $adatbazis_kapcsolat;

    $szallodak = $adatbazis_kapcsolat->s('telephely_id', 'telephely_kapcs', "`nev`='{$_SESSION[get_db_tabla().'user']}'");
    return adatbazis2tomb($szallodak, 'telephely_id');
}

//A kapott munkalapID-k alapján bejárjuk a munkalapokat. Ha adott munkalapon minden anyag státusza 0 (beérkezett), akkor maga a munkalap státusza folyamatbanra állítható
function munkalap_folyamatbanra_halehet($munkalapidk){

    global $adatbazis_kapcsolat;

    $db_error = false;

    foreach($munkalapidk as $mukalap_id){
        //Ha nincs olyan anyag, aminek a státusza ne 0 volna, akkor a munkalap státuszát folyamatbanra váltjuk
        $sql = $adatbazis_kapcsolat->s('recid', 'munkalap_anyag', "`munkalap_id`='$mukalap_id' AND `stat_id`!='0'");
        if( $sql === false ) {
            $db_error = true;
            return false;
        }
        if(!$sql){
            if(!addStatusz( 'munkalap_statusz', $mukalap_id, 2 )){
                $db_error = true;
            }
        }
    }

    if( !$db_error ) {
        return true;
    } else {
        return false;
    }

}
