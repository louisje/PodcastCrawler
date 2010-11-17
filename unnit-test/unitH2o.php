<?php
	
	require_once dirname(__FILE__) . "/common.php";
	
	/**
	 * New a H2o object
	 */
	$objH2o = new H2o('podcast.tpl', array (
		'cache' => false,
		'searchpath' => './templates/')
	);
	
	$arrChannel = array (
		"title" => "Everybody Hates Chris",
		"link" => "http://everybody.hates.chris/",
		"language" => "zh_TW",
		"copyright" => "All Right Reverse",
		"subtitle" => "Chris Hates Everybody",
		"author" => "Chris",
		"summary" => "everybody hates Chris",
		"description" => "Chris hates everybody",
		"owner" => array (
			"name" => "Chris",
			"email" => "chris@hates.everybody",
		),
		"image" => "http://everybody.hates/chris.jpg",
	);
	
	$arrEpisodes = array (
		array (
			"title" => "ep. 1",
			"subtitle" => "Go to school",
			"summary" => "Chris go to school first day",
			"media" => array (
				"url" => "http://everybody.hates.chris/downlaod/ep1.wmv",
				"length" => "12343",
				"type" => "x-medai/wmv",
			),
			"guid" => "http://everybody.hates.chris/download/ep1.wmv",
			"date" => "Web, 8 Jun 2005 19:00:00 GMT",
			"duration" => "4:34",
			"keywords" => "everybody, hates, chris",
		),
		array (
			"title" => "ep. 2",
			"subtitle" => "Fall in love",
			"summary" => "Chris fall in love first time",
			"media" => array (
				"url" => "http://everybody.hates.chris/download/ep2.wmv",
				"length" => "3832",
				"type" => "x-media/wmv",
			),
			"guid" => "http://everybody.hates.chris/download/ep2.wmv",
			"date" => "Web, 8 Jun 2005 19:00:00 GMT",
			"duration" => "4:34",
			"keywords" => "everybody, hates, chris",
		),
	);
	
	echo $objH2o -> render(array (
		"channel" => $arrChannel,
		"episodes" => $arrEpisodes)
	);
	
