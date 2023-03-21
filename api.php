<?php

declare(strict_types=1);

/***** CONFIG *****/
$ZBX_URL    = 'https://localhost.localdomain';
$ZBX_USER   = 'ZABBIX_USER';
$ZBX_PASSWD = 'ZABBIX_PASSWORD';
/******************/

define('ZBX_JSON_PATH', __DIR__ . '/zabbix.json');

switch ($_GET['type']) {
    default:
        $response = getServers();
        break;
    case 'refreshServers':
        $response = (new RefreshServers($ZBX_URL, $ZBX_USER, $ZBX_PASSWD))->refresh();
        break;
    case 'chart':
        $response = getChart($ZBX_URL, $_GET['graphtype'], $_GET['graphid']);
        header('Content-type: image/png');
        break;
}
echo $response;


function getZbxConfig(): array
{
    $json = file_get_contents(ZBX_JSON_PATH);
    return json_decode($json, true);
}

function getServers(): string
{
    $config = getZbxConfig();
    return json_encode($config['hosts'], JSON_UNESCAPED_UNICODE);
}

function getChart(string $zabbixUrl, string $graphType, string $graphId): string
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

    $chartPath = $graphType === 2 ? 'chart6.php' : 'chart2.php';
    $baseUrl = $zabbixUrl . '/' . $chartPath;
    $params = [
        'graphid' => $graphId,
        'profileIdx' => 'web.graphs.filter',
        'from' => 'now-2d',
        'to' => 'now',
    ];
    $url = $baseUrl . '?' . http_build_query($params);

    $session = getZbxConfig()['session'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'zbx_session=' . $session);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

class RefreshServers
{
    private $zabbixUrl;
    private $zabbixUser;
    private $zabbixPass;

    function __construct($zabbixUrl, string $zabbixUser, string $zabbixPass)
    {
        $this->zabbixUrl = $zabbixUrl;
        $this->zabbixUser = $zabbixUser;
        $this->zabbixPass = $zabbixPass;
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
        $size = file_put_contents(ZBX_JSON_PATH, $json, LOCK_EX);
        return "finish.<br>${size} bytes.";
    }

    private function getSession(): string
    {
        $baseUrl = $this->zabbixUrl . '/index.php';
        $params = [
            'name' => $this->zabbixUser,
            'password' => $this->zabbixPass,
            'enter' => 'Sign in',
        ];
        $url = $baseUrl . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $headers = curl_exec($ch);
        curl_close($ch);

        preg_match('/Set-Cookie:.+?zbx_session=(.+?);/i', $headers, $m);
        return $m[1];
    }

    private function sendZabbixApi(string $method, array $params, ?string $auth): array
    {
        $url = $this->zabbixUrl . '/api_jsonrpc.php';
        $headers = [
            'Content-Type: application/json-rpc',
        ];
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'auth' => $auth,
            'id' => 114514
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getZabbixAuth(): string
    {
        $method = 'user.login';
        $params = [
            'username' => $this->zabbixUser,
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
        $graphs = array_filter($json['result'], function ($graph) {
            return preg_match('/^Network traffic on.*/', $graph['name']) !== 1 ||
                preg_match('/^Network traffic on (eth|ens).*/', $graph['name']) !== 0;
        });

        $result = [];
        foreach ($graphs as $graph) {
            $result[] = [
                'graphid' => (int)$graph['graphid'],
                'graphtype' => (int)$graph['graphtype'],
                'name' => $graph['name']
            ];
        }
        return $result;
    }
}
