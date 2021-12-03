<?php
// WebDeb Engine v2.7 2016.12.22
$weblap_indul = microtime(true);

require_once 'konstansok.php';

// Időzóna beállítása
date_default_timezone_set('Europe/Budapest');

// Ne legyen logolás, csak demo felületen!
if( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' || $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' || $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
    ini_set('log_errors',       '1'             );
    ini_set('display_errors' ,  '1'             );
    error_reporting(E_ERROR);
} else {
    ini_set('log_errors',       '0'             );
    ini_set('display_errors' ,  '0'             );
    error_reporting(E_ERROR);
}

 // Alapértelmezetten '', de ha értéket adunk (pl.: tabla_), akkor létrehoz egy adatbázis kapcsolatot a 'Weblap' osztály
function get_db_tabla()   {     return 'onteam_';      }


function get_db_host()    { return ( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ? 'localhost'    : 'localhost'       );  } //Host
function get_db()         {
    switch ( $_SERVER['HTTP_HOST'] ) {
        case 'localhost'            : return 'wdbtesthu';
        case 'demo.webdeb.hu'       : return 'wdbhu_onteam';
        case 'demo.szinvatrend.hu'  : return 'szinvatrendhu1';
        case 'www.onteam.hu'        : return 'onteam_live';
        case 'onteam.hu'            : return 'onteam_live';
        case 'teszt.onteam.hu'      : return 'onteam_teszt';
        case 'dev.onteam.hu'        : return 'onteam_dev';
    }
}
function get_db_user() {
    switch ( $_SERVER['HTTP_HOST'] ) {
        case 'localhost'            : return 'krixx';
        case 'demo.webdeb.hu'       : return 'wdbhu_onteam';
        case 'demo.szinvatrend.hu'  : return 'szinvatrendhu';
        case 'www.onteam.hu'        : return 'onteam_live';
        case 'onteam.hu'            : return 'onteam_live';
        case 'teszt.onteam.hu'      : return 'onteam_teszt' ;
        case 'dev.onteam.hu'        : return 'onteam_teszt' ;
    }
}
function get_db_pass() {
    switch($_SERVER['HTTP_HOST']){
        case 'localhost'            : return 'e8zfZWdYO1ew';
        case 'demo.webdeb.hu'       : return 'Cr#X)GWq(k6j';
        case 'demo.szinvatrend.hu'  : return 'vikike';
        case 'www.onteam.hu'        : return 'HMERDQgx0JHp124U';
        case 'onteam.hu'            : return 'HMERDQgx0JHp124U';
        case 'teszt.onteam.hu'      : return 'iChtTeSbI4GXIlXg';
        case 'dev.onteam.hu'        : return 'iChtTeSbI4GXIlXg';
    }
}
function get_url_elotag() {
    $konyvtar = dirname(self());
    switch ( $_SERVER['HTTP_HOST'] ) {
        case 'localhost'            : return 'localhost/';
        case 'demo.webdeb.hu'       : return 'http://demo.webdeb.hu/danubiushotel.hu'.$konyvtar.'/';
        case 'demo.szinvatrend.hu'  : return 'https://demo.szinvatrend.hu'.$konyvtar.'/';
        case 'teszt.onteam.hu'      : return 'https://teszt.onteam.hu'.$konyvtar.'/';
        case 'dev.onteam.hu'        : return 'https://dev.onteam.hu'.$konyvtar.'/';
        case 'onteam.hu'            : return 'https://www.onteam.hu'.$konyvtar.'/';
        case 'www.onteam.hu'        : return 'https://www.onteam.hu'.$konyvtar.'/';
    }
}

// Teszt környezetben vagyunk-e vagy sem
function get_teszt_rendszer() {
    $teszt_rendszerek = array('demo.webdeb.hu', 'demo.szinvatrend.hu', 'teszt.onteam.hu', 'dev.onteam.hu');
    if( in_array($_SERVER['HTTP_HOST'], $teszt_rendszerek) ) {
        return true;
    }
    return false;
}


