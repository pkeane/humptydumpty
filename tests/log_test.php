<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');


class TestOfLogging extends UnitTestCase {

	function setUp() {
	}

	function tearDown() {
	}

	function testLogWritesToLogFile() {
		Dase_Log::info(LOG_FILE,'test');
		$val = substr(Dase_Log::readLastLine(LOG_FILE),-4);
		$this->assertTrue('test' === $val);
	}
}



