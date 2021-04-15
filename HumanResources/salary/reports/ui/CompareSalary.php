<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	98.10
//---------------------------

require_once("../../../header.inc.php");

if (isset($_GET['showRes']) && $_GET['showRes'] == 1) { 
    
	
	$whrParams = array();
	$whrParams[':FPY'] =  $_POST['from_pay_year'] ;
	$whrParams[':TPY'] =  $_POST['to_pay_year']  ;
	$whrParams[':FPM'] = $_POST['from_pay_month']  ;
	$whrParams[':TPM'] =  $_POST['to_pay_month'] ;
	
    if( $_POST['from_pay_year'] == $_POST['to_pay_year'] )
		$whr = " AND p.pay_year >= :FPY AND p.pay_year <= :TPY AND p.pay_month >= :FPM AND  p.pay_month <= :TPM " ;
	
	/*else if( $_POST['to_pay_year'] - $_POST['from_pay_year'] == 1 )
		$whr = " AND p.pay_year >= :FPY AND p.pay_year <= :TPY  "
			 . " AND ( if( p.pay_year = :FPY ,( p.pay_month >= :FPM AND p.pay_month <=12) ,(1=1) )  ) "
			 . " AND ( if( p.pay_year = :TPY , (p.pay_month >= 1 AND  p.pay_month <= :TPM) , (1=1) )  ) " ;
	else {
		
		$whr = " AND p.pay_year >= :FPY AND p.pay_year <= :TPY  "
			 . " AND ( if( p.pay_year = :FPY ,( p.pay_month >= :FPM AND p.pay_month <=12) ,(1=1) )  ) "
			 . " AND ( if( p.pay_year = :TPY , (p.pay_month >= 1 AND  p.pay_month <= :TPM) , (1=1) )  ) " ;
				
	}	*/
	else {
		$whr = " AND p.pay_year >= :FPY AND p.pay_year <= :TPY  AND  p.pay_month >= :FPM AND p.pay_month <= :TPM " ;
	}
   		
	$SlClause = "" ;
	$GRPClause = "" ; 
	//p.staff_id,pr.pfname , pr.plname  , pi.pay_year , pi.pay_month, bj.UnitID ,u.UnitName ,
	//p.staff_id,pi.pay_year, pi.pay_month
	
	if($_POST['PT_1'] == 'on' )
	{
		$SlClause .= " pi.pay_year , " ; 
		$GRPClause .= " pi.pay_year " ; 
	}
	
	if($_POST['PT_2'] == 'on' )
	{
		$SlClause .= " pi.pay_month , " ; 
		$GRPClause .= " ,pi.pay_month " ; 
	}
	
	if($_POST['PT_3'] == 'on' )
	{
		$SlClause .= " bi.InfoDesc , " ; 
		$GRPClause .= " , w.emp_state  " ; 
	}
	
	if(!empty($_POST['staff_id'])) {
       $whr .= " AND s.staff_id = :SID" ;
       $whrParams[':SID'] =  $_POST['staff_id'] ;
	   
	   $SlClause .= " p.staff_id,pr.pfname , pr.plname, u.UnitName , " ; 
	   $GRPClause .= " , s.staff_id " ; 
		
    }
      
	//......................  وضعیت استخدامی ................
	$WhereEmpstate = "";
	$keys = array_keys($_POST);
	for ($i = 0; $i < count($_POST); $i++) {

		if (strpos($keys[$i], "chkEmpState_") !== false) {
			
			$arr = preg_split('/_/', $keys[$i]);
			if (isset($arr[1]))
				$WhereEmpstate .= ($WhereEmpstate != "") ? "," . $arr[1] : $arr[1];

		}
	}
	
    $query = "  SELECT $SlClause
					  sum(pi.pay_value + pi.diff_pay_value - pi.get_value - pi.diff_get_value) pureval
					  
				FROM   HRM_payments p 
					   INNER JOIN HRM_payment_items pi  
									  on p.staff_id = pi.staff_id and 
										 p.pay_year = pi.pay_year and  
										 p.pay_month = pi.pay_month and 
										 p.payment_type = pi.payment_type
                                         
                       INNER  JOIN HRM_staff s on p.staff_id = s.staff_id
                       INNER JOIN HRM_persons pr on pr.PersonID = s.PersonID 
					   INNER JOIN HRM_writs w ON ( p.writ_id = w.writ_id AND
												   p.writ_ver = w.writ_ver AND p.staff_id = w.staff_id )
					   INNER JOIN BaseInfo bi ON bi.typeid = 58 and bi.InfoID = w.emp_state 
                                         
                       LEFT JOIN BSC_jobs bj ON bj.PersonID = pr.RefPersonID AND IsMain = 'YES'
                       LEFT JOIN BSC_units u ON bj.UnitID = u.UnitID

			  where p.payment_type = 1   AND  w.emp_state in ($WhereEmpstate) $whr 
			  group by  $GRPClause " ; 
						 
	$dataTable = PdoDataAccess::runquery($query , $whrParams );
				
	?>													
		
    
  <style>
		.reportGenerator {border-collapse: collapse;border: 1px solid black;font-family: tahoma;font-size: 8pt;
						  text-align: center;width: 50%;padding: 2px;}
		.reportGenerator .header {color: white;font-weight: bold;background-color:#4D7094}
		.reportGenerator .header1 {color: white;font-weight: bold;background-color:#465E86}		
		.reportGenerator td {border: 1px solid #555555;height: 20px; text-align:center;}
	</style>
	<?php
	echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl"><center>';

	//.........................................
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' width='100px' ></td>
				<td align='center' style='font-family:b titr;font-size:15px'>گزارش مقایسه حقوق دستمزد" .
	"<br><br> " . $CurrentYear . " </td>				
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : "
	. DateModules::shNow() . "<br>";
	echo "</td></tr></table>";

	echo '<table  class="reportGenerator" style="text-align: right;width:100%!important" cellpadding="4" cellspacing="0">			
			 <tr class="header"> ' ;
	
	if(!empty($_POST['staff_id'])) 
	{
		echo '<td>شماره شناسایی </td>	
			  <td>نام</td> 
			  <td>نام خانوادگی</td>
			  <td>حوزه فعالیت</td>    '; 
	}
	
	if($_POST['PT_1'] == 'on' ) 
	{
		 echo '<td>سال</td>' ; 
	}
	if($_POST['PT_2'] == 'on' ) 
	{
		 echo '<td>ماه</td>'  ; 
	}
	if($_POST['PT_3'] == 'on' ) 
	{
		 echo '<td>نوع فرد</td>' ; 
	}
	
	echo '<td>مبلغ</td>
		  </tr>' ; 
	/*
			 <td>شماره شناسایی </td>	
			 <td>نام</td> 
			 <td>نام خانوادگی</td>
			 <td>سال</td>
             <td>ماه</td> 
             <td>حوزه فعالیت</td>              
             <td>مبلغ</td>
			 </tr>';
	 * 	 */
			
    $TotalHours = 0 ; 
    $TotalMinute = 0 ; 
	
	for ($i = 0; $i < count($dataTable); $i++) {
		
		echo "  <tr> " ;
		
		if(!empty($_POST['staff_id'])) 
		{
			echo  "<td>" . $dataTable[$i]['staff_id'] . "</td> 
				   <td>" . $dataTable[$i]['pfname'] . "</td>	
				   <td>" . $dataTable[$i]['plname'] . "</td>
				   <td>" . $dataTable[$i]["UnitName"] . "</td>"; 
		}
		
	if($_POST['PT_1'] == 'on' ) 
	{
		 echo "<td>" . $dataTable[$i]["pay_year"] . "</td>" ; 
	}
	if($_POST['PT_2'] == 'on' ) 
	{
		 echo " <td>" . $dataTable[$i]["pay_month"] . "</td>"  ; 
	}
	if($_POST['PT_3'] == 'on' ) 
	{
		 echo " <td>" . $dataTable[$i]["InfoDesc"] . "</td>"  ; 
	}
	
	echo "<td>" . number_format($dataTable[$i]["pureval"], 0, '.', ',') . "</td>
		  </tr>"  ; 
				
	}	

	echo "</table>";

	die();
    
}

?>
<script>
    CompareSalary.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function CompareSalary()
{   
   
	this.form = this.get("form_SearchGrad");		
	
	this.CompareSalaryPanel = new Ext.Panel({
		applyTo: this.get("CompareSalaryDIV"),		
		title: "گزارش مقایسه حقوق و دستمزد",
		autoWidth:true,
		autoHeight: true,
		collapsible : true,
		animCollapse: false,
		frame: true,
		width : 600,
		bodyCfg: {style : "padding-right:10px;background-color:white;"},
                layout: {
				type:"table",
				columns:2
			},
                bodyPadding: '5 5 0',
                width:580,
                fieldDefaults: {
                        msgTarget: 'side',
                        labelWidth: 80	 
                },
		items :[
				new Ext.form.ComboBox({
				store: personStore,
				emptyText:'جستجوي كارمند بر اساس نام و نام خانوادگي ...',
				typeAhead: false,
				listConfig : {
					loadingText: 'در حال جستجو...'
				},
				pageSize:10,
				width: 480,
				colspan: 2,
				hiddenName : "staff_id",
				fieldLabel : "جستجوی فرد",
				valueField : "staff_id",
				displayField : "fullname",
				tpl: new Ext.XTemplate(
						'<table cellspacing="0" width="100%"><tr class="x-grid3-header">'
							,'<td height="23px">کد پرسنلی</td>'
							,'<td>کد شخص</td>'
							,'<td>نام</td>'
							,'<td>نام خانوادگی</td>'
							,'<td>واحد محل خدمت</td></tr>',
						'<tpl for=".">',
						'<tr class="x-boundlist-item" style="border-left:0;border-right:0">'
							,'<td style="border-left:0;border-right:0" class="search-item">{PersonID}</td>'
							,'<td style="border-left:0;border-right:0" class="search-item">{staff_id}</td>'
							,'<td style="border-left:0;border-right:0" class="search-item">{pfname}</td>'
							,'<td style="border-left:0;border-right:0" class="search-item">{plname}</td>'
							,'<td style="border-left:0;border-right:0" class="search-item">{unit_name}&nbsp;</td></tr>',
						'</tpl>'
						,'</table>'),

				listeners :{
					select : function(combo, records){
						var record = records[0];
						record.data.fullname = record.data.pfname + " " + record.data.plname; 
						this.setValue(record.data.staff_id);
						this.collapse();
					}
				}
			}),
                        {
					xtype: "numberfield",
					hideTrigger: true,
					fieldLabel: "سال از",
					name: "from_pay_year",
					allowBlank: false,
					width: 200
				}, {
					xtype: "numberfield",
					hideTrigger: true,
					fieldLabel: "ماه از",
					name: "from_pay_month",
					allowBlank: false,
					width: 200
				}, {
					xtype: "numberfield",
					hideTrigger: true,
					fieldLabel: "سال تا",
					name: "to_pay_year",
					allowBlank: false,
					width: 200
				}, {
					xtype: "numberfield",
					hideTrigger: true,
					fieldLabel: "ماه تا",
					name: "to_pay_month",
					allowBlank: false,
					width: 200
				},{
					xtype: 'fieldset',
					title: "نوع فرد",
					colspan: 3,
					style: 'background-color:#DFEAF7',
					width: 500,
					fieldLabel: 'Auto Layout',
					itemId: "chkgroup2",
					collapsible: false,
					collapsed: false,
					layout: {
						type: "table",
						columns: 4,
						tableAttrs: {
							width: "100%",
							align: "center"
						},
						tdAttrs: {
							align: 'right',
							width: "۱6%"
						}
					},
					items: [{
							xtype: "checkbox",
							boxLabel: "همه",
							checked: true,
							listeners: {
								change: function () {
									parentNode = CompareSalaryObject.CompareSalaryPanel.down("[itemId=chkgroup2]").getEl().dom;
									elems = parentNode.getElementsByTagName("input");
									for (i = 0; i < elems.length; i++)
									{
										if (elems[i].id.indexOf("chkEmpState_") != -1)
											elems[i].checked = this.getValue();
									}
								}
							}
						}]
				},
				{
                    xtype: "fieldset",
                    style: "margin-left:4px",
                    colspan: 3,
					width: 500,
                    title: "گزارش به تفکیک",
                    html:   "<input type=checkbox name='PT_1' checked>&nbsp;&nbsp;سال&nbsp;&nbsp;&nbsp;&nbsp;" +
                            "<input type=checkbox name='PT_2' >&nbsp;&nbsp;ماه&nbsp;&nbsp;&nbsp;&nbsp;" +
                            "<input type=checkbox name='PT_3' >&nbsp;&nbsp;نوع فرد&nbsp;&nbsp;&nbsp;&nbsp;" 
                }
						],	
		buttons : [{
					text:'جستجو',
					iconCls: 'search',
					handler: 
					function(){ CompareSalaryObject.advance_searching();}
				   }]
	});	
	
	new Ext.data.Store({
			fields: ["InfoID", "InfoDesc"],
			proxy: {
				type: 'jsonp',
				url: this.address_prefix + "../../../global/domain.data.php?task=searchEmpState",
				reader: {
					root: 'rows',
					totalProperty: 'totalCount'
				}
			},
			autoLoad: true,
			listeners: {
				load: function () {
					this.each(function (record) {
						CompareSalaryObject.CompareSalaryPanel.down("[itemId=chkgroup2]").add({
							xtype: "container",
							html: "<input type=checkbox name=chkEmpState_" + record.data.InfoID + " id=chkEmpState_" + record.data.InfoID + " checked > " + record.data.InfoDesc
						});

					});

				}}

		});
}

var CompareSalaryObject = new CompareSalary();

CompareSalary.prototype.advance_searching = function()
{ 
       
	this.form = this.get("CSform_SearchGrad") ;
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "CompareSalary.php?showRes=1";
	this.form.submit();	
	return;

}
</script>
<form id="CSform_SearchGrad" >
    <center>
        <div>
            <div id="CompareSalaryDIV">				
            </div>
        </div>
    </center>
</form>