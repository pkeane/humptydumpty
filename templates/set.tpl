{extends file="layout.tpl"}


{block name="content"}
<div id="homeContent">
	<h1>Exercises for Set "{$set->title}"</h1>
	<ul class="exercises">
		{foreach item=ex key=key from=$set->exercises}
		<li><a href="exercise/{$ex->id}">{$ex->title}</a></li>
		{/foreach}
	</ul>
	<h4 class="download_link"><a href="set/{$set->id}/download_all">[download all audio files]</a></h4>
</div>
{/block}
