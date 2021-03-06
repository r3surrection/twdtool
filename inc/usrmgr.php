<?php
include "verify.php";
msgbox($_GET['msg']);
$uid =0;
$uid = ((isset($_POST['edituid'])&&$_POST['edituid'] > "") ?$_POST['edituid'] : (isset($_GET['uid'])?$_GET['uid']:0)); 

if (isset($_GET['uid']) & !user_exists($uid)){
	echo '<div class="alert alert-danger">
  <strong>Abbruch</strong> Gewählte User-ID <b>'.$uid.'</b> existiert nicht. Funktion nicht ausführbar</div>	<a href="?action=usrmgr" name="back" class="btn btn-info" role="button">Zurück</a>'; exit();

}

$filter = '';
$optall = '';
$optactive = '';
$optinactive ="";
$where_grouplimit = '';
$and_grouplimit = '';
$anz = '';
$gidselected = '';
$selected = '';

if (!isdev()){
$gidfilter = 'gid = '.$_SESSION['gid'];
$where_grouplimit = ' WHERE '.$gidfilter;
$and_grouplimit = ' AND '.$gidfilter;
}

if (isdev() && $_POST['gid']){
if (is_numeric($_POST['gid'])){
$and_grouplimit = ' AND gid = '.$_POST['gid'];
$where_grouplimit = ' WHERE gid = '.$_POST['gid'];
}
if ($_POST['gid'] == "allgrp"){
$and_grouplimit = '';	
}

if ($_POST['gid'] == "uc"){
$and_grouplimit =  ' AND gid = 0';
$where_grouplimit = ' WHERE gid = 0';
}
}


if ($_POST['optactive'] == "all"){
$filter = $where_grouplimit;
$optall = "checked";
}

elseif ($_POST['optactive'] == "active" OR !$_POST){
$filter = 'WHERE active = 1'.$and_grouplimit;
$optactive = "checked";
}
elseif ($_POST['optactive'] == "inactive"){
$filter = 'WHERE active = 0'.$and_grouplimit;
$optinactive = "checked";
}


$cqry = $pdo->query('SELECT count(id) as cnt FROM `'.$config->db_pre.'users` '.$filter.' ORDER BY ign ASC');
$anz = $cqry->fetchColumn();


?>


<form class="form-vertical" role="form" method = "POST" action = "?action=usrmgr" >
    <div class="form-group">
<label class="radio-inline"><input type="radio" value = "all" name="optactive" <?=$optall;?> onchange="this.form.submit()">Alle</label>
<label class="radio-inline"><input type="radio" value = "active" name="optactive" <?=$optactive;?> onchange="this.form.submit()">Aktive</label>
<label class="radio-inline"><input type="radio" value = "inactive" name="optactive" <?=$optinactive;?> onchange="this.form.submit()">Inaktive</label>
<span style="padding-left:5px">[<?=$anz;?> User]</span>
</div>

 <div class="row">
<?php if (isdev()) {

 ?>
	<div class="form-group col-xs-6" style="width:auto;">
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
	
	 </select> </div>
 <?php } ?>

<div class="form-group col-xs-6" style="width:auto;">
<label for="inputUser" class = "control-label">Mitglied <?php if($uid > 0){ echo 'Nr: '.$uid.' ['.getuname($uid).']';} ?> bearbeiten:</label>
      <select onchange="this.form.submit()" id="inputUser" name = "edituid" class = "form-control" style="width:auto;min-width:200px;">
	 <option value="">--Wählen--</option>
	<?php
	$sql = 'SELECT id, ign FROM `'.$config->db_pre.'users` '.$filter.' ORDER BY ign ASC';
		
    foreach ($pdo->query($sql) as $row) {
		if ($uid == $row['id'])
	{
	$selected = ' selected';
	}
       echo '<option value="'.$row['id'].'" '.$selected.'>'.$row['ign'].'</option>';
	    $selected = '';
    }
	?>
	
	 </select> </div></div>


  



