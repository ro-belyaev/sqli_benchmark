<?php
/*  ???????????????????
if (dirname(__FILE__) !== getcwd()) {
    die("wrong dir\n");
}
*/ 
//echo "Will clear tests dir, sleep..\n";
//sleep(1); // if some files are writing to the dir in this moment
//echo "begin..\n";
/*
foreach (scandir("tests/". DIR_NAME) as $f) {
    if (preg_match('%^.*\.(php|log|txt)$%', $f)) {
        unlink("tests/". DIR_NAME ."/$f");
    }
}
*/
define('DIR_NAME', $id);
rrmdir("./tests/". DIR_NAME);

$query = "UPDATE $table SET xml=NULL, state=". STATE_CANCEL ." WHERE id='$id'";
mysql_query($query, $connection);
//echo "done\n";

function rrmdir($dir) {
    if (is_dir($dir)) {
	$objects = scandir($dir);
	foreach ($objects as $object) {
	    if ($object != "." && $object != "..") {
		if (filetype($dir."/".$object) == "dir") {
		    rrmdir($dir."/".$object);
		} else {
		    unlink($dir."/".$object);
		}
	    }
	}
	reset($objects);
	rmdir($dir);
    }
}


?>
