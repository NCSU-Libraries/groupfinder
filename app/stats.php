<?php

// -------------------------------
// Project Name: GroupFinder
// Displays use statistics. Processes log file and queries database
// Author: Joseph Ryan
// Date: updated January 2010
// -------------------------------

// for debugging
// error_reporting(E_ALL);
// ini_set('display_errors','On');



// configuration file
require_once("includes/config.php");

//functions file
require_once("includes/functions.php");
// authorization to view page
$authorized = false;
$userName = getCurrentUser();

$groupfinderDB = groupfinderDBConnect();
if ($userName && isAdmin($groupfinderDB, $userName)) {
	$authorized = true;
} 

// date range selection
if (isset($_GET['start'])) {
	$start = $_GET['start'];
	$default = false;
} else {
	// 12 AM 30 days ago
	$default = true;
	$start = mktime(0,0,0, date("n"), date("j")-30, date("Y"));
}
if (isset($_GET['end'])) {
	$end = $_GET['end'];
	$default = false;
} else {
	// today at 11:59:59
	$default = true;
	$end =  mktime(23,59,59, date("n"), date("j"), date("Y"));
}
// if illegal range sent, set range to last 30 days
if ($end < $start) {
	// 12 AM 30 days ago
	$default = true;
	$start = mktime(0,0,0, date("n"), date("j")-30, date("Y"));
	// today at 11:59:59
	$end =  mktime(23,59,59, date("n"), date("j"), date("Y"));
}


if ($authorized) {
	// process log file 
	$lines = file($logfile);
	$pattern = '/(\d+)\t(\w+)\t(\w+)\t(\w+)\t(\w+)/';
	foreach ($lines as $line_num => $line) {
		$line = str_replace("N/A", "NA", $line);
		preg_match($pattern, $line, $matches);
		$timestamp = $matches[1];
		// process only log entries that fall within specified time constraints
		if ($timestamp >= $start && $timestamp <= $end) {
			$action = $matches[3];
			$source = $matches[5];
			if ($action == "VIEW") {
				$views[$source]++;
			}
			$actions[$action]++;
			if ($action == "CREATE") {
				$sources[$source]++;
				$logfileTotal++;
			}
		}
	}



	
	
	$query = "SELECT UNIX_TIMESTAMP(activity.created), UNIX_TIMESTAMP(activity.start_time), UNIX_TIMESTAMP(activity.end_time), activity.user_id, activity.description, user.username, user.lastname, user.firstname, activity.location_id, location.name
					FROM activity, location, user
					WHERE activity.location_id = location.id AND activity.user_id = user.id
					AND activity.enabled=1
					AND UNIX_TIMESTAMP(activity.created) >= $start 
					AND UNIX_TIMESTAMP(activity.created) <= $end
					ORDER BY activity.start_time DESC";					
	$result = mysql_query($query, $groupfinderDB);
	if ($result && mysql_num_rows($result) != 0) {
		while ($row = mysql_fetch_row($result)) {
			$created = date("Y-m-d", $row[0]);
			$createdTime = date("H.i", $row[0]);
			$DOWcreated = date("w", $row[0]) + 1; // php format of date (w) sets 0 for sunday, need it to be 1
			$startTime = $row[1];
			$DOWdisplayed = date("w", $row[1]) + 1;
			$displayed = date("Y-m-d",$row[1]);
			$start_hour = date("H.i", $row[1]);
			$endTime = $row[2];
			$end_hour = date("H.i", $row[2]);
			$user_id = $row[3];
			$description = $row[4];		
			$username = $row[5];
			$lastname = $row[6];
			$firstname = $row[7];
			if ($lastname && $firstname) {
				$name = $lastname . ", " . $firstname;
			} else {
				$name = $username;
			}
			$location_id = $row[8];
			$location_name = $row[9];
			if ($createdTime - floor($createdTime) >= .3) {
				$hour = floor($createdTime) + 1;
				$createdTimes[$hour]++;
			} else {
				$hour = floor($createdTime);
				$createdTimes[$hour]++;
			}
			$DOW[$DOWcreated]['created']++;
			$DOW[$DOWdisplayed]['displayed']++;
			$totalPosts++;
			$locationInfo[$location_id]['use_count']++;
			$locationInfo[$location_id]['name'] = $location_name;
			$activityInfo[$created]['created_count']++;
			$activityInfo[$displayed]['displayed_count']++;
			$userInfo[$user_id]['post_count']++;
			$userInfo[$user_id]['username'] = $username; 
			$userInfo[$user_id]['name'] = $name;
			
			// store display duration info
			if ($end_hour > $start_hour) {
				for ($i=floor($start_hour);$i<=ceil($end_hour);$i++) {
					if ($i >= $start_hour && $i<=$end_hour) {
						$displayTime[$i]++;
					}
				}
			} else {
				// activities that cross over midnight require alternate handling
				// up to midnight
				for ($i=floor($start_hour); $i<24;$i++) {
					if ($i >= $start_hour && $i<=23) {
						$displayTime[$i]++;
					}
				}
				// midnight through end of activity
				for ($i=0;$i<=ceil($end_hour);$i++) {
					if ($i>=0 && $i<=$end_hour) {
						$displayTime[$i]++;
					}
				}
			}
			$activities[] = array("description" => $description, "location_name" => $location_name,  "start_time" => $startTime, "end_time" => $endTime, "lastname" => $lastname, "firstname" => $firstname, "username" => $username, "created" => $created);
		}
	} 
}
mysql_close($groupfinderDB);

