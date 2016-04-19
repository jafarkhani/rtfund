<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------

require_once '../header.inc.php';
require_once 'templates.class.php';
require_once '../global/CNTconfig.class.php';
require_once inc_dataReader;
require_once inc_response;

if(!empty($_REQUEST["task"]))
      $_REQUEST["task"]();

function SelectTemplates() {
    $where = '';
    $whereParams = array();
    if (!empty($_REQUEST['TemplateID'])) {
        $where = " AND TemplateID = :TemplateID";
        $whereParams[':TemplateID'] = $_REQUEST['TemplateID'];
    }
    $temp = CNT_templates::Get($where, $whereParams);
    $res = PdoDataAccess::fetchAll($temp, $_GET['start'], $_GET['limit']);
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function selectTemplateItems() {
    $temp = CNT_TemplateItems::Get();
    if (isset($_REQUEST['All']) && $_REQUEST['All'] == 'true') {
        $res = PdoDataAccess::fetchAll($temp, 0, $temp->rowCount());
    } else
        $res = PdoDataAccess::fetchAll($temp, $_GET['start'], $_GET['limit']);
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function GetEmptyTemplateID() {
	
	$dt = PdoDataAccess::runquery("select TemplateID from CNT_templates where TemplateContent is null");
	if(count($dt) > 0)
		return $dt[0]["TemplateID"];
	
	$obj = new CNT_templates();
	$obj->Add();
	
	return $obj->TemplateID;
}

function SaveTemplate() {
	
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
	
	$CorrectContent = CNT_templates::CorrectTemplateContentItems($_POST['TemplateContent']);
	$obj = new CNT_templates();
	$obj->TemplateContent = $CorrectContent;
	$obj->TemplateTitle = $_POST['TemplateTitle'];
	if ($_POST['TemplateID'] > 0) {
		$obj->TemplateID = $_POST['TemplateID'];
		$result = $obj->Edit($pdo);
	} else {
		$obj->StatusCode = CNTconfig::TemplateStatus_Raw;
		$result = $obj->Add($pdo);
	}
	
	if(!$result)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	$pdo->commit();
	echo Response::createObjectiveResponse(true, $obj->TemplateID);
	die();
}

function saveTemplateItem() {
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
    try {
        $obj = new CNT_TemplateItems();
        PdoDataAccess::FillObjectByJsonData($obj, $_POST['record']);
        if ($obj->TemplateItemID > 0) {
            $obj->Edit();
        } else {
            $obj->Add($pdo);
        }
        $pdo->commit();
        echo Response::createObjectiveResponse(true, '');
        die();
    } catch (Exception $e) {
        $pdo->rollBack();
        //print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, $e->getMessage());
        die();
    }
}

function GetTemplateContent() {
    $obj = new CNT_templates($_POST['TemplateID']);
    echo Response::createObjectiveResponse(true, $obj->TemplateContent);
    die();
}

function GetTemplateTitle() {
    $obj = new CNT_templates($_POST['TemplateID']);
    echo Response::createObjectiveResponse(true, $obj->TemplateTitle);
    die();
}

function GetTemplateContentToEdit() {
    $obj = new CNT_templates($_POST['TemplateID']);
    $content = $obj->TemplateContent;
    $RevContent = '';
    $arr = explode(CNTconfig::TplItemSeperator, $content);
    for ($i = 0; $i < count($arr); $i++) {
        $val = $arr[$i];
        if (is_numeric($val)) {
            $obj = new CNT_TemplateItems($val);
            $RevContent .= CNTconfig::TplItemSeperator . $val . '--' . $obj->ItemName . CNTconfig::TplItemSeperator;

            unset($obj);
        } else {
            $RevContent .= $val;
        }
    }
    echo Response::createObjectiveResponse(true, $RevContent);
    die();
}

function deleteTemplateItem() {
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
    try {
        $obj = new CNT_TemplateItems($_POST['TemplateItemID']);
        $obj->Remove($pdo);
        //
        $pdo->commit();
        echo Response::createObjectiveResponse(true, '');
        die();
    } catch (Exception $e) {
        $pdo->rollBack();
        //print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, $e->getMessage());
        die();
    }
}

function deleteTemplate() {
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
    try {
        $obj = new CNT_templates($_POST['TemplateID']);
        $obj->Remove();
        //
        $pdo->commit();
        echo Response::createObjectiveResponse(true, '');
        die();
    } catch (Exception $e) {
        $pdo->rollBack();
        //print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, $e->getMessage());
        die();
    }
}

//------------------------------------------------------------------------------
?>
