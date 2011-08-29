<?php
include('../../../config.php');
$date = $_GET['date'];
//if (!isset($_GET['date'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="AR'.$date.'.csv"');
//}

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

$SEP=",";
$Q = "";
$NL = "\r\n";

$query = "select num from lastmasinvoice";
$result = $sql->query($query);
$INV_NUM = (int)array_pop($sql->fetch_array($result));

$query = "select card_no,trans_num,
	-1*total,
        case when trans_subtype='MI' then 'STORE CHARGE' ELSE 'PAYMENT RECEIVED' END,
	datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
	case when trans_subtype='MI' then 'CG' else 'PY' END
        from dlog_90_view
        where datediff(dd,getdate(),tdate) = -1
        and (trans_subtype='MI' or department=990)
	and card_no <> 11
	order by department,card_no";
if (isset($_GET['date'])){
$query = "select card_no,trans_num,
	-1*total,
        case when trans_subtype='MI' then 'STORE CHARGE' ELSE 'PAYMENT RECEIVED' END,
	datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
	case when trans_subtype='MI' then 'CG' else 'PY' END
        from dlog_90_view
        where datediff(dd,'$date',tdate) = 0
        and (trans_subtype='MI' or department=990)
	and card_no <> 11
	order by department,card_no";

}
$result = $sql->query($query);

while ($row = $sql->fetch_row($result)){
	if ($INV_NUM <= 5000000) $INV_NUM=5000000;;
	echo "H".$SEP;
	echo $INV_NUM.$SEP;
	echo $row[0].$SEP;
	echo $Q.$row[4]."-".$row[5]."-".$row[6].$Q.$SEP;
	echo $Q.$row[3]." ".$row[1].$Q.$SEP;
	echo $Q.$row[1].$Q.$NL;
	echo "L".$SEP;
	echo $INV_NUM.$SEP;
	echo $Q.$row[7].$Q.$SEP;
	echo trim($row[2]).$NL;
	$INV_NUM=($INV_NUM+1)%10000000;
}

$INV_NUM--;
$sql->query("update lastmasinvoice set num=$INV_NUM");

?>
