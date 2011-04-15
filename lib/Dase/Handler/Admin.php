<?php

class Dase_Handler_Admin extends Dase_Handler
{
	public $resource_map = array(
		'/' => 'admin',
		'users' => 'users',
		'user/{id}/is_instructor' => 'is_instructor',
		'set_form' => 'set_form',
		'add_instructor_form/{eid}' => 'add_instructor_form',
		'instructors' => 'instructors',
		'content/{page}' => 'content_form',
		'set/{id}' => 'set',
		'set/{id}/title' => 'set_title',
		'set/{id}/exercise_sorter' => 'exercise_sorter',
		'set/{id}/instructors' => 'set_instructors',
		'set/{id}/instructor/{instructor_id}' => 'set_instructor',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
		if ($this->user->is_admin || $this->user->is_instructor) {
			//ok
		} else {
			$r->renderError(401);
		}
	}

	public function initTemplate($t)
	{
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
	}

	public function getContentForm($r)
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$c = new Dase_DBO_Content($this->db);
		$c->page = $r->get('page');
		if ($c->findOne()) {
		$t->assign('content',$c->text);
		}
		$t->assign('page',$r->get('page'));
		$r->renderResponse($t->fetch('admin_content_form.tpl'));
	}

	public function postToContentForm($r)
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$c = new Dase_DBO_Content($this->db);
		$c->page = $r->get('page');
		if ($c->findOne()) {
			$c->text = $r->get('text');
			$c->update();
		} else {
			$c->text = $r->get('text');
			$c->insert();
		}
		$page = $r->get('page');
		$r->renderRedirect('admin/content/'.$page);
	}

	public function deleteSetInstructor($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		$esu = new Dase_DBO_ExerciseSetUser($this->db);
		$esu->set_id = $set->id;
		$esu->user_id = $r->get('instructor_id');
		if ($esu->findOne()) {
			$esu->delete();
		}
		$r->renderResponse("removed instructor");
	}

	public function postToSetInstructors($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		$esu = new Dase_DBO_ExerciseSetUser($this->db);
		$esu->set_id = $set->id;
		$esu->user_id = $r->get('instructor_id');
		if (!$esu->findOne()) {
			$esu->insert();
		}
		$r->renderRedirect("admin/set/$set->id");
	}

	public function postToExerciseSorter($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		$sorted = $r->get('sorted_exercises');
		$exer_array = explode('|',$sorted);
		$i = 0;
		foreach ($exer_array as $ex) {
			$i++;
			$e = new Dase_DBO_Exercise($this->db);
			$e->load($ex);
			$e->sort_order_in_set = $i;
			$e->update();
		}
		$r->renderRedirect("admin/set/$set->id");
	}

	public function getAddInstructorForm($r) 
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$record = Utlookup::getRecord($r->get('eid'));
		$u = new Dase_DBO_User($this->db);
		$u->eid = $r->get('eid');
		if ($u->findOne()) {
			$t->assign('user',$u);
		}
		$t->assign('record',$record);
		$r->renderResponse($t->fetch('add_instructor_form.tpl'));
	}

	public function postToInstructors($r)
	{
		$record = Utlookup::getRecord($r->get('eid'));
		$user = new Dase_DBO_User($this->db);
		$user->eid = $record['eid'];
		if (!$user->findOne()) {
			$user->name = $record['name'];
			$user->email = $record['email'];
			$user->is_instructor = true;
			$user->insert();
		} else {
			$user->is_instructor = true;
			$user->update();
		}
		$r->renderRedirect('admin');

	}

	public function deleteIsInstructor($r) 
	{
		$user = new Dase_DBO_User($this->db);
		$user->load($r->get('id'));
		$user->is_instructor = 0;
		$user->update();
		$r->renderResponse('deleted privileges');
	}

	public function putIsInstructor($r) 
	{
		$user = new Dase_DBO_User($this->db);
		$user->load($r->get('id'));
		$user->is_instructor = 1;
		$user->update();
		$r->renderResponse('added privileges');
	}

	public function getAdmin($r) 
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$r->renderResponse($t->fetch('admin.tpl'));
	}

	public function getSetForm($r) 
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$this->user->getSets();
		$r->renderResponse($t->fetch('admin_set_form.tpl'));
	}

	public function getUsers($r) 
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$users = new Dase_DBO_User($this->db);
		$users->orderBy('name');
		$t->assign('users', $users->findAll(1));
		$r->renderResponse($t->fetch('admin_users.tpl'));
	}

	public function getSet($r) 
	{
		$t = new Dase_Template($r);
		$t->init($this);
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($set->creator_eid != $this->user->eid) {
			$r->renderError('401');
		}
		$set->getUsers();
		$set->getExercises();
		$t->assign('set',$set);
		$this->user->getSets();
		$instructors = new Dase_DBO_User($this->db);
		$instructors->is_instructor = true;
		$t->assign('instructors',$instructors->findAll(1));
		$r->renderResponse($t->fetch('admin_set.tpl'));
	}

	public function postToSetForm($r) 
	{
		$t = new Dase_Template($r);
		$set = new Dase_DBO_ExerciseSet($this->db);
		if ($r->get('title')) {
			$set->title = $r->get('title');
			$set->ascii_id = Dase_Util::dirify($set->title);
			$set->creator_eid = $this->user->eid;
			$set->insert();
		}
		$r->renderRedirect('admin/set_form');
	}
}

