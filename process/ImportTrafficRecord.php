<?php
ini_set("display_errors","On");
require_once '../vendor/autoload.php';

$PersonKey = isset($_GET["PersonKey"]) ? $_GET["PersonKey"] : "";
$TrafficDate = isset($_GET["TrafficDate"]) ? $_GET["TrafficDate"] : "";
$TrafficTime = isset($_GET["TrafficTime"]) ? $_GET["TrafficTime"] : "";

if($PersonKey == "")        die("Missing PersonKey paramater");
if($TrafficDate == "")      die("Missing TrafficDate paramater");
if($TrafficTime == "")      die("Missing TrafficTime paramater");

$response = new HttpResponse();
$response->CallService(HttpResponse::METHOD_POST, 
	"http://saja.krrtf.ir/api/AddAttendenceRecord",
	array(
		"PersonKey" => $PersonKey,
		"TrafficDate" => $TrafficDate,
		"TrafficTime" => $TrafficTime
	));
if($response->isOk())
    echo "success";
else
    echo "failure : " . $response->getMessage();