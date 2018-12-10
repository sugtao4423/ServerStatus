# ServerStatus
自作のServerStatusは廃止  
Zabbixでの運用に切り替えた

Zabbixのグラフを外部のページに貼り付けられるようにしたもの

## Refresh Servers
```
api.php?type=refreshServers
```
実行すると同じディレクトリに`servers.json`が作成され、ZabbixのAPIを使用するユーザーが取得できるすべての`ホスト名` `ホストID` `グラフ名` `グラフID` `グラフタイプ`が保存される

#### JSON Sample
```
[
  {
    "hostid": 10198,
    "name": "MyroomRaspi",
    "graphs": [
      {
        "graphid": 825,
        "graphtype": 0,
        "name": "CPU jumps"
      },
      {
        "graphid": 826,
        "graphtype": 0,
        "name": "CPU load"
      }, ........
    ]
  }, ........
]
```

## Get JSON
```
api.php
```

## Graph Image
```
api.php?type=chart&graphtype=${GraphType}&graphid=${GraphId}
```

## Config
api.php
```
$ZBX_URL  = 'http://localhost.localdomain';
$ZBX_AUTH = 'ZABBIX_AUTH';
$ZBX_SID  = 'ZABBIX_SESSIONID';
```