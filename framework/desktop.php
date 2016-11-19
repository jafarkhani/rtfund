<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1395.03
//-----------------------------
include('header.inc.php');
require_once 'management/framework.class.php';

$systems = FRW_access::getAccessSystems();

$menuStr = "";

foreach($systems as $sysRow)
{
	$menuStr .= "{text: '" . $sysRow["SysName"] . "',arrowCls:'none',icon : 'icons/app.png'";

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
	if(count($menus) > 0)
		$menuStr .= ",menu : {xtype : 'menu',bodyStyle: 'background:white !important;',items:[";
	//........................................................
	$groupArr = array();
	foreach($menus as $row)
	{
		if (!isset($groupArr[ $row["GroupID"] ] )) 
		{
			if(count($groupArr) > 0)
			{
				$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);
				$menuStr .= "]}},";
			}
			$icon = $row['GroupIcon'];
			$icon = (!$icon) ? "/generalUI/ext4/resources/themes/icons/star.gif" : 
				"/generalUI/ext4/resources/themes/icons/$icon";
			$menuStr .= "{text : '" . $row["GroupDesc"] . "', icon: '" . $icon . "', menu :{bodyStyle: 'background:white !important;',items:[";
			$groupArr[$row["GroupID"] ] = true;
		}
		
		$icon = $row['icon'];
		$icon = (!$icon) ? "/generalUI/ext4/resources/themes/icons/star.gif" : 
			"/generalUI/ext4/resources/themes/icons/$icon";
		$link_path = "/" .$row['SysPath'] . "/" . $row['MenuPath'];
		//--------- extract params --------------
		$param = "{";
		$param .= "MenuID : " . $row['MenuID'] . ",";
		if (strpos($link_path, "?") !== false) {
			$arr = preg_split('/\?/', $link_path);
			$link_path = $arr[0];
			$arr = preg_split('/\&/', $arr[1]);
			for ($k = 0; $k < count($arr); $k++)
				$param .= str_replace("=", ":'", $arr[$k]) . "',";
		}
		$param = substr($param, 0, strlen($param) - 1);
		$param .= "}";
		//---------------------------------------		

		$menuStr .= "{
			text: '" . $row["MenuDesc"] . "',
			handler: function(){
				framework.OpenPage('" . $link_path . "','" . $row["MenuDesc"] . "'," . $param . ");
			},
			icon: '" . $icon . "'
		},";
	}
	$menuStr .= "]}}";
	//........................................................
	if(count($menus) > 0)
		$menuStr .= "]}";
	
	$menuStr .= "},'-',";
}
if ($menuStr != "") {
	$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);
}

