<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

if (!empty($_POST["SystemID"])) {
	if (!empty($_POST["SysPath"])) {
		require_once getenv("DOCUMENT_ROOT") . "/" . $_POST["SysPath"] . "/start.php";
		die();
	}
}

require_once('../header.inc.php');
require_once 'management/framework.class.php';

$systems = FRW_access::getAccessSystems();
$_SESSION['USER']["RecentSystems"] = array();
?>
<html>
	<head>
		<title>نرم افزار جامع <?= SoftwareName?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<script>
			function OpenSystem(SystemID, SysPath){
				window.location = "../" + SysPath + "/start.php?SystemID=" + SystemID;
			}
		</script>
		<style>
			body{
				margin: 0;
				background-image: url("icons/1.jpg");
			}
			
			.headerDiv{
				height: 150px;
				width : 100%;
				background-color: #7175cd;
				border-bottom: 15px solid #50467f;
			}
			
			.menus{
				width : 700px;	
				margin-bottom: 20px;
			}
			
			.systemRow{
				width : 100%;
				margin-top: 20px;
				height : 200px;
			}
			
			.system {
				background-color: rgba(255, 255, 255, 0.51);
				border: 2px solid #aaa;
				border-radius: 15px;
				box-shadow: 2px 4px 13px #333;
				cursor: pointer;
				float: right;
				font-family: tahoma;
				font-size: 12px;
				height: 180px;
				margin-bottom: 20px;
				margin-left: 20px;
				width: 200px;
			}
			
			.SystemIcon {
				
				background-position: center center;
				background-repeat: no-repeat;
				height: 90px;
				overflow: hidden;
				padding-top: 50px;
				text-align: center;
				text-overflow: ellipsis;
				vertical-align: middle;
				width: 100%;
			}
			
			.SystemDesc {
				background-color: white;
				border-radius: 0 0 15px 15px;
				height: 24px;
				overflow: hidden;
				padding-top: 15px;
				text-align: center;
			}
			
			.systemTitle {
				font-weight: bold;
				color : #67426d;
			}			
						
			.footerDiv{
				background-color: #575ca5;
				font-family: tahoma;
				font-size: 12px;
				height : 80px;
				width : 100%;
				color : white;		
			}
		</style>
	</head>
	<body DIR=RTL style="margin:0">
		<center>
			<div class="menus" align="right">
			<?php

			if(count($systems) == 0)
			{
				echo "<center><br><br><br><font style='font-family:b titr;'>";
				echo "شما به نرم افزار جامع صندوق پژوهش و فناوری خراسان رضوی دسترسی ندارید.";
				echo "</font></center></body>";
				die();
			}
			else
			{
				for ($i = 0; $i < count($systems); $i++) {
					if ($i % 3 == 0)
						echo $i == 0 ? '<div class="systemRow">' : '</div><div class="systemRow">';

					echo "
						<div class=system onclick=\"OpenSystem('" . $systems[$i]["SystemID"] . "','" . $systems[$i]["SysPath"] . "')\" >" .
							"<div class=SystemIcon style=background-image:url('icons/SysIcons/" . $systems[$i]["SysIcon"]  . "')></div>" . 
							"<div class=SystemDesc><div class=SystemTitle>" . $systems[$i]["SysName"] . "</div></div></div>";
				}
				if (count($systems) % 3 != 0)
					echo "</div>";
			}
			?>
			</div>
		</center>
</body>
</html>