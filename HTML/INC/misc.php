<?php

/* ===== error handling ===== */

function debug ($string) {
	return;
        echo "<!-- " . $string . " -->" ;
	echo "\n";
}

function stop ($class, $string) {
	global $bcs, $VERSION;
	if ($string != "") echo "<div class=\"$class message\">" . $string . "</div>";
	include 'INC/footer.php';
	exit;
}

function error_msg ($str = FALSE) {
	static $error_str = "";
	if ($str) $error_str=$str;
	return $error_str;
}


/* ===== input handling ===== */

function input ($string) {
	$string = htmlspecialchars($string,ENT_QUOTES);
	$string = mysql_real_escape_string($string);
	return $string;
}

function convertBytes( $value ) {
    if ( is_numeric( $value ) ) {
        return $value;
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k':	$qty *= 1024;		break;
            case 'm':	$qty *= 1024*1024;	break;
            case 'g':	$qty *= 1024*1024*1024;	break;
        }
        return $qty;
    }
}



/* ===== display helpers ===== */

function user_name ($user) {
	if ($user->name == "") return $user->user;
	return user_full($user);
}

function user_full ($user) {
	return $user->user . " (" . $user->name . ")" ;
}

function device_name ($device) {
	if ($device->name != "") return $device->name;
	return "Device #" . $device->id;
}

function device_full ($device) {
	if ($device->name == "") return "#" . $device->id;
	return $device->name . " (" . $device->id . ")" ;
}


function show_data ( $config = '', $secret = "", $fill = "", $sel_user = "", $cur_user = "", $sel_role = '', $cur_role = '' ) {
	switch ($config) {
	   case '':		// dont show anything
		return FALSE; 
		break;
	   case '-':		// dont show anything
		return FALSE;
		break;
	   case 'A': 		// administrators can see all data
		if ($sel_user == $cur_user) return $secret;
		if ($cur_role == 'A') return $secret;
		return $fill;
		break;
	   case 'H':		// helpdesk can see user data
		if ($sel_user == $cur_user) return $secret;
		if ($cur_role == 'A') return $secret;
		if ($cur_role == 'H') {
			if ( ($sel_role == 'A') || ($sel_role == 'H') ) return $fill;
			return $secret;
		}
		return $fill;
		break;
	   case 'S':
		if ($sel_user == $cur_user) return $secret;
		return $fill;
		break;
	}
}


function warning ($string) {
 	echo "<div id=\"error-box\">";
 	echo $string;
 	echo "<script type=\"text/javascript\">";
 	echo "setTimeout(function() {document.getElementById('error-box').style.display='none';},4000);";
 	echo "</script>";
 	echo "</div>";
}



/* ===== HTML functions ===== */

function bcs ( $bcs ) {
	$str = "";
	$del = "";
	foreach ($bcs as $bc ) {
		$str .= $del;
		$str .= a( $bc[0], strtoupper($bc[1]) );
		$del = " - ";
	}
	return '<div id="bcs">' . $str . '</div>';
}

function help ( $content ) {
	return '<div id="help">' . $content . '</div>';
}

function br () { 
	return "<br />"; 
}

function p ($par) { 
	return "<p>" . $par . "</p>";
}

function a ( $url, $text ) {
	return "<a href=\"$url\">$text</a>";
}

function table_header ( $line = FALSE, $class = FALSE ) {
	$str = "";
	$str .= "<table" . ($class ? " class=\"$class\"" : "") . ">\n";
	if ($line) {
		$str .= "  <thead>\n";
		$str .= "    <tr>\n";
		foreach ($line as $th) {
			$str .= "<th>" . $th . "</th>\n";
		}
		$str .= "    </tr>\n";
		$str .= "  </thead>\n";
	}
	$str .= "  <tbody>\n";
	return $str;
}

function table_row ( $line = FALSE, $colspan = 0 ) {
	if ( $colspan != 0 ) {		
		$str = "";
		$str .= "    <tr>\n";
		$str .= "<td colspan=\"$colspan\">" . $line . "</td>\n";
		$str .= "    </tr>\n";
	} else {
		$str = "";
		$str .= "    <tr>\n";
		foreach ($line as $td) {
			if (is_null($td)) continue;
			$str .= "<td>" . $td . "</td>\n";
		}
		$str .= "    </tr>\n";
	}	
	return $str;
}