function get_verzio()     { return 'V1.192';                                    } // Fejlécben szereplő verzió szám
function get_db_charset() { return 'UTF8';                                      } // Karakterkódolás (Figyelem! UTF-8 helyett itt UTF8)
function get_debug()      { return false;                                       } // Hibás adatbázislekérdezések megjelennek vagy sem
function get_debug_ip()   { return '81.182.54.194';                              } // Erről az IP címről nézve az oldalt íratható ki hibaüzenet
//
// Weblap
function get_admin()         { return 'admin/';                                 } // Admin könyvtár neve
function get_afa()           { return 27;                                       }
function get_alapnyelv()     { return 'hu'/*pl. hu, en, de*/;                   } // Az oldal alapértelmezett nyelve mi legyen? Ez kerül a meta tagokba. A többnyelvűsítést a get_nyelvek()-nél kell beállítani.
function get_alk()           { return
                   ($_SERVER['HTTP_HOST']=='localhost') ? '' : '';              } // Rewritre rule localhoston is működjön, itt a www mappán belüli mappát add meg
function get_country()       { return 'Hungary';                                } // Pl.: Hungary
function css_img()           { return true;                                     } //Ha igaz, akkor a CSS-ben levő képeket DATA URI-ként beágyazza a CSS tartalmába, egyébént marad sima hivatkozás
function get_domain()        {
    switch ( $_SERVER['HTTP_HOST'] ) {
        case 'localhost'            : return 'localhost/';
        case 'demo.webdeb.hu'       : return 'demo.webdeb.hu/danubiushotel.hu';
        case 'demo.szinvatrend.hu'  : return 'demo.szinvatrend.hu';
        case 'teszt.onteam.hu'      : return 'teszt.onteam.hu';
        case 'dev.onteam.hu'        : return 'dev.onteam.hu';
        case 'onteam.hu'            : return 'www.onteam.hu';
        case 'www.onteam.hu'        : return 'www.onteam.hu';
    }
}
function get_http()          {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        return 'https://';
    } else {
        return 'http://';
    }
}
function get_sorrend_tomb_max_meret()   { return 5000;      } // CSV exportba csak úgy tudjuk a képernyő sorrendjét lehozni, ha egy ORDER BY FIELD rendezéssel a már ismert sorrendben kérjük le a mezőket. De nagyon hosszú lista esetén ez az SQL ki tud akadni, ezért limitáljuk,hogy ezt hány soron át tesszük meg.
function get_logfile_max_meret()        { return 2097152;   }
function get_mail($kiir = '')           { return !$kiir ? 'noreply@onteam.hu' :
           mail_rejtes('<a href="mailto:'.get_mail().'">'.get_mail().'</a>');   }
function get_megsz()         { return 'magaz';                                  }
function get_mail_charset()  { return 'UTF-8';                                  } // ISO-8859-1, ISO-8859-2, Windows-1250, Windows-1251
function get_nev()           { return 'ontEAM';                                 }
function get_nyelvek()       { return array();                                  } // Az oldal nyelvei pl: array('hu', 'en'). Ha üres, akkor egynyelvű az oldal. Ha többnyelvű, akkor a $_SESSION['nyelv'] változó tartalmazza, hogy épp melyik nyelven is vagyunk ez alapján lehet ketté bontani a tartalmat (pl.: if($_SESSION['nyelv']==hu){...}). Az oldalon megtalálható összes szöveget érdemes kimenteni egy nagy tömbbe, tartalom.php-ba (részletes leírás ott). A képelemeket pedig érdemes valami_'.$_SESSION['nyelv'].'.png formában kirajzolni.
function get_nyelv_detek()   { return true;                                     } // Ha ez igaz, akkor a $_SERVER['HTTP_ACCEPT_LANGUAGE'] alapján a $_SESSION['nyelv'] automatikusan megkapja az adott nyelvet
function gu()                { return get_utvonal();                            }
function get_utvonal()       { return 'tartalom/';                              }
function get_pszk_utvonal()  { return '../'.get_utvonal().'projektszamla/p_atad_dt/'; } // Számlát amikor PSzK-ba küldik, akkor készül ideiglensen egy XML, ami asznkronhoz kell. Ezeket az ideiglenes XML-ket ebben a könyvtárban tároljuk.
function get_penznem()       { return 'Ft';                                     }
function get_session()       { return true;                                     }
function get_kerturl_elotag(){ return '/hotel/admin/';                          } // WebDeb demo felületéhez kell

