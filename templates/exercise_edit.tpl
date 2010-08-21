{extends file="layout.tpl"}

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
	<div id="exerciseForms">
		<form method="post" action="exercise/{$exercise->id}/title">
			<p>
			<label for="title">Title</label>
			<input lang="he" dir="rtl" name="title" value="{$exercise->title}">
			<input type="submit" value="update">
			</p>
		</form>

		<form method="post" action="exercise/{$exercise->id}/lines">
			<p>
			<label for="text">Enter the text of the exercise.</label>
			<textarea lang="he" dir="rtl" name="text" class="exercise_text">{$exercise->str_lines}</textarea>
			<input type="submit" value="set/update exercise text">
			</p>
		</form>

		<form method="post" action="exercise/{$exercise->id}/media">
			<label for="media_file">Select a media file</label>
			<div id="media_files">
				<ul>
					{foreach from=$feed.items item=item}
					<li>
					<input type="radio" name="media_file" class="{$item.metadata.title[0]}" value="{$feed.app_root}{$item.enclosure.href}">
					<img src="{$feed.app_root}{$item.media.thumbnail}">
					<span class="label">{$item.metadata.title[0]}</span>
					</li>
					{/foreach}
				</ul>
				<input id="title_target" type="hidden" name="media_file_title" value="">
			</div>
			<input type="submit" value="set media file"></p>
			<input type="submit" name="remove" value="remove media file"></p>
		</form>

		<form method="post" action="exercise/{$exercise->id}/instructions">
			<p>
			<label for="text">Enter instructions for the exercise.</label>
			<textarea name="instructions" class="exercise_instructions">{$exercise->instructions}</textarea>
			<input type="submit" value="set/update exercise instructions"></p>
		</form>

		<form id="add_set" method="post" action="exercise/{$exercise->id}/set">
			<label for="set_id">Associate Exercise with Exercise Set (currently: {$exercise->set->title})</label>
			<select name="set_id">
				<option>select:</option>
				{foreach item=set from=$request->user->admin_sets}
				<option value="{$set->id}">{$set->title}</option>
				{/foreach}
				<option value="">-----------------</option>
				{foreach item=set from=$request->user->sets}
				<option value="{$set->id}">{$set->title}</option>
				{/foreach}
			</select>
			<input type="submit" value="save">
		</form>
		<a href="exercise/{$exercise->id}">view exercise</a>
		<form action="exercise/{$exercise->id}" method="delete">
			<input type="submit" value="delete this exercise">
		</form>
	</div>
</div>
{/block}

