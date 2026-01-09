<?php
require_once("db/mMailchimpTag.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$t = new mMailchimpTag($conn, $conf);
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 
<script src='lib/utils.js'></script>
<script> 
	tags=<?php print($t->get_tags()); ?>;
</script> 

<script> 
function addRow() {
	var row = tableTags.insertRow();

	var cell = row.insertCell(); 
	cell.innerHTML = '<input class="name" type="text" placeholder="Nom du tag"/><input class="idtag" type="hidden"/>';

	var cell = row.insertCell();
	cell.innerHTML = '<label class="switch"><input class="active" name="subscribe" type="checkbox"><span class="slider round"></span></label>';

	var cell = row.insertCell();
	cell.innerHTML = '<button type="button" onClick="confirmDeleteRow(this)" class="letter_button_red">X</button>';

	return row;
}

function deleteRow(item) {
	row = item.parentNode.parentNode;
	tableTags.deleteRow(row.rowIndex);
}

function confirmDeleteRow(item) {
	if (confirm("T'es sûr de vouloir supprimer ce tag?")) {
		deleteRow(item);
	}
}
function tagsTableToArray() {
	var r = Array();
	
	
	
	for (var i = 1; i < tableTags.rows.length; i++) {
		var l = {};
		var _name = tableTags.rows[i].cells[0].getElementsByClassName('name')[0].value;
		var id = tableTags.rows[i].cells[0].getElementsByClassName('idtag')[0].value;
		if (_name != null) { 
			l.name = _name;
			l.id = id;
			l.active = tableTags.rows[i].cells[1].getElementsByClassName('active')[0].checked;
			r.push(l);
		}
	}
	inputTags.value = JSON.stringify(r);
	return r;
}
function tagsArrayToTable() {
	for (var i = 0; i < tags.length; i++) {
		var ta = tags[i];
		var row = addRow();
		row.cells[0].getElementsByClassName('name')[0].value = ta.name;
		row.cells[0].getElementsByClassName('idtag')[0].value = ta.id;
		if (ta.active == 1)
			row.cells[1].getElementsByClassName('active')[0].checked = 1;
	}
} 
</script>
</head>
<body class="defaultback"> 
<div class="main">
<form action="mTagMailchimp.php" onsubmit="tagsTableToArray()" method="POST">
<?php
	print('</br><text class="title">Gestion des tags MailChimp</text><br/><br/><br/>');
	
?>
	<input type="hidden" name="tags" id="inputTags" />
	<table style="none; width: 100%;" id="tableTags">
		<tr> 
			<th>Nom</th>
			<th>Active</th> 
			<th>Suppr</th> 
		</tr>
	</table>


<button style="margin-right:0px" type="button" onClick="addRow()" class="letter_button">+</button>

<div class="padded">
<p><input type="submit" value="Enregistrer"/></p>
</form>
<p><input type="button" onclick="location.href='main.php';" value="Retour" /></p> 
</div> 
</div> 
</body>
<script>
tagsArrayToTable();
</script>
</html>
