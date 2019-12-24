<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	93.05
//---------------------------

class manage_payment_cancel extends PdoDataAccess
{
	public $year ;
	public $month ;
	public $payment_type;
	public $where_clause ;
	public $staff_id ; 		
	public $cost_center_id;	
	public $hrms_person_type;	
	public $start_date;
	public $end_date;	
	public $success_count;
	public $unsuccess_count;
	public $staff_where;
	public $writ_where;
	
	public $__WHERE;
	public $__WHEREPARAM;
	
	
	function __construct()
	 {
		        
	 	return;
	 }
	 
	 /*تهيه شرط مربوط به staff*/
	private function prepare_wheres() {
		//توليد شرط مربوط به افراد
		$this->staff_where = '1=1';
		if($this->staff_id) {
			$this->staff_where .= ' AND s.staff_id = '.$this->staff_id;
		}
		
		/*if( !empty($this->person_type) && $this->person_type != PERSON_TYPE_ALL) {
			$this->staff_where .= ' AND s.person_type = '.$this->person_type;
		}
		elseif( !empty($this->person_type) &&  $this->person_type == PERSON_TYPE_ALL) {
			$this->staff_where .= ' AND s.person_type in (1,2,3,5) ' ;
		}*/
		if(!($this->staff_id > 0 ))
		$this->staff_where .= ' ' ;
		
		//توليد شرط مربوط به پرداخت
		//توليد شرط مربوط به مركز هزينه در حكم
		$this->where_clause = '1=1';
		$this->writ_where = '1=1';
		
		if($this->cost_center_id) {
			$this->where_clause .= ' AND pit.cost_center_id in ( '.$this->cost_center_id.')';
			$this->writ_where .= ' AND w.cost_center_id in ('.$this->cost_center_id.')';
		}		
		$this->where_clause .= ' AND pit.pay_year = '.$this->year;
		$this->where_clause .= ' AND pit.pay_month = '.$this->month;
		$this->where_clause .= ' AND pit.payment_type = '.$this->payment_type;
	}
	
	/*بازكردن فايل خطا و خالي كردن جدول كارمندان غير قابل حذف*/
	/*در صورتي كه مركز هزينه انتخاب شده فيش داشته باشد ابطال از فيش صورت خواهد گرفت در غير اين صورت از حكم 
	مركز هزينه تعيين خواهد شد.*/
	private function init() {
		
	
		parent::runquery('TRUNCATE delete_payment_staff;');
		parent::runquery('DROP TABLE IF EXISTS temp_cancel_limit_staff;');
		
		parent::runquery('CREATE TABLE temp_cancel_limit_staff  AS
							SELECT DISTINCT s.staff_id , s.PersonID
							FROM HRM_staff s
								 INNER JOIN HRM_writs w
								 	   ON(s.last_writ_id = w.writ_id AND s.last_writ_ver = w.writ_ver AND s.staff_id = w.staff_id )
									   
							     LEFT OUTER JOIN HRM_payment_items pit
							           ON(s.staff_id = pit.staff_id AND '.$this->where_clause.')
										   
								 LEFT JOIN HRM_payments p 
									   ON pit.staff_id = p.staff_id AND 
									      pit.pay_year = p.pay_year AND 
										  pit.pay_month = p.pay_month AND 
										  pit.payment_type = p.payment_type 
										  
								 LEFT JOIN BSC_jobs bj
										ON bj.JobID = w.job_id
										
							WHERE p.state = 1 AND 
								  '.$this->staff_where.' AND '.
	    						  '((pit.staff_id IS NOT NULL AND '.$this->where_clause.') OR '.
	    						  '('.$this->writ_where.'))' .$this->__WHERE ,$this->__WHEREPARAM ); 
	    						  
	    			
		parent::runquery('ALTER TABLE temp_cancel_limit_staff ADD INDEX(staff_id);');
		
		$this->end_date =  DateModules::shamsi_to_miladi($this->year.'/'.$this->month.'/'.DateModules::DaysOfMonth($this->year, $this->month)) ;  
		$this->start_date = DateModules::shamsi_to_miladi($this->year.'/'.$this->month.'/01') ; 
		
		$this->success_count = array();
	    $this->unsuccess_count = 0;
			
		
	}
	
