<?php
/**
 * askhint 힌트 생성 테스트 파일
 * 
 * 사용법: 브라우저에서 직접 접속
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/test_askhint.php?interaction_id=123
 * 
 * 또는 특정 interaction_id를 테스트:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/test_askhint.php?interaction_id=123&test_api=1
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 askhint 힌트 생성 테스트</h1>";
echo "<hr>";

// 테스트할 interaction_id 가져오기
$interactionId = isset($_GET['interaction_id']) ? intval($_GET['interaction_id']) : 0;
$testApi = isset($_GET['test_api']) ? $_GET['test_api'] === '1' : false;

// interaction_id가 없으면 최근 askhint 레코드 조회
if ($interactionId <= 0) {
    echo "<h2>📋 최근 askhint 레코드 목록</h2>";
    
    $recentInteractions = $DB->get_records_sql(
        "SELECT id, type, userid, contentsid, contentstype, problem_type, 
                problem_image, solution_image, status, timecreated
         FROM {ktm_teaching_interactions} 
         WHERE type = 'askhint'
         ORDER BY id DESC 
         LIMIT 10"
    );
    
    if (empty($recentInteractions)) {
        echo "<p style='color: orange;'>⚠️ askhint 타입의 레코드가 없습니다.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Type</th><th>UserID</th><th>ContentsID</th><th>ContentsType</th>";
        echo "<th>Problem Type</th><th>Problem Image</th><th>Solution Image</th><th>Status</th><th>Created</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($recentInteractions as $interaction) {
            $problemImgStatus = !empty($interaction->problem_image) ? '✅ 있음 (' . strlen($interaction->problem_image) . '자)' : '❌ 없음';
            $solutionImgStatus = !empty($interaction->solution_image) ? '✅ 있음 (' . strlen($interaction->solution_image) . '자)' : '❌ 없음';
            $createdTime = date('Y-m-d H:i:s', $interaction->timecreated);
            
            echo "<tr>";
            echo "<td><strong>{$interaction->id}</strong></td>";
            echo "<td>{$interaction->type}</td>";
            echo "<td>{$interaction->userid}</td>";
            echo "<td>{$interaction->contentsid}</td>";
            echo "<td>{$interaction->contentstype}</td>";
            echo "<td>{$interaction->problem_type}</td>";
            echo "<td>{$problemImgStatus}</td>";
            echo "<td>{$solutionImgStatus}</td>";
            echo "<td>{$interaction->status}</td>";
            echo "<td>{$createdTime}</td>";
            echo "<td><a href='?interaction_id={$interaction->id}'>상세보기</a> | <a href='?interaction_id={$interaction->id}&test_api=1'>API 테스트</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><hr><br>";
    echo "<h2>🔍 특정 Interaction ID로 테스트</h2>";
    echo "<form method='get'>";
    echo "<label>Interaction ID: <input type='number' name='interaction_id' required></label> ";
    echo "<label><input type='checkbox' name='test_api' value='1'> API 테스트 실행</label> ";
    echo "<button type='submit'>테스트</button>";
    echo "</form>";
    
} else {
    // 특정 interaction_id 상세 테스트
    echo "<h2>📝 Interaction ID: {$interactionId} 상세 정보</h2>";
    
    $interaction = $DB->get_record_sql(
        "SELECT id, type, userid, contentsid, contentstype, problem_type, 
                problem_image, solution_image, solution_text, narration_text, status, timecreated
         FROM {ktm_teaching_interactions} 
         WHERE id = ?",
        array($interactionId)
    );
    
    if (!$interaction) {
        echo "<p style='color: red;'>❌ 해당 ID의 레코드를 찾을 수 없습니다.</p>";
        echo "<a href='?'>← 목록으로 돌아가기</a>";
        exit;
    }
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>필드</th><th>값</th><th>상태</th></tr>";
    
    // 각 필드 출력
    $fields = [
        'id' => ['label' => 'ID', 'value' => $interaction->id],
        'type' => ['label' => 'Type', 'value' => $interaction->type],
        'userid' => ['label' => 'User ID', 'value' => $interaction->userid],
        'contentsid' => ['label' => 'Contents ID', 'value' => $interaction->contentsid],
        'contentstype' => ['label' => 'Contents Type', 'value' => $interaction->contentstype],
        'problem_type' => ['label' => 'Problem Type', 'value' => $interaction->problem_type],
        'status' => ['label' => 'Status', 'value' => $interaction->status],
    ];
    
    foreach ($fields as $key => $field) {
        $status = !empty($field['value']) ? '✅' : '⚠️';
        echo "<tr><td><strong>{$field['label']}</strong></td><td>{$field['value']}</td><td>{$status}</td></tr>";
    }
    
    // 이미지 필드 (특별 처리)
    $problemImgValue = $interaction->problem_image ?? '';
    $solutionImgValue = $interaction->solution_image ?? '';
    
    $problemImgStatus = !empty($problemImgValue) ? '✅ 있음' : '❌ 없음';
    $solutionImgStatus = !empty($solutionImgValue) ? '✅ 있음' : '❌ 없음';
    
    echo "<tr><td><strong>Problem Image</strong></td><td>" . htmlspecialchars(substr($problemImgValue, 0, 100)) . (strlen($problemImgValue) > 100 ? '...' : '') . "</td><td>{$problemImgStatus} (" . strlen($problemImgValue) . "자)</td></tr>";
    echo "<tr><td><strong>Solution Image</strong></td><td>" . htmlspecialchars(substr($solutionImgValue, 0, 100)) . (strlen($solutionImgValue) > 100 ? '...' : '') . "</td><td>{$solutionImgStatus} (" . strlen($solutionImgValue) . "자)</td></tr>";
    
    echo "</table>";
    
    // 이미지 미리보기
    echo "<h3>🖼️ 이미지 미리보기</h3>";
    
    if (!empty($problemImgValue)) {
        // URL이 상대경로면 절대경로로 변환
        $problemImgUrl = $problemImgValue;
        if (strpos($problemImgUrl, '/moodle/') === 0) {
            $problemImgUrl = 'https://mathking.kr' . $problemImgUrl;
        }
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>문제 이미지:</strong><br>";
        echo "<img src='" . htmlspecialchars($problemImgUrl) . "' style='max-width: 500px; border: 1px solid #ccc;' onerror=\"this.outerHTML='<span style=color:red>이미지 로드 실패</span>'\">";
        echo "<br><small>URL: " . htmlspecialchars($problemImgValue) . "</small>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ 문제 이미지 없음</p>";
    }
    
    if (!empty($solutionImgValue)) {
        $solutionImgUrl = $solutionImgValue;
        if (strpos($solutionImgUrl, '/moodle/') === 0) {
            $solutionImgUrl = 'https://mathking.kr' . $solutionImgUrl;
        }
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>해설 이미지:</strong><br>";
        echo "<img src='" . htmlspecialchars($solutionImgUrl) . "' style='max-width: 500px; border: 1px solid #ccc;' onerror=\"this.outerHTML='<span style=color:red>이미지 로드 실패</span>'\">";
        echo "<br><small>URL: " . htmlspecialchars($solutionImgValue) . "</small>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ 해설 이미지 없음</p>";
    }
    
    // 이미지 Base64 변환 테스트
    $testImageConvert = isset($_GET['test_image']) ? $_GET['test_image'] === '1' : false;
    
    if ($testImageConvert) {
        echo "<hr>";
        echo "<h2>🔄 이미지 Base64 변환 테스트</h2>";
        
        // imageUrlToBase64 함수 정의 (cURL 사용)
        function testImageUrlToBase64($imageUrl) {
            $result = ['success' => false, 'message' => '', 'size' => 0, 'time' => 0, 'httpCode' => 0];
            $startTime = microtime(true);
            
            if (empty($imageUrl)) {
                $result['message'] = 'URL이 비어있음';
                return $result;
            }
            
            // cURL로 이미지 가져오기
            $fetchWithCurl = function($url) use (&$result) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
                ]);
                
                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                $result['httpCode'] = $httpCode;
                
                if ($curlError) {
                    $result['message'] .= " → cURL 오류: $curlError";
                    return false;
                }
                
                if ($httpCode !== 200) {
                    $result['message'] .= " → HTTP $httpCode";
                    return false;
                }
                
                return $imageData;
            };
            
            // /moodle/ 또는 /pluginfile.php로 시작하는 상대 경로인 경우
            if (strpos($imageUrl, '/moodle/') === 0 || strpos($imageUrl, '/pluginfile.php') === 0) {
                $fullUrl = 'https://mathking.kr' . $imageUrl;
                $result['message'] = "상대 경로 → 절대 URL 변환: $fullUrl";
                $imageData = $fetchWithCurl($fullUrl);
                if ($imageData !== false && !empty($imageData)) {
                    $result['success'] = true;
                    $result['size'] = strlen($imageData);
                    $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
                    $result['message'] .= ' → 성공';
                    return $result;
                }
                $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
                return $result;
            }
            
            // 절대 URL인 경우
            if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
                $result['message'] = "절대 URL (cURL): " . substr($imageUrl, 0, 80) . "...";
                $imageData = $fetchWithCurl($imageUrl);
                if ($imageData !== false && !empty($imageData)) {
                    $result['success'] = true;
                    $result['size'] = strlen($imageData);
                    $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
                    $result['message'] .= ' → 성공';
                    return $result;
                }
                
                // mathking.kr 도메인으로 재시도
                if (strpos($imageUrl, 'mathking.kr') === false) {
                    $retryUrl = 'https://mathking.kr' . $imageUrl;
                    $result['message'] .= ", 재시도: $retryUrl";
                    $imageData = $fetchWithCurl($retryUrl);
                    if ($imageData !== false && !empty($imageData)) {
                        $result['success'] = true;
                        $result['size'] = strlen($imageData);
                        $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
                        $result['message'] .= ' → 성공';
                        return $result;
                    }
                }
            }
            
            $result['message'] = "변환 실패: " . substr($imageUrl, 0, 100);
            $result['time'] = round((microtime(true) - $startTime) * 1000, 2);
            return $result;
        }
        
        // Problem Image 테스트
        echo "<h3>📷 Problem Image 변환 테스트</h3>";
        if (!empty($problemImgValue)) {
            $problemResult = testImageUrlToBase64($problemImgValue);
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><td><strong>원본 URL</strong></td><td style='word-break: break-all; max-width: 500px;'>" . htmlspecialchars($problemImgValue) . "</td></tr>";
            echo "<tr><td><strong>처리 과정</strong></td><td style='word-break: break-all; max-width: 500px;'>" . htmlspecialchars($problemResult['message']) . "</td></tr>";
            echo "<tr><td><strong>결과</strong></td><td>" . ($problemResult['success'] ? '✅ 성공' : '❌ 실패') . "</td></tr>";
            echo "<tr><td><strong>HTTP 코드</strong></td><td>" . $problemResult['httpCode'] . "</td></tr>";
            echo "<tr><td><strong>이미지 크기</strong></td><td>" . number_format($problemResult['size']) . " bytes</td></tr>";
            echo "<tr><td><strong>소요 시간</strong></td><td>" . $problemResult['time'] . " ms</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ problem_image가 비어있어 테스트할 수 없습니다.</p>";
        }
        
        echo "<br>";
        
        // Solution Image 테스트
        echo "<h3>📷 Solution Image 변환 테스트</h3>";
        if (!empty($solutionImgValue)) {
            $solutionResult = testImageUrlToBase64($solutionImgValue);
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><td><strong>원본 URL</strong></td><td style='word-break: break-all; max-width: 500px;'>" . htmlspecialchars($solutionImgValue) . "</td></tr>";
            echo "<tr><td><strong>처리 과정</strong></td><td style='word-break: break-all; max-width: 500px;'>" . htmlspecialchars($solutionResult['message']) . "</td></tr>";
            echo "<tr><td><strong>결과</strong></td><td>" . ($solutionResult['success'] ? '✅ 성공' : '❌ 실패') . "</td></tr>";
            echo "<tr><td><strong>HTTP 코드</strong></td><td>" . $solutionResult['httpCode'] . "</td></tr>";
            echo "<tr><td><strong>이미지 크기</strong></td><td>" . number_format($solutionResult['size']) . " bytes</td></tr>";
            echo "<tr><td><strong>소요 시간</strong></td><td>" . $solutionResult['time'] . " ms</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ solution_image가 비어있어 테스트할 수 없습니다.</p>";
        }
    }
    
    // API 테스트 실행
    $testApi = isset($_GET['test_api']) ? $_GET['test_api'] === '1' : false;
    
    if ($testApi) {
        echo "<hr>";
        echo "<h2>🚀 API 테스트 실행</h2>";
        
        // generate_dialog_narration.php 호출 시뮬레이션
        $postData = http_build_query([
            'interactionId' => $interactionId,
            'solution' => '',
            'generateTTS' => 'false',  // TTS 없이 테스트
            'customSolution' => 'false',
            'hintLevel' => 'early'
        ]);
        
        $apiUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/generate_dialog_narration.php';
        
        echo "<p><strong>요청 URL:</strong> {$apiUrl}</p>";
        echo "<p><strong>요청 파라미터:</strong></p>";
        echo "<pre>" . htmlspecialchars(print_r([
            'interactionId' => $interactionId,
            'solution' => '',
            'generateTTS' => 'false',
            'customSolution' => 'false',
            'hintLevel' => 'early'
        ], true)) . "</pre>";
        
        echo "<p style='color: orange;'>⏳ API 호출 중... (최대 120초)</p>";
        flush();
        
        // cURL로 API 호출
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: ' . $_SERVER['HTTP_COOKIE']  // 세션 쿠키 전달
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        echo "<p><strong>HTTP 응답 코드:</strong> {$httpCode}</p>";
        
        if ($curlError) {
            echo "<p style='color: red;'><strong>cURL 오류:</strong> {$curlError}</p>";
        }
        
        echo "<h3>응답 결과:</h3>";
        
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse) {
            if (isset($jsonResponse['success']) && $jsonResponse['success']) {
                echo "<p style='color: green;'>✅ 성공!</p>";
            } else {
                echo "<p style='color: red;'>❌ 실패: " . htmlspecialchars($jsonResponse['error'] ?? '알 수 없는 오류') . "</p>";
            }
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;'>" . htmlspecialchars(json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ JSON 파싱 실패. 원본 응답:</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;'>" . htmlspecialchars($response) . "</pre>";
        }
    }
    
    if (!$testImageConvert && !$testApi) {
        echo "<br>";
        echo "<a href='?interaction_id={$interactionId}&test_image=1' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 이미지 변환 테스트</a>";
        echo "<a href='?interaction_id={$interactionId}&test_api=1' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 API 테스트 실행</a>";
    }
    
    echo "<br><br><a href='?'>← 목록으로 돌아가기</a>";
}

echo "<hr>";
echo "<p style='color: #888; font-size: 12px;'>테스트 시간: " . date('Y-m-d H:i:s') . "</p>";
?>

