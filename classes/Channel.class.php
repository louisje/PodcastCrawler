<?php
	
	class Channel {
		
		public $_ = array (
			'title' => '',
			'link' => '',
			'language' => '',
			'copyright' => '',
			'subtitle' => '',
			'author' => '',
			'summary' => '',
			'description' => '',
			'owner' => array (
				'name' => '',
				'email' => '',
			),
			'image' => '',
		);
		
		public function __get($sName) {
			if (isset($this -> _[$sName]))
				return $this -> _[$sName];
			else if (isset($this -> _['owner'][$sName]))
				return $this -> _['owner'][$sName];
			else
				return NULL;
		}
		
		public function __set($sName, $sValue) {
			if (isset($this -> _['owner'][$sName]))
				$this -> _['owner'][$sName] = $sValue;
			else if (isset($this -> _[$sName]))
				$this -> _[$sName] = $sValue;
			else
				throw new Exception("property undefined!");
		}
	}
	
