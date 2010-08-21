var HD = {};

$(document).ready(function() {
	HD.initDelete('topMenu');
	HD.initDelete('emails');
	HD.initDelete('set_exercises');
	HD.initDelete('set_instructors');
	HD.initDelete('categories');
	HD.initToggle('itemlist');
	HD.initToggle('email');
	HD.initSortable('lines');
	HD.initSortable('set_exercises');
	HD.initClose();
	HD.initPlayer();
	HD.initUserPrivs();
	HD.initSetMediaFileTitle();
	HD.initQuickSelect('add_category');
	HD.initQuickSelect('add_email');
	HD.initSubmission();
	HD.initFormDelete();
	HD.initSaveExerciseOrder();
});

HD.initSaveExerciseOrder = function() {
	var exercises = [];
	$('#save_order').submit( function() {
		$('#set_exercises').find('li').each(function() {
			exercises[exercises.length] = $(this).attr('class');
		});
		var sorted_exercises = exercises.join('|');
		$('#sorted_exercises').attr('value',sorted_exercises);
	});
};

HD.initClose = function() {
	$('#close').click( function() {
		$(this).parent('div').addClass('hide');
		return false;
	});
};

HD.initUserPrivs = function() {
	$('#user_privs').find('a').click( function() {
		var method = $(this).attr('class');
		var url = $(this).attr('href');
			var _o = {
				'url': url,
				'type':method,
				'success': function(resp) {
					alert(resp);
					location.reload();
				},
				'error': function() {
					alert('sorry, there was a problem');
				}
			};
			$.ajax(_o);
		return false;
	});
};

HD.initSubmission = function() {
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

HD.initQuickSelect = function(id) {
	$('#'+id+' select').change(function() {
		$('#'+id).find('input[type="text"]').val($(this).find('option:selected').text());
	});
};

HD.initSetMediaFileTitle = function() {
	$('#media_files input[type=radio]').click(function() {
		$('#title_target').attr("value",$(this).attr('class'));
	});
};

HD.initPlayer = function() {
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

HD.initToggle = function(id) {
	$('#'+id).find('a[class="toggle"]').click(function() {
		var id = $(this).attr('id');
		var tar = id.replace('toggle','target');
		$('#'+tar).toggle();
		return false;
	});	
};

HD.initFormDelete = function() {
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

HD.initDelete = function(id) {
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

HD.initSortable = function(id) {
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
 
