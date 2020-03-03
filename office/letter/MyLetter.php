<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.10
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_component;

$mode = $_REQUEST["mode"];

$task = "";
if($mode == "receive")
{
	require_once 'letter.data.php';
	$task = "SelectReceivedLetters";
}
if($mode == "send")
{
	$task = "SelectSendedLetters";
}

if($task == "")
	die();
$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=" . $task, "grid_div");

$dg->addColumn("", "LetterID", "", true);
$dg->addColumn("", "IsSeen", "", true);
$dg->addColumn("", "IsDeleted", "", true);
$dg->addColumn("", "SendID", "", true);
$dg->addColumn("", "_SendDate", "", true);
$dg->addColumn("", "ResponseTimeout", "", true);
$dg->addColumn("", "organization", "", true);
$dg->addColumn("", "SendComment", "", true);
$dg->addColumn("", "SenderDelete", "", true);

$col = $dg->addColumn('<input type=checkbox onclick=MyLetter.CheckAll(this)>', "TafsiliID", "");
$col->renderer = "MyLetter.SelectRender";
$col->sortable = false;
$col->width = 25;

$col = $dg->addColumn("<img src=/office/icons/LetterType.gif>", "LetterType", "");
$col->renderer = "MyLetter.LetterTypeRender";
$col->width = 30;

$col = $dg->addColumn("<img src=/office/icons/attach.gif>", "hasAttach", "");
$col->renderer = "function(v,p,r){if(v == 'YES') return '<img src=/office/icons/attach.gif>';}";
$col->width = 30;

$col = $dg->addColumn("فوری", "IsUrgent", "");
$col->renderer = "function(v,p,r){if(v == 'YES') return '<img width=16px src=/office/icons/light.gif>';}";
$col->width = 30;

$col = $dg->addColumn("امضاء", "IsSigned", "");
$col->renderer = "function(v,p,r){if(v == 'YES') return '<img width=16px src=/generalUI/icons/sign.png>';}";
$col->width = 30;

$col = $dg->addColumn("شماره", "LetterID", "");
$col->width = 60;
$col->align = "center";

$col = $dg->addColumn("نوع ارجاع", "SendTypeDesc", "");
$col->width = 120;

$col = $dg->addColumn("موضوع نامه", "LetterTitle", "");
$col->renderer = "MyLetter.TitleRender";
if($mode == "receive")
	$col = $dg->addColumn("فرستنده", "FromPersonName", "");
else
	$col = $dg->addColumn("گیرنده", "ToPersonName", "");
$col->renderer = "function(v,p,r){ p.tdAttr = 'data-qtip=\"' + r.data.SendComment + '\"'; return v;}";
$col->width = 150;

$col = $dg->addColumn("فرستنده/گیرنده نامه", "OrgPost", "");
$col->renderer = "function(v,p,r){return (v == null ? '-' : v) + ' ' + (r.data.organization == null ? '' : r.data.organization);}";
$col->width = 250;

if($mode == "receive")
{
	$col = $dg->addColumn("تاریخ دریافت", "_SendDate", GridColumn::ColumnType_date);
	$col->width = 100;
}

$col = $dg->addColumn("عملیات", "");
$col->renderer = "function(v,p,r){return MyLetter.OperationRender(v,p,r);}";
$col->width = 120; 

$dg->addObject("this.deletedBtnObj");

if($mode == "receive")
{
	$dg->addButton("", "خوانده نشده", "view", "function(){MyLetterObject.UnSeen();}");
    $dg->addButton("", "خوانده شده", "tick", "function(){MyLetterObject.Seen();}");

	$dg->addButton("", "امضاء گروهی", "sign", "function(){MyLetterObject.GroupSignLetter();}");
	$dg->addButton("", "ارجاع گروهی", "sendLetter", "function(){MyLetterObject.SendLetter(true);}");
	$dg->addButton("", "حذف گروهی", "remove", "function(){MyLetterObject.GroupDeleteSend();}");
	
}
else
	$dg->addButton("", "حذف نامه های انتخابی", "remove", "function(){MyLetterObject.GroupDeleteSender();}");

$dg->EnableGrouping = true;
$dg->DefaultGroupField = "_SendDate";
if($mode == "send")
	$dg->groupHeaderTpl = "تاریخ ارسال : {[MiladiToShamsi(values.rows[0].data._SendDate)]}";
