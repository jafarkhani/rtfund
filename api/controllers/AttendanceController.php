<?php

namespace Api\Controllers;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use ResponseHelper;
use DateModules;

require_once __DIR__ . '/../../attendance/traffic/traffic.class.php';

class AttendanceController {
	
	static function AddTrafficRecord(Request $request, Response $response, array $args) {
		
		/*$params = $request->getQueryParams();
		$params = $request->getParsedBody();
		$file = $request->getUploadedFiles();*/

		$params = $request->getParsedBody();
		
		$PersonKey = isset($params["PersonKey"]) ? $params["PersonKey"] : "";
		$TrafficDate = isset($params["TrafficDate"]) ? $params["TrafficDate"] : "";
		$TrafficTime = isset($params["TrafficTime"]) ? $params["TrafficTime"] : "";
		
		if($PersonKey == "" || $TrafficDate == "" || $TrafficTime == "")
		{
			return ResponseHelper::createfailureResponseByException($response, "پارامترهای ورودی ناقص می باشد");
		}
		
		$dt = PdoDataAccess::runquery("select PersonID from BSC_persons where AttCode=?", array($PersonKey));
		if(count($dt) == 0)
		{
			$errors = "کد عضو " . $PersonKey . " در کارکنان یافت نشد." . "<br>";
			return ResponseHelper::createfailureResponseByException($response, $errors);
		}
		
		$dt = PdoDataAccess::runquery("select * from ATN_traffic where PersonID=? 
			AND TrafficDate=? AND TrafficTime=?", array($dt[0][0], $TrafficDate , $TrafficTime));
		if(count($dt) > 0)
		{
			$errors = " با تاریخ و ساعت فوق قبلا تردد در سیستم ثبت شده است";
			return ResponseHelper::createfailureResponseByException($response, $errors);
		}
		
		$obj = new ATN_traffic();
		$obj->PersonID = $dt[0][0];
		$obj->TrafficDate = $TrafficDate;
		$obj->TrafficTime = $TrafficTime;
		$obj->IsSystemic = 'YES'; 
		$obj->IsActive = "YES";
		$result = $obj->Add();
		
		if($result)
			return ResponseHelper::createSuccessfulResponse($response);
		else
			return ResponseHelper::createfailureResponseByException($response, "خطا در ثبت تردد");

	}
}
