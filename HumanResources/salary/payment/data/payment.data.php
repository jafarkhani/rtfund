<?php
//---------------------------
// programmer:	b.mahdipour
// Date:		94.11
//---------------------------
require_once '../../../header.inc.php';
require_once '../class/payment_calculation.class.php';
require_once '../class/payments.class.php';
require_once '../class/payment_cancel.class.php';
require_once '../../../baseInfo/class/salary_item_report.class.php';
require_once '../class/arrear_pay_calculation.class.php';

require_once inc_QueryHelper;
require_once(inc_response);
require_once inc_dataReader;

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";

switch ($task) {
	case "ProcessPayment":
		ProcessPayment();

	case "getProgress":
		getProgress();
            
    case "confirmation" :
		confirmation();
		
	case "Remove" :
		  Remove();
		
	//-----------------------------
	case "registerDoc":
		registerDoc();
		
	case "deleteDoc":
		deleteDoc();
		
	case "DifferSalaryItems":
		DifferSalaryItems();

        case "ProcessArrearPayment" :
              ProcessArrearPayment();
		  
}

function ProcessPayment() {
		
	
	$paymentCalcObj = new manage_payment_calculation();

	$paymentCalcObj->__YEAR = DateModules::GetYear($_POST["end_date"]);
	$paymentCalcObj->__MONTH = DateModules::GetMonth($_POST["end_date"]);
	$paymentCalcObj->__CALC_NORMALIZE_TAX = isset($_POST['tax_normalize']) ? "1" : "0";
	$paymentCalcObj->__START_NORMALIZE_TAX_MONTH = $_POST['tax_n_m'];
	$paymentCalcObj->__START_NORMALIZE_TAX_YEAR = $_POST['tax_normalized_year'];
	$paymentCalcObj->__BACKPAY_BEGIN_FROM = 1;
	$paymentCalcObj->__CALC_NEGATIVE_FICHE = isset($_POST['negative_fiche']) ? "1" : "0";
	$paymentCalcObj->month_start = DateModules::shamsi_to_miladi($_POST["start_date"]);
	$paymentCalcObj->month_end = DateModules::shamsi_to_miladi($_POST["end_date"]);
	$paymentCalcObj->__MONTH_LENGTH = ceil(DateModules::GDateMinusGDate($paymentCalcObj->month_end, $paymentCalcObj->month_start) + 1);	
	$paymentCalcObj->__MSG = $_POST["message"];
		
	// <editor-fold defaultstate="collapsed" desc="Create Where" >
	$where = "1=1";
	$whereParam = array();
		
	if (!empty($_POST["from_staff_id"])) {
		$where .= " AND s.staff_id >= :fsid";
		$whereParam[":fsid"] = $_POST["from_staff_id"];
	}
	if (!empty($_POST["to_staff_id"])) {
		$where .= " AND s.staff_id <= :tsid";
		$whereParam[":tsid"] = $_POST["to_staff_id"];
	}
	
	// </editor-fold>

	$paymentCalcObj->__WHERE = $where;
	$paymentCalcObj->__WHEREPARAM = $whereParam;
							
	if (isset($_POST["compute_backpay"]))
		$res = $paymentCalcObj->run_back();
	else 
		$res = $paymentCalcObj->run(); 
		
	if(!$res)
	{
		echo Response::createObjectiveResponse(false , ExceptionHandler::popExceptionDescription() );
		die(); 	
	}
	else
	{		
		echo Response::createObjectiveResponse(true, $paymentCalcObj->success_counter . "_" . $paymentCalcObj->fail_counter);
		die(); 	
	}
		
}

