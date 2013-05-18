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



$resd = $argv[1];

$indf = file("$resd/index.txt");
if ($indf === false) die("error opening '$resd/scanners.txt'");
$scf = file("$resd/scanners.txt");
if ($scf === false) die("error opening '$resd/scanners.txt'");

$scanners = array();
foreach ($scf as $t) {
    $t = trim($t);
    if ($t == '') continue;
    $scanners[] = $t;
}

$stat = array();
$stat_rc = array();
$statbystate = array();
$lists = array();


$stl = file_get_contents("style.css");
if ($stl === false) die("error reading style.css\n");
if (false ===file_put_contents("report/style.css", $stl)) {
    die("error writing 'report/style.css'\n");
}



$ti = -1;
$tr = false;
$tn = false;   // ??? maybe, $tm = false ???
$injsbyscanner = array();
foreach ($scanners as $sc) $injsbyscanner[$sc] = array();
foreach ($indf as $t) {
    $t = trim($t);
    if ($t == '') continue;
    if (!preg_match('%^(.*?)\t(.*)$%', $t, $m)) {
        die("bad line '$t'");
    }
    $ti++;
    if ($ti % 100 == 0) echo "$ti\n";
    //    if ($ti == 100) break;
    $code = $m[2];
    $test = eval("return $code;");
    foreach ($scanners as $sc) {
        if (preg_match('%^(.*)\+(.*)$%', $sc, $m)) {
            $sc1 = $m[1];
            $sc2 = $m[2];
            $resf1 = "$resd/${ti}_" . $sc1 . ".result";
            $resf2 = "$resd/${ti}_" . $sc2 . ".result";
            $resc1 = file_get_contents($resf1);
            if ($resc1 === false) die("error opening '$resf1'\n");
            $res1 = unserialize(gzuncompress($resc1));
            $resc2 = file_get_contents($resf2);
            if ($resc2 === false) die("error opening '$resf2'\n");
            $res2 = unserialize(gzuncompress($resc2));
            if ($res1->scanstate === 'OK' && $res1->scanstate === 'OK') {
                if ($res1->scanans === 'inj' || $res2->scanans === 'inj') {
                    $scanans = 'inj';
                } else {
                    $scanans = 'not';
                }
                
                $res = new TestRes("1\n$res1->testlog\n2\n$res2->testlog", $res1->testname, $res1->testurl, "1\n$res1->scanlog\n2\n$res2->scanlog", "OK", $scanans, 0, 0, 0, "$res1->scancmd ; $res2->scancmd");
            } else {
                $res = new TestRes("1\n$res1->testlog\n2\n$res2->testlog", $res1->testname, $res1->testurl, "1\n$res1->scanlog\n2\n$res2->scanlog", "ERROR", '', 0, 0, 0, "$res1->scancmd ; $res2->scancmd");
            }
        } else {
            $resf = "$resd/${ti}_" . $sc . ".result";
            $resc = file_get_contents($resf);
            if ($resc === false) {
                echo("error opening '$resf'\n");
                $res = new TestRes("error opening '$resf'\n", '', '', "", "ERROR", '', 0, 0, 0, "");
            } else {
                $res = unserialize(gzuncompress($resc));
            }
        }
        //var_dump($res);
        if (!isset($stat[$sc])) $stat[$sc] = array();
        if (!isset($stat_rc[$sc])) $stat_rc[$sc] = array();
        
        if (!isset($statbystate[$sc])) $statbystate[$sc] = array();
        if (!isset($statbystate[$sc][$res->scanstate])) $statbystate[$sc][$res->scanstate] = 0;
        $statbystate[$sc][$res->scanstate]++;
        if ($res->scanstate !== 'OK') continue;
        if ($res->scanans == 'inj' && $test->vuln()) {
            $injsbyscanner[$sc][] = $res->testurl;
        }
        if (get_class($test) == 'Test' || is_subclass_of($test, 'Test')) {
            $curcls = $myclasses;
            $tm = true;
        } else {
            $curcls = $realclasses;
            $tr = true;
        }
        
        $rhtml = $res->toHTML();
        $mrhtml = md5($rhtml);
        ///*
        //        echo "write " . strlen($rhtml) . "\n";
          if (false === file_put_contents("report/$mrhtml.html", $rhtml)) {
            die("error writing 'report/$mrhtml.html'\n");
        }
          //*/
        foreach ($curcls as $clname => $cl) if ($cl($test)) {
            $lists[md5($sc . "/$res->scanans/" . $clname)][] = array($res->testurl, $mrhtml, $res->testname);
            if (!isset($stat[$sc][$clname])) $stat[$sc][$clname] = array();
            if (!isset($stat_rc[$sc][$clname])) $stat_rc[$sc][$clname] = array();
            if (!isset($stat[$sc][$clname][$res->scanans])) $stat[$sc][$clname][$res->scanans] = 0;
            if (!isset($stat_rc[$sc][$clname][$res->scanans])) $stat_rc[$sc][$clname][$res->scanans] = array(0, 0);
            $stat[$sc][$clname][$res->scanans]++;
            $stat_rc[$sc][$clname][$res->scanans][1]++;
            $stat_rc[$sc][$clname][$res->scanans][0]+= preg_match_all('%len = [0-9]+ hash = [0-9a-f]{12}%', $res->testlog, $mmmmmm);
            //            echo "cnt" . preg_match_all('%len = [0-9]+ hash = [0-9a-f]{12}%', $res->testlog, $mmmmmm) . "\n";
            ksort($stat[$sc][$clname]);
        }
        unset($res);
    }
    unset($test);
}

