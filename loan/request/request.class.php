<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//---------------------------

require_once DOCUMENT_ROOT . '/office/dms/dms.class.php';

class LON_requests extends PdoDataAccess{
	
	public $RequestID;
	public $BranchID;
	public $LoanID;
	public $ReqPersonID;
	public $ReqDate;
	public $ReqAmount;
	public $StatusID;
	public $ReqDetails;
	public $BorrowerDesc;
	public $BorrowerID;
	public $BorrowerMobile;
	public $LoanPersonID;
	public $guarantees;
	public $AgentGuarantee;
	public $FundGuarantee;
	public $DocumentDesc;	
	public $IsEnded;
	public $SubAgentID;
	public $IsFree;
	public $imp_VamCode;
	public $PlanTitle;
	public $RuleNo;
	public $FundRules;
	public $DomainID;
	public $ContractType;
	public $IsLock;
	public $EndDate;

	/* New Add Fields */
	public $LetterID;
	public $SourceID;
	public $ExpertPersonID;
	public $DocReceiveDate;
	public $DocRequestDate;
	public $MeetingDate;
	public $VisitDate;
	public $WorkgroupDiscussDate;
	/*END New Add Fields */

	public $_LoanDesc;
	public $_LoanPersonFullname;
	public $_ReqPersonFullname;
	public $_BranchName;
	public $_SubAgentDesc;
	public $_LoanGroupID;
	
	function __construct($RequestID = "", $pdo = null) {
		
		$this->DT_DomainID = DataMember::CreateDMA(DataMember::DT_INT, 0);

		// change shamsi date to miladi date for save miladi date in database
		$this->DT_ReqDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_DocReceiveDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_DocRequestDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_MeetingDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_VisitDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_WorkgroupDiscussDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_EndDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($RequestID != "")
			PdoDataAccess::FillObject ($this, "
				select r.* , concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) _LoanPersonFullname, LoanDesc _LoanDesc,
						concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) _ReqPersonFullname, b.BranchName _BranchName,
						SubDesc as _SubAgentDesc,l.GroupID _LoanGroupID
						
					from LON_requests r 
					left join BSC_persons p1 on(p1.PersonID=LoanPersonID)
					left join LON_loans l using(LoanID)
					left join BSC_persons p2 on(p2.PersonID=ReqPersonID)
					left join BSC_branches b using(BranchID)
					left join BSC_SubAgents sa on(SubID=SubAgentID)
				where RequestID=?", array($RequestID), $pdo);
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery_fetchMode("
			select r.*,l.*,p.PartID,p.PartAmount,p.PartDate,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				bi.InfoDesc StatusDesc,
				BranchName,
				DomainDesc
			from LON_requests r
			left join BSC_ActDomain using(DomainID)
			left join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
			left join LON_loans l using(LoanID)
			left join BSC_branches using(BranchID)
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			where " . $where, $param);
	}
	
