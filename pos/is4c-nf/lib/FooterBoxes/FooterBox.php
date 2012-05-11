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
  @class FooterBox
  Base class for displaying footer

  The footer contain five boxes. Stores
  can select a different module for each
  box.
*/
class FooterBox {

	/**
	  CSS here will be applied (in-line) to the
	  header content. If you define a different
	  width alignment might go haywire.
	*/
	var $header_css;
	/**
	  CSS here will be applied (in-line) to the
	  display content. If you define a different
	  width alignment might go haywire.
	*/
	var $display_css;

	/**
	  Define the header for this box
	  @return An HTML string
	*/
	function header_content(){
		return "";
	}

	/**
	  Define the content for this box
	  @return An HTML string
	*/
	function display_content(){
		return "";
	}
}

?>
