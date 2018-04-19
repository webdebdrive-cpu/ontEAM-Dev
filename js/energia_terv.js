/* Szálloda hozzárendelése energia tervhez */
function addSzallodaEnergia( recid, that ) {

    var szallID = $(that).find('input[name="addSzall"]').val();

    var telephelyek = $(that).find('input[name="telephely[]"]:checked');
    var data = {};
    telephelyek.each(function(i) {
        var value = $(this).val();
        data[i] = value;
    });

    if( recid && objLength(data) ) {
        var ajax = $.ajax({
         	url : 'energia_terv.php',
         	type: 'GET',
         	data: esamp2es('recid=' + recid + '&amp;szallIDs=' + JSON.stringify(data) + '&amp;addSzallodaEnergia=' + new Date().getTime()),
         	success: function(html) {
                if(html.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(html) {
                    html = JSON.parse(html);
                    if( html.statusz == 'hiba' ) {
                        alert2(html.uzi, '', 'Hiba');
                        return false;
                    }

                    popupZar();
         		}
         	}
        });
    }

    return false;
}

/* Élesítés */
function terv_elesites(mehet) {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'energia_terv.php',
            type: 'POST',
            data: {
                recid           : _get('id'),
                mehet           : mehet,
                terv_elesites   : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                    
                    location.reload();
                    /*szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();*/
                }
            }
        });

    }

    return false;
}

/* Egységárak táblázat */
$(document).ready(function () {
    
    $(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    });
    
});