<?php
function smarty_modifier_getCategory($attribute,$type)
{
	foreach ($attribute->getCategories() as $c){
		if ('http://daseproject.org/category/'.$type == $c['scheme']) {
			return $c['term'];
		}
	}
	return false;
}

?>
