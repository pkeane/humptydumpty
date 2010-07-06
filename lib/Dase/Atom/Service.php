<?php
class Dase_Atom_Service extends Dase_Atom
{
	function __construct()
	{
		$dom = new DOMDocument('1.0','utf-8');
		$this->dom = $dom;
		$this->root = $this->dom->appendChild($this->dom->createElementNS(Dase_Atom::$ns['app'],'service'));
	}

	function addWorkspace($title)
	{
		$workspace = new Dase_Atom_Service_Workspace($this->dom,$title);
		$this->root->appendChild($workspace->root);
		return $workspace;
	}
}
