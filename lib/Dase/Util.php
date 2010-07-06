<?php
Class Dase_Util 
{
	function __construct() {}

	public static function getVersion()
	{
		$ver = explode( '.', PHP_VERSION );
		return $ver[0] . $ver[1] . $ver[2];
	}

	//from http://us3.php.net/readfile
	public static function readfileChunked ($filename) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			print $buffer;
		}
		return fclose($handle);
	} 

	//todo: work on this:
	public static function isUrl($str)
	{
		if ('http://' == substr($str,0,7) || 'https://' == substr($str,0,8)) {
			return true;
		} else {
			return false;
		}
	}

	public static function unhtmlspecialchars( $string )
	{
		$string = str_replace ( '&#039;', '\'', $string );
		$string = str_replace ( '&quot;', '"', $string );
		$string = str_replace ( '&lt;', '<', $string );
		$string = str_replace ( '&gt;', '>', $string );
		//this needs to be last!!
		$string = str_replace ( '&amp;', '&', $string );
		return $string;
	}

	public static function stripInvalidXmlChars( $in ) {
		$out = "";
		$length = strlen($in);
		for ( $i = 0; $i < $length; $i++) {
			$current = ord($in{$i});
			if (($current == 0x9) ||
				($current == 0xA) || 
				($current == 0xD) || 
				($current >= 0x20 && $current <= 0xD7FF) || 
				($current >= 0xE000 && $current <= 0xFFFD) || 
				($current >= 0x10000 && $current <= 0x10FFFF)
			){
				$out .= chr($current);
			} else{
				$out .= " ";
			}
		}
		return $out;
	}

	public static function getTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	/** from http://www.weberdev.com/get_example-3543.html */
	public static function getUniqueName()
	{
		// explode the IP of the remote client into four parts
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$ip = $_SERVER["REMOTE_ADDR"];
		} else {
			$ip = '123.456.7.8';
		}
		$ipbits = explode(".", $ip);
		// Get both seconds and microseconds parts of the time
		list($usec, $sec) = explode(" ",microtime());

		// Fudge the time we just got to create two 16 bit words
		$usec = (integer) ($usec * 65536);
		$sec = ((integer) $sec) & 0xFFFF;

		// Fun bit - convert the remote client's IP into a 32 bit
		// hex number then tag on the time.
		// Result of this operation looks like this xxxxxxxx-xxxx-xxxx
		$uid = sprintf("%08x-%04x-%04x",($ipbits[0] << 24)
			| ($ipbits[1] << 16)
				| ($ipbits[2] << 8)
					| $ipbits[3], $sec, $usec);

		return $uid;
	} 

	public static function getSubdir($serial_number)
	{
		return substr(md5($serial_number),0,2);
	}

	public static function camelize($str)
	{
		$str = trim($str,'_');
		if (false === strpos($str,'_')) {
			return ucfirst($str);
		} else {
			return str_replace(' ','',ucwords(str_replace('_',' ',$str)));
			//too clever:
			//$set = explode('_',$str);
			//array_walk($set, create_function('&$v,$k', '$v = ucfirst($v);'));
			//return join('',$set);
		}
	}

	public static function truncate($string,$max)
	{
		if (strlen($string) <= $max) {
			return $string;
		}
		return substr($string,0,$max);
	}

	public static function dirify($str)
	{
		$str = strtolower(preg_replace('/[^a-zA-Z0-9_-]/','_',trim($str)));
		return preg_replace('/__*/','_',$str);
	}

	public static function makeSerialNumber($str)
	{
		if ($str) {
			//get just the last segment if it includes directory path
			$str = array_pop(explode('/',$str));
			$str = preg_replace('/[^a-zA-Z0-9_-]/','_',trim($str));
			$str = trim(preg_replace('/__*/','_',$str),'_');
			return Dase_Util::truncate($str,50);
		} else {
			return null;
		}
	}

	public static function sortByTagName($b,$a)
	{
		if ($a['name'] == $b['name']) {
			return 0;
		}
		return strcasecmp($b['name'],$a['name']);
	}

	public static function sortByLastUpdateSortable($b,$a)
	{
		if ($a->lastUpdateSortable == $b->lastUpdateSortable) {
			return 0;
		}
		return ($a->lastUpdateSortable < $b->lastUpdateSortable) ? -1 : 1;
	}
	public static function sortByCount($b,$a)
	{
		if (count($a) == count($b)) {
			return 0;
		}
		return (count($a) < count($b)) ? -1 : 1;
	}
	public static function sortByItemCount($b,$a)
	{
		if ($a->item_count == $b->item_count) {
			return 0;
		}
		return ($a->item_count < $b->item_count) ? -1 : 1;
	}
	public static function sortBySubLevel($a,$b)
	{
		if ($a->sub_level == $b->sub_level) {
			return 0;
		}
		return ($a->sub_level < $b->sub_level) ? -1 : 1;
	}
	public static function sortByAttributeName($a,$b)
	{
		$a_str = strtolower($a->attribute_name);
		$b_str = strtolower($b->attribute_name);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	public static function sortBySortOrder($a,$b)
	{
		$a_str = $a->sort_order;
		$b_str = $b->sort_order;
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	public static function sortByCollectionName($a,$b)
	{
		$a_str = strtolower($a->collection_name);
		$b_str = strtolower($b->collection_name);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	public static function sortByTitle($a,$b)
	{
		$a_str = strtolower($a->title);
		$b_str = strtolower($b->title);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	public static function sortByValueText($a,$b)
	{
		$a_str = strtolower($a->value_text);
		$b_str = strtolower($b->value_text);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	/** like above but w/ arrays */
	public static function sortByAttName($a,$b)
	{
		$a_str = strtolower($a['attribute_name']);
		$b_str = strtolower($b['attribute_name']);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
	public static function sortValuesByAttributeSortOrder($a,$b)
	{
		$a_so = $a->attribute->sort_order;
		$b_so = $b->attribute->sort_order;
		if ($a_so == $b_so) {
			return 0;
		}
		return ($a_so < $b_so) ? -1 : 1;
	}
}