// variable to store js for output
$rows = "";
$currentTimestamp = $start;
while (date("z", $currentTimestamp) <= date("z", $end)) {
	$date = date("Y-m-d", $currentTimestamp);
	$timeInfo = explode("-", $date);
	$curYear = $timeInfo[0];
	$curMonth = $timeInfo[1] -1; // javascript's date object uses a 0-indexed month counter
	$curDay = $timeInfo[2];
	$numCreated = $activityInfo[$date]['created_count'];
	if (!$numCreated) {
		$numCreated = 0;
	}
	$rows .= "[new Date($curYear, $curMonth ,$curDay), $numCreated, undefined, undefined],\n";
	$currentTimestamp = strtotime('+1 day', $currentTimestamp);
}

// uses by user bar chart
$userRows = "";
arsort($userInfo);
foreach ($userInfo as $userID => $user) {
		$totalUsers++;
		if ($totalUsers <= 100) {
			$userRows .= "[\"" . $user['name'] ."\"," . $user['post_count'] . "],\n";
		}
}

// locations bar chart
$locationRows = "";
arsort($locationInfo);
foreach ($locationInfo as $location) {
	$locationRows .= "[\"" . $location['name'] ."\"," . $location['use_count'] . "],\n";
}

// display time of day
$displayRows = "";
for ($i=0;$i<=23;$i++) {
	if (isset($displayTime[$i])) {
		$displayCount = $displayTime[$i];
	} else {
		$displayCount = 0;
	}
	if (isset($createdTimes[$i]) && $i == 0) {
		$createdCount = $createdTimes[$i] + $createdTimes[24]; // counting algorithm can round up to 24 in some cases HACK
		$totalCreated += $createdCount;
	} elseif (isset($createdTimes[$i])) {
		$createdCount = $createdTimes[$i];
				$totalCreated += $createdCount;
	} else {
		$createdCount = 0;
	}
	
	$timeLabel = $i;
	if ($i == 0) {
		$timeLabel = "12am";
	} elseif ($i > 0 && $i < 12) {
		$timeLabel = $i . "am";
	} elseif ($i == 12) {
		$timeLabel = "12pm";
	} elseif ($i > 12) {
		$timeLabel = ($i - 12) . "pm";
	}
	$displayRows .= "[\"$timeLabel\"," . $displayCount . "],\n";
}



// day of week displayed and created
$DOWRows = "";
for($i=1;$i<=7;$i++) {
	if ($DOW[$i]['created']) {
		$created = $DOW[$i]['created'];
	} else {
		$created = 0;
	}
	$DOWRows .= "['". $DOWDisplay[$i] . "', " . $created . "],\n";
}

//views by type
$viewRows = "";
foreach ($views as $viewname => $count) {
	$viewname = ucfirst(strtolower($viewname));
	$viewRows .= "[\"$viewname\"," . $count . "],\n";
}

