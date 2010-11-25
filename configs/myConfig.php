<?php
	/**
	 * FFProbe path
	 */
	$cfgFFProbePath = '/home/louis/local/bin/ffprobe';
	
	/**
	 * Where to put generated RSS files
	 */
	$cfgRssDir = APP_ROOT . '/feeds';
	$cfgRssUrl = 'http://202.5.224.193/louis/PodcastCrawler/feeds';
	
	/**
	 * Debug & Log
	 *
	 * APP_ROOT means the root path of Converter_Worker installed.
	 */
	$cfgLogDir = APP_ROOT . '/logs'; //// .... The folder to write log. (must be writable)
	$cfgDebugMode = MODE_NONE | MODE_ERROR | MODE_WARNING | MODE_INFO | MODE_DEBUG; //// .... Log options
	
	/**
	 * $cfgLogType - How to write log
	 *
	 * 0: syslog()
	 * 1: Send by e-mail
	 * 2: STDERR
	 * 3: Log to file (DEFAULT)
	 * 4: SAPI
	 */
	$cfgLogType = 3;
