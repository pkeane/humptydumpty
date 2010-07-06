<!DOCTYPE html>
<html lang="en">
	<head>
		<base href="{$app_root}">
		<meta charset=utf-8 />
		{block name="head-meta"}{/block}
		<title>{block name="title"}Humpty Dumpty Portal{/block}</title>
		<style type="text/css">
			{block name="style"}{/block}
		</style>

		<link rel="stylesheet" type="text/css" href="www/css/style.css">
		<link rel="stylesheet" type="text/css" href="www/css/jplayer.blue.monday.css">
		{block name="head-links"}{/block}

		<script type="text/javascript" src="www/js/jquery.js"></script>
		<script type="text/javascript" src="www/js/jquery-ui.js"></script>
		<!--
		<script type="text/javascript" src="www/js/jquery.jplayer.min.js"></script>
		-->
		<script type="text/javascript" src="www/js/jquery.jplayer.js"></script>
		<script type="text/javascript" src="www/js/app.js"></script>
		{block name="head"}{/block}

	</head>
	<body>
		<!--University Wordmark-->
		<div id="wordMark">
			<div id="universityWordMark">
				<a href="http://www.utexas.edu/cola"><img alt="UT College of Liberal Arts Wordmark" src="www/images/UTCOLA.jpg"/></a>
			</div>
		</div>
		<div id="container">

			<div id="topper">
				<h1><a href="home">Humpty Dumpty Portal</a></h1>
				<h3 id="topMenu">
					<a href="home">Home</a> |
					{if $request->user->is_superuser}
					<a href="admin">admin</a> |
					{/if}
					<a href="login/{$request->user->eid}" class="delete">logout {$request->user->eid}</a> 
				</h3>
				<div class="spacer"></div>
			</div>

			<div id="sidebar">
				{block name="sidebar"}
				<ul class="menu">
					<li class="main-link">
					<a href="exercise/create" class="main">Create an Exercise</a>
					</li>
					{if $request->user->exercises}
					<li><h2>My Exercises</h2></li>
						{foreach item=ex from=$request->user->exercises}
						<li><a href="exercise/{$ex->id}">{$ex->title}</a></li>
						{/foreach}
					{/if}
					{if $request->user->categories}
					<li><h2>My Exercises By Category</h2></li>
						{foreach item=cat from=$request->user->categories}
						<li><a href="category/{$cat->id}">{$cat->text}</a></li>
						{/foreach}
					{/if}
				</ul>
				{/block}
			</div> 

			<div id="content">
				{if $msg}<h3 class="msg">{$msg}</h3>{/if}
				{block name="content"}default content{/block}
			</div>

		</div>
		<div class="spacer"></div>
		<div id="footer">
			<a href="http://www.laits.utexas.edu/its/"><img src="www/images/footer.jpg" title="LAITS" class="logo" alt="LAITS"></a> 
		</div>
	</body>
</html>
