<?php

require_once "scanner.php";
require_once "tester.php";

preparetmp();

$s = new Scanner_fisobe();

$s->prepare();

var_dump($s->run("http://127.0.0.1/~karim/mytests/tests/17.php?id=1"));

//$s->clean();



?>