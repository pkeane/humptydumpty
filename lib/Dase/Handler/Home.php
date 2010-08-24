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
	}

	public function getHome($r) 
	{
		$t = new Dase_Template($r);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		$c = new Dase_DBO_Content($this->db);
		$c->page = 'home';
		if ($c->findOne()) {
			$t->assign('content',$c->text);
		}
		$r->renderResponse($t->fetch('home.tpl'));
	}
}

