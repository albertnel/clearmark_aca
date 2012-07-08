<?php

	require_once("core/init.php");

	// -== Build the courses dropdown ==--
	$inner_join = "";
	if ($_SESSION['user']['user_id'] > 1)
		$inner_join = "INNER JOIN user_rel_course ON course.course_id = user_rel_course.course_id WHERE user_id = ".$_SESSION['user']['user_id'];

	$courses = $DB->get("SELECT course.course_id, course_name FROM course ".$inner_join." ORDER BY course_name ASC");
	$size = sizeof($courses);
	if ($size > 0)
	{
		$dd = '<select id="course_id" style="min-width:200px;">';
		for ($k = 0; $k < $size; ++$k)
			$dd .= '<option value="'.$courses[$k]['course_id'].'">'.$courses[$k]['course_name'].'</option>';
		$dd .= '</select>';
		$parse['courses_dd'] = $dd;
	}
	else
		$parse['courses_dd'] = '<span class="form_input_err">You don\'t have access to any courses.</span>';

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('students_assign_course.html');
	$result = tpParse($parse,$html);
	echo $result;

?>