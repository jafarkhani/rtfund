<?php
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 99.07
//-----------------------------

require_once '../header.inc.php';
require_once 'organDoc.class.php';
require_once inc_dataReader;
require_once inc_response;



$task = isset($_REQUEST ["task"]) ? $_REQUEST ["task"] : "";

if(!empty($task)) 
	$task();

function selectOrgDocTypes(){

    $where = "";
    $param = [];
    if(!empty($_REQUEST["InfoID"]))
    {
        $where .= " AND InfoID=:p";
        $param[":p"] = $_REQUEST["InfoID"];
    }
    $dt = PdoDataAccess::runquery("select * from BaseInfo 
		where typeID=8 AND IsActive='YES' AND param1=17" . $where . " order by InfoID",$param);
    /*var_dump($dt);*/
    echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
    die();
}

function SelectOrgDocs() {
	
	$where = "";
	$params = array();
	/*var_dump($_REQUEST);*/
	if(!empty($_REQUEST["orgDocID"]))
	{
		$where .= " AND orgDocID=:c";
		$params[":c"] = $_REQUEST["orgDocID"];
	}
	
	if(!empty($_REQUEST["orgDocType"]))
	{
		$where .= " AND b1.InfoID=:ct";
		$params[":ct"] = $_REQUEST["orgDocType"];
	}
	
    $temp = organDoc::Get($where, $params, dataReader::makeOrder());
	/*var_dump($temp);*/
	$res = PdoDataAccess::fetchAll($temp, $_GET['start'], $_GET['limit']);
	
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function DeleteOrgDoc(){
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();

    $obj = new organDoc($_POST['orgDocID']);
    $result = $obj->Remove();
    if(!$result)
    {
        $pdo->rollBack();
        //print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }
    $pdo->commit();
    echo Response::createObjectiveResponse(true, '');
    die();
}

function SaveOrgDoc() {

    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();

    /*if($_POST['ContractType'] == 3){
        $_POST['content']='در قرارداد عاملیت، اطلاعات قرارداد به صورت پیوست قرار گرفته است.';
        $_POST['TemplateID']=1;
    }
    if($_POST['ContractType'] == 4){
        $_POST['content']='در قرارداد پیمانکاران، اطلاعات قرارداد به صورت پیوست قرار گرفته است.';
        $_POST['TemplateID']=2;
    }*/

    $CntObj = new organDoc();
    
    /*var_dump($CntObj);*/
    PdoDataAccess::FillObjectByArray($CntObj, $_POST);
    /*echo '<br>';
    echo '<br>';
    echo '<br>';
    echo '<br>';*/

    if ($_POST["orgDocID"] == "")
    {
        /*$CntObj->RegPersonID = $_SESSION['USER']["PersonID"];
        $CntObj->RegDate = PDONOW;
        $CntObj->StatusID = CNT_STEPID_RAW;*/
        $result = $CntObj->Add($pdo);
    }
    else
    {
        $result = $CntObj->Edit($pdo);

        /* removing values of contract items */
        /*CNT_ContractItems::RemoveAll($CntObj->ContractID, $pdo);*/
    }

    if(!$result)
    {
        $pdo->rollBack();
        print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }

    $pdo->commit();
    echo Response::createObjectiveResponse(true, $CntObj->orgDocID);
    die();
}






?>

