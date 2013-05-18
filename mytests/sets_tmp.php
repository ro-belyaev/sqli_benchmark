<?php

$confs = array("new UConf(true, false)", "new UConf(false, false)", "new UConf(true, true)", "new UConf(false, true)");
$filters = array(
                 "new ZeroFilter()", "new RealEscape()", "new QuoteEscape1()", "new QuoteEscape2()", "new QuoteEscape3()", "new LengthCut(15)", "new StrDel(\"'\")", "new StrDel(' ')", "new StrDel(array('/*', '*/'))",
                 "new IntFilter()");

$processes = array();
// Where
foreach (array('int', 'str') as $type) {
    foreach (array("false", '"\'"', "'\"'") as $quote) {
        foreach (array("false", "true") as $func) {
            for ($parc = 0; $parc <= 2; ++$parc) {
                foreach (array("true", "false") as $exist) {
                    foreach (array("false", "true") as $ltor) {
                        foreach (array('', '0 and ', '1 or ') as $pre) {
                            foreach (array('', ' and 0', ' or 1') as $post) {
                                if ($type === 'str' && $quote === 'false') continue;
                                $processes[] = "new Where('$type', $quote, $func, $parc, $exist, $ltor, '$pre', '$post')";
                            }
                        } 
                    }
                }
            }
        }
    }
}
//$processes = array();
// Field
/*foreach (array('false', '\'`\'') as $quote) {
    foreach (array('false', 'true') as $func) {
        for ($parc = 0; $parc <= 2; ++$parc) {
            $processes[] = "new Field($quote, $func, $parc, '', '')";
        }
    }
}*/

//$processes = array();
// OrderByNum
/*foreach (array('all', 'one', 'first') as $view) {
    foreach (array('true', 'false') as $diff) {
        $processes[] = "new OrderByNum($diff, '$view')";
    }
}*/

//$processes = array();
// OrderByName
/*foreach (array('all', 'one', 'first') as $view) {
    foreach (array('true', 'false') as $diff) {
        foreach (array('false', '\'`\'') as $quote) {
            $processes[] = "new OrderByName($diff, '$view', $quote)";
        }
    }
}*/

//$processes = array();
// OrderByExpr
/*foreach (array('all', 'one', 'first') as $view) {
    foreach (array('true', 'false') as $diff) {
        foreach (array("false", "true") as $func) {
            for ($parc = 0; $parc <= 2; ++$parc) {
                $processes[] = "new OrderByExpr($diff, '$view', $func, $parc, '', '')";
            }
        }
        
    }
}*/

//$processes = array();
// OrderByWay
/*foreach (array('all', 'one', 'first') as $view) {
    foreach (array('true', 'false') as $diff) {
        $processes[] = "new OrderByWay($diff, '$view')";
    }
}*/



$outputs = array("new SingleOutput()", "new RowOutput()", "new TableOutput()", "new SingleOutput2()", "new RowOutput2()", "new TableOutput2()");
$templates = array("new SimpleTemplate()", "new BigRandTemplate()", "new BigRandTemplate(true)", "new HeaderTemplate()", 
                   //"new WebspamTemplate()"
                   );

//$processes = array_slice($processes, 0, 1);
//$outputs = array_slice($outputs, 0, 1);

?>
