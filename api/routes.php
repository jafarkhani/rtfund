<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 99.06
//-----------------------------

use Slim\Http\Request;
use Slim\Http\Response;
use Api\Controllers\AttendanceController;

$app->post('/AddAttendencaRecord', function(Request $request, Response $response, array $args){
	return AttendanceController::AddTrafficRecord($request, $response, $args);
	
});
    
