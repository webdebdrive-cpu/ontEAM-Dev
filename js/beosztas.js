// Kezdjük azzal, hogy a tálbázatot rendbe tesszük 
// (főleg sok soros táblázatnál, alacsony magasságú képernyőnél szükséges ez).
$(document).ready(function () {
    fixTable();
});
$(document).on('click', '.szerk', function(e) {
    
    if( !$('#muszak').val() ) {
        alert2('Kérem előbb válasszon műszakot!', '', 'Hiba');
        $('#muszak').addClass('hibas_ide_focus');
        return false;
    }
    
    // Adunk egy mod-ot is rá, így csak azokat fogjuk átadni az SQL-nek,amiben tényleg volt változás
    $(this).addClass('mod');
    
    var muszak_ertekek = $('#muszak').val().split('|sep|');
    
    if( muszak_ertekek[0]=='|del|' ) {
        $(this).html('');
        $(this).removeAttr('style');
        $(this).removeAttr('data-muszak_id');
        $(this).removeAttr('data-jel');
        $(this).removeAttr('data-szin');
        $(this).removeAttr('data-ora');
        $(this).removeAttr('data-tol');
        $(this).removeAttr('data-ig');
        
        // Innen fogjuk tudni, mit kell törölni adatbázisból:
        $(this).attr('data-jel',  muszak_ertekek[0]);
    } else {
        $(this).css({'background-color':'#'+muszak_ertekek[2]});
        $(this).html(muszak_ertekek[1]);
        $(this).attr('data-muszak_id',  muszak_ertekek[0]);
        $(this).attr('data-jel',        muszak_ertekek[1]);
        $(this).attr('data-szin',       muszak_ertekek[2]);
        $(this).attr('data-ora',        muszak_ertekek[3]);
        $(this).attr('data-tol',        muszak_ertekek[4]);
        $(this).attr('data-ig',         muszak_ertekek[5]);
    }
    $('#mehet_gomb, #megsem_gomb').removeAttr('disabled');
    $('#mehet_gomb').addClass('gombb_alert');
    
});

// Beosztás tálblázatban elmentett műszakok tárolása adatbázisba
function beosztas_tabla_ment() {

    var adatok_tomb = {};
    var i = 0;
    $('td.szerk.mod').each(function () {
        var tomb = {};
        if( $(this).attr('data-jel') ) {
            tomb.recid      = _get('id');
            tomb.muszak_id  = $(this).attr('data-muszak_id');
            tomb.nev_id     = $(this).attr('data-nev_id');
            tomb.datum      = $(this).attr('data-datum');
            tomb.jel        = $(this).attr('data-jel');
            tomb.szin       = $(this).attr('data-szin');
            tomb.ora        = $(this).attr('data-ora');
            tomb.tol        = $(this).attr('data-tol');
            tomb.ig         = $(this).attr('data-ig');
            adatok_tomb[i] = tomb;
            i++;
        }
    });
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            adatok_tomb         : JSON.stringify(adatok_tomb),
            beosztas_tabla_ment : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                $('#muszak').val('');
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                if (response.siker) {
                    szamlaBetolt(_get('id'), PHP_SELF, 'modosit');
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

// Mobil sorrend popup mentése
function mobil_sorrend_ment() {
    var sorrend = {};
    i = 0;
    $('ul.droptrue li').each(function () {
        sorrend[i] = $(this).attr('data-recid');
        i++;
    });
    
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            sorrend             : JSON.stringify(sorrend),
            mobil_sorrend_ment  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                $('#muszak').val('');
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
                if (response.siker) {
                    szamlaBetolt(_get('id'), PHP_SELF, 'modosit');
                    popupZar();
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

// Ha egy foglalt napra kattintunk, akkor az illető foglalt műszakját megmutatjuk egy üzenetben
function get_illeto_muszakja( naptar_recid ) {
    $.ajax({
        url : PHP_SELF,
        type: 'POST',
        data: {
            uj_telephely_id     : $('#telephely_id').val(),
            naptar_recid        : naptar_recid,
            get_illeto_muszakja : Date.now()
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
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}