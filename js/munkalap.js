/* Átadás műszaki vezetőnek */
function munkalapMuszakiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                : _get('id'),
                munkalapMuszakiVezeto: new Date().getTime()
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

/* Átadás területi felelősnek */
function munkalapTeruletiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                 : _get('id'),
                munkalapTeruletiVezeto: new Date().getTime()
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

/* Munkalap átadása adminsiztrátornak */
function munkalapAdminisztrator() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                munkalapAdminisztrator  : new Date().getTime()
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

/* Átadás műszaki vezető helyettesnek */
function munkalapMuszakiVezetoHelyettes() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                           : _get('id'),
                munkalapMuszakiVezetoHelyettes  : new Date().getTime()
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

/* Munkalap átadása műszaki igazgatónak */
function munkalapMuszakiIgazgato() {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                munkalapMuszakiIgazgato : new Date().getTime()
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

/* Munkalap átadása mobilnak */
function munkalapMobil( that ) {

    if(balnav_kijelol_ell()){

        /* Hozzárendelt csoport törlése */
        var munkMobilKivetel = $(that).find('input[name="munkMobilKivetel[]"]');

        /* Hozzárendelt személyzet törlése */
        var munkHozzMegszuntet = $(that).find('input[name="munkHozzMegszuntet[]"]');

        /* Törzsadat csatolása a munkalaphoz */
        var addTorzsID     = $(that).find('input[name="addTorzsID[]"]'),
            addTorzsMunkID = $(that).find('input[name="addTorzsMunkID[]"]');

        var idk = {};
        var i   = 1;

        var sorok = $('#hozzBeosztasNelkul table tr.kijSor');
        if( sorok.length ) {
            sorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }

        var sorok = $('#szemRendelt table tr.kijSor');
        if( sorok.length ) {
            sorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        }

        if( objLength(idk) ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    idk                 : JSON.stringify(idk),
                    munkMobilKivetel    : munkMobilKivetel.serialize(),
                    munkHozzMegszuntet  : munkHozzMegszuntet.serialize(),
                    addTorzsID          : addTorzsID.serialize(),
                    addTorzsMunkID      : addTorzsMunkID.serialize(),
                    munkalapMobil       : new Date().getTime()
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
                        location.reload();
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

    return false;
}

/* Munkalap átadása mobilnak sima, tehát csak státuszt léptetünk */
function munkalapMobil_sima() {
    
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid               : _get('id'),
            munkalapMobil_sima  : new Date().getTime()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

/* Munka átadása Diszpécser központ vezetőnek */
function munkalapDiszpKozp() {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid               : _get('id'),
                munkalapDiszpKozp   : new Date().getTime()
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

/* Munkalap készre jelentés */
function munkalapKeszre(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapKeszre: new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap Rögzítése */
function munkalapRogzit(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapRogzit: new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap külsős jóváhagyása */
function munkalapKulsosJovahagy(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();
        szurkit();
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                csak_kerdezunk          : csak_kerdezunk,
                munkalapKulsosJovahagy  : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    szurkit_zar();
                    
                    if( response.hiba ) {
                        szamlaBetolt( _get('id'), 'munkalap.php', 'modosit' );
                        setTimeout(function(){ alert2(response.hiba, response.gomb, response.title); }, 1000);
                        return false;
                    }

                    /*
                    Eredetileg a 2. jóváhagyásakor a lista első elemére kattintottunk. De miért?
                    Kivettük, mert Tibi jelezte, hogy zavaró, hogy elugrunk az adatlapról, helyette mindig szamlaBetolt(...). 2018.11.16
                    
                    if(response.masodik_jovahagyo=='1'){
                        $('#szamlaList li:first-of-type').click();
                    }else{
                        szamlaBetolt( _get('id'), 'munkalap.php', 'modosit' );
                    }*/
                    szamlaBetolt( _get('id'), 'munkalap.php', 'modosit' );
                    
                    
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

/* Munkalap Igazolása */
function munkalapIgazol(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid         : _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapIgazol: new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }
                    
                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap reklamáció elfogadása */
function munkalapReklamacioElfogad(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                       : _get('id'),
                csak_kerdezunk              : csak_kerdezunk,
                munkalapReklamacioElfogad   : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap Külsős munka megrendelve */
function munkalapArajanlatNyomtatva(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                       : _get('id'),
                csak_kerdezunk              : csak_kerdezunk,
                munkalapArajanlatNyomtatva  : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap Külsős munka megrendelve */
function munkalapKulsosMegrendelve(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                       : _get('id'),
                csak_kerdezunk              : csak_kerdezunk,
                munkalapKulsosMegrendelve   : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap lezárása */
function munkalapLezaras(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid           : _get('id'),
                csak_kerdezunk  : csak_kerdezunk,
                munkalapLezaras : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
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

/* Munkalap átadása telepített csoportnak */
function munkalapTelepitett(csak_kerdezunk) {

    if (balnav_kijelol_ell()) {

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid             : _get('id'),
                csak_kerdezunk    : csak_kerdezunk,
                munkalapTelepitett: new Date().getTime()
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

/* Munkalap felvétele */
function munkalapFelvesz( recid ) {

    if(balnav_kijelol_ell()){

        if( recid ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    recid           : recid,
                    munkalapFelvesz : new Date().getTime()
                },
                success: function(response) {
                    if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                    if(response) {
                        response = JSON.parse(response);
                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            return false;
                        }

                        szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
                        alert2();
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
}


/* Munkalaphoz adás - Megszűnt 2017.09.06 */
/*
function munkalaphozAdas(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        idk = {};

        szamlaSorok = $('#szamlaList li.szamla_sor.kijelolve');

        if( szamlaSorok.length ) {
            var i = 1;
            szamlaSorok.each(function() {
                var id = $(this).attr('data-recid');

                idk[i] = id;
                i++;
            });
        } else {
            var recid = $('#szamlaBetolt').attr('data-recid');

            if( recid ) {
                idk[1] = recid;
            }
        }

        var munkalapID = $('#munkalapID').val();
        if(csak_kerdezunk){
            munkalapID = '-1';
        }

        if( munkalapID ) {
            if( objLength(idk) ) {
                $.ajax({
                    url : 'munkalap.php',
                    type: 'POST',
                    data: {
                        idk             : JSON.stringify(idk),
                        munkalapID      : munkalapID,
                        csak_kerdezunk  : csak_kerdezunk,
                        munkalaphozAdas : new Date().getTime()
                    },
                    success: function(response) {
                        if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, '', 'Hiba');
                                return false;
                            }

                            if(csak_kerdezunk) {
                                csoportMuvelet('munkalaphoz_adas');
                            } else {
                                szurkit();
                                location.href = 'munkalap.php?id=' + response.recid + '&modosit';

                                kijelolesEltavolit();
                                popupZar();
                            }
                        }
                    }
                });
            } else {
                alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
            }
        } else {
            alert2('Kérem válasszon egy munkalapot!', '', 'Hiba');
        }
    }
    return false;
}
*/

/* Feladat törlése */
function munkFeladatTorol( recid, elem_id, mehet ) {
    
    $('.feladat_sor_'+recid).addClass('torles_elott');
    
    if(!mehet){
        alert2('Biztosan törli a következő feladatot?<br><span class="f14">Elvégzett feladat:<br><span class="kek">'+$('#elv_feladat_nev_'+recid).html()+'</span><br>Leírás:<br><span class="kek">'+$('#elv_feladat_memo_'+recid).html()+'</span></span>', '<input type="button" class="gomb zold" value="Igen" onclick="munkFeladatTorol( \''+recid+'\', \''+elem_id+'\', \'mehet\' )"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\'.torles_elott\').removeClass(\'torles_elott\');">');
        return false;
    }
    
    if( recid ) {
        $.ajax({
         	url  : 'munkalap.php',
         	type : 'POST',
            async: false,
         	data: {
                recid           : recid,
                munkFeladatTorol: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
         		}
         	},
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    } else {
        $('#'+elem_id).parents('tr').remove();
    }
}


/* Munkalap anyagvételezésnél anyagok törlése */
function munkAnyagTorol( recid, elem_id, mehet ) {
    
    $('.anyag_sor_'+recid).addClass('torles_elott');
    
    if(!mehet){
        alert2('Biztosan törli ezt az anyagot?<br><span class="f14 kek">'+$('#anyag_cikkid_'+recid).html()+' '+$('#anyag_nev_'+recid).html()+'</span>', '<input type="button" class="gomb zold" value="Igen" onclick="munkAnyagTorol( '+recid+', \''+elem_id+'\', \'mehet\' ); alert2();"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\'.torles_elott\').removeClass(\'torles_elott\');">');
        return false;
    }
    
    if( recid ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
            async: false,
         	data: {
                recid           : recid,
                munkAnyagTorol  : new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
         		}
         	},
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    } else {
        $('#'+elem_id).parents('tr').remove();
    }
}

/* Munkaidő törlése */
function munkMunkaidoTorol( recid, elem_id, mehet ) {
    
    $('.munkaido_sor_'+recid).addClass('torles_elott');
    
    if(!mehet){
        alert2('Biztosan törli ezt a munkaidőráfordítást?<br><span class="f14 kek">'+$('#munkaido_nev_'+recid).html()+' '+$('#munkaido_ido_'+recid).html()+'</span>', '<input type="button" class="gomb zold" value="Igen" onclick="munkMunkaidoTorol( \''+recid+'\', \''+elem_id+'\', \'mehet\' )"><input type="button" class="gomb piros" value="Nem" onclick="alert2(); $(\'.torles_elott\').removeClass(\'torles_elott\');">');
        return false;
    }
    
    if( recid ) {
        $.ajax({
         	url  : 'munkalap.php',
         	type : 'POST',
            async: false,
         	data : {
                recid            : recid,
                munkMunkaidoTorol: new Date().getTime()
            },
         	success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
         		}
         	},
            complete: function(xhr) {
                if( xhr.status != 200 ) {
                    alert2(xhr.responseText, '', 'Hiba');
                }
            }
        });
    } else {
        $('#'+elem_id).parents('tr').remove();
    }
}

/* Munkalap vissza nyitottnak */
function munkalapVisszaNyitott(recid, mehet){
    if(!mehet){
        alert2( 'Munkalapszám: '+$('#ikt_szam_hibauzibe').html()+'<br>Szálloda: '+$('#telephely_hibauzibe').html()+'<br>Biztosan visszateszi a nyitottak közé?', '<input type="button" class="gomb zold" onclick="munkalapVisszaNyitott(\''+recid+'\', \'mehet\')" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">');
        return false;
    }
    
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid                   : recid,
            munkalapVisszaNyitott   : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            response = JSON.parse(response);
            if( response.hiba ) {
                alert2(response.hiba, response.gomb, response.title);
                return false;
            }
            
            szamlaBetolt(recid, 'munkalap.php', 'modosit');
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Munkalap visszavétel */
function munkalapVisszavetel(recid, mehet){
    if(!mehet){
        alert2( 'Munkalapszám: '+$('#ikt_szam_hibauzibe').html()+'<br>Szálloda: '+$('#telephely_hibauzibe').html()+'<br>Biztos, hogy visszaveszi a munkalapot?', '<input type="button" class="gomb zold" onclick="munkalapVisszavetel(\''+recid+'\', \'mehet\')" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">');
        return false;
    }
    
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid               : recid,
            munkalapVisszavetel : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if(response){
                response = JSON.parse(response);
                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
            szamlaBetolt(recid, 'munkalap.php', 'modosit');
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Munkalap várakoztatás vége */
function munkalapVarakoztatVege(){
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            munkalapVarakoztatVege  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if(response){
                response = JSON.parse(response);
                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
            szamlaBetolt(_get('id'), 'munkalap.php', 'modosit');
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

/* Munkalap személyzet előkészítése  */
function munkalapElokeszit(szemelyzet_id, mehet) {

    if (!mehet) {
        alert2('Biztosan előkészíti a munkalapot?', '<input type="button" class="gomb zold" value="Igen" onclick="munkalapElokeszit('+szemelyzet_id+', \'mehet\')"><input type="button" class="gomb piros" value="Nem" onclick="alert2()">');
        return false;
    }

    if (balnav_kijelol_ell()) {

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid               : _get('id'),
                szemelyzet_id       : szemelyzet_id,
                munkalapElokeszit   : new Date().getTime()
            },
            success: function(response) {
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                        setTimeout(function(){ alert2(response.hiba, response.gomb, response.title); }, 1000);
                        
                        return false;
                    }

                    popupZar();
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

    return false;
}

/* Munkalap vissza előkészítésre */
function munkalapVisszaElokeszit() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                munkalapVisszaElokeszit : new Date().getTime()
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

/* Külső kivitelező kiválasztásakor a mailcímét másoljuk a mailküldés címzett mezőjébe */
function get_szallito_mailcim(that){
    if( that[0].tabla = 'szallito' ){
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                szallito_id             : $('#szallito_id').val(),
                get_szallito_mailcim    : Date.now()
            },
            success: function(response){
                if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
                if(response){
                    $('#pop_cimzett').val(response);
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

// Külső kivitelező hozzárendelés popup inputjait be kell állítani, alapértemlezett értékekkel feltölteni
function get_kulso_hozzarendeles_input_tartalmak(){
    
    // Datepicker beállítása
    $('.pop_datetimepicker').datetimepicker({
        closeOnDateSelect   : false,
        format              :'Y.m.d H:i',
        mask                : '\xa0\xa0\xa0\xa0.\xa0\xa0.\xa0\xa0\xa0\xa0:\xa0\xa0',
        timepicker          : true,
        step                : 60,
        validateOnBlur      : false,
        scrollInput         : false,
        defaultTime         : '08:00',
        minDate             : 0,
        onChangeDateTime    : function(dp, $input){
            /* Órapontosak vagyunk */
            $input.val( $input.val().substring(0, 14)+'00' );
        }
    });
    
    // ML adatainak lekérdezése
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            get_ml_adatok_levelhez  : Date.now()
        },
        success: function (munkalap) {
            if(munkalap.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (munkalap) {
                munkalap = JSON.parse(munkalap);
                
                // Levélküldés mezőinek alapértelmezett értékének beállítása
                $('#pop_targy').val( 'Hibabejelentés '+munkalap.ikt_szam+' '+munkalap.telephely_nev+' ' );

                uzenet =    "Tisztelt Partnerünk,\n\n"                      +
                            "Kérjük Önöktől az alábbi hiba javítását:\n"  +
                            "Munkalap szám: "+munkalap.ikt_szam+"\n";
                if( munkalap.surgos>0 ){
                    uzenet += "Sürgős !!!\n";
                }

                if( munkalap.bejelentes_hiba_text ){
                    hiba = munkalap.bejelentes_hiba_text;
                }else{
                    hiba = munkalap.text;
                }

                if( munkalap.bejelentes_hely ){
                    hely = munkalap.bejelentes_hely;
                }else{
                    hely = munkalap.hely;
                }
                
                gep = '';
                if( munkalap.gep ) {
                    gep = "Gép: " + munkalap.gep + "\n";
                }
                
                if( munkalap.bejelento ){
                    bejelento = munkalap.bejelento;
                }else{
                    bejelento = munkalap.letrehozta;
                }

                uzenet +=   "Hiba: "      + hiba      + "\n"    +
                            "Hely: "      + hely      + "\n"    +
                            gep                                 +
                            "Bejelentő: " + bejelento + "\n"    +
                            "\nKöszönettel,\n"                  +
                            munkalap.akt_felh_nev     + "\n"    +
                            munkalap.akt_felh_beosztas+ "\n"    +
                            munkalap.akt_felh_tel;

                $('#pop_uzenet').val(uzenet);
                
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
    
}

// ML visszavételezés popup inputjait be kell állítani, alapértemlezett értékekkel feltölteni
function get_ml_visszavetelezes_input_tartalmak(elokeszitesre){
    
    // ML adatainak elkérdezése
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid                   : _get('id'),
            get_ml_adatok_levelhez  : Date.now()
        },
        success: function (munkalap) {
            if(munkalap.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (munkalap) {
                munkalap = JSON.parse(munkalap);
                
                // Kérdés
                if(elokeszitesre){
                    $('#pop_kerdes').html('Munkalapszám: '+munkalap.ikt_szam+'<br>Szálloda: '+munkalap.telephely_nev+'<br>Biztos, hogy visszaküldi a munkalapot előkészítésre?');
                }else{
                    $('#pop_kerdes').html('Munkalapszám: '+munkalap.ikt_szam+'<br>Szálloda: '+munkalap.telephely_nev+'<br>Biztos, hogy visszaveszi a munkalapot?');
                }
                
                // Levélküldés mezőinek alapértelmezett értékének beállítsa
                $('#pop_cimzett').val(munkalap.mailcimek);
                $('#pop_targy').val( 'Megrendelés visszavonva: '+munkalap.ikt_szam+' '+munkalap.telephely_nev+' ' );

                uzenet =    "Tisztelt Partnerünk,\n\n"                      +
                            "Mégsem kérjük Önöktől az alábbi hiba javítását:\n"  +
                            "Munkalap szám: "+munkalap.ikt_szam+"\n";

                if( munkalap.bejelentes_hiba_text ){
                    hiba = munkalap.bejelentes_hiba_text;
                }else{
                    hiba = munkalap.text;
                }

                if( munkalap.bejelentes_hely ){
                    hely = munkalap.bejelentes_hely;
                }else{
                    hely = munkalap.hely;
                }
                
                gep = '';
                if( munkalap.gep ) {
                    gep = "Gép: " + munkalap.gep + "\n";
                }
                
                if( munkalap.bejelento ){
                    bejelento = munkalap.bejelento;
                }else{
                    bejelento = munkalap.letrehozta;
                }

                uzenet +=   "Hiba: "      + hiba      + "\n"  +
                            "Hely: "      + hely      + "\n"  +
                            gep                               +
                            "Bejelentő: " + bejelento + "\n"  +
                            "\nKöszönettel,\n"                +
                            munkalap.akt_felh_nev     + "\n"  +
                            munkalap.akt_felh_beosztas+ "\n"  +
                            munkalap.akt_felh_tel;

                $('#pop_uzenet').val(uzenet);
                
            }
        },
        complete: function(xhr) {
            if( xhr.status != 200 ) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
    
}

// Megrendelés sztornó email elküldve gomb futtatása
function megr_sztorno_mail_elkuldve() {
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid                       : _get('id'),
            megr_sztorno_mail_elkuldve  : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
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

// ML Tipusa ha Garanciális, akkor felhozzuk a projektek popupot
function garancialis_tipus_projekt_popup() {
    
    // Ha garanciális hiba és még nincs értéke a projekt számnak
    if( $('#tipus_id').val()==6 && !$('#pr_szam').val() ) {
        
        setTimeout(function(){ $('.xdsoft_datetimepicker').hide(); }, 250);
        csoportMuvelet('munkalap_projekt_valaszt');
        
    // Ha nem garanciális hiba vagy még üres, akkor viszont ürítjük a projektszám mezőt és a mellette levő nevet is
    } else if( $('#tipus_id').val()!=6 ) {
        $('#pr_szam').val('');
        $('#projekt_neve').html('');
    }
    return false;
}

/* Munkalapon a projekt törlése gomb */
function projekt_torelese( mehet ) {
    
    if( !mehet ) {
        alert2('Biztosan törli a projektet?', '<input type="button" class="gomb zold" value="Igen" onclick="projekt_torelese(\'mehet\');"> <input type="button" class="gomb piros" value="Nem" onclick="alert2();">');
        return false;
    }
    
    $.ajax({
        url : 'munkalap.php',
        type: 'POST',
        data: {
            recid           : _get('id'),
            projekt_torelese: Date.now()
        },
        success: function (response) {
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, 'Hiba');
                }
                popupZar();
                szamlaBetolt(_get('id'), PHP_SELF, 'modosit');
            }
        }
    });
}