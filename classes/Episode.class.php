<?php
	
	class Episode {
		
		public $_ = array (
			'title' => '',
			'author' => '',
			'subtitle' => '',
			'summary' => '',
			'media' => array (
				'url' => '',
				'length' => '',
				'type' => '',
			),
			'guid' => '',
			'pubDate' => '',
			'duration' => '',
			'keywords' => '',
		);
		
		public function __get($sName) {
			if (isset($this -> _[$sName]))
				return $this -> _[$sName];
			else if (isset($this -> _['media'][$sName]))
				return $this -> _['media'][$sName];
			else
				return NULL;
		}
		
		public function __set($sName, $sValue) {
			if (isset($this -> _['media'][$sName]))
				$this -> _['media'][$sName] = $sValue;
			else if (isset($this -> _[$sName]))
				$this -> _[$sName] = $sValue;
			else
				throw new Exception("property undefined!");
		}
	}
	
