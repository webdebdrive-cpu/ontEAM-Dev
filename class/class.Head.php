<?php
/**
* Head objektum. A weboldal tetejét készíti el (metatagok, kódolás stb... beállítása) 
* 
* @author www.WebDeb.hu - Bollmann Krisztián 2009.06.03
*/

class Head{
	
    /** @var string Charset - az oldal karakterkódolása*/
    public $charset  = 'utf-8';
    
    /** @var string Country - Ország*/
    public $country  = 'Hungary';
    
    /** @var string További js vagy css scriptek (pl. vízszintes menühöz)*/
    public $extrak   = '';
	    
    /** @var string Favicon - Ikon beállítása (a megadott elérési útvonalon létezik a fájl)*/
    public $favicon  = '';
    
    /** @var string Language - nyelv*/
    public $language = 'hu';
    
    /** @var int Title hossz - max mekkora legyen, ha 0 akkor korlátlan */
    public $title_hossz    = '';
    
    /** @var int Description hossz - max mekkora legyen, ha 0 akkor korlátlan */
    public $desc_hossz     = '';
    
    /** @var int Keywords hossz - max mekkora legyen, ha 0 akkor korlátlan */
    public $keywords_hossz = '';
    
    
    
    /** @var string A head tartalma*/
    private $head     = '';
    
    /** @var string Doctype - alapértelmezetten strict.dtd*/
    private $doc_type = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

    /** @var string Title - Az aktuális oldal title tagja*/
    private $title    = '';
    
    /** @var string Description - Az aktuális oldal description tagja*/
    private $desc     = '';
    
    /** @var string Keywords - Az aktuális oldal keywords tagja*/
    private $keywords = '';
				
    /** @var string OG meta - A közösségi oldalak (facebook) metatagjai*/
    private $og_meta = '';
    
    /**
    * @desc Konstruktor
    */
   	public function __construct(){
		
	}
	
	/**
	* @desc Doctype beállíátsa
	* 
	* @param string doc_type Lehetséges értékei: transitional, frameset, strict
	*/
	public function setDocType($doc_type){
		switch ( $doc_type ){
			case 'transitional': $this -> doc_type = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'; break;
			case 'frameset':     $this -> doc_type = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';  break;
			default:             $this -> doc_type = '<!DOCTYPE html>';
		}
	}

	/**
	* @desc Doctype lekérdezése
	* 
	* @return string doc_type Lehetséges értékei: transitional, frameset, strict
	*/
	public function getDocType(){
		switch ( $this -> doc_type ){
			case '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">': return 'transitional'; break;
			case '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">':  return 'frameset'; break;
			default: $this -> doc_type = '<!DOCTYPE html>'; return '';
		}
	}
	
