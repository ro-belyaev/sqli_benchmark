<?php

//apache_setenv('no-gzip', 1);
ini_set('zlib.output_compression', 0);

include_once('check_dependences.php');
$nodes_from_client = $_POST['tests'];
$xml_string = file_get_contents('./all_nodes.xml');
$xml = new SimpleXMLElement($xml_string);

check_dependences($nodes_from_client, $xml);

require_once('make_slice.php');
$xml_slice = make_slice($nodes_from_client, $xml_string);
//var_dump($xml_slice);

$criterions = array();

foreach($nodes_from_client as $node) {
    $pattern = "/^(.+)_(.+)$/";
    preg_match($pattern, $node, $matches);
    $node_id = $matches[1];
    $node_value = $matches[2];
    if(!in_array($node_id, array_keys($criterions))) {
	$criterions[$node_id] = array($node_value);
    }
    else {
	$criterions[$node_id][] = $node_value;
    }
}
include_once('sets_new.php');


$max_num_of_tests = count($confs) * count($filters) * count($processes) * count($outputs) * count($templates);
$process = 0;
define('STATE_PROGRESS', 0);
define('STATE_COMPLETE', 1);
define('STATE_CANCEL', 2);

$host = "127.0.0.1";
$login = "root";
$passw = "12345";
$db = "sqli_benchmark";
$table = "generation";
$connection = mysql_connect($host, $login, $passw);
mysql_select_db($db, $connection);
$xml_slice = mysql_real_escape_string($xml_slice);
$query = "INSERT INTO $table (`max_num_of_tests`, `process`, `state`, `xml`, `generation_time`)".
	" VALUES($max_num_of_tests, $process, ". STATE_PROGRESS  .", '$xml_slice', now())";
mysql_query($query, $connection);
$id = mysql_insert_id($connection);

define('DIR_NAME', "$id");
mkdir("./tests/$id");
//sleep(5);



/*
ob_end_clean();

header("Connection: close");
*/


ignore_user_abort();
set_time_limit(0);

//echo ini_get('output_buffering') ."\n";
//echo ini_get('zlib.output_compression') ."\n";



ob_start();
//$response_array = array('generation_id' => $id);

echo $id;

header("Content-Length: ". strlen((string)$id));



//header("Location: http://localhost/generation/generation.html?id=$id");
//header('Content-Encoding: none');//disable apache compressed
//header( 'Content-type: text/html; charset=utf-8' );
ob_end_flush();
flush();
//sleep(6);
//ob_start();

require_once('master_new.php');

/*
if(!($handle = fopen('./tmpFile.txt', 'w'))) {
    die("Can't create file");
}

foreach($confs as $conf) {fwrite($handle, $conf);fwrite($handle, "\n");}
fwrite($handle, "\n\n\n\n\n\n\n\n\n\n");
foreach($filters as $filter) {fwrite($handle, $filter);fwrite($handle, "\n");}
fwrite($handle, "\n\n\n\n\n\n\n\n\n\n");
foreach($processes as $process) {fwrite($handle, $process);fwrite($handle, "\n");}
fwrite($handle, "\n\n\n\n\n\n\n\n\n\n");
foreach($outputs as $output) {fwrite($handle, $output);fwrite($handle, "\n");}
fwrite($handle, "\n\n\n\n\n\n\n\n\n\n");
foreach($templates as $template) {fwrite($handle, $template);fwrite($handle, "\n");}


fclose($handle);
*/

