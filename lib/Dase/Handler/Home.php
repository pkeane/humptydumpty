<?php

class Dase_Handler_Home extends Dase_Handler
{
	public $resource_map = array(
		'/' => 'home',
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

	public function getHome($r) 
	{
		$t = new Dase_Template($r);
		$r->renderResponse($t->fetch('home.tpl'));
	}
}

