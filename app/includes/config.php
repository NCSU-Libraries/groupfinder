<?php

// DATABASE CONFIGURATION
$dbhost = "localhost";
$dbname = "groupfinder";
$dbuser = "groupfinder";
$dbpassword = "sup3r.s33kr1t";
// displayed when unable to connect to database.
$dberror = "Error connecting to database.";

// AUTHENTICATION
// URL user is directed to in order to authenticate
$authURL = "";

// ACTIVITY MODERATION/ADMINISTRATION
// comma delimited list of email addresses who will be notified when a user reports an 
// inappropriate activity
$moderators = "";
// inappropriate email reports will have this address in the "from" field
$internalEmail = "";

// LOG FILE
// location must be writeable by php
$logfile = 'log.txt';
// sources of activity for log 
$legalSources = array('web');



// CONTROL OF ACTIVITY DISPLAY TIMING
// controls how long an activity posted to the system will be displayed
$MAX_ACTIVITY_AGE_IN_HOURS = 4;
// for activities that start in the future, number of minutes before their start time that they 
// will begin displaying
$LEAD_TIME_IN_MINUTES = 15;
// time interval to elapse before a user is permitted to post an exact duplicate activity
// to prevent reloading of page creating duplicate posts.
$DUPLICATE_THRESHOLD_IN_MINUTES = 10;

// default building, used for db writing and querying
$BUILDING = "SMPL";

// max length of activity string
$activityMaxLength = 140;

// CONTROL OF DISPLAY AND BRANDING

// branding name
$projectTitle = "GroupFinder";
// used in title element
$organizationName = "Your Organization Here";
// Terms of Service
$TOS = "Your Terms of Service go here.";
// FAQ
$FAQ = "Your FAQ goes here.";
// Use a common starting point for directions to all points in your building.
$directionsOrigin = "Common Starting Point";
// message displayed to a user who reports an inappropriate activity
$ReportMessage = "$organizationName staff have been notified and will review this entry to be sure it complies with our Terms of Service.";

// CONSTANTS
$TAB = chr(9);
$SECONDS_PER_MINUTE = 60;
$SECONDS_PER_HOUR = 3600;
$MINUTES_PER_HOUR = 60;
// used for statistics display
$DOWDisplay = array("undefined", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

?>