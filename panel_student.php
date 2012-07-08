<?php

	require_once("core/init.php");

	$user = $DB->getrow("SELECT username, name, surname, gender, profile_pic FROM user_profile
										INNER JOIN user ON user_profile.user_id = user.user_id
										WHERE user_profile.user_id = ".$_SESSION['user']['user_id']);

	if ($user['profile_pic'] != "")
		$user_image = $user['profile_pic'];
	elseif (($user['profile_pic'] == "" & $user['gender'] == "M") || ($user['gender'] == ""))
		$user_image = "template/img/default_user_male.gif";
	else
		$user_image = "template/img/default_user_female.gif";

	$parse['user_photo'] = "<img src='".$user_image."' alt='User photo' style='max-width:160px;'>";
	$parse['nameplate_name'] = $user['name']." ".$user['surname']."<br/>".$user['username'];

	// -- Messages --
	$unread_count = $DB->getcell("SELECT COUNT(*) FROM thread
											WHERE user_id_to = ".$_SESSION['user']['user_id']." AND viewed = 0");
	if ($unread_count > 0)
		$parse['messages_unread'] = '('.$unread_count.')';

	// -- Course --
	$course = $DB->getrow("SELECT course.course_id, course_name FROM course
											INNER JOIN user_rel_course ON course.course_id = user_rel_course.course_id
											WHERE user_id = ".$_SESSION['user']['user_id']." AND active = 1");
	if ($course['course_name'] == "")
	{
		$parse['course_name'] = "You're not registered for any course yet.";
		$parse['student_notification_list'] = "N/A";
	}
	else
	{
		// -- Set course_id for calendar --
		if (isset($_SESSION['subject'])) unset($_SESSION['subject']);
		$_SESSION['course']['course_id'] = $course['course_id'];

		// -- Course name --
		$parse['course_name'] = $course['course_name'];

		// -- Subjects on initial load --
		$parse['content_panel'] = ajaxCourseLoadContent("subjects");
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('panel_student.html');
	$result = tpParse($parse,$html);
	echo $result;

?>