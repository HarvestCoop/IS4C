<?php

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

$my_keys = array(
	new quickkey("Cash","CA"),
	new quickkey("Check","CK"),
	new quickkey("Credit\nCard","CC"),
	new quickkey("EBT FS","EF"),
	new quickkey("EBT Cash","EF"),
	new quickkey("Store\nCharge","MI"),
	new quickkey("Gift Card","GD"),
	new quickkey("Coupon","CP"),
	new quickkey("Quarterly\nCoupon","MA"),
	new quickkey("InStore\nCoupon","IC"),
	new quickkey("Gift\nCertificate","TC"),
	new quickkey("Travelers\nCheck","TV")
);

?>

