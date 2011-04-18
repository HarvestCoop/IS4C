<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($_SESSION["INCLUDE_PATH"]."/parser-class-lib/Parser.php");
if (!function_exists("rePoll")) include_once($_SESSION["INCLUDE_PATH"]."/lib/lib.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class SigTermCommands extends Parser {

	function check($str){
		global $IS4C_LOCAL;
		if ($str == "TRESET"){
			$IS4C_LOCAL->set("ccTermOut","reset");
			return True;
		}
		if ($str == "TSIG"){
			$IS4C_LOCAL->set("ccTermOut","sig");
			return True;
		}
		return False;
	}

	function parse($str){
		global $IS4C_PATH,$IS4C_LOCAL;
		$ret = $this->default_json();
		$ret['udpmsg'] = $IS4C_LOCAL->get("ccTermOut");
		if ($ret['udpmsg'] == "sig")
			$ret['main_frame'] = $IS4C_PATH.'gui-modules/paycardSignature.php';
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>WAKEUP</td>
				<td>Try to coax a stuck scale back
				into operation</td>
			</tr>
			<tr>
				<td>WAKEUP2</td>
				<td>Different method, same goal</td>
			</tr>
			</table>";
	}
}

?>
