Ext.define('Ext.calendar.template.BoxLayout', {
    extend: 'Ext.XTemplate',
    
    requires: ['Ext.SHDate'],
    
    constructor: function(config){
        
        Ext.apply(this, config);
    
        var weekLinkTpl = this.showWeekLinks ? '<div id="{weekLinkId}" class="ext-cal-week-link">{weekNum}</div>' : '';
        
        this.callParent([
            '<tpl for="weeks">',
                '<div id="{[this.id]}-wk-{[xindex-1]}" class="ext-cal-wk-ct" style="top:{[this.getRowTop(xindex, xcount)]}%; height:{[this.getRowHeight(xcount)]}%;">',
                    weekLinkTpl,
                    '<table class="ext-cal-bg-tbl" cellpadding="0" cellspacing="0">',
                        '<tbody>',
                            '<tr>',
                                '<tpl for=".">',
                                     '<td id="{[this.id]}-day-{[Ext.SHDate.format(values.date, "Ymd")]}" class="{cellCls}">&#160;</td>',
                                '</tpl>',
                            '</tr>',
                        '</tbody>',
                    '</table>',
                    '<table class="ext-cal-evt-tbl" cellpadding="0" cellspacing="0">',
                        '<tbody>',
                            '<tr>',
                                '<tpl for=".">',
                                    '<td id="{[this.id]}-ev-day-{[Ext.SHDate.format(values.date, "Ymd")]}" class="{titleCls}"><div>{title}</div></td>',
                                '</tpl>',
                            '</tr>',
                        '</tbody>',
                    '</table>',
                '</div>',
            '</tpl>', {
                getRowTop: function(i, ln){
                    return ((i-1)*(100/ln));
                },
                getRowHeight: function(ln){
                    return 100/ln;
                }
            }
        ]);
    },

    applyTemplate : function(o){
        
        Ext.apply(this, o);
        
        var w = 0, title = '', first = true, isToday = false, showMonth = false, prevMonth = false, nextMonth = false,
            weeks = [[]],
            dt = Ext.SHDate.clone(this.viewStart),
            thisMonth = this.startDate.getMonth();
        
        for(; w < this.weekCount || this.weekCount == -1; w++){
            if(dt.XDate > this.viewEnd.XDate){
                break;
            }
            weeks[w] = [];
            
            for(var d = 0; d < this.dayCount; d++){
                isToday = dt.getTime() === Ext.calendar.util.Date.today().getTime();
                showMonth = first || (dt.getDate() == 1);
                prevMonth = (dt.getMonth() < thisMonth) && this.weekCount == -1;
                nextMonth = (dt.getMonth() > thisMonth) && this.weekCount == -1;
                
                if(dt.getDay() == 1){
                    // The ISO week format 'W' is relative to a Monday week start. If we
                    // make this check on Sunday the week number will be off.
                    weeks[w].weekNum = this.showWeekNumbers ? Ext.SHDate.format(dt, 'W') : '&#160;';
                    weeks[w].weekLinkId = 'ext-cal-week-'+Ext.SHDate.format(dt, 'Ymd');
                }
                
                if(showMonth){
                    if(isToday){
                        title = this.getTodayText();
                    }
                    else{
                        title = Ext.SHDate.format(dt, this.dayCount == 1 ? 'l, F j, Y' : (first ? 'M j, Y' : 'M j'));
                    }
                }
                else{
                    var dayFmt = (w == 0 && this.showHeader !== true) ? 'D j' : 'j';
                    title = isToday ? this.getTodayText() : Ext.SHDate.format(dt, dayFmt);
                }
                
                weeks[w].push({
                    title: title,
                    date: Ext.SHDate.clone(dt),
                    titleCls: 'ext-cal-dtitle ' + (isToday ? ' ext-cal-dtitle-today' : '') + 
                        (w==0 ? ' ext-cal-dtitle-first' : '') +
                        (prevMonth ? ' ext-cal-dtitle-prev' : '') + 
                        (nextMonth ? ' ext-cal-dtitle-next' : ''),
                    cellCls: 'ext-cal-day ' + (isToday ? ' ext-cal-day-today' : '') + 
                        (d==0 ? ' ext-cal-day-first' : '') +
                        (prevMonth ? ' ext-cal-day-prev' : '') +
                        (nextMonth ? ' ext-cal-day-next' : '')
                });
                dt = Ext.calendar.util.Date.add(dt, {days: 1});
                first = false;
            }
        }
        if(weeks.length)
		{
			temp = this.applyOut({
				weeks: weeks
			}, []).join('');
			return temp;
		}
    },
    
    apply: function(values) {
        return this.applyTemplate.apply(this, arguments);
    },
    
    getTodayText : function(){
        var dt = Ext.SHDate.format(new Ext.SHDate(), 'l, F j, Y'),
            todayText = this.showTodayText !== false ? this.todayText : '',
            timeText = this.showTime !== false ? ' <span id="'+this.id+'-clock" class="ext-cal-dtitle-time">' + 
                    Ext.SHDate.format(new Ext.SHDate(), 'g:i a') + '</span>' : '',
            separator = todayText.length > 0 || timeText.length > 0 ? ' &mdash; ' : '';
        
        if(this.dayCount == 1){
            return dt + separator + todayText + timeText;
        }
        fmt = this.weekCount == 1 ? 'D j' : 'j';
        return todayText.length > 0 ? todayText + timeText : Ext.SHDate.format(new Ext.SHDate(), fmt) + timeText;
    }
});