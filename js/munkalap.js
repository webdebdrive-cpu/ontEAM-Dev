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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                recid: _get('id'),
                munkalapMuszakiVezetoHelyettes: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            return false;
                        }

                        location.href = get_get();
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
            if (response) {
                response = JSON.parse(response);

                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                popupZar();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
                }
            }
        });
    }

    return false;
}

/* Munka visszaadása - nem ugyanaz mint a Diszpécser központ vezetőnek! */
function munkalapVisszaad(mehet) {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid           : _get('id'),
                mehet           : mehet,
                munkalapVisszaad: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
                }
            }
        });

    }
    return false;
}

/* Munkalap külsős jóváahgyása */
function munkalapKulsosJovahagy(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                csak_kerdezunk          : csak_kerdezunk,
                munkalapKulsosJovahagy  : new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    location.href = get_get();
                }
            }
        });

    }
    return false;
}

/* Munkalap átadása telepített csoportnak */
function munkalapTelepitett(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

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
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'modosit');
                    popupZar();
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
                    if(response) {
                        response = JSON.parse(response);
                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            return false;
                        }

                        szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
                        alert2();
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
                        if(response) {
                            response = JSON.parse(response);

                            if( response.hiba ) {
                                alert2(response.hiba, '', 'Hiba');
                                return false;
                            }

                            if(csak_kerdezunk) {
                                csoportMuvelet('munkalaphoz_adas');
                            } else {
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
         	url : 'munkalap.php',
         	type: 'POST',
            async: false,
         	data: {
                recid           : recid,
                munkFeladatTorol: new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
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
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
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
         		if(response) {
         			response = JSON.parse(response);

                    $('#'+elem_id).parents('tr').remove();
                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
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
        alert2( 'Munkalapszám: '+$('#ikt_szam').val()+'<br>Szálloda: '+$('#telephely_id option:selected').text()+'<br>Biztosan visszateszi a nyitottak közé?', '<input type="button" class="gomb zold" onclick="munkalapVisszaNyitott(\''+recid+'\', \'mehet\')" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">');
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
            response = JSON.parse(response);
            if( response.hiba ) {
                alert2(response.hiba, response.gomb, response.title);
                return false;
            }
            location.href = get_get();
        }
    });
}

/* Munkalap visszavétel */
function munkalapVisszavetel(recid, mehet){
    if(!mehet){
        alert2( 'Munkalapszám: '+$('#ikt_szam').val()+'<br>Szálloda: '+$('#telephely_id option:selected').text()+'<br>Biztos, hogy visszaveszi a munkalapot?', '<input type="button" class="gomb zold" onclick="munkalapVisszavetel(\''+recid+'\', \'mehet\')" value="Igen"><input type="button" class="gomb piros" onclick="alert2()" value="Nem">');
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
            if(response){
                response = JSON.parse(response);
                if( response.hiba ) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }
            }
            location.href = get_get();
        }
    });
}