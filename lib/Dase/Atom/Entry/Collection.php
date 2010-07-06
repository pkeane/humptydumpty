<?php

class Dase_Atom_Entry_Collection extends Dase_Atom_Entry
{
	function __construct($dom=null,$root=null)
	{
		parent::__construct($dom,$root,'collection');
	}

	function create($db,$r)
	{
		$atom_author = $this->getAuthorName();
		$user = $r->getUser('http');
		$collection_name = $this->getTitle();
		if (!$collection_name) {
			$r->renderError(400,'no title');
		}
		$c = new Dase_DBO_Collection($db);
		$c->collection_name = $collection_name;
		if ($r->has('ascii_id')) {
			$ascii_id = $r->get('ascii_id'); //set in handler based on Slug
		} else {
			$ascii_id = $this->getAsciiId();
		}
		if (!$ascii_id) {
			$ascii_id = $c->createAscii();
		}
		if (Dase_DBO_Collection::get($db,$ascii_id) || $c->findOne()) {
			$r->renderError(409,'collection already exists');
		}
		$c->ascii_id = $ascii_id;
		$coll_media_dir =  MEDIA_DIR.'/'.$ascii_id;
		if (file_exists($coll_media_dir)) {
			//todo: think about this...
			//$r->renderError(409,'collection media archive exists');
		}
		$c->is_public = 0;
		$c->created = date(DATE_ATOM);
		$c->updated = date(DATE_ATOM);

		$content = $this->getContent();
		if ($content) {
			$c->description = $content;
		}

		$summary = $this->getSummary();
		if ($summary) {
			$c->admin_notes = $summary;
		}

		if ($c->insert()) {
			$cache = $r->getCache();
			$cache->expire('app_data');
			Dase_Log::info(LOG_FILE,'created collection '.$c->collection_name);
			if (mkdir("$coll_media_dir")) {
				chmod("$coll_media_dir",0775);
				foreach (Dase_Media::$sizes as $size => $access_level) {
					mkdir("$coll_media_dir/$size");
					Dase_Log::info(LOG_FILE,'created directory '.$coll_media_dir.'/'.$size);
					chmod("$coll_media_dir/$size",0775);
				}
				symlink($coll_media_dir,$coll_media_dir.'_collection');
			}
			foreach (array('title','description','keyword','rights') as $att) {
				$a = new Dase_DBO_Attribute($db);
				$a->ascii_id = $att;
				$a->attribute_name = ucfirst($att);
				$a->collection_id = $c->id;
				$a->in_basic_search = true;
				$a->is_on_list_display = true;
				$a->is_public = true;
				$a->html_input_type = 'text';
				if ('description' == $att) {
					$a->html_input_type = 'textarea';
				}
				$a->updated = date(DATE_ATOM);
				if ($a->insert()) {
					Dase_Log::debug(LOG_FILE,'created att '.$att);
				} else {
					Dase_Log::debug(LOG_FILE,'problem creating '.$att);
				}

			}
			$cm = new Dase_DBO_CollectionManager($db);
			$cm->collection_ascii_id = $ascii_id;
			$cm->dase_user_eid = $user->eid;
			$cm->auth_level = 'admin';
			$cm->created = date(DATE_ATOM);
			$cm->created_by_eid = $user->eid;
			if ($cm->insert()) {
				Dase_Log::info(LOG_FILE,'created admin user '.$ascii_id.'::'.$user->eid);
			} else {
				Dase_Log::info(LOG_FILE,'could not create admin user');
			}
			return $ascii_id;
		} else {
			return false;
		}
	}

	function getAttributes()
	{
		return $this->getCategoriesByScheme('http://daseproject.org/category/attribute'); 
	}

	function getItemTypes()
	{
		return $this->getCategoriesByScheme('http://daseproject.org/category/item_type'); 
	}

	function getVisibility()
	{
		$vcat = array_pop($this->getCategoriesByScheme('http://daseproject.org/category/visibility')); 
		return $vcat['term'];
	}

	/** for now, PUT of a collection entry can only add, NOT delete item_types & attributes */
	function update($db,$r)
	{
		$coll = $this->getAsciiId();
		foreach ($this->getAttributes() as $att) {
			Dase_DBO_Attribute::findOrCreate($db,$coll,$att['term']);
		}
		foreach ($this->getItemTypes() as $type) {
			Dase_DBO_ItemType::findOrCreate($db,$coll,$type['term']);
		}
		$coll = Dase_DBO_Collection::get($db,$coll);
		$coll->updateVisibility($this->getVisibility());
		$r->renderResponse('updated collection');
	}

	public function addAttribute($ascii_id,$name='')
	{
		$this->addCategory($ascii_id,'http://daseproject.org/category/attribute',$name);
	}

	public function addItemType($ascii_id,$name='')
	{
		$this->addCategory($ascii_id,'http://daseproject.org/category/item_type',$name);
	}

	function getName() 
	{
		return $this->getTitle();
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
}
