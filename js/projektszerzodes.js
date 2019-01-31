/* Projekt hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény. */
function pr_hozzaad_gomb_hozzaad_vagy_torol (){
    
    /* Illetve megnézzük, hogy van-e kapcsolt számla tétel, mert ha igen, akkor csatolt Projektet törölni már nem lehet */
    $.ajax({
        url : 'projektszerzodes.php',
        type: 'POST',
        data: {
            recid               : _get('id'),
            van_kapcsolt_szamla : Date.now()
        },
        success: function (response) {
            if(response.substring(0, 6)=='SESKIL'){ location.href = 'index.php'; }
            if (response) {
                response = JSON.parse(response);
                if (response.hiba) {
                    alert2(response.hiba, response.gomb, response.title);
                }else{
                
                    if( $('#pr_szam').val() ){
                        $('#pr_szam').val('');
                        /* Törléskor csak akkor adunk hozzá 'mod' classt, ha amúgy volt eredetileg értéke */
                        if( $('#pr_szam').attr('value') ){
                            $('#pr_szam').addClass('mod');
                        }else{
                            $('#pr_szam').removeClass('mod');
                        }
                        formModositva = true;
                        $('#pr_szam_gomb').val('Projekt hozzáadása').removeClass('piros');
                        $('#mehet_gomb').addClass('gombb_alert');
                        szurkit_zar('mehet_megsem_mutat');
                        popupZar();
                        popupRejt();
                    }else{
                        csoportMuvelet('projektszerzodes_projekt_valaszt');
                    }
                    
                }
                
            }
        }
    });
    
}