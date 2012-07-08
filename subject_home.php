<?php

	require_once("core/init.php");

	if (isset($_SESSION['course'])) unset($_SESSION['course']);

	if ($_SERVER['argv'][0] != "")
		$subject_id = $_SESSION['subject']['subject_id'] = $_SERVER['argv'][0];
	else
		$subject_id = $_SESSION['subject']['subject_id'];

	$subject = $DB->getrow("SELECT subject_code, subject_name FROM subject WHERE subject_id = ".$subject_id);
	$parse['page_title'] = $parse['subject_name'] = $subject['subject_code']." - ".$subject['subject_name'];

	// -- Livestream --
	$count = $DB->getcell("SELECT COUNT(*) FROM link
							INNER JOIN link_rel_subject ON link.link_id = link_rel_subject.link_id
							WHERE subject_id = ".$subject_id." AND link_url LIKE '%livestream.com%'");
	if ($count > 0)
	{
		$parse['livestream_menu'] = '	<div class="student_menu_divider"></div>
												<div class="student_menu_item">
													<div class="float_left"><img src="template/img/student/tv.png" /></div>
													<div class="float_left" style="padding:5px 0px 0px 5px;"><a class="student_menu_link" onclick="loadContent(\'livestream_links\');">Livestream</a></div>
													<div class="float_right" style="padding:5px 5px 0px 0px;"><img src="template/img/student/menu_arrow.gif" /></div>
													<div class="clear_both"></div>
												</div>';
	}

	// -- Initial load of study material --
	$parse['content_panel'] = ajaxSubjectLoadContent('study_material');

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('subject_home.html');
	$result = tpParse($parse,$html);
	echo $result;

?>