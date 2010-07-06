<?php

/*** a minimal atom entry
 
<entry xmlns="http://www.w3.org/2005/Atom">
  <id>tag:daseproject.org,2008:temp</id>
  <author><name/></author>
  <title>title</title>
  <updated>2008-01-01T00:00:00Z</updated>
  <link href="http://daseproject.org/atom/entry/template.html"/>
</entry>

what google uses as post example:

<entry xmlns='http://www.w3.org/2005/Atom'>
 <author>
  <name>Elizabeth Bennet</name>
  <email>liz@gmail.com</email>
 </author>
 <title type='text'>Entry 1</title>
 <content type='text'>This is my entry</content>
</entry>

*********/

class Dase_Atom_Entry extends Dase_Atom
{
	protected $edited_is_set;
	protected $content_is_set;
	protected $published_is_set;
	protected $source_is_set;
	protected $summary_is_set;
	protected $entrytype;
	public static $types_map = array(
		'attribute' => 'Dase_Atom_Entry_Attribute',
		'collection' => 'Dase_Atom_Entry_Collection',
		'comment' => 'Dase_Atom_Entry_Comment',
		'item' => 'Dase_Atom_Entry_Item',
		'item_type' => 'Dase_Atom_Entry_ItemType',
		'set' => 'Dase_Atom_Entry_Set',
		'user' => 'Dase_Atom_Entry_User',
	);

	//note: dom is the dom object and root is the root
	//element of the document.  If this entry is part of
	//a feed, then the root means the root of the feed. If
	//this is a free-standing entry document, it means the
	//'entry' element

	function __construct(DOMDocument $dom=null,DOMElement $root=null,$entrytype=null)
	{
		if (!$dom && $root) {
			throw new Dase_Atom_Exception('dom doc needs to be passed into constructor with domnode');
		}
		if ($dom) {
			$this->dom = $dom;
			if ($root) {
				$this->root = $root;
			} else {
				$this->root = $dom->createElementNS(Dase_Atom::$ns['atom'],'entry');
			}
		} else { //no dom & no root
			//creator object (standalone entry document)
			$dom = new DOMDocument('1.0','utf-8');
			$this->root = $dom->appendChild($dom->createElementNS(Dase_Atom::$ns['atom'],'entry'));
			$this->dom = $dom;
		}
		if ($entrytype) {
			$this->setEntrytype($entrytype);
		}
	}

	public static function retrieve($url,$user='',$pwd='') 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

