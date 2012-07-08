<?php

	require_once("core/init.php");

	$books = $DB->get("SELECT book_id, book_title FROM book ORDER BY book_title ASC");
	$size = sizeof($books);

	for ($k = 0; $k < $size; ++$k)
	{
		$html .= '<div class="text_normal">'.$books[$k]['book_title'].' - <a href="book_manage.php?'.$books[$k]['book_id'].'">Edit</a> || <a href="book_preview.php?'.$books[$k]['book_id'].'">Preview</a></div>';
	}
	$parse['books'] = $html;

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('books.html');
	$result = tpParse($parse,$html);
	echo $result;

?>