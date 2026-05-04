<?php

$current_version = 10;
$in_memory = False;

function get_in_memory($conf) {
	$r="";
	if (isset($conf['in_memory'])) {
		if ($conf['in_memory']) {
			$r="data directory='/tmp/'";
		}
	}
	return $r;
}

function set_version($conn, $version) {
	$query = $conn->prepare("REPLACE INTO adh_param(id, value) VALUES ('version', :value);");
	$query->bindParam(":value", $version, PDO::PARAM_STR); 
	$query->execute();
} 

function raz_db($conn) {
	$query = $conn->query("DROP TABLE IF EXISTS adh_email_template");
	$query = $conn->query("DROP TABLE IF EXISTS adh_alert_sent");
	$query = $conn->query("DROP TABLE IF EXISTS adh_alert_rule");
	$query = $conn->query("DROP TABLE IF EXISTS adh_adhesion_client"); 
	$query = $conn->query("DROP TABLE IF EXISTS adh_adhesion_type_description");
	$query = $conn->query("DROP TABLE IF EXISTS adh_adhesion_type");
	$query = $conn->query("DROP TABLE IF EXISTS adh_user"); 
	$query = $conn->query("DROP TABLE IF EXISTS adh_lang");
	$query = $conn->query("DROP TABLE IF EXISTS adh_param");
	$query = $conn->query("DROP TABLE IF EXISTS adh_debug_detail"); 
	$query = $conn->query("DROP TABLE IF EXISTS adh_debug"); 
}

function clean_db($conn) {
	$query = $conn->query("DELETE FROM adh_adhesion_client");
	$query = $conn->query("DELETE FROM adh_adhesion_type_description");
	$query = $conn->query("DELETE FROM adh_adhesion_type");
	$query = $conn->query("DELETE FROM adh_user WHERE login != 'admin'"); 
	$query = $conn->query("DELETE FROM adh_debug_detail"); 
	$query = $conn->query("DELETE FROM adh_debug"); 
}

