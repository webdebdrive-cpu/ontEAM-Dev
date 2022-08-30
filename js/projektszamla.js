// Projekt számla feltöltése köztes táblából
function pr_szamla_feltolt() {
    csoportMuvelet('projektszamla_feltolt');
}

// Számla igazolása
function szamla_igazol(mehet) {
    
    if (!mehet) {
        alert2('Biztosan igazolja a számlát?', '<input type="button" class="gomb zold" value="Igen" onclick="szamla_igazol(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'projektszamla.php',
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
                    szamlaBetolt(_get('id'), 'projektszamla.php');
                    setTimeout(function () {
                        alert2(response.hiba, response.gomb, response.title);
                    }, 500);
                    
                    return false;
                }
                szamlaBetolt(_get('id'), 'projektszamla.php');
                
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

// Számla utóigazolása
function szamla_utoigazol(mehet) {
    
    if (!mehet) {
        alert2('Biztosan utóigazolja a számlát?', '<input type="button" class="gomb zold" value="Igen" onclick="szamla_utoigazol(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'projektszamla.php',
        type: 'POST',
        data: {
            id              : _get('id'),
            szamla_utoigazol: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    szamlaBetolt(_get('id'), 'projektszamla.php');
                    setTimeout(function () {
                        alert2(response.hiba, response.gomb, response.title);
                    }, 500);
                    
                    return false;
                }
                szamlaBetolt(_get('id'), 'projektszamla.php');
                
                setTimeout(function () {
                    alert2('Számla sikeresen utóigazolva!', '', 'Üzenet');
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

// Visszaadva pipa lekezelése
function pr_szamla_visszaadva() {
    if (!$('#visszaadva').prop('checked')) {
        $('#visszaadva_dt').val('').blur();
        $('#visszaadva_dt').removeClass('csillag');
    } else {
        $('#visszaadva_dt').addClass('csillag');
    }
}

// Projektszerződés hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény.
function prsz_hozzaad_gomb_hozzaad_vagy_torol() {
    
    // Illetve megnézzük, hogy van-e kapcsolt szerződés tétel, mert ha igen, akkor csatolt Projektet törölni már nem lehet
    $.ajax({
        url : 'projektszamla.php',
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
                        $('#pr_szam').val('');
                        if( $('#pr_szam').attr('value') ){
                            $('#pr_szam').addClass('mod');
                        } else {
                            $('#pr_szam').removeClass('mod');
                        }
                        $('#let_fel_autocomp').val('');
                        $('#let_fel').val('');
                        if( $('#let_fel_autocomp').attr('value') ){
                            $('#let_fel_autocomp').addClass('mod');
                        } else {
                            $('#let_fel_autocomp').removeClass('mod');
                        }
                        /*
                        A költségviselő értéke importból jön
                        $('#kolt_vis_autocomp').val('');
                        $('#kolt_vis').val('');
                        if( $('#kolt_vis_autocomp').attr('value') ){
                            $('#kolt_vis_autocomp').addClass('mod');
                        } else {
                            $('#kolt_vis_autocomp').removeClass('mod');
                        }*/

                        $('#szerzodes_neve').html('');
                        $('#projekt_neve').html('');

                        formModositva = true;
                        $('#sz_szam_gomb').val('Szerződés hozzáadása').removeClass('piros');
                        $('#mehet_gomb').addClass('gombb_alert');
                        szurkit_zar('mehet_megsem_mutat');
                        popupZar();
                        popupRejt();

                    } else {

                        csoportMuvelet('projektszamla_szerzodes_valaszt');

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
            url : 'projektszamla.php',
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
        url : 'projektszamla.php',
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
                
                szamlaBetolt(_get('id'), 'projektszamla.php');
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
            url : 'projektszamla.php',
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

// Ha változna a szálloda, akkor a kapcsolt szerződést is nullázzuk
function sz_szam_urit() {
    $('#sz_szam_gomb').val('Szerződés hozzáadása').removeClass('piros');
    $('#sz_szam').val('').addClass('mod');
}

// Ha beleírtak egy tárgy mezőbe, akkor egy esetleges alert már ne legyen rajta
$(document).on('input', '.osszevon_targy', function(e) {
    $(this).removeClass('alert');
});


// Adatlapon található nettó mezőből számol ki bruttó értéket
// FONTOS, hogy ne onclick-es verzió legyen, mert az onclick tulajdonyágot levessük JS-el, ha elszürkített a mező. És ezek a mezők oda-vissza lehetnek aktívak/inaktívak a deviza alapján.
// EZT NEM TESSZÜK MEG, mert a Bruttó egy fixen kapott érték az Andoctól
/*$(document).on('blur', '#netto', function(e) {
    if( !$('#brutto').val() || $('#brutto').val()=='0' ){
        $('#brutto').val( ezresek($('#netto').val().replace(/ /g, '')*((Number(AFA)+100)/100), 0, '', ' ') );
    }
});
*/

// Ha a bruttósított nettó és a valódi bruttó között 2 Ft-nál nagyobb az eltérés, akkor hibát dobunk. 
function pr_szamla_netto_br_ell() {
    var brutto          = Number($('#brutto').val().replace(/ /g, ''));
    var netto_afaval    = Number($('#netto').val().replace(/ /g, '')*((Number(AFA)+100)/100));
    
    if( Math.abs( brutto - netto_afaval ) > 2 ){
        return 'A Bruttó érték ('+$('#brutto').val()+') nem egyenlő a Nettó érték ('+$('#netto').val()+') * '+AFA+'% ÁFÁ-val.<br>Folytatja?';
    }
}

// Adatlapon található devizat nettó mezőből számol ki deviza bruttó értéket
// FONTOS, hogy ne onclick-es verzió legyen, mert az onclick tulajdonyágot levessük JS-el, ha elszürkített a mező. És ezek a mezők oda-vissza lehetnek aktívak/inaktívak a deviza alapján.
// EZT NEM TESSZÜK MEG, mert a Bruttó egy fixen kapott érték az Andoctól
/*
 * $(document).on('blur', '#dev_net', function(e) {
    if( !$('#dev_br').val() || $('#dev_br').val()=='0,00' ){
        $('#dev_br').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*((Number(AFA)+100)/100), 2, ',', ' ') );
    }
});*/

// Adatlapon található deviza árfolyam és deviza nettó mezőből számol ki nettó értéket
$(document).on('blur', '#dev_net', function(e) {
    // Mindig újraszámoljuk
    // if( !$('#netto').val() || $('#netto').val()=='0' ){
        $('#netto').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*($('#arfolyam').val().replace(/ /g, '').replace(',', '.')), 0, ',', ' ') );
    // }
});

// Ha a bruttósított nettó és a valódi bruttó között 2 Ft-nál nagyobb az eltérés, akkor hibát dobunk. 
function pr_szamla_deviza_netto_br_ell() {
    var brutto          = Number($('#dev_br') .val().replace(/ /g, '').replace(',', '.'));
    var netto_afaval    = Number($('#dev_net').val().replace(/ /g, '').replace(',', '.')*((Number(AFA)+100)/100));
    
    if( Math.abs( brutto - netto_afaval ) > 1 ){
        return 'A Deviza bruttó érték ('+$('#dev_br').val()+') nem egyenlő a Deviza nettó érték ('+$('#dev_net').val()+') * '+AFA+'% ÁFÁ-val.<br>Folytatja?';
    }
}

// Pénznem szerkesztésekor a kapcsolódó mezők disabled-ezése
// Ez sima adatlap betöltésekor is lefut, mert az csinálunk egy blurt a kcs_21_autocomp mezőre.
// EZ AZ EGÉSZ KI VAN KAPCSOLVA, mert ezeket importként kapjuk az Andoc-ból, az a mérvadó (tehát hiába devizás egy számla, annak árfolyama, bruttója semmiképp sem szerkeszthető)
/*$(document).on('input, blur', '#kcs_21_autocomp', function(e) {
    // Azért van egy settimeoutban, mert a mezőt elhagyva még az autocomplete feltölti adattal. De az elhagyás pillanatában még üres.
    setTimeout(function(){
        if ($('#kcs_21_autocomp').val() && $('#kcs_21_autocomp').val() != 'HUF') {
            $('#arfolyam')      .removeAttr('disabled').removeAttr('readonly').focus().addClass('csillag');
            $('#atvaltva_dt')   .removeAttr('disabled').removeAttr('readonly');
            
            if( !$('#nullas').prop('checked') ) {
                $('#dev_net')   .removeAttr('disabled').removeAttr('readonly').addClass('csillag');
                $('#dev_br')    .removeAttr('disabled').removeAttr('readonly').addClass('csillag');
            }
            
        } else {
            
            $('#arfolyam')      .val('0,0000')      .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
            $('#atvaltva_dt')   .val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0').attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod');
            $('#dev_net')       .val('0,00')        .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
            $('#dev_br')        .val('0,00')        .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
            
        }
        tabindex_beallit();
    }, 200);
});*/