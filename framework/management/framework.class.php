<?php

//-------------------------
// programmer:	Jafarkhani
// Create Date:	89.11
//-------------------------

class FRW_systems extends PdoDataAccess {

	public $SystemID;
	public $SysName;
	public $SysPath;
	public $SysIcon;
	public $ordering;
	public $IsActive;	

	public function __construct($SystemID = "") {
		if ($SystemID == "")
			return;

		parent::FillObject($this, "select * from FRW_systems where SystemID=?", array($SystemID));
	}

	static function GetAll($where  = "", $param = array()){
	
		return PdoDataAccess::runquery("select * from FRW_systems where 1=1" . $where , $param);
	}
	
	function AddSystem() {

		$result = PdoDataAccess::insert("FRW_systems", $this);
		if ($result === false)
			return false;

		$this->SystemID = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->SystemID;
		$daObj->TableName = "FRW_systems";
		$daObj->execute();
		return true;
	}

	function EditSystem() {
		
		$result = PdoDataAccess::update("FRW_systems", $this, "SystemID=:pid", array(":pid" => $this->SystemID));
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->SystemID;
		$daObj->TableName = "FRW_systems";
		$daObj->execute();
		return true;
	}

} 

class FRW_menus extends OperationClass {

	const TableName = "FRW_menus";
	const TableKey = "MenuID"; 
	
	public $MenuID;
	public $ParentID;
	public $MenuDesc;
	public $IsActive;
	public $ordering;
	public $icon;
	public $MenuPath;
	
	public $IsStaff;
	public $IsCustomer;
	public $IsShareholder;
	public $IsAgent;
	public $IsExpert;
	public $IsSupporter;

	public function __construct($MenuID = "") {
		if ($MenuID == "")
			return;

		parent::FillObject($this, "select * from FRW_menus where MenuID=?", array($MenuID));
	}

	static function GetAllMenus($SystemID) {
		$query = "
			select * from (
				select g.MenuID as GroupID,g.MenuDesc GroupDesc, g.ordering GroupOrder,
					g.icon GroupIcon,
					g.SystemID GroupSystemID, m.*
				from FRW_menus g 
				left join FRW_menus m on(g.MenuID=m.ParentID)
				where g.parentID=0 AND m.MenuID is null AND g.SystemID=:s

				union all

				select g.MenuID as GroupID, g.MenuDesc GroupDesc,g.ordering GroupOrder,
					g.icon GroupIcon,g.SystemID GroupSystemID, m.*
				from FRW_menus m
				join FRW_menus g on(m.ParentID=g.MenuID)
				where m.ParentID>0 AND m.SystemID=:s 
			)t 
			order by GroupOrder,ordering";
		return parent::runquery($query, array(":s" => $SystemID));
	}

	function AddMenu() {
		$result = PdoDataAccess::insert("FRW_menus", $this);
		if ($result === false)
			return false;

		$this->MenuID = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->MenuID;
		$daObj->TableName = "FRW_menus";
		$daObj->execute();
		return true;
	}

	function EditMenu() {
		$result = PdoDataAccess::update("FRW_menus", $this, "MenuID=:mid", array(":mid" => $this->MenuID));
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->MenuID;
		$daObj->TableName = "FRW_menus";
		$daObj->execute();
		return true;
	}

	static public function DeleteMenu($MenuID) {
		$result = PdoDataAccess::delete("FRW_menus", "MenuID=?", array($MenuID));
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $MenuID;
		$daObj->TableName = "FRW_menus";
		$daObj->execute();
		return true;
	}

}

class FRW_access extends PdoDataAccess {

	public $MenuID;
	public $PersonID;
	public $GroupID;
	public $ViewFlag;
	public $AddFlag;
	public $EditFlag;
	public $RemoveFlag;

	static function getAccessMenus() {
		
		return parent::runquery("
			select m.*
			from FRW_menus m 
			left join FRW_AccessGroupList gl on(gl.PersonID=:p)
			join FRW_access a on((a.personID=:p or a.groupID=gl.GroupID) and m.MenuID=a.MenuID)
			where m.MenuPath<>'' AND m.IsActive='YES'
			group by m.MenuID
			order by m.ordering", 
			array(":p" => $_SESSION["USER"]["PersonID"]));
	}
	