function table_footer ( $line = FALSE ) {
	$str = "";
	$str .= "  </tbody>\n";
	if ($line) {
		$str .= "  <tfoot>\n";
		$str .= "    <tr>\n";
		foreach ($line as $td) {
			$str .= "<td>" . $td . "</td>\n";
		}
		$str .= "    </tr>\n";
		$str .= "  </tfoot>\n";
	}
	$str .= "</table>\n";
	return $str;
}


function form_header ($method, $action, $confirm = FALSE) {
	if ($confirm)
		$confirm = "onsubmit=\"return confirm('"
			. addslashes(str_replace('&#039;',"'",$confirm))
			. "');\"";
	return "<form method=\"$method\" action=\"$action\" $confirm>\n";
}

function form_footer () {
	return "</form>\n";
}

function input_hidden ($name, $value) {
	return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
}

function input_text ($name, $value, $size = 10, $attr = "" ) {
	return "<input $attr type=\"text\" name=\"$name\" value=\"$value\" size=\"$size\">\n";
}

function input_file ($name) {
	return "<input type=\"file\" name=\"$name\">\n";
}

function input_password ($name, $value, $size = 10) {
	return "<input type=\"password\" name=\"$name\" value=\"$value\" size=\"$size\">\n";
}

function input_submit ($value, $img = "") {
	if (SHOW_BUTTONS)
		if ($img != "") return input_image($value, $img);
	return "<input type=\"submit\" value=\"$value\" class=\"submit\">\n";
}

function input_image ($alt, $src) {
	$src="IMG/".$src.".png";
	return '<div class="button">'
		. "<input type=\"image\" src=\"$src\" alt=\"$alt\" title=\"$alt\" class=\"button submit\">\n"
		. "<div>$alt</div>"
		. "</div>\n"
		;
}

function input_area ($name, $text, $cols="", $rows="" ) {
	if ($cols) $cols="cols=\"$cols\"";
	if ($rows) $rows="rows=\"$rows\"";
	return "<textarea name=\"$name\" $cols $rows>$text</textarea>";
}

function input_checkbox ($name, $value, $checked=FALSE) {
	return "<input type=\"checkbox\" name=\"$name\" value=\"$value\"" . ($checked?"checked":"") . ">\n";
}

function select_header ($name, $css = "") {
	if ($css) $css=" style=\"$css\""; else $css= "";
	return "<select name=\"$name\"$css>\n";
}

function select_footer () {
	return "</select>";
}

function select_option ($value, $text, $selected = FALSE) {
	return "<option value=\"$value\"" . ($selected ? " selected" : "") . ">" . $text . "</option>\n";
}

function div_header ($ver, $id) {
	if ($ver == "id")
		return "<div id=\"$id\">\n";
	else
		return "<div class=\"$id\">\n";
}

function div_footer () {
	return "</div>\n";
}

function li ($type, $entries) {
	$str = "<$type class=\"text\">";
	switch ($type) {
		case "ul": $li = "li"; $dt = "u";  $dd = "";   break;
		case "ol": $li = "li"; $dt = "i";  $dd = "";   break;
		case "dl": $li = "";   $dt = "dt"; $dd = "dd"; break;
		default:   $li = "li"; $dt = "";   $dd = "";   break;
	}
	foreach ($entries as $entry) {
		if ($li != "") $str .= "<li>";
		if (is_array($entry)) {
			if ( ($entry[0]!="") && ($dt!="") ) $entry[0] = "<$dt>" . $entry[0] . "</$dt>"; 
			if ( ($entry[1]!="") && ($dd!="") ) $entry[1] = "<$dd>" . $entry[1] . "</$dd>"; 
			$str .= $entry[0] . $entry[1];
		} else {
			$str .= "$entry";
		}
		if ($li != "") $str .= "</li>";
	}
	$str .= "</$type>";
	return $str;
}

function nobreak ($str) {
	return '<span style="white-space:nowrap;">'
		. $str
		. '</span>';
}

