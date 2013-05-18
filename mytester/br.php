<?php
require_once "testres.php";
array_shift($argv);
foreach ($argv as $t) {
  echo "$t\n";
  $x = (unserialize(gzuncompress(file_get_contents($t))));
  //  if (strpos($x->scanlog, 'exception') !== false) {
    var_dump($x);
    //break;
    //}
}

?>
