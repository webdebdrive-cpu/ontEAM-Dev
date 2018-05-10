focused = false;
function alert2(uzi, ok_gomb, cim) {
    
    /* Ha feljöün egy új hibaüzenet, akkor a szürkítést kapcsoljuk le, ez fontos, mert különben nem lehet majd a figylemeztetést kikapcsolni. */
    szurkit_zar();
    
    if( !uzi && focused ) {
        focused.focus();
    } else {
        setTimeout(function() {
            focused = document.activeElement;
        }, 10);
    }

    if( !ok_gomb ){
        ok_gomb = '<input type="button" class="link gomb" value="Ok" onclick="alert2()">';
    }

    /* Üres alert eltűntetés */
    if(!uzi){

        $('#szurkit').fadeOut(200);
        $('#alert2').fadeOut(200, function(){
            $('#szurkit').remove();
            $('#alert2').remove();
        });

        /* Hibaüzenetkor ha adtunk "hibas_ide_focus" class-t egy inputra, akkor arra fókuszáljunk rá. */
        $('input.hibas_ide_focus, textarea.hibas_ide_focus, select.hibas_ide_focus').focus().removeClass('hibas_ide_focus');

        return true;

    }else{

        /* kivesszük, mivel újból hozzá lesz adva */
        $('#szurkit').remove();
        $('#alert2').remove();

        if(uzi.substring(0,1)=='#'){

            uzi = $(uzi).html().replace('{KSZ}', '&amp;ksz='+$('#rendezesKeres').val());
            alert_kiir(uzi, ok_gomb, cim);
            return true;

        }else if(uzi.substring(0,2)=='f:'){

            $('body').append('<div id="tmp"></div>');

            var utvonal = uzi.replace('f:', '');
            $('#tmp').load(utvonal, function(){
                uzi = $('#tmp').html();
                alert_kiir(uzi, ok_gomb, cim);
                $('#tmp').remove();
                return true;
            });

        }else{

            alert_kiir(uzi, ok_gomb, cim);
            return true;

        }
    }

}

function alert_kiir(uzi, ok_gomb, cim){

    if(!cim){
        cim = 'Kérdés';
    }
    var alert2_html = '';
    alert2_html    += '<div id="alert2"><div class="figy"><div class="header">'+cim+'<div class="bezar" onclick="alert2()"></div></div><div class="tart">'+uzi+'</div><div class="f_footer c">'+ ok_gomb +'</div></div></div>';

    $('body').append('<div id="szurkit" class="dn"></div>');
    $('body').append(alert2_html);
    $('#szurkit, .szurkit').css({'opacity' : 0.2});

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

/*  Ez nem tudom miért volt lényeges, de kivettem, mert emiatt nem lehetett az alert ablak alatt kattintva bezárni 2014.04.07
    $('#alert2').css({
        'height': windowHeight
    });
*/


    $('#szurkit, .szurkit').fadeIn(200);
    $('#alert2').fadeIn(200);
    $('#alert2 input:text, #alert2 textarea').first().focus();


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
                            'left': ((left_ertek)/10)+'rem'
                        }, 100)
                    });
                });

}
;
