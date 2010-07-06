<?php

require_once 'Dase/DBO/Autogen/Exercise.php';

class Dase_DBO_Exercise extends Dase_DBO_Autogen_Exercise 
{
	public $str_lines;
	public $lines = array();
	public $emails = array();
	public $categories = array();
	public $creator;

	public function getCategories()
	{
		$ecs = new Dase_DBO_ExerciseCategory($this->db);
		$ecs->exercise_id = $this->id;
		foreach ($ecs->findAll(1) as  $ec) {
			$this->categories[] = $ec->getCategory();
		}
		return $this->categories;
	}

	public function getLines($shuffle = false)
	{
		$line = new Dase_DBO_ExerciseLine($this->db);
		$line->exercise_id = $this->id;
		$line->orderBy('correct_sort_order');
		$this->lines = $line->findAll(1);

		//todo: guarantee that it'll never be correct sort order
		if ($shuffle) {
			shuffle($this->lines);
		}
		$set = array();
		foreach ($this->lines as $ln) {
			$set[] = trim($ln->text);
		}
		$this->str_lines = join("\n",$set);
		return $this->lines;
	}

	public function deleteLines()
	{
		foreach ($this->getLines() as $line) {
			$line->delete();
		}
	}

	public function deleteEmails()
	{
		foreach ($this->getEmails() as $email) {
			$email->delete();
		}
	}

	public function getEmails()
	{
		$emails = new Dase_DBO_ExerciseEmail($this->db);
		$emails->exercise_id = $this->id;
		$this->emails = $emails->findAll(1);
		return $this->emails;
	}

	public function getCreator()
	{
		$u = new Dase_DBO_User($this->db);
		$u->eid = $this->creator_eid;
		$this->creator = $u->findOne();
		return $this->creator;
	}

	public function delete()
	{
		$this->deleteLines();
		$this->deleteEmails();
		$ecs = new Dase_DBO_ExerciseCategory($this->db);
		$ecs->exercise_id = $this->id;
		foreach ($ecs->findAll(1) as  $ec) {
			$ec->delete();
		}
		parent::delete();
	}
}
