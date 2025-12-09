<?php
/**
 * reflections3 컬럼 확인 및 추가 스크립트
 * File: check_and_add_reflections3.php
 *
 * 용도: mdl_icontent_pages 테이블에 reflections3 컬럼이 있는지 확인하고 없으면 추가
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
require_capability('moodle/site:config', context_system::instance());

echo "<h1>reflections3 컬럼 확인 및 추가</h1>";

// 1. 테이블 구조 확인
echo "<h2>1. 현재 테이블 구조 확인</h2>";

$sql = "SHOW COLUMNS FROM {icontent_pages} LIKE 'reflections%'";
try {
    $columns = $DB->get_records_sql($sql);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    $hasReflections3 = false;
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col->field}</strong></td>";
        echo "<td>{$col->type}</td>";
        echo "<td>{$col->null}</td>";
        echo "<td>{$col->key}</td>";
        echo "<td>{$col->default}</td>";
        echo "</tr>";

        if($col->field === 'reflections3') {
            $hasReflections3 = true;
        }
    }
    echo "</table>";

    // 2. reflections3 컬럼 존재 여부 확인
    echo "<h2>2. reflections3 컬럼 상태</h2>";

    if($hasReflections3) {
        echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50;'>";
        echo "✅ <strong>reflections3 컬럼이 이미 존재합니다!</strong><br>";
        echo "추가 작업이 필요 없습니다.";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800;'>";
        echo "⚠️ <strong>reflections3 컬럼이 없습니다!</strong><br>";
        echo "컬럼을 추가해야 합니다.";
        echo "</div>";

        // 3. 컬럼 추가
        echo "<h2>3. reflections3 컬럼 추가</h2>";

        // reflections0의 타입을 참고하여 동일한 타입으로 생성
        $sql = "SHOW COLUMNS FROM {icontent_pages} WHERE Field = 'reflections0'";
        $reflections0_col = $DB->get_record_sql($sql);

        if($reflections0_col) {
            echo "<p>reflections0 컬럼 타입: <strong>{$reflections0_col->type}</strong></p>";
            echo "<p>동일한 타입으로 reflections3 컬럼을 생성합니다...</p>";

            $alter_sql = "ALTER TABLE {icontent_pages}
                         ADD COLUMN reflections3 {$reflections0_col->type} NULL
                         AFTER reflections2";

            try {
                $DB->execute($alter_sql);

                echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50;'>";
                echo "✅ <strong>reflections3 컬럼이 성공적으로 추가되었습니다!</strong><br>";
                echo "이제 generate_essay_instruction.php가 정상적으로 작동할 것입니다.";
                echo "</div>";

                // 4. 추가 확인
                echo "<h2>4. 추가 확인</h2>";
                $sql = "SHOW COLUMNS FROM {icontent_pages} WHERE Field = 'reflections3'";
                $new_col = $DB->get_record_sql($sql);

                if($new_col) {
                    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                    echo "<tr><th>컬럼명</th><th>타입</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                    echo "<tr>";
                    echo "<td><strong>{$new_col->field}</strong></td>";
                    echo "<td>{$new_col->type}</td>";
                    echo "<td>{$new_col->null}</td>";
                    echo "<td>{$new_col->key}</td>";
                    echo "<td>{$new_col->default}</td>";
                    echo "</tr>";
                    echo "</table>";
                }

            } catch (Exception $e) {
                echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
                echo "❌ <strong>컬럼 추가 실패!</strong><br>";
                echo "오류: " . htmlspecialchars($e->getMessage());
                echo "<br><br>수동으로 다음 SQL을 실행하세요:<br>";
                echo "<code style='background: #f5f5f5; padding: 10px; display: block; margin-top: 10px;'>";
                echo htmlspecialchars($alter_sql);
                echo "</code>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
            echo "❌ reflections0 컬럼을 찾을 수 없어 타입을 확인할 수 없습니다.<br>";
            echo "수동으로 다음 SQL을 실행하세요:<br>";
            echo "<code style='background: #f5f5f5; padding: 10px; display: block; margin-top: 10px;'>";
            echo "ALTER TABLE mdl_icontent_pages ADD COLUMN reflections3 LONGTEXT NULL AFTER reflections2;";
            echo "</code>";
            echo "</div>";
        }
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "❌ <strong>오류 발생!</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

// 5. 다음 단계 안내
echo "<h2>5. 다음 단계</h2>";
echo "<ol>";
echo "<li>이 페이지를 새로고침하여 reflections3 컬럼이 정상적으로 추가되었는지 확인</li>";
echo "<li>mynote_test.php로 이동하여 '절차기억 생성' 버튼 클릭</li>";
echo "<li>debug_reflections3.php로 데이터가 정상 저장되었는지 확인</li>";
echo "<li>mynote_test.php에서 단계별 재생 UI가 나오는지 확인</li>";
echo "</ol>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='check_and_add_reflections3.php' style='display: inline-block; padding: 12px 24px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>이 페이지 새로고침</a>";
echo "<a href='mynote_test.php?dmn=&cid=106&nch=9&cmid=87718&quizid=&page=1&studentid=2' style='display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>mynote_test.php로 이동</a>";
echo "</div>";
?>
