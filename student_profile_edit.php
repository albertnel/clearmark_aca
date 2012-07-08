<?php

	require_once("core/init.php");

	if (sizeof($_POST) > 0)
	{
		if ($_FILES['profile_pic']['name'] != "")
		{
			// -- Get extension of file --
			$count = 0;
			while ($_FILES['profile_pic']['name'][$count] != "")
			{
				if ($_FILES['profile_pic']['name'][$count] == ".")
					$pos = $count;

				++$count;
			}
			$ext = substr($_FILES['profile_pic']['name'], $pos, strlen($_FILES['profile_pic']['name']));

			// -- Upload file --
			$result = 0;
			$target_path = "images/profile_pics/".$_SESSION['user']['user_id']."_".time().$ext;
			move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path);
			$_POST['profile_pic'] = $target_path;
		}

		if ($_POST['password'] != "")
		{
			$_POST['password'] = md5($_POST['password']);
			$DB->dbsave("user", $_POST);
		}

		$DB->dbsave("user_profile", $_POST);

		logAction("save", "student", $_POST['user_id'], "Update profile");
	}

	$user = $DB->getrow("SELECT user_profile.*, user.username FROM user_profile
						INNER JOIN user ON user.user_id = user_profile.user_id
						WHERE user_profile.user_id = ".$_SESSION['user']['user_id']);
	$parse = $user;
	$parse['student_logo_name'] = $user['name']." ".$user['surname']."<br/>".$user['username'];
	$parse['name_combo'] = $user['name']." ".$user['surname'];

	// -- Profile pic --
	if ($user['profile_pic'] != "")
		$user_image = $user['profile_pic'];
	elseif ($user['profile_pic'] == "" & $user['gender'] == "M")
		$user_image = "template\img\default_user_male.gif";
	else
		$user_image = "template\img\default_user_female.gif";
	$parse['user_image'] = "<img src='".$user_image."' alt='User Image' style='max-width:250px;'>";

	// -- Gender radio button --
	$parse['gender_radio'] = ' <label for="male" class="text_normal">Male</label>
										<input type="radio" name="gender" id="male" value="M" '.($user['gender'] == "M" ? 'checked="checked"' : "").' />
										<label for="female" class="text_normal">Female</label>
										<input type="radio" name="gender" id="female" value="F" '.($user['gender'] == "F" ? 'checked="checked"' : "").' />';

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('student_profile_edit.html');
	$result = tpParse($parse,$html);
	echo $result;
?>