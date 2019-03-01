<?php

//-----------------------------
//	Programmer	: Jafarkhani
//	Date		: 94.08
//-----------------------------

class WFM_forms extends OperationClass {

    const TableName = "WFM_forms";
    const TableKey = "FormID";
	
	const TplItemSeperator = "#";

	public $FormID;
    public $FormTitle;
    public $FormContent;
    public $IsActive;
	public $FlowID;
	public $IsStaff;
	public $IsCustomer;
	public $IsShareholder;
	public $IsSupporter;
	public $IsExpert;
	public $IsAgent;
	public $SmsSend;
	public $SendOnce;
	
    public function Remove($pdo = null){
        $res = parent::runquery("select count(*) from WFM_requests where FormID = ? limit 1",
				array($this->FormID),$pdo);
        if ($res[0][0] > 0)
		{
			parent::runquery("update " . static::TableName . " 
				set IsActive='NO' where FormID=?", array($this->FormID));
			return ExceptionHandler::GetExceptionCount() == 0;
        }
		
		parent::runquery("delete from WFM_FormAccess join WFM_FormItems using(FormItemID) where FormID=?", array($this->FormID), $pdo);
		parent::runquery("delete from WFM_FormItems where FormID=?", array($this->FormID), $pdo);
		parent::runquery("delete from WFM_FormPersons where FormID=?", array($this->FormID), $pdo);
  		
        return parent::Remove($pdo);
    }    
    
	static function CorrectFormContentItems($content) {
		
        $contentsArr = explode(self::TplItemSeperator, $content);
        $CorrectContent = '';
        if (substr($content, 0, 3) === self::TplItemSeperator) {
            $contentsArr = array_merge(array(''), $contentsArr);
        }

        for ($i = 0; $i < count($contentsArr); $i++) {
            $ArrCell = $contentsArr[$i];
            if ($i % 2 == 0) {
                $CorrectContent .= $ArrCell;
            } else {
                $arr = explode('--', $ArrCell);
                $CorrectContent .= self::TplItemSeperator . $arr[0] . self::TplItemSeperator;
            }
        }
        return $CorrectContent;
    }
}

class WFM_FormGroups extends OperationClass {

	const TableName = "WFM_FormGroups";
	const TableKey = "GroupID"; 
	
	public $GroupID;
	public $FormID;
	public $GroupDesc;
	public $ordering;
	
	function Remove($pdo = null) {
		
		$dt = PdoDataAccess::runquery("select FormID from WFM_RequestItems join WFM_FormItems using(FormItemID)"
			. " where GroupID=?", array($this->GroupID), $pdo);
		if(count($dt) > 0)
		{ 
			ExceptionHandler::PushException("این گروه در فرمها تکمیل شده است و قادر به حذف آن نمی باشید");
			return false;
		}		
		
		PdoDataAccess::runquery("delete from WFM_FormItems where GroupID=?",
			array($this->GroupID), $pdo);
		
		return parent::Remove($pdo);
	}
}

class WFM_FormItems extends OperationClass {
    
    const TableName = "WFM_FormItems";
    const TableKey = "FormItemID";

    public $FormItemID; 
	public $FormID;
	public $GroupID;
    public $ItemName;
    public $ItemType;
	public $FieldName;
	public $ComboValues;
	public $ordering;
	public $IsActive;
    
	static function Get($where = "", $params = array())
	{
		if(!isset($params[":StepRowID"]))
			$params[":StepRowID"] = -1;
		return PdoDataAccess::runquery_fetchMode("  
			select fi.* , if(fa.FormItemID is null, 'NO', 'YES') access,
				fg.GroupDesc,
				f2.ItemType DisplayType,f2.FieldName as DisplayField,f2.ItemName as DisplayDesc
			from WFM_FormItems fi 
			left join WFM_FormGroups fg using(GroupID)
			left join WFM_FormAccess fa on(fi.FormItemID=fa.FormItemID AND fa.StepRowID=:StepRowID)
			left join WFM_FormItems f2 on(f2.FormID=0 AND f2.FormItemID=fi.FieldName)
			where 1=1 " . $where, $params);
	}
	
    public function Remove($pdo = null){
        
		$dt = PdoDataAccess::runquery("select FormID from WFM_RequestItems join WFM_FormItems using(FormItemID)"
			. " where FormItemID=?", array($this->FormItemID), $pdo);
		if(count($dt) > 0)
		{ 
			$this->IsActive = "NO";
			return $this->Edit($pdo);
		}		
		
		return parent::Remove($pdo);
    }    
}

class WFM_FormPersons extends OperationClass {

	const TableName = "WFM_FormPersons";
	const TableKey = "RowID"; 
	
	public $RowID;
	public $FormID;
	public $PersonID;
	
	static function Get($where = '', $whereParams = array()) {
		
		return parent::runquery_fetchMode("select fp.*, concat_ws(' ',fname,lname,CompanyName) fullname 
			from WFM_FormPersons fp join BSC_persons p using(PersonID)
			where 1=1 " . $where, $whereParams);
	}
}

class WFM_FormAccess extends OperationClass {

