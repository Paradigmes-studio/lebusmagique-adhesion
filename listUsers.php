<?php
require_once("db/mUser.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 
<script src='lib/utils.js'></script>
</head>
<body class="defaultback"> 
<div class="main">
<div class="list" style="width: 100%; max-width:100%">
<table style="table-layout: fixed;width: 100%">
<tr><th style="width:50%">Name</th><th>Operation</th></tr>

<?php
	$u = new mUser($conn, $conf);
	$edited_users = $u->list_users(); // we don't list ourselves
	foreach ($edited_users as $edited_user) {
		print("<tr class='spacer'></tr>\n");
		print("<tr>");
		printf("<td style='overflow:hidden; width:50%%;'>%s</td>\n", $edited_user->login);
		printf("<td style='overflow:hidden; width:50%%;text-align:center;'>\n");
		if ($edited_user->login != $user->login) {
			printf("<button onclick=\"location='editUser.php?login=%s'\" class=\"letter_button\">E</button>\n", $edited_user->login);
			printf("<button type=\"button\" onclick=\"confirm_action('Do you want to delete the login %s?', 'delete_user.php?login=%s')\" class=\"letter_button_red\">X</button>\n", $edited_user->login, $edited_user->login);
		}
		printf("</td>\n");
		printf("</tr>\n");
	}
?> 
</table>
</table>
<button onclick="location='editUser.php'" class="letter_button">+</button>
</div>
<div class="padded">
<p><input type="button" onclick="location.href='main.php';" value="Retour" /></p> 
</div> 
</div> 
</body>
</html>
