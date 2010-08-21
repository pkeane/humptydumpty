{extends file="layout.tpl"}

{block name="content"}
<div class="sets_admin">
	<h1>Manage Set {$set->title}</h1>
	<form action="set/{$set->id}/title" method="post">
		<label for="title">update title</label>
		<input type="text" name="title" value="{$set->title}">
		<input type="submit" value="update">
	</form>
	{if 0 == $set->exercises|@count}
	<form method="post" action="set/{$set->id}/delete_queue">
		<input type="submit" name="delete" value="delete this set">
	</form>
	{/if}
	<h2 class="section">Exercises <span class="hint">(drag to sort)</span></h2>
	<ul class="set_exercises" id="set_exercises">
		{foreach key=key item=exercise from=$set->exercises}
		<li class="{$exercise->id}"><span class="key">{$key+1}</span>. {$exercise->title} <a href="set/{$set->id}/exercise/{$exercise->id}" class="delete">[remove from set]</a></li>
		{/foreach}
	</ul>
	<form action="admin/set/{$set->id}/exercise_sorter" id="save_order" method="post">
		<input type="hidden" name="sorted_exercises" value="suuuus" id="sorted_exercises">
		<input type="submit" value="save sort order" id="unsaved-changes" class="hide">
	</form>
	<h2 class="section">Instructors</h2>
	<ul id="set_instructors">
		{foreach item=instructor from=$set->users}
		<li>{$instructor->name} <a href="admin/set/{$set->id}/instructor/{$instructor->id}" class="delete">[remove from set]</a></li>
		{/foreach}
	</ul>
	<form method="post" action="admin/set/{$set->id}/instructors">
		<label for="instructor_id">add instructor</label>
		<select name="instructor_id">
			<option value="">select one:</option>
			{foreach item=instructor from=$instructors}
			<option value="{$instructor->id}">{$instructor->name}</option>
			{/foreach}
		</select>
		<input type="submit" value="add">
	</form>
</div>
{/block}
