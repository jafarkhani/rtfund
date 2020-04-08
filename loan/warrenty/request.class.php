<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 95.06
//---------------------------

require_once DOCUMENT_ROOT . '/office/dms/dms.class.php';

class WAR_requests extends OperationClass
{
	const TableName = "WAR_requests";
	const TableKey = "RequestID";
	
	public $RequestID;
	public $TypeID;
	public $PersonID;
	public $SubjectDesc;
	public $SubjectNO;
	public $organization;
	public $ReqDate;
	public $amount;
	public $StartDate;
	public $EndDate;
	public $CancelDate;
	public $wage;
	public $LetterNo;
	public $LetterDate;
	public $StatusID;
	public $RefRequestID;
	public $version;
	public $IsBlock;
	public $BranchID;
	public $RegisterAmount;
	public $SavePercent;
	public $SepasCode;
	
	public $_fullname;
	public $_TypeDesc;
	
	function __construct($RequestID = "", $pdo = null) {
		
		$this->DT_ReqDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_StartDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_EndDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_LetterDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_CancelDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($RequestID != "")
			PdoDataAccess::FillObject ($this, "
				select r.* , concat_ws(' ',fname,lname,CompanyName) _fullname,bf.InfoDesc _TypeDesc
					from WAR_requests r 
					left join BaseInfo bf on(bf.TypeID=74 AND InfoID=r.TypeID)
					left join BSC_persons using(PersonID)
				where RequestID=?", array($RequestID), $pdo);
	}
	
	static function SelectAll($where = "", $param = array(), $order = ""){
		
		return PdoDataAccess::runquery_fetchMode("
			select r.* , concat_ws(' ',fname,lname,CompanyName) fullname, 
				p.address,
				p.NationalID,
				p.PhoneNo,
				p.mobile,
				p.PostalCode, /*new added*/
				bf.InfoDesc TypeDesc,
				t1.DocID,group_concat(distinct t1.LocalNo) LocalNo, 
				BranchName,
				if(lst.RequestID=r.RequestID, 'YES', 'NO') IsCurrent,
				concat(if(fr.ActionType='REJECT','رد ',''),sp.StepDesc) StepDesc,
				if(sp.StepID=1 AND fr.ActionType='REJECT', 'YES', 'NO') SendEnable,
				fr.ActionType
				
			from WAR_requests r 
				left join BSC_persons p using(PersonID)
				join BSC_branches b using(BranchID)
				left join BaseInfo bf on(bf.TypeID=74 AND InfoID=r.TypeID)
				join WFM_FlowSteps sp on(sp.FlowID=" . FLOWID_WARRENTY . " AND sp.StepID=r.StatusID)
				left join WFM_FlowRows fr on(fr.IsLastRow='YES' AND fr.ObjectID=r.RequestID 
					AND fr.StepRowID=sp.StepRowID AND fr.FlowID=sp.FlowID)
			
				left join (
				select di2.SourceID1,d2.DocID,LocalNo 
					from COM_events e
						join ACC_docs d2 on(e.EventID=d2.EventID) 
						join ACC_DocItems di2 on(d2.DocID=di2.DocID)
					where ComputeFn='Warrenty'
					group by di2.SourceID1
				)t1 on(r.RequestID=t1.SourceID1)
				
				left join (
						select max(RequestID) RequestID,RefRequestID
						from WAR_requests 
						group by RefRequestID
				)lst on(lst.RefRequestID=r.RefRequestID)
			where " . $where . 
			" group by r.RequestID" . $order, $param);
	}
	
	static function ChangeStatus($RequestID, $StatusID, $ActDesc = "", $LogOnly = false, $pdo = null){
	
		if(!$LogOnly)
		{
			$obj = new WAR_requests();
			$obj->RequestID = $RequestID;
			$obj->StatusID = $StatusID;
			if(!$obj->Edit($pdo))
				return false;
		}

		return WFM_FlowRows::AddOuterFlow(FLOWID_WARRENTY, $RequestID, $StatusID, $ActDesc = "", $pdo);
	}
	
	function GetAccDoc($pdo = null){
		
		$dt = PdoDataAccess::runquery("
			select DocID from ACC_DocItems where SourceType=" . DOCTYPE_WARRENTY . " 
			AND SourceID2=?" , array($this->RequestID), $pdo);
		if(count($dt) == 0)
			return 0;
		return $dt[0][0];
	}
	
	static function EventTrigger_cancel($SourceObjects){
		
		$ReqObj = new WAR_requests((int)$SourceObjects[0]);
		$ReqObj->StatusID = WAR_STEPID_CANCEL;
		$ReqObj->CancelDate = DateModules::shamsi_to_miladi($SourceObjects[2]);
		return $ReqObj->Edit();
	}
	
	static function EventTrigger_reduce($SourceObjects){
		
		$ReqObj = new WAR_requests((int)$SourceObjects[0]);
		
		$newObj = new WAR_requests();
		PdoDataAccess::FillObjectByObject($ReqObj, $newObj);
		unset($newObj->RequestID);
		$newObj->StatusID = WAR_STEPID_RAW;
		$newObj->version = "EXTEND";
		$newObj->RefRequestID = $ReqObj->RefRequestID;
		$newObj->StartDate = DateModules::shamsi_to_miladi($SourceObjects[2]);
		$newObj->EndDate = $ReqObj->EndDate;
		$newObj->amount = (int)$SourceObjects[1];
		if(!$newObj->Add())
			return false;
		
		$ReqObj->StatusID = WAR_STEPID_REDUCE;
		$ReqObj->EndDate = DateModules::shamsi_to_miladi($SourceObjects[2]);
		return $ReqObj->Edit();
	}

}

class WAR_costs extends OperationClass
{
	const TableName = "WAR_costs";
	const TableKey = "CostID";
	
	public $CostID;
	public $RequestID;
	public $CostDesc;
	public $CostAmount;
	public $CostCodeID;
	public $CostType;

	public static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		$query = "select c.*,cc.CostCode , 
				concat_ws(' - ',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) CostCodeDesc
			from WAR_costs c
			left join ACC_CostCodes cc on(c.CostCodeID=cc.CostID)
			left join ACC_blocks b1 on(level1=b1.BlockID)
			left join ACC_blocks b2 on(level2=b2.BlockID)
			left join ACC_blocks b3 on(level3=b3.BlockID)
			left join ACC_blocks b4 on(level4=b4.BlockID)
			where 1=1 " . $where;
		
		return PdoDataAccess::runquery_fetchMode($query, $whereParams, $pdo);
	}
}

class WAR_guarantors extends OperationClass{
	
	const TableName = "WAR_guarantors";
	const TableKey = "GuarantorID";
	
	public $GuarantorID;
	public $RequestID;
	public $sex;
	public $fullname;
	public $NationalCode;
	public $father;
	public $ShNo;
	public $ShCity;
	public $BirthDate;
	public $address;
	public $phone;
	public $mobile;
	public $PersonType;
	
	function __construct($id = '') {
		
		$this->DT_BirthDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
}
?>
