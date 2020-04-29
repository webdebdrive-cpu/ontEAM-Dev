$(document).ready(function () {
    // Adatlap megjelenésekor a tól év mezőben villog kurzor.
    $('#ev_tol').focus();
    
});

// Chrome alatt a belső border másképp kellett megoldani. Jó nagy hokizás.
if (navigator.appVersion.indexOf("Chrome/") != -1) {
    setTimeout(function () {
        $('table.szamla_cont_tabla.kisbetu th.fix, table.szamla_cont_tabla.kisbetu td.fix').css('box-shadow', '0 0.1rem 0 0.1rem #ccc');
    }, 60);
}
// Ugyanez Edge-el
if (/Edge/.test(navigator.userAgent)) {
    setTimeout(function () {
        $('table.szamla_cont_tabla.kisbetu th.fix, table.szamla_cont_tabla.kisbetu td.fix').css('box-shadow', 'inset 0 0 0 0.1rem #ccc');
    }, 60);
}

// Mezőkbe kattintva az jelölődjön ki
$(document).on('click', '.kijelol', function(e) {
    $(this).select();
});

// Mezőkbe kattintva az jelölődjön ki
$(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
    $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
    $(this).addClass('kijelolve');
});

// Mezőbeírt adatok ellenőzése, mentése SESSION-be
function cash_flow_elkeszitese() {
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            ev_tol                  : $('#ev_tol').val(),
            ho_tol                  : $('#ho_tol').val(),
            ev_ig                   : $('#ev_ig').val(),
            ho_ig                   : $('#ho_ig').val(),
            bontas                  : $('#bontas').val(),
            pri_egyutt              : $('#pri_egyutt').is(':checked'),
            szetosztas_egyutt       : $('#szetosztas_egyutt').is(':checked'),
            cash_flow_elkeszitese   : Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    if( response.focus ) {
                        $('#'+response.focus).addClass('hibas_ide_focus');
                    }
                    return false;
                }
                
                // Elkezdjük betölteni a cash-fow fület, de mivel ez időigényesebb lehet
                // 200 miliszekundum után (mert a szamlaBetolt() fgv 100 miliszekundum után bezár minden alertet)
                // kiírjuk, hogy betöltés... Amit a cash-flow fül alatt be is zárunk egy ready block-ban.
                szamlaBetolt('0', 'cash_flow.php', 'cash_flow');
                setTimeout(function(){
                    alert2('<div>Cash-flow táblázat kiszámítása folyamatban, kérem várjon...</div><div class="progress-bar loading"><span></span></div>', '&nbsp;', 'Üzenet');
                }, 200);
            }
        }
    });
}

// Cash-flow táblázatnál sorok mutatása, rejtése
$(document).on('click', '.f_szalloda_kibont, .f_projekt_kibont.kibont', function(e) {
    alert2('Adatok betöltése folyamatban, kérem várjon...', '&nbsp;', 'Üzenet');
    
    var kibontva_volt = $(this).hasClass('kibontva');
    that = $(this);
    setTimeout(function(){
        that.toggleClass('kibontva');
        var id = that.parent('tr').attr('id');
        // Ha van egyáltalán ilyen class-ú sor
        if( $('.'+id).length ) {
            
            // Ha volt kibontva class neve a tr-nek, akkor mindent bezárunk
            if( kibontva_volt ) {
                $('.'+id).fadeOut(function(){ alert2(); });
                $('.'+id+'_zar').fadeOut(function(){ alert2(); });
                // Szállodát bezárva, az alatta levő projekt is bezáródik, emiatt le kell venni a kibontva class-t
                $('.'+id+' td.fix').removeClass('kibontva');

            } else {
                $('.'+id).fadeIn(function(){ alert2(); });
            }
            
        // Ha nincs, akkor is be kell zárni az alert-et
        } else {
            alert2();
        }
        fixTable();
        $('#fixTable table').width('auto');
        $('#fixTable table th').attr('style', '');
    }, 20);
});

// Cash-flow táblázatnál sorok mutatása, rejtése
$(document).on('click', '.f_osszes_kibont', function(e) {
    
    var kibontva_volt = false;
    if( $(this).hasClass('kibontva') ) {
        kibontva_volt = true;
    }
    
    alert2('Adatok betöltése folyamatban, kérem várjon...', '&nbsp;', 'Üzenet');
    if( $(this).hasClass('kibontva') ) {
        $('tr.projektigeny_sor, tr.projekt_sor, tr.tervezett_sor, tr.szerzodott_sor, tr.szamlazott_sor').fadeOut( function(){ alert2(); } );
        $('td.kibont, th.kibont').removeClass('kibontva');
    } else {
        $('tr.projektigeny_sor, tr.projekt_sor, tr.tervezett_sor.kibont, tr.szerzodott_sor.kibont, tr.szamlazott_sor.kibont').fadeIn( function(){ alert2(); } );
        $('td.kibont, th.kibont').addClass('kibontva');
    }
    
    // Class saját class lekezelése
    if( kibontva_volt ) {
        $(this).removeClass('kibontva');
    } else {
        $(this).addClass('kibontva');
    }
    
});

