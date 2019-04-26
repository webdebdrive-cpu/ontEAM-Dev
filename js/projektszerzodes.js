// Projekt hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény.
function pr_hozzaad_gomb_hozzaad_vagy_torol (){
    
    // Illetve megnézzük, hogy van-e kapcsolt számla tétel, mert ha igen, akkor csatolt Projektet törölni már nem lehet
    $.ajax({
        url : 'projektszerzodes.php',
        type: 'POST',
        data: {
            recid               : _get('id'),
            uj                  : _get('uj'),
            van_kapcsolt_szamla : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                }else{
                
                    if( $('#pr_szam').val() ){
                        $('#pr_szam').val('');
                        // Törléskor csak akkor adunk hozzá 'mod' classt, ha amúgy volt eredetileg értéke
                        if( $('#pr_szam').attr('value') ){
                            $('#pr_szam').addClass('mod');
                            $('#let_fel_autocomp').addClass('mod');
                        }else{
                            $('#pr_szam').removeClass('mod');
                            $('#let_fel_autocomp').removeClass('mod');
                        }
                        $('#let_fel_autocomp').val('');
                        $('#let_fel').val('');
                        
                        $('#projekt_neve').html('');
                        formModositva = true;
                        $('#pr_szam_gomb').val('Projekt hozzáadása').removeClass('piros');
                        $('#mehet_gomb').addClass('gombb_alert');
                        szurkit_zar('mehet_megsem_mutat');
                        popupZar();
                        popupRejt();
                    }else{
                        csoportMuvelet('projektszerzodes_projekt_valaszt');
                    }
                    
                }
                
            }
        }
    });
    
}

// Szerződés összeg aktuális értékének lekérdezése
function get_szerz_ossz(){
    if( $('#szerz_ossz').length ){
        return $('#szerz_ossz').val().replace(/ /g, '');
    }else{
        return $('#szerz_ossz_db').val().replace(/ /g, '');
    }
}

// Új feltöltéskor és szerkesztéskor is a tételek kezelése popup megnyitása előtt ellenőrzünk pár dolgot.
function tetelek_kezelese_popup_nyit() {
    if (!get_szerz_ossz() || get_szerz_ossz() == 0) {
        alert2('Tételek kezelése előtt adja meg a szerződött összeget!', '', 'Hiba');
        $('#szerz_ossz').addClass('hibas_ide_focus');
    } else if (get_szerz_ossz() < 0) {
        alert2('A szerződött összeg nem lehet 0 vagy negatív szám!', '', 'Hiba');
        $('#szerz_ossz').addClass('hibas_ide_focus');
    } else {
        csoportMuvelet('projektszerzodes_tetelek_kezelese');
    }
}

// Jótállás fül alatti dokumentum törlés
function jotallas_dok_torol ( recid, mehet ) {
    
    if( !mehet ) {
        
        alert2('Biztosan törli '+$('#dok_nevelos_nev_'+recid).html()+' dokumentumot?', '<input type="button" class="gomb zold" value="Igen" onclick="jotallas_dok_torol(\''+recid+'\', \'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
        
    } else {
        
        $.ajax({
            url : 'projektszerzodes.php',
            type: 'POST',
            data: {
                recid               : recid,
                jotallas_dok_torol  : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb,response.title);
                    } else {
                        szamlaBetolt(_get('id'), 'projektszerzodes.php', 'jotallas');
                    }
                }
            }
        });
    }
}

// Pénznem szerkesztésekor a kapcsolódó mezők disabled-ezése
$(document).on('input, blur', '#kcs_21_autocomp', function(e) {
    // Azért van egy settimeoutban, mert a mezőt elhagyva még az autocomplete feltölti adattal. De az elhagyás pillanatában még üres.
    setTimeout(function(){
        if( $('#kcs_21_autocomp').val() ){
            $('#arfolyam')  .removeAttr('disabled').removeAttr('readonly').focus();
            $('#valtva')    .removeAttr('disabled').removeAttr('readonly');
            $('#dev_net')   .removeAttr('disabled').removeAttr('readonly').addClass('csillag');
        }else{
            $('#arfolyam')  .val('0,0000')      .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod');
            $('#valtva')    .val('0000.00.00')  .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod');
            $('#dev_net')   .val('0,00')        .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
        }
        tabindex_beallit();
    }, 200);
});

// Építési engedély pipa lekezelése
$(document).on('click', '#epitesi_eng', function(e) {
    if( $('#epitesi_eng').prop('checked') ){
        $('#epitesi').removeAttr('disabled').removeAttr('readonly').addClass('csillag').focus();
    }else{
        $('#epitesi').val('').attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
    }
    tabindex_beallit();
});

