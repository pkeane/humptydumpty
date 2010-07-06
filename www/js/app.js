var App = {};

$(document).ready(function() {
	App.initDelete('topMenu');
	App.initDelete('emails');
	App.initDelete('categories');
	App.initDelete('orphans');
	App.initToggle('itemlist');
	App.initSortable('lines');
	App.initPlayer();
	App.initSetMediaFileTitle();
	App.initQuickSelect('add_category');
	App.initQuickSelect('add_email');
	App.initSubmission();
	App.initFormDelete();
});

App.initSubmission = function() {
	$('#submission').submit( function() {
		var url = $(this).attr('action');
		var lineset = '';
		$('#lines li').each(function() {
			lineset += $(this).attr('id')+'|';
		});
		if (confirm('are you sure?')) {
			var _o = {
				'url': url,
				'type':'POST',
				'data':lineset,
				'success': function(resp) {
					alert(resp);
					//location.reload();
				},
				'error': function() {
					alert('sorry, there was a problem');
				}
			};
			$.ajax(_o);
		}
		return false;
	});
};

App.initQuickSelect = function(id) {
	$('#'+id+' select').change(function() {
		$('#'+id).find('input[type="text"]').val($(this).find('option:selected').text());
	});


};

App.initSetMediaFileTitle = function() {
	$('#media_files input[type=radio]').click(function() {
		$('#title_target').attr("value",$(this).attr('class'));
	});
};

App.initPlayer = function() {
	var file = $("meta[name='media-file']").attr('content');
    $("#jquery_jplayer").jPlayer({
        ready: function () {
			this.element.jPlayer("setFile",file);
    },
		swfPath: "www/js",
        customCssIds: true  
    });
    $("#jquery_jplayer").jPlayer("cssId", "play", "play_button"); // Associates play  
    $("#jquery_jplayer").jPlayer("cssId", "pause", "pause_button"); // Associates pause
    $("#jquery_jplayer").jPlayer("cssId", "loadBar", "load_bar");
    $("#jquery_jplayer").jPlayer("cssId", "playBar", "play_bar");
    $("#jquery_jplayer").jPlayer("cssId", "volumeBar", "volume_bar");
    $("#jquery_jplayer").jPlayer("cssId", "volumeBarValue", "volume_bar_value");
	
	$("#jquery_jplayer").jPlayer("onProgressChange", function(lp,ppr,ppa,pt,tt) {
	  $("#play_time").text($.jPlayer.convertTime(pt)); // Default format of 'mm:ss'
	  $("#total_time").text($.jPlayer.convertTime(tt)); // Default format of 'mm:ss'
	});  
  
    $("#jquery_jplayer").jPlayer("onSoundComplete", function() { // Executed when the mp3 ends  
        this.element.jPlayer("stop"); // Auto-repeat  
    });
};

App.initToggle = function(id) {
	$('#'+id).find('a[class="toggle"]').click(function() {
		var id = $(this).attr('id');
		var tar = id.replace('toggle','target');
		$('#'+tar).toggle();
		return false;
	});	
};

App.initFormDelete = function() {
	$("form[method='delete']").submit(function() {
		if (confirm('are you sure?')) {
			var del_o = {
				'url': $(this).attr('action'),
				'type':'DELETE',
				'success': function() {
					location.reload();
				},
				'error': function() {
					alert('sorry, cannot delete');
				}
			};
			$.ajax(del_o);
		}
		return false;
	});
};

App.initDelete = function(id) {
	$('#'+id).find("a[class='delete']").click(function() {
		if (confirm('are you sure?')) {
			var del_o = {
				'url': $(this).attr('href'),
				'type':'DELETE',
				'success': function() {
					location.reload();
				},
				'error': function() {
					alert('sorry, cannot delete');
				}
			};
			$.ajax(del_o);
		}
		return false;
	});
};

App.initSortable = function(id) {
	$('#'+id).sortable({ 
		cursor: 'crosshair',
		opacity: 0.6,
		revert: true, 
		start: function(event,ui) {
			ui.item.addClass('highlight');
		},	
		stop: function(event,ui) {
			$('#proceed-button').addClass('hide');
			$('#unsaved-changes').removeClass('hide');
			$('#'+id).find("li").each(function(index){
				$(this).find('span.key').text(index+1);
			});	
			ui.item.removeClass('highlight');
		}	
	});
};
 
