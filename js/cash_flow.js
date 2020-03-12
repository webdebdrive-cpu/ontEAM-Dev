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
$(document).on('click', '.f_szalloda_kibont, .f_projekt_kibont', function(e) {
    alert2('Adatok betöltése folyamatban, kérem várjon...', '&nbsp;', 'Üzenet');
    that = $(this);
    setTimeout(function(){
        that.toggleClass('kibontva');
        var id = that.parent('tr').attr('id');
        // Ha van egyáltalán ilyen class-ú sor
        if( $('.'+id).length ) {
            $('.'+id).fadeToggle('dn', function(){ alert2(); });
        // Ha nincs, akkor is be kell zárni az alert-et
        } else {
            alert2();
        }
        fixTable();
    }, 20);
});

