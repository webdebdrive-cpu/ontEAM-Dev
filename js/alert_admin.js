function alert2(uzi, ok_gomb) {

    if( ok_gomb == null ){
        ok_gomb = '<p class="c"><input type="button" class="link gomb" value="Ok" onclick="alert2()"></p>';
    }

    /*Üres alert eltűntetés*/
    if(!uzi){


        $('#szurkit').fadeOut(200);
        $('#alert2').fadeOut(200, function(){
            $('#szurkit').remove();
            $('#alert2').remove();
        });
        return true;

    }else{

        /*kivesszük, mivel újból hozzá lesz adva*/
        $('#szurkit').remove();
        $('#alert2').remove();

        if(uzi.substring(0,1)=='#'){

            uzi = $(uzi).html();
            alert_kiir(uzi, ok_gomb);
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

    var alert2_html = '';
    alert2_html    += '<div id="alert2"><div class="figy"><div class="header">Kérdés - megerősítés</div><div class="tart">'+uzi+'</div><div class="f_footer">'+ ok_gomb +'</div></div></div>';

    $('body').append('<div id="szurkit" class="dn"></div>');
    $('body').append(alert2_html);
    $('#szurkit, .szurkit').css({'opacity' : 0.8});

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
