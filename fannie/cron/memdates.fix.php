<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* HELP
 
   memdates.fix.php

   Set start dates & mail flag
   For members that made their first
   equity purchase today

*/

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$TRANS = $FANNIE_TRANS_DB.($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

// legacy, wfc table. probably go away eventually
if ($sql->table_exists('mbrmaster')){
	$mmQ = "update mbrmastr set mailflag=1,
		startdate =
			right('00'+convert(varchar,datepart(mm,s.startdate)),2)
			+'/'+
			right('00'+convert(varchar,datepart(dd,s.startdate)),2)
			+'/'+
			convert(varchar,datepart(yy,s.startdate)),
		enddate =
		case when s.payments >= 100 then '' else
			right('00'+convert(varchar,datepart(mm,s.startdate)),2)
			+'/'+
			right('00'+convert(varchar,datepart(dd,s.startdate)),2)
			+'/'+
			convert(varchar,datepart(yy,s.startdate)+2) end
		from newbalancestocktoday_test s
		left join mbrmastr as m on m.memnum=s.memnum
		left join custdata as c on c.cardno=s.memnum
		and c.personnum=1
		where m.startdate='' and s.payments > 0
		and c.type='PC'";
	$sql->query($mmQ);
}

$miQ = "UPDATE meminfo AS m 
	INNER JOIN {$TRANS}newBalanceStockToday_test s
	ON m.card_no=s.memnum
	INNER JOIN custdata AS c ON c.CardNo=s.memnum
	LEFT JOIN memDates AS d ON d.card_no=s.memnum
	SET m.ads_OK=1
	WHERE (d.start_date IS null OR d.start_date = '0000-00-00 00:00:00')
	AND s.payments > 0
	AND c.Type='PC'";
if ($FANNIE_SERVER_DBMS == 'MSSQL'){
	$miQ = "UPDATE meminfo SET ads_OK=1
		FROM {$TRANS}newbalancestocktoday_test s
		left join meminfo m ON m.card_no=s.memnum
		left join custdata as c on c.cardno=s.memnum
		left join memDates as d on d.card_no=s.memnum
		where d.start_date is null and s.payments > 0
		and c.type='PC'";
}
$sql->query($miQ);

$mdQ = "UPDATE memDates AS d
	INNER JOIN {$TRANS}newBalanceStockToday_test AS s
	ON d.card_no=s.memnum
	INNER JOIN custdata AS c ON c.CardNo=s.memnum
	SET d.start_date=s.startdate,
	d.end_date=DATE_ADD(s.startdate,INTERVAL 2 YEAR)
	WHERE (d.start_date IS null OR d.start_date = '0000-00-00 00:00:00')
	AND s.payments > 0
	AND c.Type='PC'";
if ($FANNIE_SERVER_DBMS == 'MSSQL'){
	$mdQ = "UPDATE memDates SET start_date=s.startdate,
		end_date=dateadd(yy,2,s.startdate)
		FROM {$TRANS}newbalancestocktoday_test s
		left join custdata as c on c.cardno=s.memnum
		left join memDates as d on d.card_no=s.memnum
		where d.start_date is null and s.payments > 0
		and c.type='PC'";
}
$sql->query($mdQ);