	const TableName = "WFM_FormAccess";
    const TableKey = "AccessID";
	
	public $AccessID;
    public $FormItemID;
    public $StepRowID;
}

class WFM_FormGridColumns extends OperationClass {

	const TableName = "WFM_FormGridColumns";
	const TableKey = "ColumnID"; 
	
	public $ColumnID;
	public $FormItemID;
	public $ordering;
	public $ItemName;
	public $ItemType;
	public $EditorProperties;
	public $properties;
	public $ComboValues;
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		$query = "select c.* from WFM_FormGridColumns c join WFM_FormItems using(FormItemID)"
				. " where 1=1" . $where;
		return PdoDataAccess::runquery_fetchMode($query, $whereParams, $pdo);
	}
	
}
//..........................................................

class WFM_requests extends OperationClass {

    const TableName = "WFM_requests";
    const TableKey = "RequestID";

    public $RequestID;
    public $FormID;
    public $PersonID;
    public $RegDate;
	public $ReqContent;

	public $_SmsSend;
	public $_FlowID;
	public $_FormTitle;
	public $_PersonName;
    
    public function __construct($id = ""){
        
        if ($id != ''){
            parent::FillObject($this, "
					select r.* ,  f.FormTitle as _FormTitle,
						concat_ws(' ',fname,lname,CompanyName) _PersonName,
						f.FlowID _FlowID,
						f.SmsSend _SmsSend
                    from WFM_requests r
                    left join WFM_forms f using(FormID) 
					left join BSC_persons using(PersonID)
					where r.RequestID = :id", array(":id" => $id));
        }
    }

    public static function Get($where = '', $whereParams = array(), $order = "") {
		
        return parent::runquery_fetchMode("
			select r.RequestID,
				r.FormID,
				f.FlowID,
				r.PersonID,
				r.RegDate,
				f.FormTitle,
				concat_ws(' ',fname,lname,CompanyName) fullname,
				b.param4
			
			from WFM_requests r
			join WFM_forms f using(FormID) 
			join BSC_persons using(PersonID)
			join BaseInfo b on(b.TypeID=11 AND b.InfoID=5)
			
			where 1=1 " . $where . $order, $whereParams);
    }

	public static function GlobalInfoRecord($PersonID, $RequestID = 0){
		
		$returnDT = parent::runquery("
			select 
				concat_ws(' ',fname,lname,CompanyName) fullname,
				p.CompanyName,
				p.NationalID,
				p.EconomicID,
				p.RegNo,
				p.RegDate CompanyRegDate,
				p.RegPlace,
				b1.InfoDesc CompanyType,
				p.IsGovermental,
				d.DomainDesc,
				p.PhoneNo,
				p.mobile,
				b2.InfoDesc CityDesc,
				p.WebSite,
				p.address,
				p.email,
				p.AccountNo,
				p.fname,
				p.lname,
				p.FatherName,
				p.ShNo
			
			from BSC_persons p
			left join BaseInfo b1 on(b1.typeID=14 and b1.InfoID=CompanyType)
			left join BSC_ActDomain d using(DomainID)
			left join BaseInfo b2 on(b2.typeID=15 and b2.InfoID=p.CityID)
			
			where p.PersonID=:p", array(":p" => $PersonID));
		$returnDT = $returnDT[0];
		
		//-------------- request info ---------------------
		if($RequestID == 0)
		{
			$returnDT["RequestID"] = "";
			$returnDT["FormTitle"] = "";
		}
		else
		{
			$temp = self::Get(" AND RequestID=?", array($RequestID));
			$returnDT = array_merge($returnDT, $temp->fetch());
		}
		
		//--------------- deposite info ---------------------
		require_once '../../accounting/docs/doc.class.php';
		$amount = ACC_docs::GetPureRemainOfSaving($PersonID, BRANCH_UM);
		$returnDT["UmSavingAmount"] = $amount;
		$amount = ACC_docs::GetPureRemainOfSaving($PersonID, BRANCH_PARK);
		$returnDT["ParkSavingAmount"] = $amount;
		
		return $returnDT;
	}			
}

class WFM_RequestItems extends OperationClass {

    const TableName = "WFM_RequestItems";
    const TableKey = "ReqItemID";

    public $ReqItemID;
    public $RequestID;
    public $FormItemID;
    public $ItemValue;

	static public function Get($where="", $param=array()){
		
		$query = "select ri.*,fi.* from WFM_RequestItems ri
			join WFM_requests r using(RequestID)
			join WFM_FormItems fi using(FormItemID)
			where 1=1 " . $where;
		return PdoDataAccess::runquery_fetchMode($query, $param);
	}
	
    public static function RemoveAll($RequestID, $pdo = null) {
		
		$query = "delete ri from WFM_RequestItems ri
			join WFM_FormItems fi using(FormItemID)
			where ItemType<>'grid' AND RequestID=:RequestID " ;
		
        return parent::runquery($query,array(":RequestID" => $RequestID), $pdo);
	}
}

?>
