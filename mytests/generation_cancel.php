<?php

require_once './constants.php';
require_once './connection.php';

if(!isset($_GET['id'])) {
    die('id GET parameter is required!\n');
}

$id = mysql_real_escape_string($_GET['id']);
$query = "UPDATE $table SET `state`=". STATE_CANCEL ." WHERE id='$id'";
mysql_query($query, $connection);

define('DIR_NAME', "$id");

require './clear.php';

