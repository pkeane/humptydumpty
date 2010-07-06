<?php
function smarty_modifier_UrlToSerial($url)
{
	$sernum = explode("/",$url);
	$sernum = end($sernum);
	$sernum = explode(".",$sernum);
	$sernum = $sernum[0];
	return $sernum;
}

?>
