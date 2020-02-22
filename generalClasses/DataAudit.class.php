<?php
//---------------------------
// Programmer:	Jafarkhani
// Create Date:	1389.05
//---------------------------

class DataAudit
{
	private $DataAuditID;
  	private $PersonID; 			// کد شخصی عمل کننده 
  	private $SystemID; 			// کد سیستم جاری
  	private $PageName; 			// نام صفحه ای این دستکاری توسط آن انجام شده
  	private $IPAddress; 		// آدرس آی پی کامپیوتر عمل کننده
  	private $ActionTime; 		// زمان انجام عمل
  		
  	public  $TableName;
  	public  $MainObjectID;
  	public  $SubObjectID;

	const Action_add = "ADD";
	const Action_delete = "DELETE";
	const Action_update = "UPDATE";
	const Action_replace = "REPLACE";
	const Action_view = "VIEW";
	const Action_search = "SEARCH";
	const Action_send = "SEND";
	const Action_return = "RETURN";
	const Action_confirm = "CONFIRM";
	const Action_reject = "REJECT";
	const Action_other = "OTHER";
	/**
	* 	نوع عمل
  	*/
  	public  $ActionType;
  	
	/**
  	 * توضیحات بیشتر
  	 */
  	public  $description;
	
	/**
  	 * کوئری اجرا شده
  	 */
	private $QueryString;
  	
  	public function execute($pdo = null)
  	{
  		//------------------- fill data members --------------------
  		$this->DataAuditID = PDONULL;
  		$this->PersonID = isset($_SESSION["USER"]["PersonID"]) ? $_SESSION["USER"]["PersonID"] : "";
  		//$this->SystemID = SYSTEMID;
		
  		if(isset($_SERVER['HTTP_REFERER']))
			$this->PageName = $_SERVER['HTTP_REFERER'];
		else if(isset($_SERVER['SCRIPT_FILENAME']))
			$this->PageName = $_SERVER['SCRIPT_FILENAME'];
		else 
			$this->PageName = "";
		
  		$this->IPAddress = $_SESSION['LIPAddress'];
  		
  		$this->ActionTime = PDONOW;

		$this->QueryString = PdoDataAccess::GetLatestQueryString();
  		//----------------------------------------------------------
  		PdoDataAccess::insert("DataAudit", $this, $pdo);
  	}
  	
}

?>