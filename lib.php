<?php
include(dirname(__FILE__) . '/servers.php');

$func = $_GET['f'];

switch($func){
case "getGraphBtns":
    getGraphBtns();
    break;

case "getGraph":
    getGraph();
    break;
}

function getGraphBtns(){
    echo '<br>';
    $hostname = $_GET['host'];
    $servers = Servers::$servers;
    for($i = 0; $i < count($servers); $i++){
        if($servers[$i]['host']['name'] === $hostname){
            $graphs = $servers[$i]['graphs'];
            break;
        }
    }
    for($i = 0; $i < count($graphs); $i++){
        echo "<a href=\"javascript:void(0)\" class=\"button graphbtn\" data-host=\"{$hostname}\" data-graphid=\"{$graphs[$i]['graphid']}\" data-graphname=\"{$graphs[$i]['name']}\" data-graphtype=\"{$graphs[$i]['graphtype']}\">{$graphs[$i]['name']}</a> ";
    }
}

function getGraph(){
    echo "<img src=\"chart.php?graphid={$_GET['gid']}&gtype={$_GET['gtype']}\">";
}
