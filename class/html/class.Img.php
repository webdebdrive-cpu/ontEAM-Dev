<?php
/**
* <img -t legyártó osztály. Képes a képek átméretezésére lekicsinyítésére, lénygében a régi kep() függvényt helyettesíti
*
* @author www.WebDeb.hu - Bollmann Krisztián 2009.08.25
*/

require_once('class.Tag.php');
if (!function_exists('mime_content_type')) {
    require_once('mime.php');
}

class Img extends Tag{

    /** @var string - kép alt-ja*/
    public $alt   = '';

    /** @var boolean - A kép scr részéhez számoljunk-e Data URI-t vagy sem */
    public $data_uri = false;

    /** @var int - a pecsét eltolása X irányba az alap képen, százalékosan */
    public $eltol_x = '';

    /** @var int - a pecsét eltolása Y irányba az alap képen, százalékosan */
    public $eltol_y = '';

    /** @var int - a pecsét kicsinyítése az alap képhez képest, százalékosan */
    public $kicsinyit = '';

    /** @var bool - a képnek készít egy bal margót negatív irányba az új szélesség felével, ezzel középre rendezve a képet egy DIV-en belül */
    public $kozepre = false;

    /** @var string - kép maximum magassága (átméretezéshez)*/
    public $magas = '';

    /** @var string  - kép elérési útvonala*/
    public $src   = '';

    /** @var string  - kép maximum szélessége (átméretezéshez)*/
    public $szel  = '';

    /** @var string  - kép új magassága */
    public $uj_magas  = '';

    /** @var string  - kép új szélessége */
    public $uj_szel  = '';

    /** @var boolean - képről készüljön-e thumbnail*/
    public $thumb = true;

    /**
    * @desc Konstruktor
    *
    * @param string $src   - kép elétési útvonala
    * @param string $alt   - kép alt attributuma
    * @param string $szel  - kép max szélessége
    * @param string $magas - kép max magassága
    */
   	public function __construct($src = '', $alt = '', $szel = '', $magas = ''){
        $this -> src   = $src;
        $this -> alt   = $alt;
        $this -> szel  = $szel;
        $this -> magas = $magas;
    }

