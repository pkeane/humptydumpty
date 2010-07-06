<?php

require_once 'Dase/DBO/Autogen/ExerciseCategory.php';

class Dase_DBO_ExerciseCategory extends Dase_DBO_Autogen_ExerciseCategory 
{
	public $exercise;
	public $category;

	public function getExercise()
	{
		$e = new Dase_DBO_Exercise($this->db);
		$e->load($this->exercise_id);
		$this->exercise = $e;
		return $this->exercise;
	}

	public function getCategory()
	{
		$c = new Dase_DBO_Category($this->db);
		if ($c->load($this->category_id)) {
			$this->category = $c;
			return $this->category;
		}
	}

}
