<?php

$host = "127.0.0.1";
$user = "root";
$passw = "12345";
$db = "sqli_benchmark";
$table = "generation";

$connection = mysql_connect($host, $user, $passw);
$id = mysql_real_escape_string($_GET['id']);
mysql_select_db($db, $connection);
$query = "SELECT process, max_num_of_tests FROM $table WHERE `id`=$id";
$result = mysql_query($query, $connection);
$result = mysql_fetch_row($result);

$process = (int)$result[0];
$max_num_of_tests = (int)$result[1];
$progress = 0;
if($process != 0) {
    $progress = floor(($process / $max_num_of_tests) * 100);
}
if($progress == 0) {
    $progress = 1;
}

$json_data = array();
$json_data['progress'] = $progress;
//$json_data['process'] = $process;
//$json_data['num'] = $max_num_of_tests;

$json_data = json_encode($json_data);
echo $json_data;
