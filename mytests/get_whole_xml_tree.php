<?php

require_once "./transform_xml.php";

$stringXML = file_get_contents('./all_nodes.xml');

echo transform_xml($stringXML);
