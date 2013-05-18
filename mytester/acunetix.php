<?php

$x = new SimpleXMLElement(file_get_contents($argv[1]));
foreach ($x->xpath("//ReportItem/Name[.='SQL injection' or .='Blind SQL Injection']/following-sibling::Affects") as $k) echo "$k\n";

?>
