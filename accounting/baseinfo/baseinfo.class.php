<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

class ACC_cycles extends OperationClass {

	const TableName = "ACC_cycles";
	const TableKey = "CycleID";     
	
	public $CycleID;
	public $CycleDesc;
	public $CycleYear;
	public $IsClosed;
	public $ShortDepositPercent;
	public $LongDepositPercent;
	
	static function IsClosed($CycleID = ""){
		
		if(!isset($_SESSION["accounting"]))
			return true;
		
		if($CycleID == "")
			$CycleID = $_SESSION["accounting"]["CycleID"];
		
		$dt = PdoDataAccess::runquery("select * from ACC_cycles where CycleID=?", array($CycleID));
		if($dt[0]["IsClosed"] == "YES")
			return true;
		
		return false;
	}
	
}

class ACC_blocks extends PdoDataAccess{
	
    public $BlockID;
	public $LevelID;
	public $BlockCode;
	public $BlockDesc;
	public $essence;
	public $GroupID;
	public $MainCostID;

    function __construct($BlockID = "")
    {
		if($BlockID != "")
			parent::FillObject($this, "select * from ACC_blocks where BlockID=?", array($BlockID));
    }

    static function GetAll($where = "",$whereParam = array())
    {
	    $query = "select b.* , concat('[',c1.CostCode,']',concat_ws('-',b11.blockDesc,b12.blockDesc,b13.blockDesc,b14.blockDesc)) MainCostCode
					from ACC_blocks b
					left join ACC_CostCodes c1 on(c1.CostID=b.MainCostID)
					left join ACC_blocks b11 on(c1.level1=b11.blockID)
					left join ACC_blocks b12 on(c1.level2=b12.blockID)
					left join ACC_blocks b13 on(c1.level3=b13.blockID)
					left join ACC_blocks b14 on(c1.level4=b14.blockID)
					
				 ";
	    $query .= ($where != "") ? " where " . $where : "";
	    return parent::runquery_fetchMode($query, $whereParam);
    }

    function AddBlock()
    {
		if($this->BlockID == "")
			unset($this->BlockID);
		
	    if( parent::insert("ACC_blocks", $this) === false )
		    return false;

	    $this->BlockID = parent::InsertID();

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->BlockID;
		$daObj->TableName = "ACC_blocks";
		$daObj->execute();
		return true;	
    }

    function EditBlock()
    {
	    $whereParams = array();
	    $whereParams[":kid"] = $this->BlockID;

	    if( parent::update("ACC_blocks",$this," BlockID=:kid", $whereParams) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->BlockID;
		$daObj->TableName = "ACC_blocks";
		$daObj->execute();
		return true;	
    }

    static function RemoveBlock($BlockID)
    {
	    $result = parent::delete("ACC_blocks", "BlockID=:kid ",
		    array(":kid" => $BlockID));

	    if($result === false)
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $BlockID;
		$daObj->TableName = "ACC_blocks";
		$daObj->execute();
		return true;	
    }
}

class ACC_CostCodes extends PdoDataAccess {

    public $CostID;
    public $level1;
    public $level2;
    public $level3;
	public $level4;
    public $IsActive;
    public $CostCode;
	public $TafsiliType1;
	public $TafsiliType2;
	public $TafsiliType3;
	
	public $param1;
	public $param2;
	public $param3;
	
	public $ObjectType1;
	public $ObjectType2;
	public $ObjectType3;
		
	public $IsBlockable;
	public $IsCheque;

    function __construct($CstID = '') {

        if ($CstID == '')
            return;
        parent::FillObject($this, "select * from ACC_CostCodes where CostID=:CstId", array(':CstId' => $CstID));
    }

