<?php

require_once "tester.php";

$stage = @$argv[1];
$arg = @$argv[2];
$prefix = "http://localhost:8070/tests/";
if ($stage === "list") {
    $testpages = mytests("../mytests/tests/index.txt", $prefix, true);
    echo unman_list($testpages);    
} elseif ($stage === "clean") {
    echo "clean..\n";
    $testpages = mytests("../mytests/tests/index.txt", $prefix, false);
    foreach ($testpages as $ti => $tp) {
        echo "$ti\n";
        $tp->prepare();
    }
    echo "done\n";
} elseif ($stage === "process") {
    if ($arg === null) {
        die("need arg\n");
    }
    $testpages = mytests("../mytests/tests/index.txt", $prefix, true);
    $scanners = array(new ZeroScanner(), new Scanner_unmanaged($arg));
    runtests_multi($testpages, $scanners, 100, null);

} else {
    echo "list | clean | process\n";
}


?>