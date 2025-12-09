<?php
/**
 * 요약 작성 페이지
 * 실제 데이터 저장
 */

// 세션 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? '학생';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>요약 작성하기 - Confidence Booster</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- 헤더 -->
        <div class="glass p-6 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold gradient-text">요약 작성하기</h1>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($user_name); ?>님, 오늘 배운 내용을 정리해보세요!</p>
                </div>
                <button onclick="location.href='index.php'" class="px-4 py-2 text-purple-600 hover:bg-purple-50 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>돌아가기
                </button>
            </div>
        </div>

        <!-- 요약 작성 폼 -->
        <div class="glass p-8">
            <form id="summaryForm">
                <!-- 챕터 정보 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-book mr-2 text-purple-600"></i>챕터/단원
                    </label>
                    <input type="text" 
                           id="chapter" 
                           name="chapter" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                           placeholder="예: 미적분 - 도함수의 활용"
                           required>
                </div>

                <!-- 요약 내용 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-pen mr-2 text-purple-600"></i>요약 내용
                    </label>
                    <textarea id="summary" 
                              name="summary" 
                              rows="10" 
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                              placeholder="오늘 배운 내용을 자신의 언어로 정리해보세요.&#10;&#10;예시:&#10;- 핵심 개념: &#10;- 중요한 공식: &#10;- 문제 풀이 방법: &#10;- 실수하기 쉬운 부분: "
                              required></textarea>
                    <div class="mt-2 text-sm text-gray-600">
                        <span id="charCount">0</span> / 1000자
                    </div>
                </div>

                <!-- 이해도 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-chart-line mr-2 text-purple-600"></i>이해도
                    </label>
                    <div class="flex items-center space-x-4">
                        <input type="range" 
                               id="confidence" 
                               name="confidence" 
                               min="0" 
                               max="100" 
                               value="50"
                               class="flex-1">
                        <span id="confidenceValue" class="font-bold text-purple-600 text-xl w-16 text-right">50%</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>어려워요</span>
                        <span>보통이에요</span>
                        <span>자신있어요!</span>
                    </div>
                </div>

                <!-- 힌트 -->
                <div class="mb-6 p-4 bg-purple-50 rounded-lg">
                    <h3 class="font-bold text-purple-800 mb-2">
                        <i class="fas fa-lightbulb mr-2"></i>좋은 요약을 위한 팁
                    </h3>
                    <ul class="text-sm text-purple-700 space-y-1">
                        <li>• 오늘 배운 핵심 개념을 3개 이내로 정리하기</li>
                        <li>• 예제 문제를 하나 골라 풀이 과정 설명하기</li>
                        <li>• 이전 단원과의 연결점 찾아보기</li>
                        <li>• 실생활 적용 예시 생각해보기</li>
                    </ul>
                </div>

                <!-- 제출 버튼 -->
                <div class="flex justify-center space-x-4">
                    <button type="submit" 
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90 font-bold">
                        <i class="fas fa-save mr-2"></i>저장하기
                    </button>
                    <button type="button" 
                            onclick="saveDraft()"
                            class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold">
                        <i class="fas fa-file-alt mr-2"></i>임시저장
                    </button>
                </div>
            </form>
        </div>

        <!-- 이전 요약 목록 -->
        <div class="glass p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 gradient-text">최근 요약 목록</h2>
            <div id="recentSummaries" class="space-y-3">
                <p class="text-gray-500 text-center">요약 목록을 불러오는 중...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // 문자 수 카운트
    document.getElementById('summary').addEventListener('input', function() {
        const length = this.value.length;
        document.getElementById('charCount').textContent = length;
        if (length > 1000) {
            this.value = this.value.substring(0, 1000);
        }
    });

    // 이해도 슬라이더
    document.getElementById('confidence').addEventListener('input', function() {
        document.getElementById('confidenceValue').textContent = this.value + '%';
    });

    // 폼 제출
    document.getElementById('summaryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const data = {
            chapter: document.getElementById('chapter').value,
            summary: document.getElementById('summary').value,
            confidence: document.getElementById('confidence').value
        };

        // AJAX로 저장
        fetch('ajax/real_save_summary.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('요약이 저장되었습니다! 품질 점수: ' + result.data.quality + '%');
                loadRecentSummaries();
                document.getElementById('summaryForm').reset();
                document.getElementById('confidenceValue').textContent = '50%';
                document.getElementById('charCount').textContent = '0';
            } else {
                alert('저장 실패: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('저장 중 오류가 발생했습니다.');
        });
    });

    // 임시저장
    function saveDraft() {
        const data = {
            chapter: document.getElementById('chapter').value,
            summary: document.getElementById('summary').value,
            confidence: document.getElementById('confidence').value
        };
        localStorage.setItem('summaryDraft', JSON.stringify(data));
        alert('임시저장되었습니다.');
    }

    // 임시저장 불러오기
    window.addEventListener('load', function() {
        const draft = localStorage.getItem('summaryDraft');
        if (draft) {
            const data = JSON.parse(draft);
            if (confirm('임시저장된 내용이 있습니다. 불러오시겠습니까?')) {
                document.getElementById('chapter').value = data.chapter || '';
                document.getElementById('summary').value = data.summary || '';
                document.getElementById('confidence').value = data.confidence || 50;
                document.getElementById('confidenceValue').textContent = (data.confidence || 50) + '%';
                document.getElementById('charCount').textContent = (data.summary || '').length;
            }
        }
        loadRecentSummaries();
    });

    // 최근 요약 목록 로드
    function loadRecentSummaries() {
        fetch('ajax/get_recent_summaries.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.summaries.length > 0) {
                    let html = '';
                    data.summaries.forEach(summary => {
                        html += `
                            <div class="p-3 border rounded-lg hover:bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold">${summary.chapter}</h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            ${summary.summary.substring(0, 100)}...
                                        </p>
                                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                            <span>품질: ${summary.quality}%</span>
                                            <span>이해도: ${summary.confidence}%</span>
                                            <span>${summary.date}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    document.getElementById('recentSummaries').innerHTML = html;
                } else {
                    document.getElementById('recentSummaries').innerHTML = 
                        '<p class="text-gray-500 text-center">아직 작성한 요약이 없습니다.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading summaries:', error);
            });
    }
    </script>
</body>
</html>