    /**
    * @desc <a></a> Html kódjának előállítása
    *
    * @return string -  Html kód
    */
   	public function getHtml(){

        if(is_file( $this -> src )){

            $kep_reszek = pathinfo($this -> src);

    	    // Megnézzük hogy thumb létezik-e már, ha igen, akkor csak sima kiiratás, ne számoljunk feleslegesen
            if($this -> thumb){

        		$new_img_name  = $kep_reszek['dirname'].'/';
        		$new_img_name .= basename($this -> src, '.'.$kep_reszek['extension']);
        		$thumbfile     = $new_img_name.'_'.$this -> szel.'x'.$this -> magas.'.'.$kep_reszek['extension'];
                if(is_file( $thumbfile )){
                    $this -> src = $thumbfile;
                    $sajatAttributumok  = '';
                    $sajatAttributumok .= ' alt="'.$this -> alt.'"';

                    if(!$this -> title){
                        $this -> title = $this -> alt;
                    }

                    if($this -> data_uri){
                        $imageData = base64_encode( file_get_contents( $this -> src ) );
                        $this -> src = 'data:'.mime_content_type($this -> src).';base64,'.$imageData;
                    }

                    // Rewrite esetén is jó legyen a hivatkozás
                    if( basename( dirname(self()) ).'/' == get_admin()){
                        $this -> src = '../'.str_replace('../'.get_utvonal(), get_utvonal(), $this -> src);
                        // Korábban ez volt, de szerintem a fenti lesz a helyes $this -> src = get_alk().get_admin().$this -> src;
                    }elseif(!$this -> data_uri){
                        $this -> src = get_alk().$this -> src;
                    }

                    $sajatAttributumok .= ' src="'.$thumbfile.'"';
                    $szel_magas = getimagesize($thumbfile);
                    return '<img width="'.$szel_magas[0].'" height="'.$szel_magas[1].'"'.$sajatAttributumok.$this -> getAttributumok().'>';
                }
            }

            // Ha nem kép, akkor a kiterjesztésének megfelelő ikont rajzolunk ki
            $icon = false;
            $kep_formatumok = array('jpg', 'png', 'gif', 'jpeg');
            if( !in_array( strtolower($kep_reszek['extension']), $kep_formatumok) ){
                $this -> src = 'elemek/ikonok/'.$kep_reszek['extension'].'.png';
                if( basename( dirname(self()) ).'/' == get_admin()){
                    $this -> src = '../'.$this -> src;
                    $icon = true;
                }
            }

            // Ha nem volt thumb a képből, akkor megyünk tovább
    		$data = fread(fopen($this -> src, 'rb'), filesize($this -> src));
    		$src_img = @imagecreatefromstring($data);
            if($src_img){

        	    $this -> uj_szel  = imagesx($src_img);
        	    $this -> uj_magas = imagesy($src_img);

                //Ha csak a szélesség lett megadva, akkor arra az összegre kicsinyíti a képet, intelligensen kiszámítva álló és fekvő képre egyaránt
                //Figyelem! Ekkor minimum ilyen széles/magas lesz egy kép
                if($this -> szel && !$this -> magas && $this -> kozepre){

                    //Ha fekvő: azaz szélesebb mint magasabb
                    if($this -> uj_szel > $this -> uj_magas){
                        $arany = $this -> szel/$this -> uj_magas;
                        $this -> uj_magas = $this -> szel;
                        $this -> uj_szel = floor(abs($this -> uj_szel*$arany));
                    }else{
                        $arany = $this -> szel/$this -> uj_szel;
                        $this -> uj_szel = $this -> szel;
                        $this -> uj_magas = floor(abs($this -> uj_magas*$arany));
                    }

                //Amennyiben adott a szélesség és magasság is, akkor annak megfelelően kicsinyítünk
                }else{

                    if($this -> szel){
                        if($this -> szel < $this -> uj_szel){
                            $arany = $this -> uj_magas / $this -> uj_szel;
                            $this -> uj_szel = $this -> szel;
                            $this -> uj_magas = floor(abs($this -> uj_szel * $arany));
                        }
                    }
                    if($this -> magas){
                        if($this -> magas < $this -> uj_magas){
                            $arany = $this -> uj_szel/$this -> uj_magas;
                            $this -> uj_magas = $this -> magas;
                            $this -> uj_szel = floor(abs($this -> uj_magas*$arany));
                        }
                    }

                }

        		//ha a limit nagyobb mint a kép, akkor felesleges átméretezni, elég kiírni
        		if( ($this -> szel && $this -> szel < imagesx($src_img)) || ($this -> magas && $this -> magas < imagesy($src_img)) ){
        			$this -> src = 'kep.php?kep='.$this -> src.'&amp;szel='.$this -> szel.'&amp;magas='.$this -> magas;
        			if($this -> pecset){
                        $this -> src .= '&amp;pecset='.$this -> pecset.'&amp;eltol_x='.$this -> eltol_x.'&amp;eltol_y='.$this -> eltol_y.'&amp;kicsinyit='.$this -> kicsinyit;
        			}
                    if($this -> thumb){
        				$this -> src .='&amp;thumb=1&amp;szelnev='.$this -> szel.'&amp;magasnev='.$this -> magas;
        			}

        		}

                if( basename( dirname(self()) ).'/' == get_admin()) {
                    if( $icon ) {
                        $this -> src = str_replace('../'.get_utvonal(), get_utvonal(), $this -> src);
                    } else {
                        $this -> src = str_replace('../'.get_utvonal(), get_utvonal(), $this -> src);
                    }
                    // Korábban ez volt, de szerintem a fenti lesz a helyes $this -> src = get_alk().get_admin().$this -> src;
                }elseif(!$this -> data_uri){
                    $this -> src = get_alk().$this -> src;
                }

                $sajatAttributumok  = '';
                $sajatAttributumok .= ' alt="'.$this -> alt.'"';

                if(!$this -> title){
                    $this -> title = $this -> alt;
                }

                //a képnek készít egy bal margót negatív irányba az új szélesség felével, ezzel középre rendezve a képet egy DIV-en belül
                //Update: ezt inkább CSS-el oldjuk meg
//                if($this -> kozepre){
//                    $this -> style .= 'margin-left: -'.abs(round(($this -> uj_szel -$this -> szel )/2/10)).'rem;';
//                    $this -> style .= 'margin-top: -' .abs(round(($this -> uj_magas-$this -> magas)/2/10)).'rem;';
//                }


                if($this -> data_uri){
                    $imageData = base64_encode( file_get_contents( $this -> src ) );
                    $this -> src = 'data:'.mime_content_type($this -> src).';base64,'.$imageData;
                }

                $sajatAttributumok .= ' src="'.$this -> src.'"';

                //width/height attribútumot csak akkor teszünk rá, ha mindkét korlát meg volt adva, egyébként nem feltétlen a kapott szélességű lesz a kép!
                if($_GET['magas'] && $_GET['szelnev']){
                    return '<img width="'.$this -> uj_szel.'" height="'.$this -> uj_magas.'"'.$sajatAttributumok.$this -> getAttributumok().'>';
                }else{
                    return '<img '.$sajatAttributumok.$this -> getAttributumok().'>';
                }
            }
        }
    }

}

?>
