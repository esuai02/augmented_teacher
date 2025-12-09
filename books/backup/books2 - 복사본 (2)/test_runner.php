<?php
/**
 * File: test_runner.php
 * Purpose: 맞춤형 컨텐츠 시스템 자동 테스트 러너
 * Location: /mnt/c/1 Project/augmented_teacher/books/test_runner.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>맞춤형 컨텐츠 시스템 테스트</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .phase {
            margin: 30px 0;
            padding: 20px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
        }
        .phase h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .test-item {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.pass {
            background: #2ecc71;
            color: white;
        }
        .status.fail {
            background: #e74c3c;
            color: white;
        }
        .status.pending {
            background: #95a5a6;
            color: white;
        }
        .status.running {
            background: #f39c12;
            color: white;
        }
        .details {
            margin-top: 10px;
            padding: 10px;
            background: #ecf0f1;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 5px;
            text-decoration: none;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn.danger {
            background: #e74c3c;
        }
        .btn.danger:hover {
            background: #c0392b;
        }
        .summary {
            margin: 20px 0;
            padding: 20px;
            background: #3498db;
            color: white;
            border-radius: 8px;
        }
        .summary h3 {
            margin-top: 0;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 맞춤형 컨텐츠 시스템 테스트</h1>

        <div class="summary">
            <h3>테스트 개요</h3>
            <p>AI 생성 맞춤형 컨텐츠 시스템의 전체 워크플로우를 테스트합니다.</p>
            <p><strong>테스트 대상:</strong> DB 테이블 생성 → AI 생성 → DB 저장 → 수식 렌더링</p>
        </div>

        <!-- Phase 1: 데이터베이스 테이블 -->
        <div class="phase">
            <h2>Phase 1: 데이터베이스 테이블 확인</h2>

            <div class="test-item">
                <strong>테스트 1.1: 테이블 존재 여부</strong>
                <?php
                try {
                    $tableName = 'abessi_tailoredcontents';
                    $tableExists = $DB->get_manager()->table_exists($tableName);

                    if ($tableExists) {
                        echo '<span class="status pass">PASS</span>';
                        echo '<div class="details">✓ 테이블 "' . $tableName . '" 존재</div>';
                    } else {
                        echo '<span class="status fail">FAIL</span>';
                        echo '<div class="details">✗ 테이블이 존재하지 않습니다. create_tailored_contents_table.php를 먼저 실행하세요.</div>';
                    }
                } catch (Exception $e) {
                    echo '<span class="status fail">ERROR</span>';
                    echo '<div class="details">오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 1.2: 테이블 구조 확인</strong>
                <?php
                if ($tableExists) {
                    try {
                        $columns = $DB->get_columns('abessi_tailoredcontents');
                        $requiredColumns = ['id', 'contentstype', 'contentsid', 'nstep',
                                           'qstn0', 'qstn1', 'qstn2', 'qstn3',
                                           'ans0', 'ans1', 'ans2', 'ans3',
                                           'timemodified', 'timecreated'];

                        $missingColumns = [];
                        foreach ($requiredColumns as $col) {
                            if (!isset($columns[$col])) {
                                $missingColumns[] = $col;
                            }
                        }

                        if (empty($missingColumns)) {
                            echo '<span class="status pass">PASS</span>';
                            echo '<div class="details">✓ 모든 필수 컬럼 존재 (' . count($columns) . '개)</div>';
                        } else {
                            echo '<span class="status fail">FAIL</span>';
                            echo '<div class="details">✗ 누락된 컬럼: ' . implode(', ', $missingColumns) . '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<span class="status fail">ERROR</span>';
                        echo '<div class="details">오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                    echo '<div class="details">테이블이 존재하지 않아 건너뜁니다.</div>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 1.3: 레코드 수 확인</strong>
                <?php
                if ($tableExists) {
                    try {
                        $count = $DB->count_records('abessi_tailoredcontents');
                        echo '<span class="status pass">INFO</span>';
                        echo '<div class="details">현재 레코드 수: ' . $count . '개</div>';
                    } catch (Exception $e) {
                        echo '<span class="status fail">ERROR</span>';
                        echo '<div class="details">오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                }
                ?>
            </div>
        </div>

        <!-- Phase 2: 샘플 데이터 테스트 -->
        <div class="phase">
            <h2>Phase 2: 샘플 데이터 생성 테스트</h2>

            <div class="test-item">
                <strong>테스트 2.1: 샘플 레코드 생성</strong>
                <?php
                if ($tableExists) {
                    try {
                        // 테스트용 샘플 데이터
                        $testRecord = new stdClass();
                        $testRecord->contentsid = 99999;
                        $testRecord->contentstype = 1;
                        $testRecord->nstep = 999;
                        $testRecord->qstn0 = '테스트 자세히 생각하기 내용';
                        $testRecord->qstn1 = '테스트 질문 1';
                        $testRecord->qstn2 = '테스트 질문 2';
                        $testRecord->qstn3 = '테스트 질문 3';
                        $testRecord->ans0 = '';
                        $testRecord->ans1 = '';
                        $testRecord->ans2 = '';
                        $testRecord->ans3 = '';
                        $testRecord->timecreated = time();
                        $testRecord->timemodified = time();

                        // 기존 테스트 레코드 삭제
                        $DB->delete_records('abessi_tailoredcontents', array(
                            'contentsid' => 99999,
                            'contentstype' => 1,
                            'nstep' => 999
                        ));

                        // 새로 삽입
                        $insertedId = $DB->insert_record('abessi_tailoredcontents', $testRecord);

                        echo '<span class="status pass">PASS</span>';
                        echo '<div class="details">✓ 샘플 레코드 생성 성공 (ID: ' . $insertedId . ')</div>';

                    } catch (Exception $e) {
                        echo '<span class="status fail">FAIL</span>';
                        echo '<div class="details">✗ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 2.2: 샘플 레코드 조회</strong>
                <?php
                if ($tableExists) {
                    try {
                        $record = $DB->get_record('abessi_tailoredcontents', array(
                            'contentsid' => 99999,
                            'contentstype' => 1,
                            'nstep' => 999
                        ));

                        if ($record && $record->qstn1 === '테스트 질문 1') {
                            echo '<span class="status pass">PASS</span>';
                            echo '<div class="details">✓ 레코드 조회 및 데이터 검증 성공</div>';
                        } else {
                            echo '<span class="status fail">FAIL</span>';
                            echo '<div class="details">✗ 레코드 조회 실패 또는 데이터 불일치</div>';
                        }

                    } catch (Exception $e) {
                        echo '<span class="status fail">FAIL</span>';
                        echo '<div class="details">✗ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 2.3: 샘플 레코드 업데이트</strong>
                <?php
                if ($tableExists) {
                    try {
                        $record = $DB->get_record('abessi_tailoredcontents', array(
                            'contentsid' => 99999,
                            'contentstype' => 1,
                            'nstep' => 999
                        ));

                        if ($record) {
                            $record->ans1 = '테스트 답변 1';
                            $record->timemodified = time();
                            $DB->update_record('abessi_tailoredcontents', $record);

                            // 업데이트 확인
                            $updatedRecord = $DB->get_record('abessi_tailoredcontents', array('id' => $record->id));

                            if ($updatedRecord->ans1 === '테스트 답변 1') {
                                echo '<span class="status pass">PASS</span>';
                                echo '<div class="details">✓ 레코드 업데이트 성공</div>';
                            } else {
                                echo '<span class="status fail">FAIL</span>';
                                echo '<div class="details">✗ 업데이트 후 데이터 검증 실패</div>';
                            }
                        } else {
                            echo '<span class="status fail">FAIL</span>';
                            echo '<div class="details">✗ 업데이트할 레코드를 찾을 수 없습니다.</div>';
                        }

                    } catch (Exception $e) {
                        echo '<span class="status fail">FAIL</span>';
                        echo '<div class="details">✗ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 2.4: 샘플 레코드 삭제</strong>
                <?php
                if ($tableExists) {
                    try {
                        $deleted = $DB->delete_records('abessi_tailoredcontents', array(
                            'contentsid' => 99999,
                            'contentstype' => 1,
                            'nstep' => 999
                        ));

                        echo '<span class="status pass">PASS</span>';
                        echo '<div class="details">✓ 테스트 레코드 정리 완료</div>';

                    } catch (Exception $e) {
                        echo '<span class="status fail">FAIL</span>';
                        echo '<div class="details">✗ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<span class="status pending">SKIP</span>';
                }
                ?>
            </div>
        </div>

        <!-- Phase 3: API 파일 확인 -->
        <div class="phase">
            <h2>Phase 3: API 파일 존재 확인</h2>

            <div class="test-item">
                <strong>테스트 3.1: generate_detailed_thinking.php</strong>
                <?php
                $file1 = __DIR__ . '/generate_detailed_thinking.php';
                if (file_exists($file1)) {
                    echo '<span class="status pass">PASS</span>';
                    echo '<div class="details">✓ 파일 존재 (' . filesize($file1) . ' bytes)</div>';
                } else {
                    echo '<span class="status fail">FAIL</span>';
                    echo '<div class="details">✗ 파일이 존재하지 않습니다: ' . $file1 . '</div>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 3.2: get_additional_answer.php</strong>
                <?php
                $file2 = __DIR__ . '/get_additional_answer.php';
                if (file_exists($file2)) {
                    echo '<span class="status pass">PASS';
                    echo '<div class="details">✓ 파일 존재 (' . filesize($file2) . ' bytes)</div>';
                } else {
                    echo '<span class="status fail">FAIL</span>';
                    echo '<div class="details">✗ 파일이 존재하지 않습니다: ' . $file2 . '</div>';
                }
                ?>
            </div>

            <div class="test-item">
                <strong>테스트 3.3: drillingmath.php</strong>
                <?php
                $file3 = __DIR__ . '/drillingmath.php';
                if (file_exists($file3)) {
                    echo '<span class="status pass">PASS</span>';
                    echo '<div class="details">✓ 파일 존재 (' . filesize($file3) . ' bytes)</div>';
                } else {
                    echo '<span class="status fail">FAIL</span>';
                    echo '<div class="details">✗ 파일이 존재하지 않습니다: ' . $file3 . '</div>';
                }
                ?>
            </div>
        </div>

        <!-- 액션 버튼 -->
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>다음 단계</h3>
            <p>기본 테스트가 완료되었습니다. 아래 버튼을 클릭하여 실제 동작을 테스트하세요.</p>

            <a href="create_tailored_contents_table.php" class="btn" target="_blank">
                1. 테이블 생성/확인
            </a>

            <a href="drillingmath.php?cid=29566&ctype=1&section=0&nstep=1" class="btn" target="_blank">
                2. 메인 페이지 테스트
            </a>

            <a href="test_runner.php" class="btn">
                🔄 테스트 다시 실행
            </a>
        </div>

        <!-- SQL 쿼리 샘플 -->
        <div style="margin-top: 30px;">
            <h3>유용한 SQL 쿼리</h3>

            <p><strong>모든 레코드 조회:</strong></p>
            <pre>SELECT
    id, contentsid, contentstype, nstep,
    LEFT(qstn0, 50) as qstn0_preview,
    qstn1, qstn2, qstn3,
    LEFT(ans1, 30) as ans1_preview,
    FROM_UNIXTIME(timecreated) as created,
    FROM_UNIXTIME(timemodified) as modified
FROM mdl_abessi_tailoredcontents
ORDER BY timecreated DESC
LIMIT 10;</pre>

            <p><strong>특정 컨텐츠의 모든 구간 조회:</strong></p>
            <pre>SELECT nstep, qstn1, qstn2, qstn3,
       CASE WHEN ans1 != '' THEN 'O' ELSE 'X' END as ans1_exists,
       CASE WHEN ans2 != '' THEN 'O' ELSE 'X' END as ans2_exists,
       CASE WHEN ans3 != '' THEN 'O' ELSE 'X' END as ans3_exists
FROM mdl_abessi_tailoredcontents
WHERE contentsid = 29566 AND contentstype = 1
ORDER BY nstep;</pre>
        </div>

    </div>
</body>
</html>
