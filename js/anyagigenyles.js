/* Anyagigénylésnél a mennyiség mező kitöltése szálloda + anyag ismerete alapján */
$(document).on('focus', '.f_anyagig_menny_feltolt', function(e) {
    if(!$(this).val()){
        anyagig_id = '#th'+$('#telephely_id').val()+'a'+$('#nev').val();
        $('#igmen').val( $(anyagig_id).html() );
    }
});

$(document).on('click', '.f_anyagigenyles_mezok_kitolt', function(e) {

    recid_egyedi_azon   = $(this).attr('data-recid_egyedi_azon');
    telephely_id        = $(this).attr('data-telephely_id');
    cikk_id             = $(this).attr('data-cikk_id');
    cikk_id2            = $(this).attr('data-cikk_id2');
    nev_autocompl       = $('#cikk_nev_'+recid_egyedi_azon).html();
    igmen               = $('#th'+telephely_id+'a'+cikk_id2).html();
    mee                 = $('#th'+telephely_id+'a'+cikk_id2+'_mee').html();
    igleir              = $('#th'+telephely_id+'a'+cikk_id2+'_igleir').html().replace(/<br\s*[\/]?>/gi, "\n");

    $('#telephely_id')  .val(telephely_id).addClass('mod');
    $('#nev_autocomp')  .val(nev_autocompl);
    $('#nev_autocomp')  .attr('data-origin-val', nev_autocompl).addClass('mod');
    $('#nev')           .val(cikk_id).addClass('mod');
    $('#igmen')         .val(igmen).addClass('mod');
    $('#igleir')        .val(igleir).addClass('mod');
    $('.insertMee')     .html(mee);

    $('#mehet_gomb').removeAttr('disabled').addClass('gombb_alert');
    $('#megsem_gomb').removeAttr('disabled');

});
$(document).on('click', '.f_anyag_rendelkezesre_all', function(e) {

    telephely_id    = $(this).attr('data-telephely_id');
    cikkid_origi    = $(this).attr('data-cikkid_origi');
    cikk_id         = $(this).attr('data-cikk_id');
    mehet           = $(this).attr('data-mehet');
    azon            = telephely_id+'_'+cikk_id;
    azon2           = 'th'+telephely_id+'a'+cikk_id;
    
    if( !mehet ) {
        
        var kerdes  = 'Szálloda: '+$('#telephely_'+azon).html();
            kerdes += '<br>Cikk: '+$('#cikk_id_'+azon).html()+' '+$('#cikk_nev_'+azon).html();
            kerdes += '<br>Munkalapon igénylésre vár: '+$('#'+azon2).html()+' '+$('#'+azon2+'_mee').html();
            kerdes += '<br>Ha folytatja, az igénylésre váró mennyiség státusza át lesz állítva "anyag rendelkezésre áll" státuszba.';
            kerdes += '<br>Folytatja?';
        var gomb = '<input type="button" class="gomb zold" value="Igen" onclick="$(\'#gomb_anyag_r_all_'+azon+'\').attr(\'data-mehet\', \'1\').click(); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2();">';
        alert2(kerdes, gomb);
        return false;
    }

    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            telephely_id                : telephely_id,
            cikk_id                     : cikkid_origi,
            f_anyag_rendelkezesre_all   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                } else {
                    $('#sor_'+azon).fadeOut();
                }
                
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
});

// Anyagigénylés már igényelve gomb
function anyagigenyles_mar_igenyelve(cikk_id, telephely_id, ig_alatt, anyag_var, ig_foly, csak_kerdezunk) {
    $.ajax({
        url : 'anyagigenyles.php',
        type: 'POST',
        data: {
            cikk_id                     : cikk_id,
            telephely_id                : telephely_id,
            ig_alatt                    : ig_alatt,
            anyag_var                   : anyag_var,
            ig_foly                     : ig_foly,
            csak_kerdezunk              : csak_kerdezunk,
            anyagigenyles_mar_igenyelve : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if(response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
            szurkit();
            location.reload();
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}