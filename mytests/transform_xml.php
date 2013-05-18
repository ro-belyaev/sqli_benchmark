<?php

//require_once('./choose_lang.php');

function transform_xml($xml_string) {
    $language = 'en';
    /*
    $cookie_name = 'sqli_benchmark_lang';

    if(isset($_COOKIE[$cookie_name])) {
	$language = $_COOKIE[$cookie_name];
    }
    else {
	if(($accept_lang = choose_language_by_accept_lang()) != null) {
	    $language = $accept_lang;
	}
	setcookie($cookie_name, $language);
    }
    */

    $xml = new DOMDocument();
    $xml->loadXML($xml_string);
    
    $xsl = new DOMDocument();
    $xsl->load('xslt_test.xslt');

    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);
    $proc->setParameter('', 'lang', $language);


    $json_data = array();

    $json_data['xml'] = $proc->transformToXML($xml);
    $json_data['tree'] = $xml_string;

    $json_data = json_encode($json_data);
    return $json_data;
}
