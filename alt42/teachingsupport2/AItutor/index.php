<?php
/**
 * AI 튜터 시스템 메인 진입점
 * 이미지나 컨텐츠를 입력받아 실제 선생님처럼 라이브한 설명 제공
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    2.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
// config.php가 있으면 로드, 없으면 기본값 사용
if (file_exists(__DIR__ . '/../config.php')) {
    require_once(__DIR__ . '/../config.php');
}
global $DB, $USER;
require_login();

// 에러 출력 설정 (개발 중)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// OpenAI API 키 설정 (teachingagent.php 방식)
$secret_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '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';

// 학생 ID 확인
$studentid = isset($_GET['studentid']) ? $_GET['studentid'] : $USER->id;

// 분석 ID 확인
$analysisId = isset($_GET['id']) ? $_GET['id'] : null;

// 모드 확인 (learn = 학습 인터페이스로 바로 이동)
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'analyze';

// 컨텐츠 정보
$contentId = isset($_GET['contentid']) ? $_GET['contentid'] : '15652';
$contentType = isset($_GET['contenttype']) ? $_GET['contenttype'] : 'topic';

// 학생 정보 확인
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    print_error('학생 정보를 찾을 수 없습니다.');
}

// 학습 모드인 경우 학습 인터페이스로 리다이렉트
if ($mode === 'learn' && $analysisId) {
    $params = http_build_query([
        'id' => $analysisId,
        'studentid' => $studentid,
        'contentid' => $contentId,
        'contenttype' => $contentType
    ]);
    header("Location: ui/learning_interface.php?{$params}");
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 튜터 - 단원 전용 학습 지원</title>
    <link rel="stylesheet" href="ui/unit_tutor.css">
    <style>
        /* 학습 시작 버튼 스타일 */
        .start-learning-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            margin-top: 1.5rem;
        }
        .start-learning-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        .start-learning-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .start-learning-btn .btn-icon {
            font-size: 1.25rem;
        }
        
        /* 분석 완료 카드 */
        .analysis-complete-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .analysis-complete-card h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        .analysis-complete-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* 분석 ID 표시 */
        .analysis-id-display {
            background: rgba(0,0,0,0.2);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        /* 액션 버튼 그룹 */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        
        .secondary-btn {
            padding: 0.75rem 1.5rem;
            background: #374151;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .secondary-btn:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="tutor-container">
        <!-- 헤더 -->
        <header class="tutor-header">
            <h1>🎓 AI 튜터</h1>
            <p class="subtitle">단원 전용 맞춤형 학습 지원</p>
        </header>

        <!-- 메인 컨텐츠 영역 -->
        <main class="tutor-main">
            <!-- 입력 영역 -->
            <section class="input-section" id="input-section">
                <div class="input-card">
                    <h2>📝 학습 내용 입력</h2>
                    
                    <!-- 텍스트 입력 -->
                    <div class="input-group">
                        <label for="content-text">대화 내용 또는 문제 설명</label>
                        <textarea 
                            id="content-text" 
                            class="content-input" 
                            placeholder="선생님과 학생의 대화 내용을 입력하거나, 수학 문제 설명을 입력하세요..."
                            rows="8"
                        ></textarea>
                    </div>

                    <!-- 이미지 업로드 -->
                    <div class="input-group">
                        <label for="content-image">이미지 업로드 (선택사항)</label>
                        <div class="image-upload-area" id="image-upload-area">
                            <input type="file" id="content-image" accept="image/*" style="display: none;">
                            <div class="upload-placeholder">
                                <i class="upload-icon">📷</i>
                                <p>이미지를 클릭하거나 드래그하여 업로드</p>
                            </div>
                            <img id="preview-image" style="display: none; max-width: 100%; max-height: 300px; margin-top: 10px;">
                        </div>
                    </div>

                    <!-- 분석 버튼 -->
                    <button id="analyze-btn" class="analyze-button">
                        <span class="btn-icon">🔍</span>
                        분석 및 튜터링 준비
                    </button>
                </div>
            </section>

            <!-- 결과 영역 -->
            <section class="result-section" id="result-section" style="display: none;">
                <!-- 로딩 표시 -->
                <div id="loading-indicator" class="loading-indicator" style="display: none;">
                    <div class="spinner"></div>
                    <p>AI가 학습 내용을 분석하고 있습니다...</p>
                </div>

                <!-- 분석 완료 카드 (숨김 상태) -->
                <div id="analysis-complete" class="analysis-complete-card" style="display: none;">
                    <h2>✅ 분석 완료!</h2>
                    <p>학습 준비가 완료되었습니다. 아래 버튼을 클릭하여 학습을 시작하세요.</p>
                    <div id="analysis-id-display" class="analysis-id-display"></div>
                    <div class="action-buttons">
                        <button id="start-learning-btn" class="start-learning-btn" disabled>
                            <span class="btn-icon">🚀</span>
                            학습 시작하기
                        </button>
                        <button id="copy-link-btn" class="secondary-btn">
                            🔗 링크 복사
                        </button>
                        <button id="new-analysis-btn" class="secondary-btn">
                            ➕ 새 분석
                        </button>
                    </div>
                </div>

                <!-- 포괄적 질문 -->
                <div id="comprehensive-questions" class="result-card">
                    <h3>📋 포괄적 질문</h3>
                    <div id="comprehensive-questions-content"></div>
                </div>

                <!-- 세부 질문 -->
                <div id="detailed-questions" class="result-card">
                    <h3>❓ 세부 질문</h3>
                    <div id="detailed-questions-content"></div>
                </div>

                <!-- 생성된 룰 -->
                <div id="generated-rules" class="result-card">
                    <h3>⚙️ 교수법 의사결정 룰</h3>
                    <div id="generated-rules-content"></div>
                </div>

                <!-- 생성된 온톨로지 -->
                <div id="generated-ontology" class="result-card">
                    <h3>🔗 학습 맥락 온톨로지</h3>
                    <div id="generated-ontology-content"></div>
                </div>

                <!-- 라이브 튜터링 -->
                <div id="live-tutoring" class="result-card">
                    <h3>💬 라이브 튜터링</h3>
                    <div id="live-tutoring-content" class="tutoring-chat"></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // OpenAI API 키 (teachingagent.php 방식)
        const apikey = '<?php echo $secret_key; ?>';
        const studentId = <?php echo $studentid; ?>;
        const analysisId = <?php echo $analysisId ? json_encode($analysisId) : 'null'; ?>;
        
        // 컨텐츠 정보
        const contentId = '<?php echo $contentId; ?>';
        const contentType = '<?php echo $contentType; ?>';
        
        // 학습 시작 함수
        function startLearning(id) {
            if (!id) return;
            const params = new URLSearchParams({
                id: id,
                studentid: studentId,
                contentid: contentId,
                contenttype: contentType
            });
            window.location.href = 'ui/learning_interface.php?' + params.toString();
        }
        
        // 분석 완료 시 호출
        function onAnalysisComplete(id) {
            const completeCard = document.getElementById('analysis-complete');
            const idDisplay = document.getElementById('analysis-id-display');
            const startBtn = document.getElementById('start-learning-btn');
            
            if (completeCard && idDisplay && startBtn) {
                completeCard.style.display = 'block';
                idDisplay.textContent = 'ID: ' + id;
                startBtn.disabled = false;
                
                // 학습 시작 버튼 이벤트
                startBtn.onclick = function() {
                    startLearning(id);
                };
                
                // 링크 복사 버튼
                document.getElementById('copy-link-btn').onclick = function() {
                    const url = window.location.origin + window.location.pathname + '?id=' + id + '&mode=learn&studentid=' + studentId;
                    navigator.clipboard.writeText(url).then(function() {
                        alert('링크가 복사되었습니다!');
                    });
                };
                
                // 새 분석 버튼
                document.getElementById('new-analysis-btn').onclick = function() {
                    window.location.href = window.location.pathname + '?studentid=' + studentId;
                };
                
                // URL 업데이트
                const newUrl = window.location.pathname + '?id=' + id + '&studentid=' + studentId;
                window.history.pushState({ analysisId: id }, '', newUrl);
                
                // 스크롤 이동
                completeCard.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // 전역으로 노출
        window.onAnalysisComplete = onAnalysisComplete;
        window.startLearning = startLearning;
    </script>
    <script src="ui/unit_tutor.js"></script>
</body>
</html>
