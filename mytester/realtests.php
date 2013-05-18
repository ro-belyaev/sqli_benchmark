<?php

function realtests($urlfname) {
    $tf = file($urlfname);
    if ($tf === false) die("error opening '$urlfname'");
    $a = array();
    foreach ($tf as $t) {
        $t = trim($t);
        if ($t == '') continue;
        $a[] = new TestPage($t, "new RealTest()", false);
    }
    return $a;
}

?>