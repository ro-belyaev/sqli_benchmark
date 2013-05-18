<?php

$xml = new DOMDocument();
$xml->load('./all_scanners.xml');

$xsl = new DOMDocument();
$xsl->load('./transform_scanner_xml.xsl');

$proc = new XSLTProcessor();
$proc->importStyleSheet($xsl);

$json_data = array();

$json_data['xml'] = $proc->transformToXML($xml);

$json_data = json_encode($json_data);
echo $json_data;
