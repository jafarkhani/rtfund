<?php

//-----------------------------
//	Programmer	: b.Mahdipour
//	Date		: 92.09
//-----------------------------

require_once '../../../header.inc.php';
require_once '../../org_process_docs/class/group_pay_get_log.class.php';
require_once '../class/payment_calculation.class.php';
require_once '../class/payments.class.php';
require_once '../class/payment_items.class.php';
require_once 'phpExcelReader.php';
require_once(inc_response);

ini_set("display_errors","On"); 
function exe_param_sql($PayYear, $PayMonth, &$salaryParam) {

    $SDate = $PayYear . "/" . $PayMonth . "/01";
    $SDate = DateModules::shamsi_to_miladi($SDate);

    if ($PayMonth < 7)
        $endDay = "31";
    elseif ($PayMonth > 6 && $PayMonth < 12)
        $endDay = "30 ";
    elseif ($PayMonth == 12)
        $endDay = "29";

    if (DateModules::YearIsLeap($PayYear))
        $endDay = 30;
    else
        $endDay = 29;

    $EDate = $PayYear . "/" . $PayMonth . "/" . $endDay;
    $EDate = DateModules::shamsi_to_miladi($EDate);

    $tmpRes = PdoDataAccess::runquery('SELECT   param_type,
												dim1_id,
												value
										FROM salary_params
										WHERE from_date <= \'' . $EDate . '\' AND to_date >= \'' . $SDate . '\' AND
												param_type IN(' . SPT_HANDSEL_VALUE . ',' . SPT_JOB_SALARY . ')');

    for ($t = 0; $t < count($tmpRes); $t++) {
        $tmpRes[$t]['dim1_id'] = ($tmpRes[$t]['dim1_id'] == NULL) ? 0 : $tmpRes[$t]['dim1_id'];
        $salaryParam[$tmpRes[$t]['param_type']][$tmpRes[$t]['dim1_id']] = $tmpRes[$t]['value'];
    }
}

function exe_taxtable_sql($PayYear, $PayMonth, &$taxTable) {

    $SDate = $PayYear . "/" . $PayMonth . "/01";

    if ($PayMonth < 7)
        $endDay = "31";
    elseif ($PayMonth > 6 && $PayMonth < 12)
        $endDay = "30 ";
    elseif ($PayMonth == 12)
        $endDay = "29";

    if (DateModules::YearIsLeap($PayYear))
        $endDay = 30;
    else
        $endDay = 29;

    $EDate = $PayYear . "/" . $PayMonth . "/" . $endDay;

    $tmp_rs = PdoDataAccess::runquery("
                        SELECT ttype.person_type,
                               ttype.tax_table_type_id,
                               ttable.from_date,
                               ttable.to_date,
                               titem.from_value,
                               titem.to_value,
                               titem.coeficient

                        FROM HRM_tax_table_types ttype
                             INNER JOIN HRM_tax_tables ttable
                                   ON(ttype.tax_table_type_id = ttable.tax_table_type_id AND from_date <= '" . DateModules::shamsi_to_miladi($EDate) . "' AND 
								                                                             to_date >= '" . DateModules::shamsi_to_miladi($SDate) . "')
                             INNER JOIN HRM_tax_table_items titem
                                   ON(ttable.tax_table_id = titem.tax_table_id)

                        ORDER BY ttype.person_type,ttype.tax_table_type_id,ttable.from_date,titem.from_value
                        ");

    

    for ($i = 0; $i < count($tmp_rs); $i++) {
        $taxTable[$tmp_rs[$i]['tax_table_type_id']][] = array(
            'from_date' => $tmp_rs[$i]['from_date'],
            'to_date' => $tmp_rs[$i]['to_date'],
            'from_value' => $tmp_rs[$i]['from_value'],
            'to_value' => $tmp_rs[$i]['to_value'],
            'coeficient' => $tmp_rs[$i]['coeficient']);
    }
}

//پردازش ماليات تعديل شده
function process_tax_normalize($staffID, $PayVal, $PayYear, $PayMonth) {

    $SPayMonth = 1 ;
    $EPayMonth  = $PayMonth ; 
    $SDate = $PayYear . "/" . $SPayMonth . "/01";

    if ($EPayMonth < 7)
        $endDay = "31";
    elseif ($EPayMonth > 6 && $EPayMonth < 12)
        $endDay = "30 ";
    elseif ($EPayMonth == 12)
        $endDay = "29";

    $EDate = $PayYear . "/" . $EPayMonth . "/" . $endDay;
    $EDate = DateModules::shamsi_to_miladi($EDate);
    $SDate = DateModules::shamsi_to_miladi($SDate);

    $qry = " select tax_include
					from HRM_staff_include_history
						where staff_id = " . $staffID . " and start_date <= '" . $EDate . "' AND
							 (end_date IS NULL OR end_date = '0000-00-00' OR
							  end_date >= '" . $EDate . "' OR end_date > '" . $SDate . "' ) ";
							  
    $res = PdoDataAccess::runquery($qry);
	if (count($res) == 0)
	{
		return;
	}
    if ($res[0]['tax_include'] == 0) {
        return;
    }
 
    //..........................................
    
    $qry = " select  staff_id,  
					SUM(sum_tax) sum_tax,
					SUM(sum_tax_include) sum_tax_include , 
					SUM(BimeMoaf) sum_BimeMoaf 

			from
			(
                        SELECT
                              pit.staff_id staff_id, 
                              SUM(pit.get_value +
                                 (pit.diff_get_value * pit.diff_value_coef)  ) sum_tax,
                              SUM(pit.param1 + pit.diff_param1 *
                              pit.diff_value_coef ) sum_tax_include ,
                              SUM(pit.param6 + pit.diff_param6 * 
                              pit.diff_value_coef) BimeMoaf
								
                        FROM    HRM_payment_items pit 								   							     

                        WHERE     pit.pay_year >= " . $PayYear . " AND
				  pit.pay_month >= " . $SPayMonth . " AND                                    
                                  pit.pay_month <= " . $EPayMonth . " AND
                                  pit.payment_type = 1 AND 
				  pit.salary_item_type_id IN( 8 ) AND
	   		          pit.staff_id = " . $staffID ." 
                                      
                        UNION ALL 
                        
                        select  pit.staff_id staff_id, 
                                SUM( pit.get_value +
                                   ( pit.diff_get_value * pit.diff_value_coef) ) sum_tax,
                                SUM(pit.param1) sum_tax_include , 0 BimeMoaf 

                        from    HRM_payment_items pit
                        where
                                pit.pay_year >= " . $PayYear . " AND
                                pit.pay_month >= " . $SPayMonth . " AND                                    
                                pit.pay_month <= " . $EPayMonth . " AND
                                pit.payment_type !=1 AND  						
                                pit.salary_item_type_id IN(8)   AND
                                pit.staff_id = " . $staffID ." 

                        )
			tbl1
                         "; 
    
    $taxRes = PdoDataAccess::runquery($qry);

    //.........................................................................

    $qry2 = "
				SELECT  sth.staff_id,
						sth.start_date,
						sth.end_date,
						sth.tax_table_type_id,
						sth.payed_tax_value

				FROM    HRM_staff_tax_history sth 

				WHERE (end_date IS NULL OR end_date = '0000-00-00' OR  end_date > '" . $SDate . "' ) AND
					  start_date < '" . $EDate . "' AND  sth.staff_id = " . $staffID . " 
				ORDER BY sth.staff_id,sth.start_date			
				
				";
    $taxHisRes = PdoDataAccess::runquery($qry2);
    $tax_table_type_id = $taxHisRes[0]['tax_table_type_id'];

    exe_taxtable_sql($PayYear, $PayMonth, $taxTable);
    
    //.........................................................................
    /* تعدیل مالیات با توجه به بازه مربوط به آن ترم  در نظر گرفته می شود */
 
    $year_avg_tax_include = (( $taxRes[0]['sum_tax_include'] + $PayVal + $taxHisRes[0]['payed_tax_value']) - $taxRes[0]['sum_BimeMoaf']) / ($EPayMonth - $SPayMonth + 1);
    $sum_normalized_tax = $tax_table_type_id = 0; //متغيري جهت نگهداري ماليات تعديل شده براي cur_staff در تمام طول سال


 
   reset($taxTable);

    for ($m = 1; $m <= $PayMonth; $m++) {

        $begin_month_date = DateModules::shamsi_to_miladi($PayYear . "/" . $m . "/1");
        $end_month_date = DateModules::shamsi_to_miladi($PayYear . "/" . $m . "/" . DateModules::DaysOfMonth($PayYear, $m));

        for ($t = 0; $t < count($taxHisRes); $t++) {

            if (( $taxHisRes[$t]['end_date'] != null && $taxHisRes[$t]['end_date'] != '0000-00-00' ) &&
                    DateModules::CompareDate($taxHisRes[$t]['end_date'], $begin_month_date) == -1) {
                continue;
            }
            if (DateModules::CompareDate($taxHisRes[$t]['start_date'], $end_month_date) == 1) {
                break;
            }

            $tax_table_type_id = $taxHisRes[$t]['tax_table_type_id'];
            break;
        }
        if (!isset($tax_table_type_id) || $tax_table_type_id == NULL) {
            continue;
        }
        if (!key_exists($tax_table_type_id, $taxTable)) {
            
            return;
        }

        foreach ($taxTable[$tax_table_type_id] as $tax_table_row) {

            $pay_mid_month_date = DateModules::shamsi_to_miladi($PayYear . "/" . $m . "/1");
            if (DateModules::CompareDate($pay_mid_month_date, $tax_table_row['from_date']) != -1 &&
                    DateModules::CompareDate($pay_mid_month_date, $tax_table_row['to_date']) != 1) {


                if ($year_avg_tax_include >= $tax_table_row['from_value'] && $year_avg_tax_include <= $tax_table_row['to_value']) {

                    $sum_normalized_tax += ( $year_avg_tax_include - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];


                } else if ($year_avg_tax_include > $tax_table_row['to_value']) {

                    $sum_normalized_tax += ( $tax_table_row['to_value'] - $tax_table_row['from_value'] ) * $tax_table_row['coeficient'];

                }
            }
        }
    }


    //.............................................................

    $normalized_tax = $sum_normalized_tax - $taxRes[0]['sum_tax'];

    if ($normalized_tax < 0)
        $normalized_tax = 0;
    $PaymentItems = array(
        'get_value' => $normalized_tax,
        'param1' => $PayVal,
        'param2' => $sum_normalized_tax,
        'param3' => $taxRes[0]['sum_tax'] + $normalized_tax,
        'param5' => $tax_table_type_id
    );

    return $PaymentItems;
}

$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");

switch ($task) {

    case "InsertData":
        InsertData();
}

function InsertData() {

    if (!empty($_FILES['attach']['name'])) {
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('utf-8');
        $data->setRowColOffset(0);
        $data->read($_FILES["attach"]["tmp_name"]);
    }
    $log_obj = new manage_group_pay_get_log();

    $FileType = $_POST["PayType"];
    $PayYear = $_POST["pay_year"];
    $PayMonth = $_POST["pay_month"];


    $success_count = 0;
    $unsuccess_count = 0;

    //.......پرداخت کارانه.......................................................      
    if ($FileType == 4 || $FileType == 3 ) {

        $pdo = PdoDataAccess::getPdoObject();
        $pdo->beginTransaction();

       

        for ($i = 1; $i < $data->sheets[0]['numRows']; $i++) {
        
             $PaymentObj = new manage_payments();
             $PayItmObj = new manage_payment_items(); 
        
            if (!isset($data->sheets[0]['cells'][$i][0]))
                break;

            $query = " select staff_id , PersonID , bank_id, account_no , last_cost_center_id
                                from HRM_staff where staff_id =" . $data->sheets[0]['cells'][$i][0];

            $resStf = PdoDataAccess::runquery($query);
    
            if (count($resStf) == 0) {
                $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", " شماره شناسایی معتبر نمی باشد.");
                $unsuccess_count++;
                continue;
            }

            //....................................................					
            $PaymentObj->staff_id = $data->sheets[0]['cells'][$i][0];
            $PaymentObj->pay_year = $PayYear;
            $PaymentObj->pay_month = $PayMonth;
            $PaymentObj->payment_type = $FileType;
            $PaymentObj->bank_id = $resStf[0]['bank_id'];
            $PaymentObj->account_no = $resStf[0]['account_no'];
            $PaymentObj->state = 1 ;

            $qry = " select count(*) cn  
                     from HRM_payments 
                     where pay_year = " . $PayYear . " and pay_month = " . $PayMonth . " and staff_id = " . $PaymentObj->staff_id . " and 
                           payment_type = " . $FileType;

            $payRes = PdoDataAccess::runquery($qry);

            if ($payRes[0]['cn'] == 0) {

                if ($PaymentObj->Add() === false) {
                    $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", " خطا ");
                    $unsuccess_count++;

                    continue;
                }
                if( $FileType == 4 )
                    $SID = 26 ; //کد قلم مربوط به کارانه
                else if ( $FileType == 3 )
                    $SID = 45 ; //کد قلم مربوط به پاداش
                //............ مرکز هزینه .....................


                $PayItmObj->pay_year = $PayYear;
                $PayItmObj->pay_month = $PayMonth;
                $PayItmObj->staff_id = $data->sheets[0]['cells'][$i][0];
                $PayItmObj->salary_item_type_id = $SID;
                $PayItmObj->pay_value = $data->sheets[0]['cells'][$i][1];
                $PayItmObj->get_value = 0 ; 
                $PayItmObj->cost_center_id = 1;
                $PayItmObj->payment_type = $FileType;


                if ($PayItmObj->Add() === false) {
                    $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", " خطا ");
                    $unsuccess_count++;
                    continue;
                }
            } else if (count($payRes) > 0) {
                $qry = " select pay_value 
                         from HRM_payment_items 
                         where pay_year = " . $PayYear . " and pay_month = " . $PayMonth . " and 
                               staff_id = " . $PaymentObj->staff_id . " and payment_type = " . $FileType;
                $res = PdoDataAccess::runquery($qry);

                $PayItmObj->pay_year = $PayYear;
                $PayItmObj->pay_month = $PayMonth;
                $PayItmObj->staff_id = $data->sheets[0]['cells'][$i][0];
                $PayItmObj->pay_value = $data->sheets[0]['cells'][$i][1] + $res[0]['pay_value'];
                $PayItmObj->get_value = 0 ; 
                $PayItmObj->payment_type = $FileType;

                if ($PayItmObj->Edit() === false) {
                    $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", " خطای بروز رسانی ");
                    $unsuccess_count++;
                    continue;
                }
            }
          //.............................محاسبه مالیات..................      
            $TaxKey = 8 ;  
            $TaxRes = process_tax_normalize($resStf[0]['staff_id'], $PayItmObj->pay_value , $PayYear, $PayMonth, $FileType );
            
            /* اين فرد مشمول ماليات نمي باشد */

                $SDate = $PayYear . "/" . $PayMonth . "/01";

                if ($PayMonth < 7)
                    $endDay = "31";
                elseif ($PayMonth > 6 && $PayMonth < 12)
                    $endDay = "30 ";
                elseif ($PayMonth == 12)
                    $endDay = "29";

                $EDate = $PayYear . "/" . $PayMonth . "/" . $endDay;
                $EDate = DateModules::shamsi_to_miladi($EDate);
                $SDate = DateModules::shamsi_to_miladi($SDate);
                $staffID = $data->sheets[0]['cells'][$i][0];

                $qry = " select tax_include
                                            from HRM_staff_include_history
                                                    where staff_id = :SID  and start_date <= :EDATE  AND
                                                             (end_date IS NULL OR end_date = '0000-00-00' OR
                                                              end_date >= :EDATE  OR end_date > :SDATE   ) ";
                $res = PdoDataAccess::runquery($qry, array(":SID" => $staffID, ":EDATE" => $EDate,
                            ":SDATE" => $SDate));

                if (count($res) == 0 || !isset($res[0]['tax_include'])) {
                    $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", "سابقه مشمولیت فرد تعریف نشده است.");
                    $unsuccess_count++;
                    continue;
                }

                if ($res[0]['tax_include'] == 0 ) {
                    $TaxRes = 0;
                }
                

                $PayItmObj->pay_year = $PayYear;
                $PayItmObj->pay_month = $PayMonth;
                $PayItmObj->staff_id = $data->sheets[0]['cells'][$i][0];
                $PayItmObj->salary_item_type_id = $TaxKey;
                $PayItmObj->get_value = $data->sheets[0]['cells'][$i][1]  * 0.1 ;//$TaxRes['get_value'] 
                $PayItmObj->pay_value = 0;
                $PayItmObj->cost_center_id = $resStf[0]['last_cost_center_id'];
                $PayItmObj->payment_type = $FileType;
                $PayItmObj->param1 = $TaxRes['param1'];
                $PayItmObj->param2 = $TaxRes['param2'];
                $PayItmObj->param3 = $TaxRes['param3'];
                $PayItmObj->param4 = 2;
                $PayItmObj->param5 = $TaxRes['param5'];

                if ($TaxRes > 0) {
                    if ($PayItmObj->Add() === false) {
                        print_r(ExceptionHandler::PopAllExceptions()); die();
                        $log_obj->make_unsuccess_rows($data->sheets[0]['cells'][$i][0], "-", " خطا 444");
                        $unsuccess_count++;
                        continue;
                    }
                }
                unset($PayItmObj) ; 
             /***************************/
            
        }
        $log_obj->finalize();
        $st = preg_replace('/\r\n/', "", $log_obj->make_result("UploadPayFilesObj.expand();"));

        if ($unsuccess_count > 0) {
            $pdo->rollBack();
        } else {
            $pdo->commit();
        }

        echo "{success:true,data:'" . $st . "'}";
        die();
    }
}

?>