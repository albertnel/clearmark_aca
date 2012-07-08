<?php

	require_once("core/init.php");

	if ($_SERVER['argv'][0] != "")
		$course_id = $_SESSION['course']['course_id'] = $_SERVER['argv'][0];
	else
		$course_id = $_SESSION['course']['course_id'];

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('course_notifications.html');
	$result = tpParse($parse,$html);
	echo $result;

?>