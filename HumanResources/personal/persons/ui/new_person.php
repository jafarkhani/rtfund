<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	94.11
//---------------------------
require_once("../../../header.inc.php");
require_once("../data/person.data.php");
require_once '../../staff/class/staff.class.php';
//require_once '../../staff/class/staff_detasils.php';
//require_once ("../../../organization/org_units/unit.class.php");
ini_set('display_errors','on');

require_once inc_dataGrid;
//-----------------------------------------------
$RefPersonID = $personID = !empty($_POST["Q0"]) ? $_POST["Q0"] : "";
$FacilID = isset($_POST["MenuID"]) ? $_POST["MenuID"] : "";


$query = " select fname ,lname from BSC_persons  where PersonID = ".$personID  ; 
$resAccInfo = PdoDataAccess::runquery($query);

$query = " select PersonID from HRM_persons  where RefPersonID = ".$personID  ; 
$resPr = PdoDataAccess::runquery($query);

if(!empty($resPr[0]['PersonID']))
{
	$personID = $resPr[0]['PersonID'];	
}
else
	$personID ="";

if(!empty($personID))
{ 
	
	$obj = manage_person::SearchPerson($personID);    
	$SummeryInfo = manage_person::GetAllPersons("p.personid = :PID  ",array(":PID" => $personID));
	
        $staffInfo = new manage_staff("", "", $SummeryInfo[0]["staff_id"]);       
        //print_r($obj) ;   die() ; 

}
else 
{   
    $SummeryInfo[0]['FacCode'] = NULL ;   
    $SummeryInfo[0]['EduGrpCode'] = NULL ;
    $SummeryInfo[0]['ProCode']  = NULL ;     
    $obj = new manage_person();
    $staffInfo = new manage_staff();
   
}


//--------------------------------------------------------------------
$drp_issue_countries = manage_domains::DRP_Countries("country_id","",$obj->country_id,"","70");
$drp_nationality = manage_domains::DRP_Countries("nationality","",$obj->nationality,"","157");

$drp_detective = manage_domains::DRP_detectives("detectiveID","",$obj->detectiveID,"","157");

$drp_banks = manage_domains::DRP_banks("bank_id",$staffInfo->bank_id,"width:150 px","-");
     
	$dg = new sadaf_datagrid("includeHistory",$js_prefix_address . "../../staff/data/staff_include_history.data.php?task=selectIncludeHistory&PID=".$personID  ,
							 "includeHistoryGRID");
	
	$dg->addColumn("", "include_history_id","",true);
	$dg->addColumn("", "personid","",true);

	$col = $dg->addColumn("شماره شناسایی", "staff_id","int");
	$col->width = 140;

	
	$col = $dg->addColumn("تاريخ شروع", "start_date", GridColumn::ColumnType_date);
	$col->editor = ColumnEditor::SHDateField();
	$col->width = 120;
	
	$col = $dg->addColumn("تاريخ پايان", "end_date", GridColumn::ColumnType_date);
	$col->editor = ColumnEditor::SHDateField(true);
	$col->width = 120;
	
	$col = $dg->addColumn("بيمه تامين اجتماعي", "insure_include", GridColumn::ColumnType_string);
	$col->editor = ColumnEditor::ComboBox(manage_domains::DRP_Is_Valid(), "value", "caption"); 
	$col->width = 120;

	$col = $dg->addColumn("ماليات", "tax_include", "string");
	$col->editor = ColumnEditor::ComboBox(manage_domains::DRP_Is_Valid(), "value", "caption");
	$col->width = 80;
	
	$dg->width = 630;
    $dg->height = 200;
	$dg->EnableSearch = false ;
	$dg->EnablePaging = false ;
	$dg->DefaultSortField = "include_history_id";
	$dg->title = "سابقه مشمولیت";    
    
	$col = $dg->addColumn("حذف", "", "string");
	$col->renderer = " function(v,p,r){ return PersonObject.opDelRender(v,p,r); }";
	$col->width = 40;

	$dg->addButton = true;
	$dg->addHandler = "function(v,p,r){ return PersonObject.AddIncludeHistory(v,p,r);}";

	$dg->enableRowEdit = true ;
	$dg->rowEditOkHandler = "function(v,p,r){ return PersonObject.SaveHistory(v,p,r);}";
    
	$includeHistoryGrid = $dg->makeGrid_returnObjects();
	
require_once '../js/new_person.js.php';
        
?>

