<?php

	require_once("core/init.php");

	// -- Logout --
	if ($_POST['logout'])
		logoutUser();

	if ($_SESSION['user']['user_id'] == "")
		header("Location: login.php");
	else if ($_SESSION['user']['group_id'] == 5)
		header("Location: panel_student.php");
	else
		header("Location: panel_superadmin.php");

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('index.html');
	$result = tpParse($parse,$html);
	echo $result;

?>