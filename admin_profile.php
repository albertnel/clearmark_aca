<?php

	require_once("core/init.php");

	$user = $DB->getrow("SELECT name, surname, gender, email, profile_pic FROM user_profile
						WHERE profile_id = ".$_GET['id']);

	if($user['profile_pic'] != "")
	{
		$user_image = $user['profile_pic'];
	}
	elseif($user['profile_pic'] == "" & $user['gender'] == "M")
	{
		$user_image = "template\img\default_user_male.gif";
	}
	else
	{
		$user_image = "template\img\default_user_female.gif";
	}

	$parse['name'] = $user['name']." ".$user['surname'];
	$parse['email'] = "<a href='mailto:".$user['email']."'>".$user['email']."</a>";
	$parse['ajax'] = $ajax->Run();
	$parse['user_image'] = "<img src='".$user_image."' alt='User Image'>";

	$html = file_get_contents('admin_profile.html');
	$result = tpParse($parse,$html);
	echo $result;

?>