<?php
	
	require_once dirname(__FILE__) . "/../common.php";
	
	$cfgLogType = 2;
	
	$arrEpisodes = Util :: loadEpisodesFromFile("../feeds/oneapple.rss");
	echo count($arrEpisodes);
	foreach ($arrEpisodes as $objEpisode) {
		print_r($objEpisode);
	}
