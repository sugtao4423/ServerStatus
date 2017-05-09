<?php
include(dirname(__FILE__) . '/config.php');
$sId = Config::$SESSION_ID;

if(isset($_GET['graphid']))
    $graphid = $_GET['graphid'];
else
    return;

if(isset($_GET['gtype']))
    $gtype = $_GET['gtype'];
else
    $gtype = 0;

switch($gtype){
case 0:
case 1:
    $requestUrl = Config::$URL . "/chart2.php?graphid=${graphid}&period=172800";
    break;

case 2:
    $requestUrl = Config::$URL . "/chart6.php?graphid=${graphid}&period=3600";
    break;
}

$options = array(
    'http' => array(
        'method' => 'GET',
        'header' => "Cookie: zbx_sessionid=${sId}",
    )
);

header('Content-type: image/png');
$contents = file_get_contents($requestUrl, false, stream_context_create($options));
echo $contents;