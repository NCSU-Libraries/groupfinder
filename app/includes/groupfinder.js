descriptionPromptText = "Enter a description of your group here...";
locationPromptText = "Where are you meeting?";
dialogWidth = 400;
			
$(document).ready(function() {
	// set up locationSelector dialog for possible reuse
	$('#locationList').columnize({columns: 2});
	$('#locationSelector').dialog({modal: true, autoOpen: false, width: 850, buttons: { "Cancel": function() { $(this).dialog("close"); }} });
	$("#activityInputBox").val(descriptionPromptText);
	$('#locationPrompt').text(locationPromptText);
	$("#selectDropdown").hide();
	
	dropdownButtonMarkup = "<div id='locationPrompt'>" + locationPromptText + "</div>";
	$(dropdownButtonMarkup).prependTo("#form_submit");
	$('#locationPrompt').click(function() {
		$('#locationSelector').dialog("open");
	});
	
	// show directions to moused-over location
	$('#locationSelector li').mouseenter(function() {
			$('div.directions:visible').hide();
			$('#locationSelector div.' + this.id).show();			
	});
	
	// event handler for choosing location of activity	
	$('#locationSelector li').click(function() {
		// set hidden form input for location
		if ($('#loc').length < 1) {
			$('#activityInput fieldset').append('<input id="loc" type="hidden" name="location" value="' + this.id + '" />');
		} else {
			$('#loc').attr('value', this.id);
		}
		// update display to reflect selected location
		$('#locationPrompt').text("@ " + $(this).text());
								
		$('#locationPrompt').css({background: "#FFF", padding: "0 0 0 0"});
		$('#locationSelector').dialog("close");
	});
	
	// Terms of Service
	$('#toslink').click(function() {
		$('#tos').dialog({modal: true, width: dialogWidth, buttons: { "OK": function() { $(this).dialog("destroy"); }} });
		return false;
	});
	
	// About
	$('#faqlink').click(function() {
		$('#faq').dialog({modal: true, width: dialogWidth, buttons: { "OK": function() { $(this).dialog("destroy"); }} });
		return false;
	});	
	
	// Create js-only link styling for direction popups
	$('span.activityLocation').css({color: "#413AAF", cursor: "pointer"});
	
	// event handler for showing directions to clicked-on locations
	$('span.activityLocation').click(function() {
		directionsToShow = $(this).attr("title");
		$('div.' + directionsToShow).dialog({modal: true, width: dialogWidth, buttons: { "OK": function() { $(this).dialog("destroy"); }} });	
	});

	// inappropriate activity report
	$('a.report').click(function() {
	// grab data to send
		description = $(this).parents('div.activity').find('span.activityDescription').text();
		id = $(this).parents('div.activity').attr('id');
		credits = $(this).parents('div.activity').find('span.activityCreator').text();
		reporter = $('#user_name').text();
		source = $('#source').text();
	// if user chooses to report the activity, do so via ajax and report on the results
	$('#report').dialog({modal: true, width: dialogWidth, buttons: { "Cancel": function() { $(this).dialog("destroy"); }, 
		"Report": function() { 
			$(this).dialog("destroy"); 
			$.get("mail.php", {description: description, credits: credits, reporter: reporter, source: source, id: id}, function(data) {
				if (data == "Success") {
					$('#reportConfirmation').dialog({modal: true, width: dialogWidth, buttons: { "OK": function() { $(this).dialog("destroy"); }} });;
				}
			});
		}} });
		return false;
	});
	$('a.delete').click(function() {
		activityID = $(this).parents('div.activity').attr('id');
		$('#delete').dialog({modal: true, width: dialogWidth, buttons: { "Cancel": function() { $(this).dialog("destroy"); }, 
			"Remove": function() { 
				$(this).dialog("destroy"); 		
				$.get('admin.php', {action: "disable", activityID: activityID}, function(returnedData) {
					if (returnedData == "Success") {
						$('#' + activityID).hide(1000);
					} else {
						alert("Error");
					}
				});
			}} });
		
		return false;
	});
	
	
	// get rid of example text and flip text color to black
	$("#activityInputBox").click(function() {
		if ($("#activityInputBox").val() == descriptionPromptText) {
			$("#activityInputBox").val("");
			$("#activityInputBox").css("color", "#000");
		}
	});
			
	// form validation (is also done server side)
	$("#activityInput").submit(function() {
		if ($("#activityInputBox").val() == descriptionPromptText || $("#activityInputBox").val() == "") {
			$('#nodescription').dialog({modal: true, width: dialogWidth, buttons: { "OK": function() { $(this).dialog("destroy"); }} });
			return false;
		}
		if ($('#loc').length < 1) {
			$('#locationSelector').dialog("open");
			return false;
		}
	});
	
	
		

	
});