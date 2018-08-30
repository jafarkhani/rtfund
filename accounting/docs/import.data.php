<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
 
require_once '../header.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/person/persons.class.php';
require_once getenv("DOCUMENT_ROOT") . '/loan/loan/loan.class.php';
require_once getenv("DOCUMENT_ROOT") . '/loan/request/compute.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/accounting/docs/doc.class.php';
require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';

require_once inc_dataReader;
require_once inc_response;

function CheckCloseCycle($CycleID = ""){
	
	if(ACC_cycles::IsClosed($CycleID))
	{
		echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
		die();	
	}
}

function FindCostID($costCode){
	
	$dt = PdoDataAccess::runquery("select * from ACC_CostCodes where IsActive='YES' AND CostCode=?",
		array($costCode));
	
	return count($dt) == 0 ? false : $dt[0]["CostID"];
}

function FindTafsiliID($TafsiliCode, $TafsiliType){
	
	if($TafsiliType == TAFTYPE_PERSONS)
		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
			. "where IsActive='YES' AND ObjectID=? AND TafsiliType=?",
		array($TafsiliCode, $TafsiliType));
	else
		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
			. "where IsActive='YES' AND TafsiliCode=? AND TafsiliType=?",
		array($TafsiliCode, $TafsiliType));
	
	if(count($dt) == 0)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $TafsiliType . "-" .  $TafsiliCode . "]");
		return false;
	}
	
	return $dt[0]["TafsiliID"];
}
 
//---------------------------------------------------------------

function RegisterPayPartDoc($ReqObj, $PartObj, $PayObj, $BankTafsili, $AccountTafsili, $ChequeNo, $pdo, $DocID=""){
		
	CheckCloseCycle();
	/*@var $ReqObj LON_requests */
	/*@var $PartObj LON_ReqParts */
	/*@var $PayObj LON_payments */
	
	$PartObj->MaxFundWage = $PartObj->MaxFundWage*1;
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_wage = FindCostID("750" . "-" . $LoanObj->_BlockCode);
	$CostCode_agent_wage = FindCostID("730");
	$CostCode_agent_FutureWage = FindCostID("740");
	$CostCode_FutureWage = FindCostID("760" . "-" . $LoanObj->_BlockCode);
	$CostCode_deposite = FindCostID("210-01");
	$CostCode_bank = FindCostID("101");
	$CostCode_commitment = FindCostID("200-" . $LoanObj->_BlockCode . "-51");
	$CostCode_todiee = FindCostID("200-". $LoanObj->_BlockCode."-01");
	$CostCode_delayCheque = FindCostID("190-436");
	
	$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
	$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
	
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	$PayAmount = $PayObj->PayAmount;
	//------------------- find load mode ---------------------
	$LoanMode = "";
	if(!empty($ReqObj->ReqPersonID))
	{
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsAgent == "YES")
			$LoanMode = "Agent";
	}
	else
		$LoanMode = "Customer";
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
		return false;
	
	if($LoanMode == "Agent")
	{
		$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
		if(!$ReqPersonTafsili)
			return false;
		$SubAgentTafsili = "";
		if(!empty($ReqObj->SubAgentID))
		{
			$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
			if(!$SubAgentTafsili)
				return false;
		}
	}	
	//------------ find the number step to pay ---------------
	$FirstStep = true;
	$dt = PdoDataAccess::runquery("select * from ACC_DocItems where SourceType=" .
			DOCTYPE_LOAN_PAYMENT . " AND SourceID=? order by SourceID3", 
			array($ReqObj->RequestID));
	if(count($dt) > 0)
	{
		$FirstStep = false;
		$firstPayObj = new LON_payments($dt[0]["SourceID3"]);
		
		$query = "select ifnull(sum(CreditorAmount-DebtorAmount),0),group_concat(LocalNo) docs
			from ACC_DocItems join ACC_docs using(DocID) 
			where CostID=? AND TafsiliID=? ";
		$param = array($CostCode_todiee, $LoanPersonTafsili);
		if($LoanMode == "Agent")
		{
			$query .= " AND TafsiliID2=?";
			$param[] = $ReqPersonTafsili;
		}
		$dt = PdoDataAccess::runquery($query, $param);
		if($dt[0][0]*1 < $PayAmount)
		{
			ExceptionHandler::PushException("حساب تودیعی این مشتری"
				. number_format($dt[0][0]) . "\n ریال می باشد که کمتر از مبلغ این مرحله از پرداخت وام می باشد"
				. "<br>\n [ شماره اسناد : " . $dt[0]["docs"] . " ]");
			return false;
		}
	}	
	//---------------- add doc header --------------------
	if($DocID == "")
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_LOAN_PAYMENT;
		$obj->description = "پرداخت وام شماره " . $ReqObj->RequestID . " به نام " . 
			$ReqObj->_LoanPersonFullname;

		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
		$obj = new ACC_docs($DocID);
	//--------------------------------------------------------
	$MaxWage = max($PartObj->CustomerWage*1 , $PartObj->FundWage);
	$AgentFactor = $MaxWage == 0 ? 0 : ($PartObj->CustomerWage-$PartObj->FundWage)/$MaxWage;
	
	$firstPayDate = $FirstStep ? $PayObj->PayDate : $firstPayObj->PayDate;
	$result = ComputeWagesAndDelays($PartObj, $PayAmount, $firstPayDate, $PayObj->PayDate);
	$TotalFundWage = $result["TotalFundWage"];
	$TotalAgentWage = $result["TotalAgentWage"];
	$TotalCustomerWage = $result["TotalCustomerWage"];
	$FundYears = $result["FundWageYears"];
	$AgentYears = $result["AgentWageYears"];
	
	$TotalFundDelay = $result["TotalFundDelay"];
	$TotalAgentDelay = $result["TotalAgentDelay"];
	$CustomerYearDelays = $result["CustomerYearDelays"];
	///...........................................................
	$curYear = substr(DateModules::miladi_to_shamsi($PayObj->PayDate), 0, 4)*1;
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	if($LoanMode == "Agent")
	{
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
	}
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	
	$extraAmount = 0;
	if($PartObj->WageReturn == "INSTALLMENT")
	{
		if($PartObj->MaxFundWage*1 > 0)
			$extraAmount += $PartObj->MaxFundWage;
		else if($PartObj->CustomerWage > $PartObj->FundWage)
			$extraAmount += $TotalFundWage;
		else
			$extraAmount += $TotalCustomerWage;
	}
		
	if($PartObj->AgentReturn == "INSTALLMENT" && $PartObj->CustomerWage>$PartObj->FundWage)
		$extraAmount += $TotalAgentWage;

	if($PartObj->DelayReturn == "INSTALLMENT")
		$extraAmount += $TotalFundDelay;
	if($TotalAgentDelay > 0 && $PartObj->AgentDelayReturn == "INSTALLMENT")
		$extraAmount += $TotalAgentDelay;
	
	if($FirstStep)
	{
		$amount = $PartObj->PartAmount + $extraAmount;	
		
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $amount;
		$itemObj->CreditorAmount = 0;
		$itemObj->Add($pdo);
		
		if($PartObj->PartAmount != $PayAmount)
		{
			unset($itemObj->ItemID);
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostCode_todiee;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			if(!empty($ReqPersonTafsili))
			{
				$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
				$itemObj->TafsiliID2 = $ReqPersonTafsili;
			}
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $PartObj->PartAmount*1 - $PayAmount;
			$itemObj->Add($pdo);
		}
	}
	else
	{
		if($extraAmount > 0)
		{
			unset($itemObj->ItemID);
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostCode_Loan;
			$itemObj->DebtorAmount = $extraAmount;
			$itemObj->CreditorAmount = 0;
			$itemObj->Add($pdo);
		}
		
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_todiee;
		$itemObj->DebtorAmount = $PayAmount;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->Add($pdo);
	}
	//------------------------ delay -------------------------------
	$totalAgentYearAmount = 0;
	if($PartObj->MaxFundWage == 0 && $TotalFundDelay > 0)
	{
		$index = 0;
		foreach($CustomerYearDelays as $year => $value)
		{
			$FundYearAmount = ($PartObj->FundWage/$PartObj->DelayPercent)*$value;
			$AgentYearAmount = (($PartObj->CustomerWage*1-$PartObj->FundWage*1)/$PartObj->DelayPercent*1)*$value;
			
			if($FundYearAmount == 0)
				break;

			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $FundYearAmount;
			$itemObj->TafsiliType = TAFTYPE_YEARS;
			$itemObj->details = "کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
			$itemObj->TafsiliID = FindTafsiliID($year, TAFTYPE_YEARS);
			if($itemObj->TafsiliID == "")
			{
				ExceptionHandler::PushException("تفصیلی مربوط به سال" . ($curYear+$index) . " یافت نشد");
				return false;
			}		
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
			
			if($PartObj->AgentDelayReturn == "INSTALLMENT")
			{
				unset($itemObj->ItemID);
				$itemObj->CostID = $year == $curYear ? $CostCode_agent_wage : $CostCode_agent_FutureWage;
				$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
				$itemObj->TafsiliID2 = $ReqPersonTafsili;
				$itemObj->TafsiliType = TAFTYPE_YEARS;
				$itemObj->TafsiliID = FindTafsiliID($year, TAFTYPE_YEARS);
				if($AgentYearAmount < 0)
				{
					$itemObj->DebtorAmount = abs($AgentYearAmount);
					$itemObj->CreditorAmount = 0;
					$itemObj->details = "اختلاف کارمزد تنفس وام شماره " . $ReqObj->RequestID;
				}
				else
				{
					$itemObj->DebtorAmount = 0;
					$itemObj->CreditorAmount = $AgentYearAmount;
					$itemObj->details = "سهم کارمزد تنفس وام شماره " . $ReqObj->RequestID;
				}
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
					return false;
				}
			}
			else
				$totalAgentYearAmount += $AgentYearAmount;
			$index++;
		}
		if($totalAgentYearAmount < 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_deposite;			
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			$itemObj->DebtorAmount = abs($AgentYearAmount);
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliID = FindTafsiliID($curYear, TAFTYPE_YEARS);
			$itemObj->details = "اختلاف کارمزد تنفس وام شماره " . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
		}
		if($totalAgentYearAmount > 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_deposite;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $totalAgentYearAmount;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			$itemObj->details = "سهم کارمزد تنفس وام شماره " . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
		}
		if($PartObj->DelayReturn == "CHEQUE" && $TotalFundDelay > 0)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_delayCheque;
			$itemObj->DebtorAmount = $TotalFundDelay;
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			if($LoanMode == "Agent")
			{
				$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
				$itemObj->TafsiliID2 = $ReqPersonTafsili;
			}
			else
			{
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
			}
			$itemObj->details = " تنفس صندوق وام شماره" . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
		}
		if($PartObj->AgentDelayReturn == "CHEQUE" && $TotalAgentDelay > 0)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_delayCheque;
			$itemObj->DebtorAmount = $TotalAgentDelay;
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			if($LoanMode == "Agent")
			{
				$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
				$itemObj->TafsiliID2 = $ReqPersonTafsili;
			}
			else
			{
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
			}
			$itemObj->details = " تنفس سرمایه گذار وام شماره" . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
		}
	}
	//------------------------ کارمزد---------------------	
	if($PartObj->MaxFundWage > 0 && $PartObj->WageReturn == "CUSTOMER")
	{
		unset($itemObj->ItemID);
		$itemObj->details = "کارمزد وام شماره " . $ReqObj->RequestID;
		$itemObj->CostID = $CostCode_wage;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $PartObj->MaxFundWage;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = $YearTafsili;
		$itemObj->Add($pdo);
	}
	else
	{
		foreach($FundYears as $Year => $amount)
		{	
			if($amount <= 0)
				continue;
			$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
			if(!$YearTafsili)
			{
				ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $Year . "]");
				return false;
			}
			unset($itemObj->ItemID);
			$itemObj->details = "کارمزد وام شماره " . $ReqObj->RequestID;
			$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $amount;
			$itemObj->TafsiliType = TAFTYPE_YEARS;
			$itemObj->TafsiliID = $YearTafsili;
			if($LoanMode == "Agent")
			{
				$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
				$itemObj->TafsiliID2 = $ReqPersonTafsili;
			}
			$itemObj->Add($pdo);
			
			if($PartObj->FundWage*1 > $PartObj->CustomerWage)
			{
				unset($itemObj->ItemID);
				$itemObj->details = "اختلاف کارمزد وام شماره " . $ReqObj->RequestID;
				$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
				$itemObj->DebtorAmount = $amount*$AgentFactor;
				$itemObj->CreditorAmount = 0;
				$itemObj->TafsiliType = TAFTYPE_YEARS;
				$itemObj->TafsiliID = $YearTafsili;
				if($LoanMode == "Agent")
				{
					$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
					$itemObj->TafsiliID2 = $ReqPersonTafsili;
				}
				$itemObj->Add($pdo);
			}
		}
		
		if($PartObj->WageReturn == "AGENT")
		{
			unset($itemObj->ItemID);
			$itemObj->details = " کارمزد وام شماره " . $ReqObj->RequestID;
			$itemObj->CostID = $CostCode_deposite;
			$itemObj->DebtorAmount = $TotalFundWage;
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->Add($pdo);
		}
	}
	if($LoanMode == "Agent" && $PartObj->AgentReturn == "INSTALLMENT")
	{
		foreach($AgentYears as $Year => $amount)
		{
			if($amount <= 0)
				continue;
			$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
			if(!$YearTafsili)
			{
				ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $Year . "]");
				return false;
			}
			unset($itemObj->ItemID);
			$itemObj->details = "سهم کارمزد وام شماره " . $ReqObj->RequestID;
			$itemObj->CostID = $Year == $curYear ? $CostCode_agent_wage : $CostCode_agent_FutureWage;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $amount;
			$itemObj->TafsiliType = TAFTYPE_YEARS;
			$itemObj->TafsiliID = $YearTafsili;
			$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
			$itemObj->TafsiliID2 = $ReqPersonTafsili;
			$itemObj->Add($pdo);
		}
	}
	if($LoanMode == "Agent" && $PartObj->AgentReturn == "CUSTOMER" && $PartObj->CustomerWage > $PartObj->FundWage)
	{
		unset($itemObj->ItemID);
		$itemObj->details = "سهم کارمزد وام شماره " . $ReqObj->RequestID;
		$itemObj->CostID = $CostCode_deposite;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $TotalAgentWage;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		if($LoanMode == "Agent")
		{
			$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
			$itemObj->TafsiliID2 = $ReqPersonTafsili;
		}
		$itemObj->Add($pdo);
	}
	//---------- ردیف های تضمین  ----------
	
	$dt = PdoDataAccess::runquery("select * from DMS_documents 
		join BaseInfo b on(InfoID=DocType AND TypeID=8)
		join ACC_DocItems on(SourceType=" . DOCTYPE_DOCUMENT . " AND SourceID=DocumentID)
		where IsConfirm='YES' AND b.param1=1 AND ObjectType='loan' AND ObjectID=?", array($ReqObj->RequestID));
	$SumAmount = 0;
	$countAmount = 0;
	
	if(count($dt) == 0)
	{
		$dt = PdoDataAccess::runquery("
			SELECT d.DocumentID, dv.ParamValue, InfoDesc as DocTypeDesc,t.ParamValue as DocNo
				FROM DMS_DocParamValues dv
				join DMS_DocParams using(ParamID)
				join DMS_documents d using(DocumentID)
				join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
				left join (
					select d.DocumentID,ParamValue
					from DMS_DocParamValues join DMS_DocParams using(ParamID)
					join DMS_documents d using(DocumentID)
					where Keytitle='no' and ObjectType='loan'
					group by DocumentID
				) t on(d.DocumentID=t.DocumentID)
				
			where IsConfirm='YES' AND b.param1=1 AND paramType='currencyfield' AND ObjectType='loan' AND ObjectID=?",
			array($ReqObj->RequestID), $pdo);
		
		foreach($dt as $row)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
			$itemObj->DebtorAmount = $row["ParamValue"];
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			$itemObj->SourceType = DOCTYPE_DOCUMENT;
			$itemObj->SourceID = $row["DocumentID"];
			$itemObj->details = $row["DocTypeDesc"] . " به شماره " . $row["DocNo"];
			$itemObj->Add($pdo);
			
			$SumAmount += $row["ParamValue"]*1;
			$countAmount++;
		}
		if($SumAmount > 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType);
			unset($itemObj->TafsiliID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			unset($itemObj->details);
			$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $SumAmount;	
			$itemObj->Add($pdo);

		}
	}
	// ----------------------------- bank --------------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_bank;
	$itemObj->DebtorAmount = 0;
	
	$BankItemAmount = $PayAmount;
	if($PartObj->WageReturn == "CUSTOMER")
	{
		if($PartObj->MaxFundWage > 0)
			$BankItemAmount -= $PartObj->MaxFundWage;
		else if($PartObj->CustomerWage > $PartObj->FundWage)
			$BankItemAmount -= $TotalFundWage;
		else
			$BankItemAmount -= $TotalCustomerWage;
	}
	if($PartObj->AgentReturn == "CUSTOMER" && $PartObj->CustomerWage > $PartObj->FundWage)
		$BankItemAmount -= $TotalAgentWage;
	
	/*if($PartObj->DelayReturn == "CUSTOMER" || $PartObj->DelayReturn == "CHEQUE")
		$BankItemAmount -= $TotalFundDelay;
	if($PartObj->AgentDelayReturn == "CUSTOMER" || $PartObj->DelayReturn == "CHEQUE")
		$BankItemAmount -= $TotalAgentDelay;*/
	
	$itemObj->CreditorAmount = $BankItemAmount;
	$itemObj->TafsiliType = TAFTYPE_BANKS;
	$itemObj->TafsiliID = $BankTafsili;
	$itemObj->TafsiliType2 = TAFTYPE_ACCOUNTS;
	$itemObj->TafsiliID2 = $AccountTafsili;
	$itemObj->locked = "NO";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	$itemObj->Add($pdo);
	$BankRow = clone $itemObj;
	
	$itemObj->locked = "YES";
	
	if($LoanMode == "Agent")
	{
		//---- کسر از سپرده -----
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->CostID = $CostCode_deposite;
		$itemObj->DebtorAmount = $PayAmount;
		$itemObj->CreditorAmount = 0;
		$itemObj->details = "بابت پرداخت وام شماره " . $ReqObj->RequestID;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		if($SubAgentTafsili != "")
		{
			$itemObj->TafsiliType2 = TAFTYPE_SUBAGENT;
			$itemObj->TafsiliID2 = $SubAgentTafsili;
		}
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_commitment;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $PayAmount;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->Add($pdo);
	}
	
	//---------------------------------------------------------
	$dt = PdoDataAccess::runquery("select sum(DebtorAmount) dsum, sum(CreditorAmount) csum
		from ACC_DocItems where DocID=?", array($obj->DocID), $pdo);
	if($dt[0]["dsum"] != $dt[0]["csum"])
	{
		$BankRow->CreditorAmount += $dt[0]["dsum"] - $dt[0]["csum"];
		$BankItemAmount = $BankRow->CreditorAmount;
		$BankRow->Edit($pdo);
	}
	//---------------------------------------------------------
	//------ ایجاد چک ------
	
	$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFTYPE_ACCOUNTS . 
			" AND TafsiliID=? ", array($AccountTafsili));
	$AccountID = (count($dt) > 0) ? $dt[0]["ObjectID"] : "";
	
	$chequeObj = new ACC_DocCheques();
	$chequeObj->DocID = $obj->DocID;
	$chequeObj->CheckDate = $PayObj->PayDate;
	$chequeObj->amount = $BankItemAmount;
	$chequeObj->TafsiliID = $LoanPersonTafsili;
	$chequeObj->CheckNo = $ChequeNo;
	$chequeObj->AccountID = $AccountID ;
	$chequeObj->description = " پرداخت وام شماره " . $ReqObj->RequestID;
	$chequeObj->Add($pdo);
	
	//---------------------------------------------------------
	
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $obj->DocID;
}

