<?php

	require_once("core/init.php");

	$parse = $DB->getrow("SELECT * FROM jqcalendar WHERE Id = ".$_SERVER['argv'][0]);

	$html = file_get_contents('event_view.html');
	$result = tpParse($parse,$html);
	echo $result;

?>