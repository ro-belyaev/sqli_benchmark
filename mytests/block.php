<?php

if (false === file_put_contents("tests/". DIR_NAME ."/allowed.txt", '%^127\.0\.0\.1$|^:1/128$|^0:0:0:0:0:0:0:1$%')) {
    die("error writing tests/". DIR_NAME ."/allowed.txt\n");
} else {
    echo "ok\n";
}

?>
