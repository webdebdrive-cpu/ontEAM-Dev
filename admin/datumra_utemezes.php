<?PHP

define("DATEFORMAT", "Y.m.d");

function getnextvaliddate($lstart, $dlast, $ccikltip, $cciklaltip, $nciklszorz, $cciklho, $cciklhet, $cciklnap, $nidoszakos, $ckezdho, $ckezdnap, $cvegeho, $cvegenap, $daktkezd_d, $daktvege_d)
{
	$dnext = $dlast;
	$dlast = null;
	$i = 0;
    /* Mivel nincs olyan függvény, hogy valami_0, ezért ha az altipus értéke 0,
     * akkor az értékéből üres stinget készítünk, hogy ne jelenjen meg a függvény nevében.
     */
    if($cciklaltip==0){
        $cciklaltip = '';
    }
    if($ccikltip=='w'){
        $cciklaltip = '';
    }
    /* Hasonlóan a $dlast értéke ha csupa 0, akkor cseréljük le üres stringre
     */
    if($dlast=='0000.00.00'){
        $dlast = '';
    }
    if($dnext=='0000.00.00'){
        $dnext = '';
    }

	
	while (($dlast != $dnext or $dnext < date(DATEFORMAT, strtotime("+1 day"))) and $dnext < date(DATEFORMAT, strtotime('+10 year')))  //legfeljebb a következő 2 évre keressük ütemezhetőséget
	{
		$i = $i + 1;
		$dlast = $dnext;
		$dnext = null;
		$dlast = getnextdate($lstart, $dlast, $ccikltip, $cciklaltip, $nciklszorz, $cciklho, $cciklhet, $cciklnap);

		if($dlast<0){
            $dlast = '000.00.00';
        }
//var_dump($dnext);
        $dnext = validnextdate($dlast, $nidoszakos, $ckezdho, $ckezdnap, $cvegeho, $cvegenap, $daktkezd_d, $daktvege_d);
		$lstart = true; // ha elsőnek kiszámolt esedékesség érvénytelen, újrakezdjük esedékesség számítását mai naptól
		if ($i == 100)  // ha végtelen ciklusba keveredne
		{
			$dnext = "Nem ütemezhető !";
			break;
			
		}
	}
	return $dnext;
}

