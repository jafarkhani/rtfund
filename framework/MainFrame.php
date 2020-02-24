<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1390-02
//-----------------------------
require_once('../header.inc.php');
require_once 'management/framework.class.php';

$systems = FRW_access::getAccessSystems();

$menuStr = "";
foreach($systems as $sysRow)
{
	$menuStr .= "{
				xtype : 'panel',
				layout: 'fit',
				title: '" . $sysRow["SysName"] . "',
				items :[{
					xtype : 'menu',
					floating: false,
					bodyStyle : 'background-color:white !important;',
					items :[";

	
	if($sysRow["SystemID"] == "2")
	{
		$dt = PdoDataAccess::runquery("
			select * from ACC_UserState 
				join BSC_branches using(BranchID)
				join ACC_cycles using(CycleID)
			where PersonID=?", array($_SESSION["USER"]["PersonID"]));

		if(count($dt) > 0)
		{
			$_SESSION["accounting"]["BranchID"] = $dt[0]["BranchID"];
			$_SESSION["accounting"]["CycleID"] = $dt[0]["CycleID"];
			$_SESSION["accounting"]["CycleYear"] = $dt[0]["CycleYear"];
			$_SESSION["accounting"]["BranchName"] = $dt[0]["BranchName"];
			$_SESSION["accounting"]["DefaultBankTafsiliID"] = $dt[0]["DefaultBankTafsiliID"];
			$_SESSION["accounting"]["DefaultAccountTafsiliID"] = $dt[0]["DefaultAccountTafsiliID"];
		}
	}
	
	$menus = FRW_access::getAccessMenus($sysRow["SystemID"]);
	$groupArr = array();
	for ($i = 0; $i < count($menus); $i++) 
	{
		if (!isset($groupArr[ $menus[$i]["GroupID"] ] )) {
			if ($i > 0) {
				$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);
				$menuStr .= "]}},";
			}
			$icon = $menus[$i]['GroupIcon'];
			$icon = (!$icon) ? "/generalUI/ext4/resources/themes/icons/star.gif" : 
				"/generalUI/ext4/resources/themes/icons/$icon";
			
			$menuStr .= "{
				text: '" . $menus[$i]["GroupDesc"] . "',
				icon : '".$icon."',
				menu :{
					bodyStyle : 'background-color:white !important;',
					items :[";

			$groupArr[ $menus[$i]["GroupID"] ] = true;
		}

		$icon = $menus[$i]['icon'];
		$icon = (!$icon) ? "/generalUI/ext4/resources/themes/icons/star.gif" : 
			"/generalUI/ext4/resources/themes/icons/$icon";

		$link_path = "/" . $menus[$i]['SysPath'] . "/" . $menus[$i]['MenuPath'];
		$param = "{";
		$param .= "MenuID : " . $menus[$i]['MenuID'] . ",";

		//--------- extract params --------------
		if (strpos($link_path, "?") !== false) {
			$arr = preg_split('/\?/', $link_path);
			$link_path = $arr[0];
			$arr = preg_split('/\&/', $arr[1]);
			for ($k = 0; $k < count($arr); $k++)
				$param .= str_replace("=", ":'", $arr[$k]) . "',";
		}
		$param = substr($param, 0, strlen($param) - 1);
		//---------------------------------------
		$param .= "}";

		$menuStr .= "{
			text: '" . $menus[$i]["MenuDesc"] . "',
			handler: function(){
				framework.OpenPage('" . $link_path . "','" . $menus[$i]["MenuDesc"] . "'," . $param . ");
			},
			icon: '" . $icon . "'
		},";
	}

	if ($menuStr != "") {
		$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);
		$menuStr .= "]}}";
	}
	
	$menuStr .= "]}]},";
}

$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);

?>
<html>
	<head>
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>	
		<link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/Loading.css" />
		<link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-all.css" />

		<style type="text/css">
			html, body {
				font:normal 11px tahoma;
				margin:0;
				padding:0;
				border:0 none;
				overflow:hidden;
				height:100%;
			}
		</style>
	</head>
	<body dir="rtl">
		<div id="loading-mask"></div>
		<div id="loading">
			<div class="loading-indicator">در حال بارگذاری سیستم . . .
				<img src="/generalUI/ext4/resources/themes/icons/loading-balls.gif" style="margin-right:8px;" align="absmiddle"/></div>
		</div>

		<link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/icons.css" />
		<script type="text/javascript" src="/generalUI/ext4/resources/ext-all.js"></script>

		<link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-rtl.css" />
		<script type="text/javascript" src="/generalUI/ext4/resources/ext-extend.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/component.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/message.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/grid/SearchField.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/TreeSearch.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/CurrencyField.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/grid/ExtraBar.js"></script>
		<script type="text/javascript" src="/generalUI/ext4/ux/grid/gridprinter/Printer.js"></script>
<?php
require_once 'MainFrame.js.php';
?>

		<script>
			var required = '<span style="color:red;font-weight:bold" data-qtip="فیلد اجباری">*</span>';
			Ext.QuickTips.init();
			var framework;
			setTimeout(function(){
				Ext.get('loading').remove();
				Ext.get('loading-mask').fadeOut({
					remove:true
				});
				framework = new FrameWorkClass();
				if(FrameWorkClass.StartPage != "" && FrameWorkClass.StartPage != undefined)
					framework.OpenPage(FrameWorkClass.StartPage, "صفحه اصلی");
				FrameWorkClass.SystemLoad();
			}, 700);
		</script>

		<div id="LoginExpire" style="display : none;">
			<div style="color: red; height: 40px; width: 100%; z-index: 99999; position: fixed; 
				 background-color: white; text-align: center; font-weight: bold;cursor:pointer"
				 onclick="window.location='/framework/login.php'">
				<br>زمان انتظار شما به پایان رسیده است لطفا مجدد وارد شوید</div>
			<div style="position: fixed;top: 40;left: 0;height:100%;width:100%;z-index: 9999999;
			background-color : #999;opacity: 0.7;filter: alpha(opacity=70);-moz-opacity: 0.7; /* mozilla */"></div>
		</div>
		
	</body>
</html>
