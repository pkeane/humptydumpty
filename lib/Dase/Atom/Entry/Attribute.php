<?php
class Dase_Atom_Entry_Attribute extends Dase_Atom_Entry
{
	function __construct($dom=null,$root=null)
	{
		parent::__construct($dom,$root);
	}

	function insert($db,$r,$collection) 
	{
		$att = new Dase_DBO_Attribute($db);
		$att->attribute_name = $this->getTitle();
		//think about using getAscii or Slug also
		$att->ascii_id = Dase_Util::dirify($att->attribute_name);
		if (!Dase_DBO_Attribute::get($db,$collection->ascii_id,$att->ascii_id)) {
			$att->collection_id = $collection->id;
			$att->updated = date(DATE_ATOM);
			$att->sort_order = 9999;
			$att->is_on_list_display = 1;
			$att->is_public = 1;
			$att->in_basic_search = 1;
			$att->html_input_type = $this->getHtmlInputType();
			$att->insert();
			foreach ($this->getDefinedValues() as $dv) {
				$att->addDefinedValue($dv);
			}
			foreach ($this->getItemTypes() as $type) {
				$att->addItemType($type);
			}
			$att->resort();
		} else {
			throw new Dase_Exception('attribute exists');
		}
		return $att;
	}

	/** used w/ PUT request 
	 */
	function update($r,$collection) 
	{
		throw new Exception('not yet implemented');
	}

	function getItemTypes()
	{
		$item_types = array();
		foreach ($this->getCategories() as $c) {
			if ('http://daseproject.org/category/parent_item_type' == $c['scheme']) {
				$item_types[] = $c['term'];
			}
		}
		return $item_types;
	}

	function getUsageNotes()
	{
		return $this->getSummary();
	}

	function setUsageNotes($text)
	{
		$this->setSummary($text);
	}

	function getHtmlInputType()
	{
		foreach ($this->getCategories() as $c) {
			if ('http://daseproject.org/category/html_input_type' == $c['scheme']) {
				return $c['term'];
			}
		}
		//default
		return 'text';
	}

	function getDefinedValues() {
		$defined = array();
		foreach ($this->getCategories() as $c) {
			if ('http://daseproject.org/category/defined_value' == $c['scheme']) {
				$defined[] = $c['term'];
			}
		}
		return $defined;
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
