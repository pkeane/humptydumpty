<?php
class Dase_Atom_Service_Collection extends Dase_Atom_Service
{
	function __construct($dom,$url,$title)
	{
		$this->root = $dom->createElement('collection');
		$this->root->setAttribute('href',$url);
		$elem = $this->root->appendChild($dom->appendChild($dom->createElementNS(Dase_Atom::$ns['atom'],'atom:title')));
		//properly escapes text
		$elem->appendChild($dom->createTextNode($title));
		$this->dom = $dom;
	}

	public function addAccept($mime,$multipart = false) {
		$elem = $this->addElement('accept',$mime,Dase_Atom::$ns['app']);
		if ($multipart) {
			$elem->setAttribute('alternate','multipart-related');
		}
		return $this;
	}

	public function addCategorySet($fixed = 'yes',$scheme = '',$href = '') {
		$cats = new Dase_Atom_Service_CategorySet($this->dom,$fixed,$scheme,$href);
		$this->root->appendChild($cats->root);
		return $cats;
	}
}
