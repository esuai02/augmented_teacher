<?php
/**
 * PLP 독립 실행 파일
 * 이 파일을 omniui 폴더에 복사하면 바로 실행 가능
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_standalone.php
 */

// Moodle config 로드
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/omniui/plp_standalone.php');
$PAGE->set_title('Personal Learning Panel');
$PAGE->set_heading('Personal Learning Panel');

// AJAX 처리
if (!empty($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save_summary':
            $summary = $_POST['summary'] ?? '';
            if (strlen($summary) >= 30 && strlen($summary) <= 60) {
                // 실제 DB 저장 대신 세션에 저장 (테스트용)
                $_SESSION['plp_summary'] = $summary;
                echo json_encode(['success' => true, 'message' => '저장되었습니다']);
            } else {
                echo json_encode(['success' => false, 'message' => '30-60자로 작성해주세요']);
            }
            break;
            
        case 'get_stats':
            // 테스트 데이터
            echo json_encode([
                'success' => true,
                'data' => [
                    'streak' => rand(0, 10),
                    'advance_ratio' => rand(60, 80),
                    'review_ratio' => rand(20, 40),
                    'summary_count' => rand(5, 20)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

// HTML 출력
echo $OUTPUT->header();
?>

<style>
.plp-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.plp-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.plp-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #2c3e50;
}

.plp-subtitle {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 16px;
    color: #34495e;
}

.plp-textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    resize: vertical;
    transition: border-color 0.3s;
}

.plp-textarea:focus {
    outline: none;
    border-color: #3498db;
}

.plp-button {
    background: #3498db;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
}

.plp-button:hover {
    background: #2980b9;
}

.plp-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.plp-stat {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.plp-stat-value {
    font-size: 36px;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 8px;
}

.plp-stat-label {
    color: #7f8c8d;
    font-size: 14px;
}

.plp-char-count {
    color: #7f8c8d;
    font-size: 14px;
    margin-top: 8px;
}

.plp-success {
    color: #27ae60;
}

.plp-error {
    color: #e74c3c;
}

.plp-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .plp-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="plp-container">
    <h1 class="plp-title">🎯 개인학습 패널 (Personal Learning Panel)</h1>
    
    <!-- 학습 요약 -->
    <div class="plp-card">
        <h2 class="plp-subtitle">📚 오늘의 학습 요약</h2>
        <form id="summaryForm">
            <textarea 
                id="summaryText" 
                class="plp-textarea"
                placeholder="오늘 배운 내용을 30-60자로 요약하세요..."
                maxlength="60"
                minlength="30"
            ></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <span id="charCount" class="plp-char-count">0 / 60자</span>
                <button type="submit" class="plp-button">저장하기</button>
            </div>
            <div id="message" style="margin-top: 10px;"></div>
        </form>
    </div>

    <!-- 기능 그리드 -->
    <div class="plp-grid">
        <!-- 오답 태그 -->
        <div class="plp-card">
            <h2 class="plp-subtitle">🏷️ 오답 태그</h2>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                <p style="color: #7f8c8d; margin: 0;">오답 문제를 선택하고 태그를 추가하세요</p>
                <button class="plp-button" style="margin-top: 16px;" onclick="alert('오답 태그 기능 - 개발 중')">
                    태그 추가
                </button>
            </div>
        </div>

        <!-- 문제 체크 -->
        <div class="plp-card">
            <h2 class="plp-subtitle">✅ 문제 풀이 체크</h2>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                <p style="color: #7f8c8d; margin: 0;">오늘 푼 문제를 체크하세요</p>
                <button class="plp-button" style="margin-top: 16px;" onclick="alert('문제 체크 기능 - 개발 중')">
                    문제 체크
                </button>
            </div>
        </div>
    </div>

    <!-- 학습 통계 -->
    <div class="plp-card">
        <h2 class="plp-subtitle">📊 학습 현황</h2>
        <div class="plp-stats" id="statsContainer">
            <div class="plp-stat">
                <div class="plp-stat-value" id="streakValue">0</div>
                <div class="plp-stat-label">연속 통과</div>
            </div>
            <div class="plp-stat">
                <div class="plp-stat-value" id="advanceValue">70%</div>
                <div class="plp-stat-label">선행 학습</div>
            </div>
            <div class="plp-stat">
                <div class="plp-stat-value" id="reviewValue">30%</div>
                <div class="plp-stat-label">복습</div>
            </div>
            <div class="plp-stat">
                <div class="plp-stat-value" id="summaryValue">0</div>
                <div class="plp-stat-label">작성한 요약</div>
            </div>
        </div>
    </div>

    <!-- 추가 정보 -->
    <div class="plp-card" style="background: #ecf0f1;">
        <p style="margin: 0; color: #7f8c8d;">
            💡 <strong>학습 팁:</strong> 매일 꾸준히 요약을 작성하면 학습 효과가 크게 향상됩니다!
        </p>
    </div>
</div>

<script>
// 문자 수 카운터
document.getElementById('summaryText').addEventListener('input', function() {
    const length = this.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = length + ' / 60자';
    
    if (length >= 30 && length <= 60) {
        counter.className = 'plp-char-count plp-success';
    } else {
        counter.className = 'plp-char-count';
    }
});

// 폼 제출 처리
document.getElementById('summaryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const summaryText = document.getElementById('summaryText').value;
    const messageDiv = document.getElementById('message');
    
    if (summaryText.length < 30 || summaryText.length > 60) {
        messageDiv.innerHTML = '<span class="plp-error">⚠️ 요약은 30-60자 사이여야 합니다.</span>';
        return;
    }
    
    // AJAX 요청
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=save_summary&summary=' + encodeURIComponent(summaryText)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<span class="plp-success">✅ ' + data.message + '</span>';
            document.getElementById('summaryText').value = '';
            document.getElementById('charCount').textContent = '0 / 60자';
            loadStats();
        } else {
            messageDiv.innerHTML = '<span class="plp-error">⚠️ ' + data.message + '</span>';
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<span class="plp-error">⚠️ 저장 중 오류가 발생했습니다.</span>';
    });
});

// 통계 로드
function loadStats() {
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_stats'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            document.getElementById('streakValue').textContent = data.data.streak;
            document.getElementById('advanceValue').textContent = data.data.advance_ratio + '%';
            document.getElementById('reviewValue').textContent = data.data.review_ratio + '%';
            document.getElementById('summaryValue').textContent = data.data.summary_count;
        }
    })
    .catch(error => {
        console.error('Stats loading error:', error);
    });
}

// 페이지 로드 시 통계 로드
window.addEventListener('DOMContentLoaded', loadStats);

// 5초마다 통계 새로고침
setInterval(loadStats, 5000);
</script>

<?php
echo $OUTPUT->footer();
?>