    function InsertCost($pdo = null) {

        if ($pdo == null) {
            $db = parent::getPdoObject();
            $db->beginTransaction();
        } else {
            $db = $pdo;
        }
		
        $res = parent::insert("ACC_CostCodes", $this, $db);
        if ($res === false) {
			
			if ($pdo == null)
				$db->rollBack();
            return false;
        }

        $this->CostID = parent::InsertID($db);
		
        $query = "update ACC_CostCodes c 
			left join ACC_blocks b1 on(b1.levelID=1 AND b1.blockID=c.level1)
			left join ACC_blocks b2 on(b2.levelID=2 AND b2.blockID=c.level2)
			left join ACC_blocks b3 on(b3.levelID=3 AND b3.blockID=c.level3)
			left join ACC_blocks b4 on(b4.levelID=4 AND b4.blockID=c.level4)
			set c.CostCode=concat_ws('', b1.blockCode,b2.BlockCode,b3.BlockCode,b4.BlockCode)
			where CostID=?";
        $res = parent::runquery($query, array($this->CostID), $db);
        if ($res === false) {
            if ($pdo == null)
				$db->rollBack();
            return false;
        }
		
		$dt = PdoDataAccess::runquery("select * from ACC_CostCodes c1 
			join ACC_CostCodes c2 on(c2.IsActive='YES' AND c1.CostID<>c2.CostID AND c1.CostCode=c2.CostCode)
			where c1.CostID=? ", array($this->CostID), $db);
		
		if (count($dt) > 0) {
			
            if ($pdo == null)
				$db->rollBack();
			parent::PushException("کد حساب تکراری است");
            return false;
        }
		
        $auditObj = new DataAudit();
        $auditObj->ActionType = DataAudit::Action_add;
        $auditObj->MainObjectID = $this->CostID;
        $auditObj->TableName = "ACC_CostCodes";
        $auditObj->execute($db);

        if ($pdo == null)
            $db->commit();

        return true;
    }

	function UpdateCost($pdo = null) {

        if ($pdo == null) {
            $db = parent::getPdoObject();
            $db->beginTransaction();
        } else {
            $db = $pdo;
        }
				
		$dt = PdoDataAccess::runquery("select c2.* from ACC_CostCodes c1 
			join ACC_CostCodes c2 on(c2.IsActive='YES' AND c1.CostID<>c2.CostID AND c1.CostCode=c2.CostCode)
			where c1.CostID=?", array($this->CostID), $db);
		if (count($dt) > 0) {
			
            if ($pdo == null)
				$db->rollBack();
			parent::PushException("کد حساب تکراری است");
            return false;
        }
		
        $res = parent::update("ACC_CostCodes", $this,"CostID=:c", array(":c" => $this->CostID), $db);

        if ($res === false) {
			
			if ($pdo == null)
				$db->rollBack();
            return false;
        }

        $query = "update ACC_CostCodes c 
			left join ACC_blocks b1 on(b1.levelID=1 AND b1.blockID=c.level1)
			left join ACC_blocks b2 on(b2.levelID=2 AND b2.blockID=c.level2)
			left join ACC_blocks b3 on(b3.levelID=3 AND b3.blockID=c.level3)
			left join ACC_blocks b4 on(b4.levelID=4 AND b4.blockID=c.level4)
			set c.CostCode=concat_ws('', b1.blockCode,b2.BlockCode,b3.BlockCode,b4.BlockCode)
			where CostID=?";
        $res = parent::runquery($query, array($this->CostID), $db);
        if ($res === false) {
            if ($pdo == null)
				$db->rollBack();
            return false;
        }
		
        $auditObj = new DataAudit();
        $auditObj->ActionType = DataAudit::Action_update;
        $auditObj->MainObjectID = $this->CostID;
        $auditObj->TableName = "ACC_CostCodes";
        $auditObj->execute($db);

        if ($pdo == null)
            $db->commit();

        return true;
    }
	
	function ActiveCode(){
		
		$this->IsActive = "YES";
		return $this->UpdateCost();		
	}
	
    function DeleteCost($pdo = null) {

        $res = parent::delete("ACC_CostCodes", 'CostID=:CstId', array(':CstId' => $this->CostID),$pdo);

        if ($res === false) {
			parent::PopAllExceptions();
            parent::runquery("update ACC_CostCodes set IsActive='NO' where CostID=:CstId", 
					array(':CstId' => $this->CostID), $pdo);
            return false;
        }

        $auditObj = new DataAudit();
        $auditObj->ActionType = DataAudit::Action_delete;
        $auditObj->MainObjectID = $this->CostID;
        $auditObj->TableName = "ACC_CostCodes";
        $auditObj->execute($pdo);

        return true;
    }

    static function SelectCost($where = '', $param = array()) {

        $query = "select cc.*,
					b0.BlockDesc LevelTitle0,
					b1.BlockDesc LevelTitle1,
					b2.BlockDesc LevelTitle2,
                    b3.BlockDesc LevelTitle3,
					b4.BlockDesc LevelTitle4,
                    concat_ws('-',b1.blockdesc,b2.blockdesc,b3.blockdesc,b4.blockdesc) as CostDesc,
					bf1.InfoDesc TafsiliTypeDesc1,
					bf2.InfoDesc TafsiliTypeDesc2,
					bf3.InfoDesc TafsiliTypeDesc3,
					
					p1.paramDesc ParamDesc1,
					p2.paramDesc ParamDesc2,
					p3.paramDesc ParamDesc3,
					
					p1.paramID ParamID1,
					p2.paramID ParamID2,
					p3.paramID ParamID3,
					
					p1.paramType ParamType1,
					p2.paramType ParamType2,
					p3.paramType ParamType3
										
                    from ACC_CostCodes as cc
					left join ACC_blocks b1 on(b1.BlockID=cc.Level1)
					left join ACC_blocks b0 on(b1.GroupID=b0.BlockID)
                    left join ACC_blocks b2 on(b2.BlockID=cc.Level2)
                    left join ACC_blocks b3 on(b3.BlockID=cc.Level3)
					left join ACC_blocks b4 on(b4.BlockID=cc.Level4)
					left join BaseInfo bf1 on(bf1.TypeID=2 AND cc.TafsiliType1=bf1.InfoID)
					left join BaseInfo bf2 on(bf2.TypeID=2 AND cc.TafsiliType2=bf2.InfoID) 
					left join BaseInfo bf3 on(bf3.TypeID=2 AND cc.TafsiliType3=bf3.InfoID)
										
					left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
					left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
					left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)

					";
        if ($where != '') 
            $query .= ' where ' . $where;

        return parent::runquery_fetchMode($query, $param);
    }

}

class ACC_CostCodeParams extends OperationClass
{
	const TableName = "ACC_CostCodeParams";
	const TableKey = "ParamID";
	