	static function getPortalMenus() {
		
		return parent::runquery("
			select g.MenuID GroupID, g.MenuDesc GroupDesc, m.*
			from FRW_menus m 
			join FRW_menus g on(m.ParentID=g.MenuID)
			join FRW_menus g0 on(g.ParentID=g0.MenuID)
			join BSC_persons a on(a.PersonID=" . $_SESSION["USER"]["PersonID"] . " and
				(
					if(g.IsCustomer='YES',a.IsCustomer='YES',0=1) OR
					if(g.IsShareholder='YES',a.IsShareholder='YES',0=1) OR
					if(g.IsStaff='YES',a.IsStaff='YES',0=1)	OR
					if(g.IsAgent='YES',a.IsAgent='YES',0=1)	OR
					if(g.IsExpert='YES',a.IsExpert='YES',0=1)	OR
					if(g.IsSupporter='YES',a.IsSupporter='YES',0=1)
				)
			)
			where m.IsActive='YES' AND g0.MenuID=".MENUID_portal."
			order by g.ordering,m.ordering");
	}

	static function selectAccess($SystemID, $GroupID, $PersonID) {
		$query = "
			select g.MenuID ParentID, g.MenuDesc ParentDesc, m.*, a.ViewFlag,a.AddFlag,a.EditFlag,a.RemoveFlag
			from FRW_menus m 
			join FRW_menus g on(m.ParentID=g.MenuID)
			left join FRW_access a on(a.personID=? and a.GroupID=? and m.MenuID=a.MenuID)
			
			where m.SystemID=? AND m.ParentID>0 
			order by g.ordering,m.ordering";

		return parent::runquery($query, array($PersonID, $GroupID, $SystemID));
	}

	function AddAccess() {

		$result = PdoDataAccess::insert("FRW_access", $this);
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->MenuID;
		$daObj->SubObjectID = $this->PersonID;
		$daObj->TableName = "FRW_access";
		$daObj->execute();
		return true;

		return true;
	}

	static function GetAccess($MenuID){
		
		$obj = new FRW_access();
		PdoDataAccess::FillObject($obj,
		"select max(ViewFlag) ViewFlag,max(AddFlag) AddFlag,max(EditFlag) EditFlag,max(RemoveFlag) RemoveFlag
		from (
			select ViewFlag, AddFlag, EditFlag, RemoveFlag from FRW_access
			where MenuID=:m AND PersonID=:p

		union all

			select max(ViewFlag) ViewFlag,max(AddFlag) AddFlag,max(EditFlag) EditFlag,max(RemoveFlag) RemoveFlag
			from FRW_access
			join FRW_AccessGroupList g using(groupID)
			where MenuID=:m AND g.PersonID=:p
		)t",
		array(":m" => $MenuID, ":p" => $_SESSION["USER"]["PersonID"]));
		
		$obj->ViewFlag =	$obj->ViewFlag == "YES" ? true : false;
		$obj->AddFlag =		$obj->AddFlag == "YES" ? true : false;
		$obj->EditFlag =	$obj->EditFlag == "YES" ? true : false;
		$obj->RemoveFlag =	$obj->RemoveFlag == "YES" ? true : false;
		
		return $obj;
	}

	static function GetAccessBranches(){
		
		$dt = PdoDataAccess::runquery("select BranchID from BSC_BranchAccess where PersonID=?",
			array($_SESSION["USER"]["PersonID"]));
		
		$arr = array();
		foreach($dt as $row)
			$arr[] = $row["BranchID"];
		
		return $arr;				
	}
	
}

//new added
class FRW_indicator extends OperationClass {

    const TableName = "FRW_indicator";
    const TableKey = "indexID";

    public $indexID;
    public $indexType;
    public $indexName;
    public $indexDesc;
    public $measurConcept;
    public $measurPeriod;
    public $measurTimePeriod;
    public $optDirection;

    public function __construct($indexID = "") {
        if ($indexID == "")
            return;

        parent::FillObject($this, "select * from FRW_indicator where indexID=?", array($indexID));
    }
    static function Get($where = '', $whereParams = array()) {

        return parent::runquery_fetchMode("
			select m.*,
				b2.InfoDesc indexTypeDesc, b2.param3 indexConcept, b2.param1, b2.InfoID indexSubType
			from FRW_indicator m
			join BaseInfo b2 on(b2.TypeID=104 AND b2.InfoID=indexType)
			where 1=1 " . $where, $whereParams);
    }

}
//end new added

class FRW_tasks extends PdoDataAccess {

	public $TaskID;
	public $SystemID;
	public $RegPersonID;
	public $CreateDate;
	public $title;
	public $details;
	public $TaskStatus;
	public $DoneDate;
	public $DoneDesc;

	public function __construct($SystemID = "") {
		if ($SystemID == "")
			return;

		parent::FillObject($this, "select * from FRW_systems where SystemID=?", array($SystemID));
	}

	function AddTask() {

		$result = PdoDataAccess::insert("FRW_tasks", $this);
		if ($result === false)
			return false;

		$this->TaskID = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->TaskID;
		$daObj->TableName = "FRW_tasks";
		$daObj->execute();
		return true;
	}

	function EditTask() {
		
		$result = PdoDataAccess::update("FRW_tasks", $this, "TaskID=:pid", array(":pid" => $this->TaskID));
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->TaskID;
		$daObj->TableName = "FRW_tasks";
		$daObj->execute();
		return true;
	}

	static public function DeleteTask($TaskID) {
		
		$result = PdoDataAccess::delete("FRW_tasks", "TaskID=?", array($TaskID));
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $TaskID;
		$daObj->TableName = "FRW_tasks";
		$daObj->execute();
		return true;
	}
}

class FRW_TaskItems extends OperationClass {

