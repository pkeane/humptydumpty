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
		$c = new Dase_DBO_Category($this->db);
		$orphan_categories = array();
		foreach ($c->findAll() as $cat) {
			if (0 == count($cat->getExercises())) {
				$orphan_categories[] = $cat;
			}
		}
		$t->assign('orphan_categories',$orphan_categories);
		$r->renderResponse($t->fetch('admin.tpl'));
	}
}

