<?php
require_once("init.php");
require_once("get_login_info.php");

require_once("db/mAlertRule.php");
require_once("db/mAlertSent.php");
require_once("db/mAdhesionClient.php");
require_once("lib/EmailHandler.php");

$query_alerts = $conn->query("SELECT value FROM adh_param WHERE id = 'alerts_enabled'");
$row_alerts = $query_alerts->fetch();
$alerts_enabled = $row_alerts && $row_alerts['value'] === '1';

if ($alerts_enabled && $conf["send_email"] && !isset($_SESSION['alerts_checked'])) {
	$ruleManager = new mAlertRule($conn, $conf);
	$sentManager = new mAlertSent($conn, $conf);
	$emailHandler = new EmailHandler($conn, $conf);
	$activeRules = $ruleManager->list_active();

	foreach ($activeRules as $rule) {
		$clients = $sentManager->get_eligible_clients($rule);
		foreach ($clients as $client) {
			$error = "";
			$emailHandler->send_alert($client, $rule->email_template, $error);
			if ($error === "") {
				$sentManager->mark_sent($rule->id, $client->id);
			}
		}
	}
	$_SESSION['alerts_checked'] = true;
}
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css">
</head>
<body class="defaultback">
<div class="dashboard">

<div class="dashboard-header">
	<img src="res/logo.png" alt="Le Bus Magique" class="dashboard-logo">
	<div class="dashboard-welcome">Salut <?php echo htmlspecialchars($user->login); ?></div>
</div>

<div class="dashboard-section">
	<div class="dashboard-section-title">Adhesions</div>
	<div class="dashboard-grid">
		<a href="createAdhesionClient.php" class="dashboard-card dashboard-card--primary">
			<span class="dashboard-card-icon">+</span>
			<span class="dashboard-card-label">Nouvelle adhesion</span>
		</a>
		<a href="searchAdhesionClient.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128269;</span>
			<span class="dashboard-card-label">Recherche</span>
		</a>
		<a href="exportClient.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128196;</span>
			<span class="dashboard-card-label">Export</span>
		</a>
		<a href="listAdhesionType.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#9776;</span>
			<span class="dashboard-card-label">Types</span>
		</a>
		<a href="tagMailchimp.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#9993;</span>
			<span class="dashboard-card-label">Mailchimp</span>
		</a>
		<a href="statistics.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128202;</span>
			<span class="dashboard-card-label">Statistiques</span>
		</a>
		<a href="alertRules.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128276;</span>
			<span class="dashboard-card-label">Alertes</span>
		</a>
		<a href="emailTemplates.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#9993;</span>
			<span class="dashboard-card-label">Templates</span>
		</a>
	</div>
</div>

<div class="dashboard-section">
	<div class="dashboard-section-title">Administration</div>
	<div class="dashboard-grid">
		<a href="listUsers.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128101;</span>
			<span class="dashboard-card-label">Utilisateurs</span>
		</a>
		<a href="changePassword.php" class="dashboard-card">
			<span class="dashboard-card-icon">&#128274;</span>
			<span class="dashboard-card-label">Mot de passe</span>
		</a>
	</div>
</div>

<div class="dashboard-footer">
	<a href="mLogout.php" class="dashboard-logout">Deconnexion</a>
</div>

</div>
</body>
</html>
