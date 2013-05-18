<?php

//criterions

$config_display_errors_criterion = array('true' => 'true', 'false' => 'false');

$config_connection_criterion = array('true' => 'true', 'false' => 'false');


$select_clause_field_backticks = array('backticks' => '\'`\'', 'no-backticks' => 'false');

$select_clause_field_parentheses = array('0' => '0', '1' => '1', '2' => '2');

$select_clause_field_concat = array('concat' => 'true', 'no-concat' => 'false');


$where_clause_parameter_type_int = array('int' => 'int');

$where_clause_parameter_type_str = array('str' => 'str');

$where_clause_field_quote_int = array('false' => 'false', 'single-quote' => '"\'"', 'double-quote' => "'\"'");

$where_clause_field_quote_str = array('single-quote' => '"\'"', 'double-quote' => "'\"'");

$where_clause_field_func_int = array('true' => 'true', 'false' => 'false');

$where_clause_field_func_str = array('true' => 'true', 'false' => 'false');

$where_clause_field_parentheses = array('0' => '0', '1' => '1', '2' => '2');

$where_clause_field_exist = array('true' => 'true', 'false' => 'false');

$where_clause_field_invert_parameter = array('true' => 'true', 'false' => 'false');

$where_clause_field_prefix = array('empty' => '', 'zero-and' => '0 and ', 'one-or' => '1 or ');

$where_clause_field_postfix = array('empty' => '', 'and-zero' => ' and 0', 'or-one' => ' or 1');


$order_by_clause_field_num_diff = array('true' => 'true', 'false' => 'false');

$order_by_clause_field_num_view = array('all' => 'all', 'one' => 'one', 'first' => 'first');

$order_by_clause_field_name_diff = array('true' => 'true', 'false' => 'false');

$order_by_clause_field_name_view = array('all' => 'all', 'one' => 'one', 'first' => 'first');

$order_by_clause_field_name_quote = array('false' => 'false', 'backticks' => '\'`\'');

$order_by_clause_field_expr_diff = array('true' => 'true', 'false' => 'false');

$order_by_clause_field_expr_view = array('all' => 'all', 'one' => 'one', 'first' => 'first');

$order_by_clause_field_expr_func = array('true' => 'true', 'false' => 'false');

$order_by_clause_field_expr_parentheses = array('0' => '0', '1' => '1', '2' => '2');

$order_by_clause_field_way_diff = array('true' => 'true', 'false' => 'false');

$order_by_clause_field_way_view = array('all' => 'all', 'one' => 'one', 'first' => 'first');


$data_filtering_delete = array('blank' => "new StrDel(' ')", 'single' => "new StrDel(\"'\")", 'comment' => "new StrDel(array('/*', '*/'))");

$data_filtering_escape = array('single' => 'new QuoteEscape1()', 'double' => 'new QuoteEscape2()', 'single-and-double' => 'new QuoteEscape3()');

$data_filtering_zero = array('real' => 'new ZeroFilter()');

$data_filtering_real_escape = array('real' => 'new RealEscape()');

$data_filtering_int = array('real' => 'new IntFilter()');

$data_filtering_cut_15 = array('real' => 'new LengthCut(15)');


$output_criterion = array('table' => 'new TableOutput()', 'table2' => 'new TableOutput2()', 'row' => 'new RowOutput()',
		'row2' => 'new RowOutput2()', 'single' => 'new SingleOutput()', 'single2' => 'new SingleOutput2()',
		'simple' => "new SimpleOutput()"
		);


$part_of_http_response = array('simple' => 'new SimpleTemplate()', 'big-rand-false' => 'new BigRandTemplate()',
		'big-rand-true' => 'new BigRandTemplate(true)', 'header' => 'new HeaderTemplate()',
		//'webspam' => "new WebspamTemplate()"
		);


$config_display_errors = array();
$config_connection = array();

$select_quote = array();
$select_func = array();
$select_park = array();

$where_type_int = array();
$where_quote_int = array();
$where_func_int = array();
$where_parc_int = array();
$where_exist_int = array();
$where_ltor_int = array();
$where_pre_int = array();
$where_post_int = array();

$where_type_str = array();
$where_quote_str = array();
$where_func_str = array();
$where_parc_str = array();
$where_exist_str = array();
$where_ltor_str = array();
$where_pre_str = array();
$where_post_str = array();
$order_by_num_view = array();
$order_by_num_diff = array();

