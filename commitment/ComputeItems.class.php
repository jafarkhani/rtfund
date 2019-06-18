<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.09
//-----------------------------

require_once DOCUMENT_ROOT . '/loan/request/request.class.php';
require_once DOCUMENT_ROOT . '/accounting/cheque/cheque.class.php';


class EventComputeItems {
	
	static $LoanComputeArray = array();
	static $LoanPuresArray = array();
	
	static function LoanAllocate($ItemID, $SourceObjects){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		
		switch($ItemID){
			
			case 1 : // مبلغ اصل تسهیلات
				return $PartObj->PartAmount;
		}		
		
	}

	static function PayLoan($ItemID, $SourceObjects){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$PayObj = new LON_payments(isset($SourceObjects[2]) ? $SourceObjects[2] : 0);
		
		switch($ItemID){
			
			case 1 : // مبلغ اصل تسهیلات
				return $PartObj->PartAmount;
			
			case 3 : //مبلغ قابل پرداخت دراین مرحله	
				return $PayObj->PayAmount;
				
			case 5 : // مبلغ قابل پرداخت به مشتری
				return LON_requests::GetPayedAmount($ReqObj->RequestID, $PartObj);
				
			case 2 : //مبلغ کل کارمزد
			case 14 : //مبلغ کارمزد تحقق نیافته سرمایه گذار
			case 15 : // مبلغ کارمزد تحقق نیافته صندوق
				
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID);
				if($ItemID == 2)
					return $result["CustomerWage"];
				if($ItemID == 14)
					return $result["AgentWage"];
				if($ItemID == 15)
					return $result["FundWage"];
			
			case 16 : //مبلغ تنفس
			case 17 : //سهم اصل از تنفس
			case 18 : //سهم کارمزد از تنفس
				$result = LON_requests::GetDelayAmounts($ReqObj->RequestID, $PartObj);
				$wage = LON_requests::GetWageAmounts($ReqObj->RequestID, $PartObj);
				if($ItemID == 16)
					return $result["CustomerDelay"];
				if($ItemID == 17)
					return round($result["CustomerDelay"]*$PartObj->PartAmount/
						($wage["CustomerWage"]*1 + $PartObj->PartAmount*1));
				if($ItemID == 18)
					return $result["CustomerDelay"]*1 - round($result["CustomerDelay"]*$PartObj->PartAmount/
						($wage["CustomerWage"]*1 + $PartObj->PartAmount*1));
				
			case 6 : // مبلغ تضمین
				$dt =  array();
				/*$dt = PdoDataAccess::runquery("select * from DMS_documents 
					join BaseInfo b on(InfoID=DocType AND TypeID=8)
					join ACC_DocItems on(SourceType=" . DOCTYPE_DOCUMENT . " AND SourceID1=DocumentID)
					where IsConfirm='YES' AND b.param1=1 AND ObjectType='loan' AND ObjectID=?", 
						array($ReqObj->RequestID));
				 */
				$returnArray = array();
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

						where IsConfirm='YES' AND b.param1=1 AND paramType='currencyfield' 
						AND ObjectType='loan' AND ObjectID=?",
						array($ReqObj->RequestID));

