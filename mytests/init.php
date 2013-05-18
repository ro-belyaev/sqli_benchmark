<?php
require_once "config.php";

global $db_prefix;
$db_prefix = $db_pref;
global $db_conn;


if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

function mq($q, $mand = false) {
    global $db_conn;
    global $db_last;
    $db_last = $q;
    
    //$re = mysql_query($q);
    $re = mysql_query($q, $db_conn);
    if ($mand && ($re === false)) error_log("TESTERR in query '$q' : '" . mysql_error() . "'");
    return $re;
}

function dmp($a) {
    if ($a === false) return "false";
    if ($a === true) return "true";
    return is_array($a) ? ('{' . implode(",", $a) . '}') : $a;
}


?>
