<?php

abstract class Process {
    protected $table;

    public function __construct() {
        global $db_prefix;
        $this->table = $db_prefix . get_class($this);
    }

    public function table() {
        return $this->table;
    }   

    abstract public function prepare();
    abstract public function params();
    abstract public function run($p);

    public function quoted() {
        return false;
    }

    public function infolinks() {
        return array();
    }

    public function __toString() {
        return "unsuppored";
    }
    
    public function info() {
        return "No info";
    }

    public function name() {
        return get_class($this);
    }

    public function hard() {
        return false;
    }

    abstract public function type();    
}

class SampleProcess extends Process {
    public function prepare() {
	if (false === mq("create table if not exists $this->table (id int, name char(120), tag char(120), primary key(id)) engine=MyISAM CHARACTER SET utf8", true)) return false;
        if ( false === mq("insert ignore into $this->table (id, name, tag) values (1, 'aaa', 't'), (2, 'bbb', 't'), (3, 'ccc', 't')", true)) return false;
        return true;
    }

    public function params() {
        return array("id" => 1);
    }
    
    public function run($p) {
        return mq("select id,name from $this->table where id=$p[id]");
    }

    public function quoted() {
        return false;
    }
    
    public function type() {
        return 'int';
    }

}

abstract class ProcBase1 extends Process {
    public function prepare() {
        if (false === mq("create table if not exists $this->table (id int, name char(120), tag char(120), primary key(id)) engine=MyISAM CHARACTER SET utf8", true)) return false;
        if (false === mq("insert ignore into $this->table (id, name, tag) values (1, 'aaa', 't'), (2, 'bbb', 't'), (3, 'ccc', 't')", true)) return false;
        return true;
    }
}


class Where extends ProcBase1 {
    public $type, $quote, $func, $parc, $exist, $ltor, $pre, $post;

    public function __construct($type, $quote, $func, $parc, $exist, $ltor, $pre, $post) {
        parent::__construct();
        if ($type !== 'int' && $type !== 'str') die('$type must be \'int\' or \'str\'');
        if ($type === 'str' && $quote === false) die('str must be quoted');
        $this->type = $type;
        $this->quote = $quote;
        $this->func = $func;
        $this->parc = $parc;
        $this->exist = $exist;
        $this->ltor = $ltor;
        $this->pre = $pre;
        $this->post = $post;
    }

    public function __toString() {
        return "Where($this->type, " . dmp($this->quote) . ", " . dmp($this->func) . ", $this->parc, " . dmp($this->exist) .
            ", " . dmp($this->ltor) . ", '$this->pre', '$this->post')"; 
    }

    public function type() {
        return $this->type;
    }
    
    public function hard() {
        return $this->pre !== '' || $this->post !== '' || $this->parc !== 0 || $this->func !== false;
    }

    public function info() {
        if ($this->type === 'int') {
            $t = $this->quote . '$id' . $this->quote;
            if ($this->func) $t = "abs($t)";
            if ($this->ltor) {
                $t = "$t=id";
            } else {
                $t = "id=$t";
            }
        } else {
            $t = $this->quote . '$name' . $this->quote;
            if ($this->func) $t = "concat($t)";
            if ($this->ltor) {
                $t = "$t=name";
            } else {
                $t = "name=$t";
            }
        }
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        return $t;
    }

    public function params() {
        if ($this->type === 'int') {
            return array("id" => $this->exist ? 1 : 555);
        } else {
            return array("name" => $this->exist ? 'aaa' : 'xxx');
        }
    }
    
    public function run($p) {
        if ($this->type === 'int') {
            $t = $this->quote . $p['id'] . $this->quote;
            if ($this->func) $t = "abs($t)";
            if ($this->ltor) {
                $t = "$t=id";
            } else {
                $t = "id=$t";
            }
        } else {
            $t = $this->quote . $p['name'] . $this->quote;
            if ($this->func) $t = "concat($t)";
            if ($this->ltor) {
                $t = "$t=name";
            } else {
                $t = "name=$t";
            }
        }
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        return mq("select id, name from $this->table where $t");
    }

    public function quoted() {
        return $this->quote;
    }
}

class Field extends ProcBase1 {
    public $quote, $func, $parc, $pre, $post;

