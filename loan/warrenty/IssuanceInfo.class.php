<?php

//---------------------------
// programmer:	Mahdipour
// create Date:	1400.02
//---------------------------

class manage_IssuanceInfo extends PdoDataAccess {

	public $IID;
	public $BID;
	public $RelatedOrg;
	public $amount;
	public $duration;
	public $SugBail;
	public $comments;
	public $ExtraComments;
	public $ExtraComments2;
	public $KnowledgeBase;
	public $EmpType;
			
	function __construct($IID = "") {

		$this->DT_IID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_BID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_RelatedOrg = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		$this->DT_amount = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_duration = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		$this->DT_SugBail = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		$this->DT_comments = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		$this->DT_ExtraComments = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		
		return;
	}

	static function GetAll($where, $whereParams = array()) {

		$query = " select  bc.* , bi.InfoDesc BailTypeTitle ,  concat_ws(' ',fname,lname,CompanyName)  AS fullname  "
				. "	from LON_BailCondition bc "
				. "	left join BSC_persons p on(p.PersonID=bc.PersonID) "
				. " inner join BaseInfo bi on bi.typeID=74 AND bi.IsActive='YES' AND bi.InfoID = bc.BailType " . $where;
		$temp = parent::runquery($query, $whereParams);
		
		for($i=0;$i<count($temp);$i++){
			
			if( $temp[$i]['param1'] == 1 && $temp[$i]['param2'] == 1 && 
				$temp[$i]['param3'] == 1 && $temp[$i]['param4'] == 1 && $temp[$i]['param5'] == 1 )			
			{
				$temp[$i]['status'] = 'تایید شده' ; 
			}
			else 
				$temp[$i]['status'] = 'رد شده' ; 
			
		}
		
		return $temp ; 
	}

	static function GetAllName($where = "", $whereParams = array()) {

		$query = " SELECT WCID, title as WelfareTitle FROM HTL_WelfareCenters w WHERE w.WCID in (" . manage_access::getValidWelfareCenters() . ") And w.WCID not in (8) " . $where;

		return parent::runquery($query, $whereParams);
	}

	function Add($pdo = "" ) {
		$return = parent::insert("LON_IssuanceInfo", $this);

		if ($return === false)
			return false;
		
		$this->IID = parent::InsertID($pdo);		

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->IID;
		$daObj->TableName = "LON_IssuanceInfo";
		$daObj->execute();
		return true;
	}

	function Edit() {

		$whereParams = array();
		$whereParams[":IID"] = $this->IID;
		

		$result = parent::update("LON_IssuanceInfo", $this, "IID=:IID", $whereParams);

		if ($result === false)
			return false;
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->IID;
		$daObj->TableName = "LON_IssuanceInfo";
		$daObj->execute();

		return true;
	}

	static function Remove($WCID) {

		$result = parent::delete("HTL_WelfareCenters", "WCID=:WCID ", array(":WCID" => $WCID));

		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $WCID;
		$daObj->TableName = "cost_centers";
		$daObj->execute();

		return true;
	}

	static function GetWelfareSuitesInfo($WCID) {

		return PdoDataAccess::runquery(" SELECT st.*,s.number validCount
			from HTL_SuiteTypes st 
			join (
				select STID,count(SID) number from HTL_suites group by STID)s
			on(st.STID=s.STID) 
			where WCID=?
                        AND w.WCID in (" . manage_access::getValidWelfareCenters() . ") ", array($WCID));
	}

}

?>