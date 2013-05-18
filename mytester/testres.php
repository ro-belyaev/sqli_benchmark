<?php

class TestRes {
    public $testlog, $testname, $testurl, $scanlog, $scanstate, $scanans, $realtime, $usertime, $systime, $scancmd;

    public function __construct($testlog, $testname, $testurl, $scanlog, $scanstate, $scanans, $realtime, $usertime, $systime, $scancmd) {
        $this->testlog = $testlog;
        $this->testname = $testname;
        $this->testurl = $testurl;
        $this->scanlog = $scanlog;
        $this->scanstate = $scanstate;
        $this->scanans = $scanans;
        $this->realtime = $realtime;
        $this->usertime = $usertime;
        $this->systime = $systime;
        $this->scancmd = $scancmd;
    }

    public function toHTML() {
        $r = '<html><head><title>' . htmlspecialchars($this->testname) . '</title><LINK REL="StyleSheet" HREF="style.css" TYPE="text/css"></head><body>';
        $r .= htmlspecialchars($this->testname) . '</br>';
        $r .= '<a href="' . str_replace('&', '&amp;', $this->testurl) . '">' . htmlspecialchars($this->testurl) . '</a>   ';
        $r .= '<a href="' . str_replace('&', '&amp;', preg_replace('%\?.*$%', '', $this->testurl) . "?INFO") . '">info</a><br>';
        $r .= 'Scan state: ' . htmlspecialchars($this->scanstate) . '<br>';
        $r .= 'Scan ans: ' . htmlspecialchars($this->scanans) . '<br>';
        $r .= 'Time: ' . htmlspecialchars($this->realtime) . '<br>';
        $r .= 'Cmd: ' . htmlspecialchars($this->scancmd) . '<br>';
        $r .= 'Test log:<br><textarea style="width: 100%; height: 200px" readonly>' . htmlspecialchars($this->testlog) . '</textarea>';
        $r .= 'Scanner log:<br><textarea style="width: 100%; height: 200px" readonly>' . htmlspecialchars($this->scanlog) . '</textarea>';
        $r .= '</body></html>';
        return $r;
    }
    
}


?>