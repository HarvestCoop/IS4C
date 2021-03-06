<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.

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
  @class TermStateNotifier
  Display status of CC terminal
  on the cashier's screen
*/
class TermStateNotifier extends Notifier 
{
    /**
      Display the notification
      @return [string] html
    */
    public function draw()
    {
        global $CORE_LOCAL;

        if ($CORE_LOCAL->get('PaycardsCashierFacing') == '1') {
            return '';
        }

        // style box to look like a little screen
        $ret = '<div style="background:#ccc;border:solid 1px black;padding:7px;text-align:center;font-size:120%;">';
        $rdy = '<div style="background:#0c0;border:solid 1px black;padding:7px;text-align:center;font-size:120%;">';
        switch($CORE_LOCAL->get('ccTermState')) {
            case 'swipe':
                return $ret.'Slide<br />Card</div>';
                break;
            case 'ready':
                return $rdy.'Ready</div>';
                break;
            case 'pin':
                return $ret.'Enter<br />PIN</div>';
                break;
            case 'type':
                return $ret.'Card<br />Type</div>';
                break;
            case 'cashback':
                return $ret.'Cash<br />Back</div>';
                break;
        }

        return '';
    }
}