					foreach($dt as $row)
					{
						$returnArray[] = array(
							"amount" => $row["ParamValue"],
							"param1" => $row["DocNo"],
							"SourceID4" => $row["DocumentID"]);
					}
					return $returnArray;
				}
		}		
		
	}

	static function LoanBackPay($ItemID, $SourceObjects){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$BackPayObj = new LON_BackPays((int)$SourceObjects[2]);

		switch($ItemID){
			
			case 30 : // مبلغ دریافت شده
			case 32 : // مبلغ چک وصول شده
				return $BackPayObj->PayAmount;
			
			case 31 : // مبلغ اصل قسط
			case 33 : //مبلغ کارمزد سهم سرمایه گذار 
			case 34 : // مبلغ کارمزد سهم صندوق 
			case 35 : // کارمزد تاخیر سهم سرمایه گذار
			case 36 :  // کارمزد	 تاخیر سهم صندوق
			case 37 :  // جریمه تاخیر سهم سرمایه گذار
			case 38 :  // جریمه تاخیر سهم صندوق
			case 41 :  // کارمزد تعجیل سهم صندوق
			case 42 :  // کارمزد تعجیل سهم سرمایه گذار
			case 43 :  // اضافه پرداختی
				
				if(isset(self::$LoanComputeArray[ $ReqObj->RequestID ]))
					$ComputeArr = self::$LoanComputeArray[ $ReqObj->RequestID ];
				else 
				{
					$ComputeArr = LON_Computes::ComputePayments($ReqObj->RequestID);
					self::$LoanComputeArray[ $ReqObj->RequestID ] = $ComputeArr;
				}
				foreach($ComputeArr as $row)
				{
					if($row["type"] != "pay" || $row["BackPayID"] != $BackPayObj->BackPayID)
						continue;
					
					switch($ItemID)
					{
						case 31:
							return $row["pure"];
						case 33:
						case 34:
							$wagePercent = $PartObj->CustomerWage;
							$FundWage = round(($PartObj->FundWage/$wagePercent)*$row["wage"]);
							$AgentWage = $row["wage"] - $FundWage;
							if($ItemID == 34)
								return $FundWage;
							if($ItemID == 33)
								return $AgentWage;
						case 35:
						case 36:
							if($PartObj->LatePercent*1 == 0)
								return 0;
							$lateAmount = $row["late"];
							$FundLate = round(($PartObj->FundWage/$PartObj->CustomerWage)*$lateAmount);
							$AgentLate = $lateAmount - $FundLate;
							if($ItemID == 36)
								return $FundLate;
							if($ItemID == 35)
								return $AgentLate;
						case 37:
						case 38:	
							if($PartObj->ForfeitPercent*1 == 0)
								return 0;
							$forfeitAmount = $row["pnlt"];
							$FundForfeit = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$forfeitAmount);
							$AgentForfeit = $forfeitAmount - $FundForfeit;
							if($ItemID == 38)
								return $FundForfeit;
							if($ItemID == 37)
								return $AgentForfeit;
						case 41:
						case 42:	
							$earlyAmount = $row["early"];
							$FundEarly = round(($PartObj->FundWage/$PartObj->CustomerWage)*$earlyAmount);
							$AgentEarly = $earlyAmount - $FundEarly;
							if($ItemID == 41)
								return $FundEarly;
							if($ItemID == 42)
								return $AgentEarly;
							
						case 43:	
							return $row["remainPayAmount"];
					}
				}
		}		
		
	}
	
	static function LoanDaily($ItemID, $SourceObjects){
						
		require_once '../loan/request/request.class.php';
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$ComputeDate = $SourceObjects[2];
		
		if($ItemID == "80" || $ItemID == "81")
		{

			if($PartObj->CustomerWage*1 == 0 || $PartObj->ComputeMode != "NEW" )
				return 0; 

			if(isset(self::$LoanPuresArray[ $ReqObj->RequestID ]))
				$PureArr = self::$LoanPuresArray[ $ReqObj->RequestID ];
			else 
			{
				$PureArr = LON_requests::ComputePures($ReqObj->RequestID);
				self::$LoanPuresArray[ $ReqObj->RequestID ] = $PureArr;
			}

			$LastPureAmount = 0;
			for($i=1; $i < count($PureArr);$i++)
			{
				if($ComputeDate < $PureArr[$i]["InstallmentDate"])
				{
					$LastPureAmount = $PureArr[$i-1]["totalPure"];
					break;
				}
			}
			$wage = round($LastPureAmount*$PartObj->CustomerWage/36500);

			$wagePercent = $PartObj->CustomerWage;
			$FundWage = round(($PartObj->FundWage/$wagePercent)*$wage);
			$AgentWage = $wage - $FundWage;

			if($ItemID == "80")
				return $FundWage;
			if($ItemID == "81")
				return $AgentWage;
		}
		
		$Today = DateModules::Now();
		$Yesterday = DateModules::AddToGDate($Today, -1);
		
		if(isset(self::$LoanComputeArray[ $ReqObj->RequestID ][ $Today ]))
		{
			$todayArr = self::$LoanComputeArray[ $ReqObj->RequestID ][ $Today ];
			$yesterdayArr = self::$LoanComputeArray[ $ReqObj->RequestID ][ $Yesterday ];
		}
		else 
		{
			$todayArr = LON_Computes::GetRemainAmounts($ReqObj->RequestID);
			$yesterdayArr = LON_Computes::GetRemainAmounts($ReqObj->RequestID, null, $Yesterday);
			
			self::$LoanComputeArray[ $ReqObj->RequestID ][$Today] = $todayArr;
			self::$LoanComputeArray[ $ReqObj->RequestID ][$Yesterday] = $yesterdayArr;
		}
		
		switch($ItemID*1)
		{
			case 82 : 
				if($PartObj->CustomerWage == 0)
					return 0;
				$late = $todayArr["remain_late"] - $yesterdayArr["remain_late"];
				 $fundLate = round(($PartObj->FundWage/$PartObj->CustomerWage)*$late);
				return $fundLate;
			case 83 : 
				if($PartObj->CustomerWage == 0)
					return 0;
				$late = $todayArr["remain_late"] - $yesterdayArr["remain_late"];
				 $fundLate = round(($PartObj->FundWage/$PartObj->CustomerWage)*$late);
				return $late - $fundLate;
			case 84 : 
				if($PartObj->ForfeitPercent == 0)
					return 0;
				$penalty = $todayArr["remain_pnlt"] - $yesterdayArr["remain_pnlt"];
				$fundPenalty = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$penalty);
				return $fundPenalty;
			case 85 : 
				if($PartObj->ForfeitPercent == 0)
					return 0;
				$penalty = $todayArr["remain_pnlt"] - $yesterdayArr["remain_pnlt"];
				$fundPenalty = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$penalty);
				return $penalty - $fundPenalty;
		}

	}
	
	//--------------------------------------------------------
	
	static function FindTafsili($TafsiliType, $ObjectID){

		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
				. "where IsActive='YES' AND ObjectID=? AND TafsiliType=?",
			array($ObjectID, $TafsiliType));
		
		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $TafsiliType . "-" .  $ObjectID . "]");
			return false;
		}

		return array("TafsiliID" => $dt[0]["TafsiliID"], "TafsiliDesc" => $dt[0]["TafsiliDesc"]);
	}
	
	static function SetSpecialTafsilis($EventID, $EventRow, $params){
		
		$t1 = array("TafsiliID" => "0", "TafsiliDesc" => "");
		$t2 = array("TafsiliID" => "0", "TafsiliDesc" => "");
		$t3 = array("TafsiliID" => "0", "TafsiliDesc" => "");
						
		switch($EventID)
		{
			case EVENT_LOAN_ALLOCATE:
			case EVENT_LOANPAYMENT_agentSource:
			case EVENT_LOANPAYMENT_innerSource:
			case EVENT_LOANBACKPAY_innerSource_cheque:
			case EVENT_LOANBACKPAY_innerSource_non_cheque:
			case EVENT_LOANBACKPAY_agentSource_committal_cheque:
			case EVENT_LOANBACKPAY_agentSource_committal_non_cheque:
			case EVENT_LOANBACKPAY_agentSource_non_committal_cheque:
			case EVENT_LOANBACKPAY_agentSource_non_committal_non_cheque:
			case EVENT_LOANCONTRACT_innerSource:
			case EVENT_LOANCONTRACT_agentSource_committal:
			case EVENT_LOANCONTRACT_agentSource_non_committal:
			case EVENT_LOANDAILY_innerSource:
			case EVENT_LOANDAILY_agentSource_committal:
			case EVENT_LOANDAILY_agentSource_non_committal:
			case EVENT_LOANDAILY_agentlate:
			case EVENT_LOANDAILY_innerLate:
			case EVENT_LOANDAILY_agentPenalty:
			case EVENT_LOANDAILY_innerPenalty:
			case EVENT_LOANCHEQUE_payed:
			case EVENT_LOANCHEQUE_agentSource:
			case EVENT_LOANCHEQUE_innerSource:
				
				$ReqObj = new LON_requests($params[0]);
				/* @var $ReqObj LON_requests */
				
				if(in_array($EventRow["CostCode"],array("3030101","1010101")) !== false)
					return array($t1,$t2,$t3);
				
				if($EventRow["TafsiliType1"] == TAFSILITYPE_LOAN)
					$t1 = self::FindTafsili(TAFSILITYPE_LOAN, $ReqObj->LoanID);
				if($EventRow["TafsiliType2"] == TAFSILITYPE_PERSON)
					$t2 = self::FindTafsili(TAFSILITYPE_PERSON, $ReqObj->LoanPersonID);
				if($EventRow["TafsiliType3"] == TAFSILITYPE_PERSON && $ReqObj->ReqPersonID*1 > 0)
					$t3 = self::FindTafsili(TAFSILITYPE_PERSON, $ReqObj->ReqPersonID);
				break;
		}
		return array($t1,$t2,$t3);
	}
	
	static function SetParams($EventID, $EventRow, $params, &$obj){
		
		if(count($params) > 0)
		{	
			for($i=1; $i<=3; $i++)
			{
				switch($EventRow["param" . $i])
				{
					case ACC_COST_PARAM_LOAN_RequestID : //شماره تسهيلات
						$obj->{ "param" . $i } = $params[0];
						break;
					case ACC_COST_PARAM_LOAN_LastInstallmentDate : //سررسيد اقساط
						$iObj = LON_installments::GetLastInstallmentObj($params[0]);
						$obj->{ "param" . $i } = DateModules::miladi_to_shamsi($iObj->InstallmentDate);
						break;
					case ACC_COST_PARAM_LOAN_LEVEL : // طبقه تسهيلات
						$obj->{ "param" . $i } = LON_requests::GetRequestLevel($params[0]);
						break;

					case ACC_COST_PARAM_CHEQUE_date:
						$IncChequObj = new ACC_IncomeCheques($params[1]);
						$obj->{ "param" . $i } = DateModules::miladi_to_shamsi($IncChequObj->ChequeDate);
						break;

					case ACC_COST_PARAM_BANK:
						$IncChequObj = new ACC_IncomeCheques($params[1]);
						$obj->{ "param" . $i } = $IncChequObj->ChequeBank;
						break;
				}
			}
		}
		foreach($_POST as $key => $val)
		{
			if(strpos($key, "param1_") !== false && $EventRow["RowID"] == preg_replace("/param1_/","",$key))
				$obj->param1 = $val;
			
			if(strpos($key, "param2_") !== false && $EventRow["RowID"] == preg_replace("/param2_/","",$key))
				$obj->param2 = $val;
			
			if(strpos($key, "param3_") !== false && $EventRow["RowID"] == preg_replace("/param3_/","",$key))
				$obj->param3 = $val;
					
		}
	}
}
