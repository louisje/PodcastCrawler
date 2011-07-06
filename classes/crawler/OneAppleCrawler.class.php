<?php
	
	class OneAppleCrawler {
		
		const BASE_URL = "http://tw.nextmedia.com";
		const RSS_FILE = "oneapple.rss";
		
		/**
		 * Check configuration
		 */
		protected function checkConfiguration() {
			
			global $cfgFFProbePath;
			
			if (!is_executable($cfgFFProbePath))
				throw new Exception('missing FFProbe!');
		}
		
		/**
		 * Crawl web site for Podcast and save it
		 */
		public function crawl() {
			
			global $cfgRssDir;
			
			$this -> checkConfiguration();
			
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
			
			$objSimpleDom = file_get_html(self :: BASE_URL);
			$objXmlChannel -> addChild('title', '蘋果日報');
			$objXmlChannel -> addChild('link', self :: BASE_URL);
			$objXmlChannel -> addChild('description', $objSimpleDom -> find('meta[name=description]', 0) -> content);
			$objXmlChannel -> addChild('generator', '9x9 Podcast Crawler (Alpha)');
			$objXmlChannel -> addChild('language', 'zh-TW');
			$objXmlChannel -> addChild('copyright', '© 2008 Next Media Interactive Limited. All rights reversed.');
			$objXmlChannel -> addChild('webMaster', 'web@appledaily.com.tw (蘋果日報)');
			$objXmlImage = $objXmlChannel -> addChild('image');
			$objXmlImage -> addChild('url', 'http://profile.ak.fbcdn.net/hprofile-ak-snc4/hs341.snc4/41567_139537219397249_7117_n.jpg');
			$objXmlImage -> addChild('title', $objXmlChannel -> title);
			$objXmlImage -> addChild('link', $objXmlChannel -> link);
			
			$sUrl = self :: BASE_URL . '/animation/amnajax' /* . '/menusecid/1/ordertp/day' */ . '/video_issueid/' . date('Ymd') . '/pages/1/pagee';
			$objSimpleXml = simplexml_load_string(Util :: sendHttpRequest($sUrl));
			$iTotal = (String)$objSimpleXml -> total;
			if (empty($iTotal) || $iTotal == "0")
				throw new Exception('can not fetch total number of items');
			Util :: log("found total: " . $objSimpleXml -> total);
			$sUrl = self :: BASE_URL . '/animation/amnajax/video_issueid/' . date('Ymd') . '/pages/1/pagee/' . $iTotal;
			$objSimpleXml = simplexml_load_string(Util :: sendHttpRequest($sUrl));
			
			$arrXmlImgdata = $objSimpleXml -> xpath('/data/imgdata');
			$iSkipped = 0;
			$iNewAdded = 0;
			$arrXmlItems = array ();
			if ($objXmlOriginalRss) {
					
				foreach ($objXmlOriginalRss -> xpath('/rss/channel/item') as $objXmlOriginalItem) {
					
					if ((strtotime((string)$objXmlOriginalItem -> pubDate)) > (time() - (60 * 60 * 32))) {
						
						$objXmlItem = new SimpleXmlElement('<item/>');
						Util :: cloneRssItem($objXmlItem, $objXmlOriginalItem);
						$arrXmlItems[] = $objXmlItem;
					}
				}
			}
			for ($i = 0 ; $i < count($arrXmlImgdata); $i++) {
				
				if (empty($arrXmlImgdata[$i] -> artlink))
					throw new Exception('artlink is empty!');
				
				$sEpisodePageLink = self :: BASE_URL . $arrXmlImgdata[$i] -> artlink;
				$sTitle = (String)$arrXmlImgdata[$i] -> title;
				
				$objSimpleDom = str_get_html(Util :: sendHttpRequest($sEpisodePageLink));
				$sIframeUrl = @$objSimpleDom -> find('iframe#videoplayerframe', 0) -> src;
				if (empty($sIframeUrl))
					throw new Exception("missing iframe url! ($sTitle)");
				$sIframeUrl = self :: BASE_URL . $sIframeUrl;
				
				$objSimpleDomIframe = str_get_html(Util :: sendHttpRequest($sIframeUrl));
				$sEpisodePageLink = @$objSimpleDomIframe -> find('h1 > a', 0) -> href;
				if (empty($sEpisodePageLink)) {
					Util :: log("missing sEpisodePageLink! ($sTitle)", MODE_WARNING);
					continue;
				}
				$sEpisodePageLink = self :: BASE_URL . $sEpisodePageLink;
				
				$objXmlItem = new SimpleXmlElement('<item/>');
				
				foreach ($arrXmlItems as $objXmlOriginalItem) {
					
					if (((string)$objXmlOriginalItem -> guid) == $sEpisodePageLink) {
						
						$iSkipped++;
						sleep(1);
						continue 2;
					}
				}
				
				$objXmlItem -> addChild('title', $sTitle);
				$objSimpleDom = str_get_html(Util :: sendHttpRequest($sEpisodePageLink));
				$sDescription = @trim($objSimpleDom -> find('meta[name=description]', 0) -> content);
				if (empty($sDescription)) {
					//Util :: log("missing description! ($sTitle)", MODE_WARNING);
					//continue;
					$objXmlItem -> addChild('description', $sTitle);
				}
				else
					$objXmlItem -> addChild('description', $sDescription);
				
				/**
				 * Enclosure Tag
				 */
				$arrMatches = array ();
				preg_match("/so.addVariable\('file','([^']+)'\);/", (string)$objSimpleDom, $arrMatches);
				if (empty($arrMatches[1])) {
					Util :: log('missing media url!', MODE_WARNING);
					continue;
				}
				$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
				$objXmlEnclosure -> addAttribute('url', $arrMatches[1]);
				$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($arrMatches[1]));
				$objXmlEnclosure -> addAttribute('type', 'video/x-flv');
				//$objXmlItem -> addChild('duration', Util :: getVideoDuration($arrMatches[1]));
				
				$objXmlItem -> addChild('pubDate', date('r', strtotime($objSimpleDom -> find('time.time_stamp', 0) -> plaintext)));
				$objXmlItem -> addChild('link', $sEpisodePageLink);
				$objXmlItem -> addChild('guid', $sEpisodePageLink);
				
				$arrXmlItems[] = $objXmlItem;
				Util :: log("update: " . $objXmlItem -> title, MODE_INFO);
				$iNewAdded ++;
				sleep(1);
			}
			for ($i = 0; $i < count($arrXmlItems); $i++)
				Util :: cloneRssItem($objXmlChannel -> addChild('item'), $arrXmlItems[$i]);
			
			$iTotal = count($objXmlChannel -> item);
			Util :: log("total $iTotal episodes, $iNewAdded of them are new added");
			
			$objXmlRss -> asXML($sOriginalRssFile);
		}
	}
	
