<?php

function check_dependences($nodes_from_client, $simple_xml) {
    if(count($nodes_from_client) == 0) {
	die('no nodes from client');
    }

    if(!general_check($nodes_from_client, $simple_xml)) {
	die('criterions ID or values were modified');
    }

    $nodesID = array();

    foreach($nodes_from_client as $node) {
	$pattern = "/^(.+)_.+$/";
	preg_match($pattern, $node, $matches);
	$nodesID[] = $matches[1];
    }

    if(!criterions_dependence_check($nodesID, $simple_xml)
	    || !classes_dependence_check($nodesID, $simple_xml)) {
	die('bad dependence');
    }
}



function general_check($nodes, $xml) {
    $all_criterions = array();
    $result = $xml->xpath('/tree/criterions/criterion');
    foreach($result as $criterion) {
	$id = (string) $criterion->attributes()->id;
	$classes = array();
	$values = $criterion->xpath('./condition');
	foreach($values as $value) {
	    $classes[] = (string) $value->attributes()->value;
	}
	$all_criterions[$id] = $classes;
    }

    foreach($nodes as $node) {
	//$node = mysql_real_escape_string($node);
	$pattern = "/^(.+)_(.+)$/";
	preg_match($pattern, $node, $matches);
	if(count($matches) == 0) {
	    //echo "no match\n";
	    return false;
	}
	$node_id = $matches[1];
	$node_value = $matches[2];
	if(!in_array($node_id, array_keys($all_criterions))) {
	    //echo "no id\n";
	    return false;
	}
	if(!in_array($node_value, $all_criterions[$node_id])) {
	    //echo "no value\n";
	    return false;
	}
    }
    return true;
}

function criterions_dependence_check($nodes, $xml) {
    $result = $xml->xpath('/tree/dependences-between-criterions/dependence');
    foreach($result as $dependences) {
	$type = (string) $dependences->attributes()->type;
	$dependent_criterions = $dependences->xpath('./dependent-criterion');
	if($type == 'each') {
	    $check = true;
	    foreach($dependent_criterions as $criterion) {
		$id = (string) $criterion->attributes()->id;
		if(!in_array($id, $nodes)) {
		    $check = false;
		}
	    }
	    if(!$check) {
		//echo "no each\n";
		return false;
	    }
	}
	else if($type == 'at-least-one') {
	    $check = false;
	    foreach($dependent_criterions as $criterion) {
		$id = (string) $criterion->attributes()->id;
		if(in_array($id, $nodes)) {
		    $check = true;
		}
	    }
	    if(!$check) {
		//echo "no at-least-one\n";
		return false;
	    }
	}
    }
    return true;
}

function classes_dependence_check($nodes, $xml) {
    $already_seen = array();
    foreach($nodes as $node) {
	$result = $xml->xpath("/tree/dependences-between-classes/dependence/dependent-criterion[@id='$node']");
	if(!empty($result)) {
	    $current_criterion = $result[0];
	    if(!in_array($current_criterion, $already_seen)){
		$siblings = $current_criterion->xpath('preceding-sibling::* | following-sibling::*');
		foreach($siblings as $sibling) {
		    $id = (string) $sibling->attributes()->id;
		    $already_seen[] = $id;
		    if(!in_array($id, $nodes)) {
			//echo "no $id\n";
			return false;
		    }
		}
	    }
	}
    }
    return true;
}
