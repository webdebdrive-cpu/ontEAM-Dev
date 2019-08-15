$(function() {
    var saveSticky;
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
            $(table.el).find('thead').not('.sticky').find('th').each(function(i) {
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

            tablaSorrend( table.el );

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

        table.find('tbody tr').not('.fake').find('td').filter(function() {
            return $(this).index() === index;
        }).sortElements(function(a, b) {
            var at = $.text([a]),
                bt = $.text([b]);

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
        
        anyagrend = '';
        if( _get('anyagrend') ){
            anyagrend = '?anyagrend=1';
        }
        $.ajax({
            url : baseName(location.href)+'.php'+anyagrend,
            type: 'POST',
            data: {
                oszlop: $(this).attr('id'),
                irany : irany,
                anyagigenyles_tablazat_rendez: Date.now()
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

    /*parseInt(window.getComputedStyle(document.getElementById(this.id), null).getPropertyValue("width"))*/
    $('.sar-table').each(function() {
        var colgroup = $('<colgroup>');

        var height = $(this).find('thead').find('th:first-child').outerHeight();
        $(this).find('tr.fake td').css('height', height);

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

            /*if( i == 0 ) {
                width = width - 1;
            }

            var that = $(this);
            setTimeout(function() {
                that.css({
                    'min-width': width,
                    'max-width': width,
                    'width'    : width
                });
            }, 500);*/
        });

        $(this).prepend(colgroup);

        var scrollTop  = $('#tartalomJobb').scrollTop(),
            scrollLeft = $('#tartalomJobb').scrollLeft();

        var tableParent  = $(this).parents('#tartalomJobb'),
            parentOffset = tableParent.offset();

        $(this).find('thead').css({
            'position'  : 'fixed',
            'top'       : parentOffset.top,
            'left'      : -Math.abs(scrollLeft),
            'z-index'   : '10'
        });
    });

    $('#tartalomJobb').scroll(function() {
        var scrollTop  = $(this).scrollTop(),
            scrollLeft = $(this).scrollLeft();

        $('.sar-table').each(function() {
            var tableParent  = $(this).parents('#tartalomJobb'),
                parentOffset = tableParent.offset();

            if( scrollTop > 0 ) {
                $(this).find('thead').css({
                    'position'  : 'fixed',
                    'top'       : parentOffset.top,
                    'left'      : -Math.abs(scrollLeft),
                    'z-index'   : '10'
                });
            } else {
                $(this).find('thead').css({
                    'left'      : -Math.abs(scrollLeft)
                });
            }
        });
    });

    /* Resize */
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

            if( _rew > 40 ) {
                _resizeElement.th.css({
                    'min-width': _rew,
                    'max-width': _rew,
                    'width'    : _rew
                });

                var maxHeight = Math.max.apply(null, $(_resizeElement.th).map(function () {
                    return $(this).height();
                }).get());

                _resizeElement.tbody.find('tr.fake td').css('height', maxHeight);

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
        tablaSorrend($('.sar-table'));
        fejlecPozicional();
    });
});

function tablaSorrend( table ) {
    var tabla   = $(table).attr('data-tabla'),
        sorrend = {};

    if(tabla){
        jovahagyasra_varo_igazit();

        table.find('thead').not('.sticky').find('th').each(function(i) {
            if( this.id != '') {
                var width = '';
                /* Rejtett oszlop esetén nem a látható szélessége, hanem a style attribútumba írt szélességére van szükségünk */
                if( $(this).hasClass('dn') ){
                    width = $(this)[0].style.width;
                }else{
                    width = $(this).outerWidth();
                }
                if(width<40){
                    width = 40;
                }
                sorrend[i] = {id: this.id, width: width };
            }
        });

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                tabla        : tabla,
                sorrend      : JSON.stringify(sorrend),
                tablaSorrend : new Date().getTime()
            },
            success: function(response) {
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
    table.find('tbody').find('tr:first-child td').each(function(i) {
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
}

function fejlecPozicional() {
    $('.sar-table').each(function() {
        var scrollTop  = $('#tartalomJobb').scrollTop(),
            scrollLeft = $('#tartalomJobb').scrollLeft();

        var tableParent  = $(this).parents('#tartalomJobb'),
            parentOffset = tableParent.offset();

        $(this).find('thead').css({
            'position'  : 'fixed',
            'top'       : parentOffset.top,
            'left'      : -Math.abs(scrollLeft),
            'z-index'   : '10',
            /* Ez a kettó azért kell, hogy a fejléc ne takarja ki a scrollt */
            'right'     : '1.6rem',
            'overflow-x': 'hidden'
        });
    });
}
