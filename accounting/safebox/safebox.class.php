<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.12
//-----------------------------

class SFBX_holding extends OperationClass {

	const TableName = "SFBX_holding";
	const TableKey = "holdingID";
	
	public $holdingID;
	public $PersonID;
	public $holdingDate;
	public $holdingDesc;
	public $operationType;

	
	function __construct($id = '') {
		
		$this->DT_holdingDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
	
	static function Get($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode("
		select sh.*, p1.PersonID, p1.CompanyName, concat_ws(' ',p1.CompanyName,p1.fname,p1.lname) AS functorFullname from SFBX_holding sh
				left join BSC_persons p1 using(PersonID)
			where 1=1 " . $where, $param);
	}
static function Gett($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode("
		select * from SFBX_holding
				left join BSC_persons using(PersonID)
			where 1=1 " . $where, $param);
	}

}
?>