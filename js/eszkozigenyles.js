$(document).ready(function(){
//     huf_vagy_deviza();
//     nettokat_atszamol();
    setTimeout(function(){
        $('#dragTable').animate({ opacity: 1 }, 150);
    }, 100);
});
// Pénznemet kiválasztva fut le: cél, hogy a devizára kerüljön rá egy readonly, ha HUF-os, más különben az űrlap postolásakor szólni fog, hogy nincs kitöltve
function huf_vagy_deviza() {
    // Adatlapon státusz felirat mögött van egy hidden input adatlap_disabled ID-val.
    // Ez 1-es értéket kap akkor, ha az adatlap egyáltalán nem szerkeszthető, mert olyan státuszban van vagy jogok hiányoznak
    // Ekkor a huf_vagy_deviza() függvény se csináljon semmit, még véletlen se váljon írhatóvá egyik mező sem

    if( !$('#adatlap_disabled').val() ) {
        if( $('#kcs_21_autocomp').val()=='HUF' ) {
            $('#dev_net'        ).val('0,00').attr('readonly', 'readonly').removeClass('csillag').removeClass('mod');
            $('#arfolyam'       ).val('0,00').attr('readonly', 'readonly').removeClass('csillag').removeClass('mod');
            $('#kiszall'        ).attr('data-tort', '0').removeClass('nincs_tobb_blurozas').blur();
            $('#beuzemel'       ).attr('data-tort', '0').removeClass('nincs_tobb_blurozas').blur();
            $('#munkadij'       ).attr('data-tort', '0').removeClass('nincs_tobb_blurozas').blur();
            $('#egyeb_koltseg'  ).attr('data-tort', '0').removeClass('nincs_tobb_blurozas').blur();
            $('#netto'          ).removeAttr('readonly');
            if( $('#szallito_id').val() ) {
                $('#netto'   ).addClass('csillag');
            }
        } else if( $('#kcs_21_autocomp').val() ) {
            $('#dev_net'        ).removeAttr('readonly').addClass('csillag');
            $('#arfolyam'       ).removeAttr('readonly').addClass('csillag');
            $('#kiszall'        ).attr('data-tort', '2').removeClass('nincs_tobb_blurozas').blur();
            $('#beuzemel'       ).attr('data-tort', '2').removeClass('nincs_tobb_blurozas').blur();
            $('#munkadij'       ).attr('data-tort', '2').removeClass('nincs_tobb_blurozas').blur();
            $('#egyeb_koltseg'  ).attr('data-tort', '2').removeClass('nincs_tobb_blurozas').blur();
            $('#netto'          ).attr('readonly', 'readonly').removeClass('csillag').removeClass('mod');

            // EUR esetén az áfa mező legyen kitöltve
            if( $('#kcs_21_autocomp').val()=='EUR' ) {
                // Tervezett esetén az időszak áfolyamával
                var tervezett = 1;
                if( $('#tipus').val()!=1 ) {
                    tervezett = 0;
                }

                $.ajax({
                    url : PHP_SELF,
                    type: 'POST',
                    data: {
                        id              : _get('id'),
                        tervezett       : tervezett,
                        get_arfolyam    : Date.now()
                    },
                    success: function(response){
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response){
                            response = JSON.parse(response);
                            if(response.hiba){
                                alert2(response.hiba, response.gomb, response.title);
                                return false;
                            }

                            $('#arfolyam').val( response.arfolyam );
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
        tabindex_beallit();
    }
}

// Nettó mezőket, árfolyamot, devizákat átszámol
function nettokat_atszamol() {
    var igmen = Number($('#igmen').val().replace(/ /g, '').replace(/,/g, '.'));
    if( $('#kcs_21_autocomp').val()!='HUF' ) {
        var dev_net  = Number($('#dev_net' ).val().replace(/ /g, '').replace(/,/g, '.'));
        var arfolyam = Number($('#arfolyam').val().replace(/ /g, '').replace(/,/g, '.'));
        $('#netto'   ).val( ezresek(Math.round(dev_net*arfolyam), 0, ',', ' ', '-nullát is kiír-') );
        $('#ossz_dev').val( ezresek(igmen*dev_net,                2, ',', ' ', '-nullát is kiír-') );
    }

    var netto = Number($('#netto').val().replace(/ /g, '').replace(/,/g, '.'));
    $('#ossz_netto').val( ezresek(Math.round(igmen*netto), 0, ',', ' ', '-nullát is kiír-') );
}

// Nettó érték kiszámítása
$(document).on('input', '#dev_net, #arfolyam, #igmen, #netto', function(){
    nettokat_atszamol();
});

// Ezek az alábbi függvényekhez kellenek majd
var eszkoz_id = _get('id');
var eszkoz_tablazat = isset_get('tablazat');
var eszkoz_url_kieg = '';
if( eszkoz_tablazat ) {
    eszkoz_url_kieg = '?tablazat';
}
// Ezzel pedig aktualizálni lehető őket
function eszkoz_valtozok_update() {
    eszkoz_id = _get('id');
    eszkoz_tablazat = isset_get('tablazat');
    eszkoz_url_kieg = '';
    if( eszkoz_tablazat ) {
        eszkoz_url_kieg = '?tablazat';
    }    
}

// Mehet BI validálásra
var eszkoz_fulek = ['modosit', 'jovahagyasok', 'tortenet', 'dokumentumok', 'megjegyzesek'];
function mehet_validalasra(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Biztosan mehet az igénylés validálásra?', '<input type="button" class="gomb zold enter" value="Igen" onclick="mehet_validalasra(\'-tervezett-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                  : eszkoz_id,
            mehet_validalasra   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                alert2();

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.musz_el_ig_'    +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    $('.kiv_kolts_'     +eszkoz_id  ).html('0');
                    $('#kiv_kolts_db_'  +eszkoz_id  ).val ('0');
                    $('#musz_indok_db_' +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülhetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                    // Ilyet mégsem kérnek
                    // naviKov();

                    // Gombok tényleges cseréje
                    $('.muveletek').html(response.gombok   );
                
                // Egyébként az adatlapot töltsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Visszavonás műszaki alatt
function muszaki_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                  : eszkoz_id,
            muszaki_visszavon   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.musz_el_ig_'    +eszkoz_id  ).html(response.pipa     );
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );
                    // Visszavonáskor ürül a kivitelezési költség és indoklás is
                    $('.kiv_kolts_'     +eszkoz_id  ).html('0');
                    $('#kiv_kolts_db_'  +eszkoz_id  ).val ('0');
                    $('#musz_indok_db_' +eszkoz_id  ).val ('');

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)// Lépjünk a következő sorra
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);

                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Szálloda igazgató visszavon
function szalloda_ig_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                      : eszkoz_id,
            szall_igazgato_visszavon: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.szall_ig_vel_'      +eszkoz_id  ).html(response.pipa     );
                    $('#szall_ig_indok_db_' +eszkoz_id  ).val ('');
                    $('.gombok_'            +eszkoz_id  ).html(response.gombok   );

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// BI visszavon
function bi_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id          : eszkoz_id,
            bi_visszavon: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.bi_vel_'        +eszkoz_id  ).html(response.pipa     );
                    $('#bi_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Szakmai vezetés visszavon
function szak_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id              : eszkoz_id,
            szak_visszavon  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.szak_vel_'      +eszkoz_id  ).html(response.pipa     );
                    $('#szak_indok_db_' +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Felsővezető visszavon
function felso_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id              : eszkoz_id,
            felso_visszavon : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.felso_vel_'     +eszkoz_id  ).html(response.pipa     );
                    $('#felso_indok_db_'+eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Feldolgozva
function igenyles_feldolgozva(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Biztosan feldolgozásra került az igénylés?', '<input type="button" class="gomb zold enter" value="Igen" onclick="igenyles_feldolgozva(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id              : eszkoz_id,
            indok           : $('#popup_bi_vegl_indok').val(),
            // Ez arra az ágra viszi a működést, mint soron kívülinél a bi_vegl_jovahagy
            bi_vegl_jovahagy: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                alert2();

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    $('.bi_vegl_'       +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    // Erre még nincs oszlop beállítva $('#bi_vegl_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülhetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html('Dokumentumok ('+response.dok_db+')');

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülhetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ilyet mégsem kérnek
                        // naviKov();
                        // Helyette a gombokat frissítsük
                        $('.muveletek').html(response.gombok);

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok);
                    }
                
                // Egyébként az adatlapot töltsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        setTimeout(function(){
                            // Ilyet mégsem kérnek
                            // naviKov();
                        }, 1000);
                    }
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });

}

// BI véglegesítés visszavon
function bi_vegl_visszavon() {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id               : eszkoz_id,
            bi_vegl_visszavon: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    // Erre még nincs oszlop beállítva $('.bi_vegl_'            +eszkoz_id  ).html(response.pipa     );
                    // Erre még nincs oszlop beállítva $('#bi_vegl_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'+eszkoz_id  ).html(response.gombok);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    // Ilyet mégsem kérnek
                    // naviKov();
                
                // Egyébként az adatlapot tötsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    setTimeout(function(){
                        // Ilyet mégsem kérnek
                        // naviKov();
                    }, 1000);
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// BI véglegesítés
function vissza_validalasra(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Biztosan visszakerülhet az igénylés BI validálásra?', '<input type="button" class="gomb zold enter" value="Igen" onclick="vissza_validalasra(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                  : eszkoz_id,
            vissza_validalasra  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                alert2();

                // Táblázat alatt a sort frissítjük
                if( isset_get('tablazat') ) {
                    // Erre még nincs oszlop beállítva $('.bi_vegl_'       +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    // Erre még nincs oszlop beállítva $('#bi_vegl_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülhetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html('Dokumentumok ('+response.dok_db+')');

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülhetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ilyet mégsem kérnek
                        // naviKov();
                        // Helyette a gombokat frissítsük
                        $('.muveletek').html(response.gombok);

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok);
                    }
                
                // Egyébként az adatlapot töltsük be
                } else {

                    let ful   = '';
                    eszkoz_fulek.forEach(function(akt_ful) {
                        if( isset_get(akt_ful) ) {
                            ful = akt_ful;
                            return;
                        }
                    });
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        setTimeout(function(){
                            // Ilyet mégsem kérnek
                            // naviKov();
                        }, 1000);
                    }
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });


}