?>
<html>
	<head>
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>	
		<title><?= SoftwareName ?></title>
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
		<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
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
		<script type="text/javascript" src="/generalUI/ext4/ux/Printer/Printer-all.js"></script>
		<script src="/generalUI/ckeditor/ckeditor.js"></script>
		<script src="/generalUI/ext4/ux/ImageViewer.js"></script>
		<link rel="stylesheet" type="text/css" href="/office/icons/icons.css" />
	<style>
		
		.infoBox {
			background: linear-gradient(to top, #b1d352, #93bc3c);
			border-radius: 20px; 
			color : white;
			cursor: pointer;
			line-height: 22px; 
			margin: 2px; 
			text-align: right;
			vertical-align: middle;
		}
		.UserInfoBox{
			background-color: #f5b846;
			border-radius: 20px; 
			color : white;
			line-height: 22px; 
			margin: 9px 0 0 9px;
			text-align: right;
			vertical-align: middle;
			font-family: tahoma;
			font-size: 11px;
			float: left;
			width : 200px;
		}
		.accinfoBox {
			background: linear-gradient(to top, #72dbfc, #159fcd);
			border-radius: 20px; 
			color : white;
			cursor: pointer;
			padding-right:4px;
			line-height: 22px; 
			margin: 2px; 
			text-align: right;
			vertical-align: middle;
		}
		
		.menuCls span {
			color:white !important;font-weight:bold !important;
		}
		.overCls {
			background-color: #72dbfc;
		}
		.x-btn-pressedCls {
			background-color: #72dbfc;
		}
		
		.menuItems {
			padding: 10px !important;
		}
		
		.framework-comment{
			background-image:url('icons/comment.png') !important;
			background-size: 30px 30px;
		}
		.framework-Calculator{
			background-image:url('icons/Calculator.png') !important;
			background-size: 30px 30px;
		}
		
	</style>
	<script type="text/javascript">
	//-----------------------------
	//	Programmer	: SH.Jafarkhani
	//	Date		: 1394.06
	//-----------------------------

	function compareObject(o1, o2){
		for(var p in o1){
			if(o1[p] !== o2[p]){
				return false;
			}
		}
		for(var p in o2){
			if(o1[p] !== o2[p]){
				return false;
			}
		}
		return true;
	}

	FrameWorkClass.prototype = {
		TabsArray : new Array(),
		centerPanel : "",
		menuItems : "",
		StartPage : ""
	};

	function FrameWorkClass()
	{
		this.ExpireInterval = setInterval(function(){

			Ext.Ajax.request({
				url : "header.inc.php",
				method : "POST",

				success : function(response)
				{
					if(response.responseText.trim() != "")
					{
						document.getElementById("LoginExpire").style.display = "";					
						clearInterval(framework.ExpireInterval);
					}
				}
			});

		}, 5*60000); // in milisecond

		//----------------------------------------------------------
		
		this.centerPanel = new Ext.TabPanel({
			region: 'center',
			enableTabScroll : true,
			resizeTabs      : true,
			deferredRender: false,
			autoScroll : true,
			minTabWidth: 120,
			tabWidth: 'auto'
		});
		
		//----------------------------------------------------------
		
		this.northPanel = new Ext.panel.Panel({
			region: 'north',
			fill: true,	  
			items : [{
				xtype : 'panel',
				border : false,
				layout: 'fit',
				height : 100,
				html : "<div style='background-color: #fdfdfd;background-image: url(http://www.transparenttextures.com/patterns/subtle-grey.png);" +
					"font-family:IranNastaliq; font-size: 35px;color : #5883af;text-shadow: 2px 2px 4px #85ab38;padding-right:10px;height:100%' >" + 
					"<?= SoftwareName ?>"+
					"<div class='blueText UserInfoBox'>" + 
						"<img style='width: 35px; float: right; vertical-align: middle; margin-top: 3px;' src=icons/user.png>" +
						"<?= $_SESSION['USER']["fullname"] ?><br> شناسه : <?= $_SESSION['USER']["UserName"]?>" + 
					"</div>" +
					"</div>",
				bbar : {
					xtype : "toolbar",
					docked: 'bottom',
					style : "background: linear-gradient(to bottom , #159fcd, #1e8cb0);",
					defaults :{
						cls : "x-btn menuCls",
						overCls : "overCls",
						pressedCls : "pressedCls",
						focusCls : "overCls",
						menuActiveCls : "pressedCls"
					},
					items :[<?= $menuStr ?>, '->', {
						xtype : "button",						
						icon : "icons/home.png",
						scale: 'medium',
						handler : function(){framework.OpenPage("/framework/StartPage.php", "صفحه اصلی");}
					},{
						xtype : "button",
						icon : "icons/exit.png",
						scale: 'medium',
						handler : function(){framework.OpenPage("/framework/logout.php");}
					}]
				}
			}]
		});
		
		//----------------------------------------------------------
		
		this.EastPanel = new Ext.panel.Panel({
			region: 'east',
			collapsible: true,			  
			width: 180,
			minSize: 180,
			maxSize: 180,
			resizable : false,
			fill: true,	  
			bodyStyle : "background-color: #fdfdfd;background-image: url(http://www.transparenttextures.com/patterns/subtle-grey.png);text-align:center",
			defaults : {
				hideCollapseTool : true
			},
			items : [
			{
				xtype : "container",
				width: 183,
				html : '<canvas id=canvas width=90px height=90px></canvas>'
			},
			new Ext.picker.SHDate({
				border : false
			}),{
				xtype : "container",
				layout : "hbox",
				items : [{
					xtype : "button",
					tooltip : "درخواست پشتیبانی",
					scale: 'large',
					iconCls : "framework-comment",
					style : "margin: 3px; height:35px",
					handler : function(){
						framework.OpenPage('../framework/ManageRequests.php','درخواست پشتیبانی');
					}	
				},{
					xtype : "button",
					tooltip : "ماشین حساب",
					scale: 'large',
					iconCls : "framework-Calculator",
					style : "margin: 3px;height:35px",
					handler : function(){
						framework.OpenClaculator();
					}	
				}]
				
			},{
				xtype : "container",
				contentEl : document.getElementById("framework_accDiv")
			},{
				xtype : "container",
				contentEl : document.getElementById("framework_calculatorDiv")
			}]
		});
		
		//----------------------------------------------------------
		
		this.view = new Ext.Viewport({
			layout: 'border',
			renderTo : document.body,
			items: [this.northPanel,this.centerPanel,this.EastPanel]
		});
		
		//----------------------------------------------------------
				
		setInterval(this.showClock, 1000);
	}

	FrameWorkClass.prototype.OpenPage = function(itemURL, itemTitle, params)
	{
		if(itemURL == "")
			return;

		if(arguments.length < 3)
			params = {};

		itemURL = this.formatUrl(itemURL);

		var id = "ext_tab_" + Ext.MD5(itemURL);
		params.ExtTabID = id;

		if(this.TabsArray[id])
		{
			this.centerPanel.setActiveTab(id);
			if(itemTitle != "")
				this.centerPanel.items.get(id).setTitle(itemTitle);

			if(!compareObject(this.TabsArray[id].params, params))
			{
				Ext.getCmp(id).close();
				/*Ext.getCmp(id).loader.load({
					url: itemURL,
					method: "POST",
					params : newParam,
					text: "در حال بار گذاری...",
					scripts: true
				});
				this.TabsArray[id].params = params;*/
			}
			else
				return;
		}

		this.TabsArray[id] =
		{
			params : params,
			itemURL : itemURL,
			title : itemTitle
		}

		var newTab = this.centerPanel.add({
			title: itemTitle,
			id: id,
			bodyCfg: {style: "padding:10px;background-color:white"},
			closable: true,
			autoScroll : true,
			loader : {
				url: itemURL,
				method: "POST",
				params : params,
				text: "در حال بار گذاری...",
				scripts: true
			},
			listeners : {
				beforeclose : function(){
					this.destroy();
					delete framework.TabsArray[id];
					return true;
				}
			}
		}).show();

		newTab.loader.load();
	}

	FrameWorkClass.prototype.CloseTab = function(TabID)
	{
		delete framework.TabsArray[TabID];
		//this.centerPanel.getItem(TabID).close();
	// this.centerPanel.getItem(TabID).destroy();
	this.centerPanel.items.get(TabID).destroy();
	}

	FrameWorkClass.prototype.logout = function()
	{
		Ext.Ajax.request({
			url : "/framework/logout.php",
			method : "POST",

			success : function()
			{
				window.location = "/framework/login.php";
			}
		});
	}

	FrameWorkClass.prototype.home = function()
	{
		window.location = document.location;
	}

	FrameWorkClass.prototype.formatUrl = function(url)
	{
		var list = url.split("/");
		var list2 = new Array();
		for(var i=1; i<list.length; i++)
		{
			if(list[i] == "..")
				list2.pop();
			else
				list2.push(list[i]);
		}

		return "/" + list2.join("/");
	}

	//..........................................................................
	
	FrameWorkClass.prototype.showClock = function() {

            // DEFINE CANVAS AND ITS CONTEXT.
            var canvas = document.getElementById('canvas');
            var ctx = canvas.getContext('2d');

            var date = new Date;
            var angle;
            var secHandLength = 30;

            // CLEAR EVERYTHING ON THE CANVAS. RE-DRAW NEW ELEMENTS EVERY SECOND.
            ctx.clearRect(0, 0, canvas.width, canvas.height);        

            OUTER_DIAL1();
            OUTER_DIAL2();
            CENTER_DIAL();
            MARK_THE_HOURS();
            MARK_THE_SECONDS();

            SHOW_SECONDS();
            SHOW_MINUTES();
            SHOW_HOURS();

            function OUTER_DIAL1() {
                ctx.beginPath();
                ctx.arc(canvas.width / 2, canvas.height / 2, secHandLength + 10, 0, Math.PI * 2);
                ctx.strokeStyle = '#92949C';
                ctx.stroke();
            }
            function OUTER_DIAL2() {
                ctx.beginPath();
                ctx.arc(canvas.width / 2, canvas.height / 2, secHandLength + 7, 0, Math.PI * 2);
                ctx.strokeStyle = '#929BAC';
                ctx.stroke();
            }
            function CENTER_DIAL() {
                ctx.beginPath();
                ctx.arc(canvas.width / 2, canvas.height / 2, 2, 0, Math.PI * 2);
                ctx.lineWidth = 3;
                ctx.fillStyle = '#353535';
                ctx.strokeStyle = '#0C3D4A';
                ctx.stroke();
            }

            function MARK_THE_HOURS() {

                for (var i = 0; i < 12; i++) {
                    angle = (i - 3) * (Math.PI * 2) / 12;       // THE ANGLE TO MARK.
                    ctx.lineWidth = 1;            // HAND WIDTH.
                    ctx.beginPath();

                    var x1 = (canvas.width / 2) + Math.cos(angle) * (secHandLength);
                    var y1 = (canvas.height / 2) + Math.sin(angle) * (secHandLength);
                    var x2 = (canvas.width / 2) + Math.cos(angle) * (secHandLength - (secHandLength / 7));
                    var y2 = (canvas.height / 2) + Math.sin(angle) * (secHandLength - (secHandLength / 7));

                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);

                    ctx.strokeStyle = '#466B76';
                    ctx.stroke();
                }
            }

            function MARK_THE_SECONDS() {

                for (var i = 0; i < 60; i++) {
                    angle = (i - 3) * (Math.PI * 2) / 60;       // THE ANGLE TO MARK.
                    ctx.lineWidth = 1;            // HAND WIDTH.
                    ctx.beginPath();

                    var x1 = (canvas.width / 2) + Math.cos(angle) * (secHandLength);
                    var y1 = (canvas.height / 2) + Math.sin(angle) * (secHandLength);
                    var x2 = (canvas.width / 2) + Math.cos(angle) * (secHandLength - (secHandLength / 30));
                    var y2 = (canvas.height / 2) + Math.sin(angle) * (secHandLength - (secHandLength / 30));

                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);

                    ctx.strokeStyle = '#C4D1D5';
                    ctx.stroke();
                }
            }

            function SHOW_SECONDS() {

                var sec = date.getSeconds();
                angle = ((Math.PI * 2) * (sec / 60)) - ((Math.PI * 2) / 4);
                ctx.lineWidth = 0.5;              // HAND WIDTH.

                ctx.beginPath();
                // START FROM CENTER OF THE CLOCK.
                ctx.moveTo(canvas.width / 2, canvas.height / 2);   
                // DRAW THE LENGTH.
                ctx.lineTo((canvas.width / 2 + Math.cos(angle) * secHandLength),
                    canvas.height / 2 + Math.sin(angle) * secHandLength);

                // DRAW THE TAIL OF THE SECONDS HAND.
                ctx.moveTo(canvas.width / 2, canvas.height / 2);    // START FROM CENTER.
                // DRAW THE LENGTH.
                ctx.lineTo((canvas.width / 2 - Math.cos(angle) * 20),
                    canvas.height / 2 - Math.sin(angle) * 20);

                ctx.strokeStyle = '#586A73';        // COLOR OF THE HAND.
                ctx.stroke();
            }

            function SHOW_MINUTES() {

                var min = date.getMinutes();
                angle = ((Math.PI * 2) * (min / 60)) - ((Math.PI * 2) / 4);
                ctx.lineWidth = 1.5;              // HAND WIDTH.

                ctx.beginPath();
                ctx.moveTo(canvas.width / 2, canvas.height / 2);  // START FROM CENTER.
                // DRAW THE LENGTH.
                ctx.lineTo((canvas.width / 2 + Math.cos(angle) * secHandLength / 1.1),      
                    canvas.height / 2 + Math.sin(angle) * secHandLength / 1.1);

                ctx.strokeStyle = '#999';  // COLOR OF THE HAND.
                ctx.stroke();
            }

            function SHOW_HOURS() {

                var hour = date.getHours();
                var min = date.getMinutes();
                angle = ((Math.PI * 2) * ((hour * 5 + (min / 60) * 5) / 60)) - ((Math.PI * 2) / 4);
                ctx.lineWidth = 1.5;              // HAND WIDTH.

                ctx.beginPath();
                ctx.moveTo(canvas.width / 2, canvas.height / 2);     // START FROM CENTER.
                // DRAW THE LENGTH.
                ctx.lineTo((canvas.width / 2 + Math.cos(angle) * secHandLength / 1.5),      
                    canvas.height / 2 + Math.sin(angle) * secHandLength / 1.5);

                ctx.strokeStyle = '#000';   // COLOR OF THE HAND.
                ctx.stroke();
            }
        }

	//..........................................................................
	
	function LoanRFID(RequestID)
	{
		st = RequestID.lpad("0", 7);
		SUM = st[0]*1 + st[1]*2 + st[2]*3 + st[3]*4 + st[4]*5 + st[5]*6 + st[6]*7;
		remain = SUM % 11;
		remain = remain == 10 ? 0 : remain;

		code = st + remain;
		return code;
	}
	
	//..........................................................................
	FrameWorkClass.prototype.OpenClaculator = function(){
		
		if(!this.CalcWin)
		{
			this.CalcWin = new Ext.window.Window({
				width : 190,
				renderTo : document.body,
				height : 230,
				loader : {
					url : "/generalUI/calculator/calculator.html",
					method : "post",
					scripts : true,
					autoLoad : true
				},
				closeAction : "hide"
			});

		}
		this.CalcWin.show();
		this.CalcWin.loader.load();
	}
	//..........................................................................
		
	var required = '<span style="color:red;font-weight:bold" data-qtip="فیلد اجباری">*</span>';
	Ext.QuickTips.init();
	var framework;
	setTimeout(function(){
		Ext.get('loading').remove();
		Ext.get('loading-mask').fadeOut({
			remove:true
		});
		framework = new FrameWorkClass();
		//if(FrameWorkClass.StartPage != "" && FrameWorkClass.StartPage != undefined)
		//framework.OpenPage("/framework/StartPage.php", "صفحه اصلی");
	}, 7);
	
	var MonthStore = new Ext.data.SimpleStore({
	fields : ['id','title'],
	data : [ 
		["1", "فروردین"],
		["2", "اردیبهشت"],
		["3", "خرداد"],
		["4", "تیر"],
		["5", "مرداد"],
		["6", "شهریور"],
		["7", "مهر"],
		["8", "آبان"],
		["9", "آذر"],
		["10", "دی"],
		["11", "بهمن"],
		["12", "اسفند"]
	]
});

	var YearStore = new Ext.data.SimpleStore({
	fields : ['id','title'],
	data : [ 
		["1395", "1395"],
		["1396", "1396"],
		["1397", "1397"],
		["1398", "1398"],
		["1399", "1399"],
		["1400", "1400"],
		["1401", "1401"],
		["1402", "1402"],
		["1403", "1403"],
		["1404", "1404"]
	]
});

