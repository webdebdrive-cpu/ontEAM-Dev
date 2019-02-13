// Előterjesztés alatti döntés gomb működése
function eloterj_dontes_feldolgoz(){
    $('.prig_statusz_txt').hide();
    $('.prig_statusz_select').show();
    
    // Bejárjuk a sorokat, megnézzük, hogy a jkolt_input-nak van-e értéke. Ha nincs, akkor a bkolt értékét állítsuk be neki
    $('.kijSor').each(function () {
        jkolt = $(this).find('.jkolt_input input').val();
        if( jkolt == '0' || !jkolt ){
            $(this).find('.jkolt_input input').val( $(this).find('.bkolt').html() );
        }
    });
    
    $('.jkolt_txt').hide();
    $('.jkolt_input').show();
    
}

// Előterejsztés módosítása (már nem csak státuszt, hanem jóváhagyott értéket is mentünk).
function pri_eloterj_statusz_mod_mentes(rogzit, mehet){

    // Rögzítéskor azt is figyeljük, hogy minden státusz be van-e már állítva
    if(rogzit){
        hianyos = 0;
        // Van-e még ki nem töltött státusz
        $('.prig_statusz_select select').each(function () {
            if(!$(this).val()){
                hianyos++;
            }
        });
        
        // Van-e még ki nem töltött jóváhagyott érték
        $('.jkolt_input input').each(function () {
            if( $(this).val() == '0' || !$(this).val() ){
                hianyos++;
            }
        });


        // Ha van még ki nem választott select
        if(hianyos){
            alert2('Még '+hianyos+' adat (státuszok, jóváhagyott keret) vár feldolgozásra.<br>Rögzítés előtt kérem dolgozza fel ezeket is!', '', 'Hiba');
            return false;
        }else if(!mehet){

            if(!$('#ules_datum').val() || $('#ules_datum').val()=='0000-00-00'){
                alert2('Kérem előbb adja meg az ülés dátumát', '<input type="button" class="gomb zold" value="Ok" onclick="szamlaBetolt( \''+_get('id')+'\', \''+baseName(location.href) + '.php\', \'modosit_ules\');">');
                return false;
            }

            kerdes_txt = ' FB';
            if(baseName(location.href) == 'eloterj_bod'){
                kerdes_txt = ' BoD';
            }
            alert2('Biztos, hogy rögzíti a'+kerdes_txt+' döntést?', '<input type="button" class="gomb zold" value="Igen" onclick="pri_eloterj_statusz_mod_mentes(\'rögzít\',\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
            return false;
        }

    }

    pri_eloterj_recidk  = {};
    stat_id_eloterjek   = {};
    jkoltek             = {};
    i = 0;
    $('.prig_statusz_select select').each(function () {
        pri_eloterj_recidk[i] = $(this).attr('data-pri_eloterj_recid');
        stat_id_eloterjek[i]  = $(this).val();
        i++;
    });
    i = 0;
    $('.jkolt_input input').each(function () {
        jkoltek[i] = $(this).val();
        i++;
    });
    pri_eloterj_statusz_mod(pri_eloterj_recidk, stat_id_eloterjek, jkoltek, rogzit);
    $('#statusz_ment_gomb').addClass('dn');
}

// Tényleges mentés adatbázisba
function pri_eloterj_statusz_mod(pri_eloterj_recidk, stat_id_eloterjek, jkoltek, rogzit){
    $.ajax({
        url : 'projektigenyles.php',
        type: 'POST',
        data: {
            tabla                   : baseName(location.href),
            eloterj_recid           : _get('id'),
            rogzit                  : rogzit,
            pri_eloterj_recidk      : JSON.stringify(pri_eloterj_recidk),
            stat_id_eloterjek       : JSON.stringify(stat_id_eloterjek),
            jkoltek                 : JSON.stringify(jkoltek),
            pri_eloterj_statusz_mod : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if(response.hiba){
                alert2(response.hiba, response.gomb, response.title);
            }else{
                alert2();
                szamlaBetolt( _get('id'), baseName(location.href) + '.php', 'projektigeny' );
            }
        }
    });
}