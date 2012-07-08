<?php

	require_once("core/init.php");

	function itemImportEntryIsValid($contents)
	{
		global $DB;

		if (($contents[0] != "") && ($contents[1] != ""))
		{
			$count = $DB->getcell("SELECT COUNT(*) FROM user WHERE username = '".$contents[0]."'");
			if ($count == 0) return 0;

			$count = $DB->getcell("SELECT COUNT(*) FROM subject WHERE subject_code = '".$contents[1]."'");
			if ($count == 0)
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
				$user_id = $DB->getcell("SELECT user_id FROM user WHERE username = '".$contents[0]."'");
				$subject_id = $DB->getcell("SELECT subject_id FROM subject WHERE subject_code = '".$contents[1]."'");
				$DB->put("UPDATE user_rel_subject SET active = 0 WHERE user_id = ".$user_id." AND subject_id = ".$subject_id);
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
		$parse['import_result_msg'] = '	<div class="text_normal" style="padding-top:10px;">'.$valid.' students have been unassigned successfully.</div>';
		if ($invalid > 0) $parse['import_result_msg'] .= '<div class="text_normal">'.$invalid.' students have missing or invalid data and could not be assigned.</div>
																			<div class="text_normal"><a href="'.$filename.'">Click here</a> to download the file containing these items.</div>';
	}

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('students_unassign_subject.html');
	$result = tpParse($parse,$html);
	echo $result;

?>