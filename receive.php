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

	case serverRoomPower:
		serverRoomPower();
		break;
}
$db->close();

function sensors(){
	global $db, $date, $json;
	$core0 = $json["sensors"]["core0"];
	$core1 = $json["sensors"]["core1"];
	$core2 = $json["sensors"]["core2"];
	$core3 = $json["sensors"]["core3"];

	$db->exec("create table if not exists sensors(date, core0, core1, core2, core3)");
	$db->exec("insert into sensors values('${date}', ${core0}, ${core1}, ${core2}, ${core3})");
}

function memory(){
	global $db, $date, $json;
	$used = $json["memory"]["used"];
	$free = $json["memory"]["free"];
	$swap = $json["memory"]["swap"];

	$db->exec("create table if not exists memory(date, used, free, swap)");
	$db->exec("insert into memory values('${date}', ${used}, ${free}, ${swap})");
}

function process(){
	global $db, $date, $json;
	$process = $json["process"]["process"];
	$zombie = $json["process"]["zombie"];

	$db->exec("create table if not exists process(date, process, zombie)");
	$db->exec("insert into process values('${date}', ${process}, ${zombie})");
}

function serverRoom(){
	global $db, $date, $json;
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

function serverRoomPower(){
	global $db, $json;

	$db->exec("create table if not exists power(date TEXT, watt REAL, kWh REAL)");
	$db->exec("create table if not exists powerOfDay(date TEXT, wattAvg REAL, kWh REAL)");

	foreach($json["status"] as $statusArr){
		$dateTime = $statusArr["date"];
		$watt = $statusArr["watt"];
		$kWh = $statusArr["kWh"];

		$db->exec("insert into power values('${dateTime}', ${watt}, ${kWh})");
	}

	$powerExistsDay = array();
	$powerPerDayExistsDay = array();

	$query = $db->query("select distinct substr(date, 1, 10) from power");
	while($rows = $query->fetchArray())
		array_push($powerExistsDay, $rows[0]);

	$query = $db->query("select date from powerOfDay");
	while($rows = $query->fetchArray())
		array_push($powerPerDayExistsDay, $rows[0]);

	for($i = 0; $i < count($powerExistsDay); $i++){
		$day = $powerExistsDay[$i];
		if(!in_array($day, $powerPerDayExistsDay)){
			$avg_watt = $db->querySingle("select round(avg(watt), 2) from power where date like '${day}%'");
			$day_kWh = $db->querySingle("select kWh-(select kWh from power where date like '${day}%' limit 1) 
					from power where date like '${day}%' limit 
					(select count(*) from power where date like '${day}%')-1, 1");
			$db->exec("insert into powerOfDay values('${day}', ${avg_watt}, ${day_kWh})");
		}
	}
}