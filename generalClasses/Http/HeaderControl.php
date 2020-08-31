<?php

//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	98.07
//---------------------------

require "HeaderKey.php";
require "HTTPStatusCodes.php";

class HeaderControl {

	const INPUT_VALIDATION_FAILED_FOR_PERSON_ID = array("code" => "INPUT_VALIDATION_FAILED_FOR_PERSON_ID", "message" => "INPUT_VALIDATION_FAILED_FOR_PERSON_ID");
	const INPUT_VALIDATION_FAILED_FOR_USER_ID = array("code" => "INPUT_VALIDATION_FAILED_FOR_USER_ID", "message" => "INPUT_VALIDATION_FAILED_FOR_USER_ID");
	const INPUT_VALIDATION_FAILED_FOR_USER_ROLES = array("code" => "INPUT_VALIDATION_FAILED_FOR_USER_ROLES", "message" => "INPUT_VALIDATION_FAILED_FOR_USER_ROLES");
	const INPUT_VALIDATION_FAILED_FOR_SYS_KEY = array("code" => "INPUT_VALIDATION_FAILED_FOR_SYS_KEY", "message" => "INPUT_VALIDATION_FAILED_FOR_SYS_KEY");
	const INPUT_VALIDATION_FAILED_FOR_IP_ADDRESS = array("code" => "INPUT_VALIDATION_FAILED_FOR_IP_ADDRESS", "message" => "INPUT_VALIDATION_FAILED_FOR_IP_ADDRESS");
	
	const PERSON_ID_IS_MISSING = array("code" => "PERSON_ID_IS_MISSING_IN_HEADER", "message" => "PERSON_ID_IS_MISSING_IN_HEADER");
	const IP_ADDRESS_IS_MISSING = array("code" => "IP_ADDRESS_IS_MISSING_IN_HEADER", "message" => "IP_ADDRESS_IS_MISSING_IN_HEADER");
	const SYS_KEY_IS_MISSING = array("code" => "SYS_KEY_IS_MISSING_IN_HEADER", "message" => "SYS_KEY_IS_MISSING_IN_HEADER");
	const USER_ROLE_IS_MISSING = array("code" => "USER_ROLE_IS_MISSING_IN_HEADER", "message" => "USER_ROLE_IS_MISSING_IN_HEADER");
	const USER_ID_IS_MISSING = array("code" => "USER_ID_IS_MISSING_IN_HEADER", "message" => "USER_ID_IS_MISSING_IN_HEADER");
	const H_TOKEN_IS_MISSING = array("code" => "H_TOKEN_IS_MISSING_IN_HEADER", "message" => "H_TOKEN_IS_MISSING_IN_HEADER");
	const API_KEY_IS_MISSING = array("code" => "API_KEY_IS_MISSING_IN_HEADER", "message" => "API_KEY_IS_MISSING_IN_HEADER");
	
	const INVALID_QUERY_PARAMETER = array("code" => "INVALID_QUERY_PARAMETER", "message" => "INVALID_QUERY_PARAMETER");
	const INPUT_VALIDATION_FAILED = array("code" => "INPUT_VALIDATION_FAILED", "message" => "INPUT_VALIDATION_FAILED");
	const REQUEST_ID_IS_NOT_VALID = array("code" => "REQUEST_ID_IS_NOT_VALID", "message" => "REQUEST_ID_IS_NOT_VALID");
	const INVALID_TOKEN = array("code" => "INVALID_H_TOKEN", "message" => "INVALID_H_TOKEN");
	const CLASS_IS_FULL = array("code" => "CLASS_IS_FULL", "message" => "CLASS_IS_FULL");
	const INVALID_API_KEY = array("code" => "INVALID_API_KEY", "message" => "INVALID_API_KEY");
	const CLASS_IS_EXSIST_FOR_YOU = array("code" => "CLASS_IS_EXSIST_FOR_YOU", "message" => "CLASS_IS_EXSIST_FOR_YOU");
	const REQUEST_ID_IS_REQUIRED = array("code" => "REQUEST_ID_IS_REQUIRED", "message" => "REQUEST_ID_IS_REQUIRED");

