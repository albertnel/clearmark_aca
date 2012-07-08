<?php

	require_once("core/init.php");

	$book_id = $parse['book_id'] = $_SERVER['argv'][0];

	// -- Generate book and chapters HTML --
	$book = $DB->getrow("SELECT * FROM book WHERE book_id = ".$book_id);
	$parse['page_title'] = 'Book View - '.$book['book_title'];

	$chapters = $DB->get("SELECT book_chapter.chapter_id, chapter_file, position FROM book_chapter
								INNER JOIN book_rel_chapter ON book_chapter.chapter_id = book_rel_chapter.chapter_id
								WHERE book_id = ".$book_id." ORDER BY position ASC");
	$csize = sizeof($chapters);

	$bookmark = $DB->getrow("SELECT book_chapter.chapter_id, chapter_file FROM book_chapter
										INNER JOIN user_bookmark ON book_chapter.chapter_id = user_bookmark.chapter_id
										WHERE user_id = ".$_SESSION['user']['user_id']." AND book_id = ".$book_id);
	$parse['bookmark_chapter_id'] = $bookmark['chapter_id'];
	$parse['bookmark_chapter_file'] = $bookmark['chapter_file'];

	$html = '<h2 style="padding-bottom:20px;" class="bold">'.$book['book_title'].'</h2>
				<div style="margin-bottom:5px;padding:5px;background-color:'.$bg_color.'">
					<div class="book_chapter"><a onclick="viewChapter(\''.$chapters[0]['chapter_file'].'\');">Index</a></div>
					<div class="clear_both"></div>
				</div>';
	for ($k = 1; $k < $csize; ++$k)
	{
		$bg_color = (($k%2) == 0 ? "#fff" : "#d8e8ef");

		$html .= '<div style="margin-bottom:5px;padding:5px;background-color:'.$bg_color.'">
						<div class="book_chapter"><a onclick="viewChapter(\''.$chapters[$k]['chapter_file'].'\');">Chapter '.$k.'</a></div>
						<div class="bookmark_'.($bookmark['chapter_id'] == $chapters[$k]['chapter_id'] ? 'selected' : 'chapter').'" id="bookmark_'.$chapters[$k]['chapter_id'].'" onclick="bookmarkChapter(\''.$chapters[$k]['chapter_id'].'\');"></div>
						<div class="clear_both"></div>
					</div>';
	}
	$html .= '<div id="book_msg">&nbsp;</div>';
	$parse['book_files_wrapper'] = $html;

	$parse['ajax'] = $ajax->Run();
	$parse['header_wrapper'] = file_get_contents('template/header.html');
	$parse['footer_wrapper'] = file_get_contents('template/footer.html');
	$html = file_get_contents('book_view.html');
	$result = tpParse($parse,$html);
	echo $result;

?>