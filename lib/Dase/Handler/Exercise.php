<?php

class Dase_Handler_Exercise extends Dase_Handler
{
	public $resource_map = array(
		'create' => 'exercise_create_form',
		'{id}' => 'exercise',
		'{id}/lines' => 'exercise_lines',
		'{id}/instructions' => 'exercise_instructions',
		'{id}/media' => 'exercise_media',
		'{id}/email' => 'exercise_email',
		'{id}/submission' => 'exercise_submission',
		'{id}/category' => 'exercise_category',
		'{id}/email/{email_id}' => 'exercise_email',
		'{id}/category/{category_id}' => 'exercise_category',
		'{id}/edit' => 'exercise_edit',
	);

	protected function setup($r)
	{
		if ('json' != $r->format) {
			$this->user = $r->getUser();
			$this->user->getExercises();
			if ($this->user->isSuperuser($r->superusers)) {
				$this->is_superuser = true;
			} else {
				//	$r->renderError(401);
			}
		}
	}

	public function getExerciseCreateForm($r) 
	{
		$t = new Dase_Template($r);
		$r->renderResponse($t->fetch('exercise_create.tpl'));
	}

	public function postToExerciseSubmission($r) 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->load($r->get('id'));
		$lines = $r->getBody();
		$r->renderResponse('thanks. you submitted '.$lines);
	}

	public function postToExerciseCreateForm($r) 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->title = $r->get('title');
		$ex->creator_eid = $this->user->eid;
		$id = $ex->insert();
		$r->renderRedirect('exercise/'.$id.'/edit');
	}

	public function getExercise($r) 
	{
		$t = new Dase_Template($r);
		$ex = new Dase_DBO_Exercise($this->db);
		if(!$ex->load($r->get('id'))) {
			$r->renderRedirect('home');
		}
		$ex->getLines(true);
		$ex->getCategories();
		$ex->getEmails();
		$cats= new Dase_DBO_Category($this->db);
		$cats->orderBy('text');
		$t->assign('categories',$cats->findAll());
		$t->assign('exercise',$ex);
		$r->renderResponse($t->fetch('exercise.tpl'));
	}

	public function deleteExerciseEmail($r)
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$email = new Dase_DBO_ExerciseEmail($this->db);
		$email->load($r->get('email_id'));
		$email->delete();
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}

	public function deleteExerciseCategory($r)
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$ec = new Dase_DBO_ExerciseCategory($this->db);
		$ec->category_id = $r->get('category_id');
		$ec->exercise_id = $exercise->id;
		if ($ec->findOne()) {
			$ec->delete();
		}
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}


	public function getExerciseEdit($r) 
	{
		$t = new Dase_Template($r);
		$ex = new Dase_DBO_Exercise($this->db);
		if(!$ex->load($r->get('id'))) {
			$r->renderRedirect('home');
		}
		if ($this->user->eid != $ex->creator_eid) {
			$r->renderError(401,'unauthorized');
		}

		// media
		$media_url = "https://dase.laits.utexas.edu/search.json?q=&collection_ascii_id=hdportal&max=999";
		$resp = Dase_Http::get($media_url);
		$data = Dase_Json::toPhp($resp[1]);
		$t->assign('feed',$data);


		// all emails
		$set = array();
		$emails = new Dase_DBO_ExerciseEmail($this->db);
		foreach ($emails->findAll(1) as $e) {
			$set[] = $e->text;
		}
		if (count($set)) {
			$set = array_unique($set);
			sort($set);
			$t->assign('emails',$set);
		}

		// all categories
		$cset = array();
		$categories = new Dase_DBO_Category($this->db);
		$categories->orderBy('text');
		$t->assign('categories',$categories->findAll());

		$ex->getCreator();
		$ex->getLines();
		$ex->getCategories();
		$ex->getEmails();
		$t->assign('exercise',$ex);
		$r->renderResponse($t->fetch('exercise_edit.tpl'));
	}

	public function postToExerciseLines($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$exercise->deleteLines();
		// lines
		$text = $r->get('text');
		$lines = explode("\n",$text);
		$sort = 0;
		foreach($lines as $line_text) {
			$line_text= trim($line_text);
			if ($line_text) {
				$sort += 1;
				$line = new Dase_DBO_ExerciseLine($this->db);
				$line->text = $line_text;
				$line->exercise_id = $exercise->id;
				$line->correct_sort_order = $sort;
				$line->insert();
			}
		}
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}


	public function postToExerciseInstructions($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$exercise->instructions = $r->get('instructions');
		$exercise->update();
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}

	public function postToExerciseMedia($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));

		if (trim($r->get('remove'))) {
			$exercise->media_file = '';
			$exercise->media_file_title = '';
			$exercise->media_mime_type = '';
			$exercise->update();
		} else {
			if (trim($r->get('media_file'))) {
				$exercise->media_file = $r->get('media_file');
				$exercise->media_file_title = $r->get('media_file_title');
				$parts = explode('.',$exercise->media_file);
				$last = array_pop($parts);
				$lookup = array('mp3' => 'audio/mp3','jpg' => 'image/jpeg','gif' => 'image/gif');
				if (isset($lookup[$last])) {
					$exercise->media_mime_type = $lookup[$last];
				}
			}
			$exercise->update();
		}
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}

	public function postToExerciseCategory($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$category = new Dase_DBO_Category($this->db);
		$category->text = trim($r->get('category'));
		if (!$category->findOne()) {
			$category->ascii_id = Dase_Util::dirify($r->get('category'));
			$category->insert();
		}
		$exercat = new Dase_DBO_ExerciseCategory($this->db);
		$exercat->exercise_id = $exercise->id;
		$exercat->category_id = $category->id;
		$exercat->insert();

		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}

	public function postToExerciseEmail($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$email = new Dase_DBO_ExerciseEmail($this->db);
		$email->text = $r->get('email');
		$email->exercise_id = $exercise->id;
		$email->insert();
		$r->renderRedirect('exercise/'.$exercise->id.'/edit');
	}

	public function getExerciseJson($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		$exercise->getCreator();
		$exercise->getQuestions();
		$json = array();
		$json['exercise'] = array();
		$json['exercise']['title'] = $exercise->title;
		$json['exercise']['media_file'] = $exercise->media_file;
		$json['exercise']['creator_eid'] = $exercise->creator_eid;
		$json['exercise']['course'] = $exercise->course;
		$json['exercise']['notes'] = $exercise->notes;
		$json['exercise']['ascii_id'] = $exercise->ascii_id;
		$json['exercise']['is_published'] = $exercise->is_published;
		$json['exercise']['questions'] = array();
		foreach ($exercise->questions as $question) {
			$set = array();
			$set['text'] = $question->text;
			$set['lines'] = array();
			$so = 0;
			foreach ($question->lines as $line) {
				$so += 1;
				$aset = array();
				$aset['text'] = $line->text;
				$aset['sort_order'] = $so;
				$aset['is_correct'] = $line->is_correct;
				$set['lines'][] = $aset;
			}
			$json['exercise']['questions'][] = $set;
		}
		$r->renderResponse(Dase_Json::get($json));
	}

	public function deleteExercise($r) 
	{
		$e = new Dase_DBO_Exercise($this->db);
		$e->load($r->get('id'));
		$title = $e->title;
		//overload delete
		$e->delete();
		$r->renderResponse('success');
	}

}

