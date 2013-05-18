<?php

$config_criterion = array(
    "display_errors" => "config-display-errors-criterion",
    "no_sleep" => "config-connection-criterion"
);

$config_display_errors = array(
    "true" => "true",
    "false" => "false"
);


$config_connection = array(
    "true" => "true",
    "false" => "false"
);

/* -------------------------------- */

/*
$select_clause_criterion = array(
    "Field" => "select-clause-field-backticks-surrounding"
);
*/ //???
$select_clause_quote_criterion = array(
    "select-clause-field-backticks-surrounding"
);

$select_clause_quote_condition = array(
    "`" => "backticks",
    "false" => "no-backticks"
);

$select_clause_func_criterion = array(
    "select-clause-field-concat"
);

$select_clause_func_condition = array(
    "false" => "no-concat",
    "true" => "concat"
);

$select_clause_parc_criterion = array(
    "select-clause-field-parentheses-surrounding"
);

$select_clause_parc_condition = array(
    "0" => "0",
    "1" => "1",
    "2" => "2"
);

/* -------------------------------- */

/*
$where_clause_criterion = array(
    "Where" => array(
	"where-clause-parameter-type-int",
	"where-clause-parameter-type-str"
    )
);
*/

$where_clause_int_type_criterion = array(
    "where-clause-parameter-type-int"
);

$where_clause_int_type_condition = array(
    "int" => "int"
);

$where_clause_int_quote_criterion = array(
    "where-clause-field-quote-int"
);

$where_clause_int_quote_condition = array(
    "false" => "false",
    "'" => "single-quote",
    "\"" => "double-quote"
);

$where_clause_int_func_criterion = array(
    "where-clause-field-func-int"
);

$where_clause_int_func_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_int_parc_criterion = array(
    "where-clause-field-parentheses-surrounding-int"
);

$where_clause_int_parc_condition = array(
    "0" => "0",
    "1" => "1",
    "2" => "2"
);

$where_clause_int_exist_criterion = array(
    "where-clause-field-exist-int"
);

$where_clause_int_exist_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_int_invert_criterion = array(
    "where-clause-field-invert-parameter-int"
);

$where_clause_int_invert_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_int_prefix_criterion = array(
    "where-clause-field-prefix-int"
);

$where_clause_int_prefix_condition = array(
    "" => "empty",
    "0 and " => "zero-end",
    "1 or " => "one-or"
);

$where_clause_int_postfix_criterion = array(
    "where-clause-field-postfix-int"
);

$where_clause_int_postfix_condition = array(
    "" => "empty",
    " and 0" => "and-zero",
    " or 1" => "or-one"
);

/* -------------------------------------------- */

$where_clause_str_type_criterion = array(
    "where-clause-parameter-type-str"
);

$where_clause_str_type_condition = array(
    "str" => "str"
);

$where_clause_str_quote_criterion = array(
    "where-clause-field-quote-str"
);

$where_clause_str_quote_condition = array(
    "'" => "single-quote",
    "\"" => "double-quote"
);

$where_clause_str_func_criterion = array(
    "where-clause-field-func-str"
);

$where_clause_str_func_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_str_parc_criterion = array(
    "where-clause-field-parentheses-surrounding-str"
);

$where_clause_str_parc_condition = array(
    "0" => "0",
    "1" => "1",
    "2" => "2"
);

$where_clause_str_exist_criterion = array(
    "where-clause-field-exist-str"
);

$where_clause_str_exist_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_str_invert_criterion = array(
    "where-clause-field-invert-parameter-str"
);

$where_clause_str_invert_condition = array(
    "false" => "false",
    "true" => "true"
);

$where_clause_str_prefix_criterion = array(
    "where-clause-field-prefix-str"
);

$where_clause_str_prefix_condition = array(
    "" => "empty",
    "0 and " => "zero-end",
    "1 or" => "one-or"
);

$where_clause_str_postfix_criterion = array(
    "where-clause-field-postfix-str"
);

$where_clause_str_postfix_condition = array(
    "" => "empty",
    " and 0" => "and-zero",
    " or 1" => "or-one"
);

/* -------------------------------- */


$order_by_num_clause_diff_criterion = array(
    "order-by-clause-field-num-diff"
);

$order_by_num_clause_diff_condition = array(
    "false" => "false",
    "true" => "true"
);

