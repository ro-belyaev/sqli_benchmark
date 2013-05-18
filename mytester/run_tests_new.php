<?php
error_reporting(E_ALL);

require_once '../mytests/connection.php';
require_once '../mytests/constants.php';

if(!isset($_POST['set'])) {
    die("you should specify a test set\n");
}
$set_id = mysql_real_escape_string($_POST['set']);
define('DIR_NAME', $set_id);
define('GENERATION_ID', $set_id);

ignore_user_abort();
set_time_limit(0);

$id_array = $_POST['scanner_id'];
$scanners_info = array();

class ScannerInfo {
    public $label;
    public $version;
    function __construct($label, $version) {
	$this->label = $label;
	$this->version = $version;
    }
}

foreach($id_array as $id) {
    $info = explode('-', $id);
    $scanners_info[] = new ScannerInfo($info[0], $info[1]);
}

function check_scanners($scanners_info) {
    $xml = new SimpleXMLElement('./all_scanners.xml', NULL, TRUE);
    $sc_n = count($scanners_info);

    for($i = 0; $i < $sc_n; $i++) {
	$scanner_info = $scanners_info[$i];
	$label = $scanner_info->label;
	$version = $scanner_info->version;
	$scanner = $xml->xpath("/scanners/scanner[@label='$label' and @version='$version']");
	if(!$scanner) {
	    die("You specified wrong scanner\n");
	} else if($scanner[0]->attributes()->ready == "no") {
	    die("One of specified scanners is not prepared\n");
	}

	for($j = $i + 1; $j < $sc_n; $j++) {
	    $some_scanner_info = $scanners_info[$j];
	    $some_label = $some_scanner_info->label;
	    $some_version = $some_scanner_info->version;
	    if($label == $some_label && $version == $some_version) {
		die("You duplicated some scanner\n");
	    }
	}
    }
}

function map_scanners($scanners_info) {
    $scanners = array();
    $scanners[] = new ZeroScanner();
    foreach($scanners_info as $scanner_info) {
	$label = $scanner_info->label;
	$version = $scanner_info->version;

	if($label == 'wapiti' && $version == '2_2_1') {
	    $scanners[] = new Scanner_wapiti_2_2_1();
	} else if($label == 'skipfish' && $version == '1_81b') {
	    $scanners[] = new Scanner_skipfish_1_81b();
	} else if($label == 'skipfish' && $version == '2_03b') {
	    $scanners[] = new Scanner_skipfish_2_03b();
	} else if($label == 'sqlmap' && $version == '0_8') {
	    $scanners[] = new Scanner_sqlmap_0_8(); //???
	} else if($label == 'sqlmap' && $version == '0_9') {
	    $scanners[] = new Scanner_sqlmap_0_9();
	} else if($label == 'sqlmap' && $version == 'r4365') {
	    $scanners[] = new Scanner_sqlmap_r4365();
	} else if($label == 'sqlmap' && $version == 'dev') {
	    $scanners[] = new Scanner_sqlmapdev();
	} else if($label == 'bsqlbf' && $version == '2_6') {
	    $scanners[] = new Scanner_bsqlbf2();
	} else if($label == 'w3af' && $version == '1_0_rc5') {
	    $scanners[] = new Scanner_w3af_1_0_rc5();
	} else if($label == 'w3af' && $version == '1_0_stable') {
	    $scanners[] = new Scanner_w3af_1_0_stable();
	} else if($label == 'w3af' && $version == '1_1') {
	    $scanners[] = new Scanner_w3af_1_1();
	} else if($label == 'arachni' && $version == '0_3') {
	    $scanners[] = new Scanner_arachni_0_3();
	} else {
	    die("I made some mistake in scanners mapping");
	}
    }
    return $scanners;
}


require_once "tester.php";

if (isset($argv[1])) {
    $resd = $argv[1];
    echo "CONTINUE from $resd ? sleep..";
    sleep(10);
    echo "go\n";
} else {
    $resd = null;
}

check_scanners($scanners_info);
$scanners = map_scanners($scanners_info);

$query = "INSERT INTO $mytester_table (generation_id, state, start_time) ".
	"VALUES (". GENERATION_ID .", ". STATE_PROGRESS .", now())";
//echo $query ."\n";
mysql_query($query, $connection);
$launch_id = mysql_insert_id($connection);
define('LAUNCH_ID', $launch_id);


header("Connection: close");
ob_start();
echo $launch_id;
header("Content-Length: ". strlen((string)$launch_id));
ob_end_flush();
flush();
//ob_clean();
ob_start(); // ???

foreach($scanners as $scanner) {
    $query = "INSERT INTO $launch_table (scanner_id, scanner_name, launch_id, state) ".
	"VALUES (". $scanner->id(). ",'". get_class($scanner) ."',". LAUNCH_ID .",". STATE_PROGRESS .")";
    echo $query ."\n";
    mysql_query($query, $connection);
}

//$scanners = array(new ZeroScanner(), new Scanner_sqlmap());

//$scanners = array(new Scanner_skipfish());

/*$scanners = array(
                  new ZeroScanner(),
                  //new Scanner_arachni_0_3(),
                  //new Scanner_sqlmap_0_9(),
                  //new Scanner_sqlmap_r4366(),                                                                              
                  //new Scanner_sqlmap_r4365(true),                                                                          
                  //new Scanner_wapiti_2_2_1(),
                  //new Scanner_skipfish_1_81b(),                                                                            
                  //new Scanner_skipfish_2_03b(),
                  //new Scanner_skipfish_2_06b(),
                  //new Scanner_w3af_1_0_rc5(),                                                                              
                  //new Scanner_w3af_1_0_stable(),                                                                           
                  //new Scanner_w3af_1_1(),
                  //new Scanner_unmanaged("burp-res.txt","burpsuite_pro_v1.4.07"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_sqlmap_0_9.txt", "comb_skipfish_2_03b_sqlmap_0_9"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_w3af_1_0_stable.txt", "comb_skipfish_2_03b_w3af_1_0_stable"),
                  //new Scanner_unmanaged("comb_sqlmap_0_9_w3af_1_0_stable.txt", "comb_sqlmap_0_9_w3af_1_0_stable"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_sqlmap_0_9_w3af_1_0_stable.txt", "comb_skipfish_2_03b_sqlmap_0_9_w3af_1_0_stable"),

                  );
*/
$testpages = 
    array_merge(
                // mytests($index, $prefix) - загружает тесты из данного файла, 
                // в вебе они должны быть доступны с данного пути
                mytests("../mytests/tests/". DIR_NAME ."/index.txt", "http://localhost/generation/mytests/tests/". DIR_NAME ."/")

                // realtests загружает реальные тесты из файла (просто список url)
                //                realtests("real.txt")
                
                );

// третий параметр - количество потоков
runtests_multi($testpages, $scanners, 800, $resd);

?>
