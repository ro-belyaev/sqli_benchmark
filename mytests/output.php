<?php

abstract class Output {
    abstract public function get($res);
    
    public function info() {
        return "No info";
    }


    public function __toString() {
        return get_class($this);
    }

    public function hard() {
        return false;
    }

    public function eout() {
        return false;
    }
}

class SimpleOutput extends Output {
    public function get($res) {
        while ($r = mysql_fetch_row($res)) {
            echo "<pre>\n";
            print_r($r);
            echo "\n</pre><br>";
        }
    }

    public function hard() {
        return true;
    }

}

class TableOutput extends Output {
    public function get($res) {
        echo "<table>\n";
        while ($r = mysql_fetch_row($res)) {
            echo "<tr>";
            foreach ($r as $c) echo "<td>" . htmlspecialchars($c) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    public function hard() {
        return false;
    }
}

class TableOutput2 extends TableOutput {
    public function get($res) {
        if ($res === false) {
            echo htmlspecialchars(mysql_error());
        } else {
            parent::get($res);
        }
    }
    public function eout() {
        return true;
    }
    public function hard() {
        return false;
    }
}

class RowOutput extends Output {
    public function get($res) {
        $r = mysql_fetch_row($res);
        foreach ($r as $c) echo htmlspecialchars($c) . ",";
    }

    public function hard() {
        return true;
    }

}

class RowOutput2 extends RowOutput {
    public function get($res) {
        if ($res === false) {
            echo htmlspecialchars(mysql_error());
        } else {
            parent::get($res);
        }
    }

    public function hard() {
        return true;
    }

    public function eout() {
        return true;
    }
}

class SingleOutput extends Output {
    public function get($res) {
        $r = mysql_fetch_row($res);
        echo htmlspecialchars($r[0]);
    }

    public function hard() {
        return true;
    }

}

class SingleOutput2 extends SingleOutput {
    public function get($res) {
        if ($res === false) {
            echo htmlspecialchars(mysql_error());
        } else {
            parent::get($res);
        }
    }

    public function hard() {
        return true;
    }

    public function eout() {
        return true;
    }
}


?>