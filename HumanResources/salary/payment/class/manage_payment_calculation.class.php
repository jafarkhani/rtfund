<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// Date:		90.01
//---------------------------

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
	private $person_subtract_array; //وام و کسوراتي که بايد در جدول person_subtract بروزرساني شوند
	private $person_subtract_flow_array; //وام و کسوراتي که بايد در جدول person_subtract_flow بروزرساني شوند

	private $sum_tax_include; //يک متغير براي نگهداري مجموع مقادير قلام هاي مشمول ماليات
	private $sum_insure_include; //يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بيمه
	private $sum_retired_include; // يک متغير براي نگهداري مجموع مقادير قلم هاي مشمول بازنشتگي
	private $max_sum_pension; //در اين متغير همواره ماکزيمم حقوق مشمول بازنشستگ که مقرري به ان تعلق مي گيرد نگهداري ميشود

	private $cost_center_id; //متغيري جهت نگهداري کد مرکز هزينه cur_staff که از آخرين حکم استخراج مي گردد

	private $payment_file_h; //اقلامي که بايد در payment درج شوند
	private $payment_items_file_h; //اقلامي که بايد در payment_items درج شوند
	private $payment_writs_file_h; //اقلامي که بايد در payment_writs درج شوند
	private $subtract_file_h; // اقلامي که بايد در person_subtracts بروزرساني شوند
	private $subtract_flow_file_h; //اقلامي که بايد در person_flow درج شوند
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

	public function  __construct()
	{

	}

	/*پردازش مربوط به احکام که حلقه اصلي محاسبه است*/
	public function run()
	{
		if(!$this->prologue()) 
			return false;
		

		$this->monitor(8);

		$this->moveto_curStaff($this->staff_rs);

		/* پيماش recordset احکام*/
		while (!$this->writ_sql_rs->EOF) {
			//طبق بررسي و تحليل انجام شده نيازي به كنترل اعتبار اقلام حكمي نيست و وجود آنها در حكم فرد به معناي اعتبار آن مي باشد
			if( true /*$this->validate_salary_item_id($this->writ_sql_rs->fields['validity_start_date'], $this->writ_sql_rs->fields['validity_end_date'])*/ ) {

				$temp_array[$this->writ_sql_rs->fields['salary_item_type_id']] = $this->writ_sql_rs->fields;
				$pre_writ_date = $this->writ_sql_rs->fields['execute_date'];

				//تهيه آرايه احکامي که در محاسبه حقوق شخص جاري استفاده مي شوند
				$this->staff_writs[$this->cur_staff_id][$this->writ_sql_rs->fields['writ_id']] =
					array('writ_id'=>$this->writ_sql_rs->fields['writ_id'],
						  'writ_ver'=>$this->writ_sql_rs->fields['writ_ver']);

			}
			$this->writ_sql_rs->MoveNext();
			if($this->writ_sql_rs->fields['writ_id'] == $this->cur_writ_id && $this->writ_sql_rs->fields['staff_id'] == $this->cur_staff_id) {
				continue;
			}
			$this->cur_writ_id = $this->writ_sql_rs->fields['writ_id'];
			$cur_writ_date = $this->writ_sql_rs->fields['execute_date'];

			// محاسبه فاصله تاريج اجراي دو حکم
			if( sisCompareDate($pre_writ_date,$this->month_start) == -1 ) {
				$pre_writ_date = $this->month_start;
			}
			$use_month_end_date = 0;
			if( $this->writ_sql_rs->fields['staff_id'] != $this->cur_staff_id ) {
				//كاربرد اين متغير اين است كه مشخص كند آيا تاريخ انتها براي اين حكم آخر ماه است يا
				//تاريخ شروع حكم بعد. اگر تاريخ آخر ماه باشد بهتفاضل تاريخها يكي اضافه مي شود
				//در غير اين صورت خير
				$use_month_end_date = 1;
				if($this->staff_rs->fields['person_type'] == WORKER) {
					$cur_writ_date = $this->month_end;
				}
				else {
					$cur_writ_date = ConvertJ2GDate(30,$this->__MONTH,$this->__YEAR);
				}
			}
			$between_length = round(sisGDateMinusGDate($cur_writ_date,$pre_writ_date)) + $use_month_end_date;
			$main_time_slice = (  $between_length / $this->__MONTH_LENGTH) * ($this->cur_work_sheet / $this->__MONTH_LENGTH);

			list($salary_item_type_id,$fields) = each($temp_array);

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
				} else {
					$time_slice = $main_time_slice;
				}

				if( isset($this->payment_items[$key]) ) { // اگر قبلا اين قلم در آرايه اقلام حقوقي وجود دارد
					$this->payment_items[$key]['pay_value'] += $fields['pay_value'] * $time_slice;
					$this->payment_items[$key]['time_slice'] += $time_slice ;
				}
				else {
					$this->payment_items[$key] = array(
					'pay_year' => $this->__YEAR,
					'pay_month' => $this->__MONTH,
					'staff_id' => $fields['staff_id'],
					'salary_item_type_id' => $fields['salary_item_type_id'],
					'pay_value' => $fields['pay_value'] * $time_slice,
					'time_slice' => $time_slice,
					'get_value' => 0,
					'param1' => $fields['param1'],
					'param2' => $fields['param2'],
					'param3' => $fields['param3'],
					'cost_center_id' => $this->staff_rs->fields['cost_center_id'],
					'payment_type' => NORMAL,
					'debt_total_id' => $fields['debt_total_id'],
					'debt_ledger_id' => $fields['debt_ledger_id'],
					'debt_tafsili_id' => $fields['debt_tafsili_id'],
					'debt_tafsili2_id' => $fields['debt_tafsili2_id'],
					'cred_total_id' => $fields['cred_total_id'],
					'cred_ledger_id' => $fields['cred_ledger_id'],
					'cred_tafsili_id' => $fields['cred_tafsili_id'],
					'cred_tafsili2_id' => $fields['cred_tafsili2_id']);
				}
				//محاسبه مجموع حقوق مشول بازنشستگي در آخرين حکم ماه جاري
				$this->add_to_last_writ_sum_retired_include($fields,$key,$fields['pay_value']);

				$this->update_sums($fields,$fields['pay_value'] * $time_slice);
			}

			$temp_array = array();

			if( $this->writ_sql_rs->fields['staff_id'] == $this->cur_staff_id ) { //حکم بعدي متعلق به همين شخص است
				continue;
			}

			$success_check = $this->control();

			if( count($this->payment_items) == 0) { //احکام فرد هيچ قلم حقوقي نداشته اند
				//افزودن مبالغ difference در صورت محاسبه از طريق backpay
				if($this->add_difference() && $success_check){
					//ثبت حقوق محاسبه شده براي staff جاري در فايل
					$this->write_to_file();
				}
				$this->initForNextStaff();
				continue;
			}
			//شرح وضعيت : در اين نقطه بايستي تمام اقلام حکمي cur_staff در payment_items قرار گرفته باشد

			$this->process_subtract();
			//شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به وام و کسورو مزاياي ثابت cur_staff محاسبه شده باشد

			$this->process_pay_get_lists();
			// شرح وضعيت : در اين نقطه بايستي تمام اقلام مربوط به کشيک ، حق التدريس ، ماموريت ، اضافه
			//کار و کسور و مزاياي موردي فرد cur_staff محاسبه و دز payment_items قرار گرفته باشند

			//پردازش مربوط به ماليات
			if($this->__CALC_NORMALIZE_TAX == 1) {
				$this->process_tax_normalize();
			}else{
				$this->process_tax();
			}
			//شرح وضعيت : بايد در اين نقطه ماليات فرد هم محاسبه شده باشد

			//پردازش مربوط به مقرري ماه اول
			$this->process_pension();

			//فراخواني تابع محاسبه بازنشتگي
			$this->process_retire();

			//فراخواني تابع محاسبه بيمه تامين اجتماعي
			$this->process_insure();

			//پردازش مربوط به بيمه خدمات درماني
			$this->process_service_insure();

			//فراخواني تابع رير جهت مواردي غير از موارد هميشگي است که معمولا بنا بر نياز مشتري بايد نوشته شوند
			$this->process_custom();

			//افزودن مبالغ difference در صورت محاسبه از طريق backpay
			$this->add_difference();

			//ثبت حقوق محاسبه شده براي staff جاري در فايل
			$this->write_to_file();

			//مقداردهي مجدد متغيرها براي محاسبه حقوق staff بعدي
			$this->initForNextStaff();
		} // end of writ while

		$this->epilogue();
		$this->submit();
		$this->unregister_run();
		$this->statistics();
		return true;
	}

	public function run_back()
	{

echo  "****" ; die();
		//در اين تابع فرض براين است که سال مالي با سال شمسي مطابقت دارد
		$this->empty_back_tables();

		$this->last_month = $this->__MONTH;
		$this->last_month_end = $this->month_end;

		$this->backpay_recurrence = 0;
		//محاسبه حقوق ماههاي قبلي
		for ($i = $this->__BACKPAY_BEGIN_FROM; $i<$this->last_month; $i++) {
			$this->backpay_recurrence++;
			$this->backpay = true;
			$this->month_start = ConvertJ2GDate(1,$i,$this->__YEAR);
			$this->month_end = ConvertJ2GDate($this->get_month_dayno($this->__YEAR,$i),$i,$this->__YEAR);
			$this->__MONTH = $i;
			$this->__MONTH_LENGTH = $this->get_month_dayno($this->__YEAR,$i);
			if(!$this->run()) {
				return false;
			}
			$this->submit_back();
		}

		$this->exe_difference_sql();

		//محاسبه حقوق همين ماه
		$this->backpay_recurrence++;
		$this->backpay = false;
		$this->month_start = ConvertJ2GDate(1,$this->last_month,$this->__YEAR);
		$this->month_end = ConvertJ2GDate($this->get_month_dayno($this->__YEAR,$this->last_month),$this->last_month,$this->__YEAR);
		$this->__MONTH = $this->last_month;
		$this->__MONTH_LENGTH = $this->get_month_dayno($this->__YEAR,$this->last_month);
		$this->run();
	}

	//مقداردهي اوليه متغيرهاي کلاس
	function prologue()
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
		}

		$this->exe_writ_sql();
		$this->exe_pension();
		$this->exe_param_sql();
		$this->exe_paygetlist_sql();
		$this->exe_staff_sql();
		$this->exe_subtract_sql();
		$this->exe_tax_sql();
		$this->exe_tax_history();
		$this->exe_taxtable_sql();
		$this->exe_acc_info();
		$this->exe_service_insure_sql();

		$this->cur_writ_id = $this->writ_sql_rs->fields['writ_id']; //شماره حکم جاري
		$this->cur_staff_id = $this->writ_sql_rs->fields['staff_id']; // شماره staff جاري

		$this->payment_items_file_h = fopen(sisTEMPDIR.'payment_items_file.txt','w+'); //اقلامي که بايد در payment_items درج شوند

		if($this->backpay_recurrence == 1 || !$this->backpay) {
			$this->monitor(7);
			$this->payment_file_h = fopen(sisTEMPDIR.'payment_file.txt','w+'); //اقلامي که بايد در payment درج شوند
			$this->subtract_file_h = fopen(sisTEMPDIR.'subtract_file.txt','w+'); // اقلامي که بايد در person_subtracts بروزرساني شوند
			$this->subtract_flow_file_h = fopen(sisTEMPDIR.'subtract_flow_file.txt','w+'); //اقلامي که بايد در person_flow درج شوند
			$this->fail_log_file_h = fopen(sisTEMPDIR.'fail_log.php','w+');
			$this->success_log_file_h = fopen(sisTEMPDIR.'success_log.php','w+');

			$this->fail_counter = 1;
			$this->success_counter = 1;
			$this->writ_logs_file_header();
		}

		//فقط يكبار چه در حالت  backpay و چه در غير از آن حالت
		if($this->backpay_recurrence <= 1 ) {
			$this->payment_writs_file_h = fopen(sisTEMPDIR.'payment_writs_file.txt','w+'); //اقلامي که بايد در payment_writs درج شوند
		}

		$this->set_work_sheet();  //کارکرد staff جاري در ماه
		return true;
	}

	/*بررسي امکان محاسبه حقوق و نمايش خطا در صورت وجود پروسه همزمان*/
	function check_to_run()
	{
		if($this->backpay_recurrence > 1)
			return true;

		$tmp_rs = parent::runquery('SELECT * FROM payment_runs WHERE time_stamp >= :expireDate',
				array(":expireDate" => time()-$this->expire_time));

		 //هيچ اجراي فعالي وجود ندارد
		if($tmp_rs->recordCount() == 0)
		{
			parent::runquery('INSERT INTO payment_runs(time_stamp,uname) VALUES(?,?)',
				array(time(), $_SESSION["UserID"]));

			$this->run_id = parent::InsertID();
			return true;
		}

		parent::PushException(strstr(ER_CAN_NOT_RUN_PAYMENT_CALC,
    			array("%0%" => $temp[0]["uname"], "%1%" => $this->expire_time)));
		return false;
	}

	/*حذف اجرا از جدول payment_items*/
	function unregister_run()
	{
		if($this->backpay)
			return;
		parent::runquery('DELETE FROM payment_runs WHERE run_id = ?', array($this->run_id));
	}

	/* اجراي query مربوط به ساخت جدول limit_staff که با توجه به شرط ماژول تنظيمات ساخته مي شود*/
	function exe_limit_staff()
	{
		parent::runquery('DROP TABLE IF EXISTS limit_staff');

		parent::runquery('CREATE TABLE limit_staff TYPE=MyISAM AS
							SELECT s.staff_id,s.person_type
							FROM persons p
								INNER JOIN staff s ON(s.personID=p.PersonID AND s.person_type=p.person_type)
								INNER JOIN writs w ON(s.last_writ_id = w.writ_id AND s.last_writ_ver = w.writ_ver AND
														w.staff_id=s.staff_id AND w.person_type=s.person_type)
								INNER JOIN org_new_units o ON(w.ouid = o.ouid)
							WHERE p.person_type NOT IN(' . HR_RETIRED . ') AND ' . $this->__WHERE);

		parent::runquery('ALTER TABLE limit_staff ADD INDEX (staff_id)');
	}

	/* اجراي query استخراج احکام تاثيرگذار در حکم */
	function exe_writ_sql()
	{
		$this->monitor(0);

		parent::runquery('DROP TABLE IF EXISTS med');
		parent::runquery('DROP TABLE IF EXISTS smed');
		parent::runquery('DROP TABLE IF EXISTS mwv');

		parent::runquery("
                        CREATE TEMPORARY TABLE med TYPE=MyISAM AS
                        SELECT w.staff_id,
                               SUBSTRING_INDEX(SUBSTRING(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),11),'.',1) writ_id,
							   SUBSTRING_INDEX(max(CONCAT(w.execute_date,w.writ_id,'.',w.writ_ver)),'.',-1) writ_ver
                        FROM writs w
                                 INNER JOIN limit_staff ls ON(w.staff_id = ls.staff_id)
								 
                        WHERE w.execute_date <= '" . $this->month_start . "' AND
                                  w.pay_date <= '" . $this->last_month_end . "' AND
                                  w.state = " . WRIT_SALARY . " AND
                                  w.history_only = 0
                        GROUP BY w.staff_id;
                ");

		parent::runquery("
                        CREATE TEMPORARY TABLE smed TYPE=MyISAM AS
                        SELECT staff_id,
                               SUBSTRING_INDEX(SUBSTRING(max_execute_date,11),'.',1) writ_id,
                               SUBSTRING_INDEX(max_execute_date,'.',-1) writ_ver
                        FROM med;
                ");

		parent::runquery("ALTER TABLE smed ADD INDEX (staff_id,writ_id,writ_ver)");

		parent::runquery("
						CREATE TEMPORARY TABLE mwv  TYPE=MyISAM AS
                        SELECT  w.staff_id,
								w.writ_id,
								MAX(w.writ_ver) writ_ver

                        FROM writs w
							INNER JOIN limit_staff ls
								ON(w.staff_id = ls.staff_id)

                        WHERE w.execute_date <= '" . $this->month_end . "' AND
                              w.execute_date > '" . $this->month_start . "' AND
                              w.pay_date <= '" . $this->last_month_end . "' AND
                              w.history_only = 0 AND
                              w.state = " . WRIT_SALARY . "
								  
                        GROUP BY w.staff_id, w.writ_id"
                );

		parent::runquery("ALTER TABLE mwv ADD INDEX (staff_id,writ_id,writ_ver)");

		/*
		 اقلام آخرین حکم افراد
		  union all
		  اقلام کلیه نسخه های نهایی احکام افراد
		*/
		$this->writ_sql_rs = parent::runquery('
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
                           wsi.total_id dept_total_id,
                           wsi.ledger_id dept_ledger_id,
                           wsi.tafsili_id dept_tafsili_id,
                           wsi.tafsili2_id dept_tafsili2_id,
                           0 as cred_total_id,
                           0 as cred_ledger_id,
                           0 as cred_tafsili_id,
                           0 as cred_tafsili2_id,
                           sit.insure_include,
                           sit.tax_include,
                           sit.retired_include,
                           sit.pension_include,
                       	   sit.validity_start_date,
                           sit.validity_end_date,
                           sit.month_length_effect

                        FROM limit_staff ls
							INNER JOIN smed sm ON(ls.staff_id = sm.staff_id)
                            INNER JOIN writs w ON(w.writ_id = sm.writ_id AND w.writ_ver = sm.writ_ver AND w.staff_id=sm.staff_id)
                            LEFT OUTER JOIN writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver
								AND w.staff_id=wsi.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
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
                           wsi.total_id,
                           wsi.ledger_id,
                           wsi.tafsili_id,
                           wsi.tafsili2_id,
                           0 as cred_total_id,
                           0 as cred_ledger_id,
                           0 as cred_tafsili_id,
                           0 as cred_tafsili2_id,
                           sit.insure_include,
                           sit.tax_include,
                           sit.retired_include,
                           sit.pension_include,
                       	   sit.validity_start_date,
                           sit.validity_end_date,
                           sit.month_length_effect
                        FROM mwv
                             INNER JOIN writs w ON(mwv.writ_id = w.writ_id AND mwv.writ_ver = w.writ_ver AND mwv.staff_id=w.staff_id)
                             INNER JOIN limit_staff ls ON(w.staff_id = ls.staff_id)
                             LEFT OUTER JOIN writ_salary_items wsi ON(w.writ_id = wsi.writ_id AND w.writ_ver = wsi.writ_ver
									AND wsi.staff_id=w.staff_id AND wsi.must_pay = ' . MUST_PAY_YES . ')
                             LEFT OUTER JOIN salary_item_types sit ON(wsi.salary_item_type_id = sit.salary_item_type_id)

                        WHERE w.state = ' . WRIT_SALARY . ')
							
                        ORDER BY staff_id,execute_date,writ_id,writ_ver
                ');
	}

	/* نوشتن اطلاعات  مربوط به مراحل محاسبه حقوق در فايل مربوطه*/
	function monitor($curStep)
	{
		$head = '
			<html dir="rtl">
			<head>
			<meta http-equiv="refresh" content="5">
			<title>مشاهده مراحل محاسبه حقوق</title>
			</head>
			<body>
			<table border="0" width="100%" cellpadding="2">
				<tr>
					<td colspan="2">
						<font face="Tahoma" size="2" color="#996633">
							<u>محاسبه حقوق ' . DateModules::GetMonthName($this->__MONTH) . '</u>
						</font>
					</td>
				</tr>
				<tr>';
		
		$end = '
				<tr>
					<td colspan="2">
						<p align="center">
						<a href="#" onclick="window.close()">
						<font face="Tahoma" size="2" color="#008000">بستن</font>
						</a></p>
						<p><font face="Tahoma" size="2" color="#ff0000">توجه: در صورت بستن پنجره ، روال محاسبه متوقف نخواهد شد.</font></p>
					</td>
				</tr>
			</table>
			</body>
            </html>';

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
		$fh = fopen(HR_TemlDirPath . 'pay_calc_monitor_file.html','w+');
		fwrite($fh, $head . $txt . $end);
		fclose($fh);
	}

	/*اجراي query مربوط به استخراج حداکثر حقوق مشمول بازنشستگي که مقرري به آن تعلق مي گيرد*/
	function exe_pension()
	{
		$this->monitor(1);

		parent::runquery('DROP TABLE IF EXISTS temp_pension');
		parent::runquery('
						CREATE TEMPORARY TABLE temp_pension  TYPE=MyISAM AS
						(SELECT ls.staff_id,MAX(pit.param4 * 1) AS mpension

						FROM limit_staff ls
							LEFT OUTER JOIN payment_items pit ON(ls.staff_id = pit.staff_id AND pit.payment_type ='.NORMAL_PAYMENT.')

						WHERE ( ( (pit.pay_year = '.$this->__YEAR.' AND pit.pay_month < '.$this->__MONTH.') OR
								  (pit.pay_year < '.$this->__YEAR.') )	AND
								( pit.salary_item_type_id IN('.SIT_STAFF_RETIRED.',
															 '.SIT_PROFESSOR_RETIRED.',
															 '.SIT_WORKER_RETIRED.') ) ) OR
							  pit.staff_id IS NULL

						GROUP BY ls.staff_id)

						UNION ALL
						
						(SELECT ls.staff_id,MAX(pit.param4 * 1) AS mpension

						FROM limit_staff ls
								 LEFT OUTER JOIN back_payment_items pit
										 ON(ls.staff_id = pit.staff_id AND pit.payment_type = '.NORMAL_PAYMENT.')
						WHERE ( ( (pit.pay_year = '.$this->__YEAR.' AND pit.pay_month <= '.$this->__MONTH.') OR
								  (pit.pay_year < '.$this->__YEAR.') )	AND
							  (pit.salary_item_type_id IN('.SIT_STAFF_RETIRED.',
														  '.SIT_PROFESSOR_RETIRED.',
														  '.SIT_WORKER_RETIRED.') ) ) OR
							  pit.staff_id IS NULL

						GROUP BY ls.staff_id)');

		$this->pension_rs = parent::runquery('SELECT staff_id,MAX(mpension) max_sum_pension
											  FROM temp_pension
											  GROUP BY staff_id');
	}

	/*اجراي query ليست پرارمترهاي حقوقي و انتقال آنها به يک آرايه */
	function exe_param_sql()
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
			$this->salary_params[$tmp_rs[$i]['param_type']] = array(
				'person_type' => $tmp_rs[$i]['person_type'],
				'dim1_id' => $tmp_rs[$i]['dim1_id'],
				'dim2_id' => $tmp_rs[$i]['dim2_id'],
				'dim3_id' => $tmp_rs[$i]['dim3_id'],
				'value'   => $tmp_rs[$i]['value']);
		}
	}

	/* اجراي query مربوط به اضافه کار ، حق کشيک ، حق التدريس ، ماموريت ، کسور و مزاياي موردي*/
	function exe_paygetlist_sql()
	{
		$this->monitor(3);

		//در محاسبه backpay فقط اقلامي كه مشمول backpay هستند محاسبه مي شوند
		$backpay_where = '1=1';

		if($this->backpay)
			$backpay_where = 'sit.backpay_include = 1';
		
		$this->pay_get_list_rs = parent::runquery('
                        (SELECT pgli.staff_id staff_id,
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
                               pgli.total_id,
                               pgli.ledger_id,
                               pgli.tafsili_id,
                               pgli.tafsili2_id,
                               approved_amount,
                               initial_amount,
                               value,
                               0 as travel_cost

                        FROM pay_get_lists pgl
                             INNER JOIN pay_get_list_items pgli
                                   ON(pgl.list_id = pgli.list_id AND pgl.list_date >= \''.$this->month_start.'\'
									   AND pgl.list_date <= \''.$this->month_end.'\' AND doc_state = \''.CENTER_CONFIRM.'\')
                             INNER JOIN limit_staff ls ON(pgli.staff_id = ls.staff_id)
                             INNER JOIN salary_item_types sit
                                   ON(pgli.salary_item_type_id = sit.salary_item_type_id AND '.$backpay_where.')
                        )

                        UNION ALL
                        (SELECT mli.staff_id staff_id,
                               ml.list_id list_id,
                               '.MISSION_LIST.' as list_type,
                               mli.using_facilities,
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
                               0 as total_id,
                               0 as ledger_id,
                               0 as tafsili_id,
                               0 as tafsili2_id,
                               mli.duration,
                               0 as initial_amount,
                               region_coef value,
                               travel_cost

                        FROM mission_lists ml
                             INNER JOIN mission_list_items mli
                                   ON(ml.list_id = mli.list_id AND ml.list_date >= \''.$this->month_start.'\' AND
									   ml.list_date <= \''.$this->month_end.'\' AND doc_state = \''.CENTER_CONFIRM.'\')
                             INNER JOIN limit_staff ls
                                        ON(mli.staff_id = ls.staff_id)
                             INNER JOIN salary_item_types sit
                                   ON(mli.salary_item_type_id = sit.salary_item_type_id AND '.$backpay_where.')
                        )
                        ORDER by staff_id,list_type DESC
                        ');
	}

	/* اجراي query مربوط به ليست staff با اطلاعات جانبازي و ايثارگري*/
	function exe_staff_sql()
	{
		parent::runquery("SET NAMES 'utf8'");

		parent::runquery('DROP TABLE IF EXISTS dvt');

		parent::runquery('
                        CREATE TEMPORARY TABLE dvt TYPE=MyISAM AS
                        SELECT PersonID,
                               MAX(CASE devotion_type WHEN '.FREEDOM_DEVOTION.' THEN amount ELSE 0 END) freedman,
                               MAX(CASE devotion_type WHEN '.SACRIFICE_DEVOTION.' THEN amount ELSE 0 END) sacrifice

                        FROM person_devotions

                        WHERE personel_relation = '.OWN.' AND (devotion_type = '.FREEDOM_DEVOTION.' OR devotion_type = '.SACRIFICE_DEVOTION.')

                        GROUP BY PersonID;
                ');

		parent::runquery('ALTER TABLE dvt ADD INDEX (PersonID)');

		parent::runquery('DROP TABLE IF EXISTS temp_last_writs');

		parent::runquery('
                        CREATE TEMPORARY TABLE temp_last_writs TYPE=MyISAM AS
						SELECT w.staff_id,
						       SUBSTR(MAX(CONCAT(w.execute_date,w.writ_id)),11) max_writ_id,
						       SUBSTRING_INDEX(MAX(CONCAT(w.execute_date,w.writ_id,\'.\',w.writ_ver)),\'.\',-1) max_writ_ver
						FROM limit_staff ls
						     INNER JOIN writs w ON(ls.staff_id = w.staff_id)
						WHERE w.execute_date <= \''.$this->month_end.'\' AND
						      w.pay_date <= \''.$this->last_month_end.'\' AND
						      w.history_only = 0 AND
						      w.state = '.WRIT_SALARY.'
						GROUP BY staff_id
				');

		$this->staff_db->Execute('
                        ALTER TABLE temp_last_writs ADD INDEX (staff_id,max_writ_id,max_writ_ver);
                ');

		$this->staff_rs = $this->staff_db->Execute('
                        SELECT s.staff_id,
                               si.staff_id si_staff ,
                        	   si.insure_include,
                               si.tax_include,
                               si.service_include,
                               si.retired_include,
                               si.pension_include,
                               s.last_retired_pay,
                               s.tafsili_id,
                               s.person_type,
                               s.PersonID,
                               s.bank_id,
                               s.account_no,
                               s.sum_paied_pension,
                               d.freedman,
                               d.sacrifice,
                               w.cost_center_id,
                               w.ouid,
                               w.emp_state,
                               w.salary_pay_proc,
                               w.worktime_type,
                               w.emp_mode ,
                               p.staff_id pstaff_id,
                               CONCAT(per.plname,\' \',per.pfname) name

                        FROM limit_staff ls
                                 INNER JOIN staff s
                                 	ON(s.staff_id = ls.staff_id)
						         LEFT OUTER JOIN staff_include_history si
						            ON(s.staff_id = si.staff_id AND si.start_date <= \''.$this->month_end.'\' AND (si.end_date IS NULL OR si.end_date >= \''.$this->month_end.'\') )
                      			 INNER JOIN persons per
                       				ON(s.PersonID = per.PersonID)
                       			 INNER JOIN temp_last_writs tlw
                       			 	ON(s.staff_id = tlw.staff_id)
                             	 INNER JOIN writs w
                                    ON(tlw.max_writ_id = w.writ_id AND tlw.max_writ_ver = w.writ_ver)
                                 LEFT OUTER JOIN dvt d
                                    ON(s.PersonID = d.PersonID)
                                 LEFT OUTER JOIN payments p
                                    ON(w.staff_id = p.staff_id AND p.pay_year = '.$this->__YEAR.' AND p.pay_month='.$this->__MONTH.' AND p.payment_type = '.NORMAL_PAYMENT.')
                        ORDER BY s.staff_id
                        ');

	}



}




?>
