<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 98.03
//-----------------------------

class STO_goods extends OperationClass {

	const TableName = "STO_goods";
	const TableKey = "GoodID"; 
 
    public $GoodID;
    public $ParentID;
    public $GoodName;
    public $ScaleID;
    public $depreciateType;
    public $depreciateRatio;
    public $CostID;
    public $IsActive;
    public $IncreasePriceRatio;


    static function GetAll($where = "", $whereParam = array(), $SelActive = 1) {
        $query = "
			select g.*, g3.GoodName p1Desc, g2.GoodName p2Desc , bi.title as GoodScale
                from STO_goods g
				join STO_goods g2 on(g.ParentID=g2.GoodID)
				join STO_goods g3 on(g2.ParentID=g3.GoodID)
				left join BaseInfo  bi on(bi.typeID=94 AND bi.InfoID=g.ScaleID) 
								
			where 1=1 
		";

        if ($SelActive > 0)
            $query .= " AND g.IsActive='YES'  ";
        $query .= ($where != "") ? " AND " . $where : "";
        return parent::runquery_fetchMode($query, $whereParam);
    }
}

class STO_GoodProperties extends OperationClass {

	const TableName = "STO_GoodProperties";
	const TableKey = "PropertyID"; 
	
	public $PropertyID;
	public $GoodID;
	public $PropertyTitle;
	public $PropertyType;
	public $PropertyValues;
	public $IsActive;
	
	function Remove($pdo = null) {
		
		$this->IsActive = "NO";
		return $this->Edit($pdo);		
	}
}


class STO_Assets extends OperationClass {

	const TableName = "STO_assets";
	const TableKey = "AssetID"; 
	
    public $AssetID;
	public $BranchID;
    public $LabelNo;
    public $GoodID;
    public $RegDate;
	public $amount;
    public $details;
    public $BuyDate;
    public $StatusID;

    function __construct($AssetID = "") {
	
		$this->DT_BuyDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
        return parent::__construct($AssetID);
	}
    
    static function Get($where = "", $whereParam = array(), $pdo = null) {
		
        $query = "select a.*, GoodName, bf.InfoDesc StatusDesc, buy.BuyAmount, dep.totalAmount
			from STO_assets a
			left join STO_goods g using(GoodID)
			left join (select AssetID,sum(amount) BuyAmount from STO_AssetFlow where StatusID=".STO_STEPID_RAW." group by AssetID)buy 
				on(buy.AssetID=a.AssetID)
			left join (select AssetID,sum(DepreciationAmount) totalAmount from STO_AssetFlow where StatusID=".STO_STEPID_DEPRICATE." group by AssetID)dep 
				on(dep.AssetID=a.AssetID)
				
			join BaseInfo bf on(TypeID=95 AND bf.InfoID=a.StatusID)
			where 1=1 " . $where;
        
        return parent::runquery_fetchMode($query, $whereParam, $pdo);
    }
}

class STO_AssetProperties extends OperationClass {

	const TableName = "STO_AssetProperties";
	const TableKey = "AssetID"; 
	
    public $AssetID;
    public $PropertyID;
    public $PropertyValue;

}

class STO_AssetFlow extends OperationClass{
	
	const TableName = "STO_AssetFlow";
	const TableKey = "FlowID"; 
	
	public $FlowID;
	public $AssetID;
	public $ActDate;
	public $ActPersonID;
	public $StatusID;
	public $IsUsable;	
	public $amount;	
	public $DepreciationAmount;	
	public $ReceiverPersonID;	
	public $details;	
	public $IsActive;	
	public $IsLock;	
	
	static function Get($where = "", $whereParam = array(), $pdo = null) {
		
        $query = "select af.*, bf.InfoDesc StatusDesc,
				concat_ws(' ', p1.fname,p1.lname,p1.CompanyName) ActFullname,
				concat_ws(' ', p2.fname,p2.lname,p2.CompanyName) ReceiverFullName
			from STO_AssetFlow af
			join BaseInfo bf on(TypeID=95 AND bf.InfoID=af.StatusID)
			join BSC_persons p1 on(p1.PersonID=af.ActPersonID)
			left join BSC_persons p2 on(p2.PersonID=af.ReceiverPersonID)
			where 1=1 " . $where;
        
        return parent::runquery_fetchMode($query, $whereParam, $pdo);
    }
	
	function Remove($pdo = null) {
		$this->IsActive = "NO";
		return $this->Edit($pdo);
	}


	static function AddFlow($AssetID, $StatusID, $IsUsable = true, $amount = 0){
		
		$obj = new STO_AssetFlow();
		$obj->AssetID = $AssetID;
		$obj->ActDate = PDONOW;
		$obj->ActPersonID = $_SESSION["USER"]["PersonID"];
		$obj->amount = $amount;
		$obj->StatusID = $StatusID;
		$obj->IsUsable = $IsUsable;
		$obj->Add();
				
	}
}
?>