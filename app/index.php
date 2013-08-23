<?php

// -------------------------------
// Project Name: GroupFinder
// Description: GroupFinder is a system designed to accept descriptions of student study groups
// (description and location within the building) and make them available for display in multiple 
// contexts, including eboards, via the web, and more
// Author: Joseph Ryan
// -------------------------------

// for debugging
// error_reporting(E_ALL);
// ini_set('display_errors','On');

// configuration file
require_once("includes/config.php");

// database and display functions
require_once("includes/functions.php");

$userName = getCurrentUser();

if ($userName) {
	// if user filled out form in an unauthenticated state, login.php stores these values in a session
	// retrieve and unset session data
	session_start();
	if (isset($_SESSION['activityInfo']['description']) && isset($_SESSION['activityInfo']['location'])) {
		$candidateLocation = $_SESSION['activityInfo']['location'];
		$candidateDescription = $_SESSION['activityInfo']['description'];
		unset($_SESSION['activityInfo']);
	}
}

// source for logging
$source = "WEB";

if (isset($_GET['source']) && in_array(strtolower($_GET['source']), $legalSources)) {
	$source = strtoupper($_GET['source']);
} elseif (isset($_POST['source']) && in_array(strtolower($_POST['source']), $legalSources)) {
	$source = strtoupper($_POST['source']);
}

// determine candidate location and description
// case applies if user is already authenticated when form is filled out
if (isset($_POST['description']) && isset($_POST['location'])) {
	$candidateLocation = $_POST['location'];
	$candidateDescription = $_POST['description'];
}

$groupfinderDB = groupfinderDBConnect();
if ($userName) {
	$userID = userLookup($groupfinderDB, $userName);
	if (!$userID) {
		$userID = createUser($groupfinderDB, $userName);
	}
} else {
	$userID = "NOLOGIN";
}

// is user an administrator?
if ($userName && isAdmin($groupfinderDB, $userName)) {
	$admin = true;
} else {
	$admin = false;
}

logActivity($userID, "VIEW", "N/A", $source);
	
// get list of locations for current building
$locations = getLocationList($groupfinderDB, $BUILDING);
$activityCreated = false;
// determine if user is attempting to create an activity AND is authenticated
if (isset($candidateDescription) && isset($candidateLocation) && $userID != "NOLOGIN") {

	//validate location
		foreach ($locations as $locationListing) {
		if ($locationListing['code'] == $candidateLocation) {
			$locationCode = $locationListing['code'];
			break;
		}
	}	

	//sanitize description
	$description = sanitizeText($groupfinderDB, $candidateDescription);

	// if both variables pass validation, try to write to db
	if ($description && $locationCode) {
		$startTime = time();
		$activityCreated = createActivity($groupfinderDB, $source, $description, $locationCode, $userName, $startTime);
		if (!$activityCreated && !($activityCreated === 0)) { //db error . function returns 0 if a duplicate entry is submitted
			print "<h2 class=\"error\">An error has occurred. Please try again.</h2>";
		}	
	} elseif (!$validLocation) { // location failed validation
		print "<h2 class=\"error\">Please select a location and re-enter your activity description.</h2>";
	} else { // description has error
		print "<h2 class=\"error\">Please enter a valid activity description.</h2>";
	}

}
	
	
// get list of current activities for current building
$currentActivities = getCurrentActivities($groupfinderDB, $BUILDING, $MAX_ACTIVITY_AGE_IN_HOURS);

// close db connection
mysql_close($groupfinderDB);

