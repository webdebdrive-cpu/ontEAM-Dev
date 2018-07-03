/* Élesítés */
function fenntartasi_terv_elesites(mehet) {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'fenntartasi_terv.php',
            type: 'POST',
            data: {
                recid                       : _get('id'),
                mehet                       : mehet,
                fenntartasi_terv_elesites   : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                    
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    if(response.kesz_id>0){
                        szamlaSorFrissit(baseName(location.href), response.kesz_id);
                    }
                    popupZar();
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
