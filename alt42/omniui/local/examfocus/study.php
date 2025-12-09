<?php
/**
 * ExamFocus 일반 학습 모드 페이지
 * 규칙적인 일상 학습 모드
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
    $stmt->execute([$userid, 'examfocus_regular_study', time()]);
    
} catch (Exception $e) {
    $username = '사용자';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus - 일반 학습 모드</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .study-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .study-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .daily-goal {
            background: #e8f5e8;
            border-left: 5px solid #28a745;
            padding: 20px;
            margin: 15px 0;
            border-radius: 0 10px 10px 0;
        }
        .progress-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .progress-item:last-child {
            border-bottom: none;
        }
        .study-session {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .study-session:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .study-session.completed {
            background: #d4edda;
            border-color: #28a745;
        }
        .calendar-widget {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="text-center text-white mb-4">
            <h1><i class="fas fa-book-open"></i> 일반 학습 모드</h1>
            <p class="lead">📚 <?php echo htmlspecialchars($username); ?>님의 꾸준한 학습</p>
        </div>
        
        <!-- 모드 설명 -->
        <div class="study-card">
            <div class="study-header">
                <h2>🌱 꾸준함이 실력입니다</h2>
                <p class="mb-0">매일 조금씩, 꾸준히 학습하여 실력을 쌓아가세요</p>
            </div>
        </div>
        
        <!-- 오늘의 목표 -->
        <div class="daily-goal">
            <h4><i class="fas fa-target"></i> 오늘의 학습 목표</h4>
            <div class="row mt-3">
                <div class="col-md-4 text-center">
                    <h3 class="text-primary">2시간</h3>
                    <small>목표 학습 시간</small>
                </div>
                <div class="col-md-4 text-center">
                    <h3 class="text-success">15문제</h3>
                    <small>목표 문제 수</small>
                </div>
                <div class="col-md-4 text-center">
                    <h3 class="text-warning">3개</h3>
                    <small>목표 개념 학습</small>
                </div>
            </div>
            <div class="progress mt-3">
                <div class="progress-bar bg-success" style="width: 65%"></div>
            </div>
            <div class="text-center mt-2">
                <small>전체 진행률: 65% (1시간 18분 / 10문제 완료)</small>
            </div>
        </div>
        
        <!-- 학습 세션 -->
        <div class="study-card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-clipboard-list"></i> 오늘의 학습 계획</h4>
            </div>
            <div class="card-body">
                <div class="study-session completed" onclick="openStudySession(1)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-check-circle text-success"></i> 이차함수 기본 개념</h6>
                            <small class="text-muted">30분 • 5문제</small>
                        </div>
                        <span class="badge bg-success">완료</span>
                    </div>
                </div>
                
                <div class="study-session completed" onclick="openStudySession(2)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-check-circle text-success"></i> 이차함수 그래프 그리기</h6>
                            <small class="text-muted">25분 • 5문제</small>
                        </div>
                        <span class="badge bg-success">완료</span>
                    </div>
                </div>
                
                <div class="study-session" onclick="openStudySession(3)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="far fa-circle text-muted"></i> 이차함수 최댓값/최솟값</h6>
                            <small class="text-muted">40분 • 7문제</small>
                        </div>
                        <span class="badge bg-primary">진행중</span>
                    </div>
                </div>
                
                <div class="study-session" onclick="openStudySession(4)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="far fa-circle text-muted"></i> 이차함수 활용 문제</h6>
                            <small class="text-muted">45분 • 8문제</small>
                        </div>
                        <span class="badge bg-secondary">대기</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 주간 학습 현황 -->
        <div class="study-card">
            <div class="card-header bg-info text-white">
                <h4><i class="fas fa-chart-line"></i> 주간 학습 현황</h4>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>월</h6>
                            <div class="text-success fs-4">✓</div>
                            <small>2시간</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>화</h6>
                            <div class="text-success fs-4">✓</div>
                            <small>1.5시간</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>수</h6>
                            <div class="text-primary fs-4">●</div>
                            <small>진행중</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>목</h6>
                            <div class="text-muted fs-4">○</div>
                            <small>예정</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>금</h6>
                            <div class="text-muted fs-4">○</div>
                            <small>예정</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>토</h6>
                            <div class="text-muted fs-4">○</div>
                            <small>예정</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="calendar-widget">
                            <h6>일</h6>
                            <div class="text-muted fs-4">○</div>
                            <small>예정</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>이번 주 성과</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">총 학습 시간</small>
                            <h4 class="text-primary">4시간 30분</h4>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">해결한 문제</small>
                            <h4 class="text-success">23문제</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 학습 통계 -->
        <div class="study-card">
            <div class="card-header bg-warning text-dark">
                <h4><i class="fas fa-trophy"></i> 학습 성과</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h3 class="text-primary">27일</h3>
                        <small>연속 학습</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-success">89%</h3>
                        <small>정답률</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-warning">156</h3>
                        <small>누적 문제</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-info">42h</h3>
                        <small>총 학습 시간</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 액션 버튼 -->
        <div class="row">
            <div class="col-md-3">
                <button class="btn btn-success btn-lg w-100 mb-2" onclick="continueStudy()">
                    <i class="fas fa-play"></i> 학습 계속하기
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-lg w-100 mb-2" onclick="setDailyGoal()">
                    <i class="fas fa-bullseye"></i> 목표 설정
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-info btn-lg w-100 mb-2" onclick="viewStatistics()">
                    <i class="fas fa-chart-bar"></i> 통계 보기
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary btn-lg w-100 mb-2" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> 돌아가기
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 학습 세션 열기
        function openStudySession(sessionId) {
            const sessions = {
                1: '이차함수 기본 개념 학습을 시작합니다.',
                2: '이차함수 그래프 그리기 학습을 시작합니다.',
                3: '이차함수 최댓값/최솟값 학습을 시작합니다.',
                4: '이차함수 활용 문제 학습을 시작합니다.'
            };
            
            if (sessionId <= 2) {
                alert('✅ 이미 완료된 세션입니다. 복습하시겠습니까?');
            } else {
                alert(`📚 ${sessions[sessionId]}`);
                // 실제 구현에서는 해당 학습 페이지로 이동
                // window.location.href = `lesson.php?session=${sessionId}&user_id=<?php echo $userid; ?>`;
            }
        }
        
        // 학습 계속하기
        function continueStudy() {
            alert('📖 현재 진행중인 학습을 계속합니다!');
            openStudySession(3);
        }
        
        // 일일 목표 설정
        function setDailyGoal() {
            const studyTime = prompt('일일 목표 학습 시간을 설정하세요 (분 단위):', '120');
            const problemCount = prompt('일일 목표 문제 수를 설정하세요:', '15');
            
            if (studyTime && problemCount) {
                alert(`🎯 새로운 일일 목표가 설정되었습니다!\n학습 시간: ${studyTime}분\n문제 수: ${problemCount}문제`);
                
                // 실제 구현에서는 서버에 저장
                // localStorage.setItem('dailyGoal', JSON.stringify({studyTime, problemCount}));
            }
        }
        
        // 상세 통계 보기
        function viewStatistics() {
            alert('📊 상세한 학습 통계 페이지로 이동합니다.');
            // 실제 구현에서는 통계 페이지로 이동
            // window.location.href = `statistics.php?user_id=<?php echo $userid; ?>`;
        }
        
        // 돌아가기
        function goBack() {
            window.location.href = 'quickstart_real.php?user_id=<?php echo $userid; ?>';
        }
        
        // 자동 저장 기능
        setInterval(function() {
            // 학습 진행 상황을 자동으로 저장
            const progress = {
                date: new Date().toISOString().split('T')[0],
                completedSessions: document.querySelectorAll('.study-session.completed').length,
                totalSessions: document.querySelectorAll('.study-session').length
            };
            
            // 실제 구현에서는 서버로 전송
            console.log('학습 진행 상황 저장:', progress);
        }, 60000); // 1분마다 저장
        
        // 학습 격려 메시지
        function showMotivationalMessage() {
            const messages = [
                "🌟 꾸준한 학습이 성공의 열쇠입니다!",
                "💪 오늘도 한 걸음 더 성장했네요!",
                "🎯 목표를 향해 차근차근 나아가고 있어요!",
                "📚 지금의 노력이 미래의 성과가 됩니다!"
            ];
            
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            
            // 2시간마다 격려 메시지 표시
            setTimeout(() => {
                alert(randomMessage);
            }, 7200000);
        }
        
        // 페이지 로드 시 격려 메시지 스케줄링
        document.addEventListener('DOMContentLoaded', function() {
            showMotivationalMessage();
        });
    </script>
</body>
</html>