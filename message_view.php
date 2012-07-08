<?php

	require_once("core/init.php");

	$conversation_id = "";
	$user_id_to = "";

	// -- Page referer --
	$referer = explode("/", $_SERVER['HTTP_REFERER']);
	if ($referer[sizeof($referer)-1] == "message_list.php")
		$_SESSION['message']['referer'] = "message_list.php";
	else if ($referer[sizeof($referer)-1] == "students.php")
		$_SESSION['message']['referer'] = "students.php";
	else if (strpos($referer[sizeof($referer)-1], "subject_home.php") !== false)
		$_SESSION['message']['referer'] = $referer[sizeof($referer)-1];
	$parse['referer'] = $_SESSION['message']['referer'];

	if ($_SERVER['argv'][0] != "") $conversation_id = $_SERVER['argv'][0];
	if ($_SERVER['argv'][1] != "") $user_id_to = $_SERVER['argv'][1];

	if (sizeof($_POST) > 0)
	{
		// -- Save conversation --
		if ($_POST['conversation_id'] == "")
		{
			$data = array();
			$data['subject'] = $_POST['subject'];
			$data['datetime_started'] = date("Y-m-d H:i:s");
			$DB->dbsave("conversation", $data);
			$conversation_id = $data['conversation_id'];

			logAction("save", "conversation", $conversation_id, "Create conversation");
		}
		else
			$conversation_id = $_POST['conversation_id'];

		// -- Save thread --
		$data = array();
		$data['thread_datetime'] = date("Y-m-d H:i:s");
		$data['thread_message'] = $_POST['message'];
		$data['user_id_from'] = $_SESSION['user']['user_id'];
		$data['user_id_to'] = $_POST['user_id_to'];
		$DB->dbsave("thread", $data);

		$count = $DB->getcell("SELECT COUNT(*) FROM conversation_rel_thread WHERE conversation_id = ".$conversation_id);
		$DB->put("INSERT INTO conversation_rel_thread VALUES (".$conversation_id.", ".$data['thread_id'].", ".($count+1).")");

		$user_id_to = $_POST['user_id_to'];

		logAction("save", "thread", $data['thread_id'], "conversation_id=".$conversation_id);
	}

	$parse['conversation_id'] = $conversation_id;
	$parse['user_id_to'] = $user_id_to;
	$name = $DB->getcell("SELECT CONCAT(name, ' ', surname) AS 'name' FROM user_profile WHERE user_id = ".$user_id_to);

	// -- New conversation --
	if ($conversation_id == "")
	{
		$html = '<div class="form_label float_left">To:</div>
					<div class="form_input float_left text_normal">'.$name.'</div>
					<div class="clear_both"></div>

					<div class="form_label float_left">Subject:</div>
					<div class="form_input float_left">
						<input type="textbox" id="subject" name="subject" class="textbox" />
						<div id="subject_desc"><span class="form_input_desc">The subject of the conversation.</span></div>
					</div>
					<div class="clear_both"></div>

					<div class="form_label float_left">Message:</div>
					<div class="form_input float_left">
						<textarea id="message" name="message" style="width:100%;height:150px;"></textarea>
						<div id="message_desc"><span class="form_input_desc">&nbsp;</span></div>
					</div>
					<div class="clear_both"></div>

					<div class="form_submit">
						<input id="submit_button" type="button" value="Send message" onclick="sendMessage();" />
						<span id="send_msg" class="send_msg"></span>
					</div>';
	}
	else
	{
		// -- Mark as "read" --
		$thread_ids = $DB->getcol("SELECT thread.thread_id FROM thread
											INNER JOIN conversation_rel_thread ON thread.thread_id = conversation_rel_thread.thread_id
											WHERE conversation_id = ".$conversation_id." AND user_id_to = ".$_SESSION['user']['user_id']." AND viewed = 0");
		if (sizeof($thread_ids) > 0)
			$DB->put("UPDATE thread SET viewed = 1 WHERE thread_id IN (".join(",", $thread_ids).")");

		// -- Get subject --
		$parse['subject_field'] = $DB->getcell("SELECT subject FROM conversation WHERE conversation_id = ".$conversation_id);

		// -- Get threads --
		$html = conversationGetThreads($conversation_id);
		$html .= '<div class="text_normal bold" style="margin-top:20px;">Reply:</div>
					<div>
						<textarea id="message" name="message" class="thread_reply" placeholder="Click here to reply."></textarea>
						<div id="message_desc"><span class="form_input_desc">&nbsp;</span></div>
					</div>
					<div class="clear_both"></div>

					<div style="margin-top:10px;">
						<input id="submit_button" type="button" value="Send message" onclick="sendMessage();" />
						<span id="send_msg" class="send_msg"></span>
					</div>';
		$parse['view_thread_id'] = $DB->getcell("SELECT thread_id FROM conversation_rel_thread
																WHERE position = (SELECT MAX(position) FROM conversation_rel_thread WHERE conversation_id = ".$conversation_id.") AND conversation_id = ".$conversation_id);
	}
	$parse['new_message_wrapper'] = $html;

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('message_view.html');
	$result = tpParse($parse,$html);
	echo $result;

?>