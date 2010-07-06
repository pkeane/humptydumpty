<?php

class Dase_Exception extends Exception {}

class Dase
{
	public static function run($config)
	{
		//sets up db object, does NOT try to connect
		$db = new Dase_DB($config);

//		$GLOBALS['app_data'] = self::initGlobalData($db,$config);

		$r = new Dase_Http_Request();
		$r->init($db,$config);

		$r->getHandlerObject()->dispatch($r);
	}

	public static function initGlobalData($db,$config)
	{
		$cache = Dase_Cache::get($config);

		//refreshed once per hour -- expunge when necessary!
		$serialized_app_data = $cache->getData('app_data',3600);

		if (!$serialized_app_data) {
			$c = new Dase_DBO_Collection($db);
			$colls = array();
			$acl = array();
			foreach ($c->find() as $coll) {
				$colls[$coll->ascii_id] = $coll->collection_name;
				$acl[$coll->ascii_id] = $coll->visibility;
				//compat
				$acl[$coll->ascii_id.'_collection'] = $coll->visibility;
			}
			$app_data['collections'] = $colls;
			$app_data['media_acl'] = $acl;
			$cache->setData('app_data',serialize($app_data));
		} else {
			$app_data = unserialize($serialized_app_data);
		}
		return $app_data;
	}
}

