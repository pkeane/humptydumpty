<?php

class Dase_Handler_Category extends Dase_Handler
{
	public $resource_map = array(
		'{id}' => 'exercises',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
		if ($this->user->isSuperuser($r->superusers)) {
			$this->is_superuser = true;
		} else {
			$this->is_superuser = false;
		}
	}

	public function getExercises($r) 
	{
		$t = new Dase_Template($r);
		$cat = new Dase_DBO_Category($this->db);
		$cat->load($r->get('id'));
		$cat->getExercises();
		$t->assign('category',$cat);
		$cats= new Dase_DBO_Category($this->db);
		$cats->orderBy('text');
		$t->assign('categories',$cats->findAll());
		$r->renderResponse($t->fetch('category.tpl'));
	}
}

