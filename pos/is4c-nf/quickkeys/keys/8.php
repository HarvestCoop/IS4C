<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include_once($CORE_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("Milk\nReturn","9999904"),
	new quickkey("Dahl's\nWhole","1034"),
	new quickkey("Dahl's\n2%","1033"),
	new quickkey("Dahl's\n1%","1032"),
	new quickkey("Dahl's\nSkim","1031")
);

?>
