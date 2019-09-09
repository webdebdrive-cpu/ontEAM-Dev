<?php
/**
* Adatbázis objelktum. Csatlakozás adatbázishoz, lekérdezés, módosítás és törlés megvalósítása.
* SQL injection elleni védelemmel
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.06.03

* PÉLDÁK:

//Konstruktor cím, felh, jelsz, prefix
$adatbazis = new Adatbazis("tabla_");

//Csatlakozás az adatbázishoz (ez meg kell előzzön minden mást)
$adatbazis -> csatlakozas("localhost", "krixx", "e8zfZWdYO1ew");

//Adatbázis kiválasztása (kötelező)
$adatbazis -> adatb_kivalaszt("krixx");

//Karakterkódolás beállítása (kötelező)
$adatbazis -> setCharset("UTF8");

//Ha igaz, akkor sikertelen műveletkor kiírja a hibaüzenetet, és a parancsot is
$adatbazis -> debug = true;

//Kikapcsolható, a gyanús HTML-el szűrése, de csak ésszel. Inkább htmlspecialchars() függvényt használjuk, js-ek nélkül
$adatbazis -> biztonsagosHtml = true;

//Select: a lekérdezett sorokat egy tömbbként adja vissza. Ezt többször bejárható lesz újabb lekérdezés nélkül
foreach($adatbazis -> select("id, nev", "tabla", "id = 1", "id", "0,2", false) as $sor){
	echo $sor["id"]." ".$sor["nev"]."<br>";
}
//Minden Select után a getSorokSzama-val bármikor megtudhatjuk a lekrédezett sorok számát.
echo $adatbazis -> getSorokSzama()."<br>";	//Lekérdezett sorok száma



//Nem kérezi le a sorokat feleslegesen egy tömbbe, csak a sorok számát.
echo $adatbazis -> select("id, nev", "tabla", "id = 1", "id", "0,2", true);

//Insertálás megoldható tömbökkel is. Ha mindig ugyanazokba az oszlopokba szeretnén beszúrni (vagy módosítani - később)
$oszlopok = array("id",  "nev");
$ertekek  = array(
				array("1", "id1 neve"),
				array("2", "id2 neve"),
				array("3", "id3 neve"),
				array("4", "id4 neve"),
			);

$i=0;
foreach($ertekek as $ertek){
	$adatbazis -> insert("tabla", $oszlopok, $ertek);
	$i++;
}

//Az insert az utolsó beszúrt sor id-jét adja vissza
$legutolso_beszuras_idje = $adatbazis -> insert("tabla", $oszlopok, array("5", "id5 neve"));

//Update
$adatbazis -> update("tabla", $oszlopok, array("2",  "érték"), "id=1");


//Legutoljára végrehajtott parancs kiíratása
echo $adatbazis -> getParancs()."<br>";

$adatbazis -> bezar();
*/

class Adatbazis{

    /** @var Adatbázis kapcsolat*/
	public $adatbazis_kapcsolat;

    /** @var boolean Csak biztonságos HTML-t enged beszúrni. Ha HTML tartalmat szeretnénk felvinni ezt kapcsoljuk ki, de jól átgondolni, mert rés lehet*/
    public $biztonsagosHtml   = true;

    /** @var string Prefix (pl. tabla_)*/
    public $db_prefix         = '';

    /** @var boolean Ha igaz, akkor kiírjuk a parancsot hiba esetén*/
	public $debug             = false;

	/** @var array Insertnél nem tesszük idézőjel közé a kivételeket*/
	public $insert_kivetelek  = array('NOW()', 'NULL');



    /** @var array Lekérdezett sorok*/
	private $eredmeny         = array();

    /** @var string Legutóbbi parancs (debuggoláshoz jöhet jól kiírtatni, vagy feldogozni újra)*/
    private $legutobbiParancs = '';

    /** @var int Lekérdezett sorok száma*/
    private $sorok_szama      = 0;


