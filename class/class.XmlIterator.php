<?php

class XmlIterator implements Iterator {
    /**
     * XML file path
     *
     * @var string
     */
    protected $strFile = null;
 
    /**
     * XML reader object
     *
     * @var XMLReader
     */
    protected $reader = null;
 
    /**
     * Current program
     *
     * @var array
     */
    protected $program = null;
 
    /**
     * Dummy-key for iteration.
     * Has no real value except maybe act as a counter
     *
     * @var integer
     */
    protected $nKey = null;
 
    public $strObjectTagname = '';
    
    public $getAttribute = array();
 
 
 
    function __construct($strFile){
        $this->strFile = $strFile;
    }
 
    public function current() {
        return $this->program;
    }
 
    public function key() {
        return $this->nKey;
    }
 
    public function next() {
        $this->program = null;
    }
 
    public function rewind() {
        $this->reader = new XMLReader();
        $this->reader->open($this->strFile);
        $this->program = null;
        $this->nKey    = null;
    }
 
    public function valid() {
        if ($this->program === null) {
            $this->loadNext();
        }
 
        return $this->program !== null;
    }
 
    /**
     * Loads the next program
     *
     * @return void
     */
    protected function loadNext(){
        $strElementName = null;
        $bCaptureValues = false;
        $arValues       = array();
        $arNesting      = array();
 
        while ($this->reader->read()) {
            
//var_dump($this->reader->nodeType); //http://php.net/manual/en/class.xmlreader.php alapján látod, hogy milyen fajta NODE-ok vannak
//var_dump($this->reader->name); echo '<br>';

            switch ($this->reader->nodeType) {
                
                case XMLReader::ELEMENT:
                    $strElementName = $this->reader->name;

                    if ($bCaptureValues) {

                        if ($this->reader->isEmptyElement) {
                            $arValues[$strElementName] = null;
                        } else {
                            $arNesting[] = $strElementName;

                            //Ezt azért vettem ki, mert így nem tudtam az ismétlődő elemeket tömbként felvenni. Pl
                            //<elem><subelem>1</subelem><subelem>2</subelem><subelem>3</subelem></elem>
                            //$arValues[implode('-', $arNesting)] = null;
                        }
                        if( $this -> getAttribute ) {
                            foreach($this -> getAttribute as $elem_neve => $attr_nevek) {
                                if($strElementName==$elem_neve){
                                    
                                    foreach($attr_nevek as $attr_neve){
                                        if(!is_array($arValues[$attr_neve])){
                                            $arValues[$attr_neve] = array();
                                        }
                                        array_push($arValues[$attr_neve], $this->reader->getAttribute($attr_neve));
                                    }
                                }
                            }
                        }
                        
                    }
                    if ($strElementName == $this->strObjectTagname) {
                        $bCaptureValues = true;
                    }
                break;
 
                
                case XMLReader::TEXT:
                    if ($bCaptureValues) {
                        if( !isset($arValues[implode('-', $arNesting)]) ) {
                            $arValues[implode('-', $arNesting)] = array();
                        }
                        
                        array_push($arValues[implode('-', $arNesting)], $this->reader->value);
                    }
                break;

                case XMLReader::CDATA:
                    if ($bCaptureValues) {
                        $arValues[implode('-', $arNesting)] = $this->reader->value;
                    }
                break; 
                
                case XMLReader::END_ELEMENT:

                    if ($this->reader->name == $this->strObjectTagname) {
                        $this->program = $arValues;
                        ++$this->nKey;
                        break 2;
                    }
                    if ($bCaptureValues) {
                        array_pop($arNesting);
                    }
                break;
            }
        }
    }
}
?>