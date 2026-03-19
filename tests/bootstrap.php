<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
set_include_path($_SERVER['DOCUMENT_ROOT']);

require_once 'db/conn.php';

require_once 'config.dist.php';

// PHPUnit loads bootstrap inside a method scope, so variables defined in
// conn.php and config.dist.php are local, not global. Functions like
// check_update() use "global $current_version", so we must push everything
// to $GLOBALS explicitly.
$GLOBALS['current_version'] = $current_version;
$GLOBALS['conf'] = $conf;
$GLOBALS['conn'] = get_conn($conf);