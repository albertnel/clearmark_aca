<?php

	global $ajax_function;
	$ajax_function = array("ajaxUserDelete", "ajaxValidateEmail", "ajaxRemoveChapter", "ajaxBookmarkChapter", "ajaxBookRemoveFromSubject", "ajaxBookDelete", "ajaxBookShare", "ajaxBookCopy", "ajaxLevelTreeDraw", "ajaxLevelCheckName", "ajaxSaveLevel", "ajaxLevelGetName", "ajaxRemoveLevel", "ajaxGetAssignedCoursesDD", "ajaxGetAssignedSubjectsDD",
									"ajaxAssignUsersToCourse", "ajaxRemoveUserFromCourse", "ajaxAssignUsersToSubjects", "ajaxRemoveUserFromSubject",
									"ajaxCourseDelete", "ajaxAssignCoursesToLevel", "ajaxNotificationDelete", "ajaxEventDelete", "ajaxCourseLoadContent",
									"ajaxGetSubjectCourseLinks", "ajaxSubjectLinkCourse", "ajaxSubjectUnlinkCourse", "ajaxSubjectDelete", "ajaxSubjectLoadContent");

	function ajaxUserDelete($user_id)
	{
		global $DB;
		$group_id = $DB->getcell("SELECT group_id FROM user_rel_group WHERE user_id = ".$user_id);
		logAction("delete", "user", $user_id, "Delete user", $DB->getcell("SELECT group_name FROM user_group WHERE group_id = ".$group_id));
		$DB->put("DELETE FROM user WHERE user_id = ".$user_id);
		$DB->put("DELETE FROM user_profile WHERE user_id = ".$user_id);
		$DB->put("DELETE FROM user_rel_group WHERE user_id = ".$user_id);
	}

	function ajaxValidateEmail($email)
	{
		if (eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email))
			return 1;
		else
			return 0;
	}

	// --====================--
	// --== Book functions ==--
	// --====================--

	function ajaxRemoveChapter($book_id, $chapter)
	{
		global $DB;
		$chapter = $DB->getrow("SELECT book_rel_chapter.chapter_id, chapter_file FROM book_rel_chapter
										INNER JOIN book_chapter ON book_rel_chapter.chapter_id = book_chapter.chapter_id
										WHERE book_id = ".$book_id." AND position = ".$chapter);
		$DB->put("DELETE FROM book_chapter WHERE chapter_id = ".$chapter['chapter_id']);
		$DB->put("DELETE FROM book_rel_chapter WHERE chapter_id = ".$chapter['chapter_id']);
		unlink($chapter['chapter_file']);
		logAction("delete", "chapter", $chapter, "book_id=".$book_id);
	}

	function ajaxBookmarkChapter($book_id, $chapter_id)
	{
		global $DB;

		$bookmark = array();
		$bookmark['user_id'] = $_SESSION['user']['user_id'];
		$bookmark['book_id'] = $book_id;
		$bookmark['chapter_id'] = $chapter_id;

		$bookmark_id = $DB->getcell("SELECT bookmark_id FROM user_bookmark WHERE user_id = ".$_SESSION['user']['user_id']." AND book_id = ".$book_id);
		if ($bookmark_id != "")
		{
			$bookmark['bookmark_id'] = $bookmark_id;
			$DB->dbsave("user_bookmark", $bookmark);
		}
		else
			$DB->dbsave("user_bookmark", $bookmark);

		logAction("save", "bookmark", $bookmark['bookmark_id'], "Create bookmark", "book_id=".$book_id);
	}

	function ajaxBookRemoveFromSubject($book_id)
	{
		global $DB;
		$DB->put("DELETE FROM book_rel_subject WHERE book_id = ".$book_id." AND subject_id = ".$_SESSION['subject']['subject_id']);
		logAction("unlink", "book", $book_id, "subject_id=".$_SESSION['subject']['subject_id']);
	}

	function ajaxBookDelete($book_id)
	{
		global $DB;

		$chapters = $DB->get("SELECT book_chapter.chapter_id, chapter_file FROM book_chapter
										INNER JOIN book_rel_chapter ON book_chapter.chapter_id = book_rel_chapter.chapter_id
										WHERE book_id = ".$book_id);
		$size = sizeof($chapters);
		for ($k = 0; $k < $size; ++$k)
		{
			$DB->put("DELETE FROM book_chapter WHERE chapter_id = ".$chapters[$k]['chapter_id']);
			@unlink($chapters[$k]['chapter_file']);
		}
		@chmod("files/books/".$book_id, 0777);
		@unlink("files/books/".$book_id);

		$DB->put("DELETE FROM book WHERE book_id = ".$book_id);
		$DB->put("DELETE FROM book_rel_chapter WHERE book_id = ".$book_id);
		$DB->put("DELETE FROM user_bookmark WHERE book_id = ".$book_id);
		logAction("delete", "book", $book_id, "Delete book");
	}

	function ajaxBookShare($book_id, $subject_ids)
	{
		global $DB;
		$subject_ids = explode(",", $subject_ids);
		$size = sizeof($subject_ids);
		for ($k = 0; $k < $size; ++$k)
		{
			$count = $DB->getcell("SELECT COUNT(*) FROM book_rel_subject WHERE book_id = ".$book_id." AND subject_id = ".$subject_ids[$k]);
			if ($count == 0)
				$DB->put("INSERT INTO book_rel_subject VALUES (".$book_id.", ".$subject_ids[$k].", '1')");
		}
	}
	
	function ajaxBookCopy($book_id, $subject_ids)
	{
		global $DB;
		
		$book = $DB->getrow("SELECT book_title, book_code FROM book WHERE book.book_id = ".$_SERVER['argv'][0]);
		$chapters = $DB->get("SELECT chapter_file, position
								FROM book_chapter
								INNER JOIN book_rel_chapter ON book_chapter.chapter_id = book_rel_chapter.chapter_id
								WHERE book_id = ".$_SERVER['argv'][0]."
								ORDER BY position ASC");
		$csize = sizeof($chapters);
		
		$subject_ids = explode(",", $subject_ids);
		$size = sizeof($subject_ids);
		
		for ($k = 0; $k < $size; ++$k)
		{
			$new_book = $book;
			$DB->dbsave("book", $new_book);
			$DB->put("INSERT INTO book_rel_subject VALUES (".$new_book['book_id'].", ".$subject_ids[$k].", 0)");
			
			// -- Create directory --
			$book_dir = "files/books/".$new_book['book_id'];
			if (!file_exists($book_dir))
				mkdir($book_dir, 0777);			
			
			for ($j = 0; $j < $csize; ++$j)
			{
				$new_chapter = array();
				$chapter_file = explode("/", $chapters[$j]['chapter_file']);
				$chapter_file[2] = $new_book['book_id'];
				$chapter_file = join("/", $chapter_file);
				$new_chapter['chapter_file'] = $chapter_file;
				$DB->dbsave("book_chapter", $new_chapter);
				$DB->put("INSERT INTO book_rel_chapter VALUES (".$new_book['book_id'].", ".$new_chapter['chapter_id'].", ".$chapters[$j]['position'].")");
				
				copy($chapters[$j]['chapter_file'], $chapter_file);
			}
		}
	}

	// --=====================--
	// --== Level functions ==--
	// --=====================--

	function ajaxLevelTreeDraw()
	{
		require_once("lib/menu-tree/class.menu.php");
		$menu = new menu("siteedit", "tree");

		global $DB;
		$sql = "SELECT level_id as menu_id, level_parent_id as parent_id, level_name as title FROM level";
		$rs = $DB->get($sql, 2);

		$event[0][name] = "onclick";
		$event[0][action] = "selectItem('0')";
		$menu->setRootEvents($event);

		$size = sizeof($rs);
		for ($a = 0; $a < $size; $a++)
		{
			$event[0][name] = "onclick";
			$event[0][action] = "selectItem('".$rs[$a][menu_id]."')";

			$icon = '';
			$description = '';

			if($rs[$a][icon_path] != '')
				$icon = $rs[$a][icon_path];

			if($rs[$a][active_yn] == '0' && $rs[$a][parent_id] != '')
			{
				$icon = 'lib/menu/img/cross.gif';
				$description = 'Not active';
			}

			$menu->addMenuItem($rs[$a][menu_id],$rs[$a][parent_id],stripslashes($rs[$a][title]),$url,"",$description,$icon,$event);

			if ($currentNode != "" && $rs[$a][menu_id] == $currentNode)
				$currentNode = $rs[$a];
		}

		if ($currentNode == "")
			$currentNode = $rs[0];

		$_SESSION['node']['currentNode'] = $currentNode;

		// --== Built the tree from the menu items ==--
		$size = sizeof($menu->content);
		for ($a = 0; $a < $size; $a++)
		{
			if ($menu->content[$a][pid] == -1)
				$menu->content[$a][pid] = 0;

			$menu->addnode($menu->content[$a][uid],$menu->content[$a][pid],$menu->content[$a][title],$menu->content[$a][url],$menu->content[$a][title],$menu->content[$a][target],$menu->content[$a][icon],"","",$menu->content[$a][event]);
		}

		if ($_SESSION['node']['nodeToOpen'] != "")
			$menu->setOpenNode($_SESSION['node']['nodeToOpen']);
		else
			$menu->closeAllNodes();

		$menu->initializeTree();
		$html = $menu->getTree();
		return $html;
	}

	function ajaxLevelCheckName($level_id, $level_name)
	{
		global $DB;

		if ($level_id != "")
			$count = $DB->getcell("SELECT count(*) FROM level WHERE level_name = '".$level_name."' AND level_id != ".$level_id);
		else if ($level_id == "")
			$count = $DB->getcell("SELECT count(*) FROM level WHERE level_name = '".$level_name."'");

		if ($count > 0)
			return 0;
		else
			return 1;
	}

	function ajaxSaveLevel($level_id, $level_name, $parent_id)
	{
		global $DB;

		if (($level_id == "") && ($_SESSION['user']['group_id'] > 1))
		{
			// -- Lecturer & Tutor --
			if (($_SESSION['user']['group_id'] == 3) || ($_SESSION['user']['group_id'] == 4))
				return 0;

			// -- Admins --
			$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_level WHERE user_id = ".$_SESSION['user']['user_id']." AND level_id = ".$parent_id);
			if ($count == 0) return 0;
		}

		$data = array();
		$data['level_id'] = $level_id;
		$data['level_name'] = $level_name;
		if ($level_id == "") $data['level_parent_id'] = $parent_id;
		$DB->dbsave("level", $data);

		$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_level WHERE user_id = ".$_SESSION['user']['user_id']." AND level_id = ".$data['level_id']);
		if ($count == 0) $DB->put("INSERT INTO user_rel_level VALUES (".$_SESSION['user']['user_id'].", ".$data['level_id'].")");

		$_SESSION['node']['nodeToOpen'] = $data['level_id'];

		logAction("save", "level", $data['level_id'], ($level_id == "" ? "Create level" : "Update level"));

		return 1;
	}

	function ajaxLevelGetName($level_id)
	{
		global $DB;
		return $DB->getcell("SELECT level_name FROM level WHERE level_id = ".$level_id);
	}

	function ajaxRemoveLevel($level_id)
	{
		global $DB;

		// --== Get all user levels ==--
		$level_ids = array();
		$level_ids = $DB->getcol("SELECT level_id FROM user_rel_level WHERE user_id = ".$_SESSION['user']['user_id']);
		$size = sizeof($level_ids);

		for ($k = 0; $k < $size; ++$k)
		{
			$children = levelGetChildren($level_ids[$k]);

			if (sizeof($children) > 0)
			{
				$level_ids = array_merge($level_ids, $children);
				$level_ids = array_unique($level_ids);
			}
		}
		$level_ids = array_merge(array(), $level_ids);
		$size = sizeof($level_ids);

		// --== Check if level is part of user's accessible levels ==--
		$found = false;
		for ($k = 0; $k < $size; ++$k)
		{
			if ($level_ids[$k] == $level_id)
			{
				$found = true;
				break;
			}
		}

		if ($found)
		{
			$children = levelGetChildren($level_id);
			if (sizeof($children) > 0)
			{
				foreach ($children as $child)
				{
					$DB->put("DELETE FROM level WHERE level_id = ".$child);
					$DB->put("DELETE FROM user_rel_level WHERE level_id = ".$child);
				}
			}

			logAction("delete", "level", $level_id, "Delete level", $DB->getcell("SELECT level_name FROM level WHERE level_id = ".$level_id));
			$DB->put("DELETE FROM level WHERE level_id = ".$level_id);
			$DB->put("DELETE FROM user_rel_level WHERE level_id = ".$level_id);

			return 1;
		}
		else
			return 0;
	}

	function ajaxGetAssignedCoursesDD($user_id)
	{
		global $DB;

		// -- Build courses dropdown --
		$courses = $DB->get("SELECT course.course_id, course_name FROM course
									INNER JOIN user_rel_course ON course.course_id = user_rel_course.course_id
									WHERE user_id = ".$user_id);
		$size = sizeof($courses);

		if ($size > 0)
		{
			$html = '<div>
							<select multiple id="course_id" style="height:100px;width:300px;">';
			for ($k = 0; $k < $size; ++$k)
				$html .= '	<option value="'.$courses[$k]['course_id'].'">'.$courses[$k]['course_name'].'</option>';
			$html .= '	</select>
						</div>
						<div class="float_left"><a onclick="removeCourse();">Remove course</a></div>
						<div id="remove_course_msg" class="float_left" style="margin-left:10px;">&nbsp;</div>
						<div class="clear_both"></div>';
		}
		else
			$html = '<span class="form_input_desc">Not assigned to any courses.</span>';

		return $html;
	}

	function ajaxGetAssignedSubjectsDD($user_id)
	{
		global $DB;

		// -- Get all user-assigned subjects --
		$subjects = $DB->get("SELECT subject.subject_id, subject_name, subject_code FROM subject
									INNER JOIN user_rel_subject ON subject.subject_id = user_rel_subject.subject_id
									WHERE user_id = ".$user_id);
		$size = sizeof($subjects);

		if ($size > 0)
		{
			$html = '<div>
							<select multiple id="subject_id" style="height:100px;width:300px;">';
			for ($k = 0; $k < $size; ++$k)
				$html .= '	<option value="'.$subjects[$k]['subject_id'].'">'.$subjects[$k]['subject_code'].' - '.$subjects[$k]['subject_name'].'</option>';
			$html .= '	</select>
						</div>
						<div class="float_left"><a onclick="removeSubject();">Remove subject</a></div>
						<div id="remove_subject_msg" class="float_left" style="margin-left:10px;">&nbsp;</div>
						<div class="clear_both"></div>';
		}
		else
			$html = '<span class="form_input_desc">Not assigned to any subjects.</span>';

		return $html;
	}

	// --=====================--
	// --== Admin functions ==--
	// --=====================--

	function ajaxAssignUsersToCourse($user_ids, $course_id)
	{
		global $DB;

		$user_ids = explode(",", $user_ids);
		$size = sizeof($user_ids);

		for ($k = 0; $k < $size; ++$k)
		{
			$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_course WHERE user_id = ".$user_ids[$k]." AND course_id = ".$course_id);
			if ($count == 0)
				$DB->put("INSERT INTO user_rel_course VALUES (".$user_ids[$k].", ".$course_id.", 1)");
			else
				$DB->put("UPDATE user_rel_course SET active = 1 WHERE user_id = ".$user_ids[$k]." AND course_id = ".$course_id);
		}
	}

	function ajaxRemoveUserFromCourse($user_id, $course_id)
	{
		global $DB;
		$DB->put("DELETE FROM user_rel_course WHERE user_id = ".$user_id." AND course_id = ".$course_id);
		logAction("unlink", "user", $user_id, "Unlink from course", "course_id=".$course_id);
	}

	function ajaxAssignUsersToSubjects($user_ids, $subject_ids)
	{
		global $DB;

		$user_ids = explode(",", $user_ids);
		$usize = sizeof($user_ids);
		$subject_ids = explode(",", $subject_ids);
		$ssize = sizeof($subject_ids);

		for ($k = 0; $k < $usize; ++$k)
		{
			for ($j = 0; $j < $ssize; ++$j)
			{
				$count = $DB->getcell("SELECT COUNT(*) FROM user_rel_subject WHERE user_id = ".$user_ids[$k]." AND subject_id = ".$subject_ids[$j]);
				if ($count == 0)
					$DB->put("INSERT INTO user_rel_subject VALUES (".$user_ids[$k].", ".$subject_ids[$j].", 1)");
				else
					$DB->put("UPDATE user_rel_subject SET active = 1 WHERE user_id = ".$user_ids[$k]." AND subject_id = ".$subject_ids[$j]);
			}
		}
	}

	function ajaxRemoveUserFromSubject($user_id, $subject_id)
	{
		global $DB;
		$DB->put("DELETE FROM user_rel_subject WHERE user_id = ".$user_id." AND subject_id = ".$subject_id);
		logAction("unlink", "user", $user_id, "Unlink from subject", "subject_id=".$subject_id);
	}

	// --======================--
	// --== Course functions ==--
	// --======================--

	function ajaxCourseDelete($course_id)
	{
		global $DB;
		logAction("delete", "course", $course_id, "Delete course", $DB->getcell("SELECT course_name FROM course WHERE course_id = ".$course_id));
		$DB->put("DELETE FROM course WHERE course_id = ".$course_id);
	}

	function ajaxAssignCoursesToLevel($course_ids, $level_id)
	{
		global $DB;

		$course_ids = explode(",", $course_ids);
		$size = sizeof($course_ids);

		for ($k = 0; $k < $size; ++$k)
		{
			$count = $DB->getcell("SELECT COUNT(*) FROM course_rel_level WHERE course_id = ".$course_ids[$k]);
			if ($count > 0)
				$DB->put("DELETE FROM course_rel_level WHERE course_id = ".$course_ids[$k]);

			$DB->put("INSERT INTO course_rel_level VALUES (".$course_ids[$k].", ".$level_id.")");
		}
	}

	function ajaxNotificationDelete($notification_id)
	{
		global $DB;

		logAction("delete", "notification", $notification_id, "Delete notification", $DB->getcell("SELECT title FROM notification WHERE notification_id = ".$notification_id));
		$DB->put("DELETE FROM notification WHERE notification_id = ".$notification_id);
		$DB->put("DELETE FROM notification_rel_course WHERE notification_id = ".$notification_id);
		$DB->put("DELETE FROM notification_rel_subject WHERE notification_id = ".$notification_id);
	}

	function ajaxEventDelete($event_id)
	{
		global $DB;

		logAction("delete", "event", $event_id, "Delete event", $DB->getcell("SELECT Subject FROM jqcalendar WHERE Id = ".$event_id));
		$DB->put("DELETE FROM jqcalendar WHERE Id = ".$event_id);
		$DB->put("DELETE FROM event_rel_course WHERE event_id = ".$event_id);
		$DB->put("DELETE FROM event_rel_subject WHERE event_id = ".$event_id);
	}

	function ajaxCourseLoadContent($type)
	{
		global $DB;
		$course_id = $_SESSION['course']['course_id'];

		$html = '<div class="subject_content_wrapper">';

		// -- Subjects --
		if ($type == "subjects")
		{
			$html .= '<h2 style="padding-bottom:10px;">Subjects</h2>
						<div id="subjects_list" style="margin-bottom:20px;">'.getStudentSubjects($_SESSION['user']['user_id']).'</div>';
		}

		// -- Notifications --
		else if ($type == "notifications")
		{
			$notifications = $DB->get("SELECT * FROM notification
												INNER JOIN notification_rel_course ON notification.notification_id = notification_rel_course.notification_id
												WHERE start_date <= '".date("Y-m-d")."' AND end_date >= '".date("Y-m-d")."' AND course_id = ".$course_id."
												ORDER BY start_date");
			$size = sizeof($notifications);

			$html .= '<h2 style="padding-bottom:10px;">Notifications</h2>';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
					$html .= '<div style="margin-bottom:5px;"><a class="fancybox" href="notification_view.php?id='.$notifications[$k]['notification_id'].'">'.$notifications[$k]['start_date'].' - '.$notifications[$k]['title'].'</a></div>';
			}
			else
				$html .= '<div class="form_input_desc italic">No notifications available.</div>';
		}

		$html .= '</div>';

		return $html;
	}

	// --========================--
	// --== Subjects functions ==--
	// --========================--

	function ajaxGetSubjectCourseLinks($subject_id)
	{
		global $DB;

		$dd = '<div><select multiple id="course_ids" name="course_ids" style="min-width:250px;">';

		$courses = $DB->get("SELECT subject_rel_course.course_id, course_name FROM subject_rel_course
									INNER JOIN course ON subject_rel_course.course_id = course.course_id
									WHERE subject_id = ".$subject_id);
		$size = sizeof($courses);

		for ($k = 0; $k < $size; ++$k)
			$dd .= '<option value="'.$courses[$k]['course_id'].'">'.$courses[$k]['course_name'].'</option>';

		$dd .= '</select></div>
				  <div class="float_left"><a onclick="unlinkCourse();">Unlink course</a></div>
				  <div id="course_msg" class="float_left" style="margin-left:10px;">&nbsp;</div>
				  <div class="clear_both"></div>';

		return $dd;
	}

	function ajaxSubjectLinkCourse($subject_id, $course_id)
	{
		global $DB;

		$count = $DB->getcell("SELECT COUNT(*) FROM subject_rel_course WHERE subject_id = ".$subject_id." AND course_id = ".$course_id);
		if ($count == 0)
		{
			$DB->put("INSERT INTO subject_rel_course VALUES (".$subject_id.", ".$course_id.");");
			logAction("link", "subject", $subject_id, "Link to course", $course_id);
		}
	}

	function ajaxSubjectUnlinkCourse($subject_id, $course_id)
	{
		global $DB;
		$DB->put("DELETE FROM subject_rel_course WHERE subject_id = ".$subject_id." AND course_id = ".$course_id);
		logAction("unlink", "subject", $subject_id, "Unlink from course", $course_id);
	}

	function ajaxSubjectDelete($subject_id)
	{
		global $DB;

		logAction("delete", "subject", $subject_id, "Delete subject", $DB->getcell("SELECT subject_name FROM subject WHERE subject_id = ".$subject_id), "course_id=".$course_id);
		$DB->put("DELETE FROM subject WHERE subject_id = ".$subject_id);
		$DB->put("DELETE FROM subject_rel_course WHERE subject_id = ".$subject_id);
	}

	function ajaxSubjectLoadContent($type)
	{
		global $DB;
		$subject_id = $_SESSION['subject']['subject_id'];

		$html = '<div class="subject_content_wrapper">';

		// --== Study material ==--
		if ($type == "study_material")
		{
			$books = $DB->get("SELECT * FROM book
									INNER JOIN book_rel_subject ON book.book_id = book_rel_subject.book_id
									WHERE subject_id = ".$subject_id." AND external_resource = '0'
									ORDER BY book_title ASC");
			$size = sizeof($books);

			$html .= '<h2 style="padding-bottom:10px;">Study Material</h2>';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
				{
					$bg_color = (($k%2) == 0 ? "#d8e8ef" : "#fff");
					$html .= '<div style="margin-bottom:5px;padding:5px;background-color:'.$bg_color.'">
									<a href="book_view.php?'.$books[$k]['book_id'].'">'.$books[$k]['book_title'].' '.($books[$k]['book_code'] != '' ? '('.$books[$k]['book_code'].')' : '').'</a>
									<div style="float:right;"><img src="template/img/student/subject_list_arrow.png" /></div>
									<div class="clear_both"></div>
								</div>';
				}
			}
			else
				$html .= '<div class="form_input_desc italic">No study material available.</div>';
		}

		// --== Resources ==--
		if ($type == "resources")
		{
			$books = $DB->get("SELECT * FROM book
									INNER JOIN book_rel_subject ON book.book_id = book_rel_subject.book_id
									WHERE subject_id = ".$subject_id." AND external_resource = '1'
									ORDER BY book_title ASC");
			$size = sizeof($books);

			$html .= '<h2 style="padding-bottom:10px;">Resources</h2>';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
				{
					$bg_color = (($k%2) == 0 ? "#d8e8ef" : "#fff");
					$html .= '<div style="margin-bottom:5px;padding:5px;background-color:'.$bg_color.'">
									<a href="book_view.php?'.$books[$k]['book_id'].'">'.$books[$k]['book_title'].' '.($books[$k]['book_code'] != '' ? '('.$books[$k]['book_code'].')' : '').'</a>
									<div style="float:right;"><img src="template/img/student/subject_list_arrow.png" /></div>
									<div class="clear_both"></div>
								</div>';
				}
			}
			else
				$html .= '<div class="form_input_desc italic">No resources available.</div>';
		}

		// --== Livestream ==--
		else if ($type == "livestream_links")
		{
			$links = $DB->get("SELECT link.link_id, link_title, link_url FROM link
									INNER JOIN link_rel_subject ON link.link_id = link_rel_subject.link_id
									WHERE subject_id = ".$subject_id." AND link_url LIKE '%livestream.com%'");
			$size = sizeof($links);

			$html .= '<h2 style="padding-bottom:10px;">Livestream Links</h2>';
			for ($k = 0; $k < $size; ++$k)
				$html .= '<div style="margin-bottom:5px;"><a href="livestream_modal.php?'.$links[$k]['link_id'].'" class="livestream_box">'.$links[$k]['link_title'].'</a></div>';
		}

		// --== Links ==--
		else if ($type == "links")
		{
			$links = $DB->get("SELECT link.link_id, link_title, link_url FROM link
									INNER JOIN link_rel_subject ON link.link_id = link_rel_subject.link_id
									WHERE subject_id = ".$subject_id." AND link_url NOT LIKE '%livestream.com%'");
			$size = sizeof($links);

			$html .= '<h2 style="padding-bottom:10px;">Links</h2>';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
					$html .= '<div style="margin-bottom:5px;"><a href="'.$links[$k]['link_url'].'" target="_blank">'.$links[$k]['link_title'].'</a></div>';
			}
			else
				$html .= '<div class="form_input_desc italic">No links available.</div>';
		}

		// --== Lecturers ==--
		else if ($type == "lecturers")
		{
			$lecturers = $DB->get("SELECT user_profile.user_id, name, surname, email, phone_number, profile_pic, group_name FROM user_profile
											INNER JOIN user_rel_subject ON user_profile.user_id = user_rel_subject.user_id
											INNER JOIN user_rel_group ON user_profile.user_id = user_rel_group.user_id
											INNER JOIN user_group ON user_rel_group.group_id = user_group.group_id
											WHERE user_rel_group.group_id < 5 AND user_rel_group.group_id > 2 AND subject_id = ".$subject_id);
			$size = sizeof($lecturers);

			$html .= '<h2>Lecturers</h2>
						<table cellpadding="10px" cellspacing="0">';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
				{
					$bg_color = (($k%2) == 0 ? "#fff" : "#d8e8ef");

					$html .= '<tr style="background-color:'.$bg_color.';">
									<td><img src="'.$lecturers[$k]['profile_pic'].'" style="max-width:100px;" class="text_normal" alt="Photo" /></td>
									<td class="text_normal">'.$lecturers[$k]['name'].' '.$lecturers[$k]['surname'].'</td>
									<td class="text_normal">'.$lecturers[$k]['group_name'].'</td>
									<td><a class="fancybox" href="lecturer_info_view.php?id='.$lecturers[$k]['user_id'].'">View info</a></td>
									<td><a href="mailto:'.$lecturers[$k]['email'].'">Send email</a></td>
									<td><a href="message_view.php?+'.$lecturers[$k]['user_id'].'">Send message</a></td>
								</tr>';
				}
			}
			else
				$html .= '<tr><td class="form_input_desc italic">No lecturers available.</td></tr>';
			$html .= '</table>';
		}

		// --== Notifications ==--
		else if ($type == "notifications")
		{
			$notifications = $DB->get("SELECT * FROM notification
												INNER JOIN notification_rel_subject ON notification.notification_id = notification_rel_subject.notification_id
												WHERE start_date <= '".date("Y-m-d")."' AND end_date >= '".date("Y-m-d")."' AND subject_id = ".$subject_id."
												ORDER BY start_date");
			$size = sizeof($notifications);

			$html .= '<h2 style="padding-bottom:10px;">Notifications</h2>';
			if ($size > 0)
			{
				for ($k = 0; $k < $size; ++$k)
					$html .= '<div style="margin-bottom:5px;"><a class="fancybox" href="notification_view.php?id='.$notifications[$k]['notification_id'].'">'.$notifications[$k]['start_date'].' - '.$notifications[$k]['title'].'</a></div>';
			}
			else
				$html .= '<div class="form_input_desc italic">No notifications available.</div>';
		}

		$html .= '</div>';

		return $html;
	}

?>