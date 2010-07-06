<?php
class Dase_Atom_Entry_Item extends Dase_Atom_Entry
{
	protected $_collection;
	protected $_collectionAsciiId;
	protected $_metadata;
	protected $_metadata_links;

	function __construct($dom = null,$root = null)
	{
		parent::__construct($dom,$root,'item');
	}

	function getSerialNumber() 
	{
		if (!$this->serial_number) {
			//serial numbers are modelled the same way as 
			//ascii ids (last segment of id)
			$this->serial_number = $this->getAsciiId();
		}
		return $this->serial_number;
	}

	function getItemLink()
	{
		return $this->getLink('http://daseproject.org/relation/search-item');
	}

	function getEditAnnotationLink()
	{
		return $this->getLink('http://daseproject.org/relation/edit-annotation');
	}

	function getAttributesLink()
	{
		return $this->getLink('http://daseproject.org/relation/attributes');
	}

	function getMetadataLink()
	{
		return $this->getLink('http://daseproject.org/relation/metadata');
	}

	//not yet used???
	function getDescription()
	{
		return $this->getAtomElementText('summary');
	}

	function getViewitemLink()
	{
		$x = new DomXPath($this->dom);
		$x->registerNamespace('media',Dase_Atom::$ns['media']);
		$x->registerNamespace('atom',Dase_Atom::$ns['atom']);
		$elem =  $x->query("media:content/media:category[. = 'viewitem']",$this->root)->item(0)->parentNode;
		if ($elem) {
			return $elem->getAttribute('url');
		} else {
			return 'www/images/default.gif';
		}
	}

	function getThumbnailLink()
	{
		$elem = $this->root->getElementsByTagNameNS(Dase_Atom::$ns['media'],'thumbnail')->item(0);
		if ($elem) {
			return $elem->getAttribute('url');
		} else {
			return 'www/images/default.gif';
		}
	}

	function getThumbnailBase64()
	{
		$elem = $this->root->getElementsByTagNameNS(Dase_Atom::$ns['media'],'thumbnail')->item(0);
		if ($elem) {
			return base64_encode(file_get_contents($elem->getAttribute('url')));
		}
	}

	function getViewitemBase64()
	{
		$x = new DomXPath($this->dom);
		$x->registerNamespace('media',Dase_Atom::$ns['media']);
		$x->registerNamespace('atom',Dase_Atom::$ns['atom']);
		$elem =  $x->query("media:content/media:category[. = 'viewitem']",$this->root)->item(0)->parentNode;
		if ($elem) {
			return base64_encode(file_get_contents($elem->getAttribute('url')));
		}
	}

	/** only applies to tag_item */ 
	public function getTagItemId() {
		return $this->getCategoryTerm('http://daseproject.org/category/tag_item_id');
	}

	public function getLabel($att) 
	{
		//look in three places
		foreach ($this->getCategoriesByScheme('http://daseproject.org/category/metadata') as $cat) {
			if ($att == $cat['term']) {
				return $cat['label'];
			}
		}
		foreach ($this->getCategoriesByScheme('http://daseproject.org/category/private_metadata') as $cat) {
			if ($att == $cat['term']) {
				return $cat['label'];
			}
		}
		foreach ($this->getCategoriesByScheme('http://daseproject.org/category/admin_metadata') as $cat) {
			if ($att == $cat['term']) {
				return $cat['label'];
			}
		}
	}

