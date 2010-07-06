<?php
class Dase_Atom_Feed_Search extends Dase_Atom_Feed 
{
	function __construct($dom = null)
	{
		parent::__construct($dom);
	}

	function __get($var) 
	{
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		} else {
			return parent::__get($var);
		}
	}

	function getPrevious()
	{
		return $this->getLink('previous');
	}

	function getNext()
	{
		return $this->getLink('next');
	}

	function getSelf()
	{
		return $this->getLink('self');
	}

	function getSearchLink()
	{
		return $this->getLink('alternate');
	}

	function getSearchEcho()
	{
		return $this->getXpathValue("atom:subtitle/h:div/h:div[@class='searchEcho']");
	}

	function getListLink()
	{
		return $this->getLink('related','list');
	}

	function getGridLink()
	{
		return $this->getLink('related','grid');
	}

	/** for single collection searches only */
	function getCollection()
	{
		foreach ($this->getXpath("atom:link[@rel='http://daseproject.org/relation/collection']") as $node) {
			$res['href'] = $node->getAttribute('href');
			$res['title'] = $node->getAttribute('title');
			$res['ascii_id'] = array_pop(explode('/',$res['href']));
			return $res;
		}
	}

	function getCollectionFilters()
	{
		$colls = array();
		foreach ($this->getXpath("atom:category[@scheme='http://daseproject.org/category/collection_filter']") as $node) {
			$colls[] = $node->getAttribute('term');
		}
		return $colls;
	}

	/** for single collection searches only */
	function getAttributesLink()
	{
		foreach ($this->getXpath("atom:link[@rel='http://daseproject.org/relation/collection/attributes']") as $node) {
			return $node->getAttribute('href');
		}
	}

	function getSearchTallies()
	{
		$tallied = array();
		$single_coll = array();
		foreach ($this->getXpath("atom:link[@rel='http://daseproject.org/relation/single_collection_search']") as $node) {
			$single_coll['href'] = $node->getAttribute('href');
			$single_coll['title'] = $node->getAttribute('title');
			$single_coll['count'] = $node->getAttributeNS(Dase_Atom::$ns['thr'],'count');
			$tallied[] = $single_coll;
		}
		return $tallied;
	}

	function getCount()
	{
		return $this->getOpensearchTotal();
	}

	function getMax()
	{
		return $this->getXpathValue("opensearch:itemsPerPage");
	}

	function getStartIndex()
	{
		return $this->getXpathValue("opensearch:startIndex");
	}

	/** convenience method to get first item */
	function getEntry()
	{
		if (!$this->entry_dom) {
			$this->entry_dom = $this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'entry')->item(0);
		}
		return new Dase_Atom_Entry_Item($this->dom,$this->entry_dom);
	}

	public function sortByThumbnail($dimension)
	{
		$sizes = array(); 
		foreach($this->entries as $entry){
			$sizes[$entry->getThumbnailDimension($dimension)][] = $entry;
		}
		ksort($sizes);
		foreach ($sizes as $k => $set) {
			foreach ($set as $e) {
				$entries[] = $e;
			}
		}
		$this->_entries = $entries;
		return $this;
	}
}
