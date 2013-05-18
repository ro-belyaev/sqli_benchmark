<?php

require_once "./custom_classes.php";


function map_test($t, $xml_slice, $vuln) {
    return ($vuln == $t->vuln())
	&& map_configuration($t, $xml_slice)
	&& map_process($t, $xml_slice)
	&& map_filter($t, $xml_slice)
	&& map_output($t, $xml_slice)
	&& map_template($t, $xml_slice);
}



function map_configuration($t, $xml_slice) {
    global $config_criterion;
    global $config_display_errors;
    global $config_connection;

    $xpath = new DOMXPath($xml_slice);

    // check configuration criterion

    $conf_disp_err_crt = $config_criterion["display_errors"];
    $conf_connection_crt = $config_criterion["no_sleep"];

    $conf_disp_err_cond = $config_display_errors[conv_bool_to_str($t->conf->display_errors)];
    $conf_connection_cond = $config_connection[conv_bool_to_str($t->conf->no_sleep)];

    $conf_disp_err_xpath = "//criterion[@id='$conf_disp_err_crt']/condition[@value='$conf_disp_err_cond']";
    $conf_connection_xpath = "//criterion[@id='$conf_connection_crt']/condition[@value='$conf_connection_cond']";

    $conf_disp_err_check = $xpath->query($conf_disp_err_xpath);
    $conf_connection_check = $xpath->query($conf_connection_xpath);

    $disp_err_check = $conf_disp_err_check->length;
    $connection_check = $conf_connection_check->length;
    if($disp_err_check > 1 || $disp_err_check > 1) {
	die("wrong xml or mapping configuration function");
    } else if($disp_err_check == 1 && $connection_check == 1) {
	return true;
    } else {
	return false;
    }
}


function map_process($t, $xml_slice) {
    $process_name = get_class($t->process);
    if($process_name == "Field") {
	return map_select_process($t, $xml_slice);
    } else if($process_name == "Where") {
	return map_where_process($t, $xml_slice);
    } else if($process_name == "OrderByNum") {
	return map_order_by_num_process($t, $xml_slice);
    } else if($process_name == "OrderByName") {
	return map_order_by_name_process($t, $xml_slice); 
    } else if($process_name == "OrderByExpr") {
	return map_order_by_expr_process($t, $xml_slice);
    } else if($process_name == "OrderByWay") {
	return map_order_by_way_process($t, $xml_slice);
    } else {
	die("an error in map_process function");
    }
}


function map_select_process($t, $xml_slice) {
    // !!! we should ensure here, that $t->process is Field class !!!
    //global $select_clause_criterion;
    global $select_clause_quote_criterion;
    global $select_clause_quote_condition;
    global $select_clause_func_criterion;
    global $select_clause_func_condition;
    global $select_clause_parc_criterion;
    global $select_clause_parc_condition;

    $xpath = new DOMXPath($xml_slice);

    $select_crt = $select_clause_quote_criterion[0];
    $select_crt_xpath = "//criterion[@id='$select_crt']";
    $select_crt_check = $xpath->query($select_crt_xpath);
    $select_check = $select_crt_check->length;
    if($select_check == 0) {
	// out xml slice doesn't have Select clause class
	return false;
    } else {
	$quote = $t->process->quote;
	$func = $t->process->func;
	$parc = $t->process->parc;
	if(is_bool($quote)) {
	    $quote = conv_bool_to_str($quote);
	}
	if(is_bool($func)) {
	    $func = conv_bool_to_str($func);
	}
	if(is_int($parc)) {
	    $parc = strval($parc);
	}

	$select_quote_cond = $select_clause_quote_condition[$quote];
	$select_func_cond = $select_clause_func_condition[$func];
	$select_parc_cond = $select_clause_parc_condition[$parc];

	$select_quote_crt = $select_clause_quote_criterion[0];
	$select_func_crt = $select_clause_func_criterion[0];
	$select_parc_crt = $select_clause_parc_criterion[0];

	$select_quote_xpath = "//criterion[@id='$select_quote_crt']/condition[@value='$select_quote_cond']";
	$select_func_xpath = "//criterion[@id='$select_func_crt']/condition[@value='$select_func_cond']";
	$select_parc_xpath = "//criterion[@id='$select_parc_crt']/condition[@value='$select_parc_cond']";

	$select_quote_check = $xpath->query($select_quote_xpath);
	$select_func_check = $xpath->query($select_func_xpath);
	$select_parc_check = $xpath->query($select_parc_xpath);

	$quote_check = $select_quote_check->length;
	$func_check = $select_func_check->length;
	$parc_check = $select_parc_check->length;

	/*
	echo $quote ."\n";
	echo $func ."\n";
	echo $parc ."\n\n";

	echo $select_quote_xpath ."\n";
	echo $select_func_xpath ."\n";
	echo $select_parc_xpath ."\n\n";

	echo $quote_check ."\n";
	echo $func_check ."\n";
	echo $parc_check ."\n";
	*/

	if($quote_check > 1 || $func_check > 1 || $parc_check > 1) {
	    die("wrong xml or mapping select function");
	} else if($quote_check == 1 && $func_check == 1 && $parc_check == 1) {
	    return true;
	} else {
	    return false;
	}
    }
}