function RegisterSHRTFUNDPayPartDoc($ReqObj, $PartObj, $PayObj, $BankTafsili, $AccountTafsili, $ChequeNo, $pdo, $DocID=""){
		
	CheckCloseCycle();
	/*@var $ReqObj LON_requests */
	/*@var $PartObj LON_ReqParts */
	/*@var $PayObj LON_payments */
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_FundComit_mande = FindCostID("721-".$LoanObj->_BlockCode."-52-02");
	$CostCode_CustomerComit = FindCostID("721-".$LoanObj->_BlockCode."-51");
	$CostCode_bank = FindCostID("101");
	$CostCode_todiee = FindCostID("200-".$LoanObj->_BlockCode."-01");
	$CostCode_FundComit_wage = FindCostID("721-".$LoanObj->_BlockCode."-52-03");
	$CostCode_wage = FindCostID("750" . "-" . $LoanObj->_BlockCode);
	$CostCode_delayCheque = FindCostID("190-436");
		
	$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
	$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
	
	//------------------------------------------------
	
	$CycleID = $_SESSION["accounting"]["CycleID"];
	
	//---------------- add doc header --------------------
	if($DocID == "")
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_LOAN_PAYMENT;
		$obj->description = "پرداخت وام شماره " . $ReqObj->RequestID . " به نام " . 
			$ReqObj->_LoanPersonFullname;

		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
		$obj = new ACC_docs($DocID);
	
	$PayAmount = $PayObj->PayAmount;
	//--------------------------------------------------------
	$payments = LON_payments::Get(" AND RequestID=?", array($PartObj->RequestID), " order by PayDate");
	$payments = $payments->fetchAll();
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	
	$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
	if(!$ReqPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->ReqPersonID . "]");
		return false;
	}
	$SubAgentTafsili = "";
	if(!empty($ReqObj->SubAgentID))
	{
		$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
		if(!$SubAgentTafsili)
		{
			ExceptionHandler::PushException("تفصیلی زیر واحد سرمایه گذار یافت نشد.[" . $ReqObj->SubAgentID . "]");
			return false;
		}
	}
	
	//------------ find the number step to pay ---------------
	$FirstStep = true;
	$dt = PdoDataAccess::runquery("select * from ACC_DocItems 
		where SourceType='".DOCTYPE_LOAN_PAYMENT."' AND SourceID=? AND SourceID2=?", 
			array($ReqObj->RequestID, $PartObj->PartID));
	if(count($dt) > 0)
	{
		$FirstStep = false;
		$query = "select ifnull(sum(CreditorAmount-DebtorAmount),0)
			from ACC_DocItems where CostID=? AND TafsiliID=? AND TafsiliID2=?";
		$param = array($CostCode_todiee, $LoanPersonTafsili, $ReqPersonTafsili);
	
		$dt = PdoDataAccess::runquery($query, $param);
		if($dt[0][0]*1 < $PayAmount)
		{
			ExceptionHandler::PushException("حساب تودیعی این مشتری"
					. number_format($dt[0][0]) . " ریال می باشد که کمتر از مبلغ این مرحله از پرداخت وام می باشد");
			return false;
		}
	}
	//---------------- compute wage --------------------------
	$InstallmentWage = 0;
	if($PartObj->AgentReturn == "INSTALLMENT")
		$InstallmentWage = ComputeWageOfSHekoofa($PartObj);		

	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
	$itemObj->TafsiliID2 = $ReqPersonTafsili;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	
	if($FirstStep)
	{
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $PartObj->PartAmount*1 + $InstallmentWage;
		$itemObj->CreditorAmount = 0;
		$itemObj->Add($pdo);
		$LoanRow = clone $itemObj;
		
		if($InstallmentWage > 0)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_CustomerComit;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $InstallmentWage;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			$itemObj->Add($pdo);
		}
		
		if($PartObj->PartAmount != $PayAmount)
		{
			unset($itemObj->ItemID);
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostCode_todiee;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
			$itemObj->TafsiliID2 = $ReqPersonTafsili;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $PartObj->PartAmount*1 - $PayAmount;
			$itemObj->Add($pdo);
		}
	}
	else
	{		
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_todiee;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->DebtorAmount = $PayAmount;
		$itemObj->CreditorAmount = 0;
		$itemObj->Add($pdo);
	}
	//---------------------------------------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_FundComit_mande;
	$itemObj->DebtorAmount = $PayAmount;
	$itemObj->CreditorAmount = 0;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $ReqPersonTafsili;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	$itemObj->Add($pdo);
	
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_CustomerComit;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $PayAmount;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $ReqPersonTafsili;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	$itemObj->Add($pdo);
	// ----------------------------- wage --------------------------------
	$AgentWage = 0;
	if($PartObj->CustomerWage*1 > $PartObj->FundWage*1 && $PartObj->AgentReturn == "CUSTOMER")
	{
		$totalWage = $PayAmount*$PartObj->CustomerWage/100;
		$AgentFactor = ($PartObj->CustomerWage*1-$PartObj->FundWage*1)/$PartObj->CustomerWage*1;
		$AgentWage = $totalWage*$AgentFactor;		
	
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_FundComit_wage;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $AgentWage;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->Add($pdo);
	}
	$FundWage = 0;
	if($PartObj->FundWage*1 > 0 && $PartObj->WageReturn == "CUSTOMER")
	{
		$FundWage = $PayAmount*$PartObj->FundWage/100;
		
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		$itemObj->CostID = $CostCode_wage;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $FundWage;
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->Add($pdo);
	}
	//----------------------------- delay --------------------------------
	$endDelayDate = DateModules::AddToGDate($PartObj->PartDate, $PartObj->DelayDays*1, $PartObj->DelayMonths*1);
	$DelayDuration = DateModules::GDateMinusGDate($endDelayDate, $PartObj->PartDate)+1;
	$CustomerDelay = round($PayAmount*$PartObj->DelayPercent*$DelayDuration/36500);
	$FundDelay = round($PayAmount*$PartObj->FundWage*$DelayDuration/36500);
	$AgentDelay = round($PayAmount*($PartObj->DelayPercent - $PartObj->FundWage)*$DelayDuration/36500);		
	
	if($PartObj->DelayReturn == "CHEQUE" && $FundDelay > 0)
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_delayCheque;
		$itemObj->DebtorAmount = $FundDelay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->details = " تنفس صندوق وام شماره" . $ReqObj->RequestID;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
			return false;
		}
	}
	if($PartObj->AgentDelayReturn == "CHEQUE" && $AgentDelay > 0)
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_delayCheque;
		$itemObj->DebtorAmount = $AgentDelay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->details = " تنفس سرمایه گذار وام شماره" . $ReqObj->RequestID;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
			return false;
		}
	}
	//---------- ردیف های تضمین  ----------
	$dt = PdoDataAccess::runquery("select * from DMS_documents 
		join BaseInfo b on(InfoID=DocType AND TypeID=8)
		join ACC_DocItems on(SourceType=" . DOCTYPE_DOCUMENT . " AND SourceID=DocumentID)
		where IsConfirm='YES' AND b.param1=1 AND ObjectType='loan' AND ObjectID=?", array($ReqObj->RequestID));
	$SumAmount = 0;
	$countAmount = 0;
	
	if(count($dt) == 0)
	{
		$dt = PdoDataAccess::runquery("
			SELECT DocumentID, ParamValue, InfoDesc as DocTypeDesc
				FROM DMS_DocParamValues
				join DMS_DocParams using(ParamID)
				join DMS_documents d using(DocumentID)
				join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
			where b.param1=1 AND paramType='currencyfield' AND ObjectType='loan' AND ObjectID=?",
			array($ReqObj->RequestID), $pdo);
		
		foreach($dt as $row)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
			$itemObj->DebtorAmount = $row["ParamValue"];
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			$itemObj->SourceType = DOCTYPE_DOCUMENT;
			$itemObj->SourceID = $row["DocumentID"];
			$itemObj->details = $row["DocTypeDesc"];
			$itemObj->Add($pdo);
			
			$SumAmount += $row["ParamValue"]*1;
			$countAmount++;
		}
		if($SumAmount > 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType);
			unset($itemObj->TafsiliID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			unset($itemObj->details);
			$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $SumAmount;	
			$itemObj->Add($pdo);
		}
	}
	// ----------------------------- bank --------------------------------
	$BankAmount = $PayAmount - $AgentWage - $FundWage;
		
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_bank;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $BankAmount;
	$itemObj->TafsiliType = TAFTYPE_BANKS;
	$itemObj->TafsiliID = $BankTafsili;
	$itemObj->TafsiliType2 = TAFTYPE_ACCOUNTS;
	$itemObj->TafsiliID2 = $AccountTafsili;
	$itemObj->locked = "NO";
	$itemObj->SourceType = DOCTYPE_LOAN_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PartObj->PartID;
	$itemObj->SourceID3 = $PayObj->PayID;
	$itemObj->Add($pdo);
	$BankRow = clone $itemObj;
	
	$itemObj->locked = "YES";
		
	//---------------------------------------------------------
	$dt = PdoDataAccess::runquery("select sum(DebtorAmount) dsum, sum(CreditorAmount) csum
		from ACC_DocItems where DocID=?", array($obj->DocID), $pdo);
	if($dt[0]["dsum"] != $dt[0]["csum"])
	{
		$BankRow->CreditorAmount += $dt[0]["dsum"] - $dt[0]["csum"];
		$BankAmount = $BankRow->CreditorAmount ;
		$BankRow->Edit($pdo);
	}
	//---------------------------------------------------------
	//------ ایجاد چک ------
	
	$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFTYPE_ACCOUNTS . 
			" AND TafsiliID=? ", array($AccountTafsili));
	$AccountID = (count($dt) > 0) ? $dt[0]["ObjectID"] : "";
			
	$chequeObj = new ACC_DocCheques();
	$chequeObj->DocID = $obj->DocID;
	$chequeObj->CheckDate = $PayObj->PayDate;
	$chequeObj->amount = $BankAmount;
	$chequeObj->CheckNo = $ChequeNo;
	$chequeObj->AccountID = $AccountID;
	$chequeObj->TafsiliID = $LoanPersonTafsili;
	$chequeObj->description = " پرداخت وام شماره " . $ReqObj->RequestID;
	$chequeObj->Add($pdo);
	
	//---------------------------------------------------------
	
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $obj->DocID;
}

function ReturnPayPartDoc($DocID, $pdo, $DeleteDoc = true){
	
	CheckCloseCycle();
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select d.DocID,LocalNo, CycleDesc
		from ACC_DocItems d join ACC_docs using(DocID) join ACC_cycles using(CycleID)
		where StatusID <> ".ACC_STEPID_RAW." 
			AND d.DocID=? AND SourceType=". DOCTYPE_LOAN_PAYMENT, array($DocID));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	PdoDataAccess::runquery("delete from ACC_DocItems where DocID=? AND SourceType=". DOCTYPE_LOAN_PAYMENT
		, array($DocID));
	PdoDataAccess::runquery("delete from ACC_DocCheques where DocID=? ", array($DocID));

	if($DeleteDoc)
	{
		PdoDataAccess::runquery("delete d from ACC_docs d left join ACC_DocItems using(DocID)
		where DocID=? AND ItemID is null",	array($DocID), $pdo);
	}
	return ExceptionHandler::GetExceptionCount() == 0;
}

function RegisterLoanCost($CostObj, $CostID, $TafsiliID, $TafsiliID2, $pdo){
		
	CheckCloseCycle();
	//------------- get CostCodes --------------------
	$ReqObj = new LON_requests($CostObj->RequestID);
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//------------------- find load mode ---------------------
	$LoanMode = "";
	if(!empty($ReqObj->ReqPersonID))
	{
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsAgent == "YES")
			$LoanMode = "Agent";
	}
	else
		$LoanMode = "Customer";
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
		return false;
	
	if($LoanMode == "Agent")
	{
		$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
		if(!$ReqPersonTafsili)
			return false;
		$SubAgentTafsili = "";
		if(!empty($ReqObj->SubAgentID))
		{
			$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
			if(!$SubAgentTafsili)
				return false;
		}
	}	
	//---------------- add doc header --------------------
	$obj = new ACC_docs();
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $CycleID;
	$obj->BranchID = $ReqObj->BranchID;
	$obj->DocType = DOCTYPE_LOAN_COST;
	$obj->description = "هزینه های مازاد وام شماره " . $ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;
	if(!$obj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	if($LoanMode == "Agent")
	{
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
	}
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_COST;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $CostObj->CostID;
	$itemObj->CostID = $CostCode_Loan;
	$itemObj->DebtorAmount = $CostObj->CostAmount*1 > 0 ? $CostObj->CostAmount : 0;
	$itemObj->CreditorAmount = $CostObj->CostAmount*1 < 0 ? abs($CostObj->CostAmount) : 0;
	$itemObj->Add($pdo);
	// ----------------------------- bank --------------------------------
	$CostCodeObj = new ACC_CostCodes($CostID);
	unset($itemObj->ItemID);
	unset($itemObj->TafsiliType);
	unset($itemObj->TafsiliType2);
	unset($itemObj->TafsiliID2);
	unset($itemObj->TafsiliID);
	$itemObj->locked = "NO";
	$itemObj->CostID = $CostID;
	$itemObj->DebtorAmount = $CostObj->CostAmount*1 < 0 ? abs($CostObj->CostAmount) : 0;
	$itemObj->CreditorAmount = $CostObj->CostAmount*1 > 0 ? $CostObj->CostAmount : 0;
	$itemObj->TafsiliType = $CostCodeObj->TafsiliType;
	if($TafsiliID != "")
		$itemObj->TafsiliID = $TafsiliID;
	$itemObj->TafsiliType2 = $CostCodeObj->TafsiliType2;
	if($TafsiliID2 != "")
		$itemObj->TafsiliID2 = $TafsiliID2;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد ردیف بانک");
		return false;
	}		
	
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return true;
}