	function CheckForDuplicate(){
		
		if(!empty($this->RequestID))
		{
			$dt = PdoDataAccess::runquery("
			select r2.RequestID from LON_requests r1
				join LON_requests r2 on(r1.RequestID<>r2.RequestID 
					AND substr(r1.ReqDate,1,10)=substr(r2.ReqDate,1,10) AND r1.ReqAmount=r2.ReqAmount 
					AND (if(r1.LoanPersonID>0,r1.LoanPersonID=r2.LoanPersonID,1=0) 
						OR if(r1.BorrowerID<>'',r1.BorrowerID=r2.BorrowerID,1=0) 
						OR if(r1.BorrowerDesc<>'',r1.BorrowerDesc=r2.BorrowerDesc,1=0) ) )
			where r1.RequestID=?",array($this->RequestID));
			
			if(count($dt) > 0)
			{
				ExceptionHandler::PushException("در این تاریخ و با این مبلغ وام دیگری با شماره" .
					$dt[0][0]. " ثبت شده است ");
				return false;
			}
		}
		else
		{
			$dt = PdoDataAccess::runquery("
			select r2.RequestID from LON_requests r2 
			where substr(r2.ReqDate,1,10)=substr(now(),1,10) 
				AND r2.ReqAmount=:a
				AND (if(:pid > 0,r2.LoanPersonID=:pid,1=0) OR r2.BorrowerID=:bid OR r2.BorrowerDesc=:bdesc)",
				array(":a" => $this->ReqAmount, ":pid" => $this->LoanPersonID, 
					":bid" => $this->BorrowerID, ":bdesc" => $this->BorrowerDesc));
			
			if(count($dt) > 0)
			{
				ExceptionHandler::PushException("در این تاریخ و با این مبلغ وام دیگری با شماره" .
					$dt[0][0]. " ثبت شده است ");
				return false;
			}
		}
		return true;
	}
	
	function AddRequest($pdo = null){
		$this->ReqDate = PDONOW;
		
		if(!$this->CheckForDuplicate())
			return false;
		
	 	if(!parent::insert("LON_requests",$this, $pdo))
			return false;
		$this->RequestID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->RequestID;
		$daObj->TableName = "LON_requests";
		$daObj->execute($pdo);
		return true;
	}
	
	function EditRequest($pdo = null, $CheckDuplicate = true){
		
		/*if($CheckDuplicate)
			if(!$this->CheckForDuplicate())
				return false;*/
		
	 	if( parent::update("LON_requests",$this," RequestID=:l", array(":l" => $this->RequestID), $pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->RequestID;
		$daObj->TableName = "LON_requests";
		$daObj->execute($pdo);
	 	return true;
    }
	
	static function DeleteRequest($RequestID){
		
		$obj = new LON_requests($RequestID);
		if($obj->StatusID != "1")
		{
			ExceptionHandler::PushException("درخواست در حال گردش قابل حذف نمی باشد");
			return false;
		}
		
		if(!DMS_documents::DeleteAllDocument($RequestID, "loan"))
		{
			ExceptionHandler::PushException("خطا در حذف مدارک");
	 		return false;
		}		
		
		if( parent::delete("LON_ReqParts"," RequestID=?", array($RequestID)) === false )
		{
			ExceptionHandler::PushException("خطا در حذف شرایط");
	 		return false;
		}
		if( parent::delete("LON_installments"," RequestID=?", array($RequestID)) === false )
		{
			ExceptionHandler::PushException("خطا در حذف شرایط");
	 		return false;
		}
		if( parent::delete("LON_requests"," RequestID=?", array($RequestID)) === false )
	 	{
			ExceptionHandler::PushException("خطا در حذف درخواست");
			return false;
		}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $RequestID;
		$daObj->TableName = "LON_requests";
		$daObj->execute();
	 	return true;
	}
	
	static function ChangeStatus($RequestID, $StatusID, $StepComment = "", $LogOnly = false, $pdo = null, $UpdateOnly = false){
	
		if(empty($StatusID))
			return true;
		if(!$LogOnly)
		{
			$obj = new LON_requests();
			$obj->RequestID = $RequestID;
			$obj->StatusID = $StatusID;
			if(!$obj->EditRequest($pdo , false))
				return false;
		}
		if(!$UpdateOnly)
		{
			PdoDataAccess::runquery("insert into LON_ReqFlow(RequestID,PersonID,StatusID,ActDate,StepComment) 
			values(?,?,?,now(),?)", array(
				$RequestID,
				$_SESSION["USER"]["PersonID"],
				$StatusID,
				$StepComment
			), $pdo);
		}
		return ExceptionHandler::GetExceptionCount() == 0;
	}
	
	static function EventTrigger_end($SourceObjects, $eventObj, $pdo){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		
		$ReqObj->IsEnded = "YES";
		$ReqObj->StatusID = LON_REQ_STATUS_ENDED;
		if(!$ReqObj->EditRequest($pdo))
		{
			ExceptionHandler::PushException("خطا در تغییر درخواست");
			return false;
		}
		return self::ChangeStatus($ReqObj->RequestID,$ReqObj->StatusID,"", false, $pdo);	
	}

	
	//-------------------------------------
	static function ComputeWage($PartAmount, $CustomerWagePercent, $InstallmentCount, $IntervalType, $PayInterval){
	
		if($PayInterval == 0)
			return 0;

		if($CustomerWagePercent == 0)
			return 0;

		if($IntervalType == "DAY")
			$PayInterval = $PayInterval/30;

		$R = ($CustomerWagePercent/12)*$PayInterval;
		$F7 = $PartAmount;
		$F9 = $InstallmentCount;
		return ((($F7*$R*pow(1+$R,$F9))/(pow(1+$R,$F9)-1))*$F9)-$F7;
	}

	static function YearWageCompute($PartObj, $TotalWage, $YearMonths){

		/*@var $PartObj LON_ReqParts */

		$startDate = DateModules::miladi_to_shamsi($PartObj->PartDate);
		$startDate = DateModules::AddToJDate($startDate, $PartObj->DelayDays, $PartObj->DelayMonths); 
		$startDate = preg_split('/[\-\/]/',$startDate);
		$PayMonth = $startDate[1]*1;

		$FirstYearInstallmentCount = floor((12 - $PayMonth)/(12/$YearMonths));
		$FirstYearInstallmentCount = $PartObj->InstallmentCount < $FirstYearInstallmentCount ? 
				$PartObj->InstallmentCount : $FirstYearInstallmentCount;
		$MidYearInstallmentCount = floor(($PartObj->InstallmentCount-$FirstYearInstallmentCount) / $YearMonths);
		$MidYearInstallmentCount = $MidYearInstallmentCount < 0 ? 0 : $MidYearInstallmentCount;
		$LastYeatInstallmentCount = $PartObj->InstallmentCount - $FirstYearInstallmentCount - $MidYearInstallmentCount;
		$F9 = $PartObj->InstallmentCount*(12/$YearMonths);

		$yearNo = 1;
		$StartYear = $startDate[0]*1;
		$returnArr = array();
		while(true)
		{
			if($yearNo > $MidYearInstallmentCount+2)
				break;

			$BeforeMonths = 0;
			if($yearNo == 2)
				$BeforeMonths = $FirstYearInstallmentCount;
			else if($yearNo > 2)
				$BeforeMonths = $FirstYearInstallmentCount + ($yearNo-2)*$YearMonths;

			$curMonths = $FirstYearInstallmentCount;
			if($yearNo > 1 && $yearNo <= $MidYearInstallmentCount+1)
				$curMonths = $YearMonths;
			else if($yearNo > $MidYearInstallmentCount+1)
				$curMonths = $LastYeatInstallmentCount;

			$BeforeMonths = $BeforeMonths*(12/$YearMonths);
			$curMonths = $curMonths*(12/$YearMonths);

			$val = (((($F9-$BeforeMonths)*($F9-$BeforeMonths+1))-
				($F9-$BeforeMonths-$curMonths)*($F9-$BeforeMonths-$curMonths+1)))/($F9*($F9+1))*$TotalWage;

			$returnArr[ $StartYear ] = $val;
			$StartYear++;
			$yearNo++;
		}

		return $returnArr;
	}
	
	//-------------------------------------
	
	static function GetDelayAmounts($RequestID, $PartObj = null, $PayObj = null){
		
		if($PartObj->DelayDays*1 == 0 && $PartObj->DelayMonths*1 == 0)
		{
			return array(
				"CustomerDelay" => 0,
				"FundDelay" => 0,
				"AgentDelay" => 0,
				
				"CustomerYearDelays" => array(),
				"FundYearDelays" => array(),
				"AgentYearDelays" => array()
			);
		}
		/*@var $PartObj LON_ReqParts */
		/*@var $PayObj LON_payments */
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		$PayDate = $PayObj == null ? $PartObj->PartDate : $PayObj->PayDate;
		$PayAmount = $PayObj == null ? $PartObj->PartAmount : $PayObj->PayAmount;
		
		$endDelayDate = DateModules::AddToGDate($PayDate, $PartObj->DelayDays*1, $PartObj->DelayMonths*1);
		$DelayDuration = DateModules::GDateMinusGDate($endDelayDate, $PayDate)+1;
		$CustomerDelay = $FundDelay = $AgentDelay = 0;
		
		if($PartObj->DelayPercent == 0 || $PartObj->ComputeMode == "NOAVARI")
		{
			return array(
				"CustomerDelay" => 0,
				"FundDelay" => 0,
				"AgentDelay" => 0,
				
				"CustomerYearDelays" => array(),
				"FundYearDelays" => array(),
				"AgentYearDelays" => array()
			);
		}
		
		$fundZarib = $PartObj->FundWage/$PartObj->DelayPercent;
			
		if($PartObj->ComputeMode == "NEW") 
		{
			$dt = LON_payments::Get(" AND RequestID=?", array($RequestID));
			$oldFundDelayAmount = $oldAgentDelayAmount = 0;
			foreach($dt as $row)
			{
				$oldFundDelayAmount += $row["OldFundDelayAmount"]*1;
				$oldAgentDelayAmount += $row["OldAgentDelayAmount"]*1;
			}
			if($oldFundDelayAmount + $oldAgentDelayAmount > 0)
				return array(
					"CustomerDelay" => $oldFundDelayAmount + $oldAgentDelayAmount,
					"FundDelay" => $oldFundDelayAmount,
					"AgentDelay" => $oldAgentDelayAmount
				);
			
			$amount = LON_payments::GetTotalTanziledPayAmount($RequestID, $PartObj);
			$JPartDate = DateModules::miladi_to_shamsi($PartObj->PartDate);
			$endDelayDate = DateModules::AddToJDate($JPartDate, $PartObj->DelayDays*1, $PartObj->DelayMonths*1);
			
			$DelayDuration = DateModules::JDateMinusJDate($endDelayDate, $JPartDate);
			$CustomerDelay = round($amount*$PartObj->DelayPercent*$DelayDuration/36500);
			//$newAmount = LON_Computes::Tanzil($amount, $PartObj->DelayPercent, $endDelayDate, $JPartDate);
			//$CustomerDelay = round($PartObj->PartAmount - $newAmount);
			
			if($PartObj->DelayReturn != "INSTALLMENT")
				$FundDelay = round($fundZarib*$CustomerDelay);
			
			if($PartObj->AgentDelayReturn != "INSTALLMENT")
			{
				$percent = $PartObj->DelayPercent - $PartObj->FundWage;
				$AgentDelay = $CustomerDelay - round($fundZarib*$CustomerDelay);
			}
				
			$CustomerDelay = $FundDelay + $AgentDelay;
		}
		else
		{
			if($PartObj->DelayDays*1 > 0 || $PayDate<>$PartObj->PartDate)
			{
				$CustomerDelay = round($PayAmount*$PartObj->DelayPercent*$DelayDuration/36500);
				$FundDelay = round($PayAmount*$PartObj->FundWage*$DelayDuration/36500);
				$AgentDelay = round($PayAmount*($PartObj->DelayPercent - $PartObj->FundWage)*$DelayDuration/36500);		
			}
			else
			{
				$CustomerDelay = round($PayAmount*$PartObj->DelayPercent*$PartObj->DelayMonths/1200);
				$FundDelay = round($PayAmount*$PartObj->FundWage*$PartObj->DelayMonths/1200);
				$AgentDelay = round($PayAmount*($PartObj->DelayPercent - $PartObj->FundWage)*$PartObj->DelayMonths/1200);
			}
		}
		
		return array(
			"CustomerDelay" => $CustomerDelay,
			"FundDelay" => $FundDelay,
			"AgentDelay" => $AgentDelay
		);
	}
	
	static function GetWageAmounts($RequestID, $PartObj = null, $PayAmount = null){
		
		/*@var $PartObj LON_ReqParts */
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		$PayAmount = $PayAmount == null ? $PartObj->PartAmount : $PayAmount;
		
		$startDate = DateModules::miladi_to_shamsi($PartObj->PartDate);
		$startDate = preg_split('/[\-\/]/',$startDate);
		$firstYear = $startDate[0];
		
		if($PartObj->PayInterval > 0)
			$YearMonths = ($PartObj->IntervalType == "DAY" ) ? 
				floor(365/$PartObj->PayInterval) : 12/$PartObj->PayInterval;
		else
			$YearMonths = 12;
		
		//...................................
		
		//...................................
		
		$TotalWage = 0;
		switch($PartObj->ComputeMode)
		{
			case "NEW":
			case "NOAVARI":
				$dt = LON_installments::GetValidInstallments($PartObj->RequestID);
				$FundWage = 0;
				if(($PartObj->FundWage*1 == 0 || $PartObj->WageReturn != "INSTALLMENT") && 
					($PartObj->FundWage*1 < $PartObj->CustomerWage*1 && $PartObj->AgentReturn != "INSTALLMENT"))
				{
					$FundWage = $PartObj->FundWage*$PartObj->PartAmount/100;
					$AgentWage = ($PartObj->CustomerWage - $PartObj->FundWage)*$PartObj->PartAmount/100;
					$AgentWage = $AgentWage < 0 ? 0 : $AgentWage;
					return array(
						"FundWage" => $FundWage,
						"AgentWage" => $AgentWage,
						"CustomerWage" => 0
					);
				}
				if(count($dt)>0)
				{
					foreach($dt as $row)
					{
						$TotalWage += $row["wage"]*1;
						$FundWage += $row["PureFundWage"]*1;
					}
				}
				if($PartObj->FundWage <= $PartObj->CustomerWage)
				{
					if($PartObj->CustomerWage*1 != 0)
						$FundWage = $TotalWage*$PartObj->FundWage/$PartObj->CustomerWage;
					else
						$FundWage = $TotalWage*$PartObj->FundWage;
					
					$AgentWage = $TotalWage - $FundWage;
				}
				else
				{
					$FundWage += $TotalWage;
					$AgentWage = 0;
				}
				return array(
					"FundWage" => $FundWage,
					"AgentWage" => $AgentWage,
					"CustomerWage" => $TotalWage
				);
				
			case "BANK":
				$TotalWage = round(self::ComputeWage( $PayAmount,
					$PartObj->CustomerWage*1/100, 
					$PartObj->InstallmentCount, 
					$PartObj->IntervalType, $PartObj->PayInterval));	
				if($PartObj->FundWage <= $PartObj->CustomerWage)
				{
					if($PartObj->CustomerWage*1 != 0)
						$FundWage = $TotalWage*$PartObj->FundWage/$PartObj->CustomerWage;
					else
						$FundWage = $TotalWage*$PartObj->FundWage;
					$AgentWage = $TotalWage - $FundWage;
				}
				else
				{
					$FundWage = $TotalWage + round($PayAmount*($PartObj->CustomerWage-$PartObj->FundWage)/100);
					$AgentWage = 0;
				}		
				return array(
					"FundWage" => $FundWage,
					"AgentWage" => $AgentWage,
					"CustomerWage" => $TotalWage
				);
			
			case "PERCENT":
				
				if($PartObj->CustomerWage < $PartObj->FundWage)
				{
					$CustomerWage = round($PayAmount*$PartObj->CustomerWage/100);
					$AgentWage = 0;
					$FundWage = round($PayAmount*$PartObj->FundWage/100);
				}
				else
				{
					$CustomerWage = round($PayAmount*$PartObj->CustomerWage/100);
					$FundWage = round($PayAmount*$PartObj->FundWage/100);
					$AgentWage = $CustomerWage - $FundWage;
				}
				
				return array(
					"FundWage" => $FundWage,
					"AgentWage" => $AgentWage,
					"CustomerWage" => $CustomerWage
				);
		}
		
		//...................................
		
		$FundWage = 0;
		$AgentWage = 0;
		$FundYears = array();
		$AgentYears = array();
		
		if($PartObj->FundWage <= $PartObj->CustomerWage)
		{
			if($PartObj->CustomerWage*1 != 0)
				$FundWage = $TotalWage*$PartObj->FundWage/$PartObj->CustomerWage;
			else
				$FundWage = $TotalWage*$PartObj->FundWage;
			$AgentWage = $TotalWage - $FundWage;
		}
		else
		{
			$FundWage = $TotalWage + round($PayAmount*($PartObj->CustomerWage-$PartObj->FundWage)/100);
			$AgentWage = 0;
		}				
		
		//...................................
		
		return array(
			"FundWage" => $FundWage,
			"AgentWage" => $AgentWage,
			"CustomerWage" => $TotalWage
		);
	}
	//-------------------------------------
	static function BackPayCompute($partObj, &$returnArr, $curRecord, $records, $index){
		
		$totalRemain = $returnArr["TotalRemainder"];
		$RecordAmount = $curRecord["RecordAmount"]*1;
		
		if($curRecord["type"] == "installment")
		{
			if($returnArr["TotalRemainder"] >= 0)
			{
				$returnArr["TotalRemainder"] += $curRecord["RecordAmount"]*1;
				return;
			}
			$temp = $returnArr["TotalRemainder"];
			$returnArr["TotalRemainder"] += $curRecord["RecordAmount"]*1;
			$curRecord["RecordAmount"] = abs($temp);
			return;
		}
		
		//---------------- base on percent ------------------
		if($totalRemain == 0) // pay is first record
		{
			$i = $index+1;
			$share_pure = 0;
			$share_wage = 0;
			while($i < count($records))
			{
				$share = $records[$i]["RecordAmount"]*1 - $records[$i]["wage"];
				if($share < 0)
				{
					$share_pure += $share;
					$share_wage += min($records[$i]["wage"],$RecordAmount);
					$RecordAmount -= min($records[$i]["wage"],$RecordAmount);
				}
				else if($RecordAmount > $records[$i]["RecordAmount"]*1) 
				{
					$share_pure += $records[$i]["RecordAmount"]*1 - $records[$i]["wage"];
					$share_wage += $records[$i]["wage"];
					$RecordAmount -= $records[$i]["RecordAmount"]*1;
				}
				else
				{
					$total = $records[$i]["RecordAmount"]*1;
					$share = $total - $records[$i]["wage"];
					$share_pure += round($RecordAmount*($share/$total));
					$share_wage += round($RecordAmount*($records[$i]["wage"]/$total));
					break;
				}
				$i++;
			}

			$share_LateWage = 0;
			$share_LateForfeit = 0;
		}
		else
		{
			$share_pure = round($RecordAmount*($returnArr["totalpure"]/$totalRemain));
			$share_wage = round($RecordAmount*($returnArr["totalwage"]/$totalRemain));
			$share_LateWage = round($RecordAmount*($returnArr["totalLateWage"]/$totalRemain));
			$share_LateForfeit = $returnArr["totalLateWage"] == 0 ? 0 :
					$curRecord["RecordAmount"] - $share_pure - $share_wage - $share_LateWage;
		}
		
		$returnArr["share_pure"] = $share_pure;
		$returnArr["share_wage"] = $share_wage;
		$returnArr["share_LateWage"] = $share_LateWage;
		$returnArr["share_LateForfeit"] = $share_LateForfeit;
		
		$returnArr["totalpure"] -= $share_pure;//min($share_pure,$returnArr["totalpure"]);
		$returnArr["totalwage"] -= $share_wage;//min($share_wage,$returnArr["totalwage"]);
		$returnArr["totalLateWage"] -= $share_LateWage;//min($share_LateWage,$returnArr["totalLateWage"]);
		$returnArr["totalLateForfeit"] -= $share_LateForfeit;//min($share_LateForfeit,$returnArr["totalLateForfeit"]);
		return;
		
		//------ base on pure/wage/forfeit order
		for($i=1; $i<=4; $i++)
		{
			$min = min($returnArr["total" . $partObj->{"_param" . $i}], $curRecord["RecordAmount"]*1);
			$curRecord["RecordAmount"] -= $min;
			$returnArr["total" . $partObj->{"_param" . $i}] -= $min;
			if($curRecord["RecordAmount"] == 0)
				return;
			if($partObj->{"_param" . $i} == "pure")
			{
				for($j=$index+1; $j<count($records); $j++)
				{
					if($records[$j]["type"] == "installment")
						return;
				}
			}
		}
	}
	
	static function ComputePayments2($RequestID, &$installments, $ComputeDate = null, $pdo = null){

		$ComputeDate = $ComputeDate == null ? DateModules::Now() : 
			DateModules::shamsi_to_miladi($ComputeDate,"-");

		$obj = LON_ReqParts::GetValidPartObj($RequestID);		
		
		if($obj->ComputeMode == "NEW")
			return LON_Computes::NewComputePayments($RequestID, $ComputeDate, $pdo);
		
		$installments = PdoDataAccess::runquery("select * from 
			LON_installments where RequestID=? AND history='NO' AND IsDelayed='NO' order by InstallmentDate", 
			array($RequestID), $pdo);
		
		$refInstallments = array();
		for($i=0; $i<count($installments); $i++)
		{
			$installments[$i]["remainder"] = $installments[$i]["InstallmentAmount"];
			$refInstallments[ $installments[$i]["InstallmentID"] ] = &$installments[$i];			
		}

		$returnArr = array();
		$records = PdoDataAccess::runquery("
			select * from (
				select InstallmentID id,'installment' type, 0 BackPayID,
				InstallmentDate RecordDate,InstallmentAmount RecordAmount,0 PayType, '' details, wage
				from LON_installments where RequestID=:r AND history='NO' AND IsDelayed='NO'
			union All
				select BackPayID id, 'pay' type, BackPayID,
					substr(p.PayDate,1,10) RecordDate, PayAmount RecordAmount, PayType,
					if(PayType=" . BACKPAY_PAYTYPE_CORRECT . ",p.details,'') details,0
				from LON_BackPays p
				left join ACC_IncomeCheques i using(IncomeChequeID)
				left join BaseInfo bi on(bi.TypeID=6 AND bi.InfoID=p.PayType)
				where RequestID=:r AND PayDate <= :tdate AND
					if(p.PayType=".BACKPAY_PAYTYPE_CHEQUE.",i.ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
					AND PayDate <= :tdate
			union All
				select 0 id,'pay' type, 0 BackPayID,
					CostDate RecordDate, -1*CostAmount RecordAmount,0, CostDesc details, 0
				from LON_costs 
				where RequestID=:r AND CostAmount<>0 AND CostDate <= :tdate
			)t
			order by substr(RecordDate,1,10), RecordAmount desc" , 
				array(":r" => $RequestID, ":tdate" => $ComputeDate), $pdo);
		
		$TotalLate = 0;
		$TotalForfeit = 0;
		$TotalRemainder = 0;
		$ComputePayRows = array();
		for($i=0; $i < count($records); $i++)
		{
			$record = $records[$i];
			$tempForReturnArr = array(
					"InstallmentID" => $record["id"],
					"BackPayID" => $record["BackPayID"],
					"details" => $record["details"],
					"type" => $record["type"],
					"ActionType" => $record["type"],
					"ActionDate" => $record["RecordDate"],
					"ActionAmount" => $record["RecordAmount"]*1,
					"ForfeitDays" => 0,
					"CurLateAmount" => 0,
					"CurForfeitAmount" => 0,					
					"LateAmount" => $TotalLate,
					"ForfeitAmount" => $TotalForfeit,
					"TotalRemainder" => $TotalRemainder
				);	
			if($record["type"] == "installment")
			{
				$TotalRemainder += $record["RecordAmount"]*1;
			}
			if($record["type"] == "pay")
			{
				if($record["PayType"] == BACKPAY_PAYTYPE_CORRECT)
				{
					$TotalRemainder -= $record["RecordAmount"]*1;
				}
				else
				{
					if($record["RecordAmount"]*1 < 0)
					{
						$TotalRemainder += abs($record["RecordAmount"]*1);
						$record["RecordAmount"] = 0;
					}
					if($obj->PayCompute == "installment")
					{
						$min = min($TotalRemainder, $record["RecordAmount"]*1);
						$TotalRemainder -= $min;
						$record["RecordAmount"] -= $min;
						if($record["RecordAmount"] > 0)
						{
							$IsInstallmentAfter = false;
							for($j=$i+1; $j<count($records); $j++)
							{
								if($records[$j]["type"] == "installment")
								{
									$IsInstallmentAfter = true;
									break;
								}
							}
							if($IsInstallmentAfter)
							{
								$TotalRemainder -= $record["RecordAmount"];
							}
							else
							{
								$remain = $record["RecordAmount"];
								$min = min($TotalLate,$record["RecordAmount"]);
								$TotalLate -= $min;
								$remain -= $min;
								if($remain > 0)
								{
									$min = min($remain,$TotalForfeit);
									$TotalForfeit -= $min;
									$remain -= $min;
									if($remain > 0)
										$TotalRemainder -= $remain;
								}
								/*$TotalForfeit -= $record["RecordAmount"];
								if($TotalForfeit < 0)
								{
									$TotalRemainder += $TotalForfeit;
									$TotalForfeit = 0;
								}*/
							}							
						}
					}
					else
					{
						$min = min($TotalLate, $record["RecordAmount"]*1);
						$TotalLate -= $min;
						$record["RecordAmount"] -= $min;
						
						$min = min($TotalForfeit, $record["RecordAmount"]*1);
						$TotalForfeit -= $min;
						$record["RecordAmount"] -= $min;
						if($record["RecordAmount"] > 0)
						{
							$TotalRemainder -= $record["RecordAmount"]*1;
						}
					}				
				}
			}
			
			$StartDate = $record["RecordDate"];
			$ToDate = $i+1 < count($records) ? $records[$i+1]["RecordDate"] : $ComputeDate;
			if($ToDate > $ComputeDate)
				$ToDate = $ComputeDate;
			if($StartDate < $ToDate && $TotalRemainder > 0)
			{
				if($TotalRemainder > 0)
				{
					$forfeitDays = DateModules::GDateMinusGDate($ToDate,$StartDate);
					$CurLate = round($TotalRemainder*$obj->LatePercent*$forfeitDays/36500);
					$CurForfeit = round($TotalRemainder*$obj->ForfeitPercent*$forfeitDays/36500);
					$tempForReturnArr["ForfeitDays"] = $forfeitDays;
					$tempForReturnArr["CurLateAmount"] = $CurLate;
					$tempForReturnArr["CurForfeitAmount"] = $CurForfeit;
					$TotalLate += $CurLate;
					$TotalForfeit += $CurForfeit;
				}
			}

			$tempForReturnArr["TotalRemainder"] = $TotalRemainder;
			$tempForReturnArr["LateAmount"] = $TotalLate;
			$tempForReturnArr["ForfeitAmount"] = $TotalForfeit;
			
			$tempForReturnArr["pays"] = array();
			$returnArr[] = $tempForReturnArr;
			
			if($record["type"] == "pay" && $tempForReturnArr["ActionAmount"] > 0)
				$ComputePayRows[] = $tempForReturnArr;
			
			if($record["type"] == "installment")
			{
				$refInstallments[ $record["id"] ]["ForfeitDays"] = $tempForReturnArr["ForfeitDays"];
				$refInstallments[ $record["id"] ]["CurLateAmount"] = $tempForReturnArr["CurLateAmount"];
				$refInstallments[ $record["id"] ]["CurForfeitAmount"] = $tempForReturnArr["CurForfeitAmount"];
				$refInstallments[ $record["id"] ]["TotalRemainder"] = $tempForReturnArr["TotalRemainder"];
			}
		}
		//............. pay rows of each installment ..............
		$payIndex = 0;
		while(true)
		{
			$PayRecord = $payIndex < count($ComputePayRows) ? $ComputePayRows[$payIndex++] : null;
			if(!$PayRecord || $PayRecord["ActionAmount"] > 0)
				break;
		}
		
		for($i=0; $i < count($returnArr); $i++)
		{
			$InstallmentRow = &$returnArr[$i];
			if($InstallmentRow["ActionType"] != "installment")
				continue;
			
			$amount = $InstallmentRow["ActionAmount"]*1;
			
			while($amount > 0)
			{
				$StartDate = count($InstallmentRow["pays"]) > 0 ?
						$InstallmentRow["pays"][count($InstallmentRow["pays"])-1]["G-PayedDate"] :
						$InstallmentRow["ActionDate"];
				if($InstallmentRow["ActionDate"] > $StartDate)
					$StartDate = $InstallmentRow["ActionDate"];
				//if(!$PayRecord)
				//	$StartDate = $InstallmentRow["ActionDate"];
				
				$ToDate = $PayRecord ? $PayRecord["ActionDate"] : $ComputeDate;
				if($ToDate > $ComputeDate)
					$ToDate = $ComputeDate;
				$forfeitDays = DateModules::GDateMinusGDate($ToDate,$StartDate);
				$CurLate = round($amount*$obj->LatePercent*$forfeitDays/36500);
				$CurForfeit = round($amount*$obj->ForfeitPercent*1*$forfeitDays/36500);
				if($CurForfeit < 0)
				{
					$forfeitDays = 0;
					$CurForfeit = 0;
				}
				
				$SavePayedAmount = $PayRecord ? $PayRecord["ActionAmount"] : 0;
				$SaveAmount = $amount;
				if($PayRecord && $obj->PayCompute == "forfeit")
					$PayRecord["ActionAmount"] -= min($CurForfeit,$PayRecord["ActionAmount"]);
							
				$payAmount = $PayRecord ? $PayRecord["ActionAmount"] : $amount;
				
				$min = min($amount,$payAmount);
				$amount -= $min;
				if($PayRecord)
					$PayRecord["ActionAmount"] -= $min;
				
				$InstallmentRow["pays"][] = array(
					"ForfeitDays" => $forfeitDays,
					"forfeit" => $CurForfeit,
					"late" => $CurLate,
					"remain" => $PayRecord ? $amount : $SaveAmount ,
					"G-PayedDate" => $PayRecord ? $PayRecord["ActionDate"] : '',
					"PayedDate" => $PayRecord ? $PayRecord["ActionDate"] : '',
					"PayedAmount" => $SavePayedAmount
				);
				
				$refInstallments[ $InstallmentRow["InstallmentID"] ]["remainder"] -= $SavePayedAmount;
				
				if($PayRecord && $PayRecord["ActionAmount"] == 0)
				{
					$PayRecord = $payIndex < count($ComputePayRows) ? $ComputePayRows[$payIndex++] : null;
				}
			}
		}
			
		//.........................................................
		
		return $returnArr;
	}
	
	/**
	 * جدول دوم محسابه مرحله ایی اصل و کارمزد تا انتها که باید به صفر برسد
	 * @param type $RequestID
	 * @return array
	 */
	static function ComputePures($RequestID){
		
		$PartObj = LON_ReqParts::GetValidPartObj($RequestID);
		
		if($PartObj->ComputeMode == "NEW" || $PartObj->ComputeMode == "NOAVARI")
			return LON_Computes::ComputePures ($RequestID);
		
		$temp = LON_installments::GetValidInstallments($RequestID);
		//$totalBackPay = $PartObj->PartAmount;
		$totalBackPay = //self::GetPurePayedAmount($RequestID, $PartObj, $PartObj->ComputeMode == "NEW");
			self::GetPayedAmount($RequestID, $PartObj);
		//.............................
		$returnArr = array();
		$returnArr[] = array(
			"InstallmentDate" => "",
			"InstallmentAmount" => 0,
			"wage" => 0,
			"pure" => 0,
			"totalPure" => $totalBackPay
		);
		$totalPure =  $totalBackPay;
		$ComputeDate = $PartObj->PartDate;
		for($i=0; $i< count($temp); $i++)
		{
			$prevRow = $i == 0 ? null : $temp[$i-1];
			$row = &$temp[$i];
			
			if($temp[$i]["wage"]*1 > 0)
			{
				$totalPure -= $row["InstallmentAmount"] - $row["wage"];
				$returnArr[] = array(
					"InstallmentDate" => $row["InstallmentDate"],
					"InstallmentAmount" => $row["InstallmentAmount"],
					"wage" => $row["wage"],
					"pure" => $row["InstallmentAmount"] - $row["wage"],
					"totalPure" => $totalPure 
				);
				continue;
			}
			$record = array(
				"InstallmentDate" => $row["InstallmentDate"],
				"InstallmentAmount" => $row["InstallmentAmount"],
				"wage" => 0,
				"pure" => 0,
				"totalPure" => 0 
			);
			//.............................
			/*if($PartObj->PayInterval == 0 || $PartObj->WageReturn != "INSTALLMENT")
				$record["wage"] = 0;
			else
			{*/
				
				//$tanzilAmount = LON_Computes::Tanzil($row["InstallmentAmount"], $PartObj->CustomerWage, $row["InstallmentDate"], $PartObj->PartDate);
				//$record["wage"] = $row["InstallmentAmount"]*1 - $tanzilAmount;
				
				/*$V = $totalPure;
				$R = $PartObj->IntervalType == "MONTH" ? 
					1200/$PartObj->PayInterval : 36500/$PartObj->PayInterval;
				$record["wage"] = round( $V*($PartObj->CustomerWage/$R) );*/
			//}
			
			$days = DateModules::GDateMinusGDate($row["InstallmentDate"], $ComputeDate)+1;
			$record["wage"] = round( $totalPure*$PartObj->CustomerWage*$days/36500 );
			
			//.............................
			$totalPure -= $row["InstallmentAmount"] - $record["wage"];
			$ComputeDate = $row["InstallmentDate"];			
			$record["pure"] = $row["InstallmentAmount"] - $record["wage"];
			$record["totalPure"] = $totalPure;
			//.............................
			$returnArr[] = $record;
		}
		
		return $returnArr;

	}
	
	/**
	 * با درنظر گرفتن مراحل پرداخت و پرداخت تنفس در ابتدا یا اقساط این تابع مبلغی را برمی گرداند که در ابتدا
	 * به مشتری پرداخت شده است که اگر طی چند مرحله باشد مراحل دوم به بعد به تاریخ مرحله اول تنزیل می شوند
	 * @param type $RequestID
	 * @param type $PartObj
	 * @param type $TanzilCompute
	 * @return boolean
	 */
	static function GetPurePayedAmount($RequestID, $PartObj = null)
	{
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		/*@var $PartObj LON_ReqParts */
		
		if($PartObj->ComputeMode != "NEW")
		{
			return $PartObj->PartAmount*1;
		}
		
		$amount = LON_payments::GetTotalTanziledPayAmount($RequestID, $PartObj);		
		return round($amount);		
	}
	
	/**
	 مبالغی که از پرداختی به مشتری باید کسر گردد.
	 * @param type $RequestID
	 * @param type $PartObj
	 * @param type $TanzilCompute
	 * @return boolean
	 */
	static function TotalSubtractsOfPayAmount($RequestID, $PartObj = null)
	{
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		/*@var $PartObj LON_ReqParts */
		
		$amount = 0;
		
		if($PartObj->ComputeMode == "NOAVARI")
		{
			if($PartObj->WageReturn == "CUSTOMER")
				$amount += $PartObj->PartAmount*$PartObj->FundWage/100;
			if($PartObj->AgentReturn == "CUSTOMER")
				$amount += $PartObj->PartAmount*($PartObj->CustomerWage-$PartObj->FundWage)/100;
			
			return $amount;
		}
		
		if($PartObj->WageReturn == "CUSTOMER" || $PartObj->AgentReturn == "CUSTOMER")
		{
			$result = self::GetWageAmounts($RequestID, $PartObj);
			if($PartObj->WageReturn == "CUSTOMER")
			{
				if($PartObj->FundWage <= $PartObj->CustomerWage)
					$amount += $result["FundWage"];
				else
					$amount += $result["CustomerWage"];
			}
			if($PartObj->AgentReturn == "CUSTOMER")
				$amount += $result["AgentWage"];
		}
		
		return round($amount);		
	}
	
	/**
	 مبلغ قابل پرداخت به مشتری
	 * @param type $RequestID
	 * @param type $PartObj
	 * @param type $TanzilCompute
	 * @return boolean
	 */
	static function GetPayedAmount($RequestID, $PartObj = null)
	{
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		/*@var $PartObj LON_ReqParts */
		$amount = $PartObj->PartAmount*1 - self::TotalSubtractsOfPayAmount($RequestID, $PartObj);
		
		return round($amount);		
	}
	
	/**
	 *
	 * @param int $RequestID
	 * @param array $computeArr
	 * @param array $PureArr
	 * @param gdate $ComputeDate
	 * @return  مانده اصل وام تا زمان دلخواه 
	 */
	static function GetPureAmount($RequestID, $computeArr=null, $PureArr = null, $ComputeDate = ""){

		if($PureArr == null)
			$PureArr = self::ComputePures($RequestID);
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
		
		$partObj = LON_ReqParts::GetValidPartObj($RequestID);
		
		$ComputeDate = $ComputeDate == "" ? DateModules::Now() : $ComputeDate;		
		
		$TotalShouldPay = 0;
		$PureRemain = 0;
		for($i=0; $i < count($PureArr);$i++)
		{
			if($PureArr[$i]["InstallmentDate"] < $ComputeDate)
			{
				$TotalShouldPay += $PureArr[$i]["InstallmentAmount"]*1;
			}
			else
			{
				//-------- get wage until computedate -----------
				if($i == 0)
				{
					$PureRemain = $PureArr[0]["totalPure"];
					break;					
				}
				
				$fromDate = $i == 1 ? $partObj->PartDate : $PureArr[$i-1]["InstallmentDate"];
				
				$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"], $fromDate);
				$days = DateModules::GDateMinusGDate($ComputeDate, $fromDate);
				
				$wage = $PureArr[$i]["wage"]*$days/$totalDays;
				
				$PureRemain = $PureArr[$i-1]["totalPure"]*1 + $wage;
				break;
			}
		}
		return array(
			"PureAmount" => $PureRemain == 0 ? $TotalShouldPay : $PureRemain,
			"TotalShouldPay" => $TotalShouldPay + $PureRemain
		);	
	}
	
	/**
	 *
	 * @param type $RequestID
	 * @param type $computeArr
	 * @param type $PureArr
	 * @param type $ComputeDate
	 * @return مانده در صورت تسویه وام 
	 */
	static function GetDefrayAmount($RequestID, $computeArr=null, $PureArr = null, $ComputeDate = ""){

		$dt = self::GetPureAmount($RequestID, $computeArr, $PureArr, $ComputeDate);
		
		$TotalShouldPay = $dt["TotalShouldPay"];
		
		//------------ sub pays --------------
		$dt = LON_BackPays::SelectAll(" p.RequestID=? 
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)"
			, array($RequestID));
		foreach($dt as $row)
			$TotalShouldPay -= $row["PayAmount"]*1;

		//-------- add costs ----------------
		$dt = LON_costs::Get(" AND RequestID=?", array($RequestID));
		$dt = $dt->fetchAll();
		foreach($dt as $row)
			$TotalShouldPay += $row["CostAmount"]*1;
		
		//-------- add forfeits ----------------
		foreach($computeArr as $row)
		{
			$TotalShouldPay += isset($row["CurForfeitAmount"]) ? $row["CurForfeitAmount"]*1 : 0;
			$TotalShouldPay += isset($row["LateWage"]) ? $row["LateWage"]*1 : 0;
			$TotalShouldPay += isset($row["LateForfeit"]) ? $row["LateForfeit"]*1 : 0;
		}
		
		return $TotalShouldPay<0 ? 0 : $TotalShouldPay;
	}
	
    /**
	 * تاریخ اولین قسطی که کامل پرداخت نشده است
	 * @param type $RequestID
	 * @param type $computeArr
	 * @return gdate 
	 */
	static function GetMinNotPayedInstallmentDate($RequestID, $computeArr=null){
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
	
		foreach($computeArr as $row)
			if($row["type"] == "installment")
			{
				if( $row["remain_pure"]*1 != 0 || 
					$row["remain_wage"]*1 != 0 || 
					$row["remain_late"]*1 != 0 || 
					$row["remain_pnlt"]*1 != 0 )
					
					return $row["RecordDate"];
			}
		return null;
	}
	
	/**
	 * رکورد اولین قسطی که اصلا پرداخت نشده است
	 * @param type $RequestID
	 * @param type $computeArr
	 * @return gdate 
	 */
	static function GetNonPayedInstallmentRow($RequestID, $computeArr=null, $installments = array()){
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
	
		foreach($installments as $row)
			if($row["type"] == "installment" && count($row["pays"]) == 0)
				return $row;
		return null;
	}
	
	/**
	 * طبقه تسهیلات را برمی گرداند
	 * @param int $RequestID
	 */
	static function GetRequestLevel($RequestID){
		
		$ComputeDate = "";
		$dt = LON_BackPays::GetRealPaid($RequestID);
		if(count($dt) == 0)
		{
			$dt = LON_installments::GetValidInstallments($RequestID);
			if(count($dt) == 0)
				$ComputeDate = DateModules::Now();
			else
				$ComputeDate = $dt[0]["InstallmentDate"];
		}
		else
		{
			$ComputeDate = $dt[ count($dt)-1 ]["PayDate"];
		}
		
		$diff = DateModules::GDateMinusGDate(DateModules::Now(), $ComputeDate);
		if($diff < 0)
			$diffInMonth = 0;
		else
			$diffInMonth = round($diff/30, 2);
		
		$levels = PdoDataAccess::runquery("select * from ACC_CostCodeParamItems where ParamID=" . 
				ACC_COST_PARAM_LOAN_LEVEL);
		foreach($levels as $row)
		{
			if($diffInMonth >= $row["f1"]*1 && $diffInMonth <= $row["f2"]*1)
				return $row;
		}
	}
	
	/**
	 * طبقه پیگیری تسهیلات را برمی گرداند
	 * @param int $RequestID
	 */
	static function GetRequestFollowLevel($RequestID, $computeArr = null){
		
		$ComputeDate = self::GetMinNotPayedInstallmentDate($RequestID, $computeArr);
		$diff = 0;
		if($ComputeDate != null)
			$diff = DateModules::GDateMinusGDate(DateModules::Now(), $ComputeDate);
		if($diff < 0)
			$diff = 0;
		
		$levels = PdoDataAccess::runquery("select * from BaseInfo where TypeID=100");
		foreach($levels as $row)
		{
			if($diff >= $row["param1"]*1 && $diff <= $row["param2"]*1)
				return $row;
		}
	}
	
	static function GetEventID($RequestID, $EventType, $EventType3 = ""){
		
		$ReqObj = new LON_requests($RequestID);
		//----------------------------------------------------
		$where = " AND EventType='".$EventType."'";
		//----------------------------------------------------
		if($EventType == EVENTTYPE_IncomeCheque)
		{
			if($ReqObj->ReqPersonID*1 == 0)
				$where .= " AND EventType2='inner'";
			else
				$where .= " AND EventType2='agent'";
		}
		else if($RequestID > 0)
		{
			if($ReqObj->ReqPersonID*1 == 0)
				$where .= " AND EventType2='inner'";
			else if($ReqObj->ReqPersonID*1 == 1003) // صندوق نوآوری
			{
				$where .= " AND EventType2='noavari'";
			}
			else if($ReqObj->ReqPersonID*1 == 2051) // معاونت
			{
				$where .= " AND EventType2='moavenat'";
			}
			else if($ReqObj->_LoanGroupID*1 == 2)
			{ 
				$where .= " AND EventType2='hemayati' ";
			}
			else{
				$where .= " AND EventType2='agent'";
			}
		}
		if($EventType3 != "")
			$where .= " AND EventType3='".$EventType3."'";
		//----------------------------------------------------
		$dt = PdoDataAccess::runquery("select * from COM_events where 1=1 " . $where);
		
		
		//if($_SESSION["USER"]["UserName"] == "admin")
		//	echo PdoDataAccess::GetLatestQueryString ();
		
		if(count($dt) == 0)
		{
			return 0;
		}
		
		return $dt[0]["EventID"];
	}
}

class LON_NOAVARI_compute extends PdoDataAccess{
	
	static function ComputeWage($partObj){
	
		$payments = LON_payments::Get(" AND RequestID=?", array($partObj->RequestID), " order by PayDate");
		$payments = $payments->fetchAll();
		if(count($payments) == 0)
			return 0;
		//--------------- total pay months -------------
		//$firstPay = DateModules::miladi_to_shamsi($payments[0]["PurePayDate"]);
		$firstPay = DateModules::miladi_to_shamsi($payments[0]["PayDate"]);
		$paymentPeriod = $partObj->PayDuration*1;
		if($paymentPeriod == 0)
		{
			//$LastPay = DateModules::miladi_to_shamsi($payments[count($payments)-1]["PurePayDate"]);
			$LastPay = DateModules::miladi_to_shamsi($payments[count($payments)-1]["PayDate"]);
			$paymentPeriod = DateModules::GetDiffInMonth($firstPay, $LastPay);
		}
		//----------------------------------------------
		$totalWage = 0;
		$wages = array();
		foreach($payments as $row)
		{
			$wages[] = array();
			$wageindex = count($wages)-1;
			for($i=0; $i < $partObj->InstallmentCount; $i++)
			{
				//$installmentDate = DateModules::miladi_to_shamsi($payments[0]["PurePayDate"]);
				$installmentDate = DateModules::miladi_to_shamsi($payments[0]["PayDate"]);
				$monthplus = $paymentPeriod + $partObj->DelayMonths*1;
				$dayplus = 0;

				if($partObj->DelayDays*1 > 0)
					$dayplus += $partObj->DelayDays*1;

				if($partObj->IntervalType == "MONTH")
					$monthplus += ($i+1)*$partObj->PayInterval*1;
				else
					$dayplus += ($i+1)*$partObj->PayInterval*1;

				$installmentDate = DateModules::AddToJDate($installmentDate, + $dayplus, $monthplus);
				$installmentDate = DateModules::shamsi_to_miladi($installmentDate);
				$jdiff = DateModules::GDateMinusGDate($installmentDate, $row["PayDate"]);

				$wagePercent = 0;
				if(($partObj->FundWage*1 > 0 && $partObj->WageReturn == "INSTALLMENT") || 
					($partObj->FundWage*1 < $partObj->CustomerWage && $partObj->AgentReturn == "INSTALLMENT"))
					$wagePercent = $partObj->CustomerWage;
				
				$wage = round(($row["PayAmount"]/$partObj->InstallmentCount)*$jdiff*$wagePercent/36500);
				$wages[$wageindex][] = $wage;
				$totalWage += $wage;
			}
		}
		return $totalWage;
	}
}

class LON_Computes extends PdoDataAccess{
	
	static function roundUp($number, $digits){
		$factor = pow(10,$digits);
		return ceil($number*$factor) / $factor;
	}

	static function SplitYears($startDate, $endDate, $TotalAmount){
	
		$startDate = DateModules::miladi_to_shamsi($startDate);
		$endDate = DateModules::miladi_to_shamsi($endDate);

		if(substr($startDate,0,1) == 2)
			$startDate = DateModules::miladi_to_shamsi ($startDate);
		if(substr($endDate,0,1) == 2)
			$endDate = DateModules::miladi_to_shamsi ($endDate);

		$arr = preg_split('/[\-\/]/',$startDate);
		$StartYear = $arr[0]*1;

		$totalDays = 0;
		$yearDays = array();

		//............. startDate = enddate ...................
		if($startDate == $endDate)
		{
			$yearDays[$StartYear] = $TotalAmount;
		}
		//.....................................................

		$newStartDate = $startDate;
		while(DateModules::CompareDate($newStartDate, $endDate) < 0){

			$arr = preg_split('/[\-\/]/',$newStartDate);
			$LastDayOfYear = DateModules::lastJDateOfYear($arr[0]);
			if(DateModules::CompareDate($LastDayOfYear, $endDate) > 0)
				$LastDayOfYear = $endDate;

			$yearDays[$StartYear] = DateModules::JDateMinusJDate($LastDayOfYear, $newStartDate)+1;
			$totalDays += $yearDays[$StartYear];
			$StartYear++;
			$newStartDate = DateModules::AddToJDate($LastDayOfYear, 1);
		}
		$TotalDays = DateModules::JDateMinusJDate($endDate, $startDate)+1;
		$sum = 0;
		foreach($yearDays as $year => $days)
		{
			$yearDays[$year] = round(($days/$TotalDays)*$TotalAmount);
			$sum += $yearDays[$year];

			//echo  $year . " " . $days . " " . $yearDays[$year] . "\n";
		}

		if($sum <> $TotalAmount)
			$yearDays[$year] += $TotalAmount-$sum;

		return $yearDays;
	}

	static function ComputeInstallment($partObj, $installmentArray, $ComputeWage = 'YES', $roundUp = true){

		/*@var $partObj LON_ReqParts */
		
		/*if(!empty($ComputeDate))
		{
			$dt = LON_requests::GetPureAmount($partObj->RequestID, null, null, 
					DateModules::shamsi_to_miladi($ComputeDate, "-"));
			$amount = $dt["PureAmount"];
		}
		else
		{*/
			$LastPay = LON_payments::GetLastPayDate($partObj->RequestID);
			$ComputeDate = DateModules::miladi_to_shamsi($LastPay);
			$amount = LON_requests::GetPurePayedAmount($partObj->RequestID, $partObj, true);
			if($amount === false)
			{
				return false;
			}
			$TotalPureAmount = $amount;
		/*}*/
		//------------- compute percents of each installment amount ----------------
		$zarib = 1;
		$sum = 0;
		$totalZarib = 0;
		$startDate = $ComputeDate;
		$totalSubs = 0;
		for($i=0; $i<count($installmentArray);$i++)
		{
			$days = DateModules::JDateMinusJDate($installmentArray[$i]["InstallmentDate"],$startDate);
			$zarib = $zarib*(1 + ($partObj->CustomerWage/36500)*$days);
			
			if($ComputeWage == "YES")
			{
				if($installmentArray[$i]["InstallmentAmount"]*1 == 0 && $i < count($installmentArray)-1)
					$percent = 1;
				else
				{
					if($i < count($installmentArray)-1)
					{
						$percent = round($installmentArray[$i]["InstallmentAmount"]*1/$partObj->PartAmount, 2);
						$sum += round($installmentArray[$i]["InstallmentAmount"]*1/$partObj->PartAmount, 2);
					}
					else
						$percent = 1-$sum;
				}
				$installmentArray[$i]["percent"] = $percent;
				$totalZarib += $percent/$zarib;
			}
			else
			{
				if($i < count($installmentArray)-1)
				{
					$totalSubs += $installmentArray[$i]["InstallmentAmount"]/$zarib;
				}
			}
			$startDate = $installmentArray[$i]["InstallmentDate"];
		}

		//----------------- compute zarib for payment steps -------------------
		$pays = LON_payments::Get(" AND RequestID=? ", $partObj->RequestID, "order by PayDate desc");
		$pays = $pays->fetchAll();
		$paymentZarib = 1;
		if(count($pays) > 1)
		{
			for($i=0; $i<count($pays)-1; $i++)
			{
				$days = DateModules::GDateMinusGDate($pays[$i]["PurePayDate"], $pays[$i+1]["PurePayDate"]);
				$paymentZarib *= 1 + ($partObj->CustomerWage*$days/36500);
			}
		}
		//---------------------------------------------------------------------
		if($ComputeWage == "YES")
			$x = round($amount*$paymentZarib/$totalZarib);
		else
			$x = round($amount*$paymentZarib - $totalSubs)*$zarib;

		//-------  update installment Amounts ------------
		for($i=0; $i<count($installmentArray);$i++)
		{
			if($ComputeWage == "YES")
				$installmentArray[$i]["InstallmentAmount"] = $x*$installmentArray[$i]["percent"];
			else if($i == count($installmentArray)-1)
				$installmentArray[$i]["InstallmentAmount"] = $x;
		}
		//------ compute wages of installments -----------
		
		// compute wage for payment steps 
		$paymentWage = 0;
		$TotalPayedAmount = $pays[0]["PurePayAmount"];
		if(count($pays) > 1)
		{
			$pays = array_reverse($pays);
			$total = 0;
			$totalZ = 0;
			for($i=0; $i<count($pays)-1; $i++)
			{
				if($i == 0)
					$total += $pays[$i]["PurePayAmount"] ;//- $result["CustomerDelay"];
				else
					$total += $pays[$i]["PurePayAmount"];
				
				$days = DateModules::GDateMinusGDate($pays[$i+1]["PurePayDate"], $pays[$i]["PurePayDate"]);
				$Z = ($total + $totalZ)*$partObj->CustomerWage*$days/36500;
				$totalZ += $Z;
				$paymentWage += $Z;
				$TotalPayedAmount += $pays[$i]["PurePayAmount"];
			}
		}
		$paymentWage = round($paymentWage);
		//................................
		$remainPure = $TotalPayedAmount;
		$totalWage = 0;
		$totalInstallmentAmounts = 0;
		for($i=0; $i < count($installmentArray); $i++)
		{
			$days = DateModules::JDateMinusJDate($installmentArray[$i]["InstallmentDate"],$ComputeDate);

			if($i == 0)
				$installmentArray[$i]["PureWage"] = round(
					($remainPure + $paymentWage)*
					$partObj->CustomerWage*$days/36500) + $paymentWage;   
			else
				$installmentArray[$i]["PureWage"] = round(
					$remainPure*$partObj->CustomerWage*$days/36500);  
			
			$remainPure -= $installmentArray[$i]["InstallmentAmount"] - $installmentArray[$i]["PureWage"];
			$ComputeDate  = $installmentArray[$i]["InstallmentDate"];
			$totalWage += $installmentArray[$i]["PureWage"];
			$totalInstallmentAmounts += $installmentArray[$i]["InstallmentAmount"];
		}
		//----------------- rounding up to 1000 --------------------	
		if($roundUp)
		{
			$difference = 0;
			for($i=0; $i<count($installmentArray);$i++)
			{
				if($i < count($installmentArray)-1)
				{
					$a = $installmentArray[$i]["InstallmentAmount"];
					$installmentArray[$i]["InstallmentAmount"] = LON_Computes::roundUp($a,-3);
					$difference += $installmentArray[$i]["InstallmentAmount"] - $a;
				}
				else
				{
					$installmentArray[$i]["InstallmentAmount"] -= $difference;
				}
			}
		}
		//--------------------------------------------------
		$zarib = $totalWage/$totalInstallmentAmounts;
		$sumWages = 0;
		for($i=0; $i<count($installmentArray);$i++)
		{
			if($i < count($installmentArray)-1)
			{
				$installmentArray[$i]["wage"] = round($zarib*$installmentArray[$i]["InstallmentAmount"]);
				$sumWages += $installmentArray[$i]["wage"];
			}
			else
			{
				$installmentArray[$i]["wage"] = $totalWage - $sumWages;
			}
		}
		return $installmentArray;
	}
	
	static function Tanzil($amount, $wage, $Date, $StartDate){
		$Date = DateModules::miladi_to_shamsi($Date);
		$StartDate = DateModules::miladi_to_shamsi($StartDate);
		$days = DateModules::JDateMinusJDate($Date, $StartDate);

		return $amount/(1+($wage*$days/36500));
	}
	
	static function ComputePayments($RequestID, $ComputeDate = null, $pdo = null, $ComputePenalty = true){

		$bankCompute = false;
		$obj = LON_ReqParts::GetValidPartObj($RequestID);
		$LatePercent = $obj->LatePercent;
		$ForfeitPercent = $ComputePenalty ? $obj->ForfeitPercent : 0;
		
		$ComputeDate = $ComputeDate == null ? DateModules::Now() : DateModules::shamsi_to_miladi($ComputeDate,"-");;
		
		$returnArr = array();
		$records = PdoDataAccess::runquery("
			select * from (
				select InstallmentID id,'installment' type, 
				InstallmentDate RecordDate,InstallmentAmount RecordAmount,0 PayType, '' details, wage
				from LON_installments where RequestID=:r AND history='NO' AND IsDelayed='NO'
			union All
				select p.BackPayID id,'pay' type, substr(p.PayDate,1,10) RecordDate, 
					p.PayAmount/*+ifnull(p2.PayAmount,0)*/ RecordAmount, 
					p.PayType,'' details,0
				from LON_BackPays p
				left join ACC_IncomeCheques i using(IncomeChequeID)
				left join BaseInfo bi on(bi.TypeID=6 AND bi.InfoID=p.PayType)
				/*left join LON_BackPays p2 on(p2.PayType=" . BACKPAY_PAYTYPE_CORRECT . " AND p2.PayBillNo=p.BackPayID)*/
				where p.RequestID=:r 
					AND p.PayType<>" . BACKPAY_PAYTYPE_CORRECT . " 
					AND if(p.PayType=".BACKPAY_PAYTYPE_CHEQUE.",i.ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
					AND substr(p.PayDate,1,10) <= :tdate
			union All
				select 0 id, 'installment' type, CostDate RecordDate, 
					0 RecordAmount,0, CostDesc details, CostAmount wage
				from LON_costs 
				where RequestID=:r AND CostAmount>0 AND substr(CostDate,1,10) <= :tdate
			union All
				select 0 id,'pay' type, CostDate RecordDate, 
					abs(CostAmount) RecordAmount,0, CostDesc details, 0 wage
				from LON_costs 
				where RequestID=:r AND CostAmount<0 AND substr(CostDate,1,10) <= :tdate
			)t
			order by substr(RecordDate,1,10),type, RecordAmount desc" , 
				
				array(":r" => $RequestID, ":tdate" => $ComputeDate), $pdo);
		
		$PayRecords = array();
		$TotalRemainEarly = 0;
		//-------------- init array ----------------------
		for($i=0; $i < count($records); $i++)
		{
			if($records[$i]["type"] == "installment")
			{
				$records[$i]["InstallmentID"] = $records[$i]["id"];
				
				if($records[$i]["id"] == "0") // is cost
				{
					$records[$i]["pure"] = 0;
					$records[$i]["wage"] = $records[$i]["wage"]*1;
				}
				else
				{
					if($obj->ComputeMode == "BANK" && $bankCompute)
					{
						$records[$i]["pure"] = $records[$i]["RecordAmount"]*1;
						$records[$i]["wage"] = 0;
					}
					else
					{
						$records[$i]["pure"] = $records[$i]["RecordAmount"]*1 - $records[$i]["wage"]*1;
						$records[$i]["wage"] = $records[$i]["wage"]*1;						
					}
				}
				$records[$i]["late"] = 0;
				$records[$i]["pnlt"] = 0;
				$records[$i]["totalearly"] = 0;
				$records[$i]["totallate"] = 0;
				$records[$i]["totalpnlt"] = 0;
				
				$records[$i]["remain_pure"] = $records[$i]["pure"];
				$records[$i]["remain_wage"] = $records[$i]["wage"];
				$records[$i]["remain_late"] = 0;
				$records[$i]["remain_pnlt"] = 0;
				$records[$i]["remain_early"] = 0;
				
				$records[$i]["pays"] = array();
			}
			if($records[$i]["type"] == "pay")
			{
				$records[$i]["BackPayID"] = $records[$i]["id"];
				$records[$i]["remainPayAmount"] = $records[$i]["RecordAmount"]*1;
				$records[$i]["pure"] = 0;
				$records[$i]["wage"] = 0;
				$records[$i]["late"] = 0;
				$records[$i]["pnlt"] = 0;
				$records[$i]["totalearly"] = 0;			
				$records[$i]["totallate"] = 0;
				$records[$i]["totalpnlt"] = 0;
				$records[$i]["remain_pure"] = 0;
				$records[$i]["remain_wage"] = 0;
				$records[$i]["remain_late"] = 0;
				$records[$i]["remain_pnlt"] = 0;	
				$records[$i]["remain_early"] = 0;
				$records[$i]["MainIndex"] = $i;
				
				$PayRecords[] = &$records[$i];
			}
		}				
		//-------------- start computes -----------------
		for($j=0; $j<count($PayRecords); $j++)
		{
			$PayRecord = &$PayRecords[$j];
			
			//------------ compute totals -----------------
			$total_pure = $total_wage = $total_late = $total_pnlt = 0;
			for($i=0; $i<$PayRecords[$j]["MainIndex"]; $i++)
			{
				if($records[$i]["type"] != "installment")
					continue;
				
				if($records[$i]["remain_pure"] == 0 && 
					$records[$i]["remain_wage"] == 0 &&
					$records[$i]["remain_late"] == 0 && 
					$records[$i]["remain_pnlt"] == 0)
					continue;
				
				$diffDays = DateModules::GDateMinusGDate($PayRecord["RecordDate"],$records[$i]["RecordDate"]);
				if($diffDays > 0)
				{
					if(count($records[$i]["pays"])>0)
					{
						$pays = $records[$i]["pays"];
						$toDate = $pays[ count($pays)-1 ]["PayedDate"];
						$diffDays = DateModules::GDateMinusGDate(
								$PayRecord ? $PayRecord["RecordDate"] : $ComputeDate, 
								max($toDate,$records[$i]["RecordDate"]));
						
						if($pays[ count($pays)-1 ]["PayedDate"] != $PayRecords[$j-1]["RecordDate"])
						{
							if($obj->ComputeMode == "BANK" && $bankCompute)
							{
								if($obj->PayCompute == "forfeit")
								{
									$records[$i]["remain_late"] -= $records[$i]["late"];
									$records[$i]["remain_pnlt"] -= $records[$i]["pnlt"];
								}
							}
							else
							{
								$records[$i]["remain_late"] -= $records[$i]["late"];
								$records[$i]["remain_pnlt"] -= $records[$i]["pnlt"];
								$records[$i]["totallate"] -= $records[$i]["late"];
								$records[$i]["totalpnlt"] -= $records[$i]["pnlt"];
							}
						}
					}
					else
					{
						$records[$i]["remain_late"] = 0;
						$records[$i]["remain_pnlt"] = 0;
						$records[$i]["totallate"] = 0;
						$records[$i]["totalpnlt"] = 0;
					}
					
					if($records[$i]["remain_pure"] > 0)
					{
						$Late = round($records[$i]["remain_pure"]*$LatePercent*$diffDays/36500);
						$Pnlt = round($records[$i]["remain_pure"]*$ForfeitPercent*$diffDays/36500);
						
						$min = min($TotalRemainEarly,$Late);
						$TotalRemainEarly -= $min;
						$Late -= $min;
									
						$records[$i]["late"] = $Late;
						$records[$i]["pnlt"] = $Pnlt;
						$records[$i]["early"] = 0;
						$records[$i]["totallate"] += $Late;
						$records[$i]["totalpnlt"] += $Pnlt;
						$records[$i]["remain_pnlt"] += $Pnlt;
						$records[$i]["remain_late"] += $Late;
						$records[$i]["LastDiffDays"] = $diffDays;
					}
				}
				
				$total_pure += $records[$i]["remain_pure"];
				$total_wage += $records[$i]["remain_wage"];
				$total_late += $records[$i]["remain_late"];
				$total_pnlt += $records[$i]["remain_pnlt"];
			}
			//------------ minus pay from previous installments ------------------
			$total = $total_pure + $total_wage + $total_late + $total_pnlt;
			if($total > 0)
			{
				if($obj->ComputeMode == "BANK" && $bankCompute)
				{
					if($obj->PayCompute == "installment")
					{
						$remainPure = min($PayRecord["remainPayAmount"], $total_pure);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_pure);
						$remainWage = min($PayRecord["remainPayAmount"], $total_wage);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_wage);
						$remainLate = min($PayRecord["remainPayAmount"], $total_late);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_late);
						$remainPnlt = min($PayRecord["remainPayAmount"], $total_pnlt);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_pnlt);
					}
					else
					{
						$remainPnlt = min($PayRecord["remainPayAmount"], $total_pnlt);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_pnlt);
						$remainLate = min($PayRecord["remainPayAmount"], $total_late);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_late);
						$remainWage = min($PayRecord["remainPayAmount"], $total_wage);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_wage);
						$remainPure = min($PayRecord["remainPayAmount"], $total_pure);
						$PayRecord["remainPayAmount"] -= min($PayRecord["remainPayAmount"], $total_pure);
					}
				}
				else
				{
					$minPay = min($PayRecord["remainPayAmount"], $total);
					$PayRecord["remainPayAmount"] -= $minPay;
			
					$remainPure = round($minPay*$total_pure/$total);
					$remainWage = round($minPay*$total_wage/$total);
					$remainLate = round($minPay*$total_late/$total);
					$remainPnlt = round($minPay*$total_pnlt/$total);
				}
				
				
				for($k=0; $k<$PayRecords[$j]["MainIndex"]; $k++)
				{
					if($records[$k]["type"] != "installment")
						continue;

					$minPure =  $remainPure < 0 ? max($remainPure,$records[$k]["remain_pure"]) :
												  min($remainPure,$records[$k]["remain_pure"]);
					$minWage = min($remainWage,$records[$k]["remain_wage"]);
					$minLate = min($remainLate,$records[$k]["remain_late"]);
					$minPnlt = min($remainPnlt,$records[$k]["remain_pnlt"]);

					if($minPure == 0 && $minWage == 0 && $minLate == 0 && $minPnlt == 0) 
						continue;

					$records[$k]["remain_pure"] -= $minPure;
					$records[$k]["remain_wage"] -= $minWage;
					$records[$k]["remain_late"] -= $minLate;
					$records[$k]["remain_pnlt"] -= $minPnlt;

					$PayRecord["pure"] += $minPure;
					$PayRecord["wage"] += $minWage;
					$PayRecord["late"] += $minLate;
					$PayRecord["pnlt"] += $minPnlt;
					$PayRecord["totallate"] += $minLate;
					$PayRecord["totalpnlt"] += $minPnlt;

					$remainPure -= $minPure;
					$remainWage -= $minWage;
					$remainLate -= $minLate;
					$remainPnlt -= $minPnlt;

					$records[$k]["pays"][] = array(
						"BackPayID" => $PayRecord["BackPayID"],
						"PnltDays" => 0,
						"PnltDays" => 0,
						"cur_late" => $records[$k]["late"],
						"cur_pnlt" => $records[$k]["pnlt"],
						"remain_pure" => $records[$k]["remain_pure"],
						"remain_wage" => $records[$k]["remain_wage"],
						"remain_late" => $records[$k]["remain_late"],
						"remain_pnlt" => $records[$k]["remain_pnlt"],
						"pay_pure" => $minPure,
						"pay_wage" => $minWage,
						"pay_late" => $minLate,
						"pay_pnlt" => $minPnlt,
						"PnltDays" => isset($records[$k]["LastDiffDays"]) ? $records[$k]["LastDiffDays"] : 0,
						"remain" => $records[$k]["remain_pure"] + $records[$k]["remain_wage"] + 
									$records[$k]["remain_late"] + $records[$k]["remain_pnlt"],
						"PayedDate" => $PayRecord["RecordDate"],
						"PayedAmount" => $minPure + $minWage + $minLate + $minPnlt
					);
					
					if($remainPure == 0 && $remainWage == 0 && $remainLate == 0 && $remainPnlt == 0)
						break;
				}			
				if($PayRecord["remainPayAmount"] == 0)
				{
					$sum = $PayRecord["pure"] + $PayRecord["wage"] + $PayRecord["late"] +
						$PayRecord["pnlt"];
					if($sum != $PayRecord["RecordAmount"])
						$PayRecord["pure"] += $PayRecord["RecordAmount"] - $sum;
				}
			}
			
			if($PayRecord["remainPayAmount"] == 0)
				continue;
			//----------- minus pay from next installment --------------
			for($k = $PayRecords[$j]["MainIndex"]+1;$k < count($records); $k++)
			{
				if($records[$k]["type"] != "installment")
					continue;
				
				$total = $records[$k]["remain_pure"] + $records[$k]["remain_wage"];
				if($total > 0)
				{
					if($obj->ComputeMode == "BANK" && $bankCompute)
					{
						$diffDays = 0;
						$EarlyAmount = 0;
					}
					else if($obj->ComputeMode == "NOAVARI")
					{
						$diffDays = 0;
						$EarlyAmount = 0;
					}
					else
					{
						$diffDays = DateModules::GDateMinusGDate($records[$k]["RecordDate"],$PayRecord["RecordDate"]);
						$tmp = min($PayRecord["remainPayAmount"], $total);
						$pure = round($tmp*$records[$k]["remain_pure"]/$total);
						$EarlyPercent = $obj->CustomerWage*1 - 3;
						$EarlyPercent = $EarlyPercent < 0 ? 0 : $EarlyPercent; 
						$EarlyAmount = $records[$k]["remain_pure"] > 0 ? round($pure*$EarlyPercent*abs($diffDays)/36500) : 0;
						
						if($obj->ComputeMode == "BANK")
						{
							$diffDays = 0;
							$EarlyAmount = 0;
						}
					}
					$total = $records[$k]["remain_pure"] + $records[$k]["remain_wage"];
					$tmp = min($PayRecord["remainPayAmount"], $total);
					$pure = round($tmp*$records[$k]["remain_pure"]/$total);
					$wage = round($tmp*$records[$k]["remain_wage"]/$total);
		
					$TotalRemainEarly += $EarlyAmount;
					$records[$k]["totalearly"] += $EarlyAmount;
					$records[$k]["remain_pure"] -= $pure;
					$records[$k]["remain_wage"] -= $wage;

					$records[$k]["pays"][] = array(
						"BackPayID" => $PayRecord["BackPayID"],
						"PnltDays" => 0,
						"cur_late" => 0,
						"cur_pnlt" => 0,
						"remain_pure" => $records[$k]["remain_pure"],
						"remain_wage" => $records[$k]["remain_wage"],
						"remain_late" => $records[$k]["remain_late"],
						"remain_pnlt" => $records[$k]["remain_pnlt"],
						"pay_pure" => $pure,
						"pay_wage" => $wage,						
						"pay_late" => 0,
						"pay_pnlt" => 0,
						"remain" => $records[$i]["remain_pure"] + $records[$i]["remain_wage"] ,
						"PayedDate" => $PayRecord["RecordDate"],
						"PayedAmount" => $tmp
						
					);

					$PayRecord["remainPayAmount"] -= $tmp;
					$PayRecord["pure"] += $pure;
					$PayRecord["wage"] += $wage;
					//$PayRecord["totallate"] -= $EarlyAmount;
				}

				if($PayRecord["remainPayAmount"] == 0)
					break;					
			}
		}
		//--------------- compute forfeit until ToDate ---------------------
		for($i=0; $i < count($records); $i++)
		{
			if($records[$i]["type"] != "installment")
				continue;
			
			if($records[$i]["remain_pure"] == 0 && 
					$records[$i]["remain_wage"] == 0 &&
					$records[$i]["remain_late"] == 0 && 
					$records[$i]["remain_pnlt"] == 0)
					continue;
			
			$diffDays = DateModules::GDateMinusGDate($ComputeDate, $records[$i]["RecordDate"]);
			if($diffDays <= 0)
				break;

			if(count($records[$i]["pays"])>0)
			{
				$pays = $records[$i]["pays"];
				$toDate = $pays[ count($pays)-1 ]["PayedDate"];
				$diffDays = DateModules::GDateMinusGDate($ComputeDate, 
						max($toDate,$records[$i]["RecordDate"]));
				
				if($pays[ count($pays)-1 ]["PayedDate"] != $PayRecords[count($PayRecords)-1]["RecordDate"])
				{
					if($obj->ComputeMode == "BANK" && $bankCompute)
					{
						if($obj->PayCompute == "forfeit")
						{	
							$records[$i]["remain_late"] -= $records[$i]["late"];
							$records[$i]["remain_pnlt"] -= $records[$i]["pnlt"];
							$records[$i]["late"] = 0;
							$records[$i]["pnlt"] = 0;
						}
					}
					else
					{	
						$records[$i]["remain_late"] -= $records[$i]["late"];
						$records[$i]["remain_pnlt"] -= $records[$i]["pnlt"];
						$records[$i]["totallate"] -= $records[$i]["late"];
						$records[$i]["totalpnlt"] -= $records[$i]["pnlt"];
						$records[$i]["late"] = 0;
						$records[$i]["pnlt"] = 0;
					}
				}
			}
			else
			{
				$records[$i]["late"] = 0;
				$records[$i]["pnlt"] = 0;
				$records[$i]["remain_late"] = 0;
				$records[$i]["remain_pnlt"] = 0;
				$records[$i]["totallate"] = 0;
				$records[$i]["totalpnlt"] = 0;
			}

			if($records[$i]["remain_pure"] > 0)
			{
				$Late = round($records[$i]["remain_pure"]*$LatePercent*$diffDays/36500);
				$Pnlt = round($records[$i]["remain_pure"]*$ForfeitPercent*$diffDays/36500);

				$min1 = min($Late,$TotalRemainEarly);
				$Late -= $min1;
				$TotalRemainEarly -= $min1;

				$records[$i]["late"] = $Late;
				$records[$i]["pnlt"] = $Pnlt;
				$records[$i]["early"] = 0;
				$records[$i]["remain_late"] += $Late;
				$records[$i]["remain_pnlt"] += $Pnlt;
				$records[$i]["totallate"] += $Late;
				$records[$i]["totalpnlt"] += $Pnlt;
				$records[$i]["LastDiffDays"] = $diffDays;
				
				$records[$i]["pays"][] = array(
					"BackPayID" => 0,
					"PnltDays" => $diffDays,
					"cur_late" => $records[$i]["late"],
					"cur_pnlt" => $records[$i]["pnlt"],
					"remain_pure" => $records[$i]["remain_pure"],
					"remain_wage" => $records[$i]["remain_wage"],
					"remain_late" => $records[$i]["remain_late"],
					"remain_pnlt" => $records[$i]["remain_pnlt"],
					"pay_pure" => 0,
					"pay_wage" => 0,
					"pay_late" => 0,
					"pay_pnlt" => 0,
					"remain" => $records[$i]["remain_pure"] + $records[$i]["remain_wage"] + 
								$records[$i]["remain_late"] + $records[$i]["remain_pnlt"],
					"PayedDate" => $ComputeDate,
					"PayedAmount" => 0
				);
			}
		}

		//--------------------- TotalRemainEarly -------------------
		if($TotalRemainEarly > 0)
		{
			for($i=0; $i < count($records); $i++)
			{
				if($records[$i]["type"] != "installment" || $records[$i]["id"] == 0)
					continue;

				if($records[$i]["remain_pure"] == 0 && 
						$records[$i]["remain_wage"] == 0 &&
						$records[$i]["remain_late"] == 0 && 
						$records[$i]["remain_pnlt"] == 0)
						continue;

				$records[$i]["remain_late"] -= $TotalRemainEarly;
				$records[$i]["totallate"] -= $TotalRemainEarly;
				$TotalRemainEarly = 0;
				break;
			}
			if($TotalRemainEarly > 0)
			{
				for($i=count($records)-1; $i >= 0; $i--)
				{
					if($records[$i]["type"] != "installment" || $records[$i]["id"] == 0)
						continue;

					$records[$i]["remain_late"] -= $TotalRemainEarly;
					$records[$i]["totallate"] -= $TotalRemainEarly;
					$TotalRemainEarly = 0;
					break;
				}
			}
		}
		return $records;
	}

	/**
	 * مانده قابل پرداخت معوقه وام
	 * @param type $RequestID
	 * @param type $computeArr
	 * @param type $ToDate
	 * @return type
	 */
	static function GetCurrentRemainAmount($RequestID, $computeArr=null, $ToDate = null){
		
		$ToDate = $ToDate == null ? DateModules::Now() : DateModules::shamsi_to_miladi($ToDate,"-");;
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID, $ToDate);
		
		$CurrentRemain = 0;
		foreach($computeArr as $row)
		{
			if($row["RecordDate"] < $ToDate)
			{
				$CurrentRemain += $row["remain_pure"]*1 + $row["remain_wage"]*1 + 
									$row["remain_late"]*1 + $row["remain_pnlt"]*1;
			}
			else
				break;
		}
		return $CurrentRemain;
	}
	
	/**
	 * مانده های معوقه وام
	 * @param type $RequestID
	 * @param type $computeArr
	 * @param type $forfeitInclude
	 * @return type
	 */
	static function GetRemainAmounts($RequestID, $computeArr=null, $ToDate = null){
		
		$ToDate = $ToDate == null ? DateModules::Now() : DateModules::shamsi_to_miladi($ToDate,"-");;
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID, $ToDate);
		 
		$result = array(
			"remain_pure" => 0,
			"remain_wage" => 0,
			"remain_late" => 0,
			"remain_pnlt" => 0
		);
		foreach($computeArr as $row)
		{
			if($row["RecordDate"] < $ToDate)
			{
				$result["remain_pure"] += $row["remain_pure"]*1;
				$result["remain_wage"] += $row["remain_wage"]*1;
				$result["remain_late"] += $row["remain_late"]*1;
				$result["remain_pnlt"] += $row["remain_pnlt"]*1;
				
			}
			else
				break;
		}
		return $result;
	}
	
	/**
	 * کل مانده وام
	 * @param type $RequestID
	 * @param type $computeArr
	 * @return int
	 */
	static function GetTotalRemainAmount($RequestID, $computeArr=null){
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
		
		$totalRemain = 0;
		foreach($computeArr as $row)
		{
			if($row["type"] == "installment")
				$totalRemain += $row["remain_pure"]*1 + $row["remain_wage"]*1 + 
							$row["remain_late"]*1 + $row["remain_pnlt"]*1;
			else
				$totalRemain -= $row["remainPayAmount"]*1;
				
		}
		return $totalRemain;
		
	}
	
	/**
	 * جدول دوم محسابه مرحله ایی اصل و کارمزد تا انتها که باید به صفر برسد
	 * @param type $RequestID
	 * @return array
	 */
	static function ComputePures($RequestID){
		
		$PartObj = LON_ReqParts::GetValidPartObj($RequestID);
		$temp = LON_installments::GetValidInstallments($RequestID);

		//.............................
		$returnArr = array();
		$pays = LON_payments::Get(" AND p.RequestID=?", array($RequestID), " order by PayDate");
		$pays = $pays->fetchAll();
		$totalPure = 0;
		$totalZ = 0;
		for($i=0; $i<count($pays); $i++)
		{
			$totalPure += $pays[$i]["PurePayAmount"]/* - ($i==0 ? $extra : 0)*/;
			$returnArr[] = array(
				"InstallmentDate" => $pays[$i]["PurePayDate"],
				"InstallmentAmount" => 0,
				"wage" => 0,
				"pure" => 0,
				"totalPure" => $totalPure + $totalZ
			);
			$days = DateModules::GDateMinusGDate(
				$i+1 <count($pays) ? $pays[$i+1]["PurePayDate"] : $temp[0]["InstallmentDate"],$pays[$i]["PurePayDate"]);
			$totalZ += ($totalPure + $totalZ)*$days*$PartObj->CustomerWage/36500;
		}
		
		for($i=0; $i< count($temp); $i++)
		{
			$row = $temp[$i];
			$totalPure -= $row["InstallmentAmount"] - $row["PureWage"];
			$returnArr[] = array(
				"InstallmentDate" => $row["InstallmentDate"],
				"InstallmentAmount" => $row["InstallmentAmount"],
				"wage" => $row["PureWage"],
				"pure" => $row["InstallmentAmount"] - $row["PureWage"],
				"totalPure" => $totalPure 
			);
		}
		
		return $returnArr;

	}

	/**
	 * کل مبلغ جرایم وام
	 * @param type $RequestID
	 * @param type $computeArr
	 * @return int
	 */
	static function GetTotalForfeitAmount($RequestID, $computeArr=null){
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
		
		if(count($computeArr) == 0)
			return 0;
		$total = 0;
		foreach($computeArr as $row)
			if($row["type"] == "installment")
				$total += $row["pnlt"]*1;
		
		return $total;		
	}
	
	/**
	 * کل مبلغ تعجیل وام
	 * @param type $RequestID
	 * @param type $computeArr
	 * @return int
	 */
	static function GetTotalEarlyAmount($RequestID, $computeArr=null){
		
		if($computeArr == null)
			$computeArr = LON_Computes::ComputePayments($RequestID);
		
		if(count($computeArr) == 0)
			return 0;
		$total = 0;
		foreach($computeArr as $row)
			if($row["type"] == "installment")
				$total += $row["totalearly"]*1;
		
		return $total;		
	}
}

