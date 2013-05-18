<?php

require_once "exceptions.php";
require_once "ex.php";

abstract class Scanner {
    protected $dir;
    protected $id;
    abstract public function run($url);
    
    public function prepare() {
    }

    public function clean() {
    }
    
    public function name() {
        return get_class($this);
    }
    public function id() {
	return $this->id;
    }
    public function mkdir() {
        $tr = 10;
    ag:
        $this->dir = "tmp/s_" . $this->name() . "_" . rand(0, 10000000) . rand(0, 10000000);
        //echo "$this->dir\n"; // COMMENT HERE !!!
        if (!mkdir($this->dir)) {
            $tr--;
            if ($tr) goto ag;
            throw new TestExc("cant mkdir '$this->dir'\n");
        }
    }

    public function weight() {
        return 100;
    }
}

class ZeroScanner extends Scanner {
    public function run($url) {
        return new Scanres('0-scanner', 'OK', 'inj', 0, 0, 0, "nocmd");
    }
    public function __construct() {
	$this->id = 0;
    }
}

class Scanner_unmanaged extends Scanner {
    private $vulns;
    private $name;
    public function __construct($fn, $name = null) {
        //        $this->vulns = file($fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->vulns = gzcompress(file_get_contents($fn));
        if ($name === null) $name = get_class($this);
        $this->name = $name;
    }

    public function run($url) {
        $u = parse_url($url, PHP_URL_PATH);
        $rr = explode("\n", gzuncompress($this->vulns));
        foreach ($rr as $v) {
            if (strpos(trim($v), $u) !== false) {
                echo "url=$url u=$u v=$v\n";
                $res = "vuln '$v' contains '$u'";
                echo $res;
                return new Scanres($res, 'OK', 'inj', 0, 0, 0, "nocmd");
            }
        }
        echo "url=$url u=$u no v\n";
        $res = count($rr) . " vulns don't contain '$u'";
        echo "$res\n";
        return new Scanres($res, 'OK', 'not', 0, 0, 0, "nocmd");
        //        return new Scanres('
    }

    public function name() {
        return $this->name;
    }
}

class Scanner_sqlmap_0_8 extends Scanner {
    public function __construct() {
	$this->id = 4;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "cd $this->dir; ./sqlmap.py --batch -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (
                strpos($res, 'numeric injectable with') !== false || strpos($res, 'string injectable with') !== false) {
                //preg_match('%\[INFO\] confirming.*injection%', $res)) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/sqlmap-0.8/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy sqlmap:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete sqlmap:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }
}

class Scanner_sqlmap_0_9 extends Scanner {
    public function __construct() {
	$this->id = 5;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "cd $this->dir; ./sqlmap.py --batch -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (
                strpos($res, 'sqlmap identified the following injection points with') !== false) {
                //preg_match('%\[INFO\] confirming.*injection%', $res)) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/sqlmap-0.9/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy sqlmap:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete sqlmap:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }
}

class Scanner_sqlmap_r4365 extends Scanner {
    public $l5r3;
    public function __construct($l5r3 = false) {
        $this->l5r3 = $l5r3;
	$this->id = 6;
    }