//---------------------------------------------------------------
function RegisterDifferncePartsDoc($RequestID, $NewPartID, $pdo, $DocID=""){
	
	CheckCloseCycle();
	
	$ReqObj = new LON_requests($RequestID);
	$NewPartObj = new LON_ReqParts($NewPartID);
	
	$ReqPersonObj = new BSC_persons($ReqObj->ReqPersonID);
	if($ReqPersonObj->IsSupporter == "YES")
		return RegisterDifferncePartsDoc_Supporter($ReqObj, $NewPartObj, $pdo, $DocID);
	
	$dt = PdoDataAccess::runquery("select * from LON_ReqParts 
		where RequestID=? AND IsHistory='YES' order by PartID desc limit 1", array($RequestID));
	$PreviousPartObj = new LON_ReqParts($dt[0]["PartID"]);
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_wage = FindCostID("750" . "-" . $LoanObj->_BlockCode);
	$CostCode_FutureWage = FindCostID("760" . "-" . $LoanObj->_BlockCode);
	$CostCode_agent_wage = FindCostID("730");
	$CostCode_agent_FutureWage = FindCostID("740");
	$CostCode_deposite = FindCostID("210-01");
	$CostCode_todiee = FindCostID("200-". $LoanObj->_BlockCode."-01");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//--------------------------------------------------
	$LoanMode = "";
	if(!empty($ReqObj->ReqPersonID))
	{
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsAgent == "YES")
			$LoanMode = "Agent";
	}
	else
		$LoanMode = "Customer";
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	
	if($LoanMode == "Agent")
	{
		$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
		if(!$ReqPersonTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->ReqPersonID . "]");
			return false;
		}
		$SubAgentTafsili = "";
		if(!empty($ReqObj->SubAgentID))
		{
			$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
			if(!$SubAgentTafsili)
			{
				ExceptionHandler::PushException("تفصیلی زیر واحد سرمایه گذار یافت نشد.[" . $ReqObj->SubAgentID . "]");
				return false;
			}
		}
	}	
	//---------------- add doc header --------------------
	if($DocID == "")
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_LOAN_DIFFERENCE;
		$obj->description = "اختلاف حاصل از شرایط جدید برای وام شماره " . $ReqObj->RequestID;
		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
	{
		$obj = new ACC_docs($DocID);
		PdoDataAccess::runquery("delete from ACC_DocItems where DocID=?", array($DocID), $pdo);
	}
	//--------------------------------------------------------
	// compute the previous amounts
	
	$diferences = array(
		"FundWageYears" => array(),
		"AgentWageYears" => array(),
		"CustomerYearDelays" => array(),
		"TotalFundWage" => 0,
		"TotalAgentWage" => 0,
		"TotalCustomerWage" => 0,
		"TotalFundDelay" => 0,
		"TotalAgentDelay" => 0
	);
	
	$dt = PdoDataAccess::runquery("select * from LON_payments where RequestID=?", array($RequestID), $pdo);
	if(count($dt) == 0)
	{
		ExceptionHandler::PushException("هیچ ﭘرداختی برای این وام ثبت نشده است");
		return false;
	}
	$firstPayDate = $dt[0]["PayDate"];
	$totalPaid = 0;
	foreach($dt as $PayRow)
	{
		$totalPaid += $PayRow["PayAmount"];
		$result_old = ComputeWagesAndDelays($PreviousPartObj, $PayRow["PayAmount"], $firstPayDate, $PayRow["PayDate"]);
		$result_new = ComputeWagesAndDelays($NewPartObj, $PayRow["PayAmount"], $firstPayDate, $PayRow["PayDate"]);

		foreach($result_new["FundWageYears"] as $year => $amount)
		{
			if(!isset($diferences["FundWageYears"][$year]))
				$diferences["FundWageYears"][$year] = 0;
			$diferences["FundWageYears"][$year] += $amount;
		}
		foreach($result_old["FundWageYears"] as $year => $amount)
		{
			if(!isset($diferences["FundWageYears"][$year]))
				$diferences["FundWageYears"][$year] = 0;
			$diferences["FundWageYears"][$year] -= $amount;
		}
		//.....................................................
		foreach($result_new["AgentWageYears"] as $year => $amount)
		{
			if(!isset($diferences["AgentWageYears"][$year]))
				$diferences["AgentWageYears"][$year] = 0;
			$diferences["AgentWageYears"][$year] += $amount;
		}
		foreach($result_old["AgentWageYears"] as $year => $amount)
		{
			if(!isset($diferences["AgentWageYears"][$year]))
				$diferences["AgentWageYears"][$year] = 0;
			$diferences["AgentWageYears"][$year] -= $amount;
		}
		//.....................................................
		foreach($result_new["FundYearDelays"] as $year => $amount)
		{
			if(!isset($diferences["FundYearDelays"][$year]))
				$diferences["FundYearDelays"][$year] = 0;
			$diferences["FundYearDelays"][$year] += $amount;
		}
		foreach($result_old["FundYearDelays"] as $year => $amount)
		{
			if(!isset($diferences["FundYearDelays"][$year]))
				$diferences["FundYearDelays"][$year] = 0;
			$diferences["FundYearDelays"][$year] -= $amount;
		}
		//.....................................................
		foreach($result_new["AgentYearDelays"] as $year => $amount)
		{
			if(!isset($diferences["AgentYearDelays"][$year]))
				$diferences["AgentYearDelays"][$year] = 0;
			$diferences["AgentYearDelays"][$year] += $amount;
		}
		foreach($result_old["AgentYearDelays"] as $year => $amount)
		{
			if(!isset($diferences["AgentYearDelays"][$year]))
				$diferences["AgentYearDelays"][$year] = 0;
			$diferences["AgentYearDelays"][$year] -= $amount;
		}
		//.....................................................
		$diferences["TotalFundWage"] += $result_new["TotalFundWage"]*1 - $result_old["TotalFundWage"]*1;
		$diferences["TotalAgentWage"] += $result_new["TotalAgentWage"]*1 - $result_old["TotalAgentWage"]*1;
		$diferences["TotalCustomerWage"] += $result_new["TotalCustomerWage"]*1 - $result_old["TotalCustomerWage"]*1;
		$diferences["TotalFundDelay"] += $result_new["TotalFundDelay"]*1 - $result_old["TotalFundDelay"]*1;
		$diferences["TotalAgentDelay"] += $result_new["TotalAgentDelay"]*1 - $result_old["TotalAgentDelay"]*1;
	}
	$ExtraAmount = 0;
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	if($LoanMode == "Agent")
	{
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
	}
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_DIFFERENCE;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $NewPartObj->PartID;
	
	$NewExtraAmount = GetExtraLoanAmount($NewPartObj, 
			$diferences["TotalFundWage"], 
			$diferences["TotalCustomerWage"], 
			$diferences["TotalAgentWage"], 
			$diferences["TotalFundDelay"], 
			$diferences["TotalAgentDelay"]);
	//.............. todiee ............................
	if($NewPartObj->PartAmount*1 != $PreviousPartObj->PartAmount*1)
	{
		$amount = $PreviousPartObj->PartAmount*1 - $NewPartObj->PartAmount*1;
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_todiee;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		$itemObj->DebtorAmount = $amount > 0 ? $amount : 0;
		$itemObj->CreditorAmount = $amount < 0 ? abs($amount) : 0;	
		$itemObj->Add($pdo);
		
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $amount < 0 ? abs($amount) : 0;		
		$itemObj->CreditorAmount = $amount > 0 ? $amount : 0;
		$itemObj->Add($pdo);
	}
	//...............Loan ..............................
	if($NewExtraAmount != 0)
	{
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $NewExtraAmount > 0 ? $NewExtraAmount : 0;
		$itemObj->CreditorAmount = $NewExtraAmount < 0 ? abs($NewExtraAmount) : 0;		
		$itemObj->Add($pdo);
	}
	//------------------------ agent delay -------------------------------
	$curYear = substr(DateModules::shNow(), 0, 4)*1;
	if($LoanMode == "Agent")
	{
		if($NewPartObj->AgentDelayReturn == "CUSTOMER")
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_deposite;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			$itemObj->DebtorAmount = $diferences["TotalAgentDelay"]*1<0 ? abs($diferences["TotalAgentDelay"]*1) : 0;
			$itemObj->CreditorAmount = $diferences["TotalAgentDelay"]*1>0 ? $diferences["TotalAgentDelay"]*1 : 0;		
			$itemObj->details = "کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}

			if($diferences["TotalAgentDelay"]*1 > 0)
			{
				$ExtraAmount += $diferences["TotalAgentDelay"]*1;
			}
			else
			{
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				$itemObj->CostID = COSTID_saving;
				$itemObj->TafsiliType = TAFTYPE_PERSONS;
				$itemObj->TafsiliID = $LoanPersonTafsili;
				$itemObj->CreditorAmount = 0;
				$itemObj->DebtorAmount = abs($diferences["TotalAgentDelay"]*1);
				$itemObj->details = "اختلاف کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
					return false;
				}
			}
		}
		else
		{
			$prevYear = 0;
			foreach($diferences["AgentYearDelays"] as $year => $value)
			{
				if($value == 0)
					continue;

				if($year*1 < $curYear)
				{
					$prevYear += $value*1;
					continue;
				}
				$value += $prevYear;

				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				$itemObj->CostID = $year == $curYear ? $CostCode_agent_wage : $CostCode_agent_FutureWage;
				$itemObj->TafsiliType = TAFTYPE_YEARS;
				$itemObj->TafsiliID = FindTafsiliID($year, TAFTYPE_YEARS);
				$itemObj->DebtorAmount = $value<0 ? abs($value) : 0;
				$itemObj->CreditorAmount = $value>0 ? $value : 0;		
				$itemObj->details = "کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
					return false;
				}
				$prevYear = 0;
			}

			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->CostID = $CostCode_Loan;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
			$itemObj->TafsiliID2 = $ReqPersonTafsili;
			$itemObj->CreditorAmount = $diferences["TotalAgentDelay"]*1<0 ? abs($diferences["TotalAgentDelay"]*1) : 0;
			$itemObj->DebtorAmount = $diferences["TotalAgentDelay"]*1>0 ? $diferences["TotalAgentDelay"]*1 : 0;			
			$itemObj->details = "اختلاف کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
				return false;
			}
		}
	}
	//------------------------ fund delay -------------------------------
	$prevYear = 0;
	foreach($diferences["FundYearDelays"] as $year => $value)
	{
		if($value == 0)
			continue;
		if($year*1 < $curYear)
		{
			$prevYear += $value*1;
			continue;
		}
		$value += $prevYear;
		
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->CostID = $year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = FindTafsiliID($year, TAFTYPE_YEARS);
		$itemObj->DebtorAmount = $value<0 ? abs($value) : 0;
		$itemObj->CreditorAmount = $value>0 ? $value : 0;		
		$itemObj->details = "کارمزد دوره تنفس وام شماره " . $ReqObj->RequestID;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
			return false;
		}
		if($NewPartObj->DelayReturn == "CUSTOMER")
			$ExtraAmount += $value;
		
		$prevYear = 0;
	}
	//------------------------ fund wage ---------------------	
	$prevYear = 0;
	foreach($diferences["FundWageYears"] as $Year => $amount)
	{
		if($amount == 0)
			continue;
		if($Year*1 < $curYear)
		{
			$prevYear += $amount*1;
			continue;
		}
		$amount += $prevYear;
		
		$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
		if(!$YearTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $Year . "]");
			return false;
		}
		unset($itemObj->ItemID);
		$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = $YearTafsili;
		$itemObj->details = "کارمزد وام شماره " . $ReqObj->RequestID;
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->DebtorAmount = $amount<0 ? abs($amount) : 0;
		$itemObj->CreditorAmount = $amount>0 ? $amount : 0;
		$itemObj->Add($pdo);
		
		if($NewPartObj->WageReturn == "CUSTOMER")
			$ExtraAmount += $amount;
		
		$prevYear = 0;
	}
	//------------------------ agent wage ---------------------	
	if($LoanMode == "Agent")
	{
		if($NewPartObj->AgentReturn == "CUSTOMER")
		{
			if($diferences["TotalAgentWage"]*1 != 0)
			{
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				$itemObj->CostID = $CostCode_deposite;
				$itemObj->TafsiliType = TAFTYPE_YEARS;
				$itemObj->TafsiliID = FindTafsiliID($year, TAFTYPE_YEARS);
				$itemObj->DebtorAmount = $diferences["TotalAgentWage"]*1<0 ? abs($diferences["TotalAgentWage"]*1) : 0;
				$itemObj->CreditorAmount = $diferences["TotalAgentWage"]*1>0 ? $diferences["TotalAgentWage"]*1 : 0;		
				$itemObj->details = "کارمزد وام شماره " . $ReqObj->RequestID;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد تنفس");
					return false;
				}
				$ExtraAmount += $diferences["TotalAgentWage"]*1;
			}
		}
		else
		{
			$prevYear = 0;
			foreach($diferences["AgentWageYears"] as $Year => $amount)
			{
				if($amount == 0)
					continue;
				if($Year*1 < $curYear)
				{
					$prevYear += $amount*1;
					continue;
				}
				$amount += $prevYear;

				unset($itemObj->ItemID);
				$itemObj->CostID = $Year == $curYear ? $CostCode_agent_wage : $CostCode_agent_FutureWage;
				$itemObj->TafsiliType = TAFTYPE_YEARS;
				$itemObj->TafsiliID = $YearTafsili;
				$itemObj->details = "کارمزد وام شماره " . $ReqObj->RequestID;
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				$itemObj->DebtorAmount = $amount<0 ? abs($amount) : 0;
				$itemObj->CreditorAmount = $amount>0 ? $amount : 0;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد ردیف کارمزد ");
					return false;
				}
			}
		}
	}
	// ---------------------------- ExtraAmount --------------------------------
	
	if($ExtraAmount > 0)
	{
		$itemObj = new ACC_DocItems();
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $ExtraAmount;
		$itemObj->CreditorAmount = 0;	
		$itemObj->locked = "NO";
		$itemObj->SourceID = $ReqObj->RequestID;
		$itemObj->SourceID2 = $NewPartObj->PartID;
		
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف مازاد");
			return false;
		}
		
		//-------------------- add loan cost for difference --------------------
		/*$dt = PdoDataAccess::runquery("select * from LON_costs where RequestID=? AND PartID=?", 
				array($NewPartObj->RequestID, $NewPartObj->PartID));
		if(count($dt)>0)
		{
			$obj = new LON_costs($dt[0]["CostID"]);
			$obj->CostAmount = $ExtraAmount;
			$obj->Edit($pdo);
		}
		else
		{
			$obj = new LON_costs();
			$obj->CostDate = PDONOW;
			$obj->RequestID = $NewPartObj->RequestID;
			$obj->CostDesc = "اختلافت حاصل از تغییر شرایط پرداخت";
			$obj->CostAmount = $ExtraAmount;
			$obj->CostID = $CostCode_Loan;
			$obj->IsPartDiff = "YES";
			$obj->PartID = $NewPartObj->PartID;
			if(!$obj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد هزینه");
				return false;
			}
		}*/
	}
	//--------------- differences of backPays -----------------
	$prevResult = ComputeWagesAndDelays($PreviousPartObj, $PreviousPartObj->PartAmount, $PreviousPartObj->PartDate, $PreviousPartObj->PartDate);
	$newResult = ComputeWagesAndDelays($NewPartObj, $NewPartObj->PartAmount, $NewPartObj->PartDate, $NewPartObj->PartDate);
	
	$PrevTotalBackPay = $PreviousPartObj->PartAmount*1 + 
		GetExtraLoanAmount($PreviousPartObj,$prevResult["TotalFundWage"],$prevResult["TotalCustomerWage"],
				$prevResult["TotalAgentWage"],$prevResult["TotalFundDelay"], $prevResult["TotalAgentDelay"]);
	$newTotalBackPay = $NewPartObj->PartAmount*1 + 
		GetExtraLoanAmount($NewPartObj,$newResult["TotalFundWage"],$newResult["TotalCustomerWage"],
				$newResult["TotalAgentWage"],$newResult["TotalFundDelay"], $newResult["TotalAgentDelay"]);
	
	$dt = LON_BackPays::GetRealPaid($RequestID);
	$prevRemain = $PrevTotalBackPay;
	$newRemain = $newTotalBackPay;
	$diffFundDelayShare = $diffAgentDelayShare = $diffFundWageShare = 0;
	foreach($dt as $row)
	{
		$prevFundDelayShare = $prevAgentDelayShare = $prevFundWageShare = 0;
		
		if($PreviousPartObj->DelayReturn == "INSTALLMENT")
		{
			$prevPayAmount = min($prevRemain, $row["PayAmount"]*1);
			$prevFundDelayShare = round($prevResult["TotalFundDelay"]*$prevPayAmount/$PrevTotalBackPay);
		}
		if($PreviousPartObj->AgentDelayReturn == "INSTALLMENT")
		{
			$prevPayAmount = min($prevRemain, $row["PayAmount"]*1);
			$prevAgentDelayShare = round($prevResult["TotalAgentDelay"]*$prevPayAmount/$PrevTotalBackPay);
		}
		if($PreviousPartObj->WageReturn == "INSTALLMENT")
		{
			$prevPayAmount = min($prevRemain, $row["PayAmount"]*1);
			$prevFundWageShare = round($prevResult["TotalFundWage"]*$prevPayAmount/$PrevTotalBackPay);
		}
		$prevRemain -= $prevPayAmount;
		//..............................................
		$newFundDelayShare = $newAgentDelayShare = $newFundWageShare = 0;
		
		if($NewPartObj->DelayReturn == "INSTALLMENT")
		{
			$newPayAmount = min($newRemain, $row["PayAmount"]*1);
			$newFundDelayShare = round($newResult["TotalFundDelay"]*$newPayAmount/$newTotalBackPay);
		}
		if($NewPartObj->AgentDelayReturn == "INSTALLMENT")
		{
			$newPayAmount = min($newRemain, $row["PayAmount"]*1);
			$newAgentDelayShare = round($newResult["TotalAgentDelay"]*$newPayAmount/$newTotalBackPay);
		}
		if($NewPartObj->WageReturn == "INSTALLMENT")
		{
			$newPayAmount = min($newRemain, $row["PayAmount"]*1);
			$newFundWageShare = round($newResult["TotalFundWage"]*$newPayAmount/$newTotalBackPay);
		}
		$newRemain -= $newPayAmount;
		
		$diffFundDelayShare += $newFundDelayShare - $prevFundDelayShare;
		$diffAgentDelayShare += $newAgentDelayShare - $prevAgentDelayShare;
		$diffFundWageShare += $newFundWageShare - $prevFundWageShare;
	}
	if($diffFundWageShare != 0)
	{
		$itemObj = new ACC_DocItems();
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_wage;
		$itemObj->DebtorAmount = $diffFundWageShare < 0 ? abs($diffFundWageShare) : 0;
		$itemObj->CreditorAmount = $diffFundWageShare > 0 ? $diffFundWageShare : 0;	
		$itemObj->locked = "YES";
		$itemObj->details = "اختلاف پرداخت های مشتری وام شماره " . $ReqObj->RequestID;
		$itemObj->SourceID = $ReqObj->RequestID;
		$itemObj->SourceID2 = $NewPartObj->PartID;		
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف مازاد");
			return false;
		}
		if($LoanMode == "Agent")
		{
			$itemObj = new ACC_DocItems();
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostCode_deposite;
			$itemObj->DebtorAmount = $diffFundWageShare > 0 ? $diffFundWageShare : 0;	
			$itemObj->CreditorAmount = $diffFundWageShare < 0 ? abs($diffFundWageShare) : 0;
			$itemObj->locked = "YES";
			$itemObj->details = "اختلاف پرداخت های مشتری وام شماره " . $ReqObj->RequestID;
			$itemObj->SourceID = $ReqObj->RequestID;
			$itemObj->SourceID2 = $NewPartObj->PartID;		
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $ReqPersonTafsili;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف مازاد");
				return false;
			}
		}
		else
		{
			$CostCode_pasandaz = FindCostID("209-10");
			$itemObj = new ACC_DocItems();
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostCode_pasandaz;
			$itemObj->DebtorAmount = $diffFundWageShare > 0 ? $diffFundWageShare : 0;	
			$itemObj->CreditorAmount = $diffFundWageShare < 0 ? abs($diffFundWageShare) : 0;
			$itemObj->locked = "YES";
			$itemObj->details = "اختلاف پرداخت های مشتری وام شماره " . $ReqObj->RequestID;
			$itemObj->SourceID = $ReqObj->RequestID;
			$itemObj->SourceID2 = $NewPartObj->PartID;		
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف مازاد");
				return false;
			}
		}
	}
	//---------------------------------------------------------
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $obj;
}

function RegisterDifferncePartsDoc_Supporter($ReqObj, $NewPartObj, $pdo, $DocID=""){
	
	CheckCloseCycle();
	
	$dt = PdoDataAccess::runquery("select * from LON_ReqParts 
		where RequestID=? AND IsHistory='YES' order by PartID desc limit 1", array($ReqObj->RequestID));
	$PreviousPartObj = new LON_ReqParts($dt[0]["PartID"]);
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_FundComit_wage = FindCostID("721-".$LoanObj->_BlockCode."-52-03");
	$CostCode_FundComit_mande = FindCostID("721-".$LoanObj->_BlockCode."-52-02");
	$CostCode_CustomerComit = FindCostID("721-".$LoanObj->_BlockCode."-51");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	
	$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
	if(!$ReqPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->ReqPersonID . "]");
		return false;
	}
	//---------------- add doc header --------------------
	if($DocID == "")
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_LOAN_DIFFERENCE;
		$obj->description = "اختلاف حاصل از شرایط جدید برای وام شماره " . $ReqObj->RequestID;
		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
	{
		$obj = new ACC_docs($DocID);
		PdoDataAccess::runquery("delete from ACC_DocItems where DocID=?", array($DocID), $pdo);
	}
	//--------------------------------------------------------
	PdoDataAccess::runquery("delete from LON_BackPays 
			where RequestID=? AND PayType=? AND PayBillNo=?", 
			array($ReqObj->RequestID, BACKPAY_PAYTYPE_CORRECT, $NewPartObj->PartID), $pdo);
	//--------------------------------------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_LOAN_DIFFERENCE;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $NewPartObj->PartID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $ReqPersonTafsili;
	//--------------------------------------------------------
	// compute the differnce of wage of CUSTOMER
	$PreviousAgentWage = 0;
	if($PreviousPartObj->AgentReturn == "CUSTOMER")
	{
		$totalWage = $PreviousPartObj->PartAmount*$PreviousPartObj->CustomerWage/100;
		$AgentFactor = ($PreviousPartObj->CustomerWage*1-$PreviousPartObj->FundWage*1)/$PreviousPartObj->CustomerWage*1;
		$PreviousAgentWage = $totalWage*$AgentFactor;		
	}
	$NewAgentWage = 0;
	if($NewPartObj->AgentReturn == "CUSTOMER")
	{
		$totalWage = $NewPartObj->PartAmount*$NewPartObj->CustomerWage/100;
		$AgentFactor = ($NewPartObj->CustomerWage*1-$NewPartObj->FundWage*1)/$NewPartObj->CustomerWage*1;
		$NewAgentWage = $totalWage*$AgentFactor;		
	}
	if($PreviousAgentWage <> $NewAgentWage)
	{
		$diff = $NewAgentWage - $PreviousAgentWage ;
		unset($itemObj->ItemID);
		$itemObj->CostID = COSTID_Bank;
		$itemObj->locked = "NO";
		$itemObj->DebtorAmount = $diff > 0 ? $diff : 0;
		$itemObj->CreditorAmount = $diff < 0 ? -1*$diff : 0;
		$itemObj->Add($pdo);
		
		unset($itemObj->ItemID);
		$itemObj->locked = "YES";
		$itemObj->CostID = $CostCode_FundComit_mande;
		$itemObj->DebtorAmount = $diff < 0 ? -1*$diff : 0;
		$itemObj->CreditorAmount = $diff > 0 ? $diff : 0;
		$itemObj->Add($pdo);		
	}
	//--------------------------------------------------------
	// compute the extra pay
	$remain = LON_requests::GetTotalRemainAmount($NewPartObj->RequestID);
	$extraPay = 0;
	if($remain < 0)
	{
		$extraPay = abs($remain);

		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $extraPay;
		$itemObj->CreditorAmount = 0;
		$itemObj->Add($pdo);
		
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_CustomerComit;
		$itemObj->CreditorAmount = $extraPay;
		$itemObj->DebtorAmount = 0;
		$itemObj->Add($pdo);
		
		$backPayObj = new LON_BackPays();
		$backPayObj->RequestID = $ReqObj->RequestID;
		$backPayObj->PayAmount = -1*$extraPay;
		$backPayObj->PayDate = $NewPartObj->PartDate;
		$backPayObj->PayBillNo = $NewPartObj->PartID;
		$backPayObj->PayType = BACKPAY_PAYTYPE_CORRECT;
		$backPayObj->details = "اختلاف کارمزد حاصل از تغییر شرایط پرداخت";
		$backPayObj->Add($pdo);
		
	}
	//--------------------------------------------------------
	// compute the differnce of PartPAy
	
	if($PreviousPartObj->PartAmount*1 <> $NewPartObj->PartAmount*1)
	{
		$diff = $PreviousPartObj->PartAmount*1 - $NewPartObj->PartAmount*1;
				
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $diff<0 ? -1*$diff : 0;
		$itemObj->CreditorAmount = $diff>0 ? $diff : 0;
		$itemObj->details = "اختلاف مبلغ پرداخت";
		$itemObj->Add($pdo);
		
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_CustomerComit;
		$itemObj->DebtorAmount = $diff>0 ? $diff : 0;
		$itemObj->CreditorAmount = $diff<0 ? -1*$diff : 0;
		$itemObj->details = "اختلاف مبلغ پرداخت";
		$itemObj->Add($pdo);
	}
	
	//--------------------------------------------------------
	// compute the differnce of wage of INSTALLMENT
	$PreviousInstallmentWage = 0;
	if($PreviousPartObj->AgentReturn == "INSTALLMENT")
		$PreviousInstallmentWage = ComputeWageOfSHekoofa($PreviousPartObj);		

	$NewInstallmentWage = 0;
	if($NewPartObj->AgentReturn == "INSTALLMENT")
		$NewInstallmentWage = ComputeWageOfSHekoofa($NewPartObj);
	
	if($PreviousInstallmentWage <> $NewInstallmentWage)
	{
		$diff = $NewInstallmentWage - $PreviousInstallmentWage ;
		
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_Loan;
		$itemObj->DebtorAmount = $diff>0 ? $diff : 0;
		$itemObj->CreditorAmount = $diff<0 ? -1*$diff : 0;
		$itemObj->details = "اختلاف کارمزد";
		$itemObj->Add($pdo);
		
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_CustomerComit;
		$itemObj->CreditorAmount = $diff>0 ? $diff : 0;
		$itemObj->DebtorAmount = $diff<0 ? -1*$diff : 0;
		$itemObj->details = "اختلاف کارمزد";
		$itemObj->Add($pdo);
		
		/*if($diff < 0)
		{
			$backPayObj = new LON_BackPays();
			$backPayObj->RequestID = $ReqObj->RequestID;
			$backPayObj->PayAmount = $diff;
			$backPayObj->PayDate = $NewPartObj->PartDate;
			$backPayObj->PayBillNo = $NewPartObj->PartID;
			$backPayObj->PayType = BACKPAY_PAYTYPE_CORRECT;
			$backPayObj->details = "اختلاف کارمزد حاصل از تغییر شرایط پرداخت";
			$backPayObj->Add($pdo);
		}*/
	}
	//---------------------------------------------------------
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $obj;
}
//---------------------------------------------------------------
function RegisterChangeInstallmentWage($DocID, $ReqObj,$PartObj, $InstallmentObj, $newDate, $wage, $pdo){
		
	CheckCloseCycle();
	//------------- get CostCodes --------------------
	$LoanMode = "";
	if(!empty($ReqObj->ReqPersonID))
	{
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsAgent == "YES")
			$LoanMode = "Agent";
	}
	else
		$LoanMode = "Customer";
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی وام گیرنده یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	if($LoanMode == "Agent")
	{
		$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
		if(!$ReqPersonTafsili)
		{
			ExceptionHandler::PushException("تفصیلی سرمایه گذار یافت نشد.[" . $ReqObj->ReqPersonID . "]");
			return false;
		}
	}	
	//------------------------------------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_wage = FindCostID("750" . "-" . $LoanObj->_BlockCode);
	$CostCode_FutureWage = FindCostID("760" . "-" . $LoanObj->_BlockCode);
	$CostCode_agent_wage = FindCostID("730");
	$CostCode_agent_FutureWage = FindCostID("740");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//---------------- add doc header --------------------
	if($DocID == "")
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_INSTALLMENT_CHANGE;
		$obj->description = "اختلاف حاصل از تغییر قسط وام شماره " . $ReqObj->RequestID;
		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
	{
		$obj = new ACC_docs($DocID);
	}
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	if($LoanMode == "Agent")
	{
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
	}
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_INSTALLMENT_CHANGE;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $InstallmentObj->InstallmentID;
	
	//...............Loan ..............................
	$itemObj->CostID = $CostCode_Loan;
	$itemObj->DebtorAmount = $wage > 0 ? $wage : 0;
	$itemObj->CreditorAmount = $wage < 0 ? abs($wage) : 0;
	$itemObj->Add($pdo);
	//..........  wage .................................
	unset($itemObj->TafsiliType2);
	unset($itemObj->TafsiliID2);
	
	$MaxWage =		max($PartObj->CustomerWage*1 , $PartObj->FundWage);
	$FundFactor =	$MaxWage == 0 ? 0 : $PartObj->FundWage/$MaxWage;
	$AgentFactor =	$MaxWage == 0 ? 0 : ($PartObj->CustomerWage-$PartObj->FundWage)/$MaxWage;
	
	$curYear = $_SESSION["accounting"]["CycleYear"]*1;
	if($wage < 0)
		$years = SplitYears($newDate, $InstallmentObj->InstallmentDate, $wage);
	else
		$years = SplitYears($InstallmentObj->InstallmentDate, $newDate, $wage);
	
	foreach($years as $Year => $amount)
	{
		if($amount == 0)
			continue;
		$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
		if(!$YearTafsili)
		{
			ExceptionHandler::PushException("تفصیلی سال یافت نشد.[" . $Year . "]");
			return false;
		}
		unset($itemObj->ItemID);
		$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = $YearTafsili;
		$itemObj->CreditorAmount = round($FundFactor*$amount) > 0 ? round($FundFactor*$amount) : 0;
		$itemObj->DebtorAmount = round($FundFactor*$amount) < 0 ? abs(round($FundFactor*$amount)) : 0;
		$itemObj->Add($pdo);
		
		if($AgentFactor > 0)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $Year == $curYear ? $CostCode_agent_wage : $CostCode_agent_FutureWage;
			$itemObj->TafsiliType = TAFTYPE_YEARS;
			$itemObj->TafsiliID = $YearTafsili;
			$itemObj->CreditorAmount = round($AgentFactor*$amount) > 0 ? round($AgentFactor*$amount) : 0;
			$itemObj->DebtorAmount = round($AgentFactor*$amount) < 0 ? abs(round($AgentFactor*$amount)) : 0;
			$itemObj->Add($pdo);
		}
	}
	//---------------------------------------------------------
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $obj->DocID;
}

