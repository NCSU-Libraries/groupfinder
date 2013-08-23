<?php

// -------------------------------
// Project Name: GroupFinder
// Description: admin.php does two things: 
// 1. Allows users to delete activities that they have created
// 2. Renders an administrative interface for moderating activities 
// Author: Joseph Ryan
// -------------------------------

// for debugging
// error_reporting(E_ALL);
// ini_set('display_errors','On');

// configuration file
require_once("includes/config.php");

//functions file
require_once("includes/functions.php");

$authorized = false;

$userName = getCurrentUser();

$groupfinderDB = groupfinderDBConnect();

if (isset($_GET['activityID'])) {
	$activityID = $_GET['activityID'];
}

$legal_actions = array("disable", "enable");
if (isset($_GET['action']) && in_array($_GET['action'], $legal_actions)) {
	$action = $_GET['action'];
}

// code to allow user to disable activities that the user owns
if ($activityID) {
	$activityInfo = activityLookup($groupfinderDB, $activityID);
	$activityCreator = $activityInfo['userName'];
}
if ($action == "disable" && $activityID && $userName && $activityCreator == $userName) {
	if (disableActivity($groupfinderDB, $activityID)) {
			$userID = userLookup($groupfinderDB, $userName);
			$activityID = (int) $activityID;
			logActivity($userID, strtoupper($action), $activityID, "WEB");
			print "Success";
			mysql_close($groupfinderDB);
			exit();
		} else {
			print "failed to disable activity";
		}
}


// code from here down is used for admin interface
if (isAdmin($groupfinderDB, $userName)) {
	$authorized = true;
}

if ($authorized) {
	if ($action && $activityID) {
		if ($action == "disable") {
			if (disableActivity($groupfinderDB, $activityID)) {
				$userID = userLookup($groupfinderDB, $userName);
				$activityID = (int) $activityID;
				logActivity($userID, strtoupper($action), $activityID, "ADMIN");
				print "Success";
				mysql_close($groupfinderDB);
				exit();
			} else {
				print "failed to disable activity";
			}
		} elseif ($action == "enable") {
			if (enableActivity($groupfinderDB, $activityID)) {
				$userID = userLookup($groupfinderDB, $userName);
				$activityID = (int) $activityID;
				logActivity($userID, strtoupper($action), $activityID, "ADMIN");
				print "Success";
				mysql_close($groupfinderDB);
				exit();
			} else {
				print "failed to disable activity";
			}
		}
	}
	$dialogMarkup = "<div id=\"activities\">\n";
	$currentActivities = array();
	$query = "SELECT activity.description, UNIX_TIMESTAMP(activity.start_time), UNIX_TIMESTAMP(activity.end_time), location.name, location.code, user.lastname, user.firstname, user.username, UNIX_TIMESTAMP(activity.created), activity.id, activity.enabled
					FROM activity, location, user
					WHERE activity.location_id = location.id AND activity.user_id = user.id
					AND activity.enabled=1
					AND TIMESTAMPDIFF(MINUTE, activity.start_time, NOW()) < 240
					ORDER BY activity.start_time ASC";					
	$result = mysql_query($query, $groupfinderDB);
	if ($result && mysql_num_rows($result) != 0) {
		while ($row = mysql_fetch_row($result)) {
			$description = stripslashes($row[0]);
			$startTime = $row[1];
			$endTime = $row[2];
			$location = $row[3];
			$locationCode = $row[4];
			$lastname = $row[5];
			$firstname = $row[6];
			$username = $row[7];
			$created = $row[8];
			$activityID = $row[9];
			$enabled = $row[10];
			$activities[$activityID] = array("description" => $description, "location_name" => $location, "location_code" => $locationCode, "start_time" => $startTime, "end_time" => $endTime, "lastname" => $lastname, "firstname" => $firstname, "username" => $username, "created" => $created, "enabled" => $enabled);
			$dialogMarkup .= "<div title=\"$projectTitle Administration\" class=\"$activityID\">\n";
			$dialogMarkup .= "<h3>Disable this activity?</h3>\n";	
			$dialogMarkup .= "<p>$description <br />";
			if ($firstname && $lastname) {
				$dialogMarkup .= "$lastname, $firstname</p>\n";
			} else {
				$dialogMarkup .= "$username</p>\n";
			}
			$dialogMarkup .= "<p>" . date("h:i a", $startTime) . " - " . date("h:i a", $endTime) . "</p>\n";
			$dialogMarkup .= "<p class=\"activityID\">$activityID</p>\n";
			$dialogMarkup .= "</div>\n";
		}
		$dialogMarkup .= "</div>\n";
	} 
}
mysql_close($groupfinderDB);
?>