    public function name() {
        if ($this->l5r3) {
            return "Scanner_sqlmap_r4365_l5r3";
        } else {
            return "Scanner_sqlmap_r4365";
        }            
    }
    
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $e = $this->l5r3 ? " --level 5 --risk 3 " : "";
        $cmd = "cd $this->dir; ./sqlmap.py $e --batch -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (
                strpos($res, 'sqlmap identified the following injection points with') !== false) {
                //preg_match('%\[INFO\] confirming.*injection%', $res)) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/sqlmap-r4365/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy sqlmap:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete sqlmap:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }
}


class Scanner_sqlmapdev extends Scanner {
    public function __construct() {
	$this->id = 7;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "cd $this->dir; ./sqlmap.py --batch -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {

            // [17:51:27] [INFO] GET parameter 'id' is unescaped numeric (AND) injectable with 0 parenthesis
            // [17:50:15] [INFO] GET parameter 'id' is not injectable with 1 parenthesis


            if (
                //strpos($res, 'numeric injectable with') !== false || strpos($this->scanlog, 'string injectable with') !== false)
                preg_match('%\[INFO\] .* parameter \'.*\' is .* \(.*\) injectable with .* parenthesis%', $res)) 
                {
                    return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/sqlmap-dev/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy sqlmap:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete sqlmap:\n$r\n");
        }
        $this->dir = NULL;
    }

}



class Scanner_wapiti_2_2_1 extends Scanner {
    public function __construct() {
	$this->id = 1;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("wapiti not prepared");
        $folder = preg_replace('%[^/]*$%', '', $url);
        $cmd = "cd $this->dir;  python ./wapiti.py " . escapeshellarg($folder) . " -s " . escapeshellarg($url) . " --scope folder  -m '-all,sql,blindsql' 2>&1 || echo mybencherr";
        //echo "$cmd\n"; // COMMENT HERE !!!
	//file_put_contents("./scans_log.txt", $cmd. "\n", FILE_APPEND);
        $t0 = microtime(true);
        $res = ex($cmd);
	//file_put_contents("./scans_log.txt", $res. "\n", FILE_APPEND);
        //echo $res; // COMMENT HERE !!!
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (
                strpos($res, 'Evil url: ') !== false
                )
                {
                    return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/wapiti-2.2.1/src/* $this->dir/ 2>&1 && echo ok";
        //echo "$cmd\n"; // COMMENT HERE !!!
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy wapiti:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("sqlmap not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        //echo "$cmd\n"; // COMMENT HERE !!!
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete wapiti:\n$r\n");
        }
        $this->dir = NULL;
    }

    public function weight() {
        return 10;
    }

}




class Scanner_bsqlbf2 extends Scanner {
    public function __construct() {
	$this->id = 8;
    }

    private function runonce($url, $mode, $par) {
        if ($this->dir === NULL) throw new TestExc("bqlbf2 not prepared");
        $cmd = "cd $this->dir;  perl bsqlbf-v2-6.pl -url " . escapeshellarg($url) ." -database 1 -sql 'crc32(1)' -type $mode  -blind '$par' 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (strpos($res, 'mybencherr') !== false) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (
                strpos($res, '2212294583') !== false
                )
                {
                    return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
                } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }

    public function run($url) {
        if (false === $t = strpos($url, '?')) {
            throw new TestExc("strange test url '$url' \n");
        }
        $t = substr($url, $t);
        $pars = array();
        foreach (explode('&', $t) as $x) {
            if (preg_match('%^([^=]*)=.*$%', $x, $m)) {
                $a[] = $m[1];
            }
        }
        var_dump($a);
        echo "Need to launch bsqlbf2 " . count($a) . "*3 times\n";
        foreach ($a as $par) {
            for ($mode = 0; $mode <= 2; ++$mode) {
                $res = $this->runonce($url, $mode, $par);
                if ($res->ans == 'inj') {
                    return $res;
                }
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/bsqlbf2/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy bsqlbf2:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("bsqlbf2 not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete bsqlbf2:\n$r\n");
        }
        $this->dir = NULL;
    }

}


class Scanner_skipfish_1_81b extends Scanner {
    public function __construct() {
	$this->id = 2;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("skipfish not prepared");
        //$cmd = "cd $this->dir; ./sqlmap.py -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        $match = preg_replace('%=.*$%', '', $url);
        $cmd = "cd $this->dir; ./skipfish -o o13 -g 1 -t 60 -w 60 -i 60  -Y -L -V  -u -W dictionaries/empty.wl -I " . escapeshellarg($match) . " " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        //echo "$cmd\n"; // COMMENT HERE !!!
	//file_put_contents("./scans_log.txt", $cmd. "\n", FILE_APPEND);
        $t0 = microtime(true);
        $res = ex($cmd);
	//file_put_contents("./scans_log.txt", $res. "\n", FILE_APPEND);
        //echo $res;  // COMMENT HERE !!!
        $tm = microtime(true) - $t0;
        if (false !== strpos($res, 'mybencherr') || !is_dir("$this->dir/o13")) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            $cmd2 = "find $this->dir/o13 -type f -name '*.js' -exec grep -l \"'type': 50103\" {} + 2>&1";
	    //file_put_contents("./scans_log.txt", $cmd2. "\n", FILE_APPEND);
            //echo "$cmd2\n"; // COMMENT HERE !!!
            $res2 = ex($cmd2);
	    //file_put_contents("./scans_log.txt", $res2. "\n", FILE_APPEND);
            if (strpos($res2, 'o13') !== false) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/skipfish-1.81b/* $this->dir/ 2>&1 && echo ok";
        //echo "$cmd\n"; // I COMMENT THIS HERE !!!
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy skipfish:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("skipfish not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        //echo "$cmd\n"; // COMMENT HERE !!!
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete skipfish:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }

}

class Scanner_skipfish_2_03b extends Scanner_skipfish_1_81b {
    public function __construct() {
	$this->id = 3;
    }
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/skipfish-2.03b/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy skipfish:\n$r\n");
        }
    }
}

class Scanner_skipfish_2_06b extends Scanner_skipfish_2_03b {
    public function __construct() {
	$this->id = -1; //???????
    }
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/skipfish-2.06b/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy skipfish:\n$r\n");
        }
    }

    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("skipfish not prepared");
        //$cmd = "cd $this->dir; ./sqlmap.py -u " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        $match = preg_replace('%=.*$%', '', $url);
        $cmd = "cd $this->dir; ./skipfish -o o13 -g 1 -t 60 -w 60 -i 60  -Y -L  -u -W dictionaries/empty.wl -I " . escapeshellarg($match) . " " . escapeshellarg($url) . " 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (false !== strpos($res, 'mybencherr') || !is_dir("$this->dir/o13")) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            $cmd2 = "find $this->dir/o13 -type f -name '*.js' -exec grep -l \"'type': 50103\" {} + 2>&1";
            echo "$cmd2\n";
            $res2 = ex($cmd2);
            if (strpos($res2, 'o13') !== false) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    

    
}

class Scanner_w3af_1_0_rc5 extends Scanner {
    public function __construct() {
	$this->id = 9;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("w3af not prepared");
        if (false === file_put_contents("$this->dir/scr.txt", "plugins audit blindSqli,sqli
plugins output console
target set target \"$url\"
start
kb list vulns
exit
")) {
            throw new TestExc("error writing script");
        }
        
        $cmd = "cd $this->dir; ./w3af_console -s scr.txt 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (false !== strpos($res, 'mybencherr')) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (strpos($res, '| Blind SQL injection was found') !== false || strpos($res, '| SQL injection in ') !== false) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/w3af-1.0-rc5/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy w3af:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("w3af not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete w3af:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }

}

class Scanner_w3af_1_0_stable extends Scanner {
    public function __construct() {
	$this->id = 10;
    }
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("w3af not prepared");
        if (false === file_put_contents("$this->dir/scr.txt", "plugins audit blindSqli,sqli
plugins output console
target set target \"$url\"
start
kb list vulns
exit
")) {
            throw new TestExc("error writing script");
        }
        
        $cmd = "cd $this->dir; HOME=`pwd` ./w3af_console -n -s scr.txt 2>&1 || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (false !== strpos($res, 'mybencherr')) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if (strpos($res, '| Blind SQL injection was found') !== false || strpos($res, '| SQL injection in ') !== false) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R `pwd`/tmp/scanners/w3af-1.0-stable/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy w3af:\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("w3af not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete w3af:\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 100;
    }

}

class Scanner_w3af_1_1 extends Scanner_w3af_1_0_stable {
    public function __construct() {
	$this->id = 11;
    }
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R `pwd`/tmp/scanners/w3af-1.1/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy w3af:\n$r\n");
        }
    }
}

class Scanner_arachni_0_3 extends Scanner {
    public function run($url) {
        if ($this->dir === NULL) throw new TestExc("arachni not prepared");
        
        $cmd = "cd $this->dir; ./arachni --report=xml:outfile=mbreport.xml --report=stdout --audit-links --mods=sqli,sqli_blind_timing,sqli_blind_rdiff --http-req-limit=1 " . escapeshellarg($url)  . " || echo mybencherr";
        echo "$cmd\n";
        $t0 = microtime(true);
        $res = ex($cmd);
        echo $res;
        $tm = microtime(true) - $t0;
        if (false === $mbrep = file_get_contents($this->dir . '/cde-package/cde-root/mbreport.xml')) {
            throw new TestExc("error loading mbreport.xml");
        }

        $x = new SimpleXMLElement($mbrep);
        $c = 0;
        foreach ($x->xpath('//issue/tags/tag[@name="sql"]') as $k) $c++;

        if (false !== strpos($res, 'mybencherr')) {
            return new Scanres($res, 'ERROR', '', $tm, 0, 0, $cmd);
        } else {
            if ($c !== 0) {
                return new Scanres($res, 'OK', 'inj', $tm, 0, 0, $cmd);
            } else {
                return new Scanres($res, 'OK', 'not', $tm, 0, 0, $cmd);
            }
        }
    }
    
    public function prepare() {
        $this->mkdir();
        $cmd = "cp -R tmp/scanners/arachni-v0.3-cde/* $this->dir/ 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error deploy arachni\n$r\n");
        }
    }

    public function clean() {
        if ($this->dir === NULL) throw new TestExc("arachni not prepared");
        $cmd = "rm -Rf $this->dir 2>&1 && echo ok";
        echo "$cmd\n";
        if (trim($r = ex($cmd)) !== 'ok') {
            throw new TestExc("error delete arachni\n$r\n");
        }
        $this->dir = NULL;
    }
    
    public function weight() {
        return 15;
    }

}



class ScanRes {
    public $log, $state, $ans, $realtime, $usertime, $systime, $cmd;
    public function __construct($log, $state, $ans, $realtime, $usertime, $systime, $cmd) {
        $this->log = $log;
        $this->state = $state;
        $this->ans = $ans;
        $this->realtime = $realtime;
        $this->usertime = $usertime;
        $this->systime = $systime;
        $this->cmd = $cmd;
    }
}


?>
