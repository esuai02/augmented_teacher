<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
$role = $userrole ? $userrole->role : 'student';
if ($role !== 'student') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다.</h2>");
}

// 교사 심볼 추출
$tsymbol = '';
if ($USER->firstname) {
    preg_match_all('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $USER->firstname, $matches);
    $emojis = $matches[0];
    
    if (count($emojis) > 0) {
        $tsymbol = $emojis[0];
    } else {
        $teacherId = $USER->id;
        $symbols = array('🌟', '⭐', '✨', '🎯', '🔥', '💫', '🌈', '🎨', '🎪', '🎭');
        $symbolIndex = $teacherId % count($symbols);
        $tsymbol = $symbols[$symbolIndex];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>알림 시스템 테스트</title>
    <style>
        body {
            font-family: 'Nanum Gothic', sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .test-section {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        
        .test-button:hover {
            background: #45a049;
        }
        
        .result-box {
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            min-height: 100px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .success {
            color: green;
            font-weight: bold;
        }
        
        .error {
            color: red;
            font-weight: bold;
        }
        
        .notification-test {
            position: relative;
            display: inline-block;
        }
        
        .notification-icon {
            font-size: 24px;
            cursor: pointer;
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 12px;
            min-width: 20px;
            text-align: center;
        }
        
        .notification-dropdown {
            position: absolute;
            top: 40px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item:hover {
            background: #f5f5f5;
        }
        
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <h1>📢 알림 시스템 테스트</h1>
    
    <div class="test-section">
        <h2>교사 정보</h2>
        <p><strong>이름:</strong> <?php echo $USER->firstname . ' ' . $USER->lastname; ?></p>
        <p><strong>담당 심볼:</strong> <?php echo $tsymbol; ?></p>
    </div>
    
    <div class="test-section">
        <h2>1. AJAX 엔드포인트 직접 테스트</h2>
        <button class="test-button" onclick="testDirectAjax()">Direct AJAX Test</button>
        <button class="test-button" onclick="testRawFetch()">Raw Fetch Test</button>
        <div id="direct-result" class="result-box">결과가 여기 표시됩니다...</div>
    </div>
    
    <div class="test-section">
        <h2>2. 알림 시스템 시뮬레이션</h2>
        <div class="notification-test">
            <span class="notification-icon" onclick="toggleNotification()">🔔</span>
            <span id="test-badge" class="notification-badge" style="display:none;">0</span>
            <div id="test-dropdown" class="notification-dropdown" style="display:none;"></div>
        </div>
        <button class="test-button" onclick="loadNotifications()">알림 로드</button>
        <div id="notification-result" class="result-box">알림 내용이 여기 표시됩니다...</div>
    </div>
    
    <div class="test-section">
        <h2>3. 데이터베이스 직접 조회</h2>
        <button class="test-button" onclick="location.reload()">새로고침</button>
        <?php
        $threeWeeksAgo = strtotime("-3 weeks");
        $alertParams = array($threeWeeksAgo, $threeWeeksAgo);
        
        $sqlAlerts = "SELECT 
                    u.id,
                    u.firstname,
                    u.lastname,
                    COALESCE(absence.total, 0) as total_absence,
                    COALESCE(makeup.total, 0) as total_makeup,
                    (COALESCE(absence.total, 0) - COALESCE(makeup.total, 0)) as needed
                  FROM mdl_user u
                  LEFT JOIN (
                    SELECT userid, SUM(amount) as total 
                    FROM mdl_abessi_classtimemanagement 
                    WHERE event = 'absence' AND hide = 0 AND due >= ?
                    GROUP BY userid
                  ) absence ON u.id = absence.userid
                  LEFT JOIN (
                    SELECT userid, SUM(amount) as total 
                    FROM mdl_abessi_classtimemanagement 
                    WHERE event = 'makeup' AND hide = 0 AND due >= ?
                    GROUP BY userid
                  ) makeup ON u.id = makeup.userid
                  INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                  WHERE uid.fieldid = 22 AND uid.data = 'student'
                  AND u.deleted = 0 AND u.suspended = 0";
        
        if (!empty($tsymbol)) {
            $sqlAlerts .= " AND (u.firstname LIKE ? OR u.firstname LIKE ?)";
            $alertParams[] = '%' . $tsymbol . '%';
            $alertParams[] = '%' . $tsymbol . '%';
        }
        
        $sqlAlerts .= " HAVING needed >= 4 OR needed <= -5
                       ORDER BY ABS(needed) DESC
                       LIMIT 10";
        
        try {
            $alertStudents = $DB->get_records_sql($sqlAlerts, $alertParams);
            
            if ($alertStudents) {
                echo "<table>";
                echo "<tr><th>ID</th><th>이름</th><th>결석</th><th>보강</th><th>필요</th><th>타입</th></tr>";
                
                foreach ($alertStudents as $student) {
                    $type = $student->needed >= 4 ? '보강 필요' : '추가 학습';
                    $bgColor = $student->needed >= 4 ? '#fee2e2' : '#dcfce7';
                    
                    echo "<tr style='background: $bgColor;'>";
                    echo "<td>{$student->id}</td>";
                    echo "<td>{$student->firstname} {$student->lastname}</td>";
                    echo "<td>" . round($student->total_absence, 1) . "</td>";
                    echo "<td>" . round($student->total_makeup, 1) . "</td>";
                    echo "<td><strong>" . round($student->needed, 1) . "</strong></td>";
                    echo "<td>$type</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p class='success'>✅ 총 " . count($alertStudents) . "명의 알림 대상 학생 발견</p>";
            } else {
                echo "<p>알림 대상 학생이 없습니다.</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ 오류: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <script>
        function testDirectAjax() {
            const resultDiv = document.getElementById('direct-result');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            const url = 'attendance_teacher.php?ajax=alerts';
            console.log('Testing URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    resultDiv.innerHTML = '<h4>Raw Response:</h4><pre>' + text + '</pre>';
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        resultDiv.innerHTML += '<h4>Parsed JSON:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        
                        if (Array.isArray(data)) {
                            resultDiv.innerHTML += '<p class="success">✅ 성공: ' + data.length + '개의 알림 데이터 수신</p>';
                        } else if (data.error) {
                            resultDiv.innerHTML += '<p class="error">❌ 서버 오류: ' + data.error + '</p>';
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        resultDiv.innerHTML += '<p class="error">❌ JSON 파싱 오류: ' + e.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    resultDiv.innerHTML = '<p class="error">❌ Fetch 오류: ' + error.message + '</p>';
                });
        }
        
        function testRawFetch() {
            const resultDiv = document.getElementById('direct-result');
            resultDiv.innerHTML = '<p>Testing with XMLHttpRequest...</p>';
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'attendance_teacher.php?ajax=alerts', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('XHR Status:', xhr.status);
                    console.log('XHR Response:', xhr.responseText);
                    
                    if (xhr.status === 200) {
                        resultDiv.innerHTML = '<h4>XHR Response:</h4><pre>' + xhr.responseText + '</pre>';
                    } else {
                        resultDiv.innerHTML = '<p class="error">❌ XHR Error: Status ' + xhr.status + '</p>';
                    }
                }
            };
            
            xhr.send();
        }
        
        function loadNotifications() {
            const resultDiv = document.getElementById('notification-result');
            const badge = document.getElementById('test-badge');
            const dropdown = document.getElementById('test-dropdown');
            
            resultDiv.innerHTML = '<p>Loading notifications...</p>';
            
            fetch('attendance_teacher.php?ajax=alerts')
                .then(response => response.json())
                .then(data => {
                    console.log('Notification data:', data);
                    
                    if (Array.isArray(data) && data.length > 0) {
                        // 배지 업데이트
                        badge.style.display = 'inline-block';
                        badge.textContent = data.length;
                        
                        // 드롭다운 내용 생성
                        let dropdownHTML = '<div style="padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;">📢 주의가 필요한 학생 (' + data.length + '명)</div>';
                        
                        data.forEach(alert => {
                            let icon = '';
                            let message = '';
                            let bgColor = '';
                            
                            if (alert.type === 'makeup_needed') {
                                icon = '⚠️';
                                message = `보강 필요: ${alert.hours}시간`;
                                bgColor = '#fee2e2';
                            } else if (alert.type === 'extra_study') {
                                icon = '⭐';
                                message = `초과 학습: ${alert.hours}시간`;
                                bgColor = '#e0f2fe';
                            } else if (alert.type === 'surplus_study') {
                                icon = '✅';
                                message = `추가 학습: ${alert.hours}시간`;
                                bgColor = '#dcfce7';
                            }
                            
                            dropdownHTML += `
                                <div class="notification-item" style="background: ${bgColor};">
                                    <div>${icon} <strong>${alert.name}</strong></div>
                                    <div style="margin-top: 4px; color: #666;">${message}</div>
                                </div>
                            `;
                        });
                        
                        dropdown.innerHTML = dropdownHTML;
                        resultDiv.innerHTML = '<p class="success">✅ ' + data.length + '개의 알림 로드 완료</p>';
                        resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } else {
                        badge.style.display = 'none';
                        dropdown.innerHTML = '<div style="padding: 20px; text-align: center;">✨ 알림이 없습니다</div>';
                        resultDiv.innerHTML = '<p>알림이 없습니다.</p>';
                    }
                })
                .catch(error => {
                    console.error('Load error:', error);
                    resultDiv.innerHTML = '<p class="error">❌ 로드 오류: ' + error.message + '</p>';
                });
        }
        
        function toggleNotification() {
            const dropdown = document.getElementById('test-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
        
        // 페이지 로드 시 자동 테스트
        window.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, starting auto test...');
            setTimeout(testDirectAjax, 1000);
        });
    </script>
</body>
</html>