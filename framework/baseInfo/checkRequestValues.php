<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;



/*var_dump($_REQUEST) ;
echo '<br>';
echo $_REQUEST["SourceID"];*/
$SourceType = $_REQUEST["SourceType"];
$SourceID =  $_REQUEST["SourceID"];
/*var_dump($SourceID);*/

/*$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseInfo.data.php?task=GetCheckValue&".
		"SourceType=" .$SourceType . "&SourceID=" . $SourceID,"grid_div");*/


$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseInfo.data.php?task=GetReqCheckValue&".
		"SourceType=" .$SourceType . "&SourceID=" . $SourceID,"grid_div");
		
$dg->addColumn("", "valueID","", true);
$dg->addColumn("", "ItemID","", true);
$dg->addColumn("", "ItemDesc","", true);
$dg->addColumn("", "checked","", true);


$col = $dg->addColumn("نام مدرک", "ItemDesc");
$col->width = 200;

$col = $dg->addColumn("زیرفرآیند مربوطه", "subProcess");
$col->width = 100;

$col = $dg->addColumn("الزامی ", "Necessary");
$col->renderer = "CheckValues.ConfirmRender";
$col->width = 60;

$col = $dg->addColumn("", "checked");
$col->renderer = "CheckValues.CheckRender";
$col->width = 40;

$col = $dg->addColumn("مورد تایید", "description");
$col->renderer = "CheckValues.ConfirmRender";
$col->width = 80;
/*$col->ellipsis = 60;
$col->width = 140;*/

$col = $dg->addColumn("ثبت کننده", "Fullname");
$col->width = 100;
$col = $dg->addColumn("تاریخ ثبت", "DoneDate", GridColumn::ColumnType_datetime);
$col->width = 130;

$col = $dg->addColumn("پیوست","");
$col->sortable = false;
$col->renderer = "function(v,p,r){return CheckValues.attachRender(v,p,r);}";
$col->width = 50;

$dg->height = 336;
$dg->width = 780;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->autoExpandColumn = "ItemDesc";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

CheckValues.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function CheckValues()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}
/*CheckValues.NecessaryRender=function ($value){
	switch($value)
	{
		case "1":
		    return "است";
		case "2":
		    return "نیست";
	}
}*/
CheckValues.ConfirmRender=function ($value){
	switch($value)
	{
		case "1" : return "است"; break;
		     
		case "2": return "نیست"; break;
	}
}
CheckValues.CheckRender = function(v,p,r){
	
	return "<input type=checkbox onclick='CheckValuesObject.BeforeSave(this.checked)' "+
		(r.data.checked == 1 ? "checked" : "") + " >";
}
CheckValues.attachRender = function(v,p,r){

    return "<div align='center' title='پیوست' class='attach' "+
    "onclick='CheckValuesObject.RecordDocuments();' " +
    "style='background-repeat:no-repeat;background-position:center;" +
    "cursor:pointer;width:100%;height:16'></div>";
}

CheckValues.prototype.BeforeSave = function(checked){
var myStore = Ext.create('Ext.data.Store',{
        fields:['Id','Name'],
        data:[
            {Id:1,Name:'بله'},
            {Id:2,Name:'خیر'}
        ]
    });
	if(!checked)
	{
		this.Save(checked, "");
		return;
	}
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 198,
			title : "توضیحات",
			bodyStyle : "background-color:white",
			items : [{
                xtype : "combo",
                name:'description',
                fieldLabel:'آیا موردتایید می باشد ',
                displayField:'Name',
                valueField:'Id',
                queryMode:'local',
                store: myStore
            }/*,{
                xtype : "combo",
                name:'isNecessary',
                fieldLabel:'آیا الزامی می باشد ',
                displayField:'Name',
                valueField:'Id',
                queryMode:'local',
                store: myStore
            },{
				xtype : "textarea",
				width : 400,
				rows : 6,
				name : "description"
			}*/],
			closeAction : "hide",
			buttons : [{
				text : "ذخیره",				
				iconCls : "save",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		CheckValuesObject.Save(checked,
			this.up('window').down("[name=description]").getValue());});
		
	this.commentWin.show();
	this.commentWin.center();
}

CheckValues.prototype.Save = function(checked, description,isNecessary){

	var record = this.grid.getSelectionModel().getLastSelected();
	if(record == null)
	{
		this.commentWin.hide();
		return;
	}
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SaveCheckReqValue",
			ItemID : record.data.ItemID,
			SourceID : <?= $_REQUEST["SourceID"] ?>,
			checked : checked ? 1 : 0,
			description : description,
			isNecessary : isNecessary
			
		},
		success: function(response){
			mask.hide();
			if(CheckValuesObject.commentWin)
				CheckValuesObject.commentWin.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				CheckValuesObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

CheckValues.prototype.RecordDocuments = function(){

    if(!this.documentWin)
{
    this.documentWin = new Ext.window.Window({
    width : 920, 
    height : 440,
    modal : true,
    bodyStyle : "background-color:white;padding: 0 10px 0 10px",
    closeAction : "hide",
    loader : {
    url : "../office/dms/documents.php",
    scripts : true
},
    buttons :[{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});
    Ext.getCmp(this.TabID).add(this.documentWin);
}

    this.documentWin.show();
    this.documentWin.center();

    var record = this.grid.getSelectionModel().getLastSelected();
    /*console.log(record.data.ItemID);*/
    this.documentWin.loader.load({
    scripts : true,
    params : {
    ExtTabID : this.documentWin.getEl().id,
    ObjectType : 'ReqCheckList',
    ObjectID : record.data.valueID
}
});
}

var CheckValuesObject = new CheckValues();

</script>
<center>
	<div id="div_grid"></div>
</center>