//actions by type
$actionRows = "";
foreach ($actions as $actionname => $count) {
	$actionname = ucfirst(strtolower($actionname));
	$actionRows .= "[\"$actionname\"," . $count . "],\n";
}
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
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/jquery-ui.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="includes/jquery-ui-1.8.4.custom.css" type="text/css" />
	<link rel="stylesheet" href="includes/style.css" type="text/css" />
  <script type='text/javascript' src='http://www.google.com/jsapi'></script>
  <script type='text/javascript'>
  	google.load('visualization', '1', {'packages':['annotatedtimeline', 'columnchart', 'piechart', 'imagebarchart', 'imagelinechart', 'imagepiechart']});
		google.setOnLoadCallback(drawChart);
		function drawChart() {
			var data = new google.visualization.DataTable();
			
			data.addColumn('date', 'Date');
			data.addColumn('number', 'Activities Created');
			data.addColumn('string', 'title1');
			data.addColumn('string', 'text1');   
			data.addRows([
				<?=$rows; ?>
			]);
			var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('created_timeline'));
			chart.draw(data, {displayAnnotations: true, title: 'Activities Created'});
			
			data = new google.visualization.DataTable();
      data.addColumn('string', 'User');
      data.addColumn('number', 'Posts');
      data.addRows([
        <?=$userRows; ?>
      ]);
      chart = new google.visualization.LineChart(document.getElementById('posts_by_user'));
      chart.draw(data, {width: 900, height: 400, showCategoryLabels: true, legend: 'none', isVertical: true, isStacked: false, title: 'Uses by User'});
 
			data = new google.visualization.DataTable();
			data.addColumn('string', 'Name');
			data.addColumn('number', 'Uses');
			data.addRows([
				<?=$locationRows; ?>
			]);
			chart = new google.visualization.ColumnChart(document.getElementById('uses_by_location'));
			chart.draw(data, {width: 900, height: 500, legend: 'none', is3D: false, title: 'Uses by Location'});
	
			data = new google.visualization.DataTable();
			data.addColumn('string', 'Hour');
			data.addColumn('number', 'Displayed');
			data.addRows([
				<?=$displayRows; ?>
			]);
			chart = new google.visualization.ColumnChart(document.getElementById('display_by_time_of_day'));
			chart.draw(data, {width: 900, height: 400, is3D: false,  legend: 'none', title: 'Display by Time of Day'});
				
			data = new google.visualization.DataTable();
			data.addColumn('string', 'Day of Week');
			data.addColumn('number', 'Created');
			data.addRows([
				<?=$DOWRows; ?>
			]);

			chart = new google.visualization.ColumnChart(document.getElementById('dayofweek'));
			chart.draw(data, {width: 900, height: 400, isVertical: true, isStacked: false, title: 'Created by Day of Week'});

			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Source');
			data.addColumn('number', 'Number of Views');
			data.addRows([
				<?=$viewRows; ?>
			]);

			var chart = new google.visualization.PieChart(document.getElementById('views'));
			chart.draw(data, {width: 900, height: 400, is3D: false, title: "Views By Source"});			
			
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Source');
			data.addColumn('number', 'Number of Views');
			data.addRows([
				<?=$actionRows; ?>
			]);

			var chart = new google.visualization.PieChart(document.getElementById('actions'));
			chart.draw(data, {width: 900, height: 400, is3D: false, title: 'Actions'});		
			

					
				
		}
  </script>
	<script src="includes/stats.js" type="text/javascript"></script>
	<title><?=$projectTitle; ?> Usage Statistics</title>	

</head>

<body>
	<div id="stats">
	<?php if($authorized): ?>
	<h1><?=$projectTitle; ?> Usage</h1>
	<p><a href="index.php">Return to <?=$projectTitle; ?> home page</a></p>
	<p>Showing usage data from <?php echo date("m/d/y", $start); ?> to <?php echo date("m/d/y", $end); ?>.</p>
	<?php if(!$default): ?>
		<p><a href="stats.php">Show last 30 days of activity</a></p>
	<?php endif; ?>
	<p>Change date:</p>
	<form id="setDateRange" action="stats.php">
		<fieldset>
			Start Date <input type="text" id="start_picker" />
			End Date <input type="text" id="end_picker" />
			<input type="hidden" id="startTimestamp" name="start"  />
			<input type="hidden" id="endTimestamp" name="end" />
			<input type="submit" value="Change dates"/>
		</fieldset>
	</form>
	<?php if($activities): ?>
			<p><?=$totalPosts; ?> total posts created in selected date range.<br />
    	<?=$totalUsers; ?> total users in selected date range.<br />
    	Average: <?php print round($totalPosts/$totalUsers, 2); ?> posts/user.</p>

    	<p><a href="#" id="activityToggle">Show/hide activities posted in this date range.</a></p>
		<table id="statsActivities">
			<tr>
				<td class="header">Date</td>
				<td class="header">Start</td>
				<td class="header">End</td>
				<td class="header">Description</td>
				<td class="header">Location</td>
				<td class="header">Name</td>
			</tr>
		<?php
		foreach ($activities as $activity) {
			print "<tr>\n";
			print "<td>" . date("D, n/j/y", $activity['start_time']) . "</td>\n";
			print "<td>" . date("h:i a", $activity['start_time']) . "</td>\n";
			if ($activity['end_time']) {
				print "<td>" . date("h:i a", $activity['end_time']) . "</td>\n";
			} else {
				print "<td>&nbsp;</td>\n";
			}
			print "<td>" . stripslashes($activity['description']) . "</td>\n";
			print "<td>" . $activity['location_name'] . "</td>\n";
			if ($activity['lastname'] && $activity['firstname']) {
				print "<td>" . $activity['lastname'] . ", " . $activity['firstname'] . "</td>\n";
			} else {
				print "<td>" . $activity['username'] . "</td>\n";
			}
			print "</tr>\n";
		}
		?>
		</table>
		<div class='chart' id='created_timeline' style='width: 800px; height: 350px;'></div>
		<div class='chart' id='posts_by_user'></div>
		<div id='dayofweek'></div>
	  <div id='uses_by_location'></div>
 	  <div id='display_by_time_of_day'></div>
	  <div id='views'></div>
	  <div id='actions'></div>
	  <div id='wcdiv'></div>
	<?php else: ?> 
		<p>No activities in database for selected date range.</p>
	<?php endif; ?>


<?php else: ?>
	<h1>Unauthorized</h1>
	<p>You do not have access to view this page.</p>
	<p><a href="index.php">Return to <?=$projectTitle; ?> home page</a></p>
<?php endif; ?>
</div>
</body>
</html>
