<?php

define("SYSTEMID_framework", 1);
define("SYSTEMID_accounting", 2);
define("SYSTEMID_office", 4);
define("SYSTEMID_loan", 6);
define("SYSTEMID_dms", 7);
define("SYSTEMID_plan", 9);
define("SYSTEMID_hrms", 10);
define("SYSTEMID_attendance", 11);
define("SYSTEMID_contract", 12);
define("SYSTEMID_portal", 1000);

define("MENUID_portal", 1000);
define("MENUID_loans", 16);
define("MENUID_persons", 6);

//.................................................

define("BRANCH_BASE", "3");
define("BRANCH_UM", "3");
define("BRANCH_PARK", "4");

define("Default_Agent_Loan", "9");
define("Default_BranchID", "3"); 

//................. TypeID = 9 .....................
define("DOCTYPE_STARTCYCLE", "1");
define("DOCTYPE_NORMAL", "2");
define("DOCTYPE_ENDCYCLE", "3");
define("DOCTYPE_CLOSECYCLE", "25");
define("DOCTYPE_LOAN_PAYMENT", "4");
define("DOCTYPE_INSTALLMENT_PAYMENT", "5");
define("DOCTYPE_END_REQUEST", "6");
define("DOCTYPE_DEPOSIT_PROFIT", "7");
define("DOCTYPE_DOCUMENT", "8");
define("DOCTYPE_INSTALLMENT_CHEQUE", "9");
define("DOCTYPE_SHARE_PROFIT", "10");
define("DOCTYPE_SAVING_IN", "11");
define("DOCTYPE_SAVING_OUT", "12");
define("DOCTYPE_WARRENTY", "13");
define("DOCTYPE_EQUALCHECKS", "14");
define("DOCTYPE_INCOMERCHEQUE", "15");
define("DOCTYPE_LOAN_DIFFERENCE", "16");
define("DOCTYPE_LOAN_COST", "17");
define("DOCTYPE_WARRENTY_END", "18");
define("DOCTYPE_WARRENTY_EXTEND", "19");
define("DOCTYPE_INSTALLMENT_CHANGE", "20");
define("DOCTYPE_WARRENTY_CANCEL", "21");
define("DOCTYPE_SALARY", "22");
define("DOCTYPE_SALARY_PAY", "24");
define("DOCTYPE_EDITINCOMECHEQUE", "23");
define("DOCTYPE_EXECUTE_EVENT", "26");

//............  TypeID=2 ....................
define("TAFTYPE_YEARS", "2");
define("TAFTYPE_ACCOUNTS", "3");
define("TAFTYPE_SUBAGENT", "4");
define("TAFTYPE_BANKS", "6");
define("TAFTYPE_ChequeStatus", "7");

define("TAFSILITYPE_PERSON", "200");
define("TAFSILITYPE_LOAN", "130");
define("TAFSILITYPE_PROCESS", "150");
define("TAFSILITYPE_BRANCH", "300");
define("TAFSILITYPE_SOURCE", "320");

//...............................

define("COSTID_ShortDeposite", "66"); // 210-01
define("COSTID_Todiee", "63");
define("COSTID_LongDeposite", "119");
define("COSTID_SupportDeposite", "487");
define("COSTID_Fund", "1");
define("COSTID_Wage", "470"); // 550
define("COSTID_DepositeWage", "418"); // 750-09
define("COSTID_Bank", "253");
define("COSTID_share", "313"); 
define("COSTID_ShareProfit", "167");
define("COSTID_Commitment", "165");
define("COSTID_saving", "1114");
define("COSTID_current", "202");
define("COSTID_BRANCH_PARK", "17"); // 499 park branch
define("COSTID_BRANCH_UM", "205"); // 900 um branch
define("COSTID_DepositeProfit", "416");
define("COSTID_DepositeProfitSupport", "768");
define("COSTID_GetDelay", "444");

define("ShareBaseAmount", "1100000");

define("ACCROLE_EXPERT", "1");
define("ACCROLE_MANAGER", "2");

//.............. TypeID=4 ................
define("INCOMECHEQUE_NOTVOSUL", "3001");
define("INCOMECHEQUE_FLOW_VOSUL", "3002");
define("INCOMECHEQUE_VOSUL", "3003");
define("INCOMECHEQUE_BARGASHTI", "3004");
define("INCOMECHEQUE_CHANGE", "3011");
define("INCOMECHEQUE_SANDOGHAMANAT", "3012");
define("INCOMECHEQUE_BARGASHTI_HOGHUGHI", "3013");
define("INCOMECHEQUE_MOSTARAD", "3008");
define("INCOMECHEQUE_BARGASHTI_MOSTARAD", "3009");
define("INCOMECHEQUE_EDIT", "3333");

