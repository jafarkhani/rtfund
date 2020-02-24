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
			url : "../header.inc.php",
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
					style : "background-color:white;vertical-align: middle; padding: 4px;",
					height : 232,
					items : [{
						xtype : "container",
						height : 150,
						width : 190,
						html: '<table style="background-color : #00c3e0;border-radius:20px;width:100%"><tr>'+
								'<td id="framework_TD_Date" style="color:white;width: 70%;line-height: 18px;'+
									'vertical-align: middle;font-size:12px;font-weight: bold;padding:5px"></td>'+
								'<td align="left" style="padding:4px;">'+
									'<embed type="application/x-shockwave-flash" width="70" height="70" src="/framework/icons/clock.swf" wmode="transparent"></embed>'+
								'</td></tr></table>' +
								
							'<div style="margin-top:4px;color:white;line-height: 18px;font-weight: bold;'+
								'background-color : #009de0;border-radius:20px; padding:6px">'+
							
							"<br> کاربر : <?= $_SESSION['USER']["fullname"] ?>"+
							"<br> شناسه : <?= $_SESSION['USER']["UserName"]?></div>"
					},{
						xtype : "container",
						itemId : "DIV_SystemInfo",
						height : 70,
						width : 190,
						style : "margin-top:3px;color:white;background-color : #196ebe;" +
							"font-weight: bold;border-radius:20px;line-height: 2;padding:6px"
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
						items: [{
								icon: '/generalUI/ext4/resources/themes/icons/access.gif',
								text: 'تغییر رمز عبور',
								handler : function(){
									framework.OpenPage('../framework/ChangePassword.php','تغییر رمز عبور');
								}
							},{
								icon: '/generalUI/ext4/resources/themes/icons/task.jpg',
								text: 'درخواست پشتیبانی',
								handler : function(){
									framework.OpenPage('../framework/ManageRequests.php','درخواست پشتیبانی');
								}
							},{
								icon : "/generalUI/ext4/resources/themes/icons/exit.gif",
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
	
	this.SystemInfo = this.view.down("[itemId=DIV_SystemInfo]");

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

FrameWorkClass.SystemLoad = function(){};
</script>