	public $ParamID;
	public $ParamDesc;
	public $ParamType;	
	public $SrcTable;		
	public $SrcValueField;
	public $SrcDisplayField;
	public $SrcWhere;
}
 
class ACC_CostCodeParamItems extends OperationClass
{
	const TableName = "ACC_CostCodeParamItems";
	const TableKey = "ItemID";

	public $ItemID;
	public $ParamID;
	public $ParamValue;
	public $f1;	
	public $f2;	
}

class ACC_tafsilis extends PdoDataAccess{
	
	public $TafsiliID;
	public $TafsiliType;
	public $TafsiliCode;
	public $TafsiliDesc;
	public $IsActive;
	public $ObjectID;
	public $ObjectType;
			
	function __construct($TafsiliID = "") {
		
		if($TafsiliID != "")
			PdoDataAccess::FillObject ($this, "select * from ACC_tafsilis where TafsiliID=?", array($TafsiliID));
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode(
			"select t.* from ACC_tafsilis t
				left join BSC_persons p on(t.TafsiliType=1 AND t.ObjectID=p.PersonID)

				where " . $where, $param);
	}
	
	function AddTafsili($pdo = null)
	{
	 	if(!parent::insert("ACC_tafsilis",$this, $pdo))
			return false;
		$this->TafsiliID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->TafsiliID;
		$daObj->TableName = "ACC_tafsilis";
		$daObj->execute($pdo);
		return true;
	}
	
	function EditTafsili()
	{
	 	if( parent::update("ACC_tafsilis",$this," TafsiliID=:l", array(":l" => $this->TafsiliID)) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->TafsiliID;
		$daObj->TableName = "ACC_tafsilis";
		$daObj->execute();
	 	return true;
    }
	
	static function DeleteTafsili($TafsiliID){
		
		if( parent::delete("ACC_tafsilis"," TafsiliID=?", array($TafsiliID)) === false )
		{
			parent::runquery("update ACC_tafsilis set IsActive='NO' where TafsiliID=?", array($TafsiliID));
		}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $TafsiliID;
		$daObj->TableName = "ACC_tafsilis";
		$daObj->execute();
	 	return true;
	}
}

class ACC_banks extends PdoDataAccess {

    public $BankID;
    public $BankDesc;
	public $IsActive;

    function __construct($BId = '') {
        parent::__construct();
        if ($BId == '')
            return;
        PdoDataAccess::FillObject($this, "select * from ACC_banks where BankID=?", array($BId));
    }
	
    function InsertBank() {

        $res = parent::insert("ACC_banks", $this);
        if ($res === false) 
            return false;

        $this->BankID = parent::InsertID();

		$obj = new ACC_tafsilis();
		$obj->ObjectID = $this->BankID;
		$obj->TafsiliCode = $this->BankID;
		$obj->TafsiliDesc = $this->BankDesc;
		$obj->TafsiliType = TAFTYPE_BANKS;
		$obj->AddTafsili();
		
        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->BankID;
        $daObj->TableName = "ACC_banks";
        $daObj->execute();

        return true;
    }

    function UpdateBank() {

        $res = parent::update("ACC_banks", $this, 'BankID=:BId', array(':BId' => $this->BankID));
        if ($res === false)
			return false;

		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where ObjectID=? AND TafsiliType="
				. TAFTYPE_BANKS, array($this->BankID));
		if(count($dt) == 0)
		{
			$obj = new ACC_tafsilis();
			$obj->ObjectID = $this->BankID;
			$obj->TafsiliCode = $this->BankID;
			$obj->TafsiliDesc = $this->BankDesc;
			$obj->TafsiliType = TAFTYPE_BANKS;
			$obj->AddTafsili();
		}
		else
		{
			$obj = new ACC_tafsilis($dt[0]["TafsiliID"]);
			$obj->TafsiliCode = $this->BankID;
			$obj->TafsiliDesc = $this->BankDesc;
			$obj->EditTafsili();
		}
		
        if (parent::AffectedRows()) {
            $daObj = new DataAudit();
            $daObj->ActionType = DataAudit::Action_update;
            $daObj->MainObjectID = $this->BankID;
            $daObj->TableName = "ACC_banks";
            $daObj->execute();
        }

        return true;
    }

    function DeleteBank() {

        parent::runquery("update ACC_banks set IsActive='NO' where BankID=?", array($this->BankID));

		if (ExceptionHandler::GetExceptionCount() <> 0)
            return false;
		
		parent::runquery("update ACC_tafsilis set IsActive='NO' where TafsiliType=".TAFTYPE_BANKS." 
			AND ObjectID=?", array($this->BankID));

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $this->BankID;
        $daObj->TableName = "ACC_banks";
        $daObj->execute();

        return true;
    }

    public static function SelectBanks($where = '', $param = array()) {
        $query = "select * from ACC_banks ";
        if ($where != '')
            $query .= ' where ' . $where;
        $query .= ' order by BankID desc ';
        return parent::runquery_fetchMode($query, $param);
    }
}

class ACC_accounts extends PdoDataAccess {

    public $AccountID;
    public $BankID;
    public $AccountDesc;
	public $AccountNo;
    public $AccountType;
    public $IsActive;
	public $shaba;

	public $_BankDesc;
	
    function __construct($aid = '') {

        if ($aid == '')
            return;
        return parent::FillObject($this, "select ba.*,BankDesc as _BankDesc
			from ACC_accounts ba 
			join ACC_banks using(BankID) 
			where AccountID=:BAId", array(':BAId' => $aid));
    }

    public static function SelectAccounts($where = '', $param = array()) {

        $query = " select * from ACC_accounts acc ";
        if ($where != '')
            $query .= ' where ' . $where;
        $res = parent::runquery_fetchMode($query, $param);
        return $res;
    }

	public function AccountNoValidity(){
		
		$dt = parent::runquery("select * from ACC_accounts where AccountNo=? AND AccountID<>?", 
			array($this->AccountNo, $this->AccountID));
		
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("کد حساب تکراری است");
			return false;
		}
		
		return true;
	}
	
    function InsertAccount() {

		if(!$this->AccountNoValidity())
			return false;
		
		if (!parent::insert("ACC_accounts", $this))
            return false;

        $this->AccountID = parent::InsertID();

		$obj = new ACC_tafsilis();
		$obj->ObjectID = $this->AccountID;
		$obj->TafsiliCode = $this->AccountNo;
		$obj->TafsiliDesc = $this->AccountDesc;
		$obj->TafsiliType = TAFTYPE_ACCOUNTS;
		$obj->AddTafsili();
		
        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->AccountID;
        $daObj->SubObjectID = $this->BankID;
        $daObj->TableName = "ACC_accounts";
        $daObj->execute();

        return true;
    }

    function UpdateAccount() {
		
		if(!$this->AccountNoValidity())
			return false;

        if (!parent::update("ACC_accounts", $this, 'AccountID=:ACId', array(':ACId' => $this->AccountID)))
            return false;

		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where ObjectID=? AND TafsiliType=" 
				. TAFTYPE_ACCOUNTS, array($this->AccountID));
		if(count($dt) == 0)
		{
			$obj = new ACC_tafsilis();
			$obj->ObjectID = $this->AccountID;
			$obj->TafsiliCode = $this->AccountNo;
			$obj->TafsiliDesc = $this->AccountDesc;
			$obj->TafsiliType = TAFTYPE_ACCOUNTS;
			$obj->AddTafsili();
		}
		else
		{
			$obj = new ACC_tafsilis($dt[0]["TafsiliID"]);
			$obj->TafsiliCode = $this->AccountNo;
			$obj->TafsiliDesc = $this->AccountDesc;
			$obj->EditTafsili();
		}
		
		
        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->AccountID;
        $daObj->TableName = "ACC_accounts";
        $daObj->execute();

        return true;
    }

    static function DeleteAccount($AccountID) {

		parent::runquery("update ACC_accounts set IsActive='NO' where AccountID=?", array($AccountID));

		if (ExceptionHandler::GetExceptionCount() <> 0)
            return false;
		
		parent::runquery("update ACC_tafsilis set IsActive='NO' where TafsiliType=".TAFTYPE_ACCOUNTS."
			AND ObjectID=?", array($AccountID));

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $AccountID;
        $daObj->TableName = "ACC_accounts";
        $daObj->execute();

        return true;
    }

}

class ACC_ChequeBooks extends PdoDataAccess {

	public $ChequeBookID;
	public $AccountID;
	public $SerialNo;
	public $MinNo;
	public $MaxNo;
	public $IsActive;

	function __construct($ChequeBookID = '') {

		if ($ChequeBookID == '')
			return;
		return parent::FillObject($this, "select * from ACC_ChequeBooks where ChequeBookID=?", 
				array($ChequeBookID));
	}

	function InsertCheque($pdo=null) {
		
		if (parent::insert("ACC_ChequeBooks", $this, $pdo) === false)
			return false;
		
		$this->ChequeBookID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->ChequeBookID;
		$daObj->TableName = "ACC_ChequeBooks";
		$daObj->execute($pdo);
		return true;
	}

	function UpdateCheque($pdo=null) {
		
            $whereParams = array();
            $whereParams[":ChequeBookID"] = $this->ChequeBookID;
            if (parent::update("ACC_ChequeBooks", $this, " ChequeBookID=:ChequeBookID", $whereParams) === false)
                return false;
            $daObj = new DataAudit();
            $daObj->ActionType = DataAudit::Action_update;
            $daObj->MainObjectID = $this->ChequeBookID;
            $daObj->TableName = "ACC_ChequeBooks";
            $daObj->execute($pdo=null);
            return true;
        }

	function DeleteCheque(){
		
		$whereParams = array();
		$whereParams[":ChequeBookID"] = $this->ChequeBookID;
		$result = parent::delete("ACC_ChequeBooks", "ChequeBookID=:ChequeBookID", $whereParams);
		if ($result === false)
			return false;
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $this->ChequeBookID;
		$daObj->TableName = "ACC_ChequeBooks";
		$daObj->execute();
		return true;
	}

	static function SelectCheques($where = '', $param = array()) {

		$query = "select c.* from ACC_ChequeBooks c ";
		if ($where != '')
			$query .= ' where ' . $where;
		$query .= ' order by ChequeBookID desc ';
		
		return parent::runquery($query, $param);
	}

	function IsValidNumbering() {
		
		if ($this->AccountID == '' ||
				$this->MinNo == '' ||
				$this->MaxNo == '') {
			$this->PushException("اطلاعات ناقص می باشد");
			return false;
		}
		$res = parent::runquery("select ChequeBookID
  	                         from ACC_ChequeBooks
  	                        
                             where AccountID =(select AccountID from ACC_accounts where AccountID=:BAId)
                             and (
                                  (MinNo <= :min and MaxNo >= :min)
                                  or
                                  (MinNo <= :max and MaxNo >= :max)
                                  or
                                  (MinNo >= :min and MaxNo <= :max )
                                 )", array(':BAId' => $this->AccountID,
					':min' => $this->MinNo,
					':max' => $this->MaxNo)
		);

		if ($res === false) {
			$this->PushException(self::ERROR_DB);
			return false;
		}
		if (!$res)
			return true;
		else if (count($res) == 1 && $this->ChequeBookID != '' && 
				$res[0]['ChequeBookID'] == $this->ChequeBookID)
			return true;
		return false;
	}
}

class ACC_roles extends OperationClass {

	const TableName = "ACC_roles";
	const TableKey = "RowID";
	
	public $RowID;
	public $RoleID;
	public $PersonID;
	
	static function GetUserRole($PersonID){
		
		$dt = PdoDataAccess::runquery("select * from ACC_roles where PersonID=? order by RoleID desc",
			array($PersonID));
		
		return count($dt) == 0 ? "" : $dt[0]["RoleID"];
	}

}

class ACC_EPays extends OperationClass {

	const TableName = "ACC_EPays";
	const TableKey = "PayID";
	
	public $PayID;
	public $RequestID;
	public $CostID;
	public $amount;
	public $PayDate;
	public $PersonID;
	public $authority;
	public $StatusCode;
	public $error;
	public $PayRefNo;
	public $BankType;
	
}
?>