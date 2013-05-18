<?php

if(!isset($_POST['report_id'])) {
    die("you should specify POST parameter report_id");
}

require_once "../mytests/connection.php";

$report_id = mysql_real_escape_string($_POST['report_id']);

$query_check = "SELECT 1 FROM $report_table WHERE report_id='$report_id'";
$result_check = mysql_query($query_check, $connection);
if(mysql_num_rows($result_check) == 0) {
    die("no report with such id");
}
$query_state = "SELECT report_state FROM $report_table ".
		"WHERE report_id='$report_id'";
$result_state = mysql_query($query_state, $connection);
$state = mysql_fetch_row($result_state)[0];
if($state != "classes") {
    die("this report has such state that you can't launch".
	" scanners on this report");
}

$scanners_id_arr = array();
$query_scanners = "SELECT sc.scanner_id FROM $launch_table sc ". 
		    "INNER JOIN $report_table r USING(launch_id) ".
		    "WHERE r.report_id='$report_id'";
$result_scanners = mysql_query($query_scanners, $connection);
while($row = mysql_fetch_row($result_scanners)) {
    $id = $row[0];
    if($id != 0) {
	$scanners_id_arr[] = $id;
    }
}
$comb_scanners_id = "";
for($i = 0; $i < count($scanners_id_arr) - 1; $i++) {
    for($j = $i + 1; $j < count($scanners_id_arr); $j++) {
	$comb_scanners_id .= $scanners_id_arr[$i] ."+". $scanners_id_arr[$j] .",";
    }
}
$scanners_id = "";
while(($id = array_shift($scanners_id_arr)) != "") {
    $scanners_id .= $id .",";
}


$xml_scanners = new DOMDocument();
$xml_scanners->load("../mytester/all_scanners.xml");

$xsl = new DOMDocument();
$xsl->load("./get_report_scanners.xsl");

$proc = new XSLTProcessor();
$proc->importStyleSheet($xsl);
$proc->setParameter('', 'scanners', $scanners_id);
$proc->setParameter('', 'comb_scanners', $comb_scanners_id);

$json_data = json_encode($proc->transformToXML($xml_scanners));
echo $json_data;
