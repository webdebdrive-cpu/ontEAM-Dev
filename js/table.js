// Ez a képernyő bal szélétől való távolság. anyagigenyles.css -ben a --kepernyo_left-fele értékével legyen összhangban.
var kepernyo_left  = 15;
var minimum_o_szel = 40;

$(function() {
    $('#dragTable').dragtable({
        maxMovingRows   : 1,
        dragHandle      : '.some-handle',
        excludeFooter   : true,
        beforeStart     : function(table, sort) {
            $('#tartalomJobb').addClass('scrollFix');

            sorrend = {};
            $(table.el).find('thead').not('.sticky').find('th').each(function(i) {
                if(this.id != '') { sorrend[this.id] = $(this).outerWidth(); }
            });
        },
        beforeStop      : function(table, sort) {
            $('#tartalomJobb').removeClass('scrollFix');
        },
        persistState: function(table) {
            $(table.el).find('thead').not('.sticky').find('th').not('.dn').each(function(i) {
                var width  = col_width = sorrend[$(this).attr('id')],
                    index  = $(this).index(),
                    parent = $(this).parents('table');

                if( i == 0 ) {
                    width = width - 1;
                }

                $(this).css({
                    'min-width': width,
                    'max-width': width,
                    'width'    : width
                });

                parent.find('col').eq(index).css({
                    'min-width': col_width,
                    'max-width': col_width,
                    'width'    : col_width
                });
            });
            
            // Eszközigényléseknél az oszlopok mozgatása után az aktív cella oszlopindexét frissíteni kell a cookie-ban
            if( baseName(location.href) == 'eszkozigenyles' ) {
                console.log($('td.aktiv').index());
                createCookie('eszkozigeny_aktiv_oszl', $('td.aktiv').index());
            }

            tablaSorrend( table.el );
            fejlec_igazit($('.sar-table'));

            /*$(table.el).each(function() {
                var that = $(this);
                var colgroup = that.find('colgroup');

                colgroup.find('col').each(function(index, el) {
                    var width = $(this).outerWidth(),
                        index = $(this).index();

                    if( index == 0 ) {
                        width = width - 1;
                    }

                    $(that).find('th').eq(index).css({
                        'min-width': width,
                        'max-width': width,
                        'width': width
                    });
                });
            });*/
        }
    });

    $(document).on('dblclick', 'table.sar-table th', function(e) {
        var th      = $(this),
            table   = th.parents('table.sar-table'),
            order   = th.find('div.order'), irany,
            inverse = false;

        if( order.hasClass('asc') ) {
            irany = 'desc';

            inverse = true;
        } else if( order.hasClass('desc') ) {
            irany = 'asc';

            inverse = false;
        }

        if( irany ) {
            order.removeClass('asc desc').addClass(irany);
        } else {
            table.find('div.order').removeClass('asc desc');
            order.addClass('asc');

            irany = 'asc';
        }

        var index = th.index();

        table.find('tbody tr')/*.not('.fake') törölhető, már nincs fake sor */.find('td').filter(function() {
            return $(this).index() === index;
        }).sortElements(function(a, b) {

            var at = $.text([a]),
                bt = $.text([b]);
        
            if( $(a).attr('sorttable_customkey') ) {
                at = $(a).attr('sorttable_customkey');
            }
            if( $(b).attr('sorttable_customkey') ) {
                bt = $(b).attr('sorttable_customkey');
            }

            if( $(a).attr('data-sort') !== undefined ) {
                var at = $(a).attr('data-sort'),
                    bt = $(b).attr('data-sort');
            }

            if( isNumeric(at) && isNumeric(bt) ) {
                at = parseInt(at);
                bt = parseInt(bt);

                return at > bt ? inverse ? -1 : 1 : inverse ? 1 : -1;
            } else {
                return inverse ? bt.localeCompare(at) : at.localeCompare(bt);
            }

        }, function(){
            return this.parentNode;
        });

        ujra_sorszamoz_uj();
        // Eszközigénylés alatt a táblázat kicsit másképp működés (le-fel nyilak léptetése), ezért ezt itt újra kell kalibrálni
        // az initNavigacio() függvényyel, ami az eszkozigenyles_tablazat.js-ben van megírva
        if( baseName(location.href) == 'eszkozigenyles' ) {
            initNavigacio();
        }

        var url_kieg = '';
        if( _get('anyagrend') ){
            url_kieg += 'anyagrend=1&';
        }
        if( _get('mezo') ) {
            url_kieg += 'mezo='+_get('mezo')+'&';
        }
        if( _get('op') ) {
            url_kieg += 'op='+_get('op')+'&';
        }
        if( _get('felt') ) {
            url_kieg += 'felt='+_get('felt')+'&';
        }
        if( url_kieg ) {
            url_kieg = '?'+url_kieg.slice(0, -1);
        }
        $.ajax({
            url : baseName(location.href)+'.php'+url_kieg,
            type: 'POST',
            data: {
                oszlop          : $(this).attr('id'),
                irany           : irany,
                tablazat_rendez : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            },
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    });

    $(document).on('keypress keyup', 'input[name="tableSearch"]', function() {
        var value   = $(this).val(),
            th      = $(this).parents('th'),
            table   = $(this).parents('table.sar-table'),
            index   = th.index();

        table.find('tr').removeClass('dn');

        if( value ) {
            table.find('td').filter(function() {
                if( $(this).index() === index ) {
                    var text    = $(this).text(),
                        tr      = $(this).parents('tr');

                    if( $(this).attr('data-sort') !== undefined ) {
                        var text = $(this).attr('data-sort');
                    }

                    if( text.toLowerCase().indexOf( value.toLowerCase() ) === -1 ) {
                        tr.addClass('dn');
                    } else {
                        tr.removeClass('dn');
                    }
                }
            });
        }
    });

    $(document).on('click', 'input[name="nowrap"]', function() {
        var value = $(this).prop('checked'),
            th    = $(this).parents('th'),
            table = $(this).parents('table.sar-table'),
            index = th.index();

        table.find('td').filter(function() {
            if( $(this).index() === index ) {
                if( value ) {
                    $(this).addClass('nowrap');
                } else {
                    $(this).removeClass('nowrap');
                }
            }
        });
    });
        
    $(document).ready(function () {
        $('.sar-table').each(function() {
            var colgroup = $('<colgroup>');

                var height = $(this).find('thead').find('th:not(.dn)').first().outerHeight();
                // Törölhető, már nincs fake sor $(this).find('tr.fake td').css('height', height);
                $(this).css('margin-top', height);

            $(this).find('thead').find('th').each(function(i) {
                var width = $(this).outerWidth(),
                    index = $(this).index();

                class_nev = '';
                if( $(this).attr('class') ){
                    class_nev = ' class="' + $(this).attr('class') + '"';
                }

                colgroup.append('<col' + class_nev + '>');
                colgroup.find('col').eq(index).css({
                    'min-width': width,
                    'max-width': width,
                    'width'    : width
                });

            });

            $(this).prepend(colgroup);

        });
    });
    $('#tartalomJobb').scroll(function() {
        var scrollTop  = $(this).scrollTop(),
            scrollLeft = $(this).scrollLeft();

        $('.sar-table').each(function() {
            var tableParent  = $(this).parents('#tartalomJobb'),
                parentOffset = tableParent.offset();

            /* Vendégéjszakáknál a tálbázat felett van egy szűrés, ezért ezt kicsit lentebbre igazítjuk */
            var top = parentOffset.top;
            if( baseName(location.href) == 'vendegejszakak' ) {
                top += 50;
            }

            if( scrollTop > 0 ) {
                $(this).find('thead').css({
                    'position'  : 'fixed',
                    'top'       : top,
                    'left'      : -Math.abs(scrollLeft) + kepernyo_left,
                    'z-index'   : '10'
                });
            } else {
                $(this).find('thead').css({
                    'left'      : -Math.abs(scrollLeft) + kepernyo_left
                });
            }
        });
    });

    // Resize
    $('.sar-table').each(function() {
        /*var thead = $(this).find('thead').not('.sticky');*/
        var thead = $(this).find('thead');

        thead.find('th').each(function(index, el) {
            $(this).find('.thKeret').append('<div class="grip"></div>');
        });
    });

    var _click = false;
    $(document).on('mousedown', '.grip', function(event) {
        _click = true;
        _resizeElement = {};

        var th = $(this).parents('th');

        _resizeElement.th       = th;
        _resizeElement.that     = $(this).parents('thead');
        _resizeElement.tbody    = $(this).parents('table').find('tbody');
        _resizeElement.width    = th.outerWidth();
        _resizeElement.index    = th.index();
        _resizeElement.colGroup = $(this).parents('table').find('colgroup');

        var gripPos = $(this).offset();
        startCursor = {};

        startCursor.x = (event.pageX - gripPos.left) + gripPos.left;
        startCursor.y = (event.pageY - gripPos.top) + gripPos.top;

        $(document).disableSelection();
    });

    $(document).on('mousemove', function(event) {
        if( _click ) {
            var offsetCursor = {};

            offsetCursor.x = event.pageX - startCursor.x;
            offsetCursor.y = event.pageY - startCursor.y;

            _rew = _resizeElement.width + offsetCursor.x;

            if( _rew > minimum_o_szel ) {
                _resizeElement.th.css({
                    'min-width': _rew,
                    'max-width': _rew,
                    'width'    : _rew
                });

                var maxHeight = Math.max.apply(null, $(_resizeElement.th).map(function () {
                    return $(this).height();
                }).get());

                // Törölhető, már nincs fake sor
                // _resizeElement.tbody.find('tr.fake td').css('height', maxHeight);
                _resizeElement.th.closest('table').css('margin-top', maxHeight);

                _resizeElement.tbody.find('tr:first-child td').each(function() {
                    var index = $(this).index(),
                        width = $(this).outerWidth();

                    _resizeElement.that.find('th').eq(index).css({
                        'min-width': width,
                        'max-width': width,
                        'width'    : width
                    });
                });

                _col = _resizeElement.colGroup.find('col').eq(_resizeElement.index);
                _col.css({
                    'min-width': _rew,
                    'max-width': _rew,
                    'width'    : _rew
                });
            }
        }
    });

    $(document).on('mouseup', function() {
        if( _click ) {
            _click = false;

            _resizeElement.tbody.find('tr:first-child td').each(function(i) {
                var index = $(this).index(),
                    width = $(this).outerWidth();

                if( i == 0 ) {
                    width = width - 1;
                }

                _resizeElement.that.find('th').eq(index).css({
                    'min-width': width,
                    'max-width': width,
                    'width'    : width
                });
            });

            tablaSorrend($(_resizeElement.that).parents('table'));
            _resizeElement = null;
            $(document).enableSelection();

        }
    });


    fejlec_igazit($('.sar-table'));
    $(window).resize(function() {
        fejlec_igazit($('.sar-table'));
        tablaSorrend ($('.sar-table'));
        fejlecPozicional();
    });
});

function tablaSorrend( table ) {
    var tabla       = $(table).attr('data-tabla'),
        sorrend     = {};
        beallitasok = {};

    if (tabla) {
        jovahagyasra_varo_igazit();

        if( table.attr('data-beall_cellak') ) {
            beallitasok['cellak'] = table.attr('data-beall_cellak');
        }

        table.find('thead').not('.sticky').find('th').each(function(i) {
            if( this.id != '') {
                var width       = '';
                var megjelenik  = '';
                var egysoros    = '';
                
                // Rejtett oszlop esetén nem a látható szélessége, hanem a default width értékét adjuk át
                // igen ám, de az meg üres.
                if( $(this).hasClass('dn') ){
                    // width = $(this).data('def_width');
                    width = $(this)[0].style.width;
                } else {
                    width = $(this).outerWidth();
                    megjelenik = 1;
                }
                if( $(this).hasClass('egysoros') ){
                    egysoros = 1;
                }

                if( width<minimum_o_szel ) {
                    width = minimum_o_szel;
                }
                sorrend[i] = {id: this.id, width: width, megjelenik: megjelenik, egysoros: egysoros };
            }
        });

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                tabla        : tabla,
                sorrend      : JSON.stringify(sorrend),
                beallitasok  : JSON.stringify(beallitasok),
                tablaSorrend : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);
                    if( response.hiba ) {
                        alert2(response.hiba);
                        return false;
                    }
                }
            },
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    }
}