	/**
	* @desc getHtml
	* 
	* @return string a head forráskódja
	*/
	public function getHtml(){
		
        global $tartalom_tomb;
        
		$head = $this -> doc_type;
		$head .= '<html lang="'.$this -> language.'">';
		$head .= '<head>';
		$head .= '<title>'.$this -> title.'</title>';
		$head .= '<meta name="author" content="'.get_nev().'">';
		$head .= '<meta http-equiv="Content-Type" content="text/html; charset='.$this -> charset.'">';
		$head .= $this -> og_meta;
        $head .= '<meta name="description" content="'.$this -> desc.'">';
		$head .= '<meta name="keywords" content="'.$this -> keywords.'">';
		$head .= '<meta name="robots" content="All, Index, Follow">';
		$head .= '<meta name="rating" content="General">';
		$head .= '<meta name="format-detection" content="telephone=no">';
		$head .= '<meta name="dcterms.audience" content="Global">';
		$head .= '<meta name="revisit-after" content="1 days">';
        $head .= get_canonical();
        
        if(get_alk()){
            if( basename( dirname(self()) ).'/' == get_admin()){
                $head .= '<base href="'.get_alk().get_admin().'">';
            }else{
                $head .= '<base href="'.get_alk().'">';
            }
        }
        //HTML 5 alatt nem valid, .hataccess-ben kell meagdni: $head .= '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /> ';//CSS3-hoz kell, hogy IE alatt is menjen FUCK
		
        //Amennyiben az oldal responzív, úgy a meta tagokban tiltjuk az oldal nagyíthatóságát (mobilon nem zoom-olunk hanem responzívek vagyunk)
        if(get_responsive() || basename( dirname(self()) ).'/' == get_admin() ){
            $head .= '<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0, user-scalable=0,width=device-width">';
        }
        
		if($this -> favicon){
			$head .= '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">';
            $head .= '<link rel="icon" href="/favicon.png">';
		}
		
        //CSS és JS tömörítés
        if( basename( dirname(self()) ).'/' == get_admin()){
            $js_css         = get_jsfajlok_admin();
            $css_min_fajl   = '../css/wdb_admin_min.css';
            $js_min_fajl    = '../js/wdb_admin_min.js';
            $js_async_fajl  = '../js/wdb_admin_async_min.js';
        }else{
            $js_css         = get_jsfajlok();
            $css_min_fajl   = 'css/wdb_min.css';
            $js_min_fajl    = 'js/wdb_min.js';
            $js_async_fajl  = 'js/wdb_async_min.js';
        }

        $css            = '';
        if(is_file($css_min_fajl)){
            $utolso_mod = filemtime($css_min_fajl);
        }else{
            $utolso_mod = 0;
        }
        $volt_mod       = false;

        //Megnézzük, hogy volt-e bárhol változás időbélyeg szerint
        foreach($js_css['css'] as $css_fajl){
            if(is_file($css_fajl) && filemtime($css_fajl)>$utolso_mod ){
                $volt_mod = true;
                break;
            }
        }

        //Ha volt változás, akkor újrageneráljuk a $css_fajl-t
        if($volt_mod || isset($_GET['minify'])){
            foreach($js_css['css'] as $css_fajl){
                $css .= minify_css($css_fajl /*, Ha megadsz egy 2. paramétert, akkor a rem-ekből PX-t IS csinál, hogy a korábbi IE-vel is kompatibilis legyen*/);
            }
            fajlba_mentes($css_min_fajl, $css);
        }
        $head .= '<link href="'.get_alk().$css_min_fajl.'?cache='.time().'" rel="stylesheet" type="text/css">';
        
        

        //JS tömörítés
        $async_js      = '';
        $js            = '';
        if(is_file($js_min_fajl)){
            $utolso_mod = filemtime($js_min_fajl);
        }else{
            $utolso_mod = 0;
        }
        $volt_mod      = false;

        //Megnézzük, hogy volt-e bárhol változás időbélyeg szetint
        foreach($js_css['js'] as $js_fajl){
            if(strpos($js_fajl, '!js/')!==false){
                $js_fajl = str_replace('!js/', 'js/', $js_fajl);
            }
            if(is_file($js_fajl) && filemtime($js_fajl)>$utolso_mod ){
                $volt_mod = true;
                break;
            }
        }

        //Ha volt változás, akkor újrageneráljuk a $css_fajl-t
        if($volt_mod || isset($_GET['minify'])){
            foreach($js_css['js'] as $js_fajl){
                if(!$admin_oldal){//Adminnál nem lehet csak úgy rátömöríteni a JS-re?
                    if(strpos($js_fajl, '!js/')!==false){
                        $js .= fajlbol_olvas( str_replace('!js/', 'js/', $js_fajl), 1, '', 'Kommenteket szűrve a JS-ből');
                    }else{
                        $async_js .= fajlbol_olvas($js_fajl, 'Tömörítéssel', '', 'Kommenteket szűrve a JS-ből');
                    }
                }else{
                    if(strpos($js_fajl, '!js/')!==false){
                        $js .= fajlbol_olvas( str_replace('!js/', 'js/', $js_fajl), 1, '', 'Kommenteket szűrve a JS-ből');
                    }else{
                        $async_js .= fajlbol_olvas($js_fajl, '', '', 'Kommenteket szűrve a JS-ből');
                    }

                }
            }
            $async_js = str_replace('*/*', '{JQUERY_KODRESZLET}', $async_js);
            $async_js = preg_replace('#/\*.*?\*/#s', '', $async_js);
            $async_js = str_replace('{JQUERY_KODRESZLET}', '*/*', $async_js);

            $js = str_replace('*/*', '{JQUERY_KODRESZLET}', $js);
            $js = preg_replace('#/\*.*?\*/#s', '', $js);
            $js = str_replace('{JQUERY_KODRESZLET}', '*/*', $js);
            
            $js = nl2($js);
            $js = str_replace(array(') +'), array('){MINIFY_TMP}+'), $js);
            $js = str_replace(array(' {', '{ ', ';}', ' }', '} ', '; ', '( ', ' (', ') ', ' )', ' =', '= ', ', '), array('{', '{', '}', '}', '}', ';', '(', '(', ')', ')', '=', '=', ','), $js);
            $js = str_replace(array('){MINIFY_TMP}'), array(') '), $js);
            
            $async_js = nl2($async_js);
            $async_js = str_replace(array(') +'), array('){MINIFY_TMP}+'), $async_js);
            $async_js = str_replace(array(' {', '{ ', ';}', ' }', '} ', '; ', '( ', ' (', ') ', ' )', ' =', '= ', ', '), array('{', '{', '}', '}', '}', ';', '(', '(', ')', ')', '=', '=', ','), $async_js);
            $async_js = str_replace(array('){MINIFY_TMP}+'), array(') +'), $async_js);

            fajlba_mentes($js_async_fajl, $async_js);
            fajlba_mentes($js_min_fajl,   $js);
            
            foreach(get_nyelvek() as $nyelv){
                $js_mit     = array();
                $js_mivel   = array();
                foreach($tartalom_tomb as $kulcs => $tart) {
                    if($kulcs){
                        array_push($js_mit,    '{'.strtoupper($kulcs).'}'   );
                        array_push($js_mivel,   get_tart($kulcs, $nyelv)    );
                    }
                }
                fajlba_mentes( str_replace('.js', '_'.$nyelv.'.js', $js_async_fajl), str_replace($js_mit, $js_mivel, $async_js)  );
                fajlba_mentes( str_replace('.js', '_'.$nyelv.'.js', $js_min_fajl),   str_replace($js_mit, $js_mivel, $js)        );
            }
            
        }

		
		if($this -> extrak){
			$head .= $this -> extrak;
		}
        
		$head .= '</head>';
		
        header('X-Frame-Options: sameorigin');
		header('X-XSS-Protection: 1; mode=block');
		header('Content-Type: text/html; charset='.$this -> charset);
        
		return $head;
	}	
	
	
	
