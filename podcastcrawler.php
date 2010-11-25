<?php
	
	require_once dirname(__FILE__) . "/common.php";
	
	Util :: log("podcastcrawler started", MODE_INFO);
	
	/**
	 * Create lock file
	 */
	$sPidFile = $cfgLogDir . '/podcastcrawler.pid';
	if (file_exists($sPidFile)) {
		$sErrorMessage = "another instance is running! pid=" . file_get_contents($sPidFile);
		Util :: log($sErrorMessage, MODE_ERROR);
		die("\nERROR: $sErrorMessage\n\n");
	}
	if (!file_put_contents($sPidFile, getmypid())) {
		$sErrorMessage = "fail to create lock file!";
		Util :: log($sErrorMessage, MODE_ERROR);
		die("\nERROR: $sErrorMessage\n\n");
	}
	
	try {
		$objCrawler = new OneAppleCrawler();
		$objCrawler -> crawl();
	}
	catch (Exception $e) {
		Util :: log ("Exception: " . $e -> getMessage(), MODE_ERROR);
		Util :: log (" + File: " . $e -> getFile(), MODE_ERROR);
		Util :: log (" + Line: " . $e -> getLine(), MODE_ERROR);
		echo "\nERROR: " . $e -> getMessage() . "\n\n";
	}
	
	/**
	 * Remove lock file
	 */
	if (!unlink($sPidFile)) {
		$sErrorMessage = "fail to remove lock file, you may need to remove it manually";
		Util :: log($sErrorMessage, MODE_WARNING);
		die("\nWARNING: $sErrorMessage\n\n");
	}
	
	Util :: log("podcastcrawler ended", MODE_INFO);
	
