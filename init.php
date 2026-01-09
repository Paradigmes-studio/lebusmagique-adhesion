<?php
set_include_path($_SERVER['DOCUMENT_ROOT']);

require_once("db/conn.php");
require_once("config.php");
require_once("lib/debug.php");

$domain=$conf['db_name_mysql'].':';
session_start(); 
$msg="";
if (!check_conf($conf, $msg))  {
  die(sprintf("Conf incorrect: %s", $msg));
}
$conn=get_conn($conf); 

$debug = new Debug($conn, $conf);
$debug->log_(); 

?>
