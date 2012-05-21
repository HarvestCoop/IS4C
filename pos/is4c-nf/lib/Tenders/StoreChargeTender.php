<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
  @class StoreChargeTender
  Tender module for charge accounts
*/
class StoreChargeTender extends TenderModule {

	/**
	  Check for errors
	  @return True or an error message string
	*/
	function ErrorCheck(){
		global $CORE_LOCAL;
		$charge_ok = PrehLib::chargeOk();
	
		if ($charge_ok == 0){
			return DisplayLib::boxMsg("member ".$CORE_LOCAL->get("memberID")."<br />is not authorized<br />to make charges");
		}
		else if ($CORE_LOCAL->get("availBal") < 0){
			return DisplayLib::boxMsg("member ".$CORE_LOCAL->get("memberID")."<br />is over limit");
		}
		elseif ((abs($CORE_LOCAL->get("memChargeTotal"))+ $this->amount) >= ($CORE_LOCAL->get("availBal") + 0.005)){
			$memChargeRemain = $CORE_LOCAL->get("availBal");
			$memChargeCommitted = $memChargeRemain + $CORE_LOCAL->get("memChargeTotal");
			return DisplayLib::xboxMsg("available balance for charge <br>is only \$" .$memChargeCommitted);
		}
		elseif(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) < MiscLib::truncate2($this->amount)) {
			return DisplayLib::xboxMsg("charge tender exceeds purchase amount");
		}

		return True;
	}
	
	/**
	  Set up state and redirect if needed
	  @return True or a URL to redirect
	*/
	function PreReqCheck(){
		global $CORE_LOCAL;
		$CORE_LOCAL->set("chargetender",1);
		return True;
	}
}

?>
