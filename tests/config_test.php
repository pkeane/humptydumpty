<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');


class TestOfConfig extends UnitTestCase {

	function testConfigRead() {
		$c = new Dase_Config(BASE_PATH);
		$c->load('inc/config.php');
		$c->load('inc/local_config.php');
		$test = $c->getAppSettings('test');
		$this->assertTrue('dase' == $test);
	}
}



