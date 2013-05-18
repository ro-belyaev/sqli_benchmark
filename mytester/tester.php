<?php
error_reporting(E_ALL);
ini_set("error_log","error.log");

set_include_path('.');

require_once "exceptions.php";
require_once "config.php";
require_once "scanner.php";
require_once "testpage.php";
require_once "ex.php";
require_once "testres.php";
require_once "mytests.php";
require_once "realtests.php";

require_once "../mytests/connection.php";
require_once "../mytests/constants.php";

class Tester {
    private $sc, $tp;
    public function __construct(Scanner $sc, TestPage $tp) {
        $this->sc = $sc;
        $this->tp = $tp;
    }

    public function test() {
	    file_put_contents("./tmpFile.txt", "before scanner prepare\n", FILE_APPEND);
        $this->sc->prepare();
	    file_put_contents("./tmpFile.txt", "before test prepare\n", FILE_APPEND);
        $this->tp->prepare();  // problem here!!!
	    file_put_contents("./tmpFile.txt", "before scanner run\n", FILE_APPEND);
        $r = $this->sc->run($this->tp->url);
	    file_put_contents("./tmpFile.txt", "after scanner run\n", FILE_APPEND);
        $this->sc->clean();
	    file_put_contents("./tmpFile.txt", "after scanner clean\n", FILE_APPEND);
        return new TestRes($this->tp->getlog(),
                           $this->tp->name,
                           $this->tp->url,
                           $r->log,
                           $r->state,
                           $r->ans,
                           $r->realtime,
                           $r->usertime,
                           $r->systime,
                           $r->cmd);
    }
}

function preparetmp() {
    $cmd = "touch tmp/tmp && rm -Rf tmp/* && mkdir tmp/scanners && cp -R ../scanners/* tmp/scanners/ 2>&1 && echo ok";
	file_put_contents("./scans_log.txt", $cmd. "\n", FILE_APPEND);
    //echo "$cmd\n"; // COMMENT HERE !!!
    if (trim($r = ex($cmd)) !== 'ok') {
	file_put_contents("./scans_log.txt", $r. "\n", FILE_APPEND);
	//echo "r=". $r ."\n"; // COMMENT HERE !!!
        die("error prepare tmp\n");
    }
}

function runtests($testpages, $scanners) {
    preparetmp();
    $ind = '';
    //$resd = "results/" . date("Y-m-d_H-i-s", time());
    $resd = "results/". LAUNCH_ID;
    if (mkdir($resd) === false) die("error mkdir '$resd'");
    $t = '';
    foreach ($scanners as $sc) $t .= $sc->name() . "\n";
    if (false === file_put_contents("$resd/scanners.txt", $t)) die("error writing '$resd\scanners.txt'\n");
    foreach ($testpages as $ti => $tp) {
        $ti = "$ti";
        $ind .= "$tp->url\t$tp->name\n\n";
        foreach ($scanners as $sc) {
            $resf = "$resd/${ti}_" . $sc->name() . ".result";
            echo "Run " . get_class($sc) . " on " . $tp->name . "..\n";
            $tester = new Tester($sc, $tp);
            $res = $tester->test();
            if (false === file_put_contents($resf, gzcompress(serialize($res)))) die("error writing '$resf'\n");
            
        }
    }
    if (false === file_put_contents("$resd/index.txt", $ind)) die("error writing '$resd/index.txt'");
    echo "\n\n\nAll done.\n$resd\n";
}


function superdie($s = "nomsg") {
    echo "SUPERDIE $s";
    posix_kill(0, SIGKILL);
    die("superdie error\n");
}

function run1test_($sc, $tp, $ti, $resd) {
    //pcntl_exec($_SERVER['_'], array("run1test.php", base64_encode(serialize($sc)), base64_encode(serialize($tp)), "$ti", $resd));
    $path_to_php = "/usr/local/bin/php";   //????????????????
	    file_put_contents("./tmpFile.txt", "before pcntl_exec\n", FILE_APPEND);
    pcntl_exec($path_to_php, array("run1test.php", base64_encode(serialize($sc)), base64_encode(serialize($tp)), "$ti", $resd));
	    file_put_contents("./tmpFile.txt", "superdie after pcntl_exec\n", FILE_APPEND);
    superdie("exec error");  // problem is here (it executes)
}

function run1test($sc, $tp, $ti, $resd) {
    //echo "INFLATER =" . memory_get_usage() . "\n"; // COMMENT HERE !!!
    $resf = "$resd/${ti}_" . $sc->name() . ".result";
	//file_put_contents("./scans_log.txt", "Run " . get_class($sc) . " on " . $tp->name . "..\n", FILE_APPEND);
    //echo "Run " . get_class($sc) . " on " . $tp->name . "..\n"; // COMMENT HERE !!!
    try {
        $tester = new Tester($sc, $tp);
        $res = $tester->test();
    } catch (Exception $e) {
        superdie("exc $e");
    }
    //echo "Run ok\n"; // COMMENT HERE !!!
//    file_put_contents("${resf}.txt", print_r($res, true));
    if (false === file_put_contents("$resf.tmp", gzcompress(serialize($res)))) {
        superdie("error writing '$resf'");
    }
    if (false === rename("$resf.tmp", "$resf")) {
        superdie("error renaming to '$resf'");
    } 
	    file_put_contents("./tmpFile.txt", "end of run1test\n", FILE_APPEND);
}