	/*درج افرادي كه محاسبه حقوق آنها قطعي شده است*/
	private function ins_commited_staff() {
		
		parent::runquery('INSERT INTO delete_payment_staff
				    		 SELECT p.staff_id,
				    		 		null as salary_item_type_id,
				    		 		p.state
				    		 FROM temp_cancel_limit_staff ls
				    		 	  INNER JOIN HRM_payments p
				    		 	  	ON(ls.staff_id = p.staff_id)
				    		 WHERE p.pay_year = '.$this->year.' AND
				    		 	   p.pay_month = '.$this->month.' AND
				    		 	   p.payment_type = '.$this->payment_type.' AND
				    		 	   p.state = '.PAYMENT_STATE_FINAL.';'); 
								   
	    
		
	}
	
	/*درج افرادي كه آخرين گردش آنها مربوط به فيش حقوقي  نيست كه نم يتوان آنها را حذف كرد*/
	private function ins_last_flow_not_fich() {
		
		if($this->payment_type != NORMAL_PAYMENT)
			return;
		
		parent::runquery('DROP TABLE IF EXISTS temp_subtract_fich') ;
	
		parent::runquery('CREATE TABLE temp_subtract_fich  AS 
						 SELECT 
							        ls.staff_id,
							        ps2.salary_item_type_id,							     
							        SUM(CASE 
							                WHEN 
							                	 psf2.flow_date >= p.calc_date
											THEN 1
							                ELSE 0
							           END) fich_no
									   
							FROM person_subtract_flows psf2
							     INNER JOIN person_subtracts ps2
							           ON(psf2.subtract_id = ps2.subtract_id)
									   
							      INNER JOIN persons per
									   ON per.PersonID = ps2.PersonID
						 
							     INNER JOIN temp_cancel_limit_staff ls
							     	   ON(ps2.PersonID = ls.PersonID)
									   
								 INNER JOIN payments p 
									   ON p.staff_id = ls.staff_id AND p.pay_year = '.$this->year.' AND 
										  p.pay_month = '.$this->month.' AND p.payment_type = '.$this->payment_type.'
									   
							WHERE ps2.subtract_type IN(1,2) AND psf2.flow_type = 3  
							GROUP BY psf2.subtract_id,
							         ls.staff_id,
							         ps2.salary_item_type_id
							HAVING fich_no > 0; ') ;
		 		
		parent::runquery(' INSERT INTO delete_payment_staff
							SELECT staff_id,
							       salary_item_type_id,
							       null as state
							FROM temp_subtract_fich 
							  ');		
							 		
	}
	
	/*ذخيره سازي خطاها در فايل*/
	function fail_log() {
		$fail_counter = 1;
		
		$res = parent::runquery('SELECT dps.staff_id,
										dps.state,
										sit.full_title,
										CONCAT(p.plname," ",p.pfname) name

								FROM  delete_payment_staff dps
										LEFT OUTER JOIN salary_item_types  sit
											ON (dps.salary_item_type_id = sit.salary_item_type_id)
										LEFT OUTER JOIN staff s
											ON (s.staff_id = dps.staff_id)
										LEFT OUTER JOIN persons p
											ON (s.PersonID = p.PersonID)
								ORDER BY name;');
			
		$row = '<html dir="rtl">
	                        <head>
	                        <meta http-equiv="Content-Language" content="fa">
	                        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	                        <title>ليست خطاها</title>
	                        </head>
	                        <body>
	                        <table border="1" width="100%" style="font-family:B Nazanin; border-collapse: collapse">
	                        <tr>
	                            <td width="5%" align="center" bgcolor="#FF0000"><font color="#FFFFFF"><b>رديف</b></font></td>
	                            <td width="15%" align="center" bgcolor="#FF0000"><font color="#FFFFFF"><b>شماره شناسايي</b></font></td>
	                            <td width="30%" align="center" bgcolor="#FF0000"><font color="#FFFFFF"><b>نام خانوادگي و نام</b></font></td>
	                            <td width="50%" align="center" bgcolor="#FF0000" ><font color="#FFFFFF"><b>خطا</b></font></td>
	                        </tr>';
		
		if(count($res)> 0) 
		{
			for($i=0; $i < count($res); $i++)
			{
				
				if($res[$i]['state'] == PAYMENT_STATE_FINAL) {
					$txt = 'به دليل اينکه براي شخص با شماره شناسايي '.$res[$i]['staff_id'].' محاسبه حقوق قطعي شده است، امکان ابطال محاسبه حقوق وجود ندارد.';	
				}
				else {
					$txt = ' به دليل اينکه وام/کسور ثابت <font color=red>'.$res[$i]['full_title'].'</font> پس از محاسبه حقوق براي آن گردش ثبت شده است امکان ابطال محاسبه حقوق وجود ندارد. براي ابطال محاسبه حقوق بايد گردش با نوع <font color=black>فيش حقوقي</font> آخرين گردش باشد  و پس از آن گردشي وجود نداشته باشد.';
				}
				$row .= '<tr>
			                    <td bgcolor="#F5F5F5">'.$fail_counter++.'</td>
			                    <td bgcolor="#F5F5F5">'.$res[$i]['staff_id'].'</td>
			                    <td bgcolor="#F5F5F5">'.$res[$i]['name'].'</td>
			                    <td bgcolor="#F5F5F5">'.$txt.'</td>
			            </tr>';
				
				
			}			
		}
		
		$row .= '</table>
				 </body>
				 </html>';
		
		$fail_log_file_h = fopen('../../../../HumanResources/tempDir/cancel_fail_log.php','w');
		fwrite($fail_log_file_h, $row);	
		fclose($fail_log_file_h);		
		$this->unsuccess_count = $fail_counter - 1;				
	}
	
	/*حذف افرادي كه نبايد در ابطال فيش آنها ابطال شود از جدول limit_staff*/
	private function remove_from_limit_staff() {
		
		parent::runquery('DELETE FROM temp_cancel_limit_staff
							 USING  temp_cancel_limit_staff
							 		INNER JOIN delete_payment_staff dp
							 			ON(temp_cancel_limit_staff.staff_id = dp.staff_id)') ; 
				
	}
	
	/*  حذف گردشهای موقت و بروز رسانی هدر مربوط به وام ها */
	private function remove_temp_subtract_flow() {
		
		$DTF = parent::runquery(" SELECT psf.row_no 
								  FROM  person_subtract_flows psf INNER JOIN person_subtracts ps
																					ON( psf.subtract_id = ps.subtract_id AND psf.tempFlow = 1  AND  
																						psf.flow_date >= '".$this->start_date."' AND psf.flow_date < '".$this->end_date."')
																	INNER JOIN temp_cancel_limit_staff ls
																					ON(ps.PersonID = ls.PersonID) 
								   WHERE psf.tempFlow = 1 AND  psf.flow_date >= '".$this->start_date."' AND psf.flow_date < '".$this->end_date."'");
								   
	    if(count($DTF) > 0 ) 
		{
			$WhrDF ="" ; 
			for($t=0; $t < count($DTF) ;$t++)
			{				
				$WhrDF .= $DTF[$t]['row_no'].',' ; 
			}
						
			$WhrDF = substr($WhrDF,0,(strlen($WhrDF)-1)) ; 		
			parent::runquery(" DELETE FROM person_subtract_flows WHERE row_no in (".$WhrDF.") ");
			
		}
		
		/*
		parent::runquery(" DELETE psf.*  
								FROM  person_subtract_flows psf INNER JOIN person_subtracts ps
																				ON( psf.subtract_id = ps.subtract_id AND psf.tempFlow = 1  AND  
																				    psf.flow_date >= '".$this->start_date."' AND psf.flow_date < '".$this->end_date."')
																INNER JOIN temp_cancel_limit_staff ls
																				ON(ps.PersonID = ls.PersonID) 
								WHERE psf.tempFlow = 1 AND  psf.flow_date >= '".$this->start_date."' AND psf.flow_date < '".$this->end_date."'");
						echo ExceptionHandler::PopAllExceptions() ; 		
				echo 	PdoDataAccess::GetLatestQueryString(); ; die() ; 	 */ 
		
	}
	
	/*حذف اقلام فيش*/
	private function remove_payment_items($PayType="") {
		
	    parent::runquery('DELETE FROM HRM_payment_items
							 USING  temp_cancel_limit_staff ls
							 		INNER JOIN HRM_payment_items
							 			ON(ls.staff_id = HRM_payment_items.staff_id)
							 WHERE HRM_payment_items.pay_year = '.$this->year.' AND
				    		 	   HRM_payment_items.pay_month = '.$this->month.' AND
				    		 	   HRM_payment_items.payment_type = '.$this->payment_type);
		
		$this->success_count['FICH_ITEM'] = parent::AffectedRows() ; 
		
	}
	
	/*حذف احكام مربوط به محاسبه*/
	private function remove_payment_writs() {
	
		parent::runquery('DELETE FROM HRM_payment_writs
							 USING  temp_cancel_limit_staff ls
							 		INNER JOIN HRM_payment_writs
							 			ON(ls.staff_id = HRM_payment_writs.staff_id)	
							 WHERE HRM_payment_writs.pay_year = '.$this->year.' AND
				    		 	   HRM_payment_writs.pay_month = '.$this->month.' AND
				    		 	   HRM_payment_writs.payment_type = '.$this->payment_type);	
		
		$this->success_count['WRIT'] = parent::AffectedRows() ; 
	}
	
	/*حذف فيش*/
	private function remove_payments($PayType="") {
		
		
			parent::runquery('DELETE FROM HRM_payments
								 USING  temp_cancel_limit_staff ls
										INNER JOIN HRM_payments
											ON(ls.staff_id = HRM_payments.staff_id)		
								 WHERE HRM_payments.pay_year = '.$this->year.' AND
									   HRM_payments.pay_month = '.$this->month.' AND
									   HRM_payments.payment_type = '.$this->payment_type) ; 
				
		
		$this->success_count['FICH'] = parent::AffectedRows() ;
	}
	
	private function update_person_dependent_support(){
		
		$year = $this->year ;
		$month = $this->month - 1 ;
		if($month == 0){
			$month = 12 ;
			$year = $year - 1 ; 
		}
		
		parent::runquery('
			UPDATE person_dependent_supports pds
			INNER JOIN person_dependents pd
			   ON(pd.PersonID = pds.PersonID AND pd.row_no = pds.master_row_no)
			INNER JOIN staff s
			   ON(pds.PersonID = s.PersonID)
			INNER JOIN temp_cancel_limit_staff ls
			   ON(s.staff_id = ls.staff_id)
			LEFT OUTER JOIN payment_items pi
				ON pi.staff_id = ls.staff_id 
				AND pi.pay_year = '.$this->year.' 
				AND pi.pay_month = '.$this->month.' 
				AND pi.payment_type = '.NORMAL_PAYMENT.'
			SET 
				calc_year_to = (CASE WHEN calc_year_from * 100 + calc_month_from <= '.($year*100+$month).' THEN '.$year.' ELSE NULL END ),
				calc_month_to = (CASE WHEN calc_year_from * 100 + calc_month_from <= '.($year*100+$month).' THEN '.$month.' ELSE NULL END ),
				calc_year_from = (CASE WHEN calc_year_from * 100 + calc_month_from <= '.($year*100+$month).' THEN calc_year_from ELSE NULL END ),
				calc_month_from = (CASE WHEN calc_year_from * 100 + calc_month_from <= '.($year*100+$month).' THEN calc_month_from ELSE NULL END )
			WHERE pi.staff_id IS NULL 
         ') ; 

		
	}
		
	/*روال اصلي حذف*/
	public function run() {
	
		$pdo = parent::getPdoObject();
		$pdo->beginTransaction();
		
		//if($this->payment_type == 1 ) {
			$this->prepare_wheres();
			$this->init();
	 
			$this->ins_commited_staff(); 
	
			$this->remove_from_limit_staff();		
			$this->remove_payment_items();
			$this->remove_payment_writs();
			$this->remove_payments();			
		
		//}
		
		
		if(parent::GetExceptionCount() > 0 )
		{
		
			print_r(ExceptionHandler::PopAllExceptions()); echo "----"; 
			die() ; 
			$pdo->rollBack();
			return false ;
		}
		else 
		{
			$pdo->commit();
			return true ;
		} 
				
	}	

}

?>