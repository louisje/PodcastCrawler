<?php
	
	class MetacafeCrawler {
		
		const BASE_URL = "http://www.metacafe.com";
		const RSS_FILE = "metacafe.rss";
		
		/**
		 * Crawl web site for Podcast and save it
		 */
		public function crawl() {
			
			global $cfgRssDir;
			
			Util :: checkConfiguration();
			
			Util :: log("crawling MetaCafe ....", MODE_INFO);
			
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
			$objSimpleDom = file_get_html(self :: BASE_URL);
			$objXmlChannel -> addChild('title', $objSimpleDom -> find('title', 0) -> plaintext);
			$objXmlChannel -> addChild('link', self :: BASE_URL);
			$objXmlChannel -> addChild('generator', '9x9 Podcast Crawler (Alpha)');
			$objXmlChannel -> addChild('language', 'en');
			$objXmlChannel -> addChild('description', 'Metacafe is one of the world\'s largest video sites, attracting more than 40 million unique viewers each month (comScore Media Metrix). We specialize in short-form original content - from new, emerging talents and established Hollywood heavyweights alike. We\'re committed to delivering an exceptional entertainment experience, and we do so by engaging and empowering our audience every step of the way.');
			$objXmlImage = $objXmlChannel -> addChild('image');
			$objXmlImage -> addChild('url', 'http://s.mcstatic.com/Images/Global/HeaderMatrix-10.png');
			$objXmlImage -> addChild('title', htmlspecialchars((string)$objXmlChannel -> title));
			$objXmlImage -> addChild('link', htmlspecialchars((string)$objXmlChannel -> link));
			
			//$objSimpleDom = file_get_html(mb_convert_encoding(Util :: sendHttpRequest(self :: BASE_URL . '/video.php?mode=latest'), 'UTF-8', 'big5'));
			//$objSimpleDom = file_get_html(self :: BASE_URL . '/video.php?mode=latest');
			$arrDomItems = $objSimpleDom -> find('ul#Catalog1 > li');
			//$arrMatches = array ();
			//preg_match_all("/jsOpenMedia\('mv','([^']+\.wmv)'\)\">([^<]+)<\/a><b><font color=\"#FF0000\"> New!<\/font>/", $sHtml, $arrMatches, PREG_SET_ORDER);
			
			//$arrMatches = array_reverse($arrMatches);
			$iSkipped = 0;
			$arrXmlItems = array ();
			for ($i = 0 ; $i < count($arrDomItems); $i++) {
				
				$sHref = isset($arrDomItems[$i] -> find('a', 0) -> href) ? $arrDomItems[$i] -> find('a', 0) -> href : NULL;
				$sTitle = isset($arrDomItems[$i] -> find('a', 0) -> title) ? $arrDomItems[$i] -> find('a', 0) -> title : NULL;
				if (empty($sTitle) || empty($sHref))
					continue;
				//$sHref = mb_convert_encoding($sHref, 'big5', 'UTF-8');
				//$sHref = urlencode($sHref);
				//$sHref = str_replace('%2F', '/', $sHref);
				$sHref = self :: BASE_URL . $sHref;
				
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
				$sHtml = Util :: sendHttpRequest($sHref);
				$objSimpleDom = str_get_html($sHtml);
				$sDescription = $objSimpleDom -> find('div#Desc > p > p', 0) -> plaintext;
				$sAuthor = $objSimpleDom -> find('a.SubmitterClick', 0) -> title;
				
				$arrMatches = array ();
				if (preg_match('/Updated: ([-a-zA-Z0-9 ]+)/', $sHtml, $arrMatches)) {
					$sPubDate = date('r', strtotime($arrMatches[1]));
				}
				
				$arrMatches = array ();
				preg_match('/name="flashvars" value="([^"]+)"/', $sHtml, $arrMatches);
				$arrFlashVars = array ();
				parse_str($arrMatches[1], $arrFlashVars);
				if (!isset($arrFlashVars['mediaData']) || empty($arrFlashVars['mediaData'])) // This episode may be a adult video
					continue;
				$arrMediaData = json_decode($arrFlashVars['mediaData'], TRUE);
				if (empty($arrMediaData)) {
					Util :: log ('mediaData does not exist', MODE_WARNING);
					continue;
				}
				$sVideoLink = $arrMediaData['MP4']['mediaURL'] . "?__gda__=" . $arrMediaData['MP4']['key'];
				if (empty($sVideoLink)) {
					Util :: log ('Can not get $sVideoLink', MODE_WARNING);
					return;
				}
				
				/**
				 * Enclosure Tag
				 */
				//$arrMatches = array ();
				//preg_match("/addMediaPlayerObject2\( '([^']+)',/", (string)$objSimpleDom, $arrMatches);
				//if (empty($arrMatches[1]))
				//	throw new Exception('missing media url!');
				$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
				$objXmlEnclosure -> addAttribute('url', $sVideoLink);
				$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($sVideoLink));
				$objXmlEnclosure -> addAttribute('type', 'video/mp4');
				//$objXmlItem -> addChild('duration', Util :: getVideoDuration($arrMatches[1]));
				
				if (isset($sPubDate))
					$objXmlItem -> addChild('pubDate', $sPubDate);
				//$sEpisodePageLink = htmlspecialchars($sEpisodePageLink);
				//Util :: log($sEpisodePageLink);
				$objXmlItem -> addChild('link', htmlspecialchars($sHref));
				$objXmlItem -> addChild('guid', htmlspecialchars($sHref));
				$objXmlItem -> addChild('author', htmlspecialchars($sAuthor));
				$objXmlItem -> addChild('description', htmlspecialchars($sDescription));
				
				$arrXmlItems[] = $objXmlItem;
				Util :: log("update: " . $objXmlItem -> title, MODE_INFO);
				sleep(1);
			}
			//usort($arrXmlItems, "Util::compareItemGuid");
			for ($i = 0; $i < count($arrXmlItems); $i++)
				Util :: cloneRssItem($objXmlChannel -> addChild('item'), $arrXmlItems[$i]);
			
			$iTotal = count($objXmlChannel -> item);
			$iNewAdded = $iTotal - $iSkipped;
			Util :: log("total $iTotal episodes, $iNewAdded of them are new added");
			
			$objXmlRss -> asXML($sOriginalRssFile);
		}
	}
	