else
	$dg->groupHeaderTpl = "تاریخ دریافت : {[MiladiToShamsi(values.rows[0].data._SendDate)]}";

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 490;
$dg->HeaderMenu = false;
$dg->title = $mode == "send" ? "نامه های ارسالی" : "نامه های دریافتی";
$dg->DefaultSortField = "_SendDate";
$dg->autoExpandColumn = "LetterTitle";
$grid = $dg->makeGrid_returnObjects();	
?>
<script>
	
MyLetter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	mode : '<?= $mode ?>',
		
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function MyLetter(){
	
	this.deletedBtnObj = Ext.button.Button({
		xtype: "button",
		text : "نامه های حذف شده", 
		iconCls : "list",
		enableToggle : true,
		handler : function(){
			me = MyLetterObject;
			me.grid.getStore().proxy.extraParams.deleted = this.pressed ? "true" : "false";
			me.grid.getStore().load();
		}
	});
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsSeen == "NO")
			return "yellowRow";
		if(MyLetterObject.mode == "receive" && record.data.IsDeleted == "YES")
			return "pinkRow";
		if(MyLetterObject.mode == "send" && record.data.SenderDelete == "YES")
			return "pinkRow";
		return "";
	}	
	this.grid.on("itemdblclick", function(view, record){
			
		framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
		{
			LetterID : record.data.LetterID,
			SendID : record.data.SendID,
			mode : MyLetterObject.mode
		});
	});
	this.grid.getStore().on("load",function(){
		if(MyLetterObject.SummaryStore != undefined)
		MyLetterObject.SummaryStore.load();
	})
	
	if(this.mode == "send")
	{
		this.grid.render(this.get("DivPanel"));
		return;
	}
	
	this.panel = new Ext.panel.Panel({
		renderTo : this.get("DivPanel"),
		border : false,
		layout : "hbox",
		autoHeight : true,
		
		items : [{
			xtype : "container",
			flex : 1,
			html : "<div id=div_grid width=100%></div>"
		},{
			xtype : "container",
			width : 150,
			autoScroll : true,
			autoHeight : true,
				border : true,
			style : "border-left : 1px solid #99bce8;margin-left:5px",
			layout : "vbox",
			itemId : "cmp_buttons"
		}]
	});	
	
	this.SummaryStore = new Ext.data.Store({
		proxy : {
			type: 'jsonp',
			url: this.address_prefix + "letter.data.php?task=ReceivedSummary",
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["SendType","SendTypeDesc","totalCnt","newCnt"],
		autoLoad : true,
		listeners : {
			load : function(){
				me = MyLetterObject;
				//..........................................................
				me.panel.down("[itemId=cmp_buttons]").removeAll();
				for(var i=0; i<this.totalCount; i++)
				{
					record = this.getAt(i);
					me.panel.down("[itemId=cmp_buttons]").add({
						xtype : "button",
						width : 130,
						height : 50,
						autoScroll : true,
						enableToggle : true,
						scale : "large",
						style : "margin-bottom:10px",	
						itemId : record.data.SendType,
						text : record.data.SendTypeDesc + "<br><div style=float:right>" + " تعداد : " + 
							record.data.totalCnt + "</div><div style=float:left>" + "جدید : " + (record.data.newCnt*1>0 ? "<b>" : "") + 
							"( " + record.data.newCnt + " )" + (record.data.newCnt*1>0 ? "<b>" : "") + "</div>",
						handler : function(){MyLetterObject.LoadLetters(this)}
					});
				}
			}
		}
	}); 
}

MyLetter.prototype.LoadLetters = function(btn){
	
	btn.toggle(false);
	this.grid.getStore().proxy.extraParams.SendType = btn.itemId;
	this.grid.setTitle(btn.text.split('<br>')[0]);
	var col = this.grid.columns.findObject('dataIndex','SendTypeDesc');
	
	if(this.grid.rendered)
		this.grid.getStore().loadPage(1);
	else
		this.grid.render(this.get("div_grid"));
	
	if(btn.itemId == "0")
		col.show();
	else
		col.hide();
	
}

MyLetter.SelectRender = function(v,p,r){
	return "<input type=checkbox id=chk_" + r.data.SendID + " name=chk_" + r.data.SendID + " >";
}

MyLetter.CheckAll = function(checkAllElem){
	
	elems = MyLetterObject.get("mainForm").getElementsByTagName("input");
	for(i=0; i<elems.length; i++)
		if(elems[i].id.indexOf("chk_") != -1)
			elems[i].checked = checkAllElem.checked;
}

MyLetter.LetterTypeRender = function(v,p,r){
	
	if(v == 'INNER') 
		return "<img data-qtip='نامه داخلی' src=/office/icons/inner.gif>";
	if(v == 'INCOME') 
		return "<img data-qtip='نامه وارده' src=/office/icons/income.gif>";
	if(v == 'OUTCOME') 
		return "<img data-qtip='نامه صادره' src=/office/icons/outcome.gif>";
}

