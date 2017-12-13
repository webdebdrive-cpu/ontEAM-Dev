<?PHP
include("datumra_utemezes.php");

/*PÉLDÁK*/
$tesztadatok = array("l","",0,"","","",0,"1","1","12","31","","");/*határidős*/
$tesztadatok = array("d","1",3,"","","",0,"1","1","12","31","","");/*3 naponta*/
$tesztadatok = array("d","2",0,"","","",0,"1","1","12","31","","");/*hétköznap*/
$tesztadatok = array("w","",1,"","","13",0,"1","1","12","31","","");/*1 hetente hétfő szerda*/
$tesztadatok = array("w","",2,"","","25",0,"1","1","12","31","","");/*2 hetente kedd péntek*/
$tesztadatok = array("m","1",1,"","","4",0,"1","1","12","31","","");/*1 havonta 4. nap*/
$tesztadatok = array("m","2",1,"","2","2",0,"1","1","12","31","","");/*1 havonta 2. kedd*/

/*EZ FOG FUTNI*/
$tesztadatok = array("m","1","1","1","","14","","","","","","","");


print_r(tesztadatok);

print '<HTML>
		<BODY>
	<FORM ACTION="test3.php" METHOD="POST">
		<TABLE>
		<TR>Ütemezés teszt
		<TR><TD>max mai dátum dutolso:</TD><TD><INPUT NAME="dutolso" type="Text" VALUE="2017.05.24"></TD>		
		<TR><TD>cikltip:</TD><TD><INPUT NAME="cikltip" type="Text" VALUE="'.$tesztadatok[0].'"></TD>
		<TR><TD>ciklaltip:</TD><TD><INPUT NAME="ciklaltip" type="Text" VALUE="'.$tesztadatok[1].'"></TD>
		<TR><TD>ciklszorz:</TD><TD><INPUT NAME="ciklszorz" type="Text" VALUE="'.$tesztadatok[2].'"></TD>
		<TR><TD>ciklho:</TD><TD><INPUT NAME="ciklho" type="Text" VALUE="'.$tesztadatok[3].'"></TD>
		<TR><TD>ciklhet:</TD><TD><INPUT NAME="ciklhet" type="Text" VALUE="'.$tesztadatok[4].'"></TD>
		<TR><TD>ciklnap:</TD><TD><INPUT NAME="ciklnap" type="Text" VALUE="'.$tesztadatok[5].'"></TD>
		<TR><TD>idoszakos:</TD><TD><INPUT NAME="idoszakos" type="Text" VALUE="'.$tesztadatok[6].'"></TD>
		<TR><TD>kezdho:</TD><TD><INPUT NAME="kezdho" type="Text" VALUE="'.$tesztadatok[7].'"></TD>
		<TR><TD>kezdnap:</TD><TD><INPUT NAME="kezdnap" type="Text" VALUE="'.$tesztadatok[8].'"></TD>
		<TR><TD>vegeho:</TD><TD><INPUT NAME="vegeho" type="Text" VALUE="'.$tesztadatok[9].'"></TD>
		<TR><TD>vegenap:</TD><TD><INPUT NAME="vegenap" type="Text" VALUE="'.$tesztadatok[10].'"></TD>
		<TR><TD>aktkezd_d:</TD><TD><INPUT NAME="aktkezd_d" type="Text" VALUE="'.$tesztadatok[11].'"></TD>
		<TR><TD>aktvege_d:</TD><TD><INPUT NAME="aktvege_d" type="Text" VALUE="'.$tesztadatok[12].'"></TD>
		</TABLE>
		
	</FORM><BR><BR>		
		';
		/*<INPUT TYPE="Submit" Value="Hajrá">*/
		
		
		$utolso = "2017.05.24";
		
		print 'utolso vegrehajtas: ' . $utolso . "<BR>"
			  .'szabaly: ' . getcimke( $tesztadatok[0], $tesztadatok[1], $tesztadatok[2], $tesztadatok[3], $tesztadatok[4], $tesztadatok[5], $utolso) . "<BR><BR>";
			  
		for ($i=0;$i<6;$i++)
		{
		$kov=getnextvaliddate(false, $utolso  
		, $tesztadatok[0], $tesztadatok[1], $tesztadatok[2], $tesztadatok[3], $tesztadatok[4], $tesztadatok[5]
		, $tesztadatok[6], $tesztadatok[7], $tesztadatok[8], $tesztadatok[9], $tesztadatok[10], $tesztadatok[11], $tesztadatok[12]);
		
		print 'kovetkezo: ' . $kov . "<BR>";
		
		$utolso = $kov;
		}
		
print "</BODY>
		</HTML>";

?>