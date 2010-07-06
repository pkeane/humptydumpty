<?php

class Dase_Atom_Entry_User extends Dase_Atom_Entry
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

	public function getEid()
	{
		return $this->getAsciiId();
	}

	public function insert($db,$r)
	{
		$db_user = Dase_DBO_DaseUser::get($db,$this->getEid());
		if (!$db_user) {
			$db_user = new Dase_DBO_DaseUser($db);
			$db_user->name = $this->getTitle(); 
			$db_user->eid = strtolower($this->getEid()); 
			$db_user->insert();
		}
		return $db_user;
	}
}
