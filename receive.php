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
$db = new SQLite3(dirname(__FILE__) . "/database/" . $name . ".db");

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
		serverRoom();
		break;
}
$db->close();

function sensors(){
	global $db, $date, $location, $json;
	$core0 = $json["sensors"]["core0"];
	$core1 = $json["sensors"]["core1"];
	$core2 = $json["sensors"]["core2"];
	$core3 = $json["sensors"]["core3"];

	$db->exec("create table if not exists sensors(date, core0, core1, core2, core3)");
	$db->exec("insert into sensors values('${date}', ${core0}, ${core1}, ${core2}, ${core3})");
}

function memory(){
	global $db, $date, $location, $json;
	$used = $json["memory"]["used"];
	$free = $json["memory"]["free"];
	$swap = $json["memory"]["swap"];

	$db->exec("create table if not exists memory(date, used, free, swap)");
	$db->exec("insert into memory values('${date}', ${used}, ${free}, ${swap})");
}

function process(){
	global $db, $date, $location, $json;
	$process = $json["process"]["process"];
	$zombie = $json["process"]["zombie"];

	$db->exec("create table if not exists process(date, process, zombie)");
	$db->exec("insert into process values('${date}', ${process}, ${zombie})");
}

function serverRoom(){
	global $db, $date, $location, $json;
	$temp = $json["temp"];
	$hum = $json["hum"];
	$pres = $json["pres"];

	$db->exec("create table if not exists temp(date, temp)");
	$db->exec("create table if not exists hum(date, hum)");
	$db->exec("create table if not exists pres(date, pres)");
	$db->exec("insert into temp values('${date}', '${temp}')");
	$db->exec("insert into hum values('${date}', '${hum}')");
	$db->exec("insert into pres values('${date}', '${pres}')");
}