function map_where_process($t, $xml_slice) {
    // !!! we should ensure here, that $t->process is Where class !!!

    $xpath = new DOMXPath($xml_slice);

    global $where_clause_int_type_criterion;
    global $where_clause_int_type_condition;
    global $where_clause_int_quote_criterion;
    global $where_clause_int_quote_condition;
    global $where_clause_int_func_criterion;
    global $where_clause_int_func_condition;
    global $where_clause_int_parc_criterion;
    global $where_clause_int_parc_condition;
    global $where_clause_int_exist_criterion;
    global $where_clause_int_exist_condition;
    global $where_clause_int_invert_criterion;
    global $where_clause_int_invert_condition;
    global $where_clause_int_prefix_criterion;
    global $where_clause_int_prefix_condition;
    global $where_clause_int_postfix_criterion;
    global $where_clause_int_postfix_condition;


    global $where_clause_str_type_criterion;
    global $where_clause_str_type_condition;
    global $where_clause_str_quote_criterion;
    global $where_clause_str_quote_condition;
    global $where_clause_str_func_criterion;
    global $where_clause_str_func_condition;
    global $where_clause_str_parc_criterion;
    global $where_clause_str_parc_condition;
    global $where_clause_str_exist_criterion;
    global $where_clause_str_exist_condition;
    global $where_clause_str_invert_criterion;
    global $where_clause_str_invert_condition;
    global $where_clause_str_prefix_criterion;
    global $where_clause_str_prefix_condition;
    global $where_clause_str_postfix_criterion;
    global $where_clause_str_postfix_condition;

    $where_type = $t->process->type;
    $where_crt = null;

    if($where_type == "int") {
	$where_crt = $where_clause_int_type_criterion[0];
    } else if($where_type == "str") {
	$where_crt = $where_clause_str_type_criterion[0];
    }

    $where_crt_xpath = "//criterion[@id='$where_crt']";
    $where_crt_check = $xpath->query($where_crt_xpath);
    $where_check = $where_crt_check->length;

    if($where_check == 0) {
	// out xml slice doesnt't have such class
	return false;
    } else {
	$type = $t->process->type;
	$quote = $t->process->quote;
	$func = $t->process->func;
	$parc = $t->process->parc;
	$exist = $t->process->exist;
	$invert = $t->process->ltor;
	$pre = $t->process->pre;
	$post = $t->process->post;

	if(is_bool($quote)) {
	    $quote = conv_bool_to_str($quote);
	}
	$func = conv_bool_to_str($func);
	$parc = strval($parc);
	$exist = conv_bool_to_str($exist);
	$invert = conv_bool_to_str($invert);

	$where_type_crt = null;
	$where_quote_crt = null;
	$where_func_crt = null;
	$where_parc_crt = null;
	$where_exist_crt = null;
	$where_invert_crt = null;
	$where_prefix_crt = null;
	$where_postfix_crt = null;

	$where_type_cond = null;
	$where_quote_cond = null;
	$where_func_cond = null;
	$where_parc_cond = null;
	$where_exist_cond = null;
	$where_invert_cond = null;
	$where_prefix_cond = null;
	$where_postfix_cond = null;

	if($type == "int") {
	    $where_type_crt = $where_clause_int_type_criterion[0];
	    $where_quote_crt = $where_clause_int_quote_criterion[0];
	    $where_func_crt = $where_clause_int_func_criterion[0];
	    $where_parc_crt = $where_clause_int_parc_criterion[0];
	    $where_exist_crt = $where_clause_int_exist_criterion[0];
	    $where_invert_crt = $where_clause_int_invert_criterion[0];
	    $where_prefix_crt = $where_clause_int_prefix_criterion[0];
	    $where_postfix_crt = $where_clause_int_postfix_criterion[0];

	    $where_type_cond = $where_clause_int_type_condition[$type];
	    $where_quote_cond = $where_clause_int_quote_condition[$quote];
	    $where_func_cond = $where_clause_int_func_condition[$func];
	    $where_parc_cond = $where_clause_int_parc_condition[$parc];
	    $where_exist_cond = $where_clause_int_exist_condition[$exist];
	    $where_invert_cond = $where_clause_int_invert_condition[$invert];
	    $where_prefix_cond = $where_clause_int_prefix_condition[$pre];
	    $where_postfix_cond = $where_clause_int_postfix_condition[$post];
	} else {
	    $where_type_crt = $where_clause_str_type_criterion[0];
	    $where_quote_crt = $where_clause_str_quote_criterion[0];
	    $where_func_crt = $where_clause_str_func_criterion[0];
	    $where_parc_crt = $where_clause_str_parc_criterion[0];
	    $where_exist_crt = $where_clause_str_exist_criterion[0];
	    $where_invert_crt = $where_clause_str_invert_criterion[0];
	    $where_prefix_crt = $where_clause_str_prefix_criterion[0];
	    $where_postfix_crt = $where_clause_str_postfix_criterion[0];

	    $where_type_cond = $where_clause_str_type_condition[$type];
	    $where_quote_cond = $where_clause_str_quote_condition[$quote];
	    $where_func_cond = $where_clause_str_func_condition[$func];
	    $where_parc_cond = $where_clause_str_parc_condition[$parc];
	    $where_exist_cond = $where_clause_str_exist_condition[$exist];
	    $where_invert_cond = $where_clause_str_invert_condition[$invert];
	    $where_prefix_cond = $where_clause_str_prefix_condition[$pre];
	    $where_postfix_cond = $where_clause_str_postfix_condition[$post];
	}

	$where_type_xpath = "//criterion[@id='$where_type_crt']/condition[@value='$where_type_cond']";
	$where_quote_xpath = "//criterion[@id='$where_quote_crt']/condition[@value='$where_quote_cond']";
	$where_func_xpath = "//criterion[@id='$where_func_crt']/condition[@value='$where_func_cond']";
	$where_parc_xpath = "//criterion[@id='$where_parc_crt']/condition[@value='$where_parc_cond']";
	$where_exist_xpath = "//criterion[@id='$where_exist_crt']/condition[@value='$where_exist_cond']";
	$where_invert_xpath = "//criterion[@id='$where_invert_crt']/condition[@value='$where_invert_cond']";
	$where_prefix_xpath = "//criterion[@id='$where_prefix_crt']/condition[@value='$where_prefix_cond']";
	$where_postfix_xpath = "//criterion[@id='$where_postfix_crt']/condition[@value='$where_postfix_cond']";

	$where_type_check = $xpath->query($where_type_xpath);
	$where_quote_check = $xpath->query($where_quote_xpath);
	$where_func_check = $xpath->query($where_func_xpath);
	$where_parc_check = $xpath->query($where_parc_xpath);
	$where_exist_check = $xpath->query($where_exist_xpath);
	$where_invert_check = $xpath->query($where_invert_xpath);
	$where_prefix_check = $xpath->query($where_prefix_xpath);
	$where_postfix_check = $xpath->query($where_postfix_xpath);

	$type_check = $where_type_check->length;
	$quote_check = $where_quote_check->length;
	$func_check = $where_func_check->length;
	$parc_check = $where_parc_check->length;
	$exist_check = $where_exist_check->length;
	$invert_check = $where_invert_check->length;
	$prefix_check = $where_prefix_check->length;
	$postfix_check = $where_postfix_check->length;

	/*
	echo $where_type_xpath ."\n";
	echo $type_check ."\n";
	
	echo $where_quote_xpath ."\n";
	echo $quote_check ."\n";

	echo $where_func_xpath ."\n";
	echo $func_check ."\n";

	echo $where_parc_xpath ."\n";
	echo $parc_check ."\n";

	echo $where_exist_xpath ."\n";
	echo $exist_check ."\n";

	echo $where_invert_xpath ."\n";
	echo $invert_check ."\n";

	echo $where_prefix_xpath ."\n";
	echo $prefix_check ."\n";

	echo $where_postfix_xpath ."\n";
	echo $postfix_check ."\n";
	*/

	if($type_check > 1 || $quote_check > 1 
		|| $func_check > 1 || $parc_check > 1 
		|| $exist_check > 1 || $invert_check > 1 
		|| $prefix_check > 1 || $postfix_check > 1) {
	    die("wrong xml or mapping where_int function");
	} else if($type_check == 1 && $quote_check == 1
		&& $func_check == 1 && $parc_check == 1
		&& $exist_check == 1 && $invert_check == 1
		&& $prefix_check == 1 && $postfix_check == 1) {
	    return true;
	} else {
	    return false;
	}
    }

}



