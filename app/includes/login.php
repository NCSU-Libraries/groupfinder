<?php

// configuration file
require_once("config.php");

$redirectURL = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
$redirectURL = str_replace("includes/login.php", "", $redirectURL);



if (isset($_POST['description']) && isset($_POST['location'])) {
	// store variables
	session_start();
	$_SESSION['activityInfo'] = array();
	$_SESSION['activityInfo']['description'] = $_POST['description'];
	$_SESSION['activityInfo']['location'] = $_POST['location'];
	// send user to authenticate
	header("location:$authURL");
} else {
	// send user back where they came from to try again
	header("location:$redirectURL");
}
