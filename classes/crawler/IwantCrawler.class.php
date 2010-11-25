<?php
	
	class IwantCrawler {
		
		const BASE_URL = "http://mv.iwant-in.net";
		const RSS_FILE = "iwant.rss";
		
		/**
		 * Crawl web site for Podcast and save it
		 */
		public function crawl() {
			
			global $cfgRssDir;
			
			Util :: checkConfiguration();
			
			Util :: log("crawling iWant ....", MODE_INFO);
			
			$sOriginalRssFile = $cfgRssDir . "/" . self :: RSS_FILE;
			
			$objXmlOriginalRss = NULL;
			if (file_exists($sOriginalRssFile)) {
				
				if (($objXmlOriginalRss = simplexml_load_file($sOriginalRssFile)) === FALSE)
					Util :: log("Can not parse original RSS file $sOriginalRssFile", MODE_WARNING);
				else
					Util :: log("Load RSS from $sOriginalRssFile", MODE_INFO);
			}
			else
				Util :: log("Original RSS file $sOriginalRssFile does not exists", MODE_WARNING);
			
			$objXmlRss = new SimpleXmlElement('<?xml version="1.0" encoding="utf-8"?><rss version="2.0" />');
			$objXmlChannel = $objXmlRss -> addChild('channel');
			
			//$objSimpleDom = file_get_html(mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL), 'UTF-8', 'big5'));
			$sHtml = mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL . '/mv_box1.html'), 'UTF-8', 'big5');
			$objXmlChannel -> addChild('title', '銀河網路電台');
			$objXmlChannel -> addChild('link', self :: BASE_URL . '/mv_box1.html');
			$objXmlChannel -> addChild('generator', '9x9 Podcast Crawler (Alpha)');
			$objXmlChannel -> addChild('language', 'zh-TW');
			$objXmlChannel -> addChild('copyright', 'Copyright © 1998 - 2009 iWant-in.net Inc. All Rights Reserved.');
			$objXmlChannel -> addChild('webMaster', 'iwant-service@iwant-in.net (銀河網路)');
			$objXmlImage = $objXmlChannel -> addChild('image');
			$objXmlImage -> addChild('url', 'http://2town.iwant-in.net/web/images/iwant_ad_3town_20070510_logo.gif');
			$objXmlImage -> addChild('title', $objXmlChannel -> title);
			$objXmlImage -> addChild('link', $objXmlChannel -> link);
			
			//$objSimpleDom = file_get_html(mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL . '/video.php?mode=latest'), 'UTF-8', 'big5'));
			//$objSimpleDom = file_get_html(self :: BASE_URL . '/video.php?mode=latest');
			//$arrDomItems = $objSimpleDom -> find('table.data-table > tr');
			$arrMatches = array ();
			preg_match_all("/jsOpenMedia\('mv','([^']+\.wmv)'\)\">([^<]+)<\/a><b><font color=\"#FF0000\"> New!<\/font>/", $sHtml, $arrMatches, PREG_SET_ORDER);
			
			
			$arrMatches = array_reverse($arrMatches);
			$iSkipped = 0;
			$arrXmlItems = array ();
			for ($i = 0 ; $i < count($arrMatches); $i++) {
				
				$sHref = 'http://mediaserver.iwant-in.net/player/casx.php?GApp=mv&GFiles=' . $arrMatches[$i][1];
				
				
				$objXmlItem = new SimpleXmlElement('<item/>');
				
				if ($objXmlOriginalRss) {
					
					foreach ($objXmlOriginalRss -> xpath('/rss/channel/item') as $objXmlOriginalItem) {
						
						if (((string)$objXmlOriginalItem -> guid) == $sHref) {
							
							Util :: cloneRssItem($objXmlItem, $objXmlOriginalItem);
							
							$arrXmlItems[] = $objXmlItem;
							$iSkipped++;
							sleep(1);
							continue 2;
						}
					}
				}
				//$objSimpleDom = file_get_html(mb_convert_encoding(Util :: sendHttpRequest($sEpisodePageLink), 'UTF-8', 'big5'));
				//$objSimpleDom = file_get_html($sEpisodePageLink);
				//$sIframeUrl = $objSimpleDom -> find('iframe#test', 0) -> src;
				//if (empty($sIframeUrl))
				//	throw new Exception('missing iframe!');
				//$objSimpleDomIframe = file_get_html(self :: BASE_URL . $sIframeUrl);
				
				$objXmlItem -> addChild('title', $arrMatches[$i][2]);
				//$objXmlItem -> addChild('description', trim($objSimpleDom -> find('meta[name=description]', 0) -> content));
				
				/**
				 * Enclosure Tag
				 */
				//$arrMatches = array ();
				//preg_match("/addMediaPlayerObject2\( '([^']+)',/", (string)$objSimpleDom, $arrMatches);
				//if (empty($arrMatches[1]))
				//	throw new Exception('missing media url!');
				$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
				$objXmlEnclosure -> addAttribute('url', $sHref);
				//$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($arrMatches[1]));
				$objXmlEnclosure -> addAttribute('type', 'video/x-ms-wmv');
				//$objXmlItem -> addChild('duration', Util :: getVideoDuration($arrMatches[1]));
				
				//$objXmlItem -> addChild('pubDate', Util :: $arrHttpHeader['Date']); // $arrHttpHeader is set by getRemoteFileSize()
				//$sEpisodePageLink = htmlspecialchars($sEpisodePageLink);
				//Util :: log($sEpisodePageLink);
				//$objXmlItem -> addChild('link', $sEpisodePageLink);
				$objXmlItem -> addChild('guid', htmlspecialchars($sHref));
				
				$arrXmlItems[] = $objXmlItem;
				Util :: log("update: " . $objXmlItem -> title, MODE_INFO);
				sleep(1);
			}
			usort($arrXmlItems, "Util::compareItemPubDate");
			for ($i = 0; $i < count($arrXmlItems); $i++)
				Util :: cloneRssItem($objXmlChannel -> addChild('item'), $arrXmlItems[$i]);
			
			$iTotal = count($objXmlChannel -> item);
			$iNewAdded = $iTotal - $iSkipped;
			Util :: log("total $iTotal episodes, $iNewAdded of them are new added");
			
			$objXmlRss -> asXML($sOriginalRssFile);
		}
	}
	