function map_order_by_num_process($t, $xml_slice) {
    global $order_by_num_clause_diff_criterion;
    global $order_by_num_clause_diff_condition;
    global $order_by_num_clause_view_criterion;
    global $order_by_num_clause_view_condition;

    $xpath = new DOMXPath($xml_slice);

    $num_crt = $order_by_num_clause_diff_criterion[0];
    $num_crt_xpath = "//criterion[@id='$num_crt']";
    $num_crt_check = $xpath->query($num_crt_xpath);
    $num_check = $num_crt_check->length;

    if($num_check == 0) {
	// out xml slice doesn't have such class
	return false;
    } else {
	$diff = conv_bool_to_str($t->process->diff);
	$view = $t->process->view;

	$num_diff_crt = $order_by_num_clause_diff_criterion[0];
	$num_view_crt = $order_by_num_clause_view_criterion[0];
	
	$num_diff_cond = $order_by_num_clause_diff_condition[$diff];
	$num_view_cond = $order_by_num_clause_view_condition[$view];

	$num_diff_xpath = "//criterion[@id='$num_diff_crt']/condition[@value='$num_diff_cond']";
	$num_view_xpath = "//criterion[@id='$num_view_crt']/condition[@value='$num_view_cond']";

	$num_diff_check = $xpath->query($num_diff_xpath);
	$num_view_check = $xpath->query($num_view_xpath);

	$num_diff_l = $num_diff_check->length;
	$num_view_l = $num_view_check->length;

	/*
	echo $num_diff_xpath ."\n";
	echo $num_diff_l ."\n";
	echo $num_view_xpath ."\n";
	echo $num_view_l ."\n";
	*/

	if($num_diff_l > 1 || $num_view_l > 1) {
	    die("wrong xml or mapping order_by_num function");
	} else if($num_diff_l == 1 && $num_view_l == 1) {
	    return true;
	} else {
	    return false;
	}
    }
}


