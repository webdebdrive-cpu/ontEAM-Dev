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

        var readonly_e = $(this).attr('readonly');
        if(typeof readonly_e === typeof undefined || readonly_e === false){

            var defaults = {
                arazas_beallit  : '',
                csoport         : 0,
                felt            : '',
                id_kiir         : '',
                karaktertol     : 0,                    /* Hány karaktertől induljon a keresés                              */
                limit           : 10,                   /* Hány tételt listázzon ki                                         */
                mezo            : '',                   /* Szűréssel kapcsolatos GET-ek                                     */
                op              : '',
                oszlop_keres    : '',                   /* Adatbázis tábla oszlopa, amiben keresünk                         */
                oszlop_listaz   : '',                   /* Adatbázis tábla oszlopa, amiket listázunk                        */
                php_fajl        : 'autocomplete.php',   /* PHP fájl, amit meghívunk                                         */
                popupba         : '',
                props           : '',                   /* Pl. ktgnem keresésnél a feltétel kiegészül még egy props oszlopban való kereséssel */
                recid           : '',                   /* Az aktuális űrlap reid-ja. Néha szükség van rá                   */
                rkat            : 0,                    /* Rendszer kategóriára szűrünk                                     */
                szel            : 'auto',               /* Lista szélessége                                                 */
                szemelyzet_id   : '',                   /* Munkaidő ráfodítás név hozzáadásánál kell                        */
                szuros          : '',                   /* Ha a szűrőben hívtuk meg, akkor azt ezen keresztül tudjuk jelezni*/
                tabla           : '',                   /* Adatbázis tábla, amiben keresünk                                 */
            };

            var van_uj_erteke = false;
            var options = $.extend(defaults, options);

            $(this).attr('data-origin-val', $(this).val());
            $(this).addClass('autocomp'); /* Ettől lesz nyíl ikon az input végén, látszik, hogy egy autocompletes input. */

            /* Szállítóknál legyen placeholder, illetve itt 3 karakter kell a listázáshoz */
            if( defaults.tabla=='szallito' && defaults.oszlop_keres=='nev' ){
                $(this).attr('placeholder', '3 betűt üssön le listához');
                defaults.karaktertol = 3;
            }

            $(this).keyup(function(e) {
                that  = $(this);

                kesleltetes = 500;
                if( e.which == undefined ) {
                    kesleltetes = 0;
                }

                delay(function() {
                    /* Tabbal érkezve egy autocomplete mezőbe ne jöjjön fel a lista */
                    if(e.which!=KEY_TAB){
                        var width   = $(that).outerWidth();
                        var height  = $(that).outerHeight();
                        var left    = $(that).offset().left;
                        var top     = $(that).offset().top;

                        var length  = $(that).val().length;
                        var val     = $(that).val();
                        mezo        = $(that);
                        setting     = options;

                        if(length >= options.karaktertol) {
                            var cont_id = $(that).attr('id') + '_cont',
                                current_cont_id = $('#autocomplete').attr('data-cont');

                            if( !$('#' + cont_id).length ) {
                                $(that).before('<div id="' + cont_id + '" class="autocomp_cont"></div>');
                            }

                            if( current_cont_id === undefined ) {
                                $('#' + cont_id).html('<div id="autocomplete" data-cont="' + cont_id + '"></div>');
                            } else if( cont_id != current_cont_id ) {
                                $('#autocomplete').remove();
                                $('#' + cont_id).html('<div id="autocomplete" data-cont="' + cont_id + '"></div>');
                            }

                            if($('#popup').css('display') == 'block'){
                                $('#autocomplete').addClass('popupba');
                            } else {
                                $('#autocomplete').removeClass('popupba');
                            }

                            if( $('#autocomplete').css('display') != 'block' ) {
                                if(options.szel == 'auto') {
                                    $('#' + cont_id).css({ 'width': width });
                                    $('#autocomplete').css({
                                        'display'   : 'block',
                                        'max-height': '30rem',
                                        'width'     : width
                                    });
                                } else {
                                    $('#' + cont_id).css({ 'width': options.szel });
                                    $('#autocomplete').css({
                                        'display'   : 'block',
                                        'max-height': '30rem',
                                        'width'     : options.szel
                                    });
                                }
                            }

                            /* Ha nem a szűrőben van az autocomplete, akkor csak azokat a helyiségeket listázza ki,
                             *  ami adott szállodához tartozik, ezért átküldünk egy telephely_id értékete is */
                            telephely_id = '';
                            if( !options.szuros && (
                                baseName(location.href) == 'bejelentes'                 ||
                                baseName(location.href) == 'utemezett_feladatok'        ||
                                baseName(location.href) == 'rendszeres_karbantartas'    ||
                                baseName(location.href) == 'munkalap'                   ||
                                baseName(location.href) == 'eszkozkezeles'              ||
                                baseName(location.href) == 'felmeresi_terv'
                                )
                            ) {
                                telephely_id = $('#telephely_id').val();
                            }

                            obj_id = '';
                            if( !options.szuros && (
                                                    baseName(location.href) == 'bejelentes'             ||
                                                    baseName(location.href) == 'eszkozkezeles'          ||
                                                    baseName(location.href) == 'eseti_fenntartas'       ||
                                                    baseName(location.href) == 'felmeresi_terv'         ||
                                                    baseName(location.href) == 'munkalap'               ||
                                                    baseName(location.href) == 'utemezett_feladatok'    ||
                                                    baseName(location.href) == 'rendszeres_karbantartas'
                                                    )
                            ) {
                                obj_id = $('#obj_id').val();
                            }

                            if(e.keyCode!=KEY_DOWN && e.keyCode!=KEY_UP && e.keyCode!=KEY_ENTER) {

                                $.ajax({
                                    url     : options.php_fajl,
                                    type    : 'GET',
                                    data: {
                                        csoport         : options.csoport,
                                        felt            : options.felt,
                                        id_kiir         : options.id_kiir,
                                        limit           : options.limit,
                                        mezo            : options.mezo,
                                        obj_id          : obj_id,
                                        op              : options.op,
                                        oszlop_keres    : options.oszlop_keres,
                                        oszlop_listaz   : options.oszlop_listaz,
                                        props           : options.props,
                                        recid           : options.recid,
                                        rkat            : options.rkat,
                                        szemelyzet_id   : options.szemelyzet_id,
                                        tabla           : options.tabla,
                                        telephely_id    : telephely_id,
                                        value           : val
                                    },
                                    success: function(msg) {
                                        if(msg.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                                        $('div#autocomplete').html(msg);

                                        /* Ha kilógna a képernyőről */
                                        var offsetTop       = $(that).offset().top,
                                            documentHeight  = $(document).outerHeight(),
                                            documentHalf    = documentHeight / 2;

                                        var autocompHeight  = $('#autocomplete ul').outerHeight(),
                                            autocompleteTop = height;

                                        if( autocompHeight > 300 ) {
                                            autocompHeight = 300;
                                        }

                                        /* Ha az input elhagyta a documentum felét */
                                        if( offsetTop > documentHalf ) {
                                            var maradekHely = documentHeight - (offsetTop + height);

                                            autocompleteTop = -Math.abs(autocompHeight);
                                            var posTop = $(that).position().top;

                                            if( autocompHeight > posTop ) {
                                                autocompleteTop = -Math.abs(posTop);
                                                autocompHeight  = posTop;
                                            }

                                            if($('#popup').css('display') == 'block') {
                                                var popupHeight = $('div.popup_kont').outerHeight();

                                                autocompHeight = popupHeight - posTop - 70;
                                            }

                                            $('#autocomplete').css({
                                                'top': autocompleteTop,
                                                'max-height': autocompHeight
                                            });
                                        } else {
                                            var posTop = $(that).position().top;

                                            if($('#popup').css('display') == 'block') {
                                                var popupHeight = $('div.popup_kont').outerHeight();
                                                autocompHeight = popupHeight - height - posTop - parseInt($('#popup div.popup_tart div.kuldes').outerHeight());
                                            }

                                            $('#autocomplete').css({
                                                'top'       : height,
                                                'max-height': autocompHeight
                                            });
                                        }
                                    }
                                });

                            }

                        } else {
                            $('#autocomplete').css({'display' : 'none'}).html('');
                        }
                    }
                }, kesleltetes);
            });

            $(document).on('mouseover', '#autocomplete ul li', function() {
                $('#autocomplete ul li').removeClass('aktiv');
                $(this).addClass('aktiv');
            });

            $(document).on('mouseleave', '#autocomplete ul li', function() {
                $(this).removeClass('aktiv');
            });

            $(document).on('mouseleave', '#autocomplete', function() {
                $(this).removeClass('aktiv');
            });

            /* Klikkelve kiválasztva egy sor */
            $(document).on('click', '#autocomplete ul li', function(e) {
                var link   = $('#autocomplete ul li.aktiv').attr('data-href'),
                    recid  = $('#autocomplete ul li.aktiv').attr('data-recid'),
                    cikkid = $('#autocomplete ul li.aktiv').attr('data-cikkid');

                if( baseName(location.href) == 'login' && recid == 12 ) {
                    $('#szallitoid_autocomp').attr('readonly', false);
                } else {
                    $('#szallitoid_autocomp').attr('readonly', true).val('');
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
                    szurkit();
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

                        var form = $('input[name="' + mezo_nev + '"]').parents('form');
                        /* Lehet nem egy űrlapon vagyunk, hanem egy alert2-ben, ebben keressünk inputot */
                        if($('#alert2').length){
                            form = $('#alert2');
                        }

                        setTimeout(function() {
                            /* Autcomplete után a következő inputra fókusz */
                            stop = false;
                            form.find('input:not([readonly]), select:not([readonly]), textarea:not([readonly])').filter(':not(:disabled)').filter(":not([type='file'])").each(function() {
                                var name = $(this).attr('name'),
                                    type = $(this).attr('type'),
                                    tag  = $(this).prop('tagName');

                                /* Autocomplete hidden inputjai miatt csak arra fókuszálunk, ami látszik is */
                                if( name !== undefined && (stop && type == 'checkbox') || (stop && $(this).is(':visible')) ) {

                                    if( type == 'checkbox' || tag == 'SELECT' ) {
                                        document.getElementById(name).focus();
                                    } else {
                                        if($('#' + name).length){
                                            document.getElementById(name).select();
                                        }
                                    }

                                    return false;
                                }

                                if( name == mezo_nev ) {
                                    stop = true;
                                }
                            });
                        }, 50);
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
                    if(e.keyCode==KEY_ENTER) {
                        /* $("#autocomplete").css({'display':'none'}).html(''); */
                        return false;
                    }
                } else if($('#autocomplete').css('display') == 'block') {
                    if(e.keyCode==KEY_ENTER) {
                        /* $("#autocomplete").css({'display':'none'}).html(''); */
                        return false;
                    }
                }
            });

            /* Bárhova kattintva az oldalon, vagy Entert nyomva, az autocomplete záródjon be */
            that = $(this);
            $(document).click(function() {
                $('#autocomplete').css({'display':'none'}).html('');
            });

            $(document).keyup(function(e) {
                if(e.keyCode == KEY_ESCAPE){
                    $("#autocomplete").css({'display':'none'}).html('');
                }
            });


            /* Tab esetén záródjon be az autocomplete, és ha nem változott az értéke, írjuk vissza az eredetit */
            $(this).keydown(function(e) {
                if (e.keyCode == KEY_TAB) {
                    $('#autocomplete').css({'display':'none'}).html('');
                }
            });

            $(this).keydown(function(e) {
                var aktiv   = $('#autocomplete ul li.aktiv');
                var first   = $('#autocomplete ul li:first-child');
                if( e.keyCode==KEY_DOWN ) {
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

                    var scrollHeight = $('#autocomplete').prop('scrollHeight');
                    $('#autocomplete').scrollTop($('#autocomplete').scrollTop() + $('#autocomplete ul li.aktiv').position().top - 20);
                }
            });
            $(this).keyup(function(e) {
                var aktiv   = $('#autocomplete ul li.aktiv');
                var first   = $('#autocomplete ul li:first-child');
                var last    = $('#autocomplete ul li:last-child');

                if(e.keyCode==KEY_UP) {
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

                    var scrollHeight = $('#autocomplete').prop('scrollHeight');
                    $('#autocomplete').scrollTop($('#autocomplete').scrollTop() + $('#autocomplete ul li.aktiv').position().top - 20);
                }

                /* Enterrel kiválasztva egy sort */
                if(e.keyCode==KEY_ENTER) {
                    if( $('#autocomplete ul li').hasClass('aktiv') ) {

                        var link   = $('#autocomplete ul li.aktiv').attr('data-href'),
                            recid  = $('#autocomplete ul li.aktiv').attr('data-recid'),
                            cikkid = $('#autocomplete ul li.aktiv').attr('data-cikkid');

                        if( baseName(location.href) == 'login' && recid == 12 ) {
                            $('#szallitoid_autocomp').attr('readonly', false);
                        } else {
                            $('#szallitoid_autocomp').attr('readonly', true).val('');
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
                            szurkit();
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

                                var form = $('input[name="' + mezo_nev + '"]').parents('form');

                                setTimeout(function() {
                                    stop = false;
                                    form.find('input, select, textarea').filter(':not(:disabled)').filter(":not([readonly='readonly'])").filter(":not([type='file'])").each(function() {
                                        var name = $(this).attr('name'),
                                            type = $(this).attr('type'),
                                            tag  = $(this).prop('tagName');

                                        /* Autocomplete hidden inputjai miatt csak arra fókuszálunk, ami látszik is */
                                        if( name !== undefined && (stop && type == 'checkbox') || (stop && $(this).is(':visible')) ) {

                                            if( type == 'checkbox' || tag == 'SELECT' ) {
                                                document.getElementById(name).focus();
                                            } else {
                                                if($('#' + name).length){
                                                    document.getElementById(name).select();
                                                }
                                            }

                                            return false;
                                        }

                                        if( name == mezo_nev ) {
                                            stop = true;
                                        }
                                    });
                                }, 50);
                            }

                            /* Árazás popuphoz */
                            if(options.arazas_beallit){
                                rend_ossz_beallit();
                            }

                            /* KP-s anyagbeszerzéshez popuphoz */
                            if($('#pop_afa').length && $('#pop_afa').val()){
                                osszegek_kiszamol($('#pop_afa'));
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
    }
})(jQuery);
