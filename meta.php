<?php
// Az oldal title-ja
function get_title(){

	$title = get_nev().' - ';
    if( $_SERVER['HTTP_HOST'] == 'teszt.onteam.hu' ) {
        $title = '[teszt] '.$title;
    }
    if( $_SERVER['HTTP_HOST'] == 'dev.onteam.hu' ) {
        $title = '[dev] '.$title;
    }

	switch ( basename(self()) ) {

        case 'index.php': 
            if( !$_SESSION[get_db_tabla() . 'login_recid'] ) {
                $title .= 'Bejelentkezés';
            } else {
                $title = trim($title, ' - ');
            }
        break;
        case 'anyagigenyles.php':
            if( isset($_GET['anyagrend']) ) {
                $title .= 'Anyagrendelések';
            } else {
                $title .= 'Anyagigénylések';
            }
        break;
        case 'anyag_szamla.php'             : $title .= 'Anyag számlák';            break;
        case 'bejelentes.php'               : $title .= 'Bejelentések';             break;
        case 'bejelentes_arch.php'          : $title .= 'Archív bejelentések';      break;
        case 'beosztas.php'                 : $title .= 'Beosztások';               break;
        case 'cash_flow.php'                : $title .= 'Cash Flow';                break;
        case 'cikktorzs.php'                : $title .= 'Cikktörzs';                break;
        case 'dokumentumtar.php'            : $title .= 'Dokumentumtár';            break;
        case 'eloterj_fb.php'               : $title .= 'FB előterjesztés';         break;
        case 'eloterj_bod.php'              : $title .= 'BoD előterjesztés';        break;
        case 'energia.php'                  : $title .= 'Energia';                  break;
        case 'energia_szamla.php'           : $title .= 'Energia számlák';          break;
        case 'energia_terv.php'             : $title .= 'Energia egységárak';       break;
        case 'erteklista.php'               : $title .= 'Értéklisták';              break;
        case 'eseti_fenntartas.php'         : $title .= 'Eseti fenntartás';         break;
        case 'eszkozkezeles.php'            : $title .= 'Eszközkezelés';            break;
        case 'felmeresi_terv.php'           : $title .= 'Felmérési tervek';         break;
        case 'fenntartasi_szamla.php'       : $title .= 'Fenntartási számla';       break;
        case 'fenntartasi_szerzodes.php'    : $title .= 'Fenntartási szerződések';  break;
        case 'fenntartasi_terv.php'         : $title .= 'Fenntartási tervek';       break;
        case 'ktghely.php'                  : $title .= 'Költséghelyek';            break;
        case 'ktgnem.php'                   : $title .= 'Költségnemek';             break;
        case 'login.php'                    : $title .= 'Felhasználók';             break;
        case 'munkalap.php'                 : $title .= 'Munkalapok';               break;
        case 'munkalap_arch.php'            : $title .= 'Archív munkalapok';        break;
        case 'mobil_hozzarendel.php'        : $title .= 'Mobil hozzárendelés';      break;
        case 'utemezett_feladatok.php'      : $title .= 'Ütemzett feladatok';       break;
        case 'uzemeltetesi_naplo.php'       : $title .= 'Üzemeltetési napló';       break;
        case 'projektigenyles.php'          : $title .= 'Projektigénylések';        break;
        case 'projektkezeles.php'           : $title .= 'Projektek';                break;
        case 'projektszerzodes.php'         : $title .= 'Projekt szerződések';      break;
        case 'projektszamla.php'            : $title .= 'Projekt számlák';          break;
        case 'raktar.php'                   : $title .= 'Raktár';                   break;
        case 'rendszeres_karbantartas.php'  : $title .= 'Rendszeres karbantartás';  break;
        case 'szabadsagigeny.php'           : $title .= 'Szabadságigények';         break;
        case 'szerepkor.php'                : $title .= 'Szerepkörök';              break;
        case 'szerepkor_jog.php'            : $title .= 'Szerepkörök jogai';        break;
        case 'szamla.php'                   : $title .= 'Számlák';                  break;
        case 'szallito.php'                 : $title .= 'Partnertörzs';             break;
        case 'tervezes.php'                 : $title .= 'Tervezés';                 break;
        case 'torzsadat.php'                : $title .= 'Törzsadatok';              break;

	}

	return $title;
}

// Az oldal leírása
function get_description() {
    return get_title();
}

// Kulcsszavak
function get_keywords() {
    return get_title();
}


// Canonical URL
function get_canonical() {

    $domain = get_http().get_domain();

	switch ( basename(self()) ) {

        default:
            return '<link rel="canonical" href="'.$domain.htmlspecialchars($_SERVER['REQUEST_URI']).'">';
        break;

	}
}

// Közösségioldalak metatagjai
function get_og() {

    global $adatbazis_kapcsolat;

    $title = get_title();
    $desc  = str_replace( '"', '&quot;', resz_string(notags(get_description()),300) );

    $og_meta = '';

    if( get_facebook() ) {
        // Megosztáskor látszani fog egy Like gomb is, amivel azonnal lájkolni lehet az oldalt
        $og_meta .= '<meta property="article:publisher" content="'.get_facebook().'">';
        // Megosztáskor látszani fog egy Follow gomb is, amivel azonnal követni lehet a profilt
        $og_meta .= '<meta property="article:author" content="'.get_facebook().'">';
    }

    $og_meta .= '<meta property="og:title" content="'.$title.'">';
    $og_meta .= '<meta property="og:description" content="'.$desc.'">';
    $og_meta .= '<meta property="og:url" content="'.get_http().str_replace('&', '&amp;', $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] ).'">';
    $og_meta .= '<meta property="og:type" content="company">';

    // Google plus: HTML5 alatt ez sem valid
//    $og_meta .= '<meta itemprop="name" content="'.$title.'">';
//    $og_meta .= '<meta itemprop="description" content="'.$desc.'">';


	switch ( basename(self()) ) {
//		case 'index.php':
//            $kep = get_http().get_domain().'/elemek/valami.png';
//            $og_meta .= '<meta property="og:image" content="'.$kep.'">';
//            $og_meta .= '<link rel="image_src" href="'.$kep.'">';
//            $og_meta .= '<meta itemprop="image" content="'.$kep.'">';
//		break;
	}

	return $og_meta;
}