function RegisterCustomerPayDoc($DocObj, $PayObj, $CostID, $TafsiliID, $TafsiliID2, 
		$CenterAccount, $BranchID, $FirstCostID, $SecondCostID, $pdo, $grouping=false){
	
	if(isset($_SESSION["accounting"]) )
		CheckCloseCycle();
	/*@var $PayObj LON_BackPays */
	$ReqObj = new LON_requests($PayObj->RequestID);
	$PartObj = LON_ReqParts::GetValidPartObj($PayObj->RequestID);
	if($DocObj == null)
	{
		$dt = PdoDataAccess::runquery("select * from ACC_DocItems where SourceType=" . 
			DOCTYPE_INSTALLMENT_PAYMENT . " AND SourceID=? AND SourceID2=?" , 
			array($ReqObj->RequestID, $PayObj->BackPayID));
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("سند این ردیف پرداخت قبلا صادر شده است");
			return false;
		}
	}
	
	$CycleID = isset($_SESSION["accounting"]) ? $_SESSION["accounting"]["CycleID"] : substr(DateModules::shNow(), 0 , 4);
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_deposite = FindCostID("210-01");
	$CostCode_wage = FindCostID("750" . "-" . $LoanObj->_BlockCode);
	$CostCode_commitment = FindCostID("200-" . $LoanObj->_BlockCode . "-51");
	
	if(empty($CostCode_Loan))
	{
		ExceptionHandler::PushException("کد حساب مربوط به وام تعریف نشده است");
		return false;
	}
	//---------------- add doc header --------------------
	if($DocObj == null)
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_INSTALLMENT_PAYMENT;
		if($grouping)
			$obj->description = "پرداخت گروهی اقساط";
		else
			$obj->description = "پرداخت قسط وام شماره " . 
				$ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;

		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		
	}
	else
		$obj = $DocObj;
	
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	
	$LoanMode = "";
	if(!empty($ReqObj->ReqPersonID))
	{
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsAgent == "YES")
			$LoanMode = "Agent";
	}
	else
		$LoanMode = "Customer";
	
	if($LoanMode == "Agent")
	{
		$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
		if(!$ReqPersonTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->ReqPersonID . "]");
			return false;
		}
		$SubAgentTafsili = "";
		if(!empty($ReqObj->SubAgentID))
		{
			$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
			if(!$SubAgentTafsili)
			{
				ExceptionHandler::PushException("تفصیلی زیر واحد سرمایه گذار یافت نشد.[" . $ReqObj->SubAgentID . "]");
				return false;
			}
		}
	}
	//----------------------compute -----------------------
	
	$firstPayDate = $PartObj->PartDate;
	$PayObj->PayAmount = $PayObj->PayAmount*1;
	$result = ComputeWagesAndDelays($PartObj, $PartObj->PartAmount, $firstPayDate, $PartObj->PartDate);
	$TotalFundWage = $result["TotalFundWage"];
	$TotalCustomerWage = $result["TotalFundWage"];
	$TotalAgentWage = $result["TotalAgentWage"];
	$TotalFundDelay = $result["TotalFundDelay"];
	$TotalAgentDelay = $result["TotalAgentDelay"];
	$totalBackPay = $PartObj->PartAmount*1 + 
		GetExtraLoanAmount($PartObj,$TotalFundWage,$TotalCustomerWage,$TotalAgentWage,$TotalFundDelay, $TotalAgentDelay);
			
	$FundDelayShare = $AgentDelayShare = $FundWageShare = 0;
	if($PartObj->DelayReturn == "INSTALLMENT")
		$FundDelayShare = round($TotalFundDelay*$PayObj->PayAmount/$totalBackPay);
	if($PartObj->AgentDelayReturn == "INSTALLMENT")
		$AgentDelayShare = round($TotalAgentDelay*$PayObj->PayAmount/$totalBackPay);
	if($PartObj->WageReturn == "INSTALLMENT")
		$FundWageShare = round($TotalFundWage*$PayObj->PayAmount/$totalBackPay);
	if($PartObj->AgentReturn == "INSTALLMENT")	
		$AgentWageShare = round($TotalAgentWage*$PayObj->PayAmount/$totalBackPay);

	//----------------- get total remain ---------------------
	PdoDataAccess::runquery("delete from LON_BackPays 
		where RequestID=? AND PayType=? AND PayBillNo=?", 
		array($ReqObj->RequestID, BACKPAY_PAYTYPE_CORRECT, $PayObj->BackPayID));
	
	require_once getenv("DOCUMENT_ROOT") . '/loan/request/request.class.php';
	$dt = array();
	$returnArr = LON_requests::ComputePayments($PayObj->RequestID, $dt, $pdo);
	$remain = LON_requests::GetTotalRemainAmount($PayObj->RequestID, $returnArr);
	$ExtraPay = 0;
	if($remain < 0)
	{
		$ExtraPay = abs($remain);
		$ExtraPay = min($ExtraPay,$PayObj->PayAmount*1);
		
		$backPayObj = new LON_BackPays();
		$backPayObj->RequestID = $PayObj->RequestID;
		$backPayObj->PayAmount = -1*$ExtraPay;
		$backPayObj->PayDate = $PayObj->PayDate;
		$backPayObj->PayType = BACKPAY_PAYTYPE_CORRECT;
		$backPayObj->PayBillNo = $PayObj->BackPayID;
		$backPayObj->details = "بابت اضافه پرداختی مشتری و انتقال به حساب قرض الحسنه";
		$backPayObj->Add($pdo);	
		$PayObj->PayAmount = $PayObj->PayAmount*1 - $ExtraPay;
	}

	//----------------- add Doc items ------------------------
		
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->details = "پرداخت قسط وام شماره " . $ReqObj->RequestID ;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	if($LoanMode == "Agent")
	{
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
	}
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_INSTALLMENT_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PayObj->BackPayID;
	
	//---------------- loan -----------------
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_Loan;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $PayObj->PayAmount;
	if(!$itemObj->Add($pdo))
	{
		print_r(ExceptionHandler::PopAllExceptions());
		ExceptionHandler::PushException("خطا در ایجاد ردیف وام 1");
		return false;
	}
	// -------------- bank ---------------
	if($CenterAccount)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->locked = "NO";
		$itemObj->CostID = $FirstCostID;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف محل پرداخت");
			return false;
		}
		
		$Secobj = new ACC_docs();
		$Secobj->RegDate = PDONOW;
		$Secobj->regPersonID = $_SESSION['USER']["PersonID"];
		$Secobj->DocDate = PDONOW;
		$Secobj->CycleID = $CycleID;
		$Secobj->BranchID = $BranchID;
		$Secobj->DocType = DOCTYPE_INSTALLMENT_PAYMENT;
		$Secobj->description = "پرداخت قسط وام شماره " . 
			$ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;

		if(!$Secobj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند شعبه واسط ");
			return false;
		}
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->DocID = $Secobj->DocID;
		$itemObj->CostID = $SecondCostID;
		$itemObj->DebtorAmount= 0;
		$itemObj->CreditorAmount = $PayObj->PayAmount + $ExtraPay;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف سند شعبه واسط");
			return false;
		}
		
		$CostObj = new ACC_CostCodes($CostID);
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->CostID = $CostID;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = $CostObj->TafsiliType;
		if($TafsiliID != "")
			$itemObj->TafsiliID = $TafsiliID;
		$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
		if($TafsiliID2 != "")
			$itemObj->TafsiliID2 = $TafsiliID2;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف سند شعبه واسط");
			return false;
		}		
	}
	else
	{		
		$CostObj = new ACC_CostCodes($CostID);
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->locked = "NO";
		$itemObj->CostID = $CostID;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = $CostObj->TafsiliType;
		if($TafsiliID != "")
			$itemObj->TafsiliID = $TafsiliID;
		$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
		if($TafsiliID2 != "")
			$itemObj->TafsiliID2 = $TafsiliID2;

		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف بانک");
			return false;
		}		
	}
	$itemObj->locked = "YES";
	$itemObj->DocID = $obj->DocID;
	unset($itemObj->TafsiliType);
	unset($itemObj->TafsiliID);
	unset($itemObj->TafsiliType2);
	unset($itemObj->TafsiliID2);
	//-------------- extra to Pasandaz ----------------
	if($ExtraPay > 0)
	{
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = COSTID_saving;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $ExtraPay;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف وام2");
			return false;
		}
	}
	if($LoanMode == "Agent")
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_commitment;
		$itemObj->DebtorAmount = $PayObj->PayAmount;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $LoanPersonTafsili;
		$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
		$itemObj->TafsiliID2 = $ReqPersonTafsili;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف تعهد");
			return false;
		}
	}
	//------------------ forfeit amount -----------------------
	for($j=0; $j < count($returnArr); $j++)
		if($returnArr[$j]["ActionType"] == "pay" && $returnArr[$j]["InstallmentID"] == $PayObj->BackPayID)
			break;
	$forfeitAmount = $returnArr[ $j-1 ]["ForfeitAmount"]*1 - 
			$returnArr[ $j ]["ForfeitAmount"]*1 + $returnArr[ $j ]["CurForfeitAmount"]*1;		
	$forfeitAmount = min($forfeitAmount, $PayObj->PayAmount);
	if($forfeitAmount > 0 && $LoanMode == "Agent")
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->details = "مبلغ تاخیر وام شماره " . $ReqObj->RequestID ;
		$itemObj->CostID = $CostCode_wage;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $forfeitAmount;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف سپرده");
			return false;
		}
		$PayObj->PayAmount -= $forfeitAmount;
	}
	if($PayObj->PayAmount == 0)
		return ExceptionHandler::GetExceptionCount() == 0;
	//------------------- delay amount ---------------------
	if($PartObj->DelayReturn == "INSTALLMENT" && $LoanMode == "Agent" && $FundDelayShare > 0)
	{
		$FundDelayShare = min($FundDelayShare, $PayObj->PayAmount );
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->CostID = $CostCode_wage;
		$itemObj->details = "بابت تنفس وام " . $PayObj->RequestID;
		$itemObj->DebtorAmount = 0;
		$itemObj->SourceID3 = "1";
		$itemObj->CreditorAmount = $FundDelayShare;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		$PayObj->PayAmount = $PayObj->PayAmount - $FundDelayShare;
		unset($itemObj->SourceID3);
	}
	if($PayObj->PayAmount == 0)
		return ExceptionHandler::GetExceptionCount() == 0;
	
	if($LoanMode == "Agent" && $PartObj->AgentDelayReturn == "INSTALLMENT" && $AgentDelayShare > 0)
	{
		$AgentDelayShare = min($AgentDelayShare, $PayObj->PayAmount);
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->CostID = $CostCode_deposite;
		$itemObj->DebtorAmount = 0;
		$itemObj->details = "بابت تنفس وام " . $PayObj->RequestID;
		$itemObj->SourceID3 = "1";
		$itemObj->CreditorAmount = $AgentDelayShare;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		$PayObj->PayAmount = $PayObj->PayAmount - $AgentDelayShare;
		unset($itemObj->SourceID3);
	}
	if($PayObj->PayAmount == 0)
		return ExceptionHandler::GetExceptionCount() == 0;
	//------------- wage --------------
	if($LoanMode == "Agent")
	{
		if($PartObj->WageReturn == "INSTALLMENT" && $FundWageShare > 0)
		{
			$FundWageShare = min($FundWageShare, $PayObj->PayAmount);
			$PayObj->PayAmount -= $FundWageShare;
			
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType);
			unset($itemObj->TafsiliID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			$itemObj->details = "کارمزد صندوق وام شماره " . $ReqObj->RequestID ;
			$itemObj->CostID = $CostCode_wage;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $FundWageShare;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد ردیف سپرده");
				return false;
			}
		}
		if($PayObj->PayAmount == 0)
			return ExceptionHandler::GetExceptionCount() == 0;
		
		//---- اضافه به سپرده -----
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		$itemObj->details = "پرداخت قسط وام شماره " . $ReqObj->RequestID ;
		$itemObj->CostID = $CostCode_deposite;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $PayObj->PayAmount*1;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		if($SubAgentTafsili != "")
		{
			$itemObj->TafsiliType2 = TAFTYPE_SUBAGENT;
			$itemObj->TafsiliID2 = $SubAgentTafsili;
		}
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف سپرده");
			return false;
		}
	}
	
	//---------------------------------------------------------
	//print_r(ExceptionHandler::PopAllExceptions());
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
		
	return true;
}

function RegisterSHRTFUNDCustomerPayDoc($DocObj, $PayObj, $CostID, $TafsiliID, $TafsiliID2, 
		$CenterAccount, $BranchID, $FirstCostID, $SecondCostID, $pdo, $grouping=false){
	
	if(isset($_SESSION["accounting"]) )
		CheckCloseCycle();
	/*@var $PayObj LON_BackPays */
	$ReqObj = new LON_requests($PayObj->RequestID);
	$PartObj = LON_ReqParts::GetValidPartObj($PayObj->RequestID);
	
	if($DocObj == null)
	{
		$dt = PdoDataAccess::runquery("select * from ACC_DocItems where SourceType=" . DOCTYPE_INSTALLMENT_PAYMENT . " 
			AND SourceID=? AND SourceID2=?" , array($ReqObj->RequestID, $PayObj->BackPayID));
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("سند این ردیف پرداخت قبلا صادر شده است");
			return false;
		}
	}
	
	$CycleID = isset($_SESSION["accounting"]) ? $_SESSION["accounting"]["CycleID"] : substr(DateModules::shNow(), 0 , 4);
	
	//------------- get CostCodes --------------------
	$LoanObj = new LON_loans($ReqObj->LoanID);
	$CostCode_Loan = FindCostID("110" . "-" . $LoanObj->_BlockCode);
	$CostCode_bank = FindCostID("101");
	$CostCode_varizi = FindCostID("721-".$LoanObj->_BlockCode."-52-01");
	$CostCode_CustomerComit = FindCostID("721-".$LoanObj->_BlockCode."-51");
	//---------------- add doc header --------------------
	if($DocObj == null)
	{
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_INSTALLMENT_PAYMENT;
		if($grouping)
			$obj->description = "پرداخت گروهی اقساط";
		else
			$obj->description = "پرداخت قسط وام شماره " . 
				$ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;

		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
		$obj = $DocObj;
	
	//------------------ find tafsilis ---------------
	$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
	if(!$LoanPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
		return false;
	}
	
	$ReqPersonTafsili = FindTafsiliID($ReqObj->ReqPersonID, TAFTYPE_PERSONS);
	if(!$ReqPersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->ReqPersonID . "]");
		return false;
	}
	$SubAgentTafsili = "";
	if(!empty($ReqObj->SubAgentID))
	{
		$SubAgentTafsili = FindTafsiliID($ReqObj->SubAgentID, TAFTYPE_SUBAGENT);
		if(!$SubAgentTafsili)
		{
			ExceptionHandler::PushException("تفصیلی زیر واحد سرمایه گذار یافت نشد.[" . $ReqObj->SubAgentID . "]");
			return false;
		}
	}
	//----------------- get total remain ---------------------
	PdoDataAccess::runquery("delete from LON_BackPays 
		where RequestID=? AND PayType=? AND PayBillNo=?", 
		array($ReqObj->RequestID, BACKPAY_PAYTYPE_CORRECT, $PayObj->BackPayID));
	
	require_once getenv("DOCUMENT_ROOT") . '/loan/request/request.class.php';
	$remain = LON_requests::GetTotalRemainAmount($PayObj->RequestID);
	$ExtraPay = 0;
	if($remain < 0)
	{
		$ExtraPay = abs($remain);
		$ExtraPay = min($ExtraPay,$PayObj->PayAmount*1);
		
		$backPayObj = new LON_BackPays();
		$backPayObj->RequestID = $PayObj->RequestID;
		$backPayObj->PayAmount = -1*$ExtraPay;
		$backPayObj->PayDate = $PayObj->PayDate;
		$backPayObj->PayType = BACKPAY_PAYTYPE_CORRECT;
		$backPayObj->PayBillNo = $PayObj->BackPayID;
		$backPayObj->details = "بابت اضافه پرداختی مشتری";
		$backPayObj->Add($pdo);		
		
		$PayObj->PayAmount =  $PayObj->PayAmount*1 - $ExtraPay;
		
	}
	//----------------- add Doc items ------------------------
		
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->details = "پرداخت قسط وام شماره " . $ReqObj->RequestID ;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $LoanPersonTafsili;
	$itemObj->TafsiliType2 = TAFTYPE_PERSONS;
	$itemObj->TafsiliID2 = $ReqPersonTafsili;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_INSTALLMENT_PAYMENT;
	$itemObj->SourceID = $ReqObj->RequestID;
	$itemObj->SourceID2 = $PayObj->BackPayID;
	
	//---------------- loan -----------------
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostCode_Loan;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $PayObj->PayAmount;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	
	//------------ varizi ----------------
	unset($itemObj->ItemID);
	unset($itemObj->TafsiliType2);
	unset($itemObj->TafsiliID2);
	$itemObj->CostID = $CostCode_varizi;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $PayObj->PayAmount;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $ReqPersonTafsili;
	if($SubAgentTafsili != "")
	{
		$itemObj->TafsiliType2 = TAFTYPE_SUBAGENT;
		$itemObj->TafsiliID2 = $SubAgentTafsili;
	}
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}

	//--------------- pardakhtani -----------
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_CustomerComit;
	$itemObj->DebtorAmount = $PayObj->PayAmount;
	$itemObj->CreditorAmount = 0;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}

	// -------------- bank ---------------
	if($CenterAccount)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->locked = "NO";
		$itemObj->CostID = $FirstCostID;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		
		$Secobj = new ACC_docs();
		$Secobj->RegDate = PDONOW;
		$Secobj->regPersonID = $_SESSION['USER']["PersonID"];
		$Secobj->DocDate = PDONOW;
		$Secobj->CycleID = $CycleID;
		$Secobj->BranchID = $BranchID;
		$Secobj->DocType = DOCTYPE_INSTALLMENT_PAYMENT;
		$Secobj->description = "پرداخت قسط وام شماره " . 
				$ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;

		if(!$Secobj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->DocID = $Secobj->DocID;
		$itemObj->CostID = $SecondCostID;
		$itemObj->DebtorAmount= 0;
		$itemObj->CreditorAmount = $PayObj->PayAmount + $ExtraPay;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		
		$CostObj = new ACC_CostCodes($CostID);
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->CostID = $CostID;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = $CostObj->TafsiliType;
		if($TafsiliID != "")
			$itemObj->TafsiliID = $TafsiliID;
		$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
		if($TafsiliID2 != "")
			$itemObj->TafsiliID2 = $TafsiliID2;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
	{
		$CostObj = new ACC_CostCodes($CostID);
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliType2);
		unset($itemObj->TafsiliID2);
		unset($itemObj->TafsiliID);
		$itemObj->locked = "NO";
		$itemObj->CostID = $PayObj->PayType == "3" ? $CostCode_fund : $CostCode_bank;
		$itemObj->DebtorAmount= $PayObj->PayAmount + $ExtraPay;
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = $CostObj->TafsiliType;
		if($TafsiliID != "")
			$itemObj->TafsiliID = $TafsiliID;
		$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
		if($TafsiliID2 != "")
			$itemObj->TafsiliID2 = $TafsiliID2;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	//-------------- extra to Pasandaz ----------------
	if($ExtraPay > 0)
	{
		unset($itemObj->ItemID);
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_CustomerComit;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $ExtraPay;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $ReqPersonTafsili;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد ردیف وام");
			return false;
		}
	}
	//---------------------------------------------------------
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
		
	return true;
}

