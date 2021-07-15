<?php

//---------------------------
// programmer:	Mahdipour
// create Date:	1400.02
//---------------------------
ini_set("display_errors", "on");
require_once '../../header.inc.php';
require_once 'ManageWarrentyReq.class.php';
require_once 'IssuanceInfo.class.php';
require_once 'BailInfo.class.php';
require_once '../../office/workflow/wfm.class.php';
require_once(inc_response);
require_once inc_dataReader;

$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");

switch ($task) {

	case "searchWR" :
		searchWR();

	case "SaveWR":
		SaveWR();
		
	case "SaveIssuanceInfo":
		  SaveIssuanceInfo();

	case "removeWFC":
		removeWFC();
		
	case "GetWCsuites":
		GetWCsuites();
}

function searchWR() {
	
	if (!empty($_REQUEST["fields"]) && !InputValidation::validate($_REQUEST["fields"], InputValidation::Pattern_EnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	if (!empty($_REQUEST["query"]) && !InputValidation::validate($_REQUEST["query"], InputValidation::Pattern_FaEnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	
	$where = " where (1=1) ";
	$whereParam = array();
	
	$field = isset($_GET ["fields"]) ? $_GET ["fields"] : "";
	if (isset($_GET ["query"]) && $_GET ["query"] != "") {
		switch ($field) {
			case "fullname" :
				$where .= " AND concat_ws(' ',fname,lname,CompanyName) LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;
			case "BailTypeTitle" :
				$where .= " AND bi.InfoDesc LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

			case "subject" :
				$where .= " AND subject LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

			case "status" :
				$where .= " AND if((bc.param1 = 1 and bc.param2 = 1 and bc.param3 = 1 and bc.param4 = 1 and bc.param5 = 1 ) ,'تایید شده' ,'رد شده' )   LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

		}
	}
	
	$where .= dataReader::makeOrder();
	$temp = manage_BailCondition::GetAll($where,$whereParam);
	
	$no = count($temp);
	//..........................secure section ........................
	$start = (int)$_GET["start"] ;
	$limit = (int)$_GET["limit"] ;
	if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
	{
		echo dataReader::getJsonData(array(), 0);
		die();
	}
		
	$temp = array_slice($temp, $start,$limit);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function SaveWR() {
	        
	$obj = new manage_BailCondition();   
	PdoDataAccess::FillObjectByArray($obj, $_POST);	
			
	$obj->param1 = (isset($_POST["param1"]) ? 1 : 0 );
	$obj->param2 = (isset($_POST["param2"]) ? 1 : 0 );
	$obj->param3 = (isset($_POST["param3"]) ? 1 : 0 );
	$obj->param4 = (isset($_POST["param4"]) ? 1 : 0 );
	$obj->param5 = (isset($_POST["param5"]) ? 1 : 0 );
	$obj->ReqDate = DateModules::NowDateTime();
	
	if (!empty($obj->BID)) {
		if (!$obj->Edit()) {
                        echo Response::createObjectiveResponse(false, "UpdateError BID = ".$obj->BID);
			die();
		}
	} else {
		
		if (!$obj->Add()) {
                    echo Response::createObjectiveResponse(false, "InsertError BID = ".$obj->BID);
                    echo PdoDataAccess::GetLatestQueryString();
                    print_r(ExceptionHandler::PopAllExceptions());
    		die();
		}
	}

	echo Response::createObjectiveResponse(true, $obj->BID);
	die();
}

function SaveIssuanceInfo() {
	
	$obj = new manage_IssuanceInfo();
	$BailObj = new manage_BailInfo();
	$BCObj = new manage_BailCondition();
   	
	$obj->BID = $_POST['BID'];  
	$obj->RelatedOrg =  $_POST['organization']; 
	$obj->amount = $_POST['warAmount'];  
	$obj->duration = $_POST['warDate'];  
	$obj->SugBail = $_POST['ProposedGuarantee'];  
	$obj->comments = $_POST['SupplementaryExplanation'];   
	$obj->ExtraComments = !empty($_POST['CommentSuggest']) ? $_POST['CommentSuggest'] : "" ; 
	$obj->ExtraComments2 = !empty($_POST['CommentSuggest2']) ? $_POST['CommentSuggest2'] : "" ; 	
	$obj->EmpType = $_POST['EmpType'];  
	
	if(!empty($_POST['KnowledgeBase']) && $_POST['KnowledgeBase'] == true )
		$obj->KnowledgeBase = 1 ; 
	else 
		$obj->KnowledgeBase = 0 ; 
	
	$qry = " select * from LON_IssuanceInfo where BID = ? "; 
	$res = PdoDataAccess::runquery($qry,array($_POST['BID']));
	
	if( !empty($res[0]['IID']) && $res[0]['IID'] > 0 )
	{	
		
	
		$obj->IID = $res[0]['IID'] ; 
		
		if (!$obj->Edit()) {
			echo Response::createObjectiveResponse(false, "UpdateError IID = ".$obj->IID);
			die();
		}
		
		/***BailCondition****/
		$BCObj->BID = $_POST['BID'];  	
		$BCObj->PersonID = $_POST['PersonID'];
		$BCObj->BailType = $_POST['TypeID'];
		$BCObj->LetterNo = $_POST['LetterNo'];  		
		$BCObj->subject = $_POST['contractTitle'];  
		
		$result = PdoDataAccess::runquery(" update LON_BailCondition set LetterNo= ".$_POST['LetterNo']." , "
				. "subject= '".$_POST['contractTitle']."', PersonID = ".$_POST['PersonID'].", BailType = ".$_POST['TypeID']." where BID = ".$_POST['BID']  );

		if ($result === false)
			return false;
		
		//......................................................................
		
		foreach ($_POST as $key => $value) {
			$exp_key = explode('_', $key);

			if ($exp_key[0] == 'GuarID') {
				$GuarID[] = $value;
				$IDItem[] = $exp_key[1];
			}
			if ($exp_key[0] == 'GuarType') {
				$GuarType[] = $value;
				$TypeItem[] = $exp_key[1];
			}
			if ($exp_key[0] == 'GuarAmount') {
				$GuarAmount[] = $value;
				$AmountItem[] = $exp_key[1];
			}
		}

		$BailObj->BID = $_POST['BID'];  
		if (count($TypeItem) > 0 || count($AmountItem) > 0 || count($GuarID) > 0) {
			$num = count($TypeItem);
			for ($x = 0; $x < $num; $x++) {

				$BailObj->BIID = $GuarID[$x] ; 
				$BailObj->BailType = $GuarType[$x] ; 
				$BailObj->BailValue = $GuarAmount[$x]; 	

				if (!$BailObj->Edit()) {
					echo Response::createObjectiveResponse(false, "InsertError BIID = ".$BailObj->BIID);
					echo PdoDataAccess::GetLatestQueryString();
					print_r(ExceptionHandler::PopAllExceptions());
					die();
				}			

			}
		}
		//......................................................................
	}
	else {
	  
		if (!$obj->Add()) {
			echo Response::createObjectiveResponse(false, "InsertError IID = ".$BailObj->IID);
			echo PdoDataAccess::GetLatestQueryString();
			print_r(ExceptionHandler::PopAllExceptions());
			die();
		}	

		/*********************************/	

		foreach ($_POST as $key => $value) {
			$exp_key = explode('_', $key);

			if ($exp_key[0] == 'GuarType') {
				$GuarType[] = $value;
				$TypeItem[] = $exp_key[1];
			}
			if ($exp_key[0] == 'GuarAmount') {
				$GuarAmount[] = $value;
				$AmountItem[] = $exp_key[1];
			}
		}

		$BailObj->BID = $_POST['BID'];  
		if (count($TypeItem) > 0 || count($AmountItem) > 0) {
			$num = count($TypeItem);
			for ($x = 0; $x < $num; $x++) {

				$BailObj->BailType = $GuarType[$x] ; 
				$BailObj->BailValue = $GuarAmount[$x]; 	

				if (!$BailObj->Add()) {
					echo Response::createObjectiveResponse(false, "InsertError BIID = ".$BailObj->BIID);
					echo PdoDataAccess::GetLatestQueryString();
					print_r(ExceptionHandler::PopAllExceptions());
					die();
				}			

			}
		}

		/***********Insert flow************/
		$FlowID = 95 ;  /*فرآیند صدور ضمانت نامه*/
		$dt = WFM_FlowRows::GetFlowInfo($FlowID,$obj->IID);

		if(!$dt["IsStarted"]) 		
			$result = WFM_FlowRows::StartFlow($FlowID,$obj->IID);	
		else 
			$result = WFM_requests::ConfirmRequest ($ReqObj->RequestID);

		if(!$result)
		{
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
	}
	/*********************************/
	echo Response::createObjectiveResponse(true, $obj->IID);
	die();
}

function removeWFC() {
	//.................. secure section .....................
	if (!InputValidation::validate($_POST["BID"], InputValidation::Pattern_Num, false)) {
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	//......................................................
	$result = manage_BailCondition::Remove($_POST["BID"]);
	
	if (!$result) {
		Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	} else {
		Response::createObjectiveResponse(true, "");
		die();
	}
}

function GetWCsuites()
{
    if(!InputValidation::validate($_REQUEST["WCID"], InputValidation::Pattern_Num, false))
    {
        echo dataReader::getJsonData(array(), 0);
        die();
    }
    if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
    {
        echo dataReader::getJsonData(array(), 0);
        die();
    }
        
	$WCID = (int)$_REQUEST["WCID"];
	
	$temp = manage_WelfareCenter::GetWelfareSuitesInfo($WCID);
	echo dataReader::getJsonData($temp, count($temp), $_GET ["callback"]);
	die();
}

?>