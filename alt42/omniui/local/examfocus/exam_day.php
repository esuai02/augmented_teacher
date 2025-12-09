<?php
/**
 * ExamFocus 시험 당일 모드 페이지
 * 시험 당일 최종 점검 및 멘탈 관리
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
    $stmt->execute([$userid, 'examfocus_exam_day', time()]);
    
} catch (Exception $e) {
    $username = '사용자';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus - 시험 당일 모드</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .exam-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .exam-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .checklist-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }
        .checklist-item:hover {
            background: #f8f9fa;
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .checklist-item.completed {
            background: #d4edda;
            color: #155724;
        }
        .motivation-card {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            color: #2d3436;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .breathing-guide {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .breathing-circle {
            width: 150px;
            height: 150px;
            border: 3px solid #2196f3;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            transition: transform 4s ease-in-out;
        }
        .breathing-circle.expand {
            transform: scale(1.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="text-center text-white mb-4">
            <h1><i class="fas fa-star"></i> 시험 당일</h1>
            <p class="lead">🎯 <?php echo htmlspecialchars($username); ?>님, 오늘이 그 날입니다!</p>
        </div>
        
        <!-- 격려 메시지 -->
        <div class="exam-card">
            <div class="exam-header">
                <h2>🌟 준비 완료!</h2>
                <p class="mb-0">그동안 열심히 준비한 만큼 좋은 결과가 있을 거예요</p>
            </div>
        </div>
        
        <!-- 시험 준비물 체크리스트 -->
        <div class="exam-card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-check-square"></i> 시험 준비물 최종 점검</h4>
            </div>
            <div class="card-body p-0">
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">신분증 (학생증/신분증)</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">필기구 (연필, 지우개, 펜)</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">계산기 (허용된 경우)</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">시계 (디지털 시계는 확인)</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">물과 간단한 간식</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">교통비 및 여분의 돈</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">시험장 위치 및 교통편 확인</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <i class="fas fa-square-check text-success" style="display:none;"></i>
                    <i class="far fa-square text-muted"></i>
                    <span class="ms-2">충분한 수면 (7-8시간)</span>
                </div>
            </div>
        </div>
        
        <!-- 마음 다잡기 -->
        <div class="motivation-card">
            <h4><i class="fas fa-heart"></i> 마음 다잡기</h4>
            <p class="fs-5 mt-3">"그동안의 노력은 절대 배신하지 않습니다"</p>
            <p class="mb-0">침착하고 자신감 있게 문제에 집중하세요!</p>
        </div>
        
        <!-- 호흡 가이드 -->
        <div class="exam-card">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-leaf"></i> 긴장 완화 호흡법</h4>
            </div>
            <div class="card-body">
                <div class="breathing-guide">
                    <h5>4-7-8 호흡법</h5>
                    <p>긴장될 때 이 호흡법으로 마음을 진정시키세요</p>
                    
                    <div class="breathing-circle" id="breathingCircle">
                        <span id="breathingText">시작</span>
                    </div>
                    
                    <button class="btn btn-primary" onclick="startBreathing()">호흡 가이드 시작</button>
                    <button class="btn btn-secondary" onclick="stopBreathing()">중단</button>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            4초 동안 숨을 들이마시고, 7초 동안 참은 후, 8초 동안 천천히 내쉬세요
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 마지막 개념 점검 -->
        <div class="exam-card">
            <div class="card-header bg-warning text-dark">
                <h4><i class="fas fa-brain"></i> 마지막 개념 점검</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>실수하기 쉬운 부분</h6>
                        <ul class="list-unstyled">
                            <li>• 부호 실수 주의</li>
                            <li>• 계산 검산하기</li>
                            <li>• 단위 확인하기</li>
                            <li>• 범위 조건 체크</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>시험 중 전략</h6>
                        <ul class="list-unstyled">
                            <li>• 쉬운 문제부터 해결</li>
                            <li>• 시간 배분 조절</li>
                            <li>• 막히면 다음 문제로</li>
                            <li>• 검토 시간 확보</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 액션 버튼 -->
        <div class="row">
            <div class="col-md-4">
                <button class="btn btn-success btn-lg w-100 mb-2" onclick="showMotivation()">
                    <i class="fas fa-thumbs-up"></i> 응원 메시지
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-info btn-lg w-100 mb-2" onclick="setReminder()">
                    <i class="fas fa-bell"></i> 출발 알림 설정
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
        let breathingInterval = null;
        let breathingStep = 0;
        
        // 체크리스트 토글
        function toggleCheck(element) {
            const checkedIcon = element.querySelector('.fa-square-check');
            const uncheckedIcon = element.querySelector('.fa-square');
            
            if (checkedIcon.style.display === 'none') {
                // 체크
                checkedIcon.style.display = 'inline';
                uncheckedIcon.style.display = 'none';
                element.classList.add('completed');
            } else {
                // 언체크
                checkedIcon.style.display = 'none';
                uncheckedIcon.style.display = 'inline';
                element.classList.remove('completed');
            }
            
            // 모든 항목이 체크되었는지 확인
            const totalItems = document.querySelectorAll('.checklist-item').length;
            const completedItems = document.querySelectorAll('.checklist-item.completed').length;
            
            if (completedItems === totalItems) {
                setTimeout(() => {
                    alert('🎉 모든 준비가 완료되었습니다! 시험장에서 좋은 결과 있기를 바랍니다!');
                }, 500);
            }
        }
        
        // 호흡 가이드 시작
        function startBreathing() {
            if (breathingInterval) return;
            
            breathingStep = 0;
            const circle = document.getElementById('breathingCircle');
            const text = document.getElementById('breathingText');
            
            breathingInterval = setInterval(() => {
                switch(breathingStep) {
                    case 0: // 들이마시기 4초
                        text.textContent = '들이마시기';
                        circle.classList.add('expand');
                        setTimeout(() => breathingStep++, 4000);
                        break;
                    case 1: // 참기 7초
                        text.textContent = '참기';
                        setTimeout(() => breathingStep++, 7000);
                        break;
                    case 2: // 내쉬기 8초
                        text.textContent = '내쉬기';
                        circle.classList.remove('expand');
                        setTimeout(() => breathingStep = 0, 8000);
                        break;
                }
            }, 100);
        }
        
        // 호흡 가이드 중단
        function stopBreathing() {
            if (breathingInterval) {
                clearInterval(breathingInterval);
                breathingInterval = null;
            }
            
            document.getElementById('breathingCircle').classList.remove('expand');
            document.getElementById('breathingText').textContent = '시작';
        }
        
        // 응원 메시지 표시
        function showMotivation() {
            const messages = [
                "💪 당신은 충분히 준비되었습니다!",
                "🌟 최선을 다하면 결과는 따라올 거예요!",
                "🎯 집중하고 침착하게 문제를 풀어보세요!",
                "🏆 그동안의 노력이 빛을 발할 시간입니다!",
                "✨ 자신감을 가지고 도전하세요!"
            ];
            
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            alert(randomMessage);
        }
        
        // 출발 알림 설정
        function setReminder() {
            const examTime = prompt('시험 시작 시간을 입력하세요 (예: 09:00):', '09:00');
            if (examTime) {
                alert(`⏰ ${examTime} 시험을 위한 출발 알림이 설정되었습니다.\n여유롭게 출발하세요!`);
                
                // 실제 구현에서는 로컬 알림이나 서버 알림 설정
                // localStorage.setItem('examReminder', examTime);
            }
        }
        
        // 돌아가기
        function goBack() {
            window.location.href = 'quickstart_real.php?user_id=<?php echo $userid; ?>';
        }
        
        // 페이지 로드 시 현재 시간 체크
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const hour = now.getHours();
            
            // 시험 당일 아침 시간대 (6-10시)에 특별 메시지
            if (hour >= 6 && hour <= 10) {
                setTimeout(() => {
                    alert('🌅 시험 당일 아침입니다!\n가벼운 아침식사와 충분한 수분 섭취를 잊지 마세요.');
                }, 1000);
            }
        });
    </script>
</body>
</html>