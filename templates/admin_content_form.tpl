{extends file="layout.tpl"}

{block name="content"}
<div>
	<h1>Edit Content for page "{$page}"</h1>
	<form method="post">
		<textarea name="text">{$content}</textarea>
		<input type="submit" value="update">
	</form>
</div>
{/block}
