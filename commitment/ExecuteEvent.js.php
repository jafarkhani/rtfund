<script type="text/javascript">
//-----------------------------
//	Programmer	: Sh.Jafarkhani
//	Date		: 97.05
//-----------------------------

ExecuteEvent.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",
	EventID : <?= $EventID?>,
	CallbackFn : "<?= $CallbackFn ?>",
	SourcesArr : <?= common_component::PHPArray_to_JSObject($SourcesArr) ?>,

	fieldClasses : {
		combo : "ComboBox",
		textfield : "TextField",
		numberfield : "NumberField",
		shdatefield : "SHDateField",
		currencyfield : "CurrencyField"
	},
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ExecuteEvent(){

	this.RowDetails = {
		ptype: 'rowexpander',
		rowBodyTpl : [
			'<hr><table width=100%>',
			'<tr><td colspan=2>عنوان حساب: <span class=blueText>{CostDesc}</span></td>',
				'<td>آیتم محاسباتی: <span class=blueText>',
				'{[values.ComputeItemDesc == null ? "---" : values.ComputeItemDesc]}</span></td>',			
			'</tr>',
			'<tr>',
				'<td>گروه تفصیلی1: <span class=blueText>{TafsiliTypeDesc1}</span></td>',
				'<td>گروه تفصیلی2: <span class=blueText>{TafsiliTypeDesc2}</span></td>',
				'<td>گروه تفصیلی3: <span class=blueText>{TafsiliTypeDesc3}</span></td>',
			'</tr>',
			'<tr>',
				'<td>آیتم1: <span class=blueText>{param1Desc}</span></td>',
				'<td>آیتم2: <span class=blueText>{param2Desc}</span></td>',
				'<td>آیتم3: <span class=blueText>{param3Desc}</span></td>',
			'</tr>',
			'</table>'
		]
	};

	this.grid = <?=  $grid?>;
	this.grid.getStore().on("load", function(){
		
		for(var i=0; i<this.totalCount; i++)
		{
			record = this.getAt(i);
			for(j=1; j<=3; j++)
			{
				if(record.data["param" + j] != null)
					continue;
				
				if(record.data["paramType" + j] == null)
					record.data["paramType" + j] = "textfield";
				
				if(record.data["paramType" + j] == "combo")
				{
					new Ext.form[ ExecuteEventObj.fieldClasses[ record.data["paramType" + j] ] ]({
						renderTo : ExecuteEventObj.get("param" + j + "_" + record.data.RowID),
						name :  "param" + j + "_" + record.data.RowID,
						width : 80,
						store : new Ext.data.Store({
							fields:["id","title"],
							proxy: {
								type: 'jsonp',
								url: ExecuteEventObj.address_prefix + '../accounting/docs/doc.data.php?task=selectParamItems&ParamID=' +
									record.data["ParamID" + j],
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							autoLoad: true
						}),
						valueField : "id",						
						displayField : "title"
					});							
				}
				else
				{
					new Ext.form[ ExecuteEventObj.fieldClasses[ record.data["paramType" + j] ] ]({
						width : 80,
						renderTo : ExecuteEventObj.get("param" + j + "_" + record.data.RowID),
						name :  "param" + j + "_" + record.data.RowID,
						hideTrigger : (record.data["paramType" + j] == "numberfield" || 
							record.data["paramType" + j] == "currencyfield" ? true : false)
					});			
				}
			}
				
			if(record.data.ComputeItemID*1 == 0)
			{
				new Ext.form.CurrencyField({
					renderTo : record.data.CostType == "DEBTOR" ?
						ExecuteEventObj.get("DebtorAmount_" + record.data.RowID) :	
						ExecuteEventObj.get("CreditorAmount_" + record.data.RowID),
					hideTrigger : true,
					name :  record.data.CostType == "DEBTOR" ? 
						"DebtorAmount_" + record.data.RowID : "CreditorAmount_" + record.data.RowID,
					width : 90
				});
			}
			if(record.data.TafsiliID1 == "0" && record.data.TafsiliType1*1 > 0)
			{
				new Ext.form.ComboBox({
					renderTo : ExecuteEventObj.get("TafsiliID1_" + record.data.RowID),
					width : 140,
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliDesc"],
						proxy: {
							type: 'jsonp',
							url: ExecuteEventObj.address_prefix + '../accounting/baseinfo/baseinfo.data.php?'+
								'task=GetAllTafsilis&TafsiliType='+record.data.TafsiliType1,
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی ...',
					allowBlank : false,
					hiddenName : "TafsiliID1_" + record.data.RowID,
					valueField : "TafsiliID",
					displayField : "TafsiliDesc"
				});
			}
			if(record.data.TafsiliID2 == "0" && record.data.TafsiliType2*1 > 0)
			{
				new Ext.form.ComboBox({
					renderTo : ExecuteEventObj.get("TafsiliID2_" + record.data.RowID),
					width : 140,
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliDesc"],
						proxy: {
							type: 'jsonp',
							url: ExecuteEventObj.address_prefix + '../accounting/baseinfo/baseinfo.data.php?'+
								'task=GetAllTafsilis&TafsiliType='+record.data.TafsiliType2,
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی ...',
					allowBlank : false,
					hiddenName : "TafsiliID2_" + record.data.RowID,
					valueField : "TafsiliID",
					displayField : "TafsiliDesc"
				});
			}
			if(record.data.TafsiliID3 == "0" && record.data.TafsiliType3*1 > 0)
			{
				new Ext.form.ComboBox({
					renderTo : ExecuteEventObj.get("TafsiliID3_" + record.data.RowID),
					width : 140,
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliDesc"],
						proxy: {
							type: 'jsonp',
							url: ExecuteEventObj.address_prefix + '../accounting/baseinfo/baseinfo.data.php?'+
								'task=GetAllTafsilis&TafsiliType='+record.data.TafsiliType3,
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی ...',
					allowBlank : false,
					hiddenName : "TafsiliID3_" + record.data.RowID,
					valueField : "TafsiliID",
					displayField : "TafsiliDesc"
				});
			}
		}
	});
	this.grid.render(this.get("div_grid"));
	this.grid.getStore().proxy.form = this.get("MainForm");

}

ExecuteEvent.DebtorAmountRenderer = function(v,p,r){
	p.tdAttr =  "data-qtip='" + (r.data.ComputeItemDesc == null ? "---" : r.data.ComputeItemDesc) + "'";
	if(r.data.ComputeItemID*1 > 0)
		return Ext.util.Format.Money(v);
	else
		return "<div id=DebtorAmount_" + r.data.RowID + "></div>";
}

ExecuteEvent.CreditorAmountRenderer = function(v,p,r){
	p.tdAttr =  "data-qtip='" + (r.data.ComputeItemDesc == null ? "---" : r.data.ComputeItemDesc) + "'";
	if(r.data.ComputeItemID*1 > 0)
		return Ext.util.Format.Money(v);
	else
		return "<div id=CreditorAmount_" + r.data.RowID + "></div>";
}
//.................................................

ExecuteEvent.Param1Renderer = function(v,p,r){
	if( r.data.paramDesc1 == null)
		return '';
	if( v !== null)
		return r.data.paramDesc1 + ":<br>" + (r.data.ParamValue1 ? r.data.ParamValue1 : v);
	
	return r.data.paramDesc1 + ":<br><div id=param1_" + r.data.RowID + "></div>";
}
ExecuteEvent.Param2Renderer = function(v,p,r){
	if( r.data.paramDesc2 == null)
		return '';
	if( v !== null)
		return r.data.paramDesc2 + ":<br>" + (r.data.ParamValue2 ? r.data.ParamValue2 : v);
	
	return r.data.paramDesc2 + ":<br><div id=param2_" + r.data.RowID + "></div>";
}
ExecuteEvent.Param3Renderer = function(v,p,r){
	if( r.data.paramDesc3 == null)
		return '';
	if( v !== null)
		return r.data.paramDesc3 + ":<br>" + (r.data.ParamValue3 ? r.data.ParamValue3 : v);
	
	return r.data.paramDesc3 + ":<br><div id=param3_" + r.data.RowID + "></div>";
}
//.................................................

ExecuteEvent.TafsiliRenderer1 = function(v,p,r){
	if(r.data.TafsiliType1 == null || r.data.TafsiliType1 == undefined || r.data.TafsiliType1*1 == 0)
			return '';
	if(v == "0" || v == null || v == undefined)
		return r.data.TafsiliTypeDesc1 + "<br><div id=TafsiliID1_" + r.data.RowID + "></div>";
	else
		return r.data.TafsiliTypeDesc1 + ":<br>" + r.data.TafsiliDesc1;
}
ExecuteEvent.TafsiliRenderer2 = function(v,p,r){
	if(r.data.TafsiliType2 == null || r.data.TafsiliType2 == undefined || r.data.TafsiliType2*1 == 0)
		return '';
	if(v == "0" || v == null || v == undefined)
		return r.data.TafsiliTypeDesc2 + "<br><div id=TafsiliID2_" + r.data.RowID + "></div>";
	else
		return r.data.TafsiliTypeDesc2 + ":<br>" + r.data.TafsiliDesc2;
}
ExecuteEvent.TafsiliRenderer3 = function(v,p,r){
	if(r.data.TafsiliType3 == null || r.data.TafsiliType3 == undefined || r.data.TafsiliType3*1 == 0)
		return '';
	if(v == "0" || v == null || v == undefined)
		return r.data.TafsiliTypeDesc3 + "<br><div id=TafsiliID3_" + r.data.RowID + "></div>";
	else
		return r.data.TafsiliTypeDesc3 + ":<br>" + r.data.TafsiliDesc3;
}
//.................................................

ExecuteEventObj = new ExecuteEvent();

ExecuteEvent.prototype.RegisterEventDoc = function(){

	Ext.MessageBox.confirm("", "آیا مایل به صدور سند می باشید؟", function(btn){
		if(btn == "no")
			return;
			
		me = ExecuteEventObj;
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'ExecuteEvent.data.php?task=RegisterEventDoc',
			params : {
				EventID : me.EventID,
				"SourcesArr[]" : me.SourcesArr
			},
			method: 'POST',
			form : me.get("MainForm"),

			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
				{
					Ext.MessageBox.alert("", result.data);
					framework.EventWindow.hide();
					eval(ExecuteEventObj.CallbackFn + "();");
				}
				else
				{
					Ext.MessageBox.alert("خطا", result.data);
				}
			},
			failure : function(){mask.hide();}
		});
	});
}

</script>
