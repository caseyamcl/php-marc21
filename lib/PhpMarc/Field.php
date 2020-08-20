<?php

namespace PhpMarc;

/**
 * Field Class
 * Create a MARC Field object
 */
Class Field {
	
	/**
	 * ========== VARIABLE DECLARATIONS ==========
	 */

	/**
	 * Hexadecimal value for Subfield indicator
	 * @global hex SUBFIELD_INDICATOR
	 */
	const SUBFIELD_INDICATOR = "\x1F";

	/**
	 * Hexadecimal value for End of Field
	 * @global hex END_OF_FIELD
	 */
	const END_OF_FIELD = "\x1E";
	
	/**
	 * The tag name of the Field
	 * @var string
	 */
	var $tagno;
	/**
	 * Value of the first indicator
	 * @var string
	 */ 
	var $ind1;
	/**
	 * Value of the second indicator
	 * @var string
	 */
	var $ind2;
	/**
	 * Array of subfields
	 * @var array
	 */
	var $subfields = array();
	/**
	 * Specify if the Field is a Control field
	 * @var bool
	 */
	var $is_control;
	/**
	 * Array of warnings
	 * @var array
	 */
	var $warn;
	/**
	 * Value of field, if field is a Control field
	 * @var string
	 */
	var $data;

	/**
	 * Constant Getters
	 */

	public static function getEndOfField() {
		return self::END_OF_FIELD;
	}
	
	public static function getSubFieldIndicator() {
		return self::SUBFIELD_INDICATOR;
	}

	/**
	 * Error Functions
	 */
	
	/**
	 * Croaking function
	 *
	 * Similar to Perl's croak function, which ends parsing and raises an
	 * user error with a descriptive message.
	 * @param string The message to display
	 */
	function _croak($msg) {
		trigger_error($msg, E_USER_ERROR);
	}
	
	/**
	 * Fuction to issue warnings
	 *
	 * Warnings will not be displayed unless explicitly accessed, but all
	 * warnings issued during parse will be stored
	 * @param string Warning
	 * @return string Last added warning
	 */
	function _warn($msg) {
		$this->warn[] = $msg;
		return $msg;
	}
	
	/**
	 * Return an array of warnings
	 */
	function warnings() {
		return $this->warn;
	}

	/**
	 * Processing Functions
	 */
	
	/**
	 * Constructor
	 *
	 * Create a new Field object from passed arguments
	 * @param array Array ( tagno, ind1, ind2, subfield_data )
	 * @return string Returns warnings if any issued during parse
	 */
	function __construct() {
		$args = func_get_args();
		
		$tagno = array_shift($args);
		$this->tagno = $tagno;
		
		// Check if valid tag
		if(!preg_match("/^[0-9A-Za-z]{3}$/", $tagno)) {
			return $this->_warn("Tag \"$tagno\" is not a valid tag.");
		}
		
		// Check if field is Control field
		$this->is_control = (preg_match("/^\d+$/", $tagno) && $tagno < 10);
		if($this->is_control) {
			$this->data = array_shift($args);
		} else {
			foreach (array("ind1", "ind2") as $indcode) {
				$indicator = array_shift($args);
				if(!preg_match("/^[0-9A-Za-z ]$/", $indicator)) {
					if($indicator != "") {
						$this->_warn("Illegal indicator '$indicator' in field '$tagno' forced to blank");
					}
					$indicator = " ";
				}
				$this->$indcode = $indicator;
			}
			
			$subfields = array_shift($args);
			
			if(count($subfields) < 1) {
				return $this->_warn("Field $tagno must have at least one subfield");
			} else {
				$this->add_subfields($subfields);
			}
		}
	}
	
	/**
	 * Add subfield
	 *
	 * Appends subfields to existing fields last, not in "correct" plase
	 * @param array Subfield data
	 * @return string Returns warnings if issued during parse.
	 */
	function add_subfields() {
		// Process arguments
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		// Add subfields, is appropriate
		if ($this->is_control) {
			return $this->_warn("Subfields allowed only for tags bigger or equal to 10");
		} else {
			$this->subfields = array_merge($this->subfields, $args);
		}
		
		return count($args)/2;
	}
	
	/**
	 * Return Tag number of Field
	 */
	function tagno() {
		return $this->tagno;
	}
	
	/**
	 * Set/Get Data of Control field
	 *
	 * Sets the Data if argument given, otherwise Data returned
	 * @param string Data to be set
	 * @return string Data of Control field if argument not given
	 */
	function data($data = "") {
		if(!$this->is_control) {
			$this->_croak("data() is only allowed for tags bigger or equal to 10");
		}
		if($data) {
			$this->data = $data;
		} else {
			return $this->data;
		}
	}
	
	/**
	 * Get values of indicators
	 *
	 * @param string Indicator number
	 */
	function indicator($ind) {
		if($ind == 1) {
			return $this->ind1;
		} elseif ($ind == 2) {
			return $this->ind2;
		} else {
			$this->_warn("Invalid indicator: $ind");
		}
	}
	
	/**
	 * Check if Field is Control field
	 *
	 * @return bool True or False
	 */
	function is_control() {
		return $this->is_control;
	}
	
	/**
	 * Get the value of a subfield
	 *
	 * Return of the value of the given subfield, if exists
	 * @param string Name of subfield
	 * @return string|false Value of the subfield if exists, otherwise false
	 */
	function subfield($code) {
		if(array_key_exists($code, $this->subfields)) {
			return $this->subfields[$code];
		} else {
			return false;
		}
	}
	
	/**
	 * Return array of subfields
	 *
	 * @return array Array of subfields
	 */
	function subfields() {
		return $this->subfields;
	}
	
	/**
	 * Update Field
	 *
	 * Update Field with given array of arguments.
	 * @param array Array of key->value pairs of data
	 */
	function update() {
		// Process arguments
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		if($this->is_control) {
			$this->data = array_shift($args);
		} else {
			foreach ($args as $subfield => $value) {
				if($subfield == "ind1") {
					$this->ind1 = $value;
				} elseif ($subfield == "ind2") {
					$this->ind2 = $value;
				} else {
					$this->subfields[$subfield] = $value;
				}
			}
		}
	}
	
	/**
	 * Replace Field with given Field
	 *
	 * @param Field Field to replace with
	 */
	function replace_with($obj) {
		if(strtolower(get_class($obj)) == "PhpMarc\\Field") {
			$this->tagno = $obj->tagno;
			$this->ind1 = $obj->ind1;
			$this->ind2 = $obj->ind2;
			$this->subfields = $obj->subfields;
			$this->is_control = $obj->is_control;
			$this->warn = $obj->warn;
			$this->data = $obj->data;
		} else {
			$this->_croak(sprintf("Argument must be Field-object, but was '%s'", get_class($obj)));
		}
	}
	
	/**
	 * Clone Field
	 *
	 * @return Field Cloned Field object
	 */
	function make_clone() {
		if($this->is_control) {
			return new Field($this->tagno, $this->data);
		} else {
			return new Field($this->tagno, $this->ind1, $this->ind2, $this->subfields);
		}
	}
	
	/**
	 * ========== OUTPUT FUNCTIONS ==========
	 */
	
	/**
	 * Return Field formatted
	 *
	 * Return Field as string, formatted in a similar fashion to the
	 * MARC::Record formatted() functio in Perl
	 * @return string Formatted output of Field
	 */
	function formatted() {
		// Variables
		$lines = array();
		// Process
		if($this->is_control) {
			return sprintf("%3s     %s", $this->tagno, $this->data);
		} else {
			$pre = sprintf("%3s %1s%1s", $this->tagno, $this->ind1, $this->ind2);
		}
		// Process subfields
		foreach ($this->subfields as $subfield => $value) {
			$lines[] = sprintf("%6s _%1s%s", $pre, $subfield, $value);
			$pre = "";
		}
		
		return join("\n", $lines);
	}
	
	/**
	 * Return Field in Raw MARC
	 *
	 * Return the Field formatted in Raw MARC for saving into MARC files
	 * @return string Raw MARC
	 */
	function raw() {
		if($this->is_control) {
			return $this->data.$this->getEndOfField();
		} else {
			$subfields = array();
			foreach ($this->subfields as $subfield => $value) {
				$subfields[] = $this->getSubFieldIndicator().$subfield.$value;
			}
			return $this->ind1.$this->ind2.implode("", $subfields).$this->getEndOfField();
		}
	}
	
	/**
	 * Return Field as String
	 *
	 * Return Field formatted as String, with either all subfields or special
	 * subfields as specified.
	 * @return string Formatted as String
	 */
	function string($fields = "") {
		$matches = array();
		if($fields) {
			for($i=0; $i<strlen($fields); $i++) {
				if(array_key_exists($fields[$i], $this->subfields)) {
					$matches[] = $this->subfields[$fields[$i]];
				}
			}
		} else {
			$matches = $this->subfields;
		}
		return implode(" ", $matches);
	}
	
}
