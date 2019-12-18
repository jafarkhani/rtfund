<?php
//-----------------------------
//	Date		: 1397.11
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;
   
echo '<head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>			
        <link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-all.css" />
        <link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-rtl.css?v=1" />
        <link rel="stylesheet" type="text/css" href="/generalUI/icons/icons.css?v=1" />
        <script type="text/javascript" src="/generalUI/ext4/resources/ext-all.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/resources/ext-extend.js?v=1"></script>

        <script type="text/javascript" src="/generalUI/ext4/ux/component.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/message.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/SearchField.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/TreeSearch.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/CurrencyField.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/ExtraBar.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/gridprinter/Printer.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/excel.js"></script>
        <script type="text/javascript" src="/generalUI/jquery-3.4.1.min.js"></script>
      </head>';

?>

<div style=""> 
	متن تستی
</div>

<?php

die();

$dg = new sadaf_datagrid("dg", $js_prefix_address . "ManageGroup.data.php?task=SelectMyMessage", "DivGrid");

$dg->addColumn("", "GID", "int", true);

$col = $dg->addColumn("", "TM");
$col->renderer = "MyMessenger.GroupTitleRender";
     

$dg->emptyTextOfHiddenColumns = true;
$dg->autoExpandColumn = "TM";
$dg->DefaultSortField = "TM";
$dg->DefaultSortDir = "DESC";
$dg->width = 600;
$dg->EnablePaging = false;
$dg->HeaderMenu = false;
$dg->EnableSearch = false;
$dg->disableFooter = true;

$grid = $dg->makeGrid_returnObjects();

?>
<body dir="rtl">          
<center>    
    <div id="mainpanel"></div>
	<div id="DivGrid" style="margin:8px;width:98%"></div>	
