{extends file="layout.tpl"}

{block name="content"}
<div>
	<h1>User Settings for {$request->user->name}</h1>
	<dl class="user">
		<dt>name</dt>
		<dd>{$request->user->name}</dd>
		<dt>eid</dt>
		<dd>{$request->user->eid}</dd>
		{if $request->user->is_instructor}
		<dt>email</dt>
		<dd id="email">
		{$request->user->email} <a href="" id="toggleEmail" class="toggle">[update]</a>
		<form id="targetEmail" class="hide" method="post" action="user/email">
			<p>
			<input type="text" value="{$request->user->email}" name="email">
			<input type="submit" value="update">
			</p>
		</form>
		</dd>
		{/if}
		<dt>default instructor</dt>
		<dd>{$request->user->instructor->name}</dd>
		<dt>is instructor</dt>
		<dd>
		{if $request->user->is_instructor}yes{else}no{/if}
		</dd>
		<dt>is admin</dt>
		<dd>
		{if $request->user->is_admin}yes{else}no{/if}
		</dd>
	</dl>
</div>
{/block}
