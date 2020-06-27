<?php

//---------------------------
// programmer:	Mahdipour
// create Date:	94.11
//---------------------------

require_once '../../../header.inc.php'; 
require_once '../class/person.class.php';  
require_once '../../staff/class/staff.class.php'; 
//require_once '../../../baseInfo/class/bases.class.php';
//require_once '../../staff/class/staff_detasils.php';
require_once '../../staff/class/staff_include_history.class.php';
require_once '../../../salary/payment/class/payments.class.php';

require_once(inc_response); 
require_once inc_dataReader;
require_once inc_PDODataAccess; 

$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");


switch ($task) {
	case "save" :
		saveData();

	case "delete" :
		deleteData();

	case "select" :
		selectData();

	case "gridSelect" :
		gridSelect();

	case "searchPerson":
		searchPerson();

        case "searchRetPerson":
		searchRetPerson();

	case "changePT":
		changePT();
	//------------------------------------
	case "selectCourse":
		selectCourse();

	case "selectEval":
		selectEval();
	//------------------------------------

	case "saveIncludeHistory":
		saveIncludeHistory();

	case "removeIncHistory":
		removeIncHistory();
	//--------------------------------
	case "selectSalaryReceipt":
		selectSalaryReceipt();

	//--------------------------------
	case "selectStudents":
		selectStudents();
		
	case "importStudent":
		importStudent();
}

