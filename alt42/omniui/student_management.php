<?php
// 학생 관리 시스템 (읽기 전용)
// - Moodle DB 연결 사용 (/home/moodle/public_html/moodle/config.php)
// - 컬럼: 이름, 학교, 학년, 수업시간, 휴강, 보강, 남은보강
// - 링크: schedule.php, scaffolding.php, attendance_teacher.php

// Moodle 환경 포함 및 로그인 요구
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

date_default_timezone_set('Asia/Seoul');

// 교사 필터(담당 학생만 보기) 강제
$FILTER_BY_TEACHER_SYMBOL = true;

// 담당 교사 선택 파라미터 (teacherid)
// 기본값: 현재 사용자가 교사이면 본인, 아니면 0(전체)
$userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = 22", array($USER->id));
$currentIsTeacher = $userrole && $userrole->role !== 'student';
// URL 파라미터로 교사 userid 지정 시 해당 교사 기준으로 보기(읽기 전용 모드)
$teacherid = isset($_GET['userid']) ? intval($_GET['userid']) : intval($USER->id);
// 다른 교사 userid로 보는 경우: 편집 불가(읽기 전용)
$readonlyMode = ($teacherid !== intval($USER->id));

// 교사 목록 로드 (student가 아닌 계정)
$teachers = array();
try {
    $teachers = $DB->get_records_sql(
        "SELECT u.id, u.firstname, u.lastname, uid.data AS role
         FROM {user} u
         LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = 22
         WHERE u.deleted = 0 AND u.suspended = 0 AND (uid.data IS NULL OR uid.data <> 'student')
         ORDER BY u.firstname ASC, u.lastname ASC LIMIT 400"
    );
} catch (Exception $e) { $teachers = array(); }

// 심볼 오버라이드 (특정 교사 강제 심볼)
$SYMBOL_OVERRIDES = array(
    13   => '❐',
    2    => '✨',
    1719 => '◎',
    1896 => '✨',
    255  => 'ψ',
    1500 => '◈',
    1852 => '☘', // 기존 유지
    5    => '◈',
    1656 => '★',
    827  => '✨',
    943  => '⏳',
    1561 => '⚡'
);

// 심볼 필드 검색
function find_symbol_field_ids($DB) {
    $ids = array(64); // 기본 후보
    try {
        $rows = $DB->get_records_sql("SELECT id, shortname FROM {user_info_field}");
        if ($rows) {
            foreach ($rows as $r) {
                $sn = mb_strtolower((string)$r->shortname);
                if (in_array($sn, array('symbol','tsymbol','teacher_symbol','심볼','교사심볼'), true)) {
                    $ids[] = intval($r->id);
                }
            }
        }
    } catch (Exception $e) {}
    return array_values(array_unique($ids));
}

// 심볼 표준화 (텍스트/변형 → 대표 이모지)
function normalize_symbol($val) {
    $v = trim((string)$val);
    if ($v === '') return '';
    if (mb_stripos($v, '클로버') !== false || mb_stripos($v, 'clover') !== false) return '☘';
    // 표준화: 클로버는 ☘ 로 표시, 매칭은 동의어 세트로 처리
    $map = array(
        '☘️'=>'☘',
        '🍀'=>'☘',
        '♣'=>'♣️'
    );
    if (isset($map[$v])) return $map[$v];
    return $v;
}

// 선택된 교사 심볼 계산 (DB 필드 우선, 그 다음 이름)
$tsymbol = '';
if ($teacherid > 0) {
    // 0) 오버라이드 우선
    if (isset($SYMBOL_OVERRIDES[$teacherid]) && $SYMBOL_OVERRIDES[$teacherid] !== '') {
        $tsymbol = $SYMBOL_OVERRIDES[$teacherid];
    }
    // 1) DB 커스텀 필드 검색
    if ($tsymbol === '') {
        $fieldIds = find_symbol_field_ids($DB);
        foreach ($fieldIds as $fid) {
            $rec = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ?", array($teacherid, $fid));
            if ($rec && !empty($rec->data)) { $tsymbol = normalize_symbol($rec->data); if ($tsymbol !== '') break; }
        }
    }
    // 2) 이름(성/이름)에서 탐색
    if ($tsymbol === '') {
        $trow = $DB->get_record_sql("SELECT firstname, lastname FROM {user} WHERE id = ?", array($teacherid));
        if ($trow) {
            $fn = (string)$trow->firstname; $ln = (string)$trow->lastname;
            foreach (array('☘','☘️','🍀','♣','♣️','🌟','⭐','✨','🎯','🔥','💫','🌈','🎨','🎪','🎭','♦️') as $sym) {
                if (strpos($fn, $sym) !== false || strpos($ln, $sym) !== false) { $tsymbol = $sym; break; }
            }
            if ($tsymbol === '') {
                if (mb_stripos($fn, '클로버') !== false || mb_stripos($fn, 'clover') !== false || mb_stripos($ln, '클로버') !== false || mb_stripos($ln, 'clover') !== false) {
                    $tsymbol = '☘';
                }
            }
        }
    }
}

// 심볼 동의어(변형 포함) 빌더
function build_symbol_synonyms($sym) {
    $set = array($sym);
    // 클로버 동의어 및 이모지 변형 포함
    $cloverSet = array('☘','☘️','🍀','♣','♣️','클로버','clover');
    if (in_array($sym, $cloverSet, true)) return $cloverSet;
    return $set;
}

// 임의 폴백 제거: 심볼 없으면 노출 차단 플래그로 처리

// 심볼이 끝내 비어있으면 학생 표시를 차단 (보안상 전체 노출 방지)
$DENY_ALL_IF_NO_SYMBOL = empty($tsymbol);

