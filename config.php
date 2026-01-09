<?php
	$conf=array(
		"res_dir"=>"res/", // trailing path required 
		"ip_mysql"=>"lebusmaghk597.mysql.db",
		"db_name_mysql"=>"lebusmaghk597",
		"user_mysql"=>"lebusmaghk597",
		"password_mysql"=>"akUYrju4vDcu",

		"email_from"=>"contact@lebusmagiquelille.fr",
		"name_from"=>"Le Bus Magique",
		"dest_email_summary_end_of_adhesion"=>"contact@lebusmagiquelille.fr", // if multiple recipients, use ',' to separate them
		"smtp_server"=>"SSL0.OVH.NET",
		"smtp_port"=>"587", // using STARTTLS hardcoded, so use the right port for STARTTLS
		"smtp_username"=>"contact@lebusmagiquelille.fr",
		"smtp_password"=>"ContactBusMagique", 
		"dev"=>true, // default false. if true, extra menus shown in main
		"send_email"=>true, // default true. if false, no email is actually sent. use in testing environment.
		//"in_memory"=>true, // default false. if true, tables are saved in memory

		// Paramétrage pour l'envoi des emails "adhesion"
		"name_company"=>"Le Bus Magique",
		"adhesion_name_company"=>"Le Bus Magique",
		"adhesion_copy"=>"", 
		"adhesion_reply"=>"contact@lebusmagiquelille.fr", 
		"programmer"=>"nicolaspetit83@hotmail.com",
		
		// Paramétrage de mailChimp
		"apiKey"=>"3bf5800cc012b0231efd1b8233d7813e-us4",
		"listId"=>"102be40ec8"
	);
 ?>
