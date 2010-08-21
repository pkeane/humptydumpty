<?php

class Dase_Handler_Set extends Dase_Handler
{
	public $resource_map = array(
		'{id}' => 'set',
		'{id}/title' => 'title',
		'{id}/delete_queue' => 'delete_queue',
		'{id}/exercise/{exercise_id}' => 'exercise',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
	}

	public function postToDeleteQueue($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid && 0 == count($set->getExercises())) {
			$set->removeUsers();
			$set->delete();
			$r->renderRedirect("admin/set_form");
		} else {
			$r->renderError(401);
		}
	}

	public function postToTitle($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid) {
			$set->title = $r->get('title');
			$set->update();
			$r->renderRedirect("admin/set/$set->id");
		} else {
			$r->renderError(401);
		}
	}

	public function deleteExercise($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid) {
			$exer = new Dase_DBO_Exercise($this->db);
			$exer->load($r->get('exercise_id'));
			if ($exer->exercise_set_id = $set->id) {
				$exer->exercise_set_id = 0;
				$exer->update();
				$r->renderResponse('removed exercise');
			}
		} else {
			$r->renderError(401);
		}
	}

	public function deleteSet($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if (0 == count($set->getExercises())) {
			$set->delete();
		} else {
			$r->respond('403');
		}
		$r->renderOk();
	}

	public function getSet($r) 
	{
		$t = new Dase_Template($r);
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		$set->getExercises();
		$t->assign('set',$set);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		$r->renderResponse($t->fetch('set.tpl'));
	}
}

