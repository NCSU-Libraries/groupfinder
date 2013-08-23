<?php

// -------------------------------
// Project Name: GroupFinder
// mail.php allows users to report questionable posts for review by moderators
// Author: Joseph Ryan
// -------------------------------

// configuration file
require_once("includes/config.php");

// database and display functions
require_once("includes/functions.php");

$redirectURL = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
$redirectURL = str_replace("mail.php", "", $redirectURL);

if (isset($_GET['description']) && isset($_GET['credits']) && isset($_GET['reporter']) && isset($_GET['source']) && isset($_GET['id'])) {
	$description = $_GET['description'];
	$credits = $_GET['credits'];
	$reporter = $_GET['reporter'];
	$source = $_GET['source'];
	$activityID = (int) $_GET['id'];
	$to = $moderators;
	$subject = "$projectTitle inappropriate use report";
	$from = "From: $projectTitle <$internalEmail> \r\n";
	$message = "Description: " . $description . "\n";
	$message .= "Creator Information: " . $credits . "\n\n";
	$message .= "Reported by: " . $reporter . "\n";
	$message .= "Time of report: " . date("D, M j g:ia");
	$message .= "\n\n";
	$message .= "If this message needs to be moderated, visit\n";
	$message .= "$redirectURL \n\n";
	$message .= "Log in using the 'Log In' link at the top of the page.\n";
	$message .= "Then click 'Manage Posts.'";
	if (mail($to, $subject, $message, $from)) {
		$groupfinderDB = groupfinderDBConnect();
		$userID = userLookup($groupfinderDB, $reporter);
		mysql_close($groupfinderDB);
		logActivity($userID, "REPORT", $activityID, $source);
		print "Success";
	} else {
		print "Error. Please check that your email addresses are formatted properly.";
	}
} else {
	// send user back where they came from to try again
	header("location:$redirectURL");
}
?>