	function getThumbnailDimension($dimension)
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['media'],'thumbnail') as $el) {
			return $el->getAttribute($dimension);
		}
	}

	function getMedia() {
		$media_array = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['media'],'content') as $el) {
			$file['href'] = $el->getAttribute('url');
			$file['type'] = $el->getAttribute('type');
			$file['width'] = $el->getAttribute('width');
			$file['height'] = $el->getAttribute('height');
			$bytes = $el->getAttribute('fileSize');
			$kilobytes = round((int) $bytes/1000,2);
			$file['fileSize'] = $kilobytes;
			$file['label'] = $el->getElementsByTagName('category')->item(0)->nodeValue;
			$media_array[] = $file;
		}
		return $media_array;
	}

	function selectMedia($size) 
	{
		$x = new DomXPath($this->dom);
		$x->registerNamespace('media',Dase_Atom::$ns['media']);
		$x->registerNamespace('atom',Dase_Atom::$ns['atom']);
		$elem =  $x->query("media:content/media:category[. = '$size']",$this->root)->item(0)->parentNode;
		if ($elem) {
			return $elem->getAttribute('url');
		}
	}

	function getStatus()
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/status' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function setStatus($status='public')
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/status' == $el->getAttribute('scheme')) {
				$el->setAttribute('term',$status);
			}
		}
	}

	function getItemType()
	{
		$item_type['label'] = '';
		$item_type['term'] = '';
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/item_type' == $el->getAttribute('scheme')) {
				//note we get label here!
				$item_type['label'] =  $el->getAttribute('label');
				$item_type['term'] =  $el->getAttribute('term');
				break;
			}
		}
		return $item_type;
	}

	function getCollection()
	{
		if (!$this->_collection) {
			foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
				if ('http://daseproject.org/category/collection' == $el->getAttribute('scheme')) {
					$this->_collection =  $el->getAttribute('label');
					break;
				}
			}
		}
		return $this->_collection;
	}

	function getPosition()
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/position' == $el->getAttribute('scheme')) {
				return $el->getAttribute('term');
			}
		}
	}

	function getCollectionAsciiId()
	{
		if (!$this->_collectionAsciiId) {
			foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
				if ('http://daseproject.org/category/collection' == $el->getAttribute('scheme')) {
					$this->_collectionAsciiId = $el->getAttribute('term');
					break;
				}
			}
		}
		return $this->_collectionAsciiId;
	}


	function getUnique()
	{
		return $this->getCollectionAsciiId().'/'.$this->getSerialNumber();
	}

	function replaceMetadata($metadata_array) {
		//metadata array is expected to have same structure as getRawMetadata 
		// $m[att_ascii_id] = array of values
		$doomed = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/metadata' == $el->getAttribute('scheme')) {
				$doomed[] = $el;
			}
			if ('http://daseproject.org/category/private_metadata' == $el->getAttribute('scheme')) {
				$doomed[] = $el;
			}
		}
		foreach ($doomed as $goner) {
			$this->root->removeChild($goner);
		}
		foreach ($metadata_array as $k => $v) {
			foreach ($v as $value) {
				$this->addCategory($k,'http://daseproject.org/category/metadata','',$value);
			}
		}
	}

	function addMetadata($att_ascii_id,$value_text)
	{
		if ($value_text && $att_ascii_id) {
			$this->addCategory($att_ascii_id,'http://daseproject.org/category/metadata','',$value_text);
		}
	}

	function setItemType($type_ascii_id,$type_name='')
	{
		$this->addCategory($type_ascii_id,'http://daseproject.org/category/item_type',$type_name);
	}

	function getMetadataLinks($att='') 
	{
		if (count($this->_metadata_links)) {
			if ($att) {
				if (isset($this->_metadata_links[$att])) {
					return $this->_metadata_links[$att];
				} else {
					return false;
				}
			}
			return $this->_metadata_links;
		}
		$metadata = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			if (0 === strpos($el->getAttribute('rel'),'http://daseproject.org/relation/metadata-link')) {
				$v = array();
				$set = explode('/',str_replace('http://daseproject.org/relation/metadata-link/','',$el->getAttribute('rel')));
				if (2 == count($set)) {
					$coll = $set[0];
					$att_ascii_id = $set[1];
					$metadata[$att_ascii_id]['attribute_name'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'attribute');
					$v['text'] = $el->getAttribute('title');
					$v['url'] = $el->getAttribute('href');
					$v['coll'] = $coll;
					$v['edit'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'edit-id');
					$v['mod'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'mod');
					$v['modtype'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'modtype');
					$metadata[$att_ascii_id]['values'][] = $v;
					//easy access to first value
					if (1 == count($metadata[$att_ascii_id]['values'])) {
						$metadata[$att_ascii_id]['text'] = $v['text'];
						$metadata[$att_ascii_id]['url'] = $v['url'];
						$metadata[$att_ascii_id]['coll'] = $v['coll'];
					}
				}
			}
		}
		$this->_metadata_links = $metadata;
		if ($att) {
			if (isset($metadata[$att])) {
				return $metadata[$att];
			} else {
				return false;
			}
		}
		return $metadata;
	}

    function removeMetadataLinks(){
        foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
            if (0 === strpos($el->getAttribute('rel'),'http://daseproject.org/relation/metadata-link')) {
                    $doomed[] = $el;
            }
        }
        foreach($doomed as $goner){
            $this->root->removeChild($goner);
        }
    }

	function removeMetadataLink($href){
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			if (0 === strpos($el->getAttribute('rel'),'http://daseproject.org/relation/metadata-link')) {
				if(0 === strpos($el->getAttribute('href'),$href)){
					$doomed[] = $el;
				}
			}
		}
		foreach($doomed as $goner){
			$this->root->removeChild($goner);
		}
	}

    function removeMetadataLinkByRel($rel){
        foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
            if (0 === strpos($el->getAttribute('rel'),'http://daseproject.org/relation/metadata-link')) {
                if(strpos($el->getAttribute('rel'),$rel) !== false){
                    $doomed[] = $el;
                }
            }
        }
        foreach($doomed as $goner){
            $this->root->removeChild($goner);
        }
    }

	function getMetadata($att = '',$include_private_metadata=false) 
	{
		if (count($this->_metadata)) {
			if ($att) {
				if (isset($this->_metadata[$att])) {
					return $this->_metadata[$att];
				} else {
					return false;
				}
			}
			return $this->_metadata;
		}
		$metadata = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/metadata' == $el->getAttribute('scheme')) {
				$v = array();
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id]['attribute_name'] = $el->getAttribute('label');
				$v['edit'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'edit-id');
				$v['id'] = array_pop(explode('/',$v['edit'])); //provides the last segment, i.e. value id
				$v['text'] = $el->nodeValue;
				$v['mod'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'mod');
				$v['modtype'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'modtype');
				$metadata[$att_ascii_id]['values'][] = $v;
				//easy access to first value
				if (1 == count($metadata[$att_ascii_id]['values'])) {
					$metadata[$att_ascii_id]['text'] = $v['text'];
					$metadata[$att_ascii_id]['edit'] = $v['edit'];
					$metadata[$att_ascii_id]['id'] = $v['id'];
				}
			}
			if ($include_private_metadata &&
				'http://daseproject.org/category/private_metadata' == $el->getAttribute('scheme')) {
					$att_ascii_id = $el->getAttribute('term');
					$metadata[$att_ascii_id]['attribute_name'] = $el->getAttribute('label');
					$v['edit'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'edit-id');
					$v['id'] = array_pop(explode('/',$v['edit'])); //provides the last segment, i.e. value id
					$v['text'] = $el->nodeValue;
					$v['mod'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'mod');
					$v['modtype'] = $el->getAttributeNS(Dase_Atom::$ns['d'],'modtype');
					$metadata[$att_ascii_id]['values'][] = $v;
					//easy access to first value
					if (1 == count($metadata[$att_ascii_id]['values'])) {
						$metadata[$att_ascii_id]['text'] = $v['text'];
						$metadata[$att_ascii_id]['edit'] = $v['edit'];
						$metadata[$att_ascii_id]['id'] = $v['id'];
					}
				}
		}
		$this->_metadata = $metadata;
		if ($att) {
			if (isset($metadata[$att])) {
				return $metadata[$att];
			} else {
				return false;
			}
		}
		return $metadata;
	}

	function getValue($att_ascii_id)
	{
		//does NOT return modifier
		$v = $this->getMetadata($att_ascii_id,true);
		if (isset($v['text'])) {
			return $v['text'];
		} else {
			return false;
		}
	}

	/** unlike getMetadata, here we do not break down
	 * into text/edit/id
	 * it is used in Feed class to do filtering
	 ************************************************/
	function getRawMetadata($att = '') 
	{
		$metadata = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/metadata' == $el->getAttribute('scheme')) {
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id][] = $el->nodeValue;
			}
			if ('http://daseproject.org/category/private_metadata' == $el->getAttribute('scheme')) {
					$att_ascii_id = $el->getAttribute('term');
					$metadata[$att_ascii_id][] = $el->nodeValue;
				}
		}
		if ($att) {
			//any easy way to get one value
			if (isset($metadata[$att]) && count($metadata[$att])) {
				return $metadata[$att][0];
			} else {
				return false;
			}
		}
		return $metadata;
	}

	function getAllMetadata($att = '') 
	{
		$metadata = array();
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/metadata' == $el->getAttribute('scheme')) {
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id][] = $el->nodeValue;
			}
			if ('http://daseproject.org/category/private_metadata' == $el->getAttribute('scheme')) {
					$att_ascii_id = $el->getAttribute('term');
					$metadata[$att_ascii_id][] = $el->nodeValue;
				}
		}
		$count = 0;
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'link') as $el) {
			if (strstr($el->getAttribute('rel'),'http://daseproject.org/relation/metadata-link') !== false) {
				$rel = $el->getAttribute('rel');
				$att_ascii_id = substr($rel,strrpos($rel,'/')+1);
				$metadata[$att_ascii_id][$count]['text'] = $el->getAttribute('title');
				$metadata[$att_ascii_id][$count]['url'] = $el->getAttribute('href');
				$count++;
			}
		}
				

		if ($att) {
			//any easy way to get one value
			if (isset($metadata[$att]) && count($metadata[$att])) {
				return $metadata[$att][0];
			} else {
				return false;
			}
		}
		return $metadata;
	}

	function getAdminMetadata() 
	{
		foreach ($this->root->getElementsByTagNameNS(Dase_Atom::$ns['atom'],'category') as $el) {
			if ('http://daseproject.org/category/admin_metadata' == $el->getAttribute('scheme')) {
				$att_ascii_id = $el->getAttribute('term');
				$metadata[$att_ascii_id]['attribute_name'] = $el->getAttribute('label');
				$metadata[$att_ascii_id]['values'][] = array('text' => $el->nodeValue);
			}
		}
		return $metadata;
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

	function insert($db,$r,$fetch_enclosure=false) 
	{
		$user = $r->getUser('http');
		//allows service user to  override created_by_eid 
		$author = $this->getAuthorName();
		if ($user->is_serviceuser && $author) {
			$created_by_eid = $author;
		} else {
			$created_by_eid = $user->eid;
		}
		$c = Dase_DBO_Collection::get($db,$r->get('collection_ascii_id'));
		if (!$c) { return; }
		$sn = Dase_Util::makeSerialNumber($r->slug);
		$item = $c->createNewItem($sn,$created_by_eid);
		foreach ($this->getMetadata() as $att => $keyval) {
			//creates atribute if it doesn't exist!
			Dase_DBO_Attribute::findOrCreate($db,$c->ascii_id,$att);
			foreach ($keyval['values'] as $v) {
				if (trim($v['text'])) {
					$val = $item->setValue($att,$v['text'],null,$v['mod']);
				}
			}
		}

		foreach ($this->getMetadataLinks() as $att => $keyval) {
			Dase_DBO_Attribute::findOrCreate($db,$c->ascii_id,$att);
			foreach ($keyval['values'] as $v) {
				if (trim($v['text'])) {
					//check that it's proper collection
					if ($c->ascii_id = $v['coll']) {
						//don't index just yet (the false param)
						$val = $item->setValueLink($att,$v['text'],$v['url'],$v['mod'],false);
					}
				}
			}
		}

		//item_type
		$item_type = $this->getItemType(); 
		if ($item_type['term']) {
			$item->setItemType($item_type['term']);
		}

		//content
		if ($this->getContent()) {
			$item->setContent($this->getContent(),$eid,$this->getContentType());
		}

		//$item->setValue('title',$this->getTitle());
		//$item->setValue('description',$this->getSummary());

		if ($fetch_enclosure) {
			$enc = $this->getEnclosure(); 
			if ($enc) {
				$upload_dir = MEDIA_DIR.'/'.$c->ascii_id.'/uploaded_files';
				if (!file_exists($upload_dir)) {
					$r->renderError(401,'missing upload directory');
				}
				$ext = Dase_File::$types_map[$enc['type']]['ext'];
				$new_file = $upload_dir.'/'.$item->serial_number.'.'.$ext;
				file_put_contents($new_file,file_get_contents($enc['href']));

				try {
					$file = Dase_File::newFile($db,$new_file,$enc['mime_type']);
					$media_file = $file->addToCollection($item,false,MEDIA_DIR);
				} catch(Exception $e) {
					$r->renderError(500,'could not ingest enclosure file ('.$e->getMessage().')');
				}
			}
		} 
		//messy
		$item->expireCaches($r->getCache());
		$item->buildSearchIndex();
		return $item;
	}

	/** used w/ PUT request -- affects categories:
	 *  1. deletes and replaces status 
	 *  2. deletes and replaces item_type
	 *  3. deletes and replaces all metadata & private metadata (NOT admin metadata)
	 */
	function update($db,$r) 
	{
		$eid = $r->getUser()->eid;
		$sernum = $this->getSerialNumber();
		$c = Dase_DBO_Collection::get($db,$r->get('collection_ascii_id'));
		if (!$c) { return; }
		$item = Dase_DBO_Item::get($db,$c->ascii_id,$sernum);
		if (!$item) {
			$item = $c->createNewItem($sernum);
		}
		$item->updated = date(DATE_ATOM);

		//1. status
		$status = $this->getStatus();
		//Dase_Log::debug(LOG_FILE,'--------status----------------'.$status);
		//Dase_Log::debug(LOG_FILE,'--------status----------------'.$item->status);
		if (($status != $item->status) && in_array($status,array('delete','draft','public','archive'))) {
			$item->status = $status;
		}

		$item->update();

		//2. item_type
		//note that this *updates* db about item_type!
		$item_type = $this->getItemType(); 
		$orig_type = $item->getItemType();
		if (!$item_type['term'] || 'default' == $item_type['term']) {
			$item->setItemType();
		} elseif ($orig_type->ascii_id != $item_type['term']) {
			$item->setItemType($item_type['term']);
		} else {
			//nothin'
		}

		//3. metadata
		//only deletes collection metadata (not admin) metadata
		//then replaces it
		$item->deleteValues();
		foreach ($this->getMetadata(null,true) as $att => $keyval) {
			foreach ($keyval['values'] as $v) {
				if (trim($v['text'])) {
					$val = $item->setValue($att,$v['text'],null,$v['mod'],false);
				}
			}
		}

		foreach ($this->getMetadataLinks() as $att => $keyval) {
			foreach ($keyval['values'] as $v) {
				if (trim($v['text'])) {
					//check that it's proper collection
					if ($c->ascii_id = $v['coll']) {
						$val = $item->setValueLink($att,$v['text'],$v['url'],$v['mod'],false);
					}
				}
			}
		}

		//3.5 content!
		if ($this->getContent()) {
			$item->setContent($this->getContent(),$eid,$this->getContentType(),false);
		}

		//Here we allow the client to add 'no_commit=1' to trigger no commit
		if ('1' == $r->get('no_commit')) {
			$item->buildSearchIndex(false);
		} else {
			$item->buildSearchIndex();
		}
		return $item;
	}

	function addMetadataLink($href,$rel='',$type='',$length='',$title='',$dattribute='') 
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
		if ($dattribute){
			$link->setAttributeNS(Dase_Atom::$ns['d'],'d:attribute',$dattribute);
		}
		return $link;
	}


	function addLinks($db,$r) 
	{
		$eid = $r->getUser()->eid;
		$sernum = $this->getSerialNumber();

		$c = Dase_DBO_Collection::get($db,$r->get('collection_ascii_id'));
		if (!$c) { return; }

		$item = Dase_DBO_Item::get($db,$c->ascii_id,$sernum);
		if (!$item) { return; }

		$item->updated = date(DATE_ATOM);
		$item->update();

		foreach ($this->getMetadataLinks() as $att => $keyval) {
			foreach ($keyval['values'] as $v) {
				if (trim($v['text'])) {
					//check that it's proper collection
					if ($c->ascii_id = $v['coll']) {
						$val = $item->setValueLink($att,$v['text'],$v['url'],$v['mod']);
					}
				}
			}
		}

		$item->buildSearchIndex();
		return $item;
	}

	function __get($var) 
	{
		//allows smarty to invoke function as if getter
		$classname = get_class($this);
		//strips initial underscore
		$method = 'get'.ucfirst(substr($var,1));
		//for arrow get must be prefixed w/ underscore
		//if it is an atom element, since arrow gets
		//are used for retrieving metadata on entry items
		if ('_'== substr($var,0,1) && method_exists($classname,$method)) {
			return $this->{$method}();
		} elseif ($this->getMetadata($var)) {
			return $this->getMetadata($var);
        } elseif ($this->getMetadataLinks($var)){
            return $this->getMetadataLinks($var);
        } else {
			return parent::__get($var);
		}
	}
}