<div id="form_person">
<br>
<div>
	<div id="SummaryPersonidDIV">
	<table id="SummaryPNL" width="100%">
		<tr>
			<td width="20%" >
			شماره شناسایی :
			</td>
			<td  class="blueText"  colspan="3" width="60%"><?=$SummeryInfo[0]['staff_id'] ?></td>

			<td style="vertical-align:middle" align="left" rowspan="4" colspan="2" width="20%">
				<img src="<?= $js_prefix_address?>showImage.php?PersonID=<?= $personID ?>" height="110" >
			</td>

		</tr>
		<tr>
			<td width="20%">
			نام :
			</td>
			<td width="30%" class="blueText" ><?=$resAccInfo[0]['fname'] ?>	</td>
			<td width="15%">
			نام خانوادگی :
			</td>
			<td width="15%" class="blueText" ><?=$resAccInfo[0]['lname'] ?></td>
		</tr>
             
		<tr>
			<td width="20%">
			واحد محل خدمت :
			</td>
			<td width="60%" class="blueText" colspan="3"></td>
		</tr>
       
	</table></div>
	</div>
	<br>            
	<div id="mainTab"  style="width:1000px;" >
		<div id="div_PInfo" class="x-hide-display">
			<form id="personInfoForm" enctype='multipart/form-data'>
				<div class="panel" style="width:950px;">
					<input type='hidden' id='PersonID' name='PersonID' value="<?= $personID ?>">
