<?php

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

/*
insert into aa select DocID,@i:=@i+1 from (select a.* from ACC_docs a, 
 * (select @i:=0)t where cycleID=1398 AND DocDate>1 order by DocDate)t 

 * update aa join ACC_docs using(DocID) set LocalNo=no 

 * update ACC_DocItems join ACC_docs using(DocID) join LON_BackPays b on(IncomeChequeID=SourceID2)
join LON_ReqParts p on(IsHistory='NO' AND b.RequestID=p.RequestID) set DocDate=if(PartDate>'2019-03-21',PartDate,'2019-03-21') where EventID=1766
 * 
 *  */
