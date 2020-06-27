<?php
//-----------------------------
// developer: Jafarkhani
// create Date: 96.09
//-----------------------------
include_once '../../header.inc.php';

require_once inc_dataGrid;

$attach_dg = new sadaf_datagrid("attach_dg", $js_prefix_address . "framework.data.php?task=GetPics", "attach_grid_div");

$attach_dg->addColumn("کد تصویر", "PicID");

$col = $attach_dg->addColumn("منبع", "SourceType", "");
$col->renderer = "FRW_pics.SourceRender";
$col->width = 75;

$col = $attach_dg->addColumn("دریافت فایل", "", "");
$col->renderer = "FRW_pics.DownloadRender";
$col->width = 75;

$col = $attach_dg->addColumn("عملیات", "", "string");
$col->renderer = "function(v,p,r){return FRW_pics.opRender(v,p,r);}";
$col->align = "center";
$col->width = 70;


$attach_dg->addButton = true;
$attach_dg->addHandler = "function(v,p,r){ return FRW_picsObject.AddPic(v,p,r);}";

$attach_dg->width = 685;
// $attach_dg->autoExpandColumn = "RequestComment";
$attach_dg->height = 485;
$attach_dg->EnableSearch = false;
$attach_dg->EnablePaging = true;
$attach_dg->DefaultSortField = "PicID"; // todo add date and time column if needed
$attach_dg->title = "تصاویر نرم افزار";
$attach_grid = $attach_dg->makeGrid_returnObjects();
?>
<script>
FRW_pics.prototype = {

	TabID: '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix: "<?= $js_prefix_address ?>",

	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

function FRW_pics() {
	
	this.attach_grid = <?= $attach_grid ?>;
    this.attach_grid.render(this.get("attach_grid_div"));
	
    this.formWindow = new Ext.window.Window({
		title: 'اطلاعات پیوست',
		modal: true,
		width: 520,
		height: 215,
		closeAction: "hide",
		items : this.formPanel = new Ext.form.Panel({
            frame: true,
            items: [
                {
                    xtype: "hidden",
                    name: "PicID",
                    itemId: "PicID",
                },{
                    xtype : "combo",
					store : new Ext.data.SimpleStore({
						data : [
							['login' , "صفحه ورود"]
						],
						fields : ['id','value']
					}),
					displayField : "value",
					valueField : "id",
					fieldLabel : "منبع",
					name : "SourceType"
                },{
                    xtype: 'filefield',
                    width: 400,
                    name: 'attachfile',
                    fieldLabel: 'پیوست',
                    buttonText: ' انتخاب فایل'
                }
            ],

            buttons: [{
                    text: "ذخیره",
                    iconCls: "save",
                    handler: function () {

                        if (!FRW_picsObject.formPanel.getForm().isValid())
                            return;
						FRW_picsObject.formPanel.getForm().submit({
							clientValidation: true,
							url: FRW_picsObject.address_prefix + 'framework.data.php?task=SavePics',
							method: "POST",

							success: function (form, action) {
								if (action.result.success)
								{
									FRW_picsObject.attach_grid.getStore().load();
									FRW_picsObject.formWindow.hide();
								} else
								{
									alert("عملیات مورد نظر با شکست مواجه شد.");
								}

							},
							failure: function (form, action) {

								if (action && action.result)
									Ext.MessageBox.alert("ERROR", action.result.data == "عملیات مورد نظر با شکست مواجه شد." ? "" : action.result.data);
							}
						});
                    }
                }, {
                    text: "انصراف",
                    iconCls: "undo",
                    handler: function () {
                        FRW_picsObject.formWindow.hide();
                    }
                }]
			})
        });
	Ext.getCmp(this.TabID).add(this.formWindow);
}

FRW_pics.DownloadRender = function(v, p, r)
{
    return  "<a target='_blank' href='"+FRW_picsObject.address_prefix+"ShowFile.php?PicID="
            + r.data.PicID + "'><div title='نمایش فایل' class='down' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;height:16'></div></a>";
}

FRW_pics.SourceRender = function(v, p, r)
{
    switch(v)
	{
		case "login" : return "صفحه ورود";
	}
}


FRW_pics.opRender = function (value, p, record) {
		var st = "";
        
		/*st += "<div style='width:70%;float:right'><div  title='ویرایش پیوست' class='edit' onclick='FRW_picsObject.editAttach("+record.index+");' " +
			"style='float:right;background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:16;height:16'></div>";*/

        st += "<div  title='حذف پیوست' class='remove' onclick='FRW_picsObject.deleteAttach();' " +
			"style='float:right;background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:16;height:16;margin-right:3'></div>";
                    

		return st;	
	}

    FRW_pics.prototype.editAttach = function(){
        this.formPanel.getForm().reset();
		this.formWindow.show();
        var record = this.attach_grid.getSelectionModel().getLastSelected();
		this.formPanel.loadRecord(record);
    }

    FRW_pics.prototype.deleteAttach = function(){
        Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){

			if(btn == "no")
				return;

			me = FRW_picsObject;
			var record = me.attach_grid.getSelectionModel().getLastSelected();
			mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخيره سازي...'});
			mask.show();

			Ext.Ajax.request({
				url: me.address_prefix + 'framework.data.php?task=removePics',
				params:{
					PicID: record.data.PicID
				},
				method: 'POST',

				success: function(response,option){
					mask.hide();

					sd = Ext.decode(response.responseText);
					if(!sd.success)
					{
						Ext.MessageBox.alert("Error", "عملیات مورد نظر با خطا مواجه شد");
						return;
					}
					FRW_picsObject.attach_grid.getStore().load();
				},
				failure: function(){}
			});
		});
		
    }

    FRW_pics.prototype.AddPic = function ()
    {
        this.formPanel.getForm().reset();
        this.formWindow.show();
        this.formWindow.center();
    }
	
    var FRW_picsObject = new FRW_pics();

</script>
<br>
<center>
	<div><div id="newAttachDiv"> </div></div>
	<div id="attach_grid_div"> </div>
</center>