function gridSelect() {
	$where = "1=1";
	$whereParam = array();
	//.................................
	if (!empty($_REQUEST["from_PersonID"])) {
		$where .= " AND p.PersonID>=:fpid";
		$whereParam[":fpid"] = $_REQUEST["from_PersonID"];
	}

	if (!empty($_REQUEST["to_PersonID"])) {
		$where .= " AND p.PersonID<=:tpid";
		$whereParam[":tpid"] = $_REQUEST["to_PersonID"];
	}
	if (!empty($_REQUEST["from_SID"])) {
		$where .= " AND s.staff_id >=:fsid";
		$whereParam[":fsid"] = $_REQUEST["from_SID"];
	}

	if (!empty($_REQUEST["to_SID"])) {
		$where .= " AND s.staff_id <=:tsid";
		$whereParam[":tsid"] = $_REQUEST["to_SID"];
	}
	if (!empty($_REQUEST["pfname"])) {
		$where .= " AND p.pfname like :pfname";
		$whereParam[":pfname"] = "%" . $_REQUEST["pfname"] . "%";
	}
	if (!empty($_REQUEST["plname"])) {
		$where .= " AND p.plname like :plname";
		$whereParam[":plname"] = "%" . $_REQUEST["plname"] . "%";
	}
	if (!empty($_REQUEST["ouid"])) {
		$where .= " AND ( u.ouid = :ouid OR u.parent_path LIKE '%,:ouid,%' OR  u.parent_path LIKE '%:ouid,%' OR u.parent_path LIKE '%,:ouid%' ) ";
		$whereParam[":ouid"] = $_REQUEST["ouid"];
	}

	if (isset($_REQUEST["emp_mod"]) && $_REQUEST["emp_mod"] != -1) {
		$where .= " AND w.emp_mode=:mod";
		$whereParam[":mod"] = $_REQUEST["emp_mod"];
	}

	if (!empty($_REQUEST["national_code"])) {
		$where .= " AND national_code = :national_code";
		$whereParam[":national_code"] = $_REQUEST["national_code"];
	}
	if (!empty($_REQUEST["staff_group"]) && $_REQUEST["staff_group"] != -1) {
		$where .= " AND staff_group = :staff_group";
		$whereParam[":staff_group"] = $_REQUEST["staff_group"];
	}
	//.................................	
	$field = isset($_REQUEST ["fields"]) ? $_REQUEST ["fields"] : "";
	if (isset($_REQUEST ["query"]) && $_REQUEST ["query"] != "") {
		switch ($field) {
			case "fname" :
				$where .= " AND fname LIKE :qry ";
				$whereParam[":qry"] = "%" . $_REQUEST["query"] . "%";

				break;
			case "lname" :
				$where .= " AND lname LIKE :qry ";
				$whereParam[":qry"] = "%" . $_REQUEST["query"] . "%";

				break;
			case "birth_date" :
				$where .= " AND birth_date = :qry1 ";
				$whereParam[":qry1"] = $_REQUEST["query"];

				break;
			case "idcard_no" :
				$where .= " AND idcard_no = :qry1 ";
				$whereParam[":qry1"] = $_REQUEST["query"];

				break;
			case "father_name" :
				$where .= " AND Fname LIKE :qry ";
				$whereParam[":qry"] = "%" . $_REQUEST["query"] . "%";

				break;
			case "dependency" :
				$where .= " AND dependency LIKE :qry ";
				$whereParam[":qry"] = "%" . $_REQUEST["query"] . "%";

				break;

			case "insure_type" :
				$where .= " AND insure_type LIKE :qry ";
				$whereParam[":qry"] = "%" . $_REQUEST["query"] . "%";

				break;
		}
	}

	$where .= dataReader::makeOrder();

	$temp = manage_person::GetAllPersons($where, $whereParam, true);
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET ["start"], $_GET ["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}
function searchRetPerson() {
			
	$where = " 1=1";
	$whereParam = array();
	if (isset($_REQUEST["query"]) && $_REQUEST["query"] != "") {
		$where .= " AND (PFName LIKE :qry or PLName LIKE :qry or concat(PFName,' ',PLName) like :qry
				                          or concat(PLName,' ',PFName) like :qry 
				                          OR p.PersonID = :Pqry				                         
										  OR s.staff_id = :Pqry
				)";
		$whereParam = array(":qry" => "%" . $_REQUEST["query"] . "%",
							":Pqry" => $_REQUEST["query"]);
	}

	$include_new_persons = isset($_REQUEST["newPersons"]) ? true : false;
	$show_All_history = isset($_REQUEST["show_All_history"]) ? true : false;
	
	$query = " select s.staff_id , p.pfname , p.plname , s.ledger_number  
				from staff s inner join persons p 
								on s.PersonID = p.PersonID  AND s.person_type = p.person_type
					where p.person_type = 10 AND ".$where ;
	
	$temp = PdoDataAccess::runquery($query, $whereParam);
		
	$no = count($temp);
	$temp = array_slice($temp, $_GET["start"], $_GET["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function searchPerson() {
   
	$where = " 1=1";
	$whereParam = array();
	if (isset($_REQUEST["query"]) && $_REQUEST["query"] != "") {
		$where .= " AND (fname LIKE :qry or lname LIKE :qry or concat(fname,' ',lname) like :qry
				                          or concat(lname,' ',fname) like :qry 
				                          OR p.PersonID = :qry2
				                          OR o.ptitle like :qry
										  OR s.staff_id = :qry2
				)";
		$whereParam = array(":qry" => "%" . $_REQUEST["query"] . "%",
			            ":qry2" => $_REQUEST["query"]);
	}

	$include_new_persons = isset($_REQUEST["newPersons"]) ? true : false;
	$show_All_history = isset($_REQUEST["show_All_history"]) ? true : false;

	if (isset($_REQUEST['ouid'])) {

		$whereParam[":ouid"] = $_REQUEST['ouid'];
		$whereParam[":ouid2"] = "%," . $_REQUEST['ouid'] . ",%";
		$whereParam[":ouid3"] = "%" . $_REQUEST['ouid'] . ",%";
		$whereParam[":ouid4"] = "%," . $_REQUEST['ouid'] . "%";

		$where .= " AND (  o.parent_path like :ouid2 OR
                             o.parent_path like :ouid3 OR
                             o.parent_path like :ouid4 OR
                             o.ouid=:ouid OR
                             o.parent_ouid = :ouid
                               ) ";
	}

	$temp = manage_person::SelectPerson($where, $whereParam, $include_new_persons, $show_All_history);

	$no = count($temp);
	$temp = array_slice($temp, $_GET["start"], $_GET["limit"]);
 
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function saveData() {
	
		$obj = new manage_person();
		PdoDataAccess::FillObjectByArray($obj, $_POST);
 
		$obj->birth_date = DateModules::Shamsi_to_Miladi($_POST["birth_date"]);
		$obj->issue_date = DateModules::Shamsi_to_Miladi($_POST["issue_date"]);
$obj->employ_date = DateModules::Shamsi_to_Miladi($_POST["employ_date"]);
		$obj->military_from_date = DateModules::Shamsi_to_Miladi($_POST["military_from_date"]);
		$obj->military_to_date = DateModules::Shamsi_to_Miladi($_POST["military_to_date"]);
		$obj->person_type = 3 ; 

//print_r($obj); die() ; 
                
                /*$obj = new manage_person($_POST["PersonID"]);
		$obj->insure_no = $_POST["insure_no"];
		$obj->PersonID = $_POST["PersonID"];*/
	
	//---------- control duplicate national_code ----------------------
	if ($obj->national_code != "" && $obj->PersonID == "" ) {
		
		$temp = PdoDataAccess::runquery("select * from HRM_persons 
                                                 where national_code= ? ", array($obj->national_code));        
                
               
		if (count($temp) != 0) {
			Response::createObjectiveResponse(false, " این فرد قبلا در سیستم ثبت شده است. ");
			die();
		}
	}	
		
	//..........................................................................	
	if (trim($_FILES['ProfPhoto']['name']) == '' ) 
		{   
			//$message=' نام فایل خالی است ';
		  $PhotoSwitch = FALSE;
	
		}
		elseif ( $_FILES['ProfPhoto']['error'] != 0 ) 
			$message=' خطا در ارسال فایل' . $_FILES['ProfPhoto']['error'];
		elseif 	($_FILES['ProfPhoto']['size'] > $_POST['MAX_FILE_SIZE'] )
			$message=' طول فایل بیش از 50 کیلو بایت است ';
		
       elseif(in_array(strtolower(end(explode(".", $_FILES['ProfPhoto']['name']))),array("jpg","jpeg")) !=1) {
            $message= "فرمت عکس قابل قبول نمی باشد.";
       } 
	   				
		else
		{ 		

			
			$_size = $_FILES['ProfPhoto']['size'];
			$_name = $_FILES['ProfPhoto']['tmp_name'];
			$data = addslashes((fread(fopen($_name, 'r' ),$_size)));
			$PhotoQuery = "";
		
	
			/*
			//اگر استاد قبلا عکس داشته است
			$Photosql = pdodb::getInstance("","","","photo","");
		
			if (count($img_res) > 0){
			  $PhotoQuery = "UPDATE photo.StaffPhotos SET picture='$data' WHERE PersonID=".$img_res[0]['PersonID'];
			  $auditmessage = 'بروز رسانی عکس';
			}
			else{
			  $PhotoQuery = "INSERT INTO photo.StaffPhotos (PersonID, picture) VALUES ($HrmsPersonID, '$data')";
			  $auditmessage = 'اضافه کردن عکس';
			}
			$Photo_res = $Photosql->ExecuteBinary($PhotoQuery);
	                $Photosql->audit($auditmessage);
			//Added by Bagheri (2013-Oct-23) -- pass is here temporary.
			$oaPhotoQuery = "UPDATE officeas.uni_pic SET picture='$data' WHERE uid='".$UserID."'";
	        $oamysql = pdodb::getInstance("172.20.20.36", "picuser", "sp#U12_oA", "officeas");
			$oamysql->ExecuteBinary($oaPhotoQuery);
			$auditmessage = 'ﺏﺭﻭﺯ ﺮﺳﺎﻧی ﻉکﺱ اتوماسیون';
			$Photosql->audit($auditmessage);
			//End of Bagheri.
			$PhotoSwitch =true;
			 */
			
		}
	
	//..........................................................................		
	if (empty($_POST["PersonID"])) {	
	
		$staffObject = new manage_staff();
		PdoDataAccess::FillObjectByArray($staffObject, $_POST);

		$obj->PersonID = null;
		
		if($obj->national_code == "") {
		
		   $obj->national_code =  0 ; 
			
		}
			

		$return = $obj->AddPerson($staffObject);	
		
		if($return === TRUE && $PhotoSwitch !== FALSE ) 
		{		
		
			$qry = " update HRM_persons set picture = '$data' where PersonID = ".$obj->PersonID ; 
			if(PdoDataAccess::runquery($qry) === false)		
			$return	= false ;			  
		}
					
	} else {
						
		$qry = " select s.* 
			                from HRM_persons p inner join HRM_staff s 
												on p.personid = s.personid and p.person_type = s.person_type
												
			              where p.personid = ".$obj->PersonID ; 
		$ptres = PdoDataAccess::runquery($qry) ; 
		
//	echo PdoDataAccess::GetLatestQueryString() ;

		$return = $obj->EditPerson();
	
		$staffObject = new manage_staff($obj->PersonID, $obj->person_type);
					 
		PdoDataAccess::FillObjectByArray($staffObject, $_POST);
		$return = $staffObject->EditStaff(); 

              if($return === TRUE && $PhotoSwitch !== FALSE  ) 
		{		
		
			$qry = " update HRM_persons set picture = '$data' where PersonID = ".$obj->PersonID ; 
			if(PdoDataAccess::runquery($qry) === false)		
			$return	= false ;			  
		}	

		
		
	} 	
		
	echo $return ? Response::createObjectiveResponse(true, "{PID:" . $obj->PersonID . ",SID:" . $staffObject->staff_id . "}" ) :
			Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString("\n"));
	die();
}

function FillPersonItems($src) {
	$obj = new manage_person();

	$obj->birth_date = DateModules::Shamsi_to_Miladi($src["birth_date"]);
	$obj->issue_date = DateModules::Shamsi_to_Miladi($src["issue_date"]);
$obj->employ_date = DateModules::Shamsi_to_Miladi($src["employ_date"]);
	$obj->military_from_date = DateModules::Shamsi_to_Miladi($src["military_from_date"]);
	$obj->military_to_date = DateModules::Shamsi_to_Miladi($src["military_to_date"]);

	$arr = get_object_vars($obj);
	$KeyArr = array_keys($arr);

	for ($i = 0; $i < count($arr); $i++) {
		eval("\$obj->" . $KeyArr[$i] . " = (isset(\$src) && isset(\$src['" . $KeyArr[$i] . "'])) 
			? \$src ['" . $KeyArr[$i] . "'] : '';");
	}

	return $obj;
}

//------------------------------------------------------------------------------

function selectCourse() {
	$where = " pc.PersonID = :PID ";
	$whereParam = array();
	$whereParam[":PID"] = $_GET["Q0"];

	$field = isset($_GET ["fields"]) ? $_GET ["fields"] : "";
	if (isset($_GET ["query"]) && $_GET ["query"] != "") {
		switch ($field) {
			case "title" :
				$where .= " AND pc.title LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;
			case "from_date" :
				$where .= " AND from_date = :qry1 ";
				$whereParam[":qry1"] = "%" . $_GET["query"] . "%";

				break;
			case "to_date" :
				$where .= " AND to_date = :qry1 ";
				$whereParam[":qry1"] = "%" . $_GET["query"] . "%";

				break;
			case "related_Title" :
				$where .= " AND related_Title = :qry1 ";
				$whereParam[":qry1"] = "%" . $_GET["query"] . "%";

				break;
			case "internal_Title" :
				$where .= " AND internal_Title = :qry1 ";
				$whereParam[":qry1"] = "%" . $_GET["query"] . "%";

				break;
			case "total_hours" :
				$where .= " AND total_hours = :qry1 ";
				$whereParam[":qry1"] = "%" . $_GET["query"] . "%";

				break;
		}
	}

	$query = " SELECT count(*) 
		       FROM  person_courses  pc
			   where 1=1";
	$query .= ($where != "") ? " AND " . $where : "";

	$temp = PdoDataAccess::runquery($query, $whereParam);

	$no = $temp[0][0];
	
	$where .= dataReader::makeOrder();
	$where .= isset($_GET ["start"]) ? " limit " . $_GET ["start"] . "," . $_GET ["limit"] : "";

	$query = " SELECT pc.PersonID,
                      pc.group_id , 
                      pc.p_id , 
                      pc.title title,
                      pc.course_id,
                      pc.from_date,
                      pc.to_date,
					  pc.register_no , 
					  pc.score , 
					  pc.certficate_date ,
                      if(pc.total_hours is null , 0 ,pc.total_hours ) total_hours ,
                      bi1.Title related_Title,
                      bi2.Title internal_Title  
                       
		       FROM  person_courses  pc
					left join Basic_Info bi1 on pc.related =bi1.InfoID and bi1.TypeID=5
					left join Basic_Info bi2 on pc.internal =bi2.InfoID and bi2.TypeID=5
					
			   where 1=1";

	$query .= ($where != "") ? " AND " . $where : "";

	$temp = PdoDataAccess::runquery($query, $whereParam);
	
	$dt = PdoDataAccess::runquery("
									select sum(total_hours) totalHrs
									from person_courses
									where PersonID=? 
									", array($_GET["Q0"]));
	$thSum = count($dt) != 0 ? $dt[0]["totalHrs"] : 0;
	
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"] ,$thSum);
	die();
}

function selectEval() {

	$whereParam = array();
	$whereParam[":PID"] = $_GET["Q0"];

	$query = "
select * from (
 select
                            e.list_id,
                            e.list_date ,
                            ei.staff_id,
                            ou.ptitle,
                            
                            p.plname,
                            p.pfname,
                           
                            ei.annual_coef,
                            ei.high_job_coef,
                            ei.scores_sum,
                            ei.comments,
                            ei.social_behaviour_score

                            from evaluation_list_items ei
                                    LEFT OUTER JOIN evaluation_lists e
                                            ON (e.list_id = ei.list_id)
                                    LEFT OUTER JOIN org_new_units ou
                                            ON e.ouid = ou.ouid
                                    LEFT OUTER JOIN  staff s
                                        ON(s.staff_id = ei.staff_id)
                                    LEFT OUTER JOIN  persons p
                                        ON(p.PersonID = s.PersonID)

                            where p.PersonID = :PID 
                            
UNION ALL
                           SELECT    '' list_id ,
          p.ToDate ,
					st.staff_id,
          o.ptitle ptitle ,
          '' plname,
          '' pfname,

					   0 annual_coef,
             0 high_job_coef,
          round(if(s.ProtestScore != 0.000,
						 s.ProtestScore,
						 s.TotalScore)) as  scores_sum,
             0 comments,
             0 social_behaviour_score


				FROM
					ease.SEVL_Reports s
						left join
					ease.SEVL_ItemScore e ON (e.PersonID = s.PersonID)
						left join
					ease.SEVL_EvlPeriods p ON (e.EvlPeriodID = p.EvlPeriodID)
            inner join staff st on s.PersonID = st.PersonID
            inner join org_new_units o on o.ouid = st.UnitCode
				where

						 s.PersonID = :PID

				group by  s.PersonID , e.EvlPeriodID ) t
            order by list_date DESC";


	$temp = PdoDataAccess::runquery($query, $whereParam);
 // echo PdoDataAccess::GetLatestQueryString() ;
	if($_GET["Q0"] == 200092 || $_GET["Q0"] == 700120 || $_GET["Q0"] == 200141 || $_GET["Q0"] == 200331 || 
	   $_GET["Q0"] == 200919 || $_GET["Q0"] == 401365906 || $_GET["Q0"] == 201038 || $_GET["Q0"] == 200132 || $_GET["Q0"] == 306) 
		{
			
		}
	$no = count($temp);
	$temp = array_slice($temp, $_GET ["start"], $_GET ["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function deleteData() {
	$personID = $_REQUEST["PersonID"];

	$return = manage_person::RemovePerson($personID);

	if ($return)
		echo "true";
	else
		print_r(PdoDataAccess::PopAllExceptions());

	die();
}

function changePT() {
	$PersonID = $_POST["PersonID"];
	$new_person_type = $_POST["new_person_type"];
	$NID = $_POST["NationalCode"];

	$obj = new manage_person();
	$obj->PersonID = $PersonID;
	$obj->person_type = $new_person_type;
	$obj->national_code = $NID ; 
	
	$staffObject = new manage_staff($PersonID, $_POST["old_person_type"]);
	unset($staffObject->staff_id);
	$staffObject->person_type = $new_person_type;
	
	$staffObject->AddStaff();

	$return = $obj->EditPerson(null, null);

	echo $return ? Response::createObjectiveResponse(true, $PersonID) :
			Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString("\n"));
	die();
}

function saveIncludeHistory() {
	$obj = new manage_staff_include_history();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);

	if ($obj->include_history_id == "")
		$return = $obj->Add($_POST['Q0']);
	else
		$return = $obj->Edit($_POST['Q0']);

	if ($return)
		echo Response::createObjectiveResponse(true, $obj->include_history_id);
	else
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
	die();
}

function removeIncHistory() {
	$obj = new manage_staff_include_history();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);

	$return = $obj->Remove();

	if ($return)
		echo Response::createResponse(true, $obj->include_history_id);
	else
		echo Response::createResponse(false, ExceptionHandler::GetExceptionsToString());
	die();
}

function selectSalaryReceipt() {
    
	$no = count(manage_payments::GetSalaryReceipt($_GET['Q0']));

	$temp = manage_payments::GetSalaryReceipt($_GET['Q0']);

	$temp = array_slice($temp, $_GET ["start"], $_GET ["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

//------------------------------------------------------------------------------

function selectStudents() {
	$query = "
		SELECT StNo,concat(PFName,' ',PLName) fullname FROM students
		where stNO like :s OR concat(PFName,' ',PLName) like :s";
	
	$st = PdoDataAccess::runquery_fetchMode($query, array(":s" => '%' . $_GET["query"] . '%'));
	$no = $st->rowCount();	
	$temp = PdoDataAccess::fetchAll($st, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function importStudent(){
	
	$dt = PdoDataAccess::runquery("select * from StudentPersonMap where StNo=?", array($_POST["StNo"]));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "Duplicate");
		die();
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$query = "insert into 
		persons(person_type,pfname,plname,efname,elname,father_name,idcard_no,birth_date,national_code,
				sex,address1,home_phone1,mobile_phone,email,comment)
		select " . $_POST["person_type"] . ",PFName,PLName,EFName,ELName,DadName,BCN,BirthDate,NID,
				sex,address1,PhoneNo,mobile,EMail,concat('انتقال اطلاعات دانشجو با شماره دانشجویی', StNo)
		from students where StNo=?";
	PdoDataAccess::runquery($query, array($_POST["StNo"]), $pdo);

	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "1");
		//print_r(ExceptionHandler::PopAllExceptions());
		die();
	}
	//--------------------------------------------------------------------------
	
	$personID = PdoDataAccess::InsertID();
	$staff_id = PdoDataAccess::GetLastID("staff", "staff_id", "", array(), $pdo) + 1;
	
	PdoDataAccess::runquery("insert into staff(staff_id,PersonID,person_type) 
			values($staff_id, $personID, " . $_POST["person_type"] . ")", array(), $pdo);
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "2");
		//print_r(ExceptionHandler::PopAllExceptions());
		die();
	}
	//--------------------------------------------------------------------------
	$query = "insert into StudentPersonMap	values($personID," . $_POST["StNo"] . ")";
	PdoDataAccess::runquery($query, array(), $pdo);
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "5");
		die();
	}
	//--------------------------------------------------------------------------
	$query = "select RFID from educ.StudentSpecs where StNo=?";
	$dt = PdoDataAccess::runquery($query, array($_POST["StNo"]), $pdo);
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "3");
		//print_r(ExceptionHandler::PopAllExceptions());
		die();
	}
	if(count($dt) > 0 && $dt[0][0] != "")
	{
		$id = PdoDataAccess::GetLastID("pas.PersonSettings", "PersonSettingsID");
		$query = "insert into pas.PersonSettings(PersonSettingsID,PersonID,CardStatus,CardNumber,WorkGroupID,CalendarID)
			values(" . ($id+1) . ",$personID,'ENABLE',NULL,0,0)";
		PdoDataAccess::runquery($query, array(), $pdo);
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollBack();
			print_r(ExceptionHandler::PopAllExceptions());
			echo Response::createObjectiveResponse(false, "4");
			die();
		}
	}
	//--------------------------------------------------------------------------
	$pdo->commit();
	$result = (ExceptionHandler::GetExceptionCount() == 0) ? "true" : "false";
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>