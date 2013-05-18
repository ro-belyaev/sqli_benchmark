<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>history</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="my_own_style.css">
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="jquery.jstree.js"></script>
    <script type="text/javascript" src="bootstrap.js"></script>
    <script type="text/javascript" src="history2.js"></script>
    <style type="text/css">
        body {
            padding-top: 130px;
            padding-bottom: 50px;
            min-width: 780px;
        }
    </style>
</head>
<body>

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="brand" href="#"><h1>SQLi Benchmark</h1></a>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row-fluid">
        <div class="span3 well">
            <ul class="nav nav-list">
                <li><a href="#">История запусков</a></li>
                <li><a href="#">Как работать со средой</a></li>
                <li><a href="#">Обратная связь</a></li>
            </ul>
        </div>
        <div class="span8">
            <div>
                <h1>Сгенерированные тестовые наборы</h1>
                <div id="history" style="width: 65%;">
                    <div class="accordion" id="accordion" style="margin-top: 25px;">
                        <?php
                        require_once './constants.php';
                        require_once '../mytests/connection.php';
                        $query = "SELECT id, num_of_tests, generation_time FROM $table " .
                            "WHERE state='". STATE_PROGRESS ."' OR state='". STATE_COMPLETE ."'";
                        $result = mysql_query($query);
                        if(!$result) {
                            echo "Вы не сгенерировали ни одного тестового покрытия<br/>";
                        } else {
                            while($row = mysql_fetch_row($result)) {
                                $id = $row[0];
                                $num_of_tests = $row[1];
                                $time = $row[2];?>
                                <div class="accordion-group">
                                    <div class="accordion-heading">
                                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion"
                                           href="#collapse_<?php echo $id;?>">
                                            <?php echo $time;?>
                                        </a>
                                    </div>
                                    <div id="collapse_<?php echo $id;?>" class="accordion-body collapse">
                                        <div class="accordion-inner">
                                            <p>Сгенерированно <?php echo $num_of_tests;?> тестов</p>
                                            <div id="tests-actions_<?php echo $id;?>">
                                                <a id="show" href="../mytests/tests/<?php echo $id;?>/list.php">Просмотреть тестовый набор</a>
                                                <br/>
                                                <a id="tree" href="#">Дерево тестового набора</a>
                                                <br/>
                                                <a id="run" href="../mytester/launch.html?set=<?php echo $id;?>">Запустить сканеры</a>
                                                <br/>
                                                <a id="delete" href="#">Удалить тестовый набор</a>
                                            </div>
                                            <br/>
                                            <h4>Запуски сканеров</h4>
                                            <br/><?php
                                            $query_launches = "SELECT launch_id, state, start_time FROM $mytester_table ".
                                                "WHERE generation_id='$id'";
                                            $result_launches = mysql_query($query_launches, $connection);
                                            if(mysql_num_rows($result_launches) == 0) { ?>
                                                <p>Вы пока не запускали сканеры на этом тестовом наборе</p><?php
                                            } else {
                                                ?>
                                                <div class="tabbable">
                                                    <ul class="nav nav-tabs">
                                                        <?php
                                                        $launches = array();
                                                        while($row = mysql_fetch_row($result_launches)) {
                                                            $launch = array();
                                                            $launch['id'] = $row[0];
                                                            $launch['state'] = $row[1];
                                                            $launch['start_time'] = $row[2];
                                                            $launches[] = $launch;
                                                        }
                                                        for($j = 0; $j < count($launches); $j++) { ?>
                                                            <li <?php if($j == 0) { ?> class="active"<?php }?>>
                                                                <a href="#<?php
                                                                    echo $launches[$j]['id'];?>"
                                                                   data-toggle="tab"><?php
                                                                    echo $launches[$j]['start_time'];
                                                                    ?>
                                                                </a>
                                                            </li>
                                                            <?php
                                                        }
                                                        ?>
                                                    </ul>
                                                    <div class="tab-content">
                                                        <?php
                                                        for($j = 0; $j < count($launches); $j++) {
                                                            ?>
                                                            <div class="tab-pane<?php if($j == 0) {?> active<?php }?>"
                                                                id="<?php echo $launches[$j]['id'];?>">
                                                                <?php
                                                                $some_launch_id = $launches[$j]['id'];
                                                                $query_launch_info = "SELECT scanner_name,state,finish_time FROM $launch_table ".
                                                                    "WHERE launch_id='$some_launch_id'";
                                                                $result_launch_info = mysql_query($query_launch_info, $connection);?>
                                                                <ul>
                                                                    <?php
                                                                    while($row = mysql_fetch_row($result_launch_info)) {
                                                                        ?>
                                                                        <li><?php echo $row[0];?></li>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </ul>
<!--                                                                <p>I'm in Section --><?php //echo $launches[$j]['id'];?><!--.</p>-->
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div><?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal hide" id="modal">
            <div class="modal-header">
                <button class="close" data-dismiss="modal">x</button>
                <h3 id="header">Дерево тестового покрытия</h3>
            </div>
            <div class="modal-body">
                <div id="some-tree" style="margin-top: 10px;"></div>
            </div>
            <div class="modal-footer">
                <button id="close-tree" class="btn">Закрыть</button>
            </div>
        </div>

    </div>
</div>

</body>
</html>
