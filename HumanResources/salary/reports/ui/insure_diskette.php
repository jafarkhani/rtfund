<?php
//---------------------------
// programmer:	B.Mahdipour
// create Date:	96.06
//---------------------------

require_once("../../../header.inc.php");
require_once  "/home/krrtfir/public_html/HumanResources/global/sisW2D.php";
define("InsureDIRPATH", "/home/krrtfir/public_html/HumanResources/upload/");
//require_once "/home/krrtfir/public_html/generalClasses/pear/DB/dbase.php" ;
//ini_set("display_errors", "On");
// این گزارش مورد بررسی مجدد قرار گیرد چون در صفحه مربوطه تغییراتی اعمال شده است .................
//die();
 
	if(!isset($_REQUEST["task"]))   
		require_once '../js/insure_diskette.js.php';
	
	if( isset($_REQUEST["task"]) && $_REQUEST["task"] != 'GetDisk'  ) {
?>
<style>
		.reportGenerator {border-collapse: collapse;border: 1px solid black;font-family:tahoma;font-size: 8pt;
			text-align: center;width: 50%;padding: 2px;}
		.reportGenerator .header {color: white;font-weight: bold;background-color:#4D7094}
		.reportGenerator .header1 {color: white;font-weight: bold;background-color:#465E86}		
		.reportGenerator td {border: 1px solid #555555;height: 20px;}
</style>		
<?php
	}
/*اولین حکم بعد از این حکم را استخراج می کند*/
	function get_next_writ($execute_date,$staff_id)
	{

	     $WRes = PdoDataAccess::runquery("
										select writ_id, writ_ver 
										from writs
										where staff_id = ? and execute_date > ? and
											  history_only != 1 and emp_mode not in (3,9,15)
										order by execute_date , writ_id DESC , writ_ver DESC
										limit 1 ");
										
		if( count($WRes) > 0 )
			return $WRes[0]['writ_id'] ; 
		else 
		return 0 ; 
	}
	
function onCalcField(&$rec)
{
	
	if(empty($rec['work_sheet']) || $rec['work_sheet'] == 0 ) {
 
			$DT[0]['work_days'] = 1 ; 
			 
	    $work_days = $DT[0]['work_days'];
		$person_type = $DT[0]['person_type'];
	
	/*	$work_days *= 
		DateModules::DaysOfMonth($rec['pay_month'],$rec['pay_year']);	 */
		
		if($rec['pay_month'] < 7 ) 
		$work_days *= 31 ; 
		else if ($rec['pay_month'] > 7 && $rec['pay_month'] < 12 ) 
		$work_days *= 30 ; 
		else $work_days *= 29 ; 

		$rec['work_sheet'] = $work_days;			 
	    
	}
	
		
	if(!empty($rec['work_sheet']))
			$rec['work_sheet'] = round($rec['work_sheet']) ;

		$rec['daily_fee'] = round($rec['monthly_fee'] / $rec['work_sheet']);
		
		

	//	$rec['monthly_premium'] = $rec['monthly_fee'] - $rec['monthly_insure_include']   ;
		$rec['other_gets'] = $rec['gets'] - $rec['worker_insure_include'];
		return true;
}			

function copyDbfFiles() {

        $from_path = "/home/krrtfir/public_html/HumanResources/upload/dbf/" ;
	$to_path = "/home/krrtfir/public_html/HumanResources/upload/" ;
	$this_path = getcwd();
	

	if(!is_dir($to_path)) {
		mkdir($to_path, 0775);
	}

	if (is_dir($from_path)) {
		chdir($from_path);
		$handle=opendir('.');
		while (($file = readdir($handle))!==false) {
				
			if (($file != ".") && ($file != "..")) {
				if (is_file($file)) {
					chdir($this_path);							
					copy($from_path.$file, $to_path.$file);
					chdir($from_path);								
				}
			}
		}
		closedir($handle);
	}
	chdir($this_path);
	
}

if(isset($_REQUEST["task"]))
{
	
	//........................... where .....................................
	$keys = array_keys($_POST);
	$WhereCost = $WherePT = $WhereDetec = "" ;
	$arr = "" ;
	
	$WhereUnit = "" ; 
	if(!empty($_POST['DomainID'])){
		$WhereUnit = " AND bj.UnitID = ".$_POST['DomainID'] ; 
	}
	//............................ Query ...........................................
	
	$query = " DROP TABLE IF EXISTS HRM_temp_insure_include " ;
	PdoDataAccess::runquery($query) ; 
	
	if($_POST['pay_month'] >= 1 && $_POST['pay_month'] < 7 )
		$EndDay = 31 ;
	else if($_POST['pay_month'] > 6 && $_POST['pay_month'] < 12 )
		$EndDay = 30 ;
	else if($_POST['pay_month'] == 12 )
		$EndDay = 29 ;
		
	$month_start = DateModules::shamsi_to_miladi($_POST['pay_year']."/".$_POST['pay_month']."/01") ;
	$month_end = DateModules::shamsi_to_miladi($_POST['pay_year']."/".$_POST['pay_month']."/".$EndDay) ;
	$next_month_start = DateModules::shamsi_to_miladi($_POST['pay_year']."/".($_POST['pay_month']+1)."/01") ;
	
	$query = " CREATE TABLE HRM_temp_insure_include  AS
			    SELECT DISTINCT pit.staff_id
				FROM HRM_payment_items pit
							INNER JOIN HRM_payments p ON (  pit.pay_year = p.pay_year AND 
							                                pit.pay_month = p.pay_month AND pit.staff_id = p.staff_id AND
															pit.payment_type = p.payment_type)
							INNER JOIN HRM_writs w ON ( p.writ_id = w.writ_id AND
														p.writ_ver = w.writ_ver AND p.staff_id = w.staff_id )			 
                                                        INNER JOIN HRM_staff s ON p.staff_id = s.staff_id 
                                                        INNER JOIN HRM_persons pr ON s.PersonID = pr.PersonID				
							LEFT JOIN BSC_jobs bj ON bj.JobID = w.job_id
							
				WHERE pit.pay_year = ".$_POST['pay_year']." AND
					  pit.pay_month = ".$_POST['pay_month']." AND 
					  pr.detectiveID =  ".$_POST['DetectID']." AND 
					  pit.salary_item_type_id IN(7) AND 
					  pit.get_value <> 0 ".$WhereUnit ; 	
	PdoDataAccess::runquery($query) ; 	
	
	PdoDataAccess::runquery("ALTER TABLE HRM_temp_insure_include ADD INDEX(staff_id)") ; 		

	PdoDataAccess::runquery("DROP TABLE IF EXISTS HRM_temp_work_sheet") ; 		
	

	PdoDataAccess::runquery("DROP TABLE IF EXISTS HRM_temp_insure_list") ; 	
		
	PdoDataAccess::runquery(" CREATE TABLE HRM_temp_insure_list AS 
						 select

   s.staff_id ,
   s.work_start_date,
   s.account_no,
   ps.pfname,
   ps.plname, ps.idcard_no, ps.sex,
  CASE WHEN ps.sex = 1 THEN 1 ELSE 0 END man_counter ,
  CASE WHEN ps.sex = 2 THEN 1 ELSE 0 END woman_counter ,
  ps.father_name, ps.insure_no,
  ps.country_id,
  ps.birth_date,
  ps.national_code,
  ps.InsurePost job_title, ps.JobCode JobCode ,
  w.contract_start_date, w.contract_end_date,
  ps.issue_date, w.ouid, w.salary_pay_proc,
  w.person_type, c.ptitle country_title , pa.pay_year, pa.pay_month,
  pa.start_date, pa.end_date,
  /*'4000976046'*/ 	hd.detectiveCode  daily_work_place_no,
  '-' cost_center_id ,
  /*'صندوق پژوهش و فن آوری استان خراسان رضوی'*/ hd.detectiveName detective_name,
  'میدان آزادی دانشگاه فردوسی' detective_address,
  '-' collective_security_branch,
  'صندوق دانشگاه' employer_name,
  SUM(CASE WHEN (pai.salary_item_type_id = 1 )
   THEN (pai.param7) ELSE '-'  END) work_sheet,
  '-' description ,
  /* SUM(pai.pay_value + pai.diff_pay_value * pai.diff_value_coef) monthly_fee,*/
   SUM(if(pai.salary_item_type_id in (1),(pai.pay_value + pai.diff_pay_value * pai.diff_value_coef), 0 ) ) monthly_fee,
   SUM(if(pai.salary_item_type_id not in (1),(pai.pay_value + pai.diff_pay_value * pai.diff_value_coef), 0 ) ) monthly_premium_fee,
   SUM(if(pai.salary_item_type_id not in (1,17,30),(pai.pay_value + pai.diff_pay_value * pai.diff_value_coef), 0 ) ) monthly_premium,
   SUM(pai.pay_value + pai.diff_pay_value * pai.diff_value_coef) pay,
   SUM(CASE WHEN (pai.salary_item_type_id = 7 )
   THEN (pai.param1 + pai.diff_param1 * pai.diff_param1_coef) END) monthly_insure_include,

   ROUND(SUM(CASE WHEN (pai.salary_item_type_id = 7 )
   THEN (pai.get_value + pai.diff_get_value * pai.diff_value_coef) END)) worker_insure_include,

   ROUND(SUM(CASE WHEN (pai.salary_item_type_id = 7 )
   THEN (pai.get_value) END)) worker_insure_include_val,

   ROUND(SUM(CASE WHEN (pai.salary_item_type_id = 7 )
             THEN ( pai.diff_get_value * pai.diff_value_coef) END)) worker_insure_include_diffval,

   ROUND(SUM(CASE WHEN (pai.salary_item_type_id = 7 )
   THEN (pai.param2 + pai.diff_param2 * diff_param2_coef) END)) employer_insure_value,

    ROUND(SUM(CASE WHEN (pai.salary_item_type_id = 7 ) THEN (pai.param3 + pai.diff_param3 * diff_param3_coef) END)) unemployment_insure_value,

    SUM(pai.get_value + pai.diff_get_value * pai.diff_value_coef) gets,

    SUM( (pai.pay_value + pai.diff_pay_value * pai.diff_value_coef) - (pai.get_value - pai.diff_get_value * pai.diff_value_coef)) pure_pay


     from HRM_temp_insure_include ti

     INNER JOIN HRM_payments pa ON(ti.staff_id = pa.staff_id)
     LEFT OUTER JOIN HRM_payment_items pai ON (pai.pay_year = pa.pay_year AND pai.pay_month = pa.pay_month AND
                                      pai.payment_type = pa.payment_type AND pai.staff_id = pa.staff_id)

     LEFT OUTER JOIN HRM_writs w ON (pa.writ_id = w.writ_id AND
                                     pa.writ_ver=w.writ_ver AND
                                     pa.staff_id = w.staff_id )

     LEFT OUTER JOIN HRM_staff s ON (w.staff_id = s.staff_id)

     LEFT OUTER JOIN HRM_persons ps ON (ps.PersonID = s.PersonID)
     LEFT JOIN HRM_Detectives hd ON hd.DetID = ps.detectiveID 
     LEFT OUTER JOIN HRM_countries c ON (ps.country_id = c.country_id)
     LEFT OUTER JOIN HRM_salary_item_types sit ON(pai.salary_item_type_id = sit.salary_item_type_id)
	 
	 LEFT JOIN BSC_jobs bj ON bj.PersonID = ps.RefPersonID AND w.job_id = bj.JobID
	 LEFT join BSC_posts po ON po.PostID= bj.PostID

      where (pa.pay_year = ".$_POST['pay_year']." ) AND (pa.pay_month = ".$_POST['pay_month']." ) AND pa.payment_type= ".$_POST['PayType']." 


      group by s.staff_id,
               pa.pay_year, pa.pay_month, s.work_start_date, ps.pfname, ps.plname,
               ps.idcard_no, ps.sex, ps.father_name, ps.insure_no, ps.country_id,
               w.contract_start_date,w.contract_end_date, w.ouid, w.salary_pay_proc,
               pa.start_date, pa.end_date  ");
									 
	
								  
	PdoDataAccess::runquery('ALTER TABLE HRM_temp_insure_list ADD INDEX(staff_id);');
	
	$query = "	select 
					staff_id,
					work_start_date,
					account_no,
					pfname,
					plname, birth_date , 
					'مشهد' city_title, 
					idcard_no,  sex,man_counter ,
					woman_counter ,father_name,insure_no,
					country_id,	national_code ,
					job_title,
					country_title,
					contract_start_date,contract_end_date,
					ouid,
					salary_pay_proc,
					person_type,
					pay_year,
					pay_month,
					start_date,
					end_date,JobCode,
					daily_work_place_no,cost_center_id,
					detective_name,
					detective_address,
					collective_security_branch,
				    employer_name,
					work_sheet ,issue_date,
					description ,
					round(monthly_fee) monthly_fee ,
                    round(monthly_premium) monthly_premium ,
                    round(monthly_premium_fee) monthly_premium_fee ,
					round(pay) pay ,
					round(monthly_insure_include) monthly_insure_include ,
					round(worker_insure_include) worker_insure_include ,
					round(employer_insure_value) employer_insure_value ,
					round(unemployment_insure_value) unemployment_insure_value ,
					gets,
					pure_pay 
				
				from HRM_temp_insure_list s
				where  (pay_year = ".$_POST['pay_year'].") AND
					   (pay_month = ".$_POST['pay_month'].")  
					  
				order by pay_year,pay_month,daily_work_place_no,plname,pfname   
                                 " ; 
									
		$res = PdoDataAccess::runquery($query) ; 	

		$qry = " select bi.Title month_title 
                        from  Basic_Info bi 
                                where  bi.typeid = 78 AND InfoID = ".$_POST["pay_month"] ; 
		$MRes = PdoDataAccess::runquery($qry) ; 
		$monthTitle = $MRes[0]['month_title'] ;
	
		
	if( $_REQUEST["task"] == 'GetDisk' ) {		
	
	
		$counter =$work_sheet = $daily_fee = $monthly_fee = 0 ; 
		$monthly_premium = $monthly_insure_include = $pay = $worker_insure_include =$other_gets =$monthly_premium_fee = 0 ; 
		$pure_pay = $employer_insure_value = $unemployment_insure_value = 0  ; 
		$record = "" ;	
                copyDbfFiles();
                
                $cnv = new sisW2DFormatConvertor();
                
		for($i=0;$i<count($res);$i++){
			
		onCalcField($res[$i]) ;
                
                while (list($key, $value) = each($res[$i]))
                        $res[$i][$key] = str_replace(array("ی", "ی", "ک", "ك", "ؤ"), array("ي", "ي", "ك", "ك", "و"), $res[$i][$key]);

                
		if($res[$i]['sex'] == 1 )
				$sex = 'مرد';
			else
				$sex = 'زن';
		
			if($res[$i]['work_start_date'] != NULL && $res[$i]['work_start_date'] !='0000-00-00' )
			{
				$arr  = preg_split('/\//',DateModules::Miladi_to_Shamsi($res[$i]['work_start_date'])); 
				
				$year = $arr[0];
				$month = $arr[1]; 
				$day = $arr[2]; 
						
				if ($year * $month == $_POST['pay_year'] * $_POST['pay_month'] )
					$contract_start_date = DateModules::Miladi_to_Shamsi($res[$i]['work_start_date']);
				else
					$contract_start_date = NULL;
					
			}
			else $contract_start_date = NULL; // '1397/01/01';
		 	
			unset($arr);
			if($res[$i]['contract_end_date'] != '0000-00-00')
			{
			/*	$arr = preg_split('/\//',DateModules::Miladi_to_Shamsi($res[$i]['contract_end_date']));  	
				$year = $arr[0];
				$month = $arr[1]; 
				$day = $arr[2];						
				
				if ($year * $month == $_POST['pay_year'] * $_POST['pay_month']) {
					$next_writ = get_next_writ($res[$i]['contract_end_date'],$res[$i]['staff_id']);
					
					if ($next_writ == 0 && $res[$i]['salary_pay_proc'] == 1) {
						$contract_end_date = DateModules::Miladi_to_Shamsi($res[$i]['contract_end_date']);
					}				
				}*/
				$contract_end_date = DateModules::Miladi_to_Shamsi($res[$i]['contract_end_date']);
			
			} else
			$contract_end_date = NULL; //'1397/12/29';

		$record = array($res[$i]['daily_work_place_no'],
                                substr($_POST['pay_year'], 2, 2),
                                $_POST["pay_month"]*1 ,
                                1,
                                $cnv->correctDigitDir($res[$i]['insure_no']),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['pfname'])),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['plname'])),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['father_name'])),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['idcard_no'])),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['city_title'])),
                                substr(DateModules::Miladi_to_Shamsi($res[$i]['issue_date']),0,4).''.substr(DateModules::Miladi_to_Shamsi($res[$i]['issue_date']),5,2).''.substr(DateModules::Miladi_to_Shamsi($res[$i]['issue_date']),8,2),
                                substr(DateModules::Miladi_to_Shamsi($res[$i]['birth_date']),0,4).''.substr(DateModules::Miladi_to_Shamsi($res[$i]['birth_date']),5,2).''.substr(DateModules::Miladi_to_Shamsi($res[$i]['birth_date']),8,2),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $sex)),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['country_title'])),
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['job_title'])) ,                                 
                                substr($contract_start_date,0,4).''.
                                substr($contract_start_date,5,2).''.
                                substr($contract_start_date,8,2) ,
                                substr($contract_end_date,0,4).''.
                                substr($contract_end_date,5,2).''.
                                substr($contract_end_date,8,2),
                                round($res[$i]['work_sheet']),
                                round($res[$i]['daily_fee']),
                                round($res[$i]['monthly_fee']),
                                round($res[$i]['monthly_premium']),
                                round($res[$i]['monthly_insure_include']),
                                round($res[$i]['pay']),
                                round($res[$i]['worker_insure_include']),
                                27,
                                $cnv->convertStringMs2Dos(iconv('UTF-8', 'WINDOWS-1256', $res[$i]['JobCode'])),/* DSW_JOB */
                                substr($res[$i]['national_code'],0,10));	 
						
	
			$counter++;
			$work_sheet += $res[$i]['work_sheet'];
			$daily_fee += $res[$i]['daily_fee']; 
			$monthly_fee += $res[$i]['monthly_fee']; 
			$monthly_premium += $res[$i]['monthly_premium'];  
            $monthly_premium_fee += $res[$i]['monthly_premium_fee'];   
			$monthly_insure_include += $res[$i]['monthly_insure_include']; 
			$pay += $res[$i]['pay'];
			$worker_insure_include += $res[$i]['worker_insure_include'];
			$employer_insure_value += $res[$i]['employer_insure_value']; 
			$unemployment_insure_value += $res[$i]['unemployment_insure_value']; 
                        
                        $file = "DSKWOR00.DBF";
                       /* if (file_exists(InsureDIRPATH.$file)) {
                                     unlink(InsureDIRPATH.$file);
                                }*/
                        
			$db_path = InsureDIRPATH . $file ;
			$dbi = dbase_open($db_path, 2);
			dbase_add_record($dbi, $record);
			dbase_close($dbi);
			
			}
			
			if(empty($_GET['TypeDisk'])) {
			
				$file = "DSKWOR00.DBF";
				
				header('Content-disposition: filename="'.$file.'"');
				header('Content-type: application/file');
				header('Pragma: no-cache');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public'); 

				echo file_get_contents(InsureDIRPATH.$file);
				die() ; 
				
			}	
			else if($_GET['TypeDisk'] == 'KAR'){
			
	
	
			$t1 = 'ليست اصلي مشمول بيمه' ; 
                        
			$record2 = array($res[0]['daily_work_place_no'],
    				   $cnv->convertStringMs2Dos(iconv('UTF-8','WINDOWS-1256',$res[0]['detective_name'])),
    				   $cnv->convertStringMs2Dos(iconv('UTF-8','WINDOWS-1256',$res[0]['employer_name'])),
    				   $cnv->convertDigitDosEnToFa($cnv->convertStringMs2Dos(iconv('UTF-8','WINDOWS-1256',$res[0]['detective_address']))),                                 
                                   1,
    				   substr($_POST['pay_year'], 2, 2),
    				   $_POST["pay_month"] * 1 ,
                                   NULL,
                                   $cnv->convertStringMs2Dos(iconv('UTF-8','WINDOWS-1256','ليست اصلي مشمول بيمه')),     					
                                   $counter,
                                    round($work_sheet),
                                    round($daily_fee),
                                    round($monthly_fee),
                                    round($monthly_premium),
                                    round($monthly_insure_include),
                                    round($pay),
                                    round($worker_insure_include),                    
                                    round($employer_insure_value),
                                    round($unemployment_insure_value),
                                   /* (round($worker_insure_include) + round($employer_insure_value)),*/
                                    '27',
                                     0,
                                     0 ) ; 
                    print_r($record2).'----'; die();    
                                $file = "DSKKAR00.DBF" ;
                             /*   if (file_exists(InsureDIRPATH.$file)) {
                                     unlink(InsureDIRPATH.$file);
                                }  */                              
                               
                                $db_path = InsureDIRPATH.$file ;
                                $dbi = dbase_open($db_path,2);
                                dbase_add_record($dbi, $record2);
                                dbase_close($dbi); 


				header('Content-disposition: filename="'.$file.'"');
				header('Content-type: application/file');
				header('Pragma: no-cache');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');

				echo file_get_contents(InsureDIRPATH.$file);
				die() ; 
    					
			}
		
	
	}		
	elseif( $_REQUEST["task"] == 'ShowList' ) 
	{

	
	
	echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">';
		echo "<center><table style='border:0px groove #9BB1CD;border-collapse:collapse;width:90%'><tr>
				<td width=60px>&nbsp;<br><br></td>
				<td align='center' style='font-family:b titr;font-size:15px'>گزارش لیست بیمه"." </td>				
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
		     . DateModules::shNow() . "<br>";		
	echo "</td></tr></table>";      
	
	echo '<table  class="reportGenerator" style="text-align: right;width:90%!important" cellpadding="4" cellspacing="0">
			 <tr class="header1">					
				<td colspan="24">صورت دستمزد/حقوق ومزایای&nbsp;&nbsp;&nbsp;'. $monthTitle.'&nbsp;'.$_POST['pay_year'].'</td>
			 </tr>
			 <tr class="header1">
				<td colspan="4" >شماره کارگاه : &nbsp; '.$res[0]['daily_work_place_no'].'</td>
				<td colspan="4">نام گارگاه : &nbsp; '.$res[0]['detective_name'].'</td>
				<td colspan="5">نام کارفرما : &nbsp; '.$res[0]['employer_name'].'</td>
				<td colspan="6">نشانی کارگاه :&nbsp; '.$res[0]['detective_address'].'</td>
				<td colspan="5">شعبه تامین اجتماعی :&nbsp; '.$res[0]['collective_security_branch'].'</td>			
			 </tr>
			 <tr class="header">					
				<td rowspan="2">ردیف </td>
				<td colspan="9" align="center" >مشخصات بیمه شده</td>
				<td colspan="3" align="center" >روزهای کارکردماه</td>
				<td colspan="6" align="center" >دستمزد/حقوق مزایا(ریال)</td>
				<td rowspan="2" >حق بیمه <br>سهم بیمه شده</td>
				<td rowspan="2" >سایر کسور</td>		
				<td rowspan="2" >مانده قابل پرداخت</td>
				<td rowspan="2" >امضا/اثرانگشت</td>
				<td rowspan="2" >کدملی</td>
			</tr>
			<tr class="header">
				<td>نام</td>
				<td>نام خانوادگی</td>
				<td>شماره شناسنامه/گذرنامه</td>
				<td>نام پدر</td>
				<td>شماره بیمه شده</td>
				<td>شغل</td>
				<td>مرد</td>
				<td>زن</td>
				<td>غیرایرانی</td>
				<td>تاریخ آغاز به کار</td>
				<td>تاریخ ترک کار</td>
				<td>روزهای کارکرد</td>
				<td>دستمزد روزانه</td>
				<td>دستمزد ماهانه</td>
                <td>مزایا ماهانه</td>
				<td>مزایای ماهانه مشمول</td>
				<td>جمع دستمزد و مزاياي ماهانه مشمول</td>
				<td>جمع دستمزد و مزایای ماهانه مشمول و غیر مشمول</td>							
			</tr>' ; 
		$sumWorkSheet = $sumDailyFee = $sumMonthlyFee = $sumMonthlyPremium = $UEInsurevalue = $sumMonthlyPremiumFee =  0 ; 
		$sumMInsureInc = $sumPay = $sumWInsureInc = $sumOtherPay = $sumPurePay = $EInsurevalue = 0 ;	
		for($i=0;$i< count($res) ;$i++)
		{
			onCalcField($res[$i]) ;						
			
			echo " <tr>
					<td>".( $i + 1 )."</td>
					<td>".$res[$i]['pfname']."</td> 
					<td>".$res[$i]['plname']."</td>
					<td>".$res[$i]['idcard_no']."</td>
					<td>".$res[$i]['father_name']."</td>
					<td>".$res[$i]['insure_no']."</td> " ;
			mb_internal_encoding('utf-8');
			if(mb_strlen($res[$i]['job_title'])<=14)				
				echo "<td>".$res[$i]['job_title']."</td>" ; 
			else 
				echo "<td>".mb_substr($res[$i]['job_title'],0,12).'...'."</td>" ;  
			
			if($res[$i]['sex'] == 1 )
				echo "<td> * </td>" ; 
			else 
				echo "<td>&nbsp;</td>" ; 
			
			if($res[$i]['sex'] == 2 )
				echo "<td> * </td>" ; 
			else 
				echo "<td>&nbsp;</td>" ; 
				
			if($res[$i]['country_id'] != 1 )
				echo "<td> * </td>" ; 
			else 
				echo "<td>&nbsp;</td>" ; 
			
			if(!empty($res[$i]['work_start_date']) && $res[$i]['work_start_date'] !='0000-00-00'  ){
				list($year,$month,$day) = preg_split('/[\/]/',DateModules::miladi_to_shamsi($res[$i]['work_start_date']));	

				if ($year * $month == $res[$i]['pay_year'] * $res[$i]['pay_month'])
					echo "<td> ".DateModules::miladi_to_shamsi($res[$i]['work_start_date'])." </td>" ; 
				else echo "<td>&nbsp;</td>" ; 
			}	
			else echo "<td>&nbsp;</td>" ; 
                   
			if(!empty($res[$i]['contract_end_date']) && $res[$i]['contract_end_date'] !='0000-00-00' ){
			    
				list($year,$month,$day) = preg_split('/[\/]/',DateModules::miladi_to_shamsi($res[$i]['contract_end_date']));		
				if ($year * $month == $res[$i]['pay_year'] * $res[$i]['pay_month']){
			/*		
					$qry = " select  count(*) cn
								from writs
									where execute_date > '".$res[$i]['contract_end_date']."' and staff_id = ".$res[$i]['staff_id']."
							 order by execute_date limit 1
							" ; 
					$NWRes = PdoDataAccess::runquery($qry) ; 
					
						if($NWRes[0]['cn'] == 0 && $res[$i]['salary_pay_proc'] == 1 )		*/							
							echo "<td> ".DateModules::miladi_to_shamsi($res[$i]['contract_end_date'])." </td>" ; 
				}
				else echo "<td>&nbsp;</td>" ; 
			}
			else echo "<td>&nbsp;</td>" ; 
			
			echo "<td>".$res[$i]['work_sheet']."</td>
				  <td>".number_format(round($res[$i]['daily_fee']), 0, '.', ',')."</td> 
				  <td>".number_format(round($res[$i]['monthly_fee']), 0, '.', ',')."</td>
                  <td>".number_format(round($res[$i]['monthly_premium_fee']), 0, '.', ',')."</td>   
				  <td>".number_format(round($res[$i]['monthly_premium']), 0, '.', ',')."</td>
				  <td>".number_format(round($res[$i]['monthly_insure_include']), 0, '.', ',')."</td>				 
                  <td>".number_format(round($res[$i]['pay']), 0, '.', ',')."</td>
				  <td>".number_format(round($res[$i]['worker_insure_include']), 0, '.', ',')."</td>
				  <td>".number_format(round($res[$i]['other_gets']), 0, '.', ',')."</td>
				  <td>".number_format(round($res[$i]['pure_pay']), 0, '.', ',')."</td>
				  <td>".$res[$i]['account_no']."</td>
				  <td>".$res[$i]['national_code']."</td></tr>" ;
						
			$sumWorkSheet +=  $res[$i]['work_sheet'];
			$sumDailyFee += $res[$i]['daily_fee'] ; 
			$sumMonthlyFee += $res[$i]['monthly_fee'] ;
            $sumMonthlyPremiumFee += $res[$i]['monthly_premium_fee'] ;//monthly_premium_fee
			$sumMonthlyPremium += $res[$i]['monthly_premium'] ;
			$sumMInsureInc +=  $res[$i]['monthly_insure_include'] ;
			$sumPay += $res[$i]['pay'] ;
			$sumWInsureInc +=  $res[$i]['worker_insure_include'] ; 
			$sumOtherPay += $res[$i]['other_gets'] ; 
			$sumPurePay += $res[$i]['pure_pay'] ; 
			$EInsurevalue += $res[$i]['employer_insure_value'] ; 
			$UEInsurevalue += $res[$i]['unemployment_insure_value']	 ;  
		}
	

		echo "<tr style='font-weight: bold' ><td colspan='12'>جمع :&nbsp;</td>
				  <td>".number_format(round($sumWorkSheet), 0, '.', ',')."</td>
				  <td>".number_format(round($sumDailyFee), 0, '.', ',')."</td>
				  <td>".number_format(round($sumMonthlyFee), 0, '.', ',')."</td>
                  <td>".number_format(round($sumMonthlyPremiumFee), 0, '.', ',')."</td>
				  <td>".number_format(round($sumMonthlyPremium), 0, '.', ',')."</td>
				  <td>".number_format(round($sumMInsureInc), 0, '.', ',')."</td>
				  <td>".number_format(round($sumPay), 0, '.', ',')."</td>
				  <td>".number_format(round($sumWInsureInc), 0, '.', ',')."</td>
				  <td>".number_format(round($sumOtherPay), 0, '.', ',')."</td>
				  <td>".number_format(round($sumPurePay), 0, '.', ',')."</td>
				  <td colspan=2></td></tr>" ; 
				  
		echo "</table><table  width='90%' style='font-weight: bold;font-size: 11pt;' cellpadding= '3' cellspacing='0'><tr>
				  <td colspan='13' width='51%' >&nbsp;</td>
				  <td colspan='4' style='border:1px solid black;border-collapse:collapse;' width='17%' >جمع حق بیمه سهم کارفرما </td>
				  <td colspan='2' style='border:1px solid black;border-collapse:collapse;' width='11%' >".number_format(round($EInsurevalue), 0, '.', ',')."</td>
				  <td colspan='4' width='21%' >&nbsp;</td></tr>" ;
		echo "<tr><td colspan='13'>&nbsp;</td><td colspan='4' style='border:1px solid black;border-collapse:collapse;' >حق 3% بیمه بیکاری </td>
				  <td colspan='2' style='border:1px solid black;border-collapse:collapse;' >".number_format(round($UEInsurevalue), 0, '.', ',')."</td><td colspan='4'>&nbsp;</td></tr>" ;

		echo "<tr><td colspan='13'>&nbsp;</td><td colspan='4' style='border:1px solid black;border-collapse:collapse;' >جمع کل</td>
				  <td colspan='2' style='border:1px solid black;border-collapse:collapse;' >".number_format(round($sumWInsureInc + $EInsurevalue + $UEInsurevalue), 0, '.', ',')."</td>
				  <td colspan='4'>&nbsp;</td></tr></table></center>" ;
	
	}

		
}
?>
	
<form id="mainForm">
    <center>
        <div id="mainpanel"></div>
		<input type="hidden" name="Type" id="Type">
    </center>    
</form>