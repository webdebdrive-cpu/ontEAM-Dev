// Visszaadva pipa lekezelése.
function pl_szamla_visszaadva (){
    if( !$('#visszaadva').prop('checked') ){
        $('#visszaadva_dt').val('').blur();
        $('#visszaadva_dt').removeClass('csillag');
    }else{
        $('#visszaadva_dt').addClass('csillag');
    }
}

// Projektszerződés hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény.
function prsz_hozzaad_gomb_hozzaad_vagy_torol() {
    
    // Illetve megnézzük, hogy van-e kapcsolt szerződés tétel, mert ha igen, akkor csatolt Projektet törölni már nem lehet
    $.ajax({
        url : 'projektszamla.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            uj                      : _get('uj'),
            van_kapcsolt_szerzodes  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                }else{
    
                    if( $('#sz_szam').val() ){

                        // Törléskor csak akkor adunk hozzá 'mod' classt, ha amúgy volt eredetileg értéke
                        $('#sz_szam').val('');
                        if( $('#sz_szam').attr('value') ){
                            $('#sz_szam').addClass('mod');
                        }else{
                            $('#sz_szam').removeClass('mod');
                        }
                        $('#pr_szam').val('');
                        if( $('#pr_szam').attr('value') ){
                            $('#pr_szam').addClass('mod');
                        }else{
                            $('#pr_szam').removeClass('mod');
                        }
                        $('#let_fel_autocomp').val('');
                        $('#let_fel').val('');
                        if( $('#let_fel_autocomp').attr('value') ){
                            $('#let_fel_autocomp').addClass('mod');
                        }else{
                            $('#let_fel_autocomp').removeClass('mod');
                        }
                        $('#kolt_vis_autocomp').val('');
                        $('#kolt_vis').val('');
                        if( $('#kolt_vis_autocomp').attr('value') ){
                            $('#kolt_vis_autocomp').addClass('mod');
                        }else{
                            $('#kolt_vis_autocomp').removeClass('mod');
                        }

                        $('#szerzodes_neve').html('');
                        $('#projekt_neve').html('');

                        formModositva = true;
                        $('#sz_szam_gomb').val('Szerződés hozzáadása').removeClass('piros');
                        $('#mehet_gomb').addClass('gombb_alert');
                        szurkit_zar('mehet_megsem_mutat');
                        popupZar();
                        popupRejt();

                    }else{

                        csoportMuvelet('projektszamla_szerzodes_valaszt');

                    }
                }
            }
        }
    });
}

// Adatlapon található nettó mezőből számol ki bruttó értéket
// FONTOS, hogy ne onclick-es verzió legyen, mert az onclick tulajdonyágot levessük JS-el, ha elszürkített a mező. És ezek a mezők oda-vissza lehetnek aktívak/inaktívak a deviza alapján.
$(document).on('blur', '#netto', function(e) {
    if( !$('#brutto').val() || $('#brutto').val()=='0' ){
        $('#brutto').val( ezresek($('#netto').val().replace(/ /g, '')*((Number(AFA)+100)/100), 0, '', ' ') );
    }
});

// Ha a bruttósított nettó és a valódi bruttó között 2 Ft-nál nagyobb az eltérés, akkor hibát dobunk. 
function pr_szamla_netto_br_ell() {
    var brutto          = Number($('#brutto').val().replace(/ /g, ''));
    var netto_afaval    = Number($('#netto').val().replace(/ /g, '')*((Number(AFA)+100)/100));
    
    if( Math.abs( brutto - netto_afaval ) > 2 ){
        return 'A Bruttó érték ('+$('#brutto').val()+') nem egyenlő a Nettó érték ('+$('#netto').val()+') * '+AFA+'% ÁFÁ-val.<br>Folytatja?';
    }
}

// Adatlapon található devizat nettó mezőből számol ki deviza bruttó értéket
// FONTOS, hogy ne onclick-es verzió legyen, mert az onclick tulajdonyágot levessük JS-el, ha elszürkített a mező. És ezek a mezők oda-vissza lehetnek aktívak/inaktívak a deviza alapján.
$(document).on('blur', '#dev_net', function(e) {
    if( !$('#dev_br').val() || $('#dev_br').val()=='0,00' ){
        $('#dev_br').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*((Number(AFA)+100)/100), 2, ',', ' ') );
    }
});

// Adatlapon található deviza árfolyam és deviza nettó mezőből számol ki nettó értéket
$(document).on('blur', '#dev_br', function(e) {
    if( !$('#netto').val() || $('#netto').val()=='0' ){
        $('#netto').val( ezresek($('#dev_net').val().replace(/ /g, '').replace(',', '.')*($('#arfolyam').val().replace(/ /g, '').replace(',', '.')), 2, ',', ' ') );
    }
});

// Ha a bruttósított nettó és a valódi bruttó között 2 Ft-nál nagyobb az eltérés, akkor hibát dobunk. 
function pr_szamla_deviza_netto_br_ell() {
    var brutto          = Number($('#dev_br') .val().replace(/ /g, '').replace(',', '.'));
    var netto_afaval    = Number($('#dev_net').val().replace(/ /g, '').replace(',', '.')*((Number(AFA)+100)/100));
    
    if( Math.abs( brutto - netto_afaval ) > 1 ){
        return 'A Deviza bruttó érték ('+$('#dev_br').val()+') nem egyenlő a Deviza nettó érték ('+$('#dev_net').val()+') * '+AFA+'% ÁFÁ-val.<br>Folytatja?';
    }
}

// Pénznem szerkesztésekor a kapcsolódó mezők disabled-ezése
$(document).on('input, blur', '#kcs_21_autocomp', function(e) {
    // Azért van egy settimeoutban, mert a mezőt elhagyva még az autocomplete feltölti adattal. De az elhagyás pillanatában még üres.
    setTimeout(function(){
        if ($('#kcs_21_autocomp').val() && $('#kcs_21_autocomp').val() != 'HUF') {
            $('#arfolyam')  .removeAttr('disabled').removeAttr('readonly').focus();
            $('#valtva')    .removeAttr('disabled').removeAttr('readonly');
            $('#dev_net')   .removeAttr('disabled').removeAttr('readonly').addClass('csillag');
            $('#dev_br')    .removeAttr('disabled').removeAttr('readonly').addClass('csillag');
        }else{
            $('#arfolyam')  .val('0,0000')      .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod');
            $('#valtva')    .val('\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0').attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod');
            $('#dev_net')   .val('0,00')        .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
            $('#dev_br')    .val('0,00')        .attr('disabled', 'disabled').attr('readonly', 'readonly').removeClass('mod').removeClass('csillag');
        }
        tabindex_beallit();
    }, 200);
});