	//SET & GET metódusok
	/**
	* @desc Title beállíátsa
	* 
	* @param string title
	*/
	public function setTitle($title){
        if($this -> title_hossz){
            $this -> title = resz_string( notags($title), $this -> title_hossz, false );
        }else{
            $this -> title = str_replace( '"', '', notags($title) );
        }
	}
	
	/**
	* @desc Title lekrédezése
	* 
	* @return string title
	*/
	public function getTitle(){
		return $this -> title;
	}
	
	
	
	/**
	* @desc Description beállíátsa
	* 
	* @param string desc
	*/
	public function setDesc($desc){
        if($this -> desc_hossz){
            $this -> desc = str_replace( '"', '', resz_string(notags($desc), $this -> desc_hossz, false) );
        }else{
            $this -> desc = str_replace( '"', '', notags($desc));
        }
	}
	
	/**
	* @desc Description lekrédezése
	* 
	* @return string desc
	*/
	public function getDesc(){
		return $this -> desc;
	}
	
	
	
	/**
	* @desc Keywords beállíátsa
	* 
	* @param string keywords
	*/
	public function setKeywords($keywords){
        if($this -> keywords_hossz){
            $this -> keywords = str_replace( '"', '', resz_string(notags($keywords), $this -> keywords_hossz, false) );
        }else{
            $this -> keywords = str_replace( '"', '', notags($keywords));
        }
	}
	
	/**
	* @desc Keywords lekrédezése
	* 
	* @return string keywords
	*/
	public function getKeywords(){
		return $this -> keywords;
	}
    
	/**
	* @desc OG meta beállíátsa
	* 
	* @param string og meta
	*/
	public function setOg($og_meta){
		$this -> og_meta = $og_meta;
	}
	
	/**
	* @desc OG meta lekrédezése
	* 
	* @return string og meta
	*/
	public function getOg(){
		return $this -> og_meta;
	}
	
}
?>