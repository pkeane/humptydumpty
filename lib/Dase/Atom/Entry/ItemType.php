<?php

class Dase_Atom_Entry_ItemType extends Dase_Atom_Entry
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

	function insert($db,$r,$collection) 
	{
		//think about using Slug also
		$ascii_id = $this->getAsciiId();
		if (!$ascii_id) {
			$ascii_id = Dase_Util::dirify($this->getTitle());
		}
		if (!Dase_DBO_ItemType::get($db,$collection->ascii_id,$ascii_id)) {
			$type = new Dase_DBO_ItemType($db);
			$type->ascii_id = $ascii_id;
			$type->name = $this->getTitle();
			$type->collection_id = $collection->id;
			$type->description = $this->getSummary();
			$type->insert();
			return $type;
		} else {
			throw new Dase_Exception('item type exists');
		}
	}

}
