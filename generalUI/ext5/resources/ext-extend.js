/*------------------------------------------
 * programmer: Sh.Jafarkhani
 * CreateDate: 90.09
 * Email: jafarkhani.shabnam@gmail.com
 *----------------------------------------- */

function isInt(value) {
  return !isNaN(value) && 
         parseInt(Number(value)) == value && 
         !isNaN(parseInt(value, 10));
} 

function mergeObjects(obj1,obj2){
    var obj3 = {};
    for (var attrname in obj1) {obj3[attrname] = obj1[attrname];}
    for (var attrname in obj2) {obj3[attrname] = obj2[attrname];}
    return obj3;
}

String.prototype.lpad = function(padString, length) {
    var str = this;
    while (str.length < length)
        str = padString + str;
    return str;
}

String.prototype.rpad = function(padString, length) {
    var str = this;
    while (str.length < length)
        str = str + padString;
    return str;
}

Array.prototype.find = function(o){
	for(var i = 0; i < this.length; i++)
	   if(this[i] == o)
		 return i;
	return -1;
}

Array.prototype.findObject = function(datamember, value){
	for(var i = 0; i < this.length; i++)
	{
		eval("tmp = this[i]." + datamember + ";");
	   if(tmp == value)
		 return this[i];
	}
	return null;
}

ObjectName = function(obj){
	for (var name in window)
      if (window[name] == obj) 
        return name;
}

Ext.apply(Ext.Array, {
	implode : function(glue, arr) {
		st = "";
		for(i=0; i<arr.length; i++)
			st += arr[i] + (i+1 < arr.length ? glue : "");
		return st;
    }
});

function findChild(source, childElement)
{
	if(Ext.isString(source))
		source = document.getElementById(source);
	
	if(!source.childNodes)
		return false;
		
	var childs = source.childNodes;
	if(childs.length == 0)
		return false;
	
	var elementID = (childElement.id) ? childElement.id : childElement;
	
	for(var i=0; i<childs.length; i++)
	{
		if(childs[i].id && childs[i].id == elementID)
			return childs[i];
		else if(childs[i].childNodes.length == 0)
			continue;
		else
		{
			var ret = findChild(childs[i], elementID);
			if(ret != false)
				return ret;
		}
	}
	return false;
}

Ext.apply(Ext, {
	MD5 : function(str){
		var hex_chr = "0123456789abcdef";
		function rhex(num){
			str = "";
			for(j = 0; j <= 3; j++)
				str += hex_chr.charAt((num >> (j * 8 + 4)) & 0x0F) + hex_chr.charAt((num >> (j * 8)) & 0x0F);
			return str;
		}
		/*
		* Convert a string to a sequence of 16-word blocks, stored as an array.
		* Append padding bits and the length, as described in the MD5 standard.
		*/
		function str2blks_MD5(str){
			nblk = ((str.length + 8) >> 6) + 1;
			blks = new Array(nblk * 16);
			for(i = 0; i < nblk * 16; i++)
				blks[i] = 0;
			for(i = 0; i < str.length; i++)
				blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
			blks[i >> 2] |= 0x80 << ((i % 4) * 8);
			blks[nblk * 16 - 2] = str.length * 8;
			return blks;
		}
		/*
		* Add integers, wrapping at 2^32. This uses 16-bit operations internally
		* to work around bugs in some JS interpreters.
		*/
		function add(x, y) {
		var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
			return (msw << 16) | (lsw & 0xFFFF);
		}

		/*
		* Bitwise rotate a 32-bit number to the left
		*/
		function rol(num, cnt) {
			return (num << cnt) | (num >>> (32 - cnt));
		}
		/*
		* These functions implement the basic operation for each round of the
		* algorithm.
		*/
		function cmn(q, a, b, x, s, t) {
			return add(rol(add(add(a, q), add(x, t)), s), b);
		}
		function ff(a, b, c, d, x, s, t) {
			return cmn((b & c) | ((~b) & d), a, b, x, s, t);
		}
		function gg(a, b, c, d, x, s, t){
			return cmn((b & d) | (c & (~d)), a, b, x, s, t);
		}
		function hh(a, b, c, d, x, s, t){
			return cmn(b ^ c ^ d, a, b, x, s, t);
		}
		function ii(a, b, c, d, x, s, t){
			return cmn(c ^ (b | (~d)), a, b, x, s, t);
		}
		function trim(str) {
			return str.replace(/^\s+|\s+$/g,"");
		 }
		function ChkForm(FormObj) {
			   var t = FormObj.dummyvar.value;
			   if(!ChkEmptiness(FormObj.userid) || !ChkEmptiness(FormObj.dummyvar))
				   return false;
			   FormObj.passwd.value = MD5(FormObj.dummyvar.value);
			   FormObj.dummyvar.value = '';
			   return true;
		}
		function ChkEmptiness(Object) {
			if(Object.value.length == 0 || trim(Object.value)== '') {
				alert("��� ���� ����� ���� ����");
				Object.focus();
				return  false;
			}
			return  true;
		}
		var a =  1732584193, b = -271733879, c = -1732584194, d =  271733878;
		x = str2blks_MD5(str);
		for(i = 0; i < x.length; i += 16) {
		var olda = a, oldb = b, oldc = c, oldd = d;
			a = ff(a, b, c, d, x[i+ 0], 7 , -680876936);
			d = ff(d, a, b, c, x[i+ 1], 12, -389564586);
			c = ff(c, d, a, b, x[i+ 2], 17, 606105819);
			b = ff(b, c, d, a, x[i+ 3], 22, -1044525330);
			a = ff(a, b, c, d, x[i+ 4], 7 , -176418897);
			d = ff(d, a, b, c, x[i+ 5], 12, 1200080426);
			c = ff(c, d, a, b, x[i+ 6], 17, -1473231341);
			b = ff(b, c, d, a, x[i+ 7], 22, -45705983);
			a = ff(a, b, c, d, x[i+ 8], 7 , 1770035416);
			d = ff(d, a, b, c, x[i+ 9], 12, -1958414417);
			c = ff(c, d, a, b, x[i+10], 17, -42063);
			b = ff(b, c, d, a, x[i+11], 22, -1990404162);
			a = ff(a, b, c, d, x[i+12], 7 , 1804603682);
			d = ff(d, a, b, c, x[i+13], 12, -40341101);
			c = ff(c, d, a, b, x[i+14], 17, -1502002290);
			b = ff(b, c, d, a, x[i+15], 22, 1236535329);

			a = gg(a, b, c, d, x[i+ 1], 5 , -165796510);
			d = gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
			c = gg(c, d, a, b, x[i+11], 14, 643717713);
			b = gg(b, c, d, a, x[i+ 0], 20, -373897302);
			a = gg(a, b, c, d, x[i+ 5], 5 , -701558691);
			d = gg(d, a, b, c, x[i+10], 9 , 38016083);
			c = gg(c, d, a, b, x[i+15], 14, -660478335);
			b = gg(b, c, d, a, x[i+ 4], 20, -405537848);
			a = gg(a, b, c, d, x[i+ 9], 5 , 568446438);
			d = gg(d, a, b, c, x[i+14], 9 , -1019803690);
			c = gg(c, d, a, b, x[i+ 3], 14, -187363961);
			b = gg(b, c, d, a, x[i+ 8], 20, 1163531501);
			a = gg(a, b, c, d, x[i+13], 5 , -1444681467);
			d = gg(d, a, b, c, x[i+ 2], 9 , -51403784);
			c = gg(c, d, a, b, x[i+ 7], 14, 1735328473);
			b = gg(b, c, d, a, x[i+12], 20, -1926607734);

			a = hh(a, b, c, d, x[i+ 5], 4 , -378558);
			d = hh(d, a, b, c, x[i+ 8], 11, -2022574463);
			c = hh(c, d, a, b, x[i+11], 16, 1839030562);
			b = hh(b, c, d, a, x[i+14], 23, -35309556);
			a = hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
			d = hh(d, a, b, c, x[i+ 4], 11, 1272893353);
			c = hh(c, d, a, b, x[i+ 7], 16, -155497632);
			b = hh(b, c, d, a, x[i+10], 23, -1094730640);
			a = hh(a, b, c, d, x[i+13], 4 , 681279174);
			d = hh(d, a, b, c, x[i+ 0], 11, -358537222);
			c = hh(c, d, a, b, x[i+ 3], 16, -722521979);
			b = hh(b, c, d, a, x[i+ 6], 23, 76029189);
			a = hh(a, b, c, d, x[i+ 9], 4 , -640364487);
			d = hh(d, a, b, c, x[i+12], 11, -421815835);
			c = hh(c, d, a, b, x[i+15], 16, 530742520);
			b = hh(b, c, d, a, x[i+ 2], 23, -995338651);

			a = ii(a, b, c, d, x[i+ 0], 6 , -198630844);
			d = ii(d, a, b, c, x[i+ 7], 10, 1126891415);
			c = ii(c, d, a, b, x[i+14], 15, -1416354905);
			b = ii(b, c, d, a, x[i+ 5], 21, -57434055);
			a = ii(a, b, c, d, x[i+12], 6 , 1700485571);
			d = ii(d, a, b, c, x[i+ 3], 10, -1894986606);
			c = ii(c, d, a, b, x[i+10], 15, -1051523);
			b = ii(b, c, d, a, x[i+ 1], 21, -2054922799);
			a = ii(a, b, c, d, x[i+ 8], 6 , 1873313359);
			d = ii(d, a, b, c, x[i+15], 10, -30611744);
			c = ii(c, d, a, b, x[i+ 6], 15, -1560198380);
			b = ii(b, c, d, a, x[i+13], 21, 1309151649);
			a = ii(a, b, c, d, x[i+ 4], 6 , -145523070);
			d = ii(d, a, b, c, x[i+11], 10, -1120210379);
			c = ii(c, d, a, b, x[i+ 2], 15, 718787259);
			b = ii(b, c, d, a, x[i+ 9], 21, -343485551);

			a = add(a, olda);
			b = add(b, oldb);
			c = add(c, oldc);
			d = add(d, oldd);
		}
		return rhex(a) + rhex(b) + rhex(c) + rhex(d);
	}
});

