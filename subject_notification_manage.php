<?php

	require_once("core/init.php");

	$notification_id = "";
	$parse['subject_id'] = $_SESSION['subject']['subject_id'];

	if (sizeof($_POST) > 0)
	{
		if ($_POST['notification_id'] == "") unset($_POST['notification_id']);
		$_POST['datetime'] = date("Y-m-d H:i:s");
		$DB->dbsave("notification", $_POST);

		$already = $DB->getcell("SELECT COUNT(*) FROM notification_rel_subject WHERE notification_id = ".$_POST['notification_id']." AND subject_id = ".$_SESSION['subject']['subject_id']);
		if (!$already) $DB->put("INSERT INTO notification_rel_subject VALUES(".$_POST['notification_id'].", ".$_SESSION['subject']['subject_id'].")");

		logAction("save", "notification", $_POST['notification_id'], ($already == 0 ? "Create notification" : "Update notification"), "subject_id=".$_SESSION['subject']['subject_id']);

		$notification_id = $_POST['notification_id'];

		$parse['end_script'] = '<script>
											ID("submit_msg").innerHTML = \'Notification saved successfully.\';
											ID("submit_msg").style.visibility = "visible";
										</script>';
	}

	if ($_SERVER['argv'][0] != "")
		$notification_id = $_SERVER['argv'][0];

	$parse['submit_button'] = ($notification_id == "" ? "Create notification" : "Save notification");

	if ($notification_id != "")
		$parse = array_merge($parse, $DB->getrow("SELECT * FROM notification WHERE notification_id = ".$notification_id));

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('subject_notification_manage.html');
	$result = tpParse($parse,$html);
	echo $result;

?>