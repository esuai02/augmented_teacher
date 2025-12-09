<?php 
// 기본 설정 로드
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 메타인지 도우미</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .method-card {
            display: none;
        }
        .method-card.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto p-6 bg-white rounded-xl shadow-lg my-8" id="main-container">
        <!-- 시작 화면 -->
        <div id="problem-selection">
            <h1 class="text-2xl font-bold text-center text-gray-800">수학 공부 도우미</h1>
            <p class="text-center text-gray-600 mt-2">내가 겪고 있는 상황을 선택해봐요!</p>
            
            <div class="mt-6 grid grid-cols-1 gap-4" id="problem-buttons">
                <button class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-all duration-200 flex items-center text-left" data-problem="distraction">
                    <div class="text-4xl mr-4">😵</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-800">집중이 잘 안돼요</h3>
                        <p class="text-gray-600 text-sm">수학 문제를 풀 때 다른 생각이 자꾸 떠올라요</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </button>
                
                <button class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-all duration-200 flex items-center text-left" data-problem="understanding">
                    <div class="text-4xl mr-4">🤔</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-800">이해가 어려워요</h3>
                        <p class="text-gray-600 text-sm">공식은 외웠는데 문제에 적용하기 어려워요</p>
      </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </button>
                
                <button class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-all duration-200 flex items-center text-left" data-problem="forgetting">
                    <div class="text-4xl mr-4">😓</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-800">금방 잊어버려요</h3>
                        <p class="text-gray-600 text-sm">오늘 배운 내용을 내일이면 까먹어요</p>
            </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </button>
                
                <button class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-all duration-200 flex items-center text-left" data-problem="overwhelmed">
                    <div class="text-4xl mr-4">😱</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-800">양이 너무 많아요</h3>
                        <p class="text-gray-600 text-sm">시험 범위가 많아서 어떻게 공부해야 할지 모르겠어요</p>
      </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </button>
        </div>
        
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">버튼을 클릭하면 해당 문제에 맞는 학습 방법을 안내해 드립니다.</p>
          </div>
        </div>
        
        <!-- 시선돌리기 마법 학습 방법 -->
        <div id="method-eyeShifting" class="method-card">
            <div class="flex items-center mb-4">
                <button class="back-button flex items-center text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    <span class="ml-1">뒤로가기</span>
                </button>
        </div>
        
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto flex items-center justify-center mb-3 bg-purple-100 border-2 border-purple-300 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
      </div>
                <h2 class="text-2xl font-bold text-purple-700">시선돌리기 마법</h2>
                <p class="text-gray-600 mt-1">순간적으로 바로 집중되는 방법</p>
        </div>
        
            <div class="bg-white rounded-lg p-5 border-2 border-gray-200 mb-6">
                <div class="flex items-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500 mr-2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <h3 class="font-semibold text-gray-800">이런 상황일 때 좋아요!</h3>
          </div>
                <ul class="ml-7 list-disc space-y-1">
                    <li class="text-gray-700">책상에 앉으면 딴생각이 많이 나요</li>
                    <li class="text-gray-700">문제를 읽어도 무슨 뜻인지 잘 이해가 안돼요</li>
                    <li class="text-gray-700">계산하다가 자꾸 실수를 해요</li>
                    <li class="text-gray-700">문제를 보면 머리가 복잡해져요</li>
                </ul>
        </div>
            
            <div class="bg-purple-50 rounded-lg p-5 border border-purple-200 mb-6">
                <h3 class="font-bold text-purple-700 mb-3">시선돌리기 마법 사용법</h3>
                <ol class="ml-5 list-decimal space-y-3">
                    <li class="text-gray-700">
                        <span class="font-semibold">마법의 순간 만들기:</span> 문제를 풀기 전, 눈을 감고 3번 크게 숨을 쉬어보세요. 머릿속 잡생각들이 구름처럼 둥둥 떠다니는 모습을 상상해보세요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">중요한 부분만 쏙쏙:</span> 문제의 중요한 숫자와 기호에 동그라미를 쳐보세요. 문제에서 꼭 필요한 정보만 골라내는 거예요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">생각할 때 하늘 보기:</span> 계산이 필요할 때는 책상 위쪽 빈 공간이나 창밖을 바라보세요. 이렇게 하면 머릿속에 그림을 더 잘 그릴 수 있어요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">뇌에게 쉬는 시간 주기:</span> 한 문제를 풀고 나면 10초 동안 눈을 감고 휴식해보세요. 뇌가 다음 문제를 위해 충전하는 시간이에요.
                    </li>
                </ol>
            </div>
            
            <p class="text-gray-700 mb-6 bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-300">
                <span class="font-bold">효과:</span> 이 마법을 쓰면 문제를 더 정확하게 풀 수 있어요. 다른 생각이 자꾸 떠오를 때마다 이 마법을 사용해보세요!
            </p>
        </div>
        
        <!-- 지식 연결 모험 학습 방법 -->
        <div id="method-schemaConnection" class="method-card">
            <div class="flex items-center mb-4">
                <button class="back-button flex items-center text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    <span class="ml-1">뒤로가기</span>
        </button>
      </div>
            
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto flex items-center justify-center mb-3 bg-blue-100 border-2 border-blue-300 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-blue-700">지식 연결 모험</h2>
                <p class="text-gray-600 mt-1">이해의 깊이를 더하는 방법</p>
            </div>
            
            <div class="bg-white rounded-lg p-5 border-2 border-gray-200 mb-6">
                <div class="flex items-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500 mr-2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <h3 class="font-semibold text-gray-800">이런 상황일 때 좋아요!</h3>
          </div>
                <ul class="ml-7 list-disc space-y-1">
                    <li class="text-gray-700">책상에 앉으면 딴생각이 많이 나요</li>
                    <li class="text-gray-700">문제를 읽어도 무슨 뜻인지 잘 이해가 안돼요</li>
                    <li class="text-gray-700">계산하다가 자꾸 실수를 해요</li>
                    <li class="text-gray-700">문제를 보면 머리가 복잡해져요</li>
                </ul>
        </div>
        
            <div class="bg-purple-50 rounded-lg p-5 border border-purple-200 mb-6">
                <h3 class="font-bold text-purple-700 mb-3">시선돌리기 마법 사용법</h3>
                <ol class="ml-5 list-decimal space-y-3">
                    <li class="text-gray-700">
                        <span class="font-semibold">마법의 순간 만들기:</span> 문제를 풀기 전, 눈을 감고 3번 크게 숨을 쉬어보세요. 머릿속 잡생각들이 구름처럼 둥둥 떠다니는 모습을 상상해보세요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">중요한 부분만 쏙쏙:</span> 문제의 중요한 숫자와 기호에 동그라미를 쳐보세요. 문제에서 꼭 필요한 정보만 골라내는 거예요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">생각할 때 하늘 보기:</span> 계산이 필요할 때는 책상 위쪽 빈 공간이나 창밖을 바라보세요. 이렇게 하면 머릿속에 그림을 더 잘 그릴 수 있어요.
                    </li>
                    <li class="text-gray-700">
                        <span class="font-semibold">뇌에게 쉬는 시간 주기:</span> 한 문제를 풀고 나면 10초 동안 눈을 감고 휴식해보세요. 뇌가 다음 문제를 위해 충전하는 시간이에요.
                    </li>
                </ol>
        </div>
        
            <p class="text-gray-700 mb-6 bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-300">
                <span class="font-bold">효과:</span> 이 마법을 쓰면 문제를 더 정확하게 풀 수 있어요. 다른 생각이 자꾸 떠오를 때마다 이 마법을 사용해보세요!
            </p>
        </div>
      </div>
    
    <script>
        // 버튼에 이벤트 리스너 추가
        document.querySelectorAll("#problem-buttons button").forEach(button => {
            button.addEventListener("click", function() {
                // 알림 대신 해당 학습 방법 표시
                const problemType = this.getAttribute('data-problem');
                const methodId = `method-${problemType === 'distraction' ? 'eyeShifting' : 
                                      problemType === 'understanding' ? 'schemaConnection' : 
                                      problemType === 'forgetting' ? 'retrievalPractice' : 
                                      'distributedLearning'}`;
                
                // 시작 화면 숨기기
                document.getElementById('problem-selection').style.display = 'none';
                
                // 선택한 학습 방법 카드 표시
                document.getElementById(methodId).classList.add('active');
            });
        });
        
        // 뒤로가기 버튼 클릭시 이벤트 리스너 추가
        document.querySelectorAll(".back-button").forEach(button => {
            button.addEventListener("click", function() {
                // 모든 학습 방법 카드 숨기기
                document.querySelectorAll(".method-card").forEach(card => {
                    card.classList.remove('active');
                });
                
                // 시작 화면 표시
                document.getElementById('problem-selection').style.display = 'block';
            });
        });
    </script>
</body>
</html>