<?php

namespace Api\Controllers;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use ResponseHelper;
use DateModules;

class AttendanceController {
	
	static function AddTrafficRecord(Request $request, Response $response, array $args) {
		
		/*$params = $request->getQueryParams();
		$params = $request->getParsedBody();
		$file = $request->getUploadedFiles();*/

		$params = $request->getParsedBody();
		$PersonKey = isset($params["PersonKey"]) ? $params["PersonKey"] : "";
		$TrafficDateTime = isset($params["TrafficDateTime"]) ? $params["TrafficDateTime"] : "";
		
		print_r($params);
		
		//return ResponseHelper::createfailureResponseByException($response, \ExceptionHandler::GetExceptionsToString());

		//return ResponseHelper::createSuccessfulResponse($response, "---");
	}
}
