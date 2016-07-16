<?php
require_once(dirname(__FILE__) . '/config.php');
$user = getUser();
$password = getPassword();
$url = "https://${user}:${password}@sugtao4423.xyz/ServerStatus/receive.php";
$name = $argv[1];

switch($name){
	case xeon:
		$jsonArr = array(
				"name" => $name,
				"sensors" => sensors(3, 6),
				"memory" => memory(),
				"process" => process()
		);
		break;

	case git:
		$jsonArr = array(
				"name" => $name,
				"sensors" => sensors(3, 6),
				"memory" => memory(),
				"process" => process()
		);
		break;

	case micro:
	case raspi:
		$jsonArr = array(
				"name" => $name,
				"memory" => memory(),
				"process" => process()
		);
		break;

	case serverRoom:
		$result = serverRoom();
		$jsonArr = array(
				"name" => $name,
				"temp" => $result[0],
				"hum" => $result[1],
				"pres" => $result[2]
		);
		break;

	default:
		die();
}

$json = json_encode($jsonArr);

$options = array('http' => array(
		'method' => 'POST',
		'content' => http_build_query(array($json)),
));
file_get_contents($url, false, stream_context_create($options));

function sensors($start, $end){
	$sensors = command("sensors | awk 'NR==${start},NR==${end} {print $3}'");
	$core0 = preg_replace("/\.0|\+|°C/", "", $sensors[0]);
	$core1 = preg_replace("/\.0|\+|°C/", "", $sensors[1]);
	$core2 = preg_replace("/\.0|\+|°C/", "", $sensors[2]);
	$core3 = preg_replace("/\.0|\+|°C/", "", $sensors[3]);

	return array(
			"core0" => $core0,
			"core1" => $core1,
			"core2" => $core2,
			"core3" => $core3,
	);
}

function memory(){
	$memory = command("free -m | awk 'NR==3 {print $3} NR==3 {print $4} NR==4 {print $3}'");
	$used = $memory[0];
	$free = $memory[1];
	$swap = $memory[2];

	return array(
			"used" => $used,
			"free" => $free,
			"swap" => $swap
	);
}

function process(){
	$process = command("ps aux | wc -l")[0];
	$zombie = command("ps -ef | grep [d]efunct | wc -l")[0];

	return array(
			"process" => $process,
			"zombie" => $zombie
	);
}

function serverRoom(){
	$cmd = command("sudo python /home/tao/bme280_tao.py")[0];

	return split(",", $cmd);
}

function command($command){
	exec($command, $out);
	return $out;
}
