<?php
//---------------------------
// programmer:	S.Soltani
// create Date:	97.05
// programmer:	SH.Jafarkhani
// create Date:	98.07
//---------------------------

use GuzzleHttp\Client;

class HttpResponse {

    const METHOD_GET = "get";
    const METHOD_POST = "post";
    const METHOD_PUT = "put";
    const METHOD_PATCH = "patch";
    const METHOD_DELETE = "delete";

    private $httpStatus;
    private $code;
    private $message;
    private $result;

    private $extraHeaders = array();
    public $hashsalt;

    public function __construct($HashSalt = "") {
        $this->hashsalt = $HashSalt;
    }

    /**
     *
     * @param array $headerParams
     */
    public function AddHeader($headerParams) {

        $this->extraHeaders = $headerParams;
    }

    /**
     *
     * @param string $method HttpResponse::METHOD_GET or
     *HttpResponse::METHOD_POST or
     *HttpResponse::METHOD_PUT or
     *HttpResponse::METHOD_PATCH or
     *HttpResponse::METHOD_DELETE
     * @param string $url microservice address
     * @param array $params for example array('OfficeID' => 827,"SourceID" => 8888,"attachfile" => fopen('test.txt', 'r') );
     * @return boolean true/false
     */
    public function CallService($method, $url, $params){

        $client = new Client();
        switch($method)
        {
            case self::METHOD_GET :
            case self::METHOD_DELETE :
                $client = new Client();
                $response = $client->request($method, $url,  [
                    'query' => $params,
                    'headers'=> $this->GetHeader()
                ]);
                break;
            //METHOD_PUT added by S.Ehsani
            case self::METHOD_PUT :
                $client = new Client();
                $response = $client->request($method, $url,  [
                    'form_params' => $params,
                    'headers'=> $this->GetHeader()
                ]);
                break;

            case self::METHOD_POST :
                $multipartArr = array();
                foreach($params as $key => $value)
                    $multipartArr[] = array("name" => $key, "contents" => $value);

                $response = $client->request($method, $url,  [
                    'multipart' => $multipartArr,
                    'headers'=> $this->GetHeader(),
					'verify' => false
                ]);
                break;
        }
        $content = ($response->getBody()->getContents()); // added by S.Ehsani
        $arr = json_decode($response->getBody());
		print_r($arr);
        //*******************added by S.Ehsani**************
        if(empty($arr)){
            $header = substr($content, 0, strpos($content,'}'));
            $header= json_decode($header);
            $this->httpStatus = $header->httpStatus;
            $this->message = $header->message;
            $this->code = $header->code;

            $res = substr($content, strpos($content,'}')+1,strlen($content));
            $this->result =json_decode($res);

        }
        //***************************************************
        else {
            $this->httpStatus = $arr->httpStatus;
            $this->result = $arr->result;
            $this->message = $arr->message;
            $this->code = $arr->code;
        }
        //return $this->isOk(); Omitted by S.Ehsani
        return $this->isSuccessful(); //Modified By S.Ehsani

    }

    private function GetHeader(){
		
		return array();
		/*if(isset($GLOBALS["FUMHeaderInfo"]))
		{
			$headerInfo = $GLOBALS["FUMHeaderInfo"];
			$ipAddress = isset($headerInfo[HeaderKey::IP_ADDRESS]) ? $headerInfo[HeaderKey::IP_ADDRESS] : "";
			$userId = isset($headerInfo[HeaderKey::USER_ID]) ? $headerInfo[HeaderKey::USER_ID] : "";
			$personId = isset($headerInfo[HeaderKey::PERSON_ID]) ? $headerInfo[HeaderKey::PERSON_ID] : "";
			$userRole = isset($headerInfo[HeaderKey::USER_ROLES]) ? $headerInfo[HeaderKey::USER_ROLES] : "";
			$sysCode = isset($headerInfo[HeaderKey::SYS_KEY]) ? $headerInfo[HeaderKey::SYS_KEY] : "";
		}
		else
		{
			$ipAddress = $_SESSION['LIPAddress'];
			$userId = $_SESSION['UserID'];
			$personId = $_SESSION['PersonID'];
			$userRole = empty($_SESSION['UserRole']) ? 0 : $_SESSION['UserRole'];
			$sysCode = $_SESSION['SystemCode'];
		}

        $salt = $this->hashsalt . $personId;
        $hash = password_hash($userId, PASSWORD_BCRYPT, ["salt" => $salt]);

        return array_merge(array('PERSON-ID' => $personId,
            'USER-ROLES' => $userRole,
            'SYS-KEY' => $sysCode,
            'IP-ADDRESS' => $ipAddress,
            'USER-ID' => $userId,
            'H-TOKEN' => $hash) , $this->extraHeaders );*/
    }

    public function getHttpStatus() {
        return $this->httpStatus;
    }

    public function setHttpStatus($httpStatus) {
        $this->httpStatus = $httpStatus;
    }

    public function getCode() {
        return $this->code;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function isSuccessful() {
        return $this->getHttpStatus() >= 200 && $this->getHttpStatus() < 300;
    }

    public function isOk() {
        return $this->getHttpStatus() === 200;
    }

    public function jsonSerialize() {

        $vars = get_object_vars($this);
        unset($vars["extraHeaders"]);
        unset($vars["hashsalt"]);
        return $vars;
    }

    public function getJSONEncode() {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    }


}
