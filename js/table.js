$(function() {
    var saveSticky;
    $('#dragTable').dragtable({
        maxMovingRows   : 1,
        dragHandle      : '.some-handle',
        beforeStart     : function(table, sort) {
            saveSticky = $(table.el[0]).find('thead.sticky');
            $(table.el[0]).find('thead.sticky').remove();
            setTimeout(function() {
                $(table.el[0]).prepend(saveSticky);
            }, 50);
        },
        persistState: function(table) {
            table.el.find('th').each(function(i) {
                if(this.id != '') { table.sortOrder[i] = this.id; }
            });

            $.ajax({
             	url : 'esemenyek.php',
             	type: 'POST',
             	data: {
                    sorrend : JSON.stringify(table.sortOrder),
                    tablaSorrend: new Date().getTime()
                },
             	success: function(response) {
             		if(response) {
             			response = JSON.parse(response);

             			if( response.hiba ) {
             				alert2(response.hiba);
             				return false;
             			}
             		}
             	}
            });
        }
    });

    $(document).on('dblclick', 'table.sar-table th', function() {
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
        }

        var index = th.index();

        table.find('tbody td').filter(function() {
            return $(this).index() === index;
        }).sortElements(function(a, b) {
            var at = $.text([a]),
                bt = $.text([b]);

            if( $(a).attr('data-sort') !== undefined ) {
                var at = $(a).attr('data-sort'),
                    bt = $(b).attr('data-sort');
            }

            return at > bt ? inverse ? -1 : 1 : inverse ? 1 : -1;
        }, function(){
            return this.parentNode;
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

    $('.sar-table').each(function() {
        var sticky      = $(this).find('thead').clone().addClass('sticky dn'),
            stickyWidth = 0;

        $(this).find('thead').find('th').each(function() {
            var width = $(this).outerWidth(),
                index = $(this).index();

            sticky.find('th').eq(index).css({
                'min-width': width,
                'max-width': width,
                'width': width
            });

            var that = $(this);
            setTimeout(function() {
                that.css({
                    'min-width': width,
                    'max-width': width,
                    'width': width
                });
            }, 1000);

            stickyWidth += width;
        });

        sticky.find('th').each(function() {
            $(this).find('.some-handle').remove();
            $(this).find('span').css('padding-left', '0');
        });

        sticky.css({
            'position'  : 'absolute',
            'top'       : '0',
            'z-index'   : '10'
        });

        $(this).prepend(sticky);
    });

    $('#tartalomJobb').scroll(function() {
        var scrollTop = $(this).scrollTop();

        $('.sar-table').each(function() {
            var tableParent  = $(this).parents('#tartalomJobb'),
                parentOffset = tableParent.offset();

            if( scrollTop > parentOffset.top ) {
                $(this).find('thead.sticky').removeClass('dn').css({
                    'top': scrollTop
                });
            } else {
                $(this).find('thead.sticky').addClass('dn');
            }
        });
    });

    /* Resize */
    $('.sar-table').each(function() {
        var thead = $(this).find('thead').not('.sticky');

        thead.find('th').each(function(index, el) {
            $(this).find('.thKeret').append('<div class="grip"></div>');
        });
    });

    var _click = false;
    $(document).on('mousedown', '.grip', function(event) {
        _click = true;
        _resizeElement = {};

        var th = $(this).parents('th');
        _resizeElement.that  = th;
        _resizeElement.width = th.outerWidth();

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
                _resizeElement.that.css({
                    'min-width': _rew,
                    'max-width': _rew,
                    'width': _rew
                });
            }
        }
    });

    $(document).on('mouseup', function() {
        if( _click ) {
            _click = false;
            _resizeElement = null;
            $(document).enableSelection();

            $('.sar-table').each(function() {
                var thead = $(this).find('thead').not('.sticky'),
                    sticky = $(this).find('thead.sticky');

                thead.find('th').each(function(index, el) {
                    var th_width = $(this).outerWidth(),
                        th_index = $(this).index();

                    sticky.find('th').eq(th_index).css({
                        'min-width': th_width,
                        'max-width': th_width,
                        'width': th_width
                    });
                });
            });
        }
    });
});
