$(document).ready(function(){
//     huf_vagy_deviza();
//     nettokat_atszamol();
    setTimeout(function(){
        $('#dragTable').animate({ opacity: 1 }, 150);
    }, 100);
});
// Pénznemet kiválasztva fut le: cél, hogy a devizára kerüljön rá egy readonly, ha HUF-os, más különben az űrlapo postolásakor szólni fog, hogy nincs kitöltve
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

// Mehet validálásras
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

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                    naviKov();

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

// Műszakilag nem érintett gomb
function muszakilag_nem_erintett(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Az igénylés műszakilag nem érintett.<br>Biztosan mehet igazgatói engedélyezésre?', '<input type="button" class="gomb zold enter" value="Igen" onclick="muszakilag_nem_erintett(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                      : eszkoz_id,
            muszakilag_nem_erintett : Date.now()
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

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
                    naviKov();
                
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

// Szálloda igazgató jóváhagy
function szall_igazgato_jovahagy(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Biztosan mehet BI validálásra az igénylés?', '<input type="button" class="gomb zold enter" value="Igen" onclick="szall_igazgato_jovahagy(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                      : eszkoz_id,
            szall_igazgato_jovahagy : Date.now()
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
                    $('.szall_ig_vel_'      +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'              +eszkoz_id  ).html(response.statusz  );
                    $('#szall_ig_indok_db_' +eszkoz_id  ).val ('');
                    $('.gombok_'            +eszkoz_id  ).html(response.gombok   );

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
                    naviKov();
                
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

// BI validál
function bi_validal(mehet, szakmai_vel = '') {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        // 2025.09.25 Új működés szerint BI jóváhagyásra léptetejük, még a GM-eset is, nincs tehát továbbadva a kikuld paraméter sem
        // let kerdes = 'Biztosan mehet szakmai véleményezésre az igénylés?';
        let kerdes = 'Biztosan mehet BI jóváhagyásra az igénylés?';
        if( szakmai_vel ) {
            kerdes = 'Biztosan mehet szakmai véleményezésre az igénylés?';
        }
        alert2(kerdes, '<input type="button" class="gomb zold enter" value="Igen" onclick="bi_validal(\'-mehet-\', \''+szakmai_vel+'\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id          : eszkoz_id,
            szakmai_vel : szakmai_vel,
            bi_validal  : Date.now()
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
                    $('.bi_vel_'        +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    $('#bi_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    let dok_ful_txt = 'Dokumentumok';
                    if( response.megj_db>0 ) {
                        dok_ful_txt = 'Dokumentumok ('+response.dok_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html(dok_ful_txt);

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( response.naviKov ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();
                    } 
                    
                    // A tényleges gombokat is cserélni kell
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

// BI jóváhagy
function bi_jovahagy(mehet, kikuld = '') {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        let kerdes = 'Mehet felsővezető 1. szint jóváhagyásra az igénylés?';
        if( kikuld ) {
            kerdes = 'Ha folytatja, elkészül a megrendelés rekord és a megrendelő dokumentum.<br>Biztosan mehet kiküldésre vár státuszba az igénylés?';
        }
        alert2(kerdes, '<input type="button" class="gomb zold enter" value="Igen" onclick="bi_jovahagy(\'-mehet-\', \''+kikuld+'\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id          : eszkoz_id,
            kikuld      : kikuld,
            bi_jovahagy : Date.now()
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
                    $('.bi_vel_'        +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    $('#bi_indok_db_'   +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    let dok_ful_txt = 'Dokumentumok';
                    if( response.megj_db>0 ) {
                        dok_ful_txt = 'Dokumentumok ('+response.dok_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html(dok_ful_txt);

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
                    naviKov();
                
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

// Szakmai vezetés jóváhagy
function szak_beleegyezes(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Biztosan mehet felsővezetői jóváhagyásra az igénylés?', '<input type="button" class="gomb zold enter" value="Igen" onclick="szak_beleegyezes(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id              : eszkoz_id,
            szak_beleegyezes: Date.now()
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
                    $('.szak_vel_'      +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    $('#szak_indok_db_' +eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( response.naviKov ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();
                    }

                    // Tényleges gombokat is cserélni kell
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
                    naviKov();
                
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

// Felsővezető jóváhagy
function felso_beleegyezes(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Ha folytatja, elkészül a megrendelés rekord és a megrendelő dokumentum.<br>Biztosan jóváhagyja az igénylést?', '<input type="button" class="gomb zold enter" value="Igen" onclick="felso_beleegyezes(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id                  : eszkoz_id,
            felso_beleegyezes   : Date.now()
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
                    $('.felso_vel_'     +eszkoz_id  ).html(response.pipa     );
                    $('.stat_'          +eszkoz_id  ).html(response.statusz  );
                    $('#felso_indok_db_'+eszkoz_id  ).val ('');
                    $('.gombok_'        +eszkoz_id  ).html(response.gombok   );
                    
                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    let dok_ful_txt = 'Dokumentumok';
                    if( response.megj_db>0 ) {
                        dok_ful_txt = 'Dokumentumok ('+response.dok_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html(dok_ful_txt);

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
                    naviKov();
                
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
function bi_vegl_jovahagy(mehet) {

    // globális változók frissítése az adott adatlap alapján
    eszkoz_valtozok_update();

    if( !mehet ) {
        alert2('Ha folytatja, elkészül a megrendelés rekord és a megrendelő dokumentum.<br>Biztosan véglegesíti az igénylést?', '<input type="button" class="gomb zold enter" value="Igen" onclick="bi_vegl_jovahagy(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    // Jóváhagyás
    $.ajax({
        url : PHP_SELF+eszkoz_url_kieg,
        type: 'POST',
        data: {
            id              : eszkoz_id,
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

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html('Dokumentumok ('+response.dok_db+')');

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
                    $('.gombok_'                +eszkoz_id  ).html(response.gombok   );

                    // Lépjünk a következő sorra (Visszavonás csak tervezett esetben lehet, ekkor biztosan sort kell lépteteni)
                    naviKov();
                
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

                    // Dokumentumok számát is, mert ha nem tervezett volt, akkor új dokumentummal is bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('dok_db', response.dok_db    );
                    // Fülön is
                    $('#szamlaFulek a[data-oldal="dokumentumok"]').html('Dokumentumok ('+response.dok_db+')');

                    // Megjegyzések számát is, mert ha volt értesítés küldés, akkor bővülthetett
                    $('.gombok_'        +eszkoz_id  ).data('megj_db', response.megj_db    );
                    // Fülön is
                    let megj_ful_txt = 'Megjegyzések';
                    if( response.megj_db>0 ) {
                        megj_ful_txt = 'Megjegyzések ('+response.megj_db+')';
                    }
                    $('#szamlaFulek a[data-oldal="megjegyzesek"]').html(megj_ful_txt);

                    // Ha a mehet a '-tervezett-' igénylés miatt volt kitöltve, akkor lépünk a következő sorra. 
                    if( mehet == '-tervezett-' ) {
                        // Ez hívódik meg a lap tetején léptető gombnyomásra is.
                        naviKov();

                    // Egyébként a tényleges gombokat is cserélni kell
                    } else {
                        $('.muveletek').html(response.gombok   );
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
    
    if( !mehet ) {
        alert2('Biztosan elkészíthetőek a megrendelők?', '<input type="button" class="zold enter gomb" value="Igen" onclick="megrendelok_elkeszit(\'-mehet-\')"> <input type="button" class="piros gomb" value="Nem" onclick="alert2()">', 'Kérdés');
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