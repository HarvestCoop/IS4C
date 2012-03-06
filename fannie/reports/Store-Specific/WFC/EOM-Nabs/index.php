<?php
include('../../../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="dailyReport.xls"');
}

$ALIGN_RIGHT = 1;
$ALIGN_LEFT = 2;
$ALIGN_CENTER = 4;
$TYPE_MONEY = 8;

?>
<HTML>
<head>
<style type=text/css>
td {
	font-size: .9em;
}
td.left {
	padding-right: 4em;
	text-align: left;
}
td.right {
	padding-left: 4em;
	text-align: right;
}
td.center {
	padding-left: 2em;
	padding-right: 2em;
	text-align: center;
}
</style>
</head>
<?php

echo "<b>";
$monthMinus = 1;
if (isset($_GET["monthMinus"])) $monthMinus = $_GET["monthMinus"];
$stamp = strtotime("-$monthMinus month");
echo strtoupper(date("F",$stamp));
echo " ";
echo date("Y",$stamp);
$dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);
echo " NABS</b><br />";
if (!isset($_GET["excel"]))
	echo "<a href=index.php?excel=yes&monthMinus=$monthMinus>Save to Excel</a>";
echo "<p />";

$start = date("Y-m-01",$stamp);
$end = date("Y-m-t",$stamp);
$span = "'$start 00:00:00' AND '$end 23:59:59'";

$accounts = array();
$accountQ = "SELECT CardNo from custdata WHERE memType=4 ORDER BY CardNo";
$accountR = $dbc->query($accountQ);
while($accountW = $dbc->fetch_row($accountR))
	$accounts[] = $accountW['CardNo'];

$accountStr = "(";
foreach($accounts as $a)
	$accountStr .= $a.",";
$accountStr = rtrim($accountStr,",").")";

echo "<b>Total by account</b>";
$totalQ = "select l.card_no,sum(l.total),
	(sum(l.total)-(sum(l.total*CONVERT(money,m.margin)))) as cost
	FROM $dlog as l left join deptMargin as m on l.department = m.dept_ID
	WHERE card_no IN $accountStr
	and (l.department < 600 or l.department = 902)
	and l.department <> 0 and l.trans_type <> 'T'
	and tdate BETWEEN $span
	GROUP BY card_no
	ORDER BY card_no";
$totalR = $dbc->query($totalQ);
$data = array();
while ($totalW=$dbc->fetch_row($totalR)){
	if (!isset($data["$totalW[0]"])){
		$data["$totalW[0]"] = array($totalW[1],$totalW[2]);
	}
	else {
		$data["$totalW[0]"][0] += $totalW[1];
		$data["$totalW[0]"][1] += $totalW[2];
	}
}
echo tablify($data,array(0,1,2),array("Account","Retail","Wholesale"),
	array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY),
	2,array(1,2));

echo "<br /><b>Total by pCode</b>";
$totalQ = "select d.salesCode,sum(l.total),
	(sum(l.total)-(sum(l.total)*CONVERT(money,m.margin))) as cost
	FROM $dlog as l left join deptSalesCodes as d on l.department = d.dept_ID
	left join deptMargin as m on l.department=m.dept_ID
	WHERE card_no IN $accountStr
	and (l.department < 600 or l.department = 902)
	and l.department <> 0 and l.trans_type <> 'T'
	and tdate BETWEEN $span
	GROUP BY d.salesCode,m.margin
	ORDER BY d.salesCode";
$totalR = $dbc->query($totalQ);
$data = array();
while ($totalW=$dbc->fetch_row($totalR)){
	if (empty($data["$totalW[0]"])){
		$data["$totalW[0]"] = array($totalW[1],$totalW[2]);
	}
	else {
		$data["$totalW[0]"][0] += $totalW[1];
		$data["$totalW[0]"][1] += $totalW[2];
	}
}
echo tablify($data,array(0,1,2),array("pCode","Retail","Wholesale"),
	array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY),
	2,array(1,2));

foreach ($accounts as $account){
	echo "<br /><b>Total for $account</b>";
	$totalQ = "select d.salesCode,sum(l.total),
		(sum(l.total)-(sum(l.total)*CONVERT(money,m.margin))) as cost
		FROM $dlog as l left join deptSalesCodes as d on l.department = d.dept_ID
		left join deptMargin as m on l.department=m.dept_ID
		WHERE card_no = $account
		and (l.department < 600 or l.department = 902)
		and l.department <> 0 and l.trans_type <> 'T'
		and tdate BETWEEN $span
		GROUP BY d.salesCode,m.margin
		ORDER BY d.salesCode";
	$totalR = $dbc->query($totalQ);
	$data = array();
	while ($totalW=$dbc->fetch_row($totalR)){
		if (empty($data["$totalW[0]"])){
			$data["$totalW[0]"] = array($totalW[1],$account,$totalW[2]);
		}
		else {
			$data["$totalW[0]"][0] += $totalW[1];
			$data["$totalW[0]"][2] += $totalW[2];
		}
	}
	echo tablify($data,array(0,1,2,3),array("pCode","Retail","Account","Wholesale"),
		array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_CENTER,$ALIGN_RIGHT|$TYPE_MONEY),
		2,array(1,3));
}

function tablify($data,$col_order,$col_headers,$formatting,$sums=-1,$sum_cols=array()){
	$sum = 0;
	$ret = "";
	
	$ret .= "<table cellspacing=0 cellpadding=4 border=1><tr>";
	$i = 0;
	foreach ($col_headers as $c){
		while ($formatting[$i] == 0) $i++;
		$ret .= cellify("<u>".$c."</u>",$formatting[$i++]&7);
	}
	$ret .= "</tr>";
	
	$ttls = array();
	if ($sums != -1){
		for ($i=0;$i<count($col_order);$i++)
			array_push($ttls,0);
	}

	foreach(array_keys($data) as $k){
		$ret .= "<tr>";
		foreach($col_order as $c){
			if($c == 0) $ret .= cellify($k,$formatting[$c]);
			else $ret .= cellify($data[$k][$c-1],$formatting[$c]);

			if (!empty($sum_cols)){
				foreach($sum_cols as $s){	
					if ($s == $c)
						$ttls[$c] += $data[$k][$c-1];
				}
			}
		}
		$ret .= "</tr>";
	}
	if (count($data) == 0){
		$ret .= "<tr>";
		$ret .= "<td colspan=".count($col_headers)." class=center>";
		$ret .= "No results to report"."</td>";
		$ret .= "</tr>";
	}

	if (!empty($sum_cols) && count($data) > 0){
		$ret .= "<tr>";
		foreach($col_order as $c){
			$skip = false;
			foreach ($sum_cols as $s){
				if ($s == $c){
					$ret .= cellify($ttls[$c],$formatting[$c]);
					$skip = true;
				}
			}
			if (!$skip)
				$ret .= "<td>&nbsp;</td>";
		}
		$ret .= "</tr>";
	}

	$ret .= "</table>";

	return $ret;
}

function cellify($data,$formatting){
	$ALIGN_RIGHT = 1;
	$ALIGN_LEFT = 2;
	$ALIGN_CENTER = 4;
	$TYPE_MONEY = 8;
	$ret = "";
	if ($formatting & $ALIGN_LEFT) $ret .= "<td class=left>";
	elseif ($formatting & $ALIGN_RIGHT) $ret .= "<td class=right>";
	elseif ($formatting & $ALIGN_CENTER) $ret .= "<td class=center>";

	if ($formatting & $TYPE_MONEY) $ret .= sprintf("%.2f",$data);
	else $ret .= $data;

	$ret .= "</td>";

	return $ret;
}

?>
