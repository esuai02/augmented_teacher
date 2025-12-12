<?php
/**
 * Agent 10 - Concept Notes Data Analysis Tool
 * File: agent10_concept_notes/rules/data_analysis.php
 * 
 * 목적: rules.yaml 필드와 실제 DB 데이터를 비교 분석하여
 * - 데이터 소스 타입 식별 (sysdata/survdata/gendata)
 * - data_access.php 적용 여부 확인
 * - DB에 있지만 rules.yaml에 없는 데이터 식별
 * - 매핑 불일치 데이터 식별
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 파라미터
$studentid = optional_param('studentid', 0, PARAM_INT);
if (!$studentid) {
    $studentid = $USER->id;
}

// rules.yaml에서 사용하는 필드 추출
function extractRulesYamlFields() {
    $rulesFile = __DIR__ . '/rules.yaml';
    if (!file_exists($rulesFile)) {
        return [];
    }
    
    $content = file_get_contents($rulesFile);
    $fields = [];
    
    // field: 패턴으로 필드 추출 (여러 패턴 지원)
    preg_match_all('/field:\s*"([^"]+)"/', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_merge($fields, $matches[1]);
    }
    
    // analyze:, calculate:, compare: 등의 액션에서 필드 추출
    preg_match_all('/(analyze|calculate|compare|identify|evaluate|check|load|collect):\s*[\'"]?([a-z_]+)[\'"]?/i', $content, $actionMatches);
    if (!empty($actionMatches[2])) {
        $fields = array_merge($fields, $actionMatches[2]);
    }
    
    // 중복 제거 및 정렬
    $fields = array_unique($fields);
    sort($fields);
    
    return $fields;
}

// DB에서 실제 데이터 확인
function checkDatabaseFields($studentid) {
    global $DB;
    
    $dbFields = [
        // mdl_abessi_messages (개념노트)
        'abessi_messages' => [],
        // mdl_abessi_tracking (포모도로)
        'abessi_tracking' => [],
        // mdl_alt42_calmness (침착도)
        'alt42_calmness' => [],
        // mdl_alt42_onboarding (온보딩)
        'alt42_onboarding' => [],
    ];
    
    try {
        // abessi_messages 조회 (contentstype=1: 개념공부 필기보드)
        $messages = $DB->get_records_sql(
            "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, url, usedtime, 
                    student_check, turn, timemodified, hide, contentstype
             FROM {abessi_messages}
             WHERE userid = ? AND contentstype = 1
             LIMIT 1",
            [$studentid]
        );
        
        if ($messages) {
            $sample = reset($messages);
            $dbFields['abessi_messages'] = [
                'nstroke' => isset($sample->nstroke) ? 'exists' : 'missing',
                'tlaststroke' => isset($sample->tlaststroke) ? 'exists' : 'missing',
                'timecreated' => isset($sample->timecreated) ? 'exists' : 'missing',
                'contentstitle' => isset($sample->contentstitle) ? 'exists' : 'missing',
                'url' => isset($sample->url) ? 'exists' : 'missing',
                'usedtime' => isset($sample->usedtime) ? 'exists' : 'missing',
                'student_check' => isset($sample->student_check) ? 'exists' : 'missing',
                'turn' => isset($sample->turn) ? 'exists' : 'missing',
                'timemodified' => isset($sample->timemodified) ? 'exists' : 'missing',
            ];
        }
        
        // abessi_tracking 조회
        $tracking = $DB->get_records_sql(
            "SELECT id, userid, duration, timecreated, timefinished, hide
             FROM {abessi_tracking}
             WHERE userid = ?
             LIMIT 1",
            [$studentid]
        );
        
        if ($tracking) {
            $sample = reset($tracking);
            $dbFields['abessi_tracking'] = [
                'duration' => isset($sample->duration) ? 'exists' : 'missing',
                'timecreated' => isset($sample->timecreated) ? 'exists' : 'missing',
                'timefinished' => isset($sample->timefinished) ? 'exists' : 'missing',
            ];
        }
        
        // alt42_calmness 조회
        $calmness = $DB->get_records_sql(
            "SELECT id, userid, level, timecreated
             FROM {alt42_calmness}
             WHERE userid = ?
             LIMIT 1",
            [$studentid]
        );
        
        if ($calmness) {
            $sample = reset($calmness);
            $dbFields['alt42_calmness'] = [
                'level' => isset($sample->level) ? 'exists' : 'missing',
                'timecreated' => isset($sample->timecreated) ? 'exists' : 'missing',
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error in checkDatabaseFields: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $dbFields;
}

// data_access.php에서 사용하는 필드 확인
function checkDataAccessFields() {
    $dataAccessFile = __DIR__ . '/data_access.php';
    if (!file_exists($dataAccessFile)) {
        return [];
    }
    
    $content = file_get_contents($dataAccessFile);
    $fields = [];
    
    // SELECT 문에서 필드 추출
    preg_match_all('/SELECT\s+([^F]+)\s+FROM/i', $content, $matches);
    if (!empty($matches[1])) {
        $fieldList = $matches[1][0];
        $fieldArray = array_map('trim', explode(',', $fieldList));
        foreach ($fieldArray as $field) {
            $field = trim($field);
            if (!empty($field)) {
                $fields[] = $field;
            }
        }
    }
    
    return $fields;
}

// 필드 타입 분류 (rules.yaml 기반)
function classifyFieldType($fieldName, $rulesFields) {
    // rules.yaml에서 필드 사용 여부 확인
    $inRules = in_array($fieldName, $rulesFields);
    
    // 필드명 패턴으로 타입 추정
    $type = 'unknown';
    
    // System Data: DB에서 직접 가져오는 원시 데이터
    if (preg_match('/^(nstroke|tlaststroke|timecreated|usedtime|contentstitle|url|duration|level|timemodified|student_check|turn|hide|contentstype)$/', $fieldName)) {
        $type = 'sysdata';
    }
    // Survey Data: 사용자 입력/설문 데이터
    elseif (preg_match('/^(teacher_|baseline_|error_type|wrong_answer|student_survey|teacher_checklist|teacher_text_input|student_math_level|student_math_confidence|student_learning_style)$/', $fieldName)) {
        $type = 'survdata';
    }
    // Hybrid Data: 계산/조합된 데이터
    elseif (preg_match('/^(student_|unit_|stage_|concept_|average_|total_|completeness|connection|pattern|frequency|ratio|efficiency|optimal_|review_|revisit_|stroke_per_|dwell_time_|eraser_count_|stroke_order_|stroke_position_)/', $fieldName)) {
        $type = 'hybriddata';
    }
    // Generated Data: AI/LLM 생성 데이터
    elseif (preg_match('/^(generate|analysis|feedback|recommend|identify|evaluate|create|schedule|select|boost_mode|select_feedback|generate_feedback|generate_recommendation|display_message)$/', $fieldName)) {
        $type = 'gendata';
    }
    // 복합 필드 (조건부)
    elseif (preg_match('/_(low|high|medium|recent|old|available|complete|detected|needed)$/', $fieldName)) {
        // 하위/상위/최근/오래된 등의 수식어가 붙은 경우 hybriddata로 분류
        $type = 'hybriddata';
    }
    
    return [
        'type' => $type,
        'in_rules' => $inRules,
        'field_name' => $fieldName
    ];
}

// 메인 분석 실행
$rulesFields = extractRulesYamlFields();
$dbFields = checkDatabaseFields($studentid);
$dataAccessFields = checkDataAccessFields();

// 필드별 상세 분석
$fieldAnalysis = [];

// rules.yaml 필드 분석
foreach ($rulesFields as $field) {
    $analysis = classifyFieldType($field, $rulesFields);
    $analysis['in_data_access'] = in_array($field, $dataAccessFields);
    
    // DB 존재 여부 확인 (정확한 필드명 매칭 + 유사 필드명 매칭)
    $inDb = false;
    $dbTable = '';
    $mappingNote = '';
    
    // 정확한 매칭 먼저 확인
    foreach ($dbFields as $table => $fields) {
        if (isset($fields[$field]) && $fields[$field] === 'exists') {
            $inDb = true;
            $dbTable = $table;
            break;
        }
    }
    
    // 정확한 매칭이 없으면 유사 필드명 확인 (예: nstroke_low -> nstroke)
    if (!$inDb) {
        $baseField = preg_replace('/_(low|high|medium|recent|old|available|complete|detected|needed)$/', '', $field);
        foreach ($dbFields as $table => $fields) {
            if (isset($fields[$baseField]) && $fields[$baseField] === 'exists') {
                $inDb = true;
                $dbTable = $table;
                $mappingNote = "유사 필드: $baseField";
                break;
            }
        }
    }
    
    $analysis['in_db'] = $inDb;
    $analysis['db_table'] = $dbTable;
    $analysis['mapping_note'] = $mappingNote;
    
    $fieldAnalysis[$field] = $analysis;
}

// DB에 있지만 rules.yaml에 없는 필드 찾기
$dbOnlyFields = [];
foreach ($dbFields as $table => $fields) {
    foreach ($fields as $field => $status) {
        if ($status === 'exists' && !in_array($field, $rulesFields)) {
            $dbOnlyFields[] = [
                'field' => $field,
                'table' => $table,
                'in_data_access' => in_array($field, $dataAccessFields)
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 10 데이터 분석 도구</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-sysdata {
            background: #3498db;
            color: white;
        }
        
        .badge-survdata {
            background: #e74c3c;
            color: white;
        }
        
        .badge-gendata {
            background: #9b59b6;
            color: white;
        }
        
        .badge-hybriddata {
            background: #f39c12;
            color: white;
        }
        
        .badge-yes {
            background: #27ae60;
            color: white;
        }
        
        .badge-no {
            background: #e74c3c;
            color: white;
        }
        
        .badge-unknown {
            background: #95a5a6;
            color: white;
        }
        
        .flow-diagram {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            color: white;
            margin-bottom: 30px;
        }
        
        .flow-step {
            text-align: center;
            flex: 1;
        }
        
        .flow-step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .flow-arrow {
            font-size: 24px;
            color: white;
        }
        
        .priority-box {
            display: flex;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .priority-item {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        
        .priority-number {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        
        .priority-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Agent 10 - Concept Notes 데이터 분석 도구</h1>
        <p class="subtitle">rules.yaml 필드와 실제 DB 데이터 비교 분석 | 학생 ID: <?php echo htmlspecialchars($studentid); ?></p>
        
        <!-- 전체 데이터 플로우 -->
        <div class="section">
            <h2 class="section-title">1️⃣ 전체 데이터 플로우 (Main Data Flow)</h2>
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                메타데이터가 전체 시스템을 구동하며, 데이터는 sysdata → survdata → hybriddata → gendata → merge 순서로 흐릅니다.
            </p>
            <div class="flow-diagram">
                <div class="flow-step">
                    <div class="flow-step-circle">SYS</div>
                    <div>System Data</div>
                </div>
                <div class="flow-arrow">→</div>
                <div class="flow-step">
                    <div class="flow-step-circle">SURV</div>
                    <div>Survey Data</div>
                </div>
                <div class="flow-arrow">→</div>
                <div class="flow-step">
                    <div class="flow-step-circle">HYB</div>
                    <div>Hybrid Data</div>
                </div>
                <div class="flow-arrow">→</div>
                <div class="flow-step">
                    <div class="flow-step-circle">GEN</div>
                    <div>Generated Data</div>
                </div>
                <div class="flow-arrow">→</div>
                <div class="flow-step">
                    <div class="flow-step-circle">MERGE</div>
                    <div>Final Context</div>
                </div>
            </div>
        </div>
        
        <!-- 요약 카드 -->
        <div class="summary-cards">
            <div class="card">
                <div class="card-title">Rules.yaml 필드 수</div>
                <div class="card-value"><?php echo count($rulesFields); ?></div>
            </div>
            <div class="card">
                <div class="card-title">DB 존재 필드</div>
                <div class="card-value"><?php echo count(array_filter($fieldAnalysis, function($f) { return $f['in_db']; })); ?></div>
            </div>
            <div class="card">
                <div class="card-title">data_access.php 적용</div>
                <div class="card-value"><?php echo count(array_filter($fieldAnalysis, function($f) { return $f['in_data_access']; })); ?></div>
            </div>
            <div class="card">
                <div class="card-title">DB만 존재</div>
                <div class="card-value"><?php echo count($dbOnlyFields); ?></div>
            </div>
        </div>
        
        <!-- 데이터 타입별 우선순위 -->
        <div class="section">
            <h2 class="section-title">2️⃣ 데이터 타입별 우선순위 (Data Priority)</h2>
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                데이터 병합 시 우선순위: <strong>Override > GenData > HybridData > SurvData > SysData</strong>
            </p>
            <div class="priority-box">
                <div class="priority-item">
                    <div class="priority-number">1</div>
                    <div class="priority-label">Teacher Override</div>
                </div>
                <div class="priority-item">
                    <div class="priority-number">2</div>
                    <div class="priority-label">Generated Data</div>
                </div>
                <div class="priority-item">
                    <div class="priority-number">3</div>
                    <div class="priority-label">Hybrid Data</div>
                </div>
                <div class="priority-item">
                    <div class="priority-number">4</div>
                    <div class="priority-label">Survey Data</div>
                </div>
                <div class="priority-item">
                    <div class="priority-number">5</div>
                    <div class="priority-label">System Data</div>
                </div>
            </div>
        </div>
        
        <!-- Rules.yaml 필드 상세 분석 -->
        <div class="section">
            <h2 class="section-title">3️⃣ Rules.yaml 필드 상세 분석</h2>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>데이터 타입</th>
                        <th>DB 존재</th>
                        <th>DB 테이블</th>
                        <th>매핑 정보</th>
                        <th>data_access.php 적용</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fieldAnalysis as $field => $analysis): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($field); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo $analysis['type']; ?>">
                                <?php echo strtoupper($analysis['type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($analysis['in_db']): ?>
                                <span class="badge badge-yes">YES</span>
                            <?php else: ?>
                                <span class="badge badge-no">NO</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($analysis['db_table'] ?: '-'); ?></td>
                        <td>
                            <?php if (!empty($analysis['mapping_note'])): ?>
                                <span style="color: #f39c12; font-size: 11px;"><?php echo htmlspecialchars($analysis['mapping_note']); ?></span>
                            <?php else: ?>
                                <span style="color: #95a5a6;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($analysis['in_data_access']): ?>
                                <span class="badge badge-yes">적용됨</span>
                            <?php else: ?>
                                <span class="badge badge-no">미적용</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if (!$analysis['in_db'] && !$analysis['in_data_access']) {
                                echo '<span style="color: #e74c3c;">⚠️ DB 없음 + 미적용</span>';
                            } elseif ($analysis['in_db'] && !$analysis['in_data_access']) {
                                echo '<span style="color: #f39c12;">⚠️ DB 있음 + 미적용</span>';
                            } elseif ($analysis['in_db'] && $analysis['in_data_access']) {
                                echo '<span style="color: #27ae60;">✓ 정상</span>';
                            } else {
                                echo '<span style="color: #95a5a6;">-</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- DB에만 존재하는 필드 -->
        <?php if (!empty($dbOnlyFields)): ?>
        <div class="section">
            <h2 class="section-title">4️⃣ DB에 있지만 Rules.yaml에 없는 필드</h2>
            <p style="margin-bottom: 20px; color: #e74c3c;">
                ⚠️ 다음 필드들은 DB에 존재하지만 rules.yaml에서 사용하지 않습니다.
            </p>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>DB 테이블</th>
                        <th>data_access.php 적용</th>
                        <th>권장 조치</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dbOnlyFields as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['table']); ?></td>
                        <td>
                            <?php if ($item['in_data_access']): ?>
                                <span class="badge badge-yes">적용됨</span>
                            <?php else: ?>
                                <span class="badge badge-no">미적용</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$item['in_data_access']): ?>
                                <span style="color: #e74c3c;">rules.yaml에 추가 검토 필요</span>
                            <?php else: ?>
                                <span style="color: #f39c12;">rules.yaml에 추가 고려</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- data_access.php 필드 목록 -->
        <div class="section">
            <h2 class="section-title">5️⃣ data_access.php에서 사용하는 필드</h2>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml 사용</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataAccessFields as $field): ?>
                    <?php
                    $inRules = in_array($field, $rulesFields);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($field); ?></strong></td>
                        <td>
                            <?php if ($inRules): ?>
                                <span class="badge badge-yes">사용</span>
                            <?php else: ?>
                                <span class="badge badge-no">미사용</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$inRules): ?>
                                <span style="color: #e74c3c;">⚠️ rules.yaml에 추가 검토 필요</span>
                            <?php else: ?>
                                <span style="color: #27ae60;">✓ 정상</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 통계 요약 -->
        <div class="section">
            <h2 class="section-title">6️⃣ 통계 요약</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;">데이터 타입별 분포</h3>
                    <?php
                    $typeCounts = [];
                    foreach ($fieldAnalysis as $field => $analysis) {
                        $type = $analysis['type'];
                        $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                    }
                    foreach ($typeCounts as $type => $count):
                    ?>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><strong><?php echo strtoupper($type); ?></strong></span>
                            <span><?php echo $count; ?>개</span>
                        </div>
                        <div style="height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo ($count / count($fieldAnalysis)) * 100; ?>%; background: #3498db;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div>
                    <h3 style="margin-bottom: 15px; color: #2c3e50;">적용 상태</h3>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><strong>DB 존재 + data_access 적용</strong></span>
                            <span><?php echo count(array_filter($fieldAnalysis, function($f) { return $f['in_db'] && $f['in_data_access']; })); ?>개</span>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><strong>DB 존재 + data_access 미적용</strong></span>
                            <span style="color: #e74c3c;"><?php echo count(array_filter($fieldAnalysis, function($f) { return $f['in_db'] && !$f['in_data_access']; })); ?>개</span>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><strong>DB 없음</strong></span>
                            <span style="color: #e74c3c;"><?php echo count(array_filter($fieldAnalysis, function($f) { return !$f['in_db']; })); ?>개</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

