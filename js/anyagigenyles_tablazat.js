/* Anyagigénylés táblázatos nézethet tarrtozó js */

/* Ez azért kell, hogy lépkedés után is működjön a rendezés */
/*forEach(document.getElementsByTagName('table'), function(table) {
  if (table.className.search(/\bsortable\b/) != -1) {
    sorttable.makeSortable(table);
  }
});*/

/* Az első sorra kattintás (fake soron felül, tehát a 2-ra), ha nincs épp aktív sor megjelölve */
$(document).ready(function () {
    if( !$('.szamla_cont_tabla tbody tr.kijelolve').length ){
        $('.szamla_cont_tabla tbody tr:nth-of-type(2)').click();
    }
    
    /* Ha van már kijelölt sor, akkor meg ahhoz ugorjunk */
    if($('.szamla_cont_tabla tr.kijelolve').length){
        $('#tartalomJobb').scrollTop( $('.szamla_cont_tabla tr.kijelolve').position().top-100 );
    }
});

/* Anyagigénylés táblázatban a jóváhagyott mennyiség értéke ha változik. A anyagig_eredeti_jhmen tömb a fuggvenyek.php-ban az anyagigenyleslista() résznél jön létre, ahol a táblázat HTML-je kigenerálódik */
/*$(document).on('blur keyup', '.szamla_cont_tabla tbody input', function(e) {
    if( anyagig_eredeti_jhmen[$(this).attr('recid')] != $(this).val() ){
        $(this).addClass('mod');
        formModositva = true;
    }else{
        $(this).removeClass('mod');
    }
});*/

/* Form modosítva változó beállítása: minden egyes billentyű leütésre mod classt keresünk. Ha találunk, akkor módosítva van a form */
$(document).on('keyup', '.szamla_cont_tabla input', function(e) {

    if( $('.szamla_cont_tabla input.mod').length ){
        formModositva = true;
    }else{
        formModositva = false;
    }

});

/* Árazva státuszú sorokban található mező elhagyásakor */
$(document).on('blur', '.szamla_cont_tabla input', function(e) {

    osszeg = 0;
    $('.szamla_cont_tabla tbody tr').each(function () {
        if( !$(this).hasClass('fake') ){
            id = $(this).attr('data-recid');
            /* Ha van input, akkor annak az értéke */
            if( $('#jhmen_'+id).length ){
                jhmen = Number( $('#jhmen_'+id).val().replace(',', '.').replace(' ', '') );

            /* Ha nincs input, akkor a TD értéke */
            }else{
                jhmen = Number( $('#td_jhmen_'+id).html().replace(',', '.').replace(' ', ''));
            }

            rndear  = Number( $('#td_rndear_'+id).html().replace(',', '.').replace(' ', ''));
            osszeg += jhmen*rndear;
            
            /* Soronként is beállítjuk */
            $('#jovah_ertek_sor_'+id).html( ezresek(jhmen*rndear, 0, '', ' ') );
        }
    });
    $('#rend_ertek').html( ezresek(osszeg, 0, '', ' ')+' Ft' );

});

/* Árazva státuszú sorokban található mezőből ha TAB-al kilépünk */
$(document).on('keyup', '.szamla_cont_tabla input', function(e) {
    if( e.keyCode == KEY_TAB ) {
        $('.szamla_cont_tabla input:focus').closest('tr').click();
    }
});

/* Anyagigénylés táblázat Ctrl+Shift kijelölése */
$(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {
    
    if($('.szamla_cont_tabla').hasClass('kijelolheto')){

        var sorok = $('.szamla_cont_tabla tbody tr');
        /* Bal oldali navpanel SHIFT gomb használata */
        if( e.shiftKey ) {

            if( !sorok.hasClass('kijelolve') ) {

                $(this).addClass('kijelolve');

            } else {

                /* A shifthez legközelebb eső felső kijelölt sor megkeresése, ha az nincs, akkor meg az alsó */
                var akt_id = parseInt( $(this).prevAll('.kijelolve').attr('data-i') );
                if(!akt_id){
                    akt_id = parseInt( $(this).nextAll('.kijelolve').attr('data-i') );
                }

                var uj_akt_id = parseInt( $(this).attr('data-i') );

                if( uj_akt_id > akt_id ) {
                    for( var i = akt_id; i <= uj_akt_id; i++ ) {
                        if( !$('.szamla_cont_tabla tr[data-i="' + i + '"]').hasClass('kijelolve') ) {
                            $('.szamla_cont_tabla tr[data-i="' + i + '"]').addClass('kijelolve');
                            /* A valódi, rejtett nav panelt is megjelöljük */
                            $('#szamla-'+$('.szamla_cont_tabla tr[data-i="' + i + '"]').attr('data-recid')).addClass('kijelolve');
                        }
                    }
                } else {
                    for( var i = akt_id; i >= uj_akt_id; i-- ) {
                        if( !$('.szamla_cont_tabla tbody tr[data-i="' + i + '"]').hasClass('kijelolve') ) {
                            $('.szamla_cont_tabla tbody tr[data-i="' + i + '"]').addClass('kijelolve');
                            /* A valódi, rejtett nav panelt is megjelöljük */
                            $('#szamla-'+$('.szamla_cont_tabla tr[data-i="' + i + '"]').attr('data-recid')).addClass('kijelolve');
                        }
                    }
                }

            }

        /* Bal oldali navpanel CTRL gombbal való kijelölgetése */
        } else if( e.ctrlKey ) {
            /*sorok.removeClass('active');*/

            /* Tömeges kijelöléshez */
            $(this).parent().find('.kijelolve');

            if( $(this).hasClass('kijelolve') ) {
                $(this).removeClass('kijelolve');
                /* A valódi, rejtett nav panelt is megjelöljük */
                $('#szamla-'+$(this).attr('data-recid')).removeClass('kijelolve');

            } else {
                $(this).addClass('kijelolve');
                /* A valódi, rejtett nav panelt is megjelöljük */
                $('#szamla-'+$(this).attr('data-recid')).addClass('kijelolve');
            }

        }

        /* Kijelölt sorok átmásolása az eredeti navpanelbe */
        setTimeout(function(){
            $('#szamlaList li').removeClass('kijelolve');
            $('.szamla_cont_tabla tbody tr.kijelolve').each(function () {
                $('#szamla-'+$(this).attr('data-recid')).addClass('kijelolve');
            });

        }, 10);

    /* Ha nem egy kijelölhető táblázat, akkor csak a megnyomott sor legyen megjelölve */
    }else{
        $('.szamla_cont_tabla tbody tr').removeClass('kijelolve');
    }

});

/* szamla_cont_tabla tábla egy sorára kattintva az legyen kijelölve */
$(document).on('click', '.szamla_cont_tabla tbody tr', function(e) {

    /* Ha nem shift vagy Ctrl gombot nyomunk, akkor a többi sorról levesszük a kijelölést */
    if( !e.shiftKey && !e.ctrlKey ) {
        $('.szamla_cont_tabla tr').removeClass('kijelolve');
        $(this).addClass('kijelolve');
    }

});
