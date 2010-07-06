{extends file="layout.tpl"}

{block name="sidebar"}
<ul class="menu">
	<li><h2>Exercises By Category</h2></li>
	{foreach item=cat from=$categories}
	<li><a href="category/{$cat->id}">{$cat->text}</a></li>
	{/foreach}
</ul>
{/block}

{block name="content"}
<div id="homeContent">
	<h1>Exercises for Category "{$category->text}"</h1>
	<ul class="exercises">
		{foreach item=ex from=$category->exercises}
		<li><a href="exercise/{$ex->id}">{$ex->title}</a></li>
		{/foreach}
	</ul>
</div>
{/block}