function ProcessArrearPayment() {

	$payArrCalcObj = new manage_arrear_pay_calculation();

	$payArrCalcObj->__YEAR = $_POST["pay_year"] ; 
	$payArrCalcObj->__MONTH = 12 ;
	
	$start_date = $_POST["pay_year"]."/01/01";
	$end_date = $_POST["pay_year"]."/12/".DateModules::DaysOfMonth($payArrCalcObj->__YEAR,12);
	
	$payArrCalcObj->__CALC_NORMALIZE_TAX = isset($_POST['tax_normalize']) ? "1" : "0";
	$payArrCalcObj->__START_NORMALIZE_TAX_MONTH = 1 ;
	$payArrCalcObj->__START_NORMALIZE_TAX_YEAR = $_POST["pay_year"] ;
	$payArrCalcObj->__BACKPAY_BEGIN_FROM = 1;	
	$payArrCalcObj->month_start = DateModules::shamsi_to_miladi($start_date);
	$payArrCalcObj->month_end = DateModules::shamsi_to_miladi($end_date);

	$payArrCalcObj->__MSG = $_POST["message"];

	// <editor-fold defaultstate="collapsed" desc="Create Where" >
	$where = "1=1";
	$whereParam = array();

	if (!empty($_POST["person_type"])) {
		$where .= " AND p.person_type=:ptype";
		$whereParam[":ptype"] = $_POST["person_type"];
	}
	if (!empty($_POST["from_staff_id"])) {
		$where .= " AND s.staff_id >= :fsid";
		$whereParam[":fsid"] = $_POST["from_staff_id"];
	}
	if (!empty($_POST["to_staff_id"])) {
		$where .= " AND s.staff_id <= :tsid";
		$whereParam[":tsid"] = $_POST["to_staff_id"];
	}
	if (!empty($_POST["from_cost_center_id"])) {
		$where .= " AND w.cost_center_id >= :fccid";
		$whereParam[":fccid"] = $_POST["from_cost_center_id"];
	}
	if (!empty($_POST["to_cost_center_id"])) {
		$where .= " AND w.cost_center_id <= :tccid";
		$whereParam[":tccid"] = $_POST["to_cost_center_id"];
	}
	if(!isset($_POST["ouid"]))
	{
		$result = QueryHelper::MK_org_units($_POST["ouid"], true);
		$where .= " AND " . $result["where"];
		$whereParams = array_merge($whereParam, $result["param"]);
	}

	// </editor-fold>

	$payArrCalcObj->__WHERE = $where;
	$payArrCalcObj->__WHEREPARAM = $whereParam;

	$res = $payArrCalcObj->run_back();
	
	if(!$res)
	{
		echo Response::createObjectiveResponse(false , ExceptionHandler::popExceptionDescription() );
		die();
	}
	else
	{
		echo Response::createObjectiveResponse(true, $payArrCalcObj->success_counter . "_" . $payArrCalcObj->fail_counter);
		die();
	}
		
}

function getProgress() {
	
	if(file_exists(HR_TemlDirPath . 'pay_calc_monitor_file.html'))
	{
		//$result = file_get_contents(HR_TemlDirPath . 'pay_calc_monitor_file.html');
				
		echo Response::createObjectiveResponse(false, "");
	}
	else
		echo Response::createObjectiveResponse(true, "پایان عملیات");
	die();
}

function confirmation () {
    
	$keys = array_keys($_POST);
	$WhereCost = "" ;
	$arr = "" ;
	
	//...................... مراکز هزینه ................
	
	for($i=0; $i < count($_POST); $i++)
	{
		if(strpos($keys[$i],"chkcostID_") !== false)
		{			
			$arr = preg_split('/_/', $keys[$i]);	
			if(isset($arr[1]))
			$WhereCost .= ($WhereCost!="") ?  ",".$arr[1] : $arr[1] ; 
		}	
				
	}
    
    if($_POST["ItemType"] == "salary") 
        {
            $obj = new manage_payments();
            PdoDataAccess::FillObjectByArray($obj, $_POST) ;  
            $obj->_CostCenter = $WhereCost; 
            $return = $obj->change_payment_state($_POST["PersonType"]);	
        }    
    else if($_POST["ItemType"] == "reportSub") 
        {        
           $obj = new manage_salary_item_report() ;  
           
           $obj->PayYear = $_POST["pay_year"] ; 
           $obj->PayMonth = $_POST["pay_month"] ; 
           $obj->PersonType = $_POST["PersonType"] ;  
           $obj->state =  $_POST["state"] ;  
           
           $return = $obj->change_state() ; 

        }
    
    
        if($return)
		echo Response::createResponse(true,$obj->state);
	else
		echo Response::createResponse(false , ExceptionHandler::ConvertExceptionsToJsObject());
	die();
            
}

