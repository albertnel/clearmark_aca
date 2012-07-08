<?php

	require_once("core/init.php");

	// -== Build the tree ==--
	$parse['level_tree'] = ajaxLevelTreeDraw();
	unset($_SESSION['node']);

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('hierarchy.html');
	$result = tpParse($parse,$html);
	echo $result;

?>