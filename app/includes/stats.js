
$(document).ready(function() {
	$('#start_picker').val("");
	$('#end_picker').val("");
	// set last permitted end date to today
	$('#start_picker').datepicker({minDate: '', maxDate: '+0'});
	$('#end_picker').datepicker({maxDate: '+0'});
	$('#statsActivities').hide();
	
	$('#activityToggle').click(function() {
		$('#statsActivities').toggle();
		return false;
	});
	
	// time adjustment form validation
	$('#setDateRange').submit(function() {
		start = $('#start_picker').val();
		end = $('#end_picker').val();
		if (!start) {
			alert("Please select a start date.");
			return false;
		}
		if (!end) {
			alert("Please select an end date.");
			return false;
		}
		start = start.split("/");
		month = parseInt(start[0], 10) - 1; // javascript dates are zero-indexed
		day = parseInt(start[1], 10);
		year = parseInt(start[2], 10);
		start = new Date(year, month, day);
		startTimestamp = start.getTime() / 1000; 
		startTimestamp = startTimestamp.toString();
		end = end.split("/");
		month = parseInt(end[0], 10) - 1; // javascript dates are zero-indexed
		day = parseInt(end[1], 10);
		year = parseInt(end[2], 10);
		end = new Date(year, month, day, 23, 59, 59);
		endTimestamp = end.getTime() / 1000; 
		endTimestamp = endTimestamp.toString();
		if (end < start) {
			alert("Please select a start date that is earlier than the end date.");
			return false;
		}
		$('#startTimestamp').val(startTimestamp);
		$('#endTimestamp').val(endTimestamp);
	});
	
});