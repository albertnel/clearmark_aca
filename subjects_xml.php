<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
	$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'subject_name';
	$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	$where = "";
	if ($_SESSION['user']['user_id'] > 1)
	{
		$course_ids = $DB->getcol("SELECT course_id FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']);

		$subject_ids = $DB->getcol("SELECT subject_id FROM user_rel_subject WHERE user_id = ".$_SESSION['user']['user_id']);
		if (sizeof($course_ids) > 0)
			$subject_ids = array_merge($subject_ids, $DB->getcol("SELECT subject_id FROM subject_rel_course WHERE course_id IN (".join(",", $course_ids).")"));

		$where = "WHERE subject.subject_id IN (".join(",", $subject_ids).")";
	}

	$rows = $DB->get("SELECT subject.subject_id, subject_name, subject_code FROM subject ".$where);
	$size = sizeof($rows);
	for ($k = 0; $k < $size; ++$k)
	{
		$rows[$k]['student_count'] = $DB->getcell("SELECT COUNT(user_rel_subject.user_id) FROM user_rel_subject
																INNER JOIN user_rel_group ON user_rel_subject.user_id = user_rel_group.user_id
																WHERE subject_id = ".$rows[$k]['subject_id']." AND group_id = 5");
		$rows[$k]['course_name'] = join(", ", $DB->getcol("SELECT course_name FROM course INNER JOIN subject_rel_course ON course.course_id = subject_rel_course.course_id WHERE subject_id = ".$rows[$k]['subject_id']));
	}

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
		$xml .= "<row id='".$row['subject_id']."'>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['subject_name'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode(($row['subject_code'] != "" ? $row['subject_code'] : "n/a"))."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['course_name'] != "" ? $row['course_name'] : "N/A")."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['student_count'])."]]></cell>";
		$xml .= "<cell><![CDATA[<a onclick='window.location=\"subject_manage.php?".$row['subject_id']."\";'>Manage</a> || <a onclick='deleteSubject(".$row['subject_id'].");'>Delete</a>]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>