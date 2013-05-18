<?php

$host = "127.0.0.1";
$login = "root";
$passw = "12345";
$db = "sqli_benchmark";
$table = "generation";

$connection = mysql_connect($host, $login, $passw);
mysql_select_db($db, $connection);
