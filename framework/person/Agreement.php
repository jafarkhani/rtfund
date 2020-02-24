<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.11
//-----------------------------
require_once("../../header.inc.php");
require_once './persons.class.php';

$PersonID = session::IsPortal() ? $_SESSION["USER"]["PersonID"] : $_REQUEST["PersonID"];

$personObj = new BSC_persons($PersonID);
?>
<script type="text/javascript">

BSC_Agreement.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function BSC_Agreement()
{
	this.MainForm = new Ext.form.Panel({
		renderTo : this.get("mainForm"),
		border : false,
		width : 730,
		items : [{
			xtype : "container",
			hidden : <?= $personObj->IsReal == "YES" ? "false" : "true" ?>,
			contentEl : this.get("div_real")
		},{
			xtype : "container",
			hidden : <?= $personObj->IsReal == "YES" ? "true" : "false" ?>,
			contentEl : this.get("div_not_real")
		},{
			xtype : "button",
			itemId : "btn",
			style : "margin-top:20px",
			hidden : <?= $personObj->Agreement == "YES" || session::IsFramework()  ? "true" : "false" ?>,
			text : "موارد فوق مورد تایید می باشد",
			border : true,
			iconCls : "tick",
			handler : function(){ BSC_AgreementObject.Confirm();}
		},{
			xtype : "container",
			itemId : "confirm",
			hidden : <?= $personObj->Agreement == "YES" ? "false" : "true" ?>,
			cls : "blueText",
			html : "<br><br>" + "موارد فوق توسط مشتری تایید شده است"
		}]
	});
}

var BSC_AgreementObject = new BSC_Agreement();

BSC_Agreement.prototype.Confirm = function()
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainForm.getForm().submit({
		url: this.address_prefix +'persons.data.php?task=confirmAgreement',
		method: 'POST',

		success: function(form,action){
			mask.hide();
			BSC_AgreementObject.MainForm.down("[itemId=btn]").hide();
			BSC_AgreementObject.MainForm.down("[itemId=confirm]").show();			
		},
		failure: function(){
			mask.hide();
		}
	});
}

</script>
<center>
	<div id="mainForm"></div>
	<div id="div_real" style=" text-align: justify; text-justify: inter-word;">اینجانب .
		<b><?= $personObj->_fullname ?></b><br><br>
		بدین‌وسیله تعهد می‌نمایم که کلیه اطلاعات ارائه‌شده واقعی،
		کامل و صحیح می‌باشد و اطلاع دارم که ممکن است از آن در جهت تعیین 
		اهلیت اعتباری و اعتبارسنجی اینجانب استفاده شود.
		<br>
		به‌همین منظور به صندوق پژوهش و فناوری خراسان رضوی اجازه می‌دهم تا اطلاعات اینجانب را که توسط سامانه اعتبار سنجی از منابع مجاز گردآوری‌شده، به‌صورت مستمر از سامانه مذکور درخواست و دریافت نموده و از آن جهت اعتبارسنجی اینجانب استفاده کند.
		<br>
		اطلاعات اینجانب را به‌صورت مستمر به شرکت اعتبارسنجی ارسال نماید، تا از این طریق زمینه لازم جهت اعطای تسهیلات را فراهم نموده، از کامل بودن اطلاعات اطمینان حاصل نماید و جامعیت سیستم اعطای تسهیلات را حفظ کند.
		<br>
		 همچنین اجازه توزیع و پردازش اطلاعات خود را به تمامی اعضاء سامانه اعتبارسنجی داده و حق هرگونه دادخواهی، اعلام دعوی و مطالبه خسارت و صدمه ناشی از استفاده و پردازش اطلاعات سامانه اعتبارسنجی شرکت به استفاده‌کنندگان مجاز از سوی بانک مرکزی جمهوری اسلامی ایران را از خود سلب نمایم.
		 
		 <br><br>
		 
	لطفا رضایت‌نامه زیر را جهت دریافت گزارش از شرکت‌ اعتبارسنجی پس از مطالعه تایید نمایید.
	</div>
	<div id="div_not_real"  style=" text-align: justify;  text-justify: inter-word;">
 اینجانب دارنده امضاء مجاز از (شرکت- موسسه- سازمان) 
 <b><?= $personObj->_fullname ?></b><br><br>
 بدین‌وسیله اعلام می‌دارم که کلیه اطلاعات ارائه‌شده واقعی، کامل و صحیح می‌باشد و اطلاع دارم که ممکن است از آن در جهت تعیین اهلیت اعتباری و اعتبارسنجی
 (شرکت- موسسه- سازمان) استفاده شود. به‌همین منظور
 به صندوق پژوهش و فناوری خراسان رضوی اجازه می‌دهم تا: اطلاعات (شرکت- موسسه- سازمان) را که در مقابل توسط سامانه اعتبارسنجی از منابع مجاز گردآور
 ی‌شده، به‌صورت مستمر از سامانه مذکور درخواست و دریافت نموده و از آن جهت اعتبارسنجی (شرکت- موسسه- سازمان)
 استفاده کند. اطلاعات (شرکت- موسسه- سازمان) را بصورت مستمر به شرکت اعتبارسنجی ارسال نماید، تا از این طریق زمینه لازم جهت اعطای تسهیلات را فراهم نموده، از کامل بودن اطلاعات اطمینان حاصل نماید و جامعیت سیستم اعطای تسهیلات را حفظ کند. همچنین
 اجازه توزیع و پردازش اطلاعات (شرکت- موسسه- سازمان) را به تمامی اعضاء سامانه اعتبارسنجی داده و
 حق هرگونه دادخواهی، اعلام دعوی و مطالبه خسارت و صدمه ناشی از استفاده و پردازش اطلاعات سامانه اعتبارسنجی شرکت به استفاده¬کنندگان مجاز از سوی بانک مرکزی جمهوری اسلامی ایران را از (شرکت- موسسه- سازمان) سلب نمایم.

 <br><br>
	لطفا رضایت‌نامه زیر را جهت دریافت گزارش از شرکت‌ اعتبارسنجی پس از مطالعه تایید نمایید.
	</div>
	
</center>