function Remove() {	
	//... برای ماموریت قسمت تایید مماموریت نوشته شود و چک بشود که چنانچه تایید شده است امکان حذف ان رکوردها وجود ندارد.
	
	$keys = array_keys($_POST);
	$WhereCost = "" ;
	$arr = "" ;
	
	//...................... مراکز هزینه ................
	
	for($i=0; $i < count($_POST); $i++)
	{
		if(strpos($keys[$i],"chkcostID_") !== false)
		{			
			$arr = preg_split('/_/', $keys[$i]);	
			if(isset($arr[1]))
			$WhereCost .= ($WhereCost!="") ?  ",".$arr[1] : $arr[1] ; 
		}	
				
	}
	
	$cancelObj = new manage_payment_cancel();
	$cancelObj->year = $_POST["pay_year"] ; 	
	$cancelObj->month = $_POST["pay_month"] ; 
	$cancelObj->payment_type = $_POST["payment_type"] ;  
	$cancelObj->cost_center_id = $WhereCost;
	$cancelObj->staff_id = $_POST["SID"] ; 
		
	if($_POST["PersonType"] == 1 ||  $_POST["PersonType"] == 2 || $_POST["PersonType"] == 3 || $_POST["PersonType"] == 5 )
	$cancelObj->person_type = $_POST["PersonType"] ; 
    elseif ( $_POST["PersonType"] == 102 )
	   $cancelObj->person_type = '1,2,3' ; 	
    elseif ( $_POST["PersonType"] == 100 )
	   $cancelObj->person_type = '1,2,3,5' ; 	
  
	$res = $cancelObj->run();
	
	if($cancelObj->payment_type == 1 )
		echo Response::createObjectiveResponse($res, $cancelObj->success_count['WRIT']."_". $cancelObj->success_count['FICH']."_". $cancelObj->success_count['FICH_ITEM'] . "_" . $cancelObj->unsuccess_count );
	else 
		echo Response::createObjectiveResponse($res, $cancelObj->success_count['FICH']."_". $cancelObj->success_count['FICH_ITEM'] . "_" . $cancelObj->unsuccess_count );	

	die();
	
	  
	 // فعلا که دیتا بیس ها جداگانه است پارامتر می فرستیم بعد با یستی اصلاح گردد.
	// $return = $obj->PaymentCancle($_POST["PersonType"]); 
	
}

//------------------------------------------------------

function registerDoc(){
	
	require_once '../../../../accountancy/import/salary/salary.class.php';
	$AccDocObj = new ImportSalary($_POST["pay_year"], $_POST["pay_month"]);
	if($AccDocObj->InitialImportance($_POST["PersonType"] == "contract") === false)
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString("<br>") );
		die();
	}
	
	$query = "	
		select  c.CostCenterID,
				c.Title,
				sit.CostID,
				sit.CostType,
				case s.person_type when 1 then if(w.emp_state=11, 'ConditionalProf', 'Prof')
								   when 2 then 'Staff'
								   when 3 then 'Worker'
								   when 5 then 'Contract' end PersonType, 
				s.last_retired_pay,
				w.emp_state,
				c.AccUnitID,				
				pit.salary_item_type_id,
				sum(pit.pay_value) pay_value,
				sum(pit.diff_value_coef * pit.diff_pay_value) diff_pay_value,
				sum(param7) param7,
				sum(diff_param7_coef * diff_param7) diff_param7,
				sum(param2) param2,
				sum(diff_param2_coef * diff_param2) diff_param2,
				sum(param3) param3,
				sum(diff_param3_coef * diff_param3) diff_param3
				
		FROM payments p 
			JOIN staff s ON s.staff_id = p.staff_id
			JOIN writs w
				ON(p.writ_id = w.writ_id AND
				   p.writ_ver = w.writ_ver AND
				   p.staff_id = w.staff_id AND w.state=3)

			JOIN payment_items pit 
				ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
				   p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)

			JOIN persons per ON (per.personid = s.personid )
			JOIN banks b ON b.bank_id = p.bank_id
			JOIN CostCenterPlan c ON c.CostCenterID = w.CostCenterID
			JOIN salary_item_types sit using(salary_item_type_id)
			
		WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = 1 
			and s.person_type in(" . ($_POST["PersonType"] == "contract" ? "5" : "1,2,3") . ") AND sit.CostID>0

		group by c.CostCenterID , s.person_type , p.payment_type , pit.salary_item_type_id
		order by c.CostCenterID,pit.salary_item_type_id";
	
	$dt = PdoDataAccess::runquery_fetchMode($query, array(":py" => $_POST["pay_year"], ":pm" => $_POST["pay_month"]));

	$AccError = "";
	while ($row = $dt->fetch()) 
	{
		$amount = 0;
		switch( $row["salary_item_type_id"])
		{
			case 44 :
				if($row["PersonType"] == "ConditionalProf")
					$row["CostID"] = 19023;
				$amount = $row["pay_value"] + $row["diff_pay_value"];	
				break;
			//..................................................................
			case 143 :
			case 38 :
				$coef_dolat = 1.7 / 1.65;
				$amount = $row["param7"] + $row["diff_param7"] + ($row["param7"] + $row["diff_param7"]) * $coef_dolat;
				break;
			//..................................................................
			case 9920 :
			case 144 :
				$amount = $row["param2"] + $row["diff_param2"];
				break;
			case 145 :
				$amount = $row["param2"] + $row["diff_param2"] + $row["param3"] + $row["diff_param3"];
				break;
			case 744 :
				$amount = $row["param2"] + $row["diff_param2"];
				break;
			//..................................................................
			case 149 : 
			case 150 : 
			case 750 :	
				$amount = $row["param3"] + $row["diff_param3"];
				break;
			//..................................................................
			case 9931 :
				if($row["PersonType"] == "Staff" || $row["PersonType"] == "Contract")
					$row["CostID"] = 341;
				if($row["PersonType"] == "Worker")
					$row["CostID"] = 342;
				
				$amount = $row["pay_value"] + $row["diff_pay_value"];				
				break;
			//..................................................................
			default : 
				$amount = $row["pay_value"] + $row["diff_pay_value"];
		}
		
		$AccDocObj->AddItem($row["AccUnitID"], $row["CostID"], $amount, $row["PersonType"]);
		
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$AccError .= "مرکز هزینه : " . $row["Title"] . "<br><hr><br>";
			$AccError .= "<span style=color:red><h3>" . ExceptionHandler::GetExceptionsToString ("<br>") . "</h3></span>";
		}
		ExceptionHandler::PopAllExceptions();
	}
	
	$AccDocObj->CommitImportance();
	
	echo Response::createObjectiveResponse($AccError == "", $AccError);
	die();
}

