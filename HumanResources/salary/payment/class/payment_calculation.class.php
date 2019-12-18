<?php
//---------------------------
// programmer:	b.mahdipour
// Date:		92.11
//---------------------------
require_once '../../salary_params/class/salary_params.class.php';
require_once '../../person_org_docs/subtracts.class.php';
require_once getenv("DOCUMENT_ROOT") .'/attendance/traffic/traffic.class.php';
ini_set('max_execution_time', 1000); //300 seconds = 5 minutes

//ini_set("display_errors","on");
class manage_payment_calculation extends PdoDataAccess
{
	public $month_start; //از ورودي
	public $month_end; //از ورودي

	public $__MONTH_LENGTH; //از ورودي
	public $__YEAR; //از ورودي
	public $__MONTH; //از ورودي
	public $__CALC_NEGATIVE_FICHE = 0; //از ورودي
	public $__MSG ; //پيام براي فيش از ورودي
	public $__START_NORMALIZE_TAX_YEAR; //از ورودي
	public $__START_NORMALIZE_TAX_MONTH; //از ورودي
	public $__CALC_NORMALIZE_TAX; //آيا تعديل ماليات صورت گيرد يا خير؟ بلي=1 ... خير=0
	public $__WHERE; // شرط محدود کردن staff
	public $__WHEREPARAM;
	public $__BACKPAY_BEGIN_FROM; //محاسبه backpay از چه ماهي شروع شود
	public $ExtraWork = false ;

	private $last_month_end;
	private $last_month;

	private $salary_params; //يک آرايه جهت نگهداري پارامترهاي حقوقي
	private $tax_tables; //يک ارايه جهت نگهداري جداول مالياتي
	private $acc_info; //آرايه اي جهت نگهداري سرفصلهاي قلم هاي بازنشتگي و بيمه
	private $staff_writs; //آرايه اي جهت نگهداري احکامط کهدر محاسبه حقوق شخص جاري استفاده شدهاند
	private $cur_writ_id; //شماره حکم جاري
	private $cur_staff_id; // شماره staff جاري
	private $cur_work_sheet; //کارکرد ماهانه staff جاري
	
	private $last_writ_sum_retired_include; //مجموع حقوق مشمول بازنشستگي در آخرين حکم ماه جاري

	private $payment_items; //اقلام حقوق
	//private $person_subtract_array; //وام و کسوراتي که بايد در جدول person_subtract بروزرساني شوند
	//private $person_subtract_flow_array; //وام و کسوراتي که بايد در جدول person_subtract_flow بروزرساني شوند

	private $sum_tax_include; //يک متغير براي نگهداري مجموع مقادير قلام هاي مشمول ماليات
	private $sum_insure_include; //يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بيمه
	private $sum_retired_include; // يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بازنشتگي
	private $max_sum_pension; //در اين متغير همواره ماکزيمم حقوق مشمول بازنشستگ که مقرري به ان تعلق مي گيرد نگهداري ميشود
	private $extra_pay_value; // اضافه کار روز مزد ها 

	private $cost_center_id; //متغيري جهت نگهداري کد مرکز هزينه cur_staff که از آخرين حکم استخراج مي گردد

	//private $payment_file_h; //اقلامي که بايد در payment درج شوند
	//private $payment_items_file_h; //اقلامي که بايد در payment_items درج شوند
	//private $payment_writs_file_h; //اقلامي که بايد در payment_writs درج شوند
	//private $subtract_file_h; // اقلامي که بايد در person_subtracts بروزرساني شوند
	//private $subtract_flow_file_h; //اقلامي که بايد در person_flow درج شوند
	private $fail_log_file_h; //اقلامي که بايد در fail_log درج شوند
	private $success_log_file_h; //اقلامي که بايد در success_log درج شوند

	private $writ_sql_rs;
	private $pay_get_list_rs;
	private $subtracts_rs;
	private $tax_rs;
	private $tax_history_rs;
	private $staff_rs;
	private $service_insure_rs;
	private $salary_sql_rs; 
	private $diff_rs;
	private $pension_rs;

	private $run_id; //شناسه اين اجرا
	private $expire_time; //مدت زماني که کاربر بين دو اجراي متوالي محاسبه حقوق بايد صبر کند

	private $backpay = false; //متغيري جهت مشخص کردن اينکه آيا محاسبه backpay صورت گيرد يا خير؟
	private $backpay_recurrence = 0; //در اين متغير مرتبه اجراي روال محاسبه حقوق در backpay مشخص مي شود
	
	//.................................................................................................
	private $writRowCount ; 
	private $subRowCount ; 
	private $pgRowCount ; 
	private $staffRowCount ; 
	private $insureRowCount ; 
	private $taxRowCount ; 
	private $taxHisRowCount ; 
	private $pensionRowCount ; 
	private $diffRowCount ; 
	private $MsalaryRowCount ; 
	private $writRow ; 
	private $writRowID = 0 ; 
	private $staffRow ; 
	private $staffRowID = 0 ; 
	private $PGLRow ; 
	private $PGLRowID = 0 ; 
	private $subRow ; 
	private $subRowID = 0 ; 
	private $insureRow ;  
	private $insureRowID = 0 ; 
	private $taxRow ; 	
	private $taxRowID = 0 ; 
	private $taxHisRow ; 
	private $taxHisRowID = 0 ; 
	private $pensionRow ; 
	private $pensionRowID = 0 ; 	
	private $diffRow ; 
	private $diffRowID = 0 ; 
	private $MsalaryRow ;
	private $MsalaryRowID = 0 ; 
		
	public function  __construct()
	{

	}

	/*پردازش مربوط به احکام که حلقه اصلي محاسبه است*/
	public function run()
	{ 		
		
		if(!$this->prologue()) 
			return false;			
		
		$this->monitor(8);
				
		$this->moveto_curStaff($this->staff_rs,'STF'); 


	
		/* پيماش recordset احکام*/
		while ($this->writRowID <= $this->writRowCount ) {
										
			$temp_array[$this->writRow['salary_item_type_id']] = $this->writRow;			
			$pre_writ_date = $this->writRow['execute_date'];

			//تهيه آرايه احکامي که در محاسبه حقوق شخص جاري استفاده مي شوند
			$this->staff_writs[$this->cur_staff_id][$this->writRow['writ_id']] =
								array('writ_id'=>$this->writRow['writ_id'],
									  'writ_ver'=>$this->writRow['writ_ver']);
			$this->writRow = $this->writ_sql_rs->fetch(); 
			$this->writRowID++ ;
									
			if($this->writRow['writ_id'] == $this->cur_writ_id && $this->writRow['staff_id'] == $this->cur_staff_id) {
				continue;
			}
			$this->cur_writ_id = $this->writRow['writ_id'];
			$cur_writ_date = $this->writRow['execute_date'];
						
			// محاسبه فاصله تاريج اجراي دو حکم
			if( DateModules::CompareDate($pre_writ_date, $this->month_start) == -1 ) {				
				$pre_writ_date = $this->month_start;
			}
			$use_month_end_date = 0;
			
			if( $this->writRow['staff_id'] != $this->cur_staff_id ) {
				//كاربرد اين متغير اين است كه مشخص كند آيا تاريخ انتها براي اين حكم آخر ماه است يا 
				//تاريخ شروع حكم بعد. اگر تاريخ آخر ماه باشد بهتفاضل تاريخها يكي اضافه مي شود
				//در غير اين صورت خير
				$use_month_end_date = 1; 
				if($this->staffRow['person_type'] == HR_WORKER || $this->staffRow['person_type'] == HR_CONTRACT  ) {
					$cur_writ_date = $this->month_end;
				}
				else {					 					
					$cur_writ_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->__MONTH."/30") ;										
				}
			}
			
			$between_length = round(DateModules::GDateMinusGDate($cur_writ_date,$pre_writ_date)) + $use_month_end_date;
			
			
		
			
			$main_time_slice = (  $between_length / $this->__MONTH_LENGTH) * ($this->cur_work_sheet / $this->__MONTH_LENGTH);
						
			//افزون اقلام يک حکم به اقلام حقوقي
			reset($temp_array);
			$this->last_writ_sum_retired_include = 0; // مقداردهي اوليه
			
			
			while( list($key,$fields) = each($temp_array) ) {
				if($key == null || $fields['pay_value'] <= 0) { // اگر حکم قلم حقوقي نداشته باشد و يا مبلغ قلم کوچکتر مساوي صفر باشد					
					continue;
				}
								
				//در صورتي كه اين قلم بايستي تحت تاثير طول ماه نسبت به مبلغ در حكم قرار گيرد
				
				if($fields['month_length_effect']) {		
				
					$time_slice = $main_time_slice * $this->__MONTH_LENGTH / WRIT_BASE_MONTH_LENGTH;
		
			
					if( $this->staffRow['person_type'] == HR_CONTRACT ) 
					{
							$computed_time_slice = $main_time_slice;
					}
					else 
					{
						$computed_time_slice = $time_slice ; 
					}
					
				} else {
					$time_slice = $main_time_slice;
					$computed_time_slice = $main_time_slice; 
				}
				
				/************محاسبه اقلام مربوط به حق الجلسه*************/
				$totalHours = 0 ; 
				$query = "select  st.staff_id , salary_item_type_id ,
				sum(SUBSTRING_INDEX(TotalHour,':',1) * 1 ) TotalHour , 
                sum(SUBSTRING_INDEX(TotalHour, ':', -1) * 1 )  TotalMin

						from HRM_sessions s
									 inner join HRM_persons p on s.PersonID = p.RefPersonID
								 inner join HRM_staff st on p.PersonID = st.PersonID

						where st.staff_id = ". $this->cur_staff_id." and  
							  SessionDate >= '".$this->month_start."' and 
							  SessionDate <= '".$this->month_end."' and 
						      salary_item_type_id = ".$fields['salary_item_type_id']."

						group by staff_id ,salary_item_type_id " ; 
				
				$resS = PdoDataAccess::runquery($query) ; 
		
	if( $fields['SessionItem'] ==  1 && !empty($resS[0]['TotalHour']) ) 
    {
                        $fields['param2'] = $resS[0]['TotalHour'] .':'.$resS[0]['TotalMin'] ;//$time_slice ; 					

    }
            
                if(isset($resS[0]['TotalMin']) && $resS[0]['TotalMin'] > 0 ){
                 $resS[0]['TotalHour'] += round(($resS[0]['TotalMin']/60),2) ;
                                }
                                
			
				if( $fields['SessionItem'] ==  1 /*$fields['salary_item_type_id'] == 13 || 
					$fields['salary_item_type_id'] == 14 || 
					$fields['salary_item_type_id'] == 15 || 
					$fields['salary_item_type_id'] == 16*/ ) 
				{
				 
				 
					$time_slice = ( !empty($resS[0]['TotalHour']) ?  $resS[0]['TotalHour'] : 0  )   ; 
				/*	$fields['param2'] = $resS[0]['TotalHour'] .':'.$resS[0]['TotalMin'] ; */ 				
					
				}
			
				
				/************************/
				if( isset($this->payment_items[$key]) ) { // اگر قبلا اين قلم در آرايه اقلام حقوقي وجود دارد
					$this->payment_items[$key]['pay_value'] += $fields['pay_value'] * $time_slice;					
					$this->payment_items[$key]['param4'] += $time_slice;
				    $this->payment_items[$key]['param7'] +=	$between_length ; 
				}
				else {
				    
				 //   echo   $fields['pay_value'] .'****'. $time_slice ; 
					$this->payment_items[$key] = array(
					'pay_year' => $this->__YEAR,
					'pay_month' => $this->__MONTH,
					'staff_id' => $fields['staff_id'],
					'salary_item_type_id' => $fields['salary_item_type_id'],
					'pay_value' => $fields['pay_value'] * $time_slice,					
					'get_value' => 0,
					'param1' => "'".$fields['param1']."'",
					'param2' => "'".$fields['param2']."'",
					'param3' => "'".$fields['param3']."'",
					'param4' => $computed_time_slice ,	
					'param7' =>	$between_length ,  
					'payment_type' => NORMAL );
				}
			
				//محاسبه مجموع حقوق مشول بازنشستگي در آخرين حکم ماه جاري
				$this->add_to_last_writ_sum_retired_include($fields,$key,$fields['pay_value']);

				$this->update_sums($fields,$fields['pay_value'] * $time_slice);
				 
			}

				
			$temp_array = array();
					
			if( $this->writRow['staff_id'] == $this->cur_staff_id ) { //حکم بعدي متعلق به همين شخص است
				continue;
			}
				
			$success_check = $this->control();


				
			if( count($this->payment_items) == 0) { //احکام فرد هيچ قلم حقوقي نداشته اند		
					
				$this->initForNextStaff();
				continue;
			}
	
	 		//شرح وضعيت : در اين نقطه بايستي تمام اقلام حکمي cur_staff در payment_items قرار گرفته باشد
	$this->process_subtract();

			//شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به وام و کسورو مزاياي ثابت cur_staff محاسبه شده باشد

	$this->process_pay_get_lists();
	
	
			// شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به کشيک ، حق التدريس ، ماموريت ، اضافه
			//کار و کسور و مزاياي موردي فرد cur_staff محاسبه و دز payment_items قرار گرفته باشند
			
				//فراخواني تابع محاسبه بيمه تامين اجتماعي
			$this->process_insure();
	
		
				$this->sum_tax_include = $this->sum_tax_include - ((isset($this->payment_items[SIT_STAFF_REMEDY_SERVICES_INSURE]['get_value']) ? $this->payment_items[SIT_STAFF_REMEDY_SERVICES_INSURE]['get_value'] : 0 ) +
										 (isset($this->payment_items[SIT_PROFESSOR_REMEDY_SERVICES_INSURE]['get_value']) ? $this->payment_items[SIT_PROFESSOR_REMEDY_SERVICES_INSURE]['get_value'] : 0 ) + 
										 (isset($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value']) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value'] : 0 ) +
										 (isset($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value']) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value'] : 0 ) + 
										 (isset($this->payment_items[9994]['get_value']) ? $this->payment_items[9994]['get_value'] : 0 ) + 
										 (isset($this->payment_items[10149]['get_value']) ? $this->payment_items[10149]['get_value'] : 0 ) + 
										 (isset($this->payment_items[IRAN_INSURE]['get_value']) ? $this->payment_items[IRAN_INSURE]['get_value'] : 0 ) +
										 (isset($this->payment_items[9964]['get_value']) ? $this->payment_items[9964]['get_value'] : 0 ) + 
										 (isset($this->payment_items[9998]['get_value']) ? $this->payment_items[9998]['get_value'] : 0) )  ; 
			
				
			//پردازش مربوط به ماليات
			if($this->__CALC_NORMALIZE_TAX == 1) {		
					
				$this->process_tax_normalize(); 
			}else{			
				$this->process_tax(); 
			}			
				
			//فراخواني تابع رير جهت مواردي غير از موارد هميشگي است که معمولا بنا بر نياز مشتري بايد نوشته شوند
			$this->process_custom();

			//افزودن مبالغ difference در صورت محاسبه از طريق backpay
			$this->add_difference();  
		
			//ثبت حقوق محاسبه شده براي staff جاري در فايل
			$this->save_to_DataBase();			
	
			//مقداردهي مجدد متغيرها براي محاسبه حقوق staff بعدي
			$this->initForNextStaff();
			
			
		} //end of writ while
 
		$this->epilogue(); 						
		$this->unregister_run();  
		$this->statistics();
	
