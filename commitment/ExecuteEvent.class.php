<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.09
//-----------------------------

require_once DOCUMENT_ROOT . '/accounting/docs/doc.class.php';
require_once DOCUMENT_ROOT . "/commitment/ComputeItems.class.php";
require_once DOCUMENT_ROOT . "/loan/request/request.class.php";
require_once DOCUMENT_ROOT . "/loan/warrenty/request.class.php";

class ExecuteEvent extends PdoDataAccess{
	
	private $pdo; 
	public $EventID;
	public $BranchID;
	public $DocDate;
	public $DocObj = null;
	
	public $EventFunction;
	public $TriggerFunction = "";
	public $AfterTriggerFunction = "";
	public $EventFunctionParams;
	public $Sources;
	public $tafsilis = array();
	public $ComputedItems = array();
	public $ExtraDescription = "";
	
	public $AllRowsAmount = 0;
	
	function __construct($EventID, $BranchID = "") {
	
		$this->EventID = $EventID;
		$this->BranchID = $BranchID == "" ? BRANCH_BASE : $BranchID;
		
		$EventObj = new COM_events($EventID);
		$this->TriggerFunction = $EventObj->TriggerFunction;
		$this->EventFunction = $EventObj->EventFunction != "" ? 
				"EventComputeItems::" . $EventObj->EventFunction : null;
		$this->AfterTriggerFunction = $EventObj->AfterTriggerFunction;		
	}
	