// fela köv.esedékesség dátumát adja vissza intervallumhoz viszonyítva
function validnextdate($dnext, $nidoszakos, $ckezdho, $ckezdnap, $cvegeho, $cvegenap, $daktkezd_d, $daktvege_d)
{
	/*
	ellenőrzi, hogy a paraban kapott dátum érvényes-e, 
	azaz ütemezhető időszkon belül van-e, 
	ha igen: visszaadja a dátumot, 
	ha nem: visszaadja a következő legközelebbi dátumot
	helyes működés feltétele: tárgyévi ütemezést csak tárgyévben lehet megadni, 
	azaz ha gépi dátum 2010, csak 2010 év kezdettel lehet tárgyévi ütemezést megadni, 
	mert a következő évekre nem vizsgáljuk a tárgyévet
	kommentekben: tárgyév kifejezés azt az évet jelenti, amelyik m.daktkezd_d-ben van */

	//$nev = date("Y", strtotime($dnext)); // Ez magyar dátum formátumnál sajnos nem jó
	$date = DateTime::createFromFormat(DATEFORMAT, '0000.00.00');
	$nev = $date->format("Y");

    // A $nev változót itt most felülírjuk, mert a fenti esetben nem kapott rendes értéket, ha havi rendszerességű és időszakos ütemezés volt beállítva
    // Ezt mégsem tesszük meg: $nev = date('Y', strtotime( str_replace('.', '-', $dnext) ) );

	if ($nidoszakos == 0) //folyamatos ütemezés
	{
		//+1, mert mára nem ütemezhet
		return max(date(DATEFORMAT, strtotime("+1 day")), $dnext);
	}
	else //időszakos ütemezés
	{
		$dszokkezd = date(DATEFORMAT, mktime(0, 0, 0, $ckezdho, $ckezdnap, $nev)); 
		$dszokvege = date(DATEFORMAT, mktime(0, 0, 0, $cvegeho, $cvegenap, $nev));
		if (!empty($daktkezd_d)) //tárgyévi ütemezés van megadva
		{

			if (date("Y", strtotime($daktkezd_d)) == date("Y", strtotime($daktvege_d))) //márciustól októberig ütemezés
			{
				if (date("Y", strtotime($daktkezd_d)) == $nev) //tárgyévben vagyunk
				{
					if ($dnext > $daktvege_d) //tárgyévi interv. záró dátuma után
						return date(DATEFORMAT, mktime(0, 0, 0, $ckezdho, $ckezdnap, intval($nev) + 1)); 
					else
						return max($dnext, $daktkezd_d);
				}
			}
			//ha ide jut, akkor szokásos ütemezés
			else //októbertől márciusig ütemezés
			{
				if (date("Y", strtotime($daktkezd_d)) == $nev) //tárgyidőszak KEZDŐ évében vagyunk
				{
					if ($dnext > $dszokvege) //szokásos interv. záró dátuma után
						return max($dnext, $daktkezd_d);
					
				}
				if (date("Y", strtotime($daktvege_d)) == $nev) //tárgyidőszak ZÁRÓ évében vagyunk
				{
					if ($dnext < $dszokkezd) //szokásos interv. kezdő dátuma előtt
						return min($dnext, $daktvege_d);
				}
				//ha ide jut, akkor szokásos ütemezés&&&&&&&&&&&, egyik tárgyévben sem vagyunk
			}
		}

		//innen szokásos ütemezés
		if ($dszokvege > $dszokkezd) //márciustól októberig ütemezés
		{
            
			if ($dnext > $dszokvege){ //szokásos interv. záró dátuma után
				return date(DATEFORMAT, mktime(0, 0, 0, $ckezdho, $ckezdnap, intval($nev) + 1));
            }else{
				return max($dnext, $dszokkezd);
            }
		}
		else //októbertől márciusig ütemezés
		{
			if ($dnext <= $dszokvege) //szokásos interv. záró dátuma előtt
				return $dnext;
			else
				return max($dnext, $dszokkezd);
			
		}
	}
	return null; //ide nem jutunk, csak hiba esetén
	
}

//fela köv.esedékesség dátumát adja vissza
function getnextdate($lstart, $dlast, $ccikltip, $cciklaltip, $nciklszorz, $cciklho, $cciklhet, $cciklnap)
{
	/*
	lstart:
	ha false, m.dlasttól számolva a szabály szerinti következő esedékességet adja vissza
	ha true, a mai naphoz (vagy m.dlasthoz ha nagyobb) legközelebbi következő esedékességet adja vissza
	, ekkor nem szorzunk ciklusszorzóval, hogy első ütemezés ne az intervallum 3. hónapjára essen
	, (új felvitelkor vagy ütemezhető időszak kezdetekor)
	dlast: dátum amitől kezdve ütemezünk

	ez a proc a következő esedékességet adja vissza folyamatos időskálán
	, nem veszi figyelembe az intervallumot
	, ez után visszakapott dátumról még el kell dönteni, hogy beleesik-e intervallumba
	dlast: dátum ami UTÁN a legközelebbi esedékességet keressük
	*/
	if ($lstart)
	{
		/*
		újfelvitelkor vagy intervallum kezdéskor következő esedékesség számítása
		ha m.dlast jövőbeni, akkor lehet esedékes ezért az előző nappal kezdjük
		(azaz, az első ütemezhető nap m.dlast vagy date()+1)*/
		$dlast = max(dateadd ($dlast, -1, 0, 0), date(DATEFORMAT, time())); 
		/*
		ütemezés 0 ciklusszorzóval:
		, m.dlasthoz legközelebbi dátumot adja vissza (lehet előtte és utána is)*/
		eval('$dnext = getnextdate_' . trim($ccikltip) . trim($cciklaltip) . '('
			. '0,"' . $cciklho . '","' . $cciklhet . '","' . $cciklnap . '"'
			. ',"' . $dlast . '");');
		if ($dnext < dateadd($dlast, 1, 0, 0))  //előző ütemezés elmúlt dátumra vagy mára esne
		{
			/*
			ütemezés a legközelebbi dátumtól "1" ciklusszorzóval:
			, legközelebbi jövőbeni dátumra fog esni*/

			$command = 'getnextdate_' . trim($ccikltip) . trim($cciklaltip) . '('
				. '1' . ',"' . $cciklho . '","' . $cciklhet . '","' . $cciklnap . '"'
				. ',"' . $dnext . '");';
			eval('$returnvalue = '.$command);			
			return $returnvalue;
		}
		else
		{
			return $dnext;
		}
	}
	else
	{

		//ütemezéskori következő esedékesség számítása
		$command = 'getnextdate_' . trim($ccikltip) . trim($cciklaltip) . '('
			. $nciklszorz
			. ',"' . $cciklho . '"'
			. ',"' . $cciklhet . '"'
			. ',"' . $cciklnap . '"'
			. ',"' . (empty($dlast) ? date(DATEFORMAT, time()) : $dlast) . '");';
		eval('$returnvalue = '.$command);
		return $returnvalue;
	}
	
}