</center>
</body>
<style>	
	.GrpPic {
        border:1px inset #9990;
        width:120px;
        height:100px;
        cursor:pointer;
        vertical-align: middle;
        border-radius: 50%;}
	.GrpInfo {padding-right:5px; line-height: 21px}
	.MsgInfoBox {
			background: linear-gradient(to top, #72dbfc, #159fcd);
			border-radius: 50%; 
			color : white;
			cursor: pointer;
			padding-right:4px;
			line-height: 22px; 
			margin: 2px; 
			text-align: center;
			vertical-align: middle;
		}
        
    .ChatBox {
        background: linear-gradient(to top, #edf9ff, #bbdbed);
        border-radius: 5%; 
        color : black;
        cursor: pointer;
        padding-right:4px;
        line-height: 22px; 
        margin: 2px; 
        text-align: center;
        vertical-align: middle;
    }
    
    .MyChatBox {
        background: linear-gradient(to top, #eeffed, #e5f2e4);
        border-radius: 5%; 
        color : black;
        cursor: pointer;
        padding-right:4px;
        line-height: 22px; 
        margin: 2px; 
        text-align: center;
        vertical-align: middle;
    }
        
</style>
<script>

MyMessenger.prototype = {
	WinID: document.body,   
	address_prefix : "<?= $js_prefix_address?>",
	IsFirstLoad : false,
	get : function(elementID){
		return findChild(this.WinID, elementID);
	}
};

function MyMessenger(){
 
//...................................................................
  var store =   Ext.create('Ext.data.Store', {                        
            remoteSort: true,
            buffered: true,           
            fields:[{name: 'MSGID'},{name: 'MID'},{name: 'message'}],
               proxy: {
                type: 'jsonp',
                url: '/messenger/ManageGroup.data.php?task=SelectMessageGrp',                
                reader: {
                    root: 'rows',
                    totalProperty: 'totalCount',
                    messageProperty : 'MSGID'
                },
				simpleSortMode: true
            },
            sorters: [{
					property: 'MSGID',
					direction: 'DESC'
				}]
        }) ; 
              
       
    this.grid2 = new Ext.grid.GridPanel({
        
        width: 600,
        height: 430,      
        store: store ,
        verticalScrollerType: 'paginggridscroller',
        loadMask: true,
        disableSelection: true,
        invalidateScrollerOnRefresh: false,
        viewConfig: {
            trackOver: false
        },
        columns: [{menuDisabled : true,
                   align: 'center', 
                   header: '', 
                   dataIndex: 'MSGID', 
                   emptyText: '',
                   hidden: true},
                  {menuDisabled : true,
                   align: 'center', 
                   header: '', 
                   dataIndex: 'MID', 
                   emptyText: '',
                   hidden: true},               
                  {menuDisabled : true,                   
                   header: '', 
                   dataIndex: 'message', 
                   renderer: renderMsg,
                   width: 580,                    
                   type:'string', 
                   type: '',
                   hidden: false,
                   hideMode : 'display',
                   searchable: true, 
                   emptyText: ''}]
                   
    });	
   
    this.newItemPanel = new Ext.Panel({
		renderTo: this.get("mainpanel"),
		title: "لیست پیغام",
		autoHeight: true,
		width: 620,
        height: 550,  
        frame:true,
		style: "padding-right:10px",
        items:[this.grid2 ,                
               this.formpanel = new Ext.form.Panel({               
                layout : {
                    type : "table",
                    columns : 4
                },
                style : "margin:10px 0 10px",
                width : 600,
                items:[{
                        xtype : "textfield",
                        name : "MsgTxt",
                        itemId : "MsgTxt",
                        style : "margin:4px",
                        colspan:3,
                        height:40,
                        width : 400,
                       },
                       {
                        xtype: 'button',
                        height: 30,
                        name:'button1',
                        width: '20',
                        autoEl: {tag: 'center'}, 
                        text: 'Send',
                        handler : 
                            function()
                            {                                                       
                              MyMessengerObject.SaveSendingMsg();
                            }
                       },
                       {
                        xtype: "filefield",
                        name: "FileType",
                        //fieldLabel: "پیوست فایل",                        
                        itemId: 'FileType',                        
                        width: 400
                        }
                ]
                
               })
              ]
        ,
        /*loader:{
            url: this.address_prefix + "ManageGroup.php",
            scripts: true
        },*/
        

	});

	this.newItemPanel.hide();
   
    //.........................
	
	this.grid = <?= $grid ?>;	
	this.grid.render(this.get("DivGrid"));
    
    this.grid.on("cellclick", function () {
            MyMessengerObject.newItemPanel.show();       
            store.prefetch({
                start:0,
                limit:40,
                callback:function(){
                   //store.guaranteeRange(0,20); 
                    store.load();
                   if( MyMessengerObject.IsFirstLoad === false) {
                       MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                       MyMessengerObject.IsFirstLoad = true ; 
                   }              
                        
                }
            }) ; 
            
          //  MyMessengerObject.newItemPanel.loader.load();
            MyMessengerObject.grid.hide();
        });
    
    

}


MyMessenger.GroupTitleRender = function(v,p,r){
        
    var res = v.split("-");
    
	return  "<table width=100%>"+
			"<tr><td width=100px><img src='"+
				MyMessengerObject.address_prefix+"ShowFile.php?source=GrpPic&GID=" + r.data.GID + "' " +			
			" class=GrpPic></td>" + 
			"<td class=GrpInfo>"+
				"<font style=font-size:12px;font-weight:bold;color:#666>" + res[0] + "</font><br><br>" + 				
                "<font style=font-size:12px;color:#666>" + Ext.String.ellipsis(res[1],150) + "</font><br><br>" + 
			"</td>"+
            "<td width=45px ><div class='blueText MsgInfoBox'  style='height:40px;' > " +  			 
			"<div style= 'padding-top:6px' > " + res[2] + "</div></div></td></tr></table>";
}

function renderMsg(value, p, record) {
    if(record.data.MID == 5)
        return  "<div class=' ChatBox'  style='width:70%;float:left' >   " +  			 
                "<div style= 'padding-top:6px;align:right' > " + value + "</div></div>" ;   
    else 
        return  "<div class=' MyChatBox'  style='width:70%;float:right' >   " +  			 
                "<div style= 'padding-top:6px;align:right' > " + value + "</div></div>" ;  
    }
    
MyMessenger.prototype.SaveSendingMsg = function()
{          
    mask = new Ext.LoadMask(MyMessengerObject.newItemPanel, {msg:'در حال ذخيره سازي...'});    
	mask.show();  
	MyMessengerObject.formpanel.getForm().submit(
	{
		url: this.address_prefix + 'ManageGroup.data.php?task=SaveMsg',
		/*params:{
			MsgTxt: MyMessengerObject.newItemPanel.down("[itemId=MsgTxt]").getValue(),
            FileType:MyMessengerObject.newItemPanel.down("[itemId=FileType]").getValue()
		},*/
		method: 'POST',
        isUpload:true,		
		success: function(response,option){
			mask.hide();
			if(response.responseText.indexOf("InsertError") != -1 ||
				response.responseText.indexOf("UpdateError") != -1)
			{
				alert("عملیات مورد نظر با شکست مواجه شد");
				return;
			}
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				MyMessengerObject.newItemPanel.hide();
				//MyMessengerObject.grid.getStore().load();
			}
			else
			{
				alert(response.responseText);
			}
		},
		failure: function(){}
	});

}

var MyMessengerObject ; 

Ext.onReady(function(){
    MyMessengerObject = new MyMessenger(); 
});

</script>