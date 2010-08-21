{extends file="layout.tpl"}

{block name="content"}
<div class="exercises_admin">
	<h1>Manage/Create Exercises</h1>
	<h3>create a new exercise</h3>
	<form id="exerciseShortForm" action="exercise/create" class="shortForm" method="post" >
		<label for="title">title</label>
		<input class="long" type="text" name="title" />
		<input type="submit" value="create exercise"/>
	</form>

	<h3>my exercises</h3>
	<ul class="my_exercises">
		{foreach item=ex from=$request->user->exercises}
		<li><a href="exercise/{$ex->id}">{$ex->title}</a></li>
		{/foreach}
	</ul>
</div>
{/block}