function get_respos_oldalak() {
    return array(
        'index.php',
        'bejelentes.php',
        'bejelentes_arch.php',
        'munkalap.php',
        'munkalap_arch.php',
    );
}
// Amennyiben az oldal responzív, úgy a meta tagokban tiltjuk az oldal nagyíthatóságát (mobilon nem zoom-olunk hanem responzívek vagyunk)
function get_responsive()    {
    if( in_array(basename(self()), get_respos_oldalak()) ) {
        return true;
    }

    return false;
}
function get_session_felh()  { return get_db_tabla().'felhId';                  }
function get_teszt_mail()    { return 'teszt@webdeb.hu';                        }
function get_mszk_mail(){
    if( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ) {
        return WEBDEB_TESZT_MAIL;
    } else if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' ) {
        return SZINVATREND_TESZT_MAIL;
    } else {
        return MSZK_MAIL_1;
    }
}
function get_mszk_mail2(){
    if( $_SERVER['HTTP_HOST'] == 'demo.webdeb.hu' ) {
        return WEBDEB_TESZT_MAIL;
    } else if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' ) {
        return SZINVATREND_TESZT_MAIL;
    } else {
        return MSZK_MAIL_2;
    }
}
function get_teszt_mail_nev(){ return 'WebDeb Teszt';                           }
function get_web_charset()   { return 'UTF-8';                                  }

function eles_es_teszt_rendszer_e() {
    if( $_SERVER['HTTP_HOST'] == 'www.onteam.hu' || $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
        return true;
    } else {
        return false;
    }
}

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
    if( is_file('/favicon.ico') ) {
        return 'favicon.ico';
    } else {
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
                $utvonal.'!js/jquery.ismobile.js',
                $utvonal.'!js/jquery.serializeJSON.js',
                $utvonal.'!js/jquery.sortElements.js',
                $utvonal.'!js/jquery.easing.1.3.js',
                $utvonal.'!js/datetimepicker.js',
                $utvonal.'!js/admin.js',
                $utvonal.'!js/admin_sorrendez.js',
                $utvonal.'!js/jquery.dragtable.js',
                $utvonal.'!js/table.js',
                $utvonal.'js/fancybox.js',
                $utvonal.'js/main.js',
                $utvonal.'js/alert_admin.js',
                $utvonal.'js/autocomplete.js',
                $utvonal.'js/menu.js',
                $utvonal.'js/feltolt.js',
                $utvonal.'js/scrollto.js',
                $utvonal.'js/jquery.easing.1.3.js',
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
                $utvonal.'css/dragtable.css',
                $utvonal.'css/responsive.css', // responsive.css - tartalma még folytatódik egy responsive_mobil.css-ben, de azt nem húzzuk be minden menüpon alatt.
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
    if( $kulcs != 'targyak' ) {
        $_POST[$kulcs] = stripslashes($_POST[$kulcs]);
    }
}
foreach($_GET as $kulcs => $get){
    $_GET[$kulcs] = str_replace('!ES!',    '&', $_GET[$kulcs]);
    $_GET[$kulcs] = str_replace('|ES|',    '&', $_GET[$kulcs]); // Erre is szükség van, az admin WYSIWYG szerkesztője miatt
    $_GET[$kulcs] = str_replace('!PLUSZ!', '+', $_GET[$kulcs]);
    $_GET[$kulcs] = str_replace('!SHARP!', '#', $_GET[$kulcs]);
    $_GET[$kulcs] = stripslashes($_GET[$kulcs]);
}
