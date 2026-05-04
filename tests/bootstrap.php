<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
set_include_path($_SERVER['DOCUMENT_ROOT']);

require_once 'db/conn.php';

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/config.php')) {
    require_once 'config.php';
} else {
    require_once 'config.dist.php';
}

// Isolate tests from the dev DB: always use adhesion_test.
$conf['db_name_mysql'] = 'adhesion_test';

// PHPUnit loads bootstrap inside a method scope, so variables defined in
// conn.php and config.dist.php are local, not global. Functions like
// check_update() use "global $current_version", so we must push everything
// to $GLOBALS explicitly.
$GLOBALS['current_version'] = $current_version;
$GLOBALS['conf'] = $conf;

try {
    $GLOBALS['conn'] = get_conn($conf);
    // Build the schema on a fresh test DB (or upgrade if migrations exist).
    check_update($GLOBALS['conn'], $conf);
} catch (PDOException $e) {
    $GLOBALS['conn'] = null;
}