function mlog($s) {
    file_put_contents("mlog.log", date("D M j G:i:s T Y") . "\t$s\n", FILE_APPEND);
}

function runtests_multi(&$testpages, &$scanners, $thrc, $resd = null) {
    global $launch_table, $mytester_table, $table, $connection;
    //echo "BEGIN =" . memory_get_usage() . "\n"; // COMMENT HERE !!!
    ini_set('memory_limit', 30000000000);
    preparetmp();
		    file_put_contents("./tmpFile.txt", "after prepare tmp\n", FILE_APPEND);
    if (!$resd) {
        //$resd = "results/" . date("Y-m-d_H-i-s", time());
	$resd = "results/". LAUNCH_ID;
        if (mkdir($resd) === false) die("error mkdir '$resd'");
        $cont = false;
    } else {
        $cont = true;
    }
    $t = '';

    foreach ($scanners as $sc) $t .= $sc->name() . "\n";
    $ind = '';
    foreach ($testpages as $ti => $tp) {
        $ind .= "$tp->url\t$tp->name\n\n";
    }
    //        if (false === file_put_contents("$resd/index.txt", $ind)) die("error writing '$resd/index.txt'");

    if ($cont) {
        if (file_get_contents("$resd/scanners.txt") !== $t) die("$resd/scanners.txt doesn't match\n");
        if (file_get_contents("$resd/index.txt") !== $ind) die("$resd/index.txt doesn't match\n");
    } else {
        if (false === file_put_contents("$resd/scanners.txt", $t)) die("error writing '$resd/scanners.txt'\n");
        if (false === file_put_contents("$resd/index.txt", $ind)) die("error writing '$resd/index.txt'");
    }
    $testlocks = array();
    $batch = array();
    $bi = 0;
    foreach ($testpages as $ti => $tp) {
        $ti = "$ti";
        $testlocks[$ti] = 0;
        foreach ($scanners as $sc) {
            $resf = "$resd/${ti}_" . $sc->name() . ".result";
            if (!file_exists($resf)) {
                $batch[$bi] = array("tp" => $tp, "sc" => $sc, "ti" => $ti, "state" => "before", "pid" => 0, "bi" => $bi);
                $bi++;
            } else {
                //echo "SKIP $resf\n"; // COMMENT HERE !!!
            }
            //            var_dump($t);
        }
    }
    //echo "INITED =" . memory_get_usage() . "\n"; //COMMENT HERE !!!

    $t_running = 0;

    $blinks = array();
    
    //echo "sort batch.. "; // COMMENT HERE !!!
    uasort($batch, function($f1, $f2) {
            $p1 = get_class($f1["sc"]) === "ZeroScanner";
            $p2 = get_class($f2["sc"]) === "ZeroScanner";
            if ($p1 < $p2) return -1;
            if ($p1 > $p2) return 1;
            if ($f1["sc"]->weight() < $f2["sc"]->weight()) return -1;
            if ($f1["sc"]->weight() > $f2["sc"]->weight()) return 1;
            return $f1["ti"] - $f2["ti"];
        });
    //echo "ok\n"; // COMMENT HERE !!!

    mlog("while true");
    for ($ch = 0; ;++$ch) {
        if ($ch % 500 == 0) {
            $mins = 10000000000;
            foreach ($batch as &$b) {
                $mins = min($mins, $b["sc"]->weight());
            }
            unset($b);
        }
        mlog("while.. t_running=$t_running");
        $thf = true;
        //echo "t_running = $t_running\n\n"; // COMMENT HERE !!!
        //echo "mins = $mins\n"; // COMMENT HERE !!!
        if ($thf && $t_running < $thrc && !($t_running <= $thrc - $mins)) {
            //echo "\n\nSKIPPPPPPP HERE!\n-\n-\n-\n-\n"; // COMMENT HERE !!!
        }
        while ($thf && $t_running <= $thrc - $mins) {
            mlog("foreach batch t_running=$t_running");
            $thf = false;
            $frc = 0;
            foreach ($batch as $bi => &$b) {
                ++$frc;
                if ($b["state"]=="before" && $testlocks[$b["ti"]] == 0 && ($t_running == 0 || ($t_running + $b["sc"]->weight()) <= $thrc)) {
                    mlog("fork $bi $frc..");
                    if ($t_running + $b["sc"]->weight() > $thrc) {
                        //echo "no choice, run " . get_class($b["sc"]) . " with weight " . $b["sc"]->weight() . "\n"; // COMMENT HERE !!!
                    }
                    $b["state"] = "running";
                    //echo "main-fork..\n"; // COMMENT HERE !!!
                    $pid = pcntl_fork();
                    if ($pid < 0) {
                        superdie("cant fork");
                    } elseif ($pid == 0) {
                        //echo "AFTER FORK =" . memory_get_usage() . "\n"; // COMMENT HERE !!!
                        $sc = $b["sc"];
                        $tp = $b["tp"];
                        $ti = $b["ti"];
                        //                        register_shutdown_function('run1test', $sc, $tp, $ti, $resd);
                        
                        run1test_($sc, $tp, $ti, $resd);
                        exit();
                    } else {
                        mlog("forked");
                        $testlocks[$b["ti"]] = $b["pid"] = $pid;
                        $t_running += $b["sc"]->weight();
                        $blinks[$pid] = &$b;
                        // echo "thr $pid forked (" . $b["ti"] . ", " . get_class($b["sc"]) . ")\n"; // COMMENT HERE !!!
                        $thf = true;
                        if ($t_running <= $thrc - $mins) {
                            //echo "sleep..\n"; // COMMENT HERE !!!
                            sleep(0.1);
                        }
                        break;
                    }
                }
            }
            mlog("foreach end");
            unset($b);
            //echo "t_running = $t_running\n\n"; // COMMENT HERE !!!
            //echo "mins = $mins\n"; // COMMENT HERE !!!
            if ($thf && $t_running < $thrc && !($t_running <= $thrc - $mins)) {
                //echo "\n\nSKIPPPPPPP HER2E!\n-\n-\n-\n-\n"; // COMMENT HERE !!!
            }
        }
        mlog("while.. end");
        if ($t_running == 0) {
            break;
        }
        //echo "main-wait..\n"; // COMMENT HERE !!!
        $w = pcntl_wait($status);
        mlog("waiten");
        if (!pcntl_wifexited($status)) {
            superdie("thread $w aborted\n");
        }
        
        unset($status);
        //echo "thr $w ended\n"; // COMMENT HERE !!!
        $t_running -= $blinks[$w]["sc"]->weight();
        $testlocks[$blinks[$w]["ti"]] = 0;
        $blinks[$w]["state"] = "done";
        $blinks[$w]["pid"] = 0;
        $bi = $blinks[$w]["bi"];

	$cur_scanner_id = $batch[$bi]["sc"]->id();
	$query_inc_num_of_tests = "UPDATE $launch_table ".
		"SET num_of_tests=1+(SELECT num_of_tests FROM ". 
			"(SELECT num_of_tests FROM $launch_table ". 
			"WHERE launch_id='". LAUNCH_ID ."' ".
			"AND scanner_id='$cur_scanner_id') AS tbl ) ".
		"WHERE launch_id='". LAUNCH_ID ."' ".
		"AND scanner_id='$cur_scanner_id'";
	mysql_query($query_inc_num_of_tests, $connection);

	$query_change_state = "UPDATE $launch_table SET state=".
	    "CASE (SELECT num_of_tests FROM (SELECT num_of_tests FROM $launch_table WHERE ".
					    "scanner_id='$cur_scanner_id' AND launch_id='". LAUNCH_ID ."') AS tbl) ".
		"WHEN (SELECT num_of_tests FROM (SELECT gen.num_of_tests FROM $launch_table sc INNER JOIN $mytester_table mt USING (launch_id) ".
		    "INNER JOIN $table gen ON (mt.generation_id = gen.id) ".
		    "WHERE sc.scanner_id='$cur_scanner_id' AND sc.launch_id='". LAUNCH_ID ."') as some_tbl) ".
		"THEN ". STATE_COMPLETE ." ".
		"ELSE state ".
	    "END ".
	    "WHERE scanner_id='$cur_scanner_id' AND launch_id='". LAUNCH_ID ."'";
	mysql_query($query_change_state, $connection);
	//echo $query_inc_num_of_tests ."\n";
	//echo $query_change_state ."\n";
	//file_put_contents("./tmpFile.txt", $query_inc_num_of_tests, FILE_APPEND);
	//file_put_contents("./tmpFile.txt", $query_change_state, FILE_APPEND);


        unset($blinks[$w]);
        unset($batch[$bi]);
        mlog("unset bi $bi");
        
        foreach ($batch as &$b) {
            if ($b["pid"] === $w) {
                $b["state"] = "done";
                $b["pid"] = 0;
            }
        }
        unset($b);
        foreach ($testlocks as $ti => &$tl) {
            if ($tl === $w) {
                $tl = 0;
                //echo "Test $ti unlocked\n"; // COMMENT HERE !!!
            }
        }
        unset($tl);
        
        //echo "main-prd..\n"; // COMMENT HERE !!!

    }

    //echo "\n\n\nAll done.\n$resd\n"; // COMMENT HERE !!!


}

function unman_list(&$testpages) {
    $ind = '';
    foreach ($testpages as $ti => $tp) {
        $ind .= "$tp->url\n";
    }
    return $ind;
}

function unman_prepare(&$testpages) {
    echo "unmanaged prepare..\n";
    foreach ($testpages as $ti => $tp) {
        echo "$ti\n";
        $tp->prepare();
    }
    echo "done\n";
}


?>
