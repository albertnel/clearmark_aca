<?php

	require_once("core/init.php");

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('clean.html');
	$result = tpParse($parse,$html);
	echo $result;

?>