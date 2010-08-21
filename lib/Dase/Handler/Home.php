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
		$r->renderResponse($t->fetch('home.tpl'));
	}
}

