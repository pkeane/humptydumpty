<?php

class Dase_Handler_Admin extends Dase_Handler
{
	public $resource_map = array(
		'/' => 'admin',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
		if ($this->user->isSuperuser($r->superusers)) {
			$this->is_superuser = true;
		} else {
			$r->renderError(401);
		}
	}

	public function getAdmin($r) 
	{
		$t = new Dase_Template($r);
		$r->renderResponse($t->fetch('admin.tpl'));
	}
}

