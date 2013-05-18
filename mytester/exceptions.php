<?php

class TestExc extends Exception {
    public function __construct($s = "nomsg") {
        parent::__construct($s);
    } 
}


?>