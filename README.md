# なにこれ
[ふらんのcsd](https://github.com/flum1025/csd)みたいなもの。（全然違うとか怒られそう）

send.phpを使ってupdate.phpにデータを送信してやり、update.phpでSQLite3DataBaseに格納って感じ。

send.phpに引数としてサーバー名を入れてあげて、送信するデータを限定。  
送信時にサーバー名も付与して送信。  
update.phpではサーバー名によって処理するデータを限定している。

とりあえず最終的にはSQLite3の形式になるからあとの表示とかは頑張って。
