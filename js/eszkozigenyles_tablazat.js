// Eszközigénylés táblázatos nézethez tartozó js

$(document).ready(function () {
    // Csak táblázat nézetben:
    if( isset_get('tablazat') ) {

        let $table = $('table.excel');
        let $rows = $table.find('tbody tr');
    
        let $tr     = null;
        let $active = null;
        // Azért így hozzuk létre a függvényt, hogy kívülről is el lehessen érni (table.js-ben rendezés módosítása után van meghívva)
        window.initNavigacio = function() {

            $table = $('table.excel');
            $rows = $table.find('tbody tr');

            $tr = $rows.filter('.kijelolve');

            // Ha ismert, hogy melyik oszlopon álltunk, akkor arra ugrunk
            mentett_col = readCookie('eszkozigeny_aktiv_oszl');
            if( mentett_col ) {
                $active = $tr.find('td').eq(mentett_col).addClass('aktiv');

            // Egyébként az elsőre
            } else {
                $active = $tr.find('td').not('.dn').first().addClass('aktiv');
            }
            if (!$active.length) {
                return;
            }
    
            akt_oszlop_megjelol_gombok_mutat();
    
            $(document).off('keydown.excelNav').on('keydown.excelNav', function (e) {

                if( !$('#popup').is(':visible') ) {

                    let row = $rows.index($active.parent());
                    let col = $active.index();
        
                    switch (e.which) {
                        case KEY_LEFT:
                            while (col > 0) {
                                col--;
                                if (!$rows.eq(row).children().eq(col).is('.dn')) {
                                    break;
                                }
                            }
                            if ( $rows.eq(row).children().eq(col).is('.dn')) {
                                return;
                            }
                        break;
        
                        case KEY_UP:
                            row = Math.max(0, row - 1);
                            // Nem látható sorok figyemen kívül hagyása (pl. mert gyorskeresővel el vannak rejtve)
                            while (row > 0 && !$rows.eq(row).is(':visible')) {
                                row = Math.max(0, row - 1);
                            }
                            // Ha már nem is látszik az a sor, amire legutóbb léptünk, akkor ne is csinájunk semmit
                            if( !$rows.eq(row).is(':visible') ) {
                                return;
                            }
                        break;
        
                        case KEY_RIGHT:
                            let maxCol = $rows.eq(row).children().length - 1;
                            while (col < maxCol) {
                                col++;
                                if (!$rows.eq(row).children().eq(col).is('.dn')) {
                                    break;
                                }
                            }
                            if ($rows.eq(row).children().eq(col).is('.dn')) {
                                return;
                            }
                        break;
        
                        case KEY_DOWN:
                            row = Math.min($rows.length - 1, row + 1);
                            // Nem látható sorok figyemen kívül hagyása (pl. mert gyorskeresővel el vannak rejtve)
                            while ( row < $rows.length - 1 && !$rows.eq(row).is(':visible') ) {
                                row = Math.min($rows.length - 1, row + 1);
                            }
                            // Ha már nem is látszik az a sor, amire legutóbb léptünk, akkor ne is csinájunk semmit
                            if( !$rows.eq(row).is(':visible') ) {
                                return;
                            }
                        break;
        
                        default:
                        return;
                    }
        
                    $active.removeClass('aktiv');
                    $active = $rows.eq(row).children().eq(col).addClass('aktiv');

                    // Eltároljuk cookie-ba, hogy pl. Adatlap fülről visszatérve ez az információ is meglegyen.
                    createCookie('eszkozigeny_aktiv_oszl', col);
    
                    akt_oszlop_megjelol_gombok_mutat();
        
                    // Sor kijelölés frissítése
                    $rows.removeClass('kijelolve');
                    $active.parent().addClass('kijelolve');
    
                    // Aktív cella legyen a képernyőn
                    ugras_aktiv_cellahoz($active);
                    
                }

    
            });
        }
    
        // Várjuk, hogy megjelenjen a .kijelolve class
        const observer = new MutationObserver(() => {
            if ($rows.filter('.kijelolve').length > 0) {
                observer.disconnect();
                initNavigacio();
            }
        });
    
        observer.observe($table[0], {
            subtree         : true,
            attributes      : true,
            attributeFilter : ['class'],
            childList       : false
        });
    
        // Kattintás cellára: az legyen az aktív cella
        $table.on('click', 'td', function () {
            $active = $(this);
            $tr     = $rows.filter('.kijelolve');
        
            // Ha .aktiv vagy .dn class-os, akkor ne csináljon semmit
            if ($active.hasClass('aktiv') || $active.hasClass('dn')) {
                return;
            }
            $table.find('td.aktiv').removeClass('aktiv');
            $active.addClass('aktiv');

            // Eltároljuk kattintáskor is cookie-ba, hogy pl. Adatlap fülről visszatérve ez az információ is meglegyen.
            createCookie('eszkozigeny_aktiv_oszl', $active.index());
    
            akt_oszlop_megjelol_gombok_mutat();

        });

        // Direkt jóváhagyás: Felsővezető cellára kattintva ki-be pipálás
        $(document).on('click', 'table.excel td.direkt_jh', function () {
            felso_direkt_pipal($(this));
        });

        // Direkt jóváhagyás szóközzel is az aktív cellán
        $(document).on('keydown.direktJh', function (e) {
            if( e.which != 32 || $('#popup').is(':visible') || $(e.target).is('input, textarea, select') ) {
                return;
            }
            let $td = $('table.excel tbody tr.kijelolve td.aktiv.direkt_jh');
            if( $td.length ) {
                e.preventDefault();
                felso_direkt_pipal($td);
            }
        });

    }

});

