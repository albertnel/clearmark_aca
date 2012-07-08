<?php

	require_once("core/init.php");

	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$rp = isset($_POST['rp']) ? $_POST['rp'] : 15;
	$query = isset($_POST['query']) ? $_POST['query'] : false;
	$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

	// -- Where --
	if (!isset($_SESSION['message']['user_id']))
		$where = "WHERE thr1.user_id_from = ".$_SESSION['user']['user_id']." OR thr1.user_id_to = ".$_SESSION['user']['user_id'];
	else
		$where = "WHERE (thr1.user_id_from = ".$_SESSION['user']['user_id']." AND thr1.user_id_to = ".$_SESSION['message']['user_id'].") OR (thr1.user_id_to = ".$_SESSION['user']['user_id']." AND thr1.user_id_from = ".$_SESSION['message']['user_id'].")";

	// -- Query --
	$rows = $DB->get("SELECT cnv1.conversation_id,
									sub1.subject_name,
									cnv1.subject,
									MAX(thr1.thread_datetime) AS 'thread_datetime',
									thr1.user_id_from,
									thr1.user_id_to,
									(SELECT COUNT(*) FROM thread AS thr2
										INNER JOIN conversation_rel_thread AS crt2
											ON thr2.thread_id = crt2.thread_id
										WHERE crt2.conversation_id = cnv1.conversation_id AND thr2.user_id_to = ".$_SESSION['user']['user_id']." AND thr2.viewed = 0) as status
							FROM conversation AS cnv1
							INNER JOIN conversation_rel_thread AS crt1
								ON cnv1.conversation_id = crt1.conversation_id
							INNER JOIN thread AS thr1
								ON crt1.thread_id = thr1.thread_id
							LEFT JOIN subject AS sub1
								ON cnv1.subject_id = sub1.subject_id
							".$where."
							GROUP BY cnv1.conversation_id");
	foreach ($rows as &$row)
	{
		$user_ids = $DB->getrow("SELECT user_id_from, user_id_to FROM thread
										INNER JOIN conversation_rel_thread ON thread.thread_id = conversation_rel_thread.thread_id
										WHERE conversation_id = ".$row['conversation_id']." AND position = 1");
		$row['other_user_id'] = ($user_ids['user_id_from'] != $_SESSION['user']['user_id'] ? $user_ids['user_id_from'] : $user_ids['user_id_to']);
		$row['other_user_name'] = $DB->getcell("SELECT CONCAT(name, ' ', surname) FROM user_profile WHERE user_id = ".$row['other_user_id']);
	}

	if($qtype && $query)
	{
		$query = strtolower(trim($query));
		foreach($rows as $key => $row)
			if(strpos(strtolower($row[$qtype]),$query) === false) unset($rows[$key]);
	}

	//Make PHP handle the sorting
	$size = sizeof($rows);
	$sort_array = array();
	for ($k = 0; $k < $size; ++$k)
		$sort_array[] = $rows[$k]["thread_datetime"];

	array_multisort($sort_array, SORT_DESC, $rows);
	$total = count($rows);
	$rows = array_slice($rows,($page-1)*$rp,$rp);

	// -- Order by new messages on top, read message following that --
	$new_arr = array();
	$old_arr = array();
	for ($k = 0; $k < $size; ++$k)
	{
		if ($rows[$k]['status'] >= 1) $new_arr[] = $rows[$k];
		else $old_arr[] = $rows[$k];
	}
	$rows = array_merge($new_arr, $old_arr);

	header("Content-type: text/xml");
	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	$xml .= "<rows>";
	$xml .= "<page>$page</page>";
	$xml .= "<total>$total</total>";
	foreach($rows as $row)
	{
		$xml .= "<row id='".$row['conversation_id'].",".$row['other_user_id']."' onclick='alert(1);'>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['other_user_name'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode("<span class='bold'>".$row['subject']."</span>")."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['subject_name'] != "" ? $row['subject_name'] : "N/A")."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['thread_datetime'])."]]></cell>";
		$xml .= "<cell><![CDATA[".utf8_encode($row['status'] >= 1 ? "<span class='bold italic'>New message</span>" : "-")."]]></cell>";
		$xml .= "</row>";
	}
	$xml .= "</rows>";
	echo $xml;

?>