<?php

	require_once("core/init.php");

	$parse = $DB->getrow("SELECT title, content FROM notification WHERE notification_id = ".$_GET['id']);

	$html = file_get_contents('notification_view.html');
	$result = tpParse($parse,$html);
	echo $result;

?>