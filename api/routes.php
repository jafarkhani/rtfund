<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 99.06
//-----------------------------

require_once __DIR__ . '/../framework/configurations.inc.php';
require_once __DIR__ . '/../generalClasses/PDODataAccess.class.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Api\Controllers\AttendanceController;

$app->post('/AddAttendencaRecord', function(Request $request, Response $response, array $args){
	return AttendanceController::AddTrafficRecord($request, $response, $args);
	
});
    
