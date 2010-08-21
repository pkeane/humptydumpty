<?php

class Dase_Handler_Directory extends Dase_Handler
{
	public $resource_map = array(
		'/' => 'search_form',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
	}

	public function getSearchForm($r) 
	{
		$t = new Dase_Template($r);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		if ($r->get('lastname')) {
			$results = Utlookup::lookup($r->get('lastname'),'sn');
			usort($results,array('Util','sortByName'));
			$t->assign('lastname',$r->get('lastname'));
			$t->assign('results',$results);
		}
		$r->renderResponse($t->fetch('directory_search.tpl'));
	}
}