	const TableName = "FRW_TaskItems";
	const TableKey = "RowID"; 
 
	public $RowID;
	public $TaskID;
	public $PersonID;
	public $DescDate;
	public $description;

}

class FRW_phonebook extends OperationClass {
	
	const TableName = "FRW_phonebook";
	const TableKey = "RowID"; 

	public $RowID;
	public $PersonID;
	public $fullname;
	public $phone;
	public $mobile;
	public $address;
	public $details;
	public $email;
	public $ActInfo;
	public $IsPublic;
}

class FRW_CalendarEvents extends OperationClass {
	
	const TableName = "FRW_CalendarEvents";
	const TableKey = "EventID"; 

	public $EventID;
	public $PersonID;
	public $EventTitle;
	public $EventDesc;
	public $ColorID;
	public $StartDate;
	public $EndDate;
	public $FromTime;
	public $ToTime;
	public $IsAllDay;
	public $reminder;
	public $IsSeen;
	
	public static function SelectTodayReminders(){
	
		$where = " AND reminder='YES' AND IsSeen='NO' 
			AND StartDate <= :today AND :today <= EndDate AND PersonID=" . $_SESSION["USER"]["PersonID"];
		$res = FRW_CalendarEvents::Get($where, array(":today" => DateModules::shNow('-')));	

		return $res;
	}
}

class FRW_AccessGroups extends OperationClass{

	const TableName = "FRW_AccessGroups";
	const TableKey = "GroupID"; 
	
	public $GroupID;
	public $GroupDesc;
			
}

class FRW_AccessGroupList extends OperationClass{

	const TableName = "FRW_AccessGroupList";
	const TableKey = "GroupID"; 
	
	public $GroupID;
	public $PersonID;
	
	static public function SelectAll($where ="", $param = array()){
		
		return PdoDataAccess::runquery("select l.*, concat_ws(' ',fname,lname,CompanyName) fullname 
			from FRW_AccessGroupList l join BSC_persons using(PersonID)
			where 1=1 " . $where, $param);
	}

	public function Add($pdo = null) {
		
		$result = PdoDataAccess::insert(self::TableName,$this, $pdo );
		if (!$results)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->GroupID;
		$daObj->SubObjectID = $this->PersonID;
		$daObj->TableName = "FRW_AccessGroupList";
		$daObj->execute($pdo);
		return true;
	}
	
	public function Remove($pdo = null) {
		
		$result = PdoDataAccess::delete(self::TableName, "GroupID=? AND PersonID=?", 
			array($this->GroupID, $this->PersonID), $pdo);
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $this->GroupID;
		$daObj->SubObjectID = $this->PersonID;
		$daObj->TableName = "FRW_AccessGroupList";
		$daObj->execute($pdo);
		return true;
	}
}

class FRW_pics extends OperationClass {

    const TableName = "FRW_pics";
    const TableKey = "PicID";

    public $PicID;
    public $SourceType;
    public $FileType;

    function __construct($PicID = '')
    {
        $this->DT_PicID = DataMember::CreateDMA(DataMember::Pattern_Num);
        $this->DT_SourceType = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
        $this->DT_FileType = DataMember::CreateDMA(DataMember::Pattern_EnAlphaNum);

        parent::__construct($PicID);
    }

}

class FRW_news extends OperationClass {

    const TableName = "FRW_news";
    const TableKey = "NewsID";

    public $NewsID;
    public $NewsTitle;
    public $context;
	public $StartDate;
	public $EndDate;
	
	public $IsStaff;
	public $IsCustomer;
	public $IsShareholder;
	public $IsSupporter;
	public $IsExpert;
	public $IsAgent;

    function __construct($PicID = '')
    {
        $this->DT_StartDate = DataMember::CreateDMA(DataMember::Pattern_Date);
        $this->DT_EndDate = DataMember::CreateDMA(DataMember::Pattern_Date);

        parent::__construct($PicID);
    }

	static function ShowNews(){
		
		return parent::runquery_fetchMode("
			select n.*
			from FRW_news n
			join BSC_persons a on(a.PersonID=" . $_SESSION["USER"]["PersonID"] . " and
				(
					if(n.IsCustomer='YES',a.IsCustomer='YES',0=1) OR
					if(n.IsShareholder='YES',a.IsShareholder='YES',0=1) OR
					if(n.IsStaff='YES',a.IsStaff='YES',0=1)	OR
					if(n.IsAgent='YES',a.IsAgent='YES',0=1)	OR
					if(n.IsExpert='YES',a.IsExpert='YES',0=1)	OR
					if(n.IsSupporter='YES',a.IsSupporter='YES',0=1)
				)
			)
			where (n.StartDate is null or substr(now(),1,10)>= n.StartDate ) AND (n.EndDate is null or substr(now(),1,10)<= n.EndDate)");
	}
}
?>