	static function getHeaderInfo($container) {

		$request = $container->get('request');
		$headerKeys = HeaderKey::getConstants();
		$requestHeaders = $request->getHeaders();
		$headers = array();
		
		foreach ($headerKeys as $key => $value) {
			if (array_key_exists("HTTP_" . $key, $requestHeaders)) {
				$headers[str_replace('_', '-', $key)] = $requestHeaders["HTTP_" . $key][0];
			}
		}

		$GLOBALS["FUMHeaderInfo"] = $headers;
		return $headers;
	}

	static function AuthenticateHeaders($headers, $container) {

		if (empty($headers[HeaderKey::PERSON_ID])) {
			\ExceptionHandler::PushException(self::PERSON_ID_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			if (!InputValidation::validate($headers[HeaderKey::PERSON_ID], InputValidation::Pattern_Num, false)) {
				\ExceptionHandler::PushException(self::INPUT_VALIDATION_FAILED_FOR_PERSON_ID);
				return HTTPStatusCodes::BAD_REQUEST;
			}
		}
		//................................
		if (empty($headers[HeaderKey::USER_ID])) {
			\ExceptionHandler::PushException(self::USER_ID_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			if (!InputValidation::validate($headers[HeaderKey::USER_ID], InputValidation::Pattern_EnAlphaNum, false)) {
				\ExceptionHandler::PushException(self::INPUT_VALIDATION_FAILED_FOR_USER_ID);
				return HTTPStatusCodes::BAD_REQUEST;
			}
		}
		//................................
		if (empty($headers[HeaderKey::USER_ROLES])) {
			\ExceptionHandler::PushException(self::USER_ROLE_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			if (!InputValidation::validate($headers[HeaderKey::USER_ROLES], InputValidation::Pattern_Num, false)) {
				\ExceptionHandler::PushException(self::INPUT_VALIDATION_FAILED_FOR_USER_ROLES);
				return HTTPStatusCodes::BAD_REQUEST;
			}
		}
		//................................
		if (empty($headers[HeaderKey::SYS_KEY])) {
			\ExceptionHandler::PushException(self::SYS_KEY_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			if (!InputValidation::validate($headers[HeaderKey::SYS_KEY], InputValidation::Pattern_Num, false)) {
				\ExceptionHandler::PushException(self::INPUT_VALIDATION_FAILED_FOR_SYS_KEY);
				return HTTPStatusCodes::BAD_REQUEST;
			}
		}
		//................................
		if (empty($headers[HeaderKey::IP_ADDRESS])) {
			\ExceptionHandler::PushException(self::IP_ADDRESS_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			if (!InputValidation::validate($headers[HeaderKey::IP_ADDRESS], InputValidation::Pattern_Num, false)) {
				\ExceptionHandler::PushException(self::INPUT_VALIDATION_FAILED_FOR_IP_ADDRESS);
				return HTTPStatusCodes::BAD_REQUEST;
			}
		}
		//................................
		if (empty($headers[HeaderKey::H_TOKEN])) {
			\ExceptionHandler::PushException(self::H_TOKEN_IS_MISSING);
			return HTTPStatusCodes::BAD_REQUEST;
		}
		else{
			$salt = $container->get('settings')['hashSalt'] . $headers[HeaderKey::PERSON_ID];
			$hash = password_hash($headers[HeaderKey::USER_ID], PASSWORD_BCRYPT, ["salt" => $salt]);
			if ($headers[HeaderKey::H_TOKEN] !== $hash) {
				\ExceptionHandler::PushException(self::INVALID_TOKEN);
				return HTTPStatusCodes::FORBIDDEN;
			}
		}
		//................................

		return true;
	}
}
