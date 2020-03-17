<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.09
//-----------------------------

require_once DOCUMENT_ROOT . '/loan/request/request.class.php';
require_once DOCUMENT_ROOT . '/accounting/cheque/cheque.class.php';
require_once DOCUMENT_ROOT . '/accounting/docs/doc.class.php';
require_once DOCUMENT_ROOT . '/loan/warrenty/request.class.php';

			
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
				return $PartObj->PartAmount - 
					LON_requests::TotalSubtractsOfPayAmount($ReqObj->RequestID,$PartObj);
			
			case 3 : //مبلغ قابل پرداخت دراین مرحله	
				
				return $PayObj->_PurePayedAmount;
				
			case 5 : // مبلغ قابل پرداخت به مشتری
				return LON_requests::GetPayedAmount($ReqObj->RequestID, $PartObj);
				
			case 2 : //مبلغ کل کارمزد
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID);
				if($PartObj->WageReturn == "INSTALLMENT" && $PartObj->FundWage >= $PartObj->CustomerWage)
					return $result["CustomerWage"];
				if($PartObj->AgentReturn == "INSTALLMENT" && $PartObj->FundWage < $PartObj->CustomerWage)
					return $result["CustomerWage"];
				return 0;
				
			case 14 : //مبلغ کارمزد تحقق نیافته سرمایه گذار
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID);
				return $PartObj->AgentReturn == "INSTALLMENT" ? $result["AgentWage"] : 0;
				
			case 15 : // مبلغ کارمزد تحقق نیافته صندوق
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID);
				return $PartObj->WageReturn == "INSTALLMENT" ? $result["FundWage"] : 0;
							
			case 20: //کارمزد ثابت صندوق
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID, $PartObj, 
						$PayObj->PayID>0 ? $PayObj->_PurePayedAmount : null);
				if($PartObj->WageReturn != "INSTALLMENT")
					return $result["FundWage"];
				return 0;
				
			case 21: //کارمزد ثابت سرمایه گذار
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID, $PartObj, 
						$PayObj->PayID>0 ? $PayObj->_PurePayedAmount : null);
				if($PartObj->AgentReturn != "INSTALLMENT")
					return $result["AgentWage"];
				return 0;
				
			case 22: //مبلغ کارمزد صندوق از سپرده سرمایه گذار
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID, $PartObj);
				if($PartObj->FundWage > $PartObj->CustomerWage)
					return $result["FundWage"] - $result["CustomerWage"];
				return 0;
			
			case 23: // کارمزد ثابت5% صنوش
				return $PartObj->PartAmount*5/100;
				
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
							if($PartObj->CustomerWage == 0)
								return 0;
							$FundWage = $PartObj->FundWage*1 > $PartObj->CustomerWage*1 ? $row["wage"] : 
								round(($PartObj->FundWage/$PartObj->CustomerWage)*$row["wage"]);
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
							$FundLate = $PartObj->FundWage*1 > $PartObj->CustomerWage*1 ? $lateAmount : 
								round(($PartObj->FundWage/$PartObj->CustomerWage)*$lateAmount);
							
							$AgentLate = $lateAmount - $FundLate;
							if($ItemID == 36)
								return $FundLate;
							if($ItemID == 35)
								return $AgentLate ;
						case 37:
						case 38:	
							if($PartObj->ForfeitPercent*1 == 0)
								return 0;
							$forfeitAmount = $row["pnlt"];
							$FundForfeit = $PartObj->FundWage*1 > $PartObj->CustomerWage*1 ? $forfeitAmount : 
									round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$forfeitAmount);
							$AgentForfeit = $forfeitAmount - $FundForfeit;
							if($ItemID == 38)
								return $FundForfeit;
							if($ItemID == 37)
								return $AgentForfeit;
						case 41:
						case 42:	
							$earlyAmount = $row["early"];
							if($PartObj->CustomerWage == 0)
								return 0;
							$FundEarly = $PartObj->FundWage*1 > $PartObj->CustomerWage*1 ? $earlyAmount : 
									round(($PartObj->FundWage/$PartObj->CustomerWage)*$earlyAmount);
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
						
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$ComputeDate = $SourceObjects[2];
		
		if($ItemID == "80" || $ItemID == "81")
		{
			if($ComputeDate < $PartObj->PartDate)
				return 0;
			
			if($PartObj->CustomerWage*1 == 0)
				return 0; 

			if(isset(self::$LoanPuresArray[ $ReqObj->RequestID ]))
				$PureArr = self::$LoanPuresArray[ $ReqObj->RequestID ];
			else 
			{
				$result = LON_requests::GetWageAmounts($ReqObj->RequestID);
				if($result["CustomerWage"] == 0)
					self::$LoanPuresArray[ $ReqObj->RequestID ] = 0;
				else
				{
					$PureArr = LON_requests::ComputePures($ReqObj->RequestID);
					self::$LoanPuresArray[ $ReqObj->RequestID ] = $PureArr;
				}
			}

			if($PureArr === 0)
				return 0;
			
			$wage = 0;
			for($i=1; $i < count($PureArr);$i++)
			{
				if($ComputeDate < $PureArr[$i]["InstallmentDate"])
				{
					$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$PureArr[$i-1]["InstallmentDate"]);
					$wage = round($PureArr[$i]["wage"]/$totalDays);
					break;
				}
			}
			
			$FundWage = round(($PartObj->FundWage/$PartObj->CustomerWage)*$wage);
			$AgentWage = $wage - $FundWage;

			if($ItemID == "80")
				return $FundWage;
			if($ItemID == "81")
				return $AgentWage;
		}
		
		$Today = $ComputeDate;
		$Yesterday = DateModules::AddToGDate($Today, -1);
		
		if(isset(self::$LoanComputeArray[ $ReqObj->RequestID ][ $Today ]))
		{
			$todayArr = self::$LoanComputeArray[ $ReqObj->RequestID ][ $Today ];
			$yesterdayArr = self::$LoanComputeArray[ $ReqObj->RequestID ][ $Yesterday ];
		}
		else 
		{
			$todayArr = LON_Computes::GetRemainAmounts($ReqObj->RequestID, null, $Today);
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
				if($late < 0)
					return 0;
				$fundLate = round(($PartObj->FundWage/$PartObj->CustomerWage)*$late);
				return $fundLate;
			case 83 : 
				if($PartObj->CustomerWage == 0)
					return 0;
				$late = $todayArr["remain_late"] - $yesterdayArr["remain_late"];
				if($late < 0)
					return 0;
				$fundLate = round(($PartObj->FundWage/$PartObj->CustomerWage)*$late);
				return $late - $fundLate;
			case 84 : 
				if($PartObj->ForfeitPercent == 0)
					return 0;
				$penalty = $todayArr["remain_pnlt"] - $yesterdayArr["remain_pnlt"];
				if($penalty < 0)
					return 0;
				$fundPenalty = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$penalty);
				return $fundPenalty;
			case 85 : 
				if($PartObj->ForfeitPercent == 0)
					return 0;
				$penalty = $todayArr["remain_pnlt"] - $yesterdayArr["remain_pnlt"];
				if($penalty < 0)
					return 0;
				$fundPenalty = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$penalty);
				return $penalty - $fundPenalty;
			case 86 : 
				$early = $todayArr["total_early"] - $yesterdayArr["total_early"];
				return $early;
		}

	}
	
	static function LoanCost($ItemID, $SourceObjects){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$CostObj = new LON_costs((int)$SourceObjects[2]);

		if($ItemID == "140")
			return $CostObj->CostAmount;
	}
	
	static function LoanEnd($ItemID, $SourceObjects){
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		
		switch($ItemID){
			
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
	
	//--------------------------------------------------------
	
	static function Warrenty($ItemID, $SourceObjects){
		
		$ReqObj = new WAR_requests((int)$SourceObjects[0]);
				
		switch($ItemID){
			
			case 100 : // مبلغ تعهد ضمانتنامه
				return $ReqObj->amount;
				
			case 101 : // کارمزد ضمانتنامه
				$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$ReqObj->StartDate)-1;
				$TotalWage = round($days*$ReqObj->amount*(1-$ReqObj->SavePercent/100)*$ReqObj->wage/36500);	
				return $TotalWage + $ReqObj->RegisterAmount*1;
				
			case 103 : //مبلغ سپرده
				return  $ReqObj->amount*$ReqObj->SavePercent/100;
				
			case 110 : // تضامین
				$dt =  array();
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
							where Keytitle='no' and ObjectType='warrenty'
							group by DocumentID
						) t on(d.DocumentID=t.DocumentID)

						where IsConfirm='YES' AND b.param1=1 AND 
						paramType='currencyfield' AND ObjectType='warrenty' AND ObjectID=?",
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
				break;
				
			case 120 : // کارمزد برگشتی از ابطال
				$ReqObj->CancelDate = DateModules::shamsi_to_miladi($SourceObjects[2]);
				
				$extradays = $ItemID == 120 ? (int)$SourceObjects[1] : 0;		
				$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$ReqObj->StartDate)-1;
				$TotalWage = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	
				
				$days = DateModules::GDateMinusGDate($ReqObj->CancelDate,$ReqObj->StartDate);
				$days += $extradays*1;
				$NewWage = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	

				$RemainWage = $TotalWage-$NewWage;
				return $RemainWage;
				
			case 122 : // کارمزد برگشتی از تقلیل
				$EndDate = DateModules::shamsi_to_miladi($SourceObjects[2]);
				$newAmount = (int)$SourceObjects[1];
				
				$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$ReqObj->StartDate)-1;
				$TotalWage = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	
				
				$days = DateModules::GDateMinusGDate($EndDate,$ReqObj->StartDate)-1;
				$Wage1 = round($days*$ReqObj->amount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	
				
				$days = DateModules::GDateMinusGDate($ReqObj->EndDate,$EndDate)-1;
				$Wage2 = round($days*$newAmount*(1-($ReqObj->SavePercent/100))*$ReqObj->wage/36500);	

				$RemainWage = $TotalWage-$Wage1-$Wage2;
				return $RemainWage;
			
			case 123 : //اختلاف مبلغ ضمانتنامه در صورت تقلیل
				$newAmount = (int)$SourceObjects[1];
				return $ReqObj->amount - $newAmount;
				
			case 124 : //اختلاف مبلغ سپرده در صورت تقلیل
				$newAmount = (int)$SourceObjects[1];
				return $ReqObj->amount*$ReqObj->SavePercent/100 - $newAmount*$ReqObj->SavePercent/100;
			
			case 125 : // مبلغ تمدید ضمانتنامه
				$newObj = new WAR_requests((int)$SourceObjects[1]);
				return $newObj->amount;
				
			case 126 : // کارمزد تمدید ضمانتنامه
				$newObj = new WAR_requests((int)$SourceObjects[1]);
				$days = DateModules::GDateMinusGDate($newObj->EndDate,$newObj->StartDate)-1;
				$TotalWage = round($days*$newObj->amount*(1-$newObj->SavePercent/100)*$newObj->wage/36500);	
				return $TotalWage + $newObj->RegisterAmount*1;
		}
	} 
			
	//--------------------------------------------------------
	
	static function FindTafsili($TafsiliType, $ObjectID){

		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis 
				where IsActive='YES' AND ObjectID=? AND TafsiliType=?",
			array($ObjectID, $TafsiliType));
		
		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $TafsiliType . "-" .  $ObjectID . "]");
			return false;
		}

		return array("TafsiliID" => $dt[0]["TafsiliID"], "TafsiliDesc" => $dt[0]["TafsiliDesc"]);
	}
	
	static function SetSpecialTafsilis($EventRow, $params){
		
		$t1 = array("TafsiliID" => isset($_POST["TafsiliID1_" . $EventRow["RowID"]]) ? $_POST["TafsiliID1_" . $EventRow["RowID"]] : 0, "TafsiliDesc" => "");
		$t2 = array("TafsiliID" => isset($_POST["TafsiliID2_" . $EventRow["RowID"]]) ? $_POST["TafsiliID2_" . $EventRow["RowID"]] : 0, "TafsiliDesc" => "");
		$t3 = array("TafsiliID" => isset($_POST["TafsiliID3_" . $EventRow["RowID"]]) ? $_POST["TafsiliID3_" . $EventRow["RowID"]] : 0, "TafsiliDesc" => "");

		//......................................................................
		if($EventRow["EventType"] == "OutcomeCheque" && $EventRow["EventType2"] == INCOMECHEQUE_VOSUL)
		{
			$ChequeObj = new ACC_DocCheques($params[1]);
			if($EventRow["TafsiliType2"] == TAFSILITYPE_PERSON)
				$t2 = array("TafsiliID" => $ChequeObj->TafsiliID, "TafsiliDesc" => "");
			if($EventRow["CostID"] == COSTID_Bank && $EventRow["TafsiliType2"] == TAFSILITYPE_PERSON)
				$t2 = array("TafsiliID" => $ChequeObj->_AccountTafsiliID, "TafsiliDesc" => "");
			if($EventRow["TafsiliType1"] == TAFSILITYPE_ACCOUNTTYPE)
				$t1 = array("TafsiliID" => $ChequeObj->AccountTafsiliID, "TafsiliDesc" => "");
		}		
		//......................................................................
		if(	$EventRow["EventType"] == "LoanPayment" ||  
			$EventRow["EventType"] == "LoanBackPay" ||
			$EventRow["EventType"] == "LoanBackPayCheque" ||
			$EventRow["EventType"] == "LoanContract" ||
			$EventRow["EventType"] == "LoanDaily" ||
			$EventRow["EventType"] == "IncomeCheque" ||
			$EventRow["EventType"] == "LoanEnd" )
		{
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

			if($EventRow["TafsiliType1"] == TAFSILITYPE_SOURCE)
				$t1 = self::FindTafsili(TAFSILITYPE_SOURCE, $ReqObj->ReqPersonID);
			if($EventRow["TafsiliType2"] == TAFSILITYPE_SOURCE)
				$t2 = self::FindTafsili(TAFSILITYPE_SOURCE, $ReqObj->ReqPersonID);
			if($EventRow["TafsiliType3"] == TAFSILITYPE_SOURCE)
				$t3 = self::FindTafsili(TAFSILITYPE_SOURCE, $ReqObj->ReqPersonID);
		}
		//......................................................................
		if(	$EventRow["EventType"] == "RegisterWarrenty" ||  
			$EventRow["EventType"] == "CancelWarrenty" ||
			$EventRow["EventType"] == "EndWarrenty" ||
			$EventRow["EventType"] == "ExtendWarrenty" ||
			$EventRow["EventType"] == "SubWarrenty" )
		{
			$ReqObj = new WAR_requests($params[0]);
			if($EventRow["TafsiliType1"] == TAFSILITYPE_PERSON)
				$t1 = self::FindTafsili(TAFSILITYPE_PERSON, $ReqObj->PersonID);
			if($EventRow["TafsiliType2"] == TAFSILITYPE_PERSON)
				$t2 = self::FindTafsili(TAFSILITYPE_PERSON, $ReqObj->PersonID);

			if($EventRow["TafsiliType1"] == TAFSILITYPE_SOURCE)
				$t1 = self::FindTafsili(TAFSILITYPE_SOURCE, 0);
			if($EventRow["TafsiliType2"] == TAFSILITYPE_SOURCE)
				$t2 = self::FindTafsili(TAFSILITYPE_SOURCE, 0);
			if($EventRow["TafsiliType3"] == TAFSILITYPE_SOURCE)
				$t3 = self::FindTafsili(TAFSILITYPE_SOURCE, 0);
		}
		//......................................................................
		
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
						if($EventRow["EventType"] != "LoanBackPayCheque" && $EventRow["EventType"] != "LoanBackPay")
							break;
						$iObj = LON_installments::GetLastInstallmentObj($params[0]);
						$obj->{ "param" . $i } = DateModules::miladi_to_shamsi($iObj->InstallmentDate);
						break;
						
					case ACC_COST_PARAM_LOAN_LEVEL : // طبقه تسهيلات
						$record = LON_requests::GetRequestLevel($params[0]);
						$obj->{ "param" . $i } = $record["ItemID"];
						break;

					case ACC_COST_PARAM_CHEQUE_date:
						if($EventRow["EventType"] != "LoanBackPayCheque" && $EventRow["EventType"] != "LoanBackPay")
							break;
						$IncChequObj = new ACC_IncomeCheques($params[1]);
						$obj->{ "param" . $i } = DateModules::miladi_to_shamsi($IncChequObj->ChequeDate);
						break;

					case ACC_COST_PARAM_BANK:
						if($EventRow["EventType"] != "LoanBackPayCheque" && $EventRow["EventType"] != "LoanBackPay")
							break;
						$IncChequObj = new ACC_IncomeCheques($params[1]);
						$obj->{ "param" . $i } = $IncChequObj->ChequeBank;
						break;
					
					case ACC_COST_PARAM_ACCOUNT:
						if($EventRow["EventType"] == "OutcomeCheque")
						{
							$ChequObj = new ACC_DocCheques($params[1]);
							$obj->{ "param" . $i } = $ChequObj->_AccountNo;
						}
						break;
						
					case ACC_COST_PARAM_CHEQUE_NO:
						if($EventRow["EventType"] == "OutcomeCheque")
						{
							$ChequObj = new ACC_DocCheques($params[1]);
							$obj->{ "param" . $i } = $ChequObj->CheckNo;
						}
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