function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function ujra_sorszamoz_uj(){

    /* Hagyjuk időt az átrendezésnek */
    setTimeout(function(){
        var i = 1;
        $('.szamla_cont_tabla tbody tr').each(function () {
            $(this).attr('data-i', i);
            i++;
        });
    }, 1);

}

/* Anyagigénylés táblázat alján található Jóváhagyásra váró érték pozícionálásához */
function jovahagyasra_varo_igazit(){
    /* Kiszámoljuk, hogy a anyag_tabla_jovah_ertek ID-jú oszlop hol van, és annak az Y koordinátáját és szélességét kivonjuk a táblázat szélességéből */
    if( $('#anyag_tabla_jovah_ertek').length ){
        jovah_ertek_poz = $('#anyag_tabla_jovah_ertek').position();
        padding = $('#rend_ertek_td').outerWidth() - jovah_ertek_poz.left - $('#anyag_tabla_jovah_ertek').outerWidth();
        $('#rend_ertek_td').css('padding-right',  padding);
    }
}

function fejlec_igazit( table ) {
    setTimeout(function () {
        table.find('tbody').find('tr:first-child td:not(.dn)').each(function(i) {
            
            var index = $(this).index(),
                width = $(this).outerWidth();

            if( i == 0 ) {
                width = width - 1;
            }

            $(this).parents('.sar-table').find('th').eq(index).css({
                'min-width': width,
                'max-width': width,
                'width'    : width
            });

        });
    }, 500);
}

function fejlecPozicional() {
    $('.sar-table').each(function() {
        var scrollTop  = $('#tartalomJobb').scrollTop(),
            scrollLeft = $('#tartalomJobb').scrollLeft();

        var tableParent  = $(this).parents('#tartalomJobb'),
            parentOffset = tableParent.offset();

        /* Vendégéjszakáknál a tálbázat felett van egy szűrés, ezért ezt kicsit lentebbre igazítjuk */
        var top = parentOffset.top;
        if( baseName(location.href) == 'vendegejszakak' ) {
            top += 50;
        }

        $(this).find('thead').css({
            'position'  : 'fixed',
            'top'       : top,
            'left'      : -Math.abs(scrollLeft) + kepernyo_left,
            'z-index'   : '10',
            /* Ez a kettő azért kell, hogy a fejléc ne takarja ki a scrollt */
            'right'     : '1.6rem',
            'overflow-x': 'hidden'
        });
    });
}
