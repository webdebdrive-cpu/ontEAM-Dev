<?php
//WebDeb Engine v2.7 2016.12.22

/*                                                                                                                          *\
  Tudnivalók
  Ide írj fel általános tudnivalóka az oldallal kapcsolatosan. Pl. ha árösszehasonlító oldalakra vagy számlázó programhoz
  készült szinkron fájl, akkor melyik PHP felel érte, milyen gyakran kell futtatni.
\*                                                                                                                          */


$weblap_indul = microtime(true);

ini_set('log_errors',       '1'             );
ini_set('error_log',        '!Errors.txt'   );  //Melyik fájlba íródjanak a hibaüzik
ini_set('display_errors' ,  '0'             );
error_reporting(E_ERROR);                       //Csak a hibákat írja ki


/*
Session élettartam beállítása
$cookie_timeout  = 60 * 60 * 9;
$garbage_timeout = $cookie_timeout + 600;

session_set_cookie_params($cookie_timeout);             //session.cookie_lifetime értékét állítja be
ini_set('session.gc_maxlifetime', $garbage_timeout);    //session.gc_maxlifetime értékét állítja be

*/

/*
set_time_limit(3600); //Kitoljuk a time limit-et egy órára
*/

//Adatbázis

function get_db_tabla()   { return 'hotel_';                                                                            } //Alapértelmezetten '', de ha értéket adunk (pl.: tabla_), akkor létrehoz egy adatbázis kapcsolatot a 'Weblap' osztály
function get_db_host()    { return ( $_SERVER['HTTP_HOST'] == 'localhost' ? 'localhost'         : 'localhost' );        } //Host
function get_db()         { return ( $_SERVER['HTTP_HOST'] == 'localhost' ? 'szinvatrendhu1'    : 'szinvatrendhu1' );   } //Adatbázis neve
function get_db_user()    { return ( $_SERVER['HTTP_HOST'] == 'localhost' ? 'szinvatrendhu'     : 'szinvatrendhu' );    } //Felhasználói név
function get_db_pass()    { return ( $_SERVER['HTTP_HOST'] == 'localhost' ? 'vikike'            : 'vikike' );           } //Jelszó
function get_db_charset() { return 'UTF8';                                                                              } //Karakterkódolás (Figyelem! UTF-8 helyett itt UTF8)
function get_debug()      { return false;                                                                                } //Hibás adatbázislekérdezések megjelennek vagy sem

//Weblap
function get_admin()         { return 'admin/';                                 } // Admin könyvtár neve
function get_afa()           { return 27;                                       }
function get_alapnyelv()     { return 'hu'/*pl. hu, en, de*/;                   } // Az oldal alapértelmezett nyelve mi legyen? Ez kerül a meta tagokba. A többnyelvűsítést a get_nyelvek()-nél kell beállítani.
function get_alk()           { return
                   ($_SERVER['HTTP_HOST']=='localhost') ? '' : '';            } // Rewritre rule localhoston is működjön, itt a www mappán belüli mappát add meg
function get_country()       { return 'Hungary';                                     } // Pl.: Hungary
function css_img()           { return true;                                     } //Ha igaz, akkor a CSS-ben levő képeket DATA URI-ként beágyazza a CSS tartalmába, egyébént marad sima hivatkozás
function get_domain()        { return 'www.danubiushotel.hu';                        }
function get_mail($kiir)     { return !$kiir ? 'info@danubiushotel.hu' :
           mail_rejtes('<a href="mailto:'.get_mail().'">'.get_mail().'</a>'); }
function get_megsz()         { return 'magaz';                                  }
function get_mail_charset()  { return 'UTF-8';                                  } //ISO-8859-1, ISO-8859-2, Windows-1250, Windows-1251
function get_nev()           { return 'Danubius Hotel';                             }
function get_nyelvek()       { return array();                                  } //Az oldal nyelvei pl: array('hu', 'en'). Ha üres, akkor egynyelvű az oldal. Ha többnyelvű, akkor a $_SESSION['nyelv'] változó tartalmazza, hogy épp melyik nyelven is vagyunk ez alapján lehet ketté bontani a tartalmat (pl.: if($_SESSION['nyelv']==hu){...}). Az oldalon megtalálható összes szöveget érdemes kimenteni egy nagy tömbbe, tartalom.php-ba (részletes leírás ott). A képelemeket pedig érdemes valami_'.$_SESSION['nyelv'].'.png formában kirajzolni.
function get_nyelv_detek()   { return true;                                     } //Ha ez igaz, akkor a $_SERVER['HTTP_ACCEPT_LANGUAGE'] alapján a $_SESSION['nyelv'] automatikusan megkapja az adott nyelvet
function gu()                { return get_utvonal();                            }
function get_utvonal()       { return 'tartalom/';                              }
function get_penznem()       { return 'Ft';                                     }
function get_session()       { return true;                                     }
function get_responsive()    { return false;                                    } //Amennyiben az oldal responzív, úgy a meta tagokban tiltjuk az oldal nagyíthatóságát (mobilon nem zoom-olunk hanem responzívek vagyunk)
function get_session_felh()  { return get_db_tabla().'felhId';                  }
function get_teszt_mail()    { return 'teszt@webdeb.hu';                        }
function get_teszt_mail_nev(){ return 'WebDeb Teszt';                           }
function get_web_charset()   { return 'UTF-8';                                  }

