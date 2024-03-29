<?php
	
	class Util {
		
		static public $arrHttpHeader = array ();
		
		/**
		 * Check System Configuration
		 */
		static public function checkConfiguration() {
			
			global $cfgFFProbePath;
			
			if (!is_executable($cfgFFProbePath))
				throw new Exception('missing FFProbe!');
		}
		
		/**
		 * Clone RSS <item>
		 *
		 * @param  $objXmlTo   item clone to
		 * @param  $objXmlFrom item clone from
		 */
		static public function cloneRSSItem(SimpleXmlElement $objXmlTo, SimpleXmlElement $objXmlFrom) {
			
			$objXmlTo -> addChild('title', htmlspecialchars((string)$objXmlFrom -> title));
			if (count($objXmlFrom -> description))
				$objXmlTo -> addChild('description', htmlspecialchars((string)$objXmlFrom -> description));
			if (count($objXmlFrom -> enclosure)) {
				$arrEnclosureAttributes = $objXmlFrom -> enclosure -> attributes();
				$objXmlEnclosure = $objXmlTo -> addChild('enclosure');
				$objXmlEnclosure -> addAttribute('url', $arrEnclosureAttributes['url']);
				$objXmlEnclosure -> addAttribute('length', $arrEnclosureAttributes['length']);
				$objXmlEnclosure -> addAttribute('type', $arrEnclosureAttributes['type']);
			}
			if (count($objXmlFrom -> pubDate))
				$objXmlTo -> addChild('pubDate', (string)$objXmlFrom -> pubDate);
			if (count($objXmlFrom -> link))
				$objXmlTo -> addChild('link', htmlspecialchars((string)$objXmlFrom -> link));
			if (count($objXmlFrom -> guid))
				$objXmlTo -> addChild('guid', htmlspecialchars((string)$objXmlFrom -> guid));
		}
		
		/**
		 * Compare Two Item's Guid
		 *
		 * @param  $objXmlItemA first item
		 * @param  $objXmlItemB second item
		 * @return negtive, zero, positive if the second item is less, equal, greater than the first
		 */
		static public function compareItemGuid(SimpleXmlElement $objXmlItemA, SimpleXmlElement $objXmlItemB) {
			
			return strcmp($objXmlItemA -> guid, $objXmlItemB -> guid);
		}
		
		/**
		 * Compare Two Item's pubDate
		 *
		 * @param  $objXmlItemA first item
		 * @param  $objXmlItemB second item
		 * @return negtive, zero, positive if the second item is less, equal, greater than the first
		 */
		static public function compareItemPubDate(SimpleXmlElement $objXmlItemA, SimpleXmlElement $objXmlItemB) {
			
			$iTimeA = strtotime($objXmlItemA -> pubDate);
			$iTimeB = strtotime($objXmlItemB -> pubDate);
			
			return $iTimeB - $iTimeA;
		}
		
		/**
		 * Get Remote File Size
		 *
		 * @param  $sURl remote file url
		 * @return       file size or 0
		 */
		static function getRemoteFileSize($sUrl) {
			
			static $iRedirections = 0;
			$iMaxRedirection = 5;
			
			$rCurl = curl_init($sUrl);
			if (empty($rCurl))
				return NULL;
			curl_setopt($rCurl, constant('CURLOPT_RETURNTRANSFER'), true);
			curl_setopt($rCurl, constant('CURLOPT_FOLLOWLOCATION'), true);
			curl_setopt($rCurl, constant('CURLOPT_SSL_VERIFYPEER'), false);
			curl_setopt($rCurl, constant('CURLOPT_SSL_VERIFYHOST'), false);
			curl_setopt($rCurl, constant('CURLOPT_NOBODY'), true);
			curl_setopt($rCurl, constant('CURLOPT_HEADER'), true);
			
			$sHeader = curl_exec($rCurl);
			$arrHeaderLines = preg_split("/\r?\n/", trim($sHeader));
			array_shift($arrHeaderLines);
			$arrHeader = array ();
			foreach ($arrHeaderLines as $sLine) {
				@list ($sName, $sValue) = preg_split("/: /", $sLine, 2);
				$arrHeader[$sName] = $sValue;
			}
			self :: $arrHttpHeader = $arrHeader;
			if (isset($arrHeader['Content-Length'])) {
				$iRedirections = 0;
				$iContentLength = $arrHeader['Content-Length'];
				self :: log("Content-Length: $iContentLength", MDOE_DEBUG);
				return $iContentLength;
			}
			else if (isset($arrHeader['Location'])) {
				$iRedirections += 1;
				if ($iRedirections <= $iMaxRedirection) {
					self :: log("Follow: $iRedirections", MDOE_DEBUG);
					return self :: getRemoteFileSize($arrHeader['Location']);
				}
				else {
					self :: log("Exceed redirection limit!", MODE_WARNING);
					$iRedirections = 0;
					return 0;
				}
			}
			else {
				self :: log("No content-length. ($sUrl)", MODE_WARNING);
				$iRedirections = 0;
				return 0;
			}
		}
		
		/**
		 * Get Video Duration
		 *
		 * @param  $sUrl  video url
		 * @return        video duration in '00:01:21.10' format or null
		 */
		static public function getVideoDuration($sUrl) {
			
			global $cfgFFProbePath;
			
			$sCommand = "$cfgFFProbePath " . escapeshellarg($sUrl) . " 2>&1";
			//Util :: log($sCommand, MODE_INFO);
			unset($arrOutputs);
			exec($sCommand, $arrOutputs);
			foreach ($arrOutputs as $sOutput) {
				//Util :: log(" + $sOutput", MODE_DEBUG);
				if (preg_match('/^  Duration: ([0-9][0-9]:[0-9][0-9]:[0-9][0-9])\.[0-9][0-9],/', $sOutput, $arrMatches))
					$sDuration = $arrMatches[1];
			}
			if (isset($sDuration)) {
				$sDuration = preg_replace('/^(00:)+/', '', $sDuration);
				return $sDuration;
			}
			else
				return NULL;
		}
		
		/**
		 * Like basename(), but support UTF-8
		 *
		 * @param  string $sFullFileName
		 * @return string $sFileBaseName or NULL
		 */
		static public function getFileBaseName($sFullFileName = NULL) {
			
			if ($sFullFileName === NULL || strlen($sFullFileName) <= 0)
			return NULL;
			$arrExploded = explode(constant('DIRECTORY_SEPARATOR'), $sFullFileName);
			$sFileBaseName = $arrExploded[count($arrExploded) - 1];
			return $sFileBaseName;
		}
		
		/**
		 * Write log
		 *
		 * @param string $sMessage Message to log
		 * @param int    $iMode    Log mode
		 */
		static public function log($sMessage, $iMode = MODE_INFO) {
			
			global $cfgDebugMode;        /* configuration */
			global $cfgLogDir;           /* configuration */
			global $cfgLogType;          /* configuration */
			
			/* Logger will log nothing if debug mode is nor match log mode */
			if (!$cfgDebugMode)
				return;
			else if ($iMode == MODE_NONE)
				$sMode = 'NONE';
			else if (($iMode & MODE_ERROR) == MODE_ERROR)
				$sMode = 'ERROR';
			else if (($iMode & MODE_WARNING) == MODE_WARNING)
				$sMode = 'WARNING';
			else if (($iMode & MODE_INFO) == MODE_INFO)
				$sMode = 'INFO';
			else if (($iMode & MODE_DEBUG) == MODE_DEBUG)
				$sMode = 'DEBUG';
			else
				$sMode = 'UNKNOWN';
			
			$sMyPid = getmypid();
			$sLogFile = $cfgLogDir . "/" . date("Y-m-d") . ".log";
			$sLogInfo = date("[Y-m-d H:i:s],");
			
			$arrDebugBT = debug_backtrace();
			if (isset($arrDebugBT[1]['function']))
				$sFunction = $arrDebugBT[1]['function'];
			else
				$sFunction = basename($arrDebugBT[0]['file']) . ":" . $arrDebugBT[0]['line'];
			if (strlen($sFunction) > 15) {
				$sFunction = substr($sFunction, 0, 12);
				$sFunction = $sFunction . "...";
			}
			$sLogInfo .= sprintf("%16s,%8s, [%5s] %s\n", $sFunction, $sMode, $sMyPid, $sMessage);
			
			if (!file_exists($sLogFile)) {
				if (touch($sLogFile) === FALSE || chmod($sLogFile, 0666) === FALSE)
					throw new Exception("Can not create log file!");
			} else if (!is_writable($sLogFile)) {
				throw new Exception("Can not write log file!");
			}
			if ($cfgLogType == 2)
				fprintf(STDERR, $sLogInfo);
			else {
				if (error_log($sLogInfo, $cfgLogType, $sLogFile) === FALSE)
					throw new Exception("Can not write log file!");
			}
		}
		
		/**
		 * Send HTTP request to target URL use POST method and get response
		 *
		 * @param   string  $sRequestUrl  Target URL
		 * @param   mixed   $mRequest     Request to be sent
		 * @return  string  $sResult      Response
		 */
		static public function sendHttpRequest($sRequestUrl, $mRequest = NULL) {
			
			if (is_array($mRequest)) {
				
				$sPostData = "";
				foreach ($mRequest as $sName => $sValue) {
					$sPostData .= urlencode($sName) . "=" . urlencode($sValue) . "&";
				}
				$sPostData = trim($sPostData, '&');
			}
			else
				$sPostData = $mRequest;
			
			if (($rCurl = curl_init()) === FALSE)
				throw new Exception("Can not initialize cURL!");
			
			//self :: log("postUrl: $sRequestUrl", MODE_INFO);
			
			curl_setopt($rCurl, constant('CURLOPT_URL'), $sRequestUrl);
			curl_setopt($rCurl, constant('CURLOPT_RETURNTRANSFER'), TRUE);
			curl_setopt($rCurl, constant('CURLOPT_SSL_VERIFYPEER'), FALSE);
			curl_setopt($rCurl, constant('CURLOPT_SSL_VERIFYHOST'), FALSE);
			curl_setopt($rCurl, constant('CURLOPT_HEADER'), TRUE);
			curl_setopt($rCurl, constant('CURLOPT_ENCODING'), "gzip");
			curl_setopt($rCurl, constant('CURLOPT_TIMEOUT'), 30);
			curl_setopt($rCurl, constant('CURLOPT_HTTPHEADER'), array("Cache-Control: no-cache", "Pragma: no-cache"));
			if ($sPostData) {
				curl_setopt($rCurl, constant('CURLOPT_POST'), true);
				curl_setopt($rCurl, constant('CURLOPT_POSTFIELDS'), $sPostData);
				self :: log(" + postData: $sPostData", MODE_DEBUG);
			}
			
			if (($mResult = curl_exec($rCurl)) === FALSE)
				throw new Exception("curl_exec() return FALSE: " . curl_error($rCurl));
			
			$mResult = trim($mResult);
			list ($sHeader, $sBody) = preg_split("/(\r?\n){2}/", $mResult, 2);
			//self :: log("header:\n$sHeader");
			//self :: log("body:\n$sBody");
			$arrHeaderLines = preg_split("/\r?\n/", $sHeader);
			array_shift($arrHeaderLines);
			$arrHeader = array ();
			foreach ($arrHeaderLines as $sLine) {
				list ($sName, $sValue) = preg_split("/: /", $sLine, 2);
				$arrHeader[$sName] = $sValue;
			}
			self :: $arrHttpHeader = $arrHeader;
			//self :: log(print_r($arrHeader, TRUE));
			
			$arrInfo = curl_getinfo($rCurl);
			//self :: log(" + http_code: {$arrInfo['http_code']}, total_time: {$arrInfo['total_time']}, speed_download: {$arrInfo['speed_download']}", MODE_DEBUG);
			
			return $sBody;
		}
	}
	
