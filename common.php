<?php
	
	/**
	 * Error control
	 */
	error_reporting(E_ALL | E_STRICT);
	date_default_timezone_set('Asia/Taipei'); //// To depress PHP_NOTICE
	
	/**
	 * Constant definitions
	 */
	define('APP_ROOT', dirname(__FILE__));
	
	/**
	 * Log mode
	 */
	define('MODE_NONE',    0x0000);
	define('MODE_ERROR',   0x0001);
	define('MODE_WARNING', 0x0002);
	define('MODE_INFO',    0x0004);
	define('MODE_DEBUG',   0x0008);
	
	/**
	 * Include Config file
	 */
	 require_once APP_ROOT . '/configs/myConfig.php';
	
	/**
	 * Include library and classes
	 */
	require_once APP_ROOT . '/libs/h2o-php/h2o.php';
	require_once APP_ROOT . '/libs/simple-html-dom/simple_html_dom.php';
	require_once APP_ROOT . '/classes/Util.class.php';
	require_once APP_ROOT . '/classes/crawler/OneAppleCrawler.class.php';
	require_once APP_ROOT . '/classes/crawler/IpavoCrawler.class.php';
	require_once APP_ROOT . '/classes/crawler/IwantCrawler.class.php';
	require_once APP_ROOT . '/classes/crawler/EpaCrawler.class.php';
	
