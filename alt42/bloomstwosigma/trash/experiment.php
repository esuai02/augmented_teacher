<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$studentid = $_GET["userid"];
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_db_tables':
            try {
                global $CFG, $DB;
                
                $page = intval($_POST['page'] ?? 1);
                $limit = intval($_POST['limit'] ?? 50);
                $search = $_POST['search'] ?? '';
                
                // 더 많은 테스트 데이터 생성
                $allTestTables = [];
                for ($i = 1; $i <= 127; $i++) {
                    $tableTypes = ['user', 'course', 'module', 'grade', 'quiz', 'assignment', 'forum', 'book', 'lesson', 'scorm'];
                    $tableType = $tableTypes[$i % count($tableTypes)];
                    
                    $allTestTables[] = [
                        'name' => "mdl_{$tableType}_{$i}",
                        'records' => rand(10, 5000),
                        'engine' => 'MyISAM',
                        'collation' => 'utf8mb4_unicode_ci',
                        'size' => rand(100, 10000) . ' KB',
                        'last_update' => date('Y-m-d H:i:s', strtotime("-" . rand(1, 365) . " days"))
                    ];
                }
                
                // 검색 필터링
                if ($search) {
                    $allTestTables = array_filter($allTestTables, function($table) use ($search) {
                        return stripos($table['name'], $search) !== false;
                    });
                }
                
                $totalTables = count($allTestTables);
                $totalPages = ceil($totalTables / $limit);
                $offset = ($page - 1) * $limit;
                
                // 페이지네이션 적용
                $pagedTables = array_slice($allTestTables, $offset, $limit);
                
                $testData = [
                    'success' => true,
                    'tables' => $pagedTables,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_tables' => $totalTables,
                        'limit' => $limit,
                        'offset' => $offset
                    ]
                ];
                
                header('Content-Type: application/json');
                echo json_encode($testData);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            
        case 'get_table_info':
            $tableName = $_POST['table_name'] ?? '';
            echo json_encode(getTableInfo($tableName));
            exit;
            
        case 'get_table_data':
            $tableName = $_POST['table_name'] ?? '';
            $limit = intval($_POST['limit'] ?? 10);
            $offset = intval($_POST['offset'] ?? 0);
            echo json_encode(getTableData($tableName, $limit, $offset));
            exit;
            
        case 'get_db_stats':
            echo json_encode(getDBStats());
            exit;
            
        case 'test_connection':
            echo json_encode(testDBConnection());
            exit;
            
        case 'get_table_fields':
            $tableName = $_POST['table_name'] ?? '';
            echo json_encode(getTableFields($tableName));
            exit;
    }
}