function ReturnCustomerPayDoc($PayObj, $pdo, $EditMode = false){
	
	CheckCloseCycle();
	/*@var $PayObj LON_BackPays */
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select d.DocID,LocalNo,CycleDesc
		from ACC_DocItems d join ACC_docs using(DocID) join ACC_cycles using(CycleID)
		where StatusID <> ".ACC_STEPID_RAW." 
			AND SourceType=" . DOCTYPE_INSTALLMENT_PAYMENT . " AND SourceID=? AND SourceID2=?",
		array($PayObj->RequestID, $PayObj->BackPayID), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems 
		where SourceType=" . DOCTYPE_INSTALLMENT_PAYMENT . " AND SourceID=? AND SourceID2=?",
		array($PayObj->RequestID, $PayObj->BackPayID), $pdo);
	if(count($dt) == 0)
		return true;
	
	PdoDataAccess::runquery("delete from ACC_DocItems 
		where SourceType=" . DOCTYPE_INSTALLMENT_PAYMENT . " AND SourceID=? AND SourceID2=?",
		array($PayObj->RequestID, $PayObj->BackPayID), $pdo);
	
	if(!$EditMode)
	{
		PdoDataAccess::runquery("delete d from ACC_docs d left join ACC_DocItems using(DocID)
		where ItemID is null AND DocID=?",	array($dt[0][0]), $pdo);
	}
	
	return true;
	
	//return ACC_docs::Remove($dt[0][0], $pdo);
}
//---------------------------------------------------------------

function RegisterEndRequestDoc($ReqObj, $pdo){
		
	CheckCloseCycle();
	
	require_once '../../loan/request/request.data.php';
	
	/*@var $ReqObj LON_requests */
	/*@var $PartObj LON_ReqParts */
	
	//---------- ردیف های تضمین  ----------
	
	$dt = PdoDataAccess::runquery("select * from DMS_documents 
		join BaseInfo b on(InfoID=DocType AND TypeID=8)
		where b.param1=1 AND ObjectType='loan' AND ObjectID=?", array($ReqObj->RequestID));
	
	if(count($dt) > 0)
	{
		$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
		$CostCode_guaranteeAmount_daryafti = FindCostID("904-04");
		$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
		$CostCode_guaranteeAmount2_daryafti = FindCostID("905-04");
		//------------------------------------------------

		$CycleID = $_SESSION["accounting"]["CycleID"];

		//---------------- add doc header --------------------
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $ReqObj->BranchID;
		$obj->DocType = DOCTYPE_END_REQUEST;
		$obj->description = "خاتمه وام شماره " . $ReqObj->RequestID . " به نام " . $ReqObj->_LoanPersonFullname;
		
		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}

		//------------------ find tafsilis ---------------
		$LoanPersonTafsili = FindTafsiliID($ReqObj->LoanPersonID, TAFTYPE_PERSONS);
		if(!$LoanPersonTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->LoanPersonID . "]");
			return false;
		}

		//----------------- add Doc items ------------------------
		$itemObj = new ACC_DocItems();
		$itemObj->DocID = $obj->DocID;
		$itemObj->locked = "YES";
		$itemObj->SourceID2 = $ReqObj->RequestID;
		
		$dt = PdoDataAccess::runquery("
			SELECT DocumentID, ParamValue, InfoDesc as DocTypeDesc
				FROM DMS_DocParamValues
				join DMS_DocParams using(ParamID)
				join DMS_documents d using(DocumentID)
				join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
			where b.param1=1 AND paramType='currencyfield' AND ObjectType='loan' AND ObjectID=?",
			array($ReqObj->RequestID), $pdo);

		$SumAmount = 0;
		foreach($dt as $row)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $row["ParamValue"];
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $LoanPersonTafsili;
			$itemObj->SourceType = DOCTYPE_DOCUMENT;
			$itemObj->SourceID = $row["DocumentID"];
			$itemObj->details = $row["DocTypeDesc"];
			$itemObj->Add($pdo);
			
			$SumAmount += $row["ParamValue"]*1;
		}
		//---------------------------------------------------------
		if($SumAmount > 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType);
			unset($itemObj->TafsiliID);
			unset($itemObj->TafsiliType2);
			unset($itemObj->TafsiliID2);
			unset($itemObj->details);
			$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
			$itemObj->DebtorAmount = $SumAmount;
			$itemObj->CreditorAmount = 0;	
			$itemObj->Add($pdo);

			/*unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_guaranteeCount;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = count($dt) + count($dt2);	
			$itemObj->Add($pdo);

			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_guaranteeCount2;
			$itemObj->DebtorAmount = count($dt) + count($dt2);
			$itemObj->CreditorAmount = 0;
			$itemObj->Add($pdo);*/
		}
	}
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return true;
}

function ReturnEndRequestDoc($ReqObj, $pdo){
		
	CheckCloseCycle();
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select d.DocID,LocalNo, CycleDesc
		from ACC_DocItems d join ACC_docs using(DocID) join ACC_cycles using(CycleID)
		where StatusID <> ".ACC_STEPID_RAW." AND DocType=" . DOCTYPE_END_REQUEST . " AND SourceID2=?",
			array($ReqObj->RequestID), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select d.DocID from ACC_DocItems d join ACC_docs using(DocID)
		where DocType=" . DOCTYPE_END_REQUEST . " AND SourceID2=?",
		array($ReqObj->RequestID), $pdo);
	
	if(count($dt) == 0)
		return true;
	
	return ACC_docs::Remove($dt[0]["DocID"], $pdo);
}

//---------------------------------------------------------------
function RegisterOuterCheque($DocID, $InChequeObj, $pdo, $CostID ="", $TafsiliID="", $TafsiliID2="", 
		$CenterAccount="", $BranchID="", $FirstCostID="", $SecondCostID="", $PreStatus = false){

	CheckCloseCycle();
	
	/*@var $InChequeObj ACC_IncomeCheques */
	
	$CycleID = $_SESSION["accounting"]["CycleID"];
	
	//------------- get CostCodes --------------------
	$CostCode_guaranteeAmount_daryafti = FindCostID("904-04");
	$CostCode_guaranteeAmount2_daryafti = FindCostID("905-04");
	//-------------------- BranchID -----------------------
	$BackPays = $InChequeObj->GetBackPays($pdo);
	if(count($BackPays) > 0)
		$FirstBranchID = $BackPays[0]["BranchID"];
	else
		$FirstBranchID = $_SESSION["accounting"]["BranchID"];
	//---------------- add doc header --------------------
	if($DocID == "")
	{		
		$obj = new ACC_docs();
		$obj->RegDate = PDONOW;
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = PDONOW;
		$obj->CycleID = $CycleID;
		$obj->BranchID = $FirstBranchID;
		$obj->DocType = DOCTYPE_INCOMERCHEQUE;
		$obj->description = "چک شماره " . $InChequeObj->ChequeNo;
		if(!$obj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
		$obj = new ACC_docs($DocID, $pdo);
	//----------------- add Doc items ------------------------
	
	$__ChequeAmount = $InChequeObj->ChequeAmount;
	$__ChequeID = $InChequeObj->IncomeChequeID;
	$__TafsiliID = $InChequeObj->TafsiliID;
	if($__TafsiliID == "")
	{
		$dt = $InChequeObj->GetBackPays();
		if(count($dt) == 1)
			$__TafsiliID = $dt[0]["TafsiliID"];
	}
	$__SourceType = DOCTYPE_INCOMERCHEQUE;
	
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->locked = "YES";
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $__TafsiliID;
	$itemObj->TafsiliType2 = TAFTYPE_ChequeStatus;
	$itemObj->TafsiliID2 = $InChequeObj->ChequeStatus;
	$itemObj->SourceType = $__SourceType;
	$itemObj->SourceID = $__ChequeID;
	$itemObj->details = "چک شماره " . $InChequeObj->ChequeNo;
	
	//............ register status related rows .........................
	if($PreStatus !== false)
	{
		$PreStatus = PdoDataAccess::runquery("select * 
			from BaseInfo join ACC_tafsilis on(TafsiliType=".TAFTYPE_ChequeStatus." AND ObjectID=InfoID)
			where TypeID=4 AND TafsiliID=?", array($PreStatus));
		$PreStatus = $PreStatus[0];
	}
			
	$CurStatus = PdoDataAccess::runquery("select * 
			from BaseInfo join ACC_tafsilis on(TafsiliType=".TAFTYPE_ChequeStatus." AND ObjectID=InfoID)
			where TypeID=4 AND TafsiliID=?", array($InChequeObj->ChequeStatus));
	$CurStatus = $CurStatus[0];
	
	if($PreStatus !== false && $PreStatus["param1"] != "" && $PreStatus["param2"] != "")
	{
		unset($itemObj->ItemID);		
		$itemObj->CostID = $PreStatus["param1"];
		$itemObj->CreditorAmount = $__ChequeAmount;
		$itemObj->DebtorAmount = 0;
		$itemObj->TafsiliID2 = FindTafsiliID($PreStatus["InfoID"], TAFTYPE_ChequeStatus);
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $PreStatus["param2"];
		$itemObj->CreditorAmount = 0;
		$itemObj->DebtorAmount = $__ChequeAmount;
		$itemObj->TafsiliID2 = FindTafsiliID($PreStatus["InfoID"], TAFTYPE_ChequeStatus);
		$itemObj->Add($pdo);
	}
	if($CurStatus["param1"] != "" && $CurStatus["param2"] != "")
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CurStatus["param1"];
		$itemObj->CreditorAmount = 0;
		$itemObj->DebtorAmount = $__ChequeAmount;
		$itemObj->TafsiliID2 = FindTafsiliID($CurStatus["InfoID"], TAFTYPE_ChequeStatus);
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $CurStatus["param2"];
		$itemObj->CreditorAmount = $__ChequeAmount;
		$itemObj->DebtorAmount = 0;
		$itemObj->TafsiliID2 = FindTafsiliID($CurStatus["InfoID"], TAFTYPE_ChequeStatus);
		$itemObj->Add($pdo);
	}
	//--------------------------------------------------------------
	/*
	if($InChequeObj->ChequeStatus == INCOMECHEQUE_NOTVOSUL)
	{ 
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount_daryafti;
		$itemObj->DebtorAmount = $__ChequeAmount;
		$itemObj->CreditorAmount = 0;		
		
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount2_daryafti;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $__ChequeAmount;
		$itemObj->Add($pdo);		
		
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;

		return $obj->DocID;
	}*/
	//............................................................
	
	if($InChequeObj->ChequeStatus == INCOMECHEQUE_VOSUL)
	{
		/*unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount2_daryafti;
		$itemObj->DebtorAmount = $__ChequeAmount;
		$itemObj->CreditorAmount = 0;
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount_daryafti;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $__ChequeAmount;
		$itemObj->Add($pdo);
		*/
		if(count($BackPays) > 0)
		{
			foreach($BackPays as $row)
			{
				$BackPayObj = new LON_BackPays($row["BackPayID"]);
				$ReqObj = new LON_requests($BackPayObj->RequestID);
				$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
				if($PersonObj->IsSupporter == "YES")
					$result = RegisterSHRTFUNDCustomerPayDoc($obj,$BackPayObj,$CostID, $TafsiliID, $TafsiliID2, 
					$CenterAccount, $BranchID, $FirstCostID, $SecondCostID, $pdo);
				else
					$result = RegisterCustomerPayDoc($obj,$BackPayObj,$CostID, $TafsiliID, $TafsiliID2, 
					$CenterAccount, $BranchID, $FirstCostID, $SecondCostID, $pdo);
			}
		}
		else
		{
			if($CenterAccount == "true")
			{
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				unset($itemObj->TafsiliID);
				$itemObj->locked = "NO";
				$itemObj->CostID = $FirstCostID;
				$itemObj->DebtorAmount= $__ChequeAmount;
				$itemObj->CreditorAmount = 0;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد سند");
					return false;
				}

				$Secobj = new ACC_docs();
				$Secobj->RegDate = PDONOW;
				$Secobj->regPersonID = $_SESSION['USER']["PersonID"];
				$Secobj->DocDate = PDONOW;
				$Secobj->CycleID = $CycleID;
				$Secobj->BranchID = $BranchID;
				$Secobj->DocType = $__SourceType;
				if(!$Secobj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد سند");
					return false;
				}
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				unset($itemObj->TafsiliID);
				$itemObj->DocID = $Secobj->DocID;
				$itemObj->CostID = $SecondCostID;
				$itemObj->DebtorAmount= 0;
				$itemObj->CreditorAmount = $__ChequeAmount;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد سند");
					return false;
				}
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				unset($itemObj->TafsiliID);
				$CostObj = new ACC_CostCodes($CostID);
				$itemObj->CostID = $CostID;
				$itemObj->DebtorAmount= $__ChequeAmount;
				$itemObj->CreditorAmount = 0;
				$itemObj->TafsiliType = $CostObj->TafsiliType;
				if($TafsiliID != "")
					$itemObj->TafsiliID = $TafsiliID;
				$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
				if($TafsiliID2 != "")
					$itemObj->TafsiliID2 = $TafsiliID2;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد سند");
					return false;
				}
			}
			else
			{
				unset($itemObj->ItemID);
				unset($itemObj->TafsiliType);
				unset($itemObj->TafsiliType2);
				unset($itemObj->TafsiliID2);
				unset($itemObj->TafsiliID);
				$itemObj->locked = "NO";
				$CostObj = new ACC_CostCodes($CostID);
				$itemObj->CostID = $CostID;
				$itemObj->DebtorAmount= $__ChequeAmount;
				$itemObj->CreditorAmount = 0;
				$itemObj->TafsiliType = $CostObj->TafsiliType;
				if($TafsiliID != "")
					$itemObj->TafsiliID = $TafsiliID;
				$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
				if($TafsiliID2 != "")
					$itemObj->TafsiliID2 = $TafsiliID2;
				if(!$itemObj->Add($pdo))
				{
					ExceptionHandler::PushException("خطا در ایجاد سند");
					return false;
				}
			}
			
			unset($itemObj->ItemID);
			$itemObj->locked = "YES";
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $InChequeObj->CostID;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $__ChequeAmount;
			$itemObj->TafsiliType = $InChequeObj->TafsiliType;
			$itemObj->TafsiliID = $InChequeObj->TafsiliID;
			$itemObj->TafsiliType2 = $InChequeObj->TafsiliType2;
			$itemObj->TafsiliID2 = $InChequeObj->TafsiliID2;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد سند");
				return false;
			}
		}
		
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;

		return $obj->DocID;
	}
	//............................................................
/*
	if(array_search($InChequeObj->ChequeStatus, array(INCOMECHEQUE_EBTAL,INCOMECHEQUE_MOSTARAD,
			INCOMECHEQUE_BARGHASHTI_MOSTARAD,INCOMECHEQUE_MAKHDOOSH,INCOMECHEQUE_CHANGE)) !== false)
	{
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = $CostCode_guaranteeAmount_daryafti;
		$itemObj->CreditorAmount = $__ChequeAmount;
		$itemObj->DebtorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $__TafsiliID;
		$itemObj->SourceType = $__SourceType;
		$itemObj->SourceID = $__ChequeID;
		$itemObj->details = "چک شماره " . $InChequeObj->ChequeNo;
		$itemObj->Add($pdo);

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount2_daryafti;
		$itemObj->CreditorAmount = 0;
		$itemObj->DebtorAmount = $__ChequeAmount;
		$itemObj->Add($pdo);
	
		//---------------------------------------------------------
		if(ExceptionHandler::GetExceptionCount() > 0)
			return false;

		return true;
	}*/
	
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	return $obj->DocID;
}

