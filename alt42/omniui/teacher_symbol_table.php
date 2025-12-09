<?php
// 교사 심볼 조회 테이블 (fieldid=79 기본)
// 사용법: teacher_symbol_table.php?ids=13,2,1719,1896,255,1500,1852,5,1656,827,943,1561&fieldid=79

declare(strict_types=1);

// DB 설정 로드
require_once __DIR__ . '/config.php';

$dbHost = defined('DB_HOST') ? DB_HOST : (isset($CFG->dbhost) ? $CFG->dbhost : 'localhost');
$dbName = defined('DB_NAME') ? DB_NAME : (isset($CFG->dbname) ? $CFG->dbname : '');
$dbUser = defined('DB_USER') ? DB_USER : (isset($CFG->dbuser) ? $CFG->dbuser : '');
$dbPass = defined('DB_PASS') ? DB_PASS : (isset($CFG->dbpass) ? $CFG->dbpass : '');
$prefix = defined('MATHKING_DB_PREFIX') ? MATHKING_DB_PREFIX : (isset($CFG->prefix) ? $CFG->prefix : 'mdl_');

// 파라미터: ids(콤마 구분), fieldid(기본 79)
$idsParam = isset($_GET['ids']) ? trim((string)$_GET['ids']) : '13,2,1719,1896,255,1500,1852,5,1656,827,943,1561';
$fieldId  = isset($_GET['fieldid']) ? (int)$_GET['fieldid'] : 79;

$idList = array_values(array_unique(array_filter(array_map(function($v){
    return (int)preg_replace('/[^0-9]/','',$v);
}, explode(',', $idsParam)), function($v){ return $v > 0; })));

$rows = [];
$errorMsg = '';

try {
    if (!empty($idList)) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $sql = "SELECT uid.userid, uid.data AS symbol_raw
                FROM {$prefix}user_info_data uid
                WHERE uid.fieldid = ? AND uid.userid IN ($placeholders)
                ORDER BY uid.userid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$fieldId], $idList));
        $fetched = $stmt->fetchAll();

        // 결과를 id 기준으로 맵핑(누락된 id도 보여주기 위해)
        $map = [];
        foreach ($fetched as $r) {
            $map[(int)$r['userid']] = (string)$r['symbol_raw'];
        }

        foreach ($idList as $uid) {
            $raw = isset($map[$uid]) ? trim((string)$map[$uid]) : '';
            $norm = $raw;
            if ($norm !== '') {
                $low = mb_strtolower($norm);
                if (mb_strpos($low, '클로버') !== false || mb_strpos($low, 'clover') !== false) {
                    $norm = '☘';
                }
                if ($norm === '☘️' || $norm === '🍀') { $norm = '☘'; }
                if ($norm === '♣') { $norm = '♣️'; }
            }
            $rows[] = [
                'userid' => $uid,
                'symbol_raw' => $raw,
                'symbol_norm' => $norm,
            ];
        }
    }
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}

?><!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <title>교사 심볼 조회</title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR','Apple SD Gothic Neo',Arial,sans-serif;background:#f8fafc;margin:0}
        .container{max-width:900px;margin:24px auto;padding:0 16px}
        .card{background:#fff;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.06);padding:16px;margin-bottom:16px}
        h1{margin:0 0 6px 0;color:#111827}
        .desc{color:#6b7280;font-size:13px;margin-bottom:12px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
        thead th{background:#f3f4f6;font-weight:700}
        .mono{font-family:ui-monospace,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace}
        .muted{color:#9ca3af}
        .form{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
        input[type=text],input[type=number]{padding:8px 10px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px}
        button{background:#10b981;color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;font-size:13px}
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>교사 심볼 조회</h1>
            <div class="desc">fieldid 기본값은 79입니다. ids를 콤마로 구분해 입력하세요.</div>
            <form class="form" method="get">
                <input type="text" name="ids" value="<?= htmlspecialchars($idsParam, ENT_QUOTES, 'UTF-8') ?>" style="flex:1; min-width:340px;" />
                <input type="number" name="fieldid" value="<?= (int)$fieldId ?>" style="width:110px;" />
                <button type="submit">조회</button>
            </form>
            <?php if ($errorMsg !== ''): ?>
                <div style="color:#b91c1c; margin-bottom:8px;">오류: <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:120px;">userid</th>
                        <th style="width:300px;">symbol_raw</th>
                        <th style="width:120px;">symbol_norm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($idList)): ?>
                        <tr><td colspan="3" class="muted">ids를 입력하세요.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td class="mono"><?= (int)$r['userid'] ?></td>
                            <td><?= $r['symbol_raw'] !== '' ? htmlspecialchars($r['symbol_raw'], ENT_QUOTES, 'UTF-8') : '<span class="muted">(없음)</span>' ?></td>
                            <td><?= $r['symbol_norm'] !== '' ? htmlspecialchars($r['symbol_norm'], ENT_QUOTES, 'UTF-8') : '<span class="muted">(없음)</span>' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