$allcls = array();
if ($tm) $allcls = array_keys($myclasses);
if ($tr) $allcls = array_merge($allcls, array_keys($realclasses));

$html = '<html><head><title>mybench</title><style>' . file_get_contents("style.css") . '</style></head><body><h1>' . htmlspecialchars($resd) . '</h1><br>';
$html .= htmlspecialchars(print_r($statbystate, true)) . '<br>';
$html .= '<table><tr><td></td>';

foreach ($allcls as $c) {
    $html .= '<th>' . htmlspecialchars($c) . '</th>';
}
$html .= '</tr>';
foreach ($scanners as $s) {
    if (false === file_put_contents("report/inj_by_" . $s . ".txt", implode("\n", $injsbyscanner[$s]) . "\n")) {
        die("error writing report/inj_by_" . $s . ".txt\n");
    }
    $html .= '<tr><th>' . htmlspecialchars($s) . '</th>';
    $r = $stat[$s];
    foreach ($allcls as $c) {
        $k = isset($r[$c]['inj']) ? $r[$c]['inj'] : 0;
        $l = '';
        if (isset($r[$c])) {
            foreach ($r[$c] as $p => $ppp) {
                $rc_r = $stat_rc[$s][$c][$p][1] ? round($stat_rc[$s][$c][$p][0]/$stat_rc[$s][$c][$p][1], 1) : '-';
                $l .= '/<a href="' . md5($s . "/$p/" . $c) . '.html">' . htmlspecialchars($p . "($rc_r)") . '</a>';
            }
        }
        $html .= '<td>' . htmlspecialchars($k) . $l . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</table><br><p class="help">' . htmlspecialchars($classeshelp) . '</p></body></html>';

foreach ($lists as $k => $lst) {
    if (count($lst) > 100) {
        $r = '<index><head><title>mybench</title><LINK REL="StyleSheet" HREF="style.css" TYPE="text/css"></head><body>';
        for ($i = 0; $i*100 <= count($lst); ++$i) {
            $tr = '<index><head><title>mybench</title><LINK REL="StyleSheet" HREF="style.css" TYPE="text/css"></head><body>';
            for ($j = $i * 100; isset($lst[$j]) && $j < ($i + 1) * 100; ++$j) {
                $le = $lst[$j];
                $tr .= $j . ' <a href="' . str_replace('&', '&amp;', preg_replace('%\?.*$%', '', $le[0]) . "?INFO") . '">' . htmlspecialchars($le[2]) . '</a>' .
                    '   <a class="reportlink" href="' . $le[1] . '.html">report</a><br>';
                $lj = $j;
            }
            $tr .= '</body></html>';
            $mtr = md5($tr);
            //            echo "write " . strlen($tr) . "\n";
            if (file_put_contents("report/$mtr.html", $tr) === false) {
                die("error writing 'report/$mtr.html'\n");
            }
            $r .= "<a href=\"$mtr.html\">" . ($i * 100) . " - $lj</a><br>";
        }  
        if (false === file_put_contents("report/$k.html", $r)) {
            die("error writing report/$k.html\n");
        }
    } else {
        $tr = '<index><head><title>mybench</title><LINK REL="StyleSheet" HREF="style.css" TYPE="text/css"></head><body>';
        for ($j = 0; isset($lst[$j]); ++$j) {
            $le = $lst[$j];
            $tr .= $j . ' <a href="' . str_replace('&', '&amp;', preg_replace('%\?.*$%', '', $le[0]) . "?INFO") . '">' . htmlspecialchars($le[2]) . '</a>' .
                '  <a class="reportlink" href="' . $le[1] . '.html">report</a><br>';
            $lj = $j;
        }
        $tr .= '</body></html>';
        //        echo "write " . strlen($tr) . "\n";
        if (file_put_contents("report/$k.html", $tr) === false) {
            die("error writing 'report/$k.html'\n");
        }         
    }
}
//echo "write " . strlen($html) . "\n";
if (false === file_put_contents("report/index.html", $html)) {
    die("error writing report/index.html\n");
}

var_dump($stat);
var_dump($statbystate);


?>
