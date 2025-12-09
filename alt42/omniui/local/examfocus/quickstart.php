<?php
/**
 * ExamFocus 빠른 시작 페이지
 * 최소 코드로 바로 작동하는 버전
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 세션 시작
session_start();
$userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2;

// 현재 시간 기준 테스트 데이터
$test_mode = isset($_GET['test']) ? $_GET['test'] : 'D30'; // D7, D30, none

// 테스트 모드에 따른 가상 시험일 설정
switch($test_mode) {
    case 'D7':
        $exam_date = date('Y-m-d', strtotime('+7 days'));
        $days_until = 7;
        break;
    case 'D30':
        $exam_date = date('Y-m-d', strtotime('+30 days'));
        $days_until = 30;
        break;
    default:
        $exam_date = null;
        $days_until = null;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus 빠른 시작</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .main-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .examfocus-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        .recommendation-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .recommendation-box.urgent {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .recommendation-box.normal {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        .mode-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            transition: all 0.3s;
            cursor: pointer;
        }
        .mode-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .mode-card.recommended {
            border-color: #f5576c;
            background: #fff5f5;
        }
        .test-controls {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- 헤더 -->
        <div class="text-center text-white mb-4">
            <h1 class="display-4">📚 ExamFocus</h1>
            <p class="lead">시험 대비 자동 학습 모드 추천 시스템</p>
        </div>
        
        <!-- 테스트 컨트롤 -->
        <div class="examfocus-card">
            <div class="test-controls">
                <h5>🧪 테스트 모드</h5>
                <p class="text-muted mb-3">다양한 시나리오를 테스트해보세요</p>
                <div class="btn-group" role="group">
                    <a href="?test=D7" class="btn <?php echo $test_mode == 'D7' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        D-7 시뮬레이션
                    </a>
                    <a href="?test=D30" class="btn <?php echo $test_mode == 'D30' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        D-30 시뮬레이션
                    </a>
                    <a href="?test=none" class="btn <?php echo $test_mode == 'none' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        시험 없음
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 메인 카드 -->
        <div class="examfocus-card">
            <?php if ($days_until !== null): ?>
                <?php
                // 추천 로직
                if ($days_until <= 7) {
                    $recommendation = [
                        'mode' => 'concept_summary',
                        'title' => '🚨 긴급! 개념요약 집중 모드',
                        'message' => '시험이 코앞입니다! 지금은 개념요약과 대표유형에 집중할 시간입니다.',
                        'class' => 'urgent',
                        'actions' => [
                            '핵심 개념 정리',
                            '대표 유형 문제 풀이',
                            '오답 노트 최종 점검'
                        ]
                    ];
                } else {
                    $recommendation = [
                        'mode' => 'review_errors',
                        'title' => '📚 오답 회독 모드 추천',
                        'message' => '시험 준비의 황금 시기입니다. 오답을 체계적으로 복습하세요.',
                        'class' => 'normal',
                        'actions' => [
                            '오답 문제 다시 풀기',
                            '취약 단원 집중 학습',
                            '실전 문제 연습'
                        ]
                    ];
                }
                ?>
                
                <!-- 추천 박스 -->
                <div class="recommendation-box <?php echo $recommendation['class']; ?> pulse">
                    <h3><?php echo $recommendation['title']; ?></h3>
                    <hr class="bg-white">
                    <p class="mb-3"><?php echo $recommendation['message']; ?></p>
                    <div class="row">
                        <div class="col-6">
                            <strong>📅 시험까지</strong><br>
                            <span class="display-6">D-<?php echo $days_until; ?></span>
                        </div>
                        <div class="col-6">
                            <strong>📆 시험일</strong><br>
                            <span class="h4"><?php echo $exam_date; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- 추천 액션 -->
                <h5 class="mt-4">✅ 추천 학습 활동</h5>
                <ul class="list-group mb-4">
                    <?php foreach ($recommendation['actions'] as $action): ?>
                    <li class="list-group-item">
                        <input class="form-check-input me-2" type="checkbox">
                        <?php echo $action; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- 액션 버튼 -->
                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-lg" onclick="applyMode('<?php echo $recommendation['mode']; ?>')">
                        ✨ 추천 모드로 학습 시작
                    </button>
                    <button class="btn btn-outline-secondary" onclick="dismiss()">
                        나중에 결정
                    </button>
                </div>
                
            <?php else: ?>
                <!-- 시험 없음 -->
                <div class="text-center py-5">
                    <h2 class="mb-3">📖 일반 학습 모드</h2>
                    <p class="text-muted">현재 예정된 시험이 없습니다.</p>
                    <p>시험 일정이 등록되면 자동으로 학습 모드를 추천해 드립니다.</p>
                    <button class="btn btn-primary mt-3" onclick="registerExam()">
                        시험 일정 등록하기
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 학습 모드 선택 -->
        <div class="examfocus-card">
            <h4 class="mb-3">📚 학습 모드 선택</h4>
            
            <div class="mode-card <?php echo ($days_until <= 7) ? 'recommended' : ''; ?>">
                <h5>⚡ 개념요약 모드</h5>
                <p class="text-muted mb-0">핵심 개념 정리와 대표 유형 집중 (D-7 추천)</p>
            </div>
            
            <div class="mode-card <?php echo ($days_until > 7 && $days_until <= 30) ? 'recommended' : ''; ?>">
                <h5>🔄 오답 회독 모드</h5>
                <p class="text-muted mb-0">틀린 문제 복습과 취약점 보완 (D-30 추천)</p>
            </div>
            
            <div class="mode-card">
                <h5>📝 실전 연습 모드</h5>
                <p class="text-muted mb-0">실제 시험과 동일한 환경에서 연습</p>
            </div>
            
            <div class="mode-card">
                <h5>📖 일반 학습 모드</h5>
                <p class="text-muted mb-0">진도에 따른 규칙적인 학습</p>
            </div>
        </div>
        
        <!-- 푸터 링크 -->
        <div class="text-center text-white mt-4">
            <p>
                <a href="index_safe.php" class="text-white">안전 모드</a> |
                <a href="error_check.php" class="text-white">시스템 진단</a> |
                <a href="ajax/get_recommendation.php?user_id=<?php echo $userid; ?>" class="text-white">API</a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyMode(mode) {
            alert('🎉 ' + mode + ' 모드로 학습을 시작합니다!');
            // 실제 구현 시 페이지 이동 또는 AJAX 처리
        }
        
        function dismiss() {
            document.querySelector('.recommendation-box').style.display = 'none';
            sessionStorage.setItem('examfocus_dismissed', Date.now());
        }
        
        function registerExam() {
            alert('시험 일정 등록 페이지로 이동합니다.');
            // window.location.href = 'register_exam.php';
        }
        
        // 모드 카드 클릭 이벤트
        document.querySelectorAll('.mode-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.mode-card').forEach(c => c.style.background = '');
                this.style.background = '#f0f8ff';
            });
        });
    </script>
</body>
</html>