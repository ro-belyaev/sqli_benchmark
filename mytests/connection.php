<?php

$host = "127.0.0.1";
$login = "root";
$passw = "12345";
$db = "sqli_benchmark";
$table = "generation";
$generation_table = "generation";
$mytester_table = "mytester";
$launch_table = "scanners_launch";
$report_table = "report";
$custom_classes_table = "report_classes";
$report_scanners_table = "scanners_report";

$connection = mysql_connect($host, $login, $passw);
mysql_select_db($db, $connection);
