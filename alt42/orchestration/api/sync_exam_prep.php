<?php
/**
 * Sync selected exam context to omniui exam preparation system
 * Creates/updates alt42t_* tables for the given userid so that
 * /omniui/exam_preparation_system.php?userid= shows consistent data.
 *
 * Input (JSON or form):
 * - userid (int, required)
 * - exam_timeline (string, optional) e.g., '🚨 D-1주'
 */
header('Content-Type: application/json; charset=utf-8');

try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    require_login();

    $raw = file_get_contents('php://input');
    $in = is_string($raw) && strlen($raw) ? json_decode($raw, true) : null;
    if (!is_array($in)) { $in = $_POST + $_GET; }

    $userid = isset($in['userid']) ? intval($in['userid']) : (isset($USER->id) ? intval($USER->id) : 0);
    if ($userid <= 0) {
        throw new Exception('invalid userid');
    }
    $examTimeline = isset($in['exam_timeline']) ? trim((string)$in['exam_timeline']) : '';

    // Load user school (fieldid 88) and birth year (fieldid 89)
    $school = '';
    $birthRaw = '';
    try {
        $school = (string)$DB->get_field('user_info_data', 'data', ['userid'=>$userid, 'fieldid'=>88]) ?: '';
        $birthRaw = (string)$DB->get_field('user_info_data', 'data', ['userid'=>$userid, 'fieldid'=>89]) ?: '';
    } catch (Exception $e) {
        // ignore
    }

    // Parse birth year
    $birthYear = 0;
    if ($birthRaw) {
        if (preg_match('/(\d{4})/', $birthRaw, $m)) {
            $birthYear = intval($m[1]);
        }
    }

    // Compute grade (rough) based on birth year (align with omniui rules)
    $gradeStr = '';
    $gradeNum = 0;
    if ($birthYear >= 2007 && $birthYear <= 2012) {
        // Mapping similar to omniui (see exam_preparation_system.php)
        $map = [
            2007 => ['고등학교 3학년', 3],
            2008 => ['고등학교 2학년', 2],
            2009 => ['고등학교 1학년', 1],
            2010 => ['중학교 3학년', 3],
            2011 => ['중학교 2학년', 2],
            2012 => ['중학교 1학년', 1],
        ];
        $gradeStr = $map[$birthYear][0];
        $gradeNum = $map[$birthYear][1];
    }

    // Fallback: if no birth mapping, try to infer by school string
    if ($gradeNum === 0 && $school) {
        // default to 고등 1학년 if unknown
        $gradeStr = '고등학교 1학년';
        $gradeNum = 1;
    }

    // Ensure alt42t_users upsert
    $now = time();
    $existingUser = $DB->get_record('alt42t_users', ['userid' => $userid]);
    if ($existingUser) {
        $sql = "UPDATE {alt42t_users}
                SET name = :name,
                    school_name = :school,
                    grade = :grade,
                    timemodified = :tm
                WHERE userid = :userid";
        $DB->execute($sql, [
            'name' => $USER->firstname . ' ' . $USER->lastname,
            'school' => $school,
            'grade' => $gradeNum,
            'tm' => $now,
            'userid' => $userid
        ]);
        $alt42t_user_id = $existingUser->id;
    } else {
        $rec = new stdClass();
        $rec->userid = $userid;
        $rec->name = $USER->firstname . ' ' . $USER->lastname;
        $rec->school_name = $school;
        $rec->grade = $gradeNum;
        $rec->timecreated = $now;
        $rec->timemodified = $now;
        $rec->created_at = date('Y-m-d H:i:s');
        $alt42t_user_id = $DB->insert_record('alt42t_users', $rec);
    }

    // Ensure alt42t_exams upsert (exam_type best-effort; keep empty if unknown)
    $examType = '';
    // optional: approximate exam type from current month
    $m = intval(date('n'));
    if (in_array($m, [3,4,5])) $examType = '1학기 중간고사';
    elseif (in_array($m, [6,7])) $examType = '1학기 기말고사';
    elseif (in_array($m, [9,10])) $examType = '2학기 중간고사';
    elseif (in_array($m, [11,12])) $examType = '2학기 기말고사';
    elseif (in_array($m, [1,2])) $examType = '2학기 기말고사';

    // Try find existing exam row for same school/grade/userid
    $exam = $DB->get_record('alt42t_exams', [
        'school_name' => $school, 'grade' => $gradeNum, 'userid' => $userid
    ]);
    if ($exam) {
        $DB->execute("UPDATE {alt42t_exams}
                      SET exam_type = :type, timemodified = :tm
                      WHERE exam_id = :id", [
            'type' => $examType ?: ($exam->exam_type ?? ''),
            'tm' => $now,
            'id' => isset($exam->exam_id) ? $exam->exam_id : $exam->id
        ]);
        $exam_id = isset($exam->exam_id) ? $exam->exam_id : $exam->id;
    } else {
        $er = new stdClass();
        $er->school_name = $school;
        $er->grade = $gradeNum;
        $er->exam_type = $examType;
        $er->userid = $userid;
        $er->timecreated = $now;
        $er->timemodified = $now;
        $exam_id = $DB->insert_record('alt42t_exams', $er);
    }

    echo json_encode([
        'success' => true,
        'user_id' => $alt42t_user_id,
        'exam_id' => $exam_id,
        'message' => 'synced'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

