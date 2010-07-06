<?php

class Dase_Http_Request
{
	public static $types = array(
		'atom' =>'application/atom+xml',
		'cats' =>'application/atomcat+xml',
		'css' =>'text/css',
		'csv' =>'text/csv',
		'default' =>'text/html',
		'gif' =>'image/gif',
		'html' =>'text/html',
		'jpg' =>'image/jpeg',
		'json' =>'application/json',
		'mov' =>'video/quicktime',
		'mp3' =>'audio/mpeg',
		'mp4' =>'video/mp4',
		'oga' => 'audio/ogg',
		'ogv' => 'video/ogg',
		'png' =>'image/png',
		'pdf' =>'application/pdf',
		'txt' =>'text/plain',
		'uris' =>'text/uri-list',
		'uri' =>'text/uri-list',
		'xhtml' =>'application/xhtml+xml',
		'xml' =>'application/xml',
	);

	private $auth_config;
	private $cache;
	private $config;
	private $db;
	private $default_handler;
	private $eid_is_serviceuser;
	private $eid_is_superuser;
	private $env = array();
	private $http_cookie;

	//members are variables 'set'
	private $members = array();
	private $null_user;
	private $params;
	private $serviceusers = array();
	private $superusers = array();
	private $url_params = array();
	private $user;