</form>

<?php
if($uid >""){
$statement = $pdo->prepare("SELECT id,gid,ign,notes,telegram,role,created_at,updated_at,active FROM ".$config->db_pre."users WHERE id = :id");

$result = $statement->execute(array('id' => $uid));
$user = $statement->fetch();
?>
     Erstellt: <?php echo date("d.m.Y", strtotime($user['created_at'])); ?>
 | Update: <?php echo ($user['updated_at'] > "0" ? date("d.m.Y", strtotime($user['updated_at'])) : "keines"); ?>
 
<form action="?action=usrmgr" method = "POST" autocomplete="no">
  <input type = "hidden" name = "edituid" type="text" value = "<?php echo ($uid);?>">
  <div class="form-group">
    <label for="telegram">Ingame-Name:</label>
    <input type="text" class="form-control" id="ign" name = "ign"  value = "<?php echo $user['ign']; ?>">
  </div>  
  <?php if (isdev()){ ?>
    <div class="form-group">
    <label for="grp">Gruppe:</label>
      <select id="group" name = "gid" class = "form-control" style="width:auto;min-width:200px;">
	<?php
	$sql = 'SELECT id, tag, name FROM `'.$config->db_pre.'groups` ORDER BY name ASC';
	if ($user['gid'] === 0){
	 echo '<option value="0" selected>[---] Unkategorisiert</option>';
	}		

    foreach ($pdo->query($sql) as $group) 
	{
	if ($user['gid'] == $group['id']){
	  $selected = ' selected';
	}
    echo '<option value="'.$group['id'].'" '.$selected.'>['.$group['tag'].'] '.$group['name'].'</option>';
	$selected = '';
    }
	?>
	 </select> 
    </div>
  <?php } ?>
	
  <div class="form-group">
	<input type="password" style="display:none"> <!-- Prevent Password-Autofill -->
    <label for="pwd">Passwort:</label>
	<input autocomplete="new-password" type="password" class="form-control" id="pwd" name="pwd" value = "">
  </div>
  
  <?php if (isadmin()){  ?>
  <div class="form-group">
    <label for="role" class = "control-label">Stattool-Rechte:</label>
     <select  id="role" name = "role" class = "form-control">	    
<?php 
foreach($rights as $key => $value)
{
	if ($user['role'] == $key)
	{
		$selected = ' selected';
    }
	
 echo '<option value="'.$key.'" '.$selected.'>'.ucfirst($value).'</option>';
 $selected = '';
}
?>
	 </select>   
   </div>
  <?php } ?>
  <div class="form-group">
    <label for="telegram">Telegram:</label>
    <input type="text" class="form-control" id="telegram" name = "telegram"  value = "<?php echo $user['telegram']; ?>"> 
  </div>
  <div class="form-group">
      <label for="notes">Notizen:</label>
	<textarea class="form-control input-md"  rows="9" name = "notes"><?php echo $user['notes']; ?></textarea>
  </div>
  <div class="funkyradio">
        <div class="funkyradio-success">
            <input type="checkbox" name="active" id="active" <?php echo ($user['active'] == 1 ? "checked" : ""); ?>/>
            <label for="active" id = "active_p" ><?php echo ($user['active'] == 1 ? "Aktiv" : "Inaktiv"); ?></label>
        </div>
    </div>
  <div class="clearfix">
	  <div class="pull-left">
		<button type="submit" name = "updateuser" class="btn btn-success">Update</button>
	  </div>
<?php 
if ((ismod() & $user['role'] == 3) OR isadmin()){
?>
	  <div class="pull-right">
		<a href="?action=removeusr&uid=<?php echo ($uid);?>" name="removeuser" class="btn btn-danger" role="button">Nutzer entfernen</a>
	  </div>
<?php } ?>
  </div>
</form>
<?php
}

