# ServerStatus

自作のServerStatusは廃止  
Zabbixでの運用に切り替えた

Zabbixのグラフを外部のページに貼り付けられるようにしたもの

## refreshServers.php
`refreshServers.php`を実行すると同じディレクトリに`servers.php`が作成され、ZabbixのAPIを使用するユーザーが取得できるすべての`ホスト名` `ホストID` `グラフ名` `グラフID` `グラフタイプ`が保存される

Sample
```
class Servers {
public static $servers =
array(
    0 =>
    array (
        'host' => array (
            'hostid' => '10198',
            'name' => 'myroom-raspi',
        ),
        'graphs' => array (
            0 =>
            array (
                'graphid' => '810',
                'graphtype' => '0',
                'name' => 'CPU jumps',
            ),
            1 =>
            array (
                'graphid' => '811',
                'graphtype' => '0',
                'name' => 'CPU load',
            ),
            2 =>
            array (
                'graphid' => '812',
                'graphtype' => '1',
                'name' => 'CPU utilization',
            ), .....
        )
    )
);
}
```
これをどうにかすればいろいろ取得できるでしょ

## config.php
Sample
```
<?php
class Config {
    public static $URL = 'http://localhost.localdomain';
    public static $AUTH = 'ZABBIX_AUTH';
    public static $SESSION_ID = 'ZABBIX_SESSIONID';
}

```
どこかにユーザー認証する処理を書けば良かったかも
