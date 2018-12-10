<?php
declare(strict_types=1);
/***** CONFIG *****/
$ZBX_URL = 'http://localhost.localdomain';
$ZBX_AUTH = 'ZABBIX_AUTH';
$ZBX_SID = 'ZABBIX_SESSIONID';
/******************/

$serverListPath = dirname(__FILE__) . '/servers.json';

switch($_GET['type']){
    default:
        $response = getServers();
        break;
    case 'refreshServers':
        $response = refreshServers();
        break;
    case 'chart':
        $response = getChart();
        break;
}
echo $response;

function getServers(): string{
    global $serverListPath;
    return file_get_contents($serverListPath);
}

function refreshServers(): string{
    function getZabbix(array $data): string{
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json-rpc',
                'content' => json_encode($data)
            ]
        ]);
        global $ZBX_URL;
        return file_get_contents("${ZBX_URL}/api_jsonrpc.php", false, $context);
    }

    function sortAsName(array $arr): array{
        $sort = [];
        foreach($arr as $d){
            $sort[] = $d['name'];
        }
        array_multisort($sort, SORT_ASC, SORT_NATURAL, $arr);
        return $arr;
    }

    function getHosts(): array{
        global $ZBX_AUTH;
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'host.get',
            'params' => [
                'output' => [
                    'hostid',
                    'name'
                ]
            ],
            'auth' => $ZBX_AUTH,
            'id' => 114514
        ];
        $zbx = getZabbix($data);
        $json = json_decode($zbx, true)['result'];
        $result = [];
        foreach($json as $j){
            $result[] = [
                'hostid' => (int)$j['hostid'],
                'name' => $j['name']
            ];
        }
        return sortAsName($result);
    }

    function getGraphs(int $hostId): array{
        global $ZBX_AUTH;
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'graph.get',
            'params' => [
                'output' => [
                    'graphid',
                    'graphtype',
                    'name'
                ],
                'hostids' => $hostId
            ],
            'auth' => $ZBX_AUTH,
            'id' => 810
        ];
        $zbx = getZabbix($data);
        $graphs = json_decode($zbx, true)['result'];
        $result = [];
        foreach($graphs as $g){
            if(preg_match('/^Network traffic on.*/', $g['name']) === 1 AND
                preg_match('/^Network traffic on (eth|ens).*/', $g['name']) === 0){
                continue;
            }
            $result[] = [
                'graphid' => (int)$g['graphid'],
                'graphtype' => (int)$g['graphtype'],
                'name' => $g['name']
            ];
        }
        return sortAsName($result);
    }

    $result = [];
    $hosts = getHosts();
    foreach($hosts as $h){
        $graphs = getGraphs($h['hostid']);
        $h['graphs'] = $graphs;
        $result[] = $h;
    }
    $json = json_encode($result, JSON_UNESCAPED_UNICODE);

    global $serverListPath;
    $size = file_put_contents($serverListPath, $json, LOCK_EX);
    return "finish.<br>${size} bytes.";
}

function getChart(): string{
    $graphType = $_GET['graphtype'];
    $graphId = $_GET['graphid'];
    if(!is_numeric($graphType) OR !is_numeric($graphId)){
        http_response_code(400);
        return 'Set graphtype and graphid as int';
    }
    $graphType = (int)$graphType;
    $graphId = (int)$graphId;

    if($graphType < 0 OR $graphType > 2){
        http_response_code(400);
        return 'Does not correspond graphtype';
    }

    global $ZBX_URL;
    switch($graphType){
        case 0:
        case 1:
            $chartUrl = "${ZBX_URL}/chart2.php?graphid=${graphId}&profileIdx=web.graphs.filter&from=now-2d&to=now";
            break;

        case 2:
            $chartUrl = "${ZBX_URL}/chart6.php?graphid=${graphId}&profileIdx=web.graphs.filter&from=now-1h&to=now";
            break;
    }

    global $ZBX_SID;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: zbx_sessionid=${ZBX_SID}"
        ]
    ]);
    header('Content-type: image/png');
    return file_get_contents($chartUrl, false, $context);
}
