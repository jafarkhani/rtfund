<?php

//---------------------------
// programmer:	Mahdipour
// create Date:	94.12
//---------------------------
require_once '../../header.inc.php';
require_once '../class/salary_item_types.class.php';
require_once(inc_response);
require_once inc_dataReader;

$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");

switch ($task) {
	case "SelectSIT" :
		selectsalaryItem();

	case "saveSIT" :
		savesalaryItem();

	case "deleteSIT":
		deleteSIT();

	case "searchSubtractItem":
		searchSubtractItem();
		
	case "GetCostCode":
		 GetCostCode() ; 
		
	case "searchSessionItem" :
		searchSessionItem() ; 
}


function searchSessionItem() {

	$where = " (1=1) AND SessionItem = 1  ";
	$whereParam = array();
		
	$no = manage_salary_item_type::Count($where, $whereParam);

	$where .= dataReader::makeOrder();
	$where .= isset($_GET ["start"]) ? " limit " . $_GET ["start"] . "," . $_GET ["limit"] : "";

	$temp = manage_salary_item_type::Select($where, $whereParam);

	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function selectsalaryItem() {

	$where = " (1=1) ";
	$whereParam = array();
	
	
	if(!empty($_REQUEST['full_title'])){		
		$where .= " AND full_title LIKE :tit " ;  
		$whereParam[":tit"] = "%" . $_REQUEST['full_title'] . "%";
		}
		
	if(!empty($_REQUEST['print_title'])){
		$where .= " AND print_title LIKE :ptit " ;  
		$whereParam[":ptit"] = "%" . $_REQUEST['print_title'] . "%";
		}
	

	$field = isset($_GET ["fields"]) ? $_GET ["fields"] : "";

	if (isset($_GET ["query"]) && $_GET ["query"] != "") {
		switch ($field) {
			case "PTitle" :
				$where .= " AND bi1.Title LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;
			case "compute_place_title" :
				$where .= " AND bi3.Title LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

			case "full_title" :
				$where .= " AND full_title LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

			case "effectTitle" :
				$where .= " AND bi2.Title  LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;

			case "insure_include_title" :
				$where .= " AND insure_include = ";
				$where .= ($_GET["query"] == '*') ? "1" : "''";
				break;

			case "tax_include_title" :
				$where .= " AND tax_include = ";
				$where .= ($_GET["query"] == '*') ? "1" : "''";

				break;
			case "retired_include_title" :
				$where .= " AND retired_include = ";
				$where .= ($_GET["query"] == '*') ? "1" : "''";

				break;
			case "pension_include_title" :
				$where .= " AND pension_include = ";
				$where .= ($_GET["query"] == '*') ? "1" : "''";

				break;
			case "user_data_entry_title" :
				$where .= " AND user_data_entry = ";
				$where .= ($_GET["query"] == '*') ? "1" : "''";

				break;
			case "salary_compute_type_title" :
				$where .= " AND bi4.Title LIKE :qry ";
				$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;
			    
			case "salary_item_type_id" :
				$where .= " AND salary_item_type_id = :qry ";
				$whereParam[":qry"] = $_GET["query"] ;

				break;
		}
	}

		
	
	$no = manage_salary_item_type::Count($where, $whereParam);

	$where .= dataReader::makeOrder();
	$where .= isset($_GET ["start"]) ? " limit " . $_GET ["start"] . "," . $_GET ["limit"] : "";

	$temp = manage_salary_item_type::Select($where, $whereParam);

	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function savesalaryItem() {

	if (!empty($_POST["salary_item_type_id"])) {
		$obj = new manage_salary_item_type($_POST["salary_item_type_id"]);
	} else {
		$obj = new manage_salary_item_type();
	}

		PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	    $obj->user_data_entry = (isset($_POST['user_data_entry'])) ? $_POST['user_data_entry'] : 0;
	    $obj->editable_value = (isset($_POST['editable_value'])) ? $_POST['editable_value'] : 0;
	    $obj->param1_input = (isset($_POST['param1_input'])) ? $_POST['param1_input'] : 0;
	    $obj->param2_input = (isset($_POST['param2_input'])) ? $_POST['param2_input'] : 0;
	    $obj->param3_input = (isset($_POST['param3_input'])) ? $_POST['param3_input'] : 0;
	    $obj->param4_input = (isset($_POST['param4_input'])) ? $_POST['param4_input'] : 0;
	    $obj->param5_input = (isset($_POST['param5_input'])) ? $_POST['param5_input'] : 0;
	    $obj->param6_input = (isset($_POST['param6_input'])) ? $_POST['param6_input'] : 0;
	    $obj->param7_input = (isset($_POST['param7_input'])) ? $_POST['param7_input'] : 0;      
	    $obj->person_type = 3 ;
	    $obj->insure_include = (isset($_POST['insure_include'])) ? $_POST['insure_include'] : 0;
	    $obj->tax_include = (isset($_POST['tax_include'])) ? $_POST['tax_include'] : 0;
	    $obj->retired_include = (isset($_POST['retired_include'])) ? $_POST['retired_include'] : 0;
	    $obj->pension_include = (isset($_POST['pension_include'])) ? $_POST['pension_include'] : 0; 
		$obj->month_length_effect = (isset($_POST['month_length_effect'])) ? $_POST['month_length_effect'] : 0;
		
		$obj->SessionItem = (isset($_POST['SessionItem'])) ? $_POST['SessionItem'] : 0;	
    
		if(empty($_POST['validity_start_date']))
			unset($obj->validity_start_date);
 
	if (empty($_POST["salary_item_type_id"])) {
		$obj->salary_item_type_id = null;
		if (!$obj->AddSalaryItem()) {
			echo "InsertError";
			die();
		}
	} else {
		if (!$obj->EditSalaryItem()) {
			echo "UpdateError";
			die();
		}
	}

	echo Response::createObjectiveResponse("true", $obj->salary_item_type_id);
	die();
}

function deleteSIT() {
	$result = manage_salary_item_type::Remove($_POST["salary_item_type_id"]);

	Response::createObjectiveResponse($result, "");
}

function searchSubtractItem() {
	$where = "";
	$whereParam = array();

	if (isset($_REQUEST["query"]) && $_REQUEST["query"] != "") {
		$where .= " AND full_title LIKE :qry ";
		$whereParam = array(":qry" => "%" . $_REQUEST["query"] . "%");
	}

	$temp = manage_salary_item_type::selectSubItem($where, $whereParam);
	$no = count($temp);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}


?>