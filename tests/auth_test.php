<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');
require_once('simpletest/web_tester.php');

class AuthenticationTest extends WebTestCase {

	function setUp() {
	}

	function tearDown() {
	}

	function test401Header() {
		$r = new Dase_Http_Request('');
		if (!$r->http_host) {
			$this->assertTrue(true);
			return;
		}
		$app_root = str_replace('/tests','',$r->app_root);
		$this->get($app_root.'/user/pkeane/sets.atom');
		$this->assertAuthentication('Basic');
		$this->assertResponse(401);
		$this->assertRealm('DASe');
	}
}



