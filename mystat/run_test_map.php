<?php
error_reporting(E_ALL);
set_include_path('.');


if (!isset($argv[1])) {
    die("bad args\n");
}
require_once "../mytests/connection.php";
$arg = $argv[1];
preg_match('%^.+/(.+)$%', $arg, $m);
$launch_id = $m[1];
$query_generation_id = "SELECT generation_id FROM $mytester_table WHERE launch_id='$launch_id'";
$res = mysql_query($query_generation_id, $connection);
$generation_id = mysql_fetch_row($res)[0];
define('DIR_NAME', $generation_id);


require_once "../mytests/inc.php";
require_once "../mytester/testres.php";
require_once "classes.php";

require_once "./map_test_to_class.php";


$resd = $argv[1];

$indf = file("$resd/index.txt");
if ($indf === false) die("error opening '$resd/scanners.txt'");


$file_conf_pass = "conf_pass.txt";
$file_conf_fail = "conf_fail.txt";
$file_process_pass = "process_pass.txt";
$file_process_fail = "process_fail.txt";
$file_filter_pass = "filter_pass.txt";
$file_filter_fail = "filter_fail.txt";
$file_output_pass = "output_pass.txt";
$file_output_fail = "output_fail.txt";
$file_template_pass = "template_pass.txt";
$file_template_fail = "template_fail.txt";

foreach ($indf as $t) {
    $t = trim($t);
    if ($t == '') continue;
    if (!preg_match('%^(.*?)\t(.*)$%', $t, $m)) {
        die("bad line '$t'");
    }
    $code = $m[2];
    echo $code ."\n";
    $test = eval("return $code;");
    
    if(map_configuration($test)) {
	//echo "pass configuration\n";
	file_put_contents($file_conf_pass, $code ."\n\n", FILE_APPEND);
    } else {
	//echo "fail configuration\n";
	file_put_contents($file_conf_fail, $code ."\n\n", FILE_APPEND);
    }
    
    if(map_output($test)) {
	//echo "pass output\n";
	file_put_contents($file_output_pass, $code ."\n\n", FILE_APPEND);
    } else {
	//echo "fail output\n";
	file_put_contents($file_output_fail, $code ."\n\n", FILE_APPEND);
    }
    
    if(map_template($test)) {
	//echo "pass template\n";
	file_put_contents($file_template_pass, $code ."\n\n", FILE_APPEND);
    } else {
	//echo "fail template\n";
	file_put_contents($file_template_fail, $code ."\n\n", FILE_APPEND);
    }
    
    if(map_filter($test)) {
	//echo "pass filter\n";
	file_put_contents($file_filter_pass, $code ."\n\n", FILE_APPEND);
    } else {
	//echo "fail filter\n";
	file_put_contents($file_filter_fail, $code ."\n\n", FILE_APPEND);
    }

    if(map_process($test)) {
	//echo "pass process\n";
	file_put_contents($file_process_pass, $code ."\n\n", FILE_APPEND);
    } else {
	//echo "fail process\n";
	file_put_contents($file_process_fail, $code ."\n\n", FILE_APPEND);
    }
}
