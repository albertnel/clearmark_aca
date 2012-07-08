<?php

	require_once("core/init.php");

	if (sizeof($_POST) > 0)
	{
		set_time_limit(0);

		// -- Save book --
		$DB->dbsave("book", $_POST);
		$book_id = $_POST['book_id'];

		logAction("save", "book", $book_id, ($_POST['book_id'] == "" ? "Create book" : "Update book"));

		$count = $DB->getcell("SELECT COUNT(*) FROM book_rel_subject WHERE book_id = ".$book_id." AND subject_id = ".$_SESSION['subject']['subject_id']);
		if ($count == 0)
			$DB->put("INSERT INTO book_rel_subject VALUES (".$book_id.", ".$_SESSION['subject']['subject_id'].", '1')");

		// -- Create directory --
		$book_dir = "files/books/".$book_id;
		if (!file_exists($book_dir))
			mkdir($book_dir, 0777);

		// -- Index file --
		if (trim($_FILES['file_index']['name'] != ""))
		{
			$ext = substr($_FILES['file_index']['name'], -4);
			$ext = (substr($ext,0,1) != "." ? ".".$ext : $ext);
			$new_file = "index".$ext;
			move_uploaded_file($_FILES['file_index']['tmp_name'], $book_dir."/".$new_file);
			sleep(1);

			$chapter = array();
			$chapter_id = $DB->getcell("SELECT chapter_id FROM book_rel_chapter WHERE book_id = ".$book_id." AND position = 0");
			if ($chapter_id != "")
				$chapter['chapter_id'] = $chapter_id;
			$chapter['chapter_file'] = $book_dir."/".$new_file;
			$DB->dbsave("book_chapter", $chapter);
			if ($chapter_id == "")
				$DB->put("INSERT INTO book_rel_chapter VALUES(".$book_id.", ".$chapter['chapter_id'].", 0)");
		}

		// -- Chapter files --
		$fsize = sizeof($_FILES) - 1;
		for ($k = 1; $k <= $fsize; ++$k)
		{
			if (trim($_FILES['file'.$k]['name'] != ""))
			{
				$ext = substr($_FILES['file'.$k]['name'], -4);
				$ext = (substr($ext,0,1) != "." ? ".".$ext : $ext);
				$new_file = "chapter".$k.$ext;
				move_uploaded_file($_FILES['file'.$k]['tmp_name'], $book_dir."/".$new_file);
				sleep(1);

				$chapter = array();
				$chapter_id = $DB->getcell("SELECT chapter_id FROM book_rel_chapter WHERE book_id = ".$book_id." AND position = ".$k);
				if ($chapter_id != "")
					$chapter['chapter_id'] = $chapter_id;
				$chapter['chapter_file'] = $book_dir."/".$new_file;
				$DB->dbsave("book_chapter", $chapter);
				if ($chapter_id == "")
					$DB->put("INSERT INTO book_rel_chapter VALUES(".$book_id.", ".$chapter['chapter_id'].", ".$k.")");

				logAction("save", "chapter", $chapter['chapter_id'], ($chapter_id == "" ? "Create chapter" : "Update chapter"), "book_id=".$book_id);
			}
		}
	}

	if ($_SERVER['argv'][0] != "")
		$book_id = $_SERVER['argv'][0];

	$parse['submit_button'] = ($book_id == "" ? "Create book" : "Save book");

	if ($book_id != "")
	{
		$parse = array_merge($parse, $DB->getrow("SELECT * FROM book WHERE book_id = ".$book_id));
		$parse['chapter_count'] = $parse['chapter_already_count'] = $DB->getcell("SELECT COUNT(*) FROM book_rel_chapter WHERE book_id = ".$book_id) - 1;

		$chapter_files = $DB->getcol("SELECT chapter_file FROM book_chapter
												INNER JOIN book_rel_chapter ON book_chapter.chapter_id = book_rel_chapter.chapter_id
												WHERE book_id = ".$book_id."
												ORDER BY position ASC");
		$parse['index_file'] = '<a href="'.$chapter_files[0].'" target="_blank">View file</a>';
		unset($chapter_files[0]);
		$parse['chapter_files'] = join(",", $chapter_files);
	}

	$parse['subject_id'] = $_SESSION['subject']['subject_id'];

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('book_manage.html');
	$result = tpParse($parse,$html);
	echo $result;

?>