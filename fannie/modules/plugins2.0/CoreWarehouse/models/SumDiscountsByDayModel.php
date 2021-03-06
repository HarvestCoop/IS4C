<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

global $FANNIE_ROOT;
if (!class_exists('CoreWarehouseModel'))
	include_once(dirname(__FILE__).'/CoreWarehouseModel.php');

class SumDiscountsByDayModel extends CoreWarehouseModel {

	protected $name = 'sumDiscountsByDay';
	
	protected $columns = array(
	'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
	'memType' => array('type'=>'SMALLINT','primary_key'=>True,'default'=>''),
	'total' => array('type'=>'MONEY','default'=>0.00),
	'transCount' => array('type'=>'INT','default'=>0)
	);

	public function refresh_data($trans_db, $month, $year, $day=False){
		$start_id = date('Ymd',mktime(0,0,0,$month,1,$year));
		$start_date = date('Y-m-d',mktime(0,0,0,$month,1,$year));
		$end_id = date('Ymt',mktime(0,0,0,$month,1,$year));
		$end_date = date('Y-m-t',mktime(0,0,0,$month,1,$year));
		if ($day !== False){
			$start_id = date('Ymd',mktime(0,0,0,$month,$day,$year));
			$start_date = date('Y-m-d',mktime(0,0,0,$month,$day,$year));
			$end_id = $start_id;
			$end_date = $start_date;
		}

		$target_table = DTransactionsModel::selectDlog($start_date, $end_date);

		/* clear old entries */
		$sql = 'DELETE FROM '.$this->name.' WHERE date_id BETWEEN ? AND ?';
		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, array($start_id, $end_id));

		/* reload table from transarction archives */
		$sql = "INSERT INTO ".$this->name."
			SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
			memType,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			COUNT(DISTINCT trans_num) as transCount
			FROM $target_table WHERE
			tdate BETWEEN ? AND ? AND
			trans_type IN ('S') AND total <> 0
			AND upc='DISCOUNT' AND card_no <> 0
			GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), memType";
		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, array($start_date.' 00:00:00',$end_date.' 23:59:59'));
	}

	/* START ACCESSOR FUNCTIONS */

	public function date_id(){
		if(func_num_args() == 0){
			if(isset($this->instance["date_id"]))
				return $this->instance["date_id"];
			elseif(isset($this->columns["date_id"]["default"]))
				return $this->columns["date_id"]["default"];
			else return null;
		}
		else{
			$this->instance["date_id"] = func_get_arg(0);
		}
	}

	public function memType(){
		if(func_num_args() == 0){
			if(isset($this->instance["memType"]))
				return $this->instance["memType"];
			elseif(isset($this->columns["memType"]["default"]))
				return $this->columns["memType"]["default"];
			else return null;
		}
		else{
			$this->instance["memType"] = func_get_arg(0);
		}
	}

	public function total(){
		if(func_num_args() == 0){
			if(isset($this->instance["total"]))
				return $this->instance["total"];
			elseif(isset($this->columns["total"]["default"]))
				return $this->columns["total"]["default"];
			else return null;
		}
		else{
			$this->instance["total"] = func_get_arg(0);
		}
	}

	public function transCount(){
		if(func_num_args() == 0){
			if(isset($this->instance["transCount"]))
				return $this->instance["transCount"];
			elseif(isset($this->columns["transCount"]["default"]))
				return $this->columns["transCount"]["default"];
			else return null;
		}
		else{
			$this->instance["transCount"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}
