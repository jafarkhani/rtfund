<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."safebox.data.php?task=SelectPercents","div_dg");

$dgh->addColumn("","holdingID",'string',true);
$dgh->addColumn("","functorFullname",'string',true);

$col=$dgh->addColumn("&#1578;&#1575;&#1585;&#1740;&#1582;", "holdingDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 100;

$col = $dgh->addColumn("&#1588;&#1585;&#1581; &#1575;&#1602;&#1604;&#1575;&#1605;", "holdingDesc");
$col->editor = ColumnEditor::TextField();
$col->width = 340;

$col = $dgh->addColumn("&#1575;&#1606;&#1580;&#1575;&#1605; &#1583;&#1607;&#1606;&#1583;&#1607;", "PersonID");
/*$col->renderer="function(v,p,r){return r.data.functorFullname;}";
$col->editor = "this.PersonCombo";*/
$col->editor = ColumnEditor::ComboBox(array(
    array("id"=>'2161',"title"=>'&#1581;&#1605;&#1740;&#1583;&#1607; &#1585;&#1590;&#1608;&#1740;'),
    array("id"=>"2530",'title'=>"&#1587;&#1593;&#1740;&#1583; &#1582;&#1740;&#1575;&#1591; &#1605;&#1602;&#1583;&#1605;"),
    array("id"=>"2236",'title'=>"&#1605;&#1585;&#1578;&#1590;&#1740; &#1582;&#1575;&#1583;&#1605;&#1740;")),
    "id", "title");
$col->width = 150;

$col = $dgh->addColumn("&#1606;&#1608;&#1593; &#1593;&#1605;&#1604;&#1740;&#1575;&#1578;", "operationType");
$col->editor = ColumnEditor::ComboBox(array(
    array("id"=>'1',"title"=>'&#1575;&#1606;&#1578;&#1602;&#1575;&#1604; &#1576;&#1607; &#1589;&#1606;&#1583;&#1608;&#1602;'),
    array("id"=>"2",'title'=>"&#1576;&#1585;&#1583;&#1575;&#1588;&#1578; &#1575;&#1586; &#1589;&#1606;&#1583;&#1608;&#1602;")),
    "id", "title");
$col->width = 150;

$col = $dgh->addColumn('&#1593;&#1605;&#1604;&#1740;&#1575;&#1578;', '', 'string');
$col->renderer = "DepositePercent.OperationRender";
$col->width = 50;
$col->align = "center";

if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return DepositePercentObject.Add(v,p,r);}";
	$dgh->enableRowEdit = true ;
	$dgh->rowEditOkHandler = "function(v,p,r){ return DepositePercent.Save(v,p,r);}";
}
$dgh->title ="&#1583;&#1585;&#1589;&#1583; &#1587;&#1608;&#1583; &#1587;&#1662;&#1585;&#1583;&#1607; &#1607;&#1575;";

$dgh->emptyTextOfHiddenColumns=true;
$dgh->width = 780;
$dgh->DefaultSortField = "holdingID";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnableSearch = false;
$grid = $dgh->makeGrid_returnObjects();
?>
<script>

DepositePercent.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DepositePercent()
{
	/*this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/person/persons.data.php?task=selectPersons&IsStaff=YES',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID"
	});*/
      
	this.grid = <?= $grid ?>;                
	this.grid.render(this.get("div_dg"));
	
}

DepositePercent.OperationRender = function(value, p, record){

    return "<div  title='&#1593;&#1605;&#1604;&#1740;&#1575;&#1578;' class='setting' onclick='DepositePercentObject.OperationMenu(event);' " +
        "style='background-repeat:no-repeat;background-position:center;" +
        "cursor:pointer;width:100%;height:16'></div>";
}

var DepositePercentObject = new DepositePercent();

DepositePercent.prototype.OperationMenu = function(e){

    record = this.grid.getSelectionModel().getLastSelected();
    var op_menu = new Ext.menu.Menu();



        op_menu.add({text: '&#1581;&#1584;&#1601;',iconCls: 'remove',
            handler : function(){ return DepositePercentObject.Delete(); }});
    op_menu.add({text: '&#1662;&#1740;&#1608;&#1587;&#1578;',iconCls: 'attach',
        handler : function(){ return DepositePercentObject.Documents('safeBox'); }});

    op_menu.showAt(e.pageX-120, e.pageY);
}


DepositePercent.Save = function(store,record,op)
{    
	mask = new Ext.LoadMask(Ext.getCmp(DepositePercentObject.TabID), {msg:'&#1583;&#1585; &#1581;&#1575;&#1604; &#1584;&#1582;&#1610;&#1585;&#1607; &#1587;&#1575;&#1586;&#1610;...'});
	mask.show();    
	
	Ext.Ajax.request({
		url:  DepositePercentObject.address_prefix + 'safebox.data.php?task=SavePercent',
		params:{
			record : Ext.encode(record.data)
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			DepositePercentObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

DepositePercent.prototype.Add = function()
{  
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		holdingID:null	

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

DepositePercent.prototype.Delete = function()
{    
	Ext.MessageBox.confirm("","&#1570;&#1740;&#1575; &#1605;&#1575;&#1740;&#1604; &#1576;&#1607; &#1581;&#1584;&#1601; &#1605;&#1740; &#1576;&#1575;&#1588;&#1740;&#1583;&#1567;", function(btn){
		
		if(btn == "no")
			return;
		
		me = DepositePercentObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(me.grid, {msg:'&#1583;&#1585; &#1581;&#1575;&#1604; &#1584;&#1582;&#1610;&#1585;&#1607; &#1587;&#1575;&#1586;&#1610;...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'safebox.data.php?task=DeletePercent',
			params:{
				holdingID : record.data.holdingID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				DepositePercentObject.grid.getStore().load();
			},
			failure: function(){}
		});		
	});
}

DepositePercent.prototype.Documents = function(ObjectType){

    if(!this.documentWin)
    {
        this.documentWin = new Ext.window.Window({
            width : 720,
            height : 440,
            modal : true,
            bodyStyle : "background-color:white;padding: 0 10px 0 10px",
            closeAction : "hide",
            loader : {
                url : "../../office/dms/documents.php",
                scripts : true
            },
            buttons :[{
                text : "&#1576;&#1575;&#1586;&#1711;&#1588;&#1578;",
                iconCls : "undo",
                handler : function(){this.up('window').hide();}
            }]
        });
        Ext.getCmp(this.TabID).add(this.documentWin);
    }

    this.documentWin.show();
    this.documentWin.center();

    var record = this.grid.getSelectionModel().getLastSelected();
    this.documentWin.loader.load({
        scripts : true,
        params : {
            ExtTabID : this.documentWin.getEl().id,
            ObjectType : ObjectType,
            ObjectID : record.data.holdingID
        }
    });
}

</script>

<center>
	<br>
	<div id="div_dg"></div>
</center>


