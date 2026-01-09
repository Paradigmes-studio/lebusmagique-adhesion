<?php
require_once("init.php");
session_regenerate_id();
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 
</head>
<body class="defaultback">
<div class="form center padded main">
<form style="padding-top:10%;" action="check_login.php" method="post">
<p><input maxlength="50" type="text" name="login" placeholder="Login" /></p>
<p><input maxlength="200" type="password" name="password" placeholder="Password" /></p>
<p><input type="submit" value="Enter"/></p>
</div>
</form>
</div>
</body>
</html>
