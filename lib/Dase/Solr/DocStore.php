<?php
Class Dase_Solr_DocStore 
{
	private $solr_base_url;
	private $solr_update_url;
	private $solr_version = '2.2';


	function __construct($db,$config) 
	{
		$this->solr_base_url = $config->getSearch('solr_base_url');
		$this->solr_update_url = $this->solr_base_url.'/update';;
		$this->db = $db;
		$this->config = $config;
	}

	public function storeItem($item)
	{
		//use solr search
		$solr = new Dase_Solr_Search($this->db,$this->config);
		//always commits
		return $solr->buildItemIndex($item);
	}

	public function deleteItem($item)
	{
		$delete_doc = '<delete><id>'.$item->getUnique().'</id></delete>';
		$resp = Dase_Http::post($this->solr_update_url,$delete_doc,null,null,'text/xml');
		Dase_Http::post($this->solr_update_url,'<commit/>',null,null,'text/xml');
		return $resp;
	}

	public function deleteCollection($coll)
	{
		$start = Dase_Util::getTime();
		$delete_doc = '<delete><query>c:'.$coll.'</query></delete>';
		$resp = Dase_Http::post($this->solr_update_url,$delete_doc,null,null,'text/xml');
		Dase_Http::post($this->solr_update_url,'<commit/>',null,null,'text/xml');
		$end = Dase_Util::getTime();
		$index_elapsed = round($end - $start,4);
		return $resp.' deleted '.$coll.' index: '.$index_elapsed;
	}

	public function commit()
	{
		return Dase_Http::post($this->solr_update_url,'<commit/>',null,null,'text/xml');
	}

	public function getTimestamp($item_unique)
	{
		$url = $this->solr_base_url."/select/?q=_id:".$item_unique."&version=".$this->solr_version;
		Dase_Log::debug(LOG_FILE,'SOLR ITEM RETRIEVE: '.$url);
		$res = file_get_contents($url);
		$dom = new DOMDocument('1.0','utf-8');
		$dom->loadXml($res);
		foreach ($dom->getElementsByTagName('date') as $el) {
			if ('timestamp' == $el->getAttribute('name')) {
				return $el->nodeValue;
			}
		}
	}

	public function getItemAtomJson($item_unique,$app_root)
	{
		$feed = Dase_Atom_Feed::load($this->getItem($item_unique,$app_root,true));
		return $feed->asJson();
	}

	public function getSolrResponse($item_unique)
	{
		$url = $this->solr_base_url."/select/?q=_id:".$item_unique."&version=".$this->solr_version;
		list($http_code,$res) = Dase_Http::get($url,null,null);
		if ('4' == substr($http_code,0,1) || '5' == substr($http_code,0,1)) {
			Dase_Log::debug(LOG_FILE,'SOLR ERROR :'.$res);
			return '<error/>';
		}
		return $res;
	}

	public function getItemJson($item_unique,$app_root)
	{
		$entry = '';
		$url = $this->solr_base_url."/select/?q=_id:".$item_unique."&version=".$this->solr_version;
		Dase_Log::debug(LOG_FILE,'SOLR ITEM RETRIEVE: '.$url);
		$res = file_get_contents($url);

		$reader = new XMLReader();
		$reader->XML($res);
		while ($reader->read()) {
			//get json 
			if ($reader->localName == "str" && $reader->nodeType == XMLReader::ELEMENT) {
				if ('_json' == $reader->getAttribute('name')) {
					$reader->read();
					$json = $reader->value;
				}
			}
		}
		$reader->close();
		$json = str_replace('_app_root','app_root',$json);
		$json = str_replace('{APP_ROOT}',$app_root,$json);
		return $json;
	}

	//auto-generates and inserts in store if item missing
	public function getItem($item_unique,$app_root,$as_feed=false,$restore=true)
	{
		$entry = '';
		$url = $this->solr_base_url."/select/?q=_id:".$item_unique."&version=".$this->solr_version;
		Dase_Log::debug(LOG_FILE,'SOLR ITEM RETRIEVE: '.$url);
		$res = file_get_contents($url);

		$reader = new XMLReader();
		$reader->XML($res);
		while ($reader->read()) {
			//get total number found
			if ($reader->localName == "result" && $reader->nodeType == XMLReader::ELEMENT) {
				$total = $reader->getAttribute('numFound');
			}
			//get entries
			if ($reader->localName == "str" && $reader->nodeType == XMLReader::ELEMENT) {
				if ('_atom' == $reader->getAttribute('name')) {
					$reader->read();
					$entry = $reader->value;
				}
			}
			if ($reader->localName == "date" && $reader->nodeType == XMLReader::ELEMENT) {
				if ('timestamp' == $reader->getAttribute('name')) {
					$reader->read();
					$timestamp = $reader->value;
				}
			}
		}
		$reader->close();

		//automatically regenerate missing item from db
		if (0 == $total) {
			if ($restore) {
				$item = Dase_DBO_Item::getByUnique($this->db,$item_unique);
				if ($item) {
					$this->storeItem($item);
					return $this->getItem($item_unique,$app_root,$as_feed,false);
				}
			} else {
				return false;
			}
		}

		$entry = Dase_Util::unhtmlspecialchars($entry);
		$entry = str_replace('{APP_ROOT}',$app_root,$entry);
		$added = <<<EOD
<category term="$timestamp" scheme="http://daseproject.org/category/indexed_timestamp"/>
EOD;
		$entry = str_replace('<author>',$added."\n  <author>",$entry);

		if ($as_feed) {
			$updated = date(DATE_ATOM);
			$id = 'tag:daseproject.org,'.date("Y-m-d").':'.Dase_Util::getUniqueName();
			$feed = <<<EOD
<feed xmlns="http://www.w3.org/2005/Atom"
	  xmlns:d="http://daseproject.org/ns/1.0">
  <author>
	<name>DASe (Digital Archive Services)</name>
	<uri>http://daseproject.org</uri>
	<email>admin@daseproject.org</email>
  </author>
  <title>DASe Item as Feed</title>
  <updated>$updated</updated>
  <category term="item" scheme="http://daseproject.org/category/feedtype"/>
  <id>$id</id>
  $entry
</feed>
EOD;
			return $feed;
		}
		return $entry;
	}
}


