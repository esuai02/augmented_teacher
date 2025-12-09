<?php
// Moodle 설정 파일 포함
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// 로그인 확인
require_login();

// JSON 응답 헤더
header('Content-Type: application/json');

// 교사 권한 확인
$context = context_system::instance();
require_capability('moodle/course:viewparticipants', $context);

$action = required_param('action', PARAM_TEXT);
$teacherid = $USER->id;

switch ($action) {
    case 'get_students':
        $students = get_teacher_students($teacherid);
        echo json_encode(['students' => $students]);
        break;
        
    case 'get_alerts':
        $alerts = get_realtime_alerts($teacherid);
        echo json_encode(['alerts' => $alerts]);
        break;
        
    case 'update_student':
        $data = json_decode(file_get_contents('php://input'), true);
        update_student_hours($data);
        echo json_encode(['success' => true]);
        break;
        
    case 'process_attendance':
        $data = json_decode(file_get_contents('php://input'), true);
        process_attendance_action($data);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * 교사가 담당하는 학생 목록 가져오기
 */
function get_teacher_students($teacherid) {
    global $DB;
    
    $students = $DB->get_records_sql("
        SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2,
               c.fullname as course_name, c.id as courseid
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ?
        WHERE ra.roleid IN (3,4,5)
        ORDER BY u.lastname, u.firstname
    ", array($teacherid));
    
    $result = array();
    foreach ($students as $student) {
        // 학생의 출결 통계 계산
        $attendance_stats = calculate_attendance_stats($student->id);
        
        // 현재 상태 확인
        $current_status = check_current_status($student->id);
        
        $result[] = array(
            'id' => $student->id,
            'name' => $student->firstname . ' ' . $student->lastname,
            'email' => $student->email,
            'phone' => $student->phone1,
            'parent_phone' => $student->phone2,
            'course' => $student->course_name,
            'scheduled_makeup_hours' => $attendance_stats['scheduled_makeup'],
            'required_makeup_hours' => $attendance_stats['required_makeup'],
            'total_missed_hours' => $attendance_stats['total_missed'],
            'status' => $current_status
        );
    }
    
    return $result;
}

/**
 * 학생의 출결 통계 계산
 */
function calculate_attendance_stats($studentid) {
    global $DB;
    
    // 사용자 정의 필드나 별도 테이블에서 보강 시간 정보 가져오기
    // 여기서는 예시로 기본값 설정
    $stats = array(
        'scheduled_makeup' => 0,
        'required_makeup' => 0,
        'total_missed' => 0
    );
    
    // 실제 구현시 mdl_abessi_attendance 같은 테이블에서 데이터 조회
    $attendance_record = $DB->get_record_sql("
        SELECT * FROM {user_info_data} uid
        JOIN {user_info_field} uif ON uif.id = uid.fieldid
        WHERE uid.userid = ? AND uif.shortname = 'attendance_stats'
    ", array($studentid));
    
    if ($attendance_record) {
        $data = json_decode($attendance_record->data, true);
        $stats = array_merge($stats, $data);
    }
    
    return $stats;
}

/**
 * 학생의 현재 상태 확인
 */
function check_current_status($studentid) {
    global $DB;
    
    // 최근 5분 이내 활동 확인
    $recent_activity = $DB->get_record_sql("
        SELECT MAX(timecreated) as last_activity
        FROM {abessi_missionlog}
        WHERE userid = ? AND timecreated > ?
    ", array($studentid, time() - 300));
    
    if ($recent_activity && $recent_activity->last_activity) {
        // 현재 수업 중인지 확인
        $schedule = $DB->get_record_sql("
            SELECT * FROM {abessi_schedule}
            WHERE userid = ? AND pinned = 1
            ORDER BY id DESC LIMIT 1
        ", array($studentid));
        
        if ($schedule) {
            $schedule_data = json_decode($schedule->schedule_data, true);
            $today = strtolower(date('l'));
            
            if (isset($schedule_data[$today]) && $schedule_data[$today]['has_class']) {
                $current_time = date('H:i');
                $start_time = $schedule_data[$today]['start_time'];
                $end_time = $schedule_data[$today]['end_time'];
                
                if ($current_time >= $start_time && $current_time <= $end_time) {
                    return '수업 중';
                } else {
                    return '예정외 접속';
                }
            }
        }
        
        return '온라인';
    }
    
    // 보강 필요 여부 확인
    $stats = calculate_attendance_stats($studentid);
    if ($stats['required_makeup'] > 0) {
        return '보강 필요';
    }
    
    return '정상';
}

/**
 * 실시간 알림 가져오기
 */
function get_realtime_alerts($teacherid) {
    global $DB;
    
    $alerts = array();
    $students = get_teacher_students($teacherid);
    
    foreach ($students as $student) {
        // 정규수업 시간 체크
        $schedule = $DB->get_record_sql("
            SELECT * FROM {abessi_schedule}
            WHERE userid = ? AND pinned = 1
            ORDER BY id DESC LIMIT 1
        ", array($student['id']));
        
        if ($schedule) {
            $schedule_data = json_decode($schedule->schedule_data, true);
            $today = strtolower(date('l'));
            
            if (isset($schedule_data[$today]) && $schedule_data[$today]['has_class']) {
                $class_start = strtotime(date('Y-m-d') . ' ' . $schedule_data[$today]['start_time']);
                $current_time = time();
                
                // 수업 시작 15분 후 미접속시 결석 알림
                if ($current_time > $class_start + 900 && $current_time < $class_start + 7200) {
                    $attendance = $DB->get_record_sql("
                        SELECT MIN(timecreated) as first_access
                        FROM {abessi_missionlog}
                        WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) = CURDATE()
                    ", array($student['id']));
                    
                    if (!$attendance || !$attendance->first_access || $attendance->first_access > $class_start + 900) {
                        $alerts[] = array(
                            'id' => uniqid(),
                            'type' => 'absence',
                            'priority' => 'urgent',
                            'student_id' => $student['id'],
                            'student_name' => $student['name'],
                            'message' => '정규수업 결석 (15분 경과)',
                            'timestamp' => $class_start + 900,
                            'class_info' => array(
                                'subject' => $student['course'],
                                'scheduled_time' => $schedule_data[$today]['start_time'] . '-' . $schedule_data[$today]['end_time']
                            )
                        );
                    }
                }
                
                // 수업 연장 감지
                if ($current_time > strtotime(date('Y-m-d') . ' ' . $schedule_data[$today]['end_time'])) {
                    $recent_activity = $DB->get_record_sql("
                        SELECT MAX(timecreated) as last_activity
                        FROM {abessi_missionlog}
                        WHERE userid = ? AND timecreated > ?
                    ", array($student['id'], time() - 300));
                    
                    if ($recent_activity && $recent_activity->last_activity) {
                        $alerts[] = array(
                            'id' => uniqid(),
                            'type' => 'overtime',
                            'priority' => 'normal',
                            'student_id' => $student['id'],
                            'student_name' => $student['name'],
                            'message' => '정규수업 연장 중',
                            'timestamp' => time()
                        );
                    }
                }
            } else {
                // 비정규일 접속 감지
                $recent_activity = $DB->get_record_sql("
                    SELECT MIN(timecreated) as session_start
                    FROM {abessi_missionlog}
                    WHERE userid = ? AND timecreated > ?
                ", array($student['id'], time() - 300));
                
                if ($recent_activity && $recent_activity->session_start) {
                    $alerts[] = array(
                        'id' => uniqid(),
                        'type' => 'unscheduled_access',
                        'priority' => 'normal',
                        'student_id' => $student['id'],
                        'student_name' => $student['name'],
                        'message' => '예정외 접속 감지',
                        'timestamp' => $recent_activity->session_start
                    );
                }
            }
        }
    }
    
    return $alerts;
}

/**
 * 학생 정보 업데이트
 */
function update_student_hours($data) {
    global $DB;
    
    $studentid = $data['student_id'];
    $stats = array(
        'scheduled_makeup' => floatval($data['scheduled_makeup_hours']),
        'required_makeup' => floatval($data['required_makeup_hours']),
        'total_missed' => floatval($data['total_missed_hours'])
    );
    
    // 사용자 정의 필드 업데이트 또는 별도 테이블에 저장
    // 여기서는 예시로 user_info_data 사용
    $field = $DB->get_record('user_info_field', array('shortname' => 'attendance_stats'));
    
    if ($field) {
        $record = $DB->get_record('user_info_data', array(
            'userid' => $studentid,
            'fieldid' => $field->id
        ));
        
        if ($record) {
            $record->data = json_encode($stats);
            $DB->update_record('user_info_data', $record);
        } else {
            $record = new stdClass();
            $record->userid = $studentid;
            $record->fieldid = $field->id;
            $record->data = json_encode($stats);
            $DB->insert_record('user_info_data', $record);
        }
    }
    
    // 로그 기록
    $log = new stdClass();
    $log->userid = $studentid;
    $log->teacherid = $USER->id;
    $log->action = 'update_hours';
    $log->data = json_encode($stats);
    $log->timecreated = time();
    $DB->insert_record('abessi_attendance_log', $log);
}

/**
 * 출석 처리 액션
 */
function process_attendance_action($data) {
    global $DB, $USER;
    
    $modal_type = $data['modal_type'];
    $student_id = $data['student_id'];
    $modal_data = $data['data'];
    
    switch ($modal_type) {
        case 'absence':
            // 결석 처리
            $record = new stdClass();
            $record->userid = $student_id;
            $record->teacherid = $USER->id;
            $record->type = 'absence';
            $record->reason = $modal_data['reason'];
            $record->hours = floatval($modal_data['makeup_hours']);
            $record->date = date('Y-m-d');
            $record->timecreated = time();
            $DB->insert_record('abessi_attendance_record', $record);
            
            // 보강 필요 시간 업데이트
            if ($modal_data['makeup_hours'] > 0) {
                $stats = calculate_attendance_stats($student_id);
                $stats['required_makeup'] += floatval($modal_data['makeup_hours']);
                $stats['total_missed'] += floatval($modal_data['makeup_hours']);
                update_student_stats($student_id, $stats);
            }
            break;
            
        case 'makeup':
            // 보강 완료 처리
            $record = new stdClass();
            $record->userid = $student_id;
            $record->teacherid = $USER->id;
            $record->type = 'makeup_complete';
            $record->hours = floatval($modal_data['completed_hours']);
            $record->date = date('Y-m-d');
            $record->timecreated = time();
            $DB->insert_record('abessi_attendance_record', $record);
            
            // 보강 시간 차감
            $stats = calculate_attendance_stats($student_id);
            $stats['scheduled_makeup'] = max(0, $stats['scheduled_makeup'] - floatval($modal_data['completed_hours']));
            update_student_stats($student_id, $stats);
            break;
            
        case 'add_absence':
            // 휴강 추가
            $record = new stdClass();
            $record->userid = $student_id;
            $record->teacherid = $USER->id;
            $record->type = 'add_absence';
            $record->reason = $modal_data['reason'];
            $record->hours = floatval($modal_data['absence_hours']);
            $record->date = date('Y-m-d');
            $record->timecreated = time();
            $DB->insert_record('abessi_attendance_record', $record);
            
            // 보강 필요 시간 추가
            $stats = calculate_attendance_stats($student_id);
            $stats['required_makeup'] += floatval($modal_data['absence_hours']);
            $stats['total_missed'] += floatval($modal_data['absence_hours']);
            update_student_stats($student_id, $stats);
            break;
    }
    
    // 알림 제거
    if (isset($data['alert_id'])) {
        // 처리된 알림 기록
        $alert_log = new stdClass();
        $alert_log->alertid = $data['alert_id'];
        $alert_log->teacherid = $USER->id;
        $alert_log->action = $modal_type;
        $alert_log->timecreated = time();
        $DB->insert_record('abessi_alert_log', $alert_log);
    }
}

/**
 * 학생 통계 업데이트
 */
function update_student_stats($studentid, $stats) {
    global $DB;
    
    $field = $DB->get_record('user_info_field', array('shortname' => 'attendance_stats'));
    
    if ($field) {
        $record = $DB->get_record('user_info_data', array(
            'userid' => $studentid,
            'fieldid' => $field->id
        ));
        
        if ($record) {
            $record->data = json_encode($stats);
            $DB->update_record('user_info_data', $record);
        } else {
            $record = new stdClass();
            $record->userid = $studentid;
            $record->fieldid = $field->id;
            $record->data = json_encode($stats);
            $DB->insert_record('user_info_data', $record);
        }
    }
}