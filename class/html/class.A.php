<?php
/**
* <a></a> -t legyártó osztály  
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.08.25
*/

require_once('class.Tag.php');

class A extends Tag{

    /** @var string - href*/
    public $href   = '';

    /** @var string - rel*/
    public $rel    = '';

    /** @var string - target*/
    public $target = '';
    
    /** @var string - tartalom, ami a tegek közé megy*/
    private $tartalom = '';
    
    /**
    * @desc Konstruktor
    *
    * @param string $tartalom - ami a tegek közé megy
    */
   	public function __construct($tartalom = ''){
        $this -> tartalom = $tartalom;
    }
    
    /**
    * @desc <a></a> Html kódjának előállítása
    *
    * @return string -  Html kód
    */
   	public function getHtml(){
        
        $sajatAttributumok = '';
        if($this -> href   ){ $sajatAttributumok .= ' href="'  .$this -> href.'"'; }
        if($this -> rel    ){ $sajatAttributumok .= ' href="'  .$this -> rel.'"';  }
        if($this -> target ){ $sajatAttributumok .= ' target="'.$this -> rel.'"';  }
        
        if(!$this -> tartalom){
            $this -> tartalom = '{TARTALOM}';
        }
        
        return '<a'.$sajatAttributumok.$this -> getAttributumok().'>'.$this -> tartalom.'</a>';
        
    }

}
?>