// Direkt jóváhagyás: üres cellánál a zöld Jóváhagyás gomb műveletei futnak le (rákérdezés nélkül), pipás cellánál a pipa és a felsővezetői indoklás törlődik
function felso_direkt_pipal($td) {

    if( $td.hasClass('folyamatban') ) {
        return false;
    }

    let id       = $td.closest('tr').attr('id').replace(/\D/g, '');
    let van_pipa = $td.text().trim() != '';

    let adatok = { id: id };
    if( van_pipa ) {
        adatok.felso_direkt_visszavon = Date.now();
    } else {
        adatok.felso_beleegyezes = Date.now();
        adatok.direkt            = 1;
    }

    $td.addClass('folyamatban');

    $.ajax({
        url : PHP_SELF+'?tablazat',
        type: 'POST',
        data: adatok,
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                    return false;
                }

                $('.felso_vel_'+id).html(response.pipa);
                $('.gombok_'   +id).html(response.gombok);

                if( van_pipa ) {
                    $('.felso_indok_'   +id).html('');
                    $('#felso_indok_db_'+id).val ('');
                } else {
                    $('.stat_'  +id).html(response.statusz);
                    $('.gombok_'+id).data('dok_db',  response.dok_db );
                    $('.gombok_'+id).data('megj_db', response.megj_db);
                }

                // Ha a kijelölt sorban pipáltunk, a műveleti gombok és a fülek is frissüljenek
                if( $td.closest('tr').hasClass('kijelolve') ) {
                    akt_oszlop_megjelol_gombok_mutat();
                }
            }
        },
        complete: function (xhr) {
            $td.removeClass('folyamatban');
            if (xhr.status != 200) {
                alert2(xhr.responseText, '', 'Hiba');
            }
        }
    });
}

// Webcím frissítése, hogy füleken lépkedve a megfelelő adatlapjra jussunk majd
function webcim_frissit(sorId) {
    if (sorId) {
        let idSzam = sorId.replace(/\D/g, '');
        let url = new URL(window.location.href);
        url.searchParams.set('id', idSzam);
        window.history.replaceState(null, '', url);
    }
}


// Form modosítva változó beállítása: minden egyes billentyű leütésre mod classt keresünk. Ha találunk, akkor módosítva van a form
$(document).on('keyup', '.szamla_cont_tabla input', function(e) {

    if( $('.szamla_cont_tabla input.mod').length ){
        formModositva = true;
    }else{
        formModositva = false;
    }

});

