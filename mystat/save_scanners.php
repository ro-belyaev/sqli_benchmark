<?php

if(!isset($_POST['report_id'])) {
    die('you should specify POST-parameter report_id');
}

if(!isset($_POST['scanners'])) {
    die('you should specify POST-parameter scanners');
}

require_once "../mytests/connection.php";

$report_id = mysql_real_escape_string($_POST['report_id']);
$query_check_report_id = "SELECT 1 FROM $report_table ". 
			"WHERE report_id='$report_id'";
$result_check_report_id = mysql_query($query_check_report_id, $connection);
if(mysql_num_rows($result_check_report_id) == 0) {
    die('there is no report with such id!');
}

$query_check_report_state = "SELECT report_state FROM $report_table ".
			    "WHERE report_id='$report_id'";
$result_report_state = mysql_query($query_check_report_state, $connection);
$report_state = mysql_fetch_row($result_report_state)[0];
if($report_state != "classes") {
    die('this report has such state that you can\'t make this action');
}

$scanners_arr = $_POST['scanners'];
if(!is_array($scanners_arr)) {
    die('POST-parameter scanners must be an array of strings!');
}
$query_get_avb_sc = "SELECT sl.scanner_id from $launch_table sl ".
		"INNER JOIN $mytester_table mt USING(launch_id) ".
		"INNER JOIN $report_table rt ON(rt.launch_id=mt.launch_id) ".
		"WHERE report_id='$report_id'";
$result_avb_sc = mysql_query($query_get_avb_sc, $connection);
$available_scanners = array();
for($i = 0; $i < mysql_num_rows($result_avb_sc); $i++) {
    $available_scanners[] = mysql_fetch_row($result_avb_sc)[0];
}
foreach($scanners_arr as $scanner) {
    if(!is_string($scanner)) {
	die('POST-parameter scanners must be an array of strings');
    }
    if(!preg_match("/^(\d+)(\+(\d+))?$/", $scanner, $matches)) {
	die('wrong syntax of scanners array element');
    }
    if(!in_array($matches[1], $available_scanners)) {
	die('wrong scanner id');
    }
    if(isset($matches[3])) {
	if(!in_array($matches[3], $available_scanners)) {
	    die('wrong scanner id');
	}
    }
}

foreach($scanners_arr as $sc) {
   preg_match("/^(\d+)(\+(\d+))?$/", $sc, $matches);
   $scanner_1 = $matches[1];
   $scanner_2 = null;
   if(isset($matches[3])) {
       $scanner_2 = $matches[3];
   }
   $query_save_sc = null;
   if($scanner_2 != null) {
       $query_save_sc = "INSERT INTO $report_scanners_table ".
			"(report_id, scanner1_id, scanner2_id) ".
			"VALUES($report_id, $scanner_1, $scanner_2)";
   } else {
       $query_save_sc = "INSERT INTO $report_scanners_table ".
			"(report_id, scanner1_id) ".
			"VALUES($report_id, $scanner_1)";
   }
   mysql_query($query_save_sc, $connection);
}

$query_change_report_state = "UPDATE $report_table SET report_state='scanners' ".
			    "WHERE report_id='$report_id'";
mysql_query($query_change_report_state, $connection);
echo "OK";
