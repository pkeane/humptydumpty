<?php

require_once 'Dase/DBO/Autogen/Exercise.php';

class Dase_DBO_Exercise extends Dase_DBO_Autogen_Exercise 
{
	public $str_lines;
	public $lines = array();
	public $set;
	public $creator;

	public function getSet()
	{
		$es = new Dase_DBO_ExerciseSet($this->db);
		$es->load($this->exercise_set_id);
		$this->set = $es;
		return $this->set;
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

	public function getCorrect()
	{
		$lines = new Dase_DBO_ExerciseLine($this->db);
		$lines->exercise_id = $this->id;
		$lines->orderBy('correct_sort_order');
		$correct = '';
		foreach ($lines->findAll(1) as $line) {
			$correct .= $line->id.'|';
		}
		return $correct;
	}

	public function deleteLines()
	{
		foreach ($this->getLines() as $line) {
			$line->delete();
		}
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
		parent::delete();
	}
}
