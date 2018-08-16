<?php

/* --------------------------------------------------------------------

  G\ library
  http://gbackslash.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

/**
 * This class uses code that belongs or was taken from the following:
 *
 * David Soria Parra <sn_@gmx.net>
 * https://github.com/dsp/PHP-Gettext
 *
 * Jyxo, s.r.o.
 * https://github.com/jyxo/php/tree/master/Jyxo/Gettext
 *
 * WordPress
 * https://wordpress.org/
 */

/**
 * class.gettext.php
 * This class is a stand-alone implementation of gettext.
 * It works with .po and .mo files and saves the result in a cached static file (by default)
 */

namespace G;
use Exception;

class Gettext {

	// Magic words in the MO header
    const MO_MAGIC_1 = -569244523; //0xde120495
    const MO_MAGIC_2 = -1794895138; //0x950412de

	// Cache stuff
	const CACHE_FILE_SUFFIX = '.cache.php';

	protected static $default_options = ['cache' => TRUE, 'cache_type' => 'file', 'cache_filepath' => NULL, 'cache_header' => TRUE];
    protected $source_file;
    protected $parsed = FALSE;

	public $translation_table = [];
	public $translation_plural = NULL;
	public $translation_header = NULL;

    public function __construct($options=[]) {
		$this->options = array_merge(static::$default_options, (array)$options);
        $this->source_file = $this->options['file'];

		if(!@is_readable($this->source_file)) {
            throw new GettextException("Can't read source file", 100);
        }

		$file_extension = pathinfo($this->source_file, PATHINFO_EXTENSION);
		// Only allow MO and PO
		if(!in_array($file_extension, ['mo', 'po'])) {
			 throw new GettextException('Invalid file source. This only works with .mo and .po files', 101);
		}

		$this->parse_method = strtoupper($file_extension);

		if($this->options['cache']) {
			if($this->options['cache_filepath']) {
				// Custom whatever filepath cache
				$this->cache_file = $this->options['cache_filepath'];
			} else {
				// Default cache filepath.cache.php
				$this->cache_file = $this->source_file . self::CACHE_FILE_SUFFIX;
			}
			if(!$this->getCache()) { // No cache was found
				$this->parseFile();
			}
		} else {
			$this->parseFile();
		}
    }

	/**
	 * Return a translated string
	 *
	 * If the translation is not found, the original message will be returned.
	 *
	 * @param String $msg The message to search for
	 * @return translated string
	 */
    public function gettext($msg) {
		if(empty($msg)) return NULL;
        if(!$this->parsed) $this->parseFile();

		if($this->mustFixQuotes()) {
			$msg = $this->fixQuotes($msg, 'escape');
		}

		$translated = $msg;

        if(array_key_exists($msg, $this->translation_table)) {
			$translated = $this->translation_table[$msg][0];
			$translated = !empty($translated) ? $translated : $msg;
        }

		if($this->mustFixQuotes()) {
			$translated = $this->fixQuotes($translated, 'unescape');
		}

		return $translated;
    }

	/**
	 * Return a translated string in it's plural form
	 *
	 * Returns the given $count (e.g second, third,...) plural form of the
	 * given string. If the id is not found and $num == 1 $msg is returned,
	 * otherwise $msg_plural
	 *
	 * @param String $msg The message to search for
	 * @param String $msg_plural A fallback plural form
	 * @param Integer $count Which plural form
	 *
	 * @return translated string
	 */
    public function ngettext($msg, $msg_plural, $count=0) {
		if(empty($msg) or empty($msg_plural) or !is_numeric($count)) {
			return $msg;
		}
        if(!$this->parsed) $this->parseFile();

		if($this->mustFixQuotes()) {
			$msg = $this->fixQuotes($msg, 'escape');
			$msg_plural = $this->fixQuotes($msg_plural, 'escape');
		}

		$translated = $count == 1 ? $msg : $msg_plural; // Failover

		if(array_key_exists($msg, $this->translation_table)) {
			$plural_index = $this->getPluralIndex($count);
			$index_id = $plural_index !== FALSE ? $plural_index : ($count - 1);
			$table = $this->translation_table[$msg];
			if(array_key_exists($index_id, $table)) {
				$translated = $table[$index_id];
			}
    }

		if($this->mustFixQuotes()) {
			$translated = $this->fixQuotes($translated, 'unescape');
		}

		return $translated;

    }

