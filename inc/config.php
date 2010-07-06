<?php

$conf['db']['type'] = 'mysql';
$conf['db']['path'] = '/var/www-data/dase/dase.db';  //sqlite only
$conf['db']['host'] = 'localhost';
$conf['db']['name'] = 'dase';
$conf['db']['user'] = 'username';
$conf['db']['pass'] = 'password';
$conf['db']['table_prefix'] = '';

//used by a test case 
//do not change or override
$conf['app']['test'] = "dase";

//force https
$conf['app']['force_https'] = false;

$conf['app']['main_title'] = "My DASe Archive";

//must be apache writeable
//only set these (in local_config) to override 
//default <base_path>/files/...
//$conf['app']['media_dir'] = '/usr/local/dase/media';
//$conf['app']['log_dir'] = '/usr/local/dase/log';

$conf['app']['user_class'] = 'Dase_DBO_DaseUser';

// 1 OFF 
// 2 INFO 
// 3 DEBUG 
$conf['app']['log_level'] = 2;

//path to imagemagick convert
$conf['app']['convert'] = '/usr/bin/convert';

//maximum no. of items displayed on a search result page
$conf['app']['max_items'] = 30;

//handler that gets invoked when APP_ROOT is requested
//$conf['default_handler'] = 'collections';
$conf['app']['default_handler'] = 'install';

//eid & admin password
//$conf['auth']['superuser']['<username>'] = '<password>';
//$conf['auth']['serviceuser']['prop'] = 'ok';
$conf['auth']['service_token'] = "changeme";

//used to create only-known-by-server security hash
$conf['auth']['token'] = 'changeme'.date('Ymd',time());

//POST/PUT/DELETE token:	
$conf['auth']['ppd_token'] = "changeme".date('Ymd',time());

//define module handlers (can override existing handler)
//$conf['request_handler']['<handler>'] = '<module_name>';
//$conf['request_handler']['login'] = 'openid';
$conf['request_handler']['db'] = 'dbadmin';
$conf['request_handler']['install'] = 'install';

//cache can be file or memcached (only 'file' is implemented) 
$conf['cache']['type'] = 'file';
//config defines a reasonable default
//$conf['cache']['dir'] = '/usr/local/dase/cache';

//search engine
$conf['search']['engine'] = 'db';

//db, file, or something else...
$conf['docstore']['type'] = 'db';
