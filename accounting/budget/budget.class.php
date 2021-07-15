<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 98.03
//-----------------------------

class ACC_budgets extends OperationClass {

	const TableName = "ACC_budgets";
	const TableKey = "BudgetID";     
	
	public $BudgetID;
	public $ParentID;
	public $BudgetDesc;
	public $IsActive;
}

class ACC_BudgetCostCodes extends OperationClass {

	const TableName = "ACC_BudgetCostCodes";
	const TableKey = "RowID";     
	
	public $RowID;
	public $BudgetID;
	public $CostID;
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select b.*, cc.CostCode,concat_ws('-',b1.blockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) CostDesc
				from ACC_BudgetCostCodes b
				join ACC_CostCodes cc using(CostID)
				left join ACC_blocks b1 on(cc.level1=b1.blockID)
				left join ACC_blocks b2 on(cc.level2=b2.blockID)
				left join ACC_blocks b3 on(cc.level3=b3.blockID)
				left join ACC_blocks b4 on(cc.level4=b4.blockID)
				where 1=1 " . $where, $whereParams, $pdo);
	}
}

class ACC_BudgetAlloc extends OperationClass {

	const TableName = "ACC_BudgetAlloc";
	const TableKey = "AllocID";     
	
	public $AllocID;
	public $BudgetID;
	public $AllocDate;
	public $AllocAmount;
	public $details;
	
	function __construct($id = '') {
		
		$this->DT_AllocDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		return parent::__construct($id);
	}
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select a.*, b.BudgetDesc
				from ACC_BudgetAlloc a
				join ACC_budgets b using(BudgetID)
				where 1=1 " . $where, $whereParams, $pdo);
	}
}

//new added class
class ACC_BudgetApproved extends OperationClass {

    const TableName = "ACC_BudgetApproved";
    const TableKey = "ApprovedID";

    public $ApprovedID;
    public $BudgetID;
    public $CycleID;
    public $approvedAmount;
    public $PrevisionAmount;

    function __construct($id = '') {

        /*$this->DT_AllocDate = DataMember::CreateDMA(DataMember::DT_DATE);*/
        return parent::__construct($id);
    }

    static function Get($where = '', $whereParams = array(), $pdo = null) {

        return PdoDataAccess::runquery_fetchMode("
			select aba.*, ab.BudgetDesc, ac.CycleYear
				from ACC_BudgetApproved aba
				join ACC_budgets ab using(BudgetID)
				join ACC_cycles ac using(CycleID)
				where 1=1 " . $where, $whereParams, $pdo);
    }
}

//new added class
class ACC_BudgetsArchive extends OperationClass {

    const TableName = "ACC_BudgetsArchive";
    const TableKey = "BudgetArchiveID";

    public $BudgetArchiveID;
    public $ParentID;
    public $BudgetDesc;
    public $OperationalDef;
    public $CycleID;
}

?>