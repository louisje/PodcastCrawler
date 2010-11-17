<?php
	
	require_once dirname(__FILE__) . "/common.php";
	
	try {
		$objCrawler = new OneAppleCrawler();
		$objCrawler -> crawl();
	}
	catch (Exception $e) {
		Util :: log ("Exception: " . $e -> getMessage(), MODE_ERROR);
		Util :: log (" + File: " . $e -> getFile(), MODE_ERROR);
		Util :: log (" + Line: " . $e -> getLine(), MODE_ERROR);
		die("\nERROR: " . $e -> getMessage() . "\n\n");
	}
