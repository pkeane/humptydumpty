<?php
class Dase_Atom_Entry_Set extends Dase_Atom_Entry
{
	function __construct($dom=null,$root=null)
	{
		parent::__construct($dom,$root);
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

	/** used by Dase_Handler_Tag::putEdit() */
	function update($db,$r) 
	{
		$user = $r->getUser();
		$atom_author = $this->getAuthorName();
		//should be exception??
		if (!$atom_author || $atom_author != $user->eid) {
			$r->renderError(401,'users do not match');
		}
		$ascii_id = $this->getAsciiId();
		$set = Dase_DBO_Tag::get($db,$ascii_id,$user->eid);
		if (!$set) { return; }
		$cats = $this->getCategoriesByScheme('http://daseproject.org/category/visibility');
		if (count($cats)) {
			$vis = $cats[0]['term'];
		}
		$set->visibility = $vis;
		if ('public' == $vis) {
			$set->is_public = 1;
		}
		if ('private' == $vis) {
			$set->is_public = 0;
		}

		$set->updated = date(DATE_ATOM);
		$set->update();
		//note that ONLY mutable categories will be affected
		$set->deleteCategories();
		foreach ($this->getCategories() as $category) {
			//Dase_DBO_Category::add($db,$set,$category['scheme'],$category['term'],$category['label']);

			/******* newly refactored*************/

			$tag_cat = new Dase_DBO_TagCategory($db);
			$tag_cat->tag_id = $set->id;
			$tag_cat->category_id = 0;
			$tag_cat->term = $category['term'];
			$tag_cat->label = $category['label'];
			$scheme = str_replace('http://daseproject.org/category/','',$category['scheme']);
			$tag_cat->scheme = $scheme;
			if ('utexas/courses' == $scheme) {
				if (!$tag_cat->findOne()) {
					$tag_cat->insert();
				}
			}
		}
		return $set;
	}

	public function insert($db,$r)
	{
		$user = $r->getUser();
		$atom_author = $this->getAuthorName();
		//should be exception??
		if (!$atom_author || $atom_author != $user->eid) {
			$r->renderError(401,'users do not match');
		}
		$set = new Dase_DBO_Tag($db);
		$set->ascii_id = $this->getAsciiId();
		$set->eid = $user->eid;
		if ($set->findOne()) { 
			$r->renderError(409,'set with that name exists');
		}
		$set->dase_user_id = $user->id;
		$set->name = $this->getTitle();
		$set->is_public = 0;
		$set->type= 'set';
		$set->created = date(DATE_ATOM);
		$set->updated = date(DATE_ATOM);
		$set->insert();
		/*
		foreach ($this->getCategories() as $category) {
			$tag_cat = new Dase_DBO_TagCategory($db);
			$tag_cat->tag_id = $set->id;
			$tag_cat->category_id = 0;
			$tag_cat->term = $category['term'];
			$tag_cat->label = $category['label'];
			$scheme = str_replace('http://daseproject.org/category/','',$category['scheme']);
			$tag_cat->scheme = $scheme;
			$tag_cat->insert();
		}
		 */
		return $set;
	}
}
