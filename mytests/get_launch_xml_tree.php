<?php

require_once "./connection.php";
require_once "./transform_xml.php";


if(!isset($_GET['launch_id'])) {
    die("you should specify id of scanners launch!");
}

$launch_id = mysql_real_escape_string($_GET['launch_id']);


$query_check = "SELECT 1 FROM $mytester_table WHERE launch_id='$launch_id'";
$result_check = mysql_query($query_check, $connection);
if(mysql_num_rows($result_check) == 0) {
    die("no launch with such id!");
}

//              CHECK STATE OF LAUNCH HERE !!!

$query_xml = "SELECT g.xml FROM $mytester_table m ".
	    "INNER JOIN $table g ON(m.generation_id=g.id) ".
	    "WHERE m.launch_id='$launch_id'";
$result_xml = mysql_query($query_xml, $connection);
$xml_string = mysql_fetch_row($result_xml)[0];

echo transform_xml($xml_string);

