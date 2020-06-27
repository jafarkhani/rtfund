<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../header.inc.php';
require_once 'management/framework.class.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "person/persons.data.php?task=selectPendingPersons");

$dg->addColumn("","PersonID","string", true);

$col = $dg->addColumn("نام و نام خانوادگی","fullname","string");

$col = $dg->addColumn("موبایل","mobile","string");
$col->width = 100;

$col = $dg->addColumn("نام كاربري","UserName","string");
$col->width = 120;

$dg->height = 190;
$dg->width = 450;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "PersonID";
$dg->title = "کاربران جدیدی که ثبت نام کرده اند";
$dg->autoExpandColumn = "fullname";
$dg->emptyTextOfHiddenColumns = true;
$grid1 = $dg->makeGrid_returnObjects();

//--------------- news -----------------
$temp = FRW_news::ShowNews();
$temp = $temp->fetchAll();
$returnStr = "";
foreach($temp as $row)
{
	$returnStr .= '<font style="color:#17C5FF;">◄&nbsp;' . $row["NewsTitle"] . 
			"</font><div style='width:100%;padding-right:20px'> ".$row["context"]."</div><br>"; 
}

?>
<script>

FrameworkStartPage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function FrameworkStartPage(){
	
	new Ext.panel.Panel({
		renderTo : this.get("div_news"),
		title : "اعلان ها",
		frame : true,
		contentEl : this.get("div_newsContent"),
		width: 800,
		border : false,
		autoHeight : true,
		style : "margin:10px 0 0 0;text-align:right;",
		bodyStyle : "padding:10px"
	});
	
	new Ext.panel.Panel({
		renderTo : this.get("panel1"),
		title : "اتوماسیون اداری",
        width: 220,
        height: 200,
		frame : true,
        layout: 'fit',
        loader : {
			url : "../office/FirstPage.php",
			params : {
				ExtTabID : this.TabID
			},
			scripts : true,
			autoLoad : true
		}
    });
	
	this.grid1 = <?= $grid1 ?>;
	new Ext.panel.Panel({
		renderTo : this.get("panel2"),
		width : 460,
		items : this.grid1,
		frame : true
	});
	
	if(<?= $_SESSION["USER"]["UserName"] == "jannati" || $_SESSION["USER"]["UserName"] == "mokhtari" ? "true" : "false" ?>)
	{
		new Ext.panel.Panel({
			renderTo : this.get("panel3"),
			title : "ثبت ورود و خروج",
			width: 100,
			height: 200,
			frame : true,
			layout: 'fit',
			loader : {
				url : "../attendance/traffic/AddTraffic.php?StartPage=true",
				params : {
					ExtTabID : this.TabID
				},
				scripts : true,
				autoLoad : true
			}
		});
	}
	new Ext.panel.Panel({
		renderTo : this.get("panelNotes"),
		title : "یادآوری ها",
        width: 800,
		autoScroll : true,
		frame : true,
		autoHeight : true,
        layout: 'fit',
        loader : {
			url : "../framework/FollowUps.php",
			params : {
				ExtTabID : this.TabID
			},
			scripts : true,
			autoLoad : true
		}
    });
	
	new Ext.panel.Panel({
		renderTo : this.get("panel4"),
		title : "تسهیلات",
        width: 800,
		autoScroll : true,
		frame : true,
		autoHeight : true,
        layout: 'fit',
        loader : {
			url : "../loan/request/FirstPage.php",
			params : {
				ExtTabID : this.TabID
			},
			scripts : true,
			autoLoad : true
		}
    });
		
	/*new Ext.panel.Panel({
		renderTo : this.get("panel5"),
        width: 800,
		title : "هشدارهای مربوط به کارشناسی طرح ها",
		autoScroll : true,
		frame : true,
		autoHeight : true,
        layout: 'fit',
        loader : {
			url : "../plan/FirstPage.php",
			params : {
				ExtTabID : this.TabID
			},
			scripts : true,
			autoLoad : true
		}
    });*/
}


FrameworkStartPageObject = new FrameworkStartPage();

framework.centerPanel.items.get(FrameworkStartPageObject.TabID).on("activate", function(){
	framework.ReloadTab(FrameworkStartPageObject.TabID);
});
		
FrameWorkClass.prototype.ExecuteEvent = function(EventID, params, CallbackFn){
	
	if(arguments.length == 2)
		CallbackFn = Ext.emptyFn;
	
	if(!this.EventWindow)
	{
		this.EventWindow = new Ext.window.Window({
			width : 1200, 
			renderTo : document.body,
			bodyStyle : "background-color : white",
			title : "اجرای رویداد مالی",
			height : 520,
			//modal : true,
			closeAction : "hide",
			loader : {
				url : "../commitment/ExecuteEvent.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
	}

	this.EventWindow.show();
	this.EventWindow.center();
	
	baseParams = {
		ExtTabID : this.EventWindow.getEl().id,
		EventID : EventID,
		CallbackFn : CallbackFn
	};
	if(params.constructor === Array)
	{
		for(i=0; i<params.length; i++)
			baseParams["SourceID" + (i+1)] = params[i];
	}
	this.EventWindow.loader.load({
		params : baseParams
	});
}
		
</script>
<table style="margin:10px">
	<tr>
		<td><div id="panel1"></div></td>
		<td width="10px"></td>
		<td><div id="panel3"></div></td>
		<td width="10px"></td>
		<td><div id="panel2"></div></td>
	</tr>
	<tr>
		<td colspan="5">
			<div id="div_news">
				<div id="div_newsContent"><?= $returnStr ?></div>
			</div>
		</td>
	</tr>
	<tr>
		<td id="panelNotes" colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td id="panel4" colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td id="panel5" colspan="5"></td>
	</tr>
</table>