<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />
	<meta http-equiv="PRAGMA" content="NO-CACHE" />
	<meta name="ROBOTS" content="NONE" /> 
	<title><?=$projectTitle; ?> Administration</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/jquery-ui.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="includes/jquery-ui-1.8.4.custom.css" type="text/css" />	
	<link rel="stylesheet" href="includes/style.css" type="text/css" />

	<script type="text/javascript">
	function disableActivity(domElement) {
		activityID = $(domElement).children('p.activityID').text();
		$.get('admin.php', {action: "disable", activityID: activityID}, function(returnedData) {
			if (returnedData == "Success") {
				rowID = "activity_" + activityID;
				//update table text and style
				$('#' + rowID + " a").text("Re-enable?");
				$('#' + rowID).parents('tr').removeClass("enabled").addClass("disabled");
				// rebuild dialog with new text and functionality
				$(domElement).dialog('destroy');
				$(domElement).children('h3').text("Re-enable activity?");
				$(domElement).dialog({modal: true, width: 450, autoOpen: false, buttons: { "Cancel": function() { $(this).dialog("close"); }, "Re-enable": function() { enableActivity(this) }} });
				// fade in message that displays for a few seconds that the activity was disabled
			} else {
				$(domElement).dialog('close');
				alert("An error has occurred.");
			}
		});
	}
	function enableActivity(domElement) {	
		activityID = $(domElement).children('p.activityID').text();
		$.get('admin.php', {action: "enable", activityID: activityID}, function(returnedData) {
			if (returnedData == "Success") {
				rowID = "activity_" + activityID;
				//update table text and style
				$('#' + rowID + " a").text("Disable?");
				$('#' + rowID).parents('tr').removeClass("disabled").addClass("enabled");
				// rebuild dialog with new text and functionality
				$(domElement).dialog('destroy');
				$(domElement).children('h3').text("Disable activity?");
				$(domElement).dialog({modal: true, width: 450, autoOpen: false, buttons: { "Cancel": function() { $(this).dialog("close"); }, "Disable": function() { disableActivity(this) }} });
				// fade in message that displays for a few seconds that the activity was disabled
			} else {
				$(domElement).dialog('close');
				alert("An error has occurred.");				
			}
		});
	}
	
	$(document).ready(function() {
			$('#activities div').dialog({modal: true, width: 450, autoOpen: false, buttons: { "Cancel": function() { $(this).dialog("close"); }, "Disable": function() { disableActivity(this) }} });
			$('td.toggle').click(function() {
				domID = this.id;
				activityID = domID.replace("activity_", "");
				$('div.' + activityID).dialog('open');
				return false;
			});
	});
		</script>
</head>

<body>
	<div id="admin">
		<?php if($authorized): ?>
		<h1><?=$projectTitle; ?>: Manage Posts</h1>
		<p><a href="index.php">Return to <?=$projectTitle; ?> home page</a></p>
		<?php
			if ($activities) {
				print "<table>\n";
				print "<tr>\n";
				print "<td class=\"header\">Admin</td>\n";
				print "<td class=\"header\">Date</td>\n";
				print "<td class=\"header\">Start</td>\n";
				print "<td class=\"header\">End</td>\n";
				print "<td class=\"header\">Description</td>\n";
				print "<td class=\"header\">Location</td>\n";
				print "<td class=\"header\">Name</td>\n";
				print "</tr>\n";
				foreach ($activities as $activityID => $activity) {
					if ($activity['enabled']) {
						print "<tr class=\"enabled\">\n";
						print "<td id=\"activity_$activityID\" class=\"toggle\"><a href=\"#\">Disable?</a></td>";	
					} else {
						print "<tr class=\"disabled\">\n";
						print "<td>&nbsp;</td>";
					}
					
					print "<td>" . date("D, n/j/y", $activity['start_time']) . "</td>\n";
					print "<td>" . date("h:i a", $activity['start_time']) . "</td>\n";
					print "<td>" . date("h:i a", $activity['end_time']) . "</td>\n";
					print "<td>" . $activity['description'] . "</td>\n";
					print "<td>" . $activity['location_name'] . "</td>\n";
					if ($activity['lastname'] && $activity['firstname']) {
						print "<td>" . $activity['lastname'] . ", " . $activity['firstname'] . "</td>\n";
					} else {
						print "<td>" . $activity['username']  . "</td>\n";
					}
					print "</tr>\n";
				}
				print "</table>\n";
				print $dialogMarkup;
			} else {
				print "<h2>No current or upcoming activities found.</h2>\n";
			}
		?>
	<?php else: ?>
		<h1>Unauthorized</h1>
		<p>You do not have access to view this page.</p>
		<p><a href="index.php">Return to <?=$projectTitle; ?> home page</a></p>
	<?php endif; ?>	
	</div>
</body>
</html>
