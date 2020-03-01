<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.08
//-----------------------------
			
class WFM_flows extends PdoDataAccess {

	public $FlowID;
	public $ObjectType;
	public $FlowDesc;
	public $IsSystemic;

	public $_ObjectType;
	
	function __construct($FlowID = "") {
		if($FlowID != "")
			parent::FillObject ($this, "select f.* , bf.param4 _ObjectType
				from WFM_flows f
				join BaseInfo bf on(bf.TypeID=11 AND f.ObjectType=bf.InfoID)
				where FlowID=?", array($FlowID));
	}
	
	static function GetAll($where = "", $whereParam = array()) {
		
		$query = "select f.*, InfoDesc ObjectDesc,b.param4
			from WFM_flows f
			join BaseInfo b on(TypeID=11 AND ObjectType=InfoID)";
		
		$query .= ($where != "") ? " where " . $where : "";
		
		return parent::runquery_fetchMode($query, $whereParam);
	}

	function AddFlow($pdo = null) {
		
		if (!parent::insert("WFM_flows", $this, $pdo)) {
			return false;
		}

		$this->FlowID = parent::InsertID($pdo);

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->FlowID;
		$daObj->TableName = "WFM_flows";
		$daObj->execute($pdo);

		return true;
	}

	function EditFlow($pdo = null) {
		
		if (parent::update("WFM_flows", $this, " FlowID=:fid", array(":fid" => $this->FlowID), $pdo) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->FlowID;
		$daObj->TableName = "WFM_flows";
		$daObj->execute($pdo);
		
		return true;
	}
	
	static function RemoveFlow($FlowID){
		
		$dt = PdoDataAccess::runquery("select * from WFM_FlowRows where FlowID=?", array($FlowID));
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("این گردش در برخی فرم ها استفاده شده است");
			return false;
		}
		
	 	if(!parent::delete("WFM_flows", " FlowID=?", array($FlowID)))
			return false;
	 	
	 	$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $FlowID;
		$daObj->TableName = "WFM_flows";
		$daObj->execute();
		return true;
	}
}

class WFM_FlowSteps extends PdoDataAccess {

	public $StepRowID;
	public $FlowID;
	public $StepID;
	public $StepDesc;
	public $PostID;
	public $PersonID;
	public $JobID;
	public $IsActive;
	public $IsOuter;

	static function GetAll($where = "", $whereParam = array()) {
		
		$query = "select fs.*,PostName,concat_ws(' ',fname, lname,CompanyName) fullname ,
					concat(JobID,'-',PostName) JobDesc
			from WFM_FlowSteps fs
			left join BSC_jobs j using(JobID)
			left join BSC_posts p on(if(fs.PostID is null,j.PostID=p.PostID,fs.PostID=p.PostID))
			left join BSC_persons ps on(ps.PersonID=fs.PersonID)
			";
		$query .= ($where != "") ? " where " . $where : "";
		
		return parent::runquery($query, $whereParam);
	}

	function AddFlowStep($pdo = null) {
		
		if (!parent::insert("WFM_FlowSteps", $this, $pdo)) {
			return false;
		}

		$this->StepRowID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->StepRowID;
		$daObj->SubObjectID = $this->StepID;
		$daObj->TableName = "WFM_FlowSteps";
		$daObj->execute($pdo);

		return true;
	}

	function EditFlowStep($pdo = null) {
		
		if (parent::update("WFM_FlowSteps", $this, " StepRowID=:srid", 
				array(":srid" => $this->StepRowID), $pdo) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->StepRowID;
		$daObj->SubObjectID = $this->StepID;
		$daObj->TableName = "WFM_FlowSteps";
		$daObj->execute($pdo);
		
		return true;
	}
	
	static function RemoveFlowStep($StepRowID){
		
		$info = PdoDataAccess::runquery("select * from WFM_FlowSteps where StepRowID=?", array($StepRowID));
		
		$dt = parent::runquery("select * from WFM_FlowRows
			join ( select max(RowID) RowID,FlowID,ObjectID 
					from WFM_FlowRows group by FlowID,ObjectID )t
			using(RowID,FlowID,ObjectID)
			where FlowID=? AND StepRowID=?",array($info[0]["FlowID"], $StepRowID));
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("FlowRowExists");
			return false;
		}
		
		parent::runquery("update WFM_FlowSteps set IsActive='NO', StepID=-1 where StepRowID=?", array($StepRowID));
	
		PdoDataAccess::runquery("update WFM_FlowSteps set StepID=StepID-1 
			where IsOuter='NO' AND StepID>? AND FlowID=?",
			array($info[0]["StepID"], $info[0]["FlowID"]));
	 	
	 	$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $StepRowID;
		$daObj->SubObjectID = $info[0]["StepID"];
		$daObj->TableName = "WFM_FlowSteps";
		$daObj->execute();
		return true;
	}
}

class WFM_FlowRows extends PdoDataAccess {

