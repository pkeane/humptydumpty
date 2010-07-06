<?php

require_once 'Dase/DBO/Autogen/User.php';

class Dase_DBO_User extends Dase_DBO_Autogen_User 
{
	public $is_superuser;
	public $exercises = array();
	public $categories = array();

	public function getExercises()
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->creator_eid = $this->eid;
		$this->exercises = $ex->findAll(1);

		//get categories as well
		$e_lookup = array();
		foreach ($this->exercises as $e) {
			$e_lookup[$e->id] = 1;
		}
		$exercat = new Dase_DBO_ExerciseCategory($this->db);
		foreach ($exercat->findAll(1) as $ec) {
			if (isset($e_lookup[$ec->exercise_id])) {
				$cat = $ec->getCategory();
				$this->categories[$cat->text] = $ec->getCategory();
			}
		}
		ksort($this->categories);
		return $this->exercises;
	}

	public static function get($db,$id)
	{
		$user = new Dase_DBO_User($db);
		$user->load($id);
		return $user;
	}

	public function retrieveByEid($eid)
	{
		$prefix = $this->db->table_prefix;
		$dbh = $this->db->getDbh(); 
		$sql = "
			SELECT * FROM {$prefix}user 
			WHERE lower(eid) = ?
			";	
		$sth = $dbh->prepare($sql);
		$sth->execute(array(strtolower($eid)));
		$row = $sth->fetch();
		if ($row) {
			foreach ($row as $key => $val) {
				$this->$key = $val;
			}
			Dase_Log::debug(LOG_FILE,'DEBUG: retrieved user '.$eid);
			return $this;
		} else {
			Dase_Log::debug(LOG_FILE,'DEBUG: could NOT retrieve user '.$eid);
			return false;
		}
	}

	public function setHttpPassword($token)
	{
		$this->http_password = substr(md5($token.$this->eid.'httpbasic'),0,12);
		return $this->http_password;
	}

	public function getHttpPassword($token=null)
	{
		if (!$token) {
			if ($this->http_password) {
				//would have been set by request
				return $this->http_password;
			}
			throw new Dase_Exception('user auth is not set');
		}
		if (!$this->http_password) {
			$this->http_password = $this->setHttpPassword($token);
		}
		return $this->http_password;
	}

	public function isSuperuser($superusers)
	{
		if (in_array($this->eid,array_keys($superusers))) {
			$this->is_superuser = true;
			return true;
		}
		return false;
	}

}
