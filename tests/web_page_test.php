<?php

require_once('bootstrap.php');
require_once('simpletest/autorun.php');
require_once('simpletest/web_tester.php');

class WebPageTest extends WebTestCase {
    
    function testCollectionPage() {
		$r = new Dase_Http_Request('');
		if (!$r->http_host) {
			$this->assertTrue(true);
			return;
		}
		$app_root = str_replace('/tests','',$r->app_root);
        $this->get($app_root.'/test');
        $this->assertTitle('DASe Test Page');
    }
}



