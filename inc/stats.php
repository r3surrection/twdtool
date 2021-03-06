<?php
include "verify.php";

if (isset($_POST['uid'])) {
	$sUser = $_POST['uid'];
} else {
	$sUser = $_GET['uid'];
}

if(!$sUser) {
	$sUser = $_POST['suid'];
}
$sUser = preg_replace('/[^0-9]/','',$sUser);


if (isset($_GET['uid']) & !user_exists($sUser)){echo '<div class="alert alert-danger">
  <strong>Abbruch</strong> Gewählte User-ID <b>'.$sUser.'</b> existiert nicht. Funktion nicht ausführbar</div>	<a href="?action=stats" name="back" class="btn btn-info" role="button">Zurück</a>'; exit();}

if ($sUser == $_SESSION['userid'])
{
	$stattype = 'Deine Statistik';	
}
else
{
	$stattype = 'Spieler/in:';	
}

if (!isdev()){
$gidfilter = 'gid = '.$_SESSION['gid'];
$and_grouplimit = ' AND '.$gidfilter;
}

if (isdev() && $_POST['gid']){
if (is_numeric($_POST['gid'])){
$and_grouplimit = ' AND gid = '.$_POST['gid'];
}

if ($_POST['gid'] == "uc"){
$and_grouplimit =  ' AND gid = 0';
}
}

?>  
<form class="form-vertical" role="form" method = "POST" action = "?action=stats" >
<input  type = "hidden" name = "suid" type="text" value = "<? echo $sUser; ?>">

<?php if (isdev()){?>
<div class ="form-group">
<label for="inputGroup" class = "control-label">Gruppe wählen: <span class="fas fa-arrow-right"></span></label>
      <select onchange="this.form.submit()" id="inputGroup" name = "gid" class = "form-control" style="width:auto;min-width:200px;">
	 <option value="allgrp" <?php if ($_POST['gid'] == 'allgrp'){echo ' selected';} ?>>--Alle--</option>
	 <option value="uc" <?php if ($_POST['gid'] == 'uc'){echo ' selected';} ?>>--Ohne Gruppe--</option>
<?php
	$sql = 'SELECT id, tag, name FROM `'.$config->db_pre.'groups` ORDER BY name ASC';
	
	
    foreach ($pdo->query($sql) as $row) {
		if ($_POST['gid'] == $row['id'])
	{
	$gidselected = ' selected';
	}
       echo '<option value="'.$row['id'].'" '.$gidselected.'>['.$row['tag'].'] '.$row['name'].'</option>';
	    $gidselected = '';
    }

?>
     </select>
	 </div>
<?php 
}
?>
	<label for="inputUser" class = "control-label"><?php echo $stattype; ?></label> 
    <div class="form-group">
		<div class="clearfix">
	  <div class="pull-left">   
      <select onchange="this.form.submit()" id="inputUser" name = "uid" class = "form-control" style="width:auto;min-width:200px;">

<?php


$sql = 'SELECT id,ign,telegram,notes FROM `'.$config->db_pre.'users` WHERE active = 1 '.$and_grouplimit.' ORDER BY ign ASC';
		
	       echo '<option value="">--Wähle--</option>';
foreach ($pdo->query($sql) as $row) {
		
	if ($sUser == $row['id']) {
		$selected = ' selected';
	
		if ($row['notes'] > "" & isadminormod()){
			$btnnotes = '<a href="?action=notes&uid='.$sUser.'" class="btn btn-success" role="button"><span class = "fas fa-paperclip"></span> Notizen</a> ';
		}
		
		if ($row['telegram'] > ""){
			$telegram = '<a href="https://t.me/'.$row['telegram'].'" target = "_new" class="btn btn-info" role="button"><span class = "fab fa-telegram-plane"></span> Telegram</a> ';
		}
								  
		if (isadminormod()){
			$editusr = '<a href="?action=usrmgr&uid='.$sUser.'" class="btn btn-warning" role="button"><span class = "fas fa-edit"></span> Edit User </a> <a href="?action=addstat&uid='.$sUser.'" class="btn btn-success" role="button"><span class = "fas fa-plus-square"></span> Stat hinzu</a> ';
		}

	}
		
    echo '<option value="'.$row['id'].'" '.$selected.'>'.$row['ign'].'</option>';
	$selected = '';
}
echo '</select></div> 
	  <div class="pull-right">'.$btnnotes.$telegram.$editusr.'</div></div></div>';


if (isset($_POST['limit']) && $_POST['limit']>""){
	$sel_limit = preg_replace('/[^0-9]/','',$_POST['limit']);
}
else{
	$sel_limit = $config->statlimit;  //Wert aus config
}	



$query1 = $pdo->prepare('SELECT count(id) as anz FROM '.$config->db_pre.'stats WHERE uid = :uid and `fail` = 0');
$query1->execute(array(':uid' => $sUser));
$total_stats = $query1->fetchColumn();

$calc_gespmis = array();
$calc_abgespmis = array();
$diff_gespmis = array();
$diff_abgespmis = array();

if($total_stats > 0){

	echo'<div class="slidecontainer">
		   Die letzten <label for="inputUser" class = "control-label"> <span id="demo"></span> von '.$total_stats.' </label> Einträgen
	  <input onchange="this.form.submit()" type="range" min="1" max="'.$total_stats.'" value="'.$sel_limit.'" step="1" class="slider" id="myRange" name="limit">
	</div>
	</form>';


		
	#if($_POST['uid']>"" OR $_GET['uid']>""){
	if ($sUser > ""){
	#if ((isuser() && $_GET['user'] == $_SESSION['ign']) or isadminormod()){
	


		#Wenn normale User nur die letzten 2 Uploads sehen dürfen hier entkommentieren
		#if (isuser() AND ($_SESSION['ign'] <> $_POST['uid'])) {$limit = ' LIMIT 0,2';}


		#if (is_numeric($statlimit)){$limit = ' LIMIT 0,'.$statlimit;}
		if (is_numeric($sel_limit)){$limit = ' LIMIT 0,'.$sel_limit;}



		#daten abfragen für subtraktion - AndyMOD
		$querydiff = $pdo->prepare('SELECT gespielte_missionen,abgeschlossene_missonen FROM '.$config->db_pre.'stats WHERE uid = :uid and `fail` = 0 ORDER BY date DESC'. $limit);
		$querydiff->execute(array(':uid' => $sUser));
		$data = $querydiff->fetchAll(); #is maybe faster

		#daten in ein array setzen
		foreach($data as $row) {
			$calc_gespmis[] = $row['gespielte_missionen'];
			$calc_abgespmis[] = $row['abgeschlossene_missonen'];
		}
		unset($row);

		#array mit for schleife durch iterator steuerbar machen
		#berechnung durchführe und Ergebnis in neues array formatiert einsetzen
		for($i=0; $i<count($calc_gespmis); $i++) {
			if(isset($calc_gespmis[$i+1])) {
				$diff_gespmis[] 	= $calc_gespmis[$i]-$calc_gespmis[$i+1];
				$diff_abgespmis[] 	= $calc_abgespmis[$i]-$calc_abgespmis[$i+1];
			} else {
				$diff_gespmis[] 	= $calc_gespmis[$i];
				$diff_abgespmis[] 	= $calc_abgespmis[$i];
			}
		}

   
		$query = $pdo->prepare('SELECT * FROM '.$config->db_pre.'stats WHERE uid = :uid and `fail` = 0 ORDER BY date DESC'. $limit);
		$query->execute(array(':uid' => $sUser));
  
 
   
?>
<div id="container"></div>
<small> <span class = "fas fa-info-circle"></span>  Durch Klick auf einen Wert unter Legende können einzelne Werte ein- oder ausgeblendet werden.</small>
<div class="table-responsive">
   <table id="stats" class="table table-striped table-bordered nowrap table-hover table-condensed datatable" style="width:100%">
        <thead class="thead-dark">
            <tr>
                <th>Datum</th>
				<th>LVL</th>
                <th>EP</th>
                <th>Streuner</th>
				<th>Menschen</th>
				<th>Schü/Str</th>
                <th>GespMis</th>
                <th>Diff_GM</th>
                <th>AbgeMis</th>
				<th>Diff_AM</th>
                <th>Schüsse</th>
                <th>Haufen</th>
				<th>Helden</th>
				<th>Waffen</th>
				<th>Karten</th>
			    <th>Gerettet</th>
				<?php if (isadminormod()){ ?>
				<th>Edit</th>
				<?php } ?>
            </tr>
        </thead>
        <tbody>
	
<?php
#iterator für das differenzarray initialisieren
$i = 0;
foreach ($query as $row) {
	$datetime = new DateTime($row['date']);
	$year = $datetime->format('Y');
	$month = $datetime->format('m')-1; #highcharts monat fängt bei 0 an zu zählen!
	$day = $datetime->format('j');
	$fulldate = $datetime->format('d.m.Y H:i');
	$streuner[] = '[Date.UTC('.$year.', '.$month.', '.$day.'), '.$row['streuner'].'],'; 
	$menschen[] =  '[Date.UTC('.$year.', '.$month.', '.$day.'), '.$row['menschen'].'],'; 
	$heldenpower[] =  '[Date.UTC('.$year.', '.$month.', '.$day.'), '.$row['heldenpower'].'],'; 
	$waffenpower[] =  '[Date.UTC('.$year.', '.$month.', '.$day.'), '.$row['waffenpower'].'],'; 
	$gerettete[] =  '[Date.UTC('.$year.', '.$month.', '.$day.'), '.$row['gerettete'].'],'; 
	unset($datetime);
	#$schuestr = $row['gefeuerte_schuesse']/$row['streuner']
	$schuestr = ($row['streuner']>0 & $row['gefeuerte_schuesse']>0) ? round($row['gefeuerte_schuesse']/$row['streuner'],4) : 0;
	echo '<tr>
					<td style="text-align: right;"><nobr>'.$fulldate.'</nobr></td>
					<td style="text-align: right;">'. leveldata($row['exp']) .'</td>
					<td style="text-align: right;">'. $row['exp'] .'</td>
					<td style="text-align: right;">'. $row['streuner'] .'</td>
					<td style="text-align: right;">'. $row['menschen'] .'</td>
					<td style="text-align: right;">'. $schuestr .'</td>
					<td style="text-align: right;">'. $row['gespielte_missionen'] . '</td>
					<td style="text-align: right;">'. $diff_gespmis[$i] . '</td>
					<td style="text-align: right;">'. $row['abgeschlossene_missonen'] . '</td>
					<td style="text-align: right;">'. $diff_abgespmis[$i] . '</td>
					<td style="text-align: right;">'. $row['gefeuerte_schuesse'] . '</td>
					<td style="text-align: right;">'. $row['haufen'] . '</td>
					<td style="text-align: right;">'. $row['heldenpower'] . '</td>
					<td style="text-align: right;">'. $row['waffenpower'] . '</td>
					<td style="text-align: right;">'. $row['karten'] . '</td>	
					<td style="text-align: right;">'. $row['gerettete'] . '</td>';		
				
	if (isadminormod()){ 
		echo '<td style="text-align: center;"><a href="?action=editstat&id='.$row['id'].'&uid='.$sUser.'" role="button" title="Diese Statistik bearbeiten"><span class="fas fa-edit"></span></a></td>';
	} 
					
	echo "<tr>";
	$i++;
}

?>
	

<script type="text/javascript">
$(function () { 

    $('#container').highcharts({
        chart: {
            type: 'spline'
        },
        title: {
            text: 'Aktivität'
        },
		 yAxis: {
		title: {
           text: 'Werte'
        },
		 },
   xAxis: {
        type: 'datetime',
        dateTimeLabelFormats: { 
            month: '%e. %b',
            year: '%b'
        },
        title: {
            text: 'Legende'
        }
    },
  series: [{
        name: "Streunerkills",
        data: [
<?php
		// for ($i = 5; $i >= 1; $i--) {
	for ($x = count($streuner); $x >= 0; $x--)
{
    echo $streuner[$x];
}
?>

        ]
    }, {
        name: "Menschen",
        data: [
<?php
		
	for ($x = count($menschen); $x >= 0; $x--)
{
    echo $menschen[$x];
}
?>

        ]
    }, {
        name: "Heldenstärke",
        data: [
<?php
		
	for ($x = count($heldenpower); $x >= 0; $x--)
{
    echo $heldenpower[$x];
}
?>
        ]
    }
	, {
        name: "Waffenstärke",
        data: [
<?php
		
	for ($x = count($waffenpower); $x >= 0; $x--)
{
    echo $waffenpower[$x];
}
?>
        ]
    },
	  {
        name: "Überlebende",
        data: [
<?php
		
	for ($x = count($gerettete); $x >= 0; $x--)
{
    echo $gerettete[$x];
}
?>
        ]
    }
	
	]
});
});

var slider = document.getElementById("myRange");
var output = document.getElementById("demo");
output.innerHTML = slider.value;

slider.oninput = function() {
  output.innerHTML = this.value;
}

</script>
   
	</tbody>
	</table>

	</div>
<?php
}
}
else {echo 'Du hast noch keine Statistiken die angezeigt werden könnten.<br>Bitte habe noch etwas Geduld.';}
?>