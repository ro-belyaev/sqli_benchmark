<?php

$x = new SimpleXMLElement(file_get_contents($argv[1]));
foreach ($x->xpath("//issue/name[.='SQL injection']/following-sibling::path") as $k) echo "$k\n";

?>