$order_by_name_view = array();
$order_by_name_diff = array();
$order_by_name_quote = array();

$order_by_expr_view = array();
$order_by_expr_diff = array();
$order_by_expr_func = array();
$order_by_expr_park = array();

$order_by_way_view = array();
$order_by_way_diff = array();

$confs = array();
$filters = array();
$processes = array();
$outputs = array();
$templates = array();


foreach($criterions as $id => $classes) {
    foreach($classes as $value) {
	if($id == 'config-display-errors-criterion') {
	    $config_display_errors[] = $config_display_errors_criterion[$value];
	}
	else if($id == 'config-connection-criterion') {
	    $config_connection[] = $config_connection_criterion[$value];
	}
	else if($id == 'select-clause-field-backticks-surrounding'){
	    $select_quote[] = $select_clause_field_backticks[$value];
	}
	else if($id == 'select-clause-field-parentheses-surrounding'){
	    $select_park[] = $select_clause_field_parentheses[$value];
	}
	else if($id == 'select-clause-field-concat'){
	    $select_func[] = $select_clause_field_concat[$value];
	}

	else if($id == 'where-clause-parameter-type-int') {
	    $where_type_int[] = $where_clause_parameter_type_int[$value];
	}
	else if($id == 'where-clause-field-quote-int') {
	    $where_quote_int[] = $where_clause_field_quote_int[$value];
	}
	else if($id == 'where-clause-field-func-int') {
	    $where_func_int[] = $where_clause_field_func_int[$value];
	}
	else if($id == 'where-clause-field-parentheses-surrounding-int') {
	    $where_parc_int[] = $where_clause_field_parentheses[$value];
	}
	else if($id == 'where-clause-field-exist-int') {
	    $where_exist_int[] = $where_clause_field_exist[$value];
	}
	else if($id == 'where-clause-field-invert-parameter-int') {
	    $where_ltor_int[] = $where_clause_field_invert_parameter[$value];
	}
	else if($id == 'where-clause-field-prefix-int') {
	    $where_pre_int[] = $where_clause_field_prefix[$value];
	}
	else if($id == 'where-clause-field-postfix-int') {
	    $where_post_int[] = $where_clause_field_postfix[$value];
	}

	else if($id == 'where-clause-parameter-type-str') {
	    $where_type_str[] = $where_clause_parameter_type_str[$value];
	}
	else if($id == 'where-clause-field-quote-str') {
	    $where_quote_str[] = $where_clause_field_quote_str[$value];
	}
	else if($id == 'where-clause-field-func-str') {
	    $where_func_str[] = $where_clause_field_func_int[$value];
	}
	else if($id == 'where-clause-field-parentheses-surrounding-str') {
	    $where_parc_str[] = $where_clause_field_parentheses[$value];
	}
	else if($id == 'where-clause-field-exist-str') {
	    $where_exist_str[] = $where_clause_field_exist[$value];
	}
	else if($id == 'where-clause-field-invert-parameter-str') {
	    $where_ltor_str[] = $where_clause_field_invert_parameter[$value];
	}
	else if($id == 'where-clause-field-prefix-str') {
	    $where_pre_str[] = $where_clause_field_prefix[$value];
	}
	else if($id == 'where-clause-field-postfix-str') {
	    $where_post_str[] = $where_clause_field_postfix[$value];
	}

	else if($id == 'order-by-clause-field-num-diff'){
	    $order_by_num_diff[] = $order_by_clause_field_num_diff[$value];
	}
	else if($id == 'order-by-clause-field-num-view'){
	    $order_by_num_view[] = $order_by_clause_field_num_view[$value];
	}
	else if($id == 'order-by-clause-field-name-diff'){
	    $order_by_name_diff[] = $order_by_clause_field_name_diff[$value];
	}
	else if($id == 'order-by-clause-field-name-view'){
	    $order_by_name_view[] = $order_by_clause_field_name_view[$value];
	}
	else if($id == 'order-by-clause-field-name-quote'){
	    $order_by_name_quote[] = $order_by_clause_field_name_quote[$value];
	}
	else if($id == 'order-by-clause-field-expr-diff'){
	    $order_by_expr_diff[] = $order_by_clause_field_expr_diff[$value];
	}
	else if($id == 'order-by-clause-field-expr-view'){
	    $order_by_expr_view[] = $order_by_clause_field_expr_view[$value];
	}
	else if($id == 'order-by-clause-field-expr-func'){
	    $order_by_expr_func[] = $order_by_clause_field_expr_func[$value];
	}
	else if($id == 'order-by-clause-field-expr-parentheses-surrounding'){
	    $order_by_expr_park[] = $order_by_clause_field_expr_parentheses[$value];
	}
	else if($id == 'order-by-clause-field-way-diff'){
	    $order_by_way_diff[] = $order_by_clause_field_way_diff[$value];
	}
	else if($id == 'order-by-clause-field-way-view'){
	    $order_by_way_view[] = $order_by_clause_field_way_view[$value];
	}
	else if($id == 'data-filtering-zero'){
	    $filters[] = $data_filtering_zero[$value];
	}
	else if($id == 'data-filtering-int'){
	    $filters[] = $data_filtering_int[$value];
	}
	else if($id == 'data-filtering-cut-15'){
	    $filters[] = $data_filtering_cut_15[$value];
	}
	else if($id == 'data-filtering-real-escape'){
	    $filters[] = $data_filtering_real_escape[$value];
	}
	else if($id == 'data-filtering-delete-criterion'){
	    $filters[] = $data_filtering_delete[$value];
	}
	else if($id == 'data-filtering-escape-criterion'){
	    $fiters[] = $data_filtering_escape[$value];
	}
	else if($id == 'output-criterion'){
	    $outputs[] = $output_criterion[$value];
	}
	else if($id == 'part-of-http-response'){
	    $templates[] = $part_of_http_response[$value];
	}
    }
}



