<?php

ini_set('include_path',BASE_PATH.'/lib');

function __autoload($class_name) {
	$class_name = str_replace('_','/',$class_name).'.php';
	@include ($class_name);
}

//set up configuration object
$config = new Dase_Config(BASE_PATH);
$config->load('inc/config.php');
$config->load('inc/local_config.php');

if ($config->getAppSettings('force_https')) {
	if ('on' != $_SERVER['HTTPS']) {
		$secure_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header("Location:$secure_url");
	}
}

//imagemagick
define('CONVERT',$config->getAppSettings('convert'));

//log file
define('LOG_FILE',$config->getLogDir().'/dase.log');
define('FAILED_SEARCH_LOG',$config->getLogDir().'/failed_searches.log');
define('DEBUG_LOG',$config->getLogDir().'/debug.log');

//log level
define('LOG_LEVEL',$config->getAppSettings('log_level'));

//media directory
define('MEDIA_DIR',$config->getMediaDir());

//db table prefix
define('TABLE_PREFIX',$config->getDb('table_prefix'));

//cache type
define('CACHE_TYPE',$config->getCacheType());

define('SMARTY_CACHE_DIR',$config->getCacheDir());

//max items diplayed per page
define('MAX_ITEMS',$config->getAppSettings('max_items'));

//main title
define('MAIN_TITLE',$config->getAppSettings('main_title'));

//custom page logo
define('PAGE_LOGO_LINK_TARGET',$config->getLocalSettings('page_logo_link_target'));
define('PAGE_LOGO_SRC',$config->getLocalSettings('page_logo_src'));

//timer
define('START_TIME',Dase_Util::getTime());