    /**
    * @desc Konstruktor. Beállítja a tábal prefixet, ha meg van adva (ha hiányzik a végéről az '_', azt is kiegészíti)
    *
    * @param string Prefix (pl. 'tabla_' vagy csak simán 'tabla')
    */
   	public function __construct($prefix){
		if( $prefix ){
			if( substr($prefix, strlen($prefix)-1, strlen($prefix)) != '_' ){
				$prefix .= '_';
			}
			$this -> db_prefix = $prefix;
		}
	}

    /**
    * @desc Csatlakozás az adatbázishoz
    *
	* @param string host neve pl. 'localhost'
    * @param string felhasználói név
    * @param string jelszó
    */
	public function csatlakozas($host, $felh, $jelsz, $db_adatb){
		$this -> adatbazis_kapcsolat = mysqli_connect($host, $felh, $jelsz, $db_adatb) or die('Adatbázishoz csatlakozás sikertelen: '.mysqli_connect_error());
	}

	/**
    * @desc Adatbázis kiválasztása. Előtte csatlakozni kell!
    *
    * @param  string adatbázis neve, amihez csatlakozni szeretnénk
    */
    public function adatb_kivalaszt($db_adatb){
		//Ez feleslegessé vált az SQLi miatt. mysqli_select_db($db_adatb) or die('Adatbázis kiválasztása sikertelen: '.mysqli_error());
	}

	/**
    * @desc A karakterkódolás beállítása
    *
    * @param  string a karakterkódolás neve (pl. UTF8 kötöjel nélkül!)
    * @return boolean
    */
    public function setCharset($db_charset){
        mysqli_set_charset($this -> adatbazis_kapcsolat, $db_charset);
	}

    /**
    * @desc Információ lekérdezése az adatbázisból
	*  foreach($adatbazis -> select("id, nev", "tabla", "id = 1", "id", "0,2", false) as $sor){
	*      echo $sor["id"]." ".$sor["nev"]."<br>";
	*  }
	* Ha csak egy sor ad vissza akkor is egy tömbb, aminek a 0. elemére kell hivatkozni
    *
    * @param  string  Lekérdezni kívánt oszlopok, alapértelmezetten *
    * @param  string  Tábla neve prefix nélkül
    * @param  string  Where feltétel (opcionális)
    * @param  string  Order (opcionális)
    * @param  string  Limit (opcionális)
    * @param  string  group_by (opcionális). Mi alaján legyen group by
    * @param  string  num-rows csak a sorok számára van szükség
    * @param  string  join csatlakozás másik táblához. Ennek értéke egy szépen leírt JOIN legyen. Pl.  JOIN `'.get_db_tabla().'tabla_kulso` ON `'.get_db_tabla().'tabla_alap`.`nev`=`'.get_db_tabla().'tabla_kulso`.`nev`
    * @return array   A lekérdezett sorok, vagy hamis
    */
    public function select($oszlopok, $tabla, $where = null, $order = null, $limit = null, $group_by = null, $num_rows = false, $join = false, $csak_stringet_allitson_elo = false){

        $this -> eredmeny = array();

		if($oszlopok) $oszlopok = $this -> sqlStringBeallit($oszlopok);
		if($order)    $order    = $this -> sqlStringBeallit($order);
		if($where)    $where    = $this -> sqlStringBeallit($where);
		if($group_by) $group_by = $this -> sqlStringBeallit($group_by);

        if( !strstr($tabla, '`') ) {
            $tabla = '`'.$this -> db_prefix.$tabla.'`';
        }

        $select = 'SELECT '.$oszlopok.' FROM '.$tabla;

        if($join){
            $select .= $join;
        }

		if($where != null){
			$select .= ' WHERE '.$where;
		}

        if($order != null){
            if($order == '`rand()`' || $order == '`RAND()`' ){
                $order = 'RAND()';
            }
            if( !strstr($order, '`') ) {
                $order = sql_ertek($order);
            }
			$select .= ' ORDER BY '.$order;
		}

		if($group_by != null){
			$select .= ' GROUP BY '.$group_by;
		}

        if($limit != null){
			$select .= ' LIMIT '.$limit;
		}

		$select = str_replace('--', '', $select);
		$this -> legutobbiParancs = $select;

        if( $csak_stringet_allitson_elo ) {
            return true;
        }

        if( !$lekerdezes = mysqli_query($this->adatbazis_kapcsolat, $select) ) {

            if( $this->debug ) {
                echo $select.'<br>';
				echo 'Adatbázis hiba lekérdezéskor: '. mysqli_error($this -> adatbazis_kapcsolat);
			}
			return false;
		}

        $this -> sorok_szama = mysqli_num_rows($lekerdezes);
        if( !$num_rows ) {
            for ( $i = 0; $i < $this->sorok_szama; $i++ ) {
                array_push($this -> eredmeny, mysqli_fetch_assoc($lekerdezes));
	        }
	        return $this -> eredmeny;
        } else {
			return $this -> sorok_szama;
		}
	}

