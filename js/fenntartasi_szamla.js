// Fenntartási számla feltöltése köztes táblából
function fenntartasi_szamla_feltolt() {
    csoportMuvelet('fenntartasi_szamla_feltolt');
}

// Számla igazolása
function szamla_igazol(mehet) {
    
    if (!mehet) {
        alert2('Biztosan igazolja a számlát?', '<input type="button" class="gomb zold" value="Igen" onclick="szamla_igazol(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'fenntartasi_szamla.php',
        type: 'POST',
        data: {
            id           : _get('id'),
            szamla_igazol: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    szamlaBetolt(_get('id'), 'fenntartasi_szamla.php');
                    setTimeout(function () {
                        alert2(response.hiba, response.gomb, response.title);
                    }, 500);
                    
                    return false;
                }
                szamlaBetolt(_get('id'), 'fenntartasi_szamla.php');
                
                setTimeout(function () {
                    alert2('Számla sikeresen igazolva!', '', 'Üzenet');
                }, 200);
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

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

                        // Törléskor csak akkor adunk hozzá 'mod' class-t, ha amúgy volt eredetileg értéke
                        $('#sz_szam').val('');
                        if( $('#sz_szam').attr('value') ){
                            $('#sz_szam').addClass('mod');
                        } else {
                            $('#sz_szam').removeClass('mod');
                        }
                        
                        /*
                        A költségviselő értéke importból jön
                        $('#kolt_vis_autocomp').val('');
                        $('#kolt_vis').val('');
                        if( $('#kolt_vis_autocomp').attr('value') ){
                            $('#kolt_vis_autocomp').addClass('mod');
                        } else {
                            $('#kolt_vis_autocomp').removeClass('mod');
                        }
                        */

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
    
    // Itt első képésként a tételeket csoportosítjuk, tehát előállítjuk a visszaküldött XML tartalmát
    // Ha ténylegesen van csoporotsított sor, akkor annak tárgyait is bekérjük egy popuppal
    if( !mehet ) {
        $.ajax({
            url : 'fenntartasi_szamla.php',
            type: 'POST',
            data: {
                id                           : _get('id'),
                kuldes_igazolasra_csoportosit: Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
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
        return false;
    }
    
    // Ha túl vagyunk a csoportosításon, akkor elküldük igazolásra a számlát, 
    // itt készül majd el ténylegesen az XML, amit a fejben eltárolunk, hogy küldéskor meglegyen
    $.ajax({
        url : 'fenntartasi_szamla.php',
        type: 'POST',
        data: {
            id               : _get('id'),
            kuldes_igazolasra: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                
                szamlaBetolt(_get('id'), 'fenntartasi_szamla.php');
                alert2();
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Vissza az ügyintézőnek
function vissza_ui_nek(mehet) {
    if( !mehet ) {
        alert2('Biztos, hogy visszaküldi a számlát a műszaki ügyintézőnek?', '<input type="button" class="gomb zold enter" value="Igen" onclick="vissza_ui_nek(\'-mehet-\');"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    } else {
        $.ajax({
            url : 'fenntartasi_szamla.php',
            type: 'POST',
            data: {
                recid        : _get('id'),
                vissza_ui_nek: Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                    
                    szamlaBetolt(_get('id'), 'fenntartasi_szamla.php');
                    alert2();
                }
            },
            complete: function (xhr) {
                if (xhr.status != 200) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    }
}

// Tételek összevonásakor, amikor mentjük a tételek tárgyait, ellenőrizzük, hogy ki van-e töltve mindenhol
// ha igen, akkor ezeket tároljuk is le az adatbázisba
function osszevon_targy_kitoltve_ell() {
    
    var mehet = true;
    var targyak = [];
    $('.osszevon_targy').each(function () {
        if( !$(this).val().trim() ) {
            $('#osszevon_hiba').html('Kérem, adja meg minden tételhez a kívánt tárgyat!');
            $(this).focus();
            $(this).addClass('alert');
            mehet = false;
            return false;
        }
        targyak.push($(this).val());
    });
    
    if( mehet ) {
        $.ajax({
            url : 'fenntartasi_szamla.php',
            type: 'POST',
            data: {
                id                          : _get('id'),
                targyak                     : JSON.stringify(targyak),
                osszevon_targy_kitoltve_ell : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                }
                kuldes_igazolasra('mehet');
            },
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    }

}
// Ha beleírtak egy tárgy mezőbe, akkor egy esetleges alert már ne legyen rajta
$(document).on('input', '.osszevon_targy', function(e) {
    $(this).removeClass('alert');
});

// Adatlapon található deviza árfolyam és deviza nettó mezőből számol ki nettó értéket
$(document).on('blur', '#dev_net', function(e) {
    $('#netto').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*($('#arfolyam').val().replace(/ /g, '').replace(',', '.')), 0, ',', ' ') );
});