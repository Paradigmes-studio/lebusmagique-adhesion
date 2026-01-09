<?php
require_once("db/mAdhesionClient.php");
require_once("init.php");
require_once("config.php");

// output headers so that the file is downloaded rather than displayed
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="demo.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('N° adhérent', 'Prénom', 'Nom', 'email', 'Type adhésion', 'date de début', 'date de fin', 'newsletter'));
 
 $where = 'where date_debut>="' . $_POST['begining'] . '" and date_debut<="' . $_POST['end'] . '"';

//Liste des adhérent 
$ac = new mAdhesionClient($conn, $conf);
$adhesions = $ac->search($where);

//Remplissage du tableau
foreach($adhesions as $adhesion)
	fputcsv($file, array($adhesion->id, $adhesion->first_name, $adhesion->last_name, $adhesion->email, $adhesion->adhesion_type, $adhesion->date_debut, $adhesion->date_fin, $adhesion->newsletter));


//header(sprintf('Location: main.php')); 
?>