if(isset($_POST['updateuser']) && is_numeric($_POST['edituid'])){

$curr_datetime =date("Y-m-d H:i:s");

#ist dev - darf alles
#hat pw geändert
if (isdev()){
if($_POST['pwd'] > "")
{
	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users SET
	gid = :gid,
	ign = :ign,
	role = :role,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	passwd = :passwd,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':gid' => $_POST['gid'],
						  ':ign' => $_POST['ign'],
						  ':role' => $_POST['role'],
						  ':telegram' => $_POST['telegram'],
						  ':notes' => $_POST['notes'],
						  ':updated_at' => $curr_datetime,
						  ':passwd' => password_hash($_POST['pwd'], PASSWORD_DEFAULT),
						  ':active' => ($_POST['active'] ? 1 : 0),
						  ':id' => $_POST['edituid']));
}
#ist dev - darf alles
#hat pw nicht geändert
else
{
	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users SET
	gid = :gid,
	ign = :ign,
	role = :role,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':gid' => $_POST['gid'],
						  ':ign' => $_POST['ign'],
						  ':role' => $_POST['role'],
						  ':telegram' => $_POST['telegram'],
						  ':notes' => $_POST['notes'],
						  ':updated_at' => $curr_datetime,
						  ':active' => ($_POST['active'] ? 1 : 0),
						  ':id' => $_POST['edituid']));
}
}
#ist gruppenadmin - darf gruppe nicht ändern, sonst alles
#hat pw geändert
elseif (isadmin()){
if($_POST['pwd'] > "")
{

	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users SET
	ign = :ign,
	role = :role,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	passwd = :passwd,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':ign' => $_POST['ign'],
						 ':role' => $_POST['role'],
						 ':telegram' => $_POST['telegram'],
						 ':notes' => $_POST['notes'],
						 ':updated_at' => $curr_datetime,
						 ':passwd' => password_hash($_POST['pwd'], PASSWORD_DEFAULT),
						 ':active' => ($_POST['active'] ? 1 : 0),
						 ':id' => $_POST['edituid']));
}

#ist gruppenadmin
#hat pw nicht geändert	
else
{
	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users
	SET ign = :ign,
	role = :role,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':ign' => $_POST['ign'],
						  ':role' => $_POST['role'],
						  ':telegram' => $_POST['telegram'],
						  ':notes' => $_POST['notes'],
						  ':updated_at' => $curr_datetime,
						  ':active' => ($_POST['active'] ? 1 : 0),
						  ':id' => $_POST['edituid']));
}
}

#ist moderator, darf gruppe nicht ändern, darf keine rechte setzen
#hat pw nicht geändert
elseif (ismod()){
if($_POST['pwd'] > "")
{

	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users SET
	ign = :ign,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	passwd = :passwd,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':ign' => $_POST['ign'],
						 ':telegram' => $_POST['telegram'],
						 ':notes' => $_POST['notes'],
						 ':updated_at' => $curr_datetime,
						 ':passwd' => password_hash($_POST['pwd'], PASSWORD_DEFAULT),
						 ':active' => ($_POST['active'] ? 1 : 0),
						 ':id' => $_POST['edituid']));
}

#ist moderator, darf gruppe nicht ändern, darf keine rechte setzen
#hat pw geändert	
else
{
	$query = $pdo->prepare('UPDATE '.$config->db_pre.'users
	SET ign = :ign,
	telegram = :telegram,
	notes = :notes,
	notetime = NOW(),
	updated_at = :updated_at,
	active = :active
	WHERE id = :id');
	
	$query->execute(array(':ign' => $_POST['ign'],
						  ':telegram' => $_POST['telegram'],
						  ':notes' => $_POST['notes'],
						  ':updated_at' => $curr_datetime,
						  ':active' => ($_POST['active'] ? 1 : 0),
						  ':id' => $_POST['edituid']));
}
}

header('Refresh: 0; URL=?action=usrmgr&uid='.$_POST['edituid'].'&msg=updatesuccess');
}
?>