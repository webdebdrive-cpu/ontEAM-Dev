<?php
/**
* Weblap osztály 
* Létrehoz egy weboldalt újabb osztályok meghívásával (pl. header vagy adatbázis osztály)
* 
* @author www.WebDeb.hu - Bollmann Krisztián 2009.06.03
*/

class Weblap{

	/** @var object Egy adatbázis objektum. Csak akkor kap értéket, ha $db_prefix nem üres*/
	public $adatbazis       = '';
	
	/** @var string Az oldal alapértelmezett nyelve (pl.: hu vagy en).*/
	public $alap_nyelv      = '';
	
	/** @var string Adatbázis tábla prefixe. Ha üres, akkor az oldal nem használ adatbázist*/
	public $db_prefix       = '';
    
	/** @var string Az oldal hivatalos domain neve*/
	public $domain          = '';
	
	/** @var int A legkisebb (mini) képek szélessége*/
	public $kep_mini_szel   = 100;
	
	/** @var int A legkisebb (mini) képek magassága*/
	public $kep_mini_magas  = 100;
	
	/** @var int Indexképek szélessége*/
	public $kep_index_szel  = 200;
	
	/** @var int Indexképek magassága*/
	public $kep_index_magas = 200;
	
	/** @var int Átlagos kép szélessége*/
	public $kep_szel        = 400;
	
	/** @var int Átlagos kép magassága*/
	public $kep_magas       = 400;		
	
	/** @var int Nagyított kép szélessége*/
	public $kep_nagy_szel   = 1024;
	
	/** @var int Nagyított kép magassága*/
	public $kep_nagy_magas  = 768;
	
	/** @var string Az oldal hivatalos e-mail címe*/
	public $mail            = '';
	
	/** @var string Levelek karakterkódolása (ISO-8859-2, ISO-8859-1, Windows-1250, Windows-1252)*/
	public $mail_charset    = 'ISO-8859-2';
	
	/** @var string megszólítás (magaz, tegez)*/
	public $megszolitas     = 'magaz';
    
	/** @var string Az oldal neve*/
	public $nev             = '';
	
	/** @var array Az oldal nyelvei. Ha üres, akkor egynyelvű az oldal*/
	public $nyelvek         = array();
	
	/** @var boolean Ha igazra állítjuk, akkor a $_SESSION['nyelv']-et a böngésző nyelvbeállítása alapján határozza meg.
		Ha sikertelen, akkor az $alap_nyelv-vel lesz az alapértelmezett
	*/
	public $nyelv_detektal  = false;
	
    /** @var string Az a mappa, melyben további mappákba rendezzük a dinamikus tartalmat (első sorban képeket)*/
    public $utvonal         = 'tartalom';
    
	/** @var boolean Sessiont használ az oldal vagy sem*/
	public $session         = true;
	
	/** @var string A weboldal karakterkódolása (ISO-8859-2, ISO-8859-1, Windows-1250, Windows-1252)*/
	public $web_charset     = 'UTF-8';
		

	
    /**
    * @desc Konstruktor, nem csinál semmi egyebet
    */
   	public function __construct(){}

    /**
    * @desc Tulajdonásgok ellenőrzése, kezdeti beállítások végrehajtása (pl.: nyelvfigyelés, adatbázis létrehozása ha kell)
    */	
	public function indit(){
		
		if( !$this -> domain        || $this -> domain == 'www.domain.hu'   ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Még nincs beállítva a hivatalos domain név, az oldal nem indítható. config.php-ban pótold!';   exit(); }
		if( !$this -> mail          || $this -> mail   == 'info@domain.hu'  ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Még nincs beállítva a hivatalos e-mail cím, az oldal nem indítható. config.php-ban pótold!';   exit(); }
		if( !is_valid_mail($this -> mail)                                   ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Helytelen e-mail cím van beállítva a config.php-ban!';                                         exit(); }
		if( !$this -> nev           || $this -> nev    == 'Cég neve'        ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Még nincs beállítva az oldal neve/címe, így az nem indítható. config.php-ban pótold!';         exit(); }
        if( !$this -> alap_nyelv                                            ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Még nincs beállítva az oldal nyelve, így az nem indítható. config.php-ban pótold!';            exit(); }
        if( get_country() == ''                                             ){ header('Content-Type: text/html; charset=UTF-8'); echo 'Még nincs beállítva az alap Country. config.php-ban pótold!ó, magyar oldal esetén Hungary!';   exit(); }
		
		//Ha sessiont használunk vagy nyelvet, akkor session_start
		if( ($this -> session || $this -> nyelvek ) && !isset( $_SESSION ) ){
            if($_COOKIE['sess_id']){
                session_id($_COOKIE['sess_id']);
            }
            session_start();
        }
		
		//Ha a nyelvek tömb nem üres, akkor többnyelvű az oldal => $_GET[nyelv figyelése]
		if( $this -> nyelvek ){
			
			if(!$this -> alap_nyelv){ echo 'Többnyelvű oldal esetén az alapértelmezett nyelv megadása kötelező!'; exit(); }
			
			if(isset($_GET['nyelv'])){
				$_SESSION['nyelv'] = $_GET['nyelv'];
			}
			
			//Ha a nyelv detektálása megengedett és még nincs elfogadott értéke a $_SESSION['nyelv']-nek
			if($this -> nyelv_detektal){

				if( !in_array($_SESSION['nyelv'], $this -> nyelvek) ){
					$nyelv = strtolower( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
					$nyelv = str_replace( ' ', '', $nyelv );
					$nyelv = explode( ',', $nyelv );
					switch( substr($nyelv[0], 0, 2) ){
						case 'hu': $_SESSION['nyelv'] = 'hu'; break;
						case 'en': $_SESSION['nyelv'] = 'en'; break;
                        case 'de': $_SESSION['nyelv'] = 'de'; break;
						default :  $_SESSION['nyelv'] = $this -> alap_nyelv; break;
					}
				}
			//Egyébként az $alap_nyelv-et állítjuk be, ha még nincs beállítva a $_SESSION['nyelv']
			}else{
				if( !in_array($_SESSION['nyelv'], $this -> nyelvek) ){
					$_SESSION['nyelv'] = $this -> alap_nyelv;
				}
			}
		}
		
		if( !$this -> adatbazis && $this -> db_prefix ){
			$this -> adatbazis = new Adatbazis($this -> db_prefix);
		}
		
	}
	
    /**
    * @desc Példánosít egy header osztályt
    */	
	public function head_keszit(){
		return new Head();
	}
	
	public function leallit(){
		if($this -> db_prefix){
			//$this -> adatbazis -> bezar();
		}
        if(isset($_GET['genido'])){
            global $weblap_indul;
            echo 'Generálási idő: '.(microtime(true)-$weblap_indul).' ms<br>';
            echo 'Memóriaigény: '.byte_konvertal(memory_get_peak_usage()).'<br>';
        }
        //exit;ITT NEM LEHET EXIT
	}
}
?>