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
			
			$this -> checkConfiguration();
			
			$arrEpisodes = array ();
			$sHtml = Util :: sendHttpRequest(self :: BASE_URL . '/create/video/content/1');
			$objSimpleDom = new simple_html_dom();
			$objSimpleDom -> load($sHtml);
			$arrDomItems = $objSimpleDom -> find('ul > li > a');
			foreach ($arrDomItems as $objDomItem) {
				
				$objEpisode = new Episode();
				
				$sHtml = Util :: sendHttpRequest(self :: BASE_URL . $objDomItem -> href);
				$objSimpleDom = new simple_html_dom();
				$objSimpleDom -> load($sHtml);
				$objEpisode -> description = trim($objSimpleDom -> find('meta[name=description]', 0) -> content);
				$sIframeUrl = $objSimpleDom -> find('iframe#test', 0) -> src;
				if (empty($sIframeUrl))
					throw new Exception('missing iframe!');
				$sHtml = Util :: sendHttpRequest(self :: BASE_URL . $sIframeUrl);
				$objSimpleDomIframe = new simple_html_dom();
				$objSimpleDomIframe -> load($sHtml);
				
				$objEpisode -> pubDate = Util :: $arrHttpHeader['Date'];
				
				$arrMatches = array ();
				preg_match("/so.addVariable\('file','([^']+)'\);/", $sHtml, $arrMatches);
				if (empty($arrMatches[1]))
					throw new Exception('missing media url!');
				$objEpisode -> url = $arrMatches[1];
				$objEpisode -> guid = $arrMatches[1];
				$objEpisode -> type = 'video/x-flv';
				$objEpisode -> length = Util :: getRemoteFileSize($objEpisode -> url);
				$objEpisode -> duration = Util :: getVideoDuration($objEpisode -> url);
				$objEpisode -> duration = preg_replace('/^(00:)+/', '', $objEpisode -> duration);
				$objEpisode -> duration = preg_replace('/\.[0-9][0-9]$/', '', $objEpisode -> duration);
				
				preg_match("/so.addVariable\('image','([^']+)'\);/", $sHtml, $arrMatches);
				if (empty($arrMatches[1]))
					throw new Exception('missing image!');
				$objEpisode -> image = $arrMatches[1];
				
				$objEpisode -> title = $objSimpleDom -> find('title', 0) -> plaintext;
				$objEpisode -> author = $objSimpleDom -> find('meta[name=author]', 0) -> content;
				$objEpisode -> keywords = $objSimpleDom -> find('meta[name=keywords]', 0) -> content;
				$objEpisode -> summary = trim($objSimpleDomIframe -> find('div.dv_playlist_art > span', 0) -> plaintext);
				
				$arrEpisodes[] = $objEpisode -> _;
				usleep(100000);
			}
			$objChannel = new Channel();
			$sHtml = Util :: sendHttpRequest(self :: BASE_URL);
			$objSimpleDom = new simple_html_dom();
			$objSimpleDom -> load($sHtml);
			$objChannel -> title = '蘋果日報';
			$objChannel -> link = self :: BASE_URL;
			$objChannel -> language = 'zh';
			$objChannel -> copyright = '© 2008 Next Media Interactive Limited. All rights reversed.';
			$objChannel -> image = 'http://profile.ak.fbcdn.net/hprofile-ak-snc4/hs341.snc4/41567_139537219397249_7117_n.jpg';
			$objChannel -> author = $objSimpleDom -> find('meta[name=author]', 0) -> content;
			$objChannel -> description = $objSimpleDom -> find('meta[name=description]', 0) -> content;
			$objChannel -> name = '蘋果日報';
			$objChannel -> email = 'web@appledaily.com.tw';
			
			/**
			 * Generate RSS file
			 */
			global $cfgRssDir;
			$objH2o = new H2o('podcast.tpl', array (
				'cache' => false,
				'searchpath' => './templates/')
			);
			$sFeed = $objH2o -> render(array (
				"channel" => $objChannel -> _,
				"episodes" => $arrEpisodes)
			);
			file_put_contents($cfgRssDir . '/' . self :: RSS_FILE, $sFeed);
			
		}
	}
	
