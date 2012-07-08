<?php

	require_once("core/init.php");

	function itemImportEntryIsValid($contents)
	{
		if (($contents[0] != "") && ($contents[1] != "") && ($contents[2] != "") && ($contents[3] != ""))
		{
			if (!ajaxValidateEmail($contents[3]))
				return 0;
			else
				return 1;
		}
		else
			return 0;
	}

	if (sizeof($_FILES) > 0)
	{
		set_time_limit(0);

		// -- Initialize CSV file --
		$valid = $invalid = 0;
		$contents = array();
		$fh = fopen($_FILES['import_file']['tmp_name'], 'r');

		// -- Initialize CSV error file --
		$filename = "files/csv/import_errors_".time().".csv";
		$csv = fopen($filename, "w");
		$entries = array();

		while($contents = fgetcsv($fh))
		{
			if (itemImportEntryIsValid($contents))
			{
				$valid++;
				$student = array();
				$student['username'] = $contents[0];
				$student['password'] = md5($contents[0]);
				$student['name'] = $contents[1];
				$student['surname'] = $contents[2];
				$student['email'] = $contents[3];

				$DB->dbsave("user", $student);
				$DB->dbsave("user_profile", $student);

				$already = $DB->getcell("SELECT COUNT(*) FROM user_rel_group WHERE user_id = ".$student['user_id']);
				if (!$already) $DB->put("INSERT INTO user_rel_group VALUES(".$student['user_id'].", 5)");

				logAction("import", "student", $student['user_id'], "Import student");
			}
			else
			{
				$invalid++;
				$entries[] = join(",", $contents);
			}

		}

		// -- Write to error file --
		foreach ($entries as $entry)
			fputcsv($csv, split(",", $entry));
		fclose($csv);

		// -- Results --
		$parse['import_result_msg'] = '	<div class="text_normal" style="padding-top:10px;">'.$valid.' students have imported successfully.</div>';
		if ($invalid > 0) $parse['import_result_msg'] .= '<div class="text_normal">'.$invalid.' students have missing or invalid data and could not be imported.</div>
																			<div class="text_normal"><a href="'.$filename.'">Click here</a> to download the file containing these items.</div>';
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('student_import.html');
	$result = tpParse($parse,$html);
	echo $result;

?>