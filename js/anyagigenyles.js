/* Anyagigénylés táblázatos nézethet tartozó js */
var megis_sorrendezz = true;

/* Ez azért kell, hogy lépkedés után is működjön a rendezés */
/*forEach(document.getElementsByTagName('table'), function(table) {
  if (table.className.search(/\bsortable\b/) != -1) {
    sorttable.makeSortable(table);
  }
});*/

/* Oszlopnevekre kattintva azt is tároljuk le, hogy mi a sorrendezés és annak iránya */
$(document).on('dblclick', '.szamla_cont_tabla th', function(e) {

    oszlop = $(this).attr('id');
    irany  = '';
    if( $('#sorttable_sortfwdind').length ){
        irany = 'asc';
    }
    if( $('#sorttable_sortrevind').length ){
        irany = 'desc';
    }

    $.ajax({
        url : ''.self().'',
        type: 'POST',
        data: {
            oszlop: oszlop,
            irany : irany,
            anyagigenyles_tablazat_rendez: Date.now()
        },
        success: function (response) {
        }
    });
});

/* Ayagigénylés táblázatban a jóváhagyott mennyiség értéke ha változik */
$(document).on('blur keyup', '.szamla_cont_tabla tbody input', function(e) {
    if( anyagig_eredeti_jhmen[$(this).attr('id')] != $(this).val() ){
        $(this).addClass('mod');
    }
});