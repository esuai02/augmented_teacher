<?php
/**
 * 패턴 데이터 테스트 페이지
 * DB에 저장된 패턴 데이터를 확인
 */

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login();

// 테이블 존재 확인
$table_exists = $DB->get_manager()->table_exists('alt42i_math_patterns');
$pattern_count = $table_exists ? $DB->count_records('alt42i_math_patterns') : 0;
$category_count = $DB->count_records('alt42i_pattern_categories');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>패턴 데이터 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info-box {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .pattern-list {
            margin-top: 20px;
        }
        .pattern-item {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .pattern-header {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .pattern-details {
            color: #666;
            font-size: 14px;
        }
        .category-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #667eea;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        .test-api {
            margin: 20px 0;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 5px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #5a67d8;
        }
        .api-result {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>수학 학습 패턴 데이터 테스트</h1>
        
        <div class="info-box">
            <h2>데이터베이스 상태</h2>
            <p>테이블 존재: <?php echo $table_exists ? '✅ 예' : '❌ 아니오'; ?></p>
            <p>카테고리 수: <?php echo $category_count; ?>개</p>
            <p>패턴 수: <?php echo $pattern_count; ?>개</p>
            <p>현재 사용자 ID: <?php echo $USER->id; ?></p>
        </div>

        <div class="test-api">
            <h2>API 테스트</h2>
            <button onclick="testAPI()">API 호출 테스트</button>
            <div id="api-result" class="api-result" style="display:none;"></div>
        </div>

        <h2>저장된 패턴 목록</h2>
        <div class="pattern-list">
            <?php
            if ($pattern_count > 0) {
                $patterns = $DB->get_records_sql("
                    SELECT p.*, c.category_name, c.category_code
                    FROM {alt42i_math_patterns} p
                    LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
                    ORDER BY p.pattern_id ASC
                    LIMIT 10
                ");
                
                foreach ($patterns as $pattern) {
                    echo '<div class="pattern-item">';
                    echo '<div class="pattern-header">';
                    echo '#' . str_pad($pattern->pattern_id, 2, '0', STR_PAD_LEFT) . ' - ' . htmlspecialchars($pattern->pattern_name);
                    echo '<span class="category-badge">' . htmlspecialchars($pattern->category_name) . '</span>';
                    echo '</div>';
                    echo '<div class="pattern-details">';
                    echo '<p>' . htmlspecialchars($pattern->pattern_desc) . '</p>';
                    echo '<p>우선순위: ' . $pattern->priority . ' | 활성: ' . ($pattern->is_active ? '예' : '아니오') . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
                
                if ($pattern_count > 10) {
                    echo '<p style="text-align: center; color: #666;">... 그리고 ' . ($pattern_count - 10) . '개 더</p>';
                }
            } else {
                echo '<p>저장된 패턴이 없습니다. 데이터베이스 설정을 확인해주세요.</p>';
            }
            ?>
        </div>

        <h2>카테고리 목록</h2>
        <div class="pattern-list">
            <?php
            $categories = $DB->get_records('alt42i_pattern_categories', null, 'display_order ASC');
            foreach ($categories as $category) {
                echo '<div class="pattern-item">';
                echo '<div class="pattern-header">' . htmlspecialchars($category->category_name) . '</div>';
                echo '<div class="pattern-details">';
                echo '<p>코드: ' . $category->category_code . '</p>';
                echo '<p>' . htmlspecialchars($category->description) . '</p>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script>
    function testAPI() {
        const resultDiv = document.getElementById('api-result');
        resultDiv.style.display = 'block';
        resultDiv.textContent = 'API 호출 중...';
        
        fetch('api/get_math_patterns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: <?php echo $USER->id; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.textContent = JSON.stringify(data, null, 2);
            console.log('API 응답:', data);
        })
        .catch(error => {
            resultDiv.textContent = 'Error: ' + error.message;
            console.error('API 오류:', error);
        });
    }
    </script>
</body>
</html>