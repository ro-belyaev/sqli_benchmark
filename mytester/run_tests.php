<?php
error_reporting(E_ALL);

require_once "tester.php";

if (isset($argv[1])) {
    $resd = $argv[1];
    echo "CONTINUE from $resd ? sleep..";
    sleep(10);
    echo "go\n";
} else {
    $resd = null;
}


//$scanners = array(new ZeroScanner(), new Scanner_sqlmap());

//$scanners = array(new Scanner_skipfish());

$scanners = array(
                  new ZeroScanner(),
                  //new Scanner_arachni_0_3(),
                  //new Scanner_sqlmap_0_9(),
                  //new Scanner_sqlmap_r4366(),                                                                              
                  //new Scanner_sqlmap_r4365(true),                                                                          
                  new Scanner_wapiti_2_2_1(),
                  //new Scanner_skipfish_1_81b(),                                                                            
                  //new Scanner_skipfish_2_03b(),
                  //new Scanner_skipfish_2_06b(),
                  //new Scanner_w3af_1_0_rc5(),                                                                              
                  //new Scanner_w3af_1_0_stable(),                                                                           
                  //new Scanner_w3af_1_1(),
                  //new Scanner_unmanaged("burp-res.txt","burpsuite_pro_v1.4.07"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_sqlmap_0_9.txt", "comb_skipfish_2_03b_sqlmap_0_9"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_w3af_1_0_stable.txt", "comb_skipfish_2_03b_w3af_1_0_stable"),
                  //new Scanner_unmanaged("comb_sqlmap_0_9_w3af_1_0_stable.txt", "comb_sqlmap_0_9_w3af_1_0_stable"),
                  //new Scanner_unmanaged("comb_skipfish_2_03b_sqlmap_0_9_w3af_1_0_stable.txt", "comb_skipfish_2_03b_sqlmap_0_9_w3af_1_0_stable"),

                  );

$testpages = 
    array_merge(
                // mytests($index, $prefix) - загружает тесты из данного файла, 
                // в вебе они должны быть доступны с данного пути
                mytests("../mytests/tests/index.txt", "http://localhost/generation/mytests/tests/")

                // realtests загружает реальные тесты из файла (просто список url)
                //                realtests("real.txt")
                
                );

// третий параметр - количество потоков
runtests_multi($testpages, $scanners, 800, $resd);

?>
