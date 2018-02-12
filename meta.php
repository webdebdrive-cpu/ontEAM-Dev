<?php
//Az oldal title-ja
function get_title(){

    global $adatbazis_kapcsolat;

	$title = get_nev().' - ';
	//global $tartalom_tomb; Ha tartalom többől generálunk ki tartalmat

	switch(basename(self())){

        case 'index.php': 
            if(!$_SESSION[get_db_tabla().'login_recid']){
                $title .= 'Bejelentkezés';
            }else{
                $title = trim($title, ' - ');
            }
        break;
        case 'anyagigenyles.php':
            if (isset($_GET['anyagrend'])) {
                $title .= 'Anyagok / Anyagrendelések';
            }else{
                $title .= 'Anyagok / Anyagigénylések';
            }
        break;
        case 'bejelentes.php'               : $title .= 'Bejelentések / Bejelentések';                  break;
        case 'bejelentes_arch.php'          : $title .= 'Bejelentések / Archív bejelentések';           break;
        case 'beosztas.php'                 : $title .= 'Személyzet / Beosztások';                      break;
        case 'cash_flow.php'                : $title .= 'Projektek / Cash Flow';                        break;
        case 'cikktorzs.php'                : $title .= 'Anyagok / Cikktörzs';                          break;
        case 'dokumentumtar.php'            : $title .= 'Dokumentumok / Dokumentumtár';                 break;
        case 'eloterj_fb.php'               : $title .= 'Projektek / Előterjesztés/ FB előterjesztés';  break;
        case 'eloterj_bod.php'              : $title .= 'Projektek / Előterjesztés/ BoD előterjesztés'; break;
        case 'energia.php'                  : $title .= 'Energia';                                      break;
        case 'erteklista.php'               : $title .= 'Rendszer / Értéklisták';                       break;
        case 'eseti_fenntartas.php'         : $title .= 'Munkák / Eseti fenntartás';                    break;
        case 'eszkozkezeles.php'            : $title .= 'Eszközök / Eszközkezelés';                     break;
        case 'felmeresi_terv.php'           : $title .= 'Eszközök / Felmérési tervek';                  break;
        case 'fenntartasi_szerzodes.php'    : $title .= 'Dokumentumok / Fenntartási szerződések';       break;
        case 'ktghely.php'                  : $title .= 'Rendszer / Költséghelyek';                     break;
        case 'ktgnem.php'                   : $title .= 'Rendszer / Költségnemek';                      break;
        case 'login.php'                    : $title .= 'Rendszer / Felhasználók';                      break;
        case 'munkalap.php'                 : $title .= 'Munkák / Munkalapok';                          break;
        case 'munkalap_arch.php'            : $title .= 'Munkák / Archív munkalapok';                   break;
        case 'mobil_hozzarendel.php'        : $title .= 'Munkák / Munkalapok / Mobil hozzárendelé';     break;
        case 'utemezett_feladatok.php'      : $title .= 'Munkák / Ütemzett feladatok';                  break;
        case 'uzemeltetesi_naplo.php'       : $title .= 'Munkák / Üzemeltetési napló';                  break;
        case 'projektigenyles.php'          : $title .= 'Projektek / Projektigénylések';                break;
        case 'projektkezeles.php'           : $title .= 'Projektek / Projektek';                        break;
        case 'raktar.php'                   : $title .= 'Anyagok / Raktár';                             break;
        case 'rendszeres_karbantartas.php'  : $title .= 'Munkák / Rendszeres karbantartás';             break;
        case 'szabadsagigeny.php'           : $title .= 'Személyzet / Szabadságigények';                break;
        case 'szerepkor.php'                : $title .= 'Rendszer / Szerepkörök';                       break;
        case 'szerepkor_jog.php'            : $title .= 'Rendszer / Szerepkörök jogai';                 break;
        case 'szamla.php'                   : $title .= 'Dokumentumok / Számlák';                       break;
        case 'szallito.php'                 : $title .= 'Dokumentumok / Partnertörzs';                  break;
        case 'tervezes.php'                 : $title .= 'Tervezés';                                     break;
        case 'torzsadat.php'                : $title .= 'Személyzet / Törzsadatok';                     break;

	}

	return $title;
}

