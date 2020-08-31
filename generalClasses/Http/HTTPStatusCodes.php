<?php
//---------------------------
// programmer:	S.Soltani 
// create Date:	97.05
//---------------------------
/**
 * StatusCodes provides named constants for
 * HTTP protocol status codes.
 *
 * @author Samaneh Soltani
 */
class HTTPStatusCodes {

    // [Informational 1xx]
    const _CONTINUE = 100;
    const SWITCHING_PROTOCOLS = 101;
    const PROCESSING = 102;
    // [Successful 2xx]
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NONAUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT = 204;
    const RESET_CONTENT = 205;
    const PARTIAL_CONTENT = 206;
    // [Redirection 3xx]
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const USE_PROXY = 305;
    const UNUSED = 306;
    const TEMPORARY_REDIRECT = 307;
    // [Client Error 4xx]
    const errorCodesBeginAt = 400;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412;
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const REQUEST_URI_TOO_LONG = 414;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED = 417;
    const UNPROCESSABLR_ENTITY = 422;
    // [Server Error 5xx]
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
    const VERSION_NOT_SUPPORTED = 505;
    const INSUFFICIENT_STORAGE = 507;

    static function getCodeDescription($code){
        return self::$types[$code];
    }
    
    static $types = array(
        self::_CONTINUE => '_CONTINUE',
        self::SWITCHING_PROTOCOLS => 'SWITCHING_PROTOCOLS',
        self::PROCESSING => 'PROCESSING',
        // [Successful 2xx]
        self::OK => 'OK',
        self::CREATED => 'CREATED',
        self::ACCEPTED => 'ACCEPTED',
        self::NONAUTHORITATIVE_INFORMATION => 'NONAUTHORITATIVE_INFORMATION',
        self::NO_CONTENT => 'NO_CONTENT',
        self::RESET_CONTENT => 'RESET_CONTENT',
        self::PARTIAL_CONTENT => 'PARTIAL_CONTENT',
        // [Redirection 3xx]
        self::MULTIPLE_CHOICES => 'MULTIPLE_CHOICES',
        self::MOVED_PERMANENTLY => 'MOVED_PERMANENTLY',
        self::FOUND => 'FOUND',
        self::SEE_OTHER => 'SEE_OTHER',
        self::NOT_MODIFIED => 'NOT_MODIFIED',
        self::USE_PROXY => 'USE_PROXY',
        self::UNUSED => 'UNUSED',
        self::TEMPORARY_REDIRECT => 'TEMPORARY_REDIRECT',
        // [Client Error 4xx]
        self::errorCodesBeginAt => 'errorCodesBeginAt',
        self::BAD_REQUEST => 'BAD_REQUEST',
        self::UNAUTHORIZED => 'UNAUTHORIZED',
        self::PAYMENT_REQUIRED => 'PAYMENT_REQUIRED',
        self::FORBIDDEN => 'FORBIDDEN',
        self::NOT_FOUND => 'NOT_FOUND',
        self::METHOD_NOT_ALLOWED => 'METHOD_NOT_ALLOWED',
        self::NOT_ACCEPTABLE => 'NOT_ACCEPTABLE',
        self::PROXY_AUTHENTICATION_REQUIRED => 'PROXY_AUTHENTICATION_REQUIRED',
        self::REQUEST_TIMEOUT => 'REQUEST_TIMEOUT',
        self::CONFLICT => 'CONFLICT',
        self::GONE => 'GONE',
        self::LENGTH_REQUIRED => 'LENGTH_REQUIRED',
        self::PRECONDITION_FAILED => 'PRECONDITION_FAILED',
        self::REQUEST_ENTITY_TOO_LARGE => 'REQUEST_ENTITY_TOO_LARGE',
        self::REQUEST_URI_TOO_LONG => 'REQUEST_URI_TOO_LONG',
        self::UNSUPPORTED_MEDIA_TYPE => 'UNSUPPORTED_MEDIA_TYPE',
        self::REQUESTED_RANGE_NOT_SATISFIABLE => 'REQUESTED_RANGE_NOT_SATISFIABLE',
        self::EXPECTATION_FAILED => 'EXPECTATION_FAILED',
        self::UNPROCESSABLR_ENTITY => 'UNPROCESSABLR_ENTITY',
        // [Server Error 5xx]
        self::INTERNAL_SERVER_ERROR => 'INTERNAL_SERVER_ERROR',
        self::NOT_IMPLEMENTED => 'NOT_IMPLEMENTED',
        self::BAD_GATEWAY => 'BAD_GATEWAY',
        self::SERVICE_UNAVAILABLE => 'SERVICE_UNAVAILABLE',
        self::GATEWAY_TIMEOUT => 'GATEWAY_TIMEOUT',
        self::VERSION_NOT_SUPPORTED => 'VERSION_NOT_SUPPORTED',
        self::INSUFFICIENT_STORAGE => 'INSUFFICIENT_STORAGE');

}

?>