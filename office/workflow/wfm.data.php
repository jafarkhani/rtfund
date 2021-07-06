<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.07
//---------------------------
require_once '../header.inc.php';
require_once 'wfm.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = isset($_POST["task"]) ? $_POST["task"] : (isset($_GET["task"]) ? $_GET["task"] : "");
if(!empty($task))
	$task();

function SelectAllFlows(){
	
	$where = "1=1";
	$param = array();
	
	if(!empty($_REQUEST["ObjectType"]))
	{
		$where .= " AND ObjectType=?";
		$param[] = $_REQUEST["ObjectType"];
	}
	
	$dt = WFM_flows::GetAll($where, $param);
	$no = $dt->rowCount();
	
	//$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt->fetchAll(), $no, $_GET["callback"]);
	die();
}

function SaveFlow(){
	
	$obj = new WFM_flows();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->FlowID > 0)
		$result = $obj->EditFlow();
	else
	{
			
		if($obj->ObjectType != SOURCETYPE_FORM)
		{
			$dt = PdoDataAccess::runquery("select * from WFM_flows where ObjectType=?", array($obj->ObjectType));
			if(count($dt) > 0)
			{
				echo Response::createObjectiveResponse(false, "برای این آیتم قبلا گردش تعریف شده است");
				die();
			}
		}
		$result = $obj->AddFlow();
		
		//------------ add start Flow step -----------
		$obj2 = new WFM_FlowSteps();
		$obj2->FlowID = $obj->FlowID;
		$obj2->StepID = "0";
		$obj2->StepDesc = "ارسال اولیه";
		$obj2->IsOuter = "YES";
		$obj2->AddFlowStep();
		//--------------------------------------------
	}
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteFlow(){
	
	$result = WFM_flows::RemoveFlow($_POST["FlowID"]);
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function selectFlowSteps(){
	$where = "";
	if(!isset($_REQUEST["all"]))
		$where = " AND IsOuter='NO'";
	$dt = PdoDataAccess::runquery("select * from WFM_FlowSteps 
		where IsActive='YES' AND FlowID=? ". $where , array($_GET["FlowID"]));
	
	$StartArr = array($dt[0]);
	$StartArr[0]["StepRowID"] = 0;
	$StartArr[0]["StepID"] = 0;
	$StartArr[0]["StepDesc"] = "ارسال اولیه";
	
	$dt = array_merge($StartArr, $dt);
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//............................

function SelectSteps(){
	
	$dt = WFM_FlowSteps::GetAll("fs.IsActive='YES' AND fs.IsOuter='NO' AND FlowID=? " . dataReader::makeOrder(), array($_GET["FlowID"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveStep(){
	
	$obj = new WFM_FlowSteps();
	
	if(empty($_POST['StepParentID'])) {		
		PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);			
	}	
	else {
		PdoDataAccess::FillObjectByArray($obj, $_POST);
		
		if($_POST['StepParentID'] == 'src' ) {
			
			$PID = PdoDataAccess::runquery("select StepParentID from WFM_FlowSteps where FlowID=? AND StepID = 0 ", 
											array($obj->FlowID));
			$obj->StepParentID = $PID[0]['StepParentID']; 
		}
		
	}
	
	if($obj->PersonID == null)
		$obj->PersonID = PDONULL;
	if($obj->PostID == null)
		$obj->PostID = PDONULL;
	if($obj->JobID == null)
		$obj->JobID = PDONULL;
	
	if($obj->StepRowID > 0)
		$result = $obj->EditFlowStep();
	else
	{		
		$dt = PdoDataAccess::runquery("select ifnull(max(StepID),0) from WFM_FlowSteps where FlowID=? AND IsActive='YES' AND IsOuter='NO'", 
				array($obj->FlowID));
		$obj->StepID = $dt[0][0]*1 + 1;
		
		$result = $obj->AddFlowStep();
	}
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteStep(){
	
	$result = WFM_FlowSteps::RemoveFlowStep($_POST["StepRowID"]);	
	echo Response::createObjectiveResponse($result, ExceptionHandler::popExceptionDescription());
	die();
}

function MoveStep(){
	
	$FlowID = $_POST["FlowID"];
	$StepID = $_POST["StepID"];
	$direction = $_POST["direction"] == "-1" ? -1 : 1;
	$revdirection = $direction == "-1" ? "+1" : "-1";
	
	PdoDataAccess::runquery("update WFM_FlowSteps 
		set StepID=1000 
		where FlowID=? AND StepID=? AND IsOuter='NO' AND IsActive='YES'",
			array($FlowID, $StepID));
	
	PdoDataAccess::runquery("update WFM_FlowSteps 
			set StepID=StepID $revdirection 
			where FlowID=? AND StepID=? AND IsOuter='NO' AND IsActive='YES'",
			array($FlowID, $StepID + $direction));
	
	PdoDataAccess::runquery("update WFM_FlowSteps 
		set StepID=? 
		where FlowID=? AND StepID=1000 AND IsOuter='NO' AND IsActive='YES'",
			array($StepID + $direction, $FlowID));
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

//............................

function SelectAllForms(){
	
	$where = " ";
	$param = array();

	$ObjectDesc = 
		"case 
			when b.param4='loan' 
				then concat_ws(' ','وام شماره',lp.RequestID,'به مبلغ',
				PartAmount,'مربوط به',if(pp.IsReal='YES',concat(pp.fname, ' ', pp.lname),pp.CompanyName))
				
			when b.param4='contract' 
				then concat_ws(' ','قرارداد شماره',c.ContractID,cp.CompanyName,cp.fname,cp.lname)
			
			when b.param4='warrenty' 
				then concat_ws(' ','ضمانت نامه [',wr.RefRequestID,'] ', 
				wp.CompanyName,wp.fname,wp.lname, 'به مبلغ ',
				format(wr.amount,0),'از تاریخ',/*g2j*/(wr.StartDate),'تا تاریخ',/*g2j*/(wr.EndDate))
			
			when b.param4='form' 
				then concat_ws(' ',wfmf.FormTitle,'به شماره فرم ',wfmr.RequestNo,if(wfmf.DescItemID>0,concat('[ ',wfi.ItemName,' : ',wri.ItemValue,' ]'),'')) 
			
			when b.param4='process' then 
				concat_ws(' ','فرایند ',process.ProcessTitle,pperson.CompanyName,pperson.fname,pperson.lname)
			
			when b.param4 in('CORRECT','DayOFF','OFF','DayMISSION','MISSION','EXTRA','CHANGE_SHIFT')
				then concat_ws(' ','درخواست ',ap.CompanyName,ap.fname,ap.lname,'مربوط به تاریخ', /*g2j*/(ar.FromDate))
				
			when b.param4='accdoc' 
				then concat_ws(' ','سند شماره',ad.LocalNo,adb.BranchName)
		end";
		
	if(!empty($_GET["fields"]) && !empty($_GET["query"]))
	{
		$field = $_GET["fields"] == "ObjectDesc" ? $ObjectDesc : $_GET["fields"];
		$field = $_GET["fields"] == "StepDesc" ? "ifnull(fs.StepDesc,'ارسال اولیه')" : $field;
		$field = $_GET["fields"] == "ObjectTypeDesc" ? "b.InfoDesc" : $field;
		$where .= " AND $field like :fld";
		$param[":fld"] = "%" . $_GET["query"] . "%";
	}
	//----------------- received forms ----------------------
	if(!empty($_GET["MyForms"]) && $_GET["MyForms"] == "true")
	{
		$dt = PdoDataAccess::runquery("
			select FlowID,StepID 
			from WFM_FlowSteps s 
			where s.IsActive='YES' AND s.PersonID=:pid
			
			union all
			
			select FlowID,StepID 
			from WFM_FlowSteps s 
			join BSC_jobs j using(JobID)
			where s.IsActive='YES' AND j.PersonID=:pid
			
			union all 
			
			select FlowID,StepID 
			from WFM_FlowSteps s 
			join BSC_jobs j using(PostID)
			where s.IsActive='YES' AND j.PersonID=:pid

			", array(":pid" => $_SESSION["USER"]["PersonID"]));
		
		if(count($dt) == 0)
		{
			echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
			die();
		}
		$where .= " AND fr.IsEnded='NO' AND (";
		foreach($dt as $row)
		{
			$preStep = $row["StepID"]*1-1;
			$nextStep = $row["StepID"]*1+1;
			
			$where .= 
				"(fr.FlowID=" . $row["FlowID"] ." AND fs.StepID=" . $preStep . " AND ActionType='CONFIRM') OR 
				(fr.FlowID=" . $row["FlowID"] . " AND fs.StepID=" . $nextStep . " AND ActionType='REJECT') OR";
		}
		$where = substr($where, 0, strlen($where)-2) . ")";
	}
	//---------------  sended forms --------------------------
	$join = $select = "";
	if(!empty($_GET["SendForms"]) && $_GET["SendForms"] == "true")
	{
		$select = ",fr2.ActionDate SendActionDate";
		$join = " join (
					select f.FlowID,ObjectID,ObjectID2,f.ActionDate
					from WFM_FlowRows f join WFM_FlowSteps s using(StepRowID)
					where s.StepID<>0 AND f.PersonID=". $_SESSION["USER"]["PersonID"]." 
						AND (f.IsEnded='YES' OR f.IsLastRow='NO')
						
				)fr2 on(fr2.FlowID=fr.FlowID
						AND fr2.ObjectID=fr.ObjectID
						AND fr2.ObjectID2=fr.ObjectID2 )";
	}
	//--------------------------------------------------------
	
	$query = "select fr.*,f.FlowDesc, 
					f.ObjectType,
					b.InfoDesc ObjectTypeDesc,
					concat(if(fr.ActionType='REJECT','رد ',''),ifnull(fr.StepDesc,'ارسال اولیه')) StepDesc,
					if(p.IsReal='YES',concat(p.fname, ' ',p.lname),p.CompanyName) fullname,
					$ObjectDesc ObjectDesc,
					b.param1 url,
					b.param2 parameter,
					b.param3 target,
					b.param4
					$select
	
				from WFM_FlowRows fr $join
				
				join WFM_flows f on(f.FlowID=fr.FlowID)
				join BaseInfo b on(b.TypeID=11 AND b.InfoID=f.ObjectType)
				left join WFM_FlowSteps fs on(fr.StepRowID=fs.StepRowID)
				join BSC_persons p on(fr.PersonID=p.PersonID)
				
				left join LON_ReqParts lp on(b.param4='loan' AND fr.ObjectID=PartID)
				left join LON_requests lr on(lp.RequestID=lr.RequestID)
				left join BSC_persons pp on(lr.LoanPersonID=pp.PersonID)

				left join CNT_contracts c on(b.param4='contract' AND fr.ObjectID=c.ContractID)
				left join BaseInfo cbf on(cbf.TypeID=18 AND cbf.InfoID=c.ContractType)
				left join BSC_persons cp on(cp.PersonID=c.PersonID)

				left join WAR_requests wr on(b.param4='warrenty' AND wr.RequestID=fr.ObjectID)
				left join BaseInfo bf on(bf.TypeID=74 AND bf.InfoID=wr.TypeID)
				left join BSC_persons wp on(wp.PersonID=wr.PersonID)

				left join WFM_requests wfmr on(b.param4='form' AND wfmr.RequestID=fr.ObjectID)
				left join WFM_forms wfmf on(wfmr.FormID=wfmf.FormID)	
				left join WFM_FormItems wfi on(wfi.FormID=wfmf.FormID AND wfi.FormItemID=wfmf.DescItemID)
				left join WFM_RequestItems wri on(wri.RequestID=wfmr.RequestID AND wri.FormItemID=wfmf.DescItemID)
	
				left join BSC_processes process on(b.param4='process' AND process.ProcessID=fr.ObjectID)
				left join BSC_persons pperson on(pperson.PersonID=fr.ObjectID2)	

				left join ATN_requests ar on(b.param4=ar.ReqType AND ar.RequestID=fr.ObjectID)
				left join BSC_persons ap on(ap.PersonID=ar.PersonID)
	
				left join ACC_docs ad on(b.param4='accdoc' AND ad.DocID=fr.ObjectID)
				left join BSC_branches adb on(adb.BranchID=ad.BranchID)
	
				where fr.IsLastRow='YES' " . $where . dataReader::makeOrder();
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	/*
	echo PdoDataAccess::GetLatestQueryString();
	print_r(ExceptionHandler::PopAllExceptions());
	*/
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	//----------------- get loan RequestID -----------------------
	for($i=0; $i<count($temp); $i++)
	{
		if($temp[$i]["param4"] != "form")
		{
			$temp[$i]["LoanRequestID"] = 0;
			continue;
		}
		$dt = PdoDataAccess::runquery("SELECT ItemValue 
            FROM WFM_RequestItems join WFM_FormItems using(FormItemID)
            where ItemType='loan'  AND RequestID=?", array($temp[$i]['ObjectID']));
		$temp[$i]["LoanRequestID"] = count($dt) > 0 ? $dt[0][0] : "0";	
		
		/*******************************get childs*******************/		
		$AllChild = "";
		$ChildDt = PdoDataAccess::runquery(" select StepRowID, StepDesc from WFM_FlowSteps where StepParentID = ? " , array($temp[$i]['StepRowID']) ) ; 
				
		for($j=0;$j<count($ChildDt);$j++) 
		{
			$AllChild .= $ChildDt[$j]['StepRowID'].'-'.$ChildDt[$j]['StepDesc']."," ; 
		}
				
		$temp[$i]["childs"] = substr($AllChild,0,-1) ;	
		
		/************************************************************/
	}
	//------------------------------------------------------------
	
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function ChangeStatus(){
	
	$RowIDs = array();
    foreach ($_POST as $key => $value)
        if (strpos($key, "chk_RowID_") !== false)
            $RowIDs[] = str_replace("chk_RowID_", "", $key);
	if(count($RowIDs) == 0)
		$RowIDs[] = $_POST["RowID"];
	
	$pdo = PdoDataAccess::getPdoObject();
	
	
	$errors = "";
	foreach($RowIDs as $RowID)
	{
		$pdo->beginTransaction();
	
		$mode = $_REQUEST["mode"];
		$SourceObj = new WFM_FlowRows($RowID);
		$FlowObj = new WFM_flows($SourceObj->FlowID);
 
		if( $SourceObj->FlowID == 95 )
			continue;
		
		$newObj = new WFM_FlowRows();
		$newObj->FlowID = $SourceObj->FlowID;
		$newObj->ObjectID = $SourceObj->ObjectID;
		$newObj->ObjectID2 = $SourceObj->ObjectID2;
		$newObj->PersonID = $_SESSION["USER"]["PersonID"];
		$newObj->ActionType = $mode;
		$newObj->ActionDate = PDONOW;
		$newObj->ActionComment = $_POST["ActionComment"];
		//.............................................
		
		if(isset($_POST["StepID"]))
			$StepID = $_POST["StepID"];
		if($_POST["ChildID"] > 0 ) 
		{			
			
			$resStep = PdoDataAccess::runquery(" select fs.StepID StepID , fs.LastStep, bfs.StepID PStepID
												 from WFM_FlowSteps fs
															inner join WFM_FlowSteps fst on fs.StepParentID = fst.StepRowID
															left join WFM_FlowSteps bfs on bfs.StepRowID = fst.StepParentID

												 where fs.StepRowID = ? " , array($_POST["ChildID"])) ;	
			
			$StepID = $SourceObj->ActionType == "CONFIRM" ? $resStep[0]['StepID'] : $resStep[0]['PStepID'];
			
			if( $resStep[0]['LastStep'] == 1 ) 
				$newObj->IsEnded = "YES";
		}			
		else 
			$StepID = $SourceObj->ActionType == "CONFIRM" ? $SourceObj->_StepID+1 : $SourceObj->_StepID-1;

		//.............................................
		if($newObj->ActionType == "CONFIRM" && $_POST["ChildID"] == 0 )
		{
			$dt = PdoDataAccess::runquery("select Max(StepID) maxStepID from WFM_FlowSteps 
										   where IsActive='YES' AND FlowID=? AND IsOuter='NO'" , array($newObj->FlowID));
			if($dt[0][0] == $StepID)
				$newObj->IsEnded = "YES";
		}
		
		//.............................................
		$result = $newObj->AddFlowRow($StepID, $pdo , $_POST["ChildID"] );	
		
		if(!$result)
		{
			$pdo->rollBack();
			$errors .= "خطا 1در ردیف " . $RowID . "<br>";
			continue;
		}

		if($newObj->IsEnded == "YES")
			$result = WFM_FlowRows::EndObjectFlow($FlowObj->_ObjectType, 
					$newObj->ObjectID, $newObj->ObjectID2, $pdo);
		
		$errors .= ExceptionHandler::GetExceptionsToString();
		ExceptionHandler::PopAllExceptions();
		
		if(!$result)
		{
			$pdo->rollBack();
			$errors .= "خطا2 در ردیف " . $RowID . "<br>";
			continue;
		}
		$pdo->commit();
	}
	
	echo Response::createObjectiveResponse($errors == "", $errors);
	die();
}

function StartFlow(){
	
	$FlowID = $_REQUEST["FlowID"];
	$ObjectID = $_REQUEST["ObjectID"];
	$result = WFM_FlowRows::StartFlow($FlowID, $ObjectID);
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function ReturnStartFlow(){
	
	$FlowID = $_REQUEST["FlowID"];
	$ObjectID = $_REQUEST["ObjectID"];
	$objectID2 = isset($_REQUEST["ObjectID2"]) ? $_REQUEST["ObjectID2"] : 0;
	$result = WFM_FlowRows::ReturnStartFlow($FlowID, $ObjectID, $objectID2);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteAllFlow(){
	
	$FlowID = $_REQUEST["FlowID"];
	$ObjectID = $_REQUEST["ObjectID"];
	$result = WFM_FlowRows::DeleteAllFlow($FlowID, $ObjectID);
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}
//-------------------------------------

function GetStepPersons(){
	
	$dt = WFM_FlowStepPersons::Get(" AND StepRowID=?", array($_REQUEST["StepRowID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount());
	die();
}

function SaveStepPerson(){
	
	$obj = new WFM_FlowStepPersons();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function RemoveStepPersons(){
	
	$obj = new WFM_FlowStepPersons($_REQUEST["RowID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//--------------------------------------

function WFM_FormGridColumns(){}
?>