var timeout         = 200;
var closetimer		= 0;
var ddmenuitem      = 0;

function mopen(id){	
    
    /* cancel close timer*/
	mcancelclosetime();

	/* régi layer bezárása*/
	$('#sddm li div:not(#'+id+')').slideUp(80);

	/* új layer, és mutat*/
	ddmenuitem = document.getElementById(id);
    
    if( $('#'+id).is(':visible')==false ){
        $('#'+id).slideDown(80);
    }
    
}

function mclose(){
	$('#sddm li div').slideUp(80);
}

/* timer bezár*/
function mclosetime(){
	closetimer = window.setTimeout(mclose, timeout);
}

/*mégse záródjon be a menü*/
function mcancelclosetime(){
	if(closetimer)	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}

/*Klikk esetén mindig bezár - kivettem, mert ha egy főmenüre kattintunk, bezáródik, picit mozgatva az egeret, újra megnyílik, ami zavaró*/
/*document.onclick = mclose;*/
