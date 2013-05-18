<?php

require_once "../mytests/connection.php";

if(!isset($_POST['launch'])) {
    die('you should specify launch id as POST param');
}

$launch_id = mysql_real_escape_string($_POST['launch']);

$query_launch_check = "SELECT 1 FROM $launch_table ".
			"WHERE launch_id='$launch_id'";

$result_launch_check = mysql_query($query_launch_check, $connection);
if(mysql_num_rows($result_launch_check) == 0) {
    die('there is no launch with such id!');
}

$query_num_of_tests = "SELECT num_of_tests FROM $mytester_table mt ".
	"INNER JOIN $generation_table gt ON(mt.generation_id = gt.id)".
	"WHERE mt.launch_id='$launch_id'";
$result_num_of_tests = mysql_query($query_num_of_tests, $connection);
$num_of_tests = (int) mysql_fetch_row($result_num_of_tests)[0];

$prog = array();
$query_scanners_pr = "SELECT scanner_id, num_of_tests ".
    "FROM $launch_table WHERE launch_id='$launch_id'";
$result_scanners_pr = mysql_query($query_scanners_pr, $connection);
for($i = 0; $i < mysql_num_rows($result_scanners_pr); $i++) {
    $row = mysql_fetch_row($result_scanners_pr);
    $scanner_id = (int)$row[0];
    $cur_num_of_tests = (int)$row[1];
    $progress = 1;
    if($cur_num_of_tests != 0) {
	$progress = floor(($cur_num_of_tests / $num_of_tests) *100);
    }
    $prog[$scanner_id] = $progress;
}

echo json_encode($prog);
