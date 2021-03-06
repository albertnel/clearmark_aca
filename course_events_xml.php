<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 15;
	$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'StartTime';
	$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'desc';
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	$rows = $DB->get("SELECT * FROM jqcalendar
							INNER JOIN event_rel_course ON jqcalendar.Id = event_rel_course.event_id
							WHERE course_id = ".$_SESSION['course']['course_id']);
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
		$xml .= "<row id='".$row['notification_id']."'>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['Subject'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['StartTime'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['EndTime'])."]]></cell>";
		$xml .= "<cell><![CDATA[<a onclick='deleteEvent(".$row['Id'].");'>Delete</a>]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>