function getnextdate_l( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
	return $dlast;
}

function getnextdate_d1 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{

	$returnvalue = dateadd ($dlast, $nciklszorz, 0, 0);
	return $returnvalue;
}

function getnextdate_d2 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{

	$dlast = dateadd ($dlast, 1, 0, 0); //kell ide, hogy a következő első ütemezhető napot vizsgáljuk
	$ndow = dowFormStringdate($dlast);
	return ( $ndow > 5 ?
		($ndow == 6 ?
		dateadd ($dlast, 2, 0, 0) :
		dateadd ($dlast, 1, 0, 0)) :
		$dlast);
}

function getnextdate_w ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
	$ndow = dowFormStringdate($dlast);

	$cciklnap = trim($cciklnap);
	$nnapok = strlen($cciklnap);
	
	//Local anap(m.nnapok)
	
	for ($i = 0; $i < $nnapok; $i++)
		$anap[$i] = intval(substr($cciklnap, $i , 1));

	sort($anap, SORT_NUMERIC);
	
	if ($ndow >= $anap[$nnapok-1]) //a hét utolsó ütemezett napján vagy utána
	{
		$nciklszorz = max($nciklszorz,1);
		return dateadd ($dlast, ((max($nciklszorz, 1) * 7) - $ndow + $anap[0]), 0, 0);                          //a hét első ütemezett napja
	}
	else
	{
		for ($i = 0; $i < $nnapok; $i++)
		{
			if ($ndow < $anap[$i])
			{
				return dateadd ($dlast, ($anap[$i] - $ndow), 0, 0);
			}
		}
	}
}

function getnextdate_m1 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
/*	$dlast = dateadd ($dlast, 0, $nciklszorz, 0); 
	return lastvaliddate(yearFormStringdate($dlast), monthFormStringdate($dlast), intval($cciklnap));
	*/
	/*
	print " getnextdate_m1 dlast elejen: ";
	print " year: ".yearFormStringdate($dlast);
	print " month: ".monthFormStringdate($dlast);
	print " day: ".intval($cciklnap)."<BR>";
	*/
	if (yearFormStringdate($dlast) == yearFormStringdate(date(DATEFORMAT, time())) and monthFormStringdate($dlast) == monthFormStringdate(date(DATEFORMAT, time())) and intval($cciklnap) > dayFormStringdate($dlast))
	{
		$dlast = date(DATEFORMAT, mktime(0, 0, 0, monthFormStringdate(date(DATEFORMAT, time())), intval($cciklnap), yearFormStringdate(date(DATEFORMAT, time())))); 
	}
	else
	{
		$dlast = dateadd ($dlast, 0, $nciklszorz, 0); 
	}
	$lastvaliddate = lastvaliddate(yearFormStringdate($dlast), monthFormStringdate($dlast), intval($cciklnap));	
	//print "lastvaliddate: ".$lastvaliddate."<BR>";
	return $lastvaliddate;
}

function getnextdate_m2 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
	$dlast = dateadd ($dlast, 0, $nciklszorz, 0); 
	return dowtodate(yearFormStringdate($dlast), monthFormStringdate($dlast), $nciklhet, intval($cciklnap));
}

