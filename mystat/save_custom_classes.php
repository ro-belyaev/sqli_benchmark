<?php

require_once "../mytests/connection.php";
require_once "../mytests/constants.php";
require_once "../mytests/check_dependences.php";
require_once "../mytests/make_slice.php";

if(!isset($_POST['classes'])) {
    die('you should send at least one custom class!');
}

if(!isset($_POST['launch_id'])) {
    die('you should specify scanners launch id');
}

$launch_id = mysql_real_escape_string($_POST['launch_id']);
$query_check_launch_id = "SELECT 1 FROM $mytester_table ".
			    "WHERE launch_id=$launch_id";
$check_result = mysql_query($query_check_launch_id, $connection);
if(mysql_num_rows($check_result) == 0) {
    die("there is no such launch!");
}

if(!isset($_POST['classes'])) {
    die("you should specify custom classes as POST parameter");
}
$custom_classes = json_decode($_POST['classes']);
check_custom_classes($custom_classes);
$query_xml_of_launch = "SELECT g.xml FROM $mytester_table m ".
			    "INNER JOIN $generation_table g ".
			    "ON(m.generation_id = g.id) ".
			    "WHERE m.launch_id = $launch_id";
$result = mysql_query($query_xml_of_launch, $connection);
$xml_string = mysql_fetch_row($result)[0];
$simple_xml = new SimpleXMLElement($xml_string);

foreach($custom_classes as $cc) {
    check_dependences($cc->id, $simple_xml);
}

$query_register_report = "INSERT INTO $report_table (launch_id, report_state) ".
			    "VALUES($launch_id, 'classes')";
mysql_query($query_register_report, $connection);

$query_get_report_id = "SELECT report_id FROM $report_table ORDER BY report_id ".
			    "DESC LIMIT 1";
$result_report_id = mysql_query($query_get_report_id, $connection);
$report_id = mysql_fetch_row($result_report_id)[0];

foreach($custom_classes as $cc) {
    $type = mysql_real_escape_string($cc->type);
    $name = mysql_real_escape_string($cc->name);
    $custom_xml_slice = mysql_real_escape_string(make_slice($cc->id, $xml_string));
    $query_add_custom_class = "INSERT INTO $custom_classes_table (report_id, xml_slice, ".
						"type, name_of_class) ".
				"VALUES('$report_id', '$custom_xml_slice', '$type', '$name')";
    mysql_query($query_add_custom_class, $connection);
}


echo $report_id;


function check_custom_classes($custom_classes) {
    if(!is_array($custom_classes)) {
	die("wrong format of custom classes");
    }
    foreach($custom_classes as $custom_class) {
	if(!isset($custom_class->name) || !isset($custom_class->type)
		|| !isset($custom_class->id)) {
	    die("wrong format of custom classes (some field is abcent)");
	}
	if(!is_string($custom_class->name) || !is_string($custom_class->type)
		|| !is_array($custom_class->id)) {
	    die("wrong format of custom classes (wrong format of some field)");
	}
	if($custom_class->type != "vuln" && $custom_class->type != "not-vuln") {
	    die("wrong format of custom classes (wrong value of type field)");
	}
	if(count($custom_class->id) == 0) {
	    die("wrong format of custom classes (no criterions)");
	}
	foreach($custom_class->id as $id) {
	    if(!is_string($id)) {
		die("wrong format of custom class (not string in id arr)");
	    }
	}
    }
}