// Confs
foreach($config_display_errors as $display_errors) {
    foreach($config_connection as $connection) {
	$confs[] = "new Uconf($display_errors, $connection)";
    }
}


// Select
foreach($select_quote as $quote) {
    foreach($select_func as $func) {
	foreach($select_park as $park) {
	    $processes[] = "new Field($quote, $func, $park, '', '')";
	}
    }
}


// WhereInt
if(count($where_quote_int) > 0) {
    foreach($where_type_int as $type) {
	foreach($where_quote_int as $quote) {
	    foreach($where_func_int as $func) {
		foreach($where_parc_int as $parc) {
		    foreach($where_exist_int as $exist) {
			foreach($where_ltor_int as $ltor) {
			    foreach($where_pre_int as $pre) {
				foreach($where_post_int as $post) {
				    $processes[] = "new Where('$type', $quote, $func, $parc, $exist, $ltor, '$pre', '$post')";
				}
			    }
			}
		    }
		}
	    }
	}
    }
}

// WhereStr
if(count($where_quote_str) > 0) {
    foreach($where_type_str as $type) {
	foreach($where_quote_str as $quote) {
	    foreach($where_func_str as $func) {
		foreach($where_parc_str as $parc) {
		    foreach($where_exist_str as $exist) {
			foreach($where_ltor_str as $ltor) {
			    foreach($where_pre_str as $pre) {
				foreach($where_post_str as $post) {
				    $processes[] = "new Where('$type', $quote, $func, $parc, $exist, $ltor, '$pre', '$post')";
				}
			    }
			}
		    }
		}
	    }
	}
    }
}

// OrderByName
if(count($order_by_num_view) > 0) {
    foreach($order_by_num_view as $view) {
	foreach($order_by_num_diff as $diff) {
	    $processes[] = "new OrderByNum($diff, '$view')";
	}
    }
}

// OrderByName
if(count($order_by_name_view) > 0) {
    foreach($order_by_name_view as $view) {
	foreach($order_by_name_diff as $diff) {
	    foreach($order_by_name_quote as $quote) {
		$processes[] = "new OrderByName($diff, '$view', $quote)";
	    }
	}
    }
}

// OrderByExpr
if(count($order_by_expr_view) > 0) {
    foreach($order_by_expr_view as $view) {
	foreach($order_by_expr_diff as $diff) {
	    foreach($order_by_expr_func as $func) {
		foreach($order_by_expr_park as $park) {
		    $processes[] = "new OrderByExpr($diff, '$view', $func, $park, '', '')";
		}
	    }
	}
    }
}

// OrderByWay
if(count($order_by_way_view) > 0) {
    foreach($order_by_way_view as $view) {
	foreach($order_by_way_diff as $diff) {
	    $processes[] = "new OrderByWay($diff, '$view')";
	}
    }
}


