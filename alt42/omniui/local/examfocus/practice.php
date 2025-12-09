<?php
/**
 * ExamFocus 실전 연습 모드 페이지
 * 모의고사 및 실전 연습 모드
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 세션 시작
session_start();

// 사용자 ID 가져오기
$userid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 
          (isset($_SESSION['examfocus_user_id']) ? $_SESSION['examfocus_user_id'] : null);

if (!$userid) {
    header('Location: quickstart_real.php');
    exit;
}

// 데이터베이스에서 사용자 정보 가져오기
try {
    $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
    $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM mdl_user WHERE id = ? AND deleted = 0");
    $stmt->execute([$userid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: quickstart_real.php');
        exit;
    }
    
    $username = trim($user['firstname'] . ' ' . $user['lastname']);
    
    // 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userid, 'examfocus_practice', time()]);
    
} catch (Exception $e) {
    $username = '사용자';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus - 실전 연습 모드</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .practice-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .practice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .test-item {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .test-item:hover {
            border-color: #f5576c;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .test-timer {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .score-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .difficulty-easy { border-left: 5px solid #28a745; }
        .difficulty-medium { border-left: 5px solid #ffc107; }
        .difficulty-hard { border-left: 5px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="text-center text-white mb-4">
            <h1><i class="fas fa-stopwatch"></i> 실전 연습 모드</h1>
            <p class="lead">⚡ <?php echo htmlspecialchars($username); ?>님의 시험 실전 대비</p>
        </div>
        
        <!-- 모드 설명 -->
        <div class="practice-card">
            <div class="practice-header">
                <h2>🎯 실제 시험과 동일한 환경</h2>
                <p class="mb-0">시간 제한과 긴장감 속에서 실력을 점검하세요</p>
            </div>
        </div>
        
        <!-- 시험 타이머 설정 -->
        <div class="test-timer">
            <h5><i class="fas fa-clock"></i> 시험 시간 설정</h5>
            <div class="row mt-3">
                <div class="col-4">
                    <button class="btn btn-light w-100" onclick="setTimer(30)">30분</button>
                </div>
                <div class="col-4">
                    <button class="btn btn-light w-100" onclick="setTimer(60)">60분</button>
                </div>
                <div class="col-4">
                    <button class="btn btn-light w-100" onclick="setTimer(90)">90분</button>
                </div>
            </div>
            <div class="mt-2" id="selectedTime">시간을 선택하세요</div>
        </div>
        
        <!-- 모의고사 목록 -->
        <div class="practice-card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-file-alt"></i> 모의고사 선택</h4>
            </div>
            <div class="card-body">
                <div class="test-item difficulty-medium" onclick="startTest('mock1')">
                    <div class="position-relative">
                        <div class="score-badge">미응시</div>
                        <h5>2024 수능 모의고사 1회</h5>
                        <p class="text-muted mb-2">고3 수준 • 30문항 • 90분</p>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-chart-line"></i> 난이도: 중간</span>
                            <span><i class="fas fa-users"></i> 응시자: 1,247명</span>
                        </div>
                    </div>
                </div>
                
                <div class="test-item difficulty-hard" onclick="startTest('mock2')">
                    <div class="position-relative">
                        <div class="score-badge">85점</div>
                        <h5>2024 수능 모의고사 2회</h5>
                        <p class="text-muted mb-2">고3 수준 • 30문항 • 90분</p>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-chart-line"></i> 난이도: 어려움</span>
                            <span><i class="fas fa-users"></i> 응시자: 892명</span>
                        </div>
                    </div>
                </div>
                
                <div class="test-item difficulty-easy" onclick="startTest('mock3')">
                    <div class="position-relative">
                        <div class="score-badge">92점</div>
                        <h5>기출문제 종합 1회</h5>
                        <p class="text-muted mb-2">고2-3 수준 • 25문항 • 60분</p>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-chart-line"></i> 난이도: 쉬움</span>
                            <span><i class="fas fa-users"></i> 응시자: 2,156명</span>
                        </div>
                    </div>
                </div>
                
                <div class="test-item difficulty-medium" onclick="startTest('mock4')">
                    <div class="position-relative">
                        <div class="score-badge">미응시</div>
                        <h5>실전 연습 문제집</h5>
                        <p class="text-muted mb-2">고3 수준 • 20문항 • 60분</p>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-chart-line"></i> 난이도: 중간</span>
                            <span><i class="fas fa-users"></i> 응시자: 567명</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 성적 분석 -->
        <div class="practice-card">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-chart-bar"></i> 최근 성적 분석</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h3 class="text-primary">88.5</h3>
                        <small>평균 점수</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-success">상위 15%</h3>
                        <small>전체 순위</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-warning">76분</h3>
                        <small>평균 소요 시간</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-info">+12점</h3>
                        <small>지난 주 대비</small>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>강점 영역</h6>
                    <span class="badge bg-success me-2">미분과 적분</span>
                    <span class="badge bg-success me-2">확률과 통계</span>
                    
                    <h6 class="mt-3">보완 필요 영역</h6>
                    <span class="badge bg-warning me-2">삼각함수</span>
                    <span class="badge bg-warning me-2">수열</span>
                </div>
            </div>
        </div>
        
        <!-- 액션 버튼 -->
        <div class="row">
            <div class="col-md-4">
                <button class="btn btn-success btn-lg w-100 mb-2" onclick="startQuickTest()">
                    <i class="fas fa-bolt"></i> 빠른 테스트
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-warning btn-lg w-100 mb-2" onclick="reviewResults()">
                    <i class="fas fa-chart-line"></i> 성적 분석
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-light btn-lg w-100 mb-2" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> 돌아가기
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedTestTime = 0;
        
        // 시간 설정
        function setTimer(minutes) {
            selectedTestTime = minutes;
            document.getElementById('selectedTime').textContent = `${minutes}분 선택됨`;
            
            // 버튼 스타일 업데이트
            document.querySelectorAll('.test-timer .btn').forEach(btn => {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-light');
            });
            event.target.classList.remove('btn-light');
            event.target.classList.add('btn-success');
        }
        
        // 시험 시작
        function startTest(testId) {
            if (selectedTestTime === 0) {
                alert('먼저 시험 시간을 설정해주세요!');
                return;
            }
            
            if (confirm(`${selectedTestTime}분 동안 실전 모의고사를 진행하시겠습니까?\n\n⚠️ 시험 중에는 페이지를 벗어날 수 없습니다.`)) {
                alert(`🚀 ${selectedTestTime}분 실전 모의고사가 시작됩니다!`);
                
                // 실제 구현에서는 시험 페이지로 이동
                // window.location.href = `test.php?id=${testId}&time=${selectedTestTime}&user_id=<?php echo $userid; ?>`;
                
                // 풀스크린 모드로 전환
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                }
            }
        }
        
        // 빠른 테스트 (10문항, 20분)
        function startQuickTest() {
            if (confirm('빠른 테스트를 시작하시겠습니까?\n10문항 • 20분 제한')) {
                alert('⚡ 빠른 테스트가 시작됩니다!');
                // 실제 구현에서는 빠른 테스트 페이지로 이동
            }
        }
        
        // 성적 분석 보기
        function reviewResults() {
            alert('📊 상세한 성적 분석 페이지로 이동합니다.');
            // 실제 구현에서는 성적 분석 페이지로 이동
            // window.location.href = `analysis.php?user_id=<?php echo $userid; ?>`;
        }
        
        // 돌아가기
        function goBack() {
            window.location.href = 'quickstart_real.php?user_id=<?php echo $userid; ?>';
        }
        
        // 페이지 떠날 때 경고 (시험 중이 아닐 때만)
        let isTestInProgress = false;
        
        window.addEventListener('beforeunload', function(e) {
            if (isTestInProgress) {
                e.preventDefault();
                e.returnValue = '시험이 진행 중입니다. 정말 페이지를 떠나시겠습니까?';
            }
        });
    </script>
</body>
</html>