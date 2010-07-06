<?php
class Dase_Atom_Feed_Tag extends Dase_Atom_Feed 
{
	protected $_background;
	protected $_tagType;
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

	function getEid()
	{
		return $this->getXpathValue("atom:author/atom:name");
	}

	function getSelf()
	{
		return $this->getLink('self');
	}

	function getListLink()
	{
		return $this->getLink('alternate','list');
	}

	function getGridLink()
	{
		return $this->getLink('alternate','grid');
	}

	/** beware: this just gets the first coll_ascii_id it comes to 
	 * */
	function getCollectionAsciiId()
	{
		if (!$this->_collectionAsciiId) {
			foreach ($this->getXpath("atom:category[@scheme='http://daseproject.org/category/collection']") as $node) {
				$this->_collectionAsciiId = $node->getAttribute('term');
				break;
			}
		}
		return $this->_collectionAsciiId;
	}

	function getTagType()
	{
		if ($this->_tagType) {
			return $this->_tagType;
		}
		foreach ($this->getXpath("atom:category[@scheme='http://daseproject.org/category/tag_type']") as $node) {
			$this->_tagType = $node->getAttribute('term');
			return $this->_tagType;
		}
	}

	function getBackground()
	{
		if ($this->_background) {
			return $this->_background;
		}
		foreach ($this->getXpath("atom:category[@scheme='http://daseproject.org/category/background']") as $node) {
			$this->_background = $node->getAttribute('term');
			return $this->_background;
		}
	}

}
