
document.write(
	  '<link href="CSS/hints.css" rel="stylesheet" type="text/css"/>'
	+ '<!--[if lt IE 7]>'
	+   '<link href="CSS/hints-ie.css" rel="stylesheet" type="text/css"/>'
	+ '<![endif]-->'
);

var GOOD = "good_small.png";
var BAD  = "bad_small.png";

function activate ( id, checks, checkfunc, helptext ) {
	var elem = document.getElementById(id);
	html =    '<div class="hint" id="hint-' + id + '">'
		+  '<div>'
		+    '<span class="hint-arrow"></span>'
		+    '<ul>'
		+       checks 
		+    '</ul>'
		+  '</div>'
		+ '</div>'
		;
	// html = elem.outerHTML + html;
	// elem.outerHTML = html;
	var ne = document.createElement("span");
	ne.innerHTML = html;
	elem.parentNode.appendChild(ne);

	elem = document.getElementById(id);
	elem.style.cssFloat = "left";

	elem.onfocus  = function () { hint(id,helptext); };
	elem.onblur   = function () { unhint(id); };
	unhint(id);

	if (checkfunc) {
		// elem.onchange = checkfunc;
		elem.onkeyup  = checkfunc;
		checkfunc();
	}
}

var defaulthelp = false;

function hint ( id, helptext ) {
	id = "hint-" + id;
	document.getElementById(id).style.display = "block";
	var help = document.getElementById("help");
	if ( help && helptext ) {
		if (! defaulthelp) {
			defaulthelp = help.innerHTML;
			help.style.height = help.offsetHeight - 10;
		}
		help.innerHTML = helptext;
	}
}

function unhint ( id ) {
	id = "hint-" + id;
	document.getElementById(id).style.display = "none";
	if (defaulthelp) {
		document.getElementById("help").innerHTML = defaulthelp;
	}
}


function imgurl ( img ) { 
	return "url(/IMG/" + img + ")";
}



/********* device secret **********************/

if (document.getElementById("device-secret")) {
	var html = '<li id="s1">16 or 32 characters</li>'
		 + '<li id="s2">hex characters</li>'
	;
	var checkfunc = function () {
		var value = document.getElementById("device-secret").value;

		var img = BAD;
		if ( (value.length == 16) || (value.length == 32) )
			img = GOOD;
		document.getElementById("s1").style.listStyleImage = imgurl(img);

		var img = BAD;
		if ( value.match(/^[0-9a-fA-F]*$/g) )
			img = GOOD;
		document.getElementById("s2").style.listStyleImage = imgurl(img);
	};
	var text = '<p>The device secret normally consists of 16 hex characters. Some implementations use 32 characters.</p>'
		 + '<p>MOTP-AS supports up to 32 characters.</p>'
		 ;
	activate("device-secret",html,checkfunc,text);
}



/********* account pin **********************/

function weakpin (pin) {

	// check for same diff between digits
	diff=100; temp=pin;
	while (temp>=10) {
		last = temp % 10;
		temp = Math.floor(temp/10);
		before = temp % 10;
		if (diff == 100) diff = last-before;
		if (diff == last-before) continue;
		diff = 100;
		break;
	}
	if ( (diff>=-1) && (diff<=1) ) return "Same difference between digits";

	// check typical combinations
	if (pin == 2580) return "Typical combination";

	// check xyxy combinations and palindroms
	if (pin>=1000) {
		left = Math.floor(pin/100);
		right = pin % 100;
		if (left == right) return "repeated numbers";
		rr = 10*(right%10) + Math.floor(right/10);
		if (left == rr) return "palindrom";
	}

	return "";
}

if (document.getElementById("account-pin")) {
	var html = '<li id="p1">4 digits</li>'
		 + '<li id="p2">only digits</li>'
		 + '<li id="p3">PIN strength</li>'
	;
	var checkfunc = function () {
		var value = document.getElementById("account-pin").value;

		var img = BAD;
		if (value.length == 4)
			img = GOOD;
		document.getElementById("p1").style.listStyleImage = imgurl(img);

		var img = BAD;
		if ( value.match(/^[0-9]*$/g) )
			img = GOOD;
		document.getElementById("p2").style.listStyleImage = imgurl(img);

		text = weakpin(value);
		if ( text != "" ) {
			img = BAD;
			text = "PIN weak: " + text;
		} else {
			img = GOOD;
			text = "PIN strength";
		}
		document.getElementById("p3").style.listStyleImage = imgurl(img);
		document.getElementById("p3").innerHTML = text;
	};
	var text = '<p>The default MOTP implementation uses 4 digits as PIN. A few MOTP clients support more than 4 digits.</p>'
		 + '<p>MOTP-AS supports up to 8 characters (digits or small letters) as PIN.</p>'
		 ;
	activate("account-pin",html,checkfunc,text);
}


/********* RADIUS IP **********************/

function checkipv4 (ip) {
	if (ip == "0.0.0.0") return false;
	if (ip == "255.255.255.255") return false;
	var pattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
	var octets = ip.match(pattern);
	if (octets == null) return false;
	for (i = 0; i < 4; i++) {
		if (octets[i] > 255) return false;
   	}
	return true;
}

function checkipv6 (ip) {
	var pattern = /^([0-9a-f]{1,4}:+)*::?([0-9a-f]{1,4}:+)*[0-9a-f]{1,4}$/i;
	if ( ip.match(pattern) )
		return true;
	return false;
}

if (document.getElementById("radius-ip")) {
	var html = '<li id="i1">Valid IP address</li>' ;
	var checkfunc = function () {
		var value = document.getElementById("radius-ip").value;

		var img = BAD;
		if ( checkipv4(value) || checkipv6(value) ) img = GOOD;
		document.getElementById("i1").style.listStyleImage = imgurl(img);

		if ( checkipv4(value) ) text = 'Valid IPv4 address' ;
		if ( checkipv6(value) ) text = 'Valid IPv6 address' ;
		document.getElementById("i1").innerText = text;
	};
	var text = false;
	activate("radius-ip",html,checkfunc,text);
}


/********* device timezone **********************/

function checktz (tz) {
	if (tz.NaN) return false;
	if ( (tz>14) || (tz<-12) ) return false;
	if (tz != Math.floor(tz)) return false;
	return true;
}

if (document.getElementById("device-tz")) {
	var html = '<li id="t1">Timezone as hours to UTC</li>'
	;
	var checkfunc = function () {
		var value = document.getElementById("device-tz").value;

		var img = BAD;
		if (checktz(value)) {
			img = GOOD;
		}
		document.getElementById("t1").style.listStyleImage = imgurl(img);

	};
	var text = false;
	activate("device-tz",html,checkfunc,text);
}



