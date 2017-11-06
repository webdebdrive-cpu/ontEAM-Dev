function munkalapDiszpKozp() {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapDiszpKozp: new Date().getTime()
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

function munkalapTeruletiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid: _get('id'),
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

/* Átadás műszaki vezetőnek */
function munkalapMuszakiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid: _get('id'),
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

/* Átadás adminisztrátornak */
function munkalapAdminisztrator() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapAdminisztrator: new Date().getTime()
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

                        /*location.href = get_get();*/
                    }
                }
            });
        }
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

/* Munkalap külsős elutasítása */
function munkalapKulsosElutasit(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                csak_kerdezunk          : csak_kerdezunk,
                munkalapKulsosElutasit  : new Date().getTime()
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

/* Munkalap Igazolása */
function munkalapIgazolElutasit(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                   : _get('id'),
                csak_kerdezunk          : csak_kerdezunk,
                munkalapIgazolElutasit  : new Date().getTime()
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

/* Munkalap reklamáció elutasítása */
function munkalapReklamacioElutasit(csak_kerdezunk) {

    if(balnav_kijelol_ell()){

        alert2();

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid                       : _get('id'),
                csak_kerdezunk              : csak_kerdezunk,
                munkalapReklamacioElutasit  : new Date().getTime()
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

        alert2();

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
                             lert2(response.hiba, response.gomb, response.title);
                            return false;
                        }

                        szamlaBetolt(recid, baseName(location.href) + '.php', 'tetel');
                    }
                }
            });
        }

        alert2();
    }
}

/* Munkalap készítése */
function munkalapKeszit(csak_kerdezunk) {

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

        if( objLength(idk) ) {
            $.ajax({
                url  : 'munkalap.php',
                type : 'POST',
                data : {
                    idk             : JSON.stringify(idk),
                    csak_kerdezunk  : csak_kerdezunk,
                    munkalapKeszit  : new Date().getTime()
                },
                success: function(response) {
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            alert2(response.hiba, response.gomb, response.title);
                            return false;
                        }

                        location.href = 'munkalap.php?id=' + response.recid + '&modosit';

                        kijelolesEltavolit();
                        popupZar();
                    }
                }
            });
        } else {
            alert2('Nincsenek kijelölt sorok!', '', 'Hiba');
        }
    }
    return false;
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
function munkAnyagIgenyles( that, csak_kerdezunk ) {
    var addCikkUj           = $(that).find('input[name="addCikkUj[]"]'),
        addCikkID           = $(that).find('input[name="addCikkID[]"]'),
        addCikkFelhMenny    = $(that).find('input[name="addCikkFelhMenny[]"]'),
        addCikkSzukMenny    = $(that).find('input[name="addCikkSzukMenny[]"]');

    if( addCikkID.length && addCikkFelhMenny.length ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid               : _get('id'),
                addCikkUj           : addCikkUj.serialize(),
                addCikkID           : addCikkID.serialize(),
                addCikkFelhMenny    : addCikkFelhMenny.serialize(),
                addCikkSzukMenny    : addCikkSzukMenny.serialize(),
                csak_kerdezunk      : csak_kerdezunk,
                munkAnyagIgenyles   : new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        alert2(response.hiba, response.gomb, response.title);
                        return false;
                    }

                    if( response.refresh ) {
                        location.href = baseName(location.href) + '.php';
                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                    popupZar();
                    alert2();
         		}
         	}
        });
    }

    return false;
}

function munkAnyagVetelezes( that ) {
    var addCikkUj = $(that).find('input[name="addCikkUj[]"]'),
        addCikkID = $(that).find('input[name="addCikkID[]"]'),
        addCikkFelhMenny = $(that).find('input[name="addCikkFelhMenny[]"]');

    if( addCikkID.length && addCikkFelhMenny.length ) {
        $.ajax({
         	url : 'munkalap.php',
         	type: 'POST',
         	data: {
                recid               : _get('id'),
                addCikkUj           : addCikkUj.serialize(),
                addCikkID           : addCikkID.serialize(),
                addCikkFelhMenny    : addCikkFelhMenny.serialize(),
                munkAnyagVetelezes  : new Date().getTime()
            },
         	success: function(response) {
         		if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb, response.title);
                        } else {
                            alert2(response.hiba, response.gomb, response.title);
                            document.getElementById('cikk_id_autocomp').focus();
                        }

                        return false;
                    }

                    szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                    popupZar();
         		}
         	}
        });
    } else {
        alert2('Kérem válasszon felhasznált mennyiséget!', '', 'Hiba');
        document.getElementById('cikk_id_autocomp').focus();
    }

    return false;
}

function feladatHozzaadas( that ) {

    if(balnav_kijelol_ell()){

        var erteklista_id = $(that).find('input[name="erteklista_id"]'),
            memo = $(that).find('textarea[name="feladat_memo"]');

        if( erteklista_id ) {
            $.ajax({
                url : 'munkalap.php',
                type: 'POST',
                data: {
                    recid           : _get('id'),
                    erteklista_id   : erteklista_id.val(),
                    memo            : memo.val(),
                    feladatHozzaadas: new Date().getTime()
                },
                success: function(response) {
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb, response.title);
                            } else {
                                alert2(response.hiba, response.gomb, response.title);
                                document.getElementById('erteklista_id_autocomp').focus();
                            }

                            return false;
                        }

                        szamlaBetolt(_get('id'), baseName(location.href) + '.php', 'tetel');
                        popupZar();
                    }
                }
            });
        } else {
            alert2('Adja meg az elvégzett munkát!', '', 'Hiba');
            document.getElementById('erteklista_id_autocomp').focus();
        }
    }
    return false;
}