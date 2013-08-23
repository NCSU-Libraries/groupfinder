<?php

// -------------------------------
// Project Name: GroupFinder
// File: includes/functions.php
// Description: database and display functions
// Author: Joseph Ryan
// -------------------------------


function getCurrentUser() {
	// insert your own authentication code here
	// if the user is authenticated, return the username, e.g. joeuser
	// if authentication fails, return false
	
	// to test without coding your own authentication system uncomment the following line
	// return "test";
	return false;
}

function logActivity($userID=false, $activityType=false, $activityID=false, $source=false) {
	if ($userID && $activityType && $activityID && $source) {
		global $TAB, $logfile;
		$logEntry = time() . $TAB . $userID . $TAB . $activityType . $TAB . $activityID . $TAB . $source . PHP_EOL;
		if ($logHandle = @fopen($logfile, "a")) {
			fputs($logHandle, $logEntry);
			fclose($logHandle);
			return true;
		} else {
			return false;
		}
  } else {
  	return false;
  }
}


function sanitizeText($groupfinderDB, $unsanitizedText) {
	global $activityMaxLength;
	// remove whitespace
	$sanitizedText = trim($unsanitizedText);
	// remove html etc
	$sanitizedText = strip_tags($unsanitizedText);
	// escape mysql stuff. requires an open mysql connection.
	$sanitizedText = mysql_real_escape_string($unsanitizedText, $groupfinderDB);
	if (strlen($sanitizedText <= $activityMaxLength)) {
		return $sanitizedText;
	} else {
		return false;
	}
}

function groupfinderDBConnect() {
	global $dbhost, $dbname, $dbuser, $dbpassword, $dberror;
  $groupfinderDB = @mysql_connect($dbhost, $dbuser, $dbpassword);
  if ($groupfinderDB && mysql_select_db($dbname)) {
    return ($groupfinderDB);
  } else {
  	die($dberror);
  }
}

function createActivity($groupfinderDB, $source, $description, $locationCode, $creator, $startTime, $endTime=false, $roomReservationResID=false) {
		global $SECONDS_PER_HOUR, $MAX_ACTIVITY_AGE_IN_HOURS, $DUPLICATE_THRESHOLD_IN_MINUTES;	
		$endTimeInterval = $SECONDS_PER_HOUR * $MAX_ACTIVITY_AGE_IN_HOURS;
		$userID = userLookup($groupfinderDB, $creator);
		if (!$userID) {
			$userID = createUser($groupfinderDB, $creator);
		}
		if ($userID) {
			// first check to see if a duplicate activity has recently been submitted 
			// to protect against reload-incurred posts
			$query = "SELECT activity.created					
								FROM activity, location, user
								WHERE activity.location_id = location.id AND activity.user_id = user.id
								AND activity.description = '$description' 
								AND location.code = '$locationCode' 
								AND activity.user_id = '$userID'
								AND TIMESTAMPDIFF(MINUTE, activity.start_time, NOW()) < $DUPLICATE_THRESHOLD_IN_MINUTES"; 
			$result = mysql_query($query, $groupfinderDB);
			if ($result && mysql_num_rows($result) != 0) {
				return 0;
			}
			$endTime = $startTime + $endTimeInterval;
			$query = "INSERT INTO activity (location_id, description, user_id, enabled, start_time, end_time) 
							VALUES ((SELECT id FROM location WHERE code='$locationCode' and enabled=1 LIMIT 1), '$description', $userID, 1, FROM_UNIXTIME($startTime), FROM_UNIXTIME($endTime))";
			$result = mysql_query($query, $groupfinderDB);
			if ($result) {
				$activityID = mysql_insert_id($groupfinderDB);
				logActivity($userID, "CREATE", $activityID, $source);
				return $activityID;
			} else {
				return false;
			}
		} else {
			return 0;
		}
}

function disableActivity($groupfinderDB, $activityID) {
	$query = "UPDATE activity
						set enabled=0
						WHERE id=$activityID";
	$result = mysql_query($query, $groupfinderDB);
	if ($result) {
		return true;
	} else {
		return false;
	}
}

function enableActivity($groupfinderDB, $activityID) {
	$query = "UPDATE activity
						set enabled=1
						WHERE id=$activityID";
	$result = mysql_query($query, $groupfinderDB);
	if ($result) {
		return true;
	} else {
		return false;
	}
}

function getLocationList($groupfinderDB, $building) {
	$query = "SELECT name, code, floor, directions
						FROM location
						WHERE enabled=1 and building='$building'
						ORDER BY floor ASC, display_order DESC";
	$result = mysql_query($query, $groupfinderDB);
  if ($result && mysql_num_rows($result) != 0) {
    while ($row = mysql_fetch_row($result)) {
      $name = $row[0];
      $code = $row[1];
      $floor = $row[2];
      $directions = $row[3];
      $locations[] = array("name" => $name, "code" => $code, "floor" => $floor, "directions" => $directions);
    }
    return $locations;
  } else {
		return false;
	}
}

