<?php

$pid = pcntl_fork();

if($pid == -1) {
	echo "fail\n";
} else if($pid) {
	echo "parent process\n";
} else {
	echo "child process\n";
}
