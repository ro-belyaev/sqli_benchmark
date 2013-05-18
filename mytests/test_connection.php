<?php

include_once('config.php');
$connection = mysql_connect($db_host, $db_login, $db_pass);
#mysql_select_db('information_schema');
#$query = 'SELECT table_name from tables';
#$result = mysql_query($query, $connection);

mysql_real_escape_string('abc');
