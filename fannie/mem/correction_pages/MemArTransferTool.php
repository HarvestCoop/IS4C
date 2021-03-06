<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

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

include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class MemArTransferTool extends FanniePage {

	protected $title='Fannie - Member Management Module';
	protected $header='Transfer A/R';
	//was: protected $header='Transfer Member Equity';

	private $errors = '';
	private $mode = 'init';
	private $depts = array();

	private $CORRECTION_CASHIER = 1001;
	private $CORRECTION_LANE = 30;
	private $CORRECTION_DEPT = 800;

	private $dept;
	private $amount;
	private $cn1;
	private $cn2;
	private $name1;
	private $name2;

	function preprocess(){
		global $FANNIE_AR_DEPARTMENTS;
		global $FANNIE_OP_DB;

		if (empty($FANNIE_AR_DEPARTMENTS)){
			$this->errors .= "<em>Error: no AR departments found</em>";
			return True;
		}

		$ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
		if ($ret == 0){
			$this->errors .= "<em>Error: can't read AR department definition</em>";
			return True;
		}
		$temp_depts = array_pop($depts);

		$dlist = "(";
		$dArgs = array();
		foreach ($temp_depts as $d){
			$dlist .= "?,";	
			$dArgs[] = $d;
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$q = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments WHERE dept_no IN $dlist");
		$r = $dbc->exec_statement($q,$dArgs);
		if ($dbc->num_rows($r) == 0){
			return "<em>Error: equity department(s) don't exist</em>";
		}

		$this->depts = array();
		while($row = $dbc->fetch_row($r)){
			$this->depts[$row[0]] = $row[1];
		}

		if (FormLib::get_form_value('submit1',False) !== False)
			$this->mode = 'confirm';
		elseif (FormLib::get_form_value('submit2',False) !== False)
			$this->mode = 'finish';

		// error check inputs
		if ($this->mode != 'init'){

			$this->dept = FormLib::get_form_value('dept');
			$this->amount = FormLib::get_form_value('amount');
			$this->cn1 = FormLib::get_form_value('memFrom');
			$this->cn2 = FormLib::get_form_value('memTo');

			if (!isset($this->depts[$this->dept])){
				$this->errors .= "<em>Error: AR department doesn't exist</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}
			if (!is_numeric($this->amount)){
				$this->errors .= "<em>Error: amount given (".$this->amount.") isn't a number</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}
			if (!is_numeric($this->cn1)){
				$this->errors .= "<em>Error: member given (".$this->cn1.") isn't a number</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}
			if (!is_numeric($this->cn2)){
				$this->errors .= "<em>Error: member given (".$this->cn2.") isn't a number</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}

			$q = $dbc->prepare_statement("SELECT FirstName,LastName FROM custdata WHERE CardNo=? AND personNum=1");
			$r = $dbc->exec_statement($q,array($this->cn1));
			if ($dbc->num_rows($r) == 0){
				$this->errors .= "<em>Error: no such member: ".$this->cn1."</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}
			$row = $dbc->fetch_row($r);
			$this->name1 = $row[0].' '.$row[1];

			$q = $dbc->prepare_statement("SELECT FirstName,LastName FROM custdata WHERE CardNo=? AND personNum=1");
			$r = $dbc->exec_statement($q,array($this->cn2));
			if ($dbc->num_rows($r) == 0){
				$this->errors .= "<em>Error: no such member: ".$this->cn2."</em>"
					."<br /><br />"
					."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
				return True;
			}
			$row = $dbc->fetch_row($r);
			$this->name2 = $row[0].' '.$row[1];
		}

		return True;
	}
	
	function body_content(){
		if ($this->mode == 'init')
			return $this->form_content();
		elseif($this->mode == 'confirm')
			return $this->confirm_content();
		elseif($this->mode == 'finish')
			return $this->finish_content();
	}

	function confirm_content(){

		if (!empty($this->errors)) return $this->errors;

		$ret = "<form action=\"MemArTransferTool.php\" method=\"post\">";
		$ret .= "<b>Confirm transfer</b>";
		$ret .= "<p style=\"font-size:120%\">";
		$ret .= sprintf("\$%.2f %s will be moved from %d (%s) to %d (%s)",
			$this->amount,$this->depts[$this->dept],
			$this->cn1,$this->name1,$this->cn2,$this->name2);
		$ret .= "</p><p>";
		$ret .= "<input type=\"hidden\" name=\"dept\" value=\"{$this->dept}\" />";
		$ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
		$ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"{$this->cn1}\" />";
		$ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
		$ret .= "<input type=\"submit\" name=\"submit2\" value=\"Confirm\" />";
		$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$ret .= "<input type=\"submit\" value=\"Back\" onclick=\"back(); return false;\" />";
		$ret .= "</form>";
		
		return $ret;
	}

	function finish_content(){

		if (!empty($this->errors)) return $this->errors;

		$ret = '';
		
		$dtrans = array();
		$dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
		$dtrans['trans_id'] = 1;
		$this->doInsert($dtrans,$this->amount,$this->CORRECTION_DEPT,$this->cn1);

		$dtrans['trans_id']++;
		$this->doInsert($dtrans,-1*$this->amount,$this->dept,$this->cn1);

		$ret .= sprintf("Receipt #1: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$dtrans['trans_no']);

		$dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
		$dtrans['trans_id'] = 1;
		$this->doInsert($dtrans,$this->amount,$this->dept,$this->cn2);

		$dtrans['trans_id']++;
		$this->doInsert($dtrans,-1*$this->amount,$this->CORRECTION_DEPT,$this->cn2);

		$ret .= "<br /><br />";
		$ret .= sprintf("Receipt #2: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$dtrans['trans_no']);

		return $ret;
	}

	function form_content(){

		if (!empty($this->errors)) return $this->errors;

		$ret = "<form action=\"MemArTransferTool.php\" method=\"post\">";
		$ret .= "<p style=\"font-size:120%\">";
		$ret .= "Transfer $<input type=\"text\" name=\"amount\" size=\"5\" /> ";
		$ret .= "<select name=\"dept\">";
		foreach($this->depts as $k=>$v)
			$ret .= "<option value=\"$k\">$v</option>";
		$ret .= "</select>";
		$ret .= "</p><p style=\"font-size:120%;\">";
		$memNum = FormLib::get_form_value('memIN');
		$ret .= "From member #<input type=\"text\" name=\"memFrom\" size=\"5\" value=\"$memNum\" /> ";
		$ret .= "to member #<input type=\"text\" name=\"memTo\" size=\"5\" />";
		$ret .= "</p><p>";
		$ret .= "<input type=\"hidden\" name=\"type\" value=\"equity_transfer\" />";
		$ret .= "<input type=\"submit\" name=\"submit1\" value=\"Submit\" />";
		$ret .= "</p>";
		$ret .= "</form>";

		return $ret;
	}

	function getTransNo($emp,$register){
		global $FANNIE_TRANS_DB;
		$dbc = FannieDB::get($FANNIE_TRANS_DB);
		$q = $dbc->prepare_statement("SELECT max(trans_no) FROM dtransactions WHERE register_no=? AND emp_no=?");
		$r = $dbc->exec_statement($q,array($register,$emp));
		$n = array_pop($dbc->fetch_row($r));
		return (empty($n)?1:$n+1);	
	}

	function doInsert($dtrans,$amount,$department,$cardno){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
		$dbc = FannieDB::get($FANNIE_TRANS_DB);
		$OP = $FANNIE_OP_DB.$dbc->sep();

		$defaults = array(
			'register_no'=>$this->CORRECTION_LANE,
			'emp_no'=>$this->CORRECTION_CASHIER,
			'trans_no'=>$dtrans['trans_no'],
			'upc'=>'',
			'description'=>'',
			'trans_type'=>'D',
			'trans_subtype'=>'',
			'trans_status'=>'',
			'department'=>'',
			'quantity'=>1,
			'scale'=>0,
			'cost'=>0,
			'unitPrice'=>'',
			'total'=>'',
			'regPrice'=>'',
			'tax'=>0,
			'foodstamp'=>0,
			'discount'=>0,
			'memDiscount'=>0,
			'discountable'=>0,
			'discounttype'=>0,
			'voided'=>0,
			'percentDiscount'=>0,
			'ItemQtty'=>1,
			'volDiscType'=>0,
			'volume'=>0,
			'volSpecial'=>0,
			'mixMatch'=>'',
			'matched'=>0,
			'memType'=>'',
			'staff'=>'',
			'numflag'=>0,
			'charflag'=>'',
			'card_no'=>'',
			'trans_id'=>$dtrans['trans_id']
		);

		$defaults['department'] = $department;
		$defaults['card_no'] = $cardno;
		$defaults['unitPrice'] = $amount;
		$defaults['regPrice'] = $amount;
		$defaults['total'] = $amount;
		if ($amount < 0){
			$defaults['trans_status'] = 'R';
			$defaults['quantity'] = -1;
		}
		$defaults['upc'] = abs($amount).'DP'.$department;

		if (isset($this->depts[$department]))
			$defaults['description'] = $this->depts[$department];
		else {
			$nameP = $dbc->prepare_statement("SELECT dept_name FROM {$OP}departments WHERE dept_no=?");
			$nameR = $dbc->exec_statement($nameP,$department);
            if ($dbc->num_rows($nameR) == 0) {
                $defaults['description'] = 'CORRECTIONS';
            } else {
                $nameW = $dbc->fetch_row($nameR);
                $defaults['description'] = $nameW['dept_name'];
            }
		}

		$q = $dbc->prepare_statement("SELECT memType,Staff FROM {$OP}custdata WHERE CardNo=?");
		$r = $dbc->exec_statement($q,array($cardno));
		$w = $dbc->fetch_row($r);
		$defaults['memType'] = $w[0];
		$defaults['staff'] = $w[1];

		$columns = 'datetime,';
		$values = $dbc->now().',';
		$args = array();
		foreach($defaults as $k=>$v){
			$columns .= $k.',';
			$values .= '?,';
			$args[] = $v;
		}
		$columns = substr($columns,0,strlen($columns)-1);
		$values = substr($values,0,strlen($values)-1);
		$prep = $dbc->prepare_statement("INSERT INTO dtransactions ($columns) VALUES ($values)");
		$dbc->exec_statement($prep, $args);
	}
}

FannieDispatch::conditionalExec(false);

?>
