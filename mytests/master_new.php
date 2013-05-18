<?php
require_once "inc.php";
mysql_connect($db_host, $db_login, $db_pass);

/*
echo "Clearing old files..\n";
foreach (scandir("tests") as $f) {
    if (preg_match('%\.(log|php)$%', $f)) {
        unlink("tests/$f");
    } 
}
*/
ini_set('memory_limit', '800M');
$tmp_file = "./tmpres.txt";
file_put_contents($tmp_file, "avb mem: ". ini_get('memory_limit'), FILE_APPEND);  // delete then
//echo "Generating tests..\n";
$num = 1;
$test_count = 0;
$step = 80;
$lst = array();
foreach ($confs as $conf) {
    foreach ($filters as $filter) {
        foreach ($processes as $process) {
            foreach ($outputs as $output) {
                foreach ($templates as $template) {
                    $gen = "new Test($conf, $filter, $process, $output, $template, \"$num.log\")";
			
//echo "\n\n------------------------------------------------\n";
                    //echo "$gen .. ";
                    try {
                        eval("\$test = $gen;");
                        if ($test->toohard()) {
                            //echo "too hard\n";
                        } else {
                            if (file_put_contents("./tests/". DIR_NAME ."/$num.php", '<?php if(!defined(DIR_NAME)) {define("DIR_NAME","'. DIR_NAME .'");} require_once "../../inc.php"; $test = ' . $gen . '; $test->route(); ?>') === false) {
                                //                            var_dump(error_get_last());
                                die("error writing tests/". DIR_NAME ."/$num.php\n");
                            }
                            /*     */
                            //echo "tests/$num.php written\n";
                            $lst[] = array("$num.php" . $test->link(), $gen);
                                
                            //unset($test);  // i comment here !!!
                            ++$num;
                        }
			unset($test);
                    } catch (BadTest $e) {
                        //echo "bad test, skip: " . $e->getMessage() . "\n";
                    }
//echo "------------------------------------------------------------\n\n";
		    $test_count++;
		    if($test_count % $step == 0) {
			$query_check_state = "SELECT state FROM $table WHERE id=$id";
			$result = mysql_query($query_check_state, $connection);
			$cur_state = mysql_fetch_row($result)[0];
			if($cur_state == STATE_CANCEL) {
			    die("generation cancel");
			}
			$query_update_process = "UPDATE $table SET `process`=$test_count WHERE `id`=$id";
			$r = mysql_query($query_update_process, $connection);
			unset($query_check_state);
			unset($result);
			unset($cur_state);
			unset($query_update_process);
			unset($r);
			//echo "idle\n";
			//flush();
		    }
		unset($gen); // it's my code !!!
                }
            }
        }
    }
}



//echo "Writing tests/list.php ..\n";

$t = "<html><head><title>mytests</title></head><body><table>";
foreach ($lst as $ln) {
    list($href, $gen) = $ln;
    $t .= "<tr><td><a href=" . htmlspecialchars($href) . ">" . htmlspecialchars($href) . "</a></td><td>" . htmlspecialchars($gen) . "</td></tr>";
}
$t .= "</table></body>";
if (file_put_contents("tests/". DIR_NAME ."/list.php", $t) === false) {
    die("error writing tests/". DIR_NAME ."/list.php\n");
}

//echo "Done\n";

//echo "Writing tests/index.txt ..\n";

$t = "";
foreach ($lst as $ln) {
    list($href, $gen) = $ln;
    $t .= "$href\t$gen\n\n";
}
if (file_put_contents("tests/". DIR_NAME ."/index.txt", $t) === false) {
    die("error writing tests/". DIR_NAME ."/index.txt\n");
}

$query_check_state = "SELECT state FROM $table WHERE id=$id";
$result = mysql_query($query_check_state, $connection);
$cur_state = mysql_fetch_row($result)[0];
if($cur_state == STATE_CANCEL) {
    die("generation cancel");
}
$query_update_process = "UPDATE $table SET `process`=$test_count, `state`=" .STATE_COMPLETE. ", `num_of_tests`=". ($num-1) ." WHERE `id`=$id";
mysql_query($query_update_process, $connection);

//echo "Done\n";
            
?>
