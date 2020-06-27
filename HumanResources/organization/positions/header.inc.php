<?php
//---------------------------
// programmer:	jafarkhani
// Date:		88.07.15
//---------------------------

define("DB_NAME","hrmstotal");

require_once 'sys_config.class.php';
require_once config::$root_path.config::$framework_path.'User.class.php';
require_once config::$root_path.config::$ui_components_path.'HTMLUtil.class.php';
require_once config::$root_path.config::$framework_path.'FrameworkUtil.class.php';
require_once config::$root_path.config::$framework_path.'System.class.php';
require_once config::$root_path.config::$framework_path.'System.class.php';
  
require_once('session.inc.php');
include_once(config::$language.'_utf8.inc.php');

session_start();
if(!isset($_SESSION['User'])){
  FrameworkUtil::SessionExpired(); 
	return;
}
/*if(!$_SESSION['User']->isPageAuthorized()){
  FrameworkUtil::showPageNotAuthorized();
  return;
}*/
  if(!isset($NotAddSlashes))
    {

        foreach ($_POST as $key => $value) 
        {
            if(!is_array($_POST[$key]))
                $_POST[$key] = addslashes(trim($value));
            else
            {
                // must be implemented by omid milani fard
            }
        }
        
        foreach ($_GET as $key => $value) 
        {
            $_GET[$key] = addslashes(trim($value));
        }
    }
   	//--------------------------------------------------
 
   	set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalClasses");
	set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalUI");

	require_once 'PDODataAccess.class.php';
	require_once 'DataAudit.class.php';
	require_once 'jsConfig.inc.php';
	require_once 'ModuleAccess.class.php';
   	require_once('classconfig.inc.php');
	//--------------------------------------------------
	require_once inc_PDODataAccess;
	require_once inc_component;
	require_once inc_CurrencyModule;

	$address_prefix = getenv("DOCUMENT_ROOT");
	define("DOCUMENT_ROOT", $address_prefix . "/HumanResources");
	
	require_once $address_prefix . "/HumanResources/global/domain.class.php";
	require_once $address_prefix . "/HumanResources/global/exception.class.php";
  	require_once $address_prefix. "/HumanResources/global/definitions.inc.php";
	require_once $address_prefix. "/HumanResources/global/basicInfoDefinitions.inc.php";
  	require_once $address_prefix. "/HumanResources/global/variables.class.php";
	require_once $address_prefix. "/HumanResources/global/access/class/access.class.php";
	require_once $address_prefix. "/HumanResources/global/module_definitions.inc.php";
	
  	define("inc_manage_post", $address_prefix . "/HumanResources/organization/positions/post.class.php");
  	define("inc_manage_unit", $address_prefix . "/HumanResources/organization/org_units/unit.class.php");
	define("inc_manage_staff", $address_prefix . "/HumanResources/personal/staff/class/staff.class.php");
	
	define("inc_manage_tree", $address_prefix . "/HumanResources/global/manageTree.class.php");
	define("inc_QueryHelper", $address_prefix . "/HumanResources/global/report/QueryHelper.php");
  	
   	error_reporting(E_ALL);

//-------------------------------------------------------------------------------------------------
define("PersonalSystemCode", 232);
define("SalarySystemCode", 233);

define("PersonalSystem", 1);
define("SalarySystem", 2);

define("HRSystem",( $_SESSION['SystemCode'] == PersonalSystemCode ) ?  PersonalSystem :  SalarySystem) ;

define("HR_ImagePath", "/HumanResources/img/");
define("HR_TemlDirPath", "/HumanResources/tempDir/");

$js_prefix_address = implode("/" , array_splice(preg_split('/\//', $_SERVER["SCRIPT_NAME"]),0,count(preg_split('/\//', $_SERVER["SCRIPT_NAME"]))-1)) . "/";

?>