<?php

/**
* A Lapozó egy - első sorban SQL-ből kinyert - lista megjelenítésére szolgál.
* A hosszú listát oldalakra bontja, melyben könnyedén lapozhatunk.
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.06.03
*/
class Lapozo{


	/** @var  string Elem per oldal változó neve(GET). Ha ez üres string, akkor nem ajánlja fel az összes oldal kilistázását*/
	public $epo_var   = 'epo';

	/** @var  string Oldal változó neve (GET)*/
	public $oldal_var = 'oldal';

	/** @var  integer Összes elem értéke (pl.: SQL-ből lekérdezett sorok száma) */
	public $elem_osszes;

	/** @var  az oldalakat fordított sorrendbe írja ki (jobbra igazításnál szükséges)*/
	public $forditva        = false;

	/** @var  integer Nagy oldalszám esetén a középső intervallum mérete*/
	public $kozep_meret     = 9;

	/** @var  integer Ha a megadott számtól több oldal van, akkor jelenik meg az előző/követekző felirat*/
	public $max_fix_oldal   = 0;

	/** @var  array Elem per oldal mennyiségek ha választhatóak, akkor egy tömbbyi interrel adhatjuk át az opciókat*/
	public $epo_array       = array(10,25,50,100);

	/** @var  string oldalszámok mellett a 'minden elem kilistázásának' felirata (ha $mind_var üres string akkor nem jelenik ez meg)*/
	public $mind_text       = 'Összes oldal';

	/** @var  string Összes elem változó neve (GET)*/
	public $mind_var        = 'osszes';

	/** @var  string 'Előző' felirat, link title-jéhez*/
	public $elozo_title     = '« Előző oldal';

	/** @var  string 'Következő' felirat, link title-jéhez*/
	public $kovet_title     = 'Következő oldal »';

	/** @var  string Előző gomb sablonja*/
	public $sablon_elozo    = 'sablon/lapozas_elozo.html';

	/** @var  string Következő gomb sablonja*/
	public $sablon_kovetkezo= 'sablon/lapozas_kovet.html';

	/** @var  string oldalak sablonja*/
	public $sablon_oldalak  = 'sablon/lapozas_oldalak.html';

	/** @var  string option sablonja*/
	public $sablon_option   = 'sablon/lapozas_option.html';

	/** @var  string pontok sablonja*/
	public $sablon_pontok   = 'sablon/lapozas_pontok.html';

	/** @var  string select sablonja*/
	public $sablon_select   = 'sablon/lapozas_select.html';

	/** @var  string 'Aktuális oldal' felirat, link title-jéhez*/
	public $akt_oldal_title = 'Aktuális oldal';

    /* Aktuális oldal */
    public $fajl = '';

    private $elem_per_oldal;
    private $akt_oldal;
    public $oldalak_szama;
    private $also;
    private $felso;
    private $limit;
    private $return;
    private $default_epo = 10; //Nem lehet public, mert már létrehozásnál számolunk vele. Csak úgy bírálhatjuk felül, ha a GET[epo]-t beállítjuk. Ezt az esemenyek.php-ban tudod megtenni globálisan.

    function __construct(){
        $this -> akt_oldal = 1;
        $this -> kozep_meret = 7;
        $this -> elem_per_oldal = (!empty($_GET[$this -> epo_var])) ? $_GET[$this -> epo_var]:$this -> default_epo;

        if(!$_GET[$this -> oldal_var]){
            $_GET[$this -> oldal_var] = 1;
        }
    }

