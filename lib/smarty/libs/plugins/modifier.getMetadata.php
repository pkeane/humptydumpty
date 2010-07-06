<?php
//gets metadata or metadata links from an atom entry
function smarty_modifier_getMetadata($item,$attribute)
{
	if(!$item) return false;
	$metadata = $item->getMetadata($attribute);
	if(!$metadata)	$metadata = $item->getMetadataLinks($attribute);
	return $metadata['values'];
}

?>
