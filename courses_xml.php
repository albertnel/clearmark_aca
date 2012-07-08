<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
	$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'course_name';
	$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	$where = "";
	if ($_SESSION['user']['user_id'] > 1)
	{
		$course_ids = $DB->getcol("SELECT course_id FROM user_rel_course WHERE user_id = ".$_SESSION['user']['user_id']);
		$where = "WHERE course.course_id IN (".join(",", $course_ids).")";
	}

	$rows = $DB->get("SELECT course.course_id, course_name, level_name FROM course
							LEFT JOIN course_rel_level ON course.course_id = course_rel_level.course_id
							LEFT JOIN level ON course_rel_level.level_id = level.level_id ".$where);

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
		$xml .= "<row id='".$row['course_id']."'>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['course_name'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['level_name'] != "" ? $row['level_name'] : "N/A")."]]></cell>";
		$xml .= "<cell><![CDATA[<a onclick='window.location=\"course_manage.php?".$row['course_id']."\";'>Manage</a> || <a onclick='deleteCourse(".$row['course_id'].");'>Delete</a>]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>