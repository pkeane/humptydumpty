{extends file="layout.tpl"}

{block name="content"}
<div class="sets_admin">
	<h1>Manage/Create Sets</h1>
	<h3>create a new set</h3>
	<form method="post">
		<input type="text" name="title">
		<input type="submit" value="create set">
	</form>

	<h3>my sets</h3>
	<ul class="my_sets">
		{foreach item=set from=$request->user->admin_sets}
		<li><a href="admin/set/{$set->id}">{$set->title}</a></li>
		{/foreach}
	</ul>
	<h3>sets for which I have instructor privileges</h3>
	<ul class="my_sets">
		{foreach item=set from=$request->user->sets}
		<li><a href="set/{$set->id}">{$set->title}</a></li>
		{/foreach}
	</ul>
</div>
{/block}