function deleteDoc(){
	
	require_once '../../../../accountancy/import/salary/salary.class.php';
	
	$AccDocObj = new ImportSalary($_POST["pay_year"], $_POST["pay_month"]);
	if(!$AccDocObj->DeleteAccDoc($_POST["PersonType"] == "contract"))
		$msg = ExceptionHandler::GetExceptionsToString();
	else
		$msg = " پیش سند با موفقیت حذف شد";
	
	echo Response::createObjectiveResponse(true, $msg);
	die();
}

function DifferSalaryItems(){
	
	$year = $_POST["pay_year"];
	$month = $_POST["pay_month"];
	$pt = $_POST["PersonType"] == "contract" ? "5" : "1,2,3";
	
	$query = "
		select CostID,salary_item_type_id,full_title,
		sum(
			case when salary_item_type_id in(143,38)
				then param7 + diff_param7 + (param7 + diff_param7)*1.03030303
				when salary_item_type_id in(9920,144,744) then param2 + diff_param2
				when salary_item_type_id in(145) then param2 + diff_param2 + param3 + diff_param3
				when salary_item_type_id in(149,150,750) then param3 + diff_param3
				else pay_value + diff_pay_value
					end
			) st
		from (
			select  c.CostCenterID,c.Title,sit.CostID,sit.full_title,sit.CostType,
					case s.person_type when 1 then if(w.emp_state=11, 'ConditionalProf', 'Prof')
									when 2 then 'Staff'
									when 3 then 'Worker'
									when 5 then 'Contract' end PersonType,
					s.last_retired_pay,
					w.emp_state,
					c.AccUnitID,
					pit.salary_item_type_id,
					sum(pit.pay_value) pay_value,
					sum(pit.diff_value_coef * pit.diff_pay_value) diff_pay_value,
					sum(param7) param7,
					sum(diff_param7_coef * diff_param7) diff_param7,
					sum(param2) param2,
					sum(diff_param2_coef * diff_param2) diff_param2,
					sum(param3) param3,
					sum(diff_param3_coef * diff_param3) diff_param3

				FROM payments p
					JOIN staff s ON s.staff_id = p.staff_id
					JOIN writs w ON(p.writ_id = w.writ_id AND p.writ_ver = w.writ_ver AND p.staff_id = w.staff_id AND w.state=3)
					JOIN payment_items pit ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND	
							p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN persons per ON (per.personid = s.personid )
					JOIN banks b ON b.bank_id = p.bank_id
					JOIN CostCenterPlan c ON c.CostCenterID = w.CostCenterID
					JOIN salary_item_types sit using(salary_item_type_id)

				WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = 1
					and s.person_type in(" . $pt . ") and (costID=0 OR CostID is null) and pay_value > 0

				group by c.CostCenterID , s.person_type , p.payment_type , pit.salary_item_type_id
				order by c.CostCenterID,pit.salary_item_type_id
		)t
		group by CostID,salary_item_type_id";
	
	$dt = PdoDataAccess::runquery($query, array($year, $month));
	
	if(count($dt) == 0)
	{
		echo "<br>" . "هیچ قلم حقوقی یافت نشد." . "<br>&nbsp;";
		die();
	}
	
	$str = "<table border=1 width=100%>";
	foreach($dt as $row)
		$str .= "<tr><td style='padding:5px' align=center>" . $row["salary_item_type_id"] . "</td>
					 <td style='padding:5px'>" . $row["full_title"] . "</td></tr>";
	
	echo $str . "</table>";
	die();
}

?>
