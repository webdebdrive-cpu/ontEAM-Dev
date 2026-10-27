<?php
/*                                                                                                                  *\
    A fáj $_GET-ben megkapja a kép útvonalát, a szélességét, magasságát. Ez utóbbiakat az Img osztály
    már kiszámolta. Ha a $thumb nem null, akkor megnézi, hogy van-e már adott méretű thumbkép
    (utvonal/kepnev_szelxmagas.kit). Ha létezik nem csinál semmit, csak kiírja, ha nem létezik akkor elkészíti
    és ezután írja ki. 

    Amennyiben kép átméretezésnél nem találná a képet, vagy valami hiba lép fel, akkor a 
    get_utvonal().'nincskep.jpg'-t írja ki.
    Ide elvileg már el sem jut soha, mivel ezt az Img osztály függvény is vizsgálja.
\*                                                                                                                  */

function thumb_kep_kiir($thumbfile) {
    $handle    = fopen($thumbfile, 'rb');
    $thumbnail = fread($handle, filesize($thumbfile));
    echo $thumbnail;
    fclose($handle);
}

/**
* @desc Csak a weboldal könyvtárán belüli, képkiterjesztésű helyi fájl fogadható el (URL, ../config.php és hasonlók nem)
*
* @param  string $utvonal A kapott útvonal
* @param  string $gyoker  A weboldal gyökérkönyvtára
* @return boolean
*/
function kep_utvonal_ervenyes($utvonal, $gyoker) {
    $fajl = realpath($utvonal);
    if( !$fajl || !is_file($fajl) || strpos($fajl, $gyoker.DIRECTORY_SEPARATOR) !== 0 ) {
        return false;
    }
    return in_array(strtolower(pathinfo($fajl, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

$gyoker = dirname(__DIR__);
if( !isset($_GET['kep']) || !kep_utvonal_ervenyes($_GET['kep'], $gyoker) ) {
    $_GET['kep'] = '';
}
if( !empty($_GET['pecset']) && !kep_utvonal_ervenyes($_GET['pecset'], $gyoker) ) {
    $_GET['pecset'] = '';
}
// Méretek, eltolások csak egész számok lehetnek (a méretek felső korláttal), mert a thumbnail fájlnevébe is bekerülnek
foreach ( ['szel', 'magas', 'szelnev', 'magasnev'] as $meret ) {
    $_GET[$meret] = isset($_GET[$meret]) ? min(abs((int)$_GET[$meret]), 3000) : 0;
}
foreach ( ['eltol_x', 'eltol_y', 'kicsinyit'] as $ertek ) {
    if( isset($_GET[$ertek]) ) {
        $_GET[$ertek] = (int)$_GET[$ertek];
    }
}

$thumbfile = '';
if( !empty($_GET['thumb']) && $_GET['kep'] ) {

    $kep_reszek    = pathinfo($_GET['kep']);
    $new_img_name  = $kep_reszek['dirname'].'/';
    $new_img_name .= basename($_GET['kep'], '.'.$kep_reszek['extension']);
    $thumbfile     = $new_img_name.'_'.$_GET['szelnev'].'x'.$_GET['magasnev'].'.'.$kep_reszek['extension'];
    
    if( file_exists($thumbfile) ) {
        $handle    = fopen($thumbfile, 'rb');
        $thumbnail = fread($handle, filesize($thumbfile));
        echo $thumbnail;
        fclose($handle);
    }
}

if( !$_GET['thumb'] || ($_GET['thumb'] && !file_exists($thumbfile)) ) {

    $sikeres_megnyitas = @fopen($_GET['kep'], 'rb');
    if( $sikeres_megnyitas ) {

        $kepadatok  = getimagesize($_GET['kep']);
        $data       = fread(fopen($_GET['kep'], 'rb'), filesize($_GET['kep']));
		$src_img    = @imagecreatefromstring($data);
        
        if( $src_img ) {
            $new_w   = imagesx($src_img);
            $new_h   = imagesy($src_img);
            
            // Ha csak a szélesség lett megadva, akkor arra az összegre kicsinyíti a képet, intelligensen kiszámítva álló és fekvő képre egyaránt
            // Figyelem! Ekkor minimum ilyen széles/magas lesz egy kép
            if( $_GET['szel'] && !$_GET['magas'] ) {

                // Ha fekvő: azaz szélesebb mint magasabb
                if($new_w > $new_h){
                    $aspect_ratio = $_GET['szel']/$new_h;
                    $new_h = $_GET['szel'];
                    $new_w = floor(abs($new_w*$aspect_ratio));
                }else{
                    $aspect_ratio = $_GET['szel']/$new_w;
                    $new_w = $_GET['szel'];
                    $new_h = floor(abs($new_h*$aspect_ratio));
                }
                
            // Amennyiben adott a szélesség és magasság is, akkor annak megfelelően kicsinyítünk
            }else{
                
                if($_GET['szel']){
                    if($_GET['szel']<$new_w){
                        $aspect_ratio = $new_h/$new_w;
                        $new_w = $_GET['szel'];
                        $new_h = floor(abs($new_w*$aspect_ratio));
                    }
                }
                if($_GET['magas']){
                    if($_GET['magas']<$new_h){
                        $aspect_ratio = $new_w/$new_h;
                        $new_h = $_GET['magas'];
                        $new_w = floor(abs($new_h*$aspect_ratio));
                    }
                }
                
            }
            
            // PNG-t másképp méretezünk, mert átlátszó is lehet
            if($kepadatok['mime']=='image/png' || $kepadatok['mime']=='image/x-png'){
                
				if(is_file('engine.php')){
					require_once('weblap.php');
				}else{
					require_once('../weblap.php');
				}
                $dst_img = atlatszo_kep_atmeretez($src_img, $new_w, $new_h);

                Header ('Content-type: image/png');
    			if( $thumbfile ) {
                    // Ez miért kellett ide? Szerntem törölhető imagepng($dst_img,$thumbfile);
                    if($_GET['pecset']){
                        $dst_img = logot_pecsetel($thumbfile, $_GET['pecset'], $_GET['eltol_x'], $_GET['eltol_y'], $_GET['kicsinyit']);
                        
                        imagealphablending($dst_img, false);
                        imagesavealpha($dst_img, true);
                        // Progresszívvá alakítása
                        imageinterlace($dst_img, 1);
                    }
                    imagepng($dst_img, $thumbfile);
                    thumb_kep_kiir($thumbfile);
    			}else{
                    imagealphablending($dst_img, false);
                    imagesavealpha($dst_img, true);
                    // Progresszívvá alakítása
                    imageinterlace($dst_img, 1);
                    
    				imagepng($dst_img);
    			}
            
            // JPG és GIF
            }else{
        	    
        		$dst_img = imagecreatetruecolor($new_w,$new_h);
        		imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_w,$new_h,imagesx($src_img),imagesy($src_img));
                
        		if($kepadatok['mime'] == 'image/jpeg' || $kepadatok['mime'] == 'image/pjpeg'){
        			Header ('Content-type: image/jpeg');
        			if($thumbfile){
                        // Ez miért kellett ide? Szerntem törölhető imagejpeg($dst_img,$thumbfile);
                        if($_GET['pecset']){
                            $dst_img = logot_pecsetel($thumbfile, $_GET['pecset'], $_GET['eltol_x'], $_GET['eltol_y'], $_GET['kicsinyit']);
                        }
                        
                        // Progresszívvá alakítása
                        imageinterlace($dst_img, 1);
                        imagejpeg($dst_img, $thumbfile, 100);
                        
                        thumb_kep_kiir($thumbfile);
        			}else{
                        // Progresszívvá alakítása
                        imageinterlace($dst_img, 1);
        				imagejpeg($dst_img);
        			}
        		}
                
        		if( $kepadatok['mime'] == 'image/gif' ) {
                    Header ('Content-type: image/gif');
        			if( $thumbfile ) {
                        // Ez miért kellett ide? Szerntem törölhető imagegif($dst_img,$thumbfile);
                        if($_GET['pecset']){
                            $dst_img = logot_pecsetel($thumbfile, $_GET['pecset'], $_GET['eltol_x'], $_GET['eltol_y'], $_GET['kicsinyit']);
                        }
                        // Progresszívvá alakítása
                        imageinterlace($dst_img, 1);
                        
                        imagegif($dst_img, $thumbfile);
                        thumb_kep_kiir($thumbfile);
        			}else{
                        // Progresszívvá alakítása
                        imageinterlace($dst_img, 1);
                        
        				imagegif($dst_img);
        			}
        		}
                
            }
        }
        
    // Sikertelen megnyitás esetén
	} else {

        require_once('config.php');
        $handle    = fopen(get_utvonal().'nincskep.jpg', 'rb');
        $thumbnail = fread($handle, filesize(get_utvonal().'nincskep.jpg'));
        echo $thumbnail;
        fclose($handle);
        
	}
}
?>