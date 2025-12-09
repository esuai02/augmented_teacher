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
 * StudentQuiz qbehaviour locallib
 *
 * @package    qbehaviour_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../../config.php');

/**
 * Check permission if is no student
 *
 * @return boolean the current user is not a student
 */
 
function qbehaviour_studentquiz_check_created_permission($commentid) {
    global $USER, $DB;

    // Check if user is admin.
//  return true;   //// TSLEE SET EVERYONE CAN VIEW THE NOTES
  $admins = get_admins();
    foreach ($admins as $admin) {
        if ($USER->id == $admin->id) {
            return true;
        }
    } 
 
////tslee
/*
 if (has_capability('moodle/legacy:editingteacher', $context, $USER->id, false)) {
 return true;
} */
 
    

// Check if user is comment creator.
if ($DB->get_field('studentquiz_comment', 'userid', array('id' => $commentid)) == $USER->id)return true;
return false;
}

/**
 * Generate some HTML to render comments
 *
 * @param  int $questionid Question id
 * @return string HTML fragment
 */
function qbehaviour_studentquiz_comment_renderer($questionid) 
	{
	global $USER, $DB;
	$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
	$role=$userrole->role;
	if($role!=='student')$remove='삭제';

    	$modname = 'qbehaviour_studentquiz';

    	$comments = $DB->get_records(
        	'studentquiz_comment', array('questionid' => $questionid),
        	'id DESC'
    	);

    if (empty($comments)) {
        return html_writer::div(get_string('no_comments', $modname));
    }
$html = '';
$index = 0;
foreach ($comments as $comment)
	{
          	$hide = '';
       	if ($index > 1) 
		{
            		$hide = 'hidden';
        		}
        	$date = date('Y/m/d', $comment->created); 
        	$user = $DB->get_record('user', array('id' => $comment->userid));
        	$authorid = ($user !== false ? $user->id : '');
        	$username = ($user !== false ? $user->username : '');
        	$firstname = ($user !== false ? $user-> firstname : '');
        	$lastname = ($user !== false ? $user-> lastname : '');
 
	if(strpos($comment->comment, 'moreleap')!=false)$replay='<a href="'.str_replace("board.php","replay.php",$comment->comment).'&speed=9" target="_blank">공신모드</a>';
	else  $replay='';
	if ($authorid == $USER->id && strpos($comment->comment, 'hintimages')==false && strpos($comment->comment, '평가준비')!=false )
         		{ 
        		$html .= html_writer::div((qbehaviour_studentquiz_check_created_permission($comment->id) ? html_writer::span(''.$remove.'', 'remove_action',
                	array(
                	'data-id' => $comment->id, 'data-question_id' => $comment->questionid)) : '')
                	. html_writer::tag('p','<table width="100%"><tr><th align="left"  width="10%">'.$firstname.$lastname.'</th> '.$comment->comment.'<th align="left"  width="15%">'.$date.'</th><th width="8%"><a href="https://mathking.kr/moodle/message/index.php?id='.$authorid.'" target="_blank">메세지</a></th> 
		<th  align="left" width="8%"><a href="https://mathking.kr/moodle/mod/stampcoll/view.php?id=75589&view=single&userid='.$authorid.'" target="_blank">'
	 	.get_string('givecoins', 'qbehaviour_studentquiz').'</a></th><th align="left"  width="10%"></th></tr></table>'),     ////: ") 을 이곳으로 옮기면 학생은 자신의 풀이만 볼수 있음. 이경우 관리자만 모든 풀이를 봄. 선생님도 못봄. 이부분을 아이디 번호로 일시적으로 해결가능.. 원래 삭제버튼까지 있던 것을 이곳으로 이동
                	$hide);
       		++$index;
       		} 
	elseif($authorid == $USER->id && strpos($comment->comment, 'hintimages')==false)
		{
        		$html .= html_writer::div(
            		(qbehaviour_studentquiz_check_created_permission($comment->id) ? html_writer::span('remove', 'remove_action',
                	array(
                	'data-id' => $comment->id, 'data-question_id' => $comment->questionid)) : '')
                	. html_writer::tag('p','<table width="100%"><tr><th align="left"  width="10%">'.$firstname.$lastname.'</th> '.$comment->comment.'<th align="left"  width="15%">'.$date.'</th><th width="8%"><a href="https://mathking.kr/moodle/message/index.php?id='.$authorid.'" target="_blank">메세지</a></th> 
		<th  align="left" width="8%"><a href="https://mathking.kr/moodle/mod/stampcoll/view.php?id=75589&view=single&userid='.$authorid.'" target="_blank">'
	 	.get_string('givecoins', 'qbehaviour_studentquiz').'</a></th><th align="left"  width="10%"></th></tr></table>'.$replay),     ////: ") 을 이곳으로 옮기면 학생은 자신의 풀이만 볼수 있음. 이경우 관리자만 모든 풀이를 봄. 선생님도 못봄. 이부분을 아이디 번호로 일시적으로 해결가능.. 원래 삭제버튼까지 있던 것을 이곳으로 이동
                	$hide);    		
		} 
	}      
if($role!=='student')
{
$index = 0;
foreach ($comments as $comment) 
	{
        	$hide = '';
        	if ($index > 1) 
		{
            		$hide = 'hidden';
        		}
	$date = date('Y...m/d H:i', $comment->created); 
        	$user = $DB->get_record('user', array('id' => $comment->userid));
        	$authorid = ($user !== false ? $user->id : '');
       	$username = ($user !== false ? $user->username : '');
        	$firstname = ($user !== false ? $user-> firstname : '');
        	$lastname = ($user !== false ? $user-> lastname : '');
	
	if(strpos($comment->comment, 'moreleap')!=false)$replay='<a href="'.str_replace("board.php","replay.php",$comment->comment).'&speed=9" target="_blank">공신모드</a>';
	else  $replay='';
	if ( $authorid != $USER->id && strpos($comment->comment, 'hintimages')==false && strpos($comment->comment, '평가준비')!=false )
         		{ 
        		$html .= html_writer::div((qbehaviour_studentquiz_check_created_permission($comment->id) ? html_writer::span('remove', 'remove_action',
                	array(
                	'data-id' => $comment->id, 'data-question_id' => $comment->questionid)) : '')
                	. html_writer::tag('p','<table width="100%"><tr><th align="left"  width="10%">'.$firstname.$lastname.'</th> '.$comment->comment.'<th align="left"  width="15%">'.$date.'</th><th width="8%"><a href="https://mathking.kr/moodle/message/index.php?id='.$authorid.'" target="_blank">메세지</a></th> 
		<th  align="left" width="8%"><a href="https://mathking.kr/moodle/mod/stampcoll/view.php?id=75589&view=single&userid='.$authorid.'" target="_blank">'
	 	.get_string('givecoins', 'qbehaviour_studentquiz').'</a></th><th align="left"  width="10%"></th></tr></table>'),     ////: ") 을 이곳으로 옮기면 학생은 자신의 풀이만 볼수 있음. 이경우 관리자만 모든 풀이를 봄. 선생님도 못봄. 이부분을 아이디 번호로 일시적으로 해결가능.. 원래 삭제버튼까지 있던 것을 이곳으로 이동
                	$hide);
       		++$index;
       		} 
	elseif($authorid != $USER->id && strpos($comment->comment, 'hintimages')==false)
		{
        		$html .= html_writer::div(
            		(qbehaviour_studentquiz_check_created_permission($comment->id) ? html_writer::span('remove', 'remove_action',
                	array(
                	'data-id' => $comment->id, 'data-question_id' => $comment->questionid)) : '')
                	. html_writer::tag('p','<table width="100%"><tr><th align="left"  width="10%">'.$firstname.$lastname.'</th> '.$comment->comment.'<th align="left"  width="15%">'.$date.'</th><th width="8%"><a href="https://mathking.kr/moodle/message/index.php?id='.$authorid.'" target="_blank">메세지</a></th> 
		<th  align="left" width="8%"><a href="https://mathking.kr/moodle/mod/stampcoll/view.php?id=75589&view=single&userid='.$authorid.'" target="_blank">'
	 	.get_string('givecoins', 'qbehaviour_studentquiz').'</a></th><th align="left"  width="10%"></th></tr></table>'.$replay),     ////: ") 을 이곳으로 옮기면 학생은 자신의 풀이만 볼수 있음. 이경우 관리자만 모든 풀이를 봄. 선생님도 못봄. 이부분을 아이디 번호로 일시적으로 해결가능.. 원래 삭제버튼까지 있던 것을 이곳으로 이동
                	$hide);  
  	++$index;  		
		} 

	} 
if (count($comments) > 2) 
	{
        	$html .= html_writer::div(
            	html_writer::tag('button', get_string('show_more', $modname), array('type' => 'button', 'class' => 'show_more'))
            	. html_writer::tag('button', get_string('show_less', $modname), array('type' => 'button', 'class' => 'show_less hidden')), 'button_controls');
   	}
}
return $html;    
}