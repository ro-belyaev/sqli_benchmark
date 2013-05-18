<?php

abstract class Conf {
    abstract public function perform();
    public function info() {
        return "No info";
    }
    
    public function __toString() {
        return get_class($this);
    }

    public function hard() {
        return false;
    }
}
/*
class Loud extends Conf {
    public function perform() {
        global $db_host, $db_login, $db_pass, $db_conn, $db_db;
        $db_conn = mysql_connect($db_host, $db_login, $db_pass);
        mysql_select_db($db_db, $db_conn);
		        ini_set('display_errors', 1);
    }
}

class Silent extends Conf {
    public function perform() {
        global $db_host, $db_login, $db_pass, $db_conn, $db_db;
        $db_conn = mysql_connect($db_host, $db_login, $db_pass);
	mysql_select_db($db_db, $db_conn);
        ini_set('display_errors', 0);
    }
    
    public function hard() {
        return true;
    }
}
*/

class UConf extends Conf {
    public $display_errors, $no_sleep;

    public function __construct($display_errors, $no_sleep) {
        $this->display_errors = $display_errors;
        $this->no_sleep = $no_sleep;
    }

    public function hard() {
        return !($this->display_errors && !$this->no_sleep);
    }

    public function perform() {
        global $db_host, $db_login, $db_pass, $db_login_nosleep, $db_pass_nosleep, $db_conn, $db_db;
        if ($this->no_sleep) {
            $db_conn = mysql_connect($db_host, $db_login_nosleep, $db_pass_nosleep);
            mysql_select_db($db_db, $db_conn);
        } else {
            $db_conn = mysql_connect($db_host, $db_login, $db_pass);
            mysql_select_db($db_db, $db_conn);
        }
        if ($this->display_errors) {
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }
    }
}

?>
