$(document).ready(function () {
    /* Sorok kijelölése kattintásra */
    $(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    });
    
});

var pr_ml_levesz_id = '';

/* Léptetés Előkészítés alatt státuszba */
function projekt_elokeszites_ala() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid                   : _get('id'),
            projekt_elokeszites_ala : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Léptetés Folyamatban státuszba */
function projekt_folyamatbanra() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid                   : _get('id'),
            projekt_folyamatbanra   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Léptetés Kész státuszba */
function projekt_keszre() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid           : _get('id'),
            projekt_keszre  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Léptetés Kész státuszba */
function projekt_lezartra() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid           : _get('id'),
            projekt_lezartra: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Sikeres fájlfeltöltés után rákérdezünk, hogy léptessük-e tovább a Projektet */
function projekt_fajfeltoltes_utan_tovabb () {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid                           : _get('id'),
            projekt_fajfeltoltes_utan_tovabb: Date.now()
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
}

/* Felfüggesztés vége */
function projekt_felfuggesztes_vege(){
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid                       : _get('id'),
            projekt_felfuggesztes_vege  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Cashflow sor törlése */
function cf_sor_torol(mehet){
    
    /* Kijelölt sor */
    var id = $('.szamla_cont_tabla tr.kijelolve').attr('data-recid');
    if( id=='szerzodesek' ){
        alert2('A szerződés cash-flow nem törölhető!', '', 'Hiba');
        popupZar();
        setTimeout(function(){ popupZar(); }, 100);
        return false;
    }
    if( id=='szamlak' ){
        alert2('A számla cash-flow nem törölhető!', '', 'Hiba');
        popupZar();
        setTimeout(function(){ popupZar(); }, 100);
        return false;
    }
    if(!id){
        alert2('Kérem, előbb jelöljön ki egy cash-flow sort!', '', 'Hiba');
        popupZar();
        setTimeout(function(){ popupZar(); }, 100);
        return false;
    }
    
    if(!mehet){
        alert2('Biztosan törli a kijelölt cash-flow sort?', '<input type="button" class="gomb zold" value="Igen" onclick="cf_sor_torol(\'mehet\'); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">');
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid       : id,
            cf_sor_torol: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb,response.title);
                }else{
                    szamlaBetolt( _get('id'), 'projektkezeles.php', 'cash_flow' );
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

/* Feltöltött dokumentumok sorainak végén van egy minősítés gomb, az futtatja ezt a függvényt */
var dok_sorszama = 0;
function dok_minosites( sorszam, szurt = '' ) {
    dok_sorszama = sorszam;
    if( szurt ) {
        csoportMuvelet('projektszerzodes_dok_minosites_szurt');
    } else {
        csoportMuvelet('projektszerzodes_dok_minosites');
    }
}

/* Projekt újranyitása */
function projektkezeles_ujranyit() {
    $.ajax({
        url: 'projektkezeles.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            projektkezeles_ujranyit : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                } else {
                    szamlaBetolt( _get('id'), 'projektkezeles.php', 'modosit' );
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

/* Projekt folyamatbanra állítása */
function folyamatbanra_allit() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid               : _get('id'),
            folyamatbanra_allit : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                } else {
                    popupZar();
                    szamlaBetolt( _get('id'), 'projektkezeles.php', 'dokumentumok' );
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

/* Projekt folyamatbanra állítása */
function keszre_allit() {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid           : _get('id'),
            keszre_allit    : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                } else {
                    popupZar();
                    szamlaBetolt( _get('id'), 'projektkezeles.php', 'dokumentumok' );
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

/* Küldendő dokuemntumok bepiálásának megjegyése */
function projekt_dok_check( recid, checked ) {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid               : recid,
            checked             : checked,
            projekt_dok_check   : Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
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

/* Minősített dokumentumról töröljük a minősítést */
function minosites_torol( recid, tabla, mehet ) {
    if( !mehet ) {
        alert2('Biztosan törli a minősítést a következő dokumentumról:<br>'+$('#megj_kep'+recid+'_felirat a').html(), '<input type="button" class="gomb zold" value="Igen" onclick="minosites_torol( \''+recid+'\', \''+tabla+'\', \'mehet\' );"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            recid           : recid,
            tabla           : tabla,
            minosites_torol : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                } else {
                    szamlaBetolt( _get('id'), 'projektkezeles.php', 'dokumentumok' );
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

/* Amikor kiválasztottak egy projekt felelőst, akkor ellenőrizzük, hogy Területi felelős-e, ha igen, akkor annak megfelelően lesz szerekszthető pár mező */
function projekt_felelos_beallitva( login_id ) {
    
    var aktualis_szalloda = $('#telephely_id').val();
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            login_id        : login_id,
            get_szerepkor   : Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if( response.szemelyzet_id == SZEREPKOR_TER_FELELOS ) {
                    $('#tipus').val(PROJEKT_TIPUS).prop('disabled', true);
                } else {
                    $('#tipus').val('').prop('disabled', false);
                }
                $('#telephely_id').replaceWith(response.telephely_select);
                
                /* Eredeti szálloda visszaírása, ha lehetséges */
                $('#telephely_id').val(aktualis_szalloda);
                if (!$('#telephely_id option[value="' +aktualis_szalloda+ '"]').prop('selected', true).length) {
                    $('#telephely_id').val('');
                }
            }
        }
    });
    
}