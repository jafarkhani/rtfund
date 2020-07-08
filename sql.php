<?php
/*
insert into aaa(c1,ddate) select r.RequestID,ActDate from LON_requests r join LON_ReqFlow using(RequestID,StatusID) where r.StatusID=95 and ActDate>='2019-03-21'
insert into aaa(c1,ddate) select r.RequestID,EndDate from LON_requests r where r.IsEnded='YES' and EndDate>='2019-03-21'
insert into aaa(c1) select r.RequestID from LON_requests r where r.StatusID=70
 * 
select * from aaa join sajakrrt_rtfund.LON_ReqParts p1 on(p1.IsHistory='NO' AND c1=p1.RequestID) 
 join sajakrrt_oldcomputes.LON_ReqParts p2 on(p2.IsHistory='NO' AND c1=p2.RequestID)
 where p1.partID<>p2.PartID
 * 
 * 
 * ایجاد چک های ردیف های پرداخت
insert into ACC_DocCheques(DociD, AccountID,CheckNo ,CheckDate , amount , CheckStatus,description ,TafsiliID,AccountTafsiliID)
select d1.DocID,c.AccountID,c.CheckNo ,c.CheckDate , c.amount , c.CheckStatus,c.description ,c.TafsiliID,c.AccountTafsiliID from sajakrrt_rtfund.LON_payments p join sajakrrt_rtfund3.LON_PayDocs d2 using(PayID)
        join sajakrrt_rtfund.LON_PayDocs d1 on(p.PayID=d1.PayID)
        join sajakrrt_rtfund3.ACC_DocCheques c on(c.DociD=d2.DocID)
        where PayDate>='2019-03-21'
 * 
 * به روز رسانی ردیف های بانک اسناد پرداخت
 * update sajakrrt_rtfund.LON_payments p join aaa on(requestID=c1)
join sajakrrt_rtfund.LON_PayDocs d1 on(p.PayID=d1.PayID)
join sajakrrt_rtfund.ACC_DocItems di1 on(di1.DocID=d1.DocID and di1.CostID=1107)
join sajakrrt_rtfund3.LON_PayDocs d2 on(p.PayID=d2.PayID)
join sajakrrt_rtfund3.ACC_DocItems di2 on(di2.DocID=d2.DocID and di2.CostID=1107)
set di1.TafsiliID=di2.TafsiliID,di1.TafsiliID2=di2.TafsiliID2,di1.TafsiliID3=di2.TafsiliID3,
di1.param1=di2.param1,di1.param2=di2.param2,di1.param3=di2.param3
where p.PayDate>='2019-03-21'
 * 
 *
 * به روز رسانی ردیف های بانک پرداخت های مشتری
 * update 
 sajakrrt_rtfund.LON_BackPays p join aaa on(requestID=c1)
join sajakrrt_rtfund.LON_BackPayDocs d1 on(p.BackPayID=d1.BackPayID)
join sajakrrt_rtfund.ACC_DocItems di1 on(di1.DocID=d1.DocID and di1.CostID=1001 and di1.SourceID3=p.BackPayID )
join sajakrrt_rtfund3.LON_BackPayDocs d2 on(p.BackPayID=d2.BackPayID)
 join sajakrrt_rtfund3.ACC_DocItems di2 on(di2.DocID=d2.DocID and di2.CostID=1001 and di2.SourceID3=p.BackPayID)
set di1.TafsiliID=di2.TafsiliID,di1.TafsiliID2=di2.TafsiliID2,di1.TafsiliID3=di2.TafsiliID3,
di1.param1=di2.param1,di1.param2=di2.param2,di1.param3=di2.param3,di1.details=di2.details
where p.PayDate>='2019-03-21';
 * 
 * به روز رسانی ردیف های غیر بانک
update sajakrrt_rtfund.LON_BackPays p join aaa on(requestID=c1)
join sajakrrt_rtfund.LON_BackPayDocs d1 on(p.BackPayID=d1.BackPayID)
join sajakrrt_rtfund.ACC_DocItems di1 on(di1.DocID=d1.DocID and di1.CostID=1001 and di1.SourceID3=p.BackPayID )
join sajakrrt_rtfund3.LON_BackPayDocs d2 on(p.BackPayID=d2.BackPayID)
 left join sajakrrt_rtfund3.ACC_DocItems di2 on(di2.DocID=d2.DocID and di2.CostID=1001 and di2.SourceID3=p.BackPayID)
 join sajakrrt_rtfund3.ACC_DocItems di3 on(di3.DocID=d2.DocID  and di3.SourceID3=p.BackPayID AND di3.Locked='NO')
 
 set di1.TafsiliID=di3.TafsiliID,di1.TafsiliID2=di3.TafsiliID2,di1.TafsiliID3=di3.TafsiliID3,
di1.param1=di3.param1,di1.param2=di3.param2,di1.param3=di3.param3,di1.details=di3.details, di1.CostID=di3.CostID
, di1.TafsiliType=di3.TafsiliType, di1.TafsiliType2=di3.TafsiliType2, di1.TafsiliType3=di3.TafsiliType3

 where p.PayDate>='2019-03-21' AND di2.ItemID is null



select jdate,description,SourceID1,SourceID3,SourceID4, DebtorAmount,CreditorAmount
from ACC_docs join ACC_DocItems using(DocID)  join dates on(gdate=DocDate)
where EventID>0 and CostID=1001
order by DocDate,SourceID1,SourceID3,SourceID4 
 */












