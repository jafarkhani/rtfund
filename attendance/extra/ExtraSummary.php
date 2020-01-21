<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.01
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
$dg = new sadaf_datagrid("dg", $js_prefix_address . "extra.data.php?task=GetAllExtraSummarys", "grid_div");

$dg->addColumn("", "SummaryID", "", true);
$dg->addColumn("", "PersonID", "", true);
$dg->addColumn("", "StatusCode", "", true);

$col = $dg->addColumn("نام و نام خانوادگی", "fullname");

$col = $dg->addColumn("واقعیت", "RealAmount");
$col->width =120;

$col = $dg->addColumn("مجاز", "LegalAmount");
$col->width =120;

$col = $dg->addColumn("مجوز", "AllowedAmount");
$col->width =120;

$col = $dg->addColumn("نهایی", "FinalAmount");
$col->renderer = "ExtraSummary.FinalAmountRender";
$col->width =120;

$dg->addButton("btn_compute", "محاسبه اضافه کار", "refresh", "function(){ExtraSummaryObject.ComputeSummary();}");
$dg->addButton("btn_save", "ذخیره مبالغ نهایی", "save", "function(){ExtraSummaryObject.SaveSummary();}");
$dg->addButton("btn_confirm", "تایید نهایی", "tick", "function(){ExtraSummaryObject.ConfirmSummary();}");

$dg->title = "خلاصه لیست اضافه کار ماهانه";
$dg->height = 500;
$dg->width = 750;
$dg->EnablePaging = false;
$dg->DefaultSortField = "fullname";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "fullname";
$dg->EnableSearch = false;
$dg->EnablePaging = false;

$grid = $dg->makeGrid_returnObjects();

?>
<script>

ExtraSummary.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	SummaryYear : '',
	SummaryMonth : '',
	AmountElements : new Array(),

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

ExtraSummary.FinalAmountRender = function(v,p,r){
	
	if(r.data.StatusCode != "RAW")
		return r.data.FinalAmount;
	
	return "<div id='divrow_" + r.data.SummaryID + "' ></div>";
}

ExtraSummary.GridOnLoad = function(store){

	me = ExtraSummaryObject;
	if(store.totalCount == 0 || store.getAt(0).data.StatusCode == "RAW")
	{
		for(i=0; i<store.getCount(); i++)
		{
			var record = store.getAt(i);

			me.AmountElements.push(
				new Ext.form.CurrencyField({
					renderTo : findChild(me.grid.getEl().id, "divrow_" + record.data.SummaryID),
					name : "row_" + record.data.SummaryID,
					hideTrigger : true,
					value : record.data.FinalAmount,
					width : 110
				})
			);
		}
		me.grid.down("[itemId=btn_compute]").show();
		me.grid.down("[itemId=btn_save]").show();
		me.grid.down("[itemId=btn_confirm]").show();
	}
	else
	{
		me.grid.down("[itemId=btn_compute]").hide();
		me.grid.down("[itemId=btn_save]").hide();
		me.grid.down("[itemId=btn_confirm]").hide();
	}
}

function ExtraSummary(){
	
	this.grid = <?= $grid ?>;
	this.grid.getStore().on("load", ExtraSummary.GridOnLoad);
	
	this.MothFieldSet = new Ext.form.FieldSet({
		title: "انتخاب ماه",
		width: 400,
		renderTo : this.get("div_Years"),
		frame: true,
		items : [{
			xtype : "combo",
			store: YearStore,   
			labelWidth : 50,
			width : 220,
			fieldLabel : "سال",
			displayField: 'title',
			name : "year",
			valueField : "id",
			value : '<?= substr(DateModules::shNow(),0,4) ?>'
		},{
			xtype : "combo",
			store: MonthStore,   
			labelWidth : 50,
			width : 220,
			fieldLabel : "ماه",
			displayField: 'title',
			name : "month",
			valueField : "id",
			value : '<?= substr(DateModules::shNow(),5,2)*1 ?>',
			listeners : {
				select : function(){
					me = ExtraSummaryObject;
					me.SummaryMonth = this.getValue();
					me.grid.getStore().proxy.extraParams.SummaryYear = me.MothFieldSet.down("[name=year]").getValue();
					me.grid.getStore().proxy.extraParams.SummaryMonth = this.getValue();					
					me.grid.getStore().load();
				}
			}
		}]
	});
	
	this.grid.getStore().proxy.extraParams.SummaryYear = this.MothFieldSet.down("[name=year]").getValue();
	this.grid.getStore().proxy.extraParams.SummaryMonth = this.MothFieldSet.down("[name=month]").getValue();
	
	this.SummaryYear = this.MothFieldSet.down("[name=year]").getValue();
	this.SummaryMonth = this.MothFieldSet.down("[name=month]").getValue();
	this.grid.render(this.get("grid_div"));	
}

var ExtraSummaryObject = new ExtraSummary();	

ExtraSummary.prototype.ComputeSummary = function(){
	
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'extra.data.php',
		method: "POST",
		params: {
			task: "ComputeExtraSummary",
			SummaryYear : this.SummaryYear,
			SummaryMonth : this.SummaryMonth
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				ExtraSummaryObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("","خطا در اجرای عملیات");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){}
	});
}

ExtraSummary.prototype.SaveSummary = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();
	Ext.Ajax.request({
		url: this.address_prefix + 'extra.data.php?task=SaveExtraSummary',
		method: 'POST',
		form : this.get("ExtraMainForm"),
		params  :{
			SummaryYear : this.SummaryYear,
			SummaryMonth : this.SummaryMonth
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				Ext.MessageBox.alert("", "اطلاعات با موفقیت ذخیره شد");
			}
			else
			{
				Ext.MessageBox.alert("خطا", st.data);
			}
		},
		failure: function(){}
	}); 
}

ExtraSummary.prototype.ConfirmSummary = function(){

	Ext.MessageBox.confirm("", "بعد از تایید دیگر قادر به تغییر اعداد نمی باشید. آیا مایل به تایید می باشید؟",
	function(btn){
		if(btn == "no")
			return;
		
		me = ExtraSummaryObject;
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();
		Ext.Ajax.request({
			url: me.address_prefix + 'extra.data.php?task=ConfirmSummary',
			method: 'POST',
			form : me.get("ExtraMainForm"),
			params  :{
				SummaryYear : me.SummaryYear,
				SummaryMonth : me.SummaryMonth
			},
			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					Ext.MessageBox.alert("", "اطلاعات با موفقیت تایید شد");
					ExtraSummaryObject.grid.getStore().load();
				}
				else
				{
					Ext.MessageBox.alert("خطا", st.data);
				}
			},
			failure: function(){}
		}); 
	});
	
}

</script>
<center>
    <form id="ExtraMainForm">
        <br>
        <div id="div_Years"></div>
		محاسبه اضافه کار هر ماه از روز 
		29ام ماه قبل تا روز 
		28ام
		ماه جاری محاسبه می گردد.
		<br>&nbsp;<br>
        <div id="grid_div"></div>
    </form>
</center>
