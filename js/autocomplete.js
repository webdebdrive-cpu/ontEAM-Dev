/*
 Használata:

    1. config.php-ban felkiáltójellel hívd meg:
        $utvonal.'!js/autocomplete.js',

    2. Legelegánsabb, ha az esemenyek.js-ben a "$(document).ready()" rész után másold be az alábbi kódot. Az alábbi példában a #ksz ID-jú inputra tesszük a működést:
        $('#ksz').autocomplete( {
            'php_fajl'      : '../autocomplete.php',
            'tabla'         : 'termek',
            'oszlop_keres'  : 'nev, csz',
            'oszlop_listaz' : 'nev, intro'
        });

 */
(function($) {
    $.fn.autocomplete = function(options) {

        var defaults = {
            karaktertol     : 1,                    /* Hány karaktertől induljon a keresés          */
            limit           : 10,                   /* Hány tételt listázzon ki                     */
            szel            : 'auto',               /* Lista szélessége                             */
            php_fajl        : 'autocomplete.php',   /* PHP fájl, amit meghívunk                     */
            tabla           : '',                   /* Adatbázis tábla, amiben keresünk             */
            oszlop_keres    : '',                   /* Adatbázis tábla oszlopa, amiben keresünk     */
            oszlop_listaz   : '',                   /* Adatbázis tábla oszlopa, amiket listázunk    */
            rkat            : 0,                    /* Rendszer kategóriára szűrünk                 */
            id_kiir         : ''
        };

        var options = $.extend(defaults, options);

        $('body').append('<div id="autocomplete"></div>');

        $(this).keyup(function(e) {

            var width   = $(this).innerWidth();
            var height  = $(this).innerHeight();
            var left    = $(this).offset().left;
            var top     = $(this).offset().top;

            var length  = $(this).val().length;
            var val     = $(this).val();
            mezo        = $(this);

            if(length >= options.karaktertol) {
                if(options.szel == 'auto') {

                    $('#autocomplete').css({
                        'width'     : width,
                        'left'      : left,
                        'top'       : top+height,
                        'display'   : 'block'
                    });

                } else {

                    $('#autocomplete').css({
                        'width'     : options.szel,
                        'left'      : left,
                        'top'       : top+height,
                        'display'   : 'block'
                    });
                }

                if(e.keyCode!=40 && e.keyCode!=38 && e.keyCode!=13) {

                    $.ajax({
                        url     : options.php_fajl,
                        type    : 'GET',
                        data: {
                            value           : val,
                            limit           : options.limit,
                            tabla           : options.tabla,
                            oszlop_keres    : options.oszlop_keres,
                            oszlop_listaz   : options.oszlop_listaz,
                            rkat            : options.rkat,
                            id_kiir         : options.id_kiir
                        },
                        success: function(msg) {
                            $('div#autocomplete').html(msg);
                        }
                    });

                }

            } else {

                $('#autocomplete').css({'display' : 'none'});

            }
        });

        $('#autocomplete').on('mouseover', 'ul li', function() {
            $('#autocomplete ul li').removeClass('aktiv');
            $(this).addClass('aktiv');
        });

        $('#autocomplete').on('mouseleave', 'ul li', function() {
            $(this).removeClass('aktiv');
        });

        $('#autocomplete').on('mouseleave', function() {
            $(this).removeClass('aktiv');
        });

        $('#autocomplete').on('click', 'ul li', function() {
            var link = $('#autocomplete ul li.aktiv').attr('data-href'),
                recid = $('#autocomplete ul li.aktiv').attr('data-recid');

            if(link){
                location.href = link;
            }else{
                mezo.val( $('#autocomplete ul li.aktiv').html() ).change();

                if( mezo.attr('name').indexOf('_autocomp') !== -1 ) {
                    mezo_nev = mezo.attr('name').replace('_autocomp', '');
                    $('input[name="' + mezo_nev + '"]').val(recid);
                }
            }
        });

        $(this).keypress(function(e) {
            if($(this).val() == '') {
                if(e.keyCode==13) {
                    return false;
                }
            } else if($('#autocomplete').css('display') == 'block') {
                if(e.keyCode==13) {
                    return false;
                }
            }
        });

        $(document).click(function() {
            $('#autocomplete').css({'display':'none'});
        });
        $(document).keyup(function(e) {
            if(e.keyCode == 27){
                $("#autocomplete").css({'display':'none'});
            }
        });

        $(this).keyup(function(e) {
            var aktiv  = $('#autocomplete ul li.aktiv');
            var first   = $('#autocomplete ul li:first-child');
            var last    = $('#autocomplete ul li:last-child');

            if(e.keyCode==40) {
                if(!$('#autocomplete ul li').hasClass('aktiv')) {
                    first.addClass('aktiv');

                } else {
                    if(!aktiv.is(':last-child')) {
                        aktiv.removeClass('aktiv');
                        aktiv.next().addClass('aktiv');
                    } else {
                        aktiv.removeClass('aktiv');
                        first.addClass('aktiv');
                    }
                }
            }
            if(e.keyCode==38) {
                if(!$('#autocomplete ul li').hasClass('aktiv')) {
                    last.addClass('aktiv');
                } else {
                    if(!aktiv.is(':first-child')) {
                        aktiv.removeClass('aktiv');
                        aktiv.prev().addClass('aktiv');
                    } else {
                        aktiv.removeClass('aktiv');
                        last.addClass('aktiv');
                    }
                }
            }
            if(e.keyCode==13) {
                if( $('#autocomplete ul li').hasClass('aktiv') ) {
                    var link = $('#autocomplete ul li.aktiv').attr('data-href'),
                        recid = $('#autocomplete ul li.aktiv').attr('data-recid');

                    if(link){
                        location.href = link;
                    }else{
                        mezo.val( $('#autocomplete ul li.aktiv').html() ).change();

                        if( mezo.attr('name').indexOf('_autocomp') !== -1 ) {
                            mezo_nev = mezo.attr('name').replace('_autocomp', '');
                            $('input[name="' + mezo_nev + '"]').val(recid);
                        }
                    }

                    $('#autocomplete').css({'display':'none'});
                } else {
                    $('.kereso_gomb').click();
                }
            }
        });
    }
})(jQuery);
