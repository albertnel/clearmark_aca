<?php

	require_once("core/init.php");

	$parse = $DB->getrow("SELECT CONCAT(name, ' ', surname) as 'full_name', email, phone_number, office
								FROM user_profile
								WHERE user_id = ".$_GET['id']);
	$parse['email'] = 'Email: <a href="mailto:'.$parse['email'].'">'.$parse['email'].'</a>';
	$parse['office'] = 'Office: '.$parse['office'];

	$html = file_get_contents('lecturer_info_view.html');
	$result = tpParse($parse,$html);
	echo $result;

?>