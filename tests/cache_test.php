<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');


class TestOfCache extends UnitTestCase {


	/* NOTE: timestamp weirdness makes this fail
	 * from command line.  It passes on web
	 */
	function testDataIsCached() {
		$c = new Dase_Config(BASE_PATH);
		$c->load('inc/config.php');
		$c->load('inc/local_config.php');
		$cache = Dase_Cache::get($c);
		$cache->setData('my_cache_file','hello world');
		$data = $cache->getData('my_cache_file');
		$this->assertTrue('hello world' == $data);
	}

	/* NOTE: this is purposely slow
	 */
	/*
	function testDataIsExpired() {
		$c = new Dase_Config(BASE_PATH);
		$c->load('inc/config.php');
		$c->load('inc/local_config.php');
		$cache = Dase_Cache::get($c);
		$cache->setData('my_cache_file','hello world');
		$data = $cache->getData('my_cache_file');
		$this->dump('pausing for 2 seconds...');
		sleep(2);
		$this->assertFalse('hello world' == $cache->getData('my_cache_file',1));
	}
	 */
}



