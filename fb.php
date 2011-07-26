<?php
	
	require_once dirname(__FILE__) . "/common.php";
	
	define('FB_GRAPH_API_URL', 'http://graph.facebook.com/');
	
	if (empty ($_REQUEST['id']))
		die ("Missing parameters!");
	
	$sUrl = constant('FB_GRAPH_API_URL') . $_REQUEST['id'];
	$objFBProfile = json_decode(Util :: sendHttpRequest($sUrl));
	//print_r($objFBProfile);
	if (isset ($objFBProfile -> error))
		die ($objFBProfile -> error -> type . ": " . $objFBProfile -> error -> message);
	else if (empty ($objFBProfile))
		die ("Decoding error!");
	
	
	$token = file_get_contents('https://graph.facebook.com/oauth/access_token?type=client_cred&client_id=127989843879617&client_secret=51b8ed889ba2aa0534285a4078fbe5c5');
	//die($token);
	
	$sUrl = str_replace("http", "https", $sUrl) . "/feed?" . $token;;
	$mFBFeed = json_decode(Util :: sendHttpRequest($sUrl));
	//print_r($mFBFeed);
	if (isset ($mFBFeed -> error))
		die ($mFBFeed -> error -> type . ": " . $mFBFeed -> error -> message);
	else if (empty ($objFBProfile))
		die ("Decoding error!");
	
	$arrFBVideos = array();
	//print_r($mFBFeed -> data); die();
	foreach ($mFBFeed -> data as $objFBPost) {
		//if (isset($objFBPost -> source) && strpos($objFBPost -> source, '.mp4') != FALSE)
		if (isset($objFBPost -> type) && $objFBPost -> type == 'video')
			$arrFBVideos[] = $objFBPost;
	}
	//print_r($arrFBVideos);
	
	$objXmlRss = new SimpleXmlElement('<?xml version="1.0" encoding="utf-8"?><rss version="2.0" />');
	$objXmlChannel = $objXmlRss -> addChild('channel');
	
	$objXmlChannel -> addChild('title', htmlspecialchars($objFBProfile -> name));
	if (isset($objFBProfile -> link))
		$objXmlChannel -> addChild('link', htmlspecialchars($objFBProfile -> link));
	if (isset($objFBProfile -> picture)) {
		$objXmlImage = $objXmlChannel -> addChild('image');
		$objXmlImage -> addChild('url', $objFBProfile -> picture);
		$objXmlImage -> addChild('title', $objXmlChannel -> title);
		$objXmlImage -> addChild('link', $objXmlChannel -> link);
	}
	//$arrXmlItems = array();
	foreach ($arrFBVideos as $objFBVideo) {
		$objXmlItem = $objXmlChannel -> addChild('item');
		$objXmlItem -> addChild('title', htmlspecialchars($objFBVideo -> name));
		
		if (isset ($objFBVideo -> description))
			$objXmlItem -> addChild('description', htmlspecialchars($objFBVideo -> description));
		else if (isset ($objFBVideo -> message))
			$objXmlItem -> addChild('description', htmlspecialchars($objFBVideo -> message));
		
		if (isset($objFBVideo -> source) && strpos($objFBVideo -> source, '.mp4') != FALSE) {
			$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
			$objXmlEnclosure -> addAttribute('url', $objFBVideo -> source);
			$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($objFBVideo -> source));
			$objXmlEnclosure -> addAttribute('type', 'video/mp4');
			$objXmlItem -> addChild('duration', $objFBVideo -> properties[0] -> text);
		}
		
		$objXmlItem -> addChild('pubDate', date('r', strtotime($objFBVideo -> created_time)));
		$objXmlItem -> addChild('link', htmlspecialchars($objFBVideo -> link));
		$objXmlItem -> addChild('guid', $objFBVideo -> object_id);
		
		if (isset($objFBVideo -> picture)) {
			$objXmlImage = $objXmlItem -> addChild('image');
			$objXmlImage -> addChild('url', $objFBVideo -> picture);
			$objXmlImage -> addChild('title', $objXmlItem -> title);
			$objXmlImage -> addChild('link', $objXmlItem -> link);
		}
	}
	echo $objXmlRss -> asXML();
	