function map_order_by_name_process($t, $xml_slice) {
    global $order_by_name_clause_diff_criterion;
    global $order_by_name_clause_diff_condition;
    global $order_by_name_clause_view_criterion;
    global $order_by_name_clause_view_condition;
    global $order_by_name_clause_quote_criterion;
    global $order_by_name_clause_quote_condition;

    $xpath = new DOMXPath($xml_slice);

    $name_crt = $order_by_name_clause_diff_criterion[0];
    $name_crt_xpath = "//criterion[@id='$name_crt']";
    $name_crt_check = $xpath->query($name_crt_xpath);
    $name_check = $name_crt_check->length;

    if($name_check > 1) {
	// out xml slice doesn't have such class
	return false;
    } else {
	$diff = conv_bool_to_str($t->process->diff);
	$view = $t->process->view;
	$quote = $t->process->quote;
	if(is_bool($quote)) {
	    $quote = conv_bool_to_str($quote);
	}

	$name_diff_crt = $order_by_name_clause_diff_criterion[0];
	$name_view_crt = $order_by_name_clause_view_criterion[0];
	$name_quote_crt = $order_by_name_clause_quote_criterion[0];

	$name_diff_cond = $order_by_name_clause_diff_condition[$diff];
	$name_view_cond = $order_by_name_clause_view_condition[$view];
	$name_quote_cond = $order_by_name_clause_quote_condition[$quote];

	$name_diff_xpath = "//criterion[@id='$name_diff_crt']/condition[@value='$name_diff_cond']";
	$name_view_xpath = "//criterion[@id='$name_view_crt']/condition[@value='$name_view_cond']";
	$name_quote_xpath = "//criterion[@id='$name_quote_crt']/condition[@value='$name_quote_cond']";

	$name_diff_check = $xpath->query($name_diff_xpath);
	$name_view_check = $xpath->query($name_view_xpath);
	$name_quote_check = $xpath->query($name_quote_xpath);

	$name_diff_l = $name_diff_check->length;
	$name_view_l = $name_view_check->length;
	$name_quote_l = $name_quote_check->length;

	/*
	echo $name_diff_xpath ."\n";
	echo $name_diff_l ."\n";
	echo $name_view_xpath ."\n";
	echo $name_view_l ."\n";
	echo $name_quote_xpath ."\n";
	echo $name_quote_l ."\n";
	*/

	if($name_diff_l > 1 || $name_view_l > 1 || $name_quote_l > 1) {
	    die("wrong xml or mapping order_by_name function");
	} else if($name_diff_l == 1 && $name_view_l == 1 && $name_quote_l == 1) {
	    return true;
	} else {
	    return false;
	}
    }
}