// AJAX: 보안 검증 - 선택 학생이 이 교사 담당인지 확인
function is_teacher_of_student($DB, $studentid, $tsymbol) {
    if (empty($tsymbol)) return false;
    // 심볼 동의어 생성 (클로버 변형 포괄)
    $syns = build_symbol_synonyms($tsymbol);
    // 동적 WHERE 생성
    $likeConds = array();
    $params = array($studentid);
    foreach ($syns as $s) {
        $likeConds[] = "(uid64.data IS NOT NULL AND uid64.data LIKE ?)";
        $params[] = '%'.$s.'%';
    }
    foreach ($syns as $s) { $likeConds[] = "(u.firstname LIKE ?)"; $params[] = '%'.$s.'%'; }
    foreach ($syns as $s) { $likeConds[] = "(u.lastname LIKE ?)";  $params[] = '%'.$s.'%'; }
    $where = implode(' OR ', $likeConds);
    // 심볼 필드 ID 동적 IN 구성
    $symbolFieldIds = find_symbol_field_ids($DB);
    $inPlaceholders = implode(',', array_fill(0, count($symbolFieldIds), '?'));
    // uidSym은 단순히 JOIN 최적화를 위한 별칭(WHERE는 $where 활용)
    $sql = "SELECT u.id FROM {user} u
            LEFT JOIN {user_info_data} uidSym ON uidSym.userid = u.id AND uidSym.fieldid IN ($inPlaceholders)
            WHERE u.id = ? AND u.deleted = 0 AND u.suspended = 0 AND ( $where )";
    $owned = $DB->get_record_sql($sql, array_merge($symbolFieldIds, $params));
    return (bool)$owned;
}

// 파서: 수업시간 문자열 → 요일별 시작/시간
function parse_schedule_text($text) {
    $result = array(); // day(1~7) => [starttime => HH:MM, duration => float]
    if (!is_string($text) || trim($text) === '') return $result;
    $items = preg_split('/\s*,\s*/u', $text);
    foreach ($items as $item) {
        // 예: "월 19:00 2h" or "수 18:30 1.5h"
        if (preg_match('/([월화수목금토일])\s+(\d{1,2}:\d{2})\s+(\d+(?:\.\d+)?)h/u', trim($item), $m)) {
            $dowKo = $m[1];
            $time = $m[2];
            $hours = floatval($m[3]);
            $map = array('월'=>1,'화'=>2,'수'=>3,'목'=>4,'금'=>5,'토'=>6,'일'=>7);
            if (isset($map[$dowKo])) {
                $result[$map[$dowKo]] = array('starttime' => $time, 'duration' => $hours);
            }
        }
    }
    return $result;
}

// 파서: 휴강/보강 문자열 → 배열(월/일 시간 h)
function parse_attendance_text($text) {
    $result = array();
    if (!is_string($text) || trim($text) === '') return $result;
    $items = preg_split('/\s*,\s*/u', $text);
    foreach ($items as $item) {
        // 예: "10/15 19:00 2h"
        if (preg_match('/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}:\d{2})\s+(\d+(?:\.\d+)?)h/u', trim($item), $m)) {
            $result[] = array(
                'month' => intval($m[1]),
                'day' => intval($m[2]),
                'time' => $m[3],
                'hours' => floatval($m[4])
            );
        }
    }
    return $result;
}

