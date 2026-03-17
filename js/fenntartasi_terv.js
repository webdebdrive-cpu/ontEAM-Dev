/* Energia táblázat kijelölés klikkre */
$(document).ready(function () {

    /* Sorok kijelölése kattintásra */
    $(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    });
    
    /* Mee=FUTOERTEK_MEE2 popup esetén a Mennyiség (m3) mezőket élőben ki kell számolni */
    $(document).on('keyup blur', '.f_futoertek_mennyiseg', function(e) {
        sorszam = $(this).attr('data-sorszam');
        $('#fe_me'+sorszam).val( ezresek($(this).val().replace(/,/g, '.').replace(/ /g, '')/$('#fe'+sorszam).val().replace(/,/g, '.').replace(/ /g, ''), 2, ',', ' ') );
    });

});

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
                    szurkit();
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    if(response.kesz_id>0){
                        szamlaSorFrissit(baseName(location.href), response.kesz_id);
                    }
                    fulGombMeretez();
                    popupZar();
                }
            },
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });

    }

    return false;
}

/* Energiaterv véglegesítése */
function fenntartasterv_veglegesit(mehet){

    /* Először vizsgáljuk meg, hogy jóvá van-e hagyva minden az energia fül alatt */
    $.ajax({
        url : 'fenntartasi_terv.php',
        type: 'POST',
        data: {
            recid                                   : _get('id'),
            fenntartasterv_veglegesit_jovahagyva_ell: Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                /* Ha jóvá van hagyva az energia fül alatt a változtatás, akkor mehetünk tovább, csak akkor kérdezzük meg, hogy biztosan...? */
                if( response.rendben_van ) {

                    if(!mehet){
                        alert2('Biztosan véglegesíti a fenntartási tervet?', '<input type="button" class="gomb zold" value="Igen" onclick="fenntartasterv_veglegesit(\'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
                        return false;
                    }

                    $.ajax({
                        url : 'fenntartasi_terv.php',
                        type: 'POST',
                        data: {
                            recid                       : _get('id'),
                            fenntartasterv_veglegesit   : Date.now()
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
                        },
                        complete: function(xhr) {
                            if( xhr.status != 200 ) {
                                alert2(xhr.responseText, '', 'Hiba');
                            }
                        }
                    });

                }
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });

}

/* Energia fül alatt számokat szummázzuk */
function energia_szummaz(tabla, ful){

    /* Ha van ful paraméter, akkor adott fülhöz tartozó összegeket szummázzuk csak. (Összesen fül alatt használjuk) */
    var ful_class = '';
    var ful_ossz  = '';
    if(ful){
        ful_class = '.'+ful;
        ful_ossz  = '_'+ful;
    }

    for( i=1; i<13; i++ ){
        if( i<10 ){
            i = '0'+String(i);
        }
        ossz = 0;
        $('table.'+tabla+' .ft'+i+ful_class).each(function () {
            ossz += Number($(this).html().replace(/ /g, ''));
        });
        $('table.'+tabla+' #szum_ft'+i+ful_ossz).html( ezresek(ossz, '', '', ' ') );
    }
    // És még egy ráadás: Összesen fül alatt van egy hónapot összesítő oszlop is, ezt is összesíteni kell.
    ossz = 0;
    $('table.'+tabla+' .fto'+ful_class).each(function () {
        ossz += Number($(this).html().replace(/ /g, ''));
    });
    $('table.'+tabla+' #szum_fto'+ful_ossz).html( ezresek(ossz, '', '', ' ') );

    // Ha van összesen oszlop, akkor azt is szummázzuk
    if($('table.'+tabla+' .ftossz').length){
        ossz = 0;
        $('table.'+tabla+' .ftossz').each(function () {
            ossz += Number($(this).html().replace(/ /g, ''));
        });
        $('table.'+tabla+' #szum_ftossz').html( ezresek(ossz, '', '', ' ') );
    }

    // Ha van mennyiség összesen, akkor azt is szummázzuk
    if($('table.'+tabla+' .ertekossz').length){
        ossz = 0;
        $('table.'+tabla+' .ertekossz').each(function () {
            ossz += Number($(this).html().replace(/ /g, ''));
        });
        $('table.'+tabla+' #szum_ertek_o').html( ezresek(ossz, '', '', ' ') );
    }

}

// Kijelölt sor törlése
function sor_torol(ful, mehet){

    var sor = '';
    if(!$('.szamla_cont_tabla tr.kijelolve').length){
        alert2('Kérem jelölje ki a törölni kívánt sor!', '', 'Hiba');
        return;
    }else{
        sor = $('.szamla_cont_tabla tr.kijelolve').attr('id').replace('ft_ft_', '');
    }

    $.ajax({
        url : 'fenntartasi_terv.php',
        type: 'POST',
        data: {
            id          : _get('id'),
            ful         : ful,
            sor         : sor,
            mehet       : mehet,
            sor_torol   : Date.now()
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
                szamlaBetolt(_get('id'), baseName(location.href) + '.php', ful);
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Változtatások jóváhagyása
function valtozas_jovahagyas(){
    $.ajax({
        url : 'fenntartasi_terv.php',
        type: 'POST',
        data: {
            id                  : _get('id'),
            valtozas_jovahagyas : Date.now()
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
                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'energia');
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Kitöltés popup-okra az összesítő működése */
$(document).on('input', 'input.popup_mennyisegek', function(e) {
    popup_tabla_osszesit();
});
function popup_tabla_osszesit(){
    var popup_osszesen    = 0;
    var popup_osszesen_m3 = 0;
    $( 'input.popup_mennyisegek').each(function () {
        ertek = $(this).val().replace(/ /g, '').replace(',', '.');
        popup_osszesen += Number(ertek);
    });
    $('#popup_tabla_osszesen_sor').html(ezresek(popup_osszesen, 0, ',', ' '));
    
    /* Mennyiség m3 sorok ha vannak, akkor ott is összesítsünk 
     * Azért nem a Mennyiség m3 inputból olvassuk ki a már kiszámolt értéket, mert lehet hogy az még nem számolódott ki. Konkrétan emiatt tényleg rosszul számolt. */
    $( 'input.popup_mennyisegek').each(function () {
        ertek   = $(this).val().replace(/ /g, '').replace(',', '.');
        f_ertek = $('#'+$(this).attr('id').replace('em', 'fe')).val().replace(/ /g, '').replace(',', '.');
        popup_osszesen_m3 += Number(ertek/f_ertek);
    });
    $('#popup_tabla_osszesen_sor_m3').html(ezresek(popup_osszesen_m3, 2, ',', ' '));
    
}