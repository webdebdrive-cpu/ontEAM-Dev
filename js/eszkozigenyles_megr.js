// Megrendelő kiküldése
function megrendelo_kikuldese( mehet ) {
    
    if( !mehet ) {
        alert2('Rendelés száma: '+$('#rend_szam_db').val()+'<br>Megrendelő kiküldése a szállítónak.<br>Folytatja?', '<input type="button" class="gomb zold enter" value="Igen" onclick="megrendelo_kikuldese(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
        return false;
    }
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                      : _get('id'),
            megrendelo_kikuldese    : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                if( response.siker ) {
                    alert2('Megrendelők sikeresen kiküldve', '', 'Üzenet');
                    szamlaBetolt(_get('id'), 'eszkozigenyles_megr.php', 'modosit')
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