// AJAX 처리
if (isset($_REQUEST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (function_exists('require_sesskey')) {
        try { require_sesskey(); } catch (Exception $e) { echo json_encode(array('status'=>'error','message'=>'세션키 오류')); exit; }
    }
    if (!$currentIsTeacher || $teacherid <= 0 || empty($tsymbol)) {
        echo json_encode(array('status'=>'error','message'=>'권한 없음'));
        exit;
    }
    $action = $_REQUEST['ajax'];

    if ($action === 'update_schedule' && (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], array('POST','GET'), true))) {
        $studentid = isset($_REQUEST['userid']) ? intval($_REQUEST['userid']) : 0;
        $scheduleText = isset($_REQUEST['schedule']) ? trim($_REQUEST['schedule']) : '';
        if ($studentid <= 0) { echo json_encode(array('status'=>'error','message'=>'잘못된 학생')); exit; }
        if (!is_teacher_of_student($DB, $studentid, $tsymbol)) { echo json_encode(array('status'=>'error','message'=>'권한 없음(담당 아님)')); exit; }

        $parsed = parse_schedule_text($scheduleText);
        try {
            // pinned=1 스케줄 가져오기 또는 생성
            $schedule = $DB->get_record_sql("SELECT * FROM {abessi_schedule} WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1", array($studentid));
            if (!$schedule) {
                $schedule = new stdClass();
                $schedule->userid = $studentid;
                $schedule->pinned = 1;
                for ($d=1;$d<=7;$d++){ $schedule->{'duration'.$d}=0; $schedule->{'starttime'.$d}=''; }
                $schedule->timecreated = time();
                $schedule->timemodified = time();
                $schedule->id = $DB->insert_record('abessi_schedule', $schedule);
                $schedule = $DB->get_record('abessi_schedule', array('id'=>$schedule->id));
            }
            // 전체 요일 초기화 후 채우기
            for ($d=1;$d<=7;$d++){
                $schedule->{'duration'.$d} = 0;
                $schedule->{'starttime'.$d} = '';
            }
            foreach ($parsed as $d => $v) {
                $schedule->{'duration'.$d} = $v['duration'];
                $schedule->{'starttime'.$d} = $v['starttime'];
            }
            $schedule->timemodified = time();
            $DB->update_record('abessi_schedule', $schedule);
            echo json_encode(array('status'=>'success'));
        } catch (Exception $e) {
            echo json_encode(array('status'=>'error','message'=>$e->getMessage()));
        }
        exit;
    }

    if ($action === 'update_attendance' && (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], array('POST','GET'), true))) {
        $studentid = isset($_REQUEST['userid']) ? intval($_REQUEST['userid']) : 0;
        $hasCanceled = array_key_exists('canceled', $_REQUEST);
        $hasMakeup = array_key_exists('makeup', $_REQUEST);
        $cancelText = $hasCanceled ? trim($_REQUEST['canceled']) : '';
        $makeText = $hasMakeup ? trim($_REQUEST['makeup']) : '';
        if ($studentid <= 0) { echo json_encode(array('status'=>'error','message'=>'잘못된 학생')); exit; }
        if (!is_teacher_of_student($DB, $studentid, $tsymbol)) { echo json_encode(array('status'=>'error','message'=>'권한 없음(담당 아님)')); exit; }
        $threeWeeksAgo = strtotime("-3 weeks");
        $year = intval(date('Y'));
        try {
            // 기존 3주 내 기록 중 전달된 이벤트만 제거
            $deleteEvents = array();
            if ($hasCanceled) { $deleteEvents[] = 'absence'; }
            if ($hasMakeup) { $deleteEvents[] = 'makeup'; }
            if (!empty($deleteEvents)) {
                $placeholders = implode(',', array_fill(0, count($deleteEvents), '?'));
                $paramsDel = array_merge(array($studentid, $threeWeeksAgo), $deleteEvents);
                $DB->execute("DELETE FROM {abessi_classtimemanagement} WHERE userid = ? AND hide = 0 AND due >= ? AND event IN ($placeholders)", $paramsDel);
            }
            // 새로 삽입
            $ins = function($event, $arr) use ($DB, $studentid, $year) {
                foreach ($arr as $it) {
                    $dueStr = sprintf('%04d-%02d-%02d %s:00', $year, $it['month'], $it['day'], $it['time']);
                    $record = new stdClass();
                    $record->userid = $studentid;
                    $record->event = $event;
                    $record->hide = 0;
                    $record->amount = $it['hours'];
                    $record->text = '';
                    $record->due = strtotime($dueStr);
                    $record->timecreated = time();
                    $record->status = 'done';
                    $record->role = 'teacher';
                    $DB->insert_record('abessi_classtimemanagement', $record);
                }
            };
            if ($hasCanceled) { $ins('absence', parse_attendance_text($cancelText)); }
            if ($hasMakeup) { $ins('makeup', parse_attendance_text($makeText)); }
            echo json_encode(array('status'=>'success'));
        } catch (Exception $e) {
            echo json_encode(array('status'=>'error','message'=>$e->getMessage()));
        }
        exit;
    }

    if ($action === 'update_examinfo' && (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], array('POST','GET'), true))) {
        $studentid = isset($_REQUEST['userid']) ? intval($_REQUEST['userid']) : 0;
        $examdate = isset($_REQUEST['examdate']) ? trim($_REQUEST['examdate']) : '';
        $examscope = isset($_REQUEST['examscope']) ? trim($_REQUEST['examscope']) : '';
        $remarks = isset($_REQUEST['remarks']) ? trim($_REQUEST['remarks']) : '';
        if ($studentid <= 0) { echo json_encode(array('status'=>'error','message'=>'잘못된 학생')); exit; }
        if (!is_teacher_of_student($DB, $studentid, $tsymbol)) { echo json_encode(array('status'=>'error','message'=>'권한 없음(담당 아님)')); exit; }
        try {
            // schedule 메모 필드 활용: memo6=시험날짜, memo7=시험범위, memo8=비고
            $schedule = $DB->get_record_sql("SELECT * FROM {abessi_schedule} WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1", array($studentid));
            if (!$schedule) {
                $schedule = new stdClass();
                $schedule->userid = $studentid;
                $schedule->pinned = 1;
                for ($d=1;$d<=7;$d++){ $schedule->{'duration'.$d}=0; $schedule->{'starttime'.$d}=''; }
                $schedule->memo6 = '';
                $schedule->memo7 = '';
                $schedule->memo8 = '';
                $schedule->timecreated = time();
                $schedule->timemodified = time();
                $schedule->id = $DB->insert_record('abessi_schedule', $schedule);
                $schedule = $DB->get_record('abessi_schedule', array('id'=>$schedule->id));
            }
            $schedule->memo6 = $examdate;
            $schedule->memo7 = $examscope;
            $schedule->memo8 = $remarks;
            $schedule->timemodified = time();
            $DB->update_record('abessi_schedule', $schedule);
            echo json_encode(array('status'=>'success'));
        } catch (Exception $e) {
            echo json_encode(array('status'=>'error','message'=>$e->getMessage()));
        }
        exit;
    }

    echo json_encode(array('status'=>'error','message'=>'잘못된 요청'));
    exit;
}

// 검색/정렬 파라미터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchNoSpace = $search !== '' ? preg_replace('/\s+/u','', $search) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name'; // name|school|grade|remaining
$dir = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc') ? 'DESC' : 'ASC';

// 최근 기간(휴강/보강 집계) - 3주
$threeWeeksAgo = strtotime("-3 weeks");

// 사용자 정의 필드ID 탐색 (동적): 학교/학년을 shortname/name기반으로 찾는다
$schoolFieldId = null; // 예: institute(88)
$gradeFieldId = null;  // 예: grade/학년
$birthFieldId = null;  // 예: 출생년도(89)

