<?php
/**
 * WXSPERTA 에이전트 추천 시스템
 * 학생의 현재 상황과 목표에 따라 최적의 에이전트를 추천
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("../../config.php");
global $DB, $USER;
require_login();

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $USER->id;
$context = $_GET['context'] ?? 'general'; // general, exam, project, motivation, skill

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

// 학생은 자신의 데이터만 볼 수 있음
if ($role === 'student' && $student_id != $USER->id) {
    $student_id = $USER->id;
}

// 에이전트 정보 및 특성
$agents_data = [
    1 => [
        'name' => '시간 수정체',
        'icon' => '⏰',
        'category' => 'future_design',
        'tags' => ['미래비전', '목표설정', '장기계획', '자아실현'],
        'contexts' => ['general', 'motivation'],
        'strengths' => ['미래 자아 시각화', '장기 목표 연결', '동기부여']
    ],
    2 => [
        'name' => '타임라인 합성기',
        'icon' => '📅',
        'category' => 'future_design',
        'tags' => ['계획수립', '일정관리', '시간배분', '마일스톤'],
        'contexts' => ['project', 'exam'],
        'strengths' => ['체계적 계획', '시간 최적화', '진도 관리']
    ],
    3 => [
        'name' => '성장 엘리베이터',
        'icon' => '📈',
        'category' => 'future_design',
        'tags' => ['성장분석', '패턴인식', '가속전략', '성과측정'],
        'contexts' => ['skill', 'general'],
        'strengths' => ['성장 패턴 분석', '개선점 도출', '성과 추적']
    ],
    4 => [
        'name' => '성과지표 엔진',
        'icon' => '🎯',
        'category' => 'future_design',
        'tags' => ['목표수치화', 'KPI설정', '성과추적', '데이터분석'],
        'contexts' => ['exam', 'project'],
        'strengths' => ['목표 정량화', '진행률 측정', '성과 분석']
    ],
    5 => [
        'name' => '동기 엔진',
        'icon' => '🔥',
        'category' => 'execution',
        'tags' => ['동기부여', '열정관리', '내적동기', '지속력'],
        'contexts' => ['motivation', 'general'],
        'strengths' => ['동기 강화', '번아웃 예방', '열정 유지']
    ],
    6 => [
        'name' => 'SWOT 분석기',
        'icon' => '🔍',
        'category' => 'execution',
        'tags' => ['전략분석', '강약점파악', '기회포착', '위협대응'],
        'contexts' => ['project', 'skill'],
        'strengths' => ['전략적 분석', '의사결정 지원', '리스크 관리']
    ],
    7 => [
        'name' => '일일 사령부',
        'icon' => '📋',
        'category' => 'execution',
        'tags' => ['일일계획', '우선순위', '실행관리', '루틴설계'],
        'contexts' => ['general', 'exam'],
        'strengths' => ['일일 계획', '우선순위 설정', '실행력 강화']
    ],
    8 => [
        'name' => '내면 브랜딩',
        'icon' => '💎',
        'category' => 'execution',
        'tags' => ['자아정체성', '가치관정립', '내면탐구', '자기이해'],
        'contexts' => ['motivation', 'general'],
        'strengths' => ['자아 발견', '가치관 정립', '정체성 구축']
    ],
    9 => [
        'name' => '수직 탐사기',
        'icon' => '🔬',
        'category' => 'execution',
        'tags' => ['심층학습', '본질탐구', '전문성개발', '깊이있는이해'],
        'contexts' => ['skill', 'project'],
        'strengths' => ['심층 분석', '전문성 개발', '본질 이해']
    ],
    10 => [
        'name' => '자원 정원사',
        'icon' => '🌱',
        'category' => 'execution',
        'tags' => ['자료정리', '지식관리', '리소스최적화', '체계화'],
        'contexts' => ['project', 'skill'],
        'strengths' => ['자료 체계화', '지식 정리', '효율적 관리']
    ],
    11 => [
        'name' => '실행 파이프라인',
        'icon' => '⚙️',
        'category' => 'execution',
        'tags' => ['자동화', '프로세스', '효율성', '시스템구축'],
        'contexts' => ['project', 'general'],
        'strengths' => ['프로세스 자동화', '효율성 극대화', '시스템 구축']
    ],
    12 => [
        'name' => '외부 브랜딩',
        'icon' => '🎨',
        'category' => 'branding',
        'tags' => ['개인브랜드', '이미지구축', '네트워킹', '가시성향상'],
        'contexts' => ['general', 'skill'],
        'strengths' => ['개인 브랜딩', '네트워크 구축', '가시성 향상']
    ],
    13 => [
        'name' => '성장 트리거',
        'icon' => '🚀',
        'category' => 'branding',
        'tags' => ['도전과제', '성장촉진', '한계돌파', '새로운시도'],
        'contexts' => ['motivation', 'skill'],
        'strengths' => ['도전 설계', '성장 가속', '한계 극복']
    ],
    14 => [
        'name' => '경쟁 생존 전략가',
        'icon' => '♟️',
        'category' => 'branding',
        'tags' => ['경쟁전략', '차별화', '포지셔닝', '시장분석'],
        'contexts' => ['exam', 'project'],
        'strengths' => ['경쟁 우위', '전략 수립', '차별화']
    ],
    15 => [
        'name' => '시간수정체 CEO',
        'icon' => '👔',
        'category' => 'knowledge_management',
        'tags' => ['리더십', 'AI활용', '전략적사고', '통합관리'],
        'contexts' => ['project', 'general'],
        'strengths' => ['리더십 개발', 'AI 활용', '통합 관리']
    ],
    16 => [
        'name' => 'AI 정원사',
        'icon' => '🤖',
        'category' => 'knowledge_management',
        'tags' => ['AI도구활용', '지식큐레이션', '학습최적화', '스마트러닝'],
        'contexts' => ['skill', 'project'],
        'strengths' => ['AI 도구 활용', '학습 최적화', '지식 큐레이션']
    ],
    17 => [
        'name' => '신경망 설계사',
        'icon' => '🧠',
        'category' => 'knowledge_management',
        'tags' => ['학습설계', '인지과학', '기억강화', '연결학습'],
        'contexts' => ['skill', 'exam'],
        'strengths' => ['학습 설계', '기억력 강화', '지식 연결']
    ],
    18 => [
        'name' => '정보 허브',
        'icon' => '📚',
        'category' => 'knowledge_management',
        'tags' => ['정보수집', '지식통합', '리서치', '데이터관리'],
        'contexts' => ['project', 'skill'],
        'strengths' => ['정보 수집', '지식 통합', '리서치']
    ],
    19 => [
        'name' => '지식 연결망',
        'icon' => '🔗',
        'category' => 'knowledge_management',
        'tags' => ['지식연결', '통합사고', '시너지창출', '융합학습'],
        'contexts' => ['skill', 'general'],
        'strengths' => ['지식 연결', '융합 사고', '시너지 창출']
    ],
    20 => [
        'name' => '지식 수정체',
        'icon' => '💠',
        'category' => 'knowledge_management',
        'tags' => ['핵심추출', '지식결정화', '인사이트', '패턴발견'],
        'contexts' => ['exam', 'skill'],
        'strengths' => ['핵심 추출', '인사이트 도출', '지식 결정화']
    ],
    21 => [
        'name' => '유연한 백본',
        'icon' => '🦴',
        'category' => 'knowledge_management',
        'tags' => ['적응력', '유연성', '변화대응', '시스템통합'],
        'contexts' => ['general', 'project'],
        'strengths' => ['적응력 강화', '유연성', '시스템 통합']
    ]
];

// 추천 알고리즘
function getRecommendations($student_id, $context, $agents_data) {
    global $DB;
    
    $recommendations = [];
    
    // 1. 컨텍스트 기반 필터링
    $context_agents = array_filter($agents_data, function($agent) use ($context) {
        return in_array($context, $agent['contexts']);
    });
    
    // 2. 학생의 최근 활동 분석 (시뮬레이션)
    $recent_interactions = getRecentInteractions($student_id);
    
    // 3. 현재 진행률이 낮은 에이전트 우선
    $progress_data = getStudentProgress($student_id);
    
    // 4. 추천 점수 계산
    foreach ($context_agents as $agent_id => $agent) {
        $score = 0;
        
        // 컨텍스트 매칭 점수
        $context_position = array_search($context, $agent['contexts']);
        $score += (2 - $context_position) * 30; // 첫 번째 컨텍스트일수록 높은 점수
        
        // 진행률 역점수 (진행률이 낮을수록 높은 점수)
        $progress = $progress_data[$agent_id] ?? 0;
        $score += (100 - $progress) * 0.3;
        
        // 최근 상호작용 역점수 (오래 안 만날수록 높은 점수)
        $last_interaction = $recent_interactions[$agent_id] ?? 0;
        $days_since_interaction = (time() - $last_interaction) / (60 * 60 * 24);
        $score += min($days_since_interaction * 2, 40);
        
        // 카테고리 다양성 보너스
        $category_count = countCategoryInRecommendations($recommendations, $agent['category']);
        if ($category_count == 0) {
            $score += 10; // 새로운 카테고리 보너스
        }
        
        $recommendations[] = [
            'agent_id' => $agent_id,
            'agent' => $agent,
            'score' => $score,
            'progress' => $progress,
            'last_interaction_days' => round($days_since_interaction),
            'reasons' => generateReasons($agent_id, $context, $score, $progress, $days_since_interaction)
        ];
    }
    
    // 점수 기준 정렬
    usort($recommendations, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // 상위 5개만 반환
    return array_slice($recommendations, 0, 5);
}

// 최근 상호작용 데이터 (시뮬레이션)
function getRecentInteractions($student_id) {
    $interactions = [];
    foreach (range(1, 21) as $agent_id) {
        if (rand(0, 100) < 70) { // 70% 확률로 상호작용 있음
            $interactions[$agent_id] = time() - rand(0, 30) * 24 * 60 * 60; // 0-30일 전
        }
    }
    return $interactions;
}

// 학생 진행률 데이터
function getStudentProgress($student_id) {
    global $DB;
    
    $progress = [];
    for ($i = 1; $i <= 21; $i++) {
        $properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $i]);
        if ($properties) {
            $props = json_decode($properties->properties_json, true);
            $filled = count(array_filter($props, function($v) { return !empty($v); }));
            $progress[$i] = round(($filled / 8) * 100);
        } else {
            $progress[$i] = 0;
        }
    }
    return $progress;
}

// 카테고리 카운트
function countCategoryInRecommendations($recommendations, $category) {
    $count = 0;
    foreach ($recommendations as $rec) {
        if ($rec['agent']['category'] === $category) {
            $count++;
        }
    }
    return $count;
}

// 추천 이유 생성
function generateReasons($agent_id, $context, $score, $progress, $days_since) {
    $reasons = [];
    
    // 컨텍스트 기반 이유
    $context_reasons = [
        'general' => '일반적인 학습 향상',
        'exam' => '시험 준비',
        'project' => '프로젝트 수행',
        'motivation' => '동기부여 강화',
        'skill' => '스킬 개발'
    ];
    $reasons[] = $context_reasons[$context] . '에 적합';
    
    // 진행률 기반 이유
    if ($progress < 30) {
        $reasons[] = '아직 시작하지 않은 영역';
    } elseif ($progress < 70) {
        $reasons[] = '진행 중인 프로젝트 완성 필요';
    }
    
    // 상호작용 기반 이유
    if ($days_since > 14) {
        $reasons[] = '오랫동안 활용하지 않음';
    } elseif ($days_since > 7) {
        $reasons[] = '재방문 권장 시기';
    }
    
    return $reasons;
}

// 컨텍스트별 조언 생성
function getContextAdvice($context) {
    $advice = [
        'general' => [
            'title' => '일반 학습 향상 가이드',
            'tips' => [
                '다양한 에이전트를 골고루 활용하여 균형잡힌 성장을 추구하세요',
                '자신의 강점과 약점을 파악하고 맞춤형 학습 전략을 수립하세요',
                '꾸준한 자기 성찰을 통해 지속적인 개선을 이루어나가세요'
            ]
        ],
        'exam' => [
            'title' => '시험 준비 전략',
            'tips' => [
                '타임라인 합성기로 시험 준비 일정을 체계적으로 계획하세요',
                '성과지표 엔진으로 목표 점수와 현재 수준의 갭을 분석하세요',
                '지식 수정체로 핵심 내용을 추출하고 암기하세요'
            ]
        ],
        'project' => [
            'title' => '프로젝트 성공 가이드',
            'tips' => [
                'SWOT 분석기로 프로젝트의 강점과 위험요소를 파악하세요',
                '실행 파이프라인으로 작업 프로세스를 자동화하세요',
                '자원 정원사로 필요한 자료와 도구를 체계적으로 관리하세요'
            ]
        ],
        'motivation' => [
            'title' => '동기부여 강화 방법',
            'tips' => [
                '시간 수정체로 미래의 성공한 자신을 구체적으로 그려보세요',
                '동기 엔진으로 내적 동기와 외적 보상의 균형을 맞추세요',
                '성장 트리거로 새로운 도전 과제를 설정하세요'
            ]
        ],
        'skill' => [
            'title' => '스킬 개발 로드맵',
            'tips' => [
                '수직 탐사기로 특정 분야의 전문성을 깊이있게 개발하세요',
                'AI 정원사로 최신 학습 도구와 방법을 활용하세요',
                '지식 연결망으로 다양한 분야의 지식을 융합하세요'
            ]
        ]
    ];
    
    return $advice[$context] ?? $advice['general'];
}

// 추천 가져오기
$recommendations = getRecommendations($student_id, $context, $agents_data);
$context_advice = getContextAdvice($context);

// 전체 진행률 계산
$all_progress = getStudentProgress($student_id);
$overall_progress = count($all_progress) > 0 ? round(array_sum($all_progress) / count($all_progress)) : 0;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXSPERTA 에이전트 추천</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .recommendation-card {
            transition: all 0.3s ease;
        }
        
        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .score-bar {
            transition: width 0.5s ease;
        }
        
        .context-tab {
            transition: all 0.2s ease;
        }
        
        .context-tab.active {
            transform: translateY(-2px);
        }
        
        .agent-tag {
            transition: all 0.2s ease;
        }
        
        .agent-tag:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- 헤더 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">🎯 맞춤형 에이전트 추천</h1>
            
            <!-- 전체 진행률 -->
            <div class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>전체 프로젝트 진행률</span>
                    <span><?php echo $overall_progress; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-3 rounded-full score-bar" 
                         style="width: <?php echo $overall_progress; ?>%"></div>
                </div>
            </div>
            
            <!-- 컨텍스트 선택 -->
            <div class="flex gap-2 flex-wrap">
                <?php
                $contexts = [
                    'general' => ['label' => '일반 학습', 'icon' => '📚'],
                    'exam' => ['label' => '시험 준비', 'icon' => '📝'],
                    'project' => ['label' => '프로젝트', 'icon' => '🚀'],
                    'motivation' => ['label' => '동기부여', 'icon' => '💪'],
                    'skill' => ['label' => '스킬 개발', 'icon' => '🛠️']
                ];
                
                foreach ($contexts as $ctx_id => $ctx_info): ?>
                <button onclick="changeContext('<?php echo $ctx_id; ?>')" 
                        class="context-tab px-4 py-2 rounded-lg flex items-center gap-2 
                        <?php echo $context === $ctx_id ? 
                            'bg-blue-500 text-white active' : 
                            'bg-gray-200 hover:bg-gray-300'; ?>">
                    <span><?php echo $ctx_info['icon']; ?></span>
                    <span><?php echo $ctx_info['label']; ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 컨텍스트별 조언 -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-3">
                <?php echo $contexts[$context]['icon']; ?> 
                <?php echo $context_advice['title']; ?>
            </h2>
            <ul class="space-y-2">
                <?php foreach ($context_advice['tips'] as $tip): ?>
                <li class="flex items-start">
                    <span class="text-blue-500 mr-2">•</span>
                    <span class="text-gray-700"><?php echo $tip; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- 추천 에이전트 목록 -->
        <div class="space-y-4">
            <h2 class="text-xl font-semibold mb-4">추천 에이전트</h2>
            
            <?php foreach ($recommendations as $index => $rec): ?>
            <div class="recommendation-card bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-start flex-1">
                        <!-- 순위 및 아이콘 -->
                        <div class="flex items-center mr-4">
                            <span class="text-3xl font-bold text-gray-300 mr-3">#<?php echo $index + 1; ?></span>
                            <span class="text-4xl"><?php echo $rec['agent']['icon']; ?></span>
                        </div>
                        
                        <!-- 에이전트 정보 -->
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-1"><?php echo $rec['agent']['name']; ?></h3>
                            
                            <!-- 추천 이유 -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php foreach ($rec['reasons'] as $reason): ?>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                    <?php echo $reason; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- 태그 -->
                            <div class="flex flex-wrap gap-1 mb-3">
                                <?php foreach (array_slice($rec['agent']['tags'], 0, 4) as $tag): ?>
                                <span class="agent-tag text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                    #<?php echo $tag; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- 강점 -->
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">주요 강점:</span>
                                <?php echo implode(', ', $rec['agent']['strengths']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 점수 및 진행률 -->
                    <div class="ml-6 text-right">
                        <div class="mb-2">
                            <span class="text-sm text-gray-500">추천 점수</span>
                            <div class="text-2xl font-bold text-blue-500">
                                <?php echo round($rec['score']); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">현재 진행률</span>
                            <div class="flex items-center mt-1">
                                <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-500 h-2 rounded-full" 
                                         style="width: <?php echo $rec['progress']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $rec['progress']; ?>%</span>
                            </div>
                        </div>
                        
                        <a href="../../wxsperta.php?agent_id=<?php echo $rec['agent_id']; ?>" 
                           class="inline-block bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm">
                            시작하기 →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- 추가 액션 -->
        <div class="mt-8 bg-gray-100 rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold mb-3">더 많은 에이전트를 탐색하세요</h3>
            <p class="text-gray-600 mb-4">21개의 전문 AI 에이전트가 당신의 성장을 기다리고 있습니다.</p>
            <div class="flex justify-center gap-4">
                <a href="../../wxsperta.php" 
                   class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition">
                    전체 에이전트 보기
                </a>
                <a href="project_dashboard.php" 
                   class="bg-white text-gray-800 border border-gray-300 px-6 py-3 rounded-lg hover:bg-gray-50 transition">
                    진행 상황 대시보드
                </a>
            </div>
        </div>
    </div>

    <script>
        // 컨텍스트 변경
        function changeContext(newContext) {
            const params = new URLSearchParams(window.location.search);
            params.set('context', newContext);
            window.location.search = params.toString();
        }
        
        // 추천 카드 애니메이션
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.recommendation-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>