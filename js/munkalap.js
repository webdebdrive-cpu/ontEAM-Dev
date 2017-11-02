function munkalapDiszpKozp() {
    if(balnav_kijelol_ell()){
        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapDiszpKozp: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapTeruletiVezeto: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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

function munkalapMuszakiVezeto() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapMuszakiVezeto: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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

function munkalapAdminisztrator() {

    if(balnav_kijelol_ell()){

        $.ajax({
            url : 'esemenyek.php',
            type: 'POST',
            data: {
                recid: _get('id'),
                munkalapAdminisztrator: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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

function munkalapMobil( that ) {

    if(balnav_kijelol_ell()){

        /* Hozzárendelt csoport törlése */
        var munkMobilKivetel = $(that).find('input[name="munkMobilKivetel[]"]');

        /* Hozzárendelt személyzet törlése */
        var munkHozzMegszuntet = $(that).find('input[name="munkHozzMegszuntet[]"]');

        /* Törzsadat csatolása a munkalaphoz */
        var addTorzsID = $(that).find('input[name="addTorzsID[]"]'),
            addTorzsMunkID = $(that).find('input[name="addTorzsMunkID[]"]');

        var idk = {};
        var i = 1;

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
                    idk: JSON.stringify(idk),
                    munkMobilKivetel: munkMobilKivetel.serialize(),
                    munkHozzMegszuntet: munkHozzMegszuntet.serialize(),
                    addTorzsID: addTorzsID.serialize(),
                    addTorzsMunkID: addTorzsMunkID.serialize(),
                    munkalapMobil: new Date().getTime()
                },
                success: function(response) {
                    if(response) {
                        response = JSON.parse(response);

                        if( response.hiba ) {
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                            }

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


/* Munkalap átadása külső csoportnak */
function munkalapKulso( that ) {

    if(balnav_kijelol_ell()){

        var szallito_id = $(that).find('input[name="szallito_id"]'),
        szallito_id_autocomp = $(that).find('input[name="szallito_id_autocomp"]'),
        szemelyzet_id = $(that).find('select[name="p_szemelyzet_id"]');

        if( szallito_id.val() && szemelyzet_id.val() ) {
            alert2('Külső kivitelezőt és hozzárendelt személyzetet egyszerre nem lehet megadni!');
            szemelyzet_id.val('');
            szallito_id_autocomp.val('');
            szallito_id.val('');
            document.getElementById('szallito_id_autocomp').focus();
            return false;
        }

        if( !szallito_id.val() && !szemelyzet_id.val() ) {
            alert2('Válasszon külső kivitelezőt!');
            document.getElementById('szallito_id_autocomp').focus();
            return false;
        }

        $.ajax({
            url : 'munkalap.php',
            type: 'POST',
            data: {
                recid        : _get('id'),
                szallito_id  : szallito_id.val(),
                szemelyzet_id: szemelyzet_id.val(),
                munkalapKulso: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    location.href = baseName(location.href) + '.php';
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
                recid: _get('id'),
                csak_kerdezunk: csak_kerdezunk,
                munkalapLezaras: new Date().getTime()
            },
            success: function(response) {
                if(response) {
                    response = JSON.parse(response);

                    if( response.hiba ) {
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

                        return false;
                    }

                    location.href = baseName(location.href) + '.php';
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
                        if( response.gomb ) {
                            alert2(response.hiba, response.gomb);
                        } else {
                            alert2(response.hiba, '', 'Hiba');
                        }

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
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                            }

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
                            if( response.gomb ) {
                                alert2(response.hiba, response.gomb);
                            } else {
                                alert2(response.hiba, '', 'Hiba');
                            }

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