class LON_difference extends PdoDataAccess{

	public $DocObj;
	public $ReqObj;
	public $PartObj;
	public $pdo;
	
	/**
	 * رویداد عقد قرارداد
	 */
	function Contract(){

		$EventID = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanContract");
		if($EventID == 0)
		{
			ExceptionHandler::PushException("رویداد عقد قرارداد یافت نشد");
			return false;
		}

		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocObj = $this->DocObj;
		$eventobj->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID);
		$result = $eventobj->RegisterEventDoc($this->pdo);
		if($result)
			$this->DocObj = $eventobj->DocObj;
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			return false;
		}
		return true;
	}

	function Payments(){
		
		$result = true;
		$pays = PdoDataAccess::runquery("select * from LON_payments where RequestID=?", 
				array($this->ReqObj->RequestID));
		$EventID = LON_requests::GetEventID($this->ReqObj->RequestID, EVENTTYPE_LoanPayment);
		
		foreach($pays as $pay)
		{
			$eventobj = new ExecuteEvent($EventID);
			$eventobj->AfterTriggerFunction = "";
			$eventobj->TriggerFunction = "";
			$eventobj->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID, $pay["PayID"]);
			$eventobj->DocObj = $this->DocObj;
			$result = $eventobj->RegisterEventDoc($this->pdo);
		}
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;
		return true;
	}
	
	function BackPay(){
		$result = true;
		$backpays = PdoDataAccess::runquery(
				"select * from LON_BackPays
					left join ACC_IncomeCheques i using(IncomeChequeID) 
					where RequestID=? AND PayDate<".PDONOW."
				AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
				order by PayDate", array($this->ReqObj->RequestID));

		foreach($backpays as $bpay)
		{
			if($bpay["IncomeChequeID"]*1 > 0)
				$EventID = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanBackPayCheque");
			else
				$EventID = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanBackPay");
			
			$eventobj = new ExecuteEvent($EventID);
			$eventobj->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID, $bpay["BackPayID"]);
			$eventobj->DocObj = $this->DocObj;
			$result = $eventobj->RegisterEventDoc($this->pdo);
			if($result)
				$this->DocObj = $eventobj->DocObj;
		}
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;
		return true;
	}

	function DailyDocs(){

		$eventID = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanDailyIncome");
		
		$GToDate = DateModules::Now();
		
		$EventObj = new ExecuteEvent($eventID);
		$EventObj->DocObj = $this->DocObj;
		$EventObj->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID);
		
		$EventObj->ComputedItems[ "80" ] = 0;
		$EventObj->ComputedItems[ "81" ] = 0;
		unset($EventObj->EventFunction);
		$PureArr = LON_requests::ComputePures($this->ReqObj->RequestID);
		$ComputeDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
		$days = 0;
		for($i=1; $i < count($PureArr);$i++)
		{
			if($ComputeDate >= $GToDate)
				break;
			$days = DateModules::GDateMinusGDate(min($GToDate, $PureArr[$i]["InstallmentDate"]),$ComputeDate);
			$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$ComputeDate);
			$wage = round(($PureArr[$i]["wage"]/$totalDays)*$days);
			$FundWage = round(($this->PartObj->FundWage/$this->PartObj->CustomerWage)*$wage);
			$AgentWage = $wage - $FundWage;
			$EventObj->ComputedItems[ "80" ] += $FundWage;
			$EventObj->ComputedItems[ "81" ] += $AgentWage;
			$ComputeDate = min($GToDate, $PureArr[$i]["InstallmentDate"]);
		}
		
		$EventObj->RegisterEventDoc($this->pdo);
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;
		
		//....................................................
		
		$computeArr = LON_Computes::ComputePayments($this->ReqObj->RequestID);
		$totalLate = 0;
		$totalPenalty = 0;
		foreach($computeArr as $row)
		{
			if($row["type"] == "installment" && $row["InstallmentID"]*1 > 0)
			{
				$totalLate += $row["totallate"]*1;
				$totalPenalty += $row["totalpnlt"]*1;
			}
		}
		
		
		$LateEvent = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanDailyLate");
		$PenaltyEvent = LON_requests::GetEventID($this->ReqObj->RequestID, "LoanDailyPenalty");
		
		$EventObj1 = new ExecuteEvent($LateEvent);
		$EventObj->DocObj = $this->DocObj;
		$EventObj1->ComputedItems[ 82 ] = round(($this->PartObj->FundWage/$this->PartObj->CustomerWage)*$totalLate);
		$EventObj1->ComputedItems[ 83 ] = $totalLate - round(($this->PartObj->FundWage/$this->PartObj->CustomerWage)*$totalLate);
		if($EventObj1->ComputedItems[ 82 ] > 0 || $EventObj1->ComputedItems[ 83 ] > 0)
		{
			$EventObj1->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID);
			$result = $EventObj1->RegisterEventDoc($this->pdo);
			if(ExceptionHandler::GetExceptionCount() > 0)
				return false;
		}
		
		
		$EventObj2 = new ExecuteEvent($PenaltyEvent);
		$EventObj->DocObj = $this->DocObj;
		$EventObj2->ComputedItems[ 84 ] = $this->PartObj->ForfeitPercent == 0? 0 :
				round(($this->PartObj->FundForfeitPercent/$this->PartObj->ForfeitPercent)*$totalPenalty);
		$EventObj2->ComputedItems[ 85 ] = $this->PartObj->ForfeitPercent == 0? 0 : 
				$totalPenalty - round(($this->PartObj->FundForfeitPercent/$this->PartObj->ForfeitPercent)*$totalPenalty);

		if($EventObj2->ComputedItems[ 84 ] > 0 || $EventObj2->ComputedItems[ 85 ] > 0)
		{
			$EventObj2->Sources = array($this->ReqObj->RequestID, $this->PartObj->PartID);
			$result = $EventObj2->RegisterEventDoc($this->pdo);
			if(ExceptionHandler::GetExceptionCount() > 0)
				return false;
		}

		return true;
   }
   
	static function RegisterDiffernce($RequestID, $pdo, $DocID=0){

		if(ACC_cycles::IsClosed())
		{
			echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
			die();	
		}

		ini_set('max_execution_time', 30000000);
		ini_set('memory_limit','4000M');

		$ReqObj = new LON_requests($RequestID, $pdo);
		$NewPartObj = LON_ReqParts::GetValidPartObj($RequestID, $pdo);

		if($DocID > 0)
		{
			$obj = new ACC_docs($DocID);
		}
		else
		{
			$obj = new ACC_docs();
			$obj->RegDate = PDONOW;
			$obj->regPersonID = $_SESSION['USER']["PersonID"];
			$obj->DocDate = PDONOW;
			$obj->CycleID = $_SESSION["accounting"]["CycleID"];
			$obj->BranchID = $ReqObj->BranchID;
			$obj->EventID = EVENT_LOAN_CHANGE; 
			$obj->description = "سند تغییر شرایط وام شماره " . $ReqObj->RequestID . 
					" به نام " . $ReqObj->_LoanPersonFullname;
			if(!$obj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد سند");
				return false;
			}
		}

		$process = new LON_difference();
		$process->ReqObj = $ReqObj;
		$process->PartObj = $NewPartObj;
		$process->pdo = $pdo;

		$process->Contract();
		$process->Payments();
		$process->BackPay();
		$process->DailyDocs();

		PdoDataAccess::runquery("update ACC_DocItems d join (select d.ItemID,DebtorAmount,CreditorAmount from ACC_DocItems d where DocID=?)t using(ItemID)
			set d.DebtorAmount = t.CreditorAmount,d.CreditorAmount = t.DebtorAmount ", array($process->DocObj->DocID), $process->pdo);

		PdoDataAccess::runquery("insert into ACC_DocItems(DocID, CostID, TafsiliID, TafsiliID2, TafsiliID3, 
			DebtorAmount, CreditorAmount, details, locked, SourceID1, SourceID2, SourceID3, SourceID4, 
			param1, param2, param3)

			select :d, di.CostID, di.TafsiliID, di.TafsiliID2, di.TafsiliID3, 
				if(sum(DebtorAmount-CreditorAmount)<0, abs(sum(DebtorAmount-CreditorAmount)), 0) DebtorAmount,
				if(sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0) CreditorAmount,
				di.details, di.locked, di.SourceID1, di.SourceID2, di.SourceID3, di.SourceID4, 
				di.param1, di.param2, di.param3
			from ACC_DocItems di join ACC_docs d using(DocID) join ACC_CostCodes cc using(CostID)
			where case when cc.param1=".CostCode_param_loan." then di.param1=:reqId
						when cc.param2=".CostCode_param_loan." then di.param2=:reqId
						when cc.param3=".CostCode_param_loan." then di.param3=:reqId
					end
			group by CostID, TafsiliID, TafsiliID2, TafsiliID3
			having DebtorAmount>0 or CreditorAmount>0
		", array(
			":cycle" => $_SESSION["accounting"]["CycleID"],
			":d" => $obj->DocID,
			":reqId" => $process->ReqObj->RequestID
		), $process->pdo);

		ACC_docs::Remove($process->DocObj->DocID, $pdo);
			
		return $obj;
	}

}

