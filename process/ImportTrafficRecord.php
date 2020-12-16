<?php
ini_set("display_errors","On");
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;

/*
$url = "https://applybyapi.com/gentoken/";
$client = new Client();
$options = [
	'verify' => false,
    'json' => [
        "posting" => 7
       ]
   ]; 
$response = $client->post($url, $options);

$arr = json_decode($response->getBody());
print_r($arr);
die();

$url = "https://applybyapi.com/apply/";
$data = array(
	"token" => "L38WMTW6SY65KLU83HP7",
	"name"  => "Shabnam Jafarkhani",
	"email"  => "jafarkhani.shabnam@gmail.com",
	"resume"  => fopen('ShabnamCV.pdf', 'r'),
	"phone"  => "+989155089018"
);
$response = new HttpResponse();
$response->CallService(HttpResponse::METHOD_POST, $url, $data);
die();*/

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