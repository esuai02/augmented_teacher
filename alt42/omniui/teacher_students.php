<?php
// 교사별 학생 정보 보기
// - 교사 로그인: 본인 담당 학생만 표시
// - 관리자/비학생: 드롭다운으로 교사 선택 후 해당 담당 학생 표시

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();
date_default_timezone_set('Asia/Seoul');

// 권한 확인
$roleRec = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = 22", array($USER->id));
$currentRole = $roleRec ? $roleRec->role : '';
if ($currentRole === 'student') {
    die('<h2>접근 권한이 없습니다. (학생)</h2>');
}

// 교사 목록 (비학생 전용 UI용)
$teachers = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, u.email, uid.data AS role
     FROM {user} u
     LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = 22
     WHERE u.deleted = 0 AND u.suspended = 0 AND (uid.data IS NULL OR uid.data <> 'student')
     ORDER BY u.firstname ASC, u.lastname ASC LIMIT 800"
);

// 요청 파라미터
$teacherid = ($currentRole && $currentRole !== 'student' && $currentRole !== '') ? intval($USER->id) : 0; // 기본: 본인
if ($currentRole !== 'student' && $currentRole !== 'teacher') {
    // 관리자 등은 선택 가능
    $teacherid = isset($_GET['teacherid']) ? intval($_GET['teacherid']) : $teacherid;
}

// 교사 심볼 결정
function resolve_teacher_symbol($DB, $userid, $firstname) {
    $f64 = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 64", array($userid));
    if ($f64 && !empty($f64->data)) return trim($f64->data);
    if (!empty($firstname)) {
        foreach (array('🌟','⭐','✨','🎯','🔥','💫','🌈','🎨','🎪','🎭') as $sym) {
            if (strpos($firstname, $sym) !== false) return $sym;
        }
    }
    $pool = array('🌟','⭐','✨','🎯','🔥','💫','🌈','🎨','🎪','🎭');
    return $pool[$userid % count($pool)];
}

$selTeacher = null; $tsymbol = '';
if ($teacherid > 0) {
    $selTeacher = $DB->get_record_sql("SELECT id, firstname, lastname, email FROM {user} WHERE id = ?", array($teacherid));
    if ($selTeacher) $tsymbol = resolve_teacher_symbol($DB, $teacherid, $selTeacher->firstname);
}

// 학생 목록 (담당 필터)
$params = array();
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2
        FROM {user} u
        INNER JOIN {user_info_data} uid ON u.id = uid.userid AND uid.fieldid = 22 AND uid.data = 'student'
        WHERE u.deleted = 0 AND u.suspended = 0";

if ($teacherid > 0 && !empty($tsymbol)) {
    $sql .= " AND (EXISTS (SELECT 1 FROM {user_info_data} uids WHERE uids.userid = u.id AND uids.fieldid = 64 AND uids.data LIKE ?) OR u.firstname LIKE ?)";
    $params[] = '%'.$tsymbol.'%';
    $params[] = '%'.$tsymbol.'%';
} else {
    // 심볼이 없으면 아무도 노출하지 않음 (보안)
    $sql .= " AND 1=0";
}

$sql .= " ORDER BY u.firstname ASC, u.lastname ASC LIMIT 500";
$students = $DB->get_records_sql($sql, $params);

// 보조: recent absence/makeup & schedule formatting
function fmt_schedule($DB, $userid) {
    $s = $DB->get_record_sql("SELECT * FROM {abessi_schedule} WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1", array($userid));
    if (!$s) return '';
    $days = array(1=>'월',2=>'화',3=>'수',4=>'목',5=>'금',6=>'토',7=>'일');
    $parts = array();
    for ($d=1;$d<=7;$d++) {
        $dur = isset($s->{'duration'.$d}) ? floatval($s->{'duration'.$d}) : 0;
        if ($dur > 0) {
            $t = isset($s->{'starttime'.$d}) ? (string)$s->{'starttime'.$d} : '';
            $durText = rtrim(rtrim(number_format($dur,1),'0'),'.');
            $parts[] = $days[$d].($t?(' '.$t):' ').$durText.'h';
        }
    }
    return implode(', ', $parts);
}

