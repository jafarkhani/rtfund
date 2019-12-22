<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	98.07
//---------------------------

require_once("../../../header.inc.php");
require_once getenv("DOCUMENT_ROOT") . '/attendance/traffic/traffic.class.php';

if (isset($_GET['showRes']) && $_GET['showRes'] == 1) { 
    
    $CurrentYear = DateModules::GetYear($_POST['ToDate']) ; 
    $TD = DateModules::Shamsi_to_Miladi($_POST['ToDate']);
    $SD = DateModules::Shamsi_to_Miladi($CurrentYear.'/01/01');
    
    $whr = " AND wrt.emp_state = 1 AND wrt.execute_date >= :SDATE " ;
    $whrParams = array();
    if(!empty($_POST['staff_id'])) {
       $whr = " AND t2.staff_id = :SID" ;
       $whrParams[':SID'] =  $_POST['staff_id'] ;
    }
   
   $whrParams[':EDATE'] =  $TD  ;
   $whrParams[':SDATE'] =  $SD  ;
   
    $query = " select t2.staff_id,RefPersonID,pfname,plname,t2.writ_id , t2.writ_ver , st.person_type , sum(wsi.value) sv ,
    wrt.emp_mode ,wrt.execute_date lastDate
					from (
					
					select  t1.staff_id,pfname,plname,RefPersonID,
							SUBSTRING_INDEX(SUBSTRING(max_execute_date,11),'.',1) writ_id,
							SUBSTRING_INDEX(max_execute_date,'.',-1) writ_ver
					from (

                                            select w.staff_id,p.RefPersonID,p.pfname,p.plname,max( CONCAT(execute_date,writ_id,'.',writ_ver) ) max_execute_date
                                            from HRM_writs w inner join HRM_staff s on w.staff_id = s. staff_id
                                                            inner join HRM_persons p on s.PersonID = p. PersonID

                                            where  history_only <> 1 and s.person_type in(3 ) and
                                            w.emp_mode <> 4 and 
                                            execute_date <= :EDATE   and
                                            w.issue_date <= :EDATE 
                                            group by s.staff_id 

						) t1
					
					) t2 inner join HRM_writ_salary_items wsi
									on  t2.staff_id = wsi.staff_id and
										t2.writ_id = wsi.writ_id and
										t2.writ_ver = wsi.writ_ver
						 inner join HRM_writs wr 
									on wsi.staff_id = wr.staff_id and 
									   wsi.writ_id = wr.writ_id and 
									   wsi.writ_ver = wr.writ_ver
						 
						 inner join HRM_staff st on  st.staff_id = wr.staff_id
						 inner join HRM_writs wrt 
						 on st.staff_id = wrt.staff_id and 
						    st.last_writ_id = wrt.writ_id and 
						    st.last_writ_ver = wrt.writ_ver

						 where (1=1) $whr
						 group by t2.staff_id " ; 
						 
						 $dataTable = PdoDataAccess::runquery($query , $whrParams );
			//echo PdoDataAccess::GetLatestQueryString();die();
					
     
    for($i=0;$i<count($dataTable);$i++){
        
		if($dataTable[$i]["emp_mode"] == 3 || $dataTable[$i]["emp_mode"] == 4 ){
            if($TD < $dataTable[$i]["lastDate"])
               $dataTable[$i]["lastDate"] = $TD;
        }
        else 
            $dataTable[$i]["lastDate"] = $TD ;
        
        $SUM = ATN_traffic::Compute($SD, $dataTable[$i]["lastDate"] , $dataTable[$i]["RefPersonID"], false);
        if($SUM === false)
		{
			echo "خطا در بازیابی اطلاعات حضور و غیاب";die();
		}
		else 
		{
			$dataTable[$i]["Off"] =  TimeModules::SecondsToTime($SUM["Off"]);
			$dataTable[$i]["firstAbsence"] = TimeModules::SecondsToTime($SUM["firstAbsence"]);
			$dataTable[$i]["lastAbsence"] = TimeModules::SecondsToTime($SUM["lastAbsence"]);
			$dataTable[$i]["absence"] = TimeModules::SecondsToTime($SUM["absence"]);
			$dataTable[$i]["DailyOff_1"] = $SUM["DailyOff_1"] ;
			$dataTable[$i]["DailyOff_2"] = $SUM["DailyOff_2"] ;
			$dataTable[$i]["DailyOff_3"] = $SUM["DailyOff_3"] ;
			$dataTable[$i]["DailyAbsence"] = $SUM["DailyAbsence"] ;
		}
        
        
             
        unset($arr);      
        $arr = preg_split('/\//',DateModules::miladi_to_shamsi($dataTable[$i]["lastDate"]) );
        $TotalDay = ($arr[1] - 1 ) * 30.4375 + $arr[2] ;
        $dataTable[$i]["AllowedLeave"]  =  round(( 30 * $TotalDay ) / 365) ; 	
    }
		
	?>													
		
    
  <style>
		.reportGenerator {border-collapse: collapse;border: 1px solid black;font-family: tahoma;font-size: 8pt;
						  text-align: center;width: 50%;padding: 2px;}
		.reportGenerator .header {color: white;font-weight: bold;background-color:#4D7094}
		.reportGenerator .header1 {color: white;font-weight: bold;background-color:#465E86}		
		.reportGenerator td {border: 1px solid #555555;height: 20px;}
	</style>
	<?
	echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl"><center>';

	//.........................................
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' width='100px' ></td>
				<td align='center' style='font-family:b titr;font-size:15px'>گزارش ذخیره مرخصی کارکنان" .
	"<br><br> " . $CurrentYear . " </td>				
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : "
	. DateModules::shNow() . "<br>";
	echo "</td></tr></table>";



	echo '<table  class="reportGenerator" style="text-align: right;width:100%!important" cellpadding="4" cellspacing="0">			
			 <tr class="header">								
			 <td>شماره شناسایی </td>	
			 <td>نام</td> 
			 <td>نام خانوادگی</td>
			 <td>مرخصی سالانه </td>
			 <td>تا تاریخ</td>
			 <td>مرخصی استحقاقی مجاز </td>
			 
			 <td>مرخصی استحقاقی </td>
			 <td>مانده مرخصی  </td>
			 <td>مبلغ حکم  </td>
			 <td>مبلغ مانده مرخصی  </td>
			 
			 <td>مرخصی ساعتی</td>
			 <th>تاخیر</th>
			<th>تعجیل</th>
			<th>غیبت</th>
			<th>مرخصی استعلاجی</th>
			<th>مرخصی بدون حقوق</th>
			<th>غیبت روزانه</th>
			 </tr>';
    $TotalHours = 0 ; 
    $TotalMinute = 0 ; 
	for ($i = 0; $i < count($dataTable); $i++) {
        
        $TotalHours = 0 ; 
        $TotalMinute = 0 ; 
              
        $TotalHours = $dataTable[$i]['Off'][0] + $dataTable[$i]['firstAbsence'][0] + $dataTable[$i]['lastAbsence'][0] + $dataTable[$i]['absence'][0] ; 
        $TotalMinute = $dataTable[$i]['Off'][1] + $dataTable[$i]['firstAbsence'][1] + $dataTable[$i]['lastAbsence'][1] + $dataTable[$i]['absence'][1] ; 
        
        $TM = ( $TotalHours * 60 ) + $TotalMinute ; 
        $TD = round(($TM / 450),2) ; 
       
		echo " <tr>					
					<td>" . $dataTable[$i]['staff_id'] . "</td> 
					<td>" . $dataTable[$i]['pfname'] . "</td>	
					<td>" . $dataTable[$i]['plname'] . "</td>
					<td> 30 </td>
					<td>" . DateModules::miladi_to_shamsi($dataTable[$i]["lastDate"]) . "</td>	 
					<td>" . $dataTable[$i]["AllowedLeave"] . "</td>	
					<td>" . $dataTable[$i]["DailyOff_2"] . "</td>	
					<td>" . ($dataTable[$i]["AllowedLeave"] - $dataTable[$i]["DailyOff_2"] - $dataTable[$i]['DailyAbsence'] - $TD  )  . "</td>	
					<td>" . number_format(( $dataTable[$i]['sv'] ), 0, '.', ',') . "</td>
					<td>" . number_format(( ($dataTable[$i]['sv'] / 30 ) * ($dataTable[$i]["AllowedLeave"] - $dataTable[$i]["DailyOff_2"] - $dataTable[$i]['DailyAbsence'] - $TD   ) ), 0, '.', ',') . "</td>
					
					<td>" . TimeModules::ShowTime($dataTable[$i]['Off']) . "</td>
					<td>" . TimeModules::ShowTime($dataTable[$i]['firstAbsence']) . "</td>
					<td>" . TimeModules::ShowTime($dataTable[$i]['lastAbsence']) . "</td>
					<td>" . TimeModules::ShowTime($dataTable[$i]['absence']) . "</td>
					<td>" . $dataTable[$i]['DailyOff_1'] . "</td>
					<td>" . $dataTable[$i]['DailyOff_3'] . "</td>
					<td>" . $dataTable[$i]['DailyAbsence'] . "</td>
				</tr>";
				
	}	

	echo "</table>";

	die();
    
}

?>
<script>
    EmpGradation.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function EmpGradation()
{
    
   
	this.form = this.get("form_SearchGrad");
		
	
	this.advanceSearchPanel = new Ext.Panel({
		applyTo: this.get("AdvanceSearchDIV"),		
		title: "گزارش ارتقاء رتبه",
		autoWidth:true,
		autoHeight: true,
		collapsible : true,
		animCollapse: false,
		frame: true,
		width : 600,
		bodyCfg: {style : "padding-right:10px;background-color:white;"},
                layout: {
				type:"table",
				columns:1
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
				colspan: 3,
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
                        xtype: "shdatefield",
                        name : "ToDate",
                        itemId : "ToDate",
                        fieldLabel : "تا تاریخ",
                        allowBlank:false                
                        }],	
		buttons : [{
					text:'جستجو',
					iconCls: 'search',
					handler: 
					function(){ EmpGradationObject.advance_searching();}
				   }]
	});	
}

var EmpGradationObject = new EmpGradation();

EmpGradation.prototype.advance_searching = function()
{ 
        var date=this.advanceSearchPanel.down("[itemId=ToDate]");
        var value=date.value;
        if(typeof value=='undefined')
        {
             Ext.MessageBox.alert("پیام", "تاریخ را وارد نمایید.");
             return;
        }
	this.form = this.get("form_SearchGrad") ;
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "RedemptionLeave.php?showRes=1";
	this.form.submit();	
	return;

}
</script>
<form id="form_SearchGrad" >
    <center>
        <div>
            <div id="AdvanceSearchDIV">				
            </div>
        </div>
    </center>
</form>