try {
    // 후보 shortname 배열 (우선순위 순)
    $schoolShortnames = array('institute', 'school', '학교');
    $gradeShortnames = array('grade', '학년', 'schoolyear', 'gradelevel');

    $fields = $DB->get_records_sql("SELECT id, shortname, name FROM {user_info_field}");
    if ($fields) {
        foreach ($fields as $f) {
            if ($schoolFieldId === null) {
                foreach ($schoolShortnames as $s) {
                    if (mb_strtolower($f->shortname) === mb_strtolower($s) || mb_strtolower($f->name) === mb_strtolower($s)) {
                        $schoolFieldId = intval($f->id);
                        break;
                    }
                }
            }
            if ($gradeFieldId === null) {
                foreach ($gradeShortnames as $g) {
                    if (mb_strtolower($f->shortname) === mb_strtolower($g) || mb_strtolower($f->name) === mb_strtolower($g)) {
                        $gradeFieldId = intval($f->id);
                        break;
                    }
                }
            }
            if ($birthFieldId === null) {
                if (in_array(mb_strtolower($f->shortname), array('birthyear','출생년도','birth','byear','yob'), true) ||
                    in_array(mb_strtolower($f->name), array('birthyear','출생년도','birth','byear','yob'), true)) {
                    $birthFieldId = intval($f->id);
                }
            }
            if ($schoolFieldId !== null && $gradeFieldId !== null) {
                break;
            }
        }
    }

    // moodledb.txt 힌트: institute(88) → 학교
    if ($schoolFieldId === null) {
        // 마지막 폴백으로 88 시도
        $schoolFieldId = 88;
    }
    if ($birthFieldId === null) {
        // moodledb.txt 힌트: 출생년도=89
        $birthFieldId = 89;
    }
} catch (Exception $e) {
    // 무시하고 폴백 사용
    if ($schoolFieldId === null) $schoolFieldId = 88;
    if ($birthFieldId === null) $birthFieldId = 89;
}

// 학생 기본 목록 (담당 교사의 심볼로 필터)
$params = array();
// 학생 역할 강제 필터를 제거하고(데이터 불일치 이슈 방지), 심볼 기반 필터로만 제한한다
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2
        FROM {user} u
        WHERE u.deleted = 0 AND u.suspended = 0";

if ($DENY_ALL_IF_NO_SYMBOL) {
    $sql .= " AND 1=0"; // 심볼 없으면 아무 학생도 노출하지 않음
}

if ($FILTER_BY_TEACHER_SYMBOL && $teacherid > 0 && !empty($tsymbol)) {
    // 학생 심볼: 동적으로 탐색한 필드들(IN)에서 동의어 매칭 + 이름(first/last) 매칭
    $syns = build_symbol_synonyms($tsymbol);
    $subConds = array();
    $subParams = array();
    foreach ($syns as $s) { $subConds[] = 'uids.data LIKE ?'; $subParams[] = '%'.$s.'%'; }
    $nameConds = array();
    foreach ($syns as $s) { $nameConds[] = 'u.firstname LIKE ?'; $subParams[] = '%'.$s.'%'; }
    foreach ($syns as $s) { $nameConds[] = 'u.lastname LIKE ?';  $subParams[] = '%'.$s.'%'; }

    // 심볼 필드 ID들을 IN으로 구성
    $symbolFieldIds = find_symbol_field_ids($DB);
    $inPlaceholders = implode(',', array_fill(0, count($symbolFieldIds), '?'));
    $sql .= ' AND ((EXISTS (SELECT 1 FROM {user_info_data} uids WHERE uids.userid = u.id AND uids.fieldid IN ('
          . $inPlaceholders . ') AND (' . implode(' OR ', $subConds) . '))) OR (' . implode(' OR ', $nameConds) . '))';
    $params = array_merge($params, $symbolFieldIds, $subParams);
}

if (!empty($search)) {
    $sql .= " AND ( 
        u.firstname LIKE ?
        OR u.lastname LIKE ?
        OR CONCAT(u.firstname,' ',u.lastname) LIKE ?
        OR REPLACE(CONCAT(u.firstname,' ',u.lastname),' ','') LIKE ?
        OR REPLACE(CONCAT(u.lastname,' ',u.firstname),' ','') LIKE ?
    )";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $searchNoSpace . '%';
    $params[] = '%' . $searchNoSpace . '%';
}

$sql .= " ORDER BY u.firstname ASC, u.lastname ASC LIMIT 300";

$students = $DB->get_records_sql($sql, $params);
$studentIds = array();
if ($students) {
    foreach ($students as $s) { $studentIds[] = intval($s->id); }
}

// 보조 함수: IN 절 placeholder
function build_in_placeholders($count) {
    return implode(',', array_fill(0, $count, '?'));
}

// 학교/학년 정보 로드 (user_info_data)
$schoolByUser = array();
$gradeByUser = array();
$birthByUser = array();
if (!empty($studentIds)) {
    $in = build_in_placeholders(count($studentIds));
    $paramsSchool = $studentIds;
    $paramsGrade = $studentIds;
    $paramsBirth = $studentIds;

    if ($schoolFieldId !== null) {
        $sqlSchool = "SELECT userid, data FROM {user_info_data} WHERE fieldid = ? AND userid IN ($in)";
        array_unshift($paramsSchool, $schoolFieldId);
        $rows = $DB->get_records_sql($sqlSchool, $paramsSchool);
        if ($rows) {
            foreach ($rows as $r) { $schoolByUser[intval($r->userid)] = $r->data; }
        }
    }

    if ($gradeFieldId !== null) {
        $sqlGrade = "SELECT userid, data FROM {user_info_data} WHERE fieldid = ? AND userid IN ($in)";
        array_unshift($paramsGrade, $gradeFieldId);
        $rows = $DB->get_records_sql($sqlGrade, $paramsGrade);
        if ($rows) {
            foreach ($rows as $r) { $gradeByUser[intval($r->userid)] = $r->data; }
        }
    }

    if ($birthFieldId !== null) {
        $sqlBirth = "SELECT userid, data FROM {user_info_data} WHERE fieldid = ? AND userid IN ($in)";
        array_unshift($paramsBirth, $birthFieldId);
        $rows = $DB->get_records_sql($sqlBirth, $paramsBirth);
        if ($rows) {
            foreach ($rows as $r) { $birthByUser[intval($r->userid)] = $r->data; }
        }
    }
}

