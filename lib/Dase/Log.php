<?php

class Dase_Log_Exception extends Exception {}

class Dase_Log 
{
	private $filehandle;
	private $log_file;
	private static $instance;

	const OFF 		= 1;	// Nothing at all.
	const INFO 		= 2;	// Production 
	const DEBUG 	= 3;	// Most Verbose

	public function __construct() {}

	public function __destruct()
	{
		if ($this->filehandle) {
			fclose($this->filehandle);
		}
	}

	public static function getInstance() 
	{
		if (empty( self::$instance )) {
			self::$instance = new Dase_Log;
		}
		return self::$instance;
	}

	public static function debug($log_file,$msg,$backtrace = false)
	{
		//notices helpful for debugging (including all sql)
		$log = Dase_Log::getInstance();
		$log->setLogFile($log_file);
		if (LOG_LEVEL >= Dase_Log::DEBUG) {
			$log->_write($msg,$backtrace);
		}
	}

	public static function info($log_file,$msg,$backtrace = false)
	{
		//normal notices, ok for production
		$log = Dase_Log::getInstance();
		$log->setLogFile($log_file);
		if (LOG_LEVEL >= Dase_Log::INFO) {
			$log->_write($msg,$backtrace);
		}
	}

	public static function truncate($log_file)
	{
		$log = Dase_Log::getInstance();
		$log->setLogFile($log_file);
		@unlink($log_file);
		return $log->_write("---- dase log ----\n\n");
	}

	public static function readLastLine($log_file)
	{
		$log = Dase_Log::getInstance();
		$log->setLogFile($log_file);
		if ($log->log_file && file_exists($log->log_file)) {
			return trim(array_pop(file($log->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));
		}
	}

	public function setLogFile($log_file)
	{
		$this->log_file = $log_file;
	}

	private function _init()
	{
		if (!$this->log_file) { 
			return false;
		}

		$filehandle = @fopen($this->log_file, 'a');

		if (!is_resource($filehandle)) {
			return false;
		}

		$this->filehandle = $filehandle;
		return true;
	}

	private function _write($msg,$backtrace=false)
	{
		if (!$this->_init()) {
			return false;
		}
		$date = date(DATE_W3C);
		$msg = $date.' | pid: '.getmypid().' : '.$msg."\n";
		if ($backtrace) {
			//include backtrace w/ errors
			ob_start();
			debug_print_backtrace();
			$msg .= "\n".ob_get_contents();
			ob_end_clean();
		}
		if (fwrite($this->filehandle, $msg) === FALSE) {
			throw new Dase_Log_Exception('cannot write to log_file '.$this->log_file);
		}
		return true;
	}

}
