<?php
require_once("init.php");
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> 
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css"> 

<style>
.input_date {
	padding: 0px !important;
	margin: 0px !important;
	font-size: 0.9rem !important;
}
</style>
</head>
<body class="defaultback"> 
<div class="form padded main">
<?php
	$title = htmlspecialchars($_GET['title'] ?? '');
	$text = htmlspecialchars($_GET['text'] ?? '');
	$buttonName = htmlspecialchars($_GET['buttonName'] ?? '');
	$nextPage = $_GET['nextPage'] ?? 'main.php';
	$adhesionId = (int)($_GET['adhesionId'] ?? 0);

	// Whitelist des pages internes autorisees
	$allowedPages = ['main.php', 'createAdhesionClient.php', 'searchAdhesionClient.php', 'listAdhesionClient.php', 'login.php', 'tagMailchimp.php'];
	if (!in_array(parse_url($nextPage, PHP_URL_PATH), $allowedPages)) {
		$nextPage = 'main.php';
	}

	printf('</br><text class="title">%s</text><br/><br/><br/>', $title);

	printf('<text>%s</text>', $text);
	printf('<div style="text-align:center !important;"><br><img src="http://lebusmagiquelille.fr/adhesion/res/Carte%d.jpg" style="width:300px !important;text-align:center !important;"/></div>', $adhesionId);

	printf('<p><input type="button" onclick="location.href=\'%s\';" value="%s" /></p>', htmlspecialchars($nextPage), $buttonName); 

?>
</div>
</body>
</html>








<?php

require_once('config.php');

Class Display {
	private $conn;
	private $conf;

	public function __construct($conn, $conf) {
		$this->conn = $conn;
		$this->conf = $conf;
	}
	
	function generate_header(){
	
		printf('<html>');
		printf('<head>');
		printf('<meta charset="utf-8" />');
		printf('<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" /> ');
		printf('<meta name="robots" content="noindex">');
		printf('<link rel="stylesheet" type="text/css" href="mobile.css"> ');

		printf('<style>');
		printf('.input_date {');
			printf('padding: 0px !important;');
			printf('margin: 0px !important;');
			printf('font-size: 0.9rem !important;');
		printf('}');
		printf('</style>');
		printf('</head>');
		printf('<body class="defaultback">');
		printf('<div class="form padded main">"');

	}
	
	function internal_error($code, $original_url) {
		printf("<center>");
		printf("<p style='font-size:1.5em;font-weight:bold'>Désolé</p>\n");
		printf("<br>\n");
		printf("Une erreur est survenue: %s.<br>", $code);
		printf("Veuillez réesayer. Si cela ne fonctionne toujours pas, veuillez contacter <a href='%s'>%s</a>.<br>", $this->conf["booking_reply"], $this->conf["booking_reply"]);
		printf("<div class='center_div'>");
		printf("<input type='button' style='text-align:center' onclick='window.location=\"%s\";' value='Réssayer'/>\n", $original_url); 
		printf("</div>\n"); 
		printf("</center>");
		printf("</div>\n"); 
	}

	function email_sent($adhesionClient, $original_url) {
			//generate_header();
			printf("<center>");
			printf("<p style='font-size:1.5em;font-weight:bold'>Félicitations</p>\n");
			printf("<br>\n");
			printf("Vous voilà officiellement à bord du Bus Magique<br>\n");
			printf("Théroiquement vous avez reçus votre carte d'adhésion sur %s", $adhesionClient->email);
			printf("<br>Et maintenant on profite !<br>\n");
			printf("<div class='center_div'>\n");
			printf("<input type='button' onclick='window.location=\"%s\";' value='Nouvelle adhésion'/>\n", $original_url); 
			printf("</div>\n"); 
			printf("</center>");
			printf("</div>\n"); 
			printf("</body>\n");
			printf("</html\n");
	}
}

