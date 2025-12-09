<?php
/**
 * KTM 코파일럿 기본 카드 API
 * 
 * 기본 카드들을 DB에서 불러오기 위한 API
 * 기존 플러그인 테이블(mdl_ktm_card_plugin_settings)을 활용
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 에러 핸들링
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

// 성공 응답
function sendSuccess($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// 데이터베이스 연결
try {
    $pdo = new PDO(
        "mysql:host=$CFG->dbhost;dbname=$CFG->dbname;charset=utf8mb4",
        $CFG->dbuser,
        $CFG->dbpass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    sendError('Database connection failed', 500);
}

// 사용자 ID 가져오기 (실제 환경에서는 세션에서 가져와야 함)
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 2;

// 액션 처리
$action = isset($_GET['action']) ? $_GET['action'] : 'getMenuStructure';

switch ($action) {
    case 'getMenuStructure':
        getMenuStructure($pdo, $user_id);
        break;
    
    case 'getDefaultCards':
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        if (!$category) {
            sendError('Category is required');
        }
        getDefaultCards($pdo, $user_id, $category);
        break;
    
    default:
        sendError('Invalid action');
}

/**
 * 메뉴 구조 가져오기
 */
function getMenuStructure($pdo, $user_id) {
    $menuStructure = [
        'quarterly' => [
            'title' => '분기활동',
            'description' => '장기간에 걸친 학습 목표 설정과 성과 관리를 통해 체계적인 교육 계획을 수립합니다.',
            'tabs' => []
        ],
        'weekly' => [
            'title' => '주간활동',
            'description' => '주간 단위로 학습 계획을 수립하고 진행 상황을 체크합니다.',
            'tabs' => []
        ],
        'daily' => [
            'title' => '일일활동',
            'description' => '매일의 학습 목표와 성과를 관리하여 꾸준한 발전을 도모합니다.',
            'tabs' => []
        ],
        'realtime' => [
            'title' => '실시간 모니터링',
            'description' => '학습 진행 상황을 실시간으로 추적하고 즉각적인 피드백을 제공합니다.',
            'tabs' => []
        ],
        'interaction' => [
            'title' => '상호작용',
            'description' => '교사, 학생, 학부모 간의 원활한 소통과 피드백을 지원합니다.',
            'tabs' => []
        ],
        'bias' => [
            'title' => '인지관성 개선',
            'description' => '수학 학습에서 발생하는 인지적 장애물을 파악하고 개선합니다.',
            'tabs' => []
        ],
        'development' => [
            'title' => '개발',
            'description' => '학습 콘텐츠와 교육 앱을 개발하고 관리합니다.',
            'tabs' => []
        ],
        'viral' => [
            'title' => '바이럴 마케팅',
            'description' => '바이럴 콘텐츠 제작 및 소셜미디어 마케팅 전략',
            'tabs' => []
        ],
        'consultation' => [
            'title' => '상담',
            'description' => '학생 개별 상담 및 학부모 소통 관리',
            'tabs' => []
        ]
    ];
    
    // 각 카테고리의 탭 정보 가져오기
    $tabInfo = [
        'quarterly' => [
            ['id' => 'planning', 'title' => '계획관리', 'description' => '장기 목표 설정 및 관리'],
            ['id' => 'counseling', 'title' => '학부모상담', 'description' => '학부모와의 소통 관리']
        ],
        'weekly' => [
            ['id' => 'weekly_plan', 'title' => '주간계획', 'description' => '주간 학습 계획 수립'],
            ['id' => 'weekly_result', 'title' => '주간성과', 'description' => '주간 학습 성과 분석']
        ],
        'daily' => [
            ['id' => 'today_goal', 'title' => '오늘의 목표', 'description' => '일일 학습 목표 설정'],
            ['id' => 'today_result', 'title' => '일일성과', 'description' => '오늘의 학습 성과 확인']
        ],
        'realtime' => [
            ['id' => 'tracking', 'title' => '실시간 추적', 'description' => '학습 활동 실시간 모니터링'],
            ['id' => 'response', 'title' => '즉시 대응', 'description' => '문제 상황 즉시 대응']
        ],
        'interaction' => [
            ['id' => 'communication', 'title' => '소통 관리', 'description' => '다양한 주체 간 소통'],
            ['id' => 'feedback', 'title' => '피드백 시스템', 'description' => '체계적인 피드백 제공']
        ],
        'bias' => [
            ['id' => 'pattern_analysis', 'title' => '학습 패턴 분석', 'description' => '개인별 학습 패턴 파악'],
            ['id' => 'improvement', 'title' => '개선 전략', 'description' => '맞춤형 개선 전략 제공']
        ],
        'development' => [
            ['id' => 'content', 'title' => '콘텐츠 개발', 'description' => '학습 자료 제작'],
            ['id' => 'app', 'title' => '앱 개발', 'description' => '교육 앱 개발 및 관리']
        ]
    ];
    
    // 탭 정보 추가 및 각 탭의 아이템 수 계산
    foreach ($tabInfo as $category => $tabs) {
        if (isset($menuStructure[$category])) {
            foreach ($tabs as $tab) {
                // 해당 탭의 아이템 수 계산
                $sql = "SELECT COUNT(*) as item_count 
                        FROM mdl_ktm_card_plugin_settings 
                        WHERE user_id = :user_id 
                        AND category = :category 
                        AND is_active = 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':category' => $category
                ]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $tab['items'] = []; // 실제로는 getDefaultCards에서 가져옴
                $tab['itemCount'] = $result['item_count'];
                $menuStructure[$category]['tabs'][] = $tab;
            }
        }
    }
    
    sendSuccess($menuStructure);
}

