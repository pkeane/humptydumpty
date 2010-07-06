<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');

class TestOfDatabase extends UnitTestCase {

	function testDatabaseCanConnect() {
		$c = new Dase_Config(BASE_PATH);
		$c->load('inc/config.php');
		$c->load('inc/local_config.php');
		$db = new Dase_DB($c);
		$this->assertTrue($dbh = $db->getDbh());
	}
	function testDatabaseSelect() {
		$c = new Dase_Config(BASE_PATH);
		$c->load('inc/config.php');
		$c->load('inc/local_config.php');
		$db = new Dase_DB($c);
		$c = Dase_DBO_Collection::get($db,'test');
		$this->assertTrue('Test Collection' == $c->collection_name);
	}
}



