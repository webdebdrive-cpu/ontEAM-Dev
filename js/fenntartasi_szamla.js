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
        }
    });
}
