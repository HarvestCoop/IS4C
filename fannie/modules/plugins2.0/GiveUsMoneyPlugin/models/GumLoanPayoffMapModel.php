<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

/**
  @class GumLoanPayoffMapModel

  This table stores a one-to-one relationship
  between GumLoanAccounts and GumPayoffs.
*/
class GumLoanPayoffMapModel extends BasicModel
{

    protected $name = "GumLoanPayoffMap";

    protected $columns = array(
    'gumLoanAccountID' => array('type'=>'INT', 'primary_key'=>true),
    'gumPayoffID' => array('type'=>'INT', 'primary_key'=>true),
	);

    /* START ACCESSOR FUNCTIONS */

    public function gumLoanAccountID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumLoanAccountID"])) {
                return $this->instance["gumLoanAccountID"];
            } else if (isset($this->columns["gumLoanAccountID"]["default"])) {
                return $this->columns["gumLoanAccountID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["gumLoanAccountID"]) || $this->instance["gumLoanAccountID"] != func_get_args(0)) {
                if (!isset($this->columns["gumLoanAccountID"]["ignore_updates"]) || $this->columns["gumLoanAccountID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumLoanAccountID"] = func_get_arg(0);
        }
    }

    public function gumPayoffID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumPayoffID"])) {
                return $this->instance["gumPayoffID"];
            } else if (isset($this->columns["gumPayoffID"]["default"])) {
                return $this->columns["gumPayoffID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["gumPayoffID"]) || $this->instance["gumPayoffID"] != func_get_args(0)) {
                if (!isset($this->columns["gumPayoffID"]["ignore_updates"]) || $this->columns["gumPayoffID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumPayoffID"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

