/*                                                                                                                      *\
    Események figyelés JS-el
    onclick="valami()" szerű függvényhívások központosítása.

    Adott HTML elemre f_xxx nevű classokat tegyünk, ha egy eseményre JS függvényt szeretnénk hívni. 
    Az 'f_' frefix jelzi, hogy ez függvényhívás miatt van jelen, ettől függetlenül erre formázást is adhatunk
    CSS-ben.
    Ajax esetén 'phpfajl.php' -ben szintén ezt az 'f_fuggveny_neve' GET/POST-ot figyeljük, hogy következetesek legyünk.
    
    Példakód rá:

\*                                                                                                                      */


/*  JQUERY ASZYNCRON BETÖLTÉSE:

    A Google Insights szerint amit csak lehet, asynron módon töltsünk be, hogy a forráskód elején levő JS-ek ne akadályozzák az oldal gyors betöltését.
    A Jquery-t így tudod betölteni:
    
    1.  Az alábbi kódot helyezd el ide, az esemenyek.js elejére. Ez gyakorlatilag maga a jquery.js tartalma, amit így töltünk be. 
        A 2. sorban hívjuk meg a jquery.easing részt is, ezt szintén itt tedd meg.
var jQl={q:[],dq:[],gs:[],ready:function(a){"function"==typeof a&&jQl.q.push(a);return jQl},getScript:function(a,c){jQl.gs.push([a,c])},unq:function(){for(var a=0;a<jQl.q.length;a++)jQl.q[a]();jQl.q=[]},ungs:function(){for(var a=0;a<jQl.gs.length;a++)jQuery.getScript(jQl.gs[a][0],jQl.gs[a][1]);jQl.gs=[]},bId:null,boot:function(a){"undefined"==typeof window.jQuery.fn?jQl.bId||(jQl.bId=setInterval(function(){jQl.boot(a)},25)):(jQl.bId&&clearInterval(jQl.bId),jQl.bId=0,jQl.unqjQdep(),jQl.ungs(),jQuery(jQl.unq()), "function"==typeof a&&a())},booted:function(){return 0===jQl.bId},loadjQ:function(a,c){setTimeout(function(){var b=document.createElement("script");b.src=a;document.getElementsByTagName("head")[0].appendChild(b)},1);jQl.boot(c)},loadjQdep:function(a){jQl.loadxhr(a,jQl.qdep)},qdep:function(a){a&&("undefined"!==typeof window.jQuery.fn&&!jQl.dq.length?jQl.rs(a):jQl.dq.push(a))},unqjQdep:function(){if("undefined"==typeof window.jQuery.fn)setTimeout(jQl.unqjQdep,50);else{for(var a=0;a<jQl.dq.length;a++)jQl.rs(jQl.dq[a]); jQl.dq=[]}},rs:function(a){var c=document.createElement("script");document.getElementsByTagName("head")[0].appendChild(c);c.text=a},loadxhr:function(a,c){var b;b=jQl.getxo();b.onreadystatechange=function(){4!=b.readyState||200!=b.status||c(b.responseText,a)};try{b.open("GET",a,!0),b.send("")}catch(d){}},getxo:function(){var a=!1;try{a=new XMLHttpRequest}catch(c){for(var b=["MSXML2.XMLHTTP.5.0","MSXML2.XMLHTTP.4.0","MSXML2.XMLHTTP.3.0","MSXML2.XMLHTTP","Microsoft.XMLHTTP"],d=0;d<b.length;++d){try{a= new ActiveXObject(b[d])}catch(e){continue}break}}finally{return a}}};if("undefined"==typeof window.jQuery){var $=jQl.ready,jQuery=$;$.getScript=jQl.getScript};
jQl.loadjQ('js/jquery.easing.1.3.js');
jQl.boot();
    
    2. A footer.html végére helyezd el az alábbi kódot:
<script async src="js/jquery.js"></script>

    3. A config.php-ban az $utvonal.'!js/jquery.js' részt kommentezd ki, és írd mögé, hogy "Ennek betöltését az esemenyek.js-ben oldottam meg".

    FONTOS! Sajnos nem minden fog így működni. Pl. az autocomplete.js-t ha a front-end oldalon szeretnéd használni, akkor muszáj a hagyományos módon meghívnod a JS-t.
    FONTOS! Asynkron betöltés esetén a weboldalon nem használhatod a "$(document).ready(function())"-t. Itt, az esemenyek.php-ban használd az összes olyan script-et amit az oldal betöltése után kívánsz használni.

*/

$(document).ready(function() {
    
    $(document).on('click', '.f_fuggveny_neve', function() {
        
        var input = $(this); /* Ha adott elemmel dolgoznál sikeres Ajaxhívás után, mentsd el egy változóba */
        
        $.ajax({
            url: 'phpfajl.php',
            type: 'GET',
            data: esamp2es('parameter=' + $(this).attr('data-parameter') + '&amp;f_fuggveny_neve=' + new Date().getTime()),
            success: function (html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                input.addClass('valami');
                if(html){
                    alert2(html);
                }
            }
        });
    });
    
    
    
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
        
        /*
        Fancybox-hoz útmutató, személyre szabásra minta kódokat itt találsz:
        D:\AppServ\www\!WebDeb Engine v2.8\!MODULOK\Lightbox beépítése - fancybox\index.php
        */

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
    
    //SVG képek cseréje inline beágyazásra
    inline_svg();
    
});

//Azért van külön függvényben, mert egy-egy ajax hívás után is lehet le kell futtatni
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