	/**
	 * Parse the source file
	 * If cache is enabled it will try to cache the result
	 */
    private function parseFile() {
		$parseFn = 'parse' . $this->parse_method . 'File';
		try {
			$this->$parseFn();
			$this->parsed = TRUE;
			if($this->options['cache']) {
				try {
					$this->cache('file');
				} catch(Exception $e) {
					error_log($e); // Don't scream for cache issues
				}
			}
		} catch(Exception $e) {
			throw $e;
		}
    }

	/**
	 * Parse the MO file header and returns the table
	 * offsets as described in the file header.
	 *
	 * If an exception occurred, null is returned. This is intentionally
	 * as we need to get close to ext/gettext behaviour.
	 *
	 * @param resource $fp The open file handler to the MO file
	 *
	 * @return array offset
	 */
    private function parseMOHeader($fp) {
        $data = fread($fp, 8);
		if(!$data) {
			throw new GettextException("Can't fread(8) file for reading", 202);
		}
		$header = unpack('lmagic/lrevision', $data);
        if(self::MO_MAGIC_1 != $header['magic'] && self::MO_MAGIC_2 != $header['magic']) {
            return NULL;
        }
        if(0 != $header['revision']) {
            return NULL;
        }
        $data = fread($fp, 4 * 5);
		if(!$data) {
			throw new GettextException("Can't fread(4 * 5) file for reading", 203);
		}
        $offsets = unpack('lnum_strings/lorig_offset/' . 'ltrans_offset/lhash_size/lhash_offset', $data);
        return $offsets;
    }

	/**
     * Parse and returns the string offsets in a a table. Two table can be found in
     * a mo file. The table with the translations and the table with the original
     * strings. Both contain offsets to the strings in the file.
     *
     * If an exception occurred, null is returned. This is intentionally
     * as we need to get close to ext/gettext behaviour.
     *
     * @param resource	$fp     The open file handler to the MO file
     * @param int		$offset The offset to the table that should be parsed
     * @param int   	$num    The number of strings to parse
     *
     * @return Array of offsets
     */
    private function parseMOTableOffset($fp, $offset, $num) {
        if(fseek($fp, $offset, SEEK_SET) < 0) {
            return NULL;
        }
        $table = [];
        for($i=0; $i<$num; $i++) {
            $data = fread($fp, 8);
            $table[] = unpack('lsize/loffset', $data);
        }
        return $table;
    }

	/**
     * Parse a string as referenced by an table. Returns an
     * array with the actual string.
     *
     * @param resource $fp The open file handler to the MO fie
     * @param array	$entry The entry as parsed by parseMOTableOffset()
     *
     * @return Parsed string
     */
    private function parseMOEntry($fp, $entry) {
		if(fseek($fp, $entry['offset'], SEEK_SET) < 0) {
			return NULL;
		}
		if($entry['size'] > 0) {
			return fread($fp, $entry['size']);
		}
		return NULL;
    }

	/**
     * Parse the plural data found in the language
     *
     * @param string $header with nplurals and plural declaration
     */
	private function parsePluralData($header) {
    // Base english-like plural languages
    $nplurals = 2;
    $formula = '(n != 1)';
		// Detect plural data. If nothing found then use general plural handling
		if(preg_match('/\s*nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*(\({0,1}.*\){0,1})\s*;/', $header, $matches)) {
      $nplurals = (int) $matches[1];
      if(preg_match('/^([!n=<>&\|\?:%\s\(\)\d]+)$/', $matches[2]) === TRUE) {
        $formula = $matches[2];
      }
		}

		// Fix the plural formula
		$formula = $this->parenthesizePluralFormula($formula);

		// Generate the translation_plural array
		$function = str_replace('n', '$n', $formula);

		// Stock everything
		$this->translation_plural = [
			'nplurals'	=> $nplurals,
			'function'	=> $function,
		];
	}

	/**
	 * Adds parentheses to the inner parts of ternary operators in
	 * plural formulas, because PHP evaluates ternary operators from left to right
	 *
	 * @param string $formula the expression without parentheses
	 * @return string the formula with parentheses added
	 */
	private function parenthesizePluralFormula($formula) {
		$formula .= ';';
		$return = '';
		$depth = 0;
		for ($i = 0; $i < strlen($formula); ++$i) {
			$char = $formula[$i];
			switch($char) {
				case '?':
					$return .= ' ? (';
					$depth++;
					break;
				case ':':
					$return .= ') : (';
					break;
				case ';':
					$return .= str_repeat(')', $depth) . ';';
					$depth = 0;
					break;
				default:
					$return .= $char;
			}
		}
		$return = trim(rtrim($return, ';')); // Cleaning
		$return = preg_replace('/\s+/S', ' ', $return); // Extra spaces
		$return = str_replace('( ', '(', str_replace(' )', ')', $return)); // Remove extra space around ()
		return $return;
	}