function EditIncomeCheque($InChequeObj, $newAmount, $pdo){

	CheckCloseCycle();
	
	/*@var $InChequeObj ACC_IncomeCheques */
	
	$CycleID = $_SESSION["accounting"]["CycleID"];
	
	//------------- get CostCodes --------------------
	$CostCode_guaranteeAmount_daryafti = FindCostID("904-04");
	$CostCode_guaranteeAmount2_daryafti = FindCostID("905-04");
	//-------------------- BranchID -----------------------
	$BackPays = $InChequeObj->GetBackPays($pdo);
	if(count($BackPays) > 0)
		$FirstBranchID = $BackPays[0]["BranchID"];
	else
		$FirstBranchID = $_SESSION["accounting"]["BranchID"];
	//---------------- add doc header --------------------
	$obj = new ACC_docs();
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $CycleID;
	$obj->BranchID = $FirstBranchID;
	$obj->DocType = DOCTYPE_EDITINCOMECHEQUE;
	$obj->description = "چک شماره " . $InChequeObj->ChequeNo;
	if(!$obj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	//----------------- add Doc items ------------------------
	
	$__ChequeAmount = $InChequeObj->ChequeAmount;
	$__ChequeID = $InChequeObj->IncomeChequeID;
	$__TafsiliID = $InChequeObj->TafsiliID;
	if($__TafsiliID == "")
	{
		$dt = $InChequeObj->GetBackPays();
		if(count($dt) == 1)
			$__TafsiliID = $dt[0]["TafsiliID"];
	}
	$__SourceType = DOCTYPE_EDITINCOMECHEQUE;
	
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->locked = "YES";
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $__TafsiliID;
	$itemObj->TafsiliType2 = TAFTYPE_ChequeStatus;
	$itemObj->TafsiliID2 = INCOMECHEQUE_EDIT;
	$itemObj->SourceType = $__SourceType;
	$itemObj->SourceID = $__ChequeID;
	$itemObj->details = "چک شماره " . $InChequeObj->ChequeNo;
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_guaranteeAmount_daryafti;
	$itemObj->CreditorAmount = $__ChequeAmount;
	$itemObj->DebtorAmount = 0;		
	$itemObj->Add($pdo);

	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_guaranteeAmount2_daryafti;
	$itemObj->CreditorAmount = 0;
	$itemObj->DebtorAmount = $__ChequeAmount;
	$itemObj->Add($pdo);	
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_guaranteeAmount_daryafti;
	$itemObj->DebtorAmount = $newAmount;
	$itemObj->CreditorAmount = 0;		
	$itemObj->Add($pdo);

	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_guaranteeAmount2_daryafti;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $newAmount;
	$itemObj->Add($pdo);	

	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;

	return true;
}

//---------------------------------------------------------------

function ComputeDepositeProfit($ToDate, $Tafsilis, $ReportMode = false, $IsFlow = false){
	
	if(!$ReportMode)
		CheckCloseCycle();
	
	//-----------------------------------------------------
	$DepositeAmount = array(
		COSTID_ShortDeposite => array(),
		COSTID_LongDeposite => array()
	);
	$TraceArr = array();
	foreach($Tafsilis as $TafsiliID)
	{
		//-------------- get latest deposite compute -------------
		$FirstYearDay = DateModules::shamsi_to_miladi($_SESSION["accounting"]["CycleID"] . "-01-01", "-");
		if(!$IsFlow)
		{
			$dt = PdoDataAccess::runquery("select max(SourceID)
				from ACC_docs join ACC_DocItems using(DocID)
				where DocType=" . DOCTYPE_DEPOSIT_PROFIT . " 
					AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
					AND BranchID=" . $_SESSION["accounting"]["BranchID"] . "	
					AND TafsiliID=?
				order by DocID desc", array($TafsiliID));
			$LatestComputeDate = count($dt)==0 || $dt[0][0] == "" ? $FirstYearDay : $dt[0][0];
		}
		else
			$LatestComputeDate = $FirstYearDay;		

		//----------- check for all docs confirm --------------
		if(!$ReportMode)
		{
			$dt = PdoDataAccess::runquery("select group_concat(distinct LocalNo) from ACC_docs 
				join ACC_DocItems using(DocID)
				where CycleID=? AND DocID>=? AND CostID in(" . COSTID_ShortDeposite . "," . COSTID_LongDeposite . ")
				AND StatusID <> ".ACC_STEPID_CONFIRM."
				AND TafsiliID=?", array($_SESSION["accounting"]["CycleID"] ,$LatestComputeDate,$TafsiliID));
			if(count($dt) > 0 && $dt[0][0] != "")
			{
				echo Response::createObjectiveResponse(false, "اسناد با شماره های [" . $dt[0][0] . "] تایید نشده اند و قادر به صدور سند سود سپرده نمی باشید.");
				die();
			}
		}
		//------------ get percents ------------------------
		$percents = PdoDataAccess::runquery_fetchMode("select * from ACC_DepositePercents 
			where TafsiliID=? AND ToDate>? order by FromDate",array($TafsiliID, $LatestComputeDate));
		if($percents->rowCount() == 0)
		{
			echo Response::createObjectiveResponse(false, "در بازه مربوطه درصد سود تفصیلی " . $TafsiliID . " تعریف نشده است");
			die();
		}
		$percentRecord = $percents->fetch();
		//------------ get sum of deposites ----------------
		$dt = PdoDataAccess::runquery("select TafsiliID,CostID,sum(CreditorAmount-DebtorAmount) amount
			from ACC_DocItems join ACC_docs using(DocID)
			where DocDate <= ? 
				AND CostID in(" . COSTID_ShortDeposite . "," . COSTID_LongDeposite . ")
				AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
				AND BranchID=" . $_SESSION["accounting"]["BranchID"] . "
				AND TafsiliID=?
			group by TafsiliID,CostID", array($LatestComputeDate,$TafsiliID));

		foreach($dt as $row)
		{
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["amount"] = $row["amount"];
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["lastDate"] = $LatestComputeDate;
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["profit"] = 0;
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["ReturnProfit"] = 0;
			
			$row["DocDate"] = $LatestComputeDate;
			$row["DocDesc"] = "مانده قبل";
			$TraceArr[ $row["TafsiliID"] ][] = array(
				"row" => $row,
				"percent" => $percentRecord["percent"],
				"ReturnPercent" => $percentRecord["ReturnPercent"],
				"MaxAmount" => $percentRecord["MaxAmount"],
				"profit" => 0,
				"ReturnProfit" => 0,
				"days" => 0
			);
		}
		//------------ get the Deposite amount -------------
		$dt = PdoDataAccess::runquery("
			select CostID,TafsiliID,DocDate,group_concat(details SEPARATOR '<br>') DocDesc,
			sum(CreditorAmount-DebtorAmount) amount
			from ACC_DocItems 
				join ACC_docs using(DocID)
			where CostID in(" . COSTID_ShortDeposite . "," . COSTID_LongDeposite . ")
				AND DocDate > ? AND DocDate <= ?
				AND TafsiliID=?
				AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
				AND BranchID=" . $_SESSION["accounting"]["BranchID"] . "
			group by DocDate
			order by DocDate", 
				array($LatestComputeDate, $ToDate, $TafsiliID));
		
		$prevDays = 0;
		for($i=0; $i<count($dt); $i++)
		{
			if(!$percentRecord)
			{
				echo Response::createObjectiveResponse(false, "در بازه مربوطه درصد سود تفصیلی تعریف نشده است");
				die();	
			}
			$row = $dt[$i];			
			if(!isset($DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["lastDate"]))
			{
				$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["lastDate"] = $FirstYearDay;
				$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["amount"] = 0;
				$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["profit"] = 0;
				$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["ReturnProfit"] = 0;
				
			}
			$lastDate = $DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["lastDate"];
			$EndDate = $row["DocDate"];
			if($row["DocDate"] > $percentRecord["ToDate"])
			{
				$EndDate = $percentRecord["ToDate"];
			}
			
			$days = DateModules::GDateMinusGDate($EndDate, $lastDate);
			if($row["amount"]*1 < 0)
			{
				$days--;
				$days += $prevDays;
				$prevDays = 1;
			}
			else
			{
				$days += $prevDays;
				$prevDays = 0;
			}
			
			//-------------- compute profits ----------------
			$profit = 0;
			$returnProfit = 0;
			$amount = $DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["amount"];
			if($percentRecord["MaxAmount"]*1 > 0 && $amount < $percentRecord["MaxAmount"]*1)
				$returnProfit = ($percentRecord["MaxAmount"]*1 - $amount) * $days * 
						$percentRecord["ReturnPercent"] /(36500);
			else
				$profit = $amount * $days * $percentRecord["percent"] /(36500);
			//-----------------------------------------------
			
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["profit"] += $profit;
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["ReturnProfit"] += $returnProfit;
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["amount"] += $row["amount"];
			$DepositeAmount[ $row["CostID"] ][ $row["TafsiliID"] ]["lastDate"] = $EndDate;	
			
			$arr = &$TraceArr[ $row["TafsiliID"] ];
			$arr[count($arr)-1]["days"] = $days;
			$arr[count($arr)-1]["profit"] = $profit;
			$arr[count($arr)-1]["ReturnProfit"] = $returnProfit;
			
			$TraceArr[ $row["TafsiliID"] ][] = array(
				"row" => $row,
				"percent" => $percentRecord["percent"],
				"ReturnPercent" => $percentRecord["ReturnPercent"],
				"MaxAmount" => $percentRecord["MaxAmount"],
				"days" => 0,
				"profit" => 0,
				"ReturnProfit" => 0
			);
			
			if($row["DocDate"] > $percentRecord["ToDate"])
			{
				$percentRecord = $percents->fetch();
				$i--;
			}			
		}
		
		//--------------------- compute untill toDate ------------------------------
		foreach($DepositeAmount[ COSTID_ShortDeposite ] as $tafsili => &$row)
		{
			$days = DateModules::GDateMinusGDate($ToDate, $row["lastDate"]);
			$days += $prevDays;
			//-------------- compute profits ----------------
			$profit = 0;
			$returnProfit = 0;
			$amount = $row["amount"];
			if($percentRecord["MaxAmount"]*1 > 0 && $amount < $percentRecord["MaxAmount"]*1)
				$returnProfit = ($percentRecord["MaxAmount"]*1 - $amount) * $days * 
						$percentRecord["ReturnPercent"] /(36500);
			else
				$profit = $amount * $days * $percentRecord["percent"] /(36500);
			//-----------------------------------------------
			
			$row["profit"] += $profit;
			$row["ReturnProfit"] += $returnProfit;

			$arr = &$TraceArr[ $tafsili ];
			$arr[count($arr)-1]["days"] = $days;
			$arr[count($arr)-1]["profit"] = $profit;
			$arr[count($arr)-1]["ReturnProfit"] = $returnProfit;
		}
		foreach($DepositeAmount[ COSTID_LongDeposite ] as $tafsili => &$row)
		{
			$days = DateModules::GDateMinusGDate($ToDate, $row["lastDate"]);
			$days += $prevDays;
			//-------------- compute profits ----------------
			$profit = 0;
			$returnProfit = 0;
			$amount = $row["amount"];
			if($percentRecord["MaxAmount"]*1 > 0 && $amount < $percentRecord["MaxAmount"]*1)
				$returnProfit = ($percentRecord["MaxAmount"]*1 - $amount) * $days * 
						$percentRecord["ReturnPercent"] /(36500);
			else
				$profit = $amount * $days * $percentRecord["percent"] /(36500);
			//-----------------------------------------------
			
			$row["profit"] += $profit;
			$row["ReturnProfit"] += $returnProfit;

			$arr = &$TraceArr[ $tafsili ];
			$arr[count($arr)-1]["days"] = $days;
			$arr[count($arr)-1]["profit"] = $profit;
			$arr[count($arr)-1]["ReturnProfit"] = $returnProfit;
		}
	}
	if($ReportMode)
		return $TraceArr;
		
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	//--------------- add doc header ------------------
	$obj = new ACC_docs();
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->BranchID = $_SESSION["accounting"]["BranchID"];
	$obj->DocType = DOCTYPE_DEPOSIT_PROFIT;
	$obj->description = "محاسبه سود سپرده تا تاریخ " . DateModules::miladi_to_shamsi($ToDate);
	
	if(!$obj->Add($pdo))
	{
		echo Response::createObjectiveResponse(false, "خطا در ایجاد سند");
		die();
	}
	
	//---------------- add DocItems -------------------

	foreach($DepositeAmount as $CostID => $DepRow)
	{
		$CostID = $CostID == COSTID_ShortDeposite ? 416 : $CostID;
		foreach($DepRow as $TafsiliID => $itemrow)
		{
			$amount = $itemrow["profit"]*1 - $itemrow["ReturnProfit"]*1;
			
			$itemObj = new ACC_DocItems();
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $CostID;
			$itemObj->CreditorAmount = $amount > 0 ? $amount : 0;
			$itemObj->DebtorAmount = $amount < 0 ? abs($amount) : 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $TafsiliID;
			$itemObj->locked = "YES";
			$itemObj->details = "سود سپرده تا تاریخ " . DateModules::miladi_to_shamsi($ToDate);
			$itemObj->SourceType = DOCTYPE_DEPOSIT_PROFIT;
			$itemObj->SourceID = $ToDate;
			if(!$itemObj->Add($pdo))
			{
				echo Response::createObjectiveResponse(false, "خطا در ایجاد ردیف اول سند");
				die();
			}
			
			$itemObj = new ACC_DocItems();
			$itemObj->DocID = $obj->DocID;
			$itemObj->CostID = $amount > 0 ? COSTID_Wage : COSTID_DepositeWage;
			$itemObj->DebtorAmount= $amount > 0 ? $amount : 0;
			$itemObj->CreditorAmount = $amount < 0 ? abs($amount) : 0;
			$itemObj->locked = "YES";
			$itemObj->SourceType = DOCTYPE_DEPOSIT_PROFIT;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $TafsiliID;
			if(!$itemObj->Add($pdo))
			{
				echo Response::createObjectiveResponse(false, "خطا در ایجاد ردیف دوم سند");
				die();
			}	
		}
	}

	$pdo->commit();
	echo Response::createObjectiveResponse(true, $obj->LocalNo);
	die();	
}

//---------------------------------------------------------------

function ComputeShareProfit(){
	
	CheckCloseCycle();
	
	$BranchID = $_POST["BranchID"];
	//----------- check for all docs confirm --------------
	$dt = PdoDataAccess::runquery("select group_concat(distinct LocalNo) from ACC_docs 
		join ACC_DocItems using(DocID)
		where CostID =" . COSTID_share . "
		AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
		AND BranchID=?
		AND StatusID <> " . ACC_STEPID_CONFIRM, array($BranchID));
	if(count($dt) > 0 && $dt[0][0] != "")
	{
		echo Response::createObjectiveResponse(false, "اسناد با شماره های [" . $dt[0][0] . "] تایید نشده اند و قادر به صدور سند سود سهام نمی باشید.");
		die();
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();

	//--------------- add doc header ------------------
	$obj = new ACC_docs();
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->BranchID = $BranchID;
	$obj->DocType = DOCTYPE_SHARE_PROFIT;
	$obj->description = "محاسبه سود سهام سهامداران";

	if(!$obj->Add($pdo))
	{
		echo Response::createObjectiveResponse(false, "خطا در ایجاد سند");
		die();
	}
	//------------ compute profits ----------------
	$TotalProfit = $_POST["TotalProfit"];
	
	$dt = PdoDataAccess::runquery("select TafsiliID,sum(CreditorAmount-DebtorAmount) amount
		from ACC_DocItems join ACC_docs using(DocID)
		where CostID=" . COSTID_share . "
			AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "	
			AND BranchID=?
		group by TafsiliID
		order by amount", array($BranchID));
	
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(false, "هیچ حساب سهامی در این شعبه یافت نشد");
		die();
	}
	
	$TotalShares = 0;
	foreach($dt as $row)
		$TotalShares += $row["amount"];
	
	$sumProfits = 0;
	for($i=0; $i<count($dt);$i++)
	{
		$row = $dt[$i];
		$profit = ceil(($row["amount"]*1/$TotalShares)*$TotalProfit);
		$sumProfits += $profit;
		
		if($i == count($dt)-1)
			$profit += $TotalProfit - $sumProfits;
			
		$itemObj = new ACC_DocItems();
		$itemObj->DocID = $obj->DocID;
		$itemObj->CostID = COSTID_ShareProfit;
		$itemObj->CreditorAmount = $profit;
		$itemObj->DebtorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $row["TafsiliID"];
		$itemObj->locked = "YES";
		$itemObj->SourceType = DOCTYPE_SHARE_PROFIT;
		if(!$itemObj->Add($pdo))
		{
			echo Response::createObjectiveResponse(false, "خطا در ایجاد ردیف سند");
			die();
		}
	}
	//---------------------- add fund row ----------------
	/*
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = COSTID_Fund;
	$itemObj->DebtorAmount= $sumAmount;
	$itemObj->CreditorAmount = 0;
	$itemObj->locked = "YES";
	$itemObj->SourceType = DOCTYPE_DEPOSIT_PROFIT;
	if(!$itemObj->Add($pdo))
	{
		return false;
	}*/
		
	$pdo->commit();
	echo Response::createObjectiveResponse(true, $obj->DocID);
	die();	
}

//---------------------------------------------------------------

function RegisterWarrantyDoc($ReqObj, $WageCost, $TafsiliID, $TafsiliID2,$Block_CostID, $DocID, $pdo){
	
	CheckCloseCycle();
	
	/*@var $ReqObj WAR_requests */
	$IsExtend = $ReqObj->RefRequestID != $ReqObj->RequestID ? true : false;
	$refObj = new WAR_requests($ReqObj->RefRequestID);
	//-------------- get last record -----------------
	$dt = PdoDataAccess::runquery("select max(RequestID) from WAR_requests where RequestID<? AND RefRequestID=?", 
			array($ReqObj->RequestID, $ReqObj->RefRequestID));
	$preObj = new WAR_requests($dt[0][0]);
	//------------- get CostCodes --------------------
	$CostCode_warrenty = FindCostID("300");
	$CostCode_warrenty_commitment = FindCostID("700");
	$CostCode_wage = FindCostID("750-07");
	$CostCode_FutureWage = FindCostID("760-07");
	$CostCode_seporde = FindCostID("690");
	$CostCode_pasandaz = FindCostID("209-10");
	
	$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
	$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//------------------ find tafsilis ---------------
	$PersonTafsili = FindTafsiliID($ReqObj->PersonID, TAFTYPE_PERSONS);
	if(!$PersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->PersonID . "]");
		return false;
	}
	
	//------------------- compute wage ------------------
	$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$ReqObj->StartDate);
	//if(DateModules::YearIsLeap($CycleID));
		$days -= 1;
	$TotalWage = round($days*$ReqObj->amount*(1-$ReqObj->SavePercent/100)*$ReqObj->wage/36500);	
	
	$years = SplitYears(DateModules::miladi_to_shamsi($ReqObj->StartDate), 
		DateModules::miladi_to_shamsi($ReqObj->EndDate), $TotalWage);
	
	$TotalWage += $ReqObj->RegisterAmount*1;
	//--------------- check pasandaz remaindar -----------------
	$dt = PdoDataAccess::runquery("select sum(CreditorAmount-DebtorAmount) remain
		from ACC_DocItems join ACC_docs using(DocID) where CycleID=? AND CostID=?
			AND TafsiliType=? AND TafsiliID=? AND BranchID=?", array(
				$CycleID,
				$CostCode_pasandaz,
				TAFTYPE_PERSONS,
				$PersonTafsili,
				$ReqObj->BranchID
			));
	if(!$IsExtend && $WageCost == $CostCode_pasandaz && $dt[0][0]*1 < $ReqObj->amount*$ReqObj->SavePercent/100)
	{
		$message = "مانده حساب پس انداز مشتری کمتر از ".$ReqObj->SavePercent."% مبلغ ضمانت نامه می باشد";
		$message .= "<br> مانده حساب پس انداز : " . number_format($dt[0][0]);
		$message .= "<br> ".$ReqObj->SavePercent."% مبلغ ضمانت نامه: " . number_format($ReqObj->amount*$ReqObj->SavePercent/100);
		$message .= "<br> مبلغ کارمزد: " . number_format($TotalWage);
		ExceptionHandler::PushException($message);
		ExceptionHandler::PushException();
		return false;
	}
	$totalAmount = $IsExtend ? $TotalWage : ($ReqObj->amount*$ReqObj->SavePercent/100 + $TotalWage);
	if($WageCost == $CostCode_pasandaz && $dt[0][0]*1 < $totalAmount)
	{
		$message = "مانده حساب پس انداز مشتری کمتر از مبلغ کارمزد و ".$ReqObj->SavePercent."% مبلغ ضمانت نامه می باشد";
		$message .= "<br> مانده حساب پس انداز : " . number_format($dt[0][0]);
		$message .= "<br> ".$ReqObj->SavePercent."% مبلغ ضمانت نامه: " . number_format($ReqObj->amount*$ReqObj->SavePercent/100);
		$message .= "<br> مبلغ کارمزد: " . number_format($TotalWage);
		ExceptionHandler::PushException($message);
		return false;
	}
	if(!$IsExtend && $ReqObj->IsBlock == "YES")
	{
		if($Block_CostID != "" && $Block_CostID != $CostCode_pasandaz)
		{
			$dt = PdoDataAccess::runquery("select sum(CreditorAmount-DebtorAmount) remain
			from ACC_DocItems join ACC_docs using(DocID) where CycleID=? AND CostID=?
				AND TafsiliType=? AND TafsiliID=? AND BranchID=?", array(
					$CycleID,
					$Block_CostID,
					TAFTYPE_PERSONS,
					$PersonTafsili,
					$ReqObj->BranchID
				));
		}
		$amount = $ReqObj->amount*1;
		if($WageCost == $CostCode_pasandaz)
			$amount += $ReqObj->amount*$ReqObj->SavePercent/100 + $TotalWage;
		
		if($dt[0][0]*1 < $amount)
		{
			$message = "مانده حساب انتخابی جهت بلوکه کمتر از مبلغ ضمانت نامه می باشد";
			$message .= "<br>مانده حساب  : " . number_format($dt[0][0]);
			$message .= "<br> ".$ReqObj->SavePercent."% مبلغ ضمانت نامه: " . number_format($ReqObj->amount*$ReqObj->SavePercent/100);
			$message .= "<br> مبلغ کارمزد: " . number_format($TotalWage);
			$message .= "<br> مبلغ بلوکه: " . number_format($ReqObj->amount*1);
			ExceptionHandler::PushException($message);
			return false;
		}
	}	
	//---------------- add doc header --------------------
	if($DocID == null)
	{
		$DocObj = new ACC_docs();
		$DocObj->RegDate = PDONOW;
		$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
		$DocObj->DocDate = PDONOW;
		$DocObj->CycleID = $CycleID;
		$DocObj->BranchID = $ReqObj->BranchID;
		$DocObj->DocType = $IsExtend ? DOCTYPE_WARRENTY_EXTEND : DOCTYPE_WARRENTY;
		$DocObj->description = ($IsExtend ? "تمدید " : "") . 
				"ضمانت نامه " . $ReqObj->_TypeDesc . " به شماره " . 
				$ReqObj->RefRequestID . " به نام " . $ReqObj->_fullname;

		if(!$DocObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
	}
	else
		$DocObj = new ACC_docs($DocID);
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $DocObj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $PersonTafsili;
	$itemObj->SourceType = DOCTYPE_WARRENTY;
	$itemObj->SourceID = $ReqObj->RefRequestID;
	$itemObj->SourceID2 = $ReqObj->RequestID;
	$itemObj->locked = "YES";
	
	if(!$IsExtend)
	{
		$itemObj->CostID = $CostCode_warrenty;
		$itemObj->DebtorAmount = $ReqObj->amount;
		$itemObj->CreditorAmount = 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
			return false;
		}

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_warrenty_commitment;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $ReqObj->amount;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
			return false;
		}
	}
	else if($preObj->amount*1 <> $ReqObj->amount*1)
	{
		$itemObj->CostID = $CostCode_warrenty;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $preObj->amount*1 - $ReqObj->amount*1;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
			return false;
		}

		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_warrenty_commitment;
		$itemObj->DebtorAmount = $preObj->amount*1 - $ReqObj->amount*1;
		$itemObj->CreditorAmount = 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
			return false;
		}
	}
	//------------------- compute wage -----------------------
	$curYear = substr(DateModules::miladi_to_shamsi($ReqObj->StartDate), 0, 4)*1;
	foreach($years as $Year => $amount)
	{	
		if($amount == 0)
			continue;
		$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
		if(!$YearTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $Year . "]");
			return false;
		}
		unset($itemObj->ItemID);
		$itemObj->details = "کارمزد ضمانت نامه شماره " . $ReqObj->RefRequestID;
		$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $amount;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = $YearTafsili;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف کارمزد ضمانت نامه");
			return false;
		}
	}
	if($ReqObj->RegisterAmount*1 > 0)
	{
		unset($itemObj->ItemID);
		$itemObj->details = "کارمزد صدور ضمانت نامه شماره " . $ReqObj->RefRequestID;
		$itemObj->CostID = $CostCode_wage;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $ReqObj->RegisterAmount*1 ;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = FindTafsiliID($curYear, TAFTYPE_YEARS);
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف کارمزد ضمانت نامه");
			return false;
		}
	}
	
	//---------------------------- block Cost ----------------------------
	if(!$IsExtend && $ReqObj->IsBlock == "YES")
	{
		$blockObj = new ACC_CostBlocks();
		$blockObj->RegDate = PDONOW;
		$blockObj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$blockObj->CostID = !empty($Block_CostID) ? $Block_CostID : $CostCode_pasandaz;
		$blockObj->TafsiliType = TAFTYPE_PERSONS;
		$blockObj->TafsiliID = $PersonTafsili;
		$blockObj->BlockAmount = $ReqObj->amount;
		$blockObj->IsLock = "YES";
		$blockObj->EndDate = $ReqObj->EndDate;
		$blockObj->SourceType = DOCTYPE_WARRENTY;
		$blockObj->SourceID = $ReqObj->RequestID;
		$blockObj->details = "بابت ضمانت نامه شماره " . $ReqObj->RefRequestID;
		if(!$blockObj->Add())
		{
			print_r(ExceptionHandler::PopAllExceptions());
			ExceptionHandler::PushException("خطا در بلوکه کردن حساب پس انداز");
			return false;
		}
	}
	if($IsExtend && $preObj->amount*1 <> $ReqObj->amount*1 && $ReqObj->IsBlock == "YES")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_blocks where SourceType=? AND SourceID=?",
				array(DOCTYPE_WARRENTY, $ReqObj->RequestID));
		if(count($dt) > 0)
		{
			$blockObj = new ACC_CostBlocks($dt[0]["BlockID"]);
			$blockObj->IsActive = "NO";
			$blockObj->Edit($pdo);
		}
		$blockObj = new ACC_CostBlocks();
		$blockObj->RegDate = PDONOW;
		$blockObj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$blockObj->CostID = !empty($Block_CostID) ? $Block_CostID : $CostCode_pasandaz;
		$blockObj->TafsiliType = TAFTYPE_PERSONS;
		$blockObj->TafsiliID = $PersonTafsili;
		$blockObj->BlockAmount = $preObj->amount*1 - $ReqObj->amount*1;
		$blockObj->IsLock = "YES";
		$blockObj->EndDate = $ReqObj->EndDate;
		$blockObj->SourceType = DOCTYPE_WARRENTY;
		$blockObj->SourceID = $ReqObj->RequestID;
		$blockObj->details = "بابت ضمانت نامه شماره " . $ReqObj->RefRequestID;
		if(!$blockObj->Add())
		{
			print_r(ExceptionHandler::PopAllExceptions());
			ExceptionHandler::PushException("خطا در بلوکه کردن حساب پس انداز");
			return false;
		}
	}
	// ---------------------- Warrenty costs -----------------------------
	$totalCostAmount = 0;
	$dt = PdoDataAccess::runquery("select * from WAR_costs where RequestID=?", array($ReqObj->RequestID));
	
	foreach($dt as $row)
	{
		$totalCostAmount += ($row["CostType"] == "DEBTOR"? 1 : -1)*$row["CostAmount"]*1;
		if($row["CostCodeID"] == "0")
		{
			ExceptionHandler::PushException("کد حساب هزینه " . $row["CostDesc"] . " تعیین نشده است");
			return false;
		}
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		$itemObj->SourceID = $IsExtend ? $ReqObj->RefRequestID : $ReqObj->RequestID;
		$itemObj->SourceID2 = $ReqObj->RequestID;
		$itemObj->SourceID3 = $row["CostID"];
		$itemObj->details = $row["CostDesc"];
		$itemObj->CostID = $row["CostCodeID"];
		$itemObj->DebtorAmount = $row["CostType"] == "DEBTOR" ? $row["CostAmount"] : 0;
		$itemObj->CreditorAmount = $row["CostType"] == "CREDITOR" ? $row["CostAmount"] : 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت هزینه ضمانت نامه");
			return false;
		}
	}
	// ----------------------------- bank --------------------------------
	if(!$IsExtend)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		$itemObj->details = "بابت ".$ReqObj->SavePercent."% سپرده ضمانت نامه شماره " . $ReqObj->RefRequestID;
		$itemObj->CostID = $CostCode_seporde;
		$itemObj->DebtorAmount = 0;
		$itemObj->CreditorAmount = $ReqObj->amount*$ReqObj->SavePercent/100;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف سپرده");
			return false;
		}
	}
	else if($preObj->amount*1 <> $ReqObj->amount*1)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		$itemObj->details = "بابت ".$ReqObj->SavePercent."% سپرده ضمانت نامه شماره " . $ReqObj->RefRequestID;
		$itemObj->CostID = $CostCode_seporde;
		$itemObj->DebtorAmount = ($preObj->amount*1 - $ReqObj->amount*1)*$refObj->SavePercent/100;
		$itemObj->CreditorAmount = 0;
		if(!$itemObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ثبت ردیف سپرده");
			return false;
		}
	}
	//--------------------------------------------------------------------------
	
	$sepordehAmount = 0;
	if(!$IsExtend)
		$sepordehAmount = $ReqObj->amount*$ReqObj->SavePercent/100;
	else if($preObj->amount*1 <> $ReqObj->amount*1)
		$sepordehAmount -= ($preObj->amount*1 - $ReqObj->amount*1)*$refObj->SavePercent/100;
		
	$TAMOUNT = $TotalWage + $sepordehAmount - $totalCostAmount;
	
	unset($itemObj->ItemID);
	$CostObj = new ACC_CostCodes($WageCost);
	$itemObj->details = "بابت سپرده و کارمزد ضمانت نامه شماره " . $ReqObj->RefRequestID;
	$itemObj->CostID = $WageCost;
	$itemObj->DebtorAmount = $TAMOUNT < 0 ? 0 : $TAMOUNT;
	$itemObj->CreditorAmount = $TAMOUNT < 0 ? abs($TAMOUNT) : 0;
	$itemObj->TafsiliType = $CostObj->TafsiliType;
	if($TafsiliID != "")
		$itemObj->TafsiliID = $TafsiliID;
	$itemObj->TafsiliType2 = $CostObj->TafsiliType2;
	if($TafsiliID2 != "")
		$itemObj->TafsiliID2 = $TafsiliID2;
	$itemObj->SourceID = $IsExtend ? $ReqObj->RefRequestID : $ReqObj->RequestID;
	$itemObj->SourceID2 = $ReqObj->RequestID;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف کارمزد");
		return false;
	}
	
	//---------- ردیف های تضمین  ----------
	if(!$IsExtend)
	{
		$SumAmount = 0;
		$countAmount = 0;	
		$dt = PdoDataAccess::runquery("
			SELECT DocumentID, ParamValue, InfoDesc as DocTypeDesc
				FROM DMS_DocParamValues
				join DMS_DocParams using(ParamID)
				join DMS_documents d using(DocumentID)
				join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
				left join ACC_DocItems on(SourceType=" . DOCTYPE_DOCUMENT . " AND SourceID=DocumentID)
			where ItemID is null AND b.param1=1 AND 
				paramType='currencyfield' AND ObjectType='warrenty' AND ObjectID=?",array($ReqObj->RequestID), $pdo);

		foreach($dt as $row)
		{
			unset($itemObj->ItemID);
			$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
			$itemObj->DebtorAmount = $row["ParamValue"];
			$itemObj->CreditorAmount = 0;
			$itemObj->TafsiliType = TAFTYPE_PERSONS;
			$itemObj->TafsiliID = $PersonTafsili;
			$itemObj->SourceType = DOCTYPE_DOCUMENT;
			$itemObj->SourceID = $row["DocumentID"];
			$itemObj->details = $row["DocTypeDesc"];
			$itemObj->Add($pdo);

			$SumAmount += $row["ParamValue"]*1;
			$countAmount++;
		}
		if($SumAmount > 0)
		{
			unset($itemObj->ItemID);
			unset($itemObj->TafsiliType);
			unset($itemObj->TafsiliID);
			unset($itemObj->details);
			$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
			$itemObj->DebtorAmount = 0;
			$itemObj->CreditorAmount = $SumAmount;	
			$itemObj->Add($pdo);
		}
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $DocObj->DocID;
}

function ReturnWarrantyDoc($ReqObj, $pdo, $EditMode = false){
	
	CheckCloseCycle();
	
	/*@var $PayObj WAR_requests */
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc from ACC_docs join ACC_cycles using(CycleID)
			join ACC_DocItems using(DocID)
			where StatusID <> ".ACC_STEPID_RAW." AND SourceType=" . DOCTYPE_WARRENTY . " AND SourceID2=?",
			array($ReqObj->RequestID), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems 
		where SourceType=" . DOCTYPE_WARRENTY . " AND SourceID2=?",
		array($ReqObj->RequestID), $pdo);
	if(count($dt) == 0)
		return true;
	
	PdoDataAccess::runquery("delete from ACC_CostBlocks 
			where SourceType=" . DOCTYPE_WARRENTY . " AND SourceID=?",
			array($ReqObj->RequestID), $pdo);
	
	if($EditMode)
	{
		PdoDataAccess::runquery("delete from ACC_DocItems 
			where SourceType=" . DOCTYPE_WARRENTY . " AND SourceID2=?",
			array($ReqObj->RequestID), $pdo);
		
		return true;
	}
	
	return ACC_docs::Remove($dt[0][0], $pdo);
}

