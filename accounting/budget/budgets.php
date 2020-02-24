<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.05
//---------------------------
require_once '../../header.inc.php'; 

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

?>
<script type="text/javascript">
ACC_budgets.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

function ACC_budgets() {

	this.infoPanel = new Ext.form.Panel({
		applyTo: this.get("NewPnl"),
		title: "اطلاعات بودجه",
		bodyStyle: "text-align:right;padding:5px",
		frame: true,
		hidden: true,
		width: 300,
		items: [{
				xtype: "hidden",
				name: "BudgetID",
				itemId: "BudgetID",
				fieldLabel: "کد",
				labelWidth: 40,
				hideTrigger: true
			},{
				xtype: "textarea",
				name: "BudgetDesc",
				itemId: "BudgetDesc",
				fieldLabel: "عنوان",
				labelWidth: 40,
				rows: 5,
				width: 280
			}, {
				xtype: "hidden",
				itemId: "ParentID",
				name: "ParentID"
			}, {
				xtype: "hidden",
				itemId: "old_BudgetID",
				name: "old_BudgetID"
			}],
		buttons: [{
				text: "ذخیره",
				handler: function () {
					ACC_budgetsObject.SaveBudgets();
				},
				iconCls: "save"
			}, {
				text: "انصراف",
				handler: function () {
					ACC_budgetsObject.infoPanel.hide();
				},
				iconCls: "undo"
			}]
	});

	this.tree = new Ext.tree.Panel({
		renderTo: this.get('tree-div'),
		frame: true,
		width: 600,
		height: 600,
		title: "بودجه ها",
		plugins: [new Ext.tree.Search()],
		store: new Ext.data.TreeStore({
			root: {
				id: "source",
				text: "گروه های بودجه",
				expanded: true
			},
			proxy: {
				type: 'ajax',
				url: this.address_prefix + "budget.data.php?task=GetBudgetTree"
			}
		})
	});

	this.tree.getDockedItems('toolbar[dock="top"]')[0].add({
			xtype: "button",
			iconCls: "print",
			text: "چاپ",
			handler: function () {
				Ext.ux.Printer.print(ACC_budgetsObject.tree);
			}
		}, '-', {
			xtype: "button",
			iconCls: "refresh",
			text: "بازگذاری مجدد",
			handler: function () {
				ACC_budgetsObject.tree.getStore().load();
			}
		}
	);

	this.tree.on("itemcontextmenu", function (view, record, item, index, e)
	{
		e.stopEvent();
		e.preventDefault();
		view.select(index);
		me = ACC_budgetsObject;

		this.Menu = new Ext.menu.Menu();

		if(me.AddAccess)
			this.Menu.add({
				text: 'ایجاد رکورد',
				iconCls: 'add',
				handler: function(){ ACC_budgetsObject.BeforeSaveBbudgets("new"); }
			});
		if (record.data.id != "source")
		{   
			if(me.EditAccess)
				this.Menu.add({
					text: 'ویرایش رکورد',
					iconCls: 'edit',
					handler: function(){ ACC_budgetsObject.BeforeSaveBbudgets("edit"); }

				});
			
			if(me.RemoveAccess)
				this.Menu.add({
					text: 'حذف رکورد',
					iconCls: 'remove',
					handler: function(){ ACC_budgetsObject.DeleteBudgets(); }

				});
		}
	
		var coords = e.getXY();
		this.Menu.showAt([coords[0] - 120, coords[1]]);
	});
}

var ACC_budgetsObject = new ACC_budgets();

ACC_budgets.prototype.BeforeSaveBbudgets = function (mode, obj){

	var record = this.tree.getSelectionModel().getSelection()[0];

	this.infoPanel.getForm().reset();
	this.infoPanel.show();

	if (mode == "edit")
	{
		this.infoPanel.getComponent("ParentID").setValue(record.raw.ParentID);
		this.infoPanel.getComponent("BudgetDesc").setValue(record.raw.BudgetDesc);
		this.infoPanel.getComponent("BudgetID").setValue(record.data.id);
	} 
	else 
	{
		this.infoPanel.getComponent("ParentID").setValue(record.data.id == "source" ? 0 : record.data.id);
	}
}

ACC_budgets.prototype.DeleteBudgets = function () {

	var record = this.tree.getSelectionModel().getSelection()[0];

	if (record.childNodes.length != 0)
	{
		Ext.MessageBox.alert("Error","ابتدا رکوردهای این گروه را حذف کنید");
		return;
	}
	if (!confirm("آیا مایل به حذف می باشید؟"))
		return;

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg: 'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'DeleteBudget',
			BudgetID: record.data.id
		},
		url: this.address_prefix + 'budget.data.php',
		method: 'POST',
		success: function (response) {
			mask.hide();
			record.remove();
		},
		failure: function () {}
	});

}

ACC_budgets.prototype.SaveBudgets = function () {

	this.infoPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'budget.data.php?task=SaveBudget',
		method: "POST",

		success: function (form, action) {

			mode = ACC_budgetsObject.infoPanel.getComponent("BudgetID").getValue() == "" ? "new" : "edit";

			BudgetID = ACC_budgetsObject.infoPanel.getComponent("BudgetID").getValue();
			ParentID = ACC_budgetsObject.infoPanel.getComponent("ParentID").getValue();
			if (ParentID != "0")
				Parent = ACC_budgetsObject.tree.getRootNode().findChild("id", ParentID, true);
			else
				Parent = ACC_budgetsObject.tree.getRootNode();

			if (mode == "new")
			{
				Parent.set('leaf', false);
				Parent.appendChild({
					id: action.result.data,
					text: ACC_budgetsObject.infoPanel.getComponent("BudgetDesc").getValue(),
					leaf: true
				});
			} else
			{
				ACC_budgetsObject.tree.getRootNode().findChild("id", BudgetID, true).
						set('text', ACC_budgetsObject.infoPanel.getComponent("BudgetDesc").getValue());
			}

			ACC_budgetsObject.infoPanel.getForm().reset();
			ACC_budgetsObject.infoPanel.hide();

		},
		failure: function (form, action)
		{
			alert("کد وارد شده تکراری می باشد");
		}
	});
}

</script>
<table width="750px" style="margin:10px">
	<tr>
		<td valign="top" width="40%"><div id="tree-div"></div></td>
		<td valign="top" style="padding-right : 20px">
			<div id="NewPnl" class="x-hide-display"></div>
		</td>
	</tr>
</table>