/**
 * 카테고리별 기본 카드 가져오기
 */
function getDefaultCards($pdo, $user_id, $category) {
    $sql = "SELECT 
                card_title as title,
                card_index,
                plugin_config,
                display_order
            FROM mdl_ktm_card_plugin_settings
            WHERE user_id = :user_id
            AND category = :category
            AND plugin_id = 'external_link'
            AND is_active = 1
            ORDER BY display_order ASC, card_index ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':category' => $category
    ]);
    
    $cards = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config = json_decode($row['plugin_config'], true);
        
        $card = [
            'title' => $row['title'],
            'description' => $config['description'] ?? '',
            'details' => $config['details'] ?? [],
            'url' => $config['url'] ?? '#',
            'hasChainInteraction' => isset($config['hasChainInteraction']) ? $config['hasChainInteraction'] : false
        ];
        
        $cards[] = $card;
    }
    
    // 탭별로 그룹화
    $tabGroups = [];
    $tabRanges = [
        'quarterly' => [
            'planning' => [0, 4],      // 0-4번 카드
            'counseling' => [5, 12]    // 5-12번 카드
        ],
        'weekly' => [
            'weekly_plan' => [0, 3],   // 0-3번 카드
            'weekly_result' => [4, 7]  // 4-7번 카드
        ],
        'daily' => [
            'today_goal' => [0, 3],    // 0-3번 카드
            'today_result' => [4, 7]   // 4-7번 카드
        ],
        'realtime' => [
            'tracking' => [0, 3],      // 0-3번 카드
            'response' => [4, 7]       // 4-7번 카드
        ],
        'interaction' => [
            'communication' => [0, 3], // 0-3번 카드
            'feedback' => [4, 7]       // 4-7번 카드
        ],
        'bias' => [
            'pattern_analysis' => [0, 3], // 0-3번 카드
            'improvement' => [4, 7]       // 4-7번 카드
        ],
        'development' => [
            'content' => [0, 3],       // 0-3번 카드
            'app' => [4, 7]            // 4-7번 카드
        ]
    ];
    
    if (isset($tabRanges[$category])) {
        foreach ($tabRanges[$category] as $tabId => $range) {
            $tabGroups[$tabId] = array_slice($cards, $range[0], $range[1] - $range[0] + 1);
        }
    }
    
    sendSuccess([
        'category' => $category,
        'cards' => $cards,
        'tabGroups' => $tabGroups
    ]);
}
?>