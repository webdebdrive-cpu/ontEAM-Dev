<?php
/**
* <select>-et legyártó osztály  
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.08.25
*/

require_once('class.Tag.php');

class Select extends Tag{

    /** @var string - a kiválasztott opcio (selected)*/
    public $value    = array();
    
    /** @var array - <options> nevek értékek egy tömbben 'name' => 'value' alakban (vagy 'name' => 'value' ha opton group) */
    private $opciok  = array();
    
    /**
    * @desc Konstruktor
    *
    * @param array  $opciok <options> nevek értékek egy tömbben 'name' => 'value' alakban (vagy 'name' => 'value' ha opton group)
    * @param string $kivalasztott melyik érték legyen az alapértelmezett
    */
   	public function __construct($opciok = '', $value = ''){
        $this -> opciok       = $opciok;
        $this -> value = $value;
    }
    
    /**
    * @desc <select> Html kódjának előállítása
    *
    * @return string -  Html kód
    */
   	public function getHtml(){
        
        $sajatAttributumok = '';
        
        $opciokHtml = '';
        if($this -> opciok){
        	foreach($this -> opciok as $option){
        		if($option['label'] == 'optiongroup_bezar'){
        		
                	$option['label'] = htmlspecialchars(strip_tags($option['label']));
        			$opciokHtml .= '</optgroup>';
        		
                }elseif($option['label'] != ''){
        		
                	$opciokHtml .= '<optgroup label="'.$option['label'].'">';
        		
                }else{
        		    
                    if(!$option['name']){
                        $option['name'] = $option['value'];
                    }
                    
        			$opciokHtml .= '<option value="'.$option['value'].'"';
        			if(is_array($this -> value)){
                        if(in_array($option['value'], $this -> value)){
                            $opciokHtml .= ' selected="selected"';
                        }
                    }else{
                        if($this -> value == $option['value']){
                            $opciokHtml .= ' selected="selected"';
                        }
                    }
    
                    if($option['style']){
                        $opciokHtml .= ' style="'.$option['style'].'"';
                    }
                    
        			$opciokHtml .= '>'.$option['name'];
        			                
        			$opciokHtml.='</option>';
        			
        		}
        	}
    	}
        
        return $this -> pre.'<select'.$sajatAttributumok.$this -> getAttributumok().'>'.$opciokHtml.'</select>'.$this -> post;
        
    }

}
?>