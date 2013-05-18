<?php

$classeshelp = 'True - vulnerable, False - not vulnerable, T:xxx - vulnerable tests, F:xxx - not vulnerable tests';

$myclasses = array(
                   "Total" => function($t) { return true; },
                   "True" => function($t) { return $t->vuln(); },
                   "False" => function($t) { return !$t->vuln(); },
                   "T:WithErrors" => function($t) { return $t->vuln() && ($t->conf->display_errors || $t->output->eout()); },
                   "T:NoErrors" => function($t) { return $t->vuln() && (!$t->conf->display_errors && !$t->output->eout()); },
                   
                   "T:WithSleeps" => function($t) { return $t->vuln() && !$t->conf->no_sleep; },
                   "T:BlindOnly" => function ($t) { return $t->vuln() && !$t->conf->display_errors && !$t->output->eout() && $t->conf->no_sleep; },
                   "T:BlindOnlyRand" => function ($t) { return $t->vuln() && !$t->conf->display_errors && !$t->output->eout() && $t->conf->no_sleep && $t->template == "BigRandTemplate"; },
                   "F:BlindOnlyRand" => function ($t) { return !$t->vuln() && !$t->conf->display_errors && !$t->output->eout() && $t->conf->no_sleep && $t->template == "BigRandTemplate"; },
                   "T:BlindOnlyRandStable" => function ($t) { return $t->vuln() && !$t->conf->display_errors && !$t->output->eout() && $t->conf->no_sleep && $t->template == "BigRandTemplate" && $t->template->stable; },
                   "F:BlindOnlyRandStable" => function ($t) { return !$t->vuln() && !$t->conf->display_errors && !$t->output->eout() && $t->conf->no_sleep && $t->template == "BigRandTemplate" && $t->template->stable; },
                   "T:HeaderTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "HeaderTemplate"; },
                   );

