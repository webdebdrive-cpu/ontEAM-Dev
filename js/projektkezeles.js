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

/* Sikeres fájlfeltöltés után rákérdezünk, hogy léptessük-e tovább a Projekt tervet */
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