Ext.JSON = new(function() {
    var useHasOwn = !! {}.hasOwnProperty,
    isNative = function() {
        var useNative = null;

        return function() {
            if (useNative === null) {
                useNative = Ext.USE_NATIVE_JSON && window.JSON && JSON.toString() == '[object JSON]';
            }

            return useNative;
        };
    }(),
    pad = function(n) {
        return n < 10 ? "0" + n : n;
    },
    doDecode = function(json) {
        return eval("(" + json + ')');
    },
    doEncode = function(o) {
        if (!Ext.isDefined(o) || o === null) {
            return "null";
        } else if (Ext.isArray(o)) {
            return encodeArray(o);
        } else if (Ext.isDate(o)) {
            return Ext.JSON.encodeDate(o);
		}else if(Ext.isSHDate(o)) {
			return Ext.JSON.encodeSHDate(o);
        } else if (Ext.isString(o)) {
            return encodeString(o);
        } else if (typeof o == "number") {
            
            return isFinite(o) ? String(o) : "null";
        } else if (Ext.isBoolean(o)) {
            return String(o);
        } else if (Ext.isObject(o)) {
            return encodeObject(o);
        } else if (typeof o === "function") {
            return "null";
        }
        return 'undefined';
    },
    m = {
        "\b": '\\b',
        "\t": '\\t',
        "\n": '\\n',
        "\f": '\\f',
        "\r": '\\r',
        '"': '\\"',
        "\\": '\\\\',
        '\x0b': '\\u000b' 
    },
    charToReplace = /[\\\"\x00-\x1f\x7f-\uffff]/g,
    encodeString = function(s) {
        return '"' + s.replace(charToReplace, function(a) {
            var c = m[a];
            return typeof c === 'string' ? c : a;
        }) + '"';
    },
    encodeArray = function(o) {
        var a = ["[", ""],
        
        len = o.length,
        i;
        for (i = 0; i < len; i += 1) {
            a.push(doEncode(o[i]), ',');
        }
        
        a[a.length - 1] = ']';
        return a.join("");
    },
    encodeObject = function(o) {
        var a = ["{", ""],
        
        i;
        for (i in o) {
            if (!useHasOwn || o.hasOwnProperty(i)) {
                a.push(doEncode(i), ":", doEncode(o[i]), ',');
            }
        }
        
        a[a.length - 1] = '}';
        return a.join("");
    };

    
    this.encodeDate = function(o) {
        return '"' + o.getFullYear() + "-"
        + pad(o.getMonth() + 1) + "-"
        + pad(o.getDate()) + "T"
        + pad(o.getHours()) + ":"
        + pad(o.getMinutes()) + ":"
        + pad(o.getSeconds()) + '"';
    };

	this.encodeSHDate = function(o) {
        return '"' + o.getFullYear() + "-" + pad(o.getMonth() + 1) + "-" + pad(o.getDate())+ '"';
    };

    
    this.encode = function() {
        var ec;
        return function(o) {
            if (!ec) {
                
                ec = isNative() ? JSON.stringify : doEncode;
            }
            return ec(o);
        };
    }();


    
    this.decode = function() {
        var dc;
        return function(json, safe) {
            if (!dc) {
                
                dc = isNative() ? JSON.parse : doDecode;
            }
            try {
                return dc(json);
            } catch (e) {
                if (safe === true) {
                    return null;
                }
                Ext.Error.raise({
                    sourceClass: "Ext.JSON",
                    sourceMethod: "decode",
                    msg: "You're trying to decode an invalid JSON String: " + json
                });
            }
        };
    }();

})();

Ext.encode = Ext.JSON.encode; 
Ext.decode = Ext.JSON.decode;

Ext.apply(Ext.util.Format, {
	Money : function(value) {
		value = value * 1;
				
        var ps = value.toString().split('.');
		ps[1] = ps[1] ? ps[1] : null;
		
		var whole = ps[0];		
		var r = /(\d+)(\d{3})/;		
		var ts = ",";		
		while (r.test(whole)) 
			whole = whole.replace(r, '$1' + ts + '$2');
		
		value = whole + (ps[1] ? "." + ps[1] : '');
		
		return value;
    }
});
//***********************************************************
//********************* shdate ******************************
//***********************************************************

Ext.apply(Ext, {
	isSHDate: function(value) {
	if(value == undefined)
	    return false;
        return Object.prototype.toString.call(value) === '[object Object]' && value.day;
    }
});
g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

var DateModule = {};

DateModule.AddToGDate = function(gdate, dayplus, monthplus, yearplus){
	yearplus = yearplus == null ? 0 : yearplus*1;
	monthplus = monthplus == null ? 0 : monthplus*1;
	dayplus = dayplus == null ? 0 : dayplus*1;
	gdate_array = gdate.toString().split(/[\-\/]/);
	
	year = gdate_array[0]*1+yearplus;
	month = gdate_array[1]*1+monthplus;
	day = gdate_array[2]*1+dayplus;
	
	gdate = new Date(year, month-1, day, 1);
	return gdate.getFullYear() + "/" +(gdate.getMonth()+1) + "/" + gdate.getDate();
}
DateModule.AddToJDate = function(jdate, dayplus, monthplus, yearplus){

	if(jdate == "")
		return "";
	dayplus = dayplus == undefined ? 0 : dayplus*1;	
	monthplus = monthplus == undefined ? 0 : monthplus*1;
	yearplus = yearplus == undefined ? 0 : yearplus*1;

	if(dayplus > 0)
	{
		gdate = ShamsiToMiladi(jdate);
		gdate_array = gdate.split(/[\-\/]/);
		gtime = new Date(gdate_array[0],gdate_array[1]*1-1, gdate_array[2]*1+dayplus, 1);
		gtime = gtime.getFullYear() + "-" + (gtime.getMonth()*1+1).toString().lpad('0',2) + "-" + gtime.getDate().toString().lpad('0',2);
		jdate = MiladiToShamsi(gtime);
	}

	arr = jdate.split(/[\-\/]/);

	year = arr[0]*1 + Math.floor((arr[1]*1 + monthplus) / 12) + yearplus;
	monthplus = (arr[1]*1 + monthplus)%12;
	if(monthplus == 0)
	{
		year--;
		monthplus = 12;
	}
	dayplus = arr[2];

	return year + "-" + monthplus.toString().lpad('0',2) + "-" + dayplus.toString().lpad('0',2);
}
DateModule.GDateMinusGDate = function(date1, date2){
		
	var date1 = new Date(date1 + " 1:0");
	var date2 = new Date(date2 + " 1:0");
	var timeDiff = Math.abs(date2.getTime() - date1.getTime());
	var diffDays = Math.round(timeDiff / (1000 * 3600 * 24)); 
	return diffDays;
}
DateModule.JDateMinusJDate = function(jdate1, jdate2){
	
	gdate1 = ShamsiToMiladi(jdate1);
	gdate2 = ShamsiToMiladi(jdate2);
	
	return DateModule.GDateMinusGDate(gdate1,gdate2);
}
DateModule.GetDiffInMonth = function(jdate1, jdate2){
	year1 = jdate1.substr(0,4);
	month1 = jdate1.substr(5,2);

	year2 = jdate2.substr(0,4);
	month2 = jdate2.substr(5,2);

	return (year2-year1)*12 + (month2-month1);
}		
DateModule.IsDateGreater = function (DateValue1, DateValue2) {
    var DaysDiff;
    Date1 = new Date(DateValue1);
    Date2 = new Date(DateValue2);
    DaysDiff = Math.floor((Date1.getTime() - Date2.getTime()) / (1000 * 60 * 60 * 24));
    if (DaysDiff > 0) return true;
    else
    return false;
};
DateModule.IsDateLess = function (DateValue1, DateValue2) {
    var DaysDiff;
    Date1 = new Date(DateValue1);
    Date2 = new Date(DateValue2);
    DaysDiff = Math.floor((Date1.getTime() - Date2.getTime()) / (1000 * 60 * 60 * 24));
    if (DaysDiff <= 0) return true;
    else
    return false;
};
DateModule.IsDateEqual = function (DateValue1, DateValue2) {
    var DaysDiff;
    Date1 = new Date(DateValue1);
    Date2 = new Date(DateValue2);
    DaysDiff = Math.floor((Date1.getTime() - Date2.getTime()) / (1000 * 60 * 60 * 24));
    if (DaysDiff == 0) return true;
    else
    return false;
};
DateModule.DaysOfMonth = function(jyear,jmonth){
	month_length = 0;
		if(jmonth <= 6 ) {
			month_length =  31;
		}
		else {
			month_length =  30;
			if(jmonth == 12 && !DateModule.YearIsLeap(jyear)) {
				month_length = 29;
			}
		}
		return month_length;
}
DateModule.YearIsLeap = function(jyear){
	if (((jyear-22) % 33 == 0) || (( ((jyear-22)%33) % 32 != 0) && (( (jyear-22)%33 % 4 == 0))))
		return true;
	  return  false;
}
DateModule.lastJDateOfYear = function(jyear, delimiter){
	
	delimiter = (delimiter == null) ? "/" : delimiter;
	day = DateModule.DaysOfMonth(jyear, 12).toString();
	return jyear + delimiter + "12" + delimiter + day;
}
DateModule.SecondsToTime = function(seconds){
		
	hours = Math.floor(seconds / 3600);
	seconds = seconds - hours*3600;
	minutes = Math.floor( seconds / 60);
	seconds = seconds - minutes*60;
	return new Array(
		hours.toString().lpad("0",2), 
		minutes.toString().lpad("0",2),
		seconds.toString().lpad("0",2)
	);
}
DateModule.SecondsToTimeString = function(seconds){
		
	arr = DateModule.SecondsToTime(seconds);
	if(arr[0] == "00" && arr[1] == "00")
		return "";
	return (arr[0]*1) + ":" + arr[1];
}
DateModule.TimeToSeconds = function(hour, min, second){
		
	return hour*3600 + min*60 + second;
}

MiladiToShamsi = function (date, format) {
    
    if (arguments.length == 1) {
        format = "Y/m/d";
    }
	
	if (!date || date == "" || date == "0000-00-00") return "";
	
    if(date.length > 10)
		date = date.substr(0,10);
		
    if (!date || date == "" || date == "0000-00-00" || date == "0000/00/00") return "";

	arr = date.toString().split(/[\-\/]/);
    
	var tmpyear = arr[0];
	var tmpmonth = arr[1];
	var tmpday = arr[2];
        
	if(tmpyear*1 < 1500 || tmpyear.substr(0,2) == "13")
		return date;

    try {
        var dateFormat = (date.indexOf('/') != -1) ? "Y/m/d" : "Y-m-d";
        //var date1 = new Date(tmpmonth + '/' + tmpday + '/' + tmpyear + " 1:0");
		var date1 = new Date(tmpyear, tmpmonth-1, tmpday, 1);
		date1 = GtoJ(date1);
		return date1.format(format);
    } catch (e) {
        return "";
    }
}

ShamsiToMiladi = function (date, format) {
	
    if (arguments.length == 1) format = "Y/m/d";
    if (!date || date == "" || date == "0000-00-00") return "";

	if(date.toString().substr(0,4) > 1900)
		return date;

    if (date.day) {
        var dd = date.year;
        dd += (date.month < 10) ? "/0" + date.month : "/" + date.month;
        dd += (date.day < 10) ? "/0" + date.day : "/" + date.day;
        date = dd;
    }
    if (date.substring(0, 2) == "20") return date;
    try {
		var dateFormat = (date.indexOf('/') != -1) ? "Y/m/d" : "Y-m-d";
        var date1 = Ext.SHDate.parseDate(date, dateFormat);
		date1 = JtoG(date1);
		return Ext.Date.format(date1,format);
    } catch (e) {
        return "";
    }
}

GtoJ = function (date) {
    if (date == "") return null;

    function div(num1, num2) {
        return parseInt(num1 / num2);
    }
    gy = date.getFullYear() - 1600;
    gm = date.getMonth();
    gd = date.getDate() - 1;
    g_day_no = 365 * gy + div(gy + 3, 4) - div(gy + 99, 100) + div(gy + 399, 400);
    for (var i = 0; i < gm; i++) g_day_no += g_days_in_month[i];
    if (gm > 1 && ((gy % 4 == 0 && gy % 100 != 0) || (gy % 400 == 0))) g_day_no++;
    g_day_no += gd;
    j_day_no = g_day_no - 79;
    j_np = div(j_day_no, 12053);
    j_day_no = j_day_no % 12053;
    jy = 979 + 33 * j_np + 4 * div(j_day_no, 1461);
    j_day_no %= 1461;
    if (j_day_no >= 366) {
        jy += div(j_day_no - 1, 365);
        j_day_no = (j_day_no - 1) % 365;
    }
    for (var i = 0; i < 11 && j_day_no >= j_days_in_month[i]; ++i)
    j_day_no -= j_days_in_month[i];
    jm = i;
    jd = j_day_no + 1;
    return new Ext.SHDate(jy, jm, jd);
};

JtoG = function (date) {
    if (date == "") return null;

    function div(num1, num2) {
        return parseInt(num1 / num2);
    }
    jy = date.getFullYear() - 979;
    jm = date.getMonth();
    jd = date.getDate() - 1;
    j_day_no = 365 * jy + div(jy, 33) * 8 + div(jy % 33 + 3, 4);
    for (var i = 0; i < jm; ++i) j_day_no += this.j_days_in_month[i];
    j_day_no += jd;
    g_day_no = j_day_no + 79;
    gy = 1600 + 400 * div(g_day_no, 146097);
    g_day_no = g_day_no % 146097;
    leap = true;
    if (g_day_no >= 36525) {
        g_day_no--;
        gy += 100 * div(g_day_no, 36524);
        g_day_no = g_day_no % 36524;
        if (g_day_no >= 365) g_day_no++;
        else {
            leap = false;
        }
    }
    gy += 4 * div(g_day_no, 1461);
    g_day_no %= 1461;
    if (g_day_no >= 366) {
        leap = false;
        g_day_no--;
        gy += div(g_day_no, 365);
        g_day_no = g_day_no % 365;
    }
    for (var i = 0; g_day_no >= this.g_days_in_month[i] + (i == 1 && leap); i++)
    g_day_no -= this.g_days_in_month[i] + (i == 1 && leap);
    gm = i + 1;
    gd = g_day_no + 1;
    var returnDate = new Date(gy, gm - 1, gd, 1, 0);
	//----------------- jafarkhani ----------------
	/*if(returnDate.getDate() != gd) 
		returnDate = returnDate.add("d",1);*/
	return returnDate;
	//---------------------------------------------
};

Ext.SHDate = function (y, m, d, h, min, s) {
    if (arguments.length == 0) {
        this.XDate = new Date();
        var nD = GtoJ(this.XDate);
        this.year = nD.getFullYear();
        this.month = nD.getMonth();
        this.day = nD.getDate();
    } else if (arguments.length == 1) {
		this.XDate = new Date(arguments[0]);
        nD = GtoJ(this.XDate);
        this.year = nD.getFullYear();
        this.month = nD.getMonth();
        this.day = nD.getDate();		
    } else if (arguments.length == 3) {
        this.year = y;
        this.month = m;
        this.day = d;
		this.XDate = JtoG(this);
    }
	else{
		this.year = y;
        this.month = m;
        this.day = d;
		this.XDate = JtoG(this);
		min = arguments.length >= 5 ? min*1 : 0;
		s = arguments.length >= 6 ? s*1 : 0;
		this.XDate.setHours(h);
		this.XDate.setMinutes(min);
		this.XDate.setSeconds(s);
	}		
};
Ext.SHDate.prototype = {
    setHours: function (h) {
		this.XDate.setHours(h);
	},
    setMinutes: function (m) {
		this.XDate.setMinutes(m);
	},
    setSeconds: function (s) {
		this.XDate.setSeconds(s);
	},
    setMilliseconds: function () {},
    getFullYear: function () {
        return this.year;
    },
    getMonth: function () {
        return this.month;
    },
    getDay: function () {
        //this.XDate = JtoG(new Ext.SHDate(this.year, this.month, this.day));
        var d = this.XDate.getDay();
        return (d + 1 < 7) ? d + 1 : 0;
    },
    getDate: function () {
        return this.day;
    },
    getTime: function () {
		h = this.XDate.getHours();
		m = this.XDate.getMinutes();
		s = this.XDate.getSeconds();
		this.XDate = JtoG(this);
		this.XDate.setHours(h);
		this.XDate.setMinutes(m);
		this.XDate.setSeconds(s);
        return this.XDate.getTime();
    },
	setTime: function () {
        this.XDate = JtoG(this);
        return this.XDate.setTime();
    },
	getSeconds: function () {
        this.XDate = JtoG(this);
        return this.XDate.getSeconds();
    },
	getMilliseconds: function () {
        this.XDate = JtoG(this);
        return this.XDate.getMilliseconds();
    },
    setDate: function (value) {
        this.setYearDay(value);
    },
    setMonth: function (mo) {
        while (mo <= -12) {
            this.year--;
            mo += 12;
        }
        while (mo >= 12) {
            this.year++;
            mo -= 12;
        }
        this.month = 0;
        if (mo < 0) {
            if ((-1 * mo) > this.month) {
                this.year--;
                this.month = 12 + mo
            } else this.month = 12 + mo;
        } else if (mo > 0) if (mo > (11 - this.month)) {
            this.year++;
            this.month = mo - (12 - this.month);
        } else this.month = mo;
    },
    setFullYear: function (y) {
        this.year = y;
    },
    toString: function () {
        return this.year + "-" + this.month + "-" + this.day;
    },
    setYearDay: function (days) {
        while (days <= -365) {
            if (this.isLeapYear(this.year - 1)) if (days <= -366) {
                days += 366;
                this.year--;
            } else
            break;
            else {
                this.year--;
                days += 365;
            }
        }
        while (days >= 365) {
            if (this.isLeapYear(this.year + 1)) if (days >= 366) {
                days -= 366;
                this.year++;
            } else
            break;
            else {
                this.year++;
                days -= 365;
            }
        }
        Ext.SHDate.daysInMonth[11] = this.isLeapYear() ? 30 : 29;
        this.day = 0;
        var ff = this.getDayOfYear();
        if (days < 0) {
            if ((-1 * days) > ff) {
                this.year--;
                days += (ff + 1);
                var tol = this.isLeapYear() ? 366 : 365;
                days = tol + days;
            } else days = ff + days + 1;
        } else {
            var tol = this.isLeapYear() ? 366 : 365;
            if (days > (tol - (ff + 1))) {
                this.year++;
                days -= (tol - (ff + 1))
            } else days = ff + days + 1;
        }
        for (var i = 0; i < 12; i++) {
            if (days <= Ext.SHDate.daysInMonth[i]) break;
            days -= Ext.SHDate.daysInMonth[i];
        }
        this.month = i;
        this.day = days;
    },
	getHours : function () {
        //this.XDate = JtoG(this);
        return this.XDate.getHours();
    },
	getMinutes : function () {
        //this.XDate = JtoG(this);
        return this.XDate.getMinutes();
    }
};
Ext.SHDate.parseFunctions = {
    count: 0
};
Ext.SHDate.parseRegexes = [];
Ext.SHDate.formatFunctions = {
    count: 0
};
Ext.SHDate.prototype.dateFormat = function (format) {
    if (Ext.SHDate.formatFunctions[format] == null) {
        Ext.SHDate.createNewFormat(format);
    }
	if(format != undefined)
	{
		var func = Ext.SHDate.formatFunctions[format];
		try{return this[func]();}
		catch(e){alert(func);}
	}
};
Ext.SHDate.prototype.format = Ext.SHDate.prototype.dateFormat;
Ext.SHDate.createNewFormat = function (format) {
	if(format == undefined)
		return;
    var funcName = "format" + Ext.SHDate.formatFunctions.count++;
    Ext.SHDate.formatFunctions[format] = funcName;
    var code = "Ext.SHDate.prototype." + funcName + " = function(){return ";
    var special = false;
    var ch = '';
    for (var i = 0; i < format.length; ++i) {
        ch = format.charAt(i);
        if (!special && ch == "\\") {
            special = true;
        } else if (special) {
            special = false;
            code += "'" + Ext.String.escape(ch) + "' + ";
        } else {
            code += Ext.SHDate.getFormatCode(ch);
        }
    }
    eval(code.substring(0, code.length - 3) + ";}");
};
Ext.SHDate.getFormatCode = function (character) {
    switch (character) {
    case "d":return "Ext.String.leftPad(this.getDate(), 2, '0') + ";
    case "D":return "Ext.SHDate.getShortDayName(this.getDay()) + ";
    case "j":return "this.getDate() + ";
    case "l":return "Ext.SHDate.dayNames[this.getDay()] + ";
    case "S":return "this.getSuffix() + ";
    case "w":return "this.getDay() + ";
    case "z":return "this.getDayOfYear() + ";
    case "W":return "this.getWeekOfYear() + ";
    case "F":return "Ext.SHDate.monthNames[this.getMonth()] + ";
    case "m":return "Ext.String.leftPad(this.getMonth() + 1, 2, '0') + ";
    case "M":return "Ext.SHDate.monthNames[this.getMonth()] + ";
    case "n":return "(this.getMonth() + 1) + ";
    case "t":return "this.getDaysInMonth() + ";
    case "L":return "(this.isLeapYear() ? 1 : 0) + ";
    case "Y":return "this.getFullYear() + ";
    case "y":return "('' + this.getFullYear()).substring(2, 4) + ";
    case "a":return "(this.getHours() < 12 ? 'am' : 'pm') + ";
    case "A":return "(this.getHours() < 12 ? 'AM' : 'PM') + ";
    case "g":return "((this.getHours() % 12) ? this.getHours() % 12 : 12) + ";
    case "G":return "this.getHours() + ";
    case "h":return "Ext.String.leftPad((this.getHours() % 12) ? this.getHours() % 12 : 12, 2, '0') + ";
    case "H":return "Ext.String.leftPad(this.getHours(), 2, '0') + ";
    case "i":return "Ext.String.leftPad(this.getMinutes(), 2, '0') + ";
    case "s":return "Ext.String.leftPad(this.getSeconds(), 2, '0') + ";
    case "O":return "this.getGMTOffset() + ";
    case "T":return "this.getTimezone() + ";
    case "Z":return "(this.getTimezoneOffset() * -60) + ";
    default:return "'" + Ext.String.escape(character) + "' + ";
    }
};
Ext.SHDate.parseDate = function (input, format) {
    if (Ext.SHDate.parseFunctions[format] == null) Ext.SHDate.createParser(format);
    var func = Ext.SHDate.parseFunctions[format];
    return Ext.SHDate[func](input);
};
Ext.SHDate.createParser = function (format) {
    var funcName = "parse" + Ext.SHDate.parseFunctions.count++;
    var regexNum = Ext.SHDate.parseRegexes.length;
    var currentGroup = 1;
    Ext.SHDate.parseFunctions[format] = funcName;
    var code = "Ext.SHDate." + funcName + " = function(input){\n" + "var y = -1, m = -1, d = -1, h = -1, i = -1, s = -1, o, z, v;\n" + "var d = new Ext.SHDate();\n" + "y = d.getFullYear();\n" + "m = d.getMonth();\n" + "d = d.getDate();\n" + "var results = input.match(Ext.SHDate.parseRegexes[" + regexNum + "]);\n" + "if (results && results.length > 0) {";
    var regex = "";
    var special = false;
    var ch = '';
    for (var i = 0; i < format.length; ++i) {
        ch = format.charAt(i);
        if (!special && ch == "\\") {
            special = true;
        } else if (special) {
            special = false;
            regex += Ext.String.escape(ch);
        } else {
            var obj = Ext.SHDate.formatCodeToRegex(ch, currentGroup);
            currentGroup += obj.g;
            regex += obj.s;
            if (obj.g && obj.c) {
                code += obj.c;
            }
        }
    }
    code += "if (y >= 0 && m >= 0 && d > 0 && h >= 0 && i >= 0 && s >= 0)\n" + "{v = new Ext.SHDate(y, m, d, h, i, s);}\n" + "else if (y >= 0 && m >= 0 && d > 0 && h >= 0 && i >= 0)\n" + "{v = new Ext.SHDate(y, m, d, h, i);}\n" + "else if (y >= 0 && m >= 0 && d > 0 && h >= 0)\n" + "{v = new Ext.SHDate(y, m, d, h);}\n" + "else if (y >= 0 && m >= 0 && d > 0)\n" + "{v = new Ext.SHDate(y, m, d);}\n" + "else if (y >= 0 && m >= 0)\n" + "{v = new Ext.SHDate(y, m);}\n" + "else if (y >= 0)\n" + "{v = new Ext.SHDate(y);}\n" + "}return (v && (z || o))?\n" + "    ((z)? v.add(Ext.SHDate.SECOND, (v.getTimezoneOffset() * 60) + (z*1)) :\n" + "        v.add(Ext.SHDate.HOUR, (v.getGMTOffset() / 100) + (o / -100))) : v\n" + ";}";
    Ext.SHDate.parseRegexes[regexNum] = new RegExp("^" + regex + "$");
    eval(code);
};
Ext.SHDate.formatCodeToRegex = function (character, currentGroup) {
    switch (character) {
    case "D":
        return {
            g: 0,
            c: null,
            s: "(?:Sat|Sun|Mon|Tue|Wed|Thu|Fri)"
        };
    case "j":
        return {
            g: 1,
            c: "d = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{1,2})"
        };
    case "d":
        return {
            g: 1,
            c: "d = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{2})"
        };
    case "l":
        return {
            g: 0,
            c: null,
            s: "(?:" + Ext.SHDate.dayNames.join("|") + ")"
        };
    case "S":
        return {
            g: 0,
            c: null,
            s: "(?:st|nd|rd|th)"
        };
    case "w":
        return {
            g: 0,
            c: null,
            s: "\\d"
        };
    case "z":
        return {
            g: 0,
            c: null,
            s: "(?:\\d{1,3})"
        };
    case "W":
        return {
            g: 0,
            c: null,
            s: "(?:\\d{2})"
        };
    case "F":
        return {
            g: 1,
            c: "m = parseInt(Ext.SHDate.monthNumbers[results[" + currentGroup + "].substring(0, 3)], 10);\n",
            s: "(" + Ext.SHDate.monthNames.join("|") + ")"
        };
    case "M":
        return {
            g: 1,
            c: "m = parseInt(Ext.SHDate.monthNumbers[results[" + currentGroup + "]], 10);\n",
            s: "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)"
        };
    case "n":
        return {
            g: 1,
            c: "m = parseInt(results[" + currentGroup + "], 10) - 1;\n",
            s: "(\\d{1,2})"
        };
    case "m":
        return {
            g: 1,
            c: "m = parseInt(results[" + currentGroup + "], 10) - 1;\n",
            s: "(\\d{2})"
        };
    case "t":
        return {
            g: 0,
            c: null,
            s: "\\d{1,2}"
        };
    case "L":
        return {
            g: 0,
            c: null,
            s: "(?:1|0)"
        };
    case "Y":
        return {
            g: 1,
            c: "y = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{4})"
        };
    case "y":
        return {
            g: 1,
            c: "var ty = parseInt(results[" + currentGroup + "], 10);\n" + "y = ty > Date.y2kYear ? 1300 + ty : 1400 + ty;\n",
            s: "(\\d{1,2})"
        };
    case "a":
        return {
            g: 1,
            c: "if (results[" + currentGroup + "] == 'am') {\n" + "if (h == 12) { h = 0; }\n" + "} else { if (h < 12) { h += 12; }}",
            s: "(am|pm)"
        };
    case "A":
        return {
            g: 1,
            c: "if (results[" + currentGroup + "] == 'AM') {\n" + "if (h == 12) { h = 0; }\n" + "} else { if (h < 12) { h += 12; }}",
            s: "(AM|PM)"
        };
    case "g":
    case "G":
        return {
            g: 1,
            c: "h = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{1,2})"
        };
    case "h":
    case "H":
        return {
            g: 1,
            c: "h = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{2})"
        };
    case "i":
        return {
            g: 1,
            c: "i = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{2})"
        };
    case "s":
        return {
            g: 1,
            c: "s = parseInt(results[" + currentGroup + "], 10);\n",
            s: "(\\d{2})"
        };
    case "O":
        return {
            g: 1,
            c: ["o = results[", currentGroup, "];\n", "var sn = o.substring(0,1);\n", "var hr = o.substring(1,3)*1 + Math.floor(o.substring(3,5) / 60);\n", "var mn = o.substring(3,5) % 60;\n", "o = ((-12 <= (hr*60 + mn)/60) && ((hr*60 + mn)/60 <= 14))?\n", "(sn + Ext.String.leftPad(hr, 2, 0) + Ext.String.leftPad(mn, 2, 0)) : null;\n"].join(""),
            s: "([+\-]\\d{4})"
        };
    case "T":
        return {
            g: 0,
            c: null,
            s: "[A-Z]{1,4}"
        };
    case "Z":
        return {
            g: 1,
            c: "z = results[" + currentGroup + "];\n" + "z = (-43200 <= z*1 && z*1 <= 50400)? z : null;\n",
            s: "([+\-]?\\d{1,5})"
        };
    default:
        return {
            g: 0,
            c: null,
            s: Ext.String.escape(character)
        };
    }
};
Ext.SHDate.prototype.getTimezone = function () {
    return this.toString().replace(/^.*? ([A-Z]{1,4})[\-+][0-9]{4} .*$/, "$1");
};
Ext.SHDate.prototype.getGMTOffset = function () {
    return (this.getTimezoneOffset() > 0 ? "-" : "+") + Ext.String.leftPad(Math.abs(Math.floor(this.getTimezoneOffset() / 60)), 2, "0") + Ext.String.leftPad(this.getTimezoneOffset() % 60, 2, "0");
};
Ext.SHDate.prototype.getDayOfYear = function () {
    var num = 0;
    Ext.SHDate.daysInMonth[11] = this.isLeapYear() ? 30 : 29;
    for (var i = 0; i < this.getMonth(); ++i) {
        num += Ext.SHDate.daysInMonth[i];
    }
    return num + this.getDate() - 1;
};
Ext.SHDate.prototype.getWeekOfYear = function () {
    var now = this.getDayOfYear() + (4 - this.getDay());
    var jan1 = new Ext.SHDate(this.getFullYear(), 0, 1);
    var then = (7 - jan1.getDay() + 4);
    return Ext.String.leftPad(((now - then) / 7) + 1, 2, "0");
};
Ext.SHDate.prototype.isLeapYear = function () {
    var year = this.getFullYear();
    if (year > 0) return ((((((year - (474)) % 2820) + 474) + 38) * 682) % 2816) < 682;
    else
    return ((((((year - (473)) % 2820) + 474) + 38) * 682) % 2816) < 682;
};
Ext.SHDate.prototype.getFirstDayOfMonth = function () {
    var day = (this.getDay() - (this.getDate() - 1)) % 7;
    return (day < 0) ? (day + 7) : day;
};
Ext.SHDate.prototype.getDaysInMonth = function () {
    Ext.SHDate.daysInMonth[11] = this.isLeapYear() ? 30 : 29;
    return Ext.SHDate.daysInMonth[this.getMonth()];
};
Ext.SHDate.prototype.getSuffix = function () {
    return "ط§ظ…";
};
Ext.SHDate.prototype.add = function (interval, value) {
    var d = this.clone();
    if (!interval || value === 0) return d;
    switch (interval.toLowerCase()) {
    case Ext.SHDate.MILLI:
        d.setMilliseconds(this.getMilliseconds() + value);
        break;
    case Ext.SHDate.SECOND:
        d.setSeconds(this.getSeconds() + value);
        break;
    case Ext.SHDate.MINUTE:
        d.setMinutes(this.getMinutes() + value);
        break;
    case Ext.SHDate.HOUR:
        d.setHours(this.getHours() + value);
        break;
    case Ext.SHDate.DAY:
        d.setDate(this.getDate() + value);
        break;
    case Ext.SHDate.MONTH:
        /*var day = this.getDate();
        if (day > 29) {
            day = Math.min(day, Ext.SHDate.getLastDateOfMonth(Ext.SHDate.getFirstDateOfMonth(this).add('mo', value)).getDate());
        }
        d.setDate(day);*/
        d.setMonth(this.getMonth() + value);
		if(d.day > Ext.SHDate.daysInMonth[ d.month ])
			d.day = Ext.SHDate.daysInMonth[ d.month ];
        break;
    case Ext.SHDate.YEAR:
        d.setFullYear(this.getFullYear() + value);
        break;
    }
    return d;
};
Ext.SHDate.prototype.clone = function () {
    obj = new Ext.SHDate(this.year, this.month, this.day);
	obj.XDate = new Date(this.XDate.getTime());
	return obj;
};


Ext.SHDate.daysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
Ext.SHDate.monthNames = ["فرردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
Ext.SHDate.dayNames = ["شنبه", "یکشنبه", "دوشنبه", "سه شنبه", "چهارشنبه", "پنج شنبه", "جمعه"];
Ext.SHDate.y2kYear = 50;
// static functions
Ext.apply(Ext.SHDate, {
    getShortMonthName: function (month) {
        return Ext.SHDate.monthNames[month].substring(0, 3);
    },
    getShortDayName: function (day) {
		return Ext.SHDate.dayNames[day].substring(0, 3);
		
    },
    getMonthNumber: function (name) {
        return Ext.SHDate.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
    },
	formatContainsHourInfo : (function(){
        var stripEscapeRe = /(\\.)/g,
            hourInfoRe = /([gGhHisucUOPZ]|MS)/;
        return function(format){
            return hourInfoRe.test(format.replace(stripEscapeRe, ''));
        };
    })(),
    formatContainsDateInfo : (function(){
        var stripEscapeRe = /(\\.)/g,
            dateInfoRe = /([djzmnYycU]|MS)/;

        return function(format){
            return dateInfoRe.test(format.replace(stripEscapeRe, ''));
        };
    })(),
	clearTime : function(date, clone) {
	
		if (clone) {
			return Ext.SHDate.clearTime(date.clone(date));
		}
		var d = date.getDate();
		date.setHours(0);
		date.setMinutes(0);
		date.setSeconds(0);
		date.setMilliseconds(0);
		if (date.getDate() != d) { 
			for (var hr = 1, c = Ext.SHDate.add(date, Ext.SHDate.HOUR, hr); c.getDate() != d; 
					hr++, c = Ext.SHDate.add(date, Ext.SHDate.HOUR, hr));
			date.setDate(d);
			date.setHours(c.getHours());
		}
		return date;
	},
	add : function(date, interval, value) {
		var d = Ext.SHDate.clone(date),
			Date = Ext.SHDate;
		if (!interval || value === 0) return d;
		switch(interval.toLowerCase()) {
			case Ext.SHDate.MILLI:
				d.setMilliseconds(d.getMilliseconds() + value);
				break;
			case Ext.SHDate.SECOND:
				d.setSeconds(d.getSeconds() + value);
				break;
			case Ext.SHDate.MINUTE:
				d.setMinutes(d.getMinutes() + value);
				break;
			case Ext.SHDate.HOUR:
				d.setHours(d.getHours() + value);
				break;
			case Ext.SHDate.DAY:
				d.setDate(d.getDate() + value);
				break;
			case Ext.SHDate.MONTH:
				/*var day = date.getDate();
				if (day > 28) {
					day = Math.min(day, Ext.SHDate.getLastDateOfMonth(Ext.SHDate.add(Ext.SHDate.getFirstDateOfMonth(date), 'mo', value)).getDate());
				}
				d.setDate(day);*/
				d.setMonth(date.getMonth() + value);
				if(d.day > Ext.SHDate.daysInMonth[ d.month-1 ])
					d.day = Ext.SHDate.daysInMonth[ d.month-1 ];
				break;
			case Ext.SHDate.YEAR:
				d.setFullYear(date.getFullYear() + value);
				break;
		}
		return d;
	},
	clone : function(date) {
		obj = new Ext.SHDate(date.year, date.month, date.day);
		obj.XDate = new Date(date.XDate.getTime());
		return obj;
		//return new Ext.SHDate(date.year, date.month, date.day);
	},
	getFirstDateOfMonth : function (date) {
		return new Ext.SHDate(date.getFullYear(), date.getMonth(), 1);
	},
	format : function(date, format){
		return date.dateFormat(format);
	},
	getLastDayOfMonth : function (date) {
		var day = (date.getDay() + (Ext.SHDate.daysInMonth[date.getMonth()] - date.getDate())) % 7;
		return (day < 0) ? (day + 7) : day;
	},
	getLastDateOfMonth : function (date) {
		return new Ext.SHDate(date.getFullYear(), date.getMonth(), date.getDaysInMonth());
	}

});
Ext.SHDate.monthNumbers = {
    Jan: 0,
    Feb: 1,
    Mar: 2,
    Apr: 3,
    May: 4,
    Jun: 5,
    Jul: 6,
    Aug: 7,
    Sep: 8,
    Oct: 9,
    Nov: 10,
    Dec: 11
};

Ext.SHDate.MILLI = "ms";
Ext.SHDate.SECOND = "s";
Ext.SHDate.MINUTE = "mi";
Ext.SHDate.HOUR = "h";
Ext.SHDate.DAY = "d";
Ext.SHDate.MONTH = "mo";
Ext.SHDate.YEAR = "y";



Ext.define('Ext.picker.SHMonth', {
    extend: 'Ext.Component',
    requires: ['Ext.XTemplate', 'Ext.util.ClickRepeater', 'Ext.SHDate', 'Ext.button.Button'],
    alias: 'widget.shmonthpicker',
    alternateClassName: 'Ext.SHMonthPicker',

    childEls: [
        'bodyEl', 'prevEl', 'nextEl', 'buttonsEl'
    ],

    renderTpl: [
        '<div id="{id}-bodyEl" class="{baseCls}-body">',
          '<div class="{baseCls}-months">',
              '<tpl for="months">',
                  '<div class="{parent.baseCls}-item {parent.baseCls}-month"><a href="#" hidefocus="on">{.}</a></div>',
              '</tpl>',
          '</div>',
          '<div class="{baseCls}-years">',
              '<div class="{baseCls}-yearnav">',
                  '<button id="{id}-prevEl" class="{baseCls}-yearnav-prev"></button>',
                  '<button id="{id}-nextEl" class="{baseCls}-yearnav-next"></button>',
              '</div>',
              '<tpl for="years">',
                  '<div class="{parent.baseCls}-item {parent.baseCls}-year"><a href="#" hidefocus="on">{.}</a></div>',
              '</tpl>',
          '</div>',
          '<div class="' + Ext.baseCSSPrefix + 'clear"></div>',
        '</div>',
        '<tpl if="showButtons">',
          '<div id="{id}-buttonsEl" class="{baseCls}-buttons"></div>',
        '</tpl>'
    ],
    
    okText: 'OK',
    cancelText: 'Cancel',
    baseCls: Ext.baseCSSPrefix + 'monthpicker',
    showButtons: true,
    width: 178,
    smallCls: Ext.baseCSSPrefix + 'monthpicker-small',
    totalYears: 10,
    yearOffset: 5, 
    monthOffset: 6, 

    initComponent: function(){
        var me = this;

        me.selectedCls = me.baseCls + '-selected';
        me.addEvents(
            'cancelclick',
            'monthclick',
            'monthdblclick',
            'okclick',
            'select',
            'yearclick',
            'yeardblclick'
        );
        if (me.small) {
            me.addCls(me.smallCls);
        }
        me.setValue(me.value);
        me.activeYear = me.getYear(new Ext.SHDate().getFullYear() - 4, -4);
        this.callParent();
    },
    beforeRender: function(){
        var me = this,
            i = 0,
            months = [],
            shortName = Ext.SHDate.getShortMonthName,
            monthLen = me.monthOffset;

        me.callParent();

        for (; i < monthLen; ++i) {
            months.push(shortName(i), shortName(i + monthLen));
        }

        Ext.apply(me.renderData, {
            months: months,
            years: me.getYears(),
            showButtons: me.showButtons
        });
    },
    afterRender: function(){
        var me = this,
            body = me.bodyEl,
            buttonsEl = me.buttonsEl;

        me.callParent();

        me.mon(body, 'click', me.onBodyClick, me);
        me.mon(body, 'dblclick', me.onBodyClick, me);

        
        me.years = body.select('.' + me.baseCls + '-year a');
        me.months = body.select('.' + me.baseCls + '-month a');

        if (me.showButtons) {
            me.okBtn = new Ext.button.Button({
                text: me.okText,
                renderTo: buttonsEl,
                handler: me.onOkClick,
                scope: me
            });
            me.cancelBtn = new Ext.button.Button({
                text: me.cancelText,
                renderTo: buttonsEl,
                handler: me.onCancelClick,
                scope: me
            });
        }

        me.backRepeater = new Ext.util.ClickRepeater(me.prevEl, {
            handler: Ext.Function.bind(me.adjustYear, me, [-me.totalYears])
        });

        me.prevEl.addClsOnOver(me.baseCls + '-yearnav-prev-over');
        me.nextRepeater = new Ext.util.ClickRepeater(me.nextEl, {
            handler: Ext.Function.bind(me.adjustYear, me, [me.totalYears])
        });
        me.nextEl.addClsOnOver(me.baseCls + '-yearnav-next-over');
        me.updateBody();
    },
    setValue: function(value){
        var me = this,
            active = me.activeYear,
            offset = me.monthOffset,
            year,
            index;

        if (!value) {
            me.value = [null, null];
        } else if (Ext.isSHDate(value)) {
            me.value = [value.getMonth(), value.getFullYear()];
        } else {
            me.value = [value[0], value[1]];
        }

        if (me.rendered) {
            year = me.value[1];
            if (year !== null) {
                if ((year < active || year > active + me.yearOffset)) {
                    me.activeYear = year - me.yearOffset + 1;
                }
            }
            me.updateBody();
        }

        return me;
    },
    getValue: function(){
        return this.value;
    },
    hasSelection: function(){
        var value = this.value;
        return value[0] !== null && value[1] !== null;
    },
    getYears: function(){
        var me = this,
            offset = me.yearOffset,
            start = me.activeYear, 
            end = start + offset,
            i = start,
            years = [];

        for (; i < end; ++i) {
            years.push(i, i + offset);
        }

        return years;
    },
    updateBody: function(){
        var me = this,
            years = me.years,
            months = me.months,
            yearNumbers = me.getYears(),
            cls = me.selectedCls,
            value = me.getYear(null),
            month = me.value[0],
            monthOffset = me.monthOffset,
            year;

        if (me.rendered) {
            years.removeCls(cls);
            months.removeCls(cls);
            years.each(function(el, all, index){
                year = yearNumbers[index];
                el.dom.innerHTML = year;
                if (year == value) {
                    el.dom.className = cls;
                }
            });
            if (month && month !== null) {
                if (month < monthOffset) {
                    month = month * 2;
                } else {
                    month = (month - monthOffset) * 2 + 1;
                }
                months.item(month).addCls(cls);
            }
        }
    },
    getYear: function(defaultValue, offset) {
        var year = this.value[1];
        offset = offset || 0;
        return year === null ? defaultValue : year + offset;
    },
    onBodyClick: function(e, t) {
        var me = this,
            isDouble = e.type == 'dblclick';

        if (e.getTarget('.' + me.baseCls + '-month')) {
            e.stopEvent();
            me.onMonthClick(t, isDouble);
        } else if (e.getTarget('.' + me.baseCls + '-year')) {
            e.stopEvent();
            me.onYearClick(t, isDouble);
        }
    },
    adjustYear: function(offset){
        if (typeof offset != 'number') {
            offset = this.totalYears;
        }
        this.activeYear += offset;
        this.updateBody();
    },
    onOkClick: function(){
        this.fireEvent('okclick', this, this.value);
    },
    onCancelClick: function(){
        this.fireEvent('cancelclick', this);
    },
    onMonthClick: function(target, isDouble){
        var me = this;
        me.value[0] = me.resolveOffset(me.months.indexOf(target), me.monthOffset);
        me.updateBody();
        me.fireEvent('month' + (isDouble ? 'dbl' : '') + 'click', me, me.value);
        me.fireEvent('select', me, me.value);
    },
    onYearClick: function(target, isDouble){
        var me = this;
        me.value[1] = me.activeYear + me.resolveOffset(me.years.indexOf(target), me.yearOffset);
        me.updateBody();
        me.fireEvent('year' + (isDouble ? 'dbl' : '') + 'click', me, me.value);
        me.fireEvent('select', me, me.value);

    },
    resolveOffset: function(index, offset){
        if (index % 2 === 0) {
            return (index / 2);
        } else {
            return offset + Math.floor(index / 2);
        }
    },
    beforeDestroy: function(){
        var me = this;
        me.years = me.months = null;
        Ext.destroyMembers(me, 'backRepeater', 'nextRepeater', 'okBtn', 'cancelBtn');
        me.callParent();
    }
});

Ext.define('Ext.picker.SHDate', {
    extend: 'Ext.Component',
    requires: [
        'Ext.XTemplate',
        'Ext.button.Button',
        'Ext.button.Split',
        'Ext.util.ClickRepeater',
        'Ext.util.KeyNav',
        'Ext.EventObject',
        'Ext.fx.Manager',
        'Ext.picker.SHMonth'
    ],
    alias: 'widget.shdatepicker',
    alternateClassName: 'Ext.SHDatePicker',

    childEls: [
        'inner', 'eventEl', 'prevEl', 'nextEl', 'middleBtnEl', 'footerEl'
    ],

    renderTpl: [
        '<div id="{id}-inner">',
            '<div role="presentation" class="{baseCls}-header">',
                '<div class="{baseCls}-prev"><a id="{id}-prevEl" href="#" role="button" title="{prevText}"></a></div>',
                '<div class="{baseCls}-month" id="{id}-middleBtnEl"></div>',
                '<div class="{baseCls}-next"><a id="{id}-nextEl" href="#" role="button" title="{nextText}"></a></div>',
            '</div>',
            '<table id="{id}-eventEl" class="{baseCls}-inner" cellspacing="0" role="presentation">',
                '<thead role="presentation"><tr role="presentation">',
                    '<tpl for="dayNames">',
                        '<th role="columnheader" title="{.}"><span>{.:this.firstInitial}</span></th>',
                    '</tpl>',
                '</tr></thead>',
                '<tbody role="presentation"><tr role="presentation">',
                    '<tpl for="days">',
                        '{#:this.isEndOfWeek}',
                        '<td role="gridcell" id="{[Ext.id()]}">',
                            '<a role="presentation" href="#" hidefocus="on" class="{parent.baseCls}-date" tabIndex="1">',
                                '<em role="presentation"><span role="presentation"></span></em>',
                            '</a>',
                        '</td>',
                    '</tpl>',
                '</tr></tbody>',
            '</table>',
            '<tpl if="showToday">',
                '<div id="{id}-footerEl" role="presentation" class="{baseCls}-footer"></div>',
            '</tpl>',
        '</div>',
        {
            firstInitial: function(value) {
                return value.substr(0,1);
            },
            isEndOfWeek: function(value) {
                
                
                value--;
                var end = value % 7 === 0 && value !== 0;
                return end ? '</tr><tr role="row">' : '';
            },
            longDay: function(value){
                return value.format(this.longDayFormat);
            }
        }
    ],
    todayText : 'امروز',
    todayTip : '',
    minText : 'This date is before the minimum date',
    maxText : 'This date is after the maximum date',
    disabledDaysText : 'Disabled',
    disabledDatesText : 'Disabled',
    nextText : 'Next Month (Control+Right)',
    prevText : 'Previous Month (Control+Left)',
    monthYearText : 'Choose a month (Control+Up/Down to move years)',
    startDay : 0,
    showToday : true,
    disableAnim: false,
    baseCls: Ext.baseCSSPrefix + 'datepicker',
    longDayFormat: 'F d, Y',
    focusOnShow: false,
    focusOnSelect: true,
    width: 178,
    initHour: 12, 
    numDays: 42,
    
    initComponent : function() {
        var me = this;

        me.selectedCls = me.baseCls + '-selected';
        me.disabledCellCls = me.baseCls + '-disabled';
        me.prevCls = me.baseCls + '-prevday';
        me.activeCls = me.baseCls + '-active';
        me.nextCls = me.baseCls + '-prevday';
        me.todayCls = me.baseCls + '-today';
        Ext.SHDate.dayNames = Ext.SHDate.dayNames.slice(me.startDay).concat(Ext.SHDate.dayNames.slice(0, me.startDay));
        this.callParent();

        me.value = me.value ? Ext.SHDate.clearTime(me.value,true) : Ext.SHDate.clearTime(new Ext.SHDate());

        me.addEvents(
            
            'select'
        );

        me.initDisabledDays();
    },
    beforeRender: function () {
        
        var me = this,
            days = new Array(me.numDays);

        me.callParent();

        Ext.applyIf(me, {
            renderData: {}
        });
		
		me.dayNames = Ext.SHDate.dayNames;

        Ext.apply(me.renderData, {
            dayNames: me.dayNames,
            value: me.value,
            showToday: me.showToday,
            prevText: me.prevText,
            nextText: me.nextText,
            days: days
        });
        me.getTpl('renderTpl').longDayFormat = me.longDayFormat;
    },
    onRender : function(container, position){
        var me = this,
            today = new Ext.SHDate().format(me.format);

        me.callParent(arguments);

        me.el.unselectable();

        me.cells = me.eventEl.select('tbody td');
        me.textNodes = me.eventEl.query('tbody td span');

        me.monthBtn = new Ext.button.Split({
            ownerCt: me,
            ownerLayout: me.componentLayout,
            text: '',
            tooltip: me.monthYearText,
            renderTo: me.middleBtnEl
        });
        me.todayBtn = new Ext.button.Button({
            renderTo: me.footerEl,
            text: Ext.String.format(me.todayText, today),
            tooltip: Ext.String.format(me.todayTip, today),
            handler: me.selectToday,
            scope: me
        });
    },
    initEvents: function(){
        var me = this,
            eDate = Ext.SHDate,
            day = eDate.DAY;

        this.callParent();

        me.prevRepeater = new Ext.util.ClickRepeater(me.prevEl, {
            handler: me.showPrevMonth,
            scope: me,
            preventDefault: true,
            stopDefault: true
        });

        me.nextRepeater = new Ext.util.ClickRepeater(me.nextEl, {
            handler: me.showNextMonth,
            scope: me,
            preventDefault:true,
            stopDefault:true
        });

        me.keyNav = new Ext.util.KeyNav(me.eventEl, Ext.apply({
            scope: me,
            left : function(e){
                if(e.ctrlKey){
                    me.showPrevMonth();
                }else{
                    me.update(me.activeDate.add(day, -1));
                }
            },

            right : function(e){
                if(e.ctrlKey){
                    me.showNextMonth();
                }else{
                    me.update(me.activeDate.add(day, 1));
                }
            },

            up : function(e){
                if(e.ctrlKey){
                    me.showNextYear();
                }else{
                    me.update(me.activeDate.add(day, -7));
                }
            },

            down : function(e){
                if(e.ctrlKey){
                    me.showPrevYear();
                }else{
                    me.update(me.activeDate.add(day, 7));
                }
            },
            pageUp : me.showNextMonth,
            pageDown : me.showPrevMonth,
            enter : function(e){
                e.stopPropagation();
                return true;
            }
        }, me.keyNavConfig));

        if(me.showToday){
            me.todayKeyListener = me.eventEl.addKeyListener(Ext.EventObject.SPACE, me.selectToday,  me);
        }
        me.mon(me.eventEl, 'mousewheel', me.handleMouseWheel, me);
        me.mon(me.eventEl, 'click', me.handleDateClick,  me, {delegate: 'a.' + me.baseCls + '-date'});
        me.mon(me.monthBtn, 'click', me.showMonthPicker, me);
        me.mon(me.monthBtn, 'arrowclick', me.showMonthPicker, me);
        me.update(me.value);
    },
    initDisabledDays : function(){
        var me = this,
            dd = me.disabledDates,
            re = '(?:',
            len;

        if(!me.disabledDatesRE && dd){
                len = dd.length - 1;

            Ext.each(dd, function(d, i){
                re += Ext.isSHDate(d) ? '^' + Ext.String.escapeRegex(d.dateFormat(me.format)) + '$' : dd[i];
                if(i != len){
                    re += '|';
                }
            }, me);
            me.disabledDatesRE = new RegExp(re + ')');
        }
    },
    setDisabledDates : function(dd){
        var me = this;

        if(Ext.isArray(dd)){
            me.disabledDates = dd;
            me.disabledDatesRE = null;
        }else{
            me.disabledDatesRE = dd;
        }
        me.initDisabledDays();
        me.update(me.value, true);
        return me;
    },
    setDisabledDays : function(dd){
        this.disabledDays = dd;
        return this.update(this.value, true);
    },
    setMinDate : function(dt){
        this.minDate = dt;
        return this.update(this.value, true);
    },
    setMaxDate : function(dt){
        this.maxDate = dt;
        return this.update(this.value, true);
    },
    setValue : function(value){
        this.value = Ext.SHDate.clearTime(value,true);
        return this.update(this.value);
    },
    getValue : function(){
        return this.value;
    },
    focus : function(){
        this.update(this.activeDate);
    },
    onEnable: function(){
        this.callParent();
        this.setDisabledStatus(false);
        this.update(this.activeDate);

    },
    onDisable : function(){
        this.callParent();
        this.setDisabledStatus(true);
    },
    setDisabledStatus : function(disabled){
        var me = this;

        me.keyNav.setDisabled(disabled);
        me.prevRepeater.setDisabled(disabled);
        me.nextRepeater.setDisabled(disabled);
        if (me.showToday) {
            me.todayKeyListener.setDisabled(disabled);
            me.todayBtn.setDisabled(disabled);
        }
    },
    getActive: function(){
        return this.activeDate || this.value;
    },
    runAnimation: function(isHide){
        var picker = this.monthPicker,
            options = {
                duration: 200,
                callback: function(){
                    if (isHide) {
                        picker.hide();
                    } else {
                        picker.show();
                    }
                }
            };

        if (isHide) {
            picker.el.slideOut('t', options);
        } else {
            picker.el.slideIn('t', options);
        }
    },
    hideMonthPicker : function(animate){
        var me = this,
            picker = me.monthPicker;

        if (picker) {
            if (me.shouldAnimate(animate)) {
                me.runAnimation(true);
            } else {
                picker.hide();
            }
        }
        return me;
    },
    showMonthPicker : function(animate){
        var me = this,
            picker;
        
        if (me.rendered && !me.disabled) {
            picker = me.createMonthPicker();
            picker.setValue(me.getActive());
            picker.setSize(me.getSize());
            picker.setPosition(-1, -1);
            if (me.shouldAnimate(animate)) {
                me.runAnimation(false);
            } else {
                picker.show();
            }
        }
        return me;
    },
    shouldAnimate: function(animate){
        return Ext.isDefined(animate) ? animate : !this.disableAnim;
    },
    createMonthPicker: function(){
        var me = this,
            picker = me.monthPicker;

        if (!picker) {
            me.monthPicker = picker = new Ext.picker.SHMonth({
                renderTo: me.el,
                floating: true,
                shadow: false,
                small: me.showToday === false,
                listeners: {
                    scope: me,
                    cancelclick: me.onCancelClick,
                    okclick: me.onOkClick,
                    yeardblclick: me.onOkClick,
                    monthdblclick: me.onOkClick
                }
            });
            if (!me.disableAnim) {
                
                picker.el.setStyle('display', 'none');
            }
            me.on('beforehide', Ext.Function.bind(me.hideMonthPicker, me, [false]));
        }
        return picker;
    },
    onOkClick: function(picker, value){
        var me = this,
            month = value[0],
            year = value[1],
            date = new Ext.SHDate(year, month, me.getActive().getDate());

        if (date.getMonth() !== month) {
            
            date = Ext.SHDate.getLastDateOfMonth(new Ext.SHDate(year, month, 1));
        }
        me.update(date);
        me.hideMonthPicker();
    },
    onCancelClick: function(){
        
        this.selectedUpdate(this.activeDate);
        this.hideMonthPicker();
    },
    showPrevMonth : function(e){
        return this.update(this.activeDate.add(Ext.SHDate.MONTH, -1));
    },
    showNextMonth : function(e){
        return this.update(this.activeDate.add(Ext.SHDate.MONTH, 1));
    },
    showPrevYear : function(){
        this.update(this.activeDate.add(Ext.SHDate.YEAR, -1));
    },
    showNextYear : function(){
        this.update(this.activeDate.add(Ext.SHDate.YEAR, 1));
    },
    handleMouseWheel : function(e){
        e.stopEvent();
        if(!this.disabled){
            var delta = e.getWheelDelta();
            if(delta > 0){
                this.showPrevMonth();
            } else if(delta < 0){
                this.showNextMonth();
            }
        }
    },
    handleDateClick : function(e, t){
        var me = this,
            handler = me.handler;

        e.stopEvent();
        if(!me.disabled && t.dateValue && !Ext.fly(t.parentNode).hasCls(me.disabledCellCls)){
            me.cancelFocus = me.focusOnSelect === false;
            me.setValue(new Ext.SHDate(t.dateValue));
            delete me.cancelFocus;
            me.fireEvent('select', me, me.value);
            if (handler) {
                handler.call(me.scope || me, me, me.value);
            }
            
            
            
            
            me.onSelect();
        }
    },
    onSelect: function() {
        if (this.hideOnSelect) {
             this.hide();
         }
    },
    selectToday : function(){
        var me = this,
            btn = me.todayBtn,
            handler = me.handler;

        if(btn && !btn.disabled){
            me.setValue(Ext.SHDate.clearTime(new Ext.SHDate()));
            me.fireEvent('select', me, me.value);
            if (handler) {
                handler.call(me.scope || me, me, me.value);
            }
            me.onSelect();
        }
        return me;
    },
    selectedUpdate: function(date){
        var me = this,
            t = date.getTime(),
            cells = me.cells,
            cls = me.selectedCls;

        cells.removeCls(cls);
        cells.each(function(c){
            if (c.dom.firstChild.dateValue == t) {
                me.fireEvent('highlightitem', me, c);
                c.addCls(cls);
                if(me.isVisible() && !me.cancelFocus){
                    Ext.fly(c.dom.firstChild).focus(50);
                }
                return false;
            }
        }, this);
    },
    fullUpdate: function(date){
        var me = this,
            cells = me.cells.elements,
            textNodes = me.textNodes,
            disabledCls = me.disabledCellCls,
            eDate = Ext.SHDate,
            i = 0,
            extraDays = 0,
            visible = me.isVisible(),
            //------- jafarkhani -----------
			//sel = +date.clearTime(true).get,
			sel = Ext.SHDate.clearTime(date,true).getTime(),
            //today = +new Ext.SHDate().clearTime(),
			today = Ext.SHDate.clearTime(new Ext.SHDate()).getTime(),
            min = me.minDate ? Ext.SHDate.clearTime(me.minDate,true) : Number.NEGATIVE_INFINITY,
            max = me.maxDate ? Ext.SHDate.clearTime(me.maxDate,true) : Number.POSITIVE_INFINITY,
            ddMatch = me.disabledDatesRE,
            ddText = me.disabledDatesText,
            ddays = me.disabledDays ? me.disabledDays.join('') : false,
            ddaysText = me.disabledDaysText,
            format = me.format,
            days = date.getDaysInMonth(),
            firstOfMonth = Ext.SHDate.getFirstDateOfMonth(date),
            startingPos = firstOfMonth.getDay() - me.startDay,
            previousMonth = date.add(eDate.MONTH, -1),
            longDayFormat = me.longDayFormat,
            prevStart,
            current,
            disableToday,
            tempDate,
            setCellClass,
            html,
            cls,
            formatValue,
            value;

        if (startingPos < 0) {
            startingPos += 7;
        }

        days += startingPos;
        prevStart = previousMonth.getDaysInMonth() - startingPos;
        current = new Ext.SHDate(previousMonth.getFullYear(), previousMonth.getMonth(), prevStart, me.initHour);

        if (me.showToday) {
            tempDate = Ext.SHDate.clearTime(new Ext.SHDate());
            disableToday = (tempDate < min || tempDate > max ||
                (ddMatch && format && ddMatch.test(tempDate.dateFormat(format))) ||
                (ddays && ddays.indexOf(tempDate.getDay()) != -1));

            if (!me.disabled) {
                me.todayBtn.setDisabled(disableToday);
                me.todayKeyListener.setDisabled(disableToday);
            }
        }

        setCellClass = function(cell){
			//----------- jafarkhani -----------
            //value = +current.clearTime(true);
			value = Ext.SHDate.clearTime(current,true).getTime();
            cell.title = current.format(longDayFormat);
            
            cell.firstChild.dateValue = value;
            if(value == today){
                cell.className += ' ' + me.todayCls;
                cell.title = me.todayText;
            }
            if(value == sel){
                cell.className += ' ' + me.selectedCls;
                me.fireEvent('highlightitem', me, cell);
                if (visible && me.floating) {
                    Ext.fly(cell.firstChild).focus(50);
                }
            }
            
            if(value < min) {
                cell.className = disabledCls;
                cell.title = me.minText;
                return;
            }
            if(value > max) {
                cell.className = disabledCls;
                cell.title = me.maxText;
                return;
            }
            if(ddays){
                if(ddays.indexOf(current.getDay()) != -1){
                    cell.title = ddaysText;
                    cell.className = disabledCls;
                }
            }
            if(ddMatch && format){
                formatValue = current.dateFormat(format);
                if(ddMatch.test(formatValue)){
                    cell.title = ddText.replace('%0', formatValue);
                    cell.className = disabledCls;
                }
            }
        };

        for(; i < me.numDays; ++i) {
            if (i < startingPos) {
                html = (++prevStart);
                cls = me.prevCls;
            } else if (i >= days) {
                html = (++extraDays);
                cls = me.nextCls;
            } else {
                html = i - startingPos + 1;
                cls = me.activeCls;
            }
            textNodes[i].innerHTML = html;
            cells[i].className = cls;
            current.setDate(current.getDate() + 1);
            setCellClass(cells[i]);
        }

        me.monthBtn.setText(Ext.SHDate.monthNames[date.getMonth()] + ' ' + date.getFullYear());
    },
    update : function(date, forceRefresh){
        var me = this,
            active = me.activeDate;

        if (me.rendered) {
            me.activeDate = date;
            if(!forceRefresh && active && me.el && active.getMonth() == date.getMonth() && active.getFullYear() == date.getFullYear()){
                me.selectedUpdate(date, active);
            } else {
                me.fullUpdate(date, active);
            }
        }
        return me;
    },
    beforeDestroy : function() {
        var me = this;

        if (me.rendered) {
            Ext.destroy(
                me.todayKeyListener,
                me.keyNav,
                me.monthPicker,
                me.monthBtn,
                me.nextRepeater,
                me.prevRepeater,
                me.todayBtn
            );
            delete me.textNodes;
            delete me.cells.elements;
        }
        me.callParent();
    },
    onShow: function() {
        this.callParent(arguments);
        if (this.focusOnShow) {
            this.focus();
        }
    }
});

Ext.define('Ext.form.field.SHDate', {
    extend:'Ext.form.field.Picker',
    alias: 'widget.shdatefield',
    requires: ['Ext.picker.SHDate'],
    alternateClassName: ['Ext.form.SHDateField', 'Ext.form.SHDate'],
    format : "Y/m/d",
    altFormats : "Y/m/d|y/m/d|Y-m-d|y-m-d|d/m/Y|d/m/y|d-m-Y|d-m-y|n/j/Y|n/j/y|m/j/y|n/d/y|m/j/Y|n/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d|n-j|n/j",
    disabledDaysText : "Disabled",
    disabledDatesText : "Disabled",
    minText : "The date in this field must be equal to or after {0}",
    maxText : "The date in this field must be equal to or before {0}",
    invalidText : "{0} is not a valid date - it must be in the format {1}",
    triggerCls : Ext.baseCSSPrefix + 'form-date-trigger',
    showToday : true,
    initTime: '12', 
    initTimeFormat: 'H',
    matchFieldWidth: false,
    startDay: 0,

    initComponent : function(){
        var me = this,
            isString = Ext.isString,
            min, max;

        min = me.minValue;
        max = me.maxValue;
        if(isString(min)){
            me.minValue = me.parseDate(min);
        }
        if(isString(max)){
            me.maxValue = me.parseDate(max);
        }
        me.disabledDatesRE = null;
        me.initDisabledDays();

        me.callParent();
    },
    initValue: function() {
        var me = this,
            value = me.value;

        
        if (Ext.isString(value)) {
            me.value = me.rawToValue(value);
        }

        me.callParent();
    },
    initDisabledDays : function(){
        if(this.disabledDates){
            var dd = this.disabledDates,
                len = dd.length - 1,
                re = "(?:";

            Ext.each(dd, function(d, i){
                re += Ext.isSHDate(d) ? '^' + Ext.String.escapeRegex(d.dateFormat(this.format)) + '$' : dd[i];
                if (i !== len) {
                    re += '|';
                }
            }, this);
            this.disabledDatesRE = new RegExp(re + ')');
        }
    },
    setDisabledDates : function(dd){
        var me = this,
            picker = me.picker;

        me.disabledDates = dd;
        me.initDisabledDays();
        if (picker) {
            picker.setDisabledDates(me.disabledDatesRE);
        }
    },
    setDisabledDays : function(dd){
        var picker = this.picker;

        this.disabledDays = dd;
        if (picker) {
            picker.setDisabledDays(dd);
        }
    },
    setMinValue : function(dt){
        var me = this,
            picker = me.picker,
            minValue = (Ext.isString(dt) ? me.parseDate(dt) : dt);

        me.minValue = minValue;
        if (picker) {
            picker.minText = Ext.String.format(me.minText, me.formatDate(me.minValue));
            picker.setMinDate(minValue);
        }
    },
    setMaxValue : function(dt){
        var me = this,
            picker = me.picker,
            maxValue = (Ext.isString(dt) ? me.parseDate(dt) : dt);

        me.maxValue = maxValue;
        if (picker) {
            picker.maxText = Ext.String.format(me.maxText, me.formatDate(me.maxValue));
            picker.setMaxDate(maxValue);
        }
    },
    getErrors: function(value) {
        var me = this,
            format = Ext.String.format,
            errors = me.callParent(arguments),
            disabledDays = me.disabledDays,
            disabledDatesRE = me.disabledDatesRE,
            minValue = me.minValue,
            maxValue = me.maxValue,
            len = disabledDays ? disabledDays.length : 0,
            i = 0,
            svalue,
            fvalue,
            day,
            time;

        value = me.formatDate(value || me.processRawValue(me.getRawValue()));

        if (value === null || value.length < 1) { 
             return errors;
        }

        svalue = value;
        value = me.parseDate(value);
        if (!value) {
            errors.push(format(me.invalidText, svalue, me.format));
            return errors;
        }

        time = value.getTime();
        if (minValue && time < Ext.SHDate.clearTime(minValue).getTime()) {
            errors.push(format(me.minText, me.formatDate(minValue)));
        }

        if (maxValue && time > Ext.SHDate.clearTime(maxValue).getTime()) {
            errors.push(format(me.maxText, me.formatDate(maxValue)));
        }

        if (disabledDays) {
            day = value.getDay();

            for(; i < len; i++) {
                if (day === disabledDays[i]) {
                    errors.push(me.disabledDaysText);
                    break;
                }
            }
        }

        fvalue = me.formatDate(value);
        if (disabledDatesRE && disabledDatesRE.test(fvalue)) {
            errors.push(format(me.disabledDatesText, fvalue));
        }

        return errors;
    },
    rawToValue: function(rawValue) {
        return this.parseDate(rawValue) || rawValue || null;
    },
    valueToRaw: function(value) {
        return this.formatDate(this.parseDate(value));
    },
    safeParse : function(value, format) {
        var me = this,
            parsedDate,
            result = null;

        if (Ext.SHDate.formatContainsHourInfo(format)) {
            
            result = Ext.SHDate.parseDate(value, format);
        } else {
            
            parsedDate = Ext.SHDate.parseDate(value + ' ' + me.initTime, format + ' ' + me.initTimeFormat);
            if (parsedDate) {
                result = Ext.SHDate.clearTime(parsedDate);
            }
        }
        return result;
    },
    getSubmitValue: function() {
        var format = this.submitFormat || this.format,
            value = this.getValue();

        return value ? value.format(format) : '';
    },
    parseDate : function(value) {
        if(!value || Ext.isSHDate(value)){
            return value;
        }

        var me = this,
            val = me.safeParse(value, me.format),
            altFormats = me.altFormats,
            altFormatsArray = me.altFormatsArray,
            i = 0,
            len;

        if (!val && altFormats) {
            altFormatsArray = altFormatsArray || altFormats.split('|');
            len = altFormatsArray.length;
            for (; i < len && !val; ++i) {
                val = me.safeParse(value, altFormatsArray[i]);
            }
        }
        return val;
    },
    formatDate : function(date){
        return Ext.isSHDate(date) ? date.dateFormat(this.format) : date;
    },
    createPicker: function() {
        var me = this,
            format = Ext.String.format;

        return new Ext.picker.SHDate({
            pickerField: me,
            ownerCt: me.ownerCt,
            renderTo: document.body,
            floating: true,
            hidden: true,
            focusOnShow: true,
            minDate: me.minValue,
            maxDate: me.maxValue,
            disabledDatesRE: me.disabledDatesRE,
            disabledDatesText: me.disabledDatesText,
            disabledDays: me.disabledDays,
            disabledDaysText: me.disabledDaysText,
            format: me.format,
            showToday: me.showToday,
            startDay: me.startDay,
            minText: format(me.minText, me.formatDate(me.minValue)),
            maxText: format(me.maxText, me.formatDate(me.maxValue)),
            listeners: {
                scope: me,
                select: me.onSelect
            },
            keyNavConfig: {
                esc: function() {
                    me.collapse();
                }
            }
        });
    },
    onSelect: function(m, d) {
        var me = this;

        me.setValue(d);
        me.fireEvent('select', me, d);
        me.collapse();
    },
    onExpand: function() {
        var value = this.getValue();
        this.picker.setValue(Ext.isSHDate(value) ? value : new Ext.SHDate());
    },
    onCollapse: function() {
        this.focus(false, 60);
    },
    getRawValue: function() {
		var v = this.callParent();
		//---------------- jafarkhani at 90.03 -----------------
		if(v != "")
		{
			var seperator;
			if(v.indexOf("/") != -1)
				seperator = "/";
			if(v.indexOf("-") != -1)
				seperator = "-";
			var arr = v.split(seperator);
			if(arr.length == 3)
			{
				if(arr[0]*1 <= 31)
				{
					 if(arr[0].length == 1)
						 arr[0] = "0" + arr[0];
				}			
				else if(arr[0].length == 2)
					arr[0] = "13" + arr[0];
				
				if(arr[1] <= 12 && arr[1].length == 1)
					arr[1] = "0" + arr[1];
				
				if(arr[2]*1 <= 31)
				{
					 if(arr[2].length == 1)
						 arr[2] = "0" + arr[2];
				}
				else if(arr[2].length == 2)
					arr[2] = "13" + arr[2];
				
				v = arr[0] + seperator + arr[1] + seperator + arr[2];
			}
		}
		//-------------------------------------------------------
		return v;
    },
	beforeBlur : function(){
		var value = this.getRawValue();
		if(value == "")
			return;
        var me = this,
            v = me.parseDate(value),
            focusTask = me.focusTask;

        if (focusTask) {
            focusTask.cancel();
        }

        if (v) {
            me.setValue(v);
        }
    }
    
});

//***********************************************************
//**************** send form to store ***********************
//***********************************************************

Ext.apply(Ext.data.JsonP,{
	createScript: function(url, params, options) {
       
		var script = document.createElement('script');
		script.setAttribute("type", "text/javascript");
		script.setAttribute("async", true);
		
		if(this.form && this.form != "")
		{
			Ext.Ajax.request({
				url : Ext.urlAppend(url, Ext.Object.toQueryString(params)),
				method : "POST",
				form : this.form,
				success : function(response, o)
				{
					script.text = response.responseText;
				}
			});
		}
		else
			script.setAttribute("src", Ext.urlAppend(url, Ext.Object.toQueryString(params)));
               
        return script;
    }
});
Ext.override(Ext.form.action.Action, { timeout: 600});
Ext.override(Ext.form.Basic, { timeout: 600 });

Ext.override(Ext.data.JsonP,{timeout: 600000});
Ext.override(Ext.data.Connection,{timeout: 600000});
Ext.override(Ext.data.proxy.Server,{timeout: 600000});
Ext.override(Ext.data.proxy.Ajax, { timeout:600000 });

Ext.override(Ext.data.proxy.JsonP,{
	doRequest: function(operation, callback, scope) {
        
        var me      = this,
            writer  = me.getWriter(),
            request = me.buildRequest(operation),
            params = request.params;

        if (operation.allowWrite()) {
            request = writer.write(request);
        }

        
        Ext.apply(request, {
            callbackKey: me.callbackKey,
            timeout: me.timeout,
            scope: me,
            disableCaching: false, 
            callback: me.createRequestCallback(request, operation, callback, scope)
        });

        
        if (me.autoAppendParams) {
            request.params = {};
        }

		Ext.data.JsonP.form = this.form;
        request.jsonp = Ext.data.JsonP.request(request);
        
        request.params = params;
        operation.setStarted();
        me.lastRequest = request;

        return request;
    }
});

//***********************************************************
//************* adding applyTo property *********************
//***********************************************************

Ext.override(Ext.util.Renderable, {
	 render: function(container, position) {
        var me = this,
            el = me.el && (me.el = Ext.get(me.el)), 
            tree;
        Ext.suspendLayouts();
        container = me.initContainer(container);
        position = me.getInsertPosition(position);

        if (!el) {
            tree = me.getRenderTree();
            if (position) {
                el = Ext.DomHelper.insertBefore(position, tree);
            } else {
				//------------- jafarkhani ------------
                if(me.applyTo)
					el = Ext.DomHelper.insertAfter(Ext.get(me.applyTo).dom, tree);
				else
					el = Ext.DomHelper.append(container, tree);
            }
            me.wrapPrimaryEl(el);
        } else {
            
            me.initStyles(el);
            if (me.allowDomMove !== false) {
                
                if (position) {
                    container.dom.insertBefore(el.dom, position);
                } else {
                    container.dom.appendChild(el.dom);
                }
            }
        }

        me.finishRender();
        Ext.resumeLayouts(!container.isDetachedBody);
    }    
});

Ext.override(Ext.AbstractComponent, {
	constructor : function(config) {
        var me = this,
            i, len, xhooks;

        if (config) {
            Ext.apply(me, config);

            xhooks = me.xhooks;
            if (xhooks) {
                me.hookMethods(xhooks);
                delete me.xhooks;
            }
        } else {
            config = {};
        }
        me.initialConfig = config;
        me.mixins.elementCt.constructor.call(me);

        me.addEvents('beforeactivate','activate','beforedeactivate','deactivate','added','disable','enable','beforeshow','show','beforehide',
            'hide','removed','beforerender','render','afterrender','beforedestroy','destroy','resize', 'move','focus','blur');
        me.getId();
        me.setupProtoEl();
        me.mons = [];
        me.renderData = me.renderData || {};
        me.renderSelectors = me.renderSelectors || {};
        if (me.plugins) {
            me.plugins = [].concat(me.plugins);
            me.constructPlugins();
        }
        me.initComponent();
        Ext.ComponentManager.register(me);
        me.mixins.observable.constructor.call(me);
        me.mixins.state.constructor.call(me, config);
        this.addStateEvents('resize');
        if (me.plugins) {
            me.plugins = [].concat(me.plugins);
            for (i = 0, len = me.plugins.length; i < len; i++) {
                me.plugins[i] = me.initPlugin(me.plugins[i]);
            }
        }
        me.loader = me.getLoader();
		//----------- jafarkhani -------------
		if(me.applyTo){
			
			var elem;
			if(Ext.isString(me.applyTo))
				elem = Ext.get(me.applyTo);
			else
			{
				elem = Ext.get(me.applyTo);
				if(elem)
					elem = elem.dom;
			}
			if(!elem)
				return;
				
			me.name = elem.name;
			try{
				me.setValue(elem.value);
				me.setReadOnly(elem.readOnly);
				me.setDisabled(elem.disabled);
			}
			catch(e){}
			var parent = elem.parentNode;
			parent.removeChild(elem);
			//me.inputId = elem.id;
			me.render(parent);			
		}
        else if (me.renderTo) {
            me.render(me.renderTo);
        }
        if (me.autoShow) {
            me.show();
        }
    }
});

//***********************************************************
//************* new properties to Elements ******************
//***********************************************************

Ext.IMAGE_URL = "/generalUI/ext4/resources/themes";
Ext.apply(Ext.dom.Element.prototype , {
	clear: function () {
        elems = this.dom.getElementsByTagName("input");
        for (i = 0; i < elems.length; i++) {
            if (elems[i].type == "text") elems[i].value = "";
            if (elems[i].type == "checkbox") elems[i].checked = false;
        }
        elems = this.dom.getElementsByTagName("select");
        for (i = 0; i < elems.length; i++) {
            elems[i].selectedIndex = 0;
        }
        elems = this.dom.getElementsByTagName("textarea");
        for (i = 0; i < elems.length; i++) {
            elems[i].value = "";
        }
    },
    disable: function () {

		if(this.dom)
			this.dom.disabled = true;

        elems = this.dom.getElementsByTagName("input");
        for (i = 0; i < elems.length; i++) {
            if (elems[i].type == "text") elems[i].readOnly = true;
            else elems[i].disabled = true;
        }
        elems = this.dom.getElementsByTagName("select");
        for (i = 0; i < elems.length; i++) {
            elems[i].disabled = true;
        }
        elems = this.dom.getElementsByTagName("textarea");
        for (i = 0; i < elems.length; i++) {
            elems[i].readOnly = true;
        }

		if(Ext.isArray(Ext.ComponentMgr.all.items))
		{
			for(i = 0; i < Ext.ComponentMgr.all.items.length; i++)
			{
				if(Ext.ComponentMgr.all.items[i].isChildOf(this.dom))
					Ext.ComponentMgr.all.items[i].disable();
			}
		}
    },
    enable: function () {

		if(this.dom)
			this.dom.disabled = false;

        elems = this.dom.getElementsByTagName("input");
        for (i = 0; i < elems.length; i++) {
            if (elems[i].type == "text") elems[i].readOnly = false;
            else elems[i].disabled = false;
        }
        elems = this.dom.getElementsByTagName("select");
        for (i = 0; i < elems.length; i++) {
            elems[i].disabled = false;
        }
        elems = this.dom.getElementsByTagName("textarea");
        for (i = 0; i < elems.length; i++) {
            elems[i].readonly = false;
        }
    },
    readonly: function(ExceptionList){

		ExceptionList = arguments.length == 0 ? new Array() : ExceptionList;
		ExceptionList = ExceptionList == "" ? new Array() : ExceptionList;

        var elems = this.dom.getElementsByTagName("input");
		for (i = 0; i < elems.length; )
		{
			if(ExceptionList.find(elems[i].id) != -1 || ExceptionList.find(elems[i].name) != -1)
			{
				i++;
				continue;
			}
			var val = elems[i].value;
			if(elems[i].type == "text")
			{
				Ext.get(elems[i].parentNode).addCls("blueText");

				if(elems[i].nextSibling && elems[i].nextSibling.className && elems[i].nextSibling.className.indexOf("x-form-trigger") != -1)
					elems[i].parentNode.removeChild(elems[i].nextSibling);

				elems[i].parentNode.style.height = elems[i].clientHeight;
				elems[i].parentNode.replaceChild(document.createTextNode(val), elems[i]);
			}
			else if(elems[i].type == "radio")
			{
				var newImg = document.createElement("img");
				newImg.setAttribute("src", Ext.IMAGE_URL + "/icons/" + (elems[i].checked ? "radio-check.png" : "radio-check-off.png"));
				newImg.setAttribute("style", "width:13px;height:13px");
				
				elems[i].parentNode.style.height = elems[i].clientHeight;
				elems[i].parentNode.replaceChild(newImg, elems[i]);
			}
			else if(elems[i].type == "checkbox")
			{
				newImg = document.createElement("img");
				newImg.setAttribute("src", Ext.IMAGE_URL + "/icons/" + (elems[i].checked ? "check.png" : "check-off.png"));
				newImg.setAttribute("style", "width:13px;height:13px");
				
				elems[i].parentNode.style.height = elems[i].clientHeight;
				elems[i].parentNode.replaceChild(newImg, elems[i]);
			}
			else if(elems[i].type == "button")
			{
				//elems[i].parentNode.removeChild(elems[i]);
				elems[i].disabled = true;
				i++;
			}
			else
				i++;
		}
		elems = this.dom.getElementsByTagName("select");
		for (i = 0; i < elems.length; )
		{
			if(ExceptionList.find(elems[i].id) != -1 || ExceptionList.find(elems[i].name) != -1)
			{
				i++;
				continue;
			}
			val = (elems[i].selectedIndex == -1) ? "" : elems[i].options[elems[i].selectedIndex].text;
			Ext.get(elems[i].parentNode).addCls("blueText");

			if(elems[i].nextSibling && elems[i].nextSibling.className && elems[i].nextSibling.className.indexOf("x-form-trigger") != -1)
					elems[i].parentNode.removeChild(elems[i].nextSibling);

			elems[i].parentNode.style.height = elems[i].clientHeight;
			elems[i].parentNode.replaceChild(document.createTextNode(val), elems[i]);
		}

		elems = this.dom.getElementsByTagName("textarea");
		for (i = 0; i < elems.length; )
		{
			if(ExceptionList.find(elems[i].id) != -1 || ExceptionList.find(elems[i].name) != -1)
			{
				i++;
				continue;
			}
			var newSpan = document.createElement("span");
			newSpan.innerHTML = elems[i].innerHTML == "" ? elems[i].value : elems[i].innerHTML;
			Ext.get(elems[i].parentNode).addCls("blueText");
			elems[i].parentNode.replaceChild(newSpan, elems[i]);
		}

		elems = this.dom.getElementsByClassName("x-form-trigger");
		
		var index = 0;
		while(elems.length > index)
		{
			el = elems[index].parentNode.parentNode.firstChild;
			
			if(ExceptionList.find(el.firstChild.id) != -1 ||
				( el.children.length > 0 && ExceptionList.find(el.children[0].name) != -1 ) ||
				( el.children.length > 1 && ExceptionList.find(el.children[1].name) != -1 ) )
			{
				index++;
				continue;
			}
			elems[index].parentNode.removeChild(elems[index]);
		}

	}
});

Ext.apply(Ext.Component.prototype, {
	close: function() {
		if(this.ownerCt)
			this.ownerCt.remove(this);
	},
	isChildOf : function(parentElement){
		var parentID = (parentElement.id) ? parentElement.id : parentElement;
		if(this.getEl() && this.getEl().dom && this.getEl().dom.parentNode && this.getEl().dom.parentNode.id == parentID)
			return true;
		if(this.getEl() && this.getEl().dom && this.getEl().dom.parentNode)
			return Ext.get(this.getEl().dom.parentNode).isChildOf(parentID);
		return false;
	}
});

Ext.apply(Ext.grid.Panel.prototype, {
	getGridColumns : function(){
		return this.headerCt.getGridColumns();
	}
});

Ext.apply(Ext.form.Panel.prototype, {
    setReadOnly : function(flag){
        
        for(i=0; i<this.items.items.length; i++)
        {
            try{this.items.items[i].setReadOnly(flag);}
			catch(e){}
            
        }
    }
});

//***********************************************************
//************* fix autoHeight of Components ****************
//***********************************************************

Ext.override(Ext.AbstractComponent,{
	statics: {
		updateLayout: function (comp, defer) {
			var me = this,
				running = me.runningLayoutContext,
				pending;

			if (running) {
				running.queueInvalidate(comp);
			} else {
				pending = me.pendingLayouts || (me.pendingLayouts = new Ext.layout.Context());
				pending.queueInvalidate(comp);

				if (!defer && !me.layoutSuspendCount && !comp.isLayoutSuspended()) {
					me.flushLayouts();
				}
			}
			
			//----------- jafarkhani -------------
			var el = comp.getEl().dom.parentNode;
			while(el)
			{
				var cmp = Ext.getCmp(el.id);
				if(cmp)
				{
					cmp.updateLayout();
					break;
				}
				el = el.parentNode;
			}
		}
	}
});

//***********************************************************
//********************** RTL ********************************
//***********************************************************

/*Ext.override(Ext.layout.container.boxOverflow.Scroller,{
	 scrollBy: function(delta, animate) {
        this.scrollTo(this.getScrollPosition() - delta, animate);
    },
	scrollTo: function(position, animate) {
        var me = this,
            layout = me.layout,
            names = layout.getNames(),
            oldPosition = me.getScrollPosition(),
            newPosition = Ext.Number.constrain(position, -me.getMaxScrollPosition(), 0);

        if (newPosition != oldPosition && !me.scrolling) {
            delete me.scrollPosition;
            if (animate === undefined) {
                animate = me.animateScroll;
            }

            layout.innerCt.scrollTo(names.right, newPosition, animate ? me.getScrollAnim() : false);
            if (animate) {
                me.scrolling = true;
            } else {
                me.updateScrollButtons();
            }
            me.fireEvent('scroll', me, newPosition, animate ? me.getScrollAnim() : false);
        }
    }
});*/
Ext.apply(Ext.AbstractComponent.prototype,{
	setPosition2 : function(x, y, animate) {
        var me = this,
            pos = me.beforeSetPosition.apply(me, arguments);

        if (pos && me.rendered) {
            
            pos = me.convertPosition(pos);

            if (animate) {
                me.stopAnimation();
                me.animate(Ext.apply({
                    duration: 1000,
                    listeners: {
                        afteranimate: Ext.Function.bind(me.afterSetPosition, me, [pos.left, pos.top])
                    },
                    to: pos
                }, animate));
            } else {
                                
                if (pos.left !== undefined && pos.top !== undefined) {
                    me.el.setRightTop(pos.left, pos.top);
                } else if (pos.left !== undefined) {
                    me.el.setRight(pos.left);
				} else if (pos.top !==undefined) {
                    me.el.setTop(pos.top);
                }
                me.afterSetPosition(pos.left, pos.top);
            }
        }
        return me;
    }
});
Ext.override(Ext.layout.ContextItem, {
	writeProps: function(dirtyProps, flushing) {
        if (!(dirtyProps && typeof dirtyProps == 'object')) {
            return;
        }

        var me = this,
            el = me.el,
            styles = {},
            styleCount = 0, 
            styleInfo = me.styleInfo,
            
            info,
            propName,
            numericValue,
            
            dirtyX = 'x' in dirtyProps,
            dirtyY = 'y' in dirtyProps,
            x = dirtyProps.x,
            y = dirtyProps.y,
            
            width = dirtyProps.width,
            height = dirtyProps.height,
            isBorderBox = me.isBorderBoxValue,
            target = me.target,
            max = Math.max,
            paddingWidth = 0,
            paddingHeight = 0;

        
        if ('displayed' in dirtyProps) {
            el.setDisplayed(dirtyProps.displayed);
        }

        
        for (propName in dirtyProps) {
            if (flushing) {
                me.fireTriggers('domTriggers', propName);
                me.clearBlocks('domBlocks', propName);
                me.flushedProps[propName] = 1;
            }

            info = styleInfo[propName];
            if (info && info.dom) {
                
                if (info.suffix && (numericValue = parseInt(dirtyProps[propName], 10))) {
                    styles[propName] = numericValue + info.suffix;
                }
                
                else {
                    styles[propName] = dirtyProps[propName];
                }
                ++styleCount;
            }
        }

        
        if (dirtyX || dirtyY) {
            if (target.isComponent) {
				if(target.direction && target.direction == "rtl")
				{
					if(target.dock && (target.dock == "right" || target.dock == "left"))
					{
						target.setPosition(x||me.props.x, y||me.props.y);
						styles.right = 'auto';
					}
					else
					{
						target.setPosition2(x||me.props.x, y||me.props.y);
						styles.left = 'auto';
					}
				}
				else
				{
					target.setPosition(x||me.props.x, y||me.props.y);
					styles.right = 'auto';
				}
            } else {
                
                if (dirtyX) {
					if(target.direction && target.direction == "rtl")
					{
						if(target.dock && (target.dock == "right" || target.dock == "left"))
						{
							styles.left = x + 'px';
							styles.right = 'auto';
						}
						else
						{
							styles.right = x + 'px';
							styles.left = 'auto';
						}
					}
					else
					{
						styles.left = x + 'px';
						styles.right = 	'auto';
					}
                    ++styleCount;
                }
                if (dirtyY) {
                    styles.top = y + 'px';
                    ++styleCount;
                }
            }
        }

        
        if (!isBorderBox && (width > 0 || height > 0)) { 
            
            
            if(!me.frameBodyContext) {
                
                paddingWidth = me.paddingInfo.width;
                paddingHeight = me.paddingInfo.height;
            }
            if (width) {
                width = max(parseInt(width, 10) - (me.borderInfo.width + paddingWidth), 0);
                styles.width = width + 'px';
                ++styleCount;
            }
            if (height) {
                height = max(parseInt(height, 10) - (me.borderInfo.height + paddingHeight), 0);
                styles.height = height + 'px';
                ++styleCount;
            }
        }

        
        if (styleCount) {
            el.setStyle(styles);
        }
    }
});
Ext.apply(Ext.dom.Element.prototype, {
	setRightTop: function(right, top) {
        var style = this.dom.style;

        style.right = Ext.dom.Element.addUnits(right);
		style.left = "auto";
        style.top = Ext.dom.Element.addUnits(top);

        return this;
    }
});
Ext.override(Ext.form.field.HtmlEditor,{
	fontFamilies : [
        'nazanin',
		'titr',
        'Courier New',
        'Tahoma',
        'Times New Roman',
        'Verdana'
    ],
    defaultFont: 'nazanin',
	buttonTips : {
        bold : {
            title: 'Bold (Ctrl+B)',
            text: 'Make the selected text bold.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        italic : {
            title: 'Italic (Ctrl+I)',
            text: 'Make the selected text italic.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        underline : {
            title: 'Underline (Ctrl+U)',
            text: 'Underline the selected text.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        increasefontsize : {
            title: 'افزایش اندازه',
            text: 'افزایش اندازه فونت',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        decreasefontsize : {
            title: 'کاهش اندازه',
            text: 'کاهش اندازه فونت',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        backcolor : {
            title: 'رنگ زمینه',
            text: 'تغییر رنگ زمینه متن انتخابی',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        forecolor : {
            title: 'رنگ متن',
            text: 'تغییر رنگ متن انتخابی',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
		justifyfull : {
            title: 'مرتب چین',
            text: '',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        justifyleft : {
            title: 'چپ چین',
            text: 'انتقال متن به سمت چپ',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        justifycenter : {
            title: 'وسط چین',
            text: 'انتقال متن به وسط',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        justifyright : {
            title: 'راست چین',
            text: 'انتقال متن به راست',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        insertunorderedlist : {
            title: 'Bullet List',
            text: 'Start a bulleted list.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        insertorderedlist : {
            title: 'Numbered List',
            text: 'Start a numbered list.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        createlink : {
            title: 'Hyperlink',
            text: 'Make the selected text a hyperlink.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        },
        sourceedit : {
            title: 'Source Edit',
            text: 'Switch to source editing mode.',
            cls: Ext.baseCSSPrefix + 'html-editor-tip'
        }
    },
	getDocMarkup: function() {
        var me = this,
            h = me.iframeEl.getHeight() - me.iframePad * 2;
        return Ext.String.format('<html><head><style type="text/css">body{border:0;margin:0;padding:{0}px;height:{1}px;box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box;cursor:text}</style></head><body dir=rtl></body></html>', me.iframePad, h);
    },

	createToolbar : function(editor){
        var me = this,
            items = [], i,
            tipsEnabled = Ext.tip.QuickTipManager && Ext.tip.QuickTipManager.isEnabled(),
            baseCSSPrefix = Ext.baseCSSPrefix,
            fontSelectItem, toolbar, undef;

        function btn(id, toggle, handler){
            return {
                itemId : id,
                cls : baseCSSPrefix + 'btn-icon',
                iconCls: baseCSSPrefix + 'edit-'+id,
                enableToggle:toggle !== false,
                scope: editor,
                handler:handler||editor.relayBtnCmd,
                clickEvent: 'mousedown',
                tooltip: tipsEnabled ? editor.buttonTips[id] || undef : undef,
                overflowText: editor.buttonTips[id].title || undef,
                tabIndex: -1
            };
        }


        if (me.enableFont && !Ext.isSafari2) {
            fontSelectItem = Ext.widget('component', {
                renderTpl: [
                    '<select id="{id}-selectEl" class="{cls}">',
                        '<tpl for="fonts">',
                            '<option value="{[values.toLowerCase()]}" style="font-family:{.}"<tpl if="values.toLowerCase()==parent.defaultFont"> selected</tpl>>{.}</option>',
                        '</tpl>',
                    '</select>'
                ],
				//------------ added --------------
				direction : "rtl",
				//---------------------------------
                renderData: {
                    cls: baseCSSPrefix + 'font-select',
                    fonts: me.fontFamilies,
                    defaultFont: me.defaultFont
                },
                childEls: ['selectEl'],
                afterRender: function() {
                    me.fontSelect = this.selectEl;
                    Ext.Component.prototype.afterRender.apply(this, arguments);
                },
                onDisable: function() {
                    var selectEl = this.selectEl;
                    if (selectEl) {
                        selectEl.dom.disabled = true;
                    }
                    Ext.Component.prototype.onDisable.apply(this, arguments);
                },
                onEnable: function() {
                    var selectEl = this.selectEl;
                    if (selectEl) {
                        selectEl.dom.disabled = false;
                    }
                    Ext.Component.prototype.onEnable.apply(this, arguments);
                },
                listeners: {
                    change: function() {
                        me.relayCmd('fontname', me.fontSelect.dom.value);
                        me.deferFocus();
                    },
                    element: 'selectEl'
                }
            });

            items.push(
                fontSelectItem,
                '-'
            );
        }

        if (me.enableFormat) {
            items.push(
                btn('bold'),
                btn('italic'),
                btn('underline')
            );
        }

        if (me.enableFontSize) {
            items.push(
                '-',
                btn('increasefontsize', false, me.adjustFont),
                btn('decreasefontsize', false, me.adjustFont)
            );
        }

        if (me.enableColors) {
            items.push(
                '-', {
                    itemId: 'forecolor',
                    cls: baseCSSPrefix + 'btn-icon',
                    iconCls: baseCSSPrefix + 'edit-forecolor',
                    overflowText: editor.buttonTips.forecolor.title,
                    tooltip: tipsEnabled ? editor.buttonTips.forecolor || undef : undef,
                    tabIndex:-1,
                    menu : Ext.widget('menu', {
                        plain: true,
                        items: [{
                            xtype: 'colorpicker',
                            allowReselect: true,
                            focus: Ext.emptyFn,
                            value: '000000',
                            plain: true,
                            clickEvent: 'mousedown',
                            handler: function(cp, color) {
                                me.execCmd('forecolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                                me.deferFocus();
                                this.up('menu').hide();
                            }
                        }]
                    })
                }, {
                    itemId: 'backcolor',
                    cls: baseCSSPrefix + 'btn-icon',
                    iconCls: baseCSSPrefix + 'edit-backcolor',
                    overflowText: editor.buttonTips.backcolor.title,
                    tooltip: tipsEnabled ? editor.buttonTips.backcolor || undef : undef,
                    tabIndex:-1,
                    menu : Ext.widget('menu', {
                        plain: true,
                        items: [{
                            xtype: 'colorpicker',
                            focus: Ext.emptyFn,
                            value: 'FFFFFF',
                            plain: true,
                            allowReselect: true,
                            clickEvent: 'mousedown',
                            handler: function(cp, color) {
                                if (Ext.isGecko) {
                                    me.execCmd('useCSS', false);
                                    me.execCmd('hilitecolor', color);
                                    me.execCmd('useCSS', true);
                                    me.deferFocus();
                                } else {
                                    me.execCmd(Ext.isOpera ? 'hilitecolor' : 'backcolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                                    me.deferFocus();
                                }
                                this.up('menu').hide();
                            }
                        }]
                    })
                }
            );
        }

        if (me.enableAlignments) {
            items.push(
                '-',
				btn('justifyfull'),
                btn('justifyright'),
                btn('justifycenter'),
                btn('justifyleft')
            );
        }

        if (!Ext.isSafari2) {
            if (me.enableLinks) {
                items.push(
                    '-',
                    btn('createlink', false, me.createLink)
                );
            }

            if (me.enableLists) {
                items.push(
                    '-',
                    btn('insertorderedlist'),
                    btn('insertunorderedlist')
                );
            }
            if (me.enableSourceEdit) {
                items.push(
                    '-',
                    btn('sourceedit', true, function(btn){
                        me.toggleSourceEdit(!me.sourceEditMode);
                    })
                );
            }
        }
        
        
        for (i = 0; i < items.length; i++) {
            if (items[i].itemId !== 'sourceedit') {
                items[i].disabled = true;
            }
        }

        
        
        toolbar = Ext.widget('toolbar', {
            id: me.id + '-toolbar',
            ownerCt: me,
            cls: Ext.baseCSSPrefix + 'html-editor-tb',
            enableOverflow: true,
            items: items,
            ownerLayout: me.getComponentLayout(),

            
            listeners: {
                click: function(e){
                    e.preventDefault();
                },
                element: 'el'
            }
        });

        me.toolbar = toolbar;
    },
	updateToolbar: function() {
        var me = this,
            btns, doc, name, fontSelect;
        if (me.readOnly) {
            return;
        }
        if (!me.activated) {
            me.onFirstFocus();
            return;
        }
        btns = me.getToolbar().items.map;
        doc = me.getDoc();
        if (me.enableFont && !Ext.isSafari2) {
            name = (doc.queryCommandValue('FontName') || me.defaultFont).toLowerCase();
            fontSelect = me.fontSelect.dom;
            if (name !== fontSelect.value) {
                fontSelect.value = name;
            }
        }
        function updateButtons() {
            for (var i = 0, l = arguments.length, name; i < l; i++) {
                name = arguments[i];
                btns[name].toggle(doc.queryCommandState(name));
            }
        }
        if(me.enableFormat){
            updateButtons('bold', 'italic', 'underline');
        }
        if(me.enableAlignments){
            updateButtons('justifyfull','justifyright', 'justifycenter', 'justifyleft');
        }
        if(!Ext.isSafari2 && me.enableLists){
            updateButtons('insertorderedlist', 'insertunorderedlist');
        }
        Ext.menu.Manager.hideAll();
        me.syncValue();
    },
	getDocMarkup: function() {
        var me = this,
            h = me.iframeEl.getHeight() - me.iframePad * 2;
        return Ext.String.format('<html><head><link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" /><style type="text/css">body{border:0;margin:0;padding:{0}px;height:{1}px;box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box;cursor:text}</style></head><body style=direction:rtl></body></html>', me.iframePad, h);	
    }
});

Ext.apply(Ext.grid.header.Container.prototype, {direction: "rtl"});
Ext.apply(Ext.grid.Panel.prototype, {direction: "rtl"});
Ext.apply(Ext.tab.Tab.prototype, {direction: "rtl"});
Ext.apply(Ext.toolbar.Item.prototype, {direction: "rtl"});

Ext.override(Ext.grid.column.Column,{align: 'right'});
Ext.override(Ext.layout.container.VBox,{align: 'right'});
Ext.override(Ext.menu.Menu,{defaultAlign: 'tr-br?'});
Ext.override(Ext.menu.Item,{menuAlign: 'tr-tl?',
	renderTpl: [
        '<tpl if="plain">',
            '{text}',
        '<tpl else>',
            '<a id="{id}-itemEl" class="' + Ext.baseCSSPrefix + 'menu-item-link" href="{href}" <tpl if="hrefTarget">target="{hrefTarget}"</tpl> hidefocus="true" unselectable="on">',
                '<img id="{id}-iconEl" src="{icon}" class="' + Ext.baseCSSPrefix + 'menu-item-icon {iconCls}" />',
                '<span id="{id}-textEl" class="' + Ext.baseCSSPrefix + 'menu-item-text" <tpl if="arrowCls">style="margin-left: 17px;"</tpl> >{text}</span>',
                '<img id="{id}-arrowEl" src="{blank}" class="{arrowCls}" />',
            '</a>',
        '</tpl>'
    ]});
Ext.override(Ext.tip.Tip,{defaultAlign: 'tr-br?'});
Ext.override(Ext.form.field.Picker,{pickerAlign: 'tr-br?'});
Ext.override(Ext.grid.CellEditor,{alignment: "tr-tr"});
Ext.override(Ext.button.Button,{direction:"rtl", menuAlign: 'tr-br?',iconAlign: 'right',arrowAlign: 'left'});

Ext.override(Ext.toolbar.Paging,{
	displayMsg : 'نمایش {0} - {1} از {2}',
    emptyMsg : 'فاقد رکورد',
    beforePageText : 'صفحه',
    afterPageText : 'از {0}',
    firstText : 'اولین صفحه',
    prevText : 'صفحه قبل',
    nextText : 'صفحه بعد',
    lastText : 'آخرین صفحه',
    refreshText : 'Refresh',
	ExcelButton : true,
	
	getPagingItems: function() {
        var me = this;

        return [{
            itemId: 'last',
            tooltip: me.lastText,
            overflowText: me.lastText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-last',
            disabled: true,
            handler: me.moveLast,
            scope: me
        },{
            itemId: 'next',
            tooltip: me.nextText,
            overflowText: me.nextText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-next',
            disabled: true,
            handler: me.moveNext,
            scope: me
        },'-',
        me.beforePageText,
        {
            xtype: 'numberfield',
            itemId: 'inputItem',
            name: 'inputItem',
			direction: "rtl",
            cls: Ext.baseCSSPrefix + 'tbar-page-number',
            allowDecimals: false,
            minValue: 1,
            hideTrigger: true,
            enableKeyEvents: true,
            keyNavEnabled: false,
            selectOnFocus: true,
            submitValue: false,
            
            isFormField: false,
            width: me.inputItemWidth,
            margins: '-1 2 3 2',
            listeners: {
                scope: me,
                keydown: me.onPagingKeyDown,
                blur: me.onPagingBlur
            }
        },{
            xtype: 'tbtext',
            itemId: 'afterTextItem',
            text: Ext.String.format(me.afterPageText, 1)
        },
        '-',
        {
            itemId: 'prev',
            tooltip: me.prevText,
            overflowText: me.prevText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-prev',
            disabled: true,
            handler: me.movePrevious,
            scope: me
        },{
            itemId: 'first',
            tooltip: me.firstText,
            overflowText: me.firstText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-first',
            disabled: true,
            handler: me.moveFirst,
            scope: me
        },'-',{
            itemId: 'refresh',
            tooltip: me.refreshText,
            overflowText: me.refreshText,
            iconCls: Ext.baseCSSPrefix + 'tbar-loading',
            handler: me.doRefresh,
            scope: me
        },'-',{
			itemId: 'excel',
			hidden : !this.ExcelButton,
            tooltip: "خروجی اکسل",
            overflowText: "خروجی اکسل",
            iconCls: 'excel',
            handler: function(){this.up().downloadExcelXml();},
            scope: me
		}];
    }
});
    
Ext.override(Ext.grid.feature.Grouping, { 
    groupByText : 'گروه بندي براساس اين فيلد',
    showGroupsText : 'نمايش به صورت گروه بندي'
});
Ext.override(Ext.form.field.Base, {invalidText : 'مقدار این فیلد نامعتبر است'});
Ext.override(Ext.form.field.Text, {
	minLengthText : 'حداقل طول این فیلد {0} است',
    maxLengthText : 'حداکثر طول این فیلد {0} است',
    blankText : 'تکمیل این فیلد الزامی است'
});
Ext.override(Ext.form.field.Number, {
	minText : 'حداقل مقدار این فیلد {0} است',
    maxText : 'حداکثر مقدار این فیلد {0} است',
    nanText : '{0} عدد نمی باشد',
    negativeText : 'مقدار این فیلد نمی تواند منفی باشد'
});
Ext.override(Ext.form.field.Date, {
	disabledDaysText : "غیر فعال",
    disabledDatesText : "غیر فعال",
    minText : "تاریخ وارد شده باید بشتر یا برابر {0} باشد",
    maxText : "تاریخ وارد شده باید کمتر یا برابر {0} باشد",
    invalidText : "{0} تاریخ معتبری نمی باشد - تاریخ باید به فرمت {1} باشد"
});
Ext.override(Ext.form.field.Time, {
	minText : "زمان وارد شده باید برابر یا بیشتر از {0} باشد",
    maxText : "زمان وارد شده باید برابر یا کمتر از {0} باشد",
    invalidText : "{0} زمان معتبری نمی باشد"
}); 
Ext.override(Ext.view.AbstractView, {loadingText: 'در حال بارگذاری ...'});
Ext.override(Ext.grid.Lockable, {
	unlockText: 'بدون قفل',
    lockText: 'قفل'
});
Ext.override(Ext.grid.header.Container, {
	sortAscText: 'مرتب سازی صعودی',
    sortDescText: 'مرتب سازی نزولی',
    sortClearText: 'حذف مرتب سازی',
    columnsText: 'ستون ها'
});
 Ext.override(Ext.grid.property.HeaderContainer, {
	nameText : 'عنوان',
    valueText : 'مقدار',
    trueText: 'بلی',
    falseText: 'خیر'
}); 
Ext.override(Ext.tab.Tab, {closeText: 'بستن برگه'});
Ext.override(Ext.grid.column.Boolean, {
	trueText: 'بلی',
    falseText: 'خیر'
});
Ext.override(Ext.grid.RowEditor, {
	saveBtnText  : 'ذخیره',
    cancelBtnText: 'انصراف',
    errorsText: 'خطاها',
    dirtyText: 'ابتدا ذخیره یا انصراف را انجام دهید',
	
	reposition: function(animateConfig) {
        var me = this,
            context = me.context,
            row = context && Ext.get(context.row),
            btns = me.getFloatingButtons(),
            btnEl = btns.el,
            grid = me.editingPlugin.grid,
            viewEl = grid.view.el,
            mainBodyWidth = grid.headerCt.getFullWidth(),
            scrollerWidth = grid.getWidth(),
            width = Math.min(mainBodyWidth, scrollerWidth),
            scrollLeft = grid.view.el.dom.scrollLeft,
			scrollRight = grid.view.el.dom.scrollRight,
            btnWidth = btns.getWidth(),
            left = (width - btnWidth) / 2 + scrollLeft,
			right = (width - btnWidth) / 2 - scrollLeft,
            y, rowH, newHeight,
           invalidateScroller = function() {
                btnEl.scrollIntoView(viewEl, false);
                if (animateConfig && animateConfig.callback) {
                    animateConfig.callback.call(animateConfig.scope || me);
                }
            };
        if (row && Ext.isElement(row.dom)) {
            row.scrollIntoView(viewEl, false);
            y = row.getXY()[1] - 5;
            rowH = row.getHeight();
            newHeight = rowH + (me.editingPlugin.grid.rowLines ? 9 : 10);
            if (me.getHeight() != newHeight) {
                me.setHeight(newHeight);
				//--------- edited by jafarkhani -----------
                //me.el.setLeft(0);
				me.el.setRight(0);
            }
            if (animateConfig) {
                var animObj = {
                    to: {
                        y: y
                    },
                    duration: animateConfig.duration || 125,
                    listeners: {
                        afteranimate: function() {
                            invalidateScroller();
                            y = row.getXY()[1] - 5;
                        }
                    }
                };
                me.el.animate(animObj);
            } else {
                me.el.setY(y);
                invalidateScroller();
            }
        }
        if (me.getWidth() != mainBodyWidth) {
            me.setWidth(mainBodyWidth);
        }
        //btnEl.setLeft(left);
		btnEl.setRight(right);
    }
});
Ext.override(Ext.view.AbstractView, {loadingText: 'در حال بارگذاری ...'});
Ext.override(Ext.grid.plugin.DragDrop, {dragText : '{0} ردیف انتخاب شده {1}'});
Ext.override(Ext.tree.plugin.TreeViewDragDrop, {dragText : '{0} گره انتخاب شده{1}'});
Ext.override(Ext.picker.Date,{
	todayText : 'امروز',
    todayTip : '{0} (Spacebar)',
    minText : 'این تاریخ قبل از تاریخ مینیمم است',
    maxText : 'این تاریخ بعد از تاریخ حداکثر است',
    disabledDaysText : 'غیر فعال',
    disabledDatesText : 'غیر فعال',
    nextText : 'ماه بعد (Control+Right)',
    prevText : 'ماه قبل (Control+Left)',
    monthYearText : 'انتخاب ماه(Control+Up/Down to move years)'
});
Ext.override(Ext.form.CheckboxGroup,{blankText : "حداقل یک مورد از این گروه را باید انتخاب کنید"});
Ext.override(Ext.form.RadioGroup,{blankText : "حداقل یک مورد از این گروه را باید انتخاب کنید"});
Ext.override(Ext.form.field.HtmlEditor, {createLinkText : 'آدرس مربوط به لینک را وارد کنید:'});
Ext.override(Ext.window.MessageBox, {
    buttonText: {
        ok: 'تایید',
        yes: 'بلی',
        no: 'خیر',
        cancel: 'انصراف'
    }
});
//***********************************************************
//*********** always send combobox value even empty   *******
//*********** empty combobox value on clear input ***********
//***********************************************************
Ext.override(Ext.form.field.ComboBox,{
	minChars : 1, 
	setHiddenValue: function(values){
        var me = this,
            name = me.hiddenName, 
            i;
            
        if (!me.hiddenDataEl || !name) {
            return;
        }
        values = Ext.Array.from(values);
        var dom = me.hiddenDataEl.dom,
            childNodes = dom.childNodes,
            input = childNodes[0],
            valueCount = values.length,
            childrenCount = childNodes.length;
        
		if(valueCount == 0)
		{
			if(!input)
				me.hiddenDataEl.update(Ext.DomHelper.markup({
					tag: 'input', 
					type: 'hidden', 
					name: name,
					value: ""
				}));
			else
			{
				input.value = "";
				return;
			}
		}
		
        if (!input && valueCount > 0) {
            me.hiddenDataEl.update(Ext.DomHelper.markup({
                tag: 'input', 
                type: 'hidden', 
                name: name
            }));
            childrenCount = 1;
            input = dom.firstChild;
        }
        while (childrenCount > valueCount) {
            dom.removeChild(childNodes[0]);
            -- childrenCount;
        }
        while (childrenCount < valueCount) {
            dom.appendChild(input.cloneNode(true));
            ++ childrenCount;
        }
        for (i = 0; i < valueCount; i++) {
            childNodes[i].value = values[i];
        }
    },
	beforeBlur: function() {
        this.doQueryTask.cancel();
        this.assertValue();
		//...........................
		if(this.getRawValue() == "")
		{
			this.clearValue();
			this.setHiddenValue();
		}
    },
	getSubmitValue: function() {
		var v = this.getValue();
        if(v == null)
			this.setValue("");
			
		return this.getValue();		
    }
});

//***********************************************************
//*********** can type space in input s in grid rows  *******
//***********************************************************
Ext.override(Ext.selection.RowModel, {
	onKeyPress: function(e) {
        if (e.getKey() === e.SPACE) {
            //e.stopEvent();
            var me = this,
                record = me.lastFocused;
            if (record) {
                if (me.isSelected(record)) {
                    me.doDeselect(record, false);
                } else {
                    me.doSelect(record, true);
                }
            }
        }
    }
});

//***********************************************************
//*********** adjust form file button  **********************
//***********************************************************
Ext.override(Ext.form.field.File, {
	getTriggerMarkup: function() {
        var me = this,
            result,
            btn = Ext.widget('button', Ext.apply({
                id: me.id + '-buttonEl',
                ui: me.ui,
                disabled: me.disabled,
                text: me.buttonText,
                cls: Ext.baseCSSPrefix + 'form-file-btn',
                preventDefault: false,
                style: me.buttonOnly ? '' : 'margin-right:' + me.buttonMargin + 'px; top: -3px'
            }, me.buttonConfig)),
            btnCfg = btn.getRenderTree(),
            inputElCfg = {
                id: me.id + '-fileInputEl',
                cls: Ext.baseCSSPrefix + 'form-file-input',
                tag: 'input',
                type: 'file',
                size: 1
            };
        if (me.disabled) {
            inputElCfg.cn.disabled = true;
        }
        btnCfg.cn = inputElCfg;
        result = '<td id="' + me.id + '-browseButtonWrap">' + Ext.DomHelper.markup(btnCfg) + '</td>';
        btn.destroy();
        return result;
    }
});
	
//***********************************************************
//************** Grid PageSize plugin  **********************
//***********************************************************
Ext.ux.PageSizePlugin = function() {
	Ext.ux.PageSizePlugin.superclass.constructor.call(this, {
          store: new Ext.data.SimpleStore({
                 fields: ['text', 'value'],
                 data: [['10', 10], ['25', 25], ['50', 50], ['100', 100],['150', 150],['200', 200],['250', 250],['300', 300],['350', 350]]
          }),
          mode: 'local',
          displayField: 'text',
          valueField: 'value',
          editable: false,
          allowBlank: false,
          triggerAction: 'all',
          width: 50
    });
};
Ext.extend(Ext.ux.PageSizePlugin, Ext.form.ComboBox, {
	init: function(paging) {
		  paging.on('render', this.onInitView, this);
	},

	onInitView: function(paging) {
		   paging.add('-',
		  this,
		  'رکورد در هر صفحه'
		);
		this.on('select', this.onPageSizeChanged, paging);
	},

	onPageSizeChanged: function(combo) {
		   this.pageSize = parseInt(combo.getValue());
		   this.store.pageSize = parseInt(combo.getValue());
		   this.store.loadPage(1);
	}
});

//***********************************************************
//******************** MultiSelect  *************************
//***********************************************************
/**
 * A control that allows selection of multiple items in a list
 */
Ext.define('Ext.form.MultiSelect', {
    
    extend: 'Ext.form.FieldContainer',
    
    mixins: {
        bindable: 'Ext.util.Bindable',
        field: 'Ext.form.field.Field'    
    },
    
    alternateClassName: 'Ext.Multiselect',
    alias: ['widget.multiselectfield', 'widget.multiselect'],
    
    requires: ['Ext.panel.Panel', 'Ext.view.BoundList'],
    
    uses: ['Ext.view.DragZone', 'Ext.view.DropZone'],
    
    /**
     * @cfg {String} [dragGroup=""] The ddgroup name for the MultiSelect DragZone.
     */

    /**
     * @cfg {String} [dropGroup=""] The ddgroup name for the MultiSelect DropZone.
     */
    
    /**
     * @cfg {String} [title=""] A title for the underlying panel.
     */
    
    /**
     * @cfg {Boolean} [ddReorder=false] Whether the items in the MultiSelect list are drag/drop reorderable.
     */
    ddReorder: false,

    /**
     * @cfg {Object/Array} tbar An optional toolbar to be inserted at the top of the control's selection list.
     * This can be a {@link Ext.toolbar.Toolbar} object, a toolbar config, or an array of buttons/button configs
     * to be added to the toolbar. See {@link Ext.panel.Panel#tbar}.
     */

    /**
     * @cfg {String} [appendOnly=false] True if the list should only allow append drops when drag/drop is enabled.
     * This is useful for lists which are sorted.
     */
    appendOnly: false,

    /**
     * @cfg {String} [displayField="text"] Name of the desired display field in the dataset.
     */
    displayField: 'text',

    /**
     * @cfg {String} [valueField="text"] Name of the desired value field in the dataset.
     */

    /**
     * @cfg {Boolean} [allowBlank=true] False to require at least one item in the list to be selected, true to allow no
     * selection.
     */
    allowBlank: true,

    /**
     * @cfg {Number} [minSelections=0] Minimum number of selections allowed.
     */
    minSelections: 0,

    /**
     * @cfg {Number} [maxSelections=Number.MAX_VALUE] Maximum number of selections allowed.
     */
    maxSelections: Number.MAX_VALUE,

    /**
     * @cfg {String} [blankText="This field is required"] Default text displayed when the control contains no items.
     */
    blankText: 'This field is required',

    /**
     * @cfg {String} [minSelectionsText="Minimum {0}item(s) required"] 
     * Validation message displayed when {@link #minSelections} is not met. 
     * The {0} token will be replaced by the value of {@link #minSelections}.
     */
    minSelectionsText: 'Minimum {0} item(s) required',
    
    /**
     * @cfg {String} [maxSelectionsText="Maximum {0}item(s) allowed"] 
     * Validation message displayed when {@link #maxSelections} is not met
     * The {0} token will be replaced by the value of {@link #maxSelections}.
     */
    maxSelectionsText: 'Minimum {0} item(s) required',

    /**
     * @cfg {String} [delimiter=","] The string used to delimit the selected values when {@link #getSubmitValue submitting}
     * the field as part of a form. If you wish to have the selected values submitted as separate
     * parameters rather than a single delimited parameter, set this to <tt>null</tt>.
     */
    delimiter: ',',

    /**
     * @cfg {Ext.data.Store/Array} store The data source to which this MultiSelect is bound (defaults to <tt>undefined</tt>).
     * Acceptable values for this property are:
     * <div class="mdetail-params"><ul>
     * <li><b>any {@link Ext.data.Store Store} subclass</b></li>
     * <li><b>an Array</b> : Arrays will be converted to a {@link Ext.data.ArrayStore} internally.
     * <div class="mdetail-params"><ul>
     * <li><b>1-dimensional array</b> : (e.g., <tt>['Foo','Bar']</tt>)<div class="sub-desc">
     * A 1-dimensional array will automatically be expanded (each array item will be the combo
     * {@link #valueField value} and {@link #displayField text})</div></li>
     * <li><b>2-dimensional array</b> : (e.g., <tt>[['f','Foo'],['b','Bar']]</tt>)<div class="sub-desc">
     * For a multi-dimensional array, the value in index 0 of each item will be assumed to be the combo
     * {@link #valueField value}, while the value at index 1 is assumed to be the combo {@link #displayField text}.
     * </div></li></ul></div></li></ul></div>
     */
    
    ignoreSelectChange: 0,
    
    initComponent: function(){
        var me = this;

        me.bindStore(me.store, true);
        if (me.store.autoCreated) {
            me.valueField = me.displayField = 'field1';
            if (!me.store.expanded) {
                me.displayField = 'field2';
            }
        }

        if (!Ext.isDefined(me.valueField)) {
            me.valueField = me.displayField;
        }
        Ext.apply(me, me.setupItems());
        
        
        me.callParent();
        me.initField();
        me.addEvents('drop');    
    },
    
    setupItems: function() {
        var me = this;
        
        me.boundList = Ext.create('Ext.view.BoundList', {
            deferInitialRefresh: false,
            multiSelect: true,
            store: me.store,
            displayField: me.displayField,
            disabled: me.disabled
        });
        
        me.boundList.getSelectionModel().on('selectionchange', me.onSelectChange, me);
        return {
            layout: 'fit',
            title: me.title,
            tbar: me.tbar,
            items: me.boundList
        };
    },
    
    onSelectChange: function(selModel, selections){
        if (!this.ignoreSelectChange) {
            this.setValue(selections);
        }    
    },
    
    getSelected: function(){
        return this.boundList.getSelectionModel().getSelection();
    },
    
    // compare array values
    isEqual: function(v1, v2) {
        var fromArray = Ext.Array.from,
            i = 0, 
            len;

        v1 = fromArray(v1);
        v2 = fromArray(v2);
        len = v1.length;

        if (len !== v2.length) {
            return false;
        }

        for(; i < len; i++) {
            if (v2[i] !== v1[i]) {
                return false;
            }
        }

        return true;
    },
    
    afterRender: function(){
        var me = this;
        
        me.callParent();
        if (me.selectOnRender) {
            ++me.ignoreSelectChange;
            me.boundList.getSelectionModel().select(me.getRecordsForValue(me.value));
            --me.ignoreSelectChange;
            delete me.toSelect;
        }    
        
        if (me.ddReorder && !me.dragGroup && !me.dropGroup){
            me.dragGroup = me.dropGroup = 'MultiselectDD-' + Ext.id();
        }

        if (me.draggable || me.dragGroup){
            me.dragZone = Ext.create('Ext.view.DragZone', {
                view: me.boundList,
                ddGroup: me.dragGroup,
                dragText: '{0} Item{1}'
            });
        }
        if (me.droppable || me.dropGroup){
            me.dropZone = Ext.create('Ext.view.DropZone', {
                view: me.boundList,
                ddGroup: me.dropGroup,
                handleNodeDrop: function(data, dropRecord, position) {
                    var view = this.view,
                        store = view.getStore(),
                        records = data.records,
                        index;

                    // remove the Models from the source Store
                    data.view.store.remove(records);

                    index = store.indexOf(dropRecord);
                    if (position === 'after') {
                        index++;
                    }
                    store.insert(index, records);
                    view.getSelectionModel().select(records);
                    me.fireEvent('drop', me, records);
                }
            });
        }
    },
    
    isValid : function() {
        var me = this,
            disabled = me.disabled,
            validate = me.forceValidation || !disabled;
            
        
        return validate ? me.validateValue(me.value) : disabled;
    },
    
    validateValue: function(value) {
        var me = this,
            errors = me.getErrors(value),
            isValid = Ext.isEmpty(errors);
            
        if (!me.preventMark) {
            if (isValid) {
                me.clearInvalid();
            } else {
                me.markInvalid(errors);
            }
        }

        return isValid;
    },
    
    markInvalid : function(errors) {
        // Save the message and fire the 'invalid' event
        var me = this,
            oldMsg = me.getActiveError();
        me.setActiveErrors(Ext.Array.from(errors));
        if (oldMsg !== me.getActiveError()) {
            me.updateLayout();
        }
    },

    /**
     * Clear any invalid styles/messages for this field.
     *
     * **Note**: this method does not cause the Field's {@link #validate} or {@link #isValid} methods to return `true`
     * if the value does not _pass_ validation. So simply clearing a field's errors will not necessarily allow
     * submission of forms submitted with the {@link Ext.form.action.Submit#clientValidation} option set.
     */
    clearInvalid : function() {
        // Clear the message and fire the 'valid' event
        var me = this,
            hadError = me.hasActiveError();
        me.unsetActiveError();
        if (hadError) {
            me.updateLayout();
        }
    },
    
    getSubmitData: function() {
        var me = this,
            data = null,
            val;
        if (!me.disabled && me.submitValue && !me.isFileUpload()) {
            val = me.getSubmitValue();
            if (val !== null) {
                data = {};
                data[me.getName()] = val;
            }
        }
        return data;
    },

    /**
     * Returns the value that would be included in a standard form submit for this field.
     *
     * @return {String} The value to be submitted, or null.
     */
    getSubmitValue: function() {
        var me = this,
            delimiter = me.delimiter,
            val = me.getValue();
            
        return Ext.isString(delimiter) ? val.join(delimiter) : val;
    },
    
    getValue: function(){
        return this.value;
    },
    
    getRecordsForValue: function(value){
        var me = this,
            records = [],
            all = me.store.getRange(),
            valueField = me.valueField,
            i = 0,
            allLen = all.length,
            rec,
            j,
            valueLen;
            
        for (valueLen = value.length; i < valueLen; ++i) {
            for (j = 0; j < allLen; ++j) {
                rec = all[j];   
                if (rec.get(valueField) == value[i]) {
                    records.push(rec);
                }
            }    
        }
            
        return records;
    },
    
    setupValue: function(value){
        var delimiter = this.delimiter,
            valueField = this.valueField,
            i = 0,
            len,
            item;
            
        if (delimiter && Ext.isString(value)) {
            value = value.split(delimiter);
        } else if (!Ext.isArray(value)) {
            value = [value];
        }
        
        for (len = value.length; i < len; ++i) {
            item = value[i];
            if (item && item.isModel) {
                value[i] = item.get(valueField);
            }
        }
        return Ext.Array.unique(value);
    },
    
    setValue: function(value){
        var me = this,
            selModel = me.boundList.getSelectionModel();
        
        value = me.setupValue(value);
        me.mixins.field.setValue.call(me, value);
        
        if (me.rendered) {
            ++me.ignoreSelectChange;
            selModel.deselectAll();
            selModel.select(me.getRecordsForValue(value));
            --me.ignoreSelectChange;
        } else {
            me.selectOnRender = true;
        }
    },
    
    clearValue: function(){
        this.setValue([]);    
    },
    
    onEnable: function(){
        var list = this.boundList;
        this.callParent();
        if (list) {
            list.enable();
        }
    },
    
    onDisable: function(){
        var list = this.boundList;
        this.callParent();
        if (list) {
            list.disable();
        }
    },
    
    getErrors : function(value) {
        var me = this,
            format = Ext.String.format,
            errors = [],
            numSelected;

        value = Ext.Array.from(value || me.getValue());
        numSelected = value.length;

        if (!me.allowBlank && numSelected < 1) {
            errors.push(me.blankText);
        }
        if (numSelected < me.minSelections) {
            errors.push(format(me.minSelectionsText, me.minSelections));
        }
        if (numSelected > me.maxSelections) {
            errors.push(format(me.maxSelectionsText, me.maxSelections));
        }
        return errors;
    },
    
    onDestroy: function(){
        var me = this;
        
        me.bindStore(null);
        Ext.destroy(me.dragZone, me.dropZone);
        me.callParent();
    },
    
    onBindStore: function(store){
        var boundList = this.boundList;
        
        if (boundList) {
            boundList.bindStore(store);
        }
    }
    
});

/*
 * Note that this control will most likely remain as an example, and not as a core Ext form
 * control.  However, the API will be changing in a future release and so should not yet be
 * treated as a final, stable API at this time.
 */

/**
 * A control that allows selection of between two Ext.ux.form.MultiSelect controls.
 */
Ext.define('Ext.form.ItemSelector', {
    extend: 'Ext.form.MultiSelect',
    alias: ['widget.itemselectorfield', 'widget.itemselector'],
    alternateClassName: ['Ext.ux.ItemSelector'],
    requires: [
        'Ext.button.Button',
        'Ext.form.MultiSelect'
    ],
    
    /**
     * @cfg {Boolean} [hideNavIcons=false] True to hide the navigation icons
     */
    hideNavIcons:false,

    /**
     * @cfg {Array} buttons Defines the set of buttons that should be displayed in between the ItemSelector
     * fields. Defaults to <tt>['top', 'up', 'add', 'remove', 'down', 'bottom']</tt>. These names are used
     * to build the button CSS class names, and to look up the button text labels in {@link #buttonsText}.
     * This can be overridden with a custom Array to change which buttons are displayed or their order.
     */
    buttons: ['top', 'up', 'add', 'remove', 'down', 'bottom'],

    /**
     * @cfg {Object} buttonsText The tooltips for the {@link #buttons}.
     * Labels for buttons.
     */
    buttonsText: {
        top: "Move to Top",
        up: "Move Up",
        add: "Add to Selected",
        remove: "Remove from Selected",
        down: "Move Down",
        bottom: "Move to Bottom"
    },
    
    initComponent: function(){
        this.ddGroup = this.id + '-dd';
        this.callParent();
    },
    
    createList: function(){
        var me = this;
        
        return Ext.create('Ext.ux.form.MultiSelect', {
            flex: 1,
            dragGroup: me.ddGroup,
            dropGroup: me.ddGroup,
            store: {
                model: me.store.model,
                data: []
            },
            displayField: me.displayField,
            disabled: me.disabled,
            listeners: {
                boundList: {
                    scope: me,
                    itemdblclick: me.onItemDblClick,
                    drop: me.syncValue
                }
            }
        });
    },

    setupItems: function() {
        var me = this;
        
        me.fromField = me.createList();
        me.toField = me.createList();
        
        // add everything to the from field at the start
        me.fromField.store.add(me.store.getRange());
        
        return {
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            items: [
                me.fromField,
                {
                    xtype: 'container',
                    margins: '0 4',
                    width: 30,
                    layout: {
                        type: 'vbox',
                        align: 'middle'
                    },
                    items: me.createButtons()
                },
                me.toField
            ]
        };
    },
    
    createButtons: function(){
        var me = this,
            buttons = [];
            
        if (!me.hideNavIcons) {
            Ext.Array.forEach(me.buttons, function(name) {
                buttons.push({
                    xtype: 'button',
                    tooltip: me.buttonsText[name],
                    handler: me['on' + Ext.String.capitalize(name) + 'BtnClick'],
                    cls: Ext.baseCSSPrefix + 'form-itemselector-btn',
                    iconCls: Ext.baseCSSPrefix + 'form-itemselector-' + name,
                    navBtn: true,
                    scope: me,
                    margin: '4 0 0 0'
                });
            });
        }
        return buttons;
    },
    
    getSelections: function(list){
        var store = list.getStore(),
            selections = list.getSelectionModel().getSelection(),
            i = 0,
            len = selections.length;
            
        return Ext.Array.sort(selections, function(a, b){
            a = store.indexOf(a);
            b = store.indexOf(b);
            
            if (a < b) {
                return -1;
            } else if (a > b) {
                return 1;
            }
            return 0;
        });
    },

    onTopBtnClick : function() {
        var list = this.toField.boundList,
            store = list.getStore(),
            selected = this.getSelections(list),
            i = selected.length - 1,
            selection;
        
        
        store.suspendEvents();
        for (; i > -1; --i) {
            selection = selected[i];
            store.remove(selected);
            store.insert(0, selected);
        }
        store.resumeEvents();
        list.refresh();   
        this.syncValue(); 
    },

    onBottomBtnClick : function() {
        var list = this.toField.boundList,
            store = list.getStore(),
            selected = this.getSelections(list),
            i = 0,
            len = selected.length,
            selection;
            
        store.suspendEvents();
        for (; i < len; ++i) {
            selection = selected[i];
            store.remove(selection);
            store.add(selection);
        }
        store.resumeEvents();
        list.refresh();
        this.syncValue();
    },

    onUpBtnClick : function() {
        var list = this.toField.boundList,
            store = list.getStore(),
            selected = this.getSelections(list),
            i = 0,
            len = selected.length,
            selection,
            index;
            
        store.suspendEvents();
        for (; i < len; ++i) {
            selection = selected[i];
            index = Math.max(0, store.indexOf(selection) - 1);
            store.remove(selection);
            store.insert(index, selection);
        }
        store.resumeEvents();
        list.refresh();
        this.syncValue();
    },

    onDownBtnClick : function() {
        var list = this.toField.boundList,
            store = list.getStore(),
            selected = this.getSelections(list),
            i = 0,
            len = selected.length,
            max = store.getCount(),
            selection,
            index;
            
        store.suspendEvents();
        for (; i < len; ++i) {
            selection = selected[i];
            index = Math.min(max, store.indexOf(selection) + 1);
            store.remove(selection);
            store.insert(index, selection);
        }
        store.resumeEvents();
        list.refresh();
        this.syncValue();
    },

    onAddBtnClick : function() {
        var me = this,
            fromList = me.fromField.boundList,
            selected = this.getSelections(fromList);
            
        fromList.getStore().remove(selected);
        this.toField.boundList.getStore().add(selected);
        this.syncValue();
    },

    onRemoveBtnClick : function() {
        var me = this,
            toList = me.toField.boundList,
            selected = this.getSelections(toList);
            
        toList.getStore().remove(selected);
        this.fromField.boundList.getStore().add(selected);
        this.syncValue();
    },
    
    syncValue: function(){
        this.setValue(this.toField.store.getRange()); 
    },
    
    onItemDblClick: function(view, rec){
        var me = this,
            from = me.fromField.store,
            to = me.toField.store,
            current,
            destination;
            
        if (from.indexOf(rec) > -1) {
            current = from;
            destination = to;
        } else {
            current = to;
            destination = from;
        }
        current.remove(rec);
        destination.add(rec);
        me.syncValue();
    },
    
    setValue: function(value){
        var me = this,
            fromStore = me.fromField.store,
            toStore = me.toField.store,
            selected;
        
        value = me.setupValue(value);
        me.mixins.field.setValue.call(me, value);
        
        selected = me.getRecordsForValue(value);
        
        Ext.Array.forEach(toStore.getRange(), function(rec){
            if (!Ext.Array.contains(selected, rec)) {
                // not in the selected group, remove it from the toStore
                toStore.remove(rec);
                fromStore.add(rec);
            }
        });
        toStore.removeAll();
        
        Ext.Array.forEach(selected, function(rec){
            // In the from store, move it over
            if (fromStore.indexOf(rec) > -1) {
                fromStore.remove(rec);     
            }
            toStore.add(rec);
        });
    },
    
    onBindStore: Ext.emptyFn,
    
    onEnable: function(){
        var me = this;
        
        me.callParent();
        me.fromField.enable();
        me.toField.enable();
        
        Ext.Array.forEach(me.query('[navBtn]'), function(btn){
            btn.enable();
        });
    },
    
    onDisable: function(){
        var me = this;
        
        me.callParent();
        me.fromField.disable();
        me.toField.disable();
        
        Ext.Array.forEach(me.query('[navBtn]'), function(btn){
            btn.disable();
        });
    },
    
    onDestroy: function(){
        this.bindStore(null);
        this.callParent();
    }
});

//***********************************************************
//******************* rowexpander  **************************
//***********************************************************
// feature idea to enable Ajax loading and then the content
// cache would actually make sense. Should we dictate that they use
// data or support raw html as well?

/**
 * @class Ext.ux.RowExpander
 * @extends Ext.AbstractPlugin
 * Plugin (ptype = 'rowexpander') that adds the ability to have a Column in a grid which enables
 * a second row body which expands/contracts.  The expand/contract behavior is configurable to react
 * on clicking of the column, double click of the row, and/or hitting enter while a row is selected.
 *
 * @ptype rowexpander
 */
Ext.define('Ext.ux.RowExpander', {
    extend: 'Ext.AbstractPlugin',

    requires: [
        'Ext.grid.feature.RowBody',
        'Ext.grid.feature.RowWrap'
    ],

    alias: 'plugin.rowexpander',

    rowBodyTpl: null,

    /**
     * @cfg {Boolean} expandOnEnter
     * <tt>true</tt> to toggle selected row(s) between expanded/collapsed when the enter
     * key is pressed (defaults to <tt>true</tt>).
     */
    expandOnEnter: true,

    /**
     * @cfg {Boolean} expandOnDblClick
     * <tt>true</tt> to toggle a row between expanded/collapsed when double clicked
     * (defaults to <tt>true</tt>).
     */
    expandOnDblClick: true,

    /**
     * @cfg {Boolean} selectRowOnExpand
     * <tt>true</tt> to select a row when clicking on the expander icon
     * (defaults to <tt>false</tt>).
     */
    selectRowOnExpand: false,
	
	defaultExpand: false,

    rowBodyTrSelector: '.x-grid-rowbody-tr',
    rowBodyHiddenCls: 'x-grid-row-body-hidden',
    rowCollapsedCls: 'x-grid-row-collapsed',



    renderer: function(value, metadata, record, rowIdx, colIdx) {
        if (colIdx === 0) {
            metadata.tdCls = 'x-grid-td-expander';
        }
        return '<div class="x-grid-row-expander">&#160;</div>';
    },

    /**
     * @event expandbody
     * <b<Fired through the grid's View</b>
     * @param {HTMLElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HTMLElement} expandRow The &lt;tr> element containing the expanded data.
     */
    /**
     * @event collapsebody
     * <b<Fired through the grid's View.</b>
     * @param {HTMLElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HTMLElement} expandRow The &lt;tr> element containing the expanded data.
     */

    constructor: function() {
        this.callParent(arguments);
        var grid = this.getCmp();
        this.recordsExpanded = {};
        // <debug>
        if (!this.rowBodyTpl) {
            Ext.Error.raise("The 'rowBodyTpl' config is required and is not defined.");
        }
        // </debug>
        // TODO: if XTemplate/Template receives a template as an arg, should
        // just return it back!
        var rowBodyTpl = Ext.create('Ext.XTemplate', this.rowBodyTpl),
            features = [{
                ftype: 'rowbody',
                columnId: this.getHeaderId(),
                recordsExpanded: this.recordsExpanded,
                rowBodyHiddenCls: this.defaultExpand ? "" : this.rowBodyHiddenCls,
                rowCollapsedCls: this.defaultExpand ? "" : this.rowCollapsedCls,
                getAdditionalData: this.getRowBodyFeatureData,
                getRowBodyContents: function(data) {
                    return rowBodyTpl.applyTemplate(data);
                }
            },{ftype: 'rowwrap'}];

        if (grid.features) {
            grid.features = features.concat(grid.features);
        } else {
            grid.features = features;
        }

        // NOTE: features have to be added before init (before Table.initComponent)
    },

    init: function(grid) {
        this.callParent(arguments);

        // Columns have to be added in init (after columns has been used to create the
        // headerCt). Otherwise, shared column configs get corrupted, e.g., if put in the
        // prototype.
        grid.headerCt.insert(0, this.getHeaderConfig());
        grid.on('render', this.bindView, this, {single: true});
    },

    getHeaderId: function() {
        if (!this.headerId) {
            this.headerId = Ext.id();
        }
        return this.headerId;
    },

    getRowBodyFeatureData: function(data, idx, record, orig) {
        var o = Ext.grid.feature.RowBody.prototype.getAdditionalData.apply(this, arguments),
            id = this.columnId;
        o.rowBodyColspan = o.rowBodyColspan - 1;
        o.rowBody = this.getRowBodyContents(data);
        o.rowCls = this.recordsExpanded[record.internalId] ? '' : this.rowCollapsedCls;
        o.rowBodyCls = this.recordsExpanded[record.internalId] ? '' : this.rowBodyHiddenCls;
        o[id + '-tdAttr'] = ' valign="top" rowspan="2" ';
        if (orig[id+'-tdAttr']) {
            o[id+'-tdAttr'] += orig[id+'-tdAttr'];
        }
		
        return o;
    },

    bindView: function() {
        var view = this.getCmp().getView(),
            viewEl;

        if (!view.rendered) {
            view.on('render', this.bindView, this, {single: true});
        } else {
            viewEl = view.getEl();
            if (this.expandOnEnter) {
                this.keyNav = Ext.create('Ext.KeyNav', viewEl, {
                    'enter' : this.onEnter,
                    scope: this
                });
            }
            if (this.expandOnDblClick) {
                view.on('itemdblclick', this.onDblClick, this);
            }
            this.view = view;
        }
    },

    onEnter: function(e) {
        var view = this.view,
            ds   = view.store,
            sm   = view.getSelectionModel(),
            sels = sm.getSelection(),
            ln   = sels.length,
            i = 0,
            rowIdx;

        for (; i < ln; i++) {
            rowIdx = ds.indexOf(sels[i]);
            this.toggleRow(rowIdx);
        }
    },

    toggleRow: function(rowIdx) {
        var rowNode = this.view.getNode(rowIdx),
            row = Ext.get(rowNode),
            nextBd = Ext.get(row).down(this.rowBodyTrSelector),
            record = this.view.getRecord(rowNode),
            grid = this.getCmp();

        if (row.hasCls(this.rowCollapsedCls)) {
            row.removeCls(this.rowCollapsedCls);
            nextBd.removeCls(this.rowBodyHiddenCls);
            this.recordsExpanded[record.internalId] = true;
            this.view.fireEvent('expandbody', rowNode, record, nextBd.dom);
        } else {
            row.addCls(this.rowCollapsedCls);
            nextBd.addCls(this.rowBodyHiddenCls);
            this.recordsExpanded[record.internalId] = false;
            this.view.fireEvent('collapsebody', rowNode, record, nextBd.dom);
        }


        // If Grid is auto-heighting itself, then perform a component layhout to accommodate the new height
        //if (!grid.isFixedHeight()) {
            grid.doComponentLayout();
        //}
        this.view.up('gridpanel').invalidateScroller();
    },

    onDblClick: function(view, cell, rowIdx, cellIndex, e) {

        this.toggleRow(rowIdx);
    },

    getHeaderConfig: function() {
        var me                = this,
            toggleRow         = Ext.Function.bind(me.toggleRow, me),
            selectRowOnExpand = me.selectRowOnExpand;

        return {
            id: this.getHeaderId(),
            width: 24,
            sortable: false,
            resizable: false,
            draggable: false,
            hideable: false,
            menuDisabled: true,
            cls: ' x-grid-header-special',
            renderer: function(value, metadata) {
                metadata.tdCls = ' x-grid-cell-special';

                return '<div class="x-grid-row-expander">&#160;</div>';
            },
            processEvent: function(type, view, cell, recordIndex, cellIndex, e) {
                if (type == "mousedown" && e.getTarget('.x-grid-row-expander')) {
                    var row = e.getTarget('.x-grid-row');
                    toggleRow(row);
                    return selectRowOnExpand;
                }
            }
        };
    }
});


//***********************************************************
//******************* FormSerializer  ***********************
//***********************************************************
/*    @Author    Henry Paradiz    henry.paradiz at gmail dot com *    
 *    Ext.FormSerializer
 *
 *    Converts HTML form element values to JavaScript Object,
 *    JSON, and HTTP GET compatible query string.
 *
 *    Usage:
 *
 *        new Ext.FormSerializer('formID').toObject();
 *
 *        Ext.create('Ext.FormSerializer','formID').toJSON();
 *
 */
Ext.define('Ext.FormSerializer', {
    constructor: function(form) {
        this.form = Ext.get(form);
        if(!this.form)
        {
            throw "Element " + form + ' not found.';
        }
    }
    ,toObject: function() {
        var input = this.form.select('input');
        var select = this.form.select('select');
        var textarea = this.form.select('textarea');
        
        var elements = input.elements.concat(select.elements).concat(textarea.elements);
        
        var Data = {};
        
        Ext.each(elements, function(element) {
            if(element.name)
            {
                if(element.type == 'checkbox')
                {
                    Data[element.name] = element.checked?element.value:false;                    
                }
				else if(element.type == 'radio')
                {
					if(element.checked)
						Data[element.name] = element.value;
                }
				else
                {
                    Data[element.name] = element.value;
                }
            }
        }, this);
        
        return Data;
    }
    ,toQueryString: function() {
        var Data = this.toObject();
        var temp = [];
        for(i in Data)
        {
            temp.push(encodeURI(i) + '=' + encodeURI(Data[i]));
        }
        return temp.join('&');
    }
    ,toJSON: function() {
        return Ext.JSON.encode(this.toObject());
    }
});

Ext.override(Ext.data.Store, {
    /**
     * @override
     * The fix is to rearrange store.data so that it has the same order as the records in groups.
     * Multiple methods are using the order of store.data for finding the record.
     */
    group: function () {
        var store = this,
            groups;


        grid.getSelectionModel().deselectAll();


        store.callOverridden(arguments);


        groups = store.getGroups();


        store.data.clear();


        Ext.Array.each(groups, function (group) {
            Ext.Array.each(group.children, function (child) {
                store.data.add(child.internalId, child);
            });
        });


        // update the view index after the store data is sorted by groups
        grid.getView().updateIndexes();
    },
	getSum: function(records, field) {
        var total = 0,
            i = 0,
            len = records.length;
        for (; i < len; ++i) {
            total += records[i].get(field)*1;
        }
        return total;
    },
    getMin: function(records, field) {
        var i = 1,
            len = records.length,
            value, min;
        if (len > 0) {
            min = records[0].get(field)*1;
        }
        for (; i < len; ++i) {
            value = records[i].get(field)*1;
            if (value < min) {
                min = value;
            }
        }
        return min;
    },
    getMax: function(records, field) {
        var i = 1,
            len = records.length,
            value,
            max;
        if (len > 0) {
            max = records[0].get(field)*1;
        }
        for (; i < len; ++i) {
            value = records[i].get(field)*1;
            if (value > max) {
                max = value;
            }
        }
        return max;
    },
    getAverage: function(records, field) {
		 var i = 0,
            len = records.length,
            sum = 0;
        if (records.length > 0) {
            for (; i < len; ++i) {
                sum += records[i].get(field)*1;
            }
            return sum / len;
        }
        return 0;
    }
});

/*--------------------- detect browser ------------------*/
navigator.sayswho = (function(){
    var ua= navigator.userAgent, tem, 
    M= ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
    if(/trident/i.test(M[1])){
        tem=  /\brv[ :]+(\d+)/g.exec(ua) || [];
        return 'IE '+(tem[1] || '');
    }
    if(M[1]=== 'Chrome'){
        tem= ua.match(/\bOPR\/(\d+)/)
        if(tem!= null) return 'Opera '+tem[1];
    }
    M= M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
    if((tem= ua.match(/version\/(\d+)/i))!= null) M.splice(1, 1, tem[1]);
    return M.join(' ');
})();


Ext.apply(Ext.data.Model.prototype,{

	setAll: function(data){
				
		for (var key in data) {
            this.set(key, data[key]);
          }
	}
});


//***********************************************************
//********** grid group select record bug fix ***************
//***********************************************************
Ext.override(Ext.data.Store, {

  fireEvent: function(eventName, store) {
    if(store && store.isStore && eventName === "datachanged") {
      this.sortGroupedStore();
    }
    
    return this.callParent(arguments);
  },
  sortGroupedStore: function() {
    if (this.isGrouped()) {


      var me = this,
          collection = me.data,
          items = [],
          keys = [],
          groups, length, children, lengthChildren,
          i, j;


      groups = me.getGroups();
      length = groups.length;


      for (i = 0; i < length; i++) {
        children = groups[i].children;
        lengthChildren = children.length;


        for (j = 0; j < lengthChildren; j++) {
          items.push(children[j]);
          keys.push(children[j].internalId);
        }
      }


      collection.items = items;
      collection.keys = keys;


      collection.fireEvent('sort', collection, items, keys);
    }
  }
});  

//***********************************************************
//**********************  CHART *****************************
//***********************************************************
Ext.override(Ext.chart.Legend, {
    labelFont: '11px tahoma'
});
Ext.override(Ext.chart.theme.Base, {
    constructor: function(config) {
        Ext.chart.theme.call(this, config, {
            background: false,
            axis: {
                stroke: '#444',
                'stroke-width': 1
            },
            axisLabelTop: {
                fill: '#444',
                font: '11px Tahoma, Helvetica, sans-serif',
                spacing: 2,
                padding: 5,
                renderer: function(v) { return v; }
            },
            axisLabelRight: {
                fill: '#444',
                font: '11px Tahoma, Helvetica, sans-serif',
                spacing: 2,
                padding: 5,
                renderer: function(v) { return v; }
            },
            axisLabelBottom: {
                fill: '#444',
                font: '11px Tahoma, Helvetica, sans-serif',
                spacing: 2,
                padding: 5,
                renderer: function(v) { return v; }
            },
            axisLabelLeft: {
                fill: '#444',
                font: '11px Tahoma, Helvetica, sans-serif',
                spacing: 2,
                padding: 5,
                renderer: function(v) { return v; }
            },
            axisTitleTop: {
                font: 'bold 12px Tahoma',
                fill: '#444'
            },
            axisTitleRight: {
                font: 'bold 12px Tahoma',
                fill: '#444',
                rotate: {
                    x:0, y:0,
                    degrees: 270
                }
            },
            axisTitleBottom: {
                font: 'bold 12px Tahoma',
                fill: '#444'
            },
            axisTitleLeft: {
                font: 'bold 12px Tahoma',
                fill: '#444',
                rotate: {
                    x:0, y:0,
                    degrees: 270
                }
            },
            series: {
                'stroke-width': 0
            },
            seriesLabel: {
                font: '9px Tahoma',
                fill: '#333'
            },
            marker: {
                stroke: '#555',
                radius: 3,
                size: 3
            },
            colors: [ "#94ae0a", "#115fa6","#a61120", "#ff8809", "#ffd13e", "#a61187", "#24ad9a", "#7c7474", "#a66111"],
            seriesThemes: [{
                fill: "#115fa6"
            }, {
                fill: "#94ae0a"
            }, {
                fill: "#a61120"
            }, {
                fill: "#ff8809"
            }, {
                fill: "#ffd13e"
            }, {
                fill: "#a61187"
            }, {
                fill: "#24ad9a"
            }, {
                fill: "#7c7474"
            }, {
                fill: "#115fa6"
            }, {
                fill: "#94ae0a"
            }, {
                fill: "#a61120"
            }, {
                fill: "#ff8809"
            }, {
                fill: "#ffd13e"
            }, {
                fill: "#a61187"
            }, {
                fill: "#24ad9a"
            }, {
                fill: "#7c7474"
            }, {
                fill: "#a66111"
            }],
            markerThemes: [{
                fill: "#115fa6",
                type: 'circle' 
            }, {
                fill: "#94ae0a",
                type: 'cross'
            }, {
                fill: "#115fa6",
                type: 'plus' 
            }, {
                fill: "#94ae0a",
                type: 'circle'
            }, {
                fill: "#a61120",
                type: 'cross'
            }]
        });
    }
});

//***********************************************************
//**********************  Tree combo ************************
//***********************************************************
/*
Tree combo
Use with 'Ext.data.TreeStore'

If store root note has 'checked' property tree combo becomes multiselect combo (tree store must have records with 'checked' property)

Has event 'itemclick' that can be used to capture click

Options:
selectChildren - if set true and if store isn't multiselect, clicking on an non-leaf node selects all it's children
canSelectFolders - if set true and store isn't multiselect clicking on a folder selects that folder also as a value

Use:

single leaf node selector:
selectChildren: false
canSelectFolders: false
- this will select only leaf nodes and will not allow selecting non-leaf nodes

single node selector (can select leaf and non-leaf nodes)
selectChildren: false
canSelectFolders: true
- this will select single value either leaf or non-leaf

children selector:
selectChildren: true
canSelectFolders: true
- clicking on a node will select it's children and node, clicking on a leaf node will select only that node

This config:
selectChildren: true
canSelectFolders: false
- is invalid, you cannot select children without node

*/
Ext.define('Ext.ux.TreeCombo',
{
	extend: 'Ext.form.field.Picker',
	alias: 'widget.treecombo',
	tree: false,
	constructor: function(config)
	{
		this.addEvents(
		{
			"itemclick" : true
		});


		this.listeners = config.listeners;
		this.callParent(arguments);
	},
	records: [],
	recursiveRecords: [],
	ids: [],
	selectChildren: true,
	canSelectFolders: true,
	multiselect: false,
	displayField: 'text',
	valueField: 'id',
	treeWidth: 300,
	matchFieldWidth: true,
	treeHeight: 400,
	masN: 0,
	hiddenName: '',    
	hiddenDataCls: Ext.baseCSSPrefix + 'hide-display ' + Ext.baseCSSPrefix + 'form-data-hidden',
	fieldSubTpl: [
        '<div class="{hiddenDataCls}" role="presentation"></div>',
        '<input id="{id}" type="{type}" {inputAttrTpl}',
            '<tpl if="value"> value="{value}"</tpl>',
            '<tpl if="name"> name="{name}"</tpl>',
            '<tpl if="placeholder"> placeholder="{placeholder}"</tpl>',
            '<tpl if="size"> size="{size}"</tpl>',
            '<tpl if="maxLength !== undefined"> maxlength="{maxLength}"</tpl>',
            '<tpl if="readOnly"> readonly="readonly"</tpl>',
            '<tpl if="disabled"> disabled="disabled"</tpl>',
            '<tpl if="tabIdx"> tabIndex="{tabIdx}"</tpl>',
            '<tpl if="fieldStyle"> style="{fieldStyle}"</tpl>',
            'class="{fieldCls} {typeCls}" autocomplete="off" />',
        {
            compiled: true,
            disableFormats: true
        }
    ],
	getSubTplData: function(){
        var me = this;
        Ext.applyIf(me.subTplData, {
            hiddenDataCls: me.hiddenDataCls
        });
        return me.callParent(arguments);
    },
	afterRender: function(){
        var me = this;
        me.callParent(arguments);
        me.setHiddenValue(me.value);
    },
	initComponent: function() 
	{
		var	me = this;
		
		me.tree = Ext.create('Ext.tree.Panel',
		{
			alias: 'widget.assetstree',
			hidden: true,
			minHeight: 300,
			rootVisible: (typeof me.rootVisible != 'undefined') ? me.rootVisible : true,
			floating: true,
			useArrows: true,
			width: me.treeWidth,
			autoScroll: true,
			height: me.treeHeight,
			store: me.store,
			listeners:
			{
				load: function(store, records)
				{
					if(me.afterLoadSetValue != false)
					{
						me.setValue(me.afterLoadSetValue);
					}
				},
				itemclick:  function(view, record, item, index, e, eOpts)
				{
					me.itemTreeClick(view, record, item, index, e, eOpts, me)
				}
			}
		});
		
		if(me.tree.getRootNode().get('checked') != null) me.multiselect = true;
		
		this.createPicker = function()
		{
			var	me = this;
			return me.tree;
		};
		
		Ext.applyIf(me.renderSelectors, {
            hiddenDataEl: '.' + me.hiddenDataCls.split(' ').join('.')
        });
		
		this.callParent(arguments);
	},
	setHiddenValue: function(values){
        var me = this,
            name = me.hiddenName, 
            i;
        if (!me.hiddenDataEl || !name) {
            return;
        }
        values = Ext.Array.from(values);
        var dom = me.hiddenDataEl.dom,
            childNodes = dom.childNodes,
            input = childNodes[0],
            valueCount = values.length,
            childrenCount = childNodes.length;
        if (!input) {
            me.hiddenDataEl.update(Ext.DomHelper.markup({
                tag: 'input', 
                type: 'hidden', 
                name: name
            }));
            childrenCount = 1;
            input = dom.firstChild;
        }
        while (childrenCount > valueCount) {
            dom.removeChild(childNodes[0]);
            -- childrenCount;
        }
        while (childrenCount < valueCount) {
            dom.appendChild(input.cloneNode(true));
            ++ childrenCount;
        }
        for (i = 0; i < valueCount; i++) {
            childNodes[i].value = values[i];
        }
    },
	recursivePush: function(node, setIds)
	{
		var	me = this;


		me.addRecRecord(node);
		if(setIds) me.addIds(node);
		
		node.eachChild(function(nodesingle)
		{
			if(nodesingle.hasChildNodes() == true)
			{
				me.recursivePush(nodesingle, setIds);
			}
			else
			{
				me.addRecRecord(nodesingle);
				if(setIds) me.addIds(nodesingle);
			}
		});
	},
	recursiveUnPush: function(node)
	{
		var	me = this;
		me.removeIds(node);
		
		node.eachChild(function(nodesingle)
		{
			if(nodesingle.hasChildNodes() == true)
			{
				me.recursiveUnPush(nodesingle);
			}
			else me.removeIds(nodesingle);
		});
	},
	addRecRecord: function(record)
	{
		var	me = this;


		for(var i=0,j=me.recursiveRecords.length;i<j;i++)
		{
			var item = me.recursiveRecords[i];
			if(item)
			{
				if(item.getId() == record.getId()) return;
			}
		}
		me.recursiveRecords.push(record);
	},
	afterLoadSetValue: false,
	setValue: function(valueInit)
	{
		if(typeof valueInit == 'undefined') return;
		if(valueInit == null)
			valueInit = "";
		
		var	me = this,
			tree = this.tree,
			values = (valueInit == '') ? [] : valueInit.split(','),
			valueFin = [];
	
			
		inputEl = me.inputEl;


		if(tree.store.isLoading())
		{
			me.afterLoadSetValue = valueInit;
			me.setHiddenValue(valueInit);
		}


		if(inputEl && me.emptyText && !Ext.isEmpty(values))
		{
			inputEl.removeCls(me.emptyCls);
		}


		if(tree == false) return false;
		
		var node = tree.getRootNode();
		if(node == null) return false;
		
		me.recursiveRecords = [];
		me.recursivePush(node, false);
		
		me.records = [];
		Ext.each(me.recursiveRecords, function(record)
		{
			var	id = record.get(me.valueField),
				index = values.indexOf(''+id);
		
			if(me.multiselect == true) record.set('checked', false);
			
			if(index != -1)
			{
				valueFin.push(record.get(me.displayField));
				if(me.multiselect == true) record.set('checked', true);
				me.addRecord(record);
			}
		});


		me.value = valueInit;
		me.setRawValue(valueFin.join(', '));
		me.setHiddenValue(valueInit);
		
		me.checkChange();
		me.applyEmptyText();
		return me;
	},
	getValue: function() 
	{
		return this.value;
	},
	getSubmitValue: function()
	{
		return this.value;
	},
	checkParentNodes: function(node)
	{
		if(node == null) return;
		
		var	me = this,
			checkedAll = true;


		node.eachChild(function(nodesingle)
		{
			var	id = nodesingle.getId(),
				index = me.ids.indexOf(''+id);
				
			if(index == -1) checkedAll = false;
		});
		
		if(checkedAll == true)
		{
			me.addIds(node);
			me.checkParentNodes(node.parentNode);
		}
		else
		{
			me.removeIds(node);
			me.checkParentNodes(node.parentNode);
		}
	},
	addIds: function(record)
	{
		var	me = this;
		
		if(me.ids.indexOf(''+record.getId()) == -1) me.ids.push(''+record.get(me.valueField));
	},
	removeIds: function(record)
	{
		var	me = this,
			index = me.ids.indexOf(''+record.getId());
			
		if(index != -1)
		{
			me.ids.splice(index, 1);
		}
	},
	addRecord: function(record)
	{
		var	me = this;


		for(var i=0,j=me.records.length;i<j;i++)
		{
			var item = me.records[i];
			if(item)
			{
				if(item.getId() == record.getId()) return;
			}
		}
		me.records.push(record);
	},
	removeRecord: function(record)
	{
		var	me = this;


		for(var i=0,j=me.records.length;i<j;i++)
		{
			var item = me.records[i];
			if(item && item.getId() == record.getId()) delete(me.records[i]);
		}
	},
	itemTreeClick: function(view, record, item, index, e, eOpts, treeCombo)
	{
		var	me = treeCombo,
			checked = !record.get('checked');//it is still not checked if will be checked in this event
		
		if(me.multiselect == true) record.set('checked', checked);//check record
		
		var node = me.tree.getRootNode().findChild(me.valueField, record.get(me.valueField), true);
		if(node == null) 
		{
			if(me.tree.getRootNode().get(me.valueField) == record.get(me.valueField)) node = me.tree.getRootNode();
			else return false;
		}
		
		if(me.multiselect == false) me.ids = [];
		
		//if it can't select folders and it is a folder check existing values and return false
		if(me.canSelectFolders == false && record.get('leaf') == false)
		{
			me.setRecordsValue(view, record, item, index, e, eOpts, treeCombo);
			return false;
		}
		
		//if record is leaf
		if(record.get('leaf') == true) 
		{
			if(checked == true)
			{
				me.addIds(record);
			}
			else
			{
				me.removeIds(record);
			}
		}
		else //it's a directory
		{			
			me.recursiveRecords = [];
			if(checked == true)
			{
				if(me.multiselect == false)
				{
					if(me.canSelectFolders == true) me.addIds(record); 
				}
				else
				{
					if(me.canSelectFolders == true)
					{
						me.recursivePush(node, true);
					}
				}
			}
			else
			{
				if(me.multiselect == false)
				{
					if(me.canSelectFolders == true) me.recursiveUnPush(node);
					else me.removeIds(record);
				}
				else me.recursiveUnPush(node);
			}
		}
		
		//this will check every parent node that has his all children selected
		if(me.canSelectFolders == true && me.multiselect == true) me.checkParentNodes(node.parentNode);
		
		me.setRecordsValue(view, record, item, index, e, eOpts, treeCombo);
		me.fireEvent('select', me, me.records);
	},
	fixIds: function()
	{
		var me = this;
		
		for(var i=0,j=me.ids.length;i<j;i++)
		{
			if(me.ids[i] == 'NaN') me.ids.splice(i, 1);
		}
	},
	setRecordsValue: function(view, record, item, index, e, eOpts, treeCombo)
	{
		var	me = treeCombo;
		
		me.fixIds();
		
		me.setValue(me.ids.join(','));


		me.fireEvent('itemclick', me, record, item, index, e, eOpts, me.records, me.ids);


		if(me.multiselect == false) me.onTriggerClick();
	}	
});

//***********************************************************
//**********************  CheckCombo ************************
//***********************************************************
Ext.override(Ext.form.field.ComboBox, {
	
    setHiddenValue: function(values){
        var me = this,
            name = me.hiddenName, 
            i;
        if (!me.hiddenDataEl || !name) {
            return;
        }
        values = Ext.Array.from(values);
        var dom = me.hiddenDataEl.dom,
            childNodes = dom.childNodes,
            input = childNodes[0],
            valueCount = values.length;
        //var childrenCount = childNodes.length;
        if (!input && valueCount > 0) {
            me.hiddenDataEl.update(Ext.DomHelper.markup({
                tag: 'input', 
                type: 'hidden', 
                name: name
            }));
            //childrenCount = 1;
            input = dom.firstChild;
        }
		if (input)
			input.value = "";
		for (i = 0; i < valueCount; i++) 
            input.value += values[i] + (i+1 < valueCount ? "," : "" );
        /*while (childrenCount > valueCount) {
            dom.removeChild(childNodes[0]);
            -- childrenCount;
        }
        while (childrenCount < valueCount) {
            dom.appendChild(input.cloneNode(true));
            ++ childrenCount;
        }
        for (i = 0; i < valueCount; i++) {
            childNodes[i].value = values[i];
        }*/
    }
});
Ext.define('Ext.ux.CheckCombo',
{
	extend: 'Ext.form.field.ComboBox',
	alias: 'widget.checkcombo',
	multiSelect: true,
	allSelector: false,
	addAllSelector: false,
	allText: 'All',
	createPicker: function() 
	{
	   var me = this,
	       picker,
	       menuCls = Ext.baseCSSPrefix + 'menu',
	       opts = Ext.apply({
	           pickerField: me,
	           selModel: {
	               mode: me.multiSelect ? 'SIMPLE' : 'SINGLE'
	           },
	           floating: true,
	           hidden: true,
	           ownerCt: me.ownerCt,
	           cls: me.el.up('.' + menuCls) ? menuCls : '',
	           store: me.store,
	           displayField: me.displayField,
	           focusOnToFront: false,
	           pageSize: me.pageSize,
	           tpl: 
			[
				'<ul><tpl for=".">',
					'<li role="option" class="' + Ext.baseCSSPrefix + 'boundlist-item"><span class="x-combo-checker">&nbsp;</span> {' + me.displayField + '}</li>',
				'</tpl></ul>'
			]
	       }, me.listConfig, me.defaultListConfig);

	   picker = me.picker = Ext.create('Ext.view.BoundList', opts);
	   if (me.pageSize) {
	       picker.pagingToolbar.on('beforechange', me.onPageChange, me);
	   }		

	   me.mon(picker, {
	       itemclick: me.onItemClick,
	       refresh: me.onListRefresh,
	       scope: me
	   });

	   me.mon(picker.getSelectionModel(), {
	       'beforeselect': me.onBeforeSelect,
	       'beforedeselect': me.onBeforeDeselect,
	       'selectionchange': me.onListSelectionChange,
	       scope: me
	   });

	   return picker;
    },
    getValue: function()
    {
    		return this.value.join(',');
    },
    getSubmitValue: function()
	{
		return this.getValue();
	},
    expand: function()
    {
	   var me = this,
	       bodyEl, picker, collapseIf;

	   if (me.rendered && !me.isExpanded && !me.isDestroyed) {
	       bodyEl = me.bodyEl;
	       picker = me.getPicker();
	       collapseIf = me.collapseIf;

	       // show the picker and set isExpanded flag
	       picker.show();
	       me.isExpanded = true;
	       me.alignPicker();
	       bodyEl.addCls(me.openCls);

			if(me.addAllSelector == true && me.allSelector == false)
			{
				me.allSelector = picker.getEl().insertHtml('afterBegin', '<div class="x-boundlist-item" role="option"><span class="x-combo-checker">&nbsp;</span> '+me.allText+'</div>', true);
				me.allSelector.on('click', function(e)
				{
					if(me.allSelector.hasCls('x-boundlist-selected'))
					{
						me.allSelector.removeCls('x-boundlist-selected');
						me.setValue('');
						me.fireEvent('select', me, []);
					}
					else
					{
						var records = [];
						me.store.each(function(record)
						{
							records.push(record);
						});
						me.allSelector.addCls('x-boundlist-selected');
						me.select(records);
						me.fireEvent('select', me, records); 
					}
				});
			}
	       // monitor clicking and mousewheel
	       me.mon(Ext.getDoc(), {
	           mousewheel: collapseIf,
	           mousedown: collapseIf,
	           scope: me
	       });
	       Ext.EventManager.onWindowResize(me.alignPicker, me);
	       me.fireEvent('expand', me);
	       me.onExpand();
	   }
    },
    onListSelectionChange: function(list, selectedRecords) 
    {
	   var me = this,
	       isMulti = me.multiSelect,
	       hasRecords = selectedRecords.length > 0;
	   // Only react to selection if it is not called from setValue, and if our list is
	   // expanded (ignores changes to the selection model triggered elsewhere)
	   if (me.isExpanded) {
	       if (!isMulti) {
	           Ext.defer(me.collapse, 1, me);
	       }
	       /*
	        * Only set the value here if we're in multi selection mode or we have
	        * a selection. Otherwise setValue will be called with an empty value
	        * which will cause the change event to fire twice.
	        */
	
	       if (isMulti || hasRecords) {
	           me.setValue(selectedRecords, false);
	       }
	       if (hasRecords) {
	           me.fireEvent('select', me, selectedRecords);
	       }
	       me.inputEl.focus();
	   }
	   
		if(me.addAllSelector == true && me.allSelector != false)
		{
			if(selectedRecords.length == me.store.getTotalCount()) me.allSelector.addCls('x-boundlist-selected');
			else me.allSelector.removeCls('x-boundlist-selected'); 
		}    
    }
});

//***********************************************************
//**********************  CheckList *************************
//***********************************************************
Ext.define('Ext.ux.CheckList', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.checklist',
    layout: {
        type: 'table'
    },
    frame: true,
	frameHeader: false,
	columns : 1,
   initComponent : function(){
        var me = this,
            cols = me.columns;
        if (cols) {
            me.layout = Ext.apply({}, {columns: cols}, me.layout);
        }
        var me = this;
		if (!me.store) throw "No store defined for StoreCheckboxGroup";
		if (!me.mapFn) throw "No mapFn defined for StoreCheckboxGroup";
		if (me.items) throw "Items may not be defined for StoreCheckboxGroup; we get them from the store!";
		var checklistStore = me.store, // <-- using the required "store" property
			renderCheckboxes = function() {
				me.removeAll();
				me.add(
				checklistStore.getRange().map(me.mapFn)); // <-- using the required "mapFn" property
			};
		checklistStore.on({
			load: renderCheckboxes, // <-------- binding the
			update: renderCheckboxes, // <------ function to
			datachanged: renderCheckboxes, // <- the store
			filterchange: renderCheckboxes // <- events
		});
        me.callParent(arguments);
    }
});