// generate markup for location selector popup and select element
$floorLocationsMarkup = "";
$directionsMarkup = "<div class=\"directions\">\n<h3>Mouse over a location to see directions.</h3>\n</div>\n";
$selectMarkup = "<select id=\"selectDropdown\">\n<option value=\"\" >Please select a location</option>\n";
foreach ($locations as $location) {
	// for dropdown (used if js is turned off)
	$selectMarkup .= "<option value=\"" . $location['code'] . "\">" . $location['name'] . "</option>\n";
	// for dialog
	$directionsMarkup .= "<div title=\"Directions from $directionsOrigin\" class=\"directions " . $location['code'] . "\">\n<h3>" . $location['name'] . "</h3>\n<img src=\"includes/directions/" . $location['code'] .".jpg\" alt=\"\" />\n";
	$directionsMarkup .= "<p>" . $location['directions'] . "</p>\n</div>\n";
	$currentFloor = $location['floor'];
	if ($currentFloor == 0 && !isset($currentFloorHeading)) {
		$floorLocationsMarkup .= "<h3 class=\"floorHeader\">Ground Floor</h3>\n";
		$floorLocationsMarkup .= "<ul>\n";
		$currentFloorHeading = $currentFloor;
	}
	if ($currentFloor != $currentFloorHeading) {
		$floorLocationsMarkup .= "</ul>\n";
		$floorLocationsMarkup .= "<h3 class=\"floorHeader\">Floor " . $currentFloor ."</h3>\n";
		$floorLocationsMarkup .= "<ul>\n";
		$currentFloorHeading = $currentFloor;
	}
	$floorLocationsMarkup .= "<li id=\"" . $location['code'] . "\">" . $location['name'] . "</li>\n";
}
$floorLocationsMarkup .= "</ul>\n";
$selectMarkup .= "</select>\n";

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
	<title><?=$projectTitle; ?> : <?=$organizationName; ?></title>	
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/jquery-ui.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="includes/jquery-ui-1.8.4.custom.css" type="text/css" />	
	<link rel="stylesheet" href="includes/style.css" type="text/css" />
	<script src="includes/autocolumn.min.js" type="text/javascript"></script>
	<script src="includes/groupfinder.js" type="text/javascript"></script>
</head>
<body>
	<div class="access">
		<a href="#activityInputBlock">Skip to activity creation form</a>	
		<a href="#activityDisplayBlock">Skip to list of existing activities</a>
	</div>

<div id="doc">	
	<div id="hd">	
		<div id="branding">
			<h1><?=$projectTitle; ?></h1>
			<a href="?source=<?=strtolower($source); ?>"><img src="includes/images/logo.png" alt="" /></a>
		</div>
		<div id="activityInputBlock">	
		<?php if ($admin): ?> 
		<div id="administration">
			<ul>
				<li><a href="admin.php">Manage Posts</a></li>
				<li><a href="stats.php">View Statistics</a></li>
			</ul>
		</div>
		<?php endif; ?>
			<?php
			// display information about whether or not you are logged in
			// and set form action appropriately

			if ($userName) {
				print "<p id=\"credentials\">Logged in as <span id=\"user_name\">$userName</span>.</p>\n";
				print "<form method=\"post\" id=\"activityInput\" action=\".\">\n";
			} else {
				print "<p id=\"credentials\">You are not logged in. <a href=\"$authURL\">Log in</a></p>\n";
				print "<form method=\"post\" id=\"activityInput\" action=\"includes/login.php\">\n";
			}
			?>						
				<fieldset>
					<input type="hidden" name="source" value="<?=$source; ?>" />
					<input type="hidden" name="building" value="<?=$BUILDING; ?>" />
					<input type="hidden" name="view" value="default" />
					<input type="text" id="activityInputBox" maxlength="89" name="description" />
					<?=$selectMarkup; ?>
					<div id="form_submit">
						<button type="submit">Post</button>
					</div>
				</fieldset>
			</form>
		</div> 
	</div>
	<div id="bd">
		<div id="activityDisplayBlock">
			 
			<?php if($currentActivities): ?>
				<?php
					foreach($currentActivities as $activity) {
						renderActivity($activity);
					}
				?>
			<?php else: ?>
				<div id="gfPromo">
					<h3>Use <?=$projectTitle; ?> to broadcast your location so that others can find you.</h3>
					<p>Your own promotional text goes here.</p>
				</div>
			<?php endif; ?>	
			</div>
	</div>
	<div id="ft">
		<ul>
			<li><a id="faqlink" href="#faq">About <?=$projectTitle; ?></a></li>
			<li><a id="toslink" href="#tos">Terms of Service</a></li>
		</ul>
	</div>
</div>		
<div id="locationSelector" title="Choose a Location">
	<div id="locationList">	
		<?=$floorLocationsMarkup; ?>
	</div>
	<div id="locationDirections">
		<?=$directionsMarkup; ?>
	</div>	
</div>
<div id="popups">
	<div id="nodescription" title="<?=$projectTitle; ?>">Please enter a description of your group, then click "Post."</div>
	<div id="tos" title="Terms of Service"><?=$TOS; ?></div>
	<?=$directionsMarkup; ?>
	<div id="reportConfirmation" title="Activity Reported"><?=$ReportMessage; ?></div>
	<div id="faq" title="About <?=$projectTitle; ?>"><?=$FAQ; ?></div>
	<div id="report" title="<?=$projectTitle; ?>">
		<h2>Report this post?</h2>
	</div>
	<div id="delete" title="<?=$projectTitle; ?>">
		<h2>Remove your activity?</h2>
	</div>
</div>
<div id="source"><?=$source; ?></div>
</body>
</html>