function map_order_by_expr_process($t, $xml_slice) {
    global $order_by_expr_clause_diff_criterion;
    global $order_by_expr_clause_diff_condition;
    global $order_by_expr_clause_view_criterion;
    global $order_by_expr_clause_view_condition;
    global $order_by_expr_clause_func_criterion;
    global $order_by_expr_clause_func_condition;
    global $order_by_expr_clause_parc_criterion;
    global $order_by_expr_clause_parc_condition;

    $xpath = new DOMXPath($xml_slice);

    $expr_crt = $order_by_expr_clause_diff_criterion[0];
    $expr_crt_xpath = "//criterion[@id='$expr_crt']";
    $expr_crt_check = $xpath->query($expr_crt);
    $expr_check = $expr_crt_check->length;

    if($expr_check == 0) {
	// our slice xml doesn't have such class
	return false;
    } else {
	$diff = conv_bool_to_str($t->process->diff);
	$view = $t->process->view;
	$func = conv_bool_to_str($t->process->func);
	$parc = strval($t->process->parc);

	$expr_diff_crt = $order_by_expr_clause_diff_criterion[0];
	$expr_view_crt = $order_by_expr_clause_view_criterion[0];
	$expr_func_crt = $order_by_expr_clause_func_criterion[0];
	$expr_parc_crt = $order_by_expr_clause_parc_criterion[0];

	$expr_diff_cond = $order_by_expr_clause_diff_condition[$diff];
	$expr_view_cond = $order_by_expr_clause_view_condition[$view];
	$expr_func_cond = $order_by_expr_clause_func_condition[$func];
	$expr_parc_cond = $order_by_expr_clause_parc_condition[$part];

	$expr_diff_xpath = "criterion[@id='$expr_diff_crt']/condition[@value='$expr_diff_cond']";
	$expr_view_xpath = "criterion[@id='$expr_view_crt']/condition[@value='$expr_view_cond']";
	$expr_func_xpath = "criterion[@id='$expr_func_crt']/condition[@value='$expr_func_cond']";
	$expr_parc_xpath = "criterion[@id='$expr_parc_crt']/condition[@value='$expr_parc_cond']";

	$expr_diff_check = $xpath->query($expr_diff_xpath);
	$expr_view_check = $xpath->query($expr_view_xpath);
	$expr_func_check = $xpath->query($expr_func_xpath);
	$expr_parc_check = $xpath->query($expr_parc_xpath);

	$expr_diff_l = $expr_diff_check->length;
	$expr_view_l = $expr_view_check->length;
	$expr_func_l = $expr_func_check->length;
	$expr_parc_l = $expr_parc_check->length;

	if($expr_diff_l > 1 || $expr_view_l > 1
		|| $expr_func_l > 1 || $expr_parc_l > 1) {
	    die("wrong xml or mapping order_by_expr function");
	} else if($expr_diff_l == 1 && $expr_view_l == 1
		&& $expr_func_l == 1 && $expr_parc_l == 1) {
	    return true;
	} else {
	    return false;
	}
    }
}


