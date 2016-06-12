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
	$menuStr .= "{text: '" . $sysRow["SysName"] . "',scale: 'medium',icon : 'icons/app.png'";
	
	$menus = FRW_access::getAccessMenus($sysRow["SystemID"]);
	if(count($menus) > 0)
		$menuStr .= ",menu : {xtype : 'menu',items:[";
	
	//........................................................
	$groupArr = array();
	foreach($menus as $row)
	{
		if (!isset($groupArr[ $row["GroupID"] ] )) 
		{
			if(count($groupArr) > 0)
			{
				$menuStr = substr($menuStr, 0, strlen($menuStr) - 1);
				$menuStr .= "]},";
			}
			$icon = $row['GroupIcon'];
			$icon = (!$icon) ? "/generalUI/ext4/resources/themes/icons/star.gif" : 
				"/generalUI/ext4/resources/themes/icons/$icon";
			$menuStr .= "{text : '" . $row["GroupDesc"] . "', icon: '" . $icon . "', menu :[";
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
	$menuStr .= "]}";
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
				contentEl : document.getElementById("framework_banner"),
				bbar : [<?= $menuStr ?>, '->', {
					xtype : "button",
					icon : "icons/home.png",
					scale: 'medium'					
				},{
					xtype : "button",
					icon : "icons/exit.png",
					scale: 'medium'					
				}]
				
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
			}),
			{
				xtype : "container",
				contentEl : document.getElementById("framework_UserDiv")
			},{
				xtype : "container",
				contentEl : document.getElementById("framework_taskDiv")				
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

	FrameWorkClass.SystemLoad = function(){};
	
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
    
</script>
	</script>

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
		}, 7);
	</script>

		<div id="LoginExpire" style="display : none;">
			<div style="color: red; height: 40px; width: 100%; z-index: 99999; position: fixed; 
				 background-color: white; text-align: center; font-weight: bold;cursor:pointer"
				 onclick="window.location='/framework/login.php'">
				<br>زمان انتظار شما به پایان رسیده است لطفا مجدد وارد شوید</div>
			<div style="position: fixed;top: 40;left: 0;height:100%;width:100%;z-index: 9999999;
			background-color : #999;opacity: 0.7;filter: alpha(opacity=70);-moz-opacity: 0.7; /* mozilla */"></div>
		</div>
		<!--------------------------------------------------------------------->
		<div id="framework_banner" style="font-family:IranNastaliq; font-size: 30px;color:white;
			 text-shadow: 2px 2px 4px white;padding-right:10px;background: linear-gradient(to bottom right, #001635, #02a2cc);">
			<?= SoftwareName ?>
		</div>
		<!--------------------------------------------------------------------->
		<div style="line-height: 21px; text-align: right; margin: 2px; border-radius: 15px; border: 1px solid rgb(13, 218, 178);" 
			 id="framework_UserDiv" class="blueText">
		<img style="width: 35px; float: right; vertical-align: middle; margin-top: 3px;" src="icons/user.png">
			<?= $_SESSION['USER']["fullname"] ?>
			<br> شناسه : <?= $_SESSION['USER']["UserName"]?>
		</div>
		<!--------------------------------------------------------------------->
		<div class="blueText" id="framework_taskDiv" style="cursor: pointer;
			 border-radius: 15px; border: 1px solid rgb(13, 218, 178);
			 height: 35px; line-height: 33px; margin: 2px; text-align: right;">
			<img src="icons/comment.png" style="margin: 3px; float: right; width: 30px;">
			درخواست پشتیبانی
		</div>
	</body>
</html>