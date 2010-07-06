<?php
class Dase_Atom_Feed_Collection extends Dase_Atom_Feed 
{
	function __construct($dom = null)
	{
		parent::__construct($dom);
	}

	function __get($var) {
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		} else {
			return parent::__get($var);
		}
	}

	function getDescription()
	{
		return $this->getSubtitle();
	}

	function getName() 
	{
		return $this->getTitle();
	}

	function ingest($db,$r,$fetch_enclosures=false) 
	{
		$user = $r->getUser();
		$coll_ascii_id = $this->getAsciiId();
		$count = $this->getItemCount();
		$collection_name = $this->getTitle();
		$ascii_id = $this->getAsciiId();
		$c = new Dase_DBO_Collection($db);
		$c->collection_name = $collection_name;
		if (Dase_DBO_Collection::get($db,$ascii_id) || $c->findOne()) {
			//$r->renderError(409,'collection already exists');
			Dase_Log::info(LOG_FILE,'collection exists '.$c->collection_name);
			return;
		}
		$c->ascii_id = $ascii_id;
		$c->is_public = 0;
		$c->created = date(DATE_ATOM);
		$c->updated = date(DATE_ATOM);
		if ($c->insert()) {
			$cache = $r->getCache();
			$cache->expire('app_data');
			Dase_Log::info(LOG_FILE,'created collection '.$c->collection_name);
			$coll_media_dir =  MEDIA_DIR.'/'.$ascii_id;
			if (file_exists($coll_media_dir)) {
				//$r->renderError(409,'collection media archive exists');
				Dase_Log::info(LOG_FILE,'collection media archive exists');
			} else {
				if (mkdir("$coll_media_dir")) {
					chmod("$coll_media_dir",0775);
					foreach (Dase_Media::$sizes as $size => $access_level) {
						mkdir("$coll_media_dir/$size");
						Dase_Log::info(LOG_FILE,'created directory '.$coll_media_dir.'/'.$size);
						chmod("$coll_media_dir/$size",0775);
					}
					//todo: compat only!
					symlink($coll_media_dir,$coll_media_dir.'_collection');
				}
			}
			foreach ($this->getEntries() as $entry) {
				if ('item' == $entry->getEntryType()) {
					$r->set('collection_ascii_id',$c->ascii_id);
					$entry->insert($db,$r,$fetch_enclosures);
				}
			}
		}
	}
}