MyLetter.TitleRender = function(v,p,r){
	
	if(r.data.ResponseTimeout != "0000-00-00" && r.data.ResponseTimeout != null && 
			r.data.ResponseTimeout != "")
		v += "<br>مهلت پاسخ : " + MiladiToShamsi(r.data.ResponseTimeout);
	return v;
}

MyLetterObject = new MyLetter();

MyLetter.OperationRender = function(v,p,r){
	
	return "<div  title='اطلاعات نامه' class='info2' "+
		" onclick='MyLetterObject.LetterInfo();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;float:right;width:20px;height:16'></div>" + 
		
		"<div  title='ارجاع نامه' class='sendLetter' "+
		" onclick='MyLetterObject.SendLetter(false);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;float:right;width:20px;height:16'></div>" + 
		
		(MyLetterObject.mode == "send" && r.data.IsSeen == "NO" ? 
			"<div  title='برگشت' class='undo' " +
			" onclick='MyLetterObject.ReturnLetter();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" : ""
		) +
		
		"<div  title='سابقه' class='history' "+
		" onclick='MyLetterObject.ShowHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;float:right;width:20px;height:16'></div>" +
		
		(MyLetterObject.mode == "receive" && r.data.IsDeleted == "NO" ? 
			"<div  title='حذف از کارتابل' class='remove' " +
			" onclick='MyLetterObject.DeleteSend(0);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" : ""
		) +
		(MyLetterObject.mode == "receive" && r.data.IsDeleted == "YES" ? 
			"<div  title='اضافه به کارتابل' class='add' " +
			" onclick='MyLetterObject.DeleteSend(1);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" : ""
		) + 
		(MyLetterObject.mode == "send" && r.data.SenderDelete == "NO" ? 
			"<div  title='حذف از کارتابل' class='remove' " +
			" onclick='MyLetterObject.DeleteSender(0);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" : ""
		) +
		(MyLetterObject.mode == "send" && r.data.SenderDelete == "YES" ? 
			"<div  title='اضافه به کارتابل' class='add' " +
			" onclick='MyLetterObject.DeleteSender(1);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" : ""
		);
}

MyLetter.prototype.LetterInfo = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
		{
			LetterID : record.data.LetterID,
			SendID : record.data.SendID,
			mode : this.mode
		});
}

MyLetter.prototype.SendLetter = function(grouping){
	
	if(!this.SendingWin)
	{
		this.SendingWin = new Ext.window.Window({
			title : "ارجاع نامه",
			width : 620,			
			height : 435,
			modal : true,
			bodyStyle : "background-color:white;",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "sending.php",
				scripts : true
			}
		});
		Ext.getCmp(this.TabID).add(this.SendingWin);
	}

	this.SendingWin.show();
	this.SendingWin.center();
	
	sendIDArr = new Array();
	if(grouping)
	{
		elems = MyLetterObject.get("mainForm").getElementsByTagName("input");
		for(i=0; i<elems.length; i++)
			if(elems[i].id.indexOf("chk_") != -1 && elems[i].checked)
				sendIDArr.push( elems[i].id.replace("chk_","") );
	}
	
	this.SendingWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.SendingWin.getEl().id,
			parent : "MyLetterObject.SendingWin",
			AfterSendHandler : "MyLetterObject.AfterSend",
			LetterID : this.grid.getSelectionModel().getLastSelected().data.LetterID,
			SendIDArr : grouping ? Ext.encode(sendIDArr) : "",
			SendID : !grouping ? this.grid.getSelectionModel().getLastSelected().data.SendID : ""
		}
	});
}

MyLetter.prototype.ReturnLetter = function(){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url : this.address_prefix + "letter.data.php?task=ReturnSend",
		method : "post",
		params : {
			SendID : this.grid.getSelectionModel().getLastSelected().data.SendID,
			LetterID : this.grid.getSelectionModel().getLastSelected().data.LetterID
		},
		
		success : function(response){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(result.success)
				MyLetterObject.grid.getStore().load();
			else
			{	
				if(result.data == "IsSeen")
					Ext.MessageBox.alert("Error", "نامه توسط گیرنده دیده شده و قابل برگشت نمی باشد");
				else
					Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
			}
		}
	})
}

