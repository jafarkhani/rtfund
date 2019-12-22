<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

class sys_config{
	 public static $db_server = array (
	        "driver"   => "mysql",
	        "host"     => "localhost",
			"database" => "krrtfir_rtfundold",
			//"database" => "framewor_rtfund",
	        "user"     => "root",
	        "pass"     => "bahar"
	 );
}

class smtp_config{
	public static $server = "dns1.mashhadhost.com";
	public static $username = "admin@krrtf.ir";
	public static $password = "Heag7j35Y2";
	public static $FromAddress = "admin@krrtf.ir";
}

class sms_config{
	public static $server = "http://79.175.176.61/Service.asmx?wsdl";
	public static $username = "abdolahi";
	public static $password = "09153750964";
	public static $LineNumber = "30002222000000";
}

define("BANK_AYANDEH_PIN", "qn75G3KAr0R03J5lCm6X");

define("BANK_TEJARAT_MERCHANTID", "D01E");
define("BANK_TEJARAT_SHALKEY", "22338240992352910814917221751200141041845518824222260 ");
define("BANK_TEJARAT_ADMINEMAIL", "krfn.ir@gmail.com");

define("SoftwareName", "صندوق پژوهش و فناوری غیر دولتی استان خراسان رضوی");
define("OWNER_NATIONALID", "10380491265");
define("OWNER_REGCODE", "33943");
define("OWNER_REGDATE", "1387/06/24");
define("OWNER_ADDRESS", "شعبه دانشگاه فردوسی مشهد : پردیس، درب غربی( ورودی شهید باهنر ) <br>" . 
	"email : krfn.ir@gmail.com <br> تلفن : 38837392 - فکس : 38837392 ".
	"<br> کد پستی : 9177948974" );
define("OWNER_WELCOME_MESSAGE", "ثبت نام شما در صندوق پژوهش و فناوری خراسان رضوی تایید گردید<br>" . 
		"می توانید از طریق لینک زیر وارد پرتال خود شوید <br>" . 
		"<a href=http://portal.krrtf.ir target=blank>http://portal.krrtf.ir </a>");

define("DOCUMENT_ROOT", "D:/projects/rtfund");
//define("DOCUMENT_ROOT", "/home/framewor/public_html");
?>
