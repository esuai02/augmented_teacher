<?php
/**
 * Database Status Check for Math Cognitive Inertia Library
 * Checks if all 60 personas are properly inserted
 */

// Moodle 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Database Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .pattern-row:hover { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>수학 인지관성 도감 데이터베이스 상태 확인</h1>
    
    <?php
    // 1. 테이블 존재 확인
    echo "<h2>1. 테이블 상태</h2>";
    $tables = [
        'alt42i_pattern_categories' => '카테고리',
        'alt42i_math_patterns' => '패턴',
        'alt42i_pattern_solutions' => '솔루션',
        'alt42i_audio_files' => '오디오 파일',
        'alt42i_user_pattern_progress' => '사용자 진행상황'
    ];
    
    foreach ($tables as $table => $name) {
        $exists = $DB->get_manager()->table_exists($table);
        if ($exists) {
            $count = $DB->count_records($table);
            echo "<div class='status success'>✓ {$name} 테이블 ({$table}): {$count}개 레코드</div>";
        } else {
            echo "<div class='status error'>✗ {$name} 테이블 ({$table}): 존재하지 않음</div>";
        }
    }
    
    // 2. 카테고리 데이터 확인
    echo "<h2>2. 카테고리 데이터</h2>";
    try {
        $categories = $DB->get_records('alt42i_pattern_categories', null, 'display_order ASC');
        if (!empty($categories)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>코드</th><th>이름</th><th>순서</th></tr>";
            foreach ($categories as $cat) {
                echo "<tr>";
                echo "<td>{$cat->id}</td>";
                echo "<td>{$cat->category_code}</td>";
                echo "<td>{$cat->category_name}</td>";
                echo "<td>{$cat->display_order}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status error'>카테고리 데이터가 없습니다.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='status error'>카테고리 조회 오류: " . $e->getMessage() . "</div>";
    }
    
    // 3. 패턴 데이터 확인 (처음 10개만)
    echo "<h2>3. 패턴 데이터 (처음 10개)</h2>";
    try {
        $patterns = $DB->get_records_sql("
            SELECT p.*, c.category_name 
            FROM {alt42i_math_patterns} p
            LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
            ORDER BY p.pattern_id ASC
            LIMIT 10
        ");
        
        if (!empty($patterns)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>패턴ID</th><th>이름</th><th>카테고리</th><th>아이콘</th><th>우선순위</th><th>활성</th></tr>";
            foreach ($patterns as $pattern) {
                echo "<tr class='pattern-row'>";
                echo "<td>{$pattern->id}</td>";
                echo "<td>{$pattern->pattern_id}</td>";
                echo "<td>{$pattern->pattern_name}</td>";
                echo "<td>{$pattern->category_name}</td>";
                echo "<td>{$pattern->icon}</td>";
                echo "<td>{$pattern->priority}</td>";
                echo "<td>" . ($pattern->is_active ? '✓' : '✗') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $total = $DB->count_records('alt42i_math_patterns');
            echo "<div class='status info'>전체 패턴 수: {$total}개</div>";
        } else {
            echo "<div class='status error'>패턴 데이터가 없습니다.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='status error'>패턴 조회 오류: " . $e->getMessage() . "</div>";
    }
    
    // 4. 오디오 파일 확인
    echo "<h2>4. 오디오 파일 상태</h2>";
    try {
        $audio_files = $DB->get_records_sql("
            SELECT a.*, p.pattern_name 
            FROM {alt42i_audio_files} a
            JOIN {alt42i_math_patterns} p ON a.pattern_id = p.id
            ORDER BY p.pattern_id ASC
            LIMIT 5
        ");
        
        if (!empty($audio_files)) {
            echo "<table>";
            echo "<tr><th>패턴</th><th>파일 경로</th><th>유형</th></tr>";
            foreach ($audio_files as $audio) {
                echo "<tr>";
                echo "<td>{$audio->pattern_name}</td>";
                echo "<td>{$audio->file_path}</td>";
                echo "<td>{$audio->file_type}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $total_audio = $DB->count_records('alt42i_audio_files');
            echo "<div class='status info'>전체 오디오 파일 수: {$total_audio}개</div>";
        } else {
            echo "<div class='status error'>오디오 파일 데이터가 없습니다.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='status error'>오디오 파일 조회 오류: " . $e->getMessage() . "</div>";
    }
    
    // 5. API 테스트
    echo "<h2>5. API 연결 테스트</h2>";
    ?>
    
    <button onclick="testAPI()" class="test-btn">API 테스트</button>
    <div id="api-result" style="margin-top: 10px; padding: 10px; background: #f0f0f0; display: none;">
        <pre id="api-content"></pre>
    </div>
    
    <script>
    function testAPI() {
        const resultDiv = document.getElementById('api-result');
        const contentPre = document.getElementById('api-content');
        
        resultDiv.style.display = 'block';
        contentPre.textContent = 'API 호출 중...';
        
        fetch('api/get_math_patterns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            contentPre.textContent = JSON.stringify(data, null, 2);
            
            if (data.success) {
                resultDiv.className = 'status success';
                console.log('패턴 수:', data.patterns.length);
                console.log('카테고리 수:', data.categories.length);
            } else {
                resultDiv.className = 'status error';
            }
        })
        .catch(error => {
            contentPre.textContent = 'Error: ' + error.message;
            resultDiv.className = 'status error';
        });
    }
    </script>
    
    <h2>6. 데이터 삽입 도구</h2>
    <p>데이터가 없는 경우 아래 버튼을 클릭하여 60personas.txt의 데이터를 삽입할 수 있습니다:</p>
    <a href="insert_60_personas_data.php" class="test-btn" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">60 페르소나 데이터 삽입</a>
    
</body>
</html>