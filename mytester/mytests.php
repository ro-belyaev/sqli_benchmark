<?php

function mytests($tfname, $tprefix, $noclean = false) {
    $tf = file($tfname);
    if ($tf === false) die("error opening '$tfname'");
    $a = array();
    foreach ($tf as $t) {
        $t = trim($t);
        if ($t == '') continue;
        if (!preg_match('%^(.*?)\t(.*)$%', $t, $m)) {
            die("bad line '$t'");
        }
        $a[] = new TestPage($tprefix . $m[1], $m[2], true, $noclean);
    }
    return $a;
}

?>