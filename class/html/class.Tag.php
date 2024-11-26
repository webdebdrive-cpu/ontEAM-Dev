<?php
/**
* Általános Html tag osztály. Az általános attribútumokat képes beállítani és legenerálni.
* Ha megadjuk milyen tag-et szeretnénk (pl. b, div), akkor a getHtml()-el ki is generálhatuk az
* ezeknek nem kell külön osztályt készíteni. Ez csak olyan tag-ekre vonatkozik, melye nem rengelkeznek
* speciális attribútumokkal, tulajdonságokkal és van záró párjuk is (nem rövidítve használja)
* Utóbbi esetben létrehoznásnál 2. paraméterként megadható a tag-ek közé szánt taratlom is
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.08.25
*/

class Tag{

    /** @var string - data-alert-hez kell*/
    public $alert           = '';

    /** @var string - autocomplete attribútum */
    public $autocomplete    = '';

    /** @var string - az input css-osztálya*/
    public $cssClass        = '';

    /** @var string - disabled*/
    public $disabled        = '';

    /** @var string - data*/
    public $data            = '';

    /** @var string - a tag id-je*/
    public $id              = '';

    /** @var int - maxlength érték */
    public $maxlength       = '';

    /** @var string - a tag css-osztálya*/
    public $name            = '';

    /** @var string - onfocus érték*/
    public $onfocus         = '';

    /** @var string - onblur érték*/
    public $onblur          = '';

    /** @var string - onchange érték*/
    public $onchange        = '';

    /** @var string - onclick érték*/
    public $onclick         = '';

    /** @var string - ondblclick érték*/
    public $ondblclick      = '';

    /** @var string - oninput érték*/
    public $oninput         = '';

    /** @var string - onkeydown érték*/
    public $onkeydown       = '';

    /** @var string - onkeypress érték*/
    public $onkeypress      = '';

    /** @var string - onkeyup érték*/
    public $onkeyup         = '';

    /** @var string - onmousedown érték*/
    public $onmousedown     = '';

    /** @var string - onmousemove érték*/
    public $onmousemove     = '';

    /** @var string - onmouseout érték*/
    public $onmouseout      = '';

    /** @var string - onmouseover érték*/
    public $onmouseover     = '';

    /** @var string - placeholder értéke */
    public $placeholder     = '';

    /** @var string - input utáni tartalom pl.: Ft, vagy ikon kép stb..*/
    public $post            = '';

    /** @var string - input előtti tartalom pl.: http://*/
    public $pre             = '';

    /** @var string - style érték*/
    public $style           = '';

    /** @var string - tartalom, ami a teg közé megy*/
    public $tartalom        = '';

    /** @var string - az input title-ja*/
    public $title           = '';

    /** @var string - az input value-ja*/
    public $value           = '';



    /** @var string - ha általános teg, akkor ezt itt tároljuk le és a getHtml innen olvasa kiíráshoz*/
    private $tag            = '';

    /**
    * @desc Konstruktor
    *
    * @param strgin tag. Ha meg van adva, akkor értelmezhető a getHtml, mely egy általános Html teg-et ad vissza
    * @param strgin $tartalom, mely a két tag közé megy
    */
   	public function __construct($tag = '', $tartalom = ''){
        $this -> tag      = $tag;
        $this -> tartalom = $tartalom;
    }

    /**
    * @desc Input tulajdonságait rajtak össze (id, name, style, onmouse..)
    *
    * @return string -  Inputtulajdonságok kódja
    */
   	protected function getAttributumok(){

        $return = '';

        if($this -> alert       ){ $return .= ' data-alert="'   .$this -> alert         .'"'; }
        if($this -> autocomplete){ $return .= ' autocomplete="' .$this -> autocomplete  .'"'; }
        if($this -> cssClass    ){ $return .= ' class="'        .$this -> cssClass      .'"'; }
        if($this -> data        ){ $return .= ' '.$this -> data;                              }
        if($this -> disabled    ){ $return .= ' readonly';                                    }
        if($this -> maxlength   ){ $return .= ' maxlength="'    .$this -> maxlength     .'"'; }
        if($this -> name        ){ $return .= ' name="'         .$this -> name          .'"'; }
        if($this -> id          ){ $return .= ' id="'           .$this -> id            .'"'; }
        if($this -> onblur      ){ $return .= ' onblur="'       .$this -> onblur        .'"'; }
        if($this -> onclick     ){ $return .= ' onclick="'      .$this -> onclick       .'"'; }
        if($this -> onchange    ){ $return .= ' onchange="'     .$this -> onchange      .'"'; }
        if($this -> ondblclick  ){ $return .= ' ondblclick="'   .$this -> ondblclick    .'"'; }
        if($this -> onfocus     ){ $return .= ' onfocus="'      .$this -> onfocus       .'"'; }
        if($this -> oninput     ){ $return .= ' oninput="'      .$this -> oninput       .'"'; }
        if($this -> onkeydown   ){ $return .= ' onkeydown="'    .$this -> onkeydown     .'"'; }
        if($this -> onkeypress  ){ $return .= ' onkeypress="'   .$this -> onkeypress    .'"'; }
        if($this -> onkeyup     ){ $return .= ' onkeyup="'      .$this -> onkeyup       .'"'; }
        if($this -> onmousedown ){ $return .= ' onmousedown="'  .$this -> onmousedown   .'"'; }
        if($this -> onmousemove ){ $return .= ' onmousemove="'  .$this -> onmousemove   .'"'; }
        if($this -> onmouseout  ){ $return .= ' onmouseout="'   .$this -> onmouseout    .'"'; }
        if($this -> onmouseover ){ $return .= ' onmouseover="'  .$this -> onmouseover   .'"'; }
        if($this -> placeholder ){ $return .= ' placeholder="'  .$this -> placeholder   .'"'; }
        if($this -> style       ){ $return .= ' style="'        .$this -> style         .'"'; }
        if($this -> title       ){ $return .= ' title="'        .$this -> title         .'"'; }
        if($this -> value!=''   ){ $return .= ' value="'        .$this -> value         .'"'; }


        return $return;

    }

    /**
    * @desc Általános tageknél (<b></b>, <>). Ha nincs megadva tartalom, úgy a {TARTALOM} sablon használja
    *
    * @return string -  Inputtulajdonságok kódja
    */
   	public function getHtml(){
        if( $this->tag ) {
            if( !$this->tartalom && $this->tag != 'textarea' ) {
                $this -> tartalom = '{TARTALOM}';
            }
            return $this -> pre.'<'.$this -> tag.$this -> getAttributumok().'>'.$this -> tartalom.'</'.$this -> tag.'>'.$this -> post;
        }
    }

}
