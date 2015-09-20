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
	this.items = new Array(<?= $menuStr ?>);

	this.ExpireInterval = setInterval(function(){
		
		Ext.Ajax.request({
			url : "header.inc.php",
			method : "POST",

			success : function(response)
			{
				if(response.responseText.trim() != "")
				{
					framework.centerPanel.tabBar.getEl().dom.innerHTML = "<div align=center style=width:100%;color:red;font-size:12px;font-weight:bold;>"+
						"<br>شما در سیستم غیر فعال شده اید. لطفا مجدد وارد سیستم شوید...<br>&nbsp;</div>";
					clearInterval(framework.ExpireInterval);
				}
			}
		});
		
	}, 600000);
	
	this.centerPanel = new Ext.TabPanel({
		region: 'center',
		enableTabScroll : true,
		resizeTabs      : true,
		deferredRender: false,
		autoScroll : true,
		minTabWidth: 120,
		tabWidth: 'auto'
	});
	
	this.view = new Ext.Viewport({
		layout: 'border',
		renderTo : document.body,
		items: [ 
			//------------------------------------------------------------------
			this.centerPanel,
			//------------------------------------------------------------------
			{
				xtype: 'panel',
                region: 'east',
                split: true,
				collapsible: true,			  
				itemId: 'leftPanel',
                width: 200,
				minSize: 200,
                maxSize: 200,
				layout:'accordion',
				fill: true,	  
				defaults : {
					hideCollapseTool : true
				},
				tbar : [{
					autoWidth : true,
					xtype: 'container',
					layout : "vbox",
					items : [{
						xtype : "container",
						height : 230,
						html: '<div style="width: 200px;background-color:white; vertical-align: middle; padding-right: 4px; padding-top: 4px; padding-bottom: 4px;" align="center">'+
							
							'<table width="96%" style="background-color : #35bc7a;border-radius:20px"><tr>'+
								'<td id="framework_TD_Date" style="color:white;width: 70%;padding-right: 8px;padding-top: 8px;'+
									'vertical-align: middle;font-size:12px;font-weight: bold;"></td>'+
								'<td align="left" style="padding:5px;">'+
									'<embed type="application/x-shockwave-flash" width="70" height="70" src="/framework/icons/clock.swf" wmode="transparent"></embed>'+
								'</td></tr></table>' +
								
							'<div style="width:96%;margin-top:4px;color:white;line-height: 2;font-weight: bold;background-color : #f86924;border-radius:20px; line_height:">'+
							"<?= $SystemName?>"+
							"<br> کاربر : <?= $_SESSION['USER']["fullname"] ?>"+
							"<br> شناسه : <?= $_SESSION['USER']["UserID"]?></div>" +
					
					'<div style="width:96%;margin-top:4px;color:white;background-color : #FFCC00;font-weight: bold;border-radius:20px;line-height: 2;">'+
							"<?= $SystemName?>"+
							"<br> کاربر : <?= $_SESSION['USER']["fullname"] ?>"+
							"<br> شناسه : <?= $_SESSION['USER']["UserID"]?></div></div>"
					}]
					
				}],
				items : [{
					xtype : "panel",
					height : 150,
					overflowY : 'auto',
					layout: 'fit',
					title: 'منوی اصلی',
					items :[{
						xtype: 'menu',
						bodyStyle : "background-color:white !important;",
						floating: false,
						items: [
							{
								icon : "/framework/icons/systems.gif",
								text: 'انتخاب سیستم',
								menu : [<?= $sysArray ?>]
							},{
								icon: '/framework/icons/access.gif',
								text: 'تغییر رمز عبور',
								handler : function(){
									framework.OpenPage('../generalClasses/change_pass.php','تغییر رمز عبور');
								}
							},{
								icon : "/framework/icons/exit.gif",
								text : "خروج",
								handler : function(){
									framework.logout();
								}
							}
						]}
				]},<?= $menuStr?>]
			}
		//------------------------------------------------------------------
        ]
	});

	var now = new Date();
	var XDate = GtoJ(now);
	now = Ext.SHDate.dayNames[XDate.getDay()] + "<br>" +
		XDate.day + " " + Ext.SHDate.monthNames[XDate.getMonth()] + " " + XDate.year;
	document.getElementById("framework_TD_Date").innerHTML = now;

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


</script>