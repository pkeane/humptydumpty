<?php

class Dase_Atom_Exception extends Exception {}

class Dase_Atom
{
	//these need to be public
	//so Feed can access Entry's root
	//upon serialization
	public $dom;
	public $root;

	protected $id;
	protected $rights_is_set;
	protected $title_is_set;
	protected $updated_is_set;
	protected $links = array(); //links cache
	protected $xpath_obj;
	public static $ns = array(
		'ae' => 'http://purl.org/atom/ext/',
		'app' => 'http://www.w3.org/2007/app',
		'atom' => 'http://www.w3.org/2005/Atom',
		'dc' => 'http://purl.org/dc/elements/1.1/',
		'dcterms' => 'http://purl.org/dc/terms/',
		'd' => 'http://daseproject.org/ns/1.0',
		'gd' =>'http://schemas.google.com/g/2005',
		'gsx' =>'http://schemas.google.com/spreadsheets/2006/extended',
		'h' => 'http://www.w3.org/1999/xhtml',
		'media' => 'http://search.yahoo.com/mrss/',
		'opensearch' => 'http://a9.com/-/spec/opensearch/1.1/',
		'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'thr' => 'http://purl.org/syndication/thread/1.0',

	);

	function __get($var) {
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		}
	}

	public static function getNewId()
	{
		return 'tag:daseproject.org,'.date('Y').':'.Dase_Util::getUniqueName();
	}

	//convenience method for atom elements
	function addElement($tagname,$text='',$ns='') 
	{
		if (!$ns) {
			$ns = Dase_Atom::$ns['atom'];
		}
		$elem = $this->root->appendChild($this->dom->createElementNS($ns,$tagname));
		if ($text || '0' === (string) $text) { //so '0' works
			$elem->appendChild($this->dom->createTextNode($text));
		}
		return $elem;
	}

	//convenience method for atom elements
	function addChildElement($parent,$tagname,$text='',$ns='') 
	{
		if (!$ns) {
			$ns = Dase_Atom::$ns['atom'];
		}
		$elem = $parent->appendChild($this->dom->createElementNS($ns,$tagname));
		if ($text) {
			$elem->appendChild($this->dom->createTextNode($text));
		}
		return $elem;
	}

	function addAuthor($name_text='',$uri_text='',$email_text='') 
	{
		$author = $this->addElement('author');
		if (!$name_text) {
			$name_text = 'DASe (Digital Archive Services)';
			$uri_text = 'http://daseproject.org';
			$email_text = 'admin@daseproject.org';
		}
		$this->addChildElement($author,'name',$name_text);
		if ($uri_text) {
			$this->addChildElement($author,'uri',$uri_text);
		}
		if ($email_text) {
			$this->addChildElement($author,'email',$email_text);
		}
	}

	function getAuthorName()
	{
		return $this->getXpathValue("atom:author/atom:name",$this->root);
	}

	function addCategory($term,$scheme='',$label='',$text='') 
	{
		$cat = $this->addElement('category',$text);
		$cat->setAttribute('term',$term);
		if ($scheme) {
			$cat->setAttribute('scheme',$scheme);
		}
		if ($label) {
			$cat->setAttribute('label',$label);
		}
		return $cat;
	}

	function getItemCount()
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/item_count' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function getCategories() 
	{
		$categories = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $cat) {
			$category = array();
			$category['term'] = $cat->getAttribute('term');
			$category['scheme'] = $cat->getAttribute('scheme');
			//if ($cat->getAttribute('label')) {
			$category['label'] = $cat->getAttribute('label');
			//}
			if ($cat->nodeValue || '0' === $cat->nodeValue) {
				$category['value'] = $cat->nodeValue;
			}
			$categories[] = $category;
		}
		return $categories;
	}

	/** return just the first 'hit' */
	function getCategoryTerm($scheme) 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $cat) {
			if ($scheme == $cat->getAttribute('scheme')) {
				return $cat->getAttribute('term');
			}
		}
	}

	function getCategoriesByScheme($scheme) 
	{
		$categories = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $cat) {
			if ($scheme == $cat->getAttribute('scheme')) {
				$category['term'] = $cat->getAttribute('term');
				if ($cat->getAttribute('label')) {
					$category['label'] = $cat->getAttribute('label');
				}
				if ($cat->nodeValue || '0' === $cat->nodeValue) {
					$category['value'] = $cat->nodeValue;
				}
				$categories[] = $category;
			}
		}
		return $categories;
	}

	/** returns the DOMNode! */
	function getCategoryNode($scheme,$term) 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $cat) {
			if (
				$scheme == $cat->getAttribute('scheme') &&
				$term == $cat->getAttribute('term')
			) {
				return $cat;
			}
		}
	}

	/** returns the DOMNode! */
	function getLinkNode($rel,$href) 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $link) {
			if (
				$rel == $link->getAttribute('rel') &&
				$href == $link->getAttribute('href')
			) {
				return $link;
			}
		}
	}

	function addContributor($name_text,$uri_text = '',$email_text = '') 
	{
		$contributor = $this->addElement('contributor');
		$this->addChildElement($contributor,'name',$name_text);
		if ($uri_text) {
			$this->addChildElement($contributor,'uri',$uri_text);
		}
		if ($email_text) {
			$this->addChildElement($contributor,'email',$email_text);
		}
	}

	function getId() {
		return $this->getAtomElementText('id');
	}

	function setId($text='') 
	{
		if ($this->id) {
			throw new Dase_Atom_Exception('id is already set');
		} elseif(!$text) {
			$text = 'tag:daseproject.org,'.date("Y-m-d").':'.time();
		} 
		$this->id = $text;
		$id_element = $this->addElement('id',$text);
	}

	function addLink($href,$rel='',$type='',$length='',$title='') 
	{
		$link = $this->addElement('link');
		//a felicitous attribute order
		if ($rel) {
			$link->setAttribute('rel',$rel);
		}
		if ($title) {
			$link->setAttribute('title',$title);
		}
		$link->setAttribute('href',$href);
		if ($type) {
			$link->setAttribute('type',$type);
		}
		if ($length) {
			$link->setAttribute('length',$length);
		}
		return $link;
	}

	function addLinkEarly($href,$rel='',$type='',$length='',$title='') 
	{
		//the idea here is that it is early in the document and so fast to find.
		//an important optimization for large feeds
		$ns = Dase_Atom::$ns['atom'];
		$ref_node = $this->root->getElementsByTagNameNS($ns,'id')->item(0);
		$link = $this->root->insertBefore($this->dom->createElementNS($ns,'link'),$ref_node);
		//a felicitous attribute order
		if ($rel) {
			$link->setAttribute('rel',$rel);
		}
		if ($title) {
			$link->setAttribute('title',$title);
		}
		$link->setAttribute('href',$href);
		if ($type) {
			$link->setAttribute('type',$type);
		}
		if ($length) {
			$link->setAttribute('length',$length);
		}
		return $link;
	}

	function getLinks() {
		$links = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			$link['item_type'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'item_type');
			$link['count'] = $el->getAttributeNS(Dase_Atom::$ns['thr'],'count');
			$link['rel'] = $el->getAttribute('rel');
			$link['href'] = $el->getAttribute('href');
			$link['title'] = $el->getAttribute('title');
			$link['type'] = $el->getAttribute('type');
			$link['length'] = $el->getAttribute('length');
			foreach ($link as $k => $v) {
				if (!$link[$k] && '0' !== $link[$k]) {
					unset ($link[$k]);
				}
			}
			$links[] = $link;
		}
		return $links;
	}

	function getLinksByRel($rel) {
		$links = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			if ($el->getAttribute('rel') == $rel) {
				$link = array();
				$link['rel'] = $el->getAttribute('rel');
				$link['item_type'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'item_type');
				$link['count'] = $el->getAttributeNS(Dase_Atom::$ns['thr'],'count');
				$link['href'] = $el->getAttribute('href');
				$link['title'] = $el->getAttribute('title');
				$link['type'] = $el->getAttribute('type');
				$link['length'] = $el->getAttribute('length');
				foreach ($link as $k => $v) {
					if (!$link[$k] && '0' !== $link[$k]) {
						unset ($link[$k]);
					}
				}
				$links[] = $link;
			}
		}
		return $links;
	}

	function getNext() 
	{
		return $this->getLink('next');
	}

	function getPrevious() 
	{
		return $this->getLink('previous');
	}

	function getServiceLink()
	{
		return $this->getLink('service');
	}

	function getSelf()
	{
		return $this->getLink('self');
	}

	//todo: should also pass in media_type for filtering
	function getLink($rel='alternate',$title='_notitle') 
	{
		if (!$title) {
			$title = '_notitle';
		}

		//check cache
		if (isset($this->links[$rel]) && ($this->links[$rel][$title] || 0 === $this->links[$rel][$title])) {
			return $this->links[$rel][$title];
		}

		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			//allow filtering on title
			if ($title && '_notitle' != $title) {
				if ($rel == $el->getAttribute('rel') && $title == $el->getAttribute('title')) {
					$this->links[$rel][$title] = $el->getAttribute('href');
					return $el->getAttribute('href');
				}
			} else {
				if ($rel == $el->getAttribute('rel')) {
					$this->links[$rel]['_notitle'] = $el->getAttribute('href');
					return $el->getAttribute('href');
				}
			}
		}
		//not found
		$this->links[$rel][$title] = 0;
	}

	function getRelatedLinks() 
	{
		$links = array();
		foreach ($this->getLinksByRel('related') as $link) {
			$links[] = $link;
		}
		return $links;
	}

	function setRights($text) 
	{
		if ($this->rights_is_set) {
			//throw new Dase_Atom_Exception('rights is already set');
			return false;
		} else {
			$this->rights_is_set = true;
			return $this->addElement('rights',$text);
		}
	}

	function getRights() 
	{
		return $this->getAtomElementText('rights');
	}

	function setTitle($text) 
	{
		if ($this->title_is_set) {
			//throw new Dase_Atom_Exception('title is already set');
			return false;
		} else {
			$this->title_is_set = true;
			return $this->addElement('title',$text);
		}
	}

	function getTitle() 
	{
		return $this->getAtomElementText('title');
	}

	function getAtomElementText($name,$ns_prefix='atom') 
	{
		//only works w/ simple string
		if ($this->root->getElementsByTagNameNS(Dase_Atom::$ns[$ns_prefix],$name)->item(0)) {
			return trim($this->root->getElementsByTagNameNS(Dase_Atom::$ns[$ns_prefix],$name)->item(0)->nodeValue);
		}
	}

	private function _initXpath() {
		if ($this->xpath_obj) {
			return $this->xpath_obj;
		} else {
			if ('DOMDocument' != get_class($this->dom)) {
				$c = get_class($this->dom);
				throw new Dase_Atom_Exception("xpath must be performed on DOMDocument, not $c");
			}
			$x = new DomXPath($this->dom);
			foreach (Dase_Atom::$ns as $k => $v) {
				$x->registerNamespace($k,$v);
			}
			$this->xpath_obj = $x;
			return $this->xpath_obj;
		}

	}

	function getXpathValue($xpath,$context_node = null) 
	{
		$x = $this->_initXpath();
		if ($context_node) {
			$it = $x->query($xpath,$context_node)->item(0);
		} else {
			$it = $x->query($xpath)->item(0);
		}
		if ($it) {
			return $it->nodeValue;
		}
	}

	/** client must evaluate resulting DOMNodeList */
	function getXpath($xpath,$context_node = null) 
	{
		$x = $this->_initXpath();
		if ($context_node) {
			return $x->query($xpath,$context_node);
		} else {
			return $x->query($xpath);
		}
	}

	function getUpdated() 
	{
		return $this->getAtomElementText('updated');
	}

	function setUpdated($text) 
	{
		if ($this->updated_is_set) {
			throw new Dase_Atom_Exception('updated is already set');
		} else {
			$this->updated_is_set = true;
		}
		$updated = $this->addElement('updated',$text);
	}

	function asXml($node=null) 
	{
		//format output
		$this->dom->formatOutput = true;
		if ($node) {
			return $this->dom->saveXML($node);
		} else {
			return $this->dom->saveXML();
		}
	}

	function getAsciiId()
	{
		//by convention, for entities that have an ascii id,
		//it will be the last segment of the atom:id
		return array_pop(explode('/',$this->getId()));
	}
}
