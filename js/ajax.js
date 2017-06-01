/*
	Password Generator

	Supports generating passwords of unlimited length and selectable complexity.

	@author nrekow
	
*/

function centerBox() {
    $("#outer_box").position({
        my: "center", at: "center", of: window
    });
}// END: centerBox();


function doAJAX(s) {
	// Check if a parameter has been specified and prepare for POST; fallback to an empty string.
	if (typeof(s) == 'undefined') {
		s = '';
	} else {
		s = '&' + s + '=1';
	}
	
	// Check value of #length input form field; fallback to 10 if empty or lower than 1.
	if ($('#length').val() <= 0 || $('#length').val() === '') {
		$('#length').val(10);
	}
	
	// Serialize our form (e.g. grab all form fields and their values and store then in the "data" variable.
	var data = $('#generator').serialize();
	
	// Send "data" against our PHP script using AJAX; expects JSON in return.
	$.ajax({
		type: 'post',
		url: 'generator.php',
		data: 'ajax=1&' + data + s,
		dataType: 'json',

		// Re-enable the button after the AJAX call has been completed.
		complete: function() {
			$('#generate').prop('disabled', false);
		},
		
		// On success write generated password string into our #result form field
		success: function(result) {
			$('#result').val(result.password);

			// Hide strength (e.g. red, yellow, green bars) ...
			$('.strength').hide();

			// ... check if our AJAX call also returned a strength ... 
			if (typeof(result.strength) != 'undefined' && result.strength.length > 0) {
				// ... and just show the one returned by the AJAX call.
				$('#strength-' + result.strength).show();
			}
		},
		
		// On error write status into browser's log
		error: function(xhr, status) {
			console.log('AJAX call failed with status ' + status);
		}
	});
}//END: doAJAX()


$(function() {
	// Handle clicks on the Generate button
	$('#generate').click(function() {
		// Disable the button to avoid hammering.
		$('#generate').prop('disabled', true);
		doAJAX();
	});
	
	// Handle clicks on the "Check strength" button
	$('#checkstrength').click(function() {
		doAJAX('checkstrength');
	});
	
	// Handle clicks on the "Reset" button.
	// This button has the type "reset", which resets all form fields to their initial values, so we just need to clear the strength-meter.
	$('#reset').click(function() {
		$('.strength').hide();
	});
	
	// Center the box if the browser window is resized.
	$(window).resize(function () {
		centerBox();
	});

	// Configure the box to be draggable inside the browser window, but don't allow to move it out of the viewport.
	$('#outer_box').draggable({
		containment: '#box',
		handle: '#title',
		cancel: '#inner_box'
	});
	
	// Disable text-selection of title-bar, because this is used as drag-handle
	$('#title').disableSelection();
	
	// Center box upon load
	centerBox();
});