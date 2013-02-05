<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');

class VendorMovementReport extends FannieReportPage {

	function preprocess(){
		$this->report_cache = 'day';
		$this->title = "Fannie : Vendor Movement";
		$this->header = "Vendor Movement Report";

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(False);
		
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return True;
	}

	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB;
		$date1 = get_form_value('date1',date('Y-m-d'));
		$date2 = get_form_value('date2',date('Y-m-d'));
		$vendor = get_form_value('vendor','');
		$groupby = get_form_value('groupby','upc');

		$dlog = select_dlog($date1,$date2);
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";

		$type_condition = sprintf("e.manufacturer like %s",$dbc->escape('%'.$manu.'%'));

		$query = "";
		switch($groupby){
		case 'upc':
			$query = "select t.upc,p.description,
				  sum(t.quantity) as qty,
				  sum(t.total),d.dept_no,d.dept_name,s.superID
				  from $sumTable as t left join products as p
				  on t.upc=p.upc left join prodExtra as e on p.upc = e.upc
				  left join departments as d on p.department = d.dept_no
				  left join MasterSuperDepts as s on d.dept_no = s.dept_ID
				  where e.distributor like ?
				  and t.tdate between '$date1 00:00:00' and '$date2 23:59:59'
				  group by t.upc,p.description,d.dept_no,d.dept_name,s.superID
				  order by sum(t.total) desc";
			break;
		case 'date':
			$query = "select year(t.tdate),month(t.tdate),day(t.tdate),
				sum(t.quantity),sum(t.total)
				  from products as p left join prodExtra as e on p.upc = e.upc
				  left join $sumTable as t on p.upc = t.upc
				  where e.distributor like ?
				  and t.tdate between ? AND ?
				  group by year(t.tdate),month(t.tdate),day(t.tdate)
				  order by year(t.tdate),month(t.tdate),day(t.tdate)";
			break;
		case 'dept':
			$query = "select d.dept_no,d.dept_name,sum(t.quantity),sum(t.total),s.superID
				  from products as p left join prodExtra as e on p.upc = e.upc
				  left join $sumTable as t on p.upc = t.upc
				  left join departments as d on p.department = d.dept_no
				  left join MasterSuperDepts as s on d.dept_no=s.dept_ID
				  where e.distributor like ?
				  and t.tdate between ? AND ?
				  group by d.dept_no,d.dept_name,s.superID
				  order by sum(t.total) desc";
			break;
		}
		$args = array('%'.$vendor.'%',$date1.' 00:00:00',$date2.' 23:59:59');
		$prep = $dbc->prepare_statement($query);

		$result = $dbc->exec_statement($prep,$args);
		$ret = array();
		while ($row = $dbc->fetch_array($result)){
			$record = array();
			if ($groupby == "date"){
				$record[] = $row[1]."/".$row[2]."/".$row[0];
				$record[] = $row[3];
				$record[] = $row[4];
			}
			else {
				for($i=0;$i<$dbc->num_fields($result);$i++)
					$record[] .= $row[$i];
			}
			$ret[] = $record;
		}
		return $ret;
	}
	
	function calculate_footers($data){
		if (empty($data))
			return array();
		switch(count($data[0])){
		case 7:
			$this->report_headers = array('UPC','Description','Qty','$',
				'Dept#','Department','Subdept');
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[2];
				$sumSales += $row[3];
			}
			return array('Total',null,$sumQty,$sumSales,null,null,null);
			break;
		case 5:
			$this->report_headers = array('Dept#','Department','Qty','$','Subdept');
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[2];
				$sumSales += $row[3];
			}
			return array('Total',null,$sumQty,$sumSales,null);
			break;
		case 3:
			$this->report_headers = array('Date','Qty','$');
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[1];
				$sumSales += $row[2];
			}
			return array('Total',$sumQty,$sumSales);
			break;
		}
	}

	function form_content(){
?>
<div id=main>	
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<td> <p><b>Vendor</b></p>
			</td>
			<td><p>
			<input type=text name=vendor id=vendor  />
			</p>
			</td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<tr>
		<td><b>Sum report by</b></td>
		<td><select name=groupby>
		<option value="upc">UPC</option>
		<option value="date">Date</option>
		<option value="dept">Department</option>
		</select></td>
		</tr>
		<td> <input type=submit name=submit value="Submit"> </td>
		<td> <input type=reset name=reset value="Start Over"> 
		<input type=checkbox name=excel value=xls /> Excel </td>
		<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
	}
}

$obj = new VendorMovementReport();
$obj->draw_page();
?>
