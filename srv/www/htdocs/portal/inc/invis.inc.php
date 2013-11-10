<?php

/* 
 * inc/invis.inc.php v1.0
 * InvisConfig PHP class
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
 
class InvisConfig {
	
	private $dom;	
	
	public $sources;
	public $sections;
	
	private $sections_by_name;
	
	function __construct() {
		$this -> dom = new DOMDocument();
		$this -> dom -> preserveWhiteSpace = false;
	}

	function load($file) {
		if (@$this -> dom -> load($file)) {
			
			// source list (<link>, <script>, ...)
			$source_base = $this -> dom -> getElementsByTagName("sources") -> item(0);
			$this -> sources = $source_base -> getElementsByTagName("source");

			// section list
			$section_base = $this -> dom -> getElementsByTagName("sections") -> item(0);
			$this -> sections = $section_base -> getElementsByTagName("section");
			// build names list
			$this -> sections_by_name = array();
			foreach ($this -> sections as $section) {
				$this -> sections_by_name[$section -> getAttribute("linkname")] = $section;
			}
			return true;
		} else
			return false;
	}
	
	function getSection($name) {
		return $this -> sections_by_name[$name];
	}
	
	function getOnLoadScript() {
		//return "invis.init();";
		return $this -> dom -> documentElement -> getAttribute("onload");
	}
}
?>

