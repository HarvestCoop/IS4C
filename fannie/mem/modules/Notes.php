<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class Notes extends MemberModule {

	function ShowEditForm($memNum, $country="US"){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = $dbc->prepare_statement("SELECT note,stamp FROM memberNotes
				WHERE cardno=? ORDER BY stamp DESC");
		$infoR = $dbc->exec_statement($infoQ,array($memNum));

		$note = "";
		$date = "";

		$ret = "<fieldset><legend>Notes</legend>";

		$ret .= "<table class=\"MemFormTable\" border=\"0\">";
		$ret .= "<tr><th>Additional Notes</th>";
//		$ret .= "<td><a href=\"\">History</a></td></tr>";
		$ret .= "<td> ";
		if ($dbc->num_rows($infoR) > 0){
			$ret .= "<input type=\"button\" value=\"History\" id=\"historyButton\"
				style=\"display:block;\"
				onclick=\"
					tb = document.getElementById('noteHistory'); tb.style.display='block';
					nhb = document.getElementById('noHistoryButton'); nhb.style.display='block';
					hb = document.getElementById('historyButton'); hb.style.display='none';
					\"
				/>";
			$ret .= "<input type=\"button\" value=\"NoHistory\" id=\"noHistoryButton\"
				style=\"display:none;\"
				onclick=\"
					tb = document.getElementById('noteHistory'); tb.style.display='none';
					hb = document.getElementById('historyButton'); hb.style.display='block';
					nhb = document.getElementById('noHistoryButton'); nhb.style.display='none';
					\"
				/>";
		}
		$ret .= "</td></tr>\n";
		$ret .= "<tr><td colspan=\"2\"><textarea name=\"Notes_text\" rows=\"4\" cols=\"25\">";
//		$ret .= $note;
		$ret .= "</textarea></td></tr>";
		$ret .= "</table>\n";

		$ret .= "<table id=\"noteHistory\" class=\"MemFormTable\" border=\"0\" style=\"display:none;\">";
		while (	$infoW = $dbc->fetch_row($infoR) ) {
			$note = str_replace("<br />","\n",$infoW['note']);
			$date = $infoW['stamp'];
			$ret .= "<tr><td>$date</td><td>$note</td></tr>\n";
		}
		$ret .= "</table>\n";

		$ret .= "</fieldset>\n";
		return $ret;
	}

	function SaveFormData($memNum){

		$note = FormLib::get_form_value('Notes_text');
		if ( $note == "" ) {
			return "";
		}

		$dbc = $this->db();

		$insertNote = $dbc->prepare_statement("INSERT into memberNotes
				(cardno, note, stamp, username)
				VALUES (?, ?, ".$dbc->now().", 'Admin')");

		$test1 = $dbc->exec_statement($insertNote,array($memNum,$note));

		if ($test1 === False )
			return "Error: problem saving Notes<br />";
		else
			return "";
	}

// Notes
}

?>
