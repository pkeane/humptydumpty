<?php

class Dase_Atom_Categories extends Dase_Atom 
{
	protected $_categories = array();
	public static $schemes = array(
		//work on this
		'http://daseproject.org/category/entrytype',
	);

	function __construct($dom = null)
	{
		if ($dom) {
			//reader object
			$this->root = $dom->getElementsByTagNameNS(Dase_Atom::$ns['app'],'categories')->item(0);
			$this->dom = $dom;
		}  else {
			//creator object
			$dom = new DOMDocument('1.0','utf-8');
			$this->root = $dom->appendChild($dom->createElementNS(Dase_Atom::$ns['app'],'app:categories'));
			$this->dom = $dom;
		}
	}

	public static function load($xml) 
	{
		//reader object
		$dom = new DOMDocument('1.0','utf-8');
		if (is_file($xml)) {
			$dom->load($xml);
		} else {
			$dom->loadXml($xml);
		}
		return new Dase_Atom_Categories($dom);
	}

	function addCategory($term,$scheme='',$label='',$text = '') 
	{
		$cat = $this->addElement('category',$text,Dase_Atom::$ns['atom']);
		$cat->setAttribute('term',$term);
		if ($scheme) {
			$cat->setAttribute('scheme',$scheme);
		} 
		if ($label) {
			$cat->setAttribute('label',$label);
		}
	}

	function setFixed($yes_or_no) 
	{
		$this->root->setAttribute('fixed',$yes_or_no);
	}

	function getFixed() 
	{
		$fixed = $this->root->getAttribute('fixed');
		if (!$fixed) {
			$fixed = 'no';
		}
		return $fixed;
	}

	function setScheme($scheme) 
	{
		$this->root->setAttribute('scheme',$scheme);
	}

	function getScheme() 
	{
		return $this->root->getAttribute('scheme');
	}

	function getAll($cascade_root_scheme=true) {
		$default_scheme = $this->getScheme();
		$categories = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $cat) {
			$category['term'] = $cat->getAttribute('term');
			$category['label'] = $cat->getAttribute('label');
			$category['scheme'] = $cat->getAttribute('scheme');
			$category['value'] = $cat->nodeValue;
			if (!$category['scheme']) {
				if ($cascade_root_scheme) {
					$category['scheme'] = $default_scheme;
				} else {
					unset($category['scheme']);
				}
			}
			$categories[] = $category;
		}
		return $categories;
	}

	function __get($var) {
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		} else {
			return parent::__get($var);
		}
	}

	function asXml() 
	{
		//format output
		$this->dom->formatOutput = true;
		return $this->dom->saveXML();
	}

	function asJson()
	{
		$json = array();
		$json['fixed'] = $this->getFixed();
		$json['scheme'] = $this->getScheme();
		$json['categories'] = $this->getCategories(false);
		return Dase_Json::get($json);
	}
}