if (0) {
$myclasses = 
    array(
          "Total" => function($t) { return true; },
          "True" => function($t) { return $t->vuln(); },
          "False" => function($t) { return !$t->vuln(); },
          "T:Silent" => function($t) { return $t->vuln() && $t->conf == "Silent"; },
          "T:Loud" => function($t) { return $t->vuln() && $t->conf == "Loud"; },
          "T:ZeroFilter" => function($t) { return $t->vuln() && $t->filter == "ZeroFilter"; },
          "T:StrDel(')" => function($t) { return $t->vuln() && $t->filter == "StrDel(')"; },
          "T:StrDel( )" => function($t) { return $t->vuln() && $t->filter == "StrDel( )"; },
          "T:StrDel({/*,*/})" => function($t) { return $t->vuln() && $t->filter == "StrDel({/*,*/})"; },
          "T:RealEscape" => function($t) { return $t->vuln() && $t->filter == "RealEscape"; },
          "T:QuoteEscape1" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape1"; },
          "T:QuoteEscape2" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape2"; },
          "T:QuoteEscape3" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape3"; },
          "T:LengthCut(15)" => function($t) { return $t->vuln() && $t->filter == "LengthCut(15)"; },
          "T:Where" => function($t) { return $t->vuln() && get_class($t->process) == "Where"; },
          "T:Field" => function($t) { return $t->vuln() && get_class($t->process) == "Field"; },
          "T:OrderByNum" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByNum"; },
          "T:OrderByExpr" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByExpr"; },
          "T:OrderByWay" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByWay"; },
          "T:SingleOutput" => function($t) { return $t->vuln() && get_class($t->output) == "SingleOutput"; },
          "T:RowOutput" => function($t) { return $t->vuln() && get_class($t->output) == "RowOutput"; },
          "T:TableOutput" => function($t) { return $t->vuln() && get_class($t->output) == "TableOutput"; },
          "T:SingleOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "SingleOutput2"; },
          "T:RowOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "RowOutput2"; },
          "T:TableOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "TableOutput2"; },
          "T:WithError" => function($t) { return $t->vuln() && $t->output->eout(); },
          "T:SimpleTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "SimpleTemplate"; },
          "T:DomRandTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "DomRandTemplate"; },
          "T:HeaderTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "HeaderTemplate"; },
          "F:SimpleTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "SimpleTemplate"; },
          "F:DomRandTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "DomRandTemplate"; },
          "F:HeaderTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "HeaderTemplate"; },

                    
          /* "True:S" => function($t) { return $t->vuln() && !$t->rand(); }, */
          /* "False:S" => function($t) { return !$t->vuln() && !$t->rand(); }, */
          /* "T:S:Silent" => function($t) { return $t->vuln() && $t->conf == "Silent" && !$t->rand(); }, */
          /* "T:S:Loud" => function($t) { return $t->vuln() && $t->conf == "Loud" && !$t->rand(); }, */
          /* "T:S:ZeroFilter" => function($t) { return $t->vuln() && $t->filter == "ZeroFilter" && !$t->rand(); }, */
          /* "T:S:StrDel(')" => function($t) { return $t->vuln() && $t->filter == "StrDel(')" && !$t->rand(); }, */
          /* "T:S:StrDel( )" => function($t) { return $t->vuln() && $t->filter == "StrDel( )" && !$t->rand(); }, */
          /* "T:S:StrDel({/\*,*\/})" => function($t) { return $t->vuln() && $t->filter == "StrDel({/\*,*\/})" && !$t->rand(); }, */
          /* "T:S:RealEscape" => function($t) { return $t->vuln() && $t->filter == "RealEscape" && !$t->rand(); }, */
          /* "T:S:QuoteEscape1" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape1" && !$t->rand(); }, */
          /* "T:S:QuoteEscape2" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape2" && !$t->rand(); }, */
          /* "T:S:QuoteEscape3" => function($t) { return $t->vuln() && $t->filter == "QuoteEscape3" && !$t->rand(); }, */
          /* "T:S:LengthCut(15)" => function($t) { return $t->vuln() && $t->filter == "LengthCut(15)" && !$t->rand(); }, */
          /* "T:S:Where" => function($t) { return $t->vuln() && get_class($t->process) == "Where" && !$t->rand(); }, */
          /* "T:S:Field" => function($t) { return $t->vuln() && get_class($t->process) == "Field" && !$t->rand(); }, */
          /* "T:S:OrderByNum" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByNum" && !$t->rand(); }, */
          /* "T:S:OrderByExpr" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByExpr" && !$t->rand(); }, */
          /* "T:S:OrderByWay" => function($t) { return $t->vuln() && get_class($t->process) == "OrderByWay" && !$t->rand(); }, */
          /* "T:S:SingleOutput" => function($t) { return $t->vuln() && get_class($t->output) == "SingleOutput" && !$t->rand(); }, */
          /* "T:S:RowOutput" => function($t) { return $t->vuln() && get_class($t->output) == "RowOutput" && !$t->rand(); }, */
          /* "T:S:TableOutput" => function($t) { return $t->vuln() && get_class($t->output) == "TableOutput" && !$t->rand(); }, */
          /* "T:S:SingleOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "SingleOutput2" && !$t->rand(); }, */
          /* "T:S:RowOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "RowOutput2" && !$t->rand(); }, */
          /* "T:S:TableOutput2" => function($t) { return $t->vuln() && get_class($t->output) == "TableOutput2" && !$t->rand(); }, */
          /* "T:S:WithError" => function($t) { return $t->vuln() && $t->output->eout() && !$t->rand(); }, */
          /* "T:S:SimpleTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "SimpleTemplate" && !$t->rand(); }, */
          /* "T:S:DomRandTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "DomRandTemplate" && !$t->rand(); }, */
          /* "T:S:HeaderTemplate" => function($t) { return $t->vuln() && get_class($t->template) == "HeaderTemplate" && !$t->rand(); }, */
          /* "F:S:SimpleTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "SimpleTemplate" && !$t->rand(); }, */
          /* "F:S:DomRandTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "DomRandTemplate" && !$t->rand(); }, */
          /* "F:S:HeaderTemplate" => function($t) { return !$t->vuln() && get_class($t->template) == "HeaderTemplate" && !$t->rand(); }, */
          
          
          

          /*
          "Total_my" => function($t) {return true; },
          "Vulnerable" => function($t) { return $t->is_vulnerable(); },
          "Not_vulnerable" => function($t) { return !$t->is_vulnerable(); },
          "ZeroFilter" => function($t) { return get_class($t->filter) === "ZeroFilter"; },
          "not ZeroFilter" => function($t) { return get_class($t->filter) !== "ZeroFilter"; },
          "Loud" => function($t) { return get_class($t->conf) === "Loud"; },
          "Silent" => function($t) { return get_class($t->conf) === "Silent"; },
          "spc-del" => function($t) { return $t->filter->name() === "StrDel( )"; },
          */
          );

}
$realclasses =
    array(
          "Total" => function($t) { return true; },
          "Total_real" => function($t) { return true; },
          );

?>