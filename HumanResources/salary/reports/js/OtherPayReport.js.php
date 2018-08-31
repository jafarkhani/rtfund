<script type="text/javascript">
	//---------------------------
	// programmer:	Mahdipour
	// Date:		92.11
	//---------------------------

	Mission.prototype = {
		TabID : '<?= $_REQUEST["ExtTabID"] ?>',
		address_prefix : "<?= $js_prefix_address ?>",
		//mainPanel : "",    	
		get : function(elementID){
			return findChild(this.TabID, elementID);
		}
	};
	Mission.prototype.showReport = function(type)
	{ 	
		this.form = this.get("mainForm")
		this.form.target = "_blank";
		this.form.method = "POST";
		this.form.action =  this.address_prefix + "OtherPayReport.php?showRes=1";
		this.form.action += type == "excel" ? "&excel=true" : "";	
		this.form.submit();	
		return;
	}

	function Mission()
	{
		var types = Ext.create('Ext.data.ArrayStore', {
			fields: ['val', 'title'],
			data : [
				['1','هیئت علمی'],                               
				['2','کارمند'],                               
				['3','روزمزدبیمه ای'],                               
				['5','قراردادی'],                               
				['102','هیئت علمی،کارمند،روزمزد'], 
                                  
			]
		});                             
       
		this.formPanel = new Ext.form.Panel({
			applyTo: this.get("mainpanel"),
			layout: {
				type:"table",
				columns:2
			},
			collapsible: false,
			frame: true,
			title:'گزارش پرداخت ماموریت',
			bodyPadding: '5 5 0',
			width:580,
			fieldDefaults: {
				msgTarget: 'side',
				labelWidth: 80	 
			},
			defaultType: 'textfield',
			items: [{
					xtype:"numberfield" ,
					fieldLabel: 'سال',
					name: 'pay_year',
					itemId: "pay_year",
					width:200,
					hideTrigger:true
				},
				{
					xtype:"numberfield" ,
					fieldLabel: 'ماه',
					name: 'pay_month', 
					itemId: "pay_month",
					width:200,
					hideTrigger:true
				},
				{
					xtype : "combo",
					store :  new Ext.data.Store({
						fields : ["InfoID","InfoDesc"],
						proxy : {
							type: 'jsonp',
							url : this.address_prefix + "../../../global/domain.data.php?task=searchPayType",
							reader: {
								root: 'rows',
								totalProperty: 'totalCount'
							}
						}
					}),
					valueField : "InfoID",
					displayField : "InfoDesc",
					hiddenName : "PayType",
					fieldLabel : "نوع پرداخت",
					allowBlank : false,
                    colspan:2,
					listConfig: {
						loadingText: 'در حال جستجو...',
						emptyText: 'فاقد اطلاعات',
						itemCls : "search-item"
					},
					listeners: {
						select: function (combo, records) {						
							
						}
					},
					width:300
				} 
			] , 
			buttons: [{
					text : "مشاهده گزارش",                                           
					iconCls : "report",
					handler: function(){MissionObject.showReport('show');}
				},{
					text : "خروجی Excel",
					iconCls : "excel",
					handler : function(){MissionObject.showReport('excel');}
				}]
		});
	
	}

	var MissionObject = new Mission() ; 


</script>