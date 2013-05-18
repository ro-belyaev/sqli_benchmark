<?php

function make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000)+37*getmypid();
}
srand(make_seed());

require_once 'tester.php';
require_once "scanner_p.php";

$sc = unserialize(base64_decode($argv[1]));
$tp = unserialize(base64_decode($argv[2]));
$ti = intval($argv[3]);
$resd = $argv[4];

run1test($sc, $tp, $ti, $resd);
	file_put_contents("./tmpFile.txt", "end of run1test.php\n", FILE_APPEND); // delete then

?>