$order_by_num_clause_view_criterion = array(
    "order-by-clause-field-num-view"
);

$order_by_num_clause_view_condition = array(
    "all" => "all",
    "one" => "one",
    "first" => "first"
);

/* -------------------------------- */

$order_by_name_clause_diff_criterion = array(
    "order-by-clause-field-name-diff"
);

$order_by_name_clause_diff_condition = array(
    "false" => "false",
    "true" => "true"
);

$order_by_name_clause_view_criterion = array(
    "order-by-clause-field-name-view"
);

$order_by_name_clause_view_condition = array(
    "all" => "all",
    "one" => "one",
    "first" => "first"
);

$order_by_name_clause_quote_criterion = array(
    "order-by-clause-field-name-quote"
);

$order_by_name_clause_quote_condition = array(
    "false" => "false",
    "`" => "backticks"
);

/* -------------------------------- */

$order_by_expr_clause_diff_criterion = array(
    "order-by-clause-field-expr-diff"
);

$order_by_expr_clause_diff_condition = array(
    "false" => "false",
    "true" => "true"
);

$order_by_expr_clause_view_criterion = array(
    "order-by-clause-field-expr-view"
);

$order_by_expr_clause_view_condition = array(
    "all" => "all",
    "one" => "one",
    "first" => "first"
);

$order_by_expr_clause_func_criterion = array(
    "order-by-clause-field-expr-func"
);

$order_by_expr_clause_func_condition = array(
    "false" => "false",
    "true" => "true"
);

$order_by_expr_clause_parc_criterion = array(
    "order-by-clause-field-expr-parentheses-surrounding"
);

$order_by_expr_clause_parc_condition = array(
    "0" => "0",
    "1" => "1",
    "2" => "2"
);

/* -------------------------------- */

$order_by_way_clause_diff_criterion = array(
    "order-by-clause-field-way-diff"
);

$order_by_way_clause_diff_condition = array(
    "false" => "false",
    "true" => "true"
);

$order_by_way_clause_view_criterion = array(
    "order-by-clause-field-way-view"
);

$order_by_way_clause_view_condition = array(
    "all" => "all",
    "one" => "one",
    "first" => "first"
);


/* -------------------------------- */

$filter_criterion = array(
    "ZeroFilter" => "data-filtering-zero",
    "IntFilter" => "data-filtering-int",
    "StrDel" => "data-filtering-delete-criterion", //???
    "RealEscape" => "data-filtering-real-escape",
    "QuoteEscape1" => "data-filtering-escape-criterion",
    "QuoteEscape2" => "data-filtering-escape-criterion",
    "QuoteEscape3" => "data-filtering-escape-criterion",
    "LengthCut" => "data-filtering-cut-15"
);

$filter_criterion_condition = array(
    "ZeroFilter" => "real",
    "IntFilter" => "real",
    "RealEscape" => "real",
    "LengthCut" => "real"
);

$filter_criterion_delete_condition_arg = array(
    " " => "blank",
    "'" => "single",
    "/*,*/" => "comment"   //???
);

$filter_critetion_escape_condition = array(
    "QuoteEscape1" => "single",
    "QuoteEscape2" => "double",
    "QuoteEscape3" => "single-and-double" 
);

/* -------------------------------- */

$output_criterion = array(
    "SimpleOutput" => "output-criterion",
    "TableOutput" => "output-criterion",
    "TableOutput2" => "output-criterion",
    "RowOutput" => "output-criterion",
    "RowOutput2" => "output-criterion",
    "SingleOutput" => "output-criterion",
    "SingleOutput2" => "output-criterion"
);

$output_criterion_condition = array(
    "SimpleOutput" => "simple",
    "TableOutput" => "table",
    "TableOutput2" => "table2",
    "RowOutput" => "row",
    "RowOutput2" => "row2",
    "SingleOutput" => "single",
    "SingleOutput2" => "single2"
);

/* -------------------------------- */


$template_criterion = array(
    "SimpleTemplate" => "part-of-http-response",
    "BigRandTemplate" => "part-of-http-response",
    "HeaderTemplate" => "part-of-http-response"
    //"WebspamTemplate" => ""
);

$template_criterion_condition = array(
    "SimpleTemplate" => "simple",
    "HeaderTemplate" => "header"
);

$template_criterion_condition_stable = array(
    "false" => "big-rand-false",
    "true" => "big-rand-true"
);







