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
            id_kiir         : '',
            recid           : '',                   /* Az aktuális űrlap reid-ja. Néha szükség van rá */
            mezo            : '',                   /* Szűréssel kapcsolatos GET-ek */
            op              : '',
            felt            : '',
            popupba         : '',
            arazas_beallit  : '',
            csoport         : 0,
            szuros          : ''                    /* Ha a szűrőben hívtuk meg, akkor azt ezen keresztül tudjuk jelezni */
        };

        var van_uj_erteke = false;
        var options = $.extend(defaults, options);

        $(this).attr('data-origin-val', $(this).val());

        if( !$('#autocomplete').length ) {
            $('body').append('<div id="autocomplete"></div>');
        }
        if(options.popupba){
            $('#autocomplete').addClass('popupba');
        }

        $(this).keyup(function(e) {

            var width   = $(this).innerWidth();
            var height  = $(this).innerHeight();
            var left    = $(this).offset().left;
            var top     = $(this).offset().top;

            var length  = $(this).val().length;
            var val     = $(this).val();
            mezo        = $(this);
            setting     = options;

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

                /* Ha nem a szűrőben van az autocomplete, akkor csak azokat a helyiségeket listázza ki,
                 *  ami adott szállodához tartozik, ezért átküldünk egy telephely_id értékete is */
                telephely_id = '';
                if( !options.szuros && (
                                        baseName(location.href) == 'bejelentes' ||
                                        baseName(location.href) == 'utemezett_feladatok' ||
                                        baseName(location.href) == 'rendszeres_karbantartas' ||
                                        baseName(location.href) == 'munkalap' ||
                                        baseName(location.href) == 'eszkozkezeles' ||
                                        baseName(location.href) == 'felmeresi_terv'
                                        )
                ) {
                    telephely_id = $('#telephely_id').val();
                }

                obj_id = '';
                if( !options.szuros && (
                                        baseName(location.href) == 'bejelentes' ||
                                        baseName(location.href) == 'utemezett_feladatok' ||
                                        baseName(location.href) == 'rendszeres_karbantartas' ||
                                        baseName(location.href) == 'munkalap' ||
                                        baseName(location.href) == 'eszkozkezeles' ||
                                        baseName(location.href) == 'felmeresi_terv'
                                        )
                ) {
                    obj_id = $('#obj_id').val();
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
                            id_kiir         : options.id_kiir,
                            csoport         : options.csoport,
                            recid           : options.recid,
                            mezo            : options.mezo,
                            op              : options.op,
                            felt            : options.felt,
                            telephely_id    : telephely_id,
                            obj_id          : obj_id
                        },
                        success: function(msg) {
                            $('div#autocomplete').html(msg);
                        }
                    });

                }

            } else {
                $('#autocomplete').css({'display' : 'none'}).html('');
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

        $('#autocomplete').on('click', 'ul li', function(e) {

            var link   = $('#autocomplete ul li.aktiv').attr('data-href'),
                recid  = $('#autocomplete ul li.aktiv').attr('data-recid'),
                cikkid = $('#autocomplete ul li.aktiv').attr('data-cikkid');

            if( baseName(location.href) == 'login' && recid == 12 ) {
                $('#szallitoid_autocomp').attr('disabled', false);
            } else {
                $('#szallitoid_autocomp').attr('disabled', true).val('');
                $('#szallitoid').val('');
            }

            var mee = $('#autocomplete ul li.aktiv').attr('data-mee');
            if( mee !== undefined ) {
                $('.insertMee').text(mee);
            }
            if( cikkid !== undefined ) {
                $('#cikkid_origi').val(cikkid);
            }

            if(link){
                location.href = link;
            }else{
                var li_val = $('#autocomplete ul li.aktiv').html();

                if( li_val.indexOf('&') !== -1 ) {
                    li_val = li_val.replace('&amp;', '&');
                }

                mezo.val( li_val ).change();
                van_uj_erteke = true;

                mezo.attr('data-origin-val', mezo.val());

                if( mezo.attr('name').indexOf('_autocomp') !== -1 ) {
                    mezo_nev = mezo.attr('name').replace('_autocomp', '');
                    $('input[name="' + mezo_nev + '"]').val(recid);

                    var form = $('input[name="' + mezo_nev + '"]').parents('#szerk_form');
                    /* Lehet nem egy űrlapon vagyunk, hanem egy alert2-ben, ebben keressünk inputot */
                    if($('#alert2').length){
                        form = $('#alert2');
                    }

                    /* Autcomplete után a következő inputra fókusz */
                    stop = false;
                    form.find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');

                        if( stop ) {
                            if($('#'+name).length){
                                document.getElementById(name).select();
                            }
                            return false;
                        }

                        if( name == mezo_nev ) {
                            stop = true;
                        }
                    });
                }

                /* Árazás popuphoz */
                if(options.arazas_beallit){
                    rend_ossz_beallit();
                }

                /* KP-s anyagbeszerzéshez popuphoz */
                if($('#pop_afa').length && $('#pop_afa').val()){
                    osszegek_kiszamol($('#pop_afa'));
                }

                if( setting.tabla == 'obj' ) {
                    gepVane();
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

        /* Bárhova kattintva az oldalon, vagy Entert nomva, az autocomplete záródjon be  */
        that = $(this);
        $(document).click(function() {
            $('#autocomplete').css({'display':'none'}).html('');
        });

        $(document).keyup(function(e) {
            if(e.keyCode == 27){
                $("#autocomplete").css({'display':'none'}).html('');
            }
        });


        /* Tab esetén záródjon be az autocomplete, és ha nem változott az értéke, írjuk vissza az eredetit */
        $(this).keydown(function(e) {
            if (e.keyCode == 9) {
                $('#autocomplete').css({'display':'none'}).html('');
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

                    if( baseName(location.href) == 'login' && recid == 12 ) {
                        $('#szallitoid_autocomp').attr('disabled', false);
                    } else {
                        $('#szallitoid_autocomp').attr('disabled', true).val('');
                        $('#szallitoid').val('');
                    }

                    var mee = $('#autocomplete ul li.aktiv').attr('data-mee');
                    if( mee !== undefined ) {
                        $('.insertMee').text(mee);
                    }

                    if(link){
                        location.href = link;
                    }else{
                        var li_val = $('#autocomplete ul li.aktiv').html();

                        if( li_val.indexOf('&') !== -1 ) {
                            li_val = li_val.replace('&amp;', '&');
                        }

                        mezo.val( li_val ).change();
                        van_uj_erteke = true;

                        mezo.attr('data-origin-val', mezo.val());

                        if( mezo.attr('name').indexOf('_autocomp') !== -1 ) {
                            mezo_nev = mezo.attr('name').replace('_autocomp', '');
                            $('input[name="' + mezo_nev + '"]').val(recid);

                            var form = $('input[name="' + mezo_nev + '"]').parents('#szerk_form');

                            stop = false;
                            form.find('input, select, textarea').each(function() {
                                var name = $(this).attr('name');

                                if( stop ) {
                                    if($('#'+name).length){
                                        document.getElementById(name).select();
                                        return false;
                                    }
                                }

                                if( name == mezo_nev ) {
                                    stop = true;
                                }
                            });
                        }

                        /* Árazás popuphoz */
                        if(options.arazas_beallit){
                            rend_ossz_beallit();
                        }

                        /* KP-s anyagbeszerzéshez popuphoz */
                        if($('#pop_afa').length && $('#pop_afa').val()){
                            osszegek_kiszamol();
                        }

                        e.stopPropagation();
                    }

                    if( options.tabla == 'obj' ) {
                        gepVane();
                    }

                    $('#autocomplete').css({'display':'none'}).html('');
                } else {
                    $('.kereso_gomb').click();
                }
            }
        });
    }
})(jQuery);
