<?php

	// --====================--
	// --== Core Functions ==--
	// --====================--

	function debug($i)
	{
		echo '<pre>';
		print_r($i);
		echo '</pre>';
	}

	function debug_hard()
	{
		echo "<PRE STYLE=\"font-family: courier; font-size:12px; text-align: left;\">\nDebug message\n----- -------\n</PRE>";
		foreach(func_get_args() as $item) {
			if ($item == '')
				echo "<PRE STYLE=\"font-family: courier; font-size:12px; text-align: left;\">\nempty item\n</PRE>";
			switch (true)
			{
				case (is_array($item)):
					$imax = sizeof($item);
					for ($i=0;$i<$imax;$i++)
					{
						if (!is_array($item[$i]) && !is_object($item[$i]))
						{
							$item[$i] = htmlspecialchars($item[$i]);
						}
					}
				break;

				case (is_string($item)):
					$item = htmlspecialchars($item);
				break;

				default:
				break;
			}

			echo "<PRE STYLE=\"font-family: courier; font-size:12px; text-align: left;\">\n";
			print_r($item);
			echo "</PRE>\n";
		}
   }

   function logAction($action, $object, $object_ids, $extra1="", $extra2="", $extra3="")
   {
   	global $DB;

   	$log = array();
   	$log['user_id'] = $_SESSION['user']['user_id'];
   	$log['action'] = $action;
   	$log['object'] = $object;
   	$log['object_ids'] = $object_ids;
   	$log['extra1'] = $extra1;
   	$log['extra2'] = $extra2;
   	$log['extra3'] = $extra3;
   	$DB->dbsave("log", $log);
   }

	// --====================--
	// --== User Functions ==--
	// --====================--

	function loginUser($username, $password)
	{
		global $DB;

		// -- Prevent against SQL injection --
		$username = trim($username);
		$username = stripslashes($username);
		$password = trim($password);
		$password = stripslashes($password);

		$user = $DB->getrow("SELECT user.user_id, group_id FROM user
									INNER JOIN user_rel_group ON user.user_id = user_rel_group.user_id
									WHERE username = '".$username."' AND password = '".md5($password)."'");
		if (sizeof($user) > 0)
		{
			$_SESSION['user'] = $user;
			return 1;
		}
		else
			return 0;
	}

	function logoutUser()
	{
		unset($_SESSION['user']);
	}

	// --=====================--
	// --== Level Functions ==--
	// --=====================--

	function levelGetChildren($level_id)
	{
		if ($level_id != "")
		{
			global $DB;

			$result = array();

			$children = $DB->getcol("SELECT level_id FROM level WHERE level_parent_id = ".$level_id);

			if (sizeof($children) > 0)
			{
				$result = array_merge($result, $children);

				foreach ($children as $child)
					$result = array_merge($result, levelGetChildren($child));
			}

			return $result;
		}
	}

	// --=============================--
	// --== Student Panel Functions ==--
	// --=============================--

	function getStudentSubjects($student_id="")
	{
		global $DB;

		if ($student_id == "")
			$student_id = $_SESSION['user']['user_id'];

		$subjects = $DB->get("SELECT subject.subject_id, subject_name, subject_code FROM subject
									INNER JOIN user_rel_subject ON
									user_rel_subject.subject_id = subject.subject_id
									WHERE user_id = ".$student_id."
									ORDER BY subject_code, subject_name");
		$size = sizeof($subjects);

		$html = '';
		for ($k = 0; $k < $size; ++$k)
		{
			$bg_color = (($k%2) == 0 ? "#d8e8ef" : "#fff");
			$html .= '<div style="margin-bottom:5px;padding:5px;background-color:'.$bg_color.'">
							<a href="subject_home.php?'.$subjects[$k]['subject_id'].'">'.$subjects[$k]['subject_code'].' - '.$subjects[$k]['subject_name'].'</a>
							<div style="float:right;"><img src="template/img/student/subject_list_arrow.png" /></div>
							<div class="clear_both"></div>
						</div>';
		}

		return $html;
	}

	function getCourseNotifications($course_id)
	{
		global $DB;

		$notifications = $DB->get("SELECT * FROM notification
											INNER JOIN notification_rel_course ON notification.notification_id = notification_rel_course.notification_id
											WHERE start_date <= '".date("Y-m-d")."' AND end_date >= '".date("Y-m-d")."' AND course_id = ".$course_id."
											ORDER BY start_date");

		foreach($notifications as $key => $value)
			$notification_list .= '<div style="margin-bottom:5px;"><a class="fancybox" href="notification_view.php?id='.$value['notification_id'].'">'.$value['start_date'].' - '.$value['title'].'</a></div>';

		return $notification_list;
	}

	function getSubjectNotifications($subject_id)
	{
		global $DB;

		$notifications = $DB->get("SELECT * FROM notification
											INNER JOIN notification_rel_subject ON notification.notification_id = notification_rel_subject.notification_id
											WHERE start_date <= '".date("Y-m-d")."' AND end_date >= '".date("Y-m-d")."' AND subject_id = ".$subject_id."
											ORDER BY start_date");

		if (sizeof($notifications) > 0)
		{
			foreach($notifications as $key => $value)
				$notification_list .= '<div style="margin-bottom:5px;"><a class="fancybox" href="notification_view.php?id='.$value['notification_id'].'">'.$value['start_date'].' - '.$value['title'].'</a></div>';
		}
		else
			return '<div class="form_input_desc italic">No notifications available.</div>';

		return $notification_list;
	}

	// --===============================--
	// --== Student Subject Functions ==--
	// --===============================--

	function getSubjectLecturer($subject_id)
	{
		global $DB;

		$lecturer = $DB->getrow("SELECT * FROM user_profile
								INNER JOIN user_rel_subject ON user_rel_subject.user_id = user_profile.user_id
								INNER JOIN user_rel_group ON user_rel_group.user_id = user_profile.user_id
								WHERE user_rel_subject.subject_id = ".$subject_id." AND user_rel_group.group_id = 3");

		$return = "Lecturer: <a class='admin_profile' href='admin_profile.php?id=".$lecturer['profile_id']."'>".$lecturer['name']." ".$lecturer['surname']."</a>";

		return $return;
	}

	function getSubjectTutors($subject_id)
	{
		global $DB;

		$tutors = $DB->get("SELECT * FROM user_profile
								INNER JOIN user_rel_subject ON user_rel_subject.user_id = user_profile.user_id
								INNER JOIN user_rel_group ON user_rel_group.user_id = user_profile.user_id
								WHERE user_rel_subject.subject_id = ".$subject_id." AND user_rel_group.group_id = 4");

		$return = "List of tutors:<ul>";

		foreach($tutors as $key=>$value)
		{
			$return .= "<li> <a class='admin_profile' href='admin_profile.php?id=".$value['profile_id']."'>".$value['name']." ".$value['surname']."</a></li>";
		}

		$return .= "</ul>";

		return $return;
	}

	function getSubjectBooks($subject_id)
	{
		global $DB;

		$books = $DB->get("SELECT * FROM book
							INNER JOIN book_rel_subject ON book_rel_subject.book_id = book.book_id
							WHERE book_rel_subject.subject_id = ".$subject_id);

		$book_list = "<ul>";
		foreach($books as $key => $value)
		{
			$book_list .= "<li><b><a href='notification.php?id=".$value['book_id']."'>".$value['book_title']."</a>";
		}
		$book_list .= "</ul>";

		return $book_list;
	}

	function getSubjectLinks($subject_id)
	{
		global $DB;

		$links = $DB->get("SELECT * FROM link
							INNER JOIN link_rel_subject ON link_rel_subject.link_id = link.link_id
							WHERE link_rel_subject.subject_id = ".$subject_id);

		$link_list = "<ul>";
		foreach($links as $key => $value)
		{
			$link_list .= "<li><b><a href='notification.php?id=".$value['link_id']."'>".$value['link_title']."</a>";
		}
		$link_list .= "</ul>";

		return $link_list;
	}

	// --============================--
	// --== Conversation Functions ==--
	// --============================--

	function conversationGetThreads($conversation_id)
	{
		global $DB;

		$threads = $DB->get("SELECT * FROM thread
									INNER JOIN conversation_rel_thread ON thread.thread_id = conversation_rel_thread.thread_id
									WHERE conversation_id = ".$conversation_id." ORDER BY position ASC");
		$size = sizeof($threads);

		$your_name = $DB->getcell("SELECT CONCAT(name, ' ', surname) AS 'name' FROM user_profile WHERE user_id = ".$_SESSION['user']['user_id']);
		$other_name = "";
		$html = "";

		for ($k = 0; $k < $size; ++$k)
		{
			$bg_color = (($k%2) == 0 ? "#EEEEEE" : "#FFFFFF");

			if ($other_name == "")
			{
				if ($threads[$k]['user_id_from'] != $_SESSION['user']['user_id'])
					$other_name = $DB->getcell("SELECT CONCAT(name, ' ', surname) AS 'name' FROM user_profile WHERE user_id = ".$threads[$k]['user_id_from']);
				else
					$other_name = $DB->getcell("SELECT CONCAT(name, ' ', surname) AS 'name' FROM user_profile WHERE user_id = ".$threads[$k]['user_id_to']);
			}

			$html .= '<div id="thread_wrapper_'.$threads[$k]['thread_id'].'" class="thread_wrapper" style="background-color:'.$bg_color.'">
							<div class="thread_label">From:</div>
							<div class="text_normal float_left">'.($threads[$k]['user_id_from'] == $_SESSION['user']['user_id'] ? $your_name : $other_name).'</div>
							<div class="clear_both"></div>

							<div class="thread_label" style="margin-top:10px;">To:</div>
							<div class="text_normal float_left" style="margin-top:10px;">'.($threads[$k]['user_id_to'] == $_SESSION['user']['user_id'] ? $your_name : $other_name).'</div>
							<div class="clear_both"></div>

							<div class="thread_label" style="margin-top:10px;">Date:</div>
							<div class="text_normal float_left" style="margin-top:10px;">'.$threads[$k]['thread_datetime'].'</div>
							<div class="clear_both"></div>

							<div style="width:100%;height:1px;background-color: #3A66CC;margin-top:10px;margin-bottom:20px;"></div>

							<div class="text_normal" style="margin-top:10px;">'.$threads[$k]['thread_message'].'</div>
							<div class="clear_both"></div>
						</div>';
		}

		return $html;
	}

?>