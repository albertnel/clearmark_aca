<?php

	require_once("core/init.php");

	$url = $DB->getcell("SELECT link_url FROM link WHERE link_id = ".$_SERVER['argv'][0]);
	$parse['livestream_iframe'] = html_entity_decode($url, ENT_QUOTES);

	$html = file_get_contents('livestream_modal.html');
	$result = tpParse($parse,$html);
	echo $result;

?>