function getnextdate_y1 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
	$nyear = yearFormStringdate($dlast) + $nciklszorz;
	return lastvaliddate($nyear, $nciklho, intval($cciklnap));
}

function getnextdate_y2 ( $nciklszorz, $nciklho, $nciklhet, $cciklnap, $dlast)
{
	$nyear = yearFormStringdate($dlast) + $nciklszorz;
	return dowtodate($nyear, $nciklho, $nciklhet, intval($cciklnap));
}

function lastvaliddate ( $nev, $nho, $nnap)
{
	/*visszaadja a dátumot a paraméterekből
	, vagy a hónap utolsó érvényes napját (ha nnap>28)*/
	if ($nnap > 28) //utolsó nap
	{
		//date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap, $nev))
		$nnap = min($nnap,31);
		
		$date3 = date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap - 3, $nev));
		$date2 = date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap - 2, $nev));
		$date1 = date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap - 1, $nev));
		$date = date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap, $nev));
		
		if (monthFormStringdate($date3) != monthFormStringdate($date2))
			$date2 = "";
		
		if (monthFormStringdate($date3) != monthFormStringdate($date1))
			$date1 = "";
		
		if (monthFormStringdate($date3) != monthFormStringdate($date))
			$date = "";

		$returnvalue = empty($date) ? (empty($date1) ? (empty($date2) ? $date3 : $date2 ) : $date1 ) : $date;
	}
	else
	{
		$nnap = max($nnap, 1);
		$returnvalue =  date(DATEFORMAT, mktime(0, 0, 0, $nho, $nnap, $nev));
	}
	return $returnvalue;
}
	
function dowtodate ( $nev,  $nho, $nhet, $nnap)
{
	/*nhet: hét sorszáma a hónapban, ha 5 akkor az utolsó
	, nem a heteket számolja, hanem azt a dátumot adja vissza
	, amelyikre esik a hét egy adott napja
	pl: ha nev=2010,nho=3,nhet=2,nnap=4
	, azt a dátumot adja vissza, amikorra
	esik 2010.március második csütörtöke
	nnap: nap sorszáma a héten*/
	if ($nhet == 5) //utolsó
	{
		$dgomon = dateadd (date(DATEFORMAT, mktime(0, 0, 0, $nho, 1, $nev)), 0, 1, 0);
		$ndow = dowFormStringdate($dgomon);
		return dateadd ($dgomon, -($ndow - $nnap + ($nnap < $ndow ? 0 : 7)), 0, 0);
	}
	$ndow = dowFormStringdate(date(DATEFORMAT, mktime(0, 0, 0, $nho, 1, $nev)));
	return dateadd (date(DATEFORMAT, mktime(0, 0, 0, $nho, (($nhet - 1) * 7), $nev)), ($nnap - $ndow) + 1 + ($nnap < $ndow ? 7 : 0), 0, 0);
}