function recent_attendance($DB, $userid) {
    $since = strtotime('-3 weeks');
    $rows = $DB->get_records_sql(
        "SELECT event, amount, due FROM {abessi_classtimemanagement} WHERE userid = ? AND hide = 0 AND due >= ? ORDER BY due DESC",
        array($userid, $since)
    );
    $abs = array(); $mak = array(); $sa=0; $sm=0;
    if ($rows) foreach ($rows as $r) {
        $txt = date('m/d H:i', intval($r->due)).' '.rtrim(rtrim(number_format((float)$r->amount,1),'0'),'.').'h';
        if ($r->event==='absence') { $abs[]=$txt; $sa+=floatval($r->amount); }
        if ($r->event==='makeup')  { $mak[]=$txt; $sm+=floatval($r->amount); }
    }
    return array('absText'=>implode(', ',$abs),'makText'=>implode(', ',$mak),'remain'=>round($sa-$sm,1));
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>교사별 학생 보기</title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR','Apple SD Gothic Neo',Arial,sans-serif;background:#f1f5f9;margin:0}
        .container{max-width:1200px;margin:24px auto;padding:0 16px}
        .card{background:#fff;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.06);padding:16px;margin-bottom:16px}
        .header{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .title{font-size:22px;font-weight:800;color:#1f2937}
        .controls{display:flex;gap:8px;align-items:center}
        .btn{background:#2563eb;color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;font-size:13px;text-decoration:none}
        .btn:hover{background:#1d4ed8}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-weight:700;font-size:11px;background:#eef2ff;color:#4338ca}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
        thead th{background:#f3f4f6;font-weight:700}
        .muted{color:#9ca3af;font-style:italic}
        .remain{font-weight:700}
        .pos{color:#dc2626}.zero{color:#2563eb}.neg{color:#16a34a}
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div>
                    <div class="title">👩‍🏫 교사별 학생 보기</div>
                    <div style="color:#6b7280;font-size:12px;">담당 심볼로 학생을 매칭합니다. 심볼 문제는 <a href="<?= htmlspecialchars($CFG->wwwroot) ?>/local/augmented_teacher/alt42/omniui/teacher_symbols.php" class="btn" style="padding:2px 8px; font-size:11px;">심볼 진단</a>에서 확인</div>
                </div>
                <div class="controls">
                    <?php if ($currentRole !== 'teacher'): ?>
                        <form method="get" action="" style="display:flex; gap:8px; align-items:center;">
                            <label for="teacherid" style="font-size:13px;color:#374151;font-weight:600;">교사</label>
                            <select name="teacherid" id="teacherid" onchange="this.form.submit()" style="padding:6px 8px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px;">
                                <?php if ($teachers): foreach ($teachers as $t): ?>
                                <option value="<?= intval($t->id) ?>" <?= intval($t->id)===$teacherid?'selected':'' ?>><?= htmlspecialchars(trim($t->firstname.' '.$t->lastname)) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <span class="badge">심볼: <?= htmlspecialchars($tsymbol ?: '-') ?></span>
                        </form>
                    <?php else: ?>
                        <span class="badge">심볼: <?= htmlspecialchars($tsymbol ?: '-') ?></span>
                    <?php endif; ?>
                    <a class="btn" href="<?= htmlspecialchars($CFG->wwwroot) ?>/local/augmented_teacher/alt42/omniui/student_management.php" target="_blank">학생 관리</a>
                </div>
            </div>
        </div>

        <div class="card" style="padding:0; overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px; text-align:center;">No</th>
                        <th style="min-width:160px;">이름</th>
                        <th style="min-width:160px;">학교</th>
                        <th style="min-width:100px;">연락처</th>
                        <th style="min-width:260px;">수업시간</th>
                        <th style="min-width:220px;">휴강</th>
                        <th style="min-width:220px;">보강</th>
                        <th style="min-width:90px; text-align:right;">남은보강</th>
                        <th style="min-width:220px;">바로가기</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$students): ?>
                        <tr><td colspan="9" style="text-align:center;color:#6b7280;padding:20px;">표시할 학생이 없습니다.</td></tr>
                    <?php else: $i=1; foreach ($students as $stu):
                        // school from user_info_data (institute=88)
                        $school = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 88", array($stu->id));
                        $sched = fmt_schedule($DB, $stu->id);
                        $att = recent_attendance($DB, $stu->id);
                        $rem = floatval($att['remain']); $cls = ($rem>0.1?'pos':($rem<-0.1?'neg':'zero'));
                    ?>
                        <tr>
                            <td style="text-align:center;color:#6b7280;font-weight:600;"><?= $i++ ?></td>
                            <td><?= htmlspecialchars(trim($stu->firstname.' '.$stu->lastname)) ?></td>
                            <td><?= $school && $school->data ? htmlspecialchars($school->data) : '<span class="muted">(미입력)</span>' ?></td>
                            <td><?= htmlspecialchars($stu->phone1 ?: ($stu->phone2 ?: '')) ?></td>
                            <td><?= $sched ? htmlspecialchars($sched) : '<span class="muted">(없음)</span>' ?></td>
                            <td><?= $att['absText'] ? htmlspecialchars($att['absText']) : '<span class="muted">-</span>' ?></td>
                            <td><?= $att['makText'] ? htmlspecialchars($att['makText']) : '<span class="muted">-</span>' ?></td>
                            <td style="text-align:right;"><span class="remain <?= $cls ?>"><?= number_format($rem,1) ?></span></td>
                            <td>
                                <a class="btn" style="background:#059669" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id=<?= intval($stu->id) ?>" target="_blank">시간표</a>
                                <a class="btn" style="background:#9333ea" href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id=<?= intval($stu->id) ?>" target="_blank">스캐폴딩</a>
                                <a class="btn" style="background:#0ea5e9" href="<?= htmlspecialchars($CFG->wwwroot) ?>/local/augmented_teacher/alt42/omniui/attendance_teacher.php?userid=<?= intval($stu->id) ?>" target="_blank">출결</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


