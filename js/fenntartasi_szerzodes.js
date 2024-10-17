// Pénznemet kiválasztva fut le: cél, hogy a devizára kerüljön rá egy readonly, ha HUF-os, más különben az űrlapo postolásakor szólni fog, hogy nincs kitöltve
function huf_vagy_deviza() {
    if( $('#kcs_21_autocomp').val()=='HUF' ) {
        $('#dev_net' ).val('0,00').attr('readonly', 'readonly');
        $('#arfolyam').val('0,00').attr('readonly', 'readonly').removeClass('csillag');
        $('#netto'   ).removeAttr('readonly');
    } else {
        $('#dev_net' ).removeAttr('readonly');
        $('#arfolyam').removeAttr('readonly').addClass('csillag');
        $('#netto'   ).attr('readonly', 'readonly');
    }
}

// Nettó érték kiszámítása
$(document).on('input', '#dev_net, #arfolyam', function(){
    var dev_net  = Number($('#dev_net').val().replace(/ /g, '').replace(/,/g, '.'));
    var arfolyam = Number($('#arfolyam').val().replace(/ /g, '').replace(/,/g, '.'));
    $('#netto').val( ezresek(Math.round(dev_net*arfolyam), 0, ',', ' ', '-nullát is kiír-') );
});

// Nettó érték kiszámítása
$(document).on('focus', '#kolt_vis_autocomp', function(){
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            kolt_vis_mod: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
        },
        complete: function (xhr) {
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
});

function fenntartasi_szerzodes_feltolt() {
    csoportMuvelet('fenntartasi_szerzodes_feltolt');
}

// Adott szerződés áttevés projektszerződésnek
function projekt_szerzodes(mehet) {
    if( !mehet ) {
        alert2('Biztosan áthelyezi ezt a szerződést projekt szerződésnek?', '<input type="button" class="gomb zold enter" value="Igen" onclick="projekt_szerzodes(\'mehet\'); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }
    
    $.ajax({
        url : 'fenntartasi_szerzodes.php',
        type: 'POST',
        data: {
            andoc_id         : _get('andoc_id'),
            projekt_szerzodes: Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            formModositva = false;
            location.href = 'fenntartasi_szerzodes.php';
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
    
}