<?php

require_once 'Dase/DBO/Autogen/User.php';

class Dase_DBO_User extends Dase_DBO_Autogen_User 
{
	public $is_superuser;
	public $exercises = array();
	public $admin_sets = array();
	public $sets = array();
	public $instructor;

	public function getExercises()
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->creator_eid = $this->eid;
		$ex->orderBy('title');
		$this->exercises = $ex->findAll(1);

		return $this->exercises;
	}

	public function getInstructor() 
	{
		$inst = new Dase_DBO_User($this->db);
		$inst->eid = $this->instructor_eid;
		if ($inst->findOne()) {
			$this->instructor = $inst;
			return $this->instructor;
		}
	}

	public function getSets()
	{
		$this->sets = array();
		$sets = new Dase_DBO_ExerciseSet($this->db);
		$sets->creator_eid = $this->eid;
		$sets->orderBy('title');
		foreach ($sets->findAll(1) as $s) {
			$this->admin_sets[] = $s;
		}

		$esus = new Dase_DBO_ExerciseSetUser($this->db);
		$esus->user_id = $this->id;
		foreach ($esus->findAll(1) as $esu) {
			$set = new Dase_DBO_ExerciseSet($this->db);
			$set->load($esu->set_id);
			$this->sets[] = $set;
		}
		usort($this->sets,array('Util','sortObjectsByTitle'));
		//return $this->sets;
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
