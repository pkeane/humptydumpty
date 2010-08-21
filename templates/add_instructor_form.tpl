{extends file="layout.tpl"}

{block name="content"}
<div>
	<h1>Add Instructor</h1>
	<dl class="user">
		<dt>name</dt>
		<dd>{$record.name}</dd>
		<dt>eid</dt>
		<dd>{$record.eid}</dd>
		<dt>email</dt>
		<dd>{$record.email}</dd>
		<dt>title</dt>
		<dd>{$record.title}</dd>
		<dt>unit</dt>
		<dd>{$record.unit}</dd>
		<dt>phone</dt>
		<dd>{$record.phone}</dd>
	</dl>
	{if $user}
	<h3>{$user->name} is already registered{if $user->is_instructor} as instructor{/if}</h3>
	{/if}

	<form method="post" action="admin/instructors">
		<input type="hidden" name="eid" value="{$record.eid}">
		{if $user && !$user->is_instructor}
		<input type="submit" value="make {$record.name} an instructor">
		{elseif !$user}
		<input type="submit" value="add {$record.name} as instructor">
		{/if}
	</form>
</div>
{/block}
