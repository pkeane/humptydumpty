<?php

class Dase_Handler_Exercise extends Dase_Handler
{
	public $resource_map = array(
		'create' => 'exercise_create_form',
		'{id}' => 'exercise',
		'{id}/lines' => 'exercise_lines',
		'{id}/title' => 'exercise_title',
		'{id}/instructions' => 'exercise_instructions',
		'{id}/media' => 'exercise_media',
		'{id}/submission' => 'exercise_submission',
		'{id}/category' => 'exercise_category',
		'{id}/category/{category_id}' => 'exercise_category',
		'{id}/edit' => 'exercise_edit',
		'{id}/set' => 'exercise_set',
	);

	protected function setup($r)
	{
		if ('json' != $r->format) {
			$this->user = $r->getUser();
			$this->user->getExercises();
			$this->user->getSets();
		}
	}

	public function getExerciseCreateForm($r) 
	{
		$t = new Dase_Template($r);
		$sets = new Dase_DBO_ExerciseSet($this->db);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		$r->renderResponse($t->fetch('exercise_create.tpl'));
	}

	public function postToExerciseSubmission($r) 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->load($r->get('id'));
		$set = $ex->getSet();
		$correct = $ex->getCorrect();
		$lines = trim($r->getBody());

		$lines_set = explode('|',$lines);
		$ordered_lines = '';
		foreach ($lines_set as $line_id) {
			$l = new Dase_DBO_ExerciseLine($this->db);
			$l->load($line_id);
			$ordered_lines .= $l->text."\n";
		}

		$instructor = $this->user->getInstructor();
		if ($instructor) {
			$email = $instructor->email;
		} else {
			$creator = $ex->getCreator();
			$email = $creator->email;
		}

		$notification = new Dase_DBO_Notification($this->db);
		$notification->timestamp = date(DATE_ATOM);
		$notification->recipient = $email;
		$notification->student_eid = $this->user->eid;
		$notification->exercise_title = $ex->title;
		$notification->set_title = $set->title;
		$notification->ordered_lines = $ordered_lines;
		$notification->insert();

		if ($lines == $correct) {
			$result = "correct answer";
			$alert = "You answered correctly :)\n\n$email will be notified\n";
		} else {
			$result = "incorrect answer";
			//$alert = "Sorry, you had some errors. Try Again!\n\n$email will be notified\n";
			$alert = "Sorry, still broken - there's always next time!\n\n$email will be notified\n";
		}
		$email_header = 'From: Humpty Dumpty Portal'."\r\n";
		$email_header .= 'Cc: pkeane@mail.utexas.edu' . "\r\n";
		$email_subject = "Humpty Dumpty Portal Exercise Submission";
		$email_body = "student: ".$this->user->name." (".$this->user->eid.")\n";
		$email_body .= "exercise : $ex->title from set $set->title\n";
		$email_body .= "\n$result\n";
		$email_body .= "\nordered_lines:\n\n$ordered_lines\n";
		mail($email,$email_subject,$email_body,$email_header);

		$r->renderResponse($alert);
	}

	public function postToExerciseSet($r) 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->load($r->get('id'));
		$ex->exercise_set_id = $r->get('set_id');
		$ex->update();
		$r->renderRedirect("exercise/$ex->id/edit");
	}

	public function postToExerciseCreateForm($r) 
	{
		$ex = new Dase_DBO_Exercise($this->db);
		$ex->title = $r->get('title');
		$ex->creator_eid = $this->user->eid;
		$ex->exercise_set_id = $r->get('exercise_set_id');
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
		$t->assign('set',$ex->getSet());
		$t->assign('exercise',$ex);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		$r->renderResponse($t->fetch('exercise.tpl'));
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

		// all categories
		$cset = array();
		$ex->getCreator();
		$ex->getLines();
		$ex->getSet();
		$t->assign('exercise',$ex);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
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

	public function postToExerciseTitle($r) 
	{
		$exercise = new Dase_DBO_Exercise($this->db);
		$exercise->load($r->get('id'));
		if ($r->get('title')) {
			$exercise->title = $r->get('title');
		}
		$exercise->update();
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

