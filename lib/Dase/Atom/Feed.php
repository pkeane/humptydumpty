<?php

/*** a minimal atom feed

<feed xmlns="http://www.w3.org/2005/Atom">
  <id>tag:daseproject.org,2008:temp</id>
  <author><name/></author>
  <title>title</title>
  <updated>2008-01-01T00:00:00Z</updated>
  <link href="http://daseproject.org/atom/entry/template.html"/>
</entry>

*********/

class Dase_Atom_Feed extends Dase_Atom 
{
	protected $_entries = array();
	protected $generator_is_set;
	protected $subtitle_is_set;
	private static $types_map = array(
		'archive' => array(
			'feed' => 'Dase_Atom_Feed_Collection',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'attribute' => array(
			'feed' => 'Dase_Atom_Feed_Collection',
			'entry' => 'Dase_Atom_Entry_Attribute',
		),
		'attributes' => array(
			'feed' => 'Dase_Atom_Feed_Collection',
			'entry' => 'Dase_Atom_Entry_Attribute',
		),
		'collection_list' => array(
			'feed' => 'Dase_Atom_Feed_CollectionList', 
			'entry' => 'Dase_Atom_Entry_Collection'
		),
		'collection' => array(
			'feed' => 'Dase_Atom_Feed_Collection',
			'entry' => 'Dase_Atom_Entry_Collection',
		),
		'item' => array(
			'feed' => 'Dase_Atom_Feed_Item',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'item_types' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry_ItemType',
		),
		'search' => array(
			'feed' => 'Dase_Atom_Feed_Search',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'none' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry',
		),
		'searchitem' => array(
			'feed' => 'Dase_Atom_Feed_Item',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'tag' => array(
			'feed' => 'Dase_Atom_Feed_Tag',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'tagitem' => array(
			'feed' => 'Dase_Atom_Feed_Item',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'attribute_values' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry',
		),
		'items' => array(
			'feed' => 'Dase_Atom_Feed_Collection',
			'entry' => 'Dase_Atom_Entry_Item',
		),
		'sets' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry_Set',
		),
		'user_list' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry_User',
		),
		'comments' => array(
			'feed' => 'Dase_Atom_Feed',
			'entry' => 'Dase_Atom_Entry_Comment',
		),
	);
	protected $feedtype;

	function __construct($dom = null)
	{
		if ($dom) {
			//reader object
			$this->root = $dom->documentElement;
			$this->dom = $dom;
		}  else {
			//creator object
			$dom = new DOMDocument('1.0','utf-8');
			$this->root = $dom->appendChild($dom->createElementNS(Dase_Atom::$ns['atom'],'feed'));
			$this->dom = $dom;
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
		curl_close($ch);

		//beware tight coupling to dase error text!!!
		if (0 === strpos($xml,'DASe Error')) {
			return false;
		}

		//for debugging
		if (isset($_GET['showfeed'])) {
			print $xml;
			exit;
		}
		$dom = new DOMDocument('1.0','utf-8');
		if (!$xml) {
			print "an error has occurred";
			exit;
		}
		//error entry
		if (!@ $dom->loadXML($xml)) {
			$entry = new Dase_Atom_Feed;
			$entry->setTitle($xml);
			print $entry->asXml();exit;
			return $entry;
		}
		return self::_init($dom);
	}

	public static function load($xml) {
		$dom = new DOMDocument('1.0','utf-8');
		$dom->load($xml);
		return self::_init($dom);
	}

	private static function _init($dom)
	{
		//reader object
		foreach ($dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/feedtype' == $el->getAttribute('scheme')) {
				$feedtype = $el->getAttribute('term');
				$class = self::$types_map[$feedtype]['feed'];
				if ($class) {
					$obj = new $class($dom);
					$obj->feedtype = $feedtype;
					return $obj;
				} else {
					$feed = new Dase_Atom_Feed($dom);
					$feed->feedtype = 'none';
					return $feed;
				}
			}
		}
		//in case no category element
		$feed = new Dase_Atom_Feed($dom);
		$feed->feedtype = 'none';
		return $feed;
	}

	function setFeedType($type) 
	{
		$this->addCategory($type,'http://daseproject.org/category/feedtype'); 
	}

	function getFeedType() 
	{
		foreach ($this->dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/feedtype' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function getError() 
	{
		foreach ($this->dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/error' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function addEntry($type = '')
	{
		if ($type && isset(Dase_Atom_Entry::$types_map[$type])) {
			$entry = new Dase_Atom_Entry::$types_map[$type]($this->dom);
			$entry->setEntryType($type);
		} else {
			$entry = new Dase_Atom_Entry($this->dom);
		}
		//entries will be appended in asXml method
		$this->_entries[] = $entry;
		return $entry;
	}

	function addItemEntry(Dase_DBO_Item $item,$app_root)
	{
		$dom = new DOMDocument('1.0','utf-8');
		$ds = new Dase_Solr_DocStore($item->db,$item->config);
		$xml = $ds->getItem($item->getUnique(),$app_root);
		if ($xml) {
			$dom->loadXml($xml);
			$e = $dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'entry');
			$root = $e->item(0);
			$root = $this->dom->importNode($root,true);
			$entry = new Dase_Atom_Entry_Item($this->dom,$root);
			$this->_entries[] = $entry;
			return $entry;
		}
	}

	function addItemEntryByItemUnique($db,$item_unique,$config,$app_root)
	{
		$dom = new DOMDocument('1.0','utf-8');
		$ds = new Dase_Solr_DocStore($db,$config);
		$xml = $ds->getItem($item_unique,$app_root);
		if ($xml) {
			$dom->loadXml($xml);
			$e = $dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'entry');
			$root = $e->item(0);
			$root = $this->dom->importNode($root,true);
			$entry = new Dase_Atom_Entry_Item($this->dom,$root);
			$this->_entries[] = $entry;
			return $entry;
		}
	}

	function setGenerator($text,$uri='',$version='')
	{
		if ($this->generator_is_set) {
			throw new Dase_Atom_Exception('generator is already set');
		} else {
			$this->generator_is_set = true;
		}
		$generator = $this->addElement('generator',$text);
		if ($uri) {
			$generator->setAttribute('uri',$uri);
		}
		if ($version) {
			$generator->setAttribute('version',$version);
		}
	}

	function setSubtitle($text='')
	{
		if ($this->subtitle_is_set) {
			throw new Dase_Atom_Exception('subtitle is already set');
		} else {
			$this->subtitle_is_set = true;
		}
		if ($text) {
			$subtitle = $this->addElement('subtitle',$text);
			$subtitle->setAttribute('type','text');
		} else {
			$subtitle = $this->addElement('subtitle');
			$subtitle->setAttribute('type','xhtml');
			//results in namespace prefixes which messes up some aggregators
			//return $this->addChildElement($subtitle,'xhtml:div','',Dase_Atom::$ns['h']);
			$div = $subtitle->appendChild($this->dom->createElement('div'));
			$div->setAttribute('xmlns',Dase_Atom::$ns['h']);
			return $div;
			//note that best practice here is to use simplexml 
			//to add subtitle to the returned div
		}
	}

	function setOpensearchTotalResults($num)
	{
		$this->addElement('totalResults',$num,Dase_Atom::$ns['opensearch']);
	}

	function setOpensearchStartIndex($num)
	{
		$this->addElement('startIndex',$num,Dase_Atom::$ns['opensearch']);
	}

	function setOpensearchItemsPerPage($num)
	{
		$this->addElement('itemsPerPage',$num,Dase_Atom::$ns['opensearch']);
	}

	function setOpensearchQuery($q)
	{
		$el = $this->addElement('Query',null,Dase_Atom::$ns['opensearch']);
		$el->setAttribute('role','request');
		$el->setAttribute('searchTerms',$q);
	}

	function getOpensearchTotal()
	{
		return $this->getXpathValue("opensearch:totalResults");
	}

	function getQuery()
	{
		return $this->getXpathValue("opensearch:Query/@searchTerms");
	}


	function attachEntries()
	{
		//attach entries
		if ($this->_entries) {
			foreach ($this->_entries as $entry) {
				$this->root->appendChild($entry->root);
			}
		}
	}

	function asXml()
	{
		$this->attachEntries();
		return parent::asXml();
	}

	public function filter($att,$val) 
	{
		$entries = array();
		foreach ($this->getEntries() as $entry) {
			foreach ($entry->getRawMetadata() as $att_ascii => $values) {
				if ($att == $att_ascii) {
					if (in_array($val,$values)) {
						$entries[] = $entry;
					}
				}
			} 
		}
		$this->_entries = $entries;
		return $this;
	}

	public function filterOnExists($att) 
	{
		$entries = array();
		foreach ($this->getEntries() as $entry) {
			foreach ($entry->getRawMetadata() as $att_ascii => $values) {
				if ($att == $att_ascii) {
					if (count($values)) {
						$entries[] = $entry;
					}
				}
			} 
		}
		$this->_entries = $entries;
		return $this;
	}

	/** should allow for csv output */
	public function asSimpleArray()
	{
		$atts = array();
		$set = array();
		$got_attributes = 0;
		foreach ($this->getEntries() as $entry) {
			$metadata = $entry->getMetadata();
			//base attribute set on first entry
			if (!$got_attributes) {
				foreach ($metadata as $att_ascii => $keyval) {
					$atts[] = $att_ascii;
				} 
				$set[] = $atts;
				$got_attributes = 1;
			}
			$item = array();
			foreach ($atts as  $att_ascii) {
				if (isset($metadata[$att_ascii])) {
					$item[$att_ascii] = $metadata[$att_ascii]['values'][0];
				} else {
					$item[$att_ascii] = '';
				}
			} 
			$set[] = $item;
		}
		return $set;
	}

	public function sortBy($att) 
	{
		$entries_deep = array();
		$entries = array();
		foreach ($this->getEntries() as $entry) {
			$entries_deep[$entry->getValue($att)][] = $entry;
		}
		ksort($entries_deep);
		foreach ($entries_deep as $k => $set) {
			foreach ($set as $e) {
				$entries[] = $e;
			}
		}
		$this->_entries = $entries;
		return $this;
	}

	public function sortByTitle() 
	{
		$entries = $this->getEntries();
		usort($entries,array('Dase_Atom_Feed','_sortByAtomTitle'));
		$this->_entries = $entries;
		return $this;
	}

	public static function _sortByAtomTitle($a,$b)
	{
		$at = $a->getTitle();
		$bt = $b->getTitle();
		return strnatcasecmp($at,$bt); 

	}

	public function sortByPublished()
	{
		$entries = $this->getEntries();
		usort($entries,array('Dase_Atom_Feed','_sortByEntryPublished'));
		$this->_entries = $entries;
		return $this;

	}

    public function sortBySortOrder(){
        $entries_deep = array();
        foreach ($this->getEntries() as $entry){
            foreach ($entry->getCategories() as $cat){
                if($cat['scheme'] == 'http://daseproject.org/category/sort_order'){
                    $entries_deep[$cat['term']][] = $entry;
                }
            }
        }
        ksort($entries_deep);
        foreach ($entries_deep as $k => $set) {
            foreach ($set as $e) {
                $entries[] = $e;
            }
        }
        $this->_entries = $entries;
        return $this;
    }

	public static function _sortByEntryPublished($a,$b)
	{
		$at = $a->getPublished();
		$bt = $b->getPublished();
		return strnatcasecmp($at,$bt);

	}

	public function getCount()
	{
		return count($this->getEntries());
	}

	protected function getEntries()
	{
		if (count($this->_entries)) {
			return $this->_entries;
		}
		if (!$this->feedtype) {
			$this->feedtype = $this->getFeedType();
			if (!$this->feedtype) {
				throw new Dase_Atom_Exception('cannot get feedtype');
			}
		}
		$class = self::$types_map[$this->feedtype]['entry'];
		$entries = array();
		foreach ($this->dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'entry') as $entry_dom) {
			foreach ($entry_dom->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
				if ('http://daseproject.org/category/entrytype' == $el->getAttribute('scheme')) {
					if (Dase_Atom_Entry::$types_map[$el->getAttribute('term')]) {
						$class = Dase_Atom_Entry::$types_map[$el->getAttribute('term')];
					}
				}
			}
			if ($class) {
				//entry subclass
				$entry = new $class($this->dom,$entry_dom);
			} else {
				$entry = new Dase_Atom_Entry($this->dom,$entry_dom);
			}
			$entries[] = $entry;
		}
		$this->_entries = $entries;
		return $entries;
	}

	protected function getSubtitle() {
		return $this->getAtomElementText('subtitle');
	}

	function asJson() 
	{
		//todo: all the opensearch methods
		$feed_array = array(
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'subtitle' => $this->getSubtitle(),
			'updated' => $this->getUpdated(),
			'feedtype' => $this->getFeedtype(),
			//	'author_name' => $this->getAuthorName(),
			'rights' => $this->getRights(),
			'category' => $this->getCategories(),
			'link' => $this->getLinks(),
		);
		foreach ($this->getEntries() as $entry) {
			$feed_array['entries'][] = $entry->asArray();
		}
		return Dase_Json::get($feed_array);
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
}
