<script>
	var fajl_sor_id{OSZLOP} = "{ID}";
	var tabla{OSZLOP}       = "{TABLA}";
	var oszlop{OSZLOP}      = "{OSZLOP}";
	var fajl{OSZLOP}        = "{FAJL}";
	var fajl_torol{OSZLOP}  = '<p class="c" style="margin-top: 5px"><input class="gomb" type="button" value="Igen" onclick="fajl_torol(fajl_sor_id{OSZLOP},tabla{OSZLOP},oszlop{OSZLOP},fajl{OSZLOP}); alert2()"> <input class="gomb" type="button" value="Nem" onclick="alert2()"></p>';
</script>
<a id="fajl_nagyito_{OSZLOP}" href="../letolt.php?fajl={FAJL}&amp;nev={NEV}{KIT}" target="_blank">
	<img class="thumb" alt="Fájl letöltése" title="Fájl letöltése" src="../elemek/admin/nagyito.gif" width="22" height="22">
</a>