class LON_ReqParts extends PdoDataAccess{
	
	public $PartID;
	public $RequestID;
	public $PartDesc;
	public $PartDate;
	public $PartStartDate;
	public $PartAmount;
	public $InstallmentCount;
	public $IntervalType;
	public $PayInterval;
	public $DelayMonths;
	public $DelayDays;
	public $ForfeitPercent;
	public $DelayPercent;
	public $FundForfeitPercent;
	public $LatePercent;
	public $ForgivePercent;
	public $CustomerWage;
	public $FundWage;
	public $WageReturn;
	public $PayCompute;
	public $MaxFundWage;
	public $DelayReturn;
	public $AgentReturn;
	public $AgentDelayReturn;
	public $IsHistory;
	public $PayDuration;
	public $details;
	public $ComputeMode;
	public $BackPayCompute;
	
	public $_BackPayComputeDesc;
	public $_param1;
	public $_param2;
	public $_param3;
	public $_param4;
	
	function __construct($PartID = "", $pdo = null) {
		
		$this->DT_PartDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_PartStartDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_MaxFundWage = DataMember::CreateDMA(DataMember::DT_INT, 0);
		
		if($PartID != "")
			PdoDataAccess::FillObject ($this, "select p.*, 
					bf.InfoDesc _BackPayComputeDesc,
					bf.param1 _param1,
					bf.param2 _param2,
					bf.param3 _param3,
					bf.param4 _param4
				from LON_ReqParts p
				join BaseInfo bf on(TypeID=81 AND InfoID=BackPayCompute)
				where PartID=?", array($PartID), $pdo);
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery("
			select rp.*,r.StatusID,r.LoanPersonID,r.ReqPersonID, r.imp_VamCode,
				bf.InfoDesc BackPayComputeDesc,t.LocalNo,t.DocDate				
				
			from LON_ReqParts rp join LON_requests r using(RequestID)
			join BaseInfo bf on(TypeID=81 AND InfoID=BackPayCompute)
			left join (
				select SourceID2,LocalNo, DocDate from ACC_DocItems join ACC_docs using(DocID)
				where SourceType=".DOCTYPE_LOAN_DIFFERENCE."
				group by SourceID2
			)t on(SourceID2=rp.PartID)
			where " . $where, $param);
	}
	
	function AddPart($pdo = null){
		
		if (!parent::insert("LON_ReqParts", $this, $pdo)) {
			return false;
		}
		$this->PartID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->PartID;
		$daObj->TableName = "LON_ReqParts";
		$daObj->execute($pdo);
		return true;
	}
	
	function EditPart($pdo = null){
		
	 	if( parent::update("LON_ReqParts",$this," PartID=:l", array(":l" => $this->PartID), $pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->PartID;
		$daObj->TableName = "LON_ReqParts";
		$daObj->execute($pdo);
	 	return true;
    }
	
	static function DeletePart($PartID, $pdo = null){
		
		if( parent::delete("LON_ReqParts"," PartID=?", array($PartID),$pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $PartID;
		$daObj->TableName = "LON_ReqParts";
		$daObj->execute($pdo);
	 	return true;
	}
	
	static function GetValidPartObj($RequestID, $pdo=null){
		
		$dt = PdoDataAccess::runquery("select * from LON_ReqParts 
			where IsHistory='NO' AND RequestID=? order by PartID desc limit 1",array($RequestID), $pdo);
		if(count($dt) == 0)
			return null;
		
		return new LON_ReqParts($dt[0]["PartID"], $pdo);
	}
	
	static function GetRejectParts(){

		return PdoDataAccess::runquery("
			select r.RequestID ,concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) LoanPersonFullname
				from WFM_FlowRows fr
				join WFM_FlowSteps sp on(sp.FlowID=fr.FlowID AND fr.StepRowID=sp.StepRowID)
				join LON_ReqParts r on(PartID=ObjectID)
				join LON_requests using(RequestID)
				left join BSC_persons p1 on(p1.PersonID=LoanPersonID)
			where fr.FlowID=" . FLOWID_LOAN . " AND IsLastRow='YES' AND ActionType='REJECT' AND StepID=1");	
	}
}

class LON_installments extends PdoDataAccess{
	
	public $InstallmentID;
	public $RequestID;
	public $InstallmentDate;
	public $InstallmentAmount;
	public $wage;
	public $PureWage;
	public $PureFundWage;
	public $IsDelayed;
	public $history;
	public $ComputeType;
			
	function __construct($InstallmentID = "") {
		
		$this->DT_InstallmentDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_PaidDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($InstallmentID != "")
			PdoDataAccess::FillObject ($this, "select * from LON_installments where InstallmentID=?", array($InstallmentID));
	}
	
	static function SelectAll($where = "", $param = array()){
		
		return PdoDataAccess::runquery("
			select i.*,r.*,p.* , group_concat(distinct LocalNo) docs
			from LON_installments i
			join LON_requests r using(RequestID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join ACC_DocItems on(SourceType=" .DOCTYPE_INSTALLMENT_CHANGE. "
				AND SourceID1=i.RequestID AND SourceID2=i.InstallmentID)
			left join ACC_docs using(DocID)
			where " . $where . " group by i.InstallmentID", $param);
	}
	
	function AddInstallment($pdo = null){
		
	 	if(!parent::insert("LON_installments",$this, $pdo))
	 		return false;

		$this->InstallmentID = parent::InsertID($pdo);
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->InstallmentID;
		$daObj->TableName = "LON_installments";
		$daObj->execute($pdo);
	 	return true;
    }
	
	function EditInstallment($pdo = null){
		
	 	if( parent::update("LON_installments",$this," InstallmentID=:l", 
				array(":l" => $this->InstallmentID), $pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->InstallmentID;
		$daObj->TableName = "LON_installments";
		$daObj->execute($pdo);
	 	return true;
    }
	
	static function GetValidInstallments($RequestID, $pdo = null){
		
		return PdoDataAccess::runquery("select * from 
			LON_installments where RequestID=? AND history='NO' AND IsDelayed='NO'
			order by InstallmentDate", 
			array($RequestID), $pdo);
		
	}
	
	static function GetLastInstallmentObj($RequestID){ 
		
		$obj = new LON_installments();
		PdoDataAccess::FillObject($obj, "
			select *
			from LON_installments
			where RequestID=?
			order by InstallmentDate desc limit 1", array($RequestID));
		return $obj;
	}
	
		/**
	 * loan amount + delay if in installment + wage if in installment
	 */
	static function GetTotalInstallmentsAmount($RequestID){
		
		$dt = self::GetValidInstallments($RequestID);
		$amount = 0;
		foreach($dt as $row)
			$amount += $row["InstallmentAmount"];
		return $amount;
	}
	
	static function ComputeInstallments($RequestID = "", $pdo2 = null, $IsLastest = false){
	
		//------------------- check for docs -------------------
		/*$dt = PdoDataAccess::runquery("select * from ACC_DocItems
			join LON_installments on(SourceID1=RequestID AND SourceID2=InstallmentID)
			where SourceType=" . DOCTYPE_INSTALLMENT_CHANGE . " AND SourceID1=? AND 
				history='NO' AND IsDelayed='NO'", array($RequestID));
		if(count($dt) > 0)
		{
			if($returnMode)
				return false;

			echo Response::createObjectiveResponse(false, "DocExists");
			die();
		}*/
		//-----------------------------------------------
		$obj2 = new LON_requests($RequestID);
		$partObj = LON_ReqParts::GetValidPartObj($RequestID);
		
		if($obj2->IsLock == "YES" && !$IsLastest)
		{
			ExceptionHandler::PushException("وام موربوطه قفل بوده و قادر به محاسبه اقساط نمی باشید.");
			return false;
		}
		

		if($partObj->ComputeMode == "NOAVARI")
		{
			$payments = LON_payments::Get(" AND RequestID=?", array($RequestID), "order by PayDate");
			$payments = $payments->fetchAll();
			if(count($payments) == 0)
				return true;

			PdoDataAccess::runquery("delete from LON_installments "
					. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID));

			//--------------- total pay months -------------
			$paymentPeriod = $partObj->PayDuration*1;
			//----------------------------------------------	
			$totalWage = LON_NOAVARI_compute::ComputeWage($partObj);
			
			if($pdo2 == null)
			{
				$pdo = PdoDataAccess::getPdoObject();
				$pdo->beginTransaction();
			}
			else
				$pdo = $pdo2;

			for($i=0; $i < $partObj->InstallmentCount; $i++)
			{
				$installmentDate = DateModules::miladi_to_shamsi($payments[0]["PayDate"]);
				$monthplus = $paymentPeriod + $partObj->DelayMonths*1;
				$dayplus = 0;

				if($partObj->DelayDays*1 > 0)
					$dayplus += $partObj->DelayDays*1;

				if($partObj->IntervalType == "MONTH")
					$monthplus += ($i+1)*$partObj->PayInterval*1;
				else
					$dayplus += ($i+1)*$partObj->PayInterval*1;

				$installmentDate = DateModules::AddToJDate($installmentDate, + $dayplus, $monthplus);
				$installmentDate = DateModules::shamsi_to_miladi($installmentDate);

				$obj2 = new LON_installments();
				$obj2->RequestID = $RequestID;
				$obj2->InstallmentDate = $installmentDate;
				$obj2->wage = round($totalWage/$partObj->InstallmentCount);
				$obj2->PureWage = round($totalWage/$partObj->InstallmentCount);
				$obj2->InstallmentAmount = round($partObj->PartAmount/$partObj->InstallmentCount) + 
						round($totalWage/$partObj->InstallmentCount);

				if($totalWage == 0 && $partObj->CustomerWage > 0)
				{
					$ConstantWage = $partObj->PartAmount*$partObj->CustomerWage/100;
					$obj2->wage = round($ConstantWage/$partObj->InstallmentCount);
					$obj2->PureWage = $obj2->wage;
				}				
				
				if(!$obj2->AddInstallment($pdo))
				{
					if($pdo2 == null)
						$pdo->rollBack();
					return false;
				}
			}

			if($pdo2 == null)
				$pdo->commit();	
			return true;
		}
		//------------------------------------------------------

		if($pdo2 == null)
		{
			$pdo = PdoDataAccess::getPdoObject();
			$pdo->beginTransaction();
		}
		else
			$pdo = $pdo2;
		//-----------------------------------------------
		if($partObj->ComputeMode == "NEW")
		{
			$RawInstallmentArray = array();
			$jdate = DateModules::miladi_to_shamsi($partObj->PartDate);
			$jdate = DateModules::AddToJDate($jdate, $partObj->DelayDays, $partObj->DelayMonths);
			$jdate = DateModules::AddToJDate($jdate, 0, $partObj->PayDuration*1);

			$temp = PdoDataAccess::runquery("select * from LON_installments "
					. " where RequestID=? AND IsDelayed='NO' AND history='NO'", array($RequestID));
			if($IsLastest && count($temp) > 0)
			{
				for ($i = 0; $i < count($temp); $i++) {
					$RawInstallmentArray[] = array(
						"InstallmentAmount" => $temp[$i]["InstallmentAmount"],
						"InstallmentDate" => DateModules::miladi_to_shamsi($temp[$i]["InstallmentDate"])
					);
				}	
				$ComputeWage = "NO";
			}
			else
			{
				for($i=0; $i < $partObj->InstallmentCount; $i++)
				{
					$RawInstallmentArray[] = array(
						"InstallmentAmount" => 0,
						"InstallmentDate" => DateModules::AddToJDate($jdate, 
						$partObj->IntervalType == "DAY" ? $partObj->PayInterval*($i+1) : 0, 
						$partObj->IntervalType == "MONTH" ? $partObj->PayInterval*($i+1) : 0)
					);
				}
				$ComputeWage = "YES";
			}
			PdoDataAccess::runquery("delete from LON_installments "
				. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID));

			$installmentArray = LON_Computes::ComputeInstallment($partObj, $RawInstallmentArray, $ComputeWage);
			if(!$installmentArray)
			{
				if($pdo2 == null)
					$pdo->rollBack();
				return false;
			}

			//--------- compute fundwage for fundWage>customerWage -------------
			$installmentArray2 = null;
			if($partObj->FundWage > $partObj->CustomerWage)
			{
				$partObj->CustomerWage = $partObj->FundWage - $partObj->CustomerWage;
				$installmentArray2 = LON_Computes::ComputeInstallment($partObj, $RawInstallmentArray, null, $ComputeWage);
			}
			//------------------------------------------------------------------
			
			for($i=0; $i < count($installmentArray); $i++)
			{
				$obj = new LON_installments();
				$obj->RequestID = $RequestID;
				$obj->InstallmentDate = DateModules::shamsi_to_miladi($installmentArray[$i]["InstallmentDate"]);
				$obj->InstallmentAmount = $installmentArray[$i]["InstallmentAmount"];
				$obj->wage = $installmentArray[$i]["wage"];
				$obj->PureWage = $installmentArray[$i]["PureWage"]; 
				$obj->PureFundWage = $installmentArray2 == null ? 0 : $installmentArray2[$i]["PureWage"];
				if(!$obj->AddInstallment($pdo)) 
				{
					if($pdo2 == null)
						$pdo->rollBack();
					return false;
				}
			}			
		}
		if($partObj->ComputeMode == "PERCENT")
		{
			$TotalPure = LON_payments::GetTotalPureAmount($partObj->RequestID, $partObj);
			$result = LON_requests::GetWageAmounts($partObj->RequestID, $partObj, $TotalPure);
			$TotalAmount = $TotalPure;
			
			if($partObj->WageReturn == "INSTALLMENT")
			{
				if($partObj->FundWage <= $partObj->CustomerWage)
					$TotalAmount += $result["FundWage"];
				else
					$TotalAmount += $result["CustomerWage"];
			}
			if($partObj->AgentReturn == "INSTALLMENT")
				$TotalAmount += $result["AgentWage"];
			
			$totalWage = $TotalAmount - $TotalPure;
			$zarib = $totalWage/$TotalAmount;
			
			$temp = PdoDataAccess::runquery("select * from LON_installments "
					. " where RequestID=? AND IsDelayed='NO' AND history='NO' "
					. " order by InstallmentDate", array($RequestID));
			if($IsLastest && count($temp) > 0)
			{
				$totalInstalmentAmount = 0;
				$totalInstalmentWage = 0;
				for ($i = 0; $i < count($temp)-1; $i++)
				{
					$totalInstalmentAmount += $temp[$i]["InstallmentAmount"]*1;
					
					$obj2 = new LON_installments($temp[$i]["InstallmentID"]);
					$obj2->wage = round($zarib*$temp[$i]["InstallmentAmount"]);
					$obj2->PureWage = $obj2->wage;
					$obj2->EditInstallment($pdo);
				
					$totalInstalmentWage += $obj2->wage;
				}
				
				$obj2 = new LON_installments($temp[count($temp)-1]["InstallmentID"]);
				$obj2->InstallmentAmount = $TotalAmount - $totalInstalmentAmount;
				$obj2->wage = $totalWage - $totalInstalmentWage;
				$obj2->PureWage = $obj2->wage;
				if(!$obj2->EditInstallment($pdo))
				{
					if($pdo2 == null)
						$pdo->rollBack();
					return false;
				}
				if($pdo2 == null)
					$pdo->commit();	
				return true;
			}
			
			
			PdoDataAccess::runquery("delete from LON_installments "
				. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID));
			
			$allPay = $TotalAmount/$partObj->InstallmentCount;

			if($partObj->InstallmentCount > 1)
				$allPay = LON_Computes::roundUp($allPay,-3);
			else
				$allPay = round($allPay);

			$LastPay = $TotalAmount - $allPay*($partObj->InstallmentCount-1);

			//---------------------------------------------------------------------

			$jdate = DateModules::miladi_to_shamsi($partObj->PartDate);
			$jdate = DateModules::AddToJDate($jdate, $partObj->DelayDays, $partObj->DelayMonths);

			$totalInstalmentWage = 0;
			for($i=0; $i < $partObj->InstallmentCount; $i++)
			{
				$obj2 = new LON_installments();
				$obj2->RequestID = $RequestID;

				$obj2->InstallmentDate = DateModules::AddToJDate($jdate, 
					$partObj->IntervalType == "DAY" ? $partObj->PayInterval*($i+1) : 0, 
					$partObj->IntervalType == "MONTH" ? $partObj->PayInterval*($i+1) : 0);
				
				if( $i == $partObj->InstallmentCount*1-1)
				{
					$obj2->InstallmentAmount = $allPay;
					$obj2->wage = round($zarib*$allPay); 
					$obj2->PureWage = $obj2->wage;
					$totalInstalmentWage += $obj2->wage;
				}
				else 
				{
					$obj2->InstallmentAmount = $LastPay;
					$obj2->wage = $totalWage - $totalInstalmentWage;
					$obj2->PureWage = $obj2->wage;
				}
				if(!$obj2->AddInstallment($pdo))
				{
					if($pdo2 == null)
						$pdo->rollBack();
					return false;
				}
			}
		}

		if($pdo2 == null)
			$pdo->commit();	
		return true;
	}
}

class LON_BackPays extends PdoDataAccess{
	
	public $BackPayID;
	public $RequestID;
	public $PayType;
	public $PayDate;
	public $PayAmount;
	public $PayRefNo;
	public $PayBillNo;
	public $details;
	public $IsGroup;
	public $IncomeChequeID;
	public $EqualizationID;
	
	function __construct($BackPayID = "") {
		
		$this->DT_PayDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($BackPayID != "")
			PdoDataAccess::FillObject ($this, "
				select *
				from LON_BackPays 
				where BackPayID=?", array($BackPayID));
	}
	
	static function SelectAll($where = "", $param = array()){
		
		$temp = preg_split("/order by/", $where);
		$where = $temp[0];
		$order = count($temp) > 1 ? " order by " . $temp[1] : "";
		
		return PdoDataAccess::runquery("
			select p.*,
		 		i.ChequeNo,
				i.ChequeStatus,
				t.TafsiliDesc ChequeStatusDesc,
				bi.InfoDesc PayTypeDesc,
				bd.DocID,
				bd.LocalNo
			from LON_BackPays p
			left join BaseInfo bi on(bi.TypeID=6 AND bi.InfoID=p.PayType)
			left join ACC_IncomeCheques i using(IncomeChequeID)
			left join ACC_tafsilis t on(t.TafsiliType=".TAFTYPE_ChequeStatus." AND t.TafsiliID=ChequeStatus)
			
			left join LON_BackPayDocs bd on(bd.BackPayID=p.BackPayID)
			
			where " . $where . " group by BackPayID " . $order, $param);
	}
	
	function Add($pdo = null){
		
	 	if(!parent::insert("LON_BackPays",$this, $pdo))
	 		return false;
		
		$this->BackPayID = parent::InsertID($pdo);

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->BackPayID;
		$daObj->TableName = "LON_BackPays";
		$daObj->execute($pdo);
	 	return true;
    }
	
	function Edit($pdo = null){
		
	 	if( parent::update("LON_BackPays",$this," BackPayID=:l", 
				array(":l" => $this->BackPayID), $pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->BackPayID;
		$daObj->TableName = "LON_BackPays";
		$daObj->execute($pdo);
	 	return true;
    }
	
	static function DeletePay($BackPayID, $pdo = null){
		
		if( parent::delete("LON_BackPays"," BackPayID=?", array($BackPayID), $pdo) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $BackPayID;
		$daObj->TableName = "LON_BackPays";
		$daObj->execute($pdo);
	 	return true;
	}
	
	static function GetAccDoc($BackPayID, $pdo = null){
		
		$obj = new LON_BackPays($BackPayID);
		
		$dt = PdoDataAccess::runquery("
			select DocID from ACC_DocItems where SourceType=" . DOCTYPE_INSTALLMENT_PAYMENT . " 
			AND SourceID1=? AND SourceID2=?" , array($obj->RequestID, $obj->BackPayID), $pdo);
		if(count($dt) == 0)
			return 0;
		return $dt[0][0];
	}
	
	static function GetRealPaid($RequestID){
		
		return LON_BackPays::SelectAll(" p.RequestID=? 
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate"
			, array($RequestID));
	}
	
	static function EventTrigger_extraPay($SourceObjects, $eventObj, $pdo){
		
		return true;
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$BackPayObj = new LON_BackPays((int)$SourceObjects[2]);
		
		$ComputeArr = EventComputeItems::$LoanComputeArray[ $ReqObj->RequestID ];
		foreach($ComputeArr as $row)
		{
			if($row["type"] != "pay" || $row["BackPayID"] != $BackPayObj->BackPayID)
				continue;
			
			if($row["remainPayAmount"] == 0)
				return true;
			
			$backPayObj = new LON_BackPays();
			$backPayObj->RequestID = $BackPayObj->RequestID;
			$backPayObj->PayAmount = $row["remainPayAmount"]*1;
			$backPayObj->PayDate = $BackPayObj->PayDate;
			$backPayObj->PayType = BACKPAY_PAYTYPE_CORRECT;
			$backPayObj->PayBillNo = $BackPayObj->BackPayID;
			$backPayObj->details = "بابت اضافه پرداختی مشتری و انتقال به حساب قرض الحسنه";
			$backPayObj->Add($pdo);	
			
			$BackPayObj->PayAmount = $BackPayObj->PayAmount - $row["remainPayAmount"]*1;
			$BackPayObj->Edit($pdo);
			return true;
		}
		return true;
	}
}

class LON_payments extends OperationClass{
	
	const TableName = "LON_payments";
	const TableKey = "PayID";

	public $PayID;
	public $RequestID;
	public $PayType;
	public $PayDate;
	public $PayAmount; 
	public $RealPayedDate;
	
	public $OldFundDelayAmount;
	public $OldAgentDelayAmount;
	public $OldFundWage;
	public $OldAgentWage;
	
	public $_PurePayedAmount;
	
	function __construct($PayID = "") {
		
		$this->DT_PayDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_RealPayedDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($PayID != "")
			parent::FillObject ($this, "select *,
				PayAmount - ifnull(OldFundDelayAmount,0) 
						- ifnull(OldAgentDelayAmount,0)
						- ifnull(OldFundWage,0)
						- ifnull(OldAgentWage,0)as _PurePayedAmount
				from LON_payments  
				where payID=?", array($PayID));
	}
	
	static function Get($where = '', $whereParams = array(), $order = "") {
		
		return parent::runquery_fetchMode("
			select p.*,
				d.DocID,
				d.LocalNo,
				d.StatusID,
				PayAmount - ifnull(OldFundDelayAmount,0) 
						- ifnull(OldAgentDelayAmount,0)
						- ifnull(OldFundWage,0)
						- ifnull(OldAgentWage,0)as PurePayAmount,
				if(RealPayedDate is null or d.DociD is null, PayDate, RealPayedDate) as PurePayDate
				
			from LON_payments p
			left join LON_PayDocs d on(p.PayID=d.PayID)
			
			where 1=1 " . $where .  
			" group by p.PayID " . $order, $whereParams);
	}
	
	function CheckPartAmount(){
		
		$dt = parent::runquery("select ifnull(sum(PayAmount),0) from LON_payments 
			where RequestID=? AND PayID<>?", array($this->RequestID, $this->PayID));
		
		$PartObj = LON_ReqParts::GetValidPartObj($this->RequestID);
		
		if($dt[0][0]*1 + $this->PayAmount*1 > $PartObj->PartAmount*1)
		{
			ExceptionHandler::PushException("مبالغ وارد شده از سقف مبلغ وام تجاوز می کند");
			return false;
		}
		
		return true;
	}
	
	function Add($pdo = null) {
		
		if(!$this->CheckPartAmount())
			return false;
		
		return parent::Add($pdo);
	}
	
	function Edit($pdo = null) {
		
		if(!$this->CheckPartAmount())
			return false;
		
		return parent::Edit($pdo);
	}
	
	static function GetDocID($PayID){
		
		$dt = parent::runquery("select d.DocID
			from LON_payments p
			left join ACC_DocItems di on(di.SourceType=" . DOCTYPE_LOAN_PAYMENT . " 
				AND SourceID1=p.RequestID AND SourceID3=p.PayID) 
			left join ACC_docs d on(di.DocID=d.DocID)
			where p.PayID=? ", array($PayID));
		return count($dt) > 0 ? $dt[0][0] : 0;
	}
	
	static function GetFirstPayDate($RequestID){
		
		$dt = PdoDataAccess::runquery("select * from LON_payments where RequestID=? order by PayDate",
				array($RequestID));
		if(count($dt) == 0)
		{
			$obj = LON_ReqParts::GetValidPartObj($RequestID);
			return $obj->PartDate;
		}
		
		return $dt[0]["PayDate"];
	}
	
	static function GetLastPayDate($RequestID){
		
		$dt = PdoDataAccess::runquery("select * from LON_payments where RequestID=? order by PayDate desc",
				array($RequestID));
		if(count($dt) == 0)
		{
			$obj = LON_ReqParts::GetValidPartObj($RequestID);
			return $obj->PartDate;
		}
		
		return $dt[0]["PayDate"];
	}
	
	/**
	 * با درنظر گرفتن مراحل پرداخت اگر طی چند مرحله باشد مراحل دوم به بعد به تاریخ مرحله اول تنزیل می شوند
	 * @param type $RequestID
	 * @param type $PartObj
	 * @param type $TanzilCompute
	 * @return boolean
	 */
	static function GetTotalTanziledPayAmount($RequestID, $PartObj = null){
		
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		/*@var $PartObj LON_ReqParts */
		
		if($PartObj->ComputeMode != "NEW")
		{
			return $PartObj->PartAmount*1;
		}
		
		$dt = LON_payments::Get(" AND p.RequestID=? ", $RequestID, "order by PayDate desc");
		//print_r(ExceptionHandler::PopAllExceptions());
		$dt = $dt->fetchAll();
		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("مراحل پرداخت را وارد نکرده اید");
			return 0;
		}
		
		$amount = 0;
		for($i=0; $i<count($dt); $i++)
		{
			if($i == count($dt)-1)
			{
				$amount += $dt[$i]["PurePayAmount"]*1;
				break;
			}
			$amount = LON_Computes::Tanzil($amount + $dt[$i]["PurePayAmount"]*1, 
					$PartObj->CustomerWage, $dt[$i]["PurePayDate"], 
					$dt[$i+1]["PurePayDate"]);
		}
		
		return round($amount);	
	}
	
	/**
	 * جمع کل پرداخت ها
	 * @param type $RequestID
	 * @param type $PartObj
	 * @param type $TanzilCompute
	 * @return boolean
	 */
	static function GetTotalPureAmount($RequestID, $PartObj = null){
		
		$PartObj = $PartObj == null ? LON_ReqParts::GetValidPartObj($RequestID) : $PartObj;
		/*@var $PartObj LON_ReqParts */
		
		$dt = LON_payments::Get(" AND p.RequestID=? ", $RequestID, "order by PayDate desc");
		//print_r(ExceptionHandler::PopAllExceptions());
		$dt = $dt->fetchAll();
		
		$amount = 0;
		for($i=0; $i<count($dt); $i++)
		{
			$amount += $dt[$i]["PurePayAmount"]*1;
		}
		
		return round($amount);	
	}
	
	static function UpdateRealPayed($SourceObjects, $eventObj, $pdo){
		
		$ReqObj = new LON_payments((int)$SourceObjects[2]);
		$ReqObj->RealPayedDate = PDONOW;
		$ReqObj->Edit();
		
		return true;
	}
	
	static function UpdateComputes($SourceObjects, $eventObj, $pdo){
		
		$ReqObj = new LON_payments((int)$SourceObjects[2]);
		
		LON_installments::ComputeInstallments($ReqObj->RequestID, $pdo, true);
		LON_difference::RegisterDiffernce($ReqObj->RequestID,$pdo);
		
		return true;
	}
	
}

class LON_messages extends OperationClass {

	const TableName = "LON_messages";
	const TableKey = "MessageID";
	
	public $MessageID;
	public $RequestID;
	public $RegPersonID;
	public $CreateDate;
	public $details;
	public $MsgStatus;
	public $DoneDate;
	public $DoneDesc;
	
	static function Get($where = '', $whereParams = array(), $order = "order by CreateDate desc") {
		
		return PdoDataAccess::runquery_fetchMode("
		select	m.* , r.BorrowerDesc,
				concat_ws(' ',p.fname,p.lname,p.CompanyName) RegPersonName,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname
				
		from LON_messages m
		join BSC_persons p on(RegPersonID = PersonID)
		join LON_requests r using(RequestID)
		left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
		
		where 1=1 " . $where . " " . $order, $whereParams);	
	}
}

class LON_events extends OperationClass {

    const TableName = "LON_events";
    const TableKey = "EventID";

    public $EventID;
	public $RequestID;
	public $RegPersonID;
    public $EventTitle;
    public $EventDate;
	public $LetterID;
	public $FollowUpDate;
	public $FollowUpDesc;
	public $FollowUpPersonID;
	  
    function __construct($id = ""){
        
		$this->DT_EventDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_FollowUpDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
        parent::__construct($id);
    }

	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select e.*, concat_ws(' ',p1.CompanyName,p1.fname,p1.lname) RegFullname, 
				concat_ws(' ',p2.CompanyName,p2.fname,p2.lname) FollowUpFullname
			from LON_events e 
				left join BSC_persons p1 on(p1.PersonID=RegPersonID)
				left join BSC_persons p2 on(p2.PersonID=FollowUpPersonID)
			where 1=1 " . $where, $whereParams, $pdo);
	}
	
}

class LON_costs extends OperationClass{
	
	const TableName = "LON_costs";
	const TableKey = "CostID";
	
	public $CostID;
	public $RequestID;
	public $CostDesc;
	public $CostAmount;
	public $IsPartDiff;
	public $PartID;
	public $CostDate;
	
	function __construct($id = '') {
		
		$this->DT_CostDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select c.*,d.LocalNo from LON_costs c
			left join ACC_DocItems di on(c.CostID=di.SourceID2 AND di.SourceType=17)
			left join ACC_docs d using(DocID)
			where 1=1 " . $where . " group by CostID", $whereParams, $pdo);
	}
	
	function GetAccDoc(){
		 
		$dt = PdoDataAccess::runquery("select d.* 
			from ACC_DocItems di join ACC_docs d using(DocID) 
			where SourceID1=? AND SourceID2=? AND SourceType=17
			group by DocID", array($this->RequestID, $this->CostID));
		
		return count($dt) > 0 ? $dt[0] : false;
	}

}

class LON_follows extends OperationClass{
	
	const TableName = "LON_follows";
	const TableKey = "FollowID";
	
	public $FollowID;
	public $RequestID;
	public $RegDate;
	public $RegPersonID;
	public $StatusID;
	public $details;
	public $LawerName;
	public $RefDate;
	public $LawerDoc;
	
	function __construct($id = '') {
		
		$this->DT_RegDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_RefDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select f.*,concat_ws(' ',p.fname,p.lname) RegPersonName , t.letters,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname
			from LON_follows f
			join LON_requests r using(RequestID)
			join BSC_persons p on(f.RegPersonID=p.PersonID)
			join BaseInfo bf on(bf.TypeID=98 AND bf.InfoID=f.StatusID)
			left join ( select FollowID,group_concat(LetterID) letters 
						from LON_FollowLetters join OFC_letters l using(LetterID) group by FollowID )t
				on(t.FollowID=f.FollowID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			where 1=1 " . $where , $whereParams, $pdo);
	}	
}

class LON_FollowLetters extends OperationClass{
	
	const TableName = "LON_FollowLetters";
	const TableKey = "FollowID";
	
	public $FollowID;
	public $LetterID;
	
	function __construct($id = '') {
		
		$this->DT_RegDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_RefDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
}

class LON_FollowTemplates extends OperationClass{
	
	const TableName = "LON_FollowTemplates";
	const TableKey = "TemplateID";
	
	public $TemplateID;
	public $StatusID;
	public $LetterSubject;
	public $LetterContent;
	
	function __construct($id = '') {
		
		parent::__construct($id);
	}
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		
		return PdoDataAccess::runquery_fetchMode("
			select f.*,bf.InfoDesc StatusDesc
			from LON_FollowTemplates f
			join BaseInfo bf on(bf.TypeID=98 AND bf.InfoID=f.StatusID)
			where 1=1 " . $where , $whereParams, $pdo);
	}	
}

class LON_guarantors extends OperationClass{
	
	const TableName = "LON_guarantors";
	const TableKey = "GuarantorID";
	
	public $GuarantorID;
	public $RequestID;
	public $sex;
	public $fullname;
	public $NationalCode;
	public $father;
	public $ShNo;
	public $ShCity;
	public $BirthDate;
	public $address;
	public $phone;
	public $mobile;
	public $PersonType;
    public $FormType; //new added
    public $EconomicID; //new added
    public $email; //new added
    public $PostalCode; //new added
    public $NewspaperAdsNum; //new added
	function __construct($id = '') {
		
		$this->DT_BirthDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
}

?>
