<?php

if (false === file_put_contents("tests/". DIR_NAME ."/allowed.txt", '%%')) {
    die("error writing tests/". DIR_NAME ."/allowed.txt\n");
} else {
    echo "ok\n";
}
?>
