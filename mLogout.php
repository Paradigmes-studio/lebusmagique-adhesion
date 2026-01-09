<?php
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect


session_destroy();

header('Location: login.php'); 
?>