function get_title_hossz()  { return 75;                                        } //Title max mekkora legyen - ha 0, akkor korlátlan
function get_desc_hossz()   { return 190;                                       } //Desc max mekkora legyen - ha 0, akkor korlátlan
function get_key_hossz()    { return 200;                                       } //Key max mekkora legyen - ha 0, akkor korlátlan

//Social linkek
function get_facebook()     { return '';   }    //https://www.facebook.com/!FACEBOOK_PROFIL_LINKJE!
function get_twitter()      { return '';   }    //https://twitter.com/!TWITTER_PROFIL_LINKJE!
function get_google()       { return '';   }    //https://plus.google.com/!GOOGLE_PROFIL_LINKJE!

//Letöltésnél nem engedünk meg minden kiterjesztést letölteni
function get_engedelyezett_letoltesek()  { return array('txt'); }

function tranzakcioHiba() { return 'Az adatbázis műveletek részét, vagy egészét nem lehetett végrehajtani, kérem próbálja újra a folyamatot!'; }

//Head
function get_doctype() { return '';                 } //transitional, frameset (egyébként strict)
function get_noskype() { return true;               }
function get_language(){ return get_alapnyelv();    }
function get_favicon() {
    if(is_file('favicon.ico')){
        return 'favicon.ico';
    }else{
        return '';
    }
}
function get_jsfajlok(){

	$utvonal = '';
	return array(
		'js' => array(
            $utvonal.'!js/jquery.js',
            $utvonal.'js/jquery.easing.1.3.js',
            $utvonal.'js/scrollto.js',
            $utvonal.'js/main.js',
            $utvonal.'js/esemenyek.js',
            $utvonal.'js/alert.js',
            //{KOVETKEZO_JS}
		),

		'css' => array(
            $utvonal.'css/nullaz.css',
            $utvonal.'css/altalanos.css',
            $utvonal.'css/tartalom.css',
            $utvonal.'css/respo.css',
            $utvonal.'css/alert.css',
            $utvonal.'css/fancybox.css',
            //{KOVETKEZO_CSS}
		)
	);
}

function get_jsfajlok_admin(){

	$utvonal = '../';
	$js = array(
                $utvonal.'!js/jquery.js',
                $utvonal.'!js/jquery-ui.js',
                $utvonal.'!js/jquery.easing.1.3.js',
                $utvonal.'!js/datetimepicker.js',
                $utvonal.'!js/admin.js',
                $utvonal.'!js/admin_sorrendez.js',
                $utvonal.'js/fancybox.js',
                $utvonal.'js/main.js',
                $utvonal.'js/alert_admin.js',
                $utvonal.'js/autocomplete.js',
                $utvonal.'js/menu.js',
                $utvonal.'js/feltolt.js',
                $utvonal.'js/scrollto.js',
                $utvonal.'js/jquery.easing.1.3.js',
                $utvonal.'js/admin_sorrendez.js',
                //{KOVETKEZO_ADMIN_JS}
		);

	$css = array(
                $utvonal.'css/nullaz.css',
                $utvonal.'css/12.css',
                $utvonal.'css/alert_admin.css',
                $utvonal.'css/datetimepicker.css',
                $utvonal.'css/autocomplete.css',
                $utvonal.'css/admin_menu.css',
                $utvonal.'css/admin.css',
                $utvonal.'css/lapozas_admin.css',
                $utvonal.'css/fancybox_admin.css',
                $utvonal.'css/feltolt.css',
                $utvonal.'css/tooltip.css',
                //{KOVETKEZO_ADMIN_CSS}
		);

	return array( 'js' => $js, 'css' => $css );
}

//Képek
function get_mini_sz()    { return 1;  }
function get_mini_m()     { return 1;  }
function get_indexkep_sz(){ return 2;  }
function get_indexkep_m() { return 2;  }
function get_kep_sz()     { return 3;  }
function get_kep_m()      { return 3;  }
function get_nagykep_sz() { return 4;  }
function get_nagykep_m()  { return 4;  }

//Ajaxból meghívott változókban pár speciális karaktert visszaállítunk (Az esamp2es JS függvényben alakítjuk ezeket át.)
foreach($_POST as $kulcs => $post){
    $_POST[$kulcs] = str_replace('!ES!',    '&', $_POST[$kulcs]);
    $_POST[$kulcs] = str_replace('|ES|',    '&', $_POST[$kulcs]); // Erre is szükség van, az admin WYSIWYG szerkesztője miatt
    $_POST[$kulcs] = str_replace('!PLUSZ!', '+', $_POST[$kulcs]);
    $_POST[$kulcs] = str_replace('!SHARP!', '#', $_POST[$kulcs]);
    $_POST[$kulcs] = stripslashes($_POST[$kulcs]);
}
foreach($_GET as $kulcs => $get){
    $_GET[$kulcs] = str_replace('!ES!',    '&', $_GET[$kulcs]);
    $_GET[$kulcs] = str_replace('|ES|',    '&', $_GET[$kulcs]); // Erre is szükség van, az admin WYSIWYG szerkesztője miatt
    $_GET[$kulcs] = str_replace('!PLUSZ!', '+', $_GET[$kulcs]);
    $_GET[$kulcs] = str_replace('!SHARP!', '#', $_GET[$kulcs]);
    $_GET[$kulcs] = stripslashes($_GET[$kulcs]);
}

?>
