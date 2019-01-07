/* Visszaadva pipa lekezelése. */
function pl_szamla_visszaadva (){
    if( !$('#visszaadva').prop('checked') ){
        $('#visszaadva_dt').val('').blur();
        $('#visszaadva_dt').removeClass('csillag');
    }else{
        $('#visszaadva_dt').addClass('csillag');
    }
}

/* Projektszerződés hozzáadása, törlése gomb: melyik legyen? Ezt dönti el ez a függvény. */
function prsz_hozzaad_gomb_hozzaad_vagy_torol (){
    if( $('#sz_szam').val() ){
        
        /* Törléskor csak akkor adunk hozzá 'mod' classt, ha amúgy volt eredetileg értéke */
        $('#sz_szam').val('');
        if( $('#sz_szam').attr('value') ){
            $('#sz_szam').addClass('mod');
        }else{
            $('#sz_szam').removeClass('mod');
        }
        $('#pr_szam').val('');
        if( $('#pr_szam').attr('value') ){
            $('#pr_szam').addClass('mod');
        }else{
            $('#pr_szam').removeClass('mod');
        }
        $('#let_fel_autocomp').val('');
        $('#let_fel').val('');
        if( $('#let_fel_autocomp').attr('value') ){
            $('#let_fel_autocomp').addClass('mod');
        }else{
            $('#let_fel_autocomp').removeClass('mod');
        }
        $('#kolt_vis_autocomp').val('');
        $('#kolt_vis').val('');
        if( $('#kolt_vis_autocomp').attr('value') ){
            $('#kolt_vis_autocomp').addClass('mod');
        }else{
            $('#kolt_vis_autocomp').removeClass('mod');
        }
        
        formModositva = true;
        $('#sz_szam_gomb').val('Szerződés hozzáadása').removeClass('piros');
        $('#mehet_gomb').addClass('gombb_alert');
        szurkit_zar('mehet_megsem_mutat');
        popupZar();
        popupRejt();
        
    }else{
        
        csoportMuvelet('projektszamla_szerzodes_valaszt');
        
    }
}