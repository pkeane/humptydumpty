<?php

require_once 'Dase/DBO/Autogen/ExerciseSet.php';

class Dase_DBO_ExerciseSet extends Dase_DBO_Autogen_ExerciseSet 
{
	public $exercises = array();
	public $users = array();

	public static function getAll($db)
	{
		$sets = new Dase_DBO_ExerciseSet($db);
		$sets->orderBy('title');
		return $sets->findAll(1);
	}

	public function getExercises() 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->exercise_set_id = $this->id;
		$ex->orderBy('sort_order_in_set');
		foreach ($ex->findAll(1) as $e) {
			$this->exercises[] = $e;
		}
		return $this->exercises;
	}

	public function getUsers() 
	{
		$esus = new Dase_DBO_ExerciseSetUser($this->db);
		$esus->set_id = $this->id;
		foreach ($esus->findAll(1) as $esu) {
			$u = new Dase_DBO_User($this->db);
			$u->load($esu->user_id);
			$this->users[] = $u;
		}
		return $this->users;
	}

	public function removeUsers() 
	{
		$esus = new Dase_DBO_ExerciseSetUser($this->db);
		$esus->set_id = $this->id;
		foreach ($esus->findAll(1) as $esu) {
			$esu->delete();
		}
	}
}
