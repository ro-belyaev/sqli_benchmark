<?php
require_once "getans.php";

class TestPage {
    public $url, $managed, $name, $noclean;

    public function __construct($url, $name, $managed, $noclean = false) {
        $this->url = $url;
        $this->managed = $managed;
        $this->name = $name;
        $this->noclean = $noclean;
    }

    public function prebuild() {
        if ($this->managed && !$this->noclean) {
            if (getans(preg_replace('%\?.*$%', "?PREPARE", $this->url)) !== 'PREPARE DONE') {
                throw new TestExc();
            }
        }
    }

    public function prepare() {
        if ($this->managed && !$this->noclean) {
            if (getans(preg_replace('%\?.*$%', "?CLEAR", $this->url)) === false) {
                throw new TestExc();
            }
        }
        $this->prebuild(); // problem is here!!! (just forget to start mysqld)
    }

    public function getlog() {
        if ($this->managed) {
            //echo "\nget log.. "; // COMMENT HERE !!!
            $r = getans(preg_replace('%\?.*$%', "?GETLOG", $this->url));
            if ($r === false) {
                throw new TestExc();
            } else {
                //echo "OK\n"; // COMMENT HERE !!!
                return $r;
            }
        } else {
            return '';
        }
    }
}

?>
