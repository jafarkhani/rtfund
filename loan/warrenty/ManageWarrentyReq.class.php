<?php

//---------------------------
// programmer:	Mahdipour
// create Date:	1400.02
//---------------------------

class manage_BailCondition extends PdoDataAccess {

	public $BID;
	public $PersonID;
	public $BailType;
	public $subject;
	public $param1;
	public $param2;
	public $param3;
	public $param4;
	public $param5;
	public $LetterNo ; 

	function __construct($BID = "") {

		$this->DT_BID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_PersonID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_BailType = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_subject = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
		$this->DT_param1 = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_param2 = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_param3 = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_param4 = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_param5 = DataMember::CreateDMA(DataMember::Pattern_Num);

		return;
	}

	static function GetAll($where, $whereParams = array()) {

		$query = " select  bc.* , li.RelatedOrg, amount , duration , SugBail , comments , ExtraComments , "
				. "		   KnowledgeBase , EmpType , bi.InfoDesc BailTypeTitle ,  concat_ws(' ',fname,lname,CompanyName)  AS fullname  "
				. "	from LON_BailCondition bc "
				. "	left join BSC_persons p on(p.PersonID=bc.PersonID) "
				. " left join LON_IssuanceInfo li on bc.BID = li.BID "
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

	function Add() {
		$return = parent::insert("LON_BailCondition", $this);

		if ($return === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->BID;
		$daObj->TableName = "LON_BailCondition";
		$daObj->execute();
		return true;
	}

	function Edit() {

		$whereParams = array();
		$whereParams[":BID"] = $this->BID;

		$result = parent::update("LON_BailCondition", $this, "BID=:BID", $whereParams);

		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->BID;
		$daObj->TableName = "LON_BailCondition";
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