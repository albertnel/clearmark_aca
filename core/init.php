<?php

	session_start();

	// -- Initialize DB --
	require_once("database/class.db.php");
	$DB = new DB("database/conf.db.php");

	// -- Include functions file --
	require_once("functions.php");

	// -- Include HTML parse class --
	require_once("parse.php");
	require_once("class.parse.php");
	$parse = array();

	// -- Include AJAX --
	require_once("lib/ajax/PHPLiveX.php");
	require_once("ajax_func.php");
	$ajax = new PHPLiveX();
	$ajax->Ajaxify($ajax_function);

?>