	function RegisterEventDoc($pdo = null){
		
		if($pdo == null)
		{
			$this->pdo = parent::getPdoObject();
			$this->pdo->beginTransaction();
		}
		else
			$this->pdo = &$pdo;

		$eventRows = PdoDataAccess::runquery("
			select * from COM_EventRows er 
				join COM_events e using(EventID) 
				join  ACC_CostCodes cc using(CostID)
			where er.EventID=? AND er.IsActive='YES'
			order by CostType,CostCode", 
			array($this->EventID), $this->pdo);

		if(count($eventRows) == 0)
		{
			ExceptionHandler::PushException("رویداد ".$this->EventID." فاقد ردیف می باشد");
			return false;
		}

		//------------------ run trigger --------------------
		if($this->TriggerFunction != "")
			if(!call_user_func($this->TriggerFunction, $this->Sources, $this, $pdo))
			{
				ExceptionHandler::PushException("خطا در اجرای  Trigger " . $this->TriggerFunction);
				return false;
			} 
		//---------------------------------------------------

		switch($eventRows[0]["EventType"])
		{
            // new added for set description for warrenty event
            case 'RegisterWarrenty':
				$warObj = new WAR_requests($this->Sources[0]);
                $this->ExtraDescription = 'شماره ضمانتنامه [ ' . $this->Sources[0] . ' ] ' . 
					$warObj->_fullname . " ". $this->ExtraDescription;
                break;
            // end new added for set description for warrenty event

			case EVENTTYPE_LoanContract:
			case EVENTTYPE_LoanAllocate:
			case EVENTTYPE_LoanPayment:
			case EVENTTYPE_LoanBackPay:
			case EVENTTYPE_LoanEnd:
				$reqObj = new LON_requests($this->Sources[0]);
				$this->ExtraDescription = 'شماره وام [ ' . $this->Sources[0] . ' ] ' . 
					$reqObj->_LoanPersonFullname . " ". $this->ExtraDescription;
		}
		//---------------------------------------------------
		if(!$this->DocObj) 
		{
			$CycleID = isset($_SESSION["accounting"]) ? 
				$_SESSION["accounting"]["CycleID"] : 
				substr(DateModules::shNow(), 0 , 4);
			
			$this->DocObj = new ACC_docs();
			$this->DocObj->RegDate = PDONOW;
			$this->DocObj->regPersonID = $_SESSION['USER']["PersonID"];
			$this->DocObj->DocDate = empty($this->DocDate) ? PDONOW : $this->DocDate;
			$this->DocObj->CycleID = $CycleID;
			$this->DocObj->BranchID = $this->BranchID;
			$this->DocObj->DocType = DOCTYPE_EXECUTE_EVENT;
			$this->DocObj->EventID = $this->EventID;
			$this->DocObj->description = $this->ExtraDescription;
		}

		//----------------------- add doc items -------------------
		foreach ($eventRows as $eventRow)
		{
			if(!$this->AddDocItem($eventRow))
			{
				if($pdo == null)
					$this->pdo->rollBack();
				return false;
			}
		}
		//------- balance the doc with low prices -----------
		$dt = PdoDataAccess::runquery("select di.*, sum(DebtorAmount) dsum, sum(CreditorAmount) csum
			from ACC_DocItems di where DocID=?", array($this->DocObj->DocID), $pdo);
		if($dt[0]["dsum"] != $dt[0]["csum"] && $dt[0]["dsum"]*1 - $dt[0]["csum"]*1 < 1000)
		{
			$diff = $dt[0]["dsum"]*1 - $dt[0]["csum"]*1;
			$itemObj = new ACC_DocItems();
			PdoDataAccess::FillObjectByArray($itemObj, $dt[0]);
			unset($itemObj->ItemID);
			$itemObj->DebtorAmount = $diff>0 ? 0 : abs($diff);
			$itemObj->CreditorAmount = $diff<0 ? 0 : abs($diff);
			$itemObj->details = "رفع اختلاف حاصل از رند";
			$itemObj->Add($pdo);
		}
		//------------------ run trigger --------------------
		if($this->AfterTriggerFunction != "")
			if(!call_user_func($this->AfterTriggerFunction, $this->Sources, $this, $pdo))
			{
				$this->pdo->rollBack();
				ExceptionHandler::PushException("خطا در اجرای  Trigger " . $this->AfterTriggerFunction);	
				return false;
			}
		//---------------------------------------------------
		
		if($pdo == null)
			$this->pdo->commit();
		return true;
	}
	
	private function AddDocItem($eventRow, $amount = null){
		
		$obj = new ACC_DocItems();
		$obj->DocID = $this->DocObj->DocID;
		$obj->CostID = $eventRow["CostID"];
		$obj->locked = ($obj->CostID == "1001") ? "NO" : "YES";
		
		//------------------ set amounts ------------------------
		if($this->AllRowsAmount*1 > 0)
		{
			if($eventRow["CostType"] == "DEBTOR")
				$amount = isset($_POST["DebtorAmount_" . $eventRow["RowID"]]) ? 
					$_POST["DebtorAmount_" . $eventRow["RowID"]] : $this->AllRowsAmount;
			else
				$amount = isset($_POST["CreditorAmount_" . $eventRow["RowID"]]) ? 
					$_POST["CreditorAmount_" . $eventRow["RowID"]] : $this->AllRowsAmount;
			$amount = preg_replace("/,/", "", $amount);
		}
		
		if($amount == null) 
		{
			if($eventRow["ComputeItemID"]*1 > 0)
			{
				if(isset($this->ComputedItems[ $eventRow["ComputeItemID"] ]))
					$amount = $this->ComputedItems[ $eventRow["ComputeItemID"] ];
				else
				{
					if(isset($this->EventFunction))
					{
						$amount = call_user_func($this->EventFunction, $eventRow["ComputeItemID"], $this->Sources);
						$this->ComputedItems[ $eventRow["ComputeItemID"] ] = $amount;
					}
				}
				if(is_array($amount))
				{
					if(isset($amount["amount"]))
					{
						PdoDataAccess::FillObjectByArray($obj, $amount);
						$amount = $amount["amount"];
					}
					else
					{
						foreach($amount as $amountRow)
						{
							if(!$this->AddDocItem($eventRow, $amountRow))
								return false;
						}
						return true;
					}
				}
			}
		}
		else 
		{
			if(is_array($amount) && isset($amount["amount"]))
			{
				PdoDataAccess::FillObjectByArray($obj, $amount);
				$amount = $amount["amount"];
				
			}
		}
		
		if($amount == 0)
			return true;
		
		$obj->DebtorAmount = $eventRow["CostType"] == "DEBTOR" ? $amount : 0;
		$obj->CreditorAmount = $eventRow["CostType"] == "CREDITOR" ? $amount : 0;
		if($obj->DebtorAmount < 0)
		{
			$obj->CreditorAmount = -1*$obj->DebtorAmount;
			$obj->DebtorAmount = 0;
		}
		if($obj->CreditorAmount < 0)
		{
			$obj->DebtorAmount = -1*$obj->CreditorAmount;
			$obj->CreditorAmount = 0;
		}	
		
		//---------------- set tafsilis --------------------
		$obj->TafsiliType = $eventRow["TafsiliType1"];
		$obj->TafsiliType2 = $eventRow["TafsiliType2"];
		$obj->TafsiliType3 = $eventRow["TafsiliType3"];
		$result = EventComputeItems::SetSpecialTafsilis($eventRow, $this->Sources);
		$obj->TafsiliID = $result[0]["TafsiliID"];
		$obj->TafsiliID2 = $result[1]["TafsiliID"];
		$obj->TafsiliID3 = $result[2]["TafsiliID"];
		
		if(!empty($this->tafsilis))
		{
			if(!empty($eventRow["TafsiliType1"]) && !empty($this->tafsilis[ $eventRow["TafsiliType1"] ]))
				$obj->TafsiliID = $this->tafsilis[ $eventRow["TafsiliType1"] ];
			
			if(!empty($eventRow["TafsiliType2"]) && !empty($this->tafsilis[ $eventRow["TafsiliType2"] ]))
				$obj->TafsiliID2 = $this->tafsilis[ $eventRow["TafsiliType2"] ];
			
			if(!empty($eventRow["TafsiliType3"]) && !empty($this->tafsilis[ $eventRow["TafsiliType3"] ]))
				$obj->TafsiliID3 = $this->tafsilis[ $eventRow["TafsiliType3"] ];
		}
		
		//------------------- set SourceIDs  ---------------------
		if(is_array($this->Sources))
			for($i=0; $i < count($this->Sources); $i++)
				$obj->{ "SourceID" . ($i+1) } = (int)$this->Sources[$i];
		else
			$obj->SourceID = $this->Sources;
		//------------------- set params  ---------------------
		EventComputeItems::SetParams($this->EventID, $eventRow, $this->Sources, $obj);
		
		//-- insert DocHeaders if at least one row would be added --
		if(empty($this->DocObj->DocID))
		{
			if(!$this->DocObj->Add($this->pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد سند");
				return false;
			}
			$obj->DocID = $this->DocObj->DocID;
		}
		//-------------------------------------------------------------
		
		if(!$obj->Add($this->pdo))
		{
			ExceptionHandler::PushException("خطا در صدور ردیف های سند تعهدی");
			return false;
		}
		return true;
	}

	static function GetRegisteredDoc($EventID, $SourceIDs){
	
		$params = array(":e" => $EventID);
		$query = "select LocalNo 
				from ACC_DocItems join ACC_docs using(DocID)
				where EventID=:e ";

		if(!is_array($SourceIDs))
			$SourceIDs = array($SourceIDs);

		$index = 0;
		foreach($SourceIDs as $sourceID)
		{
			$query .= " AND SourceID" . ($index+1) . "=:s". $index;
			$params[":s" . $index] = $sourceID;
			$index++;
		}

		$dt = PdoDataAccess::runquery($query, $params);
		return count($dt) > 0 ? $dt[0]["LocalNo"] : false;
	}
}
