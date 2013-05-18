<?php
require_once "init.php";

abstract class Filter {
    abstract public function filt($s);

    public function anti() {
        return array();
    }
    
    public function info() {
        return "No info";
    }

    public function __toString() {
        return get_class($this);
    }

    public function hard() {
        return false;
    }
    
    public function safe() {
        return false;
    }
    
}

class ZeroFilter extends Filter {
    public function filt($s) {
        return $s;
    }
}

class IntFilter extends Filter {
    public function filt($s) {
        return (string)intval($s);
    }

    public function safe() {
        return true;
    }

}


class StrDel extends Filter {
    public $arg;

    public function __construct($arg) {
        $this->arg = $arg;
    }
     
    public function filt($s) {
        return str_replace($this->arg, '', $s);
    }

    public function hard() {
        return true;
    }

    public function anti() {
        if (is_array($this->arg)) {
            return $this->arg;
        } else {
            return array($this->arg);
        }
    }
    
    public function __toString() {
        return get_class($this) . "(" . dmp($this->arg) . ")";
    }
}

class RealEscape extends Filter {
    public function filt($s) {
        return mysql_real_escape_string($s);
    }

    public function anti() {
        return array('"', "'");
    }
}

class QuoteEscape1 extends Filter {
    public function filt($s) {
        return str_replace('\'', '\\\'', $s);
    }

    public function hard() {
        return true;
    }
}


class QuoteEscape2 extends Filter {
    public function filt($s) {
        return str_replace('"', '\\"', $s);
    }

    public function hard() {
        return true;
    }
}

class QuoteEscape3 extends Filter {
    public function filt($s) {
        return str_replace('"', '\\"', str_replace('\'', '\\\'', $s));
    }
    
    public function hard() {
        return true;
    }
}

class LengthCut extends Filter {
    private $arg;

    public function __construct($arg) {
        $this->arg = $arg;
    }
     
    public function __toString() {
        return get_class($this) . "(" . dmp($this->arg) . ")";
    }
    
    public function filt($s) {
        return substr($s, 0, $this->arg);
    }

    public function hard() {
        return true;
    }
}



?>
