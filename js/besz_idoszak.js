// Időszak megnyitása
function idoszak_megnyitasa(mehet) {
    
    if( !mehet ) {
        alert2('Biztosan megnyitja a '+$('#azon_db').val()+' időszakot?', '<input type="button" class="gomb zold enter" value="Igen" onclick="idoszak_megnyitasa(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                  : _get('id'),
            idoszak_megnyitasa  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Adatlap frissítése
                szamlaBetolt(_get('id'), 'besz_idoszak.php', 'modosit' );

            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Időszak zárása
function idoszak_zarasa(mehet) {
    
    if( !mehet ) {
        alert2('Biztosan lezárja a '+$('#azon_db').val()+' időszakot?', '<input type="button" class="gomb zold enter" value="Igen" onclick="idoszak_zarasa(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id              : _get('id'),
            idoszak_zarasa  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Adatlap frissítése
                szamlaBetolt(_get('id'), 'besz_idoszak.php', 'modosit' );

            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Határidő utáni újranyitás popupjához
var ujranyitas_fured = '';
var ujranyitas_hatar = '';

// Igényfeladás megnyitása
// Ha a szerver szerint már lejárt az igényfeladási határidő, akkor popupban kérjük be az újranyitás indoklását, és azzal küldjük újra
function igenyfeladas_nyit_zar(fured = '', zar = '', mehet = '', indoklas = '') {
    
    if( !mehet ) {
        var furedi_text = '';
        if( fured ) {
            furedi_text = ' füredi';
        }
        if( !zar ) {
            alert2('Biztosan megnyitja a '+$('#azon_db').val()+' időszak'+furedi_text+' igénylésfeladását?', '<input type="button" class="gomb zold enter" value="Igen" onclick="igenyfeladas_nyit_zar(\''+fured+'\', \''+zar+'\', \'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        } else {
            alert2('Biztosan bezárja a '+$('#azon_db').val()+' időszak'+furedi_text+' igénylésfeladását?', '<input type="button" class="gomb zold enter" value="Igen" onclick="igenyfeladas_nyit_zar(\''+fured+'\', \''+zar+'\', \'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        }
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                      : _get('id'),
            fured                   : fured,
            zar                     : zar,
            indoklas                : indoklas,
            igenyfeladas_nyit_zar   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                // Lejárt határidő: indoklás bekérése popupban
                if (response.indoklas_kell) {
                    ujranyitas_fured = fured;
                    ujranyitas_hatar = response.hatar;
                    alert2();
                    $.get('../sablon/admin/popup/besz_idoszak_ujranyitas.html?cache=' + Date.now(), function (data) {
                        $('#popupTart').html(data);
                        popupNyit();
                    });
                    return false;
                }

                if (indoklas) {
                    popupZar();
                }

                // Adatlap frissítése
                szamlaBetolt(_get('id'), 'besz_idoszak.php', 'modosit' );

            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Időszak archiválása
function idoszak_archivalasa(mehet) {
    
    if( !mehet ) {
        alert2('Biztosan archiválja a lezárt időszakokhoz tartozó igényléseket,<br>valamint a soron kívüli és GM keretes (megrendelt vagy elutasított) igényléseket?', '<input type="button" class="gomb zold enter" value="Igen" onclick="idoszak_archivalasa(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }

    alert2('Archiválás folyamatban, kérem várjon...', '', 'Üzenet');
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                  : _get('id'),
            idoszak_archivalasa : Date.now()
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