<?php
if(isset($_GET['siker'])){
    //A sikerességet nem írjuk ki
	/*?><div class="info">A művelet sikeresen befejeződött!</div><?php*/

}elseif(isset($_GET['hiba'])){

	?><div class="err_info"><?php

		if($_GET['hiba'] == 'kategoria_hiba'){

			echo 'A kategória nem üres, így az nem törölhető!';

		}elseif($_GET['kephiba'] == ''){

			if(is_numeric($_GET['errnum'])){
				$i = 0;
				foreach($akt_tomb as $ertek){
					if($i == $_GET['errnum']){
                        echo $ertek['alert'];
                    }
					$i++;
				}
			}else{
				?>A művelet során ismeretlen hiba lépett fel!<?php
			}

		}else{
			if($_GET['kephiba'] == 'k1'){echo 'Túl nagy méretű fájl, feltöltés sikertelen!';        }
			if($_GET['kephiba'] == 'k2'){echo 'A kép csak jpg, gif és png kiterjesztésű lehet!';    }
			if($_GET['kephiba'] == 'k3'){echo 'Az előző kép törlése nem sikerült!';                 }
			if($_GET['kephiba'] == 'k4'){echo 'A fájl feltöltése során ismeretlen hiba lépett fel!';}
			if($_GET['kephiba'] == 'k5'){echo 'Nincs `kep` oszlop az adatbázisban!';                }
		}

	?></div><?php
}?>
