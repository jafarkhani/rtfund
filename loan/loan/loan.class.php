<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//---------------------------

require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';

class LON_loans extends PdoDataAccess
{
	public $LoanID;
	public $GroupID;
	public $LoanDesc;
	public $MaxAmount;
	public $InstallmentCount;
	public $IntervalType;
	public $PayInterval;
	public $DelayMonths;
	public $ForfeitPercent;
	public $CustomerWage;
	public $BlockID;
	public $IsCustomer;
	public $IsPlan;
	public $IsActive;
	public $_BlockCode;
	public $_SepordeCode;
			
	function __construct($LoanID = "") {
		
		if($LoanID != "")
			PdoDataAccess::FillObject ($this, "select l.*,b.BlockCode _BlockCode,bi.param1 _SepordeCode
				from LON_loans l 
				join BaseInfo bi on(bi.TypeID=1 AND bi.InfoID=l.GroupID)
				left join ACC_blocks b using(BlockID) 
				where LoanID=?", array($LoanID));
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode("select l.*,InfoDesc GroupDesc from LON_loans l
			join BaseInfo bf on(bf.TypeID=1 AND bf.InfoID=l.GroupID)
			where " . $where, $param);
	}
	
	function AddLoan()
	{
	 	if(!parent::insert("LON_loans",$this))
			return false;
		$this->LoanID = parent::InsertID();
				
		/*$blockObj = new ACC_blocks();
		$blockObj->BlockCode = $this->LoanID;
		$blockObj->BlockDesc = $this->LoanDesc;
		$blockObj->LevelID = "2";
		$blockObj->AddBlock();
		
		$this->BlockID = $blockObj->BlockID;
		$this->EditLoan();*/
				
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;	
		$daObj->MainObjectID = $this->LoanID;
		$daObj->TableName = "LON_loans";
		$daObj->execute();
		
		$obj = new ACC_tafsilis();
		$obj->ObjectID = $this->LoanID;
		$obj->TafsiliCode = $this->LoanID;
		$obj->TafsiliDesc = $this->LoanDesc;
		$obj->TafsiliType = TAFSILITYPE_LOAN;
		$obj->AddTafsili();
		
		return true;
	}
	
	function EditLoan()
	{
	 	if( parent::update("LON_loans",$this," LoanID=:l", array(":l" => $this->LoanID)) === false )
	 		return false;

		/*$obj = new LON_loans($this->LoanID);
		$blockObj = new ACC_blocks($obj->BlockID);
		$blockObj->BlockDesc = $this->LoanDesc;
		$blockObj->EditBlock();*/
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->LoanID;
		$daObj->TableName = "LON_loans";
		$daObj->execute();
		
		if($this->IsActive == "NO")
			return true;
		
		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
				. "where ObjectID=? AND TafsiliType=" . TAFSILITYPE_LOAN, array($this->LoanID));
		
		if(count($dt) == 0)
		{
			$obj = new ACC_tafsilis();
			$obj->ObjectID = $this->LoanID;
			$obj->TafsiliCode = $this->LoanID;
			$obj->TafsiliDesc =  $this->LoanDesc;
			$obj->TafsiliType = TAFSILITYPE_LOAN;
			$obj->AddTafsili();
		}
		else
		{
			$obj = new ACC_tafsilis($dt[0]["TafsiliID"]);
			$obj->TafsiliCode = $this->LoanID;
			$obj->TafsiliDesc = $this->LoanDesc;
			$obj->EditTafsili();
		}
		
	 	return true;
    }
	
	static function DeleteLoan($LoanID){
		
		if( parent::delete("LON_loans"," LoanID=?", array($LoanID)) === false )
		{
			$obj = new LON_loans($LoanID);
			$obj->IsActive = "NO";
			return $obj->EditLoan();
		}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $LoanID;
		$daObj->TableName = "LON_loans";
		$daObj->execute();
	 	return true;
	}
}

class LON_LetterTemplates extends OperationClass{
	
	const TableName = "LON_LetterTemplates";
	const TableKey = "TemplateID";
	
	public $TemplateID;
	public $TemplateDesc;
	public $LetterSubject;
	public $LetterContent;
	
	function __construct($id = '') {
		
		parent::__construct($id);
	}
	
}

?>
