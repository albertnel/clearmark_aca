<?php

	require_once("core/init.php");

	$user = $DB->getrow("SELECT username, name, surname, gender, profile_pic, group_id FROM user_profile
										INNER JOIN user ON user_profile.user_id = user.user_id
										INNER JOIN user_rel_group ON user_profile.user_id = user_rel_group.user_id
										WHERE user_profile.user_id = ".$_SESSION['user']['user_id']);

	if ($user['profile_pic'] != "")
		$user_image = $user['profile_pic'];
	elseif (($user['profile_pic'] == "" & $user['gender'] == "M") || ($user['gender'] == ""))
		$user_image = "template/img/default_user_male.gif";
	else
		$user_image = "template/img/default_user_female.gif";

	$parse['user_photo'] = "<img src='".$user_image."' alt='User photo' style='max-width:160px;'>";
	$parse['nameplate_name'] = $user['name']." ".$user['surname']."<br/>".$user['username'];

	// -- Menu --
	if ($user['group_id'] == 1) $parse['button_hierarchy_container'] = '<div id="button_hierarchy" onclick="checkPerms(\'hierarchy\');"></div>';
	if ($user['group_id'] <= 3) $parse['button_admins_container'] = '<div id="button_admins" onclick="checkPerms(\'admins\');"></div>';
	if ($user['group_id'] <= 4)
	{
		$parse['button_students_container'] = '<div id="button_students" onclick="window.location=\'students.php\';"></div>';
		$parse['button_courses_container'] = '<div id="button_courses" onclick="window.location=\'courses.php\';"></div>';
		$parse['button_subjects_container'] = '<div id="button_subjects" onclick="window.location=\'subjects.php\';"></div>';
		$parse['button_messages_container'] = '<div id="button_messages" onclick="window.location=\'message_list.php\';"></div>';
	}

	// -- Faculties --
	$levels = $DB->getcol("SELECT level_name FROM level
									INNER JOIN user_rel_level ON level.level_id = user_rel_level.level_id
									WHERE user_id = ".$_SESSION['user']['user_id']." ORDER BY level_name ASC");
	$size = sizeof($levels);

	$html = '<table>
					<tr>
						<td class="text_normal bold" style="width:80px;">Faculties:</td>';
	if ($size > 0)
	{
		for ($k = 0; $k < $size; ++$k)
			$html .= '<td class="text_normal">'.$levels[$k].'</td></tr><tr><td></td>';
	}
	else
		$html .= '<td class="text_normal">N/A</td>';
	$html .= '</tr></table>';

	// -- Courses --
	$courses = $DB->getcol("SELECT course_name FROM course
								INNER JOIN user_rel_course ON course.course_id = user_rel_course.course_id
								WHERE user_id = ".$_SESSION['user']['user_id']." ORDER BY course_name ASC");
	$size = sizeof($courses);

	$html .= '<table>
					<tr>
						<td class="text_normal bold" style="width:80px;">Courses:</td>';
	if ($size > 0)
	{
		for ($k = 0; $k < $size; ++$k)
			$html .= '<td class="text_normal">'.$courses[$k].'</td></tr><tr><td></td>';
	}
	else
		$html .= '<td class="text_normal">N/A</td>';
	$html .= '</tr></table>';
	$parse['assigned_objects'] = $html;

	// -- Notifications --
	$course_ids = $DB->getcol("SELECT course_id FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']);
	$subject_ids = $DB->getcol("SELECT subject_id FROM user_rel_subject WHERE user_id = ".$_SESSION['user']['user_id']);
	if (sizeof($course_ids) > 0)
		$subject_ids = array_merge($subject_ids, $DB->getcol("SELECT subject_id FROM subject_rel_course WHERE course_id IN (".join(",", $course_ids).")"));
	$subject_ids = array_unique($subject_ids);
	$subject_ids = array_merge(array(), $subject_ids);

	$notification_ids = array();
	if (sizeof($course_ids) > 0)
		$notification_ids = $DB->getcol("SELECT notification.notification_id FROM notification INNER JOIN notification_rel_course ON notification.notification_id = notification_rel_course.notification_id WHERE course_id IN (".join(",", $course_ids).")");
	if (sizeof($subject_ids) > 0)
		$notification_ids = array_merge($notification_ids, $DB->getcol("SELECT notification.notification_id FROM notification INNER JOIN notification_rel_subject ON notification.notification_id = notification_rel_subject.notification_id WHERE subject_id IN (".join(",", $subject_ids).")"));
	$size = sizeof($notification_ids);

	if ($size > 0)
	{
		$where = 'WHERE ';
		if (sizeof($course_ids) > 0) $where .= 'course.course_id IN (".join(",", '.$course_ids.').") OR ';
		if (sizeof($subject_ids) > 0) $where .= 'subject.subject_id IN (".join(",", '.$subject_ids.').")';

		$notifications = $DB->get("SELECT title, start_date, course_name, subject_name FROM notification
											LEFT JOIN notification_rel_course ON notification.notification_id = notification_rel_course.notification_id
											LEFT JOIN course ON notification_rel_course.course_id = course.course_id
											LEFT JOIN notification_rel_subject ON notification.notification_id = notification_rel_subject.notification_id
											LEFT JOIN subject ON notification_rel_subject.subject_id = subject.subject_id
											".$where."
											ORDER BY start_date DESC
											LIMIT 3");

		$html = '<table style="width:100%;">';
		if (sizeof($notifications) > 0)
		{
			for ($k = 0; $k < 3; ++$k)
			{
				$html .= '<tr>
								<td class="text_normal">'.$notifications[$k]['title'].'</td>
								<td class="text_normal">'.$notifications[$k]['start_date'].'</td>
								<td class="text_normal">'.($notifications[$k]['course_name'] != "" ? $notifications[$k]['course_name'] : $notifications[$k]['subject_name']).'</td>
							</tr>';
			}
		}
		else
			$html .= '<span class="text_normal">N/A</span>';
		$html .= '</table>';
		$parse['latest_notifications'] = $html;
	}
	else
		$parse['latest_notifications'] = '<span class="text_normal">N/A</span>';

	// -- Events --
	$event_ids = array();
	if (sizeof($course_ids) > 0)
		$event_ids = $DB->getcol("SELECT event_id FROM event_rel_course WHERE course_id IN (".join(",", $course_ids).")");
	if (sizeof($subject_ids) > 0)
		$event_ids = array_merge($event_ids, $DB->getcol("SELECT event_id FROM event_rel_subject WHERE subject_id IN (".join(",", $subject_ids).")"));
	$size = sizeof($event_ids);

	if ($size > 0)
	{
		$where = 'WHERE ';
		if (sizeof($course_ids) > 0) $where .= 'course.course_id IN ('.join(",", $course_ids).') OR ';
		if (sizeof($subject_ids) > 0) $where .= 'subject.subject_id IN ('.join(",", $subject_ids).')';

		$events = $DB->get("SELECT Subject, StartTime, course_name, subject_name FROM jqcalendar
											LEFT JOIN event_rel_course ON jqcalendar.Id = event_rel_course.event_id
											LEFT JOIN course ON event_rel_course.course_id = course.course_id
											LEFT JOIN event_rel_subject ON jqcalendar.Id = event_rel_subject.event_id
											LEFT JOIN subject ON event_rel_subject.subject_id = subject.subject_id
											".$where."
											ORDER BY StartTime DESC
											LIMIT 3");

		$html = '<table style="width:100%;">';
		if (sizeof($events) > 0)
		{
			for ($k = 0; $k < 3; ++$k)
			{
				$html .= '<tr>
								<td class="text_normal">'.$events[$k]['Subject'].'</td>
								<td class="text_normal">'.$events[$k]['StartTime'].'</td>
								<td class="text_normal">'.($events[$k]['course_name'] != "" ? $events[$k]['course_name'] : $events[$k]['subject_name']).'</td>
							</tr>';
			}
		}
		else
			$html .= '<span class="text_normal">N/A</span>';
		$html .= '</table>';
		$parse['latest_events'] = $html;
	}
	else
		$parse['latest_events'] = '<span class="text_normal">N/A</span>';

	// -- Messages --
	$messages = array();
	$messages = $DB->get("SELECT subject, datetime_started, user_profile.name, user_profile.surname FROM conversation
									INNER JOIN conversation_rel_thread ON conversation.conversation_id = conversation_rel_thread.conversation_id
									INNER JOIN thread ON conversation_rel_thread.thread_id = thread.thread_id
									INNER JOIN user_profile ON thread.user_id_from = user_profile.user_id
									WHERE user_id_to = '".$_SESSION['user']['user_id']."'
									ORDER BY datetime_started DESC
									LIMIT 3");
	$size = sizeof($messages);

	if ($size > 0)
	{
		$html = '<table style="width:100%;">';
		if (sizeof($messages) > 0)
		{
			for ($k = 0; $k < 3; ++$k)
			{
				$html .= '<tr>
								<td class="text_normal">'.$messages[$k]['subject'].'</td>
								<td class="text_normal">'.$messages[$k]['datetime_started'].'</td>
								<td class="text_normal">'.$messages[$k]['name'].' '.$messages[$k]['surname'].'</td>
							</tr>';
			}
		}
		else
			$html .= '<span class="text_normal">N/A</span>';
		$html .= '</table>';
		$parse['latest_messages'] = $html;
	}
	else
		$parse['latest_messages'] = '<span class="text_normal">N/A</span>';

	// -- Messages --
	$unread_count = $DB->getcell("SELECT COUNT(*) FROM thread
											WHERE user_id_to = ".$_SESSION['user']['user_id']." AND viewed = 0");
	if ($unread_count > 0)
		$parse['messages_unread'] = '('.$unread_count.' new)';

	$parse['group_id'] = $_SESSION['user']['group_id'];
	$parse['admin_info'] = $DB->getcell("SELECT CONCAT(name, ' ', surname, ' (', group_name, ')') FROM user_profile
													INNER JOIN user_rel_group ON user_profile.user_id = user_rel_group.user_id
													INNER JOIN user_group ON user_rel_group.group_id = user_group.group_id
													WHERE user_profile.user_id = ".$_SESSION['user']['user_id']);

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('panel_superadmin.html');
	$result = tpParse($parse,$html);
	echo $result;

?>