<?php
require_once('bootstrap.php');
require_once('simpletest/autorun.php');

class TestOfCompile extends UnitTestCase {

	function testAllSourceFilesCompile() {
		$iter = new RecursiveDirectoryIterator(BASE_PATH.'/lib/Dase');
		foreach (new RecursiveIteratorIterator($iter) as $file) {
			if (
				false === substr($file->getPathname(),'.swp') && 
				false === strpos($file->getPathname(),'.svn') && 
				strpos($file->getPathname(),'.php')
			) {
				include_once $file->getPathname();
			}
		}
		$success = true;
		$this->assertTrue($success);
	}
}