/*
select p.RequestID,p.PayID,g2j(p.payDate),p.PayAmount,
                p.PayAmount - ifnull(p.OldFundDelayAmount,0) - ifnull(p.OldAgentDelayAmount,0)
                        - ifnull(p.OldFundWage,0) - ifnull(p.OldAgentWage,0)as PurePayAmount1,
                        
                        p2.PayAmount - ifnull(p2.OldFundDelayAmount,0) - ifnull(p2.OldAgentDelayAmount,0)
                        - ifnull(p2.OldFundWage,0) - ifnull(p2.OldAgentWage,0)as PurePayAmount2
                
            from krrtfir_rtfund.LON_payments p join krrtfir_oldcomputes.LON_payments p2 on(p.PayID=p2.PayID)
            join krrtfir_oldcomputes.aa on(aa.DociD=p.RequestID)   */
/*
 * چک های وصول نشده در یک تاریخ خاص
select ifnull(b.BackPayID,LoanRequestID) RequestID, ChequeNo,g2j(ChequeDate), InfoDesc 
from ACC_ChequeHistory h join(  SELECT max(RowID) RowID,IncomeChequeID FROM `ACC_ChequeHistory` 
								where ATS<'2019-03-21' and StatusID<>3333 group by IncomeChequeID
                                     )t on(h.RowID=t.RowID and h.IncomeChequeID=t.IncomeChequeID)
join ACC_IncomeCheques c on(h.IncomeChequeID=c.IncomeChequeID)
left join LON_BackPays b on(c.IncomeChequeID=b.IncomeChequeID)
join BaseInfo on(typeID=4 and InfoID=h.StatusID)
where h.StatusID not in(3003,3009,3011,3008) and c.PayedDate is null and b.BackPayID is null
		*/
/*
 * چک هایی که در سند وصول نشده ندارند
select  ChequeNo, ChequeAmount, LoanDesc, r.RequestID
from ACC_ChequeHistory h join(  SELECT max(RowID) RowID,IncomeChequeID FROM `ACC_ChequeHistory` 
                                where ATS<'2019-03-21' and StatusID<>3333 group by IncomeChequeID
                                     )t on(h.RowID=t.RowID and h.IncomeChequeID=t.IncomeChequeID)
join ACC_IncomeCheques c on(h.IncomeChequeID=c.IncomeChequeID)
join LON_BackPays b on(b.IncomeChequeID=c.IncomeChequeID)
join LON_requests r on(b.RequestID=r.RequestID)
join LON_loans l using(LoanID)
left join (select SourceID4,DocID from ACC_DocItems join ACC_docs using(DocID) join COM_events using(EventID) where CycleID=1398 AND 
          EventType in('IncomeCheque','LoanBackPayCheque') group by SourceID4)t2 on(t2.SourceID4=h.IncomeChequeID)
join BaseInfo on(typeID=4 and InfoID=h.StatusID)
where h.StatusID not in(3003,3009,3011,3008) and if(c.ChequeStatus=3003,c.PayedDate>='2019-03-21',1=1) and t2.DocID is  null/

/*
 لیست وام هایی که شرایط برداخت طی اقساط است ولی مبلغ برداختی کمتر از مبلغ وام می باشد
select p.RequestID,PartAmount,purepayed from LON_ReqParts p join aa on(DocID=RequestID) 
join (select RequestID,sum(PayAmount - ifnull(OldFundDelayAmount,0) 
                        - ifnull(OldAgentDelayAmount,0)
                        - ifnull(OldFundWage,0)
                        - ifnull(OldAgentWage,0)) purepayed from LON_payments join aa on(DocID=RequestID) 
      where OldFundDelayAmount>0 or OldAgentDelayAmount>0 or OldFundWage>0 or OldAgentWage>0
group by RequestID)t on(t.RequestID=p.RequestID)
where IsHistory='NO' and (if(FundWage>0,wageReturn='INSTALLMENT',1=0) or if(FundWage<CustomerWage,AgentReturn ='INSTALLMENT',1=0))
order by aa.DociD  
  */

/* مرتب سازی شماره اسناد در انتهای هر سال
insert into aa(DocID,regDoc) select DocID,@i:=@i+1 from (select a.* from ACC_docs a, 
  (select @i:=0)t where cycleID=1398 AND DocDate>1 
  order by case DocType when 1 then 1 when 3 then 1000 else 2 end,DocDate)t;
 * 
 * update aa join ACC_docs using(DocID) set LocalNo=regDoc

 * update ACC_DocItems join ACC_docs using(DocID) join LON_BackPays b on(IncomeChequeID=SourceID2)
join LON_ReqParts p on(IsHistory='NO' AND b.RequestID=p.RequestID) set DocDate=if(PartDate>'2019-03-21',PartDate,'2019-03-21') where EventID=1766
 * 
 *  */


