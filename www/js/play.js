$(document).ready(function() {
	var file = $("meta[name='media-file']").attr('content');
	$("#jpId").jPlayer({
		ready: function () {
			alert('i am ready');
			this.element.jPlayer("setFile",file);
		}});
});

