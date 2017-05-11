/*
	Password Generator

	Supports generating passwords of unlimited length and selectable complexity.

	@author nrekow
	
*/
$(function() {
	$('#generate').click(function() {
		doAJAX();
	});
	
	$('#checkstrength').click(function() {
		doAJAX('checkstrength');
	});
	
	$('#reset').click(function() {
		$('.strength').hide();
	});
	
	$(window).resize(function () {
	    $("#box").position({
	        my: "center", at: "center", of: window
	    });
	});

	$('#box').draggable({
		containment: 'window'
	});
});

function doAJAX(s) {
	if (typeof(s) == 'undefined') {
		s = '';
	} else {
		s = '&' + s + '=1';
	}
	
	if ($('#length').val() <= 0 || $('#length').val() == '') {
		$('#length').val(10);
	}
	
	var data = $('#generator').serialize();
	$.ajax({
		type: 'post',
		url: 'generator.php',
		data: 'ajax=1&' + data + s,
		dataType: 'json',
		success: function(result) {
			$('#result').val(result.password);
			
			if(typeof(result.strength) != 'undefined' && result.strength.length > 0) {
				$('.strength').hide();
				$('#strength-' + result.strength).show();
			}
		},
		error: function(xhr, status) {
			console.log('AJAX call failed with status ' + status);
		}
	});
}//END: doAJAX()