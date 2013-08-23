<?php

// -------------------------------
// Project Name: GroupFinder
// Purpose of this code: Read-only display of current activities formatted for large screen display
// Author: Joseph Ryan
// -------------------------------

// for debugging
// error_reporting(E_ALL);
// ini_set('display_errors','On');

// configuration file
require_once("includes/config.php");

//project functions file
require_once("includes/functions.php");
$groupfinderDB = groupfinderDBConnect();


// get list of current activities for current building
$currentActivities = getCurrentActivities($groupfinderDB, $BUILDING, $MAX_ACTIVITY_AGE_IN_HOURS);

// close db connection
mysql_close($groupfinderDB);

$numActivities = count($currentActivities);


// Construct the refresh URL for eboard
$request_time = mktime();
$refresh_url = $_SERVER['PHP_SELF'] . "?t=$request_time";

?>

<?php if ($currentActivities): ?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" id="eboard">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />
	<meta http-equiv="PRAGMA" content="NO-CACHE" />
	<meta name="ROBOTS" content="NONE" /> 
	<meta http-equiv="Refresh" content="10; url=<?=$refresh_url?>" />
	<title><?=$projectTitle; ?> : <?=$organizationName; ?></title>	
	<link rel="stylesheet" href="includes/style.css" type="text/css" />
</head>
<body>
	<div id="eboard_header">
		<h1><?=$projectTitle; ?></h1>
		<img src="includes/images/logo.png" alt="" />
		<ul>
			<li>Create study groups once.</li>
			<li>View them everywhere.</li>
			<li>Only at <?=$organizationName; ?>.</li>
		</ul>
		<div class="clear"></div>
	</div>
	<div id="activities">
	<?php		
		$mode = "eboard";
		foreach($currentActivities as $activity) {
			renderActivity($activity, $mode, $numActivities);
		}
	?>
	</div>
</body>
</html>

<?php else: ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!-- eboard full screen logo display when no current activities -->
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="CACHE-CONTROL" content="NO-CACHE" />
		<meta http-equiv="PRAGMA" content="NO-CACHE" />
		<meta name="ROBOTS" content="NONE" /> 
		<meta http-equiv="Refresh" content="10; url=<?=$refresh_url?>" />	
		<title><?=$projectTitle; ?></title>
		<style type="text/css">	
			html, body, img {margin: 0 0 0 0; padding: 0 0 0 0;}
			html, body {background: #231fa3; }
		</style>
	</head>
	<body>
		<img id="noActivitiesLogo" src="includes/images/eboard.png" alt="" />
	</body>
</html>
	
<?php endif; ?>