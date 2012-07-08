<?php

	require_once("core/init.php");

	$parse['subject_id'] = $_SESSION['subject']['subject_id'];
	$parse['book_id'] = $_SERVER['argv'][0];
	$book = $DB->getrow("SELECT book_title, book_code FROM book WHERE book_id = ".$_SERVER['argv'][0]);
	$parse['book_name_field'] = $book['book_title']." (".$book['book_code'].")";		

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('book_copy.html');
	$result = tpParse($parse,$html);
	echo $result;

?>