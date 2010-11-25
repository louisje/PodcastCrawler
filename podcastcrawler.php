<?php
	
	require_once dirname(__FILE__) . "/common.php";
	
	/**
	 * Create lock file
	 */
	$sPidFile = $cfgLogDir . '/podcastcrawler.pid';
	if (file_exists($sPidFile))
		die("\nERROR: another instance is running!\n\n");
	if (!file_put_contents($sPidFile, getmypid()))
		die("\nERROR: fail to create lock file!\n\n");
	
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
	if (!unlink($sPidFile))
		die("\nWARNING: fail to remove lock file, you may need to remove it manually.\n\n");
	
