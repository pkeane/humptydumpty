<?php

/*
 *   see http://www.phpinsider.com/smarty-forum/viewtopic.php?t=8944
 *
 *   Copyright (c) 2006, Matthias Kestenholz <mk@spinlock.ch>, Moritz Zumbühl <mail@momoetomo.ch>
 *   Distributed under the GNU General Public License.
 *   Read the entire license text here: http://www.gnu.org/licenses/gpl.html
 *
 *   this class adds Django-style template inheritance to Smarty
 *
 *   adapted for DASe by Peter Keane 4/2008
 *
 */  


class Dase_Template {

	protected $smarty;

	public function __construct($request,$use_module_template_dir=false)
	{
		// make sure E_STRICT is turned off
		$er = error_reporting(E_ALL^E_NOTICE);

		require_once 'smarty/libs/Smarty.class.php';
		$this->smarty = new Smarty();
		$this->smarty->compile_dir = SMARTY_CACHE_DIR; 
		$this->smarty->compile_id = $request->module ? $request->module : 'smarty';
		if ($use_module_template_dir) {
			$this->smarty->template_dir = BASE_PATH.'/modules/'.$request->module.'/templates';
		} else {
			$this->smarty->template_dir = BASE_PATH.'/templates';
		}
		$this->smarty->caching = false;
		$this->smarty->security = false;
		$this->smarty->register_block('block', '_smarty_swisdk_process_block');
		$this->smarty->register_function('detect_ie', '_smarty_function_detect_ie');
		$this->smarty->register_function('extends', '_smarty_swisdk_extends');
		$this->smarty->register_modifier('solr_escape', '_smarty_solr_escape');
		$this->smarty->register_modifier('filter', '_smarty_dase_atom_feed_filter');
		$this->smarty->register_modifier('sortby', '_smarty_dase_atom_feed_sortby');
		$this->smarty->register_modifier('select', '_smarty_dase_atom_entry_select');
		$this->smarty->register_modifier('label', '_smarty_dase_atom_entry_label');
		$this->smarty->register_modifier('media', '_smarty_dase_atom_entry_select_media');
		$this->smarty->register_modifier('modifiers','_smarty_documenter_modifiers');
		$this->smarty->register_modifier('params','_smarty_documenter_get_params');
		$this->smarty->assign_by_ref('_swisdk_smarty_instance', $this);

		$this->smarty->register_modifier('shift', 'array_shift');
		//todo: confusing! $app_root shouldn't have trailing /
		$this->smarty->assign('app_root', trim($request->app_root,'/').'/');
		if ($request->module) {
			$this->smarty->assign('module_root', $request->module_root.'/');
			if (file_exists(BASE_PATH.'/modules/'.$request->module.'/templates/menu.tpl')) {
				$this->smarty->assign('module_menu', BASE_PATH.'/modules/'.$request->module.'/templates/menu.tpl');
			}
		}
		$this->smarty->assign('app_data', $GLOBALS['app_data']);
		$this->smarty->assign('msg', $request->get('msg'));
		//for searches w/ no results
		$this->smarty->assign('failed_query', $request->get('failed_query'));
		$this->smarty->assign('request', $request);
		$this->smarty->assign('main_title', MAIN_TITLE);
		$this->smarty->assign('page_logo_link_target', PAGE_LOGO_LINK_TARGET);
		$this->smarty->assign('page_logo_src', PAGE_LOGO_SRC);

		error_reporting($er);
	}

	public function __call($method, $args)
	{
		$er = error_reporting(E_ALL^E_NOTICE);
		$ret = call_user_func_array( array(&$this->smarty, $method), $args);
		error_reporting($er);
		return $ret;
	}

	public function __get($var)
	{
		$er = error_reporting(E_ALL^E_NOTICE);
		$ret = $this->smarty->$var;
		error_reporting($er);
		return $ret;
	}

	public function __set($var, $value)
	{
		$er = error_reporting(E_ALL^E_NOTICE);
		$ret = ($this->smarty->$var = $value);
		error_reporting($er);
		return $ret;
	}

	public function display($resource_name)
	{
		echo $this->fetch($resource_name);
	}

	public function fetch($resource_name)
	{
		$ret = $this->smarty->fetch($resource_name);
		while($resource = $this->_derived) {
			$this->_derived = null;
			$ret = $this->smarty->fetch($resource);
		}
		return $ret;
	}

	function __destruct() 
	{
		$now = Dase_Util::getTime();
		$elapsed = round($now - START_TIME,4);
		Dase_Log::debug(LOG_FILE,'finished templating '.$elapsed);
	}

	// template inheritance
	public $_blocks = array();
	public $_derived = null;
}

/** free-floating functions! */
function _smarty_solr_escape($string)
{
	// solr specials: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
	$pattern= '/(\+|-|"|~|\(|\)|&&|\?|}|{|:|\[|\]|!)/';
	return preg_replace($pattern,'\\\$1',$string);

}

function _smarty_dase_atom_feed_filter(Dase_Atom_Feed $feed,$att,$val)
{
	//returns an array of entries that match 
	return $feed->filter($att,$val);
}

function _smarty_dase_atom_feed_sortby(Dase_Atom_Feed $feed,$att)
{
	return $feed->sortBy($att);
}

function _smarty_dase_atom_entry_label(Dase_Atom_Entry $entry,$att)
{
	return $entry->getLabel($att);
}

function _smarty_dase_atom_entry_select(Dase_Atom_Entry $entry,$att)
{
	//returns value of attribute 
	$set = $entry->getMetadata($att);
	return $set['text'];
}

function _smarty_dase_atom_entry_select_media(Dase_Atom_Entry $entry,$size)
{
	//returns media of stated size 
	return $entry->selectMedia($size);
}

function _smarty_documenter_modifiers(Documenter $d,$value)
{
	//used in documentation 
	return $d->getModifiers($value);
}

/** here is a function used in docs */
function _smarty_documenter_get_params(ReflectionParameter $p)
{
	//from no starch oo php book
	$description = "";
	//is it an object?
	$c = $p->getClass();
	if(is_object($c)){
		$description .= $c->getName() . " ";
	}
	$description .= "\$" . $p->getName();
	//check default
	if ($p->isDefaultValueAvailable()){
		$val = $p->getDefaultValue();
		//could be empty string
		if($val == ""){
			$val = "\"\"";
		}
		$description .= " = $val";
	}
	return $description;
}

function _smarty_swisdk_process_block($params, $content, &$smarty, &$repeat)
{
	if($content===null)
		return;
	$name = $params['name'];
	$ss = $smarty->get_template_vars('_swisdk_smarty_instance');
	if(!isset($ss->_blocks[$name]))
		$ss->_blocks[$name] = $content;
	return $ss->_blocks[$name];
}

function _smarty_swisdk_extends($params, &$smarty)
{
	$ss = $smarty->get_template_vars('_swisdk_smarty_instance');
	$ss->_derived = $params['file'];
} 

function _smarty_function_detect_ie($params, &$smarty)
{
	if (isset($_SERVER['HTTP_USER_AGENT']) && 
		(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
		$smarty->assign('ie', true);
	else
		$smarty->assign('ie', false);
}
