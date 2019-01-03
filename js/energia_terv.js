/* Egységárak táblázat kijelölés klikkre */
$(document).ready(function () {

    $(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    });

});

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
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {

                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szurkit();
                    popupZar();
                    szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'egysegarak' ).done(function() {
                        $('.szamla_cont_tabla tr').removeClass('kijelolve');
                        $('#tr_ear_' + recid).addClass('kijelolve');
                        $('#tartalomJobb .fht-tbody').scrollTop($('#tr_ear_' + recid+'.kijelolve').position().top);
                    });

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

/* Új egységár sor feltöltése */
function uj_egysegar(){

    // Ha nincs kijelölve sor, akkor csak szálloda választó jön fel, a sor meg üres adatokkal jön létre.
    var kijelolt_egysegar = $('.szamla_cont_tabla .kijelolve').attr('data-recid');
    if(!kijelolt_egysegar){
        kapcsolodoSzerkeszt('szallodak_energia_uj_hozzaad');

    // Ha van aktív sok, akkor a szállodákat átmásolva, minden más adatot üresen hagyva hozunk létre új sort
    }else{
        $.ajax({
            url : 'energia_terv.php',
            type: 'POST',
            data: {
                terv_recid      : _get('id'),
                egysegar_recid  : kijelolt_egysegar,
                uj_egysegar     : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szurkit();

                    szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'egysegarak' ).done(function() {
                        $('.szamla_cont_tabla tr').removeClass('kijelolve');
                        $('#tr_ear_' + response.recid).addClass('kijelolve');
                        $('#tartalomJobb .fht-tbody').scrollTop($('#tartalomJobb .fht-tbody').prop('scrollHeight'));
                    });
                }
            }
        });
    }
    return false;
}

function uj_egysegar_szallodak(that){
    var szallID = $(that).find('input[name="addSzall"]').val();

    var telephelyek = $(that).find('input[name="telephely[]"]:checked');
    var data = {};
    telephelyek.each(function(i) {
        var value = $(this).val();
        data[i] = value;
    });

    if( objLength(data) ) {
        $.ajax({
            url : 'energia_terv.php',
            type: 'POST',
            data: {
                terv_recid      : _get('id'),
                szalloda_idk    : JSON.stringify(data),
                uj_egysegar     : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szurkit();

                    popupZar();
                    szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'egysegarak' ).done(function() {
                        $('.szamla_cont_tabla tr').removeClass('kijelolve');
                        $('#tr_ear_' + response.recid).addClass('kijelolve');
                    });
                }
            }
        });
    }else{
        alert2('Kérem válasszon szállodát!', '', 'Hiba');
    }

    return false;
}

/* Egységár sor törlése */
function egysegar_sor_torol(mehet){

    // Ha nincs kijelölve sor, hibaüzi
    var kijelolt_egysegar = $('.szamla_cont_tabla .kijelolve').attr('data-recid');
    if(!kijelolt_egysegar){
        alert2('Előbb jelölje ki a törölni kívánt sort!', '', 'Hiba');
        return false;
    }else if(!mehet){
        alert2('Biztosan törli a kijelölt egységár sort?', '<input type="button" class="gomb zold" value="Igen" onclick="egysegar_sor_torol(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    $.ajax({
        url : 'energia_terv.php',
        type: 'POST',
        data: {
            egysegar_recid      : kijelolt_egysegar,
            egysegar_sor_torol  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {

                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }else{
                    szurkit();
                    szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'egysegarak' );
                }
            }
        }
    });
}

/* Energiaterv véglegesítése */
function energiaterv_veglegesit(mehet){

    if(!mehet){
        alert2('Biztosan véglegesíti a tervet?', '<input type="button" class="gomb zold" value="Igen" onclick="energiaterv_veglegesit(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }

    $.ajax({
        url : 'energia_terv.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            energiaterv_veglegesit  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {

                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                szurkit();
                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
            }
        }
    });
}