    /**
    * @desc  Select röviden (az eredeti függvényt hívja meg)
    */
    public function s($oszlopok, $tabla, $where = null, $order = null, $limit = null, $group_by = false, $num_rows = false, $join = false, $csak_sztring = false){
        return $this -> select($oszlopok, $tabla, $where, $order, $limit, $group_by, $num_rows, $join, $csak_sztring);
    }
    public function s2($oszlopok, $tabla, $where = null, $order = null, $limit = null, $group_by = false, $num_rows = false, $join = false, $csak_sztring = false){
        $elso_sor = $this -> select($oszlopok, $tabla, $where, $order, $limit, $group_by, $num_rows, $join, $csak_sztring);
        return $elso_sor[0];
    }

    /**
    * @desc Információ beszúrása az adatbázisba
    * pl.: $adatbazis -> insert('tabla', array('id', 'nev'), array(1, 'név'));
    *
    * @param  string Tábla neve prfix nélkül
    * @param  array  Oszlopok nevei
    * @param  array  Beszúrandó értékek
    * @return int    Legutóbb beszúrt sor id-je
    */
    public function insert($tabla, $oszlopok, $ertekek){

		$insert = 'INSERT INTO `'.$this -> db_prefix.$tabla.'` SET ';

        if(!is_array($oszlopok)){
            $oszlopok = array($oszlopok);
        }

        if(!is_array($ertekek)){
            $ertekek = array($ertekek);
        }

        $i = 0;
        $insert_reszek = array();
        $oszlopok = array_values($oszlopok);
        $ertekek  = array_values($ertekek);
		foreach($oszlopok as $oszlop){
			$oszlop      = sql_ertek($oszlop);
			$ertekek[$i] = sql_ertek($ertekek[$i], true); // Tehát HTML teg-ek engedélyezve
			// De jobb kiszűrni ami gyanús
			if( $this -> biztonsagosHtml ){
				$ertekek[$i] = biztonsagos_html($ertekek[$i]);
			}

			if(in_array($ertekek[$i], $this -> insert_kivetelek)){
				array_push($insert_reszek,'`'.$oszlop.'`='.trim( htmlspecialchars( $ertekek[$i] ) ) );
			} else {
				array_push($insert_reszek,'`'.$oszlop.'`=\''.trim( htmlspecialchars( $ertekek[$i].'\'' ) ) );
			}

			$i++;
		}

		$insert .= implode(', ', $insert_reszek);

		$insert = str_replace('--', '', $insert);
		$this -> legutobbiParancs = $insert;

        if(!mysqli_query($this -> adatbazis_kapcsolat, $insert)){
			if($this -> debug){
				echo $insert.'<br>';
				echo 'Adatbázis hiba sor beszúrásakor: '.mysqli_error($this -> adatbazis_kapcsolat);
			}
			return false;
		} else {
			return mysqli_insert_id($this -> adatbazis_kapcsolat);
		}

    }

    /**
    * @desc  Insert röviden (az eredeti függvényt hívja meg)
    */
    public function i($tabla, $oszlopok, $ertekek){
        return $this -> insert($tabla, $oszlopok, $ertekek);
    }


