{extends file="base.tpl"}

{block name="title"}Humpty Dumpty Portal{/block}

{block name="head"}
<script type="text/javascript" src="www/js/jquery.jplayer.js"></script>
{/block}

{block name="head-links"}
<link rel="stylesheet" type="text/css" href="www/css/jplayer.blue.monday.css">
{/block}

{block name="wordmark"}
<div id="universityWordMark">
	<a href="http://www.utexas.edu/cola"><img alt="UT College of Liberal Arts Wordmark" src="www/images/UTCOLA.jpg"/></a>
</div>
{/block}

{block name="header"}
<div class="header-inner">
	<h1><a href="home">Humpty Dumpty Portal</a></h1>
	<h4 id="topMenu">
		<a href="home">home</a> |
		{if $request->user->is_instructor or $request->user->is_admin}
		<a href="admin">admin</a> |
		{/if}
		<a href="login/{$request->user->eid}" class="delete">logout {$request->user->eid}</a> 
	</h3>
</div>
<div class="clear"></div>
{/block}

{block name="sidebar"}
<ul class="menu">
	<li><h2>Exercise Sets</h2></li>
	{foreach item=mset from=$exercise_sets}
	<li><a href="set/{$mset->id}" {if $set->id == $mset->id}class="current"{/if}>{$mset->title}</a></li>
	{/foreach}
</ul>
{/block}

{block name="main"}
{if $msg}<h3 class="msg">{$msg}</h3>{/if}
{block name="content"}default content{/block}
{/block}

{block name="footer"}
<div class="brand">
	<table class="logo">
		<tr><td class="a1">&nbsp;</td><td class="a2">&nbsp;</td><td class="a3">&nbsp;</td><td class="a4">&nbsp;</td><td class="a5">&nbsp;</td></tr>
		<tr><td class="b1">&nbsp;</td><td class="b2">&nbsp;</td><td class="b3">&nbsp;</td><td class="b4">&nbsp;</td><td class="b5">&nbsp;</td></tr>
		<tr><td class="c1">&nbsp;</td><td class="c2">&nbsp;</td><td class="c3">&nbsp;</td><td class="c4">&nbsp;</td><td class="c5">&nbsp;</td></tr>
		<tr><td class="d1">&nbsp;</td><td class="d2">&nbsp;</td><td class="d3">&nbsp;</td><td class="d4">&nbsp;</td><td class="d5">&nbsp;</td></tr>
		<tr><td class="e1">&nbsp;</td><td class="e2">&nbsp;</td><td class="e3">&nbsp;</td><td class="e4">&nbsp;</td><td class="e5">&nbsp;</td></tr>
	</table>
	<div class="label">
		<a href="http://www.laits.utexas.edu/its/"><strong>Liberal Arts</strong> Instructional Technology Services</a>
	</div>
</div>
{/block}