function create_table_lang($conn, $conf) { 
	// table langs
	$query = $conn->query(sprintf("CREATE TABLE adh_lang(id VARCHAR(2) PRIMARY KEY, name VARCHAR(200) NOT NULL) %s;", get_in_memory($conf)));

	$conn->beginTransaction(); 
	$query = $conn->prepare("INSERT INTO adh_lang(id, name) VALUES (:id, :name);");
	$first = true;
	if (($handle = fopen("res/lang.csv", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			if ($first) {
				$first = 0; // do not import header
			}
			else {
				$query->bindParam(':id', $data[0]);
				$query->bindParam(':name', $data[1]);
				$query->execute();
			}
		}
		fclose($handle);
	}
	else {
		throw new Exception("Cannot import lang list");
	} 
	$conn->commit();
}

function init_db_from_scratch($conn, $conf) {
	// table params
	$query = $conn->query(sprintf("CREATE TABLE adh_param(id VARCHAR(50) PRIMARY KEY, value VARCHAR(100)) %s;", get_in_memory($conf)));

	// list of langs
	create_table_lang($conn, $conf);

	// table adh_user
	$query = $conn->query(sprintf("CREATE TABLE adh_user(login VARCHAR(50) NOT NULL, password VARCHAR(200) NOT NULL, PRIMARY KEY(login)) %s;", get_in_memory($conf)));
	$query = $conn->prepare("INSERT INTO adh_user(login, password) VALUES ('admin', :password);");
	$query->bindValue(':password', password_hash('admin', PASSWORD_DEFAULT), PDO::PARAM_STR);
	$query->execute();

	// table adh_adhesion_type
	$query = $conn->query(sprintf("CREATE TABLE adh_adhesion_type(id INTEGER AUTO_INCREMENT, name VARCHAR(200) NOT NULL, price FLOAT, email_welcome VARCHAR(100), duration INTEGER, PRIMARY KEY(id)) %s;", get_in_memory($conf)));
	
	// table description adhesion type par langue
	$query = $conn->query(sprintf("CREATE TABLE adh_adhesion_type_description(adhesion_type INTEGER, lang VARCHAR(2), description TEXT, PRIMARY KEY(adhesion_type, lang), FOREIGN KEY fk_adh_adhesion_type_description_adh_adhesion_type(adhesion_type) REFERENCES adh_adhesion_type(id) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY fk_adh_adhesion_type_description_lang(lang) REFERENCES adh_lang(id)) %s;", get_in_memory($conf)));

	// table adh_adhesion_client
	$conn->query(sprintf("CREATE TABLE adh_adhesion_client(id INTEGER AUTO_INCREMENT, last_name VARCHAR(200), first_name VARCHAR(200), email VARCHAR(200), adhesion_type VARCHAR(200), date_debut DATETIME, date_fin DATETIME, newsletter BINARY, referral_source VARCHAR(200), edit_token CHAR(32) NULL, PRIMARY KEY(id), UNIQUE KEY idx_edit_token (edit_token)) %s;", get_in_memory($conf)));

	$conn->query(sprintf("CREATE TABLE adh_debug(id INTEGER AUTO_INCREMENT, in_progress BOOLEAN, PRIMARY KEY (id)) %s;", get_in_memory($conf)));
	$conn->query(sprintf("CREATE TABLE adh_debug_detail(id INTEGER AUTO_INCREMENT, debug INTEGER, request VARCHAR(100), get_ TEXT, post_ TEXT, PRIMARY KEY (id), FOREIGN KEY fk_debug_detail_debug(debug) REFERENCES adh_debug(id) ON DELETE CASCADE ON UPDATE CASCADE) %s;", get_in_memory($conf)));

	$conn->query(sprintf("CREATE TABLE adh_email_template(
		id INTEGER AUTO_INCREMENT,
		name VARCHAR(200) NOT NULL,
		subject VARCHAR(200) NOT NULL,
		body TEXT NOT NULL,
		PRIMARY KEY(id)
	) %s", get_in_memory($conf)));

	global $current_version;
	return $current_version;
}

function migrate_1_2($conn, $conf) {

	$conn->query(sprintf("CREATE TABLE adh_mailchimp_tag(id INTEGER AUTO_INCREMENT, name VARCHAR(200), active BOOLEAN, PRIMARY KEY(id)) %s;", get_in_memory($conf)));

	return 2;
}

function migrate_2_3($conn, $conf) {
	$conn->query("ALTER TABLE adh_adhesion_client ADD COLUMN referral_source VARCHAR(200) NULL");
	return 3;
}

function migrate_3_4($conn, $conf) {
	$conn->query(sprintf("CREATE TABLE adh_alert_rule(
		id INTEGER AUTO_INCREMENT,
		name VARCHAR(200) NOT NULL,
		trigger_type ENUM('before', 'on', 'after') NOT NULL,
		trigger_days INTEGER NOT NULL DEFAULT 0,
		email_template VARCHAR(200) NOT NULL,
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY(id)
	) %s", get_in_memory($conf)));

	$conn->query(sprintf("CREATE TABLE adh_alert_sent(
		id INTEGER AUTO_INCREMENT,
		alert_rule_id INTEGER NOT NULL,
		adhesion_client_id INTEGER NOT NULL,
		sent_at DATETIME NOT NULL,
		PRIMARY KEY(id),
		UNIQUE KEY uq_alert_sent (alert_rule_id, adhesion_client_id),
		FOREIGN KEY fk_alert_sent_rule(alert_rule_id) REFERENCES adh_alert_rule(id) ON DELETE CASCADE,
		FOREIGN KEY fk_alert_sent_client(adhesion_client_id) REFERENCES adh_adhesion_client(id) ON DELETE CASCADE
	) %s", get_in_memory($conf)));

	return 4;
}

function migrate_4_5($conn, $conf) {
	$conn->query(sprintf("CREATE TABLE adh_email_template(
		id INTEGER AUTO_INCREMENT,
		name VARCHAR(200) NOT NULL,
		subject VARCHAR(200) NOT NULL,
		body TEXT NOT NULL,
		PRIMARY KEY(id)
	) %s", get_in_memory($conf)));

	// Import existing Adhesion.html template
	$file = 'res/Adhesion.html';
	if (file_exists($file)) {
		$body = file_get_contents($file);
		$query = $conn->prepare("INSERT INTO adh_email_template(name, subject, body) VALUES (:name, :subject, :body)");
		$query->bindValue(':name', 'Adhesion', PDO::PARAM_STR);
		$query->bindValue(':subject', 'Bienvenue à bord Moussaillon!', PDO::PARAM_STR);
		$query->bindValue(':body', $body, PDO::PARAM_STR);
		$query->execute();
		$template_id = $conn->lastInsertId();

		// Convert all email_welcome references in adh_adhesion_type to template ID
		$conn->query(sprintf("UPDATE adh_adhesion_type SET email_welcome = '%d' WHERE email_welcome NOT REGEXP '^[0-9]+$'", $template_id));

		// Convert all email_template references in adh_alert_rule to template ID
		$conn->query(sprintf("UPDATE adh_alert_rule SET email_template = '%d' WHERE email_template NOT REGEXP '^[0-9]+$'", $template_id));
	}

	return 5;
}

function migrate_5_6($conn, $conf) {
	// Template: Expiration proche
	$body_soon = file_get_contents('res/ExpirationProche.html');
	if ($body_soon !== false) {
		$query = $conn->prepare("INSERT INTO adh_email_template(name, subject, body) VALUES (:name, :subject, :body)");
		$query->bindValue(':name', 'Expiration proche', PDO::PARAM_STR);
		$query->bindValue(':subject', 'Ton adhésion au Bus Magique arrive bientôt à son terme !', PDO::PARAM_STR);
		$query->bindValue(':body', $body_soon, PDO::PARAM_STR);
		$query->execute();
	}

	// Template: Adhésion expirée
	$body_expired = file_get_contents('res/AdhesionExpiree.html');
	if ($body_expired !== false) {
		$query = $conn->prepare("INSERT INTO adh_email_template(name, subject, body) VALUES (:name, :subject, :body)");
		$query->bindValue(':name', 'Adhésion expirée', PDO::PARAM_STR);
		$query->bindValue(':subject', 'Ton adhésion au Bus Magique a expiré !', PDO::PARAM_STR);
		$query->bindValue(':body', $body_expired, PDO::PARAM_STR);
		$query->execute();
	}

	return 6;
}

function migrate_6_7($conn, $conf) {
	// No-op: alert rules feature removed in v9 (Brevo automations take over).
	return 7;
}

function migrate_7_8($conn, $conf) {
	$conn->query("DROP TABLE IF EXISTS adh_mailchimp_tag");
	return 8;
}

function migrate_8_9($conn, $conf) {
	$conn->query("DROP TABLE IF EXISTS adh_alert_sent");
	$conn->query("DROP TABLE IF EXISTS adh_alert_rule");
	$query = $conn->prepare("DELETE FROM adh_param WHERE id = 'alerts_enabled'");
	$query->execute();
	return 9;
}

function migrate_9_10($conn, $conf) {
	$conn->query("ALTER TABLE adh_adhesion_client ADD COLUMN edit_token CHAR(32) NULL");
	$conn->query("ALTER TABLE adh_adhesion_client ADD UNIQUE KEY idx_edit_token (edit_token)");

	$update = $conn->prepare("UPDATE adh_adhesion_client SET edit_token = :token WHERE id = :id");
	$rows = $conn->query("SELECT id FROM adh_adhesion_client WHERE edit_token IS NULL");
	while ($row = $rows->fetch()) {
		$update->bindValue(':token', bin2hex(random_bytes(16)), PDO::PARAM_STR);
		$update->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
		$update->execute();
	}

	return 10;
}

function get_version($conn) {
	$query = $conn->query("SHOW TABLES;");
	$count = $query->rowCount();
	if ($count == 0) {
		$version = 0; // first launch
	}
	else { 
		$query = $conn->query("SHOW TABLES LIKE 'adh_param';");
		$count = $query->rowCount();
		if ($count == 0) {
			$version = 0;
		} else {
			$query = $conn->query("SELECT value FROM adh_param WHERE id = 'version';");
			if ($query->rowCount() != 1) {
				$version = 0;
			} else {
				$res=$query->fetch();
				$version=$res['value']; 
			}
		}
	} 
	return $version;
}

function check_update($conn, $conf) {
	global $current_version;
	$db_version = get_version($conn);
	if ($db_version > $current_version) {
		throw new Exception("database version > script version");
	}
	if ($db_version != $current_version) {
		if ($db_version == 0) {
			$db_version = init_db_from_scratch($conn, $conf);
		}
		if ($db_version == 1) { $db_version=migrate_1_2($conn, $conf); }
		if ($db_version == 2) { $db_version=migrate_2_3($conn, $conf); }
		if ($db_version == 3) { $db_version=migrate_3_4($conn, $conf); }
		if ($db_version == 4) { $db_version=migrate_4_5($conn, $conf); }
		if ($db_version == 5) { $db_version=migrate_5_6($conn, $conf); }
		if ($db_version == 6) { $db_version=migrate_6_7($conn, $conf); }
		if ($db_version == 7) { $db_version=migrate_7_8($conn, $conf); }
		if ($db_version == 8) { $db_version=migrate_8_9($conn, $conf); }
		if ($db_version == 9) { $db_version=migrate_9_10($conn, $conf); }
	}
	set_version($conn, $db_version);
}

function get_conn_attributes($string, $user, $password, $conf) {
	$conn = new PDO($string, $user, $password); // encoding ?? which one ??? how does that works ?
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	check_update($conn, $conf);
	return $conn; 
} 

function get_dev($conf) {
	$res=false;
	if (isset($conf['dev'])) {
		$res=$conf['dev'];
	}
	return $res;
}

function get_conn($conf) {
	return get_conn_attributes(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $conf['ip_mysql'], $conf['db_name_mysql']), $conf['user_mysql'], $conf['password_mysql'], $conf);
} 

function find_in_conf($conf, $param, &$msg) {
	if (!array_key_exists($param, $conf)) {
		$msg .= sprintf("missing parameter %s, ", $param);
	}
}

function check_conf($conf, &$msg) {
	$msg = "";
	find_in_conf($conf, "res_dir", $msg);
	find_in_conf($conf, "ip_mysql", $msg);
	find_in_conf($conf, "db_name_mysql", $msg);
	find_in_conf($conf, "user_mysql", $msg);
	find_in_conf($conf, "password_mysql", $msg);
	find_in_conf($conf, "name_company", $msg);
	find_in_conf($conf, "email_from", $msg);
	find_in_conf($conf, "dest_email_summary_end_of_adhesion", $msg);
	find_in_conf($conf, "smtp_server", $msg);
	find_in_conf($conf, "smtp_port", $msg);
	find_in_conf($conf, "smtp_username", $msg);
	find_in_conf($conf, "smtp_password", $msg);
	if ($msg != "") {
		$msg = substr($msg, 0, -2);
	}
	return ($msg == "");
}

?>