    /**
    * @desc Törlés táblából
    * pl.: $adatbazis -> delete('tabla', id=1);
    *
    * @param  string Tábla neve prfix nélkül
    * @param  string Where feltétel (opcionális)
    * @return boolean Igaz, ha sikerült a törlés, és hamis ha nem
    */
    public function delete($table, $where = null){

		if($where == null){
            $delete = 'DELETE '.$this -> db_prefix.$table;
        } else {
        	$where  = $this -> sqlStringBeallit($where);
            $delete = 'DELETE FROM '.$this -> db_prefix.$table.' WHERE '.$where;
        }

        $delete = str_replace('--', '', $delete);
        $this -> legutobbiParancs = $delete;

		if(!mysqli_query($this -> adatbazis_kapcsolat, $delete)){
			if($this -> debug){
				echo $delete.'<br>';
				echo 'Adatbázis hiba sor törléskor: '.mysqli_error();
			}
			return false;
		} else {
			return true;
		}

    }

    /**
    * @desc  Delete röviden (az eredeti függvényt hívja meg)
    */
    public function d($table, $where = null){
        return $this -> delete($table, $where);
    }


    /**
    * @desc Tábla adatainak módosítása
    * pl.: $adatbazis -> update('tabla', array('id',  'nev'), array('2',  'érték'), 'id=1');
    *
    * @param  string  Tábla neve prefix nélkül
    * @param  array   módosítani kíván oszlopok
    * @param  array   új értékek
    * @param  string  Where feltétel
    *
    * @return boolean Igaz, ha sikerült update-elni a táblát, és hamis ha nem
    */
    public function update($tabla, $oszlopok, $ertekek, $where, $limit = ''){

        //Update esetén veszélyes, ha nincs megadva feltétel, mert mindent felülírunk. Emiat kötelezővé tesze, hogy legalább egy 1-es meg legyen adva, így elkerülve azt a tévedést, ha üresen maradna
        if($where!='1'){
            if($where){
                $where = $this -> sqlStringBeallit($where);
            } else {
                echo 'Nem adtál meg WHERE feltételt az UPDATE-ra. Írj egy 1-est, ha az összes sorra vonatkozik!';
                exit;
            }
        }

        $update = 'UPDATE `'.$this -> db_prefix.$tabla.'` SET ';

        if(!is_array($oszlopok)){
            $oszlopok = array($oszlopok);
        }

        if(!is_array($ertekek)){
            $ertekek = array($ertekek);
        }

        $i = 0;
        $update_reszek = array();

        //Tömb újraindexelése (arra az esetre, ha pl. egy unset miatt a tömb foghíjas lenne)
        $oszlopok = array_values($oszlopok);
        $ertekek  = array_values($ertekek);
		foreach($oszlopok as $oszlop){
			//$oszlop      = sql_ertek($oszlop);
			$ertekek[$i] = sql_ertek($ertekek[$i], true);

			if(in_array($ertekek[$i], $this -> insert_kivetelek)){
				array_push($update_reszek,'`'.$oszlop.'`='. htmlspecialchars( $ertekek[$i] ) );
			} else {
				array_push($update_reszek,'`'.$oszlop.'`=\''. htmlspecialchars( $ertekek[$i] ) .'\'');
			}

			$i++;
		}

		$update .= implode(', ', $update_reszek);

		if($where != null){
			$update .= ' WHERE '.$where;
		}

        if($limit){
            $update .= ' LIMIT '.$limit;
        }

		$update = str_replace('--', '', $update);
		$this -> legutobbiParancs = $update;

        if(!$lekerdezes = mysqli_query($this -> adatbazis_kapcsolat, $update)){
        	if($this -> debug){
				echo $update.'<br>';
				echo 'Adatbázis hiba módosításkor: '.mysqli_error();
			}
			return false;
		} else {
			return true;
		}

    }

    /**
    * @desc  Update röviden (az eredeti függvényt hívja meg)
    */
    public function u($tabla, $oszlopok, $ertekek, $where, $limit = ''){
        return $this -> update($tabla, $oszlopok, $ertekek, $where, $limit);
    }

    /**
    * @desc Adatbázis kapcsolat bezárása
    *
    * @return boolean
    */
    public function bezar(){
        if($this -> adatbazis_kapcsolat && mysqli_close($this -> adatbazis_kapcsolat)){
			return true;
		} else {
			return false;
		}

    }

