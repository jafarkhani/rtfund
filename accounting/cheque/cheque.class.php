<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.07
//-----------------------------

class ACC_IncomeCheques extends OperationClass{
	
	const TableName = "ACC_IncomeCheques";
	const TableKey = "IncomeChequeID";
	
	public $IncomeChequeID;
	public $BranchID;
	public $CostID;
	public $TafsiliType;
	public $TafsiliID;
	public $TafsiliType2;
	public $TafsiliID2;
	public $ChequeNo;
	public $ChequeDate;
	public $ChequeAmount;
	public $ChequeBank;
	public $ChequeBranch;
	public $ChequeAccNo;
	public $ChequeStatus;
	public $description;
	public $EqualizationID;
	public $PayedDate;
	public $LoanRequestID;
	
	function __construct($id = '') {
		
		$this->DT_ChequeDate = DataMember::CreateDMA(DataMember::DT_DATE);		
		$this->DT_PayedDate = DataMember::CreateDMA(DataMember::DT_DATE);		
		parent::__construct($id);
	}
	
	static function Get($where = "", $param = array(), $pdo = null){
		
		$query = "
			SELECT o.*,
				cc.CostCode,
				concat_ws('-', b1.blockDesc, b2.blockDesc, b3.blockDesc, b4.blockDesc) CostDesc,
				b.BankDesc, 
				t.InfoDesc ChequeStatusDesc
			
			FROM ACC_IncomeCheques o

			left join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)

			left join ACC_banks b on(ChequeBank=BankID)
			left join BaseInfo t on(t.TypeID=4 AND t.InfoID=ChequeStatus)
			
			where 1=1 " . $where;
		
		return parent::runquery_fetchMode($query, $param, $pdo);
	}
	
	static function AddToHistory($IncomeChequeID, $status, $DocID = "", $pdo = null, $details = ""){
		
		PdoDataAccess::runquery("
			insert into ACC_ChequeHistory(IncomeChequeID,StatusID,PersonID,ATS,details,DocID)
			values(?,?,?,now(),?,?)", array(
				$IncomeChequeID,
				$status,
				$_SESSION["USER"]["PersonID"],
				$details,
				$DocID
			),$pdo);
	}

	function Add($pdo = null) {
		
		$dt = self::Get(" AND o.ChequeNo=?", array($this->ChequeNo));
		
		if($dt->rowCount() > 0)
		{
			ExceptionHandler::PushException("چک دیگری با این شماره قبلا ثبت شده است");
			return false;
		}
		
		return parent::Add($pdo);
	}
	
	function GetBackPays($pdo = null){
		
		return PdoDataAccess::runquery("
			select * from LON_BackPays 
				join LON_requests using(RequestID)
				left join ACC_tafsilis t2 on(t2.TafsiliType=".TAFSILITYPE_PERSON." AND t2.ObjectID=LoanPersonID)
			where IncomeChequeID=?", 
				array($this->IncomeChequeID), $pdo);
	}
	
	function HasDoc($pdo = null){
		
		$dt = PdoDataAccess::runquery("
			select DocID
			from ACC_ChequeHistory join ACC_docs using(DocID)
			where IncomeChequeID=?", array($this->IncomeChequeID), $pdo);
		return count($dt) > 0;
		
		/*$dt = PdoDataAccess::runquery("
			select DocID
			from ACC_DocItems join ACC_docs using(DocID)
			where SourceType in(" . DOCTYPE_INCOMERCHEQUE . ",".DOCTYPE_EDITINCOMECHEQUE.")
			AND SourceID1=?", array($this->IncomeChequeID));
		return count($dt) > 0;*/
	}
	
	function DeleteDocs($pdo = null){
		
		$dt = PdoDataAccess::runquery("
			delete di FROM ACC_DocItems di join ACC_docs d using(docID)
			join ACC_ChequeHistory using(DocID)
			where IncomeChequeID=? AND d.StatusID = " . ACC_STEPID_RAW, array($this->IncomeChequeID),$pdo);
		
		$dt = PdoDataAccess::runquery("
			delete d FROM ACC_docs d 
			join ACC_ChequeHistory using(DocID)
			where IncomeChequeID=? AND d.StatusID = " . ACC_STEPID_RAW, array($this->IncomeChequeID),$pdo);
		
	}
	
	static function EventTrigger_changeStatus($SourceObjects, $exeEventObj, $pdo){
		
		$PayObj = new LON_BackPays((int)$SourceObjects[2]);
		
		$obj = new ACC_IncomeCheques($PayObj->IncomeChequeID);
		$Status = $SourceObjects[3];
		$PayedDate = isset($SourceObjects[4]) ? $SourceObjects[4] : PDONOW;
		$obj->ChequeStatus = $Status;
		if($Status == INCOMECHEQUE_VOSUL)
			$obj->PayedDate = $PayedDate;
		if(!$obj->Edit($pdo))
			return false;

		if($Status == INCOMECHEQUE_VOSUL && $obj->PayedDate != "")
		{
			$PayObj->PayDate = $obj->PayedDate;
			$result = $PayObj->Edit($pdo);
			if(!$result)
			{
				ExceptionHandler::PushException("خطا در بروزرسانی تاریخ پرداخت مشتری");
				return false;
			}	
		}
		
		ACC_IncomeCheques::AddToHistory($obj->IncomeChequeID, $Status, $exeEventObj->DocObj->DocID, $pdo);
		return true;
	}
}

?>