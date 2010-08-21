{extends file="layout.tpl"}

{block name="content"}
<div class="sets_instructor">
	<h1>Grant Instructor Privileges</h1>
	<ul class="users" id="user_privs">
		{foreach item=u from=$users}
		<li>{$u->name}
		{if $u->is_instructor}
		<a href="admin/user/{$u->id}/is_instructor" class="delete">[remove privileges]</a>
		{else}
		<a href="admin/user/{$u->id}/is_instructor" class="put">[grant privileges]</a>
		{/if}
		</li>
		{/foreach}
	</ul>
</div>
{/block}