	/**
	* Az oldalak kiszámítása, HTML tartalom összeállítása. Kiíratás előtt ezt a metódust meg kell hívni.
	*/
    function lapozas(){

        if($_GET[$this -> epo_var] == $this -> mind_var){
            //Korábban rosszul számoltuk volna? Összes elem esetén az oldalak száma 0
//            $this -> oldalak_szama  = ceil($this -> elem_osszes / $this -> default_epo);
//            $this -> elem_per_oldal = $this -> default_epo;
            $this -> oldalak_szama  = 0;
            $this -> elem_per_oldal = $this -> elem_osszes;
        }else{
            if(!is_numeric($this -> elem_per_oldal) || $this -> elem_per_oldal <= 0){
				$this -> elem_per_oldal = $this -> default_epo;
				$_GET[$this -> epo_var] = $this -> default_epo;
			}
            $this -> oldalak_szama = ceil($this -> elem_osszes / $this -> elem_per_oldal);
        }

    	//ha csak egy oldalnyi elemünk van van, ne csináljunk semmit (felesleges a lapozás)
    	if($this -> oldalak_szama > 1){

	        $this -> akt_oldal = (int) $_GET[$this -> oldal_var]; //kötelezően numerikus
	        if($this -> akt_oldal < 1 || !is_numeric($this -> akt_oldal)) $this -> akt_oldal = 1;
	        if($this -> akt_oldal > $this -> oldalak_szama) $this -> akt_oldal = $this -> oldalak_szama;
	        $elozo_oldal     = $this -> akt_oldal-1;
	        $kovetkezo_oldal = $this -> akt_oldal+1;

			$lapozas_elozo_sablon   = fajlbol_olvas( $this -> sablon_elozo,      true);
			$lapozas_kovet_sablon   = fajlbol_olvas( $this -> sablon_kovetkezo,  true);
			$lapozas_pontok_sablon  = fajlbol_olvas( $this -> sablon_pontok,     true);
			$lapozas_oldalak_sablon = fajlbol_olvas( $this -> sablon_oldalak,    true);

			$lapozas_elkov_mit      = array('{CLASS}', '{TITLE}', '{LINK}');
			$lapozas_oldalak_mit    = array('{CLASS}', '{TITLE}', '{LINK}', '{OLDALSZAM}');


			//Előző oldal
			$title = $this -> elozo_title;
			$link  = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this -> oldal_var.'='.$elozo_oldal.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;
            ;
            /*Ha nem az első oldalon agyunk ÉS az oldlak száma több mint a maximális fix oldal (ha ez utóbbi igaz, akkor már haználjuk az előző következő feliratot, egyébként nem írjuk ki pl.: 2-3 oldalnál felesleges előző következő, elég ha az oldalak számát látjuk, ha mégis ekl akkor a max fix oladt étékét 0-ra kell állítani) ÉS nem az összes oldal módban vagyunk*/
			if($this->akt_oldal != 1 && $this -> oldalak_szama >= $this -> max_fix_oldal && $_GET[$this -> oldal_var] != $this -> mind_var){
				$lapozas_elkov_mivel  = array('lapozas', $title, $link);
			}else{
				$lapozas_elkov_mivel  = array('dn', $title, $link);
			}

			$elozo_oldal_html = str_replace($lapozas_elkov_mit, $lapozas_elkov_mivel, $lapozas_elozo_sablon);

			//Ha az oldalak száma több mint a 'max_fix_oldal', tehát ahányat maximálisanmeg szeretnénk jeleníteni
	        if($this -> oldalak_szama > $this -> max_fix_oldal){

	            $this -> start_range = $this -> akt_oldal - floor($this -> kozep_meret/2);
	            $this -> end_range   = $this -> akt_oldal + floor($this -> kozep_meret/2);

	            if($this->start_range <= 0){
	                $this->end_range += abs($this->start_range)+1;
	                $this->start_range = 1;
	            }
	            if($this -> end_range > $this -> oldalak_szama){
	                $this -> start_range -= $this -> end_range-$this -> oldalak_szama;
	                $this -> end_range    = $this -> oldalak_szama;
	            }
	            $this -> range = range($this -> start_range, $this -> end_range);

				//Oldalak kiíratása (sorban, vagy fordítva)
				if(!$this -> forditva){
    	            for($i = 1; $i <= $this->oldalak_szama; $i++){
    	                if($this -> range[0] > 2 && $i == $this -> range[0]) $this->return .= $lapozas_pontok_sablon;
    	                // Oldalak átfutása. Ha első utolsó vagy a közép intervallum résza akkor kiíratás
    	                if($i==1 || $i==$this -> oldalak_szama || in_array($i,$this -> range)){
    	                	if($i == $this -> akt_oldal && $_GET[$this -> epo_var] != $this -> mind_var){
    							$link = $_SERVER['REQUEST_URI'];
    							$lapozas_oldalak_mivel = array('akt_oldal', $this -> akt_oldal_title, $link, $i);
    							$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    						}else{
    							$link = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this->oldal_var.'='.$i.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;
    							$lapozas_oldalak_mivel = array('lapozas', $i.'. oldalra lapozás', $link, $i);
    							$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    						}
    	                }
    	                if($this -> range[$this -> kozep_meret-1] < $this -> oldalak_szama-1 && $i == $this -> range[$this -> kozep_meret-1]) $this->return .= $lapozas_pontok_sablon;
    	            }
                }else{
    	            for($i = $this->oldalak_szama; $i >=1 ; $i--){
    	                if($this -> range[0] > 2 && $i == $this -> range[0]) $this->return .= $lapozas_pontok_sablon;
    	                // Oldalak átfutása. Ha első utolsó vagy a közép intervallum résza akkor kiíratás
    	                if($i==1 || $i==$this -> oldalak_szama || in_array($i,$this -> range)){
    	                	if($i == $this -> akt_oldal && $_GET[$this -> epo_var] != $this -> mind_var){
    							$link = $_SERVER['REQUEST_URI'];
    							$lapozas_oldalak_mivel = array('akt_oldal', $this -> akt_oldal_title, $link, $i);
    							$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    						}else{
    							$link = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this->oldal_var.'='.$i.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;
    							$lapozas_oldalak_mivel = array('lapozas', $i.'. oldalra lapozás', $link, $i);
    							$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    						}
    	                }
    	                $eltolas = 0;
    	                if($this -> forditva){
                            $eltolas = 1;
                        }
    	                if($this -> range[$this -> kozep_meret-1] < $this -> oldalak_szama-1 && $i-$eltolas == $this -> range[$this -> kozep_meret-1]) $this->return .= $lapozas_pontok_sablon;
    	            }
                }

			//Ha az oldalak kiférnek egy sorba, nem kell középintervallumot használni
			}else{

			    if(!$this -> forditva){
    	            for($i=1; $i<=$this -> oldalak_szama; $i++){

    	            	if($i == $this -> akt_oldal && $_GET[$this->oldal_var] != $this -> mind_var){
    						$link = $_SERVER['REQUEST_URI'];
    						$lapozas_oldalak_mivel = array('akt_oldal', $this -> akt_oldal_title, $link, $i);
    						$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    					}else{
    						$link = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this -> oldal_var.'='.$i.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;
    						$lapozas_oldalak_mivel = array('lapozas', $i.'. oldalra lapozás', $link, $i);
    						$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    					}

    	            }
                }else{
    	            for($i=$this -> oldalak_szama; $i>=1; $i--){

    	            	if($i == $this -> akt_oldal && $_GET[$this->oldal_var] != $this -> mind_var){
    						$link = $_SERVER['REQUEST_URI'];
    						$lapozas_oldalak_mivel = array('akt_oldal', $this -> akt_oldal_title, $link, $i);
    						$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    					}else{
    						$link = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this -> oldal_var.'='.$i.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;
    						$lapozas_oldalak_mivel = array('lapozas', $i.'. oldalra lapozás', $link, $i);
    						$this->return .= str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
    					}

    	            }
                }
	        }

            //Követlező oldal
			$title = $this->kovet_title;
			$link  = $this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this -> oldal_var.'='.$kovetkezo_oldal.'&amp;'.$this -> epo_var.'='.$this -> elem_per_oldal;

            if( !$_GET[$this -> oldal_var] ){
                $_GET[$this -> oldal_var] = 1;
            }
            /*Ha nem az utolsó oldaon vagyunk ÉS az oldalak száma nagyaobb mint a maximális fixoldalak száma ÉS ha nem az össezs módban vagyunk*/
			if( $this -> akt_oldal != $this -> oldalak_szama && $this -> oldalak_szama >= $this -> max_fix_oldal && $_GET[$this -> oldal_var] != $this -> mind_var ){
                $lapozas_elkov_mivel  = array('lapozas', $title, $link);
			}else{
				$lapozas_elkov_mivel  = array('dn', $title, $link);
			}

            if(!$this -> forditva){
                $this->return .= str_replace($lapozas_elkov_mit,$lapozas_elkov_mivel,$lapozas_kovet_sablon);
                $this->return = $elozo_oldal_html.$this->return;
            }else{
                $this->return = str_replace($lapozas_elkov_mit,$lapozas_elkov_mivel,$lapozas_kovet_sablon).$this->return.$elozo_oldal_html;
            }


	        //Összes oldal
	        if($this -> mind_var){
	        	if($_GET[$this -> epo_var] == $this -> mind_var){
					$link = $_SERVER['REQUEST_URI'];
					$lapozas_oldalak_mivel = array('akt_oldal', $this -> mind_text, $link, $this -> mind_text);
				}else{
					$link = get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this -> oldal_var.'=1&amp;'.$this -> epo_var.'='.$this -> mind_var;
					$lapozas_oldalak_mivel = array('', $this -> mind_text, $link, $this -> mind_text);
				}
				$this->return.=str_replace($lapozas_oldalak_mit, $lapozas_oldalak_mivel, $lapozas_oldalak_sablon);
			}

	        //SQL LIMIT-jének meghatározása
	        $this -> also  = ($this -> akt_oldal-1) * $this -> elem_per_oldal;
	        $this -> felso = ($_GET[$this -> epo_var] == $this -> mind_var) ? $this -> elem_osszes:($this -> akt_oldal * $this -> elem_per_oldal)-1;
	        $this -> limit = ($this -> mind_var && $_GET[$this -> epo_var] == $this -> mind_var) ? '': $this->also.', '.$this->elem_per_oldal;
	    }
    }

