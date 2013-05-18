<?php

$string= file_get_contents("./all_nodes.xml");

$xml = new SimpleXMLElement($string);

$dependence_between_classes = $xml->xpath('/tree/dependences-between-classes');
$dependence_between_criterions = $xml->xpath('/tree/dependences-between-criterions');

$result = $xml->xpath('/tree/nodes/node');

$json = array();

$nodes = array();

foreach($result as $xml_node){
    $name= (string) $xml_node->name;
    $attr = $xml_node->attributes();
    $nodes[] = array(
	"data" => $name,
	"attr" => array(
	    'id' => (string) $attr->{'id'},
	    'data-parent_id' => (string) $attr->{'parent-id'}
	),
	"children" => array()
    );
}

$result = $xml->xpath('/tree/criterions/criterion');

foreach($result as $xml_node){
    $one_node = array();
    $parent_id = $xml_node->attributes()->container;
    $criterion_id = $xml_node->attributes()->id;
    $criterion = $xml_node->xpath('./condition');
    foreach($criterion as $class) {
	$name = (string) $class->description;
	$id = (string) $class->attributes()->value;
	$nodes[] = array(
	    "data" => $name,
	    "attr" => array(
		"id" => $criterion_id ."_". $id,
		"data-parent_id" => (string) $parent_id
	    ),
	    "children" => array()
	);
    }
}

$json['nodes'] = $nodes;
$json['xml'] = $string;
$json = json_encode($json);

//var_dump($nodes);
echo $json;
