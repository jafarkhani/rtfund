<?php

/*
update ACC_docs join ACC_DocItems using(DocID) join LON_payments on(SourceID3=PayID) 
set DebtorAmount = if(DebtorAmount>0, PayAmount-OldFundWage-OldAgentWage, 0),
CreditorAmount = if(CreditorAmount>0, PayAmount-OldFundWage-OldAgentWage, 0)
where EventID in(141,143)
 * 
 *  */

/*
insert into aa select DocID,@i:=@i+1 from (select a.* from ACC_docs a, 
 * (select @i:=0)t where cycleID=1398 AND DocDate>1 order by DocDate)t 

 * update aa join ACC_docs using(DocID) set LocalNo=no 

 * update ACC_DocItems join ACC_docs using(DocID) join LON_BackPays b on(IncomeChequeID=SourceID2)
join LON_ReqParts p on(IsHistory='NO' AND b.RequestID=p.RequestID) set DocDate=if(PartDate>'2019-03-21',PartDate,'2019-03-21') where EventID=1766
 * 
 *  */

/*
select max(DocDate),g2j(max(DocDate)) from ACC_docs where EventID in(161,1722,1723,1724,1725,1726,1727) and DocDate<'2019-10-22'
select DocID from ACC_docs where EventID in(161,1722,1723,1724,1725,1726,1727) group by EventID,DocDate having count(DocID)>1
 *  */

/*
insert into STO_AssetFlow(AssetID,ActDate,ActPersonID,StatusID,IsUsable,ReceiverPersonID) 
select RowID,now(),1000,2,'YES', case `col 5` when 'اشرفی' then 2313
when 'آخوند زاده' then 2537 
when 'ابراهیمی' then 1947
when 'جنتی' then 2265
when 'حیدری' then 2171
when 'خادمی' then 2276
when 'دزیانی' then 2562
when 'رجبی' then 2606
when 'سیدیان' then 1108
when 'کوثر' then 2560
when 'محبی' then 2550
when 'مختاری' then 2633
when 'مدیر عامل' then 2161 end
 from `TABLE 225`


insert into STO_AssetFlow(AssetID,ActDate,ActPersonID,StatusID,IsUsable,ReceiverPersonID) 
select AssetID,BuyDate,1000,1,'YES',0 from STO_assets


insert into STO_AssetFlow(AssetID,ActDate,ActPersonID,StatusID,IsUsable,DepreciationAmount,details) 
select RowID,'2016-03-19',1000,4,'YES', `col 18`, 'استهلاک سالهای قبل'
 from `TABLE 225` where `col 18`<>0


update STO_AssetFlow set IsLock='YES'

ALTER TABLE `STO_AssetFlow` 
ADD COLUMN `IsLock` ENUM('YES','NO') NULL DEFAULT 'NO' AFTER `IsActive`;

 */
/*
select  
si.ItemID,
b0.blockCode,b0.blockDesc,
b1.blockCode,b1.blockDesc,
b2.blockCode,b2.blockDesc,
b3.blockCode,b3.blockDesc,

bi.InfoDesc TafsiliGroupDesc,t.TafsiliDesc,
bi2.InfoDesc Tafsili2GroupDesc,t2.TafsiliDesc as Tafsili2Desc,
bi3.InfoDesc Tafsili3GroupDesc,t3.TafsiliDesc as Tafsili3Desc,

p1.paramDesc paramDesc1,si.param1,
p2.paramDesc paramDesc2,si.param2,
p3.paramDesc paramDesc3,si.param3

		from ACC_DocItems si
			join ACC_CostCodes cc using(CostID)
            
			join ACC_blocks b1 on(cc.level1=b1.blockID)
            join ACC_blocks b0 on(b1.GroupID=b0.blockID)
			join ACC_blocks b2 on(cc.level2=b2.blockID)
			join ACC_blocks b3 on(cc.level3=b3.blockID)
			
			left join BaseInfo bi on(si.TafsiliType=InfoID AND TypeID=2)
			left join BaseInfo bi2 on(si.TafsiliType2=bi2.InfoID AND bi2.TypeID=2)
			left join BaseInfo bi3 on(si.TafsiliType3=bi3.InfoID AND bi3.TypeID=2)
			
			left join ACC_tafsilis t on(t.TafsiliID=si.TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=si.TafsiliID2)
			left join ACC_tafsilis t3 on(t3.TafsiliID=si.TafsiliID3)
			
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)
            
            where DocID=9212

 */
	
/*	
insert into ACC_tafsilis(TafsiliCode,TafsiliType,TafsiliDesc,ObjectID) 
		select PersonID,200,concat_ws(' ',fname,lname,CompanyName),PersonID from BSC_persons

insert into ACC_tafsilis(TafsiliCode,TafsiliType,TafsiliDesc,ObjectID) 
select AccountID,200,concat(BankDesc,' - ',AccountDesc),AccountID
from ACC_accounts join ACC_banks using(BankID)

insert into ACC_tafsilis(TafsiliCode,TafsiliType,TafsiliDesc,ObjectID) 
        select b1.ProcessID,150,b1.ProcessTitle,b1.ProcessID 
 *		from BSC_processes b1 left join BSC_processes b2 on(b2.parentID=b1.ProcessID) where b2.ProcessID is null
 * 

*/

/*
	$StartDate = "1400-01-01"	;
	$toDate = '1500-01-01';
	
	while($StartDate < $toDate)
	{
		PdoDataAccess::runquery("insert into dates values(?,?)", array(
		DateModules::shamsi_to_miladi($StartDate,"-"),$StartDate
		));
		$StartDate = DateModules::AddToJDate($StartDate, 1);
	}
 *  */

ALTER TABLE `framewor_rtfund`.`ACC_ChequeHistory` ADD COLUMN `DocID` INTEGER UNSIGNED DEFAULT 0 AFTER `details`;

ALTER TABLE `framewor_rtfund`.`LON_payments` 
ADD COLUMN `OldFundDelayAmount` DECIMAL(13,0) NOT NULL DEFAULT 0,
ADD COLUMN `OldAgentDelayAmount` DECIMAL(13,0) NOT NULL DEFAULT 0 ;


ALTER TABLE `framewor_rtfund`.`LON_requests` ADD COLUMN `DomainID` INTEGER UNSIGNED NOT NULL DEFAULT 0 COMMENT 'حوزه فعالیت' AFTER `FundRules`;

ALTER TABLE `framewor_rtfund`.`LON_payments` ADD COLUMN `OldFundWage` DECIMAL(13,0) NOT NULL DEFAULT 0 AFTER `OldAgentDelayAmount`,
 ADD COLUMN `OldAgentWage` DECIMAL(13,0) NOT NULL DEFAULT 0 AFTER `OldFundWage`;