	public $RowID;
	public $FlowID;
	public $StepRowID;
	public $ObjectID;
	public $ObjectID2;
	public $PersonID;
	public $ActionDate;
	public $ActionType;
	public $ActionComment;
	public $IsEnded;
	public $StepDesc;
	public $IsLastRow;
	
	public $_StepID;
	public $_ObjectType;

	function __construct($RowID = "", $pdo = null) {
		if($RowID != "")
			parent::FillObject ($this, "select f.* ,StepID _StepID, bf.param4 _ObjectType
				from WFM_FlowRows f
				left join WFM_FlowSteps using(StepRowID)
				join WFM_flows fl on(f.FlowID=fl.FlowID)
				join BaseInfo bf on(bf.TypeID=11 AND fl.ObjectType=bf.InfoID)
				
				where RowID=?", array($RowID), $pdo);
	}
	
	static function GetAll($where = "", $whereParam = array()) {
		
		$query = "select sd.*, 
			concat_ws(' ',fname, lname,CompanyName) fullname
			from WFM_FlowRows sd
			join BSC_persons p using(PersonID)";
		
		$query .= ($where != "") ? " where " . $where : "";
		
		return parent::runquery_fetchMode($query, $whereParam);
	}

	function UpdateSourceStatus($StepID){
		
		switch($this->FlowID)
		{
			case "4":
				PdoDataAccess::runquery("update WAR_requests set StatusID=? where RequestID=?",
					array($StepID, $this->ObjectID ));
				break;
			case "8":
				PdoDataAccess::runquery("update ACC_docs set StatusID=? where DocID=?",
					array($StepID, $this->ObjectID ));
				break;
			case "9":
			case "10":
			case "11":
			case "12":
			case "13":
			case "14":
			case "15":				
				PdoDataAccess::runquery("update ATN_requests set ReqStatus=? where RequestID=?",
					array($StepID, $this->ObjectID ));
				break;
		}
	}

    function AddFlowRow($StepID , $pdo = null) {

        PdoDataAccess::runquery("update WFM_FlowRows set IsLastRow='NO' "
            . " where FlowID=? AND ObjectID=? AND ObjectID2=?",
            array($this->FlowID, $this->ObjectID, $this->ObjectID2), $pdo);
        /*var_dump($this->FlowID);var_dump($StepID);*/
        //.......... get StepRowID ...................
        $dt = PdoDataAccess::runquery("select StepRowID, StepDesc from WFM_FlowSteps 
			where IsActive='YES' AND FlowID=? AND StepID=?" , array($this->FlowID, $StepID));
        if(count($dt) == 0)
        {
            ExceptionHandler::PushException("خطا در تعریف وضعیت ها");
            return false;
        }
        $this->StepRowID = $dt[0]["StepRowID"];
        $this->StepDesc = $dt[0]["StepDesc"];
        //..............................................

        if (!parent::insert("WFM_FlowRows", $this, $pdo)) {
            return false;
        }

        $this->UpdateSourceStatus($StepID);

        $this->RowID = parent::InsertID($pdo);


        /*$PersonID=$_SESSION["USER"]["PersonID"];
    $queryString="select r.PersonID,r.RequestID,fr.ObjectID
          from wfm_flowrows fr
          left join wfm_requests r on (fr.ObjectID=r.RequestID)
          where IsLastRow='YES' AND IsEnded='YES' AND r.PersonID=?";
    $findEnd = PdoDataAccess::runquery_fetchMode($queryString, array($PersonID));*/
        $queryString1="select *
              from WFM_FlowRows 
              where IsLastRow='YES' AND IsEnded='YES' AND ObjectID=?";
        $findEnd1 = PdoDataAccess::runquery_fetchMode($queryString1, array($this->ObjectID));
        if ($findEnd1->rowCount() >0){
            $result = $findEnd1->fetchAll();
            $RequestID=$result[0]['ObjectID'];
            $queryString2="select RequestNo,PersonID,f.FormTitle
              from WFM_requests r
              join WFM_forms f using(FormID) 
              where r.RequestID=?";
            $findEnd2 = PdoDataAccess::runquery_fetchMode($queryString2, array($RequestID));
            if ($findEnd2->rowCount() > 0){
                $resultant = $findEnd2->fetchAll();
                $RequestNo=$resultant[0]['RequestNo'];
                $PersonID=$resultant[0]['PersonID'];
                $FormTitle=$resultant[0]['FormTitle'];
                $_POST['MsgTitle']='تایید فرم';
                $_POST['MsgDesc']="با سلام. فرم ".$FormTitle."  شما با شماره ".$RequestNo." مورد تایید قرار گرفت.";
                $_POST['PersonID']=1000;


                $obj = new OFC_messages();
                PdoDataAccess::FillObjectByArray($obj, $_POST);
                /*$obj->PersonID = $_POST['PersonID'];*/
                $obj->MsgDate = PDONOW;
                $pdoobj = PdoDataAccess::getPdoObject();
                /*$pdoobj->beginTransaction();*/

                if(!$obj->Add($pdoobj))
                {
                    $pdoobj->rollBack();
                    echo Response::createObjectiveResponse(false, "خطا در ایجاد پیام");
                    die();
                }
                $obj2 = new OFC_MessageReceivers();
                $obj2->MessageID = $obj->MessageID;
                $obj2->PersonID = $PersonID;
                $obj2->Add($pdoobj);

            }
        }
        /*if ($findEnd1){
            echo 'query true barmigardanad';
        }else{
            echo 'query false barmigardanad';
        }
        if ($findEnd1->rowCount() >0){
            echo 'query yek araye barmigardanad';
        }else{
            echo 'query hich arayeie barnemigardanad';
        }*/



        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "WFM_FlowRows";
        $daObj->execute($pdo);

        return true;
    }

    function AddFlowRows($StepID , $pdo = null) {

        PdoDataAccess::runquery("update WFM_FlowRows set IsLastRow='NO' "
            . " where FlowID=? AND ObjectID=? AND ObjectID2=?",
            array($this->FlowID, $this->ObjectID, $this->ObjectID2), $pdo);
        /*var_dump($this->FlowID);var_dump($StepID);*/
        //.......... get StepRowID ...................
        $dt = PdoDataAccess::runquery("select StepRowID, StepDesc from WFM_FlowSteps 
			where IsActive='YES' AND FlowID=? AND StepID=?" , array($this->FlowID, $StepID));
        if(count($dt) == 0)
        {
            ExceptionHandler::PushException("خطا در تعریف وضعیت ها");
            return false;
        }
        $this->StepRowID = $dt[0]["StepRowID"];
        $this->StepDesc = $dt[0]["StepDesc"];
        //..............................................

        if (!parent::insert("WFM_FlowRows", $this, $pdo)) {
            return false;
        }

        $this->UpdateSourceStatus($StepID);

        $this->RowID = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "WFM_FlowRows";
        $daObj->execute($pdo);

        return true;
    }

    static function AddOuterFlow($FlowID, $ObjectID, $StepID, $Comment = "", $pdo = null){

        $dt = parent::runquery("select StepRowID from WFM_FlowSteps where FlowID=? AND StepID=?",
            array($FlowID, $StepID));
        /*var_dump(count($dt));*/
        if(count($dt) == 0)
            return false;

        $obj = new self();
        $obj->FlowID = $FlowID;
        $obj->StepRowID = $dt[0]["StepRowID"];
        $obj->ObjectID = $ObjectID;
        $obj->PersonID = $_SESSION["USER"]["PersonID"];
        $obj->ActionType = "DONE";
        $obj->ActionDate = PDONOW;
        $obj->ActionComment = $Comment;
        return $obj->AddFlowRow($StepID, $pdo);
    }

    static function AddOuterFlows($FlowID, $ObjectID, $StepID, $Comment = "", $pdo = null){

        $dt = parent::runquery("select StepRowID from WFM_FlowSteps where FlowID=? AND StepID=?",
            array($FlowID, $StepID));
        /*var_dump(count($dt));*/
        if(count($dt) == 0)
            return false;

        $obj = new self();
        $obj->FlowID = $FlowID;
        $obj->StepRowID = $dt[0]["StepRowID"];
        $obj->ObjectID = $ObjectID;
        $obj->PersonID = $_SESSION["USER"]["PersonID"];
        $obj->ActionType = "DONE";
        $obj->ActionDate = PDONOW;
        $obj->ActionComment = $Comment;
        return $obj->AddFlowRows($StepID, $pdo);
    }
	
	static function EndObjectFlow($ObjectType, $ObjectID, $ObjectID2, $pdo = null){
		
		switch($ObjectType)
		{
			case 'contract' : 
				$EndStepID = CNT_STEPID_CONFIRM; 
				PdoDataAccess::runquery("update CNT_contracts set StatusID=? where ContractID=?", 
					array($EndStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;
			case 'plan' : 
				$EndStepID = 105; 
				PdoDataAccess::runquery("update PLN_plans set StepID=? where PlanID=?", 
					array($EndStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;
			case 'warrenty' : 
				$EndStepID = WAR_STEPID_CONFIRM; 
				PdoDataAccess::runquery("update WAR_requests set StatusID=? where RequestID=?", 
					array($EndStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;
			case 'accdoc' : 
				$EndStepID = ACC_STEPID_CONFIRM; 
				PdoDataAccess::runquery("update ACC_docs set StatusID=? where DocID=?", 
					array($EndStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;
			case 'CORRECT':
			case 'DayOFF':
			case 'OFF':
			case 'DayMISSION':
			case 'MISSION':
			case 'EXTRA':
			case 'CHANGE_SHIFT':
				$EndStepID = ATN_STEPID_CONFIRM; 
				PdoDataAccess::runquery("update ATN_requests set ReqStatus=? where RequestID=?", 
					array($EndStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;
			case "process":
				switch($ObjectID)
				{
					case PROCESS_REGISTRATION :
						PdoDataAccess::runquery("update BSC_persons set IsActive='YES' where PersonID=?",
									array($ObjectID2));
						require_once 'sms.php';
						require_once '../../framework/person/persons.class.php';
						$personObj = new BSC_persons($ObjectID2);
						if($personObj->mobile == "")
						{
							ExceptionHandler::PushException ("ارسال پیامک به دلیل عدم تکمیل شماره پیامک مشتری انجام نگردید");
						}
						else
						{
							$SendError = "";
							$context = "ثبت نام شما در پورتال " . SoftwareName . " تایید گردید";
							$result = ariana2_sendSMS($personObj->mobile, $context, "number", $SendError);
							if(!$result)
								ExceptionHandler::PushException ("ارسال پیامک به دلیل خطای زیر انجام نگردید" . "[" . $SendError . "]");
						}
						return true;
				}
		}
		
		return true;
	}
	
	function EditFlowRow($pdo = null) {
		
		if (parent::update("WFM_FlowRows", $this, " RowID=:did", array(":did" => $this->RowID), $pdo) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->RowID;
		$daObj->TableName = "WFM_FlowRows";
		$daObj->execute($pdo);
		
		return true;
	}
	
	static function StartFlow($FlowID, $ObjectID, $objectID2 = 0){
		
		$obj = new WFM_FlowRows();
		$obj->FlowID = $FlowID;
		$obj->StepRowID = PDONULL;
		$obj->ObjectID = $ObjectID;
		$obj->ObjectID2 = $objectID2;
		$obj->PersonID = $_SESSION["USER"]["PersonID"];		
		$obj->ActionDate = PDONOW;
		$obj->ActionType = "CONFIRM";
		return $obj->AddFlowRow(0);		
	}
	
	static function ReturnStartFlow($FlowID, $ObjectID, $objectID2 = 0, $pdo = null){
		
		$newObj = new WFM_FlowRows();
		$newObj->FlowID = $FlowID;
		$newObj->ObjectID = $ObjectID;
		$newObj->ObjectID2 = $ObjectID2;
		$newObj->PersonID = $_SESSION["USER"]["PersonID"];
		$newObj->ActionType = "REJECT";
		$newObj->ActionDate = PDONOW;
		$newObj->ActionComment = "برگشت ارسال اولیه";
		$newObj->AddFlowRow("0", $pdo);	
		
		//-------------------------------------------------------
		
		$FlowObj = new WFM_flows($FlowID);
		switch($FlowObj->_ObjectType)
		{
			case 'contract' : 
				$EndStepID = CNT_STEPID_RAW; 
				PdoDataAccess::runquery("update CNT_contracts set StatusID=? where ContractID=?", 
					array($EndStepID, $ObjectID), $pdo);
				break;
			case 'warrenty' : 
				$EndStepID = WAR_STEPID_RAW; 
				PdoDataAccess::runquery("update WAR_requests set StatusID=? where RequestID=?", 
					array($EndStepID, $ObjectID), $pdo);
				break;
			case 'accdoc' : 
				$EndStepID = ACC_STEPID_RAW; 
				PdoDataAccess::runquery("update ACC_docs set StatusID=? where DocID=?", 
					array($EndStepID, $ObjectID), $pdo);
				break;
			case 'CORRECT':
			case 'DayOFF':
			case 'OFF':
			case 'DayMISSION':
			case 'MISSION':
			case 'EXTRA':
			case 'CHANGE_SHIFT':
				$EndStepID = ATN_STEPID_RAW; 
				PdoDataAccess::runquery("update ATN_requests set ReqStatus=? where RequestID=?", 
					array($EndStepID, $ObjectID), $pdo);
				break;
		}
		return ExceptionHandler::GetExceptionCount() == 0;
		
	}
	
	static function DeleteAllFlow($FlowID, $ObjectID, $objectID2=0){
		
		switch($FlowID*1)
		{
			case 2 : 
				$RawStepID = 100; 
				PdoDataAccess::runquery("update CNT_contracts set StatusID=? where ContractID=?", array($RawStepID, $ObjectID));
				PdoDataAccess::runquery("delete from WFM_FlowRows where FlowID=? AND ObjectID=?", array($FlowID, $ObjectID));
				return ExceptionHandler::GetExceptionCount() == 0;
			/*case 3 : 
				$RawStepID = 100; 
				PdoDataAccess::runquery("update PLN_plans set StepID=? where PlanID=?", 
					array($RawStepID, $ObjectID), $pdo);
				return ExceptionHandler::GetExceptionCount() == 0;*/
			case 4 : 
				$RawStepID = 100; 
				require_once("../../loan/warrenty/request.class.php");
				$obj = new WAR_requests($ObjectID);
				if($obj->GetAccDoc()*1 > 0)
				{
					ExceptionHandler::PushException("این ضمانت نامه دارای سند بوده و گردش آن قابل حذف نمی باشد");
					return false;
				}
				PdoDataAccess::runquery("delete from WFM_FlowRows where FlowID=? AND ObjectID=?", array($FlowID, $ObjectID));
				PdoDataAccess::runquery("update WAR_requests set StatusID=? where RequestID=?", 
					array($RawStepID, $ObjectID));
				return ExceptionHandler::GetExceptionCount() == 0;
		}
		
		return ExceptionHandler::GetExceptionCount() == 0;
	}
	
	static function IsFlowStarted($FlowID, $ObjectID, $objectID2=0){
		
		$dt = PdoDataAccess::runquery("select * from WFM_FlowRows "
			. "where FlowID=? AND ObjectID=? AND objectID2=?", array($FlowID, $ObjectID, $objectID2));
		
		return (count($dt) > 0);
	}
	
	static function IsFlowEnded($FlowID, $ObjectID, $objectID2=0){
		
		$dt = PdoDataAccess::runquery("select IsEnded from WFM_FlowRows 
			where FlowID=? AND ObjectID=? AND objectID2=? AND ActionType='CONFIRM'
			order by RowID desc", array($FlowID, $ObjectID, $objectID2));
		
		if(count($dt) > 0 && $dt[0][0] == "YES")
			return true;
		return false;
	}
	
	static function GetFlowInfo($FlowID, $ObjectID, $objectID2=0){
		
		$dt = PdoDataAccess::runquery("select * from WFM_FlowRows fr
			join WFM_FlowSteps sp on(sp.FlowID=fr.FlowID AND fr.StepRowID=sp.StepRowID)
			where fr.FlowID=? AND ObjectID=? AND objectID2=? AND fr.IsLastRow='YES'
			", array($FlowID, $ObjectID, $objectID2));
		
		$StepDesc = "";
		if(count($dt) > 0)
		{
			$StepDesc = ($dt[0]["ActionType"] == "REJECT" ? "رد " : "") . $dt[0]["StepDesc"];
			if($dt[0]["StepID"] == "0")
			{
				if($dt[0]["ActionType"] == "REJECT")
					$StepDesc = "برگشت فرم";
				else
					$StepDesc = "ارسال فرم";
			}
			$JustStarted = $dt[0]["StepID"] == "0" && $dt[0]["ActionType"] == "CONFIRM" ? true : false;
			
			$SendEnable = false;
			if($dt[0]["ActionType"] == "REJECT" && $dt[0]["StepID"] == "1")
				$SendEnable = true;
			if($dt[0]["ActionType"] == "REJECT" && $dt[0]["StepID"] == "0")
				$SendEnable = true;
		}
		else
		{
			$StepDesc = "خام";
			$JustStarted = false;
			$SendEnable = true;
		}
		
		return array(
			"StepRowID" => count($dt) > 0 ? $dt[0]["StepRowID"] : 0,
			"IsStarted" => count($dt) > 0 && $JustStarted ? true : false,
			"ActionType" => count($dt) > 0 ? $dt[0]["ActionType"] : "",
			"ActionComment" => count($dt) > 0 ? $dt[0]["ActionComment"] : "" ,
			"IsEnded" => count($dt) > 0 && $dt[0]["IsEnded"] == "YES" ? true : false,
			"SendEnable" => $SendEnable,
			"JustStarted" => $JustStarted ,
			"StepDesc" => $StepDesc
		);
	}
	
	static function GetlatestFlowRow($FlowID, $ObjectID, $objectID2=0){
		
		$dt = PdoDataAccess::runquery("select * from WFM_FlowRows fr
			where fr.FlowID=? AND ObjectID=? AND objectID2=? AND fr.IsLastRow='YES'
			", array($FlowID, $ObjectID, $objectID2));
		return count($dt) == 0 ? null : new WFM_FlowRows($dt[0]["RowID"]);
	}
		
	static function SelectReceivedForms($where="", $params = array()){
		
		$dt = PdoDataAccess::runquery("select FlowID,StepID 
			from WFM_FlowSteps s 
			left join BSC_persons p using(PostID)
			where s.IsActive='YES' AND if(s.PersonID>0,s.PersonID=:pid,p.PersonID=:pid)",
				array(":pid" => $_SESSION["USER"]["PersonID"]));
		if(count($dt) == 0)
			return array();

		$where .= " AND fr.IsEnded='NO' AND (";
		foreach($dt as $row)
		{
			$preStep = $row["StepID"]*1-1;
			$nextStep = $row["StepID"]*1+1;

			$where .= "(fr.FlowID=" . $row["FlowID"] .
				" AND fs.StepID" . "=" . $preStep . 
				" AND ActionType='CONFIRM') OR (fr.FlowID=" . $row["FlowID"] . 
				" AND fs.StepID=" . $nextStep . " AND ActionType='REJECT') OR";
		}
		$where = substr($where, 0, strlen($where)-2) . ")";
		//--------------------------------------------------------
		$query = "select fr.*,f.FlowDesc, 
						b.InfoDesc ObjectTypeDesc,
						ifnull(fr.StepDesc,'ارسال اولیه') StepDesc,
						if(p.IsReal='YES',concat(p.fname, ' ',p.lname),p.CompanyName) fullname,
						b.param1 url,
						b.param2 parameter
					from WFM_FlowRows fr
					join WFM_flows f using(FlowID)
					join BaseInfo b on(b.TypeID=11 AND b.InfoID=f.ObjectType)
					left join WFM_FlowSteps fs on(fr.StepRowID=fs.StepRowID)
					join BSC_persons p on(fr.PersonID=p.PersonID)

					where fr.IsLastRow='YES' " . $where . dataReader::makeOrder();
		return PdoDataAccess::runquery_fetchMode($query, $params);
	}
	
}

class WFM_FlowStepPersons extends OperationClass {

	const TableName = "WFM_FlowStepPersons";
	const TableKey = "RowID"; 
	
	public $RowID;
	public $StepRowID;
	public $PersonID;
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return parent::runquery_fetchMode("select fp.*, concat_ws(' ',fname,lname,CompanyName) fullname 
			from WFM_FlowStepPersons fp join BSC_persons p using(PersonID)
			where 1=1 " . $where, $whereParams, $pdo);
	}
}


?>