<?php 

namespace PhpMarc;

/**
 * Record Class
 * Create a MARC Record class
 */
Class Record {
	
	/**
	 * ========== VARIABLE DECLARATIONS ==========
	 */
	
	/**
	 * Hexadecimal value for End of Field
	 * @global hex END_OF_FIELD
	 */
	const END_OF_FIELD = "\x1E";

	/**
	 * Hexadecimal value for End of Record
	 * @global hex END_OF_RECORD
	 */
	const END_OF_RECORD = "\x1D";

	/**
	 * Length of the Directory
	 * @global integer DIRECTORY_ENTRY_LEN
	 */
	const DIRECTORY_ENTRY_LEN = 12;

	/**
	 * Length of the Leader
	 * @global integer LEADER_LEN
	 */
	const LEADER_LEN = 24;

	/**
	 * Contain all @link Field objects of the Record
	 * @var array
	 */
	var $fields;
	/**
	 * Leader of the Record
	 * @var string
	 */
	var $ldr;
	/**
	 * Array of warnings
	 * @var array
	 */
	var $warn;
	
	/**
	 * Constant Getters
	 */

	public static function getEndOfField() {
		return self::END_OF_FIELD;
	}
	
	public static function getEndOfRecord() {
		return self::END_OF_RECORD;
	}

	public static function getDirectoryEntryLen() {
		return self::DIRECTORY_ENTRY_LEN;
	}

	public static function getLeaderLen() {
		return self::LEADER_LEN;
	}

	/**
	 * ========== ERROR FUNCTIONS ==========
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
	 * ========== PROCESSING FUNCTIONS ==========
	 */
	
	/**
	 * Constructor
	 *
	 * Set all variables to defaults to create new Record object
	 */
	function __construct() {
		$this->fields = array();
		$this->ldr = str_repeat(' ', 24);
	}
	
	/**
	 * Get/Set Leader
	 *
	 * If argument specified, sets leader, otherwise gets leader. No validation
	 * on the specified leader is performed
	 * @param string Leader
	 * @return string|null Return leader in case requested.
	 */
	function leader($ldr = "") {
		if($ldr) {
			$this->ldr = $ldr;
		} else {
			return $this->ldr;
		}
	}
	
	/**
	 * Append field to existing
	 *
	 * Given Field object will be appended to the existing list of fields. Field will be
	 * appended last and not in its "correct" location.
	 * @param Field The field to append
	 */
	function append_fields($field) {
		if(strtolower(get_class($field)) == "phpmarc\\field") {
			$this->fields[$field->tagno][] = $field;
		} else {
			$this->_croak(sprintf("Given argument must be Field object, but was '%s'", get_class($field)));
		}
	}
	
	/**
	 * Build Record Directory
	 *
	 * Generate the directory of the Record according to existing data.
	 * @return array Array ( $fields, $directory, $total, $baseaddress )
	 */
	function _build_dir() {
        // Vars
		$fields = array();
        $directory = array();

        $dataend = 0;
        foreach ($this->fields as $field_group ) {
			foreach ($field_group as $field) {
				// Get data in raw format
				$str = $field->raw();
				$fields[] = $str;

				// Create directory entry
				$len = strlen($str);
				$direntry = sprintf( "%03s%04d%05d", $field->tagno(), $len, $dataend );
				$directory[] = $direntry;
				$dataend += $len;
			}
        }

		/**
		 * Rules from MARC::Record::USMARC
		 */
        $baseaddress =
                $this->getLeaderLen() +    // better be 24
                ( count($directory) * $this->getDirectoryEntryLen() ) +
                                // all the directory entries
                1;              // end-of-field marker


        $total =
                $baseaddress +  // stuff before first field
                $dataend +      // Length of the fields
                1;              // End-of-record marker



        return array($fields, $directory, $total, $baseaddress);
	}
	
	/**
	 * Set Leader lengths
	 *
	 * Set the Leader lengths of the record according to defaults specified in
	 * http://www.loc.gov/marc/bibliographic/ecbdldrd.html
	 */
	function leader_lengths($reclen, $baseaddr) {
		$this->ldr = substr_replace($this->ldr, sprintf("%05d", $reclen), 0, 5);
		$this->ldr = substr_replace($this->ldr, sprintf("%05d", $baseaddr), 12, 5);
		$this->ldr = substr_replace($this->ldr, '22', 10, 2);
		$this->ldr = substr_replace($this->ldr, '4500', 20, 4);
	}
	
	/**
	 * Return all Field objects
	 * @return array Array of Field objects
	 */
	function fields() {
		return $this->fields;
	}
	
	/**
	 * Get specific field
	 *
	 * Search for field in Record fields based on field name, e.g. 020
	 * @param string Field name
	 * @return Field|false Return Field if found, otherwise false
	 */
	function field($spec) {
		if(array_key_exists($spec, $this->fields)) {
			return $this->fields[$spec][0];
		} else {
			return false;
		}
	}
	
	/**
	 * Get subfield of Field object
	 *
	 * Returns the value of a specific subfield of a given Field object
	 * @param string Name of field
	 * @param string Name of subfield
	 * @return string|false Return value of subfield if Field exists, otherwise false
	 */
	function subfield($field, $subfield) {
		if(!$field = $this->field($field)) {
			return false;
		} else {
			return $field->subfield($subfield);
		}
	}
	
	/**
	 * Delete Field
	 *
	 * Delete a given field from within a Record
	 * @param Field The field to be deleted
	 */
	function delete_field($obj) {
		unset($this->fields[$obj->field]);
	}
	
	/**
	 * Clone record
	 *
	 * Clone a record with all its Fields and subfields
	 * @return Record Clone record
	 */
	function make_clone() {
		$clone = new Record;
		$clone->leader($this->ldr);

		foreach ($this->fields() as $data) {
			foreach ($data as $field) {
				$clone->append_fields($field);
			}
		}

		return $clone;
	}
	
	/**
	 * ========== OUTPUT FUNCTIONS ==========
	 */
	
	/**
	 * Formatted representation of Field
	 *
	 * Format a Field with a sprintf()-like formatting syntax. The formatting
	 * codes are the names of the subfields of the Field.
	 * @param string Field name
	 * @param string Format string
	 * @return string|false Return formatted string if Field exists, otherwise False
	 */
	function ffield($tag, $format) {
		$result = "";
		if($field = $this->field($tag)) {
			for ($i=0; $i<strlen($format); $i++) {
				$curr = $format[$i];
				if($curr != "%") {
					$result[] = $curr;
				} else {
					$i++;
					$curr = $format[$i];
					if($curr == "%") {
						$result[] = $curr;
					} else {
						$result[] = $field->subfield($curr);
					}
				}
			}
			return implode("", $result);
		} else {
			return false;
		}
	}
	
	/**
	 * Return Raw
	 *
	 * Return the Record in raw MARC format.
	 * @return string Raw MARC data
	 */
	function raw() {
		list ($fields, $directory, $reclen, $baseaddress) = $this->_build_dir();
		$this->leader_lengths($reclen, $baseaddress);
	
		/**
		 * Glue together all parts
		 */
		return $this->ldr.implode("", $directory).$this->getEndOfField().implode("", $fields).$this->getEndOfRecord();
	}
    
	/**
	 * Return formatted
	 *
	 * Return the Record in a formatted fashion. Similar to the output
	 * of the formatted() function in MARC::Record in Perl
	 * @return string Formatted representation of MARC record
	 */
	function formatted() {
		$formatted = "";
		foreach ($this->fields as $field_group) {
			foreach($field_group as $field) {
				$formatted .= $field->formatted()."\n";
			}
		}
		return $formatted;
	}
}
