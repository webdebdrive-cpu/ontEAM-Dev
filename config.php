<?php
//WebDeb Engine v2.7 2016.12.22 Ádám

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


require_once 'konstansok.php';

//Adatbázis
date_default_timezone_set('Europe/Budapest');

function get_db_tabla()   {                                     //Alapértelmezetten '', de ha értéket adunk (pl.: tabla_), akkor létrehoz egy adatbázis kapcsolatot a 'Weblap' osztály
    switch($_SERVER['HTTP_HOST']){
        case 'teszt.onteam.hu'  : return 'onteam_teszt_';
        default                 : return 'onteam_';
    }
} 
function get_db_host()    { return ( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ? 'localhost'    : 'localhost'       );  } //Host
function get_db()         {
    switch($_SERVER['HTTP_HOST']){
        case 'localhost'            : return 'szinvatrendhu1';
        case 'demo.webdeb.hu'       : return 'wdbhu';
        case 'demo.szinvatrend.hu'  : return 'szinvatrendhu1';
        case 'onteam.hu'            : return 'onteam_live';
        case 'teszt.onteam.hu'	    : return 'onteam_live';
    }
}
function get_db_user() {
    switch($_SERVER['HTTP_HOST']){
        case 'localhost'            : return 'szinvatrendhu';
        case 'demo.webdeb.hu'       : return 'wdbtdb';
        case 'demo.szinvatrend.hu'  : return 'szinvatrendhu';
        case 'onteam.hu'            : return 'onteam_live';
        case 'teszt.onteam.hu'      : return 'onteam_live' ;
    }
}
function get_db_pass() {
    switch($_SERVER['HTTP_HOST']){
        case 'localhost'            : return 'vikike';
        case 'demo.webdeb.hu'       : return 'h3rbdzto';
        case 'demo.szinvatrend.hu'  : return 'vikike';
        case 'onteam.hu'            : return 'HMERDQgx0JHp124U';
        case 'teszt.onteam.hu'	    : return 'HMERDQgx0JHp124U';
    }
}

// Teszt környezetben vagyunk-e vagy sem
function get_teszt_rendszer() {
    $teszt_rendszerek = array('demo.webdeb.hu', 'demo.szinvatrend.hu', 'teszt.onteam.hu');
    if( in_array($_SERVER['HTTP_HOST'], $teszt_rendszerek) ) {
        return true;
    }
    return false;
}

function get_db_charset() { return 'UTF8';                                      } // Karakterkódolás (Figyelem! UTF-8 helyett itt UTF8)
function get_debug()      { return false;                                       } // Hibás adatbázislekérdezések megjelennek vagy sem
function get_ut_logfajl() { return 'uf_utemezo.err';                            } // Ütemező logfájlja

// Weblap
function get_admin()         { return 'admin/';                                 } // Admin könyvtár neve
function get_afa()           { return 27;                                       }
function get_alapnyelv()     { return 'hu'/*pl. hu, en, de*/;                   } // Az oldal alapértelmezett nyelve mi legyen? Ez kerül a meta tagokba. A többnyelvűsítést a get_nyelvek()-nél kell beállítani.
function get_alk()           { return
                   ($_SERVER['HTTP_HOST']=='localhost') ? '' : '';              } // Rewritre rule localhoston is működjön, itt a www mappán belüli mappát add meg
function get_country()       { return 'Hungary';                                } // Pl.: Hungary
function css_img()           { return true;                                     } //Ha igaz, akkor a CSS-ben levő képeket DATA URI-ként beágyazza a CSS tartalmába, egyébént marad sima hivatkozás
function get_domain()        { return 'www.onteam.hu';                          }
function get_mail($kiir)     { return !$kiir ? 'noreply@onteam.hu' :
           mail_rejtes('<a href="mailto:'.get_mail().'">'.get_mail().'</a>');   }
function get_megsz()         { return 'magaz';                                  }
function get_mail_charset()  { return 'UTF-8';                                  } //ISO-8859-1, ISO-8859-2, Windows-1250, Windows-1251
function get_nev()           { return 'onTEAM';                                 }
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

// Social linkek
function get_facebook()     { return '';   }    //https://www.facebook.com/!FACEBOOK_PROFIL_LINKJE!
function get_twitter()      { return '';   }    //https://twitter.com/!TWITTER_PROFIL_LINKJE!
function get_google()       { return '';   }    //https://plus.google.com/!GOOGLE_PROFIL_LINKJE!

// Letöltésnél nem engedünk meg minden kiterjesztést letölteni
function get_engedelyezett_letoltesek()  { return array('txt'); }

function tranzakcioHiba() { return 'Az adatbázis műveleteket nem lehetett végrehajtani, kérem próbálja újra a folyamatot!'; }

// Head
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
                $utvonal.'js/moment.min.js',
                $utvonal.'js/fullcalendar.min.js',
                $utvonal.'js/hu.js',
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

// Képek
function get_mini_sz()    { return 1;  }
function get_mini_m()     { return 1;  }
function get_indexkep_sz(){ return 2;  }
function get_indexkep_m() { return 2;  }
function get_kep_sz()     { return 3;  }
function get_kep_m()      { return 3;  }
function get_nagykep_sz() { return 4;  }
function get_nagykep_m()  { return 4;  }

// Ajaxból meghívott változókban pár speciális karaktert visszaállítunk (Az esamp2es JS függvényben alakítjuk ezeket át.)
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