<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title></title>
    <link rel="stylesheet" type="text/css" href="new_bootstrap/bootstrap/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="my_own_style.css">
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="new_bootstrap/bootstrap/js/bootstrap.js"></script>
    <script type="text/javascript" src="jquery.jstree.js"></script>
    <script type="text/javascript" src="./run.js"></script>
    <style type="text/css">
        body {
            padding-top: 130px;
            padding-bottom: 50px;
            min-width: 780px;
        }
    </style>
    <style type="text/css">
        div#tests > ul{
            background: white;
            display: block;
        }
        div#scanners > ul{
            background: white;
            display: block;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="navbar navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container">
                <a class="brand" href="#"><h1>{{site_title}}</h1></a>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span3" style="position: fixed;">
            <div class="well">
                <ul class="nav nav-list">
                    <li>
                        <a href="#">{{navigation_history}}</a>
                    </li>
                    <li>
                        <a href="#">{{navigation_documentation}}</a>
                    </li>
                    <li>
                        <a href="#">{{navigation_feedback}}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div id="main" class="span7" style="margin-left: 30%;">
            <div id="content" style="margin-top: 40px; margin-bottom: 80px;">
                <div id="content-tests" style="margin-bottom: 15px;">
                    <h3>Сканеры работают</h3>
                    <br/>
                    <br/>
                    <dl class="dl-horizontal" id="scanners" style="">
                        <?php
                        require_once "../mytests/connection.php";
                        if(!isset($_GET['launch'])) {
                            die('You should specify launch id!');
                        }
                        $launch_id = mysql_real_escape_string($_GET['launch']);
                        $query = "select scanner_id from $launch_table where launch_id='$launch_id' order by scanner_id";
                        $result = mysql_query($query, $connection);
                        if(mysql_num_rows($result) == 0) {
                            die('There is no such launch!');
                        }
                        $xml = new DOMDocument();
                        $xml->load('./all_scanners.xml');
                        $xpath = new DOMXPath($xml);
                        for($i = 0; $i < mysql_num_rows($result); $i++) {
                            $row = mysql_fetch_row($result);
                            $scanner_id = $row[0];
                            $xpath_res = $xpath->query("//scanner[@id='$scanner_id']/name");
                            $scanner_name = $xpath_res->item(0)->nodeValue ."<br/>";
                            if($scanner_id != 0) {
                            ?>
                                <dt><?php echo $scanner_name;?></dt>
                                <dd id="<?php echo $scanner_id;?>" class="progress progress-striped active">
                                    <div class="bar" style="width: 0%;"></div>
                                </dd>
                                <?php
                            }
                        }
?>
                    </div>
                </div>



                <div style="margin-bottom: 49px;">
                    <button id="cancel" class="btn btn-danger">Отмена</button>
                    <button id="continue" class="btn btn-success">Продолжить</button>
                </div>
                <div id="post-footer" style="height: 1px;"></div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