function dateadd ($stringdate, $days, $months, $years)
{

	$date = DateTime::createFromFormat(DATEFORMAT, $stringdate);
	$originalDate = DateTime::createFromFormat(DATEFORMAT, $stringdate);

	if ($days > 0)
	{
		$date->add(new DateInterval('P'.$days.'D'));
	}
	if ($days < 0)
	{
		$date->sub(new DateInterval('P'.-$days.'D'));	
	}
	if ($months > 0)
	{		
		$date->add(new DateInterval('P'.$months.'M'));
		// visszaminuszoljuk a változást és ha nem az eredeti jön ki akkor az új hónapnak nincs annyi napja ezért hibásat ad vissza 
		$testDate = DateTime::createFromFormat(DATEFORMAT, $date->format(DATEFORMAT));
		$testDate->sub(new DateInterval('P'.$months.'M'));
		if ($testDate != $originalDate)
		{	
			$date->modify( 'last day of -1 month' );
		}
	}
		
	if ($months < 0)
	{
		$date->sub(new DateInterval('P'.-$months.'M'));
		// visszaminuszoljuk a változást és ha nem az eredeti jön ki akkor az új hónapnak nincs annyi napja ezért hibásat ad vissza
		$testDate = DateTime::createFromFormat(DATEFORMAT, $date->format(DATEFORMAT));
		$testDate->add(new DateInterval('P'.-$months.'M'));
		if ($testDate != $originalDate)
		{	
			$date->modify( 'last day of -1 month' );
		}		
	}
	if ($years > 0)
	{
		$date->add(new DateInterval('P'.$years.'Y'));
		// visszaminuszoljuk a változást és ha nem az eredeti jön ki akkor az új hónapnak nincs annyi napja ezért hibásat ad vissza 
		$testDate = DateTime::createFromFormat(DATEFORMAT, $date->format(DATEFORMAT));
		$testDate->sub(new DateInterval('P'.$years.'Y'));
		if ($testDate != $originalDate)
		{	
			$date->modify( 'last day of -1 month' );
		}
	}	
	if ($years < 0)
	{
		$date->sub(new DateInterval('P'.-$years.'Y'));
		// visszaminuszoljuk a változást és ha nem az eredeti jön ki akkor az új hónapnak nincs annyi napja ezért hibásat ad vissza
		$testDate = DateTime::createFromFormat(DATEFORMAT, $date->format(DATEFORMAT));
		$testDate->add(new DateInterval('P'.-$years.'Y'));
		if ($testDate != $originalDate)
		{	
			$date->modify( 'last day of -1 month' );
		}		
	}		

	$returnvalue = $date->format(DATEFORMAT);
	//print " -> ".$returnvalue." <BR>";
	return $returnvalue;
}

function dowFormStringdate($stringdate)
{
	$date = DateTime::createFromFormat(DATEFORMAT, $stringdate);
	$returnvalue = $date->format('N') % 7;
	return $returnvalue;
}

function dayFormStringdate($stringdate)
{
	$date = DateTime::createFromFormat(DATEFORMAT, $stringdate);
	$returnvalue = intval($date->format('d'));
	return $returnvalue;
}

function monthFormStringdate($stringdate)
{
	$date = DateTime::createFromFormat(DATEFORMAT, $stringdate);
	$returnvalue = intval($date->format('m'));
	return $returnvalue;
}

function yearFormStringdate($stringdate)
{
	$date = DateTime::createFromFormat(DATEFORMAT, $stringdate);
	$returnvalue = intval($date->format('Y'));
	return $returnvalue;
}

// cimkék

// szokásos_ütemezés_cimke
function getcimke_idoszak ( $nidoszakos, $ckezdho, $ckezdnap, $cvegeho, $cvegenap)
{
	return ($nidoszakos == 0 ? 'folyamatos' :
	getcimke_ho($ckezdho) . ' ' . $ckezdnap . '. - ' .
	getcimke_ho($cvegeho) . ' ' . $cvegenap . '.');
}

// tárgyévi_ütemezés_cimke
function getcimke_aktidoszak ( $nidoszakos, $daktkezd_d, $daktvege_d)
{
	return ($nidoszakos == 0 or empty($daktkezd_d) ? '' : $daktkezd_d . ' - ' . $daktvege_d);
}

function getcimke ( $ccikltip, $cciklaltip, $nciklszorz, $cciklho, $cciklhet, $cciklnap, $desnap_d)
{
    if($ccikltip=='w'){
        $cciklaltip = '';
    }
	$command = 'getcimke_' . trim($ccikltip) . trim($cciklaltip) . '('
	. '"' . $nciklszorz . '"'
	. ',"' . $cciklho . '"'
	. ',"' . $cciklhet . '"'
	. ',"' . $cciklnap . '"' 
	. ',"' . $desnap_d . '");';
	eval('$returnvalue = '.$command);
	return $returnvalue;
}

function getcimke_l ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	return 'határidő: ' . $cesnap_d;
}

function getcimke_d1 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	$returnvalue =  '' . $nciklszorz . ' naponta';
	return $returnvalue;
}

function getcimke_d2 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	return 'minden hétköznap';
}

