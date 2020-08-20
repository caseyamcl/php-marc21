<?php

namespace PhpMarc;

/**
 * USMARC Class
 * Extension class to File class, which allows passing of raw MARC string
 * instead of filename
 */
Class USMARC Extends File {
	/**
	 * Read raw MARC string for decoding
	 * @param string Raw MARC
	*/
	function usmarc($string) {
		$this->raw[] = $string;
		$this->pointer = 0;
	}
}
