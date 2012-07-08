<?php

	require_once("core/init.php");

	$notification = $DB->getrow("SELECT title, content FROM notification
									WHERE notification_id = ".$_GET['id']);

	echo "<table width='600px' height='450px'>";
	echo "<tr height='30px'>";
	echo "<td width='75px' class='text_normal bold'>Title:</td>";
	echo "<td>".$notification['title']."</td>";
	echo "</tr>";

	echo "<tr valign='top'>";
	echo "<td><b>Content: </b></td>";
	echo "<td><div style='height:570px;width=100%;overflow:auto;'>".$notification['content']."</div></td>";
	echo "</tr>";
	echo "<table>";

?>