	/**
	* Megjelenített elemek számának állíthatósága. Meghívásra készít egy szelektet fix értékekkel (10, 25,50,100,Összes)
	*
	* @return string A slelect HTML kódja
	*/
    function epoSelectKiir(){
		$select_sablon = fajlbol_olvas( $this -> sablon_select, true);
    	$option_sablon = fajlbol_olvas( $this -> sablon_option, true);

		$select_mit    = array('{CLASS}', '{ONCHANGE}', '{OPTIONS}');
		$option_mit    = array('{SELECTED}', '{VALUE}', '{NAME}');

        $options = '';
        foreach($this -> epo_array as $epo_opt){
        	if($epo_opt == $this -> elem_per_oldal){
				$selected = ' selected="selected"';
			}else{
				$selected = '';
			}
			$value = $epo_opt;
			$name  = $epo_opt;

			$option_mivel = array($selected, $value, $name);
			$options .= str_replace($option_mit,$option_mivel,$option_sablon);
		}

		$class    = 'lapozas';
		$onchange = 'location.href=\''.$this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this->oldal_var.'=1&amp;'.$this->epo_var.'=\'+this.value';

		$select_mivel = array($class, $onchange, $options);
		return str_replace($select_mit,$select_mivel,$select_sablon);
    }

	/**
	* Ugrás egy adott oldalra. A metódus hívásával egy olyan select jeleníthető meg, mely az összes oldal sorszámát tartalmazza, így tetszőleges oldalra ugorhatunk.
	*
	* @return string A slelect HTML kódja
	*/
    function oldalakSelectKiir(){
    	//ha csak egy oldalnyi elemünk van van, ne csináljunk semmit (felesleges a lapozás)
    	if($this -> oldalak_szama > 1){
			$select_sablon = fajlbol_olvas( $this -> sablon_select, true);
	    	$option_sablon = fajlbol_olvas( $this -> sablon_option, true);

			$select_mit    = array('{CLASS}', '{ONCHANGE}', '{OPTIONS}');
			$option_mit    = array('{SELECTED}', '{VALUE}', '{NAME}');

			$options='';
	        for($i=1; $i <= $this -> oldalak_szama; $i++){
	        	if($i == $this -> akt_oldal){
					$selected = ' selected="selected"';
				}else{
					$selected = '';
				}
				$value = $i;
				$name  = $i;

				$option_mivel = array($selected, $value, $name);
				$options .= str_replace($option_mit,$option_mivel,$option_sablon);
	        }

			$class    = 'lapozas';
			$onchange = 'location.href=\''.$this -> fajl.get_get(array($this -> oldal_var, $this -> epo_var, 'unset', 'szamlaLista', 'tabla')).'&amp;'.$this->oldal_var.'=\'+this.value+\'&amp;'.$this->epo_var.'='.$this->elem_per_oldal.'\'';

			$select_mivel = array($class, $onchange, $options);
			return str_replace($select_mit,$select_mivel,$select_sablon);
		}
    }

	/**
	* A végeredmény kiiratása. Többször is meghívható (pl. kilistázás előtt és után)
	*
	* @return string A lapozás HTML kódja
	*/
    function kiir(){
        return $this->return;
    }

	function getLimit(){
        return $this -> limit;
    }

    /**
    * Megadja az oldalak számát
    *
	* @return int Az oldalak szám
	*/
	function getOldalakSzama(){
        return $this -> oldalak_szama;
    }
}

?>
