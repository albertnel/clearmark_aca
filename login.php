<?php

	require_once("core/init.php");

	if (sizeof($_POST) > 0)
	{
		if (loginUser($_POST['username'], $_POST['password']))
		{
			if (isset($_SESSION['user']['user_id']) && ($_SESSION['user']['group_id'] < 5))
				header("Location: panel_superadmin.php");
			elseif ($_SESSION['user']['group_id'] == 5)
				header("Location: panel_student.php");
		}
		else
			$parse['end_script'] = '<script> alert("The username or password that you entered\r\nis incorrect, please try again."); </script>';
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$parse['logout_button'] = "";
	$html = file_get_contents('login.html');
	$result = tpParse($parse,$html);
	echo $result;

?>