	/**
	 * Get plural index
	 *
	 * @param int msg count
	 * @return int plural index
	 */
	function getPluralIndex($count) {
    if(!is_callable($this->translation_plural['callable'])) {
      // So, this is how you interpeter this thing
      $function = $this->translation_plural['function'];
      $nplurals = $this->translation_plural['nplurals'];
      $evil = "\$callable = function(\$n) {\$index = (int)$function; return \$index < $nplurals ? \$index : ($nplurals - 1);};";
      eval($evil);
      $this->translation_plural['callable'] = $callable;
    }
		return call_user_func($this->translation_plural['callable'], $count);
	}

	private function parseHeader($header) {
		$headerTable = [];
        $lines = array_map('trim', explode("\n", $header));
        foreach($lines as $line) {
			if(starts_with('msgid', $line) or starts_with('msgstr', $line)) continue;
			$line = preg_replace('#\"(.*)\"#', '$1', $line);
			$line = rtrim($line, '\n');
            $parts = explode(':', $line, 2);
            if(!isset($parts[1])) continue; // Skip empty keys
            $headerTable[trim($parts[0])] = trim($parts[1]);
        }
        return $headerTable;
	}

	/**
     * Parse a PO entry chunk
     * @param Array $chunk
	 *
     * @return Array of translation table
     */
	private function parsePOEntry($chunk) {
		$chunks = explode("\n", $chunk);

		foreach($chunks as $chunk) {

			// Skip #: and empty chunks
			if(starts_with('#', $chunk) or is_null($chunk)) {
				continue;
			}

			// Parse the plural forms
			if(is_null($this->translation_plural) and starts_with('"Plural-Forms:', $chunk)) {
				$this->parsePluralData($chunk);
			}

			// Nasty regexes
			if(preg_match('/^msgid "(.*)"/', $chunk, $matches)) {
				$msgid = $matches[1];
			} elseif(preg_match('/^msgstr "(.*)"/', $chunk, $matches)) {
				$msgstr = $matches[1];
			} elseif(preg_match('/^#~ msgid "(.*)"/', $chunk, $matches)) {
				//$obsolete = TRUE;
				$msgid = $matches[1];
			} elseif(preg_match('/^#~ msgstr "(.*)"/', $chunk, $matches)) {
				//$obsolete = TRUE;
				$msgstr = $matches[1];
			} elseif(preg_match('/^(#: .+)$/', $chunk, $matches)) {
				$location .= $matches[1];
			} elseif(preg_match('/^#, fuzzy/', $chunk)) {
				$fuzzy = TRUE;
			} elseif(preg_match('/^msgid_plural "(.*)"/', $chunk, $matches)) {
				$plural = $matches[1];
				//$msgstr = [];
			} elseif(preg_match('/^msgstr\[([0-9])+\] "(.*)"/', $chunk, $matches)) {
				if($matches[2] == '') continue;
				if(!is_array($msgstr)) {
					$msgstr = [];
				}
				$msgstr[$matches[1]] = $matches[2];
			}
		}

		if($msgstr == '') $msgstr = NULL;

		if(empty($msgid)) {
			return NULL;
		} else {
			return [
				'msgid' => $msgid,
				'msgstr'=> is_null($msgstr) ? NULL : (array)$msgstr
			];
		}

	}


	/**
	 * Parse binary .mo file
	 */
	private function parseMOFile() {

        $filesize = filesize($this->source_file);
        if($filesize < 4 * 7) {
            return;
        }

		$fp = @fopen($this->source_file, 'rb');
		if(!$fp) {
			throw new GettextException("Can't fopen file for reading", 200);
		}

		$offsets = $this->parseMOHeader($fp);

		if(NULL == $offsets || $filesize < 4 * ($offsets['num_strings'] + 7)) {
			fclose($fp);
			return;
		}

		$transTable = array();
		$table = $this->parseMOTableOffset($fp, $offsets['trans_offset'], $offsets['num_strings']);
		if(NULL == $table) {
			fclose($fp);
			return;
		}

		foreach($table as $idx => $entry) {
			$transTable[$idx] = $this->parseMOEntry($fp, $entry);
		}

		$this->translation_header = $this->parseHeader(reset($transTable));

		// Parse plural data
		$this->parsePluralData($this->translation_header['Plural-Forms']);

		$table = $this->parseMOTableOffset($fp, $offsets['orig_offset'], $offsets['num_strings']);

		foreach($table as $idx => $entry) {
			$entry = $this->parseMOEntry($fp, $entry);
			$formes = explode(chr(0), $entry);
			$translation = explode(chr(0), $transTable[$idx]);
			foreach($formes as $form) {
				if(empty($form)) continue;
				$this->translation_table[$form] = $translation;
			}
		}

		fclose($fp);
	}

