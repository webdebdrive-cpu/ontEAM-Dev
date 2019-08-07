$(document).ready(function () {
    /* Sorok kijelölése kattintásra */
    $(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    });
    
});

/* Léptetés Előkészítés alatt státuszba */
function projekt_elokeszites_ala() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Léptetés Folyamatban státuszba */
function projekt_folyamatbanra() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Léptetés Kész státuszba */
function projekt_keszre() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Léptetés Kész státuszba */
function projekt_lezartra() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Sikeres fájlfeltöltés után rákérdezünk, hogy léptessük-e tovább a Projektet */
function projekt_fajfeltoltes_utan_tovabb () {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Felfüggesztés vége */
function projekt_felfuggesztes_vege(){
    $.ajax({
        url : 'projektkezeles.php',
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
        alert2('Biztosan törli a kijelölt cash-fow sort?', '<input type="button" class="gomb zold" value="Igen" onclick="cf_sor_torol(\'mehet\'); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">');
        return false;
    }
    
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
    
}

/* Feltöltött dokumentumok sorainak végén van egy minősítés gomb, az futtatja ezt a függvényt */
var dok_sorszama = 0;
function dok_minosites( sorszam ) {
    dok_sorszama = sorszam;
    csoportMuvelet('projektszerzodes_dok_minosites');
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
        }
    });
}

/* Projekt folyamatbanra állítása */
function folyamatbanra_allit() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}

/* Projekt folyamatbanra állítása */
function keszre_allit() {
    $.ajax({
        url : 'projektkezeles.php',
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
        }
    });
}