<?php

require_once 'Dase/DBO/Autogen/Category.php';

class Dase_DBO_Category extends Dase_DBO_Autogen_Category 
{
	public $exercises;

	public function getExercises() {
		$exercat = new Dase_DBO_ExerciseCategory($this->db);
		$exercat->category_id = $this->id;
		foreach ($exercat->findAll(1) as $ec) {
			$this->exercises[] = $ec->getExercise();
		}
		return $this->exercises;
	}

}
