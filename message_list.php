<?php

	require_once("core/init.php");

	// -- Page referer --
	if ($_SESSION['user']['group_id'] == 5)
		$parse['referer'] = "panel_student.php";
	else
		$parse['referer'] = "panel_superadmin.php";

	unset($_SESSION['message']);

	// -- If user_id in query, show only messages with that user --
	if ($_SERVER['argv'][0] != "")
		$_SESSION['message']['user_id'] = $_SERVER['argv'][0];

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('message_list.html');
	$result = tpParse($parse,$html);
	echo $result;

?>