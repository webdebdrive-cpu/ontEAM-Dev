/*                                                                                                                      *\
    Események figyelés JS-el
    onclick="valami()" szerű függvényhívások központosítása.

    Adott HTML elemre f_xxx nevű classokat tegyünk, ha egy eseményre JS függvényt szeretnénk hívni. 
    Az 'f_' frefix jelzi, hogy ez függvényhívás miatt van jelen, ettől függetlenül erre formázást is adhatunk
    CSS-ben.
    Ajax esetén 'phpfajl.php' -ben szintén ezt az 'f_fuggveny_neve' GET/POST-ot figyeljük, hogy következetesek legyünk.
    
    Példakód rá:

\*                                                                                                                      */

$(document).ready(function() {
    
    /* Gyári beállítások */
    
    $(document).on('keyup', '.f_adoszam', function(){
        $(this).val( adoszam_formaz($(this).val()) );
    });
    $(document).on('blur', '.f_adoszam', function(){
        $(this).val( adoszam_formaz($(this).val()) );
    });
    $(document).on('keyup', '.f_csakszam', function(){
        $(this).val( onlyNumbers($(this).val()) );
    });
    $(document).on('blur', '.f_csakszam', function(){
        $(this).val( onlyNumbers($(this).val()) );
    });
    $(document).on('keyup', '.f_mobilszam', function(){
        $(this).val( onlyNumbersMobilhoz($(this).val()) );
    });
    $(document).on('blur', '.f_mobilszam', function(){
        $(this).val( onlyNumbersMobilhoz($(this).val()) );
    });
    
    $('.f_mail_rejtes').each(function() {
        $(this).replaceWith( mail_rejtes($(this).html()) );
    });
    
    if(!readCookie('cookie_jog')){
        $('div.cookie').removeClass('rejt');
    }
    
    /* Fancybox */
    $.getScript('js/fancybox.js', function() {
        $('a.light').fancybox({
            'prevEffect'            : 'fade',
            'nextEffect'            : 'fade'
        });

        $('a.youtube').click(function() {
            $.fancybox({
                    'padding'       : 0,
                    'autoScale'     : false,
                    'transitionIn'  : 'none',
                    'transitionOut' : 'none',
                    'title'         : this.title,
                    'width'         : 680,
                    'height'        : 495,
                    'href'          : this.href.replace(new RegExp("watch\\?v=", "i"), 'v/'),
                    'type'          : 'swf',
                    'swf'           : {
                        'wmode'             : 'transparent',
                        'allowfullscreen'   : 'true'
                    }
                });
            return false;
        });
        
    });
    
    // SVG képek cseréje inline beágyazásra
    inline_svg();
    
});

// Azért van külön függvényben, mert egy-egy ajax hívás után is lehet le kell futtatni
function inline_svg(){
    $('img.f_svg').each(function(){
        var $img     = jQuery(this);
        var imgID    = $img.attr('id');
        var imgClass = $img.attr('class');
        var imgURL   = $img.attr('src');

        $.get(imgURL, function(data) {
            
            // Csak az SVG tag-el foglalkozunk, a többit elhagyjuk
            var $svg = $(data).find('svg');

            // ID beállítása, ha volt
            if(typeof imgID !== 'undefined') {
                $svg = $svg.attr('id', imgID);
            }
            
            // CSS beállítása, ha volt
            if(typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass+' replaced-svg');
            }

            // Felesleges XML tag-ek eltávolítása
            $svg = $svg.removeAttr('xmlns:a');

            // Kép cseréje az SVG kódra
            $img.replaceWith($svg);

        }, 'xml');

    });
}