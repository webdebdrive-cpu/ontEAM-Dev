// Megrendelő kiküldése
function megrendelo_kikuldese( mehet ) {
    
    if( !mehet ) {
        // A dokumentumok fülön lévő fájlok listájának lekérése, ezekből lehet még csatolni a megrendelő mellé
        $.ajax({
            url : PHP_SELF,
            type: 'POST',
            data: {
                id                  : _get('id'),
                get_kikuld_dokok    : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    alert2('Rendelés száma: '+$('#rend_szam_db').val()+'<br>Megrendelő kiküldése a szállítónak.'+response.html+'<br><br><label><input type="checkbox" id="nincs_email"> Nincs email küldés</label><br><br>Folytatja?', '<input type="button" class="gomb zold enter" value="Igen" onclick="megrendelo_kikuldese(\'-mehet-\')"> <input type="button" class="gomb piros" value="Nem" onclick="alert2()">', 'Kérdés');
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

    var nincs_email = $('#nincs_email').is(':checked') ? 1 : 0;
    // Vesszővel elválasztott szövegként küldjük, mert a config.php minden POST értéken stripslashes()-t futtat, ami tömböt nem kezel
    var dokok = $('#kikuld_dokok input:checked').map(function() { return $(this).val(); }).get().join(',');

    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            id                      : _get('id'),
            megrendelo_kikuldese    : Date.now(),
            nincs_email             : nincs_email,
            dokok                   : dokok
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
                    alert2(nincs_email ? 'Megrendelés rögzítve (e-mail küldés nélkül)' : 'Megrendelők sikeresen kiküldve', '', 'Üzenet');
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