var personStore = new Ext.data.Store({
	pageSize: 10,
	model:  Ext.define(Ext.id(), {
		extend: 'Ext.data.Model',
		fields:['PersonID','pfname','plname','unit_name','person_type','staff_id','personTypeName']
	}),
	remoteSort: true,
	proxy:{
		type: 'jsonp',
		url: "/HumanResources/personal/persons/data/person.data.php?task=searchPerson&newPersons=true",
		reader: {
			root: 'rows',
			totalProperty: 'totalCount'
		}
	}
});
					  
	</script>
	<script type="text/javascript" src="/HumanResources/global/LOV/LOV.js?v=1"></script>		
		<div id="LoginExpire" style="display : none;">
			<div style="color: red; height: 40px; width: 100%; z-index: 99999; position: fixed; 
				 background-color: white; text-align: center; font-weight: bold;cursor:pointer"
				 onclick="window.location='/framework/login.php'">
				<br>زمان انتظار شما به پایان رسیده است لطفا مجدد وارد شوید</div>
			<div style="position: fixed;top: 40;left: 0;height:100%;width:100%;z-index: 9999999;
			background-color : #999;opacity: 0.7;filter: alpha(opacity=70);-moz-opacity: 0.7; /* mozilla */"></div>
		</div>
		<!--------------------------------------------------------------------->
		<div class="blueText accinfoBox" id="framework_accDiv" style="height: 80px;" 
			 onclick="framework.OpenPage('../accounting/baseinfo/UserState.php','تعیین شعبه و دوره')">
			<div style="padding-top:6px">دوره مالی : <?= $_SESSION["accounting"]["CycleYear"]?> 
				<br>شعبه : <?= $_SESSION["accounting"]["BranchName"]?>
			</div>
		</div>
		<!--------------------------------------------------------------------->
	</body>
</html>