function getcimke_w ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	$cciklnap = trim($cciklnap);
	return '' . $nciklszorz . ' hetente, '
		. rtrim((strpos($cciklnap, '1') === FALSE ? '' : getcimke_nap('1') . ',')
		.(strpos($cciklnap, '2') === FALSE ? '' : getcimke_nap('2') . ',')
		.(strpos($cciklnap, '3') === FALSE ? '' : getcimke_nap('3') . ',')
		.(strpos($cciklnap, '4') === FALSE ? '' : getcimke_nap('4') . ',')
		.(strpos($cciklnap, '5') === FALSE ? '' : getcimke_nap('5') . ',')
		.(strpos($cciklnap, '6') === FALSE ? '' : getcimke_nap('6') . ',')
		.(strpos($cciklnap, '7') === FALSE ? '' : getcimke_nap('7') . ','), ',')
		.(strlen($cciklnap) == 1 ? ' napon' : ' napokon');
}

function getcimke_m1 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	return '' . $nciklszorz . ' havonta, a hónap ' . ($cciklnap == '31' ? 'utolsó' : (rtrim($cciklnap) == '1' ? 'első' : rtrim($cciklnap) . '.') ) . ' napján';
}

function getcimke_m2 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	return '' . $nciklszorz . ' havonta, a ' 
		. getcimke_het(Rtrim($cciklhet)) . ' ' . getcimke_nap(Rtrim($cciklnap)) . ' során';
}

function getcimke_y1 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	$returnvalue = '' . $nciklszorz . ' évente, ' . getcimke_ho(rtrim($cciklho)) . ' ' . rtrim($cciklnap).'. napján';
	return $returnvalue;
}

function getcimke_y2 ( $nciklszorz, $cciklho, $cciklhet, $cciklnap, $cesnap_d)
{
	return '' . $nciklszorz . ' évente, '
	. getcimke_ho(Rtrim($cciklho))
	. ' ' . getcimke_het(Rtrim($cciklhet)) . ' ' . getcimke_nap(Rtrim($cciklnap)) . ' során';
}

function getcimke_nap ($cid)
{
	switch($cid)
	{
		case '1':
			$returnvalue = 'hétfő';
			break;
		case '2':
			$returnvalue = 'kedd';
			break;
		case '3':
			$returnvalue = 'szerda';
			break;
		case '4':
			$returnvalue = 'csütörtök';
			break;
		case '5':
			$returnvalue = 'péntek';
			break;
		case '6':
			$returnvalue = 'szombat';
			break;
		case '7':
			$returnvalue = 'vasárnap';
			break;
	}
	return $returnvalue;
}

function getcimke_het ($cid)
{
	switch($cid)
	{
		case '1':
			$returnvalue = 'első';
			break;
		case '2':
			$returnvalue = 'második';
			break;
		case '3':
			$returnvalue = 'harmadik';
			break;
		case '4':
			$returnvalue = 'negyedik';
			break;
		case '5':
			$returnvalue = 'utolsó';
			break;
	}
	return $returnvalue;
}

function getcimke_ho ($cid)
{
	switch($cid)
	{
		case '1':
			$returnvalue = 'január';
			break;
		case '2':
			$returnvalue = 'február';
			break;
		case '3':
			$returnvalue = 'március';
			break;
		case '4':
			$returnvalue = 'április';
			break;
		case '5':
			$returnvalue = 'május';
			break;
		case '6':
			$returnvalue = 'június';
			break;
		case '7':
			$returnvalue = 'július';
			break;
		case '8':
			$returnvalue = 'augusztus';
			break;
		case '9':
			$returnvalue = 'szeptember';
			break;
		case '10':
			$returnvalue = 'október';
			break;
		case '11':
			$returnvalue = 'november';
			break;
		case '12':
			$returnvalue = 'december';
			break;			
			
	}
	return $returnvalue;
}

function getcimke_cikltipus ($cid)
{
	switch($cid)
	{
		case 'l':
			$returnvalue = 'Határidő';
			break;
		case 'd':
			$returnvalue = 'Nap';
			break;
		case 'w':
			$returnvalue = 'Hét';
			break;
		case 'm':
			$returnvalue = 'Hónap';
			break;
		case 'y':
			$returnvalue = 'Év';
			break;
	}
	return $returnvalue;
}


?>