<input type='hidden' id='RefPersonID' name='RefPersonID' value="<?= $RefPersonID ?>"> 
					<table width="640px">
						<tr>
							<td width="25%">
							نام فارسی :
							</td>
							<td width="25%">
							<input type="text" id="pfname" name="pfname" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $resAccInfo[0]['fname']?>">
							</td>
							<td width="25%">
							نام خانوادگي فارسي :
							</td>
							<td width="25%">
							<input type="text" id="plname" name="plname" class="x-form-text x-form-field" style="width: 150px"
								   value="<?= $resAccInfo[0]['lname']?>">
							</td>
						</tr>                     
						<tr>
							<td width="25%">
							نام انگليسي :
							</td>
							<td width="25%">
							<input type="text" id="efname" name="efname" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->efname?>">
							</td>
							<td width="25%">
							نام خانوادگي انگليسي :
							</td>
							<td width="25%">
							<input type="text" id="elname" name="elname" class="x-form-text x-form-field" style="width: 150px"
								   value="<?= $obj->elname?>">
							</td>
						</tr>

						<tr>
							<td width="25%">
							نام پدر :
							</td>
							<td width="25%">
							<input type="text" id="father_name" name="father_name" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->father_name?>" >
							</td>
							<td width="25%">
							شماره شناسنامه :
							</td>
							<td width="25%">
							<input type="text" id="idcard_no" name="idcard_no" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->idcard_no?>" >
							</td>
						</tr>
						<tr>
			<td>
			<input type="hidden" name="MAX_FILE_SIZE" value="50000" /> 
			 مسیر فایل عکس: 
			</td>
			<td>
				<input type="file" name="ProfPhoto" />
			</td>
		</tr>
						<tr>
							<td width="25%">
							سريال شناسنامه :
							</td>
							<td width="75%" colspan="3">

							<input type="text" id="idcard_serial" name="idcard_serial" class="x-form-text x-form-field" style="width: 130px"
								   value="<?= $obj->idcard_serial?>" >
							&nbsp;&nbsp;&nbsp;
							الف-21-895454
							</td>
						</tr>

						<tr>
							<td width="25%">
							تاريخ تولد :
							</td>
							<td width="25%">
							<input type="text" id="birth_date" name="birth_date" class="x-form-text x-form-field" style="width: 80px"
								   value="<?= DateModules::Miladi_to_Shamsi($obj->birth_date) ?>" >
							</td>
							<td width="25%">
							تاريخ صدور شناسنامه :
							</td>
							<td width="25%">
							<input type="text" id="issue_date" name="issue_date" class="x-form-text x-form-field" style="width: 80px"
								   value="<?= DateModules::Miladi_to_Shamsi($obj->issue_date) ?>">
							</td>
						</tr>
						<tr>
							<td width="25%">
							محل تولد :
							</td>
							<td width="25%">
								<input type="text" id="birth_state_id" >
							</td>
							<td><input type="text" id="birth_city_id" >
							</td>
							<td></td>
						</tr>
					
						<tr>
							<td width="25%">
							محل صدور شناسنامه :
							</td>
							<td width="25%">
								<input type="text" id="issue_state_id">
							</td>
							<td><input type="text" id="issue_city_id">
							</td>
							<td></td>
						</tr>

						<tr>
							<td width="25%">
							کشور متبوع :
							</td>
							<td width="25%"><?= $drp_issue_countries ?></td>
							<td width="25%">
							كد ملي :
							</td>
							<td width="25%">
							<input type="text" id="national_code" name="national_code" class="x-form-text x-form-field" style="width: 150px"
								   value="<?= $obj->national_code ?>">
							</td>
						</tr>

						<tr>
							<td width="15%" height="21px">
							جنسيت :
							</td>
							<td width="85%" colspan="3">
							<input type="radio" id="sex" name="sex" value="1" <?= ($obj->sex == "1") ? "checked" : "" ?>
											  style="width:3%" size="6" >مرد &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="radio" id="sex" name="sex" value="2"  <?= ($obj->sex == "2") ? "checked" : "" ?>
											   style="width:3%" size="5">زن
							</td>
						</tr>

						<tr>
							<td width="15%"  height="21px" >
							وضعيت تاهل :
							</td>
							<td width="75%" colspan="3" >
										<input type="radio" id="marital_status" name="marital_status" value="1" <?= ($obj->marital_status == "1") ? "checked" : "" ?>
															style="width:3%" size="5">مجرد &nbsp;&nbsp;&nbsp;
							&nbsp;&nbsp;<input type="radio" id="marital_status" name="marital_status" value="2" <?= ($obj->marital_status == "2") ? "checked" : "" ?>
															style="width:3%" size="5">متاهل-داراي همسر &nbsp;&nbsp;
							&nbsp;&nbsp;<input type="radio" id="marital_status" name="marital_status" value="3" <?= ($obj->marital_status == "3") ? "checked" : "" ?>
															style="width:3%" size="10">متاهل-طلاق گرفته &nbsp;
							&nbsp;&nbsp;<input type="radio" id="marital_status" name="marital_status" value="4" <?= ($obj->marital_status == "4") ? "checked" : "" ?>
															style="width:3%" size="10">متاهل-همسر فوت شده
							</td>
						</tr>

                         <tr>
                            <td width="25%">
                                &#1578;&#1593;&#1583;&#1575;&#1583; &#1601;&#1585;&#1586;&#1606;&#1583; :
                            </td>
                            <td width="25%" >
                                <input type="text" id="childCount" name="childCount" class="x-form-text x-form-field" style="width: 100px"
                                       value="<?= $obj->childCount ?>">
                            </td>


                            <td width="25%">
                                &#1578;&#1575;&#1585;&#1740;&#1582; &#1575;&#1587;&#1578;&#1582;&#1583;&#1575;&#1605; :
                            </td>
                            <td width="25%">
                                <input type="text" id="employ_date" name="employ_date" class="x-form-text x-form-field" style="width: 80px"
                                       value="<?= DateModules::Miladi_to_Shamsi($obj->employ_date) ?>">
                            </td>
                        </tr>

						<tr>
							<td width="25%" height="21px" >
							سرپرست خانواده ؟
							</td>
							<td width="25%">  <input type="radio" id="family_protector" name="family_protector" value="1"
								<?= ($obj->family_protector == "1") ? "checked" : "" ?> style="width:10%">بلی &nbsp;&nbsp;&nbsp;
								&nbsp;&nbsp;&nbsp;<input type="radio" id="family_protector" name="family_protector" value="0"
								<?= ($obj->family_protector == "0") ? "checked" : "" ?> style="width:10%">خیر
							</td>
							<td width="25%">
							نوع سكونت خانواده :
							</td>
							<td width="25%">  <input type="radio" id="locality_type" name="locality_type" value="0"
											  <?= ($obj->locality_type == "0") ? "checked" : "" ?> style="width:10%" size="10">بومي &nbsp;&nbsp;&nbsp;
							&nbsp;&nbsp;&nbsp;<input type="radio" id="locality_type" name="locality_type" value="1"
											  <?= ($obj->locality_type == "1") ? "checked" : "" ?> style="width:10%" size="10">غير بومي
							</td>
						</tr>

						<tr>
							<td width="25%">
							آدرس :
							</td>
							<td width="75%" colspan="3">
							<input type="text" id="address1" name="address1" class="x-form-text x-form-field" style="width: 99%"
								   value="<?= $obj->address1 ?>">
							</td>
						</tr>

						<tr>
							<td width="25%">
							كد پستي :
							</td>
							<td width="25%">
							<input type="text" id="postal_code1" name="postal_code1" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->postal_code1 ?>">
							</td>
							<td width="25%">
							تلفن منزل  :
							</td>
							<td width="25%">
							<input type="text" id="home_phone1" name="home_phone1" class="x-form-text x-form-field" style="width: 150px"
									value="<?= $obj->home_phone1 ?>">
							</td>
						</tr>
										
						<tr>
							<td width="25%">
							تلفن همراه :
							</td>
							<td width="25%">
							<input type="text" id="mobile_phone" name="mobile_phone" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->mobile_phone ?>">
							</td>
							<td width="25%">
							آدرس پست الکترونيکي :
							</td>
							<td width="25%">
							<input type="text" id="email" name="email" class="x-form-text x-form-field" style="width: 150px"
								   value="<?= $obj->email ?>">
							</td>
						</tr>

						<tr>
							<td width="25%">
							مليت :
							</td>
							<td width="20%"><?= $drp_nationality ?></td>
						</tr>

						<tr>
							<td width="25%">
							دين :
							</td>
							<td width="25%" ><input type="text" id="religion" ></td>
							<td width="25%">
							مذهب :
							</td>
							<td width="25%"><input type="text" id="subreligion" ></td>
						</tr>

						<tr>
							<td width="25%">
							شماره بيمه :
							</td>
							<td width="25%">
							<input type="text" id="insure_no" name="insure_no" class="x-form-text x-form-field" style="width: 100px"
								   value="<?= $obj->insure_no ?>">
							</td>
						<td width="25%">
							کارگاه: 
							</td>
							<td width="20%"><?= $drp_detective ?></td>
						</tr>
						<tr>
							<td width="40%">
					پست بیمه تامین اجتماعی:
							</td>
							<td width="30%">
							<input type="text" id="InsurePost" name="InsurePost" class="x-form-text x-form-field" style="width: 200px"
								   value="<?= $obj->InsurePost ?>">
							</td>
							<td width="10%">
				کد شغل:
							</td>
							<td width="20%">
							<input type="text" id="JobCode" name="JobCode" class="x-form-text x-form-field" style="width: 80px"
								   value="<?= $obj->JobCode ?>">
							</td>
						</tr>
                                                <tr>
							<td width="25%">
							بانک :
							</td>
							<td width="25%" ><?= $drp_banks ?></td>
                                                        <td width="25%">
							شماره حساب :
							</td>
							<td width="25%" >
							<input type="text" id="account_no" name="account_no" class="x-form-text x-form-field" style="width: 200px"
								   value="<?= $staffInfo->account_no ?>">
							</td>
						</tr>
						<tr>
							<td width="25%">
							وضعيت نظام وظيفه :
							</td>
							<td width="25%"><input type="text" id="military_status" ></td>
							<td width="25%"><input type="text" id="military_type" ></td>
						</tr>
						<tr>
							<td width="25%">
							شروع خدمت وظيفه :
							</td>
							<td width="25%">
							<input type="text" id="military_from_date" name="military_from_date" class="x-form-text x-form-field" style="width: 80px"
								   value="<?= DateModules::Miladi_to_Shamsi($obj->military_from_date) ?>">
							</td>
							<td width="25%">
							پايان خدمت وظيفه :
							</td>
							<td width="25%">
							<input type="text" id="military_to_date" name="military_to_date" class="x-form-text x-form-field" style="width: 80px"
								   value="<?= DateModules::Miladi_to_Shamsi($obj->military_to_date) ?>">
							</td>
						</tr>

						<tr>
							<td width="25%">
			مدت خدمت :
							</td>
							
							<td width="25%">
							    <table><tr><td>
							<input type="text" id="military_duration" name="military_duration" class="x-form-text x-form-field" style="width: 50px"
								   value="<?= $obj->military_duration ?>" >   </td>
								    <td> &nbsp;
							 ماه &nbsp;
								    </td>
								    <td>
							<input type="text" id="military_duration_day" name="military_duration_day" class="x-form-text x-form-field" style="width: 50px"
								   value="<?= $obj->military_duration_day ?>">    </td>
									<td>&nbsp;
									    روز &nbsp;
									</td> </tr></table>
							</td>
							<td width="25%">
							توضيحات خدمت وظيفه :
							</td>
							<td width="25%">
							<input type="text" id="military_comment" name="military_comment" class="x-form-text x-form-field" style="width: 150px"
								   value="<?= $obj->military_comment ?>">
							</td>
						</tr>
						<tr><td>توضیحات:</td>
							<td colspan="3"><textarea id="comment" name="comment" class="x-form-field" style="width:98%"
								rows="5"><?= $obj->comment ?></textarea></td>
						</tr>						
						<tr><td></td><td colspan="3" ><br><hr  width="500px" style="color: #99BBE8 "></td></tr>						
						<tr>
							<td></td>
							<td colspan="3" >
							<input type="button" id="person_save_btn"  class="button" onclick="PersonObject.saveAction();" value="ذخیره">
							</td>
						</tr>
					</table>
				</div>
			</form>
			
			<br>
				
				<div id="includeHistoryGRID"></div>
				
		</div>
	</div>
</div>