		return true;
	}
	
	//................................ محاسبات غیر حکمی ............................
	// پردازش مربوط به وام و کسور , مزاياي ثابت
	
	private function process_subtract() {
		
	
		$this->moveto_curStaff($this->subtracts_rs,'SUB');		

		while ($this->subRowID  <=  $this->subRowCount && $this->subRow['staff_id'] == $this->cur_staff_id) {

			if( !$this->validate_salary_item_id($this->subRow['validity_start_date'], $this->subRow['validity_end_date']) ) {
				
				$this->subRow = $this->subtracts_rs->fetch(); 
				$this->subRowID++ ; 				
				continue;
			}

			$key = $this->subRow['salary_item_type_id']; //اين متغير صرفا جهت افزايش خوانايي کد تعريف شده است
			$param1 = null;

			if($this->subRow['subtract_type'] == FIX_BENEFIT) {
			    
			    $entry_title_full = 'pay_value';
				$entry_title_empty = 'get_value';
				
				if(DateModules::CompareDate($this->subRow['start_date'],$this->month_start) == 1) {					
					$s_date = $this->subRow['start_date'];
				}
				else {
					$s_date = $this->month_start;
				}

				if(!$this->subRow['end_date'] || $this->subRow['end_date'] == '0000-00-00' || DateModules::CompareDate($this->subRow['end_date'],$this->month_end) != -1) {					
					$e_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->__MONTH."/30") ; 					
				}
				else {
					$e_date = $this->subRow['end_date'];
				}
				
				$distance = round(DateModules::GDateMinusGDate($e_date,$s_date) + 1);
				if($distance < 0) {
					$this->subRow = $this->subtracts_rs->fetch(); 
					$this->subRowID++ ; 				
					continue;
				}
				if($distance > $this->cur_work_sheet) {
					$distance = $this->cur_work_sheet;
				}
			//	$this->subRow['get_value'] *= ($distance / $this->__MONTH_LENGTH); //ضرب مزاياي ثابت در کارکرد ماهانه
			}
			else {				
				$entry_title_full = 'get_value';
				$entry_title_empty = 'pay_value';
			}
$Remainder = 0 ; 
			if( $this->subRow['subtract_type'] == LOAN )  {
				$multiply = -1;
				$param1 = "'LOAN'";
$Remainder = $this->subRow['remainder'] ; 
			}
			else if($this->subRow['subtract_type'] == FIX_FRACTION ) {
				$multiply = 1;
				$param1 = "'FIX_FRACTION'";
$Remainder = $this->subRow['receipt'] ; 
			}
			else {
			  
			    $multiply = 0; 
			    
			    
			}

			$temp_array = array(
			'pay_year' => $this->__YEAR,
			'pay_month' => $this->__MONTH,
			'staff_id' => $this->cur_staff_id,
			'salary_item_type_id' => $key,
			$entry_title_full => $this->subRow['get_value'],
			$entry_title_empty => 0,
			'param1' => $param1,
			'param2' => $this->subRow['subtract_id'],
			'param3' => ( ($key == 11) ? $this->subRow['get_value'] : NULL ) ,
			'param4' => /*$this->subRow['remainder']*/ $Remainder + $this->subRow['get_value']* $multiply, 
			'cost_center_id' => 0,
			'payment_type' => NORMAL
			);
						 
			if( DateModules::CompareDate(DateModules::Now(), $this->month_end) == -1) { 
				$flow_date = DateModules::Now();
			}else {
				$flow_date = $this->month_end;
			}
			if(!$this->backpay) {
			/*	array_push($this->person_subtract_array,
				array('subtract_id' => $this->subRow['subtract_id'] ,
				'staff_id' => $this->cur_staff_id,
				'subtract_type' => $this->subRow['subtract_type'],
				'bank_id' => $this->subRow['bank_id'],
				'first_value' => $this->subRow['first_value'],
				'instalment' => $this->subRow['instalment'],
				'remainder' => $this->subRow['remainder'] + $this->subRow['get_value'] * $multiply,
				'start_date' => $this->subRow['start_date'],
				'end_date' => $this->subRow['end_date'],
				'comments' => $this->subRow['comments'],
				'salary_item_type_id' => $this->subRow['salary_item_type_id'],
				'account_no' => $this->subRow['account_no'],				
				'loan_no' => $this->subRow['loan_no'],
				'flow_date' => $flow_date,
				'flow_time' => DateModules::CurrentTime(),
				'subtract_status' => $this->subRow['subtract_status'],
				'contract_no' => $this->subRow['contract_no']
				)
				);				
				array_push($this->person_subtract_flow_array,
				array('subtract_id' => $this->subRow['subtract_id'] ,
				'row_no' => $this->subRow['subtract_flow_id'] + 1,
				'flow_type' => CALCULATE_FICHE_FLOW_TYPE,
				'flow_date' => $flow_date,
				'flow_time' => DateModules::CurrentTime(),
				'old_remainder' => $this->subRow['remainder'],
				'new_remainder' => $this->subRow['remainder'] + $this->subRow['get_value']* $multiply,
				'old_instalment' => $this->subRow['instalment'],
				'new_instalment' => $this->subRow['instalment'],
				'comments' => 'فيش حقوقي' 
				)
				);*/
			}

			if( isset($this->payment_items[$key]) ) {
				//قسط
				$this->payment_items[$key][$entry_title_full] += $temp_array[$entry_title_full];
				//مانده
				$this->payment_items[$key]['param4'] += $temp_array['param4'];
			}
			else {
				$this->payment_items[$key] = $temp_array;
			}

			$this->update_sums($this->subRow, $temp_array['pay_value']);

			$this->subRow = $this->subtracts_rs->fetch(); 
			$this->subRowID++ ;
		}
		
		if($this->__MONTH == 5) {
		
			//	print_r($this->payment_items);
			//	die();
			//echo PdoDataAccess::GetLatestQueryString(); 
		//die(); 
		
		
		} 

	}
	
		// پردازش کشيک و حق التدريس و اضافه کار و ...
	private function process_pay_get_lists() {
		//param5 list_id
		//param6 list_type
	 
		$this->moveto_curStaff($this->pay_get_list_rs,'PGL');
				
		while ($this->PGLRowID <= $this->pgRowCount  && $this->PGLRow['staff_id'] == $this->cur_staff_id) {

			
	
			$key = 12 ; //اين متغير صرفا جهت افزايش خوانايي کد تعريف شده است

			$func_name = 'compute_salary_item3_20' ; 			
			$temp_array = $this->$func_name();
			

			if( isset($this->payment_items[$key]) ) {
				if(in_array($key , array(12))){
        			if($this->staffRow['person_type'] == HR_WORKER ){
        				$this->payment_items[$key]['param2'] += $temp_array['param2'];
        			}
        			else if ($this->staffRow['person_type'] == HR_EMPLOYEE ){
        				$this->payment_items[$key]['param3'] += $temp_array['param3'];
        			}
        		}
				$this->payment_items[$key]['pay_value'] += $temp_array['pay_value'];
				$this->payment_items[$key]['get_value'] += $temp_array['get_value'];
			}
			else {
				$this->payment_items[$key] = $temp_array;
			}


			$this->update_sums($this->PGLRow, $temp_array['pay_value']);
					
			$this->extra_pay_value = $temp_array['pay_value'] ; 
			
			//.................................حق ماموریت.....................
		
			$PersonID = $this->PGLRow['RefPersonID'] ; 
			
			$resAtt = ATN_traffic::Compute($this->month_start, $this->month_end, $PersonID) ; 			
									
			if( $resAtt['DailyMission'] > 0 ) 
			{
				$key = 24 ; 
				$func_name = 'compute_salary_item3_21' ; 			
				$temp_array = $this->$func_name($resAtt['DailyMission']);
			
				if( isset($this->payment_items[$key]) ) {
					if(in_array($key , array(24))){
						if($this->staffRow['person_type'] == HR_WORKER ){
							$this->payment_items[$key]['param2'] += $temp_array['param2'];
						}        			
					}
					$this->payment_items[$key]['pay_value'] += $temp_array['pay_value'];
					$this->payment_items[$key]['get_value'] += $temp_array['get_value'];
				}
				else {
					$this->payment_items[$key] = $temp_array;
				}
				
				//$this->sum_tax_include += $temp_array['pay_value'] ;		
			
			}
			
			
			$this->PGLRow = $this->pay_get_list_rs->fetch(); 
			$this->PGLRowID++ ;
		}
		
		
	}
	
		/*پردازش مربوط به بيمه تامين اجتماعي*/
	private function process_insure() {
		//param1 : مجموع مزاياي شامل بيمه
		//param2 : سهم کارفرما
		//param3 : بيمه بيکاري
		
		$key = $this->get_insure_salary_item_id();

		if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
			return ;
		}
		
		/* بيمه تامين اجتماعي از هر فردي که در شرط زير صدق نمي کند کم خواهد شد*/
		if ( $this->staffRow['insure_include'] != 1  ) {
			return;
		}
		//.......... برای کارکنان روز مزد بیمه ای که بیش از 25 سال سابقه کار دارند به اضافه کار آنها نیز بایستی بیمه تعلق بگیرد ..................
		//..............  به کلیه کارکنان تغییر پیدا کرد به جز هیئت علمی............
		if($this->staffRow['person_type'] != HR_PROFESSOR  && $this->staffRow['Over25'] == 1  && $this->extra_pay_value != 0 && 
				 ( ($this->__YEAR == 1392 && $this->__MONTH >= 8) || $this->__YEAR > 1392  ) )
		{
			
			$this->sum_insure_include += $this->extra_pay_value ;
		}			
		//......................................................................................................................................
		
		$param1 = $this->sum_insure_include;
		/*در صورتي که مجمع حقوق م مزاياي مشمول بيمه از حداکثر دستمزد ماهانه بيشتر شود همان حداکثر در نظر گرفته مي شود*/
		if($param1 > $this->salary_params[SPT_MAX_DAILY_SALARY_INSURE_INCLUDE][PERSON_TYPE_ALL]['value'] * $this->__MONTH_LENGTH) {
			$param1 = $this->salary_params[SPT_MAX_DAILY_SALARY_INSURE_INCLUDE][PERSON_TYPE_ALL]['value'] * $this->__MONTH_LENGTH;
		}

		//نرخ بيمه سهم کارفرما
		$employer_insure_value = $this->salary_params[SPT_SOCIAL_SUPPLY_INSURE_EMPLOYER_VALUE][PERSON_TYPE_ALL]['value'];
		//نرخ بيمه بيکاري
		$unemployment_insure_value = $this->salary_params[SPT_UNEMPLOYMENT_INSURANCE_VALUE][PERSON_TYPE_ALL]['value'];
		//نرخ بيمه سهم شخص
		$person_insure_value = $this->salary_params[SPT_SOCIAL_SUPPLY_INSURE_PERSON_VALUE][PERSON_TYPE_ALL]['value'];

		$param2 = round($employer_insure_value * $param1);

		$param3 = round($unemployment_insure_value * $param1);

		if( $this->__YEAR == 1389 && $this->__MONTH > 8  && 
		    ( $this->staffRow['emp_state'] == 1 || 
		      $this->staffRow['emp_state'] == 10 || 
		      $this->staffRow['emp_state'] == 2 )) 
		    {
		    	$param3 = 0 ; 
		    }
		  
		$value = round($person_insure_value * $param1);

		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'pay_value' => 0,
		'get_value' => $value,
		'param1' => $param1,
		'param2' => $param2,
		'param3' => $param3,		
		'payment_type' => NORMAL );
	}
	
	//پردازش  مربوط به بيمه خدمات درماني
	private function process_service_insure() {
	
		if ( $this->staffRow['service_include'] != 1 ) {
				return;
			}
			
		$this->moveto_curStaff($this->service_insure_rs,'Insure');
	
			// شرط افزوده شد که چنانچه فرد در لیست نبود مقدار را صفر بگذارد ---------
		if( $this->cur_staff_id == $this->insureRow['staff_id'])
		{ 
					
	
			if( !$this->validate_salary_item_id($this->insureRow['validity_start_date'], $this->insureRow['validity_end_date']) ) {
				return ;
			}
	
			$key = $this->get_service_insure_salary_item_id();
	
			if($key == null) { //به اين نوع شخص بيمه خدمات درماني تعلق نمي گيرد
				return ;
			}
			
			$insureCoef = ( $this->__YEAR > 1392  )  ? 2 : 1.65 ; 
			
			if($this->insureRow['own_normal'] > 0) {
							
				if( $this->__YEAR <= 1390 && $this->sum_retired_include > 6606000 )
					$Rtv = 6606000 ;
				else if ( $this->__YEAR > 1390 && $this->__YEAR < 1392 && $this->sum_retired_include > 7794000  ) 	
					$Rtv = 7794000 ;
				else if( $this->__YEAR > 1391 &&   $this->__YEAR < 1393 && $this->sum_retired_include > 9800000 )
					$Rtv = 9800000 ;				
				else if( $this->__YEAR > 1392 && $this->sum_retired_include > 12178200 ) 
					$Rtv = 12178200 ;				
				
				else $Rtv = $this->sum_retired_include ; 
					
			
			   $normalvalue = ($Rtv * $insureCoef ) / 100 ;			   
			     
			}
			else    {
			   $normalvalue =0 ; 
			   
			}
			
			//سهم شخص
			$value = round($normalvalue + //نرمال						 
					 	   ($this->insureRow['extra1'] * $this->salary_params[SPT_FIRST_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value']) + //مازاد1
					       ($this->insureRow['extra2'] * $this->salary_params[SPT_SECOND_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'])); //مازاد2
		
					       
			$org_value = round($normalvalue);	

		}
		else {
				
			  return; // نیازی به ذخیره نمی باشد.
			  
			  $value = 0 ; 
			  $org_value = 0 ;
		}
				
		//انتصاب بيمه خدمات درماني به payment_items
		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'pay_value' => 0,
		'get_value' => $value,
		'param1' =>$this->insureRow['normal'],
		'param2' =>$this->insureRow['extra1'],
		'param3' =>$this->insureRow['extra2'],
		'param4' =>( $this->__YEAR < 1391 ) ? $this->salary_params[SPT_NORMAL_INSURE_VALUE][PERSON_TYPE_ALL]['value'] : 0  ,
		'param5' =>$this->salary_params[SPT_FIRST_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'],
		'param6' =>$this->salary_params[SPT_SECOND_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'],
		'param7' =>$org_value,
		'param8' =>$this->insureRow['normal2'],
		'param9' => 0 ,		
		'cost_center_id' => 0,
		'payment_type' => NORMAL );
	}
	
		//پردازش ماليات تعديل شده
	private function process_tax_normalize() {
		
		$key = $this->get_tax_salary_item_id();
		
		if($this->backpay) {
		
		
			/*بدليل اينكه در هنگام محاسبه backpay حقوق مشمول ماليات فرد تغيير مي كند لذا اين مبلغ را 
			در جدول back_payment_items درج مي كنيم تا در محاسبه مبلغ مشمول ماليات در سال
			تاثير بگذارد*/
			$this->payment_items[$key] = array(
			'pay_year' => $this->__YEAR,
			'pay_month' => $this->__MONTH,
			'staff_id' => $this->cur_staff_id,
			'salary_item_type_id' => $key,
			'get_value' => 0, 
			'pay_value' => 0,
			'diff_get_value' => 1,//براي اينكه اين قلم هم در پايگاه داده درج شود
			'cost_center_id' => 0,
			'payment_type' => NORMAL,
			'param1' => $this->sum_tax_include);
			return true;
		}
		
		/*اين فرد مشمول ماليات نمي باشد*/
		if(empty($this->staffRow['tax_include'])) {
			return ;
		}
			
		/*قلم حقوقي ماليات معتبر نبوده است*/
		if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
			return ;
		}
		$this->moveto_curStaff($this->tax_rs,'TAX');
		$this->moveto_curStaff($this->tax_history_rs,'TAXHIS');
		/* در اين قسمت فرض شده است که تاريخ تعديل ماليات هموراه از ابتداي سال است*/
//..........................
		
		for($t=1;$t <= $this->__MONTH ;$t++){
			
			if($t >= 1 && $t < 7 )
			{
				$StartDate = DateModules::shamsi_to_miladi($this->__YEAR.'/'.$t.'/1','-') ; 
				$EndDate = 	DateModules::shamsi_to_miladi($this->__YEAR.'/'.$t.'/31','-'); 
			}
			elseif($t >= 7 && $t <= 12 )	{		
				
				$StartDate = DateModules::shamsi_to_miladi($this->__YEAR.'/'.$t.'/1','-') ; 
				$EndDate = 	DateModules::shamsi_to_miladi($this->__YEAR.'/'.$t.'/30','-') ; 
			}
				$PersonID = $this->taxHisRow['RefPersonID'] ; 
							 
				$resAtt = ATN_traffic::Compute($StartDate, $EndDate, $PersonID) ; 
			
				if( $resAtt['attend'] > 0 )
				{
					$this->__START_NORMALIZE_TAX_MONTH =  $t ;                     
					break ;					 
				}
			
			}
            
            if($this->staffRow['emp_state'] == 4 )
            {
                $this->__START_NORMALIZE_TAX_MONTH = $this->__MONTH ; 
            }
//..........................
	/*	$year_avg_tax_include = ( (($this->cur_staff_id == $this->taxRow['staff_id']) ? $this->taxRow['sum_tax_include'] : 0 ) + 
		$this->sum_tax_include + $this->taxHisRow['payed_tax_value'] - ( empty($this->payment_items[7]['get_value']) ? 0 : (($this->payment_items[7]['get_value'] * 2) / 7 ) ) ) / ($this->__MONTH - $this->__START_NORMALIZE_TAX_MONTH + 1); */
		
	$year_avg_tax_include =                      ( (($this->cur_staff_id == $this->taxRow['staff_id']) ? $this->taxRow['sum_tax_include'] : 0 ) + 
                                $this->sum_tax_include + $this->taxHisRow['payed_tax_value'] - 
                              ( empty($this->payment_items[7]['get_value']) ? (0 + $this->taxRow['sum_BimeMoaf'] ) : (($this->payment_items[7]['get_value'] * 2) / 7 ) + $this->taxRow['sum_BimeMoaf'] ) ) / ($this->__MONTH - $this->__START_NORMALIZE_TAX_MONTH + 1);
    
		$sum_normalized_tax = $tax_table_type_id = 0; //متغيري جهت نگهداري ماليات تعديل شده براي cur_staff در تمام طول سال
		
	 
/*echo $this->taxRow['sum_tax_include'] .'--sti---'. $this->sum_tax_include.'---stiii---'.  	 $this->taxHisRow['payed_tax_value'].'--ptv--'.
  ($this->__MONTH - $this->__START_NORMALIZE_TAX_MONTH + 1).'---m---'.$year_avg_tax_include ;  die(); */ 

		reset($this->tax_tables);

		for($m = $this->__START_NORMALIZE_TAX_MONTH; $m <= $this->__MONTH; $m++ ) {
            
//echo $m.'----ff--' ;
			$begin_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/1") ; 			
			$end_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/".DateModules::DaysOfMonth($this->__YEAR,$m)) ;	

			while ($this->taxHisRowID <= $this->taxHisRowCount && $this->taxHisRow['staff_id'] == $this->cur_staff_id) {
				
				if( ( $this->taxHisRow['end_date'] != null  && $this->taxHisRow['end_date'] != '0000-00-00' ) && 
						DateModules::CompareDate($this->taxHisRow['end_date'],$begin_month_date) == -1 ) { 					
					$this->taxHisRow = $this->tax_history_rs->fetch(); 
					$this->taxHisRowID++ ; 					
					continue;
				}
				if(DateModules::CompareDate($this->taxHisRow['start_date'],$end_month_date) == 1 ) { 						
					break;
				}
				
				$tax_table_type_id = $this->taxHisRow['tax_table_type_id'];
				break;
			}
			
		
			if(!isset($tax_table_type_id) ||  $tax_table_type_id == NULL) 
			{
				continue ; 
			}
			if(! key_exists($tax_table_type_id, $this->tax_tables)) {
				return ;				
			}
				
			foreach( $this->tax_tables[ $tax_table_type_id ] as $tax_table_row ) {
				$pay_mid_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/1") ;				
				if( DateModules::CompareDate($pay_mid_month_date, $tax_table_row['from_date']) != -1 && 
					DateModules::CompareDate($pay_mid_month_date,$tax_table_row['to_date']) != 1 ) { 
	//	echo $year_avg_tax_include .'---'.$tax_table_row['from_value'] .'****'. $year_avg_tax_include ."****". $tax_table_row['to_value'].'---<br>' ; 
					if( $year_avg_tax_include >= $tax_table_row['from_value'] && $year_avg_tax_include <= $tax_table_row['to_value'] ) {
						$sum_normalized_tax += ( $year_avg_tax_include - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];						
//echo  $this->__MONTH.'***'.$year_avg_tax_include .'***'. $tax_table_row['from_value'] .'-----'. $tax_table_row['coeficient'].'---<br>';
					}
					else if($year_avg_tax_include > $tax_table_row['to_value']){
						$sum_normalized_tax += ( $tax_table_row['to_value'] - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];											
					}
				}
				
			}
		}
	
//echo $sum_normalized_tax .'****'.$this->taxRow['sum_tax'];
//die();
	
	//	$this->__START_NORMALIZE_TAX_MONTH =  1 ;
		
		$normalized_tax = $sum_normalized_tax - $this->taxRow['sum_tax'];
		if($normalized_tax < 0)
		$normalized_tax = 0;
		 
		//انتصاب ماليات تعديل شده به  payment_items
		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'get_value' => $normalized_tax,
		'pay_value' => 0,
		'cost_center_id' => 0 ,
		'payment_type' => NORMAL );

		$this->payment_items[$key]['param1'] = $this->sum_tax_include; //مجموع حقوق مشمول ماليات در ماه جاري
		$this->payment_items[$key]['param2'] = $sum_normalized_tax; //مالياتي که از ابتدا تا کنون بايد پرداخت مي شده است
		$this->payment_items[$key]['param3'] = $this->taxRow['sum_tax'] + $normalized_tax; // مالياتي که از ابتدا تا کنون پرداخت شده است
		$this->payment_items[$key]['param4'] = 2; //اگر محاسبه بدون تعديل انجام شده است 1 و  اگر با تعديل انجام گرديده 2 قرار مي دهيم
		$this->payment_items[$key]['param5'] = $tax_table_type_id; //آخرين جدول مالياتي که در محاسبه ماليات استفاده شده است گذاشته مي شود
		$this->payment_items[$key]['param6'] = (empty($this->payment_items[7]['get_value']) ? 0 : (($this->payment_items[7]['get_value'] * 2) / 7 ) );
	}
	
	//پردازش ماليات معمولي
	private function process_tax() {
		if($this->backpay) {
			return true;
		}
		/*اين فرد مشمول ماليات نمي باشد*/
		if(empty($this->staffRow['tax_include'])) {
			return ;
		}
		$key = $this->get_tax_salary_item_id();
		
		if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
			return ;
		}
		$this->moveto_curStaff($this->tax_rs,'TAX');
		$this->moveto_curStaff($this->tax_history_rs,'TAXHIS');
		$tax_table_type_id = $this->taxHisRow['tax_table_type_id'];
		if(! key_exists($tax_table_type_id, $this->tax_tables)) {
			return ;				
		}
		
		$this->sum_tax_include -= ( empty($this->payment_items[7]['get_value']) ? 0 : (($this->payment_items[7]['get_value'] * 2) / 7 ) ) ;	
		
		$tax = 0;  //متغيري جهت نگهداري ماليات
		reset($this->tax_tables);	
		foreach( $this->tax_tables[$tax_table_type_id] as $tax_table_row ) {
			
			$pay_mid_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->__MONTH."/15") ; 			
					
			if(DateModules::CompareDate($pay_mid_month_date, $tax_table_row['from_date']) != -1 && 
			   DateModules::CompareDate($pay_mid_month_date,$tax_table_row['to_date']) != 1 ) { 
				if( $this->sum_tax_include >= $tax_table_row['from_value'] && $this->sum_tax_include <= $tax_table_row['to_value'] ) {
					$tax += ( $this->sum_tax_include - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];
				}
				else if($this->sum_tax_include > $tax_table_row['to_value']){
					$tax += ( $tax_table_row['to_value'] - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];
				}
			}
				
			
		}
	
		//انتصاب ماليات تعديل شده به  payment_items
		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'get_value' => $tax,
		'pay_value' => 0,
		'cost_center_id' => 0,
		'payment_type' => NORMAL );

		$this->payment_items[$key]['param1'] = $this->sum_tax_include + ( empty($this->payment_items[7]['get_value']) ? 0 : (($this->payment_items[7]['get_value'] * 2) / 7 ) ) ;	 ; //مجموع حقوق مشمول ماليات در اين ماه
		$this->payment_items[$key]['param3'] = $this->taxRow['sum_tax'] + $tax; //مالياتي که از ابتدا تا کنون رداخت شده است
		$this->payment_items[$key]['param4'] = 1; //اگر محاسبه بدون تعديل انجام شده است 1 و  اگر با تعديل انجام گرديده 2 قرار مي دهيم
		$this->payment_items[$key]['param5'] = $tax_table_type_id; //آخرين جدول مالياتي که در محاسبه ماليات استفاده شده است گذاشته مي شود
		/* 2.7 مبلغ بیمه تامین اجتماعی*/
		$this->payment_items[$key]['param6'] = (empty($this->payment_items[7]['get_value']) ? 0 : (($this->payment_items[7]['get_value'] * 2) / 7 ) ); ; 
	}
	
	
	//پردازش مربوط به مقرري ماه اول
	private function process_pension() {
		//مقرري از كساني كه مشمول مقرري هستند كم خواهد شد
		if( $this->staffRow['pension_include'] == 0 ) {
			return;
		}

		$key = $this->get_pension_salary_item_id();
		if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
			return ;
		}
		
		$this->moveto_curStaff($this->pension_rs,'PENSION');

        if( ($this->__YEAR <='1390' && $this->__MONTH < '7') || $this->staffRow['person_type']  ==  HR_PROFESSOR || $this->staffRow['person_type']  == HR_WORKER  ) {
                //در صورتي كه ماكزيمم حقوق مشمول مقرري در طي محاسبه backpay كوچكتر از
                //مقدار ذخيره شده در پايگاه داده باشد مقدار آن تغير خواهد كرد
                
			/*	if($this->__MONTH == 1 && $_SESSION['UserID'] == 'bmahdipour') 
				{
					echo $this->max_sum_pension."--maxSumPen--".$this->pensionRow['max_sum_pension']."---pardakht---".$this->staffRow['sum_paied_pension'].'***'.$this->last_writ_sum_retired_include."###" ; 
						die();
				}
			*/	
				if($this->max_sum_pension < $this->pensionRow['max_sum_pension']) {
                    $this->max_sum_pension = $this->pensionRow['max_sum_pension'];
                } 
								
                //مجموع مقرري پرداخت شده تا بدو استخدام
                if($this->staffRow['sum_paied_pension'] > $this->max_sum_pension) {
                    $this->max_sum_pension = $this->staffRow['sum_paied_pension'];
                }
				
                $value = round($this->last_writ_sum_retired_include  - $this->max_sum_pension) * $this->get_retired_coef() ;
                if($value < 0) {
                    $value = 0;
                }
                //هنگامي که از طريق backpay محاسبه حقوق انجام مي شود بايد مقادير ماکزيمم با توجه به احکام جديد محاسبه و نگهداري شود
                if($this->last_writ_sum_retired_include > $this->max_sum_pension) {
                    $this->max_sum_pension = $this->last_writ_sum_retired_include;
                }
        }
        else {
			
		if($this->max_sum_pension < $this->pensionRow['max_sum_pension'] ) {
                   $this->max_sum_pension = $this->pensionRow['max_sum_pension'];
                }
		
				  //مجموع مقرري پرداخت شده تا بدو استخدام
                if($this->staffRow['sum_paied_pension'] > $this->max_sum_pension) {
                   $this->max_sum_pension = $this->staffRow['sum_paied_pension'];
                }

		$value = round($this->last_writ_sum_retired_include  - $this->max_sum_pension) * $this->get_retired_coef() ;
		if($value < 0) {
		    $value = 0;
		}

         if($this->last_writ_sum_retired_include > $this->max_sum_pension) {
                    $this->max_sum_pension = $this->last_writ_sum_retired_include;
                }
          
        }
        
		if( isset($this->payment_items[$key]) ) {// اگر قبلا اين قلم در آرايه اقلام حقوقي وجود دارد
			$this->payment_items[$key]['get_value'] += $value;
		}
		else {
			$this->payment_items[$key] = array(
			'pay_year' => $this->__YEAR,
			'pay_month' => $this->__MONTH,
			'staff_id' => $this->cur_staff_id,
			'salary_item_type_id' => $key,
			'get_value' => $value,
			'pay_value' => 0,
			'cost_center_id' => 0,
			'payment_type' => NORMAL );
		}
	}
	
	//پردازش مربوط به بازنشتگي
	private function process_retire() {
		//param1 : نرخ بازنشستگي
		//param2 : حقوق و مزاياي مستمر
		//param3 : بازنشستگي - سهم کارفرما
		//param5 : ضريب بازنشستگي سهم سازمان
		
		$key = $this->get_retired_salary_item_id();
		
		//اگر كسي مشمول بازنشستگي نباشد نه سهم فرد و نه سهم سازمان خواهد داشت
		if( $this->staffRow['retired_include'] != 0 ) {
	
			if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
				return ;
			}
					
			if($this->staffRow['worktime_type'] == HALF_TIME) {
				$this->sum_retired_include *= 2;
			}
			
			if($this->staffRow['worktime_type'] == QUARTER_TIME) {
				$this->sum_retired_include = $this->sum_retired_include * ( 4 / 3 ) ;
			}
			//مبلغ مقرري و بدهي مقرري از  مجموع مشمول بازنشستگي کم مي شود.
			$this->sum_retired_include -= $this->payment_items[$this->get_pension_salary_item_id()]['get_value']  ;
			
			//فعلا براي اينکه نرخ بازنشتگي براي همه يکي است اين قسمت از يک ثابت استفاده شده است
			$param1 = $this->salary_params[SPT_RETIREMENT_VALUE][PERSON_TYPE_ALL]['value'];
			$param5 = $this->salary_params[SPT_RETIREMENT_EMPLOYER_VALUE][PERSON_TYPE_ALL]['value'];
			$param3 = $this->sum_retired_include * $param5;
			
			$value = round($param1 * $this->sum_retired_include * $this->get_retired_coef() * $this->staffRow['retired_include']);
			
			$param2 = $this->sum_retired_include;
		}
		else			
			return;
				
		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'pay_value' => 0,
		'get_value' => $value,
		'param1' => $param1,
		'param2' => $param2,
		'param3' => $param3,
		'param4' => $this->max_sum_pension,
		'param5' => $param5,
		'cost_center_id' => 0,
		'payment_type' => NORMAL );
	}

	
	/*اجراي متدهاي اضافي بنا بر نياز مشتري*/
	private function process_custom(){


return ; 
	
			//cur_staff جانباز است و بايد قلم برگشتي بيمه تامين اجتماعي براي او محاسبه شود
		if($this->staffRow['sacrifice'] > 0 || $this->staffRow['freedman'] > 0 || $this->staffRow['shohadachild'] > 0 ) {
			switch ($this->staffRow['person_type']) {
				case HR_PROFESSOR : $this->compute_salary_item1_25(); break;
				case HR_EMPLOYEE : $this->compute_salary_item2_29(); break;
				case HR_WORKER : $this->compute_salary_item3_30(); break;
				case HR_CONTRACT : $this->compute_salary_item3_30(); break; 
			}			
		}

/*if($_SESSION['UserID'] == 'jafarkhani' && $this->__MONTH == 11    ) 
	{
		echo $this->payment_items[524]['pay_value']."###" ; 
	}*/
		
		//cur_staff جانباز است و بايد مبلغ مقرري ، بيمه خدمات درماني ، بازنشستگي و بيمه تكميلي ايران به او برگشت داده شود
		if(($this->staffRow['sacrifice'] > 0 || $this->staffRow['freedman'] > 0 || $this->staffRow['shohadachild'] > 0 ) && 
		   ($this->staffRow['person_type'] == HR_PROFESSOR || $this->staffRow['person_type'] == HR_EMPLOYEE )) {
			if($this->staffRow['person_type'] == HR_PROFESSOR) {
				$temp_array = $this->compute_salary_item1_26(); //&
			}
			else if($this->staffRow['person_type'] == HR_EMPLOYEE ) {
				$temp_array = $this->compute_salary_item2_30(); // &
				//  اگر افراد قراردادی هم داشته باشند فقط قسمت بیمه این تابع را براش بگذاز
			}
			$key = $temp_array['salary_item_type_id'];
			
			
	/*if($_SESSION['UserID'] == 'jafarkhani' && $this->__MONTH == 11    ) 
	{
		echo $temp_array['pay_value']."@@@" ; die();
	}*/
			
			
			if( isset($this->payment_items[$key]) ) {// اگر قبلا اين قلم در آرايه اقلام حقوقي وجود دارد
				$this->payment_items[$key]['pay_value'] += $temp_array['pay_value'];
			}
			else {
				$this->payment_items[$key] = $temp_array;
			}
		}
			
	}
	
	/*تابع افزودن مبالغ differ حاصل از محاسبه backpay به آرايه payment_items*/
	private function add_difference() {
		if($this->backpay_recurrence == 0 || $this->backpay) {
			return false;
		}
		$result = false ;		
		
		
		$this->moveto_curStaff($this->diff_rs,'DIFF');
		while (!$this->diffRowID <= $this->diffRowCount && $this->diffRow['staff_id'] == $this->cur_staff_id) {
						
			$key = $this->diffRow['salary_item_type_id']; //اين خط فقط بدليل افزايش خوانايي اضافه شده است
			if($this->diffRow['get_value_diff'] || $this->diffRow['pay_value_diff'])
				$result = true ;
			if( $this->diffRow['get_value_diff'] <> 0 || $this->diffRow['pay_value_diff'] <> 0 )
			{
			
					if(is_array($this->payment_items) && key_exists($key,$this->payment_items)) {
						$this->payment_items[$key]['diff_get_value'] = $this->diffRow['get_value_diff'];
						$this->payment_items[$key]['diff_pay_value'] = $this->diffRow['pay_value_diff'];
						$this->payment_items[$key]['diff_param1'] = $this->diffRow['param1_diff'];
						$this->payment_items[$key]['diff_param2'] = $this->diffRow['param2_diff'];
						$this->payment_items[$key]['diff_param3'] = $this->diffRow['param3_diff'];
						$this->payment_items[$key]['diff_param4'] = $this->diffRow['param4_diff'];
						$this->payment_items[$key]['diff_param5'] = $this->diffRow['param5_diff'];
						$this->payment_items[$key]['diff_param6'] = $this->diffRow['param6_diff'];
						$this->payment_items[$key]['diff_param7'] = $this->diffRow['param7_diff'];
						$this->payment_items[$key]['diff_param8'] = $this->diffRow['param8_diff'];
						$this->payment_items[$key]['diff_param9'] = $this->diffRow['param9_diff'];
					}
					else {
						
							$this->payment_items[$key] = array(
							'pay_year' => $this->__YEAR,
							'pay_month' => $this->__MONTH,
							'staff_id' => $this->diffRow['staff_id'],
							'salary_item_type_id' => $key,
							'pay_value' => 0,
							'get_value' => 0,
							'diff_get_value' =>$this->diffRow['get_value_diff'],
							'diff_pay_value' =>$this->diffRow['pay_value_diff'],
							'diff_param1' =>$this->diffRow['param1_diff'],
							'diff_param2' =>$this->diffRow['param2_diff'],
							'diff_param3' =>$this->diffRow['param3_diff'],
							'diff_param4' =>$this->diffRow['param4_diff'],
							'diff_param5' =>$this->diffRow['param5_diff'],
							'diff_param6' =>$this->diffRow['param6_diff'],
							'diff_param7' =>$this->diffRow['param7_diff'],
							'diff_param8' =>$this->diffRow['param8_diff'],
							'diff_param9' =>$this->diffRow['param9_diff'],
							'cost_center_id' => 0,
							'payment_type' => NORMAL);
					}
					
					$this->payment_items[$key]['diff_value_coef'] = 1;
					$this->payment_items[$key]['diff_param1_coef'] = 1;
					$this->payment_items[$key]['diff_param2_coef'] = 1;
					$this->payment_items[$key]['diff_param3_coef'] = 1;
					$this->payment_items[$key]['diff_param4_coef'] = 1;
					$this->payment_items[$key]['diff_param5_coef'] = 1;
					$this->payment_items[$key]['diff_param6_coef'] = 1;
					$this->payment_items[$key]['diff_param7_coef'] = 1;
					$this->payment_items[$key]['diff_param8_coef'] = 1;
					$this->payment_items[$key]['diff_param9_coef'] = 1;
					
					//استثنا بنا بر خواسته دانشگاه : صرفا براي كسور بازنشستگي و مقرري منفي نمايش داده مي شود
					//استثنا2 : از تاريخ 12/2/86 مبالغ منفي مربوط به بيمه تكميلي و بيمه درماني نيز برگشت داده مي شوند
					$negative_array = array(SIT_PROFESSOR_RETIRED, SIT_STAFF_RETIRED, SIT_WORKER_RETIRED,
											STAFF_FIRST_MONTH_MOGHARARY, PROFESSOR_FIRST_MONTH_MOGHARARY,
											SIT_STAFF_REMEDY_SERVICES_INSURE, SIT_PROFESSOR_REMEDY_SERVICES_INSURE, IRAN_INSURE);
					if( $this->diffRow['effect_type'] == FRACTION && !in_array($key,$negative_array) ) {
						
						if($this->diffRow['get_value_diff'] < 0) {
							$this->payment_items[$key]['diff_value_coef'] = 0;
						}
						if($this->diffRow['param1_diff'] < 0) {
							$this->payment_items[$key]['diff_param1_coef'] = 0;
						}
						if($this->diffRow['param2_diff'] < 0) {
							$this->payment_items[$key]['diff_param2_coef'] = 0;
						}
						if($this->diffRow['param3_diff'] < 0) {
							$this->payment_items[$key]['diff_param3_coef'] = 0;
						}
						if($this->diffRow['param4_diff'] < 0) {
							$this->payment_items[$key]['diff_param4_coef'] = 0;
						}
						if($this->diffRow['param5_diff'] < 0) {
							$this->payment_items[$key]['diff_param5_coef'] = 0;
						}
						if($this->diffRow['param6_diff'] < 0) {
							$this->payment_items[$key]['diff_param6_coef'] = 0;
						}
						if($this->diffRow['param7_diff'] < 0) {
							$this->payment_items[$key]['diff_param7_coef'] = 0;
						}
						if($this->diffRow['param8_diff'] < 0) {
							$this->payment_items[$key]['diff_param8_coef'] = 0;
						}
						if($this->diffRow['param9_diff'] < 0) {
							$this->payment_items[$key]['diff_param9_coef'] = 0;
						}
					}
			
			}
			
			$this->diffRow = $this->diff_rs->fetch() ;
			$this->diffRowID++ ; 
					
		}
		
		return $result ;
	}
	
	/*درج اطلاعات جداول در پايگاه داده و در جداول back_payments , back_payment_items*/
	/*private function submit_back() {
		
		$file_path = HR_TemlDirPath;
		parent::runquery(' LOCK TABLES back_payment_items READ;') ; 
		parent::runquery(' LOCK TABLES back_payment_items WRITE;') ; 
		*/
		/*********************************************************************************************/
		/*parent::runquery(' ALTER TABLE back_payment_items DISABLE KEYS;') ; 
		parent::runquery('
                        LOAD DATA LOCAL INFILE \''.$file_path.'payment_items_file.txt\' INTO TABLE back_payment_items
                        FIELDS TERMINATED BY \',\'
                        (diff_get_value, diff_pay_value, pay_year, pay_month, staff_id,
                        salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8 , param9,
                        diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8 , diff_param9,
                        cost_center_id, payment_type,diff_value_coef,
                        diff_param1_coef,diff_param2_coef,diff_param3_coef,diff_param4_coef,diff_param5_coef,
                        diff_param6_coef,diff_param7_coef , diff_param8_coef , diff_param9_coef);
						') ; 
		parent::runquery('ALTER TABLE back_payment_items ENABLE KEYS;') ; */
		
		/*********************************************************************************************/
		//parent::runquery('UNLOCK TABLES;') ; 		
	// }*/
	
	/*اجراي query هاي لازم جهت ايجاد آرايه difference بين payment_items و back_payment_items*/
	private function exe_difference_sql() {
		//چون سال مالي مبتني بر سال شمسي فرض شده است سال شروع backpay با سال شروع حقوق يکي است
		
		$whr = "" ; 
									
		parent::runquery('TRUNCATE HRM_temp_done_payments');
		parent::runquery('                       
					    insert into HRM_temp_done_payments
                        SELECT pit.staff_id,
                               pit.salary_item_type_id,
                               sit.effect_type,
                               SUM(pit.get_value + pit.diff_get_value) sum_get_value_done,
                               SUM(pit.pay_value + pit.diff_pay_value) sum_pay_value_done,
                               SUM(CASE
                       WHEN (param1 <> 0 AND diff_param1 <> 0) THEN param1 + diff_param1
                       WHEN (param1 <> 0) THEN param1
                       WHEN (diff_param1 <> 0) THEN diff_param1
                       ELSE 0
                       END) sum_param1_done,
                               SUM(CASE
                       WHEN (param2 <> 0 AND diff_param2 <> 0) THEN param2 + diff_param2
                       WHEN (param2 <> 0) THEN param2
                       WHEN (diff_param2 <> 0) THEN diff_param2
                       ELSE 0
                       END) sum_param2_done,
                               SUM(CASE
                       WHEN (param3 <> 0 AND diff_param3 <> 0) THEN param3 + diff_param3
                       WHEN (param3 <> 0) THEN param3
                       WHEN (diff_param3 <> 0) THEN diff_param3
                       ELSE 0
                       END) sum_param3_done,
                               SUM(CASE
                       WHEN (param4 <> 0 AND diff_param4 <> 0) THEN param4 + diff_param4
                       WHEN (param4 <> 0) THEN param4
                       WHEN (diff_param4 <> 0) THEN diff_param4
                       ELSE 0
                       END) sum_param4_done,
                               SUM(CASE
                       WHEN (param5 <> 0 AND diff_param5 <> 0) THEN param5 + diff_param5
                       WHEN (param5 <> 0) THEN param5
                       WHEN (diff_param5 <> 0) THEN diff_param5
                       ELSE 0
                       END) sum_param5_done,
                               SUM(CASE
                       WHEN (param6 <> 0 AND diff_param6 <> 0) THEN param6 + diff_param6
                       WHEN (param6 <> 0) THEN param6
                       WHEN (diff_param6 <> 0) THEN diff_param6
                       ELSE 0
                       END) sum_param6_done,
                               SUM(CASE
                       WHEN (param7 <> 0 AND diff_param7 <> 0) THEN param7 + diff_param7
                       WHEN (param7 <> 0) THEN param7
                       WHEN (diff_param7 <> 0) THEN diff_param7
                       ELSE 0
                       END) sum_param7_done,
	                       SUM(CASE
                       WHEN (param8 <> 0 AND diff_param8 <> 0) THEN param8 + diff_param8
                       WHEN (param8 <> 0) THEN param8
                       WHEN (diff_param8 <> 0) THEN diff_param8
                       ELSE 0
                       END) sum_param8_done ,
	                       SUM(CASE
                       WHEN (param9 <> 0 AND diff_param9 <> 0) THEN param9 + diff_param9
                       WHEN (param9 <> 0) THEN param9
                       WHEN (diff_param9 <> 0) THEN diff_param9
                       ELSE 0
                       END) sum_param9_done

                       FROM  HRM_limit_staff ls
                                  INNER JOIN  HRM_payment_items pit
                                          ON(ls.staff_id = pit.staff_id)
                                  INNER JOIN HRM_salary_item_types sit
                                  		  ON(pit.salary_item_type_id = sit.salary_item_type_id AND sit.backpay_include = 1)
                      								  
					  WHERE pit.pay_month >= '.$this->__BACKPAY_BEGIN_FROM.' AND
							pit.pay_year='.$this->__YEAR.' AND
							'.$whr.'
							pit.payment_type in( '.NORMAL_PAYMENT.')
                                  
                       GROUP BY pit.staff_id,
                                pit.salary_item_type_id,
								sit.effect_type;
                ') ; 
	
		parent::runquery('TRUNCATE HRM_temp_must_payments');
		parent::runquery(' /*CREATE  TABLE temp_must_payments  AS*/
								insert into HRM_temp_must_payments
								SELECT pit.staff_id,
									pit.salary_item_type_id,
									sit.effect_type,
									SUM(pit.get_value) sum_get_value_must,
									SUM(pit.pay_value) sum_pay_value_must,
									SUM(CASE
									WHEN param1 <> 0 THEN param1
									ELSE 0
									END) sum_param1_must,
											SUM(CASE
									WHEN param2 <> 0 THEN param2
									ELSE 0
									END) sum_param2_must,
											SUM(CASE
									WHEN param3 <> 0 THEN param3
									ELSE 0
									END) sum_param3_must,
											SUM(CASE
									WHEN param4 <> 0 THEN param4
									ELSE 0
									END) sum_param4_must,
											SUM(CASE
									WHEN param5 <> 0 THEN param5
									ELSE 0
									END) sum_param5_must,
											SUM(CASE
									WHEN param6 <> 0 THEN param6
									ELSE 0
									END) sum_param6_must,
											SUM(CASE
									WHEN param7 <> 0 THEN param7
									ELSE 0
									END) sum_param7_must,
											SUM(CASE
									WHEN param8 <> 0 THEN param8
									ELSE 0
									END) sum_param8_must,
											SUM(CASE
									WHEN param9 <> 0 THEN param9
									ELSE 0
									END) sum_param9_must

							FROM HRM_back_payment_items pit
									INNER JOIN HRM_salary_item_types sit
											ON(pit.salary_item_type_id = sit.salary_item_type_id AND sit.backpay_include = 1)

							GROUP BY pit.staff_id,
									 pit.salary_item_type_id,
									 sit.effect_type;') ; 

		
		$this->diff_rs = parent::runquery_fetchMode('
                        (SELECT
                                bpit.staff_id staff_id,
                                bpit.salary_item_type_id salary_item_type_id,
                                bpit.effect_type,
                                CASE
                                WHEN bpit.sum_get_value_must IS NULL THEN  0 - pit.sum_get_value_done
                                WHEN pit.sum_get_value_done IS NULL THEN bpit.sum_get_value_must
                                ELSE bpit.sum_get_value_must - pit.sum_get_value_done
                                END get_value_diff,
                                CASE
                                WHEN bpit.sum_pay_value_must IS NULL THEN 0 - pit.sum_pay_value_done
                                WHEN pit.sum_pay_value_done IS NULL THEN bpit.sum_pay_value_must
                                ELSE bpit.sum_pay_value_must - pit.sum_pay_value_done
                                END pay_value_diff,
                                CASE
                                WHEN bpit.sum_param1_must IS NULL THEN 0 - pit.sum_param1_done
                                WHEN pit.sum_param1_done IS NULL THEN bpit.sum_param1_must
                                ELSE bpit.sum_param1_must - pit.sum_param1_done
                                END param1_diff,
                                CASE
                                WHEN bpit.sum_param2_must IS NULL THEN 0 - pit.sum_param2_done
                                WHEN pit.sum_param2_done IS NULL THEN bpit.sum_param2_must
                                ELSE bpit.sum_param2_must - pit.sum_param2_done
                                END param2_diff,
                                CASE
                                WHEN bpit.sum_param3_must IS NULL THEN 0 - pit.sum_param3_done
                                WHEN pit.sum_param3_done IS NULL THEN bpit.sum_param3_must
                                ELSE bpit.sum_param3_must - pit.sum_param3_done
                                END param3_diff,
                                CASE
                                WHEN bpit.sum_param4_must IS NULL THEN 0 - pit.sum_param4_done
                                WHEN pit.sum_param4_done IS NULL THEN bpit.sum_param4_must
                                ELSE bpit.sum_param4_must - pit.sum_param4_done
                                END param4_diff,
                                CASE
                                WHEN bpit.sum_param5_must IS NULL THEN 0 - pit.sum_param5_done
                                WHEN pit.sum_param5_done IS NULL THEN bpit.sum_param5_must
                                ELSE bpit.sum_param5_must - pit.sum_param5_done
                                END param5_diff,
                                CASE
                                WHEN bpit.sum_param6_must IS NULL THEN 0 - pit.sum_param6_done
                                WHEN pit.sum_param6_done IS NULL THEN bpit.sum_param6_must
                                ELSE bpit.sum_param6_must - pit.sum_param6_done
                                END param6_diff,
                                CASE
                                WHEN bpit.sum_param7_must IS NULL THEN 0 - pit.sum_param7_done
                                WHEN pit.sum_param7_done IS NULL THEN bpit.sum_param7_must
                                ELSE bpit.sum_param7_must - pit.sum_param7_done
                                END param7_diff,
                                CASE
                                WHEN bpit.sum_param8_must IS NULL THEN 0 - pit.sum_param8_done
                                WHEN pit.sum_param8_done IS NULL THEN bpit.sum_param8_must
                                ELSE bpit.sum_param8_must - pit.sum_param8_done
                                END param8_diff,
                                CASE
                                WHEN bpit.sum_param9_must IS NULL THEN 0 - pit.sum_param9_done
                                WHEN pit.sum_param9_done IS NULL THEN bpit.sum_param9_must
                                ELSE bpit.sum_param9_must - pit.sum_param9_done
                                END param9_diff

                        FROM HRM_temp_must_payments bpit
                             LEFT OUTER JOIN HRM_temp_done_payments pit
                                  ON(bpit.staff_id = pit.staff_id AND bpit.salary_item_type_id = pit.salary_item_type_id)
                        WHERE  bpit.sum_get_value_must <> pit.sum_get_value_done  OR
                               bpit.sum_pay_value_must <> pit.sum_pay_value_done OR
                               bpit.sum_param1_must <> pit.sum_param1_done OR
                               bpit.sum_param2_must <> pit.sum_param2_done OR
                               bpit.sum_param3_must <> pit.sum_param3_done OR
                               bpit.sum_param4_must <> pit.sum_param4_done OR
                               bpit.sum_param5_must <> pit.sum_param5_done OR
                               bpit.sum_param6_must <> pit.sum_param6_done OR
                               bpit.sum_param7_must <> pit.sum_param7_done OR
                               bpit.sum_param8_must <> pit.sum_param8_done OR
                               bpit.sum_param9_must <> pit.sum_param9_done OR
                  			   pit.sum_get_value_done IS NULL OR
                   			   pit.sum_pay_value_done IS NULL)
                        UNION
                        (SELECT
                                pit.staff_id staff_id,
                                pit.salary_item_type_id salary_item_type_id,
                                pit.effect_type,
                                CASE
                                WHEN bpit.sum_get_value_must IS NULL THEN  0 - pit.sum_get_value_done
                                WHEN pit.sum_get_value_done IS NULL THEN bpit.sum_get_value_must
                                ELSE bpit.sum_get_value_must - pit.sum_get_value_done
                                END get_value_diff,
                                CASE
                                WHEN bpit.sum_pay_value_must IS NULL THEN 0 - pit.sum_pay_value_done
                                WHEN pit.sum_pay_value_done IS NULL THEN bpit.sum_pay_value_must
                                ELSE bpit.sum_pay_value_must - pit.sum_pay_value_done
                                END pay_value_diff,
                                CASE
                                WHEN bpit.sum_param1_must IS NULL THEN 0 - pit.sum_param1_done
                                WHEN pit.sum_param1_done IS NULL THEN bpit.sum_param1_must
                                ELSE bpit.sum_param1_must - pit.sum_param1_done
                                END param1_diff,
                                CASE
                                WHEN bpit.sum_param2_must IS NULL THEN 0 - pit.sum_param2_done
                                WHEN pit.sum_param2_done IS NULL THEN bpit.sum_param2_must
                                ELSE bpit.sum_param2_must - pit.sum_param2_done
                                END param2_diff,
                                CASE
                                WHEN bpit.sum_param3_must IS NULL THEN 0 - pit.sum_param3_done
                                WHEN pit.sum_param3_done IS NULL THEN bpit.sum_param3_must
                                ELSE bpit.sum_param3_must - pit.sum_param3_done
                                END param3_diff,
                                CASE
                                WHEN bpit.sum_param4_must IS NULL THEN 0 - pit.sum_param4_done
                                WHEN pit.sum_param4_done IS NULL THEN bpit.sum_param4_must
                                ELSE bpit.sum_param4_must - pit.sum_param4_done
                                END param4_diff,
                                CASE
                                WHEN bpit.sum_param5_must IS NULL THEN 0 - pit.sum_param5_done
                                WHEN pit.sum_param5_done IS NULL THEN bpit.sum_param5_must
                                ELSE bpit.sum_param5_must - pit.sum_param5_done
                                END param5_diff,
                                CASE
                                WHEN bpit.sum_param6_must IS NULL THEN 0 - pit.sum_param6_done
                                WHEN pit.sum_param6_done IS NULL THEN bpit.sum_param6_must
                                ELSE bpit.sum_param6_must - pit.sum_param6_done
                                END param6_diff,
                                CASE
                                WHEN bpit.sum_param7_must IS NULL THEN 0 - pit.sum_param7_done
                                WHEN pit.sum_param7_done IS NULL THEN bpit.sum_param7_must
                                ELSE bpit.sum_param7_must - pit.sum_param7_done
                                END param7_diff,
                                CASE
                                WHEN bpit.sum_param8_must IS NULL THEN 0 - pit.sum_param8_done
                                WHEN pit.sum_param8_done IS NULL THEN bpit.sum_param8_must
                                ELSE bpit.sum_param8_must - pit.sum_param8_done
                                END param8_diff,
                                CASE
                                WHEN bpit.sum_param9_must IS NULL THEN 0 - pit.sum_param9_done
                                WHEN pit.sum_param9_done IS NULL THEN bpit.sum_param9_must
                                ELSE bpit.sum_param9_must - pit.sum_param9_done
                                END param9_diff
                        FROM HRM_temp_done_payments pit
                             LEFT OUTER JOIN HRM_temp_must_payments bpit
                                  ON(bpit.staff_id = pit.staff_id AND bpit.salary_item_type_id = pit.salary_item_type_id)
                        WHERE  bpit.sum_get_value_must <> pit.sum_get_value_done  OR
                               bpit.sum_pay_value_must <> pit.sum_pay_value_done OR
                               bpit.sum_param1_must <> pit.sum_param1_done OR
                               bpit.sum_param2_must <> pit.sum_param2_done OR
                               bpit.sum_param3_must <> pit.sum_param3_done OR
                               bpit.sum_param4_must <> pit.sum_param4_done OR
                               bpit.sum_param5_must <> pit.sum_param5_done OR
                               bpit.sum_param6_must <> pit.sum_param6_done OR
                               bpit.sum_param7_must <> pit.sum_param7_done OR
                               bpit.sum_param8_must <> pit.sum_param8_done OR
                               bpit.sum_param9_must <> pit.sum_param9_done OR
                   			   bpit.sum_get_value_must IS NULL OR
                   			   bpit.sum_pay_value_must IS NULL)

                        ORDER BY staff_id,salary_item_type_id
                ') ; 
		 	

		$this->diffRowCount = $this->diff_rs->rowCount();
		$this->diffRow = $this->diff_rs->fetch() ;
		$this->diffRowID++ ; 
		
	}
		
	/*نوشتن اطلاعات آرايه ها در فايل*/
	private function save_to_DataBase() {		
		
		//نوشتن آرايه paymnet_items در فايل
		ob_start();
		$pure_pay = 0; //متغيري جهت نگهداري خالص پرداختي
		reset($this->payment_items);
										
		foreach ($this->payment_items as $pay_row) {
			
			if( $pay_row['pay_value']==0 && $pay_row['get_value']==0 &&					
				(!empty($pay_row['diff_pay_value']) && $pay_row['diff_pay_value']  ==0 ) && 
				(!empty($pay_row['diff_get_value']) && $pay_row['diff_get_value']  ==0 )  &&
				$pay_row['salary_item_type_id']!= SIT_PROFESSOR_RETIRED &&
				$pay_row['salary_item_type_id']!=SIT_STAFF_RETIRED )
				continue;
				
			$pay_row['payment_type'] = 1 ;
				
			if(empty($pay_row['pay_value']))                     $pay_row['pay_value'] = 0;
			if(empty($pay_row['get_value']))                     $pay_row['get_value'] = 0;
			if(empty($pay_row['param1']))                        $pay_row['param1'] = 0;
			if(empty($pay_row['param2']))                        $pay_row['param2'] = 0;
			if(empty($pay_row['param3']))                        $pay_row['param3'] = 0;
			if(empty($pay_row['param4']))                        $pay_row['param4'] = 0;
			if(empty($pay_row['param5']))                        $pay_row['param5'] = 0;
			if(empty($pay_row['param6']))                        $pay_row['param6'] = 0;
			if(empty($pay_row['param7']))                        $pay_row['param7'] = 0;
			if(empty($pay_row['param8']))                        $pay_row['param8'] = 0;
			if(empty($pay_row['param9']))                        $pay_row['param9'] = 0;
			if(empty($pay_row['diff_param1']))                $pay_row['diff_param1'] = 0;
			if(empty($pay_row['diff_param2']))                $pay_row['diff_param2'] = 0;
			if(empty($pay_row['diff_param3']))                $pay_row['diff_param3'] = 0;
			if(empty($pay_row['diff_param4']))                $pay_row['diff_param4'] = 0;
			if(empty($pay_row['diff_param5']))                $pay_row['diff_param5'] = 0;
			if(empty($pay_row['diff_param6']))                $pay_row['diff_param6'] = 0;
			if(empty($pay_row['diff_param7']))                $pay_row['diff_param7'] = 0;
			if(empty($pay_row['diff_param8']))                $pay_row['diff_param8'] = 0;
			if(empty($pay_row['diff_param9']))                $pay_row['diff_param9'] = 0;
			if(empty($pay_row['diff_get_value']))        $pay_row['diff_get_value'] = 0;
			if(empty($pay_row['diff_pay_value']))        $pay_row['diff_pay_value'] = 0;
			if(!isset($pay_row['diff_value_coef']))       $pay_row['diff_value_coef'] = 1;
			
			echo   
			'('.$pay_row['diff_get_value'].','.
			$pay_row['diff_pay_value'].','.
			$pay_row['pay_year'].','.
			$pay_row['pay_month'].','.
			$pay_row['staff_id'].','.
			$pay_row['salary_item_type_id'].','.
			$pay_row['pay_value'].','.
			$pay_row['get_value'].','.
			$pay_row['param1'].','.
			$pay_row['param2'].','.
			$pay_row['param3'].','.
			$pay_row['param4'].','.
			$pay_row['param5'].','.
			$pay_row['param6'].','.
			$pay_row['param7'].','.
			$pay_row['param8'].','.
			$pay_row['param9'].','.
			$pay_row['diff_param1'].','.
			$pay_row['diff_param2'].','.
			$pay_row['diff_param3'].','.
			$pay_row['diff_param4'].','.
			$pay_row['diff_param5'].','.
			$pay_row['diff_param6'].','.
			$pay_row['diff_param7'].','.
			$pay_row['diff_param8'].','.
			$pay_row['diff_param9'].','.
			'0,'.
			$pay_row['payment_type'].','.			
			$pay_row['diff_value_coef'].'),';
			
			echo chr(10);

			$pure_pay += $pay_row['pay_value'] + ($pay_row['diff_pay_value'] * $pay_row['diff_value_coef']) - $pay_row['get_value'] - ($pay_row['diff_get_value'] * $pay_row['diff_value_coef']);
		}		
		
		/*خطا : حقوق فرد منفي شده است لذا ساير قسمتها براي او انجام نمي شود*/
		if($pure_pay < 0 && !$this->backpay) {
			
			if(!$this->__CALC_NEGATIVE_FICHE ) {								
				$this->log('FAIL','حقوق اين شخص به مبلغ '.CurrencyModulesclass::toCurrency($pure_pay*(-1),'CURRENCY').' منفي شده است.');
				ob_clean();
				return ;
			}			
			else {
				$this->log('FAIL','حقوق اين شخص به مبلغ '.CurrencyModulesclass::toCurrency($pure_pay*(-1),'CURRENCY').' منفي شده است.(فيش اين فرد از بخش چاپ فيش در دسترس است، لطفا پس از انجام كنترلهاي لازم فيشهاي منفي را ابطال كنيد)');
			}
		}
		
		$file_line = str_replace(',,',',\N,',ob_get_clean()); //براي اصلاح مقادير null
		$file_line = str_replace(',,',',\N,',$file_line); //براي اصلاح مقادير null
		
		$pdo = parent::getPdoObject();
		$pdo->beginTransaction();
	
		//if($this->backpay) //در صورتي که محاسبه backpay صورت مي گيرد نيازي به نوشتن ساير فايلها نيست
		//	return ;
		if(!$this->backpay)	{
			
		//نوشتن آرايه staff_writs در فايل payment_writs
		reset($this->staff_writs);
		//$writ_row = '';
		
		if($this->ExtraWork == 1 && $this->last_month == $this->__MONTH ) 
		{
			$ptype = 3 ; 			
		}		
		else 
			$ptype = NORMAL ; 
		
		
		foreach ($this->staff_writs[$this->cur_staff_id] as $writ) {
			
			parent::runquery(" insert into HRM_payment_writs(writ_id,writ_ver,staff_id,pay_year,pay_month,payment_type) values 
							(".$writ['writ_id'].",".$writ['writ_ver'].",".$this->cur_staff_id.",".$this->__YEAR.",".$this->last_month.",".$ptype.")" , array() ,$pdo) ; 
			 	
	//print_r(ExceptionHandler::PopAllExceptions());
	//	echo PdoDataAccess::GetLatestQueryString() ; 
	//die();	
			if( parent::AffectedRows() == 0  )
			{


				$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول احکام مورد استفاده در ماه جاری ');
				$pdo->rollBack();	
				ob_clean();				
				return ;
			}
 
			
		}
		//fwrite($this->payment_writs_file_h,$writ_row);
		
		
		//نوشتن payment در فايل
		$payment_row = $this->cur_staff_id . ',' .
		$this->__YEAR . ',' .
		$this->__MONTH . ',' .
		$writ['writ_id'] . ',' .
		$writ['writ_ver'] . ",'" .
		$this->month_start . "','" .
		$this->month_end . "'," .
		$ptype . ',' .
		$this->__MSG.',' .
		$this->staffRow['bank_id'].',"' .
		$this->staffRow['account_no'].'",'.
		PAYMENT_STATE_NORMAL.",'".
		DateModules::NowDateTime()."'";

		$file_line2 = str_replace(',,',',\N,',$payment_row); //براي اصلاح مقادير null
		$file_line2 = str_replace(',,',',\N,',$file_line2); //براي اصلاح مقادير null

		parent::runquery(" insert into HRM_payments(staff_id,pay_year,pay_month,writ_id,writ_ver,start_date,end_date,payment_type,message,
						   bank_id,account_no,state ,calc_date ) value (".$file_line2.") ", array(),$pdo) ; 
			
		 
		if( parent::AffectedRows() == 0  )
		{
 			
			$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول پرداختها ');
			$pdo->rollBack();
			ob_clean();				
			return ;
		}
		
		}

if($this->backpay)
		$tblName =  "HRM_back_payment_items" ; 
	else 
		$tblName = "HRM_payment_items"; 
	$file_line = substr($file_line, 0, (strlen($file_line)-2)) ;

	if(strlen($file_line) > 0 ) {
	parent::runquery("insert into ".$tblName." (diff_get_value, diff_pay_value, pay_year, pay_month, staff_id,
                        salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
                        diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
                        cost_center_id, payment_type, diff_value_coef ) values ".$file_line." " , array(),$pdo ) ; 

 
	}
	else {
		$pdo->rollBack();
		ob_clean();				
		return ;
	}
	
	if( parent::AffectedRows() == 0 )
	{
		
		//echo PdoDataAccess::GetLatestQueryString() ; die();
		
		$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول اقلام حقوقی');
		$pdo->rollBack();
		ob_clean();				
		return ;
	}
	
		$this->log('SUCCESS',$pure_pay);
	 	$pdo->commit();
		return true;

	}
	
	/*خاتمه اجراي محاسبه حقوق و بسته شدن فايلها و record sets*/
	private function epilogue() {
		$this->monitor(9);	
		
		if(!$this->backpay) {									
		
			fwrite($this->fail_log_file_h, '</table></cenetr></body></html>');
			fwrite($this->success_log_file_h, '</table></cenetr></body></html>');
			fclose($this->fail_log_file_h);
			fclose($this->success_log_file_h);
			chmod('../../../tempDir/fail_log.php',0644);
			chmod('../../../tempDir/success_log.php',0644);		
		}
	}
	
	/*ثبت اطلاعات محاسبه حقوق در پايگاه داده*/
	/*private function submit() {
			 
		$this->monitor(10);   
		if($this->backpay) {
			return ;
		}

		$file_path = HR_TemlDirPath;
*/
		/*********************************************************************************************/
		/*parent::runquery('LOCK TABLES payments READ, payment_items READ, person_subtracts READ, person_subtract_flows READ, payment_writs READ;') ; 
		parent::runquery('LOCK TABLES payments WRITE, payment_items WRITE, person_subtracts WRITE, person_subtract_flows WRITE, payment_writs WRITE;') ; 		
		
		/*********************************************************************************************/
	/*	parent::runquery('ALTER TABLE payments DISABLE KEYS;') ; 
		parent::runquery('LOAD DATA  LOCAL INFILE \''.$file_path.'payment_file.txt\' INTO TABLE payments
						  FIELDS TERMINATED BY \',\'
						  (staff_id,pay_year,pay_month,writ_id,writ_ver,start_date,end_date,payment_type,message,
						   bank_id,account_no,state);') ; 
		
		parent::runquery('ALTER TABLE payments ENABLE KEYS;') ; 
		
		/*********************************************************************************************/
	/*	parent::runquery(' ALTER TABLE payment_items DISABLE KEYS;') ; 
		parent::runquery('
                        LOAD DATA LOCAL INFILE \''.$file_path.'payment_items_file.txt\' INTO TABLE payment_items
                        FIELDS TERMINATED BY \',\'
                        (diff_get_value, diff_pay_value, pay_year, pay_month, staff_id,
                        salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
                        diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
                        cost_center_id, payment_type, diff_value_coef );
                ') ;
		parent::runquery('ALTER TABLE payment_items ENABLE KEYS;') ; 
		
		/*********************************************************************************************/
	/*	parent::runquery('ALTER TABLE payment_writs DISABLE KEYS;') ; 
		parent::runquery('
							LOAD DATA  LOCAL INFILE \''.$file_path.'payment_writs_file.txt\' INTO TABLE payment_writs
							FIELDS TERMINATED BY \',\'
							(writ_id,writ_ver,staff_id,pay_year,pay_month,payment_type);
						') ; 
		parent::runquery('ALTER TABLE payment_writs ENABLE KEYS;') ; 
		
		/*********************************************************************************************/
		/*parent::runquery('ALTER TABLE person_subtracts DISABLE KEYS;') ; 
		parent::runquery('SET FOREIGN_KEY_CHECKS=0'); 
		parent::runquery('
							LOAD DATA  LOCAL INFILE \''.$file_path.'subtract_file.txt\' REPLACE INTO TABLE person_subtracts
							FIELDS TERMINATED BY \',\'
							(subtract_id,staff_id,subtract_type,bank_id,first_value,
							instalment,remainder,start_date,end_date,comments,
							salary_item_type_id,account_no,loan_no,flow_date,flow_time,subtract_status,contract_no);
						') ; 		
		parent::runquery('SET FOREIGN_KEY_CHECKS=1') ;
		parent::runquery('ALTER TABLE person_subtracts ENABLE KEYS;') ; */
		
		/*********************************************************************************************/
		/*parent::runquery('ALTER TABLE person_subtract_flows DISABLE KEYS;') ; 
		parent::runquery(' LOAD DATA LOCAL INFILE \''.$file_path.'subtract_flow_file.txt\' INTO TABLE person_subtract_flows
								FIELDS TERMINATED BY \',\'
								(subtract_id,row_no,flow_type,flow_date,flow_time,
								old_remainder,new_remainder,old_instalment,new_instalment,
								comments);
						'); 
		
		parent::runquery(' ALTER TABLE person_subtract_flows ENABLE KEYS;') ;*/ 
		
		/*********************************************************************************************/
		/*parent::runquery('UNLOCK TABLES;') ; 		
		
		$this->update_person_dependent_support(); // barrassiiii shavad 
	} */
	
	//نمايش آمار
	private function statistics() {
		$this->monitor(11);
		if($this->backpay)
		return ;				
	}
		
	private function update_person_dependent_support(){
		parent::runquery('
			UPDATE person_dependent_supports pds
			INNER JOIN mpds
			      ON(mpds.PersonID = pds.PersonID
			      AND mpds.master_row_no = pds.master_row_no
			      AND mpds.row_no = pds.row_no)
			INNER JOIN person_dependents pd
			   ON(pd.PersonID = pds.PersonID AND pd.row_no = pds.master_row_no)
			INNER JOIN staff s
			   ON(pds.PersonID = s.PersonID)
			INNER JOIN limit_staff ls
			   ON(s.staff_id = ls.staff_id)
			INNER JOIN payment_items pi
				ON pi.staff_id = ls.staff_id 
				AND pi.pay_year = '.$this->__YEAR.' 
				AND pi.pay_month = '.$this->__MONTH.' 
				AND pi.payment_type = '.NORMAL_PAYMENT.'
			SET calc_year_to = '.$this->__YEAR.' , calc_month_to = '.$this->__MONTH.' ,
			    calc_year_from = (CASE WHEN calc_year_from IS NULL THEN '.$this->__YEAR.' ELSE calc_year_from END),
			    calc_month_from = (CASE WHEN calc_month_from IS NULL THEN 6 ELSE calc_month_from END)
         ') ; 
	
	}
	
	
	//........................ در این تابع جدول back_payment_items..................
	private function empty_back_tables()
	{
		parent::runquery("TRUNCATE HRM_back_payment_items") ; 
	
		return;		
	}
//............................................................. محاسبه حقوق با انجام فرآیند back Pay...............................................
		
	public function run_back()
	{	


		//در اين تابع فرض براين است که سال مالي با سال شمسي مطابقت دارد
		$this->empty_back_tables() ; 
	
		$this->last_month = $this->__MONTH;
		$this->last_month_end = $this->month_end;

		$this->backpay_recurrence = 0;
				
		//محاسبه حقوق ماههاي قبلي
		for ($i = $this->__BACKPAY_BEGIN_FROM; $i<$this->last_month; $i++) {
			
			$this->backpay_recurrence++;
			$this->backpay = true;			
			$this->month_start = DateModules::shamsi_to_miladi($this->__YEAR."/".$i."/01") ; 					
			$this->month_end = DateModules::shamsi_to_miladi($this->__YEAR."/".$i."/".DateModules::DaysOfMonth($this->__YEAR,$i)) ;			 			
			$this->__MONTH = $i;
			$this->__MONTH_LENGTH = DateModules::DaysOfMonth($this->__YEAR,$i) ; 					
								
			if(!$this->run()) {			
				return false;
			}						
			
		}
    
		$this->exe_difference_sql();

		
		//محاسبه حقوق همين ماه
		$this->backpay_recurrence++;
		$this->backpay = false;
		$this->month_start = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->last_month."/01") ; 								
		$this->month_end = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->last_month."/".DateModules::DaysOfMonth($this->__YEAR,$this->last_month)) ; 				
		$this->__MONTH = $this->last_month;
		$this->__MONTH_LENGTH = DateModules::DaysOfMonth($this->__YEAR,$this->last_month);
		$this->run();
		
		return true ;
	}

	//مقداردهي اوليه متغيرهاي کلاس
	private function prologue()
	{
			
		if($this->backpay_recurrence > 0)
			$this->expire_time = 300; //مدت زمان بر حسب ثانيه
		else
			$this->expire_time = 60; //مدت زمان بر حسب ثانيه
	
		if(!$this->check_to_run())
			return false;  

		
		$this->salary_params = array(); //يک آرايه جهت نگهداري پارامترهاي حقوقي
		$this->tax_tables = array(); //يک ارايه جهت نگهداري جداول مالياتي
		$this->acc_info = array();
		$this->payment_items = array(); //اقلام حقوق
		$this->person_subtract_array = array(); //وام و کسوراتي که بايد در جدول person_subtract بروزرساني شوند
		$this->person_subtract_flow_array = array(); //وام و کسوراتي که بايد در جدول person_subtract_flow بروزرساني شوند

		$this->sum_tax_include = 0; //يک متغير براي نگهداري مجموع مقادير قلام هاي مشمول ماليات
		$this->sum_insure_include = 0; //يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بيمه
		$this->sum_retired_include = 0; // يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بازنشتگي
		$this->max_sum_pension = 0;  //در اين متغير همواره ماکزيمم حقوق مشمول بازنشستگ که مقرري به ان تعلق مي گيرد نگهداري ميشود
		$this->cost_center_id = 0; //متغيري جهت نگهداري کد مرکز هزينه cur_staff که از آخرين حکم استخراج مي گردد
		$this->writRowID = $this->staffRowID = $this->PGLRowID = $this->subRowID =  $this->insureRowID = $this->taxRowID = $this->taxHisRowID = $this->pensionRowID = 0 ;  

		//در محاسبه backpay تاريخ آخرين ماه براي فيلتر کردن روي pay_date استفاده مي شود
		if(!$this->backpay)
		{
			$this->last_month_end = $this->month_end;
			$this->last_month = $this->__MONTH;
		}
		
		//فقط يكبار چه در حالت  backpay و چه در غير از آن حالت
		if($this->backpay_recurrence <= 1)
		{
			$this->staff_writs = array();
			$this->exe_limit_staff(); 				
					
				//  به روز رسانی وامهایی که فاقد اعتبار هستند ...........................
				manage_subtracts::GetRemainder("","","", true ,$this->last_month , $this->__YEAR); 	
				manage_subtracts::UpdateExpireLoan();   				
			
		}
				
		$this->exe_writ_sql();	 		
		
		$this->exe_param_sql(); 		
		$this->exe_paygetlist_sql(); 
		$this->exe_staff_sql();
		
		$this->exe_subtract_sql(); 
				
		$this->exe_tax_sql(); 
		$this->exe_tax_history(); 
		$this->exe_taxtable_sql(); 
		$this->exe_acc_info();  				
							
		$this->writRowCount = $this->writ_sql_rs->rowCount();
		$this->writRow = $this->writ_sql_rs->fetch(); 
		$this->writRowID++ ; 
						
		$this->cur_writ_id = $this->writRow['writ_id']; //شماره حکم جاري
		$this->cur_staff_id = $this->writRow['staff_id']; // شماره staff جاري
				
		$this->staffRowCount = $this->staff_rs->rowCount(); 
		$this->staffRow = $this->staff_rs->fetch(); 
		$this->staffRowID++ ;
		
		$this->pgRowCount = $this->pay_get_list_rs->rowCount();
		$this->PGLRow =  $this->pay_get_list_rs->fetch() ; 				
		$this->PGLRowID++ ; 
		
		$this->subRowCount = $this->subtracts_rs->rowCount();
		$this->subRow = $this->subtracts_rs->fetch() ; 
		$this->subRowID++ ; 	
	
		if(!$this->backpay) { 
			$this->taxRowCount = $this->tax_rs->rowCount();
			$this->taxRow = $this->tax_rs->fetch() ;
			$this->taxRowID++ ; 

			$this->taxHisRowCount = $this->tax_history_rs->rowCount();
			$this->taxHisRow = $this->tax_history_rs->fetch() ;
			$this->taxHisRowID++ ; 
		}
			

		if($this->backpay_recurrence == 1 || !$this->backpay) {
			$this->monitor(7); 
			
			$this->fail_log_file_h = fopen('../../../../HumanResources/tempDir/fail_log.php','w+');
			$this->success_log_file_h = fopen('../../../../HumanResources/tempDir/success_log.php','w+');



			$this->fail_counter = 1;
			$this->success_counter = 1;
			$this->writ_logs_file_header(); 
		}
		
		//فقط يكبار چه در حالت  backpay و چه در غير از آن حالت
		if($this->backpay_recurrence <= 1 ) {
			//$this->payment_writs_file_h = fopen(HR_TemlDirPath.'payment_writs_file.txt','w+'); //اقلامي که بايد در payment_writs درج شوند
		}

		$this->set_work_sheet();   //کارکرد staff جاري در ماه
		
		return true;
	}
	
	//درج هدرهاي مربوط به fail و sucess
	private function writ_logs_file_header() {
		$fail_header = '<html dir="rtl">
                                                <head>
                                                <meta http-equiv="Content-Language" content="fa">
                                                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                                                <title>ليست خطاها</title>
                                                </head>
                                                <body><center>
                                                <table border="1" width="70%" style="font-family:nazanin; border-collapse: collapse">
                                                <tr>
                                                        <td width="5%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>رديف</b></font></td>
                                                        <td width="10%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>شماره شناسايي</b></font></td>                                                       
                                                        <td width="25%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>نام خانوادگي و نام</b></font></td>
                                                        <td width="30%" align="center" bgcolor="#3F5F96" ><font color="#FFFFFF"><b>خطا</b></font></td>
                                                </tr>';
		fwrite($this->fail_log_file_h, $fail_header);

		$success_header='<html dir="rtl">
                                                <head>
                                                <meta http-equiv="Content-Language" content="fa">
                                                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                                                <title>ليست موفقيتها</title>
                                                </head>
                                                <body><center>
                                                <table border="1" width="50%" style="font-family:nazanin; border-collapse: collapse" dir="rtl">
                                                <tr>
                                                        <td width="5%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>رديف</b></font></td>
                                                        <td width="10%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>شماره شناسايي</b></font></td>                                                     
                                                        <td width="25%" align="center" bgcolor="#3F5F96"><font color="#FFFFFF"><b>نام خانوادگي و نام</b></font></td>
                                                        <td width="10%" align="center" bgcolor="#3F5F96" ><font color="#FFFFFF"><b>خالص دريافتي(ريال)</b></font></td>
                                                </tr>';
		fwrite($this->success_log_file_h, $success_header);
	}
	
	//استخراج تعداد روزهاي کارکرد در صورتي که براي اين staff درج شده باشد
	private function set_work_sheet() {
		
		$work_sheet = $this->__MONTH_LENGTH;

	/*	$this->moveto_curStaff($this->pay_get_list_rs ,'PGL');
		
		if( $this->PGLRow['staff_id'] == $this->cur_staff_id && $this->PGLRow['list_type'] == WORK_SHEET_LIST) {
			$work_sheet = $this->PGLRow['approved_amount'];		
			$this->PGLRow = $this->pay_get_list_rs->fetch(); 
			$this->PGLRowID++ ; 
		}
	*/
		$this->cur_work_sheet = $work_sheet; 
	}
	
	private function moveto_curStaff(&$rs,$type) {
		if($type == 'PGL') 
		{
			while ($this->PGLRowID <= $this->pgRowCount) {				
				if($this->PGLRow['staff_id'] < $this->cur_staff_id) {
				$this->PGLRow = $rs->fetch(); 
				$this->PGLRowID++ ; 
					continue;
				}
				else if($this->PGLRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
		
		}		
		else if($type == 'STF') 
		{   	
			while ($this->staffRowID <= $this->staffRowCount) {				
				if($this->staffRow['staff_id'] < $this->cur_staff_id) {
				$this->staffRow = $rs->fetch(); 
				$this->staffRowID++ ; 
					continue;
				}
				else if($this->staffRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
		
		}
		else if($type == 'SUB') 
		{   	
			while ($this->subRowID <= $this->subRowCount) {				
				if($this->subRow['staff_id'] < $this->cur_staff_id) {
					$this->subRow = $rs->fetch(); 
					$this->subRowID++ ; 
					continue;
				}
				else if($this->subRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
		
		}
		else if ($type == 'Insure')
		{			
			while ($this->insureRowID <= $this->insureRowCount) {	
				
				if($this->insureRow['staff_id'] < $this->cur_staff_id) {
					$this->insureRow = $rs->fetch(); 
					$this->insureRowID++ ; 
					continue;
				}
				else if($this->insureRow['staff_id'] >= $this->cur_staff_id) 
					break;				
					
			}
			
		}
		else if ($type == 'TAX')
		{
			while ($this->taxRowID <= $this->taxRowCount) {				
				if($this->taxRow['staff_id'] < $this->cur_staff_id) {
					$this->taxRow = $rs->fetch(); 
					$this->taxRowID++ ; 
					continue;
				}
				else if($this->taxRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
			
		}
		else if ($type == 'TAXHIS')
		{
			while ($this->taxHisRowID <= $this->taxHisRowCount) {				
				if($this->taxHisRow['staff_id'] < $this->cur_staff_id) {
					$this->taxHisRow = $rs->fetch(); 
					$this->taxHisRowID++ ; 
					continue;
				}
				else if($this->taxHisRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
			
		}
		else if ($type == 'PENSION')
		{
			while ($this->pensionRowID <= $this->pensionRowCount) {				
				if($this->pensionRow['staff_id'] < $this->cur_staff_id) {
					$this->pensionRow = $rs->fetch(); 
					$this->pensionRowID++ ; 
					continue;
				}
				else if($this->pensionRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
			
		}
		else if ($type == 'DIFF')
		{
			while ($this->diffRowID <= $this->diffRowCount) {				
				if($this->diffRow['staff_id'] < $this->cur_staff_id) {
					$this->diffRow = $rs->fetch(); 
					$this->diffRowID++ ; 
					continue;
				}
				else if($this->diffRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
			
		}
		else if ($type == 'MSLY')
		{ 
			while ($this->MsalaryRowID <= $this->MsalaryRowCount) {				
				if($this->MsalaryRow['staff_id'] < $this->cur_staff_id) {
					$this->MsalaryRow = $rs->fetch(); 
					$this->MsalaryRowID++ ; 
					continue;
				}
				else if($this->MsalaryRow['staff_id'] >= $this->cur_staff_id)
					break;
			}
			
		}
				
	}
	
	//مقداردهي مجدد متغيرها براي محاسبه حقوق نفر بعدي
	private function initForNextStaff() {
		//$this->person_subtract_array = array();
		//$this->person_subtract_flow_array = array();

		$this->sum_tax_include = 0;
		$this->sum_insure_include = 0;
		$this->sum_retired_include = 0;
		
		$this->max_sum_pension = 0;
		$this->extra_pay_value = 0 ; 
		$this->cost_center_id = 0;

		$this->payment_items = array();
		$this->cur_staff_id = $this->writRow['staff_id'];

		$this->moveto_curStaff($this->staff_rs,'STF');
		$this->set_work_sheet();
	}
	
	/*تعيين اعتبار قلم حقوقي*/
	private function validate_salary_item_id($validity_start_date, $validity_end_date ,$t = "") {
		/*echo $this->acc_info[$key]['validity_start_date']."---VSD".$this->acc_info[$key]['validity_end_date']."---VED-----<br>" ; */
		/*if($t == true ) {
		echo $validity_start_date."****".$this->month_end."****<br>" ; 
		DateModules::CompareDate($validity_start_date, $this->month_end) ; 
		echo "*****<br>" ; 
		echo $validity_end_date."&&&&&".$this->month_start."&&&&&<br>" ; 
		DateModules::CompareDate($validity_end_date, $this->month_start);		
		echo "$$$$$$<br>";
		die() ; 
		}*/
		if( DateModules::CompareDate($validity_start_date, $this->month_end) != 1 && 
									( DateModules::CompareDate($validity_end_date, $this->month_start) != -1 || $validity_end_date == null || $validity_end_date == '0000-00-00' ) ) 
			//echo "step999----<br>" ;
		return true; 
		else
		return false;
	}
	
		//تابع محاسبه اقلام ضريبي
	private function coef() {
		//در مورد ضريبي ها فرض مي شود که فيلد approved_amount همان ضريب باشد

		switch($this->PGLRow['multiplicand']) {
			case BASE_SALARY_MULTIPLICAND:
			$value = $this->payment_items[$this->get_base_salary_item_id()]['pay_value'] * $this->PGLRow['approved_amount'];
			break;
			case SALARY_MULTIPLICAND:
			$value = $this->get_salary() * $this->PGLRow['approved_amount'];
			break;
			case CONTINUES_SALARY_MULTIPLICAND:
			$value = $this->get_continues_salary() * $this->PGLRow['approved_amount'];
			break;
		}

		if($this->PGLRow['list_type'] == PAY_GET_LIST || $this->PGLRow['list_type'] == GROUP_PAY_GET_LIST) {
			$entry_title_full = 'pay_value';
			$entry_title_empty = 'get_value';
		}
		else {
			$entry_title_full = 'get_value';
			$entry_title_empty = 'pay_value';
		}
		$payment_rec = array('pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => $this->PGLRow['salary_item_type_id'],
							 $entry_title_full            => $value,
							 $entry_title_empty    => 0,
							 'param1'              => $this->PGLRow['approved_amount'],
							 'cost_center_id'      => 0,
							 'payment_type'        => NORMAL );
		return $payment_rec;

	}
	
	//کد قلم حقوق مبنا براي staff جاري
	private function get_base_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT2_BASE_SALARY;
			case HR_PROFESSOR: return SIT1_BASE_SALARY;
			case HR_WORKER: return SIT3_BASE_SALARY;
		}
	}
	
	//کد قلم حقوقي بيمه تامين اجتماعي براي staff جاري
	private function get_insure_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT_STAFF_COLLECTIVE_SECURITY_INSURE;
			case HR_PROFESSOR: return SIT_PROFESSOR_COLLECTIVE_SECURITY_INSURE;
			case HR_WORKER: return  7 ;
			case HR_CONTRACT: return SIT5_STAFF_COLLECTIVE_SECURITY_INSURE;
		}
	}
	
	//کد قلم حقوقي ماليات براي staff جاري
	private function get_tax_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT_STAFF_TAX;
			case HR_PROFESSOR: return SIT_PROFESSOR_TAX;
			case HR_WORKER: return  8 ;
			case HR_CONTRACT: return SIT5_STAFF_TAX ;	
		}
	}
	
	//کد قلم حقوقي بيمه خدمات درماني براي staff جاري
	function get_service_insure_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT_STAFF_REMEDY_SERVICES_INSURE;
			case HR_PROFESSOR: return SIT_PROFESSOR_REMEDY_SERVICES_INSURE;
		}
	}
	
	//کد قلم حقوقي مقرري براي staff جاري
	private function get_pension_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return STAFF_FIRST_MONTH_MOGHARARY;
			case HR_PROFESSOR: return PROFESSOR_FIRST_MONTH_MOGHARARY;
		}
	}
	
	//کد قلم حقوقي بازنشتگي براي staff جاري
	private function get_retired_salary_item_id() {	
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT_STAFF_RETIRED;
			case HR_PROFESSOR: return SIT_PROFESSOR_RETIRED;
			case HR_WORKER: return SIT_WORKER_RETIRED;
		}
	}
	
	//تعيين ضريب تاثير گذار بر محاسبه بازنشستگي و مقرري
	private function get_retired_coef() {
		$coefficient = 1; //init
		
		if ($this->staffRow['last_retired_pay'] != NULL && $this->staffRow['last_retired_pay'] != '0000-00-00' &&
				DateModules::CompareDate($this->staffRow['last_retired_pay'],$this->month_start) == -1) { 
			$coefficient = 0;
		}
		elseif ($this->staffRow['last_retired_pay'] != NULL && $this->staffRow['last_retired_pay'] != '0000-00-00' &&
		DateModules::CompareDate($this->staffRow['last_retired_pay'], $this->month_end) != 1 ) {	 
			$coefficient = (round(DateModules::GDateMinusGDate($this->staffRow['last_retired_pay'],$this->month_start)) + 1) / $this->__MONTH_LENGTH;
		}
		return $coefficient;
	}
	
	//مبلغ حقوق در تعريف دفترچه
	private function get_salary() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE:
			$value = $this->payment_items[SIT2_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT2_ANNUAL_INC]['pay_value'];
			break;
			case HR_PROFESSOR:
			$value = $this->payment_items[SIT1_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT_PROFESSOR_SPECIAL_EXTRA]['pay_value'];
			break;
			case HR_WORKER:
			$value = $this->payment_items[SIT_WORKER_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT_WORKER_ANNUAL_INC]['pay_value'];
			break;
		}
		return $value;
	}
	
		//مبلغ حقوق مستمري
	private function get_continues_salary() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE:
			$value = $this->payment_items[SIT_STAFF_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT_STAFF_MIN_PAY]['pay_value'] +
					 $this->payment_items[SIT_STAFF_SHIFT_EXTRA]['pay_value'] +
					 $this->payment_items[SIT_STAFF_HARD_WORK_EXTRA]['pay_value'] +
					 $this->payment_items[SIT_STAFF_DOMINANT_JOB_EXTRA]['pay_value'] +
					 $this->payment_items[SIT_STAFF_JOB_EXTRA]['pay_value'] +
					 $this->payment_items[SIT_STAFF_ADAPTION_DIFFERENCE]['pay_value'] +
					 $this->payment_items[SIT_STAFF_ANNUAL_INC]['pay_value'];
			break;
			case HR_PROFESSOR:
			$value = $this->payment_items[SIT_PROFESSOR_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT_PROFESSOR_FOR_BYLAW_15_3015]['pay_value'] +
					 $this->payment_items[SIT_PROFESSOR_DEVOTION_EXTRA]['pay_value'] +
					 $this->payment_items[SIT_PROFESSOR_ADAPTION_DIFFERENCE]['pay_value'] +
					 $this->payment_items[SIT_PROFESSOR_SPECIAL_EXTRA]['pay_value'];
			break;
			case HR_WORKER:
			$value = $this->payment_items[SIT_WORKER_BASE_SALARY]['pay_value'] +
					 $this->payment_items[SIT_WORKER_ANNUAL_INC]['pay_value'];
			break;
		}
		return $value;

	}
	
	//محاسبه مجموع حقوق مشول مقرري در آخرين حکم ماه جاري
	private function add_to_last_writ_sum_retired_include(&$fields, $key, $value) {
		$this->last_writ_sum_retired_include += $value * $fields['pension_include'];
	}
	
	//بروز رساني مجموع حقوق مشمول بيمه و ماليات و بازنشتگي
	private function update_sums(&$fields, $value) {
		$this->sum_tax_include += $value * $fields['tax_include'];
	    $this->sum_insure_include += $value * $fields['insure_include'];	
	    
	}
	
		//قسمت کنترل فرد
	private function control() {
		if($this->backpay) {
			return true ;
		}
		if($this->staffRow['pstaff_id'] > 0 ) { //قبلا فيش براي فرد صادر شده است
			
			$this->log('FAIL' ,'قبلا براي اين شخص محاسبه حقوق انجام شده است.');
			$this->payment_items = null;
			return false ;
		}
		
		if(empty($this->staffRow['si_staff'])){		
			
			$this->log('FAIL' ,'سوابق مشموليت براي اين شخص ثبت نشده است .');
			$this->payment_items = null;
			$this->staff_writs[$this->cur_staff_id] = array();
			return false ;
		}
		
		/* سابقه مالیاتی برای فرد تکمیل نشده است*/
		if(empty($this->staffRow['tax_table_type_id'])){
			$this->log('FAIL' ,'سوابق مالیاتی برای این فردتکمیل نشده است ');
			$this->payment_items = null;
			
			return false ;
		}
		
		return true ;
	}
	
	/* log */
	private function log($type, $txt) {
		if($this->backpay)
		return ;
		if($type == 'FAIL') {
			$row = '<tr>
						<td bgcolor="#F5F5F5">'.$this->fail_counter++.'</td>
						<td bgcolor="#F5F5F5">'.$this->cur_staff_id.'</td>                                                
						<td bgcolor="#F5F5F5">'.$this->staffRow['name'].'</td>
						<td bgcolor="#F5F5F5">'.$txt.'</td>
					</tr>';
			fwrite($this->fail_log_file_h, $row);
		}
		else {
			$row = '<tr>
						<td bgcolor="#F5F5F5">'.$this->success_counter++.'</td>
						<td bgcolor="#F5F5F5">'.$this->cur_staff_id.'</td>                                        
						<td bgcolor="#F5F5F5">'.$this->staffRow['name'].'</td>
						<td bgcolor="#F5F5F5" >'.CurrencyModulesclass::toCurrency($txt,'CURRENCY').'</td>
					</tr>';
			fwrite($this->success_log_file_h, $row);
		}
	}

	/*بررسي امکان محاسبه حقوق و نمايش خطا در صورت وجود پروسه همزمان*/
	private function check_to_run()
	{
return true ; 
		if($this->backpay_recurrence > 1)
			return true;

		$tmp_rs = parent::runquery('SELECT * FROM HRM_payment_runs WHERE time_stamp >= :expireDate',
				                    array(":expireDate" => time()-$this->expire_time));
 
		 
		 //هيچ اجراي فعالي وجود ندارد
		if(count($tmp_rs) == 0)
		{			
			parent::runquery('INSERT INTO HRM_payment_runs(time_stamp,uname) VALUES(?,?)',
					  		  array(time(), $_SESSION["UserID"]));

			$this->run_id = parent::InsertID();
			return true;
		}
		
		parent::PushException(strtr(ER_CAN_NOT_RUN_PAYMENT_CALC,
    			array("%0%" => $tmp_rs[0]["uname"], "%1%" => $this->expire_time))); 
		
		return false;
	}

	/*حذف اجرا از جدول payment_items*/
	private function unregister_run()
	{
		if($this->backpay)
			return;
		parent::runquery('DELETE FROM HRM_payment_runs WHERE run_id = ?', array($this->run_id));
	}

	/* اجراي query مربوط به ساخت جدول limit_staff که با توجه به شرط ماژول تنظيمات ساخته مي شود*/
	private function exe_limit_staff()
	{
				
		parent::runquery('TRUNCATE HRM_limit_staff');
				
			parent::runquery('
							 insert into HRM_limit_staff
								SELECT s.staff_id  , s.personID , s.person_type ,  RefPersonID
								FROM HRM_persons p
									INNER JOIN HRM_staff s ON(s.personID=p.PersonID AND s.person_type=p.person_type)
									INNER JOIN HRM_writs w ON(s.last_writ_id = w.writ_id AND s.last_writ_ver = w.writ_ver AND
														      w.staff_id=s.staff_id AND w.person_type=s.person_type)
									INNER JOIN BSC_persons pr
										ON  p.RefPersonID = pr.PersonID
									LEFT JOIN BSC_jobs bj
										ON bj.JobID = w.job_id
									
								WHERE p.person_type IN(3) AND  ' . $this->__WHERE , $this->__WHEREPARAM ); 	
						
	
	}

	/* اجراي query استخراج احکام تاثيرگذار در حکم */
	private function exe_writ_sql()
	{		
				
		$this->monitor(0);	
				
		parent::runquery('TRUNCATE HRM_smed');	
		parent::runquery('TRUNCATE HRM_mwv');
 		
		parent::runquery("
                        insert into HRM_smed 
                        SELECT w.staff_id,
                               SUBSTRING_INDEX(SUBSTRING(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),11),'.',1) writ_id,
							   SUBSTRING_INDEX(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),'.',-1) writ_ver
                        FROM HRM_writs w
                                 INNER JOIN HRM_limit_staff ls ON(w.staff_id = ls.staff_id)
								 
                        WHERE w.execute_date <= '" . $this->month_start . "' AND
							  w.pay_date <= '" . $this->last_month_end . "' AND
							/*w.state = " . WRIT_SALARY . " AND*/
							  w.history_only = 0
                        GROUP BY w.staff_id;
                ");		
                
           /* if($this->__MONTH == 8 ) {
              echo PdoDataAccess::GetLatestQueryString().'***<br>' ; 
                    
                }    */

		parent::runquery("						
						insert into HRM_mwv
                        SELECT  w.staff_id,
								w.writ_id,
								MAX(w.writ_ver) writ_ver

                        FROM HRM_writs w
							INNER JOIN HRM_limit_staff ls
								ON(w.staff_id = ls.staff_id)

                        WHERE w.execute_date <= '" . $this->month_end . "' AND
                              w.execute_date > '" . $this->month_start . "' AND
                              w.pay_date <= '" . $this->last_month_end . "' AND
                              w.history_only = 0 /*AND
                              w.state = " . WRIT_SALARY . "*/
								  
                        GROUP BY w.staff_id, w.writ_id"
                );
		
	   
   /*if($this->__MONTH == 8 ) {
              echo PdoDataAccess::GetLatestQueryString().'*eeeeeee*<br>' ; 
                    
                }   */
		/*
		 اقلام آخرین حکم افراد
		  union all
		  اقلام کلیه نسخه های نهایی احکام افراد
		*/
		$this->writ_sql_rs = parent::runquery_fetchMode('
                        (SELECT
                           w.staff_id,
                           w.writ_id,
                           w.writ_ver,
                           w.execute_date,
                           wsi.salary_item_type_id,
                           wsi.param1,
                           wsi.param2,
                           wsi.param3,
                           wsi.value pay_value,                           
                           sit.insure_include,
                           sit.tax_include,
                           sit.retired_include,
                           sit.pension_include,                       	   
                           sit.month_length_effect,
                           sit.SessionItem

                        FROM HRM_limit_staff ls
							INNER JOIN HRM_smed sm ON(ls.staff_id = sm.staff_id)
                            INNER JOIN HRM_writs w ON(w.writ_id = sm.writ_id AND w.writ_ver = sm.writ_ver AND w.staff_id=sm.staff_id)
                            LEFT OUTER JOIN HRM_writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver AND 
							                                         w.staff_id = wsi.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
                            LEFT OUTER JOIN HRM_salary_item_types sit ON(wsi.salary_item_type_id = sit.salary_item_type_id)

                        WHERE (1=1)  /* and w.state = '.WRIT_SALARY.' */ )

                        UNION ALL
						
                        (SELECT
                           w.staff_id,
                           w.writ_id,
                           w.writ_ver,
                           w.execute_date,
                           wsi.salary_item_type_id,
                           wsi.param1,
                           wsi.param2,
                           wsi.param3,
                           wsi.value,                           
                           sit.insure_include,
                           sit.tax_include,
                           sit.retired_include,
                           sit.pension_include,                       	   
                           sit.month_length_effect,
                           sit.SessionItem
                        FROM HRM_mwv mwv
                             INNER JOIN HRM_writs w ON(mwv.writ_id = w.writ_id AND mwv.writ_ver = w.writ_ver AND mwv.staff_id=w.staff_id)
                             INNER JOIN HRM_limit_staff ls ON(w.staff_id = ls.staff_id)
                             LEFT OUTER JOIN HRM_writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver
									AND wsi.staff_id=w.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
                             LEFT OUTER JOIN HRM_salary_item_types sit ON(wsi.salary_item_type_id = sit.salary_item_type_id)

                        WHERE (1=1) /* and w.state = ' . WRIT_SALARY . '*/)
							
                        ORDER BY staff_id,execute_date,writ_id,writ_ver
                ');		
              /*  if($this->__MONTH == 8 ) {
              echo PdoDataAccess::GetLatestQueryString().'***' ; die(); 
                    
                }*/
		
		
	}

	/* نوشتن اطلاعات  مربوط به مراحل محاسبه حقوق در فايل مربوطه*/
	private function monitor($curStep)
	{
		$head = '			
			<table border="0" width="100%" cellpadding="2">
				<tr>
					<td colspan="2">
						<font face="Tahoma" size="2" color="#B82E8A">
							<u>محاسبه حقوق ' . DateModules::GetMonthName($this->__MONTH) . '</u>
						</font>
						
					</td>
				</tr>
				<tr>';
		
		$end = '				
			</table>
			';

		$step_array = array(
			'بارگذاري اطلاعات احکام ...',
			'بارگذاري اطلاعات مربوط به مقرري ...',
			'بارگذاري  پارامترهاي حقوقي ...',
			'بارگذاري اطلاعات اسناد و فرايندهاي سازماني ...',
			'بارگذاري اطلاعات وام و کسور ...',
			'بارگذاري اطلاعات جداول مالياتي ...',
			'بارگذاري اطلاعات وابستگان ...',
			'ايجاد فايلهاي مورد نياز ...',
			'محاسبه حقوق ... (اين فرايند طولاني است ، لطفا منتظر بمانيد)',
			'بستن فايلها ...',
			'ذخيره اطلاعات ...',
			'پايان محاسبه');

		$run_pic = HR_ImagePath . 'run.gif';
		$done_pic = HR_ImagePath . 'done.gif';
	
		$txt ='<td colspan="2"><font face="Tahoma" size="2">';
		for($i = 0; $i<$curStep; $i++) 
			$txt .= '<p><img border="0" src="' . $done_pic . '" width="15" height="14">&nbsp;' . $step_array[$i] . '</p>';

		if($curStep < 11 || $this->backpay)
			$txt .= '<p><img border="0" src="' . $run_pic . '" width="15" height="14">&nbsp;' . $step_array[$curStep] . '</p>';
		else
			$txt .= '<p><img border="0" src="' . $done_pic . '" width="15" height="14">&nbsp;' . $step_array[$curStep] . '</p>';

		$txt .= '</font></td>';

		//ايجاد فايل pay_calc_monitor جهت مانيتور کردن محاسبه حقوق
		$fh = fopen('../../../tempDir/pay_calc_monitor_file.html','w+');
			
		fwrite($fh, $head . $txt . $end );
		fclose($fh);
	}
	
	/*اجراي query ليست پرارمترهاي حقوقي و انتقال آنها به يک آرايه */
	private function exe_param_sql()
	{
		$this->monitor(2);
		
		$tmp_rs = parent::runquery("
                        SELECT
							person_type,
							param_type,
                            dim1_id,
                            dim2_id,
                            dim3_id,
                            value

                        FROM HRM_salary_params

                        WHERE from_date <= '" . $this->month_end . "' AND
							 (  to_date >= '" . $this->month_end . "' OR to_date IS NULL OR to_date = '0000-00-00') ");

		for($i=0; $i<count($tmp_rs); $i++)
		{
			$this->salary_params[$tmp_rs[$i]['param_type']][$tmp_rs[$i]['person_type']] = array(				
				'dim1_id' => $tmp_rs[$i]['dim1_id'],
				'dim2_id' => $tmp_rs[$i]['dim2_id'],
				'dim3_id' => $tmp_rs[$i]['dim3_id'],
				'value'   => $tmp_rs[$i]['value']);
		} 

			
		 
	}

	/* اجراي query مربوط به اضافه کار ، حق کشيک ، حق التدريس ، ماموريت ، کسور و مزاياي موردي*/
	private function exe_paygetlist_sql()
	{
		$this->monitor(3);
		
		//در محاسبه backpay فقط اقلامي كه مشمول backpay هستند محاسبه مي شوند
		$backpay_where = '1=1';

		if($this->backpay)
			$backpay_where = 'sit.backpay_include = 1';
		
		$this->pay_get_list_rs = parent::runquery_fetchMode("
                        SELECT staff_id ,
								FinalAmount , 1 insure_include , 1 tax_include ,s.RefPersonID


						   FROM ATN_ExtraSummary es
									inner join HRM_persons p on es.PersonID = p.RefPersonID
									inner join HRM_limit_staff s on p.PersonID = s.PersonID


						 where SummaryYear = ".$this->__YEAR." AND SummaryMonth = ".$this->__MONTH." AND StatusCode = 'CONFIRM'
						              
						  ORDER by staff_id
                        "); 

					
		
	}

	/* اجراي query مربوط به ليست staff با اطلاعات جانبازي و ايثارگري*/
	private function exe_staff_sql()
	{
		parent::runquery("SET NAMES 'utf8'");
	
		parent::runquery('TRUNCATE HRM_dvt');
		parent::runquery('                        
						insert into HRM_dvt
                        SELECT PersonID,
                               MAX(CASE devotion_type WHEN '.FREEDOM_DEVOTION.' THEN amount ELSE 0 END) freedman,
                               MAX(CASE devotion_type WHEN '.SACRIFICE_DEVOTION.' THEN amount ELSE 0 END) sacrifice

                        FROM HRM_person_devotions

                        WHERE personel_relation = '.OWN.' AND (devotion_type = '.FREEDOM_DEVOTION.' OR devotion_type = '.SACRIFICE_DEVOTION.')

                        GROUP BY PersonID;
                ');
		
		parent::runquery('TRUNCATE HRM_temp_last_writs');
		
		parent::runquery("                        
						insert into HRM_temp_last_writs
						SELECT w.staff_id,
						       SUBSTR(MAX(CONCAT(w.execute_date,w.writ_id)),11) max_writ_id,
						       SUBSTRING_INDEX(MAX(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),'.',-1) max_writ_ver
						FROM HRM_limit_staff ls
						     INNER JOIN HRM_writs w ON(ls.staff_id = w.staff_id)
						WHERE w.execute_date <= '".$this->month_end."' AND
						      w.pay_date <= '".$this->last_month_end."' AND
						      w.history_only = 0 /*AND
						      w.state = ".WRIT_SALARY." */ 
						GROUP BY staff_id ");
			
		parent::runquery('TRUNCATE HRM_temp_dev_child');
		parent::runquery(' insert into HRM_temp_dev_child
		                        SELECT PersonID 	                             
		                        FROM HRM_person_devotions		
		                        WHERE personel_relation in ('.DAUGHTER.','.BOY.' ) AND 
		                             (devotion_type = '.BEHOLDER_FAMILY_DEVOTION.' )
		
		                        GROUP BY PersonID');
				
		$this->staff_rs = parent::runquery_fetchMode(" SELECT   s.staff_id,sth.tax_table_type_id,
																tdc.PersonID shohadachild ,
																si.staff_id si_staff ,
																si.insure_include,
																si.tax_include,
																si.service_include,
																si.retired_include,
																si.pension_include,
																s.last_retired_pay,					
																s.person_type,
																s.PersonID,
																s.bank_id,
																s.account_no,
																s.sum_paied_pension,
																s.Over25 ,
																d.freedman,
																d.sacrifice,																
																w.ouid,
																w.emp_state,
																w.salary_pay_proc,
																w.worktime_type,
																w.emp_mode ,
																p.staff_id pstaff_id,
																CONCAT(per.plname,' ',per.pfname) name

                        FROM HRM_limit_staff ls
                                 INNER JOIN HRM_staff s
                                 	ON(s.staff_id = ls.staff_id)
						         LEFT OUTER JOIN HRM_staff_include_history si
						            ON(s.staff_id = si.staff_id AND si.start_date <= '".$this->month_end."' AND 
									(si.end_date IS NULL OR si.end_date = '0000-00-00' OR  
									 si.end_date >= '".$this->month_end."' OR si.end_date > '".$this->month_start."' ) )										 
                      			 INNER JOIN HRM_persons per
                       				ON(s.PersonID = per.PersonID)
                       			 INNER JOIN HRM_temp_last_writs tlw
                       			 	ON(s.staff_id = tlw.staff_id)
                             	 INNER JOIN HRM_writs w
                                    ON(tlw.max_writ_id = w.writ_id AND tlw.max_writ_ver = w.writ_ver AND tlw.staff_id = w.staff_id )
                                 LEFT OUTER JOIN HRM_dvt d
                                    ON(s.PersonID = d.PersonID)
                                 LEFT OUTER JOIN   HRM_temp_dev_child tdc
                                 	ON( s.PersonID = tdc.PersonID )    
                                 LEFT OUTER JOIN HRM_payments p
                                    ON(w.staff_id = p.staff_id AND p.pay_year = ".$this->__YEAR." AND p.pay_month=".$this->__MONTH." AND p.payment_type = ".NORMAL_PAYMENT.")
								 LEFT OUTER JOIN HRM_staff_tax_history  sth 
								    ON sth.staff_id = ls.staff_id 
                        ORDER BY s.staff_id ") ; 	

//echo PdoDataAccess::GetLatestQueryString() ; 
//die() ; 
			
	}
	
	/* اجراي query مربوط به وام و کسور و مزاياي ثابت*/
	private function exe_subtract_sql() {
		$this->monitor(4);
			
	//................................................................
		//در محاسبه backpay فقط اقلامي كه مشمول backpay هستند محاسبه مي شوند
		$backpay_where = '(1=1)';
		if($this->backpay) {
			$backpay_where = 'sit.backpay_include = 1';
		}

		$this->subtracts_rs = parent::runquery_fetchMode(" SELECT   ps.subtract_id,
																	ps.subtract_type,																	
																	ls.staff_id, 
																	ps.salary_item_type_id,
																	CASE
																		WHEN ps.subtract_type = ".LOAN." AND ps.instalment > sr.remainder THEN sr.remainder
																		ELSE ps.instalment
																	END get_value,																 
																	ps.instalment,
																	sr.remainder,
																	sr.receipt,
																	sit.validity_start_date,
																	sit.validity_end_date,
																	sit.insure_include,
																	sit.tax_include,
																	sit.retired_include,                               
																	ps.bank_id,
																	ps.first_value,
																	ps.start_date,
																	ps.end_date,
																	ps.comments,
																	ps.account_no,
																	ps.loan_no,
																	ps.contract_no 

													FROM HRM_limit_staff ls
															INNER JOIN HRM_person_subtracts ps
																ON(ps.PersonID = ls.PersonID)																															
															INNER JOIN HRM_salary_item_types sit
																ON(ps.salary_item_type_id = sit.salary_item_type_id)
															LEFT JOIN HRM_tmp_SubtractRemainders sr 
																ON  sr.subtract_id = ps.subtract_id
															
																
													WHERE  (ps.instalment > 0) AND	if(ps.subtract_type = 1 , ps.IsFinished = 0 , (1=1))  AND													
														   (ps.start_date <= '".$this->month_end."') AND 
														   (ps.end_date >= '".$this->month_start."' OR 
														    ps.end_date IS NULL OR ps.end_date = '0000-00-00' )  AND
															".$backpay_where."

													GROUP BY ls.staff_id,
															ps.subtract_type,
															ps.salary_item_type_id,
															ps.subtract_id,
															CASE
															WHEN ps.subtract_type = ".LOAN." AND ps.instalment > sr.remainder THEN sr.remainder
															ELSE ps.instalment
															END,
															ps.instalment,
															sr.remainder,
															sit.validity_start_date,
															sit.validity_end_date,
															sit.insure_include,
															sit.tax_include,
															sit.retired_include,                                 
															ps.bank_id,
															ps.first_value,
															ps.start_date,
															ps.end_date,
															ps.comments,
															ps.account_no,
															ps.loan_no
															") ;  
														
																	
									
	}
	
	
	/*اجراي query مربوط به محاسبه ماليات*/
	private function exe_tax_sql() {
		if($this->backpay) {
			return true;
		}
		$TaxWhere = " "	 ;	
		if($this->backpay_recurrence > 1) {
			$source_table = 'HRM_back_payment_items';
		}
		else 
		{
			$source_table =  'HRM_payment_items';					
		}
		
		$this->tax_rs = parent::runquery_fetchMode("
                       select  staff_id, PersonID ,
					SUM(sum_tax) sum_tax,
					SUM(sum_tax_include) sum_tax_include , 
					SUM(BimeMoaf) sum_BimeMoaf 

			from
			( SELECT
                              pit.staff_id staff_id,ls.PersonID ,
						SUM(pit2.get_value +
						 	(pit2.diff_get_value * pit2.diff_value_coef)  ) sum_tax,
						SUM(pit.param1 + pit.diff_param1 * pit2.diff_value_coef ) sum_tax_include ,
						SUM(pit2.param6 + pit2.diff_param6 * pit2.diff_value_coef ) BimeMoaf
								
                        FROM HRM_limit_staff ls
                                 INNER JOIN ".$source_table." pit
                                         ON(pit.staff_id = ls.staff_id)
                                 LEFT OUTER JOIN HRM_payment_items pit2
                                        ON(pit2.staff_id = pit.staff_id AND 
                                           pit2.pay_year = pit.pay_year AND
                                           pit2.pay_month = pit.pay_month AND
                                           pit2.payment_type = ".NORMAL_PAYMENT." AND
                                           pit2.salary_item_type_id = pit.salary_item_type_id)
										   
							     

                        WHERE pit.pay_year >= ".$this->__START_NORMALIZE_TAX_YEAR." AND
                                  pit.pay_month >= ".$this->__START_NORMALIZE_TAX_MONTH." AND
                                  pit.salary_item_type_id IN(8) ".$TaxWhere."
                        GROUP BY pit.staff_id
                        UNION ALL 
                        select  pit.staff_id staff_id,ls.PersonID ,
                                SUM( pit.get_value +
                                   ( pit.diff_get_value * pit.diff_value_coef) ) sum_tax,
                                SUM(pit.param1) sum_tax_include , 0 BimeMoaf 

					from HRM_limit_staff ls 
                                                inner join HRM_payments p on ls.staff_id = p.staff_id
                                                inner join HRM_payment_items pit
                                                    on p.staff_id = pit.staff_id and
                                p.pay_year = pit.pay_year and
                                p.pay_month = pit.pay_month and
                                p.payment_type = pit.payment_type
                        where
                                pit.pay_year >= ".$this->__START_NORMALIZE_TAX_YEAR."  AND  pit.pay_month >= 1 AND
                                pit.payment_type !=1 AND  						
                                pit.salary_item_type_id IN(8)   

                        GROUP BY pit.staff_id
                        )
			tbl1

			GROUP BY staff_id
                        ");
			

	}
	
	/* اجراي query مربوط به استخراج سابقه مالياتي*/
	private function exe_tax_history() {
		if($this->backpay) {
			return true;
		}		
		
		if($this->__CALC_NORMALIZE_TAX) {
			$start_date = DateModules::shamsi_to_miladi($this->__START_NORMALIZE_TAX_YEAR."/".$this->__START_NORMALIZE_TAX_MONTH."/01") ; 			
			$w = "end_date IS NULL OR end_date = '0000-00-00' OR end_date > '$start_date'";
		} else {
			$w = "NOT((start_date > '".$this->month_end."') OR (end_date IS NOT NULL  AND  end_date != '0000-00-00' AND end_date < '".$this->month_start."'))";
		}
				
		$this->tax_history_rs = parent::runquery_fetchMode("
													SELECT sth.staff_id,ls.RefPersonID ,
														sth.start_date,
														sth.end_date,
														sth.tax_table_type_id,
														sth.payed_tax_value
													FROM HRM_limit_staff ls
														INNER JOIN HRM_staff_tax_history sth
																ON(ls.staff_id = sth.staff_id)
																
													WHERE ".$w."
													ORDER BY sth.staff_id,sth.start_date			
													");	
													
			  
													
	}
	
		/* اجراي query جداول مالياتي و انتقال آنها به يک ارايه */
	private function exe_taxtable_sql() {
		if($this->backpay) {
			return true;
		}

		$this->monitor(5);

		$tmp_rs = parent::runquery("
                        SELECT ttype.person_type,
                               ttype.tax_table_type_id,
                               ttable.from_date,
                               ttable.to_date,
                               titem.from_value,
                               titem.to_value,
                               titem.coeficient

                        FROM HRM_tax_table_types ttype
                             INNER JOIN HRM_tax_tables ttable
                                   ON(ttype.tax_table_type_id = ttable.tax_table_type_id AND 
								      from_date <= '".$this->month_end."' AND to_date >= '".$this->month_start."')
                             INNER JOIN HRM_tax_table_items titem
                                   ON(ttable.tax_table_id = titem.tax_table_id)

                        ORDER BY ttype.person_type,ttype.tax_table_type_id,ttable.from_date,titem.from_value
                        ");
				
					 
		for($i=0; $i<count($tmp_rs); $i++)
		{
			$this->tax_tables[$tmp_rs[$i]['tax_table_type_id']][] = array(
																		'from_date' => $tmp_rs[$i]['from_date'],
																		'to_date' => $tmp_rs[$i]['to_date'],
																		'from_value' => $tmp_rs[$i]['from_value'],
																		'to_value' => $tmp_rs[$i]['to_value'],
																		'coeficient'   => $tmp_rs[$i]['coeficient']);
		}
		
	}
	
	/* اجراي query براي استخراج سرفصل ماليات و بازنشتگي وبيمه*/
	private function exe_acc_info() {
		
		$tmp_rs = parent::runquery("
                        SELECT
								salary_item_type_id,                              
								sit.validity_start_date,
								sit.validity_end_date
								
                        FROM HRM_salary_item_types sit
                        WHERE
                             sit.salary_item_type_id IN (7,8)
						");
		
			
		for($i=0; $i<count($tmp_rs); $i++)
		{
			$this->acc_info[$tmp_rs[$i]['salary_item_type_id']] = array(
																		'validity_start_date' => $tmp_rs[$i]['validity_start_date'],
																		'validity_end_date' => $tmp_rs[$i]['validity_end_date']);
		}
		
	}
	
	///.................................................................... توابع مربوط به پرداخت های متفرقه ..............................
	
	//محاسبه قلم حقوقي برگشت حق بيمه جانبازان کارمند
	private function compute_salary_item2_29($add_value = 0) {
		
		$value = (( !empty($this->payment_items[$this->get_insure_salary_item_id()]['get_value']) ) ? $this->payment_items[$this->get_insure_salary_item_id()]['get_value'] : 0 ) + 
				 (( !empty($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value']) ) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value'] : 0 ) +
				 (( !empty($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value']) ) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value'] : 0 ) +
				 $add_value ;
		
		$key = SIT_RETURN_INSURE_AND_RETIRED_WOUNDED_PERSONS;//اين متغير صرفا جهت افزايش خوانايي کد اضافه شده است
		
		$this->payment_items[$key] = array(
										 'pay_year'            => $this->__YEAR,
										 'pay_month'           => $this->__MONTH,
										 'staff_id'            => $this->cur_staff_id,
										 'salary_item_type_id' => $key,
										 'get_value'           => 0,
										 'pay_value'           => $value,
										 'cost_center_id'      => 0,
										 'payment_type'        => NORMAL );				
	}
	
	//محاسبه قلم حقوقي برگشتي مقرري و بازنشستگي و بيمه خدمات درماني و بيمه ايران جانبازان کارمند
	private function compute_salary_item2_30() {
		//param1 : تعداد عادي
		//param2 : تعداد مازاد 1
		//param3 : تعداد مازاد 2
		
		if( $this->insureRow['staff_id'] == $this->cur_staff_id ){
			
			$param1 = $this->insureRow['ret_normal'];
			$param2 = $this->insureRow['ret_extra1'];
			$param3 = $this->insureRow['ret_extra2'];
			$param8 = $this->insureRow['ret_normal2'];
			

/*if($_SESSION['UserID'] == 'jafarkhani' && $this->__MONTH == 11  ) 
		{
			echo $this->insureRow['ret_extra1']."###"; die();
			
		}*/
			$own_normal = $this->insureRow['own_normal'];
			//baharrr $this->staffRow['person_type']
			$normal_value = (isset($this->salary_params[SPT_NORMAL_INSURE_VALUE][PERSON_TYPE_ALL]['value'])) ? $this->salary_params[SPT_NORMAL_INSURE_VALUE][PERSON_TYPE_ALL]['value'] : 0  ;
			$first_surplus_value = (isset($this->salary_params[SPT_FIRST_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'])) ? $this->salary_params[SPT_FIRST_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'] : 0 ;
			$second_surplus_value = (isset($this->salary_params[SPT_SECOND_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'])) ? $this->salary_params[SPT_SECOND_SURPLUS_INSURE_VALUE][PERSON_TYPE_ALL]['value'] : 0 ;
			$normal2_value = (isset($this->salary_params[SPT_NORMAL2_INSURE_VALUE][PERSON_TYPE_ALL]['value'])) ? $this->salary_params[SPT_NORMAL2_INSURE_VALUE][PERSON_TYPE_ALL]['value'] : 0 ;
			
			$insureCoef = ( $this->__YEAR > 1392  )  ? 2 : 1.65 ; 
					
			if($own_normal > 0 ){ 
							
				if(  $this->__YEAR <= 1390 && $this->sum_retired_include > 6606000 )
					$Rtv = 6606000 ; 
				else if ( $this->__YEAR > 1390 && $this->__YEAR < 1392 && $this->sum_retired_include > 7794000  ) 	
						$Rtv = 7794000 ;
					else if( $this->__YEAR > 1391 &&   $this->__YEAR < 1393 && $this->sum_retired_include > 9800000 )
					$Rtv = 9800000 ;
				else if( $this->__YEAR > 1392 && $this->sum_retired_include > 12178200 ) 
						$Rtv = 12178200 ;	
				else 	
					$Rtv = $this->sum_retired_include ; 
					
				$re_normal_value = ($Rtv * $insureCoef ) / 100 ;
							
			}
			else    {
				$re_normal_value = 0 ;
			}
			
		
			$insure_value = $re_normal_value + ($param2 * $first_surplus_value) + ($param3 * $second_surplus_value) + ($param8 * $normal2_value);
		}
		else {	
			$param1 = $param2 = $param3 = $param8 = $normal_value = $first_surplus_value = $second_surplus_value =  $normal2_value = 0 ; 
			$insure_value = 0 ; 
		}
		$value = $insure_value + (isset($this->payment_items[IRAN_INSURE]['get_value']) ? $this->payment_items[IRAN_INSURE]['get_value'] : 0 );
		
		
			
		
		$value += (isset($this->payment_items[$this->get_pension_salary_item_id()]['get_value']) ? $this->payment_items[$this->get_pension_salary_item_id()]['get_value'] : 0 ) + 
				  (isset($this->payment_items[$this->get_retired_salary_item_id()]['get_value']) ?  $this->payment_items[$this->get_retired_salary_item_id()]['get_value'] : 0 ) ;
				  
	   /*if($_SESSION['UserID'] == 'jafarkhani' && $this->__MONTH == 8  && $this->cur_staff_id == 311716 ) 
		{
			echo $value.'---fgfg---<br>' ; 		die(); 	
		} */
				 
		$key = RETURN_FIRST_MONTH_MOGHARARY;//اين متغير صرفا جهت افزايش خوانايي کد اضافه شده است
		
		
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => $key,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => $param1,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'param4'			   => $normal_value,
							 'param5'			   => $first_surplus_value,
							 'param6'			   => $second_surplus_value,
							 'param8'			   => $param8,
							 'param9'			   => $normal2_value,
							 'cost_center_id'      => 0,
							 'payment_type'        => NORMAL );
		
		return $payment_rec;
	}
	
	/*اضافه کار عادي*/
	private function compute_salary_item2_21() {
	
	    //param1 : نرخ اضافه کار
	    //param2 : حقوق مبنا + افزايش سنواتي + فوق العاده شغل + تفاوت تطبيق + حداقل دريافتي + فوق العاده شغل برجسته + فوق العاده جذب + فوق العاده تعديل
	    //param3 : تعداد ساعات اضافه کار
		//param4 :   حق مدیریت
		//اضافه کار تشويقي را محاسبه مي کند.
		/*
		*/    
	    $ManagementValue = 0 ; 
		if ( (($this->__YEAR < 1393  ) ||  ($this->__YEAR == 1393 &&  $this->__MONTH < 2)) && $this->staffRow['person_type'] == HR_EMPLOYEE  ) {
	 	    
			$extra_work_include_items = array(34 , 35 , 36);
					
			if ($this->PGLRow['initial_amount']) {
				$this->compute_salary_item2_28();
			}
			
			$param1 = 1 / 176;
		
			foreach ($extra_work_include_items as $salary_item){
				if($this->payment_items[$salary_item]['pay_value']>0)
					$param2 += $this->payment_items[$salary_item]['pay_value']/$this->payment_items[$salary_item]['time_slice'] ;
			}
			
			$param3 = $this->PGLRow['approved_amount'];
		
			$value = $param1 * $param2 * $param3;		
		
		}
		else if ( (($this->__YEAR < 1393  ) ||  ($this->__YEAR == 1393 &&  $this->__MONTH < 2)) && $this->staffRow['person_type'] == HR_CONTRACT  ) {
	 	    	
			// اضافه کار تشویقی برای افراد قراردادی
			/*if ($this->PGLRow['initial_amount']) {
				$this->compute_salary_item2_28();
			} */ 
			
			$param1 = 1.4 ;
			$param3 = $this->PGLRow['approved_amount'];
			
			$salary = $this->payment_items[605]['pay_value'] +
					  $this->payment_items[885]['pay_value'] ;	

			$param2 = $salary / $this->__MONTH_LENGTH;

			$value = $param1 * ($param3 / 7.33) * $param2 * (1 / $this->payment_items[605]['param4']);				
		
		}
		
		else {
						
	      $param1 = 1 / 176;
		  		// مبلغ حق مدیریت در param4  قرار می گیرد.	   
	     /* $ManagementValue = ( (isset($this->payment_items[10373]['param2'])) ? $this->payment_items[10373]['param2'] : 0  + 			  
							   (isset($this->payment_items[10373]['param3'])) ? $this->payment_items[10373]['param3'] : 0  + 
							   (isset($this->payment_items[10377]['param2'])) ? $this->payment_items[10377]['param2'] : 0  +
							   (isset($this->payment_items[10377]['param3'])) ? $this->payment_items[10377]['param3'] : 0   ) * $this->payment_items[10364]['pay_value'] ; */
		      
	      $param2 = $this->payment_items[10364]['pay_value']         +
					$this->payment_items[10366]['pay_value']    +
					$this->payment_items[10367]['pay_value']  ;  
	      
	      $param3 =  $this->PGLRow['approved_amount'];	      
	      $value = $param1 * $param2 * $param3;	      
	  }
		
		
		if ($this->staffRow['person_type'] == HR_EMPLOYEE  ) 
		{
			$keyITM = SIT_STAFF_EXTRA_WORK ; 
		
		} 
		elseif($this->staffRow['person_type'] == HR_CONTRACT ) 
		{
			$keyITM = 639 ; 
		} 
						
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => $keyITM ,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => $param1,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'param4'              => 0 ,
							 'cost_center_id'      => 0,
							 'payment_type'        => NORMAL );

		return $payment_rec;	
		
	}
	
	/*اضافه کار تشويقي*/
	private function compute_salary_item2_28() {
	
	    //param1 : نرخ اضافه کار
	    //param2 : حقوق مبنا + افزايش سنواتي + فوق العاده شغل + تفاوت تطبيق + حداقل دريافتي + فوق العاده شغل برجسته + فوق العاده جذب + فوق العاده تعديل
	    //param3 : تعداد ساعات اضافه کار
		//بدليل خاص بودن نحوه نگهداري اضافه کار تشويقي اين تابع و تابع مشابه اش براي هيات علمي به صورت خاص نوشته شده اند
		
		$extra_work_include_items = array(34 , 35 , 36);
	
		$param1 = 1 / 176;
	
		foreach ($extra_work_include_items as $salary_item){
			if($this->payment_items[$salary_item]['pay_value']>0)
				$param2 += $this->payment_items[$salary_item]['pay_value']/$this->payment_items[$salary_item]['time_slice'] ;
		}
	    
		$param3 = $this->PGLRow['initial_amount'];
	
	    $value = $param1 * $param2 * $param3;
	    
	    $key = SIT_STAFF_HORTATIVE_EXTRA_WORK;
	
		if(isset($this->payment_items[$key])) {
			$this->payment_items[$key]['pay_value'] += $value;				
			$this->payment_items[$key]['param3'] += $param3;
		}
		else {
			$this->payment_items[$key] = array(
								 'pay_year'            => $this->__YEAR,
								 'pay_month'           => $this->__MONTH,
								 'staff_id'            => $this->cur_staff_id,
								 'salary_item_type_id' => $key,
								 'get_value'           => 0,
								 'pay_value'           => $value,
								 'param1'              => $param1,
								 'param2'              => $param2,
								 'param3'              => $param3,
								 'cost_center_id'      => 0,
								 'payment_type'        => NORMAL );
		}
							 
		$this->update_sums($this->PGLRow , $value);
		return true;		
	}
	//............................ توابع روز مزد بیمه ای ........................................
	
	/* اضافه کار عادي*/
	private function compute_salary_item3_20(){
		//param1 : ضريب
		//param2 : تعداد ساعات اضافه کار
		//param3 : دستمزد روزانه

		$param1 = 1.4;
		$param2 = $this->PGLRow['FinalAmount'];


	/*	$salary = ( !empty($this->payment_items[1]['pay_value']) ? $this->payment_items[1]['pay_value'] : 0  )  +  
		          ( !empty($this->payment_items[2]['pay_value']) ? $this->payment_items[2]['pay_value'] : 0  )  +  
		          ( !empty($this->payment_items[9]['pay_value']) ? $this->payment_items[9]['pay_value'] : 0  )  +
				  ( !empty($this->payment_items[4]['pay_value']) ? $this->payment_items[4]['pay_value'] : 0  )  ; */
				  
				  /***********************************/
                $qry = "	select *

						from
						(

						SELECT
								w.staff_id,
								w.writ_id,
								w.writ_ver,
								w.execute_date,
								wsi.salary_item_type_id,
								wsi.value pay_value

						FROM HRM_limit_staff ls
								INNER JOIN HRM_smed sm 
                                                                    ON(ls.staff_id = sm.staff_id)
								INNER JOIN HRM_writs w 
                                                                    ON(w.writ_id = sm.writ_id AND w.writ_ver = sm.writ_ver AND 
                                                                       w.staff_id=sm.staff_id)
								LEFT OUTER JOIN HRM_writ_salary_items wsi 
                                                                    ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver AND
                                                                       w.staff_id = wsi.staff_id AND wsi.must_pay = 1)
								LEFT OUTER JOIN HRM_salary_item_types sit 
                                                                    ON(wsi.salary_item_type_id = sit.salary_item_type_id)

						WHERE     /*w.state = 3 AND*/ w.staff_id = ".$this->staffRow['staff_id']." AND
                                                          wsi.salary_item_type_id in (1,2,9,4 )

						union ALL

						SELECT
								w.staff_id,
								w.writ_id,
								w.writ_ver,
								w.execute_date,
								wsi.salary_item_type_id,
								wsi.value pay_value

						FROM HRM_mwv mwv
							 INNER JOIN HRM_writs w ON(mwv.writ_id = w.writ_id AND mwv.writ_ver = w.writ_ver
													AND mwv.staff_id=w.staff_id)
							 INNER JOIN HRM_limit_staff ls ON(w.staff_id = ls.staff_id)
                                                         
							 LEFT OUTER JOIN HRM_writ_salary_items wsi 
                                                                    ON(w.writ_id = wsi.writ_id AND 
                                                                       w.writ_ver = wsi.writ_ver
									AND wsi.staff_id=w.staff_id AND wsi.must_pay = 1)
                                                                        
							 LEFT OUTER JOIN HRM_salary_item_types sit 
                                                                    ON(wsi.salary_item_type_id = sit.salary_item_type_id)

						WHERE  /* w.state = 3 AND*/ w.staff_id = ".$this->staffRow['staff_id']." AND 
                                                        wsi.salary_item_type_id in (1,2,9,4 )

						) t

						ORDER BY execute_date DESC ,writ_id DESC ,writ_ver DESC
						" ; 
			
			$ResExtra = PdoDataAccess::runquery($qry) ; 
			$item1 = $item2 = $item9 = $item4 = 0  ; 
    	
			for($t=0;$t<count($ResExtra);$t++)
			{
				if( $ResExtra[0]['writ_id'] == $ResExtra[$t]['writ_id'] &&
					$ResExtra[0]['writ_ver'] == $ResExtra[$t]['writ_ver'] )
				{
					if($ResExtra[$t]['salary_item_type_id'] == 1 )
					   $item1  += $ResExtra[$t]['pay_value']; 
					
					if($ResExtra[$t]['salary_item_type_id'] == 2 )
					   $item2 += $ResExtra[$t]['pay_value']; 
					
					if($ResExtra[$t]['salary_item_type_id'] == 9 )
					   $item9 += $ResExtra[$t]['pay_value']; 
                                        
                    if($ResExtra[$t]['salary_item_type_id'] == 4 )
					   $item4 += $ResExtra[$t]['pay_value']; 
					
				}
				else 
					break;
			}
                        
                        $salary =  $item1 + $item2 + $item9 + $item4 ;
                        
                        
                /**********************************/
		$param3 = $salary / 30 ;
 

		//$param3 = $salary / $this->__MONTH_LENGTH;
 
		$value = $param1 * ($param2 / 7.33) * $param3;
	
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => 12 ,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => $param1,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'cost_center_id'      => 0,
							 'payment_type'        => NORMAL );

		return $payment_rec;		
	}	
	/*ماموریت*/
	private function compute_salary_item3_21($DailyMission=0){
		//param1 : 
		//param2 : تعداد روز ماموریت
		//param3 : مجموع اقلام 

		
		$param2 = $DailyMission;

	
		$salary = ( !empty($this->payment_items[1]['pay_value']) ? ( $this->payment_items[1]['pay_value'] * 30/31 ) : 0  )  +  
		          ( !empty($this->payment_items[2]['pay_value']) ? $this->payment_items[2]['pay_value'] : 0  )  +  
		          ( !empty($this->payment_items[9]['pay_value']) ? $this->payment_items[4]['pay_value'] : 0  )  ;

		$param3 = $salary / 30 /*$this->__MONTH_LENGTH*/;
 
		$value =  $param2 * $param3;
	
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => 24 ,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => 0,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'cost_center_id'      => 0,
							 'payment_type'        => NORMAL );

		return $payment_rec;		
	}	
	
	/* اضافه کار تشويقي*/
	private function compute_salary_item3_29(){
		//param1 : ضريب
		//param2 : تعداد ساعات اضافه کار
		//param3 : دستمزد روزانه
		//بدليل خاص بودن نحوه نگهداري اضافه کار تشويقي اين تابع و تابع مشابه اش براي هيات علمي به صورت خاص نوشته شده اند

		$param1 = 1.4;
		$param2 = $this->PGLRow['initial_amount'];
		
		$salary = $this->payment_items[SIT_WORKER_BASE_SALARY]['pay_value'] +
				  $this->payment_items[SIT_WORKER_ANNUAL_INC]['pay_value'] +
				  $this->payment_items[SIT_WORKER_DEVOTION_EXTRA]['pay_value'];	

		$param3 = $salary / $this->__MONTH_LENGTH;

		$value = $param1 * ($param2 / 7.33) * $param3;
		
		$key = SIT_WORKER_HORTATIVE_EXTRA_WORK;
		
		if(isset($this->payment_items[$key])) {
			$this->payment_items[$key]['pay_value'] += $value;				
			$this->payment_items[$key]['param2'] += $param2;
		}
		else {
			$this->payment_items[$key] = array(
								 'pay_year'            => $this->__YEAR,
								 'pay_month'           => $this->__MONTH,
								 'staff_id'            => $this->cur_staff_id,
								 'salary_item_type_id' => $key,
								 'get_value'           => 0,
								 'pay_value'           => $value,
								 'param1'              => $param1,
								 'param2'              => $param2,
								 'param3'              => $param3,
								 'cost_center_id'      => 0,
								 'payment_type'        => NORMAL );
		}

		$this->update_sums($this->PGLRow , $value);
		return true;		
	}
	
	//محاسبه قلم حقوقي برگشت حق بيمه جانبازان روزمزد بيمه اي
	//و برگشت بيمه تكميلي ايران
	private function compute_salary_item3_30() {
		// براي افراد روزمزد بيمه اي با توجه به اينكه قلم برگشت بازنشستگي ندارند
		//بيمه تكميلي ايران با اين قلم برگشت داده مي شود
		return $this->compute_salary_item2_29((isset($this->payment_items[IRAN_INSURE]['get_value'])) ? $this->payment_items[IRAN_INSURE]['get_value'] : 0 );
	}	
	
	//محاسبه قلم حقوقي برگشت حق بيمه جانبازان کارمند
	private function compute_salary_item1_25() {
		return $this->compute_salary_item2_29();
	}
	// اضافه کار عادی قراردادی
	private function compute_salary_item5_21() {
		return $this->compute_salary_item2_21();
	}
	
	//محاسبه قلم حقوقي برگشتي مقرري و بازنشستگي جانبازان کارمند
	private function compute_salary_item1_26() {
		return $this->compute_salary_item2_30();
	}
	
	//...........................................................................................
	
	static function calculate_mission($staff_id,$pay_year,$pay_month,$dayNo,$coef,&$IncludeSalary)
	{
		
		$qry = " select person_type from staff where staff_id = ".$staff_id ; 
		$res = PdoDataAccess::runquery($qry) ; 
				
		if(	$res[0]['person_type'] == 1 ) 
		{
			
			$qry = " select salary_item_type_id , (pay_value ) val 
						from payment_items
							where staff_id = $staff_id and pay_year = $pay_year and pay_month = $pay_month and salary_item_type_id in (1,6,22) 
					 group by salary_item_type_id " ; 
			
			$resItm = PdoDataAccess::runquery($qry) ; 
			
			
			
			if($pay_year == 1392 && $pay_month < 7 )
			{
				for($j=0;$j<count($resItm);$j++)
				{
					if(($resItm[$j]['salary_item_type_id'] == 1 ))
						$baseSalary = $resItm[$j]['val'];
					else if ($resItm[$j]['salary_item_type_id'] == 6) 
						$makhsos =  $resItm[$j]['val'] ;					
				}				
				$IncludeSalary = $baseSalary + $makhsos ; 
				$value = ( ( $baseSalary + $makhsos ) * $dayNo ) / 20 ; 
				
			}
			else if($pay_year < 1393 ) {
								
				if(($pay_year >= 1392 && $pay_month > 7) || $pay_year > 1392  )
					$baseValue =  3939372  ; 
				else 
					return 0 ; 
				$baseSalary=$makhsos=$jazb=0 ; 
				for($j=0;$j<count($resItm);$j++)
				{
					if(($resItm[$j]['salary_item_type_id'] == 1 ))
						$baseSalary = $resItm[$j]['val'];
					else if ($resItm[$j]['salary_item_type_id'] == 6) 
						$makhsos =  $resItm[$j]['val'] ;
					else if ($resItm[$j]['salary_item_type_id'] == 22)
						$jazb = $resItm[$j]['val'] ;
					
				}
				$IncludeSalary = $baseSalary + $makhsos + $jazb ; 
				$sumItm = ( $baseSalary + $makhsos + $jazb ) / 20 ; 
				
				if( $sumItm > ( 3939372 * 20 /100 ))
					$sumItm = ( 3939372 * 20 /100 ) ; 
				
				$value = $sumItm * $dayNo ; 				
								
			}			
			else {
			
				$baseSalary=$makhsos=$jazb=0 ; 
				for($j=0;$j<count($resItm);$j++)
				{
					if(($resItm[$j]['salary_item_type_id'] == 1 ))
						$baseSalary = $resItm[$j]['val'];
					else if ($resItm[$j]['salary_item_type_id'] == 6) 
						$makhsos =  $resItm[$j]['val'] ;
					else if ($resItm[$j]['salary_item_type_id'] == 22)
						$jazb = $resItm[$j]['val'] ;
					
				}
				$IncludeSalary = $baseSalary + $makhsos + $jazb ; 
				$sumItm = ( $baseSalary + $makhsos + $jazb ) / 20 ; 
				
				if( $sumItm > 945450 )
					$sumItm = 945450 ; 
				
				$value = $sumItm * $dayNo ; 												
			
			}
			
		}
		else
		{//	echo "-------------------------------<br>" ; 
					
			if($pay_year < 1393 ) {			
				$minSalary = manage_salary_params::get_salaryParam_value("", 2 , SPT_MIN_SALARY, DateModules::shamsi_to_miladi($pay_year."/".$pay_month."/01"));
			
				if($pay_month < 7 ) $day = 31 ;
					else if($pay_month > 6 && $pay_month < 12 ) $day = 30 ;
						else if($pay_month == 12 ) $day = 29 ;
						
				$param1 = $minSalary / 20 ; // 4900000
						
				$qry = " SELECT  insure_include , service_include
							FROM staff_include_history
								WHERE  staff_id = $staff_id and start_date <='".DateModules::shamsi_to_miladi($pay_year."/".$pay_month."/01")."' and
									(  end_date is null or end_date = '0000-00-00' or end_date >= '".DateModules::shamsi_to_miladi($pay_year."/".$pay_month."/$day")."' )" ;
				
				$res2 = PdoDataAccess::runquery($qry) ; 
				
				if($res2[0]['service_include'] == 1 )
					$param2 = manage_payment_calculation::sum_salary_items($pay_year,$pay_month,$staff_id,$res[0]['person_type'],1); 
					
				if($res2[0]['insure_include'] == 1 )	
					$param2 = manage_payment_calculation::sum_salary_items($pay_year,$pay_month,$staff_id,$res[0]['person_type'],2); 
				
		
				$param3 = ($param2 - $minSalary) / 50 ; 
				
				$IncludeSalary = $param2 ; 
				$param4 = $param1 + $param3 ;
		
				$value = ((( $param4  * $coef) + $param4 )  * intval($dayNo))  + ($param4  * ( $dayNo - intval($dayNo) ) )  ; 		// ضریب منطقه همیشه مقدار دارد ؟؟؟؟؟			
				
				//..................
				
				$qry = " select  sum(pay_value ) pval 
							from hrmstotal.payment_items
								where staff_id = $staff_id and pay_year = $pay_year and pay_month = $pay_month and salary_item_type_id in (10364 , 10366 , 10367) 
						  " ; 
				
				$result = PdoDataAccess::runquery($qry) ; 
				$MissionVal = $result[0]['pval'] / 20 ; 
				$value  = ($MissionVal > 724200)  ? 724200 : ($MissionVal) ;  
		
			}

			if($pay_year >= 1393 ) 
			{
				if($res[0]['person_type'] == 2 || $res[0]['person_type'] == 5  ) 
				{
				
					$qry = " select  sum(pay_value) pval 
								from hrmstotal.payment_items
									where staff_id = $staff_id and pay_year = $pay_year and pay_month = $pay_month and salary_item_type_id in (10364 , 10366 , 10367) 
							  " ; 
					
					$result = PdoDataAccess::runquery($qry) ; 
					$MissionVal = $result[0]['pval'] / 20 ; 
					$param3  = ($MissionVal > 724200)  ? 724200 : ($MissionVal) ;  
		$IncludeSalary = $result[0]['pval'] ;  
					$value = $param3 * $dayNo ; 
				
				}
				if($res[0]['person_type'] == 3) 
				{
						$param2 = manage_payment_calculation::sum_salary_items($pay_year,$pay_month,$staff_id,$res[0]['person_type'],2); 


			if($param2 <= 6000000)
						{
							$param3 = $param2 / 20 ; 							
						}
						else {
							$param3 = 6000000 /  20 ; 
							$param3 += ( $param2 - 6000000) / 50 ; 							
						}
						

						//.........................
																				
						$value = $param3 * $dayNo * $coef ; 			
/*if($staff_id == 882660 ) {
 echo "----".$param3."**".$dayNo."***".$coef."---".$value ;  	 die() ;  }*/

 
				}				
			
			}
		}	

		return $value ;
		
	}
	
	//............................. محاسبه مجموع قلمهای مشمول بازنشستگی یا بیمه ................................
	
	static function sum_salary_items($pay_year,$pay_month,$staff_id,$pTyp,$insureType="")
	{
		
		$where = "" ; 
		if($insureType == 1) $where = " and sit.retired_include = 1 " ; 
		if($insureType == 2) $where = " and sit.insure_include = 1 " ; 
		
		$qry = "select sum(pit.pay_value ) sval

				 from payment_items pit inner join salary_item_types sit
											on pit.salary_item_type_id = sit.salary_item_type_id

				 where pay_year = $pay_year and pay_month = $pay_month and staff_id = $staff_id  and payment_type = 1 ".$where ; 
		
		$res = PdoDataAccess::runquery($qry) ; 
		
		return $res[0]['sval'];
	}

}

?>
