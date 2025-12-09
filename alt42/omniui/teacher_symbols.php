<?php
// 교사 심볼 진단 페이지
// - 각 교사의 심볼을 정리해서 보여줍니다.
// - 심볼 소스: user_info_data.fieldid=64 > firstname 이모지 > fallback(id 기반)

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 접근 제한: 학생은 접근 불가
$role = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = 22", array($USER->id));
if (!$role || $role->role === 'student') {
    die('<h2>접근 권한이 없습니다.</h2>');
}

// 교사/관리 사용자 목록 조회
$teachers = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, u.email, uid.data AS role
     FROM {user} u
     LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = 22
     WHERE u.deleted = 0 AND u.suspended = 0 AND (uid.data IS NULL OR uid.data <> 'student')
     ORDER BY u.firstname ASC, u.lastname ASC LIMIT 1000"
);

function resolve_symbol($DB, $userid, $firstname, $lastname) {
    // 1) fieldid=64
    $f64 = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 64", array($userid));
    if ($f64 && !empty($f64->data)) {
        $val = trim($f64->data);
        if (mb_stripos($val, '클로버') !== false || mb_stripos($val, 'clover') !== false) { $val = '☘'; }
        if ($val === '🍀' || $val === '☘️') { $val = '☘'; }
        return array('symbol' => $val, 'source' => 'field64');
    }
    // 2) firstname 이모지
    $emojiSet = array('☘','☘️','🍀','♣','♣️','🌟','⭐','✨','🎯','🔥','💫','🌈','🎨','🎪','🎭','♦️');
    if (!empty($firstname)) {
        foreach ($emojiSet as $sym) { if (strpos($firstname, $sym) !== false) return array('symbol'=>$sym,'source'=>'firstname'); }
        if (mb_stripos($firstname, '클로버') !== false || mb_stripos($firstname, 'clover') !== false) return array('symbol'=>'☘','source'=>'firstname');
    }
    if (!empty($lastname)) {
        foreach ($emojiSet as $sym) { if (strpos($lastname, $sym) !== false) return array('symbol'=>$sym,'source'=>'lastname'); }
        if (mb_stripos($lastname, '클로버') !== false || mb_stripos($lastname, 'clover') !== false) return array('symbol'=>'☘','source'=>'lastname');
    }
    // 3) fallback: userid 기반 결정적 매핑
    $pool = array('☘','♣️','🌟','⭐','✨','🎯','🔥','💫','🌈','🎨','🎪','🎭','♦️');
    $sym = $pool[$userid % count($pool)];
    return array('symbol' => $sym, 'source' => 'fallback');
}

// HTML
?><!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>교사 심볼 진단</title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR','Apple SD Gothic Neo',Arial,sans-serif;background:#f8fafc;margin:0}
        .container{max-width:1100px;margin:24px auto;padding:0 16px}
        .card{background:#fff;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.06);padding:16px;margin-bottom:16px}
        h1{margin:0 0 6px 0;color:#111827}
        .desc{color:#6b7280;font-size:13px;margin-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
        thead th{background:#f3f4f6;font-weight:700}
        .badge{display:inline-block;border-radius:999px;padding:2px 8px;font-weight:700;font-size:11px}
        .b1{background:#eef2ff;color:#4338ca}
        .b2{background:#ecfeff;color:#065f46}
        .b3{background:#fff7ed;color:#9a3412}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;color:#374151}
    </style>
    <meta name="robots" content="noindex,nofollow">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>교사 심볼 진단</h1>
            <div class="desc">심볼 결정 순서: <span class="mono">user_info_data.fieldid=64</span> → 교사 이름의 이모지 → <span class="mono">userid</span> 기반 폴백.
            학생 필터는 학생의 <span class="mono">fieldid=64</span> 또는 이름에 심볼 포함 시 매칭됩니다.</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:80px">UserID</th>
                        <th style="width:220px">이름</th>
                        <th style="width:200px">이메일</th>
                        <th style="width:120px">심볼</th>
                        <th style="width:120px">소스</th>
                        <th>매칭 규칙 요약</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teachers): foreach ($teachers as $t): $r=resolve_symbol($DB, intval($t->id), $t->firstname, $t->lastname); ?>
                        <tr>
                            <td class="mono"><?= intval($t->id) ?></td>
                            <td><?= htmlspecialchars(trim($t->firstname.' '.$t->lastname)) ?></td>
                            <td class="mono"><?= htmlspecialchars((string)$t->email) ?></td>
                            <td><span class="badge b1" title="교사 심볼"><?= htmlspecialchars($r['symbol']) ?></span></td>
                            <td>
                                <?php if ($r['source']==='field64'): ?>
                                    <span class="badge b2">field64</span>
                                <?php elseif ($r['source']==='firstname'): ?>
                                    <span class="badge b3">firstname</span>
                                <?php else: ?>
                                    <span class="badge">fallback</span>
                                <?php endif; ?>
                            </td>
                            <td class="mono">학생 uid64 LIKE "%<?= htmlspecialchars($r['symbol']) ?>%" OR firstname LIKE "%<?= htmlspecialchars($r['symbol']) ?>%"</td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="color:#6b7280; text-align:center; padding:16px;">표시할 교사가 없습니다.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


