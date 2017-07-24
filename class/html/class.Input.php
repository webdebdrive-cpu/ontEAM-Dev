<?php
/**
* <input type="valami" előállítására szolgáló osztály. Az input típusát a konstruktorban adjuk meg.
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.08.25
*/
require_once('class.Tag.php');
class Input extends Tag{

    /** @var boolean - radio és checkbox-hoz*/
    public $checked  = false;

    /** @var array - ha egy fált az admin oldalon meg akarunk tekinteni vagy töröln.
    * Ide egy tömbböt várunk array( fajlnev, id, oszlop, sorid, nev)
    */
    public $fajlTorlessel = array();

    /** @var boolean - ha igaz, akkor egy jelszómétert is készít mellé*/
    public $jelszometer   = false;
    
    /** @var string - input maxlength-je*/
    public $maxlength     = '';
    
    /** @var boolean - ha igaz, akkor készít hozzá egy naptár funkciót is. Fotnos, hogy az input idje is be legyen állítva*/
    public $naptar        = false;
    
    /** @var string - input utáni tartalom pl.: Ft, vagy ikon kép stb..*/
    public $post          = '';
        
    /** @var string - input előtti tartalom pl.: http://*/
    public $pre           = '';
    
    /** @var string - input tipusa*/
    private $type         = '';


    /**
    * @desc Konstruktor
    *
    * @param string input type
    */
   	public function __construct($type){
        $this -> type = $type;
    }

    /**
    * @desc HTML kód összeállítása és return
    * @return string - a kétsz HTML kód
    */
   	public function getHtml(){
        
        $sajatAttributumok = '';
        if($this -> maxlength && is_numeric($this -> maxlength) ){ $sajatAttributumok .= ' maxlength="'  .$this -> maxlength.'"'; }
        if($this -> checked   && ($this -> type == 'checkbox' || $this -> type == 'radio') ){ $sajatAttributumok .= ' checked="checked"'; }
        
        $extrak = '';
		if($this -> jelszoMeter && $this -> type == 'text'){
    		
            $extrak .= '<div class="jelszoMeter_erosseg" id="jelszoMeter_erosseg"></div><div class="jelszoMeter_tip" id="jelszoMeter_tip"></div>';
    	
        }elseif( $this -> fajlTorlessel && is_file( $this -> fajlTorlessel['fajl'] )  && $this -> type == 'file'){
    		$ft_sablon = fajlbol_olvas('../'.get_utvonal().'sablon/admin/fajltorlessel.js', true);
    		$ft_mit    = array('{ID}', '{TABLA}', '{OSZLOP}', '{FAJL}', '{NEV}', '{KIT}');
    		$kit       = substr($this -> fajlTorlessel['fajl'], strrpos($this -> fajlTorlessel['fajl'], '.') );
    		$fajlnev = str_replace('../', '', $this -> fajlTorlessel['fajl']); //Kivesszük a ../-t mert a letolt.php egy szinttel fentebb van
            $ft_mivel  = array($this -> fajlTorlessel['id'], $this -> fajlTorlessel['tabla'], $this -> fajlTorlessel['oszlop'], $fajlnev, $this -> fajlTorlessel['nev'], $kit, $this -> fajlTorlessel['id']);
            $extrak   .= str_replace($ft_mit, $ft_mivel, $ft_sablon);
    		
    		$extrak .= ' <img id="fajl_kuka_'.$this -> fajlTorlessel['oszlop'].'" class="thumb" alt="Fájl törlése" title="Fájl törlése" src="../elemek/admin/kuka.gif" width="22" height="22" onclick="alert2(\'Biztosan törli a fájlt?\',fajl_torol'.$this -> fajlTorlessel['oszlop'].')">';
    	}
        
        return $this -> pre.'<input type="'.$this -> type.'"'.$sajatAttributumok.$this -> getAttributumok().'>'.$extrak.$this -> post;

    }
   	
}
?>