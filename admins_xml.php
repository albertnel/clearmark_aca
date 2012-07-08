<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 15;
	$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'username';
	$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	$where = "";
	if ($_SESSION['user']['user_id'] > 1)
	{
		$user_ids = array();
		$temp = array();

		$where = "group_id != 1 AND group_id != 5 ";
		for ($k = 1; $k < $_SESSION['user']['group_id']; ++$k)
			$where .= 'AND group_id != '.($k+1).' ';

		// -- Get assigned courses --
		$course_ids = $DB->getcol("SELECT course_id FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']);
		if (sizeof($course_ids) > 0)
		{
			$temp = $DB->getcol("SELECT user_rel_course.user_id FROM user_rel_course
										INNER JOIN user_rel_group ON user_rel_course.user_id = user_rel_group.user_id
										WHERE ".$where);
		}
		$user_ids = array_merge($user_ids, $temp);

		// -- Get assigned subjects --
		$subject_ids = $DB->getcol("SELECT subject_id FROM user_rel_subject WHERE user_id = ".$_SESSION['user']['user_id']);
		if (sizeof($course_ids) > 0)
			$subject_ids = array_merge($subject_ids, $DB->getcol("SELECT subject_id FROM subject_rel_course WHERE course_id IN (".join(",", $course_ids).")"));

		if (sizeof($subject_ids) > 0)
		{
			$temp = $DB->getcol("SELECT user_rel_subject.user_id FROM user_rel_subject
										INNER JOIN user_rel_group ON user_rel_subject.user_id = user_rel_group.user_id
										WHERE ".$where);
		}
		$user_ids = array_merge($user_ids, $temp);

		// -- Get created but unassigned users --
		$temp = $DB->getcol("SELECT user_id FROM user WHERE user_id_created = '".$_SESSION['user']['user_id']."'");
		$user_ids = array_merge($user_ids, $temp);

		// -- Make unique --
		$user_ids = array_unique($user_ids);
		$user_ids = array_merge(array(), $user_ids);

		if (sizeof($user_ids) == 0) $user_ids[] = -1;

		$where = "AND user_profile.user_id IN (".join(",", $user_ids).")";
	}

	$rows = $DB->get("SELECT user.user_id, username, name, surname, group_name FROM user_profile
							INNER JOIN user ON user_profile.user_id = user.user_id
							INNER JOIN user_rel_group ON user_profile.user_id = user_rel_group.user_id
							INNER JOIN user_group ON user_rel_group.group_id = user_group.group_id
							WHERE user_rel_group.group_id < 5 ".$where." AND user_profile.user_id != ".$_SESSION['user']['user_id']);
	if($qtype && $query)
	{
		$query = strtolower(trim($query));
		foreach($rows as $key => $row)
			if(strpos(strtolower($row[$qtype]),$query) === false) unset($rows[$key]);
	}

	//Make PHP handle the sorting
	$sort_array = array();
	foreach ($rows as $key => $row)
		$sort_array[$key] = $row[$sortname];

	$sort_method = SORT_ASC;
	if($sortorder == 'desc')
		 $sort_method = SORT_DESC;

	array_multisort($sort_array, $sort_method, $rows);
	$total = count($rows);
	$rows = array_slice($rows,($page-1)*$rp,$rp);

	header("Content-type: text/xml");
	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	$xml .= "<rows>";
	$xml .= "<page>$page</page>";
	$xml .= "<total>$total</total>";
	foreach($rows as $row)
	{
		$xml .= "<row id='".$row['user_id']."'>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['username'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['name'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['surname'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['group_name'])."]]></cell>";
		$xml .= "<cell><![CDATA[<a onclick='window.location=\"admin_manage.php?".$row['user_id']."\";'>Edit</a> || <a onclick='deleteUser(".$row['user_id'].");'>Delete</a>]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>