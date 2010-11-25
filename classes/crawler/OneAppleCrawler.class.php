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
			
			$objSimpleDom = file_get_html(self :: BASE_URL . '/create/video/content/1');
			$arrDomItems = $objSimpleDom -> find('ul > li > a');
			$iSkipped = 0;
			foreach ($arrDomItems as $objDomItem) {
				
				$sEpisodePageLink = self :: BASE_URL . $objDomItem -> href;
				
				$objXmlItem = $objXmlChannel -> addChild('item');
				
				if ($objXmlOriginalRss) {
					
					foreach ($objXmlOriginalRss -> xpath('/rss/channel/item') as $objXmlOriginalItem) {
						
						if (((string)$objXmlOriginalItem -> guid) == $sEpisodePageLink) {
							
							$objXmlItem -> addChild('title', (string)$objXmlOriginalItem -> title);
							$objXmlItem -> addChild('description', (string)$objXmlOriginalItem -> description);
							$arrEnclosureAttributes = $objXmlOriginalItem -> enclosure -> attributes();
							$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
							$objXmlEnclosure -> addAttribute('url', $arrEnclosureAttributes['url']);
							$objXmlEnclosure -> addAttribute('length', $arrEnclosureAttributes['length']);
							$objXmlEnclosure -> addAttribute('type', $arrEnclosureAttributes['type']);
							$objXmlItem -> addChild('pubDate', (string)$objXmlOriginalItem -> pubDate);
							$objXmlItem -> addChild('link', (string)$objXmlOriginalItem -> link);
							$objXmlItem -> addChild('guid', (string)$objXmlOriginalItem -> guid);
							
							$iSkipped++;
							usleep(100000);
							continue 2;
						}
					}
				}
				$objSimpleDom = file_get_html($sEpisodePageLink);
				$sIframeUrl = $objSimpleDom -> find('iframe#test', 0) -> src;
				if (empty($sIframeUrl))
					throw new Exception('missing iframe!');
				$objSimpleDomIframe = file_get_html(self :: BASE_URL . $sIframeUrl);
				
				$objXmlItem -> addChild('title', $objSimpleDom -> find('title', 0) -> plaintext);
				$objXmlItem -> addChild('description', trim($objSimpleDomIframe -> find('div.dv_playlist_art > span', 0) -> plaintext));
				
				/**
				 * Enclosure Tag
				 */
				$arrMatches = array ();
				preg_match("/so.addVariable\('file','([^']+)'\);/", (string)$objSimpleDomIframe, $arrMatches);
				if (empty($arrMatches[1]))
					throw new Exception('missing media url!');
				$objXmlEnclosure = $objXmlItem -> addChild('enclosure');
				$objXmlEnclosure -> addAttribute('url', $arrMatches[1]);
				$objXmlEnclosure -> addAttribute('length', Util :: getRemoteFileSize($arrMatches[1]));
				$objXmlEnclosure -> addAttribute('type', 'video/x-flv');
				//$objXmlItem -> addChild('duration', Util :: getVideoDuration($arrMatches[1]));
				
				$objXmlItem -> addChild('pubDate', Util :: $arrHttpHeader['Date']); // $arrHttpHeader is set by getRemoteFileSize()
				$objXmlItem -> addChild('link', $sEpisodePageLink);
				$objXmlItem -> addChild('guid', $sEpisodePageLink);
				
				Util :: log("update: " . $objXmlItem -> title, MODE_INFO);
				usleep(100000);
			}
			$iTotal = count($objXmlChannel -> item);
			$iNewAdded = $iTotal - $iSkipped;
			Util :: log("total $iTotal episodes, $iNewAdded of them are new added");
			
			$objXmlRss -> asXML($sOriginalRssFile);
		}
	}
	
