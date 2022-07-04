// Számla áthelyezése
function szamla_athelyez(tipus, mehet) {
    
    var recidk = [];
    $('table.szamla_cont_tabla tr.kijelolve').each(function () {
        recidk.push($(this).attr('data-recid'));
    });

    if( !recidk.length ) {
        alert2('Kérem előbb jelöljön ki egy sort a táblázatból!', '', 'Hiba');
        return false;
    }
    
    if( !mehet ) {
        var tipus_kerdes = '';
        switch( tipus ) {
            case 'anyag'        : tipus_kerdes = 'az anyag';       break;
            case 'energia'      : tipus_kerdes = 'az energia';     break;
            case 'fenntartas'   : tipus_kerdes = 'a fenntartási';  break;
            case 'megallapodas' : tipus_kerdes = 'a megállapodás'; break;
            case 'projekt'      : tipus_kerdes = 'a projekt';      break;
        }
        var tobbes_szam = '';
        if( recidk.length>1 ) {
            tobbes_szam = 'ka';
        }
        alert2('Biztosan áthelyezi a kijelölt számlá'+tobbes_szam+'t '+tipus_kerdes+' számlákhoz?', '<input type="button" value="Igen" class="gomb zold" onclick="szamla_athelyez(\''+tipus+'\', \'-mehet-\')"> <input type="button" value="Nem" class="gomb piros" onclick="alert2()">');
        return false;
    } else {
        
        var recid = $('table.szamla_cont_tabla tr.kijelolve').attr('data-recid');
        $.ajax({
            url : PHP_SELF,
            type: 'POST',
            data: {
                recidk          : JSON.stringify(recidk),
                tipus           : tipus,
                szamla_athelyez : Date.now()
            },
            success: function (response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    } else {
                        szamla_sor_eltuntet();
                        alert2();
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
}

// Nem műszaki számla
function nem_muszaki_szamla(mehet) {
    
    if( !$('table.szamla_cont_tabla tr.kijelolve').length ) {
        alert2('Kérem előbb jelöljön ki egy sort a táblázatból!', '', 'Hiba');
        return false;
    }
    
    if( !mehet ) {
        alert2('Biztosan elutasítja a kijelölt számlát?', '<input type="button" value="Igen" class="gomb zold" onclick="nem_muszaki_szamla(\'-mehet-\')"> <input type="button" value="Nem" class="gomb piros" onclick="alert2()">');
        return false;
    } else {
    
        var recid = $('table.szamla_cont_tabla tr.kijelolve').attr('data-recid');
        $.ajax({
            url : PHP_SELF,
            type: 'POST',
            data: {
                recid               : recid,
                nem_muszaki_szamla  : Date.now()
            },
            success: function (response) {
                if (response) {
                    response = JSON.parse(response);
                    if (response.hiba) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    } else {
                        szamla_sor_eltuntet();
                        alert2();
                    }
                }
            },
            complete: function (xhr) {
                if (xhr.status != 200) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    }
}

function szamla_sor_eltuntet() {
    $('table.szamla_cont_tabla tr.kijelolve').fadeOut('fast', function(){
        $(this).remove();
        if( $('table.szamla_cont_tabla tbody tr').length ) {
            $('table.szamla_cont_tabla tbody tr:first-of-type').addClass('kijelolve');
        } else {
            $('#szamlaBetolt').html('<h1 style="margin-top: 4rem">Jelenleg nem található egyetlen Andoc-ból küldött számla sem!</h1>');
        }
    });
}