{extends file="layout.tpl"}

{block name="content"}
<div id="homeContent">
	<h1>Administration</h1>
	<h3>orphan categories</h3>
	<ul id="orphans">
		{foreach item=cat from=$orphan_categories}
		<li>
		<a href="category/{$cat->id}">{$cat->text}</a>
		<a href="category/{$cat->id}" class="delete">[x]</a>
		</li>
		{/foreach}
	</ul>
</div>
{/block}
