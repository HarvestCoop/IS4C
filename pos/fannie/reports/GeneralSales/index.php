<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['submit'])){
	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];
	$dept = $_REQUEST['dept'];

	$dlog = select_dlog($d1,$d2);

	if (isset($_REQUEST['excel'])){
		header("Content-Disposition: inline; filename=sales_{$d1}_{$d2}.xls");
		header("Content-type: application/vnd.ms-excel; name='excel'");
	}
	else{
		printf("<a href=index.php?date1=%s&date2=%s&dept=%s&submit=yes&excel=yes>Save to Excel</a>",
			$d1,$d2,$dept);
	}

	$sales = "SELECT d.Dept_name,sum(t.total),sum(t.quantity),
			s.superID,s.super_name
			FROM departments as d, $dlog as t,
			MasterSuperDepts AS s
			WHERE d.Dept_no= t.Department 
			AND d.Dept_no=s.dept_ID
			AND (tDate BETWEEN '$d1 00:00:00' AND '$d2 23:59:59') 
			AND (s.superID > 0) 
			AND (t.trans_type = 'I' or t.trans_type = 'D')
			GROUP BY s.superID,s.super_name,d.dept_name,t.department
			ORDER BY s.superID,t.department";
	if ($dept == 1){
		$sales = "SELECT CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
			sum(t.total),sum(t.quantity),
			CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
			CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END
			FROM $dlog AS t LEFT JOIN
			products AS p ON t.upc=p.upc LEFT JOIN
			departments AS d ON d.dept_no=t.department LEFT JOIN
			departments AS e ON p.department=e.dept_no LEFT JOIN
			MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
			MasterSuperDepts AS r ON r.dept_ID=t.department
			WHERE
			(tDate BETWEEN '$d1 00:00:00' AND '$d2 23:59:59') 
			AND (t.trans_type = 'I' or t.trans_type = 'D')
			AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0))
			GROUP BY
			CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
			CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
			CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
			CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
			ORDER BY
			CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
			CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
	}
	$supers = array();
	$salesR = $dbc->query($sales);
	
	$curSuper = 0;
	$grandTotal = 0;
	while($row = $dbc->fetch_row($salesR)){
		if ($curSuper != $row[3]){
			$curSuper = $row[3];
			$supers[$curSuper] = array('sales'=>0.0,'qty'=>0.0,'name'=>$row[4],'depts'=>array());
		}
		$supers[$curSuper]['sales'] += $row[1];
		$supers[$curSuper]['qty'] += $row[2];
		$supers[$curSuper]['depts'][] = array('name'=>$row[0],'sales'=>$row[1],'qty'=>$row[2]);
		$grandTotal += $row[1];
	}

	foreach($supers as $s){
		echo "<table border=1>\n";//create table
		echo "<tr align=right bgcolor='FFFF99'><td>&nbsp;</td><td>Sales</td><td>Qty</td><td>% Sales</td><td>Dept %</td></tr>\n";//create table header
		$superSum = $s['sales'];
		foreach($s['depts'] as $d){
			printf("<tr align=right><td size=25>%s</td><td size=25>\$%.2f</td><td size=15>%.2f</td>
				<td size=15>%.2f %%</td><td size=15>%.2f %%</td></tr>\n",
				$d['name'],$d['sales'],$d['qty'],
				$d['sales'] / $grandTotal * 100,
				$d['sales'] / $superSum * 100);
		}

		printf("<tr border = 1 align=right bgcolor=#ffff99><th>%s</th><th>\$%.2f</th><th>%.2f</th>
			<th>%.2f %%</th><td>&nbsp;</td></tr>\n",
			$s['name'],$s['sales'],$s['qty'],$s['sales']/$grandTotal * 100);
			
		echo "</table><br />";
	}

	printf("<b>Total Sales: </b>\$%.2f",$grandTotal);
}
else {

$page_title = "Fannie : General Sales Report";
$header = "General Sales Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
	if (date("w",$ts) == 1 && $lastSunday != "")
		$lastMonday = date("Y-m-d",$ts);
	elseif(date("w",$ts) == 0)
		$lastSunday = date("Y-m-d",$ts);
	$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));	
}
?>
<script type="text/javascript"
	src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js">
</script>
<form action=index.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
</tr><tr>
<th>End Date</th>
<td><input type=text name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
</tr><tr>
<td colspan=2><select name=dept>
<option value=0>Use department settings at time of sale</option>
<option value=1>Use current department settings</option>
</select></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