function map_order_by_way_process($t, $xml_slice) {
    global $order_by_way_clause_diff_criterion;
    global $order_by_way_clause_diff_condition;
    global $order_by_way_clause_view_criterion;
    global $order_by_way_clause_view_condition;

    $xpath = new DOMXPath($xml_slice);

    $way_crt = $order_by_way_clause_diff_criterion[0];
    $way_crt_xpath = "//criterion[@id='$way_crt']";
    $way_crt_check = $xpath->query($way_crt_xpath);
    $way_check = $way_crt_check->length;

    if($way_check == 0) {
	// out xml slice doesn't have such class
	return false;
    } else {
	$diff = conv_bool_to_str($t->process->diff);
	$view = $t->process->view;

	$way_diff_crt = $order_by_way_clause_diff_criterion[0];
	$way_view_crt = $order_by_way_clause_view_criterion[0];

	$way_diff_cond = $order_by_way_clause_diff_condition[$diff];
	$way_view_cond = $order_by_way_clause_view_condition[$view];

	$way_diff_xpath = "//criterion[@id='$way_diff_crt']/condition[@value='$way_diff_cond']";
	$way_view_xpath = "//criterion[@id='$way_view_crt']/condition[@value='$way_view_cond']";

	$way_diff_check = $xpath->query($way_diff_xpath);
	$way_view_check = $xpath->query($way_view_xpath);

	$way_diff_l = $way_diff_check->length;
	$way_view_l = $way_view_check->length;

	if($way_diff_l > 1 || $way_view_l > 1) {
	    die("wrong xml or mapping order_by_way function");
	} else if($way_diff_l == 1 && $way_view_l == 1) {
	    return true;
	} else {
	    return false;
	}
    }
}


function map_filter($t, $xml_slice) {
    global $filter_criterion;
    global $filter_criterion_condition;
    global $filter_criterion_delete_condition_arg;
    global $filter_critetion_escape_condition;

    $xpath = new DOMXPath($xml_slice);

    $filter_class = get_class($t->filter);
    $filter_crt = $filter_criterion[$filter_class];
    $filter_cond = null;

    if($filter_class == "StrDel") {
	$arg = $t->filter->arg;
	if(is_array($arg)) {
	    $arg = conv_arr_to_str($arg);
	}
	$filter_cond = $filter_criterion_delete_condition_arg[$arg];
    } else if($filter_class == "QuoteEscape1"
	    || $filter_class == "QuoteEscape2"
	    || $filter_class == "QuoteEscape3") {
	$filter_cond = $filter_critetion_escape_condition[$filter_class];
    } else {
	$filter_cond = $filter_criterion_condition[$filter_class];
    }

    $filter_xpath = "//criterion[@id='$filter_crt']/condition[@value='$filter_cond']";
    $filter_check = $xpath->query($filter_xpath);
    $check = $filter_check->length;

    if($check > 1) {
	die("wrong xml or mapping filter function");
    } else if($check == 1) {
	return true;
    } else {
	return false;
    }
}

function map_output($t, $xml_slice) {
    global $output_criterion;
    global $output_criterion_condition;

    $xpath = new DOMXPath($xml_slice);

    $output_class = get_class($t->output);
    $output_crt = $output_criterion[$output_class];
    $output_cond = $output_criterion_condition[$output_class];
    $output_xpath = "//criterion[@id='$output_crt']/condition[@value='$output_cond']";
    $output_check = $xpath->query($output_xpath);
    $check = $output_check->length;
    if($check > 1) {
	die("wrong xml or mapping output function");
    } else if($check == 1) {
	return true;
    } else {
	return false;
    }
}

function map_template($t, $xml_slice) {
    global $template_criterion;
    global $template_criterion_condition;
    global $template_criterion_condition_stable;

    $xpath = new DOMXPath($xml_slice);

    $template_class = get_class($t->template);
    $template_crt = $template_criterion[$template_class];
    $template_cond = null;
    if($template_class == "BigRandTemplate") {
	$stable = conv_bool_to_str($t->template->stable);
	$template_cond = $template_criterion_condition_stable[$stable];
    } else {
	$template_cond = $template_criterion_condition[$template_class];
    }

    $template_xpath = "//criterion[@id='$template_crt']/condition[@value='$template_cond']";
    $template_check = $xpath->query($template_xpath);
    $check = $template_check->length;

    if($check > 1) {
	die("wrong xml of mapping template function");
    } else if($check == 1) {
	return true;
    } else {
	return false;
    }
}

function conv_bool_to_str($b) {
    return $b ? "true" : "false";
}

function conv_arr_to_str($arr) {
    $r = array_shift($arr);
    while($part = array_shift($arr)) {
	$r .= ",". $part;
    }
    return $r;
}
