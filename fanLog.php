<?php
$dbLocation = dirname(__FILE__) . "/database/fanLog/fanLog.db";

if(isset($_GET['count']))
	$count = $_GET['count'];
else
	$count = 10;

$db = new SQLite3($dbLocation);
$query = $db->query("select * from fanLog limit (select count(*) from fanLog)-${count}, ${count}");
$arr = array();
while ($rows = $query->fetchArray(SQLITE3_ASSOC))
	array_push($arr, $rows);
echo json_encode($arr, JSON_UNESCAPED_UNICODE);
$db->close();