<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<base href="{$module_root}/"/>
		<title>DASe {$db} Admin</title>
		<link rel="stylesheet" type="text/css" href="{$module_root}/css/style.css">
		<script type="text/javascript" src="scripts/jquery.js"></script> 
		<script type="text/javascript" src="scripts/dbadmin_jquery.js"></script>
	</head>
	<body>
		<div class="container">
			<div class="branding">
				{$db} Admin
			</div>
			<div class="content">

				<ul class="tableSet">
					{foreach key=table item=cols from=$tables}
					<li><a href="{$table}">{$table}</a>
					<ul>
						{foreach item=col from=$cols}
						<li>{$col}</li>
						{/foreach}
					</ul>
					</li>
					{/foreach}
				</ul>

			</div>



		</div>
	</body>
</html>
