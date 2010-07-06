{extends file="layout.tpl"}

{block name="sidebar"}
<ul class="menu">
	<li><h2>Exercises By Category</h2></li>
	{foreach item=cat from=$categories}
	<li><a href="category/{$cat->id}/exercises">{$cat->text}</a></li>
	{/foreach}
</ul>
{/block}

{block name="head-meta"}
<meta name="media-file" content="{$exercise->media_file}">
{/block}

{block name="content"}
{if 'audio/mp3' == $exercise->media_mime_type}
<div id="jquery_jplayer"></div>
<div class="interface">
	<ul class="controls">  
		<li id="play_button">Play</li>  
		<li id="pause_button">Pause</li>   
	</ul>
	<div class="progress">
		<div id="load_bar" class="jp-load-bar">
			<div id="play_bar" class="jp-play-bar"></div>
		</div>
	</div>
	<div id="volume">
		<p>Volume</p>
		<div id="volume_bar" class="jp-volume-bar">
			<div id="volume_bar_value" class="jp-volume-bar-value"></div>
		</div>
	</div>
	<ul id="times">
		<li id="play_time" class="jp-play-time">00:00</li>
		<li  id="total_time" class="jp-total-time">03:09</li>
	</ul>
</div>
<div dir="rtl" lang="he" id="title">{$exercise->media_file_title}</div>
{/if}

<div class="exercise">
	<h1 dir="rtl" lang="he">Exercise: {$exercise->title}</h1>
	<ul dir="rtl" class="lines" id="lines">
		{foreach item=line from=$exercise->lines}
		<li	id="{$line->id}" lang="he" dir="rtl">{$line->text}</li>
		{/foreach}
	</ul>
	<form id="submission" dir="rtl" method="post" action="exercise/{$exercise->id}/submission">
		<input type="submit" value="submit answer">
	</form>
</div>

{if $exercise->creator_eid == $request->user->eid}
<div class="control">
<a href="exercise/{$exercise->id}/edit">edit exercise</a>
</div>
{/if}
{/block}

