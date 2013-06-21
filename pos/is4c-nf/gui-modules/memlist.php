<?php
/*******************************************************************************

   Copyright 2010 Whole Foods Co-op

   This file is part of IT CORE.

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  5Oct2012 Eric Lee Added:
	*                    + A WEFC_Toronto-only chunk for collecting Member Card#
	*                    + A general facility for displaying an error encountered in preprocess()
	*                       in body_content() using temp_message.

*/

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $entered;
	var $db;
	var $temp_message;

	function preprocess(){
		global $CORE_LOCAL;

		// set variable ahead of time
		// so we know if lookup found no one
		// vs. lookup didn't happen
		$this->temp_num_rows = -1;

		$CORE_LOCAL->set("away",1);
		$entered = "";
		if ($CORE_LOCAL->get("idSearch") && strlen($CORE_LOCAL->get("idSearch")) > 0) {
			$entered = $CORE_LOCAL->get("idSearch");
			$CORE_LOCAL->set("idSearch","");
		}
		elseif (isset($_REQUEST['search'])){
			$entered = strtoupper(trim($_REQUEST["search"]));
			$entered = str_replace("'", "''", $entered);
		}
		else return True;

		if (substr($entered, -2) == "ID") $entered = substr($entered, 0, strlen($entered) - 2);

		$personNum = 1;
		$selected_name = False;
		// Values of memlist items are "CardNo::personNum"
		if (strstr($entered,"::") !== False){
			$tmp = explode("::",$entered);
			$entered = $tmp[0];
			$personNum = $tmp[1];
			$selected_name = True;
		}

		// No input available, stop
		if (!$entered || strlen($entered) < 1 || $entered == "CL") {
			$CORE_LOCAL->set("mirequested",0);
			$CORE_LOCAL->set("scan","scan");
			$CORE_LOCAL->set("reprintNameLookup",0);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		$memberID = $entered;
		$db_a = Database::pDataConnect();

		$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
			MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
			SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
			where CardNo = '".$entered."' order by personNum";
		if (!is_numeric($entered)) {
			$query = "select CardNo,personNum,LastName,FirstName from custdata 
				where LastName like '".$entered."%' order by LastName, FirstName";
		}
		if ($selected_name && is_numeric($personNum)){
			/**
			  13Feb13 Andy
			  Use personNum if provided so the lookup returns
			  the correct record
			*/
			$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
				MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
				SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
				where CardNo = '".$entered."' AND personNum=".$personNum;
		}

		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);

		// if theres only 1 match don't show the memlist
		if ($num_rows == 1 && $CORE_LOCAL->get("verifyName")==0) {
			$selected_name = True;
			$personNum = 1;
		}

		// if there's one result and either
		// a. it's the default nonmember account or
		// b. it's been confirmed in the select box
		// then set the member number
		// proceed/return to the appropriate next page
		if ( ($num_rows == 1 && $entered == $CORE_LOCAL->get("defaultNonMem"))
				||
		    (is_numeric($entered) && is_numeric($personNum) && $selected_name) ) {
			$row = $db_a->fetch_array($result);
			PrehLib::setMember($row["CardNo"], $personNum,$row);
			$CORE_LOCAL->set("scan","scan");

			// WEFC_Toronto: If a Member Card # was entered when the choice from the list was made,
			// add the memberCards record.
			if ( $CORE_LOCAL->get('store') == "WEFC_Toronto" ) {
				$mmsg = "";
				if ( isset($_REQUEST['memberCard']) && $_REQUEST['memberCard'] != "" ) {
					$memberCard = $_REQUEST['memberCard'];
					if ( !is_numeric($memberCard) || strlen($memberCard) > 5 || $memberCard == 0 ) {
						$mmsg = "Bad Member Card# format >{$memberCard}<";
					}
					else {
						$upc = sprintf("00401229%05d", $memberCard);
						// Check that it isn't already there, perhaps for someone else.
						$mQ = "SELECT card_no FROM memberCards where card_no = {$row['CardNo']}";
						$mResult = $db_a->query($mQ);
						$mNumRows = $db_a->num_rows($mResult);
						if ( $mNumRows > 0 ) {
							$mmsg = "{$row['CardNo']} is already associated with another Member Card";
						}
						else {
							$mQ = "INSERT INTO memberCards (card_no, upc) VALUES ({$row['CardNo']}, '$upc')";
							$mResult = $db_a->query($mQ);
							if ( !$mResult ) {
								$mmsg = "Linking membership to Member Card failed.";
							}
						}
					}
				}
				if ( $mmsg != "" ) {
					// Prepare to display the error.
					$this->temp_result = $result;
					$this->temp_num_rows = $num_rows;
					$this->entered = $entered;
					$this->db = $db_a;
					$this->temp_message = $mmsg;
					return True;
				}
			// /WEFC_Toronto bit.
			}

			// don't bother with unpaid balance check if there is no balance
			if ($entered != $CORE_LOCAL->get("defaultNonMem") && $CORE_LOCAL->get('balance') > 0){
				$unpaid = PrehLib::check_unpaid_ar($row["CardNo"]);
				if ($unpaid)
					$this->change_page($this->page_url."gui-modules/UnpaidAR.php");
				else
					$this->change_page($this->page_url."gui-modules/pos2.php");
			}
			else
				$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		// Prepare to display the memlist (list to choose from).
		$this->temp_result = $result;
		$this->temp_num_rows = $num_rows;
		$this->entered = $entered;
		$this->db = $db_a;
		$this->temp_message = "";
		return True;

	} // END preprocess() FUNCTION

	function head_content(){
		global $CORE_LOCAL;
		if ($this->temp_num_rows > 0){
			$this->add_onload_command("\$('#search').keypress(processkeypress);\n");
			$this->add_onload_command("\$('#search').focus();\n");
		} else {
			$this->default_parsewrapper_js('reginput','selectform');
			$this->add_onload_command("\$('#reginput').focus();\n");
		}
		?>
		<script type="text/javascript">
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#search option:selected').each(function(){
						$(this).val('');
					});
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;
		$num_rows = $this->temp_num_rows;
		$result = $this->temp_result;
		$entered = $this->entered;
		$db = $this->db;
		$message = $this->temp_message;

		echo "<div class=\"baseHeight\">"
			."<form id=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">";

		// First check for a problem found in preprocess.
		if ( $message != "" ) {
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">
			{$message}<br />".
			_("enter member number or name").
			"</span>
				<input type=\"text\" name=\"search\" size=\"15\"
			       	onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
				<br />press [enter] to cancel
			</div>";
		}
		/* for no results, just throw up a re-do
		 * otherwise, put results in a select box
		 */
		elseif ($num_rows < 1) {
			echo "
			<div class=\"colored centeredDisplay\">
				<span class=\"larger\">";
			if ($num_rows == -1)
				echo _("member search")."<br />"._("enter member number or name");
			else
				echo _("no match found")."<br />"._("next search or member number");
			echo "</span>
				<input type=\"text\" name=\"search\" size=\"15\"
			       	onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
				<br />
				press [enter] to cancel
			</div>";
		}
		else {
			echo "<div class=\"listbox\">"
				."<select name=\"search\" size=\"15\" "
				."onblur=\"\$('#search').focus();\" ondblclick=\"document.forms['selectform'].submit();\" id=\"search\">";

			$selectFlag = 0;
			if (!is_numeric($entered) && $CORE_LOCAL->get("memlistNonMember") == 1) {
				echo "<option value='3::1' selected> 3 "
					."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Customer";
				$selectFlag = 1;
			}

			for ($i = 0; $i < $num_rows; $i++) {
				$row = $db->fetch_array($result);
				if( $i == 0 && $selectFlag == 0) {
					$selected = "selected";
				} else {
					$selected = "";
				}
				echo "<option value='".$row["CardNo"]."::".$row["personNum"]."' ".$selected.">"
					.$row["CardNo"]." ".$row["LastName"].", ".$row["FirstName"]."\n";
			}
			echo "</select></div><!-- /.listbox -->"
				."<div class=\"listboxText centerOffset\">"
				._("use arrow keys to navigate")."<p>"._("clear to cancel")."</div><!-- /.listboxText .centerOffset -->"
				."<div class=\"clear\"></div>";

			// A textbox for the Member Card number, to be added to the db for the selected member.
			if ( $CORE_LOCAL->get('store') == "WEFC_Toronto" ) {
				echo "<div style='text-align:left; margin-top: 0.5em;'>
				<p style='margin: 0.2em 0em 0.2em 0em; font-size:0.8em;'>To link the member chosen above to a Member Card:</p>";
				echo "<span style='font-weight:bold;'>Member Card#:</span> <input name='memberCard' id='memberCard' width='20' title='The digits after 01229, no leading zeroes, not the final, small check-digit' />";
				echo "<p style='margin-top: 0.2em; font-size:0.8em;'>If the back of the card has: '4 01229 00125 7' enter 125
				<br />Then the card should be recognized in the scan.</p>";
				echo "</div>";
			}

		}
		echo "</form></div>";
	} // END body_content() FUNCTION

// /class memlist
}

new memlist();

?>
