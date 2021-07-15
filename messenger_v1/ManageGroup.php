<?php 

require_once '../header.inc.php';
require_once inc_dataGrid;

require_once 'ManageGroup.js.php';

$dgh = new sadaf_datagrid("deph",$js_prefix_address."ManageGroup.data.php?task=SelectGrop","div_dg");

$col = $dgh->addColumn("کد گروه","GID");
$col->width = 70;

$col = $dgh->addColumn("عنوان گروه", "GroupTitle", GridColumn::ColumnType_string);
$col->editor = ColumnEditor::TextField();

$col = $dgh->addColumn("حذف", "", "");
$col->renderer = "ManageGroup.opRender";
$col->width = 70;

$dgh->addButton = true;
$dgh->addHandler = "function(v,p,r){ return ManageGroupObject.AddGrp(v,p,r);}";


$dgh->title = "گروه های پیام رسان";
$dgh->width = 500;
$dgh->DefaultSortField = "GID";
$dgh->DefaultSortDir = "ASC";
$dgh->autoExpandColumn = "GroupTitle";
$dgh->EnableSearch = true;
$dgh->enableRowEdit = true ;
$dgh->pageSize = "20" ;
$dgh->collapsible = true;
$dgh->collapsed = false;
$dgh->rowEditOkHandler = "function(v,p,r){ return ManageGroupObject.SaveGrp(v,p,r);}";

$gridSupport = $dgh->makeGrid_returnObjects();
//........................اعضای گروه.........................

$dg = new sadaf_datagrid("bg",$js_prefix_address."ManageGroup.data.php?task=SelectMembers","div_branch");

$dg->addColumn("کد گروه","GID","",true);

$col = $dg->addColumn("کد عضو","MID");
$col->width = 70;

$col = $dg->addColumn("نام ", "fname", "string",true);
$col->renderer = "function(v){ return ' '; }" ;

$col = $dg->addColumn("نام خانوادگی", "lname", "string",true);
$col->renderer = "function(v){ return ' '; }" ;

$col = $dg->addColumn("نام و نام خانوادگی عضو", "PersonID", "int");
$col->renderer = "function(v,p,r){return r.data.fname + '  ' + r.data.lname }";
$col->editor = "ManageGroupObject.personCombo";
    
$col = $dg->addColumn("حذف", "", "");
$col->renderer = "ManageGroup.opDelRender";
$col->width = 50;

$dg->addButton = true;
$dg->addHandler = "function(v,p,r){ return ManageGroupObject.AddMember(v,p,r);}";


$dg->title = "اعضای گروه";
$dg->width = 500;
$dg->DefaultSortField = "MID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "PersonID";
$dg->EnableSearch = false ; 
$dg->enableRowEdit = true ;
$dg->rowEditOkHandler = "function(v,p,r){ return ManageGroupObject.SaveMember(v,p,r);}";

$branchGrid = $dg->makeGrid_returnObjects();

?>
<script>
	ManageGroup.prototype.afterLoad = function()
	{      
		this.grid = <?= $gridSupport?>;
		this.grid.render(this.get("div_dg"));       
	}
    
    ManageGroup.prototype.LoadPersonInfo = function(){ 
          this.personCombo = new Ext.form.ComboBox({
                store: new Ext.data.Store({
                    pageSize: 10,
                    model:  Ext.define(Ext.id(), {
                        extend: 'Ext.data.Model',
                        fields:['PersonID','pfname','plname','unit_name','person_type','staff_id','personTypeName',{
                        name : "fullname",
                        convert : function(v,record){return record.data.pfname+" "+record.data.plname;}
                }]
                    }),
                    remoteSort: true,
                    proxy:{
                        type: 'jsonp',
                        url: "/HumanResources/personal/persons/data/person.data.php?task=searchPerson&newPersons=true",
                        reader: {
                            root: 'rows',
                            totalProperty: 'totalCount'
                        }
                    }
                }) ,
                emptyText:'جستجوي استاد/كارمند بر اساس نام و نام خانوادگي ...',
                typeAhead: false,
                listConfig : {
                    loadingText: 'در حال جستجو...'
                },
                pageSize:10,
                width: 550,
                hiddenName : "PersonID",
                valueField : "PersonID",
                displayField : "fullname" ,
                tpl: new Ext.XTemplate(
                        '<table cellspacing="0" width="100%"><tr class="x-grid3-header">'
                            ,'<td>کد شخص</td>'
                            ,'<td>نام</td>'
                            ,'<td>نام خانوادگی</td>'
                            ,'<td>نوع شخص</td></tr>',
                        '<tpl for=".">',
                        '<tr class="x-boundlist-item" style="border-left:0;border-right:0">'
                            ,'<td style="border-left:0;border-right:0" class="search-item">{PersonID}</td>'
                            ,'<td style="border-left:0;border-right:0" class="search-item">{pfname}</td>'
                            ,'<td style="border-left:0;border-right:0" class="search-item">{plname}</td>'
                            ,'<td style="border-left:0;border-right:0" class="search-item">{personTypeName}</td>'
                            ,'</tr>'
                            ,'</tpl>'
                            ,'</table>')

            });
            
             this.branchGrid = <?= $branchGrid ?> ;
            
    }
    
    

	var ManageGroupObject = new ManageGroup();
</script>
<center>    
    <div><div id='FormDIV' class="x-hidden" > </div></div>
    <br><br>
	<div id="div_dg"></div>
	<br><br>
	<div id="div_branch" ></div>   
</center>
