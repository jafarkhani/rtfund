<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.09
//-----------------------------

require_once DOCUMENT_ROOT . '/loan/request/request.class.php';
require_once DOCUMENT_ROOT . '/accounting/cheque/cheque.class.php';


class EventComputeItems {
	
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
				$dt = array();
				$ComputeArr = LON_requests::ComputePayments($ReqObj->RequestID, $dt);
				foreach($ComputeArr as $row)
				{
					if($row["ActionType"] != "pay" || $row["BackPayID"] != $BackPayObj->BackPayID)
						continue;
					
					switch($ItemID)
					{
						case 31:
							return $row["share_pure"];
						case 33:
						case 34:
							$wagePercent = $PartObj->CustomerWage;
							$FundWage = round(($PartObj->FundWage/$wagePercent)*$row["share_wage"]);
							$AgentWage = $row["share_wage"] - $FundWage;
							if($ItemID == 34)
								return $FundWage;
							if($ItemID == 33)
								return $AgentWage;
						case 35:
						case 36:
							if($PartObj->LatePercent*1 == 0)
								return 0;
							$lateAmount = $row["share_LateWage"];
							$FundLate = round(($PartObj->FundForfeitPercent/$PartObj->LatePercent)*$lateAmount);
							$AgentLate = $lateAmount - $FundLate;
							if($ItemID == 36)
								return $FundLate;
							if($ItemID == 35)
								return $AgentLate;
						case 37:
						case 38:	
							if($PartObj->ForfeitPercent*1 == 0)
								return 0;
							$forfeitAmount = $row["share_LateForfeit"];
							$FundForfeit = round(($PartObj->FundForfeitPercent/$PartObj->ForfeitPercent)*$forfeitAmount);
							$AgentForfeit = $forfeitAmount - $FundForfeit;
							if($ItemID == 38)
								return $FundForfeit;
							if($ItemID == 37)
								return $AgentForfeit;
							
					}
				}
		}		
		
	}
	
	static function LoanDaily($ItemID, $SourceObjects){
		
		require_once '../loan/request/request.class.php';
		
		$ReqObj = new LON_requests((int)$SourceObjects[0]);
		$PartObj = new LON_ReqParts((int)$SourceObjects[1]);
		$ComputeDate = $SourceObjects[2];
		
		if($PartObj->CustomerWage*1 == 0 || $PartObj->ComputeMode != "NEW" )
			return 0; 
		
		$PureArr = LON_requests::ComputePures($ReqObj->RequestID);
		$LastPureAmount = $PureArr[0]["totalPure"];
		for($i=1; $i < count($PureArr);$i++)
		{
			if($PureArr[$i]["InstallmentDate"] < $ComputeDate)
				continue;
			$LastPureAmount = $PureArr[$i-1]["totalPure"];
			break;
		}
		if($i == count($PureArr))
			return 0;
		
		//$fromDate = $i == 1 ? $PartObj->PartDate : $PureArr[$i-1]["InstallmentDate"];
		//$days = DateModules::GDateMinusGDate($ComputeDate, $fromDate);
		$wage = round($LastPureAmount*$PartObj->CustomerWage/36500);
		
		$wagePercent = $PartObj->CustomerWage;
		$FundWage = round(($PartObj->FundWage/$wagePercent)*$wage);
		$AgentWage = $wage - $FundWage;
		
		if($ItemID == "80")
			return $FundWage;
		if($ItemID == "81")
			return $AgentWage;
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
		
		$t1 = array("TafsiliID" => "", "TafsiliDesc" => "");
		$t2 = array("TafsiliID" => "", "TafsiliDesc" => "");
		$t3 = array("TafsiliID" => "", "TafsiliDesc" => "");
						
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
			case EVENT_LOANDCHEQUE_agentSource:
			case EVENT_LOANCONTRACT_innerSource:
				
				$ReqObj = new LON_requests($params[0]);
				/* @var $ReqObj LON_requests */
				
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
