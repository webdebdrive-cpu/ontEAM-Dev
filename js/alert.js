/*
 * @desc alert3 
 * Szolid értesítési rendszer, avagy: Hogyan ne kapjon agyhalált a felhasználó!
 * 
 * @param String text
 * @param Object options
 * @returns Alert3
 * 
 * Általános meghívási alakja:
 * alert3('Teszt üzenet');
 * 
 * Ha automatikus bezárás nélkül akarjuk meghívni:
 * alert3('Teszt üzenet', {'eltunik': false});
 * 
 * Ilyen esetben üres text paraméterrel tudjuk bezárni:
 * alert3('');
 * 
 * options paraméterek a következőek lehetnek:
 * - 'honnan': A képernyő melyik részéről ússzon be az üzenet, értékei: balFent | balLent | jobbFent | jobbLent | fentKozep | lentKozep
 * - 'eltunes': Hány másodperc alatt tűnjön el az üzenet,
 * - 'eltunik': Eltűnjön e automatikusan az alert3, vagy sem,
 * - 'animacio': A beúszás sebessége,
 * - 'eace': Milyen finom íze legyen az animációnak, értékei: google.com :)
 * 
 * Köszönöm a figyelmet!
 * 
 */
$(function() {
    
    alert3 = function(text, options) {
        var defaults = {
            'honnan'  : 'lentKozep',
            'eltunes' : 3000,
            'eltunik' : true,
            'animacio': 600,
            'ease'    : 'easeInOutElastic'
        };
        
        var options = $.extend(defaults, options);
        
        if( text === '' ) {
            if( irany === 'top' ) {
                /* Alert3 pozíciója */
                var padd  = parseInt( $('#alert3').css('left') ),
                    ertek = $('#alert3').outerHeight();
            } else if( irany === 'right' ) {
                /* Alert3 pozíciója */
                var padd  = parseInt( $('#alert3').css('left') ),
                    ertek = $('#alert3').outerWidth();
            } else if( irany === 'bottom' ) {
                /* Alert3 pozíciója */
                var padd  = parseInt( $('#alert3').css('left') ),
                    ertek = $('#alert3').outerHeight();
            } else if( irany === 'left' ) {
                /* Alert3 pozíciója */
                var padd  = parseInt( $('#alert3').css('left') ),
                    ertek = $('#alert3').outerWidth();
            }
            
            var param = {opacity: 0};
            param[irany] = '-' + ertek;

            $('#alert3').animate(param, options.animacio, options.ease, function() {
                $(this).remove();
            });
            
            return false;
        }
        
        /* Generálunk egy div-et, amibe majd  a tartalom fog kerülni */
        $('body').append('<div id="alert3"></div>');
        $('#alert3').text( text );
        
        /* Pozíciók beállítása */
        if( options.honnan === 'balFent' ) {
            $('#alert3').css({
                'top': '1rem',
                'left': '1rem'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('left') );
                irany = 'left';
            
        } else if( options.honnan === 'balLent' ) {
            $('#alert3').css({
                'bottom': '1rem',
                'left': '1rem'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('left') );
                irany = 'left';
            
        } else if( options.honnan === 'jobbFent' ) {
            $('#alert3').css({
                'top': '1rem',
                'right': '1rem'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('right') );
                irany = 'right';
            
        } else if( options.honnan === 'jobbLent' ) {
            $('#alert3').css({
                'bottom': '1rem',
                'right': '1rem'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('right') );
                irany = 'right';
            
        } else if( options.honnan === 'fentKozep' ) {
            $('#alert3').css({
                'top': '1rem',
                'left': '50%'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('top') );
                irany = 'top';
        } else if( options.honnan === 'lentKozep' ) {
            $('#alert3').css({
                'bottom': '1rem',
                'left': '50%'
            });
            
            /* Alert3 pozíciója */
            var padd  = parseInt( $('#alert3').css('bottom') );
                irany = 'bottom';
        }
        
        /* Alert3 szélesség */
        var width = $('#alert3').outerWidth();
        
        if( irany === 'top' || irany === 'bottom' ) {
            /* Alert3 szélesség */
            var height = $('#alert3').outerHeight();
            
            $('#alert3').css('margin-left', -(width / 2));
            
            /* Átadjuk a magasság értékét */
            width = height;
        }
        
        /* Kettő összege */
        var osszeg = width + padd;

        /* Kihúzás a képernyőről */
        $('#alert3').css(irany, '-' + (osszeg) + 'px ');
        
        /* Animált becsúszás */
        var param = {opacity: 1};
        param[irany] = padd;
        
        $('#alert3').animate(param, options.animacio, options.ease);
        
        /* Adott idő múlva eltűntetjük magától */
        if( options.eltunik ) {
            setTimeout(function() { 
                var param = {opacity: 0};
                param[irany] = '-' + osszeg;

                $('#alert3').animate(param, options.animacio, options.ease, function() {
                    $(this).remove();
                });
            }, options.eltunes);
        }
    };
});



/* @desc Alert2 
*/
var alert2_tmp    = '';
var alert2_tmp_id = '';
function alert2(uzi, ok_gomb){
    
    if( ok_gomb == null ){
        ok_gomb = '<p class="c"><input type="button" class="link gomb" value="Rendben" onclick="alert2()"></p>';
    }
    
    /*Üres alert eltűntetés*/
    if(!uzi){
    
        $('#szurkit').fadeOut(200);
        $('#alert2').fadeOut(200, function(){
            $('#szurkit').remove();
            $('#alert2').remove();
        });
        
        /* Ha egy rejtett (pl.) DIV-ből olvastuk be, akkor visszamásoljuk */
        if(alert2_tmp){
            $(alert2_tmp_id).html(alert2_tmp);
            alert2_tmp    = '';
            alert2_tmp_id = '';
        }
        
        return true;
    
    }else{
    
        /*kivesszük, mivel újból hozzá lesz adva*/
        $('#szurkit').remove();
        $('#alert2').remove();
        
        if(uzi.substring(0,1)=='#'){

            uzi_tartalom   = $(uzi).html();
            /* Hogy ne legyen duplázódás, a ID tartalmát nullázzuk */
            alert2_tmp     = uzi_tartalom;
            alert2_tmp_id  = uzi;
            
            $(uzi).html('');
            alert_kiir(uzi_tartalom, ok_gomb);
            return true;
        
        }else if(uzi.substring(0,2)=='f:'){

            $('body').append('<div id="tmp"></div>');
            
            var utvonal = uzi.replace('f:', '');
            $('#tmp').load(utvonal, function(){
                uzi = $('#tmp').html();
                alert_kiir(uzi, ok_gomb);
                $('#tmp').remove();
                return true;
            });
            
        }else{
            
            alert_kiir(uzi, ok_gomb);
            return true;
            
        }
    }

}

function alert_kiir(uzi, ok_gomb){
    
    var alert2_html = '';/*<img src="elemek/admin/alert_x.png" alt="X" class="bezar link" onclick="alert2()">*/
    alert2_html    +=   '<div id="alert2">'                         +
                        '<div class="alert_figy">'                  +
                        '<div class="alert_header">Üzenet</div>'    + 
                        '<div class="alert_tart">'+uzi+'</div>'     + 
                        '<div class="f_footer">'+ ok_gomb +'</div></div></div>';
    
    $('body').append('<div id="szurkit" onclick="alert2()"></div>');
    $('body').append(alert2_html);
    $('#szurkit').css({'opacity' : 0.2});
    
    var windowWidth  = document.documentElement.clientWidth;
    var windowHeight = document.documentElement.clientHeight;
    var popupHeight  = $('#alert2').height();
    var popupWidth   = $('#alert2').width();
    
    var top_ertek  = windowHeight/2-popupHeight/2;
    var left_ertek = windowWidth/2-popupWidth/2;
    
    $('#alert2').css({
        'top' : top_ertek,
        'left': left_ertek
    });

    $('#szurkit').fadeIn(200);
    $('#alert2').fadeIn(200);
    
    return true;
}

function alert_raz(){
    
    var windowWidth  = document.documentElement.clientWidth;
    var popupWidth   = $('#alert2').width();
    
    var left_ertek = windowWidth/2-popupWidth/2;
    
    $('#alert2').animate({
                    'left': ((left_ertek-10)/10)+'rem'
                }, 100, function(){
                    $('#alert2').animate({
                        'left': ((left_ertek+20)/10)+'rem'
                    }, 100, function(){
                        $('#alert2').animate({
                            'left': (left_ertek/10)+'rem'
                        }, 100)
                    });
                });
    
}

document.onkeydown = function(e){
	var kC  = (window.event) ? 	event.keyCode : e.keyCode; /* MSIE or Firefox? */
	var Esc = (window.event) ?	27 : e.DOM_VK_ESCAPE; /* MSIE : Firefox */ 
	if(kC == Esc && $('#szurkit').length){
		alert2();
	}
}
;