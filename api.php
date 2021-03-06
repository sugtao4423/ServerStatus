<?php

declare(strict_types=1);

/***** CONFIG *****/
$ZBX_URL    = 'https://localhost.localdomain';
$ZBX_USER   = 'ZABBIX_USER';
$ZBX_PASSWD = 'ZABBIX_PASSWORD';
/******************/

$jsonPath = __DIR__ . '/zabbix.json';

switch ($_GET['type']) {
    default:
        $response = getServers($jsonPath);
        break;
    case 'refreshServers':
        $response = (new RefreshServers($ZBX_URL, $ZBX_USER, $ZBX_PASSWD, $jsonPath))->refresh();
        break;
    case 'chart':
        $response = getChart($ZBX_URL, $jsonPath, $_GET['graphtype'], $_GET['graphid']);
        break;
}
echo $response;


function getServers(string $jsonPath): string
{
    $json = file_get_contents($jsonPath);
    $json = json_decode($json, true);
    return json_encode($json['hosts'], JSON_UNESCAPED_UNICODE);
}

function getChart(string $zabbixUrl, string $jsonPath, string $graphType, string $graphId): string
{
    if (!is_numeric($graphType) || !is_numeric($graphId)) {
        http_response_code(400);
        return 'Set graphtype and graphid as int';
    }
    $graphType = (int)$graphType;
    $graphId = (int)$graphId;

    if ($graphType < 0 || $graphType > 2) {
        http_response_code(400);
        return 'Does not supported graphtype';
    }

    if ($graphType == 2) {
        $chartPath = 'chart6.php';
    } else {
        $chartPath = 'chart2.php';
    }
    $chartUrl = "${zabbixUrl}/${chartPath}?graphid=${graphId}&profileIdx=web.graphs.filter&from=now-2d&to=now";

    $json = file_get_contents($jsonPath);
    $json = json_decode($json, true);
    $session = $json['session'];

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: zbx_session=${session}"
        ]
    ]);
    header('Content-type: image/png');
    return file_get_contents($chartUrl, false, $context);
}

class RefreshServers
{
    private $zabbixUrl;
    private $zabbixUser;
    private $zabbixPass;
    private $jsonPath;

    function __construct($zabbixUrl, string $zabbixUser, string $zabbixPass, string $jsonPath)
    {
        $this->zabbixUrl = $zabbixUrl;
        $this->zabbixUser = $zabbixUser;
        $this->zabbixPass = $zabbixPass;
        $this->jsonPath = $jsonPath;
    }

    function refresh(): string
    {
        $auth = $this->getZabbixAuth();
        $hosts = $this->getHosts($auth);
        foreach ($hosts as &$host) {
            $graphs = $this->getGraphs($auth, $host['hostid']);
            $host['graphs'] = $graphs;
        }

        $session = $this->getSession();
        $json = [
            'session' => $session,
            'hosts' => $hosts
        ];
        $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        $size = file_put_contents($this->jsonPath, $json, LOCK_EX);
        return "finish.<br>${size} bytes.";
    }

    private function getSession(): string
    {
        $loginUrl = "{$this->zabbixUrl}/index.php";
        $loginUrl .= "?name={$this->zabbixUser}&password={$this->zabbixPass}&enter=Sign%20in";
        file_get_contents($loginUrl);
        foreach ($http_response_header as $header) {
            if (preg_match('/^Set-Cookie:.+?zbx_session=(.+?);/', $header, $m) === 1) {
                return $m[1];
            }
        }
    }

    private function sendZabbixApi(string $method, array $params, ?string $auth): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json-rpc',
                'content' => json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method' => $method,
                        'params' => $params,
                        'auth' => $auth,
                        'id' => 114514
                    ]
                )
            ]
        ]);
        $json = file_get_contents("{$this->zabbixUrl}/api_jsonrpc.php", false, $context);
        return json_decode($json, true);
    }

    private function getZabbixAuth(): string
    {
        $method = 'user.login';
        $params = [
            'user' => $this->zabbixUser,
            'password' => $this->zabbixPass
        ];
        $json = $this->sendZabbixApi($method, $params, null);
        return $json['result'];
    }

    private function getHosts(string $auth): array
    {
        $method = 'host.get';
        $params = [
            'output' => [
                'hostid',
                'name'
            ],
            'sortfield' => 'name'
        ];
        $json = $this->sendZabbixApi($method, $params, $auth);
        $result = [];
        foreach ($json['result'] as $host) {
            $result[] = [
                'hostid' => (int)$host['hostid'],
                'name' => $host['name']
            ];
        }
        return $result;
    }

    private function getGraphs(string $auth, int $hostId): array
    {
        $method = 'graph.get';
        $params = [
            'output' => [
                'graphid',
                'graphtype',
                'name'
            ],
            'hostids' => $hostId,
            'sortfield' => 'name'
        ];
        $json = $this->sendZabbixApi($method, $params, $auth);
        $result = [];
        foreach ($json['result'] as $graph) {
            if (
                preg_match('/^Network traffic on.*/', $graph['name']) === 1 &&
                preg_match('/^Network traffic on (eth|ens).*/', $graph['name']) === 0
            ) {
                continue;
            }
            $result[] = [
                'graphid' => (int)$graph['graphid'],
                'graphtype' => (int)$graph['graphtype'],
                'name' => $graph['name']
            ];
        }
        return $result;
    }
}