// Az oldal leírása
function get_description(){

    global $adatbazis_kapcsolat;

	$title = get_nev().' ';

	switch(basename(self())){
		case 'index.php':
        /*Minta kód, termékek kilistázására, kategóriájukra stb..
            if($_GET['id']){

                $_GET['id'] = sql_ertek($_GET['id']);

                $termek = $adatbazis_kapcsolat -> select('nev marka kat', 'termek', 'id='.$_GET['id']);
                $termek = $termek[0];

                $marka = $adatbazis_kapcsolat -> select('nev', 'marka', 'id='.$termek['marka']);
                $marka = $marka[0];

                $kat = $adatbazis_kapcsolat -> select('nev', 'kat', 'id='.$termek['kat']);
                $kat = $kat[0];

                $title .= $marka['nev'].' '.$termek['nev'].' '.$kat['nev'];

            }elseif(isset($_GET['kat'])){

                $_GET['kat'] = sql_ertek($_GET['kat']);
                $kategoria = $adatbazis_kapcsolat -> select('nev', 'kat', 'id='.$_GET['kat']);
                $kategoria = $kategoria[0];
                $title .= $kategoria['nev'];

            }elseif(isset($_GET['marka'])){

                 $_GET['marka'] = sql_ertek($_GET['marka']);
                 $marka = $adatbazis_kapcsolat -> select('nev', 'marka', 'id='.$_GET['marka']);
                 $marka = $marka[0];
                 $title .= $marka['nev'];

            }elseif(isset($_GET['ksz'])){

                $_GET['ksz'] = sql_ertek($_GET['ksz']);
                $title .= 'Találatok a '.$_GET['ksz'].' kulcsszóra';

            }else{

                $termekek = $adatbazis_kapcsolat -> select('nev', 'termek', 'kiemelt>0');
                foreach($termekek as $termek){
                    $title .= ' '.$termek['nev'];
                }

            }
            */

		break;

        case 'regisztracio.php':
            if (isset($_GET['elf'])) {
                $title .= 'Elfelejtett jelszó';
            }elseif (isset($_GET['login'])) {
                $title .= 'Bejelentkezés';
            }else{
                $title .= 'Regisztráció';
            }
        break;

        case 'kosar.php':
            $title .= 'Kosár';
        break;
	}

	return $title;
}

//Kulcsszavak
function get_keywords(){

    /*global $adatbazis_kapcsolat;

	$title = get_nev();

	switch(basename(self())){
		case 'index.php':
		break;
	}

	return $title;*/

    return get_description();

}


// Canonical URL
function get_canonical(){

// Teszteléshez: ha pretty URL-eket használunk, akkor így láthatjuk, hogy mi is a valódi PHP fájl, és milyen GET-eink vannak
if (isset($_GET['canon'])) {
    echo '<pre style="font-size: 1.4rem">';
    echo self();
    print_r($_GET);
    echo '</pre>';
}

	global $adatbazis_kapcsolat;

    $domain = get_http().get_domain();

	switch(basename(self())){

//        case 'index.php':
//
//            if (isset($_GET['id'])) {
//
//                $termek = $adatbazis_kapcsolat -> select('nevid', 'termek', 'id='.$_GET['id']);
//                $termek = $termek[0];
//                $link = $termek['nevid'].'/t/';
//                return '<link rel="canonical" href="'.$domain.$link.'">';
//
//            }else{
//
//                return '<link rel="canonical" href="'.$domain.'">';
//
//            }
//
//        break;

        default:
            return '<link rel="canonical" href="'.trim( trim( $domain.self().get_get(), '='), '?').'">';
        break;

	}
}

// Közösségioldalak metatagjai
function get_og(){

    global $adatbazis_kapcsolat;

    $title = get_title();
    $desc  = str_replace( '"', '&quot;', resz_string(notags(get_description()),300) );

    $og_meta = '';

    if(get_facebook()){
        //Megosztáskor látszani fog egy Like gomb is, amivel azonnal lájkolni lehet az oldalt
        $og_meta .= '<meta property="article:publisher" content="'.get_facebook().'">';
        //Megosztáskor látszani fog egy Follow gomb is, amivel azonnal követni lehet a profilt
        $og_meta .= '<meta property="article:author" content="'.get_facebook().'">';
    }

    $og_meta .= '<meta property="og:title" content="'.$title.'">';
    $og_meta .= '<meta property="og:description" content="'.$desc.'">';
    $og_meta .= '<meta property="og:url" content="'.get_http().str_replace('&', '&amp;', $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] ).'">';
    $og_meta .= '<meta property="og:type" content="company">';

    //Google plus: HTML5 alatt ez sem valid
//    $og_meta .= '<meta itemprop="name" content="'.$title.'">';
//    $og_meta .= '<meta itemprop="description" content="'.$desc.'">';


	switch(basename(self())){
//		case 'index.php':
//            $kep = get_http().get_domain().'/elemek/valami.png';
//            $og_meta .= '<meta property="og:image" content="'.$kep.'">';
//            $og_meta .= '<link rel="image_src" href="'.$kep.'">';
//            $og_meta .= '<meta itemprop="image" content="'.$kep.'">';
//		break;
	}

	return $og_meta;
}
?>