// 시간표(수업시간) 로드: pinned=1 최근 1건
$scheduleByUser = array(); // userid => schedule record
if (!empty($studentIds)) {
    $in = build_in_placeholders(count($studentIds));
    $sqlSchedule = "SELECT * FROM {abessi_schedule} WHERE pinned = 1 AND userid IN ($in) ORDER BY id DESC";
    $rows = $DB->get_records_sql($sqlSchedule, $studentIds);
    if ($rows) {
        foreach ($rows as $r) {
            $uid = intval($r->userid);
            if (!isset($scheduleByUser[$uid])) {
                $scheduleByUser[$uid] = $r; // 첫 번째(최신 id DESC)만 채택
            }
        }
    }
}

// 휴강/보강 집계 및 최근 항목 문자열
$aggByUser = array(); // userid => ['absence_total','makeup_total','remaining']
$absTextByUser = array(); // 최근 휴강 텍스트 리스트
$makeTextByUser = array();
if (!empty($studentIds)) {
    $in = build_in_placeholders(count($studentIds));

    // 합계 (최근 3주)
    $paramsAgg = array_merge(array($threeWeeksAgo, $threeWeeksAgo), $studentIds);
    $sqlAgg = "SELECT userid,
                      SUM(CASE WHEN event='absence' THEN amount ELSE 0 END) AS absence_total,
                      SUM(CASE WHEN event='makeup' THEN amount ELSE 0 END) AS makeup_total
               FROM {abessi_classtimemanagement}
               WHERE hide = 0 AND due >= ? AND userid IN ($in)
               GROUP BY userid";
    $rows = $DB->get_records_sql($sqlAgg, $paramsAgg);
    if ($rows) {
        foreach ($rows as $r) {
            $uid = intval($r->userid);
            $absence = floatval($r->absence_total);
            $makeup = floatval($r->makeup_total);
            $aggByUser[$uid] = array(
                'absence_total' => $absence,
                'makeup_total' => $makeup,
                'remaining' => round($absence - $makeup, 1)
            );
        }
    }

    // 최근 항목(각 5개)
    $paramsRecent = $studentIds;
    $sqlRecent = "SELECT userid, event, amount, due
                  FROM {abessi_classtimemanagement}
                  WHERE hide = 0 AND userid IN ($in)
                  ORDER BY due DESC";
    $rows = $DB->get_records_sql($sqlRecent, $paramsRecent);
    if ($rows) {
        foreach ($rows as $r) {
            $uid = intval($r->userid);
            $txt = date('m/d H:i', intval($r->due)) . ' ' . rtrim(rtrim(number_format((float)$r->amount, 1), '0'), '.') . 'h';
            if ($r->event === 'absence') {
                if (!isset($absTextByUser[$uid])) $absTextByUser[$uid] = array();
                if (count($absTextByUser[$uid]) < 5) $absTextByUser[$uid][] = $txt;
            } elseif ($r->event === 'makeup') {
                if (!isset($makeTextByUser[$uid])) $makeTextByUser[$uid] = array();
                if (count($makeTextByUser[$uid]) < 5) $makeTextByUser[$uid][] = $txt;
            }
        }
    }
}

// 수업시간 문자열 생성
function format_schedule_text($schedule) {
    if (!$schedule) return '';
    $days = array(1=>'월',2=>'화',3=>'수',4=>'목',5=>'금',6=>'토',7=>'일');
    $parts = array();
    for ($d=1; $d<=7; $d++) {
        $durationField = 'duration' . $d;
        $startFieldNum = 'start' . $d;       // 정수 시각(예: 19)
        $startFieldStr = 'starttime' . $d;   // 문자열(예: "19:00")
        $duration = isset($schedule->$durationField) ? floatval($schedule->$durationField) : 0;
        if ($duration > 0) {
            $timeText = '';
            if (isset($schedule->$startFieldStr) && !empty($schedule->$startFieldStr)) {
                $timeText = $schedule->$startFieldStr;
            } elseif (isset($schedule->$startFieldNum) && $schedule->$startFieldNum !== null && $schedule->$startFieldNum !== '') {
                $h = intval($schedule->$startFieldNum);
                if ($h >= 0 && $h <= 23) {
                    $timeText = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
                }
            }
            $durText = rtrim(rtrim(number_format($duration, 1), '0'), '.');
            $parts[] = $days[$d] . (empty($timeText) ? '' : (' ' . $timeText)) . ' ' . $durText . 'h';
        }
    }
    return implode(', ', $parts);
}