function EndWarrantyDoc($ReqObj, $pdo){
	
	CheckCloseCycle();
	
	/*@var $ReqObj WAR_requests */
	
	//------------- get CostCodes --------------------
	$CostCode_warrenty = FindCostID("300");
	$CostCode_warrenty_commitment = FindCostID("700");
	
	$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
	$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
	
	$CostCode_pasandaz = FindCostID("209-10");
	$CostCode_seporde = FindCostID("210-03");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//------------------ find tafsilis ---------------
	$PersonTafsili = FindTafsiliID($ReqObj->PersonID, TAFTYPE_PERSONS);
	if(!$PersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->PersonID . "]");
		return false;
	}
	//---------------- add doc header --------------------
	$DocObj = new ACC_docs();
	$DocObj->RegDate = PDONOW;
	$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
	$DocObj->DocDate = PDONOW;
	$DocObj->CycleID = $CycleID;
	$DocObj->BranchID = $ReqObj->BranchID;
	$DocObj->DocType = DOCTYPE_WARRENTY_END;
	$DocObj->description = "خاتمه ضمانت نامه " . $ReqObj->_TypeDesc . " به شماره " . 
			$ReqObj->RequestID . " به نام " . $ReqObj->_fullname;

	if(!$DocObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $DocObj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $PersonTafsili;
	$itemObj->SourceType = DOCTYPE_WARRENTY_END;
	$itemObj->SourceID = $ReqObj->RefRequestID;
	$itemObj->SourceID2 = $ReqObj->RequestID;
	$itemObj->locked = "YES";
	
	$itemObj->CostID = $CostCode_warrenty;
	$itemObj->CreditorAmount = $ReqObj->amount;
	$itemObj->DebtorAmount = 0;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
		return false;
	}
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_warrenty_commitment;
	$itemObj->CreditorAmount = 0;
	$itemObj->DebtorAmount = $ReqObj->amount;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
		return false;
	}
	//...............................................
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_seporde;
	$itemObj->DebtorAmount = round($ReqObj->amount*$ReqObj->SavePercent/100);
	$itemObj->CreditorAmount = 0;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
		return false;
	}
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_pasandaz;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = round($ReqObj->amount*$ReqObj->SavePercent/100);
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
		return false;
	}
	//---------------------------- block Cost ----------------------------
	if($ReqObj->IsBlock == "YES")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_blocks where SourceType=? AND SourceID=?",
				array(DOCTYPE_WARRENTY, $ReqObj->RequestID));
		if(count($dt) > 0)
		{
			$blockObj = new ACC_CostBlocks($dt[0]["BlockID"]);
			$blockObj->IsActive = "NO";
			$blockObj->Edit($pdo);
		}
	}
	//---------- ردیف های تضمین  ----------
	$SumAmount = 0;
	$dt = PdoDataAccess::runquery("
		SELECT DocumentID, ParamValue, InfoDesc as DocTypeDesc
			FROM DMS_DocParamValues
			join DMS_DocParams using(ParamID)
			join DMS_documents d using(DocumentID)
			join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
			left join ACC_DocItems on(SourceType=" . DOCTYPE_DOCUMENT . " AND SourceID=DocumentID)
		where ItemID is null AND b.param1=1 AND 
			paramType='currencyfield' AND ObjectType='warrenty' AND ObjectID=?",array($ReqObj->RefRequestID), $pdo);

	foreach($dt as $row)
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
		$itemObj->CreditorAmount = $row["ParamValue"];
		$itemObj->DebtorAmount  = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $PersonTafsili;
		$itemObj->SourceType = DOCTYPE_DOCUMENT;
		$itemObj->SourceID = $row["DocumentID"];
		$itemObj->details = $row["DocTypeDesc"];
		$itemObj->Add($pdo);

		$SumAmount += $row["ParamValue"]*1;
	}
	if($SumAmount > 0)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		unset($itemObj->details);
		$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
		$itemObj->CreditorAmount = 0;
		$itemObj->DebtorAmount = $SumAmount;	
		$itemObj->Add($pdo);
	}

	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $DocObj->DocID;
}

function CancelWarrantyDoc($ReqObj, $extradays, $pdo){
	
	CheckCloseCycle();
	
	/*@var $ReqObj WAR_requests */
	
	//------------- get CostCodes --------------------
	$CostCode_warrenty = FindCostID("300");
	$CostCode_warrenty_commitment = FindCostID("700");
	$CostCode_wage = FindCostID("750-07");
	$CostCode_FutureWage = FindCostID("760-07");
	
	$CostCode_pasandaz = FindCostID("209-10");
	$CostCode_seporde = FindCostID("210-03");
	
	$CostCode_guaranteeAmount_zemanati = FindCostID("904-02");
	$CostCode_guaranteeAmount2_zemanati = FindCostID("905-02");
	//------------------------------------------------
	$CycleID = $_SESSION["accounting"]["CycleID"];
	//------------------ find tafsilis ---------------
	$PersonTafsili = FindTafsiliID($ReqObj->PersonID, TAFTYPE_PERSONS);
	if(!$PersonTafsili)
	{
		ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $ReqObj->PersonID . "]");
		return false;
	}
	//---------------- add doc header --------------------
	$DocObj = new ACC_docs();
	$DocObj->RegDate = PDONOW;
	$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
	$DocObj->DocDate = PDONOW;
	$DocObj->CycleID = $CycleID;
	$DocObj->BranchID = $ReqObj->BranchID;
	$DocObj->DocType = DOCTYPE_WARRENTY_CANCEL;
	$DocObj->description = "ابطال ضمانت نامه " . $ReqObj->_TypeDesc . " به شماره " . 
			$ReqObj->RequestID . " به نام " . $ReqObj->_fullname;

	if(!$DocObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $DocObj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $PersonTafsili;
	$itemObj->SourceType = DOCTYPE_WARRENTY_CANCEL;
	$itemObj->SourceID = $ReqObj->RefRequestID;
	$itemObj->SourceID2 = $ReqObj->RequestID;
	$itemObj->locked = "YES";
	//------------------- compute wage ------------------
	$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$ReqObj->StartDate);
	$days -= 1;
	$TotalWage = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	
	
	$days = DateModules::GDateMinusGDate($ReqObj->CancelDate,$ReqObj->StartDate);
	$days += $extradays*1;
	$FundWage = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	
	
	$RemainWage = $TotalWage-$FundWage;
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_pasandaz;
	$itemObj->CreditorAmount = $RemainWage;
	if(!$itemObj->Add($pdo))
	{
		print_r(ExceptionHandler::PopAllExceptions());
		ExceptionHandler::PushException("خطا در بلوکه کردن حساب پس انداز");
		return false;
	}
	
	$years = SplitYears(DateModules::miladi_to_shamsi($ReqObj->StartDate), 
		DateModules::miladi_to_shamsi($ReqObj->EndDate), $TotalWage);
	
	$years = array_reverse($years, true);
	
	$curYear = substr(DateModules::miladi_to_shamsi($ReqObj->StartDate), 0, 4)*1;
	foreach($years as $Year => $amount)
	{	
		if($amount == 0)
			continue;
		$YearTafsili = FindTafsiliID($Year, TAFTYPE_YEARS);
		if(!$YearTafsili)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $Year . "]");
			return false;
		}
		unset($itemObj->ItemID);
		$itemObj->details = "برگشت کارمزد ضمانت نامه شماره " . $ReqObj->RequestID;
		$itemObj->CostID = $Year == $curYear ? $CostCode_wage : $CostCode_FutureWage;
		$itemObj->DebtorAmount = min($amount, $RemainWage);
		$itemObj->CreditorAmount = 0;
		$itemObj->TafsiliType = TAFTYPE_YEARS;
		$itemObj->TafsiliID = $YearTafsili;
		$itemObj->Add($pdo);
		
		$RemainWage -= min($RemainWage, $amount);
		if($RemainWage == 0)
			break;
	}
	//----------------- add Doc items ------------------------
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $DocObj->DocID;
	$itemObj->TafsiliType = TAFTYPE_PERSONS;
	$itemObj->TafsiliID = $PersonTafsili;
	$itemObj->SourceType = DOCTYPE_WARRENTY_END;
	$itemObj->SourceID = $ReqObj->RefRequestID;
	$itemObj->SourceID2 = $ReqObj->RequestID;
	$itemObj->locked = "YES";
	
	$itemObj->CostID = $CostCode_warrenty;
	$itemObj->CreditorAmount = $ReqObj->amount;
	$itemObj->DebtorAmount = 0;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
		return false;
	}
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_warrenty_commitment;
	$itemObj->CreditorAmount = 0;
	$itemObj->DebtorAmount = $ReqObj->amount;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
		return false;
	}
	//...............................................
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_seporde;
	$itemObj->DebtorAmount = round($ReqObj->amount*$ReqObj->SavePercent/100);
	$itemObj->CreditorAmount = 0;
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف ضمانت نامه");
		return false;
	}
	
	unset($itemObj->ItemID);
	$itemObj->CostID = $CostCode_pasandaz;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = round($ReqObj->amount*$ReqObj->SavePercent/100);
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ثبت ردیف تعهد ضمانت نامه");
		return false;
	}
	//---------------------------- block Cost ----------------------------
	if($ReqObj->IsBlock == "YES")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_blocks where SourceType=? AND SourceID=?",
				array(DOCTYPE_WARRENTY, $ReqObj->RequestID));
		if(count($dt) > 0)
		{
			$blockObj = new ACC_CostBlocks($dt[0]["BlockID"]);
			$blockObj->IsActive = "NO";
			$blockObj->Edit($pdo);
		}
	}
	//---------- ردیف های تضمین  ----------
	$SumAmount = 0;
	$dt = PdoDataAccess::runquery("
		SELECT DocumentID, ParamValue, InfoDesc as DocTypeDesc
			FROM DMS_DocParamValues
			join DMS_DocParams using(ParamID)
			join DMS_documents d using(DocumentID)
			join BaseInfo b on(InfoID=d.DocType AND TypeID=8)
			
		where b.param1=1 AND 
			paramType='currencyfield' AND ObjectType='warrenty' AND ObjectID=?",array($ReqObj->RefRequestID), $pdo);
	foreach($dt as $row)
	{
		unset($itemObj->ItemID);
		$itemObj->CostID = $CostCode_guaranteeAmount_zemanati;
		$itemObj->CreditorAmount = $row["ParamValue"];
		$itemObj->DebtorAmount  = 0;
		$itemObj->TafsiliType = TAFTYPE_PERSONS;
		$itemObj->TafsiliID = $PersonTafsili;
		$itemObj->SourceType = DOCTYPE_DOCUMENT;
		$itemObj->SourceID = $row["DocumentID"];
		$itemObj->details = $row["DocTypeDesc"];
		$itemObj->Add($pdo);

		$SumAmount += $row["ParamValue"]*1;
	}
	if($SumAmount > 0)
	{
		unset($itemObj->ItemID);
		unset($itemObj->TafsiliType);
		unset($itemObj->TafsiliID);
		unset($itemObj->details);
		$itemObj->CostID = $CostCode_guaranteeAmount2_zemanati;
		$itemObj->CreditorAmount = 0;
		$itemObj->DebtorAmount = $SumAmount;	
		$itemObj->Add($pdo);
	}

	if(ExceptionHandler::GetExceptionCount() > 0)
		return false;
	
	return $DocObj->DocID;
}

