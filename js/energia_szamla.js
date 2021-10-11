// Energia számla feltöltése köztes táblából
function energia_szamla_feltolt() {
    csoportMuvelet('energia_szamla_feltolt');
}

// Számla igazolása
function szamla_igazol(mehet) {
    
    if (!mehet) {
        alert2('Biztosan igazolja a számlát?', '<input type="button" class="gomb zold" value="Igen" onclick="szamla_igazol(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'energia_szamla.php',
        type: 'POST',
        data: {
            id           : _get('id'),
            szamla_igazol: Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    szamlaBetolt(_get('id'), 'energia_szamla.php');
                    setTimeout(function () {
                        alert2(response.hiba, response.gomb, response.title);
                    }, 500);
                    
                    return false;
                }
                szamlaBetolt(_get('id'), 'energia_szamla.php');
                
                setTimeout(function () {
                    alert2('Számla sikeresen igazolva!', '', 'Üzenet');
                }, 200);
            }
        }
    });
}

// FONTOS: NEM TÉVEDÉS, tényleg a fenntartási szerződésekkel kell itt dogozni
// Fenntartási szerződés hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény.
function fennt_hozzaad_gomb_hozzaad_vagy_torol() {
    
    $.ajax({
        url : 'fenntartasi_szamla.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            uj                      : _get('uj'),
            van_kapcsolt_szerzodes  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                } else {
    
                    if ($('#sz_szam').val()) {

                        // Törléskor csak akkor adunk hozzá 'mod' classt, ha amúgy volt eredetileg értéke
                        $('#sz_szam').val('');
                        if( $('#sz_szam').attr('value') ){
                            $('#sz_szam').addClass('mod');
                        } else {
                            $('#sz_szam').removeClass('mod');
                        }
                        
                        $('#kolt_vis_autocomp').val('');
                        $('#kolt_vis').val('');
                        if( $('#kolt_vis_autocomp').attr('value') ){
                            $('#kolt_vis_autocomp').addClass('mod');
                        } else {
                            $('#kolt_vis_autocomp').removeClass('mod');
                        }

                        $('#szerzodes_neve').html('');

                        formModositva = true;
                        $('#sz_szam_gomb').val('Szerződés hozzáadása').removeClass('piros');
                        $('#mehet_gomb').addClass('gombb_alert');
                        szurkit_zar('mehet_megsem_mutat');
                        popupZar();
                        popupRejt();

                    } else {

                        csoportMuvelet('fenntartasi_szamla_szerzodes_valaszt');

                    }
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

// Számla küldés igazolásra
function kuldes_igazolasra(mehet) {
    
    if( !mehet ) {
        alert2('Biztosan igazolásra küldi a számlát?', '<input type="button" class="gomb zold" value="Igen" onclick="kuldes_igazolasra(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'energia_szamla.php',
        type: 'POST',
        data: {
            id               : _get('id'),
            kuldes_igazolasra: Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                
                szamlaBetolt(_get('id'), 'energia_szamla.php');
                alert2();
            }
        }
    });
}

// Adatlapon található deviza árfolyam és deviza nettó mezőből számol ki nettó értéket
$(document).on('blur', '#dev_net', function(e) {
    $('#netto').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*($('#arfolyam').val().replace(/ /g, '').replace(',', '.')), 0, ',', ' ') );
});