//............  TypeID=6 ....................
define("BACKPAY_PAYTYPE_SUBSALARY", "3");
define("BACKPAY_PAYTYPE_EPAY", "4");
define("BACKPAY_PAYTYPE_CHEQUE", "9");
define("BACKPAY_PAYTYPE_CORRECT", "100");
//.............. Loan Statuses TypeID=5 ..............
define("LON_REQ_STATUS_RAW", "1");
define("LON_REQ_STATUS_SEND", "10");
define("LON_REQ_STATUS_RETURN", "11");
define("LON_REQ_STATUS_REJECT", "20");
define("LON_REQ_STATUS_PRECONFIRM", "30");
define("LON_REQ_STATUS_RETURN_CUSTOMER", "35");
define("LON_REQ_STATUS_SEND_CUSTOMER", "40");
define("LON_REQ_STATUS_CUSTOMER_COMPLETE", "50");
define("LON_REQ_STATUS_REJECT_ATTACH", "60");
define("LON_REQ_STATUS_CONFIRM", "70");
define("LON_REQ_STATUS_DEFRAY", "95");
define("LON_REQ_STATUS_ENDED", "101");

//............  TypeID=11 ....................
define("SOURCETYPE_LOAN", "1");
define("SOURCETYPE_CONTRACT", "2");
define("SOURCETYPE_PLAN", "3");
define("SOURCETYPE_WARRENTY", "4");
define("SOURCETYPE_FORM", "5");

define("BLOCKID_LOAN","8");

//------------- flowID baseinfo typeID=11 ---------------
define("FLOWID_LOAN","1");
define("FLOWID_WFM_FORM","5");
define("FLOWID_WARRENTY", "4");
define("FLOWID_CONTRACT","2");
define("FLOWID_ACCDOC","8");

define("FLOWID_TRAFFIC_CORRECT","9");
define("FLOWID_TRAFFIC_DayOFF","10");
define("FLOWID_TRAFFIC_OFF","11");
define("FLOWID_TRAFFIC_DayMISSION","12");
define("FLOWID_TRAFFIC_MISSION","13");
define("FLOWID_TRAFFIC_EXTRA","14");
define("FLOWID_TRAFFIC_CHANGE_SHIFT","15");
//-------------------------------------------------------

define("WAR_STEPID_RAW", "100");
define("WAR_STEPID_CONFIRM", "110");
define("WAR_STEPID_END", "120");
define("WAR_STEPID_CANCEL", "130");
define("WAR_STEPID_REDUCE", "140");

define("CNT_STEPID_RAW", "100");
define("CNT_STEPID_CONFIRM", "110");

define("DMS_DOCTYPE_LETTER", "63");
define("DMS_DOCTYPE_Documents", "131");

//-------------- office ---------------
define("OFC_ACCESSTYPE_NORMAL", "1");
define("OFC_ACCESSTYPE_SECRET", "2");

define("OFC_ROLE_SECRET", "1");
 
//------------ attendance ------------
define("ATN_STEPID_RAW", "100");
define("ATN_STEPID_CONFIRM", "200");

//------------ meeting ------------
define("MTG_STATUSID_RAW", "1");
define("MTG_STATUSID_DONE", "2");
define("MTG_STATUSID_CANCLE", "3");

//------------ acc doc ------------
define("ACC_STEPID_RAW", "100");
define("ACC_STEPID_CONFIRM", "200");

define("ACC_COST_PARAM_LOAN_RequestID", "3");
define("ACC_COST_PARAM_LOAN_LastInstallmentDate", "4");
define("ACC_COST_PARAM_LOAN_LEVEL", "105");
define("ACC_COST_PARAM_CHEQUE_date", "95");
define("ACC_COST_PARAM_BANK", "1");


define("DEFRAYLOAN_VOTEFORM", "9");
define("DEFRAYLOAN_WFMFORM", "16");

//---------- typeID=95 ------------
define("STO_STEPID_RAW", "1");
define("STO_STEPID_DEPRICATE", "4");
//------------ events ---------------
define("EVENT_LOAN_ALLOCATE", 131);

define("EVENT_LOANCONTRACT_innerSource", 132);
define("EVENT_LOANCONTRACT_agentSource_committal", 134);
define("EVENT_LOANCONTRACT_agentSource_non_committal", 133);

define("EVENT_LOANPAYMENT_innerSource", 141);
define("EVENT_LOANPAYMENT_agentSource", 143);

define("EVENT_LOANBACKPAY_innerSource_cheque", 151);
define("EVENT_LOANBACKPAY_innerSource_non_cheque", 152);
define("EVENT_LOANBACKPAY_agentSource_committal_cheque", 155);
define("EVENT_LOANBACKPAY_agentSource_committal_non_cheque", 156);
define("EVENT_LOANBACKPAY_agentSource_non_committal_cheque", 153);
define("EVENT_LOANBACKPAY_agentSource_non_committal_non_cheque", 154);

