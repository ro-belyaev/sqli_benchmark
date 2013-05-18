<?php

define('DIR_NAME', "4");

require_once "inc.php";
require_once "sets_tmp.php";


/*
echo "Clearing old files..\n";
foreach (scandir("tests") as $f) {
    if (preg_match('%\.(log|php)$%', $f)) {
        unlink("tests/$f");
    } 
}
*/


echo "Generating tests..\n";
$num = 1;
$limit = -1;
if (isset($argv[1])) {
    $limit = intval($argv[1]);
}

$lst = array();
foreach ($confs as $conf) {
    foreach ($filters as $filter) {
        foreach ($processes as $process) {
            foreach ($outputs as $output) {
                foreach ($templates as $template) {
                    $gen = "new Test($conf, $filter, $process, $output, $template, \"$num.log\")";
                    echo "$gen .. ";
                    try {
                        eval("\$test = $gen;");
                        if ($test->toohard()) {
                            echo "too hard\n";
                        } else {
                            if (file_put_contents("tests/$num.php", '<?php require_once "../inc.php"; $test = ' . $gen . '; $test->route(); ?>') === false) {
                                //                            var_dump(error_get_last());
                                die("error writing tests/$num.php\n");
                            }
                            /*     */
                            echo "tests/$num.php written\n";
                            $lst[] = array("$num.php" . $test->link(), $gen);
                            if ($num == $limit) goto limlim;
                                
                            unset($test);
                            ++$num;
                        }
                    } catch (BadTest $e) {
                        echo "bad test, skip: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
}

limlim:
echo "Writing tests/list.php ..\n";

$t = "<html><head><title>mytests</title></head><body><table>";
foreach ($lst as $ln) {
    list($href, $gen) = $ln;
    $t .= "<tr><td><a href=" . htmlspecialchars($href) . ">" . htmlspecialchars($href) . "</a></td><td>" . htmlspecialchars($gen) . "</td></tr>";
}
$t .= "</table></body>";
if (file_put_contents("tests/list.php", $t) === false) {
    die("error writing tests/list.php\n");
}

echo "Done\n";

echo "Writing tests/index.txt ..\n";

$t = "";
foreach ($lst as $ln) {
    list($href, $gen) = $ln;
    $t .= "$href\t$gen\n\n";
}
if (file_put_contents("tests/index.txt", $t) === false) {
    die("error writing tests/index.txt\n");
}

echo "Done\n";

?>
