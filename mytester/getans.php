<?php


function getans($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_ENCODING, 0); 
    curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 600);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
    $t = curl_exec($ch);
    if (curl_errno($ch) == 0) {
        curl_close($ch);
        return $t;
    } else {
        if (curl_errno($ch) == 7 && preg_match('%Cannot assign requested address\s*$%', curl_error($ch))) {
            curl_close($ch);
            //echo "curl retry..\n"; // COMMENT HERE !!!
            sleep(5);
            return getans($url);
        }
        //echo "curl failed: '$url' - " . curl_errno($ch) . " "  . curl_error($ch) . "\n"; // COMMENT HERE !!!
        curl_close($ch);
        return false;
    }
}


?>
