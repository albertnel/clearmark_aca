<?php

	require_once("core/init.php");

	if (isset($_SESSION['course'])) unset($_SESSION['course']);

	if (sizeof($_POST) > 0)
	{
		if ($_POST['subject_id'] == "") unset($_POST['subject_id']);
		$DB->dbsave("subject", $_POST);
		logAction("save", "subject", $_POST['subject_id'], "Save subject");
		$subject_id = $_POST['subject_id'];

		$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_subject WHERE user_id = ".$_SESSION['user']['user_id']." AND subject_id = ".$subject_id);
		if ($count == 0)
			$DB->put("INSERT INTO user_rel_subject VALUES ('".$_SESSION['user']['user_id']."', '".$subject_id."', 1)");

		$parse['end_script'] = '<script>
											ID("submit_msg").innerHTML = \'Subject saved successfully.\';
											ID("submit_msg").style.visibility = "visible";
										</script>';

		// -- Links --
		if ($_POST['link_count'] > 0)
		{
			$link_ids = $DB->getcol("SELECT link_id FROM link_rel_subject WHERE subject_id = ".$subject_id);
			if (sizeof($link_ids) > 0)
			{
				$DB->put("DELETE FROM link_rel_subject WHERE subject_id = ".$subject_id);
				$DB->put("DELETE FROM link WHERE link_id IN (".join(",", $link_ids).")");
			}

			for ($k = 1; $k <= $_POST['link_count']; ++$k)
			{
				$link = array();
				$link['link_title'] = $_POST['link_title'.$k];
				$link['link_url'] = htmlspecialchars($_POST['link_url'.$k], ENT_QUOTES);
				$link['link_url'] = stripslashes($link['link_url']);
				$DB->dbsave("link", $link);
				$DB->put("INSERT INTO link_rel_subject VALUES (".$link['link_id'].", ".$subject_id.", ".$k.")");
				logAction("save", "link", $link['link_id'], "subject_id=".$subject_id);
			}
		}
	}

	if ($_SERVER['argv'][0] != "")
		$subject_id = $_SERVER['argv'][0];

	$parse['submit_button'] = ($subject_id == "" ? "Create subject" : "Save subject");

	if ($subject_id != "")
	{
		$_SESSION['subject']['subject_id'] = $subject_id;
		$parse = array_merge($parse, $DB->getrow("SELECT * FROM subject WHERE subject_id = ".$subject_id));

		// -- Courses dropdown --
		if ($_SESSION['user']['user_id'] == 1)
		{
			$courses = $DB->get("SELECT course.course_id, course_name FROM course ORDER BY course_name ASC");
		}
		else
		{
			$courses = $DB->get("SELECT course.course_id, course_name FROM course
										INNER JOIN user_rel_course ON course.course_id = user_rel_course.course_id
										WHERE user_id = ".$_SESSION['user']['user_id']."
										ORDER BY course_name ASC");
		}
		$size = sizeof($courses);

		if ($size > 0)
		{
			$dd = '<select id="course_dd_id" style="min-width:150px;margin-bottom:10px;">';
			for ($k = 0; $k < $size; ++$k)
			{
				$dd .= '<option value='.$courses[$k]['course_id'].'>'.$courses[$k]['course_name'].'</option>';
			}
			$dd .= '</select>
					  <input type="button" style="margin-left:10px;" value="Add" onclick="linkCourse();" />
					  <div id="linked_courses_dd">'.ajaxGetSubjectCourseLinks($subject_id).'</div>
					  <div id="courses_desc"><span class="form_input_desc">The courses that can access this subject.</span></div>';
			$parse['courses_dd'] = $dd;
		}
		else
			$parse['courses_dd'] = '<input type="hidden" id="course_dd_id" value="0" /><span class="form_input_desc">You don\'t have access to any courses.</span>';

		// -- Study material --
		$books = $DB->get("SELECT * FROM book
								INNER JOIN book_rel_subject ON book.book_id = book_rel_subject.book_id
								WHERE subject_id = ".$subject_id." AND external_resource = '0'
								ORDER BY book_title ASC");
		$size = sizeof($books);

		if ($size > 0)
		{
			$html = '<table>';
			for ($k = 0; $k < $size; ++$k)
				$html .= '<tr>
								<td class="text_normal">'.$books[$k]['book_title'].' '.($books[$k]['book_code'] != '' ? '('.$books[$k]['book_code'].')' : '').'</td>
								<td style="padding-left:10px;">
									<a href="book_manage.php?'.$books[$k]['book_id'].'">Edit</a> ||
									<a href="book_preview.php?'.$books[$k]['book_id'].'">Preview</a> ||
									<a href="book_share.php?'.$books[$k]['book_id'].'">Share</a> ||
									<a href="book_copy.php?'.$books[$k]['book_id'].'">Copy</a> ||
									<a onclick="removeBook('.$books[$k]['book_id'].');">Remove from subject</a> ||
									<a onclick="deleteBook('.$books[$k]['book_id'].');">Delete book</a></td>
							</tr>';
			$html .= '</table>';
			$parse['books'] = $html;
		}
		else
			$parse['books'] = '<span class="form_input_desc">No study material created yet.</span>';

		// -- Resources --
		$resources = $DB->get("SELECT * FROM book
								INNER JOIN book_rel_subject ON book.book_id = book_rel_subject.book_id
								WHERE subject_id = ".$subject_id." AND external_resource = '1'
								ORDER BY book_title ASC");
		$size = sizeof($resources);

		if ($size > 0)
		{
			$html = '<table>';
			for ($k = 0; $k < $size; ++$k)
				$html .= '<tr>
								<td class="text_normal">'.$resources[$k]['book_title'].' '.($resources[$k]['book_code'] != '' ? '('.$resources[$k]['book_code'].')' : '').'</td>
								<td style="padding-left:10px;">
									<a href="book_preview.php?'.$resources[$k]['book_id'].'">Preview</a> ||
									<a onclick="removeBook('.$resources[$k]['book_id'].');">Remove from subject</a>
							</tr>';
			$html .= '</table>';
			$parse['resources'] = $html;
		}
		else
			$parse['resources'] = '<span class="form_input_desc">No resources created yet.</span>';

		// -- Links --
		$links = $DB->get("SELECT link.link_id, link_title, link_url FROM link
								INNER JOIN link_rel_subject ON link.link_id = link_rel_subject.link_id
								WHERE subject_id = ".$subject_id);
		$size = sizeof($links);
		if ($size > 0)
		{
			$parse['link_count'] = $size;

			for ($k = 1; $k <= $size; ++$k)
				$parse['links_wrapper'] .= '<div id="link'.$k.'_wrapper">
														<div><input type="textbox" id="link_title'.$k.'" name="link_title'.$k.'" class="textbox" placeholder="Title of the link" value="'.$links[$k-1]['link_title'].'" /></div>
														<div class="form_input float_left">
															<input type="textbox" id="link_url'.$k.'" name="link_url'.$k.'" class="textbox" placeholder="URL / Address" value="'.$links[$k-1]['link_url'].'" />
															<span style="padding-left:10px;">'.(strpos($links[$k-1]['link_url'], "livestream.com") !== false ? '<a href="livestream_modal.php?'.$links[$k-1]['link_id'].'" class="livestream_box">' : '<a href="'.$links[$k-1]['link_url'].'" target="_blank">').'Preview link</a></span>
															<img src="template/img/close.gif" class="remove_chapter_icon" title="Remove" onclick="removeLink('.$k.');" />
															<div id="link'.$k.'_desc"><span class="form_input_desc">Ex. http://www.google.com/.</span></div>
														</div>
														<div class="clear_both"></div>
													</div>';
		}
		else
			$parse['links_wrapper'] = '<span class="form_input_desc">No links created yet.</span>';
	}
	else
		$parse['courses_dd'] = '<input type="hidden" id="course_dd_id" value="0" /><span class="form_input_desc">Please create the subject first.</span>';

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('subject_manage.html');
	$result = tpParse($parse,$html);
	echo $result;

?>