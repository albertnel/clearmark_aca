<?php

	require_once("core/init.php");

	// -== Build the subjects dropdown ==--
	$where = "";
	if ($_SESSION['user']['user_id'] > 1)
	{
		$course_ids = array();
		$subject_ids = array();

		// -- First, get assigned courses --
		$course_ids = $DB->getcol("SELECT course_id FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']);

		// -- Get all related subjects --
		if (sizeof($course_ids) > 0)
			$subject_ids = $DB->getcol("SELECT subject_id FROM subject_rel_course WHERE course_id IN (".join(",", $course_ids).")");

		// -- Get all user-assigned subjects --
		$subject_ids = array_merge($subject_ids, $DB->getcol("SELECT subject_id FROM user_rel_subject WHERE user_id = ".$_SESSION['user']['user_id']));

		// -- Make array unique --
		$subject_ids = array_unique($subject_ids);
		$subject_ids = array_merge(array(), $subject_ids);

		// -- Where --
		$where = "WHERE subject_id IN (".join(",", $subject_ids).")";
	}

	$subjects = $DB->get("SELECT subject.subject_id, subject_name FROM subject ".$where." ORDER BY subject_name ASC");
	$size = sizeof($subjects);
	if ($size > 0)
	{
		$dd = '<select id="subject_id" style="min-width:200px;">';
		for ($k = 0; $k < $size; ++$k)
			$dd .= '<option value="'.$subjects[$k]['subject_id'].'">'.$subjects[$k]['subject_name'].'</option>';
		$dd .= '</select>';
		$parse['subjects_dd'] = $dd;
	}
	else
		$parse['subjects_dd'] = '<span class="form_input_err">You don\'t have access to any subjects.</span>';

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('students_assign_subjects.html');
	$result = tpParse($parse,$html);
	echo $result;

?>