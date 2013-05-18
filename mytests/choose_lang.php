<?php

function choose_language_by_get_param() {
    $available_lang = array("en", "ru");
    if(isset($_GET['lang'])) {
	//$get_lang = mysql_real_escape_string($_GET['lang']);
	$get_lang = $_GET['lang'];
	if(in_array($get_lang, $available_lang)) {
	    return $get_lang;
	}
    }
    return null;
}


function choose_language_by_accept_lang() {
    $available_lang = array("en", "ru");
    if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$string = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$proposed_lang = explode(',', $string);
	$pattern = "/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i";
	preg_match_all($pattern, $string, $matches);
	if(count($matches[1])) {
	    $langs = array_combine($matches[1], $matches[4]);
	    foreach($langs as $lang => $value) {
		if($value === '') {
		    $langs[$lang] = '1.0';
		}
	    }
	    arsort($langs, SORT_NUMERIC);
	    foreach($langs as $lang => $quality) {
		foreach($available_lang as $key => $value) {
		    if(strpos($lang, $value) === 0) {
			return $value;
		    }
		}
	    }
	}
    }
    return null;
}