/*
CREATE   VIEW LON_PayDocs AS

select di.SourceID3 AS PayID,d.DocID AS DocID,d.LocalNo AS LocalNo,d.StatusID AS StatusID
from (ACC_DocItems di join ACC_docs d on((di.DocID = d.DocID)))
where (di.SourceType = 4)
group by di.SourceID3

union all

select di2.SourceID3 AS SourceID3,d2.DocID AS DocID,d2.LocalNo AS LocalNo,d2.StatusID AS StatusID
from COM_events e join ACC_docs d2 on(e.EventID = d2.EventID)
join ACC_DocItems di2 on(d2.DocID = di2.DocID)
where e.EventFunction = 'PayLoan'

group by di2.SourceID3; */

/*
CREATE VIEW  LON_BackPayDocs AS

select 1 AS typeID,di.SourceID1 AS RequestID,di.SourceID2 AS BackPayID,d.DocID AS DocID,d.LocalNo AS LocalNo,
d.StatusID AS StatusID
from (ACC_DocItems di join ACC_docs d on((di.DocID = d.DocID)))
where ((di.SourceType = 5) and (di.SourceID2 > 0))
group by di.SourceID1,di.SourceID2,d.DocID

union all

select 2 AS TypeID,di.SourceID1 AS RequestID,di.SourceID3 AS BackPayID,d.DocID AS DocID,d.LocalNo AS LocalNo,
d.StatusID AS StatusID
from ACC_DocItems di join ACC_docs d on(di.DocID = d.DocID)
join COM_events e on(d.EventID = e.EventID)
where e.EventFunction = 'LoanBackPay' and di.SourceID3 > 0
group by di.SourceID1,di.SourceID3,d.DocID; */

/*select requestID, sum(wage) w, sum(PureWage) pw from LON_installments join LON_requests using(RequestID)
where StatusID=70 and ReqPersonID<>1003
group by RequestID
having w<>pw*/

/*
CREATE  VIEW  ParamItems AS 

select 1 AS paramID,BankID AS ItemID,BankDesc AS ParamValue 
from ACC_banks 

union all 

select 2 AS paramID,BranchID AS ItemID,BranchName AS ParamValue 
from BSC_branches 

union all 

select 3 AS paramID,RequestID AS ItemID,RequestID AS ParamValue 
from LON_requests 

union all 

select 101 AS paramID,TafsiliID AS ItemID,TafsiliDesc AS ParamValue 
from ACC_tafsilis where (TafsiliType = 150) 

union all 

select 104 AS paramID,LoanID AS ItemID,LoanDesc AS ParamValue 
from LON_loans 

union all 

select ParamID AS paramID,ItemID AS ItemID,ParamValue AS ParamValue 
from ACC_CostCodeParamItems
 */

/*گزارش روزانه وام های حمایتی برای حسابرس
 * select * from dates
left join (select PayAmount,PayDate from LON_payments join LON_requests using(requestID) where SubAgentID=15)t  on(gdate=PayDate)

where  Gdate>='2019-06-27'
 */



/*اصلاح اسناد دریافت چک که اشتباه داخلی خورده 
 * select d.CycleID,d.LocalNo,ChequeNo, di.CostID
from ACC_docs d join ACC_DocItems di using(DociD) join ACC_IncomeCheques ic on(SourceID4=IncomeChequeId) join LON_BackPays b using(IncomeChequeId) join LON_BackPayDocs bd using(BackPayID)
 join LON_requests r on(b.RequestID=r.RequestID)
where d.EventID=1766 AND ReqPersonID>0 AND di.CostID=1052
group by d.DocID
 * 
 * update ACC_docs d join ACC_DocItems di using(DociD) join ACC_IncomeCheques ic on(SourceID4=IncomeChequeId) join LON_BackPays b using(IncomeChequeId) join LON_BackPayDocs bd using(BackPayID)
 join LON_requests r on(b.RequestID=r.RequestID)
 set di.CostID=1024
where d.CycleID=1399 AND d.EventID=1766 AND ReqPersonID>0 AND di.CostID=1020
 * 
 * 
 * update ACC_docs d join ACC_DocItems di using(DociD) join ACC_IncomeCheques ic on(SourceID4=IncomeChequeId) join LON_BackPays b using(IncomeChequeId) join LON_BackPayDocs bd using(BackPayID)
 join LON_requests r on(b.RequestID=r.RequestID)
 set di.CostID=1057
where d.CycleID=1399 AND d.EventID=1766 AND ReqPersonID>0 AND di.CostID=1052
 * 
 * چک های که سند وصول نشده ندارند
 * select * from ACC_IncomeCheques i left join (select SourceID4 from ACC_DocItems join ACC_docs using(DociD) join COM_events using(eventID) 
                                           where EventType='IncomeCheque'   group by SourceID4)t on(i.IncomeChequeID=t.SourceID4)
                                           where t.SourceID4 is null
                                           order by ChequeDate desc
 */