<?php
/**
 * 오답 분석 페이지
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
    <title>오답 분석하기 - Confidence Booster</title>
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
        .error-type-card {
            transition: all 0.3s ease;
        }
        .error-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .error-type-card.selected {
            border-color: #8b5cf6;
            background-color: #f3e8ff;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- 헤더 -->
        <div class="glass p-6 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold gradient-text">오답 분석하기</h1>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($user_name); ?>님, 실수를 통해 배워봅시다!</p>
                </div>
                <button onclick="location.href='index.php'" class="px-4 py-2 text-purple-600 hover:bg-purple-50 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>돌아가기
                </button>
            </div>
        </div>

        <!-- 오답 분석 폼 -->
        <div class="glass p-8">
            <form id="errorForm">
                <!-- 문제 정보 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-question-circle mr-2 text-purple-600"></i>문제 번호/설명
                    </label>
                    <input type="text" 
                           id="problem" 
                           name="problem" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                           placeholder="예: 모의고사 15번 - 극한값 구하기"
                           required>
                </div>

                <!-- 챕터 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-book mr-2 text-purple-600"></i>관련 단원
                    </label>
                    <input type="text" 
                           id="chapter" 
                           name="chapter" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                           placeholder="예: 미적분 - 함수의 극한">
                </div>

                <!-- 오류 유형 선택 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-exclamation-triangle mr-2 text-purple-600"></i>오류 유형
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="error-type-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer" 
                             onclick="selectErrorType('calculation')">
                            <div class="flex items-center">
                                <input type="radio" name="error_type" value="calculation" id="type_calculation" class="mr-3">
                                <label for="type_calculation" class="cursor-pointer">
                                    <span class="font-semibold">계산 실수</span>
                                    <p class="text-sm text-gray-600">부호, 연산 실수</p>
                                </label>
                            </div>
                        </div>
                        <div class="error-type-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer" 
                             onclick="selectErrorType('concept')">
                            <div class="flex items-center">
                                <input type="radio" name="error_type" value="concept" id="type_concept" class="mr-3">
                                <label for="type_concept" class="cursor-pointer">
                                    <span class="font-semibold">개념 부족</span>
                                    <p class="text-sm text-gray-600">원리 이해 부족</p>
                                </label>
                            </div>
                        </div>
                        <div class="error-type-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer" 
                             onclick="selectErrorType('application')">
                            <div class="flex items-center">
                                <input type="radio" name="error_type" value="application" id="type_application" class="mr-3">
                                <label for="type_application" class="cursor-pointer">
                                    <span class="font-semibold">응용 부족</span>
                                    <p class="text-sm text-gray-600">문제 적용 어려움</p>
                                </label>
                            </div>
                        </div>
                        <div class="error-type-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer" 
                             onclick="selectErrorType('careless')">
                            <div class="flex items-center">
                                <input type="radio" name="error_type" value="careless" id="type_careless" class="mr-3">
                                <label for="type_careless" class="cursor-pointer">
                                    <span class="font-semibold">부주의</span>
                                    <p class="text-sm text-gray-600">문제 오독, 실수</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 오답 원인 분석 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-search mr-2 text-purple-600"></i>오답 원인 분석
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="5" 
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                              placeholder="왜 틀렸는지 구체적으로 분석해보세요.&#10;&#10;예시:&#10;- 무엇을 놓쳤나요?&#10;- 어떤 개념이 헷갈렸나요?&#10;- 어떤 실수를 했나요?"
                              required></textarea>
                </div>

                <!-- 해결 방법 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-lightbulb mr-2 text-purple-600"></i>해결 방법
                    </label>
                    <textarea id="solution" 
                              name="solution" 
                              rows="5" 
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:border-purple-500"
                              placeholder="다음에는 어떻게 해결할 수 있을까요?&#10;&#10;예시:&#10;- 올바른 풀이 과정&#10;- 기억해야 할 포인트&#10;- 주의할 점"></textarea>
                </div>

                <!-- 팁 -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-bold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>효과적인 오답 분석 방법
                    </h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• 틀린 이유를 구체적으로 파악하기</li>
                        <li>• 비슷한 유형의 문제 찾아보기</li>
                        <li>• 정답 풀이와 내 풀이 비교하기</li>
                        <li>• 같은 실수를 반복하지 않기 위한 체크리스트 만들기</li>
                    </ul>
                </div>

                <!-- 제출 버튼 -->
                <div class="flex justify-center space-x-4">
                    <button type="submit" 
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90 font-bold">
                        <i class="fas fa-save mr-2"></i>분석 저장
                    </button>
                    <button type="reset" 
                            class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold">
                        <i class="fas fa-redo mr-2"></i>다시 작성
                    </button>
                </div>
            </form>
        </div>

        <!-- 오답 패턴 분석 -->
        <div class="glass p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 gradient-text">나의 오답 패턴</h2>
            <div id="errorPatterns" class="space-y-3">
                <p class="text-gray-500 text-center">오답 패턴을 분석 중...</p>
            </div>
        </div>
    </div>

    <script>
    // 오류 유형 선택
    function selectErrorType(type) {
        // 모든 카드 선택 해제
        document.querySelectorAll('.error-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // 선택한 카드 하이라이트
        document.getElementById('type_' + type).checked = true;
        document.getElementById('type_' + type).closest('.error-type-card').classList.add('selected');
    }

    // 폼 제출
    document.getElementById('errorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const errorType = document.querySelector('input[name="error_type"]:checked');
        if (!errorType) {
            alert('오류 유형을 선택해주세요.');
            return;
        }
        
        const data = {
            problem: document.getElementById('problem').value,
            chapter: document.getElementById('chapter').value,
            error_type: errorType.value,
            description: document.getElementById('description').value,
            solution: document.getElementById('solution').value
        };

        // AJAX로 저장
        fetch('ajax/real_save_error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message);
                
                // 유사한 오답이 있으면 표시
                if (result.data.similar_errors && result.data.similar_errors.length > 0) {
                    let msg = '\n\n유사한 오답 패턴이 발견되었습니다:\n';
                    result.data.similar_errors.forEach(err => {
                        msg += `- ${err.chapter}: ${err.problem}\n`;
                    });
                    alert(msg);
                }
                
                // 팁 표시
                if (result.data.tip) {
                    alert('💡 팁: ' + result.data.tip);
                }
                
                loadErrorPatterns();
                document.getElementById('errorForm').reset();
                document.querySelectorAll('.error-type-card').forEach(card => {
                    card.classList.remove('selected');
                });
            } else {
                alert('저장 실패: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('저장 중 오류가 발생했습니다.');
        });
    });

    // 오답 패턴 로드
    function loadErrorPatterns() {
        fetch('ajax/get_error_patterns.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.patterns) {
                    let html = '';
                    const typeLabels = {
                        'calculation': '계산 실수',
                        'concept': '개념 부족',
                        'application': '응용 부족',
                        'careless': '부주의'
                    };
                    
                    for (let type in data.patterns) {
                        const count = data.patterns[type];
                        const percentage = Math.round((count / data.total) * 100);
                        html += `
                            <div class="p-4 border rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold">${typeLabels[type]}</span>
                                    <span class="text-purple-600 font-bold">${count}개 (${percentage}%)</span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" 
                                         style="width: ${percentage}%"></div>
                                </div>
                            </div>
                        `;
                    }
                    
                    if (data.recent_errors && data.recent_errors.length > 0) {
                        html += '<h3 class="font-bold mt-4 mb-2">최근 오답</h3>';
                        data.recent_errors.forEach(err => {
                            html += `
                                <div class="p-3 bg-gray-50 rounded-lg mb-2">
                                    <div class="font-semibold">${err.problem}</div>
                                    <div class="text-sm text-gray-600">${err.chapter} - ${typeLabels[err.error_type]}</div>
                                    <div class="text-xs text-gray-500">${err.date}</div>
                                </div>
                            `;
                        });
                    }
                    
                    document.getElementById('errorPatterns').innerHTML = html;
                } else {
                    document.getElementById('errorPatterns').innerHTML = 
                        '<p class="text-gray-500 text-center">아직 분석된 오답이 없습니다.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading patterns:', error);
            });
    }

    // 페이지 로드 시 패턴 로드
    window.addEventListener('load', function() {
        loadErrorPatterns();
    });
    </script>
</body>
</html>