// 학생 배열을 렌더링용으로 구성
$rows = array();
foreach ($students as $s) {
    $uid = intval($s->id);
    $name = trim($s->firstname . ' ' . $s->lastname);
    $school = isset($schoolByUser[$uid]) ? $schoolByUser[$uid] : '';
    $grade = isset($gradeByUser[$uid]) ? $gradeByUser[$uid] : '';
    if ($grade === '' || $grade === null) {
        // 출생년도 기반 자동 계산 (한국 학제, 3월 신학기)
        $birthYear = isset($birthByUser[$uid]) ? intval(preg_replace('/[^0-9]/','',$birthByUser[$uid])) : 0;
        if ($birthYear > 0) {
            $currentYear = intval(date('Y'));
            $currentMonth = intval(date('n'));
            $schoolYear = ($currentMonth < 3) ? ($currentYear - 1) : $currentYear;
            $g = $schoolYear - $birthYear - 6; // 1학년 시작 기준
            if ($g >= 1 && $g <= 6) {
                $grade = '초등 ' . $g . '학년';
            } elseif ($g >= 7 && $g <= 9) {
                $grade = '중등 ' . ($g - 6) . '학년';
            } elseif ($g >= 10 && $g <= 12) {
                $grade = '고등 ' . ($g - 9) . '학년';
            } else {
                // 범위를 벗어나면 공백 유지
            }
        }
    }
    $schedule = isset($scheduleByUser[$uid]) ? $scheduleByUser[$uid] : null;
    $scheduleText = format_schedule_text($schedule);
    $absText = isset($absTextByUser[$uid]) ? implode(', ', $absTextByUser[$uid]) : '';
    $makeText = isset($makeTextByUser[$uid]) ? implode(', ', $makeTextByUser[$uid]) : '';
    $agg = isset($aggByUser[$uid]) ? $aggByUser[$uid] : array('absence_total'=>0,'makeup_total'=>0,'remaining'=>0);
    $remaining = $agg['remaining'];

    $rows[] = array(
        'id' => $uid,
        'name' => $name,
        'email' => isset($s->email) ? (string)$s->email : '',
        'phone' => isset($s->phone1) ? (string)$s->phone1 : (isset($s->phone2) ? (string)$s->phone2 : ''),
        'school' => $school,
        'grade' => $grade,
        'classSchedule' => $scheduleText,
        'canceledClasses' => $absText,
        'makeupClasses' => $makeText,
        'remainingMakeup' => $remaining,
        'examDate' => ($schedule && isset($schedule->memo6)) ? (string)$schedule->memo6 : '',
        'examScope' => ($schedule && isset($schedule->memo7)) ? (string)$schedule->memo7 : '',
        'remarks' => ($schedule && isset($schedule->memo8)) ? (string)$schedule->memo8 : '',
        'scheduleId' => $schedule ? intval($schedule->id) : 0
    );
}

// 검색어 공백무시 후 2차 필터링 (이름/학교/학년/이메일/전화/시간표/휴강/보강)
if ($search !== '') {
    $q = mb_strtolower(preg_replace('/\s+/u','', $search));
    $rows = array_values(array_filter($rows, function($r) use ($q) {
        $hay = implode(' ', array(
            $r['name'], $r['school'], $r['grade'], $r['email'], $r['phone'],
            $r['classSchedule'], $r['canceledClasses'], $r['makeupClasses']
        ));
        $hay = mb_strtolower(preg_replace('/\s+/u','', $hay));
        return strpos($hay, $q) !== false;
    }));
}

// 정렬 (PHP 측)
usort($rows, function($a, $b) use ($sort, $dir) {
    $av = '';
    $bv = '';
    if ($sort === 'remaining') {
        $av = floatval($a['remainingMakeup']);
        $bv = floatval($b['remainingMakeup']);
    } elseif ($sort === 'school') {
        $av = mb_strtolower($a['school']);
        $bv = mb_strtolower($b['school']);
    } elseif ($sort === 'grade') {
        $av = mb_strtolower($a['grade']);
        $bv = mb_strtolower($b['grade']);
    } else {
        $av = mb_strtolower($a['name']);
        $bv = mb_strtolower($b['name']);
    }
    if ($av == $bv) return 0;
    if ($dir === 'DESC') return ($av < $bv) ? 1 : -1;
    return ($av > $bv) ? 1 : -1;
});