define("EVENT_LOAN_COST_INNER", 1866);
define("EVENT_LOAN_COST_AGENT", 1867);

define("EVENT_LOANDAILY_innerSource", 161);
define("EVENT_LOANDAILY_agentSource_committal", 1722);
define("EVENT_LOANDAILY_agentSource_non_committal", 1723);
define("EVENT_LOANDAILY_innerLate", 1724);
define("EVENT_LOANDAILY_agentlate", 1725);  
define("EVENT_LOANDAILY_innerPenalty", 1726);
define("EVENT_LOANDAILY_agentPenalty", 1727);

define("EVENT_LOAN_CHANGE", 171);
define("EVENT_LOAN_END", 191);

define("EVENT_LOANCHEQUE_payed", 1755);
define("EVENT_LOANCHEQUE_innerSource", 1766);
define("EVENT_LOANCHEQUE_agentSource", 1772);

define("EVENT_CHEQUE_SANDOGHAMANAT_inner", 1768);
define("EVENT_CHEQUE_SANDOGHAMANAT_agent", 1773);
define("EVENT_CHEQUE_SENDTOBANK_inner", 1769);
define("EVENT_CHEQUE_SENDTOBANK_agent", 1774);
define("EVENT_CHEQUE_SENDTOBANKFROMAMANAT_inner", 1778);
define("EVENT_CHEQUE_SENDTOBANKFROMAMANAT_agent", 1777);
define("EVENT_CHEQUE_BARGASHT_inner", 1770);
define("EVENT_CHEQUE_BARGASHT_agent", 1775);
define("EVENT_CHEQUE_BARGASHTHOGHUGHI_inner", 1771);
define("EVENT_CHEQUE_BARGASHTHOGHUGHI_agent", 1776);
define("EVENT_CHEQUE_MOSTARAD_inner", 1781);
define("EVENT_CHEQUE_MOSTARAD_agent", 1779);
define("EVENT_CHEQUE_BARGASHTMOSTARAD_inner", 1782);
define("EVENT_CHEQUE_BARGASHTMOSTARAD_agent", 1780);
define("EVENT_CHEQUE_RETURNBANK_inner", 1868);
define("EVENT_CHEQUE_RETURNBANK_agent", 1871);
define("EVENT_CHEQUE_BARGHASHTITOVOSUL_inner", 1868);
define("EVENT_CHEQUE_BARGHASHTITOVOSUL_agent", 1871);

define("EVENT_WAR_REG_2", 1797);
define("EVENT_WAR_REG_3", 1809);
define("EVENT_WAR_REG_4", 1810);
define("EVENT_WAR_REG_6", 1811);
define("EVENT_WAR_REG_7", 1812);
define("EVENT_WAR_REG_other", 1813);

define("EVENT_WAR_CANCEL_2", 1814);
define("EVENT_WAR_CANCEL_3", 1815);
define("EVENT_WAR_CANCEL_4", 1816);
define("EVENT_WAR_CANCEL_6", 1817);
define("EVENT_WAR_CANCEL_7", 1818);
define("EVENT_WAR_CANCEL_other", 1819);

define("EVENT_WAR_END_2", 1820);
define("EVENT_WAR_END_3", 1821);
define("EVENT_WAR_END_4", 1822);
define("EVENT_WAR_END_6", 1823);
define("EVENT_WAR_END_7", 1824);
define("EVENT_WAR_END_other", 1825);

define("EVENT_WAR_EXTEND_2", 1839);
define("EVENT_WAR_EXTEND_3", 1840);
define("EVENT_WAR_EXTEND_4", 1841);
define("EVENT_WAR_EXTEND_6", 1842);
define("EVENT_WAR_EXTEND_7", 1843);
define("EVENT_WAR_EXTEND_other", 1844);

define("EVENT_WAR_SUB_2", 1826);
define("EVENT_WAR_SUB_3", 1827);
define("EVENT_WAR_SUB_4", 1828);
define("EVENT_WAR_SUB_6", 1829);
define("EVENT_WAR_SUB_7", 1830);
define("EVENT_WAR_SUB_other", 1831);

//------------- CostCode params -----------
define("CostCode_param_loan", "3");
//------------- TypeIDs -----------
define("TYPEID_MeetingType", 88);
define("TYPEID_MeetingStatusID", 89);

define("POSTID_MODIRAMEL", 77);

//------------ Processes ----------
define("PROCESS_REGISTRATION", "1030102");

define("FILE_FRAMEWORK_PICS",getenv("DOCUMENT_ROOT") . "/storage/framework/");

function BeginReport() {

        echo '<html>
			<head>
				<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
				<META http-equiv=Content-Type content="text/html; charset=UTF-8" >' .
			'</head>
			<body dir="rtl">';
    }

?>
