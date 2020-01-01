<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	96.05
//---------------------------
class FRW_reports extends OperationClass{
	
	const TableName = "FRW_reports";
	const TableKey = "ReportID";
	
	public $ReportID;
	public $title;
	public $MenuID;
	public $IsManagerDashboard ;
	public $IsShareholderDashboard ;
	public $IsAgentDashboard ;
	public $IsSupporterDashboard ;
	public $IsCustomerDashboard ;
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		$query = "select r.*, concat(MenuPath) reportPath
			from FRW_reports r
			join FRW_menus using(MenuID)
			where 1=1 " . $where;
		return PdoDataAccess::runquery_fetchMode($query, $whereParams, $pdo);
	}
}

class FRW_ReportItems extends OperationClass{
	
	const TableName = "FRW_ReportItems";
	const TableKey = "RowID";
	
	public $RowID;
	public $ReportID;
	public $ElemName;
	public $ElemValue;
}