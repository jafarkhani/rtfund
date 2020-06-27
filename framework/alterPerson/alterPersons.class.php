<?php
//---------------------------
// programmer:	Mokhtari
// create Date: 98.06
//---------------------------

/*require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';*/
		
class BSC_AlterPersons extends PdoDataAccess{
    const TableName = "BSC_AlterPersons";
    const TableKey = "AlterPersonID";
	public $AlterPersonID;
    public $NationalID;
	public $fullname;
	public $mobile;
    public $educationDeg;
	public $sex;
    public $BirthDate;
	public $WorkExp;
	public $SpecAchieve;
	public $assistPart;
	public $readyDate;
	public $reqWage;
	public $habitRange;
	public $result;
	public $fillDate;
	public $WorkExpPlace;
	public $marital;


	function __construct($AlterPersonID = "") {
	    $this->DT_BirthDate = DataMember::CreateDMA(DataMember::DT_DATE);
	    $this->DT_readyDate = DataMember::CreateDMA(DataMember::DT_DATE);
	    $this->DT_fillDate = DataMember::CreateDMA(DataMember::DT_DATE);
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode(" 
			select * from BSC_AlterPersons	where " . $where, $param);
	}

	function AddPerson(){

		if(!empty($this->NationalID))
		{
			$dt = PdoDataAccess::runquery("select * 
				from BSC_AlterPersons where NationalID=?", array($this->NationalID));
			if(count($dt) > 0)
			{
				ExceptionHandler::PushException("کدملی وارد شده تکراری است");
				return false;
			}
		}
		
	 	if(!parent::insert("BSC_AlterPersons",$this))
			return false;
		$this->AlterPersonID = parent::InsertID();
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->AlterPersonID;
		$daObj->TableName = "BSC_AlterPersons";
		$daObj->execute();
		
		return true;
	}
	
	function EditPerson(){
		if($this->NationalID != "")
		{
			$dt = PdoDataAccess::runquery("select * 
				from BSC_AlterPersons where AlterPersonID<>? AND NationalID=?", array($this->AlterPersonID, $this->NationalID));
			if(count($dt) > 0)
			{
				ExceptionHandler::PushException("کدملی وارد شده تکراری است");
				return false;
			}
		}		
		
	 	if( parent::update("BSC_AlterPersons",$this," AlterPersonID=:l", array(":l" => $this->AlterPersonID)) === false )
	 		return false;
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->AlterPersonID;
		$daObj->TableName = "BSC_AlterPersons";
		$daObj->execute();
		
	 	return true;
    }
	
	static function DeletePerson($PersonID){
		
		PdoDataAccess::runquery("delete from BSC_AlterPersons where AlterPersonID=?", array($PersonID));

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $PersonID;
		$daObj->TableName = "BSC_AlterPersons";
		$daObj->execute();
	 	return true;
	}

}

?>
