<?php

	require_once("core/init.php");

	if (isset($_SESSION['subject'])) unset($_SESSION['subject']);

	if (sizeof($_POST) > 0)
	{
		if ($_POST['course_id'] == "") unset($_POST['course_id']);
		$DB->dbsave("course", $_POST);
		$course_id = $_POST['course_id'];
		logAction("save", "course", $course_id, "Save course");

		$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']." AND course_id = ".$course_id);
		if ($count == 0)
			$DB->put("INSERT INTO user_rel_course VALUES ('".$_SESSION['user']['user_id']."', '".$course_id."', 1)");


		$parse['end_script'] = '<script>
											ID("submit_msg").innerHTML = \'Course saved successfully.\';
											ID("submit_msg").style.visibility = "visible";
										</script>';
	}

	if ($_SERVER['argv'][0] != "")
		$course_id = $_SERVER['argv'][0];

	$parse['submit_button'] = ($course_id == "" ? "Create course" : "Save course");

	if ($course_id != "")
	{
		$_SESSION['course']['course_id'] = $course_id;
		$parse = array_merge($parse, $DB->getrow("SELECT course_id, course_name FROM course WHERE course_id = ".$course_id));
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('course_manage.html');
	$result = tpParse($parse,$html);
	echo $result;

?>