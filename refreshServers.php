<?php
include(dirname(__FILE__) . '/config.php');

$result = getHostsGraphs(true);

$serverListFile = dirname(__FILE__) . '/servers.php';
$listString = var_export($result, true);
$listString = "<?php\nclass Servers{\npublic static \$servers = ${listString};\n}";
$status = file_put_contents($serverListFile, $listString, LOCK_EX);

echo "finish.<br>Code: ${status}";

function getHostsGraphs($isSort){
    // arr(hostArray, graphArray)
    $hostGraphs = array();
    $hostList = getHostList($isSort);
    for($i = 0; $i < count($hostList); $i++){
        $gList = getGraphList($hostList[$i], $isSort);
        array_push($hostGraphs, array(
            "host" => $hostList[$i],
            "graphs" => $gList
        ));
    }
    return $hostGraphs;
}

function getHostList($isSort){
    // 0: hostid, 1: name
    $data = array(
        "jsonrpc" => "2.0",
        "method" => "host.get",
        "params" => array(
            "output" => array(
                "hostid",
                "name"
            )
        ),
        "auth" => Config::$AUTH,
        "id" => 114514
    );
    $result = url_get_contents($data);
    $json = json_decode($result, true);
    $hList = array();
    for($i = 0; $i < count($json["result"]); $i++){
        $hList[$i] = $json["result"][$i];
    }
    if($isSort)
        return sortAsValue($hList, "name");
    else
        return $hList;
}

function getGraphList($hostId, $isSort){
    $data = array(
        "jsonrpc" => "2.0",
        "method" => "graph.get",
        "params" => array(
            "output" => array(
                "graphid",
                "graphtype",
                "name"
            ),
            "hostids" => $hostId
        ),
        "auth" => Config::$AUTH,
        "id" => 810
    );
    $result = url_get_contents($data);
    $json = json_decode($result, true);
    $gList = array();
    for($i = 0; $i < count($json["result"]); $i++){
        $gList[$i] = $json["result"][$i];
    }
    if($isSort)
        return sortAsValue($gList, "name");
    else
        return $gList;
}

function url_get_contents($jsonData){
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => 'Content-Type: application/json-rpc',
            'content' => json_encode($jsonData)
        )
    ));
    return file_get_contents(Config::$URL . '/api_jsonrpc.php', false, $context);
}

function sortAsValue($data, $keyname){
    foreach($data as $key => $value){
        $keydata[$key] = strtolower($value[$keyname]);
    }
    array_multisort($keydata, SORT_ASC, $data);
    return $data;
}
