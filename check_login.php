<?php
require_once("db/user.php");
require_once("db/mUser.php");
require_once("init.php");

// Rate limiting: max 5 attempts per IP per 15 minutes
$max_attempts = 5;
$lockout_seconds = 900;
$rate_limit_dir = sys_get_temp_dir() . '/adhesion_login';
if (!is_dir($rate_limit_dir)) {
	mkdir($rate_limit_dir, 0700, true);
}

$ip = $_SERVER['REMOTE_ADDR'];
$rate_file = $rate_limit_dir . '/' . md5($ip) . '.json';

$attempts = [];
if (file_exists($rate_file)) {
	$attempts = json_decode(file_get_contents($rate_file), true) ?: [];
	// Keep only attempts within the lockout window
	$attempts = array_values(array_filter($attempts, function($ts) use ($lockout_seconds) {
		return $ts > time() - $lockout_seconds;
	}));
}

if (count($attempts) >= $max_attempts) {
	header('Location: login.php?error=Too many attempts, try again later');
	exit();
}

$user=new User();
$u=new mUser($conn, $conf);

$u->read($_POST['login'], $user);

if ($user !== null) {
	if (password_verify($_POST['password'], $user->password)) {
		// Reset attempts on success
		if (file_exists($rate_file)) {
			unlink($rate_file);
		}
		session_regenerate_id(true);
		$_SESSION[$domain.'user'] = serialize($user);
		header('Location: main.php');
		exit();
	}
}

// Record failed attempt
$attempts[] = time();
file_put_contents($rate_file, json_encode($attempts), LOCK_EX);

header('Location: login.php?error=Wrong id/password');
exit();

?>
