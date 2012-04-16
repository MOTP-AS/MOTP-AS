<html><head>
<title>Universal Mobile-OTP</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1"></head><body bgcolor="#f8f8ff" OnLoad="document.userinput.pin.focus();">
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0; user-scalable=1;"/>
<font face="arial">

<h2>Universal Mobile-OTP</h2>

<script type="text/javascript">

<?php
	include 'JS/md5.js';
	echo "var secret = \"$SECRET\" ;";
?>

</script>

<script type="text/javascript">

function motp (pin, secret) {
  var time = new Date();
  time = time.getTime() / 1000 / 10;
  time = Math.floor(time);
  var otp = hex_md5( time + secret + pin );
  document.getElementById('pin').value="";
  return otp.substring(0,6);
}

function getOTP() {
  var pin=document.getElementById('pin').value;
  document.getElementById('otp').value = motp(pin,secret); 
}
</script>

<table>
 <tbody>
  <form name="userinput" onsubmit="getOTP();return false;">
    <tr><th>PIN:</th><td><input id="pin" size="4" type="password"></td><td><input type=submit value="OTP" type="button"></td></tr>
    <tr><th>Passcode:</th><td><input id="otp" size="6" type="text"></td></tr>
  </form>
 </tbody>
</table>
<p>


<script type="text/javascript">
document.write("<font size=-1>(c)2011 motp.sourceforge.net - epoch: <span id=epoch></span><font size=+0><p>");
function epoch () {
  var time = new Date();
  time = time.getTime() / 1000 / 10;
  time = Math.floor(time);
  document.getElementById('epoch').innerHTML = time;
  setTimeout('epoch()',1000);
}
epoch();
</script>

</body>
</html>
