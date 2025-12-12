<?php
/**
 * Teacher Persona Engine Test Page
 *
 * TeacherPersonaEngine 테스트 페이지
 * 페르소나 선택, 피드백 생성, 매칭 테스트
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/api/test.php
 *
 * @package AugmentedTeacher\Agent06\API
 * @version 1.0
 * @author Claude Code
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$currentFile = __FILE__;

// 관리자 권한 확인
$context = context_system::instance();
$isAdmin = has_capability('moodle/site:config', $context);

// 사용자 정보
$userInfo = [
    'id' => $USER->id,
    'name' => fullname($USER),
    'email' => $USER->email
];

// 테스트 실행
$testResults = [];
$testMessage = $_POST['test_message'] ?? '';
$testAction = $_POST['test_action'] ?? '';

if ($testAction === 'process' && !empty($testMessage)) {
    try {
        require_once(__DIR__ . '/../engine/TeacherPersonaEngine.php');
        $engine = new \AugmentedTeacher\Agent06\Engine\TeacherPersonaEngine(true);

        $startTime = microtime(true);
        $result = $engine->process($USER->id, $testMessage, []);
        $endTime = microtime(true);

        $testResults = [
            'success' => $result['success'] ?? false,
            'response' => $result['response'] ?? '',
            'persona_id' => $result['persona_id'] ?? 'unknown',
            'situation' => $result['situation'] ?? 'T0',
            'student_persona' => $result['student_persona'] ?? null,
            'processing_time' => round(($endTime - $startTime) * 1000, 2) . 'ms'
        ];
    } catch (Exception $e) {
        $testResults = [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $currentFile,
            'line' => $e->getLine()
        ];
    }
}

// 템플릿 테스트
$templateResult = null;
if ($testAction === 'template') {
    require_once(__DIR__ . '/../templates/teacher_templates.php');

    $personaId = $_POST['persona_id'] ?? 'T0_P2';
    $category = $_POST['category'] ?? 'greeting';
    $studentName = $_POST['student_name'] ?? '학생';

    $templateResult = \AugmentedTeacher\Agent06\Templates\TeacherTemplates::getRandomTemplate(
        $personaId,
        $category,
        ['student_name' => $studentName]
    );
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Persona Engine Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
        }
        .info-item label {
            display: block;
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-item span {
            font-weight: 600;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #444;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .result-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .result-box.success {
            border-left: 4px solid #28a745;
        }
        .result-box.error {
            border-left: 4px solid #dc3545;
        }
        .result-item {
            margin-bottom: 10px;
        }
        .result-item strong {
            color: #333;
        }
        .response-text {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-top: 10px;
            font-size: 1.1em;
            line-height: 1.6;
        }
        .api-list {
            display: grid;
            gap: 10px;
        }
        .api-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
        .api-item code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warn { background: #fff3cd; color: #856404; }
        .flex-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .quick-test {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .quick-test button {
            padding: 8px 16px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Teacher Persona Engine Test</h1>

        <!-- 사용자 정보 -->
        <div class="card">
            <h2>👤 사용자 정보</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>사용자 ID</label>
                    <span><?php echo $userInfo['id']; ?></span>
                </div>
                <div class="info-item">
                    <label>이름</label>
                    <span><?php echo htmlspecialchars($userInfo['name']); ?></span>
                </div>
                <div class="info-item">
                    <label>권한</label>
                    <span class="status-badge <?php echo $isAdmin ? 'status-ok' : 'status-warn'; ?>">
                        <?php echo $isAdmin ? '관리자' : '일반 사용자'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- 피드백 테스트 -->
        <div class="card">
            <h2>💬 피드백 생성 테스트</h2>
            <form method="POST">
                <input type="hidden" name="test_action" value="process">
                <div class="form-group">
                    <label>학생 메시지</label>
                    <textarea name="test_message" placeholder="학생이 보낸 메시지를 입력하세요..."><?php echo htmlspecialchars($testMessage); ?></textarea>
                </div>
                <div class="flex-row">
                    <button type="submit" class="btn btn-primary">🚀 피드백 생성</button>
                </div>
                <div class="quick-test">
                    <strong>빠른 테스트:</strong>
                    <button type="submit" name="test_message" value="안녕하세요! 수학 공부하러 왔어요." class="btn btn-secondary">일반 인사</button>
                    <button type="submit" name="test_message" value="이 문제 너무 어려워요... 못하겠어요 ㅠㅠ" class="btn btn-secondary">좌절감</button>
                    <button type="submit" name="test_message" value="이 문제 풀었는데 맞는지 모르겠어요." class="btn btn-secondary">확인 요청</button>
                    <button type="submit" name="test_message" value="공부하기 너무 싫어요. 힘들어요." class="btn btn-secondary">번아웃</button>
                </div>
            </form>

            <?php if (!empty($testResults)): ?>
                <div class="result-box <?php echo $testResults['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo $testResults['success'] ? '✅ 성공' : '❌ 실패'; ?></h3>

                    <?php if ($testResults['success']): ?>
                        <div class="result-item">
                            <strong>선택된 페르소나:</strong> <?php echo htmlspecialchars($testResults['persona_id']); ?>
                        </div>
                        <div class="result-item">
                            <strong>상황 유형:</strong> <?php echo htmlspecialchars($testResults['situation']); ?>
                        </div>
                        <?php if ($testResults['student_persona']): ?>
                            <div class="result-item">
                                <strong>학생 페르소나:</strong> <?php echo htmlspecialchars(json_encode($testResults['student_persona'], JSON_UNESCAPED_UNICODE)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="result-item">
                            <strong>처리 시간:</strong> <?php echo $testResults['processing_time']; ?>
                        </div>
                        <div class="result-item">
                            <strong>선생님 응답:</strong>
                            <div class="response-text"><?php echo htmlspecialchars($testResults['response']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="result-item">
                            <strong>에러:</strong> <?php echo htmlspecialchars($testResults['error'] ?? '알 수 없는 오류'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 템플릿 테스트 -->
        <div class="card">
            <h2>📝 템플릿 테스트</h2>
            <form method="POST">
                <input type="hidden" name="test_action" value="template">
                <div class="info-grid">
                    <div class="form-group">
                        <label>페르소나 ID</label>
                        <select name="persona_id">
                            <optgroup label="T0 - 일반 대화">
                                <option value="T0_P1">T0_P1 (친근한 대화형)</option>
                                <option value="T0_P2" selected>T0_P2 (균형잡힌 전문가형)</option>
                                <option value="T0_P3">T0_P3 (체계적 분석가형)</option>
                            </optgroup>
                            <optgroup label="T1 - 격려">
                                <option value="T1_P1">T1_P1 (열정적 동기부여형)</option>
                                <option value="T1_P2">T1_P2 (따뜻한 지지형)</option>
                            </optgroup>
                            <optgroup label="T2 - 교정">
                                <option value="T2_P1">T2_P1 (건설적 피드백형)</option>
                                <option value="T2_P2">T2_P2 (분석적 교정형)</option>
                            </optgroup>
                            <optgroup label="T3 - 학습 설계">
                                <option value="T3_P1">T3_P1 (맞춤형 설계형)</option>
                                <option value="T3_P2">T3_P2 (전략적 코칭형)</option>
                            </optgroup>
                            <optgroup label="T4 - 정서 지원">
                                <option value="T4_P1">T4_P1 (공감적 지지형)</option>
                                <option value="T4_P2">T4_P2 (안정적 상담형)</option>
                            </optgroup>
                            <optgroup label="T5 - 성과 리뷰">
                                <option value="T5_P1">T5_P1 (긍정적 리뷰형)</option>
                                <option value="T5_P2">T5_P2 (데이터 기반 리뷰형)</option>
                            </optgroup>
                            <optgroup label="E - 비상 상황">
                                <option value="E_CRISIS">E_CRISIS (위기 상황)</option>
                                <option value="E_BURNOUT">E_BURNOUT (번아웃)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>카테고리</label>
                        <select name="category">
                            <option value="greeting">인사 (greeting)</option>
                            <option value="encouragement">격려 (encouragement)</option>
                            <option value="response">응답 (response)</option>
                            <option value="praise">칭찬 (praise)</option>
                            <option value="motivation">동기부여 (motivation)</option>
                            <option value="correction">교정 (correction)</option>
                            <option value="guidance">안내 (guidance)</option>
                            <option value="empathy">공감 (empathy)</option>
                            <option value="support">지지 (support)</option>
                            <option value="immediate">즉각 대응 (immediate)</option>
                            <option value="recognition">인식 (recognition)</option>
                            <option value="recovery">회복 (recovery)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>학생 이름</label>
                        <input type="text" name="student_name" value="홍길동" placeholder="학생 이름">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">🎨 템플릿 렌더링</button>
            </form>

            <?php if ($templateResult !== null): ?>
                <div class="result-box success">
                    <h3>📄 렌더링 결과</h3>
                    <div class="response-text"><?php echo htmlspecialchars($templateResult); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- API 엔드포인트 목록 -->
        <div class="card">
            <h2>🔌 API 엔드포인트</h2>
            <div class="api-list">
                <div class="api-item">
                    <strong>피드백 생성</strong><br>
                    <code>POST /api/process.php?action=process</code><br>
                    <small>Body: {"message": "학생 메시지", "user_id": 123}</small>
                </div>
                <div class="api-item">
                    <strong>페르소나 목록</strong><br>
                    <code>GET /api/process.php?action=personas</code>
                </div>
                <div class="api-item">
                    <strong>페르소나 상세</strong><br>
                    <code>GET /api/process.php?action=persona&persona_id=T1_P1</code>
                </div>
                <div class="api-item">
                    <strong>학생-선생님 매칭</strong><br>
                    <code>GET /api/process.php?action=matching&student_persona=S1_P1&situation=T1</code>
                </div>
                <div class="api-item">
                    <strong>상태 조회</strong><br>
                    <code>GET /api/process.php?action=state&user_id=123</code>
                </div>
                <div class="api-item">
                    <strong>API 상태</strong><br>
                    <code>GET /api/process.php?action=health</code>
                </div>
            </div>
        </div>

        <!-- 링크 -->
        <div class="card">
            <h2>🔗 관련 링크</h2>
            <div class="info-grid">
                <a href="process.php?action=health" target="_blank" class="btn btn-secondary">API 상태 확인</a>
                <a href="process.php?action=personas" target="_blank" class="btn btn-secondary">페르소나 목록 (JSON)</a>
                <a href="../" class="btn btn-secondary">Persona System 폴더</a>
                <a href="../../" class="btn btn-secondary">Agent06 폴더</a>
            </div>
        </div>
    </div>

    <script>
        // 빠른 테스트 버튼 처리
        document.querySelectorAll('.quick-test button').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.value) {
                    document.querySelector('textarea[name="test_message"]').value = this.value;
                }
            });
        });
    </script>
</body>
</html>
<?php
/*
 * 관련 DB 테이블:
 * - at_agent_persona_state (페르소나 상태)
 * - mdl_user (사용자 정보)
 *
 * 참조 파일:
 * - engine/TeacherPersonaEngine.php
 * - templates/teacher_templates.php
 * - process.php (API 엔드포인트)
 */
