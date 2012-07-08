<?php

	require_once("core/init.php");

	if (sizeof($_POST) > 0)
	{
		if ($_POST['profile_id'] == "") unset($_POST['profile_id']);
		if ($_POST['user_id'] == "") unset($_POST['user_id']);
		$_POST['password'] = md5($_POST['password']);
		$_POST['user_id_created'] = $_SESSION['user']['user_id'];

		$DB->dbsave("user", $_POST);
		$DB->dbsave("user_profile", $_POST);

		$already = $DB->getcell("SELECT COUNT(*) FROM user_rel_group WHERE user_id = ".$_POST['user_id']);
		if (!$already) $DB->put("INSERT INTO user_rel_group VALUES(".$_POST['user_id'].", 5)");

		$user_id = $_POST['user_id'];

		$parse['end_script'] = '<script>
											ID("submit_msg").innerHTML = \'Details saved successfully.\';
											ID("submit_msg").style.visibility = "visible";
										</script>';

		logAction("save", "student", $user_id, ($already == 0 ? "Create student" : "Update student"));
	}

	if ($_SERVER['argv'][0] != "")
		$user_id = $_SERVER['argv'][0];

	$parse['submit_button'] = ($user_id == "" ? "Create student" : "Save student");

	if ($user_id != "")
	{
		$parse = array_merge($parse, $DB->getrow("SELECT user.user_id, profile_id, username, name, surname, email FROM user_profile INNER JOIN user ON user_profile.user_id = user.user_id WHERE user.user_id = ".$user_id));
		$parse['courses_dd'] = ajaxGetAssignedCoursesDD($user_id);
		$parse['subjects_dd'] = ajaxGetAssignedSubjectsDD($user_id);
	}
	else
	{
		$parse['courses_dd'] = '<span class="form_input_desc">Not assigned to any courses.</span>';
		$parse['subjects_dd'] = '<span class="form_input_desc">Not assigned to any subjects.</span>';
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('student_manage.html');
	$result = tpParse($parse,$html);
	echo $result;

?>