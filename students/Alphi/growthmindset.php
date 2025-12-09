<?php 
header('Content-Type: text/html; charset=utf-8');

 
$studentid=$_GET["id"]; 
$mode=$_GET["mode"];
if($mode==NULL){
    $mode='';
}
 
    include_once("/home/moodle/public_html/moodle/config.php"); 
    global $DB, $USER; 

    $username= $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");
    $studentname=$username->firstname.$username->lastname;

     // SQL 인젝션 방지를 위한 파라미터 바인딩
     $params = array('userid' => $USER->id, 'fieldid' => 22);
     $userrole = $DB->get_record_sql(
         "SELECT data AS role FROM mdl_user_info_data WHERE userid = :userid AND fieldid = :fieldid",
         $params
     );

// 현재 날짜 설정
$today = date('Y년 n월 j일');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>성장형 마인드셋 10분 프로그램</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- 아이콘용 폰트어썸 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-btn.active {
            background-color: #4338ca; /* indigo-800 */
        }
        .tab-btn:hover:not(.active) {
            background-color: #4f46e5; /* indigo-600 */
        }
        .credits-container {
            width: 80%;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        .credits {
            position: absolute;
            width: 100%;
            text-align: center;
            font-size: 22px;
            line-height: 1.8;
            animation: scrollCredits 60s linear forwards;
            transform-origin: center bottom;
            font-weight: 300;
            color: #fff;
        }
        
        .credits p {
            margin: 20px 0;
        }
        
        .highlight {
            font-weight: bold;
            color: #80d8ff;
        }
        
        .title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 40px;
            color: #80d8ff;
        }
        
        .quote {
            font-size: 24px;
            font-style: italic;
            margin-top: 40px;
            color: #ffd54f;
        }
        
        @keyframes scrollCredits {
            0% {
                transform: translateY(100vh) translateZ(0);
                opacity: 0;
            }
            5% {
                opacity: 1;
            }
            95% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100%) translateZ(0);
                opacity: 0;
            }
        }
        
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .star {
            position: absolute;
            background-color: white;
            border-radius: 50%;
            animation: twinkle ease infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }
        
        /* 우주 광속 애니메이션 추가 */
        @keyframes warpSpeed {
            0% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                transform: scale(0) rotate(720deg) translateZ(0);
                opacity: 0;
            }
        }
        
        /* 프로그레스 바 스타일 */
        .progress-container {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 10%;
            height: 10px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            z-index: 60;
        }
        
        .progress-bar {
            height: 100%;
            width: 0%;
            background-color: #80d8ff;
            border-radius: 5px;
            transition: width 0.5s;
        }
        
        .progress-time {
            position: absolute;
            top: 15px;
            right: 0;
            color: #fff;
            font-size: 12px;
        }

        .restart-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            font-size: 24px;
            z-index: 70;
            display: none;
        }

        .break-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            font-size: 24px;
            z-index: 70;
            display: none;
        }
    </style>
    <?php if($mode === 'autoclick'): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
 
            // 수학 영역 목록
            const mathAreas = [
                'number_system',
                'exponential_log',
                'sequence',
                'expression',
                'set_proposition',
                'equation',
                'inequality',
                'function',
                'derivative',
                'integral',
                'plane_figure',
                'plane_coordinate',
                'solid_figure',
                'space_coordinate',
                'vector',
                'probability',
                'statistics'
            ];
            
            // 랜덤하게 영역 선택
            const randomIndex = Math.floor(Math.random() * mathAreas.length);
            const randomArea = mathAreas[randomIndex];
            
            // 선택된 영역의 마인드셋 표시
            showMathMindset(randomArea);
 
        });
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <!-- 사이드바 네비게이션 -->
        <div class="w-24 bg-indigo-700 text-white flex flex-col items-center pt-6 pb-6">
            <div class="mb-8">
                <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center">
                    <i class="fas fa-brain text-indigo-700 text-xl cursor-pointer" onclick="window.open('https://claude.ai/public/artifacts/d6cbea55-b8c5-4076-8f3d-145fa1048673?fullscreen=true', '_blank')"></i>
                </div>
            </div>
            
            <nav class="flex flex-col items-center space-y-8 flex-grow">
                <a href="#" data-tab="home" class="tab-btn p-3 rounded-xl flex flex-col items-center w-16 active">
                    <i class="fas fa-clock text-xl"></i>
                    <span class="text-xs mt-1">홈</span>
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/self_diagnosis.php?id=<?php echo $studentid; ?>" class="p-3 rounded-xl flex flex-col items-center w-16 bg-indigo-600 hover:bg-indigo-500 transition-colors">
                    <i class="fas fa-clipboard-check text-xl"></i>
                    <span class="text-xs mt-1">자가<br>진단</span>
                </a>
                <a href="#" data-tab="journal" class="tab-btn p-3 rounded-xl flex flex-col items-center w-16">
                    <i class="fas fa-book-open text-xl"></i>
                    <span class="text-xs mt-1">인지<br>관성</span>
                </a>
                
                <a href="#" data-tab="game" class="tab-btn p-3 rounded-xl flex flex-col items-center w-16">
                    <i class="fas fa-gamepad text-xl"></i>
                    <span class="text-xs mt-1">공부<br>과학</span>
                </a>
       
                <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid=<?php echo $studentid; ?>" target="_blank" >
                    <i class="fas fa-user-circle text-xl"></i><br>
                    <span class="text-xs mt-1">페르소나</span>
                </a>             
                <a  href="https://mathking.kr/moodle/local/augmented_teacher/books/inspiregrowth.php?userid=<?php echo $studentid; ?>" target="_blank" >
                    <i class="fas fa-user-circle text-xl"></i><br>
                    <span class="text-xs mt-1">도전문제</span>
                </a>  
                <a  href="https://claude.ai/public/artifacts/87e21b71-3a87-4838-88b4-baf267174449?fullscreen=true&userid=<?php echo $studentid; ?>" target="_blank" >
                    <i class="fas fa-user-circle text-xl"></i><br>
                    <span class="text-xs mt-1">에이전트</span>
                </a>        
            </nav>
            
            <div class="mt-auto">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800">
                     
                    <span class="text-xs mt-1"><?php echo $studentname; ?></span>
                </a>
            </div>
        </div>
        
        <!-- 메인 콘텐츠 영역 -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- 상단 헤더 -->
            <header class="h-16 bg-white shadow-sm flex items-center px-6 justify-between">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-indigo-900">성장형 마인드셋 10분 프로그램</h1>
                 </div>
                <div class="flex items-center">
                    <div class="mr-6 text-sm">
                        <span class="text-gray-500">연속 참여</span>
                        <span class="ml-2 font-bold text-indigo-600">5일째</span>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="font-semibold text-indigo-700"><?php echo $username->lastname; ?></span>
                    </div>
                </div>
            </header>
            
            <!-- 메인 콘텐츠 -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- 홈 탭 콘텐츠 -->
                <div id="home-content" class="tab-content active space-y-6">
                    <!-- 오늘의 도전 카드 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                   
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center mr-4">
                                    <i class="fas fa-video text-indigo-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium">하나 ! 성장형 마인드셋이란 ?</h3>
                                    <p class="text-sm text-gray-500">실패를 통해 배우는 뇌의 성장 원리 (약 3분)</p>
                                </div>
                                <a href="https://www.youtube.com/watch?v=n6Pbjyly908" target="_blank" class="ml-auto flex items-center bg-indigo-600 text-white rounded-full px-4 py-2">
                                    <span>영상보기</span>
                                    <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            </div>
                            
                            <div class="flex items-center mb-6">
                                <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center mr-4">
                                    <i class="fas fa-video text-indigo-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium">둘 ! 성장마인드셋 사례 살펴보기</h3>
                                    <p class="text-sm text-gray-500">마이클 조던의 실패와 성공 스토리 (약 12분)</p>
                                </div>
                                <a href="https://www.youtube.com/watch?v=zLwcYesl3_4" target="_blank" class="ml-auto flex items-center bg-indigo-600 text-white rounded-full px-4 py-2">
                                    <span>영상보기</span>
                                    <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            </div>
                            
                   

                            <!-- 수학 마인드셋 이미지 -->
                            <div id="math-image-container" class="w-full rounded-lg overflow-hidden mb-6 text-center" style="display: none;">
                                <a href="https://www.youtube.com/watch?v=T-vL_bFxm30" target="_blank" class="inline-block">
                                    <img src="https://mathking.kr/Contents/IMAGES/mindset.jpg" alt="성장형 마인드셋 이미지" class="rounded-lg shadow-md max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity">
                                </a>
                                <div class="mt-2 text-sm text-gray-600">
                                    이미지를 클릭하면 YouTube에서 수학 마인드셋 동영상이 재생됩니다
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 활동 요약 카드 
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">이번 주 활동 요약</h2>
                        <div class="grid grid-cols-7 gap-2 mb-6">
                            <?php
                            $days = ['월', '화', '수', '목', '금', '토', '일'];
                            for ($i = 0; $i < 7; $i++) {
                                $class = $i < 3 ? 'bg-green-100 text-green-600' : ($i === 3 ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400');
                                $content = $i < 3 ? '<i class="fas fa-check-circle"></i>' : ($i === 3 ? '오늘' : '');
                                echo '<div class="flex flex-col items-center">
                                    <span class="text-xs text-gray-500 mb-2">'.$days[$i].'</span>
                                    <div class="h-12 w-12 rounded-full flex items-center justify-center '.$class.'">
                                        '.$content.'
                                    </div>
                                </div>';
                            }
                            ?>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                            <div>
                                <span class="text-sm text-gray-500">이번 주 완료</span>
                                <div class="text-xl font-bold text-indigo-600">3/7</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">총 참여 일수</span>
                                <div class="text-xl font-bold text-indigo-600">32일</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">획득한 배지</span>
                                <div class="text-xl font-bold text-indigo-600">5개</div>
                            </div>
                            <button class="text-indigo-600 flex items-center text-sm font-medium">
                                <span>자세히 보기</span>
                                <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>-->
                    
                    <!-- 다음 추천 활동 
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">다음 추천 활동</h2>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg flex items-center">
                                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4 text-purple-600">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium">도전을 기회로 바꾸는 마음가짐</h3>
                                    <p class="text-sm text-gray-500">어려운 상황을 성장 기회로 전환하는 법 배우기</p>
                                </div>
                                <span class="text-indigo-600 text-xs">내일</span>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg flex items-center">
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4 text-blue-600">
                                    <i class="fas fa-bookmark"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium">뇌의 가소성 이해하기</h3>
                                    <p class="text-sm text-gray-500">노력이 뇌를 어떻게 변화시키는지 알아보기</p>
                                </div>
                                <span class="text-indigo-600 text-xs">2일 후</span>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg flex items-center">
                                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4 text-green-600">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium">긍정적 자기 대화 훈련</h3>
                                    <p class="text-sm text-gray-500">내면의 비판적 목소리를 성장 지향적으로 바꾸기</p>
                                </div>
                                <span class="text-indigo-600 text-xs">3일 후</span>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- 수학 영역 버튼 섹션 -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                        <h2 class="text-lg font-semibold mb-4">수학공부 ? 인생공부 !</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php
                            $math_areas = [
                                '수체계' => 'number_system',
                                '지수와 로그' => 'exponential_log',
                                '수열' => 'sequence',
                                '식의 계산' => 'expression',
                                '집합과 명제' => 'set_proposition',
                                '방정식' => 'equation',
                                '부등식' => 'inequality',
                                '함수' => 'function',
                                '미분' => 'derivative',
                                '적분' => 'integral',
                                '평면도형' => 'plane_figure',
                                '평면좌표' => 'plane_coordinate',
                                '입체도형' => 'solid_figure',
                                '공간좌표' => 'space_coordinate',
                                '벡터' => 'vector',
                                '경우의 수와 확률' => 'probability',
                                '통계' => 'statistics'
                            ];

                            foreach ($math_areas as $name => $id) {
                                echo '<button onclick="showMathMindset(\''.$id.'\')" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg p-4 text-center transition-colors duration-200">
                                        <i class="fas fa-square-root-alt text-xl mb-2"></i>
                                        <div class="font-medium">'.$name.'</div>
                                    </button>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- 저널 탭 콘텐츠 (준비중) -->
                <div id="journal-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-tools text-indigo-400 text-5xl mb-4"></i>
                            <h2 class="text-2xl font-bold text-indigo-800 mb-2">행동 분석/피드백 기반 학습향상 전략</h2>
                            <p class="text-gray-600 mb-6">개념공부와 문제 풀이 과정에서 나타나는 무의식적 행동 데이터를 수집하고 분석하여 학습 향상을 돕습니다</p>
                            <p class="text-sm text-gray-500">맞춤형 피드백의 놀라운 효과 (막히면 3분 응시하기, 자동 발화되는 습관 목록작성하고 추적하기)</p>
                            <br><br><table><tr><td> <a href="https://claude.ai/public/artifacts/56d40197-a5a3-49e2-857d-555b76a9e7cb?fullscreen=true" target="_blank" class="bg-indigo-600 text-white rounded-full px-4 py-2">나의 인지관성 점검</a></td><td><a href="https://claude.ai/public/artifacts/b39b2e40-b7c5-4fc6-9766-ada87f31acb4?fullscreen=true" target="_blank" class="bg-indigo-600 text-white rounded-full px-4 py-2"> 적용하기</a></td><td><a href="https://claude.ai/public/artifacts/8b540fb8-c416-41b0-9e47-8007194ad288?fullscreen=true" target="_blank" class="bg-indigo-600 text-white rounded-full px-4 py-2">학부모 이해하기</a></td><td><a href="https://claude.ai/public/artifacts/93d622ff-97a3-4f86-8dee-c99c770ee90d?fullscreen=true" target="_blank" class="bg-indigo-600 text-white rounded-full px-4 py-2">학부모 유형분석</a></td></tr></table>
                                  
                        </div>
                    </div>
                </div>
                
                <!-- 게임 탭 콘텐츠 -->
                <div id="game-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-2xl font-bold text-indigo-800 mb-6 text-center">수학 게임으로 배우기</h2>
                        <div class="grid grid-cols-3 gap-6 max-w-2xl mx-auto">
                            <?php
                            // 게임 아이콘 배열 (추후 다른 게임으로 교체 가능)
                            $games = [
                                ['icon' => 'fa-dice', 'name' => '도파민 이야기', 'color' => 'bg-red-500'],
                                ['icon' => 'fa-shapes', 'name' => '성장 마인드셋', 'color' => 'bg-blue-500'],
                                ['icon' => 'fa-calculator', 'name' => '수학뇌 이야기', 'color' => 'bg-green-500'],
                                ['icon' => 'fa-chart-line', 'name' => '우리뇌는 컴퓨터', 'color' => 'bg-purple-500'],
                                ['icon' => 'fa-cubes', 'name' => '지능 이야기', 'color' => 'bg-yellow-500'],
                                ['icon' => 'fa-infinity', 'name' => '학습심리 진단', 'color' => 'bg-pink-500'],
                                ['icon' => 'fa-balance-scale', 'name' => '시험시간이 남아요', 'color' => 'bg-indigo-500'],
                                ['icon' => 'fa-vector-square', 'name' => '차이 규칙 리듬타기', 'color' => 'bg-indigo-500'],
                                ['icon' => 'fa-percentage', 'name' => '성적향상 계단', 'color' => 'bg-indigo-500']
                            ];
                            
                            // 게임마다 고유링크 적용
                            $games[0]['link'] = "https://claude.ai/public/artifacts/1a9520b5-5fca-4d6c-897f-563798a37382?fullscreen=true";
                            $games[1]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            $games[2]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            $games[3]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            $games[4]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            $games[5]['link'] = "https://claude.ai/public/artifacts/fadcb701-9558-4df7-8a46-0df5223d2046?fullscreen=true";
                            $games[6]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            $games[7]['link'] = "https://claude.ai/public/artifacts/8384944d-ddfd-46fd-824f-f771d8426e60?fullscreen=true";
                            
           
                            
                            foreach ($games as $game) {
                                echo '<a href="'.$game['link'].'" target="_blank" class="group transform transition-all duration-200 hover:scale-105">
                                        <div class="'.$game['color'].' rounded-2xl p-8 text-white shadow-lg hover:shadow-2xl transition-shadow">
                                            <div class="flex flex-col items-center">
                                                <i class="fas '.$game['icon'].' text-5xl mb-3 group-hover:animate-bounce"></i>
                                                <span class="text-sm font-medium text-center">'.$game['name'].'</span>
                                            </div>
                                        </div>
                                    </a>';
                            }
                            ?>
                        </div>
                        <div class="mt-8 text-center">
                            <p class="text-gray-600 text-sm">
                                <i class="fas fa-info-circle"></i> 게임을 통해 수학 개념을 재미있게 학습해보세요!
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- 성장 탭 콘텐츠 (준비중) -->
                <div id="progress-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-chart-line text-indigo-400 text-5xl mb-4"></i>
                            <h2 class="text-2xl font-bold text-indigo-800 mb-2">성장 통계 준비중</h2>
                            <p class="text-gray-600 mb-6">마인드셋 성장 측정 및 통계 기능이 곧 제공될 예정입니다.</p>
                            <p class="text-sm text-gray-500">더 정확한 성장 분석을 위해 준비하고 있습니다.</p>
                        </div>
                    </div>
                </div>
                
                <!-- 커뮤니티 탭 콘텐츠 (준비중) -->
                <div id="community-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-comment-dots text-indigo-400 text-5xl mb-4"></i>
                            <h2 class="text-2xl font-bold text-indigo-800 mb-2">커뮤니티 준비중</h2>
                            <p class="text-gray-600 mb-6">다른 사용자들과 경험을 공유할 수 있는 커뮤니티 기능이 곧 제공될 예정입니다.</p>
                            <p class="text-sm text-gray-500">함께 성장하는 환경을 만들기 위해 열심히 준비하고 있습니다.</p>
                        </div>
                    </div>
                </div>
                
                <!-- 내정보 탭 콘텐츠 (준비중) -->
                <div id="profile-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-user-cog text-indigo-400 text-5xl mb-4"></i>
                            <h2 class="text-2xl font-bold text-indigo-800 mb-2">내정보 준비중</h2>
                            <p class="text-gray-600 mb-6">개인 프로필 및 마인드셋 설정 기능이 곧 제공될 예정입니다.</p>
                            <p class="text-sm text-gray-500">개인화된 경험을 위해 열심히 준비하고 있습니다.</p>
                        </div>
                    </div>
                </div>
                
                <!-- iframe 콘텐츠 -->
                <div id="iframe-content" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-6 h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold">성장형 마인드셋 도구</h2>
                            <button onclick="closeIframe()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="h-full">
                            <iframe 
                                id="mindset-iframe"
                                src="https://claude.ai/public/artifacts/d6cbea55-b8c5-4076-8f3d-145fa1048673?fullscreen=true"
                                class="w-full h-full border-0 rounded-lg"
                                style="min-height: 600px;">
                            </iframe>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- 수학 마인드셋 모달 -->
    <div id="math-mindset-modal" class="fixed inset-0 bg-black hidden z-50">
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="credits-container w-4/5 h-4/5">
                <div class="credits" id="math-mindset-content">
                    <!-- 컨텐츠는 JavaScript로 동적 로드 -->
                </div>
            </div>
            <!-- 프로그레스 바 추가 -->
            <div class="progress-container">
                <div class="progress-bar" id="mindset-progress"></div>
                <div class="progress-time" id="progress-time">0:00 / 10:00</div>
            </div>
            <!-- 다시 시작 메시지 -->
            <div class="restart-message" id="restart-message">
                다시 시작해 주세요!
            </div>
            <!-- 휴식 메시지 -->
            <div class="break-message" id="break-message">
                휴식 중
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 탭 버튼을 모두 가져옵니다
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // 각 탭 버튼에 클릭 이벤트를 추가합니다
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 현재 활성화된 탭을 비활성화합니다
                    document.querySelector('.tab-btn.active').classList.remove('active');
                    document.querySelector('.tab-content.active').classList.remove('active');
                    
                    // 클릭한 탭을 활성화합니다
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-content').classList.add('active');
                });
            });
            
            // 모달 닫기 이벤트 추가
            const modal = document.getElementById('math-mindset-modal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeMathMindset();
                }
            });
        });

        // 수학 마인드셋 관련 함수들
        // 전역 변수로 컨텐츠 정의
        window.mathContents = {
            'number_system': {
                title: '《나는 수체계처럼 성장한다》',
                content: `
                    <h1 class="title">《나는 수체계처럼 성장한다》</h1>
                    
                    <p>처음의 나는 1이었다<br>
                    그저 존재하는 것만으로 충분했던<br>
                    더하기만 배운 아이</p>
                    
                    <p>하지만 어느 날<br>
                    빼앗기고, 지워지고,<br>
                    0이 되었다<br>
                    없다는 게 있다는 걸 알게 된 날<br>
                    나는 비로소 시작했다</p>
                    
                    <p>음수처럼 뒤로 물러나기도 했지<br>
                    걸음이 줄어드는 게 아니라<br>
                    방향이 바뀌는 거란 걸<br>
                    시간이 흐른 후에야 이해했어</p>
                    
                    <p>부족함을 채우기 위해<br>
                    분수를 배웠고<br>
                    쪼개진 나를 조각조각 받아들이며<br>
                    <span class="highlight">"내 일부도 괜찮아"</span>라고 말했어</p>
                    
                    <p>그런 나에게<br>
                    "그래도 부족해"라며<br>
                    무리수들이 찾아왔지<br>
                    끝나지 않는 소수처럼<br>
                    나는 완전해질 수 없다는 걸 알았고</p>
                    
                    <p>그때서야 정수가 되었다<br>
                    모든 나를 안아줄 수 있는 용기<br>
                    음수도, 양수도, 0도<br>
                    나였다고</p>
                    
                    <p>그리고 나서야<br>
                    나는 실수가 되었다<br>
                    정확하지 않아도 충분한 나<br>
                    무한한 나, 어딘가에 계속 존재하는 나<br>
                    좌표축을 채우는 끊기지 않는 숨</p>
                    
                    <p>그러다<br>
                    어느 날<br>
                    <span class="highlight">"허수라는 것도 있어"</span><br>
                    선생님이 말했지</p>
                    
                    <p>"네가 존재한다고 믿는 것 너머에도<br>
                    숨어 있는 수들이 있단다"</p>
                    
                    <p>그 말에 나는 알았다<br>
                    나의 불안도, 상처도, 꿈도<br>
                    눈에 보이지 않아도<br>
                    실재한다는 것</p>
                    
                    <p>그래서<br>
                    나는 이제 복소수다</p>
                    
                    <p>현실과 상상을<br>
                    같이 품은 사람<br>
                    실수의 너비, 허수의 깊이</p>
                    
                    <p>나는 수체계처럼<br>
                    끝없이 확장된다<br>
                    모든 좌절은 새로운 수의 이름<br>
                    나는 계속 계산되고 있는 중이다</p>
                    
                    <p class="quote">"수체계가 확장될 때마다, 인간의 세계도 확장된다.<br>
                    나도 그렇게, 매일 조금씩 정의되고 있다."</p>
                `
            },
            'exponential_log': {
                title: '《지수처럼 커지고, 로그처럼 이해된다》',
                content: `
                    <h1 class="title">《지수처럼 커지고, 로그처럼 이해된다》</h1>
                    
                    <p>처음엔<br>
                    1이었다<br>
                    그냥 나 자신 하나</p>
                    
                    <p>하지만<br>
                    하루를 쌓고,<br>
                    하나의 도전을 더하고<br>
                    또 하나의 실수를 견뎌낼 때마다</p>
                    
                    <p>나는<br>
                    <span class="highlight">2¹, 2², 2³...</span><br>
                    지수처럼 커지고 있었어<br>
                    보이지 않게, 하지만 분명히</p>
                    
                    <p>처음엔 몰랐지<br>
                    왜 나만 느리냐고<br>
                    왜 자꾸 틀리냐고</p>
                    
                    <p>하지만<br>
                    지수의 성장은 처음엔 미미하지만<br>
                    곧 폭발적으로 커진다는 걸<br>
                    함수를 배우며, 삶을 살아가며 알게 되었어</p>
                    
                    <p>그리고 로그가 찾아왔다<br>
                    모든 걸 거꾸로 풀고 싶은 순간<br>
                    나는 성장하고 있었지만<br>
                    그게 얼마만큼인지 설명할 수 없었지</p>
                    
                    <p>로그는 말했다<br>
                    <span class="highlight">"너는 지금<br>
                    어느 지수에 도달했는지를<br>
                    알아차리는 수학이야"</span></p>
                    
                    <p>그래서 나는 나를 로그처럼 해석하기 시작했어<br>
                    얼마만큼 자랐지?<br>
                    언제부터 바뀌기 시작했지?</p>
                    
                    <p>틀렸던 문제를 다시 풀었을 때<br>
                    무서웠던 발표에서 손을 들었을 때<br>
                    그 모든 순간은<br>
                    작은 지수의 결과였고<br>
                    로그의 질문에 대한 답이었어</p>
                    
                    <p>지수는 행동이고<br>
                    로그는 이해야<br>
                    성장은 둘 다 필요해<br>
                    행동하고,<br>
                    그리고 그걸 스스로 알아보는 것</p>
                    
                    <p>나는 이제 안다<br>
                    느려 보여도 괜찮다고<br>
                    커진다는 건<br>
                    계속 곱해지는 것이라고</p>
                    
                    <p>그리고 나의 용기는<br>
                    로그로 확인되는 중이다<br>
                    조금씩, 그러나 분명하게</p>
                    
                    <p class="quote">"지수는 너를 키우고, 로그는 너를 안아준다.<br>
                    틀린 순간도 성장의 지표다."</p>
                `
            },
            'sequence': {
                title: '《나는 수열이다》',
                content: `
                    <h1 class="title">《나는 수열이다》</h1>
                    
                    <p>처음의 나는 a₁이었다<br>
                    고작 하나의 점,<br>
                    처음 적은 오답 하나<br>
                    처음 꺼낸 용기 하나</p>
                    
                    <p>두 번째, 세 번째<br>
                    나는 자꾸만 이어졌고<br>
                    a₂, a₃, a₄...</p>
                    
                    <p>누구는 말했다<br>
                    "왜 이렇게 느리게 변하니?"<br>
                    하지만 나는 몰랐지<br>
                    등차수열의 마음은<br>
                    한 걸음씩 가는 것이라고</p>
                    
                    <p>그런데 어느 날,<br>
                    나는 변하기 시작했다<br>
                    <span class="highlight">a, ar, ar², ar³...</span></p>
                    
                    <p>등비수열이 된 마음은<br>
                    단 한 번의 결심으로<br>
                    생각을, 행동을, 세상을<br>
                    곱하기 시작했어</p>
                    
                    <p>그렇게 나는<br>
                    쌓여가는 나를 바라보게 되었고<br>
                    작은 변화가<br>
                    기하급수적 용기가 되는 걸 느꼈지</p>
                    
                    <p>그러다 멈칫<br>
                    어떤 날은 aₙ이 사라지고<br>
                    점화식 속의 나는<br>
                    이전의 나에게 물었지</p>
                    
                    <p>"지금의 나는<br>
                    어떻게 만들어졌을까?"</p>
                    
                    <p>그리고 답했다<br>
                    <span class="highlight">"이전의 나 + 작은 변화"<br>
                    "이전의 나 × 꾸준한 선택"</span></p>
                    
                    <p>수열은 숫자들의 성장기록<br>
                    그리고<br>
                    나는 나의 수열을<br>
                    매일 하나씩 쓰고 있는 중</p>
                    
                    <p>오름차순이 아니어도 괜찮아<br>
                    어쩌면 한 번쯤 내려가야<br>
                    다음 항이 더 높아지는 걸지도</p>
                    
                    <p>한 번의 실패, 두 번의 실수<br>
                    모두 수열의 일부였어<br>
                    모양은 달라도<br>
                    계속되고 있다는 것</p>
                    
                    <p>그게 가장 큰 진실이야</p>
                    
                    <p class="quote">"지금의 나는 aₙ,<br>
                    다음 나는 aₙ₊₁.<br>
                    언제나 성장하고 있다는 증거."</p>
                `
            },
            'expression': {
                title: '《나는 괄호를 닫지 못한 날에도》',
                content: `
                    <h1 class="title">《나는 괄호를 닫지 못한 날에도》</h1>
                    
                    <p>어느 날<br>
                    나는 식을 풀다가 틀렸어<br>
                    괄호를 하나 닫지 않았고<br>
                    부호를 반대로 써버렸지</p>
                    
                    <p><span class="highlight">-3 + 2x = 5</span><br>
                    사실은 쉬운 문제였는데<br>
                    나는 복잡하게 만들었어</p>
                    
                    <p>그날 나는 속으로 중얼거렸어<br>
                    "나는 수학에 소질이 없나 봐…"<br>
                    그 말은 곧<br>
                    <span class="highlight">내 마음속 = (자존감 - 1)</span> 이 되었지</p>
                    
                    <p>하지만 그 순간<br>
                    선생님이 말했다<br>
                    "이건 네가 틀린 게 아니라,<br>
                    배우는 중이라는 증거야"</p>
                    
                    <p>나는 생각했어<br>
                    실수는 계산의 적이 아니라,<br>
                    계산의 일부일 수 있다</p>
                    
                    <p>괄호 안엔<br>
                    두려움도, 가능성도 같이 들어 있어<br>
                    빼기도, 더하기도, 곱하기도<br>
                    모두 네가 조합할 수 있어</p>
                    
                    <p>단 하나의 실수도,<br>
                    너의 성장공식에선 누락되지 않아</p>
                    
                    <p>때로는 식이 복잡해<br>
                    전개가 안 되고, 인수분해는 멀기만 해<br>
                    하지만 풀다 보면 알게 돼<br>
                    복잡한 식일수록<br>
                    해는 더 멋지게 다가온다는 걸</p>
                    
                    <p>내가 실수한 식들은<br>
                    하나하나 다 적어두자<br>
                    그건 틀린 게 아니라<br>
                    내 계산력이 커져가는 그래프</p>
                    
                    <p>이젠 말할 수 있어<br>
                    "이 식은 내가 틀릴 뻔한 문제야.<br>
                    그래서 더 오래 기억될 거야."</p>
                    
                    <p>그리고 나는<br>
                    오늘도 괄호를 열고<br>
                    차분히 한 줄씩 정리해가</p>
                    
                    <p>실수를 두려워하지 않는 사람만이<br>
                    진짜로 식을 정리할 수 있어</p>
                    
                    <p class="quote">"식은 복잡할수록 정리할 수 있고,<br>
                    삶은 실수할수록 더 단순해진다."</p>
                `
            },
            'set_proposition': {
                title: '《나는 나만의 진리집합이다》',
                content: `
                    <h1 class="title">《나는 나만의 진리집합이다》</h1>
                    
                    <p>처음엔 아무것도 몰랐어<br>
                    '속한다'는 게 뭔지<br>
                    '조건'이 뭔지<br>
                    '명제'가 왜 중요한지</p>
                    
                    <p>하지만 나는 매일 생각했지<br>
                    나는 어떤 사람인가요?<br>
                    나는 참인가요, 거짓인가요?</p>
                    
                    <p>그 질문은 마치<br>
                    명제처럼 나를 향했어<br>
                    <span class="highlight">"x는 도전을 두려워하지 않는다."</span><br>
                    이건 참일까?</p>
                    
                    <p>처음엔 아니었지<br>
                    틀릴까 봐, 웃음살까 봐<br>
                    나는 늘 빈 집합에 머물렀어</p>
                    
                    <p>그러다 어느 날<br>
                    수학은 말해줬어<br>
                    명제의 진리는, 그것이 틀릴 수 있기에 가치 있다고<br>
                    그리고 모든 명제는<br>
                    조건을 바꾸면 참이 될 수 있다고</p>
                    
                    <p>나는 알게 되었어<br>
                    <span class="highlight">"아직은 두려워하지만,<br>
                    계속 시도하고 있다."</span><br>
                    이건 분명 참이야<br>
                    조건을 조정하자<br>
                    삶이 논리처럼 명확해졌어</p>
                    
                    <p>그리고 나는 집합을 배웠지<br>
                    'A에 속한다'<br>
                    'B의 여집합에 속한다'<br>
                    그건 단지 기호가 아니라<br>
                    내가 속한 곳, 내가 선택한 태도를 뜻했어</p>
                    
                    <p>틀렸던 나,<br>
                    포기하려 했던 나<br>
                    그 모든 나도 하나의 원소였어<br>
                    내 성장의 진리집합 속에서</p>
                    
                    <p>수학이 알려준 가장 중요한 정리<br>
                    모든 나의 조각은 결국 참이 된다는 것<br>
                    지금은 불완전해 보여도<br>
                    언젠가 모든 조건을 만족하는 날이 올 거라는 것</p>
                    
                    <p>그리고 나는<br>
                    명제를 쓰듯 다짐한다<br>
                    <span class="highlight">"오늘도 나는 성장의 집합에 속한다."</span><br>
                    이 명제는<br>
                    반드시 참이다</p>
                    
                    <p class="quote">"참이 되려면 완벽할 필요 없다.<br>
                    조건을 바꾸면, 너도 언젠가 모든 것을 만족할 수 있어."</p>
                `
            },
            'equation': {
                title: '《나라는 이름의 방정식》',
                content: `
                    <h1 class="title">《나라는 이름의 방정식》</h1>
                    
                    <p>나는 방정식이었다<br>
                    아직 풀리지 않은 문장<br>
                    x 하나에 모든 불안을 담고<br>
                    = 기호로 삶과 균형을 이루려 애썼다</p>
                    
                    <p>때론<br>
                    <span class="highlight">x + 3 = 7</span><br>
                    같이 단순해 보이는 문제도<br>
                    어떻게 빼야 할지 몰라<br>
                    한참을 서성였지</p>
                    
                    <p>누구는 쉽게 푼다 말하고<br>
                    나는 왜 이렇게 오래 걸릴까<br>
                    그럴수록 나는<br>
                    자꾸만 미지수로만 살아가는 기분이었어</p>
                    
                    <p>그러던 어느 날<br>
                    선생님이 말했다<br>
                    <span class="highlight">"방정식은 틀려도 괜찮아<br>
                    중요한 건 양변을 똑같이 대하려는 마음이야"</span></p>
                    
                    <p>그 말이 좋았어<br>
                    양쪽이 다르다고<br>
                    틀린 게 아니었어<br>
                    서로를 이해하는 중이었을 뿐</p>
                    
                    <p>나는 나를<br>
                    좌변이라 믿었고<br>
                    세상은 늘 우변이었다</p>
                    
                    <p>하지만<br>
                    x를 남겨두고 고민할수록<br>
                    나는 내 해를<br>
                    조금씩 좁혀가기 시작했지</p>
                    
                    <p>이제는 알겠어<br>
                    미지수란 이름으로 남겨진 나도<br>
                    항등식이 되고 싶은 마음이란 걸<br>
                    언젠가 어떤 순간<br>
                    모든 게 같아지는 순간이 온다는 걸</p>
                    
                    <p>그래서 나는 포기하지 않는다<br>
                    때로는 이차방정식처럼 복잡해도<br>
                    완전제곱식으로 풀고,<br>
                    판별식으로 이해하면서</p>
                    
                    <p>나의 해를 향해<br>
                    조용히, 그러나 꾸준히 나아간다<br>
                    그게 바로<br>
                    해답보다 더 중요한 계산의 의미</p>
                    
                    <p class="quote">"방정식이 어렵다는 건,<br>
                    네가 아직 미지수라는 뜻이야.<br>
                    미지수란, 풀릴 수 있다는 가능성의 다른 이름이야."</p>
                `
            },
            'inequality': {
                title: '《나는 아직 작지만, 작지 않다》',
                content: `
                    <h1 class="title">《나는 아직 작지만, 작지 않다》</h1>
                    
                    <p><span class="highlight">x < y</span><br>
                    수학은 이렇게 말했다<br>
                    너는 아직 y보다 작다고</p>
                    
                    <p>그날 나는 문제를 틀렸고<br>
                    누군가 옆에서 맞췄지<br>
                    마음속에서<br>
                    x < y 라는 문장이 울려 퍼졌어</p>
                    
                    <p>그게 무섭고<br>
                    창피했어<br>
                    작다는 건 틀렸다는 거니까<br>
                    그때까진 그렇게 믿었거든</p>
                    
                    <p>하지만 선생님은 말했다<br>
                    <span class="highlight">"x < y는 틀렸다는 뜻이 아니야<br>
                    그저 더 자랄 수 있다는 뜻이지"</span></p>
                    
                    <p>그 말에 나는 조용히<br>
                    나만의 부등식을 적었어</p>
                    
                    <p>지금의 나 < 내일의 나<br>
                    어제의 나 < 오늘의 나 + 1</p>
                    
                    <p>나는 알게 됐어<br>
                    부등식은 나를 깎지 않아요<br>
                    그건 여백이고<br>
                    아직 열리지 않은 공간이야</p>
                    
                    <p><span class="highlight">x > 0</span><br>
                    이건 뭘까?<br>
                    내 안엔 분명히 어떤 가능성이 존재한다는 뜻</p>
                    
                    <p><span class="highlight">x ≥ 시도</span><br>
                    성공보다<br>
                    도전한 사람만이 포함되는 부등호</p>
                    
                    <p><span class="highlight">x ≤ 실패</span><br>
                    괜찮아<br>
                    실패보다 작거나 같다는 건<br>
                    그 실패를 품을 수 있는 사람이라는 뜻이야</p>
                    
                    <p>나는 이제 부등식으로 말할 수 있어</p>
                    
                    <p>나는 아직 완벽하지 않지만<br>
                    성장을 포함한 영역 안에 존재하고 있어</p>
                    
                    <p>그리고 나는 매일<br>
                    나만의 부등호를 정리한다</p>
                    
                    <p>내일의 나 ≥ 오늘의 나 – 실수 + 시도</p>
                    
                    <p class="quote">"성장은 항상 등호가 아니다.<br>
                    크거나 작거나,<br>
                    그 안에 네가 있다는 게 중요한 거야."</p>
                `
            },
            'derivative': {
                title: '《나는 변화율 위에 있다》',
                content: `
                    <h1 class="title">《나는 변화율 위에 있다》</h1>
                    
                    <p>가만히 있어도<br>
                    시간은 흘렀고<br>
                    나는 늘 어떤 곡선 위를 걷고 있었다</p>
                    
                    <p>한때는 내려가기도 했지<br>
                    a점에서 멈췄을 때<br>
                    나는 말했어<br>
                    "나는 왜 여기서 멈췄을까?"</p>
                    
                    <p>그러자 수학이<br>
                    작은 목소리로 말해줬어<br>
                    <span class="highlight">"기울기를 구해보자."</span></p>
                    
                    <p>그건<br>
                    실패를 분석하는 일<br>
                    지금 나의 위치가 아니라<br>
                    어디로 향하고 있는지 보는 일</p>
                    
                    <p>내 삶의 변화율,<br>
                    그게 바로 미분값이었지</p>
                    
                    <p>처음엔 어려웠어<br>
                    접선을 긋는다는 게<br>
                    미소한 변화량으로<br>
                    진짜 나를 파악하는 게</p>
                    
                    <p>하지만 나는 배웠어<br>
                    변화는 순간에서 시작된다는 것<br>
                    한 점의 미분값이<br>
                    나의 전체 움직임을 바꾼다는 걸</p>
                    
                    <p>그래서 이젠<br>
                    조금씩 속도를 본다<br>
                    마음이 가파를 때는,<br>
                    그만큼 나는 위로 향하고 있다는 뜻<br>
                    속도가 0인 날엔<br>
                    나는 그걸 극값이라 부른다<br>
                    잠시 멈추었을 뿐<br>
                    다시 나아갈 준비 중이니까</p>
                    
                    <p>누군가는 말했지<br>
                    "넌 아직 멀었어"<br>
                    하지만 나는 알고 있다<br>
                    <span class="highlight">f'(x) ≠ 0</span><br>
                    나는 변하고 있고<br>
                    그 변화는 점점 커지고 있다는 것</p>
                    
                    <p>미분은<br>
                    작은 순간을 사랑하는 수학<br>
                    그리고<br>
                    나는 그 순간을 살고 있는 사람</p>
                    
                    <p class="quote">"지금의 나는 곡선 위의 한 점,<br>
                    하지만 그 점의 기울기만으로<br>
                    나는 어디로든 갈 수 있다."</p>
                `
            },
            'integral': {
                title: '《나는 적분으로 자란다》',
                content: `
                    <h1 class="title">《나는 적분으로 자란다》</h1>
                    
                    <p>작았다<br>
                    너무 작아서<br>
                    아무도 눈여겨보지 않았던 나의 하루들</p>
                    
                    <p>단 하나의 실수,<br>
                    단 한 문제에 쏟은 10분,<br>
                    그걸로 뭐가 되겠냐는 말들</p>
                    
                    <p>하지만 수학은 말해줬어<br>
                    <span class="highlight">"작은 것들의 합이,<br>
                    가장 큰 것을 만든다."</span></p>
                    
                    <p>dx, dx, dx…<br>
                    거의 0에 가까운 조각이<br>
                    무한히 모이면<br>
                    넓이가 되고, 형태가 되고<br>
                    존재의 증거가 된다고</p>
                    
                    <p>나는 몰랐지<br>
                    내가 풀었던 문제들이<br>
                    내가 했던 포기가 아닌 선택들이<br>
                    사실은<br>
                    나를 적분하고 있었다는 걸</p>
                    
                    <p>눈에 보이지 않는 미소,<br>
                    그만두지 않았던 노력<br>
                    그건 그래프 아래 채워지는 그림자<br>
                    천천히, 그리고 확실하게<br>
                    내 삶을 메꿔가고 있었어</p>
                    
                    <p>적분은 증명해<br>
                    작은 것은 결코 작지 않다</p>
                    
                    <p>오늘의 dx는<br>
                    내일의 ∫가 되고<br>
                    결국 결과값 C에<br>
                    내 이야기를 더하는 거야</p>
                    
                    <p><span class="highlight">C</span><br>
                    그건 나만이 줄 수 있는 의미<br>
                    세상 누구와도 다른<br>
                    나만의 상수항</p>
                    
                    <p class="quote">"네가 쌓고 있는 하루하루는<br>
                    모두 적분 중이다.<br>
                    결국 너는, 너의 넓이만큼 성장한다."</p>
                `
            },
            'plane_figure': {
                title: '《나는 도형이다》',
                content: `
                    <h1 class="title">《나는 도형이다》</h1>
                    
                    <p>나는 처음<br>
                    점이었다<br>
                    움직이지 않고,<br>
                    작디작은 하나의 시작점</p>
                    
                    <p>누군가 선을 그어주었고<br>
                    나는 선분이 되었다<br>
                    길어졌고,<br>
                    가다가 꺾였고,<br>
                    어딘가에 도달했다</p>
                    
                    <p>그때 나는<br>
                    모서리가 생긴다는 걸 배웠다<br>
                    모서리는<br>
                    틀림이 아니라 방향이었다는 걸</p>
                    
                    <p>선이 세 개 모이자<br>
                    나는 삼각형이 되었다<br>
                    세심하게 균형 잡힌 날도 있었고<br>
                    삐뚤고 불안한 날도 있었지</p>
                    
                    <p>하지만 그 누구도 말해주지 않았던 것<br>
                    모든 삼각형은<br>
                    넓이를 가질 수 있다는 것<br>
                    불완전해 보여도<br>
                    자리를 차지한다는 것</p>
                    
                    <p>그러다 네 번째 선분이 생겼다<br>
                    나는 사각형이 되었고<br>
                    모난 내 마음도<br>
                    네 개의 각으로 표현되기 시작했지</p>
                    
                    <p>사람들은 말했다<br>
                    <span class="highlight">"원처럼 살아라,<br>
                    가장 완벽한 곡선이니까"</span></p>
                    
                    <p>하지만 나는 이제 안다<br>
                    원이 되기 위해선<br>
                    수많은 점들이 필요한 법<br>
                    완벽은 단숨에 그려지지 않고<br>
                    수천 번의 돌고 도는 흔적 끝에야<br>
                    비로소 생긴다는 걸</p>
                    
                    <p>그래서 나는 오늘도<br>
                    나만의 도형을 그려간다<br>
                    한 줄씩, 한 꼭짓점씩</p>
                    
                    <p>나는<br>
                    평면 위의 성장<br>
                    입체로 뻗어가는 꿈의 밑그림<br>
                    직선이 꺾이고, 곡선이 이어지는<br>
                    내 이름의 도형</p>
                    
                    <p class="quote">"삐뚤어져도 괜찮아.<br>
                    그건 너만의 각이니까.<br>
                    도형은, 각이 있을 때 비로소 면이 된다."</p>
                `
            },
            'plane_coordinate': {
                title: '《나는 좌표평면 위에 있다》',
                content: `
                    <h1 class="title">《나는 좌표평면 위에 있다》</h1>
                    
                    <p>처음에 나는 (0, 0)이었다<br>
                    어느 방향도,<br>
                    어느 크기도 없이<br>
                    그냥 시작의 점</p>
                    
                    <p>나는 물었어<br>
                    "나는 어디쯤 있을까?"<br>
                    그 질문이<br>
                    x축으로 나를 걷게 했고<br>
                    y축으로 나를 들여다보게 했어</p>
                    
                    <p>어느 날은<br>
                    x = 실수, y = 후회<br>
                    내가 찍은 점은<br>
                    조금 슬퍼 보였지</p>
                    
                    <p>하지만 선생님이 말했다<br>
                    <span class="highlight">"좌표는 변할 수 있어<br>
                    점은 움직이는 거니까"</span><br>
                    그 말에 나는<br>
                    방향을 다시 잡았어</p>
                    
                    <p>(−2, 3)도<br>
                    (4, −1)도<br>
                    모두 나였고<br>
                    나는 내 마음의 그래프를<br>
                    조금씩 완성하고 있었어</p>
                    
                    <p>실수해도 괜찮아<br>
                    그건 단지<br>
                    그래프 위 한 점일 뿐<br>
                    그 점들이 모여<br>
                    선을 만들고, 곡선을 만들고<br>
                    패턴을 만들며 너를 설명해줘</p>
                    
                    <p>중요한 건<br>
                    지금 네가 어디에 있느냐가 아니라<br>
                    어떤 방향으로 나아가고 있느냐는 것</p>
                    
                    <p>기울기라는 말<br>
                    이젠 나의 태도를 뜻해<br>
                    양수의 삶, 음수의 시간<br>
                    그 모두가 기울기를 가진 채<br>
                    내 그래프를 만들고 있어</p>
                    
                    <p>나는 한 점이지만<br>
                    동시에<br>
                    좌표평면 전체를 그려가는 함수이기도 해</p>
                    
                    <p>나의 좌표는 자주 바뀔 거야<br>
                    하지만 나는 알지<br>
                    원점을 지나온 사람은<br>
                    어디든 갈 수 있다는 걸</p>
                    
                    <p class="quote">"좌표는 바뀌어도,<br>
                    성장의 방향은 잃지 마."</p>
                `
            },
            'solid_figure': {
                title: '《입체로 자라는 나》',
                content: `
                    <h1 class="title">《입체로 자라는 나》</h1>
                    
                    <p>나는 처음엔 점이었다<br>
                    보이지도 않고<br>
                    크기도 없고<br>
                    그저 "존재한다"는 이유만으로 충분했던</p>
                    
                    <p>그다음 선이 되었지<br>
                    한 방향으로 뻗어가며<br>
                    무언가를 향해 가는 나</p>
                    
                    <p>그러다<br>
                    면이 되었고<br>
                    방향이 두 개가 생기며<br>
                    넓이 있는 생각을 하게 되었어</p>
                    
                    <p>그리고 어느 날<br>
                    나는 입체가 되었다<br>
                    깊이 있는 마음이 생겼다는 뜻이야<br>
                    높낮이 있는 감정도<br>
                    볼록하고 오목한 날들도<br>
                    이젠 나를 구성하는 면들이 되었지</p>
                    
                    <p>나는 알아<br>
                    도형처럼 사람도<br>
                    단면만 보고 판단할 수 없다는 것</p>
                    
                    <p>누군가는 나를<br>
                    <span class="highlight">육면체라 말하겠지<br>
                    반듯한 줄 알았는데<br>
                    사실 안을 열어보면<br>
                    뒤죽박죽 감정이 숨겨진 정육면체</span></p>
                    
                    <p>또 누군가는<br>
                    나를 구처럼 느낄지도 몰라<br>
                    굴곡 없이 보여도<br>
                    어디에든 중심을 가지고 있다는 것</p>
                    
                    <p>그리고 누군가는<br>
                    "너는 아직 미완성이야"<br>
                    꼭대기가 없는 피라미드 같다고<br>
                    하지만 나는 알고 있어<br>
                    성장은 늘 쌓이는 중이라는 걸</p>
                    
                    <p>부피는<br>
                    겉으로 보이지 않아<br>
                    그건 시간이 쌓아준 나의 용적</p>
                    
                    <p>나는 표면적보다<br>
                    내면적으로 확장 중이야<br>
                    보이는 면보다<br>
                    보이지 않는 공간이 더 많아</p>
                    
                    <p>그리고 무엇보다<br>
                    나는 지금<br>
                    자르고, 깎이고, 더해지는 중<br>
                    그게 입체가 되는 유일한 길이니까</p>
                    
                    <p class="quote">"사람도 도형처럼, 단면으론 알 수 없다.<br>
                    성장은 입체처럼 서서히 부피를 얻는다."</p>
                `
            },
            'space_coordinate': {
                title: '《나는 (x, y, z)라는 이름의 가능성이다》',
                content: `
                    <h1 class="title">《나는 (x, y, z)라는 이름의 가능성이다》</h1>
                    
                    <p>나는 한때<br>
                    평면 위에 살았어<br>
                    x축과 y축만 있으면<br>
                    모든 게 괜찮을 줄 알았지</p>
                    
                    <p>그러다 갑자기<br>
                    세 번째 축이 생겼어<br>
                    z<br>
                    설명할 수 없는 감정 같았고<br>
                    문제집에서 제일 보기 싫은 그 문제 같았어</p>
                    
                    <p><span class="highlight">"이건 3차원이야."</span><br>
                    선생님은 웃으며 말했지<br>
                    "이제 너도<br>
                    한 방향으로만 살아갈 수는 없단다."</p>
                    
                    <p>좌표는 복잡해졌고<br>
                    방향은 더 많아졌지만<br>
                    나는 천천히 이해했어</p>
                    
                    <p>성장이란,<br>
                    더 많은 축을 받아들이는 것이라는 걸</p>
                    
                    <p>나의 x는<br>
                    지나온 길,<br>
                    나의 y는<br>
                    지금 내 선택,<br>
                    그리고 나의 z는<br>
                    아직 정리되지 않은 감정과 가능성</p>
                    
                    <p>나는 처음엔 그걸 싫어했어<br>
                    불확실하고<br>
                    계산도 어렵고<br>
                    벡터까지 나온다며 울기도 했지</p>
                    
                    <p>하지만, 어느 순간 알았어<br>
                    공간이 넓어질수록<br>
                    나는 더 많은 곳에 존재할 수 있다는 것</p>
                    
                    <p>더 이상 한 평면에<br>
                    갇혀 있지 않아도 돼<br>
                    내가 서 있는 곳은<br>
                    <span class="highlight">(x, y, z)</span><br>
                    단 하나뿐인 나만의 위치</p>
                    
                    <p>틀린 풀이도,<br>
                    방향을 잘못 잡은 점도<br>
                    다 좌표 공간 어딘가에 찍혀 있어<br>
                    그건 실패가 아니라<br>
                    내가 존재한 흔적이야</p>
                    
                    <p>그리고 오늘<br>
                    또 하나의 좌표를 찍는다<br>
                    한 걸음 위로<br>
                    한 걸음 옆으로<br>
                    조금씩, 입체적으로</p>
                    
                    <p>나는 3차원의 나를<br>
                    이해해가는 중이다</p>
                    
                    <p class="quote">"좌표는 늘 새로 찍을 수 있어.<br>
                    지금의 너는, 이전보다 한 축 더 성장한 위치에 있어."</p>
                `
            },
            'vector': {
                title: '《나는 방향을 가진 사람이다》',
                content: `
                    <h1 class="title">《나는 방향을 가진 사람이다》</h1>
                    
                    <p>수많은 점 중 하나였던 나<br>
                    그저 좌표 위에 찍혀 있는<br>
                    작은 존재였지</p>
                    
                    <p>하지만 어느 날<br>
                    나는 움직이기 시작했어<br>
                    점이 아니라 벡터가 되었다</p>
                    
                    <p>단지 위치가 아니라<br>
                    방향과 크기<br>
                    그게 나를 살아 있게 했지</p>
                    
                    <p><span class="highlight">"너는 어디로 가고 있니?"</span><br>
                    벡터는 늘 그렇게 묻는다<br>
                    "얼마나 빠르게가 아니라,<br>
                    어디로 향하고 있느냐가 중요해"</p>
                    
                    <p>누군가는 더 큰 성과를 내고<br>
                    더 빠르게 나아가는 것처럼 보여도<br>
                    내 벡터는 내 속도, 내 방향<br>
                    그게 진짜 나의 정의</p>
                    
                    <p>합벡터처럼<br>
                    나와 너의 용기가 겹치면<br>
                    더 멀리 나아갈 수 있어</p>
                    
                    <p>평행하지 않아도 괜찮아<br>
                    언젠가는 같은 평면 위에서<br>
                    너의 방향을 존중하고, 나의 길을 믿으며<br>
                    같은 공간을 만들 수 있어</p>
                    
                    <p>그리고 어떤 날은<br>
                    정지벡터 같을 때도 있어<br>
                    0이라는 숫자 앞에<br>
                    나는 아무것도 아닌가 싶기도 해</p>
                    
                    <p>하지만 벡터는 알지<br>
                    0도 방향은 있다는 걸<br>
                    그 자리에 있어도<br>
                    나의 방향성은 사라지지 않는다</p>
                    
                    <p>나의 성장은<br>
                    좌표가 아니라<br>
                    방향으로 측정된다</p>
                    
                    <p>오늘 나는<br>
                    조금씩 움직이는 벡터<br>
                    작은 크기라도<br>
                    방향이 있다는 것<br>
                    그게 나의 성장이고<br>
                    나의 증거야</p>
                    
                    <p class="quote">"네가 어디에 있는지가 아니라,<br>
                    어디를 향해 가고 있는지가<br>
                    너를 말해준다."</p>
                `
            },
            'probability': {
                title: '《확률 속에서 피어나는 나》',
                content: `
                    <h1 class="title">《확률 속에서 피어나는 나》</h1>
                    
                    <p>동전을 던질 때마다<br>
                    나는 묻는다<br>
                    앞이 나올까? 뒷면일까?<br>
                    하지만 진짜 중요한 건<br>
                    던졌느냐의 여부였다</p>
                    
                    <p>수많은 경우의 수 앞에서<br>
                    나는 주저했다<br>
                    길이 너무 많아서,<br>
                    결과가 너무 불확실해서</p>
                    
                    <p>하지만 수학은 말해줬지<br>
                    <span class="highlight">"가능한 수가 많다는 건,<br>
                    네가 선택할 수 있는 게 많다는 뜻이야."</span></p>
                    
                    <p>하나의 선택이<br>
                    또 다른 선택을 낳고<br>
                    그게 쌓여<br>
                    순열이 되고, 조합이 되고,<br>
                    하나의 삶이 된다</p>
                    
                    <p>지금의 나는<br>
                    a도 b도 아닌 선택을 했지만<br>
                    그건 틀림이 아니라<br>
                    경로 중 하나일 뿐이야</p>
                    
                    <p>너는 단 하나의 정답이 아니라<br>
                    모든 가능성 안에 있는 하나의 경우니까</p>
                    
                    <p>그리고 확률이 말해줬지<br>
                    <span class="highlight">"네가 시도할수록,<br>
                    일어날 가능성은 점점 커진다"</span><br>
                    그건 수학의 진리이자<br>
                    삶의 법칙이기도 해</p>
                    
                    <p>0.1의 가능성도<br>
                    100번 시도하면<br>
                    의지라는 이름의 확률이 된다</p>
                    
                    <p>실패가 두려웠던 나는<br>
                    이제 안다<br>
                    성공은 예외가 아니라<br>
                    반복 속에서 예측 가능한 일이라는 걸</p>
                    
                    <p>그러니까 오늘도<br>
                    나는 시도한다<br>
                    가슴 속 주사위를 던진다<br>
                    불확실한 내일에 대한 확신을 품고</p>
                    
                    <p class="quote">"성공의 확률은 정해져 있지 않다.<br>
                    시도하는 네가, 확률을 만든다."</p>
                `
            },
            'statistics': {
                title: '《나는 통계로 성장한다》',
                content: `
                    <h1 class="title">《나는 통계로 성장한다》</h1>
                    
                    <p>내 성적은<br>
                    한 번씩 튀어 오른다<br>
                    그리고 가끔은 떨어진다<br>
                    선생님은 말했지<br>
                    <span class="highlight">"이건 이상치야"</span></p>
                    
                    <p>그래서 나는<br>
                    나의 실패를 이상치라 부르기로 했다<br>
                    흔하지 않지만, 사라져야 할 건 아니라는 뜻<br>
                    때로는<br>
                    그 이상치가<br>
                    나를 더 특별하게 만든다는 걸<br>
                    나는 통계에서 배웠다</p>
                    
                    <p>처음엔<br>
                    평균만 바라봤어<br>
                    "나는 왜 평균보다 낮을까"<br>
                    "이 점수면 나는 못하는 편이지 뭐…"</p>
                    
                    <p>하지만<br>
                    통계는 속삭였지<br>
                    <span class="highlight">"평균은 단지 중심이지,<br>
                    너를 전부 말하진 않아."</span></p>
                    
                    <p>나는 알게 됐어<br>
                    표준편차라는 친구가 있다는 걸<br>
                    나의 다름, 나의 흔들림은<br>
                    정상 범위 안의 떨림이었다는 걸</p>
                    
                    <p>그 뒤로<br>
                    내 실수 하나, 성취 하나<br>
                    모두 다 데이터가 되었고<br>
                    나는 내 인생의<br>
                    누적도수분포표를<br>
                    하나하나 그려가기 시작했어</p>
                    
                    <p>그래프는 말해줬어<br>
                    <span class="highlight">"처음보다 지금이 더 오른쪽으로 갔잖아.<br>
                    너, 분명히 성장 중이야."</span></p>
                    
                    <p>그리고 나는 알았다<br>
                    통계는 과거를 셈하면서<br>
                    미래를 준비하는 수학이라는 걸</p>
                    
                    <p>데이터는 나를 부정하지 않아<br>
                    다만 이해하려고 할 뿐<br>
                    그 순간 나는<br>
                    내 통계를 사랑하게 되었어</p>
                    
                    <p class="quote">"실패는 이상치가 아니라, 하나의 데이터다.<br>
                    통계로 보면, 나는 계속 성장하는 중이다."</p>
                `
            }
        };

        // 수학 마인드셋 관련 함수들
        function showMathMindset(areaId) {
            const modal = document.getElementById('math-mindset-modal');
            const content = document.getElementById('math-mindset-content');
            
            // 선택된 영역의 컨텐츠 표시
            if (window.mathContents[areaId]) {
                content.innerHTML = window.mathContents[areaId].content;
                modal.classList.remove('hidden');
                
                // 별들 생성
                createStars();

                // 애니메이션 60초로 설정하고 시작
                startAnimation(content);
                
                // 프로그레스 바 시작
                startProgressBar();
            }
        }

        // 모달 닫기 함수
        function closeMathMindset() {
            const modal = document.getElementById('math-mindset-modal');
            const content = document.getElementById('math-mindset-content');
            
            // 모달 숨기기
            modal.classList.add('hidden');
            
            // 애니메이션 초기화
            content.style.animation = 'none';
            content.offsetHeight; // 리플로우
            
            // 배경의 별들 제거
            const stars = document.querySelector('.stars');
            if (stars) {
                stars.remove();
            }
            
            // 프로그레스 바 초기화
            stopProgressBar();
            
            // 재시작 메시지 숨기기
            document.getElementById('restart-message').style.display = 'none';
            
            // 애니메이션 타이머 정리
            if (window.animationTimer) {
                clearTimeout(window.animationTimer);
                window.animationTimer = null;
            }
            if (window.repeatTimer) {
                clearTimeout(window.repeatTimer);
                window.repeatTimer = null;
            }
        }
        
        // 프로그레스 바 관련 변수
        let progressInterval;
        let startTime;
        const totalDuration = 600; // 총 10분 (600초)
        
        // 프로그레스 바 시작 함수
        function startProgressBar() {
            const progressBar = document.getElementById('mindset-progress');
            const progressTime = document.getElementById('progress-time');
            
            // 초기화
            progressBar.style.width = '0%';
            startTime = Date.now();
            
            // 프로그레스 바 업데이트 인터벌 설정 (100ms마다)
            clearInterval(progressInterval);
            progressInterval = setInterval(() => {
                const elapsedTime = (Date.now() - startTime) / 1000; // 초 단위
                const percentage = Math.min((elapsedTime / totalDuration) * 100, 100);
                
                // 프로그레스 바 업데이트
                progressBar.style.width = percentage + '%';
                
                // 시간 표시 업데이트 (분:초 형식)
                const elapsedMinutes = Math.floor(elapsedTime / 60);
                const elapsedSeconds = Math.floor(elapsedTime % 60);
                const formattedElapsed = `${elapsedMinutes}:${elapsedSeconds.toString().padStart(2, '0')}`;
                
                const totalMinutes = Math.floor(totalDuration / 60);
                const totalSeconds = Math.floor(totalDuration % 60);
                const formattedTotal = `${totalMinutes}:${totalSeconds.toString().padStart(2, '0')}`;
                
                progressTime.textContent = `${formattedElapsed} / ${formattedTotal}`;
                
                // 종료 조건
                if (elapsedTime >= totalDuration) {
                    clearInterval(progressInterval);
                    // 10분이 지나면 별들이 빨려들어가는 효과 적용
                    startWarpSpeedEffect();
                    // "다시 시작해 주세요" 메시지 표시
                    document.getElementById('restart-message').style.display = 'block';
                }
            }, 100);
        }
        
        // 프로그레스 바 중지 함수
        function stopProgressBar() {
            clearInterval(progressInterval);
            const progressBar = document.getElementById('mindset-progress');
            if (progressBar) {
                progressBar.style.width = '0%';
            }
            const progressTime = document.getElementById('progress-time');
            if (progressTime) {
                progressTime.textContent = '0:00 / 10:00';
            }
        }

        function createStars() {
            const starsContainer = document.createElement('div');
            starsContainer.classList.add('stars');
            const numStars = 200;
            
            for (let i = 0; i < numStars; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                const size = Math.random() * 2;
                const duration = 3 + Math.random() * 7;
                
                star.style.left = x + '%';
                star.style.top = y + '%';
                star.style.width = size + 'px';
                star.style.height = size + 'px';
                star.style.animationDuration = duration + 's';
                
                starsContainer.appendChild(star);
            }
            
            document.getElementById('math-mindset-modal').appendChild(starsContainer);
        }

        // 애니메이션 재생 및 반복 함수
        function startAnimation(contentElement) {
            // 기존 타이머 초기화
            if (window.animationTimer) {
                clearTimeout(window.animationTimer);
            }
            if (window.repeatTimer) {
                clearTimeout(window.repeatTimer);
            }
            
            // 휴식 메시지 및 재시작 메시지 숨기기
            document.getElementById('break-message').style.display = 'none';
            document.getElementById('restart-message').style.display = 'none';
            
            // 애니메이션 시작
            contentElement.style.animation = 'none';
            contentElement.offsetHeight; // 리플로우
            contentElement.style.animation = 'scrollCredits 60s linear forwards';
            
            // 애니메이션 종료 감지 및 재시작 설정
            window.animationTimer = setTimeout(() => {
                // 휴식 중 메시지 표시
                document.getElementById('break-message').style.display = 'block';
                
                // 크레딧 종료 후 1분 뒤 다시 시작 (랜덤 영역 선택)
                window.repeatTimer = setTimeout(() => {
                    if (document.getElementById('math-mindset-modal').classList.contains('hidden')) {
                        return; // 모달이 닫혔으면 재생하지 않음
                    }
                    
                    // 휴식 메시지 숨기기
                    document.getElementById('break-message').style.display = 'none';
                    
                    // 랜덤하게 새로운 영역 선택하여 표시
                    showRandomMathMindset();
                }, 60000); // 1분 후 재시작
            }, 60000); // 애니메이션 길이 60초
        }
        
        // 랜덤 영역 선택 함수
        function showRandomMathMindset() {
            const mathAreas = [
                'number_system',
                'exponential_log',
                'sequence',
                'expression',
                'set_proposition',
                'equation',
                'inequality',
                'function',
                'derivative',
                'integral',
                'plane_figure',
                'plane_coordinate',
                'solid_figure',
                'space_coordinate',
                'vector',
                'probability',
                'statistics'
            ];
            
            // 랜덤하게 영역 선택
            const randomIndex = Math.floor(Math.random() * mathAreas.length);
            const randomArea = mathAreas[randomIndex];
            
            // 선택된 영역의 콘텐츠 표시
            const content = document.getElementById('math-mindset-content');
            
            if (window.mathContents && window.mathContents[randomArea]) {
                content.innerHTML = window.mathContents[randomArea].content;
                
                // 별들 생성
                createStars();
                
                // 애니메이션 시작
                startAnimation(content);
                
                // 프로그레스 바는 계속 유지 (리셋하지 않음)
            }
        }

        // 우주 광속 효과 시작 함수
        function startWarpSpeedEffect() {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                // 별마다 약간의 시간차를 두고 애니메이션 적용
                setTimeout(() => {
                    star.style.animation = 'warpSpeed 2s cubic-bezier(0.5, 0, 0.75, 0) forwards';
                }, index * 5); // 5ms 간격으로 순차 적용
            });
        }
        
        // iframe 표시 함수
        function showIframe() {
            // 현재 활성화된 탭을 비활성화
            document.querySelector('.tab-btn.active').classList.remove('active');
            document.querySelector('.tab-content.active').classList.remove('active');
            
            // iframe 콘텐츠를 활성화
            document.getElementById('iframe-content').classList.add('active');
        }
        
        // iframe 닫기 함수
        function closeIframe() {
            // iframe 콘텐츠를 비활성화
            document.getElementById('iframe-content').classList.remove('active');
            
            // 홈 탭을 다시 활성화
            document.querySelector('[data-tab="home"]').classList.add('active');
            document.getElementById('home-content').classList.add('active');
        }
    </script>
</body>
</html> 