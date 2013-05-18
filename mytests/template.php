<?php

abstract class Template {
    abstract public function output($s);
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

class SimpleTemplate extends Template {
    public function output($s) {
        echo "<html><head><title>mytests</title></head><body><h1>Hello!</h1>$s</body></html>";
    }
}

class BigRandTemplate extends Template {
    public $stable;
    
    public function __construct($stable = false) {
        $this->stable = $stable;
    }

    private function piece($content, $depth) {
        if ($depth == 0) {
            return $content;
        } else {
            $tags = array(array("<div>", "</div>"), array("<p>", ""), array("<span>", "</span>"),
                          array("<table><tr><td>", "</td></tr></table>"), array("<br>", ""));
            $t = $tags[array_rand($tags)];
            return $t[0] . $this->piece($content, $depth - 1) . $t[1];
        }
    }

    public function output($s) {
        if ($this->stable) {
            srand(crc32(md5($_SERVER["QUERY_STRING"])));
        }         
        echo "<html><head><title>mytests</title></head><body>";
        echo $this->piece($s, rand(5,10));
        $t = file("../webspam.txt");
        $res = '';
        $c = rand(900, 1100);
        for ($i = 0; $i < $c; ++$i) {
            $k = trim($t[array_rand($t)]);
            $l = str_replace(' ', '-', $k);
            $res .= "<a href=\"http://msu.ru/$l.html\">$k</a>\n";
        }
        echo $res;
        echo "</body></html>";        
    }

    public function hard() {
        return true;
    }    

}

class HeaderTemplate extends Template {
    public function output($s) {
        header('Location: ' . urlencode($s));
        echo "<html><head><title>mytests</title></head><body><h1>Good bye!</h1></body></html>";
    }
    
    public function hard() {
        return true;
    }
}

class WebspamTemplate extends Template {
    public function output($s) {
        $q = $_SERVER["QUERY_STRING"];
        echo "<html><head><title>mytests</title></head><body><h1>Hello!</h1>";
        echo "<div style=\"position:absolute; left:-4000px;width:2000px;\">" . $this->ws(md5($q)) . "</div>";
        echo $s;
        echo "<div style=\"position:absolute; left:-4000px;width:2000px;\">" . $this->ws(md5("t$q")) . "</div>"; 
        echo "</body></html>";
    }
    
    private function ws($s) {
        $h = 0;
        for ($i = 0; isset($s[$i]); ++$i) {
            $h = ($h * ord($s[$i]) * 29 + 15) % 10000000;
        }
        $t = file("../webspam.txt");
        srand($h);
        $res = '';
        $c = rand(900, 1100);
        for ($i = 0; $i < $c; ++$i) {
            $k = trim($t[array_rand($t)]);
            $l = str_replace(' ', '-', $k);
            $res .= "<a href=\"http://msu.ru/$l.html\">$k</a>\n";
        }
        return $res;
    }

    public function hard() {
        return true;
    }
}


?>