function getCurrentActivities($groupfinderDB, $building, $maxAgeInHours=8) { 
	global $MINUTES_PER_HOUR, $LEAD_TIME_IN_MINUTES;
	$LEAD_TIME_IN_MINUTES = $LEAD_TIME_IN_MINUTES * -1;
	$query = "SELECT activity.description, UNIX_TIMESTAMP(activity.start_time), UNIX_TIMESTAMP(activity.end_time), location.name, location.code, user.lastname, user.firstname, user.username, activity.id
					FROM activity, location, user
					WHERE activity.location_id = location.id AND activity.user_id = user.id
					AND activity.enabled = 1
					AND user.enabled = 1
					AND location.building = '$building'
					AND activity.end_time > NOW()
					AND TIMESTAMPDIFF(MINUTE, activity.start_time, NOW()) > $LEAD_TIME_IN_MINUTES
					ORDER BY activity.start_time DESC";					
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
			$userName = $row[7];
			$activityID = $row[8];
			$currentActivities[] = array("description" => $description, "location_name" => $location, "location_code" => $locationCode, "start_time" => $startTime, "end_time" => $endTime, "lastname" => $lastname, "firstname" => $firstname, "userName" => $userName, "id" => $activityID);
		}
		return $currentActivities;
	}  else {
		return false;
	}
}

function activityLookup($groupfinderDB, $activityID) {
	$query = "SELECT activity.description, UNIX_TIMESTAMP(activity.start_time), UNIX_TIMESTAMP(activity.end_time), location.name, location.code, user.lastname, user.firstname, user.username, activity.id
						FROM activity, location, user
						WHERE activity.location_id = location.id AND activity.user_id = user.id
						AND activity.id = $activityID";
	$result = mysql_query($query, $groupfinderDB);
	if ($result && mysql_num_rows($result) != 0) {
		$row = mysql_fetch_row($result);
		$description = stripslashes($row[0]);
		$startTime = $row[1];
		$endTime = $row[2];
		$location = $row[3];
		$locationCode = $row[4];
		$lastname = $row[5];
		$firstname = $row[6];
		$userName = $row[7];
		$activityID = $row[8];
		$activityInfo = array("description" => $description, "location_name" => $location, "location_code" => $locationCode, "start_time" => $startTime, "end_time" => $endTime, "lastname" => $lastname, "firstname" => $firstname, "userName" => $userName, "id" => $activityID);
		return $activityInfo;
	} else {
		return false;
	}
}

function userLookup($groupfinderDB, $userName) {
	$query = "SELECT id
						FROM user
						WHERE username = '$userName'";
	$result = mysql_query($query, $groupfinderDB);
	if ($result && mysql_num_rows($result) != 0) {
		$row = mysql_fetch_row($result);			
		$userID = $row[0];
		return $userID;
	} else {
		return false;
	}
}

function createUser($groupfinderDB, $userName) {
	$query = "INSERT INTO user (username, enabled, created) 
				VALUES ('$userName', 1, NOW())";
	$result = mysql_query($query, $groupfinderDB);
	if ($result) {
		$userID = mysql_insert_id($groupfinderDB);
		return $userID;
	} else {
		return false;
	} 
}

function isAdmin($groupfinderDB, $userName) {
	$admin = false;
	$query = "SELECT admin
						FROM user
						WHERE username='$userName'";
	$result = mysql_query($query, $groupfinderDB);					
	if ($result && mysql_num_rows($result) != 0) {
		$row = mysql_fetch_row($result);
		$admin = $row[0];
	} 
	if ($admin) {
		return true;
	} else {
		return false;
	}
}

// display function

function renderActivity($activity, $mode="standard", $numActivities=false) {
		global $userName;
		$displayName = $activity['userName'];
		if ($activity['firstname'] && $activity['lastname']) {
			$displayName = substr($activity['firstname'], 0, 1) . ". " . $activity['lastname'];
		}
		$displayTime = date("g:ia", $activity['start_time']);
		if ($mode =="eboard") {
			if ($numActivities >= 1 && $numActivities <= 5) {
				$class = "largest";
			} elseif ($numActivities > 5 && $numActivities <= 10) {
				$class = "larger";
			} elseif ($numActivities > 10 && $numActivities <= 15) {
				$class = "large";
			} elseif ($numActivities > 15 && $numActivities <= 20) {
				$class = "medium";
			} elseif ($numActivities > 20 && $numActivities <= 25) {
				$class = "small";
			} else {
				$class = "smallest";
			}
			print "<div class=\"$class\">\n";
			print "<span class=\"activityDescription\">" . $activity['description']. "</span><span class=\"delimiter\"> @ </span><span class=\"activityLocation\">" . $activity['location_name'] . "</span>";
			print "<br />";
			print "<span class=\"credits\">" . $displayName . " (" . $displayTime . ")</span>";
			print "</div>\n";
		} else {
			// default case, used for web version
			$metadata = "<p class=\"creator\"><span class=\"activityCreator\">" . $displayName . "</span> <span class=\"activityTime\">" . $displayTime . "</span></p>\n";
			if ($userName) {
				$metadata .= "<span class=\"ui-icon ui-icon-info\"></span><p class=\"activityTools\"><span class=\"ui-icon ui-icon-alert\"></span><a href=\"#\" class=\"report\">Report</a>";
				if ($userName == $activity['userName']) {
					$metadata .= " <span class=\"ui-icon ui-icon-circle-close\"></span><a href=\"#\" class=\"delete\">Remove</a>";
				}
				$metadata .= "</p>\n";
			}
			print "<div class=\"activity\" id=\"" . $activity['id'] . "\">\n";
			print "<h3><span class=\"activityDescription\">" . $activity['description']. "</span>";
			print " <span class=\"delimiter\">@</span> ";
			print "<span class=\"activityLocation\" title=\"" . $activity['location_code'] . "\">" . $activity['location_name'] . "</span></h3>\n";
			print $metadata;
			print "</div>\n";
		}
}

