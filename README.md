# ServerStatus
自作のServerStatusは廃止  
Zabbixでの運用に切り替えた

Zabbixのグラフを外部のページに貼り付けられるようにしたもの

## Refresh Servers
```
api.php?type=refreshServers
```
実行すると同じディレクトリに`zabbix.json`が作成される。

`session`はグラフの取得に必要なセッションIDであり、  
`hosts`はZabbixのAPIを使用するユーザーが取得できるすべての`ホスト名` `ホストID` `グラフ名` `グラフID` `グラフタイプ`の配列である。

#### JSON Sample
```
{
  "session": "hogehoge",
  "hosts": [
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
}
```

## Get JSON of hosts array
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
$ZBX_URL    = 'https://localhost.localdomain';
$ZBX_USER   = 'ZABBIX_USER';
$ZBX_PASSWD = 'ZABBIX_PASSWORD';
```