// moodle DB 직접 사용
function connectToMathkingDB() {
    try {
        global $CFG, $DB;
        
        // 단순히 현재 moodle DB 사용
        $moodleDB = new PDO(
            'mysql:host=' . $CFG->dbhost . ';dbname=' . $CFG->dbname . ';charset=utf8mb4', 
            $CFG->dbuser, 
            $CFG->dbpass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
        return $moodleDB;
    } catch (PDOException $e) {
        error_log('DB 연결 실패: ' . $e->getMessage());
        return null;
    }
}

// 테이블 목록 가져오기
function getDBTables() {
    try {
        $moodleDB = connectToMathkingDB();
        if (!$moodleDB) {
            return ['error' => 'DB 연결 실패'];
        }
        
        // 테이블 목록 가져오기
        $stmt = $moodleDB->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $tableList = [];
        
        // 처음 20개 테이블만 처리 (빠른 로딩을 위해)
        $limitedTables = array_slice($tables, 0, 20);
        
        foreach ($limitedTables as $table) {
            try {
                // 각 테이블의 기본 정보 가져오기
                $countStmt = $moodleDB->prepare("SELECT COUNT(*) FROM `$table`");
                $countStmt->execute();
                $recordCount = $countStmt->fetchColumn();
                
                $tableList[] = [
                    'name' => $table,
                    'records' => $recordCount,
                    'engine' => 'MyISAM',
                    'collation' => 'utf8mb4_unicode_ci',
                    'size' => 'Unknown',
                    'last_update' => date('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                // 개별 테이블 오류는 무시하고 계속 진행
                continue;
            }
        }
        
        return ['success' => true, 'tables' => $tableList, 'total_tables' => count($tables)];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// 특정 테이블 정보 가져오기
function getTableInfo($tableName) {
    try {
        $mathkingDB = connectToMathkingDB();
        if (!$mathkingDB) {
            return ['error' => 'DB 연결 실패'];
        }
        
        // 테이블 구조 정보
        $stmt = $mathkingDB->query("DESCRIBE `$tableName`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 레코드 수
        $countStmt = $mathkingDB->prepare("SELECT COUNT(*) FROM `$tableName`");
        $countStmt->execute();
        $recordCount = $countStmt->fetchColumn();
        
        // 테이블 상태 정보
        $statusStmt = $mathkingDB->query("SHOW TABLE STATUS LIKE '$tableName'");
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'table_name' => $tableName,
            'columns' => $columns,
            'record_count' => $recordCount,
            'engine' => $status['Engine'] ?? '',
            'size' => formatBytes($status['Data_length'] + $status['Index_length']),
            'last_update' => $status['Update_time'] ?? ''
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// 테이블 데이터 가져오기 (페이지네이션 지원)
function getTableData($tableName, $limit = 10, $offset = 0) {
    try {
        $mathkingDB = connectToMathkingDB();
        if (!$mathkingDB) {
            return ['error' => 'DB 연결 실패'];
        }
        
        // 전체 레코드 수 조회
        $countStmt = $mathkingDB->prepare("SELECT COUNT(*) FROM `$tableName`");
        $countStmt->execute();
        $totalRecords = $countStmt->fetchColumn();
        
        // 컬럼 정보 가져오기
        $columnsStmt = $mathkingDB->prepare("DESCRIBE `$tableName`");
        $columnsStmt->execute();
        $columnsInfo = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($columnsInfo, 'Field');
        
        // 데이터 가져오기
        $stmt = $mathkingDB->prepare("SELECT * FROM `$tableName` LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $data,
            'columns' => $columns,
            'total_records' => $totalRecords,
            'current_page' => floor($offset / $limit) + 1,
            'total_pages' => ceil($totalRecords / $limit),
            'limit' => $limit,
            'offset' => $offset
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// DB 통계 정보 가져오기
function getDBStats() {
    try {
        $mathkingDB = connectToMathkingDB();
        if (!$mathkingDB) {
            return ['error' => 'DB 연결 실패'];
        }
        
        // 테이블 수
        $tableCountStmt = $mathkingDB->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'mathking'");
        $tableCount = $tableCountStmt->fetchColumn();
        
        // 전체 레코드 수 (대략적)
        $totalRecords = 0;
        $tablesStmt = $mathkingDB->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $countStmt = $mathkingDB->prepare("SELECT COUNT(*) FROM `$table`");
            $countStmt->execute();
            $totalRecords += $countStmt->fetchColumn();
        }
        
        // DB 크기
        $sizeStmt = $mathkingDB->query("
            SELECT SUM(data_length + index_length) as db_size 
            FROM information_schema.tables 
            WHERE table_schema = 'mathking'
        ");
        $dbSize = $sizeStmt->fetchColumn();
        
        return [
            'success' => true,
            'table_count' => $tableCount,
            'total_records' => number_format($totalRecords),
            'db_size' => formatBytes($dbSize)
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// 바이트를 읽기 쉬운 형태로 변환
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// DB 연결 테스트
function testDBConnection() {
    global $CFG, $DB;
    
    $result = [
        'moodle_config' => [
            'dbhost' => $CFG->dbhost ?? 'not set',
            'dbname' => $CFG->dbname ?? 'not set',
            'dbuser' => $CFG->dbuser ?? 'not set',
            'dbpass' => $CFG->dbpass ? 'set' : 'not set'
        ],
        'connection_test' => [],
        'available_databases' => []
    ];
    
    try {
        // 기본 연결 테스트
        $testDB = new PDO(
            'mysql:host=' . $CFG->dbhost . ';charset=utf8mb4', 
            $CFG->dbuser, 
            $CFG->dbpass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $result['connection_test']['status'] = 'success';
        $result['connection_test']['message'] = 'MySQL 연결 성공';
        
        // 사용 가능한 데이터베이스 목록
        $stmt = $testDB->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $result['available_databases'] = $databases;
        
        // mathking DB 존재 확인
        $result['mathking_exists'] = in_array('mathking', $databases);
        
        // 현재 DB에서 테이블 목록 가져오기
        $currentDB = new PDO(
            'mysql:host=' . $CFG->dbhost . ';dbname=' . $CFG->dbname . ';charset=utf8mb4', 
            $CFG->dbuser, 
            $CFG->dbpass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $tablesStmt = $currentDB->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        $result['current_db_tables'] = array_slice($tables, 0, 10); // 첫 10개만
        $result['current_db_table_count'] = count($tables);
        
    } catch (Exception $e) {
        $result['connection_test']['status'] = 'error';
        $result['connection_test']['message'] = $e->getMessage();
    }
    
    return $result;
}

// 테이블 필드 정보 가져오기
function getTableFields($tableName) {
    try {
        $moodleDB = connectToMathkingDB();
        if (!$moodleDB) {
            return ['error' => 'DB 연결 실패'];
        }
        
        // 테이블 구조 정보 가져오기
        $stmt = $moodleDB->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fieldList = [];
        foreach ($fields as $field) {
            $fieldList[] = [
                'name' => $field['Field'],
                'type' => $field['Type'],
                'null' => $field['Null'],
                'key' => $field['Key'],
                'default' => $field['Default'],
                'extra' => $field['Extra']
            ];
        }
        
        return [
            'success' => true,
            'table_name' => $tableName,
            'fields' => $fieldList
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>실험 추적 시스템</title>
    <link rel="stylesheet" href="experiment.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div id="app" class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 text-white">
        <!-- 헤더 -->
        <header class="header bg-white/10 backdrop-blur-md border-b border-white/20 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-chart-line text-indigo-400 text-2xl"></i>
                    <div>
                        <h1 class="text-2xl font-bold">교육 실험 설계 및 추적 시스템</h1>
                        <p class="text-sm text-gray-300" id="experiment-title">실험명을 설정해주세요</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="user-info text-sm">
                        <span>사용자: <?php echo $USER->firstname . ' ' . $USER->lastname; ?></span>
                        <span class="ml-4">역할: <?php echo $role; ?></span>
                    </div>
                    <div class="px-4 py-2 rounded-lg bg-blue-500/20 text-blue-400 flex items-center space-x-2">
                        <i class="fas fa-users"></i>
                        <span class="text-sm" id="participant-count">참가자: 0명</span>
                    </div>
                    <button class="p-2 bg-white/10 hover:bg-white/20 rounded-lg">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- 탭 네비게이션 -->
        <nav class="navigation bg-white/5 border-b border-white/20">
            <div class="px-6 flex space-x-6">
                <button class="nav-tab active" data-tab="design">
                    <i class="fas fa-cog"></i>
                    <span>실험 설계</span>
                </button>
                <button class="nav-tab" data-tab="groups">
                    <i class="fas fa-users"></i>
                    <span>그룹 배정</span>
                </button>
                <button class="nav-tab" data-tab="tracking">
                    <i class="fas fa-database"></i>
                    <span>데이터 추적</span>
                </button>
                <button class="nav-tab" data-tab="experiment">
                    <i class="fas fa-file-text"></i>
                    <span>실험 기록</span>
                </button>
            </div>
        </nav>

        <!-- 메인 콘텐츠 -->
        <main class="main-content p-6">
            <!-- 실험 설계 탭 -->
            <section id="design-tab" class="tab-content active">
                <!-- 실험 기본 설정 -->
                <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20 mb-6">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-cog mr-2"></i>
                        실험 기본 설정
                    </h3>
                    
                    <form id="experiment-config-form">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">실험명</label>
                                <input
                                    type="text"
                                    id="experiment-name"
                                    name="experiment-name"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    placeholder="예: 메타인지 피드백 효과 검증 실험"
                                />
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">설명</label>
                                <textarea
                                    id="experiment-description"
                                    name="experiment-description"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    rows="3"
                                    placeholder="실험의 목적과 배경을 간단히 설명해주세요..."
                                ></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">시작일</label>
                                <input
                                    type="date"
                                    id="start-date"
                                    name="start-date"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">실험 기간 (주)</label>
                                <input
                                    type="number"
                                    id="duration"
                                    name="duration"
                                    value="8"
                                    min="1"
                                    max="24"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                        </div>
                        
                        <button type="submit" class="mt-6 px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                            <i class="fas fa-save mr-2"></i>
                            실험 설정 저장
                        </button>
                    </form>
                </div>

                <!-- 개입 방법 -->
                <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20 mb-6">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-brain mr-2"></i>
                        개입 방법
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            class="feedback-method p-4 rounded-lg border-2 border-purple-400/30 bg-purple-500/10 hover:bg-purple-500/20 transition-all text-left"
                            data-type="metacognitive"
                        >
                            <h4 class="font-semibold mb-1">메타인지 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">자기점검 질문, 조건 확인 유도</p>
                            <p class="text-xs text-purple-300" id="metacognitive-count">선택됨: 0개</p>
                        </button>
                        
                        <button 
                            class="feedback-method p-4 rounded-lg border-2 border-blue-400/30 bg-blue-500/10 hover:bg-blue-500/20 transition-all text-left"
                            data-type="learning"
                        >
                            <h4 class="font-semibold mb-1">학습인지 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">콘텐츠 요약, 전략 제안</p>
                            <p class="text-xs text-blue-300" id="learning-count">선택됨: 0개</p>
                        </button>
                        
                        <button 
                            class="feedback-method p-4 rounded-lg border-2 border-green-400/30 bg-green-500/10 hover:bg-green-500/20 transition-all text-left"
                            data-type="combined"
                        >
                            <h4 class="font-semibold mb-1">결합형 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">메타인지 + 학습인지 교차</p>
                            <p class="text-xs text-green-300" id="combined-count">선택됨: 0개</p>
                        </button>
                        
                        <div class="p-4 rounded-lg border-2 border-gray-400/30 bg-gray-500/10">
                            <h4 class="font-semibold mb-1">통제그룹</h4>
                            <p class="text-sm text-gray-300 mb-2">선택된 피드백 비활성화</p>
                            <p class="text-xs text-gray-400" id="disabled-count">비활성화됨: 0개</p>
                        </div>
                    </div>
                </div>

                <!-- 측정 지표 선택 -->
                <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-target mr-2"></i>
                        측정 지표 선택
                    </h3>
                    
                    <div class="space-y-3" id="tracking-configs">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </section>

            <!-- 그룹 배정 탭 -->
            <section id="groups-tab" class="tab-content">
                <!-- 선생님 선택 -->
                <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20 mb-6">
                    <h3 class="text-xl font-semibold mb-4">선생님 선택</h3>
                    <select 
                        id="teacher-select"
                        class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        <option value="">선생님을 선택하세요</option>
                        <option value="김선생님">김선생님</option>
                        <option value="박선생님">박선생님</option>
                        <option value="이선생님">이선생님</option>
                    </select>
                </div>

                <div id="group-assignment-container" class="grid grid-cols-3 gap-6" style="display: none;">
                    <!-- 학생 목록 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold mb-4" id="teacher-students-title">학생 목록</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto" id="available-students"></div>
                    </div>

                    <!-- 통제 그룹 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">통제 그룹 (<span id="control-group-count">0</span>명)</h3>
                            <button 
                                id="add-to-control"
                                class="text-sm px-3 py-1 bg-gray-500 hover:bg-gray-600 rounded disabled:opacity-50"
                                disabled
                            >
                                선택된 학생 추가
                            </button>
                        </div>
                        <div class="space-y-2 max-h-96 overflow-y-auto" id="control-group"></div>
                    </div>

                    <!-- 실험 그룹 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">실험 그룹 (<span id="experiment-group-count">0</span>명)</h3>
                            <button 
                                id="add-to-experiment"
                                class="text-sm px-3 py-1 bg-green-500 hover:bg-green-600 rounded disabled:opacity-50"
                                disabled
                            >
                                선택된 학생 추가
                            </button>
                        </div>
                        <div class="space-y-2 max-h-96 overflow-y-auto" id="experiment-group"></div>
                    </div>
                </div>
            </section>

            <!-- 데이터 추적 탭 -->
            <section id="tracking-tab" class="tab-content">
                <div class="grid grid-cols-2 gap-6">
                    <!-- 추적 설정 목록 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold">추적 설정 목록</h3>
                            <button 
                                id="add-tracking-config"
                                class="px-4 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg flex items-center space-x-2"
                            >
                                <i class="fas fa-plus"></i>
                                <span>추적 설정 추가</span>
                            </button>
                        </div>
                        
                        <div class="space-y-3" id="tracking-config-list">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>

                    <!-- DB 연결 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold">
                                <i class="fas fa-database mr-2"></i>
                                DB 연결
                            </h3>
                            <div class="flex space-x-2">
                                <button 
                                    id="selectDBBtn"
                                    onclick="showDBTablesModal()"
                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm"
                                >
                                    <i class="fas fa-database mr-1"></i>
                                    DB 선택하기
                                </button>
                                <button 
                                    id="showDBInfoBtn"
                                    onclick="showDBInfo()"
                                    class="px-3 py-1 bg-green-500 hover:bg-green-600 rounded text-sm"
                                >
                                    <i class="fas fa-info-circle mr-1"></i>
                                    DB 정보
                                </button>
                            </div>
                        </div>
                        
                        <!-- 선택된 테이블 정보 -->
                        <div id="selectedTableInfo" class="mb-4" style="display: none;">
                            <div class="bg-blue-500/10 border border-blue-400/30 rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-blue-300">선택된 테이블</h4>
                                    <button onclick="clearSelectedTable()" class="text-gray-400 hover:text-white">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="text-sm text-gray-300" id="selectedTableName">-</div>
                                <div class="text-xs text-gray-400" id="selectedTableDetails">-</div>
                            </div>
                        </div>
                        
                        <!-- 필드 목록 -->
                        <div id="fieldsSection" style="display: none;">
                            <h4 class="font-medium mb-3 text-purple-300">
                                <i class="fas fa-list mr-2"></i>
                                테이블 필드
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto mb-4" id="fieldsList">
                                <!-- JavaScript로 동적 생성 -->
                            </div>
                        </div>
                        
                        <!-- 조건 설정 -->
                        <div id="conditionsSection" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-green-300">
                                    <i class="fas fa-filter mr-2"></i>
                                    데이터 조건
                                </h4>
                                <button onclick="addCondition()" class="px-2 py-1 bg-green-500 hover:bg-green-600 rounded text-xs">
                                    <i class="fas fa-plus mr-1"></i>
                                    조건 추가
                                </button>
                            </div>
                            <div class="space-y-2" id="conditionsList">
                                <!-- JavaScript로 동적 생성 -->
                            </div>
                            
                            <!-- SQL 미리보기 -->
                            <div class="mt-4 p-3 bg-gray-800 rounded-lg">
                                <div class="text-xs text-gray-400 mb-1">SQL 미리보기:</div>
                                <div class="text-xs font-mono text-green-300" id="sqlPreview">SELECT * FROM table_name</div>
                            </div>
                            
                            <!-- 실행 버튼 -->
                            <div class="mt-4 flex space-x-2">
                                <button onclick="executeQuery()" class="px-3 py-1 bg-purple-500 hover:bg-purple-600 rounded text-sm">
                                    <i class="fas fa-play mr-1"></i>
                                    실행
                                </button>
                                <button onclick="saveQuery()" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm">
                                    <i class="fas fa-save mr-1"></i>
                                    저장
                                </button>
                            </div>
                        </div>
                        
                        <!-- 초기 상태 -->
                        <div id="initialState" class="text-gray-400 text-center py-8">
                            <i class="fas fa-database text-4xl mb-2"></i>
                            <p>DB 선택하기 버튼을 클릭하여 테이블을 선택하세요</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 실험 기록 탭 -->
            <section id="experiment-tab" class="tab-content">
                <div class="grid grid-cols-2 gap-6">
                    <!-- 실험 기록 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold">실험 기록</h3>
                            <div class="flex space-x-2">
                                <button 
                                    id="show-survey-modal"
                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm"
                                >
                                    학생설문
                                </button>
                                <button 
                                    id="show-analysis-modal"
                                    class="px-3 py-1 bg-green-500 hover:bg-green-600 rounded text-sm"
                                >
                                    분석보고서
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3 max-h-96 overflow-y-auto" id="experiment-results">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>

                    <!-- 가설 기록 -->
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <h3 class="text-xl font-semibold mb-4">가설 기록 (미래 실험 추천)</h3>
                        
                        <!-- 가설 입력 -->
                        <div class="mb-4">
                            <textarea
                                id="new-hypothesis"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                rows="3"
                                placeholder="새로운 가설을 입력하세요..."
                            ></textarea>
                            <button
                                id="add-hypothesis"
                                class="mt-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 rounded text-sm disabled:opacity-50"
                            >
                                가설 추가
                            </button>
                        </div>
                        
                        <!-- 가설 목록 -->
                        <div class="space-y-3 max-h-64 overflow-y-auto" id="hypotheses-list">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- 푸터 상태바 -->
        <footer class="fixed bottom-0 left-0 right-0 bg-white/10 backdrop-blur-md border-t border-white/20 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6 text-sm text-gray-300">
                    <span class="flex items-center">
                        <i class="fas fa-calendar mr-2"></i>
                        기간: <span id="footer-duration">8</span>주
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-brain mr-2"></i>
                        피드백: <span id="footer-feedback">0</span>개 선택
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-target mr-2"></i>
                        추적: <span id="footer-tracking">0</span>개 활성
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="text-sm text-blue-400 hover:text-blue-300 flex items-center">
                        <i class="fas fa-book-open mr-1"></i>
                        실험 가이드
                    </button>
                    <button class="text-sm text-purple-400 hover:text-purple-300 flex items-center">
                        <i class="fas fa-info mr-1"></i>
                        도움말
                    </button>
                </div>
            </div>
        </footer>
    </div>

    <!-- 피드백 선택 모달 -->
    <div id="feedback-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold" id="feedback-modal-title">피드백 선택</h3>
                <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 gap-3" id="feedback-list">
                <!-- JavaScript로 동적 생성 -->
            </div>
            
            <div class="flex justify-end mt-6">
                <button class="close-modal px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                    확인
                </button>
            </div>
        </div>
    </div>

    <!-- 추적 설정 추가 모달 -->
    <div id="tracking-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">추적 설정 추가</h3>
                <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="tracking-form">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">설정명</label>
                        <input
                            type="text"
                            id="tracking-name"
                            class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="예: 문제해결 속도 향상도"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">설명</label>
                        <textarea
                            id="tracking-description"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            rows="3"
                            placeholder="추적 목적과 내용을 설명해주세요..."
                        ></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" class="close-modal px-6 py-2 bg-gray-500 hover:bg-gray-600 rounded-lg">
                        취소
                    </button>
                    <button type="submit" class="px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                        추가
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 설문 모달 -->
    <div id="survey-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">실험별 맞춤 설문</h3>
                <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- 설문 생성 도구 -->
                <div class="bg-white/5 p-4 rounded-lg">
                    <h4 class="font-semibold mb-3">새 설문 항목 추가</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">질문 유형</label>
                            <select class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm">
                                <option>Likert 척도 (5점)</option>
                                <option>객관식 (4지선다)</option>
                                <option>주관식 (서술형)</option>
                                <option>평점 (10점 척도)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">카테고리</label>
                            <select class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm">
                                <option>메타인지 인식</option>
                                <option>학습 만족도</option>
                                <option>피드백 효과성</option>
                                <option>자기효능감</option>
                                <option>학습 동기</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">질문 내용</label>
                        <textarea
                            class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm"
                            rows="2"
                            placeholder="예: 메타인지 피드백을 받은 후 문제 해결 능력이 향상되었다고 느낍니까?"
                        ></textarea>
                    </div>
                    <button class="mt-3 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded text-sm">
                        질문 추가
                    </button>
                </div>

                <!-- 기존 설문 목록 -->
                <div>
                    <h4 class="font-semibold mb-3">실험 맞춤 설문 항목</h4>
                    <div class="space-y-3" id="survey-items">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 분석 보고서 모달 -->
    <div id="analysis-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-5xl max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">분석 보고서</h3>
                <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="prose max-w-none bg-white/95 p-6 rounded-lg text-gray-800" id="analysis-report">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </div>
    </div>

    <!-- DB 정보 모달 -->
    <div id="db-info-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">
                    <i class="fas fa-database mr-2"></i>
                    mathking 데이터베이스 정보
                </h3>
                <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 p-4 rounded-lg">
                        <h5 class="font-semibold mb-2">연결 정보</h5>
                        <div class="text-sm space-y-1">
                            <p><span class="text-gray-400">호스트:</span> localhost</p>
                            <p><span class="text-gray-400">데이터베이스:</span> mathking</p>
                            <p><span class="text-gray-400">문자셋:</span> utf8mb4</p>
                        </div>
                    </div>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <h5 class="font-semibold mb-2">통계</h5>
                        <div class="text-sm space-y-1" id="db-stats">
                            <p><span class="text-gray-400">총 테이블:</span> <span id="total-tables">로딩중...</span></p>
                            <p><span class="text-gray-400">총 레코드:</span> <span id="total-records">로딩중...</span></p>
                            <p><span class="text-gray-400">DB 크기:</span> <span id="db-size">로딩중...</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/5 p-4 rounded-lg">
                    <h5 class="font-semibold mb-2">최근 업데이트된 테이블</h5>
                    <div class="text-sm" id="recent-tables">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DB 테이블 선택 모달 -->
    <div id="dbTablesModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">
                    <i class="fas fa-database mr-2"></i>
                    DB 테이블 선택
                </h3>
                <button onclick="closeModal('dbTablesModal')" class="p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- 검색 -->
            <div class="mb-4">
                <input
                    type="text"
                    id="modalTableSearchInput"
                    class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="테이블 검색..."
                    onkeyup="searchModalTables()"
                />
            </div>
            
            <!-- 테이블 목록 -->
            <div class="space-y-2 max-h-96 overflow-y-auto mb-4" id="modalDbTablesList">
                <div class="text-gray-400 text-center py-4">테이블 목록을 로딩중...</div>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-400" id="modalTablesInfo">-</div>
                <div class="flex space-x-2" id="modalPagination">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 테이블 데이터 미리보기 모달 -->
    <div id="tablePreviewModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-6xl max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold" id="previewTableTitle">
                    <i class="fas fa-table mr-2"></i>
                    테이블 미리보기
                </h3>
                <button onclick="closeModal('tablePreviewModal')" class="p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="previewTableContent">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </div>
    </div>

    <!-- DB 정보 모달 -->
    <div id="dbInfoModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">
                    <i class="fas fa-info-circle mr-2"></i>
                    DB 정보
                </h3>
                <button onclick="closeModal('dbInfoModal')" class="p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="dbInfoContent">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </div>
    </div>

    <script src="experiment.js"></script>
</body>
</html>