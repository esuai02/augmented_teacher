<?php
/**
 * 웹 기반 마이그레이션 관리자
 */

require_once __DIR__ . '/plugin_db_config.php';

$step = $_GET['step'] ?? 'check';
$message = '';
$success = false;

try {
    $pdo = getDBConnection();
    
    switch ($step) {
        case 'check':
            // 현재 상태 확인
            $tables = [
                'mdl_alt42DB_card_plugin_settings' => '기존 테이블',
                'mdl_alt42DB_card_plugin_settings_new' => '새 테이블',
                'mdl_alt42DB_card_plugin_settings_old' => '백업 테이블',
                'mdl_alt42DB_card_plugin_settings_backup' => '백업 테이블2'
            ];
            
            $tableStatus = [];
            foreach ($tables as $table => $desc) {
                $exists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch() ? true : false;
                $count = 0;
                if ($exists) {
                    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                }
                $tableStatus[$table] = [
                    'desc' => $desc,
                    'exists' => $exists,
                    'count' => $count
                ];
            }
            break;
            
        case 'create_table':
            // 새 테이블 생성
            $sql = file_get_contents(__DIR__ . '/create_new_card_plugin_settings_table.sql');
            
            // 주석 제거
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // SQL을 개별 쿼리로 분리
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            $errors = [];
            $successes = 0;
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                        $successes++;
                    } catch (PDOException $e) {
                        $errors[] = substr($query, 0, 50) . "... : " . $e->getMessage();
                    }
                }
            }
            
            // 새 테이블 존재 확인
            $checkTable = $pdo->query("SHOW TABLES LIKE 'mdl_alt42DB_card_plugin_settings_new'")->fetch();
            
            if ($checkTable) {
                $message = "새 테이블이 성공적으로 생성되었습니다. (성공: {$successes}개 쿼리)";
                $success = true;
            } else {
                $message = "테이블 생성 중 오류가 발생했습니다. " . implode(', ', $errors);
                $success = false;
            }
            
            header("Location: migration_manager.php?step=check&msg=" . urlencode($message));
            exit;
            
        case 'migrate_data':
            // 데이터 마이그레이션
            include __DIR__ . '/migrate_plugin_config_data.php';
            exit;
            
        case 'validate':
            // 검증
            include __DIR__ . '/validate_migration.php';
            exit;
            
        case 'switch_tables':
            // 테이블 전환
            $sql = file_get_contents(__DIR__ . '/switch_to_new_table.sql');
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($queries as $query) {
                if (!empty($query) && !preg_match('/^\s*--/m', $query) && !preg_match('/^\s*SELECT/i', $query)) {
                    $pdo->exec($query);
                }
            }
            
            $message = "테이블이 성공적으로 전환되었습니다.";
            $success = true;
            header("Location: migration_manager.php?step=check&msg=" . urlencode($message));
            exit;
    }
    
} catch (Exception $e) {
    $message = "오류: " . $e->getMessage();
}

// 메시지 확인
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터베이스 마이그레이션 관리자</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .status-table th,
        .status-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .status-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .exists { color: #28a745; }
        .not-exists { color: #dc3545; }
        .button-group {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .steps {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h3 {
            margin-top: 0;
            color: #495057;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 5px 0;
            line-height: 1.6;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>데이터베이스 마이그레이션 관리자</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'check'): ?>
            <h2>현재 테이블 상태</h2>
            <table class="status-table">
                <thead>
                    <tr>
                        <th>테이블 이름</th>
                        <th>설명</th>
                        <th>상태</th>
                        <th>레코드 수</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableStatus as $table => $info): ?>
                        <tr>
                            <td><code><?php echo $table; ?></code></td>
                            <td><?php echo $info['desc']; ?></td>
                            <td class="<?php echo $info['exists'] ? 'exists' : 'not-exists'; ?>">
                                <?php echo $info['exists'] ? '✓ 존재' : '✗ 없음'; ?>
                            </td>
                            <td><?php echo $info['exists'] ? $info['count'] . '개' : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="steps">
                <h3>마이그레이션 단계</h3>
                <ol>
                    <li><strong>새 테이블 생성</strong> - 정규화된 구조의 새 테이블을 생성합니다.</li>
                    <li><strong>데이터 마이그레이션</strong> - 기존 JSON 데이터를 개별 컬럼으로 이전합니다.</li>
                    <li><strong>검증</strong> - 데이터가 올바르게 이전되었는지 확인합니다.</li>
                    <li><strong>테이블 전환</strong> - 새 테이블을 활성화합니다.</li>
                </ol>
            </div>
            
            <div class="warning">
                <strong>⚠️ 주의사항:</strong>
                <ul>
                    <li>작업 전 반드시 데이터베이스를 백업하세요.</li>
                    <li>각 단계는 순서대로 진행해야 합니다.</li>
                    <li>검증 단계에서 문제가 발견되면 테이블 전환을 진행하지 마세요.</li>
                </ul>
            </div>
            
            <div class="button-group">
                <?php 
                $newTableExists = $tableStatus['mdl_alt42DB_card_plugin_settings_new']['exists'];
                $oldTableExists = $tableStatus['mdl_alt42DB_card_plugin_settings']['exists'];
                $hasData = $oldTableExists && $tableStatus['mdl_alt42DB_card_plugin_settings']['count'] > 0;
                $newHasData = $newTableExists && $tableStatus['mdl_alt42DB_card_plugin_settings_new']['count'] > 0;
                ?>
                
                <?php if (!$newTableExists): ?>
                    <a href="?step=create_table" class="btn btn-primary" 
                       onclick="return confirm('새 테이블을 생성하시겠습니까?');">
                        1. 새 테이블 생성
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary" disabled>✓ 새 테이블 생성됨</button>
                <?php endif; ?>
                
                <?php if ($newTableExists && $hasData && !$newHasData): ?>
                    <a href="?step=migrate_data" class="btn btn-success">
                        2. 데이터 마이그레이션
                    </a>
                <?php elseif ($newHasData): ?>
                    <button class="btn btn-success" disabled>✓ 데이터 마이그레이션 완료</button>
                <?php else: ?>
                    <button class="btn btn-success" disabled>2. 데이터 마이그레이션</button>
                <?php endif; ?>
                
                <?php if ($newHasData): ?>
                    <a href="?step=validate" class="btn btn-warning">
                        3. 마이그레이션 검증
                    </a>
                <?php else: ?>
                    <button class="btn btn-warning" disabled>3. 마이그레이션 검증</button>
                <?php endif; ?>
                
                <?php if ($newHasData && !$tableStatus['mdl_alt42DB_card_plugin_settings_old']['exists']): ?>
                    <a href="?step=switch_tables" class="btn btn-danger"
                       onclick="return confirm('테이블을 전환하시겠습니까? 이 작업은 되돌리기 어렵습니다.');">
                        4. 테이블 전환
                    </a>
                <?php elseif ($tableStatus['mdl_alt42DB_card_plugin_settings_old']['exists']): ?>
                    <button class="btn btn-danger" disabled>✓ 테이블 전환 완료</button>
                <?php else: ?>
                    <button class="btn btn-danger" disabled>4. 테이블 전환</button>
                <?php endif; ?>
            </div>
            
            <div class="button-group" style="margin-top: 30px;">
                <a href="test_new_table_structure.html" class="btn btn-primary" target="_blank">
                    테스트 페이지 열기
                </a>
                <a href="?" class="btn btn-primary">
                    새로고침
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>