// szamla_cont_tabla tábla egy sorára kattintva az legyen kijelölve
$(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {

    // Ha nem shift vagy Ctrl gombot nyomunk, akkor a többi sorról levesszük a kijelölést
    if( !e.shiftKey && !e.ctrlKey ) {
        $('.szamla_cont_tabla tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    }

});

// Aktív cella oszlop celláira is ráteszünk egy kiemelést
function akt_oszlop_megjelol_gombok_mutat() {

    setTimeout(function(){
        
        $active = $('table.excel tbody tr').filter('.kijelolve').find('td.aktiv');
    
        $('table.excel td').removeClass('akt_oszl');
        let oszlopIndex = $active.index();
    
        // Az oszlopok színezését egyelőre mégsem kérik
        // $('table.excel tr').each(function () {
        //     $(this).find('td').eq(oszlopIndex).addClass('akt_oszl');
        // });
    
        // Gombok bemásolása a műveletek konténerbe
        $('.muveletek').html( $active.parent().find('.akt_sor_gombok').html() );

        // dok_db, megj_db és tetel_db frissítése a fül felioraton sorokon lépkedve
        let dok_db   = $active.parent().find('.akt_sor_gombok').data('dok_db'  );
        let megj_db  = $active.parent().find('.akt_sor_gombok').data('megj_db' );
        let tetel_db = $active.parent().find('.akt_sor_gombok').data('tetel_db');
        if( dok_db>0 ) {
            dok_db = ' ('+dok_db+')';
        } else {
            dok_db = '';
        }
        if( megj_db>0 ) {
            megj_db = ' ('+megj_db+')';
        } else {
            megj_db = '';
        }
        if( tetel_db>0 ) {
            tetel_db = ' ('+tetel_db+')';
        } else {
            tetel_db = '';
        }
        $('#szamlaFulek').find('a[data-oldal="dokumentumok"]').html('Dokumentumok'+dok_db );
        $('#szamlaFulek').find('a[data-oldal="megjegyzesek"]').html('Megjegyzések'+megj_db);
        $('#szamlaFulek').find('a[data-oldal="tetel"]'       ).html('Tételek'+tetel_db    );
    
        // Webcím frissítése, hogy füleken lépkedve a megfelelő adatlapjra jussunk majd
        webcim_frissit($active.parent().attr('id'));

        // Aktív cella legyen a képernyőn
        ugras_aktiv_cellahoz($active);

    }, 10);
    
}

// Az éppen aktiv cella ha nincs a képernyőben, akkor scrollozzunk oda
function ugras_aktiv_cellahoz($active) {

    // Ha nem táblázat nézetben vagyunk, akkor nincs is teendő
    if( !isset_get('tablazat') ) {
        return false;
    }
    
    // Scrollozás, ha az aktív cella kikerülne a konténer látható részéből
    let $scrollContainer = $('#tartalomJobb');
    let cellRect = $active[0].getBoundingClientRect();
    let containerRect = $scrollContainer[0].getBoundingClientRect();

    let currentScrollLeft = $scrollContainer.scrollLeft();
    let currentScrollTop  = $scrollContainer.scrollTop();

    let targetScrollLeft = currentScrollLeft;
    let targetScrollTop  = currentScrollTop;

    // Vízszintes scroll (jobbra–balra)
    let vizszintes_rahagyas = 10;
    if (cellRect.right > containerRect.right) {
        targetScrollLeft += cellRect.right - containerRect.right + vizszintes_rahagyas;
    } else if (cellRect.left < containerRect.left) {
        targetScrollLeft -= containerRect.left - cellRect.left + vizszintes_rahagyas;
    }

    // Függőleges scroll (le–fel)
    // Azért kell a ráhagyás, mert a táblázat valójában alacsonyabb, mint a containerRect.bottom
    let tablazat_rahagyas = 300; 
    let tablazat_fejlec_magassaga = $('.szamla_cont_tabla thead').outerHeight();
    let tablazat_magassag_fele = (containerRect.bottom - tablazat_rahagyas)/2;
    if (cellRect.bottom > containerRect.bottom) {
        targetScrollTop += cellRect.bottom - containerRect.bottom + tablazat_magassag_fele;
    } else if (cellRect.top-tablazat_fejlec_magassaga < containerRect.top) {
        targetScrollTop -= containerRect.top - cellRect.top + tablazat_fejlec_magassaga;
    }

    // Animált scroll mindkét irányban
    // Hosszabb táblázatnál ez már volt, hogy lassabban reagált, tehágy hagyjuk az animálást
    // $scrollContainer.animate({
    //     scrollLeft: targetScrollLeft,
    //     scrollTop: targetScrollTop
    // }, 200);

    // Animáció nélkül, sima scroll
    $scrollContainer.scrollLeft(targetScrollLeft);
    $scrollContainer.scrollTop (targetScrollTop );
}

// Oszlopszélességek beállátása, oszlop rejtése, egysorosítása
function eszkozigenyles_oszlopok_beallit( reload, elso_betoltes = '', tabalzat_ne_ments = '') {

    // Oszlopok rejtése
    $('table.excel tr td').removeClass('dn');
    $('table.excel th.dn').each(function () {
        // index 0-tól indul, nth-child 1-től
        var index = $(this).index() + 1;
        $('table.excel tr td:nth-child(' + index + ')').addClass('dn');
        $('table.excel tr td:nth-child(' + index + ')').css({
            'min-width': 0,
            'max-width': 0,
            'width'    : 0
        });
    });

    // Első betöltéskor az összes oszlopra tegyük rá, hogy egysoros
    if( elso_betoltes ) {
        $('table.excel th').addClass('egysoros');
    }

    // Oszlopok egysorosítása
    $('table.excel tr td').removeClass('egysoros');
    $('table.excel th.egysoros').each(function () {
        // index 0-tól indul, nth-child 1-től
        var index = $(this).index() + 1;
        $('table.excel tr td:nth-child(' + index + ')').addClass('egysoros');
    });

    // Cella beállítás mentése
    $('#dragTable').attr('data-beall_cellak', $('#beall_cellak').val());

    // Mentés adatbázisba
    if( !tabalzat_ne_ments ) {
        tablaSorrend($('#dragTable'));
    }

    // Ez így sehogysem ment popup mentés után, ezért akkor reload-ozzuk
    // Tehát ha volt egy nagyon széles oszlop, amit elrejtettünk, akkor a rákövetkező oszlop kapta meg azt a szélességet. A HTML kódban hiába volt alacsonyabb
    // akár 0 szélesség, annak az outerWidth tulajdonsága csak megjegyezte az előző szélességet és emiatt ezt nem lehetett megoldani sehogy. Sok órás próbálkozás ellenére sem.
    if( reload ) {
        location.reload();
    } else {
        fejlec_igazit($('table.excel'));
    }

}