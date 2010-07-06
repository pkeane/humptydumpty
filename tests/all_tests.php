<?php

require_once('bootstrap.php');
require_once('show_passes.php');
require_once('simpletest/autorun.php');
SimpleTest::prefer(new ShowPasses());

class AllTests extends TestSuite {
	function __construct() {
		$this->TestSuite('All tests');
		$this->addFile(dirname(__FILE__).'/auth_test.php');
		$this->addFile(dirname(__FILE__).'/cache_test.php');
		$this->addFile(dirname(__FILE__).'/compile_test.php');
		$this->addFile(dirname(__FILE__).'/config_test.php');
		$this->addFile(dirname(__FILE__).'/db_test.php');
		$this->addFile(dirname(__FILE__).'/log_test.php');
		$this->addFile(dirname(__FILE__).'/web_page_test.php');
	}
}


