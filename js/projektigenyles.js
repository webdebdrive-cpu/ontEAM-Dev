/* Projektigénylés alatt az adatlap és cash flow fül közötti váltás */
function prigeny_ful_valt(ful){

    /* Ha nem az adatlapon vagyunk akkor szamlaBetolt */
    if(!_get('modosit')){
        betoltott = szamlaBetolt(_get('id'), 'projektigenyles.php', 'modosit');

        if(ful=='cash_flow'){
            $.when( betoltott ).done(function() {
                if(ful=='cash_flow'){
                    cash_flow_fulre_lep();
                }
            });
        }

    /* Egyébként csak JS tartalom csere */
    }else{

        if(ful=='adatlap'){
            $('#cash_flow_div').hide();
            $('#szerk_form').show();
            $('#ful_cash_flow').removeClass('active');
            $('#ful_modosit').addClass('active');
            $('#navGombok .uj').attr('href', 'projektigenyles.php?uj');
        }

        if(ful=='cash_flow'){
            cash_flow_fulre_lep();
        }

        var vannak_ideiglenes_sorok = false;
        $('#cash_flow_div div.kijSor').each(function() {
            if( $(this).attr('data-id')=='UJ' ){
                vannak_ideiglenes_sorok = true;
            }
        });

        if(vannak_ideiglenes_sorok){
            $('#mehet_gomb').removeAttr('disabled').addClass('gombb_alert');
            $('#megsem_gomb').removeAttr('disabled');
        }

    }

}

/* Cashflow fülre lépés */
function cash_flow_fulre_lep(){
    $('#cash_flow_div').show();
    $('#szerk_form').hide();
    $('#ful_cash_flow').addClass('active');
    $('#ful_modosit').removeClass('active');
    setTimeout(function(){ 
        $('#navGombok .uj').attr('href', 'javascript: kapcsolodoSzerkeszt(\'cash_flow\'); void(0);');
    }, 500);  
    
    window.history.pushState('', '', 'projektigenyles.php?id='+_get('id')+'&cash_flow');
}

/* Cashflow fülön vagyunk? */
function cash_flow_fulon_vagyunk(){
    return $('#ful_cash_flow').hasClass('active');
}

/* Cashflow fülön vagyunk? */
function cash_flow_sorok_torol(){
    
    /* Ha van kijelölt sor */
    if( $('.kijSor.kijelolve').length ){
    
        /* Ha nincs meg egyik kezelés joga sem, akkor nem törölheti */
        if( _jogok[39]=='1' || _jogok[60]=='1' || _jogok[59]=='1' || _jogok[62]=='1' || _jogok[49]=='1' || _jogok[47]=='1' || _jogok[64]=='1' ){

            alert2('Biztosan törli a kijelölt Cash-flow összegeket?', '<p class="c"><input class="gomb zold" type="button" value="Igen" onclick="mellekTorol(\'cash_flow\', \'cash_flow\', \'cash_flow\');"><input class="gomb piros" type="button" value="Nem" onclick="alert2()"></p>');

        }else{
            alert2('Önnek nincs joga Cash-flow összeget törölni!', '', 'Hiba');
        }
        
    }else{
        alert2('Kérjük, jelölje meg a törölni kívánt összegeket!', '', 'Hiba');
    }
}