		//do not need to verify certificate
		//from http://blog.taragana.com/index.php/archive/how-to-use-curl-in-php-for-authentication-and-ssl-communication/
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//this will NOT work in safemode
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		if ($user && $pwd) {
			curl_setopt($ch, CURLOPT_USERPWD,"$user:$pwd");
		}
		$xml = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ('200' == $info['http_code']) {
			return self::load($xml);
		} else {
			//throw exception??
			return $info['http_code'];
		}
	}

	public static function load($xml,$force_type='') 
	{
		//reader object
		$dom = new DOMDocument('1.0','utf-8');
		if (is_file($xml)) {
			if (!$dom->load($xml)) {
				throw new Dase_Atom_Exception('bad xml');
			}
		} else {
			if (!$dom->loadXml($xml)) {
				Dase_Log::debug(LOG_FILE,"bad xml:\n ".$xml);
				throw new Dase_Atom_Exception('bad xml -- see log');
			}
		}
		$root = $dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'*')->item(0);
		if ('entry' != $root->localName) {
			throw new Dase_Atom_Exception('wrong document type '.$root->localName);
		}
		$entrytype = '';
		foreach ($dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/entrytype' == $el->getAttribute('scheme')) {
				$entrytype = $el->getAttribute('term');
				break;
			}
		}
		if ($force_type) {
			$entrytype = $force_type;
		}
		//todo: clean up this logic
		if (isset($entrytype) && isset(self::$types_map[$entrytype])) {
			$class = self::$types_map[$entrytype];
			if ($class) {
				$obj = new $class($dom,$root);
				$obj->entrytype = $entrytype;
				return $obj;
			} else {
				$entry = new Dase_Atom_Entry($dom,$root);
				$entry->entrytype = 'none';
				return $entry;
			}
		} else {
			$entry = new Dase_Atom_Entry($dom);
			$entry->entrytype = 'none';
			return $entry;
		}
	}

	public function putToUrl($url,$user,$pwd)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->asXml());
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pwd);
		$str  = array(
			"Content-Type: application/atom+xml;type=entry"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $str);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);  
		if ('200' == $info['http_code']) {
			return 'ok';
		} else {
			return $result;
		}
	}

	public function delete($user,$pwd)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->getEditLink());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pwd);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);  
		if ('200' == $info['http_code']) {
			return 'ok';
		} else {
			return $result;
		}
	}

	public function postToUrl($url,$user,$pwd,$slug='')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->asXml());
		curl_setopt($ch, CURLOPT_USERPWD,$user.':'.$pwd);
		$str  = array(
			"Content-Type: application/atom+xml;type=entry",
			"Slug: $slug"
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $str);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);  
		if ('201' == $info['http_code']) {
			$loc = array(); 
			preg_match('/Location: ([^\n]*)/im', $result, $loc); 
			if (count($loc) > 1) {
				return $loc[1];
			}
			return 'ok';
		} else {
			return $result;
		}
	}

	function __get($var) 
	{
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		} else {
			return parent::__get($var);
		}
	}

	function setExternalContent($url,$mime_type)
	{
		$content = $this->addElement('content');
		$content->setAttribute('src',$url);
		$content->setAttribute('type',$mime_type);
	}

	function setContentXml($xml,$type)
	{
		if ($this->content_is_set) {
			throw new Dase_Atom_Exception('content is already set');
		} else {
			$this->content_is_set = true;
		}
		$content = $this->addElement('content');
		$content->setAttribute('type',$type);
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($xml);
		$inner = $this->dom->importNode($dom->getElementsByTagName('*')->item(0),true);
		$content->appendChild($inner);
	}

	function setContent($text='',$type='text')
	{
		if ($this->content_is_set) {
			throw new Dase_Atom_Exception('content is already set');
		} else {
			$this->content_is_set = true;
		}
		if ($text) {
			if ('html' == $type) {
				//$content = $this->addElement('content',htmlentities($text,ENT_COMPAT,'UTF-8'));
				$content = $this->addElement('content',$text);
				$content->setAttribute('type','html');
			} elseif ('xhtml' == $type) {
				$content = $this->addElement('content');
				$content->setAttribute('type','xhtml');
				$div = $content->appendChild($this->dom->createElement('div'));
				$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
				$div->appendChild($this->dom->createTextNode($text));
			} else {
				$content = $this->addElement('content',$text);
				$content->setAttribute('type',$type);
			}
		} else {
			$content = $this->addElement('content');
			$content->setAttribute('type','xhtml');
			//results in namespace prefixes which messes up some aggregators
			//return $this->addChildElement($content,'xhtml:div','',Dase_Atom::$ns['xhtml']);
			$div = $content->appendChild($this->dom->createElement('div'));
			$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
			return $div;
			//note that best practice here is to use simplexml 
			//to add content to the returned div
		}
	}

	function replaceContent($text='',$type='text')
	{
		$content = $this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'content')->item(0);
		if ($content) {
			$this->root->removeChild($content);
		}
		$this->content_is_set = true;
		if ($text) {
			if ('html' == $type) {
				$content = $this->addElement('content',htmlentities($text,ENT_COMPAT,'UTF-8'));
				$content->setAttribute('type','html');
			} elseif ('xhtml' == $type) {
				$content = $this->addElement('content');
				$content->setAttribute('type','xhtml');
				$div = $content->appendChild($this->dom->createElement('div'));
				$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
				$div->appendChild($this->dom->createTextNode($text));
			} else {
				$content = $this->addElement('content',$text);
				$content->setAttribute('type',$type);
			}
		} else {
			$content = $this->addElement('content');
			$content->setAttribute('type','xhtml');
			//results in namespace prefixes which messes up some aggregators
			//return $this->addChildElement($content,'xhtml:div','',Dase_Atom::$ns['xhtml']);
			$div = $content->appendChild($this->dom->createElement('div'));
			$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
			return $div;
			//note that best practice here is to use simplexml 
			//to add content to the returned div
		}
	}

	function setMediaContent($url,$mime) 
	{
		if ($this->content_is_set) {
			throw new Dase_Atom_Exception('content is already set');
		} else {
			$this->content_is_set = true;
		}
		$content = $this->addElement('content');
		$content->setAttribute('src',$url);
		$content->setAttribute('type',$mime);
	}

	function getGoogleMetadata() {
		$metadata = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['gsx'],'*') as $dd) {
			$metadata[$dd->localName]['attribute_name'] = $dd->localName;
			$metadata[$dd->localName]['values'][] = $dd->nodeValue;
		}
		return $metadata;
	}

	function setPublished($text)
	{
		if ($this->published_is_set) {
			throw new Dase_Atom_Exception('published is already set');
		} else {
			$this->published_is_set = true;
		}
		$published = $this->addElement('published',$text);
	}

	function getPublished()
	{
		return $this->getAtomElementText('published');
	}

	function setSource()
	{
		if ($this->source_is_set) {
			throw new Dase_Atom_Exception('source is already set');
		} else {
			$this->source_is_set = true;
		}
		//finish!!!!!!!!!!
	}

	function setSummary($text,$type='',$replace = true)
	{
		//todo: clean up this logic
		if ($replace) {
			if ($text) {
				$sum = $this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'summary')->item(0);
				if ($sum) {
					$this->root->removeChild($sum);
				}
			}
		} else {
			if ($this->summary_is_set) {
				throw new Dase_Atom_Exception('summary is already set');
			} else {
				$this->summary_is_set = true;
			}
		}
		//note that sending empty text deletes summary
		if ($text) {
			if ('html' == $type) {
				$summary = $this->addElement('summary',htmlentities($text,ENT_COMPAT,'UTF-8'));
				$summary->setAttribute('type','html');
			} else {
				$summary = $this->addElement('summary',$text);
			}
		} else {
			$this->summary_is_set = false;
		}
	}

	/** we use the summary element to embed thumbnail */
	function setThumbnail($url)
	{
		if ($this->summary_is_set) {
			return;
		}
		$summary = $this->addElement('summary');
		$summary->setAttribute('type','xhtml');
		$div = $summary->appendChild($this->dom->createElement('div'));
		$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
		$thumbnail = $div->appendChild($this->dom->createElement('img'));
		$thumbnail->setAttribute('src',$url);
		$this->summary_is_set = true;
	}

	function getSummary() 
	{
		return $this->getAtomElementText('summary');
	}

	function getSummaryType() 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'summary') as $el) {
			return $el->getAttribute('type');
		}
	}

	function getContent() 
	{
		return $this->getAtomElementText('content');
	}

	function getContentSrc() 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'content') as $el) {
			return $el->getAttribute('src');
		}
	}

	function getContentType() 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'content') as $el) {
			return $el->getAttribute('type');
		}
	}

	function getContentXmlNode() 
	{
		return $this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'content')->item(0)->getElementsByTagName('*')->item(0);
	}

	function setEntrytype($type) 
	{
		if (!$this->getEntrytype) {
			$this->addCategory($type,'http://daseproject.org/category/entrytype'); 
		}
	}

	function getEntrytype() 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/entrytype' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function setEdited($dateTime)
	{
		if ($this->edited_is_set) {
			throw new Dase_Atom_Exception('edited is already set');
		} else {
			$this->edited_is_set = true;
		}
		$edited = $this->addElement('app:edited',$dateTime,Dase_Atom::$ns['app']);
	}

	function getEditLink()
	{
		return $this->getLink('edit');
	}

	function getEditContentLink()
	{
		return $this->getLink('http://daseproject.org/relation/edit-content');
	}

	function getAlternateLink()
	{
		//returns first on found
		return $this->getLink('alternate');
	}

	function getJsonEditLink()
	{
		//maybe filter by @type ??
		return $this->getLink('http://daseproject.org/relation/edit');
	}

	function getEnclosure() 
	{
		//note: only gets one!!!
		foreach ($this->getLinksByRel('enclosure') as $link) {
			return $link;
		}
		return false;
	}

	function addInReplyTo($ref,$type,$href,$source='')
	{
		$irt = $this->addElement('thr:in-reply-to',null,Dase_Atom::$ns['thr']);
		$irt->setAttribute('ref',$ref);
		$irt->setAttribute('type',$type);
		$irt->setAttribute('href',$href);
		if ($source) {
			$irt->setAttribute('source',$source);
		}
		return $irt;	
	}

	function asArray() 
	{
		$atom_array = array(
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'updated' => $this->getUpdated(),
			'entrytype' => $this->getEntrytype(),
			'author_name' => $this->getAuthorName(),
			'summary' => $this->getSummary(),
			'rights' => $this->getRights(),
			'category' => $this->getCategories(),
			'link' => $this->getLinks(),
			'content' => array(
				'type' => $this->getContentType(),
				'text' => $this->getContent(),
			),
		);
		return $atom_array;
	}

	function asJson() 
	{
		return Dase_Json::get($this->asArray());
	}


	//todo: pass in @rel for these:

	function setInlineFeed($feed,$rel){
		$link = $this->addLink($feed->getId(),$rel,'application/atom+xml');
		$inline = $this->addChildElement($link,'ae:inline','',Dase_Atom::$ns['ae']);
		$inline->appendChild($this->dom->importNode($feed->root,true));
	}

	function getInlineFeed($rel){
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			//if($el->getAttribute('rel') == 'http://daseproject.org/relation/order_items'){
			if ($el->getAttribute('rel') == $rel){
				$dom = new DOMDocument('1.0','utf-8');
				$dom->appendChild($dom->importNode($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'feed')->item(0),true));
				return new Dase_Atom_Feed($dom);
			}
		}
		return false;
	}

}