function ReturnCancelDoc($ReqObj, $pdo, $EditMode = false){
	
	CheckCloseCycle();
	
	/*@var $PayObj WAR_requests */
	
	$dt = PdoDataAccess::runquery("select DocID,StatusID,LocalNo,CycleDesc 
		from ACC_DocItems join ACC_docs using(DocID)  join ACC_cycles using(CycleID)
		where SourceType=" . DOCTYPE_WARRENTY_CANCEL . " AND SourceID2=?",
		array($ReqObj->RequestID), $pdo);
	if(count($dt) == 0)
		return true;
	
	if($dt[0]["StatusID"] != ACC_STEPID_RAW)
	{
		ExceptionHandler::PushException("سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		return false;
	}

	PdoDataAccess::runquery("delete from ACC_DocItems 
		where SourceType=" . DOCTYPE_WARRENTY_CANCEL . " AND SourceID2=?",
		array($ReqObj->RequestID), $pdo);

	return ACC_docs::Remove($dt[0][0], $pdo);
}

//---------------------------------------------------------------

function RegisterSalaryDoc($PObj, $pdo){
	
	$CycleID = $PObj->pay_year;
	CheckCloseCycle($CycleID);
	
	//-------------------------------------------------
	$query = "
		select salary_item_type_id,full_title
		FROM HRM_payments p
			JOIN HRM_payment_items pi ON(p.pay_year = pi.pay_year AND p.pay_month = pi.pay_month AND	
					p.staff_id = pi.staff_id AND p.payment_type = pi.payment_type)
			JOIN HRM_salary_item_types sit using(salary_item_type_id)
			left join ACC_CostCodes cc using(CostID)
			
		WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = ?
			and (sit.CostID is null or cc.CostID is null)
			and (pay_value > 0 or get_value>0)
		group by pi.salary_item_type_id
		order by pi.salary_item_type_id";

	$dt = PdoDataAccess::runquery_fetchMode($query, 
			array($PObj->pay_year, $PObj->pay_month,$PObj->payment_type), $pdo);
	if($dt->rowCount() > 0)
	{
		$str = "اقلام حقوقی زیر فاقد کد حساب می باشند و امکان صدور سند وجود ندارد<br>".
				"<table border=1 width=100%>";
		while ($row = $dt->fetch())
			$str .= "<tr><td style=padding:5px align=center>" . $row["salary_item_type_id"] . 
				"</td><td style=padding:5px>" . $row["full_title"] . "</td></tr>";

		$str .= "</table>";
		ExceptionHandler::PushException($str);
		return false;
	}

	//................. check register before .................

	$query = "select * from ACC_DocItems di join ACC_docs using(DocID)
		where SourceType=".DOCTYPE_SALARY." AND SourceID=? AND SourceID2=? AND CycleID=" . $CycleID;
	$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));
	
	if(count($dt) > 0)
	{
		ExceptionHandler::PushException("سند این ماه قبلا صادر شده است");
		return false;
	};

	//---------------- check for non-confirm salary payments ---------------

	$query = "	
		select p.pay_year
		FROM HRM_payment_items pi join HRM_payments p 
			ON(p.pay_year = pi.pay_year AND p.pay_month = pi.pay_month AND
				p.staff_id = pi.staff_id AND p.payment_type = pi.payment_type)
		WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = ? 
			and p.state=1 ";
	$dt = PdoDataAccess::runquery_fetchMode($query, 
			array($PObj->pay_year, $PObj->pay_month,$PObj->payment_type), $pdo);
	if($dt->rowCount() > 0)
	{
		ExceptionHandler::PushException("در این ماه محاسبات حقوق غیر قطعی وجود دارد و قادر به صدور سند نمی باشید.");
		return false;
	}
	//------------------ get CostCodes -------------------
	$dt = PdoDataAccess::runquery("select * from HRM_CostCodes");
	$CostCodes = array();
	foreach($dt as $row)
		$CostCodes[ $row["RowType"] ] = $row["CostID"];
	//---------------- add doc header --------------------
	$DocObj = new ACC_docs();
	$DocObj->RegDate = PDONOW;
	$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
	$DocObj->DocDate = PDONOW;
	$DocObj->CycleID = $CycleID;
	$DocObj->BranchID = Default_BranchID;
	$DocObj->DocType = DOCTYPE_SALARY;
	$DocObj->description = " حقوق " . DateModules::GetMonthName($PObj->pay_month) . " ماه";
	if(!$DocObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	//----------------- add Doc items ------------------------
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
			details,locked,SourceType,SourceID,SourceID2)

		select $DocObj->DocID,
			CostID,
			if(TafsiliID<>0 , 1 , null ) TafsiliType,
			if(TafsiliID=0, null, TafsiliID) TafsiliID,
			
			case when effect_type=1 then 
				if(amount>0,amount,0) 
			else
				if(amount<0,-1*amount,0)
			end,
			case when effect_type=1 then 
				if(amount<0,-1*amount,0)
			else
				if(amount>0,amount,0) 
			end,
			'".$DocObj->description ."',
			'YES',
			".DOCTYPE_SALARY.",
			".$PObj->payment_type.",
			".$PObj->pay_month."

		from (
			select 
				sit.CostID,sit.effect_type,
				if(sit.effect_type=2 AND sit.TafsiliID=0,t.TafsiliID, sit.TafsiliID) TafsiliID,
				round(sum(
					case when sit.effect_type = 1 then
						pit.pay_value + pit.diff_value_coef * pit.diff_pay_value 
					else
						pit.get_value + pit.diff_value_coef * pit.diff_get_value 
					end
					+ if(sit.Param1CostID>0, param1 + diff_param1_coef * diff_param1 , 0)
					+ if(sit.Param2CostID>0, param2 + diff_param2_coef * diff_param2 , 0)
					+ if(sit.Param3CostID>0, param3 + diff_param3_coef * diff_param3 , 0)
					+ if(sit.Param4CostID>0, param4 + diff_param4_coef * diff_param4 , 0)
				)) as amount

			FROM HRM_payments p
				JOIN HRM_payment_items pit
					ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
					p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
				JOIN HRM_salary_item_types sit using(salary_item_type_id)				
				JOIN HRM_staff s ON s.staff_id = p.staff_id
				join HRM_persons p1 on(p1.PersonID=s.PersonID)
				join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
				JOIN ACC_tafsilis t on(t.TafsiliType=".TAFTYPE_PERSONS." AND ObjectID=p2.PersonID)
			WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
			
			group by CostID, if(sit.effect_type=2 AND sit.TafsiliID=0,t.TafsiliID, sit.TafsiliID)
		)t 
		where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
			":pt" => $PObj->payment_type), $pdo);

	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		ExceptionHandler::PushException("خطا در بازیابی و اضافه رکورد ها از سیستم حقوق");
		return false;
	}
	//------------------- pure pay for each person -----------------------
	if(empty($CostCodes["PurePay"]))
	{
		$pdo->rollBack();
		ExceptionHandler::PushException("کد حساب خالص پرداختی تعریف نشده است");
		return false;
	}
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,details,
			TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
			locked,SourceType,SourceID,SourceID2)

		select $DocObj->DocID,
			".$CostCodes["PurePay"].",
			'".$DocObj->description ."',
			".TAFTYPE_PERSONS.",
			TafsiliID,
			if(amount<0,-1*amount,0),
			if(amount>0,amount,0), 
			'YES',
			".DOCTYPE_SALARY.",
			".$PObj->payment_type.",
			".$PObj->pay_month."

		from (
			select 
				t.TafsiliID,
				sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
					- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
				) as amount

			FROM HRM_payments p
				JOIN HRM_payment_items pit
					ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
					p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
				JOIN HRM_salary_item_types sit using(salary_item_type_id)				
				JOIN HRM_staff s ON s.staff_id = p.staff_id
				join HRM_persons p1 on(p1.PersonID=s.PersonID)
				join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
				JOIN ACC_tafsilis t on(t.TafsiliType=".TAFTYPE_PERSONS." AND ObjectID=p2.PersonID)
					
			WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
			group by TafsiliID
		)t 
		where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
			":pt" => $PObj->payment_type), $pdo);

	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		ExceptionHandler::PushException("خطا در ثبت خالص حقوق");
		return false;
	}
	//---------------------  param1 and param2 --------------------------
	for($i=1; $i<=4; $i++)
	{
		PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,details,
				DebtorAmount,CreditorAmount,
				locked,SourceType,SourceID,SourceID2)

			select $DocObj->DocID,
				Param".$i."CostID,
				'".$DocObj->description ."',
				if(effect_type=2,amount,0), 
				if(effect_type=1,amount,0),
				'YES',
				".DOCTYPE_SALARY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select sit.Param".$i."CostID,effect_type,
					sum(param".$i." + diff_param".$i."_coef * diff_param".$i.") as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
					AND sit.Param".$i."CostID>0
				group by sit.Param".$i."CostID
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type), $pdo);
	}	
	 
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		ExceptionHandler::PushException("خطا در ثبت پارامترهای اقلام");
		return false;
	}
	return true;
} 

function ReturnSalaryDoc($PObj, $pdo){
	
	$CycleID = $PObj->pay_year;
	CheckCloseCycle($CycleID);
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc 
			from ACC_docs join ACC_DocItems using(DocID) join ACC_cycles using(CycleID)
			where StatusID <> ".ACC_STEPID_RAW." AND SourceType=" . DOCTYPE_SALARY . " AND SourceID=? AND SourceID2=?",
	array($PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc 
			from ACC_docs join ACC_DocItems using(DocID) join ACC_cycles using(CycleID)
			where SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID=? AND SourceID2=?",
	array($PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند پرداخت با شماره " . 
				$dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] . " صادر شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems 
		where SourceType=" . DOCTYPE_SALARY . " AND SourceID=? AND SourceID2=?",
		array($PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) == 0)
		return true;
	
	PdoDataAccess::runquery("delete from ACC_DocItems 
		where SourceType=" . DOCTYPE_SALARY . " AND SourceID=? AND SourceID2=?",
		array($PObj->payment_type, $PObj->pay_month), $pdo);

	return ACC_docs::Remove($dt[0][0], $pdo);
}

function RegisterPaySalaryDoc($PObj, $pdo){
	
	$CycleID = $PObj->pay_year;
	CheckCloseCycle($CycleID);
	
	$query = "select LocalNo from ACC_DocItems di join ACC_docs using(DocID)
		where SourceType=".DOCTYPE_SALARY_PAY." AND SourceID=? AND SourceID2=? AND CycleID=" . $CycleID;
	$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));
	if(count($dt) > 0)
	{
		ExceptionHandler::PushException("سند این ماه قبلا در سند شماره"
			.$dt[0]["LocalNo"]. "صادر شده است");
		return false;
	};
	
	$query = "select sum(CreditorAmount) amount from ACC_DocItems di join ACC_docs using(DocID)
		where SourceType=".DOCTYPE_SALARY." AND SourceID=? AND SourceID2=? AND CycleID=" . $CycleID;
	$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));
	
	if($dt[0]["amount"] == 0)
	{
		ExceptionHandler::PushException("سند حقوق این ماه هنوز صادر نشده است");
		return false;
	};
	$TotalAmount = $dt[0]["amount"] ;
	//---------------------------------------------
	
	$DocObj = new ACC_docs();
	$DocObj->RegDate = PDONOW;
	$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
	$DocObj->DocDate = PDONOW;
	$DocObj->CycleID = $CycleID;
	$DocObj->BranchID = Default_BranchID;
	$DocObj->DocType = DOCTYPE_SALARY_PAY;
	$DocObj->description = "پرداخت حقوق " . DateModules::GetMonthName($PObj->pay_month) . " ماه";
	if(!$DocObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	
	$query = "
		insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
			details,locked,SourceType,SourceID,SourceID2)
		
		select ".$DocObj->DocID.",
			di.CostID,
			di.TafsiliType,
			di.TafsiliID,
			di.CreditorAmount,
			0,
			di.details,
			'YES',
			".DOCTYPE_SALARY_PAY.",
			SourceID,
			SourceID2
		from ACC_DocItems di join ACC_docs using(DocID)
		where SourceType=".DOCTYPE_SALARY." AND SourceID=? AND SourceID2=? 
			AND CreditorAmount>0 AND CycleID=" . $CycleID;
	PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month),$pdo);
	
	//----------------------- add bank row -----------------------
	$CostCode_bank = FindCostID("101");
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $DocObj->DocID;
	$itemObj->CostID = $CostCode_bank;
	$itemObj->DebtorAmount = 0;
	$itemObj->CreditorAmount = $TotalAmount;
	$itemObj->TafsiliType = TAFTYPE_BANKS;
	$itemObj->TafsiliType2 = TAFTYPE_ACCOUNTS;
	$itemObj->locked = "NO";
	$itemObj->SourceType = DOCTYPE_SALARY_PAY;
	$itemObj->SourceID = $PObj->payment_type;
	$itemObj->SourceID2 = $PObj->pay_month;
	$itemObj->Add($pdo);
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		ExceptionHandler::PushException("خطا در صدور سند");
		return false;
	}
	return true;
}

function ReturnPaySalaryDoc($PObj, $pdo){
	
	$CycleID = $PObj->pay_year;
	CheckCloseCycle($CycleID);
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc from ACC_docs 
			join ACC_DocItems using(DocID) join ACC_cycles using(CycleID)
			where StatusID <> ".ACC_STEPID_RAW." AND SourceType=" . DOCTYPE_SALARY_PAY . 
			" AND SourceID=? AND SourceID2=?",
	array($PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems 
		where SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID=? AND SourceID2=?",
		array($PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) == 0)
		return true;
	
	PdoDataAccess::runquery("delete from ACC_DocItems 
		where SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID=? AND SourceID2=?",
		array($PObj->payment_type, $PObj->pay_month), $pdo);

	return ACC_docs::Remove($dt[0][0], $pdo);
}

//---------------------------------------------------------------

function RegisterInOutAccountDoc($BranchID, $amount, $mode, $description,
		$BaseCostID,$BaseTafsiliType,$BaseTafsiliID,$BaseTafsiliType2,$BaseTafsiliID2,
		$CostID,$TafsiliType, $TafsiliID,$TafsiliType2, $TafsiliID2, $IsLock = false) {
	
	if(isset($_SESSION["accounting"]) )
		CheckCloseCycle();
	
	$CycleID = isset($_SESSION["accounting"]) ? $_SESSION["accounting"]["CycleID"] : 
		substr(DateModules::shNow(), 0 , 4);
	
	if($mode < 0)
	{
		$query = "select ifnull(sum(CreditorAmount-DebtorAmount),0) remaindar
		from ACC_DocItems di
			join ACC_docs d using(DocID)
		where d.CycleID=:c AND d.BranchID=:b AND 
			di.CostID=:cost AND di.TafsiliType = :t AND di.TafsiliID=:tid";
		$param = array(
			":c" => $CycleID,
			":b" => $BranchID,
			":cost" => $BaseCostID,
			":t" => $BaseTafsiliType,
			":tid" => $BaseTafsiliID
		);
		
		$dt = PdoDataAccess::runquery($query, $param);
		//echo PdoDataAccess::GetLatestQueryString();
		if($_POST["amount"] > $dt[0][0]*1)
		{
			ExceptionHandler::PushException("مبلغ وارد شده بیشتر از مانده حساب می باشد");
			return false;
		}
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	//---------------- add doc header --------------------
	$obj = new ACC_docs();
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $CycleID;
	$obj->BranchID = $BranchID;
	$obj->DocType = $mode > 0 ? DOCTYPE_SAVING_IN : DOCTYPE_SAVING_OUT;
	$obj->description = $mode > 0 ? "واریز به حساب" : "برداشت از حساب";
	if(!$obj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد سند");
		return false;
	}
	
	//-------------------------------------------------
		
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $BaseCostID;
	$itemObj->DebtorAmount = $mode > 0 ? 0 : $amount;
	$itemObj->CreditorAmount = $mode > 0 ? $amount : 0;
	$itemObj->TafsiliType = $BaseTafsiliType;
	$itemObj->TafsiliID = $BaseTafsiliID;
	$itemObj->TafsiliType2 = $BaseTafsiliType2;
	$itemObj->TafsiliID2 = $BaseTafsiliID2;
	$itemObj->details = $description;
	$itemObj->locked = $IsLock ? "YES" : "NO";
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد ردیف سند");
		return false;
	}
	
	$itemObj = new ACC_DocItems();
	$itemObj->DocID = $obj->DocID;
	$itemObj->CostID = $CostID;
	$itemObj->DebtorAmount = $mode > 0 ? $amount : 0;
	$itemObj->CreditorAmount = $mode > 0 ? 0 : $amount;
	$itemObj->TafsiliType = $TafsiliType;
	$itemObj->TafsiliID = $TafsiliID;
	$itemObj->TafsiliType2 = $TafsiliType2;
	$itemObj->TafsiliID2 = $TafsiliID2;
	$itemObj->locked = $IsLock ? "YES" : "NO";
	if(!$itemObj->Add($pdo))
	{
		ExceptionHandler::PushException("خطا در ایجاد ردیف سند");
		return false;
	}
	
	$pdo->commit();
	return true;
}
?>