	public function __construct()
	{
		$env['protocol'] = isset($_SERVER['HTTPS']) ? 'https' : 'http'; 
		$env['method'] = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : '';
		$env['_get'] = $_GET;
		$env['_post'] = $_POST;
		$env['_cookie'] = $_COOKIE;
		$env['_files'] = $_FILES;
		$env['htuser'] = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		$env['htpass'] = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		$env['request_uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$env['http_host'] =	isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$env['server_addr'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
		$env['query_string'] =	isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$env['script_name'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
		$env['slug'] = isset($_SERVER['HTTP_SLUG']) ? $_SERVER['HTTP_SLUG'] : '';
		$env['http_title'] = isset($_SERVER['HTTP_TITLE']) ? $_SERVER['HTTP_TITLE'] : '';
		$env['remote_addr'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$env['http_user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$env['app_root'] = trim($env['protocol'].'://'.$env['http_host'].dirname($env['script_name']),'/');
		//env is assign to this twice since it needs to be use in other methods
		$this->env = $env;
		$env['format'] = $this->getFormat();
		$env['module'] = $this->getModule(); 
		$env['handler'] = $this->getHandler(); 
		$env['handler_path'] = $env['handler'];
		$env['path'] = $this->getPath();
		if ($env['module']) {
			$env['module_root'] = $env['app_root'].'/modules/'.$env['module'];
		} else {
			$env['module_root'] = '';
		}
		$env['response_mime_type'] = self::$types[$env['format']];
		$env['content_type'] = $this->getContentType();
		$this->env = $env;

	}

	public function init($db,$config)
	{
		$this->db = $db;
		$this->config = $config;

		$this->initDefaultHandler();
		$this->initUser();
		$this->initCache();
		$this->initCookie();
		$this->initAuth();
		$this->initPlugin();
		$this->logRequest();
	}

	public function __get( $var )
	{
		//first env
		if ( array_key_exists($var,$this->env)) {
			return $this->env[$var];
		}
		//second params
		if ( array_key_exists( $var, $this->members ) ) {
			return $this->members[ $var ];
		}
		//third getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		}
	}

	public function initDefaultHandler() 
	{
		//for simple (single) handler
		$handler_file = BASE_PATH.'/handler.php';
		if (file_exists($handler_file)) {
			include_once "$handler_file";
			//do not autoload -- want only handler.php class
			if (class_exists('Dase_Handler_Default',false)) { 
				$this->env['handler'] = 'default';
				$this->env['handler_path'] = '';
				return;
			} else {
				//fall through
			}
		}

		$default_handler = $this->config->getAppSettings('default_handler');

		//when root of app is requested 
		if (!$this->env['handler']) {
			$this->renderRedirect($default_handler);
		} else {
			$this->default_handler = $default_handler;
		}
	}

	public function initAuth()
	{
		$auth_config = $this->config->getAuth();
		$this->token = $auth_config['token'];
		$this->ppd_token = $auth_config['ppd_token'];
		$this->service_token = $auth_config['service_token'];
		$this->superusers = isset($auth_config['superuser']) ? $auth_config['superuser'] : array();
		$this->serviceusers = isset($auth_config['serviceuser']) ? $auth_config['serviceuser'] : array();
		$this->auth_config = $auth_config;
	}

	public function checkUrlAuth()
	{
		$url = $this->app_root.'/'.$this->getPath(false);
		$expires = $this->get('expires');
		$auth_token = $this->get('auth_token');

		if (!$expires || !$auth_token ) {
			return false;
		}
		if (time() > $expires) {
			return false;
		}
		if ($auth_token == md5($url.$expires.$this->token)) {
			return true;
		}
		return false;
	}

	public function getAuthConfig()
	{
		return $this->auth_config;
	}

	public function getSuperusers()
	{
		return $this->superusers;
	}

	public function getServiceusers()
	{
		return $this->serviceusers;
	}

	public function getAuthToken()
	{
		return $this->token;
	}

	public function getBody()
	{
		return file_get_contents("php://input");
	}

	public function initPlugin()
	{
		$custom_handlers = $this->config->getCustomHandlers();
		if ($this->module) { 
			return; 
		}
		$h = $this->handler;
		//simply reimplement any handler as a module
		if (isset($custom_handlers[$h])) {
			if(!file_exists(BASE_PATH.'/modules/'.$custom_handlers[$h])) {
				$this->renderError(404,'no such module');
			}
			Dase_Log::info(LOG_FILE,'**PLUGIN ACTIVATED**: handler:'.$h.' module:'.$custom_handlers[$h]);
			$this->setModule($custom_handlers[$h]);
		}
	}

	public function initModule($config)
	{
		if (!$this->module) {
			return;
		}
		//modules, by convention, have one handler in a file named
		$handler_file = BASE_PATH.'/modules/'.$this->module.'/handler.php';
		if (file_exists($handler_file)) {
			include "$handler_file";

			//module can set/override configurations
			$handler_config_file = BASE_PATH.'/modules/'.$this->module.'/inc/config.php';
			$config->load($handler_config_file);

			//modules can carry their own libraries
			$new_include_path = ini_get('include_path').':modules/'.$this->module.'/lib'; 
			ini_set('include_path',$new_include_path); 

			//would this allow module names w/ underscores???
			//$classname = 'Dase_ModuleHandler_'.Dase_Util::camelize($r->module);
			$classname = 'Dase_ModuleHandler_'.ucfirst($this->module);
		} else {
			$this->renderError(404,"no such handler: $handler_file");
		}
		return $classname;
	}

	public function getHandlerObject()
	{
		$classname = $this->initModule($this->config);
		if (!$classname) {
			$classname = 'Dase_Handler_'.Dase_Util::camelize($this->handler);
		}
		if (class_exists($classname,true)) {
			return new $classname($this->db,$this->config);
		} else {
			Dase_Log::info(LOG_FILE,'no such handler class '.$classname.' redirecting');
			$this->renderRedirect($this->default_handler);
		}
	}

	public function getElapsed()
	{
		$now = Dase_Util::getTime();
		return round($now - START_TIME,4);
	}	


	public function logRequest()
	{
		Dase_Log::debug(LOG_FILE,$this->getLogData());
	}

	public function getLogData()
	{
		$env = $this->env;
		$out = "\n-----------------------------------\n";
		$out .= '[request_uri] '.$env['request_uri']."\n";
		$out .= '[method] '.$env['method']."\n";
		$out .= '[remote_addr] '.$env['remote_addr']."\n";
		$out .= '[http_user_agent] '.$env['http_user_agent']."\n";
		$out .= '[app_root] '.$env['app_root']."\n";
		if (isset($env['format'])) {
			$out .= '[format] '.$env['format']."\n";
		}
		if (isset($env['module'])){
			$out .= '[module] '.$env['module']."\n";
		}
		if (isset($env['handler'])) {
			$out .= '[handler] '.$env['handler']."\n";
		}
		$out .= "\n-----------------------------------\n";
		return $out;
	}

	public function initCookie()
	{
		$token = $this->config->getAuth('token');
		$this->http_cookie = new Dase_Http_Cookie($this->app_root,$this->module,$token);
	}

	public function setCookie($cookie_type,$value)
	{
		$this->http_cookie->set($cookie_type,$value);
	}

	public function getCookie($cookie_type)
	{
		return $this->http_cookie->get($cookie_type,$this->env['_cookie']);
	}

	public function clearCookies()
	{
		$this->http_cookie->clear();
	}

	public function initCache()
	{
		$this->cache = Dase_Cache::get($this->config);
	}

	public function getCache()
	{
		return $this->cache;
	}

	public function getCacheId()
	{
		//cache buster deals w/ aggressive browser caching.  Not to be used on server (so normalized).
		$query_string = preg_replace("!cache_buster=[0-9]*!i",'cache_buster=stripped',$this->query_string);
		//allows us to pass in a ttl 
		$query_string = preg_replace("!(&|\?)ttl=[0-9]*!i",'',$query_string);
		Dase_Log::debug(LOG_FILE,'cache id is '. $this->method.'|'.$this->path.'|'.$this->format.'|'.$query_string);
		return $this->method.'|'.$this->path.'|'.$this->format.'|'.$query_string;
	}

	public function checkCache($ttl=null)
	{
		//so you can pass in 'ttl' query param
		if ($this->get('ttl')) {
			$ttl = $this->get('ttl');
		}
		$content = $this->cache->getData($this->getCacheId(),$ttl);
		if ($content) {
			$this->renderResponse($content,false);
		}
	}

	public function setModule($module) 
	{
		$this->env['module'] = $module;
		$this->env['module_root'] = $this->env['app_root'].'/modules/'.$this->env['module'];
	}

	public function getModule()
	{
		$parts = explode('/',trim($this->getPath(),'/'));
		$first = array_shift($parts);
		if ('modules' == $first) {
			if(!isset($parts[0])) {
				$this->renderError(404,'no module specified');
			}
			if(!file_exists(BASE_PATH.'/modules/'.$parts[0])) {
				$this->renderError(404,'no such module');
			}
			return $parts[0];
		} else {
			return '';
		}
	}

	public function getHandler()
	{
		$parts = explode('/',trim($this->getPath(),'/'));
		$first = array_shift($parts);
		if ('modules' == $first && isset($parts[0])) {
			//so dispatch matching works
			return 'modules/'.$parts[0];
		} else {
			return $first;
		}
	}

	public function getHeaders() 
	{
		//note: will ONLY work w/ apache (OK by me!)
		return apache_request_headers();
	}

	public function getHeader($name)
	{
		$headers = $this->getHeaders();
		if (isset($headers[$name])) {
			return $headers[$name];
		} else {
			return false;
		}
	}

	public function getContentType() 
	{
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$header = $_SERVER['CONTENT_TYPE'];
		}
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$header = $_SERVER['HTTP_CONTENT_TYPE'];
		}
		if (isset($header)) {
			list($type,$subtype,$params) = Dase_Media::parseMimeType($header);
			if (isset($params['type'])) {
				return $type.'/'.$subtype.';type='.$params['type'];
			} else {
				return $type.'/'.$subtype;
			}
		}
	}

	public function getFormat()
	{
		//first check extension
		$pathinfo = pathinfo($this->getPath(false));
		if (isset($pathinfo['extension']) && $pathinfo['extension']) {
			$ext = $pathinfo['extension'];
			if (isset(self::$types[$ext])) {
				return $ext;
			}
		}
		//next, try 'format=' query param
		if ($this->has('format')) {
			if (isset(self::$types[$this->get('format')])) {
				return $this->get('format');
			}
		}	
		//default is html for get requests
		if ('get' == $this->env['method']) {
			return 'html';
		}
		return 'default';
	}

	public function getPath($strip_extension=true)
	{
		//returns full path w/o domain & w/o query string
		$path = $this->env['request_uri'];
		if (strpos($path,'..')) { //thwart the wily hacker
			throw new Dase_Http_Exception('no go');	
		}
		$base = trim(dirname($this->env['script_name']),'/');
		$path= preg_replace("!$base!",'',$path,1);
		$path= str_replace("index.php",'',$path);
		$path= trim($path, '/');
		/* Remove the query_string from the URL */
		if ( strpos($path, '?') !== FALSE ) {
			list($path,$query_string )= explode('?', $path);
		}
		if ($strip_extension) {
			if (strpos($path,'.') !== false) {
				$parts = explode('.', $path);
				$ext = array_pop($parts);
				if (isset(Dase_Http_Request::$types[$ext])) {
					$path = join('.',$parts);
				} else {	
					//path remains what it originally was
				}
			}
		}
		return $path;
	}

	public function get($key,$as_array = false)
	{
		//note: cannot deal well w/ php style array params
		$post = $this->_post;
		if (!$as_array) {
			//precedence is post,get,url_param,set member
			$value = $this->_filterPost($key) ? $this->_filterPost($key) : $this->_filterGet($key);
			if (trim($value) || '0' === substr($value,0,1)) {
				return $value;
			} else {
				if (isset($this->params[$key])) {
					return $this->params[$key];
				}
				if (isset($this->members[$key])) {
					return $this->members[$key];
				}
				if (isset($this->url_params[$key])) {
					//necessary for late-set url_params like when we pass "original search" in
					return $this->url_params[$key][0]; //'cause it is an array
				}
				return false;
			}
		} else {
			if ('post' == $this->method) {
				if (isset($post[$key])) {
					if (is_array($post[$key])) {
						//need to implement the value[] for this to work
						return $this->_filterArray($post[$key]);
					} else {
						return array(strip_tags($post[$key]));
					}
				}
			} else {
				return $this->_getUrlParamsArray($key);
			}
		}
	}

	public function getAll($key)
	{
		return $this->get($key,true);
	}

	public function has($key)
	{
		return $this->_filterPost($key) || 
			$this->_filterGet($key) || 
			isset($this->params[$key]) ||
			isset($this->members[$key]) ||
			isset($this->url_params[$key]); //necessary for late-set url_params
	}

	public function set($key,$val)
	{
		$this->members[$key] = $val;
	}

	/** allows multiple values for a key */
	public function setUrlParam($key,$val)
	{
		$this->_getUrlParamsArray($key); //presetting avoids trouncing 
		if (!isset($this->url_params[$key])) {
			$this->url_params[$key] = array();
		} 
		$this->url_params[$key][] = $val;
	}

	public function setParams($params)
	{
		$this->params = $params;
	}

	private function getUrlParams()
	{
		$this->_getUrlParamsArray('xxxxx');
		return $this->url_params;
	}

	private function _getUrlParamsArray($key)
	{
		if (count($this->url_params)) {
			//meaning we've been here
			if (isset($this->url_params[$key])) {
				return $this->url_params[$key];
			} else {
				return array();
			}
		}
		//allow multiple params w/ same key as an array (like standard CGI)
		//todo: write tests for this
		$url_params = array();
		$url_params[$key] = array();
		//NOTE: urldecode is NOT UTF-8 compatible
		$pairs = explode('&',html_entity_decode(urldecode($this->query_string)));
		if (count($pairs) && $pairs[0]) {
			foreach ($pairs as $pair) {
				if (false !== strpos($pair,'=')) {	
					list($k,$v) = explode('=',$pair);
					if (!isset($url_params[$key])) {
						$url_params[$k] = array();
					} 
					$url_params[$k][] = $v;
				} else { //this deals with case of '&' in search term!
					//like search?q=horse&q=red & green
					//we still have $k left over from last list ($k,$v) = explode... 
					if (isset($k)) {
						//gets the last one
						$last = array_pop($url_params[$k]);
						$url_params[$k][] = $last.'&'.$pair;
					}
				} 
			}
		}
		$this->url_params = $url_params;
		return $url_params[$key];
	}

	public function getUrl() 
	{
		$this->path = $this->path ? $this->path : $this->getPath();
		return trim($this->path . '?' . $this->query_string,'?');
	}

	public function addQueryString($pairs_string)
	{
		if ($this->query_string) {
			$this->query_string .= "&".$pairs_string;
		} else {
			$this->query_string = "?".$pairs_string;
		}
	}

	public function setQueryStringParam($key,$val)
	{
		$this->query_string = preg_replace("!$key=[^&]*!","$key=$val",$this->query_string,1,$count);
		if (!$count) {
			$this->addQueryString("$key=$val");
		}
	}

	public function initUser()
	{
		$this->null_user = Dase_User::get($this->db,$this->config);
	}

	public function setUser($user)
	{
		$this->user = $user;
	}

	public function getUser($auth='cookie',$force_login=true)
	{
		if ($this->user) {
			return $this->user;
		}

		//allow auth type to be forced w/ query param
		if ($this->has('auth')) {
			$auth = $this->get('auth');
		}

		switch ($auth) {
		case 'cookie':
			$eid = $this->http_cookie->getEid($this->_cookie);
			break;
		case 'http':
			$eid = $this->_authenticate();
			break;
		case 'service':
			$eid = $this->_authenticate(true);
			break;
		case 'none':
			//returns a null user
			return $this->null_user;
		default:
			$eid = $this->http_cookie->getEid($this->_cookie);
		}

		//eids are always lowercase
		$eid = strtolower($eid);

		if ($eid) {
			$u = clone $this->null_user;
			$this->user = $u->retrieveByEid($eid);
		}

		if ($eid && $this->user) {
			if (isset($this->serviceusers[$eid])) {
				$this->user->is_serviceuser = true;
			}
			if (isset($this->superusers[$eid])) {
				$this->user->is_superuser = true;
			}
			//set http password
			$this->user->setHttpPassword($this->token);
			return $this->user;
		} else {
			if (!$force_login) { return false; }
			if ('html' == $this->format) {
				$params['target'] = $this->getUrl();
				$this->renderRedirect('login/form',$params);
			} else {
				//last chance, check url auth but it 
				//ONLY works to override cookie auth
				if ('cookie' == $auth && $this->checkUrlAuth()) {
					return $this->null_user;
				}
				$this->renderError(401,'unauthorized');
			}
		}
	}

	/** this function authenticates Basic HTTP
	 *  and returns EID
	 */

	private function _authenticate($check_db=false)
	{
		$request_headers = apache_request_headers();
		$passwords = array();

		if ($this->htuser && $this->htpass) {
			$eid = $this->htuser;
			//Dase_Log::debug(LOG_FILE,'adding password '.substr(md5($this->token.$eid.'httpbasic'),0,12));
			//Dase_Log::debug(LOG_FILE,'token is '.$this->token);
			$passwords[] = substr(md5($this->token.$eid.'httpbasic'),0,12);

			//for service users:
			//if eid is among service users, get password w/ service_token as salt
			if (isset($this->serviceusers[$eid])) {
				Dase_Log::debug(LOG_FILE,'serviceuser request from '.$eid);
				$passwords[] = md5($this->service_token.$eid);
			}

			//lets me use the superuser passwd for http work
			if (isset($this->superusers[$eid])) {
				$passwords[] = $this->superusers[$eid];
			}

			//this is used for folks needing a quick service pwd to do uploads
			if ($check_db) {
				$u = clone $this->null_user;
				if ($u->retrieveByEid($eid)) {
					$pass_md5 = md5($this->htpass);
					if ($pass_md5 == $u->service_key_md5) {
						Dase_Log::debug(LOG_FILE,'accepted user '.$eid.' using password '.$this->htpass);
						return $eid;
					}
				}
			}

			if (in_array($this->htpass,$passwords)) {
				Dase_Log::debug(LOG_FILE,'accepted user '.$eid.' using password '.$this->htpass);
				return $eid;
			} else {
				Dase_Log::debug(LOG_FILE,'rejected user '.$eid.' using password '.$this->htpass);
			}
		} else {
			Dase_Log::debug(LOG_FILE,'PHP_AUTH_USER and/or PHP_AUTH_PW not set');
		}
		header('WWW-Authenticate: Basic realm="DASe"');
		header('HTTP/1.1 401 Unauthorized');
		echo "sorry, authorized users only";
		exit;
	}

	private function _filterArray($ar)
	{
		if (Dase_Util::getVersion() >= 520) {
			return filter_var_array($ar, FILTER_SANITIZE_STRING);
		} else {
			foreach ($ar as $k => $v) {
				$ar[$k] = strip_tags($v);
			}
			return $ar;
		}
	}

	private function _filterGet($key)
	{
		$get = $this->_get;
		if (Dase_Util::getVersion() >= 520) {
			return trim(filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING));
		} else {
			if (isset($get[$key])) {
				return trim(strip_tags($get[$key]));
			}
		}
		return false;
	}

	private function _filterPost($key)
	{
		$post = $this->_post;
		if (Dase_Util::getVersion() >= 520) {
			return trim(filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING));
		} else {
			if (isset($post[$key])) {
				if (is_array($post[$key])) {
					$clean_array = array();
					foreach ($post[$key] as $inp) {
						$inp = strip_tags($inp);
						$clean_array[] = $inp;
					}
					return $clean_array;
				} else {
					return strip_tags($post[$key]);
				}
			}
		}
		return false;
	}

	public function renderResponse($content,$set_cache=true,$status_code=null)
	{
		$response = new Dase_Http_Response($this);
		if ('get' != $this->method) {
			$set_cache = false;
		}
		$response->render($content,$set_cache,$status_code);
		exit;
	}

	public function renderOk($msg='')
	{
		$response = new Dase_Http_Response($this);
		$response->ok($msg);
		exit;
	}

	public function serveFile($path,$mime_type,$download=false)
	{
		//Dase_Log::debug(LOG_FILE,'serving '.$path.' as '.$mime_type);
		$response = new Dase_Http_Response($this);
		$response->serveFile($path,$mime_type,$download);
		exit;
	}

	public function renderRedirect($path='',$params=null)
	{
		$response = new Dase_Http_Response($this);
		$response->redirect($path,$params);
		exit;
	}

	public function renderError($code,$msg='',$log_error=true)
	{
		$response = new Dase_Http_Response($this);
		$response->error($code,$msg,$log_error);
		exit;
	}

	public function renderAtomError($code,$msg='')
	{
		$response = new Dase_Http_Response($this);
		$response->atomError($code,$msg);
		exit;
	}
}