    /**
    * @desc Oszlopok, order esetén megformázza a parancsot
	* pl.: id nev vagy id, nev => `id`, `nev`
	*
	* @param  string Formázandó parancsrész
	* @return string Formázott parancsrész
    */
    private function sqlStringBeallit($string){

		//Ha a where feltételben in vagy like van, akkor nem formázzuk meg
		if(preg_match('/in |IN |like |LIKE |now() |NOW() |distinct |DISTINCT |`/',$string)){
			return $string;
		//Ha van benne =, > vagy < jel, akkor ez a WHERE rész (pl. nev=10 vagy `nev`='10' vagy nev=10 or nev=11)
		}elseif(strstr($string, '=') || strstr($string, '>') || strstr($string, '<')){

			$kivetelek = array('OR', 'AND', 'or', 'and', '&&', '||');

			//Eltávolítjuk a felesleges szóközöket a műveleti jel elől. PL.: teszt  !=  1  => teszt!=1
			$ujstring = preg_replace('/(\w+)( *)(!=|<=|>=)( *)(\w+)/', '$1$3$5', $string);
			if($ujstring == $string){
				$ujstring = preg_replace('/(\w+)( *)(=|<|>)( *)(\w+)/', '$1$3$5', $string);
			}

			$reszek   = explode(' ', $ujstring);

			$string = '';
			$meret = count($reszek);
			for($i=0; $i<$meret; $i++){
				if($reszek[$i]){
					if( !in_array($reszek[$i], $kivetelek) ){
						$muveleti_jel = preg_replace('/(.*)(!=|<=|>=)(.*)/', '$2', $reszek[$i]);
						//Ha a fentiekre nem volt illeszkedés (tehát a művleti jel megegyzeik a részek[i]-vel), akkor az
						//egykarakterű operátorokra illesztünk
						if( $muveleti_jel == $reszek[$i]){
							$muveleti_jel = preg_replace('/(.*)(=|<|>)(.*)/', '$2', $reszek[$i]);
						}

						list($balo, $jobbo) = preg_split('(!=|<=|>=|=|>|<)', $reszek[$i]);
						$balo  = preg_replace('/^(`|\'?)(.*)(`|\'$)/', '$2', $balo); //Esetleges idézőjelek eltávolítása `teszt` => teszt 'teszt' => teszt
						$jobbo = preg_replace('/^(`|\'?)(.*)(`|\'$)/', '$2', $jobbo);
						$string .= "`".$balo."`".$muveleti_jel."'".$jobbo."'";
					} else {
						$string .= ' '.$reszek[$i].' ';	//or, and..
					}
				}
			}

		} else {

			$mit   = array(', `asc`', ', `desc`');
			$mivel = array(' ASC', ' DESC');

			//Ha nem üres és nem kérünk le minden sort
			if($string && $string!='*'){
				$reszek = explode(' ', $string);

				$meret = count($reszek);
				for($i = 0; $i < $meret; $i++){
					$reszek[$i] = preg_replace('/^(`|\'|,?)(.*)(`|\'|,$)/', '$2', $reszek[$i]);
					if($reszek[$i]){	//Üres szting nem kap idézőjelet
						$reszek[$i]='`'.$reszek[$i].'`';
					}
				}
				//Üres elemeket töröljük (pl. ha az oszlopok vagy order ilyen: nev , id)
				$i=0;
				foreach($reszek as $resz){
					if(!$resz){
						unset($reszek[$i]);
					}
					$i++;
				}
				$string = implode(', ', $reszek);
			}

			$string = str_replace($mit, $mivel, $string);

		}

		return $string;
	}


    /**
    * Select után lekérdeztt sorok száma
    *
    * @return int
    */
    public function getSorokSzama(){
		return $this->sorok_szama;
    }

    /**
    * Legutóbbi select parancsa
    *
    * @return string
    */
    public function getParancs(){
		return $this->legutobbiParancs;
    }

    /**
    * Legutóbbi select parancsa rövidebben
    *
    * @return string
    */
    public function gp(){
		return $this->getParancs();
    }

}
?>
