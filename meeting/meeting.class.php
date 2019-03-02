<?php
//---------------------------
// create Date: 97.11
//---------------------------

class MTG_MeetingTypePersons extends OperationClass {

	const TableName = "MTG_MeetingTypePersons";
	const TableKey = "RowID"; 
	
	public $RowID;
	public $MeetingType;
	public $PersonID;
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("
			select fp.*, concat_ws(' ',fname,lname,CompanyName) fullname 
			from MTG_MeetingTypePersons fp join BSC_persons p using(PersonID)
			where 1=1 " . $where, $whereParams);
	}
}

class MTG_meetings extends OperationClass {

	const TableName = "MTG_meetings";
	const TableKey = "MeetingID"; 
	
	public $MeetingID;
	public $MeetingNo;
	public $MeetingType;
	public $StatusID;
	public $place;
	public $MeetingDate;
	public $StartTime;
	public $EndTime;
	public $details;
	public $secretary;
	
	public $_MeetingTypeDesc;
	public $_secretaryName;
	
	public function __construct($id = '') {
		
		$this->DT_MeetingDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($id != "")
		{
			parent::FillObject($this, "select m.* , b.InfoDesc _MeetingTypeDesc,
					concat_ws(' ',fname,lname,CompanyName) _secretaryName
				from MTG_meetings m 
				join BaseInfo b on(MeetingType=InfoID and TypeID=".TYPEID_MeetingType.")
				left join BSC_persons p on(p.PersonID=m.secretary)
				where MeetingID=?
				", array($id));
		}
	}
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("
			select m.*, concat_ws(' ',fname,lname,CompanyName) fullname ,
				b1.InfoDesc StatusDesc,
				b2.InfoDesc MeetingTypeDesc
			from MTG_meetings m 
			left join BSC_persons p on(p.PersonID=m.secretary)
			join BaseInfo b1 on(b1.TypeID=".TYPEID_MeetingStatusID." AND b1.InfoID=StatusID)
			join BaseInfo b2 on(b2.TypeID=".TYPEID_MeetingType." AND b2.InfoID=MeetingType)
			where 1=1 " . $where, $whereParams);
	}
	
	function Add($pdo = null) {
		
		$this->MeetingNo = parent::GetLastID("MTG_meetings", "MeetingNo", "MeetingType=?", 
				array($this->MeetingType), $pdo) + 1;
		
		return parent::Add($pdo);
	}
	
	function Remove($pdo = null) {
		
		PdoDataAccess::runquery("delete from MTG_MeetingPersons where MeetingID=?", array($this->MeetingID));
		
		return parent::Remove($pdo);
	}
}

class MTG_MeetingPersons extends OperationClass{
	
	const TableName = "MTG_MeetingPersons";
	const TableKey = "RowID"; 
	
	public $RowID;
	public $MeetingID;
	public $PersonID;
	public $fullname;
	public $AttendType;
	public $IsPresent;
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("
			select mp.*, if(mp.personID=0,mp.fullname,concat_ws(' ',fname,lname,CompanyName)) fullname 
			from MTG_MeetingPersons mp left join BSC_persons p using(PersonID)
			where 1=1 " . $where, $whereParams);
	}
}

class MTG_agendas extends OperationClass{
	
	const TableName = "MTG_agendas";
	const TableKey = "AgendaID"; 
	
	public $AgendaID;
	public $MeetingID;
	public $RefAgendaID;
	public $RecordID;
	public $MeetingType;
	public $title;
	public $PersonRowID;
	public $PersonID;
	public $PresentTime;
	public $IsDone;
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("
			select a.*, 
				case when a.PersonID>0 then concat_ws(' ',p.fname,p.lname,p.CompanyName) 
				else if(mp.PersonID=0,mp.fullname,concat_ws(' ',p2.fname,p2.lname,p2.CompanyName)) end fullname
			from MTG_agendas a 
				left join BSC_persons p using(PersonID)
				left join MTG_MeetingPersons mp on(PersonRowID=mp.RowID)
				left join BSC_persons p2 on(mp.PersonID=p2.PersonID)
				
			where 1=1 " . $where, $whereParams);
	}
}

class MTG_MeetingRecords extends OperationClass{
	
	const TableName = "MTG_MeetingRecords";
	const TableKey = "RecordID"; 
	
	public $RecordID;
	public $MeetingID;
	public $subject;
	public $details;
	public $PersonID;
	public $FollowUpDate;
	public $RecordStatus;
	public $keywords;
	
	public function __construct($id = '') {
		
		$this->DT_FollowUpDate = DataMember::CreateDMA(DataMember::Pattern_Date);
		
		return parent::__construct($id);
	}
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("
			select mr.*, concat_ws(' ',fname,lname,CompanyName) fullname 
			from MTG_MeetingRecords mr left join BSC_persons p using(PersonID)
			where 1=1 " . $where, $whereParams);
	}
}
?>
