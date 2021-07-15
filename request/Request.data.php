<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------

require_once('../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'Request.class.php';
/*require_once '../baseinfo/elements.class.php';
require_once 'TreeModules.class.php';*/

$task = $_REQUEST["task"];
switch ($task) {
		
	default : 
		eval($task. "();");
}

function SelectReceivedRequestss($returnCount = false){
	
	$where = "StatusID in (10,50)";
	 
	$branches = FRW_access::GetAccessBranches();
	$where .= " AND BranchID in(" . implode(",", $branches) . ")";
	
	$dt = LON_requests::SelectAll($where . dataReader::makeOrder());
	
	if($returnCount)
		return $dt->rowCount();
	
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}
function SaveCustomerRequest(){
    $obj = new request();
    PdoDataAccess::FillObjectByArray($obj, $_POST);
   
        $obj->PersonID = $_SESSION["USER"]["PersonID"];
        $date=date("Y-m-d");
        $time=date("H");
        $obj->referalDate = $date;
        $obj->referalTime = $time;
        $obj->StatusID = 2;
        $obj->IsRegister = 'Yes';
        $obj->IsPresent = 'No';
		$obj->FlowID = 90; 
        /*$obj->IsInfoORService = 'Service';*/

    if($obj->IDReq > 0){
        $result = $obj->EditReq();				
    }
    else{		
        $result = $obj->AddReq();
		}
			
	/* گردش فرم */  
	/*$dt = WFM_FlowRows::GetFlowInfo($formObj->FlowID, $ReqObj->RequestID);
	if(!$dt["IsStarted"])
		$result2 = WFM_FlowRows::StartFlow($formObj->FlowID, $ReqObj->RequestID);
	else*/
	require_once '../office/workflow/form.class.php';

	$result2 = WFM_requests::ConfirmRequest($obj->IDReq,"CONFIRM" , "true");

	if(!$result2)
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	 
//-----------  save Letter pic ----------------
    if(!empty($_FILES['LetterPic']['tmp_name']))
    {
        if($_FILES['LetterPic']['size'] > 200000)
        {
            echo Response::createObjectiveResponse(false, "&#1581;&#1583;&#1575;&#1705;&#1579;&#1585; &#1581;&#1580;&#1605; &#1605;&#1580;&#1575;&#1586; &#1601;&#1575;&#1740;&#1604; 200 &#1705;&#1740;&#1604;&#1608;&#1576;&#1575;&#1740;&#1578; &#1605;&#1740; &#1576;&#1575;&#1588;&#1583;");
            die();
        }
        $st = preg_split("/\./", $_FILES['LetterPic']['name']);
        $extension = strtolower($st [count($st) - 1]);
        if(array_search($extension, array("gif","jpg","jpeg","png","pdf")) === false)
        {
            echo Response::createObjectiveResponse(false, "&#1601;&#1602;&#1591; &#1605;&#1608;&#1575;&#1585;&#1583; &#1586;&#1740;&#1585; &#1576;&#1585;&#1575;&#1740; &#1606;&#1608;&#1593; &#1601;&#1575;&#1740;&#1604; &#1605;&#1580;&#1575;&#1586; &#1605;&#1740; &#1576;&#1575;&#1588;&#1583;: <br>" .
                "gif , jpg , jpeg , png , pdf");
            die();
        }
        if(!empty($_FILES['LetterPic']['tmp_name']))
        {
            PdoDataAccess::runquery_photo("update request set LetterPic=:pdata where IDReq=:p",
                array(":pdata" => fread(fopen($_FILES['LetterPic']['tmp_name'], 'r' ),$_FILES['LetterPic']['size'])),
                array(":p" => $obj->IDReq));
        }
    }

    echo Response::createObjectiveResponse($result, !$result ? ExceptionHandler::GetExceptionsToString() : "");

}
function SelectReceivedRequests($returnCount = false){

    $where = "1=1";
    $param = array();
    
    $where .= " AND StatusID=2 AND referPersonID=0";
    $temp = request::SelectAll($where . dataReader::makeOrder(), $param);
    
    if($returnCount)
		return $temp->rowCount();
   
    $no = $temp->rowCount();
    $temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

    echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
    die();
}

function SaveNewRequest(){


if ($_POST['IsRegister']== 'No' && !empty($_POST['askerName']) && !empty($_POST['askerMob'])){

    $ask= new askerPerson();
    $where = "askerMob";
    $param = $_POST['askerMob'];
    
    $res = $ask::IsMobExist($param);

    if ($res->rowCount()>0){
        /*echo 'MobExist';*/
        $resultant=$res->fetchAll();
        $askerID=$resultant[0]['askerID'];
    }else{
        /*echo 'MobNOTExist';*/
        $ask->askerName=$_POST['askerName'];
        $ask->askerMob=$_POST['askerMob'];
        $result = $ask->AddAsker();
        $askerID=$ask->askerID;
        echo Response::createObjectiveResponse($result, $ask->askerID);
    }
}
    $obj = new request();
    PdoDataAccess::FillObjectByArray($obj, $_POST);
    if (isset($askerID)){
        $obj->askerID = $askerID;
    }
    if($obj->IDReq > 0){
        $result = $obj->EditReq();
    }
    else{
        /*echo '<br>';
        echo 'adddessssssss';
        echo '<br>';
        echo $obj->IDReq;
        echo '<br>';*/
        $result = $obj->AddReq();
    }
    echo Response::createObjectiveResponse($result, !$result ? ExceptionHandler::GetExceptionsToString() : "");

    /*PLN_plans::ChangeStatus($obj->PlanID, $obj->StepID , "", true);*/

    /*	else if(session::IsFramework())
        {
            $obj->PlanID = $PlanID;
            $result = $obj->EditPlan();
        }*/
    //print_r(ExceptionHandler::PopAllExceptions());
    /*echo Response::createObjectiveResponse($result, $obj->IDReq);
    die();*/
}
function SelectMyPlans(){

    $where = "1=1";

    $dt = request::SelectAll($where);
    print_r(ExceptionHandler::PopAllExceptions());

    $count = $dt->rowCount();
    $dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
    echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
    die();
}
function selectAllRequest(){

    $where = "1=1";
    $param = array();

    if (isset($_REQUEST['fields']) && isset($_REQUEST['query']))
    {
        /*var_dump($_REQUEST['fields']);
        echo '<br>';
        var_dump($_REQUEST['query']);
        echo '<br>';*/
        $field = $_REQUEST['fields'];
        /*$field = $_REQUEST['fields'] == "fullname" ? "concat_ws(' ',fname,lname,CompanyName)" : $field;*/
        $where .= ' and ' . $field . ' like :fld';
        $_REQUEST['query'] = $_REQUEST['query'] == "*" ? "YES" : $_REQUEST['query'];
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }

    if(!empty($_REQUEST["PersonID"]))
    {
        $where .= " AND PersonID=:p";
        $param[":p"] = $_REQUEST["PersonID"];
    }

    if(!empty($_REQUEST["query"]) && !isset($_REQUEST['fields']))
    {
        $where .= " AND ( concat(fname,' ',lname) like :p or CompanyName like :p)";
        $param[":p"] = "%" . $_REQUEST["query"] . "%";
    }
    
    $temp = request::SelectAll($where.dataReader::makeOrder(),$param);

    $no = $temp->rowCount();
    
    $temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
    
    if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["askerID"]))
    {
        $temp = array_merge(array(array("askerID" => 0)), $temp);
    }

    echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
   
    die();
}
function selectRequests(){

    $where = "1=1";
    $param = array();

    if(!empty($_REQUEST["PersonID"]))
    {
        $where .= " AND IDReq=:p";
        $param[":p"] = $_REQUEST["PersonID"];
    }

    $temp = request::SelectAll($where . dataReader::makeOrder(), $param);

    $no = $temp->rowCount();
    $temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

    if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["PersonID"]))
    {
        $temp = array_merge(array(array("PersonID" => 0)), $temp);
    }

    echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
    die();
}
function selectAskers(){
    $dt = PdoDataAccess::runquery("select * from askerPerson WHERE 1=1 ORDER by askerID ");
    echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
    die();
}
/*function selectAskerss(){
    $where = "1=1";
    $param = array();

    if(!empty($_REQUEST["askerID"]))
    {
        $where .= " AND askerID=:p";
        $param[":p"] = $_REQUEST["askerID"];
    }

    $temp = askerPerson::runquery_fetchMode("
        select * from  askerPerson
			where " . $where );

    $no = $temp->rowCount();
    $temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

    if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["askerID"]))
    {
        $temp = array_merge(array(array("askerID" => 0)), $temp);
    }

    echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
    die();
}*/
function DeleteRequest(){

    $result = request::DeleteRequest($_POST["IDReq"]);
    echo Response::createObjectiveResponse($result, "");
    die();
}

?>