// Ha a szállító ki van töltve, pár mező váljon kötelezővé
var szallito_mezok = ['kcs_21_autocomp', 'igmen'];
function szallito_kotelezo_mezok() {

    if( $('#szallito_id').val() ) {
        for( i=0; i<szallito_mezok.length; i++ ) {
            $('#'+szallito_mezok[i]).addClass('csillag');
        }

    }

    // Ez a függvény is fusson le, mert ebben jelezzük, hogy melyik nettós mező aktív és kötlező
    huf_vagy_deviza();
    
}
$(document).on('blur', '#szallito_id_autocomp', function(){
    setTimeout(function(){
        if( !$('#szallito_id').val() ) {
            for( i=0; i<szallito_mezok.length; i++ ) {
                $('#'+szallito_mezok[i]).removeClass('csillag');
            }
            $('#netto').removeClass('csillag');
        }
    }, 100);
});

// Megrendelők elkészítése
function megrendelok_elkeszit(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();
    
    if( !mehet ) {
        $.ajax({
            url : PHP_SELF,
            type: 'POST',
            data: {
                id                  : eszkoz_id,
                csak_kerdez         : 1,
                megrendelok_elkeszit: Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                    alert2(response.db+' db. igénylés került jóváhagyásra.<br>Biztosan elkészíthetőek a megrendelők?', '<input type="button" class="zold enter gomb" value="Igen" onclick="megrendelok_elkeszit(\'-mehet-\')"> <input type="button" class="piros gomb" value="Nem" onclick="alert2()">', 'Kérdés');
                }
            },
            complete: function (xhr) {
                if (xhr.status != 200) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                  : eszkoz_id,
            megrendelok_elkeszit: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                location.reload(); 
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}