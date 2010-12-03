<?php
	
	class BioCrawler {
		
		const BASE_URL = "http://210.60.246.140";
		const RSS_FILE = "bio.rss";
		
		/**
		 * Crawl web site for Podcast and save it
		 */
		public function crawl() {
			
			global $cfgRssDir;
			
			Util :: checkConfiguration();
			
			Util :: log("crawling BIO ....", MODE_INFO);
			
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
			
			//$objSimpleDom = file_get_html(self :: BASE_URL . '/bio/new_page_278.htm');
			$objSimpleDom = str_get_html(mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL . '/bio/new_page_278.htm'), 'UTF-8', 'big5'));
			$objXmlChannel -> addChild('title', '生物教學影片');
			$objXmlChannel -> addChild('link', self :: BASE_URL . '/bio/new_page_278.htm');
			$objXmlChannel -> addChild('generator', '9x9 Podcast Crawler (Alpha)');
			$objXmlChannel -> addChild('language', 'zh-TW');
			$objXmlChannel -> addChild('description', '生物教學影片');
			//$objXmlImage = $objXmlChannel -> addChild('image');
			//$objXmlImage -> addChild('url', 'http://media.epa.gov.tw/mm_ch/images/logo.png');
			//$objXmlImage -> addChild('title', $objXmlChannel -> title);
			//$objXmlImage -> addChild('link', $objXmlChannel -> link);
			
			//$objSimpleDom = file_get_html(mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL . '/video.php?mode=latest'), 'UTF-8', 'big5'));
			//$objSimpleDom = file_get_html(self :: BASE_URL . '/video.php?mode=latest');
			$arrDomItems = $objSimpleDom -> find('table', 1) -> find('tr > td > table > tr > td');
			//$arrMatches = array ();
			//preg_match_all("/jsOpenMedia\('mv','([^']+\.wmv)'\)\">([^<]+)<\/a><b><font color=\"#FF0000\"> New!<\/font>/", $sHtml, $arrMatches, PREG_SET_ORDER);
			
			//$arrMatches = array_reverse($arrMatches);
			$iSkipped = 0;
			$arrXmlItems = array ();
			for ($i = 0 ; $i < count($arrDomItems); $i++) {
				
				$sHref = isset($arrDomItems[$i] -> find('a', 0) -> href) ? $arrDomItems[$i] -> find('a', 0) -> href : NULL;
				$sTitle = isset($arrDomItems[$i] -> plaintext) ? $arrDomItems[$i] -> plaintext : NULL;
				if (empty($sTitle) || empty($sHref))
					continue;
				$sHref = self :: BASE_URL . '/bio/' . $sHref;
				$sTitle = preg_replace('/^【開啟】/', '', $sTitle);
				
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
				//$objSimpleDom = file_get_html($sHref);
				//$sVideoLink = $objSimpleDom -> find('div#ctl00_ContentPlaceHolder1_MovieContent1_panelbody', 0) -> find('table', 1) -> find('tr', 4) -> find('td a', 0) -> href;
				//$sPubDate = date('r', strtotime($objSimpleDom -> find('span#ctl00_ContentPlaceHolder1_MovieContent1_lblUpdate', 0) -> plaintext));
				//$sAuthor = $objSimpleDom -> find('span#ctl00_ContentPlaceHolder1_MovieContent1_lblPhotographer', 0) -> plaintext;
				//$sDescription = $objSimpleDom -> find('span#ctl00_ContentPlaceHolder1_MovieContent1_lblDescription', 0) -> plaintext;
				//$sIframeUrl = $objSimpleDom -> find('iframe#test', 0) -> src;
				//if (empty($sIframeUrl))
				//	throw new Exception('missing iframe!');
				//$objSimpleDomIframe = file_get_html(self :: BASE_URL . $sIframeUrl);
				
				$objXmlItem -> addChild('title', $sTitle);
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
				$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($sHref));
				$objXmlEnclosure -> addAttribute('type', 'video/x-ms-wmv');
				//$objXmlItem -> addChild('duration', Util :: getVideoDuration($arrMatches[1]));
				
				//$objXmlItem -> addChild('pubDate', $sPubDate);
				//$sEpisodePageLink = htmlspecialchars($sEpisodePageLink);
				//Util :: log($sEpisodePageLink);
				$objXmlItem -> addChild('link', htmlspecialchars($sHref));
				$objXmlItem -> addChild('guid', htmlspecialchars($sHref));
				//$objXmlItem -> addChild('author', $sAuthor);
				$objXmlItem -> addChild('description', $sTitle);
				
				$arrXmlItems[] = $objXmlItem;
				Util :: log("update: " . $objXmlItem -> title, MODE_INFO);
				sleep(1);
			}
			usort($arrXmlItems, "Util::compareItemGuid");
			for ($i = 0; $i < count($arrXmlItems); $i++)
				Util :: cloneRssItem($objXmlChannel -> addChild('item'), $arrXmlItems[$i]);
			
			$iTotal = count($objXmlChannel -> item);
			$iNewAdded = $iTotal - $iSkipped;
			Util :: log("total $iTotal episodes, $iNewAdded of them are new added");
			
			$objXmlRss -> asXML($sOriginalRssFile);
		}
	}
	
