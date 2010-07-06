<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH',dirname(__FILE__).'/..');
define('TEST_BASE_PATH',dirname(__FILE__));

define('LOG_LEVEL',3);
define('LOG_FILE',BASE_PATH.'/files/log/dase-test.log');

ini_set('include_path',BASE_PATH.'/lib');

function __autoload($class_name) {
	$class_name = str_replace('_','/',$class_name).'.php';
	@include ($class_name);
}

define('START_TIME',Dase_Util::getTime());

