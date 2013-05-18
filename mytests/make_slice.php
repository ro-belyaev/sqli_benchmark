<?

function make_slice($nodes_from_user, $xml_string) {
    $xml_dom = new DOMDocument();
    $xml_dom->loadXML($xml_string);
    $xpath = new DOMXPath($xml_dom);

    $containers_array = array();
    $nodes_array = array();

    while($nodes_from_user) {
	$id = array_shift($nodes_from_user);
	$id_split = explode('_', $id);
	$criterion_id = $id_split[0];
	$condition_id = $id_split[1];
	
	if(array_key_exists($criterion_id, $nodes_array)) {
	    $nodes_array[$criterion_id][] = $condition_id;
	} else {
	    $nodes_array[$criterion_id] = array($condition_id);
	}
	
	$res = $xpath->query("//criterions/criterion[@id='$criterion_id']/@container");
	$container = $res->item(0)->value;
	if(!in_array($container, $containers_array)) {
	    $containers_array[] = $container;
	    $t = true;
	    $node_id = $container;
	    while($t) {
		$res = $xpath->query("//node[@id='$node_id']/@parent-id");
		$parent_id = $res->item(0)->value;
		if($parent_id == NULL) {
			//echo $node_id ."\n"; // !!!
		    die('ERROR!!! some parent node is omited!');
		} else if($parent_id != '-1' && !in_array($parent_id, $containers_array)) {
		    $containers_array[] = $parent_id;
		    $node_id = $parent_id;
		} else {
		    $t = false;
		}
	    }
	}
    }

    $containers_str = '^'. array_shift($containers_array) .'$';
    while($containers_array) {
	$containers_str .= ',^'. array_shift($containers_array) .'$';
    }

    $nodes_str = "";
    foreach($nodes_array as $criterion => $conditions) {
	foreach($conditions as $condition) {
	    $nodes_str .= '^'. $criterion . '_'. $condition .',';
	}
	array_shift($nodes_array);
    }

    //nodes_str format: ^criterionId1_conditionId1,^criterionId1_conditionId2,^criterionId2_ ...
    //containers_str format: ^containerId1$,^containerId2$,^containerId3$

    $xsl = new DOMDocument();
    $xsl->load('./make_slice.xsl');

    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);
    $proc->setParameter('', 'nodes_str', $nodes_str);
    $proc->setParameter('', 'containers_str', $containers_str);

    $slice_xml = $proc->transformToXML($xml_dom);

    //var_dump($nodes_str);
    //var_dump($nodes_array);
    //var_dump($containers_str);
    //var_dump($containers_array);
    //var_dump($slice_xml);
    return $slice_xml;
}
