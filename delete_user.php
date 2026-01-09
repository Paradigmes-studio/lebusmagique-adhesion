<?php
require_once("db/mUser.php"); 
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$u = new mUser($conn, $conf);

$user = $_GET['login'];
$u->delete_by_login($user);
header(sprintf('Location: listUsers.php?info=User %s removed', $user)); 


?>