	/**
	 * Parse text based .po file
	 */
	private function parsePOFile() {
		$linenumber = 0;
		$chunks = [];
		$file = file($this->source_file);
		if(!$file) {
			throw new GettextException("Can't read file into an array", 204);
		}
		foreach($file as $line) {
			if($line == "\n" or $line == "\r\n") {
				++$linenumber;
			} else {
				if(!array_key_exists($linenumber, $chunks)) {
					$chunks[$linenumber] = '';
				}
				$chunks[$linenumber] .= $line;
			}
		}
		$this->translation_header = $this->parseHeader(reset($chunks));

		foreach($chunks as $chunk) {
			$entry = $this->parsePOEntry($chunk);
			if(!$entry['msgid'] or is_null($entry['msgstr'])) continue;
			$this->translation_table[$entry['msgid']] = $entry['msgstr'];
		}
	}

	/**
	 * Get cached results (cached file)
	 *
	 * @return bool cache status
	 */
	private function getCache() {
		if(@is_readable($this->cache_file)) {
			// Outdated cache?
			$source_mtime = filemtime($this->source_file);
			$cache_mtime = filemtime($this->cache_file);
			if($source_mtime and $cache_mtime and $source_mtime > $cache_mtime) {
				return FALSE;
			}
			if(!@include_once($this->cache_file)) {
				return FALSE;
			}
			if(is_array($translation_table)) {
				$this->translation_table = $translation_table;
				if(is_array($translation_plural)) {
					$this->translation_plural = $translation_plural;
				}
				if(is_array($translation_header)) {
					$this->translation_header = $translation_header;
				}
				$this->is_cached = TRUE;
				$this->parsed = TRUE;
				return TRUE;
			}
		}
		$this->is_cached = FALSE;
		return FALSE;
	}

	/**
	 * Cache the translation results into a file
	 */
	private function cache() {
		if(!@is_dir(dirname($this->cache_file))) {
			throw new GettextException("Target cache dir doesn't exists", 400);
		}
		if(($fh = @fopen($this->cache_file, 'w')) === FALSE) {
			throw new GettextException("Can't fopen cache file for writing", 401);
		}
		// Cache contents, closer as possible to the mo/po scheme
		$contents = '<?php' . "\n";

		if($this->options['cache_header']) {
			if(!is_null($this->translation_header)) {
				$contents .= '$translation_header = ' . var_export($this->translation_header, TRUE) . ';' . "\n";
			}
			if(!is_null($this->translation_plural)) {
				$translation_plural = $this->translation_plural;
				unset($translation_plural['callable']); // Don't cache the callable reference
				$contents .= '$translation_plural = ' . var_export($translation_plural, TRUE) . ';' . "\n";
			}
		}

		// Note that we keep the same "quotes" used by the PO file scheme
		$contents .= '$translation_table = [';
		foreach($this->translation_table as $k => $v) {
			$k = $this->parse_method == 'PO' ? $k : $this->fixQuotes($k, 'escape');
			$contents .= "\n" . '	"' . $k . '" => [';
			foreach($v as $kk => $vv) {
				$kk = $this->parse_method == 'PO' ? $kk : $this->fixQuotes($kk, 'escape');
				$vv = $this->parse_method == 'PO' ? $vv : $this->fixQuotes($vv, 'escape');
				$contents .= "\n" . '		' . $kk . ' => "' . $vv . '",';
			}
			$contents .= "\n". '	],';
		}
		$contents .= "\n" . '];' . "\n" . '?>';

		if(!fwrite($fh, $contents)) {
			throw new GettextException("Can't save translation results to cache file", 402);
		}
		@touch($this->source_file); // Make sure to use the correct filemtime next time
		fclose($fh);
	}

	private function fixQuotes($msg, $action=NULL) {
		if($this->is_cached) return $msg;
		switch($action) {
			case 'escape':
				$msg = str_replace('"', '\"', $msg);
			break;
			case 'unescape':
				$msg = str_replace('\"', '"', $msg);
			break;
		}
		return $msg;
	}

	private function mustFixQuotes() {
		return $this->is_cached or $this->parse_method == 'PO';
	}

}

class GettextException extends Exception {}