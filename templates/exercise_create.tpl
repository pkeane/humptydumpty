{extends file="layout.tpl"}

{block name="content"}
<h1>New Exercise Form</h1>
<div class="main">
	<form id="exerciseShortForm" action="exercise/create" class="shortForm" method="post" >
		<p>
		<label for="title">Exercise Title</label>
		<input class="long" type="text" name="title" />
		</p>
		<p>
		<input type="submit" value="start new exercise"/>
		</p>
	</form>
</div>
{/block}

