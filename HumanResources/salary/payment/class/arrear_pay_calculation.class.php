<?php
//---------------------------
// programmer:	b.mahdipour
// Date:		93.7
//---------------------------
require_once '../../salary_params/class/salary_params.class.php';
require_once '../../person_org_docs/subtracts.class.php';

class manage_arrear_pay_calculation extends PdoDataAccess
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

	private $last_month_end;
	private $first_month_start;
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
	private $diff_rs;
	private $pension_rs;

	private $run_id; //شناسه اين اجرا
	private $expire_time; //مدت زماني که کاربر بين دو اجراي متوالي محاسبه حقوق بايد صبر کند

	private $backpay = false; //متغيري جهت مشخص کردن اينکه آيا محاسبه backpay صورت گيرد يا خير؟
	private $backpay_recurrence = 0; //در اين متغير مرتبه اجراي روال محاسبه حقوق در backpay مشخص مي شود
    private $MakeDiff = 0 ; // این متغیر جهت اینکه فقط یک مرتبه در برج 12 تفاوت های پرداخت محاسبه گردد
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
												
				if( isset($this->payment_items[$key]) ) { // اگر قبلا اين قلم در آرايه اقلام حقوقي وجود دارد
					$this->payment_items[$key]['pay_value'] += $fields['pay_value'] * $time_slice;					
					$this->payment_items[$key]['param4'] += $time_slice;
				}
				else {
                     
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
					'cost_center_id' => $this->staffRow['cost_center_id'],
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

		//	$this->process_subtract();
			//شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به وام و کسورو مزاياي ثابت cur_staff محاسبه شده باشد

			//$this->process_pay_get_lists();
			// شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به کشيک ، حق التدريس ، ماموريت ، اضافه
			//کار و کسور و مزاياي موردي فرد cur_staff محاسبه و دز payment_items قرار گرفته باشند
			
				//فراخواني تابع محاسبه بيمه تامين اجتماعي
			//$this->process_insure();
			
			//پردازش مربوط به بيمه خدمات درماني
			//$this->process_service_insure(); 
			
			if($this->__YEAR > 1391 ) {
				$this->sum_tax_include = $this->sum_tax_include - ((isset($this->payment_items[SIT_STAFF_REMEDY_SERVICES_INSURE]['get_value']) ? $this->payment_items[SIT_STAFF_REMEDY_SERVICES_INSURE]['get_value'] : 0 ) +
										 (isset($this->payment_items[SIT_PROFESSOR_REMEDY_SERVICES_INSURE]['get_value']) ? $this->payment_items[SIT_PROFESSOR_REMEDY_SERVICES_INSURE]['get_value'] : 0 ) + 
										 (isset($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value']) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_1]['get_value'] : 0 ) +
										 (isset($this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value']) ? $this->payment_items[SIT_AGE_AND_ACCIDENT_INSURE_2]['get_value'] : 0 ) + 
										 (isset($this->payment_items[9994]['get_value']) ? $this->payment_items[9994]['get_value'] : 0 ) + 
										 (isset($this->payment_items[10149]['get_value']) ? $this->payment_items[10149]['get_value'] : 0 ) + 
										 (isset($this->payment_items[IRAN_INSURE]['get_value']) ? $this->payment_items[IRAN_INSURE]['get_value'] : 0 ) +
										 (isset($this->payment_items[9964]['get_value']) ? $this->payment_items[9964]['get_value'] : 0 ) + 
										 (isset($this->payment_items[9998]['get_value']) ? $this->payment_items[9998]['get_value'] : 0) )  ; 
			
			}
	
			//پردازش مربوط به ماليات
			/*if($this->__CALC_NORMALIZE_TAX == 1) {				
				$this->process_tax_normalize(); 
			}else{
				$this->process_tax(); 
			} */
		
			//پردازش مربوط به مقرري ماه اول
			//$this->process_pension();
 
			//فراخواني تابع محاسبه بازنشتگي
			//$this->process_retire();

			//فراخواني تابع رير جهت مواردي غير از موارد هميشگي است که معمولا بنا بر نياز مشتري بايد نوشته شوند
			$this->process_custom();
 
            $this->save_correct_pay_to_DataBase();
   
			//ثبت حقوق محاسبه شده براي staff جاري در فايل
			//$this->save_to_DataBase();
            $this->exe_difference_sql();

    	//افزودن مبالغ difference در صورت محاسبه از طريق backpay
			$this->add_difference();
             
			//مقداردهي مجدد متغيرها براي محاسبه حقوق staff بعدي
			$this->initForNextStaff();
			
			
		} //end of writ while
		
		$this->epilogue(); 
		//$this->submit();  
		$this->unregister_run();  
		$this->statistics();
	
		return true;
	}
	
	//................................ محاسبات غیر حکمی ............................
	// پردازش مربوط به وام و کسور , مزاياي ثابت
	
	/*private function process_subtract() {
		
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
				$this->subRow['get_value'] *= ($distance / $this->__MONTH_LENGTH); //ضرب مزاياي ثابت در کارکرد ماهانه
			}
			else {				
				$entry_title_full = 'get_value';
				$entry_title_empty = 'pay_value';
			}

			if( $this->subRow['subtract_type'] == LOAN )  {
				$multiply = -1;
				$param1 = "'LOAN'";
			}
			else if($this->subRow['subtract_type'] == FIX_FRACTION ) {
				$multiply = 1;
				$param1 = "'FIX_FRACTION'";
			}
			else $multiply = 0;

			$temp_array = array(
			'pay_year' => $this->__YEAR,
			'pay_month' => $this->__MONTH,
			'staff_id' => $this->cur_staff_id,
			'salary_item_type_id' => $key,
			$entry_title_full => $this->subRow['get_value'],
			$entry_title_empty => 0,
			'param1' => $param1,
			'param2' => $this->subRow['subtract_id'],
			'param3' => NULL,
			'param4' => $this->subRow['remainder'] + $this->subRow['get_value']* $multiply, 
			'cost_center_id' => $this->staffRow['cost_center_id'],
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
/*			}

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

	}*/
	/*
		// پردازش کشيک و حق التدريس و اضافه کار و ...
	private function process_pay_get_lists() {
		//param5 list_id
		//param6 list_type
		$this->moveto_curStaff($this->pay_get_list_rs,'PGL');
		 
		while ($this->PGLRowID <= $this->pgRowCount  && $this->PGLRow['staff_id'] == $this->cur_staff_id) {

			if( !$this->validate_salary_item_id($this->PGLRow['validity_start_date'], $this->PGLRow['validity_end_date']) ) {				
				$this->PGLRow = $this->pay_get_list_rs->fetch(); 
				$this->PGLRowID++ ;
				continue;
			}

			$key = $this->PGLRow['salary_item_type_id']; //اين متغير صرفا جهت افزايش خوانايي کد تعريف شده است

			//فرخواني تابع مربوط به هر رديف ...
			//خروجي تابع بايد رديف قابل درج، قلم حقوقي ارسال شده در ماه باشد اين قسمت براي قلم هايي است که تابع دارند
			if( $this->PGLRow['salary_compute_type'] == SALARY_COMPUTE_TYPE_FUNCTION) { // تابعي
				$func_name = $this->PGLRow['function_name']; 
				
				$temp_array = & $this->$func_name();
			}
			else if( $this->PGLRow['salary_compute_type'] == SALARY_COMPUTE_TYPE_MULTIPLY) //ضريبي
			//در مورد ضريبي ها فرض مي شود که فيلد approved_amount همان ضريب باشد
			$temp_array = & $this->coef();
			else { //با فرض اينکه رديفهاي بدون تابع همگي مبلغ هستند
				if($this->PGLRow['list_type'] == PAY_GET_LIST || $this->PGLRow['list_type'] == GROUP_PAY_GET_LIST) {
					$entry_title_full = 'pay_value';
					$entry_title_empty = 'get_value';
				}
				else {
					$entry_title_full = 'get_value';
					$entry_title_empty = 'pay_value';
				}
				// param1 , param2 , param3 مبالغ ثابت null است
				$temp_array = array(
				'pay_year' => $this->__YEAR,
				'pay_month' => $this->__MONTH,
				'staff_id' => $this->cur_staff_id,
				'salary_item_type_id' => $key,
				$entry_title_full => $this->PGLRow['value'],
				$entry_title_empty => 0,
				'cost_center_id' => $this->staffRow['cost_center_id'],
				'payment_type' => NORMAL
				);
			}
			$temp_array['param5'] = $this->PGLRow['list_id'];
			$temp_array['param6'] = $this->PGLRow['list_type'];

			if( isset($this->payment_items[$key]) ) {
				if(in_array($key , array(SIT_STAFF_EXTRA_WORK,
        								 SIT_WORKER_EXTRA_WORK,
        								 SIT_STAFF_HORTATIVE_EXTRA_WORK,
        								 SIT_WORKER_HORTATIVE_EXTRA_WORK))){
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
						
			$this->extra_pay_value = ($key == SIT_WORKER_EXTRA_WORK ) ? $temp_array['pay_value'] : 0 ; 
			
			$this->PGLRow = $this->pay_get_list_rs->fetch(); 
			$this->PGLRowID++ ;
		}
	}
	*/
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
		
		/*if($this->staffRow['person_type'] == HR_WORKER  && $this->staffRow['Over25'] == 1  && $this->extra_pay_value != 0 &&
				 ( ($this->__YEAR == 1392 && $this->__MONTH >= 8) || $this->__YEAR > 1392  ) )
		{
			
			$this->sum_insure_include += $this->extra_pay_value ;
		}	*/
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
		'cost_center_id' => $this->staffRow['cost_center_id'],
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
		'cost_center_id' => $this->staffRow['cost_center_id'],
		'payment_type' => NORMAL );
	}
	
		//پردازش ماليات تعديل شده
	private function process_tax_normalize() {

       
		$key = $this->get_tax_salary_item_id();
		
        /*بدليل اينكه در هنگام محاسبه backpay حقوق مشمول ماليات فرد تغيير مي كند لذا اين مبلغ را
        در جدول back_payment_items درج مي كنيم تا در محاسبه مبلغ مشمول ماليات در سال
        تاثير بگذارد*/
       if( $this->__MONTH < $this->last_month ) {


               $this->payment_items[$key] = array(
                                                    'pay_year' => $this->__YEAR,
                                                    'pay_month' => $this->__MONTH,
                                                    'staff_id' => $this->cur_staff_id,
                                                    'salary_item_type_id' => $key,
                                                    'get_value' => 0,
                                                    'pay_value' => 0,
                                                    'diff_get_value' => 1,//براي اينكه اين قلم هم در پايگاه داده درج شود
                                                    'cost_center_id' => $this->staffRow['cost_center_id'],
                                                    'payment_type' => NORMAL,
                                                    'param1' => $this->sum_tax_include);
                return true;
       }
		
		 /*
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

		$year_avg_tax_include = ( (($this->cur_staff_id == $this->taxRow['staff_id']) ? $this->taxRow['sum_tax_include'] : 0 ) + $this->sum_tax_include + $this->taxHisRow['payed_tax_value']) / ($this->__MONTH - $this->__START_NORMALIZE_TAX_MONTH + 1);
		$sum_normalized_tax = $tax_table_type_id = 0; //متغيري جهت نگهداري ماليات تعديل شده براي cur_staff در تمام طول سال

		reset($this->tax_tables);

		for($m = $this->__START_NORMALIZE_TAX_MONTH; $m <= $this->__MONTH; $m++ ) {
			$begin_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/1") ; 			
			$end_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/".DateModules::DaysOfMonth($this->__YEAR,$m)) ;	

			while ($this->taxHisRowID <= $this->taxHisRowCount && $this->taxHisRow['staff_id'] == $this->cur_staff_id) {
				
				if( ( $this->taxHisRow['end_date'] != null  && $this->taxHisRow['end_date'] != '0000-00-00' ) && 
						DateModules::CompareDate($this->taxHisRow['end_date'],$begin_month_date) == -1 ) { 					
					$this->taxHisRow = $rs->fetch(); 
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
				$pay_mid_month_date = DateModules::shamsi_to_miladi($this->__YEAR."/".$m."/15") ;
				if( DateModules::CompareDate($pay_mid_month_date, $tax_table_row['from_date']) != -1 && 
					DateModules::CompareDate($pay_mid_month_date,$tax_table_row['to_date']) != 1 ) { 
										
					if( $year_avg_tax_include >= $tax_table_row['from_value'] && $year_avg_tax_include <= $tax_table_row['to_value'] ) {
						$sum_normalized_tax += ( $year_avg_tax_include - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];						
						
					}
					else if($year_avg_tax_include > $tax_table_row['to_value']){
						$sum_normalized_tax += ( $tax_table_row['to_value'] - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];											
					}
				}
				
			}
		}
			
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
		'cost_center_id' => $this->staffRow['cost_center_id'],
		'payment_type' => NORMAL );

		$this->payment_items[$key]['param1'] = $this->sum_tax_include; //مجموع حقوق مشمول ماليات در ماه جاري
		$this->payment_items[$key]['param2'] = $sum_normalized_tax; //مالياتي که از ابتدا تا کنون بايد پرداخت مي شده است
		$this->payment_items[$key]['param3'] = $this->taxRow['sum_tax'] + $normalized_tax; // مالياتي که از ابتدا تا کنون پرداخت شده است
		$this->payment_items[$key]['param4'] = 2; //اگر محاسبه بدون تعديل انجام شده است 1 و  اگر با تعديل انجام گرديده 2 قرار مي دهيم
		$this->payment_items[$key]['param5'] = $tax_table_type_id; //آخرين جدول مالياتي که در محاسبه ماليات استفاده شده است گذاشته مي شود
	

    }
	
	//پردازش ماليات معمولي
	private function process_tax() {
      
		/*if($this->backpay) {
			return true;
		}*/
		/*اين فرد مشمول ماليات نمي باشد*/
		if(empty($this->staffRow['tax_include'])) {
			return ;
		}
		$key = $this->get_tax_salary_item_id();

if( $this->__MONTH < $this->last_month ) {


               $this->payment_items[$key] = array(
                                                    'pay_year' => $this->__YEAR,
                                                    'pay_month' => $this->__MONTH,
                                                    'staff_id' => $this->cur_staff_id,
                                                    'salary_item_type_id' => $key,
                                                    'get_value' => 0,
                                                    'pay_value' => 0,
                                                    'diff_get_value' => 1,//براي اينكه اين قلم هم در پايگاه داده درج شود
                                                    'cost_center_id' => $this->staffRow['cost_center_id'],
                                                    'payment_type' => NORMAL,
                                                    'param1' => $this->sum_tax_include);
                return true;
       }
		
		if( !$this->validate_salary_item_id($this->acc_info[$key]['validity_start_date'], $this->acc_info[$key]['validity_end_date']) ) {
			return ;
		}
		/*$this->moveto_curStaff($this->tax_rs,'TAX');
		$this->moveto_curStaff($this->tax_history_rs,'TAXHIS');
		$tax_table_type_id = $this->taxHisRow['tax_table_type_id'];
		if(! key_exists($tax_table_type_id, $this->tax_tables)) {
			return ;				
		}
		
			
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
*/
		//انتصاب ماليات تعديل شده به  payment_items
		$this->payment_items[$key] = array(
		'pay_year' => $this->__YEAR,
		'pay_month' => $this->__MONTH,
		'staff_id' => $this->cur_staff_id,
		'salary_item_type_id' => $key,
		'get_value' => ( $this->sum_tax_include * 0.1 ) ,
		'pay_value' => 0,
		'cost_center_id' => $this->staffRow['cost_center_id'],
		'payment_type' => NORMAL );

		$this->payment_items[$key]['param1'] = $this->sum_tax_include; //مجموع حقوق مشمول ماليات در اين ماه
		$this->payment_items[$key]['param3'] = $this->taxRow['sum_tax'] + ( $this->sum_tax_include * 0.1 ); //مالياتي که از ابتدا تا کنون رداخت شده است
		$this->payment_items[$key]['param4'] = 1; //اگر محاسبه بدون تعديل انجام شده است 1 و  اگر با تعديل انجام گرديده 2 قرار مي دهيم
//		$this->payment_items[$key]['param5'] = $tax_table_type_id; //آخرين جدول مالياتي که در محاسبه ماليات استفاده شده است گذاشته مي شود
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
			'cost_center_id' => $this->staffRow['cost_center_id'],
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
			//$this->sum_retired_include -= $this->payment_items[$this->get_pension_salary_item_id()]['get_value']  ;
			
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
		'cost_center_id' => $this->staffRow['cost_center_id'],
		'payment_type' => NORMAL );
	}

	
	/*اجراي متدهاي اضافي بنا بر نياز مشتري*/
	private function process_custom(){
		//cur_staff جانباز است و بايد قلم برگشتي بيمه تامين اجتماعي براي او محاسبه شود
		if($this->staffRow['sacrifice'] > 0 || $this->staffRow['freedman'] > 0 || $this->staffRow['shohadachild'] > 0 ) {
			switch ($this->staffRow['person_type']) {
				case HR_PROFESSOR : $this->compute_salary_item1_25(); break;
				case HR_EMPLOYEE : $this->compute_salary_item2_29(); break;
				case HR_WORKER : $this->compute_salary_item3_30(); break;
				case HR_CONTRACT : $this->compute_salary_item3_30(); break; 
			}			
		}
		
		//cur_staff جانباز است و بايد مبلغ مقرري ، بيمه خدمات درماني ، بازنشستگي و بيمه تكميلي ايران به او برگشت داده شود
		if(($this->staffRow['sacrifice'] > 0 || $this->staffRow['freedman'] > 0 || $this->staffRow['shohadachild'] > 0 ) && 
		   ($this->staffRow['person_type'] == HR_PROFESSOR || $this->staffRow['person_type'] == HR_EMPLOYEE )) {
			if($this->staffRow['person_type'] == HR_PROFESSOR) {
				$temp_array = &$this->compute_salary_item1_26();
			}
			else if($this->staffRow['person_type'] == HR_EMPLOYEE ) {
				$temp_array = &$this->compute_salary_item2_30(); //  اگر افراد قراردادی هم داشته باشند فقط قسمت بیمه این تابع را براش بگذاز
			}
			$key = $temp_array['salary_item_type_id'];
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

        if( $this->__MONTH < $this->last_month )
        {
            return ;
        }
		


		$result = false ;				
		unset($this->payment_items); // حذف کلیه رکورد های قبلی
        
		$this->moveto_curStaff($this->diff_rs,'DIFF');
		while (!$this->diffRowID <= $this->diffRowCount && $this->diffRow['staff_id'] == $this->cur_staff_id) {
						
			$key = $this->diffRow['salary_item_type_id']; //اين خط فقط بدليل افزايش خوانايي اضافه شده است
			if($this->diffRow['get_value_diff'] || $this->diffRow['pay_value_diff'])
				$result = true ;
		
				$this->payment_items[$key] = array(
				'pay_year' => $this->__YEAR,
				'pay_month' => $this->__MONTH,
				'staff_id' => $this->diffRow['staff_id'],
				'salary_item_type_id' => $key,
				'pay_value' => 0,
				'get_value' => 0,
				'get_value' =>$this->diffRow['get_value_diff'],
				'pay_value' =>$this->diffRow['pay_value_diff'],
				'param1' =>$this->diffRow['param1_diff'],
				'param2' =>$this->diffRow['param2_diff'],
				'param3' =>$this->diffRow['param3_diff'],
				'param4' =>$this->diffRow['param4_diff'],
				'param5' =>$this->diffRow['param5_diff'],
				'param6' =>$this->diffRow['param6_diff'],
				'param7' =>$this->diffRow['param7_diff'],
				'param8' =>$this->diffRow['param8_diff'],
				'param9' =>$this->diffRow['param9_diff'],
				'cost_center_id' => $this->staffRow['cost_center_id'],
				'payment_type' => NORMAL);
			//}
			
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

			$this->diffRow = $this->diff_rs->fetch() ;
			$this->diffRowID++ ; 
					
		}

           //....................................................افزودن به جدول مربوط به پرداخت دیون ............................
         
        //....بدست اوردن بالاترین ورژن معتبر برای دیون .....................
         $VerRes = parent::runquery(" select MAx(arrear_ver) 
                                        from Arrear_payments
                                            where pay_year =".$this->__YEAR." and staff_id = ".$this->cur_staff_id." and payment_type = 1 and state = 2 ");

        if(empty($VerRes[0]['MaxVer']))
			$VerRes[0]['MaxVer'] = 0 ; 
		
		$PayVer = $VerRes[0]['MaxVer'] ; 
		
        //..... حذف ورژن هایی که تائید نشده اند
        parent::runquery(" delete pit.* from Arrear_payment_items pit inner join Arrear_payments p
                                                                    on pit.pay_year = p.pay_year and pit.staff_id = p.staff_id and pit.arrear_ver = p.arrear_ver and p.state = 1
                                where pit.pay_year =".$this->__YEAR." and pit.staff_id = ".$this->cur_staff_id." and pit.payment_type = 9 and pit.arrear_ver > ". $VerRes[0]['MaxVer']);
		
		parent::runquery(" delete from corrective_payment_writs
                              where pay_year = ".$this->__YEAR." and  staff_id = ".$this->cur_staff_id." and arrear_ver > ".$VerRes[0]['MaxVer'] ) ; 
		
        parent::runquery(" delete from Arrear_payments
                                    where pay_year =".$this->__YEAR." and staff_id = ".$this->cur_staff_id." and payment_type = 9 and arrear_ver > ". $VerRes[0]['MaxVer']);
        
		$PayVer++ ;


		//....................................................................................
		        //نوشتن آرايه staff_writs در فايل payment_writs
		reset($this->staff_writs);
		//$writ_row = '';

		foreach ($this->staff_writs[$this->cur_staff_id] as $writ) {

			parent::runquery(" insert into hrmstotal.corrective_payment_writs (writ_id,writ_ver,staff_id,pay_year,pay_month,payment_type,arrear_ver) values
							(".$writ['writ_id'].",".$writ['writ_ver'].",".$this->cur_staff_id.",".$this->__YEAR.",".$this->__MONTH.",9,".$PayVer." )" , array()) ;
               
			if( parent::AffectedRows() == 0  )
			{
				$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول احکام اصلاحی');				
				ob_clean();
				return ;
			}

		}
		
		//..........................................
      
		//نوشتن payment در فايل
		$payment_row = $this->cur_staff_id . ',' .
		$this->__YEAR . ',' .'12,'.
        $PayVer . ',' .
		$writ['writ_id'] . ',' .
		$writ['writ_ver'] . ',9,' .		
		$this->__MSG.',' .
		$this->staffRow['bank_id'].',' .
		$this->staffRow['account_no'].','.
		PAYMENT_STATE_NORMAL.",'".
		DateModules::NowDateTime()."'";

		$file_line2 = str_replace(',,',',\N,',$payment_row); //براي اصلاح مقادير null
		$file_line2 = str_replace(',,',',\N,',$file_line2); //براي اصلاح مقادير null

		parent::runquery(" insert into Arrear_payments (staff_id,pay_year,pay_month,arrear_ver,writ_id,writ_ver,payment_type,message,
						   bank_id,account_no,state ,calc_date ) value (".$file_line2.") ", array()) ;

		if( parent::AffectedRows() == 0  )
		{
			$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول پرداختهای دیون','End');
						
			return ;
		}
		
//..............................................................................
             //نوشتن آرايه paymnet_items در فايل
		ob_start();
		$pure_pay = 0; //متغيري جهت نگهداري خالص پرداختي
		reset($this->payment_items);

		foreach ($this->payment_items as $pay_row) {

			if( ( $pay_row['pay_value']==0 && $pay_row['get_value']==0 &&
				(!empty($pay_row['diff_pay_value']) && $pay_row['diff_pay_value']  ==0 ) &&
				(!empty($pay_row['diff_get_value']) && $pay_row['diff_get_value']  ==0 )  &&
                        $pay_row['salary_item_type_id']!= SIT_PROFESSOR_RETIRED &&
                        $pay_row['salary_item_type_id']!=SIT_STAFF_RETIRED ) || 
					( $pay_row['pay_value']==0 && $pay_row['get_value']==0 &&
				     ((!empty($pay_row['diff_pay_value']) && $pay_row['diff_pay_value']  ==0 ) || !isset($pay_row['diff_pay_value']) ) &&
			   	     ((!empty($pay_row['diff_get_value']) && $pay_row['diff_get_value']  ==0 ) || !isset($pay_row['diff_pay_value']) ) ))				
                  continue;
			
			
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
                $pay_row['pay_year'].','.'12,'.
                $pay_row['staff_id'].','.
                $PayVer .','.
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
                $pay_row['cost_center_id'].','.
                '9,'.
                $pay_row['diff_value_coef'].'),';

			echo chr(10);

			$pure_pay += $pay_row['pay_value'] + ($pay_row['diff_pay_value'] * $pay_row['diff_value_coef']) - $pay_row['get_value'] - ($pay_row['diff_get_value'] * $pay_row['diff_value_coef']);
		}

        //...............................................................................
        $file_line = str_replace(',,',',\N,',ob_get_clean()); //براي اصلاح مقادير null
		$file_line = str_replace(',,',',\N,',$file_line); //براي اصلاح مقادير null

        $file_line = substr($file_line, 0, (strlen($file_line)-2)) ;

        parent::runquery("insert into Arrear_payment_items (diff_get_value, diff_pay_value, pay_year,pay_month, staff_id, arrear_ver,
                            salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
                            diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
                            cost_center_id, payment_type, diff_value_coef ) values ".$file_line." " , array() ) ;
     
        if( parent::AffectedRows() == 0 )
        {
            $this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول اقلام حقوقی دیون','End');
            ob_clean();
            return ;
        }
        
		$qry = " SELECT if(sit.tax_include = 1 , sum(pay_value) , 0 )  tv , if(sit.retired_include = 1 , sum(pay_value) , 0 )  rv
					FROM Arrear_payment_items pit inner join salary_item_types sit
                                    on pit.salary_item_type_id = sit.salary_item_type_id
						WHERE staff_id = ".$this->cur_staff_id." and pay_year = ". $this->__YEAR ." and arrear_ver =".$PayVer  ; 
		
		$resTax = PdoDataAccess::runquery($qry) ; 
		$taxVal = $resTax[0]['tv'] * 0.1 ; 
						
		$key = $this->get_tax_salary_item_id();
		
		parent::runquery("insert into Arrear_payment_items (diff_get_value, diff_pay_value, pay_year, pay_month,staff_id, arrear_ver,
															salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
															diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
															cost_center_id, payment_type, diff_value_coef ) values (0,0,".$this->__YEAR.",12,".$this->cur_staff_id.",".$PayVer.",
															".$key.",0,".$taxVal.",
															".$resTax[0]['tv'].",0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,".$this->staffRow['cost_center_id'].",9,1	) " , array() ) ;
       
        if( parent::AffectedRows() == 0 )
        {
            $this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول اقلام حقوقی دیون','End');
            ob_clean();
            return ;
        }
				        //..............................بازنشستگی ...............................
		$Rkey = $this->get_retired_salary_item_id();
		
		$param1 = $this->salary_params[SPT_RETIREMENT_VALUE][PERSON_TYPE_ALL]['value'];
		$param5 = $this->salary_params[SPT_RETIREMENT_EMPLOYER_VALUE][PERSON_TYPE_ALL]['value'];
						
		$RetVal = round($param1 * $resTax[0]['rv'] * $this->get_retired_coef() * $this->staffRow['retired_include']);
		
		$param2 = $resTax[0]['rv'];
		
		parent::runquery("insert into Arrear_payment_items (diff_get_value, diff_pay_value, pay_year, pay_month,staff_id, arrear_ver,
															salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
															diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
															cost_center_id, payment_type, diff_value_coef ) values (0,0,".$this->__YEAR.",12,".$this->cur_staff_id.",".$PayVer.",
															".$Rkey.",0,".$RetVal.",
															".$param1.",".$param2.",0,0,".$param5.",0,0,0,0,0,0,0,0,0,0,0,0,0,".$this->staffRow['cost_center_id'].",9,1	) " , array() ) ;
       
        if( parent::AffectedRows() == 0 )
        {
            $this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول اقلام حقوقی دیون','End');
            ob_clean();
            return ;
        }
		//.....................................................................
        $this->log('SUCCESS',$pure_pay,'End');
	 	
		return true;
		
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
		if( $this->__MONTH < $this->last_month || $this->MakeDiff == 1 )
        {
            return ; 
        }
		
		
		//parent::runquery('DROP TABLE IF EXISTS temp_done_payments') ; 
		parent::runquery('TRUNCATE Arrear_done_payments');
		parent::runquery('                     
					    insert into Arrear_done_payments
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

                       FROM  Arrear_limit_staff ls
                                  INNER JOIN  payment_items pit
                                          ON(ls.staff_id = pit.staff_id)
                                  INNER JOIN salary_item_types sit
                                  		  ON(pit.salary_item_type_id = sit.salary_item_type_id AND sit.backpay_include = 1)
                       WHERE  sit.compute_place = 1  AND pit.pay_month >= '.$this->__BACKPAY_BEGIN_FROM.' AND
                                  pit.pay_year='.$this->__YEAR.' AND
                                  (pit.payment_type = '.NORMAL_PAYMENT.'
                                  OR ('.$this->__YEAR.'=1388 AND pit.pay_month = 3 AND pit.payment_type =13 ) 
                                  )
                       GROUP BY pit.staff_id,
                                pit.salary_item_type_id,
								sit.effect_type;
                ') ; 
		
		//parent::runquery(' ALTER TABLE temp_done_payments ADD INDEX(staff_id,salary_item_type_id); ') ; 
		//parent::runquery(' DROP TABLE IF EXISTS temp_must_payments;') ; 
		parent::runquery('TRUNCATE Arrear_must_payments');
		parent::runquery(' /*CREATE  TABLE temp_must_payments  AS*/
								insert into Arrear_must_payments
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

							FROM corrective_payment_items pit
									INNER JOIN salary_item_types sit
											ON(pit.salary_item_type_id = sit.salary_item_type_id AND sit.backpay_include = 1)

							GROUP BY pit.staff_id,
									 pit.salary_item_type_id,
									 sit.effect_type;') ; 
		//parent::runquery('ALTER TABLE temp_must_payments ADD INDEX(staff_id,salary_item_type_id);') ; 
		
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

                        FROM Arrear_must_payments bpit
                             LEFT OUTER JOIN Arrear_done_payments pit
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
                        FROM Arrear_done_payments pit
                             LEFT OUTER JOIN Arrear_must_payments bpit
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

        $this->MakeDiff  = 1 ; 
				
	}

    private function save_correct_pay_to_DataBase() {
        //نوشتن آرايه paymnet_items در فايل
		ob_start();
		//$pure_pay = 0; //متغيري جهت نگهداري خالص پرداختي
		reset($this->payment_items);

		foreach ($this->payment_items as $pay_row) {

			if( $pay_row['pay_value']==0 && $pay_row['get_value']==0 &&
				(!empty($pay_row['diff_pay_value']) && $pay_row['diff_pay_value']  ==0 ) &&
				(!empty($pay_row['diff_get_value']) && $pay_row['diff_get_value']  ==0 )  &&
                        $pay_row['salary_item_type_id']!= SIT_PROFESSOR_RETIRED &&
                        $pay_row['salary_item_type_id']!=SIT_STAFF_RETIRED )
                  continue;
                
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
                $pay_row['cost_center_id'].','.
                $pay_row['payment_type'].','.
                $pay_row['diff_value_coef'].'),';

			echo chr(10);

			//$pure_pay += $pay_row['pay_value'] + ($pay_row['diff_pay_value'] * $pay_row['diff_value_coef']) - $pay_row['get_value'] - ($pay_row['diff_get_value'] * $pay_row['diff_value_coef']);
		}	
		

//...............................................................................
        $file_line = str_replace(',,',',\N,',ob_get_clean()); //براي اصلاح مقادير null
		$file_line = str_replace(',,',',\N,',$file_line); //براي اصلاح مقادير null
        
        $file_line = substr($file_line, 0, (strlen($file_line)-2)) ;

        parent::runquery("insert into corrective_payment_items (diff_get_value, diff_pay_value, pay_year, pay_month, staff_id,
                            salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
                            diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
                            cost_center_id, payment_type, diff_value_coef ) values ".$file_line." " , array() ) ;
        //echo parent::GetLatestQueryString() ; die() ;
        if( parent::AffectedRows() == 0 )
        {           
            $this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول اقلام حقوقی اصلاحی');
            ob_clean();
            return ;
        }



		return true;
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
			$pay_row['cost_center_id'].','.
			$pay_row['payment_type'].','.			
			$pay_row['diff_value_coef'].'),';
			
			echo chr(10);

			$pure_pay += $pay_row['pay_value'] + ($pay_row['diff_pay_value'] * $pay_row['diff_value_coef']) - $pay_row['get_value'] - ($pay_row['diff_get_value'] * $pay_row['diff_value_coef']);
		}		
		
		/*خطا : حقوق فرد منفي شده است لذا ساير قسمتها براي او انجام نمي شود*/
		if($pure_pay < 0 && !$this->backpay) { // ماه آخر بود
			if(!$this->__CALC_NEGATIVE_FICHE) {								
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
		
		foreach ($this->staff_writs[$this->cur_staff_id] as $writ) {
				 
			parent::runquery(" insert into hrmstotal.payment_writs (writ_id,writ_ver,staff_id,pay_year,pay_month,payment_type) values 
							(".$writ['writ_id'].",".$writ['writ_ver'].",".$this->cur_staff_id.",".$this->__YEAR.",".$this->last_month.",".NORMAL.")" , array(),$pdo ) ; 
			 	
               /* echo parent::GetLatestQueryString() ;
                die();*/
			if( parent::AffectedRows() == 0  )
			{
				$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول احکام مورد استفاده در ماه جاری ');
				$pdo->rollBack();	
				ob_clean();				
				return ;
			}
			
			/*$writ_row .= $writ['writ_id'] . ',' .
			$writ['writ_ver'] . ',' .
			$this->cur_staff_id . ',' .
			$this->__YEAR . ',' .
			$this->last_month . ',' .
			NORMAL . ',' .
			$this->__MSG.chr(10);*/
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
		NORMAL . ',' .
		$this->__MSG.',' .
		$this->staffRow['bank_id'].',' .
		$this->staffRow['account_no'].','.
		PAYMENT_STATE_NORMAL.",'".
		DateModules::NowDateTime()."'";

		$file_line2 = str_replace(',,',',\N,',$payment_row); //براي اصلاح مقادير null
		$file_line2 = str_replace(',,',',\N,',$file_line2); //براي اصلاح مقادير null

		parent::runquery(" insert into payments (staff_id,pay_year,pay_month,writ_id,writ_ver,start_date,end_date,payment_type,message,
						   bank_id,account_no,state ,calc_date ) value (".$file_line2.") ", array(),$pdo) ; 
			
            //echo parent::GetLatestQueryString() ; die();
		if( parent::AffectedRows() == 0  )
		{
			$this->log('FAIL' ,'خطا در افزودن اطلاعات به جدول پرداختها ');
			$pdo->rollBack();
			ob_clean();				
			return ;
		}
		
		}
				
//fwrite($this->payment_file_h,$file_line);
//if($this->backpay)
	//	$tblName =  "back_payment_items" ;
	//else
		$tblName = "corrective_payment_items";
	$file_line = substr($file_line, 0, (strlen($file_line)-2)) ;
	
	parent::runquery("insert into ".$tblName." (diff_get_value, diff_pay_value, pay_year, pay_month, staff_id,
                        salary_item_type_id, pay_value, get_value, param1, param2, param3,param4, param5, param6, param7, param8, param9,
                        diff_param1,diff_param2,diff_param3,diff_param4,diff_param5,diff_param6,diff_param7,diff_param8,diff_param9,
                        cost_center_id, payment_type, diff_value_coef ) values ".$file_line." " , array(),$pdo ) ; 
	//echo parent::GetLatestQueryString() ; die() ; 
	if( parent::AffectedRows() == 0 )
	{
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
		
		//fclose($this->payment_items_file_h);		
		if( $this->__MONTH == $this->last_month )
        {
			//fclose($this->payment_file_h);
			//fclose($this->subtract_file_h);
			//fclose($this->subtract_flow_file_h);
			//fclose($this->payment_writs_file_h);			
			fwrite($this->fail_log_file_h, '</table></cenetr></body></html>');
			fwrite($this->success_log_file_h, '</table></cenetr></body></html>');
			fclose($this->fail_log_file_h);
			fclose($this->success_log_file_h);

			//chmod(HR_TemlDirPath.'payment_file.txt',0777);
			//chmod(HR_TemlDirPath.'payment_items_file.txt',0777);
			//chmod(HR_TemlDirPath.'payment_writs_file.txt',0777);
			//chmod(HR_TemlDirPath.'subtract_file.txt',0777);
			//chmod(HR_TemlDirPath.'subtract_flow_file.txt',0777);
			chmod('../../../HRProcess/arrear_fail_log.php',0777);
			chmod('../../../HRProcess/arrear_success_log.php',0777);		
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
		/*if($this->backpay)
		return ;*/
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
	private function empty_corrective_tables()
	{
		parent::runquery("TRUNCATE corrective_payment_items") ;
		return;		
	}
//............................................................. محاسبه حقوق با انجام فرآیند back Pay...............................................
		
	public function run_back()
	{
		//در اين تابع فرض براين است که سال مالي با سال شمسي مطابقت دارد
		$this->empty_corrective_tables() ;
		
		$this->last_month = $this->__MONTH;
		$this->last_month_end = $this->month_end;
        $this->first_month_start = $this->month_start ; 

		$this->backpay_recurrence = 0;
		//محاسبه حقوق ماههاي قبلي
		for ($i = $this->__BACKPAY_BEGIN_FROM; $i<=$this->last_month; $i++) {
			$this->backpay_recurrence++;
			//$this->backpay = true;
        //    $this->backpay = false;
			$this->month_start = DateModules::shamsi_to_miladi($this->__YEAR."/".$i."/01") ; 					
			$this->month_end = DateModules::shamsi_to_miladi($this->__YEAR."/".$i."/".DateModules::DaysOfMonth($this->__YEAR,$i)) ;			 			
			$this->__MONTH = $i;
			$this->__MONTH_LENGTH = DateModules::DaysOfMonth($this->__YEAR,$i) ; 					
			if(!$this->run()) {			
				return false;
			}	
						
		}
//...............................................................................
		
/*
		//محاسبه حقوق همين ماه
		$this->backpay_recurrence++;
		$this->backpay = false;
		$this->month_start = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->last_month."/01") ; 								
		$this->month_end = DateModules::shamsi_to_miladi($this->__YEAR."/".$this->last_month."/".DateModules::DaysOfMonth($this->__YEAR,$this->last_month)) ; 				
		$this->__MONTH = $this->last_month;
		$this->__MONTH_LENGTH = DateModules::DaysOfMonth($this->__YEAR,$this->last_month);
		$this->run();*/
		
		return true ;
	}

	//مقداردهي اوليه متغيرهاي کلاس
	private function prologue()
	{
		$this->expire_time = 300; //مدت زمان بر حسب ثانيه
	
		/*if(!$this->check_to_run())
			return false; */
	
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
	/*	if(!$this->backpay)
		{
			$this->last_month_end = $this->month_end;
			$this->last_month = $this->__MONTH;
		}*/
		
		//فقط يكبار چه در حالت  backpay و چه در غير از آن حالت
		if($this->backpay_recurrence <= 1)
		{
			$this->staff_writs = array();
			$this->exe_limit_staff(); 						
		}

		$this->exe_writ_sql();			
		$this->exe_pension(); 	
		$this->exe_param_sql(); 		
		//$this->exe_paygetlist_sql();
		$this->exe_staff_sql();	
		        
        $this->exe_tax_sql();        
        $this->exe_tax_history();
		$this->exe_taxtable_sql();       
		$this->exe_acc_info();                
		$this->exe_service_insure_sql(); 
		//.........................................

		$this->writRowCount = $this->writ_sql_rs->rowCount();
		$this->writRow = $this->writ_sql_rs->fetch(); 
		$this->writRowID++ ; 
						
		$this->cur_writ_id = $this->writRow['writ_id']; //شماره حکم جاري
		$this->cur_staff_id = $this->writRow['staff_id']; // شماره staff جاري
				
		$this->staffRowCount = $this->staff_rs->rowCount(); 
		$this->staffRow = $this->staff_rs->fetch(); 
		$this->staffRowID++ ;
		
		/*$this->pgRowCount = $this->pay_get_list_rs->rowCount();
		$this->PGLRow =  $this->pay_get_list_rs->fetch() ; 				
		$this->PGLRowID++ ; */
		 
//		$this->subRowCount = $this->subtracts_rs->rowCount();
		//$this->subRow = $this->subtracts_rs->fetch() ;
	//	$this->subRowID++ ;
		
		$this->insureRowCount = $this->service_insure_rs->rowCount();
		$this->insureRow = $this->service_insure_rs->fetch() ;
		$this->insureRowID++ ; 
	
		if($this->__MONTH == $this->last_month) {
			$this->taxRowCount = $this->tax_rs->rowCount();
			$this->taxRow = $this->tax_rs->fetch() ;
			$this->taxRowID++ ; 

			$this->taxHisRowCount = $this->tax_history_rs->rowCount();
			$this->taxHisRow = $this->tax_history_rs->fetch() ;
			$this->taxHisRowID++ ; 
		}


		$this->pensionRowCount = $this->pension_rs->rowCount();
		$this->pensionRow = $this->pension_rs->fetch() ;
		$this->pensionRowID++ ; 
		
		
		//$this->payment_items_file_h = fopen(HR_TemlDirPath.'payment_items_file.txt','w+'); //اقلامي که بايد در payment_items درج شوند
			

		if($this->backpay_recurrence == 1 /*|| !$this->backpay*/) {
			$this->monitor(7); 
			//$this->payment_file_h = fopen(HR_TemlDirPath.'payment_file.txt','w+'); //اقلامي که بايد در payment درج شوند
			//$this->subtract_file_h = fopen(HR_TemlDirPath.'subtract_file.txt','w+'); // اقلامي که بايد در person_subtracts بروزرساني شوند
			//$this->subtract_flow_file_h = fopen(HR_TemlDirPath.'subtract_flow_file.txt','w+'); //اقلامي که بايد در person_flow درج شوند
			$this->fail_log_file_h = fopen('../../../HRProcess/arrear_fail_log.php','w+');
			$this->success_log_file_h = fopen('../../../HRProcess/arrear_success_log.php','w+');

			$this->fail_counter = 1;
			$this->success_counter = 1;
			$this->writ_logs_file_header(); 
		}

		//فقط يكبار چه در حالت  backpay و چه در غير از آن حالت
	//	if($this->backpay_recurrence <= 1 ) {
			//$this->payment_writs_file_h = fopen(HR_TemlDirPath.'payment_writs_file.txt','w+'); //اقلامي که بايد در payment_writs درج شوند
	//	}

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
				
		if($this->staffRow['person_type'] != HR_WORKER && $this->staffRow['person_type'] != HR_CONTRACT ) {
			$this->__MONTH_LENGTH = 30;//اين عدد از طرف دانشگاه تعيين شده است
		}
			
		$work_sheet = $this->__MONTH_LENGTH;

		/*$this->moveto_curStaff($this->pay_get_list_rs ,'PGL');
		
		if( $this->PGLRow['staff_id'] == $this->cur_staff_id && $this->PGLRow['list_type'] == WORK_SHEET_LIST) {
			$work_sheet = $this->PGLRow['approved_amount'];		
			$this->PGLRow = $this->pay_get_list_rs->fetch(); 
			$this->PGLRowID++ ; 
		}*/
	
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
							 'cost_center_id'      => $this->staffRow['cost_center_id'],
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
			case HR_WORKER: return SIT_WORKER_COLLECTIVE_SECURITY_INSURE;
			case HR_CONTRACT: return SIT5_STAFF_COLLECTIVE_SECURITY_INSURE;
		}
	}
	
	//کد قلم حقوقي ماليات براي staff جاري
	private function get_tax_salary_item_id() {
		switch ($this->staffRow['person_type']) {
			case HR_EMPLOYEE: return SIT_STAFF_TAX;
			case HR_PROFESSOR: return SIT_PROFESSOR_TAX;
			case HR_WORKER: return SIT_WORKER_TAX;
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
		$this->sum_retired_include += $value * $fields['retired_include'];
	}
	
		//قسمت کنترل فرد
	private function control() {
        
		 if( $this->__MONTH < $this->last_month ) {
			return true;
		 }
       
		/*if($this->staffRow['pstaff_id'] > 0 ) { //قبلا فيش براي فرد صادر شده است
			$this->log('FAIL' ,'قبلا براي اين شخص محاسبه حقوق انجام شده است.');
			$this->payment_items = null;
			return false ;
		} */ 
		if( empty($this->staffRow['cost_center_id'])) { //فرد مرکز هزينه ندارد
			$this->log('FAIL' ,'براي اين شخص مرکز هزينه تعيين نشده است.');
			$this->payment_items = null;
			$this->staff_writs[$this->cur_staff_id] = array();
			return false ;
		}
		if(empty($this->staffRow['si_staff'])){			
			$this->log('FAIL' ,'سوابق مشموليت براي اين شخص ثبت نشده است .');
			$this->payment_items = null;
			$this->staff_writs[$this->cur_staff_id] = array();
			return false ;
		}
		return true ;
	}
	
	/* log */
	private function log($type, $txt,$type='') {
		if( $this->__MONTH < $this->last_month && $type !='End' )
        {
            return ;
        }
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
		 
		if($this->backpay_recurrence > 1)
			return true;

		$tmp_rs = parent::runquery('SELECT * FROM Arrear_payment_runs WHERE time_stamp >= :expireDate',
				                    array(":expireDate" => time()-$this->expire_time));
 
		 
		 //هيچ اجراي فعالي وجود ندارد
		if(count($tmp_rs) == 0)
		{			
			parent::runquery('INSERT INTO Arrear_payment_runs(time_stamp,uname) VALUES(?,?)',
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
		if( $this->__MONTH < $this->last_month )
        	return;

        parent::runquery('DELETE FROM Arrear_payment_runs WHERE run_id = ?', array($this->run_id));
	}

	/* اجراي query مربوط به ساخت جدول limit_staff که با توجه به شرط ماژول تنظيمات ساخته مي شود*/
	private function exe_limit_staff()
	{
		//parent::runquery('DROP TABLE IF EXISTS limit_staff');
		
		parent::runquery('TRUNCATE Arrear_limit_staff');
		
		parent::runquery('/*CREATE TABLE limit_staff  AS*/
						 insert into Arrear_limit_staff
							SELECT s.staff_id  , s.personID , s.person_type 
							FROM persons p
								INNER JOIN staff s ON(s.personID=p.PersonID AND s.person_type=p.person_type)
								INNER JOIN writs w ON(s.last_writ_id = w.writ_id AND s.last_writ_ver = w.writ_ver AND
													  w.staff_id=s.staff_id AND w.person_type=s.person_type)
								INNER JOIN org_new_units o ON(w.ouid = o.ouid)
							WHERE p.person_type NOT IN(' . HR_RETIRED . ') AND 
								  s.staff_id in ( select staff_id
													from writs
													where execute_date <= "'.$this->last_month_end.'" and 
														  execute_date >= "'.$this->first_month_start.'"  and arrear = 1 ) AND 																							  
								  s.last_cost_center_id in(' . manage_access::getValidCostCenters() . ') AND  ' . $this->__WHERE , $this->__WHEREPARAM ); 
				 
		//parent::runquery('ALTER TABLE limit_staff ADD INDEX (staff_id)');
		
	
	}

	/* اجراي query استخراج احکام تاثيرگذار در حکم */
	private function exe_writ_sql()
	{
		$this->monitor(0);
				
		//parent::runquery('DROP TABLE IF EXISTS smed');
		parent::runquery('TRUNCATE Arrear_smed');
	//	parent::runquery('DROP TABLE IF EXISTS mwv');
		parent::runquery('TRUNCATE Arrear_mwv');
		
		parent::runquery(" /*CREATE*/
                        insert into Arrear_smed
                        SELECT w.staff_id,
                               SUBSTRING_INDEX(SUBSTRING(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),11),'.',1) writ_id,
							   SUBSTRING_INDEX(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),'.',-1) writ_ver
                        FROM writs w
                                 INNER JOIN Arrear_limit_staff ls ON(w.staff_id = ls.staff_id)
								 
                        WHERE w.execute_date <= '" . $this->month_start . "' AND
                                  /*w.pay_date <= '" . $this->last_month_end . "' AND*/
                                  w.state = " . WRIT_SALARY . " AND
                                  w.history_only = 0  
                        GROUP BY w.staff_id;
                ");		

		//parent::runquery("ALTER TABLE smed ADD INDEX (staff_id,writ_id,writ_ver)");
		
		parent::runquery("
						/*CREATE  TABLE mwv   AS*/
						insert into Arrear_mwv
                        SELECT  w.staff_id,
								w.writ_id,
								MAX(w.writ_ver) writ_ver

                        FROM writs w
							INNER JOIN Arrear_limit_staff ls
								ON(w.staff_id = ls.staff_id)

                        WHERE w.execute_date <= '" . $this->month_end . "' AND
                              w.execute_date > '" . $this->month_start . "' AND
                              /*w.pay_date <= '" . $this->last_month_end . "' AND*/
                              w.history_only = 0  AND
                              w.state = " . WRIT_SALARY . "
								  
                        GROUP BY w.staff_id, w.writ_id"
                );

		//parent::runquery("ALTER TABLE mwv ADD INDEX (staff_id,writ_id,writ_ver)"); 

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
                           sit.month_length_effect

                        FROM Arrear_limit_staff ls
							INNER JOIN Arrear_smed sm ON(ls.staff_id = sm.staff_id)
                            INNER JOIN writs w ON(w.writ_id = sm.writ_id AND w.writ_ver = sm.writ_ver AND w.staff_id=sm.staff_id)
                            LEFT OUTER JOIN writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver AND 
							                                         w.staff_id = wsi.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
                            LEFT OUTER JOIN salary_item_types sit ON(wsi.salary_item_type_id = sit.salary_item_type_id)

                        WHERE w.state = '.WRIT_SALARY.')

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
                           sit.month_length_effect
                        FROM Arrear_mwv mwv 
                             INNER JOIN writs w ON(mwv.writ_id = w.writ_id AND mwv.writ_ver = w.writ_ver AND mwv.staff_id=w.staff_id)
                             INNER JOIN Arrear_limit_staff ls ON(w.staff_id = ls.staff_id)
                             LEFT OUTER JOIN writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver
									AND wsi.staff_id=w.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
                             LEFT OUTER JOIN salary_item_types sit ON(wsi.salary_item_type_id = sit.salary_item_type_id)

                        WHERE w.state = ' . WRIT_SALARY . ')
							
                        ORDER BY staff_id,execute_date,writ_id,writ_ver
                ');
				 
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
		$fh = fopen('../../../HRProcess/arrear_pay_calc_monitor_file.html','w+');				
		fwrite($fh, $head . $txt . $end );
		fclose($fh);
	}

	/*اجراي query مربوط به استخراج حداکثر حقوق مشمول بازنشستگي که مقرري به آن تعلق مي گيرد*/
	private function exe_pension()
	{
		$this->monitor(1);
		
		//parent::runquery('DROP TABLE IF EXISTS temp_pension');
		parent::runquery('TRUNCATE Arrear_pension');
		
		if($this->__YEAR > '1390' || ( $this->__YEAR == '1390' && $this->__MONTH > '6' ) ){ 
			
			//parent::runquery('DROP TABLE IF EXISTS temp_pension_last_year');
			//parent::runquery('DROP TABLE IF EXISTS temp_pension_last_year2');	
			parent::runquery('TRUNCATE Arrear_pension_last_year');
			parent::runquery('TRUNCATE Arrear_pension_last_year2');
			
			parent::runquery('/*CREATE  TABLE temp_pension_last_year   AS */
								insert into Arrear_pension_last_year

								select max(pit.pay_month) pay_month , pit.staff_id

								from Arrear_limit_staff ls left join payment_items pit
									                           on ( ls.staff_id = pit.staff_id and  pit.payment_type = 1  )

								where pit.pay_year = '.(($this->__YEAR) - 1 ).' and
									pit.salary_item_type_id in (34,36 , 10264 , 10267) and ls.person_type in (1,2)
								group by  pit.staff_id'); 
			
			parent::runquery('/*CREATE  TABLE temp_pension_last_year2   AS */
								insert into Arrear_pension_last_year2
								select max(pit.pay_month) pay_month , pit.staff_id
										from Arrear_limit_staff ls left join payment_items pit
													on ( ls.staff_id = pit.staff_id and  pit.payment_type = 1  )

								where pit.pay_year = '.(($this->__YEAR) - 1 ).' and pit.pay_month != 12 and 
									  pit.salary_item_type_id in (34,36 , 10264 , 10267) and ls.person_type in (1,2)
								group by  pit.staff_id') ; 
			
			$unionTbl = ' UNION ALL
						( select  pit.staff_id , sum(pit.pay_value) pv
								from  Arrear_pension_last_year tp inner join payment_items pit
												on (tp.staff_id = pit.staff_id AND
													pit.payment_type = 1  AND
													tp.pay_month = pit.pay_month AND
													pit.pay_year = '.(($this->__YEAR) - 1 ).' AND
													pit.salary_item_type_id in (34,36 ,10264 , 10267) )
						  group by pit.staff_id )
														 
						  UNION ALL
						( select  pit.staff_id , sum(pit.pay_value) pv
										from  Arrear_pension_last_year2 tp inner join payment_items pit
														on (tp.staff_id = pit.staff_id AND
															pit.payment_type = 1  AND
															tp.pay_month = pit.pay_month AND
															pit.pay_year = '.(($this->__YEAR) - 1 ).' AND
															pit.salary_item_type_id in (34,36 ,10264 , 10267) )
						 group by pit.staff_id )' ; 
			
		}
			
			
		parent::runquery('
						/*CREATE  TABLE temp_pension   AS*/
						 insert into Arrear_pension
						(SELECT ls.staff_id,MAX(pit.param4 * 1) AS mpension

						FROM Arrear_limit_staff ls
							LEFT OUTER JOIN payment_items pit ON(ls.staff_id = pit.staff_id AND pit.payment_type ='.NORMAL_PAYMENT.')

						WHERE ls.person_type in (1,2) and ( ( (pit.pay_year = '.$this->__YEAR.' AND pit.pay_month < '.$this->__MONTH.') OR
								  (pit.pay_year < '.$this->__YEAR.') )	AND
								  (pit.salary_item_type_id IN('.SIT_STAFF_RETIRED.',
															  '.SIT_PROFESSOR_RETIRED.',
															  '.SIT_WORKER_RETIRED.') ) ) OR
							  pit.staff_id IS NULL

						GROUP BY ls.staff_id)

						UNION ALL
						
						(SELECT ls.staff_id,MAX(pit.param4 * 1) AS mpension

						FROM Arrear_limit_staff ls
								 LEFT OUTER JOIN corrective_payment_items pit
										 ON(ls.staff_id = pit.staff_id AND pit.payment_type = '.NORMAL_PAYMENT.')
						WHERE ls.person_type in (1,2) and ( (pit.pay_year = '.$this->__YEAR.' AND pit.pay_month <= '.$this->__MONTH.') 	AND
							    (pit.salary_item_type_id IN('.SIT_STAFF_RETIRED.',
														    '.SIT_PROFESSOR_RETIRED.',
														    '.SIT_WORKER_RETIRED.') ) ) OR
							     pit.staff_id IS NULL

						GROUP BY ls.staff_id)'. $unionTbl );
				
		$this->pension_rs = parent::runquery_fetchMode('SELECT staff_id,MAX(mpension) max_sum_pension
														FROM temp_pension
														GROUP BY staff_id'); 
				
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

                        FROM salary_params

                        WHERE from_date <= '" . $this->month_end . "' AND to_date >= '" . $this->month_end . "'");

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
	/*private function exe_paygetlist_sql()
	{
		$this->monitor(3);
		
		//در محاسبه backpay فقط اقلامي كه مشمول backpay هستند محاسبه مي شوند
		$backpay_where = '1=1';

		if($this->backpay)
			$backpay_where = 'sit.backpay_include = 1';
		
		$this->pay_get_list_rs = parent::runquery_fetchMode("
                        SELECT pgli.staff_id staff_id,
                               pgl.list_id list_id,
                               pgl.list_type list_type,
                               0 as using_facilities,
                               sit.salary_item_type_id salary_item_type_id,
                               sit.compute_place,
                               sit.salary_compute_type,
                               sit.multiplicand,
                               sit.function_name,
                               sit.validity_start_date,
                               sit.validity_end_date,
                               sit.insure_include,
                               sit.tax_include,
                               sit.retired_include,                               
                               approved_amount,
                               initial_amount,
                               value,
                               0 as travel_cost

                        FROM pay_get_lists pgl
                             INNER JOIN pay_get_list_items pgli
                                   ON(pgl.list_id = pgli.list_id AND pgl.list_date >= '".$this->month_start."'
									   AND pgl.list_date <= '".$this->month_end."' AND doc_state = ".CENTER_CONFIRM.")
                             INNER JOIN limit_staff ls ON(pgli.staff_id = ls.staff_id)
                             INNER JOIN salary_item_types sit
                                   ON(pgli.salary_item_type_id = sit.salary_item_type_id AND ".$backpay_where.")
                        WHERE pgl.list_type <> 9                
                        ORDER by staff_id,list_type DESC
                        "); 				
		
	}*/

	/* اجراي query مربوط به ليست staff با اطلاعات جانبازي و ايثارگري*/
	private function exe_staff_sql()
	{
		parent::runquery("SET NAMES 'utf8'");

		//parent::runquery('DROP TABLE IF EXISTS dvt');
		parent::runquery('TRUNCATE Arrear_dvt');
		parent::runquery('
                        /*CREATE TABLE dvt  AS*/
						insert into Arrear_dvt
                        SELECT PersonID,
                               MAX(CASE devotion_type WHEN '.FREEDOM_DEVOTION.' THEN amount ELSE 0 END) freedman,
                               MAX(CASE devotion_type WHEN '.SACRIFICE_DEVOTION.' THEN amount ELSE 0 END) sacrifice

                        FROM person_devotions

                        WHERE personel_relation = '.OWN.' AND (devotion_type = '.FREEDOM_DEVOTION.' OR devotion_type = '.SACRIFICE_DEVOTION.')

                        GROUP BY PersonID;
                ');

		//parent::runquery('ALTER TABLE dvt ADD INDEX (PersonID)');

		//parent::runquery('DROP TABLE IF EXISTS temp_last_writs');
		parent::runquery('TRUNCATE Arrear_last_writs');
		//baharrr
		parent::runquery("
                        /*CREATE TABLE temp_last_writs  AS*/
						insert into Arrear_last_writs
						SELECT w.staff_id,
						       SUBSTR(MAX(CONCAT(w.execute_date,w.writ_id)),11) max_writ_id,
						       SUBSTRING_INDEX(MAX(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),'.',-1) max_writ_ver
						FROM Arrear_limit_staff ls
						     INNER JOIN writs w ON(ls.staff_id = w.staff_id)
						WHERE w.execute_date <= '".$this->month_end."' AND
						     /* w.pay_date <= '".$this->last_month_end."' AND*/
						      w.history_only = 0 AND
						      w.state = ".WRIT_SALARY."
						GROUP BY staff_id ");
	
		//parent::runquery('ALTER TABLE temp_last_writs ADD INDEX (staff_id,max_writ_id,max_writ_ver)');
		
		//parent::runquery('DROP TABLE IF EXISTS temp_dev_child') ; 
		parent::runquery('TRUNCATE temp_dev_child');
		parent::runquery('/*CREATE  TABLE Arrear_dev_child  AS*/
							insert into Arrear_dev_child
		                        SELECT PersonID 	                             
		                        FROM person_devotions		
		                        WHERE personel_relation in ('.DAUGHTER.','.BOY.' ) AND 
		                             (devotion_type = '.BEHOLDER_FAMILY_DEVOTION.' )
		
		                        GROUP BY PersonID');
		//parent::runquery('ALTER TABLE temp_dev_child ADD INDEX (PersonID)'); 
		
		$this->staff_rs = parent::runquery_fetchMode(" SELECT   s.staff_id,
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
																w.cost_center_id,
																w.ouid,
																w.emp_state,
																w.salary_pay_proc,
																w.worktime_type,
																w.emp_mode ,																
																CONCAT(per.plname,' ',per.pfname) name

                        FROM Arrear_limit_staff ls
                                 INNER JOIN staff s
                                 	ON(s.staff_id = ls.staff_id)
						         LEFT OUTER JOIN staff_include_history si
						            ON(s.staff_id = si.staff_id AND si.start_date <= '".$this->month_end."' AND 
									(si.end_date IS NULL OR si.end_date = '0000-00-00' OR  
									 si.end_date >= '".$this->month_end."' OR si.end_date > '".$this->month_start."' ) )										 
                      			 INNER JOIN persons per
                       				ON(s.PersonID = per.PersonID)
                       			 INNER JOIN Arrear_last_writs tlw
                       			 	ON(s.staff_id = tlw.staff_id)
                             	 INNER JOIN writs w
                                    ON(tlw.max_writ_id = w.writ_id AND tlw.max_writ_ver = w.writ_ver AND tlw.staff_id = w.staff_id )
                                 LEFT OUTER JOIN Arrear_dvt d
                                    ON(s.PersonID = d.PersonID)
                                 LEFT OUTER JOIN   Arrear_dev_child tdc
                                 	ON( s.PersonID = tdc.PersonID )    
                                 
                        ORDER BY s.staff_id ") ; 	
					
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
																		WHEN ps.subtract_type = ".LOAN." AND ps.instalment > ps.remainder THEN ps.remainder
																		ELSE ps.instalment
																	END get_value,
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
																	ps.loan_no,
																	ps.contract_no 

													FROM limit_staff ls
															INNER JOIN person_subtracts ps
																ON(ps.PersonID = ls.PersonID)																															
															INNER JOIN salary_item_types sit
																ON(ps.salary_item_type_id = sit.salary_item_type_id)
															LEFT JOIN tmp_SubtractRemainders sr 
																ON  sr.subtract_id = ps.subtract_id
																
													WHERE  (ps.instalment > 0) AND	ps.IsFinished = 0 AND													
														   (ps.start_date <= '".$this->month_end."') AND 
														   (ps.end_date >= '".$this->month_start."' OR 
														    ps.end_date IS NULL OR ps.end_date = '0000-00-00' )  AND
															".$backpay_where."

													GROUP BY ls.staff_id,
															ps.subtract_type,
															ps.salary_item_type_id,
															ps.subtract_id,
															CASE
															WHEN ps.subtract_type = ".LOAN." AND ps.instalment > ps.remainder THEN ps.remainder
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

       // echo parent::GetLatestQueryString(); die();
												
	}
	
	
	/*اجراي query مربوط به محاسبه ماليات*/
	private function exe_tax_sql() {
        if( $this->__MONTH < $this->last_month ) {
			return true;
		}
		/*
		if($this->backpay_recurrence > 1) {
			$source_table = 'back_payment_items';
		}
		else 
		{
			$source_table =  'payment_items';
			
		}*/
		
			$this->tax_rs = parent::runquery_fetchMode("

                        SELECT T1.staff_id , 
                               (T1.sum_tax + IF(T2.sum_tax IS NULL , 0 , T2.sum_tax )) sum_tax ,
                               T1.sum_tax_include
                        FROM (
                        SELECT
                                pit.staff_id staff_id,
                                SUM(pit2.get_value + (pit2.diff_get_value * pit2.diff_value_coef)) sum_tax,
                                SUM(pit.param1 + pit.diff_param1) sum_tax_include

                        FROM Arrear_limit_staff ls
                                 INNER JOIN corrective_payment_items pit
                                         ON(pit.staff_id = ls.staff_id)
                                 LEFT OUTER JOIN payment_items pit2
                                        ON(pit2.staff_id = pit.staff_id AND 
                                           pit2.pay_year = pit.pay_year AND
                                           pit2.pay_month = pit.pay_month AND
                                           pit2.payment_type = ".NORMAL_PAYMENT." AND
                                           pit2.salary_item_type_id = pit.salary_item_type_id)

                        WHERE pit.pay_year = ".$this->__START_NORMALIZE_TAX_YEAR." AND 
                              pit.pay_month >= ".$this->__START_NORMALIZE_TAX_MONTH." AND
                              pit.pay_month < ".$this->last_month." AND 
                              pit.salary_item_type_id IN(".SIT_PROFESSOR_TAX.",
                                                         ".SIT_STAFF_TAX.",
                                                         ".SIT_WORKER_TAX.",
                                                         ".SIT5_STAFF_TAX.")
                        GROUP BY pit.staff_id ) T1
                        LEFT JOIN (

                                 select   staff_id , SUM(get_value + (diff_get_value * diff_value_coef)) sum_tax
                                         from  payment_items
                                                    where pay_year = ".$this->__START_NORMALIZE_TAX_YEAR." and
                                                          pay_month = ".$this->last_month." and
                                                          payment_type = ".NORMAL_PAYMENT." and
                                                          salary_item_type_id IN(".SIT_PROFESSOR_TAX.",
                                                                                 ".SIT_STAFF_TAX.",
                                                                                 ".SIT_WORKER_TAX.",
                                                                                 ".SIT5_STAFF_TAX.")
                                        group by staff_id

                                  ) T2
                                   ON T1.staff_id = T2.staff_id

                        ");
			
	}
	
	/* اجراي query مربوط به استخراج سابقه مالياتي*/
	private function exe_tax_history() {
        
		if( $this->__MONTH < $this->last_month ) {
			return true;
		}		
		
		if($this->__CALC_NORMALIZE_TAX) {
			$start_date = DateModules::shamsi_to_miladi($this->__START_NORMALIZE_TAX_YEAR."/".$this->__START_NORMALIZE_TAX_MONTH."/01") ; 			
			$w = "end_date IS NULL OR end_date = '0000-00-00' OR end_date > '$start_date'";
		} else {
			$w = "NOT((start_date > '".$this->month_end."') OR (end_date IS NOT NULL  AND  end_date != '0000-00-00' AND end_date < '".$this->month_start."'))";
		}
				
		$this->tax_history_rs = parent::runquery_fetchMode("
                                        SELECT sth.staff_id,
                                               sth.start_date,
                                               sth.end_date,
                                               sth.tax_table_type_id,
                                               sth.payed_tax_value
                                        FROM Arrear_limit_staff ls
                                              INNER JOIN staff_tax_history sth
                                                    ON(ls.staff_id = sth.staff_id)
                                        WHERE ".$w."
                                        ORDER BY sth.staff_id,sth.start_date
                                        ");	
				
	}
	
		/* اجراي query جداول مالياتي و انتقال آنها به يک ارايه */
	private function exe_taxtable_sql() {
        
		if( $this->__MONTH < $this->last_month ) {
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

                        FROM tax_table_types ttype
                             INNER JOIN tax_tables ttable
                                   ON(ttype.tax_table_type_id = ttable.tax_table_type_id AND from_date <= '".$this->month_end."' AND to_date >= '".$this->month_start."')
                             INNER JOIN tax_table_items titem
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
								
                        FROM salary_item_types sit
                        WHERE
                             sit.salary_item_type_id IN (".SIT_PROFESSOR_COLLECTIVE_SECURITY_INSURE.",
														 ".SIT_STAFF_COLLECTIVE_SECURITY_INSURE.",
														 ".SIT_WORKER_COLLECTIVE_SECURITY_INSURE.",
														 ".SIT5_STAFF_COLLECTIVE_SECURITY_INSURE.",
														 ".SIT_PROFESSOR_RETIRED.",
														 ".SIT_STAFF_RETIRED.",
														 ".SIT_WORKER_RETIRED.",
														 ".SIT5_STAFF_RETIRED.",
														 ".SIT_PROFESSOR_TAX.",														
														 ".SIT_STAFF_TAX.",
														 ".SIT_WORKER_TAX.",
														 ".SIT5_STAFF_TAX.",
														 ".STAFF_FIRST_MONTH_MOGHARARY.",
														 ".PROFESSOR_FIRST_MONTH_MOGHARARY.",
														 ".RETURN_FIRST_MONTH_MOGHARARY." ,
														 ".SIT5_STAFF_FIRST_MONTH_MOGHARARY.",
														 ".SIT_RETURN_INSURE_AND_RETIRED_WOUNDED_PERSONS.")
						");
		
				
		for($i=0; $i<count($tmp_rs); $i++)
		{
			$this->acc_info[$tmp_rs[$i]['salary_item_type_id']] = array(
																		'validity_start_date' => $tmp_rs[$i]['validity_start_date'],
																		'validity_end_date' => $tmp_rs[$i]['validity_end_date']);
		}
		
	}
	
	/* اجراي query مربوط به بيمه خدمات درماني و برگشت بيمه جانبازان */
	function exe_service_insure_sql() {
		$this->monitor(6);
		
		/*if(!$this->backpay){
			
			//parent::runquery(" DROP TABLE IF EXISTS mpds ");
			parent::runquery('TRUNCATE mpds');
			parent::runquery("  /*CREATE  TABLE mpds  AS*/
								/*insert into mpds
								SELECT pds.PersonID,
									pds.master_row_no,
									MAX(pds.from_date) from_date ,
									SUBSTR(MAX(CONCAT(pds.from_date,pds.row_no)),11) row_no
								FROM person_dependent_supports pds
								WHERE pds.from_date <= '".$this->month_end."' AND(pds.to_date >= '".$this->month_start."' OR 
									  pds.to_date IS NULL OR pds.to_date = '0000-00-00' ) AND 
									 (pds.status = ".DELETE_IN_EMPLOYEES." OR pds.status = ".IN_SALARY.") 
								GROUP BY pds.PersonID,pds.master_row_no;");
			
			$this->service_insure_rs = parent::runquery_fetchMode(" SELECT pds.PersonID ,
																	s.staff_id,
																	COUNT( CASE pds.insure_type
																			WHEN ".NORMAL." THEN 1
																			END) normal,
																	COUNT( CASE pds.insure_type
																			WHEN ".NORMAL." AND pd.dependency IN(".OWN.") THEN 1
																			END) own_normal,          
																	COUNT( CASE pds.insure_type
																			WHEN ".NORMAL2." THEN 5
																			END) normal2,
																	COUNT( CASE pds.insure_type
																			WHEN ".FIRST_SURPLUS." THEN 2
																			END) extra1,
																	COUNT( CASE pds.insure_type
																			WHEN ".SECOND_SURPLUS." THEN 3
																			END) extra2,
																	COUNT( CASE
																		WHEN pds.insure_type = ".NORMAL." AND pd.dependency IN(".OWN.",".FATHER.",".MOTHER.",".WIFE.",".BOY.",".DAUGHTER.") THEN 1
																		END)ret_normal,
																	COUNT( CASE
																		WHEN pds.insure_type = ".NORMAL2." AND pd.dependency IN(".OWN.",".FATHER.",".MOTHER.",".WIFE.",".BOY.",".DAUGHTER.") THEN 5
																		END)ret_normal2,
																	COUNT( CASE
																		WHEN pds.insure_type = ".FIRST_SURPLUS." AND pd.dependency IN(".OWN.",".FATHER.",".MOTHER.",".WIFE.",".BOY.",".DAUGHTER.") THEN 2
																		END)ret_extra1,
																	COUNT( CASE
																		WHEN pds.insure_type = ".SECOND_SURPLUS." AND pd.dependency IN(".OWN.",".FATHER.",".MOTHER.",".WIFE.",".BOY.",".DAUGHTER.") THEN 3
																		END)ret_extra2,	                                   
																		sit.validity_start_date,
																		sit.validity_end_date

																FROM mpds mp
																	INNER JOIN person_dependent_supports pds
																		ON(mp.PersonID = pds.PersonID AND mp.master_row_no = pds.master_row_no AND mp.row_no = pds.row_no)
																	INNER JOIN person_dependents pd
																		ON(pd.PersonID = pds.PersonID AND pd.row_no = pds.master_row_no)
															INNER JOIN staff s
																ON(pds.PersonID = s.PersonID)
															INNER JOIN limit_staff ls
																	ON(s.staff_id = ls.staff_id)
															INNER JOIN salary_item_types sit
																ON((sit.salary_item_type_id = ".SIT_PROFESSOR_REMEDY_SERVICES_INSURE." AND s.person_type = ".HR_PROFESSOR.") OR
																	(sit.salary_item_type_id = ".SIT_STAFF_REMEDY_SERVICES_INSURE." AND s.person_type = ".HR_EMPLOYEE."))

																GROUP BY s.staff_id,
																				pds.PersonID,	                                        
																			sit.validity_start_date,
																			sit.validity_end_date");
			
		}
		else
		{*/
			$this->service_insure_rs = parent::runquery_fetchMode(" SELECT 
                                                                        p.PersonID ,
                                                                        s.staff_id,
                                                                        IFNULL(pi.param1,0) normal,
                                                                        IFNULL(pi.param8,0) normal2,
                                                                        IFNULL(pi.param2,0) extra1,
                                                                        IFNULL(pi.param3,0) extra2,
                                                                        IFNULL(pi.param7,0) own_normal ,
                                                                        IFNULL(pi2.param1,0) ret_normal,
                                                                        IFNULL(pi2.param8,0) ret_normal2,
                                                                        IFNULL(pi2.param2,0) ret_extra1,
                                                                        IFNULL(pi2.param3,0) ret_extra2,
                                                                        null validity_start_date,
                                                                        null validity_end_date

                                                                    FROM staff s
                                                                        INNER JOIN persons p
                                                                            ON(p.PersonID = s.PersonID)
                                                                        INNER JOIN Arrear_limit_staff ls
                                                                            ON(s.staff_id = ls.staff_id)
                                                                        LEFT OUTER JOIN payment_items pi
                                                                            ON
                                                                                pi.staff_id = s.staff_id
                                                                                AND pi.pay_year = ".$this->__YEAR."
                                                                                AND pi.pay_month = ".$this->__MONTH."
                                                                                AND (
                                                                                    (pi.salary_item_type_id = ".SIT_PROFESSOR_REMEDY_SERVICES_INSURE." AND s.person_type = ".HR_PROFESSOR.") OR
                                                                                    (pi.salary_item_type_id = ".SIT_STAFF_REMEDY_SERVICES_INSURE." AND s.person_type = ".HR_EMPLOYEE.")
                                                                                )
                                                                        LEFT OUTER JOIN payment_items pi2
                                                                               ON
                                                                                 pi2.staff_id = s.staff_id
                                                                                 AND pi2.pay_year = ".$this->__YEAR."
                                                                                 AND pi2.pay_month = ".$this->__MONTH."
                                                                                 AND (pi2.salary_item_type_id = ".RETURN_FIRST_MONTH_MOGHARARY.") ") ;

			
		/*} */
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
										 'cost_center_id'      => $this->staffRow['cost_center_id'],
										 'payment_type'        => NORMAL );				
	}
	
	//محاسبه قلم حقوقي برگشتي مقرري و بازنشستگي و بيمه خدمات درماني و بيمه ايران جانبازان کارمند
	private function compute_salary_item2_30() {
		//param1 : تعداد عادي
		//param2 : تعداد مازاد 1
		//param3 : تعداد مازاد 2
		
		$param1 = $this->insureRow['ret_normal'];
		$param2 = $this->insureRow['ret_extra1'];
		$param3 = $this->insureRow['ret_extra2'];
		$param8 = $this->insureRow['ret_normal2'];
		
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
		$value = $insure_value + (isset($this->payment_items[IRAN_INSURE]['get_value']) ? $this->payment_items[IRAN_INSURE]['get_value'] : 0 );
		
		$value += (isset($this->payment_items[$this->get_pension_salary_item_id()]['get_value']) ? $this->payment_items[$this->get_pension_salary_item_id()]['get_value'] : 0 ) + 
				  (isset($this->payment_items[$this->get_retired_salary_item_id()]['get_value']) ?  $this->payment_items[$this->get_retired_salary_item_id()]['get_value'] : 0 ) ;
				 
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
							 'cost_center_id'      => $this->staffRow['cost_center_id'],
							 'payment_type'        => NORMAL );
		
		return $payment_rec;
	}
	
	/*اضافه کار عادي*/
	private function compute_salary_item2_21() {
	
	    //param1 : نرخ اضافه کار
	    //param2 : حقوق مبنا + افزايش سنواتي + فوق العاده شغل + تفاوت تطبيق + حداقل دريافتي + فوق العاده شغل برجسته + فوق العاده جذب + فوق العاده تعديل
	    //param3 : تعداد ساعات اضافه کار
	
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
		
		else {
						
	      $param1 = 1 / 176;
		  			   
	      $ManagementValue = ( (isset($this->payment_items[10373]['param2'])) ? $this->payment_items[10373]['param2'] : 0  + 			  
							   (isset($this->payment_items[10373]['param3'])) ? $this->payment_items[10373]['param3'] : 0  + 
							   (isset($this->payment_items[10377]['param2'])) ? $this->payment_items[10377]['param2'] : 0  +
							   (isset($this->payment_items[10377]['param3'])) ? $this->payment_items[10377]['param3'] : 0   ) * $this->payment_items[10364]['pay_value'] ; 
		      
	      $param2 = $this->payment_items[10364]['pay_value']         +
					$this->payment_items[10366]['pay_value']    +
					$this->payment_items[10367]['pay_value']  ;  
	      
	      $param3 =  $this->PGLRow['approved_amount'];	      
	      $value = $param1 * $param2 * $param3;	      
	  }
		
		
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => SIT_STAFF_EXTRA_WORK,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => $param1,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'param4'              => $ManagementValue,
							 'cost_center_id'      => $this->staffRow['cost_center_id'],
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
								 'cost_center_id'      => $this->staffRow['cost_center_id'],
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

		//اضافه کار تشويقي را محاسبه مي کند.
		if ($this->PGLRow['initial_amount']) {
			$this->compute_salary_item3_29();
		}
		
		$param1 = 1.4;
		$param2 = $this->PGLRow['approved_amount'];
		
		$salary = $this->payment_items[SIT_WORKER_BASE_SALARY]['pay_value'] +
				  $this->payment_items[SIT_WORKER_ANNUAL_INC]['pay_value'] + 
				  (isset($this->payment_items[SIT_WORKER_DEVOTION_EXTRA]['pay_value']) ? $this->payment_items[SIT_WORKER_DEVOTION_EXTRA]['pay_value'] : 0 ) ;	

		$param3 = $salary / $this->__MONTH_LENGTH;

		$value = $param1 * ($param2 / 7.33) * $param3;
		
		$payment_rec = array(
							 'pay_year'            => $this->__YEAR,
							 'pay_month'           => $this->__MONTH,
							 'staff_id'            => $this->cur_staff_id,
							 'salary_item_type_id' => SIT_WORKER_EXTRA_WORK,
							 'get_value'           => 0,
							 'pay_value'           => $value,
							 'param1'              => $param1,
							 'param2'              => $param2,
							 'param3'              => $param3,
							 'cost_center_id'      => $this->staffRow['cost_center_id'],
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
								 'cost_center_id'      => $this->staffRow['cost_center_id'],
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
	
	static function calculate_mission($staff_id,$pay_year,$pay_month,$dayNo,$coef,$IncludeSalary)
	{
		
		$qry = " select person_type from staff where staff_id = ".$staff_id ; 
		$res = PdoDataAccess::runquery($qry) ; 
				
		if(	$res[0]['person_type'] == 1 ) 
		{
			
			$qry = " select salary_item_type_id , (pay_value ) val 
						from hrms.payment_items
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
			else {
								
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
			
		}
		else
		{//	echo "-------------------------------<br>" ; 
						
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
		
		return $value ;
		
	}
	
	//............................. محاسبه مجموع قلمهای مشمول بازنشستگی یا بیمه ................................
	
	static function sum_salary_items($pay_year,$pay_month,$staff_id,$pTyp,$insureType="")
	{
		if($pTyp == 2 || $pTyp == 3)
			$DB = "hrms.";
		else 
			$DB = "hrms_sherkati.";
			
		$where = "" ; 
		if($insureType == 1) $where = " and sit.retired_include = 1 " ; 
		if($insureType == 2) $where = " and sit.insure_include = 1 " ; 
		
		$qry = "select sum(pit.pay_value ) sval

				 from ".$DB."payment_items pit inner join ".$DB."salary_item_types sit
											on pit.salary_item_type_id = sit.salary_item_type_id

				 where pay_year = $pay_year and pay_month = $pay_month and staff_id = $staff_id  and payment_type = 1 ".$where ; 
		
		$res = PdoDataAccess::runquery($qry) ; 
		
		return $res[0]['sval'];
	}

}

?>
				