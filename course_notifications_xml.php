<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 15;
	$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'start_date';
	$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'asc';
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	$rows = $DB->get("SELECT * FROM notification
							INNER JOIN notification_rel_course ON notification.notification_id = notification_rel_course.notification_id
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
		$xml .= "<cell><![CDATA[".utf8_encode($row['title'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['start_date'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['end_date'])."]]></cell>";
		$xml .= "<cell><![CDATA[<a onclick='window.location=\"course_notification_manage.php?".$row['notification_id']."\";'>Edit</a> || <a onclick='deleteNotification(".$row['notification_id'].");'>Delete</a>]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>