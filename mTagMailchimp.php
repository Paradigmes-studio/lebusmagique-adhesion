<?php
require_once("db/mMailchimpTag.php");
require_once("init.php");
require_once("get_login_info.php"); // if not, redirect

$in_tags = json_decode($_POST['tags']);

$t = new mMailchimpTag($conn, $conf); 


//Supprimer les enregistrements correspondants aux lignes supprimées
$listTags = $t->list_mailchimp_tag();
foreach($listTags as $tag) {
	$wasDeleted = true;
	foreach($in_tags as $in_tag)
	if ($in_tag->id == $tag->id) {
			$wasDeleted = false;
			break;
	}
	if ($wasDeleted)
		$t->delete_by_id($tag->id);
}

//Mise à jour et insertion des lignes
foreach($in_tags as $in_tag) {
	$tag = new MailchimpTag();
	$tag->id = $in_tag->id;
	if ($tag->id == "")
		$tag->new  = true;
	else 
		$tag->new  = false;
	$tag->name = $in_tag->name;
	$tag->active = $in_tag->active;
	$t->write($tag);
}

	
/*************************/
/*Message de confirmation*/
/*************************/
$nextPage = "tagMailchimp.php";
$buttonName = "Ok";

$title = "Ca à marché!";
$text = "Liste des tags mise à jour</br>";

header(sprintf('Location: message.php?title=%s&text=%s&nextPage=%s&buttonName=%s',$title,$text,$nextPage,$buttonName));

