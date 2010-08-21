<?php
Class Util 
{
	function __construct() {}

	public static function sortByName($a,$b)
	{
		$a_str = strtolower($a['name']);
		$b_str = strtolower($b['name']);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}

	public static function sortObjectsByTitle($a,$b)
	{
		$a_str = strtolower($a->title);
		$b_str = strtolower($b->title);
		if ($a_str == $b_str) {
			return 0;
		}
		return ($a_str < $b_str) ? -1 : 1;
	}
}


