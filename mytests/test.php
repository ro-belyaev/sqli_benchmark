<?php

class BadTest extends Exception {
    public function __construct($s = "nomsg") {
        parent::__construct($s);
    }
}

class VeryBadTest extends Exception {
    public function __construct($s = "nomsg") {
        parent::__construct($s);
    }
}


class Test {
    public $conf, $filter, $process, $output, $template, $logfile;

    public function __construct(Conf $conf, Filter $filter, Process $process, Output $output, Template $template, $logfile = false) {
        if ($conf == null) throw new VeryBadTest("null conf");
        if ($filter == null) throw new VeryBadTest("null filter");
        if ($process == null) throw new VeryBadTest("null process");
        if ($output == null) throw new VeryBadTest("null output");
        if ($template == null) throw new VeryBadTest("null template");
        /*        
        foreach ($process->params() as $k => $p) {
            if ((string)$filter->filt($p) !== (string)$p) {
//echo "yes throw\n";
                throw new BadTest("param '$k' filters '" . $p . "' => '" . $filter->filt($p) . "'");
            }
	    //echo "no throw\n";
        }
	*/
	$this->conf = $conf;
        $this->filter = $filter;
        $this->process = $process;
        $this->output = $output;
        $this->template = $template;
        //$this->logfile = $logfile;

//??? WHAT IF LOGFILE == FALSE ???
	if($logfile) {
	    $this->logfile = '/var/www/generation/mytests/tests/'. DIR_NAME.'/'. $logfile;
	    //echo "\n----------------\n". $this->logfile ."\n--------------\n";
	} else {
	    $this->logfile = $logfile;
	}

        if (isset($_SERVER["QUERY_STRING"])) if (!file_exists($this->logfile)) if (file_put_contents($this->logfile, '') === false) {
            die("error writing $logfile\n");
        }
    }
    
    public function vuln() {
        if ($this->filter->safe()) return false;
        if ($this->process->quoted() && in_array($this->process->quoted(), $this->filter->anti())) return false;
        return true;
    }

    public function toohard() {
        /*
        if ($this->conf->hard()) echo "conf hard\n";
        if ($this->process->hard()) echo "process hard\n";
        if ($this->filter->hard()) echo "filter hard\n";
        if ($this->output->hard()) echo "output hard\n";
        if ($this->template->hard()) echo "template hard\n";
        */

        return $this->conf->hard() && $this->process->hard() ||
            $this->filter->hard() && $this->process->hard() ||
            $this->filter->hard() && $this->output->hard() ||
            $this->filter->hard() && $this->template->hard() ||
            $this->process->hard() && $this->template->hard();
    }
    
    public function run() {
        if ($this->logfile) {
            $lf = fopen($this->logfile, "a+");
            if (!$lf) die("error opening '$lf'");
            flock($lf, LOCK_SH);
        }
        $log = date("Y-m-d H:i:s", time()) . "\n\t" . urldecode($_SERVER['QUERY_STRING']) . "\n";
        $this->conf->perform();
        $pars = array();
        foreach ($this->process->params() as $par => $def) {
            $pars[$par] = $this->filter->filt($_GET[$par]);
        }
        $this->process->prepare();
        $t = microtime(true);
        $res = $this->process->run($pars);
        $t = microtime(true) - $t;
        global $db_last;
        $log .= "\t" . $db_last . "\n\tTime: " . number_format($t, 5, ".", "") . "\n\t";
        if ($res === false) {
            $log .= "Error: " . mysql_error();
        } else {
            $log .= "Num_rows: " . mysql_num_rows($res);
        }
        ob_start();
        $this->output->get($res);
        $out = ob_get_contents();
        ob_end_clean();
        $log .= "\n\tlen = " . strlen($out) . " hash = " . substr(md5($out), 20) . "\n";
        $this->template->output($out);
        if ($this->logfile) {
            fwrite($lf, $log);
            flock($lf, LOCK_UN);
            fclose($lf);
        }
    }

    public function prepare() {
        return $this->process->prepare();
    }

    public function show_info() {
        echo "<html><head><title>Test info</title></head>\n<body>";
        echo '<a href="' . htmlspecialchars($this->link()) . '">' . htmlspecialchars($this->link()) . '</a>';
        echo "<table>\n";
        echo "<tr><td>conf</td><td>" . htmlspecialchars($this->conf) . "</td><td>" . htmlspecialchars($this->conf->info()) . "</tr>\n";
        echo "<tr><td>filter</td><td>" . htmlspecialchars($this->filter) . "</td><td>" . htmlspecialchars($this->filter->info()) . "</tr>\n";
        echo "<tr><td>process</td><td>" . htmlspecialchars($this->process) . "</td><td>\n"; 
        foreach ($this->process->infolinks() as $href) {
            echo '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($href) . "</a><br>\n";
        }
        echo htmlspecialchars($this->process->info()) . "</td></tr>\n";
        echo "<tr><td>output</td><td>" . htmlspecialchars($this->output) . "</td><td>" . htmlspecialchars($this->output->info()) . "</tr>\n";
        echo "<tr><td>template</td><td>" . htmlspecialchars($this->template) . "</td><td>" . htmlspecialchars($this->template->info()) . "</tr>\n";
        echo "</table>";
        echo "<form action='?CLEAR' method='POST'><input type='submit' value='CLEAR LOG'></form>";
        echo "<textarea style='width: 100%; height: 250px' readonly>" . ($this->logfile ? htmlspecialchars(file_get_contents($this->logfile)) : "");
        echo "</textarea></body></html>\n";
    }
    
    public function link() {
        $pars = array();
        foreach ($this->process->params() as $par => $def) {
            $pars[] = "$par=" . urlencode($def);
        }
        return "?" . implode("&", $pars);
    }

    public function route() {
        //        $_SERVER["QUERY_STRING"] = "";
        
        if ($_SERVER["QUERY_STRING"] == 'INFO') {
            $this->show_info();
            return;
        }
        if (false !== $allowed = @file_get_contents("allowed.txt")) {
            if (!preg_match($allowed, $_SERVER['REMOTE_ADDR'])) {
                header('HTTP/1.1 403 Forbidden');
                echo "blocked";
                die();
            }
        }
        if ($_SERVER["QUERY_STRING"] == 'PREPARE') {
            $this->conf->perform();
            if ($this->prepare()) {
                echo "PREPARE DONE";
            } else {
                echo "PREPARE ERROR";
            }
        } elseif ($_SERVER["QUERY_STRING"] == 'CLEAR') {
            if ($this->logfile) {
                $lf = fopen($this->logfile, "a+");
                if (!$lf) die("error opening '$lf'");
                flock($lf, LOCK_EX);
                flock($lf, LOCK_UN);
                fclose($lf);
                if (file_put_contents($this->logfile, '') === false) {
                    die("error writing $logfile\n");
                }
            }
            header("Location: ?INFO");
        } elseif ($_SERVER["QUERY_STRING"] == 'GETLOG') {
            if ($this->logfile) {
                $lf = fopen($this->logfile, "rb");
                if (!$lf) die("error opening '$lf'");
                $t = microtime(true);
                flock($lf, LOCK_EX);
                $t = number_format(microtime(true) - $t, 6);
                echo "// lock acquired after " ; echo $t; echo " sec\n";
                $c = '';
                while (!feof($lf)) {
                    $c .= fread($lf, 8192);
                }
                flock($lf, LOCK_UN);
                fclose($lf);
                echo $c;
            }
        } else {
            $this->run();
            
        }
    }

}

class RealTest {


}

?>