    public function __construct($quote, $func, $parc, $pre, $post) {
        if ($quote !== '`' && $quote !== false) die('$quote must be \'`\' or false');
        parent::__construct();
        $this->quote = $quote;
        $this->func = $func;
        $this->parc = $parc;
        $this->pre = $pre;
        $this->post = $post;
    }

    public function type() {
        return false;
    }

    public function params() {
        return array("info" => "name");
    }
    
    public function run($p) {
        $t = $p['info'];
        $t = $this->quote . $t . $this->quote;
        if ($this->func) $t = "concat($t)";
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        return mq("select id, $t from $this->table where name='aaa'");
    }
    
    public function info() {
        $t = '$info';
        $t = $this->quote . $t . $this->quote;
        if ($this->func) $t = "concat($t)";
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        return $t;
    }

    public function quoted() {
        return $this->quote;
    }
    
}

class OrderByNum extends ProcBase1 {
    public $diff, $view;

    public function __construct($diff, $view) {
        if ($view !== 'all' && $view !== 'one' && $view !== 'first') die('$type must be \'all\' or \'one\' or \'first\'');
        $this->diff = $diff;
        $this->view = $view;        
        parent::__construct();
    }

    public function type() {
        return true;
    }

    public function params() {
        return array("column" => ($this->diff ? 2 : 3));
    }

    public function run($p) {
        $t = "$p[column]";
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return mq("select id, name, tag from $this->table $t");
    }

    public function info() {
        $t = '$column';
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return $t;
    }    
}

class OrderByName extends ProcBase1 {
    public $diff, $view, $quote;

    public function __construct($diff, $view, $quote) {
        if ($view !== 'all' && $view !== 'one' && $view !== 'first') die('$type must be \'all\' or \'one\' or \'first\'');
        if ($quote !== '`' && $quote !== false) die('$quote must be \'`\' or false');
        $this->diff = $diff;
        $this->view = $view;
        $this->quote = $quote;
        parent::__construct();
    }

    public function type() {
        return false;
    }

    public function params() {
        return array("column" => ($this->diff ? "name" : "tag"));
    }

    public function run($p) {
        $t = "$p[column]";
        $t = $this->quote . $t . $this->quote;
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return mq("select id, name, tag from $this->table $t");
    }

    public function info() {
        $t = '$column';
        $t = $this->quote . $t . $this->quote;
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return $t;
    }    

    public function quoted() {
        return $this->quote;
    }
}

class OrderByExpr extends ProcBase1 {
    public $diff, $view, $func, $parc, $pre, $post;

    public function __construct($diff, $view, $func, $parc, $pre, $post) {
        if ($view !== 'all' && $view !== 'one' && $view !== 'first') die('$type must be \'all\' or \'one\' or \'first\'');
        $this->diff = $diff;
        $this->view = $view;
        $this->func = $func;
        $this->parc = $parc;
        $this->pre = $pre;
        $this->post = $post;
        parent::__construct();
    }

    public function params() {
        return array("cost" => 2);
    }

    public function type() {
        return true;
    }

    public function run($p) {
        $t = "$p[cost]";
        if ($this->func) $t = "abs($t)";
        $t = "$t*" . ($this->diff ? "id" : "length(tag)");
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return mq("select id, name, tag from $this->table $t");
    }

    public function info() {
        $t = '$cost';
        if ($this->func) $t = "abs($t)";
        $t = "$t*id";
        $t = $this->pre . str_repeat('(', $this->parc) . $t . str_repeat(')', $this->parc) . $this->post;
        $t = "order by $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return $t;
    }    

}



class OrderByWay extends ProcBase1 {
    public $diff, $view;

    public function __construct($diff, $view) {
        if ($view !== 'all' && $view !== 'one' && $view !== 'first') die('$type must be \'all\' or \'one\' or \'first\'');
        $this->diff = $diff;
        $this->view = $view;        
        parent::__construct();
    }

    public function params() {
        return array("order" => "asc");
    }

    public function type() {
        return false;
    }

    public function run($p) {
        $t = "$p[order]";
        $f = $this->diff ? "id" : "tag";
        $t = "order by $f $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return mq("select id, name, tag from $this->table $t");
    }

    public function info() {
        $t = '$order';
        $f = $this->diff ? "id" : "tag";
        $t = "order by $f $t";
        if ($this->view === 'one') {
            $t = "where id=1 $t";
        } elseif ($this->view === 'first') {
            $t = "$t limit 1";
        }
        return $t;
    }    
}


?>
