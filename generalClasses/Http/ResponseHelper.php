<?php
//---------------------------
// programmer:	S.Soltani 
// create Date:	97.05
//---------------------------
use Psr\Http\Message\ResponseInterface as Response;

class ResponseHelper {

    public static function createSuccessfulResponse(Response $response, $data = null) {
        $httpresponse = new HttpResponse();
        $httpresponse->setHttpStatus(200);
        $httpresponse->setResult($data);

        $response->withHeader('Content-Type', 'application/json');
        $response->withStatus($httpresponse->getHttpStatus());
        $response->getBody()->write($httpresponse->getJSONEncode());
        return $response;
    }

    /**
     * 
     * @param Response $response
     * @param int $httpStatus
     * @param array(errorCode=> "" , errorMessage=> "") $error
     * @return Response
     */
    public static function createFailureResponse(Response $response, $httpStatus, $error = null) {
        $httpresponse = new HttpResponse();
        $httpresponse->setHttpStatus($httpStatus);
        if ($error != null){
            $httpresponse->setCode($error["code"]);
            $httpresponse->setMessage($error["message"]);
        }
        else{
             $httpresponse->setCode(HTTPStatusCodes::getCodeDescription($httpStatus));
        }
        $response->withHeader('Content-Type', 'application/json');
        $response->withStatus($httpresponse->getHttpStatus());
        $response->getBody()->write($httpresponse->getJSONEncode());
        return $response;
    }

    /**
     * 
     * @param Response $response
     * @param type $errorMessage
     * @return Response
     */
    public static function createFailureResponseByException(Response $response, $errorMessage) {
        $httpresponse = new HttpResponse();
        $httpresponse->setHttpStatus(HTTPStatusCodes::INTERNAL_SERVER_ERROR);
        $httpresponse->setCode("EXCEPTION");
        $httpresponse->setMessage($errorMessage);

        $response->withHeader('Content-Type', 'application/json');
        $response->withStatus($httpresponse->getHttpStatus());
        $response->getBody()->write($httpresponse->getJSONEncode());
        return $response;
    }

}
