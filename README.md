# なにこれ
[ふらんのcsd](https://github.com/flum1025/csd)みたいなもの。（全然違うとか怒られそう）

send.phpを使ってupdate.phpにデータを送信してやり、update.phpでSQLite3DataBaseに格納って感じ。

send.phpに引数としてサーバー名と送信するデータの種類を渡す。  
update.phpでは受け取ったデータをSQLite3に格納する。

## free -mをしてみて
| | total | used | free | shared | buffers | cached
--- | --- | --- | --- | --- | --- | ---
Mem: | 12016 | 3321 | 8695 | 349 | 104 | 1206
-/+ buffers/cache: | | 2010 | 10006
Swap: | 7 | 0 | 7

| | total | used | free | shared | buff/cache | available
--- | --- | --- | --- | --- | --- | ---
Mem: | 3934 | 606 | 2220 | 30 | 1106 | 3237
Swap: | 4093 | 0 | 4093

このどちらかがでてくるはず。

もし下のような`-/+ buffers/cache:`行がない結果なら  
`--no-memory-buffers-line`オプションをつける
## Sample
* サーバー名: xeon
* 送信するデータ: メモリ、プロセス
* ただし上記の表の**上**の結果

`php send.php --name xeon --memory --process`

* サーバー名: git
* 送信するデータ: メモリ、プロセス
* ただし上記の表の**下**の結果

`php send.php --name git --memory --process --no-memory-buffers-line`