MyLetter.prototype.DeleteSend = function(mode){
	
	var msg = mode == 0 ? "آیا مایل به حذف از کارتابل می باشید؟" : "آیا مایل به برگشت به کارتابل می باشید؟";
	Ext.MessageBox.confirm("",msg, function(btn){
		if(btn == "no")
			return;
		
		me = MyLetterObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url : me.address_prefix + "letter.data.php?task=DeleteSend",
			method : "post",
			params : {
				mode : mode,
				SendID : me.grid.getSelectionModel().getLastSelected().data.SendID,
				LetterID : me.grid.getSelectionModel().getLastSelected().data.LetterID
			},

			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					MyLetterObject.grid.getStore().load();
				else
				{	
					if(result.data == "IsSeen")
						Ext.MessageBox.alert("Error", "نامه توسط گیرنده دیده شده و قابل برگشت نمی باشد");
					else
						Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
				}
			}
		})
	});
}

MyLetter.prototype.DeleteSender = function(mode){
	
	var msg = mode == 0 ? "آیا مایل به حذف از کارتابل می باشید؟" : "آیا مایل به برگشت به کارتابل می باشید؟";
	Ext.MessageBox.confirm("",msg, function(btn){
		if(btn == "no")
			return;
		
		me = MyLetterObject;
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url : me.address_prefix + "letter.data.php?task=DeleteSender",
			method : "post",
			params : {
				mode : mode,
				SendID : me.grid.getSelectionModel().getLastSelected().data.SendID,
				LetterID : me.grid.getSelectionModel().getLastSelected().data.LetterID
			},

			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					MyLetterObject.grid.getStore().load();
				else
				{	
					Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
				}
			}
		})
	});
}

MyLetter.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش نامه',
			modal : true,
			autoScroll : true,
			width: 615,
			height : 530,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "history.php",
				scripts : true
			},
			buttons : [{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
		});
		Ext.getCmp(this.TabID).add(this.HistoryWin);
	}
	
	this.HistoryWin.show();
	this.HistoryWin.center();
	this.HistoryWin.loader.load({
		params : {
			ExtTabID : this.HistoryWin.getEl().id,
			LetterID : this.grid.getSelectionModel().getLastSelected().data.LetterID
		}
	});
}

MyLetter.prototype.AfterSend = function(){

	MyLetterObject.grid.getStore().load();
}

MyLetter.prototype.UnSeen = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
		return;
	}
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url : this.address_prefix + "letter.data.php?task=UnSeen",
		method : "post",
		params : {
			SendID : record.data.SendID
		},
		
		success : function(response){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(result.success)
				MyLetterObject.grid.getStore().load();
			else
			{	
				Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
			}
		}
	})
}
MyLetter.prototype.Seen = function(){

    record = this.grid.getSelectionModel().getLastSelected();
    if(!record)
    {
        Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
        return;
    }

    mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
    mask.show();

    Ext.Ajax.request({
        url : this.address_prefix + "letter.data.php?task=Seen",
        method : "post",
        params : {
            SendID : record.data.SendID
        },

        success : function(response){
            mask.hide();
            result = Ext.decode(response.responseText);
            if(result.success)
                MyLetterObject.grid.getStore().load();
            else
            {
                Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
            }
        }
    })
}

MyLetter.prototype.GroupDeleteSend = function(){
	
	var msg = "آیا مایل به حذف از کارتابل می باشید؟";
	Ext.MessageBox.confirm("",msg, function(btn){
		if(btn == "no")
			return;
		
		me = MyLetterObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url : me.address_prefix + "letter.data.php?task=GroupDeleteSend",
			form : me.get("mainForm"),
			method : "post",
			
			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					MyLetterObject.grid.getStore().load();
			}
		})
	});
}

MyLetter.prototype.GroupDeleteSender = function(mode){
	
	var msg = "آیا مایل به حذف از کارتابل می باشید؟";
	Ext.MessageBox.confirm("",msg, function(btn){
		if(btn == "no")
			return;
		
		me = MyLetterObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url : me.address_prefix + "letter.data.php?task=GroupDeleteSender",
			form : me.get("mainForm"),
			method : "post",
			
			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					MyLetterObject.grid.getStore().load();
			}
		})
	});
}

MyLetter.prototype.GroupSignLetter = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به امضا می باشید؟", function(btn){
		if(btn == "no")
			return;
		me = MyLetterObject;
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php?task=GroupSignLetter' , 
			method: "POST",
			form : me.get("mainForm"),

			success : function(response){
				mask.hide();
				MyLetterObject.grid.getStore().load();
			},
			failure : function(){
				mask.hide();
			}
		});
	})
	
}
</script>
<br>
<form id="mainForm">
	<div id="DivPanel" style="margin-right:8px;width:98%"></div>
</form>