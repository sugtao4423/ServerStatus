<?php
require_once('./config.php');
$user = getUser();
$password = getPassword();
if(!isset($_SERVER['PHP_AUTH_USER'])){
	header('WWW-Authenticate: Basic realm="ServerStatusReceive Page."');
	header('HTTP/1.0 401 Unauthorized');
	die('<html lang="ja"><meta charset="utf-8">ログインが必要です。');
}else{
	if ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $password){
		header('WWW-Authenticate: Basic realm="ServerStatusReceive Page."');
		header('HTTP/1.0 401 Unauthorized');
		die('<html lang="ja"><meta charset="utf-8">ログインが必要です。');
	}
}

$json = json_decode($_POST[0], true);

$name = $json['name'];

$date = date('Y-m-d H:i');
$location = "database/" . $name . "/";

switch($name){
	case xeon:
	case git:
		sensors();
		memory();
		process();
		break;

	case micro:
	case raspi:
		memory();
		process();
		break;

	case serverRoom:
		serverRoomTemp();
		break;

	case fanLog:
		fanLog();
		break;
}

function sensors(){
	global $date, $location, $json;
	$core0 = $json["sensors"]["core0"];
	$core1 = $json["sensors"]["core1"];
	$core2 = $json["sensors"]["core2"];
	$core3 = $json["sensors"]["core3"];

	$db = new SQLite3($location . "sensors.db");
	$db->exec("create table if not exists sensors(date, core0, core1, core2, core3)");
	$db->exec("insert into sensors values('${date}', ${core0}, ${core1}, ${core2}, ${core3})");
	$db->close();
}

function memory(){
	global $date, $location, $json;
	$used = $json["memory"]["used"];
	$free = $json["memory"]["free"];
	$swap = $json["memory"]["swap"];

	$db = new SQLite3($location . "memory.db");
	$db->exec("create table if not exists memory(date, used, free, swap)");
	$db->exec("insert into memory values('${date}', ${used}, ${free}, ${swap})");
	$db->close();
}

function process(){
	global $date, $location, $json;
	$process = $json["process"]["process"];
	$zombie = $json["process"]["zombie"];

	$db = new SQLite3($location . "process.db");
	$db->exec("create table if not exists process(date, process, zombie)");
	$db->exec("insert into process values('${date}', ${process}, ${zombie})");
	$db->close();
}

function serverRoomTemp(){
	global $date, $location, $json;
	$temp = $json["temp"];

	$db = new SQLite3($location . "temp.db");
	$db->exec("create table if not exists temp(date, temp)");
	$db->exec("insert into temp values('${date}', '${temp}')");
}

function fanLog(){
	global $location, $json;
	$status = $json["status"];
	$date = $json["date"];
	$time = $json["time"];
	$temp = $json["temp"];

	$db = new SQLite3($location . "fanLog.db");
	$db->exec("create table if not exists fanLog(status, date, time, temp)");
	$db->exec("insert into fanLog values('${status}', ${date}', '${time}', '${temp}')");
}