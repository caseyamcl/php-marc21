<?php

namespace PhpMarc;

/**
 * Class File
 * Class to read MARC records from file(s)
 *
 * 
 */
Class File {
	
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
	 * Array containing raw records
	 * @var array
	 */
	var $raw;
	/**
	 * Array of warnings
	 * @var array
	 */
	var $warn;
	/**
	 * Current position in the array of records
	 * @var integer
	 */
	var $pointer;
	
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
	 * Get warning(s)
	 *
	 * Get either all warnings or a specific warning ID
	 * @param integer ID of the warning
	 * @return array|string Return either Array of all warnings or specific warning
	 */
	function warnings($id = "") {
		if(!$id) {
			return $this->warn;
		} else {
			if(array_key_exists($id, $this->warn)) {
				return $this->warn[$id];
			} else {
				return "Invalid warning ID: $id";
			}
		}
	}

	/**
	 * ========== PROCESSING FUNCTIONS ==========
	 */
	
	/**
	 * Return the next raw MARC record
	 * 
	 * Returns th nexts raw MARC record from the read file, unless all
	 * records already have been read.
	 * @return string|false Either a raw record or False
	 */
	function _next() {
		/**
		 * Exit if we are at the end of the file
		 */
		if ($this->pointer >= count($this->raw)) {
			return FALSE;
		}
		
		/**
		 * Read next line
		 */
		$usmarc = $this->raw[$this->pointer++];
	
		// remove illegal stuff that sometimes occurs between records
		// preg_replace does not know what to do with \x00, thus omitted.
		$usmarc = preg_replace("/^[\x0a\x0d]+/", "", $usmarc);
	
		/**
		 * Record validation
		 */
		if ( strlen($usmarc) < 5 ) {
			$this->_warn( "Couldn't find record length" );
		}
		$reclen = substr($usmarc,0,5);
		if ( preg_match("/^\d{5}$/", $reclen) || $reclen != strlen($usmarc) ) {
			$this->_warn( "Invalid record length \"$reclen\"" );
		}
	
		return $usmarc;
	}
	
	/**
	 * Read in MARC record file
	 *
	 * This function will read in MARC record files that either
	 * contain a single MARC record, or numerous records.
	 * @param string Name of the file
	 * @return string Returns warning if issued during read
	 */
	function file($in) {
		if(file_exists($in)) {
			$input = file($in);
			$recs = explode(self::END_OF_RECORD, join("", $input));
			// Append END_OF_RECORD as we lost it when splitting
			// Last is not record, as it is empty because every record ends
			// with END_OF_RECORD.
			for ($i = 0; $i < (count($recs)-1); $i++) {
				$this->raw[] = $recs[$i].self::END_OF_RECORD;
			}
			$this->pointer = 0;
		} else {
			return $this->_warn("Invalid input file: $in");
		}
	}
	
	/**
	 * Return next Record-object
	 *
	 * Decode the next raw MARC record and return
	 * @return Record A Record object
	 */
	function next() {
		if($raw = $this->_next()) {
			return $this->decode($raw);
		} else {
			return false;
		}
	}
	
	/**
	 * Decode a given raw MARC record
	 *
	 * "Port" of Andy Lesters MARC::File::USMARC->decode() function into PHP. Ideas and
	 * "rules" have been used from USMARC::decode().
	 *
	 * @param string Raw MARC record
	 * @return Record Decoded MARC Record object
	 */
	function decode($text) {
		if(!preg_match("/^\d{5}/", $text, $matches)) {
			$this->_croak('Record length "'.substr( $text, 0, 5 ).'" is not numeric');
		}
		
		$marc = new Record;
		
		// Store record length
		$reclen = $matches[0];
		
		if($reclen != strlen($text)) {
			$this->_croak( "Invalid record length: Leader says $reclen bytes, but it's actually ".strlen($text));
		}
		
		if (substr($text, -1, 1) != self::END_OF_RECORD)
			$this->_croak("Invalid record terminator");
			
	    // Store leader
		$marc->leader(substr( $text, 0, self::LEADER_LEN ));
		
		// bytes 12 - 16 of leader give offset to the body of the record
		$data_start = 0 + substr( $text, 12, 5 );
	
		// immediately after the leader comes the directory (no separator)
		$dir = substr( $text, self::LEADER_LEN, $data_start - self::LEADER_LEN - 1 );  // -1 to allow for \x1e at end of directory
		
		// character after the directory must be \x1e
		if (substr($text, $data_start-1, 1) != self::END_OF_FIELD) {
			$this->_croak("No directory found");
		}
		
		// All directory entries 12 bytes long, so length % 12 must be 0
		if (strlen($dir) % self::DIRECTORY_ENTRY_LEN != 0) {
			$this->_croak("Invalid directory length");
		}
		
		// go through all the fields
		$nfields = strlen($dir) / self::DIRECTORY_ENTRY_LEN;
		for ($n=0; $n<$nfields; $n++) {
			// As pack returns to key 1, leave place 0 in list empty
			list(, $tagno) = unpack("A3", substr($dir, $n*self::DIRECTORY_ENTRY_LEN, self::DIRECTORY_ENTRY_LEN));
			list(, $len) = unpack("A3/A4", substr($dir, $n*self::DIRECTORY_ENTRY_LEN, self::DIRECTORY_ENTRY_LEN));
			list(, $offset) = unpack("A3/A4/A5", substr($dir, $n*self::DIRECTORY_ENTRY_LEN, self::DIRECTORY_ENTRY_LEN));
			
			// Check directory validity
			if (!preg_match("/^[0-9A-Za-z]{3}$/", $tagno)) {
				$this->_croak("Invalid tag in directory: \"$tagno\"");
			}
			if (!preg_match("/^\d{4}$/", $len)) {
				$this->_croak("Invalid length in directory, tag $tagno: \"$len\"");
			}
			if (!preg_match("/^\d{5}$/", $offset)) {
				$this->_croak("Invalid offset in directory, tag $tagno: \"$offset\"");
			}
			if ($offset + $len > $reclen) {
				$this->_croak("Directory entry runs off the end of the record tag $tagno");
			}
			
			$tagdata = substr( $text, $data_start + $offset, $len );
			
			if ( substr($tagdata, -1, 1) == self::END_OF_FIELD ) {
				# get rid of the end-of-tag character
				$tagdata = substr($tagdata, 0, -1);
				--$len;
			} else {
				$this->_croak("field does not end in end of field character in tag $tagno");
			}
	
			if ( preg_match("/^\d+$/", $tagno) && ($tagno < 10) ) {
				$marc->append_fields(new Field($tagno, $tagdata));
			} else {
				$subfields = @explode(self::SUBFIELD_INDICATOR, $tagdata);
				$indicators = array_shift($subfields);
	
				if ( strlen($indicators) > 2 || strlen( $indicators ) == 0 ) {
					$this->_warn("Invalid indicators \"$indicators\" forced to blanks for tag $tagno\n");
					list($ind1,$ind2) = array(" ", " ");
				} else {
					$ind1 = substr( $indicators, 0, 1 );
					$ind2 = substr( $indicators, 1, 1 );
				}
	
				// Split the subfield data into subfield name and data pairs
				$subfield_data = array();
				foreach ($subfields as $subfield) {
					if ( strlen($subfield) > 0 ) {
						$subfield_data[substr($subfield, 0, 1)] = substr($subfield, 1);
					} else {
						$this->_warn( "Entirely empty subfield found in tag $tagno" );
					}
				}
	
				if (!isset($subfield_data)) {
					$this->_warn( "No subfield data found for tag $tagno" );
				}
	
				$marc->append_fields(new Field($tagno, $ind1, $ind2, $subfield_data ));
			}
		}
		return $marc;
	}
	
	/**
	 * Get the number of records available in this Record
	 * @return int The number of records
	 */
	function num_records() {
		return count($this->raw);
	}
}
