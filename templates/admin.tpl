{extends file="layout.tpl"}

{block name="content"}
<div>
	<h1>Administration</h1>
	<ul class="operations">
		<li><a href="user/settings">my user settings</a></li>
		{if $request->user->is_admin}
		<li><a href="directory">add an instructor</a></li>
		<li><a href="admin/users">grant/remove instructor privileges</a></li>
		{/if}
		<li><a href="admin/set_form">manage/create sets</a></li>
		<li><a href="exercise/create">manage/create exercises</a></li>
	</ul>
</div>
{/block}
