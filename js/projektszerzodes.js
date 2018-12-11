/* Projekt hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény. */
function pr_hozzaad_gomb_hozzaad_vagy_torol (){
    if( $('#pr_szam').val() ){
        $('#pr_szam').val('');
        $('#pr_szam_gomb').val('Projekt hozzáadása').removeClass('piros');
        popupZar();
        popupRejt();
    }else{
        csoportMuvelet('projektszerzodes_projekt_valaszt');
    }
}