// 정렬 링크 토글
function sort_link($key, $label, $currentSort, $currentDir) {
    $nextDir = ($currentSort === $key && $currentDir === 'ASC') ? 'desc' : 'asc';
    $arrow = '';
    if ($currentSort === $key) {
        $arrow = $currentDir === 'ASC' ? '▲' : '▼';
    }
    $qs = http_build_query(array(
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'sort' => $key,
        'dir' => $nextDir
    ));
    return '<a href="?'.$qs.'" style="color:inherit; text-decoration:none;">'.$label.' '.$arrow.'</a>';
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>학생 관리 시스템</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans KR', 'Apple SD Gothic Neo', Arial, sans-serif; background:#f1f5f9; margin:0; }
        .container { max-width:1400px; margin:24px auto; padding:0 8px; box-sizing: border-box; }
        .card { background:#fff; border-radius:12px; box-shadow:none; padding:16px; margin-bottom:16px; }
        .header { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .title { font-size:22px; font-weight:800; color:#1f2937; }
        .controls { display:flex; gap:8px; flex-wrap:wrap; }
        .btn { background:linear-gradient(90deg,#34d399,#10b981); color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer; font-weight:600; font-size:13px; box-shadow:none; }
        .btn:hover { filter:brightness(1.03); transform:translateY(-1px); transition:.15s ease; }
        .search { display:flex; align-items:center; gap:8px; margin-top:12px; }
        .search input { flex:1; padding:8px 10px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
        .search input:focus { outline:none; border-color:#93c5fd; }
        table { width:100%; border-collapse:collapse; font-size:13px; table-layout: fixed; }
        thead th { position:sticky; top:0; background:linear-gradient(90deg,#22c55e,#16a34a); color:#f8fafc; padding:10px 8px; text-align:left; border-right:1px solid rgba(255,255,255,0.12); font-weight:700; letter-spacing:0.2px; }
        thead th:first-child { border-top-left-radius:10px; }
        thead th:last-child { border-top-right-radius:10px; border-right:none; }
        tbody td { padding:10px 8px; border-bottom:1px solid #e5e7eb; background:#fff; overflow:visible; line-height:1.45; }
        th, td { word-break: break-word; white-space: normal; }
        /* 셀은 줄바꿈 허용 + 세로 확장 */
        .cell { display:block; max-width:100%; overflow:visible; white-space:normal; word-break:break-word; overflow-wrap:anywhere; }
        .editable { display:block; max-width:100%; overflow:visible; white-space:normal; word-break:break-word; overflow-wrap:anywhere; }
        tbody tr:nth-child(2n) td { background:#f0fdf4; }
        tbody tr:hover td { background:#dcfce7; transition: background-color .15s ease-in-out; }
        .muted { color:#9ca3af; font-style:italic; }
        .remaining { font-weight:700; }
        .remaining.pos { color:#dc2626; }
        .remaining.zero { color:#10b981; }
        .remaining.neg { color:#16a34a; }
        .links a { display:inline-block; margin-right:6px; font-size:12px; color:#10b981; text-decoration:none; }
        .links a:hover { text-decoration:underline; color:#059669; }
        .legend { display:flex; gap:16px; font-size:12px; color:#6b7280; margin-top:8px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; font-size:11px; }
        .badge.teacher { background:#ecfdf5; color:#065f46; }
        .count { font-size:12px; color:#6b7280; }
        .btn.secondary { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
        .btn.secondary:hover { background:#e5e7eb; }
        .btn.sm { padding:6px 10px; font-size:12px; border-radius:6px; }
        .examdate-input:focus { outline:none; border-color:#a7f3d0 !important; box-shadow:none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div>
                    <div class="title">📚 학생 관리 시스템</div>
                    <div class="count">총 <?= count($rows) ?>명 · 보기 교사 ID: <?= (int)$teacherid ?><?= $readonlyMode ? ' · 다른 교사 보기' : '' ?></div>
                </div>
                <div class="controls">
                    <span class="badge teacher">심볼: <?= htmlspecialchars($tsymbol ?: '-') ?></span>
                    <a class="btn" href="<?= htmlspecialchars($CFG->wwwroot) ?>/local/augmented_teacher/alt42/omniui/attendance_teacher.php" target="_blank">⏱️ 출결관리</a>
                </div>
            </div>
            <form class="search" method="get" action="">
                <input type="text" name="search" placeholder="이름, 학교 등 검색..." value="<?= htmlspecialchars($search) ?>" />
                <button type="submit" class="btn">검색</button>
            </form>
            <div class="legend">
                <div><strong>남은보강</strong>: 최근 3주 기준 (휴강 - 보강)</div>
                <div><span class="remaining pos">3.0+</span> 보강 많이 남음 · <span class="remaining zero">0.0</span> 정상 · <span class="remaining neg">-X.X</span> 초과 학습</div>
                <div><strong>시간표</strong>: 기본(핀) 기준 표시 · 임시는 출결관리에서 기록</div>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-y:auto; overflow-x:hidden;">
            <table>
                <thead>
                    <tr>
                        <th style="width:10%; "><?= sort_link('name','이름',$sort,$dir) ?></th>
                        <th style="width:10%; "><?= sort_link('school','학교',$sort,$dir) ?></th>
                        <th style="width:6%; text-align:center; "><?= sort_link('grade','학년',$sort,$dir) ?></th>
                        <th style="width:11%;">수업시간</th>
                        <th style="width:7%; text-align:center;">휴강</th>
                        <th style="width:6%; text-align:right; "><?= sort_link('remaining','남은보강',$sort,$dir) ?></th>
                        <th style="width:30%;">시험날짜</th>
                        <th style="width:8%;">시험범위 메모</th>
                        <th style="width:9%;">비고</th>
                        <th style="width:7%;">링크</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="10" style="text-align:center; padding:16px; color:#6b7280;">검색 결과가 없습니다.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><div class="cell"><?= htmlspecialchars($r['name']) ?></div></td>
                                <td><div class="cell"><?= $r['school'] ? htmlspecialchars($r['school']) : '<span class="muted">(미입력)</span>' ?></div></td>
                                <td><div class="cell"><?= $r['grade'] ? htmlspecialchars($r['grade']) : '<span class="muted">(미입력)</span>' ?></div></td>
                                <td>
                                    <div class="editable" data-type="schedule" data-userid="<?= $r['id'] ?>" contenteditable="<?= $currentIsTeacher ? 'true' : 'false' ?>" spellcheck="false">
                                        <?= $r['classSchedule'] ? htmlspecialchars($r['classSchedule']) : '' ?>
                                    </div>
                                    <?php if (!$r['classSchedule']): ?><span class="muted">(없음)</span><?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php $sid = intval($r['id']); ?>
                                    <a class="btn secondary sm" href="https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php?userid=<?= $sid ?>" target="_blank" title="휴강/출결 기록 바로가기">휴강 기록</a>
                                </td>
                                <?php 
                                    $rem = floatval($r['remainingMakeup']);
                                    $cls = ($rem > 0.1) ? 'pos' : (($rem < -0.1) ? 'neg' : 'zero');
                                ?>
                                <td style="text-align:right;"><span class="remaining <?= $cls ?>"><?= number_format($rem,1) ?></span></td>
                                <td>
                                    <?php $examValue = (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$r['examDate'])) ? $r['examDate'] : ''; ?>
                                    <input type="date" class="examdate-input" data-type="examdate" data-userid="<?= $r['id'] ?>" value="<?= htmlspecialchars($examValue) ?>" style="width:240px; padding:6px 8px; border:2px solid #e5e7eb; border-radius:6px; font-size:12px;" <?= $currentIsTeacher ? '' : 'disabled' ?> />
                                </td>
                                <td>
                                    <div class="editable" data-type="examscope" data-userid="<?= $r['id'] ?>" contenteditable="<?= $currentIsTeacher ? 'true' : 'false' ?>" spellcheck="false">
                                        <?= $r['examScope'] ? htmlspecialchars($r['examScope']) : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="editable" data-type="remarks" data-userid="<?= $r['id'] ?>" contenteditable="<?= $currentIsTeacher ? 'true' : 'false' ?>" spellcheck="false">
                                        <?= $r['remarks'] ? htmlspecialchars($r['remarks']) : '' ?>
                                    </div>
                                </td>
                                <td class="links"><div class="cell">
                                    <?php $sid = intval($r['id']); $scid = intval($r['scheduleId']); ?>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id=<?= $sid ?><?= $scid>0?('&eid='.$scid):'' ?>&nweek=12" target="_blank" title="시간표">📅</a>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id=<?= $sid ?>" target="_blank" title="스캐폴딩">🧱</a>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/attendance_teacher.php?userid=<?= $sid ?>" target="_blank" title="출결">⏱️</a>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        // 인라인 편집: Enter/Blur 시 저장
        (function(){
            var SESSKEY = '<?= sesskey() ?>';
            function getAjaxEndpoint(){
                try { return new URL(window.location.href).toString(); }
                catch(e){ return window.location.href; }
            }
            function postForm(body){
                var url = getAjaxEndpoint();
                return fetch(url, {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: body
                }).then(function(r){
                    if(!r.ok) { throw new Error('HTTP '+r.status); }
                    return r.json();
                });
            }
            function postOrGet(action, body){
                var baseUrl = getAjaxEndpoint();
                var fullBody = 'ajax='+encodeURIComponent(action)
                             + (SESSKEY ? ('&sesskey='+encodeURIComponent(SESSKEY)) : '')
                             + (body ? ('&'+body) : '');
                return postForm(fullBody).catch(function(err){
                    var is404 = err && String(err.message||'').indexOf('HTTP 404') >= 0;
                    if (!is404) { throw err; }
                    var joinChar = baseUrl.indexOf('?') >= 0 ? '&' : '?';
                    var getUrl = baseUrl + joinChar + fullBody;
                    return fetch(getUrl, { method:'GET' }).then(function(r){
                        if(!r.ok) { throw new Error('HTTP '+r.status); }
                        return r.json();
                    });
                });
            }
            function getRowValues(userid){
                var container = document.querySelectorAll('.editable[data-userid="'+userid+'"]');
                var vals = { schedule:'', canceled:'' };
                container.forEach(function(el){
                    var t = el.getAttribute('data-type');
                    var v = (el.textContent||'').trim();
                    if (t==='schedule') vals.schedule = v;
                    if (t==='canceled') vals.canceled = v;
                });
                return vals;
            }
            function save(el){
                var userid = parseInt(el.getAttribute('data-userid'),10);
                var type = el.getAttribute('data-type');
                var vals = getRowValues(userid);
                if (type==='schedule'){
                    postOrGet('update_schedule',
                        'userid='+encodeURIComponent(userid)+'&schedule='+encodeURIComponent(vals.schedule)
                    ).then(function(j){
                        if(j.status!=='success'){ alert('저장 실패: '+(j.message||'')); }
                        else { location.reload(); }
                    }).catch(function(err){ alert('저장 오류: '+ (err && err.message ? err.message : '')); });
                } else if (type==='canceled') {
                    postOrGet('update_attendance',
                        'userid='+encodeURIComponent(userid)+'&canceled='+encodeURIComponent(vals.canceled)
                    ).then(function(j){
                        if(j.status!=='success'){ alert('저장 실패: '+(j.message||'')); }
                        else { location.reload(); }
                    }).catch(function(err){ alert('저장 오류: '+ (err && err.message ? err.message : '')); });
                } else if (type==='examdate' || type==='examscope' || type==='remarks') {
                    var examdateInput = document.querySelector('input.examdate-input[data-userid="'+userid+'"]');
                    var examdate = examdateInput ? (examdateInput.value||'').trim() : '';
                    var examscope = (document.querySelector('.editable[data-type="examscope"][data-userid="'+userid+'"]').textContent||'').trim();
                    var remarks = (document.querySelector('.editable[data-type="remarks"][data-userid="'+userid+'"]').textContent||'').trim();
                    postOrGet('update_examinfo',
                        'userid='+encodeURIComponent(userid)+'&examdate='+encodeURIComponent(examdate)+'&examscope='+encodeURIComponent(examscope)+'&remarks='+encodeURIComponent(remarks)
                    ).then(function(j){
                        if(j.status!=='success'){ alert('저장 실패: '+(j.message||'')); }
                        else { location.reload(); }
                    }).catch(function(err){ alert('저장 오류: '+ (err && err.message ? err.message : '')); });
                }
            }
            document.querySelectorAll('.editable').forEach(function(el){
                el.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); this.blur(); } });
                el.addEventListener('blur', function(){ save(this); });
            });
            // 날짜 인풋 변경 시 저장
            document.querySelectorAll('input.examdate-input').forEach(function(el){
                el.addEventListener('change', function(){
                    var userid = parseInt(this.getAttribute('data-userid'),10);
                    var examscopeEl = document.querySelector('.editable[data-type="examscope"][data-userid="'+userid+'"]');
                    var remarksEl = document.querySelector('.editable[data-type="remarks"][data-userid="'+userid+'"]');
                    var examdate = this.value || '';
                    var examscope = examscopeEl ? (examscopeEl.textContent||'').trim() : '';
                    var remarks = remarksEl ? (remarksEl.textContent||'').trim() : '';
                    postOrGet('update_examinfo',
                        'userid='+encodeURIComponent(userid)+'&examdate='+encodeURIComponent(examdate)+'&examscope='+encodeURIComponent(examscope)+'&remarks='+encodeURIComponent(remarks)
                    ).then(function(j){
                        if(j.status!=='success'){ alert('저장 실패: '+(j.message||'')); }
                        else { location.reload(); }
                    }).catch(function(err){ alert('저장 오류: '+ (err && err.message ? err.message : '')); });
                });
            });
        })();
        </script>
    </div>
</body>
</html>


