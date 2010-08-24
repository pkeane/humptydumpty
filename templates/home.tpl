{extends file="layout.tpl"}

{block name="content"}
<div id="homeContent">
	<h1>Welcome to the Humpty Dumpty Portal</h1>
	<p>
	{$content|markdown}
	</p>
</div>
{/block}
