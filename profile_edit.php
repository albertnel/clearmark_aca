<?php

	require_once("core/init.php");
	
	if (sizeof($_POST) > 0)
	{
		$destination_path = getcwd().DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR."profile_pics".DIRECTORY_SEPARATOR;
		$result = 0;
		$target_path = $destination_path . basename( $_FILES['profile_pic']['name']);
		
		if ((($_FILES["profile_pic"]["type"] == "image/gif") || ($_FILES["profile_pic"]["type"] == "image/jpeg")) 
				&& ($_FILES["profile_pic"]["size"] < 20000))
		{
			if(@move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path))
			{
				$result = 1;
				$_POST['profile_pic'] = "images".DIRECTORY_SEPARATOR."profile_pics".DIRECTORY_SEPARATOR.basename( $_FILES['profile_pic']['name']);
			}
		}
		else
		{
			$error = "Invalid upload";
		}
		
		if(!$error)
		{
			$DB->dbsave("user_profile", $_POST);
			
			if($_POST['password'])
			{
				$_POST['password'] = MD5($_POST['password']);
				$DB->dbsave("user", $_POST);
			}
		}
	}
	
	$user = $DB->getrow("SELECT user_profile.*, user.username FROM user_profile 
						INNER JOIN user ON user.user_id = user_profile.user_id 
						WHERE user_profile.user_id = ".$_SESSION['user']['user_id']);
	
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
	
	$parse = $user;
	$parse['student_number'] = $user['username'];
	$parse['user_image'] = "<img src='".$user_image."' alt='User Image'>";
	
	$parse['gender_radio'] = '<label for="female">Female</label>
								<input type="radio" name="gender" id="female" value="F" '.($user['gender'] == "F" ? 'checked="checked"' : "").' />
								<label for="male">Male</label>
								<input type="radio" name="gender" id="male" value="M" '.($user['gender'] == "M" ? 'checked="checked"' : "").' />';
	
	$parse['ajax'] = $ajax->Run();
	$html = file_get_contents('profile_edit.html');
	$result = tpParse($parse,$html);
	echo $result;
?>