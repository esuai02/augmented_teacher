<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions used by the quiz module.
 *
 * This contains functions that are called from within the quiz module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-indpendent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package    mod_quiz
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
include_once("/home/moodle/public_html/moodle/config.php"); 
global $CFG, $DB, $USER, $SITE;
  
$eventdata->courseid          = 27;
$eventdata = new \core\message\message(); 

$eventdata->component         = 'mod_quiz';


$eventdata->name              ='confirmation';  
$eventdata->notification      = 1;

$eventdata->userfrom          =$userfrom; 
$eventdata->userto            =$userto;  // 1. teacherid
$eventdata->subject           = 'subject';
$eventdata->fullmessage       =$notificationtext;
$eventdata->fullmessageformat = FORMAT_PLAIN;
$eventdata->fullmessagehtml   = '';
if($cjnevent==NULL)$cjnevent='cjn';
$smallmessage=$cjnevent;
$eventdata->smallmessage      =$smallmessage;
$eventdata->contexturl        = 'confirm2';
$eventdata->contexturlname    ='confirm3';

message_send($eventdata);

if($indic->assist1!=NULL)
	{
	$eventdata->userto =$indic->assist1; // 2. 보조1
	message_send($eventdata);
	}
if($indic->assist2!=NULL)
	{
	$eventdata->userto =$indic->assist2; // 3. 보조2
	message_send($eventdata);
	}
if($indic->assist3!=NULL)
	{
	$eventdata->userto =$indic->assist3; // 4. 보조3
	message_send($eventdata);
	}
 
if( ($indic->mngrpick==1 || $eventdata->component==='mod_quiz') && $userto !=$indic->managerid )  // 5. 메니저 ( 동작이해 안됨 )
	{
	$eventdata->userto =$indic->managerid;
	message_send($eventdata);
	}
 
 
?>