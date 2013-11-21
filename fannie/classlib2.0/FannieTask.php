<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class FannieTask

  Base class for scheduled tasks
*/
class FannieTask 
{
    public $description = 'Information about the task';    

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => 1,
        'month' => 1,
        'weekday' => '*',
    );

    /**
      Implement task functionality here
    */
    public function run()
    {

    }

    /**
      Format message with date information
      and task's class name
      @param $str message string
      @return formatted string
    */
    public function cronMsg($str)
    {
        $info = new ReflectionClass($this);
        return date('r').': '.$info->getName().': '.$str."\n";
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    if ($argc != 2) {
        echo "Usage: php FannieTask.php <Task Class Name>\n";    
        exit;
    }

    include(dirname(__FILE__).'/../config.php');
    include(dirname(__FILE__).'/FannieAPI.php');

    $class = $argv[1];
    if (!class_exists($class)) {
        echo "Error: class '$class' does not exist\n";
        exit;
    }

    $obj = new $class();
    if (!is_a($obj, 'FannieTask')) {
        echo "Error: invalid class. Must be subclass of FannieTask\n";
        exit;
    }

    $obj->run();
}
