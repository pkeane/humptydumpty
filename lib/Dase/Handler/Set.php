<?php

class Dase_Handler_Set extends Dase_Handler
{
	public $resource_map = array(
		'{id}' => 'set',
		'{id}/title' => 'title',
		'{id}/delete_queue' => 'delete_queue',
		'{id}/exercise/{exercise_id}' => 'exercise',
		'{id}/download_all' => 'download_all',
	);

	protected function setup($r)
	{
		$this->user = $r->getUser();
		$this->user->getExercises();
	}

	public function getDownloadAll($r) 
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		//ZIP stuff
		$zip = new ZipArchive();
		$target_dir = "/tmp/humptydumpty_zip";
		if (!file_exists($target_dir)) {
			if (!mkdir($target_dir)) {
				$r->renderError(500);
			}
		}
		$filename = $target_dir.'/'.$set->ascii_id.".zip";
		if (file_exists($filename)) {
			unlink($filename);
		}
		if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
			$r->renderError(401,'cannot create zip');
		}
		$exercises = $set->getExercises();
		$has_media = 0;
		foreach ($exercises as $ex) {
			if ($ex->media_file) {
				$ex_ascii = Dase_Util::dirify($ex->title);
				$fn = $target_dir.'/exercise-'.$ex_ascii;
				file_put_contents($fn,file_get_contents($ex->media_file));
				if (filesize($fn)) {
					$zip->addFile($fn,$set->ascii_id.'/'.$ex_ascii.'.mp3');
					$has_media = 1;
				}
			}
		}
		$zip->close();
		if (!$has_media) {
			$params['msg'] = $set->title.' has no associated media';
			$r->renderRedirect('set/'.$set->id,$params);
		}
		//todo: need to set a cron job to garbage collect the set in media/tmp
		$r->serveFile($filename,'application/zip',true);
	}

	public function postToDeleteQueue($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid && 0 == count($set->getExercises())) {
			$set->removeUsers();
			$set->delete();
			$r->renderRedirect("admin/set_form");
		} else {
			$r->renderError(401);
		}
	}

	public function postToTitle($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid) {
			$set->title = $r->get('title');
			$set->update();
			$r->renderRedirect("admin/set/$set->id");
		} else {
			$r->renderError(401);
		}
	}

	public function deleteExercise($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if ($this->user->eid == $set->creator_eid) {
			$exer = new Dase_DBO_Exercise($this->db);
			$exer->load($r->get('exercise_id'));
			if ($exer->exercise_set_id = $set->id) {
				$exer->exercise_set_id = 0;
				$exer->update();
				$r->renderResponse('removed exercise');
			}
		} else {
			$r->renderError(401);
		}
	}

	public function deleteSet($r)
	{
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		if (0 == count($set->getExercises())) {
			$set->delete();
		} else {
			$r->respond('403');
		}
		$r->renderOk();
	}

	public function getSet($r) 
	{
		$t = new Dase_Template($r);
		$set = new Dase_DBO_ExerciseSet($this->db);
		$set->load($r->get('id'));
		$set->getExercises();
		$t->assign('set',$set);
		$t->assign('exercise_sets',Dase_DBO_ExerciseSet::getAll($this->db));
		$r->renderResponse($t->fetch('set.tpl'));
	}
}

