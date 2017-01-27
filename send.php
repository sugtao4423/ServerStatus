<?php
require_once(dirname(__FILE__) . '/config.php');
$user = getUser();
$password = getPassword();
$url = "https://${user}:${password}@sugtao4423.xyz/ServerStatus/receive.php";

$options = getopt('', array(
		'name:',
		'memory::',
		'process::',
		'no-memory-buffers-line::'
));

if(!$options or empty($options['name'])){
	echo 'Require name parameter.';
	die();
}

$name = $options['name'];
$jsonArr['name'] = $name;
switch($name){
	case serverRoom:
	case myRoom:
		$result = room();
		$jsonArr['temp'] = $result[0];
		$jsonArr['hum'] = $result[1];
		$jsonArr['pres'] = $result[2];
		break;

	case serverRoomPower:
		$jsonArr['status'] = serverRoomPower();
		break;

	default:
		if(array_key_exists('memory', $options))
			$jsonArr['memory'] = memory(array_key_exists('no-memory-buffers-line', $options));
		if(array_key_exists('process', $options))
			$jsonArr['process'] = process();
		break;
}

$json = json_encode($jsonArr);

$httpOpt = array('http' => array(
		'method' => 'POST',
		'content' => http_build_query(array($json)),
));
file_get_contents($url, false, stream_context_create($httpOpt));

function memory($no_memory_buffers_line){
	if($no_memory_buffers_line)
		$memory = command("free -m | awk 'NR==2 {print $3} NR==2 {print $7} NR==3 {print $3}'");
	else
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

function room(){
	$cmd = command("sudo python /home/tao/bme280_tao.py")[0];

	return split(",", $cmd);
}

function serverRoomPower(){
	do{
		sleep(3);
		exec("sudo python /home/tao/taptst10ctl/taptst10ctl.py", $out, $cmdResult);
	}while($cmdResult != 0);

	$result = array();

	foreach($out as $line){
		$arr = split(",", $line);
		$dateTime = $arr[1];
		$watt = $arr[2];
		$kWh = $arr[3];

		if(date('Y-m-d') === split(" ", $dateTime)[0])
			continue;
		array_push($result, array(
				"date" => $dateTime,
				"watt" => $watt,
				"kWh" => $kWh
		));
	}
	return $result;
}

function command($command){
	exec($command, $out);
	return $out;
}
